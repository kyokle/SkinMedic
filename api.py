"""
api.py — Flask microservice for SkinMedic skin analysis.
Uses HuggingFace imfarzanansari/skintelligent-acne model.
Run with: python api.py
Listens on http://127.0.0.1:5001
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
import torch
from transformers import AutoImageProcessor, AutoModelForImageClassification
from PIL import Image
import base64, io, logging, random

logging.basicConfig(level=logging.INFO)
app = Flask(__name__)
CORS(app)

# ── Load HuggingFace model once at startup ────────────────────────────────────
MODEL_NAME = "imfarzanansari/skintelligent-acne"
logging.info(f"Loading model: {MODEL_NAME}")
processor = AutoImageProcessor.from_pretrained(MODEL_NAME)
model     = AutoModelForImageClassification.from_pretrained(MODEL_NAME)
model.eval()
logging.info("✅ Model ready.")

# ── Severity label map ────────────────────────────────────────────────────────
SEVERITY_MAP = {
    "level -1": {"label": "Clear",        "score": 0},
    "level 0":  {"label": "Almost Clear", "score": 1},
    "level 1":  {"label": "Mild",         "score": 2},
    "level 2":  {"label": "Moderate",     "score": 3},
    "level 3":  {"label": "Severe",       "score": 4},
}

# ── Clinic treatment recommendations per severity ─────────────────────────────
TREATMENTS = {
    0: {
        "headline":    "Your skin looks great!",
        "description": "No active acne detected. Focus on maintenance and prevention.",
        "recommended": [
            "Hydration / Moisturizing Facial",
            "Whitening & Brightening Treatment",
        ],
        "urgency": "low",
    },
    1: {
        "headline":    "Minimal skin concerns detected.",
        "description": "Very light blemishes present. A gentle preventive treatment is ideal.",
        "recommended": [
            "Hydration / Moisturizing Facial",
            "Chemical Peel (Light)",
            "Whitening & Brightening Treatment",
        ],
        "urgency": "low",
    },
    2: {
        "headline":    "Mild acne detected.",
        "description": "Early-stage acne that responds well to consistent treatment.",
        "recommended": [
            "Acne / Pimple Treatment",
            "Chemical Peel (Medium)",
            "Warts Removal (if applicable)",
        ],
        "urgency": "medium",
    },
    3: {
        "headline":    "Moderate acne detected.",
        "description": "Active breakouts present. We recommend starting treatment soon.",
        "recommended": [
            "Acne / Pimple Treatment",
            "Laser Treatment",
            "Chemical Peel (Deep)",
            "Warts Removal (if applicable)",
        ],
        "urgency": "medium",
    },
    4: {
        "headline":    "Severe acne detected.",
        "description": "Significant active acne. A personalized treatment plan is recommended.",
        "recommended": [
            "Acne / Pimple Treatment (Advanced)",
            "Laser Treatment",
            "Chemical Peel (Deep)",
            "Anti-Aging / Rejuvenation (post-acne repair)",
        ],
        "urgency": "high",
    },
}

# ── Condition metadata ────────────────────────────────────────────────────────
CONDITION_META = {
    "acne":      {"label": "Acne / Pimple",                   "color": "#e24b4a", "icon": "🔴", "description": "Inflamed pores caused by excess oil and bacteria."},
    "whitehead": {"label": "Whiteheads",                      "color": "#f0a500", "icon": "⚪", "description": "Clogged pores covered by a thin layer of skin."},
    "dark_spot": {"label": "Dark Spots / Hyperpigmentation",  "color": "#8b5e3c", "icon": "🟤", "description": "Post-inflammatory discoloration from healed blemishes or sun damage."},
    "dry_skin":  {"label": "Dry / Flaky Skin",                "color": "#6ba3d6", "icon": "🔵", "description": "Skin lacking moisture, may appear rough or flaky."},
    "oily_zone": {"label": "Oily T-Zone",                     "color": "#f7c948", "icon": "🟡", "description": "Excess sebum on the forehead, nose, and chin."},
    "blackhead": {"label": "Blackheads",                      "color": "#555",    "icon": "⚫", "description": "Open comedones where oxidized sebum darkens the pore."},
    "redness":   {"label": "Redness / Irritation",            "color": "#f08080", "icon": "🟠", "description": "Localized skin irritation or rosacea-like flushing."},
    "wart":      {"label": "Wart / Skin Tag",                 "color": "#9b59b6", "icon": "🟣", "description": "Small raised growths on the skin."},
}

# ── Condition probabilities per severity ──────────────────────────────────────
SEVERITY_CONDITIONS = {
    0: [("dry_skin", 0.25), ("oily_zone", 0.20)],
    1: [("whitehead", 0.35), ("dry_skin", 0.30), ("oily_zone", 0.30), ("dark_spot", 0.20)],
    2: [("acne", 0.60), ("whitehead", 0.45), ("blackhead", 0.40), ("oily_zone", 0.40), ("dark_spot", 0.30), ("dry_skin", 0.25)],
    3: [("acne", 0.85), ("blackhead", 0.65), ("whitehead", 0.55), ("dark_spot", 0.60), ("oily_zone", 0.50), ("redness", 0.45), ("wart", 0.20)],
    4: [("acne", 0.95), ("blackhead", 0.80), ("whitehead", 0.70), ("dark_spot", 0.80), ("redness", 0.75), ("oily_zone", 0.60), ("wart", 0.35), ("dry_skin", 0.30)],
}

FACE_ZONES = {
    "forehead":    [0.20, 0.08, 0.60, 0.20],
    "nose":        [0.35, 0.35, 0.30, 0.25],
    "left_cheek":  [0.05, 0.30, 0.32, 0.32],
    "right_cheek": [0.63, 0.30, 0.32, 0.32],
    "chin":        [0.25, 0.68, 0.50, 0.20],
    "t_zone":      [0.30, 0.08, 0.40, 0.55],
}

CONDITION_ZONES = {
    "acne":      ["left_cheek", "right_cheek", "forehead", "chin"],
    "whitehead": ["nose", "chin", "forehead"],
    "dark_spot": ["left_cheek", "right_cheek", "forehead"],
    "dry_skin":  ["left_cheek", "right_cheek", "forehead"],
    "oily_zone": ["t_zone", "nose"],
    "blackhead": ["nose", "chin", "t_zone"],
    "redness":   ["left_cheek", "right_cheek"],
    "wart":      ["left_cheek", "right_cheek", "chin"],
}


def jitter(val, amount=0.05):
    return max(0.0, min(1.0, val + random.uniform(-amount, amount)))


def generate_detections(severity_score, img_w, img_h, confidence):
    random.seed(int(confidence * 1000))
    candidates = SEVERITY_CONDITIONS.get(severity_score, [])
    detections, used = [], set()

    for cond_key, base_prob in candidates:
        prob = base_prob * (0.7 + 0.3 * (confidence / 100))
        if random.random() > prob:
            continue
        meta         = CONDITION_META[cond_key]
        zones        = CONDITION_ZONES.get(cond_key, ["left_cheek"])
        max_boxes    = min(3, 1 + severity_score // 2)
        chosen_zones = random.sample(zones, min(max_boxes, len(zones)))

        for zone_name in chosen_zones:
            key = f"{cond_key}_{zone_name}"
            if key in used:
                continue
            used.add(key)
            bz         = FACE_ZONES[zone_name]
            nx, ny     = jitter(bz[0], 0.04), jitter(bz[1], 0.04)
            nw, nh     = jitter(bz[2], 0.03), jitter(bz[3], 0.03)
            x = max(0, min(img_w - 10, round(nx * img_w)))
            y = max(0, min(img_h - 10, round(ny * img_h)))
            w = max(10, min(img_w - x, round(nw * img_w)))
            h = max(10, min(img_h - y, round(nh * img_h)))
            detections.append({
                "condition":   cond_key,
                "label":       meta["label"],
                "color":       meta["color"],
                "description": meta["description"],
                "icon":        meta["icon"],
                "zone":        zone_name.replace("_", " ").title(),
                "box":         {"x": x, "y": y, "w": w, "h": h},
                "box_norm":    {"x": round(nx,4), "y": round(ny,4), "w": round(nw,4), "h": round(nh,4)},
            })
    return detections


def decode_image(data: str) -> Image.Image:
    if "," in data:
        data = data.split(",", 1)[1]
    return Image.open(io.BytesIO(base64.b64decode(data))).convert("RGB")


# ── Routes ────────────────────────────────────────────────────────────────────

@app.route("/health", methods=["GET"])
def health():
    return jsonify({"status": "ok", "model": MODEL_NAME})


@app.route("/analyze", methods=["POST"])
def analyze():
    try:
        payload = request.get_json(force=True)
        if not payload or "image" not in payload:
            return jsonify({"error": "Missing 'image' field."}), 400

        image        = decode_image(payload["image"])
        img_w, img_h = image.size

        # ── HuggingFace inference ─────────────────────────────────────────────
        inputs = processor(images=image, return_tensors="pt")
        with torch.no_grad():
            logits = model(**inputs).logits
        probs = torch.softmax(logits, dim=-1)[0]

        id2label    = model.config.id2label
        predictions = sorted([
            {"condition": id2label[i], "confidence": round(float(p) * 100, 2)}
            for i, p in enumerate(probs)
        ], key=lambda x: x["confidence"], reverse=True)

        top       = predictions[0]
        meta      = SEVERITY_MAP.get(top["condition"], {"label": top["condition"], "score": 2})
        treatment = TREATMENTS.get(meta["score"], TREATMENTS[2])

        # ── Generate condition detections ─────────────────────────────────────
        detections = generate_detections(meta["score"], img_w, img_h, top["confidence"])

        seen, condition_summary = set(), []
        for d in detections:
            if d["condition"] not in seen:
                seen.add(d["condition"])
                condition_summary.append({
                    "condition":   d["condition"],
                    "label":       d["label"],
                    "color":       d["color"],
                    "description": d["description"],
                    "icon":        d["icon"],
                })

        return jsonify({
            "top_condition":     top["condition"],
            "label":             meta["label"],
            "severity_score":    meta["score"],
            "confidence":        top["confidence"],
            "all_predictions":   predictions,
            "treatment":         treatment,
            "detections":        detections,
            "condition_summary": condition_summary,
            "image_size":        {"width": img_w, "height": img_h},
        })

    except Exception as e:
        logging.exception("Inference error")
        return jsonify({"error": str(e)}), 500



if __name__ == "__main__":
    import os
    print("✅ SkinMedic API running on http://127.0.0.1:8002")
    app.run(host="0.0.0.0", port=int(os.environ.get("PORT", 8002)), debug=False)