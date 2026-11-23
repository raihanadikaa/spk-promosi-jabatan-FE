{{-- Layar intro logo (bulatan naik tangga). Tampil hanya bila <html> diberi kelas tr-intro-on oleh skrip di <head>;
     animasinya di resources/js/intro.js. Markup harus sama dengan logoMarkup() di file itu. --}}
<div id="tr-intro" class="tr-intro" aria-hidden="true">
    <div class="tr-intro-stage">
        <svg class="tr-intro-logo" viewBox="0 0 32 32" aria-hidden="true">
            <rect class="tr-i-box" width="32" height="32" rx="7" fill="#2563EB"/>
            <path class="tr-i-stairs" d="M8 23h5v-5h5v-5h6" pathLength="1" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            <g class="tr-i-hop"><circle class="tr-i-glow" cx="24" cy="9.5" r="1.9" fill="#BFDBFE"/><circle class="tr-i-dot" cx="24" cy="9.5" r="1.9" fill="#BFDBFE"/></g>
        </svg>
        <p class="tr-i-word">TalentRank</p>
        <p class="tr-i-tag">Sistem pendukung keputusan promosi jabatan</p>
    </div>
</div>
