import Alpine from 'alpinejs';
import { initCountUp } from './countup';
import { initInteractions } from './interactions';
import { photoEditor } from './photo-editor';
import { themePicker, themeToggle, textPicker } from './preferences';
import { initIntro } from './intro';

window.Alpine = Alpine;

// initIntro dulu: handler logout-nya harus berjalan sebelum handler "memproses" form.
initIntro();
initInteractions();

Alpine.data('photoEditor', photoEditor);
Alpine.data('themePicker', themePicker);
Alpine.data('themeToggle', themeToggle);
Alpine.data('textPicker', textPicker);

Alpine.start();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCountUp);
} else {
    initCountUp();
}
