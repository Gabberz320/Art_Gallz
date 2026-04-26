document.querySelectorAll(".drop-zone__input").forEach((inputElement) => {
    const dropZoneElement = inputElement.closest(".drop-zone");
//click to open file dialog
    dropZoneElement.addEventListener("click", () => {
        inputElement.click();
    });

    inputElement.addEventListener("change", () => {
        if (inputElement.files.length) {
            updateThumbnail(dropZoneElement, inputElement.files[0]);
        }
    });
//drag and drop functionality
    dropZoneElement.addEventListener("dragover", (e) => {
        e.preventDefault();
        dropZoneElement.classList.add("drop-zone--over");
    });

    ["dragleave", "dragend"].forEach((type) => {
        dropZoneElement.addEventListener(type, () => {
            dropZoneElement.classList.remove("drop-zone--over");
        });
    });
//handle dropped files
    dropZoneElement.addEventListener("drop", (e) => {
        e.preventDefault();
        dropZoneElement.classList.remove("drop-zone--over");

        if (e.dataTransfer.files.length) {
            inputElement.files = e.dataTransfer.files;
            updateThumbnail(dropZoneElement, e.dataTransfer.files[0]);
        }
    });
});
//thumbnail update funct ion to show image preview or file name
function updateThumbnail(dropZoneElement, file) {
    let thumbnailElement = dropZoneElement.querySelector(".drop-zone__thumb");

    if (dropZoneElement.querySelector(".drop-zone__prompt")) {
        dropZoneElement.querySelector(".drop-zone__prompt").remove();
    }

    if (!thumbnailElement) {
        thumbnailElement = document.createElement("div");
        thumbnailElement.classList.add("drop-zone__thumb");
        dropZoneElement.appendChild(thumbnailElement);
    }

    thumbnailElement.textContent = "";

    if (file && file.type && file.type.startsWith("image/")) {
        const objectUrl = URL.createObjectURL(file);
        thumbnailElement.style.backgroundImage = "url('" + objectUrl + "')";
    } else {
        thumbnailElement.style.backgroundImage = "none";
        thumbnailElement.textContent = "Selected: " + (file ? file.name : "Unknown File");
        thumbnailElement.style.display = "flex";
        thumbnailElement.style.alignItems = "center";
        thumbnailElement.style.justifyContent = "center";
        thumbnailElement.style.color = "#333";
    }
}
//auto-hide upload message after 3 seconds
const uploadMessage = document.getElementById("upload-message");

if (uploadMessage && uploadMessage.dataset.autohide === "true") {
    setTimeout(() => {
        uploadMessage.classList.add("is-hiding");

        setTimeout(() => {
            uploadMessage.remove();
        }, 250);
    }, 3000);
}
