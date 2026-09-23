<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, onMounted } from "vue";
import { router } from '@inertiajs/vue3';
import { AgGridVue } from "ag-grid-vue3";
import { ModuleRegistry, AllCommunityModule } from "ag-grid-community";
import VendasDetalhesShow from './vendasDetalhesShow.vue';
import Swal from 'sweetalert2';

// CSS do AG Grid (obrigatório para o tema funcionar)
import 'ag-grid-community/styles/ag-grid.css';
import 'ag-grid-community/styles/ag-theme-alpine.css';

// Registrar módulos AG Grid
ModuleRegistry.registerModules([AllCommunityModule]);

// Permissões
import { usePermission } from '@/composables/usePermission';
const { can } = usePermission();

// Props
const props = defineProps({
    vendas: {
        type: Array,
        required: true,
        default: () => []
    },
    viaspagamentos: {
        type: Array,
        required: true,
        default: () => []
    }
});

// ============ STATE ============
const searchText         = ref("");
const usuarioserach      = ref("");
const DataVenda          = ref("");
const viapagamentoserach = ref("");
const mostrar            = ref("tabela");
const FaturaVenda        = ref(null);
const vendadetalhes      = ref([]);
const gridApi            = ref(null);
const isLoading          = ref(false);
const darkMode           = ref(false);

// ============ COMPUTED ============
const usuarios = computed(() => {
    const uniqueUsers = new Set();
    props.vendas.forEach(venda => {
        if (venda.usuario) uniqueUsers.add(venda.usuario);
    });
    return Array.from(uniqueUsers);
});

const dadosFiltrados = computed(() => {
    let dados = [...props.vendas];

    if (usuarioserach.value) {
        dados = dados.filter(item => item.usuario === usuarioserach.value);
    }

    if (DataVenda.value) {
        const dataFiltro = new Date(DataVenda.value).toDateString();
        dados = dados.filter(item => new Date(item.Data).toDateString() === dataFiltro);
    }

    if (viapagamentoserach.value) {
        dados = dados.filter(item => item.Via_pagamento === viapagamentoserach.value);
    }

    return dados;
});

// ============ AG GRID ============
const defaultColDef = {
    resizable: true,
    sortable: true,
    filter: true,
    cellStyle: {
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center'
    }
};

const colDefs = [
    {
        field: "id",
        headerName: "Nr",
        width: 70,
        valueGetter: (params) => params.node.rowIndex + 1
    },
    {
        field: "Fatura",
        headerName: "Fatura",
        width: 100,
        cellStyle: { fontWeight: 'bold' }
    },
    {
        field: "Via_pagamento",
        headerName: "Via",
        width: 100
    },
    {
        field: "Valor_pago",
        headerName: "Valor Pago",
        width: 120,
        valueFormatter: (params) => params.value ? `MT ${Number(params.value).toFixed(2)}` : 'MT 0.00'
    },
    {
        field: "Troco",
        headerName: "Troco",
        width: 100,
        valueFormatter: (params) => params.value ? `MT ${Number(params.value).toFixed(2)}` : 'MT 0.00'
    },
    {
        field: "Data",
        headerName: "Data",
        width: 110,
        valueFormatter: (params) => {
            if (!params.value) return '';
            return new Date(params.value).toLocaleDateString('pt-PT');
        }
    },
    {
        field: "Horas",
        headerName: "Horas",
        width: 90
    },
    {
        field: "Items",
        headerName: "Itens",
        width: 100
    },
    {
        field: "Total",
        headerName: "Total",
        width: 130,
        cellStyle: { fontWeight: 'bold', color: '#2563eb' },
        valueFormatter: (params) => params.value ? `MT ${Number(params.value).toFixed(2)}` : 'MT 0.00'
    },
    {
        field: "accoes",
        headerName: "Ações",
        width: 130,
        sortable: false,
        filter: false,
        cellRenderer: () => {
            return `
                <div class="flex justify-center gap-2">
                    <button class="px-2 py-1 text-xs text-white transition-colors bg-blue-500 rounded btn-visualizar hover:bg-blue-600">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            `;
        }
    }
];

// ============ HANDLERS AG GRID ============
function onGridReady(params) {
    gridApi.value = params.api;
}

function onCellClicked(params) {
    if (params.colDef.field !== 'accoes') return;

    const button = params.event.target.closest("button");
    if (!button) return;

    if (button.classList.contains("btn-visualizar")) {
        visualizarVenda(params.data);
    }

    if (button.classList.contains("btn-eliminar")) {
        eliminarVenda(params.data);
    }
}

// ============ AÇÕES ============
function visualizarVenda(dados) {
    FaturaVenda.value   = dados ?? null;
    vendadetalhes.value = dados?.detalhes?.itens ?? [];
    mostrar.value       = "detalhes";
}

async function eliminarVenda(dados) {
    const result = await Swal.fire({
        title: 'Tem certeza?',
        text: `Deseja realmente eliminar a venda #${dados.Fatura}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, eliminar!',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            // await axios.delete(`/vendas/${dados.id}`);
            Swal.fire('Eliminado!', 'A venda foi eliminada com sucesso.', 'success');
        } catch (error) {
            Swal.fire('Erro!', 'Ocorreu um erro ao eliminar a venda.', 'error');
        }
    }
}

// ============ FILTROS ============
function limparFiltros() {
    usuarioserach.value      = "";
    DataVenda.value          = "";
    viapagamentoserach.value = "";
    searchText.value         = "";
}

// ============ RECARREGAR DADOS ============
function carregarDados() {
    isLoading.value = true;
    router.reload({
        only: ['vendas', 'viaspagamentos'],
        onFinish: () => { isLoading.value = false; }
    });
}

// ============ DARK MODE ============
function toggleDark() {
    darkMode.value = !darkMode.value;
    document.documentElement.classList.toggle('dark', darkMode.value);
    localStorage.setItem('darkMode', darkMode.value);
}

onMounted(() => {
    if (localStorage.getItem('darkMode') === 'true') {
        darkMode.value = true;
        document.documentElement.classList.add('dark');
    }
});

// Expor funções para uso externo
defineExpose({
    limparFiltros,
    carregarDados
});
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
                <div>
                    <h2 class="flex items-center gap-2 text-2xl font-bold text-gray-800 dark:text-white">
                        📊 Vendas Diárias
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Dados das vendas realizadas
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        @click="carregarDados"
                        class="flex items-center gap-2 px-4 py-2 text-white transition-all duration-200 bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed"
                        :disabled="isLoading"
                    >
                        <i class="fas fa-sync-alt" :class="{ 'animate-spin': isLoading }"></i>
                        Atualizar
                    </button>
                </div>
            </div>
        </template>

        <!-- ============ TABELA ============ -->
        <div v-show="mostrar === 'tabela'" class="p-4 bg-white border-t-4 rounded-xl border-cyan-700 dark:bg-gray-800">
            <!-- Filtros -->
            <div class="grid grid-cols-1 gap-4 p-4 mb-6 bg-white rounded dark:bg-gray-800 md:grid-cols-2 lg:grid-cols-4">
                <div class="relative">
                    <i class="absolute text-gray-400 fas fa-search left-3 top-3"></i>
                    <input
                        type="text"
                        v-model="searchText"
                        placeholder="Pesquisar vendas..."
                        class="w-full py-2 pl-10 pr-4 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    />
                </div>

                <div class="relative">
                    <i class="absolute text-gray-400 fas fa-user left-3 top-3"></i>
                    <select
                        v-model="usuarioserach"
                        class="w-full py-2 pl-10 pr-4 border rounded-lg appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    >
                        <option value="">Todos os usuários</option>
                        <option v-for="usuario in usuarios" :key="usuario" :value="usuario">
                            {{ usuario }}
                        </option>
                    </select>
                </div>

                <div class="relative">
                    <i class="absolute text-gray-400 fas fa-calendar left-3 top-3"></i>
                    <input
                        v-model="DataVenda"
                        type="date"
                        class="w-full py-2 pl-10 pr-4 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    />
                </div>

                <div class="relative">
                    <i class="absolute text-gray-400 fas fa-credit-card left-3 top-3"></i>
                    <select
                        v-model="viapagamentoserach"
                        class="w-full py-2 pl-10 pr-4 border rounded-lg appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    >
                        <option value="">Todas as vias</option>
                        <option v-for="value in viaspagamentos" :key="value.nome" :value="value.nome">
                            {{ value.nome }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Grid -->
            <div class="overflow-x-auto">
                <AgGridVue
                    :rowData="dadosFiltrados"
                    :columnDefs="colDefs"
                    :quickFilterText="searchText"
                    :pagination="true"
                    :paginationPageSize="15"
                    :defaultColDef="defaultColDef"
                    class="ag-theme-alpine"
                    style="height: 450px; width: 100%;"
                    @grid-ready="onGridReady"
                    @cell-clicked="onCellClicked"
                />
            </div>
        </div>

        <!-- ============ DETALHES ============ -->
        <div v-show="mostrar === 'detalhes'" class="p-4 bg-white rounded-md dark:bg-gray-800">
            <button
                class="p-2 transition-colors rounded bg-slate-300 hover:bg-slate-400 dark:bg-slate-600 dark:hover:bg-slate-500"
                @click="mostrar = 'tabela'"
            >
                <i class="text-blue-600 fas fa-table fa-lg hover:text-blue-900 dark:text-blue-400"></i>
                <span class="ml-2 dark:text-white">Voltar</span>
            </button>

            <VendasDetalhesShow
                :venda="vendadetalhes"
                :FaturaVenda="FaturaVenda"
                :flag="0"
            />
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.btn-eliminar:hover,
.btn-visualizar:hover {
    transform: scale(1.05);
}

/* Select com seta personalizada */
select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    padding-right: 2.5rem;
}

/* Transições */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Scrollbar */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.dark ::-webkit-scrollbar-thumb {
    background: #475569;
}

.dark ::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}

/* Hover */
.hover\:shadow-xl:hover {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Dark mode overrides */
.dark .bg-white {
    background-color: #1F2937;
}

.dark .text-gray-900 {
    color: #F3F4F6;
}

.dark .text-gray-700 {
    color: #D1D5DB;
}

.dark .border-gray-200 {
    border-color: #374151;
}
</style>