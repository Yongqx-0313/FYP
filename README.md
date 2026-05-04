# 🧠 AI-Driven Use Case Diagram Consistency Checker

This Final Year Project (FYP) is a **web-based tool** that automatically checks the consistency between **use case diagrams** and their corresponding **written requirements** in PDF documents. It uses **OCR**, **rule-based NLP**, and **AI-powered analysis** to identify errors, generate suggestions, and streamline system design verification.

> **By:** Yong Qi Xiang  
> **Supervisors:** A.P. Dr. Lim Tek Yong, A.P. Dr. Chua Fang Fang  
> **FYP ID:** FYP02-SE-T2510-0106

---

## 🚀 Features

- ✅ **PDF Upload & Preview**
- 📄 **Page Splitting & Download**
- 🤖 **AI-Based Use Case Diagram Detection (Teachable Machine)**
- 🔍 **OCR Extraction of Actors, Use Cases, Titles, and Lines**
- 📚 **Requirement Text Analysis using Rule-Based NLP**
- ⚖️ **Diagram vs. Description Comparison**
- 💡 **AI Suggestions via GPT (Puter.js, Deepseek)**
- 📝 **Spelling & Grammar Check**
- 🧾 **HTML Report Generation**
- 🌙 **Dark Mode**
- 🎓 **Interactive Onboarding Tutorial (Intro.js)**
- 💬 **UML FAQ Chatbot with PlantUML Support (Gradio + Ollama)**

---

## 🧩 Tech Stack

| Layer      | Tools Used |
|------------|------------|
| **Frontend** | HTML, Tailwind CSS, JavaScript, FontAwesome, Intro.js |
| **Backend**  | Python (Flask), PHP |
| **OCR**      | PyTesseract, Tesseract.js |
| **AI Logic** | Puter.js (GPT-4o), Deepseek (Ollama), Gradio |
| **Diagram Detection** | Google Teachable Machine, TensorFlow.js |
| **Image Processing** | Roboflow, OpenCV |
| **Spell Check** | language_tool_python |
| **Database**  | MySQL (via PHP) |
| **Diagram Code** | PlantUML |

---

## 📁 Folder Structure

```
📦 root/
├── splitpdf.py          # Flask backend for PDF splitting
├── app.py               # AI chatbot and UML support (Gradio)
├── run_both.py          # Launcher for Flask + Gradio
├── detectUCD.js         # Detect diagram pages using Teachable Machine
├── OCR.js               # OCR extraction logic with labeling & issue detection
├── compareDoc.js        # Requirement comparison logic
├── splitpdf.html        # Main frontend UI
└── static/              # CSS, icons, images
```

---

## ⚙️ How It Works

1. **Upload PDF** → Preview pages
2. **Detect Diagrams** → AI scans for pages with use case diagrams
3. **OCR Extraction** → Pulls actors, use cases, system names, include/extend lines
4. **Compare with Description** → Matches diagram content with document text
5. **AI Suggestion** → GPT checks for logic errors (e.g., "Admin can Cook Food")
6. **Download Report** → Shows all detected issues, categorized mappings, and recommendations

---

## 🧪 Testing Strategy

- ✅ Unit Testing: File management, login, consistency logic
- 🔁 Integration Testing: OCR ↔ NLP ↔ Frontend
- 👨‍💻 Usability Testing: Interface walkthrough with users
- ✅ Acceptance Testing: Confirmed system meets functional specs

---

## 📈 Commercialization Potential

- 🎓 **Target Users**: Students, lecturers, software companies
- 💰 **Model**: Freemium for students + Subscription for institutions
- 🏆 **Competitive Advantage**:
  - Detects mismatches between use case diagrams and written requirements
  - Highlights missing actors/use cases, misplaced lines, or floating elements
  - Generates AI-driven suggestions and spellcheck reports

---

## 📊 SWOT Summary

| Strengths                     | Weaknesses                    |
|------------------------------|-------------------------------|
| Combines OCR + NLP + AI      | Limited to use case diagrams  |
| Smart rule-based checking    | Solo-developed project        |
| Rare functionality in market | Academic time constraints     |

| Opportunities                | Threats                        |
|-----------------------------|-------------------------------|
| Expand to sequence diagrams | Rising AI competitors          |
| University licensing         | High expectations of accuracy |

---

## 🧠 FAQ (Chatbot Supported)

Ask questions like:
- What is a floating use case?
- How does the include relationship work?
- What does a valid use case diagram look like?

🌐 Powered by Gradio + Ollama (Deepseek model)  
🎨 Renders PlantUML diagrams on demand

---

## 📄 License

This project is for academic purposes and may be adapted for commercial use with permission.

---

## 🏁 How to Run/ Setup Instruction

1. Set up Python environment with `Flask`, `Gradio`, `pytesseract`, `language_tool_python`, etc.
2. Run:
   ```bash
   python run_both.py
   ```
3. Open browser at:
   - `http://127.0.0.1:5000` for PDF/OCR frontend
   - `http://127.0.0.1:7860` for chatbot (iframe loaded automatically)

---

## 🔒 Security Notes

- All uploaded files are processed locally on the server and are not shared externally.
- Error handling is implemented on both frontend and backend (try–catch blocks, fallback messages).
- The chatbot is sandboxed via iframe and runs on `localhost` to prevent data leaks.
- User-uploaded data is scoped by session/folder IDs to prevent cross-access.
- No third-party authentication or external database sharing is enabled.
- Files are not stored persistently unless exported manually by the user.

## 🙌 Acknowledgements

- Roboflow, OpenAI, LanguageTool, PlantUML, Teachable Machine
- Based on UML standards from [OMG](https://www.uml-diagrams.org/)
- Educational resources: GeeksforGeeks, W3Schools

---

## 🔮 Future Enhancements

- ✅ Expand support for other UML diagram types (e.g., Sequence, Class diagrams)
- 🌐 Deploy as a SaaS product with real user login and cloud storage
- 🧠 Improve AI reasoning using fine-tuned language models (e.g., Gemini, Claude)
- 📐 Visual editor for correcting detected diagrams directly on canvas
- 💡 Real-time chat assistant trained on software design principles
- 📊 Analytics dashboard to track common diagram issues and suggestion history
- 🔁 Integration with third-party tools like GitHub, Draw.io, yEd

> ⚠️ Disclaimer: This tool is an aid, not a replacement for human judgment. Always manually verify results.

## 👨‍💻 Developer

Yong Qi Xiang Bachelor of Computer Science (Hons) Software Engineering Faculty of Computing and Informatics, Multimedia University

## Dependecies Maintenance & Update

npm audit fix
