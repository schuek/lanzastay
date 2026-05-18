<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { ChevronDownIcon } from '@heroicons/vue/24/outline';

const { locale } = useI18n();

const isOpen = ref(false);
const rootRef = ref(null);

const options = [
    { code: 'es', label: 'ES', name: 'Español', flag: '🇪🇸' },
    { code: 'en', label: 'EN', name: 'English', flag: '🇬🇧' },
    { code: 'fr', label: 'FR', name: 'Français', flag: '🇫🇷' },
    { code: 'de', label: 'DE', name: 'Deutsch', flag: '🇩🇪' },
];

const activeOption = computed(
    () => options.find((option) => option.code === locale.value) ?? options[0],
);

const changeLang = (lang) => {
    if (locale.value !== lang) {
        locale.value = lang;
        localStorage.setItem('hotel_lang', lang);
        document.documentElement.lang = lang;
    }
    isOpen.value = false;
};

const toggleOpen = (event) => {
    event.stopPropagation();
    isOpen.value = !isOpen.value;
};

const onDocumentClick = (event) => {
    if (!rootRef.value?.contains(event.target)) {
        isOpen.value = false;
    }
};

const onEscape = (event) => {
    if (event.key === 'Escape') {
        isOpen.value = false;
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
    <div ref="rootRef" class="relative">
        <button
            type="button"
            class="inline-flex items-center gap-1 rounded-md border border-[#2F2A26]/15 bg-white px-2 py-1.5 text-[#2F2A26] shadow-sm transition hover:bg-[#2F2A26]/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#A64B35]/40"
            :aria-expanded="isOpen"
            aria-haspopup="listbox"
            :aria-label="`${$t('common.idioma')}: ${activeOption.name}`"
            @click="toggleOpen"
        >
            <span class="text-lg leading-none" aria-hidden="true">{{ activeOption.flag }}</span>
            <ChevronDownIcon
                class="h-3.5 w-3.5 text-[#2F2A26]/50 transition-transform duration-200"
                :class="{ 'rotate-180': isOpen }"
                aria-hidden="true"
            />
        </button>

        <Transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 scale-95 -translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 -translate-y-1"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 z-50 mt-1.5 min-w-0 origin-top-right overflow-hidden rounded-md border border-[#2F2A26]/10 bg-white p-1 shadow-lg"
                role="listbox"
                :aria-label="$t('common.idioma')"
            >
                <button
                    v-for="option in options"
                    :key="option.code"
                    type="button"
                    role="option"
                    :aria-selected="locale === option.code"
                    class="flex w-full items-center justify-center rounded-md px-2.5 py-2 text-lg transition-colors"
                    :class="locale === option.code
                        ? 'bg-[#A64B35]/10 ring-1 ring-[#A64B35]/20'
                        : 'hover:bg-[#2F2A26]/5'"
                    :aria-label="option.name"
                    @click="changeLang(option.code)"
                >
                    <span aria-hidden="true">{{ option.flag }}</span>
                </button>
            </div>
        </Transition>
    </div>
</template>
