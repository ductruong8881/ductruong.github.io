# Creating a zip file containing the upgraded drawing app (index.html + README).
from pathlib import Path
import zipfile, textwrap, os, html

out_dir = Path("/mnt/data/drawing_app")
out_dir.mkdir(parents=True, exist_ok=True)

index_html = r"""<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>🔥 Advanced Drawing Board</title>
<style>
  :root{
    --toolbar-bg: rgba(20,20,20,0.80);
    --accent: #ffcc00;
    --ui-color: #f0f0f0;
    --panel-bg: rgba(30,30,30,0.85);
  }
  html,body{height:100%;margin:0;background:#111;color:var(--ui-color);font-family:system-ui,Segoe UI,Roboto,"Helvetica Neue",Arial;-webkit-font-smoothing:antialiased;}
  .toolbar{
    position:fixed;top:12px;left:50%;transform:translateX(-50%);background:var(--toolbar-bg);padding:8px 10px;border-radius:10px;display:flex;gap:8px;align-items:center;z-index:1000;box-shadow:0 6px 20px rgba(0,0,0,0.6);backdrop-filter: blur(6px);flex-wrap:wrap;max-width:95vw;transition:opacity .25s ease;
  }
  .toolbar.hidden{opacity:0;pointer-events:none}
  .toolbar *{color:var(--ui-color);font-size:14px}
  .toolbtn{background:transparent;border:1px solid rgba(255,255,255,0.07);color:var(--ui-color);padding:6px 8px;border-radius:8px;cursor:pointer;user-select:none}
  .toolbtn.active{border-color:var(--accent);box-shadow:0 0 8px rgba(255,204,0,0.14)}
  input[type="color"]{width:38px;height:34px;border-radius:6px;border:none;padding:0;margin:0;cursor:pointer}
  .range{width:120px}.small{width:70px}.sep{width:1px;height:28px;background:rgba(255,255,255,0.06);margin:0 6px;border-radius:2px}

  #canvasWrap{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background-color:white;background-position:center center;background-repeat:no-repeat;background-size:contain;z-index:0}
  canvas{position:relative;width:100%;height:100%;display:block;touch-action:none;cursor:crosshair;z-index:1;background:transparent}

  .hints{position:fixed;left:12px;bottom:12px;background:rgba(0,0,0,0.45);padding:8px 10px;border-radius:8px;font-size:13px;color:#ddd;z-index:1000;transition:opacity .3s}
  .hints.hidden{display:none}

  #toggleToolbarBtn,#toggleHintsBtn{position:fixed;top:12px;background:var(--toolbar-bg);border:none;color:var(--ui-color);font-size:20px;padding:6px 10px;border-radius:8px;cursor:pointer;z-index:1100;user-select:none;box-shadow:0 3px 10px rgba(0,0,0,0.5);transition:background-color .3s}
  #toggleToolbarBtn{right:12px}#toggleHintsBtn{right:60px}
  #toggleToolbarBtn:hover,#toggleHintsBtn:hover{background-color:rgba(255,204,0,0.9);color:#111}

  /* Panel for text effects */
  .panel{
    position:fixed;right:12px;top:60px;background:var(--panel-bg);padding:10px;border-radius:10px;box-shadow:0 6px 20px rgba(0,0,0,.6);z-index:1001;display:flex;flex-direction:column;gap:8px;min-width:180px
  }
  .panel.hidden{display:none}
  .panel label{font-size:13px;display:flex;align-items:center;justify-content:space-between;gap:8px}
  .preset-row{display:flex;gap:6px;flex-wrap:wrap}

  /* Shape palette */
  .shape-palette{display:flex;gap:6px;align-items:center}
  .shape-btn{width:36px;height:36px;border-radius:6px;border:1px solid rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:center;cursor:pointer;background:transparent}
  .shape-btn.active{box-shadow:0 0 8px rgba(255,204,0,0.12);border-color:var(--accent)}

  @media (max-width:640px){
    .toolbar{left:12px;transform:none;max-width:calc(100% - 24px)}.range{width:90px}
    .panel{right:8px;top:78px;min-width:150px}
  }
</style>
</head>
<body>
<button id="toggleToolbarBtn" title="Ẩn/Hiện thanh công cụ">🛠️</button>
<button id="toggleHintsBtn" title="Ẩn phím tắt">⌨️</button>

<div class="toolbar" role="toolbar" aria-label="Drawing toolbar">
  <button class="toolbtn" id="penBtn" title="Pen (P)">✏️</button>
  <button class="toolbtn" id="eraserBtn" title="Eraser (E)">🧽</button>
  <div class="sep" aria-hidden="true"></div>

  <label title="Màu"><input type="color" id="color" value="#000000"></label>
  <label title="Độ dày"><input type="range" id="size" min="1" max="200" value="6" class="range"></label>
  <label title="Độ mờ"><input type="range" id="alpha" min="0.05" max="1" step="0.05" value="1" class="small"></label>

  <div class="sep" aria-hidden="true"></div>

  <button class="toolbtn" id="undoBtn" title="Undo (Ctrl+Z)">↶</button>
  <button class="toolbtn" id="redoBtn" title="Redo (Ctrl+Y)">↷</button>

  <div class="sep" aria-hidden="true"></div>

  <button class="toolbtn" id="clearBtn" title="Clear (C)">🗑️</button>
  <button class="toolbtn" id="saveBtn" title="Save PNG (S)">💾</button>

  <div class="sep" aria-hidden="true"></div>

  <button class="toolbtn" id="bucketBtn" title="Bucket (H)">🪣</button>
  <button class="toolbtn" id="shapeToggleBtn" title="Shapes">▦</button>

  <div class="shape-palette" id="shapePalette" style="display:none">
    <div class="shape-btn" data-shape="rect" title="Rectangle">🟦</div>
    <div class="shape-btn" data-shape="circle" title="Circle">⚪</div>
    <div class="shape-btn" data-shape="star" title="Star">⭐</div>
    <div class="shape-btn" data-shape="heart" title="Heart">❤️</div>
  </div>

  <button class="toolbtn" id="textBtn" title="Text Tool (T)">🅣</button>

  <div class="sep" aria-hidden="true"></div>

  <button class="toolbtn" id="fullscreenBtn" title="Fullscreen (F)">⤢</button>

  <div class="sep" aria-hidden="true"></div>

  <label style="display:flex; align-items:center; gap:6px; user-select:none; font-size:13px;" title="Chọn nền">
    <input type="radio" name="bgmode" id="bgWhite" value="white" checked> Nền trắng
  </label>
  <label style="display:flex; align-items:center; gap:6px; user-select:none; font-size:13px;" title="Chọn ảnh nền">
    <input type="radio" name="bgmode" id="bgImage" value="image"> Ảnh nền
  </label>
  <input type="file" id="bgFile" accept="image/*" style="display:none" title="Chọn ảnh nền">
</div>

<div id="canvasWrap">
  <canvas id="board"></canvas>
</div>

<div class="hints">
  Phím tắt: P Pen, E Eraser, C Clear, S Save, F Fullscreen, Ctrl/Cmd+Z Undo, Ctrl/Cmd+Y Redo, T Text, H Bucket, 1-4 Shapes, B White BG, I Image BG, +/- brush size
</div>

<!-- Text effect panel -->
<div class="panel hidden" id="textPanel" aria-hidden="true">
  <label>Font: <select id="fontSelect"><option>Arial</option><option>Verdana</option><option>Georgia</option><option>Impact</option><option>Times New Roman</option></select></label>
  <label>Size: <input id="textSize" type="range" min="12" max="200" value="48"></label>
  <label>Color: <input type="color" id="textColor" value="#ffffff"></label>
  <label>Effect:
    <select id="textEffect">
      <option value="fill">Fill</option>
      <option value="stroke">Stroke</option>
      <option value="shadow">Shadow</option>
      <option value="neon">Neon</option>
      <option value="gradient">Gradient</option>
    </select>
  </label>
  <div class="preset-row">
    <button class="toolbtn" id="presetStroke">🖊️ Viền</button>
    <button class="toolbtn" id="presetNeon">✨ Neon</button>
    <button class="toolbtn" id="presetGrad">🌈 Gradient</button>
    <button class="toolbtn" id="presetShadow">🕶️ Shadow</button>
  </div>
</div>

<script>
(() => {
  // Elements
  const canvas = document.getElementById('board');
  const ctx = canvas.getContext('2d');
  const canvasWrap = document.getElementById('canvasWrap');
  const toolbar = document.querySelector('.toolbar');
  const toggleToolbarBtn = document.getElementById('toggleToolbarBtn');
  const hints = document.querySelector('.hints');
  const toggleHintsBtn = document.getElementById('toggleHintsBtn');

  const penBtn = document.getElementById('penBtn');
  const eraserBtn = document.getElementById('eraserBtn');
  const colorInput = document.getElementById('color');
  const sizeInput = document.getElementById('size');
  const alphaInput = document.getElementById('alpha');

  const undoBtn = document.getElementById('undoBtn');
  const redoBtn = document.getElementById('redoBtn');
  const clearBtn = document.getElementById('clearBtn');
  const saveBtn = document.getElementById('saveBtn');
  const fullscreenBtn = document.getElementById('fullscreenBtn');

  const bucketBtn = document.getElementById('bucketBtn');
  const shapeToggleBtn = document.getElementById('shapeToggleBtn');
  const shapePalette = document.getElementById('shapePalette');
  const shapeButtons = Array.from(document.querySelectorAll('.shape-btn'));

  const textBtn = document.getElementById('textBtn');
  const textPanel = document.getElementById('textPanel');
  const fontSelect = document.getElementById('fontSelect');
  const textSize = document.getElementById('textSize');
  const textColor = document.getElementById('textColor');
  const textEffect = document.getElementById('textEffect');
  const presetStroke = document.getElementById('presetStroke');
  const presetNeon = document.getElementById('presetNeon');
  const presetGrad = document.getElementById('presetGrad');
  const presetShadow = document.getElementById('presetShadow');

  const bgWhiteRadio = document.getElementById('bgWhite');
  const bgImageRadio = document.getElementById('bgImage');
  const bgFileInput = document.getElementById('bgFile');

  // State
  let mode = 'pen'; // pen | eraser | bucket | shape | text
  let currentShape = null; // rect|circle|star|heart
  let brush = { size: parseInt(sizeInput.value,10), color: colorInput.value, alpha: parseFloat(alphaInput.value) };
  let drawing = false;
  let last = {x:0,y:0};
  let pointerId = null;

  // Undo/redo stacks using ImageData (in-memory)
  let undoStack = [];
  let redoStack = [];
  const MAX_STACK = 40;

  // Device pixel ratio handling
  function resizeCanvas(){
    const dpr = window.devicePixelRatio || 1;
    const w = window.innerWidth;
    const h = window.innerHeight;
    // Save current content
    const prev = ctx.getImageData(0,0,canvas.width || 1, canvas.height || 1);
    canvas.width = Math.floor(w * dpr);
    canvas.height = Math.floor(h * dpr);
    canvas.style.width = w + 'px';
    canvas.style.height = h + 'px';
    ctx.setTransform(dpr,0,0,dpr,0,0);
    // restore
    if (prev){
      try{
        ctx.putImageData(prev, 0, 0);
      }catch(e){ /* ignore */ }
    }
  }

  // Save state (not canvas) to localStorage
  function saveUIState(){
    const st = {
      toolbarHidden: toolbar.classList.contains('hidden'),
      hintsHidden: hints.classList.contains('hidden'),
      brush,
      bgMode: bgImageRadio.checked ? 'image' : 'white',
      bgData: canvasWrap.dataset.bg || null
    };
    localStorage.setItem('drawUI', JSON.stringify(st));
  }
  function loadUIState(){
    try{
      const st = JSON.parse(localStorage.getItem('drawUI') || 'null');
      if (!st) return;
      if (st.toolbarHidden) toolbar.classList.add('hidden');
      if (st.hintsHidden) hints.classList.add('hidden');
      if (st.brush){
        brush = st.brush;
        colorInput.value = brush.color;
        sizeInput.value = brush.size;
        alphaInput.value = brush.alpha;
      }
      if (st.bgMode === 'image' && st.bgData){
        setBackgroundImage(st.bgData);
        bgImageRadio.checked = true;
      } else {
        setBackgroundWhite();
        bgWhiteRadio.checked = true;
      }
    }catch(e){}
  }

  // Undo / Redo with ImageData
  function pushUndo(){
    try{
      if (undoStack.length >= MAX_STACK) undoStack.shift();
      const d = ctx.getImageData(0,0,canvas.width,canvas.height);
      undoStack.push(d);
      redoStack = [];
      updateButtons();
    }catch(e){ console.warn('pushUndo err', e); }
  }
  function doUndo(){
    if (!undoStack.length) return;
    const top = undoStack.pop();
    redoStack.push(ctx.getImageData(0,0,canvas.width,canvas.height));
    ctx.putImageData(top, 0, 0);
    updateButtons();
  }
  function doRedo(){
    if (!redoStack.length) return;
    const top = redoStack.pop();
    undoStack.push(ctx.getImageData(0,0,canvas.width,canvas.height));
    ctx.putImageData(top, 0, 0);
    updateButtons();
  }
  function updateButtons(){
    undoBtn.disabled = undoStack.length === 0;
    redoBtn.disabled = redoStack.length === 0;
  }

  // Drawing primitives
  function startDraw(x,y,pressure=1){
    drawing = true; last = {x,y,pressure};
    pushUndo();
    drawLine(x,y,pressure);
  }
  function endDraw(){
    drawing = false; last = {x:0,y:0};
  }
  function drawLine(x,y,pressure=1){
    const sz = brush.size * (pressure || 1);
    ctx.lineCap = 'round'; ctx.lineJoin = 'round';
    if (mode === 'eraser'){
      ctx.globalCompositeOperation = 'destination-out';
      ctx.globalAlpha = 1.0;
      ctx.strokeStyle = 'rgba(0,0,0,1)';
      ctx.lineWidth = sz;
    } else {
      ctx.globalCompositeOperation = 'source-over';
      ctx.globalAlpha = brush.alpha;
      ctx.strokeStyle = brush.color;
      ctx.lineWidth = sz;
    }
    ctx.beginPath();
    ctx.moveTo(last.x,last.y);
    ctx.lineTo(x,y);
    ctx.stroke();
    last.x = x; last.y = y; last.pressure = pressure;
  }

  // Shapes
  function drawShape(shape, x, y, w, h, opts = {}){
    ctx.save();
    ctx.globalCompositeOperation = 'source-over';
    ctx.globalAlpha = 1.0;
    ctx.fillStyle = opts.fill || brush.color;
    ctx.strokeStyle = opts.stroke || brush.color;
    ctx.lineWidth = opts.strokeWidth || 2;
    ctx.beginPath();
    if (shape === 'rect'){
      ctx.roundRect ? ctx.roundRect(x, y, w, h, 8) : ctx.rect(x, y, w, h);
      ctx.fill();
    } else if (shape === 'circle'){
      const cx = x + w/2, cy = y + h/2, r = Math.min(w,h)/2;
      ctx.arc(cx, cy, r, 0, Math.PI*2);
      ctx.fill();
    } else if (shape === 'star'){
      // draw a 5-point star
      const cx = x + w/2, cy = y + h/2, r = Math.min(w,h)/2;
      const spikes = 5, outer = r, inner = r*0.5;
      let rot = Math.PI/2*3;
      ctx.moveTo(cx, cy - outer);
      for (let i=0;i<spikes;i++){
        let x1 = cx + Math.cos(rot) * outer;
        let y1 = cy + Math.sin(rot) * outer;
        ctx.lineTo(x1,y1);
        rot += Math.PI / spikes;
        let x2 = cx + Math.cos(rot) * inner;
        let y2 = cy + Math.sin(rot) * inner;
        ctx.lineTo(x2,y2);
        rot += Math.PI / spikes;
      }
      ctx.closePath();
      ctx.fill();
    } else if (shape === 'heart'){
      const cx = x + w/2, cy = y + h/2;
      const scale = Math.min(w,h)/100;
      ctx.translate(cx, cy);
      ctx.beginPath();
      ctx.moveTo(0, -30*scale);
      ctx.bezierCurveTo(25*scale, -80*scale, 140*scale, -35*scale, 0, 60*scale);
      ctx.bezierCurveTo(-140*scale, -35*scale, -25*scale, -80*scale, 0, -30*scale);
      ctx.fill();
      ctx.setTransform(1,0,0,1,0,0); // reset transform
    }
    ctx.restore();
  }

  // Flood fill (bucket) - simple stack algorithm
  function colorsMatch(a, b){
    return a[0]===b[0] && a[1]===b[1] && a[2]===b[2] && a[3]===b[3];
  }
  function floodFill(x, y, fillColor){
    const dpr = window.devicePixelRatio || 1;
    const px = Math.floor(x * dpr);
    const py = Math.floor(y * dpr);
    const w = canvas.width;
    const h = canvas.height;
    const img = ctx.getImageData(0,0,w,h);
    const data = img.data;
    const idx = (py * w + px) * 4;
    const targetColor = [data[idx], data[idx+1], data[idx+2], data[idx+3]];
    const replacement = [
      Math.round(parseInt(fillColor.slice(1,3),16)),
      Math.round(parseInt(fillColor.slice(3,5),16)),
      Math.round(parseInt(fillColor.slice(5,7),16)),
      255
    ];
    if (colorsMatch(targetColor, replacement)) return;
    const stack = [[px,py]];
    while(stack.length){
      const [cx,cy] = stack.pop();
      let current = (cy * w + cx) * 4;
      // move up
      while(cy>=0 && colorsMatch([data[current],data[current+1],data[current+2],data[current+3]], targetColor)){
        cy--;
        current -= w*4;
      }
      cy++;
      current += w*4;
      let reachLeft=false, reachRight=false;
      while(cy<h && colorsMatch([data[current],data[current+1],data[current+2],data[current+3]], targetColor)){
        // color it
        data[current] = replacement[0];
        data[current+1] = replacement[1];
        data[current+2] = replacement[2];
        data[current+3] = replacement[3];
        // left
        if (cx-1 >=0){
          const li = (cy * w + (cx-1)) * 4;
          if (colorsMatch([data[li],data[li+1],data[li+2],data[li+3]], targetColor)){
            stack.push([cx-1, cy]);
          }
        }
        // right
        if (cx+1 < w){
          const ri = (cy * w + (cx+1)) * 4;
          if (colorsMatch([data[ri],data[ri+1],data[ri+2],data[ri+3]], targetColor)){
            stack.push([cx+1, cy]);
          }
        }
        cy++;
        current += w*4;
      }
    }
    ctx.putImageData(img, 0, 0);
  }

  // Text tool - opens prompt and draws text with effect
  function placeText(x,y){
    const txt = prompt("Nhập chữ:", "Hello");
    if (!txt) return;
    pushUndo();
    const font = textSize.value + "px " + fontSelect.value;
    ctx.save();
    ctx.font = font;
    ctx.textBaseline = 'top';
    const metrics = ctx.measureText(txt);
    const textW = metrics.width;
    const tx = x;
    const ty = y;
    // effects
    const ef = textEffect.value;
    if (ef === 'stroke'){
      ctx.lineWidth = Math.max(2, Math.floor(textSize.value/12));
      ctx.strokeStyle = textColor.value;
      ctx.strokeText(txt, tx, ty);
    } else if (ef === 'shadow'){
      ctx.fillStyle = textColor.value;
      ctx.shadowColor = 'rgba(0,0,0,0.7)';
      ctx.shadowBlur = 12;
      ctx.shadowOffsetX = 6;
      ctx.shadowOffsetY = 6;
      ctx.fillText(txt, tx, ty);
    } else if (ef === 'neon'){
      ctx.fillStyle = textColor.value;
      ctx.shadowColor = textColor.value;
      ctx.shadowBlur = 30;
      ctx.fillText(txt, tx, ty);
      ctx.shadowBlur = 0;
      ctx.globalAlpha = 0.9;
      ctx.fillText(txt, tx, ty);
    } else if (ef === 'gradient'){
      const g = ctx.createLinearGradient(tx, ty, tx + textW, ty);
      g.addColorStop(0, textColor.value);
      g.addColorStop(1, '#ffff00');
      ctx.fillStyle = g;
      ctx.fillText(txt, tx, ty);
    } else {
      ctx.fillStyle = textColor.value;
      ctx.fillText(txt, tx, ty);
    }
    ctx.restore();
  }

  // Background helpers
  function setBackgroundWhite(){
    canvasWrap.style.backgroundColor = 'white';
    canvasWrap.style.backgroundImage = '';
    delete canvasWrap.dataset.bg;
    saveUIState();
  }
  function setBackgroundImage(url){
    canvasWrap.style.backgroundColor = 'transparent';
    canvasWrap.style.backgroundImage = `url(${url})`;
    canvasWrap.dataset.bg = url;
    saveUIState();
  }

  // Pointer handling
  function getLocalPos(e){
    const rect = canvas.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    return {x,y};
  }

  function onPointerDown(e){
    if (e.pointerType === 'mouse' && e.button !== 0) return;
    pointerId = e.pointerId;
    canvas.setPointerCapture(pointerId);
    const pos = getLocalPos(e);
    if (mode === 'pen' || mode === 'eraser'){
      startDraw(pos.x, pos.y, e.pressure || 1);
    } else if (mode === 'bucket'){
      pushUndo();
      floodFill(pos.x, pos.y, colorInput.value);
    } else if (mode === 'shape' && currentShape){
      pushUndo();
      // place shape centered around click
      const size = Math.min(window.innerWidth, window.innerHeight) * 0.2;
      const x = pos.x - size/2, y = pos.y - size/2;
      drawShape(currentShape, x, y, size, size, {fill: colorInput.value});
    } else if (mode === 'text'){
      placeText(pos.x, pos.y);
    }
    e.preventDefault();
  }
  function onPointerMove(e){
    if (!drawing || e.pointerId !== pointerId) return;
    const pos = getLocalPos(e);
    drawLine(pos.x, pos.y, e.pressure || 1);
    e.preventDefault();
  }
  function onPointerUp(e){
    if (pointerId !== e.pointerId) return;
    canvas.releasePointerCapture(pointerId);
    pointerId = null;
    endDraw();
    e.preventDefault();
  }

  canvas.addEventListener('pointerdown', onPointerDown);
  canvas.addEventListener('pointermove', onPointerMove);
  canvas.addEventListener('pointerup', onPointerUp);
  canvas.addEventListener('pointercancel', onPointerUp);
  canvas.addEventListener('pointerout', onPointerUp);
  canvas.addEventListener('pointerleave', onPointerUp);

  // UI wiring
  penBtn.addEventListener('click', ()=>{ mode='pen'; penBtn.classList.add('active'); eraserBtn.classList.remove('active'); bucketBtn.classList.remove('active'); textBtn.classList.remove('active'); shapeToggleBtn.classList.remove('active'); textPanel.classList.add('hidden'); currentShape=null; });
  eraserBtn.addEventListener('click', ()=>{ mode='eraser'; eraserBtn.classList.add('active'); penBtn.classList.remove('active'); bucketBtn.classList.remove('active'); textBtn.classList.remove('active'); shapeToggleBtn.classList.remove('active'); textPanel.classList.add('hidden'); currentShape=null; });
  bucketBtn.addEventListener('click', ()=>{ mode='bucket'; bucketBtn.classList.add('active'); penBtn.classList.remove('active'); eraserBtn.classList.remove('active'); textBtn.classList.remove('active'); shapeToggleBtn.classList.remove('active'); textPanel.classList.add('hidden'); currentShape=null; });

  sizeInput.addEventListener('input', ()=>{ brush.size = parseInt(sizeInput.value,10); });
  colorInput.addEventListener('input', ()=>{ brush.color = colorInput.value; });
  alphaInput.addEventListener('input', ()=>{ brush.alpha = parseFloat(alphaInput.value); });

  clearBtn.addEventListener('click', ()=>{ pushUndo(); ctx.clearRect(0,0,canvas.width,canvas.height); updateButtons(); });

  undoBtn.addEventListener('click', doUndo);
  redoBtn.addEventListener('click', doRedo);

  saveBtn.addEventListener('click', ()=>{ const a = document.createElement('a'); a.href = canvas.toDataURL('image/png'); a.download = `drawing_${Date.now()}.png`; document.body.appendChild(a); a.click(); document.body.removeChild(a); });

  fullscreenBtn.addEventListener('click', ()=>{ if (!document.fullscreenElement) document.documentElement.requestFullscreen().catch(()=>{}); else document.exitFullscreen().catch(()=>{}); });

  shapeToggleBtn.addEventListener('click', ()=>{ shapePalette.style.display = shapePalette.style.display === 'none' ? 'flex' : 'none'; });

  shapeButtons.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      shapeButtons.forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      currentShape = btn.dataset.shape;
      mode = 'shape';
      penBtn.classList.remove('active'); eraserBtn.classList.remove('active'); bucketBtn.classList.remove('active'); textBtn.classList.remove('active');
      textPanel.classList.add('hidden');
    });
  });

  // Text tool
  textBtn.addEventListener('click', ()=>{ mode='text'; textPanel.classList.toggle('hidden'); textBtn.classList.add('active'); penBtn.classList.remove('active'); eraserBtn.classList.remove('active'); bucketBtn.classList.remove('active'); shapeToggleBtn.classList.remove('active'); currentShape=null; });

  presetStroke.addEventListener('click', ()=>{ textEffect.value='stroke'; textColor.value='#000000'; textSize.value = 48; fontSelect.value='Impact'; });
  presetNeon.addEventListener('click', ()=>{ textEffect.value='neon'; textColor.value='#ff2d95'; textSize.value = 64; fontSelect.value='Impact'; });
  presetGrad.addEventListener('click', ()=>{ textEffect.value='gradient'; textColor.value='#00bfff'; textSize.value = 56; fontSelect.value='Georgia'; });
  presetShadow.addEventListener('click', ()=>{ textEffect.value='shadow'; textColor.value='#ffffff'; textSize.value = 48; fontSelect.value='Verdana'; });

  // Toggle toolbar/hints
  toggleToolbarBtn.addEventListener('click', ()=>{ const hidden = toolbar.classList.toggle('hidden'); toggleToolbarBtn.textContent = hidden ? '👁️' : '🛠️'; saveUIState(); });
  toggleHintsBtn.addEventListener('click', ()=>{ const hidden = hints.classList.toggle('hidden'); toggleHintsBtn.textContent = hidden ? '🚫' : '⌨️'; saveUIState(); });

  // Background file input
  bgFileInput.addEventListener('change', (e)=>{
    const file = e.target.files[0]; if (!file) return;
    const reader = new FileReader();
    reader.onload = ()=>{ setBackgroundImage(reader.result); bgImageRadio.checked = true; saveUIState(); }
    reader.readAsDataURL(file);
  });
  bgWhiteRadio.addEventListener('change', ()=>{ if (bgWhiteRadio.checked) setBackgroundWhite(); });
  bgImageRadio.addEventListener('change', ()=>{ if (bgImageRadio.checked) bgFileInput.click(); });

  // Keyboard shortcuts
  window.addEventListener('keydown', (e)=>{
    const mod = (navigator.platform.match('Mac') ? e.metaKey : e.ctrlKey);
    if (mod && e.key.toLowerCase() === 'z'){ e.preventDefault(); doUndo(); }
    else if (mod && e.key.toLowerCase() === 'y'){ e.preventDefault(); doRedo(); }
    else if (e.key.toLowerCase() === 's'){ e.preventDefault(); saveBtn.click(); }
    else if (e.key.toLowerCase() === 'c'){ e.preventDefault(); pushUndo(); ctx.clearRect(0,0,canvas.width,canvas.height); updateButtons(); }
    else if (e.key.toLowerCase() === 'p'){ penBtn.click(); mode='pen'; }
    else if (e.key.toLowerCase() === 'e'){ eraserBtn.click(); mode='eraser'; }
    else if (e.key.toLowerCase() === 'f'){ e.preventDefault(); fullscreenBtn.click(); }
    else if (e.key.toLowerCase() === 't'){ textBtn.click(); }
    else if (e.key.toLowerCase() === 'h'){ bucketBtn.click(); }
    else if (e.key === '+'){ sizeInput.value = Math.min(200, parseInt(sizeInput.value)+2); sizeInput.dispatchEvent(new Event('input')); }
    else if (e.key === '-'){ sizeInput.value = Math.max(1, parseInt(sizeInput.value)-2); sizeInput.dispatchEvent(new Event('input')); }
    else if (e.key.toLowerCase() === 'b'){ bgWhiteRadio.checked = true; setBackgroundWhite(); }
    else if (e.key.toLowerCase() === 'i'){ bgImageRadio.checked = true; bgFileInput.click(); }
    else if (['1','2','3','4'].includes(e.key)){ const idx = parseInt(e.key)-1; shapeButtons[idx].click(); }
  });

  // Prevent scroll on touch
  ['touchstart','touchmove','touchend','touchcancel'].forEach(ev=>{ canvas.addEventListener(ev, e=>e.preventDefault(), {passive:false}); });

  // Init
  function init(){
    resizeCanvas();
    pushUndo(); // initial snapshot
    loadUIState();
    updateButtons();
  }
  window.addEventListener('resize', ()=>{ // preserve drawing across resize
    try{
      const cur = ctx.getImageData(0,0,canvas.width,canvas.height);
      resizeCanvas();
      ctx.putImageData(cur, 0, 0);
    }catch(e){ resizeCanvas(); }
  });
  init();

})();
</script>
</body>
</html>
"""

readme = """Drawing App - Full feature single-file demo
===========================================
Files:
 - index.html : main app (open in browser)

Features included:
 - Pen / Eraser with pressure support
 - Undo / Redo implemented with ImageData stacks
 - Shape templates (rect, circle, star, heart)
 - Bucket (flood fill) tool
 - Text tool with simple effects (stroke, shadow, neon, gradient)
 - Save PNG, Clear, Fullscreen
 - Remember UI state (toolbar/hints/bg/mode) in localStorage
 - Keyboard shortcuts: P,E,T,H,1-4,B,I,+,-,Ctrl+Z/Ctrl+Y,...

How to use:
 - Unzip and open index.html in a modern browser (Chrome, Edge, Firefox).
 - For image background: click "Ảnh nền" then pick a file.
"""

# Write files
(index_html_path := out_dir / "index.html").write_text(index_html, encoding="utf-8")
(out_readme := out_dir / "README.txt").write_text(readme, encoding="utf-8")

# Create zip
zip_path = Path("/mnt/data/drawing_app.zip")
with zipfile.ZipFile(zip_path, "w", compression=zipfile.ZIP_DEFLATED) as zf:
    zf.write(index_html_path, arcname="index.html")
    zf.write(out_readme, arcname="README.txt")

zip_path_str = str(zip_path)
zip_path_str

