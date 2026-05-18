<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    pending: { type: Number, default: 0 },
    readonly: { type: Boolean, default: false },
    href: { type: String, default: null },
});
</script>

<template>
    <component
        :is="href ? 'a' : 'div'"
        :href="href ?? undefined"
        class="block rounded-xl border border-[#2F2A26]/10 bg-white p-5 shadow-sm transition"
        :class="href ? (readonly ? 'hover:border-[#2F2A26]/20' : 'cursor-pointer hover:border-[#A64B35]/30 hover:shadow-md') : ''"
    >
        <div class="flex items-center justify-between gap-3">
            <slot name="icon" />
            <span
                class="rounded-full px-3 py-1 text-sm font-bold tabular-nums"
                :class="readonly ? 'bg-gray-100 text-gray-700' : 'bg-[#A64B35]/10 text-[#A64B35]'"
            >
                {{ pending }} activos
            </span>
        </div>
        <h3 class="mt-3 font-bold text-[#2F2A26]">{{ title }}</h3>
        <p v-if="subtitle" class="mt-1 text-xs text-[#2F2A26]/55">{{ subtitle }}</p>
        <p v-if="readonly" class="mt-2 text-[10px] font-semibold uppercase tracking-wide text-[#2F2A26]/40">
            Solo lectura
        </p>
    </component>
</template>
