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

interface Permissao {
    id: number;
    name: string;
    label?: string;
    grupo?: string;
    group?: string;
}

interface Role {
    id: number;
    name: string;
    label?: string;
    description?: string;
    permissoes?: Permissao[];
    permissions?: Permissao[];
}

/* ======================================================
   PROPS
====================================================== */

const props = defineProps<{
    role?: Role | null;
}>();

/* ======================================================
   EMITS
====================================================== */

const emit = defineEmits<{
    (e: 'update:mostrarFormulario', value: boolean): void;
}>();

/* ======================================================
   FORM
====================================================== */

const form = useForm({
    name: '',
    label: '',
    description: '',
    permissoes: [] as number[],
});

/* ======================================================
   ESTADOS
====================================================== */

const permissoesDisponiveis = ref<Permissao[]>([]);
const carregandoPermissoes = ref(false);
const erroPermissoes = ref<string | null>(null);
const filtroPermissoes = ref('');

/* ======================================================
   COMPUTED
====================================================== */

const modoEdicao = computed(() => !!props.role?.id);

const tituloFormulario = computed(() =>
    modoEdicao.value ? 'Editar Papel' : 'Novo Papel'
);

const descricaoFormulario = computed(() =>
    modoEdicao.value
        ? 'Atualize os dados do papel e as suas permissões'
        : 'Preencha os dados para registar um novo papel'
);

const textoBotao = computed(() =>
    modoEdicao.value ? 'Atualizar' : 'Registar'
);

/** Permissões filtradas pela pesquisa */
const permissoesFiltradas = computed(() => {
    const termo = filtroPermissoes.value.trim().toLowerCase();
    if (!termo) return permissoesDisponiveis.value;

    return permissoesDisponiveis.value.filter((p) => {
        const alvo = `${p.name} ${p.label ?? ''}`.toLowerCase();
        return alvo.includes(termo);
    });
});

/** Agrupa permissões por grupo */
const permissoesAgrupadas = computed(() => {
    const grupos = new Map<string, Permissao[]>();

    for (const p of permissoesFiltradas.value) {
        const grupo = p.grupo ?? p.group ?? 'Geral';
        if (!grupos.has(grupo)) grupos.set(grupo, []);
        grupos.get(grupo)!.push(p);
    }

    return Array.from(grupos.entries()).map(([grupo, itens]) => ({
        grupo,
        itens,
    }));
});

/** Total de permissões selecionadas */
const totalSelecionadas = computed(() => form.permissoes.length);

/** Total de permissões disponíveis (respeitando filtro) */
const totalDisponiveis = computed(() => permissoesFiltradas.value.length);

/* ======================================================
   HELPERS
====================================================== */

const getPermissaoLabel = (p: Permissao): string =>
    p.label ?? p.name;

/* ======================================================
   CARREGAR PERMISSÕES
====================================================== */

const getPermissoes = async () => {
    carregandoPermissoes.value = true;
    erroPermissoes.value = null;

    try {
        const { data } = await axios.get(route('permissoes.index'));
        permissoesDisponiveis.value = Array.isArray(data)
            ? data
            : data.permissoes ?? [];
    } catch (error) {
        console.error('Erro ao carregar permissões:', error);
        erroPermissoes.value = 'Não foi possível carregar as permissões.';
    } finally {
        carregandoPermissoes.value = false;
    }
};

/* ======================================================
   TOGGLE PERMISSÃO
====================================================== */

const temPermissao = (id: number): boolean =>
    form.permissoes.includes(id);

const togglePermissao = (id: number) => {
    const index = form.permissoes.indexOf(id);
    if (index >= 0) {
        form.permissoes.splice(index, 1);
    } else {
        form.permissoes.push(id);
    }
};

/* ======================================================
   TOGGLE GRUPO (marcar/desmarcar todas de um grupo)
====================================================== */

const grupoTodoMarcado = (itens: Permissao[]): boolean =>
    itens.every((p) => temPermissao(p.id));

const toggleGrupo = (itens: Permissao[]) => {
    const todosMarcados = grupoTodoMarcado(itens);

    if (todosMarcados) {
        // Desmarcar todas
        for (const p of itens) {
            const index = form.permissoes.indexOf(p.id);
            if (index >= 0) form.permissoes.splice(index, 1);
        }
    } else {
        // Marcar todas
        for (const p of itens) {
            if (!temPermissao(p.id)) form.permissoes.push(p.id);
        }
    }
};

/* ======================================================
   MARCAR / DESMARCAR TODAS (global)
====================================================== */

const marcarTodas = () => {
    form.permissoes = permissoesDisponiveis.value.map((p) => p.id);
};

const desmarcarTodas = () => {
    form.permissoes = [];
};

/* ======================================================
   SUBMIT
====================================================== */

const handleSubmit = () => {
    if (modoEdicao.value && props.role?.id) {
        form.put(route('roles.update', { role: props.role.id }), {
            onSuccess: () => {
                form.reset();
                emit('update:mostrarFormulario', false);
            },
        });
    } else {
        form.post(route('roles.store'), {
            onSuccess: () => {
                form.reset();
                emit('update:mostrarFormulario', false);
            },
        });
    }
};

/* ======================================================
   FECHAR
====================================================== */

const fechar = () => {
    form.reset();
    form.clearErrors();
    emit('update:mostrarFormulario', false);
};

/* ======================================================
   CICLO DE VIDA
====================================================== */

onMounted(() => {
    getPermissoes();
});

/* ======================================================
   WATCH — preencher quando receber role
====================================================== */

watch(
    () => props.role,
    (novo) => {
        if (!novo) return;

        form.name = novo.name ?? '';
        form.label = novo.label ?? '';
        form.description = novo.description ?? '';

        const lista = novo.permissoes ?? novo.permissions ?? [];
        form.permissoes = lista.map((p) => p.id);

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
                <p class="eyebrow">Gestão de papéis</p>
                <h1>{{ tituloFormulario }}</h1>
                <p class="page-description">{{ descricaoFormulario }}</p>
            </div>

            <button
                type="button"
                class="btn-voltar"
                @click="fechar"
            >
                <i class="fa fa-list" aria-hidden="true"></i>
                Lista de Papéis
            </button>
        </header>

        <!-- ================= FORM ================= -->
        <form class="form-card" @submit.prevent="handleSubmit">
            <!-- ============ DADOS BÁSICOS ============ -->
            <section class="form-section">
                <h3 class="form-section-title">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    Informação do Papel
                </h3>

                <div class="form-grid">
                    <!-- NOME TÉCNICO -->
                    <div class="form-group">
                        <InputLabel for="name" value="Nome técnico *" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="block w-full mt-1"
                            required
                            autofocus
                            placeholder="ex: admin, gestor, operador"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                        <p class="form-hint">
                            Letras minúsculas, sem espaços. Usado internamente.
                        </p>
                    </div>

                    <!-- LABEL -->
                    <div class="form-group">
                        <InputLabel for="label" value="Label" />
                        <TextInput
                            id="label"
                            v-model="form.label"
                            type="text"
                            class="block w-full mt-1"
                            placeholder="ex: Administrador"
                        />
                        <InputError class="mt-2" :message="form.errors.label" />
                        <p class="form-hint">
                            Nome visível para o utilizador.
                        </p>
                    </div>
                </div>

                <!-- DESCRIÇÃO -->
                <div class="form-group">
                    <InputLabel for="description" value="Descrição" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="form-textarea"
                        placeholder="Breve descrição do papel e das suas responsabilidades..."
                    ></textarea>
                    <InputError class="mt-2" :message="form.errors.description" />
                </div>
            </section>

            <!-- ============ PERMISSÕES ============ -->
            <section class="form-section">
                <h3 class="form-section-title">
                    <i class="fas fa-key" aria-hidden="true"></i>
                    Permissões
                    <span class="section-counter">
                        {{ totalSelecionadas }} / {{ permissoesDisponiveis.length }}
                    </span>
                </h3>

                <!-- Estados de carregamento -->
                <div v-if="carregandoPermissoes" class="loading-state">
                    <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                    A carregar permissões...
                </div>

                <div v-else-if="erroPermissoes" class="error-state">
                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                    {{ erroPermissoes }}
                </div>

                <template v-else>
                    <!-- Barra de ferramentas das permissões -->
                    <div class="permissoes-toolbar">
                        <div class="permissoes-search">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <input
                                type="text"
                                v-model="filtroPermissoes"
                                placeholder="Filtrar permissões..."
                                aria-label="Filtrar permissões"
                            />
                        </div>

                        <div class="permissoes-actions">
                            <button
                                type="button"
                                class="btn-mini"
                                @click="marcarTodas"
                                :disabled="totalDisponiveis === 0"
                            >
                                <i class="fas fa-check-double" aria-hidden="true"></i>
                                Todas
                            </button>

                            <button
                                type="button"
                                class="btn-mini"
                                @click="desmarcarTodas"
                                :disabled="totalSelecionadas === 0"
                            >
                                <i class="fas fa-times" aria-hidden="true"></i>
                                Nenhuma
                            </button>
                        </div>
                    </div>

                    <!-- Sem resultados -->
                    <div
                        v-if="permissoesAgrupadas.length === 0"
                        class="empty-state"
                    >
                        <i class="fas fa-search empty-icon" aria-hidden="true"></i>
                        Nenhuma permissão encontrada para "{{ filtroPermissoes }}".
                    </div>

                    <!-- Grupos de permissões -->
                    <div v-else class="permissoes-grid">
                        <div
                            v-for="bloco in permissoesAgrupadas"
                            :key="bloco.grupo"
                            class="permissoes-grupo"
                        >
                            <header class="grupo-header">
                                <label class="grupo-toggle">
                                    <input
                                        type="checkbox"
                                        :checked="grupoTodoMarcado(bloco.itens)"
                                        @change="toggleGrupo(bloco.itens)"
                                    />
                                    <h4>{{ bloco.grupo }}</h4>
                                </label>
                                <span class="grupo-count">
                                    {{
                                        bloco.itens.filter((p) => temPermissao(p.id)).length
                                    }}/{{ bloco.itens.length }}
                                </span>
                            </header>

                            <div class="grupo-itens">
                                <label
                                    v-for="p in bloco.itens"
                                    :key="p.id"
                                    class="permissao-item"
                                    :class="{ 'permissao-item--active': temPermissao(p.id) }"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="temPermissao(p.id)"
                                        @change="togglePermissao(p.id)"
                                    />
                                    <span>{{ getPermissaoLabel(p) }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </template>

                <InputError class="mt-2" :message="form.errors.permissoes" />
            </section>

            <!-- ============ AÇÕES ============ -->
            <div class="form-actions">
                <button
                    type="button"
                    class="btn-cancelar"
                    @click="fechar"
                    :disabled="form.processing"
                >
                    Cancelar
                </button>

                <PrimaryButton
                    type="submit"
                    :disabled="form.processing || carregandoPermissoes"
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
    max-width: 900px;
    padding: 2rem;
    background: #fff;
    border: 1px solid #dce5e8;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(23, 50, 77, 0.04);
}

/* -------- Secções -------- */
.form-section {
    padding-bottom: 1.5rem;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid #e6edef;
}

.form-section:last-of-type {
    border-bottom: none;
    padding-bottom: 0;
    margin-bottom: 0;
}

.form-section-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0 0 1.25rem;
    color: #17324d;
    font-size: 1rem;
    font-weight: 700;
}

.form-section-title i {
    color: #ad5d3b;
}

.section-counter {
    margin-left: auto;
    padding: 0.2rem 0.7rem;
    color: #ad5d3b;
    background: #fff5ee;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
}

/* -------- Grid de campos -------- */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-hint {
    margin-top: 0.35rem;
    color: #8a97a3;
    font-size: 0.78rem;
}

/* -------- Textarea -------- */
.form-textarea {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    padding: 0.6rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    color: #17324d;
    font-family: inherit;
    font-size: 0.9rem;
    resize: vertical;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.form-textarea:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}

/* -------- Estados de carregamento/erro -------- */
.loading-state,
.error-state {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 1.25rem;
    border-radius: 6px;
    font-size: 0.9rem;
}

.loading-state {
    color: #6a7b88;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
}

.error-state {
    color: #991b1b;
    background: #fef2f2;
    border: 1px solid #fecaca;
}

/* -------- Toolbar de permissões -------- */
.permissoes-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.permissoes-search {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    min-width: 200px;
    padding: 0.5rem 0.75rem;
    border: 1px solid #cbd8dd;
    border-radius: 6px;
    background: #fbfcfc;
}

.permissoes-search i {
    color: #ad5d3b;
    font-size: 0.85rem;
}

.permissoes-search input {
    flex: 1;
    border: 0;
    outline: 0;
    background: transparent;
    color: #17324d;
    font-size: 0.88rem;
}

.permissoes-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-mini {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.4rem 0.75rem;
    color: #17324d;
    background: #fff;
    border: 1px solid #cbd8dd;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-mini:hover:not(:disabled) {
    color: #fff;
    background: #17324d;
    border-color: #17324d;
}

.btn-mini:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* -------- Grid de permissões -------- */
.permissoes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
}

.permissoes-grupo {
    padding: 1rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
}

.grupo-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding-bottom: 0.6rem;
    margin-bottom: 0.6rem;
    border-bottom: 1px dashed #d1d5db;
}

.grupo-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    user-select: none;
}

.grupo-toggle input[type='checkbox'] {
    width: 1rem;
    height: 1rem;
    accent-color: #ad5d3b;
    cursor: pointer;
}

.grupo-header h4 {
    margin: 0;
    color: #ad5d3b;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.grupo-count {
    padding: 0.15rem 0.55rem;
    color: #17324d;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 700;
}

.grupo-itens {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.permissao-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.5rem;
    border-radius: 4px;
    font-size: 0.88rem;
    cursor: pointer;
    user-select: none;
    transition: background-color 0.12s ease;
}

.permissao-item:hover {
    background: #fff;
}

.permissao-item--active {
    background: #fff5ee;
    color: #17324d;
    font-weight: 600;
}

.permissao-item input[type='checkbox'] {
    width: 1rem;
    height: 1rem;
    accent-color: #ad5d3b;
    cursor: pointer;
    flex-shrink: 0;
}

/* -------- Empty state -------- */
.empty-state {
    padding: 2rem 1rem;
    text-align: center;
    color: #6a7b88;
    background: #f9fafb;
    border: 1px dashed #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
}

.empty-icon {
    display: block;
    margin-bottom: 0.5rem;
    color: #d47a50;
    font-size: 1.5rem;
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