<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { DocumentArrowDownIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { computed, ref } from 'vue';

defineProps({
    rooms: Array,
});

const createForm = useForm({
    number: '',
    status: 'disponible',
});

const editRoomId = ref(null);
const editForm = useForm({
    number: '',
    status: 'disponible',
});
const createModalOpen = ref(false);
const checkInModalOpen = ref(false);
const checkInRoomId = ref(null);
const checkInForm = useForm({
    guest_email: '',
});
const checkOutModalOpen = ref(false);
const checkOutRoomId = ref(null);
const deleteModalOpen = ref(false);
const deleteRoomId = ref(null);
const mostrarModalQR = ref(false);
const habitacionSeleccionada = ref(null);

const roomNumber = (room) => String(room?.number ?? room?.numero ?? '');

const adminRoomRoute = (name, roomOrId) => {
    const id = typeof roomOrId === 'object' ? roomOrId?.id : roomOrId;
    return route(name, { room: id });
};

const guestMenuUrl = (room) => {
    if (!room?.access_token) {
        return '';
    }
    return route('menu.show', { habitacion: room.access_token });
};

const roomStatus = (room) => (editRoomId.value === room?.id ? editForm.status : room?.status);

const isRoomOccupied = (room) => roomStatus(room) === 'ocupada';

const guestEmailFor = (room) => {
    const email = room?.guest_email?.trim();
    return email || null;
};

const qrImageUrl = computed(() => {
    const room = habitacionSeleccionada.value;
    if (!room?.access_token) {
        return '';
    }
    const data = guestMenuUrl(room);
    return `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(data)}`;
});

const statusLabel = (status) =>
    ({
        disponible: 'Libre',
        ocupada: 'Ocupada',
        mantenimiento: 'Mantenimiento',
    })[status] ?? status;

const statusBadgeClass = (status) =>
    ({
        disponible: 'bg-emerald-50 text-emerald-800 ring-emerald-200/80',
        ocupada: 'bg-[#A64B35]/12 text-[#A64B35] ring-[#A64B35]/25',
        mantenimiento: 'bg-amber-50 text-amber-900 ring-amber-200/80',
    })[status] ?? 'bg-[#2F2A26]/5 text-[#2F2A26]/70 ring-[#2F2A26]/10';

const rowClass = (room) => {
    if (editRoomId.value === room?.id) {
        return 'bg-[#2F2A26]/[0.03]';
    }
    switch (room?.status) {
        case 'ocupada':
            return 'bg-[#A64B35]/[0.05] hover:bg-[#A64B35]/[0.08]';
        case 'mantenimiento':
            return 'bg-amber-50/50 hover:bg-amber-50/70';
        default:
            return 'bg-white hover:bg-gray-50/80';
    }
};

const isDisponible = (room) => room.status === 'disponible';
const isOcupada = (room) => room.status === 'ocupada';

const openCreateModal = () => {
    createForm.reset();
    createForm.status = 'disponible';
    createModalOpen.value = true;
};

const closeCreateModal = () => {
    createModalOpen.value = false;
    createForm.reset();
    createForm.clearErrors();
};

const submitCreate = () => {
    createForm.post(route('rooms.store'), {
        onSuccess: () => {
            closeCreateModal();
        },
    });
};

const abrirModalQR = (room) => {
    habitacionSeleccionada.value = room;
    mostrarModalQR.value = true;
};

const cerrarModalQR = () => {
    mostrarModalQR.value = false;
    habitacionSeleccionada.value = null;
};

const startEdit = (room) => {
    editRoomId.value = room.id;
    editForm.number = room.numero;
    editForm.status = room.status;
};

const cancelEdit = () => {
    editRoomId.value = null;
    editForm.reset();
};

const saveEdit = (roomId) => {
    editForm.put(adminRoomRoute('rooms.update', roomId), {
        onSuccess: () => {
            cancelEdit();
        },
    });
};

const openDeleteModal = (roomId) => {
    deleteRoomId.value = roomId;
    deleteModalOpen.value = true;
};

const removeRoom = () => {
    router.delete(adminRoomRoute('rooms.destroy', deleteRoomId.value), {
        onSuccess: () => {
            deleteModalOpen.value = false;
            deleteRoomId.value = null;
        },
    });
};

const openCheckInModal = (room) => {
    checkInRoomId.value = room.id;
    checkInForm.reset();
    checkInModalOpen.value = true;
};

const submitCheckIn = () => {
    checkInForm.post(adminRoomRoute('rooms.checkin', checkInRoomId.value), {
        onSuccess: () => {
            checkInModalOpen.value = false;
        },
    });
};

const openCheckOutModal = (roomId) => {
    checkOutRoomId.value = roomId;
    checkOutModalOpen.value = true;
};

const checkOutAndInvoice = () => {
    if (!checkOutRoomId.value) return;
    checkOutModalOpen.value = false;
    window.location.href = adminRoomRoute('rooms.checkout.invoice', checkOutRoomId.value);
};
</script>

<template>
    <Head title="Habitaciones" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-[#2F2A26]">Gestión de Habitaciones</h2>
        </template>

        <div class="min-h-screen bg-gray-50 py-10">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-[#2F2A26]">Inventario</h3>
                        <p class="mt-0.5 text-sm text-[#2F2A26]/60">
                            Libres, ocupadas y mantenimiento — acciones según el estado de cada habitación.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-[#2F2A26] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#2F2A26]/90"
                        @click="openCreateModal"
                    >
                        <PlusIcon class="h-5 w-5" aria-hidden="true" />
                        Añadir Habitación
                    </button>
                </div>

                <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 text-gray-500">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm uppercase tracking-wide">Habitación</th>
                                <th class="px-4 py-3 text-left text-sm uppercase tracking-wide">Estado</th>
                                <th class="px-4 py-3 text-left text-sm uppercase tracking-wide">Sesión</th>
                                <th class="px-4 py-3 text-right text-sm uppercase tracking-wide">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="room in rooms" :key="room.id">
                                <tr class="border-t border-gray-100 transition-colors" :class="rowClass(room)">
                                    <td class="px-4 py-3">
                                        <input
                                            v-if="editRoomId === room.id"
                                            v-model="editForm.number"
                                            class="rounded-xl border-gray-300 focus:border-[#A64B35] focus:ring-[#A64B35]"
                                        >
                                        <span v-else class="font-bold text-[#2F2A26]">{{ room.numero }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <select
                                            v-if="editRoomId === room.id"
                                            v-model="editForm.status"
                                            class="rounded-xl border-gray-300 focus:border-[#A64B35] focus:ring-[#A64B35]"
                                        >
                                            <option value="disponible">Disponible</option>
                                            <option value="ocupada">Ocupada</option>
                                            <option value="mantenimiento">Mantenimiento</option>
                                        </select>
                                        <span
                                            v-else
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold uppercase tracking-wide ring-1"
                                            :class="statusBadgeClass(room.status)"
                                        >
                                            {{ statusLabel(room.status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-[#2F2A26]/55">
                                        <span>{{ room.current_session_token ? 'Activa' : 'Sin sesión' }}</span>
                                        <span
                                            v-if="isRoomOccupied(room) && guestEmailFor(room)"
                                            class="mt-1 block text-xs text-[#2F2A26]/65"
                                        >
                                            {{ guestEmailFor(room) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <button
                                                type="button"
                                                class="rounded-xl border border-[#2F2A26]/15 bg-white px-3 py-1 text-xs font-semibold text-[#2F2A26]/80 transition hover:bg-[#2F2A26]/5"
                                                @click="abrirModalQR(room)"
                                            >
                                                Ver QR
                                            </button>

                                            <template v-if="editRoomId === room.id">
                                                <button
                                                    type="button"
                                                    class="rounded-xl bg-[#A64B35] px-3 py-1 text-xs font-bold text-white"
                                                    @click="saveEdit(room.id)"
                                                >
                                                    Guardar
                                                </button>
                                                <button
                                                    type="button"
                                                    class="rounded-xl border border-gray-200 px-3 py-1 text-xs font-semibold text-gray-600"
                                                    @click="cancelEdit"
                                                >
                                                    Cancelar
                                                </button>
                                                <button
                                                    type="button"
                                                    class="rounded-xl border border-red-200 px-3 py-1 text-xs font-bold text-red-600"
                                                    @click="openDeleteModal(room.id)"
                                                >
                                                    Eliminar
                                                </button>
                                            </template>
                                            <template v-else>
                                                <button
                                                    type="button"
                                                    class="rounded-xl bg-[#2F2A26] px-3 py-1 text-xs font-bold text-white"
                                                    @click="startEdit(room)"
                                                >
                                                    Editar
                                                </button>

                                                <button
                                                    v-if="isDisponible(room)"
                                                    type="button"
                                                    class="rounded-xl bg-[#A64B35] px-3 py-1.5 text-xs font-bold text-white shadow-sm transition hover:bg-[#8f3f2e]"
                                                    @click="openCheckInModal(room)"
                                                >
                                                    Check-in
                                                </button>

                                                <a
                                                    v-if="isOcupada(room)"
                                                    :href="adminRoomRoute('rooms.invoice.download', room)"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="inline-flex items-center gap-1 rounded-xl border border-[#2F2A26]/20 px-3 py-1 text-xs font-semibold text-[#2F2A26] transition hover:bg-[#2F2A26]/5"
                                                >
                                                    <DocumentArrowDownIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                                                    Descargar Factura PDF
                                                </a>
                                                <button
                                                    v-if="isOcupada(room)"
                                                    type="button"
                                                    class="rounded-xl bg-[#A64B35] px-3 py-1.5 text-xs font-bold text-white shadow-sm transition hover:bg-[#8f3f2e]"
                                                    @click="openCheckOutModal(room.id)"
                                                >
                                                    Check-out
                                                </button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="editRoomId === room.id" class="border-t-0 bg-[#2F2A26]/[0.03]">
                                    <td colspan="4" class="px-4 pb-4 pt-0">
                                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[#2F2A26]/55">
                                            Huésped actual:
                                        </label>
                                        <input
                                            v-if="isRoomOccupied(room) && guestEmailFor(room)"
                                            :value="guestEmailFor(room)"
                                            type="email"
                                            readonly
                                            class="w-full max-w-md cursor-default rounded-xl border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-700"
                                        >
                                        <p v-else-if="isRoomOccupied(room)" class="text-sm text-gray-500">
                                            Ocupada — sin correo registrado
                                        </p>
                                        <p v-else class="text-sm italic text-gray-500">Sin huésped actual</p>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="createModalOpen" max-width="md" @close="closeCreateModal">
            <div class="p-6">
                <h3 class="text-lg font-bold text-[#2F2A26]">Añadir habitación</h3>
                <p class="mt-1 text-sm text-[#2F2A26]/60">Registra una nueva habitación en el inventario activo.</p>
                <form class="mt-5 space-y-4" @submit.prevent="submitCreate">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[#2F2A26]/55">
                            Número
                        </label>
                        <input
                            v-model="createForm.number"
                            type="text"
                            required
                            placeholder="Ej. 101"
                            class="w-full rounded-xl border-gray-300 focus:border-[#A64B35] focus:ring-[#A64B35]"
                        >
                        <p v-if="createForm.errors.number" class="mt-1 text-xs text-red-600">{{ createForm.errors.number }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[#2F2A26]/55">
                            Estado inicial
                        </label>
                        <select
                            v-model="createForm.status"
                            class="w-full rounded-xl border-gray-300 focus:border-[#A64B35] focus:ring-[#A64B35]"
                        >
                            <option value="disponible">Disponible</option>
                            <option value="ocupada">Ocupada</option>
                            <option value="mantenimiento">Mantenimiento</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                        <button
                            type="button"
                            class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600"
                            @click="closeCreateModal"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="rounded-xl bg-[#A64B35] px-5 py-2 text-sm font-bold text-white hover:bg-[#8f3f2d] disabled:opacity-60"
                        >
                            Crear habitación
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <div v-if="checkInModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="checkInModalOpen = false"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-bold text-[#2F2A26]">Check-in de huésped</h3>
                <p class="mt-1 text-sm text-gray-500">Introduce el email para activar la sesión de la habitación.</p>
                <form class="mt-4 space-y-3" @submit.prevent="submitCheckIn">
                    <input
                        v-model="checkInForm.guest_email"
                        type="email"
                        required
                        placeholder="huesped@email.com"
                        class="w-full rounded-xl border-gray-300 focus:border-[#A64B35] focus:ring-[#A64B35]"
                    >
                    <p v-if="checkInForm.errors.guest_email" class="text-xs text-red-600">{{ checkInForm.errors.guest_email }}</p>
                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600"
                            @click="checkInModalOpen = false"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="checkInForm.processing"
                            class="rounded-xl bg-[#A64B35] px-4 py-2 text-sm font-semibold text-white hover:bg-[#8f3f2e]"
                        >
                            Confirmar check-in
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="checkOutModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="checkOutModalOpen = false"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-bold text-[#2F2A26]">Confirmar check-out</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Puedes descargar la factura de la estancia actual o confirmar el check-out (cierra la habitación y envía la factura por email).
                </p>
                <div class="flex flex-wrap justify-end gap-2 pt-5">
                    <button
                        type="button"
                        class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600"
                        @click="checkOutModalOpen = false"
                    >
                        Cancelar
                    </button>
                    <a
                        v-if="checkOutRoomId"
                        :href="adminRoomRoute('rooms.invoice.download', checkOutRoomId)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-[#2F2A26]/20 px-4 py-2 text-sm font-semibold text-[#2F2A26] transition hover:bg-[#2F2A26]/5"
                    >
                        <DocumentArrowDownIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                        Descargar Factura PDF
                    </a>
                    <button
                        type="button"
                        class="rounded-xl bg-[#A64B35] px-4 py-2 text-sm font-semibold text-white hover:bg-[#8f3f2e]"
                        @click="checkOutAndInvoice"
                    >
                        Confirmar check-out
                    </button>
                </div>
            </div>
        </div>

        <div v-if="mostrarModalQR && habitacionSeleccionada" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="cerrarModalQR"></div>
            <div class="relative w-full max-w-xs rounded-2xl border border-gray-100 bg-white p-8 text-center shadow-xl">
                <h3 class="mb-1 text-base font-bold text-[#2F2A26]">
                    Habitación {{ roomNumber(habitacionSeleccionada) }}
                </h3>
                <p class="mb-6 text-xs text-gray-500">Escanea para acceder al menú del huésped</p>

                <div class="flex justify-center">
                    <img
                        :src="qrImageUrl"
                        :alt="`Código QR habitación ${roomNumber(habitacionSeleccionada)}`"
                        width="250"
                        height="250"
                        class="rounded-lg border border-gray-100"
                    >
                </div>

                <button
                    type="button"
                    class="mt-8 w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100"
                    @click="cerrarModalQR"
                >
                    Cerrar
                </button>
            </div>
        </div>

        <Modal :show="deleteModalOpen" max-width="md" @close="deleteModalOpen = false">
            <div class="p-6">
                <h3 class="text-lg font-bold text-[#2F2A26]">Eliminar habitación</h3>
                <p class="mt-1 text-sm text-gray-500">Esta acción no se puede deshacer.</p>
                <div class="flex justify-end gap-2 pt-5">
                    <button
                        type="button"
                        class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600"
                        @click="deleteModalOpen = false"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white"
                        @click="removeRoom"
                    >
                        Eliminar
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
