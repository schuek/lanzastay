<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ClockIcon,
    CheckCircleIcon,
    WrenchScrewdriverIcon,
    SignalIcon
} from '@heroicons/vue/24/solid';

const props = defineProps({
    orders: Array
});

const reactiveOrders = ref([...props.orders]);
const activeTypeFilter = ref('todos');
let pollingInterval = null;
const knownOrderIds = ref(new Set(props.orders.map((order) => order.id)));

onMounted(() => {
    startPolling();
});

onBeforeUnmount(() => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});

const playNotificationSound = () => {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
    if (!AudioContextClass) return;

    const audioCtx = new AudioContextClass();
    const oscillator = audioCtx.createOscillator();
    const gainNode = audioCtx.createGain();

    oscillator.type = 'sine';
    oscillator.frequency.setValueAtTime(880, audioCtx.currentTime);
    gainNode.gain.setValueAtTime(0.15, audioCtx.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.2);

    oscillator.connect(gainNode);
    gainNode.connect(audioCtx.destination);
    oscillator.start();
    oscillator.stop(audioCtx.currentTime + 0.2);
};

const fetchLatestOrders = async () => {
    const response = await fetch(route('orders.poll'), {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    });

    if (!response.ok) return;

    const data = await response.json();
    const incomingOrders = data.orders ?? [];

    const incomingIds = new Set(incomingOrders.map((order) => order.id));
    const hasNewOrder = incomingOrders.some((order) => !knownOrderIds.value.has(order.id));

    reactiveOrders.value = incomingOrders;
    knownOrderIds.value = incomingIds;

    if (hasNewOrder) {
        playNotificationSound();
    }
};

const startPolling = () => {
    fetchLatestOrders();
    pollingInterval = setInterval(fetchLatestOrders, 5000);
};

const formatPrice = (value) => new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value);
const formatDate = (dateString) => new Date(dateString).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
const formatType = (type) => ({
    comida: 'Comida',
    limpieza: 'Limpieza',
    mantenimiento: 'Mantenimiento',
}[type] ?? type);
const progressLabels = {
    comida: { inProgress: 'Preparando', completed: 'Entregado' },
    limpieza: { inProgress: 'Limpiando', completed: 'Terminado' },
    mantenimiento: { inProgress: 'Reparando', completed: 'Solucionado' },
};
const getProgressLabel = (order, statusKey) => {
    const labels = progressLabels[order.service_type] ?? progressLabels.comida;
    return statusKey === 'en_proceso' ? labels.inProgress : labels.completed;
};

const filteredOrders = computed(() => {
    if (activeTypeFilter.value === 'todos') return reactiveOrders.value;
    return reactiveOrders.value.filter((order) => order.service_type === activeTypeFilter.value);
});

const updateStatus = (id, newStatus) => {
    router.put(route('orders.update', id), {
        status: newStatus
    }, {
        onSuccess: () => {
            const orderIndex = reactiveOrders.value.findIndex(o => o.id === id);
            if(orderIndex !== -1) {
                reactiveOrders.value[orderIndex].status = newStatus;
                if(newStatus === 'completado') {
                    reactiveOrders.value.splice(orderIndex, 1);
                }
            }
        }
    });
};
</script>

<template>
    <Head title="Pedidos en Cocina" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-[#1A1A1A] leading-tight flex items-center gap-2">
                Panel de Cocina en Directo
                <SignalIcon class="w-6 h-6 text-[#D45D3B] animate-pulse" />
            </h2>
        </template>

        <div class="py-12 bg-[#F5F5F5] min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <div class="flex gap-2 mb-6">
                    <button @click="activeTypeFilter = 'todos'" :class="activeTypeFilter === 'todos' ? 'bg-[#D45D3B] text-white' : 'bg-white text-[#1A1A1A]'" class="px-4 py-2 rounded-lg border border-[#1A1A1A]/10 font-bold text-sm">Todos</button>
                    <button @click="activeTypeFilter = 'comida'" :class="activeTypeFilter === 'comida' ? 'bg-[#D45D3B] text-white' : 'bg-white text-[#1A1A1A]'" class="px-4 py-2 rounded-lg border border-[#1A1A1A]/10 font-bold text-sm">Comida</button>
                    <button @click="activeTypeFilter = 'limpieza'" :class="activeTypeFilter === 'limpieza' ? 'bg-[#D45D3B] text-white' : 'bg-white text-[#1A1A1A]'" class="px-4 py-2 rounded-lg border border-[#1A1A1A]/10 font-bold text-sm">Limpieza</button>
                    <button @click="activeTypeFilter = 'mantenimiento'" :class="activeTypeFilter === 'mantenimiento' ? 'bg-[#D45D3B] text-white' : 'bg-white text-[#1A1A1A]'" class="px-4 py-2 rounded-lg border border-[#1A1A1A]/10 font-bold text-sm">Mantenimiento</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                    <div v-for="order in filteredOrders" :key="order.id" class="bg-white rounded-xl shadow-lg overflow-hidden border border-[#1A1A1A]/10 flex flex-col animate-in fade-in slide-in-from-bottom-4 duration-500">

                        <div class="p-4 bg-[#1A1A1A] text-white flex justify-between items-center">
                            <div>
                                <span class="text-xs uppercase tracking-widest text-gray-400">Habitación</span>
                                <h3 class="text-2xl font-black">{{ order.habitacion?.numero ?? order.room_number }}</h3>
                                <p class="text-xs uppercase tracking-widest text-[#D45D3B] mt-1">{{ formatType(order.service_type) }}</p>
                            </div>
                            <div class="text-right flex flex-col items-end">
                                <span class="text-xs uppercase tracking-widest text-gray-400">Hora</span>
                                <p class="font-bold flex items-center gap-1">
                                    <ClockIcon class="w-4 h-4 text-gray-400" />
                                    {{ formatDate(order.created_at) }}
                                </p>
                            </div>
                        </div>

                        <div class="p-4 flex-grow">
                            <ul v-if="order.service_type === 'comida'" class="divide-y divide-gray-100">
                                <li v-for="service in order.services" :key="service.id" class="py-2 flex justify-between items-center">
                                    <span class="text-gray-800 font-bold">
                                        {{ service.pivot.quantity }}x {{ service.name }}
                                    </span>
                                    <span class="text-[#1A1A1A]/60 text-sm">{{ formatPrice(service.price) }}</span>
                                </li>
                            </ul>
                            <p v-if="order.service_type === 'limpieza'" class="text-[#1A1A1A]/70 text-sm">
                                Hora solicitada: <strong>{{ order.requested_time ?? '-' }}</strong>
                            </p>
                            <p v-if="order.service_type === 'mantenimiento'" class="text-[#1A1A1A]/70 text-sm">
                                {{ order.description }}
                            </p>
                        </div>

                        <div class="p-4 bg-gray-50 border-t">
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-gray-500 font-medium">Total:</span>
                                <span class="text-xl font-black text-[#D45D3B]">{{ order.service_type === 'comida' ? formatPrice(order.total_price) : '--' }}</span>
                            </div>

                            <div class="flex flex-col gap-2">
                                <button
                                    v-if="order.status === 'recibido'"
                                    @click="updateStatus(order.id, 'en_proceso')"
                                    class="w-full bg-[#1A1A1A] hover:bg-[#D45D3B] text-white py-3 rounded-lg font-bold flex items-center justify-center gap-2 transition"
                                >
                                    <WrenchScrewdriverIcon class="w-5 h-5" />
                                    <span>Marcar como {{ getProgressLabel(order, 'en_proceso') }}</span>
                                </button>

                                <button
                                    v-if="order.status !== 'completado'"
                                    @click="updateStatus(order.id, 'completado')"
                                    class="w-full bg-[#D45D3B] hover:opacity-90 text-white py-3 rounded-lg font-bold flex items-center justify-center gap-2 transition"
                                >
                                    <CheckCircleIcon class="w-5 h-5" />
                                    <span>Marcar como {{ getProgressLabel(order, 'completado') }}</span>
                                </button>

                                <div class="text-center mt-2">
                                    <span class="text-[10px] uppercase font-black tracking-tighter text-gray-400">
                                        Estado actual: {{ order.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div v-if="filteredOrders.length === 0" class="text-center py-20 bg-white rounded-xl shadow-sm border-2 border-dashed border-gray-200">
                    <CheckCircleIcon class="w-16 h-16 mx-auto text-green-200 mb-4" />
                    <h3 class="text-lg font-bold text-gray-900">¡Todo despejado!</h3>
                    <p class="text-gray-500">No hay pedidos pendientes en este momento.</p>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
