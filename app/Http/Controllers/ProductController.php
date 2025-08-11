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

    public function create(Request $request)
    {
        // dd($request);
        //  Валидация данных
        $validator = $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
            'desc' => 'string|nullable',
            'count' => 'required|integer',
            'price' => 'required|decimal:0,4',
            'producer' => 'required|string',
            'volume' => 'required|decimal:0,4',
            'country' => 'required|string',
            'img' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $slug = Category::where('name', $validator['category'])->first();
        $path = $request->file('img')->store('products', 'public');

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
            'img' => $path, // путь к картинке
            'category_id' => $slug->id,
            'slug' => Str::slug($validator['name'])
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
            return Product::all();
        }

        $slug = Str::slug($name);

        // Синонимы категорий
        $synonyms = [
            'fastfood' => ['fast food', 'fastfood', 'фаст фуд', 'бургеры', 'бургер', 'шаурма', 'шаверма', 'гамбургер', 'пицца'],
            'restouran' => ['ресторан', 'кафе', 'столовая', 'пиццерия'],
            'products' => ['продукты', 'еда', 'магазин']
        ];

        $categorySlug = null;
        foreach ($synonyms as $cat => $words) {
            foreach ($words as $word) {
                if (mb_stripos($name, $word) !== false) {
                    $categorySlug = $cat;
                    break 2;
                }
            }
        }

        return Product::query()
            ->when($categorySlug, function ($q) use ($categorySlug) {
                $q->whereHas('category', function ($query) use ($categorySlug) {
                    $query->where('slug', $categorySlug);
                });
            })
            ->orWhere('name', 'like', "%$name%")
            ->orWhere('slug', 'like', "%$slug%")
            ->get();
    }
}
