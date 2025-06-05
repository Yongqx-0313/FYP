let smartTimerId = null;
let smartTotalElapsed = 0;
let smartCountdown = 16;
let smartPhases = [16, 30, 60, 80];
let smartPhaseIndex = 0;

function startEstimatedTimer(loadingTextEl, baseText = "Processing") {
   if (smartTimerId) clearInterval(smartTimerId);
   smartTotalElapsed = 0;
   smartCountdown = smartPhases[0];
   smartPhaseIndex = 0;

   smartTimerId = setInterval(() => {
      smartTotalElapsed++;
      loadingTextEl.innerHTML = `<strong>${baseText}</strong><br><em>Estimated time: ${smartCountdown} seconds</em>`;
      smartCountdown--;

      if (smartCountdown < 0) {
         smartPhaseIndex++;
         if (smartPhaseIndex < smartPhases.length) {
            smartCountdown = smartPhases[smartPhaseIndex];
         } else {
            smartCountdown = smartPhases[smartPhases.length - 1];
         }
      }
   }, 1000);
}

function stopEstimatedTimer(loadingTextEl, message = "✅ Done") {
   if (smartTimerId) clearInterval(smartTimerId);
   smartTimerId = null;
   loadingTextEl.innerHTML = `<span class='text-green-600 font-semibold'>${message} (Took ${smartTotalElapsed} seconds)</span>`;
}