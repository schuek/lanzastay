<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CleaningBoardPanel from '@/Components/Admin/CleaningBoardPanel.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    orders: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Tablero de Limpieza" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#F9FAFB] py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <CleaningBoardPanel :orders="orders" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
