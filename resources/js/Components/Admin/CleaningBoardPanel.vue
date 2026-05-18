<script setup>
import FaIcon from '@/Components/UI/FaIcon.vue';
import { isCleaningBoardOrder, resolveCleaningRequest } from '@/constants/cleaningRequests';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    orders: { type: Array, default: () => [] },
    minimal: { type: Boolean, default: false },
});

const { t } = useI18n();

const reactiveOrders = ref(
    (props.orders ?? []).filter(isCleaningBoardOrder),
);
const hideCompleted = ref(true);
let pollTimer = null;

const refreshOrders = async () => {
    try {
        const { data } = await axios.get(route('orders.poll'), {
            params: { service_type: 'limpieza' },
        });
        reactiveOrders.value = (data.orders ?? []).filter(isCleaningBoardOrder);
    } catch {
        /* polling silencioso */
    }
};

onMounted(() => {
    pollTimer = window.setInterval(refreshOrders, 15000);
});

onUnmounted(() => {
    if (pollTimer) window.clearInterval(pollTimer);
});

const activeTasks = computed(() => {
    const list = reactiveOrders.value.filter((o) => o.status !== 'completado');
    return hideCompleted.value ? list : reactiveOrders.value;
});

const sortedTasks = computed(() => {
    return [...activeTasks.value].sort((a, b) => {
        const aAmenity = resolveCleaningRequest(a).isAmenity ? 0 : 1;
        const bAmenity = resolveCleaningRequest(b).isAmenity ? 0 : 1;
        if (aAmenity !== bAmenity) return aAmenity - bAmenity;
        return new Date(b.created_at) - new Date(a.created_at);
    });
});

const completeTask = (orderId) => {
    router.put(route('tasks.cleaning.update', orderId), { status: 'completado' }, {
        preserveScroll: true,
        onSuccess: () => {
            const target = reactiveOrders.value.find((o) => o.id === orderId);
            if (target) target.status = 'completado';
        },
    });
};

const formatTime = (time) => {
    if (!time) return '—';
    const raw = String(time);
    return raw.length >= 5 ? raw.slice(0, 5) : raw;
};

const formatDate = (value) => {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleString('es-ES', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
};

const requestLabel = (order) => t(resolveCleaningRequest(order).labelKey);

const cardClass =
    'rounded-xl border border-[#2F2A26]/8 border-t-2 border-t-[#A64B35] bg-white shadow-sm';

const actionButtonClass =
    'w-full rounded-lg border border-[#2F2A26] bg-white py-2.5 text-sm font-semibold text-[#2F2A26] transition hover:bg-[#2F2A26] hover:text-white disabled:opacity-50';

const tableActionButtonClass =
    'rounded-lg border border-[#2F2A26] bg-white px-4 py-2 text-xs font-semibold text-[#2F2A26] transition hover:bg-[#2F2A26] hover:text-white disabled:opacity-50';
</script>

<template>
    <div>
        <header class="mb-6">
            <h1 class="text-2xl font-black text-[#2F2A26]">
                {{ minimal ? 'Mis tareas de limpieza' : 'Tablero de Housekeeping' }}
            </h1>
            <p v-if="!minimal" class="mt-1 text-sm text-[#2F2A26]/70">
                Toallas, papel higiénico y limpieza de habitación con hora preferida.
            </p>
            <label
                v-if="!minimal"
                class="mt-3 inline-flex items-center gap-2 text-sm text-[#2F2A26]/70"
            >
                <input
                    v-model="hideCompleted"
                    type="checkbox"
                    class="rounded border-[#2F2A26]/30 text-[#2F2A26] focus:ring-[#2F2A26]/30"
                />
                Ocultar finalizados
            </label>
        </header>

        <div
            v-if="sortedTasks.length === 0"
            class="rounded-2xl border border-[#2F2A26]/10 bg-white py-16 text-center text-[#2F2A26]/55 shadow-sm"
        >
            No hay tareas pendientes.
        </div>

        <div v-else-if="minimal" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <article
                v-for="order in sortedTasks"
                :key="order.id"
                class="flex flex-col p-5"
                :class="cardClass"
            >
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-[#2F2A26]/45">Habitación</p>
                        <p class="text-4xl font-black text-[#2F2A26]">{{ order.room_number }}</p>
                    </div>
                    <span
                        v-if="resolveCleaningRequest(order).isAmenity"
                        class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-semibold uppercase text-[#2F2A26]"
                    >
                        <FaIcon :icon="resolveCleaningRequest(order).icon" class="text-xs text-[#A64B35]" />
                        Amenity
                    </span>
                </div>

                <div class="mt-3 flex items-center gap-2">
                    <FaIcon
                        :icon="resolveCleaningRequest(order).icon"
                        class="shrink-0 text-lg text-[#A64B35]"
                    />
                    <p class="text-sm font-bold text-[#2F2A26]">{{ requestLabel(order) }}</p>
                </div>

                <p
                    v-if="!resolveCleaningRequest(order).isAmenity"
                    class="mt-3 text-xs font-semibold uppercase tracking-wide text-[#2F2A26]/55"
                >
                    Hora solicitada
                </p>
                <p
                    v-if="!resolveCleaningRequest(order).isAmenity"
                    class="text-2xl font-black tabular-nums text-[#2F2A26]"
                >
                    {{ formatTime(order.requested_time) }}
                </p>
                <p v-else class="mt-2 text-xs text-[#2F2A26]/60">
                    Entrega inmediata
                </p>

                <button type="button" class="mt-6" :class="actionButtonClass" @click="completeTask(order.id)">
                    Marcar entregado
                </button>
            </article>
        </div>

        <div v-else class="overflow-hidden rounded-xl border border-[#2F2A26]/10 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-[#2F2A26]/10 bg-[#F9FAFB]">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-bold uppercase tracking-wide text-[#2F2A26]/55">
                                Hab.
                            </th>
                            <th class="min-w-[12rem] px-4 py-3 text-xs font-bold uppercase tracking-wide text-[#2F2A26]/55">
                                Solicitud
                            </th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-bold uppercase tracking-wide text-[#2F2A26]/55">
                                Hora / Urgencia
                            </th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-bold uppercase tracking-wide text-[#2F2A26]/55">
                                Recibido
                            </th>
                            <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-[#2F2A26]/55">
                                Acción
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#2F2A26]/10">
                        <tr
                            v-for="order in sortedTasks"
                            :key="order.id"
                            class="border-t-2 border-t-[#A64B35] bg-white hover:bg-[#FAFAFA]"
                        >
                            <td class="whitespace-nowrap px-4 py-3 align-middle font-bold text-[#2F2A26]">
                                {{ order.room_number }}
                            </td>
                            <td class="px-4 py-3 align-middle">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-[#A64B35]"
                                    >
                                        <FaIcon :icon="resolveCleaningRequest(order).icon" class="text-base" />
                                    </span>
                                    <div>
                                        <p class="font-bold text-[#2F2A26]">{{ requestLabel(order) }}</p>
                                        <p
                                            v-if="resolveCleaningRequest(order).isAmenity"
                                            class="text-xs text-[#2F2A26]/60"
                                        >
                                            Amenity · entrega rápida
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 align-middle tabular-nums">
                                <span
                                    v-if="resolveCleaningRequest(order).isAmenity"
                                    class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-[#2F2A26]"
                                >
                                    Inmediato
                                </span>
                                <span v-else class="text-lg font-black text-[#2F2A26]">
                                    {{ formatTime(order.requested_time) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 align-middle text-xs text-[#2F2A26]/55">
                                {{ formatDate(order.created_at) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 align-middle text-right">
                                <button
                                    type="button"
                                    :class="tableActionButtonClass"
                                    @click="completeTask(order.id)"
                                >
                                    Marcar entregado
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
