async function extractImagesWithOCR() {
   const fileInput = document.getElementById('pdfFile');
   const file = fileInput.files[0];
   if (!file) return alert("Please upload a PDF.");

   const enableLineCheck = document.querySelector('input[name="enableLineCheck"]').checked;

   const detectedText = document.getElementById('detected-page-info').innerText;
   const match = detectedText.match(/page\(s\): ([\d, ]+)/);
   if (!match) return alert("Please detect Use Case Diagram first.");

   const loading = document.getElementById('loading-indicator');
   const loadingText = document.getElementById('loading-text');
   loadingText.innerHTML = "<strong>Extracting images and running OCR...</strong>";
   loading.style.display = 'block';
   startEstimatedTimer(loadingText, "Extracting images and running OCR");
   await new Promise(r => setTimeout(r, 100));

   const pages = match[1].replace(/\s+/g, '');
   const formData = new FormData();
   formData.append('pdf', file);
   formData.append('pages', pages);
   formData.append('enableLineCheck', enableLineCheck);

   try {
      const res = await fetch('/extract-images-ocr', {
         method: 'POST',
         body: formData
      });

      if (!res.ok) {
         const errorText = await res.text();
         throw new Error(`Server error: ${res.status}\n${errorText}`);
      }

      const results = await res.json();
      const container = document.getElementById('ocr-results');
      container.style.display = 'block';
      container.innerHTML = '';

      results.forEach(result => {
         const page = result.page;
         ocrResultsByPage[page] = {
            actors: result.actors || [],
            useCases: result.useCases || [],
            categorized: result.categorized || {},
            titles: result.title || [],
            includeCount: result.includeCount ?? 0,
            extendCount: result.extendCount ?? 0
         };

         console.log("📄 OCR result for page", page, ocrResultsByPage[page]);

         const contentId = `content-${page}`;
         const arrowId = `arrow-${page}`;
         let html = `
            <div class="p-4 bg-white rounded shadow mb-4 dark:bg-black">
              <div class="flex justify-between"> 
                <div class="dark:text-white"> Page ${result.page} • 🖼️ ${result.image} </div>
                <button onclick="toggleSection('${contentId}', '${arrowId}')" class="flex items-center gap-2 text-left font-semibold text-gray-700">
                  <span id="${arrowId}" class="transform rotate-180 transition-transform"><i class="fa-solid fa-angle-down dark:text-white"></i></span>
                </button>
              </div>
              <div id="${contentId}" class="mt-2">
                <p class="dark:text-white"><strong>System Name:</strong> ${result.title || '-'}</p>
                <p class="dark:text-white"><strong>Actor:</strong> ${result.actors?.join(', ') || '-'}</p>
                <p class="dark:text-white"><strong>Use Case:</strong> ${result.useCases?.join(', ') || '-'}</p>
                <p class="dark:text-white"><strong>Include Label Count:</strong> ${result.includeCount ?? 0}</p>
                <p class="dark:text-white"><strong>Extend Label Count:</strong> ${result.extendCount ?? 0}</p>`;

         if (result.issues && result.issues.length > 0) {
            html += `<div class="mt-2 text-red-600"><strong>🔴 Issues Detected:</strong><ul>`;
            result.issues.forEach(issue => {
               html += `<li>
                     • <strong>${issue.label}</strong>: ${issue.reason}(confidence: ${issue.confidence.toFixed(2)})
                     ${issue.suggestion ? `<br/><span class="text-green-700"><b>Suggestion:</b>${issue.suggestion}</span>` : ''}
                  </li>`;
            });
            html += `</ul></div>`;
         }

         if (result.labeled_image) {
            html += `<img src="/extracted_images/${result.labeled_image}" class="mt-3 rounded border shadow" width="300">`;
         }
console.log("Actor–Use Case Mapping (categorized):", result.categorized);

if (result.categorized && Object.keys(result.categorized).length > 0) {
   html += `<div class="mt-3"><strong class="dark:text-white">🎯 Actor–Use Case Mapping:</strong><ul>`;
   for (const [actor, usecases] of Object.entries(result.categorized)) {
      html += `<li class="dark:text-white"><strong>${actor}</strong> → ${usecases.join(', ') || '-'}</li>`;
   }
   html += `</ul></div>`;
} else {
   html += `<div class="mt-3 text-sm text-gray-500 italic">No actor–use case mapping found or the enable line detection function did not open and allow.</div>`;
}


         html += `</div></div>`;
         container.innerHTML += html;
      });

      stopEstimatedTimer(loadingText, "✅ OCR completed successfully.");
   } catch (err) {
      loadingText.innerHTML = "<span class='text-red-600'>❌ OCR failed.</span>";
      console.error(err);
      stopEstimatedTimer(loadingText, "❌ Failed");
   } finally {
      setTimeout(() => loading.style.display = 'none', 1500);
   }
}
