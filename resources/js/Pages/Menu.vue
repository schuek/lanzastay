<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowLeftIcon, BellIcon, CakeIcon, CheckCircleIcon, ClockIcon, CurrencyEuroIcon, ExclamationCircleIcon, MapPinIcon, PhoneIcon, ShoppingCartIcon, SparklesIcon, UserGroupIcon, UserIcon, WrenchScrewdriverIcon, XMarkIcon, HomeIcon, TicketIcon, QuestionMarkCircleIcon } from '@heroicons/vue/24/solid';
import LanguageSelector from '@/Components/LanguageSelector.vue';

const props = defineProps({
    services: Array,
    myOrders: Array,
    activities: Array,
    myReservations: Array,
    currentRoom: String,
    currentRoomId: Number,
    sessionToken: String,
    guestEmail: String,
    myActivityBookings: Array,
});

const notification = ref(null);
const currentTab = ref('home');
const selectedServiceCategory = ref(null);
const cart = ref([]);
const isCartOpen = ref(false);
const requestedTime = ref('');
const maintenanceDescription = ref('');
const reactiveOrders = ref([...(props.myOrders ?? [])]);
const reactiveReservations = ref([...(props.myReservations ?? [])]);
const reactiveActivityBookings = ref([...(props.myActivityBookings ?? [])]);
let pollingInterval = null;
const isChatOpen = ref(false);
const showReservationSuccess = ref(false);
const isHelpModalOpen = ref(false);

// Navegación con historial
const tabHistory = ref(['home']);

// Listener para botón atrás del navegador
const handlePopState = (event) => {
    if (tabHistory.value.length > 1) {
        tabHistory.value.pop();
        currentTab.value = tabHistory.value[tabHistory.value.length - 1];
    } else {
        // Si solo queda 'home', permitir salir de la página
        window.history.back();
    }
};

// Cambiar de pestaña con historial
const changeTab = (newTab) => {
    if (currentTab.value !== newTab) {
        currentTab.value = newTab;
        tabHistory.value.push(newTab);
        window.history.pushState({ tab: newTab }, '', `#${newTab}`);
    }
};

onMounted(() => {
    // Configurar listener para botón atrás
    window.addEventListener('popstate', handlePopState);
    
    // Inicializar historial
    const hashTab = window.location.hash.slice(1);
    if (hashTab && ['home', 'services', 'activities', 'orders', 'profile'].includes(hashTab)) {
        currentTab.value = hashTab;
        tabHistory.value = [hashTab];
        window.history.replaceState({ tab: hashTab }, '', `#${hashTab}`);
    } else {
        window.history.replaceState({ tab: 'home' }, '', '#home');
    }
    
    fetchMyOrders();

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

    pollingInterval = setInterval(fetchMyOrders, 5000);
});

onBeforeUnmount(() => {
    window.removeEventListener('popstate', handlePopState);
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});

const selectedActivity = ref(null);
const isActivityModalOpen = ref(false);
const isBookingModalOpen = ref(false);
const bookingSeats = ref(1);
const actividades = ref([
    { id: 1, titulo: 'Circuito Spa & Relax', descripcion: 'Relájate en nuestro circuito de aguas termales, sauna y baño turco. Ideal para desconectar.', categoria: 'Bienestar', precio: 25, horario: '10:00 - 20:00', plazas_totales: 15, plazas_disponibles: 8, imagen: '/images/spa.avif' },
    { id: 2, titulo: 'Yoga al Amanecer', descripcion: 'Empieza el día con energía y paz interior frente al mar. Apto para todos los niveles.', categoria: 'Deportes', precio: 0, horario: '08:00 - 09:00', plazas_totales: 20, plazas_disponibles: 12, imagen: '/images/yoga.avif' },
    { id: 3, titulo: 'Acceso Gimnasio', descripcion: 'Mantente en forma durante tus vacaciones con nuestras máquinas de última generación.', categoria: 'Deportes', precio: 0, horario: '06:00 - 23:00', plazas_totales: 30, plazas_disponibles: 30, imagen: '/images/gym.avif' },
    { id: 4, titulo: 'Música en Vivo: Noche Acústica', descripcion: 'Disfruta de una velada mágica con artistas locales en nuestra terraza principal.', categoria: 'Entretenimiento y Cultura', precio: 0, horario: '21:00 - 23:30', plazas_totales: 50, plazas_disponibles: 25, imagen: '/images/musica.avif' },
    { id: 5, titulo: 'Búsqueda del Tesoro Pirata', descripcion: '¡Los más pequeños se divertirán buscando pistas por todo el hotel para encontrar el cofre oculto!', categoria: 'Actividades para niños', precio: 0, horario: '11:00 - 13:00', plazas_totales: 20, plazas_disponibles: 5, imagen: '/images/tesoro.avif' }
]);
const selectedActividadReserva = ref(null);
const isReservaModalOpen = ref(false);
const cantidadReserva = ref(1);

const availableHours = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];

// Chatbot draggable completo
const chatbotPos = ref({ bottom: 100, right: 20 });
let isDragging = false;
let startX, startY, initialBottom, initialRight;

const startDragChatbot = (e) => {
    isDragging = true;
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    startX = clientX;
    startY = clientY;
    initialRight = chatbotPos.value.right;
    initialBottom = chatbotPos.value.bottom;
    
    document.addEventListener('mousemove', onDragChatbot);
    document.addEventListener('touchmove', onDragChatbot, { passive: false });
    document.addEventListener('mouseup', stopDragChatbot);
    document.addEventListener('touchend', stopDragChatbot);
    
    e.preventDefault();
};

const onDragChatbot = (e) => {
    if (!isDragging) return;
    e.preventDefault();
    
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    
    const deltaX = clientX - startX;
    const deltaY = clientY - startY;
    
    // Calcular nueva posición
    let newRight = initialRight - deltaX;
    let newBottom = initialBottom - deltaY;
    
    // Limitar dentro de la pantalla
    const maxRight = window.innerWidth - 80; // 80px es el ancho del botón
    const maxBottom = window.innerHeight - 80; // 80px es el alto del botón
    
    newRight = Math.max(0, Math.min(newRight, maxRight));
    newBottom = Math.max(0, Math.min(newBottom, maxBottom));
    
    chatbotPos.value.right = newRight;
    chatbotPos.value.bottom = newBottom;
};

const stopDragChatbot = () => {
    isDragging = false;
    document.removeEventListener('mousemove', onDragChatbot);
    document.removeEventListener('touchmove', onDragChatbot);
    document.removeEventListener('mouseup', stopDragChatbot);
    document.removeEventListener('touchend', stopDragChatbot);
};


const fetchMyOrders = async () => {
    if (!props.currentRoom || !props.sessionToken) return;

    try {
        const response = await axios.get(route('api.orders.my'), {
            params: {
                room_number: props.currentRoom,
                session_token: props.sessionToken,
            },
        });

        reactiveOrders.value = response.data?.orders ?? [];
    } catch (error) {
        console.error('No se pudieron refrescar los pedidos de la sesion.', error);
    }
};

const showNotification = (message, type = 'success') => {
    notification.value = { message, type };
    setTimeout(() => {
        notification.value = null;
    }, 3000);
};

const totalPrice = computed(() => cart.value.reduce((acc, item) => acc + (item.price * item.quantity), 0));
const activeOrder = computed(() => {
    return reactiveOrders.value.find(o => o.status !== 'completado' && o.status !== 'cancelada');
});
const foodServices = computed(() => {
    return (props.services ?? []).filter((service) => {
        const explicitType = (service.service_type ?? '').toString().toLowerCase();
        const categoryName = (service.category?.name ?? '').toString().toLowerCase();
        return explicitType === 'comida' || categoryName.includes('comida') || categoryName.includes('restaurante');
    });
});

const busTours = computed(() => (props.activities ?? []).filter((activity) => activity.type === 'bus_tour'));
const actividadesGenerales = computed(() => actividades.value.filter((activity) => activity.categoria !== 'Actividades para niños'));
const actividadesNinos = computed(() => actividades.value.filter((activity) => activity.categoria === 'Actividades para niños'));
const bookingTotal = computed(() => Number((selectedActivity.value?.price ?? 0) * bookingSeats.value));
const precioTotalReserva = computed(() => Number((selectedActividadReserva.value?.precio ?? 0) * cantidadReserva.value));

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

const openReservaModal = (actividad) => {
    selectedActividadReserva.value = actividad;
    cantidadReserva.value = 1;
    isReservaModalOpen.value = true;
};

const confirmarReservaActividad = () => {
    if (!selectedActividadReserva.value) return;

    const maxSeats = selectedActividadReserva.value.plazas_disponibles;
    if (cantidadReserva.value < 1 || cantidadReserva.value > maxSeats) {
        showNotification('La cantidad seleccionada no es válida.', 'error');
        return;
    }

    axios.post(route('reservas.store'), {
        room_number: props.currentRoom,
        session_token: props.sessionToken,
        actividad_id: selectedActividadReserva.value.id,
        titulo: selectedActividadReserva.value.titulo,
        horario: selectedActividadReserva.value.horario,
        precio: selectedActividadReserva.value.precio,
        num_personas: cantidadReserva.value,
        plazas_disponibles: selectedActividadReserva.value.plazas_disponibles,
    }).then((response) => {
        isReservaModalOpen.value = false;
        if (response.data?.reservation) {
            reactiveActivityBookings.value.unshift(response.data.reservation);
        }
        showReservationSuccess.value = true;
        setTimeout(() => {
            showReservationSuccess.value = false;
        }, 2000);
    }).catch((error) => {
        const message = error?.response?.data?.errors?.num_personas?.[0]
            ?? error?.response?.data?.errors?.guest_email?.[0]
            ?? 'No se pudo completar la reserva.';
        showNotification(message, 'error');
    });
};

const adjustCantidadReserva = () => {
    const maxSeats = selectedActividadReserva.value?.plazas_disponibles ?? 1;
    if (cantidadReserva.value < 1) cantidadReserva.value = 1;
    if (cantidadReserva.value > maxSeats) cantidadReserva.value = maxSeats;
};

const formatPrice = (value) => new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value || 0);
const formatDateTime = (value) => new Date(value).toLocaleString('es-ES', { dateStyle: 'medium', timeStyle: 'short' });
const formatReservationType = (type) => type === 'bus_tour' ? 'Bus' : 'Actividad';
const statusLabel = (status) => ({ pendiente: 'Pendiente', confirmada: 'Confirmada', cancelada: 'Cancelada' }[status] ?? status);
const orderStatusLabel = (order) => {
    const labelsByType = {
        comida: {
            recibido: 'Pendiente',
            en_proceso: 'En cocina',
            completado: 'Completado',
        },
        limpieza: {
            recibido: 'Pendiente',
            en_proceso: 'En limpieza',
            completado: 'Completado',
        },
        mantenimiento: {
            recibido: 'Pendiente',
            en_proceso: 'En revisión',
            completado: 'Completado',
        },
    };

    return labelsByType[order.service_type]?.[order.status] ?? order.status;
};
const orderStatusClass = (status) => status === 'completado'
    ? 'text-gray-500 bg-gray-100'
    : 'text-[#A64B35] bg-[#A64B35]/10';
</script>

<template>
    <Head title="Conserje Virtual" />

    <div class="max-w-md mx-auto min-h-screen bg-white pb-24 relative">
        <div v-if="activeOrder" @mousedown="startDrag" @touchstart="startDrag" :style="{ top: widgetPos.top + 'px', left: widgetPos.left + 'px', position: 'fixed' }" class="z-50 w-[90%] max-w-sm bg-[#2F2A26] rounded-2xl shadow-2xl p-4 flex items-center justify-between border border-white/10 touch-none cursor-move">
            <div class="flex items-center gap-3 text-white pointer-events-none">
                <ClockIcon class="w-8 h-8 text-[#A64B35]" />
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-gray-400">En curso</p>
                    <p class="font-black text-sm">{{ activeOrder.service_type }}</p>
                </div>
            </div>
            <button @click.stop="router.visit(route('orders.tracking', activeOrder.id))" class="bg-[#A64B35] text-white text-xs font-bold px-4 py-2 rounded-xl">
                Ver estado
            </button>
        </div>
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

        <div class="px-4 pt-4 pb-24">
            <!-- Header con ayuda -->
            <div class="flex items-center justify-between mb-6 pb-3 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <span class="text-lg font-black text-gray-900 tracking-tight">LANZA<span class="text-gray-600">STAY</span></span>
                    <span class="text-xs text-gray-500 font-medium">Habitación {{ currentRoom }}</span>
                </div>
                <button @click="isHelpModalOpen = true" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
                    <PhoneIcon class="w-4 h-4 text-gray-700" />
                    <span class="text-xs font-medium text-gray-700">Ayuda</span>
                </button>
            </div>

            <div v-if="currentTab === 'home'" class="space-y-5">
                <!-- Tarjetas verticales compactas -->
                <div class="space-y-3">
                    <button @click="changeTab('services')" class="w-full relative h-32 rounded-xl overflow-hidden group shadow-sm">
                        <img src="https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=800&q=80&fm=avif" alt="Servicios del Hotel" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/40 to-transparent"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-white text-lg font-bold text-center px-4">Servicios</span>
                        </div>
                    </button>

                    <button @click="changeTab('activities')" class="w-full relative h-32 rounded-xl overflow-hidden group shadow-sm">
                        <img src="https://images.unsplash.com/photo-1530521954074-e64f6810b32d?auto=format&fit=crop&w=800&q=80&fm=avif" alt="Actividades" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/40 to-transparent"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-white text-lg font-bold text-center px-4">Actividades</span>
                        </div>
                    </button>
                </div>

                <!-- Turismo Recomendado -->
                <section>
                    <h2 class="text-center text-lg font-bold text-gray-900 mb-3">Turismo</h2>
                    <div class="flex overflow-x-auto gap-3 snap-x hide-scroll-bar [scrollbar-width:none] [&::-webkit-scrollbar]:hidden pb-2">
                        <article
                            v-for="tour in busTours"
                            :key="tour.id"
                            class="w-[200px] h-[280px] flex-shrink-0 relative rounded-xl overflow-hidden snap-start bg-white shadow-sm border border-gray-200"
                        >
                            <div class="relative h-36">
                                <img
                                    :src="tour.image_url || 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=600&q=80&fm=avif'"
                                    :alt="tour.name"
                                    class="w-full h-full object-cover"
                                >
                                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                            </div>

                            <div class="p-3">
                                <h3 class="text-sm font-bold text-gray-900 mb-2 truncate">{{ tour.name || 'Timanfaya' }}</h3>
                                <div class="flex gap-1.5">
                                    <a 
                                        :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(tour.name + ' Lanzarote')}`" 
                                        target="_blank"
                                        class="flex-1 inline-flex items-center justify-center gap-1 rounded-md border border-gray-300 px-2 py-1.5 text-[10px] font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                                    >
                                        <MapPinIcon class="w-3 h-3" />
                                        <span>Cómo llegar</span>
                                    </a>
                                    <button 
                                        @click="openActivityModal(tour)" 
                                        class="flex-1 inline-flex items-center justify-center gap-1 rounded-md bg-blue-600 text-white px-2 py-1.5 text-[10px] font-medium hover:bg-blue-700 transition-colors"
                                    >
                                        <TicketIcon class="w-3 h-3" />
                                        <span>Reservar</span>
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>
            </div>

            <!-- SECCIÓN SERVICIOS -->
            <div v-if="currentTab === 'services'" class="space-y-4">
                <button v-if="selectedServiceCategory" @click="selectedServiceCategory = null" class="mb-4 inline-flex items-center gap-2 text-xs font-medium text-gray-600 bg-gray-100 px-3 py-2 rounded-lg">
                    <ArrowLeftIcon class="w-3 h-3" /> 
                    Volver
                </button>

                <!-- Grid limpio para servicios -->
                <div v-if="!selectedServiceCategory" class="grid grid-cols-3 gap-3">
                    <button @click="selectedServiceCategory = 'restaurante'" class="relative aspect-square rounded-xl overflow-hidden group bg-white border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="absolute inset-0 bg-gradient-to-br from-orange-50 to-orange-100"></div>
                        <div class="absolute inset-0 flex flex-col items-center justify-center p-2">
                            <CakeIcon class="w-6 h-6 text-orange-600 mb-1.5" />
                            <span class="text-xs font-medium text-gray-800 text-center">Restaurante</span>
                        </div>
                    </button>
                    
                    <button @click="selectedServiceCategory = 'limpieza'" class="relative aspect-square rounded-xl overflow-hidden group bg-white border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-blue-100"></div>
                        <div class="absolute inset-0 flex flex-col items-center justify-center p-2">
                            <SparklesIcon class="w-6 h-6 text-blue-600 mb-1.5" />
                            <span class="text-xs font-medium text-gray-800 text-center">Limpieza</span>
                        </div>
                    </button>
                    
                    <button @click="selectedServiceCategory = 'mantenimiento'" class="relative aspect-square rounded-xl overflow-hidden group bg-white border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="absolute inset-0 bg-gradient-to-br from-gray-50 to-gray-100"></div>
                        <div class="absolute inset-0 flex flex-col items-center justify-center p-2">
                            <WrenchScrewdriverIcon class="w-6 h-6 text-gray-600 mb-1.5" />
                            <span class="text-xs font-medium text-gray-800 text-center">Mantenimiento</span>
                        </div>
                    </button>
                </div>

                <!-- Contenido del Restaurante -->
                <div v-if="selectedServiceCategory === 'restaurante'" class="space-y-3">
                    <h3 class="text-base font-bold text-gray-900 mb-3">Menú del Restaurante</h3>
                    <div v-for="service in foodServices" :key="service.id" class="bg-white rounded-lg p-3 border border-gray-200">
                        <div class="flex justify-between items-start mb-2">
                            <h4 class="text-sm font-semibold text-gray-900">{{ service.name }}</h4>
                            <span class="text-sm font-bold text-orange-600">{{ formatPrice(service.price) }}</span>
                        </div>
                        <p class="text-xs text-gray-600 mb-2 line-clamp-2">{{ service.description }}</p>
                        <button @click="addToCart(service)" class="bg-orange-600 text-white px-3 py-1.5 rounded-md text-xs font-medium hover:bg-orange-700 transition-colors">Añadir</button>
                    </div>
                </div>

                <div v-if="selectedServiceCategory === 'limpieza'" class="space-y-3">
                    <div class="bg-white rounded-lg p-3 border border-gray-200 space-y-2">
                        <button @click="showNotification('Solicitud de toallas enviada.', 'success')" class="w-full text-left px-3 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">Pedir toallas</button>
                        <button @click="showNotification('Solicitud de cambio de sábanas enviada.', 'success')" class="w-full text-left px-3 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">Cambio de sábanas</button>
                        <div class="pt-2 border-t border-gray-100">
                            <p class="text-sm font-medium text-gray-900 mb-2">Programar hora</p>
                            <select v-model="requestedTime" class="w-full rounded-md border-gray-300 text-sm mb-2 px-3 py-2">
                                <option value="">Selecciona una hora</option>
                                <option v-for="hour in availableHours" :key="hour" :value="hour">{{ hour }}</option>
                            </select>
                            <button @click="submitCleaningRequest" class="w-full bg-blue-600 text-white rounded-md py-2 text-sm font-medium hover:bg-blue-700 transition-colors">Enviar solicitud</button>
                        </div>
                    </div>
                </div>

                <div v-if="selectedServiceCategory === 'mantenimiento'" class="bg-white rounded-lg p-3 border border-gray-200">
                    <h3 class="text-sm font-bold text-gray-900 mb-2">Reportar avería</h3>
                    <textarea v-model="maintenanceDescription" rows="4" class="w-full rounded-md border-gray-300 text-sm mb-3 px-3 py-2" placeholder="Describe el problema..."></textarea>
                    <button @click="submitMaintenanceRequest" class="w-full bg-gray-800 text-white rounded-md py-2 text-sm font-medium hover:bg-gray-900 transition-colors">Enviar reporte</button>
                </div>
            </div>

            <div v-if="currentTab === 'activities'" class="space-y-8">
                <section>
                    <h3 class="text-lg font-black text-[#2F2A26] mb-3">Actividades del hotel</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <article v-for="activity in actividadesGenerales" :key="activity.id" class="bg-white rounded-2xl border border-[#2F2A26]/10 overflow-hidden shadow-sm">
                            <div class="relative h-40">
                                <img :src="activity.imagen" :alt="activity.titulo" class="w-full h-full object-cover">
                                <span class="absolute top-3 left-3 bg-[#2F2A26]/85 text-white text-[11px] font-bold px-2.5 py-1 rounded-full">
                                    {{ activity.categoria }}
                                </span>
                            </div>
                            <div class="p-4">
                                <h4 class="text-base font-black text-[#2F2A26]">{{ activity.titulo }}</h4>
                                <p class="text-sm text-[#2F2A26]/70 mt-2 line-clamp-3">{{ activity.descripcion }}</p>

                                <div class="mt-4 space-y-2 text-sm text-[#2F2A26]/80">
                                    <div class="flex items-center gap-2">
                                        <ClockIcon class="w-4 h-4 text-[#A64B35]" />
                                        <span>{{ activity.horario }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <CurrencyEuroIcon class="w-4 h-4 text-[#A64B35]" />
                                        <span>{{ activity.precio === 0 ? 'Gratis' : formatPrice(activity.precio) }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <UserGroupIcon class="w-4 h-4 text-[#A64B35]" />
                                        <span>{{ activity.plazas_disponibles }} / {{ activity.plazas_totales }} plazas</span>
                                    </div>
                                </div>

                                <button @click="openReservaModal(activity)" class="w-full mt-4 bg-[#A64B35] text-white rounded-lg py-2.5 font-bold">
                                    {{ $t('actions.book') }}
                                </button>
                            </div>
                        </article>
                    </div>
                </section>

                <section v-if="actividadesNinos.length > 0" class="bg-[#A64B35]/5 border border-[#A64B35]/20 rounded-2xl p-4">
                    <h3 class="text-lg font-black text-[#2F2A26] mb-3">Niños</h3>
                    <div class="grid grid-cols-1 gap-4">
                        <article v-for="activity in actividadesNinos" :key="activity.id" class="bg-white rounded-2xl border-2 border-[#A64B35]/35 overflow-hidden shadow-sm">
                            <div class="relative h-44">
                                <img :src="activity.imagen" :alt="activity.titulo" class="w-full h-full object-cover">
                                <span class="absolute top-3 left-3 bg-[#A64B35] text-white text-[11px] font-bold px-2.5 py-1 rounded-full">
                                    {{ activity.categoria }}
                                </span>
                            </div>
                            <div class="p-4">
                                <h4 class="text-lg font-black text-[#2F2A26]">{{ activity.titulo }}</h4>
                                <p class="text-sm text-[#2F2A26]/70 mt-2 line-clamp-3">{{ activity.descripcion }}</p>
                                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-2 text-sm text-[#2F2A26]/80">
                                    <p class="inline-flex items-center gap-2"><ClockIcon class="w-4 h-4 text-[#A64B35]" />{{ activity.horario }}</p>
                                    <p class="inline-flex items-center gap-2"><CurrencyEuroIcon class="w-4 h-4 text-[#A64B35]" />{{ activity.precio === 0 ? 'Gratis' : formatPrice(activity.precio) }}</p>
                                    <p class="inline-flex items-center gap-2"><UserGroupIcon class="w-4 h-4 text-[#A64B35]" />{{ activity.plazas_disponibles }} disponibles</p>
                                </div>
                                <button @click="openReservaModal(activity)" class="w-full mt-4 bg-[#A64B35] text-white rounded-lg py-2.5 font-bold">
                                    {{ $t('actions.book') }}
                                </button>
                            </div>
                        </article>
                    </div>
                </section>
            </div>

            <div v-if="currentTab === 'orders'" class="space-y-4">
                <div v-if="reactiveOrders.length === 0" class="bg-white rounded-xl p-6 border border-[#2F2A26]/10 text-center text-[#2F2A26]/70">No tienes solicitudes registradas.</div>
                <div v-for="order in reactiveOrders" :key="order.id" class="bg-white rounded-xl p-4 border border-[#2F2A26]/10">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <div>
                            <span class="font-bold text-[#2F2A26]">#{{ order.id }} - {{ order.service_type }}</span>
                            <p class="text-xs text-[#2F2A26]/55 mt-1">{{ formatDateTime(order.created_at) }}</p>
                        </div>
                        <span :class="orderStatusClass(order.status)" class="text-xs px-2.5 py-1 rounded-full font-bold whitespace-nowrap">
                            {{ orderStatusLabel(order) }}
                        </span>
                    </div>
                    <p v-if="order.service_type === 'limpieza'" class="text-sm text-[#2F2A26]/70">Hora solicitada: {{ order.requested_time }}</p>
                    <p v-if="order.service_type === 'mantenimiento'" class="text-sm text-[#2F2A26]/70">{{ order.description }}</p>
                    <ul v-if="order.service_type === 'comida'" class="text-sm text-[#2F2A26]/70 mb-2">
                        <li v-for="service in order.services" :key="service.id">{{ service.pivot.quantity }}x {{ service.name }}</li>
                    </ul>
                    <p v-if="order.service_type === 'comida'" class="font-bold text-[#A64B35]">{{ formatPrice(order.total_price) }}</p>
                    <div v-if="order.status === 'en_proceso'" class="mt-3 flex justify-end">
                        <button
                            @click="router.visit(route('orders.tracking', order.id))"
                            class="text-xs font-semibold text-[#A64B35] hover:text-[#8B3E2D] transition-colors"
                        >
                            Ver detalles
                        </button>
                    </div>
                </div>

                <div class="pt-2">
                    <h3 class="text-base font-black text-[#2F2A26] mb-3">Mis Actividades</h3>
                    <div v-if="reactiveActivityBookings.length === 0" class="bg-white rounded-xl p-4 border border-[#2F2A26]/10 text-sm text-[#2F2A26]/70">
                        Aún no tienes reservas de actividades registradas.
                    </div>
                    <div v-for="booking in reactiveActivityBookings" :key="booking.id" class="bg-white rounded-xl p-4 border border-[#2F2A26]/10 mb-2">
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-[#2F2A26]">{{ booking.titulo_actividad ?? 'Actividad #' + booking.actividad_id }}</p>
                            <span class="text-sm font-bold text-[#A64B35]">{{ formatPrice(booking.precio_total) }}</span>
                        </div>
                        <p class="text-xs text-[#2F2A26]/60 mt-1">Horario: {{ booking.horario_actividad ?? '-' }}</p>
                        <p class="text-xs text-[#2F2A26]/60">Personas: {{ booking.num_personas }}</p>
                    </div>
                </div>
            </div>

            <div v-if="currentTab === 'profile'" class="space-y-6">
                <!-- Información del perfil -->
                <div class="bg-white rounded-xl p-6 border border-[#2F2A26]/10">
                    <h3 class="text-lg font-black text-[#2F2A26] mb-4">Mi Información</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm text-[#2F2A26]/60 block mb-1">Email del cliente</label>
                            <p class="font-semibold text-[#2F2A26]">{{ guestEmail || 'No disponible' }}</p>
                        </div>
                        <div>
                            <label class="text-sm text-[#2F2A26]/60 block mb-1">Idioma</label>
                            <LanguageSelector />
                        </div>
                        <button class="w-full bg-[#2F2A26] text-white rounded-lg py-3 font-semibold hover:bg-[#2F2A26]/90 transition-colors">
                            📄 Ver Factura PDF
                        </button>
                    </div>
                </div>

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

        <!-- CHATBOT FLOTANTE DRAGGABLE -->
        <button 
            @mousedown="startDragChatbot" 
            @touchstart="startDragChatbot"
            @click="!isDragging && (isChatOpen = !isChatOpen)"
            :style="{ bottom: chatbotPos.bottom + 'px', right: chatbotPos.right + 'px' }"
            class="fixed bg-gray-800 text-white p-3 rounded-full shadow-lg z-40 hover:bg-gray-700 transition-colors touch-none"
        >
            <ChatBubbleLeftRightIcon class="w-5 h-5" />
        </button>

        <!-- Ventana de Chat -->
        <div v-if="isChatOpen" class="fixed inset-0 z-50 flex items-end justify-end p-4">
            <div @click="isChatOpen = false" class="absolute inset-0 bg-black/40"></div>
            <div class="relative w-full max-w-sm bg-white rounded-t-2xl shadow-2xl h-[70vh] flex flex-col">
                <!-- Header del chat -->
                <div class="bg-gray-800 text-white p-4 rounded-t-2xl">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-base">Asistente Virtual</h3>
                        <button @click="isChatOpen = false" class="text-white/80 hover:text-white">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>
                </div>
                
                <!-- Botón destacado de contacto con recepción -->
                <div class="p-4 border-b border-gray-200">
                    <button class="w-full bg-blue-600 text-white rounded-lg py-2.5 text-sm font-medium flex items-center justify-center gap-2 hover:bg-blue-700 transition-colors">
                        <PhoneIcon class="w-4 h-4" />
                        <span>Contactar con Recepción</span>
                    </button>
                </div>
                
                <!-- Área de mensajes -->
                <div class="flex-1 p-4 overflow-y-auto">
                    <div class="space-y-3">
                        <div class="bg-gray-100 rounded-lg p-3 max-w-[80%]">
                            <p class="text-sm text-gray-800">¡Hola! ¿En qué puedo ayudarte hoy?</p>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-3 max-w-[80%] ml-auto">
                            <p class="text-sm text-gray-800">Necesito información sobre los servicios del hotel</p>
                        </div>
                    </div>
                </div>
                
                <!-- Input de mensaje -->
                <div class="p-4 border-t border-gray-200">
                    <div class="flex gap-2">
                        <input type="text" placeholder="Escribe tu mensaje..." class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                            Enviar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Ayuda -->
        <div v-if="isHelpModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="isHelpModalOpen = false" class="absolute inset-0 bg-black/40"></div>
            <div class="relative bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Ayuda y Contacto</h3>
                    <button @click="isHelpModalOpen = false" class="text-gray-400 hover:text-gray-600">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
                
                <div class="space-y-3">
                    <a href="tel:+123456789" class="w-full flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                        <PhoneIcon class="w-5 h-5 text-gray-600" />
                        <div class="text-left">
                            <p class="text-sm font-medium text-gray-900">Llamar a Recepción</p>
                            <p class="text-xs text-gray-500">+123 456 789</p>
                        </div>
                    </a>
                    
                    <a href="https://wa.me/123456789" target="_blank" class="w-full flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                        <ChatBubbleLeftRightIcon class="w-5 h-5 text-gray-600" />
                        <div class="text-left">
                            <p class="text-sm font-medium text-gray-900">WhatsApp</p>
                            <p class="text-xs text-gray-500">Chat instantáneo</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Botón del carrito -->
        <button v-if="cart.length > 0" @click="isCartOpen = !isCartOpen" class="fixed bottom-20 right-6 bg-gray-800 text-white p-3 rounded-full shadow-lg z-40">
            <ShoppingCartIcon class="w-5 h-5" />
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
                <div class="border-t pt-4 pb-28 md:pb-4 bg-white relative z-50">
                    <div class="flex justify-between mb-3 font-bold">
                        <span>Total</span>
                        <span class="text-[#A64B35]">{{ formatPrice(totalPrice) }}</span>
                    </div>
                    <button @click="submitOrder" class="w-full bg-[#A64B35] text-white rounded-lg py-3 font-bold shadow-lg">
                        Confirmar pedido
                    </button>
                </div>
            </div>
        </div>

        <!-- BARRA DE NAVEGACIÓN INFERIOR FIJA -->
        <nav class="fixed bottom-0 w-full max-w-md mx-auto bg-white border-t border-gray-200 h-16 flex justify-around items-center px-4 z-50">
            <button @click="changeTab('home')" :class="currentTab === 'home' ? 'text-blue-600' : 'text-gray-400'" class="flex flex-col items-center gap-1 transition-colors">
                <HomeIcon class="w-5 h-5" />
                <span class="text-[10px] font-medium">Inicio</span>
            </button>
            
            <button @click="changeTab('orders')" :class="currentTab === 'orders' ? 'text-blue-600' : 'text-gray-400'" class="flex flex-col items-center gap-1 transition-colors">
                <TicketIcon class="w-5 h-5" />
                <span class="text-[10px] font-medium">Mis Pedidos</span>
            </button>
            
            <button @click="changeTab('profile')" :class="currentTab === 'profile' ? 'text-blue-600' : 'text-gray-400'" class="flex flex-col items-center gap-1 transition-colors">
                <UserIcon class="w-5 h-5" />
                <span class="text-[10px] font-medium">Mi Perfil</span>
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

        <div v-if="isReservaModalOpen && selectedActividadReserva" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="isReservaModalOpen = false"></div>
            <div class="relative bg-white rounded-2xl border border-[#2F2A26]/10 shadow-xl p-5 w-full max-w-sm">
                <h3 class="text-lg font-black text-[#2F2A26]">{{ selectedActividadReserva.titulo }}</h3>
                <p class="text-sm text-[#2F2A26]/70 mt-1 inline-flex items-center gap-2">
                    <ClockIcon class="w-4 h-4 text-[#A64B35]" />
                    {{ selectedActividadReserva.horario }}
                </p>

                <label class="block text-sm text-[#2F2A26]/70 mt-4">¿Cuántos son?</label>
                <input
                    v-model.number="cantidadReserva"
                    @input="adjustCantidadReserva"
                    type="number"
                    min="1"
                    :max="selectedActividadReserva.plazas_disponibles"
                    class="w-full rounded-lg border-[#2F2A26]/20 mt-1"
                >
                <p class="text-xs text-[#2F2A26]/55 mt-1">Máximo {{ selectedActividadReserva.plazas_disponibles }} personas.</p>

                <p class="text-sm font-bold text-[#A64B35] mt-3">
                    Precio Total: {{ selectedActividadReserva.precio === 0 ? 'Gratis' : formatPrice(precioTotalReserva) }}
                </p>
                <button @click="confirmarReservaActividad" class="w-full mt-4 bg-[#A64B35] text-white rounded-lg py-2.5 font-bold">
                    {{ $t('actions.confirm') }}
                </button>
            </div>
        </div>

        <div v-if="showReservationSuccess" class="fixed inset-0 z-[70] flex items-center justify-center pointer-events-none">
            <div class="bg-white rounded-2xl shadow-2xl border border-green-200 px-8 py-6 flex items-center gap-3">
                <CheckCircleIcon class="w-8 h-8 text-green-600" />
                <p class="text-lg font-black text-[#2F2A26]">¡Reserva Confirmada!</p>
            </div>
        </div>
    </div>
</template>
