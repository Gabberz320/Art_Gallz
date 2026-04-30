const inputs = document.querySelectorAll(".drop-zone__input");
console.log('upload.js: found drop-zone inputs', inputs.length);

inputs.forEach((inputElement) => {
    const dropZoneElement = inputElement.closest(".drop-zone");
    console.log('upload.js: init for', dropZoneElement);

    // change handler
    inputElement.addEventListener("change", (e) => {
        console.log('upload.js: input change, files=', inputElement.files && inputElement.files.length);
        if (inputElement.files && inputElement.files.length) {
            updateThumbnail(dropZoneElement, inputElement.files[0]);
        }
    });

    // drag and drop
    dropZoneElement.addEventListener("dragover", (e) => {
        e.preventDefault();
        // required to allow drop
        e.dataTransfer && (e.dataTransfer.dropEffect = 'copy');
        dropZoneElement.classList.add("drop-zone--over");
        console.log('upload.js: dragover');
    });

    ["dragleave", "dragend"].forEach((type) => {
        dropZoneElement.addEventListener(type, () => {
            dropZoneElement.classList.remove("drop-zone--over");
        });
    });

    dropZoneElement.addEventListener("drop", (e) => {
        e.preventDefault();
        dropZoneElement.classList.remove("drop-zone--over");
        console.log('upload.js: drop event', e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length);

        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
            try { inputElement.files = e.dataTransfer.files; } catch (err) { console.warn('upload.js: could not assign to input.files', err); }
            updateThumbnail(dropZoneElement, e.dataTransfer.files[0]);
        }
    });
});

// thumbnail update to show image preview or filename
function updateThumbnail(dropZoneElement, file) {
    let thumbnailElement = dropZoneElement.querySelector(".drop-zone__thumb");

    const prompt = dropZoneElement.querySelector(".drop-zone__prompt");
    if (prompt) prompt.remove();

    if (!thumbnailElement) {
        thumbnailElement = document.createElement("div");
        thumbnailElement.classList.add("drop-zone__thumb");
        dropZoneElement.appendChild(thumbnailElement);
    }

    // clear previous
    thumbnailElement.innerHTML = "";

    if (file && file.type && file.type.startsWith("image/")) {
        const objectUrl = URL.createObjectURL(file);
        const img = document.createElement('img');
        img.src = objectUrl;
        img.alt = file.name || 'preview';
        img.onload = () => URL.revokeObjectURL(objectUrl);
        thumbnailElement.appendChild(img);
    } else {
        thumbnailElement.textContent = "Selected: " + (file ? file.name : "Unknown File");
        thumbnailElement.style.display = "flex";
        thumbnailElement.style.alignItems = "center";
        thumbnailElement.style.justifyContent = "center";
        thumbnailElement.style.color = "#333";
    }
}
