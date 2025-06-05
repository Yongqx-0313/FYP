function clearAll() {
   // Clear file input
   document.getElementById('pdfFile').value = '';

   // Clear preview thumbnails
   document.getElementById('thumbnails').innerHTML = '';

   // Hide split form
   document.getElementById('splitForm').style.display = 'none';

   // Hide sort buttons if you used a container
   const sortBox = document.getElementById('sort-controls');
   if (sortBox) sortBox.style.display = 'none';

   document.getElementById('selected-file-name').innerText = '';
   // Clear page count
   document.getElementById('page-count').innerText = '';

   // Clear messages
   document.getElementById('detected-page-info').innerText = '';
   document.getElementById('loading-text').innerText = 'Processing...';
   document.getElementById('loading-indicator').style.display = 'none';

   // Clear filename input
   const filenameInput = document.getElementById('filename');
   if (filenameInput) filenameInput.value = '';

   // Reset uploaded file reference
   uploadedFile = null;

   document.getElementById('ocr-results').style.display = 'none';
   document.getElementById('compare-result').style.display = 'none';
   document.getElementById('gpt-response').style.display = 'none';

   lastComparisonResults = null;
}

document.getElementById('gptSuggestButton').disabled = true;
