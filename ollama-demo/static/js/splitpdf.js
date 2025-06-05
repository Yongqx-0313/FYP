let model, maxPredictions;
const modelPath = "/static/tm_model/";
let ocrResultsByPage = {};

// Auto-attach onchange listener AFTER DOM loads
window.addEventListener('DOMContentLoaded', () => {
   const fileInput = document.getElementById('pdfFile');
   fileInput.addEventListener('change', handleFileSelect);
});

function handleFileSelect(event) {
   const files = event.target.files;
   const fileNameEl = document.getElementById('selected-file-name');

   if (files && files.length > 0) {
      uploadedFile = files[0];
      fileNameEl.textContent = `Selected file: ${uploadedFile.name}`;
      // OPTIONAL: auto-upload once selected
      // uploadPDF();
   } else {
      uploadedFile = null;
      fileNameEl.textContent = '';
   }
}

function handleDrop(event) {
   event.preventDefault();
   const fileInput = document.getElementById('pdfFile');
   fileInput.files = event.dataTransfer.files;

   // Manually trigger 'change' since it's programmatic
   const changeEvent = new Event('change');
   fileInput.dispatchEvent(changeEvent);
}

async function init() {
   const modelURL = modelPath + "model.json";
   const metadataURL = modelPath + "metadata.json";
   model = await tmImage.load(modelURL, metadataURL);
   maxPredictions = model.getTotalClasses();
   console.log("Model loaded with", maxPredictions, "classes");
}

window.onload = () => init();

async function splitPDF(e) {
   e.preventDefault();
   const checkboxes = document.querySelectorAll('#thumbnails input[type=checkbox]:checked');
   const selected = Array.from(checkboxes).map(cb => cb.value);
   if (!selected.length) return alert("Please select at least one page.");

   // show spinner…
   const loading = document.getElementById('loading-indicator');
   const loadingText = document.getElementById('loading-text');
   loadingText.innerHTML = "<strong>Splitting PDF...</strong>";
   loading.style.display = 'block';
   await new Promise(r => setTimeout(r, 100));

   // fetch the blob
   const formData = new FormData();
   formData.append('pdf', uploadedFile);
   formData.append('pages', selected.join(','));
   let blob;
   try {
      const res = await fetch('/split', {
         method: 'POST',
         body: formData
      });
      blob = await res.blob();
   } catch (err) {
      loadingText.innerHTML = "<span class='text-red-600'>❌ Failed to split PDF.</span>";
      console.error(err);
      setTimeout(() => loading.style.display = 'none', 1500);
      return;
   }

   // Decide suggested filename
   const inputName = document.getElementById('filename')?.value.trim() || 'selected_pages';
   const suggestedName = inputName.toLowerCase().endsWith('.pdf') ?
      inputName :
      inputName + '.pdf';

   // If File System Access API is available…
   if (window.showSaveFilePicker) {
      try {
         const handle = await window.showSaveFilePicker({
            suggestedName,
            types: [{
               description: 'PDF Document',
               accept: {
                  'application/pdf': ['.pdf']
               }
            }]
         });
         const writable = await handle.createWritable();
         await writable.write(blob);
         await writable.close();
         loadingText.innerHTML = "<span class='text-green-600'>✅ File saved!</span>";
      } catch (err) {
         // user cancelled or error
         loadingText.innerHTML = "<span class='text-yellow-600'>⚠️ Save cancelled.</span>";
         console.error(err);
      }
   } else {
      // Fallback: classic download
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = suggestedName;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      loadingText.innerHTML = "<span class='text-green-600'>✅ Download started…</span>";
   }

   // hide spinner after a bit
   setTimeout(() => loading.style.display = 'none', 1500);
}

