let uploadedFile;
async function uploadPDF() {
   const fileInput = document.getElementById('pdfFile');
   const file = fileInput.files[0];
   if (!file) return alert("Please upload a PDF");

   uploadedFile = file;

   const loading = document.getElementById('loading-indicator');
   const loadingText = document.getElementById('loading-text');
   loadingText.innerHTML = "<strong>Uploading PDF and generating preview...</strong>";
   loading.style.display = 'block';
   startEstimatedTimer(loadingText, "Uploading PDF and generating preview");
   await new Promise(resolve => setTimeout(resolve, 100));

   const formData = new FormData();
   formData.append('pdf', file);

   try {
      const res = await fetch('/preview', {
         method: 'POST',
         body: formData
      });
      const pages = await res.json();
      const container = document.getElementById('thumbnails');
      container.innerHTML = '';

      pages.forEach(p => {
         container.innerHTML += `
            <div class="bg-white rounded shadow p-2 text-center">
              <img src="${p.img}" class="rounded border" />
              <label class="block mt-2 dark:text-black"><input type="checkbox" value="${p.page}" /> Page ${p.page}</label>
            </div>`;
      });

      document.getElementById('page-count').innerText = `📄 Total pages in your uploaded PDF: ${pages.length}`;
      document.getElementById('splitForm').style.display = 'block';
      document.getElementById('sort-controls').style.display = 'flex';

      loadingText.innerHTML = "<span class='text-green-600 font-semibold'>✅ PDF uploaded and previewed successfully.</span>";
   } catch (err) {
      loadingText.innerHTML = "<span class='text-red-600'>❌ Failed to preview PDF.</span>";
      console.error(err);
   } finally {
      setTimeout(() => {
         loading.style.display = 'none';
      }, 1500);
   }
}