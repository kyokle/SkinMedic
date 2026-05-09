<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class SkinAnalysisController extends Controller
{
    private string $pythonApiUrl;

    public function __construct()
    {
        $this->pythonApiUrl = env('SKIN_API_URL', 'http://127.0.0.1:8002');
    }

    public function index()
    {
        return view('skin-analysis.index');
    }

    public function analyze(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $file = $request->file('photo');

        try {
            // Send as multipart/form-data — matches Python API's UploadFile parameter
            $base64Image = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));

$response = Http::timeout(30)
    ->post("{$this->pythonApiUrl}/analyze", [
        'image' => $base64Image,
    ]);

            if ($response->failed()) {
                throw new \Exception('Skin analysis service returned an error: ' . $response->body());
            }

            $result = $response->json();

            // ── Face detection guard ──────────────────────────────────────────
            // skin_api.py already runs MediaPipe face detection inside /analyze
            // and returns { "success": false, "error": "..." } when no face found.
            if (isset($result['success']) && $result['success'] === false) {
    return back()->with('error', $result['error'] ?? 'No face detected. Please try again.');
}

if (isset($result['error'])) {
    return back()->with('error', $result['error']);
}
            // ─────────────────────────────────────────────────────────────────

        } catch (\Exception $e) {
            return back()->with('error', 'Analysis failed: ' . $e->getMessage());
        }

        $label          = $result['label']             ?? 'Unknown';
        $confidence     = $result['confidence']        ?? 0;
        $severityScore  = $result['severity_score']    ?? 0;
        $treatment      = $result['treatment']         ?? [];
        $detections     = $result['detections']        ?? [];
        $condSummary    = $result['condition_summary'] ?? [];
        $allPredictions = array_map(fn($p) => [
            'condition'  => $p['condition']  ?? '',
            'confidence' => $p['confidence'] ?? 0,
        ], $result['all_predictions'] ?? []);

        $transformed = [
            'label'             => $label,
            'confidence'        => $confidence,
            'severity_score'    => $severityScore,
            'condition_summary' => $condSummary,
            'all_predictions'   => $allPredictions,
            'detections'        => $detections,
            'image_size'        => $result['image_size'] ?? ['width' => 0, 'height' => 0],
            'treatment' => [
                'headline'    => $treatment['headline']    ?? 'Your Skin Analysis',
                'description' => $treatment['description'] ?? '',
                'recommended' => $treatment['recommended'] ?? [],
                'urgency'     => $treatment['urgency']     ?? 'low',
            ],
        ];

        // Store photo as base64 data URI for the result view
        $base64  = base64_encode(file_get_contents($file->getRealPath()));
        $dataUri = 'data:' . $file->getMimeType() . ';base64,' . $base64;

        return redirect()->route('skin-analysis.result')
            ->with('result',   $transformed)
            ->with('photoUrl', $dataUri);
    }

    public function result()
    {
        $result   = session('result');
        $photoUrl = session('photoUrl');

        if (!$result) {
            return redirect()->route('skin-analysis.index')
                             ->with('error', 'No result found. Please try again.');
        }

        return view('skin-analysis.result', compact('result', 'photoUrl'));
    }
}