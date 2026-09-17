<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted, reactive } from "vue";
import vendasDetalhesShow from './vendasDetalhesShow.vue';
import PrintReportButton from '@/Components/PrintReportButton.vue';
import Swal from 'sweetalert2';
import { debounce } from 'lodash';

// ==================== PROPS ====================
const props = defineProps({
    usuario: {
        type: Array,
        default: () => []
    },
    viasdepagamento: {
        type: Array,
        default: () => []
    }
});

// ==================== STATE ====================
const isLoading = ref(false);
const dataInicial = ref(null);
const dataFinal = ref(null);
const usuarioSelecionado = ref(null);
const viapagamentoselcionada = ref(null);
const dadosvendasfeitas = ref([]);

const totalResumo = reactive({
    totalItems: 0,
    totalVenda: 0,
    totalIva: 0,
    totalCompra: 0,
    totalLucro: 0
});

// ==================== COMPUTED ====================
const totalVendas = computed(() => dadosvendasfeitas.value.length);

const hasData = computed(() => dadosvendasfeitas.value.length > 0);

const darkMode = ref(false);
const filtrosAtivos = computed(() => ({
    usuario: usuarioSelecionado.value !== null,
    viaPagamento: viapagamentoselcionada.value !== null,
    dataInicial: dataInicial.value !== null,
    dataFinal: dataFinal.value !== null
}));

// ==================== FUNÇÕES ====================
function getDataHoje() {
    const hoje = new Date();
    const ano = hoje.getFullYear();
    const mes = String(hoje.getMonth() + 1).padStart(2, '0');
    const dia = String(hoje.getDate()).padStart(2, '0');
    return `${ano}-${mes}-${dia}`;
}

function formatarMoeda(valor) {
    return new Intl.NumberFormat('pt-MZ', {
        style: 'currency',
        currency: 'MZN'
    }).format(valor);
}

function calcularTotais(dados) {
    const totais = dados.reduce((acc, item) => {
        // Safe parsing of numeric values
        const totalVenda = Number(item.Total) || 0;
        const itemsCount = Number(item.Items) || 0;
    const iva = Number(item.totaliva) || 0;
        const totalCompra = Number(item.totalcompra) || 0;

        return {
            totalItems: acc.totalItems + itemsCount,
            totalVenda: acc.totalVenda + totalVenda,
            totalIva: acc.totalIva + iva,
            totalCompra: acc.totalCompra + totalCompra,
            totalLucro: acc.totalLucro + (totalVenda - totalCompra)
        };
    }, {
        totalItems: 0,
        totalVenda: 0,
        totalIva: 0,
        totalCompra: 0,
        totalLucro: 0
    });

    Object.assign(totalResumo, totais);
}

async function buscardadosrelatorio() {
    // Validação das datas
    if (!dataInicial.value || !dataFinal.value) {
        dadosvendasfeitas.value = [];
        calcularTotais([]);
        return;
    }

    // Validação de intervalo de datas
    if (new Date(dataInicial.value) > new Date(dataFinal.value)) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção',
            text: 'A data inicial não pode ser maior que a data final.'
        });
        return;
    }

    isLoading.value = true;

    try {
        const response = await axios.get(`/vendas/relatorio/dados/${dataInicial.value}/${dataFinal.value}`);

        if (!response.data || !Array.isArray(response.data)) {
            throw new Error('Dados inválidos recebidos');
        }

        let dadosFiltrados = response.data;

        // Aplicar filtros
        if (usuarioSelecionado.value) {
            dadosFiltrados = dadosFiltrados.filter(item =>
                item.usuario === usuarioSelecionado.value
            );
        }

        if (viapagamentoselcionada.value) {
            dadosFiltrados = dadosFiltrados.filter(item =>
                item.Via_pagamento === viapagamentoselcionada.value
            );
        }

        dadosvendasfeitas.value = dadosFiltrados;
        calcularTotais(dadosFiltrados);

        if (dadosFiltrados.length === 0) {
            // Opcional: mostrar mensagem de nenhum resultado
            console.log('Nenhuma venda encontrada para os filtros selecionados.');
        }

    } catch (error) {
        console.error('Erro ao buscar dados:', error);
        dadosvendasfeitas.value = [];
        calcularTotais([]);

        Swal.fire({
            icon: 'error',
            title: 'Erro ao carregar dados',
            text: error.response?.data?.message || 'Falha ao carregar os dados. Tente novamente.',
            confirmButtonColor: '#3085d6'
        });
    } finally {
        isLoading.value = false;
    }
}

function limparFiltros() {

    usuarioSelecionado.value = null;
    viapagamentoselcionada.value = null;
    const hoje = getDataHoje();
    dataInicial.value = hoje;
    dataFinal.value = hoje;
}

// ==================== DEBOUNCED WATCH ====================
const debouncedBuscar = debounce(buscardadosrelatorio, 500);

watch(
    [usuarioSelecionado, viapagamentoselcionada, dataInicial, dataFinal],
    (newValues, oldValues) => {
        // Verificar se houve mudança real
        const changed = newValues.some((val, index) => val !== oldValues?.[index]);
        if (changed) {
            debouncedBuscar();
        }
    },
    { deep: true }
);

// ==================== LIFECYCLE ====================
onMounted(() => {
    const hoje = getDataHoje();
    dataInicial.value = hoje;
    dataFinal.value = hoje;

    // Restaurar tema
    const savedTheme = localStorage.getItem('darkMode');
    if (savedTheme === 'true') {
        darkMode.value = true;
        document.documentElement.classList.add('dark');
    }
// carregarDados()

});

// ==================== EXPOSE ====================
defineExpose({
    limparFiltros,
    buscardadosrelatorio
});

async function carregarDados() {
   // alert();
    isLoading.value = true;


    try{
      limparFiltros()
       debouncedBuscar()
    } catch(erroe){

    } finally{
 isLoading.value = false;
    }


}
// Função para imprimir em A5
function imprimirA5() {
  const printContent = document.querySelector('.tablePane');
  if (!printContent) {
        Swal.fire({
            icon: 'error',
            title: 'Impressão indisponível',
            text: 'Conteúdo não encontrado para impressão.'
        });
    return;
  }

  // Clone do conteúdo
  const contentClone = printContent.cloneNode(true);

  // Remove os botões do clone
  const buttonsContainer = contentClone.querySelector('.no-print');
  if (buttonsContainer) {
    buttonsContainer.remove();
  }

  // Remove o loading state
  const loadingDiv = contentClone.querySelector('.text-center.py-8');
  if (loadingDiv) {
    loadingDiv.remove();
  }

  const contentHTML = contentClone.innerHTML;

  // Criar janela de impressão A5
  const printWindow = window.open('', '_blank', 'width=420,height=595');
  if (!printWindow) {
        Swal.fire({
            icon: 'warning',
            title: 'Pop-up bloqueado',
            text: 'Permita pop-ups no navegador para imprimir.'
        });
    return;
  }

  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>Fatura </title>
      <style>
        @page {
          size: A4 portrait;
          margin: 10mm 8mm;
        }

        * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
        }

        body {
          font-family: 'Segoe UI', Arial, sans-serif;
          font-size: 10px;
          line-height: 1.4;
          background: white;
          color: #333;
          padding: 0;
        }

        .print-container {
          max-width: 100%;
          padding: 2px;
        }

        .print-header {
          text-align: center;
          margin-bottom: 8px;
          padding-bottom: 5px;
          border-bottom: 2px solid #007bff;
        }

        .print-header h2 {
          font-size: 14px;
          color: #007bff;
          margin: 0;
        }

        .print-header p {
          font-size: 9px;
          color: #666;
          margin-top: 2px;
        }

        .info-grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 3px 8px;
          background: #f8f9fa;
          padding: 5px 8px;
          border-radius: 4px;
          margin-bottom: 8px;
          font-size: 9px;
        }

        .info-item {
          display: flex;
          align-items: center;
        }

        .info-item b {
          color: #495057;
          margin-right: 3px;
          font-size: 9px;
        }

        .info-item small {
          font-size: 8px;
          color: #6c757d;
        }

        table {
          width: 100%;
          border-collapse: collapse;
          margin: 5px 0;
          font-size: 9px;
        }

        thead th {
          background: #007bff;
          color: white;
          padding: 4px 6px;
          text-align: left;
          font-weight: 600;
          font-size: 8px;
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }

        thead th:not(:first-child) {
          text-align: right;
        }

        tbody td {
          padding: 4px 6px;
          border-bottom: 1px solid #dee2e6;
          vertical-align: top;
        }

        tbody td:not(:first-child) {
          text-align: right;
        }

        tbody tr:last-child td {
          border-bottom: none;
        }

        tfoot td {
          padding: 5px 6px;
          border-top: 2px solid #007bff;
          font-weight: bold;
          font-size: 10px;
          background: #f8f9fa;
        }

        tfoot td:last-child {
          color: #007bff;
          font-size: 11px;
        }

        .total-row td {
          background: #e9ecef;
        }

        .produto-nome {
          font-weight: 600;
          font-size: 8px;
        }

        .produto-detalhe {
          font-size: 7px;
          color: #6c757d;
          display: block;
        }

        .no-items {
          text-align: center;
          padding: 15px 0;
          color: #6c757d;
          font-size: 9px;
        }

        .footer {
          text-align: center;
          margin-top: 8px;
          padding-top: 5px;
          border-top: 1px solid #dee2e6;
          font-size: 7px;
          color: #6c757d;
        }

        .badge {
          display: inline-block;
          padding: 1px 6px;
          background: #28a745;
          color: white;
          border-radius: 10px;
          font-size: 7px;
        }

        @media print {
          body {
            margin: 0;
            padding: 0;
          }
          .no-print {
            display: none !important;
          }
          .print-container {
            padding: 0;
          }
        }
      </style>
    </head>
    <body>
      <div class="print-container">
        <div class="print-header">
          <h2>FATURA #</h2>
          <p>${new Date().toLocaleDateString('pt-PT', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
          })}</p>
        </div>



        ${contentHTML.includes('table') ? contentHTML : `
          <div class="no-items">
            <p>Nenhum item encontrado nesta venda</p>
          </div>
        `}

        <div class="footer">
          <span>Documento emitido por sistema • ${new Date().toLocaleDateString('pt-PT')}</span>
          <br>
          <span style="font-size: 6px; color: #adb5bd;">Obrigado pela preferência!</span>
        </div>
      </div>

      <script>
        // Auto-print após carregamento
        window.onload = function() {
          setTimeout(function() {
            window.print();
            window.close();
          }, 300);
        };
      <\/script>
    </body>
    </html>
  `);

  printWindow.document.close();
}

function toggleDark() {
    darkMode.value = !darkMode.value;
    document.documentElement.classList.toggle('dark', darkMode.value);
    localStorage.setItem('darkMode', darkMode.value);
}

</script>

<template>
    <Head title="Relatório de Vendas" />

        <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
                <div>
                    <h2 class="flex items-center gap-2 text-2xl font-bold text-gray-800 dark:text-white">
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

                <div class="flex flex-wrap gap-3">
                    <button
                        @click="carregarDados"
                        class="flex items-center gap-2 px-4 py-2 text-white transition-all duration-200 bg-blue-600 rounded-lg hover:bg-blue-700"
                        :disabled="isLoading"
                    >
                        <i class="fas fa-sync-alt" :class="{'animate-spin': isLoading}"></i>
                        Atualizar
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12" >
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Filtros -->
                <div class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                            <!-- Funcionário -->
                            <div class="md:col-span-3">
                                <label class="block mb-1 text-sm font-medium text-gray-700">
                                    Funcionário
                                </label>
                                <select
                                    v-model="usuarioSelecionado"
                                    class="w-full border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option :value="null">Todos os Funcionários</option>
                                    <option
                                        v-for="user in usuario"
                                        :key="user.id"
                                        :value="user.name"
                                    >
                                        {{ user.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Via de Pagamento -->
                            <div class="md:col-span-3">
                                <label class="block mb-1 text-sm font-medium text-gray-700">
                                    Via de Pagamento
                                </label>
                                <select
                                    v-model="viapagamentoselcionada"
                                    class="w-full border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option :value="null">Todas as Vias</option>
                                    <option
                                        v-for="via in viasdepagamento"
                                        :key="via.id"
                                        :value="via.nome"
                                    >
                                        {{ via.nome }}
                                    </option>
                                </select>
                            </div>

                            <!-- Datas -->
                            <div class="md:col-span-4">
                                <label class="block mb-1 text-sm font-medium text-gray-700">
                                    Período
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <input
                                        type="date"
                                        v-model="dataInicial"
                                        class="border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500"
                                    />
                                    <input
                                        type="date"
                                        v-model="dataFinal"
                                        class="border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>
                            </div>

                            <!-- Ações -->
                            <!-- <div class="flex items-end md:col-span-2">
                                <button
                                    @click="limparFiltros"
                                    class="w-full px-4 py-2 font-medium text-gray-700 transition duration-200 bg-gray-100 rounded-lg hover:bg-gray-200"
                                >
                                    <i class="fas fa-undo"></i> Limpar Filtros
                                </button>
                            </div> -->
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div v-if="isLoading" class="p-12 bg-white rounded-lg shadow-sm">
                    <div class="flex flex-col items-center justify-center">
                        <div class="w-12 h-12 mb-4 border-b-2 border-blue-500 rounded-full animate-spin"></div>
                        <p class="text-gray-600">Carregando dados...</p>
                    </div>
                </div>

                <!-- Sem Dados -->
                <div v-else-if="!hasData" class="p-12 bg-white rounded-lg shadow-sm">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma venda encontrada</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Tente ajustar os filtros ou selecionar outro período.
                        </p>
                    </div>
                </div>

                <!-- Dados -->
                <div v-else class="tablePane">
                    <!-- Resumo -->
                    <div class="p-4 mb-6 text-white rounded-lg shadow-sm bg-gradient-to-r from-blue-900 to-blue-800 ">
                        <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
                            <div class="text-center">
                                <div class="text-sm opacity-75">Total de Vendas</div>
                                <div class="text-xl font-bold">{{ totalVendas }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm opacity-75">Itens Vendidos</div>
                                <div class="text-xl font-bold">{{ totalResumo.totalItems }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm opacity-75">Total em Vendas</div>
                                <div class="text-xl font-bold">{{ formatarMoeda(totalResumo.totalVenda) }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm opacity-75">Total em IVA</div>
                                <div class="text-xl font-bold">{{ formatarMoeda(totalResumo.totalIva) }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm opacity-75">Lucro Total</div>
                                <div class="text-xl font-bold text-green-400">{{ formatarMoeda(totalResumo.totalLucro) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Detalhes das Vendas -->
                    <div  v-for="(venda, index) in dadosvendasfeitas" class="p-6 bg-white shadow-lg dark:bg-gray-800 rounded-xl" :key="index">
                    <vendasDetalhesShow


                        :venda="venda?.detalhes?.itens || []"
                        :FaturaVenda="venda"
                        :flag="1"
                    />
                </div>
                </div>
            </div>


             <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2 no-print"  v-if="hasData" >
             <div></div>
        <div>

                    <PrintReportButton
                        selector=".tablePane"
                        title="Relatório de Vendas"
                        label="Imprimir"
                    />
        </div>


      </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Animações suaves */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Estilos personalizados para selects */
select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
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
