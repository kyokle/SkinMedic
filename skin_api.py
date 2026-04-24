"""
SkinMedic Python Backend
FastAPI + MediaPipe Tasks API + HuggingFace
Run with: uvicorn skin_api:app --reload --port 8000
"""

from fastapi import FastAPI, File, UploadFile, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
import mediapipe as mp
from mediapipe.tasks import python as mp_python
from mediapipe.tasks.python import vision as mp_vision
import numpy as np
import cv2
from PIL import Image
import urllib.request
import os
from transformers import pipeline

# ──────────────────────────────────────────────
# App Setup
# ──────────────────────────────────────────────
app = FastAPI(
    title="SkinMedic API",
    description="Skin analysis API using MediaPipe + HuggingFace",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Change to your Laravel URL in production
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ──────────────────────────────────────────────
# Load Models (once on startup)
# ──────────────────────────────────────────────

# Download MediaPipe face detector model if not present
FACE_MODEL_PATH = "face_detector.tflite"
FACE_MODEL_URL = (
    "https://storage.googleapis.com/mediapipe-models/face_detector/"
    "blaze_face_short_range/float16/1/blaze_face_short_range.tflite"
)

if not os.path.exists(FACE_MODEL_PATH):
    print("Downloading MediaPipe face detector model...")
    urllib.request.urlretrieve(FACE_MODEL_URL, FACE_MODEL_PATH)
    print("Face detector model downloaded.")

# Initialize MediaPipe Face Detector (new Tasks API)
_face_detector_options = mp_vision.FaceDetectorOptions(
    base_options=mp_python.BaseOptions(model_asset_path=FACE_MODEL_PATH),
    min_detection_confidence=0.5
)
face_detector = mp_vision.FaceDetector.create_from_options(_face_detector_options)

# HuggingFace skin condition classifier
print("Loading HuggingFace model...")
try:
    skin_classifier = pipeline(
        "image-classification",
        model="imfarzanansari/skintelligent-acne",
        top_k=5
    )
    print("HuggingFace model loaded.")
except Exception as e:
    print(f"Warning: Could not load HuggingFace model: {e}")
    skin_classifier = None


# ──────────────────────────────────────────────
# Helper Functions
# ──────────────────────────────────────────────

def decode_image(file_bytes: bytes) -> np.ndarray:
    """Convert uploaded file bytes to OpenCV image (BGR)."""
    nparr = np.frombuffer(file_bytes, np.uint8)
    img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
    if img is None:
        raise ValueError("Could not decode image.")
    return img


def detect_face(img_bgr: np.ndarray) -> dict:
    """
    Use MediaPipe Face Detection (Tasks API) to check if a face is present.
    Returns face bounding box and confidence.
    """
    img_rgb = cv2.cvtColor(img_bgr, cv2.COLOR_BGR2RGB)
    mp_image = mp.Image(image_format=mp.ImageFormat.SRGB, data=img_rgb)
    results = face_detector.detect(mp_image)

    if not results.detections:
        return {"face_detected": False}

    detection = results.detections[0]
    confidence = detection.categories[0].score
    bbox = detection.bounding_box

    return {
        "face_detected": True,
        "confidence": round(float(confidence), 3),
        "bounding_box": {
            "x": bbox.origin_x,
            "y": bbox.origin_y,
            "width": bbox.width,
            "height": bbox.height,
        }
    }


def classify_skin_type(img_bgr: np.ndarray, bbox: dict) -> dict:
    """
    Classify skin type (oily, dry, normal, combination) based on
    brightness and texture analysis of the face region.
    """
    x, y, w, h = bbox["x"], bbox["y"], bbox["width"], bbox["height"]

    img_h, img_w = img_bgr.shape[:2]
    x1 = max(0, x)
    y1 = max(0, y)
    x2 = min(img_w, x + w)
    y2 = min(img_h, y + h)

    face_crop = img_bgr[y1:y2, x1:x2]
    if face_crop.size == 0:
        return {"skin_type": "unknown", "reason": "Could not crop face region"}

    # HSV analysis for brightness and saturation
    hsv = cv2.cvtColor(face_crop, cv2.COLOR_BGR2HSV)
    brightness = np.mean(hsv[:, :, 2])
    saturation = np.mean(hsv[:, :, 1])

    # Laplacian variance for texture
    gray = cv2.cvtColor(face_crop, cv2.COLOR_BGR2GRAY)
    texture_score = cv2.Laplacian(gray, cv2.CV_64F).var()

    if brightness > 180 and saturation > 80:
        skin_type = "oily"
        description = "Skin appears shiny with high brightness and saturation."
    elif brightness < 120:
        skin_type = "dry"
        description = "Skin appears dull with low brightness levels."
    elif texture_score > 300:
        skin_type = "combination"
        description = "Mixed texture detected — likely combination skin."
    else:
        skin_type = "normal"
        description = "Balanced brightness and texture — likely normal skin."

    return {
        "skin_type": skin_type,
        "description": description,
        "metrics": {
            "brightness": round(float(brightness), 2),
            "saturation": round(float(saturation), 2),
            "texture_score": round(float(texture_score), 2),
        }
    }


def analyze_skin_conditions(img_bgr: np.ndarray, bbox: dict) -> dict:
    """
    Use HuggingFace model to classify skin conditions on the cropped face region.
    """
    if skin_classifier is None:
        return {"conditions": [], "error": "Model not loaded"}

    x, y, w, h = bbox["x"], bbox["y"], bbox["width"], bbox["height"]
    img_h, img_w = img_bgr.shape[:2]
    face_crop = img_bgr[max(0, y):min(img_h, y + h), max(0, x):min(img_w, x + w)]

    if face_crop.size == 0:
        return {"conditions": [], "error": "Empty face crop"}

    pil_img = Image.fromarray(cv2.cvtColor(face_crop, cv2.COLOR_BGR2RGB))
    predictions = skin_classifier(pil_img)

    conditions = [
        {"label": p["label"], "confidence": round(p["score"] * 100, 1)}
        for p in predictions
    ]

    return {"conditions": conditions}


def generate_recommendations(skin_type: str, conditions: list) -> list:
    """Generate skincare recommendations based on skin type and conditions."""
    recommendations = []

    type_recs = {
        "oily": [
            "Use a gentle foaming cleanser twice daily.",
            "Apply oil-free, non-comedogenic moisturizer.",
            "Use salicylic acid toner to control sebum.",
        ],
        "dry": [
            "Use a hydrating cream cleanser.",
            "Apply a rich moisturizer with hyaluronic acid.",
            "Avoid hot showers that strip natural oils.",
        ],
        "combination": [
            "Use a balanced gel cleanser.",
            "Apply lightweight moisturizer on oily zones.",
            "Use richer moisturizer on dry areas.",
        ],
        "normal": [
            "Maintain your routine with a gentle cleanser.",
            "Use a balanced SPF moisturizer daily.",
            "Exfoliate 1-2 times per week.",
        ],
    }
    recommendations.extend(type_recs.get(skin_type, []))

    condition_labels = [c["label"].lower() for c in conditions]
    if any("acne" in label or "pimple" in label for label in condition_labels):
        recommendations.append("Consider benzoyl peroxide or niacinamide for acne treatment.")
        recommendations.append("Avoid touching your face and change pillowcases frequently.")

    return recommendations


# ──────────────────────────────────────────────
# Routes
# ──────────────────────────────────────────────

@app.get("/")
def root():
    return {"message": "SkinMedic API is running!", "docs": "/docs"}


@app.get("/health")
def health():
    return {
        "status": "ok",
        "model_loaded": skin_classifier is not None
    }


@app.post("/analyze")
async def analyze(file: UploadFile = File(...)):
    """
    Main endpoint: Accepts a photo, detects face, classifies skin type,
    analyzes skin conditions, and returns recommendations.

    Called from Laravel:
        POST http://localhost:8000/analyze
        Body: multipart/form-data  { file: <image> }
    """
    if not file.content_type.startswith("image/"):
        raise HTTPException(status_code=400, detail="Uploaded file must be an image.")

    file_bytes = await file.read()

    try:
        img_bgr = decode_image(file_bytes)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

    # Step 1: Detect face
    face_result = detect_face(img_bgr)
    if not face_result["face_detected"]:
        return JSONResponse(content={
            "success": False,
            "error": "No face detected. Please take a clearer photo facing the camera."
        })

    bbox = face_result["bounding_box"]

    # Step 2: Classify skin type
    skin_type_result = classify_skin_type(img_bgr, bbox)

    # Step 3: Analyze skin conditions
    conditions_result = analyze_skin_conditions(img_bgr, bbox)

    # Step 4: Generate recommendations
    recommendations = generate_recommendations(
        skin_type_result["skin_type"],
        conditions_result.get("conditions", [])
    )

    return JSONResponse(content={
        "success": True,
        "face_detection": face_result,
        "skin_type": skin_type_result,
        "skin_conditions": conditions_result,
        "recommendations": recommendations,
    })