<script setup>
import FaIcon from '@/Components/UI/FaIcon.vue';
import { ACTION_ICON_CLASS, panelCardClass } from '@/Components/Dashboard/dashboardThemes';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Link
        :href="route('admin.personal')"
        class="group flex w-full items-center justify-between gap-6 bg-white px-6 py-5 sm:px-8"
        :class="panelCardClass('admin')"
    >
        <h3 class="text-base font-semibold text-[#2F2A26] sm:text-lg">Gestión de Personal</h3>
        <FaIcon icon="users-gear" :class="ACTION_ICON_CLASS" aria-hidden="true" />
    </Link>
</template>
