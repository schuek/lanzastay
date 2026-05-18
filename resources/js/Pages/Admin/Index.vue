<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useRestaurantCategory } from '@/composables/useRestaurantCategory';
import {
    PencilSquareIcon,
    TrashIcon,
    PlusCircleIcon,
    PhotoIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    services: {
        type: Array,
        default: () => [],
    },
});

const { restaurantCategoryLabel, restaurantCategoryBadgeClass } = useRestaurantCategory();

const catalogServices = computed(() =>
    (props.services ?? []).filter((service) => (service.service_type ?? 'comida') === 'comida'),
);

const deleteService = (id) => {
    if (confirm('¿Estás seguro de borrar este servicio?')) {
        router.delete(route('catalog.destroy', id));
    }
};

const formatPrice = (value) =>
    new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value);

const neutralBadgeClass = 'bg-slate-50 text-slate-600 ring-1 ring-inset ring-slate-100';
</script>

<template>
    <Head title="Gestión del Catálogo" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Gestión del Catálogo</h2>
                    <p class="mt-0.5 text-sm text-gray-500">Restaurante — carta y productos del menú</p>
                </div>
                <Link
                    :href="route('catalog.create')"
                    class="flex items-center gap-2 rounded-full bg-[#A64B35] px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-90"
                >
                    <PlusCircleIcon class="h-5 w-5" />
                    Nuevo producto
                </Link>
            </div>
        </template>

        <div class="min-h-screen bg-gray-50 py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl border border-gray-100/80 bg-white shadow-[0_1px_3px_rgba(0,0,0,0.06),0_4px_16px_rgba(0,0,0,0.04)]">
                    <div v-if="catalogServices.length > 0" class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/80">
                                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                                        Imagen
                                    </th>
                                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                                        Nombre
                                    </th>
                                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                                        Descripción
                                    </th>
                                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                                        Precio
                                    </th>
                                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                                        Categoría
                                    </th>
                                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                                        Horario
                                    </th>
                                    <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="service in catalogServices"
                                    :key="service.id"
                                    class="transition-colors duration-150 hover:bg-gray-50/60"
                                >
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <img
                                            v-if="service.image_url"
                                            :src="service.image_url"
                                            :alt="service.name"
                                            class="h-11 w-11 shrink-0 rounded-lg object-cover ring-1 ring-gray-100"
                                        />
                                        <div
                                            v-else
                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-gray-100 ring-1 ring-gray-100"
                                        >
                                            <PhotoIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-gray-900">
                                        {{ service.name }}
                                    </td>
                                    <td class="max-w-xs truncate px-5 py-4 text-sm text-gray-500">
                                        {{ service.description }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-bold text-gray-900">
                                        {{ formatPrice(service.price) }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span
                                            v-if="restaurantCategoryLabel(service)"
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="restaurantCategoryBadgeClass(restaurantCategoryLabel(service))"
                                        >
                                            {{ restaurantCategoryLabel(service) }}
                                        </span>
                                        <span v-else class="text-sm text-gray-400">—</span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="neutralBadgeClass"
                                        >
                                            {{ service.horario || '—' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <div class="flex justify-end gap-1">
                                            <Link
                                                :href="route('catalog.edit', service.id)"
                                                class="rounded-lg p-2 text-gray-400 transition-colors duration-200 hover:bg-blue-50 hover:text-blue-600"
                                                title="Editar"
                                            >
                                                <PencilSquareIcon class="h-5 w-5" />
                                            </Link>
                                            <button
                                                type="button"
                                                class="rounded-lg p-2 text-gray-400 transition-colors duration-200 hover:bg-red-50 hover:text-red-600"
                                                title="Eliminar"
                                                @click="deleteService(service.id)"
                                            >
                                                <TrashIcon class="h-5 w-5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="py-16 text-center">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-50">
                            <PlusCircleIcon class="h-8 w-8 text-gray-400" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900">El catálogo está vacío</h3>
                        <p class="mb-6 text-gray-500">Aún no hay productos del restaurante.</p>
                        <Link :href="route('catalog.create')" class="font-bold text-[#A64B35] hover:underline">
                            Añadir el primero
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
