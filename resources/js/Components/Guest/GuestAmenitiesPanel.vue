<script setup>
import FaIcon from '@/Components/UI/FaIcon.vue';
import {
    HOUSEKEEPING_AMENITIES,
    RESTAURANT_AMENITIES,
    amenityButtonClass,
    amenityIconClass,
} from '@/constants/amenityRequests';
import { useI18n } from 'vue-i18n';

defineProps({
    submittingCode: { type: String, default: null },
    compact: { type: Boolean, default: false },
    showTitle: { type: Boolean, default: true },
});

const emit = defineEmits(['submit']);

const { t } = useI18n();

const onSubmit = (item) => emit('submit', item);
</script>

<template>
    <div
        class="rounded-xl border border-[#2F2A26]/8 bg-white shadow-sm"
        :class="compact ? 'p-4' : 'p-4 sm:p-5'"
    >
        <h2
            v-if="showTitle"
            class="text-sm font-semibold text-[#2F2A26] sm:text-base"
        >
            {{ $t('amenities.panel_title') }}
        </h2>

        <div class="space-y-5" :class="showTitle ? 'mt-4' : 'mt-0'">
            <section>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-[#A64B35]">
                    {{ $t('amenities.restaurant_heading') }}
                </h3>
                <ul class="mt-2 space-y-2">
                    <li v-for="item in RESTAURANT_AMENITIES" :key="item.code">
                        <button
                            type="button"
                            :class="amenityButtonClass"
                            :disabled="submittingCode === item.code"
                            @click="onSubmit(item)"
                        >
                            <FaIcon :icon="item.icon" class="text-base" :class="amenityIconClass" />
                            <span>{{ $t(item.labelKey) }}</span>
                        </button>
                    </li>
                </ul>
            </section>

            <section>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-[#2F2A26]">
                    {{ $t('amenities.housekeeping_heading') }}
                </h3>
                <ul class="mt-2 space-y-2">
                    <li v-for="item in HOUSEKEEPING_AMENITIES" :key="item.code">
                        <button
                            type="button"
                            :class="amenityButtonClass"
                            :disabled="submittingCode === item.code"
                            @click="onSubmit(item)"
                        >
                            <FaIcon :icon="item.icon" class="text-base" :class="amenityIconClass" />
                            <span>{{ $t(item.labelKey) }}</span>
                        </button>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
