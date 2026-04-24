<?php

namespace App\Http\Controllers;

use App\Http\Controllers\SidebarDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class AdminAddAccountController extends Controller
{
    use SidebarDataController;

    private const ALLOWED_ROLES = ['admin', 'staff'];

    private const PASSWORD_RULES = [
        ['/[A-Z]/', 'Password needs an uppercase letter.'],
        ['/[a-z]/', 'Password needs a lowercase letter.'],
        ['/[0-9]/', 'Password needs a number.'],
        ['/[\W_]/', 'Password needs a special character.'],
    ];

    // ── Auth guard ────────────────────────────────────────────
    private function authorise()
    {
        if (!in_array(Session::get('role'), self::ALLOWED_ROLES)) {
            return redirect()->route('index');
        }
        return null;
    }

    // ── GET /admin/add-account ────────────────────────────────
    public function index()
    {
        if ($redirect = $this->authorise()) return $redirect;

        return view('admin_add-account', $this->sidebarData());
    }

    // ── POST /admin/add-account ───────────────────────────────
    public function store(Request $request)
    {
        if ($redirect = $this->authorise()) return $redirect;

        $data   = $this->collectInput($request);
        $errors = $this->validate($data);

        if (empty($errors) && $this->emailExists($data['email'])) {
            $errors[] = 'This email is already registered.';
        }

        if (!empty($errors)) {
            return back()
                ->with('errors', $errors)
                ->withInput($request->except(['password', 'confirm_password']));
        }

        $this->createUser($data);

        $fullName = htmlspecialchars($data['firstname'] . ' ' . $data['lastname']);

        return redirect()
            ->route('admin.add-account')
            ->with('success', "Account for <strong>{$fullName}</strong> created!");
    }

    // ── Input collection ──────────────────────────────────────
    private function collectInput(Request $request): array
    {
        return [
            'email'            => trim($request->input('email', '')),
            'password'         => $request->input('password', ''),
            'confirm_password' => $request->input('confirm_password', ''),
            'firstname'        => trim($request->input('firstname', '')),
            'lastname'         => trim($request->input('lastname', '')),
            'gender'           => $request->input('gender', 'Not specified'),
            'address'          => trim($request->input('address', '')),
            'phone_no'         => trim($request->input('phone_no', '')),
            'role'             => $request->input('role', ''),
        ];
    }

    // ── Validation ────────────────────────────────────────────
    private function validate(array $data): array
    {
        $errors = [];

        foreach (['email', 'password', 'firstname', 'lastname', 'role'] as $field) {
            if ($data[$field] === '') {
                $errors[] = 'All required fields must be filled.';
                break;
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format.';
        }

        if ($data['password'] !== $data['confirm_password']) {
            $errors[] = 'Passwords do not match.';
        }

        if (strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        foreach (self::PASSWORD_RULES as [$pattern, $message]) {
            if (!preg_match($pattern, $data['password'])) {
                $errors[] = $message;
            }
        }

        return $errors;
    }

    // ── Database helpers ──────────────────────────────────────
    private function emailExists(string $email): bool
    {
        return DB::table('users')->where('email', $email)->exists();
    }

    private function createUser(array $data): void
    {
        DB::table('users')->insert([
            'email'         => $data['email'],
            'firstName'     => $data['firstname'],
            'lastName'      => $data['lastname'],
            'password_hash' => Hash::make($data['password']),
            'gender'        => $data['gender'],
            'address'       => $data['address'],
            'phone_no'      => $data['phone_no'],
            'role'          => $data['role'],
        ]);
    }
}