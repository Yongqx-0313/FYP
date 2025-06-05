

const html = document.documentElement;

window.onload = async function () {
  // ✅ Load model
  await init();

  // ✅ Dark mode toggle
  const darkToggle = document.getElementById('darkModeToggle');
  const settingsPanel = document.getElementById('settings');

  const isDark = localStorage.getItem('darkMode') === 'true';
  if (isDark) {
    document.documentElement.classList.add('dark');
    if (darkToggle) darkToggle.checked = true;
  }

  if (darkToggle) {
    darkToggle.addEventListener('change', () => {
      const isDark = darkToggle.checked;
      localStorage.setItem('darkMode', isDark);
      document.documentElement.classList.toggle('dark', isDark);
    });
  }

  // ✅ Attach PDF onchange listener
  const fileInput = document.getElementById('pdfFile');
  if (fileInput) {
    fileInput.addEventListener('change', handleFileSelect);
  }
};
//👇 Force dark mode to use class only 
tailwind.config = {
      darkMode: 'class'
}