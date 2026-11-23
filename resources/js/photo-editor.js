/**
 * Editor foto profil (Alpine): potong persegi (Cropper.js), perbesar/putar/balik, filter, kecerahan & kontras.
 *
 * Filter dihitung sendiri dengan matriks warna (rumus resmi Filter Effects CSS), bukan ctx.filter,
 * supaya hasil yang tersimpan sama di semua browser (Safari belum mendukung ctx.filter).
 * Pratinjau memakai CSS filter dengan nilai yang sama. Server tetap memproses ulang gambar (lihat SettingsController).
 */
// Cropper.js (+CSS) dimuat hanya saat editor dibuka, supaya halaman lain tetap ringan.
let CropperClass = null;
async function loadCropper() {
    if (!CropperClass) {
        const [mod] = await Promise.all([import('cropperjs'), import('cropperjs/dist/cropper.css')]);
        CropperClass = mod.default;
    }
    return CropperClass;
}

const MAX_BYTES = 5 * 1024 * 1024;
const TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const OUTPUT = 512;

/** Preset filter: daftar [fungsi, nilai] dalam urutan CSS. */
const FILTERS = [
    { key: 'asli', label: 'Asli', ops: [] },
    { key: 'cerah', label: 'Cerah', ops: [['brightness', 1.08], ['contrast', 1.06], ['saturate', 1.25]] },
    { key: 'hangat', label: 'Hangat', ops: [['sepia', 0.22], ['saturate', 1.2], ['hue-rotate', -8], ['brightness', 1.03]] },
    { key: 'sejuk', label: 'Sejuk', ops: [['saturate', 1.05], ['hue-rotate', 12], ['brightness', 1.03]] },
    { key: 'vintage', label: 'Vintage', ops: [['sepia', 0.45], ['contrast', 0.92], ['brightness', 1.06], ['saturate', 0.85]] },
    { key: 'dramatis', label: 'Dramatis', ops: [['contrast', 1.3], ['saturate', 1.1], ['brightness', 0.95]] },
    { key: 'hitamputih', label: 'Hitam putih', ops: [['grayscale', 1], ['contrast', 1.12]] },
    { key: 'pudar', label: 'Pudar', ops: [['contrast', 0.85], ['brightness', 1.08], ['saturate', 0.7]] },
];

function opsFor(key, brightness, contrast) {
    const base = (FILTERS.find((f) => f.key === key) ?? FILTERS[0]).ops;
    const extra = [];
    if (brightness) extra.push(['brightness', 1 + brightness / 100]);
    if (contrast) extra.push(['contrast', 1 + contrast / 100]);
    return [...base, ...extra];
}

function cssFor(ops) {
    if (!ops.length) return 'none';
    return ops.map(([fn, v]) => (fn === 'hue-rotate' ? `hue-rotate(${v}deg)` : `${fn}(${v})`)).join(' ');
}

/* ---- Matriks warna 3×4 (baris: R, G, B; kolom: r, g, b, offset 0–255) ---- */
const I = [[1, 0, 0, 0], [0, 1, 0, 0], [0, 0, 1, 0]];

function matrixOf(fn, v) {
    const m3 = (a) => a.map((row) => [...row, 0]);
    switch (fn) {
        case 'grayscale': {
            const a = 1 - Math.min(1, v);
            return m3([
                [0.2126 + 0.7874 * a, 0.7152 - 0.7152 * a, 0.0722 - 0.0722 * a],
                [0.2126 - 0.2126 * a, 0.7152 + 0.2848 * a, 0.0722 - 0.0722 * a],
                [0.2126 - 0.2126 * a, 0.7152 - 0.7152 * a, 0.0722 + 0.9278 * a],
            ]);
        }
        case 'sepia': {
            const a = 1 - Math.min(1, v);
            return m3([
                [0.393 + 0.607 * a, 0.769 - 0.769 * a, 0.189 - 0.189 * a],
                [0.349 - 0.349 * a, 0.686 + 0.314 * a, 0.168 - 0.168 * a],
                [0.272 - 0.272 * a, 0.534 - 0.534 * a, 0.131 + 0.869 * a],
            ]);
        }
        case 'saturate':
            return m3([
                [0.213 + 0.787 * v, 0.715 - 0.715 * v, 0.072 - 0.072 * v],
                [0.213 - 0.213 * v, 0.715 + 0.285 * v, 0.072 - 0.072 * v],
                [0.213 - 0.213 * v, 0.715 - 0.715 * v, 0.072 + 0.928 * v],
            ]);
        case 'hue-rotate': {
            const r = (v * Math.PI) / 180;
            const c = Math.cos(r);
            const s = Math.sin(r);
            return m3([
                [0.213 + c * 0.787 - s * 0.213, 0.715 - c * 0.715 - s * 0.715, 0.072 - c * 0.072 + s * 0.928],
                [0.213 - c * 0.213 + s * 0.143, 0.715 + c * 0.285 + s * 0.14, 0.072 - c * 0.072 - s * 0.283],
                [0.213 - c * 0.213 - s * 0.787, 0.715 - c * 0.715 + s * 0.715, 0.072 + c * 0.928 + s * 0.072],
            ]);
        }
        case 'brightness':
            return [[v, 0, 0, 0], [0, v, 0, 0], [0, 0, v, 0]];
        case 'contrast': {
            const o = (0.5 - 0.5 * v) * 255;
            return [[v, 0, 0, o], [0, v, 0, o], [0, 0, v, o]];
        }
        default:
            return I;
    }
}

/** b setelah a: hasil = b × a (affine). */
function compose(a, b) {
    return b.map((row) => [
        row[0] * a[0][0] + row[1] * a[1][0] + row[2] * a[2][0],
        row[0] * a[0][1] + row[1] * a[1][1] + row[2] * a[2][1],
        row[0] * a[0][2] + row[1] * a[1][2] + row[2] * a[2][2],
        row[0] * a[0][3] + row[1] * a[1][3] + row[2] * a[2][3] + row[3],
    ]);
}

function applyOps(canvas, ops) {
    if (!ops.length) return canvas;
    const m = ops.reduce((acc, [fn, v]) => compose(acc, matrixOf(fn, v)), I);
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const d = img.data;
    for (let i = 0; i < d.length; i += 4) {
        const r = d[i];
        const g = d[i + 1];
        const b = d[i + 2];
        d[i] = m[0][0] * r + m[0][1] * g + m[0][2] * b + m[0][3];
        d[i + 1] = m[1][0] * r + m[1][1] * g + m[1][2] * b + m[1][3];
        d[i + 2] = m[2][0] * r + m[2][1] * g + m[2][2] * b + m[2][3];
    }
    ctx.putImageData(img, 0, 0); // Uint8ClampedArray membulatkan & membatasi 0–255 otomatis
    return canvas;
}

function toBlob(canvas) {
    return new Promise((resolve) => {
        canvas.toBlob((blob) => {
            if (blob && blob.type === 'image/webp') return resolve(blob);
            canvas.toBlob((jpg) => resolve(jpg), 'image/jpeg', 0.9); // browser tanpa WebP encoder
        }, 'image/webp', 0.9);
    });
}

export function photoEditor() {
    return {
        open: false,
        dragging: false,
        saving: false,
        error: '',
        filters: FILTERS,
        filter: 'asli',
        brightness: 0,
        contrast: 0,
        zoomLevel: 0,
        thumb: '',
        cropper: null,
        objectUrl: null,
        scaleX: 1,
        minRatio: null,

        get ops() { return opsFor(this.filter, this.brightness, this.contrast); },
        get css() { return cssFor(this.ops); },
        cssOf(key) { return cssFor(opsFor(key, 0, 0)); },

        pick(file) {
            this.error = '';
            if (!file) return;
            if (!TYPES.includes(file.type)) {
                this.error = 'Format foto harus JPG, PNG, atau WebP.';
                return;
            }
            if (file.size > MAX_BYTES) {
                this.error = `Ukuran foto ${(file.size / 1024 / 1024).toFixed(1).replace('.', ',')} MB, maksimal 5 MB.`;
                return;
            }
            this.objectUrl && URL.revokeObjectURL(this.objectUrl);
            this.objectUrl = URL.createObjectURL(file);
            this.thumb = this.objectUrl;
            this.filter = 'asli';
            this.brightness = 0;
            this.contrast = 0;
            this.scaleX = 1;
            this.open = true;
            this.$nextTick(() => this.mount());
        },

        async mount() {
            let Cropper;
            try {
                Cropper = await loadCropper();
            } catch (e) {
                this.open = false;
                this.error = 'Editor foto gagal dimuat. Periksa koneksi lalu coba lagi.';
                return;
            }
            const img = this.$refs.image;
            this.cropper?.destroy();
            img.src = this.objectUrl;
            this.cropper = new Cropper(img, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.9,
                background: false,
                guides: false,
                center: false,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
                // Orientasi EXIF sudah diterapkan browser modern; pengecekan Cropper memakai XHR ke blob: (diblokir CSP).
                checkOrientation: false,
                preview: '.tr-preview',
                ready: () => {
                    img.classList.remove('opacity-0');
                    this.minRatio = this.cropper.getCanvasData().width / this.cropper.getImageData().naturalWidth;
                    this.zoomLevel = 0;
                },
                zoom: (e) => {
                    if (!this.minRatio) return;
                    // Slider 0–100 ↔ rasio 1×–5× dari ukuran awal
                    const level = Math.round(((e.detail.ratio / this.minRatio - 1) / 4) * 100);
                    this.zoomLevel = Math.max(0, Math.min(100, level));
                },
            });
        },

        zoom(delta) { this.cropper?.zoom(delta); },
        setZoom() { this.minRatio && this.cropper?.zoomTo(this.minRatio * (1 + (this.zoomLevel / 100) * 4)); },
        rotate(deg) { this.cropper?.rotate(deg); },
        flip() { this.scaleX = -this.scaleX; this.cropper?.scaleX(this.scaleX); },
        reset() {
            this.cropper?.reset();
            this.scaleX = 1;
            this.filter = 'asli';
            this.brightness = 0;
            this.contrast = 0;
        },

        close() {
            this.open = false;
            this.saving = false;
            setTimeout(() => { this.cropper?.destroy(); this.cropper = null; }, 200);
        },

        async save() {
            if (!this.cropper || this.saving) return;
            this.saving = true;
            try {
                const cropped = this.cropper.getCroppedCanvas({
                    width: OUTPUT, height: OUTPUT, fillColor: '#ffffff', imageSmoothingEnabled: true, imageSmoothingQuality: 'high',
                });
                const blob = await toBlob(applyOps(cropped, this.ops));
                const ext = blob.type === 'image/webp' ? 'webp' : 'jpg';
                const transfer = new DataTransfer();
                transfer.items.add(new File([blob], `foto-profil.${ext}`, { type: blob.type }));
                this.$refs.output.files = transfer.files;
                document.documentElement.classList.add('tr-navigating');
                this.$refs.upload.submit();
            } catch (e) {
                this.saving = false;
                this.error = 'Foto gagal diproses di browser ini. Coba foto lain atau browser terbaru.';
                this.close();
            }
        },
    };
}

/** Pilihan tema di halaman Tampilan: terang, gelap, atau ikuti sistem (kunci dihapus). */
export function themePicker() {
    const read = () => { try { return localStorage.getItem('tr-theme') || 'system'; } catch (e) { return 'system'; } };
    return {
        choice: read(),
        set(value) {
            this.choice = value;
            try {
                value === 'system' ? localStorage.removeItem('tr-theme') : localStorage.setItem('tr-theme', value);
            } catch (e) { /* mode privat: tetap berlaku untuk halaman ini */ }
            const dark = value === 'dark' || (value === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            const apply = () => document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
            const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            document.startViewTransition && !calm ? document.startViewTransition(apply) : apply();
        },
    };
}
