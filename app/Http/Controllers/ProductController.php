<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    //
    public function show()
    {
        return Product::all();
    }

    public function create(Request $request, Category $slug)
    {
        // dd($request);
        //  Валидация данных
        $validator = $request->validate([
            'name' => 'required|string',
            'desc' => 'string|nullable',
            'count' => 'required|integer',
            'price' => 'required|decimal:0,4',
            'producer' => 'required|string',
            'volume' => 'required|decimal:0,4',
            'country' => 'required|string',
            'img' => 'required|string',
        ]);
        // dd($validator['producer']);

        // //  Генерация slug
        $slug_product = Str::slug($validator['name']);
        $product = $slug->products()->create([
            'name' => $validator['name'],
            'desc' => $validator['desc'],
            'count' => $validator['count'],
            'price' => $validator['price'],
            'producer' => $validator['producer'],
            'volume' => $validator['volume'],
            'country' => $validator['country'],
            'img' => $validator['img'],
            'category_id' => $slug->id,
            'slug' => $slug_product
        ]);
        // dd($product);

        // //  Ответ (или редирект)
        return response()->json([
            'message' => 'Товар успешно создан',
            'category' => $product,
        ]);
    }

    public function search(Request $request)
    {
        $slug = Str::slug($request->get('name'));
        return Product::where('slug', $slug)->get();  
    }
}
