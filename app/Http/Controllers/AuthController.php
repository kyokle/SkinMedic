<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function logout()
    {
        Session::flush();
        return redirect()->route('index');
    }
}