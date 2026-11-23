import Alpine from 'alpinejs';
import { initCountUp } from './countup';
import { initInteractions } from './interactions';

window.Alpine = Alpine;

initInteractions();

Alpine.start();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCountUp);
} else {
    initCountUp();
}
