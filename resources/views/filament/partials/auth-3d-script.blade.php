<script>
    (() => {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        const card = document.querySelector('.auth-3d-card');
        if (!card) {
            return;
        }

        const maxTilt = 12;
        let raf = null;

        const onMove = (event) => {
            if (raf) {
                cancelAnimationFrame(raf);
            }

            raf = requestAnimationFrame(() => {
                const rect = card.getBoundingClientRect();
                const x = (event.clientX - rect.left) / rect.width - 0.5;
                const y = (event.clientY - rect.top) / rect.height - 0.5;

                card.classList.add('is-tilting');
                card.style.transform = `rotateX(${(-y * maxTilt).toFixed(2)}deg) rotateY(${(x * maxTilt).toFixed(2)}deg) translateZ(36px)`;
            });
        };

        const onLeave = () => {
            card.classList.remove('is-tilting');
            card.style.transform = '';
        };

        document.addEventListener('mousemove', onMove, { passive: true });
        document.addEventListener('mouseleave', onLeave);
    })();
</script>
