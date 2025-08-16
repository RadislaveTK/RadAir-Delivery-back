<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
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
        // Валидация
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

        // Находим категорию
        $slug = Category::where('name', $validator['category'])->first();

        if (!$slug) {
            return response()->json(['error' => 'Категория не найдена'], 404);
        }

        // API-ключ ImgBB (вынеси в .env)
        $apiKey = env('IMGBB_API_KEY');

        // Читаем содержимое файла и кодируем в base64
        $imagePath = $request->file('img')->getRealPath();
        $imageData = base64_encode(file_get_contents($imagePath));

        // Отправляем запрос в ImgBB
        $client = new \GuzzleHttp\Client();
        $response = $client->post('https://api.imgbb.com/1/upload', [
            'query' => [
                'key' => $apiKey,
            ],
            'form_params' => [
                'image' => $imageData,
                // 'expiration' => 600, // опционально, автоудаление через X секунд
            ],
        ]);

        $result = json_decode($response->getBody(), true);

        if (!isset($result['success']) || !$result['success']) {
            return response()->json(['error' => 'Ошибка загрузки фото в ImgBB'], 500);
        }

        // URL картинки с ImgBB
        $imgUrl = $result['data']['url'];

        // Создаём продукт
        $product = $slug->products()->create([
            'name' => $validator['name'],
            'desc' => $validator['desc'],
            'count' => $validator['count'],
            'price' => $validator['price'],
            'producer' => $validator['producer'],
            'volume' => $validator['volume'],
            'country' => $validator['country'],
            'img' => $imgUrl,  // сохраняем не путь к файлу, а URL ImgBB
            'category_id' => $slug->id,
            'slug' => Str::slug($validator['name'])
        ]);

        return response()->json([
            'message' => 'Товар успешно создан',
            'category' => $product,
        ]);
    }

    public function search(Request $request)
    {
        $nameParam = trim($request->get('name'));
        $categoryParam = trim($request->get('category'));
        $producerParam = trim($request->get('producer'));
        $countryParam = trim($request->get('country'));

        $query = Product::query()->with('category');

        // 🔹 Поиск по имени товара или по категории (если name совпадает с категорией)
        if ($nameParam) {
            $slug = Str::slug($nameParam);

            $query->where(function ($q) use ($nameParam, $slug) {
                $q->where('name', 'like', "%{$nameParam}%")
                    ->orWhereHas('category', function ($cat) use ($nameParam, $slug) {
                        $cat->where('name', 'like', "%{$nameParam}%")
                            ->orWhere('slug', $slug);
                    });
            });
        }

        // 🔹 Фильтр по категории (slug или имя)
        if ($categoryParam) {
            $slug = Str::slug($categoryParam);

            $query->whereHas('category', function ($cat) use ($categoryParam, $slug) {
                $cat->where('name', 'like', "%{$categoryParam}%")
                    ->orWhere('slug', $slug);
            });
        }

        // 🔹 Фильтр по производителю
        if ($producerParam) {
            $query->where('producer', 'like', "%{$producerParam}%");
        }

        // 🔹 Фильтр по стране
        if ($countryParam) {
            $query->where('country', 'like', "%{$countryParam}%");
        }

        // Пагинация
        $products = $query->paginate(10);
        return response($products, 200);
        // return response()->json([
        //     'data' => $products->items(),
        //     'meta' => [
        //         'current_page' => $products->currentPage(),
        //         'last_page' => $products->lastPage(),
        //         'total' => $products->total(),
        //     ]
        // ]);
    }
}
