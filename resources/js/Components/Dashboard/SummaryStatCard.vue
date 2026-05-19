<script setup>
defineProps({
    title: { type: String, required: true },
    value: { type: [Number, String], required: true },
});
</script>

<template>
    <div
        class="flex min-h-[7.5rem] flex-col items-center justify-center rounded-2xl border border-[#A64B35]/35 bg-white px-5 py-6 text-center shadow-[0_1px_3px_rgba(47,42,38,0.05)]"
    >
        <span
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#A64B35]/[0.08] ring-1 ring-[#A64B35]/15"
        >
            <slot name="icon" />
        </span>
        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.12em] text-[#2F2A26]/50">
            {{ title }}
        </p>
        <p class="mt-1 text-2xl font-bold tabular-nums leading-none text-[#2F2A26]">
            {{ value }}
        </p>
    </div>
</template>
