<script setup>
import FaIcon from '@/Components/UI/FaIcon.vue';
import { HOUSEKEEPING_AMENITIES } from '@/constants/amenityRequests';
import { CheckIcon } from '@heroicons/vue/24/solid';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    submittingCode: { type: String, default: null },
    confirmedCode: { type: String, default: null },
    submittingRoom: { type: Boolean, default: false },
    roomCleaningLimitReached: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
});

const requestedTime = defineModel('requestedTime', { type: String, default: '' });

const emit = defineEmits(['submit', 'submit-room']);

const { t } = useI18n();

const cleaningTimeSlots = computed(() => {
    const slots = [];
    for (let hour = 8; hour < 18; hour += 1) {
        const start = `${String(hour).padStart(2, '0')}:00`;
        const end = `${String(hour + 1).padStart(2, '0')}:00`;
        slots.push({ value: start, label: `${start} - ${end}` });
    }
    return slots;
});

const isAmenitySelected = (code) =>
    props.submittingCode === code || props.confirmedCode === code;

const amenityCardClass = (code) => {
    const selected = isAmenitySelected(code);
    return [
        'flex w-full flex-col rounded-xl border bg-white p-4 text-left shadow-sm transition-colors',
        selected
            ? 'border-[#A64B35] ring-1 ring-[#A64B35]/25'
            : 'border-[#2F2A26]/25 hover:border-[#A64B35]/40',
        props.submittingCode && props.submittingCode !== code ? 'opacity-60' : '',
    ];
};

const amenityIconClass = (code) =>
    isAmenitySelected(code) ? 'text-[#A64B35]' : 'text-[#2F2A26]/70';

const selectionCircleClass = (code) =>
    isAmenitySelected(code)
        ? 'flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-2 border-[#A64B35] bg-[#A64B35]/10'
        : 'h-9 w-9 shrink-0 rounded-full border border-[#2F2A26]/20';

const amenityLocked = computed(() => Boolean(props.confirmedCode));

const canSubmitRoom = computed(
    () => Boolean(requestedTime.value) && !props.submittingRoom && !props.roomCleaningLimitReached,
);

const onAmenityClick = (item) => {
    if (props.submittingCode || props.submittingRoom || amenityLocked.value) return;
    emit('submit', item);
};

const onSubmitRoom = () => {
    if (!canSubmitRoom.value) return;
    emit('submit-room');
};
</script>

<template>
    <div
        class="rounded-xl border border-[#2F2A26]/8 bg-white shadow-sm"
        :class="compact ? 'p-4' : 'p-4 sm:p-5'"
    >
        <h2 class="text-base font-bold text-[#2F2A26] sm:text-lg">
            {{ $t('amenities.panel_title') }}
        </h2>

        <ul class="mt-4 space-y-3">
            <li v-for="item in HOUSEKEEPING_AMENITIES" :key="item.code">
                <button
                    type="button"
                    :class="amenityCardClass(item.code)"
                    :disabled="Boolean(submittingCode) || submittingRoom || amenityLocked"
                    :aria-pressed="isAmenitySelected(item.code)"
                    @click="onAmenityClick(item)"
                >
                    <div class="flex w-full items-center gap-3">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#FAFAFA]"
                            :class="isAmenitySelected(item.code) ? 'text-[#A64B35]' : 'text-[#2F2A26]/70'"
                        >
                            <FaIcon :icon="item.icon" class="text-lg" :class="amenityIconClass(item.code)" />
                        </span>
                        <span class="flex-1 text-sm font-semibold text-[#2F2A26]">{{ $t(item.labelKey) }}</span>
                        <span :class="selectionCircleClass(item.code)" aria-hidden="true">
                            <CheckIcon
                                v-if="isAmenitySelected(item.code)"
                                class="h-5 w-5 text-[#A64B35]"
                            />
                        </span>
                    </div>
                    <p
                        v-if="isAmenitySelected(item.code)"
                        class="mt-2 text-sm font-medium text-[#A64B35]"
                    >
                        {{ $t('amenities.confirmed_hint') }}
                    </p>
                </button>
            </li>
        </ul>

        <section class="mt-6 border-t border-[#2F2A26]/10 pt-6">
            <p
                v-if="roomCleaningLimitReached"
                class="rounded-lg border border-[#2F2A26]/10 bg-[#F0F0F0] px-3 py-2.5 text-sm text-[#2F2A26]/75"
            >
                {{ $t('amenities.daily_limit') }}
            </p>

            <h3 class="text-sm font-bold text-[#2F2A26]" :class="roomCleaningLimitReached ? 'mt-4' : ''">
                {{ $t('amenities.room_schedule_title') }}
            </h3>
            <p class="mt-1.5 text-sm text-[#2F2A26]/70">
                {{ $t('amenities.room_schedule_hint') }}
            </p>

            <label class="mt-4 block">
                <span class="mb-1.5 block text-xs font-semibold text-[#2F2A26]">
                    {{ $t('amenities.room_schedule_select_label') }}
                </span>
                <select
                    v-model="requestedTime"
                    class="w-full rounded-lg border border-[#2F2A26]/20 bg-white px-3 py-3.5 text-base font-medium text-[#2F2A26] shadow-sm focus:border-[#A64B35] focus:outline-none focus:ring-2 focus:ring-[#A64B35]/25 disabled:opacity-50"
                    :disabled="submittingRoom || Boolean(submittingCode) || roomCleaningLimitReached"
                >
                    <option value="">{{ $t('amenities.room_schedule_placeholder') }}</option>
                    <option
                        v-for="slot in cleaningTimeSlots"
                        :key="slot.value"
                        :value="slot.value"
                    >
                        {{ slot.label }}
                    </option>
                </select>
            </label>

            <button
                type="button"
                class="mt-4 w-full rounded-lg bg-[#A64B35] py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#8f3f2e] disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="!canSubmitRoom"
                @click="onSubmitRoom"
            >
                {{ submittingRoom ? $t('amenities.room_submitting') : $t('amenities.room_schedule_cta') }}
            </button>
        </section>
    </div>
</template>
