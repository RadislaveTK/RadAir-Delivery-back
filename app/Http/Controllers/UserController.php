<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{
    //
    public function register(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => '',
            'phone' => 'required|string',
            'password' => 'required|string|min:6|',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => "test@gmail.com",
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'position' => "Петропавловск",
        ]);

        $user->assignRole('user');

        // Авторизуем сразу после регистрации (опционально)
        // auth()->login($user);
        Auth::login($user);

        return response()->json([
            'message' => 'Пользователь зарегистрирован',
            'user' => $user,
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Неверные данные'], 401);
        }

        return response()->json(Auth::user());
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        return response()->json(['message' => 'Вы вышли']);
    }
}
