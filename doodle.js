const canvas = document.getElementById('doodleCanvas');
const ctx = canvas.getContext('2d');
const colorPicker = document.getElementById('colorPicker');
const sizePicker = document.getElementById('sizePicker');
const eraserBtn = document.getElementById('eraserBtn');
const brushBtn = document.getElementById('brushBtn');
const saveBtn = document.getElementById('saveBtn');
const fillBtn = document.getElementById('fillBtn');
let isFill = false;

// Set canvas resolution
function resizeCanvas() {
    canvas.width = canvas.parentElement.clientWidth;
    canvas.height = canvas.parentElement.clientHeight;
}
window.addEventListener('resize', resizeCanvas);
resizeCanvas();

let painting = false;
let isEraser = false;

// Get coordinates for both Mouse and Touch
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
    if (isFill) {
        const coords = getCoords(e);
        floodFill(Math.floor(coords.x), Math.floor(coords.y), colorPicker.value);
        return; 
    }
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

function floodFill(startX, startY, fillColor) {
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const data = imageData.data;
    
    // Convert hex color to RGBA
    const r = parseInt(fillColor.slice(1, 3), 16);
    const g = parseInt(fillColor.slice(3, 5), 16);
    const b = parseInt(fillColor.slice(5, 7), 16);
    
    const targetPos = (startY * canvas.width + startX) * 4;
    const targetR = data[targetPos];
    const targetG = data[targetPos + 1];
    const targetB = data[targetPos + 2];
    const targetA = data[targetPos + 3];

    // Don't fill if color is already the same
    if (targetR === r && targetG === g && targetB === b && targetA === 255) return;

    const stack = [[startX, startY]];
    
    while (stack.length) {
        const [x, y] = stack.pop();
        let currentPos = (y * canvas.width + x) * 4;

        if (x < 0 || x >= canvas.width || y < 0 || y >= canvas.height) continue;
        
        if (data[currentPos] === targetR && 
            data[currentPos + 1] === targetG && 
            data[currentPos + 2] === targetB && 
            data[currentPos + 3] === targetA) {
            
            // Change pixel color
            data[currentPos] = r;
            data[currentPos + 1] = g;
            data[currentPos + 2] = b;
            data[currentPos + 3] = 255;

            // Add neighbors to stack
            stack.push([x + 1, y]);
            stack.push([x - 1, y]);
            stack.push([x, y + 1]);
            stack.push([x, y - 1]);
        }
    }
    ctx.putImageData(imageData, 0, 0);
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
    isFill = false;
    eraserBtn.classList.add('active');
    brushBtn.classList.remove('active');
    fillBtn.classList.remove('active');
});

fillBtn.addEventListener('click', () => {
    isFill = true;
    isEraser = false;
    fillBtn.classList.add('active');
    brushBtn.classList.remove('active');
    eraserBtn.classList.remove('active');
});

brushBtn.addEventListener('click', () => {
    isEraser = false;
    isFill = false;
    brushBtn.classList.add('active');
    eraserBtn.classList.remove('active');
    fillBtn.classList.remove('active');
});

function clearCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
}

// Handling Post functionality
saveBtn.addEventListener('click', () => {
    const form = document.getElementById('doodleForm');
    const input = document.getElementById('imageInput');

    // Make sure the form exists
    if (!form || !input) {
        alert('Please log in to post your doodle!');
        return;
    }

    const tempCanvas = document.createElement('canvas');
    const tCtx = tempCanvas.getContext('2d');
    tempCanvas.width = canvas.width;
    tempCanvas.height = canvas.height;

    // Draw solid notebook background color
    tCtx.fillStyle = "#f1f1f1";
    tCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
    
    // Draw the horizontal grey lines
    tCtx.strokeStyle = "#e1e1e1";
    tCtx.lineWidth = 1;
    tCtx.beginPath();
    for (let y = 0; y < tempCanvas.height; y += 30) {
        tCtx.moveTo(0, y);
        tCtx.lineTo(tempCanvas.width, y);
    }
    tCtx.stroke();

    // Draw the red notebook margin line
    tCtx.strokeStyle = "#ffb4b8";
    tCtx.lineWidth = 2;
    tCtx.beginPath();
    tCtx.moveTo(51, 0); 
    tCtx.lineTo(51, tempCanvas.height);
    tCtx.stroke();

    // Draw the user's doodle on top
    tCtx.drawImage(canvas, 0, 0);

    // Put the image data into the hidden input and submit the form!
    const imageData = tempCanvas.toDataURL("image/png");
    input.value = imageData;
    
    form.submit(); 
});