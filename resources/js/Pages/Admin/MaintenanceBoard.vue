<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MaintenanceBoardPanel from '@/Components/Admin/MaintenanceBoardPanel.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    orders: { type: Array, default: () => [] },
    rooms: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Tablero de Mantenimiento" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#F9FAFB] py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <MaintenanceBoardPanel :orders="orders" :rooms="rooms" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
