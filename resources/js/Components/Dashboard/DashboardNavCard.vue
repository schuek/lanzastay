<script setup>
import FaIcon from '@/Components/UI/FaIcon.vue';
import { ACTION_ICON_CLASS, panelCardClass } from '@/Components/Dashboard/dashboardThemes';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    href: { type: String, required: true },
    actionIcon: { type: String, required: true },
    department: { type: String, required: true },
});

const cardClass = computed(() => panelCardClass(props.department));
</script>

<template>
    <Link
        :href="href"
        class="group flex min-h-[11rem] flex-col justify-between bg-white px-5 py-5"
        :class="cardClass"
        :aria-label="title"
    >
        <h3 class="text-sm font-semibold leading-snug text-[#2F2A26] sm:text-base">
            {{ title }}
        </h3>

        <div class="mt-4 flex justify-end">
            <FaIcon :icon="actionIcon" :class="ACTION_ICON_CLASS" />
        </div>
    </Link>
</template>
