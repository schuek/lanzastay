<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

// Recibimos el plato (service) y la lista de categorías para el desplegable
const props = defineProps({
    service: Object,
    categories: Array
});

// Inicializamos el formulario con los datos que ya tiene el plato
const form = useForm({
    name: props.service.name,
    description: props.service.description,
    price: props.service.price,
    category_id: props.service.category_id,
});

// Enviamos los datos con método PUT (Actualizar)
const submit = () => {
    form.put(route('services.update', props.service.id));
};
</script>

<template>
    <Head title="Editar Servicio" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Editar: {{ service.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                    <form @submit.prevent="submit" class="space-y-6">

                        <div>
                            <label class="block font-medium text-sm text-gray-700">Nombre del Plato</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700">Categoría</label>
                            <select
                                v-model="form.category_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.category_id" class="text-red-500 text-sm mt-1">{{ form.errors.category_id }}</div>
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700">Precio (€)</label>
                            <input
                                v-model="form.price"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <div v-if="form.errors.price" class="text-red-500 text-sm mt-1">{{ form.errors.price }}</div>
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700">Descripción</label>
                            <textarea
                                v-model="form.description"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                rows="3"
                            ></textarea>
                            <div v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</div>
                        </div>

                        <div class="flex items-center justify-end gap-4 pt-4 border-t">
                            <Link :href="route('admin.index')" class="text-gray-600 hover:text-gray-900 font-medium">
                                Cancelar
                            </Link>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition flex items-center shadow-md"
                            >
                                <span v-if="form.processing">Guardando...</span>
                                <span v-else>Guardar Cambios</span>
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
