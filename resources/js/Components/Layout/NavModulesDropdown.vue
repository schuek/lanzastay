<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import {
    HomeIcon,
    QrCodeIcon,
    SparklesIcon,
    WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline';

const isDepartamentosOpen = ref(false);
const dropdownRoot = ref(null);

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
            class="absolute start-0 z-50 mt-2 min-w-[12rem] rounded-lg border border-gray-100 bg-white py-1 shadow-lg"
            role="menu"
            @click="closeDepartamentos"
        >
            <DropdownLink :href="route('admin.qrcodes')" class="flex items-center gap-2">
                <QrCodeIcon class="h-4 w-4 text-purple-600" />
                Códigos QR
            </DropdownLink>
            <DropdownLink :href="route('rooms.index')" class="flex items-center gap-2">
                <HomeIcon class="h-4 w-4 text-[#A64B35]" />
                Habitaciones
            </DropdownLink>
            <DropdownLink :href="route('admin.service-requests')" class="flex items-center gap-2">
                <SparklesIcon class="h-4 w-4 text-[#A64B35]" />
                Peticiones Limp. / Mant.
            </DropdownLink>
            <DropdownLink :href="route('tasks.cleaning')" class="flex items-center gap-2">
                <SparklesIcon class="h-4 w-4 text-violet-600" />
                Tablero limpieza
            </DropdownLink>
            <DropdownLink :href="route('tasks.maintenance')" class="flex items-center gap-2">
                <WrenchScrewdriverIcon class="h-4 w-4 text-amber-600" />
                Tablero mantenimiento
            </DropdownLink>
        </div>
    </div>
</template>
