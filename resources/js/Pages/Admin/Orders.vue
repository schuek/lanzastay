<script setup>
import LaravelEcho from 'laravel-echo';
import { Transition, computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    orders: {
        type: Array,
        default: () => [],
    },
});

const reactiveOrders = ref([...(props.orders ?? [])]);
const hideCompleted = ref(true);
const checkedItems = ref({});
const kitchenToast = ref(false);

onMounted(() => {
    const client = window.Echo;
    if (!client || !(client instanceof LaravelEcho)) {
        return;
    }

    client.channel('kitchen').listen('.OrderCreated', (payload) => {
        const incoming = payload?.order;
        if (!incoming?.id) {
            return;
        }
        if (reactiveOrders.value.some((o) => o.id === incoming.id)) {
            return;
        }
        reactiveOrders.value.unshift(incoming);
        kitchenToast.value = true;
        window.setTimeout(() => {
            kitchenToast.value = false;
        }, 3500);
    });
});

onUnmounted(() => {
    window.Echo?.leave('kitchen');
});

const filteredOrders = computed(() => {
    if (!hideCompleted.value) return reactiveOrders.value;
    return reactiveOrders.value.filter((order) => order.status !== 'completado');
});

const updateStatus = (orderId, status) => {
    router.put(route('orders.update', orderId), { status }, {
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
</script>

<template>
    <Head title="Pedidos Actuales" />

    <AuthenticatedLayout>
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="kitchenToast"
                class="fixed top-4 left-1/2 z-[100] -translate-x-1/2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-lg"
                role="status"
            >
                ¡Nuevo pedido recibido!
            </div>
        </Transition>

        <div class="min-h-screen bg-[#F9FAFB] py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="text-3xl font-black text-[#2F2A26]">Panel de Cocina</h1>
                        <p class="text-sm text-[#2F2A26]/70">Gestiona pedidos activos con flujo rapido de preparacion.</p>
                    </div>

                    <label class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 shadow-sm border border-[#2F2A26]/10 text-sm text-[#2F2A26]">
                        <input v-model="hideCompleted" type="checkbox" class="rounded border-[#A64B35]/30 text-[#A64B35] focus:ring-[#A64B35]" />
                        Ocultar entregados
                    </label>
                </div>

                <div v-if="filteredOrders.length === 0" class="rounded-2xl bg-white p-8 text-center text-[#2F2A26]/70 shadow-sm">
                    No hay pedidos pendientes para mostrar.
                </div>

                <div v-else class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="order in filteredOrders"
                        :key="order.id"
                        class="rounded-2xl bg-white p-5 shadow-sm border border-[#2F2A26]/10"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs uppercase tracking-wider text-[#2F2A26]/55">Habitación</p>
                                <p class="text-5xl font-black leading-none text-[#2F2A26] mt-1">{{ order.room_number }}</p>
                            </div>
                            <span class="rounded-full bg-[#A64B35]/10 px-3 py-1 text-xs font-semibold text-[#A64B35]">
                                Hace {{ minutesSinceCreated(order.created_at) }}
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-3 gap-2 text-xs">
                            <div class="rounded-xl bg-[#F9FAFB] p-2">
                                <p class="text-[#2F2A26]/60">Pedido</p>
                                <p class="font-semibold text-[#2F2A26]">#{{ order.id }}</p>
                            </div>
                            <div class="rounded-xl bg-[#F9FAFB] p-2">
                                <p class="text-[#2F2A26]/60">Total</p>
                                <p class="font-semibold text-[#2F2A26]">{{ formatMoney(order.total_price) }}</p>
                            </div>
                            <div class="rounded-xl bg-[#F9FAFB] p-2">
                                <p class="text-[#2F2A26]/60">Estado</p>
                                <p class="font-semibold capitalize text-[#2F2A26]">{{ order.status }}</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <p class="text-sm font-semibold text-[#2F2A26] mb-2">Checklist de platos</p>
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
                                    />
                                    <span class="text-sm text-[#2F2A26]">{{ service.name }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="mt-5 flex items-center gap-2">
                            <button
                                @click="updateStatus(order.id, 'en_proceso')"
                                class="flex-1 rounded-xl bg-[#A64B35] px-3 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                            >
                                Marcar en Cocina
                            </button>
                            <button
                                @click="updateStatus(order.id, 'completado')"
                                class="flex-1 rounded-xl bg-[#7C3D2D] px-3 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                            >
                                Completado
                            </button>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>