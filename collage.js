const canvas = document.getElementById('mainCanvas');
const ctx = canvas.getContext('2d');
const selectionPanel = document.getElementById('selection-panel');
const textInput      = document.getElementById('text-edit');
const colorPicker    = document.getElementById('colorPicker');
const opacityPicker  = document.getElementById('opacityPicker');

let shapes        = [];   // Array of all objects on canvas
let selectedShape = null; // The object currently being edited
let isDragging    = false;
let isResizing    = false;
let resizeCorner  = '';   
let startX, startY;

// Defaults
let currentColor   = '#ffffff';
let currentOpacity = 1.0;
const gridSize     = 10; 
const BASE_WIDTH   = 800;
const BASE_HEIGHT  = 500;

function init() {
    resizeCanvas();
    render();
}
window.onload = init;

function resizeCanvas() {
    const canvasArea = document.getElementById('canvas-area');
    if (!canvasArea) return;

    // Ensure the canvas fits on mobile screens 
    const availableWidth = Math.max(320, canvasArea.clientWidth - 32);
    const width  = Math.min(BASE_WIDTH, availableWidth);
    const height = Math.round(width * (BASE_HEIGHT / BASE_WIDTH));

    canvas.width  = width;
    canvas.height = height;
}

window.addEventListener('resize', () => {
    resizeCanvas();
    render(); 
});

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

function handleStart(e) {
    if (e.type === 'touchstart') e.preventDefault();
    
    const { x: mx, y: my } = getEventCoords(e);
    const handleSize = 20; 
    
    if (selectedShape) {
        const {x, y, w, h} = selectedShape;

        // Check Delete Button 
        const dx = mx - (x + w);
        const dy = my - y;
        if (Math.sqrt(dx*dx + dy*dy) < 25) { 
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

    // Hit detection
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

        // Resize Logic
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
        // Drag with Grid Snapping
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

// Event Listeners for Interaction
canvas.addEventListener('mousedown', handleStart);
window.addEventListener('mousemove', handleMove);
window.addEventListener('mouseup', handleEnd);

canvas.addEventListener('touchstart', handleStart, { passive: false });
window.addEventListener('touchmove', handleMove, { passive: false });
window.addEventListener('touchend', handleEnd);

colorPicker.addEventListener('input', (e) => {
    if (selectedShape) {
        selectedShape.color = e.target.value;
        currentColor = e.target.value; 
        render(); 
    }
});

opacityPicker.addEventListener('input', (e) => {
    if (selectedShape) {
        selectedShape.opacity = parseFloat(e.target.value) / 100;
        currentOpacity = selectedShape.opacity; 
        render();
    }
});

const fontSizePicker = document.getElementById('fontSizePicker');
if (fontSizePicker) {
    fontSizePicker.addEventListener('input', (e) => {
        if (selectedShape && selectedShape.type === 'text') {
            selectedShape.fontSize = parseInt(e.target.value, 10);
            render();
        }
    });
}

window.deleteSelected = function() {
    if (selectedShape) {
        shapes = shapes.filter(s => s !== selectedShape);
        selectedShape = null;
        updateSidebar();
        render();
    }
};

window.moveLayer = function(direction) {
    if (!selectedShape) return;
    const idx = shapes.indexOf(selectedShape);
    
    if (direction === 'up' && idx < shapes.length - 1) {
        const temp = shapes[idx];
        shapes[idx] = shapes[idx + 1];
        shapes[idx + 1] = temp;
    } else if (direction === 'down' && idx > 0) {
        const temp = shapes[idx];
        shapes[idx] = shapes[idx - 1];
        shapes[idx - 1] = temp;
    }
    render();
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
    uploader.type = 'file';
    uploader.accept = 'image/*'; // Only allow image files

    uploader.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        
        // Read the file from the user's device
        reader.onload = function(event) {
            const img = new Image();
            img.onload = function() {
                const newImageShape = {
                    type: 'image',
                    img: img,      
                    x: 100,        
                    y: 100,
                    w: img.width / 4,  
                    h: img.height / 4,
                    opacity: 1.0,
                    color: null   
                };
                shapes.push(newImageShape);
                selectedShape = newImageShape; 
                updateSidebar();
                render(); 
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    };
    uploader.click(); 
};

window.postCollageToGallery = function() {
    // Clear selection so UI doesn't appear in the final photo
    selectedShape = null; 
    updateSidebar();
    render();

    // Small delay to ensure the canvas has updated
    setTimeout(() => {
        try {
            const imageData = canvas.toDataURL('image/png');
            const input = document.getElementById('collageImageInput');
            const form  = document.getElementById('collageForm');
            if (input && form) {
                input.value = imageData;
                form.submit();
            }
        } catch (e) {
            alert("Could not save image. Try using images from the local library.");
        }
    }, 100);
};

function updateSidebar() {
    selectionPanel.style.display = selectedShape ? 'block' : 'none'; //FIX
    
    if (selectedShape) {
        // Sync Text Settings
        if (selectedShape.type === 'text') {
            textInput.value = selectedShape.text;
            document.getElementById('text-group').style.display = 'block';
            document.getElementById('fontSizePicker').value = selectedShape.fontSize;
        } else {
            document.getElementById('text-group').style.display = 'none';
        }
        
        // Sync Visual Settings
        if (colorPicker) colorPicker.value = selectedShape.color || currentColor;
        if (opacityPicker) opacityPicker.value = Math.round((selectedShape.opacity || 1) * 100);
    }
}

textInput.addEventListener('input', () => {
    if (selectedShape && selectedShape.type === 'text') {
        selectedShape.text = textInput.value;
        render();
    }
});

function render() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // Background
    ctx.fillStyle = "white";
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    shapes.forEach((s) => {
        ctx.save();
        ctx.globalAlpha = s.opacity;
        ctx.fillStyle   = s.color;
        
        if (s.type === 'rect') {
            ctx.fillRect(s.x, s.y, s.w, s.h);
        } else if (s.type === 'image') {
            ctx.drawImage(s.img, s.x, s.y, s.w, s.h);
        } else if (s.type === 'text') {
            ctx.font = `bold ${s.fontSize}px ${s.font}`;
            wrapText(ctx, s.text, s.x, s.y + s.fontSize, s.w, s.fontSize * 1.2);
        }
        ctx.restore();

        if (s === selectedShape) {
            ctx.strokeStyle = '#ff00cc'; 
            ctx.lineWidth   = 2;
            ctx.setLineDash([5, 5]);
            ctx.strokeRect(s.x, s.y, s.w, s.h);
            ctx.setLineDash([]);
            
            // Corner Resize Knobs
            ctx.fillStyle = '#ff00cc';
            const corners = [
                [s.x, s.y], [s.x+s.w, s.y], 
                [s.x, s.y+s.h], [s.x+s.w, s.y+s.h]
            ];
            corners.forEach(p => ctx.fillRect(p[0]-5, p[1]-5, 10, 10));

            // Delete Button
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

function wrapText(context, text, x, y, maxWidth, lineHeight) {
    const words = text.split(' ');
    let line = '';
    for (let n = 0; n < words.length; n++) {
        let testLine = line + words[n] + ' ';
        let metrics = context.measureText(testLine);
        if (metrics.width > maxWidth && n > 0) {
            context.fillText(line, x, y);
            line = words[n] + ' ';
            y += lineHeight;
        } else {
            line = testLine;
        }
    }
    context.fillText(line, x, y);
}