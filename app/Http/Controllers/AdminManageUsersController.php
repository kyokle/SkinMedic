<?php

namespace App\Http\Controllers;

use App\Http\Controllers\SidebarDataController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminManageUsersController extends Controller
{
    use SidebarDataController;

    private function checkAuth()
    {
        if (session('role') !== 'admin') abort(redirect('index?login=true'));
    }

    public function index(Request $request)
    {
        $this->checkAuth();
        $tab = $request->get('tab', 'doctor');

        $users = $tab === 'patient'
            ? DB::table('users as u')
                ->leftJoinSub(
                    DB::table('appointments')
                        ->select(
                            'user_id',
                            DB::raw('COUNT(DISTINCT appointment_id) AS total_visits'),
                            DB::raw('MAX(appointment_date) AS last_visit')
                        )
                        ->where('status', 'completed')
                        ->groupBy('user_id'),
                    'agg',
                    'agg.user_id', '=', 'u.user_id'
                )
                ->select('u.*', 'agg.total_visits', 'agg.last_visit')
                ->where('u.role', 'patient')
                ->orderBy('u.lastName')
                ->get()
            : DB::table('users')->where('role', $tab)->orderBy('lastName')->get();

        $counts = [
            'doctor'  => DB::table('users')->where('role', 'doctor')->count(),
            'staff'   => DB::table('users')->where('role', 'staff')->count(),
            'patient' => DB::table('users')->where('role', 'patient')->count(),
        ];

        return view('admin_manage_users', array_merge(
            $this->sidebarData(),
            compact('users', 'counts', 'tab')
        ));
    }

    public function update(Request $request)
    {
        $this->checkAuth();
        $uid = (int) $request->input('user_id');
        $tab = $request->input('tab', 'doctor');

        DB::table('users')->where('user_id', $uid)->update([
            'firstName' => $request->input('firstName'),
            'lastName'  => $request->input('lastName'),
            'email'     => $request->input('email'),
            'phone_no'  => $request->input('phone_no'),
            'gender'    => $request->input('gender'),
            'role'      => $request->input('role'),
        ]);

        return redirect()->route('admin.manage-users', ['tab' => $tab]);
    }


    public function delete(Request $request)
    {
        $this->checkAuth();
        $uid = (int) $request->input('user_id');
        $tab = $request->input('tab', 'doctor');

        DB::table('users')->where('user_id', $uid)->delete();

        return redirect()->route('admin.manage-users', ['tab' => $tab]);
    }

    public function setPreferredTime(Request $request)
    {
        $this->checkAuth();
        $uid  = (int) $request->input('user_id');
        $time = $request->input('preferred_time');

        DB::table('users')->where('user_id', $uid)->update([
            'preferred_time' => $time,
            'is_regular'     => 1,
        ]);

        return redirect()->route('admin.manage-users', ['tab' => 'patient']);
    }

    public function removeRegular(Request $request)
    {
        $this->checkAuth();
        $uid = (int) $request->input('user_id');

        DB::table('users')->where('user_id', $uid)->update([
            'preferred_time' => null,
            'is_regular'     => 0,
        ]);

        return redirect()->route('admin.manage-users', ['tab' => 'patient']);
    }
}