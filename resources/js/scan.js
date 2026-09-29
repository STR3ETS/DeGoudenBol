// Scanner voor cadeaubonnen: camera via BarcodeDetector (Chrome/Edge/Android), anders handmatige invoer.
export const initScan = () => {
    const root = document.querySelector('[data-scan-form]');
    if (!root) return;

    const button = root.querySelector('[data-scan-start]');
    const wrap = root.querySelector('[data-scan-video-wrap]');
    const video = root.querySelector('[data-scan-video]');
    const form = root.querySelector('[data-scan-submit]');
    const input = root.querySelector('[data-scan-code]');
    const method = root.querySelector('[data-scan-method]');
    if (!button || !video || !form || !input) return;

    let stream = null;
    let timer = null;

    const stop = () => {
        if (timer) clearInterval(timer);
        if (stream) stream.getTracks().forEach((track) => track.stop());
        stream = null;
        wrap.classList.add('hidden');
        button.textContent = 'Camera starten';
    };

    button.addEventListener('click', async () => {
        if (stream) return stop();

        if (!('BarcodeDetector' in window)) {
            alert('Deze browser kan geen QR-codes lezen. Typ het bonnummer in.');
            return;
        }

        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        } catch {
            alert('Geen toegang tot de camera.');
            return;
        }

        video.srcObject = stream;
        await video.play();
        wrap.classList.remove('hidden');
        button.textContent = 'Camera stoppen';

        const detector = new BarcodeDetector({ formats: ['qr_code'] });
        timer = setInterval(async () => {
            try {
                const codes = await detector.detect(video);
                if (codes.length === 0) return;
                const value = String(codes[0].rawValue || '').trim();
                if (!value) return;
                stop();
                input.value = value;
                if (method) method.value = 'scan';
                form.submit();
            } catch {}
        }, 400);
    });

    window.addEventListener('pagehide', stop);
};
