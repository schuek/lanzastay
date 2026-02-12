<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ClipboardDocumentCheckIcon,
    PencilSquareIcon,
    QrCodeIcon,
    UserGroupIcon
} from '@heroicons/vue/24/outline';

// Definimos las opciones del panel para mantener el código limpio
const adminOptions = [
    {
        title: 'Pedidos Actuales',
        description: 'Ver y gestionar los pedidos entrantes de las habitaciones.',
        route: 'orders.index',
        icon: ClipboardDocumentCheckIcon,
        color: 'text-green-600',
        bg: 'bg-green-50',
        border: 'hover:border-green-200'
    },
    {
        title: 'Editar Menú y Servicios',
        description: 'Añadir platos, cambiar precios o modificar servicios.',
        route: 'admin.index',
        icon: PencilSquareIcon,
        color: 'text-blue-600',
        bg: 'bg-blue-50',
        border: 'hover:border-blue-200'
    },
    {
        title: 'Generador de QRs',
        description: 'Imprimir códigos para las habitaciones (Próximamente).',
        route: 'admin.qrcodes', // Lo dejamos temporalmente aquí hasta crear la ruta
        icon: QrCodeIcon,
        color: 'text-purple-600',
        bg: 'bg-purple-50',
        border: 'hover:border-purple-200'
    },
    {
        title: 'Personal',
        description: 'Gestionar perfil y cuenta de administrador.',
        route: 'profile.edit',
        icon: UserGroupIcon,
        color: 'text-gray-600',
        bg: 'bg-gray-50',
        border: 'hover:border-gray-200'
    },
];
</script>

<template>
    <Head title="Panel de Administración" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Panel de Control</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <Link
                        v-for="option in adminOptions"
                        :key="option.title"
                        :href="route(option.route)"
                        class="bg-white overflow-hidden shadow-sm sm:rounded-xl p-8 transition-all duration-200 hover:shadow-lg border border-transparent flex items-start gap-6 group"
                        :class="option.border"
                    >
                        <div class="p-4 rounded-2xl shrink-0 transition-colors" :class="option.bg">
                            <component :is="option.icon" class="w-10 h-10" :class="option.color" />
                        </div>

                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">
                                {{ option.title }}
                            </h3>
                            <p class="text-gray-500 mt-2 text-sm leading-relaxed">
                                {{ option.description }}
                            </p>
                        </div>

                        <div class="self-center opacity-0 group-hover:opacity-100 transition-opacity transform group-hover:translate-x-1">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </Link>

                </div>

                <div class="mt-10 bg-white shadow-sm rounded-lg p-6 border-t-4 border-indigo-500">
                    <h3 class="font-bold text-gray-800 mb-2">Estado del Sistema</h3>
                    <p class="text-gray-600 text-sm">Actualmente el sistema está operativo. Los pedidos entran en tiempo real.</p>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
