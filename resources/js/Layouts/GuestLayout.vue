<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="guest-shell">
        <div class="guest-inner">
            <Link href="/" class="guest-home-link" aria-label="Voltar ao início">
                <ApplicationLogo class="guest-logo" />
            </Link>

            <slot />
        </div>
    </div>
</template>

<style scoped>
.guest-shell {
    min-height: 100vh;
    padding: 1.5rem;
    background:
        radial-gradient(circle at 10% 10%, rgb(212 122 80 / .14), transparent 30%),
        radial-gradient(circle at 90% 90%, rgb(23 50 77 / .12), transparent 34%),
        #edf2f3;
}

.guest-inner {
    display: grid;
    min-height: calc(100vh - 3rem);
    place-items: center;
}

.guest-home-link {
    position: absolute;
    top: 1.75rem;
    left: 1.75rem;
    z-index: 2;
    display: grid;
    width: 2.8rem;
    height: 2.8rem;
    place-items: center;
    padding: .55rem;
    border: 1px solid rgb(23 50 77 / .12);
    border-radius: .65rem;
    background: rgb(255 255 255 / .72);
    box-shadow: 0 8px 24px rgb(23 50 77 / .08);
}

.guest-logo { width: 100%; height: 100%; fill: #ad5d3b; color: #ad5d3b; }

:global(.dark) .guest-shell {
    background:
        radial-gradient(circle at 10% 10%, rgb(173 93 59 / .2), transparent 30%),
        #111c24;
}

:global(.dark) .guest-home-link { border-color: #344752; background: #22333e; }
:global(.dark) .guest-logo { fill: #e49a70; color: #e49a70; }

@media (max-width: 640px) {
    .guest-shell { padding: .75rem; }
    .guest-inner { min-height: calc(100vh - 1.5rem); }
    .guest-home-link { top: 1rem; left: 1rem; }
}
</style>
