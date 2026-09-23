<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref, computed, watch, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';

/* ======================================================
   TIPAGENS
====================================================== */

interface Papel {
    id: number;
    name?: string;
    nome?: string;
    label?: string;
}

interface Usuario {
    id: number;
    name: string;
    email: string;
    roles?: Papel[];
}

/* ======================================================
   PROPS
====================================================== */

const props = defineProps<{
    usuario?: Usuario | null;
}>();

/* ======================================================
   EMITS
====================================================== */

const emit = defineEmits<{
    (e: 'update:registousuario', value: boolean): void;
}>();

/* ======================================================
   FORMULÁRIO
====================================================== */

const form = useForm({
    name: '',
    email: '',
    papel: null as number | null,
});

/* ======================================================
   ESTADOS
====================================================== */

const papeis = ref<Papel[]>([]);
const carregandoPapeis = ref(false);
const erroPapeis = ref<string | null>(null);

/* ======================================================
   COMPUTED
====================================================== */

/** true se estamos em modo edição (recebemos um usuário) */
const modoEdicao = computed(() => !!props.usuario?.id);

/** Título dinâmico */
const tituloFormulario = computed(() =>
    modoEdicao.value ? 'Editar Usuário' : 'Registar Usuário'
);

/** Texto do botão */
const textoBotao = computed(() =>
    modoEdicao.value ? 'Atualizar' : 'Registar'
);

/* ======================================================
   HELPERS
====================================================== */

/** Extrai o label do papel independente do formato da API */
const getPapelLabel = (papel: Papel): string =>
    papel.label ?? papel.name ?? papel.nome ?? '—';

/* ======================================================
   BUSCAR PAPÉIS
====================================================== */

const getPapeis = async () => {
    carregandoPapeis.value = true;
    erroPapeis.value = null;

    try {
        const { data } = await axios.get(route('roles.mostrar'));
console.log('roles.mostrar =>', data);
        papeis.value = Array.isArray(data) ? data : [];

    } catch (error) {
        console.error('Erro ao carregar papéis:', error);

        erroPapeis.value = 'Não foi possível carregar os papéis.';

    } finally {
        carregandoPapeis.value = false;
    }
};

/* ======================================================
   SUBMIT — CRIAR
====================================================== */

const submit = () => {
    form.post(route('cadastrousuario'), {
        onSuccess: () => {
            form.reset();
            emit('update:registousuario', false);
        },
    });
};

/* ======================================================
   SUBMIT — ATUALIZAR
====================================================== */

const atualizarDadosUsuario = () => {
    if (!props.usuario?.id) return;

    form.put(route('usuario.update', props.usuario.id), {
        onSuccess: () => {
            form.reset();
            emit('update:registousuario', false);
        },
    });
};

/* ======================================================
   AÇÃO ÚNICA (criar OU atualizar)
====================================================== */

const handleSubmit = () => {
    if (modoEdicao.value) {
        atualizarDadosUsuario();
    } else {
        submit();
    }
};

/* ======================================================
   FECHAR
====================================================== */

const esconderFormulario = () => {
    form.reset();
    form.clearErrors();
    emit('update:registousuario', false);
};

/* ======================================================
   CICLO DE VIDA
====================================================== */

onMounted(() => {
    getPapeis();
});

/* ======================================================
   WATCH — preencher formulário quando receber usuário
====================================================== */

watch(
    () => props.usuario,
    (novo) => {
        if (!novo) return;

        form.name = novo.name ?? '';
        form.email = novo.email ?? '';
        form.papel = novo.roles?.[0]?.id ?? null;
        form.clearErrors();
    },
    { immediate: true }
);
</script>

<template>
    <Head :title="tituloFormulario" />

    <div class="form-shell">
        <!-- ================= HEADER ================= -->
        <header class="page-header">
            <div>
                <p class="eyebrow">Gestão de usuários</p>
                <h1>{{ tituloFormulario }}</h1>
                <p class="page-description">
                    {{
                        modoEdicao
                            ? 'Atualize os dados do usuário abaixo'
                            : 'Preencha os dados para registar um novo usuário'
                    }}
                </p>
            </div>

            <button
                type="button"
                class="btn-voltar"
                @click="esconderFormulario"
            >
                <i class="fa fa-list" aria-hidden="true"></i>
                Lista de Usuários
            </button>
        </header>

        <!-- ================= FORM ================= -->
        <form
            class="form-card"
            @submit.prevent="handleSubmit"
        >
            <!-- NOME -->
            <div class="form-group">
                <InputLabel for="name" value="Nome" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="block w-full mt-1"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Nome completo"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <!-- EMAIL -->
            <div class="form-group">
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="block w-full mt-1"
                    required
                    autocomplete="email"
                    placeholder="email@exemplo.com"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <!-- PAPEL -->
            <div class="form-group">
                <InputLabel for="papel" value="Papel no sistema" />

                <select
                    id="papel"
                    v-model="form.papel"
                    class="form-select"
                    :disabled="carregandoPapeis"
                    required
                >
                    <option :value="null" disabled>
                        {{ carregandoPapeis ? 'A carregar...' : 'Selecione o papel' }}
                    </option>
                    <option
                        v-for="papel in papeis"
                        :key="papel.id"
                        :value="papel.id"
                    >
                        {{ getPapelLabel(papel) }}
                    </option>
                </select>

                <InputError class="mt-2" :message="form.errors.papel" />

                <p v-if="erroPapeis" class="mt-2 text-sm text-red-600">
                    {{ erroPapeis }}
                </p>
            </div>

            <!-- AÇÕES -->
            <div class="form-actions">
                <button
                    type="button"
                    class="btn-cancelar"
                    @click="esconderFormulario"
                    :disabled="form.processing"
                >
                    Cancelar
                </button>

                <PrimaryButton
                    type="submit"
                    :class="{ 'opacity-50': form.processing }"
                    :disabled="form.processing || carregandoPapeis"
                >
                    <i
                        v-if="form.processing"
                        class="mr-2 fas fa-spinner fa-spin"
                        aria-hidden="true"
                    ></i>
                    {{ form.processing ? 'A processar...' : textoBotao }}
                </PrimaryButton>
            </div>
        </form>
    </div>
</template>

<style scoped>
.form-shell {
    min-height: calc(100vh - 7rem);
    padding: 2rem clamp(1rem, 4vw, 3.5rem);
    color: #17324d;
    background: #f4f7f8;
}

/* -------- Header -------- */
.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.eyebrow {
    margin: 0;
    color: #ad5d3b;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

h1 {
    margin: 0.2rem 0 0.4rem;
    font-family: Georgia, serif;
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 500;
}

.page-description {
    margin: 0;
    color: #6a7b88;
    font-size: 0.9rem;
}

.btn-voltar {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.65rem 1.1rem;
    color: #17324d;
    background: #fff;
    border: 1px solid #cbd8dd;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.btn-voltar:hover {
    color: #fff;
    background: #17324d;
    border-color: #17324d;
}

/* -------- Card do formulário -------- */
.form-card {
    max-width: 640px;
    padding: 2rem;
    background: #fff;
    border: 1px solid #dce5e8;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(23, 50, 77, 0.04);
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-group:last-of-type {
    margin-bottom: 0;
}

/* -------- Select -------- */
.form-select {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: #fff;
    color: #17324d;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.form-select:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}

.form-select:disabled {
    background: #f3f4f6;
    cursor: not-allowed;
    opacity: 0.7;
}

/* -------- Ações -------- */
.form-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 2rem;
    padding-top: 1.25rem;
    border-top: 1px solid #e6edef;
}

.btn-cancelar {
    padding: 0.5rem 1rem;
    color: #6b7280;
    background: transparent;
    border: 1px solid transparent;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-cancelar:hover:not(:disabled) {
    color: #17324d;
    background: #f3f4f6;
}

.btn-cancelar:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* -------- Responsivo -------- */
@media (max-width: 760px) {
    .form-shell {
        padding: 1.25rem 0.75rem;
    }

    .page-header {
        flex-direction: column;
        align-items: stretch;
    }

    .btn-voltar {
        justify-content: center;
    }

    .form-card {
        padding: 1.25rem;
    }

    .form-actions {
        flex-direction: column-reverse;
    }

    .form-actions > * {
        width: 100%;
        justify-content: center;
    }
}
</style>