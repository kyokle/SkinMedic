{{-- resources/views/patient_AR_Skin_Analysis.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>AR Skin Analysis – SkinMedic</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('asset/css/sidebar_patient.css') }}">
  <link rel="stylesheet" href="{{ asset('asset/css/patient_ar_skin_analysis.css') }}">
  <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/face_mesh.js" crossorigin="anonymous"></script>
</head>
<body>

@include('partials.sidebar_patient')

<div class="main">
  <div class="topbar">
    <h2>AR Skin Analysis</h2>
    <div class="date-box">
      <p>Today's Date</p>
      <strong>{{ now()->toDateString() }}</strong>
    </div>
  </div>

  <div class="sa-page">
    <div class="sa-header">
      <p class="kicker">AI-POWERED</p>
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

      {{-- LEFT --}}
      <div class="sa-left">

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

        <div class="sa-camera-wrap hidden" id="cameraView">
          <div class="sa-ar-status" id="arStatus">
            <span class="sa-ar-dot"></span>
            <span id="arStatusText">Initializing AR…</span>
          </div>
          <div class="sa-skin-badge hidden" id="skinBadge">
            <span id="skinBadgeText">Detecting…</span>
          </div>
          <div class="sa-ar-container">
            <video id="videoFeed" autoplay playsinline muted></video>
            <canvas id="arCanvas"></canvas>
          </div>
          <div class="sa-cam-controls">
            <button type="button" class="sa-cam-cancel" id="cancelCameraBtn">Cancel</button>
            <button type="button" class="sa-cam-capture" id="captureBtn">
              <span class="sa-shutter"></span>
            </button>
            <button type="button" class="sa-cam-flip" id="flipCameraBtn">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M1 4v6h6"/><path d="M23 20v-6h-6"/>
                <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15"/>
              </svg>
            </button>
          </div>
          <canvas id="snapCanvas" class="hidden"></canvas>
        </div>

        <form action="{{ route('patient.skin-analysis.analyze') }}" method="POST"
              enctype="multipart/form-data" id="saForm" class="hidden">
          @csrf
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
          <button type="submit" class="sa-submit-btn" id="submitBtn">
            <span class="sa-btn-text">Analyze My Skin</span>
            <span class="sa-btn-loader hidden">Analyzing…</span>
          </button>
        </form>

      </div>

      {{-- RIGHT --}}
      <div class="sa-right">
        <div class="sa-info-card">
          <h3 class="sa-info-title">How it works</h3>
          <ol class="sa-steps-list">
            <li><strong>Take or upload</strong> a clear close-up photo of your face</li>
            <li><strong>AR tracks</strong> your face regions in real time</li>
            <li><strong>AI analyzes</strong> your skin condition in seconds</li>
            <li><strong>Get recommendations</strong> matched to our clinic's treatments</li>
          </ol>
        </div>

        <div class="sa-info-card">
          <h3 class="sa-info-title">For best results</h3>
          <ul class="sa-tips-list">
            <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#80a833" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Good natural lighting</li>
            <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#80a833" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Face the camera directly</li>
            <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#80a833" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> No heavy makeup or filters</li>
            <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#80a833" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Recent photo works best</li>
          </ul>
        </div>

        <div class="sa-info-card hidden" id="legendCard">
          <h3 class="sa-info-title">Live Detection</h3>
          <div class="sa-legend">
            <div class="sa-legend-item"><span class="sa-legend-dot" style="background:#22c55e"></span> Normal</div>
            <div class="sa-legend-item"><span class="sa-legend-dot" style="background:#3b82f6"></span> Dry</div>
            <div class="sa-legend-item"><span class="sa-legend-dot" style="background:#f97316"></span> Oily</div>
            <div class="sa-legend-item"><span class="sa-legend-dot" style="background:#a855f7"></span> Combination</div>
          </div>
          <p style="font-size:11px;color:#9c8f83;margin:10px 0 0;">Colors shown on your face regions update in real time.</p>
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
</div>

<script>
const chooser=document.getElementById('chooser'),cameraView=document.getElementById('cameraView'),saForm=document.getElementById('saForm'),videoFeed=document.getElementById('videoFeed'),arCanvas=document.getElementById('arCanvas'),snapCanvas=document.getElementById('snapCanvas'),previewImg=document.getElementById('previewImg'),photoFileInput=document.getElementById('photoFileInput'),submitBtn=document.getElementById('submitBtn'),arStatus=document.getElementById('arStatus'),arStatusDot=arStatus.querySelector('.sa-ar-dot'),arStatusText=document.getElementById('arStatusText'),skinBadge=document.getElementById('skinBadge'),skinBadgeText=document.getElementById('skinBadgeText'),legendCard=document.getElementById('legendCard');
let stream=null,facingMode='user',capturedBlob=null,faceMesh=null,camera=null,arRunning=false,resultHistory=[];
const HISTORY_SIZE=15;
const SKIN_COLORS={Normal:{r:34,g:197,b:94},Dry:{r:59,g:130,b:246},Oily:{r:249,g:115,b:22},Combination:{r:168,g:85,b:247},Unknown:{r:200,g:200,b:200}};
const FOREHEAD_PTS=[10,67,109,338,297,332],LEFT_CHEEK_PTS=[50,101,118,117,123],RIGHT_CHEEK_PTS=[280,330,347,346,352];

function analyzeRegionPixels(ctx,pts,w,h){if(!pts||!pts.length)return'Unknown';const xs=pts.map(p=>p.x*w),ys=pts.map(p=>p.y*h);const x=Math.max(0,Math.min(...xs)-10),y=Math.max(0,Math.min(...ys)-10),x2=Math.min(w,Math.max(...xs)+10),y2=Math.min(h,Math.max(...ys)+10),rw=x2-x,rh=y2-y;if(rw<=0||rh<=0)return'Unknown';const data=ctx.getImageData(x,y,rw,rh).data;let tR=0,tG=0,tB=0,c=0;for(let i=0;i<data.length;i+=4){tR+=data[i];tG+=data[i+1];tB+=data[i+2];c++;}if(!c)return'Unknown';const r=tR/c,g=tG/c,b=tB/c,max=Math.max(r,g,b)/255,min=Math.min(r,g,b)/255,s=max===0?0:(max-min)/max,l=0.299*r+0.587*g+0.114*b,sat255=s*255,val255=(max)*255;if(sat255>80&&val255>150)return'Oily';if(sat255<50&&l>140)return'Dry';if(sat255>60&&l>120)return'Combination';return'Normal';}

function drawRegion(ctx,pts,color,alpha=0.35){if(!pts||pts.length<3)return;ctx.save();ctx.globalAlpha=alpha;ctx.fillStyle=`rgb(${color.r},${color.g},${color.b})`;ctx.beginPath();ctx.moveTo(pts[0].x,pts[0].y);for(let i=1;i<pts.length;i++)ctx.lineTo(pts[i].x,pts[i].y);ctx.closePath();ctx.fill();ctx.globalAlpha=1;ctx.strokeStyle=`rgba(${color.r},${color.g},${color.b},0.8)`;ctx.lineWidth=1.5;ctx.beginPath();ctx.moveTo(pts[0].x,pts[0].y);for(let i=1;i<pts.length;i++)ctx.lineTo(pts[i].x,pts[i].y);ctx.closePath();ctx.stroke();ctx.restore();}

function drawMesh(ctx,lm){ctx.save();ctx.fillStyle='rgba(128,168,51,0.55)';for(const p of lm){ctx.beginPath();ctx.arc(p.x,p.y,1.2,0,Math.PI*2);ctx.fill();}ctx.restore();}

function onFaceMeshResults(results){const w=arCanvas.width,h=arCanvas.height,ctx=arCanvas.getContext('2d');ctx.clearRect(0,0,w,h);if(!results.multiFaceLandmarks||!results.multiFaceLandmarks.length){arStatusText.textContent='No face detected';arStatusDot.classList.remove('active');skinBadge.classList.add('hidden');return;}arStatusText.textContent='Face tracked ✓';arStatusDot.classList.add('active');skinBadge.classList.remove('hidden');const lm=results.multiFaceLandmarks[0];const scaled=lm.map(p=>({x:(1-p.x)*w,y:p.y*h}));const raw=lm.map(p=>({x:p.x*w,y:p.y*h}));drawMesh(ctx,scaled);const off=document.createElement('canvas');off.width=w;off.height=h;const oCtx=off.getContext('2d');oCtx.drawImage(videoFeed,0,0,w,h);const fT=analyzeRegionPixels(oCtx,FOREHEAD_PTS.map(i=>raw[i]),w,h),lT=analyzeRegionPixels(oCtx,LEFT_CHEEK_PTS.map(i=>raw[i]),w,h),rT=analyzeRegionPixels(oCtx,RIGHT_CHEEK_PTS.map(i=>raw[i]),w,h);drawRegion(ctx,FOREHEAD_PTS.map(i=>scaled[i]),SKIN_COLORS[fT]||SKIN_COLORS.Unknown);drawRegion(ctx,LEFT_CHEEK_PTS.map(i=>scaled[i]),SKIN_COLORS[lT]||SKIN_COLORS.Unknown);drawRegion(ctx,RIGHT_CHEEK_PTS.map(i=>scaled[i]),SKIN_COLORS[rT]||SKIN_COLORS.Unknown);const all=[fT,lT,rT],overall=all.sort((a,b)=>all.filter(v=>v===b).length-all.filter(v=>v===a).length)[0];resultHistory.push(overall);if(resultHistory.length>HISTORY_SIZE)resultHistory.shift();const sm=resultHistory.sort((a,b)=>resultHistory.filter(v=>v===b).length-resultHistory.filter(v=>v===a).length)[0];const c=SKIN_COLORS[sm]||SKIN_COLORS.Unknown;skinBadgeText.textContent=sm+' Skin';skinBadge.style.background=`rgba(${c.r},${c.g},${c.b},0.9)`;}

async function startARCamera(){chooser.classList.add('hidden');cameraView.classList.remove('hidden');legendCard.classList.remove('hidden');arStatusText.textContent='Starting camera…';try{stream=await navigator.mediaDevices.getUserMedia({video:{facingMode,width:{ideal:1280},height:{ideal:720}}});videoFeed.srcObject=stream;await new Promise(r=>videoFeed.onloadedmetadata=r);videoFeed.play();arCanvas.width=videoFeed.videoWidth||640;arCanvas.height=videoFeed.videoHeight||480;faceMesh=new FaceMesh({locateFile:f=>`https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/${f}`});faceMesh.setOptions({maxNumFaces:1,refineLandmarks:true,minDetectionConfidence:0.5,minTrackingConfidence:0.5});faceMesh.onResults(onFaceMeshResults);arStatusText.textContent='Loading AR model…';camera=new Camera(videoFeed,{onFrame:async()=>{await faceMesh.send({image:videoFeed});},width:arCanvas.width,height:arCanvas.height});await camera.start();arRunning=true;arStatusText.textContent='Initializing AR…';}catch(e){alert('Camera permission denied. Please use the upload option instead.');stopCamera();}}

function stopCamera(){if(camera){camera.stop();camera=null;}if(stream){stream.getTracks().forEach(t=>t.stop());stream=null;}arRunning=false;resultHistory=[];cameraView.classList.add('hidden');legendCard.classList.add('hidden');chooser.classList.remove('hidden');}

document.getElementById('openCameraBtn').addEventListener('click',startARCamera);
document.getElementById('flipCameraBtn').addEventListener('click',async()=>{facingMode=facingMode==='user'?'environment':'user';if(camera){camera.stop();camera=null;}if(stream){stream.getTracks().forEach(t=>t.stop());stream=null;}await startARCamera();});
document.getElementById('cancelCameraBtn').addEventListener('click',stopCamera);
document.getElementById('captureBtn').addEventListener('click',()=>{snapCanvas.width=videoFeed.videoWidth||640;snapCanvas.height=videoFeed.videoHeight||480;const sCtx=snapCanvas.getContext('2d');sCtx.save();sCtx.translate(snapCanvas.width,0);sCtx.scale(-1,1);sCtx.drawImage(videoFeed,0,0);sCtx.restore();snapCanvas.toBlob(blob=>{capturedBlob=blob;showPreview(URL.createObjectURL(blob));stopCamera();},'image/jpeg',0.92);});
document.getElementById('openUploadBtn').addEventListener('click',()=>photoFileInput.click());
photoFileInput.addEventListener('change',()=>{if(!photoFileInput.files.length)return;capturedBlob=null;showPreview(URL.createObjectURL(photoFileInput.files[0]));});

function showPreview(url){previewImg.src=url;chooser.classList.add('hidden');cameraView.classList.add('hidden');saForm.classList.remove('hidden');}

document.getElementById('retakeBtn').addEventListener('click',()=>{saForm.classList.add('hidden');chooser.classList.remove('hidden');previewImg.src='';photoFileInput.value='';capturedBlob=null;});

saForm.addEventListener('submit',function(e){e.preventDefault();const fd=new FormData(this);if(capturedBlob){fd.delete('photo');fd.set('photo',capturedBlob,'capture.jpg');}submitBtn.querySelector('.sa-btn-text').classList.add('hidden');submitBtn.querySelector('.sa-btn-loader').classList.remove('hidden');submitBtn.disabled=true;fetch(this.action,{method:'POST',body:fd,redirect:'manual'}).then(r=>{if(r.type==='opaqueredirect'||r.status===302||r.ok){window.location.href='{{ route("patient.skin-analysis.result") }}';}else{return r.text().then(html=>{document.open();document.write(html);document.close();});}}).catch(()=>{alert('Something went wrong. Please try again.');submitBtn.querySelector('.sa-btn-text').classList.remove('hidden');submitBtn.querySelector('.sa-btn-loader').classList.add('hidden');submitBtn.disabled=false;});});
</script>

</body>
</html>