<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const voltar = () => {
    window.history.back();
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="min-h-[70vh] flex items-center justify-center px-4">

            <div class="text-center">

                <div class="mb-4 text-7xl">
                    🚫
                </div>

                <h1 class="text-3xl font-bold text-gray-800">
                    Ação não permitida
                </h1>

                <p class="mt-3 text-gray-500">
                    Você não possui permissão para realizar esta operação.
                </p>

                <div class="flex justify-center gap-3 mt-6">

                    <button
                        @click="voltar"
                        class="px-5 py-2 text-white bg-gray-600 rounded-lg hover:bg-gray-700"
                    >
                        Voltar
                    </button>

                    <a
                        :href="route('dashboard')"
                        class="px-5 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700"
                    >
                        Dashboard
                    </a>

                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>