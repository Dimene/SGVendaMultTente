<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Register from '@/Pages/Auth/Register.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { AgGridVue } from 'ag-grid-vue3';
import {
    ModuleRegistry,
    AllCommunityModule,
    type ColDef,
    type GridApi,
    type GridReadyEvent,
    type ICellRendererParams,
    type CellClickedEvent,
} from 'ag-grid-community';
import Swal from 'sweetalert2';
import axios from 'axios';

ModuleRegistry.registerModules([AllCommunityModule]);

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
    usuarios: Usuario[];
}>();

/* ======================================================
   ESTADOS
====================================================== */

const gridApi = ref<GridApi | null>(null);
const searchText = ref('');
const registousuario = ref(false);
const usuarioSelecionado = ref<Usuario | null>(null);
const apagandoUsuario = ref<number | null>(null);
const resetandoSenha = ref<number | null>(null);

/* ======================================================
   CONFIGURAÇÃO PADRÃO DAS COLUNAS
====================================================== */

const defaultColDef: ColDef = {
    resizable: true,
    sortable: true,
    filter: true,
    cellStyle: {
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
    },
};

/* ======================================================
   HELPERS
====================================================== */

/** Extrai o label do papel independente do formato da API */
const getPapelLabel = (usuario: Usuario): string => {
    const papel = usuario?.roles?.[0];
    if (!papel) return 'Sem Papel';
    return papel.label ?? papel.name ?? papel.nome ?? 'Sem Papel';
};

/* ======================================================
   AÇÕES — EDITAR / CRIAR
====================================================== */

const abrirFormularioEdicao = (user: Usuario) => {
    usuarioSelecionado.value = user;
    registousuario.value = true;
};

const abrirFormularioCriacao = () => {
    usuarioSelecionado.value = null;
    registousuario.value = true;
};

/* ======================================================
   AÇÕES — APAGAR
====================================================== */

const apagarUsuario = async (id: number) => {
    if (apagandoUsuario.value !== null) return;

    const resultado = await Swal.fire({
        title: 'Apagar usuário?',
        text: 'Esta ação não pode ser desfeita.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sim, apagar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
    });

    if (!resultado.isConfirmed) return;

    apagandoUsuario.value = id;

    try {
        const url = route('usuario.destroy', { usuario: id });
        await axios.delete(url);

        await Swal.fire({
            icon: 'success',
            title: 'Sucesso!',
            text: 'Usuário eliminado com sucesso.',
            timer: 1500,
            showConfirmButton: false,
        });

        router.reload({
            only: ['usuarios'],
            preserveScroll: true,
            preserveState: true,
        });
    } catch (error) {
        console.error('Erro ao apagar usuário:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: 'Não foi possível eliminar o usuário.',
        });
    } finally {
        apagandoUsuario.value = null;
    }
};

/* ======================================================
   AÇÕES — RESETAR SENHA
====================================================== */

const resetarSenha = async (id: number) => {
    if (resetandoSenha.value !== null) return;

    const resultado = await Swal.fire({
        title: 'Resetar senha?',
        text: 'Uma nova senha será gerada para este usuário.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d97706',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sim, resetar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
    });

    if (!resultado.isConfirmed) return;

    resetandoSenha.value = id;

    try {
        const url = route('usuario.resetarSenha', { id:id });
        const { data } = await axios.post(url);

        await Swal.fire({
            icon: 'success',
            title: 'Senha resetada!',
            html: data?.password
                ? `Nova senha: <strong>${data.password}</strong>`
                : 'Senha redefinida com sucesso.',
        });
    } catch (error) {
        console.error('Erro ao resetar senha:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: 'Não foi possível resetar a senha.',
        });
    } finally {
        resetandoSenha.value = null;
    }
};

/* ======================================================
   COLUNAS DO AG GRID
====================================================== */

const colDefs = computed<ColDef<Usuario>[]>(() => [
    {
        headerName: 'Nº',
        width: 80,
        valueGetter: (params) => (params.node?.rowIndex ?? 0) + 1,
        sortable: false,
        filter: false,
    },
    {
        field: 'name',
        headerName: 'Nome',
        flex: 1,
        minWidth: 180,
    },
    {
        field: 'email',
        headerName: 'Email',
        flex: 1,
        minWidth: 220,
    },
    {
        headerName: 'Papel',
        flex: 1,
        minWidth: 180,
        valueGetter: ({ data }) => (data ? getPapelLabel(data) : '—'),
    },
    {
        headerName: 'Ações',
        width: 180,
        sortable: false,
        filter: false,
        cellRenderer: (params: ICellRendererParams<Usuario>) => {
            const id = params.data?.id;
            if (!id) return '';

            const estaApagando = apagandoUsuario.value === id;
            const estaResetando = resetandoSenha.value === id;

            return `
                <div class="flex items-center justify-center gap-2">
                    <button
                        title="Editar usuário"
                        class="flex items-center justify-center w-8 h-8 text-blue-600 transition-all duration-200 bg-blue-100 rounded-md btn-edit hover:bg-blue-600 hover:text-white"
                    >
                        <i class="fas fa-edit"></i>
                    </button>

                    <button
                        title="${estaApagando ? 'A apagar...' : 'Apagar usuário'}"
                        class="btn-apagar flex items-center justify-center w-8 h-8 text-red-600 transition-all duration-200 bg-red-100 rounded-md hover:bg-red-600 hover:text-white ${estaApagando ? 'opacity-50 cursor-not-allowed' : ''}"
                        ${estaApagando ? 'disabled' : ''}
                    >
                        ${estaApagando
                            ? '<i class="fas fa-spinner fa-spin"></i>'
                            : '<i class="fas fa-trash"></i>'}
                    </button>

                    <button
                        title="${estaResetando ? 'A resetar...' : 'Resetar senha'}"
                        class="btn-resetsenha flex items-center justify-center w-8 h-8 text-amber-600 transition-all duration-200 bg-amber-100 rounded-md hover:bg-amber-600 hover:text-white ${estaResetando ? 'opacity-50 cursor-not-allowed' : ''}"
                        ${estaResetando ? 'disabled' : ''}
                    >
                        ${estaResetando
                            ? '<i class="fas fa-spinner fa-spin"></i>'
                            : '<i class="fas fa-key"></i>'}
                    </button>
                </div>
            `;
        },
        onCellClicked: (params: CellClickedEvent<Usuario>) => {
            const target = params.event?.target as HTMLElement | null;
            if (!target || !params.data?.id) return;

            if (target.closest('.btn-edit')) {
                abrirFormularioEdicao(params.data);
            } else if (target.closest('.btn-apagar')) {
                apagarUsuario(params.data.id);
            } else if (target.closest('.btn-resetsenha')) {
                resetarSenha(params.data.id);
            }
        },
    },
]);

/* ======================================================
   GRID READY
====================================================== */

const onGridReady = (params: GridReadyEvent) => {
    gridApi.value = params.api;
};
</script>

<template>
    <AuthenticatedLayout>
        <!-- ================= HEADER ================= -->
        <template #header>
            <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
                <div>
                    <h2 class="flex items-center gap-2 text-2xl font-bold text-gray-800 dark:text-white">
                        <i class="fa fa-users" aria-hidden="true"></i>
                        Usuários
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Lista dos usuários registados no sistema
                    </p>
                </div>
            </div>
        </template>

        <!-- ================= LISTA ================= -->
        <div v-show="!registousuario" class="page-shell">
            <header class="page-header">
                <div>
                    <p class="eyebrow">Gestão de usuários</p>
                    <h1>Lista de usuários</h1>
                    <p class="page-description">
                        Total: <strong>{{ props.usuarios.length }}</strong> usuário(s)
                    </p>
                </div>

                <button
                    type="button"
                    class="btn-novo"
                    @click="abrirFormularioCriacao"
                >
                    <i class="fa fa-plus" aria-hidden="true"></i>
                    Novo Usuário
                </button>
            </header>

            <div class="table-panel">
                <div class="table-toolbar">
                    <div>
                        <h2>Usuários registados</h2>
                        <p>Pesquise por nome, email ou papel</p>
                    </div>

                    <div class="search-box">
                        <i class="fas fa-search search-icon" aria-hidden="true"></i>
                        <input
                            type="text"
                            v-model="searchText"
                            placeholder="Pesquisar usuário..."
                            aria-label="Pesquisar usuário"
                        />
                    </div>
                </div>

                <div class="grid-wrapper">
                    <AgGridVue
                        class="ag-theme-alpine purchases-grid"
                        :rowData="props.usuarios"
                        :columnDefs="colDefs"
                        :defaultColDef="defaultColDef"
                        :quickFilterText="searchText"
                        :pagination="true"
                        :paginationPageSize="5"
                        :paginationPageSizeSelector="[5, 10, 20, 50]"
                        @grid-ready="onGridReady"
                    />
                </div>
            </div>
        </div>

        <!-- ================= FORMULÁRIO ================= -->
        <div v-show="registousuario" class="registousuario">
            <Register
                v-model:registousuario="registousuario"
                :usuario="usuarioSelecionado"
            />
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@import 'ag-grid-community/styles/ag-grid.css';
@import 'ag-grid-community/styles/ag-theme-alpine.css';

.page-shell {
    min-height: calc(100vh - 7rem);
    padding: 2rem clamp(1rem, 4vw, 3.5rem);
    color: #17324d;
    background: #f4f7f8;
}

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
}

.btn-novo {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.65rem 1.1rem;
    color: #fff;
    background: #ad5d3b;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.btn-novo:hover {
    background: #8f4a2e;
}

/* -------- Tabela -------- */
.table-panel {
    overflow: hidden;
    border: 1px solid #dce5e8;
    border-radius: 6px;
    background: #fff;
}

.table-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.25rem 1.35rem;
    border-bottom: 1px solid #e6edef;
}

.table-toolbar h2 {
    margin: 0 0 0.25rem;
    font-size: 1.1rem;
}

.table-toolbar p {
    margin: 0;
    color: #6a7b88;
    font-size: 0.85rem;
}

.search-box {
    display: flex;
    align-items: center;
    width: min(100%, 20rem);
    min-width: 18rem;
    border: 1px solid #cbd8dd;
    border-radius: 4px;
    background: #fbfcfc;
}

.search-icon {
    padding-left: 0.75rem;
    color: #ad5d3b;
    font-size: 1rem;
}

.search-box input {
    width: 100%;
    padding: 0.7rem 0.8rem;
    border: 0;
    outline: 0;
    background: transparent;
    color: #17324d;
}

/* -------- Grid -------- */
.grid-wrapper {
    width: 100%;
    padding: 0.5rem;
}

.purchases-grid {
    width: 100%;
    height: 29rem;
    --ag-header-background-color: #17324d;
    --ag-header-foreground-color: #fff;
    --ag-header-font-weight: 700;
    --ag-row-hover-color: #fff5ee;
    --ag-border-color: #e2eaed;
    --ag-font-family: inherit;
}

/* -------- Responsivo -------- */
@media (max-width: 760px) {
    .page-shell {
        padding: 1.25rem 0.75rem;
    }

    .page-header,
    .table-toolbar {
        flex-direction: column;
        align-items: stretch;
    }

    .search-box {
        width: 100%;
        min-width: 0;
    }

    .purchases-grid {
        height: 32rem;
    }
}
</style>