import Alpine from 'alpinejs';
import { initCountUp } from './countup';
import { initInteractions } from './interactions';
import { photoEditor, themePicker } from './photo-editor';
import { initIntro } from './intro';

window.Alpine = Alpine;

// initIntro dulu: handler logout-nya harus berjalan sebelum handler "memproses" form.
initIntro();
initInteractions();

Alpine.data('photoEditor', photoEditor);
Alpine.data('themePicker', themePicker);

Alpine.start();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCountUp);
} else {
    initCountUp();
}
