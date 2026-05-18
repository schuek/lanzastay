<script setup>
import { Link } from '@inertiajs/vue3';
import {
    ClipboardDocumentCheckIcon,
    HomeIcon,
    PencilSquareIcon,
    QrCodeIcon,
    SparklesIcon,
    TicketIcon,
    UserGroupIcon,
    WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline';

defineProps({
    isAdmin: { type: Boolean, default: false },
});
</script>

<template>
    <aside class="rounded-2xl border border-[#2F2A26]/10 bg-white p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-[#2F2A26]/45">Navegación</p>
        <nav class="mt-3 space-y-1">
            <Link
                :href="route('orders.kitchen')"
                class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium text-[#2F2A26]/80 transition hover:bg-[#2F2A26]/5 hover:text-[#2F2A26]"
            >
                <ClipboardDocumentCheckIcon class="h-4 w-4 shrink-0 text-emerald-600" />
                Pedidos cocina
            </Link>
            <Link
                :href="route('rooms.index')"
                class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium text-[#2F2A26]/80 transition hover:bg-[#2F2A26]/5 hover:text-[#2F2A26]"
            >
                <HomeIcon class="h-4 w-4 shrink-0 text-[#A64B35]" />
                Habitaciones
            </Link>
            <Link
                :href="route('admin.qrcodes')"
                class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium text-[#2F2A26]/80 transition hover:bg-[#2F2A26]/5 hover:text-[#2F2A26]"
            >
                <QrCodeIcon class="h-4 w-4 shrink-0 text-violet-600" />
                Códigos QR
            </Link>
            <Link
                v-if="isAdmin"
                :href="route('catalog.index')"
                class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium text-[#2F2A26]/80 transition hover:bg-[#2F2A26]/5 hover:text-[#2F2A26]"
            >
                <PencilSquareIcon class="h-4 w-4 shrink-0 text-blue-600" />
                Catálogo
            </Link>
            <Link
                :href="route('activities.index')"
                class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium text-[#2F2A26]/80 transition hover:bg-[#2F2A26]/5 hover:text-[#2F2A26]"
            >
                <TicketIcon class="h-4 w-4 shrink-0 text-[#A64B35]" />
                Actividades
            </Link>
            <Link
                :href="route('admin.service-requests')"
                class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium text-[#2F2A26]/80 transition hover:bg-[#2F2A26]/5 hover:text-[#2F2A26]"
            >
                <SparklesIcon class="h-4 w-4 shrink-0 text-[#A64B35]" />
                Peticiones dept.
            </Link>
            <Link
                :href="route('tasks.cleaning')"
                class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium text-[#2F2A26]/80 transition hover:bg-[#2F2A26]/5 hover:text-[#2F2A26]"
            >
                <WrenchScrewdriverIcon class="h-4 w-4 shrink-0 text-amber-600" />
                Tablero limpieza
            </Link>
            <Link
                v-if="isAdmin"
                :href="route('admin.personal')"
                class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium text-[#2F2A26]/80 transition hover:bg-[#2F2A26]/5 hover:text-[#2F2A26]"
            >
                <UserGroupIcon class="h-4 w-4 shrink-0 text-[#2F2A26]" />
                Personal
            </Link>
        </nav>
    </aside>
</template>
