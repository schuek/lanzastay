<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { BellIcon, CakeIcon, ChatBubbleLeftRightIcon, CheckCircleIcon, ExclamationCircleIcon, MapPinIcon, PhoneIcon, ShoppingCartIcon, SparklesIcon, UserIcon, WrenchScrewdriverIcon, XMarkIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    services: Array,
    myOrders: Array,
    activities: Array,
    myReservations: Array,
    currentRoom: String,
    currentRoomId: Number,
    sessionToken: String,
});

const notification = ref(null);
const currentView = ref('home');
const selectedServiceCategory = ref(null);
const cart = ref([]);
const isCartOpen = ref(false);
const requestedTime = ref('');
const maintenanceDescription = ref('');
const reactiveOrders = ref([...(props.myOrders ?? [])]);
const reactiveReservations = ref([...(props.myReservations ?? [])]);

const selectedActivity = ref(null);
const isActivityModalOpen = ref(false);
const isBookingModalOpen = ref(false);
const bookingSeats = ref(1);

const availableHours = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];

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

const busTours = computed(() => (props.activities ?? []).filter((activity) => activity.type === 'bus_tour'));
const hotelActivities = computed(() => (props.activities ?? []).filter((activity) => activity.type === 'hotel_activity'));
const bookingTotal = computed(() => Number((selectedActivity.value?.price ?? 0) * bookingSeats.value));

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

    const payload = {
        room_number: props.currentRoom,
        habitacion_id: props.currentRoomId,
        session_token: props.sessionToken,
        service_type: 'comida',
        total: totalPrice.value,
        cart: cart.value.map((item) => ({ id: item.id, quantity: item.quantity, price: item.price })),
    };

    axios.post('/api/orders', payload).then((response) => {
        if (response.data?.order) {
            reactiveOrders.value.unshift(response.data.order);
            router.visit(route('orders.tracking', response.data.order.id));
        }
        cart.value = [];
        isCartOpen.value = false;
        showNotification('Pedido de comida enviado.', 'success');
    }).catch(() => {
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
    axios.post('/api/activity-reservations', {
        room_number: props.currentRoom,
        session_token: props.sessionToken,
        activity_id: selectedActivity.value.id,
        seats_booked: bookingSeats.value,
    }).then((response) => {
        if (response.data?.reservation) {
            reactiveReservations.value.unshift(response.data.reservation);
        }
        isBookingModalOpen.value = false;
        showNotification('Reserva enviada a recepción.', 'success');
    }).catch((error) => {
        const message = error?.response?.data?.errors?.seats_booked?.[0] ?? 'No se pudo completar la reserva.';
        showNotification(message, 'error');
    });
};

const formatPrice = (value) => new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value || 0);
const formatDateTime = (value) => new Date(value).toLocaleString('es-ES', { dateStyle: 'medium', timeStyle: 'short' });
const formatReservationType = (type) => type === 'bus_tour' ? 'Bus' : 'Actividad';
const statusLabel = (status) => ({ pendiente: 'Pendiente', confirmada: 'Confirmada', cancelada: 'Cancelada' }[status] ?? status);
</script>

<template>
    <Head title="Conserje Virtual" />

    <div class="max-w-md mx-auto min-h-screen bg-white pb-24 relative">
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="notification" class="fixed top-5 right-5 z-50 max-w-sm w-full bg-white shadow-lg rounded-lg ring-1 ring-[#2F2A26]/10 overflow-hidden">
                <div class="p-4 flex items-center">
                    <CheckCircleIcon v-if="notification.type === 'success'" class="h-6 w-6 text-[#A64B35]" />
                    <ExclamationCircleIcon v-else class="h-6 w-6 text-red-500" />
                    <p class="ml-3 text-sm font-medium text-[#2F2A26]">{{ notification.message }}</p>
                </div>
            </div>
        </Transition>

        <div class="px-4 pt-5 pb-8">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-xl font-black text-[#2F2A26]">Habitación {{ currentRoom }}</h1>
                <span class="text-sm font-bold text-[#A64B35]">LANZASTAY</span>
            </div>

            <div v-if="currentView === 'home'" class="space-y-6">
                <div class="space-y-4">
                    <div class="relative rounded-xl h-32 overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=1200&q=80&fm=avif" alt="Servicios" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/30"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <button @click="currentView = 'services_menu'" class="bg-white rounded-full px-6 py-2 font-bold text-sm text-[#2F2A26]">Servicios</button>
                        </div>
                    </div>

                    <div class="relative rounded-xl h-32 overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1530521954074-e64f6810b32d?auto=format&fit=crop&w=1200&q=80&fm=avif" alt="Actividades" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/30"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <button @click="currentView = 'activities'" class="bg-white rounded-full px-6 py-2 font-bold text-sm text-[#2F2A26]">Actividades</button>
                        </div>
                    </div>
                </div>

                <section>
                    <h2 class="text-center text-2xl font-black text-[#2F2A26] mb-4">Sitios que te pueden gustar</h2>
                    <div class="flex overflow-x-auto gap-4 snap-x hide-scroll-bar [scrollbar-width:none] [&::-webkit-scrollbar]:hidden pb-2">
                        <article
                            v-for="tour in busTours"
                            :key="tour.id"
                            class="w-[240px] h-[320px] flex-shrink-0 relative rounded-2xl overflow-hidden snap-start bg-[#A64B35]"
                        >
                            <div class="absolute inset-0 z-0 bg-gradient-to-br from-[#A64B35] to-[#2F2A26] flex items-center justify-center p-4">
                                <span class="text-white/10 font-black text-3xl uppercase tracking-widest text-center -rotate-12 leading-none">
                                    {{ tour.name || 'TOUR' }}
                                </span>
                            </div>

                            <img
                                src="/images/timanfaya.avif"
                                :alt="tour.name"
                                class="w-full h-full object-cover relative z-10"
                                @error="$event.target.style.display='none'"
                            >

                            <div class="absolute z-20 inset-x-0 bottom-0 h-44 bg-gradient-to-t from-black/80 to-transparent pointer-events-none"></div>

                            <div class="absolute z-30 left-4 right-4 bottom-4 text-white">
                                <h3 class="text-lg font-bold mb-2">{{ tour.name || 'Timanfaya' }}</h3>
                                <div class="flex items-center gap-3 text-sm">
                                    <button @click="openActivityModal(tour)" class="inline-flex items-center gap-1 font-semibold">
                                        <MapPinIcon class="w-4 h-4" />
                                        <span>Cómo llegar</span>
                                    </button>
                                    <span class="opacity-70">|</span>
                                    <button @click="openActivityModal(tour)" class="font-semibold">Reservar Bus</button>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>
            </div>

            <div v-if="currentView === 'services_menu'" class="space-y-4">
                <button @click="selectedServiceCategory = null" class="inline-flex items-center text-sm font-bold text-[#A64B35]">&lt;- Volver</button>

                <div v-if="!selectedServiceCategory" class="grid grid-cols-2 gap-3">
                    <button @click="selectedServiceCategory = 'comida'" class="aspect-square rounded-2xl border border-[#2F2A26]/10 bg-white p-4 flex flex-col items-start justify-between">
                        <CakeIcon class="w-7 h-7 text-[#A64B35]" />
                        <span class="text-lg font-black text-[#2F2A26]">Comida</span>
                    </button>
                    <button @click="selectedServiceCategory = 'limpieza'" class="aspect-square rounded-2xl border border-[#2F2A26]/10 bg-white p-4 flex flex-col items-start justify-between">
                        <SparklesIcon class="w-7 h-7 text-[#A64B35]" />
                        <span class="text-lg font-black text-[#2F2A26]">Limpieza</span>
                    </button>
                    <button @click="selectedServiceCategory = 'mantenimiento'" class="aspect-square rounded-2xl border border-[#2F2A26]/10 bg-white p-4 flex flex-col items-start justify-between col-span-2">
                        <WrenchScrewdriverIcon class="w-7 h-7 text-[#A64B35]" />
                        <span class="text-lg font-black text-[#2F2A26]">Mantenimiento</span>
                    </button>
                </div>

                <div v-if="selectedServiceCategory === 'comida'" class="space-y-3">
                    <div v-for="service in foodServices" :key="service.id" class="bg-white rounded-xl p-4 border border-[#2F2A26]/10">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-bold text-[#2F2A26]">{{ service.name }}</h3>
                            <span class="font-bold text-[#A64B35]">{{ formatPrice(service.price) }}</span>
                        </div>
                        <p class="text-sm text-[#2F2A26]/70 mb-3">{{ service.description }}</p>
                        <button @click="addToCart(service)" class="bg-[#A64B35] text-white px-3 py-2 rounded-lg text-sm font-bold">Añadir al carrito</button>
                    </div>
                </div>

                <div v-if="selectedServiceCategory === 'limpieza'" class="space-y-3">
                    <div class="bg-white rounded-xl p-4 border border-[#2F2A26]/10 space-y-3">
                        <button @click="showNotification('Solicitud de toallas enviada.', 'success')" class="w-full rounded-lg border border-[#2F2A26]/15 px-3 py-2 text-left font-semibold text-[#2F2A26]">Pedir toallas</button>
                        <button @click="showNotification('Solicitud de cambio de sábanas enviada.', 'success')" class="w-full rounded-lg border border-[#2F2A26]/15 px-3 py-2 text-left font-semibold text-[#2F2A26]">Cambio de sábanas</button>
                        <div class="pt-1">
                            <p class="font-bold text-[#2F2A26] mb-2">Programar hora</p>
                            <select v-model="requestedTime" class="w-full rounded-lg border-[#2F2A26]/20 mb-3">
                                <option value="">Selecciona una hora</option>
                                <option v-for="hour in availableHours" :key="hour" :value="hour">{{ hour }}</option>
                            </select>
                            <button @click="submitCleaningRequest" class="w-full bg-[#A64B35] text-white rounded-lg py-2 font-bold">Enviar limpieza</button>
                        </div>
                    </div>
                </div>

                <div v-if="selectedServiceCategory === 'mantenimiento'" class="bg-white rounded-xl p-4 border border-[#2F2A26]/10">
                    <h3 class="text-lg font-black text-[#2F2A26] mb-2">Describe la avería</h3>
                    <textarea v-model="maintenanceDescription" rows="5" class="w-full rounded-lg border-[#2F2A26]/20 mb-4" placeholder="Ejemplo: el aire acondicionado no enfría."></textarea>
                    <button @click="submitMaintenanceRequest" class="w-full bg-[#A64B35] text-white rounded-lg py-3 font-bold">Enviar reporte</button>
                </div>
            </div>

            <div v-if="currentView === 'activities'" class="space-y-3">
                <article v-for="activity in hotelActivities" :key="activity.id" class="bg-white rounded-2xl border border-[#2F2A26]/10 p-3">
                    <div class="flex gap-3">
                        <div class="w-20 h-20 rounded-xl bg-black shrink-0 overflow-hidden">
                            <img v-if="activity.image_url" :src="activity.image_url" :alt="activity.name" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="text-base font-black text-[#2F2A26] uppercase">{{ activity.name }}</h3>
                                    <p class="text-xs text-[#2F2A26]/60">Al aire libre</p>
                                </div>
                                <button @click="startReservation(activity)" class="rounded-lg border border-[#A64B35] px-3 py-1 text-xs font-bold text-[#A64B35]">APUNTARSE</button>
                            </div>
                            <div class="mt-3 flex items-center gap-2">
                                <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-xs font-bold text-[#2F2A26]">{{ new Date(activity.date_time).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }) }}</span>
                                <p class="text-xs text-[#2F2A26]/70 truncate">{{ activity.description || 'Actividad guiada para huéspedes.' }}</p>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <div v-if="currentView === 'orders'" class="space-y-4">
                <div v-if="reactiveOrders.length === 0" class="bg-white rounded-xl p-6 border border-[#2F2A26]/10 text-center text-[#2F2A26]/70">No tienes solicitudes registradas.</div>
                <div v-for="order in reactiveOrders" :key="order.id" class="bg-white rounded-xl p-4 border border-[#2F2A26]/10">
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-bold text-[#2F2A26]">#{{ order.id }} - {{ order.service_type }}</span>
                        <span class="text-xs px-2 py-1 rounded-full bg-[#A64B35]/10 text-[#A64B35] font-bold uppercase">{{ order.status }}</span>
                    </div>
                    <p v-if="order.service_type === 'limpieza'" class="text-sm text-[#2F2A26]/70">Hora solicitada: {{ order.requested_time }}</p>
                    <p v-if="order.service_type === 'mantenimiento'" class="text-sm text-[#2F2A26]/70">{{ order.description }}</p>
                    <ul v-if="order.service_type === 'comida'" class="text-sm text-[#2F2A26]/70 mb-2">
                        <li v-for="service in order.services" :key="service.id">{{ service.pivot.quantity }}x {{ service.name }}</li>
                    </ul>
                    <p v-if="order.service_type === 'comida'" class="font-bold text-[#A64B35]">{{ formatPrice(order.total_price) }}</p>
                </div>
            </div>

            <div v-if="currentView === 'profile'" class="space-y-4">
                <h3 class="text-lg font-black text-[#2F2A26]">Mis Reservas</h3>
                <div v-if="reactiveReservations.length === 0" class="bg-white rounded-xl p-6 border border-[#2F2A26]/10 text-center text-[#2F2A26]/70">
                    Todavía no tienes reservas de excursiones o actividades.
                </div>
                <div v-for="reservation in reactiveReservations" :key="reservation.id" class="bg-white rounded-xl p-4 border border-[#2F2A26]/10">
                    <div class="flex justify-between items-center mb-1">
                        <p class="font-bold text-[#2F2A26]">{{ reservation.activity?.name }}</p>
                        <span class="text-xs px-2 py-1 rounded-full bg-[#A64B35]/10 text-[#A64B35] font-bold uppercase">{{ statusLabel(reservation.status) }}</span>
                    </div>
                    <p class="text-xs text-[#2F2A26]/60">{{ formatReservationType(reservation.activity?.type) }} - {{ formatDateTime(reservation.activity?.date_time) }}</p>
                    <p class="text-sm text-[#2F2A26]/70 mt-1">Plazas: {{ reservation.seats_booked }}</p>
                    <p class="text-sm font-bold text-[#A64B35]">{{ formatPrice(reservation.total_price) }}</p>
                </div>
            </div>
        </div>

        <a href="https://wa.me/123456789" target="_blank" class="fixed bottom-24 right-6 bg-[#A64B35] text-white px-4 py-3 rounded-full shadow-xl font-bold z-40 flex items-center gap-2">
            <PhoneIcon class="w-5 h-5" />
            SOS
        </a>

        <button v-if="cart.length > 0" @click="isCartOpen = !isCartOpen" class="fixed bottom-20 right-6 bg-[#2F2A26] text-white p-4 rounded-full shadow-xl z-40">
            <ShoppingCartIcon class="w-6 h-6" />
        </button>

        <div v-if="isCartOpen" class="fixed inset-0 z-50 flex justify-end">
            <div @click="isCartOpen = false" class="absolute inset-0 bg-black/40"></div>
            <div class="relative w-full max-w-md bg-white h-full shadow-2xl p-6 flex flex-col">
                <div class="flex justify-between items-center mb-4 border-b pb-3">
                    <h2 class="text-xl font-black text-[#2F2A26]">Pedido de comida</h2>
                    <button @click="isCartOpen = false"><XMarkIcon class="w-7 h-7 text-[#2F2A26]/50" /></button>
                </div>
                <div class="flex-1 overflow-y-auto space-y-3">
                    <div v-for="item in cart" :key="item.id" class="bg-[#FFFFFF] rounded-lg p-3 border border-[#2F2A26]/10 flex justify-between items-center">
                        <div>
                            <p class="font-bold text-[#2F2A26]">{{ item.name }}</p>
                            <p class="text-sm text-[#2F2A26]/70">{{ formatPrice(item.price) }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="decreaseQty(item)" class="px-2 rounded bg-white border border-[#2F2A26]/20">-</button>
                            <span class="font-bold">{{ item.quantity }}</span>
                            <button @click="increaseQty(item)" class="px-2 rounded bg-white border border-[#2F2A26]/20">+</button>
                        </div>
                    </div>
                </div>
                <div class="border-t pt-4">
                    <div class="flex justify-between mb-3 font-bold">
                        <span>Total</span>
                        <span class="text-[#A64B35]">{{ formatPrice(totalPrice) }}</span>
                    </div>
                    <button @click="submitOrder" class="w-full bg-[#A64B35] text-white rounded-lg py-3 font-bold">Confirmar pedido</button>
                </div>
            </div>
        </div>

        <nav class="fixed bottom-0 w-full max-w-md mx-auto bg-[#F9F9F9] rounded-t-3xl shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.05)] h-20 flex justify-around items-center px-6 z-50">
            <button @click="currentView = 'home'" class="text-[#A64B35]">
                <ChatBubbleLeftRightIcon class="w-7 h-7" />
            </button>
            <button @click="currentView = 'profile'" class="bg-[#5FC34B] text-white p-3 rounded-full shadow-lg -mt-6">
                <UserIcon class="w-7 h-7" />
            </button>
            <button @click="currentView = 'orders'" class="text-[#A64B35]">
                <BellIcon class="w-7 h-7" />
            </button>
        </nav>

        <div v-if="isActivityModalOpen && selectedActivity" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="isActivityModalOpen = false"></div>
            <div class="relative bg-white rounded-2xl border border-[#2F2A26]/10 shadow-xl w-full h-[88vh] max-w-md overflow-hidden flex flex-col">
                <div class="h-64 bg-black">
                    <img
                        :src="selectedActivity.image_url || 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=1200&q=80&fm=avif'"
                        :alt="selectedActivity.name"
                        class="w-full h-full object-cover"
                    >
                </div>
                <div class="p-5 flex-1 overflow-y-auto">
                    <h3 class="text-2xl font-black text-[#2F2A26]">{{ selectedActivity.name }}</h3>
                    <p class="text-sm text-[#2F2A26]/70 mt-3">{{ selectedActivity.description }}</p>
                    <p class="text-xs text-[#2F2A26]/60 mt-2">{{ formatDateTime(selectedActivity.date_time) }}</p>
                </div>
                <div class="p-5 pt-3 border-t border-[#2F2A26]/10 space-y-2">
                    <a class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-[#2F2A26]/15 px-3 py-3 font-bold text-[#2F2A26]" :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(selectedActivity.name)}`" target="_blank">
                        <MapPinIcon class="w-5 h-5" />
                        Cómo llegar
                    </a>
                    <button @click="startReservation(selectedActivity)" class="w-full bg-[#A64B35] text-white rounded-lg py-3 font-bold">Reservar Bus</button>
                </div>
            </div>
        </div>

        <div v-if="isBookingModalOpen && selectedActivity" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="isBookingModalOpen = false"></div>
            <div class="relative bg-white rounded-xl border border-[#2F2A26]/10 shadow-xl p-5 w-full max-w-sm">
                <h3 class="text-lg font-black text-[#2F2A26]">Confirmar reserva</h3>
                <p class="text-sm text-[#2F2A26]/70 mt-1">{{ selectedActivity.name }}</p>
                <label class="block text-sm text-[#2F2A26]/70 mt-4">Número de plazas</label>
                <input v-model.number="bookingSeats" type="number" min="1" class="w-full rounded-lg border-[#2F2A26]/20 mt-1">
                <p class="text-sm font-bold text-[#A64B35] mt-3">Total: {{ formatPrice(bookingTotal) }}</p>
                <button @click="confirmReservation" class="w-full mt-4 bg-[#A64B35] text-white rounded-lg py-2 font-bold">Confirmar reserva</button>
            </div>
        </div>
    </div>
</template>
