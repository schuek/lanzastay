<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { PrinterIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    habitaciones: {
        type: Array,
        default: () => [],
    },
});

const printAll = () => {
    window.print();
};

const printSingle = (habitacion) => {
    const printWindow = window.open('', '_blank', 'width=900,height=700');
    if (!printWindow) return;

    printWindow.document.write(`
        <html>
            <head>
                <title>QR Habitación ${habitacion.numero}</title>
                <style>
                    body { font-family: Arial, sans-serif; display: grid; place-items: center; min-height: 100vh; margin: 0; }
                    .card { border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; text-align: center; }
                    .title { font-size: 28px; font-weight: 800; color: #2F2A26; margin-bottom: 16px; }
                    .brand { margin-top: 12px; color: #A64B35; font-weight: 800; letter-spacing: 0.08em; }
                </style>
            </head>
            <body>
                <div class="card">
                    <div class="title">Habitación ${habitacion.numero}</div>
                    <div>${habitacion.qr_svg}</div>
                    <div class="brand">LANZASTAY</div>
                </div>
                <script>window.onload = () => { window.print(); window.close(); };<\/script>
            </body>
        </html>
    `);
    printWindow.document.close();
};
</script>

<template>
    <Head title="Códigos QR" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between no-print">
                <h2 class="text-2xl font-black text-[#2F2A26]">Códigos QR</h2>
                <button
                    @click="printAll"
                    class="inline-flex items-center gap-2 rounded-xl bg-[#A64B35] px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-90"
                >
                    <PrinterIcon class="h-5 w-5" />
                    Imprimir todos los QR
                </button>
            </div>
        </template>

        <div class="min-h-screen bg-[#F8F7F6] py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3 print-grid">
                    <article
                        v-for="habitacion in habitaciones"
                        :key="habitacion.id"
                        class="qr-card rounded-2xl border border-[#2F2A26]/10 bg-white p-6 shadow-sm"
                    >
                        <h3 class="text-3xl font-black text-[#2F2A26]">Habitación {{ habitacion.numero }}</h3>
                        <div class="mt-5 flex justify-center rounded-xl border border-[#2F2A26]/10 bg-white p-4" v-html="habitacion.qr_svg"></div>
                        <button
                            @click="printSingle(habitacion)"
                            class="no-print mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#2F2A26] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#A64B35]"
                        >
                            <PrinterIcon class="h-4 w-4" />
                            Imprimir
                        </button>
                    </article>
                </div>

                <div v-if="habitaciones.length === 0" class="py-16 text-center text-[#2F2A26]/60">
                    No hay habitaciones disponibles para generar QR.
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@media print {
    .no-print,
    nav,
    header,
    aside {
        display: none !important;
    }

    .print-grid {
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        gap: 12px !important;
    }

    .qr-card {
        break-inside: avoid;
        page-break-inside: avoid;
        box-shadow: none !important;
    }
}
</style>
