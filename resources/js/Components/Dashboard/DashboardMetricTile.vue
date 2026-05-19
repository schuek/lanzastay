<script setup>
import FaIcon from '@/Components/UI/FaIcon.vue';
import {
    ICON_METRIC_SIZE,
    ICON_RING_CLASS,
    metricCardClass,
} from '@/Components/Dashboard/dashboardThemes';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: String, required: true },
    href: { type: String, default: null },
    icon: { type: String, required: true },
    department: { type: String, required: true },
});

const cardClass = computed(() => [
    metricCardClass(props.department),
    props.href ? 'group' : '',
]);
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :href="href || undefined"
        class="flex min-h-[9.5rem] flex-col items-center justify-center px-5 py-7 text-center"
        :class="cardClass"
    >
        <span :class="[ICON_RING_CLASS, ICON_METRIC_SIZE, 'group-hover:bg-[#A64B35]/12']">
            <FaIcon :icon="icon" class="text-xl text-[#A64B35]" aria-hidden="true" />
        </span>
        <p class="mt-5 text-[10px] font-semibold uppercase tracking-[0.14em] text-[#2F2A26]/50">
            {{ label }}
        </p>
        <p class="mt-1.5 text-2xl font-bold tabular-nums leading-none text-[#2F2A26]">
            {{ value }}
        </p>
    </component>
</template>
