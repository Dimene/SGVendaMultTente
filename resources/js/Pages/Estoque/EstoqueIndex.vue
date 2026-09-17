<script setup>
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    inventario: {
        type: Array,
        default: () => []
    }
})

const pesquisa = ref('')
const filtroCategoria = ref('')
const categorias = computed(() => [...new Set(props.inventario.map(item => item.categoria))].sort())

const itensFiltrados = computed(() => props.inventario.filter((item) => {
    const termo = pesquisa.value.trim().toLowerCase()
    const correspondeTexto = !termo || [item.produto, item.categoria, item.loja_nome]
        .some(valor => String(valor || '').toLowerCase().includes(termo))
    return correspondeTexto && (!filtroCategoria.value || item.categoria === filtroCategoria.value)
}))

const totais = computed(() => itensFiltrados.value.reduce((total, item) => ({
    armazem: total.armazem + Number(item.armazem || 0),
    loja: total.loja + Number(item.loja || 0),
    quantidade: total.quantidade + Number(item.quantidade_total || 0),
    receita1: total.receita1 + Number(item.receita_venda1 || 0),
    iva1: total.iva1 + Number(item.iva_venda1 || 0),
    receita2: total.receita2 + Number(item.receita_venda2 || 0),
    iva2: total.iva2 + Number(item.iva_venda2 || 0)
}), { armazem: 0, loja: 0, quantidade: 0, receita1: 0, iva1: 0, receita2: 0, iva2: 0 }))

const formatNumber = value => new Intl.NumberFormat('pt-MZ').format(Number(value || 0))
const formatCurrency = value => new Intl.NumberFormat('pt-MZ', {
    style: 'currency',
    currency: 'MZN',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
}).format(Number(value || 0))

function limparFiltros() {
    pesquisa.value = ''
    filtroCategoria.value = ''
}

function imprimir() {
    window.print()
}
</script>

<template>
    <Head title="Inventário" />
    <AuthenticatedLayout>
        <div class="inventory-page">
            <header class="page-header">
                <div>
                    <p class="eyebrow">Armazém e lojas</p>
                    <h1>Inventário</h1>
                    <p class="subtitle">Consulte o stock disponível e o valor potencial de faturação.</p>
                </div>
                <button class="print-button no-print" type="button" @click="imprimir">
                    <i class="fas fa-print" aria-hidden="true"></i> Imprimir
                </button>
            </header>

            <section class="summary-grid" aria-label="Resumo do inventário">
                <article><span>Stock total</span><strong>{{ formatNumber(totais.quantidade) }}</strong><small>unidades disponíveis</small></article>
                <article><span>Armazém</span><strong>{{ formatNumber(totais.armazem) }}</strong><small>unidades em armazém</small></article>
                <article><span>Lojas</span><strong>{{ formatNumber(totais.loja) }}</strong><small>unidades distribuídas</small></article>
                <article class="summary-accent"><span>Receita no preço 1</span><strong>{{ formatCurrency(totais.receita1) }}</strong><small>IVA estimado: {{ formatCurrency(totais.iva1) }}</small></article>
                <article class="summary-dark"><span>Receita no preço 2</span><strong>{{ formatCurrency(totais.receita2) }}</strong><small>IVA estimado: {{ formatCurrency(totais.iva2) }}</small></article>
            </section>

            <section class="toolbar no-print">
                <label class="search-field">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <input v-model="pesquisa" type="search" placeholder="Pesquisar produto, categoria ou loja" />
                </label>
                <select v-model="filtroCategoria" aria-label="Filtrar por categoria">
                    <option value="">Todas as categorias</option>
                    <option v-for="categoria in categorias" :key="categoria" :value="categoria">{{ categoria }}</option>
                </select>
                <button class="secondary-button" type="button" @click="limparFiltros">Limpar</button>
                <span class="counter">{{ itensFiltrados.length }} produto(s)</span>
            </section>

            <section class="table-card">
                <div v-if="!itensFiltrados.length" class="empty-state">
                    <i class="fas fa-box-open" aria-hidden="true"></i>
                    <strong>Nenhum produto em stock</strong>
                    <span>Ajuste os filtros ou registe stock para visualizar o inventário.</span>
                </div>
                <div v-else class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Categoria</th>
                                <th class="number">Armazém</th>
                                <th class="number">Loja</th>
                                <th class="number">Total</th>
                                <th class="number">Preço 1</th>
                                <th class="number">Receita 1</th>
                                <th class="number">IVA 1</th>
                                <th class="number">Preço 2</th>
                                <th class="number">Receita 2</th>
                                <th class="number">IVA 2</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in itensFiltrados" :key="item.id">
                                <td><strong>{{ item.produto }}</strong><small v-if="item.loja_nome">{{ item.loja_nome }}</small></td>
                                <td>{{ item.categoria }}</td>
                                <td class="number">{{ formatNumber(item.armazem) }}</td>
                                <td class="number">{{ formatNumber(item.loja) }}</td>
                                <td class="number total-cell">{{ formatNumber(item.quantidade_total) }}</td>
                                <td class="number">{{ formatCurrency(item.preco_venda1) }}</td>
                                <td class="number revenue-cell">{{ formatCurrency(item.receita_venda1) }}</td>
                                <td class="number iva-cell">{{ formatCurrency(item.iva_venda1) }}<small>{{ item.iva_percentual }}%</small></td>
                                <td class="number">{{ formatCurrency(item.preco_venda2) }}</td>
                                <td class="number revenue-cell">{{ formatCurrency(item.receita_venda2) }}</td>
                                <td class="number iva-cell">{{ formatCurrency(item.iva_venda2) }}<small>{{ item.iva_percentual }}%</small></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2">Totais</td>
                                <td class="number">{{ formatNumber(totais.armazem) }}</td>
                                <td class="number">{{ formatNumber(totais.loja) }}</td>
                                <td class="number">{{ formatNumber(totais.quantidade) }}</td>
                                <td></td>
                                <td class="number">{{ formatCurrency(totais.receita1) }}</td>
                                <td class="number">{{ formatCurrency(totais.iva1) }}</td>
                                <td></td>
                                <td class="number">{{ formatCurrency(totais.receita2) }}</td>
                                <td class="number">{{ formatCurrency(totais.iva2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.inventory-page { min-height: calc(100vh - 7rem); padding: 2rem clamp(1rem, 4vw, 3.5rem); color: #17324d; background: #f4f7f8; }
.page-header, .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.eyebrow { margin: 0 0 .35rem; color: #ad5d3b; font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
h1 { margin: 0 0 .4rem; font: 500 clamp(2rem, 4vw, 3.2rem) Georgia, serif; } .subtitle { margin: 0; color: #6a7b88; }
.print-button, .secondary-button { display: inline-flex; align-items: center; gap: .5rem; min-height: 2.6rem; padding: .65rem 1rem; border: 0; border-radius: 4px; cursor: pointer; font-weight: 700; }
.print-button { color: #fff; background: #ad5d3b; } .print-button:hover { background: #914b30; } .secondary-button { color: #17324d; background: #e5edef; }
.summary-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .85rem; margin: 2rem 0 1rem; }
.summary-grid article { padding: 1rem; border: 1px solid #dce5e8; border-radius: 6px; background: #fff; } .summary-grid span, .summary-grid small { display: block; color: #6a7b88; font-size: .78rem; } .summary-grid strong { display: block; margin: .5rem 0 .2rem; font-size: 1.3rem; } .summary-accent { border-color: #d47a50 !important; background: #fff8f3 !important; } .summary-dark { color: #fff; border-color: #17324d !important; background: #17324d !important; } .summary-dark span, .summary-dark small { color: #c7d7df; }
.toolbar { margin-bottom: 1rem; padding: 1rem; border: 1px solid #dce5e8; border-radius: 6px; background: #fff; } .search-field { display: flex; flex: 1; align-items: center; gap: .6rem; color: #ad5d3b; } .search-field input { width: 100%; border: 0; outline: 0; background: transparent; } .toolbar select { min-height: 2.5rem; padding: .6rem; border: 1px solid #cbd8dd; border-radius: 4px; background: #fbfcfc; } .counter { color: #6a7b88; font-size: .85rem; white-space: nowrap; }
.table-card { overflow: hidden; border: 1px solid #dce5e8; border-radius: 6px; background: #fff; } .table-scroll { overflow-x: auto; } table { width: 100%; min-width: 1180px; border-collapse: collapse; } th, td { padding: .8rem .7rem; border-bottom: 1px solid #e6edef; text-align: left; white-space: nowrap; } th { color: #6a7b88; background: #f7fafb; font-size: .7rem; letter-spacing: .03em; text-transform: uppercase; } td small { display: block; margin-top: .2rem; color: #6a7b88; font-size: .72rem; } tbody tr:hover { background: #fffaf6; } .number { text-align: right; } .total-cell, .revenue-cell { color: #17324d; font-weight: 700; } .iva-cell { color: #ad5d3b; } tfoot td { border-bottom: 0; background: #f7fafb; font-weight: 800; }
.empty-state { display: grid; justify-items: center; gap: .5rem; padding: 4rem 1rem; color: #6a7b88; } .empty-state i { color: #ad5d3b; font-size: 2rem; }
:global(.dark) .inventory-page { color: #f3f4f6; background: #17232d; } :global(.dark) .summary-grid article, :global(.dark) .toolbar, :global(.dark) .table-card { border-color: #344752; background: #22333e; } :global(.dark) .summary-accent { background: #4c3024 !important; } :global(.dark) .toolbar select { color: #f3f4f6; border-color: #425864; background: #172a35; } :global(.dark) .table-card th, :global(.dark) tfoot td { color: #b9c8cf; border-color: #344752; background: #1d2d37; } :global(.dark) td { border-color: #344752; } :global(.dark) tbody tr:hover { background: #2b404c; } :global(.dark) .total-cell, :global(.dark) .revenue-cell { color: #e8eef1; } :global(.dark) .secondary-button { color: #e8eef1; background: #344752; }
@media (max-width: 900px) { .summary-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 650px) { .page-header, .toolbar { align-items: stretch; flex-direction: column; } .summary-grid { grid-template-columns: 1fr; } .print-button { justify-content: center; } .toolbar select, .toolbar .secondary-button { width: 100%; } }
@media print { .inventory-page { padding: 0; background: #fff; } .no-print { display: none !important; } .summary-grid article, .table-card { color: #17324d !important; border-color: #ccc !important; background: #fff !important; } }
</style>
