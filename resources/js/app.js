const random = (min, max) => min + Math.random() * (max - min);

const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const palette = ['#6f7d62', '#dfe4d4', '#dcb7ab', '#c9b18a', '#fbf8f2'];

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
    const colors = ['#dcb7ab', '#c48f84', '#6f7d62', '#c9b18a'];

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
 * An eagle that wanders the hero along random waypoints. When tapped it lets out
 * a puff of hearts, drops a little note, and flies off before returning later.
 */
function dove(notes = []) {
    return {
        leaving: false,
        away: false,
        burst: null,
        note: null,
        noteOrder: [],
        noteTimer: null,
        x: 0,
        y: 0,
        heading: 0,
        speed: 110,
        target: { x: 0, y: 0 },
        retargetAt: 0,
        lastFrameAt: 0,

        init() {
            if (prefersReducedMotion()) {
                return;
            }

            this.spawn();
            requestAnimationFrame((now) => this.fly(now));
        },

        bounds() {
            return { width: this.$el.clientWidth, height: this.$el.clientHeight };
        },

        pickTarget(now) {
            const { width, height } = this.bounds();
            let x = random(width * 0.08, width * 0.85);

            while (Math.abs(x - this.x) < width * 0.3) {
                x = random(width * 0.08, width * 0.85);
            }

            this.target = { x, y: random(height * 0.05, height * 0.75) };
            this.speed = random(85, 150);
            this.retargetAt = now + random(3000, 7000);
        },

        spawn() {
            const { width, height } = this.bounds();
            const edge = Math.floor(random(0, 3));

            if (edge === 0) {
                this.x = -180;
                this.y = random(height * 0.05, height * 0.6);
            } else if (edge === 1) {
                this.x = width + 20;
                this.y = random(height * 0.05, height * 0.6);
            } else {
                this.x = random(width * 0.1, width * 0.8);
                this.y = -130;
            }

            this.pickTarget(performance.now());
            this.heading = Math.atan2(this.target.y - this.y, this.target.x - this.x);
        },

        fly(now) {
            const elapsed = Math.min((now - (this.lastFrameAt || now)) / 1000, 0.05);
            this.lastFrameAt = now;

            if (! this.away) {
                const distance = Math.hypot(this.target.x - this.x, this.target.y - this.y);

                if (! this.leaving && (distance < 60 || now > this.retargetAt)) {
                    this.pickTarget(now);
                }

                const desired = Math.atan2(this.target.y - this.y, this.target.x - this.x);
                const turn = Math.atan2(Math.sin(desired - this.heading), Math.cos(desired - this.heading));
                const maxTurn = (this.leaving ? 4 : 1.8) * elapsed;

                this.heading += Math.max(-maxTurn, Math.min(maxTurn, turn));
                this.x += Math.cos(this.heading) * this.speed * elapsed;
                this.y += Math.sin(this.heading) * this.speed * elapsed;

                if (this.leaving && this.isOffScreen()) {
                    this.away = true;

                    setTimeout(() => {
                        this.spawn();
                        this.leaving = false;
                        this.away = false;
                    }, random(4000, 9000));
                }

                const facingLeft = Math.cos(this.heading) < 0;
                const tilt = Math.max(-20, Math.min(20, (Math.atan2(Math.sin(this.heading), Math.abs(Math.cos(this.heading))) * 180) / Math.PI));

                this.$refs.flier.style.transform = `translate(${this.x}px, ${this.y}px)`;
                this.$refs.pose.style.transform = `scaleX(${facingLeft ? -1 : 1}) rotate(${tilt}deg)`;
            }

            requestAnimationFrame((next) => this.fly(next));
        },

        isOffScreen() {
            const { width, height } = this.bounds();

            return this.x < -200 || this.x > width + 40 || this.y < -150 || this.y > height + 40;
        },

        greet() {
            if (this.leaving) {
                return;
            }

            const { width } = this.bounds();
            const centerX = this.x + this.$refs.pose.offsetWidth / 2;
            const centerY = this.y + this.$refs.pose.offsetHeight / 2;

            this.leaving = true;
            this.burst = { x: centerX, y: centerY, id: performance.now() };
            setTimeout(() => this.burst = null, 1000);

            this.target = { x: Math.cos(this.heading) < 0 ? -280 : width + 120, y: -220 };
            this.speed = 380;

            this.showNote(this.$refs.pose.getBoundingClientRect());
        },

        /**
         * Show the next note beside the dove, kept inside the visible screen and
         * clear of the fixed navigation bar.
         */
        showNote(doveBounds) {
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
            const fitsBelow = doveBounds.bottom + 12 + noteHeight <= window.innerHeight - margin;

            const x = Math.max(margin, Math.min(window.innerWidth - noteWidth - margin, doveBounds.left + doveBounds.width / 2 - noteWidth / 2));
            const y = Math.max(navigationHeight, Math.min(window.innerHeight - noteHeight - margin, fitsBelow ? doveBounds.bottom + 12 : doveBounds.top - 12 - noteHeight));

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
    'font: 600 16px Georgia, serif; color: #3f4a3a',
    'font: 13px ui-monospace, monospace; color: #6d7166',
);

document.addEventListener('alpine:init', () => {
    window.Alpine.data('dove', dove);
    window.Alpine.data('sideFlowers', sideFlowers);
    window.Alpine.data('music', music);
});
