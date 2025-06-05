let lastComparisonResults = null;

async function compareWithRequirement() {
   const fileInput = document.getElementById('pdfFile');
   const file = fileInput.files[0];
   if (!file) return alert("Please upload a PDF first.");

   const detectedText = document.getElementById('detected-page-info').innerText;
   const match = detectedText.match(/page\(s\): ([\d, ]+)/);
   if (!match) return alert("Please detect Use Case Diagram first.");

   const loading = document.getElementById('loading-indicator');
   const loadingText = document.getElementById('loading-text');
   loadingText.innerHTML = "<strong>Comparing diagram with paragraph description...</strong>";
   loading.style.display = 'block';
   startEstimatedTimer(loadingText, "Comparing diagram with paragraph description");
   await new Promise(r => setTimeout(r, 100));

   const pages = match[1].replace(/\s+/g, '').split(',').map(Number);
   const formData = new FormData();
   formData.append('pdf', file);
   formData.append('pages', pages.join(','));

   // 👇 Add cached diagram results per page
   pages.forEach(page => {
      const data = ocrResultsByPage[page];
      if (data) {
         (data.actors || []).forEach(a => formData.append(`actors[${page}][]`, a));
         formData.append(`includeCount[${page}]`, data.includeCount || 0);
         formData.append(`extendCount[${page}]`, data.extendCount || 0);
         (data.useCases || []).forEach(u => formData.append(`useCases[${page}][]`, u));
         (data.titles || []).forEach(t => formData.append(`titles[${page}][]`, t));
      }
      if (data.categorized) {
  for (const [actor, useCases] of Object.entries(data.categorized)) {
    useCases.forEach(uc => {
      formData.append(`categorized[${page}][${actor}][]`, uc);
    });
  }
}

   });

   try{
   const res = await fetch('/compare-diagram-with-text', {
      method: 'POST',
      body: formData
   });
   const results = await res.json();
   console.log(results);
   lastComparisonResults = results;
   document.getElementById('gptSuggestButton').disabled = false;


   const box = document.getElementById('compare-result');
   box.style.display = 'block';
   document.getElementById('compare-result').innerText = JSON.stringify(results, null, 2);
   
   box.innerHTML = results.map((r, index) => {
      let html = '';

      const normalize = arr =>
         new Set((arr || []).map(x =>
            x.toLowerCase().replace(/\s+/g, ' ').replace(/[^a-z0-9 ]/gi, '').trim()
         ));

      const compareList = (ocrList, expectedList) => {
         const ocrSet = normalize(ocrList);
         const expectedSet = normalize(expectedList);
         return {
            missing: [...expectedSet].filter(x => !ocrSet.has(x)),
            extra: [...ocrSet].filter(x => !expectedSet.has(x))
         };
      };

      const actorCompare = compareList(r.ocr_actors, r.expected_actors);
      const useCaseCompare = compareList(r.ocr_usecases, r.expected_usecases);
      let relationNote = '';
      if (r.note && r.note.trim()) {
         if (r.note.startsWith('✅')) {
      relationNote = `<p class="text-green-600 font-semibold mt-2">${r.note}</p>`;
   } else {
      relationNote = `<p class="text-yellow-600 font-semibold mt-2">${r.note}</p>`;
   }
      }
      console.log(relationNote);


      const renderComparison = (missing, extra, label = 'Item') => {
         let comparisonHtml = '';
         missing.forEach(m => {
            comparisonHtml += `<p class="text-red-600">🔴 <strong>${label} missing from diagram image:</strong> ${m}</p>`;
         });
         extra.forEach(e => {
            comparisonHtml += `<p class="text-blue-600">🔵 <strong>${label} missing from document text:</strong> ${e}</p>`;
         });
         return comparisonHtml;
      };
      // Unique IDs for toggle
      const contentId = `compare-content-${index}`;
      const arrowId = `compare-arrow-${index}`;
      //html += `
      //<div class="p-4 bg-gray-100 rounded shadow mb-6">
      //   <p class="font-semibold text-lg">📄 Page ${r.page}</p>
      // `;
      html += `
    <div class="p-4 bg-gray-100 rounded shadow mb-6 dark:bg-black">
      <div class="flex justify-between">
        <div><p class="font-semibold text-lg dark:text-white">📄 Page ${r.page} -Diagram Issue Summary</p></div>
        <button onclick="toggleSection('${contentId}', '${arrowId}')" class="flex items-center gap-2 font-semibold text-gray-700">
          <span id="${arrowId}" class="transform rotate-180 transition-transform"><i class="fa-solid fa-angle-down dark:text-white"></i></span>
        </button>
      </div>
      <div id="${contentId}" class="mt-3">
  `;

      //   // ✅ Add this outside the html string
      //   if (r.paragraph_issues?.length) {
      //      html += `<div class="mt-3 text-red-700"><strong>📝 Paragraph Spelling & Grammar Issues:</strong><ul>`;
      //      r.paragraph_issues.forEach(issue => {
      //         if (issue.type === 'spelling') {
      //           html += `<li>• Misspelled word: <em class="text-blue-800">${issue.word}</em> → <strong>${issue.suggestion}</strong></li>`;
      //         } else {
      //           html += `<li>• Grammar: <em class="text-blue-800">${issue.error}</em> – ${issue.message} 
      //           ${
      //             Array.isArray(issue.suggestion)
      //               ? `(e.g., ${issue.suggestion.join(', ')})`
      //               : issue.suggestion
      //               ? `(e.g., ${issue.suggestion})`
      //               : ''
      //             }
      //         </li>`;
      //         }
      //      });
      //      html += `</ul></div>`;
      //   }

const ignoreUpperCase = document.getElementById("UpperCaseToggle").checked;

const ocrIssues = (r.ocr_usecase_issues || []).filter(issue => {
   if (!ignoreUpperCase) return true;

   // Filter out only capitalization-related grammar errors
   if (issue.type === 'grammar') {
      const isCapitalization = issue.message.toLowerCase().includes("does not start with an uppercase letter");
      const isSuggestionCapitalized = issue.suggestion?.some(s =>
         s[0] === s[0].toUpperCase() && s.slice(1) === s.slice(1).toLowerCase()
      );

      return !(isCapitalization && isSuggestionCapitalized); // ❌ skip if it's just capitalization
   }

   return true; // keep all other issues
});

if (ocrIssues.length) {
   html += `<section class="border border-gray-600 rounded p-4 mt-6"><div class="mt-3 text-red-600 text-lg"><span class="font-bold underline">Spelling and Grammar Issues in OCR Use Cases Diagram Image:</span><ul>`;
   ocrIssues.forEach(issue => {
      if (issue.type === 'spelling') {
         html += `<li class="text-gray-900 dark:text-white"><strong>• Misspelled: </strong><em class="text-blue-700">${issue.word}</em> → <span class="text-gray-700 dark:text-whit">${issue.suggestion}</span></li>`;
      } else {
         html += `<li class="text-gray-900 dark:text-white"><strong>• Grammar: </strong><em class="text-indigo-700">${issue.error}</em> – ${issue.message} <span class="text-gray-700 dark:text-whit">${issue.suggestion?.length ? `(e.g., ${issue.suggestion.join(', ')})` : ''}</span></li>`;
      }
   });
   html += `</ul></div></section>`;

   if (ignoreUpperCase) {
      html += `<p class="text-pink-500 text-sm italic dark:text-yellow-400">Uppercase-only grammar suggestions were ignored.</p>`;
   }
}


      let paragraphHtml = r.paragraph;
      const ignoreWhitespace = document.getElementById("WhitespaceToggle").checked;

// Filter paragraph issues if needed
const paragraphIssues = (r.paragraph_issues || []).filter(issue => {
   if (!ignoreWhitespace) return true;

   const isWhitespaceIssue =
   issue.type === 'grammar' &&
   /consecutive\s+spaces|many\s+spaces|whitespace|extra\s+space|double\s+space/i.test(issue.message);
   //console.log("Issue message:", issue.message);

   return !isWhitespaceIssue;
});

if (paragraphIssues.length) {
   paragraphIssues.forEach(issue => {
      const errorText = issue.type === 'spelling' ? issue.word : issue.error;
      const suggestionList = Array.isArray(issue.suggestion) ? issue.suggestion :
                              issue.suggestion ? [issue.suggestion] : [];

      const tooltip = `Type: ${issue.type.toUpperCase()} – ${issue.message} (e.g., ${suggestionList.join(', ') || 'No suggestion'})`;
      const escapedText = errorText.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      const regex = new RegExp(`\\b${escapedText}\\b`, 'gi');

      paragraphHtml = paragraphHtml.replace(regex, match => {
         return `<span class="underline decoration-wavy decoration-red-500 cursor-help" title="${tooltip}">${match}</span>`;
      });
   });

   if (ignoreWhitespace) {
      paragraphHtml += `<p class="text-pink-500 text-sm italic dark:text-yellow-400">Whitespace-related grammar issues were ignored.</p>`;
   }
}
let actorSummary = '';
if (actorCompare.missing.length === 0 && actorCompare.extra.length === 0) {
   actorSummary = `<p class="text-green-500 font-semibold mt-2">✅ All actors match between document and diagram.</p>`;
} else {
   actorSummary = `<p class="text-yellow-500 font-semibold mt-2">⚠ Conflict found in actor list.</p>`;
}
let useCaseSummary = '';
if (useCaseCompare.missing.length === 0 && useCaseCompare.extra.length === 0) {
   useCaseSummary = `<p class="text-green-500 font-semibold mt-2">✅ All use cases match between document and diagram.</p>`;
} else {
   useCaseSummary = `<p class="text-yellow-500 font-semibold mt-2">⚠ Conflict found in use case list.</p>`;
}

console.log(r.categorized);
      // Continue HTML rendering
      html += `
      <section class="border border-gray-600 rounded p-4 mt-6">
      <h3 class="text-lg font-bold text-blue-600 underline">Actor Comparison</h3>
      <p class="mt-2 dark:text-white"><strong>Actors (From Diagram Image):</strong> ${r.ocr_actors.join(', ') || '-'}</p>
      <p class="dark:text-white"><strong>Actors (From Document Text):</strong> ${r.expected_actors.join(', ') || '-'}</p>
      ${renderComparison(actorCompare.missing, actorCompare.extra, 'Actor')}
      ${actorSummary}</section>
      

      <section class="border border-gray-600 rounded p-4 mt-6">
      <h3 class="text-lg font-bold text-green-600 underline">Use Case Comparison</h3>
      <p class="mt-4 dark:text-white"><strong>Use Cases (From Diagram Image):</strong> ${r.ocr_usecases.join(', ') || '-'}</p>
      <p class="dark:text-white"><strong>Use Cases (From Document Text):</strong> ${r.expected_usecases.join(', ') || '-'}</p>
      ${renderComparison(useCaseCompare.missing, useCaseCompare.extra, 'Use Case')}
      ${useCaseSummary}
      </section>

      <section class="border border-gray-600 rounded p-4 mt-6">
      <h3 class="text-lg font-bold text-indigo-600 underline">Relationship Check</h3>
      ${relationNote}</section>

      <section class="border border-gray-600 rounded p-4 mt-6">
      <h3 class="text-lg font-bold text-fuchsia-600 underline">Extracted Paragraph</h3>
      <p class="mt-4 text-sm text-gray-600 dark:text-white whitespace-pre-wrap"><strong>Document Text:</strong><br/> ${paragraphHtml}</p><section>

      ${r.doc_actor_usecase_map ? `
      <section class="border border-gray-600 rounded p-4 mt-6">
        <h3 class="text-lg font-bold text-amber-600 underline">Actor–Use Case Mapping from Document</h3>
        ${Object.entries(r.doc_actor_usecase_map).map(([actor, ucs]) => `
          <p><strong>${actor}</strong> → ${ucs.join(', ')}</p>
        `).join('')}
      </section>` : ''}
      
      ${r.categorized && r.doc_actor_usecase_map ? `
  <section class="border border-gray-600 rounded p-4 mt-6">
    <h3 class="text-lg font-bold text-lime-600 underline">Comparison:UCD Image vs Document Mapping</h3>

    ${(() => {
      function normalize(str) {
        return str.toLowerCase().trim();
      }

      // Collect all unique actor names (normalized) from both mappings
      const allActorNames = new Set([
        ...Object.keys(r.categorized || {}).map(normalize),
        ...Object.keys(r.doc_actor_usecase_map || {}).map(normalize),
      ]);

      return [...allActorNames].map(normActor => {
        // Find original casing of actor name (from either side)
        const diagramActor = Object.keys(r.categorized || {}).find(a => normalize(a) === normActor);
        const docActor = Object.keys(r.doc_actor_usecase_map || {}).find(a => normalize(a) === normActor);
        const displayName = diagramActor || docActor || normActor;

        const imgUseCases = (diagramActor && r.categorized[diagramActor]) || [];
        const docUseCases = (docActor && r.doc_actor_usecase_map[docActor]) || [];

        const imgSet = new Set(imgUseCases.map(normalize));
        const docSet = new Set(docUseCases.map(normalize));

        const missingInImg = [...docSet].filter(u => !imgSet.has(u));
        const missingInDoc = [...imgSet].filter(u => !docSet.has(u));

        return `
          <div class="mb-3">
            <p class="dark:text-white"><strong>${displayName}</strong></p>
            ${missingInImg.length ? `<p class="text-red-600 text-sm">🔴 Missing in Diagram: ${missingInImg.join(', ')}</p>` : ''}
            ${missingInDoc.length ? `<p class="text-blue-600 text-sm">🔵 Missing in Document: ${missingInDoc.join(', ')}</p>` : ''}
            ${!missingInImg.length && !missingInDoc.length ? `<p class="text-green-600 text-sm">✅ All matched</p>` : ''}
          </div>
        `;
      }).join('');
    })()}
  </section>
` : ''}
<div class="flex justify-end">
<button onclick="downloadPDFPerPage()"" class="mt-3 bg-green-500 hover:bg-green-600 active:bg-green-700 text-white px-4 py-2 rounded">Download Report (PDF)</button></div>


    </div>
   </div> 
  `;

      return html;
   }).join('');
   stopEstimatedTimer(loadingText, "✅ Comparison complete.");
   } catch (err) {
      loadingText.innerHTML = "<span class='text-red-600'>❌ Comparison failed.</span>";
      console.error(err);
      clearInterval(intervalTimerId);
   } finally {
      setTimeout(() => loading.style.display = 'none', 1500);
   }
}

async function askGptForSuggestions() {
  if (!lastComparisonResults) {
    alert("❗ Please run comparison first.");
    return;
  }

  const parsed = lastComparisonResults;

  // Generate GPT prompt
  let prompt = `You are a senior software engineer helping students debug use case diagrams.\n\n`;

  parsed.forEach(r => {
    prompt += `
### 🧾 Page ${r.page}

**OCR Actors:** ${r.ocr_actors.join(', ') || '-'}  
**Document Actors:** ${r.expected_actors.join(', ') || '-'}

**OCR Use Cases:** ${r.ocr_usecases.join(', ') || '-'}  
**Document Use Cases:** ${r.expected_usecases.join(', ') || '-'}

**Includes:** ${r.includeCount || 0} | **Extends:** ${r.extendCount || 0}

📄 **Requirement Paragraph:**  
"""  
${r.paragraph}
"""

🧠 Please help check:
1. Are any actor–use case pairings illogical? 
   You may write in a neatly way with suitable html elementlike bold or strong or underline or others to highlight some words or sentences and can use some suitable color or background color with CSS.

2. Are 'include' or 'extend' relations misused?
   You may write in a neatly way with suitable html elementlike bold or strong or underline or others to highlight some words or sentences and can use some suitable color or background color with CSS.

3. Any modeling errors or inconsistencies?
   You may write in a neatly way with suitable html elementlike bold or strong or underline or others to highlight some words or sentences and can use some suitable color or background color with CSS.

4. Give a summary of inconsistency for each pages and suammry of improvement or suggestion for each pages and  final comment for each pages.
   You may write in a neatly way with suitable html elementlike bold or strong or underline or others to highlight some words or sentences and can use some suitable color or background color with CSS.
---
`;
  });

  // Show loading indicator
  const gptOutputDiv = document.getElementById("gpt-response");
  gptOutputDiv.style.display = 'block';
  gptOutputDiv.innerHTML = `<p class="text-yellow-500">🤖 Asking AI for analysis...</p>`;

  try {
    const response = await puter.ai.chat(prompt);
    gptOutputDiv.innerHTML = `
      <h3 class="font-bold mb-2 text-lg">🤖 AI Suggestion Result</h3>
      <pre class="whitespace-pre-wrap bg-gray-100 p-3 rounded dark:bg-black dark:text-white text-sm leading-relaxed">${response}</pre>
    `;
  } catch (err) {
    gptOutputDiv.innerHTML = `<p class="text-red-600">❌ AI analysis failed.</p>`;
    console.error(err);
  }
}


