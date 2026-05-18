<script setup>
import Modal from '@/Components/Modal.vue';
import { router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    orders: { type: Array, default: () => [] },
    rooms: { type: Array, default: () => [] },
    minimal: { type: Boolean, default: false },
});

const reactiveOrders = ref([...(props.orders ?? [])]);
const filterPrioridad = ref('');
const filterEstado = ref('');
let pollTimer = null;

const showFormModal = ref(false);
const showResolveModal = ref(false);
const editingOrder = ref(null);
const resolvingOrder = ref(null);

const ticketForm = useForm({
    habitacion_id: '',
    description: '',
    notas_internas: '',
    prioridad: 'media',
});

const resolveForm = useForm({
    notas_resolucion: '',
});

watch(
    () => props.orders,
    (next) => {
        reactiveOrders.value = [...(next ?? [])];
    },
    { deep: true },
);

const refreshOrders = async () => {
    try {
        const { data } = await axios.get(route('orders.poll'), {
            params: { service_type: 'mantenimiento' },
        });
        reactiveOrders.value = data.orders ?? [];
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

const roomNumber = (order) =>
    String(order.room_number ?? order.habitacion?.numero ?? '—');

const formatDate = (value) => {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '—';
    return date.toLocaleString('es-ES', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatTime = (time, fallback) => {
    if (time) {
        const raw = String(time);
        return raw.length >= 5 ? raw.slice(0, 5) : raw;
    }
    if (fallback) {
        return new Date(fallback).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    }
    return '—';
};

const statusKey = (status) => {
    const key = String(status ?? 'recibido').toLowerCase();
    if (key === 'en_camino') return 'en_proceso';
    return key;
};

const statusLabel = (status) => {
    const key = statusKey(status);
    if (key === 'completado') return 'Completado';
    if (key === 'en_proceso') return 'En proceso';
    return 'Pendiente';
};

const statusBadgeClass = (status) => {
    const key = statusKey(status);
    if (key === 'completado') return 'bg-green-100 text-green-800';
    if (key === 'en_proceso') return 'bg-blue-100 text-blue-800';
    return 'bg-yellow-100 text-yellow-800';
};

const prioridadLabel = (prioridad) => {
    const map = { alta: 'Alta', media: 'Media', baja: 'Baja' };
    return map[String(prioridad ?? 'media').toLowerCase()] ?? 'Media';
};

const prioridadBadgeClass = (prioridad) => {
    const key = String(prioridad ?? 'media').toLowerCase();
    if (key === 'alta') return 'bg-[#A64B35]/15 text-[#A64B35] ring-1 ring-[#A64B35]/30';
    if (key === 'baja') return 'bg-gray-100 text-gray-600 ring-1 ring-gray-200';
    return 'bg-amber-100 text-amber-800 ring-1 ring-amber-200';
};

const filteredOrders = computed(() => {
    return reactiveOrders.value.filter((order) => {
        const prioOk =
            !filterPrioridad.value ||
            String(order.prioridad ?? 'media').toLowerCase() === filterPrioridad.value;
        const estadoOk =
            !filterEstado.value || statusKey(order.status) === filterEstado.value;
        return prioOk && estadoOk;
    });
});

const canCreate = computed(() => !props.minimal && props.rooms.length > 0);

const openCreateModal = () => {
    editingOrder.value = null;
    ticketForm.reset();
    ticketForm.clearErrors();
    ticketForm.prioridad = 'media';
    showFormModal.value = true;
};

const openEditModal = (order) => {
    editingOrder.value = order;
    ticketForm.clearErrors();
    ticketForm.habitacion_id = order.habitacion_id ?? '';
    ticketForm.description = order.description ?? '';
    ticketForm.notas_internas = order.notas_internas ?? '';
    ticketForm.prioridad = order.prioridad ?? 'media';
    showFormModal.value = true;
};

const closeFormModal = () => {
    showFormModal.value = false;
    editingOrder.value = null;
    ticketForm.reset();
    ticketForm.clearErrors();
};

const submitTicketForm = () => {
    if (editingOrder.value) {
        ticketForm
            .transform((data) => ({
                description: data.description,
                notas_internas: data.notas_internas,
                prioridad: data.prioridad,
            }))
            .put(route('tasks.maintenance.update', editingOrder.value.id), {
                preserveScroll: true,
                onSuccess: () => {
                    closeFormModal();
                    refreshOrders();
                },
            });
        return;
    }

    ticketForm.post(route('tasks.maintenance.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeFormModal();
            refreshOrders();
        },
    });
};

const openResolveModal = (order) => {
    resolvingOrder.value = order;
    resolveForm.reset();
    resolveForm.clearErrors();
    showResolveModal.value = true;
};

const closeResolveModal = () => {
    showResolveModal.value = false;
    resolvingOrder.value = null;
    resolveForm.reset();
    resolveForm.clearErrors();
};

const submitResolve = () => {
    if (!resolvingOrder.value) return;

    resolveForm
        .transform((data) => ({
            status: 'completado',
            notas_resolucion: data.notas_resolucion,
        }))
        .put(route('tasks.maintenance.update', resolvingOrder.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                const target = reactiveOrders.value.find((o) => o.id === resolvingOrder.value.id);
                if (target) {
                    target.status = 'completado';
                    target.notas_resolucion = resolveForm.notas_resolucion;
                }
                closeResolveModal();
            },
        });
};

const markInProgress = (order) => {
    router.put(
        route('tasks.maintenance.update', order.id),
        { status: 'en_proceso' },
        {
            preserveScroll: true,
            onSuccess: () => {
                const target = reactiveOrders.value.find((o) => o.id === order.id);
                if (target) target.status = 'en_proceso';
            },
        },
    );
};
</script>

<template>
    <div>
        <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-[#2F2A26]">
                    {{ minimal ? 'Mis averías' : 'Helpdesk de mantenimiento' }}
                </h1>
                <p v-if="!minimal" class="mt-1 text-sm text-[#2F2A26]/70">
                    Tickets internos con prioridad, notas técnicas y expediente de cierre.
                </p>
            </div>
            <button
                v-if="canCreate"
                type="button"
                class="rounded-xl bg-[#2F2A26] px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#2F2A26]/90"
                @click="openCreateModal"
            >
                + Nuevo ticket
            </button>
        </header>

        <!-- Filtros -->
        <div
            v-if="!minimal"
            class="mb-5 flex flex-wrap items-end gap-4 rounded-xl border border-[#2F2A26]/10 bg-white p-4 shadow-sm"
        >
            <div class="min-w-[10rem] flex-1">
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-[#2F2A26]/50">
                    Prioridad
                </label>
                <select
                    v-model="filterPrioridad"
                    class="w-full rounded-lg border border-[#2F2A26]/15 bg-white px-3 py-2 text-sm text-[#2F2A26] focus:border-[#2F2A26] focus:outline-none focus:ring-1 focus:ring-[#2F2A26]"
                >
                    <option value="">Todas</option>
                    <option value="alta">Alta</option>
                    <option value="media">Media</option>
                    <option value="baja">Baja</option>
                </select>
            </div>
            <div class="min-w-[10rem] flex-1">
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-[#2F2A26]/50">
                    Estado
                </label>
                <select
                    v-model="filterEstado"
                    class="w-full rounded-lg border border-[#2F2A26]/15 bg-white px-3 py-2 text-sm text-[#2F2A26] focus:border-[#2F2A26] focus:outline-none focus:ring-1 focus:ring-[#2F2A26]"
                >
                    <option value="">Todos</option>
                    <option value="recibido">Pendiente</option>
                    <option value="en_proceso">En proceso</option>
                    <option value="completado">Completado</option>
                </select>
            </div>
            <p class="text-xs text-[#2F2A26]/50">
                {{ filteredOrders.length }} ticket(s)
            </p>
        </div>

        <div
            v-if="filteredOrders.length === 0"
            class="rounded-2xl border border-[#2F2A26]/10 bg-white py-16 text-center text-[#2F2A26]/55 shadow-sm"
        >
            No hay tickets con los filtros seleccionados.
        </div>

        <!-- Vista minimal (técnico) -->
        <div v-else-if="minimal" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <article
                v-for="order in filteredOrders"
                :key="order.id"
                class="flex flex-col rounded-2xl border border-[#2F2A26]/15 bg-white p-5 shadow-sm"
            >
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-[#2F2A26]/45">Habitación</p>
                        <p class="text-4xl font-black text-[#2F2A26]">{{ roomNumber(order) }}</p>
                    </div>
                    <span
                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                        :class="prioridadBadgeClass(order.prioridad)"
                    >
                        {{ prioridadLabel(order.prioridad) }}
                    </span>
                </div>
                <p class="mt-3 line-clamp-3 text-sm text-[#2F2A26]/80">
                    {{ order.description || 'Sin descripción' }}
                </p>
                <p v-if="order.notas_internas" class="mt-2 text-xs text-[#2F2A26]/60">
                    <span class="font-bold">Instrucciones:</span> {{ order.notas_internas }}
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span
                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                        :class="statusBadgeClass(order.status)"
                    >
                        {{ statusLabel(order.status) }}
                    </span>
                    <span class="text-xs text-[#2F2A26]/45">{{ formatDate(order.created_at) }}</span>
                </div>
                <div class="mt-5 flex flex-col gap-2">
                    <button
                        v-if="statusKey(order.status) === 'recibido'"
                        type="button"
                        class="w-full rounded-xl border border-blue-600 py-2.5 text-sm font-bold text-blue-700 hover:bg-blue-50"
                        @click="markInProgress(order)"
                    >
                        Tomar en proceso
                    </button>
                    <button
                        v-if="statusKey(order.status) !== 'completado'"
                        type="button"
                        class="w-full rounded-xl bg-[#2F2A26] py-2.5 text-sm font-bold text-white hover:bg-[#2F2A26]/90"
                        @click="openResolveModal(order)"
                    >
                        Resolver / cerrar
                    </button>
                    <button
                        type="button"
                        class="w-full rounded-xl border border-[#2F2A26]/15 py-2 text-sm font-semibold text-[#2F2A26] hover:bg-[#F9FAFB]"
                        @click="openEditModal(order)"
                    >
                        Editar notas
                    </button>
                </div>
            </article>
        </div>

        <!-- Tabla helpdesk -->
        <div v-else class="overflow-hidden rounded-xl border border-[#2F2A26]/10 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-[#2F2A26]/10 bg-[#F9FAFB]">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-bold uppercase tracking-wide text-[#2F2A26]/55">
                                Hab.
                            </th>
                            <th class="min-w-[14rem] px-4 py-3 text-xs font-bold uppercase tracking-wide text-[#2F2A26]/55">
                                Problema
                            </th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-bold uppercase tracking-wide text-[#2F2A26]/55">
                                Prioridad
                            </th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-bold uppercase tracking-wide text-[#2F2A26]/55">
                                Estado
                            </th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-bold uppercase tracking-wide text-[#2F2A26]/55">
                                Fecha
                            </th>
                            <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-[#2F2A26]/55">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#2F2A26]/10">
                        <tr
                            v-for="order in filteredOrders"
                            :key="order.id"
                            class="hover:bg-[#F9FAFB]/80"
                        >
                            <td class="whitespace-nowrap px-4 py-3 align-top font-bold text-[#2F2A26]">
                                {{ roomNumber(order) }}
                            </td>
                            <td class="max-w-md px-4 py-3 align-top text-[#2F2A26]/80">
                                <p class="whitespace-pre-wrap break-words leading-relaxed">
                                    {{ order.description || '—' }}
                                </p>
                                <p
                                    v-if="order.notas_internas"
                                    class="mt-1 text-xs text-[#2F2A26]/55"
                                >
                                    <span class="font-semibold">Instrucciones:</span>
                                    {{ order.notas_internas }}
                                </p>
                                <p
                                    v-if="order.notas_resolucion && statusKey(order.status) === 'completado'"
                                    class="mt-1 text-xs text-green-800"
                                >
                                    <span class="font-semibold">Expediente:</span>
                                    {{ order.notas_resolucion }}
                                </p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 align-top">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="prioridadBadgeClass(order.prioridad)"
                                >
                                    {{ prioridadLabel(order.prioridad) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 align-top">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="statusBadgeClass(order.status)"
                                >
                                    {{ statusLabel(order.status) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 align-top text-xs text-[#2F2A26]/60">
                                {{ formatDate(order.created_at) }}
                                <span class="block text-[10px] text-[#2F2A26]/40">
                                    {{ formatTime(order.requested_time, order.created_at) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 align-top">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <button
                                        v-if="statusKey(order.status) === 'recibido'"
                                        type="button"
                                        class="rounded-lg border border-blue-600 px-2.5 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50"
                                        @click="markInProgress(order)"
                                    >
                                        En proceso
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg border border-[#2F2A26]/20 px-2.5 py-1.5 text-xs font-semibold text-[#2F2A26] hover:bg-[#F9FAFB]"
                                        @click="openEditModal(order)"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        v-if="statusKey(order.status) !== 'completado'"
                                        type="button"
                                        class="rounded-lg bg-[#2F2A26] px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-[#2F2A26]/90"
                                        @click="openResolveModal(order)"
                                    >
                                        Resolver
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal crear / editar -->
        <Modal :show="showFormModal" max-width="lg" @close="closeFormModal">
            <div class="p-6">
                <h2 class="text-lg font-bold text-[#2F2A26]">
                    {{ editingOrder ? 'Editar ticket' : 'Nuevo ticket de mantenimiento' }}
                </h2>

                <form class="mt-5 space-y-4" @submit.prevent="submitTicketForm">
                    <div v-if="!editingOrder">
                        <label class="mb-1 block text-sm font-semibold text-[#2F2A26]">Habitación</label>
                        <select
                            v-model="ticketForm.habitacion_id"
                            required
                            class="w-full rounded-lg border border-[#2F2A26]/15 px-3 py-2 text-sm focus:border-[#2F2A26] focus:outline-none focus:ring-1 focus:ring-[#2F2A26]"
                        >
                            <option value="" disabled>Seleccionar…</option>
                            <option v-for="room in rooms" :key="room.id" :value="room.id">
                                {{ room.numero }}
                            </option>
                        </select>
                        <p v-if="ticketForm.errors.habitacion_id" class="mt-1 text-xs text-red-600">
                            {{ ticketForm.errors.habitacion_id }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-[#2F2A26]">
                            Descripción del problema
                        </label>
                        <textarea
                            v-model="ticketForm.description"
                            required
                            rows="3"
                            class="w-full rounded-lg border border-[#2F2A26]/15 px-3 py-2 text-sm focus:border-[#2F2A26] focus:outline-none focus:ring-1 focus:ring-[#2F2A26]"
                            placeholder="Avería reportada…"
                        />
                        <p v-if="ticketForm.errors.description" class="mt-1 text-xs text-red-600">
                            {{ ticketForm.errors.description }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-[#2F2A26]">
                            Notas / instrucciones (internas)
                        </label>
                        <textarea
                            v-model="ticketForm.notas_internas"
                            rows="3"
                            class="w-full rounded-lg border border-[#2F2A26]/15 px-3 py-2 text-sm focus:border-[#2F2A26] focus:outline-none focus:ring-1 focus:ring-[#2F2A26]"
                            placeholder="Indicaciones para el técnico…"
                        />
                        <p v-if="ticketForm.errors.notas_internas" class="mt-1 text-xs text-red-600">
                            {{ ticketForm.errors.notas_internas }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-[#2F2A26]">Prioridad</label>
                        <select
                            v-model="ticketForm.prioridad"
                            class="w-full rounded-lg border border-[#2F2A26]/15 px-3 py-2 text-sm focus:border-[#2F2A26] focus:outline-none focus:ring-1 focus:ring-[#2F2A26]"
                        >
                            <option value="baja">Baja</option>
                            <option value="media">Media</option>
                            <option value="alta">Alta</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            class="rounded-lg px-4 py-2 text-sm font-semibold text-[#2F2A26]/70 hover:bg-[#F9FAFB]"
                            @click="closeFormModal"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-[#2F2A26] px-4 py-2 text-sm font-bold text-white hover:bg-[#2F2A26]/90 disabled:opacity-50"
                            :disabled="ticketForm.processing"
                        >
                            {{ editingOrder ? 'Guardar cambios' : 'Crear ticket' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Modal resolver / expediente -->
        <Modal :show="showResolveModal" max-width="lg" @close="closeResolveModal">
            <div class="p-6">
                <h2 class="text-lg font-bold text-[#2F2A26]">Resolver / cerrar tarea</h2>
                <p class="mt-1 text-sm text-[#2F2A26]/65">
                    Documenta el trabajo realizado antes de marcar el ticket como completado.
                </p>
                <p v-if="resolvingOrder" class="mt-2 text-sm font-semibold text-[#2F2A26]">
                    Habitación {{ roomNumber(resolvingOrder) }} — {{ resolvingOrder.description }}
                </p>

                <form class="mt-5 space-y-4" @submit.prevent="submitResolve">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-[#2F2A26]">
                            Notas de resolución (expediente) *
                        </label>
                        <textarea
                            v-model="resolveForm.notas_resolucion"
                            required
                            rows="5"
                            class="w-full rounded-lg border border-[#2F2A26]/15 px-3 py-2 text-sm focus:border-[#2F2A26] focus:outline-none focus:ring-1 focus:ring-[#2F2A26]"
                            placeholder="Qué se reparó, piezas sustituidas, pruebas realizadas…"
                        />
                        <p v-if="resolveForm.errors.notas_resolucion" class="mt-1 text-xs text-red-600">
                            {{ resolveForm.errors.notas_resolucion }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            class="rounded-lg px-4 py-2 text-sm font-semibold text-[#2F2A26]/70 hover:bg-[#F9FAFB]"
                            @click="closeResolveModal"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-green-700 px-4 py-2 text-sm font-bold text-white hover:bg-green-800 disabled:opacity-50"
                            :disabled="resolveForm.processing"
                        >
                            Cerrar ticket
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </div>
</template>
