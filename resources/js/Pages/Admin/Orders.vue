<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    CheckCircleIcon,
    ClockIcon,
    FireIcon,
    MagnifyingGlassIcon,
    SignalIcon,
    SparklesIcon,
    WrenchScrewdriverIcon,
    XMarkIcon,
} from '@heroicons/vue/24/solid';

const props = defineProps({
    orders: Array,
});

const reactiveOrders = ref([...(props.orders ?? [])]);
const activeTypeFilter = ref('todos');
const roomSearch = ref('');
const dateFrom = ref('');
const dateTo = ref('');
const groupByMode = ref('none');
const selectedOrder = ref(null);
const knownOrderIds = ref(new Set((props.orders ?? []).map((order) => order.id)));
let pollingInterval = null;

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
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) return;

    const data = await response.json();
    const incomingOrders = data.orders ?? [];
    const incomingIds = new Set(incomingOrders.map((order) => order.id));
    const hasNewOrder = incomingOrders.some((order) => !knownOrderIds.value.has(order.id));

    reactiveOrders.value = incomingOrders;
    knownOrderIds.value = incomingIds;

    if (selectedOrder.value) {
        selectedOrder.value = incomingOrders.find((order) => order.id === selectedOrder.value.id) ?? selectedOrder.value;
    }

    if (hasNewOrder) {
        playNotificationSound();
    }
};

const startPolling = () => {
    fetchLatestOrders();
    pollingInterval = setInterval(fetchLatestOrders, 5000);
};

onMounted(() => {
    startPolling();
});

onBeforeUnmount(() => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});

const formatPrice = (value) => new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(Number(value ?? 0));
const formatTime = (dateString) => new Date(dateString).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
const formatDateTime = (dateString) => new Date(dateString).toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'short' });
const formatType = (type) => ({
    comida: 'Comida',
    limpieza: 'Limpieza',
    mantenimiento: 'Mantenimiento',
}[type] ?? type);
const formatStatus = (status) => ({
    recibido: 'Pendiente',
    en_proceso: 'En proceso',
    completado: 'Completado',
}[status] ?? status);
const getTypeColor = (type) => ({
    comida: 'bg-orange-500',
    limpieza: 'bg-blue-500',
    mantenimiento: 'bg-red-600',
}[type] ?? 'bg-gray-500');
const getStatusBadgeClass = (status) => ({
    recibido: 'bg-[#A64B35]/10 text-[#A64B35]',
    en_proceso: 'bg-amber-100 text-amber-700',
    completado: 'bg-gray-100 text-gray-600',
}[status] ?? 'bg-gray-100 text-gray-600');
const getProgressLabel = (order, statusKey) => {
    const progressLabels = {
        comida: { inProgress: 'Preparar', completed: 'Entregado' },
        limpieza: { inProgress: 'Limpiar', completed: 'Terminado' },
        mantenimiento: { inProgress: 'Reparado', completed: 'Solucionado' },
    };
    const labels = progressLabels[order.service_type] ?? progressLabels.comida;
    return statusKey === 'en_proceso' ? labels.inProgress : labels.completed;
};
const tokenLabel = (token) => token ? `${token.slice(0, 8)}...` : 'Sin token';
const matchesDateRange = (order) => {
    if (!dateFrom.value && !dateTo.value) return true;

    const orderDate = new Date(order.created_at);
    const start = dateFrom.value ? new Date(`${dateFrom.value}T00:00:00`) : null;
    const end = dateTo.value ? new Date(`${dateTo.value}T23:59:59`) : null;

    if (start && orderDate < start) return false;
    if (end && orderDate > end) return false;
    return true;
};

const filteredOrders = computed(() => {
    return reactiveOrders.value.filter((order) => {
        const roomNumber = `${order.habitacion?.numero ?? order.room_number ?? ''}`.toLowerCase();
        const roomMatches = !roomSearch.value || roomNumber.includes(roomSearch.value.toLowerCase().trim());
        const typeMatches = activeTypeFilter.value === 'todos' || order.service_type === activeTypeFilter.value;

        return roomMatches && typeMatches && matchesDateRange(order);
    });
});

const ordersRecibido = computed(() => filteredOrders.value.filter((order) => order.status === 'recibido'));
const ordersEnProceso = computed(() => filteredOrders.value.filter((order) => order.status === 'en_proceso'));
const historicalOrders = computed(() => filteredOrders.value);
const groupedHistoricalOrders = computed(() => {
    if (groupByMode.value !== 'token') return [];

    const groups = new Map();

    historicalOrders.value.forEach((order) => {
        const token = order.session_token || 'sin-token';
        if (!groups.has(token)) {
            groups.set(token, {
                token,
                roomNumber: order.habitacion?.numero ?? order.room_number,
                orders: [],
            });
        }
        groups.get(token).orders.push(order);
    });

    return Array.from(groups.values());
});

const openOrderDetail = (order) => {
    selectedOrder.value = order;
};

const closeOrderDetail = () => {
    selectedOrder.value = null;
};

const updateStatus = (id, newStatus) => {
    router.put(route('orders.update', id), { status: newStatus }, {
        preserveScroll: true,
        onSuccess: () => {
            const orderIndex = reactiveOrders.value.findIndex((order) => order.id === id);
            if (orderIndex !== -1) {
                reactiveOrders.value[orderIndex].status = newStatus;
            }

            if (selectedOrder.value?.id === id) {
                selectedOrder.value = {
                    ...selectedOrder.value,
                    status: newStatus,
                };
            }
        },
    });
};
</script>

<template>
    <Head title="Panel de Control" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-[#2F2A26] leading-tight flex items-center gap-2">
                Centro de Operaciones LanzaStay
                <SignalIcon class="w-6 h-6 text-[#A64B35] animate-pulse" />
            </h2>
        </template>

        <div class="py-8 bg-gray-100 min-h-screen">
            <div class="max-w-[95rem] mx-auto sm:px-6 lg:px-8 space-y-8">
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                    <div class="flex flex-col xl:flex-row gap-4 xl:items-end xl:justify-between">
                        <div class="flex gap-3 overflow-x-auto pb-2 hide-scroll-bar">
                            <button @click="activeTypeFilter = 'todos'" :class="activeTypeFilter === 'todos' ? 'bg-[#2F2A26] text-white shadow-md' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'" class="px-5 py-2.5 rounded-xl font-bold text-sm transition-all">Todos</button>
                            <button @click="activeTypeFilter = 'comida'" :class="activeTypeFilter === 'comida' ? 'bg-orange-500 text-white shadow-md' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'" class="px-5 py-2.5 rounded-xl font-bold text-sm transition-all">Restaurante</button>
                            <button @click="activeTypeFilter = 'limpieza'" :class="activeTypeFilter === 'limpieza' ? 'bg-blue-500 text-white shadow-md' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'" class="px-5 py-2.5 rounded-xl font-bold text-sm transition-all">Limpieza</button>
                            <button @click="activeTypeFilter = 'mantenimiento'" :class="activeTypeFilter === 'mantenimiento' ? 'bg-red-600 text-white shadow-md' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'" class="px-5 py-2.5 rounded-xl font-bold text-sm transition-all">Mantenimiento</button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 w-full xl:max-w-5xl">
                            <label class="relative block">
                                <span class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1">Habitación</span>
                                <MagnifyingGlassIcon class="w-4 h-4 text-gray-400 absolute left-3 top-[38px]" />
                                <input v-model="roomSearch" type="text" placeholder="Ej: 101" class="w-full rounded-xl border-gray-300 pl-9 text-sm focus:border-[#A64B35] focus:ring-[#A64B35]" />
                            </label>
                            <label class="block">
                                <span class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1">Desde</span>
                                <input v-model="dateFrom" type="date" class="w-full rounded-xl border-gray-300 text-sm focus:border-[#A64B35] focus:ring-[#A64B35]" />
                            </label>
                            <label class="block">
                                <span class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1">Hasta</span>
                                <input v-model="dateTo" type="date" class="w-full rounded-xl border-gray-300 text-sm focus:border-[#A64B35] focus:ring-[#A64B35]" />
                            </label>
                            <label class="block">
                                <span class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1">Agrupar por</span>
                                <select v-model="groupByMode" class="w-full rounded-xl border-gray-300 text-sm focus:border-[#A64B35] focus:ring-[#A64B35]">
                                    <option value="none">Sin agrupación</option>
                                    <option value="token">Sesión / token</option>
                                </select>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row gap-8 items-start">
                    <div class="w-full lg:w-1/2 bg-gray-200/50 rounded-2xl p-4 border border-gray-300 shadow-inner min-h-[420px]">
                        <div class="flex items-center justify-between mb-4 px-2">
                            <h3 class="text-lg font-black text-gray-800 flex items-center gap-2">
                                <FireIcon class="w-5 h-5 text-red-500" /> Nuevos Pedidos
                            </h3>
                            <span class="bg-white text-gray-800 px-3 py-1 rounded-full text-sm font-bold shadow-sm">{{ ordersRecibido.length }}</span>
                        </div>

                        <div class="flex flex-col gap-4">
                            <div v-for="order in ordersRecibido" :key="order.id" class="bg-white rounded-xl shadow-md overflow-hidden flex flex-col transition-all">
                                <div class="h-2 w-full" :class="getTypeColor(order.service_type)"></div>

                                <div class="p-5">
                                    <div class="flex justify-between items-start mb-4 gap-3">
                                        <div>
                                            <span class="text-xs uppercase tracking-widest text-gray-500 font-bold">Habitación</span>
                                            <h4 class="text-3xl font-black text-[#2F2A26]">{{ order.habitacion?.numero ?? order.room_number }}</h4>
                                            <p class="text-xs text-gray-500 mt-2">Sesión: {{ tokenLabel(order.session_token) }}</p>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ formatType(order.service_type) }}</span>
                                            <p class="font-bold flex items-center justify-end gap-1 text-gray-600 mt-2 text-sm">
                                                <ClockIcon class="w-4 h-4" /> {{ formatDateTime(order.created_at) }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mb-5 bg-gray-50 p-3 rounded-lg border border-gray-100 text-sm">
                                        <ul v-if="order.service_type === 'comida'" class="space-y-1">
                                            <li v-for="service in order.services" :key="service.id" class="flex justify-between font-medium text-gray-800">
                                                <span>{{ service.pivot.quantity }}x {{ service.name }}</span>
                                                <span>{{ formatPrice(service.pivot.price) }}</span>
                                            </li>
                                        </ul>
                                        <p v-if="order.service_type === 'limpieza'" class="text-gray-800 font-medium">Hora solicitada: <span class="text-blue-600 font-bold">{{ order.requested_time ?? '-' }}</span></p>
                                        <p v-if="order.service_type === 'mantenimiento'" class="text-gray-800 italic">"{{ order.description }}"</p>
                                    </div>

                                    <div class="flex gap-3">
                                        <button @click="openOrderDetail(order)" class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-xl font-bold">
                                            Ver detalle
                                        </button>
                                        <button @click="updateStatus(order.id, 'en_proceso')" class="flex-1 bg-[#2F2A26] hover:bg-gray-800 text-white py-3 rounded-xl font-bold flex items-center justify-center gap-2 transition-all shadow-md">
                                            <WrenchScrewdriverIcon class="w-5 h-5" /> {{ getProgressLabel(order, 'en_proceso') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-if="ordersRecibido.length === 0" class="text-center py-10 text-gray-400 font-medium">No hay pedidos nuevos con los filtros actuales.</div>
                        </div>
                    </div>

                    <div class="w-full lg:w-1/2 bg-gray-200/50 rounded-2xl p-4 border border-gray-300 shadow-inner min-h-[420px]">
                        <div class="flex items-center justify-between mb-4 px-2">
                            <h3 class="text-lg font-black text-gray-800 flex items-center gap-2">
                                <SparklesIcon class="w-5 h-5 text-amber-500" /> En Curso
                            </h3>
                            <span class="bg-white text-gray-800 px-3 py-1 rounded-full text-sm font-bold shadow-sm">{{ ordersEnProceso.length }}</span>
                        </div>

                        <div class="flex flex-col gap-4">
                            <div v-for="order in ordersEnProceso" :key="order.id" class="bg-white rounded-xl shadow-md overflow-hidden border-l-4" :class="getTypeColor(order.service_type).replace('bg-', 'border-')">
                                <div class="p-5">
                                    <div class="flex justify-between items-start mb-3 gap-3">
                                        <div>
                                            <h4 class="text-xl font-black text-gray-800">Habitación {{ order.habitacion?.numero ?? order.room_number }}</h4>
                                            <p class="text-xs text-gray-500 mt-1">Sesión: {{ tokenLabel(order.session_token) }}</p>
                                        </div>
                                        <span class="text-xs uppercase font-bold text-amber-600 bg-amber-100 px-2 py-1 rounded animate-pulse">En proceso</span>
                                    </div>
                                    <p class="text-sm text-gray-500 mb-4">{{ formatType(order.service_type) }} - Pedido a las {{ formatDateTime(order.created_at) }}</p>

                                    <div class="flex gap-3">
                                        <button @click="openOrderDetail(order)" class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-xl font-bold">
                                            Ver detalle
                                        </button>
                                        <button @click="updateStatus(order.id, 'completado')" class="flex-1 bg-green-500 hover:bg-green-600 text-white py-3 rounded-xl font-bold flex items-center justify-center gap-2 transition-all shadow-md">
                                            <CheckCircleIcon class="w-5 h-5" /> {{ getProgressLabel(order, 'completado') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-if="ordersEnProceso.length === 0" class="text-center py-10 text-gray-400 font-medium">No hay tareas en curso con los filtros actuales.</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                        <div>
                            <h3 class="text-lg font-black text-[#2F2A26]">Historial y detalle de pedidos</h3>
                            <p class="text-sm text-gray-500">Consulta incidencias por habitación, fechas y sesión.</p>
                        </div>
                        <p class="text-sm font-semibold text-gray-600">{{ historicalOrders.length }} resultados</p>
                    </div>

                    <div v-if="groupByMode === 'token'" class="p-5 space-y-5">
                        <div v-for="group in groupedHistoricalOrders" :key="group.token" class="border border-gray-200 rounded-2xl overflow-hidden">
                            <div class="px-4 py-3 bg-gray-50 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                <div>
                                    <p class="text-sm font-black text-[#2F2A26]">Habitación {{ group.roomNumber }}</p>
                                    <p class="text-xs text-gray-500">Sesión {{ tokenLabel(group.token === 'sin-token' ? '' : group.token) }}</p>
                                </div>
                                <p class="text-xs font-semibold text-gray-500">{{ group.orders.length }} pedidos</p>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-white text-gray-500 uppercase text-xs tracking-wide">
                                        <tr>
                                            <th class="text-left px-4 py-3">Pedido</th>
                                            <th class="text-left px-4 py-3">Tipo</th>
                                            <th class="text-left px-4 py-3">Estado</th>
                                            <th class="text-left px-4 py-3">Fecha y hora</th>
                                            <th class="text-left px-4 py-3">Total</th>
                                            <th class="text-right px-4 py-3">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="order in group.orders" :key="order.id" class="border-t border-gray-100 hover:bg-gray-50">
                                            <td class="px-4 py-3 font-semibold text-[#2F2A26]">#{{ order.id }}</td>
                                            <td class="px-4 py-3">{{ formatType(order.service_type) }}</td>
                                            <td class="px-4 py-3">
                                                <span :class="getStatusBadgeClass(order.status)" class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold">
                                                    {{ formatStatus(order.status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-gray-600">{{ formatDateTime(order.created_at) }}</td>
                                            <td class="px-4 py-3 font-semibold">{{ formatPrice(order.total_price) }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <button @click="openOrderDetail(order)" class="text-[#A64B35] font-semibold hover:underline">Ver detalle</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div v-if="groupedHistoricalOrders.length === 0" class="text-center py-12 text-gray-400 font-medium">No hay pedidos para ese rango o agrupación.</div>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide">
                                <tr>
                                    <th class="text-left px-5 py-3">Pedido</th>
                                    <th class="text-left px-5 py-3">Habitación</th>
                                    <th class="text-left px-5 py-3">Sesión</th>
                                    <th class="text-left px-5 py-3">Tipo</th>
                                    <th class="text-left px-5 py-3">Estado</th>
                                    <th class="text-left px-5 py-3">Fecha y hora</th>
                                    <th class="text-left px-5 py-3">Total</th>
                                    <th class="text-right px-5 py-3">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="order in historicalOrders" :key="order.id" class="border-t border-gray-100 hover:bg-gray-50 cursor-pointer" @click="openOrderDetail(order)">
                                    <td class="px-5 py-4 font-semibold text-[#2F2A26]">#{{ order.id }}</td>
                                    <td class="px-5 py-4">{{ order.habitacion?.numero ?? order.room_number }}</td>
                                    <td class="px-5 py-4 text-gray-600">{{ tokenLabel(order.session_token) }}</td>
                                    <td class="px-5 py-4">{{ formatType(order.service_type) }}</td>
                                    <td class="px-5 py-4">
                                        <span :class="getStatusBadgeClass(order.status)" class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold">
                                            {{ formatStatus(order.status) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">{{ formatDateTime(order.created_at) }}</td>
                                    <td class="px-5 py-4 font-semibold">{{ formatPrice(order.total_price) }}</td>
                                    <td class="px-5 py-4 text-right">
                                        <button @click.stop="openOrderDetail(order)" class="text-[#A64B35] font-semibold hover:underline">Ver detalle</button>
                                    </td>
                                </tr>
                                <tr v-if="historicalOrders.length === 0">
                                    <td colspan="8" class="px-5 py-12 text-center text-gray-400 font-medium">No hay pedidos que coincidan con los filtros.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="selectedOrder" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="closeOrderDetail"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl border border-gray-200 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <div>
                        <h3 class="text-xl font-black text-[#2F2A26]">Detalle del pedido #{{ selectedOrder.id }}</h3>
                        <p class="text-sm text-gray-500">Habitación {{ selectedOrder.habitacion?.numero ?? selectedOrder.room_number }} · {{ formatDateTime(selectedOrder.created_at) }}</p>
                    </div>
                    <button @click="closeOrderDetail" class="text-gray-400 hover:text-gray-600">
                        <XMarkIcon class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="rounded-xl bg-gray-50 p-4 border border-gray-100">
                            <p class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-1">Tipo</p>
                            <p class="font-semibold text-[#2F2A26]">{{ formatType(selectedOrder.service_type) }}</p>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-4 border border-gray-100">
                            <p class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-1">Estado</p>
                            <span :class="getStatusBadgeClass(selectedOrder.status)" class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold">
                                {{ formatStatus(selectedOrder.status) }}
                            </span>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-4 border border-gray-100">
                            <p class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-1">Sesión / token</p>
                            <p class="font-mono text-sm text-[#2F2A26] break-all">{{ selectedOrder.session_token || 'Sin token registrado' }}</p>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-4 border border-gray-100">
                            <p class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-1">Total pagado</p>
                            <p class="font-semibold text-[#2F2A26]">{{ formatPrice(selectedOrder.total_price) }}</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                            <h4 class="font-black text-[#2F2A26]">Contenido del pedido</h4>
                        </div>
                        <div class="p-4">
                            <ul v-if="selectedOrder.service_type === 'comida'" class="space-y-3">
                                <li v-for="service in selectedOrder.services" :key="service.id" class="flex items-center justify-between">
                                    <div>
                                        <p class="font-semibold text-[#2F2A26]">{{ service.name }}</p>
                                        <p class="text-sm text-gray-500">{{ service.pivot.quantity }} unidades</p>
                                    </div>
                                    <p class="font-semibold text-[#2F2A26]">{{ formatPrice(service.pivot.price * service.pivot.quantity) }}</p>
                                </li>
                            </ul>
                            <div v-else-if="selectedOrder.service_type === 'limpieza'" class="text-sm text-gray-700">
                                Hora solicitada: <span class="font-semibold text-[#2F2A26]">{{ selectedOrder.requested_time || '-' }}</span>
                            </div>
                            <div v-else class="text-sm text-gray-700">
                                {{ selectedOrder.description || 'Sin descripcion registrada.' }}
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <button
                            v-if="selectedOrder.status === 'recibido'"
                            @click="updateStatus(selectedOrder.id, 'en_proceso')"
                            class="flex-1 bg-[#2F2A26] text-white py-3 rounded-xl font-bold flex items-center justify-center gap-2"
                        >
                            <WrenchScrewdriverIcon class="w-5 h-5" /> Marcar en proceso
                        </button>
                        <button
                            v-if="selectedOrder.status === 'en_proceso'"
                            @click="updateStatus(selectedOrder.id, 'completado')"
                            class="flex-1 bg-green-500 text-white py-3 rounded-xl font-bold flex items-center justify-center gap-2"
                        >
                            <CheckCircleIcon class="w-5 h-5" /> Marcar completado
                        </button>
                        <button @click="closeOrderDetail" class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-xl font-bold">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>