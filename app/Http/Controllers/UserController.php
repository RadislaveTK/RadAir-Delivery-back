<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    //
    public function register(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'name' => 'required|string|max:255',
        //     'phone' => 'required|string',
        //     'password' => 'required|string|min:6|',
        // ]);

        // if ($validator->fails()) {
        //     return response()->json(['errors' => $validator->errors()], 422);
        // }

        // $user = User::create([
        //     'name' => $request->name,
        //     'password' => Hash::make($request->password),
        //     'phone' => $request->phone,
        //     'position' => "Петропавловск",
        // ]);

        // $user->assignRole('user');

        // // Авторизуем сразу после регистрации (опционально)
        // // auth()->login($user);
        // Auth::login($user);

        // return response()->json([
        //     'message' => 'Пользователь зарегистрирован',
        //     'user' => $user,
        // ]);

        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'phone' => 'required|string',
                'password' => 'required|string|min:6|',
            ]);

            if (User::where('phone', $request->phone)->first()) {
                return response()->json([
                    'response_code' => 500,
                    'status' => 'error',
                    'message' => 'Пользователь уже зарегистрирован',
                ]);
            }

            $user = User::create([
                'name' => $request->name,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'position' => 'Петропавловск',
            ]);
            $user->assignRole('user');

            return response()->json([
                'response_code' => 201,
                'status' => 'success',
                'message' => 'Пользователь зарегистрирован',
                'user' => $user,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'response_code' => 422,
                'status' => 'error',
                'message' => 'Валидация провалена',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Ошибка регистрации: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Регистрация провалена',
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->only('phone', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'response_code' => 401,
                'status' => 'error',
                'message' => 'Неверные данные'
            ], 401);
        }
        $token = Auth::user()->createToken('authToken')->plainTextToken;

        return response()->json([
            'response_code' => 200,
            'status' => 'success',
            'message' => 'Авторизация пройдена',
            'user' => Auth::user(),
            'token' => $token,
        ], 200);
    }

    public function logout(Request $request)
    {
        // Auth::guard('web')->logout();
        // Auth::logout();
        // $request->session()->regenerate();
        // Auth::user()->tokens()->delete();
        // $request->user()->currentAccessToken()->delete();

        Auth::guard('web')->logout();

        // Инвалидируем текущую сессию
        $request->session()->invalidate();

        // Генерируем новый CSRF токен
        $request->session()->regenerateToken();

        return response()->json([
            'response_code' => 200,
            'status' => 'success',
            'message' => 'Вы вышли',
        ], 200);
    }
}
