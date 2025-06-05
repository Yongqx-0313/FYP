async function detectUseCasePages() {
   if (!model) return alert("Model still loading, try again in a moment.");
   const loading = document.getElementById('loading-indicator');
   const loadingText = document.getElementById('loading-text');
   loadingText.innerHTML = "<strong>Detecting diagrams...</strong>";
   loading.style.display = 'block';
   startEstimatedTimer(loadingText, "Detecting diagrams");
   await new Promise(r => setTimeout(r, 100));

   const pages = document.querySelectorAll('#thumbnails .bg-white');
   if (!pages.length) {
      document.getElementById('loading-indicator').style.display = 'none'; // 👈 hide the spinner
      return alert("Please preview PDF first.");
   }

   const predictions = [];
   for (let i = 0; i < pages.length; i++) {
      const div = pages[i];
      const img = div.querySelector("img");
      const prediction = await model.predict(img);
      const topPrediction = prediction[0];
      if (topPrediction.probability > 0.85) {
         predictions.push(i + 1);
         div.classList.add("ring", "ring-green-400");
         div.style.display = "block";
      } else {
         div.style.display = "none";
      }
   }

   stopEstimatedTimer(loadingText, "✅ Diagram detection complete.");
   setTimeout(() => loading.style.display = 'none', 1500);

   const info = document.getElementById('detected-page-info');
   info.innerText = predictions.length ?
      `✅ Use Case Diagram detected on page(s): ${predictions.join(', ')}` :
      "❌ No Use Case Diagram detected.";
}