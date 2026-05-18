<script setup>
import { Link } from '@inertiajs/vue3';
import {
    ClipboardDocumentCheckIcon,
    HomeIcon,
    PencilSquareIcon,
    QrCodeIcon,
} from '@heroicons/vue/24/outline';

defineProps({
    isAdmin: { type: Boolean, default: false },
});
</script>

<template>
    <section>
        <h2 class="mb-4 text-lg font-bold text-[#2F2A26]">Accesos rápidos</h2>
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <Link
                :href="route('orders.kitchen')"
                class="flex flex-col items-center gap-2 rounded-xl bg-white p-5 text-center shadow-sm transition hover:shadow-md"
            >
                <ClipboardDocumentCheckIcon class="h-8 w-8 text-green-600" />
                <span class="text-sm font-bold text-[#2F2A26]">Pedidos</span>
            </Link>
            <Link
                :href="route('rooms.index')"
                class="flex flex-col items-center gap-2 rounded-xl bg-white p-5 text-center shadow-sm transition hover:shadow-md"
            >
                <HomeIcon class="h-8 w-8 text-[#A64B35]" />
                <span class="text-sm font-bold text-[#2F2A26]">Habitaciones</span>
            </Link>
            <Link
                :href="route('admin.qrcodes')"
                class="flex flex-col items-center gap-2 rounded-xl bg-white p-5 text-center shadow-sm transition hover:shadow-md"
            >
                <QrCodeIcon class="h-8 w-8 text-purple-600" />
                <span class="text-sm font-bold text-[#2F2A26]">QR</span>
            </Link>
            <Link
                v-if="isAdmin"
                :href="route('catalog.index')"
                class="flex flex-col items-center gap-2 rounded-xl bg-white p-5 text-center shadow-sm transition hover:shadow-md"
            >
                <PencilSquareIcon class="h-8 w-8 text-blue-600" />
                <span class="text-sm font-bold text-[#2F2A26]">Catálogo</span>
            </Link>
        </div>
    </section>
</template>
