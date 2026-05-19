<script setup>
import DropdownLink from '@/Components/DropdownLink.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const isDepartamentosOpen = ref(false);
const dropdownRoot = ref(null);

const departmentItems = [
    { href: () => route('admin.qrcodes'), label: 'Códigos QR', icon: 'qrcode' },
    { href: () => route('rooms.index'), label: 'Habitaciones', icon: 'door-open' },
    { href: () => route('admin.service-requests'), label: 'Peticiones Limp. / Mant.', icon: 'clipboard-list' },
    { href: () => route('tasks.cleaning'), label: 'Tablero limpieza', icon: 'broom' },
    { href: () => route('tasks.maintenance'), label: 'Tablero mantenimiento', icon: 'screwdriver-wrench' },
];

const isModulesActive = computed(() => {
    const r = route();
    return (
        r.current('admin.qrcodes')
        || r.current('rooms.index')
        || r.current('habitaciones.index')
        || r.current('tasks.cleaning')
        || r.current('tasks.maintenance')
        || r.current('admin.service-requests')
    );
});

const triggerClass = computed(() =>
    isModulesActive.value
        ? 'inline-flex items-center gap-1 border-b-2 border-[#A64B35] px-1 pt-1 text-sm font-semibold text-[#2F2A26]'
        : 'inline-flex items-center gap-1 border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-[#2F2A26]/60 hover:border-[#2F2A26]/20 hover:text-[#2F2A26]',
);

const closeDepartamentos = () => {
    isDepartamentosOpen.value = false;
};

const onDocumentClick = (event) => {
    if (!isDepartamentosOpen.value) {
        return;
    }
    if (dropdownRoot.value && !dropdownRoot.value.contains(event.target)) {
        closeDepartamentos();
    }
};

const onEscape = (event) => {
    if (event.key === 'Escape') {
        closeDepartamentos();
    }
};

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onEscape);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onEscape);
});
</script>

<template>
    <div ref="dropdownRoot" class="relative">
        <button
            type="button"
            :class="triggerClass"
            aria-haspopup="true"
            :aria-expanded="isDepartamentosOpen"
            @click.stop="isDepartamentosOpen = !isDepartamentosOpen"
        >
            Departamentos
            <svg
                class="h-4 w-4 shrink-0 opacity-70 transition-transform"
                :class="{ 'rotate-180': isDepartamentosOpen }"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>

        <div
            v-show="isDepartamentosOpen"
            class="absolute start-0 z-50 mt-2 min-w-[14rem] rounded-xl border border-[#2F2A26]/10 bg-white py-1.5 shadow-lg"
            role="menu"
            @click="closeDepartamentos"
        >
            <DropdownLink
                v-for="item in departmentItems"
                :key="item.icon"
                :href="item.href()"
                class="flex items-center gap-2.5 !text-[#2F2A26] hover:!bg-[#A64B35]/[0.06]"
            >
                <span
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#A64B35]/[0.08]"
                >
                    <FaIcon :icon="item.icon" class="text-sm text-[#A64B35]" />
                </span>
                <span class="text-sm font-medium leading-snug">{{ item.label }}</span>
            </DropdownLink>
        </div>
    </div>
</template>
