<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function show()
    {
        return Category::all();
    }

    public function create(Request $request, $name)
    {
        //  Валидация данных
        $validator = validator(['name' => $name], ['name' => 'required|string|max:255',]);

        //  Проверка
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $validator = $validator->validated();

        //  Генерация slug
        $slug = Str::slug($validator['name']);

        //  Создание или получение категории
        $category = Category::firstOrCreate(
            ['slug' => $slug],
            ['name' => $validator['name']]
        );

        //  Ответ (или редирект)
        return response()->json([
            'message' => 'Категория успешно добавлена или уже существует',
            'category' => $category
        ]);
    }
}
