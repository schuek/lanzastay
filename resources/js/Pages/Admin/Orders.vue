<script setup>
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ClockIcon,
    CheckCircleIcon,
    FireIcon,
    TruckIcon,
    SignalIcon
} from '@heroicons/vue/24/solid';

const props = defineProps({
    orders: Array
});

const reactiveOrders = ref([...props.orders]);
const bellSound = new Audio('https://cdn.freesound.org/previews/337/337049_3232293-lq.mp3');

onMounted(() => {
    window.Echo.channel('orders')
        .listen('OrderCreated', (e) => {
            reactiveOrders.value.unshift(e.order);
            bellSound.play().catch(err => console.log("Audio bloqueado", err));
        });
});

const formatPrice = (value) => new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value);
const formatDate = (dateString) => new Date(dateString).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });

const updateStatus = (id, newStatus) => {
    router.patch(route('orders.updateStatus', id), {
        status: newStatus
    }, {
        onSuccess: () => {
            const orderIndex = reactiveOrders.value.findIndex(o => o.id === id);
            if(orderIndex !== -1) {
                 reactiveOrders.value[orderIndex].status = newStatus;
                 if(newStatus === 'completed') {
                     reactiveOrders.value.splice(orderIndex, 1);
                 }
            }
        }
    });
};
</script>

<template>
    <Head title="Pedidos en Cocina" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                Panel de Cocina en Directo
                <SignalIcon class="w-6 h-6 text-red-500 animate-pulse" />
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                    <div v-for="order in reactiveOrders" :key="order.id" class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 flex flex-col animate-in fade-in slide-in-from-bottom-4 duration-500">

                        <div class="p-4 bg-gray-800 text-white flex justify-between items-center">
                            <div>
                                <span class="text-xs uppercase tracking-widest text-gray-400">Habitación</span>
                                <h3 class="text-2xl font-black">{{ order.room_number }}</h3>
                            </div>
                            <div class="text-right flex flex-col items-end">
                                <span class="text-xs uppercase tracking-widest text-gray-400">Hora</span>
                                <p class="font-bold flex items-center gap-1">
                                    <ClockIcon class="w-4 h-4 text-gray-400" />
                                    {{ formatDate(order.created_at) }}
                                </p>
                            </div>
                        </div>

                        <div class="p-4 flex-grow">
                            <ul class="divide-y divide-gray-100">
                                <li v-for="service in order.services" :key="service.id" class="py-2 flex justify-between items-center">
                                    <span class="text-gray-800 font-bold">
                                        {{ service.pivot.quantity }}x {{ service.name }}
                                    </span>
                                    <span class="text-gray-500 text-sm">{{ formatPrice(service.price) }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="p-4 bg-gray-50 border-t">
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-gray-500 font-medium">Total:</span>
                                <span class="text-xl font-black text-gray-900">{{ formatPrice(order.total) }}</span>
                            </div>

                            <div class="flex flex-col gap-2">
                                <button
                                    v-if="order.status === 'pending'"
                                    @click="updateStatus(order.id, 'preparing')"
                                    class="w-full bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-lg font-bold flex items-center justify-center gap-2 transition"
                                >
                                    <FireIcon class="w-5 h-5" />
                                    <span>Empezar a preparar</span>
                                </button>

                                <button
                                    v-if="order.status === 'preparing'"
                                    @click="updateStatus(order.id, 'delivering')"
                                    class="w-full bg-blue-500 hover:bg-blue-600 text-white py-3 rounded-lg font-bold flex items-center justify-center gap-2 transition"
                                >
                                    <TruckIcon class="w-5 h-5" />
                                    <span>Enviar a habitación</span>
                                </button>

                                <button
                                    v-if="order.status === 'delivering'"
                                    @click="updateStatus(order.id, 'completed')"
                                    class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-bold flex items-center justify-center gap-2 transition"
                                >
                                    <CheckCircleIcon class="w-5 h-5" />
                                    <span>Entregado</span>
                                </button>

                                <div class="text-center mt-2">
                                    <span class="text-[10px] uppercase font-black tracking-tighter text-gray-400">
                                        Estado actual: {{ order.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div v-if="reactiveOrders.length === 0" class="text-center py-20 bg-white rounded-xl shadow-sm border-2 border-dashed border-gray-200">
                    <CheckCircleIcon class="w-16 h-16 mx-auto text-green-200 mb-4" />
                    <h3 class="text-lg font-bold text-gray-900">¡Todo despejado!</h3>
                    <p class="text-gray-500">No hay pedidos pendientes en este momento.</p>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
