<?php

namespace App\Http\Controllers;

use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => true,
            'data' => User::select('id', 'name', 'email', 'role', 'created_at')->get()
        ]);
    }
}