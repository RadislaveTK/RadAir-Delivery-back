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
        $name = trim($request->get('name'));
        $categoryParam = trim($request->get('category'));

        $slug = $name ? Str::slug($name) : null;

        // Синонимы категорий
        $synonyms = [
            'fastfood' => ['fast food', 'fastfood', 'фаст фуд', 'бургеры', 'бургер', 'шаурма', 'шаверма', 'гамбургер', 'пицца'],
            'restouran' => ['ресторан', 'кафе', 'столовая', 'пиццерия'],
            'products' => ['продукты', 'еда', 'магазин']
        ];

        $categorySlug = null;

        // Если category передан — ищем по нему
        if ($categoryParam) {
            $categorySlug = Str::slug($categoryParam);
        } else {
            // Если category не передан — пробуем определить по синонимам name
            foreach ($synonyms as $cat => $words) {
                foreach ($words as $word) {
                    if (mb_stripos($name, $word) !== false) {
                        $categorySlug = $cat;
                        break 2;
                    }
                }
            }
        }

        $query = Product::query();

        // Фильтр по категории
        if ($categorySlug) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q
                    ->where('slug', $categorySlug)
                    ->orWhere('name', 'like', "%$categorySlug%");
            });
        }

        // Фильтр по имени
        if ($name) {
            $query->where(function ($q) use ($name, $slug) {
                $q
                    ->where('name', 'like', "%$name%")
                    ->orWhere('slug', 'like', "%$slug%");
            });
        }

        // Ленивое постраничное получение
        $perPage = 12;
        return $query->paginate($perPage);
    }
}
