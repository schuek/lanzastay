<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Acceso empleados" />

    <div class="flex min-h-[100dvh] min-h-screen">
        <!-- Panel visual (escritorio) -->
        <div
            class="relative hidden w-1/2 flex-col justify-end bg-cover bg-center md:flex"
            style="background-image: url('/images/welcome.avif')"
        >
            <div class="absolute inset-0 bg-black/55" aria-hidden="true" />

            <div class="relative z-10 p-10 lg:p-14">
                <p class="text-3xl font-black leading-tight text-white lg:text-4xl">
                    Gestión hotelera<br>
                    <span class="text-[#A64B35]">sin fricciones</span>
                </p>
                <p class="mt-4 max-w-md text-base leading-relaxed text-white/85">
                    Controla pedidos, habitaciones y servicios de LanzaStay desde un único panel profesional, pensado para equipos de recepción y operaciones.
                </p>
            </div>
        </div>

        <!-- Formulario -->
        <div class="flex w-full flex-col justify-center bg-white px-6 py-12 sm:px-10 md:w-1/2 lg:px-16">
            <div class="mx-auto w-full max-w-md">
                <Link href="/" class="mb-10 block text-center">
                    <span class="text-3xl font-black tracking-tight text-[#2F2A26]">
                        LANZA<span class="text-[#A64B35]">STAY</span>
                    </span>
                    <span class="mt-1 block text-xs font-semibold uppercase tracking-widest text-[#2F2A26]/50">
                        Acceso empleados
                    </span>
                </Link>

                <div
                    v-if="status"
                    class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
                >
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <InputLabel for="email" value="Correo electrónico" class="text-[#2F2A26]" />

                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1.5 block w-full rounded-xl border border-[#2F2A26]/10 bg-gray-50 px-4 py-3 text-[#2F2A26] shadow-none focus:border-[#A64B35] focus:ring-[#A64B35]"
                            required
                            autofocus
                            autocomplete="username"
                        />

                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Contraseña" class="text-[#2F2A26]" />

                        <TextInput
                            id="password"
                            v-model="form.password"
                            type="password"
                            class="mt-1.5 block w-full rounded-xl border border-[#2F2A26]/10 bg-gray-50 px-4 py-3 text-[#2F2A26] shadow-none focus:border-[#A64B35] focus:ring-[#A64B35]"
                            required
                            autocomplete="current-password"
                        />

                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <label class="flex items-center">
                            <Checkbox v-model:checked="form.remember" name="remember" />
                            <span class="ms-2 text-sm text-[#2F2A26]/70">Recordarme</span>
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm font-medium text-[#A64B35] hover:text-[#8E402E] focus:outline-none focus-visible:underline"
                        >
                            ¿Olvidaste tu contraseña?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-xl bg-[#2F2A26] px-4 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#A64B35] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#A64B35] focus-visible:ring-offset-2 disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        Iniciar sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
