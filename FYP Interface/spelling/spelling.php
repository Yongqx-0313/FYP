<script type="text/javascript" src="JavaScriptSpellCheck/include.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>

<script>
  const highlights = []; // Store highlights for the report

  // Function to highlight spelling errors
  function highlightErrors() {
    const textArea = document.getElementById("myTextArea");
    const inputText = textArea.value;
    const words = inputText.split(/\s+/);
    const container = document.getElementById("highlightContainer");

    // Clear previous highlights
    container.innerHTML = "";
    highlights.length = 0;

    words.forEach((word) => {
      // Perform spell-check and get suggestions
      const suggestions = $Spelling.SpellCheckSuggest(word);

      if (Array.isArray(suggestions) && suggestions.length > 0) {
        const errorSpan = document.createElement("span");
        errorSpan.textContent = word;
        errorSpan.style.color = "red";
        errorSpan.style.cursor = "pointer";
        errorSpan.style.textDecoration = "underline";

        // Add hover effect to show suggestions
        errorSpan.title = `Suggestions: ${suggestions.join(", ")}`;
        errorSpan.onmouseenter = () => {
          errorSpan.style.backgroundColor = "#f8d7da";
        };
        errorSpan.onmouseleave = () => {
          errorSpan.style.backgroundColor = "transparent";
        };

        container.appendChild(errorSpan);
        container.appendChild(document.createTextNode(" "));

        // Add to highlights array
        highlights.push({
          word,
          suggestions: suggestions.join(", "),
        });
      } else {
        container.appendChild(document.createTextNode(word + " "));
      }
    });
  }

  // Function to save spelling report
  function saveReport() {
    let reportContent = "Spelling Report\n\n";
    highlights.forEach((item, index) => {
      reportContent += `${index + 1}. Misspelled Word: ${item.word}\n`;
      reportContent += `   Suggestions: ${item.suggestions}\n\n`;
    });

    const blob = new Blob([reportContent], {
      type: "text/plain"
    });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "Spelling_Report.txt";
    a.click();
    URL.revokeObjectURL(url);
  }

  // Function to extract text from uploaded file
  function extractText(event) {
    const file = event.target.files[0];
    if (!file) return;

    const textArea = document.getElementById("myTextArea");

    if (file.type === "application/pdf") {
      extractTextFromPDF(file).then((text) => {
        textArea.value = text;
      });
    } else if (file.type === "text/plain") {
      extractTextFromTextFile(file);
    } else if (file.type === "application/vnd.openxmlformats-officedocument.wordprocessingml.document") {
      extractTextFromDOCX(file).then((text) => {
        textArea.value = text;
      });
    } else {
      alert("Unsupported file type. Please upload a PDF, DOCX, or TXT file.");
    }
  }

  // Function to extract text from PDF
  async function extractTextFromPDF(file) {
    const reader = new FileReader();
    return new Promise((resolve) => {
      reader.onload = async function() {
        const typedarray = new Uint8Array(reader.result);
        const pdf = await pdfjsLib.getDocument(typedarray).promise;
        let text = "";

        for (let i = 1; i <= pdf.numPages; i++) {
          const page = await pdf.getPage(i);
          const textContent = await page.getTextContent();
          text += textContent.items.map((item) => item.str).join(" ") + "\n";
        }
        resolve(text);
      };
      reader.readAsArrayBuffer(file);
    });
  }

  // Function to extract text from DOCX
  async function extractTextFromDOCX(file) {
    return new Promise((resolve) => {
      const reader = new FileReader();
      reader.onload = function(event) {
        const arrayBuffer = reader.result;
        mammoth
          .extractRawText({
            arrayBuffer: arrayBuffer
          })
          .then(function(result) {
            resolve(result.value); // Extracted text
          })
          .catch(function(err) {
            console.error("Error extracting DOCX text:", err);
            resolve("Error extracting text from DOCX file.");
          });
      };
      reader.readAsArrayBuffer(file);
    });
  }

  // Function to extract text from TXT file
  function extractTextFromTextFile(file) {
    const reader = new FileReader();
    reader.onload = function() {
      document.getElementById("myTextArea").value = reader.result;
    };
    reader.readAsText(file);
  }
</script>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link href="../../src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="../../src/main.css">
    <link rel="stylesheet" href="../../src/file.css">
</head>

<body class="bg-gray-100 min-h-screen">
    
    <?php include 'header.php'; ?>
<div class="flex justify-center items-center py-10">
    <div class="bg-white shadow-lg rounded-lg p-6 w-full max-w-lg ">
        <input 
            type="file" 
            id="fileInput" 
            accept=".pdf, .txt, .docx" 
            onchange="extractText(event)"
            class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        
        <textarea 
            id="myTextArea" 
            class="w-full mt-4 p-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500]" 
            placeholder="Extracted text will appear here..."
        readonly></textarea>

        <div class="flex gap-4 mt-4 justify-between">
            <button 
                onclick="highlightErrors()" 
                class="bg-red-500 mr-4 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-md ">
                Highlight Errors
            </button>
            <button 
                onclick="saveReport()" 
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md ">
                Save Report
            </button>
        </div>

        <div 
            id="highlightContainer" 
            class="w-full mt-4 p-3 border rounded-md bg-gray-50 min-h-[100px] text-gray-700 whitespace-pre-wrap">
        </div>
    </div>
    
   
    </div>
</body>
 <?php include 'footer.php'; ?>
</html>