<script setup>
import { resolveAmenityFromDescription } from '@/constants/amenityRequests';
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useRestauranteOrdersChannel } from '@/composables/useRestauranteOrdersChannel';

const { t } = useI18n();

const props = defineProps({
    orders: { type: Array, default: () => [] },
    compact: { type: Boolean, default: false },
});

const reactiveOrders = ref([...(props.orders ?? [])]);

useRestauranteOrdersChannel((incoming) => {
    if (reactiveOrders.value.some((o) => o.id === incoming.id)) {
        return;
    }
    reactiveOrders.value.unshift(incoming);
});

const pendingOrders = computed(() =>
    reactiveOrders.value.filter(
        (o) => o.service_type === 'comida' && !['completado', 'entregado'].includes(o.status),
    ),
);

const advanceStatus = (order) => {
    const flow = { recibido: 'en_proceso', en_proceso: 'en_camino', en_camino: 'completado', pagado: 'en_proceso' };
    const next = flow[order.status] ?? 'completado';
    router.put(route('orders.kitchen.update', order.id), { status: next }, {
        preserveScroll: true,
        onSuccess: () => {
            const row = reactiveOrders.value.find((o) => o.id === order.id);
            if (row) row.status = next;
        },
    });
};

const statusLabel = (status) => ({
    recibido: 'Recibido',
    pagado: 'Pagado',
    en_proceso: 'En preparación',
    en_camino: 'En camino',
    completado: 'Completado',
}[status] ?? status);

const lineSummary = (order) => {
    const amenity = resolveAmenityFromDescription(order.description);
    if (amenity) return t(amenity.labelKey);
    const lines = order.services ?? [];
    if (!lines.length) return '—';
    return lines.map((s) => `${s.pivot?.quantity ?? 1}× ${s.name}`).join(', ');
};
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-[#2F2A26]/10 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#2F2A26]/10 px-4 py-4 sm:px-6">
            <div>
                <h2 class="text-lg font-black text-[#2F2A26]">Pedidos pendientes</h2>
                <p class="text-sm text-[#2F2A26]/60">{{ pendingOrders.length }} en cola</p>
            </div>
            <Link
                v-if="compact"
                :href="route('orders.kitchen')"
                class="text-sm font-bold text-[#A64B35] hover:underline"
            >
                Ver panel completo →
            </Link>
        </div>

        <div v-if="pendingOrders.length === 0" class="px-6 py-12 text-center text-sm text-[#2F2A26]/55">
            No hay pedidos pendientes en cocina.
        </div>

        <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[#2F2A26]/10 text-left text-sm">
                <thead class="bg-[#F9FAFB] text-xs font-bold uppercase tracking-wide text-[#2F2A26]/55">
                    <tr>
                        <th class="px-4 py-3 sm:px-6">Hab.</th>
                        <th class="px-4 py-3">Detalle</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right sm:px-6">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#2F2A26]/10">
                    <tr v-for="order in pendingOrders" :key="order.id" class="hover:bg-[#F9FAFB]/80">
                        <td class="px-4 py-4 font-black text-[#2F2A26] sm:px-6">{{ order.room_number }}</td>
                        <td class="max-w-xs truncate px-4 py-4 text-[#2F2A26]/80" :title="lineSummary(order)">
                            {{ lineSummary(order) }}
                        </td>
                        <td class="px-4 py-4">
                            <span class="rounded-full bg-[#A64B35]/10 px-2.5 py-1 text-xs font-bold text-[#A64B35]">
                                {{ statusLabel(order.status) }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-right sm:px-6">
                            <button
                                type="button"
                                class="rounded-lg bg-[#2F2A26] px-3 py-1.5 text-xs font-bold text-white hover:bg-[#A64B35]"
                                @click="advanceStatus(order)"
                            >
                                Avanzar
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
