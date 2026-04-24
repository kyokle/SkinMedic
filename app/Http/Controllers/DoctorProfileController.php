<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorProfileController extends Controller
{
    public function show()
    {
        $userId = session('user_id');

        // Auto-create doctor record if missing
        $exists = DB::table('doctor')->where('user_id', $userId)->exists();
        if (!$exists) {
            DB::table('doctor')->insert(['user_id' => $userId]);
        }

        $doctor = DB::selectOne("
            SELECT u.firstName, u.lastName, u.email, u.gender, u.phone_no, u.address,
                   d.doctor_id, d.license_number, d.specialization,
                   d.years_of_experience, d.consultation_fee,
                   d.availability_schedule, d.profile_picture
            FROM users u
            LEFT JOIN doctor d ON u.user_id = d.user_id
            WHERE u.user_id = ?
        ", [$userId]);

        $doctor = $doctor ? (array) $doctor : [
            'firstName' => '', 'lastName' => '', 'email' => '', 'gender' => '',
            'phone_no' => '', 'address' => '', 'doctor_id' => '',
            'license_number' => '', 'specialization' => '',
            'years_of_experience' => '', 'consultation_fee' => '',
            'availability_schedule' => '', 'profile_picture' => '',
        ];

        $profilePic = !empty($doctor['profile_picture'])
            ? 'uploads/' . $doctor['profile_picture']
            : 'uploads/default.png';
        $sidebarData = (new SidebarDoctorController)->getSidebarData();
        
        return view('doctor_profile', array_merge(compact('doctor', 'profilePic'), $sidebarData));
    }

    public function uploadPic(Request $request)
    {
        $request->validate([
            'profile_pic' => 'required|image|mimes:jpg,jpeg,png',
        ]);

        $userId   = session('user_id');
        $filename = time() . '.' . $request->file('profile_pic')->getClientOriginalExtension();
        $request->file('profile_pic')->move(public_path('uploads'), $filename);

        DB::table('doctor')
            ->where('user_id', $userId)
            ->update(['profile_picture' => $filename]);

        return redirect()->route('doctor.profile');
    }

    public function updatePersonal(Request $request)
    {
        $request->validate([
            'firstName' => 'required|string|max:100',
            'lastName'  => 'required|string|max:100',
            'gender'    => 'required|in:male,female,others',
            'phone_no'  => 'nullable|string|max:20',
            'address'   => 'nullable|string|max:255',
        ]);

        DB::table('users')
            ->where('user_id', session('user_id'))
            ->update([
                'firstName' => $request->firstName,
                'lastName'  => $request->lastName,
                'gender'    => $request->gender,
                'phone_no'  => $request->phone_no,
                'address'   => $request->address,
            ]);

        return redirect()->route('doctor.profile');
    }

    public function updateDoctor(Request $request)
    {
        $request->validate([
            'license_number'       => 'nullable|string|max:100',
            'specialization'       => 'nullable|string|max:100',
            'years_of_experience'  => 'nullable|integer|min:0',
            'consultation_fee'     => 'nullable|numeric|min:0',
            'availability_schedule'=> 'nullable|string|max:255',
        ]);

        DB::table('doctor')
            ->where('user_id', session('user_id'))
            ->update([
                'license_number'        => $request->license_number,
                'specialization'        => $request->specialization,
                'years_of_experience'   => $request->years_of_experience,
                'consultation_fee'      => $request->consultation_fee,
                'availability_schedule' => $request->availability_schedule,
            ]);
        
        return redirect()->route('doctor.profile');
    }
}