<script setup>
import { computed, ref } from 'vue';
import { useRestauranteOrdersChannel } from '@/composables/useRestauranteOrdersChannel';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SummaryStatCard from '@/Components/Dashboard/SummaryStatCard.vue';
import { CakeIcon, TicketIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    orders: {
        type: Array,
        default: () => [],
    },
    totalServices: {
        type: Number,
        default: 0,
    },
    totalActivities: {
        type: Number,
        default: 0,
    },
});

const reactiveOrders = ref([...(props.orders ?? [])]);
const hideCompleted = ref(true);
const checkedItems = ref({});

useRestauranteOrdersChannel((incoming) => {
    if (reactiveOrders.value.some((o) => o.id === incoming.id)) {
        return;
    }
    reactiveOrders.value.unshift(incoming);
});

const filteredOrders = computed(() => {
    const foodOrders = reactiveOrders.value.filter((order) => order.service_type === 'comida');
    if (!hideCompleted.value) return foodOrders;
    return foodOrders.filter((order) => order.status !== 'completado');
});

const updateStatus = (orderId, status) => {
    router.put(route('orders.kitchen.update', orderId), { status }, {
        preserveScroll: true,
        onSuccess: () => {
            const target = reactiveOrders.value.find((order) => order.id === orderId);
            if (target) target.status = status;
        },
    });
};

const formatMoney = (value) => new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(Number(value ?? 0));

const minutesSinceCreated = (dateString) => {
    const createdTime = new Date(dateString).getTime();
    if (Number.isNaN(createdTime)) return '-- min';
    const diff = Math.max(0, Date.now() - createdTime);
    return `${Math.floor(diff / 60000)} min`;
};

const checklistKey = (orderId, serviceId) => `${orderId}-${serviceId}`;

const orderIsDelivered = (order) => ['entregado', 'completado'].includes(order?.status);

const orderCardClass = (order) => (orderIsDelivered(order)
    ? 'border border-gray-200 bg-gray-100 opacity-60 shadow-sm'
    : 'border border-[#2F2A26]/10 bg-white shadow-md');

const paymentBadgeClass = (order) => {
    if (order?.status === 'pagado') {
        return 'inline-flex items-center rounded-full bg-emerald-100 px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-emerald-900 ring-1 ring-emerald-200';
    }
    return 'inline-flex items-center rounded-full bg-amber-100 px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-amber-950 ring-1 ring-amber-300/80';
};

const paymentBadgeLabel = (order) => (order?.status === 'pagado' ? 'Pagado' : 'Cargar a habitación');
</script>

<template>
    <Head title="Pedidos Actuales" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#F9FAFB] py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-black text-[#2F2A26]">Panel de Cocina</h1>
                        <p class="text-sm text-[#2F2A26]/70">Gestiona pedidos activos con flujo rápido de preparación.</p>
                    </div>

                    <label class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 shadow-sm border border-[#2F2A26]/10 text-sm text-[#2F2A26]">
                        <input v-model="hideCompleted" type="checkbox" class="rounded border-[#A64B35]/30 text-[#A64B35] focus:ring-[#A64B35]" />
                        Ocultar entregados
                    </label>
                </div>

                <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <SummaryStatCard title="Total Servicios" :value="totalServices" icon-bg-class="bg-blue-50">
                        <template #icon>
                            <CakeIcon class="h-5 w-5 text-blue-600" />
                        </template>
                    </SummaryStatCard>
                    <SummaryStatCard title="Total Actividades" :value="totalActivities" icon-bg-class="bg-[#A64B35]/10">
                        <template #icon>
                            <TicketIcon class="h-5 w-5 text-[#A64B35]" />
                        </template>
                    </SummaryStatCard>
                </div>

                <div v-if="filteredOrders.length === 0" class="rounded-2xl bg-white p-8 text-center text-[#2F2A26]/70 shadow-sm">
                    No hay pedidos pendientes para mostrar.
                </div>

                <div v-else class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="order in filteredOrders"
                        :key="order.id"
                        class="overflow-hidden rounded-2xl transition-opacity"
                        :class="orderCardClass(order)"
                    >
                        <div class="rounded-t-xl bg-[#2F2A26] py-4 text-center text-4xl font-black tracking-tight text-white">
                            {{ order.room_number }}
                        </div>

                        <div class="flex justify-center border-b border-[#2F2A26]/10 bg-white px-3 py-3">
                            <span :class="paymentBadgeClass(order)">{{ paymentBadgeLabel(order) }}</span>
                        </div>

                        <div class="p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <span class="rounded-full bg-[#A64B35]/10 px-3 py-1 text-xs font-semibold text-[#A64B35]">
                                    Hace {{ minutesSinceCreated(order.created_at) }}
                                </span>
                                <span class="rounded-full bg-[#2F2A26]/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-[#2F2A26]">
                                    {{ order.status }}
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-3 gap-2 text-xs">
                                <div class="rounded-xl bg-[#F9FAFB] p-2 ring-1 ring-[#2F2A26]/5">
                                    <p class="text-[#2F2A26]/60">Pedido</p>
                                    <p class="font-semibold text-[#2F2A26]">#{{ order.id }}</p>
                                </div>
                                <div class="rounded-xl bg-[#F9FAFB] p-2 ring-1 ring-[#2F2A26]/5">
                                    <p class="text-[#2F2A26]/60">Total</p>
                                    <p class="font-semibold text-[#A64B35]">{{ formatMoney(order.total_price) }}</p>
                                </div>
                                <div class="rounded-xl bg-[#F9FAFB] p-2 ring-1 ring-[#2F2A26]/5">
                                    <p class="text-[#2F2A26]/60">Tipo</p>
                                    <p class="font-semibold capitalize text-[#2F2A26]">{{ order.service_type }}</p>
                                </div>
                            </div>

                            <div
                                v-if="order.notas"
                                class="mt-4 rounded-xl border border-amber-200/80 bg-amber-50 px-3 py-2.5"
                            >
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-amber-800/70">Notas del huésped</p>
                                <p class="mt-1 text-sm text-[#2F2A26] whitespace-pre-wrap">{{ order.notas }}</p>
                            </div>

                            <div class="mt-4">
                                <p class="mb-2 text-sm font-semibold text-[#2F2A26]">Checklist de platos</p>
                                <ul class="space-y-2">
                                    <li
                                        v-for="service in (order.services ?? [])"
                                        :key="service.id"
                                        class="flex items-center gap-2 rounded-lg border border-[#2F2A26]/10 px-3 py-2"
                                    >
                                        <input
                                            v-model="checkedItems[checklistKey(order.id, service.id)]"
                                            type="checkbox"
                                            class="rounded border-[#A64B35]/30 text-[#A64B35] focus:ring-[#A64B35]"
                                            :disabled="orderIsDelivered(order)"
                                        />
                                        <span class="text-sm text-[#2F2A26]">{{ service.name }}</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="mt-5 flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    class="min-h-[44px] flex-1 rounded-xl bg-[#A64B35] px-3 py-2 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="orderIsDelivered(order)"
                                    @click="updateStatus(order.id, 'en_proceso')"
                                >
                                    Marcar en cocina
                                </button>
                                <button
                                    type="button"
                                    class="min-h-[44px] flex-1 rounded-xl bg-[#7C3D2D] px-3 py-2 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="orderIsDelivered(order)"
                                    @click="updateStatus(order.id, 'completado')"
                                >
                                    Completado
                                </button>
                            </div>

                            <a
                                v-if="orderIsDelivered(order)"
                                :href="route('orders.kitchen.invoice', order.id)"
                                class="mt-4 flex min-h-[44px] w-full items-center justify-center rounded-xl border-2 border-[#2F2A26]/25 bg-white px-4 py-2 text-sm font-semibold text-[#2F2A26] transition hover:border-[#A64B35] hover:text-[#A64B35]"
                            >
                                Generar factura
                            </a>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>