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

        $file = $request->file('photo');

        try {
            // Send as multipart/form-data — matches Python API's UploadFile parameter
            $response = Http::timeout(30)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post("{$this->pythonApiUrl}/analyze");

            if ($response->failed()) {
                throw new \Exception('Skin analysis service returned an error: ' . $response->body());
            }

            $result = $response->json();

            // ── Face detection guard ──────────────────────────────────────────
            // skin_api.py already runs MediaPipe face detection inside /analyze
            // and returns { "success": false, "error": "..." } when no face found.
            if (isset($result['success']) && $result['success'] === false) {
                return back()->with(
                    'error',
                    $result['error'] ?? 'No face detected in your photo. Please upload a clear, well-lit photo of your face and try again.'
                );
            }
            // ─────────────────────────────────────────────────────────────────

        } catch (\Exception $e) {
            return back()->with('error', 'Analysis failed: ' . $e->getMessage());
        }

        // Store photo as base64 data URI for the result view
        $base64  = base64_encode(file_get_contents($file->getRealPath()));
        $dataUri = 'data:' . $file->getMimeType() . ';base64,' . $base64;

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

        return view('patient_Skin_Analysis_result', array_merge(
            $this->sidebarData(),
            compact('result', 'photoUrl')
        ));
    }
}