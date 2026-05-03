<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    PencilSquareIcon,
    TrashIcon,
    PlusCircleIcon,
    CakeIcon,          // Icono Restaurante
    SparklesIcon,      // Icono Limpieza
    WrenchScrewdriverIcon // Icono Mantenimiento
} from '@heroicons/vue/24/outline';

const props = defineProps({
    services: Array,
    categories: Array
});

const activeCategoryId = ref(props.categories[0]?.id || null);

const filteredServices = computed(() => {
    if (!activeCategoryId.value) return [];
    return props.services.filter(service => service.category_id === activeCategoryId.value);
});

const getIcon = (iconName) => {
    const map = {
        'CakeIcon': CakeIcon,
        'SparklesIcon': SparklesIcon,
        'WrenchScrewdriverIcon': WrenchScrewdriverIcon
    };
    return map[iconName] || CakeIcon;
};

const deleteService = (id) => {
    if (confirm('¿Estás seguro de borrar este servicio?')) {
        router.delete(route('services.destroy', id));
    }
};

const formatPrice = (value) => new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value);
</script>

<template>
    <Head title="Gestión de Servicios" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión del Catálogo</h2>
                <Link :href="route('services.create')" class="bg-[#A64B35] hover:opacity-90 text-white px-4 py-2 rounded-full text-sm font-bold flex items-center gap-2 shadow-sm transition">
                    <PlusCircleIcon class="w-5 h-5" />
                    Nuevo Servicio
                </Link>
            </div>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <div class="flex space-x-2 mb-6 overflow-x-auto pb-2">
                    <button
                        v-for="category in categories"
                        :key="category.id"
                        @click="activeCategoryId = category.id"
                        class="flex items-center gap-2 px-6 py-3 rounded-full font-bold text-sm transition-all shadow-sm border"
                        :class="activeCategoryId === category.id
                            ? 'bg-[#1A1A1A] text-white border-[#1A1A1A] ring-2 ring-offset-2 ring-[#D45D3B]'
                            : 'bg-white text-gray-600 hover:bg-gray-50 border-gray-200'">
                        <component :is="getIcon(category.icon)" class="w-5 h-5" />
                        {{ category.name }}
                        <span class="ml-2 bg-opacity-20 px-2 py-0.5 rounded-full text-xs"
                            :class="activeCategoryId === category.id ? 'bg-white text-white' : 'bg-gray-200 text-gray-600'">
                            {{ services.filter(s => s.category_id === category.id).length }}
                        </span>
                    </button>
                </div>

                <div class="bg-white overflow-hidden rounded-2xl shadow-sm border border-gray-100">

                    <table v-if="filteredServices.length > 0" class="min-w-full divide-y divide-gray-200 rounded-2xl">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Horario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Imagen URL</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="service in filteredServices" :key="service.id" class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">
                                    {{ service.name }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                    {{ service.description }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-[#A64B35]">
                                    {{ formatPrice(service.price) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 capitalize">
                                    {{ service.service_type || 'comida' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-[#A64B35]/10 text-[#A64B35]">
                                        {{ service.categoria_restaurante || '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                        {{ service.horario || '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500 max-w-xs truncate">
                                    {{ service.image_url || '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-3">
                                        <Link :href="route('services.edit', service.id)" class="text-[#A64B35] hover:opacity-70">
                                            <PencilSquareIcon class="w-5 h-5" />
                                        </Link>
                                        <button @click="deleteService(service.id)" class="text-red-600 hover:text-red-900">
                                            <TrashIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-else class="text-center py-16">
                        <div class="bg-gray-50 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                            <PlusCircleIcon class="w-8 h-8 text-gray-400" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900">Esta sección está vacía</h3>
                        <p class="text-gray-500 mb-6">No hay servicios creados en esta categoría todavía.</p>
                        <Link :href="route('services.create')" class="text-[#A64B35] font-bold hover:underline">
                            ¡Crea el primero ahora!
                        </Link>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
