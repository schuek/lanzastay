<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    activities: Array,
    reservations: Array,
    activeTab: {
        type: String,
        default: 'management',
    },
});

const tab = ref(props.activeTab);
const editingId = ref(null);

const form = useForm({
    name: '',
    description: '',
    type: 'hotel_activity',
    date_time: '',
    price: '',
    max_seats: 1,
    image_url: '',
});

const startEdit = (activity) => {
    editingId.value = activity.id;
    form.name = activity.name;
    form.description = activity.description ?? '';
    form.type = activity.type;
    form.date_time = toDateTimeInput(activity.date_time);
    form.price = activity.price;
    form.max_seats = activity.max_seats;
    form.image_url = activity.image_url ?? '';
};

const resetForm = () => {
    editingId.value = null;
    form.reset();
    form.type = 'hotel_activity';
    form.max_seats = 1;
};

const submitForm = () => {
    if (editingId.value) {
        form.put(route('activities.update', editingId.value), {
            onSuccess: resetForm,
        });
        return;
    }

    form.post(route('activities.store'), {
        onSuccess: resetForm,
    });
};

const removeActivity = (id) => {
    if (!confirm('¿Seguro que deseas eliminar esta actividad?')) return;
    router.delete(route('activities.destroy', id));
};

const setReservationStatus = (reservationId, status) => {
    router.put(route('activity-reservations.update-status', reservationId), { status });
};

const busTours = computed(() => props.activities.filter((item) => item.type === 'bus_tour'));
const hotelActivities = computed(() => props.activities.filter((item) => item.type === 'hotel_activity'));

const formatPrice = (value) => new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value || 0);
const formatDateTime = (value) => new Date(value).toLocaleString('es-ES');
const toDateTimeInput = (value) => {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};
</script>

<template>
    <Head title="Actividades y Reservas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-[#2F2A26] leading-tight">Actividades y Reservas</h2>
        </template>

        <div class="py-10 bg-[#FFFFFF] min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="flex gap-2">
                    <button @click="tab = 'management'" :class="tab === 'management' ? 'bg-[#A64B35] text-white' : 'bg-white text-[#2F2A26]'" class="px-4 py-2 rounded-lg border border-[#2F2A26]/15 font-bold">
                        Gestión
                    </button>
                    <button @click="tab = 'reception'" :class="tab === 'reception' ? 'bg-[#A64B35] text-white' : 'bg-white text-[#2F2A26]'" class="px-4 py-2 rounded-lg border border-[#2F2A26]/15 font-bold">
                        Recepción
                    </button>
                </div>

                <div v-if="tab === 'management'" class="space-y-6">
                    <div class="bg-white rounded-xl border border-[#2F2A26]/10 shadow-sm p-6">
                        <h3 class="font-bold text-[#2F2A26] mb-4">{{ editingId ? 'Editar actividad' : 'Alta de actividad o excursión' }}</h3>
                        <form @submit.prevent="submitForm" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <input v-model="form.name" type="text" placeholder="Nombre" class="rounded-lg border-[#2F2A26]/20 focus:border-[#A64B35] focus:ring-[#A64B35]">
                            <select v-model="form.type" class="rounded-lg border-[#2F2A26]/20 focus:border-[#A64B35] focus:ring-[#A64B35]">
                                <option value="hotel_activity">Actividad del hotel</option>
                                <option value="bus_tour">Excursión en bus</option>
                            </select>
                            <input v-model="form.date_time" type="datetime-local" class="rounded-lg border-[#2F2A26]/20 focus:border-[#A64B35] focus:ring-[#A64B35]">
                            <input v-model="form.price" type="number" min="0" step="0.01" placeholder="Precio" class="rounded-lg border-[#2F2A26]/20 focus:border-[#A64B35] focus:ring-[#A64B35]">
                            <input v-model="form.max_seats" type="number" min="1" step="1" placeholder="Plazas máximas" class="rounded-lg border-[#2F2A26]/20 focus:border-[#A64B35] focus:ring-[#A64B35]">
                            <input v-model="form.image_url" type="url" placeholder="URL imagen (opcional)" class="rounded-lg border-[#2F2A26]/20 focus:border-[#A64B35] focus:ring-[#A64B35]">
                            <textarea v-model="form.description" rows="3" placeholder="Descripción" class="md:col-span-2 rounded-lg border-[#2F2A26]/20 focus:border-[#A64B35] focus:ring-[#A64B35]"></textarea>
                            <div class="md:col-span-2 flex gap-2">
                                <button type="submit" class="bg-[#A64B35] text-white rounded-lg font-bold px-4 py-2 hover:opacity-90">
                                    {{ editingId ? 'Guardar cambios' : 'Crear' }}
                                </button>
                                <button v-if="editingId" type="button" @click="resetForm" class="bg-white text-[#2F2A26] border border-[#2F2A26]/20 rounded-lg font-bold px-4 py-2">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="bg-white rounded-xl border border-[#2F2A26]/10 shadow-sm overflow-hidden">
                        <table class="min-w-full">
                            <thead class="bg-[#2F2A26] text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs uppercase">Nombre</th>
                                    <th class="px-4 py-3 text-left text-xs uppercase">Tipo</th>
                                    <th class="px-4 py-3 text-left text-xs uppercase">Fecha</th>
                                    <th class="px-4 py-3 text-left text-xs uppercase">Precio</th>
                                    <th class="px-4 py-3 text-left text-xs uppercase">Plazas</th>
                                    <th class="px-4 py-3 text-right text-xs uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="activity in activities" :key="activity.id" class="border-t border-[#2F2A26]/10">
                                    <td class="px-4 py-3 font-bold text-[#2F2A26]">{{ activity.name }}</td>
                                    <td class="px-4 py-3 text-sm text-[#2F2A26]/70">{{ activity.type === 'bus_tour' ? 'Bus' : 'Hotel' }}</td>
                                    <td class="px-4 py-3 text-sm text-[#2F2A26]/70">{{ formatDateTime(activity.date_time) }}</td>
                                    <td class="px-4 py-3 text-sm font-bold text-[#A64B35]">{{ formatPrice(activity.price) }}</td>
                                    <td class="px-4 py-3 text-sm text-[#2F2A26]/70">{{ activity.max_seats }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <button @click="startEdit(activity)" class="px-3 py-1 rounded-md bg-[#2F2A26] text-white text-xs font-bold">Editar</button>
                                            <button @click="removeActivity(activity.id)" class="px-3 py-1 rounded-md border border-red-300 text-red-600 text-xs font-bold">Eliminar</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div class="bg-white border border-[#2F2A26]/10 rounded-xl p-4">
                            <h4 class="font-bold text-[#2F2A26] mb-3">Excursiones (Bus)</h4>
                            <ul class="space-y-2 text-sm text-[#2F2A26]/80">
                                <li v-for="item in busTours" :key="item.id">{{ item.name }} - {{ formatDateTime(item.date_time) }}</li>
                            </ul>
                        </div>
                        <div class="bg-white border border-[#2F2A26]/10 rounded-xl p-4">
                            <h4 class="font-bold text-[#2F2A26] mb-3">Actividades Hotel</h4>
                            <ul class="space-y-2 text-sm text-[#2F2A26]/80">
                                <li v-for="item in hotelActivities" :key="item.id">{{ item.name }} - {{ formatDateTime(item.date_time) }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div v-else class="bg-white rounded-xl border border-[#2F2A26]/10 shadow-sm overflow-hidden">
                    <table class="min-w-full">
                        <thead class="bg-[#2F2A26] text-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs uppercase">Habitación</th>
                                <th class="px-4 py-3 text-left text-xs uppercase">Reserva</th>
                                <th class="px-4 py-3 text-left text-xs uppercase">Plazas</th>
                                <th class="px-4 py-3 text-left text-xs uppercase">Total</th>
                                <th class="px-4 py-3 text-left text-xs uppercase">Estado</th>
                                <th class="px-4 py-3 text-right text-xs uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="reservation in reservations" :key="reservation.id" class="border-t border-[#2F2A26]/10">
                                <td class="px-4 py-3 font-bold text-[#2F2A26]">{{ reservation.room?.numero ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-[#2F2A26]/80">
                                    {{ reservation.activity?.name ?? '-' }}
                                    <span class="block text-xs text-[#2F2A26]/50">{{ reservation.activity?.type === 'bus_tour' ? 'Bus' : 'Hotel' }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-[#2F2A26]/70">{{ reservation.seats_booked }}</td>
                                <td class="px-4 py-3 text-sm font-bold text-[#A64B35]">{{ formatPrice(reservation.total_price) }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-xs px-2 py-1 rounded-full bg-[#A64B35]/10 text-[#A64B35] font-bold uppercase">{{ reservation.status }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <button @click="setReservationStatus(reservation.id, 'confirmada')" class="px-3 py-1 rounded-md bg-[#A64B35] text-white text-xs font-bold">Confirmar</button>
                                        <button @click="setReservationStatus(reservation.id, 'cancelada')" class="px-3 py-1 rounded-md border border-red-300 text-red-600 text-xs font-bold">Cancelar</button>
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
