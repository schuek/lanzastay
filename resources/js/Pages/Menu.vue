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
    FaceFrownIcon
} from '@heroicons/vue/24/solid';

const props = defineProps({
    services: Array,
    categories: Array
});

// --- DETECTAR HABITACIÓN DEL QR ---
const params = new URLSearchParams(window.location.search);
const currentRoom = params.get('room') || '101';

// --- ESTADO ---
const activeCategory = ref(null);
const cart = ref([]);
const isCartOpen = ref(false);
const notification = ref(null);

// --- NOTIFICACIONES ---
const showNotification = (message, type = 'success') => {
    notification.value = { message, type };
    setTimeout(() => notification.value = null, 3000);
};

// --- LÓGICA DEL CARRITO (CON AGRUPACIÓN) ---
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
    if (item.quantity > 1) {
        item.quantity--;
    } else {
        cart.value = cart.value.filter(i => i.id !== item.id);
    }
};

const totalPrice = computed(() => {
    return cart.value.reduce((acc, item) => acc + (item.price * item.quantity), 0);
});

// --- ENVIAR PEDIDO ---
const submitOrder = () => {
    if (cart.value.length === 0) return;

    const flatCart = [];
    cart.value.forEach(item => {
        for (let i = 0; i < item.quantity; i++) {
            flatCart.push(item);
        }
    });

    router.post(route('order.store'), {
        room_number: currentRoom,
        total: totalPrice.value,
        cart: flatCart
    }, {
        onSuccess: () => {
            cart.value = [];
            isCartOpen.value = false;
            showNotification('Su pedido ha sido enviado a cocina correctamente', 'success');
        },
        onError: () => {
            showNotification('Hubo un error al procesar la solicitud', 'error');
        }
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

    <div class="min-h-screen bg-gray-100 relative pb-24">

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
                <button
                    v-if="activeCategory"
                    @click="activeCategory = null"
                    class="flex items-center text-gray-600 hover:text-indigo-600 font-bold transition"
                >
                    <ArrowLeftIcon class="w-5 h-5 mr-1" /> Volver
                </button>
                <span v-else class="text-xl font-bold text-gray-800">Habitación {{ currentRoom }}</span>
                <div class="text-indigo-600 font-black tracking-tighter">LANZASTAY</div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-8">

            <div v-if="!activeCategory" class="animate-in fade-in zoom-in duration-300">
                <h2 class="text-2xl font-light text-center mb-8 text-gray-600">Seleccione un servicio</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <button
                        v-for="category in categories"
                        :key="category.id"
                        @click="activeCategory = category"
                        class="bg-white p-8 rounded-2xl shadow-md hover:shadow-xl transition-all transform hover:-translate-y-1 flex flex-col items-center gap-4 border border-transparent hover:border-indigo-100"
                    >
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

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="service in filteredServices" :key="service.id" class="bg-white rounded-xl shadow-sm overflow-hidden flex flex-col h-full border border-gray-100">
                        <div class="p-5 flex-1">
                            <div class="flex justify-between items-start">
                                <h3 class="font-bold text-lg text-gray-900">{{ service.name }}</h3>
                                <span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded-full">
                                    {{ formatPrice(service.price) }}
                                </span>
                            </div>
                            <p class="text-gray-500 text-sm mt-2 leading-relaxed">{{ service.description }}</p>
                        </div>
                        <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                            <button @click="addToCart(service)" class="bg-slate-900 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors active:scale-95 flex items-center gap-2">
                                Añadir +
                            </button>
                        </div>
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
                    <button @click="isCartOpen = false"><XMarkIcon class="w-8 h-8 text-gray-400" /></button>
                </div>

                <div class="flex-1 overflow-y-auto space-y-4">
                    <div v-if="cart.length === 0" class="flex flex-col items-center justify-center text-gray-400 py-12">
                        <FaceFrownIcon class="w-16 h-16 mb-2 opacity-50" />
                        <p>El carrito está vacío</p>
                    </div>

                    <div v-for="item in cart" :key="item.id" class="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-800">{{ item.name }}</h4>
                            <p class="text-xs text-gray-500">{{ formatPrice(item.price) }} / ud.</p>
                        </div>

                        <div class="flex items-center gap-3 bg-white px-2 py-1 rounded-md shadow-sm border border-gray-200">
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
                    <div class="flex justify-between font-bold text-xl mb-4">
                        <span>Total:</span>
                        <span class="text-indigo-600">{{ formatPrice(totalPrice) }}</span>
                    </div>
                    <button @click="submitOrder" class="w-full bg-slate-900 text-white py-4 rounded-xl font-bold text-lg hover:bg-green-600 transition shadow-lg">
                        Confirmar Pedido
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
