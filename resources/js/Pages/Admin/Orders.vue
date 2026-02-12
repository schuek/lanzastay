<script setup>
import { ref, onMounted, onUnmounted } from 'vue'; // Asegúrate de importar esto
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ClockIcon, CheckCircleIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    orders: Array
});

// --- SONIDO DE NOTIFICACIÓN 🔔 ---
// Usamos un sonido online de "campanilla de servicio"
const bellSound = new Audio('https://cdn.freesound.org/previews/337/337049_3232293-lq.mp3');

// Variable para recordar cuántos pedidos teníamos antes
const previousOrderCount = ref(props.orders.length);

// --- AUTO-REFRESCO (POLLING) ---
let intervalId = null;

onMounted(() => {
    intervalId = setInterval(() => {
        router.reload({
            only: ['orders'], // Solo recargamos la lista de pedidos
            preserveScroll: true,
            preserveState: true,
            onSuccess: (page) => {
                // Al terminar de recargar, comprobamos si ha entrado algo nuevo
                const newCount = page.props.orders.length;

                if (newCount > previousOrderCount.value) {
                    // ¡HAY PEDIDOS NUEVOS!
                    console.log("¡Ding! Nuevo pedido entrante 🛎️");

                    // Reproducimos el sonido (capturamos error por si el navegador bloquea el audio)
                    bellSound.play().catch(error => {
                        console.warn("El navegador bloqueó el sonido. Haz clic en la página para habilitarlo.");
                    });
                }

                // Actualizamos el contador para la próxima vez
                previousOrderCount.value = newCount;
            }
        });
    }, 5000); // Cada 5 segundos
});

onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
});

// Formateo de moneda
const formatPrice = (value) => new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value);

// Completar pedido
const completeOrder = (id) => {
    if (confirm('¿Pedido entregado?')) {
        router.post(route('order.complete', id));
    }
};
</script>

<template>
    <Head title="Pedidos en Cocina" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pedidos</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                    <div v-for="order in orders" :key="order.id"
                        class="bg-white rounded-xl shadow-lg border-l-4 overflow-hidden relative"
                        :class="order.status === 'pending' ? 'border-yellow-400' : 'border-green-500 opacity-75'"
                    >
                        <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                            <div>
                                <h3 class="font-bold text-lg text-gray-800">HABITACIÓN {{ order.room_number }}</h3>
                                <p class="text-xs text-gray-500">Hora: {{ formatDate(order.created_at) }}</p>
                            </div>

                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide"
                                :class="order.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'">
                                {{ order.status === 'pending' ? 'Pendiente' : 'Servido' }}
                            </span>
                        </div>

                        <div class="p-4 space-y-3">
                            <ul class="divide-y divide-gray-100">
                                <li v-for="service in order.services" :key="service.id" class="py-2 flex justify-between">
                                    <span class="text-gray-700 font-medium">1x {{ service.name }}</span>
                                    </li>
                            </ul>
                        </div>

                        <div class="p-4 bg-gray-50 border-t flex justify-between items-center">
                            <span class="font-bold text-xl text-gray-900">{{ formatPrice(order.total_price) }}</span>

                            <button
                                @click="toggleStatus(order)"
                                class="flex items-center gap-2 px-4 py-2 rounded-lg text-white font-bold transition shadow-sm"
                                :class="order.status === 'pending'
                                    ? 'bg-slate-900 hover:bg-green-600'
                                    : 'bg-gray-400 hover:bg-yellow-500'"
                            >
                                <component :is="order.status === 'pending' ? CheckCircleIcon : ClockIcon" class="w-5 h-5" />
                                {{ order.status === 'pending' ? 'Servir' : 'Reabrir' }}
                            </button>
                        </div>
                    </div>

                </div>

                <div v-if="orders.length === 0" class="text-center py-20 bg-white rounded-lg shadow-sm border border-dashed border-gray-300">
                    <ClockIcon class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                    <h3 class="text-lg font-medium text-gray-900">Sin pedidos pendientes</h3>
                    <p class="text-gray-500">Todo tranquilo por aquí...</p>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
