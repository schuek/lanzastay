<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { TrashIcon, PlusIcon, PrinterIcon } from '@heroicons/vue/24/outline';

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

const printPage = () => {
    window.print();
};
</script>

<template>
    <Head title="Gestión de Habitaciones" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center no-print">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestor de Habitaciones y QRs</h2>

                <button @click="printPage" class="bg-gray-800 text-white px-4 py-2 rounded-lg font-bold shadow hover:bg-gray-700 flex items-center gap-2">
                    <PrinterIcon class="w-5 h-5" />
                    Imprimir Pegatinas
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <div class="bg-white p-6 rounded-xl shadow-sm mb-8 border border-gray-100 no-print">
                    <h3 class="font-bold text-gray-800 mb-4">Añadir Nueva Habitación</h3>
                    <form @submit.prevent="submit" class="flex gap-4 items-start">
                        <div class="flex-1 max-w-xs">
                            <input
                                v-model="form.number"
                                type="text"
                                placeholder="Ej: 305, Piscina..."
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                :class="{ 'border-red-500': form.errors.number }"
                            >
                            <p v-if="form.errors.number" class="text-red-500 text-xs mt-1">{{ form.errors.number }}</p>
                        </div>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-bold flex items-center gap-2 transition disabled:opacity-50"
                        >
                            <PlusIcon class="w-5 h-5" />
                            Crear
                        </button>
                    </form>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 print:grid-cols-3">

                    <div v-for="item in codes" :key="item.room" class="group bg-white p-5 rounded-xl shadow-lg border-2 border-dashed border-gray-300 flex flex-col break-inside-avoid hover:border-indigo-300 transition-colors">

                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-2xl font-black text-gray-800 leading-tight">{{ item.room }}</h3>

                            <button
                                @click="deleteRoom(item.id)"
                                class="text-red-400 hover:text-red-700 bg-red-50 hover:bg-red-100 p-2 rounded-lg opacity-0 group-hover:opacity-100 transition-all no-print shrink-0 ml-4"
                                title="Borrar habitación"
                            >
                                <TrashIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <p class="text-center text-gray-500 mb-4 text-[10px] font-bold tracking-widest uppercase">Escanea para pedir</p>

                        <div class="bg-white p-1 mx-auto mb-2" v-html="item.qr"></div>

                        <div class="mt-auto pt-3 border-t w-full text-center">
                            <span class="font-black text-indigo-600 tracking-widest text-xs">LANZASTAY</span>
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
