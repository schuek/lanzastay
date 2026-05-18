<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    order: Object,
});

const { tm } = useI18n();
const orderState = ref(props?.order ?? null);
let pollingInterval = null;

const stepKeysByType = {
    comida: ['recibido', 'en_proceso', 'en_camino', 'completado'],
    limpieza: ['recibido', 'en_proceso', 'en_camino', 'completado'],
    mantenimiento: ['recibido', 'en_proceso', 'en_camino', 'completado'],
};

const steps = computed(() => {
    const type = orderState.value?.service_type ?? 'comida';
    const keys = stepKeysByType[type] ?? stepKeysByType.comida;
    const labels = tm(`tracking.steps.${type}`);

    return keys.map((key, index) => ({
        key,
        label: Array.isArray(labels) ? labels[index] : String(labels),
    }));
});

const currentStepIndex = computed(() => {
    const status = orderState.value?.status;
    return Math.max(steps.value.findIndex((step) => step.key === status), 0);
});

const currentStatusLabel = computed(() => steps.value[currentStepIndex.value]?.label ?? '');

const connectorFillWidth = computed(() => {
    const total = Math.max(steps.value.length - 1, 1);
    const ratio = currentStepIndex.value / total;
    return `calc((100% - 2rem) * ${ratio})`;
});

const fetchStatus = async () => {
    if (!orderState.value?.id) return;

    const response = await axios.get(`/api/orders/${orderState.value.id}/status`);
    if (response.data?.order) {
        orderState.value = response.data.order;
    }
};

onMounted(() => {
    fetchStatus();
    pollingInterval = setInterval(fetchStatus, 5000);
});

onBeforeUnmount(() => {
    if (pollingInterval) clearInterval(pollingInterval);
});

const goBack = () => {
    window.history.back();
};
</script>

<template>
    <Head :title="$t('tracking.title')" />

    <div class="min-h-screen bg-[#F5F5F5] py-10 px-4">
        <div v-if="orderState" class="max-w-2xl mx-auto bg-white rounded-2xl border border-[#1A1A1A]/10 shadow-sm p-6">
            <h1 class="text-2xl font-black text-[#1A1A1A] mb-1">{{ $t('tracking.title') }}</h1>
            <p class="text-sm text-[#1A1A1A]/70 mb-8">
                {{ $t('tracking.pedido_habitacion', { id: orderState?.id, room: orderState?.habitacion?.numero ?? orderState?.room_number }) }}
            </p>

            <div class="relative mb-10 px-0.5 pt-0.5">
                <div
                    class="pointer-events-none absolute left-4 right-4 top-4 z-0 h-1 -translate-y-1/2 rounded-full bg-gray-200"
                    aria-hidden="true"
                />
                <div
                    class="pointer-events-none absolute left-4 top-4 z-0 h-1 -translate-y-1/2 rounded-full bg-[#A64B35] transition-all duration-500 ease-out"
                    :style="{ width: connectorFillWidth }"
                    aria-hidden="true"
                />

                <div class="relative z-10 flex justify-between">
                    <div
                        v-for="(step, index) in steps"
                        :key="step.key"
                        class="flex min-w-0 flex-1 flex-col items-center"
                    >
                        <div
                            class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 bg-white text-xs font-bold shadow-sm"
                            :class="index <= currentStepIndex ? 'border-[#A64B35] bg-[#A64B35] text-white' : 'border-gray-300 text-gray-300'"
                        >
                            {{ index + 1 }}
                        </div>
                        <span
                            class="mt-2 max-w-[4.5rem] text-center text-xs font-medium uppercase tracking-wide"
                            :class="index <= currentStepIndex ? 'text-[#A64B35]' : 'text-gray-400'"
                        >
                            {{ step.label }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-[#F5F5F5] rounded-xl p-4 mb-6">
                <p class="text-sm text-[#1A1A1A]/80">{{ $t('tracking.estado_actual') }}</p>
                <p class="text-xl font-black text-[#1A1A1A] mt-1">{{ currentStatusLabel }}</p>
            </div>

            <button @click="goBack" class="w-full mt-6 bg-[#A64B35] text-white rounded-lg py-3 font-bold flex items-center justify-center gap-2 shadow-lg hover:bg-orange-800 transition">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                    <path fill-rule="evenodd" d="M11.03 3.97a.75.75 0 010 1.06l-6.22 6.22H21a.75.75 0 010 1.5H4.81l6.22 6.22a.75.75 0 11-1.06 1.06l-7.5-7.5a.75.75 0 010-1.06l7.5-7.5a.75.75 0 011.06 0z" clip-rule="evenodd" />
                </svg>
                {{ $t('tracking.volver_menu') }}
            </button>
        </div>
        <div v-else class="max-w-2xl mx-auto bg-white rounded-2xl border border-[#1A1A1A]/10 shadow-sm p-6 text-center">
            <p class="text-[#1A1A1A]/70 font-bold">{{ $t('tracking.cargando') }}</p>
        </div>
    </div>
</template>
