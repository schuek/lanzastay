<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
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
const checkInModalOpen = ref(false);
const checkInRoomId = ref(null);
const checkInForm = useForm({
    guest_email: '',
});
const checkOutModalOpen = ref(false);
const checkOutRoomId = ref(null);
const deleteModalOpen = ref(false);
const deleteRoomId = ref(null);

const submitCreate = () => {
    createForm.post(route('rooms.store'), {
        onSuccess: () => createForm.reset('number'),
    });
};

const startEdit = (room) => {
    editRoomId.value = room.id;
    editForm.number = room.numero;
    editForm.status = room.status;
};

const saveEdit = (roomId) => {
    editForm.put(route('rooms.update', roomId), {
        onSuccess: () => {
            editRoomId.value = null;
        },
    });
};

const openDeleteModal = (roomId) => {
    deleteRoomId.value = roomId;
    deleteModalOpen.value = true;
};
const removeRoom = () => {
    router.delete(route('rooms.destroy', deleteRoomId.value), {
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
    checkInForm.post(route('rooms.checkin', checkInRoomId.value), {
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
    window.location.href = route('rooms.checkout.invoice', checkOutRoomId.value);
};
</script>

<template>
    <Head title="Habitaciones" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Habitaciones</h2>
        </template>

        <div class="py-10 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-bold text-gray-800 mb-4">Añadir Habitación</h3>
                    <form @submit.prevent="submitCreate" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <input v-model="createForm.number" type="text" placeholder="Numero habitacion" class="rounded-xl border-gray-300 focus:border-[#A64B35] focus:ring-[#A64B35]">
                        <select v-model="createForm.status" class="rounded-xl border-gray-300 focus:border-[#A64B35] focus:ring-[#A64B35]">
                            <option value="disponible">Disponible</option>
                            <option value="ocupada">Ocupada</option>
                            <option value="mantenimiento">Mantenimiento</option>
                        </select>
                        <button type="submit" class="bg-[#A64B35] text-white rounded-full font-bold px-4 py-2 hover:opacity-90">Crear</button>
                    </form>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
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
                            <tr v-for="room in rooms" :key="room.id" class="border-t border-gray-100">
                                <td class="px-4 py-3">
                                    <input v-if="editRoomId === room.id" v-model="editForm.number" class="rounded-xl border-gray-300">
                                    <span v-else class="font-bold text-gray-800">{{ room.numero }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <select v-if="editRoomId === room.id" v-model="editForm.status" class="rounded-xl border-gray-300">
                                        <option value="disponible">Disponible</option>
                                        <option value="ocupada">Ocupada</option>
                                        <option value="mantenimiento">Mantenimiento</option>
                                    </select>
                                    <span v-else class="text-sm font-bold" :class="room.status === 'ocupada' ? 'text-[#A64B35]' : 'text-gray-500'">{{ room.status }}</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">{{ room.current_session_token ? 'Activa' : 'Sin sesión' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <button v-if="editRoomId === room.id" @click="saveEdit(room.id)" class="px-3 py-1 rounded-xl bg-[#A64B35] text-white text-xs font-bold">Guardar</button>
                                        <button v-else @click="startEdit(room)" class="px-3 py-1 rounded-xl bg-gray-800 text-white text-xs font-bold">Editar</button>
                                        <button
                                            v-if="room.status === 'disponible'"
                                            @click="openCheckInModal(room)"
                                            class="px-3 py-1 rounded-full bg-[#A64B35] text-white text-xs font-bold"
                                        >
                                            Check-in
                                        </button>
                                        <button
                                            v-else-if="room.status === 'ocupada'"
                                            @click="openCheckOutModal(room.id)"
                                            class="px-3 py-1 rounded-full bg-gray-700 text-white text-xs font-bold"
                                        >
                                            Check-out y Factura
                                        </button>
                                        <button @click="openDeleteModal(room.id)" class="px-3 py-1 rounded-xl border border-red-300 text-red-600 text-xs font-bold">Eliminar</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="checkInModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="checkInModalOpen = false"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-gray-100 shadow-sm bg-white p-6">
                <h3 class="text-lg font-bold text-gray-800">Check-in de huésped</h3>
                <p class="text-sm text-gray-500 mt-1">Introduce el email para activar la sesión de la habitación.</p>
                <form @submit.prevent="submitCheckIn" class="mt-4 space-y-3">
                    <input
                        v-model="checkInForm.guest_email"
                        type="email"
                        required
                        placeholder="huesped@email.com"
                        class="w-full rounded-xl border-gray-300 focus:border-[#A64B35] focus:ring-[#A64B35]"
                    >
                    <p v-if="checkInForm.errors.guest_email" class="text-xs text-red-600">{{ checkInForm.errors.guest_email }}</p>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="checkInModalOpen = false" class="px-4 py-2 rounded-xl border border-gray-200 text-gray-600 text-sm font-semibold">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="checkInForm.processing" class="px-4 py-2 rounded-full bg-[#A64B35] text-white text-sm font-semibold">
                            Confirmar check-in
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="checkOutModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="checkOutModalOpen = false"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-gray-100 shadow-sm bg-white p-6">
                <h3 class="text-lg font-bold text-gray-800">Confirmar check-out</h3>
                <p class="text-sm text-gray-500 mt-1">Se cerrará la estancia y se descargará automáticamente la factura PDF.</p>
                <div class="flex justify-end gap-2 pt-5">
                    <button type="button" @click="checkOutModalOpen = false" class="px-4 py-2 rounded-xl border border-gray-200 text-gray-600 text-sm font-semibold">
                        Cancelar
                    </button>
                    <button type="button" @click="checkOutAndInvoice" class="px-4 py-2 rounded-full bg-gray-700 text-white text-sm font-semibold">
                        Confirmar check-out
                    </button>
                </div>
            </div>
        </div>

        <div v-if="deleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="deleteModalOpen = false"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-gray-100 shadow-sm bg-white p-6">
                <h3 class="text-lg font-bold text-gray-800">Eliminar habitación</h3>
                <p class="text-sm text-gray-500 mt-1">Esta acción no se puede deshacer.</p>
                <div class="flex justify-end gap-2 pt-5">
                    <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 rounded-xl border border-gray-200 text-gray-600 text-sm font-semibold">
                        Cancelar
                    </button>
                    <button type="button" @click="removeRoom" class="px-4 py-2 rounded-full bg-red-600 text-white text-sm font-semibold">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
