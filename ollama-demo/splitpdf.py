from flask import Flask, request, render_template, jsonify, send_file
import fitz  # PyMuPDF
import base64
import io
import os
import re
from PIL import Image as PILImage, ImageDraw
import pytesseract
from flask import send_from_directory
from flask import request
from spellchecker import SpellChecker
import language_tool_python
from collections import defaultdict
import cv2
import numpy as np
import requests
import math
import sys
sys.stdout.reconfigure(encoding='utf-8')

# Path to tesseract executable (if not in PATH)
pytesseract.pytesseract.tesseract_cmd = r"C:\Program Files\Tesseract-OCR\tesseract.exe"
os.makedirs("extracted_images", exist_ok=True)

app = Flask(__name__)


def upload_to_php(folder_id, file_path):
    url = f"http://localhost/FYP/FYP%20Interface/viewfile.php?folder_id={folder_id}"
    with open(file_path, "rb") as f:
        files = {"custom-file-upload": (os.path.basename(file_path), f)}
        response = requests.post(url, files=files)
    return response.status_code, response.text

@app.route("/upload-ucd", methods=["POST"])
def upload_ucd():
    folder_id = request.args.get("folder_id")
    file = request.files.get("custom-file-upload")  

    if not file or not folder_id:
        return jsonify({"error": "Missing file or folder_id"}), 400

    save_path = f"./temp/{file.filename}"
    os.makedirs("./temp", exist_ok=True)
    file.save(save_path)

    # Forward to PHP
    status, response_text = upload_to_php(folder_id, save_path)

    return jsonify({
        "flaskSaved": save_path,
        "phpStatus": status,
        "phpResponse": response_text
    })



def detect_lines(filepath):
    img = cv2.imread(filepath)
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    edges = cv2.Canny(gray, 50, 150, apertureSize=3)
    lines = cv2.HoughLinesP(edges, 1, np.pi / 180, threshold=80, minLineLength=50, maxLineGap=10)
    return lines


def line_touches_box(line, box, padding=10):
    x1, y1, x2, y2 = line[0]
    left = box['x'] - box['width'] / 2 - padding
    right = box['x'] + box['width'] / 2 + padding
    top = box['y'] - box['height'] / 2 - padding
    bottom = box['y'] + box['height'] / 2 + padding
    return ((left <= x1 <= right and top <= y1 <= bottom) or
            (left <= x2 <= right and top <= y2 <= bottom))

def line_overlaps_textbox(line, boxes, padding=10):
    x1, y1, x2, y2 = line[0]
    for box in boxes:
        left = box['x'] - box['width'] / 2 - padding
        right = box['x'] + box['width'] / 2 + padding
        top = box['y'] - box['height'] / 2 - padding
        bottom = box['y'] + box['height'] / 2 + padding

        if (left <= x1 <= right and top <= y1 <= bottom) or (left <= x2 <= right and top <= y2 <= bottom):
            return True
    return False


def analyze_connections_advanced(predictions, opencv_lines):
    actors = []
    use_cases = []
    results = {
        'floating_use_cases': [],
        'floating_actors': [],
        'incorrect_relationships': [],
        'ownership_map': defaultdict(list),
        'note': ''
    }

    for p in predictions:
        label = p['class']
        if label == 'Actor':
            actors.append(p)
        elif label == 'Use_Case':
            use_cases.append(p)

    for uc in use_cases:
        uc_connected = False
        for actor in actors:
            if opencv_lines is not None:
                for line in opencv_lines:
                    if line_touches_box(line, uc) and line_touches_box(line, actor):
                        results['ownership_map'][(actor['x'], actor['y'])].append(uc)
                        uc_connected = True
        for other_uc in use_cases:
            if other_uc != uc and opencv_lines is not None:
                for line in opencv_lines:
                    if line_touches_box(line, uc) and line_touches_box(line, other_uc):
                        uc_connected = True
        if not uc_connected:
            results['floating_use_cases'].append(uc)

    for actor in actors:
        if (actor['x'], actor['y']) not in results['ownership_map']:
            results['floating_actors'].append(actor)

    if results['floating_use_cases'] or results['floating_actors']:
        results['note'] = "\u26a0\ufe0f Detected unconnected elements. Some diagram structure issues may exist."

    return results
@app.route("/")
def index():
    return render_template("splitpdf.html")


@app.route("/preview", methods=["POST"])
def preview():
    file = request.files["pdf"]
    doc = fitz.open(stream=file.read(), filetype="pdf")

    previews = []
    for page_num in range(len(doc)):
        page = doc.load_page(page_num)
        pix = page.get_pixmap(matrix=fitz.Matrix(2, 2))  # Higher quality
        img_bytes = pix.tobytes("png")
        encoded = base64.b64encode(img_bytes).decode("utf-8")
        previews.append(
            {"page": page_num + 1, "img": f"data:image/png;base64,{encoded}"}
        )

    return jsonify(previews)


@app.route("/split", methods=["POST"])
def split():
    file = request.files["pdf"]
    selected_pages = list(map(int, request.form["pages"].split(",")))

    doc = fitz.open(stream=file.read(), filetype="pdf")
    new_pdf = fitz.open()

    for page_num in selected_pages:
        if 1 <= page_num <= len(doc):
            new_pdf.insert_pdf(doc, from_page=page_num - 1, to_page=page_num - 1)

    buffer = io.BytesIO()
    new_pdf.save(buffer)
    buffer.seek(0)

    return send_file(
        buffer,
        as_attachment=True,
        download_name="selected_pages.pdf",
        mimetype="application/pdf",
    )



@app.route("/extract-images-ocr", methods=["POST"])
def extract_images_and_ocr():
    file = request.files["pdf"]
    pages = request.form.get("pages")
    if not pages:
        return jsonify({"error": "No pages provided"}), 400

    page_numbers = list(map(int, pages.split(",")))
    doc = fitz.open(stream=file.read(), filetype="pdf")
    extracted_results = []

error_labels = {
    "Incorrect Association Lines": {
        "reason": "Line violates UML association rule",
        "suggestion": "Ensure association lines are solid, straight without any arrow, and clearly link actors to use cases."
    },
    "wrong_extends_line": {
        "reason": "Line drawn incorrectly, possibly drawn in no dotted line or wrong arrow style",
        "suggestion": "Use a dotted line with a hollow arrowhead for extend relationships."
    },
    "wrong_include_line": {
        "reason": "Line drawn incorrectly, possibly drawn in no dotted line or wrong arrow style",
        "suggestion": "Use a dotted line with a closed arrowhead pointing to the included use case."
    },
    "Incorrect Use Case": {
        "reason": "Use case shape not following UML convention",
        "suggestion": "Use oval shapes for use cases according to UML standards."
    },
    "IncorrectUse Case Diagram Title": {
        "reason": "Title format invalid or missing",
        "suggestion": "Add a system title like 'Student Registration System' centered inside the boundary."
    },
    "IncorrectUse_Case_Boundary": {
        "reason": "Use case boundary drawn incorrectly, possibly contains non-UML elements",
        "suggestion": "Draw a rectangular boundary to enclose all use cases and place the system title inside."
    },
    "Incorrect_Actor": {
        "reason": "Actor not drawn using proper UML actor style",
        "suggestion": "Use stick figure or proper labeled icon to represent actor."
    },
    "Wrong Position Use Case Diagram Title": {
        "reason": "Title not placed inside use case boundary",
        "suggestion": "Move the system title inside the use case boundary rectangle and placed it at the center top."
    },
    "Line with no label": {
        "reason": "Line has no label; possibly an incomplete relationship",
        "suggestion": "Add appropriate labels like 'include', 'extend', or actor-use case association."
    }
}
    enable_line_check = request.form.get("enableLineCheck") == "true" or request.form.get("enableLineCheck") == "on"

    for page_num in page_numbers:
        actor_to_usecases = {}
        if page_num < 1 or page_num > len(doc):
            continue

        page = doc.load_page(page_num - 1)
        images = page.get_images(full=True)
        for idx, img in enumerate(images):
            xref = img[0]
            base_image = doc.extract_image(xref)
            image_bytes = base_image["image"]
            image_ext = base_image["ext"]
            filename = f"page{page_num}_img{idx + 1}.{image_ext}"
            filepath = os.path.join("extracted_images", filename)

            with open(filepath, "wb") as f:
                f.write(image_bytes)
            predictions = []  # ✅ Default in case Roboflow fails
            include_count = 0
            extend_count = 0
            connection_results = {
                'floating_use_cases': [],
                'floating_actors': [],
                'incorrect_relationships': [],
                'ownership_map': defaultdict(list),
                'note': ''
            }
            try:
                with open(filepath, "rb") as f:
                    img_base64 = base64.b64encode(f.read()).decode("utf-8")

                response = requests.post(
                    "https://serverless.roboflow.com/use-case-diagram-ocaut/5",
                    params={"api_key": "erhUiKryC5pLrlT5t0as"},
                    data=img_base64,
                    headers={"Content-Type": "application/x-www-form-urlencoded"},
                    timeout=20,
                )
                response.raise_for_status()
                predictions = response.json().get("predictions", [])
                include_count = sum(1 for p in predictions if p['class'].lower() == 'include_label')
                extend_count = sum(1 for p in predictions if p['class'].lower() == 'extends_label')  


            except Exception as e:
                extracted_results.append({
                    "page": page_num,
                    "image": filename,
                    "title": [],
                    "actors": [],
                    "useCases": [],
                    "issues": [{
                        "label": "Prediction Failed",
                        "reason": f"Roboflow request failed: {str(e)}",
                        "confidence": 0.0,
                    }],
                    "categorized": actor_to_usecases,
                })
                continue

            if enable_line_check:
                opencv_lines = detect_lines(filepath)
                connection_results = analyze_connections_advanced(predictions, opencv_lines)
            else:
                opencv_lines = None
                connection_results = {
                    'floating_use_cases': [],
                    'floating_actors': [],
                    'incorrect_relationships': [],
                    'ownership_map': defaultdict(list),
                    'note': ''
                }

            ignore_text_labels = [
                "Use_Case_Text", "Actor_Text", "Use Case Diagram Title",
                "extends_label", "include_label"
            ]
            text_boxes = [p for p in predictions if p['class'] in ignore_text_labels]
            img = PILImage.open(filepath).convert("RGB")
            draw = ImageDraw.Draw(img)

            if enable_line_check and opencv_lines is not None:
                for line in opencv_lines:
                    if not line_overlaps_textbox(line, text_boxes):
                        x1, y1, x2, y2 = line[0]
                        draw.line([x1, y1, x2, y2], fill="purple", width=3)

            actor_texts = []
            use_case_texts = []
            diagram_titles = []
            issues = []

            for pred in predictions:
                label = pred.get("class")
                conf = pred.get("confidence", 0)

                x, y, w, h = pred["x"], pred["y"], pred["width"], pred["height"]
                left, top = int(x - w / 2), int(y - h / 2)
                right, bottom = int(x + w / 2), int(y + h / 2)

                draw.rectangle([left, top, right, bottom], outline="green", width=2)
                draw.text((left, top - 12), f"{label} ({conf:.2f})", fill="green")

                if label in ["Actor_Text", "Use_Case_Text", "Use Case Diagram Title"]:
                    cropped = img.crop((max(0, left + 1), max(0, top + 1), min(img.width, right - 1), min(img.height, bottom - 1)))
                    ocr_text = pytesseract.image_to_string(cropped).strip()
                    if label == "Actor_Text" and ocr_text:
                        actor_texts.append(ocr_text)
                    elif label == "Use_Case_Text" and ocr_text:
                        use_case_texts.append(ocr_text)
                    elif label == "Use Case Diagram Title" and ocr_text:
                        diagram_titles.append(ocr_text)

                if label in error_labels:
                    issue_info = error_labels[label]
                    if isinstance(issue_info, dict):
                        issues.append({
                            "label": label,
                            "reason": issue_info.get("reason", ""),
                            "suggestion": issue_info.get("suggestion", ""),
                            "confidence": conf
                        })
                    else:
        
                        issues.append({
                            "label": label,
                            "reason": issue_info,
                            "suggestion": "",
                            "confidence": conf
                        })

            if connection_results['note']:
                issues.append({"label": "Connection Issue", "reason": connection_results['note'], "confidence": 0.6})
                for uc in connection_results['floating_use_cases']:
                    issues.append({"label": "Floating Use Case", "reason": f"Use case at ({uc['x']}, {uc['y']}) appears unconnected.", "confidence": 0.5})
                for a in connection_results['floating_actors']:
                    issues.append({"label": "Floating Actor", "reason": f"Actor at ({a['x']}, {a['y']}) appears unconnected.", "confidence": 0.5})

            def has_label(predictions, label, threshold=0.45):
                return any(
                    p["class"] == label and p.get("confidence", 0) >= threshold for p in predictions
                )

            if not has_label(predictions, "Use Case Diagram Title"):
                issues.append({
                    "label": "Missing System Name",
                    "reason": "The diagram is missing a proper system name (e.g., 'Music Management System').",
                    "confidence": 0.7,
                    "suggestion": "Add a clear system name in the middle top and inside the use case boundary box.",
                })

            if not has_label(predictions, "Use_Case_Boundary"):
                issues.append({
                    "label": "Missing Boundary",
                    "reason": "The diagram is missing a proper use case boundary.",
                    "confidence": 0.7,
                    "suggestion": "Draw a rectangular system boundary that encloses all use cases and contains the system name.",
                })
# Show floating elements visually
            floating_ucs = [p for p in predictions if any(abs(p['x'] - uc['x']) < 1 and abs(p['y'] - uc['y']) < 1 for uc in predictions if uc.get('label') == 'Floating Use Case')]
            for uc in connection_results["floating_use_cases"]:
                lx = uc['x'] - uc['width'] / 2
                ty = uc['y'] - uc['height'] / 2
                rx = uc['x'] + uc['width'] / 2
                by = uc['y'] + uc['height'] / 2
                draw.rectangle([lx, ty, rx, by], outline="red", width=3)
                draw.text((lx, ty - 12), "Floating Use Case", fill="red")

                
            labeled_img_path = filepath.replace(".", "_labeled.")
            img.save(labeled_img_path)

            extracted_results.append({
                "page": page_num,
                "image": filename,
                "labeled_image": os.path.basename(labeled_img_path),
                "title": diagram_titles,
                "actors": actor_texts,
                "useCases": use_case_texts,
                "issues": issues,
                "categorized": actor_to_usecases,
                "includeCount": include_count,
                "extendCount": extend_count
            })
            # --- Categorize use cases under each actor ---
# Step 1: Create (x, y) → label map for actors
        actor_label_map = {}
        for pred in predictions:
            if pred["class"] == "Actor_Text":
                ox, oy = pred["x"], pred["y"]
                label = pytesseract.image_to_string(
                    img.crop((
                        int(ox - pred["width"] / 2),
                        int(oy - pred["height"] / 2),
                        int(ox + pred["width"] / 2),
                        int(oy + pred["height"] / 2)
                    )).convert("L")
                ).strip()
                if label:
                    actor_label_map[(ox, oy)] = label

# Step 2: Map actor labels to use case texts
        

        for (ax, ay), use_cases in connection_results["ownership_map"].items():
    # Find best matching OCR actor label (smallest distance)
            closest = min(actor_label_map.keys(), key=lambda k: (k[0] - ax) ** 2 + (k[1] - ay) ** 2, default=None)
            actor_name = actor_label_map.get(closest, f"Actor@({ax},{ay})")
            actor_to_usecases[actor_name] = []

            for uc in use_cases:
        # Find closest OCR label for the use case
                closest_label = None
                min_dist = float("inf")
                for pred in predictions:
                    if pred["class"] == "Use_Case_Text":
                        dx = pred["x"] - uc["x"]
                        dy = pred["y"] - uc["y"]
                        dist = dx * dx + dy * dy
                        if dist < min_dist:
                            min_dist = dist
                            closest_label = pred

                if closest_label:
                    label_text = pytesseract.image_to_string(
                        img.crop((
                            int(closest_label["x"] - closest_label["width"] / 2),
                            int(closest_label["y"] - closest_label["height"] / 2),
                            int(closest_label["x"] + closest_label["width"] / 2),
                            int(closest_label["y"] + closest_label["height"] / 2)
                        )).convert("L")
                    ).strip()
                    
                    if label_text and label_text not in actor_to_usecases[actor_name]:
                        actor_to_usecases[actor_name].append(label_text)

                    print("Actor–Use Case Mapping:", actor_to_usecases)
    
    return jsonify(extracted_results)


    file = request.files["pdf"]
    pages = request.form.get("pages")
    if not pages:
        return jsonify({"error": "No pages provided"}), 400

    page_numbers = list(map(int, pages.split(",")))
    doc = fitz.open(stream=file.read(), filetype="pdf")
    extracted_results = []

    os.makedirs("extracted_images", exist_ok=True)

    for page_num in page_numbers:
        
        if page_num < 1 or page_num > len(doc):
            continue
        
        page = doc.load_page(page_num - 1)
        images = page.get_images(full=True)
        for idx, img in enumerate(images):
            xref = img[0]
            base_image = doc.extract_image(xref)
            image_bytes = base_image["image"]
            image_ext = base_image["ext"]
            filename = f"page{page_num}_img{idx + 1}.{image_ext}"
            filepath = os.path.join("extracted_images", filename)

            # Save image to disk
            with open(filepath, "wb") as f:
                f.write(image_bytes)

            # Perform OCR using pytesseract
            try:
                img_pil = Image.open(filepath)
                text = pytesseract.image_to_string(img_pil)
            except Exception as e:
                text = f"OCR failed for {filename}: {str(e)}"

            extracted_results.append(
                {"page": page_num, "image": filename, "text": text}
            )

    return jsonify(extracted_results)


@app.route("/extracted_images/<filename>")
def serve_image(filename):
    return send_from_directory("extracted_images", filename)

def build_actor_usecase_mapping(paragraph, actor_list, usecase_list):
    from collections import defaultdict
    import re

    mapping = defaultdict(list)
    sentences = re.split(r'[.?!]\s*', paragraph)

    def normalize(text):
        return re.sub(r'[^a-z0-9]', '', text.lower())

    for sentence in sentences:
        norm_sent = normalize(sentence)
        print(">>> Sentence:", sentence)
        for actor in actor_list:
            actor_norm = normalize(actor)
            if actor_norm in norm_sent:
                print(f"✅ Matched actor: {actor}")
                for usecase in usecase_list:
                    if normalize(usecase) in norm_sent:
                        print(f"    ➕ use case matched: {usecase}")
                        mapping[actor].append(usecase)
    print("Final mapping:", dict(mapping))
    return mapping


@app.route("/compare-diagram-with-text", methods=["POST"])
def compare_with_paragraph():
    import re
    from PyPDF2 import PdfReader
    from difflib import SequenceMatcher

    def normalize_list(lst):
        return set(
            re.sub(r"\s+", " ", x.strip().lower().replace(":", "")).strip() for x in lst
        )

    def extract_actors(paragraph):
        match = re.search(
            r"actors?\s*:\s*(.+?)(?:\.|\Z)", paragraph, flags=re.IGNORECASE | re.DOTALL
        )
        if not match:
            return []
        actor_string = match.group(1)
        actor_string = re.sub(r"\s+and\s+", ", ", actor_string, flags=re.IGNORECASE)
        raw_actors = re.split(r",\s*", actor_string)
        return [
            a.strip().lower() for a in raw_actors if 1 <= len(a.strip().split()) <= 10
        ]

    def clean_usecase_text(text):
        text = re.sub(r"figure\s+\d+.*$", "", text, flags=re.IGNORECASE)
        text = re.sub(r"\s+and\s+", ", ", text, flags=re.IGNORECASE)
        parts = re.split(r",\s*", text)
        cleaned = []
        for p in parts:
            p = p.strip().lower()
            if p and 1 <= len(p.split()) <= 6:
                cleaned.append(p)
        return cleaned

    def check_spelling_grammar(texts, source_label):
        spell = SpellChecker()
        tool = language_tool_python.LanguageTool("en-US")
        issues = []

        for t in texts:
            words = t.split()
            misspelled = spell.unknown(words)
            for word in misspelled:
                suggestion = spell.correction(word)
                issues.append(
                    {
                        "source": source_label,
                        "type": "spelling",
                        "word": word,
                        "suggestion": suggestion,
                    }
                )

            matches = tool.check(t)
            for match in matches:
                issues.append(
                    {
                        "source": source_label,
                        "type": "grammar",
                        "message": match.message,
                        "error": t[match.offset : match.offset + match.errorLength],
                        "suggestion": match.replacements[:2],
                    }
                )

        return issues

    file = request.files["pdf"]
    pages = request.form.get("pages")
    if not pages:
        return jsonify({"error": "No pages specified"}), 400

    doc = PdfReader(file)
    page_numbers = list(map(int, pages.split(",")))
    results = []
    
# 🔁 Step 1: Extract categorized[page][actor][] from form
    categorized_per_page = {}
    for key in request.form:
        if key.startswith("categorized["):
            match = re.match(r"categorized\[(\d+)\]\[(.+?)\]\[\]", key)
            if match:
                page, actor = int(match.group(1)), match.group(2)
                if page not in categorized_per_page:
                    categorized_per_page[page] = {}
                categorized_per_page[page].setdefault(actor, []).append(request.form.get(key))

    for page_num in page_numbers:
        if page_num > len(doc.pages):
            continue

        text = doc.pages[page_num - 1].extract_text()
        match = re.search(
            r"Use Case Diagram\s*[:\-]?\s*\n*(.+?)(\n\n|\Z)",
            text,
            re.DOTALL | re.IGNORECASE,
        )
        paragraph = match.group(1).strip() if match else text.strip()
        


        actor_text = extract_actors(paragraph)
        include_keywords = []
        extend_keywords = []
        usecase_text = []
        

        patterns = [
            r"include(?:s)?:\s*(.+?)(?=(?:the use cases|the actor|figure|\Z))",
            r"include(?:s)?\s+(.+?)(?=(?:\.\s*figure|\.\s*$| figure|\Z))",
            r"(?:use cases for .*?|the .*? use cases).*?consist(?:s)? of\s*(.+?)(?:\.|\n|$)",
            r"(?:use cases for .*?|the .*? use cases).*?have\s*(.+?)(?:\.|\n|$)",
            r"(?:use cases for .*?|the .*? use cases).*?has\s*(.+?)(?:\.|\n|$)",
            r"(?:use cases for .*?|the .*? use cases).*?contain(?:s)?\s*(.+?)(?:\.|\n|$)",
            r"(?:use cases for .*?|the .*? use cases).*?perform(?:s)?\s*(.+?)(?:\.|\n|$)",
            r"(?:use cases for .*?|the .*? use cases).*?includes(?:s)?\s*(.+?)(?:\.|\n|$)",
        ]

        for pattern in patterns:
            matches = re.findall(pattern, paragraph, flags=re.IGNORECASE | re.DOTALL)
            for match in matches:
                cleaned_items = clean_usecase_text(match)
                usecase_text += cleaned_items
        actor_to_usecases_map = build_actor_usecase_mapping(paragraph, actor_text, usecase_text)
        # ➕ Add enhanced sentence-level include/extend detection (e.g. "The X use case includes Y")
        # Support both English and Chinese quotation marks
        def is_probably_list_context(sentence):
            # Returns True if "include" is used just to list use cases for an actor/system
            keywords = ["actor", "actors", "use case", "use cases", "system"]
            before_include = sentence.lower().split("include")[0]
            return any(k in before_include for k in keywords)
        paragraph = paragraph.replace("“", '"').replace("”", '"')
        paragraph = re.sub(r"[“”]", '"', paragraph)
        paragraph = re.sub(r"\s+", " ", paragraph)
        paragraph = re.sub(r"\.(?=\s|$)", "", paragraph)

        quote_chars = r"['\"]" 
        pattern_combo_sent = rf'The\s+{quote_chars}?(.+?){quote_chars}?\s+use\s+case\s+includes\s+{quote_chars}?(.+?){quote_chars}?\s+and\s+extends\s+{quote_chars}?(.+?){quote_chars}?\s*(?:use\s+case)?'
        pattern_include_sent = rf'The\s+{quote_chars}?(.+?){quote_chars}?\s+use\s+case\s+includes\s+{quote_chars}?(.+?){quote_chars}?\s*(?:use\s+case)?'
        pattern_extend_sent = rf'The\s+{quote_chars}?(.+?){quote_chars}?\s+use\s+case\s+extends\s+{quote_chars}?(.+?){quote_chars}?\s*(?:use\s+case)?'

# First handle the combo (includes + extends in one sentence)
        matches = re.findall(pattern_combo_sent, paragraph, flags=re.IGNORECASE)
        for from_uc, include_target, extend_target in matches:
            include_keywords.append((from_uc.strip(), include_target.strip()))
            extend_keywords.append((from_uc.strip(), extend_target.strip()))


# Then extract the clean include ones (they may appear even if combo was matched)
        matches = re.findall(pattern_include_sent, paragraph, flags=re.IGNORECASE)
        for from_uc, to_uc in matches:
            sentence = f'The "{from_uc}" use case includes "{to_uc}"'
            if not is_probably_list_context(sentence):
                include_keywords.append((from_uc.strip(), to_uc.strip()))

# Then extract any clean "X extends Y" (can be totally separate sentences)
        matches = re.findall(pattern_extend_sent, paragraph, flags=re.IGNORECASE)
        for from_uc, to_uc in matches:
            extend_keywords.append((from_uc.strip(), to_uc.strip()))


        diagram_actors = request.form.getlist(f"actors[{page_num}][]")
        diagram_usecases = request.form.getlist(f"useCases[{page_num}][]")

        expected_actor_set = normalize_list(actor_text)
        expected_usecase_set = normalize_list(usecase_text)


        for s in usecase_text:
            if 'include' in s.lower() and not is_probably_list_context(s):
                include_keywords.append(s)
            if 'extend' in s.lower():
                extend_keywords.append(s)

        # 改用长度 >= 1 的判断，只要有 include keyword 就算数
        include_count_from_text = len([pair for pair in include_keywords if isinstance(pair, (tuple, list, str))])
        extend_count_from_text = len([pair for pair in extend_keywords if isinstance(pair, (tuple, list, str))])


        include_count_from_diagram = int(request.form.get(f"includeCount[{page_num}]", 0))
        extend_count_from_diagram = int(request.form.get(f"extendCount[{page_num}]", 0))

        relation_note = ""
        if include_count_from_diagram != include_count_from_text:
            relation_note += f"⚠ Include relationships mentioned in document: {include_count_from_text}, but detected: {include_count_from_diagram} in diagram image.<br/>"
        if extend_count_from_diagram != extend_count_from_text:
            relation_note += f"⚠ Extend relationships mentioned in document: {extend_count_from_text}, but detected: {extend_count_from_diagram} in diagram image."
            
        if not relation_note.strip():
            if include_count_from_text > 0 or extend_count_from_text > 0:
        #all relationship match
                relation_note = " All include/extend relationships between document and diagram match correctly."
            else:
        # nothing mention relationship in document or diagram image
                relation_note = "There is no include or extend relationship found in both the document and the diagram image."
        print(f"[Page {page_num}] Document has {include_count_from_text} include and {extend_count_from_text} extend")
        print(f"[Page {page_num}] Diagram has {include_count_from_diagram} include and {extend_count_from_diagram} extend")
        print(f"[Page {page_num}] Final relation note: {relation_note}")

        ocr_actor_set = normalize_list(diagram_actors)
        ocr_usecase_set = normalize_list(diagram_usecases)

        paragraph_issues = check_spelling_grammar([paragraph], "paragraph")
        ocr_usecase_issues = check_spelling_grammar(diagram_usecases, "diagram")

        results.append(
            {
                "page": page_num,
                "paragraph": paragraph,
                "expected_actors": list(expected_actor_set),
                "expected_usecases": list(expected_usecase_set),
                "ocr_actors": list(ocr_actor_set),
                "ocr_usecases": list(ocr_usecase_set),
                "paragraph_issues": paragraph_issues,
                "ocr_usecase_issues": ocr_usecase_issues,
                "note": relation_note,
                "include_keywords": include_keywords,
                "extend_keywords": extend_keywords,
                "doc_actor_usecase_map": actor_to_usecases_map,
                "categorized": categorized_per_page.get(page_num, {}),
            }
        )

    return jsonify(results)


if __name__ == "__main__":
    app.run(debug=True)
