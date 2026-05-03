<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { SignalIcon } from '@heroicons/vue/24/solid';

const props = defineProps({ orders: Array });

const reactiveOrders = ref([...(props.orders ?? [])]);
const knownOrderIds = ref(new Set((props.orders ?? []).map((order) => order.id)));
const now = ref(Date.now());
let pollingInterval = null;
let tickerInterval = null;

const kanbanColumns = [
    { key: 'recibido', title: 'RECIBIDO' },
    { key: 'en_proceso', title: 'PREPARANDO' },
    { key: 'en_camino', title: 'EN CAMINO' },
    { key: 'completado', title: 'COMPLETADO' },
];

const playNotificationSound = () => {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
    if (!AudioContextClass) return;
    const audioCtx = new AudioContextClass();
    const oscillator = audioCtx.createOscillator();
    const gainNode = audioCtx.createGain();
    oscillator.type = 'sine';
    oscillator.frequency.setValueAtTime(880, audioCtx.currentTime);
    gainNode.gain.setValueAtTime(0.12, audioCtx.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.2);
    oscillator.connect(gainNode);
    gainNode.connect(audioCtx.destination);
    oscillator.start();
    oscillator.stop(audioCtx.currentTime + 0.2);
};

const fetchLatestOrders = async () => {
    const response = await fetch(route('orders.poll'), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (!response.ok) return;

    const data = await response.json();
    const incomingOrders = data.orders ?? [];
    const incomingIds = new Set(incomingOrders.map((order) => order.id));
    const hasNewOrder = incomingOrders.some((order) => !knownOrderIds.value.has(order.id));

    reactiveOrders.value = incomingOrders;
    knownOrderIds.value = incomingIds;
    now.value = Date.now();
    if (hasNewOrder) playNotificationSound();
};

const startPolling = () => {
    fetchLatestOrders();
    pollingInterval = setInterval(fetchLatestOrders, 5000);
    tickerInterval = setInterval(() => {
        now.value = Date.now();
    }, 30000);
};

onMounted(() => startPolling());
onBeforeUnmount(() => {
    if (pollingInterval) clearInterval(pollingInterval);
    if (tickerInterval) clearInterval(tickerInterval);
});

const ordersByStatus = computed(() => {
    const grouped = { recibido: [], en_proceso: [], en_camino: [], completado: [] };
    reactiveOrders.value.forEach((order) => {
        if (!grouped[order.status]) grouped.recibido.push(order);
        else grouped[order.status].push(order);
    });
    return grouped;
});

const getNextStatus = (status) => ({
    recibido: 'en_proceso',
    en_proceso: 'en_camino',
    en_camino: 'completado',
}[status] ?? null);

const nextStatusLabel = (status) => ({
    recibido: 'Preparando',
    en_proceso: 'En camino',
    en_camino: 'Completar',
}[status] ?? '');

const updateStatus = (id, newStatus) => {
    router.put(route('orders.update', id), { status: newStatus }, {
        preserveScroll: true,
        onSuccess: () => {
            const orderIndex = reactiveOrders.value.findIndex((order) => order.id === id);
            if (orderIndex !== -1) reactiveOrders.value[orderIndex].status = newStatus;
        },
    });
};

const formatTime = (dateString) => new Date(dateString).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
const summarizeOrder = (order) => {
    if (order.service_type !== 'comida') {
        if (order.service_type === 'limpieza') return `Limpieza a las ${order.requested_time || '--:--'}`;
        return order.description || 'Solicitud de mantenimiento';
    }
    const items = (order.services ?? []).slice(0, 3).map((service) => `${service.pivot.quantity}x ${service.name}`);
    if ((order.services ?? []).length > 3) items.push(`+${order.services.length - 3} más`);
    return items.join(', ');
};

const timeAgo = (dateString) => {
    const diffMs = Math.max(0, now.value - new Date(dateString).getTime());
    const diffMinutes = Math.floor(diffMs / 60000);
    if (diffMinutes < 1) return 'Hace < 1 min';
    if (diffMinutes < 60) return `Hace ${diffMinutes} min`;
    const diffHours = Math.floor(diffMinutes / 60);
    if (diffHours < 24) return `Hace ${diffHours} h`;
    return `Hace ${Math.floor(diffHours / 24)} d`;
};
</script>

<template>
    <Head title="Pedidos Actuales" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                Centro de Operaciones
                <SignalIcon class="w-5 h-5 text-[#A64B35] animate-pulse" />
            </h2>
        </template>

        <div class="py-8 bg-gray-50 min-h-screen">
            <div class="max-w-[110rem] mx-auto sm:px-6 lg:px-8">
                <div class="rounded-3xl bg-gray-100 border border-gray-200 shadow-sm p-4 md:p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                        <section
                            v-for="column in kanbanColumns"
                            :key="column.key"
                            class="bg-white rounded-2xl border border-gray-100 shadow-sm p-3"
                        >
                            <header class="flex items-center justify-between mb-3">
                                <h3 class="text-xs font-bold tracking-wide text-gray-500">{{ column.title }}</h3>
                                <span class="inline-flex rounded-full bg-[#A64B35]/10 text-[#A64B35] px-2.5 py-1 text-xs font-semibold">
                                    {{ ordersByStatus[column.key].length }}
                                </span>
                            </header>

                            <div class="space-y-3 max-h-[70vh] overflow-y-auto pr-1">
                                <article
                                    v-for="order in ordersByStatus[column.key]"
                                    :key="order.id"
                                    class="rounded-2xl border border-gray-100 shadow-sm bg-white p-3"
                                >
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <p class="text-[11px] font-medium text-gray-500">Habitación</p>
                                            <p class="text-3xl font-black text-gray-800 leading-none">{{ order.habitacion?.numero ?? order.room_number }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs text-gray-500">{{ formatTime(order.created_at) }}</p>
                                            <p class="text-[11px] font-semibold text-[#A64B35]">{{ timeAgo(order.created_at) }}</p>
                                        </div>
                                    </div>

                                    <p class="text-xs text-gray-500 mt-2 line-clamp-2">{{ summarizeOrder(order) }}</p>

                                    <div class="mt-3 flex items-center justify-end gap-2">
                                        <button
                                            v-if="getNextStatus(order.status)"
                                            @click="updateStatus(order.id, getNextStatus(order.status))"
                                            class="inline-flex items-center rounded-full bg-[#A64B35] px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90 transition"
                                        >
                                            {{ nextStatusLabel(order.status) }}
                                        </button>
                                    </div>
                                </article>

                                <p v-if="ordersByStatus[column.key].length === 0" class="text-xs text-gray-500 py-8 text-center">
                                    Sin pedidos en esta fase.
                                </p>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>