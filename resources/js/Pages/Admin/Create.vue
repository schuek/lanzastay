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
    service_type: 'comida',
    categoria_restaurante: 'Comida',
    horario: 'Todo el dia',
    image_url: '',
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

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden rounded-2xl shadow-sm border border-gray-100 p-6">

                    <form @submit.prevent="submit" class="space-y-6">

                        <div>
                            <label class="block font-medium text-sm text-gray-700">Nombre</label>
                            <input v-model="form.name" type="text" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#A64B35] focus:ring-[#A64B35]" required />
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700">Categoría</label>
                            <select v-model="form.category_id" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#A64B35] focus:ring-[#A64B35]" required>
                                <option value="" disabled>Selecciona una categoría</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700">Precio (€)</label>
                            <input v-model="form.price" type="number" step="0.01" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#A64B35] focus:ring-[#A64B35]" required />
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700">Tipo de Servicio</label>
                            <select v-model="form.service_type" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#A64B35] focus:ring-[#A64B35]" required>
                                <option value="comida">Comida</option>
                                <option value="limpieza">Limpieza</option>
                                <option value="mantenimiento">Mantenimiento</option>
                            </select>
                        </div>

                        <div v-if="form.service_type === 'comida'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-medium text-sm text-gray-700">Categoría restaurante</label>
                                <select v-model="form.categoria_restaurante" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#A64B35] focus:ring-[#A64B35]">
                                    <option value="Comida">Comida</option>
                                    <option value="Bebida">Bebida</option>
                                    <option value="Postre">Postre</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-medium text-sm text-gray-700">Horario</label>
                                <select v-model="form.horario" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#A64B35] focus:ring-[#A64B35]">
                                    <option value="Desayuno">Desayuno</option>
                                    <option value="Almuerzo">Almuerzo</option>
                                    <option value="Cena">Cena</option>
                                    <option value="Todo el dia">Todo el dia</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700">Descripción</label>
                            <textarea v-model="form.description" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#A64B35] focus:ring-[#A64B35]" rows="3"></textarea>
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700">Imagen (URL)</label>
                            <input v-model="form.image_url" type="url" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#A64B35] focus:ring-[#A64B35]" placeholder="https://..." />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('admin.index')" class="text-gray-600 hover:text-gray-900">Cancelar</Link>
                            <button type="submit" :disabled="form.processing" class="bg-[#A64B35] text-white px-4 py-2 rounded-full hover:opacity-90 transition">
                                Guardar Plato
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
