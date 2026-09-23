<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted, reactive } from "vue";
import vendasDetalhesShow from './vendasDetalhesShow.vue';
import PrintReportButton from '@/Components/PrintReportButton.vue';
import Swal from 'sweetalert2';
import { debounce } from 'lodash';
import { usePermission } from '@/composables/usePermission';
const { 
    can
        }= usePermission();
// ==================== PROPS ====================
const props = defineProps({
    funcionarios: {          // ← antes era 'usuario'
        type: Array,
        default: () => []
    },
    viasdepagamento: {
        type: Array,
        default: () => []
    },
    empresa: {               // ← antes vinha como '$empresa', nunca chegava
        type: Object,
        default: () => ({})
    }
});
// ==================== STATE ====================
const page = props.empresa;
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
// ═══════════════════════════════════════════════════════════════
// FUNÇÃO: imprimirA5()
// Descrição: Gera janela de impressão A4 com design profissional
// ═══════════════════════════════════════════════════════════════
function imprimirA5() {
  // ─────────────────────────────────────────────
  // 1. VALIDAÇÃO
  // ─────────────────────────────────────────────
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

  // ─────────────────────────────────────────────
  // 2. CLONE E LIMPEZA
  // ─────────────────────────────────────────────
  const contentClone = printContent.cloneNode(true);

  contentClone.querySelectorAll('.no-print').forEach(el => el.remove());
  contentClone.querySelectorAll('.text-center.py-8, .loading, .spinner').forEach(el => el.remove());
  contentClone.querySelectorAll('input, button, select, textarea').forEach(el => el.remove());
  // Remove scrollbars/overflow
  contentClone.querySelectorAll('[style*="overflow"]').forEach(el => el.style.overflow = 'visible');

  const contentHTML = contentClone.innerHTML;

  // ─────────────────────────────────────────────
  // 3. DADOS SEGUROS (com fallbacks)
  // ─────────────────────────────────────────────
  const empresa = page.props?.empresa || {};
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

  // Período do relatório
  const periodoTexto = (dataInicial.value && dataFinal.value)
    ? `${new Date(dataInicial.value).toLocaleDateString('pt-PT')} → ${new Date(dataFinal.value).toLocaleDateString('pt-PT')}`
    : 'Período não definido';

  // ─────────────────────────────────────────────
  // 4. ABRIR JANELA
  // ─────────────────────────────────────────────
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

  // ─────────────────────────────────────────────
  // 5. HTML DE IMPRESSÃO
  // ─────────────────────────────────────────────
  printWindow.document.write(`
    <!DOCTYPE html>
    <html lang="pt">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Relatório de Vendas — ${nomeEmpresa}</title>

      <!-- Fontes premium -->
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

      <style>
        /* ═══════════════════════════════════════════════
           VARIÁVEIS
           ═══════════════════════════════════════════════ */
        :root {
          --primary: #6366f1;
          --primary-dark: #4f46e5;
          --primary-light: #818cf8;
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
          --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
          --font-mono: 'JetBrains Mono', 'Courier New', monospace;
        }

        /* ═══════════════════════════════════════════════
           RESET
           ═══════════════════════════════════════════════ */
        * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
          -webkit-print-color-adjust: exact !important;
          print-color-adjust: exact !important;
        }

        @page {
          size: A4 portrait;
          margin: 10mm 10mm;
        }

        html, body { width: 100%; }

        body {
          font-family: var(--font-sans);
          font-size: 10px;
          line-height: 1.45;
          color: var(--slate-800);
          background: #ffffff;
          -webkit-font-smoothing: antialiased;
        }

        .print-container {
          width: 100%;
          max-width: 100%;
          position: relative;
        }

        /* ═══════════════════════════════════════════════
           CABEÇALHO
           ═══════════════════════════════════════════════ */
        .print-header {
          display: flex;
          align-items: center;
          justify-content: space-between;
          gap: 14px;
          padding-bottom: 10px;
          margin-bottom: 12px;
          border-bottom: 3px solid var(--primary);
          position: relative;
        }

        .print-header::after {
          content: '';
          position: absolute;
          bottom: -3px;
          left: 0;
          width: 90px;
          height: 3px;
          background: linear-gradient(90deg, var(--info), var(--purple));
        }

        .header-brand {
          display: flex;
          align-items: center;
          gap: 12px;
          flex: 1;
          min-width: 0;
        }

        .header-brand img {
          max-height: 55px;
          max-width: 110px;
          object-fit: contain;
          border-radius: 8px;
        }

        .header-brand .brand-info {
          display: flex;
          flex-direction: column;
          gap: 2px;
          min-width: 0;
        }

        .header-brand .brand-name {
          font-size: 14px;
          font-weight: 800;
          color: var(--slate-900);
          letter-spacing: -0.02em;
          line-height: 1.1;
        }

        .header-brand .brand-detail {
          font-size: 8.5px;
          color: var(--slate-500);
          line-height: 1.35;
        }

        .header-title {
          text-align: right;
          flex-shrink: 0;
        }

        .header-title .title-label {
          font-size: 8px;
          font-weight: 700;
          letter-spacing: 2px;
          text-transform: uppercase;
          color: var(--slate-400);
        }

        .header-title .title-main {
          font-size: 20px;
          font-weight: 800;
          color: var(--primary);
          line-height: 1.1;
          letter-spacing: -0.02em;
        }

        .header-title .title-sub {
          font-size: 8.5px;
          color: var(--slate-500);
          font-weight: 500;
          margin-top: 2px;
        }

        /* ═══════════════════════════════════════════════
           BARRA DE PERÍODO
           ═══════════════════════════════════════════════ */
        .period-bar {
          display: flex;
          align-items: center;
          justify-content: space-between;
          gap: 10px;
          padding: 6px 12px;
          background: linear-gradient(135deg, var(--slate-100), var(--slate-50));
          border: 1px solid var(--slate-200);
          border-radius: 8px;
          margin-bottom: 12px;
          font-size: 9px;
        }

        .period-bar .period-label {
          font-size: 7.5px;
          font-weight: 700;
          text-transform: uppercase;
          letter-spacing: 0.8px;
          color: var(--slate-400);
        }

        .period-bar .period-value {
          font-family: var(--font-mono);
          font-weight: 700;
          color: var(--slate-700);
          font-size: 9.5px;
        }

        .period-bar .emit-info {
          font-size: 8px;
          color: var(--slate-500);
        }

        /* ═══════════════════════════════════════════════
           CARDS DE RESUMO
           ═══════════════════════════════════════════════ */
        .summary-cards {
          display: grid;
          grid-template-columns: repeat(5, 1fr);
          gap: 6px;
          margin-bottom: 14px;
        }

        .summary-card {
          padding: 8px 6px;
          border-radius: 8px;
          text-align: center;
          color: #ffffff;
          position: relative;
          overflow: hidden;
        }

        .summary-card::before {
          content: '';
          position: absolute;
          top: 0; left: 0; right: 0;
          height: 3px;
          background: rgba(255,255,255,0.3);
        }

        .summary-card.card-vendas    { background: linear-gradient(135deg, #6366f1, #4f46e5); }
        .summary-card.card-itens     { background: linear-gradient(135deg, #06b6d4, #0891b2); }
        .summary-card.card-total     { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .summary-card.card-iva       { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .summary-card.card-lucro     { background: linear-gradient(135deg, #10b981, #059669); }

        .summary-card .card-label {
          font-size: 7px;
          font-weight: 700;
          text-transform: uppercase;
          letter-spacing: 0.7px;
          opacity: 0.9;
          margin-bottom: 3px;
        }

        .summary-card .card-value {
          font-family: var(--font-mono);
          font-size: 13px;
          font-weight: 800;
          line-height: 1.1;
          letter-spacing: -0.02em;
        }

        /* ═══════════════════════════════════════════════
           SEÇÃO DE VENDAS
           ═══════════════════════════════════════════════ */
        .vendas-section {
          margin-top: 6px;
        }

        .venda-item {
          margin-bottom: 14px;
          page-break-inside: avoid;
          border: 1px solid var(--slate-200);
          border-radius: 10px;
          overflow: hidden;
          background: #ffffff;
        }

        .venda-header {
          display: flex;
          align-items: center;
          justify-content: space-between;
          padding: 6px 12px;
          background: linear-gradient(135deg, var(--slate-800), var(--slate-900));
          color: #ffffff;
        }

        .venda-header .venda-numero {
          font-family: var(--font-mono);
          font-size: 11px;
          font-weight: 800;
          letter-spacing: -0.02em;
        }

        .venda-header .venda-meta {
          display: flex;
          gap: 14px;
          font-size: 8px;
        }

        .venda-header .venda-meta-item {
          display: flex;
          align-items: center;
          gap: 3px;
        }

        .venda-header .venda-meta-item .label {
          color: rgba(255,255,255,0.6);
          font-weight: 600;
          text-transform: uppercase;
          font-size: 7px;
          letter-spacing: 0.5px;
        }

        .venda-header .venda-meta-item .value {
          font-family: var(--font-mono);
          font-weight: 700;
          font-size: 8.5px;
        }

        /* Tabela dentro da venda */
        .venda-body {
          padding: 8px 10px;
        }

        .venda-body table {
          width: 100%;
          border-collapse: collapse;
          font-size: 8.5px;
        }

        .venda-body table thead th {
          background: var(--slate-100);
          color: var(--slate-600);
          padding: 5px 7px;
          text-align: left;
          font-weight: 700;
          font-size: 7px;
          text-transform: uppercase;
          letter-spacing: 0.5px;
          border-bottom: 2px solid var(--slate-300);
        }

        .venda-body table thead th:not(:first-child) {
          text-align: right;
        }

        .venda-body table tbody td {
          padding: 4px 7px;
          border-bottom: 1px solid var(--slate-100);
          vertical-align: top;
          color: var(--slate-700);
        }

        .venda-body table tbody td:not(:first-child) {
          text-align: right;
          font-family: var(--font-mono);
          font-size: 8px;
          font-weight: 500;
        }

        .venda-body table tbody tr:nth-child(even) {
          background: var(--slate-50);
        }

        .venda-body table tbody tr:last-child td {
          border-bottom: none;
        }

        .venda-body table .produto-nome {
          font-weight: 700;
          font-size: 8.5px;
          color: var(--slate-800);
          display: block;
          line-height: 1.2;
        }

        .venda-body table .produto-detalhe {
          font-size: 7px;
          color: var(--slate-500);
          display: block;
          margin-top: 1px;
          font-weight: 500;
        }

        /* Rodapé da venda com totais */
        .venda-footer {
          display: flex;
          justify-content: flex-end;
          gap: 12px;
          padding: 5px 10px;
          background: var(--slate-50);
          border-top: 1px solid var(--slate-200);
          font-size: 8px;
        }

        .venda-footer .total-item {
          display: flex;
          align-items: center;
          gap: 4px;
        }

        .venda-footer .total-item .label {
          color: var(--slate-500);
          font-weight: 600;
          text-transform: uppercase;
          font-size: 7px;
          letter-spacing: 0.5px;
        }

        .venda-footer .total-item .value {
          font-family: var(--font-mono);
          font-weight: 800;
          color: var(--slate-800);
          font-size: 9.5px;
        }

        .venda-footer .total-item.total-geral .value {
          color: var(--primary);
          font-size: 11px;
        }

        /* ═══════════════════════════════════════════════
           ASSINATURAS
           ═══════════════════════════════════════════════ */
        .signature-block {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 40px;
          margin-top: 30px;
          padding-top: 15px;
          page-break-inside: avoid;
        }

        .signature-line {
          border-top: 1px solid var(--slate-400);
          padding-top: 4px;
          text-align: center;
          font-size: 8px;
          color: var(--slate-500);
          font-weight: 600;
          text-transform: uppercase;
          letter-spacing: 0.6px;
        }

        /* ═══════════════════════════════════════════════
           RODAPÉ
           ═══════════════════════════════════════════════ */
        .footer {
          margin-top: 20px;
          padding-top: 8px;
          border-top: 1px dashed var(--slate-300);
          text-align: center;
          font-size: 7.5px;
          color: var(--slate-400);
          line-height: 1.6;
        }

        .footer .footer-brand {
          font-weight: 700;
          color: var(--slate-600);
          font-size: 8px;
          letter-spacing: 0.3px;
        }

        .footer .footer-thanks {
          font-size: 7px;
          color: var(--slate-400);
          margin-top: 1px;
        }

        /* ═══════════════════════════════════════════════
           ESTADO VAZIO
           ═══════════════════════════════════════════════ */
        .no-items {
          text-align: center;
          padding: 30px 15px;
          color: var(--slate-400);
          font-size: 10px;
          border: 2px dashed var(--slate-200);
          border-radius: 10px;
          margin: 12px 0;
        }

        /* ═══════════════════════════════════════════════
           DARK MODE (opcional)
           ═══════════════════════════════════════════════ */
        body.dark-mode {
          background: #0f172a;
          color: #e2e8f0;
        }

        body.dark-mode .print-header { border-bottom-color: var(--primary-light); }
        body.dark-mode .brand-name { color: #f1f5f9; }
        body.dark-mode .brand-detail { color: #94a3b8; }
        body.dark-mode .period-bar {
          background: #1e293b;
          border-color: #334155;
        }
        body.dark-mode .period-bar .period-value { color: #e2e8f0; }
        body.dark-mode .venda-item {
          background: #1e293b;
          border-color: #334155;
        }
        body.dark-mode .venda-body table thead th {
          background: #334155;
          color: #cbd5e1;
        }
        body.dark-mode .venda-body table tbody td {
          color: #cbd5e1;
          border-bottom-color: #334155;
        }
        body.dark-mode .venda-body table tbody tr:nth-child(even) { background: #0f172a; }
        body.dark-mode .venda-body table .produto-nome { color: #f1f5f9; }
        body.dark-mode .venda-footer {
          background: #0f172a;
          border-top-color: #334155;
        }
        body.dark-mode .venda-footer .total-item .value { color: #e2e8f0; }
        body.dark-mode .footer { border-top-color: #334155; color: #64748b; }

        /* ═══════════════════════════════════════════════
           IMPRESSÃO
           ═══════════════════════════════════════════════ */
        @media print {
          body {
            margin: 0;
            padding: 0;
            background: #ffffff !important;
            color: #0f172a !important;
          }
          body.dark-mode {
            background: #ffffff !important;
            color: #0f172a !important;
          }
          .no-print { display: none !important; }
          .print-container { padding: 0; }
          a { text-decoration: none; color: inherit; }

          .summary-card { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
          .venda-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
      </style>
    </head>
    <body>
      <div class="print-container">

        <!-- ═══════════════ CABEÇALHO ═══════════════ -->
        <div class="print-header">
          <div class="header-brand">
            ${logoUrl
              ? `<img src="${logoUrl}" alt="${nomeEmpresa}" onerror="this.style.display='none'">`
              : ''
            }
            <div class="brand-info">
              <div class="brand-name">${nomeEmpresa}</div>
              ${nuitEmpresa ? `<div class="brand-detail">NUIT: ${nuitEmpresa}</div>` : ''}
              ${enderecoEmpresa ? `<div class="brand-detail">${enderecoEmpresa}</div>` : ''}
              ${(telefoneEmpresa || emailEmpresa)
                ? `<div class="brand-detail">${telefoneEmpresa}${telefoneEmpresa && emailEmpresa ? ' • ' : ''}${emailEmpresa}</div>`
                : ''
              }
            </div>
          </div>

          <div class="header-title">
            <div class="title-label">Relatório</div>
            <div class="title-main">VENDAS</div>
            <div class="title-sub">Emitido em ${dataEmissao}</div>
          </div>
        </div>

        <!-- ═══════════════ PERÍODO ═══════════════ -->
        <div class="period-bar">
          <div>
            <span class="period-label">Período:</span>
            <span class="period-value">${periodoTexto}</span>
          </div>
          <div class="emit-info">
            ${usuarioSelecionado.value ? `Funcionário kjfdkjkjdfkjdf: <strong>${usuarioSelecionado.value}</strong>` : 'Todos os funcionários'}
            ${viapagamentoselcionada.value ? ` • Via: <strong>${viapagamentoselcionada.value}</strong>` : ''}
          </div>
        </div>

        <!-- ═══════════════ CARDS DE RESUMO ═══════════════ -->
        <div class="summary-cards">
          <div class="summary-card card-vendas">
            <div class="card-label">Vendas </div>
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

        <!-- ═══════════════ VENDAS ═══════════════ -->
        <div class="vendas-section">
          ${contentHTML.includes('<table') || contentHTML.includes('tablePane')
            ? contentHTML
            : `
              <div class="no-items">
                Nenhuma venda encontrada para os filtros aplicados
              </div>
            `
          }
        </div>

        <!-- ═══════════════ ASSINATURAS ═══════════════ -->
        <div class="signature-block">
          <div class="signature-line">Responsável pelo Relatório</div>
          <div class="signature-line">Gerência / Direção</div>
        </div>

        <!-- ═══════════════ RODAPÉ ═══════════════ -->
        <div class="footer">
          <div class="footer-brand">${nomeEmpresa} • Documento gerado eletronicamente</div>
          <div>Emitido em ${dataCurta} às ${new Date().toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' })}</div>
          <div class="footer-thanks">Obrigado pela preferência! ✨</div>
        </div>

      </div>

      <!-- ═══════════════ SCRIPT DE AUTO-IMPRESSÃO ═══════════════ -->
      <script>
        (function() {
          // Para ativar dark mode na visualização, descomente:
          // document.body.classList.add('dark-mode');

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
                                   <option v-for="user in funcionarios"     :key="user.id" :value="user.name" >
   

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
