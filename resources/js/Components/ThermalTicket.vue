<script setup>
import Swal from 'sweetalert2';

const emit = defineEmits(['printed']);

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

async function imprimir(ticket) {
  const confirmacao = await Swal.fire({
    icon: 'success',
    title: 'Venda finalizada',
    text: 'Deseja imprimir o ticket?',
    showCancelButton: true,
    confirmButtonText: 'Imprimir ticket',
    cancelButtonText: 'Agora não',
    confirmButtonColor: '#16a34a'
  });

  if (!confirmacao.isConfirmed) return;

  const printWindow = window.open('', '_blank', 'width=320,height=600');
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
      <td>
        ${escapeHtml(item.nome)}
        <small class="details">IVA: ${moeda(item.iva)}% | Desc.: ${moeda(item.desconto)}%</small>
      </td>
      <td class="center">${item.quantidade}</td>
      <td class="right">${moeda(item.preco_unitario)}</td>
      <td class="right">${moeda(item.total_linha)}</td>
    </tr>
  `).join('');

  const data = new Date().toLocaleString('pt-PT');
  printWindow.document.write(`
    <!doctype html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>Ticket de venda</title>
      <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; }
        body { width: 72mm; margin: 0 auto; padding: 3mm 0; color: #000; font: 11px/1.3 Arial, sans-serif; }
        h1 { margin: 0; text-align: center; font-size: 16px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .muted { color: #333; font-size: 10px; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { border-bottom: 1px solid #000; text-align: left; }
        th, td { padding: 2px 0; vertical-align: top; }
        th:nth-child(2), td:nth-child(2) { width: 12%; text-align: center; }
        th:nth-child(3), td:nth-child(3) { width: 22%; text-align: right; }
        th:nth-child(4), td:nth-child(4) { width: 24%; text-align: right; }
        .details { display: block; color: #333; font-size: 8px; }
        .total { display: flex; justify-content: space-between; font-size: 15px; font-weight: bold; }
        .footer { margin-top: 10px; text-align: center; font-size: 10px; }
      </style>
    </head>
    <body>
      <h1>VENDA</h1>
      <div class="center muted">${escapeHtml(ticket.fatura || '')}</div>
      <div class="center muted">${data}</div>
      <div class="line"></div>
      <div>Cliente: ${escapeHtml(ticket.cliente || 'Consumidor')}</div>
      <div class="line"></div>
      <table>
        <thead><tr><th>Produto</th><th>Qtd</th><th>Preço</th><th>Total</th></tr></thead>
        <tbody>${linhas}</tbody>
      </table>
      <div class="line"></div>
      <div class="total"><span>TOTAL</span><span>${moeda(ticket.total)} MZN</span></div>
      <div>Pagamento: ${escapeHtml(ticket.pagamento || '')}</div>
      <div>Pago: ${moeda(ticket.valor_pago)} MZN</div>
      <div>Troco: ${moeda(ticket.troco)} MZN</div>
      <div class="footer">Obrigado pela preferência!</div>
      <script>
        window.onload = function () {
          setTimeout(function () { window.print(); window.close(); }, 300);
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
