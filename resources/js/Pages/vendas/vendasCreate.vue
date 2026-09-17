<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, reactive,onMounted } from 'vue';
import axios from 'axios';
import DadosCompra from '../Compras/DadosCompra.vue';
import ThermalTicket from '@/Components/ThermalTicket.vue';

// ============================================================
//  PROPS
// ============================================================
const props = defineProps({
  grupo: { type: Array, default: () => [] },
  tabela_ivas: { type: Array, default: () => [] },
  viaspagamentos: { type: Array, default: () => [] },
  clientes: { type: Array, default: () => [] },
});

// ============================================================
//  COMPOSABLE: CARRINHO
// ============================================================
function useCart() {
  const cart = ref([]);
  const totalItens = ref(0);
  const totalVenda = ref(0);
  const conteudoDados = ref(true);
  const selectedProduct = ref(null);
  

  // Adicionar item ao carrinho (respeita stock)// item  dados 
  function visualizar(produto){

   
     selectedProduct.value = produto;
      conteudoDados.value=false;
     
    console.log(conteudoDados);
  }


  function addItem(produto) {
    if (produto.estoqueDisponivel <= 0) {
      notify('Produto sem stock disponível', 'error');
      return false;
    }

    const existente = cart.value.find(item => item.id === produto.id);
    if (existente) {
      if (existente.quantidade < produto.estoqueOriginal) {
        existente.quantidade++;
        recalcItem(existente);
        updateTotals();
        return true;
      } else {
        notify('Não é possível adicionar mais – stock esgotado.', 'error');
        return false;
      }
    }

    // Novo item
    const precoPadrao = precoSelecionado(produto);
    const novo = {
      id: produto.id,
      categoria: produto.produto?.categoria?.nome || '',
      loja: produto.produtoloja || '',
      nome: produto.produto?.nome || '',
      quantidade: 1,
      preco1: Number(produto.preco_venda1 ?? 0),
      preco2: Number(produto.preco_venda2 ?? 0),
      preco: precoPadrao,
      iva: Number(produto.iva || 0),
      desconto: 0,
      descontoMax: Number(produto.desconto || 0),
      totalLinha: precoPadrao,
    };
    cart.value.push(novo);
    recalcItem(novo);
    updateTotals();
    return true;
  }


  
  // Remover item
  function removeItem(index) {
    cart.value.splice(index, 1);
    updateTotals();
  }

  // Recalcular linha (preço * quantidade - desconto + IVA)
  function recalcItem(item) {
    const precoAtual = Number(item.preco ?? item.preco1 ?? item.preco2 ?? 0);
    let subtotal = precoAtual * item.quantidade;
    const descontoPercent = Math.min(Number(item.desconto || 0), item.descontoMax || 100);
    subtotal = subtotal * (1 - descontoPercent / 100);
    subtotal = subtotal * (1 + Number(item.iva || 0) / 100);
    item.totalLinha = Math.round(subtotal * 100) / 100; // evitar floats
  }

  // Atualizar totais
  function updateTotals() {
    totalItens.value = cart.value.reduce((acc, i) => acc + i.quantidade, 0);
    totalVenda.value = cart.value.reduce((acc, i) => acc + i.totalLinha, 0);
  }

  // Validar quantidade manual (input)
  function validateQuantity(item, produtoOriginal) {
    if (!produtoOriginal) return;
    let qtd = parseInt(item.quantidade) || 1;
    qtd = Math.max(1, qtd);

    // Soma quantidade deste produto no carrinho (incluindo este)
    const totalNoCarrinho = cart.value
      .filter(c => c.id === item.id)
      .reduce((sum, c) => sum + c.quantidade, 0);

    if (totalNoCarrinho > produtoOriginal.estoque) {
      // Ajusta para o máximo possível
      const qtdeOutros = cart.value
        .filter(c => c.id === item.id && c !== item)
        .reduce((sum, c) => sum + c.quantidade, 0);
      const maxPermitido = produtoOriginal.estoque - qtdeOutros;
      if (maxPermitido < 1) {
        notify('Stock insuficiente.', 'error');
        item.quantidade = 1;
      } else {
        item.quantidade = Math.min(qtd, maxPermitido);
      }
    } else {
      item.quantidade = qtd;
    }
    recalcItem(item);
    updateTotals();
  }

  // Limpar carrinho
  function clearCart() {
    cart.value = [];
    updateTotals();
  }

  return {
    cart,
    totalItens,
    totalVenda,
    conteudoDados,
    selectedProduct,
    addItem,
    visualizar,
    removeItem,
    validateQuantity,
    clearCart,
    recalcItem,
    updateTotals,
  };
}

// ============================================================
//  INSTANCIAR COMPOSABLES
// ============================================================
const {
  cart,
  totalItens,
  selectedProduct,
  conteudoDados,
  totalVenda,
  addItem,
  visualizar,
  removeItem,
  validateQuantity,
  clearCart,
  recalcItem,
  updateTotals,
} = useCart();

// ============================================================
//  PRODUTOS (com stock disponível)
// ============================================================
const produtosDisponiveis = computed(() => {
  return props.grupo.map(item => {
    const qtdNoCarrinho = cart.value
      .filter(c => c.id === item.id)
      .reduce((sum, c) => sum + c.quantidade, 0);

    const precoVendaPadrao = Number(item.preco_venda1 ?? item.preco_venda ?? 0);

    return {
      ...item,
      preco_venda: precoVendaPadrao,
      estoqueOriginal: Number(item.produtoloja?.Quantidade ?? 0),
      estoqueDisponivel: Number(item.produtoloja?.Quantidade ?? 0) - qtdNoCarrinho,
    };
  }).filter(item => item.estoqueDisponivel > 0);
});

// ============================================================
//  PESQUISA
// ============================================================
const searchTerm = ref('');

const filteredProducts = computed(() => {
  if (!searchTerm.value.trim()) return produtosDisponiveis.value;
  const term = searchTerm.value.toLowerCase().trim();
  return produtosDisponiveis.value.filter(item => {
    const nome = item.produto?.nome?.toLowerCase() || '';
    const categoria = item.produto?.categoria?.nome?.toLowerCase() || '';
    const codigo = item.produto?.codigo?.toLowerCase() || '';
    const preco = item.preco_venda?.toString() || '';
    return nome.includes(term) || categoria.includes(term) || codigo.includes(term) || preco.includes(term);
  });
});

const hasResults = computed(() => filteredProducts.value.length > 0);

// ============================================================
//  TICKET (abrir/fechar)
// ============================================================
const abrirTicket = ref(false);

function toggleTicket() {
  abrirTicket.value = !abrirTicket.value;
}

// ============================================================
//  FORMULÁRIO DE VENDA (Inertia)
// ============================================================
const form = useForm({
  itens: [],
  total: 0,
  via_pagamento_id: props.viaspagamentos.length ? props.viaspagamentos[0].id : null,
  cliente_id: props.clientes.length ? props.clientes[0].id : null,
  valor_pago: '',
  troco: '',
  referencia: '',
});

const thermalTicket = ref(null);

const clientesDisponiveis = ref([...props.clientes]);
const modalClienteAberto = ref(false);
const clienteSalvando = ref(false);
const clienteErros = ref({});
const novoCliente = reactive({
  nome: '',
  documento: '',
  telefone: '',
  email: '',
});

function abrirModalCliente() {
  clienteErros.value = {};
  modalClienteAberto.value = true;
}

function fecharModalCliente() {
  if (clienteSalvando.value) return;
  modalClienteAberto.value = false;
}

function limparNovoCliente() {
  novoCliente.nome = '';
  novoCliente.documento = '';
  novoCliente.telefone = '';
  novoCliente.email = '';
}

async function registarCliente() {
  clienteErros.value = {};
  clienteSalvando.value = true;


  try {
    const response = await axios.post(route('clientes.store'), novoCliente);
    const cliente = response.data;

    clientesDisponiveis.value.push(cliente);
    form.cliente_id = cliente.id;
    modalClienteAberto.value = false;
    limparNovoCliente();
    notify('Cliente registado com sucesso.', 'success');
  } catch (error) {
    clienteErros.value = error.response?.data?.errors || {};
    notify('Não foi possível registar o cliente.', 'error');
  } finally {
    clienteSalvando.value = false;
  }
}

function precoSelecionado(produto) {
  const preco = Number(form.cliente_id) === 1
    ? produto.preco_venda1
    : produto.preco_venda2;

  return Number(preco ?? produto.preco_venda ?? 0);
}

function atualizarPrecosCarrinho() {
  cart.value.forEach(item => {
    item.preco = Number(Number(form.cliente_id) === 1 ? item.preco1 : item.preco2);
    recalcItem(item);
  });
  updateTotals();
}

// Campos adicionais por via de pagamento
const camposAdicionais = ref([]);

// ============================================================
//  FINALIZAR VENDA
// ============================================================
function finalizarVenda() {
  if (cart.value.length === 0) {
    notify('Carrinho vazio! Adicione produtos.', 'warning');
    return;
  }

  // Validar se todos os itens têm quantidade > 0 e stock
  for (const item of cart.value) {
    const produto = props.grupo.find(p => p.id === item.id);
    if (!produto) {
      notify(`Produto "${item.nome}" não encontrado no catálogo.`, 'error');
      return;
    }
    if (item.quantidade > produto.estoque) {
      notify(`Stock insuficiente para "${item.nome}". Disponível: ${produto.estoque}`, 'error');
      return;
    }
  }

  // Montar payload
  form.itens = cart.value.map(item => ({
    produto_id: item.id,
    nome: item.nome,
    quantidade: item.quantidade,
    preco_unitario: Number(item.preco ?? item.preco1 ?? item.preco2 ?? 0),
    loja:item.loja,
    iva: item.iva,
    desconto: item.desconto,
    total_linha: item.totalLinha,
  }));
  form.total = totalVenda.value;

  // Validar pagamento
  const via = props.viaspagamentos.find(v => v.id === form.via_pagamento_id);
  if (via?.id === 1) { // supondo que ID 1 = dinheiro
    const pago = parseFloat(form.valor_pago) || 0;
    if (pago < form.total) {
      notify('Valor pago insuficiente.', 'error');
      return;
    }
    form.troco = (pago - form.total).toFixed(2);
  } else {
    // outros métodos: referência obrigatória?
    if (!form.referencia?.trim()) {
      notify('Preencha a referência.', 'warning');
      return;
    }
  }

  form.post(route('vendas.store'), {
    onSuccess: () => {
      const cliente = clientesDisponiveis.value.find(item => Number(item.id) === Number(form.cliente_id));
      const viaPagamento = props.viaspagamentos.find(item => Number(item.id) === Number(form.via_pagamento_id));
      const ticket = {
        itens: form.itens.map(item => ({ ...item })),
        total: form.total,
        cliente: cliente?.nome || 'Consumidor',
        pagamento: viaPagamento?.nome || '',
        valor_pago: form.valor_pago,
        troco: form.troco,
      };

      clearCart();
      abrirTicket.value = false;
      notify('Venda finalizada com sucesso!', 'success');
      thermalTicket.value?.imprimir(ticket);
    },
    onError: (errors) => {
      console.error(errors);
      notify('Erro ao finalizar venda. Verifique os dados.', 'error');
    },
  });
}

// ============================================================
//  WATCHERS
// ============================================================
// Atualizar totais sempre que o carrinho mudar
watch(cart, updateTotals, { deep: true });

// Cliente 1 usa preço singular; os restantes usam preço para empresas.
watch(
  () => form.cliente_id,
  () => atualizarPrecosCarrinho(),
  { immediate: true }
);

// Atualizar troco quando valor_pago mudar
watch(
    [() => form.valor_pago, totalVenda],
    () =>troco()
);



function troco(){
  if (Number(form.via_pagamento_id) !== 1) {
    form.troco = '0.00';
    return;
  }

        const pago = Number(form.valor_pago) || 0;
        const total = Number(totalVenda.value) || 0;

        form.troco = pago > total
            ? (pago - total).toFixed(2)
            : '0.00';


}
// Atualizar campos adicionais conforme via de pagamento.
function atualizarCamposPagamento() {
  const viaId = Number(form.via_pagamento_id);
  camposAdicionais.value = [];

  if (viaId === 1) {
    camposAdicionais.value.push(
      { campo: 'valor_pago', tipo: 'number', label: 'Valor Pago' },
      { campo: 'troco', tipo: 'number', label: 'Troco' }
    );
    form.valor_pago = '';
    form.troco = '0.00';
    return;
  }

  camposAdicionais.value.push(
    { campo: 'valor_pago', tipo: 'number', label: 'Valor Pago' },
    { campo: 'referencia', tipo: 'text', label: 'Referência/Talão' }
  );
  form.valor_pago = Number(totalVenda.value || 0).toFixed(2);
  form.troco = '0.00';
  form.referencia = '';
}

watch(
  () => form.via_pagamento_id,
  atualizarCamposPagamento,
  { immediate: true }
);

watch(totalVenda, () => {
  if (Number(form.via_pagamento_id) !== 1) {
    form.valor_pago = Number(totalVenda.value || 0).toFixed(2);
  }
});

// ============================================================
//  NOTIFICAÇÕES (substituto de alert)
// ============================================================
const notificacao = reactive({ mensagem: '', tipo: '', visivel: false });
let timeoutNotificacao = null;

function notify(mensagem, tipo = 'info') {
  if (timeoutNotificacao) clearTimeout(timeoutNotificacao);
  notificacao.mensagem = mensagem;
  notificacao.tipo = tipo;
  notificacao.visivel = true;
  timeoutNotificacao = setTimeout(() => {
    notificacao.visivel = false;
  }, 4000);
}

// ============================================================
//  UTILITÁRIOS
// ============================================================
function getAtributos(dados) {
  if (!dados) return {};
  try {
    return typeof dados === 'string' ? JSON.parse(dados) : dados;
  } catch {
    return {};
  }
}

// Fechar ticket com tecla ESC (opcional)
// Pode ser adicionado no mounted



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


// ============ FORMATADORES ============
const formatarData = (data) => {
    if (!data) return 'N/A';
    try {
        const date = new Date(data);
        return date.toLocaleDateString('pt-MZ', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    } catch { return data; }
};

const formatarMoeda = (valor) => `MT ${Number(valor || 0).toFixed(2)}`;

const getStatusColor = (estoque) => {
    if (estoque > 50) return 'bg-green-500';
    if (estoque > 20) return 'bg-yellow-500';
    if (estoque > 0) return 'bg-orange-500';
    return 'bg-red-500';
};

const getStatusText = (estoque) => {
    if (estoque > 50) return 'Estoque alto';
    if (estoque > 20) return 'Estoque médio';
    if (estoque > 0) return 'Estoque baixo';
    return 'Esgotado';
};

const getStatusBadgeColor = (estoque) => {
    if (estoque > 50) return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
    if (estoque > 20) return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
    if (estoque > 0) return 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400';
    return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
};



// ============ FUNÇÕES AUXILIARES ============
const extrairAtributos = (produto) => {
    if (!produto?.outrosatributos?.outrosAtributos) return {};
    try {
        let atributos = produto.outrosatributos.outrosAtributos;
        if (typeof atributos === 'string') atributos = JSON.parse(atributos);
        return atributos;
    } catch { return {}; }
};

const obterFotos = (produto) => {
    if (!produto?.outrosatributos?.fotos) return [];
    try {
        let fotos = produto.outrosatributos.fotos;
        if (typeof fotos === 'string') fotos = JSON.parse(fotos);
        return Array.isArray(fotos) ? fotos : [];
    } catch { return []; }
};

const visualizardetalhes = defineModel("visualizardetalhes", { type: Boolean, default: false });

function voltar() {
    visualizardetalhes.value = false;
}

const atributosParaLista = (produto) => {
    const atributos = extrairAtributos(produto);
    const lista = [];
    const icones = {
        'IVA': '💰', 'iva': '💰', 'lucro': '📈', 'Som': '🔊', 'Grafica': '🎮',
        'Memoria': '🧠', 'Monitor': '🖥️', 'Armazenamento': '💾', 'armazem': '🏪',
        'Processador': '⚡', 'Placa Mãe': '🔌', 'Fonte': '🔋', 'Teclado': '⌨️', 'Mouse': '🖱️'
    };
    for (const [chave, valor] of Object.entries(atributos)) {
        lista.push({ label: chave, value: valor, icon: icones[chave] || '📌' });
    }
    return lista;
};

</script>

<template>
  <Head title="Vendas" />
  <ThermalTicket ref="thermalTicket" />

  <AuthenticatedLayout>
 <template #header>
            <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
                <div>
                    <h2 class="flex items-center gap-2 text-2xl font-bold text-gray-800 dark:text-white">
                         <i class="text-2xl text-blue-600 fas fa-store"></i>
                         Vendas
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                            <!-- {{ dataAtualizacao.toLocaleTimeString('pt-MZ') }} -->
                        </span>
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                       Registar Nova Venda *:
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
    <!-- NOTIFICAÇÃO -->
    <div
      v-if="notificacao.visivel"
      class="fixed top-4 left-1/2 transform -translate-x-1/2 z-[999] max-w-sm w-full px-4 py-3 rounded-xl shadow-2xl transition-all duration-300"
      :class="{
        'bg-green-100 border-green-400 text-green-800': notificacao.tipo === 'success',
        'bg-red-100 border-red-400 text-red-800': notificacao.tipo === 'error',
        'bg-yellow-100 border-yellow-400 text-yellow-800': notificacao.tipo === 'warning',
        'bg-blue-100 border-blue-400 text-blue-800': notificacao.tipo === 'info',
      }"
    >
      <div class="flex items-center gap-2">
        <i class="fas fa-circle-check" v-if="notificacao.tipo === 'success'"></i>
        <i class="fas fa-circle-exclamation" v-else-if="notificacao.tipo === 'error'"></i>
        <i class="fas fa-triangle-exclamation" v-else-if="notificacao.tipo === 'warning'"></i>
        <i class="fas fa-info-circle" v-else></i>
        <span>{{ notificacao.mensagem }}</span>
      </div>
    </div>

    <!-- Modal de novo cliente -->
    <div
      v-if="modalClienteAberto"
      class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/50 px-4"
      @click.self="fecharModalCliente"
    >
      <div class="w-full max-w-lg overflow-hidden bg-white shadow-2xl rounded-2xl dark:bg-gray-800">
        <div class="flex items-center justify-between px-5 py-4 text-white bg-blue-600">
          <div>
            <h3 class="text-lg font-bold">Novo cliente</h3>
            <p class="text-xs text-blue-100">Registe o cliente para esta venda</p>
          </div>
          <button type="button" class="p-2 rounded-full hover:bg-white/20" @click="fecharModalCliente">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <form class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2" @submit.prevent="registarCliente">
          <div class="sm:col-span-2">
            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200">Nome *</label>
            <input
              v-model="novoCliente.nome"
              type="text"
              required
              autofocus
              class="w-full p-2.5 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
            />
            <p v-if="clienteErros.nome" class="mt-1 text-xs text-red-600">{{ clienteErros.nome[0] }}</p>
          </div>

          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200">Documento</label>
            <input v-model="novoCliente.documento" type="text" class="w-full p-2.5 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
          </div>

          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200">Telefone</label>
            <input v-model="novoCliente.telefone" type="tel" class="w-full p-2.5 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
          </div>

          <div class="sm:col-span-2">
            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200">Email</label>
            <input v-model="novoCliente.email" type="email" class="w-full p-2.5 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
            <p v-if="clienteErros.email" class="mt-1 text-xs text-red-600">{{ clienteErros.email[0] }}</p>
          </div>

          <div class="flex justify-end gap-2 sm:col-span-2">
            <button type="button" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200" @click="fecharModalCliente">
              Cancelar
            </button>
            <button type="submit" :disabled="clienteSalvando" class="px-4 py-2 font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">
              <i class="mr-1 fas" :class="clienteSalvando ? 'fa-spinner fa-spin' : 'fa-save'"></i>
              {{ clienteSalvando ? 'A guardar...' : 'Guardar cliente' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="pb-20 mx-auto max-w-10xl sm:px-6 lg:px-1 " v-show="conteudoDados">
      <!-- Barra de pesquisa -->
      <div class="sticky top-0 z-50 px-4 pt-2 pb-4 -mx-4 bg-white rounded-lg shadow-sm backdrop-blur-sm">
        <div class="relative max-w-2xl mx-auto rounded-lg">
          <i class="absolute text-gray-400 transform -translate-y-1/2 fas fa-search left-3 top-1/2"></i>
          <input
            v-model="searchTerm"
            type="text"
            placeholder="🔍 Pesquisar produto, categoria, código..."
            class="w-full py-3 pl-10 pr-4 transition-all duration-300 bg-gray-100 border-0 rounded-full shadow-md focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
          <button
            v-if="searchTerm"
            @click="searchTerm = ''"
            class="absolute text-gray-400 transform -translate-y-1/2 right-3 top-1/2 hover:text-gray-600"
          >
            <i class="fas fa-times-circle"></i>
          </button>
        </div>
        <div v-if="searchTerm" class="mt-2 text-sm text-center text-gray-500">
          {{ filteredProducts.length }} resultado{{ filteredProducts.length !== 1 ? 's' : '' }} encontrado{{ filteredProducts.length !== 1 ? 's' : '' }}
        </div>
      </div>

      <!-- Grid de produtos -->
      <div v-if="hasResults || !searchTerm" class="grid grid-cols-2 gap-4 mt-4 sm:grid-cols-3 lg:grid-cols-4">
        <div
          v-for="produto in filteredProducts"
          :key="produto.id"
          class="overflow-hidden transition-all duration-300 bg-white border border-gray-200 group rounded-xl hover:border-blue-300 hover:shadow-lg"
        >
          <!-- Imagem -->
          <div class="relative flex items-center justify-center h-40 overflow-hidden bg-gradient-to-br from-gray-50 to-gray-200">
            <template v-if="getAtributos(produto.outrosatributos?.fotos)?.length">
              <img
                :src="'/storage/' + getAtributos(produto.outrosatributos?.fotos)[0]"
                class="object-cover w-full h-full transition-transform duration-500 group-hover:scale-105"
                :alt="produto.produto?.nome || 'Produto'"
              />
              <div v-if="getAtributos(produto.outrosatributos?.fotos).length > 1"
                   class="absolute bottom-2 right-2 bg-black/70 text-white text-xs px-2 py-0.5 rounded-full">
                +{{ getAtributos(produto.outrosatributos?.fotos).length - 1 }}
              </div>
            </template>
            <div v-else class="text-center text-gray-400">
              <i class="block mb-2 text-3xl fas fa-image"></i>
              <span class="text-xs">Sem imagem</span>
            </div>
            <!-- Badges stock -->
            <div v-if="produto.estoqueDisponivel === 0" class="absolute px-2 py-1 text-xs text-white bg-red-500 rounded-full shadow-lg top-2 right-2">
              Esgotado
            </div>
            <div v-else-if="produto.estoqueDisponivel <= 5" class="absolute px-2 py-1 text-xs text-white bg-orange-400 rounded-full shadow-lg top-2 right-2">
              Últimas unidades
            </div>
          </div>

          <!-- Conteúdo -->
          <div class="p-3">
            <div class="text-xs font-semibold text-blue-600 uppercase tracking-wider mb-0.5">
              {{ produto.produto?.categoria?.nome || 'Categoria' }}
            </div>
            <div class="text-sm font-medium truncate" :title="produto.produto?.nome">
              {{ produto.produto?.nome || 'Produto sem nome' }}
            </div>

            <!-- Atributos extras -->
            <div v-if="getAtributos(produto.outrosatributos?.outrosAtributos)" class="mt-1 space-y-0.5 text-xs">
              <div v-for="(dados, key) in getAtributos(produto.outrosatributos?.outrosAtributos)"
                   :key="key"
                   v-show="key !=='lucro'|| key !=='armazem'"
                   class="flex justify-between">
                <span class="text-gray-500">{{ key }}</span>
                <span class="font-medium text-gray-700">{{ dados }}</span>
              </div>
              <div v-show="produto.desconto > 0" class="flex justify-between">
                <span class="text-gray-500">Desconto</span>
                <span class="font-medium text-gray-700">{{ produto.desconto }}%</span>
              </div>
            </div>

            <!-- Preço e stock -->
            <div class="flex items-center justify-between pt-2 mt-2 border-t border-gray-100">
              <div class="flex items-center gap-1">
                <i class="text-xs text-gray-400 fas fa-box"></i>
                <span class="text-xs text-gray-600">{{ produto.estoqueDisponivel }}</span>
              </div>
              <div class="text-right">
                <span class="text-sm font-bold text-green-600">
                  Singular:
                  {{ Number(produto.preco_venda1 || 0).toFixed(2) }} MZN
                </span> 
                <span class="text-sm font-bold text-green-600">
              Empresa:
                  {{ Number(produto.preco_venda2 || 0).toFixed(2) }} MZN
                </span>
              </div>
            </div>

            <!-- Botões -->
            <div class="flex gap-2 pt-2 mt-2 border-t border-gray-100">
              <button
                @click="addItem(produto)"
                :disabled="produto.estoqueDisponivel === 0"
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium py-1.5 px-2 rounded-lg transition-colors duration-200 flex items-center justify-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <i class="fas fa-cart-plus"></i>
                Adicionar
              </button>
              <button class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs 
              font-medium py-1.5 px-2 rounded-lg transition-colors duration-200 flex items-center justify-center gap-1"
              @click="visualizar(produto)"
              >
                <i class="fas fa-eye"></i>
                Ver
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Mensagem vazia -->
      <div v-if="!filteredProducts.length && !searchTerm" class="py-20 text-center text-gray-500">
        <i class="block mb-4 text-gray-300 fas fa-box-open text-7xl"></i>
        <p class="text-xl font-semibold">Nenhum produto disponível</p>
        <p class="text-sm text-gray-400">Os produtos aparecerão aqui quando forem adicionados</p>
      </div>
    </div>

    <div  v-show="!conteudoDados">
       <button
                type="button"
                @click="conteudoDados=true"
                class="px-4 py-2 text-white bg-gray-500 rounded-lg"
            >
                ← Voltar
            </button>
      <div v-if="selectedProduct" class="space-y-4">
                            <!-- Cards de info (mantido igual ao original) -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                    <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nome</label>
                                    <p class="mt-1 text-sm font-semibold text-gray-800 truncate dark:text-white">{{ selectedProduct?.produto?.nome || 'N/A' }}</p>
                                </div>
                                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                    <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Categoria</label>
                                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white">{{ selectedProduct?.produto?.categoria?.nome || 'N/A' }}</p>
                                </div>
                                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                    <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Grupo</label>
                                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white">{{ selectedProduct?.produto?.grupo?.nome || 'N/A' }}</p>
                                </div>
                                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                    <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID Produto</label>
                                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white">#{{ selectedProduct?.produto_id || 'N/A' }}</p>
                                </div>
                                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                    <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Armazém</label>
                                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white">#{{ selectedProduct?.Armazem_id || 'N/A' }}</p>
                                </div>
                                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                    <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estoque</label>
                                    <p class="mt-1 text-sm font-semibold" :class="getStatusBadgeColor(selectedProduct?.estoque || 0)">
                                        {{ selectedProduct?.estoque || 0 }} unidades
                                    </p>
                                </div>
                            </div>

                            <!-- Preços -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                    <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Preço Compra</label>
                                    <p class="mt-1 text-sm font-semibold text-orange-600 dark:text-orange-400">{{ formatarMoeda(selectedProduct?.preco_compra) }}</p>
                                </div>
                                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                    <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Preço Venda 1 - Singular</label>
                                    <p class="mt-1 text-sm font-semibold text-green-600 dark:text-green-400">{{ formatarMoeda(selectedProduct?.preco_venda1) }}</p>
                                </div> 
                                
                                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                    <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Preço Venda 2 - Empresa</label>
                                    <p class="mt-1 text-sm font-semibold text-green-600 dark:text-green-400">{{ formatarMoeda(selectedProduct?.preco_venda2) }}</p>
                                </div>
                                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                    <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">IVA</label>
                                    <p class="mt-1 text-sm font-semibold text-purple-600 dark:text-purple-400">{{ selectedProduct?.iva || 0 }}%</p>
                                </div>
                                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                    <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Desconto</label>
                                    <p class="mt-1 text-sm font-semibold text-red-600 dark:text-red-400">{{ selectedProduct?.desconto || 0 }}%</p>
                                </div>
                            </div>

                            <!-- Margem de lucro -->
                            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Margem de Lucro</label>
                                <div class="flex items-center gap-3 mt-1">
                                    <span class="text-sm font-bold text-purple-600 dark:text-purple-400">
                                        {{ selectedProduct?.preco_venda && selectedProduct?.preco_compra
                                            ? ((selectedProduct.preco_venda - selectedProduct.preco_compra) / selectedProduct.preco_compra * 100).toFixed(1)
                                            : 0 }}%
                                    </span>
                                    <div class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full transition-all duration-500 rounded-full bg-gradient-to-r from-green-500 to-purple-500"
                                             :style="{ width: `${selectedProduct?.preco_venda && selectedProduct?.preco_compra ? Math.min(((selectedProduct.preco_venda - selectedProduct.preco_compra) / selectedProduct.preco_compra * 100), 100) : 0}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Atributos -->
                            <div v-if="Object.keys(extrairAtributos(selectedProduct)).length > 0" class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center gap-2">🔧 Especificações</label>
                                <div class="grid grid-cols-2 gap-2 mt-2">
                                    <template v-for="atributo in atributosParaLista(selectedProduct)" :key="atributo.label">
                                      <div v-if="atributo.label !== 'Preço Venda'"
                                         class="flex items-center gap-2 p-2 bg-white border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-600">
                                        <span class="text-base">{{ atributo.icon }}</span>
                                        <div class="min-w-0">
                                          <p class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase">{{ atributo.label }}</p>
                                          <p class="text-xs font-semibold text-gray-800 truncate dark:text-white">{{ atributo.value }}</p>
                                        </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Fotos -->
                            <div v-if="obterFotos(selectedProduct).length > 0" class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center gap-2">📸 Fotos</label>
                                <div class="flex gap-2 pb-2 mt-2 overflow-x-auto">
                                    <div v-for="(foto, index) in obterFotos(selectedProduct)" :key="index"
                                         class="flex-shrink-0 w-20 h-20 overflow-hidden bg-gray-100 border border-gray-200 rounded-lg dark:border-gray-600 dark:bg-gray-700">
                                        <img :src="`/storage/${foto}`" :alt="`Foto ${index + 1}`"
                                             class="object-cover w-full h-full"
                                             @error="(e) => e.target.src = '/images/no-image.png'"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="flex flex-wrap items-center gap-2 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Status:</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium flex items-center gap-1"
                                      :class="getStatusBadgeColor(selectedProduct?.estoque || 0)">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="getStatusColor(selectedProduct?.estoque || 0)"></span>
                                    {{ getStatusText(selectedProduct?.estoque || 0) }}
                                </span>
                                <span class="text-[10px] text-gray-500 ml-auto">Criado: {{ formatarData(selectedProduct?.created_at) }}</span>
                            </div>

                            <button
                              @click="addItem(selectedProduct)"
                :disabled="selectedProduct.estoqueDisponivel === 0"
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium py-1.5 px-2 rounded-lg transition-colors duration-200 flex items-center justify-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed"
            
                            >
        
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                Adicionar ao Carrinho
                            </button>
                        </div>
    </div>

    <!-- Botão flutuante do carrinho -->
    <button
      @click="toggleTicket"
      class="fixed z-50 flex items-center justify-center w-16 h-16 text-white transition-transform duration-300 rounded-full shadow-2xl bottom-6 right-6 bg-gradient-to-br from-blue-500 to-blue-700 hover:scale-110 flutuar"
    >
      <i class="text-2xl fas fa-shopping-cart"></i>
      <span class="absolute flex items-center justify-center text-xs font-bold text-white bg-red-500 border-2 border-white rounded-full shadow-lg -top-1 -right-1 w-7 h-7">
        {{ totalItens }}
      </span>
    </button>

    <!-- Ticket -->
    <div
      v-if="abrirTicket"
      class="fixed bottom-24 right-6 z-50 w-auto max-w-[calc(100vw-2rem)] bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden transition-all duration-300 max-h-[80vh] flex flex-col"
    >
      <!-- Cabeçalho -->
      <div class="flex items-center justify-between flex-shrink-0 p-4 text-white bg-gradient-to-r from-blue-600 to-blue-800">
        <div class="flex items-center gap-2">
          <i class="text-xl fas fa-receipt"></i>
          <span class="text-lg font-bold">Ticket de Venda</span>
        </div>
        <button @click="abrirTicket=false" class="p-1 transition-colors rounded-full hover:bg-white/20">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <!-- Conteúdo -->
      <div class="flex-1 p-4 overflow-y-auto">

   <div class="flex gap-2 flex-col-2">
        <!-- Via de pagamento -->
        
        <select v-model="form.via_pagamento_id" class="w-full p-2 mb-3 border rounded-lg">
          <option v-for="via in viaspagamentos" :key="via.id" :value="via.id">
            {{ via.nome }}
          </option>
        </select>


       
          <div class="flex items-center w-full gap-2 mb-3">
            <select v-model="form.cliente_id" class="flex-1 w-full p-2 border rounded-lg">
              <option v-for="cliente in clientesDisponiveis" :key="cliente.id" :value="cliente.id">
                {{ cliente.nome }}
              </option>
            </select>
            <button
              type="button"
              title="Registar novo cliente"
              class="flex items-center justify-center h-10 gap-1 px-3 text-white bg-green-600 rounded-lg hover:bg-green-700"
              @click="abrirModalCliente"
            >
              <i class="fas fa-plus"></i>
              <span>Novo</span>
            </button>
          </div>



        </div>


        <!-- Lista do carrinho -->
        <div v-if="totalItens === 0" class="py-8 text-center text-gray-400">
          <i class="block mb-2 text-4xl fas fa-shopping-basket"></i>
          <p>Carrinho vazio</p>
        </div>

        <template v-else>
          <!-- Cabeçalho tabela -->
          <div class="grid grid-cols-6 gap-1 pb-2 mb-2 text-xs font-semibold text-gray-500 border-b">
            <span class="col-span-2">Produto</span>
            <span class="text-center">Qtd</span>
            <span class="text-center">IVA</span>
            <span class="text-center">Desc</span>
            <span class="text-right">Total</span>
          </div>

          <!-- Itens -->
          <div
            v-for="(item, index) in cart"
            :key="item.id"
            class="grid items-center grid-cols-6 gap-1 py-2 text-sm border-b"
          >
            <div class="col-span-2">
              <p class="text-xs font-bold text-gray-800">{{ item.categoria }}</p>
              <p class="text-xs text-gray-600 truncate">{{ item.nome }}</p>
            </div>
            <input
              type="number"
              v-model.number="item.quantidade"
              @input="validateQuantity(item, props.grupo.find(p => p.id === item.id))"
              min="1"
              class="w-12 text-sm text-center border rounded-lg outline-none focus:ring-2 focus:ring-blue-300"
            />
            <select
              v-model.number="item.iva"
              @change="recalcItem(item); updateTotals()"
              class="w-20 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-blue-300"
            >
              <option v-for="v in props.tabela_ivas" :key="v.id" :value="Number(v.percentagem)">
                {{ v.percentagem }}%
              </option>
              <option value="0">0%</option>
            </select>
            <input
              type="number"
              v-model.number="item.desconto"
              @input="recalcItem(item); updateTotals()"
              min="0"
              :max="item.descontoMax"
              class="text-sm text-center border rounded-lg outline-none w-14 focus:ring-2 focus:ring-blue-300"
            />
            <div class="flex items-center justify-end gap-1">
              <span class="font-bold text-green-600">{{ item.totalLinha.toFixed(2) }}</span>
              <button @click="removeItem(index)" class="text-xs text-red-500 hover:text-red-700">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </template>
      </div>

      <!-- Rodapé -->
      <div class="flex-shrink-0 p-4 border-t bg-gray-50">
        <div class="flex items-center justify-between text-lg font-bold">
          <span>Total</span>
          <span class="text-xl text-green-600">{{ totalVenda.toFixed(2) }} MZN</span>
        </div>

        <!-- Campos adicionais -->
        <div v-for="campo in camposAdicionais" :key="campo.campo" class="mt-2">
          <label class="block text-sm font-medium">{{ campo.label }}</label>
          <input
            :type="campo.tipo"
            v-model="form[campo.campo]"
            class="w-full p-2 border rounded-lg"
            :disabled="campo.campo === 'troco'"
          />
        </div>

        <button
          @click="finalizarVenda"
          class="flex items-center justify-center w-full gap-2 py-3 mt-3 font-bold text-white transition-all duration-300 shadow-lg bg-gradient-to-r from-green-500 to-green-700 hover:from-green-600 hover:to-green-800 rounded-xl"
        >
          <i class="fas fa-check-circle"></i>
          Finalizar Venda
        </button>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
@keyframes flutuar {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}
.flutuar {
  animation: flutuar 2s infinite;
}

::-webkit-scrollbar {
  width: 6px;
}
::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}
::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
.group:hover {
  transform: translateY(-2px);
}
input:focus, select:focus {
  outline: none;
}
button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
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

