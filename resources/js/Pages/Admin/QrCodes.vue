<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { TrashIcon, PlusIcon, PrinterIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    codes: Array,
    errors: Object
});

// Formulario para crear habitación
const form = useForm({
    number: ''
});

const submit = () => {
    form.post(route('rooms.store'), {
        onSuccess: () => form.reset(),
    });
};

const deleteRoom = (id) => {
    if (confirm('¿Seguro que quieres borrar esta habitación?')) {
        router.delete(route('rooms.destroy', id));
    }
};

const checkInRoom = (id) => {
    router.post(route('rooms.checkin', id));
};

const checkOutRoom = (id) => {
    if (confirm('Confirmar check-out de esta habitación?')) {
        router.post(route('rooms.checkout', id));
    }
};

const printPage = () => {
    window.print();
};

const downloadQr = (item) => {
    const blob = new Blob([item.qr], { type: 'image/svg+xml;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `lanzastay-habitacion-${item.room}.svg`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
};
</script>

<template>
    <Head title="Gestión de Habitaciones" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center no-print">
                <h2 class="font-semibold text-xl text-[#1A1A1A] leading-tight">Gestor de Habitaciones y QRs</h2>

                <button @click="printPage" class="bg-[#D45D3B] text-white px-4 py-2 rounded-lg font-bold shadow hover:opacity-90 flex items-center gap-2">
                    <PrinterIcon class="w-5 h-5" />
                    Imprimir Pegatinas
                </button>
            </div>
        </template>

        <div class="py-12 bg-[#F5F5F5] min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <div class="bg-white p-6 rounded-xl shadow-sm mb-8 border border-[#1A1A1A]/10 no-print">
                    <h3 class="font-bold text-[#1A1A1A] mb-4">Añadir Nueva Habitación</h3>
                    <form @submit.prevent="submit" class="flex gap-4 items-start">
                        <div class="flex-1 max-w-xs">
                            <input
                                v-model="form.number"
                                type="text"
                                placeholder="Ej: 305, Piscina..."
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-[#D45D3B] focus:ring-[#D45D3B]"
                                :class="{ 'border-red-500': form.errors.number }"
                            >
                            <p v-if="form.errors.number" class="text-red-500 text-xs mt-1">{{ form.errors.number }}</p>
                        </div>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-[#D45D3B] hover:opacity-90 text-white px-6 py-2 rounded-lg font-bold flex items-center gap-2 transition disabled:opacity-50"
                        >
                            <PlusIcon class="w-5 h-5" />
                            Crear
                        </button>
                    </form>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 print:grid-cols-3">

                    <div v-for="item in codes" :key="item.room" class="group bg-white p-5 rounded-xl shadow-lg border-2 border-dashed border-[#1A1A1A]/30 flex flex-col break-inside-avoid transition-colors">

                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-2xl font-black text-[#1A1A1A] leading-tight">Hab. {{ item.room }}</h3>

                            <button
                                @click="deleteRoom(item.id)"
                                class="text-red-400 hover:text-red-700 bg-red-50 hover:bg-red-100 p-2 rounded-lg opacity-0 group-hover:opacity-100 transition-all no-print shrink-0 ml-4"
                                title="Borrar habitación"
                            >
                                <TrashIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <p class="text-center text-[11px] font-bold mb-2" :class="item.status === 'ocupada' ? 'text-[#D45D3B]' : 'text-[#1A1A1A]/60'">
                            Estado: {{ item.status }}
                        </p>
                        <p class="text-center text-[#1A1A1A]/60 mb-3 text-[10px] font-bold tracking-widest uppercase">Escanea para pedir</p>
                        <p class="text-center text-[11px] text-[#1A1A1A]/70 mb-4 break-all">{{ item.menu_url }}</p>

                        <div v-if="item.status === 'ocupada' && item.current_session_token" class="bg-white p-1 mx-auto mb-2" v-html="item.qr"></div>
                        <div v-else class="text-center text-sm text-[#1A1A1A]/60 border border-dashed border-[#1A1A1A]/20 rounded-lg py-6 mb-2">
                            QR inactivo hasta check-in
                        </div>

                        <div class="grid grid-cols-2 gap-2 mt-3 no-print">
                            <button
                                @click="checkInRoom(item.id)"
                                :disabled="item.status === 'ocupada'"
                                class="w-full bg-[#1A1A1A] text-white px-3 py-2 rounded-lg font-bold text-xs hover:bg-[#D45D3B] disabled:opacity-40"
                            >
                                Check-in
                            </button>
                            <button
                                @click="checkOutRoom(item.id)"
                                :disabled="item.status !== 'ocupada'"
                                class="w-full bg-[#D45D3B] text-white px-3 py-2 rounded-lg font-bold text-xs hover:opacity-90 disabled:opacity-40"
                            >
                                Check-out
                            </button>
                        </div>

                        <button
                            @click="downloadQr(item)"
                            :disabled="item.status !== 'ocupada' || !item.current_session_token"
                            class="w-full mt-3 bg-[#D45D3B] text-white px-3 py-2 rounded-lg font-bold text-sm flex items-center justify-center gap-2 no-print hover:opacity-90"
                        >
                            <ArrowDownTrayIcon class="w-4 h-4" />
                            Descargar QR
                        </button>

                        <div class="mt-auto pt-3 border-t w-full text-center">
                            <span class="font-black text-[#D45D3B] tracking-widest text-xs">LANZASTAY</span>
                        </div>
                    </div>

                </div>
                <div v-if="codes.length === 0" class="text-center py-12 text-gray-400 no-print">
                    No hay habitaciones creadas. ¡Añade la primera arriba!
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* IMPRIMIR */
@media print {
    .no-print, nav, header, aside {
        display: none !important;
    }

    body {
        background: white;
        margin: 0;
        padding: 0;
    }

    .grid {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 1rem !important;
    }

    .break-inside-avoid {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}
</style>
