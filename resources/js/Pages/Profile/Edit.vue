<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Perfil" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 shadow-lg rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 shadow-indigo-500/30">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Perfil
                    </h2>
                    <p class="text-sm text-gray-500">Gerencie suas informações pessoais e segurança</p>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto space-y-6 max-w-7xl sm:px-6 lg:px-8">
                <!-- Informações do Perfil -->
                <div
                    class="overflow-hidden transition-shadow duration-300 bg-white shadow-sm ring-1 ring-gray-200 hover:shadow-md sm:rounded-2xl"
                >
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-indigo-50/50 to-purple-50/50 sm:px-8">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            <h3 class="text-base font-semibold text-gray-900">
                                Informações do Perfil
                            </h3>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            Atualize as informações do seu perfil e endereço de e-mail.
                        </p>
                    </div>
                    <div class="p-6 sm:p-8">
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                            class="max-w-xl"
                        />
                    </div>
                </div>

                <!-- Atualizar Senha -->
                <div
                    class="overflow-hidden transition-shadow duration-300 bg-white shadow-sm ring-1 ring-gray-200 hover:shadow-md sm:rounded-2xl"
                >
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-indigo-50/50 to-purple-50/50 sm:px-8">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                            <h3 class="text-base font-semibold text-gray-900">
                                Atualizar Senha
                            </h3>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            Certifique-se de que sua conta está usando uma senha longa e aleatória para permanecer segura.
                        </p>
                    </div>
                    <div class="p-6 sm:p-8">
                        <UpdatePasswordForm class="max-w-xl" />
                    </div>
                </div>

                <!-- Excluir Conta -->
                
            </div>
        </div>
    </AuthenticatedLayout>
</template>