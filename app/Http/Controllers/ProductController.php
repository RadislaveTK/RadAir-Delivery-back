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

        // 🔹 Словарь синонимов для категорий
        $synonyms = [
            'fastfood' => ['fast food', 'fastfood', 'фаст фуд', 'фастфуд', 'бургеры', 'бургер', 'шаурма', 'пицца', "суши", "быстрая еда", "фаст"],
            'restouran' => ['restouran', 'ресторан', 'рестик', 'ресмторан'],
            'products' => ['продукты', 'товары', 'products', 'food', "еда"],
        ];

        // 🔹 Функция нормализации параметра (поиск в синонимах)
        $normalize = function ($param) use ($synonyms) {
            if (!$param) return null;

            $paramLower = mb_strtolower($param);

            foreach ($synonyms as $main => $alts) {
                if (in_array($paramLower, $alts) || $paramLower === $main) {
                    return $main;
                }
            }

            return $param; // если синонима нет — оставляем как есть
        };

        $nameParamNorm = $normalize($nameParam);
        $categoryParamNorm = $normalize($categoryParam);

        // 🔹 Поиск по имени товара или категории
        if ($nameParamNorm) {
            $slug = Str::slug($nameParamNorm);

            $query->where(function ($q) use ($nameParamNorm, $slug) {
                $q->where('name', 'like', "%{$nameParamNorm}%")
                    ->orWhereHas('category', function ($cat) use ($nameParamNorm, $slug) {
                        $cat->where('name', 'like', "%{$nameParamNorm}%")
                            ->orWhere('slug', $slug);
                    });
            });
        }

        // 🔹 Фильтр по категории
        if ($categoryParamNorm) {
            $slug = Str::slug($categoryParamNorm);

            $query->whereHas('category', function ($cat) use ($categoryParamNorm, $slug) {
                $cat->where('name', 'like', "%{$categoryParamNorm}%")
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
    }
}
