<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //
    public function register(Request $request) {

        $validator = $request->validate([
            'name' => "require"
        ]);

        $user = new User();
        $user->name = "";
    }
}
