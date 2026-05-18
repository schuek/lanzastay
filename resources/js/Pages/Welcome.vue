<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});

const urlParams = new URLSearchParams(window.location.search);
const roomNumber = urlParams.get('room');
</script>

<template>
    <Head title="Bienvenidos" />

    <div class="relative flex h-screen w-screen flex-col bg-cover bg-center bg-no-repeat bg-[url('/images/welcome.avif')]">
        <div
            class="absolute inset-0 bg-black/50"
            aria-hidden="true"
        />

        <main class="relative z-10 flex flex-1 flex-col items-center justify-center px-4 py-16 sm:px-6">
            <div
                class="w-full max-w-lg rounded-2xl border border-white/20 bg-white/10 p-8 text-center shadow-2xl backdrop-blur-md sm:p-10"
            >
                <p class="text-3xl font-black leading-none tracking-tight text-white sm:text-4xl">
                    LANZA<span class="text-[#A64B35]">STAY</span>
                </p>

                <div
                    v-if="roomNumber"
                    class="mt-6 rounded-xl border border-[#A64B35]/40 bg-[#A64B35]/20 px-4 py-3"
                >
                    <p class="text-xs font-semibold uppercase tracking-widest text-[#A64B35]">
                        Tu habitación
                    </p>
                    <p class="mt-1 text-xl font-bold text-white sm:text-2xl">
                        Habitación {{ roomNumber }}
                    </p>
                </div>

                <p class="mt-6 text-sm leading-relaxed text-white/95 sm:text-base">
                    Bienvenido a tu estancia. Para acceder a los servicios de tu habitación, por favor escanea el código QR que encontrarás en tu mesita de noche.
                </p>
            </div>

            <Link
                v-if="canLogin && !$page.props.auth?.user"
                :href="route('login')"
                class="mt-10 inline-flex items-center justify-center rounded-xl border border-white px-8 py-3 text-sm font-semibold tracking-wide text-white transition hover:bg-white hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white/80"
            >
                Acceso Empleados
            </Link>

            <Link
                v-else-if="canLogin && $page.props.auth?.user"
                :href="route('dashboard')"
                class="mt-10 inline-flex items-center justify-center rounded-xl border border-white px-8 py-3 text-sm font-semibold tracking-wide text-white transition hover:bg-white hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white/80"
            >
                Panel Principal
            </Link>
        </main>
    </div>
</template>
