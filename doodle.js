const canvas = document.getElementById('doodleCanvas');
const ctx = canvas.getContext('2d');
const colorPicker = document.getElementById('colorPicker');
const sizePicker = document.getElementById('sizePicker');
const eraserBtn = document.getElementById('eraserBtn');
const brushBtn = document.getElementById('brushBtn');
const saveBtn = document.getElementById('saveBtn');

// Set canvas resolution
function resizeCanvas() {
    canvas.width = canvas.parentElement.clientWidth;
    canvas.height = canvas.parentElement.clientHeight;
}
window.addEventListener('resize', resizeCanvas);
resizeCanvas();

let painting = false;
let isEraser = false;

/**
 * Get coordinates for both Mouse and Touch
 */
function getCoords(e) {
    const rect = canvas.getBoundingClientRect();
    // Check if it's a touch event
    if (e.touches && e.touches.length > 0) {
        return {
            x: e.touches[0].clientX - rect.left,
            y: e.touches[0].clientY - rect.top
        };
    }
    // Otherwise, treat as mouse event
    return {
        x: e.clientX - rect.left,
        y: e.clientY - rect.top
    };
}

function startPosition(e) {
    painting = true;
    draw(e);
}

function finishedPosition() {
    painting = false;
    ctx.beginPath();
}

function draw(e) {
    if (!painting) return;
    
    // Prevent scrolling on mobile while drawing
    if (e.cancelable) e.preventDefault();

    const coords = getCoords(e);

    ctx.lineWidth = sizePicker.value;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round'; 
    
    if (isEraser) {
        ctx.globalCompositeOperation = 'destination-out';
    } else {
        ctx.globalCompositeOperation = 'source-over';
        ctx.strokeStyle = colorPicker.value;
    }

    ctx.lineTo(coords.x, coords.y);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(coords.x, coords.y);
}

// Mouse Events
canvas.addEventListener('mousedown', startPosition);
canvas.addEventListener('mouseup', finishedPosition);
canvas.addEventListener('mousemove', draw);

// Touch Events (Mobile)
canvas.addEventListener('touchstart', (e) => {
    startPosition(e);
}, { passive: false });

canvas.addEventListener('touchend', finishedPosition);

canvas.addEventListener('touchmove', (e) => {
    draw(e);
}, { passive: false });

// Tool Switching
eraserBtn.addEventListener('click', () => {
    isEraser = true;
    eraserBtn.classList.add('active');
    brushBtn.classList.remove('active');
});

brushBtn.addEventListener('click', () => {
    isEraser = false;
    brushBtn.classList.add('active');
    eraserBtn.classList.remove('active');
});

function clearCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
}

// Handling Post functionality
saveBtn.addEventListener('click', () => {
    const tempCanvas = document.createElement('canvas');
    const tCtx = tempCanvas.getContext('2d');
    tempCanvas.width = canvas.width;
    tempCanvas.height = canvas.height;

    tCtx.fillStyle = "#f1f1f1";
    tCtx.fillRect(0,0, tempCanvas.width, tempCanvas.height);
    tCtx.drawImage(canvas, 0, 0);

    const imageData = tempCanvas.toDataURL("image/png");
    console.log("Ready to post!", imageData);
    alert("Doodle captured!");
});