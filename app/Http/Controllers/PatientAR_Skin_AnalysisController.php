<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class PatientAR_Skin_AnalysisController extends Controller
{
    use SidebarDataController;

    private string $pythonApiUrl;

    public function __construct()
    {
        $this->pythonApiUrl = env('SKIN_API_URL', 'http://127.0.0.1:5001');
    }

    public function index()
    {
        if (!Session::has('user_id')) {
            return redirect('/')->with('error', 'Please login first.');
        }

        return view('patient_AR_Skin_Analysis', $this->sidebarData());
    }

    public function analyze(Request $request)
    {
        if (!Session::has('user_id')) {
            return redirect('/')->with('error', 'Please login first.');
        }

        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $file    = $request->file('photo');
        $base64  = base64_encode(file_get_contents($file->getRealPath()));
        $dataUri = 'data:' . $file->getMimeType() . ';base64,' . $base64;

        try {
            $response = Http::timeout(30)
                ->post("{$this->pythonApiUrl}/analyze", [
                    'image' => $dataUri,
                ]);

            if ($response->failed()) {
                throw new \Exception('Skin analysis service returned an error: ' . $response->body());
            }

            $result = $response->json();

        } catch (\Exception $e) {
            return back()->with('error', 'Analysis failed: ' . $e->getMessage());
        }

        return redirect()->route('patient.skin-analysis.result')
            ->with('result',   $result)
            ->with('photoUrl', $dataUri);
    }

    public function result()
{
    if (!Session::has('user_id')) {
        return redirect('/')->with('error', 'Please login first.');
    }

    $result   = session('result');
    $photoUrl = session('photoUrl');

    if (!$result) {
        return redirect()->route('patient.skin-analysis')
                         ->with('error', 'No result found. Please try again.');
    }

    return view('patient_skin_analysis_result', array_merge(  // ← changed view name
        $this->sidebarData(),
        compact('result', 'photoUrl')
    ));
}
}