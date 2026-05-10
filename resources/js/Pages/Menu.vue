<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import {
    ConciergeBell,
    ChefHat,
    ChevronLeft,
    CheckCircle2,
    Clock3,
    Euro,
    Home,
    MapPin,
    MessageCircle,
    Phone,
    ReceiptText,
    ShoppingCart,
    Sparkles,
    Ticket,
    TriangleAlert,
    User,
    Users,
    UtensilsCrossed,
    Wrench,
    X,
} from 'lucide-vue-next';
import LanguageSelector from '@/Components/LanguageSelector.vue';

const props = defineProps({
    services: { type: Array, default: () => [] },
    myOrders: { type: Array, default: () => [] },
    activities: { type: Array, default: () => [] },
    myReservations: { type: Array, default: () => [] },
    currentRoom: { type: String, default: '' },
    currentRoomId: { type: Number, default: undefined },
    sessionToken: { type: String, default: '' },
    guestEmail: { type: String, default: '' },
});

const currentTab = ref('home');
const notification = ref(null);
const selectedServiceCategory = ref(null);
const cart = ref([]);
const isCartOpen = ref(false);
const requestedTime = ref('');
const maintenanceDescription = ref('');
const reactiveReservations = ref([...(props.myReservations ?? [])]);
const paymentMethod = ref('room');
const isHelpModalOpen = ref(false);
const isChatOpen = ref(false);
const showReservationSuccess = ref(false);
let pollingInterval = null;
const orderStatusSnapshot = ref({});

const selectedActivity = ref(null);
const isActivityModalOpen = ref(false);
const isBookingModalOpen = ref(false);
const bookingSeats = ref(1);
const selectedActividadReserva = ref(null);
const isReservaModalOpen = ref(false);
const cantidadReserva = ref(1);
const restaurantFilter = ref('comida');
const selectedMenuItem = ref(null);
const isMenuItemModalOpen = ref(false);
const excludedIngredients = ref([]);
const page = usePage();

const availableHours = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];

const chatbotPos = ref({ bottom: 98, right: 16 });
let isDraggingChatbot = false;
let startX = 0;
let startY = 0;
let initialBottom = 0;
let initialRight = 0;

const totalPrice = computed(() => cart.value.reduce((acc, item) => acc + (item.price * item.quantity), 0));
const foodServices = computed(() => (props.services ?? []).filter((service) => {
    const explicitType = (service.service_type ?? '').toString().toLowerCase();
    const serviceCategory = (service.service_category ?? '').toString().toLowerCase();
    return explicitType === 'comida' || ['comida', 'bebida', 'postre'].includes(serviceCategory);
}));
const categorizedFoodServices = computed(() => {
    const map = { bebidas: [], comida: [], postres: [] };
    foodServices.value.forEach((service) => {
        const category = (service.service_category ?? '').toString().toLowerCase();
        if (category === 'bebida') {
            map.bebidas.push(service);
            return;
        }
        if (category === 'postre') {
            map.postres.push(service);
            return;
        }
        map.comida.push(service);
    });
    return map;
});
const filteredRestaurantServices = computed(() => categorizedFoodServices.value[restaurantFilter.value] ?? []);
const busTours = computed(() => (props.activities ?? []).filter((activity) => activity.type === 'bus_tour'));
/** Actividades de hotel desde BD; excluye las marcadas con prefijo [niños] en descripción (misma lógica que el listado general anterior). */
const hotelActivitiesGeneral = computed(() => (props.activities ?? []).filter(
    (activity) => activity.type === 'hotel_activity' && !String(activity.description ?? '').startsWith('[niños]'),
));
const bookingTotal = computed(() => Number((selectedActivity.value?.price ?? 0) * bookingSeats.value));
const precioTotalReserva = computed(() => Number((selectedActividadReserva.value?.price ?? 0) * cantidadReserva.value));

const formatPrice = (value) => new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value || 0);
const formatDateTime = (value) => {
    if (value == null || value === '') return '—';
    const d = new Date(value);
    return Number.isNaN(d.getTime()) ? '—' : d.toLocaleString('es-ES', { dateStyle: 'medium', timeStyle: 'short' });
};
const activityDateYmd = (activity) => {
    if (!activity?.date_time) return undefined;
    const d = new Date(activity.date_time);
    if (Number.isNaN(d.getTime())) return undefined;
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
};

const buildReservationPayload = (activity, seats, type) => {
    const date = activityDateYmd(activity);
    return {
        room_number: props.currentRoom,
        session_token: props.sessionToken,
        activity_id: activity.id,
        guests: seats,
        seats_booked: seats,
        type,
        ...(date ? { date } : {}),
    };
};

const postReservation = (activity, seats, type) => axios.post('/api/reservations', buildReservationPayload(activity, seats, type));
const formatReservationType = (type) => type === 'bus_tour' ? 'Bus' : 'Actividad';
const statusLabel = (status) => ({ pendiente: 'Pendiente', confirmada: 'Confirmada', cancelada: 'Cancelada' }[status] ?? status);
const orderStatusClass = (status) => status === 'completado' ? 'text-gray-500 bg-gray-100' : 'text-[#A64B35] bg-[#A64B35]/10';
const orderStatusLabel = (order) => {
    const labelsByType = {
        comida: { recibido: 'Pendiente', en_proceso: 'En cocina', en_camino: 'En camino', completado: 'Completado', pagado: 'Pagado' },
        limpieza: { recibido: 'Pendiente', en_proceso: 'En limpieza', en_camino: 'En camino', completado: 'Completado' },
        mantenimiento: { recibido: 'Pendiente', en_proceso: 'En revisión', en_camino: 'En camino', completado: 'Completado' },
    };
    return labelsByType[order.service_type]?.[order.status] ?? order.status;
};

const showNotification = (message, type = 'success') => {
    notification.value = { message, type };
    setTimeout(() => {
        notification.value = null;
    }, 5000);
};
const mostrarNotificacion = (message, type = 'success') => showNotification(message, type);

const changeTab = (newTab) => {
    if (currentTab.value === newTab) return;
    currentTab.value = newTab;
    window.history.pushState({ tab: newTab }, '');
};

const handlePopState = () => {
    if (currentTab.value !== 'home') {
        currentTab.value = 'home';
        return;
    }
    window.removeEventListener('popstate', handlePopState);
    window.history.back();
};

const startDragChatbot = (event) => {
    isDraggingChatbot = true;
    const point = event.touches ? event.touches[0] : event;
    startX = point.clientX;
    startY = point.clientY;
    initialBottom = chatbotPos.value.bottom;
    initialRight = chatbotPos.value.right;

    document.addEventListener('mousemove', onDragChatbot);
    document.addEventListener('mouseup', stopDragChatbot);
    document.addEventListener('touchmove', onDragChatbot, { passive: false });
    document.addEventListener('touchend', stopDragChatbot);
    event.preventDefault();
};

const onDragChatbot = (event) => {
    if (!isDraggingChatbot) return;
    const point = event.touches ? event.touches[0] : event;
    const deltaX = point.clientX - startX;
    const deltaY = point.clientY - startY;

    const maxRight = Math.max(0, window.innerWidth - 64);
    const maxBottom = Math.max(0, window.innerHeight - 64);
    chatbotPos.value.right = Math.max(0, Math.min(initialRight - deltaX, maxRight));
    chatbotPos.value.bottom = Math.max(0, Math.min(initialBottom - deltaY, maxBottom));

    event.preventDefault();
};

const stopDragChatbot = () => {
    isDraggingChatbot = false;
    document.removeEventListener('mousemove', onDragChatbot);
    document.removeEventListener('mouseup', stopDragChatbot);
    document.removeEventListener('touchmove', onDragChatbot);
    document.removeEventListener('touchend', stopDragChatbot);
};

const fetchMyOrders = () => {
    router.reload({
        only: ['myOrders'],
        preserveScroll: true,
        onSuccess: () => {
            const current = props.myOrders ?? [];
            current
                .filter((order) => order.status !== 'completado')
                .forEach((order) => {
                    const previousStatus = orderStatusSnapshot.value[order.id];
                    if (previousStatus && previousStatus !== order.status) {
                        mostrarNotificacion(`Tu pedido #${order.id} cambió a ${orderStatusLabel(order)}.`);
                    }
                    orderStatusSnapshot.value[order.id] = order.status;
                });
        },
    });
};

const orderStepIndex = (status) => {
    if (status === 'recibido') return 0;
    if (status === 'en_proceso') return 1;
    if (status === 'en_camino') return 2;
    if (status === 'completado') return 3;
    return 0;
};

const orderSteps = [
    { key: 'recibido', label: 'Recibido', icon: Clock3 },
    { key: 'preparando', label: 'Preparando', icon: ChefHat },
    { key: 'en_camino', label: 'En camino', icon: ConciergeBell },
    { key: 'entregado', label: 'Entregado', icon: CheckCircle2 },
];

const orderTitle = (order) => {
    if (order.service_type === 'comida') {
        const mainItem = order.services?.[0]?.name;
        return mainItem ? `Pedido de ${mainItem}` : 'Pedido de comida';
    }
    if (order.service_type === 'limpieza') return 'Pedido de limpieza';
    if (order.service_type === 'mantenimiento') return 'Pedido de mantenimiento';
    return 'Pedido';
};

const serviceIngredients = (service) => {
    if (Array.isArray(service.ingredients)) return service.ingredients;
    return [];
};

const hasVegBadge = (service) => Boolean(service.is_vegan);

const openMenuItemModal = (service) => {
    selectedMenuItem.value = service;
    excludedIngredients.value = [];
    isMenuItemModalOpen.value = true;
};

const addToCart = (service) => {
    const existing = cart.value.find((item) => item.id === service.id);
    if (existing) existing.quantity += 1;
    else cart.value.push({ ...service, quantity: 1 });
    isCartOpen.value = true;
};
const increaseQty = (item) => item.quantity++;
const decreaseQty = (item) => {
    if (item.quantity > 1) item.quantity--;
    else cart.value = cart.value.filter((i) => i.id !== item.id);
};

const submitOrder = () => {
    if (cart.value.length === 0) return;
    if (paymentMethod.value === 'card') {
        axios.post(route('orders.checkout', { numero: props.currentRoom }), {
            cart: cart.value.map((item) => ({ id: item.id, name: item.name, quantity: item.quantity, price: item.price })),
            total: totalPrice.value,
            session_token: props.sessionToken,
        }).then((response) => {
            if (response.data?.url) {
                window.location.href = response.data.url;
                return;
            }
            showNotification('No se pudo iniciar el pago con tarjeta.', 'error');
        }).catch(() => showNotification('No se pudo iniciar el pago con tarjeta.', 'error'));
        return;
    }

    axios.post('/api/orders', {
        room_number: props.currentRoom,
        habitacion_id: props.currentRoomId,
        session_token: props.sessionToken,
        service_type: 'comida',
        total: totalPrice.value,
        cart: cart.value.map((item) => ({ id: item.id, quantity: item.quantity, price: item.price })),
    }).then(() => {
        cart.value = [];
        isCartOpen.value = false;
        showNotification('Pedido de comida enviado.', 'success');
        fetchMyOrders();
    }).catch(() => showNotification('No se pudo enviar el pedido.', 'error'));
};

const submitCleaningRequest = () => {
    if (!requestedTime.value) return showNotification('Selecciona una hora para limpieza.', 'error');
    axios.post('/api/orders', {
        room_number: props.currentRoom,
        habitacion_id: props.currentRoomId,
        session_token: props.sessionToken,
        service_type: 'limpieza',
        requested_time: requestedTime.value,
    }).then(() => {
        requestedTime.value = '';
        showNotification('Solicitud de limpieza enviada.', 'success');
    }).catch(() => showNotification('No se pudo enviar la solicitud.', 'error'));
};

const submitMaintenanceRequest = () => {
    if (!maintenanceDescription.value.trim()) return showNotification('Describe la avería para continuar.', 'error');
    axios.post('/api/orders', {
        room_number: props.currentRoom,
        habitacion_id: props.currentRoomId,
        session_token: props.sessionToken,
        service_type: 'mantenimiento',
        description: maintenanceDescription.value.trim(),
    }).then(() => {
        maintenanceDescription.value = '';
        showNotification('Reporte de mantenimiento enviado.', 'success');
    }).catch(() => showNotification('No se pudo enviar el reporte.', 'error'));
};

const openActivityModal = (activity) => {
    selectedActivity.value = activity;
    isActivityModalOpen.value = true;
};
const startReservation = (activity) => {
    selectedActivity.value = activity;
    bookingSeats.value = 1;
    isActivityModalOpen.value = false;
    isBookingModalOpen.value = true;
};
const confirmReservation = () => {
    if (!selectedActivity.value) return;
    postReservation(selectedActivity.value, bookingSeats.value, 'bus_tour').then((response) => {
        if (response.data?.reservation) reactiveReservations.value.unshift(response.data.reservation);
        bookingSeats.value = 1;
        selectedActivity.value = null;
        isBookingModalOpen.value = false;
        showReservationSuccess.value = true;
        setTimeout(() => { showReservationSuccess.value = false; }, 1800);
        showNotification('Reserva enviada a recepción.', 'success');
    }).catch((error) => {
        const errs = error?.response?.data?.errors ?? {};
        const message = errs.seats_booked?.[0] ?? errs.date?.[0] ?? errs.type?.[0] ?? 'No se pudo completar la reserva.';
        showNotification(message, 'error');
    });
};

const openReservaModal = (actividad) => {
    selectedActividadReserva.value = actividad;
    cantidadReserva.value = 1;
    isReservaModalOpen.value = true;
};
const adjustCantidadReserva = () => {
    const maxSeats = selectedActividadReserva.value?.max_seats ?? 1;
    if (cantidadReserva.value < 1) cantidadReserva.value = 1;
    if (cantidadReserva.value > maxSeats) cantidadReserva.value = maxSeats;
};
const confirmarReservaActividad = () => {
    if (!selectedActividadReserva.value) return;
    postReservation(selectedActividadReserva.value, cantidadReserva.value, 'hotel_activity').then((response) => {
        if (response.data?.reservation) reactiveReservations.value.unshift(response.data.reservation);
        cantidadReserva.value = 1;
        selectedActividadReserva.value = null;
        isReservaModalOpen.value = false;
        showReservationSuccess.value = true;
        setTimeout(() => { showReservationSuccess.value = false; }, 1800);
        showNotification('Reserva confirmada.', 'success');
    }).catch((error) => {
        const errs = error?.response?.data?.errors ?? {};
        const message = errs.seats_booked?.[0] ?? errs.date?.[0] ?? errs.type?.[0] ?? 'No se pudo completar la reserva.';
        showNotification(message, 'error');
    });
};

onMounted(() => {
    window.history.replaceState({ tab: 'home' }, '');
    window.addEventListener('popstate', handlePopState);
    if (page.props.flash?.success) {
        showNotification(page.props.flash.success, 'success');
    }
    if (page.props.flash?.error) {
        showNotification(page.props.flash.error, 'error');
    }
    fetchMyOrders();
    pollingInterval = setInterval(fetchMyOrders, 12000);
});

onUnmounted(() => {
    window.removeEventListener('popstate', handlePopState);
    stopDragChatbot();
    if (pollingInterval) clearInterval(pollingInterval);
});
</script>

<template>
    <Head title="Conserje Virtual" />

    <div class="max-w-md mx-auto min-h-screen bg-white relative">
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="notification" class="fixed top-20 left-1/2 -translate-x-1/2 z-[70] w-[90%] max-w-sm rounded-xl shadow-sm bg-white border border-gray-200">
                <div class="p-3 flex items-center gap-2">
                    <CheckCircle2 v-if="notification.type === 'success'" class="w-4 h-4 text-[#A64B35]" />
                    <TriangleAlert v-else class="w-4 h-4 text-[#A64B35]" />
                    <p class="text-xs text-gray-700">{{ notification.message }}</p>
                </div>
            </div>
        </Transition>

        <header class="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur border-b border-[#2F2A26]/10">
            <div class="max-w-md mx-auto px-4 py-3 flex items-center justify-between">
                <div>
                    <p class="text-base font-bold text-[#2F2A26] leading-none">LANZA<span class="text-[#A64B35]">STAY</span></p>
                    <p class="text-xs text-[#2F2A26]/60 mt-1">Habitación {{ currentRoom }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="text-xs text-[#2F2A26]">
                        <LanguageSelector />
                    </div>
                    <button
                        v-if="cart.length > 0"
                        @click="isCartOpen = true"
                        class="relative inline-flex items-center justify-center w-9 h-9 rounded-full border border-[#2F2A26]/15 text-[#2F2A26]"
                    >
                        <ShoppingCart class="w-4 h-4 text-[#A64B35]" />
                        <span class="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white text-[10px] leading-4 text-center">
                            {{ cart.length }}
                        </span>
                    </button>
                    <button @click="isHelpModalOpen = true" class="inline-flex items-center gap-1.5 rounded-full border border-[#2F2A26]/15 px-3 py-1.5 text-xs font-medium text-[#2F2A26]">
                        <Phone class="w-3.5 h-3.5 text-[#A64B35]" />
                        AYUDA
                    </button>
                </div>
            </div>
        </header>

        <main class="px-4 pt-20 pb-32 space-y-4">
            <section v-if="currentTab === 'home'" class="space-y-4">
                <button @click="changeTab('services')" class="w-full rounded-2xl bg-[url('/images/servicios.avif')] bg-cover bg-center overflow-hidden text-white text-left">
                    <div class="bg-black/40 p-5">
                        <p class="text-xs uppercase tracking-wide text-white/80">Explora</p>
                        <p class="text-base font-semibold mt-1">Servicios</p>
                    </div>
                </button>

                <button @click="changeTab('activities')" class="w-full rounded-2xl bg-[url('/images/actividades.avif')] bg-cover bg-center overflow-hidden text-white text-left">
                    <div class="bg-black/40 p-5">
                        <p class="text-xs uppercase tracking-wide text-white/80">Descubre</p>
                        <p class="text-base font-semibold mt-1">Actividades</p>
                    </div>
                </button>

                <section>
                    <h2 class="text-sm font-semibold text-[#2F2A26] mb-2">Turismo</h2>
                    <div class="flex overflow-x-auto gap-3 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        <article v-for="tour in busTours" :key="tour.id" class="min-w-[220px] h-64 rounded-xl border border-[#2F2A26]/10 bg-white overflow-hidden flex flex-col">
                            <img :src="tour.image_url || 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=600&q=80&fm=avif'" :alt="tour.name" class="w-full h-40 object-cover">
                            <div class="p-2.5">
                                <p class="text-sm font-semibold text-[#2F2A26] truncate">{{ tour.name || 'Excursión' }}</p>
                                <div class="mt-2 flex items-center gap-1.5">
                                    <a :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent((tour.name || 'Lanzarote') + ' Lanzarote')}`" target="_blank" class="flex-1 inline-flex items-center justify-center gap-1 rounded-md border border-[#2F2A26]/20 py-1 text-[10px] text-[#2F2A26]">
                                        <MapPin class="w-3 h-3" />
                                        Cómo llegar
                                    </a>
                                    <button @click="openActivityModal(tour)" class="flex-1 inline-flex items-center justify-center gap-1 rounded-md bg-[#A64B35] text-white py-1 text-[10px]">
                                        <Ticket class="w-3 h-3" />
                                        Reservar
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>
            </section>

            <section v-if="currentTab === 'services'" class="space-y-4">
                <button v-if="selectedServiceCategory" @click="selectedServiceCategory = null" class="inline-flex items-center gap-1 text-xs text-[#2F2A26]">
                    <ChevronLeft class="w-3.5 h-3.5" />
                    Volver
                </button>

                <div v-if="!selectedServiceCategory" class="space-y-3">
                    <button @click="selectedServiceCategory = 'restaurante'" class="w-full rounded-xl bg-[url('/images/restaurante.avif')] bg-cover bg-center overflow-hidden text-left">
                        <div class="bg-black/40 p-4 inline-flex items-center gap-2 w-full">
                            <UtensilsCrossed class="w-5 h-5 text-white" />
                            <p class="text-sm font-semibold text-white">Restaurante</p>
                        </div>
                    </button>
                    <button @click="selectedServiceCategory = 'limpieza'" class="w-full rounded-xl bg-[url('/images/limpieza.avif')] bg-cover bg-center overflow-hidden text-left">
                        <div class="bg-black/40 p-4 inline-flex items-center gap-2 w-full">
                            <Sparkles class="w-5 h-5 text-white" />
                            <p class="text-sm font-semibold text-white">Limpieza</p>
                        </div>
                    </button>
                    <button @click="selectedServiceCategory = 'mantenimiento'" class="w-full rounded-xl bg-[url('/images/mantenimiento.avif')] bg-cover bg-center overflow-hidden text-left">
                        <div class="bg-black/40 p-4 inline-flex items-center gap-2 w-full">
                            <Wrench class="w-5 h-5 text-white" />
                            <p class="text-sm font-semibold text-white">Mantenimiento</p>
                        </div>
                    </button>
                </div>

                <div v-if="selectedServiceCategory === 'restaurante'" class="space-y-2">
                    <div class="flex items-center gap-2 pb-1">
                        <button @click="restaurantFilter = 'bebidas'" :class="restaurantFilter === 'bebidas' ? 'bg-[#A64B35] text-white' : 'bg-white text-[#2F2A26] border border-[#2F2A26]/20'" class="px-3 py-1.5 rounded-full text-xs">Bebidas</button>
                        <button @click="restaurantFilter = 'comida'" :class="restaurantFilter === 'comida' ? 'bg-[#A64B35] text-white' : 'bg-white text-[#2F2A26] border border-[#2F2A26]/20'" class="px-3 py-1.5 rounded-full text-xs">Comida</button>
                        <button @click="restaurantFilter = 'postres'" :class="restaurantFilter === 'postres' ? 'bg-[#A64B35] text-white' : 'bg-white text-[#2F2A26] border border-[#2F2A26]/20'" class="px-3 py-1.5 rounded-full text-xs">Postres</button>
                    </div>
                    <article v-for="service in filteredRestaurantServices" :key="service.id" class="rounded-xl border border-[#2F2A26]/10 bg-white p-3">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-semibold text-[#2F2A26]">{{ service.name }}</p>
                                <span v-if="hasVegBadge(service)" class="text-[10px] px-2 py-0.5 rounded-full bg-[#A64B35]/10 text-[#A64B35]">
                                    Veg
                                </span>
                            </div>
                            <p class="text-xs font-semibold text-[#A64B35]">{{ formatPrice(service.price) }}</p>
                        </div>
                        <p class="text-xs text-[#2F2A26]/65 mt-1">{{ service.description }}</p>
                        <div class="mt-2 flex items-center gap-2">
                            <button @click="openMenuItemModal(service)" class="rounded-md border border-[#2F2A26]/20 px-2.5 py-1 text-xs text-[#2F2A26]">Detalles</button>
                            <button @click="addToCart(service)" class="rounded-md bg-[#A64B35] text-white px-2.5 py-1 text-xs">Añadir</button>
                        </div>
                    </article>
                    <div v-if="filteredRestaurantServices.length === 0" class="rounded-xl border border-[#2F2A26]/10 bg-white p-3 text-xs text-[#2F2A26]/65">
                        No hay productos en esta categoría.
                    </div>
                </div>

                <div v-if="selectedServiceCategory === 'limpieza'" class="rounded-xl border border-[#2F2A26]/10 bg-white p-3 space-y-2">
                    <select v-model="requestedTime" class="w-full rounded-lg border-[#2F2A26]/15 text-sm">
                        <option value="">Selecciona hora</option>
                        <option v-for="hour in availableHours" :key="hour" :value="hour">{{ hour }}</option>
                    </select>
                    <button @click="submitCleaningRequest" class="w-full rounded-lg bg-[#A64B35] text-white py-2 text-sm">Enviar solicitud</button>
                </div>

                <div v-if="selectedServiceCategory === 'mantenimiento'" class="rounded-xl border border-[#2F2A26]/10 bg-white p-3 space-y-2">
                    <textarea v-model="maintenanceDescription" rows="3" class="w-full rounded-lg border-[#2F2A26]/15 text-sm" placeholder="Describe el problema"></textarea>
                    <button @click="submitMaintenanceRequest" class="w-full rounded-lg bg-[#A64B35] text-white py-2 text-sm">Enviar reporte</button>
                </div>
            </section>

            <section v-if="currentTab === 'activities'" class="space-y-3">
                <div v-if="hotelActivitiesGeneral.length === 0" class="rounded-xl border border-[#2F2A26]/10 bg-white p-4 text-xs text-[#2F2A26]/70">
                    No hay actividades del hotel disponibles en este momento.
                </div>
                <article v-for="activity in hotelActivitiesGeneral" :key="activity.id" class="rounded-xl border border-[#2F2A26]/10 bg-white overflow-hidden">
                    <img :src="activity.image_url || '/images/spa.avif'" :alt="activity.name" class="h-32 w-full object-cover">
                    <div class="p-3">
                        <p class="text-sm font-semibold text-[#2F2A26]">{{ activity.name }}</p>
                        <p class="text-xs text-[#2F2A26]/65 mt-1">{{ String(activity.description ?? '').replace(/^\[niños\]\s*/i, '') }}</p>
                        <div class="mt-2 space-y-1 text-xs text-[#2F2A26]/70">
                            <p class="inline-flex items-center gap-1"><Clock3 class="w-3.5 h-3.5 text-[#A64B35]" /> {{ formatDateTime(activity.date_time) }}</p>
                            <p class="inline-flex items-center gap-1 ml-3"><Euro class="w-3.5 h-3.5 text-[#A64B35]" /> {{ Number(activity.price) === 0 ? 'Gratis' : formatPrice(activity.price) }}</p>
                            <p class="inline-flex items-center gap-1 ml-3"><Users class="w-3.5 h-3.5 text-[#A64B35]" /> {{ activity.max_seats }} plazas</p>
                        </div>
                        <button @click="openReservaModal(activity)" class="mt-3 w-full rounded-lg bg-[#A64B35] text-white py-2 text-sm">
                            {{ $t('actions.book') }}
                        </button>
                    </div>
                </article>
            </section>

            <section v-if="currentTab === 'orders'" class="space-y-3">
                <div v-if="props.myOrders.length === 0" class="rounded-xl border border-[#2F2A26]/10 bg-white p-4 text-xs text-[#2F2A26]/70">
                    No tienes solicitudes registradas.
                </div>
                <article v-for="order in props.myOrders" :key="order.id" class="rounded-xl border border-[#2F2A26]/10 bg-white p-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-[#2F2A26]">{{ orderTitle(order) }}</p>
                            <p class="text-xs text-[#2F2A26]/55">{{ formatDateTime(order.created_at) }}</p>
                            <p class="text-[10px] text-[#2F2A26]/50 mt-1 font-mono uppercase tracking-wide">
                                Estado: {{ order.status }}
                            </p>
                        </div>
                        <span :class="orderStatusClass(order.status)" class="text-[10px] px-2 py-1 rounded-full font-medium">
                            {{ orderStatusLabel(order) }}
                        </span>
                    </div>
                    <ul v-if="order.service_type === 'comida'" class="mt-2 text-xs text-[#2F2A26]/70">
                        <li v-for="service in order.services" :key="service.id">{{ service.pivot.quantity }}x {{ service.name }}</li>
                    </ul>
                    <p v-if="order.service_type === 'comida'" class="mt-1 text-xs font-semibold text-[#A64B35]">{{ formatPrice(order.total_price) }}</p>
                    <div class="mt-3">
                        <div class="grid grid-cols-4 gap-1">
                            <div v-for="(step, index) in orderSteps" :key="step.key" class="flex flex-col items-center gap-1">
                                <component :is="step.icon" :class="index <= orderStepIndex(order.status) ? 'text-[#A64B35]' : 'text-[#2F2A26]/30'" class="w-3.5 h-3.5" />
                                <span :class="index <= orderStepIndex(order.status) ? 'text-[#A64B35]' : 'text-[#2F2A26]/45'" class="text-[10px] text-center leading-tight">{{ step.label }}</span>
                            </div>
                        </div>
                        <div class="mt-2 h-1 rounded-full bg-[#2F2A26]/10 overflow-hidden">
                            <div class="h-full bg-[#A64B35] transition-all duration-300" :style="{ width: `${((orderStepIndex(order.status) + 1) / 4) * 100}%` }"></div>
                        </div>
                    </div>
                </article>
            </section>

            <section v-if="currentTab === 'profile'" class="space-y-3">
                <div class="rounded-xl border border-[#2F2A26]/10 bg-white p-4">
                    <p class="text-xs text-[#2F2A26]/60">Email del huésped</p>
                    <p class="text-sm font-semibold text-[#2F2A26] mt-1">{{ guestEmail || 'No disponible' }}</p>
                </div>
                <div class="space-y-2">
                    <p class="text-sm font-semibold text-[#2F2A26]">Mis reservas de actividades</p>
                    <div class="rounded-lg border border-[#2F2A26]/10 p-3 bg-white">
                        <p class="text-[10px] text-[#2F2A26]/60">Total</p>
                        <p class="text-base font-semibold text-[#2F2A26]">{{ reactiveReservations.length }}</p>
                    </div>
                    <article v-for="reservation in reactiveReservations" :key="reservation.id" class="rounded-xl border border-[#2F2A26]/10 bg-white p-3">
                        <div class="flex justify-between items-center">
                            <p class="text-sm font-semibold text-[#2F2A26]">{{ reservation.activity?.name }}</p>
                            <span class="text-[10px] px-2 py-1 rounded-full bg-[#A64B35]/10 text-[#A64B35]">{{ statusLabel(reservation.status) }}</span>
                        </div>
                        <p class="text-xs text-[#2F2A26]/65 mt-1">{{ formatReservationType(reservation.activity?.type) }} · {{ formatDateTime(reservation.activity?.date_time) }}</p>
                        <p class="text-xs text-[#2F2A26]/55 mt-0.5">Plazas: {{ reservation.seats_booked }} · {{ formatPrice(reservation.total_price) }}</p>
                    </article>
                </div>
            </section>
        </main>

        <button
            @mousedown="startDragChatbot"
            @touchstart="startDragChatbot"
            @click="!isDraggingChatbot && (isChatOpen = !isChatOpen)"
            :style="{ bottom: `${chatbotPos.bottom}px`, right: `${chatbotPos.right}px` }"
            class="fixed z-50 w-12 h-12 rounded-full bg-[#2F2A26] text-white shadow-lg flex items-center justify-center touch-none"
        >
            <MessageCircle class="w-5 h-5" />
        </button>

        <nav class="fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur border-t border-[#2F2A26]/10">
            <div class="max-w-md mx-auto h-16 px-8 flex items-center justify-between">
                <button @click="changeTab('home')" :class="currentTab === 'home' ? 'text-[#A64B35]' : 'text-gray-400'" class="flex flex-col items-center">
                    <Home class="w-5 h-5" />
                    <span class="text-[10px]">INICIO</span>
                </button>
                <button @click="changeTab('orders')" :class="currentTab === 'orders' ? 'text-[#A64B35]' : 'text-gray-400'" class="flex flex-col items-center">
                    <ReceiptText class="w-5 h-5" />
                    <span class="text-[10px]">MIS PEDIDOS</span>
                </button>
                <button @click="changeTab('profile')" :class="currentTab === 'profile' ? 'text-[#A64B35]' : 'text-gray-400'" class="flex flex-col items-center">
                    <User class="w-5 h-5" />
                    <span class="text-[10px]">MI PERFIL</span>
                </button>
            </div>
        </nav>

        <Teleport to="body">
            <div v-if="isHelpModalOpen" class="fixed inset-0 z-[80] flex items-end sm:items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" @click="isHelpModalOpen = false"></div>
                <div class="relative w-full max-w-sm rounded-2xl bg-white border border-[#2F2A26]/10 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-semibold text-[#2F2A26]">Ayuda y Contacto</p>
                        <button @click="isHelpModalOpen = false" class="text-[#2F2A26]/60"><X class="w-4 h-4" /></button>
                    </div>
                    <div class="space-y-2">
                        <a href="tel:+123456789" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-[#A64B35] text-white py-2 text-sm">
                            <Phone class="w-4 h-4" />
                            Llamar a Recepción
                        </a>
                        <a href="https://wa.me/123456789" target="_blank" class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-[#2F2A26]/15 py-2 text-sm text-[#2F2A26]">
                            <MessageCircle class="w-4 h-4 text-[#A64B35]" />
                            WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </Teleport>

        <div v-if="isChatOpen" class="fixed inset-0 z-[75] flex items-end justify-end p-4">
            <div class="absolute inset-0 bg-black/40" @click="isChatOpen = false"></div>
            <div class="relative w-full max-w-sm bg-white rounded-2xl border border-[#2F2A26]/10 shadow-xl overflow-hidden">
                <div class="p-3 bg-[#2F2A26] text-white flex items-center justify-between">
                    <p class="text-sm">Asistente Virtual</p>
                    <button @click="isChatOpen = false"><X class="w-4 h-4" /></button>
                </div>
                <div class="p-3 text-xs text-[#2F2A26]/70">
                    ¿Necesitas ayuda rápida? Usa el botón AYUDA para contactar recepción.
                </div>
            </div>
        </div>

        <div v-if="isCartOpen" class="fixed inset-0 z-[85] flex justify-end">
            <div class="absolute inset-0 bg-black/40" @click="isCartOpen = false"></div>
            <div class="relative w-full max-w-md bg-white h-full shadow-xl p-4 flex flex-col">
                <div class="flex items-center justify-between pb-3 border-b border-[#2F2A26]/10">
                    <p class="text-base font-semibold text-[#2F2A26]">Pedido de comida</p>
                    <button @click="isCartOpen = false"><X class="w-5 h-5 text-[#2F2A26]/60" /></button>
                </div>
                <div class="flex-1 overflow-y-auto space-y-2 py-3">
                    <article v-for="item in cart" :key="item.id" class="rounded-lg border border-[#2F2A26]/10 p-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-[#2F2A26]">{{ item.name }}</p>
                            <p class="text-xs text-[#2F2A26]/60">{{ formatPrice(item.price) }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="decreaseQty(item)" class="w-6 h-6 rounded border border-[#2F2A26]/20 text-xs">-</button>
                            <span class="text-xs">{{ item.quantity }}</span>
                            <button @click="increaseQty(item)" class="w-6 h-6 rounded border border-[#2F2A26]/20 text-xs">+</button>
                        </div>
                    </article>
                </div>
                <div class="pt-3 border-t border-[#2F2A26]/10">
                    <div class="flex items-center justify-between text-sm mb-3">
                        <span>Total</span>
                        <span class="text-[#A64B35] font-semibold">{{ formatPrice(totalPrice) }}</span>
                    </div>
                    <label class="block text-xs text-[#2F2A26]/70 mb-1">¿Cómo deseas pagar?</label>
                    <select v-model="paymentMethod" class="w-full mb-3 rounded-lg border-[#2F2A26]/20 text-sm">
                        <option value="room">Cargar a la habitación</option>
                        <option value="card">Pagar ahora con tarjeta</option>
                    </select>
                    <button @click="submitOrder" class="w-full rounded-lg bg-[#A64B35] text-white py-2.5 text-sm">Confirmar pedido</button>
                </div>
            </div>
        </div>

        <div v-if="isActivityModalOpen && selectedActivity" class="fixed inset-0 z-[85] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="isActivityModalOpen = false"></div>
            <div class="relative bg-white rounded-2xl border border-[#2F2A26]/10 shadow-xl w-full max-w-sm overflow-hidden">
                <img :src="selectedActivity.image_url || 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=1200&q=80&fm=avif'" :alt="selectedActivity.name" class="w-full h-40 object-cover">
                <div class="p-4">
                    <p class="text-sm font-semibold text-[#2F2A26]">{{ selectedActivity.name }}</p>
                    <p class="text-xs text-[#2F2A26]/65 mt-1">{{ selectedActivity.description }}</p>
                    <div class="mt-3 space-y-2">
                        <a :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(selectedActivity.name)}`" target="_blank" class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-[#2F2A26]/20 py-2 text-xs text-[#2F2A26]">
                            <MapPin class="w-3.5 h-3.5" />
                            Cómo llegar
                        </a>
                        <button @click="startReservation(selectedActivity)" class="w-full rounded-lg bg-[#A64B35] text-white py-2 text-xs">Reservar Bus</button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isBookingModalOpen && selectedActivity" class="fixed inset-0 z-[90] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="isBookingModalOpen = false"></div>
            <div class="relative bg-white rounded-xl border border-[#2F2A26]/10 shadow-xl p-4 w-full max-w-sm">
                <p class="text-sm font-semibold text-[#2F2A26]">Confirmar reserva</p>
                <p class="text-xs text-[#2F2A26]/65 mt-1">{{ selectedActivity.name }}</p>
                <label class="block text-xs text-[#2F2A26]/65 mt-3">Número de plazas</label>
                <input v-model.number="bookingSeats" type="number" min="1" class="w-full rounded-lg border-[#2F2A26]/20 mt-1 text-sm">
                <p class="text-xs font-semibold text-[#A64B35] mt-2">Total: {{ formatPrice(bookingTotal) }}</p>
                <button @click="confirmReservation" class="w-full mt-3 rounded-lg bg-[#A64B35] text-white py-2 text-sm">Confirmar reserva</button>
            </div>
        </div>

        <div v-if="isReservaModalOpen && selectedActividadReserva" class="fixed inset-0 z-[90] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="isReservaModalOpen = false"></div>
            <div class="relative bg-white rounded-xl border border-[#2F2A26]/10 shadow-xl p-4 w-full max-w-sm">
                <p class="text-sm font-semibold text-[#2F2A26]">{{ selectedActividadReserva.name }}</p>
                <p class="text-xs text-[#2F2A26]/65 mt-1">{{ formatDateTime(selectedActividadReserva.date_time) }}</p>
                <label class="block text-xs text-[#2F2A26]/65 mt-3">¿Cuántos son?</label>
                <input v-model.number="cantidadReserva" @input="adjustCantidadReserva" type="number" min="1" :max="selectedActividadReserva.max_seats" class="w-full rounded-lg border-[#2F2A26]/20 mt-1 text-sm">
                <p class="text-xs text-[#A64B35] mt-2">Precio total: {{ Number(selectedActividadReserva.price) === 0 ? 'Gratis' : formatPrice(precioTotalReserva) }}</p>
                <button @click="confirmarReservaActividad" class="w-full mt-3 rounded-lg bg-[#A64B35] text-white py-2 text-sm">
                    {{ $t('actions.confirm') }}
                </button>
            </div>
        </div>

        <div v-if="isMenuItemModalOpen && selectedMenuItem" class="fixed inset-0 z-[92] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="isMenuItemModalOpen = false"></div>
            <div class="relative bg-white rounded-xl border border-[#2F2A26]/10 shadow-xl p-4 w-full max-w-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-[#2F2A26]">{{ selectedMenuItem.name }}</p>
                    <button @click="isMenuItemModalOpen = false" class="text-[#2F2A26]/60"><X class="w-4 h-4" /></button>
                </div>
                <p class="text-xs text-[#2F2A26]/65 mt-1">{{ selectedMenuItem.description }}</p>
                <span v-if="hasVegBadge(selectedMenuItem)" class="inline-block mt-2 text-[10px] px-2 py-0.5 rounded-full bg-[#A64B35]/10 text-[#A64B35]">Vegano/Vegetariano</span>
                <div class="mt-3">
                    <p class="text-xs font-semibold text-[#2F2A26] mb-2">Ingredientes (marca lo que no quieres)</p>
                    <div class="space-y-1.5 max-h-40 overflow-y-auto">
                        <label v-for="ingredient in serviceIngredients(selectedMenuItem)" :key="ingredient" class="flex items-center gap-2 text-xs text-[#2F2A26]">
                            <input v-model="excludedIngredients" type="checkbox" :value="ingredient" class="rounded border-[#2F2A26]/25 text-[#A64B35] focus:ring-[#A64B35]" />
                            <span>{{ ingredient }}</span>
                        </label>
                    </div>
                </div>
                <button @click="addToCart(selectedMenuItem); isMenuItemModalOpen = false" class="w-full mt-4 rounded-lg bg-[#A64B35] text-white py-2 text-sm">
                    Añadir al pedido
                </button>
            </div>
        </div>

        <div v-if="showReservationSuccess" class="fixed inset-0 z-[95] flex items-center justify-center pointer-events-none">
            <div class="bg-white rounded-2xl shadow-xl border border-[#A64B35]/25 px-6 py-4 flex items-center gap-2">
                <CheckCircle2 class="w-5 h-5 text-[#A64B35]" />
                <p class="text-sm text-[#2F2A26]">Reserva confirmada</p>
            </div>
        </div>
    </div>
</template>
