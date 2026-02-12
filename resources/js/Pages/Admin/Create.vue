<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

defineProps({ categories: Array });

// El formulario empieza VACÍO
const form = useForm({
    name: '',
    description: '',
    price: '',
    category_id: '',
});

const submit = () => {
    form.post(route('services.store'));
};
</script>

<template>
    <Head title="Nuevo Servicio" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Crear Nuevo Plato</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                    <form @submit.prevent="submit" class="space-y-6">

                        <div>
                            <label class="block font-medium text-sm text-gray-700">Nombre</label>
                            <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700">Categoría</label>
                            <select v-model="form.category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="" disabled>Selecciona una categoría</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700">Precio (€)</label>
                            <input v-model="form.price" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700">Descripción</label>
                            <textarea v-model="form.description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" rows="3"></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('admin.index')" class="text-gray-600 hover:text-gray-900">Cancelar</Link>
                            <button type="submit" :disabled="form.processing" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition">
                                Guardar Plato
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
