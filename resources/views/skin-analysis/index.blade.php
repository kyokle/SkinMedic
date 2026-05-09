{{-- resources/views/skin-analysis/index.blade.php --}}
@extends('layouts.app')

@section('title', 'AR Skin Analysis')

@section('content')

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

{{-- MediaPipe JS (browser-based, no Python needed for live tracking) --}}
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/face_mesh.js" crossorigin="anonymous"></script>

{{-- face-api.js for upload face detection --}}
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

<div class="sa-page">

  <a href="{{ url('/') }}" class="sr-back-btn">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <polyline points="15 18 9 12 15 6"/>
    </svg>
    Back to Home
  </a>

  <div class="sa-header">
    
    <h1 class="sa-title">AR Skin Analysis</h1>
    <p class="sa-sub">Take or upload a photo to get an instant skin condition assessment and personalized treatment recommendations from our clinic.</p>
  </div>

  @if(session('error'))
    <div class="sa-alert">{{ session('error') }}</div>
  @endif
  @if($errors->any())
    <div class="sa-alert">{{ $errors->first() }}</div>
  @endif

  <div class="sa-layout">

    {{-- LEFT: camera / upload chooser --}}
    <div class="sa-left">

      {{-- Step 1: Choose source --}}
      <div id="chooser">
        <p class="sa-step-label">Choose how to provide your photo</p>
        <div class="sa-choices">
          <button type="button" class="sa-choice-btn" id="openCameraBtn">
            <span class="sa-choice-icon">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                <circle cx="12" cy="13" r="4"/>
              </svg>
            </span>
            <div>
              <span class="sa-choice-label">Use Camera</span>
              <span class="sa-choice-hint">Live AR face tracking</span>
            </div>
          </button>

          <button type="button" class="sa-choice-btn" id="openUploadBtn">
            <span class="sa-choice-icon">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
              </svg>
            </span>
            <div>
              <span class="sa-choice-label">Upload Photo</span>
              <span class="sa-choice-hint">JPG, PNG, WebP · max 8MB</span>
            </div>
          </button>
        </div>
      </div>

      {{-- Step 2: Live AR Camera with MediaPipe face mesh overlay --}}
      <div class="sa-camera-wrap hidden" id="cameraView">

        {{-- Status badge --}}
        <div class="sa-ar-status" id="arStatus">
          <span class="sa-ar-dot"></span>
          <span id="arStatusText">Initializing AR…</span>
        </div>

        {{-- Skin type live badge --}}
        <div class="sa-skin-badge hidden" id="skinBadge">
          <span id="skinBadgeText">Detecting…</span>
        </div>

        {{-- Video + canvas overlay --}}
        <div class="sa-ar-container">
          <video id="videoFeed" autoplay playsinline muted></video>
          <canvas id="arCanvas"></canvas>
        </div>

        {{-- Region labels overlay --}}
        <div class="sa-region-labels" id="regionLabels" style="display:none">
          <div class="sa-region-tag" id="tagForehead">Forehead</div>
          <div class="sa-region-tag" id="tagLeftCheek">L. Cheek</div>
          <div class="sa-region-tag" id="tagRightCheek">R. Cheek</div>
        </div>

        <div class="sa-cam-controls">
          <button type="button" class="sa-cam-cancel" id="cancelCameraBtn">Cancel</button>
          <button type="button" class="sa-cam-capture" id="captureBtn" disabled title="Capture photo">
            <span class="sa-shutter"></span>
          </button>
          <button type="button" class="sa-cam-flip" id="flipCameraBtn" title="Flip camera">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M1 4v6h6"/><path d="M23 20v-6h-6"/>
              <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15"/>
            </svg>
          </button>
        </div>
        <canvas id="snapCanvas" class="hidden"></canvas>
      </div>

      {{-- Step 3: Preview + submit --}}
      <form action="{{ route('skin-analysis.analyze') }}" method="POST"
            enctype="multipart/form-data" id="saForm" class="hidden">
        @csrf

        {{-- Upload face-check status message --}}
        <div class="sa-face-check hidden" id="faceCheckMsg">
          <span id="faceCheckText">Checking for a face in your photo…</span>
        </div>

        <div class="sa-preview-wrap">
          <img id="previewImg" src="" alt="Your photo"/>
          <button type="button" class="sa-retake-btn" id="retakeBtn">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="1 4 1 10 7 10"/>
              <path d="M3.51 15a9 9 0 1 0 .49-3"/>
            </svg>
            Retake / Change
          </button>
        </div>
        <input type="file" name="photo" id="photoFileInput" accept="image/*" class="hidden">
        <button type="submit" class="sa-submit-btn" id="submitBtn" disabled>
          <span class="sa-btn-text">Analyze My Skin</span>
          <span class="sa-btn-loader hidden">Analyzing…</span>
        </button>
      </form>

    </div>

    {{-- RIGHT: info panel --}}
    <div class="sa-right">
      <div class="sa-info-card">
        <h3 class="sa-info-title">How it works</h3>
        <ol class="sa-steps-list">
          <li><strong>Take or upload</strong> a clear close-up photo of your face</li>
          <li><strong>AR tracks</strong> your face regions in real time</li>
          <li><strong>AI assist to analyzes</strong> your skin condition in seconds</li>
          <li><strong>Get recommendations</strong> matched to our clinic's treatments</li>
        </ol>
      </div>

      <div class="sa-info-card sa-tips-card">
        <h3 class="sa-info-title">For best results</h3>
        <ul class="sa-tips-list">
          <li>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#80a833" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            Good natural lighting
          </li>
          <li>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#80a833" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            Face the camera directly
          </li>
          <li>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#80a833" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            No heavy makeup or filters
          </li>
          <li>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#80a833" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            Recent photo works best
          </li>
        </ul>
      </div>

      

      <p class="sa-disclaimer">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        Your photo is processed privately and never stored on our servers.
      </p>
    </div>

  </div>
</div>

<style>
.sa-page {
  font-family: 'Poppins', sans-serif;
  max-width: 900px;
  margin: 0 auto;
  padding: 40px 24px 60px;
}

.sr-back-btn {
  display: inline-flex; align-items: center; gap: 6px;
  background: none; border: 1.5px solid #c5d98a; color: #5a7220;
  border-radius: 20px; padding: 7px 18px; font-size: 13px;
  font-weight: 500; font-family: 'Poppins', sans-serif;
  cursor: pointer; text-decoration: none; margin-bottom: 20px;
  transition: background .15s;
}
.sr-back-btn:hover { background: #f0f7e0; }

.sa-header { margin-bottom: 32px; }
.sa-title {
  font-family: 'Playfair Display', serif;
  font-size: 42px; color: #5d595f; margin: 4px 0 10px; font-weight: 700;
}
.sa-sub { color: #5a5248; font-size: 15px; line-height: 1.6; max-width: 620px; margin: 0; }

.sa-alert {
  background: #fff0ee; border: 1px solid #f5c4b3; color: #993c1d;
  border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1.5rem; font-size: .9rem;
}

.sa-layout {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 32px;
  align-items: start;
}

.sa-step-label {
  font-size: 12px; font-weight: 600; text-transform: uppercase;
  letter-spacing: .08em; color: #9c8f83; margin: 0 0 14px;
}

.sa-choices { display: flex; flex-direction: column; gap: 12px; }

.sa-choice-btn {
  display: flex; align-items: center; gap: 16px;
  background: #faf9f8; border: 2px solid #e0d8cf;
  border-radius: 14px; padding: 18px 20px;
  cursor: pointer; transition: background .15s, border-color .15s;
  text-align: left; width: 100%; font-family: 'Poppins', sans-serif;
}
.sa-choice-btn:hover { background: #f0f7e0; border-color: #80a833; }
.sa-choice-icon { color: #80a833; flex-shrink: 0; }
.sa-choice-label { font-size: 15px; font-weight: 600; color: #2e2420; display: block; line-height: 1.3; }
.sa-choice-hint  { font-size: 12px; color: #9c8f83; display: block; }

/* ── AR Camera ── */
.sa-camera-wrap {
  border-radius: 16px; overflow: hidden;
  background: #0a0a0a; position: relative;
}

.sa-ar-container {
  position: relative; width: 100%;
}
.sa-ar-container video {
  width: 100%; display: block; max-height: 420px;
  object-fit: cover; transform: scaleX(-1);
}
.sa-ar-container canvas#arCanvas {
  position: absolute; top: 0; left: 0;
  width: 100%; height: 100%;
  pointer-events: none;
}

/* AR status badge */
.sa-ar-status {
  position: absolute; top: 12px; left: 12px; z-index: 10;
  background: rgba(0,0,0,0.65); backdrop-filter: blur(6px);
  border: 1px solid rgba(255,255,255,0.15);
  border-radius: 20px; padding: 5px 12px;
  display: flex; align-items: center; gap: 7px;
  font-size: 12px; color: #fff; font-family: 'Poppins', sans-serif;
}
.sa-ar-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: #f97316; animation: pulse 1.2s infinite;
}
.sa-ar-dot.active { background: #80a833; }
@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: .5; transform: scale(.8); }
}

/* Skin type live badge */
.sa-skin-badge {
  position: absolute; top: 12px; right: 12px; z-index: 10;
  background: rgba(128,168,51,0.9); backdrop-filter: blur(6px);
  border-radius: 20px; padding: 5px 14px;
  font-size: 12px; font-weight: 600; color: #fff;
  font-family: 'Poppins', sans-serif;
  transition: background .4s;
}

/* Region labels */
.sa-region-labels {
  position: absolute; top: 0; left: 0; right: 0; bottom: 60px;
  pointer-events: none; z-index: 8;
}
.sa-region-tag {
  position: absolute;
  background: rgba(0,0,0,0.55); backdrop-filter: blur(4px);
  color: #fff; font-size: 10px; font-weight: 600;
  padding: 3px 8px; border-radius: 10px;
  font-family: 'Poppins', sans-serif;
  border: 1px solid rgba(255,255,255,0.2);
  transform: translate(-50%, -100%);
  white-space: nowrap;
}

/* Camera controls */
.sa-cam-controls {
  position: relative; display: flex; align-items: center;
  justify-content: space-between; padding: 14px 20px 18px;
  background: linear-gradient(to top, rgba(0,0,0,.7), transparent);
  margin-top: -1px;
}
.sa-cam-cancel {
  background: none; border: none; color: #fff; font-family: 'Poppins', sans-serif;
  font-size: 14px; font-weight: 500; cursor: pointer; padding: 6px 10px; border-radius: 8px;
}
.sa-cam-cancel:hover { background: rgba(255,255,255,.15); }
.sa-cam-capture {
  width: 64px; height: 64px; border-radius: 50%;
  background: rgba(255,255,255,.15); border: 3px solid #fff;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: transform .1s, opacity .2s;
}
.sa-cam-capture:active { transform: scale(.92); }
.sa-cam-capture:disabled { opacity: 0.4; cursor: not-allowed; }
.sa-shutter { width: 48px; height: 48px; border-radius: 50%; background: #fff; display: block; }
.sa-cam-flip {
  background: none; border: none; color: #fff; cursor: pointer;
  padding: 8px; border-radius: 8px;
}
.sa-cam-flip:hover { background: rgba(255,255,255,.15); }

/* Preview */
.sa-preview-wrap { position: relative; margin-bottom: 16px; }
#previewImg { display: block; max-width: 100%; max-height: 360px; width: auto; height: auto; margin: 0 auto; object-fit: contain; border-radius: 14px; }
.sa-retake-btn {
  position: absolute; top: 10px; right: 10px;
  background: rgba(0,0,0,.55); color: #fff; border: none;
  border-radius: 20px; padding: 6px 14px; font-size: 12px;
  cursor: pointer; display: flex; align-items: center; gap: 6px;
  font-family: 'Poppins', sans-serif; transition: background .15s;
}
.sa-retake-btn:hover { background: rgba(0,0,0,.78); }

.sa-submit-btn {
  background: #80a833; color: #fff; border: none;
  padding: 13px 60px; border-radius: 30px;
  font-size: 15px; font-family: 'Poppins', sans-serif;
  font-weight: 600; cursor: pointer; transition: background .2s, opacity .2s; width: 100%;
}
.sa-submit-btn:hover:not(:disabled) { background: #6b9228; }
.sa-submit-btn:disabled { opacity: 0.45; cursor: not-allowed; }
.sa-btn-loader.hidden, .sa-btn-text.hidden { display: none; }

/* Face check status message */
.sa-face-check {
  display: flex; align-items: center; gap: 8px;
  border-radius: 10px; padding: 10px 14px; margin-bottom: 12px;
  font-size: 13px; font-weight: 500; font-family: 'Poppins', sans-serif;
  background: #faeeda; color: #854f0b; border: 1px solid #f0d0a0;
  transition: background .3s, color .3s;
}
.sa-face-check.success {
  background: #f0f7e0; color: #4a6e10; border-color: #c5d98a;
}
.sa-face-check.error {
  background: #fcebeb; color: #a32d2d; border-color: #f5c4b3;
}

/* Info cards */
.sa-info-card {
  background: #faf9f8; border-radius: 18px; padding: 22px 24px;
  margin-bottom: 16px;
  box-shadow: 0 6px 14px rgba(0,0,0,0.06), inset 0 0 0 2px #e0d8cf;
}
.sa-info-title {
  font-family: 'Playfair Display', serif;
  font-size: 18px; color: #2e2420; margin: 0 0 14px; font-weight: 700;
}
.sa-steps-list { margin: 0; padding-left: 18px; }
.sa-steps-list li { font-size: 13px; color: #5a5248; margin-bottom: 10px; line-height: 1.5; }
.sa-steps-list li strong { color: #80a833; }

.sa-tips-list { list-style: none; margin: 0; padding: 0; }
.sa-tips-list li { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #5a5248; margin-bottom: 10px; }

/* Legend */
.sa-legend { display: flex; flex-direction: column; gap: 8px; }
.sa-legend-item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #5a5248; }
.sa-legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }

.sa-disclaimer {
  display: flex; align-items: center; gap: 6px;
  font-size: 11.5px; color: #a09488; line-height: 1.5;
}

.hidden { display: none !important; }

@media (max-width: 820px) {
  .sa-layout { grid-template-columns: 1fr; }
  .sa-title { font-size: 28px; }
}
</style>

<script>
// ── DOM refs ──────────────────────────────────────────────────────────────────
const chooser        = document.getElementById('chooser');
const cameraView     = document.getElementById('cameraView');
const saForm         = document.getElementById('saForm');
const videoFeed      = document.getElementById('videoFeed');
const arCanvas       = document.getElementById('arCanvas');
const snapCanvas     = document.getElementById('snapCanvas');
const previewImg     = document.getElementById('previewImg');
const photoFileInput = document.getElementById('photoFileInput');
const submitBtn      = document.getElementById('submitBtn');
const arStatus       = document.getElementById('arStatus');
const arStatusDot    = arStatus.querySelector('.sa-ar-dot');
const arStatusText   = document.getElementById('arStatusText');
const skinBadge      = document.getElementById('skinBadge');
const skinBadgeText  = document.getElementById('skinBadgeText');
const legendCard     = document.getElementById('legendCard');
const captureBtn     = document.getElementById('captureBtn');
const faceCheckMsg   = document.getElementById('faceCheckMsg');
const faceCheckText  = document.getElementById('faceCheckText');

let stream        = null;
let facingMode    = 'user';
let capturedBlob  = null;
let faceMesh      = null;
let camera        = null;
let arRunning     = false;
let faceDetected  = false;

// ── Skin-type color map ───────────────────────────────────────────────────────
const SKIN_COLORS = {
  Normal:      { r:34,  g:197, b:94  },
  Dry:         { r:59,  g:130, b:246 },
  Oily:        { r:249, g:115, b:22  },
  Combination: { r:168, g:85,  b:247 },
  Unknown:     { r:200, g:200, b:200 },
};

const FOREHEAD_PTS    = [10, 67, 109, 338, 297, 332];
const LEFT_CHEEK_PTS  = [50, 101, 118, 117, 123];
const RIGHT_CHEEK_PTS = [280, 330, 347, 346, 352];

// ── Simple brightness/saturation analysis ─────────────────────────────────────
function analyzeRegionPixels(ctx, pts, w, h) {
  if (!pts || pts.length === 0) return 'Unknown';
  const xs = pts.map(p => p.x * w);
  const ys = pts.map(p => p.y * h);
  const x  = Math.max(0, Math.min(...xs) - 10);
  const y  = Math.max(0, Math.min(...ys) - 10);
  const x2 = Math.min(w, Math.max(...xs) + 10);
  const y2 = Math.min(h, Math.max(...ys) + 10);
  const rw = x2 - x, rh = y2 - y;
  if (rw <= 0 || rh <= 0) return 'Unknown';

  const data = ctx.getImageData(x, y, rw, rh).data;
  let totalR = 0, totalG = 0, totalB = 0, count = 0;
  for (let i = 0; i < data.length; i += 4) {
    totalR += data[i]; totalG += data[i+1]; totalB += data[i+2]; count++;
  }
  if (count === 0) return 'Unknown';
  const r = totalR/count, g = totalG/count, b = totalB/count;

  const max = Math.max(r,g,b)/255, min = Math.min(r,g,b)/255;
  const s = max === 0 ? 0 : (max - min) / max;
  const v = max;
  const l = 0.299*r + 0.587*g + 0.114*b;

  const sat255 = s * 255, val255 = v * 255;
  if (sat255 > 80 && val255 > 150) return 'Oily';
  if (sat255 < 50 && l > 140)      return 'Dry';
  if (sat255 > 60 && l > 120)      return 'Combination';
  return 'Normal';
}

// ── Draw filled polygon for a region ─────────────────────────────────────────
function drawRegion(ctx, pts, color, alpha = 0.35) {
  if (!pts || pts.length < 3) return;
  ctx.save();
  ctx.globalAlpha = alpha;
  ctx.fillStyle = `rgb(${color.r},${color.g},${color.b})`;
  ctx.beginPath();
  ctx.moveTo(pts[0].x, pts[0].y);
  for (let i = 1; i < pts.length; i++) ctx.lineTo(pts[i].x, pts[i].y);
  ctx.closePath();
  ctx.fill();
  ctx.globalAlpha = 1;

  ctx.strokeStyle = `rgba(${color.r},${color.g},${color.b},0.8)`;
  ctx.lineWidth = 1.5;
  ctx.beginPath();
  ctx.moveTo(pts[0].x, pts[0].y);
  for (let i = 1; i < pts.length; i++) ctx.lineTo(pts[i].x, pts[i].y);
  ctx.closePath();
  ctx.stroke();
  ctx.restore();
}

// ── Draw face mesh dots ───────────────────────────────────────────────────────
function drawMesh(ctx, landmarks) {
  ctx.save();
  ctx.fillStyle = 'rgba(128,168,51,0.55)';
  for (const lm of landmarks) {
    ctx.beginPath();
    ctx.arc(lm.x, lm.y, 1.2, 0, Math.PI * 2);
    ctx.fill();
  }
  ctx.restore();
}

// ── Main MediaPipe results callback ──────────────────────────────────────────
let resultHistory = [];
const HISTORY_SIZE = 15;

function onFaceMeshResults(results) {
  const w = arCanvas.width;
  const h = arCanvas.height;
  const ctx = arCanvas.getContext('2d');

  ctx.clearRect(0, 0, w, h);

  if (!results.multiFaceLandmarks || results.multiFaceLandmarks.length === 0) {
    arStatusText.textContent = 'No face detected';
    arStatusDot.classList.remove('active');
    skinBadge.classList.add('hidden');
    faceDetected = false;
    captureBtn.disabled = true;
    return;
  }

  arStatusText.textContent = 'Face tracked ✓';
  arStatusDot.classList.add('active');
  skinBadge.classList.remove('hidden');
  faceDetected = true;
  captureBtn.disabled = false;

  const lm = results.multiFaceLandmarks[0];

  const scaled = lm.map(p => ({ x: (1 - p.x) * w, y: p.y * h }));

  drawMesh(ctx, scaled);

  const offscreen = document.createElement('canvas');
  offscreen.width = w; offscreen.height = h;
  const offCtx = offscreen.getContext('2d');
  offCtx.drawImage(videoFeed, 0, 0, w, h);
  const rawScaled = lm.map(p => ({ x: p.x * w, y: p.y * h }));

  const fPts  = FOREHEAD_PTS.map(i    => rawScaled[i]);
  const lPts  = LEFT_CHEEK_PTS.map(i  => rawScaled[i]);
  const rPts  = RIGHT_CHEEK_PTS.map(i => rawScaled[i]);

  const fType = analyzeRegionPixels(offCtx, fPts, w, h);
  const lType = analyzeRegionPixels(offCtx, lPts, w, h);
  const rType = analyzeRegionPixels(offCtx, rPts, w, h);

  const mFPts = FOREHEAD_PTS.map(i    => scaled[i]);
  const mLPts = LEFT_CHEEK_PTS.map(i  => scaled[i]);
  const mRPts = RIGHT_CHEEK_PTS.map(i => scaled[i]);

  drawRegion(ctx, mFPts, SKIN_COLORS[fType]  || SKIN_COLORS.Unknown);
  drawRegion(ctx, mLPts, SKIN_COLORS[lType]  || SKIN_COLORS.Unknown);
  drawRegion(ctx, mRPts, SKIN_COLORS[rType]  || SKIN_COLORS.Unknown);

  const all = [fType, lType, rType];
  const overall = all.sort((a,b) =>
    all.filter(v=>v===b).length - all.filter(v=>v===a).length)[0];
  resultHistory.push(overall);
  if (resultHistory.length > HISTORY_SIZE) resultHistory.shift();
  const smoothed = resultHistory.sort((a,b) =>
    resultHistory.filter(v=>v===b).length - resultHistory.filter(v=>v===a).length)[0];

  const c = SKIN_COLORS[smoothed] || SKIN_COLORS.Unknown;
  skinBadgeText.textContent = smoothed + ' Skin';
  skinBadge.style.background = `rgba(${c.r},${c.g},${c.b},0.9)`;
}

// ── Start AR camera ───────────────────────────────────────────────────────────
async function startARCamera() {
  chooser.classList.add('hidden');
  cameraView.classList.remove('hidden');
  legendCard.classList.remove('hidden');
  arStatusText.textContent = 'Starting camera…';
  captureBtn.disabled = true;

  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode, width: { ideal: 1280 }, height: { ideal: 720 } }
    });
    videoFeed.srcObject = stream;

    await new Promise(r => videoFeed.onloadedmetadata = r);
    videoFeed.play();

    arCanvas.width  = videoFeed.videoWidth  || 640;
    arCanvas.height = videoFeed.videoHeight || 480;

    faceMesh = new FaceMesh({ locateFile: file =>
      `https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/${file}`
    });
    faceMesh.setOptions({
      maxNumFaces: 1,
      refineLandmarks: true,
      minDetectionConfidence: 0.5,
      minTrackingConfidence: 0.5,
    });
    faceMesh.onResults(onFaceMeshResults);

    arStatusText.textContent = 'Loading AR model…';

    camera = new Camera(videoFeed, {
      onFrame: async () => {
        await faceMesh.send({ image: videoFeed });
      },
      width: arCanvas.width,
      height: arCanvas.height,
    });
    await camera.start();
    arRunning = true;
    arStatusText.textContent = 'Initializing AR…';

  } catch(e) {
    alert('Camera permission denied. Please use the upload option instead.');
    stopCamera();
  }
}

function stopCamera() {
  if (camera)  { camera.stop(); camera = null; }
  if (stream)  { stream.getTracks().forEach(t => t.stop()); stream = null; }
  arRunning     = false;
  faceDetected  = false;
  resultHistory = [];
  captureBtn.disabled = true;
  cameraView.classList.add('hidden');
  legendCard.classList.add('hidden');
  chooser.classList.remove('hidden');
}

document.getElementById('openCameraBtn').addEventListener('click', startARCamera);

document.getElementById('flipCameraBtn').addEventListener('click', async () => {
  facingMode = facingMode === 'user' ? 'environment' : 'user';
  if (camera)  { camera.stop(); camera = null; }
  if (stream)  { stream.getTracks().forEach(t => t.stop()); stream = null; }
  await startARCamera();
});

document.getElementById('cancelCameraBtn').addEventListener('click', stopCamera);

// ── Capture ───────────────────────────────────────────────────────────────────
captureBtn.addEventListener('click', () => {
  if (!faceDetected) {
    alert('No face detected. Please position your face in the camera.');
    return;
  }

  snapCanvas.width  = videoFeed.videoWidth  || 640;
  snapCanvas.height = videoFeed.videoHeight || 480;
  const sCtx = snapCanvas.getContext('2d');
  sCtx.save();
  sCtx.translate(snapCanvas.width, 0);
  sCtx.scale(-1, 1);
  sCtx.drawImage(videoFeed, 0, 0);
  sCtx.restore();

  snapCanvas.toBlob(blob => {
    capturedBlob = blob;
    showPreview(URL.createObjectURL(blob), true);
    stopCamera();
  }, 'image/jpeg', 0.92);
});

// ── Upload ────────────────────────────────────────────────────────────────────
let faceApiReady = false;
const MODEL_URL  = 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights';

async function loadFaceApiModels() {
  if (faceApiReady) return;
  await faceapi.nets.tinyFaceDetector.loadFromUri(
    'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights'
  );
  faceApiReady = true;
}

async function imageHasFace(imgEl) {
  await loadFaceApiModels();
  const detection = await faceapi.detectSingleFace(
    imgEl,
    new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 })
  );
  return !!detection;
}

document.getElementById('openUploadBtn').addEventListener('click', () => photoFileInput.click());

photoFileInput.addEventListener('change', async () => {
  if (!photoFileInput.files.length) return;
  capturedBlob = null;

  const file    = photoFileInput.files[0];
  const url     = URL.createObjectURL(file);

  showPreview(url, false);

  faceCheckMsg.className    = 'sa-face-check';
  faceCheckText.textContent = '🔍 Checking for a face in your photo…';
  submitBtn.disabled = true;

  const tempImg = new Image();
  tempImg.src   = url;
  tempImg.onload = async () => {
    try {
      const hasFace = await imageHasFace(tempImg);
      if (hasFace) {
        faceCheckMsg.className    = 'sa-face-check success';
        faceCheckText.textContent = '✓ Face detected — ready to analyze!';
        submitBtn.disabled = false;
      } else {
        faceCheckMsg.className    = 'sa-face-check error';
        faceCheckText.textContent = '✗ No face detected. Please upload a clear photo of your face.';
        submitBtn.disabled = true;
      }
    } catch (err) {
      faceCheckMsg.className    = 'sa-face-check success';
      faceCheckText.textContent = '✓ Photo loaded — ready to analyze.';
      submitBtn.disabled = false;
    }
  };
});

function showPreview(url, faceAlreadyConfirmed) {
  previewImg.src = url;
  chooser.classList.add('hidden');
  cameraView.classList.add('hidden');
  saForm.classList.remove('hidden');

  if (faceAlreadyConfirmed) {
    faceCheckMsg.className    = 'sa-face-check success';
    faceCheckText.textContent = '✓ Face detected — ready to analyze!';
    faceCheckMsg.classList.remove('hidden');
    submitBtn.disabled = false;
  } else {
    faceCheckMsg.classList.remove('hidden');
    submitBtn.disabled = true;
  }
}

document.getElementById('retakeBtn').addEventListener('click', () => {
  saForm.classList.add('hidden');
  faceCheckMsg.classList.add('hidden');
  chooser.classList.remove('hidden');
  previewImg.src = '';
  photoFileInput.value = '';
  capturedBlob  = null;
  submitBtn.disabled = true;
});

// ── Submit ────────────────────────────────────────────────────────────────────
saForm.addEventListener('submit', function(e) {
  e.preventDefault();
  if (submitBtn.disabled) return;

  submitBtn.querySelector('.sa-btn-text').classList.add('hidden');
  submitBtn.querySelector('.sa-btn-loader').classList.remove('hidden');
  submitBtn.disabled = true;

  const doSubmit = () => {
    this.submit(); // real browser form POST — session flash preserved
  };

  if (capturedBlob) {
    // Swap the blob into the real file input via DataTransfer
    const dt = new DataTransfer();
    dt.items.add(new File([capturedBlob], 'capture.jpg', { type: 'image/jpeg' }));
    photoFileInput.files = dt.files;
    doSubmit();
  } else {
    doSubmit();
  }
});
</script>

@endsection