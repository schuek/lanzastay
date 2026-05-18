<script setup>
import FaIcon from '@/Components/UI/FaIcon.vue';
import { metricCardClass } from '@/Components/Dashboard/dashboardThemes';
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
    props.href ? 'group cursor-pointer' : '',
]);
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :href="href || undefined"
        class="flex flex-col items-center justify-center px-4 py-6 text-center"
        :class="cardClass"
    >
        <FaIcon :icon="icon" class="text-lg text-[#2F2A26]" />
        <p class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-[#2F2A26]/55">{{ label }}</p>
        <p class="mt-1 text-2xl font-bold text-[#2F2A26]">{{ value }}</p>
    </component>
</template>
