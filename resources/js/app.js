import { initNav } from './nav';
import { initReveal } from './reveal';
import { initShare } from './share';
import { initScan } from './scan';

const boot = () => {
    initNav();
    initReveal();
    initShare();
    initScan();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

document.addEventListener('livewire:navigated', () => initReveal());
