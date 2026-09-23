<template>
  <div class="grid grid-cols-1 tablePane">
    <!-- Verificação de dados antes de renderizar -->
    <div v-if="!FaturaVenda || !FaturaVenda.Fatura" class="py-8 text-center">
      <i class="text-3xl text-blue-500 fas fa-spinner fa-spin"></i>
      <p class="mt-2 text-gray-600">Carregando detalhes da venda...</p>
    </div>

    <template v-else>
      <!-- <b>Detalhes da Compra</b> -->
      <hr>

      <div class="grid grid-cols-1 gap-2 p-2 text-white rounded-sm bg-slate-600 md:grid-cols-4">
        <p class="p-2">
          <b>Fatura-{{ FaturaVenda.Fatura }}</b>
         
          <small>
            <i>({{ FaturaVenda.Via_pagamento || 'N/A' }} {{ FaturaVenda.referencia || '' }})</i>
          </small>
        </p>

        <div class="p-2">
          <b>Data:</b>
          {{ FaturaVenda.Data || 'N/A' }} às {{ FaturaVenda.Horas || 'N/A' }}
        </div>

        <div class="p-2">
          <b>Custo:</b>
          {{ formatCurrency(FaturaVenda.Total) }}
          <small>({{ FaturaVenda.Items || 0 }} item{{ FaturaVenda.Items > 1 ? 's' : '' }})</small>
        </div>

        <div class="p-2">
          <b>Registada Por:</b>
          {{ FaturaVenda.usuario || 'N/A' }}
        </div>

         <span><b>Cliente:</b>{{FaturaVenda.cliente}}</span>
      </div>

      <!-- Tabela de Itens -->
      <div v-if="venda && venda.length > 0" class="mt-4 overflow-x-auto">
        <table class="w-full border-collapse">
          <thead class="bg-gray-100">
            <tr>
              <th class="p-2 text-left border">Nome</th>
              <th class="p-2 text-right border">Qtd</th>
              <th class="p-2 text-right border">Preço</th>
              <th class="p-2 text-right border">IVA</th>
              <th class="p-2 text-right border">Desconto</th>
              <th class="p-2 text-right border">Valor</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(value, index) in venda" :key="index" class="hover:bg-gray-50"   >
              <td class="p-2 border">
                <b>{{ getProdutoNome(value) }}</b>
                <span v-if="value.produto?.produto?.nome">
                  ({{ value.produto.produto.nome }})
                </span>
              </td>
              <td class="p-2 text-right border">{{ value.quantidade || 0 }}</td>
              <td class="p-2 text-right border">{{ formatCurrency(value.preco_unitario) }}</td>
              <td class="p-2 text-right border">{{ value.iva || 0 }}%</td>
              <td class="p-2 text-right border">{{ value.desconto||0 }}%</td>
              <td class="p-2 text-right border">{{ formatCurrency(value.subtotal) }}</td>
            </tr>
          </tbody>
          <tfoot class="font-bold bg-gray-50">
            <tr>
              <td colspan="5" class="p-2 text-right border">Total:</td>
              <td class="p-2 text-right border">
                {{ formatCurrency(calcularTotal) }}
              </td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div v-else class="py-4 text-center text-gray-500">
        <i class="text-2xl fas fa-box-open"></i>
        <p>Nenhum item encontrado nesta venda</p>
      </div>

      <!-- Botões de Ação -->
      <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2 no-print"  v-if="flag===0">
        <div>
          <button class="w-full p-2 text-white rounded bg-cyan-700 hover:bg-cyan-800" @click="imprimirA5">
            <i class="fas fa-print" aria-hidden="true"></i>
            Imprimir A5
          </button>
        </div>
        <div>
          <button
            class="w-full p-2 text-white bg-blue-600 rounded hover:bg-blue-800"
            @click="reverter(FaturaVenda.id)"
            :disabled="!FaturaVenda.id"
          >
            <i class="fas fa-undo"></i>
            Reverter
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
  venda: {
    type: Array,
    default: () => []
  },
  FaturaVenda: {
    type: Object,
    default: () => ({})
  },
  flag: {
    type: Number,
    default: 0
  }
});


const page = usePage();

// Computed para calcular o total
const calcularTotal = computed(() => {
  if (!props.venda || props.venda.length === 0) return 0;
  return props.venda.reduce((sum, item) => sum + Number(item.subtotal || 0), 0);
});

// Função para formatar moeda
function formatCurrency(value) {
  if (value === null || value === undefined) return 'MT 0.00';
  return `MT ${Number(value).toFixed(2)}`;
}

// Função para obter nome do produto
function getProdutoNome(item) {
  if (!item) return 'Produto';
  if (item.produto?.produto?.categoria?.nome) {
    return item.produto.produto.categoria.nome;
  }
  return 'Produto';
}

// Função para reverter venda
async function reverter(id) {
  if (!id) {
    await Swal.fire({
      icon: 'error',
      title: 'Venda inválida',
      text: 'ID da venda não encontrado.'
    });
    return;
  }

  const confirmacao = await Swal.fire({
    icon: 'warning',
    title: 'Reverter venda?',
    text: 'Esta ação não pode ser desfeita.',
    showCancelButton: true,
    confirmButtonText: 'Sim, reverter',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#dc2626'
  });

  if (confirmacao.isConfirmed) {
    router.get(`/vendas/reverter/vendas/${id}`, {}, {
      onStart: () => console.log('Revertendo venda...'),
      onSuccess: () => {
        console.log('Venda revertida com sucesso!');
        Swal.fire({
          icon: 'success',
          title: 'Venda revertida',
          text: 'A venda foi revertida com sucesso.',
          timer: 2200,
          showConfirmButton: false
        });
        router.reload();
      },
      onError: (errors) => {
        console.error('Erros:', errors);
        Swal.fire({
          icon: 'error',
          title: 'Erro ao reverter',
          text: 'Verifique os dados e tente novamente.'
        });
      },
      onFinish: () => console.log('Processo finalizado'),
    });
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
const empresa = page.props?.empresa || {};

const logoUrl = empresa.logo ? `/storage/${empresa.logo}` : null;
const nomeEmpresa = empresa.nome_fantasia || empresa.nome || 'Empresa';
 
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
      <title>Fatura #${props.FaturaVenda.Fatura || ''}</title>
      <style>
        @page {
          size: A5 portrait;
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
        

  <img src="${logoUrl}" alt="${nomeEmpresa}" onerror="this.style.display='none'" style="max-height: 50px; margin-bottom: 5px;" />
    
          <p>${page.props.empresa.nome}</p>
         <h2>FATURA #${props.FaturaVenda.Fatura || ''}</h2>
          <p>${new Date().toLocaleDateString('pt-PT', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
          })}</p>
        </div>

        <div class="info-grid">
          <div class="info-item">
            <b>Pagamento:</b>
            <span>${props.FaturaVenda.Via_pagamento || 'N/A'}</span>
            <small>${props.FaturaVenda.referencia || ''}</small>
          </div>
          <div class="info-item">
            <b>Vendedor:</b>
            <span>${props.FaturaVenda.usuario || 'N/A'}</span>
          </div>
          <div class="info-item">
            <b>Itens:</b>
            <span>${props.FaturaVenda.Items || 0}</span>
          </div>
          <div class="info-item">
            <b>Total:</b>
            <span style="color: #007bff; font-weight: bold; font-size: 11px;">
              ${formatCurrency(props.FaturaVenda.Total)}
            </span>
          </div>
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
</script>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }
}

/* Estilos para a visualização em tela */
.info-grid-print {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 3px 8px;
  background: #f8f9fa;
  padding: 5px 8px;
  border-radius: 4px;
  margin-bottom: 8px;
}
</style>
