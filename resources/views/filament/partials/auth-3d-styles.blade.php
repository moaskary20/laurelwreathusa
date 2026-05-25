@include('filament.partials.admin-theme-variables')

<style>
    .auth-3d-layout {
        position: relative;
        min-height: 100vh;
        overflow: hidden;
        background: radial-gradient(
            ellipse 120% 80% at 50% 0%,
            rgba(var(--admin-secondary-rgb), 0.22) 0%,
            var(--ci-bg) 55%
        );
        perspective: 1400px;
    }

    .auth-3d-scene {
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 0;
        overflow: hidden;
    }

    .auth-3d-scene__stage {
        position: absolute;
        inset: -10%;
        transform-style: preserve-3d;
        animation: auth-scene-drift 18s ease-in-out infinite;
    }

    @keyframes auth-scene-drift {
        0%,
        100% {
            transform: rotateX(8deg) rotateY(-12deg) translateZ(0);
        }
        50% {
            transform: rotateX(12deg) rotateY(10deg) translateZ(40px);
        }
    }

    .auth-3d-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(40px);
        opacity: 0.55;
    }

    .auth-3d-orb--1 {
        width: 22rem;
        height: 22rem;
        top: 8%;
        inset-inline-start: 10%;
        background: rgba(var(--admin-secondary-rgb), 0.45);
        animation: auth-orb-1 14s ease-in-out infinite;
    }

    .auth-3d-orb--2 {
        width: 18rem;
        height: 18rem;
        bottom: 12%;
        inset-inline-end: 8%;
        background: rgba(var(--admin-primary-rgb), 0.5);
        animation: auth-orb-2 16s ease-in-out infinite;
    }

    .auth-3d-orb--3 {
        width: 12rem;
        height: 12rem;
        top: 45%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(var(--admin-secondary-rgb), 0.35);
        animation: auth-orb-3 11s ease-in-out infinite;
    }

    @keyframes auth-orb-1 {
        0%,
        100% {
            transform: translate3d(0, 0, 80px);
        }
        50% {
            transform: translate3d(60px, 40px, 160px);
        }
    }

    @keyframes auth-orb-2 {
        0%,
        100% {
            transform: translate3d(0, 0, 120px);
        }
        50% {
            transform: translate3d(-50px, -30px, 60px);
        }
    }

    @keyframes auth-orb-3 {
        0%,
        100% {
            transform: translate(-50%, -50%) translate3d(0, 0, 100px);
        }
        50% {
            transform: translate(-50%, -50%) translate3d(30px, -20px, 200px);
        }
    }

    .auth-3d-ring {
        position: absolute;
        left: 50%;
        top: 42%;
        border-radius: 50%;
        border: 2px solid rgba(var(--admin-secondary-rgb), 0.35);
        transform-style: preserve-3d;
    }

    .auth-3d-ring--outer {
        width: min(70vw, 32rem);
        height: min(70vw, 32rem);
        margin-left: calc(min(70vw, 32rem) / -2);
        margin-top: calc(min(70vw, 32rem) / -2);
        animation: auth-ring-spin 24s linear infinite;
    }

    .auth-3d-ring--inner {
        width: min(50vw, 22rem);
        height: min(50vw, 22rem);
        margin-left: calc(min(50vw, 22rem) / -2);
        margin-top: calc(min(50vw, 22rem) / -2);
        border-color: rgba(var(--admin-primary-rgb), 0.4);
        animation: auth-ring-spin-reverse 18s linear infinite;
    }

    @keyframes auth-ring-spin {
        from {
            transform: rotateX(65deg) rotateZ(0deg);
        }
        to {
            transform: rotateX(65deg) rotateZ(360deg);
        }
    }

    @keyframes auth-ring-spin-reverse {
        from {
            transform: rotateX(72deg) rotateY(20deg) rotateZ(360deg);
        }
        to {
            transform: rotateX(72deg) rotateY(20deg) rotateZ(0deg);
        }
    }

    .auth-3d-cube {
        position: absolute;
        width: 4.5rem;
        height: 4.5rem;
        transform-style: preserve-3d;
    }

    .auth-3d-cube--a {
        top: 18%;
        inset-inline-end: 18%;
        animation: auth-cube-spin-a 20s linear infinite;
    }

    .auth-3d-cube--b {
        bottom: 22%;
        inset-inline-start: 14%;
        width: 3rem;
        height: 3rem;
        animation: auth-cube-spin-b 15s linear infinite;
    }

    @keyframes auth-cube-spin-a {
        from {
            transform: rotateX(0deg) rotateY(0deg) translateZ(100px);
        }
        to {
            transform: rotateX(360deg) rotateY(360deg) translateZ(100px);
        }
    }

    @keyframes auth-cube-spin-b {
        from {
            transform: rotateX(20deg) rotateY(0deg) translateZ(60px);
        }
        to {
            transform: rotateX(380deg) rotateY(-360deg) translateZ(60px);
        }
    }

    .auth-3d-cube__face {
        position: absolute;
        width: 100%;
        height: 100%;
        border: 1px solid rgba(var(--admin-secondary-rgb), 0.5);
        background: rgba(var(--admin-primary-rgb), 0.25);
        backdrop-filter: blur(2px);
    }

    .auth-3d-cube--b .auth-3d-cube__face {
        border-color: rgba(var(--admin-primary-rgb), 0.45);
        background: rgba(var(--admin-secondary-rgb), 0.2);
    }

    .auth-3d-cube__face--front {
        transform: rotateY(0deg) translateZ(calc(4.5rem / 2));
    }

    .auth-3d-cube__face--back {
        transform: rotateY(180deg) translateZ(calc(4.5rem / 2));
    }

    .auth-3d-cube__face--right {
        transform: rotateY(90deg) translateZ(calc(4.5rem / 2));
    }

    .auth-3d-cube__face--left {
        transform: rotateY(-90deg) translateZ(calc(4.5rem / 2));
    }

    .auth-3d-cube__face--top {
        transform: rotateX(90deg) translateZ(calc(4.5rem / 2));
    }

    .auth-3d-cube__face--bottom {
        transform: rotateX(-90deg) translateZ(calc(4.5rem / 2));
    }

    .auth-3d-cube--b .auth-3d-cube__face--front,
    .auth-3d-cube--b .auth-3d-cube__face--back,
    .auth-3d-cube--b .auth-3d-cube__face--right,
    .auth-3d-cube--b .auth-3d-cube__face--left,
    .auth-3d-cube--b .auth-3d-cube__face--top,
    .auth-3d-cube--b .auth-3d-cube__face--bottom {
        transform-origin: center;
    }

    .auth-3d-cube--b .auth-3d-cube__face--front {
        transform: rotateY(0deg) translateZ(1.5rem);
    }

    .auth-3d-cube--b .auth-3d-cube__face--back {
        transform: rotateY(180deg) translateZ(1.5rem);
    }

    .auth-3d-cube--b .auth-3d-cube__face--right {
        transform: rotateY(90deg) translateZ(1.5rem);
    }

    .auth-3d-cube--b .auth-3d-cube__face--left {
        transform: rotateY(-90deg) translateZ(1.5rem);
    }

    .auth-3d-cube--b .auth-3d-cube__face--top {
        transform: rotateX(90deg) translateZ(1.5rem);
    }

    .auth-3d-cube--b .auth-3d-cube__face--bottom {
        transform: rotateX(-90deg) translateZ(1.5rem);
    }

    .auth-3d-content {
        transform-style: preserve-3d;
    }

    .auth-3d-card {
        transform-style: preserve-3d;
        transition: transform 0.15s ease-out, box-shadow 0.25s ease;
        background: #0a0a0c !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        box-shadow:
            0 28px 56px rgba(0, 0, 0, 0.55),
            0 0 0 1px rgba(255, 255, 255, 0.06) inset !important;
        backdrop-filter: none;
        --tw-ring-color: transparent !important;
        animation: auth-card-enter 1s cubic-bezier(0.33, 1, 0.68, 1) both;
    }

    @keyframes auth-card-enter {
        from {
            opacity: 0;
            transform: rotateX(18deg) translateY(40px) translateZ(-80px);
        }
        to {
            opacity: 1;
            transform: rotateX(0deg) translateY(0) translateZ(0);
        }
    }

    .auth-3d-card.is-tilting {
        animation: none;
    }

    .auth-3d-layout .fi-simple-header {
        position: relative;
    }

    .auth-3d-layout .fi-simple-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: min(92%, 24rem);
        height: 11rem;
        border-radius: 1.25rem;
        background: radial-gradient(
            ellipse 95% 88% at 50% 42%,
            rgba(0, 0, 0, 0.95) 0%,
            rgba(0, 0, 0, 0.75) 50%,
            rgba(0, 0, 0, 0.4) 75%,
            transparent 100%
        );
        z-index: 0;
        pointer-events: none;
    }

    .auth-3d-layout .fi-simple-header .fi-logo {
        position: relative;
        z-index: 1;
    }

    .auth-3d-layout .fi-simple-header img.fi-logo.dark\:hidden {
        display: none !important;
    }

    .auth-3d-layout .fi-simple-header img.fi-logo {
        filter: drop-shadow(0 10px 28px rgba(0, 0, 0, 0.55));
    }

    .auth-3d-layout .fi-simple-header-heading,
    .auth-3d-layout .fi-simple-header-subheading {
        color: rgba(255, 255, 255, 0.95) !important;
    }

    .auth-3d-layout .fi-simple-header-subheading {
        color: rgba(255, 255, 255, 0.7) !important;
    }

    .auth-3d-layout .fi-fo-field-wrp-label {
        color: var(--admin-secondary-light) !important;
    }

    .auth-3d-layout .fi-input-wrp {
        background: rgba(255, 255, 255, 0.06) !important;
        border-color: rgba(255, 255, 255, 0.14) !important;
    }

    .auth-3d-layout input {
        color: #fff !important;
    }

    @media (prefers-reduced-motion: reduce) {
        .auth-3d-scene__stage,
        .auth-3d-orb,
        .auth-3d-ring,
        .auth-3d-cube,
        .auth-3d-card {
            animation: none !important;
        }

        .auth-3d-card {
            transform: none !important;
        }
    }

    @media (max-width: 768px) {
        .auth-3d-cube {
            opacity: 0.5;
            transform: scale(0.75);
        }
    }
</style>
