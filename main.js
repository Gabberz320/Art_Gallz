// Auto-hide upload message after 3 seconds
const uploadMessage = document.getElementById("upload-message");
if (uploadMessage && uploadMessage.dataset.autohide === "true") {
	setTimeout(() => {
		uploadMessage.classList.add("is-hiding");
		setTimeout(() => {
			uploadMessage.remove();
		}, 250);
	}, 3000);
}

// Handle Google Sign-In response
function handleCredentialResponse(response) {
    const responsePayload = jwt_decode(response.credential);
    if (!responsePayload) {
        console.error("Login error.");
        return;
    }
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'login.php';
    const fields = { 
        google_id: responsePayload.sub, 
        name: responsePayload.name, 
        email: responsePayload.email 
    };
    for (const key in fields) {
        const input = document.createElement('input');
        input.type = 'hidden'; 
        input.name = key;
        input.value = fields[key];
        form.appendChild(input);
    }
    document.body.appendChild(form);
    form.submit();
}

// Initialize GSI and render button when available
(function initGSI() {
    function tryInit() {
        if (typeof google !== 'undefined' && google.accounts && google.accounts.id) {
            if (!window._gsiInitialized) {
                const clientId = document.body.dataset.googleClientId;
                if (clientId) {
                    google.accounts.id.initialize({
                        client_id: clientId,
                        callback: handleCredentialResponse
                    });
                    window._gsiInitialized = true;
                }
            }
            const signinEl = document.querySelector('.g_id_signin');
            if (signinEl && window._gsiInitialized) {
                google.accounts.id.renderButton(signinEl, { theme: 'filled_black', size: 'medium' });
            }
            return true;
        }
        return false;
    }
    if (!tryInit()) {
        const interval = setInterval(() => { if (tryInit()) clearInterval(interval); }, 200);
        window.addEventListener('load', () => { tryInit(); });
    }
})();

// Prompt GSI on logout redirect
const params = new URLSearchParams(window.location.search);
if (params.get('loggedout') === '1') {
    window.addEventListener('load', () => {
        if (typeof google !== 'undefined') {
            google.accounts.id.prompt();
        }
    });
}

// DOM-dependent code - wait for page to load
document.addEventListener('DOMContentLoaded', () => {
    // Avatar dropdown toggle
    const avatarBtn = document.getElementById('avatarBtn');
    const avatarDropdown = document.getElementById('avatarDropdown');
    if (avatarBtn) {
        avatarBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            avatarDropdown.classList.toggle('open');
        });
        document.addEventListener('click', () => {
            avatarDropdown.classList.remove('open');
        });
    }

    // Theme toggle
    const toggle = document.getElementById('modeToggle');
    if (toggle) {
        toggle.addEventListener('click', () => {
            document.body.classList.toggle('light');
        });
    }

    // Like button handler
    document.querySelectorAll('.like_btn[data-id]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const artId = btn.dataset.id;
            const countEl = btn.querySelector('.like_count');
            const res = await fetch('like.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `art_id=${artId}`
            });
            const data = await res.json();
            if (data.success) {
                countEl.textContent = data.count;
                btn.classList.toggle('liked', data.liked);
            }
        });
    });
});
