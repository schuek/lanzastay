<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import QrcodeVue from 'qrcode.vue';

defineProps({
    habitaciones: {
        type: Array,
        default: () => [],
    },
});

const getQrValue = (habitacion) => `${window.location.origin}/menu/${habitacion.numero}`;

const habitacionParaImprimir = ref(null);

const printCard = async (habitacion) => {
    habitacionParaImprimir.value = habitacion;
    await nextTick();
    window.print();
    habitacionParaImprimir.value = null;
};
</script>

<template>
    <Head title="Generador de QRs" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h1 class="mb-6 text-3xl font-black text-[#2F2A26] print:hidden">Generador de QRs</h1>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="habitacion in habitaciones"
                        :key="habitacion.id"
                        :class="[
                            'rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition',
                            habitacionParaImprimir?.id === habitacion.id
                                ? 'print:fixed print:inset-0 print:z-50 print:flex print:items-center print:justify-center print:bg-white print:p-10 print:border-0 print:shadow-none print:rounded-none'
                                : 'print:hidden',
                        ]"
                    >
                        <div class="print:flex print:w-full print:max-w-md print:flex-col print:items-center">
                            <h2 class="text-xl font-bold text-gray-900 print:mb-6 print:text-5xl print:font-black print:text-[#2F2A26]">
                                Habitación {{ habitacion.numero }}
                            </h2>

                            <div class="mt-4 flex justify-center rounded-lg bg-gray-50 p-4 print:mt-0 print:bg-white print:p-0">
                                <QrcodeVue
                                    :value="getQrValue(habitacion)"
                                    :size="220"
                                    level="H"
                                    render-as="svg"
                                    foreground="#2F2A26"
                                    background="#FFFFFF"
                                />
                            </div>

                            <p class="mt-4 hidden text-center text-sm text-[#2F2A26] print:block">
                                Escanea para ver el menu y servicios de LanzaStay
                            </p>

                            <button
                                type="button"
                                class="mt-4 w-full rounded-lg bg-[#2F2A26] px-4 py-2 font-semibold text-white transition hover:bg-[#A64B35] print:hidden"
                                @click="printCard(habitacion)"
                            >
                                Imprimir
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
