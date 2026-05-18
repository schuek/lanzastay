<script setup>
import DashboardKpiCard from '@/Components/Dashboard/DashboardKpiCard.vue';
import DashboardOperationsPanel from '@/Components/Dashboard/DashboardOperationsPanel.vue';
import DashboardQuickAccessSidebar from '@/Components/Dashboard/DashboardQuickAccessSidebar.vue';
import {
    BuildingOffice2Icon,
    ClipboardDocumentListIcon,
    HomeIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    operationalKpis: { type: Object, required: true },
    departmentStats: { type: Object, default: null },
    isAdmin: { type: Boolean, default: false },
    title: { type: String, required: true },
    subtitle: { type: String, required: true },
});

const occupancyBadge = computed(() => {
    const { occupied = 0, total = 0 } = props.operationalKpis?.occupancy ?? {};
    if (total === 0) return { text: 'Sin datos', tone: 'neutral' };
    if (occupied === 0) return { text: 'Vacío', tone: 'neutral' };
    if (occupied >= total) return { text: 'Completo', tone: 'warning' };
    return { text: 'Activo', tone: 'success' };
});

const kitchenBadge = computed(() => {
    const n = props.operationalKpis?.kitchen_pending ?? 0;
    if (n === 0) return { text: 'Al día', tone: 'success' };
    if (n <= 3) return { text: 'En curso', tone: 'warning' };
    return { text: 'Alta carga', tone: 'warning' };
});

const servicesBadge = computed(() => {
    const n = props.operationalKpis?.services_pending ?? 0;
    if (n === 0) return { text: 'Sin pendientes', tone: 'success' };
    return { text: 'Pendiente', tone: 'warning' };
});

const availableBadge = computed(() => {
    const n = props.operationalKpis?.occupancy?.available ?? 0;
    if (n === 0) return { text: 'Sin libres', tone: 'neutral' };
    return { text: 'Disponible', tone: 'success' };
});

const occupancyDisplay = computed(() => {
    const label = props.operationalKpis?.occupancy?.label;
    if (!label) return '—';
    return `${label} Habitaciones`;
});
</script>

<template>
    <div class="space-y-8">
        <header>
            <h1 class="text-2xl font-black text-[#2F2A26]">{{ title }}</h1>
            <p class="mt-1 text-sm text-[#2F2A26]/65">{{ subtitle }}</p>
        </header>

        <section aria-label="Métricas clave">
            <div class="mb-3 flex items-end justify-between gap-2">
                <h2 class="text-sm font-bold uppercase tracking-wide text-[#2F2A26]/50">Métricas clave</h2>
                <span class="text-[10px] font-medium text-[#2F2A26]/40">Actualización al cargar</span>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <DashboardKpiCard
                    :title="'Ocupación actual'"
                    :value="occupancyDisplay"
                    :subtitle="operationalKpis.occupancy?.subtitle ?? 'Habitaciones ocupadas'"
                    :badge="occupancyBadge.text"
                    :badge-tone="occupancyBadge.tone"
                    :href="route('rooms.index')"
                >
                    <template #icon>
                        <BuildingOffice2Icon class="h-6 w-6" />
                    </template>
                </DashboardKpiCard>

                <DashboardKpiCard
                    title="Comandas en cocina"
                    :value="operationalKpis.kitchen_pending ?? 0"
                    subtitle="Pedidos activos en preparación"
                    :badge="kitchenBadge.text"
                    :badge-tone="kitchenBadge.tone"
                    :href="route('orders.kitchen')"
                >
                    <template #icon>
                        <ClipboardDocumentListIcon class="h-6 w-6" />
                    </template>
                </DashboardKpiCard>

                <DashboardKpiCard
                    title="Servicios pendientes"
                    :value="operationalKpis.services_pending ?? 0"
                    :subtitle="`Limpieza ${operationalKpis.cleaning_pending ?? 0} · Mant. ${operationalKpis.maintenance_pending ?? 0}`"
                    :badge="servicesBadge.text"
                    :badge-tone="servicesBadge.tone"
                    :href="route('admin.service-requests')"
                >
                    <template #icon>
                        <SparklesIcon class="h-6 w-6 text-[#A64B35]" />
                    </template>
                </DashboardKpiCard>

                <DashboardKpiCard
                    title="Habitaciones libres"
                    :value="operationalKpis.occupancy?.available ?? 0"
                    subtitle="Listas para check-in"
                    :badge="availableBadge.text"
                    :badge-tone="availableBadge.tone"
                    :href="route('rooms.index')"
                >
                    <template #icon>
                        <HomeIcon class="h-6 w-6 text-[#2F2A26]/70" />
                    </template>
                </DashboardKpiCard>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <div class="space-y-8 lg:col-span-8 xl:col-span-9">
                <DashboardOperationsPanel
                    :department-stats="departmentStats"
                    :is-admin="isAdmin"
                    :show-quick-access="false"
                />
            </div>
            <div class="lg:col-span-4 xl:col-span-3">
                <DashboardQuickAccessSidebar :is-admin="isAdmin" />
            </div>
        </div>
    </div>
</template>
