<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    CakeIcon,
    SparklesIcon,
    WrenchScrewdriverIcon,
    ArrowLeftIcon,
    ShoppingCartIcon,
    XMarkIcon,
    CheckCircleIcon,
    ExclamationCircleIcon,
    FaceFrownIcon,
    MapPinIcon,
    BuildingStorefrontIcon,
    ClipboardDocumentListIcon,
    InboxIcon,
    FireIcon,
    TruckIcon
} from '@heroicons/vue/24/solid';

const props = defineProps({
    services: Array,
    categories: Array,
    myOrders: Array,
    currentRoom: String
});

// --- ESTADO ---
const activeTab = ref('services'); // Controla las 3 pestañas
const activeCategory = ref(null);
const cart = ref([]);
const isCartOpen = ref(false);
const notification = ref(null);

// --- GUÍA TURÍSTICA ---
const touristSpots = [
    {
        id: 1,
        name: 'Parque Nacional de Timanfaya',
        description: 'Descubre los paisajes marcianos y siente el calor de los volcanes en activo.',
        image: 'https://images.unsplash.com/photo-1610486001550-a92c0a9611e0?w=600&q=80',
        distance: 'Aprox. 30 min',
        mapUrl: 'https://www.google.com/maps/dir/?api=1&origin=Arrecife,+Lanzarote&destination=Parque+Nacional+de+Timanfaya'
    },
    {
        id: 2,
        name: 'Jameos del Agua',
        description: 'Un tubo volcánico transformado en un oasis subterráneo por César Manrique.',
        image: 'https://images.unsplash.com/photo-1579782527015-89dbad1c55bc?w=600&q=80',
        distance: 'Aprox. 35 min',
        mapUrl: 'https://www.google.com/maps/dir/?api=1&origin=Arrecife,+Lanzarote&destination=Jameos+del+Agua'
    },
    {
        id: 3,
        name: 'Playas de Papagayo',
        description: 'Calas de arena blanca y aguas cristalinas protegidas del viento.',
        image: 'https://images.unsplash.com/photo-1563630381190-77c336ea5459?w=600&q=80',
        distance: 'Aprox. 45 min',
        mapUrl: 'https://www.google.com/maps/dir/?api=1&origin=Arrecife,+Lanzarote&destination=Playa+de+Papagayo'
    }
];

// --- NOTIFICACIONES ---
const showNotification = (message, type = 'success') => {
    notification.value = { message, type };
    setTimeout(() => notification.value = null, 3000);
};

// --- LÓGICA DEL CARRITO ---
const addToCart = (service) => {
    const existingItem = cart.value.find(item => item.id === service.id);
    if (existingItem) {
        existingItem.quantity++;
        showNotification(`Añadida otra unidad de ${service.name}`, 'success');
    } else {
        cart.value.push({ ...service, quantity: 1 });
        showNotification(`${service.name} añadido al carrito`, 'success');
    }
    if (!isCartOpen.value) isCartOpen.value = true;
};

const increaseQty = (item) => item.quantity++;
const decreaseQty = (item) => {
    if (item.quantity > 1) item.quantity--;
    else cart.value = cart.value.filter(i => i.id !== item.id);
};

const totalPrice = computed(() => {
    return cart.value.reduce((acc, item) => acc + (item.price * item.quantity), 0);
});

// --- ENVIAR PEDIDO ---
const submitOrder = () => {
    if (cart.value.length === 0) return;
    const flatCart = [];
    cart.value.forEach(item => {
        for (let i = 0; i < item.quantity; i++) flatCart.push(item);
    });

    router.post(route('order.store'), {
        room_number: props.currentRoom,
        total: totalPrice.value,
        cart: flatCart
    }, {
        onSuccess: () => {
            cart.value = [];
            isCartOpen.value = false;
            showNotification('Pedido enviado correctamente', 'success');
            // Cambiamos automáticamente a la pestaña de "Mis Pedidos" para que lo vea
            activeTab.value = 'orders';
        },
        onError: () => showNotification('Hubo un error al procesar la solicitud', 'error')
    });
};

// --- UTILIDADES ---
const filteredServices = computed(() => {
    if (!activeCategory.value) return [];
    return props.services.filter(s => s.category_id === activeCategory.value.id);
});

const iconMap = {
    'CakeIcon': CakeIcon,
    'SparklesIcon': SparklesIcon,
    'WrenchScrewdriverIcon': WrenchScrewdriverIcon
};

const formatPrice = (value) => new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value);
</script>

<template>
    <Head title="Servicios de Habitación" />

    <div class="min-h-screen bg-gray-100 relative pb-24 font-sans">

        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="notification" class="fixed top-5 right-5 z-50 max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
                <div class="p-4 flex items-center">
                    <div class="flex-shrink-0">
                        <CheckCircleIcon v-if="notification.type === 'success'" class="h-6 w-6 text-green-500" />
                        <ExclamationCircleIcon v-else class="h-6 w-6 text-red-500" />
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-gray-900">{{ notification.message }}</p>
                    </div>
                </div>
                <div :class="notification.type === 'success' ? 'bg-green-500' : 'bg-red-500'" class="h-1 w-full animate-pulse"></div>
            </div>
        </Transition>

        <div class="bg-white shadow-sm sticky top-0 z-30">
            <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
                <span class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    Habitación {{ currentRoom }}
                </span>
                <div class="text-indigo-600 font-black tracking-tighter text-xl">LANZASTAY</div>
            </div>

            <div class="flex border-t border-gray-100 overflow-x-auto">
                <button
                    @click="activeTab = 'services'"
                    :class="{'border-indigo-600 text-indigo-600': activeTab === 'services', 'border-transparent text-gray-400 hover:text-gray-600': activeTab !== 'services'}"
                    class="flex-1 py-3 px-2 border-b-2 font-bold text-sm flex justify-center items-center gap-1 transition-colors whitespace-nowrap min-w-[110px]"
                >
                    <BuildingStorefrontIcon class="w-5 h-5" /> Servicios
                </button>
                <button
                    @click="activeTab = 'guide'"
                    :class="{'border-indigo-600 text-indigo-600': activeTab === 'guide', 'border-transparent text-gray-400 hover:text-gray-600': activeTab !== 'guide'}"
                    class="flex-1 py-3 px-2 border-b-2 font-bold text-sm flex justify-center items-center gap-1 transition-colors whitespace-nowrap min-w-[110px]"
                >
                    <MapPinIcon class="w-5 h-5" /> Guía Local
                </button>
                <button
                    @click="activeTab = 'orders'"
                    :class="{'border-indigo-600 text-indigo-600': activeTab === 'orders', 'border-transparent text-gray-400 hover:text-gray-600': activeTab !== 'orders'}"
                    class="flex-1 py-3 px-2 border-b-2 font-bold text-sm flex justify-center items-center gap-1 transition-colors whitespace-nowrap min-w-[110px]"
                >
                    <ClipboardDocumentListIcon class="w-5 h-5" /> Mis Pedidos
                </button>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-8">

            <div v-if="activeTab === 'services'" class="animate-in fade-in zoom-in duration-300">
                <button v-if="activeCategory" @click="activeCategory = null" class="mb-6 flex items-center text-gray-600 hover:text-indigo-600 font-bold transition">
                    <ArrowLeftIcon class="w-5 h-5 mr-1" /> Volver a categorías
                </button>

                <div v-if="!activeCategory">
                    <h2 class="text-2xl font-light text-center mb-8 text-gray-600">¿Qué desea pedir hoy?</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                        <button v-for="category in categories" :key="category.id" @click="activeCategory = category" class="bg-white p-8 rounded-2xl shadow-md hover:shadow-xl transition-all transform hover:-translate-y-1 flex flex-col items-center gap-4 border border-transparent hover:border-indigo-100">
                            <div class="bg-indigo-50 p-4 rounded-full text-indigo-600">
                                <component :is="iconMap[category.icon]" class="w-12 h-12" />
                            </div>
                            <span class="text-xl font-bold text-gray-800">{{ category.name }}</span>
                        </button>
                    </div>
                </div>

                <div v-else class="animate-in slide-in-from-right duration-300">
                    <div class="flex items-center gap-3 mb-6">
                        <component :is="iconMap[activeCategory.icon]" class="w-8 h-8 text-indigo-600" />
                        <h2 class="text-2xl font-bold text-gray-800">{{ activeCategory.name }}</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="service in filteredServices" :key="service.id" class="bg-white rounded-xl shadow-sm overflow-hidden flex flex-col h-full border border-gray-100">
                            <div class="p-5 flex-1">
                                <div class="flex justify-between items-start">
                                    <h3 class="font-bold text-lg text-gray-900">{{ service.name }}</h3>
                                    <span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded-full">{{ formatPrice(service.price) }}</span>
                                </div>
                                <p class="text-gray-500 text-sm mt-2 leading-relaxed">{{ service.description }}</p>
                            </div>
                            <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                                <button @click="addToCart(service)" class="bg-slate-900 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors active:scale-95 flex items-center gap-2">Añadir +</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'guide'" class="animate-in fade-in duration-300 space-y-8 max-w-2xl mx-auto">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-black text-gray-800">Descubre Lanzarote</h2>
                    <p class="text-gray-500 mt-2">Los rincones favoritos de nuestro equipo para que disfrutes de la isla.</p>
                </div>
                <div v-for="spot in touristSpots" :key="spot.id" class="bg-white rounded-2xl shadow-md overflow-hidden border border-gray-100 flex flex-col transform hover:scale-[1.02] transition-transform duration-300">
                    <img :src="spot.image" class="w-full h-56 object-cover" :alt="spot.name" />
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-3">
                            <h3 class="text-xl font-bold text-gray-900">{{ spot.name }}</h3>
                            <span class="bg-orange-100 text-orange-800 text-xs font-bold px-3 py-1 rounded-full whitespace-nowrap">{{ spot.distance }}</span>
                        </div>
                        <p class="text-gray-600 text-sm leading-relaxed mb-6">{{ spot.description }}</p>
                        <a :href="spot.mapUrl" target="_blank" class="block w-full text-center bg-indigo-50 text-indigo-600 font-bold py-3 rounded-xl hover:bg-indigo-100 transition shadow-sm">Abrir en Google Maps</a>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'orders'" class="animate-in fade-in duration-300 max-w-2xl mx-auto space-y-6">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-black text-gray-800">Tus Pedidos</h2>
                    <p class="text-gray-500 mt-2">Seguimiento de la habitación {{ currentRoom }}</p>
                </div>

                <div v-if="!myOrders || myOrders.length === 0" class="text-center py-12 bg-white rounded-2xl shadow-sm border border-gray-100">
                    <p class="text-gray-500">Aún no has pedido nada. ¡Anímate!</p>
                </div>

                <div v-for="order in myOrders" :key="order.id" class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 p-6">
                    <div class="flex justify-between items-center border-b pb-4 mb-4">
                        <span class="font-bold text-gray-800">Pedido #{{ order.id }}</span>

                        <span v-if="order.status === 'pending'" class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-bold uppercase flex items-center gap-1">
                            <InboxIcon class="w-3.5 h-3.5" /> Recibido
                        </span>
                        <span v-else-if="order.status === 'preparing'" class="bg-orange-100 text-orange-600 px-3 py-1 rounded-full text-xs font-bold uppercase flex items-center gap-1">
                            <FireIcon class="w-3.5 h-3.5" /> En marcha
                        </span>
                        <span v-else-if="order.status === 'delivering'" class="bg-blue-100 text-blue-600 px-3 py-1 rounded-full text-xs font-bold uppercase flex items-center gap-1">
                            <TruckIcon class="w-3.5 h-3.5" /> En camino
                        </span>
                        <span v-else-if="order.status === 'completed'" class="bg-green-100 text-green-600 px-3 py-1 rounded-full text-xs font-bold uppercase flex items-center gap-1">
                            <CheckCircleIcon class="w-3.5 h-3.5" /> Entregado
                        </span>
                    </div>

                    <ul class="space-y-2 mb-4">
                        <li v-for="service in order.services" :key="service.id" class="flex justify-between text-sm text-gray-600">
                            <span>{{ service.pivot.quantity }}x {{ service.name }}</span>
                        </li>
                    </ul>

                    <div class="text-right font-black text-lg text-indigo-600 border-t pt-4">
                        {{ formatPrice(order.total) }}
                    </div>
                </div>
            </div>

        </div>

        <button v-if="cart.length > 0" @click="isCartOpen = !isCartOpen" class="fixed bottom-6 right-6 bg-indigo-600 text-white p-4 rounded-full shadow-2xl hover:bg-indigo-700 transition-all z-40 flex items-center gap-2 animate-bounce">
            <ShoppingCartIcon class="w-6 h-6" />
            <span class="font-bold text-lg">{{ cart.reduce((acc, i) => acc + i.quantity, 0) }}</span>
        </button>

        <div v-if="isCartOpen" class="fixed inset-0 z-50 flex justify-end">
            <div @click="isCartOpen = false" class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md bg-white h-full shadow-2xl p-6 flex flex-col animate-in slide-in-from-right">
                <div class="flex justify-between items-center mb-6 border-b pb-4">
                    <h2 class="text-2xl font-bold text-gray-800">Su Pedido</h2>
                    <button @click="isCartOpen = false"><XMarkIcon class="w-8 h-8 text-gray-400 hover:text-gray-600 transition" /></button>
                </div>
                <div class="flex-1 overflow-y-auto space-y-4">
                    <div v-if="cart.length === 0" class="flex flex-col items-center justify-center text-gray-400 py-12">
                        <FaceFrownIcon class="w-16 h-16 mb-2 opacity-50" />
                        <p>El carrito está vacío</p>
                    </div>
                    <div v-for="item in cart" :key="item.id" class="flex justify-between items-center bg-gray-50 p-3 rounded-lg border border-gray-100">
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-800">{{ item.name }}</h4>
                            <p class="text-xs text-gray-500">{{ formatPrice(item.price) }} / ud.</p>
                        </div>
                        <div class="flex items-center gap-3 bg-white px-2 py-1 rounded-md shadow-sm border border-gray-200 ml-2">
                            <button @click="decreaseQty(item)" class="w-6 h-6 flex items-center justify-center text-indigo-600 font-bold hover:bg-gray-100 rounded">-</button>
                            <span class="font-bold text-gray-800 w-4 text-center">{{ item.quantity }}</span>
                            <button @click="increaseQty(item)" class="w-6 h-6 flex items-center justify-center text-indigo-600 font-bold hover:bg-gray-100 rounded">+</button>
                        </div>
                        <div class="ml-4 font-bold text-indigo-600 w-16 text-right">
                            {{ formatPrice(item.price * item.quantity) }}
                        </div>
                    </div>
                </div>
                <div class="border-t pt-6 mt-4">
                    <div class="flex justify-between font-bold text-xl mb-6 bg-gray-50 p-4 rounded-xl">
                        <span>Total:</span>
                        <span class="text-indigo-600">{{ formatPrice(totalPrice) }}</span>
                    </div>
                    <button @click="submitOrder" class="w-full bg-slate-900 text-white py-4 rounded-xl font-bold text-lg hover:bg-green-600 transition shadow-lg flex justify-center items-center gap-2">
                        <CheckCircleIcon class="w-6 h-6" />
                        Confirmar Pedido
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
