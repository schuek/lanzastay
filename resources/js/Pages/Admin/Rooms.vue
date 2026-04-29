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

const removeRoom = (roomId) => {
    if (confirm('Seguro que deseas eliminar esta habitación?')) {
        router.delete(route('rooms.destroy', roomId));
    }
};

const checkIn = (roomId) => router.post(route('rooms.checkin', roomId));
const checkOut = (roomId) => router.post(route('rooms.checkout', roomId));
const finalizeStay = (roomId) => {
    if (confirm('Se enviará la factura final al cliente y se cerrará la estancia. ¿Continuar?')) {
        router.post(route('rooms.finalize-stay', roomId));
    }
};
</script>

<template>
    <Head title="Habitaciones" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-[#1A1A1A] leading-tight">Gestión de Habitaciones</h2>
        </template>

        <div class="py-10 bg-[#F5F5F5] min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white rounded-xl border border-[#1A1A1A]/10 shadow-sm p-6">
                    <h3 class="font-bold text-[#1A1A1A] mb-4">Añadir Habitación</h3>
                    <form @submit.prevent="submitCreate" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <input v-model="createForm.number" type="text" placeholder="Numero habitacion" class="rounded-lg border-[#1A1A1A]/20 focus:border-[#D45D3B] focus:ring-[#D45D3B]">
                        <select v-model="createForm.status" class="rounded-lg border-[#1A1A1A]/20 focus:border-[#D45D3B] focus:ring-[#D45D3B]">
                            <option value="disponible">Disponible</option>
                            <option value="ocupada">Ocupada</option>
                            <option value="mantenimiento">Mantenimiento</option>
                        </select>
                        <button type="submit" class="bg-[#D45D3B] text-white rounded-lg font-bold px-4 py-2 hover:opacity-90">Crear</button>
                    </form>
                </div>

                <div class="bg-white rounded-xl border border-[#1A1A1A]/10 shadow-sm overflow-hidden">
                    <table class="min-w-full">
                        <thead class="bg-[#1A1A1A] text-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs uppercase tracking-wide">Habitación</th>
                                <th class="px-4 py-3 text-left text-xs uppercase tracking-wide">Estado</th>
                                <th class="px-4 py-3 text-left text-xs uppercase tracking-wide">Sesión</th>
                                <th class="px-4 py-3 text-right text-xs uppercase tracking-wide">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="room in rooms" :key="room.id" class="border-t border-[#1A1A1A]/10">
                                <td class="px-4 py-3">
                                    <input v-if="editRoomId === room.id" v-model="editForm.number" class="rounded-md border-[#1A1A1A]/20">
                                    <span v-else class="font-bold text-[#1A1A1A]">{{ room.numero }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <select v-if="editRoomId === room.id" v-model="editForm.status" class="rounded-md border-[#1A1A1A]/20">
                                        <option value="disponible">Disponible</option>
                                        <option value="ocupada">Ocupada</option>
                                        <option value="mantenimiento">Mantenimiento</option>
                                    </select>
                                    <span v-else class="text-sm font-bold" :class="room.status === 'ocupada' ? 'text-[#D45D3B]' : 'text-[#1A1A1A]/70'">{{ room.status }}</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-[#1A1A1A]/60">{{ room.current_session_token ? 'Activa' : 'Sin sesión' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <button v-if="editRoomId === room.id" @click="saveEdit(room.id)" class="px-3 py-1 rounded-md bg-[#D45D3B] text-white text-xs font-bold">Guardar</button>
                                        <button v-else @click="startEdit(room)" class="px-3 py-1 rounded-md bg-[#1A1A1A] text-white text-xs font-bold">Editar</button>
                                        <button @click="checkIn(room.id)" class="px-3 py-1 rounded-md bg-[#1A1A1A] text-white text-xs font-bold">Check-in</button>
                                        <button @click="checkOut(room.id)" class="px-3 py-1 rounded-md bg-[#D45D3B] text-white text-xs font-bold">Check-out</button>
                                        <button @click="finalizeStay(room.id)" class="px-3 py-1 rounded-md bg-emerald-600 text-white text-xs font-bold">Finalizar Estancia</button>
                                        <button @click="removeRoom(room.id)" class="px-3 py-1 rounded-md border border-red-300 text-red-600 text-xs font-bold">Eliminar</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
