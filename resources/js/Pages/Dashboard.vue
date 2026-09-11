<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue';
import Swal from 'sweetalert2';
import VueApexCharts from 'vue3-apexcharts';

// ======================================================
// PROPS
// ======================================================

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

// ======================================================
// ESTADOS
// ======================================================

const isLoading = ref(false);
const darkMode = ref(false);
const periodoSelecionado = ref('mes');
const dataAtualizacao = ref(new Date());
let intervaloAtualizacao = null;

// ======================================================
// DADOS DO DASHBOARD
// ======================================================

const dashboard = reactive({
    // ================= KPIs =================
    kpis: {
        vendasHoje: {
            titulo: 'Vendas Hoje',
            valor: 52500,
            crescimento: 12.5,
            icon: 'fa-cart-shopping',
            cor: 'from-blue-500 to-blue-600'
        },
        vendasMes: {
            titulo: 'Vendas do Mês',
            valor: 1850000,
            crescimento: 18.4,
            icon: 'fa-chart-line',
            cor: 'from-green-500 to-green-600'
        },
        lucro: {
            titulo: 'Lucro Total',
            valor: 650000,
            crescimento: 15.2,
            icon: 'fa-money-bill-trend-up',
            cor: 'from-emerald-500 to-emerald-600'
        },
        iva: {
            titulo: 'IVA Recolhido',
            valor: 148000,
            crescimento: 8.7,
            icon: 'fa-file-invoice',
            cor: 'from-purple-500 to-purple-600'
        },
        caixa: {
            titulo: 'Saldo em Caixa',
            valor: 320000,
            crescimento: 5.5,
            icon: 'fa-cash-register',
            cor: 'from-yellow-500 to-yellow-600'
        },
        produtos: {
            titulo: 'Produtos em Stock',
            valor: 856,
            crescimento: -3.2,
            icon: 'fa-boxes-stacked',
            cor: 'from-red-500 to-red-600'
        }
    },

    // ================= VENDAS =================
    vendasMensais: [
        120000, 180000, 250000, 300000,
        450000, 500000, 620000, 580000,
        700000, 850000, 900000, 1050000
    ],

    vendasSemana: [
        35000, 42000, 38000, 65000,
        85000, 95000, 70000
    ],

    categorias: [
        { nome: 'Alimentos', valor: 45, cor: '#3B82F6' },
        { nome: 'Bebidas', valor: 20, cor: '#10B981' },
        { nome: 'Eletrónicos', valor: 25, cor: '#F59E0B' },
        { nome: 'Outros', valor: 10, cor: '#EF4444' }
    ],

    pagamentos: [
        { nome: 'Dinheiro', valor: 40, cor: '#8B5CF6' },
        { nome: 'M-Pesa', valor: 35, cor: '#EC4899' },
        { nome: 'Cartão', valor: 15, cor: '#06B6D4' },
        { nome: 'Transferência', valor: 10, cor: '#F97316' }
    ],

    // ================= TABELAS =================
    ultimasVendas: [
        { fatura: 'FT000001', cliente: 'João Ernesto', valor: 4500, estado: 'Pago', data: '2024-01-15 14:30' },
        { fatura: 'FT000002', cliente: 'Maria Santos', valor: 12500, estado: 'Pago', data: '2024-01-15 13:45' },
        { fatura: 'FT000003', cliente: 'Empresa Alfa', valor: 85000, estado: 'Pendente', data: '2024-01-15 12:20' },
        { fatura: 'FT000004', cliente: 'Carlos Lima', valor: 32000, estado: 'Pago', data: '2024-01-15 11:10' },
        { fatura: 'FT000005', cliente: 'Beatriz Oliveira', valor: 15000, estado: 'Pendente', data: '2024-01-15 10:05' }
    ],

    produtosBaixoStock: [
        { produto: 'Açúcar 1Kg', quantidade: 5, minimo: 10 },
        { produto: 'Óleo Vegetal', quantidade: 3, minimo: 8 },
        { produto: 'Leite', quantidade: 2, minimo: 15 },
        { produto: 'Arroz 5Kg', quantidade: 4, minimo: 12 },
        { produto: 'Farinha Trigo', quantidade: 1, minimo: 6 }
    ],

    clientesTop: [
        { nome: 'Empresa Alfa', compras: 35, total: 450000 },
        { nome: 'Escola São João', compras: 20, total: 280000 },
        { nome: 'Maria Santos', compras: 15, total: 150000 },
        { nome: 'João Ernesto', compras: 12, total: 98000 },
        { nome: 'Carlos Lima', compras: 10, total: 75000 }
    ]
});

// ======================================================
// COMPUTED
// ======================================================

const totalVendas = computed(() => {
    return dashboard.kpis.vendasMes.valor;
});

const totalLucro = computed(() => {
    return dashboard.kpis.lucro.valor;
});

const crescimentoMedio = computed(() => {
    const valores = Object.values(dashboard.kpis);
    const soma = valores.reduce((acc, kpi) => acc + kpi.crescimento, 0);
    return (soma / valores.length).toFixed(1);
});

// ======================================================
// CONFIGURAÇÕES APEXCHARTS
// ======================================================

const chartTheme = computed(() => ({
    mode: darkMode.value ? 'dark' : 'light',
    palette: 'palette1'
}));

// Gráfico de Vendas Mensais
const vendasChartOptions = computed(() => ({
    chart: {
        type: 'line',
        toolbar: { show: false },
        animations: { enabled: true, speed: 800 },
        background: 'transparent',
        fontFamily: 'Inter, sans-serif'
    },
    stroke: {
        curve: 'smooth',
        width: 3,
        colors: ['#3B82F6']
    },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.7,
            opacityTo: 0.2,
            stops: [0, 90, 100]
        }
    },
    xaxis: {
        categories: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
        labels: { style: { colors: darkMode.value ? '#9CA3AF' : '#6B7280' } }
    },
    yaxis: {
        labels: {
            formatter: (value) => moeda(value),
            style: { colors: darkMode.value ? '#9CA3AF' : '#6B7280' }
        }
    },
    tooltip: {
        y: { formatter: (value) => moeda(value) },
        theme: darkMode.value ? 'dark' : 'light'
    },
    grid: {
        borderColor: darkMode.value ? '#374151' : '#E5E7EB'
    },
    legend: {
        labels: { colors: darkMode.value ? '#F3F4F6' : '#1F2937' }
    }
}));

const vendasSeries = computed(() => [
    {
        name: 'Vendas',
        data: dashboard.vendasMensais,
        color: '#3B82F6'
    }
]);

// Gráfico de Vendas Semanais
const semanaChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        toolbar: { show: false },
        animations: { enabled: true, speed: 800 },
        background: 'transparent',
        fontFamily: 'Inter, sans-serif'
    },
    plotOptions: {
        bar: {
            borderRadius: 8,
            distributed: true,
            columnWidth: '60%'
        }
    },
    colors: ['#3B82F6', '#60A5FA', '#93C5FD', '#3B82F6', '#60A5FA', '#93C5FD', '#3B82F6'],
    xaxis: {
        categories: ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
        labels: { style: { colors: darkMode.value ? '#9CA3AF' : '#6B7280' } }
    },
    yaxis: {
        labels: {
            formatter: (value) => moeda(value),
            style: { colors: darkMode.value ? '#9CA3AF' : '#6B7280' }
        }
    },
    tooltip: {
        y: { formatter: (value) => moeda(value) },
        theme: darkMode.value ? 'dark' : 'light'
    },
    grid: {
        borderColor: darkMode.value ? '#374151' : '#E5E7EB'
    }
}));

const semanaSeries = computed(() => [
    {
        name: 'Vendas',
        data: dashboard.vendasSemana
    }
]);

// Gráfico de Categorias
const categoriaChartOptions = computed(() => ({
    chart: {
        type: 'donut',
        animations: { enabled: true, speed: 800 },
        background: 'transparent',
        fontFamily: 'Inter, sans-serif'
    },
    labels: dashboard.categorias.map(item => item.nome),
    colors: dashboard.categorias.map(item => item.cor),
    legend: {
        position: 'bottom',
        labels: { colors: darkMode.value ? '#F3F4F6' : '#1F2937' }
    },
    tooltip: {
        y: { formatter: (value) => `${value}%` },
        theme: darkMode.value ? 'dark' : 'light'
    },
    plotOptions: {
        pie: {
            donut: {
                size: '65%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Total',
                        formatter: () => '100%',
                        color: darkMode.value ? '#F3F4F6' : '#1F2937'
                    }
                }
            }
        }
    }
}));

const categoriaSeries = computed(() =>
    dashboard.categorias.map(item => item.valor)
);

// Gráfico de Pagamentos
const pagamentoChartOptions = computed(() => ({
    chart: {
        type: 'pie',
        animations: { enabled: true, speed: 800 },
        background: 'transparent',
        fontFamily: 'Inter, sans-serif'
    },
    labels: dashboard.pagamentos.map(item => item.nome),
    colors: dashboard.pagamentos.map(item => item.cor),
    legend: {
        position: 'bottom',
        labels: { colors: darkMode.value ? '#F3F4F6' : '#1F2937' }
    },
    tooltip: {
        y: { formatter: (value) => `${value}%` },
        theme: darkMode.value ? 'dark' : 'light'
    },
    dataLabels: {
        enabled: true,
        formatter: (value) => `${value}%`
    }
}));

const pagamentoSeries = computed(() =>
    dashboard.pagamentos.map(item => item.valor)
);

// ======================================================
// FUNÇÕES
// ======================================================

function moeda(valor) {
    return new Intl.NumberFormat('pt-MZ', {
        style: 'currency',
        currency: 'MZN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(valor);
}

function toggleDark() {
    darkMode.value = !darkMode.value;
    document.documentElement.classList.toggle('dark', darkMode.value);
    localStorage.setItem('darkMode', darkMode.value);
}

function getStatusClass(estado) {
    const classes = {
        'Pago': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'Pendente': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        'Cancelado': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
    };
    return classes[estado] || classes['Pendente'];
}

function getStockAlert(quantidade, minimo) {
    if (quantidade <= minimo * 0.3) return 'critico';
    if (quantidade <= minimo * 0.6) return 'baixo';
    return 'normal';
}

function getStockColor(quantidade, minimo) {
    const alerta = getStockAlert(quantidade, minimo);
    const cores = {
        'critico': 'text-red-600 dark:text-red-400',
        'baixo': 'text-yellow-600 dark:text-yellow-400',
        'normal': 'text-green-600 dark:text-green-400'
    };
    return cores[alerta];
}

async function carregarDados() {
    isLoading.value = true;

    try {
        // Simular chamada API
        await new Promise(resolve => setTimeout(resolve, 800));

        // Atualizar dados com valores aleatórios para simular tempo real
        const variacao = (Math.random() - 0.5) * 1000;
        dashboard.kpis.vendasHoje.valor += variacao;
        dashboard.kpis.vendasHoje.crescimento += (Math.random() - 0.5) * 2;

        dataAtualizacao.value = new Date();

        // Notificação de atualização
        Swal.fire({
            icon: 'success',
            title: 'Dados Atualizados!',
            text: 'Os dados do dashboard foram atualizados com sucesso.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });

    } catch (error) {
        console.error('Erro ao carregar dados:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: 'Falha ao carregar os dados do dashboard.'
        });
    } finally {
        isLoading.value = false;
    }
}

// ======================================================
// ATUALIZAÇÃO EM TEMPO REAL
// ======================================================

function iniciarAtualizacaoTempoReal() {
    intervaloAtualizacao = setInterval(() => {
        // Atualizar apenas se não estiver carregando
        if (!isLoading.value) {
            const variacao = (Math.random() - 0.5) * 500;
            dashboard.kpis.vendasHoje.valor += variacao;

            // Adicionar nova venda simulada
            const clientes = ['João Ernesto', 'Maria Santos', 'Carlos Lima', 'Beatriz Oliveira', 'Pedro Silva'];
            const valores = [2500, 3800, 4200, 5600, 7100];

            dashboard.ultimasVendas.unshift({
                fatura: `FT${String(Date.now()).slice(-6)}`,
                cliente: clientes[Math.floor(Math.random() * clientes.length)],
                valor: valores[Math.floor(Math.random() * valores.length)],
                estado: Math.random() > 0.3 ? 'Pago' : 'Pendente',
                data: new Date().toLocaleString('pt-MZ')
            });

            if (dashboard.ultimasVendas.length > 10) {
                dashboard.ultimasVendas.pop();
            }
        }
    }, 30000); // A cada 30 segundos
}

// ======================================================
// LIFECYCLE
// ======================================================

onMounted(() => {
    // Restaurar tema
    const savedTheme = localStorage.getItem('darkMode');
    if (savedTheme === 'true') {
        darkMode.value = true;
        document.documentElement.classList.add('dark');
    }

    carregarDados();
    iniciarAtualizacaoTempoReal();
});

onUnmounted(() => {
    if (intervaloAtualizacao) {
        clearInterval(intervaloAtualizacao);
    }
});

// ======================================================
// WATCHERS
// ======================================================

watch(darkMode, (novo) => {
    // Os gráficos serão atualizados automaticamente via computed
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        📊 Dashboard de Vendas
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                            {{ dataAtualizacao.toLocaleTimeString('pt-MZ') }}
                        </span>
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Resumo comercial do sistema • Crescimento médio: {{ crescimentoMedio }}%
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

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- LOADING -->
                <div
                    v-if="isLoading"
                    class="h-96 flex flex-col justify-center items-center gap-2"
                >
                    <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-600 border-t-transparent"></div>
                    <p class="text-gray-500 dark:text-gray-400">Carregando dados...</p>
                </div>

                <div v-else class="space-y-6">
                    <!-- ================= KPI CARDS ================= -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-2">
                        <div
                            v-for="(kpi, key) in dashboard.kpis"
                            :key="key"
                            class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 p-5"
                        >
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">
                                        {{ kpi.titulo }}
                                    </p>
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mt-2">
                                        {{ moeda(kpi.valor) }}
                                    </h3>
                                    <p
                                        :class="kpi.crescimento >= 0 ? 'text-green-500' : 'text-red-500'"
                                        class="text-sm font-semibold mt-2"
                                    >
                                        <i
                                            :class="kpi.crescimento >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"
                                            class="mr-1"
                                        ></i>
                                        {{ Math.abs(kpi.crescimento) }}%
                                        <span class="text-gray-400 font-normal">vs mês anterior</span>
                                    </p>
                                </div>

                                <div
                                    :class="[
                                        'w-12 h-12 rounded-lg bg-gradient-to-br flex items-center justify-center text-white text-xl flex-shrink-0',
                                        kpi.cor
                                    ]"
                                >
                                    <i :class="'fas ' + kpi.icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================= GRÁFICOS LINHA / BARRA ================= -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- VENDAS MENSAIS -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-gray-900 dark:text-white">
                                    📈 Evolução das Vendas
                                </h3>
                                <select
                                    v-model="periodoSelecionado"
                                    class="text-sm border rounded-lg px-3 py-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                >
                                    <option value="mes">Último mês</option>
                                    <option value="trimestre">Último trimestre</option>
                                    <option value="ano">Último ano</option>
                                </select>
                            </div>
                            <VueApexCharts
                                height="350"
                                type="line"
                                :options="vendasChartOptions"
                                :series="vendasSeries"
                            />
                        </div>

                        <!-- VENDAS SEMANA -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-4">
                                📊 Vendas da Semana
                            </h3>
                            <VueApexCharts
                                height="350"
                                type="bar"
                                :options="semanaChartOptions"
                                :series="semanaSeries"
                            />
                        </div>
                    </div>

                    <!-- ================= DONUT + PIE ================= -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- CATEGORIAS -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-4">
                                🥧 Vendas por Categoria
                            </h3>
                            <VueApexCharts
                                height="320"
                                type="donut"
                                :options="categoriaChartOptions"
                                :series="categoriaSeries"
                            />
                        </div>

                        <!-- PAGAMENTOS -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-4">
                                💳 Formas de Pagamento
                            </h3>
                            <VueApexCharts
                                height="320"
                                type="pie"
                                :options="pagamentoChartOptions"
                                :series="pagamentoSeries"
                            />
                        </div>
                    </div>

                    <!-- ================= ÚLTIMAS VENDAS ================= -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 overflow-hidden">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-bold text-gray-900 dark:text-white">
                                🧾 Últimas Vendas
                            </h3>
                            <span class="text-sm text-gray-500">
                                Total: {{ dashboard.ultimasVendas.length }} vendas
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-sm text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                                        <th class="pb-3">Fatura</th>
                                        <th class="pb-3">Cliente</th>
                                        <th class="pb-3">Data</th>
                                        <th class="pb-3 text-right">Valor</th>
                                        <th class="pb-3 text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="venda in dashboard.ultimasVendas.slice(0, 5)"
                                        :key="venda.fatura"
                                        class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                    >
                                        <td class="py-3 font-medium text-gray-900 dark:text-white">
                                            {{ venda.fatura }}
                                        </td>
                                        <td class="py-3 text-gray-700 dark:text-gray-300">
                                            {{ venda.cliente }}
                                        </td>
                                        <td class="py-3 text-gray-500 dark:text-gray-400 text-sm">
                                            {{ venda.data }}
                                        </td>
                                        <td class="py-3 text-right font-bold text-gray-900 dark:text-white">
                                            {{ moeda(venda.valor) }}
                                        </td>
                                        <td class="py-3 text-center">
                                            <span
                                                :class="getStatusClass(venda.estado)"
                                                class="px-3 py-1 rounded-full text-xs font-medium"
                                            >
                                                {{ venda.estado }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ================= ESTOQUE + CLIENTES ================= -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- ESTOQUE -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-4">
                                ⚠️ Produtos com Baixo Estoque
                            </h3>
                            <div class="space-y-3">
                                <div
                                    v-for="produto in dashboard.produtosBaixoStock"
                                    :key="produto.produto"
                                    class="flex justify-between items-center p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                >
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ produto.produto }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            Mínimo: {{ produto.minimo }} unidades
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p
                                            :class="getStockColor(produto.quantidade, produto.minimo)"
                                            class="text-lg font-bold"
                                        >
                                            {{ produto.quantidade }}
                                        </p>
                                        <span
                                            :class="getStockAlert(produto.quantidade, produto.minimo) === 'critico' ? 'bg-red-500' : 'bg-yellow-500'"
                                            class="px-2 py-1 text-xs text-white rounded-full"
                                        >
                                            {{ getStockAlert(produto.quantidade, produto.minimo).toUpperCase() }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TOP CLIENTES -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-4">
                                🏆 Melhores Clientes
                            </h3>
                            <div class="space-y-3">
                                <div
                                    v-for="(cliente, index) in dashboard.clientesTop.slice(0, 5)"
                                    :key="cliente.nome"
                                    class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            :class="[
                                                'w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold',
                                                index === 0 ? 'bg-gradient-to-br from-yellow-400 to-yellow-600' :
                                                index === 1 ? 'bg-gradient-to-br from-gray-400 to-gray-600' :
                                                index === 2 ? 'bg-gradient-to-br from-amber-600 to-amber-800' :
                                                'bg-gradient-to-br from-blue-500 to-blue-700'
                                            ]"
                                        >
                                            {{ index + 1 }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">
                                                {{ cliente.nome }}
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                {{ cliente.compras }} compras
                                            </p>
                                        </div>
                                    </div>
                                    <span class="font-bold text-gray-900 dark:text-white">
                                        {{ moeda(cliente.total) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================= FOOTER ================= -->
                    <div class="text-center text-sm text-gray-500 dark:text-gray-400 py-4 border-t dark:border-gray-700">
                        <p>
                            <i class="fas fa-sync-alt text-blue-500 animate-spin" style="animation-duration: 3s;"></i>
                            Dados atualizados em tempo real • Última atualização: {{ dataAtualizacao.toLocaleString('pt-MZ') }}
                        </p>
                        <p class="mt-1">
                            Sistema de Vendas v2.0 • Desenvolvido com ❤️
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
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
