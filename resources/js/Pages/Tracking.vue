<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    order: Object,
});

const orderState = ref(props?.order ?? null);
let pollingInterval = null;

const stepLabelsByType = {
    comida: ['Recibido', 'Preparando', 'En camino', 'Entregado'],
    limpieza: ['Recibido', 'Limpiando', 'En camino', 'Terminado'],
    mantenimiento: ['Recibido', 'Reparando', 'En camino', 'Solucionado'],
};

const steps = computed(() => {
    const type = orderState.value?.service_type ?? 'comida';
    const labels = stepLabelsByType[type] ?? stepLabelsByType.comida;

    return [
        { key: 'recibido', label: labels[0] },
        { key: 'en_proceso', label: labels[1] },
        { key: 'en_camino', label: labels[2] },
        { key: 'completado', label: labels[3] },
    ];
});

const currentStepIndex = computed(() => {
    const status = orderState.value?.status;
    return Math.max(steps.value.findIndex((step) => step.key === status), 0);
});
const currentStatusLabel = computed(() => steps.value[currentStepIndex.value]?.label ?? 'Recibido');

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
    <Head title="Seguimiento de solicitud" />

    <div class="min-h-screen bg-[#F5F5F5] py-10 px-4">
        <div v-if="orderState" class="max-w-2xl mx-auto bg-white rounded-2xl border border-[#1A1A1A]/10 shadow-sm p-6">
            <h1 class="text-2xl font-black text-[#1A1A1A] mb-1">Seguimiento de solicitud</h1>
            <p class="text-sm text-[#1A1A1A]/70 mb-8">Pedido #{{ orderState?.id }} - Habitación {{ orderState?.habitacion?.numero ?? orderState?.room_number }}</p>

            <div class="relative mb-10">
                <div class="absolute top-4 left-0 right-0 h-1 bg-[#1A1A1A]/10"></div>
                <div class="absolute top-4 left-0 h-1 bg-[#D45D3B] transition-all duration-500" :style="{ width: `${(currentStepIndex / ((steps?.length ?? 1) - 1 || 1)) * 100}%` }"></div>

                <div class="relative flex justify-between">
                    <div v-for="(step, index) in steps" :key="step.key" class="flex flex-col items-center w-1/3">
                        <div
                            class="w-8 h-8 rounded-full border-2 flex items-center justify-center font-bold text-xs"
                            :class="index <= currentStepIndex ? 'bg-[#D45D3B] border-[#D45D3B] text-white' : 'bg-white border-[#1A1A1A]/30 text-[#1A1A1A]/60'"
                        >
                            {{ index + 1 }}
                        </div>
                        <span class="mt-2 text-xs font-bold uppercase tracking-wide" :class="index <= currentStepIndex ? 'text-[#D45D3B]' : 'text-[#1A1A1A]/60'">
                            {{ step.label }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-[#F5F5F5] rounded-xl p-4 mb-6">
                <p class="text-sm text-[#1A1A1A]/80">Estado actual:</p>
                <p class="text-xl font-black text-[#1A1A1A] mt-1">{{ currentStatusLabel }}</p>
            </div>

            <button @click="goBack" class="w-full mt-6 bg-[#A64B35] text-white rounded-lg py-3 font-bold flex items-center justify-center gap-2 shadow-lg hover:bg-orange-800 transition">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                    <path fill-rule="evenodd" d="M11.03 3.97a.75.75 0 010 1.06l-6.22 6.22H21a.75.75 0 010 1.5H4.81l6.22 6.22a.75.75 0 11-1.06 1.06l-7.5-7.5a.75.75 0 010-1.06l7.5-7.5a.75.75 0 011.06 0z" clip-rule="evenodd" />
                </svg>
                Volver al Menú
            </button>
        </div>
        <div v-else class="max-w-2xl mx-auto bg-white rounded-2xl border border-[#1A1A1A]/10 shadow-sm p-6 text-center">
            <p class="text-[#1A1A1A]/70 font-bold">Cargando seguimiento...</p>
        </div>
    </div>
</template>
