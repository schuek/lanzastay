import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const ROLE_LABELS = {
    admin: 'Admin',
    recepcion: 'Recepción',
    cocina: 'Cocina',
    room_service: 'Room service',
    limpieza: 'Limpieza',
    mantenimiento: 'Mantenimiento',
};

/**
 * Rol y vista UI derivados de Inertia (síncronos, sin parpadeo en hidratación).
 * roleView: 'admin' | 'recepcion' | 'kitchen' | 'cleaning' | 'maintenance' | 'unknown'
 */
export function useAuthRole() {
    const page = usePage();

    const authUser = computed(() => page.props.auth?.user ?? null);
    const role = computed(() => authUser.value?.role ?? '');
    const isRoleReady = computed(() => Boolean(authUser.value?.role));

    const roleView = computed(() => {
        switch (role.value) {
            case 'admin':
                return 'admin';
            case 'recepcion':
                return 'recepcion';
            case 'cocina':
            case 'room_service':
                return 'kitchen';
            case 'limpieza':
                return 'cleaning';
            case 'mantenimiento':
                return 'maintenance';
            default:
                return 'unknown';
        }
    });

    const roleLabel = computed(() => ROLE_LABELS[role.value] ?? 'Staff');

    const isAdmin = computed(() => roleView.value === 'admin');
    const isReception = computed(() => roleView.value === 'recepcion');
    const isKitchen = computed(() => roleView.value === 'kitchen');
    const isCleaningStaff = computed(() => roleView.value === 'cleaning');
    const isMaintenanceStaff = computed(() => roleView.value === 'maintenance');
    const isFieldStaff = computed(() => isCleaningStaff.value || isMaintenanceStaff.value);

    return {
        authUser,
        role,
        roleView,
        roleLabel,
        isRoleReady,
        isAdmin,
        isReception,
        isKitchen,
        isCleaningStaff,
        isMaintenanceStaff,
        isFieldStaff,
    };
}
