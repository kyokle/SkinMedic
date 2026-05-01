<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class PatientProfileController extends Controller
{
    use SidebarDataController;
    public function index()
    {
        $userId = (int) Session::get('user_id');

        $exists = DB::table('patient')->where('user_id', $userId)->exists();
        if (!$exists) {
            DB::table('patient')->insert(['user_id' => $userId]);
        }

        $data = DB::table('users as u')
            ->leftJoin('patient as p', 'u.user_id', '=', 'p.user_id')
            ->where('u.user_id', $userId)
            ->select(
                'u.user_id', 'u.firstName', 'u.lastName', 'u.email',
                'u.gender', 'u.address', 'u.phone_no',
                'p.patient_id', 'p.medical_history', 'p.allergies',
                'p.emergency_contact_name', 'p.emergency_contact_phone',
                'p.profile_picture'
            )
            ->first();

        $profilePic = (!empty($data->profile_picture) && $data->profile_picture !== 'default.png')
            ? $data->profile_picture
            : 'default.png';

        return view('patient_profile', array_merge(
            $this->sidebarData(),
            compact('data', 'profilePic')
        ));
    }

    public function uploadPic(Request $request)
    {
        $userId = (int) Session::get('user_id');

        $request->validate([
            'profile_pic' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $file = $request->file('profile_pic');
        $path = Storage::disk('cloudinary')->putFile('patient_profiles', $file);
        $url  = Storage::disk('cloudinary')->url($path);

        DB::table('patient')
            ->where('user_id', $userId)
            ->update(['profile_picture' => $url]); // ← also fixed $imgUrl bug → $url

        return redirect()->route('patient.profile')->with('upload_success', true);
    }

    public function updatePersonal(Request $request)
    {
        $userId = (int) Session::get('user_id');

        $request->validate([
            'firstName' => 'required|string|max:100',
            'lastName'  => 'required|string|max:100',
            'gender'    => 'required|in:male,female,others',
            'address'   => 'nullable|string|max:255',
            'phone_no'  => 'nullable|string|max:20',
        ]);

        DB::table('users')
            ->where('user_id', $userId)
            ->update([
                'firstName' => $request->firstName,
                'lastName'  => $request->lastName,
                'gender'    => $request->gender,
                'address'   => $request->address,
                'phone_no'  => $request->phone_no,
            ]);

        return redirect()->route('patient.profile');
    }

    public function updateMedical(Request $request)
    {
        $userId = (int) Session::get('user_id');

        $request->validate([
            'medical_history'         => 'nullable|string',
            'allergies'               => 'nullable|string',
            'emergency_contact_name'  => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
        ]);

        DB::table('patient')
            ->where('user_id', $userId)
            ->update([
                'medical_history'         => $request->medical_history,
                'allergies'               => $request->allergies,
                'emergency_contact_name'  => $request->emergency_contact_name,
                'emergency_contact_phone' => $request->emergency_contact_phone,
            ]);

        return redirect()->route('patient.profile');
    }
}