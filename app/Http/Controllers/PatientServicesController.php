<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class PatientServicesController extends Controller
{
    use SidebarDataController;
    public function index()
    {
        $services = DB::table('services')
            ->where('status', 'available')
            ->get();

        return view('patient_services', array_merge(
            $this->sidebarData(),
            compact('services')
        ));
    }
}