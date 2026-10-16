const PIECE_COUNT = 70;
const EMOJI_FONT = '"Apple Color Emoji", "Segoe UI Emoji", "Noto Color Emoji", sans-serif';

function getOverlay() {
    return document.querySelector('[data-celebration]');
}

function rainEmojis(emojis) {
    const canvas = document.createElement('canvas');
    canvas.className = 'celebration-confetti';
    canvas.setAttribute('aria-hidden', 'true');
    document.body.appendChild(canvas);

    const width = window.innerWidth;
    const height = window.innerHeight;
    const ratio = window.devicePixelRatio || 1;
    canvas.width = width * ratio;
    canvas.height = height * ratio;

    const context = canvas.getContext('2d');
    context.scale(ratio, ratio);
    context.textAlign = 'center';
    context.textBaseline = 'middle';

    const pieces = Array.from({ length: PIECE_COUNT }, () => ({
        emoji: emojis[Math.floor(Math.random() * emojis.length)],
        x: Math.random() * width,
        y: -40 - Math.random() * height * 0.6,
        size: 18 + Math.random() * 18,
        speed: 250 + Math.random() * 200,
        sway: Math.random() * Math.PI * 2,
        swaySpeed: 1 + Math.random() * 2,
        rotation: Math.random() * Math.PI * 2,
        spin: (Math.random() - 0.5) * 4,
    }));

    let last = performance.now();

    const frame = (now) => {
        if (!canvas.isConnected) {
            return;
        }

        const seconds = Math.min((now - last) / 1000, 0.05);
        last = now;
        context.clearRect(0, 0, width, height);

        let falling = 0;
        for (const piece of pieces) {
            piece.y += piece.speed * seconds;
            piece.sway += piece.swaySpeed * seconds;
            piece.rotation += piece.spin * seconds;

            if (piece.y - piece.size > height) {
                continue;
            }

            falling++;
            context.save();
            context.translate(piece.x + Math.sin(piece.sway) * 20, piece.y);
            context.rotate(piece.rotation);
            context.font = `${piece.size}px ${EMOJI_FONT}`;
            context.fillText(piece.emoji, 0, 0);
            context.restore();
        }

        if (falling > 0) {
            requestAnimationFrame(frame);
        } else {
            canvas.remove();
        }
    };

    requestAnimationFrame(frame);
}

export function dismissCelebration() {
    const overlay = getOverlay();
    if (!overlay) {
        return false;
    }

    overlay.remove();
    return true;
}

export function startCelebration() {
    const overlay = getOverlay();
    if (!overlay) {
        return;
    }

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay || event.target.closest('[data-celebration-close]')) {
            dismissCelebration();
        }
    });
    overlay.querySelector('[data-celebration-close]')?.focus();

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    let emojis = [];
    try {
        emojis = JSON.parse(overlay.dataset.emojis || '[]');
    } catch {
        emojis = [];
    }

    // Only emojis the user actually picked; without any there is no confetti at all.
    if (emojis.length > 0) {
        rainEmojis(emojis);
    }
}

// Turbolinks would otherwise keep the overlay in its snapshot and celebrate again on
// the back button.
document.addEventListener('turbolinks:before-cache', () => {
    dismissCelebration();
    document.querySelectorAll('.celebration-confetti').forEach(canvas => canvas.remove());
});
