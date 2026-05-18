<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    value: { type: [String, Number], required: true },
    subtitle: { type: String, default: '' },
    badge: { type: String, default: '' },
    badgeTone: {
        type: String,
        default: 'neutral',
        validator: (v) => ['neutral', 'success', 'warning', 'accent'].includes(v),
    },
    href: { type: String, default: null },
});

const badgeClasses = computed(() => {
    const map = {
        success: 'bg-emerald-50 text-emerald-800 ring-emerald-200/80',
        warning: 'bg-amber-50 text-amber-900 ring-amber-200/80',
        accent: 'bg-[#A64B35]/10 text-[#A64B35] ring-[#A64B35]/20',
        neutral: 'bg-[#2F2A26]/5 text-[#2F2A26]/70 ring-[#2F2A26]/10',
    };
    return map[props.badgeTone] ?? map.neutral;
});

const cardClass =
    'group flex h-full flex-col rounded-2xl border border-[#2F2A26]/10 bg-white p-5 shadow-sm transition hover:border-[#2F2A26]/20 hover:shadow-md';
</script>

<template>
    <component :is="href ? Link : 'div'" :href="href || undefined" :class="cardClass">
        <div class="flex items-start justify-between gap-3">
            <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#2F2A26]/5 text-[#2F2A26] ring-1 ring-[#2F2A26]/10"
            >
                <slot name="icon" />
            </div>
            <span
                v-if="badge"
                class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide ring-1"
                :class="badgeClasses"
            >
                {{ badge }}
            </span>
        </div>
        <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-[#2F2A26]/50">{{ title }}</p>
        <p class="mt-1 text-2xl font-black tabular-nums tracking-tight text-[#2F2A26]">{{ value }}</p>
        <p v-if="subtitle" class="mt-1.5 text-sm text-[#2F2A26]/60">{{ subtitle }}</p>
    </component>
</template>
