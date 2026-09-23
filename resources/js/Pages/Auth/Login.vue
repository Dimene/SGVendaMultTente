<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

/* ─────────────────────────────────────────────────────────
 *  Contrato do componente — explícito e tipado
 * ───────────────────────────────────────────────────────── */
interface Props {
    canResetPassword?: boolean;
    status?: string;
}

defineProps<Props>();

/* ─────────────────────────────────────────────────────────
 *  Estado do formulário — uma única fonte de verdade
 * ───────────────────────────────────────────────────────── */
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

/* ─────────────────────────────────────────────────────────
 *  Derivados — a UI reage, não pergunta
 * ───────────────────────────────────────────────────────── */
const isSubmitting = computed(() => form.processing);

/* ─────────────────────────────────────────────────────────
 *  Ação — intenção clara, efeito colateral explícito
 * ───────────────────────────────────────────────────────── */
const submit = (): void => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Entrar" />
        <div class="login-card">
            <section class="brand-panel">
                <div class="brand-mark"><ApplicationLogo class="brand-logo" /></div>
                <p class="brand-kicker">Gestão comercial</p>
                <h1>{{ $page.props.empresa?.nome_fantasia || $page.props.empresa?.nome || 'ERP System' }}</h1>
                <p class="brand-copy">Uma visão mais clara para cada compra, venda e decisão do seu negócio.</p>
                <div class="brand-line"></div>
                <span class="brand-caption">Acesso seguro ao seu painel</span>
            </section>

            <section class="form-panel">
                <div class="form-intro">
                    <span class="welcome-icon"><i class="fas fa-arrow-right-to-bracket" aria-hidden="true"></i></span>
                    <p class="form-kicker">Bem-vindo de volta</p>
                    <h2>Entre na sua conta</h2>
                    <p>Use os seus dados para continuar.</p>
                </div>

                <div v-if="status" class="status-message" role="status">{{ status }}</div>

                <form class="login-form" novalidate @submit.prevent="submit">
                    <div class="field-group">
                        <InputLabel for="email" value="E-mail" />
                        <div class="input-wrap"><i class="fas fa-envelope" aria-hidden="true"></i><TextInput id="email" v-model="form.email" type="email" name="email" class="modern-input" placeholder="seu@email.com" autocomplete="username" autofocus required :aria-invalid="!!form.errors.email" :aria-describedby="form.errors.email ? 'email-error' : undefined" /></div>
                        <InputError id="email-error" class="field-error" :message="form.errors.email" />
                    </div>

                    <div class="field-group">
                        <div class="password-heading"><InputLabel for="password" value="Senha" /><Link v-if="canResetPassword" :href="route('password.request')">Esqueceu a senha?</Link></div>
                        <div class="input-wrap"><i class="fas fa-lock" aria-hidden="true"></i><TextInput id="password" v-model="form.password" type="password" name="password" class="modern-input" placeholder="Introduza a sua senha" autocomplete="current-password" required :aria-invalid="!!form.errors.password" :aria-describedby="form.errors.password ? 'password-error' : undefined" /></div>
                        <InputError id="password-error" class="field-error" :message="form.errors.password" />
                    </div>

                    <label class="remember-line"><Checkbox v-model:checked="form.remember" name="remember" /><span>Lembrar-me</span></label>

                    <PrimaryButton type="submit" class="login-button" :class="{ 'is-loading': isSubmitting }" :disabled="isSubmitting" :aria-busy="isSubmitting"><span>{{ isSubmitting ? 'Entrando...' : 'Entrar no painel' }}</span><i v-if="isSubmitting" class="fas fa-spinner fa-spin" aria-hidden="true"></i><i v-else class="fas fa-arrow-right" aria-hidden="true"></i></PrimaryButton>
                </form>
                <p class="security-note"><i class="fas fa-shield-halved" aria-hidden="true"></i> Os seus dados são protegidos com segurança.</p>
            </section>
        </div>
    </GuestLayout>
</template>

<style scoped>
.login-card { display: grid; width: min(100%, 68rem); overflow: hidden; border: 1px solid rgb(255 255 255 / .2); border-radius: 1.25rem; background: #fff; box-shadow: 0 24px 70px rgb(19 50 77 / .18); grid-template-columns: minmax(0, .9fr) minmax(24rem, 1.1fr); }
.brand-panel { position: relative; display: flex; flex-direction: column; justify-content: center; min-height: 36rem; padding: clamp(2rem, 5vw, 4.5rem); overflow: hidden; color: #fff; background: #17324d; }
.brand-panel::before, .brand-panel::after { position: absolute; width: 18rem; height: 18rem; border: 1px solid rgb(255 255 255 / .13); border-radius: 50%; content: ''; } .brand-panel::before { right: -9rem; top: -6rem; } .brand-panel::after { bottom: -11rem; left: -8rem; }
.brand-mark { display: grid; width: 4.5rem; height: 4.5rem; place-items: center; margin-bottom: 2rem; padding: .8rem; border: 1px solid rgb(255 255 255 / .25); border-radius: .8rem; background: rgb(255 255 255 / .1); } .brand-logo { width: 100%; height: 100%; fill: #fff; color: #fff; } .brand-kicker, .form-kicker { margin: 0 0 .65rem; color: #e49a70; font-size: .72rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; } .brand-panel h1 { position: relative; z-index: 1; max-width: 22rem; margin: 0; font: 500 clamp(2.2rem, 4vw, 3.7rem)/1.05 Georgia, serif; } .brand-copy { position: relative; z-index: 1; max-width: 24rem; margin: 1.25rem 0 2rem; color: #c6d4dc; line-height: 1.7; } .brand-line { width: 3.2rem; height: 3px; margin-bottom: .8rem; background: #d47a50; } .brand-caption { color: #9fb3c0; font-size: .78rem; }
.form-panel { display: flex; flex-direction: column; justify-content: center; padding: clamp(2rem, 5vw, 4.5rem); } .form-intro { margin-bottom: 2rem; } .welcome-icon { display: grid; width: 2.7rem; height: 2.7rem; place-items: center; margin-bottom: 1.2rem; border-radius: .7rem; color: #ad5d3b; background: #fff1e9; } .form-kicker { margin-bottom: .4rem; } .form-intro h2 { margin: 0 0 .45rem; color: #17324d; font: 500 2rem Georgia, serif; } .form-intro p:last-child { margin: 0; color: #6a7b88; }
.status-message { margin-bottom: 1rem; padding: .75rem 1rem; border: 1px solid #b7e0c5; border-radius: .5rem; color: #27643b; background: #effaf2; font-size: .85rem; } .login-form { display: grid; gap: 1.25rem; } .field-group { display: grid; gap: .45rem; } .field-group :deep(label) { color: #526675; font-size: .8rem; font-weight: 700; } .input-wrap { display: flex; align-items: center; gap: .65rem; padding: 0 .85rem; border: 1px solid #cbd8dd; border-radius: .45rem; background: #fbfcfc; transition: border-color .2s, box-shadow .2s; } .input-wrap:focus-within { border-color: #d47a50; box-shadow: 0 0 0 3px rgb(212 122 80 / .14); } .input-wrap > i { color: #9aabb4; font-size: .85rem; } .modern-input { width: 100%; padding: .8rem 0; border: 0; outline: 0; background: transparent; box-shadow: none !important; } .field-error { margin-top: 0; font-size: .78rem; } .password-heading { display: flex; align-items: center; justify-content: space-between; gap: 1rem; } .password-heading a { color: #ad5d3b; font-size: .75rem; font-weight: 700; } .remember-line { display: flex; align-items: center; gap: .55rem; color: #6a7b88; font-size: .8rem; } .login-button { display: flex; width: 100%; align-items: center; justify-content: center; gap: .7rem; min-height: 3.1rem; margin-top: .35rem; border: 0; border-radius: .45rem; color: #fff; background: #ad5d3b; font-weight: 800; } .login-button:hover { background: #914b30; } .login-button.is-loading { opacity: .7; } .security-note { display: flex; align-items: center; justify-content: center; gap: .45rem; margin: 1.5rem 0 0; color: #8b9ba4; font-size: .72rem; } .security-note i { color: #6b9f7a; }
:global(.dark) .login-card { border-color: #344752; background: #22333e; } :global(.dark) .form-panel { background: #22333e; } :global(.dark) .form-intro h2 { color: #f3f4f6; } :global(.dark) .form-intro p:last-child, :global(.dark) .remember-line { color: #aebdc5; } :global(.dark) .input-wrap { border-color: #425864; background: #172a35; } :global(.dark) .modern-input { color: #f3f4f6; } :global(.dark) .field-group :deep(label) { color: #c7d7df; }
@media (max-width: 720px) { .login-card { display: block; border-radius: .8rem; } .brand-panel { min-height: 16rem; padding: 2rem; } .brand-mark { width: 3.5rem; height: 3.5rem; margin-bottom: 1.2rem; } .brand-panel h1 { font-size: 2.2rem; } .brand-copy { margin: .8rem 0 1rem; font-size: .9rem; } .form-panel { padding: 2rem; } }
</style>