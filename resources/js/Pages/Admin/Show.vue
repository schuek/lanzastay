<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CakeIcon, BeakerIcon, SparklesIcon, WrenchScrewdriverIcon } from '@heroicons/vue/24/solid';

// Recibimos solo el servicio
const props = defineProps({
    service: Object
});

// Mapeo de iconos para mostrarlo también aquí
const iconMap = {
    'CakeIcon': CakeIcon,
    'BeakerIcon': BeakerIcon,
    'SparklesIcon': SparklesIcon,
    'WrenchScrewdriverIcon': WrenchScrewdriverIcon
};

const formatPrice = (value) => {
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value);
}
</script>

<template>
    <Head :title="'Ver ' + service.name" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detalle del Servicio</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">

                    <div class="bg-indigo-600 p-6 flex items-center justify-between">
                        <h1 class="text-3xl font-bold text-white">{{ service.name }}</h1>
                        <div class="bg-white p-2 rounded-full">
                            <component
                                :is="iconMap[service.category.icon]"
                                class="w-8 h-8 text-indigo-600"
                            />
                        </div>
                    </div>

                    <div class="p-6 space-y-6">

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Categoría</h3>
                                <p class="mt-1 text-lg font-semibold text-gray-900">{{ service.category.name }}</p>
                            </div>

                            <div>
                                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Precio</h3>
                                <p class="mt-1 text-2xl font-bold text-green-600">{{ formatPrice(service.price) }}</p>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Descripción</h3>
                            <div class="mt-2 bg-gray-50 p-4 rounded-md border border-gray-100 text-gray-700 leading-relaxed">
                                {{ service.description }}
                            </div>
                        </div>

                        <div class="text-xs text-gray-400 border-t pt-4 flex justify-between">
                            <span>Creado: {{ new Date(service.created_at).toLocaleDateString() }}</span>
                            <span>ID de Sistema: #{{ service.id }}</span>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 flex justify-between items-center">
                        <Link :href="route('admin.index')" class="text-gray-600 hover:text-gray-900 font-medium">
                            ← Volver al listado
                        </Link>

                        <Link
                            :href="route('services.edit', service.id)"
                            class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700 transition"
                        >
                            Editar este plato
                        </Link>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
