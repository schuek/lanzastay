<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    message: {
        type: String,
        default: 'No se puede cargar el menú en este momento.',
    },
});
</script>

<template>
    <Head title="Menú no disponible" />

    <div class="min-h-screen bg-[#F5F5F5] flex items-center justify-center px-4">
        <div class="max-w-xl w-full bg-white rounded-2xl border border-[#2F2A26]/10 p-8 text-center shadow-sm">
            <h1 class="text-2xl font-black text-[#2F2A26] mb-4">
                LANZA<span class="text-[#A64B35]">STAY</span>
            </h1>
            <p class="text-[#2F2A26]/80 font-medium">
                {{ message }}
            </p>
        </div>
    </div>
</template>
