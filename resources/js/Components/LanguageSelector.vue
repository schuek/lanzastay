<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();
const isOpen = ref(false);

const options = [
    { code: 'es', label: '🇪🇸 ES' },
    { code: 'en', label: '🇬🇧 EN' },
    { code: 'de', label: '🇩🇪 DE' },
    { code: 'fr', label: '🇫🇷 FR' },
    { code: 'it', label: '🇮🇹 IT' },
];

const changeLang = (lang) => {
    locale.value = lang;
    localStorage.setItem('hotel_lang', lang);
    isOpen.value = false;
};
</script>

<template>
    <div class="relative">
        <button @click="isOpen = !isOpen" class="px-3 py-1.5 rounded-lg border border-[#2F2A26]/20 text-xs font-bold text-[#2F2A26] bg-white">
            {{ locale.toUpperCase() }}
        </button>
        <div v-if="isOpen" class="absolute right-0 mt-2 w-28 bg-white border border-[#2F2A26]/10 rounded-xl shadow-lg z-50">
            <button
                v-for="option in options"
                :key="option.code"
                @click="changeLang(option.code)"
                class="w-full text-left px-3 py-2 text-xs font-semibold text-[#2F2A26] hover:bg-[#A64B35]/10 first:rounded-t-xl last:rounded-b-xl"
            >
                {{ option.label }}
            </button>
        </div>
    </div>
</template>
