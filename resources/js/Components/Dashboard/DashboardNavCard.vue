<script setup>
import FaIcon from '@/Components/UI/FaIcon.vue';
import {
    ICON_NAV_SIZE,
    ICON_RING_CLASS,
    panelCardClass,
} from '@/Components/Dashboard/dashboardThemes';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    href: { type: String, required: true },
    actionIcon: { type: String, required: true },
    department: { type: String, required: true },
});

const cardClass = computed(() => [panelCardClass(props.department), 'group'].join(' '));
</script>

<template>
    <Link
        :href="href"
        class="flex min-h-[10.5rem] flex-col items-center justify-center gap-4 px-5 py-7 text-center"
        :class="cardClass"
        :aria-label="title"
    >
        <span :class="[ICON_RING_CLASS, ICON_NAV_SIZE, 'group-hover:bg-[#A64B35]/12']">
            <FaIcon :icon="actionIcon" class="text-2xl text-[#A64B35]" aria-hidden="true" />
        </span>
        <h3 class="max-w-[12rem] text-sm font-semibold leading-snug text-[#2F2A26] sm:text-[15px]">
            {{ title }}
        </h3>
    </Link>
</template>
