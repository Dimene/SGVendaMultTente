<script setup>
import Swal from 'sweetalert2';
import { usePage } from '@inertiajs/vue3';

const emit = defineEmits(['printed']);
const page = usePage();
const empresa = page.props?.empresa || {};

// ==================== EMPRESA ====================
const nomeEmpresa     = empresa.nome_fantasia || empresa.nome || 'Empresa';
const nuitEmpresa     = empresa.nuit || '';
const enderecoEmpresa = empresa.endereco || '';
const telefoneEmpresa = empresa.telefone || '';
const emailEmpresa    = empresa.email || '';

// ==================== LOGO ====================
// Resolve o caminho do logo com segurança:
// - se já começa com "http" → usa tal como está
// - se começa com "/" → usa tal como está
// - senão → prefixa com /storage/
function resolveLogo(logo) {
  if (!logo) return null;
  const s = String(logo).trim();
  if (!s) return null;
  if (s.startsWith('http://') || s.startsWith('https://')) return s;
  if (s.startsWith('/')) return s;
  if (s.startsWith('storage/')) return `/${s}`;
  return `/storage/${s}`;
}

const logoUrl = resolveLogo(empresa.logo);

// ==================== HELPERS ====================
function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function moeda(value) {
  return Number(value || 0).toFixed(2);
}

// ==================== IMPRIMIR ====================
async function imprimir(ticket) {
  const confirmacao = await Swal.fire({
    icon: 'success',
    title: 'Venda finalizada',
    text: 'Deseja imprimir o ticket?',
    showCancelButton: true,
    confirmButtonText: 'Imprimir ticket',
    cancelButtonText: 'Agora não',
    confirmButtonColor: '#16a34a',
    reverseButtons: true
  });

  if (!confirmacao.isConfirmed) return;

  const printWindow = window.open('', '_blank', 'width=380,height=700');
  if (!printWindow) {
    await Swal.fire({
      icon: 'warning',
      title: 'Pop-up bloqueado',
      text: 'Permita pop-ups no navegador para imprimir o ticket.'
    });
    return;
  }

  const linhas = (ticket.itens || []).map(item => `
    <tr>
      <td class="col-prod">
        <span class="prod-nome">${escapeHtml(item.nome)}</span>
        <small class="details">
          ${item.iva ? `IVA ${moeda(item.iva)}%` : ''}
          ${item.desconto ? ` • Desc. ${moeda(item.desconto)}%` : ''}
        </small>
      </td>
      <td class="col-qtd">${item.quantidade}</td>
      <td class="col-preco">${moeda(item.preco_unitario)}</td>
      <td class="col-total">${moeda(item.total_linha)}</td>
    </tr>
  `).join('');

  const data      = new Date();
  const dataHora  = data.toLocaleString('pt-PT', { dateStyle: 'short', timeStyle: 'short' });
  const dataCurta = data.toLocaleDateString('pt-PT');

  // subtotal e iva calculados localmente para o resumo
  const subtotal = (ticket.itens || []).reduce((acc, i) => acc + (Number(i.preco_unitario) * Number(i.quantidade)), 0);
  const totalIva = (ticket.itens || []).reduce(
    (acc, i) => acc + (Number(i.preco_unitario) * Number(i.quantidade)) * (Number(i.iva || 0) / 100),
    0
  );

  printWindow.document.write(`
    <!doctype html>
    <html lang="pt">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Ticket — ${escapeHtml(ticket.fatura || '')}</title>
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
      <style>
        /* ═══════════════ RESET ═══════════════ */
        * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
          -webkit-print-color-adjust: exact !important;
          print-color-adjust: exact !important;
        }

        @page {
          size: 80mm auto;
          margin: 2mm;
        }

        html, body {
          width: 76mm;
          margin: 0 auto;
        }

        body {
          font-family: 'Inter', Arial, sans-serif;
          font-size: 10.5px;
          line-height: 1.4;
          color: #0f172a;
          background: #ffffff;
          padding: 3mm 2mm;
          -webkit-font-smoothing: antialiased;
        }

        /* ═══════════════ CABEÇALHO ═══════════════ */
        .header {
          text-align: center;
          padding-bottom: 6px;
          border-bottom: 1.5px dashed #94a3b8;
          margin-bottom: 8px;
        }

        .header .logo-wrap {
          display: flex;
          align-items: center;
          justify-content: center;
          margin-bottom: 6px;
        }

        .header .logo-wrap img {
          max-height: 48px;
          max-width: 60mm;
          object-fit: contain;
          display: block;
        }

        .header .empresa-nome {
          font-size: 14px;
          font-weight: 800;
          letter-spacing: -0.02em;
          color: #0f172a;
          margin-bottom: 2px;
        }

        .header .empresa-info {
          font-size: 8.5px;
          color: #64748b;
          line-height: 1.35;
        }

        .header .empresa-info div {
          display: block;
        }

        /* ═══════════════ BADGE DOC ═══════════════ */
        .doc-badge {
          text-align: center;
          margin: 6px 0 4px;
        }

        .doc-badge .doc-title {
          display: inline-block;
          font-size: 9px;
          font-weight: 800;
          letter-spacing: 2px;
          text-transform: uppercase;
          color: #6366f1;
          padding: 2px 8px;
          border: 1px solid #c7d2fe;
          border-radius: 4px;
          background: #eef2ff;
        }

        .doc-fatura {
          text-align: center;
          font-family: 'JetBrains Mono', monospace;
          font-size: 11px;
          font-weight: 700;
          color: #0f172a;
          margin-top: 4px;
        }

        .doc-data {
          text-align: center;
          font-size: 8.5px;
          color: #64748b;
          margin-top: 1px;
        }

        /* ═══════════════ SECÇÃO INFO ═══════════════ */
        .info-row {
          display: flex;
          justify-content: space-between;
          gap: 6px;
          font-size: 9.5px;
          margin: 4px 0;
        }

        .info-row .info-label {
          font-weight: 700;
          color: #475569;
          text-transform: uppercase;
          font-size: 8px;
          letter-spacing: 0.5px;
        }

        .info-row .info-value {
          font-weight: 600;
          color: #0f172a;
          text-align: right;
          word-break: break-word;
        }

        /* ═══════════════ DIVISOR ═══════════════ */
        .line {
          border-top: 1px dashed #94a3b8;
          margin: 7px 0;
        }

        .line.solid {
          border-top: 1px solid #0f172a;
          margin: 5px 0;
        }

        /* ═══════════════ TABELA ITENS ═══════════════ */
        table {
          width: 100%;
          border-collapse: collapse;
          font-size: 9.5px;
        }

        thead th {
          font-size: 8px;
          font-weight: 800;
          text-transform: uppercase;
          letter-spacing: 0.5px;
          color: #475569;
          text-align: left;
          padding: 3px 0;
          border-bottom: 1px solid #0f172a;
        }

        thead th.col-qtd,
        thead th.col-preco,
        thead th.col-total {
          text-align: right;
        }

        tbody td {
          padding: 3px 0;
          vertical-align: top;
          border-bottom: 1px dotted #e2e8f0;
        }

        tbody tr:last-child td {
          border-bottom: none;
        }

        .col-prod  { width: 46%; text-align: left; }
        .col-qtd   { width: 12%; text-align: center; font-family: 'JetBrains Mono', monospace; font-weight: 600; }
        .col-preco { width: 20%; text-align: right; font-family: 'JetBrains Mono', monospace; font-weight: 600; }
        .col-total { width: 22%; text-align: right; font-family: 'JetBrains Mono', monospace; font-weight: 700; }

        .prod-nome {
          display: block;
          font-weight: 600;
          font-size: 9.5px;
          line-height: 1.25;
          color: #0f172a;
        }

        .details {
          display: block;
          font-size: 7.5px;
          color: #64748b;
          margin-top: 1px;
        }

        /* ═══════════════ TOTAIS ═══════════════ */
        .totais {
          margin-top: 4px;
        }

        .totais .linha {
          display: flex;
          justify-content: space-between;
          font-size: 9.5px;
          padding: 2px 0;
        }

        .totais .linha .label {
          color: #64748b;
          font-weight: 600;
        }

        .totais .linha .value {
          font-family: 'JetBrains Mono', monospace;
          font-weight: 600;
          color: #0f172a;
        }

        .totais .linha.total-final {
          margin-top: 4px;
          padding-top: 6px;
          border-top: 1.5px solid #0f172a;
          font-size: 13px;
        }

        .totais .linha.total-final .label {
          color: #0f172a;
          font-weight: 800;
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }

        .totais .linha.total-final .value {
          font-weight: 800;
          color: #6366f1;
          font-size: 14px;
        }

        /* ═══════════════ PAGAMENTO ═══════════════ */
        .pagamento {
          background: #f8fafc;
          border: 1px solid #e2e8f0;
          border-radius: 6px;
          padding: 6px 8px;
          margin-top: 8px;
          font-size: 9.5px;
        }

        .pagamento .linha {
          display: flex;
          justify-content: space-between;
          padding: 1.5px 0;
        }

        .pagamento .linha .label {
          color: #64748b;
          font-weight: 600;
        }

        .pagamento .linha .value {
          font-family: 'JetBrains Mono', monospace;
          font-weight: 700;
          color: #0f172a;
        }

        .pagamento .linha.troco .value {
          color: #16a34a;
        }

        /* ═══════════════ RODAPÉ ═══════════════ */
        .footer {
          margin-top: 10px;
          padding-top: 8px;
          border-top: 1.5px dashed #94a3b8;
          text-align: center;
        }

        .footer .obrigado {
          font-size: 11px;
          font-weight: 700;
          color: #0f172a;
          margin-bottom: 3px;
        }

        .footer .sub {
          font-size: 8px;
          color: #64748b;
          line-height: 1.4;
        }

        .footer .sub .brand {
          font-weight: 700;
          color: #475569;
        }

        .footer .qr {
          font-family: 'JetBrains Mono', monospace;
          font-size: 8px;
          color: #94a3b8;
          letter-spacing: 2px;
          margin-top: 4px;
        }

        /* ═══════════════ PRINT ═══════════════ */
        @media print {
          body {
            width: 76mm;
            padding: 0;
          }
          a { text-decoration: none; color: inherit; }
        }
      </style>
    </head>
    <body>

      <!-- ═══════════════ CABEÇALHO ═══════════════ -->
      <div class="header">
        ${logoUrl ? `
          <div class="logo-wrap">
            <img
              src="${escapeHtml(logoUrl)}"
              alt="${escapeHtml(nomeEmpresa)}"
              referrerpolicy="no-referrer"
              crossorigin="anonymous"
            >
          </div>
        ` : ''}

        <div class="empresa-nome">${escapeHtml(nomeEmpresa)}</div>

        <div class="empresa-info">
          ${nuitEmpresa     ? `<div>NUIT: ${escapeHtml(nuitEmpresa)}</div>` : ''}
          ${enderecoEmpresa ? `<div>${escapeHtml(enderecoEmpresa)}</div>` : ''}
          ${(telefoneEmpresa || emailEmpresa)
            ? `<div>${escapeHtml(telefoneEmpresa)}${telefoneEmpresa && emailEmpresa ? ' • ' : ''}${escapeHtml(emailEmpresa)}</div>`
            : ''
          }
        </div>
      </div>

      <!-- ═══════════════ DOC ═══════════════ -->
      <div class="doc-badge">
        <span class="doc-title">Venda</span>
      </div>
      <div class="doc-fatura">${escapeHtml(ticket.fatura || '')}</div>
      <div class="doc-data">${dataHora}</div>

      <div class="line"></div>

      <!-- ═══════════════ CLIENTE ═══════════════ -->
      <div class="info-row">
        <span class="info-label">Cliente</span>
        <span class="info-value">${escapeHtml(ticket.cliente || 'Consumidor Final')}</span>
      </div>

      ${ticket.vendedor ? `
        <div class="info-row">
          <span class="info-label">Vendedor</span>
          <span class="info-value">${escapeHtml(ticket.vendedor)}</span>
        </div>
      ` : ''}

      <div class="line"></div>

      <!-- ═══════════════ ITENS ═══════════════ -->
      <table>
        <thead>
          <tr>
            <th class="col-prod">Produto</th>
            <th class="col-qtd">Qtd</th>
            <th class="col-preco">Preço</th>
            <th class="col-total">Total</th>
          </tr>
        </thead>
        <tbody>
          ${linhas || '<tr><td colspan="4" class="center" style="padding:8px;color:#94a3b8;">Sem itens</td></tr>'}
        </tbody>
      </table>

      <!-- ═══════════════ TOTAIS ═══════════════ -->
      <div class="totais">
        ${subtotal ? `
          <div class="linha">
            <span class="label">Subtotal</span>
            <span class="value">${moeda(subtotal)} MZN</span>
          </div>
        ` : ''}

        ${totalIva ? `
          <div class="linha">
            <span class="label">IVA</span>
            <span class="value">${moeda(totalIva)} MZN</span>
          </div>
        ` : ''}

        <div class="linha total-final">
          <span class="label">Total</span>
          <span class="value">${moeda(ticket.total)} MZN</span>
        </div>
      </div>

      <!-- ═══════════════ PAGAMENTO ═══════════════ -->
      <div class="pagamento">
        ${ticket.pagamento ? `
          <div class="linha">
            <span class="label">Forma</span>
            <span class="value">${escapeHtml(ticket.pagamento)}</span>
          </div>
        ` : ''}

        <div class="linha">
          <span class="label">Pago</span>
          <span class="value">${moeda(ticket.valor_pago)} MZN</span>
        </div>

        ${Number(ticket.troco) > 0 ? `
          <div class="linha troco">
            <span class="label">Troco</span>
            <span class="value">${moeda(ticket.troco)} MZN</span>
          </div>
        ` : ''}
      </div>

      <!-- ═══════════════ RODAPÉ ═══════════════ -->
      <div class="footer">
        <div class="obrigado">Obrigado pela preferência!</div>
        <div class="sub">
          <span class="brand">${escapeHtml(nomeEmpresa)}</span><br>
          Documento emitido em ${dataCurta}
        </div>
        <div class="qr">* * * * * * * * * * * *</div>
      </div>

      <script>
        window.onload = function () {
          setTimeout(function () {
            window.focus();
            window.print();
            setTimeout(function () { window.close(); }, 400);
          }, 400);
        };
        window.onafterprint = function () {
          setTimeout(function () { window.close(); }, 200);
        };
      <\/script>
    </body>
    </html>
  `);

  printWindow.document.close();
  emit('printed');
}

defineExpose({ imprimir });
</script>

<template>
  <span class="hidden" aria-hidden="true"></span>
</template>