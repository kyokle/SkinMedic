<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\VerifyEmailMail;
use App\Mail\ResetOtpMail;

class IndexController extends Controller
{
    /**
     * Show the home/landing page.
     * Redirects authenticated users to their respective dashboards.
     */
    public function index(Request $request)
    {
        // Redirect already-logged-in users to their role page
        if (Session::has('user_id') && Session::has('role')) {
            switch (Session::get('role')) {
                case 'doctor':
                    return redirect()->route('doctor.home');
                case 'staff':
                    return redirect()->route('staff.home');
                case 'patient':
                    return redirect()->route('patient.home');
            }
        }

        // Fetch latest 6 services
        $services = DB::table('services')
            ->select('service_id', 'name', 'description', 'price', 'image')
            ->orderBy('service_id', 'desc')
            ->limit(6)
            ->get();

        // Fetch latest 6 available products
        $products = DB::table('products')
            ->where('status', 'available')
            ->where('quantity', '>', 0)
            ->orderBy('date_added', 'desc')
            ->limit(6)
            ->get();
        
        // Fetch reviews
        $reviews = DB::table('reviews')
            ->join('users', 'reviews.user_id', '=', 'users.user_id')
            ->leftJoin('services', 'reviews.service_id', '=', 'services.service_id')
            ->select('reviews.*',
                DB::raw("CONCAT(users.firstname, ' ', users.lastname) as patient_name"),
                'services.name as service_name')
                ->orderBy('reviews.created_at', 'desc')
                ->limit(6)
                ->get();

        // Determine which popup to show based on query params
        $showLoginPopup = $request->has('login');   // ?login=true
        $showAdminPopup = $request->has('admin');   // ?admin=true

        return view('index', compact(
            'services',
            'products',
            'reviews',
            'showLoginPopup',
            'showAdminPopup'
        ));
    }

    /**
     * Handle client login via AJAX.
     */
    public function login(Request $request)
{
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required',
    ]);

    $user = DB::table('users')->where('email', $request->email)->first();

    if (!$user || !password_verify($request->password, $user->password_hash)) {
        return response()->json(['success' => false, 'error' => 'Invalid email or password.']);
    }

    // Block login if email not verified
    if (is_null($user->email_verified_at)) {
        return response()->json(['success' => false, 'error' => 'Please verify your email before logging in.']);
    }

    Session::put('user_id', $user->user_id);
    Session::put('email', $user->email);
    Session::put('role',  $user->role);
    Auth::loginUsingId($user->user_id);

    $redirectMap = [
        'doctor'  => route('doctor.home'),
        'staff'   => route('staff.home'),
        'patient' => route('patient.home'),
    ];

    return response()->json([
        'success'  => true,
        'redirect' => $redirectMap[$user->role] ?? '/',
    ]);
}

    /**
     * Handle admin login via AJAX.
     */
    public function adminLogin(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $admin = DB::table('users')
            ->where('email', $request->email)
            ->whereIn('role', ['doctor', 'staff', 'admin'])
            ->first();

        if (!$admin || !password_verify($request->password, $admin->password_hash)) {
            return response()->json(['success' => false, 'error' => 'Invalid admin credentials.']);
        }

        Session::put('user_id', $admin->user_id);
        Session::put('email', $admin->email);
        Session::put('role',  $admin->role);
        Auth::loginUsingId($admin->user_id);

        $redirectMap = [
            'doctor' => route('doctor.home'),
            'staff'  => route('staff.home'),
            'admin'  => route('admin.home'),
        ];

        return response()->json([
            'success'  => true,
            'redirect' => $redirectMap[$admin->role] ?? '/',
        ]);
    }

    /**
     * Handle patient signup via AJAX.
     */
    public function signup(Request $request)
{
    $request->validate([
        'firstname' => 'required|string|max:100',
        'lastname'  => 'required|string|max:100',
        'email'     => 'required|email|unique:users,email',
        'gender'    => 'required|in:male,female,others',
        'password'  => 'required|min:8',
    ]);

    // Generate verification token
    $token = Str::random(64);

    $userId = DB::table('users')->insertGetId([
        'firstname'      => $request->firstname,
        'lastname'       => $request->lastname,
        'email'          => $request->email,
        'gender'         => $request->gender,
        'phone_no'       => $request->phone_no,
        'address'        => $request->address,
        'password_hash'  => password_hash($request->password, PASSWORD_DEFAULT),
        'role'           => 'patient',
        'email_verified_at' => null, // not verified yet
        'verify_token'   => $token,  // store token
    ]);

    // Send verification email
    $verifyUrl = route('verify.email', ['token' => $token]);
    Mail::to($request->email)->send(new VerifyEmailMail($verifyUrl));

    // Don't log them in yet — ask them to verify first
    return response()->json([
        'success' => true,
        'message' => 'Account created! Please check your email to verify your account.',
        'redirect' => null, // stay on page
    ]);
}
    /**
     * Step 1 of Forgot Password: send OTP to email.
     */
    public function forgotPassword(Request $request)
{
    $request->validate(['fp_email' => 'required|email']);

    $user = DB::table('users')->where('email', $request->fp_email)->first();

    if (!$user) {
        return response()->json(['success' => false, 'error' => 'Email not found.']);
    }

    $otp = rand(100000, 999999);

    Session::put('reset_otp',   $otp);
    Session::put('reset_email', $request->fp_email);
    Session::put('otp_expires', now()->addMinutes(10)); // expiry

    // ✅ Actually send the OTP now
    Mail::to($request->fp_email)->send(new ResetOtpMail($otp));

    return response()->json([
        'success' => true,
        'message' => 'OTP sent! Check your inbox.',
    ]);
}

    /**
     * Step 2 of Forgot Password: verify OTP.
     */
    public function verifyResetOtp(Request $request)
{
    $request->validate(['otp' => 'required|digits:6']);

    // Check expiry
    if (now()->gt(Session::get('otp_expires'))) {
        return response()->json(['success' => false, 'error' => 'OTP has expired. Please request a new one.']);
    }

    if ((string) Session::get('reset_otp') !== (string) $request->otp) {
        return response()->json(['success' => false, 'error' => 'Invalid OTP.']);
    }

    return response()->json(['success' => true]);
}

    /**
     * Step 3 of Forgot Password: reset password.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'password'         => 'required|min:8|confirmed',
            'confirm_password' => 'required',
        ]);

        $email = Session::get('reset_email');

        if (!$email) {
            return response()->json(['success' => false, 'error' => 'Session expired. Please try again.']);
        }

        DB::table('users')
            ->where('email', $email)
            ->update(['password_hash' => password_hash($request->password, PASSWORD_DEFAULT)]);

        Session::forget(['reset_otp', 'reset_email']);

        return response()->json([
            'success' => true,
            'message' => '✓ Password reset! You can now log in.',
        ]);
    }
}