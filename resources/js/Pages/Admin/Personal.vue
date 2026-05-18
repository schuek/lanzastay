<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    users: { type: Array, default: () => [] },
    roleOptions: { type: Array, default: () => [] },
});

const page = usePage();
const authUserId = computed(() => page.props.auth?.user?.id);

const employeeModalOpen = ref(false);
const deleteModalOpen = ref(false);
const editingUser = ref(null);
const userToDelete = ref(null);

const form = useForm({
    name: '',
    email: '',
    role: 'recepcion',
    password: '',
});

const roleLabel = (role) => {
    const match = props.roleOptions.find((o) => o.value === role);
    return match?.label ?? role ?? '—';
};

const roleBadgeClass = (role) => {
    const map = {
        admin: 'bg-[#2F2A26]/10 text-[#2F2A26] ring-[#2F2A26]/15',
        recepcion: 'bg-[#0A6ACF]/10 text-[#0A6ACF] ring-[#0A6ACF]/20',
        cocina: 'bg-[#A64B35]/12 text-[#A64B35] ring-[#A64B35]/25',
        room_service: 'bg-[#A64B35]/8 text-[#8f3f2d] ring-[#A64B35]/15',
        limpieza: 'bg-[#5FC34B]/12 text-[#3d9e32] ring-[#5FC34B]/25',
        mantenimiento: 'bg-[#D9C5B2]/60 text-[#2F2A26] ring-[#D9C5B2]',
    };
    return map[role] ?? 'bg-gray-100 text-gray-700 ring-gray-200';
};

const formatDate = (iso) => {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const modalTitle = computed(() => (editingUser.value ? 'Editar empleado' : 'Añadir nuevo empleado'));

const resetForm = () => {
    form.reset();
    form.clearErrors();
    form.role = props.roleOptions[1]?.value ?? 'recepcion';
    editingUser.value = null;
};

const openCreateModal = () => {
    resetForm();
    employeeModalOpen.value = true;
};

const openEditModal = (user) => {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    form.password = '';
    form.clearErrors();
    employeeModalOpen.value = true;
};

const closeEmployeeModal = () => {
    employeeModalOpen.value = false;
    resetForm();
};

const submitEmployee = () => {
    if (editingUser.value) {
        form.put(route('admin.personal.update', editingUser.value.id), {
            preserveScroll: true,
            onSuccess: () => closeEmployeeModal(),
        });
        return;
    }

    form.post(route('admin.personal.store'), {
        preserveScroll: true,
        onSuccess: () => closeEmployeeModal(),
    });
};

const openDeleteModal = (user) => {
    userToDelete.value = user;
    deleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    deleteModalOpen.value = false;
    userToDelete.value = null;
};

const confirmDelete = () => {
    if (!userToDelete.value) return;

    router.delete(route('admin.personal.destroy', userToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
};

const canDelete = (user) => user.id !== authUserId.value;
</script>

<template>
    <Head title="Gestión de Personal" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-[#2F2A26]">Gestión de Personal</h2>
        </template>

        <div class="min-h-screen bg-[#F0F0F0] py-8 sm:py-10">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.15em] text-[#2F2A26]/45">
                            Recursos humanos
                        </p>
                        <p class="mt-1 text-sm text-[#2F2A26]/60">
                            Equipo del hotel, roles operativos y acceso por departamento.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-[#2F2A26] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#2F2A26]/90"
                        @click="openCreateModal"
                    >
                        <FaIcon icon="user-plus" class="text-sm" />
                        Añadir Nuevo Empleado
                    </button>
                </div>

                <div
                    v-if="page.props.flash?.success"
                    class="rounded-xl border border-[#5FC34B]/30 bg-[#5FC34B]/10 px-4 py-3 text-sm font-medium text-[#2F2A26]"
                >
                    {{ page.props.flash.success }}
                </div>

                <div class="overflow-hidden rounded-2xl border border-[#2F2A26]/8 bg-[#FFFFFF] shadow-[0_1px_4px_rgba(47,42,38,0.05)]">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-[#2F2A26]/8 bg-[#F5F0EB]/40">
                                    <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-[#2F2A26]/55">
                                        Nombre
                                    </th>
                                    <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-[#2F2A26]/55">
                                        Email
                                    </th>
                                    <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-[#2F2A26]/55">
                                        Rol
                                    </th>
                                    <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-[#2F2A26]/55">
                                        Fecha de creación
                                    </th>
                                    <th class="px-5 py-3.5 text-right text-[11px] font-bold uppercase tracking-wider text-[#2F2A26]/55">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="user in users"
                                    :key="user.id"
                                    class="border-t border-[#2F2A26]/6 transition hover:bg-[#F5F0EB]/25"
                                >
                                    <td class="px-5 py-4 text-sm font-semibold text-[#2F2A26]">
                                        {{ user.name }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-[#2F2A26]/70">
                                        {{ user.email }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1"
                                            :class="roleBadgeClass(user.role)"
                                        >
                                            {{ roleLabel(user.role) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-sm tabular-nums text-[#2F2A26]/60">
                                        {{ formatDate(user.created_at) }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            <button
                                                type="button"
                                                class="rounded-lg border border-[#2F2A26]/12 px-3 py-1.5 text-xs font-semibold text-[#2F2A26]/75 transition hover:bg-[#2F2A26]/5"
                                                @click="openEditModal(user)"
                                            >
                                                Editar
                                            </button>
                                            <button
                                                v-if="canDelete(user)"
                                                type="button"
                                                class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50"
                                                @click="openDeleteModal(user)"
                                            >
                                                Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="users.length === 0">
                                    <td colspan="5" class="px-5 py-16 text-center text-sm text-[#2F2A26]/50">
                                        No hay empleados registrados. Añade el primero con el botón superior.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="employeeModalOpen" max-width="md" @close="closeEmployeeModal">
            <div class="p-6">
                <h3 class="text-lg font-bold text-[#2F2A26]">{{ modalTitle }}</h3>
                <p class="mt-1 text-sm text-[#2F2A26]/55">
                    {{ editingUser ? 'Actualiza los datos del empleado.' : 'Registra un nuevo miembro del equipo.' }}
                </p>

                <form class="mt-5 space-y-4" @submit.prevent="submitEmployee">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[#2F2A26]/55">
                            Nombre completo
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full rounded-xl border-gray-300 focus:border-[#0A6ACF] focus:ring-[#0A6ACF]"
                        >
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[#2F2A26]/55">
                            Email
                        </label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            class="w-full rounded-xl border-gray-300 focus:border-[#0A6ACF] focus:ring-[#0A6ACF]"
                        >
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[#2F2A26]/55">
                            Rol
                        </label>
                        <select
                            v-model="form.role"
                            required
                            class="w-full rounded-xl border-gray-300 focus:border-[#0A6ACF] focus:ring-[#0A6ACF]"
                        >
                            <option v-for="opt in roleOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <p v-if="form.errors.role" class="mt-1 text-xs text-red-600">{{ form.errors.role }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[#2F2A26]/55">
                            {{ editingUser ? 'Nueva contraseña (opcional)' : 'Contraseña' }}
                        </label>
                        <input
                            v-model="form.password"
                            type="password"
                            :required="!editingUser"
                            autocomplete="new-password"
                            class="w-full rounded-xl border-gray-300 focus:border-[#0A6ACF] focus:ring-[#0A6ACF]"
                        >
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-[#2F2A26]/8 pt-4">
                        <button
                            type="button"
                            class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600"
                            @click="closeEmployeeModal"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-xl bg-[#2F2A26] px-5 py-2 text-sm font-bold text-white disabled:opacity-60"
                        >
                            {{ editingUser ? 'Guardar cambios' : 'Crear empleado' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="deleteModalOpen" max-width="sm" @close="closeDeleteModal">
            <div class="p-6">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-50 text-red-600">
                    <FaIcon icon="triangle-exclamation" class="text-xl" />
                </div>
                <h3 class="mt-4 text-lg font-bold text-[#2F2A26]">Confirmar eliminación</h3>
                <p class="mt-2 text-sm text-[#2F2A26]/65">
                    ¿Eliminar a
                    <span class="font-semibold text-[#2F2A26]">{{ userToDelete?.name }}</span>
                    ? Esta acción no se puede deshacer.
                </p>
                <p v-if="page.props.errors?.delete" class="mt-2 text-xs text-red-600">
                    {{ page.props.errors.delete }}
                </p>
                <div class="mt-6 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600"
                        @click="closeDeleteModal"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white hover:bg-red-700"
                        @click="confirmDelete"
                    >
                        Eliminar empleado
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
