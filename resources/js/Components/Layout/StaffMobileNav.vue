<script setup>
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

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
        <p class="px-4 pb-1 pt-3 text-xs font-bold uppercase tracking-wider text-gray-400">Departamentos</p>
        <ResponsiveNavLink :href="route('admin.qrcodes')" :active="route().current('admin.qrcodes')">Códigos QR</ResponsiveNavLink>
        <ResponsiveNavLink :href="route('rooms.index')" :active="route().current('rooms.index')">Habitaciones</ResponsiveNavLink>
        <ResponsiveNavLink :href="route('tasks.cleaning')" :active="route().current('tasks.cleaning')">Limpieza</ResponsiveNavLink>
        <ResponsiveNavLink :href="route('tasks.maintenance')" :active="route().current('tasks.maintenance')">Mantenimiento</ResponsiveNavLink>
    </template>

    <template v-else-if="variant === 'recepcion'">
        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">Panel Principal</ResponsiveNavLink>
        <ResponsiveNavLink :href="route('orders.kitchen')" :active="route().current('orders.kitchen')">Pedidos Cocina</ResponsiveNavLink>
        <ResponsiveNavLink :href="route('activities.index')" :active="route().current('activities.index')">Actividades y Reservas</ResponsiveNavLink>
        <ResponsiveNavLink :href="route('admin.service-requests')" :active="route().current('admin.service-requests')">Peticiones Limp. / Mant.</ResponsiveNavLink>
        <p class="px-4 pb-1 pt-3 text-xs font-bold uppercase tracking-wider text-gray-400">Departamentos</p>
        <ResponsiveNavLink :href="route('admin.qrcodes')" :active="route().current('admin.qrcodes')">Códigos QR</ResponsiveNavLink>
        <ResponsiveNavLink :href="route('rooms.index')" :active="route().current('rooms.index')">Habitaciones</ResponsiveNavLink>
        <ResponsiveNavLink :href="route('tasks.cleaning')" :active="route().current('tasks.cleaning')">Limpieza</ResponsiveNavLink>
        <ResponsiveNavLink :href="route('tasks.maintenance')" :active="route().current('tasks.maintenance')">Mantenimiento</ResponsiveNavLink>
    </template>

    <template v-else-if="variant === 'kitchen'">
        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">Panel Principal</ResponsiveNavLink>
        <ResponsiveNavLink :href="route('orders.kitchen')" :active="route().current('orders.kitchen')">Pedidos Cocina</ResponsiveNavLink>
        <ResponsiveNavLink :href="route('catalog.index')" :active="route().current('catalog.index')">Editar Catálogo</ResponsiveNavLink>
    </template>
</template>
