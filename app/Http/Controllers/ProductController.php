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
        $name = trim($request->get('name'));

        if (!$name) {
            return response()->json([]);
        }

        $slug = Str::slug($name);

        // Синонимы категорий
        $synonyms = [
            'fastfood' => ['fast food','fastfood', 'фаст фуд', 'бургеры', 'бургер', 'шаурма', 'шаверма', 'гамбургер', 'пицца'],
            'restouran' => ['ресторан', 'кафе', 'столовая', 'пиццерия'],
            'products' => ['продукты', 'еда', 'магазин']
        ];

        $category = null;
        foreach ($synonyms as $cat => $words) {
            foreach ($words as $word) {
                if (mb_stripos($name, $word) !== false) {
                    $category = $cat;
                    break 2;
                }
            }
        }

        return Product::query()
            ->when($category, function ($q) use ($category) {
                $q->where('category', $category);
            })
            ->orWhere('name', 'like', "%$name%")
            ->orWhere('slug', 'like', "%$slug%")
            ->get();
    }
}
