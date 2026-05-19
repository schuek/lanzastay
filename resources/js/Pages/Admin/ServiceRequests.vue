<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    peticionesLimpieza: { type: Array, default: () => [] },
    avisosMantenimiento: { type: Array, default: () => [] },
});

const tab = ref('limpieza');
const updatingId = ref(null);

const roomNumber = (order) =>
    String(order.room_number ?? order.habitacion?.numero ?? '—');

const formatTime = (value) => {
    if (!value) return '—';
    const raw = String(value);
    return raw.length >= 5 ? raw.slice(0, 5) : raw;
};

const formatDate = (value) => {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleString('es-ES', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
};

const statusLabel = (status, serviceType) => {
    const key = String(status ?? '').toLowerCase();
    if (key === 'completado') {
        return serviceType === 'limpieza' ? 'Completado' : 'Resuelto';
    }
    if (key === 'en_proceso' || key === 'en_camino') {
        return 'En curso';
    }
    return 'Pendiente';
};

const statusBadgeClass = (status) => {
    const key = String(status ?? '').toLowerCase();
    if (key === 'completado') {
        return 'bg-[#2F2A26]/10 text-[#2F2A26]';
    }
    if (key === 'en_proceso' || key === 'en_camino') {
        return 'bg-[#D9C5B2]/60 text-[#2F2A26]';
    }
    return 'bg-[#F0F0F0] text-[#2F2A26]';
};

const statusSelectClass =
    'max-w-[9rem] rounded-lg border border-[#E8E8E8] bg-white px-2 py-1.5 text-xs font-medium text-[#2F2A26] shadow-sm focus:border-[#A64B35] focus:outline-none focus:ring-1 focus:ring-[#A64B35]/25 disabled:opacity-50';

const btnInProgressClass =
    'rounded-lg border border-[#2F2A26] bg-transparent px-2.5 py-1.5 text-xs font-semibold text-[#2F2A26] transition-colors hover:bg-[#F0F0F0] disabled:opacity-50';

const btnCompleteClass =
    'rounded-lg bg-[#2F2A26] px-2.5 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-black disabled:opacity-50';

const updateStatus = (orderId, status) => {
    updatingId.value = orderId;
    router.put(route('admin.service-requests.update', orderId), { status }, {
        preserveScroll: true,
        onFinish: () => {
            updatingId.value = null;
        },
    });
};

const markInProgress = (order) => {
    if (order.status === 'recibido') {
        updateStatus(order.id, 'en_proceso');
    }
};

const markResolved = (order) => {
    updateStatus(order.id, 'completado');
};
</script>

<template>
    <Head title="Peticiones de departamentos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-[#2F2A26]">
                Peticiones de Limpieza y Mantenimiento
            </h2>
        </template>

        <div class="min-h-screen bg-[#F9FAFB] py-8 sm:py-10">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <p class="text-sm text-[#2F2A26]/65">
                    Supervisa y actualiza el estado de las solicitudes enviadas por los huéspedes desde el menú de la habitación.
                </p>

                <div class="flex flex-wrap gap-2 border-b border-[#2F2A26]/10 pb-1">
                    <button
                        type="button"
                        class="rounded-t-lg px-4 py-2 text-sm font-bold transition"
                        :class="tab === 'limpieza'
                            ? 'bg-[#A64B35] text-white'
                            : 'bg-white text-[#2F2A26] hover:bg-[#2F2A26]/5'"
                        @click="tab = 'limpieza'"
                    >
                        Peticiones de Limpieza
                        <span
                            class="ml-2 rounded-full px-2 py-0.5 text-xs"
                            :class="tab === 'limpieza' ? 'bg-white/20' : 'bg-[#2F2A26]/10'"
                        >
                            {{ peticionesLimpieza.length }}
                        </span>
                    </button>
                    <button
                        type="button"
                        class="rounded-t-lg px-4 py-2 text-sm font-bold transition"
                        :class="tab === 'mantenimiento'
                            ? 'bg-[#A64B35] text-white'
                            : 'bg-white text-[#2F2A26] hover:bg-[#2F2A26]/5'"
                        @click="tab = 'mantenimiento'"
                    >
                        Avisos de Mantenimiento
                        <span
                            class="ml-2 rounded-full px-2 py-0.5 text-xs"
                            :class="tab === 'mantenimiento' ? 'bg-white/20' : 'bg-[#2F2A26]/10'"
                        >
                            {{ avisosMantenimiento.length }}
                        </span>
                    </button>
                </div>

                <!-- Limpieza -->
                <div v-show="tab === 'limpieza'" class="overflow-hidden rounded-xl border border-[#2F2A26]/10 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="border-b border-[#2F2A26]/10 bg-[#F9FAFB]">
                                <tr>
                                    <th class="whitespace-nowrap px-4 py-3 text-xs font-bold uppercase tracking-wide text-[#2F2A26]/55">
                                        Habitación
                                    </th>
                                    <th class="whitespace-nowrap px-4 py-3 text-xs font-bold uppercase tracking-wide text-[#2F2A26]/55">
                                        Hora de limpieza
                                    </th>
                                    <th class="whitespace-nowrap px-4 py-3 text-xs font-bold uppercase tracking-wide text-[#2F2A26]/55">
                                        Estado
                                    </th>
                                    <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-[#2F2A26]/55">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#2F2A26]/10">
                                <tr v-for="order in peticionesLimpieza" :key="order.id" class="hover:bg-[#F9FAFB]/80">
                                    <td class="whitespace-nowrap px-4 py-3 font-bold text-[#2F2A26]">
                                        {{ roomNumber(order) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-[#2F2A26]/80">
                                        {{ formatTime(order.requested_time) }}
                                        <span v-if="order.created_at" class="block text-[10px] text-[#2F2A26]/45">
                                            Solicitado {{ formatDate(order.created_at) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="statusBadgeClass(order.status)"
                                        >
                                            {{ statusLabel(order.status, 'limpieza') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            <select
                                                :value="order.status"
                                                :class="statusSelectClass"
                                                :disabled="updatingId === order.id"
                                                @change="updateStatus(order.id, $event.target.value)"
                                            >
                                                <option value="recibido">Pendiente</option>
                                                <option value="en_proceso">En curso</option>
                                                <option value="completado">Completado</option>
                                            </select>
                                            <button
                                                v-if="order.status === 'recibido'"
                                                type="button"
                                                :class="btnInProgressClass"
                                                :disabled="updatingId === order.id"
                                                @click="markInProgress(order)"
                                            >
                                                En curso
                                            </button>
                                            <button
                                                v-if="order.status !== 'completado'"
                                                type="button"
                                                :class="btnCompleteClass"
                                                :disabled="updatingId === order.id"
                                                @click="markResolved(order)"
                                            >
                                                Completar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="peticionesLimpieza.length === 0">
                                    <td colspan="4" class="px-4 py-12 text-center text-sm text-[#2F2A26]/55">
                                        No hay peticiones de limpieza registradas.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mantenimiento -->
                <div v-show="tab === 'mantenimiento'" class="overflow-hidden rounded-xl border border-[#2F2A26]/10 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="border-b border-[#2F2A26]/10 bg-[#F9FAFB]">
                                <tr>
                                    <th class="whitespace-nowrap px-4 py-3 text-xs font-bold uppercase tracking-wide text-[#2F2A26]/55">
                                        Habitación
                                    </th>
                                    <th class="min-w-[12rem] px-4 py-3 text-xs font-bold uppercase tracking-wide text-[#2F2A26]/55">
                                        Descripción del problema
                                    </th>
                                    <th class="whitespace-nowrap px-4 py-3 text-xs font-bold uppercase tracking-wide text-[#2F2A26]/55">
                                        Estado
                                    </th>
                                    <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-[#2F2A26]/55">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#2F2A26]/10">
                                <tr v-for="order in avisosMantenimiento" :key="order.id" class="hover:bg-[#F9FAFB]/80">
                                    <td class="whitespace-nowrap px-4 py-3 align-top font-bold text-[#2F2A26]">
                                        {{ roomNumber(order) }}
                                    </td>
                                    <td class="max-w-md px-4 py-3 align-top text-[#2F2A26]/80">
                                        <p class="whitespace-pre-wrap break-words text-sm leading-relaxed sm:max-w-lg">
                                            {{ order.description || '—' }}
                                        </p>
                                        <span v-if="order.created_at" class="mt-1 block text-[10px] text-[#2F2A26]/45">
                                            Reportado {{ formatDate(order.created_at) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 align-top">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="statusBadgeClass(order.status)"
                                        >
                                            {{ statusLabel(order.status, 'mantenimiento') }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 align-top">
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            <select
                                                :value="order.status"
                                                :class="statusSelectClass"
                                                :disabled="updatingId === order.id"
                                                @change="updateStatus(order.id, $event.target.value)"
                                            >
                                                <option value="recibido">Pendiente</option>
                                                <option value="en_proceso">En curso</option>
                                                <option value="completado">Resuelto</option>
                                            </select>
                                            <button
                                                v-if="order.status === 'recibido'"
                                                type="button"
                                                :class="btnInProgressClass"
                                                :disabled="updatingId === order.id"
                                                @click="markInProgress(order)"
                                            >
                                                En curso
                                            </button>
                                            <button
                                                v-if="order.status !== 'completado'"
                                                type="button"
                                                :class="btnCompleteClass"
                                                :disabled="updatingId === order.id"
                                                @click="markResolved(order)"
                                            >
                                                Resuelto
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="avisosMantenimiento.length === 0">
                                    <td colspan="4" class="px-4 py-12 text-center text-sm text-[#2F2A26]/55">
                                        No hay avisos de mantenimiento registrados.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
