<template>
    <AuthenticatedLayout>
         <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        📊 Vendas Diarias
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                            <!-- {{ dataAtualizacao.toLocaleTimeString('pt-MZ') }} -->
                        </span>
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Dados das vendas Realizadas *:
                        <!-- {{ crescimentoMedio }}% -->
                    </p>
                </div>

                <div class="flex gap-3 flex-wrap">
                    <button
                        @click="toggleDark"
                        class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-200"
                    >
                        <i :class="darkMode ? 'fas fa-sun text-yellow-400' : 'fas fa-moon'"></i>
                        {{ darkMode ? 'Claro' : 'Escuro' }}
                    </button>

                    <button
                        @click="carregarDados"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 flex items-center gap-2"
                        :disabled="isLoading"
                    >
                        <i class="fas fa-sync-alt" :class="{'animate-spin': isLoading}"></i>
                        Atualizar
                    </button>
                </div>
            </div>
        </template>

        <!-- Tabela de Vendas -->
        <div class="p-4 bg-white rounded-xl border-t-4 border-cyan-700" v-show="mostrar === 'tabela'">
            <!-- Filtros -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 bg-white p-4 mb-6 rounded">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    <input
                        type="text"
                        v-model="searchText"
                        placeholder="Pesquisar vendas..."
                        class="border rounded-lg pl-10 pr-4 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <div class="relative">
                    <i class="fas fa-user absolute left-3 top-3 text-gray-400"></i>
                    <select
                        v-model="usuarioserach"
                        class="border rounded-lg pl-10 pr-4 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none"
                    >
                        <option value="">Todos os usuários</option>
                        <option v-for="usuario in usuarios" :key="usuario" :value="usuario">
                            {{ usuario }}
                        </option>
                    </select>
                </div>

                <div class="relative">
                    <i class="fas fa-calendar absolute left-3 top-3 text-gray-400"></i>
                    <input
                        v-model="DataVenda"
                        type="date"
                        class="border rounded-lg pl-10 pr-4 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <div class="relative">
                    <i class="fas fa-credit-card absolute left-3 top-3 text-gray-400"></i>
                    <select
                        v-model="viapagamentoserach"
                        class="border rounded-lg pl-10 pr-4 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none"
                    >
                        <option value="">Todas as vias</option>
                        <option v-for="value in viaspagamentos" :key="value.nome" :value="value.nome">
                            {{ value.nome }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Tabela -->
            <div class="overflow-x-auto">
                <AgGridVue
                    :rowData="dadosFiltrados"
                    :columnDefs="colDefs"
                    :quickFilterText="searchText"
                    :pagination="true"
                    :paginationPageSize="15"
                    class="ag-theme-alpine"
                    style="height: 450px; width: 100%;"
                    @grid-ready="onGridReady"
                    :defaultColDef="defaultColDef"
                />
            </div>
        </div>

        <!-- Detalhes da Venda -->
        <div class="p-4 bg-white rounded-md" v-show="mostrar === 'detalhes'">
            <button
                class="bg-slate-300 rounded p-2 hover:bg-slate-400 transition-colors"
                @click="mostrar = 'tabela'"
            >
                <i class="fas fa-table fa-lg text-blue-600 hover:text-blue-900"></i>
                <span class="ml-2">Voltar</span>
            </button>

            <vendasDetalhesShow
                :venda="vendadetalhes"
                :FaturaVenda="FaturaVenda"
                :flag="0"
            />
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, watch, onMounted } from "vue";
import { AgGridVue } from "ag-grid-vue3";
import { ModuleRegistry, AllCommunityModule } from "ag-grid-community";
import vendasDetalhesShow from './vendasDetalhesShow.vue';
import Swal from 'sweetalert2';

// Registrar módulos AG Grid
ModuleRegistry.registerModules([AllCommunityModule]);

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

// Refs
const searchText = ref("");
const usuarioserach = ref("");
const DataVenda = ref("");
const viapagamentoserach = ref("");
const mostrar = ref("tabela");
const FaturaVenda = ref(null);
const vendadetalhes = ref([]);
const gridApi = ref(null);

// Computed: Usuários únicos
const usuarios = computed(() => {
    const uniqueUsers = new Set();
    props.vendas.forEach(venda => {
        if (venda.usuario) {
            uniqueUsers.add(venda.usuario);
        }
    });
    return Array.from(uniqueUsers);
});

// Computed: Dados filtrados
const dadosFiltrados = computed(() => {
    let dados = [...props.vendas];

    // Filtro por usuário
    if (usuarioserach.value) {
        dados = dados.filter(item => item.usuario === usuarioserach.value);
    }

    // Filtro por data
    if (DataVenda.value) {
        const dataFiltro = new Date(DataVenda.value);
        dados = dados.filter(item => {
            const dataVenda = new Date(item.Data);
            return dataVenda.toDateString() === dataFiltro.toDateString();
        });
    }

    // Filtro por via de pagamento
    if (viapagamentoserach.value) {
        dados = dados.filter(item => item.Via_pagamento === viapagamentoserach.value);
    }

    return dados;
});

// Configuração padrão das colunas
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

// Definição das colunas
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
        valueFormatter: (params) => {
            return params.value ? `MT ${Number(params.value).toFixed(2)}` : 'MT 0.00';
        }
    },
    {
        field: "Troco",
        headerName: "Troco",
        width: 100,
        valueFormatter: (params) => {
            return params.value ? `MT ${Number(params.value).toFixed(2)}` : 'MT 0.00';
        }
    },
    {
        field: "Data",
        headerName: "Data",
        width: 110,
        valueFormatter: (params) => {
            if (!params.value) return '';
            const date = new Date(params.value);
            return date.toLocaleDateString('pt-PT');
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
        cellStyle: {
            fontWeight: 'bold',
            color: '#2563eb'
        },
        valueFormatter: (params) => {
            return params.value ? `MT ${Number(params.value).toFixed(2)}` : 'MT 0.00';
        }
    },
    {
        field: "accoes",
        headerName: "Ações",
        width: 130,
        sortable: false,
        filter: false,
        cellRenderer: () => {
            return `
                <div class="flex gap-2 justify-center">
                    <button class="btn-visualizar px-2 py-1 bg-blue-500 text-white rounded text-xs hover:bg-blue-600 transition-colors">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-eliminar px-2 py-1 bg-red-500 text-white rounded text-xs hover:bg-red-600 transition-colors">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
        },
        onCellClicked: (params) => {
            const button = params.event.target.closest("button");
            if (!button) return;

            if (button.classList.contains("btn-visualizar")) {
                visualizarVenda(params.data);
            }

            if (button.classList.contains("btn-eliminar")) {
                eliminarVenda(params.data);
            }
        }
    }
];

// Funções
function onGridReady(params) {
    gridApi.value = params.api;
}

function visualizarVenda(dados) {
    FaturaVenda.value = dados??null;
    vendadetalhes.value = dados?.detalhes?.itens || [];
    mostrar.value = "detalhes";
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
            // Aqui você faria a chamada para eliminar
            // await axios.delete(`/vendas/${dados.id}`);

            Swal.fire(
                'Eliminado!',
                'A venda foi eliminada com sucesso.',
                'success'
            );
        } catch (error) {
            Swal.fire(
                'Erro!',
                'Ocorreu um erro ao eliminar a venda.',
                'error'
            );
        }
    }
}

// Watchers
watch([usuarioserach, DataVenda, viapagamentoserach], () => {
    // Não precisa fazer nada, os dados filtrados são computados automaticamente
}, { deep: true });

// Limpar filtros
function limparFiltros() {
    usuarioserach.value = "";
    DataVenda.value = "";
    viapagamentoserach.value = "";
    searchText.value = "";
}

// Mounted
onMounted(() => {
    // Inicializações adicionais se necessário
});

// Expor funções para uso no template
defineExpose({
    limparFiltros
});

const isLoading = ref(false);
const darkMode = ref(false);
function toggleDark() {
    darkMode.value = !darkMode.value;
    document.documentElement.classList.toggle('dark', darkMode.value);
    localStorage.setItem('darkMode', darkMode.value);
}
onMounted(() => {
    // Restaurar tema
    const savedTheme = localStorage.getItem('darkMode');
    if (savedTheme === 'true') {
        darkMode.value = true;
        document.documentElement.classList.add('dark');
    }


});
</script>

<style scoped>
/* Estilos personalizados se necessário */
.btn-eliminar:hover {
    transform: scale(1.05);
}

.btn-visualizar:hover {
    transform: scale(1.05);
}

/* Estilo para o select com seta personalizada */
select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    padding-right: 2.5rem;
}

/* Animações */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}



/* Animações suaves */
* {
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
}

/* Scrollbar personalizada */
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

/* Animação de gradiente */
.bg-gradient-to-br {
    background-size: 200% 200%;
    animation: gradientShift 3s ease-in-out infinite;
}

@keyframes gradientShift {
    0%, 100% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
}

/* Efeito de shimmer para loading */
@keyframes shimmer {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}

/* Hover effects */
.hover\:shadow-xl:hover {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Transições de tema */
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
