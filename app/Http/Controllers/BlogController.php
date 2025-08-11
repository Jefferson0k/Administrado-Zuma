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

    public function guardar(BlogStoreRequest $request){
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
        $post = Post::create($validated);
        $category_ids = explode(',', $validated['category_id']);
        foreach ($category_ids as $category_id) {
            PostCategory::create([
                'post_id' => $post->id,
                'category_id' => (int) trim($category_id),
            ]);
        }

        return response()->json([
            'message' => 'Publiación creada exitosamente.',
            'post' => $post,
        ], 201);
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
    
    public function actualizar(BlogStoreRequest $request, $id){
        $validated = $request->validated();

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
        $posts = Post::with('ratings')->where('state_id', 2)->get();
        return response()->json($posts);
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
