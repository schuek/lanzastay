<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    roomNumber: String,
    sessionToken: String,
    guestEmail: String,
});

const form = useForm({
    room_number: props.roomNumber,
    session_token: props.sessionToken,
    guest_email: props.guestEmail ?? '',
});

const submit = () => {
    form.post(route('guest.enter'));
};
</script>

<template>
    <Head title="Bienvenida" />

    <div class="min-h-screen bg-gradient-to-b from-[#2F2A26] to-[#1F1A18] flex items-center justify-center px-4">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-8 border border-[#2F2A26]/10">
            <div class="text-center mb-6">
                <p class="text-3xl font-black text-[#A64B35] tracking-tight">LANZA<span class="text-[#2F2A26]">STAY</span></p>
                <h1 class="text-2xl font-black text-[#2F2A26] mt-3">Bienvenido/a</h1>
                <p class="text-sm text-[#2F2A26]/70 mt-2">Habitación {{ roomNumber }} · Introduce tu email para continuar.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="text-sm font-semibold text-[#2F2A26]">Email</label>
                    <input
                        v-model="form.guest_email"
                        type="email"
                        required
                        class="mt-1 w-full rounded-xl border-[#2F2A26]/20 focus:border-[#A64B35] focus:ring-[#A64B35]"
                        placeholder="tu@email.com"
                    >
                    <p v-if="form.errors.guest_email" class="text-xs text-red-600 mt-1">{{ form.errors.guest_email }}</p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-[#A64B35] text-white rounded-xl py-3 font-bold hover:bg-[#8E402E] transition-colors disabled:opacity-60"
                >
                    Entrar
                </button>
            </form>
        </div>
    </div>
</template>
