const uploadMessage = document.getElementById("upload-message");

if (uploadMessage && uploadMessage.dataset.autohide === "true") {
	setTimeout(() => {
		uploadMessage.classList.add("is-hiding");

		setTimeout(() => {
			uploadMessage.remove();
		}, 250);
	}, 3000);
}
