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
use Illuminate\Support\Str;
use Inertia\Inertia;

class BlogController extends Controller
{
    public function index(){
        return Inertia::render('panel/Blog/Index', [
            'user' => Auth::user()  // 👈 aquí pasas el usuario
        ]);
    }

     
    public function create(){
        return Inertia::render('panel/Blog/CreatePost');
    }

    public function seguimiento(){
        return Inertia::render('panel/Blog/Seguimiento');
    }

    public function categorias(){
        return Inertia::render('panel/Blog/Categorias');
    }

    public function lista(){
        $posts = Post::with([
            'categories',
            'ratings',
            'user:id,name',
            'updated_user:id,name',
        ])->get();

        return response()->json([
            'message' => 'Datos cargados exitosamente.',
            'posts' => $posts,
        ], 201);
    }

    public function productos(){
        $products = Product::all();
        return response()->json($products);
    }

    public function listar_categoria(){
        $categories = Category::with([
            'products:id,nombre',
        ])->get();
        return response()->json($categories);
    }

    public function listar_categoria_filtrada($id){
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
        'resumen'           => 'nullable|string|max:1000',
        'imagenes'          => 'required|array|min:1',
        'imagenes.*'        => 'image|mimes:jpeg,png|max:10240', // 10MB para coincidir con el frontend
        'category_id'       => 'required', // puede venir como CSV o como array
        'state_id'          => 'required|exists:states,id',
        'fecha_programada'  => 'nullable|date_format:Y-m-d H:i:s',
        'fecha_publicacion' => 'nullable|date_format:Y-m-d H:i:s',
    ]);

    // Normalizar categorías a array<int>
    $categoryIds = $request->input('category_id');
    if (is_string($categoryIds)) {
        $categoryIds = collect(explode(',', $categoryIds))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values()
            ->all();
    } elseif (is_array($categoryIds)) {
        $categoryIds = collect($categoryIds)
            ->map(fn ($id) => (int) $id)
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
                'category_id'=> $catId,
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


    public function guardar_categoria(CategoryStoreRequest $request)
    {
        $validated = $request->validated();
        $category = Category::create($validated);
        CategoryProduct::create([
            'category_id' => $category->id,
            'product_id' => $validated['product_id'],
        ]);
        return response()->json([
            'message' => 'Categoría creada correctamente.',
            'category' => $category,
        ], 201);
    }

    /*public function actualizar_categoria(ProductStoreRequest $request, $id){
        $validated = $request->validated();
        $product = Product::findOrFail($id);
        $product->update($validated);
        return response()->json(['message' => 'Categoría actualizado correctamente']);
    }*/

    public function eliminar_categoria($id){
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
    
    public function eliminar($id){
        $post = Post::findOrFail($id);
        $post->delete();
        return response()->json(['message' => 'Publicación eliminada correctamente']);
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
    
    public function actualizar(Request $request, $id){
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'updated_user_id' => 'nullable|integer',
            'titulo' => 'required|string|max:255',
            'contenido' => 'required|string',
            'resumen' => 'nullable|string|max:1000',
            'imagen' => 'required|image|max:2048', // ajusta si no es imagen
            //'imagen' => 'nullable|string|max:255',
            //'product_id' => 'required|exists:products,id',
            'category_id' => 'required|exists:categories,id',
            'state_id' => 'required|exists:states,id',
            'fecha_programada' => 'nullable|date_format:Y-m-d H:i:s',
            'fecha_publicacion'=> 'nullable|date_format:Y-m-d H:i:s',
        ]);

        // Si hay imagen, la procesamos
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

        // Actualizamos el post
        $post = Post::findOrFail($id);
        $post->update($validated);

        // 💡 Sincronizamos las categorías si vienen en el request
        if (isset($validated['category_id'])) {
            $category_ids = explode(',', $validated['category_id']);
            $category_ids = array_map('intval', array_map('trim', $category_ids));

            // Validar que existan todas las categorías antes de sincronizar
            $existingCategoryIds = Category::whereIn('id', $category_ids)->pluck('id')->toArray();
            $missing = array_diff($category_ids, $existingCategoryIds);
            if (count($missing)) {
                return response()->json([
                    'error' => 'Una o más categorías no existen: ' . implode(', ', $missing)
                ], 422);
            }

            $post->categories()->sync($category_ids);
        }

        return response()->json(['message' => 'Publicación actualizada correctamente']);
    }


    public function publicar($user_id, $post_id, $state_id){
        $post = Post::findOrFail($post_id);
        $post->updated_user_id = $user_id;
        $post->state_id = $state_id;
        $post->fecha_publicacion = now();
        $post->save();
        return response()->json(['message' => 'Publicación actualizada correctamente']);
    }

    public function publicaciones(){
        $posts = Post::with(['ratings','categories'])->where('state_id', 2)->get();
        return response()->json($posts);
    }


 public function showPost($id)
{
    $post = Post::with(['ratings', 'categories','images'])
        ->where('id', $id)
        ->where('state_id', 2)
        ->first(); // 👈 devuelve un solo post o null

    if (!$post) {
        return response()->json([
            'message' => 'Post not found'
        ], 404);
    }

    // Obtiene IDs de categorías
    $categoryIds = $post->categories->pluck('id')->toArray();

    // Busca posts relacionados
    $related = Post::where('state_id', 2)
        ->where('id', '!=', $post->id)
        ->whereHas('categories', function ($query) use ($categoryIds) {
            $query->whereIn('categories.id', $categoryIds);
        })
        ->limit(5)
        ->get();

    // Agrega los relacionados al post
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
