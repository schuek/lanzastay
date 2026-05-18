<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2.5 border-l-4 border-[#A64B35] text-start text-base font-semibold text-[#2F2A26] bg-[#A64B35]/10 focus:outline-none focus:bg-[#A64B35]/15 transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2.5 border-l-4 border-transparent text-start text-base font-medium text-[#2F2A26]/75 hover:text-[#2F2A26] hover:bg-gray-50 hover:border-[#2F2A26]/15 focus:outline-none focus:text-[#2F2A26] transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
