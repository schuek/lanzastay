<script setup>
import DashboardHrStrip from '@/Components/Dashboard/DashboardHrStrip.vue';
import DashboardMetricTile from '@/Components/Dashboard/DashboardMetricTile.vue';
import DashboardNavCard from '@/Components/Dashboard/DashboardNavCard.vue';
import { useAuthRole } from '@/composables/useAuthRole';
import { computed } from 'vue';

const props = defineProps({
    operationalKpis: { type: Object, required: true },
    departmentStats: { type: Object, default: null },
    isAdmin: { type: Boolean, default: false },
});

const { authUser } = useAuthRole();

const brandTitle = computed(() => (props.isAdmin ? 'LanzaStay Admin' : 'LanzaStay Recepción'));

const greetingName = computed(() => {
    if (props.isAdmin) {
        return 'Administrador';
    }
    return authUser.value?.name?.split(' ')[0] ?? 'Usuario';
});

const occupancyPercent = computed(() => {
    const { occupied = 0, total = 0 } = props.operationalKpis?.occupancy ?? {};
    if (total === 0) {
        return '—';
    }
    const label = props.operationalKpis?.occupancy?.label;
    return label ? `${Math.round((occupied / total) * 100)}% · ${label}` : `${Math.round((occupied / total) * 100)}%`;
});

const pendingLabel = (count) => {
    const n = Number(count) || 0;
    return n === 1 ? '1' : String(n);
};

const navPanels = computed(() => {
    const panels = [
        {
            title: 'Gestión Habitaciones',
            href: route('rooms.index'),
            actionIcon: 'door-open',
            department: 'reception',
        },
        {
            title: 'Generador QRs',
            href: route('admin.qrcodes'),
            actionIcon: 'qrcode',
            department: 'reception',
        },
        {
            title: 'Pedidos Cocina',
            href: route('orders.kitchen'),
            actionIcon: 'fire-burner',
            department: 'kitchen',
        },
        {
            title: 'Actividades y Reservas',
            href: route('activities.index'),
            actionIcon: 'ticket-simple',
            department: 'reception',
        },
        {
            title: 'Tablero Limpieza',
            href: route('tasks.cleaning'),
            actionIcon: 'broom',
            department: 'cleaning',
        },
        {
            title: 'Tablero Mantenimiento',
            href: route('tasks.maintenance'),
            actionIcon: 'screwdriver-wrench',
            department: 'maintenance',
        },
    ];

    if (props.isAdmin) {
        panels.splice(2, 0, {
            title: 'Catálogo Carta',
            href: route('catalog.index'),
            actionIcon: 'book-open-dish',
            department: 'kitchen',
        });
    }

    return panels;
});
</script>

<template>
    <div class="min-h-[calc(100vh-4rem)] bg-[#FAFAFA]">
        <div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-10">
            <header>
                <p class="text-xs font-medium uppercase tracking-[0.2em] text-[#2F2A26]/55">{{ brandTitle }}</p>
                <h1 class="mt-1 text-2xl font-semibold text-[#2F2A26] sm:text-3xl">Hola, {{ greetingName }}</h1>
            </header>

            <section aria-label="Panel de estado operativo">
                <h2 class="mb-4 text-xs font-semibold uppercase tracking-[0.12em] text-[#2F2A26]/55">
                    Estado operativo
                </h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <DashboardMetricTile
                        label="Ocupación"
                        :value="occupancyPercent"
                        :href="route('rooms.index')"
                        icon="hotel"
                        department="reception"
                    />
                    <DashboardMetricTile
                        label="Comandas Cocina"
                        :value="pendingLabel(operationalKpis.kitchen_pending)"
                        :href="route('orders.kitchen')"
                        icon="bowl-food"
                        department="kitchen"
                    />
                    <DashboardMetricTile
                        label="Tareas Limpieza"
                        :value="pendingLabel(operationalKpis.cleaning_pending)"
                        :href="route('tasks.cleaning')"
                        icon="pump-soap"
                        department="cleaning"
                    />
                    <DashboardMetricTile
                        label="Tareas Mantenimiento"
                        :value="pendingLabel(operationalKpis.maintenance_pending)"
                        :href="route('tasks.maintenance')"
                        icon="toolbox"
                        department="maintenance"
                    />
                </div>
            </section>

            <section aria-label="Panel de control">
                <h2 class="mb-4 text-xs font-semibold uppercase tracking-[0.12em] text-[#2F2A26]/55">
                    Panel de control
                </h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <DashboardNavCard
                        v-for="panel in navPanels"
                        :key="panel.href"
                        :title="panel.title"
                        :href="panel.href"
                        :action-icon="panel.actionIcon"
                        :department="panel.department"
                    />
                </div>
            </section>

            <section v-if="isAdmin" aria-label="Gestión de personal">
                <DashboardHrStrip />
            </section>
        </div>
    </div>
</template>
