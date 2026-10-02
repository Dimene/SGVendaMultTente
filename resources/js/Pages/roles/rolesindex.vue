<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import RoleForm from '@/pages/roles/RoleForm.vue';
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

interface Permissao {
    id: number;
    name?: string;
    nome?: string;
    label?: string;
}

interface Role {
    id: number;
    name: string;
    label?: string;
    description?: string;
    permissoes?: Permissao[];
    permissions?: Permissao[];
    users_count?: number;
    users?: object;
    created_at?: string;
    updated_at?: string;
}

/* ======================================================
   PROPS
====================================================== */

const props = defineProps<{
    roles: Role[];
}>();

/* ======================================================
   ESTADOS
====================================================== */

const gridApi = ref<GridApi | null>(null);
const searchText = ref('');
const mostrarFormulario = ref(false);
const roleSelecionado = ref<Role | null>(null);
const eliminandoRole = ref<number | null>(null);

/* ======================================================
   CONFIGURAÇÃO PADRÃO
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

/** Label do papel (fallback para name) */
const getRoleLabel = (role: Role): string =>
    role.label ?? role.name ?? '—';

/** Lista de permissões (aceita 2 formatos) */
const getPermissoes = (role: Role): Permissao[] =>
    role.permissoes ?? role.permissions ?? [];

/** Total de permissões */
const getTotalPermissoes = (role: Role): number =>
    getPermissoes(role).length;
    
  


    //verificar  se e admin dar todas ()
   

/** Total de usuários que usam este papel */
const getTotalUsuarios = (role: Role): number =>
    role.users_count ?? role.users.length ?? 0;

/** Formata data */
const formatarData = (valor?: string): string => {
    if (!valor) return '—';
    return new Date(valor).toLocaleDateString('pt-MZ', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

/* ======================================================
   CONTADORES
====================================================== */

const contadores = computed(() => {
    const total = props.roles.length;
    const comPermissoes = props.roles.filter(
        (r) => getTotalPermissoes(r) > 0
    ).length;
    const emUso = props.roles.filter(
        (r) => getTotalUsuarios(r) > 0
    ).length;

    return {
        total,
        comPermissoes,
        semPermissoes: total - comPermissoes,
        emUso,
    };
});

/* ======================================================
   AÇÕES — CRIAR / EDITAR
====================================================== */

const abrirFormularioCriacao = () => {
    roleSelecionado.value = null;
    mostrarFormulario.value = true;
};

const abrirFormularioEdicao = (role: Role) => {
    roleSelecionado.value = role;
    mostrarFormulario.value = true;
};

/* ======================================================
   AÇÕES — ELIMINAR
====================================================== */

const eliminarRole = async (role: Role) => {
    if (eliminandoRole.value !== null) return;

    // Bloqueia se o papel estiver em uso
    const totalUsuarios = getTotalUsuarios(role);
    if (totalUsuarios > 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Papel em uso',
            text: `Este papel está atribuído a ${totalUsuarios} usuário(s). Remova-o primeiro.`,
        });
        return;
    }

    const resultado = await Swal.fire({
        title: 'Eliminar papel?',
        text: 'Esta ação não pode ser desfeita.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sim, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
    });

    if (!resultado.isConfirmed) return;

    eliminandoRole.value = role.id;

    try {
        await axios.delete(route('roles.destroy', { role: role.id }));

        await Swal.fire({
            icon: 'success',
            title: 'Sucesso!',
            text: 'Papel eliminado com sucesso.',
            timer: 1500,
            showConfirmButton: false,
        });

        router.reload({
            only: ['roles'],
            preserveScroll: true,
            preserveState: true,
        });
    } catch (error) {
        console.error('Erro ao eliminar papel:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: 'Não foi possível eliminar o papel.',
        });
    } finally {
        eliminandoRole.value = null;
        gridApi.value?.refreshCells({ force: true });
    }
};

/* ======================================================
   COLUNAS DO AG GRID
====================================================== */

const colDefs = computed<ColDef<Role>[]>(() => [
    {
        headerName: 'Nº',
        width: 70,
        valueGetter: (params) => (params.node?.rowIndex ?? 0) + 1,
        sortable: false,
        filter: false,
    },
    {
        field: 'name',
        headerName: 'Nome técnico',
        flex: 1,
        minWidth: 160,
        cellRenderer: (params: ICellRendererParams<Role>) => {
            const data = params.data;
            if (!data) return '';
            return `
                <div class="role-name">
                    <i class="fas fa-shield-halved role-icon"></i>
                    <span>${data.name}</span>
                </div>
            `;
        },
    },
    {
        headerName: 'Label',
        flex: 1,
        minWidth: 160,
        valueGetter: ({ data }) => (data ? getRoleLabel(data) : '—'),
    },
    {
        field: 'description',
        headerName: 'Descrição',
        flex: 1,
        minWidth: 220,
        valueGetter: ({ data }) => data?.description ?? '—',
    },
  {
    headerName: 'Permissões',
    width: 150,
    valueGetter: ({ data }) => {
        if (!data) return 0;
        // Se for admin, retorna um valor alto para ordenação ficar no topo (opcional)
        if (data.name?.toLowerCase() === 'admin') return Infinity;
        return getTotalPermissoes(data);
    },
    cellRenderer: (params: ICellRendererParams<Role>) => {
        const data = params.data;
        if (!data) return '';

        // Caso especial: ADMIN mostra "Todas"
        if (data.name?.toLowerCase() === 'admin') {
            return `
                <div class="flex items-center justify-center">
                    <span class="badge badge--admin">
                        <i class="fas fa-crown"></i>
                        Todas
                    </span>
                </div>
            `;
        }

        const total = getTotalPermissoes(data);

        if (total === 0) {
            return `
                <div class="flex items-center justify-center">
                    <span class="badge badge--vazio">
                        <i class="fas fa-lock"></i>
                        Sem permissões
                    </span>
                </div>
            `;
        }

        return `
            <div class="flex items-center justify-center">
                <span class="badge badge--permissoes">
                    <i class="fas fa-key"></i>
                    ${total} permiss${total === 1 ? 'ão' : 'ões'}
                </span>
            </div>
        `;
    },
},
    {
        headerName: 'Usuários',
        width: 130,
        valueGetter: ({ data }) => (data ? getTotalUsuarios(data) : 0),
        cellRenderer: (params: ICellRendererParams<Role>) => {
            const data = params.data;
            if (!data) return '';

            const total = getTotalUsuarios(data);
            const classe = total > 0 ? 'badge--em-uso' : 'badge--vazio';

            return `
                <div class="flex items-center justify-center">
                    <span class="badge ${classe}">
                        <i class="fas fa-users"></i>
                        ${total}
                    </span>
                </div>
            `;
        },
    },
    {
        field: 'created_at',
        headerName: 'Criado em',
        width: 130,
        valueGetter: ({ data }) => (data ? formatarData(data.created_at) : '—'),
    },
    {
        headerName: 'Ações',
        width: 130,
        sortable: false,
        filter: false,
        cellRenderer: (params: ICellRendererParams<Role>) => {
            const data = params.data;
            if (!data?.id) return '';

            const estaEliminando = eliminandoRole.value === data.id;

            return `
                <div class="flex items-center justify-center gap-2">
                    <button
                        title="Editar papel"
                        class="flex items-center justify-center w-8 h-8 text-blue-600 transition-all duration-200 bg-blue-100 rounded-md btn-editar hover:bg-blue-600 hover:text-white"
                    >
                        <i class="fas fa-edit"></i>
                    </button>

                    <button
                        title="${estaEliminando ? 'A eliminar...' : 'Eliminar papel'}"
                        class="btn-eliminar flex items-center justify-center w-8 h-8 text-red-600 transition-all duration-200 bg-red-100 rounded-md hover:bg-red-600 hover:text-white ${estaEliminando ? 'opacity-50 cursor-not-allowed' : ''}"
                        ${estaEliminando ? 'disabled' : ''}
                    >
                        ${estaEliminando
                            ? '<i class="fas fa-spinner fa-spin"></i>'
                            : '<i class="fas fa-trash"></i>'}
                    </button>
                </div>
            `;
        },
        onCellClicked: (params: CellClickedEvent<Role>) => {
            const target = params.event?.target as HTMLElement | null;
            const data = params.data;
            if (!target || !data?.id) return;

            if (target.closest('.btn-editar')) {
                abrirFormularioEdicao(data);
            } else if (target.closest('.btn-eliminar')) {
                eliminarRole(data);
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
                        <i class="fa fa-shield-halved" aria-hidden="true"></i>
                        Papéis
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Lista dos papéis de permissão de uso do sistema
                    </p>
                </div>
            </div>
        </template>

        <!-- ================= LISTA ================= -->
        <div v-show="!mostrarFormulario" class="page-shell">
            <header class="page-header">
                <div>
                    <p class="eyebrow">Gestão de papéis</p>
                    <h1>Lista de papéis</h1>
                    <p class="page-description">
                        Total: <strong>{{ props.roles.length }}</strong> papel(éis)
                    </p>
                </div>

                <button
                    type="button"
                    class="btn-novo"
                    @click="abrirFormularioCriacao"
                >
                    <i class="fa fa-plus" aria-hidden="true"></i>
                    Novo Papel
                </button>
            </header>

            <!-- ================= RESUMO ================= -->
            <div class="summary-grid">
                <div class="summary-card">
                    <p class="summary-label">Total</p>
                    <strong>{{ contadores.total }}</strong>
                    <p class="summary-detail">papéis registados</p>
                </div>

                <div class="summary-card summary-card--accent">
                    <p class="summary-label">Em uso</p>
                    <strong>{{ contadores.emUso }}</strong>
                    <p class="summary-detail">atribuídos a usuários</p>
                </div>

                <div class="summary-card">
                    <p class="summary-label">Sem permissões</p>
                    <strong>{{ contadores.semPermissoes }}</strong>
                    <p class="summary-detail">papéis vazios</p>
                </div>
            </div>

            <!-- ================= TABELA ================= -->
            <div class="table-panel">
                <div class="table-toolbar">
                    <div>
                        <h2>Papéis do sistema</h2>
                        <p>Pesquise por nome, label ou descrição</p>
                    </div>

                    <div class="search-box">
                        <i class="fas fa-search search-icon" aria-hidden="true"></i>
                        <input
                            type="text"
                            v-model="searchText"
                            placeholder="Pesquisar papel..."
                            aria-label="Pesquisar papel"
                        />
                    </div>
                </div>

                <div class="grid-wrapper">
                    <AgGridVue
                        class="ag-theme-alpine roles-grid"
                        :rowData="props.roles"
                        :columnDefs="colDefs"
                        :defaultColDef="defaultColDef"
                        :quickFilterText="searchText"
                        :pagination="true"
                        :paginationPageSize="10"
                        :paginationPageSizeSelector="[5, 10, 20, 50]"
                        @grid-ready="onGridReady"
                    />
                </div>
            </div>
        </div>

        <!-- ================= FORMULÁRIO ================= -->
        <div v-show="mostrarFormulario" class="formulario">
            <RoleForm
                v-model:mostrarFormulario="mostrarFormulario"
                :role="roleSelecionado"
            />
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@import 'ag-grid-community/styles/ag-grid.css';
@import 'ag-grid-community/styles/ag-theme-alpine.css';

/* -------- Página -------- */
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

/* -------- Cards resumo -------- */
.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.summary-card {
    padding: 1.1rem 1.25rem;
    border: 1px solid #dce5e8;
    border-radius: 6px;
    background: #fff;
}

.summary-card--accent {
    border-color: #d47a50;
    background: #fff8f3;
}

.summary-label {
    margin: 0;
    color: #ad5d3b;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.summary-card strong {
    display: block;
    margin: 0.4rem 0 0.2rem;
    font-size: 1.7rem;
    font-weight: 700;
    line-height: 1;
}

.summary-detail {
    margin: 0;
    color: #6a7b88;
    font-size: 0.8rem;
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

.roles-grid {
    width: 100%;
    height: 32rem;
    --ag-header-background-color: #17324d;
    --ag-header-foreground-color: #fff;
    --ag-header-font-weight: 700;
    --ag-row-hover-color: #fff5ee;
    --ag-border-color: #e2eaed;
    --ag-font-family: inherit;
}

/* -------- Nome do papel (dentro do grid) -------- */
:deep(.role-name) {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
}

:deep(.role-icon) {
    color: #ad5d3b;
    font-size: 0.9rem;
}

/* -------- Badges -------- */
:deep(.badge) {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.7rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    white-space: nowrap;
}

:deep(.badge--permissoes) {
    color: #1e3a8a;
    background: #dbeafe;
    border: 1px solid #93c5fd;
}

:deep(.badge--em-uso) {
    color: #14532d;
    background: #dcfce7;
    border: 1px solid #86efac;
}

:deep(.badge--vazio) {
    color: #6b7280;
    background: #f3f4f6;
    border: 1px solid #d1d5db;
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

    .roles-grid {
        height: 34rem;
    }
}
</style>