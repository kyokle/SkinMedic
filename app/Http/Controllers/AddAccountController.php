<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmailMail;

class AddAccountController extends Controller
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

        $otp = rand(100000, 999999);

        DB::table('users')->insert([
            'email'             => $request->email,
            'firstName'         => $request->firstname,
            'lastName'          => $request->lastname,
            'password_hash'     => Hash::make($request->password),
            'gender'            => $request->gender   ?? 'Not specified',
            'address'           => $request->address  ?? 'Not provided',
            'phone_no'          => $request->phone_no ?? 'Not provided',
            'role'              => $request->role      ?? 'patient',
            'email_verified_at' => null,
            'email_otp'         => $otp,
            'otp_expires_at'    => now()->addMinutes(10),
        ]);

        try {
            Mail::to($request->email)->send(new VerifyEmailMail((string)$otp, $request->firstname));
            $msg = 'Account created! A 6-digit verification code has been sent to ' . $request->email . '. They must verify before logging in.';
        } catch (\Exception $e) {
            \Log::error('Verification email failed for ' . $request->email . ': ' . $e->getMessage());
            $msg = 'Account created, but the verification email failed to send. Temporary OTP: ' . $otp;
        }

        return redirect()->route('admin.add-account')->with('success', $msg);
    }
}