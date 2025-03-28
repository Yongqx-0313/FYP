<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI-Driven Diagram Detecter & Checking System</title>
    <link href="../../src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="../../src/main.css">
    <link rel="stylesheet" href="../../src/file.css">
</head>
<body class="bg-gray-100 min-h-screen">
<?php include 'header.php'; ?>
    <div class="md:flex justify-center min-h-screen items-center min-w-[24.5rem]">
        <div class="flex flex-col border-solid border-2 border-sky-500">
            <div>
                <h1 class="text-center bg-orange-300 font-bold text-3xl">AI-Driven Use Case Diagram Consistency Checker</h1>
            </div>
            <div class="flex flex-col md:flex-row">
                <div class="flex flex-col bg-yellow-300 py-4 px-4">
                    <p class="text-center font-bold">Upload Image:</p>
                    <div class="my-2">
                        <input type="file" id="imageUpload" accept="image/*" onchange="loadImage(event)" class="ml-[4rem] mr-[1rem] md:ml-[6rem] w-[240px]" />
                    </div>
    
                    <div id="image-container" class="flex justify-center my-4">
    
                    </div>
                    <div id="label-container" class="mx-8"></div>
                </div>
    
                <div class="flex flex-col bg-lime-300 py-4 px-4">
                    <p class="text-center font-bold">Upload Document:</p>
                    <div class="my-2">
                        <input type="file" id="docUpload" accept=".txt" onchange="loadDocument(event)" class="ml-[4rem] mr-[1rem] md:ml-[6rem] w-[240px]" />
                    </div>
                </div>
    
    
            </div>
    
            <div class="bg-blue-200 flex justify-center">
                <div class="flex flex-col">
                    <div class="my-1 font-bold text-center">OCR Output:</div>
                    <div id="ocr-output" class="my-4"></div>
    
                    <div class="my-1 font-bold text-center">OCR Result Text:</div>
                    <div id="ocr-result-text" class="my-4"></div>
    
                    <div class="font-bold text-center">Document Text:</div>
                    <div id="document-text" class="w-[15rem] my-4 md:w-[27rem]"></div>
    
                    <div id="comparison-container" class="my-4"></div>
                </div>
    
            </div>
    
        </div>
    
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>



<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@latest/dist/tf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@teachablemachine/image@latest/dist/teachablemachine-image.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@latest/dist/tesseract.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>

<script type="text/javascript">
    const modelPath = "./"; // Path to your model files

    let model, labelContainer, maxPredictions;
    let diagramType = "", diagramElements = { actors: [], useCases: [], relationships: [] };

    async function init() {
        const modelURL = modelPath + "model.json";
        const metadataURL = modelPath + "metadata.json";

        try {
            console.log("Loading model...");
            model = await tmImage.load(modelURL, metadataURL);
            maxPredictions = model.getTotalClasses();
            console.log("Model loaded successfully with " + maxPredictions + " classes.");

            // Initialize the label container
            labelContainer = document.getElementById("label-container");
            for (let i = 0; i < maxPredictions; i++) {
                labelContainer.appendChild(document.createElement("div"));
            }
        } catch (error) {
            console.error("Error loading model:", error);
        }
    }

    window.onload = init;


    async function loadImage(event) {
        const imageContainer = document.getElementById("image-container");
        imageContainer.innerHTML = ''; // Clear any previous image

        const file = event.target.files[0];
        if (file && file.type.startsWith("image/")) {
            const reader = new FileReader();

            reader.onload = async function (e) {
                const image = new Image();
                image.src = e.target.result;
                image.width = 200;
                image.height = 200;

                image.onload = async () => {
                    console.log("Image loaded successfully.");
                    imageContainer.appendChild(image);
                    await predict(image);
                };

                image.onerror = () => {
                    console.error("Error loading image.");
                    alert("Failed to load image. Please try another file.");
                };
            };

            reader.onerror = () => {
                console.error("Error reading file.");
                alert("There was an error reading the file. Please try again.");
            };

            reader.readAsDataURL(file);
        } else {
            alert("Please upload a valid image file.");
        }
    }

    async function predict(image) {
        try {
            console.log("Running prediction...");
            const prediction = await model.predict(image);
            labelContainer.innerHTML = ''; // Clear previous predictions

            let highestProbability = 0;
            diagramType = "";
            for (let i = 0; i < maxPredictions; i++) {
                const probability = prediction[i].probability;
                const classPrediction = `${prediction[i].className}: ${probability.toFixed(2)}`;
                const predictionElement = document.createElement("div");
                predictionElement.innerHTML = classPrediction;
                labelContainer.appendChild(predictionElement);
                console.log(classPrediction);

                if (probability > highestProbability) {
                    highestProbability = probability;
                    diagramType = prediction[i].className;
                }
            }

            // Run OCR to detect text elements in the diagram
            diagramElements = await extractTextFromImage(image);
            console.log("Detected elements from diagram:", diagramElements);

            // Display OCR output
            const ocrOutput = document.getElementById("ocr-output");
            ocrOutput.innerHTML = `<h3 class="font-bold">OCR Detected Elements</h3>`;
            ocrOutput.innerHTML += `<ul><li>Actors: ${diagramElements.actors.join(", ")}</li></ul>`;
            ocrOutput.innerHTML += `<ul><li>Use Cases: ${diagramElements.useCases.join(", ")}</li></ul>`;
            ocrOutput.innerHTML += `<p>Relationships: ${diagramElements.relationships.join(", ")}</p>`;

            console.log("Prediction complete. Diagram Type:", diagramType);
        } catch (error) {
            console.error("Error during prediction:", error);
            alert("Failed to make a prediction. Please try again.");
        }
    }

    async function extractTextFromImage(image) {
        try {
            const result = await Tesseract.recognize(image, 'eng', { logger: m => console.log(m) });
            console.log("OCR Result:", result.data.text);

            // Display OCR result text in the frontend as a list
            const ocrResultText = document.getElementById("ocr-result-text");
            const textLines = result.data.text.split('\n');
            let listItems = '<ul>';

            textLines.forEach(line => {
                if (line.trim() !== '') {
                    listItems += `<li>${line}</li>`;
                }
            });
            listItems += '</ul>';

            ocrResultText.innerHTML = listItems;


            return extractKeywords(result.data.text);
        } catch (error) {
            console.error("Error during OCR:", error);
            return { actors: [], useCases: [], relationships: [] };
        }
    }

    function loadDocument(event) {
        const file = event.target.files[0];
        if (file && file.type === "text/plain") {
            const reader = new FileReader();
            reader.onload = function (e) {
                try {
                    const textContent = e.target.result;

                    // Display document text in the frontend as a list
                    const documentText = document.getElementById("document-text");
                    const textLines = textContent.split('\n');
                    let listItems = '<ul>';

                    textLines.forEach(line => {
                        if (line.trim() !== '') {
                            listItems += `<li>${line}</li>`;
                        }
                    });
                    listItems += '</ul>';

                    documentText.innerHTML = listItems;


                    const docElements = extractKeywords(textContent);
                    compareResults(diagramType, docElements);
                } catch (error) {
                    console.error("Error processing document:", error);
                    alert("Error processing the document. Please try again.");
                }
            };
            reader.onerror = () => {
                console.error("Error reading document.");
                alert("There was an error reading the document file. Please try again.");
            };
            reader.readAsText(file);
        } else {
            alert("Please upload a valid text document.");
        }
    }

    function extractKeywords(text) {
        const actors = text.match(/actor\s+([a-zA-Z0-9]+)/gi) || [];
        const useCases = text.match(/use\s*case\s+([a-zA-Z0-9]+)/gi) || [];
        const relationships = text.match(/(associates|depends\s*on|connects\s*to|linked\s*to)/gi) || [];
        return { actors, useCases, relationships };
    }

    function compareResults(diagramType, docElements) {
        const comparisonContainer = document.getElementById("comparison-container");
        comparisonContainer.innerHTML = `<h3 class="font-bold">Comparison Results</h3>`;

        const actorMatch = compareElementLists(diagramElements.actors, docElements.actors);
        comparisonContainer.innerHTML += `<p>Actors (Diagram vs Document): ${diagramElements.actors.length} vs ${docElements.actors.length} - ${actorMatch ? "Match" : "Mismatch"}</p>`;

        const useCaseMatch = compareElementLists(diagramElements.useCases, docElements.useCases);
        comparisonContainer.innerHTML += `<p>Use Cases (Diagram vs Document): ${diagramElements.useCases.length} vs ${docElements.useCases.length} - ${useCaseMatch ? "Match" : "Mismatch"}</p>`;

        const diagramHasRelationships = diagramElements.relationships.length > 0;
        const docHasRelationships = docElements.relationships.length > 0;

        if (diagramHasRelationships && docHasRelationships) {
            comparisonContainer.innerHTML += `<p>Both diagram and document contain relationships.</p>`;
        } else if (diagramHasRelationships) {
            comparisonContainer.innerHTML += `<p>Diagram has relationships, but the document does not.</p>`;
        } else if (docHasRelationships) {
            comparisonContainer.innerHTML += `<p>Document mentions relationships, but the diagram does not.</p>`;
        } else {
            comparisonContainer.innerHTML += `<p>No relationships found in either the diagram or document.</p>`;
        }

        console.log("Comparison complete:", { diagramElements, docElements });
    }

    function compareElementLists(list1, list2) {
        return list1.length === list2.length && list1.every((el) => list2.includes(el));
    }
</script>