import subprocess
import time
import os

# Specify the project directory where Flask and Gradio scripts are located
project_dir = os.path.join(os.getcwd(), "ollama-demo")

# ✅ Start Flask app (splitpdf.py)
flask_process = subprocess.Popen(
    ["python", "splitpdf.py"],
    cwd=project_dir  # Change working directory
)

# Wait a bit to let Flask start before launching Gradio (app.py)
time.sleep(2)

# ✅ Start Gradio app (app.py)
gradio_process = subprocess.Popen(
    ["python", "app.py"],
    cwd=project_dir
)

try:
    flask_process.wait()
    gradio_process.wait()
except KeyboardInterrupt:
    print("Shutting down...")
    flask_process.terminate()
    gradio_process.terminate()
