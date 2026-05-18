<script setup>
import DepartmentStatCard from '@/Components/Dashboard/DepartmentStatCard.vue';
import { Link } from '@inertiajs/vue3';
import {
    ClipboardDocumentCheckIcon,
    HomeIcon,
    PencilSquareIcon,
    QrCodeIcon,
    SparklesIcon,
    TicketIcon,
    WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline';

defineProps({
    departmentStats: { type: Object, default: null },
    isAdmin: { type: Boolean, default: false },
    showQuickAccess: { type: Boolean, default: true },
});
</script>

<template>
    <div class="space-y-8">
    <section>
        <h2 class="text-lg font-bold text-[#2F2A26]">Actividades y reservas</h2>
        <p class="mt-1 text-sm text-[#2F2A26]/65">Crear, editar y cancelar excursiones y reservas.</p>
        <Link
            :href="route('activities.index')"
            class="mt-4 flex items-center gap-5 rounded-xl border-2 border-[#A64B35]/25 bg-white p-6 shadow-sm transition hover:border-[#A64B35]/50 hover:shadow-md"
        >
            <div class="rounded-2xl bg-[#A64B35]/10 p-4">
                <TicketIcon class="h-10 w-10 text-[#A64B35]" />
            </div>
            <div class="min-w-0 flex-1">
                <h3 class="text-xl font-bold text-[#2F2A26]">Módulo de actividades</h3>
                <p class="mt-1 text-sm text-[#2F2A26]/65">Gestión completa: alta, edición y cancelación de reservas.</p>
            </div>
            <span class="hidden text-[#A64B35] sm:block">→</span>
        </Link>
    </section>

    <section v-if="departmentStats">
        <h2 class="text-lg font-bold text-[#2F2A26]">Estado de departamentos</h2>
        <p class="mt-1 text-sm text-[#2F2A26]/65">
            {{ isAdmin ? 'Supervisión operativa de todos los departamentos.' : 'Resumen en tiempo real (solo lectura).' }}
        </p>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <DepartmentStatCard
                title="Cocina"
                subtitle="Pedidos en preparación"
                :pending="departmentStats.kitchen?.pending ?? 0"
                :readonly="!isAdmin"
                :href="route('orders.kitchen')"
            >
                <template #icon>
                    <ClipboardDocumentCheckIcon class="h-8 w-8 text-green-600" />
                </template>
            </DepartmentStatCard>
            <DepartmentStatCard
                title="Limpieza"
                subtitle="Peticiones de aseo"
                :pending="departmentStats.cleaning?.pending ?? 0"
                readonly
                :href="route('tasks.cleaning')"
            >
                <template #icon>
                    <SparklesIcon class="h-8 w-8 text-[#A64B35]" />
                </template>
            </DepartmentStatCard>
            <DepartmentStatCard
                title="Mantenimiento"
                subtitle="Averías reportadas"
                :pending="departmentStats.maintenance?.pending ?? 0"
                readonly
                :href="route('tasks.maintenance')"
            >
                <template #icon>
                    <WrenchScrewdriverIcon class="h-8 w-8 text-amber-600" />
                </template>
            </DepartmentStatCard>
        </div>
    </section>

    <section v-if="showQuickAccess">
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
    </div>
</template>
