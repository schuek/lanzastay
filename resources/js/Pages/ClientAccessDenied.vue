<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    message: String,
});
</script>

<template>
    <Head title="Acceso no disponible" />

    <div class="min-h-screen bg-[#F5F5F5] flex items-center justify-center px-4">
        <div class="max-w-xl w-full bg-white rounded-2xl border border-[#1A1A1A]/10 p-8 text-center shadow-sm">
            <h1 class="text-2xl font-black text-[#1A1A1A] mb-4">LanzaStay</h1>
            <p class="text-[#1A1A1A]/80 font-medium">
                {{ message }}
            </p>
        </div>
    </div>
</template>
