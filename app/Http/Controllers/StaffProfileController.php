<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\SidebarDataController;

class StaffProfileController extends Controller
{
    use SidebarDataController;
    public function index()
    {
        $userId = session('user_id');

        $existing = DB::selectOne("SELECT * FROM staff WHERE user_id = ?", [$userId]);
        if (!$existing) {
            DB::insert("INSERT INTO staff (user_id) VALUES (?)", [$userId]);
        }

        $staff = DB::selectOne("
            SELECT u.user_id, u.firstName, u.lastName, u.email, u.gender, u.address, u.phone_no,
                   s.staff_id, s.position, s.department, s.hire_date, s.shift_schedule, s.profile_picture
            FROM users u
            LEFT JOIN staff s ON u.user_id = s.user_id
            WHERE u.user_id = ?
        ", [$userId]);

        if (!$staff) {
            $staff = (object)[
                'user_id' => '', 'firstName' => '', 'lastName' => '', 'email' => '',
                'gender' => '', 'address' => '', 'phone_no' => '', 'staff_id' => '',
                'position' => '', 'department' => '', 'hire_date' => '',
                'shift_schedule' => '', 'profile_picture' => '',
            ];
        }

        $profilePic = !empty($staff->profile_picture)
            ? $staff->profile_picture
            : 'uploads/default.png';

        return view('staff_profile', array_merge(
            $this->sidebarData(),
            compact('staff', 'profilePic')
        ));
    }

    public function updatePersonal(Request $request)
    {
        DB::update("
            UPDATE users SET firstName=?, lastName=?, gender=?, address=?, phone_no=?
            WHERE user_id=?
        ", [
            $request->input('firstName'),
            $request->input('lastName'),
            $request->input('gender'),
            $request->input('address'),
            $request->input('phone_no'),
            session('user_id'),
        ]);

        return redirect()->route('staff.profile');
    }

    public function updateEmployment(Request $request)
    {
        DB::update("
            UPDATE staff SET position=?, department=?, shift_schedule=?, hire_date=?
            WHERE user_id=?
        ", [
            $request->input('position'),
            $request->input('department'),
            $request->input('shift_schedule'),
            $request->input('hire_date'),
            session('user_id'),
        ]);

        return redirect()->route('staff.profile');
    }

    public function uploadPic(Request $request)
{
    $userId = session('user_id');

    $request->validate([
        'profile_pic' => 'required|image|mimes:jpg,jpeg,png|max:5120',
    ]);

    $path = Storage::disk('cloudinary')->putFile('staff_profiles', $request->file('profile_pic'));
    $url  = Storage::disk('cloudinary')->url($path);

    DB::table('staff')
        ->where('user_id', $userId)
        ->update(['profile_picture' => $url]);

    return redirect()->route('staff.profile')->with('upload_success', true);
}
}