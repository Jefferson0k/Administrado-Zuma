<?php

namespace App\Http\Controllers;

use App\Http\Requests\BLog\BlogStoreRequest;
use App\Http\Requests\Category\CategoryStoreRequest;
use App\Http\Requests\CorporateEntity\StoreCorporateEntityRequests;
use App\Http\Requests\Product\ProductStoreRequest;
use App\Http\Resources\Tasas\CorporateEntity\CorporateEntityResource;
use App\Models\Category;
use App\Models\CategoryProduct;
use App\Models\CorporateEntity;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostProduct;
use App\Models\PostRating;
use App\Models\PostImage;
use App\Models\Product;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\ComentarioPost;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BlogController extends Controller
{
    public function index()
    {
        return Inertia::render('panel/Blog/Index', [
            'user' => Auth::user()  // 👈 aquí pasas el usuario
        ]);
    }


    public function create()
    {
        return Inertia::render('panel/Blog/CreatePost');
    }

    public function seguimiento()
    {
        return Inertia::render('panel/Blog/Seguimiento');
    }

    public function categorias()
    {
        return Inertia::render('panel/Blog/Categorias');
    }

    public function lista()
    {
        $posts = Post::with([
            'categories',
            'ratings',
            'user:id,name',
            'updated_user:id,name',
            'images',              // ✅ eager-load gallery images so edit dialog has them
        ])->get();

        return response()->json([
            'message' => 'Datos cargados exitosamente.',
            'posts' => $posts,
        ], 201);
    }

    public function productos()
    {
        $products = Product::all();
        return response()->json($products);
    }

    public function listar_categoria(Request $request)
    {
        // cantidad por página, si no envían nada será 10 por defecto
        $perPage = $request->input('per_page', 10);

        $categories = Category::with([
            'products:id,nombre',
        ])->paginate($perPage);

        return response()->json($categories);
    }

    public function listar_categoria_filtrada($id)
    {
        $categories = Category::with([
            'products:id,nombre',
        ])
            ->whereHas('products', function ($query) use ($id) {
                $query->where('products.id', $id);
            })
            ->get();
        return response()->json($categories);
    }

    public function guardar(Request $request)
    {
        // Validación base (múltiples imágenes)
        $validated = $request->validate([
            'user_id'           => 'required|exists:users,id',
            'updated_user_id'   => 'nullable|integer',
            'titulo'            => 'required|string|max:255',
            'contenido'         => 'required|string',
            'enlaces'           => 'nullable|string|max:100000',

            // ✅ Acepta EITHER imagen (single) OR imagenes (array)
            'imagenes'          => 'required_without:imagen|array|min:1',
            'imagenes.*'        => 'image|mimes:jpeg,jpg,png|max:10240',
            'imagen'            => 'required_without:imagenes|image|mimes:jpeg,jpg,png|max:10240',

            'category_id'       => 'required', // CSV o array
            'state_id'          => 'required|exists:states,id',
            'fecha_programada'  => 'nullable|date_format:Y-m-d H:i:s',
            'fecha_publicacion' => 'nullable|date_format:Y-m-d H:i:s',
        ]);

        // Normalizar categorías a array<int>
        $categoryIds = $request->input('category_id');
        if (is_string($categoryIds)) {
            $categoryIds = collect(explode(',', $categoryIds))
                ->map(fn($id) => (int) trim($id))
                ->filter()
                ->values()
                ->all();
        } elseif (is_array($categoryIds)) {
            $categoryIds = collect($categoryIds)
                ->map(fn($id) => (int) $id)
                ->filter()
                ->values()
                ->all();
        } else {
            $categoryIds = [];
        }

        // Verificar que existan
        if (Category::whereIn('id', $categoryIds)->count() !== count($categoryIds)) {
            return response()->json(['error' => 'Una o más categorías no existen.'], 422);
        }

        $imagenes = $request->file('imagenes');

        return DB::transaction(function () use ($validated, $imagenes, $categoryIds) {
            // 1) Guardar imagen principal en posts.imagen
            $principal   = $imagenes[0];
            $mainName    = Str::random(10) . '.' . $principal->getClientOriginalExtension();
            $principal->storeAs('images', $mainName, 'public');

            // 2) Crear post (evitar mass-assign de "imagenes")
            $postData = $validated;
            unset($postData['imagenes']);
            $postData['imagen'] = $mainName;

            $post = Post::create($postData);

            // 3) Guardar categorías
            foreach ($categoryIds as $catId) {
                PostCategory::create([
                    'post_id'    => $post->id,
                    'category_id' => $catId,
                ]);
            }

            // 4) Guardar imágenes adicionales en post_image
            if (count($imagenes) > 1) {
                foreach (array_slice($imagenes, 1) as $img) {
                    $name = Str::random(10) . '.' . $img->getClientOriginalExtension();
                    $img->storeAs('images', $name, 'public');

                    PostImage::create([
                        'post_id'    => $post->id,
                        'image_path' => $name,
                    ]);
                }
            }

            return response()->json([
                'message' => 'Publicación creada exitosamente.',
                'post'    => $post->load('images'), // si defines la relación
            ], 201);
        });
    }


    public function getcomentarios($id)
    {

        $comentarios = ComentarioPost::where('post_id', $id)->get();

        return response()->json($comentarios);
    }



    public function saveComentario(Request $request)
    {
        // Validación de datos
        $validated = $request->validate([
            'post_id'    => 'required|exists:posts,id',
            'comentario' => 'required|string|max:500',
            'email'      => 'required|email|max:255',
            'estrellas' => 'nullable|numeric|min:0|max:5',
            'nombre' => 'required|string',
        ]);

        // Inserción en la base de datos
        $comentario = ComentarioPost::create([
            'post_id'    => $validated['post_id'],
            'comentario' => $validated['comentario'],
            'email'      => $validated['email'],
        ]);

        $rating = Rating::create([
            'post_id'    => $validated['post_id'],
            'entrellas' => $validated['estrellas'] ?? 3.00,
            'ip'      => '192.168.0.10',

        ]);

        return response()->json([
            'ok'        => true,
            'message'   => 'Comentario guardado con éxito',
            'data'      => $comentario
        ], 201);
    }



    public function guardar_categoria(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'categorias' => 'required|array|min:1',
            'categorias.*' => 'string|max:255',
        ]);

        $categoriasCreadas = [];

        foreach ($validated['categorias'] as $nombreCategoria) {
            $category = Category::create([
                'nombre' => $nombreCategoria,
                'product_id' => $request->product_id,

            ]);

            CategoryProduct::create([
                'category_id' => $category->id,
                'product_id' => $validated['product_id'],
            ]);

            $categoriasCreadas[] = $category;
        }

        return response()->json([
            'message' => 'Categorías creadas correctamente.',
            'categorias' => $categoriasCreadas,
        ], 201);
    }

    /*public function actualizar_categoria(ProductStoreRequest $request, $id){
        $validated = $request->validated();
        $product = Product::findOrFail($id);
        $product->update($validated);
        return response()->json(['message' => 'Categoría actualizado correctamente']);
    }*/

    public function eliminar_categoria($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();
        return response()->json(['message' => 'Categoría eliminada correctamente']);
    }

    public function guardar_rating(Request $request)
    {
        $request->validate([
            'post_id' => 'required|exists:posts,id',
            'estrellas' => 'required|integer|between:1,5',
        ]);
        $rating = Rating::updateOrCreate(
            [
                'post_id' => $request->post_id,
                //'ip' => $this->getRealIp($request),
                'ip' => $request->ip(),
            ],
            [
                'estrellas' => $request->estrellas,
            ]
        );
        PostRating::updateOrCreate([
            'post_id' => $request->post_id,
            'rating_id' => $rating->id,
        ]);
        return response()->json([
            'message' => 'Rating guardado',
            'rating_id' => $rating->id,
        ], 200);
    }

    public function eliminar($id)
    {
        $post = Post::with('images')->findOrFail($id);

        return DB::transaction(function () use ($post) {
            // Eliminar imagen principal
            if ($post->imagen && Storage::disk('public')->exists("images/{$post->imagen}")) {
                Storage::disk('public')->delete("images/{$post->imagen}");
            }

            // Eliminar imágenes adicionales
            foreach ($post->images as $img) {
                if (Storage::disk('public')->exists("images/{$img->image_path}")) {
                    Storage::disk('public')->delete("images/{$img->image_path}");
                }
                $img->delete();
            }

            // Eliminar categorías
            PostCategory::where('post_id', $post->id)->delete();

            // Eliminar post
            $post->delete();

            return response()->json([
                'message' => 'Publicación eliminada exitosamente.'
            ]);
        });
    }


    /*public function actualizar(BlogStoreRequest $request, $id){
        $validated = $request->validated();
        if ($request->hasFile('imagen')) {
            $img = $request->file('imagen');
            Log::debug($img);
            $allowedMimeTypes = ['image/jpeg', 'image/png'];
            if ($img->isValid() && in_array($img->getMimeType(), $allowedMimeTypes)) {
                $randomName = Str::random(10) . '.' . $img->getClientOriginalExtension();
                $img->storeAs('public/images', $randomName);
                $validated['imagen'] = $randomName;
            } else {
                return response()->json(['error' => 'Solo se permiten imágenes JPG o PNG válidas'], 422);
            }
        }
        $post = Post::findOrFail($id);
        $post->update($validated);
        return response()->json(['message' => 'Publicación actualizada correctamente']);
    }*/

    public function actualizar(Request $request, $id)
    {
        $validated = $request->validate([
            'user_id'           => 'required|exists:users,id',
            'updated_user_id'   => 'nullable|integer',
            'titulo'            => 'required|string|max:255',
            'contenido'         => 'required|string',
            'resumen'           => 'nullable|string|max:1000',

            // ⚠️ ya no es required; opcional si quieres cambiar la portada con una NUEVA imagen
            'imagen'            => 'nullable|image|mimes:jpeg,jpg,png|max:10240',

            // categorías puede llegar como CSV
            'category_id'       => 'required|string',

            'state_id'          => 'required|exists:states,id',
            'fecha_programada'  => 'nullable|date_format:Y-m-d H:i:s',

            // NUEVO: manejo granular de galería
            'new_images'        => 'nullable|array',
            'new_images.*'      => 'image|mimes:jpeg,jpg,png|max:10240',
            'delete_image_ids'  => 'nullable|array',
            'delete_image_ids.*' => 'integer|exists:post_images,id',
            'cover_image_id'    => 'nullable|integer|exists:post_images,id',
        ]);

        $post = Post::with('images')->findOrFail($id);

        // Actualiza campos base
        $post->update([
            'user_id'          => $validated['user_id'],
            'updated_user_id'  => $request->input('updated_user_id'),
            'titulo'           => $validated['titulo'],
            'contenido'        => $validated['contenido'],
            'resumen'          => $request->input('resumen'),
            'state_id'         => $validated['state_id'],
            'fecha_programada' => $request->input('fecha_programada'),
        ]);

        // Sincroniza categorías
        $category_ids = collect(explode(',', $validated['category_id']))
            ->map(fn($v) => (int) trim($v))
            ->filter()
            ->values()
            ->all();

        $existing = Category::whereIn('id', $category_ids)->pluck('id')->toArray();
        $missing = array_diff($category_ids, $existing);
        if (count($missing)) {
            return response()->json(['error' => 'Una o más categorías no existen: ' . implode(', ', $missing)], 422);
        }
        $post->categories()->sync($category_ids);

        // Si subieron una NUEVA portada (imagen)
        if ($request->hasFile('imagen')) {
            $img = $request->file('imagen');
            $name = Str::random(10) . '.' . $img->getClientOriginalExtension();
            $img->storeAs('images', $name, 'public');

            // borra la portada anterior si existía
            if ($post->imagen && Storage::disk('public')->exists("images/{$post->imagen}")) {
                Storage::disk('public')->delete("images/{$post->imagen}");
            }
            $post->imagen = $name;
            $post->save();
        }

        // Cambiar portada desde una imagen existente (post_images)
        if ($request->filled('cover_image_id')) {
            $img = PostImage::where('post_id', $post->id)->findOrFail($request->cover_image_id);
            // Mueve portada actual a galería (opcional) o simplemente reemplaza
            $post->imagen = $img->image_path;
            $post->save();
            // si NO quieres duplicados, puedes eliminar este PostImage o mantenerlo
            // $img->delete();
        }

        // Agregar nuevas imágenes a galería
        if ($request->hasFile('new_images')) {
            foreach ($request->file('new_images') as $file) {
                $name = Str::random(10) . '.' . $file->getClientOriginalExtension();
                $file->storeAs('images', $name, 'public');
                PostImage::create([
                    'post_id'    => $post->id,
                    'image_path' => $name,
                ]);
            }
        }

        // Eliminar imágenes marcadas
        $deleteIds = $request->input('delete_image_ids', []);
        if (!empty($deleteIds)) {
            $imgs = PostImage::where('post_id', $post->id)->whereIn('id', $deleteIds)->get();
            foreach ($imgs as $im) {
                if (Storage::disk('public')->exists("images/{$im->image_path}")) {
                    Storage::disk('public')->delete("images/{$im->image_path}");
                }
                $im->delete();
            }
        }

        return response()->json(['message' => 'Publicación actualizada correctamente']);
    }



    public function publicar($user_id, $post_id, $state_id)
    {
        $post = Post::findOrFail($post_id);
        $post->updated_user_id = $user_id;
        $post->state_id = $state_id;
        $post->fecha_publicacion = now();
        $post->save();
        return response()->json(['message' => 'Publicación actualizada correctamente']);
    }

    public function publicaciones(Request $request)
    {
        $query = Post::with(['ratings', 'categories'])
            ->where('state_id', 2);

        // Buscar por texto
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('posts.titulo', 'like', "%{$search}%")
                    ->orWhere('posts.contenido', 'like', "%{$search}%")
                    ->orWhereHas('categories', function ($q2) use ($search) {
                        $q2->where('categories.nombre', 'like', "%{$search}%");
                    });
            });
        }

        // Filtrar por categorías (uno o varios IDs)
        if ($request->filled('category_id')) {
            $categories = (array) $request->category_id; // puede venir como array o single id
            $query->whereHas('categories', function ($q) use ($categories) {
                $q->whereIn('categories.id', $categories);
            });
        }

        // Paginación
        $perPage = $request->get('per_page', 10);
        $posts = $query->paginate($perPage);

        return response()->json($posts);
    }


    public function showPost($id)
    {
        $post = Post::with(['ratings', 'categories', 'images'])
            ->where('id', $id)
            ->where('state_id', 2)
            ->first();

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        // 👇 Incrementa visitas totales (una por carga del detalle)
        $post->increment('views_total');

        // Relacionados (igual que ya tenías)
        $categoryIds = $post->categories->pluck('id')->toArray();
        $related = Post::where('state_id', 2)
            ->where('id', '!=', $post->id)
            ->whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('categories.id', $categoryIds);
            })
            ->limit(5)
            ->get();

        $post->related_articles = $related;

        return response()->json($post);
    }



    private function getRealIp(Request $request): string
    {
        $keys = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($keys as $key) {
            if ($ip = $request->server($key)) {
                // Si es una lista (ej. proxy), toma la primera IP
                $parts = explode(',', $ip);
                return trim($parts[0]);
            }
        }

        return $request->ip(); // fallback seguro
    }
}
