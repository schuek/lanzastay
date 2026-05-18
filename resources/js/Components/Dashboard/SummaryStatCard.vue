<script setup>
defineProps({
    title: { type: String, required: true },
    value: { type: [Number, String], required: true },
    iconBgClass: { type: String, default: 'bg-[#A64B35]/10' },
});
</script>

<template>
    <div class="flex items-center gap-3 rounded-xl border border-[#2F2A26]/10 bg-white p-4 shadow-sm">
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg" :class="iconBgClass">
            <slot name="icon" />
        </div>
        <div class="flex min-w-0 flex-1 items-center justify-between gap-3">
            <p class="text-sm font-semibold text-[#2F2A26]/70">{{ title }}</p>
            <p class="shrink-0 text-xl font-black tabular-nums text-[#2F2A26]">{{ value }}</p>
        </div>
    </div>
</template>
