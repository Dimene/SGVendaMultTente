<script setup>
import Swal from 'sweetalert2';

const props = defineProps({
  selector: {
    type: String,
    default: '.tablePane'
  },
  title: {
    type: String,
    default: 'Relatório de Vendas'
  },
  label: {
    type: String,
    default: 'Imprimir'
  }
});

function imprimir() {
  const printContent = document.querySelector(props.selector);

  if (!printContent) {
    Swal.fire({
      icon: 'error',
      title: 'Impressão indisponível',
      text: 'Conteúdo não encontrado para impressão.'
    });
    return;
  }

  const contentClone = printContent.cloneNode(true);
  contentClone.querySelectorAll('.no-print').forEach(element => element.remove());
  contentClone.querySelector('.text-center.py-8')?.remove();

  const printWindow = window.open('', '_blank', 'width=420,height=595');
  if (!printWindow) {
    Swal.fire({
      icon: 'warning',
      title: 'Pop-up bloqueado',
      text: 'Permita pop-ups no navegador para imprimir.'
    });
    return;
  }

  const contentHTML = contentClone.innerHTML;
  const agora = new Date().toLocaleDateString('pt-PT', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
  const hoje = new Date().toLocaleDateString('pt-PT');

  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>${props.title}</title>
      <style>
        @page { size: A4 portrait; margin: 10mm 8mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 10px; line-height: 1.4; background: white; color: #333; }
        .print-container { max-width: 100%; padding: 2px; }
        .print-header { text-align: center; margin-bottom: 8px; padding-bottom: 5px; border-bottom: 2px solid #007bff; }
        .print-header h2 { font-size: 14px; color: #007bff; margin: 0; }
        .print-header p { font-size: 9px; color: #666; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin: 5px 0; font-size: 9px; }
        thead th { background: #007bff; color: white; padding: 4px 6px; text-align: left; font-weight: 600; font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        thead th:not(:first-child), tbody td:not(:first-child) { text-align: right; }
        tbody td { padding: 4px 6px; border-bottom: 1px solid #dee2e6; vertical-align: top; }
        tbody tr:last-child td { border-bottom: none; }
        tfoot td { padding: 5px 6px; border-top: 2px solid #007bff; font-weight: bold; font-size: 10px; background: #f8f9fa; }
        tfoot td:last-child { color: #007bff; font-size: 11px; }
        .no-items { text-align: center; padding: 15px 0; color: #6c757d; font-size: 9px; }
        .footer { text-align: center; margin-top: 8px; padding-top: 5px; border-top: 1px solid #dee2e6; font-size: 7px; color: #6c757d; }
        @media print { body { margin: 0; padding: 0; } .no-print { display: none !important; } .print-container { padding: 0; } }
      </style>
    </head>
    <body>
      <div class="print-container">
        <div class="print-header">
          <h2>${props.title}</h2>
          <p>${agora}</p>
        </div>
        ${contentHTML.includes('table') ? contentHTML : '<div class="no-items"><p>Nenhum item encontrado.</p></div>'}
        <div class="footer">
          <span>Documento emitido por sistema • ${hoje}</span><br>
          <span>Obrigado pela preferência!</span>
        </div>
      </div>
      <script>
        window.onload = function() {
          setTimeout(function() { window.print(); window.close(); }, 300);
        };
      <\/script>
    </body>
    </html>
  `);

  printWindow.document.close();
}
</script>

<template>
  <button
    type="button"
    class="w-full p-2 text-white bg-cyan-700 rounded hover:bg-cyan-800"
    @click="imprimir"
  >
    <i class="fas fa-print" aria-hidden="true"></i>
    {{ label }}
  </button>
</template>
