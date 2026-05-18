<script setup>
import { ref } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import StaffNavLinks from '@/Components/Layout/StaffNavLinks.vue';
import StaffMobileNav from '@/Components/Layout/StaffMobileNav.vue';
import { Link } from '@inertiajs/vue3';
import { useAuthRole } from '@/composables/useAuthRole';

const showingNavigationDropdown = ref(false);

const { authUser, roleView, roleLabel, isRoleReady, isFieldStaff } = useAuthRole();
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <template v-if="isRoleReady">
            <nav class="border-b border-gray-200 bg-white">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 min-h-[4rem] items-center justify-between gap-3">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <Link
                                :href="route('dashboard')"
                                class="group flex shrink-0 items-center gap-3 rounded-lg outline-none ring-[#A64B35]/30 focus-visible:ring-2"
                            >
                                <span class="text-xl font-black leading-none tracking-tight text-[#2F2A26] sm:text-2xl">
                                    LANZA<span class="text-[#A64B35]">STAY</span>
                                </span>
                                <span class="hidden shrink-0 rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white lg:inline" :class="isFieldStaff ? 'bg-[#A64B35]' : 'bg-[#2F2A26]'">
                                    {{ roleLabel }}
                                </span>
                            </Link>

                            <div
                                v-if="roleView === 'admin' || roleView === 'recepcion' || roleView === 'kitchen'"
                                class="relative z-20 hidden min-w-0 flex-1 sm:flex sm:items-center sm:justify-end sm:gap-x-0.5 lg:gap-x-1"
                            >
                                <StaffNavLinks v-if="roleView === 'admin'" variant="admin" />
                                <StaffNavLinks v-else-if="roleView === 'recepcion'" variant="recepcion" />
                                <StaffNavLinks v-else-if="roleView === 'kitchen'" variant="kitchen" />
                            </div>
                        </div>

                        <div class="ms-2 flex shrink-0 items-center gap-1">
                            <div class="hidden sm:block">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="inline-flex max-w-[9rem] truncate rounded-md px-2 py-2 text-sm font-medium text-[#2F2A26]/80 hover:text-[#2F2A26] lg:max-w-[11rem]"
                                        >
                                            {{ authUser?.name }}
                                        </button>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">Perfil</DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">Cerrar sesión</DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>

                            <button
                                v-if="roleView === 'admin' || roleView === 'recepcion' || roleView === 'kitchen'"
                                type="button"
                                class="rounded-md p-2 text-gray-400 hover:bg-gray-100 sm:hidden"
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path v-if="!showingNavigationDropdown" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>

                            <div v-else class="sm:hidden">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button type="button" class="rounded-md p-2 text-gray-500 hover:bg-gray-100">
                                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">Perfil</DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">Cerrar sesión</DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="(roleView === 'admin' || roleView === 'recepcion' || roleView === 'kitchen') && showingNavigationDropdown"
                    class="border-t border-gray-100 sm:hidden"
                >
                    <div class="space-y-0.5 pb-3 pt-2">
                        <StaffMobileNav v-if="roleView === 'admin'" variant="admin" />
                        <StaffMobileNav v-else-if="roleView === 'recepcion'" variant="recepcion" />
                        <StaffMobileNav v-else-if="roleView === 'kitchen'" variant="kitchen" />
                    </div>
                    <div class="border-t border-gray-200 px-4 pb-3 pt-3">
                        <ResponsiveNavLink :href="route('profile.edit')">Perfil</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">Cerrar sesión</ResponsiveNavLink>
                    </div>
                </div>
            </nav>

            <header v-if="$slots.header" class="border-b border-gray-200 bg-white">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <main>
                <div v-if="$page.props.flash?.error" class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
                    <p class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800" role="alert">
                        {{ $page.props.flash.error }}
                    </p>
                </div>
                <slot />
            </main>
        </template>
    </div>
</template>
