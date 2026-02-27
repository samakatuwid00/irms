/**
 * ImageCropperModal
 *
 * Features:
 * - Zoom in / out with buttons and mouse wheel (pivot on cursor)
 * - Pan the image by dragging on empty / image area (outside crop box)
 * - Draw a NEW crop box by dragging anywhere on the image
 * - Move the crop box by dragging inside it
 * - Resize via 8 edge + corner handles
 * - Compress output to < 100 KB (JPEG quality iteration ONLY — original dimensions preserved)
 * - Returns a File via Promise; null if cancelled
 */
export class ImageCropperModal {
    constructor() {
        this._modal        = null;
        this._canvas       = null;
        this._ctx          = null;
        this._image        = null;
        this._originalFile = null;
        this._resolvePromise = null;

        // Viewport transform
        this._zoom = 1;
        this._panX = 0;
        this._panY = 0;

        // Crop box in canvas-display-space (null = not drawn yet)
        this._crop = null; // { x, y, w, h }

        // Interaction state
        this._mode         = 'idle'; // idle | panning | drawing | moving | resizing
        this._resizeHandle = null;
        this._saved        = {};     // snapshot at pointer-down
        this._drawStart    = null;

        this._canvasW = 0;
        this._canvasH = 0;

        this._injectStyles();
        this._buildModal();
    }

    /* ────────────────────────────────────────────
       Public API
    ──────────────────────────────────────────── */

    /**
     * Open the modal for the given File.
     * @param {File} file
     * @returns {Promise<File|null>}  null = user cancelled
     */
    open(file) {
        return new Promise((resolve) => {
            this._resolvePromise = resolve;
            this._originalFile  = file;
            this._loadFile(file);
        });
    }

    /* ────────────────────────────────────────────
       Build DOM
    ──────────────────────────────────────────── */

    _buildModal() {
        const el = document.createElement('div');
        el.id = 'icpModal';
        el.className = 'icp-overlay hidden';
        el.innerHTML = `
            <div class="icp-dialog">

                <div class="icp-header">
                    <div class="icp-header-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>
                        </svg>
                        Crop &amp; Compress Image
                    </div>
                    <button type="button" id="icpCloseBtn" class="icp-close-btn" title="Cancel">&times;</button>
                </div>

                <div class="icp-hint">
                    <span>🔍 <b>Scroll / buttons</b> to zoom</span>
                    <span>✋ <b>Drag image</b> to pan</span>
                    <span>✂️ <b>Drag on image</b> to draw crop</span>
                    <span>↔️ <b>Drag handles</b> to resize</span>
                </div>

                <div class="icp-canvas-wrap">
                    <canvas id="icpCanvas"></canvas>
                </div>

                <div class="icp-footer">
                    <div class="icp-zoom-bar">
                        <button type="button" id="icpZoomOut" title="Zoom out">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                        </button>
                        <span id="icpZoomLabel">100%</span>
                        <button type="button" id="icpZoomIn" title="Zoom in">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                        </button>
                        <button type="button" id="icpZoomFit" class="icp-fit-btn">Fit</button>
                    </div>

                    <div class="icp-info">
                        <span id="icpDimInfo">Draw a crop area on the image</span>
                        <span id="icpSizeInfo"></span>
                    </div>

                    <div class="icp-actions">
                        <button type="button" id="icpCancelBtn"  class="icp-btn-secondary">Cancel</button>
                        <button type="button" id="icpResetBtn"   class="icp-btn-secondary">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.3"/></svg>
                            Reset
                        </button>
                        <button type="button" id="icpApplyBtn"   class="icp-btn-primary">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            Apply &amp; Compress
                        </button>
                    </div>
                </div>

            </div>
        `;

        document.body.appendChild(el);
        this._modal  = el;
        this._canvas = document.getElementById('icpCanvas');
        this._ctx    = this._canvas.getContext('2d');

        // Button listeners
        document.getElementById('icpCloseBtn').addEventListener('click',  () => this._cancel());
        document.getElementById('icpCancelBtn').addEventListener('click', () => this._cancel());
        document.getElementById('icpResetBtn').addEventListener('click',  () => { this._crop = null; this._draw(); this._updateInfo(); });
        document.getElementById('icpApplyBtn').addEventListener('click',  () => this._apply());
        document.getElementById('icpZoomIn').addEventListener('click',    () => this._zoomBy(0.15));
        document.getElementById('icpZoomOut').addEventListener('click',   () => this._zoomBy(-0.15));
        document.getElementById('icpZoomFit').addEventListener('click',   () => this._fitImage());

        // Canvas pointer listeners
        this._canvas.addEventListener('mousedown',  (e) => this._onDown(e));
        this._canvas.addEventListener('mousemove',  (e) => this._onMove(e));
        this._canvas.addEventListener('mouseup',    ()  => this._onUp());
        this._canvas.addEventListener('mouseleave', ()  => this._onUp());
        this._canvas.addEventListener('wheel',      (e) => this._onWheel(e), { passive: false });

        this._canvas.addEventListener('touchstart', (e) => this._onDown(e), { passive: false });
        this._canvas.addEventListener('touchmove',  (e) => this._onMove(e), { passive: false });
        this._canvas.addEventListener('touchend',   ()  => this._onUp());
    }

    /* ────────────────────────────────────────────
       File loading
    ──────────────────────────────────────────── */

    _loadFile(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
                this._image = img;
                this._crop  = null;
                this._setupCanvas();
                this._fitImage();
                this._show();
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    _setupCanvas() {
        const maxW = Math.min(780, window.innerWidth  - 48);
        const maxH = Math.min(500, window.innerHeight - 230);
        this._canvasW = this._canvas.width  = maxW;
        this._canvasH = this._canvas.height = maxH;
    }

    /* ────────────────────────────────────────────
       Zoom & Pan
    ──────────────────────────────────────────── */

    _fitImage() {
        const img    = this._image;
        const scaleW = this._canvasW / img.naturalWidth;
        const scaleH = this._canvasH / img.naturalHeight;
        this._zoom   = Math.min(scaleW, scaleH, 1);
        // Centre image
        this._panX   = (this._canvasW - img.naturalWidth  * this._zoom) / 2;
        this._panY   = (this._canvasH - img.naturalHeight * this._zoom) / 2;
        this._clampPan();
        this._draw();
        this._updateZoomLabel();
    }

    _zoomBy(delta, pivotX, pivotY) {
        const oldZoom = this._zoom;
        const newZoom = Math.min(10, Math.max(0.05, oldZoom + delta));
        if (newZoom === oldZoom) return;
        const px = pivotX ?? this._canvasW / 2;
        const py = pivotY ?? this._canvasH / 2;
        // Zoom toward pivot
        this._panX = px - (px - this._panX) * (newZoom / oldZoom);
        this._panY = py - (py - this._panY) * (newZoom / oldZoom);
        this._zoom = newZoom;
        this._clampPan();
        this._draw();
        this._updateZoomLabel();
    }

    _clampPan() {
        const imgW   = this._image.naturalWidth  * this._zoom;
        const imgH   = this._image.naturalHeight * this._zoom;
        const margin = 50; // allow scrolling image slightly off-screen
        this._panX   = Math.max(margin - imgW, Math.min(this._canvasW - margin, this._panX));
        this._panY   = Math.max(margin - imgH, Math.min(this._canvasH - margin, this._panY));
    }

    _updateZoomLabel() {
        const el = document.getElementById('icpZoomLabel');
        if (el) el.textContent = Math.round(this._zoom * 100) + '%';
    }

    /* ────────────────────────────────────────────
       Coordinate conversion
    ──────────────────────────────────────────── */

    _toImage(cx, cy) {
        return { x: (cx - this._panX) / this._zoom, y: (cy - this._panY) / this._zoom };
    }

    /* ────────────────────────────────────────────
       Drawing
    ──────────────────────────────────────────── */

    _draw() {
        const ctx = this._ctx;
        const cw  = this._canvasW, ch = this._canvasH;

        ctx.clearRect(0, 0, cw, ch);
        this._drawCheckerboard();

        // Image
        ctx.save();
        ctx.translate(this._panX, this._panY);
        ctx.scale(this._zoom, this._zoom);
        ctx.drawImage(this._image, 0, 0);
        ctx.restore();

        if (!this._crop) return;

        const { x, y, w, h } = this._crop;

        // Dim overlay
        ctx.fillStyle = 'rgba(0,0,0,0.55)';
        ctx.fillRect(0, 0, cw, ch);

        // Clip: redraw image inside crop area (undimmed)
        ctx.save();
        ctx.beginPath();
        ctx.rect(x, y, w, h);
        ctx.clip();
        ctx.translate(this._panX, this._panY);
        ctx.scale(this._zoom, this._zoom);
        ctx.drawImage(this._image, 0, 0);
        ctx.restore();

        // Crop border
        ctx.strokeStyle = '#3B82F6';
        ctx.lineWidth   = 1.5;
        ctx.strokeRect(x + 0.5, y + 0.5, w, h);

        // Rule-of-thirds grid
        ctx.strokeStyle = 'rgba(255,255,255,0.28)';
        ctx.lineWidth   = 1;
        for (let i = 1; i <= 2; i++) {
            ctx.beginPath(); ctx.moveTo(x + w/3*i, y);   ctx.lineTo(x + w/3*i, y+h); ctx.stroke();
            ctx.beginPath(); ctx.moveTo(x, y + h/3*i);   ctx.lineTo(x+w, y + h/3*i); ctx.stroke();
        }

        // Handles
        const HS = 7;
        this._handles(x, y, w, h).forEach(({ hx, hy }) => {
            ctx.fillStyle   = '#fff';
            ctx.strokeStyle = '#3B82F6';
            ctx.lineWidth   = 1.5;
            ctx.fillRect(hx - HS/2, hy - HS/2, HS, HS);
            ctx.strokeRect(hx - HS/2, hy - HS/2, HS, HS);
        });
    }

    _drawCheckerboard() {
        const ctx = this._ctx, s = 14;
        for (let r = 0; r * s < this._canvasH; r++) {
            for (let c = 0; c * s < this._canvasW; c++) {
                this._ctx.fillStyle = (r + c) % 2 === 0 ? '#c8cdd4' : '#e2e5ea';
                this._ctx.fillRect(c*s, r*s, s, s);
            }
        }
    }

    _handles(x, y, w, h) {
        return [
            { id:'nw', hx:x,     hy:y      },
            { id:'n',  hx:x+w/2, hy:y      },
            { id:'ne', hx:x+w,   hy:y      },
            { id:'e',  hx:x+w,   hy:y+h/2  },
            { id:'se', hx:x+w,   hy:y+h    },
            { id:'s',  hx:x+w/2, hy:y+h    },
            { id:'sw', hx:x,     hy:y+h    },
            { id:'w',  hx:x,     hy:y+h/2  },
        ];
    }

    /* ────────────────────────────────────────────
       Hit tests
    ──────────────────────────────────────────── */

    _hitHandle(mx, my) {
        if (!this._crop) return null;
        const { x, y, w, h } = this._crop;
        const tol = 10;
        for (const { id, hx, hy } of this._handles(x, y, w, h)) {
            if (Math.abs(mx - hx) <= tol && Math.abs(my - hy) <= tol) return id;
        }
        return null;
    }

    _insideCrop(mx, my) {
        if (!this._crop) return false;
        const { x, y, w, h } = this._crop;
        return mx > x+8 && mx < x+w-8 && my > y+8 && my < y+h-8;
    }

    _insideImage(mx, my) {
        return (
            mx >= this._panX &&
            mx <= this._panX + this._image.naturalWidth  * this._zoom &&
            my >= this._panY &&
            my <= this._panY + this._image.naturalHeight * this._zoom
        );
    }

    /* ────────────────────────────────────────────
       Pointer events
    ──────────────────────────────────────────── */

    _getPos(e) {
        const rect = this._canvas.getBoundingClientRect();
        const src  = e.touches ? e.touches[0] : e;
        return { x: src.clientX - rect.left, y: src.clientY - rect.top };
    }

    _onDown(e) {
        if (e.type === 'touchstart') e.preventDefault();
        const pos = this._getPos(e);

        const handle = this._hitHandle(pos.x, pos.y);
        if (handle) {
            this._mode         = 'resizing';
            this._resizeHandle = handle;
            this._saved        = { ...pos, crop: { ...this._crop } };
            return;
        }
        if (this._insideCrop(pos.x, pos.y)) {
            this._mode  = 'moving';
            this._saved = { ...pos, crop: { ...this._crop } };
            return;
        }
        if (this._insideImage(pos.x, pos.y)) {
            // Start drawing a new crop (clears old one)
            this._mode      = 'drawing';
            this._drawStart = { ...pos };
            this._crop      = null;
            return;
        }
        // Pan
        this._mode  = 'panning';
        this._saved = { ...pos, panX: this._panX, panY: this._panY };
    }

    _onMove(e) {
        if (e.type === 'touchmove') e.preventDefault();
        const pos = this._getPos(e);
        this._updateCursor(pos);

        const dx = pos.x - (this._saved.x ?? pos.x);
        const dy = pos.y - (this._saved.y ?? pos.y);

        switch (this._mode) {
            case 'panning':
                this._panX = this._saved.panX + dx;
                this._panY = this._saved.panY + dy;
                this._clampPan();
                this._draw();
                break;

            case 'drawing': {
                const ds = this._drawStart;
                const x  = Math.min(ds.x, pos.x);
                const y  = Math.min(ds.y, pos.y);
                const w  = Math.abs(pos.x - ds.x);
                const h  = Math.abs(pos.y - ds.y);
                if (w > 5 || h > 5) {
                    this._crop = { x, y, w, h };
                }
                this._draw();
                this._updateInfo();
                break;
            }

            case 'moving': {
                const { w, h } = this._saved.crop;
                const cw = this._canvasW, ch = this._canvasH;
                let nx   = this._saved.crop.x + dx;
                let ny   = this._saved.crop.y + dy;
                nx = Math.max(0, Math.min(nx, cw - w));
                ny = Math.max(0, Math.min(ny, ch - h));
                this._crop = { x: nx, y: ny, w, h };
                this._draw();
                this._updateInfo();
                break;
            }

            case 'resizing':
                this._doResize(pos);
                break;
        }
    }

    _onUp() {
        this._mode         = 'idle';
        this._resizeHandle = null;
    }

    _onWheel(e) {
        e.preventDefault();
        const pos   = this._getPos(e);
        const delta = e.deltaY < 0 ? 0.14 : -0.14;
        this._zoomBy(delta, pos.x, pos.y);
    }

    _updateCursor(pos) {
        const handle = this._hitHandle(pos.x, pos.y);
        if (handle) {
            const map = { nw:'nwse-resize', se:'nwse-resize', ne:'nesw-resize', sw:'nesw-resize', n:'ns-resize', s:'ns-resize', e:'ew-resize', w:'ew-resize' };
            this._canvas.style.cursor = map[handle] || 'pointer';
        } else if (this._insideCrop(pos.x, pos.y)) {
            this._canvas.style.cursor = 'move';
        } else if (this._insideImage(pos.x, pos.y)) {
            this._canvas.style.cursor = this._mode === 'panning' ? 'grabbing' : 'crosshair';
        } else {
            this._canvas.style.cursor = this._mode === 'panning' ? 'grabbing' : 'grab';
        }
    }

    /* ────────────────────────────────────────────
       Resize
    ──────────────────────────────────────────── */

    _doResize(pos) {
        const MIN   = 20;
        const cw    = this._canvasW, ch = this._canvasH;
        const dx    = pos.x - this._saved.x;
        const dy    = pos.y - this._saved.y;
        let { x, y, w, h } = this._saved.crop;
        const id    = this._resizeHandle;

        if (id.includes('e')) w = Math.max(MIN, Math.min(cw - x, w + dx));
        if (id.includes('s')) h = Math.max(MIN, Math.min(ch - y, h + dy));
        if (id.includes('w')) {
            const nx = Math.max(0, Math.min(x + w - MIN, x + dx));
            w += x - nx; x = nx;
        }
        if (id.includes('n')) {
            const ny = Math.max(0, Math.min(y + h - MIN, y + dy));
            h += y - ny; y = ny;
        }

        this._crop = { x, y, w, h };
        this._draw();
        this._updateInfo();
    }

    /* ────────────────────────────────────────────
       Info bar
    ──────────────────────────────────────────── */

    _updateInfo() {
        const el = document.getElementById('icpDimInfo');
        if (!el) return;
        if (!this._crop) { el.textContent = 'Draw a crop area on the image'; return; }
        const { w, h } = this._crop;
        const natW = Math.round(w / this._zoom);
        const natH = Math.round(h / this._zoom);
        el.textContent = `Crop: ${natW} × ${natH} px`;
    }

    /* ────────────────────────────────────────────
       Apply: crop + compress
    ──────────────────────────────────────────── */

    async _apply() {
        const btn = document.getElementById('icpApplyBtn');
        btn.disabled    = true;
        btn.innerHTML   = '<span class="icp-spinner"></span> Processing…';

        try {
            const file   = await this._cropAndCompress();
            const sizeEl = document.getElementById('icpSizeInfo');
            if (sizeEl) sizeEl.textContent = `Output: ${(file.size / 1024).toFixed(1)} KB`;

            this._hide();
            this._resolvePromise(file);
        } catch (err) {
            console.error(err);
            alert('Failed to process the image. Please try again.');
        } finally {
            btn.disabled  = false;
            btn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Apply & Compress`;
        }
    }

    async _cropAndCompress() {
        const img  = this._image;

        // Convert canvas-space crop → image natural coords
        let natX, natY, natW, natH;
        if (this._crop) {
            const { x, y, w, h } = this._crop;
            const tl = this._toImage(x, y);
            const br = this._toImage(x + w, y + h);
            natX = Math.max(0, Math.round(tl.x));
            natY = Math.max(0, Math.round(tl.y));
            natW = Math.min(img.naturalWidth  - natX, Math.round(br.x - tl.x));
            natH = Math.min(img.naturalHeight - natY, Math.round(br.y - tl.y));
        } else {
            natX = 0; natY = 0;
            natW = img.naturalWidth;
            natH = img.naturalHeight;
        }

        // Draw at native resolution — dimensions are NEVER changed
        const off = document.createElement('canvas');
        off.width  = natW;
        off.height = natH;
        off.getContext('2d').drawImage(img, natX, natY, natW, natH, 0, 0, natW, natH);

        const MAX      = 100 * 1024; // 100 KB hard limit
        const baseName = (this._originalFile?.name || 'image.jpg').replace(/\.[^.]+$/, '');

        // Quality iteration only — NO dimension downscaling
        let quality = 0.92;
        let blob;
        while (quality >= 0.01) {
            blob = await _toBlob(off, 'image/jpeg', quality);
            if (blob.size <= MAX) break;
            quality = parseFloat((quality - 0.05).toFixed(2));
        }

        // If even quality=0.01 exceeds 100 KB the image is extremely large/complex;
        // we still return the best (lowest quality) result rather than silently failing.
        return new File([blob], `${baseName}.jpg`, { type: 'image/jpeg' });
    }

    /* ────────────────────────────────────────────
       Cancel
    ──────────────────────────────────────────── */

    _cancel() {
        this._hide();
        this._resolvePromise(null);
    }

    /* ────────────────────────────────────────────
       Show / Hide
    ──────────────────────────────────────────── */

    _show() {
        this._modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    _hide() {
        this._modal.classList.add('hidden');
        document.body.style.overflow = '';
        const si = document.getElementById('icpSizeInfo');
        const di = document.getElementById('icpDimInfo');
        if (si) si.textContent = '';
        if (di) di.textContent = 'Draw a crop area on the image';
    }

    /* ────────────────────────────────────────────
       Inject CSS
    ──────────────────────────────────────────── */

    _injectStyles() {
        if (document.getElementById('icpStyles')) return;
        const s = document.createElement('style');
        s.id = 'icpStyles';
        s.textContent = `
            /* ── Overlay ── */
            .icp-overlay {
                position: fixed; inset: 0; z-index: 9999;
                background: rgba(0,0,0,0.82);
                display: flex; align-items: center; justify-content: center;
                padding: 16px; box-sizing: border-box;
            }
            .icp-overlay.hidden { display: none !important; }

            /* ── Dialog ── */
            .icp-dialog {
                background: #fff; border-radius: 16px;
                padding: 20px; display: flex; flex-direction: column; gap: 12px;
                max-width: 840px; width: 100%;
                box-shadow: 0 32px 100px rgba(0,0,0,0.55);
                box-sizing: border-box;
            }

            /* ── Header ── */
            .icp-header {
                display: flex; align-items: center; justify-content: space-between;
            }
            .icp-header-title {
                display: flex; align-items: center; gap: 8px;
                font-weight: 700; font-size: 1rem; color: #0f172a;
            }
            .icp-close-btn {
                font-size: 1.7rem; line-height: 1;
                background: none; border: none; cursor: pointer;
                color: #94a3b8; padding: 0 4px; transition: color .15s;
            }
            .icp-close-btn:hover { color: #ef4444; }

            /* ── Hint bar ── */
            .icp-hint {
                display: flex; flex-wrap: wrap; gap: 8px 16px;
                font-size: 0.73rem; color: #64748b;
                background: #f8fafc; border: 1px solid #e2e8f0;
                padding: 7px 14px; border-radius: 8px;
            }

            /* ── Canvas ── */
            .icp-canvas-wrap {
                background: #0f172a; border-radius: 10px;
                display: flex; align-items: center; justify-content: center;
                overflow: hidden;
            }
            #icpCanvas {
                display: block; cursor: crosshair;
                user-select: none; -webkit-user-select: none;
                touch-action: none;
            }

            /* ── Footer ── */
            .icp-footer {
                display: flex; flex-wrap: wrap; align-items: center;
                gap: 10px; justify-content: space-between;
            }

            /* ── Zoom bar ── */
            .icp-zoom-bar {
                display: flex; align-items: center; gap: 6px;
            }
            .icp-zoom-bar button {
                width: 30px; height: 30px; border-radius: 7px;
                border: 1px solid #cbd5e1; background: #f1f5f9;
                cursor: pointer; color: #334155;
                display: flex; align-items: center; justify-content: center;
                transition: background .15s;
            }
            .icp-zoom-bar button:hover { background: #dbeafe; border-color: #93c5fd; color: #1d4ed8; }
            .icp-fit-btn { width: auto !important; padding: 0 12px; font-size: 0.78rem; font-weight: 600; }
            #icpZoomLabel {
                min-width: 46px; text-align: center;
                font-size: 0.82rem; color: #475569;
                font-variant-numeric: tabular-nums; font-weight: 600;
            }

            /* ── Info ── */
            .icp-info {
                display: flex; gap: 14px;
                font-size: 0.79rem; color: #64748b; flex: 1;
                justify-content: center;
            }

            /* ── Action buttons ── */
            .icp-actions { display: flex; gap: 8px; align-items: center; }

            .icp-btn-primary {
                display: inline-flex; align-items: center; gap: 6px;
                background: #3b82f6; color: #fff; border: none;
                padding: 8px 18px; border-radius: 8px; cursor: pointer;
                font-size: 0.86rem; font-weight: 600;
                transition: background .15s, box-shadow .15s;
                box-shadow: 0 2px 8px rgba(59,130,246,0.35);
            }
            .icp-btn-primary:hover:not(:disabled) { background: #2563eb; }
            .icp-btn-primary:disabled { opacity: .55; cursor: not-allowed; }

            .icp-btn-secondary {
                display: inline-flex; align-items: center; gap: 5px;
                background: #f1f5f9; color: #475569;
                border: 1px solid #cbd5e1;
                padding: 8px 14px; border-radius: 8px; cursor: pointer;
                font-size: 0.86rem; transition: background .15s;
            }
            .icp-btn-secondary:hover { background: #e2e8f0; }

            /* ── Spinner ── */
            .icp-spinner {
                display: inline-block; width: 13px; height: 13px;
                border: 2px solid rgba(255,255,255,0.4);
                border-top-color: #fff; border-radius: 50%;
                animation: icpSpin 0.7s linear infinite;
            }
            @keyframes icpSpin { to { transform: rotate(360deg); } }

            /* ── Responsive ── */
            @media (max-width: 540px) {
                .icp-dialog { padding: 12px; gap: 10px; }
                .icp-footer { flex-direction: column; align-items: stretch; }
                .icp-actions { flex-wrap: wrap; }
                .icp-actions button { flex: 1; }
                .icp-info { justify-content: flex-start; }
                .icp-hint span { display: block; }
            }
        `;
        document.head.appendChild(s);
    }
}

/* ── Utility ── */
function _toBlob(canvas, type, quality) {
    return new Promise((res, rej) => {
        canvas.toBlob(b => b ? res(b) : rej(new Error('toBlob failed')), type, quality);
    });
}
