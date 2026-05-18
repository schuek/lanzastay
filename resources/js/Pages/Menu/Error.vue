<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    message: {
        type: String,
        default: '',
    },
});

const { t } = useI18n();

const displayMessage = computed(() => {
    if (!props.message) {
        return t('errors.menu_default');
    }
    if (props.message.includes('Habitación inactiva') || props.message.includes('check-in')) {
        return t('errors.habitacion_inactiva');
    }
    return props.message;
});
</script>

<template>
    <Head :title="$t('errors.menu_title')" />

    <div class="min-h-screen bg-[#F5F5F5] flex items-center justify-center px-4">
        <div class="max-w-xl w-full bg-white rounded-2xl border border-[#2F2A26]/10 p-8 text-center shadow-sm">
            <h1 class="text-2xl font-black text-[#2F2A26] mb-4">
                LANZA<span class="text-[#A64B35]">STAY</span>
            </h1>
            <p class="text-[#2F2A26]/80 font-medium">
                {{ displayMessage }}
            </p>
        </div>
        </div>
</template>
