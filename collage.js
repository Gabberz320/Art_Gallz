/**
 * COLLAGE STUDIO 
 */
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

function init() {
    canvas.width = 800;
    canvas.height = 500;
    render();
}
window.onload = init;

/**
 * DELETE LOGIC
 */
window.deleteSelected = function() {
    if (selectedShape) {
        shapes = shapes.filter(s => s !== selectedShape);
        selectedShape = null;
        updateSidebar();
        render();
    }
};

// Keyboard Listener for Delete/Backspace
window.addEventListener('keydown', function(e) {
    // Only delete if an object is selected and we aren't typing in the text input
    if ((e.key === 'Delete' || e.key === 'Backspace') && selectedShape) {
        if (document.activeElement !== textInput) {
            deleteSelected();
        }
    }
});

/**
 * TEXT & FONT CONTROLS
 */
window.updateSelectedText = function() {
    if (selectedShape && selectedShape.type === 'text') {
        selectedShape.text = document.getElementById('text-edit').value;
        render();
    }
};

window.updateFontSize = function(val) {
    if (selectedShape && selectedShape.type === 'text') {
        selectedShape.fontSize = parseInt(val);
        render();
    }
};

window.updateFontFace = function(val) {
    if (selectedShape && selectedShape.type === 'text') {
        selectedShape.font = val;
        render();
    }
};

/**
 * OBJECT CREATION
 */
window.addShape = function(type) {
    let w = 150;
    let h = 150;
    let txt = "";
    
    if (type === 'text') {
        w = 300;
        h = 60;
        txt = "New Text Content";
    }

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
    uploader.accept = 'image/*';
    uploader.onchange = function(e) {
        const reader = new FileReader();
        reader.onload = function(event) {
            const img = new Image();
            img.onload = function() {
                shapes.push({
                    type: 'image',
                    img: img,
                    x: 50, y: 50,
                    w: img.width / 4,
                    h: img.height / 4,
                    opacity: 1.0
                });
                render();
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(e.target.files[0]);
    };
    uploader.click();
};

/**
 * MOUSE INTERACTION
 */
canvas.addEventListener('mousedown', function(e) {
    const rect = canvas.getBoundingClientRect();
    const mx = e.clientX - rect.left;
    const my = e.clientY - rect.top;
    const handleSize = 15;
    
    if (selectedShape) {
        const {x, y, w, h} = selectedShape;

        // Check for Delete Button Click 
        const dx = mx - (x + w);
        const dy = my - y;
        const distance = Math.sqrt(dx*dx + dy*dy);
        if (distance < 12) {
            deleteSelected();
            return;
        }

        // Check for Resize Corners
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

    // Select Shape
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
        
        if (selectedShape.color) colorPicker.value = selectedShape.color;
        opacityPicker.value = selectedShape.opacity * 100;
        
        if (selectedShape.type === 'text') {
            document.getElementById('fontSizePicker').value = selectedShape.fontSize;
            document.getElementById('fontFamily').value = selectedShape.font;
        }
    }
    updateSidebar();
    render();
});

window.addEventListener('mousemove', function(e) {
    if (!isDragging && !isResizing) return;
    const rect = canvas.getBoundingClientRect();
    const mx = e.clientX - rect.left;
    const my = e.clientY - rect.top;

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
        selectedShape.x = Math.round((mx - startX) / gridSize) * gridSize;
        selectedShape.y = Math.round((my - startY) / gridSize) * gridSize;
    }
    render();
});

window.addEventListener('mouseup', () => { 
    isDragging = false; 
    isResizing = false; 
    resizeCorner = '';
});

window.moveLayer = function(direction) {
    if (!selectedShape) return;
    const idx = shapes.indexOf(selectedShape);
    if (direction === 'up' && idx < shapes.length - 1) {
        [shapes[idx], shapes[idx+1]] = [shapes[idx+1], shapes[idx]];
    } else if (direction === 'down' && idx > 0) {
        [shapes[idx], shapes[idx-1]] = [shapes[idx-1], shapes[idx]];
    }
    render();
};

window.clearCanvas = function() {
    if (confirm("Clear your workspace?")) {
        shapes = [];
        selectedShape = null;
        updateSidebar();
        render();
    }
};

function updateSidebar() {
    selectionPanel.style.display = selectedShape ? 'block' : 'none';
    if (selectedShape && selectedShape.type === 'text') {
        textInput.value = selectedShape.text;
        document.getElementById('text-group').style.display = 'block';
    } else {
        document.getElementById('text-group').style.display = 'none';
    }
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

function render() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
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

        if (s === selectedShape) {
            // Dash border
            ctx.strokeStyle = '#ff00cc';
            ctx.lineWidth = 2;
            ctx.setLineDash([5, 5]);
            ctx.strokeRect(s.x, s.y, s.w, s.h);
            ctx.setLineDash([]);
            
            // Resize handles
            ctx.fillStyle = '#ff00cc';
            const h = 10; 
            ctx.fillRect(s.x - h/2, s.y - h/2, h, h); 
            ctx.fillRect(s.x + s.w - h/2, s.y - h/2, h, h); 
            ctx.fillRect(s.x - h/2, s.y + s.h - h/2, h, h); 
            ctx.fillRect(s.x + s.w - h/2, s.y + s.h - h/2, h, h); 

            // X button
            ctx.fillStyle = '#ff4444'; // Red
            ctx.beginPath();
            ctx.arc(s.x + s.w, s.y, 12, 0, Math.PI * 2);
            ctx.fill();
            
            ctx.fillStyle = 'white';
            ctx.font = 'bold 14px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('×', s.x + s.w, s.y);
        }
    });
}

window.postCollageToGallery = function() {
    if (!canvas || !canvas.width || !canvas.height) {
        alert('Canvas is not ready yet. Please try again in a moment.');
        return;
    }

    const hasContent = shapes.length > 0;
    if (!hasContent) {
        const proceed = confirm('Your collage is empty. Post a blank canvas anyway?');
        if (!proceed) {
            return;
        }
    }

    const imageData = canvas.toDataURL('image/png');
    const input = document.getElementById('collageImageInput');
    const form = document.getElementById('collageForm');

    if (!input || !form) {
        alert('Posting form is missing. Please refresh and try again.');
        return;
    }

    input.value = imageData;
    form.submit();
};