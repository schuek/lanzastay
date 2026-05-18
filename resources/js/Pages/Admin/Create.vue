<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({ categories: Array });

const CATEGORY_BY_SERVICE_TYPE = {
    comida: 'Restaurante',
    limpieza: 'Limpieza',
    mantenimiento: 'Mantenimiento',
};

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

const isComida = computed(() => form.service_type === 'comida');
const isLimpieza = computed(() => form.service_type === 'limpieza');
const isMantenimiento = computed(() => form.service_type === 'mantenimiento');

const pageTitle = computed(() => {
    if (isComida.value) return 'Crear Nuevo Plato o Bebida';
    if (isLimpieza.value) return 'Crear Servicio de Limpieza';
    if (isMantenimiento.value) return 'Crear Tarea/Servicio de Mantenimiento';
    return 'Crear Nuevo Servicio';
});

const submitButtonLabel = computed(() => {
    if (isComida.value) return 'Guardar Plato o Bebida';
    if (isLimpieza.value) return 'Guardar Servicio de Limpieza';
    if (isMantenimiento.value) return 'Guardar Tarea de Mantenimiento';
    return 'Guardar Servicio';
});

const nameLabel = computed(() => {
    if (isComida.value) return 'Nombre del plato o bebida';
    if (isLimpieza.value) return 'Nombre del servicio de limpieza';
    if (isMantenimiento.value) return 'Nombre de la tarea de mantenimiento';
    return 'Nombre';
});

const namePlaceholder = computed(() => {
    if (isComida.value) return 'Ej. Paella de mariscos, Agua con gas…';
    if (isLimpieza.value) return 'Ej. Cambio de sábanas, Limpieza completa…';
    if (isMantenimiento.value) return 'Ej. Reparación de aire acondicionado…';
    return '';
});

const descriptionPlaceholder = computed(() => {
    if (isComida.value) return 'Ingredientes, alérgenos, tamaño de ración, notas para cocina…';
    if (isLimpieza.value) return 'Qué incluye el servicio (toallas, productos, horario preferido…)';
    if (isMantenimiento.value) return 'Alcance del trabajo, materiales necesarios, prioridad…';
    return '';
});

const imagePlaceholder = computed(() => {
    if (isComida.value) return 'https://ejemplo.com/imagen-plato.jpg (opcional)';
    if (isLimpieza.value) return 'URL de imagen ilustrativa del servicio (opcional)';
    if (isMantenimiento.value) return 'URL de imagen de referencia (opcional)';
    return 'https://...';
});

const filteredCategories = computed(() => {
    const expectedName = CATEGORY_BY_SERVICE_TYPE[form.service_type];
    if (!expectedName) return props.categories ?? [];
    return (props.categories ?? []).filter((category) => category.name === expectedName);
});

watch(
    () => form.service_type,
    () => {
        const options = filteredCategories.value;
        const stillValid = options.some((c) => String(c.id) === String(form.category_id));
        if (!stillValid) {
            form.category_id = options[0]?.id ?? '';
        }
        if (!isComida.value) {
            form.categoria_restaurante = 'Comida';
            form.horario = 'Todo el dia';
        }
    },
    { immediate: true },
);

const submit = () => {
    if (!isComida.value && (form.price === '' || form.price === null)) {
        form.price = 0;
    }
    form.post(route('catalog.store'));
};
</script>

<template>
    <Head :title="pageTitle" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ pageTitle }}</h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden rounded-2xl shadow-sm border border-gray-100 p-6">

                    <form @submit.prevent="submit" class="space-y-6">

                        <div>
                            <label class="block font-medium text-sm text-gray-700">Tipo de Servicio</label>
                            <select v-model="form.service_type" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#A64B35] focus:ring-[#A64B35]" required>
                                <option value="comida">Comida</option>
                                <option value="limpieza">Limpieza</option>
                                <option value="mantenimiento">Mantenimiento</option>
                            </select>
                            <p v-if="isLimpieza || isMantenimiento" class="mt-1 text-xs text-gray-500">
                                Los campos del formulario se adaptan al tipo seleccionado.
                            </p>
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700">{{ nameLabel }}</label>
                            <input
                                v-model="form.name"
                                type="text"
                                :placeholder="namePlaceholder"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#A64B35] focus:ring-[#A64B35]"
                                required
                            />
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700">Categoría</label>
                            <select
                                v-model="form.category_id"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#A64B35] focus:ring-[#A64B35]"
                                required
                                :disabled="filteredCategories.length === 0"
                            >
                                <option value="" disabled>
                                    {{ filteredCategories.length ? 'Selecciona una categoría' : 'No hay categorías para este tipo' }}
                                </option>
                                <option v-for="category in filteredCategories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700">
                                Precio (€)
                                <span v-if="!isComida" class="font-normal text-gray-500"> — opcional</span>
                            </label>
                            <input
                                v-model="form.price"
                                type="number"
                                step="0.01"
                                min="0"
                                :placeholder="isComida ? '0.00' : '0 si el servicio no tiene coste adicional'"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#A64B35] focus:ring-[#A64B35]"
                                :required="isComida"
                            />
                        </div>

                        <div v-if="isComida" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-medium text-sm text-gray-700">Categoría restaurante</label>
                                <select v-model="form.categoria_restaurante" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#A64B35] focus:ring-[#A64B35]">
                                    <option value="Comida">Comida</option>
                                    <option value="Bebida">Bebida</option>
                                    <option value="Postre">Postre</option>
                                    <option value="Entrante">Entrante</option>
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
                            <textarea
                                v-model="form.description"
                                :placeholder="descriptionPlaceholder"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#A64B35] focus:ring-[#A64B35]"
                                rows="3"
                            />
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700">
                                Imagen (URL)
                                <span class="font-normal text-gray-500"> — opcional</span>
                            </label>
                            <input
                                v-model="form.image_url"
                                type="url"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#A64B35] focus:ring-[#A64B35]"
                                :placeholder="imagePlaceholder"
                            />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('catalog.index')" class="text-gray-600 hover:text-gray-900">Cancelar</Link>
                            <button type="submit" :disabled="form.processing" class="bg-[#A64B35] text-white px-4 py-2 rounded-full hover:opacity-90 transition">
                                {{ submitButtonLabel }}
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
