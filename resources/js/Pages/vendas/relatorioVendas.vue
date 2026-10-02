<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted, reactive } from "vue";
import vendasDetalhesShow from './vendasDetalhesShow.vue';
import PrintReportButton from '@/Components/PrintReportButton.vue';
import Swal from 'sweetalert2';
import { debounce } from 'lodash';
import { usePermission } from '@/composables/usePermission';

const { can } = usePermission();

// ==================== PROPS ====================
const props = defineProps({
    funcionarios: { type: Array, default: () => [] },
    viasdepagamento: { type: Array, default: () => [] },
    empresa: { type: Object, default: () => ({}) },
    lojas: { type: Array, default: () => [] }
});

// ==================== STATE ====================
const page = props.empresa;
const isLoading = ref(false);
const dataInicial = ref(null);
const dataFinal = ref(null);
const lojaid = ref(null);
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

const darkMode = ref(false);

// ==================== COMPUTED ====================
const totalVendas = computed(() => dadosvendasfeitas.value.length);
const hasData = computed(() => dadosvendasfeitas.value.length > 0);

const filtrosAtivos = computed(() => ({
    usuario: usuarioSelecionado.value !== null,
    viaPagamento: viapagamentoselcionada.value !== null,
    dataInicial: dataInicial.value !== null,
    dataFinal: dataFinal.value !== null
}));

// ==================== FUNÇÕES AUXILIARES ====================
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
    }).format(valor || 0);
}

function calcularTotais(dados) {
    const totais = dados.reduce((acc, item) => {
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
    }, { totalItems: 0, totalVenda: 0, totalIva: 0, totalCompra: 0, totalLucro: 0 });

    Object.assign(totalResumo, totais);
}

// ==================== BUSCAR DADOS ====================
async function buscardadosrelatorio() {
    if (!dataInicial.value || !dataFinal.value) {
        dadosvendasfeitas.value = [];
        calcularTotais([]);
        return;
    }

    if (!lojaid.value) {
        Swal.fire({
            icon: 'info',
            title: 'Selecione uma loja',
            text: 'É necessário selecionar uma loja para gerar o relatório.'
        });
        return;
    }

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
        const response = await axios.get(
            `/vendas/relatorio/dados/${lojaid.value}/${dataInicial.value}/${dataFinal.value}`
        );

        if (!response.data || !Array.isArray(response.data)) {
            throw new Error('Dados inválidos recebidos');
        }

        let dadosFiltrados = response.data;

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
    [usuarioSelecionado, viapagamentoselcionada, dataInicial, dataFinal, lojaid],
    (newValues, oldValues) => {
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

    // Pré-seleciona a primeira loja se existir
    if (props.lojas && props.lojas.length > 0 && !lojaid.value) {
        lojaid.value = props.lojas[0].id;
    }

    const savedTheme = localStorage.getItem('darkMode');
    if (savedTheme === 'true') {
        darkMode.value = true;
        document.documentElement.classList.add('dark');
    }
});

// ==================== EXPOSE ====================
defineExpose({
    limparFiltros,
    buscardadosrelatorio
});

async function carregarDados() {
    isLoading.value = true;
    try {
        await buscardadosrelatorio();
    } catch (e) {
        console.error(e);
    } finally {
        isLoading.value = false;
    }
}

// ==================== IMPRESSÃO ====================
function imprimirA5() {
    const printContent = document.querySelector('.tablePane');
    if (!printContent) {
        Swal.fire({
            icon: 'error',
            title: 'Impressão indisponível',
            text: 'Conteúdo não encontrado para impressão.',
            confirmButtonColor: '#6366f1'
        });
        return;
    }

    const contentClone = printContent.cloneNode(true);
    contentClone.querySelectorAll('.no-print').forEach(el => el.remove());
    contentClone.querySelectorAll('.text-center.py-8, .loading, .spinner').forEach(el => el.remove());
    contentClone.querySelectorAll('input, button, select, textarea').forEach(el => el.remove());
    contentClone.querySelectorAll('[style*="overflow"]').forEach(el => el.style.overflow = 'visible');

    const contentHTML = contentClone.innerHTML;

    // ─── DADOS DA EMPRESA ───
    const empresa = page?.props?.empresa || page?.empresa || page || {};
    const logoUrl = empresa.logo ? `/storage/${empresa.logo}` : null;
    const nomeEmpresa = empresa.nome_fantasia || empresa.nome || 'Empresa';
    const nuitEmpresa = empresa.nuit || '';
    const enderecoEmpresa = empresa.endereco || '';
    const telefoneEmpresa = empresa.telefone || '';
    const emailEmpresa = empresa.email || '';

    const dataEmissao = new Date().toLocaleDateString('pt-PT', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
    const dataCurta = new Date().toLocaleDateString('pt-PT');

    const periodoTexto = (dataInicial.value && dataFinal.value)
        ? `${new Date(dataInicial.value).toLocaleDateString('pt-PT')} → ${new Date(dataFinal.value).toLocaleDateString('pt-PT')}`
        : 'Período não definido';

    // ─── LOJA SELECIONADA ───
    const lojaSelecionada = props.lojas?.find(l => l.id === lojaid.value);
    const nomeLoja = lojaSelecionada?.Desc || 'Todas as lojas';

    // ─── NOME DO FUNCIONÁRIO ───
    const nomeFuncionario = usuarioSelecionado.value || 'Todos os funcionários';
    const nomeVia = viapagamentoselcionada.value || 'Todas as vias';

    const printWindow = window.open('', '_blank', 'width=900,height=700,scrollbars=yes,resizable=yes');
    if (!printWindow) {
        Swal.fire({
            icon: 'warning',
            title: 'Pop-up bloqueado',
            text: 'Permita pop-ups no navegador para imprimir.',
            confirmButtonColor: '#6366f1'
        });
        return;
    }

    printWindow.document.write(`
        <!DOCTYPE html>
        <html lang="pt">
        <head>
          <meta charset="UTF-8">
          <title>Relatório de Vendas — ${nomeEmpresa}</title>
          <link rel="preconnect" href="https://fonts.googleapis.com">
          <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
          <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
          <style>
            :root {
              --primary: #6366f1;
              --primary-dark: #4f46e5;
              --success: #10b981;
              --warning: #f59e0b;
              --danger: #ef4444;
              --info: #06b6d4;
              --purple: #8b5cf6;
              --slate-50: #f8fafc;
              --slate-100: #f1f5f9;
              --slate-200: #e2e8f0;
              --slate-300: #cbd5e1;
              --slate-400: #94a3b8;
              --slate-500: #64748b;
              --slate-600: #475569;
              --slate-700: #334155;
              --slate-800: #1e293b;
              --slate-900: #0f172a;
              --font-sans: 'Inter', -apple-system, sans-serif;
              --font-mono: 'JetBrains Mono', monospace;
            }
            * { margin: 0; padding: 0; box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            @page { size: A4 portrait; margin: 10mm; }
            body { font-family: var(--font-sans); font-size: 10px; line-height: 1.45; color: var(--slate-800); background: #fff; }
            .print-container { width: 100%; }

            .print-header { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding-bottom: 10px; margin-bottom: 12px; border-bottom: 3px solid var(--primary); position: relative; }
            .print-header::after { content: ''; position: absolute; bottom: -3px; left: 0; width: 90px; height: 3px; background: linear-gradient(90deg, var(--info), var(--purple)); }
            .header-brand { display: flex; align-items: center; gap: 12px; flex: 1; }
            .header-brand img { max-height: 55px; max-width: 110px; object-fit: contain; border-radius: 8px; }
            .brand-name { font-size: 14px; font-weight: 800; color: var(--slate-900); }
            .brand-detail { font-size: 8.5px; color: var(--slate-500); }
            .header-title { text-align: right; }
            .title-label { font-size: 8px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--slate-400); }
            .title-main { font-size: 20px; font-weight: 800; color: var(--primary); line-height: 1.1; }
            .title-sub { font-size: 8.5px; color: var(--slate-500); font-weight: 500; }

            .period-bar { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 6px 12px; background: linear-gradient(135deg, var(--slate-100), var(--slate-50)); border: 1px solid var(--slate-200); border-radius: 8px; margin-bottom: 12px; font-size: 9px; }
            .period-label { font-size: 7.5px; font-weight: 700; text-transform: uppercase; color: var(--slate-400); }
            .period-value { font-family: var(--font-mono); font-weight: 700; color: var(--slate-700); font-size: 9.5px; }
            .emit-info { font-size: 8px; color: var(--slate-500); }

            .summary-cards { display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; margin-bottom: 14px; }
            .summary-card { padding: 8px 6px; border-radius: 8px; text-align: center; color: #fff; }
            .summary-card.card-vendas { background: linear-gradient(135deg, #6366f1, #4f46e5); }
            .summary-card.card-itens  { background: linear-gradient(135deg, #06b6d4, #0891b2); }
            .summary-card.card-total  { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
            .summary-card.card-iva    { background: linear-gradient(135deg, #f59e0b, #d97706); }
            .summary-card.card-lucro  { background: linear-gradient(135deg, #10b981, #059669); }
            .card-label { font-size: 7px; font-weight: 700; text-transform: uppercase; opacity: 0.9; margin-bottom: 3px; }
            .card-value { font-family: var(--font-mono); font-size: 13px; font-weight: 800; }

            .vendas-section { margin-top: 6px; }
            .venda-item { margin-bottom: 14px; page-break-inside: avoid; border: 1px solid var(--slate-200); border-radius: 10px; overflow: hidden; background: #fff; }
            .venda-header { display: flex; align-items: center; justify-content: space-between; padding: 6px 12px; background: linear-gradient(135deg, var(--slate-800), var(--slate-900)); color: #fff; }

            .signature-block { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 30px; padding-top: 15px; }
            .signature-line { border-top: 1px solid var(--slate-400); padding-top: 4px; text-align: center; font-size: 8px; color: var(--slate-500); font-weight: 600; text-transform: uppercase; }

            .footer { margin-top: 20px; padding-top: 8px; border-top: 1px dashed var(--slate-300); text-align: center; font-size: 7.5px; color: var(--slate-400); }
            .footer-brand { font-weight: 700; color: var(--slate-600); font-size: 8px; }
            .footer-thanks { font-size: 7px; color: var(--slate-400); }

            .no-items { text-align: center; padding: 30px 15px; color: var(--slate-400); font-size: 10px; border: 2px dashed var(--slate-200); border-radius: 10px; margin: 12px 0; }

            @media print {
              body { margin: 0; padding: 0; background: #fff !important; color: #0f172a !important; }
              .no-print { display: none !important; }
              .summary-card, .venda-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            }
          </style>
        </head>
        <body>
          <div class="print-container">

            <div class="print-header">
              <div class="header-brand">
                ${logoUrl ? `<img src="${logoUrl}" alt="${nomeEmpresa}" onerror="this.style.display='none'">` : ''}
                <div class="brand-info">
                  <div class="brand-name">${nomeEmpresa}</div>
                  ${nuitEmpresa ? `<div class="brand-detail">NUIT: ${nuitEmpresa}</div>` : ''}
                  ${enderecoEmpresa ? `<div class="brand-detail">${enderecoEmpresa}</div>` : ''}
                  ${(telefoneEmpresa || emailEmpresa)
                    ? `<div class="brand-detail">${telefoneEmpresa}${telefoneEmpresa && emailEmpresa ? ' • ' : ''}${emailEmpresa}</div>`
                    : ''}
                </div>
              </div>
              <div class="header-title">
                <div class="title-label">Relatório</div>
                <div class="title-main">VENDAS</div>
                <div class="title-sub">Emitido em ${dataEmissao}</div>
              </div>
            </div>

            <div class="period-bar">
              <div>
                <span class="period-label">Loja:</span>
                <span class="period-value">${nomeLoja}</span>
              </div>
              <div>
                <span class="period-label">Período:</span>
                <span class="period-value">${periodoTexto}</span>
              </div>
              <div class="emit-info">
                Funcionário: <strong>${nomeFuncionario}</strong> • Via: <strong>${nomeVia}</strong>
              </div>
            </div>

            <div class="summary-cards">
              <div class="summary-card card-vendas">
                <div class="card-label">Vendas</div>
                <div class="card-value">${totalVendas.value}</div>
              </div>
              <div class="summary-card card-itens">
                <div class="card-label">Itens</div>
                <div class="card-value">${totalResumo.totalItems}</div>
              </div>
              <div class="summary-card card-total">
                <div class="card-label">Total</div>
                <div class="card-value">${formatarMoeda(totalResumo.totalVenda)}</div>
              </div>
              <div class="summary-card card-iva">
                <div class="card-label">IVA</div>
                <div class="card-value">${formatarMoeda(totalResumo.totalIva)}</div>
              </div>
              <div class="summary-card card-lucro">
                <div class="card-label">Lucro</div>
                <div class="card-value">${formatarMoeda(totalResumo.totalLucro)}</div>
              </div>
            </div>

            <div class="vendas-section">
              ${contentHTML.includes('<table')
                ? contentHTML
                : `<div class="no-items">Nenhuma venda encontrada para os filtros aplicados</div>`
              }
            </div>

            <div class="signature-block">
              <div class="signature-line">Responsável pelo Relatório</div>
              <div class="signature-line">Gerência / Direção</div>
            </div>

            <div class="footer">
              <div class="footer-brand">${nomeEmpresa} • Documento gerado eletronicamente</div>
              <div>Emitido em ${dataCurta} às ${new Date().toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' })}</div>
              <div class="footer-thanks">Obrigado pela preferência! ✨</div>
            </div>

          </div>

          <script>
            (function() {
              window.onload = function() {
                setTimeout(function() {
                  window.focus();
                  window.print();
                  setTimeout(function() { window.close(); }, 500);
                }, 700);
              };
              window.onafterprint = function() {
                setTimeout(function() { window.close(); }, 300);
              };
            })();
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
                        📊 Vendas Diárias
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Dados das vendas realizadas
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

        <div class="py-12">
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
                                    <option v-for="user in funcionarios" :key="user.id" :value="user.name">
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
                                    <option v-for="via in viasdepagamento" :key="via.id" :value="via.nome">
                                        {{ via.nome }}
                                    </option>
                                </select>
                            </div>

                            <!-- Loja -->
                            <div class="md:col-span-2">
                                <label class="block mb-1 text-sm font-medium text-gray-700">
                                    Loja
                                </label>
                                <select
                                    v-model="lojaid"
                                    class="w-full border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option :value="100" v-show="can('visualizar-relatorio')">-- Selecione --</option>
                                    <option
                                        v-for="loja in lojas"
                                        :key="loja.id"
                                        :value="loja.id"
                                        v-show="can('Relatorio-' + loja.Desc) || can('visualizar-relatorio')"
                                    >
                                        {{ loja.Desc }}
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
                    <div class="p-4 mb-6 text-white rounded-lg shadow-sm bg-gradient-to-r from-blue-900 to-blue-800">
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
                    <div v-for="(venda, index) in dadosvendasfeitas" class="p-6 bg-white shadow-lg dark:bg-gray-800 rounded-xl" :key="index">
                        <vendasDetalhesShow
                            :venda="venda?.detalhes?.itens || []"
                            :FaturaVenda="venda"
                            :flag="1"
                        />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2 no-print" v-if="hasData">
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
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
}

* {
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
}

::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
.dark ::-webkit-scrollbar-thumb { background: #475569; }
.dark ::-webkit-scrollbar-thumb:hover { background: #64748b; }

@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

.hover\:shadow-xl:hover {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.dark .bg-white { background-color: #1F2937; }
.dark .text-gray-900 { color: #F3F4F6; }
.dark .text-gray-700 { color: #D1D5DB; }
.dark .border-gray-200 { border-color: #374151; }
</style>