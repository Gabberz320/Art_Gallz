const canvas = document.getElementById('mainCanvas');
const ctx = canvas.getContext('2d');
const selectionPanel = document.getElementById('selection-panel');
const textInput = document.getElementById('text-edit');
const colorPicker = document.getElementById('colorPicker');
const opacityPicker = document.getElementById('opacityPicker');

let shapes = [];
let selectedShape = null;
let isDragging = false;
let isResizing = false;
let resizeCorner = ''; 
let startX, startY;

let currentColor = '#ffffff';
let currentOpacity = 1.0;
const gridSize = 10; 
const BASE_CANVAS_WIDTH = 800;
const BASE_CANVAS_HEIGHT = 500;

function init() {
    resizeCanvas();
    render();
}
window.onload = init;

function getEventCoords(e) {
    const rect = canvas.getBoundingClientRect();
    let clientX, clientY;

    if (e.touches && e.touches.length > 0) {
        clientX = e.touches[0].clientX;
        clientY = e.touches[0].clientY;
    } else {
        clientX = e.clientX;
        clientY = e.clientY;
    }

    return {
        x: clientX - rect.left,
        y: clientY - rect.top
    };
}

function resizeCanvas() {
    const canvasArea = document.getElementById('canvas-area');
    if (!canvasArea) return;

    const availableWidth = Math.max(320, canvasArea.clientWidth - 32);
    const width = Math.min(BASE_CANVAS_WIDTH, availableWidth);
    const height = Math.round(width * (BASE_CANVAS_HEIGHT / BASE_CANVAS_WIDTH));

    canvas.width = width;
    canvas.height = height;
}

window.addEventListener('resize', function() {
    const hasContent = shapes.length > 0;
    resizeCanvas();
    if (hasContent) render();
});

function handleStart(e) {
    // Prevent default browser behavior while interacting with the studio
    if (e.type === 'touchstart') e.preventDefault();
    
    const { x: mx, y: my } = getEventCoords(e);
    const handleSize = 20; // Increased hit area for mobile 
    
    if (selectedShape) {
        const {x, y, w, h} = selectedShape;

        // Check Delete X Button
        const dx = mx - (x + w);
        const dy = my - y;
        const distance = Math.sqrt(dx*dx + dy*dy);
        if (distance < 25) { 
            deleteSelected();
            return;
        }

        // Check Resize Corners 
        if (mx > x - handleSize && mx < x + handleSize && my > y - handleSize && my < y + handleSize) {
            isResizing = true; resizeCorner = 'tl'; return;
        }
        if (mx > x+w - handleSize && mx < x+w + handleSize && my > y - handleSize && my < y + handleSize) {
            isResizing = true; resizeCorner = 'tr'; return;
        }
        if (mx > x - handleSize && mx < x + handleSize && my > y+h - handleSize && my < y+h + handleSize) {
            isResizing = true; resizeCorner = 'bl'; return;
        }
        if (mx > x+w - handleSize && mx < x+w + handleSize && my > y+h - handleSize && my < y+h + handleSize) {
            isResizing = true; resizeCorner = 'br'; return;
        }
    }

    // Iterate backwards to select the object on top of the visual stack
    let found = null;
    for (let i = shapes.length - 1; i >= 0; i--) {
        const s = shapes[i];
        if (mx > s.x && mx < s.x + s.w && my > s.y && my < s.y + s.h) {
            found = s;
            break;
        }
    }

    selectedShape = found;
    if (selectedShape) {
        isDragging = true;
        startX = mx - selectedShape.x;
        startY = my - selectedShape.y;
    }
    updateSidebar();
    render();
}

function handleMove(e) {
    if (!isDragging && !isResizing) return;
    if (e.cancelable) e.preventDefault();

    const { x: mx, y: my } = getEventCoords(e);

    if (isResizing && selectedShape) {
        const s = selectedShape;
        const oldX2 = s.x + s.w;
        const oldY2 = s.y + s.h;

        if (resizeCorner === 'br') {
            s.w = Math.max(20, mx - s.x);
            s.h = Math.max(20, my - s.y);
        } else if (resizeCorner === 'tl') {
            s.x = Math.min(oldX2 - 20, mx);
            s.y = Math.min(oldY2 - 20, my);
            s.w = oldX2 - s.x;
            s.h = oldY2 - s.y;
        } else if (resizeCorner === 'tr') {
            s.y = Math.min(oldY2 - 20, my);
            s.w = Math.max(20, mx - s.x);
            s.h = oldY2 - s.y;
        } else if (resizeCorner === 'bl') {
            s.x = Math.min(oldX2 - 20, mx);
            s.w = oldX2 - s.x;
            s.h = Math.max(20, my - s.y);
        }
    } else if (isDragging) {
        // Snapping logic 
        selectedShape.x = Math.round((mx - startX) / gridSize) * gridSize;
        selectedShape.y = Math.round((my - startY) / gridSize) * gridSize;
    }
    render();
}

function handleEnd() {
    isDragging = false; 
    isResizing = false; 
    resizeCorner = '';
}

canvas.addEventListener('mousedown', handleStart);
window.addEventListener('mousemove', handleMove);
window.addEventListener('mouseup', handleEnd);

canvas.addEventListener('touchstart', handleStart, { passive: false });
window.addEventListener('touchmove', handleMove, { passive: false });
window.addEventListener('touchend', handleEnd);


window.addEventListener('keydown', function(e) {
    if ((e.key === 'Delete' || e.key === 'Backspace') && selectedShape) {
        if (document.activeElement !== textInput) {
            deleteSelected();
        }
    }
});

window.deleteSelected = function() {
    if (selectedShape) {
        shapes = shapes.filter(s => s !== selectedShape);
        selectedShape = null;
        updateSidebar();
        render();
    }
};

window.addShape = function(type) {
    let w = 150, h = 150, txt = "";
    if (type === 'text') { w = 300; h = 60; txt = "New Text Content"; }

    const newShape = {
        type: type,
        x: 50, y: 50, w: w, h: h,
        color: currentColor,
        opacity: currentOpacity,
        text: txt,
        font: 'Arial',
        fontSize: 30
    };
    
    shapes.push(newShape);
    selectedShape = newShape;
    updateSidebar();
    render();
};

window.triggerImageUpload = function() {
    const uploader = document.createElement('input');
    uploader.type = 'file'; uploader.accept = 'image/*';
    uploader.onchange = function(e) {
        const reader = new FileReader();
        reader.onload = function(event) {
            const img = new Image();
            img.onload = function() {
                shapes.push({
                    type: 'image', img: img, x: 50, y: 50,
                    w: img.width / 4, h: img.height / 4, opacity: 1.0
                });
                render();
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(e.target.files[0]);
    };
    uploader.click();
};

function render() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // Draw white background
    ctx.fillStyle = "white";
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    shapes.forEach(function(s) {
        ctx.save();
        ctx.globalAlpha = s.opacity;
        ctx.fillStyle = s.color;
        
        if (s.type === 'rect') {
            ctx.fillRect(s.x, s.y, s.w, s.h);
        } else if (s.type === 'image') {
            ctx.drawImage(s.img, s.x, s.y, s.w, s.h);
        } else if (s.type === 'text') {
            ctx.font = `bold ${s.fontSize}px ${s.font}`;
            wrapText(ctx, s.text, s.x, s.y + s.fontSize, s.w, s.fontSize * 1.2);
        }
        ctx.restore();

        // Draw selection UI overlay
        if (s === selectedShape) {
            ctx.strokeStyle = '#ff00cc'; // Neon Pink theme
            ctx.lineWidth = 2;
            ctx.setLineDash([5, 5]);
            ctx.strokeRect(s.x, s.y, s.w, s.h);
            ctx.setLineDash([]);
            

            ctx.fillStyle = '#ff00cc';
            const handleSize = 10; 
            ctx.fillRect(s.x - 5, s.y - 5, handleSize, handleSize); 
            ctx.fillRect(s.x + s.w - 5, s.y - 5, handleSize, handleSize); 
            ctx.fillRect(s.x - 5, s.y + s.h - 5, handleSize, handleSize); 
            ctx.fillRect(s.x + s.w - 5, s.y + s.h - 5, handleSize, handleSize); 

            // Delete Circle
            ctx.fillStyle = '#ff4444';
            ctx.beginPath();
            ctx.arc(s.x + s.w, s.y, 14, 0, Math.PI * 2);
            ctx.fill();
            ctx.fillStyle = 'white';
            ctx.font = 'bold 16px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('×', s.x + s.w, s.y);
        }
    });
}

function updateSidebar() {
    selectionPanel.style.display = selectedShape ? 'block' : 'none';
    if (selectedShape) {
        if (selectedShape.type === 'text') {
            textInput.value = selectedShape.text;
            document.getElementById('text-group').style.display = 'block';
        } else {
            document.getElementById('text-group').style.display = 'none';
        }
        // Update picker values to match selected object
        if (colorPicker) colorPicker.value = selectedShape.color || currentColor;
        if (opacityPicker) opacityPicker.value = Math.round((selectedShape.opacity || 1) * 100);
    }
}

// Tools
if (colorPicker) {
    colorPicker.addEventListener('input', (e) => {
        const val = e.target.value;
        if (selectedShape) {
            selectedShape.color = val;
            render();
        } else {
            currentColor = val;
        }
    });
}

if (opacityPicker) {
    opacityPicker.addEventListener('input', (e) => {
        const val = parseInt(e.target.value) / 100;
        if (selectedShape) {
            selectedShape.opacity = val;
            render();
        } else {
            currentOpacity = val;
        }
    });
}

function wrapText(context, text, x, y, maxWidth, lineHeight) {
    const words = text.split(' ');
    let line = '';
    for (let n = 0; n < words.length; n++) {
        let testLine = line + words[n] + ' ';
        if (context.measureText(testLine).width > maxWidth && n > 0) {
            context.fillText(line, x, y);
            line = words[n] + ' ';
            y += lineHeight;
        } else {
            line = testLine;
        }
    }
    context.fillText(line, x, y);
}