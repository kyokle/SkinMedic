<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AddAccountController extends Controller  // ← was AccountController
{
    public function index()
    {
        return view('add_account', $this->sidebarData());
    }

    public function store(Request $request)
    {
        $request->validate([
            'email'            => 'required|email|unique:users,email',
            'password'         => 'required|same:confirm_password',
            'confirm_password' => 'required',
            'firstname'        => 'required|string',
            'lastname'         => 'required|string',
        ]);

        DB::table('users')->insert([
            'email'         => $request->email,
            'firstName'     => $request->firstname,
            'lastName'      => $request->lastname,
            'password_hash' => Hash::make($request->password),
            'gender'        => $request->gender   ?? 'Not specified',
            'address'       => $request->address  ?? 'Not provided',
            'phone_no'      => $request->phone_no ?? 'Not provided',
            'role'          => $request->role      ?? 'patient',
        ]);

        return redirect()->route('admin.add-account')->with('success', 'Account created successfully.');
    }
}