<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { loadStripe } from '@stripe/stripe-js';
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
    Lock,
} from 'lucide-vue-next';
import LanguageSelector from '@/Components/LanguageSelector.vue';
import { AMENITY_CODES, resolveAmenityFromDescription, serviceTypeForAmenityCode } from '@/constants/amenityRequests';
import GuestAmenitiesPanel from '@/Components/Guest/GuestAmenitiesPanel.vue';
import ChatbotWidget from '@/Components/ChatbotWidget.vue';
import { useRestaurantCategory } from '@/composables/useRestaurantCategory';
import { useTourismPlace } from '@/composables/useTourismPlace';
import { Swiper, SwiperSlide } from 'swiper/vue';
import 'swiper/css';

const tourismSwiperBreakpoints = {
    0: {
        slidesPerView: 1.5,
        spaceBetween: 16,
    },
    768: {
        slidesPerView: 3,
        spaceBetween: 24,
    },
    1024: {
        slidesPerView: 4,
        spaceBetween: 24,
    },
};

const props = defineProps({
    services: { type: Array, default: () => [] },
    myOrders: { type: Array, default: () => [] },
    activities: { type: Array, default: () => [] },
    myReservations: { type: Array, default: () => [] },
    currentRoom: { type: String, default: '' },
    roomAccessToken: { type: String, default: '' },
    currentRoomId: { type: Number, default: undefined },
    sessionToken: { type: String, default: '' },
    guestEmail: { type: String, default: '' },
    stripePublishableKey: { type: String, default: '' },
});

const currentTab = ref('home');
const notification = ref(null);
const selectedServiceCategory = ref(null);
const cart = ref([]);
const isCartOpen = ref(false);
const requestedTime = ref('');
const submittingAmenityCode = ref(null);
const maintenanceDescription = ref('');
const reactiveReservations = ref([...(props.myReservations ?? [])]);
const paymentMethod = ref('room');
const isHelpModalOpen = ref(false);
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
const mostrarModalTurismo = ref(false);
const sitioSeleccionado = ref(null);
const mostrarModalComida = ref(false);
const comidaSeleccionada = ref(null);
const cantidadComida = ref(1);
const metodoPago = ref('habitacion');
const cargando = ref(false);
const notasPedido = ref('');
const page = usePage();
const { t, locale } = useI18n();
const {
    restaurantCategoryLabel,
    restaurantCategoryKey,
    restaurantCategoryBadgeClass,
    restaurantCategoryI18nKey,
} = useRestaurantCategory();
const { tourismDescription } = useTourismPlace();

let stripeInstance = null;
let cardElement = null;
let stripeLoadPromise = null;
let stripeMountGeneration = 0;

const stripeCardReady = ref(false);
const stripeCardError = ref('');
const stripeCardLoading = ref(false);
const stripeUnavailable = ref(false);

const resolveStripePublishableKey = () => {
    const fromServer = (props.stripePublishableKey ?? '').trim();
    const fromVite = (import.meta.env.VITE_STRIPE_KEY ?? '').trim();
    return fromServer || fromVite;
};

const isValidStripePublishableKey = (key) => /^pk_(test|live)_[a-zA-Z0-9]+$/.test(key);

const getStripeLoadPromise = () => {
    const key = resolveStripePublishableKey();
    if (!isValidStripePublishableKey(key)) {
        return null;
    }
    if (!stripeLoadPromise) {
        stripeLoadPromise = loadStripe(key);
    }
    return stripeLoadPromise;
};

const resetStripeLoader = () => {
    stripeLoadPromise = null;
};

const availableHours = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];

const totalPrice = computed(() => cart.value.reduce((acc, item) => acc + (item.price * item.quantity), 0));
const foodServices = computed(() => (props.services ?? []).filter((service) => {
    const explicitType = (service.service_type ?? '').toString().toLowerCase();
    const serviceCategory = (service.service_category ?? '').toString().toLowerCase();
    return explicitType === 'comida' || ['comida', 'bebida', 'postre', 'entrante'].includes(serviceCategory);
}));
const categorizedFoodServices = computed(() => {
    const map = { bebidas: [], comida: [], postres: [], entradas: [] };
    foodServices.value.forEach((service) => {
        const category = restaurantCategoryKey(service);
        if (category === 'bebida') {
            map.bebidas.push(service);
            return;
        }
        if (category === 'postre') {
            map.postres.push(service);
            return;
        }
        if (category === 'entrante') {
            map.entradas.push(service);
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
const precioTotalModalComida = computed(() => Number((comidaSeleccionada.value?.price ?? 0) * cantidadComida.value));

const formatPrice = (value) => new Intl.NumberFormat(locale.value === 'en' ? 'en-GB' : 'es-ES', { style: 'currency', currency: 'EUR' }).format(value || 0);
const formatDateTime = (value) => {
    if (value == null || value === '') return '—';
    const d = new Date(value);
    const loc = locale.value === 'en' ? 'en-GB' : 'es-ES';
    return Number.isNaN(d.getTime()) ? '—' : d.toLocaleString(loc, { dateStyle: 'medium', timeStyle: 'short' });
};
const activityDateYmd = (activity) => {
    if (!activity?.date_time) return undefined;
    const d = new Date(activity.date_time);
    if (Number.isNaN(d.getTime())) return undefined;
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
};

const guestRoomPayload = () => ({
    access_token: props.roomAccessToken,
    session_token: props.sessionToken,
});

const plazasDisponibles = (activity) => {
    const disponibles = activity?.plazas_disponibles ?? activity?.max_seats ?? 0;
    return Math.max(0, Number(disponibles));
};

const buildReservationPayload = (activity, seats, type) => {
    const date = activityDateYmd(activity);
    return {
        ...guestRoomPayload(),
        activity_id: activity.id,
        plazas: seats,
        guests: seats,
        seats_booked: seats,
        type,
        ...(date ? { date } : {}),
    };
};

const postReservation = (activity, seats, type) => axios.post('/api/reservations', buildReservationPayload(activity, seats, type));
const formatReservationType = (type) => (type === 'bus_tour' ? t('profile.tipo_bus') : t('profile.tipo_actividad'));
const statusLabel = (status) => t(`profile.status.${status}`, status);
const orderStatusBadgeClass = (status) => {
    if (status === 'completado') {
        return 'bg-[#A64B35]/10 text-[#A64B35]';
    }
    if (status === 'recibido') {
        return 'bg-gray-100 text-gray-500';
    }
    return 'bg-gray-100 text-gray-600';
};

/** Ancho de la línea activa: de centro del 1.º al del último icono (márgenes 1rem = mitad de w-8). */
const orderConnectorWidth = (status) => {
    const index = orderStepIndex(status);
    const ratio = index / 3;
    return `calc((100% - 2rem) * ${ratio})`;
};

const isOrderStepCompleted = (status, stepIndex) => stepIndex <= orderStepIndex(status);

const orderStepIconClass = (status, stepIndex) =>
    isOrderStepCompleted(status, stepIndex) ? 'text-[#A64B35]' : 'text-gray-300';

const orderStepLabelClass = (status, stepIndex) =>
    isOrderStepCompleted(status, stepIndex) ? 'text-[#A64B35] font-medium' : 'text-gray-400';
const orderStatusLabel = (order) => {
    const key = `orders.status.${order.service_type}.${order.status}`;
    const translated = t(key);
    return translated !== key ? translated : order.status;
};

const showNotification = (message, type = 'success') => {
    notification.value = { message, type };
    setTimeout(() => {
        notification.value = null;
    }, 5000);
};
const mostrarNotificacion = (message, type = 'success') => showNotification(message, type);

const menuHistoryState = (tab, category = null) => ({
    menu: true,
    tab,
    category: tab === 'services' ? category : null,
});

const applyMenuHistoryState = (state) => {
    const tab = state?.tab ?? 'home';
    currentTab.value = tab;
    selectedServiceCategory.value = tab === 'services' ? (state?.category ?? null) : null;
};

const pushMenuHistory = (tab, category = null) => {
    window.history.pushState(menuHistoryState(tab, category), '');
};

const changeTab = (newTab) => {
    if (currentTab.value === newTab) return;
    const category = newTab === 'services' ? selectedServiceCategory.value : null;
    currentTab.value = newTab;
    if (newTab !== 'services') {
        selectedServiceCategory.value = null;
    }
    pushMenuHistory(newTab, newTab === 'services' ? category : null);
};

const openServiceCategory = (category) => {
    currentTab.value = 'services';
    selectedServiceCategory.value = category;
    pushMenuHistory('services', category);
};

const goBackInMenu = () => {
    if (selectedServiceCategory.value) {
        window.history.back();
        return;
    }
    if (currentTab.value !== 'home') {
        window.history.back();
    }
};

const handlePopState = (event) => {
    if (event.state?.menu) {
        applyMenuHistoryState(event.state);
        return;
    }
    if (currentTab.value !== 'home') {
        applyMenuHistoryState(menuHistoryState('home'));
        window.history.replaceState(menuHistoryState('home'), '');
    }
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
                        mostrarNotificacion(t('orders.cambio_estado', { id: order.id, status: orderStatusLabel(order) }));
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

const orderSteps = computed(() => [
    { key: 'recibido', label: t('orders.steps.recibido'), icon: Clock3 },
    { key: 'preparando', label: t('orders.steps.preparando'), icon: ChefHat },
    { key: 'en_camino', label: t('orders.steps.en_camino'), icon: ConciergeBell },
    { key: 'entregado', label: t('orders.steps.entregado'), icon: CheckCircle2 },
]);

const orderTitle = (order) => {
    if (order.service_type === 'comida') {
        const mainItem = order.services?.[0]?.name;
        return mainItem ? t('orders.pedido_de', { item: mainItem }) : t('orders.pedido_comida');
    }
    if (order.service_type === 'limpieza' || order.service_type === 'comida') {
        const amenity = resolveAmenityFromDescription(order.description);
        if (amenity) return t(amenity.labelKey);
    }
    if (order.service_type === 'limpieza') return t('cleaning.room_cleaning');
    if (order.service_type === 'mantenimiento') return t('orders.pedido_mantenimiento');
    return t('orders.pedido');
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

const addToCart = (service, qty = 1) => {
    const existing = cart.value.find((item) => item.id === service.id);
    if (existing) existing.quantity += qty;
    else cart.value.push({ ...service, quantity: qty });
    isCartOpen.value = true;
};

const abrirModalTurismo = (sitio) => {
    sitioSeleccionado.value = sitio;
    mostrarModalTurismo.value = true;
};
const cerrarModalTurismo = () => {
    mostrarModalTurismo.value = false;
    sitioSeleccionado.value = null;
};

const unmountStripeCard = () => {
    stripeMountGeneration += 1;
    if (cardElement) {
        try {
            cardElement.unmount();
        } catch {
            /* elemento ya desmontado */
        }
        cardElement = null;
    }
    stripeCardReady.value = false;
    stripeCardError.value = '';
    stripeCardLoading.value = false;
    stripeUnavailable.value = false;
};

const waitForCardMountTarget = async (maxAttempts = 12) => {
    for (let attempt = 0; attempt < maxAttempts; attempt += 1) {
        await nextTick();
        const mountTarget = document.getElementById('card-element');
        if (mountTarget) {
            return mountTarget;
        }
        await new Promise((resolve) => {
            requestAnimationFrame(resolve);
        });
    }
    return null;
};

const mountStripeCard = async () => {
    const generation = stripeMountGeneration + 1;
    stripeMountGeneration = generation;

    stripeCardLoading.value = true;
    stripeUnavailable.value = false;
    stripeCardError.value = '';

    try {
        const promise = getStripeLoadPromise();
        if (!promise) {
            stripeUnavailable.value = true;
            stripeCardError.value = t('notifications.stripe_no_config');
            return;
        }

        const mountTarget = await waitForCardMountTarget();
        if (generation !== stripeMountGeneration) {
            return;
        }
        if (!mountTarget) {
            stripeUnavailable.value = true;
            stripeCardError.value = t('notifications.stripe_load_error');
            return;
        }

        if (cardElement) {
            stripeCardReady.value = true;
            return;
        }

        stripeInstance = await promise;
        if (generation !== stripeMountGeneration) {
            return;
        }
        if (!stripeInstance) {
            stripeUnavailable.value = true;
            stripeCardError.value = t('notifications.stripe_load_error');
            resetStripeLoader();
            return;
        }

        const elements = stripeInstance.elements();
        cardElement = elements.create('card', {
            hidePostalCode: true,
            style: {
                base: {
                    fontSize: '16px',
                    color: '#2F2A26',
                    '::placeholder': { color: '#2F2A2666' },
                },
                invalid: { color: '#b91c1c' },
            },
        });

        cardElement.on('change', (event) => {
            stripeCardError.value = event.error?.message ?? '';
        });

        cardElement.mount(mountTarget);
        stripeCardReady.value = true;
    } catch (error) {
        console.error('[Stripe] mount failed', error);
        resetStripeLoader();
        stripeInstance = null;
        cardElement = null;
        stripeUnavailable.value = true;
        stripeCardError.value = error?.message ?? t('notifications.stripe_load_error');
    } finally {
        if (generation === stripeMountGeneration) {
            stripeCardLoading.value = false;
        }
    }
};

watch(
    () => [metodoPago.value, mostrarModalComida.value],
    async ([metodo, modalOpen]) => {
        if (metodo === 'tarjeta' && modalOpen) {
            await mountStripeCard();
        } else if (metodo !== 'tarjeta') {
            unmountStripeCard();
        }
    },
    { flush: 'post' },
);

const resetearModalComida = () => {
    unmountStripeCard();
    comidaSeleccionada.value = null;
    cantidadComida.value = 1;
    metodoPago.value = 'habitacion';
    notasPedido.value = '';
    cargando.value = false;
};

const abrirModalComida = (plato) => {
    comidaSeleccionada.value = plato;
    cantidadComida.value = 1;
    metodoPago.value = 'habitacion';
    notasPedido.value = '';
    cargando.value = false;
    mostrarModalComida.value = true;
};

const cerrarModalComida = () => {
    if (cargando.value) return;
    mostrarModalComida.value = false;
    resetearModalComida();
};

const decrementarCantidadComida = () => {
    if (cantidadComida.value > 1) cantidadComida.value -= 1;
};
const incrementarCantidadComida = () => {
    cantidadComida.value += 1;
};

const enviarPedidoComida = (payload) => {
    axios.post('/api/orders', payload).then((response) => {
        if (response.status !== 200) {
            showNotification(response.data?.message ?? t('notifications.error_pedido'), 'error');
            cargando.value = false;
            return;
        }
        mostrarModalComida.value = false;
        resetearModalComida();
        showNotification(response.data?.message ?? t('notifications.pedido_confirmado'), 'success');
        fetchMyOrders();
    }).catch((error) => {
        const message = error.response?.data?.message
            ?? error.response?.data?.errors?.stripe_token?.[0]
            ?? t('notifications.error_pedido');
        showNotification(message, 'error');
        cargando.value = false;
    });
};

const confirmarModalComida = async () => {
    if (!comidaSeleccionada.value || cargando.value) return;

    const plato = comidaSeleccionada.value;
    const qty = cantidadComida.value;
    const total = Number(plato.price ?? 0) * qty;
    const cartPayload = [{ id: plato.id, name: plato.name, quantity: qty, price: plato.price }];

    const orderPayload = {
        ...guestRoomPayload(),
        service_type: 'comida',
        total,
        notas: notasPedido.value.trim() || null,
        cart: cartPayload.map((item) => ({ id: item.id, quantity: item.quantity, price: item.price })),
    };

    cargando.value = true;

    if (metodoPago.value === 'tarjeta') {
        try {
            if (!stripeInstance || !cardElement || !stripeCardReady.value) {
                await mountStripeCard();
            }
            if (!stripeInstance || !cardElement || stripeUnavailable.value) {
                showNotification(stripeCardError.value || t('notifications.introduce_tarjeta'), 'error');
                cargando.value = false;
                return;
            }

            const { token, error } = await stripeInstance.createToken(cardElement);
            if (error) {
                showNotification(error.message ?? t('notifications.tarjeta_invalida'), 'error');
                cargando.value = false;
                return;
            }

            enviarPedidoComida({ ...orderPayload, stripe_token: token.id });
        } catch (error) {
            console.error('[Stripe] tokenization failed', error);
            showNotification(error?.message ?? t('notifications.tarjeta_invalida'), 'error');
            cargando.value = false;
        }
        return;
    }

    enviarPedidoComida(orderPayload);
};
const increaseQty = (item) => item.quantity++;
const decreaseQty = (item) => {
    if (item.quantity > 1) item.quantity--;
    else cart.value = cart.value.filter((i) => i.id !== item.id);
};

const submitOrder = () => {
    if (cart.value.length === 0) return;
    if (paymentMethod.value === 'card') {
        showNotification(t('notifications.tarjeta_modal'), 'error');
        return;
    }

    axios.post('/api/orders', {
        ...guestRoomPayload(),
        service_type: 'comida',
        total: totalPrice.value,
        cart: cart.value.map((item) => ({ id: item.id, quantity: item.quantity, price: item.price })),
    }).then(() => {
        cart.value = [];
        isCartOpen.value = false;
        showNotification(t('notifications.comida_enviada'), 'success');
        fetchMyOrders();
    }).catch(() => showNotification(t('notifications.error_enviar_pedido'), 'error'));
};

const submitAmenity = (item) => {
    if (submittingAmenityCode.value) return;
    submittingAmenityCode.value = item.code;
    const serviceType = serviceTypeForAmenityCode(item.code);
    axios
        .post('/api/orders', {
            ...guestRoomPayload(),
            service_type: serviceType,
            description: item.code,
        })
        .then(() => {
            showNotification(t('notifications.amenity_enviada'), 'success');
            fetchMyOrders();
        })
        .catch(() => showNotification(t('notifications.error_enviar_solicitud'), 'error'))
        .finally(() => {
            submittingAmenityCode.value = null;
        });
};

const submitRoomCleaningRequest = () => {
    if (!requestedTime.value) return showNotification(t('notifications.selecciona_hora_limpieza'), 'error');
    axios
        .post('/api/orders', {
            ...guestRoomPayload(),
            service_type: 'limpieza',
            requested_time: requestedTime.value,
            description: AMENITY_CODES.ROOM,
        })
        .then(() => {
            requestedTime.value = '';
            showNotification(t('notifications.limpieza_enviada'), 'success');
            fetchMyOrders();
        })
        .catch(() => showNotification(t('notifications.error_enviar_solicitud'), 'error'));
};

const submitMaintenanceRequest = () => {
    if (!maintenanceDescription.value.trim()) return showNotification(t('notifications.describe_averia'), 'error');
    axios.post('/api/orders', {
        ...guestRoomPayload(),
        service_type: 'mantenimiento',
        description: maintenanceDescription.value.trim(),
    }).then(() => {
        maintenanceDescription.value = '';
        showNotification(t('notifications.mantenimiento_enviado'), 'success');
    }).catch(() => showNotification(t('notifications.error_enviar_reporte'), 'error'));
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
        showNotification(t('notifications.reserva_recepcion'), 'success');
    }).catch((error) => {
        const errs = error?.response?.data?.errors ?? {};
        const message = errs.plazas?.[0] ?? errs.seats_booked?.[0] ?? error?.response?.data?.message ?? errs.date?.[0] ?? errs.type?.[0] ?? t('notifications.error_reserva');
        showNotification(message, 'error');
    });
};

const openReservaModal = (actividad) => {
    selectedActividadReserva.value = actividad;
    cantidadReserva.value = 1;
    isReservaModalOpen.value = true;
};
const adjustCantidadReserva = () => {
    const maxPlazas = plazasDisponibles(selectedActividadReserva.value) || 1;
    if (cantidadReserva.value < 1) cantidadReserva.value = 1;
    if (cantidadReserva.value > maxPlazas) cantidadReserva.value = maxPlazas;
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
        showNotification(t('notifications.reserva_ok'), 'success');
    }).catch((error) => {
        const errs = error?.response?.data?.errors ?? {};
        const message = errs.plazas?.[0] ?? errs.seats_booked?.[0] ?? error?.response?.data?.message ?? errs.date?.[0] ?? errs.type?.[0] ?? t('notifications.error_reserva');
        showNotification(message, 'error');
    });
};

onMounted(() => {
    window.history.replaceState(menuHistoryState('home'), '');
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
    unmountStripeCard();
    window.removeEventListener('popstate', handlePopState);
    if (pollingInterval) clearInterval(pollingInterval);
});
</script>

<template>
    <Head :title="$t('app.title')" />

    <div class="relative mx-auto min-h-screen w-full max-w-md bg-[#FAFAFA] md:max-w-3xl lg:max-w-5xl">
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

        <header class="fixed top-0 inset-x-0 z-50 flex justify-center border-b border-[#2F2A26]/10 bg-white/95 backdrop-blur">
            <div class="mx-auto flex w-full max-w-md items-center justify-between px-4 py-3 md:max-w-3xl md:px-6 lg:max-w-5xl lg:px-8">
                <div>
                    <p class="text-base font-bold text-[#2F2A26] leading-none">LANZA<span class="text-[#A64B35]">STAY</span></p>
                    <p class="text-xs text-[#2F2A26]/60 mt-1">{{ $t('menu.habitacion', { num: currentRoom }) }}</p>
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
                        {{ $t('menu.ayuda') }}
                    </button>
                </div>
            </div>
        </header>

        <main class="space-y-4 px-4 pb-32 pt-20 md:px-6 lg:px-8">
            <section v-if="currentTab === 'home'" class="space-y-4 md:space-y-6">
                <div class="flex flex-col gap-4 md:flex-row">
                <button type="button" @click="changeTab('services')" class="relative h-28 w-full overflow-hidden rounded-2xl bg-[url('/images/servicios.avif')] bg-cover bg-center text-white sm:h-32 md:min-h-[8.5rem] md:flex-1">
                    <div class="absolute inset-0 flex h-full w-full flex-col items-center justify-center bg-black/45 p-3 text-center">
                        <p class="text-xs uppercase tracking-wide text-white/80 sm:text-sm md:text-base">{{ $t('menu.explora') }}</p>
                        <p class="mt-1 text-base font-semibold sm:text-lg md:text-xl">{{ $t('menu.servicios') }}</p>
                    </div>
                </button>

                <button type="button" @click="changeTab('activities')" class="relative h-28 w-full overflow-hidden rounded-2xl bg-[url('/images/actividades.avif')] bg-cover bg-center text-white sm:h-32 md:min-h-[8.5rem] md:flex-1">
                    <div class="absolute inset-0 flex h-full w-full flex-col items-center justify-center bg-black/45 p-3 text-center">
                        <p class="text-xs uppercase tracking-wide text-white/80 sm:text-sm md:text-base">{{ $t('menu.descubre') }}</p>
                        <p class="mt-1 text-base font-semibold sm:text-lg md:text-xl">{{ $t('menu.actividades') }}</p>
                    </div>
                </button>
                </div>

                <section>
                    <h2 class="mb-3 text-sm font-semibold text-[#2F2A26] md:text-base">{{ $t('menu.turismo') }}</h2>
                    <Swiper
                        :breakpoints="tourismSwiperBreakpoints"
                        :grab-cursor="true"
                        :watch-overflow="true"
                        class="tourism-swiper -mx-1 px-1"
                    >
                        <SwiperSlide
                            v-for="tour in busTours"
                            :key="tour.id"
                            class="!h-auto"
                        >
                            <article
                                class="flex h-full cursor-pointer flex-col overflow-hidden rounded-xl border border-[#2F2A26]/10 bg-white"
                                @click="abrirModalTurismo(tour)"
                            >
                            <img
                                :src="tour.image_url || 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=600&q=80&fm=avif'"
                                :alt="tour.name"
                                class="aspect-[4/3] w-full shrink-0 rounded-t-xl object-cover"
                            />
                            <div class="flex flex-1 flex-col p-3 sm:p-4">
                                <p class="line-clamp-2 text-sm font-semibold leading-snug text-[#2F2A26] sm:text-base">{{ tour.name || $t('menu.excursion') }}</p>
                                <div class="mt-2 flex items-center gap-1.5">
                                    <a
                                        :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent((tour.name || 'Lanzarote') + ' Lanzarote')}`"
                                        target="_blank"
                                        class="inline-flex flex-1 items-center justify-center gap-1 rounded-md border border-[#2F2A26]/20 py-1.5 text-[10px] text-[#2F2A26]"
                                        @click.stop
                                    >
                                        <MapPin class="h-3 w-3 shrink-0" />
                                        {{ $t('menu.como_llegar') }}
                                    </a>
                                    <button type="button" class="inline-flex flex-1 items-center justify-center gap-1 rounded-md bg-[#A64B35] py-1.5 text-[10px] text-white" @click.stop="openActivityModal(tour)">
                                        <Ticket class="h-3 w-3 shrink-0" />
                                        {{ $t('menu.reservar') }}
                                    </button>
                                </div>
                            </div>
                            </article>
                        </SwiperSlide>
                    </Swiper>
                </section>
            </section>

            <section v-if="currentTab === 'services'" class="space-y-4">
                <div v-if="!selectedServiceCategory" class="space-y-3">
                    <button type="button" @click="openServiceCategory('restaurante')" class="relative w-full h-28 sm:h-32 rounded-xl overflow-hidden bg-cover bg-center bg-[url('/images/restaurante.avif')] text-white">
                        <div class="absolute inset-0 flex h-full w-full items-center justify-center bg-black/45 px-3">
                            <p class="w-full text-center text-base font-semibold text-white drop-shadow-[0_1px_3px_rgba(0,0,0,0.8)] sm:text-lg">{{ $t('services.restaurante') }}</p>
                        </div>
                    </button>
                    <button type="button" @click="openServiceCategory('limpieza')" class="relative w-full h-28 sm:h-32 rounded-xl overflow-hidden bg-cover bg-center bg-[url('/images/limpieza.avif')] text-white">
                        <div class="absolute inset-0 flex h-full w-full items-center justify-center bg-black/45 px-3">
                            <p class="w-full text-center text-base font-semibold text-white drop-shadow-[0_1px_3px_rgba(0,0,0,0.8)] sm:text-lg">{{ $t('services.limpieza') }}</p>
                        </div>
                    </button>
                    <button type="button" @click="openServiceCategory('mantenimiento')" class="relative w-full h-28 sm:h-32 rounded-xl overflow-hidden bg-cover bg-center bg-[url('/images/mantenimiento.avif')] text-white">
                        <div class="absolute inset-0 flex h-full w-full items-center justify-center bg-black/45 px-3">
                            <p class="w-full text-center text-base font-semibold text-white drop-shadow-[0_1px_3px_rgba(0,0,0,0.8)] sm:text-lg">{{ $t('services.mantenimiento') }}</p>
                        </div>
                    </button>
                </div>

                <div v-if="selectedServiceCategory === 'restaurante'" class="space-y-2">
                    <div class="flex items-center gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
                        <button @click="restaurantFilter = 'bebidas'" :class="restaurantFilter === 'bebidas' ? 'bg-[#A64B35] text-white' : 'bg-white text-[#2F2A26] border border-[#2F2A26]/20'" class="shrink-0 rounded-full px-3 py-1.5 text-xs">{{ $t('services.bebidas') }}</button>
                        <button @click="restaurantFilter = 'comida'" :class="restaurantFilter === 'comida' ? 'bg-[#A64B35] text-white' : 'bg-white text-[#2F2A26] border border-[#2F2A26]/20'" class="shrink-0 rounded-full px-3 py-1.5 text-xs">{{ $t('services.comida') }}</button>
                        <button @click="restaurantFilter = 'entradas'" :class="restaurantFilter === 'entradas' ? 'bg-[#A64B35] text-white' : 'bg-white text-[#2F2A26] border border-[#2F2A26]/20'" class="shrink-0 rounded-full px-3 py-1.5 text-xs">{{ $t('services.entradas') }}</button>
                        <button @click="restaurantFilter = 'postres'" :class="restaurantFilter === 'postres' ? 'bg-[#A64B35] text-white' : 'bg-white text-[#2F2A26] border border-[#2F2A26]/20'" class="shrink-0 rounded-full px-3 py-1.5 text-xs">{{ $t('services.postres') }}</button>
                    </div>
                    <article
                        v-for="service in filteredRestaurantServices"
                        :key="service.id"
                        class="min-w-0 cursor-pointer rounded-xl border border-[#2F2A26]/10 bg-white p-3 sm:p-4"
                        @click="abrirModalComida(service)"
                    >
                        <div class="flex min-w-0 items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <div class="flex min-w-0 flex-wrap items-center gap-1.5">
                                    <p class="min-w-0 break-words text-sm font-semibold leading-snug text-[#2F2A26] sm:text-base">
                                        {{ service.name }}
                                    </p>
                                    <span
                                        v-if="restaurantCategoryLabel(service)"
                                        class="inline-flex shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold leading-none"
                                        :class="restaurantCategoryBadgeClass(restaurantCategoryLabel(service))"
                                    >
                                        {{ $t(restaurantCategoryI18nKey(service)) }}
                                    </span>
                                    <span
                                        v-if="hasVegBadge(service)"
                                        class="inline-flex shrink-0 rounded-full bg-[#A64B35]/10 px-2 py-0.5 text-[10px] font-medium text-[#A64B35]"
                                    >
                                        {{ $t('services.veg') }}
                                    </span>
                                </div>
                            </div>
                            <p class="shrink-0 text-xs font-semibold tabular-nums text-[#A64B35] sm:text-sm">
                                {{ formatPrice(service.price) }}
                            </p>
                        </div>
                        <p class="mt-1.5 line-clamp-2 break-words text-xs leading-relaxed text-[#2F2A26]/65 sm:text-sm">
                            {{ service.description }}
                        </p>
                        <div class="mt-2.5 flex flex-wrap items-center gap-2">
                            <button type="button" class="rounded-md border border-[#2F2A26]/20 px-2.5 py-1 text-xs text-[#2F2A26]" @click.stop="openMenuItemModal(service)">{{ $t('services.detalles') }}</button>
                            <button type="button" class="rounded-md bg-[#A64B35] text-white px-2.5 py-1 text-xs" @click.stop="abrirModalComida(service)">{{ $t('services.anadir') }}</button>
                        </div>
                    </article>
                    <div v-if="filteredRestaurantServices.length === 0" class="rounded-xl border border-[#2F2A26]/10 bg-white p-3 sm:p-4 md:p-6 text-xs sm:text-sm text-[#2F2A26]/65">
                        {{ $t('services.sin_productos') }}
                    </div>
                </div>

                <div v-if="selectedServiceCategory === 'limpieza'" class="space-y-4">
                    <GuestAmenitiesPanel
                        :submitting-code="submittingAmenityCode"
                        @submit="submitAmenity"
                    />
                    <div class="rounded-xl border border-[#2F2A26]/8 border-t-2 border-t-[#A64B35] bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#2F2A26]">
                            {{ $t('cleaning.room_section') }}
                        </p>
                        <select
                            v-model="requestedTime"
                            class="mt-2 w-full rounded-lg border border-[#2F2A26]/12 px-3 py-2 text-sm text-[#2F2A26]"
                        >
                            <option value="">{{ $t('services.selecciona_hora') }}</option>
                            <option v-for="hour in availableHours" :key="hour" :value="hour">{{ hour }}</option>
                        </select>
                        <button
                            type="button"
                            class="mt-3 w-full rounded-lg border border-[#2F2A26]/15 bg-white py-2.5 text-sm font-semibold text-[#2F2A26] shadow-sm hover:shadow-md"
                            @click="submitRoomCleaningRequest"
                        >
                            {{ $t('cleaning.request_room_cleaning') }}
                        </button>
                    </div>
                </div>

                <div v-if="selectedServiceCategory === 'mantenimiento'" class="rounded-xl border border-[#2F2A26]/10 bg-white p-3 sm:p-4 md:p-6 space-y-2">
                    <textarea v-model="maintenanceDescription" rows="3" class="w-full rounded-lg border-[#2F2A26]/15 text-sm" :placeholder="$t('services.describe_problema')"></textarea>
                    <button @click="submitMaintenanceRequest" class="w-full rounded-lg bg-[#A64B35] text-white py-2 text-sm">{{ $t('services.enviar_reporte') }}</button>
                </div>
            </section>

            <section v-if="currentTab === 'activities'" class="space-y-3">
                <div v-if="hotelActivitiesGeneral.length === 0" class="rounded-xl border border-[#2F2A26]/10 bg-white p-4 text-xs text-[#2F2A26]/70">
                    {{ $t('activities.sin_disponibles') }}
                </div>
                <article v-for="activity in hotelActivitiesGeneral" :key="activity.id" class="rounded-xl border border-[#2F2A26]/10 bg-white overflow-hidden">
                    <img :src="activity.image_url || '/images/spa.avif'" :alt="activity.name" class="h-32 w-full shrink-0 object-cover rounded-t-xl sm:h-40 md:h-48">
                    <div class="p-3 sm:p-4 md:p-6">
                        <p class="text-base sm:text-lg md:text-xl font-semibold text-[#2F2A26]">{{ activity.name }}</p>
                        <p class="text-xs sm:text-sm md:text-base text-[#2F2A26]/65 mt-1">{{ String(activity.description ?? '').replace(/^\[niños\]\s*/i, '') }}</p>
                        <div class="mt-2 space-y-1 text-xs sm:text-sm text-[#2F2A26]/70">
                            <p class="inline-flex items-center gap-1"><Clock3 class="w-3.5 h-3.5 text-[#A64B35]" /> {{ formatDateTime(activity.date_time) }}</p>
                            <p class="inline-flex items-center gap-1 ml-3"><Euro class="w-3.5 h-3.5 text-[#A64B35]" /> {{ Number(activity.price) === 0 ? $t('activities.gratis') : formatPrice(activity.price) }}</p>
                            <p class="inline-flex items-center gap-1 ml-3"><Users class="w-3.5 h-3.5 text-[#A64B35]" /> {{ $t('activities.plazas', { n: plazasDisponibles(activity) }) }}</p>
                        </div>
                        <button @click="openReservaModal(activity)" class="mt-3 w-full rounded-lg bg-[#A64B35] text-white py-2 text-sm">
                            {{ $t('actions.book') }}
                        </button>
                    </div>
                </article>
            </section>

            <section v-if="currentTab === 'orders'" class="space-y-3">
                <div v-if="props.myOrders.length === 0" class="rounded-xl border border-[#2F2A26]/10 bg-white p-4 text-xs text-[#2F2A26]/70">
                    {{ $t('orders.vacio') }}
                </div>
                <article v-for="order in props.myOrders" :key="order.id" class="rounded-xl border border-[#2F2A26]/10 bg-white p-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-[#2F2A26]">{{ orderTitle(order) }}</p>
                            <p class="text-xs text-[#2F2A26]/55">{{ formatDateTime(order.created_at) }}</p>
                            <p class="text-[10px] text-[#2F2A26]/50 mt-1 font-mono uppercase tracking-wide">
                                {{ $t('orders.estado') }}: {{ orderStatusLabel(order) }}
                            </p>
                        </div>
                        <span :class="orderStatusBadgeClass(order.status)" class="shrink-0 rounded-full px-2 py-1 text-[10px] font-medium">
                            {{ orderStatusLabel(order) }}
                        </span>
                    </div>
                    <ul v-if="order.service_type === 'comida'" class="mt-2 text-xs text-[#2F2A26]/70">
                        <li v-for="service in order.services" :key="service.id">{{ service.pivot.quantity }}x {{ service.name }}</li>
                    </ul>
                    <p v-if="order.service_type === 'comida'" class="mt-1 text-xs font-semibold text-[#A64B35]">{{ formatPrice(order.total_price) }}</p>
                    <div class="mt-3">
                        <div class="relative px-0.5 pt-0.5">
                            <div
                                class="pointer-events-none absolute left-4 right-4 top-4 z-0 h-0.5 -translate-y-1/2 rounded-full bg-gray-200"
                                aria-hidden="true"
                            />
                            <div
                                class="pointer-events-none absolute left-4 top-4 z-0 h-0.5 -translate-y-1/2 rounded-full bg-[#A64B35] transition-all duration-500 ease-out"
                                :style="{ width: orderConnectorWidth(order.status) }"
                                aria-hidden="true"
                            />
                            <div class="relative z-10 flex justify-between">
                                <div
                                    v-for="(step, index) in orderSteps"
                                    :key="step.key"
                                    class="flex min-w-0 flex-1 flex-col items-center gap-1.5"
                                >
                                    <div
                                        class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-white shadow-sm"
                                        :class="isOrderStepCompleted(order.status, index) ? 'border-[#A64B35]/40' : ''"
                                    >
                                        <component
                                            :is="step.icon"
                                            class="h-4 w-4"
                                            :class="orderStepIconClass(order.status, index)"
                                        />
                                    </div>
                                    <span
                                        class="max-w-[4.5rem] text-center text-[10px] leading-tight"
                                        :class="orderStepLabelClass(order.status, index)"
                                    >
                                        {{ step.label }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <section v-if="currentTab === 'profile'" class="space-y-3">
                <div class="rounded-xl border border-[#2F2A26]/10 bg-white p-4">
                    <p class="text-xs text-[#2F2A26]/60">{{ $t('profile.email_huesped') }}</p>
                    <p class="text-sm font-semibold text-[#2F2A26] mt-1">{{ guestEmail || $t('profile.no_disponible') }}</p>
                </div>
                <div class="space-y-2">
                    <p class="text-sm font-semibold text-[#2F2A26]">{{ $t('profile.mis_reservas') }}</p>
                    <div class="rounded-lg border border-[#2F2A26]/10 p-3 bg-white">
                        <p class="text-[10px] text-[#2F2A26]/60">{{ $t('profile.total') }}</p>
                        <p class="text-base font-semibold text-[#2F2A26]">{{ reactiveReservations.length }}</p>
                    </div>
                    <article v-for="reservation in reactiveReservations" :key="reservation.id" class="rounded-xl border border-[#2F2A26]/10 bg-white p-3">
                        <div class="flex justify-between items-center">
                            <p class="text-sm font-semibold text-[#2F2A26]">{{ reservation.activity?.name }}</p>
                            <span class="text-[10px] px-2 py-1 rounded-full bg-[#A64B35]/10 text-[#A64B35]">{{ statusLabel(reservation.status) }}</span>
                        </div>
                        <p class="text-xs text-[#2F2A26]/65 mt-1">{{ formatReservationType(reservation.activity?.type) }} · {{ formatDateTime(reservation.activity?.date_time) }}</p>
                        <p class="text-xs text-[#2F2A26]/55 mt-0.5">{{ $t('profile.plazas_reserva', { seats: reservation.seats_booked, price: formatPrice(reservation.total_price) }) }}</p>
                    </article>
                </div>
            </section>
        </main>

        <div class="fixed inset-x-0 bottom-0 z-50 flex justify-center px-4 pb-[env(safe-area-inset-bottom)] sm:px-6 lg:px-8">
            <nav class="mx-auto flex h-16 w-full max-w-md items-center justify-between rounded-t-2xl border border-b-0 border-[#2F2A26]/10 bg-white/95 px-8 backdrop-blur md:max-w-3xl lg:max-w-5xl">
                <button @click="changeTab('home')" :class="currentTab === 'home' ? 'text-[#A64B35]' : 'text-gray-400'" class="flex flex-col items-center">
                    <Home class="w-5 h-5" />
                    <span class="text-[10px]">{{ $t('menu.inicio') }}</span>
                </button>
                <button @click="changeTab('orders')" :class="currentTab === 'orders' ? 'text-[#A64B35]' : 'text-gray-400'" class="flex flex-col items-center">
                    <ReceiptText class="w-5 h-5" />
                    <span class="text-[10px]">{{ $t('menu.pedidos') }}</span>
                </button>
                <button @click="changeTab('profile')" :class="currentTab === 'profile' ? 'text-[#A64B35]' : 'text-gray-400'" class="flex flex-col items-center">
                    <User class="w-5 h-5" />
                    <span class="text-[10px]">{{ $t('menu.perfil') }}</span>
                </button>
            </nav>
        </div>

        <ChatbotWidget :room-number="currentRoom" :room-access-token="roomAccessToken" />

        <Teleport to="body">
            <div v-if="isHelpModalOpen" class="fixed inset-0 z-[80] flex items-end sm:items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" @click="isHelpModalOpen = false"></div>
                <div class="relative w-full max-w-sm rounded-2xl bg-white border border-[#2F2A26]/10 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-semibold text-[#2F2A26]">{{ $t('help.titulo') }}</p>
                        <button @click="isHelpModalOpen = false" class="text-[#2F2A26]/60"><X class="w-4 h-4" /></button>
                    </div>
                    <div class="space-y-2">
                        <a href="tel:+123456789" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-[#A64B35] text-white py-2 text-sm">
                            <Phone class="w-4 h-4" />
                            {{ $t('help.llamar') }}
                        </a>
                        <a href="https://wa.me/123456789" target="_blank" class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-[#2F2A26]/15 py-2 text-sm text-[#2F2A26]">
                            <MessageCircle class="w-4 h-4 text-[#A64B35]" />
                            {{ $t('help.whatsapp') }}
                        </a>
                    </div>
                </div>
            </div>
        </Teleport>

        <div v-if="isCartOpen" class="fixed inset-0 z-[85] flex justify-end">
            <div class="absolute inset-0 bg-black/40" @click="isCartOpen = false"></div>
            <div class="relative w-full max-w-md bg-white h-full shadow-xl p-4 flex flex-col">
                <div class="flex items-center justify-between pb-3 border-b border-[#2F2A26]/10">
                    <p class="text-base font-semibold text-[#2F2A26]">{{ $t('checkout.titulo') }}</p>
                    <button type="button" :aria-label="$t('checkout.cerrar_carrito')" @click="isCartOpen = false"><X class="w-5 h-5 text-[#2F2A26]/60" /></button>
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
                        <span>{{ $t('checkout.total') }}</span>
                        <span class="text-[#A64B35] font-semibold">{{ formatPrice(totalPrice) }}</span>
                    </div>
                    <label class="block text-xs text-[#2F2A26]/70 mb-1">{{ $t('checkout.como_pagar') }}</label>
                    <select v-model="paymentMethod" class="w-full mb-3 rounded-lg border-[#2F2A26]/20 text-sm">
                        <option value="room">{{ $t('checkout.cargar_habitacion') }}</option>
                        <option value="card">{{ $t('checkout.pagar_ahora') }}</option>
                    </select>
                    <button @click="submitOrder" class="w-full rounded-lg bg-[#A64B35] text-white py-2.5 text-sm">{{ $t('checkout.btn_confirmar') }}</button>
                </div>
            </div>
        </div>

        <div v-if="isActivityModalOpen && selectedActivity" class="fixed inset-0 z-[85] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="isActivityModalOpen = false"></div>
            <div class="relative bg-white rounded-2xl border border-[#2F2A26]/10 shadow-xl w-full max-w-sm overflow-hidden">
                <img :src="selectedActivity.image_url || 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=1200&q=80&fm=avif'" :alt="selectedActivity.name" class="h-48 w-full shrink-0 object-cover rounded-t-2xl">
                <div class="p-4">
                    <p class="text-sm font-semibold text-[#2F2A26]">{{ selectedActivity.name }}</p>
                    <p class="text-xs text-[#2F2A26]/65 mt-1">{{ selectedActivity.description }}</p>
                    <div class="mt-3 space-y-2">
                        <a :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(selectedActivity.name)}`" target="_blank" class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-[#2F2A26]/20 py-2 text-xs text-[#2F2A26]">
                            <MapPin class="w-3.5 h-3.5" />
                            {{ $t('menu.como_llegar') }}
                        </a>
                        <button @click="startReservation(selectedActivity)" class="w-full rounded-lg bg-[#A64B35] text-white py-2 text-xs">{{ $t('activities.reservar_bus') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isBookingModalOpen && selectedActivity" class="fixed inset-0 z-[90] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="isBookingModalOpen = false"></div>
            <div class="relative bg-white rounded-xl border border-[#2F2A26]/10 shadow-xl p-4 w-full max-w-sm">
                <p class="text-sm font-semibold text-[#2F2A26]">{{ $t('activities.confirmar_reserva') }}</p>
                <p class="text-xs text-[#2F2A26]/65 mt-1">{{ selectedActivity.name }}</p>
                <label class="block text-xs text-[#2F2A26]/65 mt-3">{{ $t('activities.numero_plazas') }}</label>
                <input v-model.number="bookingSeats" type="number" min="1" :max="plazasDisponibles(selectedActivity) || 1" class="w-full rounded-lg border-[#2F2A26]/20 mt-1 text-sm">
                <p class="text-xs font-semibold text-[#A64B35] mt-2">{{ $t('activities.total_label') }} {{ formatPrice(bookingTotal) }}</p>
                <button @click="confirmReservation" class="w-full mt-3 rounded-lg bg-[#A64B35] text-white py-2 text-sm">{{ $t('activities.confirmar_reserva') }}</button>
            </div>
        </div>

        <div v-if="isReservaModalOpen && selectedActividadReserva" class="fixed inset-0 z-[90] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="isReservaModalOpen = false"></div>
            <div class="relative bg-white rounded-xl border border-[#2F2A26]/10 shadow-xl p-4 w-full max-w-sm">
                <p class="text-sm font-semibold text-[#2F2A26]">{{ selectedActividadReserva.name }}</p>
                <p class="text-xs text-[#2F2A26]/65 mt-1">{{ formatDateTime(selectedActividadReserva.date_time) }}</p>
                <label class="block text-xs text-[#2F2A26]/65 mt-3">{{ $t('activities.cuantos_son') }}</label>
                <input v-model.number="cantidadReserva" @input="adjustCantidadReserva" type="number" min="1" :max="plazasDisponibles(selectedActividadReserva) || 1" class="w-full rounded-lg border-[#2F2A26]/20 mt-1 text-sm">
                <p class="text-xs text-[#A64B35] mt-2">{{ $t('activities.precio_total') }} {{ Number(selectedActividadReserva.price) === 0 ? $t('activities.gratis') : formatPrice(precioTotalReserva) }}</p>
                <button @click="confirmarReservaActividad" class="w-full mt-3 rounded-lg bg-[#A64B35] text-white py-2 text-sm">
                    {{ $t('actions.confirm') }}
                </button>
            </div>
        </div>

        <div v-if="isMenuItemModalOpen && selectedMenuItem" class="fixed inset-0 z-[92] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="isMenuItemModalOpen = false"></div>
            <div class="relative bg-white rounded-xl border border-[#2F2A26]/10 shadow-xl p-4 w-full max-w-sm">
                <div class="flex items-start justify-between gap-2 min-w-0">
                    <div class="min-w-0 flex-1">
                        <p class="break-words text-sm font-semibold text-[#2F2A26]">{{ selectedMenuItem.name }}</p>
                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                            <span
                                v-if="restaurantCategoryLabel(selectedMenuItem)"
                                class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                :class="restaurantCategoryBadgeClass(restaurantCategoryLabel(selectedMenuItem))"
                            >
                                {{ $t(restaurantCategoryI18nKey(selectedMenuItem)) }}
                            </span>
                            <span v-if="hasVegBadge(selectedMenuItem)" class="inline-flex rounded-full bg-[#A64B35]/10 px-2 py-0.5 text-[10px] text-[#A64B35]">{{ $t('services.vegano') }}</span>
                        </div>
                    </div>
                    <button @click="isMenuItemModalOpen = false" class="shrink-0 text-[#2F2A26]/60"><X class="w-4 h-4" /></button>
                </div>
                <p class="mt-2 break-words text-xs text-[#2F2A26]/65">{{ selectedMenuItem.description }}</p>
                <div class="mt-3">
                    <p class="text-xs font-semibold text-[#2F2A26] mb-2">{{ $t('services.ingredientes') }}</p>
                    <div class="space-y-1.5 max-h-40 overflow-y-auto">
                        <label v-for="ingredient in serviceIngredients(selectedMenuItem)" :key="ingredient" class="flex items-center gap-2 text-xs text-[#2F2A26]">
                            <input v-model="excludedIngredients" type="checkbox" :value="ingredient" class="rounded border-[#2F2A26]/25 text-[#A64B35] focus:ring-[#A64B35]" />
                            <span>{{ ingredient }}</span>
                        </label>
                    </div>
                </div>
                <button type="button" @click="addToCart(selectedMenuItem, 1); isMenuItemModalOpen = false" class="w-full mt-4 rounded-lg bg-[#A64B35] text-white py-2 text-sm">
                    {{ $t('services.anadir_pedido') }}
                </button>
            </div>
        </div>

        <div v-if="mostrarModalTurismo && sitioSeleccionado" class="fixed inset-0 z-[93] flex items-center justify-center p-3 sm:p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="cerrarModalTurismo" />
            <div class="relative w-[90%] sm:max-w-md md:max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl bg-white border border-[#2F2A26]/10 shadow-xl">
                <img
                    :src="sitioSeleccionado.image_url || 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=1200&q=80&fm=avif'"
                    :alt="sitioSeleccionado.name"
                    class="h-32 w-full shrink-0 object-cover rounded-t-2xl sm:h-48 md:h-56"
                />
                <div class="space-y-4 p-6">
                    <p class="text-lg font-semibold text-[#2F2A26] sm:text-xl">{{ sitioSeleccionado.name || $t('tourism.lugar') }}</p>
                    <p class="text-sm leading-relaxed text-[#2F2A26]/80 sm:text-base">{{ tourismDescription(sitioSeleccionado) }}</p>
                    <button
                        type="button"
                        class="w-full rounded-lg bg-[#2F2A26] py-2 text-sm font-medium text-white transition hover:bg-[#A64B35] sm:py-3"
                        @click="cerrarModalTurismo"
                    >
                        {{ $t('tourism.cerrar') }}
                    </button>
                </div>
            </div>
        </div>

        <div v-if="mostrarModalComida && comidaSeleccionada" class="fixed inset-0 z-[94] flex items-center justify-center p-3 sm:p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="cerrarModalComida" />
            <div class="relative max-h-[90vh] w-[92%] overflow-y-auto rounded-2xl border border-[#2F2A26]/10 bg-white p-4 shadow-xl sm:max-w-sm md:max-w-md sm:p-5">
                <p class="break-words text-lg font-semibold text-[#2F2A26] sm:text-xl">{{ comidaSeleccionada.name }}</p>
                <span
                    v-if="restaurantCategoryLabel(comidaSeleccionada)"
                    class="mt-2 inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-semibold"
                    :class="restaurantCategoryBadgeClass(restaurantCategoryLabel(comidaSeleccionada))"
                >
                    {{ $t(restaurantCategoryI18nKey(comidaSeleccionada)) }}
                </span>
                <p class="mt-2 break-words text-xs text-[#2F2A26]/70 sm:text-sm">{{ comidaSeleccionada.description }}</p>
                <div class="mt-4 flex items-center justify-center gap-3 rounded-lg border border-[#2F2A26]/10 p-2 sm:gap-4 sm:p-4">
                    <button
                        type="button"
                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border border-[#2F2A26]/20 text-base font-medium text-[#2F2A26] sm:h-8 sm:w-8 sm:text-lg disabled:opacity-50"
                        :disabled="cargando"
                        @click="decrementarCantidadComida"
                    >
                        -
                    </button>
                    <span class="min-w-[2rem] text-center text-sm font-semibold text-[#2F2A26] sm:text-base">{{ cantidadComida }}</span>
                    <button
                        type="button"
                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border border-[#2F2A26]/20 text-base font-medium text-[#2F2A26] sm:h-8 sm:w-8 sm:text-lg disabled:opacity-50"
                        :disabled="cargando"
                        @click="incrementarCantidadComida"
                    >
                        +
                    </button>
                </div>

                <div class="mt-4">
                    <label for="notas-pedido-comida" class="mb-1.5 block text-xs font-semibold text-[#2F2A26]">{{ $t('checkout.personalizacion') }}</label>
                    <textarea
                        id="notas-pedido-comida"
                        v-model="notasPedido"
                        rows="3"
                        :disabled="cargando"
                        :placeholder="$t('checkout.placeholder_notas')"
                        class="w-full resize-none rounded-xl border border-[#2F2A26]/10 bg-gray-50 px-3 py-2.5 text-xs text-[#2F2A26] placeholder:text-[#2F2A26]/40 focus:border-[#A64B35] focus:outline-none focus:ring-1 focus:ring-[#A64B35] disabled:opacity-60 sm:text-sm"
                    />
                </div>

                <div class="mt-4">
                    <label for="metodo-pago-comida" class="block text-xs font-semibold text-[#2F2A26] mb-1.5">{{ $t('checkout.metodo_pago') }}</label>
                    <select
                        id="metodo-pago-comida"
                        v-model="metodoPago"
                        :disabled="cargando"
                        class="w-full rounded-xl border border-[#2F2A26]/15 bg-gray-50 px-3 py-2.5 text-sm text-[#2F2A26] focus:border-[#A64B35] focus:outline-none focus:ring-1 focus:ring-[#A64B35] disabled:opacity-60"
                    >
                        <option value="habitacion">{{ $t('checkout.cargar_habitacion') }}</option>
                        <option value="tarjeta">{{ $t('checkout.pagar_tarjeta') }}</option>
                    </select>
                </div>

                <div v-if="metodoPago === 'tarjeta'" class="mt-4 space-y-2">
                    <div class="flex items-center gap-2 text-xs text-[#2F2A26]/70">
                        <Lock class="h-3.5 w-3.5 shrink-0 text-emerald-700" aria-hidden="true" />
                        <span>{{ $t('checkout.pago_seguro_stripe') }}</span>
                    </div>
                    <p v-if="stripeCardLoading" class="rounded-md border border-[#2F2A26]/10 bg-gray-50 px-3 py-2.5 text-xs text-[#2F2A26]/70">
                        {{ $t('notifications.stripe_loading') }}
                    </p>
                    <div
                        id="card-element"
                        class="min-h-[2.75rem] rounded-md border border-[#2F2A26]/15 bg-white p-3"
                        :class="{ 'sr-only': stripeCardLoading || stripeUnavailable }"
                        aria-live="polite"
                    />
                    <div
                        v-if="stripeUnavailable"
                        class="rounded-md border border-red-200 bg-red-50 px-3 py-2.5 text-xs text-red-800"
                        role="alert"
                    >
                        <p>{{ stripeCardError || $t('notifications.stripe_load_error') }}</p>
                        <button
                            type="button"
                            class="mt-2 font-semibold text-[#A64B35] underline hover:no-underline"
                            :disabled="stripeCardLoading || cargando"
                            @click="mountStripeCard"
                        >
                            {{ $t('notifications.stripe_retry') }}
                        </button>
                    </div>
                    <p v-else-if="stripeCardError" class="text-xs text-red-600">{{ stripeCardError }}</p>
                </div>

                <div class="mt-4 flex gap-2 sm:mt-6">
                    <button
                        type="button"
                        class="flex-1 rounded-lg bg-gray-200 py-2 text-sm font-medium text-[#2F2A26] sm:py-3 sm:text-base disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="cargando"
                        @click="cerrarModalComida"
                    >
                        {{ $t('checkout.cancelar') }}
                    </button>
                    <button
                        type="button"
                        class="flex-1 rounded-lg bg-[#2F2A26] py-2 text-sm font-medium text-white transition hover:bg-[#A64B35] sm:py-3 sm:text-base disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="cargando"
                        @click="confirmarModalComida"
                    >
                        <span v-if="cargando">{{ metodoPago === 'tarjeta' ? $t('checkout.procesando_pago') : $t('checkout.enviando') }}</span>
                        <span v-else-if="metodoPago === 'tarjeta'">{{ $t('checkout.pagar') }} - {{ formatPrice(precioTotalModalComida) }}</span>
                        <span v-else>{{ $t('checkout.confirmar_precio', { price: formatPrice(precioTotalModalComida) }) }}</span>
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showReservationSuccess" class="fixed inset-0 z-[95] flex items-center justify-center pointer-events-none">
            <div class="bg-white rounded-2xl shadow-xl border border-[#A64B35]/25 px-6 py-4 flex items-center gap-2">
                <CheckCircle2 class="w-5 h-5 text-[#A64B35]" />
                <p class="text-sm text-[#2F2A26]">{{ $t('activities.reserva_confirmada') }}</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.tourism-swiper :deep(.swiper-slide) {
    display: flex;
    height: auto;
    box-sizing: border-box;
}

.tourism-swiper :deep(.swiper-wrapper) {
    align-items: stretch;
}
</style>
