<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import {
    ShoppingCartIcon,
    XMarkIcon,
    CheckCircleIcon,
    ExclamationCircleIcon,
    PhoneIcon,
    ClipboardDocumentListIcon,
} from '@heroicons/vue/24/solid';

const props = defineProps({
    services: Array,
    myOrders: Array,
    currentRoom: String,
    currentRoomId: Number,
    sessionToken: String,
});

const notification = ref(null);
const currentView = ref('services');
const selectedServiceType = ref(null);
const cart = ref([]);
const isCartOpen = ref(false);
const requestedTime = ref('');
const maintenanceDescription = ref('');
const reactiveOrders = ref([...props.myOrders]);

const availableHours = [
    '08:00', '09:00', '10:00', '11:00', '12:00',
    '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'
];

onMounted(() => {
    reactiveOrders.value.forEach((order) => {
        window.Echo.channel(`order.${order.id}`)
            .listen('OrderStatusUpdated', (e) => {
                const index = reactiveOrders.value.findIndex((o) => o.id === e.order.id);
                if (index !== -1) {
                    reactiveOrders.value[index].status = e.order.status;
                    showNotification('Tu solicitud ha cambiado de estado.', 'success');
                }
            });
    });
});

const showNotification = (message, type = 'success') => {
    notification.value = { message, type };
    setTimeout(() => {
        notification.value = null;
    }, 3000);
};

const totalPrice = computed(() => cart.value.reduce((acc, item) => acc + (item.price * item.quantity), 0));
const foodServices = computed(() => {
    return (props.services ?? []).filter((service) => {
        const explicitType = (service.service_type ?? '').toString().toLowerCase();
        const categoryName = (service.category?.name ?? '').toString().toLowerCase();

        return explicitType === 'comida' || categoryName.includes('comida') || categoryName.includes('restaurante');
    });
});

const addToCart = (service) => {
    const existing = cart.value.find((item) => item.id === service.id);
    if (existing) {
        existing.quantity += 1;
    } else {
        cart.value.push({ ...service, quantity: 1 });
    }
    isCartOpen.value = true;
};

const increaseQty = (item) => item.quantity++;
const decreaseQty = (item) => {
    if (item.quantity > 1) item.quantity--;
    else cart.value = cart.value.filter((i) => i.id !== item.id);
};

const resetRequestForms = () => {
    requestedTime.value = '';
    maintenanceDescription.value = '';
};

const submitOrder = () => {
    if (cart.value.length === 0) {
        showNotification('Añade al menos un producto.', 'error');
        return;
    }

    const payloadCart = cart.value.map((item) => ({
        id: item.id,
        quantity: item.quantity,
        price: item.price,
    }));

    const payload = {
        room_number: props.currentRoom,
        habitacion_id: props.currentRoomId,
        session_token: props.sessionToken,
        service_type: 'comida',
        total: totalPrice.value,
        cart: payloadCart,
    };

    console.log('[LanzaStay] submitOrder payload:', payload);

    axios.post('/api/orders', payload).then((response) => {
        if (response.data?.order) {
            reactiveOrders.value.unshift(response.data.order);
            router.visit(route('orders.tracking', response.data.order.id));
        }
        cart.value = [];
        isCartOpen.value = false;
        showNotification('Pedido de comida enviado.', 'success');
    }).catch((error) => {
        console.error('[LanzaStay] submitOrder error:', error?.response?.data ?? error);
        showNotification('No se pudo enviar el pedido.', 'error');
    });
};

const submitCleaningRequest = () => {
    if (!requestedTime.value) {
        showNotification('Selecciona una hora para limpieza.', 'error');
        return;
    }

    axios.post('/api/orders', {
        room_number: props.currentRoom,
        habitacion_id: props.currentRoomId,
        session_token: props.sessionToken,
        service_type: 'limpieza',
        requested_time: requestedTime.value,
    }).then((response) => {
        if (response.data?.order) {
            router.visit(route('orders.tracking', response.data.order.id));
        }
            resetRequestForms();
        showNotification('Solicitud de limpieza enviada.', 'success');
    }).catch(() => {
        showNotification('No se pudo enviar la solicitud.', 'error');
    });
};

const submitMaintenanceRequest = () => {
    if (!maintenanceDescription.value.trim()) {
        showNotification('Describe la avería para continuar.', 'error');
        return;
    }

    axios.post('/api/orders', {
        room_number: props.currentRoom,
        habitacion_id: props.currentRoomId,
        session_token: props.sessionToken,
        service_type: 'mantenimiento',
        description: maintenanceDescription.value.trim(),
    }).then((response) => {
        if (response.data?.order) {
            router.visit(route('orders.tracking', response.data.order.id));
        }
            resetRequestForms();
        showNotification('Reporte de mantenimiento enviado.', 'success');
    }).catch(() => {
        showNotification('No se pudo enviar el reporte.', 'error');
    });
};

const formatPrice = (value) => new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value);
</script>

<template>
    <Head title="Conserje Virtual" />

    <div class="min-h-screen bg-[#F5F5F5] relative pb-24">
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="notification" class="fixed top-5 right-5 z-50 max-w-sm w-full bg-white shadow-lg rounded-lg ring-1 ring-[#1A1A1A]/10 overflow-hidden">
                <div class="p-4 flex items-center">
                    <CheckCircleIcon v-if="notification.type === 'success'" class="h-6 w-6 text-[#D45D3B]" />
                    <ExclamationCircleIcon v-else class="h-6 w-6 text-red-500" />
                    <p class="ml-3 text-sm font-medium text-[#1A1A1A]">{{ notification.message }}</p>
                </div>
            </div>
        </Transition>

        <div class="bg-white shadow-sm sticky top-0 z-30 border-b border-[#1A1A1A]/10">
            <div class="max-w-md mx-auto px-4 py-4 flex justify-between items-center">
                <span class="text-xl font-bold text-[#1A1A1A]">Habitación {{ currentRoom }}</span>
                <span class="text-[#D45D3B] font-black tracking-tighter text-xl">LANZASTAY</span>
            </div>
            <div class="max-w-md mx-auto px-4 pb-3 flex gap-2">
                <button @click="currentView = 'services'" :class="currentView === 'services' ? 'bg-[#D45D3B] text-white' : 'bg-white text-[#1A1A1A]'" class="flex-1 py-2 rounded-lg border border-[#1A1A1A]/10 font-bold text-sm">
                    Solicitar servicio
                </button>
                <button @click="currentView = 'orders'" :class="currentView === 'orders' ? 'bg-[#D45D3B] text-white' : 'bg-white text-[#1A1A1A]'" class="flex-1 py-2 rounded-lg border border-[#1A1A1A]/10 font-bold text-sm">
                    Mis peticiones
                </button>
            </div>
        </div>

        <div class="max-w-md mx-auto px-4 py-6">
            <div v-if="currentView === 'services'" class="space-y-6">
                <div v-if="!selectedServiceType" class="grid grid-cols-1 gap-3">
                    <button @click="selectedServiceType = 'comida'" class="bg-white rounded-xl p-4 border border-[#1A1A1A]/10 text-left">
                        <p class="text-lg font-black text-[#1A1A1A]">Pedir Comida</p>
                        <p class="text-sm text-[#1A1A1A]/70">Elige platos y bebidas del menú.</p>
                    </button>
                    <button @click="selectedServiceType = 'limpieza'" class="bg-white rounded-xl p-4 border border-[#1A1A1A]/10 text-left">
                        <p class="text-lg font-black text-[#1A1A1A]">Solicitar Limpieza</p>
                        <p class="text-sm text-[#1A1A1A]/70">Programa la hora de paso del personal.</p>
                    </button>
                    <button @click="selectedServiceType = 'mantenimiento'" class="bg-white rounded-xl p-4 border border-[#1A1A1A]/10 text-left">
                        <p class="text-lg font-black text-[#1A1A1A]">Reportar Mantenimiento</p>
                        <p class="text-sm text-[#1A1A1A]/70">Describe una avería o incidencia técnica.</p>
                    </button>
                </div>

                <div v-if="selectedServiceType === 'comida'" class="space-y-4">
                    <button @click="selectedServiceType = null" class="text-sm font-bold text-[#D45D3B]">Volver</button>
                    <div class="space-y-3">
                        <div v-for="service in foodServices" :key="service.id" class="bg-white rounded-xl p-4 border border-[#1A1A1A]/10">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-bold text-[#1A1A1A]">{{ service.name }}</h3>
                                <span class="font-bold text-[#D45D3B]">{{ formatPrice(service.price) }}</span>
                            </div>
                            <p class="text-sm text-[#1A1A1A]/70 mb-3">{{ service.description }}</p>
                            <button @click="addToCart(service)" class="bg-[#1A1A1A] hover:bg-[#D45D3B] text-white px-3 py-2 rounded-lg text-sm font-bold">
                                Anadir
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="selectedServiceType === 'limpieza'" class="bg-white rounded-xl p-4 border border-[#1A1A1A]/10">
                    <button @click="selectedServiceType = null" class="text-sm font-bold text-[#D45D3B] mb-3">Volver</button>
                    <h3 class="text-lg font-black text-[#1A1A1A] mb-2">Horario de limpieza</h3>
                    <select v-model="requestedTime" class="w-full rounded-lg border-[#1A1A1A]/20 mb-4">
                        <option value="">Selecciona una hora</option>
                        <option v-for="hour in availableHours" :key="hour" :value="hour">{{ hour }}</option>
                    </select>
                    <button @click="submitCleaningRequest" class="w-full bg-[#D45D3B] text-white rounded-lg py-3 font-bold">Enviar solicitud</button>
                </div>

                <div v-if="selectedServiceType === 'mantenimiento'" class="bg-white rounded-xl p-4 border border-[#1A1A1A]/10">
                    <button @click="selectedServiceType = null" class="text-sm font-bold text-[#D45D3B] mb-3">Volver</button>
                    <h3 class="text-lg font-black text-[#1A1A1A] mb-2">Describe la averia</h3>
                    <textarea v-model="maintenanceDescription" rows="5" class="w-full rounded-lg border-[#1A1A1A]/20 mb-4" placeholder="Ejemplo: el aire acondicionado no enfria."></textarea>
                    <button @click="submitMaintenanceRequest" class="w-full bg-[#D45D3B] text-white rounded-lg py-3 font-bold">Enviar reporte</button>
                </div>
            </div>

            <div v-if="currentView === 'orders'" class="space-y-4">
                <div v-if="reactiveOrders.length === 0" class="bg-white rounded-xl p-6 border border-[#1A1A1A]/10 text-center text-[#1A1A1A]/70">
                    No tienes solicitudes registradas.
                </div>
                <div v-for="order in reactiveOrders" :key="order.id" class="bg-white rounded-xl p-4 border border-[#1A1A1A]/10">
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-bold text-[#1A1A1A]">#{{ order.id }} - {{ order.service_type }}</span>
                        <span class="text-xs px-2 py-1 rounded-full bg-[#D45D3B]/10 text-[#D45D3B] font-bold uppercase">{{ order.status }}</span>
                    </div>
                    <p v-if="order.service_type === 'limpieza'" class="text-sm text-[#1A1A1A]/70">Hora solicitada: {{ order.requested_time }}</p>
                    <p v-if="order.service_type === 'mantenimiento'" class="text-sm text-[#1A1A1A]/70">{{ order.description }}</p>
                    <ul v-if="order.service_type === 'comida'" class="text-sm text-[#1A1A1A]/70 mb-2">
                        <li v-for="service in order.services" :key="service.id">{{ service.pivot.quantity }}x {{ service.name }}</li>
                    </ul>
                    <p v-if="order.service_type === 'comida'" class="font-bold text-[#D45D3B]">{{ formatPrice(order.total_price) }}</p>
                </div>
            </div>
        </div>

        <a href="https://wa.me/123456789" target="_blank" class="fixed bottom-6 right-6 bg-[#D45D3B] text-white px-4 py-3 rounded-full shadow-xl font-bold z-40 flex items-center gap-2">
            <PhoneIcon class="w-5 h-5" />
            SOS
        </a>

        <button v-if="cart.length > 0" @click="isCartOpen = !isCartOpen" class="fixed bottom-20 right-6 bg-[#1A1A1A] text-white p-4 rounded-full shadow-xl z-40">
            <ShoppingCartIcon class="w-6 h-6" />
        </button>

        <div v-if="isCartOpen" class="fixed inset-0 z-50 flex justify-end">
            <div @click="isCartOpen = false" class="absolute inset-0 bg-black/40"></div>
            <div class="relative w-full max-w-md bg-white h-full shadow-2xl p-6 flex flex-col">
                <div class="flex justify-between items-center mb-4 border-b pb-3">
                    <h2 class="text-xl font-black text-[#1A1A1A]">Pedido de comida</h2>
                    <button @click="isCartOpen = false"><XMarkIcon class="w-7 h-7 text-[#1A1A1A]/50" /></button>
                </div>
                <div class="flex-1 overflow-y-auto space-y-3">
                    <div v-for="item in cart" :key="item.id" class="bg-[#F5F5F5] rounded-lg p-3 flex justify-between items-center">
                        <div>
                            <p class="font-bold text-[#1A1A1A]">{{ item.name }}</p>
                            <p class="text-sm text-[#1A1A1A]/70">{{ formatPrice(item.price) }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="decreaseQty(item)" class="px-2 rounded bg-white border border-[#1A1A1A]/20">-</button>
                            <span class="font-bold">{{ item.quantity }}</span>
                            <button @click="increaseQty(item)" class="px-2 rounded bg-white border border-[#1A1A1A]/20">+</button>
                        </div>
                    </div>
                </div>
                <div class="border-t pt-4">
                    <div class="flex justify-between mb-3 font-bold">
                        <span>Total</span>
                        <span class="text-[#D45D3B]">{{ formatPrice(totalPrice) }}</span>
                    </div>
                    <button @click="submitOrder" class="w-full bg-[#1A1A1A] hover:bg-[#D45D3B] text-white rounded-lg py-3 font-bold">
                        Confirmar pedido
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
