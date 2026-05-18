<script setup>
import NavLink from '@/Components/NavLink.vue';
import NavModulesDropdown from '@/Components/Layout/NavModulesDropdown.vue';

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
        <NavLink :href="route('dashboard')" :active="route().current('dashboard')">Panel</NavLink>
        <NavLink :href="route('orders.kitchen')" :active="route().current('orders.kitchen')">Pedidos</NavLink>
        <NavLink
            :href="route('catalog.index')"
            :active="route().current('catalog.index') || route().current('catalog.create') || route().current('catalog.edit')"
        >
            Catálogo
        </NavLink>
        <NavLink
            :href="route('activities.index')"
            :active="route().current('activities.index') || route().current('activity-reservations.index')"
        >
            Actividades
        </NavLink>
        <NavLink
            :href="route('admin.service-requests')"
            :active="route().current('admin.service-requests')"
        >
            Peticiones
        </NavLink>
        <NavModulesDropdown />
    </template>

    <template v-else-if="variant === 'recepcion'">
        <NavLink :href="route('dashboard')" :active="route().current('dashboard')">Panel</NavLink>
        <NavLink :href="route('orders.kitchen')" :active="route().current('orders.kitchen')">Pedidos</NavLink>
        <NavLink
            :href="route('activities.index')"
            :active="route().current('activities.index') || route().current('activity-reservations.index')"
        >
            Actividades
        </NavLink>
        <NavLink
            :href="route('admin.service-requests')"
            :active="route().current('admin.service-requests')"
        >
            Peticiones
        </NavLink>
        <NavModulesDropdown />
    </template>

    <template v-else-if="variant === 'kitchen'">
        <NavLink :href="route('dashboard')" :active="route().current('dashboard')">Panel</NavLink>
        <NavLink :href="route('orders.kitchen')" :active="route().current('orders.kitchen')">Pedidos</NavLink>
        <NavLink
            :href="route('catalog.index')"
            :active="route().current('catalog.index') || route().current('catalog.create') || route().current('catalog.edit')"
        >
            Catálogo
        </NavLink>
    </template>
</template>
