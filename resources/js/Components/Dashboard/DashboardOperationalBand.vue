<script setup>
import FaIcon from '@/Components/UI/FaIcon.vue';
import { CARD_INTERACTIVE, CARD_SHELL } from '@/Components/Dashboard/dashboardThemes';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    departmentStats: { type: Object, default: null },
});

const departments = computed(() => {
    const kitchen = props.departmentStats?.kitchen?.pending ?? 0;
    const cleaning = props.departmentStats?.cleaning?.pending ?? 0;
    const maintenance = props.departmentStats?.maintenance?.pending ?? 0;

    const statusFor = (pending) => ({
        tone: pending > 0 ? 'pending' : 'active',
        label: pending > 0 ? 'Pendiente' : 'Activo',
    });

    return [
        {
            key: 'kitchen',
            name: 'Cocina',
            icon: 'utensils',
            department: 'kitchen',
            pending: kitchen,
            href: route('orders.kitchen'),
            ...statusFor(kitchen),
        },
        {
            key: 'reception',
            name: 'Recepción',
            icon: 'bell-concierge',
            department: 'reception',
            pending: 0,
            href: route('rooms.index'),
            tone: 'active',
            label: 'Activo',
        },
        {
            key: 'cleaning',
            name: 'Limpieza',
            icon: 'broom',
            department: 'cleaning',
            pending: cleaning,
            href: route('tasks.cleaning'),
            ...statusFor(cleaning),
        },
        {
            key: 'maintenance',
            name: 'Mantenimiento',
            icon: 'toolbox',
            department: 'maintenance',
            pending: maintenance,
            href: route('tasks.maintenance'),
            ...statusFor(maintenance),
        },
    ];
});

const dotClass = (tone, department) => {
    if (tone === 'pending') {
        return 'bg-amber-400 ring-2 ring-amber-200';
    }
    const map = {
        kitchen: 'bg-[#A64B35] ring-2 ring-[#A64B35]/30',
        cleaning: 'bg-[#5FC34B] ring-2 ring-[#5FC34B]/30',
        maintenance: 'bg-[#8C6239] ring-2 ring-[#D9C5B2]',
        reception: 'bg-[#0A6ACF] ring-2 ring-[#0A6ACF]/30',
    };
    return map[department] ?? 'bg-[#0A6ACF]';
};
</script>

<template>
    <section aria-label="Estado operativo">
        <h2 class="mb-4 text-xs font-bold uppercase tracking-wider text-[#2F2A26]/50">Estado operativo</h2>
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <Link
                v-for="dept in departments"
                :key="dept.key"
                :href="dept.href"
                class="group flex flex-col p-4"
                :class="[CARD_SHELL, CARD_INTERACTIVE, 'cursor-pointer']"
            >
                <div class="flex items-center justify-between gap-2">
                    <FaIcon :icon="dept.icon" class="text-2xl text-[#A64B35]" />
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" :class="dotClass(dept.tone, dept.department)" />
                </div>
                <p class="mt-3 text-sm font-bold text-[#2F2A26]">{{ dept.name }}</p>
                <p class="mt-1 text-xs font-medium text-[#2F2A26]/55">
                    {{ dept.label }}
                    <span v-if="dept.pending > 0" class="text-amber-700"> · {{ dept.pending }}</span>
                </p>
            </Link>
        </div>
    </section>
</template>
