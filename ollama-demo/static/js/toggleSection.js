function toggleSection(contentId, arrowId) {
   const content = document.getElementById(contentId);
   const arrow = document.getElementById(arrowId);

   content.classList.toggle('hidden');
   arrow.classList.toggle('rotate-180');
   arrow.classList.toggle('rotate-0');
}

const chatWidget = document.getElementById("chatbot-widget");
  chatWidget.style.display = "none";

  function toggleChat() {
    if (chatWidget.style.display === "none") {
      chatWidget.style.display = "block";
    } else {
      chatWidget.style.display = "none";
    }
  }
  function toggleSetting() {
    const settingsPanel = document.getElementById('settings');
    settingsPanel.classList.toggle('hidden');
  }

  function toggleDisclaimer() {
    const extra = document.getElementById('disclaimer-extra');
    const btn = document.getElementById('disclaimer-toggle-btn');

    const isHidden = extra.classList.contains('hidden');
    if (isHidden) {
      extra.classList.remove('hidden');
      btn.innerText = 'See Less';
    } else {
      extra.classList.add('hidden');
      btn.innerText = 'See More';
    }
  }
  function updateDisclaimer() {
    const mode = document.getElementById("disclaimerMode").value;
    const summary = document.getElementById("disclaimer-summary");
    const detailed = document.getElementById("disclaimer-detailed");

    if (mode === "summary") {
      summary.classList.remove("hidden");
      detailed.classList.add("hidden");
    } else {
      summary.classList.add("hidden");
      detailed.classList.remove("hidden");
    }
  }

document.addEventListener('keydown', (e) => {
  if (e.key === 'S') {
    // Toggle settings button + panel
    const settingsBtn = document.getElementById('toggleSettingsBtn');
    const settingsPanel = document.getElementById('settings');

    const visible = !settingsBtn.classList.contains('hidden');
    settingsBtn.classList.toggle('hidden', visible);
    settingsPanel.classList.add('hidden'); // always hide panel when hiding button
  }

  if (e.key === 'C') {
    // Toggle chat button + panel
    const chatBtn = document.getElementById('toggleChatBtn');
    const chatPanel = document.getElementById('chatbot-widget');

    const visible = !chatBtn.classList.contains('hidden');
    chatBtn.classList.toggle('hidden', visible);
    chatPanel.style.display = 'none'; // always hide panel when hiding button
  }

  if (e.key === 'Escape') {
    // Hide both
    document.getElementById('toggleSettingsBtn')?.classList.add('hidden');
    document.getElementById('toggleChatBtn')?.classList.add('hidden');
    document.getElementById('settings')?.classList.add('hidden');
    document.getElementById('chatbot-widget').style.display = 'none';
  }
});
