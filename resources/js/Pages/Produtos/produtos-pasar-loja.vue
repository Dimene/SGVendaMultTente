<script setup>
import { ref, watch, computed, onMounted } from "vue";
import { AgGridVue } from "ag-grid-vue3";
import {
    ModuleRegistry,
    AllCommunityModule
} from "ag-grid-community";
import axios from 'axios';
import Swal from "sweetalert2";

ModuleRegistry.registerModules([AllCommunityModule]);

// ============ PROPS ============
const props = defineProps({
    produtosdetalhes: { type: Array, default: () => [] },
    lojas: { type: Array, default: () => [] }
});

// ============ CÓPIA LOCAL DOS PRODUTOS ============
const produtosData = ref([]);
watch(() => props.produtosdetalhes, (newVal) => {
    produtosData.value = newVal.map(p => ({ ...p }));
}, { immediate: true });

// ============ ESTADO DO PAINEL ============
const selectedProduct = ref(null);
const activeTab = ref('detalhes');
const isPanelOpen = ref(false);

// ============ CARRINHO E LOJA ============
const produtosAdicionados = ref([]);
const lojaSelecionada = ref(null);

// ============ DADOS PARA IMPRESSÃO ============
const comprovativoData = ref({
    loja: null,          // objeto da loja
    itens: [],
    total: 0,
    data: '',
    hora: ''
});

// ============ FUNÇÕES DO PAINEL ============
const selecionarProduto = (data) => {
    selectedProduct.value = data;
    activeTab.value = 'detalhes';
    isPanelOpen.value = true;
};

const fecharPainel = () => {
    isPanelOpen.value = false;
    setTimeout(() => { selectedProduct.value = null; }, 300);
};

// ============ FUNÇÕES DO CARRINHO ============
const adicionarAoCarrinho = (produto) => {
    if (!produto) return;
    const produtoLocal = produtosData.value.find(p => p.id === produto.id);
    if (!produtoLocal) return;
    const estoqueAtual = produtoLocal.estoque || 0;
    if (estoqueAtual <= 0) {
        Swal.fire('Sem estoque', 'Produto sem estoque disponível!', 'warning');
        return;
    }
    const existe = produtosAdicionados.value.find(p => p.id === produto.id);
    if (existe) {
        if (estoqueAtual >= 1) {
            existe.quantidade += 1;
            produtoLocal.estoque -= 1;
        } else {
            Swal.fire('Estoque insuficiente', 'Não há estoque suficiente.', 'error');
        }
    } else {
        produtosAdicionados.value.push({
            ...produto,
            quantidade: 1,
            adicionado_em: new Date().toISOString()
        });
        produtoLocal.estoque -= 1;
    }
    activeTab.value = 'adicionados';
    isPanelOpen.value = true;
};

const removerDoCarrinho = (produtoId) => {
    const produtoCarrinho = produtosAdicionados.value.find(p => p.id === produtoId);
    if (!produtoCarrinho) return;
    const produtoLocal = produtosData.value.find(p => p.id === produtoId);
    if (produtoLocal) {
        produtoLocal.estoque = (produtoLocal.estoque || 0) + (produtoCarrinho.quantidade || 1);
    }
    produtosAdicionados.value = produtosAdicionados.value.filter(p => p.id !== produtoId);
};

const alterarQuantidade = (produtoId, incremento) => {
    const produtoCarrinho = produtosAdicionados.value.find(p => p.id === produtoId);
    if (!produtoCarrinho) return;
    const produtoLocal = produtosData.value.find(p => p.id === produtoId);
    if (!produtoLocal) return;
    const novaQuantidade = (produtoCarrinho.quantidade || 1) + incremento;
    if (novaQuantidade > 0) {
        if (incremento > 0 && (produtoLocal.estoque || 0) < incremento) {
            Swal.fire('Estoque insuficiente', 'Não é possível aumentar a quantidade.', 'warning');
            return;
        }
        produtoCarrinho.quantidade = novaQuantidade;
        produtoLocal.estoque = (produtoLocal.estoque || 0) - incremento;
    } else {
        removerDoCarrinho(produtoId);
    }
};

// ============ TOTAIS ============
const totalCarrinho = computed(() => {
    return produtosAdicionados.value.reduce((total, p) => {
        return total + (Number(p.preco_venda || 0) * (p.quantidade || 1));
    }, 0);
});

const totalItens = computed(() => {
    return produtosAdicionados.value.reduce((total, p) => {
        return total + (p.quantidade || 1);
    }, 0);
});

// ============ PREPARAR DADOS PARA IMPRESSÃO ============
const prepararComprovativo = (responseve) => {
  const  idloja=responseve.dadosvindo?.loja_id;

     console.log(responseve.dadosvindo.itens);
    const loja = props.lojas.find(l => l.id ===idloja);
    comprovativoData.value = {
        loja: loja || { Desc: 'Loja não encontrada' },
        itens:  responseve.dadosvindo.itens.map(p => ({
            nome: p.nome || 'Produto',
            quantidade: p.Quantidade || 1,
            preco_unitario: Number(p.precoVenda || 0),
            subtotal: Number(p.precoVenda || 0) * (p.Quantidade || 1)
        })),
        total: totalCarrinho.value,
        data: new Date().toLocaleDateString('pt-MZ'),
        hora: new Date().toLocaleTimeString('pt-MZ')
    };
};

// ============ IMPRIMIR COMPROVATIVO ============
const imprimirComprovativo = (responseve) => {
    // Prepara os dados (caso não tenha sido feito ainda)
    if (comprovativoData.value.itens.length === 0) {
        prepararComprovativo(responseve);
    }
    // Exibe a div de impressão e chama a impressão
    const printArea = document.getElementById('print-area');
    if (printArea) {
        printArea.style.display = 'block';
        window.print();
        // Após a impressão (ou cancelamento), ocultamos novamente
        setTimeout(() => {
            printArea.style.display = 'none';
        }, 1000);
    } else {
        Swal.fire('Erro', 'Não foi possível encontrar a área de impressão.', 'error');
    }
};

// ============ FINALIZAR COMPRA ============
const finalizarCompra = async () => {
    if (produtosAdicionados.value.length === 0) {
        Swal.fire('Carrinho vazio', 'Adicione produtos antes de finalizar.', 'warning');
        return;
    }

    if (!lojaSelecionada.value) {
        Swal.fire('Loja não selecionada', 'Selecione uma loja antes de finalizar.', 'warning');
        return;
    }

    const payload = {
        loja_id: lojaSelecionada.value,
        itens: produtosAdicionados.value.map(p => ({
            produto_id: p.id,
            quantidade: p.quantidade || 1,
            preco_unitario: p.preco_venda || 0,
        })),
        total: totalCarrinho.value,
        data_compra: new Date().toISOString(),
    };

    try {
    const responsive= await axios.post('/vendas/adicionar/lojas', payload, {
            headers: { 'Content-Type': 'application/json' }
        });

        // Guarda os dados para impressão antes de limpar o carrinho
        prepararComprovativo(responsive.data);

        // Limpa o carrinho e a seleção de loja
        produtosAdicionados.value = [];
        lojaSelecionada.value = null;

        // Mostra o SweetAlert com opção de imprimir

        if(responsive.data.success){
        Swal.fire({
            icon: 'success',
            title: 'venda finalizada!',
            text: 'Produtos transferidos com sucesso.',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-print"></i> Imprimir comprovativo',
            cancelButtonText: 'Fechar',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                imprimirComprovativo();
            }
        });


        fecharPainel();
    }
    } catch (error) {
        console.error('Erro ao finalizar compra:', error);
        Swal.fire('Erro', 'Ocorreu um erro ao finalizar a compra. Tente novamente.', 'error');
    }
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

// ============ COLUNAS DA TABELA ============
const colunastabela = [
    {
        headerName: "Nome",
        sortable: true,
        filter: true,
        width: 150,
        valueGetter: (params) => params.node.data?.produto?.nome ?? " "
    },
    {
        headerName: "Categoria",
        sortable: true,
        filter: true,
        width: 130,
        valueGetter: (params) => params.node.data?.produto?.categoria?.nome ?? " "
    },
    {
        headerName: "Qtd",
        sortable: true,
        filter: true,
        width: 80,
        valueGetter: (params) => params.node.data?.estoque ?? " "
    },
    {
        headerName: "Pr. Venda",
        sortable: true,
        filter: true,
        width: 120,
        valueGetter: (params) => params.node.data?.preco_venda
            ? `MT ${Number(params.node.data.preco_venda).toFixed(2)}`
            : " "
    },
    {
        headerName: "Ações",
        sortable: false,
        filter: false,
        width: 140,
        cellRenderer: () => `
            <div class="flex gap-1 justify-center">
                <button class="btn-adicionar px-2 py-1 bg-blue-500 text-white rounded text-xs hover:bg-blue-600 transition-colors">
                    <i class="fas fa-shopping-bag"></i>
                </button>
                <button class="btn-visualizar px-2 py-1 bg-green-500 text-white rounded text-xs hover:bg-green-600 transition-colors">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        `,
        onCellClicked: (params) => {
            const button = params.event.target.closest("button");
            if (!button) return;
            if (button.classList.contains("btn-adicionar")) adicionarAoCarrinho(params.data);
            if (button.classList.contains("btn-visualizar")) selecionarProduto(params.data);
        }
    }
];

const onGridReady = () => {};
const searchText = ref("");

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
</script>

<template>
    <div class="h-full">
        <!-- Título e cabeçalho -->
        <div class="mb-4 flex items-center justify-between flex-wrap gap-2">
            <button
                type="button"
                @click="voltar"
                class="px-4 py-2 bg-gray-500 text-white rounded-lg"
            >
                ← Voltar
            </button>

            <label class="text-lg font-semibold text-gray-800 dark:text-white">
                📋 Lista de produtos
            </label>

            <div class="flex items-center gap-3">
                <span
                    v-if="totalItens > 0"
                    class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full text-sm font-medium flex items-center gap-2"
                >
                    🛒 {{ totalItens }} itens
                    <span class="text-xs">({{ formatarMoeda(totalCarrinho) }})</span>
                </span>
                <span class="text-sm text-gray-500">
                    Total: {{ produtosData.length }} produtos
                </span>
            </div>
        </div>

        <!-- LAYOUT PRINCIPAL -->
        <div class="flex gap-6 h-[calc(100vh-200px)]">
            <!-- COLUNA 1: TABELA -->
            <div class="flex-1 min-w-0">
                <input
                    v-model="searchText"
                    type="text"
                    placeholder="Pesquisar produto..."
                    class="w-full border rounded-lg px-4 py-2 mb-4"
                />

                <AgGridVue
                    :rowData="produtosData"
                    :columnDefs="colunastabela"
                    :pagination="true"
                    :quickFilterText="searchText"
                    :paginationPageSize="15"
                    class="ag-theme-alpine h-full w-full"
                    @grid-ready="onGridReady"
                    :defaultColDef="{
                        resizable: true,
                        cellStyle: {
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center'
                        }
                    }"
                />
            </div>

            <!-- COLUNA 2: PAINEL -->
            <div class="w-[40%] min-w-[350px] max-w-[500px] flex-shrink-0">
                <!-- Estado vazio -->
                <div
                    v-if="!isPanelOpen"
                    class="h-full flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-800/50 rounded-2xl border-2 border-dashed border-gray-300 dark:border-gray-600 p-8 text-center"
                >
                    <div class="text-6xl mb-4">👈</div>
                    <h4 class="text-lg font-semibold text-gray-800 dark:text-white">
                        Selecione um produto
                    </h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 max-w-xs">
                        Clique em 👁️ Visualizar para ver os detalhes ou
                        🛒 Adicionar para colocar no carrinho
                    </p>
                </div>

                <!-- Painel aberto -->
                <div v-else class="h-full bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 flex flex-col overflow-hidden">
                    <!-- Cabeçalho -->
                    <div class="flex-shrink-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                                <span class="text-lg">{{ activeTab === 'detalhes' ? '📦' : '🛒' }}</span>
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-sm font-semibold text-gray-800 dark:text-white truncate">
                                    {{ activeTab === 'detalhes' ? selectedProduct?.produto?.nome || 'Detalhes' : 'Carrinho de Compras' }}
                                </h4>
                                <p class="text-xs text-gray-500 truncate">
                                    {{ activeTab === 'detalhes' ? `Código: #${selectedProduct?.id || 'N/A'}` : `${totalItens} itens adicionados` }}
                                </p>
                            </div>
                        </div>
                        <button
                            @click="fecharPainel"
                            class="w-7 h-7 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center justify-center transition-colors flex-shrink-0"
                        >
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Abas -->
                    <div class="flex-shrink-0 border-b border-gray-200 dark:border-gray-700 px-4">
                        <div class="flex gap-1">
                            <button
                                @click="activeTab = 'detalhes'"
                                class="py-2 px-3 text-xs font-medium transition-colors relative rounded-t-lg"
                                :class="activeTab === 'detalhes'
                                    ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                            >
                                📄 Detalhes
                            </button>
                            <button
                                @click="activeTab = 'adicionados'"
                                class="py-2 px-3 text-xs font-medium transition-colors relative rounded-t-lg flex items-center gap-1.5"
                                :class="activeTab === 'adicionados'
                                    ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                            >
                                🛒 Adicionados
                                <span
                                    v-if="produtosAdicionados.length > 0"
                                    class="px-1.5 py-0.5 text-[10px] rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400"
                                >
                                    {{ produtosAdicionados.length }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- CONTEÚDO: ABA DETALHES -->
                    <div v-show="activeTab === 'detalhes'" class="flex-1 overflow-y-auto p-4 space-y-4">
                        <div v-if="selectedProduct" class="space-y-4">
                            <!-- Cards de info (mantido igual ao original) -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                    <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nome</label>
                                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white truncate">{{ selectedProduct?.produto?.nome || 'N/A' }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                    <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Categoria</label>
                                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white">{{ selectedProduct?.produto?.categoria?.nome || 'N/A' }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                    <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Grupo</label>
                                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white">{{ selectedProduct?.produto?.grupo?.nome || 'N/A' }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                    <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID Produto</label>
                                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white">#{{ selectedProduct?.produto_id || 'N/A' }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                    <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Armazém</label>
                                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white">#{{ selectedProduct?.Armazem_id || 'N/A' }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                    <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estoque</label>
                                    <p class="mt-1 text-sm font-semibold" :class="getStatusBadgeColor(selectedProduct?.estoque || 0)">
                                        {{ selectedProduct?.estoque || 0 }} unidades
                                    </p>
                                </div>
                            </div>

                            <!-- Preços -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                    <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Preço Compra</label>
                                    <p class="mt-1 text-sm font-semibold text-orange-600 dark:text-orange-400">{{ formatarMoeda(selectedProduct?.preco_compra) }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                    <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Preço Venda</label>
                                    <p class="mt-1 text-sm font-semibold text-green-600 dark:text-green-400">{{ formatarMoeda(selectedProduct?.preco_venda) }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                    <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">IVA</label>
                                    <p class="mt-1 text-sm font-semibold text-purple-600 dark:text-purple-400">{{ selectedProduct?.iva || 0 }}%</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                    <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Desconto</label>
                                    <p class="mt-1 text-sm font-semibold text-red-600 dark:text-red-400">{{ selectedProduct?.desconto || 0 }}%</p>
                                </div>
                            </div>

                            <!-- Margem de lucro -->
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Margem de Lucro</label>
                                <div class="mt-1 flex items-center gap-3">
                                    <span class="text-sm font-bold text-purple-600 dark:text-purple-400">
                                        {{ selectedProduct?.preco_venda && selectedProduct?.preco_compra
                                            ? ((selectedProduct.preco_venda - selectedProduct.preco_compra) / selectedProduct.preco_compra * 100).toFixed(1)
                                            : 0 }}%
                                    </span>
                                    <div class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-green-500 to-purple-500 rounded-full transition-all duration-500"
                                             :style="{ width: `${selectedProduct?.preco_venda && selectedProduct?.preco_compra ? Math.min(((selectedProduct.preco_venda - selectedProduct.preco_compra) / selectedProduct.preco_compra * 100), 100) : 0}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Atributos -->
                            <div v-if="Object.keys(extrairAtributos(selectedProduct)).length > 0" class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center gap-2">🔧 Especificações</label>
                                <div class="mt-2 grid grid-cols-2 gap-2">
                                    <div v-for="atributo in atributosParaLista(selectedProduct)" :key="atributo.label"
                                         class="flex items-center gap-2 p-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                                        <span class="text-base">{{ atributo.icon }}</span>
                                        <div class="min-w-0">
                                            <p class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase">{{ atributo.label }}</p>
                                            <p class="text-xs font-semibold text-gray-800 dark:text-white truncate">{{ atributo.value }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Fotos -->
                            <div v-if="obterFotos(selectedProduct).length > 0" class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                <label class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center gap-2">📸 Fotos</label>
                                <div class="mt-2 flex gap-2 overflow-x-auto pb-2">
                                    <div v-for="(foto, index) in obterFotos(selectedProduct)" :key="index"
                                         class="w-20 h-20 flex-shrink-0 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-700">
                                        <img :src="`/storage/${foto}`" :alt="`Foto ${index + 1}`"
                                             class="w-full h-full object-cover"
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

                            <button @click="adicionarAoCarrinho(selectedProduct)"
                                    class="w-full py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors flex items-center justify-center gap-2 text-sm font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                Adicionar ao Carrinho
                            </button>
                        </div>
                    </div>

                    <!-- CONTEÚDO: ABA ADICIONADOS -->
                    <div v-show="activeTab === 'adicionados'" class="flex-1 overflow-y-auto p-4">
                        <!-- Seletor de lojas -->
                        <div class="mb-4">
                            <label for="loja" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Selecione a Loja
                            </label>
                            <select
                                id="loja"
                                v-model="lojaSelecionada"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option value="" disabled>Escolha uma loja</option>
                                <option
                                    v-for="loja in lojas"
                                    :key="loja.id"
                                    :value="loja.id"
                                >
                                    {{ loja.Desc }}
                                </option>
                            </select>
                        </div>

                        <!-- Lista do carrinho -->
                        <div v-if="produtosAdicionados.length === 0" class="h-full flex flex-col items-center justify-center text-center">
                            <div class="text-5xl mb-3">🛒</div>
                            <h5 class="text-sm font-semibold text-gray-800 dark:text-white">Carrinho vazio</h5>
                            <p class="text-xs text-gray-500 mt-1">Adicione produtos clicando em 🛒</p>
                        </div>

                        <div v-else class="space-y-3">
                            <div v-for="produto in produtosAdicionados" :key="produto.id"
                                 class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg">📦</span>
                                            <div class="min-w-0">
                                                <h6 class="text-sm font-medium text-gray-800 dark:text-white truncate">
                                                    {{ produto?.produto?.nome || 'Produto' }}
                                                </h6>
                                                <p class="text-xs text-gray-500">{{ formatarMoeda(produto?.preco_venda) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center gap-0.5">
                                            <button @click="alterarQuantidade(produto.id, -1)"
                                                    class="w-6 h-6 rounded bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 flex items-center justify-center transition-colors text-xs font-bold">−</button>
                                            <span class="w-6 text-center text-sm font-semibold text-gray-800 dark:text-white">{{ produto.quantidade || 1 }}</span>
                                            <button @click="alterarQuantidade(produto.id, 1)"
                                                    class="w-6 h-6 rounded bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 flex items-center justify-center transition-colors text-xs font-bold">+</button>
                                        </div>
                                        <span class="text-sm font-semibold text-blue-600 dark:text-blue-400 min-w-[70px] text-right">
                                            {{ formatarMoeda(Number(produto?.preco_venda || 0) * (produto.quantidade || 1)) }}
                                        </span>
                                        <button @click="removerDoCarrinho(produto.id)"
                                                class="w-6 h-6 rounded bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50 flex items-center justify-center transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Totais e botões -->
                            <div class="sticky bottom-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 pt-3 mt-3">
                                <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                    <span class="text-sm font-bold text-gray-800 dark:text-white">Total</span>
                                    <span class="text-base font-bold text-blue-600 dark:text-blue-400">{{ formatarMoeda(totalCarrinho) }}</span>
                                </div>
                                <div class="flex gap-2 mt-3">
                                    <button @click="produtosAdicionados = []"
                                            class="flex-1 px-3 py-2 rounded-lg border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-sm">
                                        Limpar
                                    </button>
                                    <button @click="finalizarCompra"
                                            class="flex-1 px-3 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors flex items-center justify-center gap-2 text-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        Finalizar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== ÁREA DE IMPRESSÃO (COMPROVATIVO) ========== -->
        <div id="print-area" style="display: none;">
            <div class="comprovativo-wrapper">
                <div class="comprovativo">
                    <div class="header">
                        <h1>📄 Comprovativo de Venda</h1>
                        <p><strong>Loja:</strong> {{ comprovativoData.loja?.Desc || 'N/A' }}</p>
                        <p><strong>Data:</strong> {{ comprovativoData.data }} &nbsp;|&nbsp; <strong>Hora:</strong> {{ comprovativoData.hora }}</p>
                    </div>

                    <table class="tabela-itens">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Produto</th>
                                <th>Qtd</th>
                                <th>Preço Unit.</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in comprovativoData.itens" :key="index">
                                <td>{{ index + 1 }}</td>
                                <td>{{ item.nome }}</td>
                                <td>{{ item.quantidade }}</td>
                                <td>{{ formatarMoeda(item.preco_unitario) }}</td>
                                <td>{{ formatarMoeda(item.subtotal) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-align: right; font-weight: bold;">Total</td>
                                <td style="font-weight: bold; font-size: 1.2em;">{{ formatarMoeda(comprovativoData.total) }}</td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="footer">
                        <p>Obrigado pela preferência!</p>
                        <p class="small">Este comprovativo é gerado automaticamente.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Estilos existentes mantidos... */
@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.2); }
}
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
.overflow-y-auto::-webkit-scrollbar { width: 4px; }
.overflow-y-auto::-webkit-scrollbar-track { background: transparent; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
.dark .overflow-y-auto::-webkit-scrollbar-thumb { background: #475569; }
.dark .overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #64748b; }
.transition-all { transition-property: all; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 300ms; }
.ag-theme-alpine { --ag-header-height: 35px; --ag-row-height: 38px; }
.overflow-x-auto { scrollbar-width: thin; }
.overflow-x-auto::-webkit-scrollbar { height: 4px; }
.overflow-x-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px; }

/* ======= ESTILOS PARA IMPRESSÃO ======= */
@media print {
    /* Esconde tudo o que não é a área de impressão */
    body * {
        visibility: hidden;
    }
    #print-area, #print-area * {
        visibility: visible;
    }
    #print-area {
        display: block !important;
        position: fixed;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: white;
        z-index: 9999;
        overflow: auto;
        padding: 20px;
    }
    /* Remove margens extras */
    .comprovativo-wrapper {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: 100vh;
    }
    .comprovativo {
        max-width: 800px;
        width: 100%;
        background: white;
        padding: 30px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-family: Arial, Helvetica, sans-serif;
        color: #333;
    }
    .comprovativo .header {
        text-align: center;
        border-bottom: 2px solid #333;
        padding-bottom: 15px;
        margin-bottom: 20px;
    }
    .comprovativo .header h1 {
        margin: 0 0 10px 0;
        font-size: 24px;
        color: #1a56db;
    }
    .comprovativo .header p {
        margin: 5px 0;
        font-size: 14px;
    }
    .tabela-itens {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        font-size: 14px;
    }
    .tabela-itens th,
    .tabela-itens td {
        border: 1px solid #ccc;
        padding: 8px 10px;
        text-align: left;
    }
    .tabela-itens th {
        background: #f0f0f0;
        font-weight: bold;
    }
    .tabela-itens tfoot td {
        border-top: 2px solid #333;
        padding: 12px 10px;
    }
    .comprovativo .footer {
        margin-top: 30px;
        text-align: center;
        border-top: 1px solid #ddd;
        padding-top: 15px;
        font-size: 13px;
        color: #666;
    }
    .comprovativo .footer .small {
        font-size: 11px;
        color: #999;
    }
}
</style>
