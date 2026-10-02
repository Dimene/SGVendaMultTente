<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});

// Controle do alerta de sucesso
const showSuccessAlert = ref(false);

// Observa quando o form é salvo com sucesso e exibe o alerta
watch(
    () => form.recentlySuccessful,
    (novoValor) => {
        if (novoValor) {
            showSuccessAlert.value = true;
            setTimeout(() => {
                showSuccessAlert.value = false;
            }, 3000);
        }
    }
);
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Informações do Perfil
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Atualize as informações do seu perfil e endereço de e-mail.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel for="name" value="Nome" />

                <TextInput
                    id="name"
                    type="text"
                    class="block w-full mt-1"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="E-mail" />

                <TextInput
                    id="email"
                    type="email"
                    class="block w-full mt-1"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Seu endereço de e-mail não está verificado.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="text-sm text-gray-600 underline rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Clique aqui para reenviar o e-mail de verificação.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    Um novo link de verificação foi enviado para o seu e-mail.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">
                    Salvar
                </PrimaryButton>
            </div>
        </form>

        <!-- Alerta de sucesso animado (toast) -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-2 opacity-0"
        >
            <div
                v-if="showSuccessAlert"
                class="fixed z-50 flex items-center gap-3 px-4 py-3 bg-white border border-green-200 shadow-lg bottom-6 right-6 rounded-xl shadow-green-500/10"
            >
                <div class="flex items-center justify-center w-8 h-8 bg-green-100 rounded-full shrink-0">
                    <svg
                        class="w-5 h-5 text-green-600"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2.5"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4.5 12.75l6 6 9-13.5"
                        />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">
                        Perfil atualizado!
                    </p>
                    <p class="text-xs text-gray-500">
                        Suas alterações foram salvas com sucesso.
                    </p>
                </div>
                <button
                    @click="showSuccessAlert = false"
                    class="ml-2 text-gray-400 transition hover:text-gray-600"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </Transition>
    </section>
</template>