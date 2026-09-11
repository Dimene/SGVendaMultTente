<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, reactive,onMounted } from 'vue';

// ============================================================
//  PROPS
// ============================================================
const props = defineProps({
  grupo: { type: Array, default: () => [] },
  tabela_ivas: { type: Array, default: () => [] },
  viaspagamentos: { type: Array, default: () => [] },
});

// ============================================================
//  COMPOSABLE: CARRINHO
// ============================================================
function useCart() {
  const cart = ref([]);
  const totalItens = ref(0);
  const totalVenda = ref(0);

  // Adicionar item ao carrinho (respeita stock)
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
    const novo = {
      id: produto.id,
      categoria: produto.produto?.categoria?.nome || '',
      nome: produto.produto?.nome || '',
      quantidade: 1,
      preco: Number(produto.preco_venda),
      iva: Number(produto.iva || 0),
      desconto: 0,
      descontoMax: Number(produto.desconto || 0),
      totalLinha: Number(produto.preco_venda),
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
    let subtotal = item.preco * item.quantidade;
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
    addItem,
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
  totalVenda,
  addItem,
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
    return {
      ...item,
      estoqueOriginal: item.estoque,
      estoqueDisponivel: item.estoque - qtdNoCarrinho,
    };
  });
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
  valor_pago: '',
  troco: '',
  referencia: '',
});

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
    quantidade: item.quantidade,
    preco_unitario: item.preco,
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
      clearCart();
      abrirTicket.value = false;
      notify('Venda finalizada com sucesso!', 'success');
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

// Atualizar troco quando valor_pago mudar
watch(
    [() => form.valor_pago, totalVenda],
    () =>troco()
);



function troco(){

        const pago = Number(form.valor_pago) || 0;
        const total = Number(totalVenda.value) || 0;

        form.troco = pago > total
            ? (pago - total).toFixed(2)
            : '0.00';


}
// Atualizar campos adicionais conforme via de pagamento
watch(
  () => form.via_pagamento_id,
  (novoId) => {
    const via = props.viaspagamentos.find(v => v.id === novoId);
    camposAdicionais.value = [];
    if (via?.id === 1) { // dinheiro
      camposAdicionais.value.push(
        { campo: 'valor_pago', tipo: 'number', label: 'Valor Pago' },
        { campo: 'troco', tipo: 'number', label: 'Troco' }
      );
      form.valor_pago = '';
      form.troco = '0.00';
    } else {
      camposAdicionais.value.push(
        { campo: 'referencia', tipo: 'text', label: 'Referência' }
      );
      form.referencia = '';
    }
  },
  { immediate: true }
);

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
</script>

<template>
  <Head title="Vendas" />

  <AuthenticatedLayout>
 <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                         <i class="fas fa-store text-2xl text-blue-600"></i>
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

    <div class="mx-auto max-w-10xl sm:px-6 lg:px-1 pb-20 ">
      <!-- Barra de pesquisa -->
      <div class="sticky top-0 z-50 bg-white backdrop-blur-sm pb-4 pt-2 -mx-4 px-4 shadow-sm  rounded-lg">
        <div class="relative max-w-2xl mx-auto rounded-lg">
          <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
          <input
            v-model="searchTerm"
            type="text"
            placeholder="🔍 Pesquisar produto, categoria, código..."
            class="w-full rounded-full border-0 bg-gray-100 focus:bg-white shadow-md focus:ring-2 focus:ring-blue-500 focus:border-transparent pl-10 pr-4 py-3 transition-all duration-300"
          />
          <button
            v-if="searchTerm"
            @click="searchTerm = ''"
            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
          >
            <i class="fas fa-times-circle"></i>
          </button>
        </div>
        <div v-if="searchTerm" class="mt-2 text-sm text-gray-500 text-center">
          {{ filteredProducts.length }} resultado{{ filteredProducts.length !== 1 ? 's' : '' }} encontrado{{ filteredProducts.length !== 1 ? 's' : '' }}
        </div>
      </div>

      <!-- Grid de produtos -->
      <div v-if="hasResults || !searchTerm" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mt-4">
        <div
          v-for="produto in filteredProducts"
          :key="produto.id"
          class="group bg-white rounded-xl border border-gray-200 hover:border-blue-300 hover:shadow-lg transition-all duration-300 overflow-hidden"
        >
          <!-- Imagem -->
          <div class="relative h-40 bg-gradient-to-br from-gray-50 to-gray-200 flex items-center justify-center overflow-hidden">
            <template v-if="getAtributos(produto.outrosatributos?.fotos)?.length">
              <img
                :src="'/storage/' + getAtributos(produto.outrosatributos?.fotos)[0]"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                :alt="produto.produto?.nome || 'Produto'"
              />
              <div v-if="getAtributos(produto.outrosatributos?.fotos).length > 1"
                   class="absolute bottom-2 right-2 bg-black/70 text-white text-xs px-2 py-0.5 rounded-full">
                +{{ getAtributos(produto.outrosatributos?.fotos).length - 1 }}
              </div>
            </template>
            <div v-else class="text-gray-400 text-center">
              <i class="fas fa-image text-3xl mb-2 block"></i>
              <span class="text-xs">Sem imagem</span>
            </div>
            <!-- Badges stock -->
            <div v-if="produto.estoqueDisponivel === 0" class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full shadow-lg">
              Esgotado
            </div>
            <div v-else-if="produto.estoqueDisponivel <= 5" class="absolute top-2 right-2 bg-orange-400 text-white text-xs px-2 py-1 rounded-full shadow-lg">
              Últimas unidades
            </div>
          </div>

          <!-- Conteúdo -->
          <div class="p-3">
            <div class="text-xs font-semibold text-blue-600 uppercase tracking-wider mb-0.5">
              {{ produto.produto?.categoria?.nome || 'Categoria' }}
            </div>
            <div class="font-medium text-sm truncate" :title="produto.produto?.nome">
              {{ produto.produto?.nome || 'Produto sem nome' }}
            </div>

            <!-- Atributos extras -->
            <div v-if="getAtributos(produto.outrosatributos?.outrosAtributos)" class="mt-1 space-y-0.5 text-xs">
              <div v-for="(dados, key) in getAtributos(produto.outrosatributos?.outrosAtributos)"
                   :key="key"
                   v-show="key !== 'lucro'"
                   class="flex justify-between">
                <span class="text-gray-500">{{ key }}</span>
                <span class="text-gray-700 font-medium">{{ dados }}</span>
              </div>
              <div v-show="produto.desconto > 0" class="flex justify-between">
                <span class="text-gray-500">Desconto</span>
                <span class="text-gray-700 font-medium">{{ produto.desconto }}%</span>
              </div>
            </div>

            <!-- Preço e stock -->
            <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-100">
              <div class="flex items-center gap-1">
                <i class="fas fa-box text-gray-400 text-xs"></i>
                <span class="text-xs text-gray-600">{{ produto.estoqueDisponivel }}</span>
              </div>
              <div class="text-right">
                <span class="text-sm font-bold text-green-600">
                  {{ Number(produto.preco_venda || 0).toFixed(2) }} MZN
                </span>
              </div>
            </div>

            <!-- Botões -->
            <div class="flex gap-2 mt-2 pt-2 border-t border-gray-100">
              <button
                @click="addItem(produto)"
                :disabled="produto.estoqueDisponivel === 0"
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium py-1.5 px-2 rounded-lg transition-colors duration-200 flex items-center justify-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <i class="fas fa-cart-plus"></i>
                Adicionar
              </button>
              <button class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium py-1.5 px-2 rounded-lg transition-colors duration-200 flex items-center justify-center gap-1">
                <i class="fas fa-eye"></i>
                Ver
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Mensagem vazia -->
      <div v-if="!filteredProducts.length && !searchTerm" class="text-center text-gray-500 py-20">
        <i class="fas fa-box-open text-7xl text-gray-300 mb-4 block"></i>
        <p class="text-xl font-semibold">Nenhum produto disponível</p>
        <p class="text-sm text-gray-400">Os produtos aparecerão aqui quando forem adicionados</p>
      </div>
    </div>

    <!-- Botão flutuante do carrinho -->
    <button
      @click="toggleTicket"
      class="fixed bottom-6 right-6 z-50 w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-700 text-white rounded-full shadow-2xl flex items-center justify-center hover:scale-110 transition-transform duration-300 flutuar"
    >
      <i class="fas fa-shopping-cart text-2xl"></i>
      <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold w-7 h-7 rounded-full flex items-center justify-center shadow-lg border-2 border-white">
        {{ totalItens }}
      </span>
    </button>

    <!-- Ticket -->
    <div
      v-if="abrirTicket"
      class="fixed bottom-24 right-6 z-50 w-auto max-w-[calc(100vw-2rem)] bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden transition-all duration-300 max-h-[80vh] flex flex-col"
    >
      <!-- Cabeçalho -->
      <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-4 flex justify-between items-center flex-shrink-0">
        <div class="flex items-center gap-2">
          <i class="fas fa-receipt text-xl"></i>
          <span class="font-bold text-lg">Ticket de Venda</span>
        </div>
        <button @click="abrirTicket=false" class="hover:bg-white/20 p-1 rounded-full transition-colors">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <!-- Conteúdo -->
      <div class="flex-1 overflow-y-auto p-4">
        <!-- Via de pagamento -->
        <label class="block mb-2 font-semibold">Via de Pagamento</label>
        <select v-model="form.via_pagamento_id" class="w-full border rounded-lg p-2 mb-3">
          <option v-for="via in viaspagamentos" :key="via.id" :value="via.id">
            {{ via.nome }}
          </option>
        </select>

        <!-- Lista do carrinho -->
        <div v-if="totalItens === 0" class="text-center text-gray-400 py-8">
          <i class="fas fa-shopping-basket text-4xl mb-2 block"></i>
          <p>Carrinho vazio</p>
        </div>

        <template v-else>
          <!-- Cabeçalho tabela -->
          <div class="grid grid-cols-6 gap-1 text-xs font-semibold text-gray-500 border-b pb-2 mb-2">
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
            class="grid grid-cols-6 gap-1 items-center border-b py-2 text-sm"
          >
            <div class="col-span-2">
              <p class="font-bold text-gray-800 text-xs">{{ item.categoria }}</p>
              <p class="text-gray-600 text-xs truncate">{{ item.nome }}</p>
            </div>
            <input
              type="number"
              v-model.number="item.quantidade"
              @input="validateQuantity(item, props.grupo.find(p => p.id === item.id))"
              min="1"
              class="w-12 border rounded-lg text-center text-sm focus:ring-2 focus:ring-blue-300 outline-none"
            />
            <select
              v-model.number="item.iva"
              @change="recalcItem(item); updateTotals()"
              class="border rounded-lg text-sm w-20 focus:ring-2 focus:ring-blue-300 outline-none"
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
              class="w-14 border rounded-lg text-center text-sm focus:ring-2 focus:ring-blue-300 outline-none"
            />
            <div class="flex items-center gap-1 justify-end">
              <span class="font-bold text-green-600">{{ item.totalLinha.toFixed(2) }}</span>
              <button @click="removeItem(index)" class="text-red-500 hover:text-red-700 text-xs">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </template>
      </div>

      <!-- Rodapé -->
      <div class="border-t p-4 bg-gray-50 flex-shrink-0">
        <div class="flex justify-between items-center text-lg font-bold">
          <span>Total</span>
          <span class="text-green-600 text-xl">{{ totalVenda.toFixed(2) }} MZN</span>
        </div>

        <!-- Campos adicionais -->
        <div v-for="campo in camposAdicionais" :key="campo.campo" class="mt-2">
          <label class="block text-sm font-medium">{{ campo.label }}</label>
          <input
            :type="campo.tipo"
            v-model="form[campo.campo]"
            class="w-full border rounded-lg p-2"
            :disabled="campo.campo === 'troco'"
          />
        </div>

        <button
          @click="finalizarVenda"
          class="mt-3 w-full bg-gradient-to-r from-green-500 to-green-700 hover:from-green-600 hover:to-green-800 text-white py-3 rounded-xl font-bold shadow-lg transition-all duration-300 flex items-center justify-center gap-2"
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

