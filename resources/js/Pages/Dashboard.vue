
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CleaningBoardPanel from '@/Components/Admin/CleaningBoardPanel.vue';
import MaintenanceBoardPanel from '@/Components/Admin/MaintenanceBoardPanel.vue';
import KitchenOrdersTable from '@/Components/Dashboard/KitchenOrdersTable.vue';
import DashboardAdminView from '@/Components/Dashboard/DashboardAdminView.vue';
import { useAuthRole } from '@/composables/useAuthRole';
import { Head, Link } from '@inertiajs/vue3';
import SummaryStatCard from '@/Components/Dashboard/SummaryStatCard.vue';
import { CakeIcon, PencilSquareIcon, TicketIcon } from '@heroicons/vue/24/outline';

defineProps({
    departmentStats: { type: Object, default: null },
    operationalKpis: { type: Object, default: null },
    cleaningOrders: { type: Array, default: () => [] },
    maintenanceOrders: { type: Array, default: () => [] },
    kitchenOrders: { type: Array, default: () => [] },
    totalServices: { type: Number, default: 0 },
    totalActivities: { type: Number, default: 0 },
});

const { isRoleReady, roleView } = useAuthRole();
</script>

<template>
    <Head title="Panel Principal" />

    <AuthenticatedLayout>
        <template v-if="isRoleReady">
            <div v-if="roleView === 'cleaning'" class="min-h-[calc(100vh-4rem)] bg-[#F0F0F0] py-6 sm:py-8">
                <div class="mx-auto max-w-3xl px-4 sm:px-6">
                    <CleaningBoardPanel :orders="cleaningOrders" minimal />
                </div>
            </div>

            <div v-else-if="roleView === 'maintenance'" class="min-h-[calc(100vh-4rem)] bg-[#F0F0F0] py-6 sm:py-8">
                <div class="mx-auto max-w-3xl px-4 sm:px-6">
                    <MaintenanceBoardPanel :orders="maintenanceOrders" minimal />
                </div>
            </div>

            <div v-else-if="roleView === 'kitchen'" class="py-6 sm:py-8">
                <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-black text-[#2F2A26]">Cocina</h1>
                            <p class="mt-1 text-sm text-[#2F2A26]/65">Cola de pedidos y catálogo de productos.</p>
                        </div>
                        <Link
                            :href="route('catalog.index')"
                            class="inline-flex items-center gap-2 rounded-xl bg-[#A64B35] px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#8f3f2d]"
                        >
                            <PencilSquareIcon class="h-5 w-5" />
                            Editar catálogo
                        </Link>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <SummaryStatCard title="Total Servicios" :value="totalServices" icon-bg-class="bg-blue-50">
                            <template #icon>
                                <CakeIcon class="h-5 w-5 text-blue-600" />
                            </template>
                        </SummaryStatCard>
                        <SummaryStatCard title="Total Actividades" :value="totalActivities" icon-bg-class="bg-[#2F2A26]/5">
                            <template #icon>
                                <TicketIcon class="h-5 w-5 text-[#2F2A26]" />
                            </template>
                        </SummaryStatCard>
                    </div>

                    <KitchenOrdersTable :orders="kitchenOrders" compact />
                </div>
            </div>

            <DashboardAdminView
                v-else-if="roleView === 'admin' && operationalKpis"
                :operational-kpis="operationalKpis"
                :department-stats="departmentStats"
                :is-admin="true"
            />

            <DashboardAdminView
                v-else-if="roleView === 'recepcion' && operationalKpis"
                :operational-kpis="operationalKpis"
                :department-stats="departmentStats"
                :is-admin="false"
            />

            <div v-else class="py-12 text-center text-sm text-[#2F2A26]/60">
                Rol no reconocido. Contacta con administración.
            </div>
        </template>
    </AuthenticatedLayout>
</template>
