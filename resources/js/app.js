import { initNav } from './nav';
import { initReveal } from './reveal';
import { initShare } from './share';
import { initScan } from './scan';
import { initUi } from './ui';

const boot = () => {
    initNav();
    initReveal();
    initShare();
    initScan();
    initUi();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

document.addEventListener('livewire:navigated', () => {
    initReveal();
    initUi();
});
