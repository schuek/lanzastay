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
</script>

<template>
    <Head title="Bienvenidos" />

    <div class="min-h-screen flex flex-col">
        <header class="w-full p-6 flex justify-between items-center bg-white shadow-sm z-10 relative">
            <div class="font-black text-2xl text-indigo-600 tracking-tighter">LANZASTAY</div>

            <nav v-if="canLogin" class="flex gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="font-bold text-gray-600 hover:text-indigo-600 transition"
                >
                    Panel de Control
                </Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="bg-gray-100 px-4 py-2 rounded-lg font-bold text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 transition"
                    >
                        Acceso Empleados
                    </Link>
                </template>
            </nav>
        </header>

        <main class="flex-1 flex flex-col items-center justify-center text-center p-6 relative bg-cover bg-center bg-[url('/images/hero-bg.avif')]">

                <div class="absolute inset-0 bg-black bg-opacity-40"></div>

                <div class="relative z-10 max-w-3xl text-white mt-[-10vh] animate-in fade-in zoom-in duration-700">
                    <h1 class="text-5xl md:text-7xl font-black mb-6 drop-shadow-lg tracking-tight">Tu descanso en el paraíso.</h1>
                    <p class="text-xl md:text-2xl mb-10 text-gray-100 drop-shadow-md font-light">
                        La experiencia más moderna de la isla. Relájate, pide desde tu cama y disfruta de las vistas.
                    </p>
                <a href="#contacto" class="bg-indigo-600 text-white px-8 py-4 rounded-full font-bold text-lg hover:bg-indigo-700 transition shadow-xl">
                    Descubre más
                </a>
            </div>
        </main>
    </div>
</template>
