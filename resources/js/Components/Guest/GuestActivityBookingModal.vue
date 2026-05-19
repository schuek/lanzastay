<script setup>
import {
    activityCapacityLabel,
    activityHasLimitedCapacity,
    activityPlazasDisponibles,
} from '@/composables/useActivityCapacity';
import { Clock3, Euro, Lock, Minus, Plus, Users, X } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    activity: { type: Object, default: null },
    open: { type: Boolean, default: false },
    submitting: { type: Boolean, default: false },
    stripeCardLoading: { type: Boolean, default: false },
    stripeUnavailable: { type: Boolean, default: false },
    stripeCardError: { type: String, default: '' },
});

const seats = defineModel('seats', { type: Number, default: 1 });
const scheduledTime = defineModel('scheduledTime', { type: String, default: '' });
const paymentMethod = defineModel('paymentMethod', { type: String, default: 'efectivo' });

const emit = defineEmits(['close', 'confirm', 'retry-stripe']);

const { t } = useI18n();

const activityTimeSlots = computed(() => {
    const slots = [];
    for (let hour = 9; hour < 18; hour += 1) {
        const start = `${String(hour).padStart(2, '0')}:00`;
        const end = `${String(hour + 1).padStart(2, '0')}:00`;
        slots.push({ value: start, label: `${start} - ${end}` });
    }
    return slots;
});

const durationLabel = computed(() => {
    const description = String(props.activity?.description ?? '');
    const match = description.match(/\[duracion:(\d+)\]/i);
    if (match) {
        return t('activities.duration_minutes', { n: match[1] });
    }
    return t('activities.duration_on_site');
});

const descriptionText = computed(() =>
    String(props.activity?.description ?? '')
        .replace(/^\[niños\]\s*/i, '')
        .replace(/\[duracion:\d+\]\s*/i, '')
        .trim(),
);

const limitedCapacity = computed(() => activityHasLimitedCapacity(props.activity));

const capacityLabel = computed(() =>
    props.activity ? activityCapacityLabel(props.activity, t) : '',
);

const plazasLibres = computed(() => activityPlazasDisponibles(props.activity));

const maxSeats = computed(() => {
    if (!limitedCapacity.value) return 20;
    return Math.max(1, plazasLibres.value ?? 1);
});

const totalPrice = computed(() => Number((props.activity?.price ?? 0) * seats.value));

const requiresPayment = computed(() => totalPrice.value > 0);

const formatPrice = (value) =>
    new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(Number(value ?? 0));

const decrementSeats = () => {
    if (seats.value > 1) seats.value -= 1;
};

const incrementSeats = () => {
    if (seats.value < maxSeats.value) seats.value += 1;
};

const missingScheduledTime = computed(() => !String(scheduledTime.value ?? '').trim());

const canSubmit = computed(() => seats.value >= 1 && !props.submitting);

const confirmLabel = computed(() => {
    if (props.submitting) return t('activities.enviando_reserva');
    if (!requiresPayment.value) return t('activities.confirmar_reserva');
    if (paymentMethod.value === 'tarjeta') {
        return t('activities.confirmar_pagar_tarjeta', { price: formatPrice(totalPrice.value) });
    }
    return t('activities.confirmar_pagar_efectivo');
});
</script>

<template>
    <div
        v-if="open && activity"
        class="fixed inset-0 z-[96] flex items-center justify-center p-3 sm:p-4"
        role="dialog"
        aria-modal="true"
    >
        <div
            class="absolute inset-0 bg-black/60 backdrop-blur-sm"
            @click="emit('close')"
        />

        <div class="relative max-h-[92vh] w-full max-w-md overflow-y-auto rounded-2xl border border-[#2F2A26]/10 bg-white shadow-xl">
            <img
                :src="activity.image_url || 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=1200&q=80&fm=avif'"
                :alt="activity.name"
                class="h-36 w-full shrink-0 object-cover sm:h-44"
            />

            <div class="space-y-4 p-5 sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-lg font-bold text-[#2F2A26] sm:text-xl">{{ activity.name }}</h2>
                    <button
                        type="button"
                        class="shrink-0 rounded-lg p-1 text-[#2F2A26]/50 hover:bg-[#2F2A26]/5 hover:text-[#2F2A26]"
                        :aria-label="t('tourism.cerrar')"
                        @click="emit('close')"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <p v-if="descriptionText" class="text-sm leading-relaxed text-[#2F2A26]/80">
                    {{ descriptionText }}
                </p>

                <ul class="flex flex-wrap gap-3 text-xs text-[#2F2A26]/70 sm:text-sm">
                    <li class="inline-flex items-center gap-1.5">
                        <Clock3 class="h-4 w-4 text-[#A64B35]" />
                        {{ durationLabel }}
                    </li>
                    <li class="inline-flex items-center gap-1.5">
                        <Euro class="h-4 w-4 text-[#A64B35]" />
                        {{ Number(activity.price) === 0 ? $t('activities.gratis') : formatPrice(activity.price) }}
                        <span class="text-[#2F2A26]/50">/ {{ $t('activities.por_persona') }}</span>
                    </li>
                    <li class="inline-flex items-center gap-1.5 font-medium" :class="limitedCapacity ? 'text-[#2F2A26]' : 'text-[#A64B35]'">
                        <Users class="h-4 w-4 shrink-0 text-[#A64B35]" />
                        {{ capacityLabel }}
                    </li>
                </ul>

                <div class="rounded-xl border border-[#2F2A26]/10 bg-[#FAFAFA] p-4 space-y-4">
                    <div>
                        <label class="mb-2 block text-xs font-semibold text-[#2F2A26]">
                            {{ $t('activities.numero_plazas') }}
                        </label>
                        <div class="flex items-center justify-center gap-4">
                            <button
                                type="button"
                                class="flex h-9 w-9 items-center justify-center rounded-lg border border-[#2F2A26]/20 bg-white text-lg font-medium text-[#2F2A26] hover:border-[#A64B35] disabled:opacity-40"
                                :disabled="seats <= 1 || submitting"
                                @click="decrementSeats"
                            >
                                <Minus class="h-4 w-4" />
                            </button>
                            <span class="min-w-[2rem] text-center text-lg font-bold text-[#2F2A26]">{{ seats }}</span>
                            <button
                                type="button"
                                class="flex h-9 w-9 items-center justify-center rounded-lg border border-[#2F2A26]/20 bg-white text-lg font-medium text-[#2F2A26] hover:border-[#A64B35] disabled:opacity-40"
                                :disabled="seats >= maxSeats || submitting"
                                @click="incrementSeats"
                            >
                                <Plus class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-semibold text-[#2F2A26]">
                            {{ $t('activities.hora_preferida') }}
                        </label>
                        <p class="mb-2 text-xs text-[#2F2A26]/65">{{ $t('activities.hora_preferida_hint') }}</p>
                        <select
                            v-model="scheduledTime"
                            class="w-full rounded-lg border bg-white px-3 py-3 text-base font-medium text-[#2F2A26] focus:outline-none focus:ring-2 disabled:opacity-50"
                            :class="missingScheduledTime ? 'border-amber-400 focus:border-amber-500 focus:ring-amber-500/25' : 'border-[#2F2A26]/20 focus:border-[#A64B35] focus:ring-[#A64B35]/25'"
                            :disabled="submitting"
                        >
                            <option value="">{{ $t('activities.selecciona_hora') }}</option>
                            <option
                                v-for="slot in activityTimeSlots"
                                :key="slot.value"
                                :value="slot.value"
                            >
                                {{ slot.label }}
                            </option>
                        </select>
                        <p v-if="missingScheduledTime" class="mt-2 text-xs font-medium text-amber-700">
                            {{ $t('activities.selecciona_hora_obligatorio') }}
                        </p>
                    </div>

                    <p class="text-sm font-semibold text-[#A64B35]">
                        {{ $t('activities.precio_total') }}
                        {{ Number(activity.price) === 0 ? $t('activities.gratis') : formatPrice(totalPrice) }}
                    </p>

                    <div v-if="requiresPayment" class="space-y-3 border-t border-[#2F2A26]/10 pt-4">
                        <label class="block text-xs font-semibold text-[#2F2A26]">{{ $t('activities.metodo_pago') }}</label>
                        <select
                            v-model="paymentMethod"
                            class="w-full rounded-lg border border-[#2F2A26]/20 bg-white px-3 py-3 text-sm font-medium text-[#2F2A26] focus:border-[#A64B35] focus:outline-none focus:ring-2 focus:ring-[#A64B35]/25 disabled:opacity-50"
                            :disabled="submitting"
                        >
                            <option value="efectivo">{{ $t('activities.pagar_efectivo') }}</option>
                            <option value="tarjeta">{{ $t('activities.pagar_tarjeta') }}</option>
                        </select>
                        <p v-if="paymentMethod === 'efectivo'" class="text-xs text-[#2F2A26]/65">
                            {{ $t('activities.pago_efectivo_hint') }}
                        </p>

                        <div v-if="paymentMethod === 'tarjeta'" class="space-y-2">
                            <div class="flex items-center gap-2 text-xs text-[#2F2A26]/70">
                                <Lock class="h-3.5 w-3.5 shrink-0 text-emerald-700" aria-hidden="true" />
                                <span>{{ $t('checkout.pago_seguro_stripe') }}</span>
                            </div>
                            <p v-if="stripeCardLoading" class="rounded-md border border-[#2F2A26]/10 bg-gray-50 px-3 py-2.5 text-xs text-[#2F2A26]/70">
                                {{ $t('notifications.stripe_loading') }}
                            </p>
                            <div
                                id="activity-card-element"
                                class="rounded-md border border-[#2F2A26]/10 bg-gray-50 px-3 py-3"
                                :class="{ 'sr-only': stripeCardLoading || stripeUnavailable }"
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
                                    :disabled="stripeCardLoading || submitting"
                                    @click="emit('retry-stripe')"
                                >
                                    {{ $t('notifications.stripe_retry') }}
                                </button>
                            </div>
                            <p v-else-if="stripeCardError" class="text-xs text-red-600">{{ stripeCardError }}</p>
                        </div>
                    </div>
                </div>

                <button
                    type="button"
                    class="w-full rounded-lg bg-[#A64B35] py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#8f3f2e] disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="!canSubmit"
                    @click="emit('confirm')"
                >
                    {{ confirmLabel }}
                </button>
            </div>
        </div>
    </div>
</template>
