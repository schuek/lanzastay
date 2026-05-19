<script setup>
import FaIcon from '@/Components/UI/FaIcon.vue';
import {
    ICON_RING_CLASS,
    ICON_STRIP_SIZE,
    panelCardClass,
} from '@/Components/Dashboard/dashboardThemes';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Link
        :href="route('admin.personal')"
        class="group flex w-full flex-col items-center justify-center gap-3 px-6 py-7 text-center sm:flex-row sm:gap-5"
        :class="panelCardClass('admin')"
    >
        <span :class="[ICON_RING_CLASS, ICON_STRIP_SIZE, 'group-hover:bg-[#A64B35]/12']">
            <FaIcon icon="users-gear" class="text-xl text-[#A64B35]" aria-hidden="true" />
        </span>
        <h3 class="text-base font-semibold text-[#2F2A26] sm:text-lg">
            Gestión de Personal
        </h3>
    </Link>
</template>
