import Alpine from 'alpinejs';
import { initCountUp } from './countup';

window.Alpine = Alpine;

Alpine.start();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCountUp);
} else {
    initCountUp();
}
