<script setup>
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';

const departmentLinks = [
    { routeName: 'admin.qrcodes', label: 'Códigos QR', icon: 'qrcode' },
    { routeName: 'rooms.index', label: 'Habitaciones', icon: 'door-open' },
    { routeName: 'tasks.cleaning', label: 'Limpieza', icon: 'broom' },
    { routeName: 'tasks.maintenance', label: 'Mantenimiento', icon: 'screwdriver-wrench' },
];

defineProps({
    variant: {
        type: String,
        required: true,
        validator: (v) => ['admin', 'recepcion', 'kitchen'].includes(v),
    },
});
</script>

<template>
    <template v-if="variant === 'admin'">
        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">Panel Principal</ResponsiveNavLink>
        <ResponsiveNavLink :href="route('orders.kitchen')" :active="route().current('orders.kitchen')">Pedidos Cocina</ResponsiveNavLink>
        <ResponsiveNavLink :href="route('catalog.index')" :active="route().current('catalog.index')">Gestión de Catálogo</ResponsiveNavLink>
        <ResponsiveNavLink :href="route('activities.index')" :active="route().current('activities.index')">Actividades y Reservas</ResponsiveNavLink>
        <ResponsiveNavLink :href="route('admin.service-requests')" :active="route().current('admin.service-requests')">Peticiones Limp. / Mant.</ResponsiveNavLink>
        <p class="px-4 pb-1 pt-3 text-xs font-bold uppercase tracking-wider text-[#2F2A26]/45">Departamentos</p>
        <ResponsiveNavLink
            v-for="link in departmentLinks"
            :key="link.routeName"
            :href="route(link.routeName)"
            :active="route().current(link.routeName)"
            class="flex items-center gap-2.5"
        >
            <FaIcon :icon="link.icon" class="text-base text-[#A64B35]" />
            {{ link.label }}
        </ResponsiveNavLink>
    </template>

    <template v-else-if="variant === 'recepcion'">
        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">Panel Principal</ResponsiveNavLink>
        <ResponsiveNavLink :href="route('orders.kitchen')" :active="route().current('orders.kitchen')">Pedidos Cocina</ResponsiveNavLink>
        <ResponsiveNavLink :href="route('activities.index')" :active="route().current('activities.index')">Actividades y Reservas</ResponsiveNavLink>
        <ResponsiveNavLink :href="route('admin.service-requests')" :active="route().current('admin.service-requests')">Peticiones Limp. / Mant.</ResponsiveNavLink>
        <p class="px-4 pb-1 pt-3 text-xs font-bold uppercase tracking-wider text-[#2F2A26]/45">Departamentos</p>
        <ResponsiveNavLink
            v-for="link in departmentLinks"
            :key="link.routeName"
            :href="route(link.routeName)"
            :active="route().current(link.routeName)"
            class="flex items-center gap-2.5"
        >
            <FaIcon :icon="link.icon" class="text-base text-[#A64B35]" />
            {{ link.label }}
        </ResponsiveNavLink>
    </template>

    <template v-else-if="variant === 'kitchen'">
        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">Panel Principal</ResponsiveNavLink>
        <ResponsiveNavLink :href="route('orders.kitchen')" :active="route().current('orders.kitchen')">Pedidos Cocina</ResponsiveNavLink>
        <ResponsiveNavLink :href="route('catalog.index')" :active="route().current('catalog.index')">Editar Catálogo</ResponsiveNavLink>
    </template>
</template>
