function applyTheme(theme) {
    document.documentElement.classList.remove('light', 'dark');
    document.documentElement.classList.add(theme);
    localStorage.setItem('theme', theme);

    const toggle = document.getElementById('modeToggle');
    if (toggle) {
        toggle.classList.toggle('active', theme === 'light');
    }
}

// load theme on every page
const savedTheme = localStorage.getItem('theme') || 'dark';
applyTheme(savedTheme);

// Auto-hide upload message after 3 seconds
// const uploadMessage = document.getElementById("upload-message");
// if (uploadMessage && uploadMessage.dataset.autohide === "true") {
// 	setTimeout(() => {
// 		uploadMessage.classList.add("is-hiding");
// 		setTimeout(() => {
// 			uploadMessage.remove();
// 		}, 250);
// 	}, 3000);
// }

// Handle Google Sign-In response
function handleCredentialResponse(response) {
    const responsePayload = jwt_decode(response.credential);
    if (!responsePayload) {
        console.error("Login error.");
        return;
    }

    const redirectTarget = `${window.location.pathname}${window.location.search}${window.location.hash}`;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'login.php';
    const fields = { 
        google_id: responsePayload.sub, 
        name: responsePayload.name, 
        email: responsePayload.email,
        redirect_to: redirectTarget
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
                const isSmall = window.matchMedia('(max-width: 510px)').matches;
                google.accounts.id.renderButton(signinEl, {
                    theme: 'filled_black',
                    size: isSmall ? 'small' : 'medium',
                    text: isSmall ? 'signin' : 'signin_with',
                    width: isSmall ? 92 : 170,
                    shape: 'rect'
                });
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
    // toggle mobile sidebar and close on outside click
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.querySelector('.sidebar');
    
    if (mobileBtn && sidebar) {
        // toggle menu when clicking the button
        mobileBtn.addEventListener('click', (e) => {
            sidebar.classList.toggle('open');
            e.stopPropagation(); 
        });

        // close menu when clicking anywhere else
        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && !mobileBtn.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // autohide messages 
    const uploadMessage = document.getElementById("upload-message");
        if (uploadMessage && uploadMessage.dataset.autohide === "true") {
            setTimeout(() => {
                uploadMessage.classList.add("is-hiding");
                setTimeout(() => {
                    uploadMessage.remove();
                }, 250);
            }, 3000);
        }
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
            const current = localStorage.getItem('theme') || 'dark';
            const next = current === 'light' ? 'dark' : 'light';
            applyTheme(next);
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

window.addEventListener('load', () => {
    document.documentElement.classList.remove('no-transition');
});