const random = (min, max) => min + Math.random() * (max - min);

const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const palette = ['#6b84a0', '#dbe4ee', '#dcb7ab', '#c9b18a', '#fbf8f2'];

const heartShape = new Path2D('M10 17C4 12 0 9 0 5A5 5 0 0 1 10 3a5 5 0 0 1 10 2c0 4-4 7-10 12Z');

/**
 * Animate petals or hearts on a temporary full-screen canvas, fading them out
 * over the second half of the animation.
 */
function playParticles(pieces, duration) {
    if (prefersReducedMotion()) {
        return;
    }

    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    const ratio = window.devicePixelRatio || 1;

    canvas.style.cssText = 'position:fixed;inset:0;width:100%;height:100%;pointer-events:none;z-index:60';
    canvas.width = window.innerWidth * ratio;
    canvas.height = window.innerHeight * ratio;
    context.scale(ratio, ratio);
    document.body.appendChild(canvas);

    const startedAt = performance.now();

    function frame(now) {
        const elapsed = now - startedAt;

        context.clearRect(0, 0, window.innerWidth, window.innerHeight);
        context.globalAlpha = Math.max(0, Math.min(1, 2 * (1 - elapsed / duration)));

        pieces.forEach((piece) => {
            piece.velocityY = Math.min(piece.velocityY + piece.gravity, piece.maxFall ?? Infinity);
            piece.velocityX *= piece.drag ?? 0.99;
            piece.x += piece.velocityX + Math.sin(elapsed / 400 + piece.rotation) * (piece.sway ?? 0);
            piece.y += piece.velocityY;
            piece.rotation += piece.spin;

            context.save();
            context.translate(piece.x, piece.y);
            context.rotate(piece.rotation);
            context.fillStyle = piece.color;

            if (piece.shape === 'heart') {
                context.scale(piece.size / 10, piece.size / 10);
                context.translate(-10, -9);
                context.fill(heartShape);
            } else {
                context.beginPath();
                context.ellipse(0, 0, piece.size, piece.size / 2.2, 0, 0, Math.PI * 2);
                context.fill();
            }

            context.restore();
        });

        if (elapsed < duration) {
            requestAnimationFrame(frame);
        } else {
            canvas.remove();
        }
    }

    requestAnimationFrame(frame);
}

/**
 * A soft burst of sage, blush and gold petals, played when a guest accepts the invitation.
 */
function celebrate() {
    playParticles(Array.from({ length: 160 }, () => ({
        x: window.innerWidth / 2 + random(-60, 60),
        y: window.innerHeight * 0.6,
        velocityX: random(-7, 7),
        velocityY: random(-22, -6),
        gravity: 0.35,
        size: random(4, 11),
        rotation: random(0, Math.PI),
        spin: random(-0.15, 0.15),
        color: palette[Math.floor(random(0, palette.length))],
    })), 4000);
}

/**
 * A handful of blush petals drifting down from a tapped flower.
 */
function dropPetals(x, y) {
    const colors = ['#d8b1a8', '#f3e2dc', '#c48f84', '#fbf1ec'];

    playParticles(Array.from({ length: 12 }, () => ({
        x: x + random(-12, 12),
        y: y + random(-12, 12),
        velocityX: random(-1.5, 1.5),
        velocityY: random(-2.5, 0),
        gravity: 0.05,
        maxFall: 1.6,
        sway: 0.8,
        size: random(4, 7),
        rotation: random(0, Math.PI),
        spin: random(-0.06, 0.06),
        color: colors[Math.floor(random(0, colors.length))],
    })), 4500);
}

/**
 * Hearts raining down the whole screen, unlocked by the easter eggs.
 */
function rainHearts() {
    const colors = ['#dcb7ab', '#c48f84', '#6b84a0', '#c9b18a'];

    playParticles(Array.from({ length: 80 }, () => ({
        shape: 'heart',
        x: random(0, window.innerWidth),
        y: -random(20, window.innerHeight * 0.8),
        velocityX: 0,
        velocityY: random(2, 4),
        gravity: 0.02,
        maxFall: 5,
        sway: 0.8,
        size: random(6, 14),
        rotation: random(-0.4, 0.4),
        spin: random(-0.01, 0.01),
        color: colors[Math.floor(random(0, colors.length))],
    })), 6000);
}

window.addEventListener('rsvp-celebrate', celebrate);

/**
 * Soap bubbles that drift up through the hero. Tapping one pops it into a
 * spray of droplets and shows a little note, then a new bubble floats in later.
 */
function bubbles(notes = [], count = 2) {
    const motion = [];
    let sky = null;

    return {
        bubbles: Array.from({ length: count }, () => ({ popped: false })),
        bursts: [],
        note: null,
        noteOrder: [],
        noteTimer: null,
        lastFrameAt: 0,

        init() {
            sky = this.$el;

            if (prefersReducedMotion()) {
                return;
            }

            this.bubbles.forEach((_, index) => this.spawn(index, 0.45 + index * 0.35));
            requestAnimationFrame((now) => this.float(now));
        },

        element(index) {
            return sky.querySelector(`[data-bubble="${index}"]`);
        },

        /**
         * Place a bubble at the given height of the hero (as a fraction), or just below it.
         */
        spawn(index, heightFraction = null) {
            const width = sky.clientWidth;
            const height = sky.clientHeight;

            motion[index] = {
                baseX: random(width * 0.08, width * 0.85),
                y: heightFraction === null ? height + 20 : height * heightFraction,
                speed: random(20, 45),
                sway: random(12, 32),
                swaySpeed: random(0.6, 1.2),
                phase: random(0, Math.PI * 2),
                x: 0,
            };
        },

        float(now) {
            const elapsed = Math.min((now - (this.lastFrameAt || now)) / 1000, 0.05);
            this.lastFrameAt = now;

            motion.forEach((bubble, index) => {
                const element = this.element(index);

                if (! element || this.bubbles[index].popped) {
                    return;
                }

                bubble.y -= bubble.speed * elapsed;
                bubble.phase += bubble.swaySpeed * elapsed;
                bubble.x = Math.min(bubble.baseX + Math.sin(bubble.phase) * bubble.sway, sky.clientWidth - element.offsetWidth);

                if (bubble.y < -element.offsetHeight - 20) {
                    this.spawn(index);
                }

                element.style.transform = `translate(${bubble.x}px, ${bubble.y}px)`;
            });

            requestAnimationFrame((next) => this.float(next));
        },

        pop(index) {
            const bubble = this.bubbles[index];
            const element = this.element(index);

            if (bubble.popped || ! element) {
                return;
            }

            const id = performance.now();

            bubble.popped = true;
            this.bursts.push({
                id,
                x: motion[index].x + element.offsetWidth / 2,
                y: motion[index].y + element.offsetHeight / 2,
            });
            setTimeout(() => this.bursts = this.bursts.filter((burst) => burst.id !== id), 1000);

            this.showNote(element.getBoundingClientRect());

            setTimeout(() => {
                this.spawn(index);
                bubble.popped = false;
            }, random(3000, 7000));
        },

        /**
         * Show the next note beside the popped bubble, kept inside the visible
         * screen and clear of the fixed navigation bar.
         */
        showNote(bubbleBounds) {
            if (notes.length === 0) {
                return;
            }

            if (this.noteOrder.length === 0) {
                this.noteOrder = [...notes.keys()].sort(() => Math.random() - 0.5);
            }

            const noteWidth = 256;
            const noteHeight = 130;
            const margin = 16;
            const navigationHeight = 72;
            const fitsBelow = bubbleBounds.bottom + 12 + noteHeight <= window.innerHeight - margin;

            const x = Math.max(margin, Math.min(window.innerWidth - noteWidth - margin, bubbleBounds.left + bubbleBounds.width / 2 - noteWidth / 2));
            const y = Math.max(navigationHeight, Math.min(window.innerHeight - noteHeight - margin, fitsBelow ? bubbleBounds.bottom + 12 : bubbleBounds.top - 12 - noteHeight));

            clearTimeout(this.noteTimer);
            this.note = { text: notes[this.noteOrder.pop()], x: x + window.scrollX, y: y + window.scrollY };
            this.noteTimer = setTimeout(() => this.note = null, 6000);
        },
    };
}

/**
 * The round flowers framing the hero, which spin and shed petals when tapped.
 */
function sideFlowers() {
    return {
        tap(event) {
            const flower = event.currentTarget;

            if (prefersReducedMotion() || flower.classList.contains('is-spinning')) {
                return;
            }

            flower.classList.add('is-spinning');
            flower.addEventListener('animationend', () => flower.classList.remove('is-spinning'), { once: true });

            const bounds = flower.getBoundingClientRect();
            dropPetals(bounds.left + bounds.width / 2, bounds.top + bounds.height / 2);
        },
    };
}

/**
 * The three hero photo frames. They take turns fading to their next photo,
 * and drift with the mouse by different amounts so they feel layered.
 */
function heroPhotos(sets = []) {
    const listeners = new AbortController();
    let timer = null;
    let turn = 0;
    let frameRequested = false;

    return {
        current: sets.map(() => 0),
        previous: sets.map(() => null),
        loaded: sets.map((set) => set.map((_, index) => index < (prefersReducedMotion() ? 1 : 2))),
        isVisible: false,

        init() {
            if (prefersReducedMotion()) {
                return;
            }

            if (sets.some((set) => set.length > 1)) {
                timer = setInterval(() => this.advance(), 3500);
            }

            window.addEventListener('pointermove', (event) => this.follow(event), { passive: true, signal: listeners.signal });
            document.documentElement.addEventListener('pointerleave', () => this.setOffset(0, 0), { signal: listeners.signal });
        },

        destroy() {
            clearInterval(timer);
            listeners.abort();
        },

        advance() {
            if (document.hidden || !this.isVisible) {
                return;
            }

            for (let step = 0; step < sets.length; step++) {
                const frame = (turn + step) % sets.length;
                const total = sets[frame].length;

                if (total > 1) {
                    this.previous[frame] = this.current[frame];
                    this.current[frame] = (this.current[frame] + 1) % total;
                    this.loaded[frame][(this.current[frame] + 1) % total] = true;
                    turn = frame + 1;

                    return;
                }
            }
        },

        /**
         * The photo fading in sits on top, while the one it replaces stays
         * fully shown underneath so the frame never dims mid-fade.
         */
        photoClasses(frame, photo) {
            const isCurrent = this.current[frame] === photo;
            const isShown = isCurrent || this.previous[frame] === photo;

            return {
                'z-10': isCurrent,
                'scale-100 opacity-100': isShown,
                'scale-105 opacity-0': !isShown,
            };
        },

        follow(event) {
            if (event.pointerType !== 'mouse' || frameRequested) {
                return;
            }

            frameRequested = true;
            requestAnimationFrame(() => {
                frameRequested = false;
                this.setOffset(
                    (event.clientX / window.innerWidth) * 2 - 1,
                    (event.clientY / window.innerHeight) * 2 - 1,
                );
            });
        },

        setOffset(x, y) {
            this.$el.style.setProperty('--mx', x.toFixed(3));
            this.$el.style.setProperty('--my', y.toFixed(3));
        },
    };
}

/**
 * Background music that starts when the envelope is opened, unless the guest
 * muted it on an earlier visit.
 */
function music(source) {
    return {
        playing: false,
        audio: null,

        init() {
            this.audio = new Audio();
            this.audio.preload = 'none';
            this.audio.src = source;
            this.audio.loop = true;
            this.audio.volume = 0.5;
            this.audio.addEventListener('play', () => this.playing = true);
            this.audio.addEventListener('pause', () => this.playing = false);

            window.addEventListener('invitation-opened', () => this.autoplay());

            if (sessionStorage.getItem('invitation-opened') === '1') {
                this.autoplay();
            }
        },

        /**
         * Browsers block sound until the guest interacts with the page, so when
         * the envelope was skipped on a reload, wait for their first tap or key.
         */
        autoplay() {
            if (localStorage.getItem('invitation-music') === 'off') {
                return;
            }

            this.audio.play().catch(() => {
                const listeners = new AbortController();
                const resume = () => {
                    listeners.abort();

                    if (localStorage.getItem('invitation-music') !== 'off') {
                        this.play();
                    }
                };

                window.addEventListener('click', resume, { capture: true, signal: listeners.signal });
                window.addEventListener('keydown', resume, { capture: true, signal: listeners.signal });
            });
        },

        play() {
            this.audio.play().catch(() => this.playing = false);
        },

        toggle() {
            if (this.playing) {
                this.audio.pause();
                localStorage.setItem('invitation-music', 'off');
            } else {
                this.play();
                localStorage.setItem('invitation-music', 'on');
            }
        },
    };
}

const konamiCode = ['ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight', 'b', 'a'];
let konamiProgress = 0;

window.addEventListener('keydown', (event) => {
    const key = event.key.length === 1 ? event.key.toLowerCase() : event.key;

    konamiProgress = key === konamiCode[konamiProgress] ? konamiProgress + 1 : (key === konamiCode[0] ? 1 : 0);

    if (konamiProgress === konamiCode.length) {
        konamiProgress = 0;
        rainHearts();
    }
});

window.merge = () => {
    rainHearts();

    return '💙 Merged successfully. 2 hearts, 0 conflicts.';
};

console.log(
    '%cHey there, fellow developer! 👋%c\n\nThis invitation was hand-built by the groom.\nRun merge() here, or try ↑ ↑ ↓ ↓ ← → ← → B A on the page.',
    'font: 600 16px Georgia, serif; color: #3d4e63',
    'font: 13px ui-monospace, monospace; color: #6d7166',
);

document.addEventListener('alpine:init', () => {
    window.Alpine.data('bubbles', bubbles);
    window.Alpine.data('sideFlowers', sideFlowers);
    window.Alpine.data('heroPhotos', heroPhotos);
    window.Alpine.data('music', music);
});
