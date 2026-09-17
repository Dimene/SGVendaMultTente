<script setup>
import { computed, onMounted, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Swal from 'sweetalert2'

const props = defineProps({
    fornecedores: {
        type: Array,
        default: () => []
    }
})

const hoje = new Date()
const dataFinal = ref(hoje.toISOString().slice(0, 10))
const dataInicial = ref(new Date(hoje.getFullYear(), hoje.getMonth(), 1).toISOString().slice(0, 10))
const fornecedorId = ref('')
const status = ref('')
const compras = ref([])
const carregando = ref(false)

const totais = computed(() => compras.value.reduce((total, compra) => ({
    compras: total.compras + 1,
    itens: total.itens + Number(compra.itens || 0),
    stock: total.stock + Number(compra.stock || 0),
    valorStock: total.valorStock + Number(compra.valor_stock || 0),
    despesas: total.despesas + Number(compra.despesas || 0),
    total: total.total + Number(compra.total || 0)
}), {
    compras: 0,
    itens: 0,
    stock: 0,
    valorStock: 0,
    despesas: 0,
    total: 0
}))

const formatCurrency = (value) => new Intl.NumberFormat('pt-MZ', {
    style: 'currency',
    currency: 'MZN',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
}).format(Number(value || 0))

const formatNumber = (value) => new Intl.NumberFormat('pt-MZ').format(Number(value || 0))
const formatDate = (value) => value ? new Date(`${value}T00:00:00`).toLocaleDateString('pt-PT') : '-'
const statusLabel = (value) => ({
    PENDENTE: 'Pendente',
    APROVADO: 'Aprovado',
    ENTREGUE: 'Entregue',
    CANCELADO: 'Cancelado'
}[value] || value || '-')

async function carregarRelatorio() {
    if (dataInicial.value && dataFinal.value && dataInicial.value > dataFinal.value) {
        await Swal.fire('Período inválido', 'A data inicial não pode ser maior que a data final.', 'warning')
        return
    }

    carregando.value = true
    try {
        const params = new URLSearchParams()
        if (dataInicial.value) params.set('data_inicial', dataInicial.value)
        if (dataFinal.value) params.set('data_final', dataFinal.value)
        if (fornecedorId.value) params.set('fornecedor_id', fornecedorId.value)
        if (status.value) params.set('status', status.value)

        const response = await axios.get(`/compras/relatorios/dados?${params.toString()}`)
        compras.value = Array.isArray(response.data) ? response.data : []
    } catch (error) {
        compras.value = []
        await Swal.fire('Erro', error.response?.data?.message || 'Não foi possível carregar o relatório.', 'error')
    } finally {
        carregando.value = false
    }
}

function limparFiltros() {
    fornecedorId.value = ''
    status.value = ''
    carregarRelatorio()
}

function imprimir() {
    window.print()
}

onMounted(() => {
    carregarRelatorio()
})
</script>

<template>
    <Head title="Relatório de Compras" />

    <AuthenticatedLayout>
        <div class="report-page">
            <header class="report-header">
                <div>
                    <p class="eyebrow">Gestão de compras</p>
                    <h1>Relatório de compras</h1>
                    <p class="subtitle">Consulte os pedidos, despesas e stock por período.</p>
                </div>
                <div class="header-actions no-print">
                    <button class="print-button" type="button" @click="imprimir">
                        <i class="fas fa-print" aria-hidden="true"></i>
                        Imprimir
                    </button>
                </div>
            </header>

            <section class="filters no-print">
                <div>
                    <label for="data-inicial">Data inicial</label>
                    <input id="data-inicial" v-model="dataInicial" type="date" />
                </div>
                <div>
                    <label for="data-final">Data final</label>
                    <input id="data-final" v-model="dataFinal" type="date" />
                </div>
                <div>
                    <label for="fornecedor">Fornecedor</label>
                    <select id="fornecedor" v-model="fornecedorId">
                        <option value="">Todos os fornecedores</option>
                        <option v-for="fornecedor in props.fornecedores" :key="fornecedor.id" :value="fornecedor.id">
                            {{ fornecedor.nome }}
                        </option>
                    </select>
                </div>
                <div>
                    <label for="status">Status</label>
                    <select id="status" v-model="status">
                        <option value="">Todos os status</option>
                        <option value="PENDENTE">Pendente</option>
                        <option value="APROVADO">Aprovado</option>
                        <option value="ENTREGUE">Entregue</option>
                        <option value="CANCELADO">Cancelado</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="button" class="primary-button" :disabled="carregando" @click="carregarRelatorio">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        {{ carregando ? 'A carregar...' : 'Consultar' }}
                    </button>
                    <button type="button" class="secondary-button" @click="limparFiltros">Limpar</button>
                </div>
            </section>

            <section class="summary-grid">
                <article><span>Compras</span><strong>{{ formatNumber(totais.compras) }}</strong></article>
                <article><span>Itens em stock</span><strong>{{ formatNumber(totais.stock) }}</strong></article>
                <article><span>Valor do stock</span><strong>{{ formatCurrency(totais.valorStock) }}</strong></article>
                <article><span>Despesas</span><strong>{{ formatCurrency(totais.despesas) }}</strong></article>
                <article class="summary-total"><span>Total apurado</span><strong>{{ formatCurrency(totais.total) }}</strong></article>
            </section>

            <section class="report-table">
                <div class="table-heading">
                    <div>
                        <h2>Detalhes dos pedidos</h2>
                        <p>{{ formatNumber(totais.itens) }} itens encontrados no período selecionado.</p>
                    </div>
                    <span class="result-count">{{ compras.length }} registos</span>
                </div>

                <div v-if="carregando" class="empty-state">A carregar o relatório...</div>
                <div v-else-if="!compras.length" class="empty-state">
                    <i class="fas fa-file-invoice" aria-hidden="true"></i>
                    <strong>Nenhuma compra encontrada</strong>
                    <span>Ajuste os filtros e tente novamente.</span>
                </div>
                <div v-else class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Data</th>
                                <th>Fornecedor</th>
                                <th>Status</th>
                                <th class="number">Itens</th>
                                <th class="number">Stock</th>
                                <th class="number">Valor stock</th>
                                <th class="number">Despesas</th>
                                <th class="number">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="compra in compras" :key="compra.id">
                                <td class="order-number">{{ compra.numero_pedido || '-' }}</td>
                                <td>{{ formatDate(compra.data) }}</td>
                                <td>{{ compra.fornecedor }}</td>
                                <td><span :class="`status status--${String(compra.status || '').toLowerCase()}`">{{ statusLabel(compra.status) }}</span></td>
                                <td class="number">{{ formatNumber(compra.itens) }}</td>
                                <td class="number">{{ formatNumber(compra.stock) }}</td>
                                <td class="number">{{ formatCurrency(compra.valor_stock) }}</td>
                                <td class="number">{{ formatCurrency(compra.despesas) }}</td>
                                <td class="number total-cell">{{ formatCurrency(compra.total) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4">Totais</td>
                                <td class="number">{{ formatNumber(totais.itens) }}</td>
                                <td class="number">{{ formatNumber(totais.stock) }}</td>
                                <td class="number">{{ formatCurrency(totais.valorStock) }}</td>
                                <td class="number">{{ formatCurrency(totais.despesas) }}</td>
                                <td class="number">{{ formatCurrency(totais.total) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.report-page {
    min-height: calc(100vh - 7rem);
    padding: 2rem clamp(1rem, 4vw, 3.5rem);
    color: #17324d;
    background: #f4f7f8;
}

.report-header,
.table-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}

.header-actions {
    display: flex;
    gap: .6rem;
}

.eyebrow {
    margin: 0 0 .35rem;
    color: #ad5d3b;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
}

h1 {
    margin: 0 0 .4rem;
    font-family: Georgia, serif;
    font-size: clamp(2rem, 4vw, 3.2rem);
    font-weight: 500;
}

.subtitle,
.table-heading p {
    margin: 0;
    color: #6a7b88;
}

.print-button,
.primary-button,
.secondary-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    min-height: 2.6rem;
    padding: .6rem 1rem;
    border: 0;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 700;
}

.print-button,
.primary-button {
    color: #fff;
    background: #ad5d3b;
}

.print-button:hover,
.primary-button:hover {
    background: #914b30;
}

.secondary-button {
    color: #17324d;
    background: #e5edef;
}

.filters {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr)) auto;
    gap: .85rem;
    align-items: end;
    margin: 2rem 0 1rem;
    padding: 1.2rem;
    border: 1px solid #dce5e8;
    border-radius: 6px;
    background: #fff;
}

.filters label {
    display: block;
    margin-bottom: .4rem;
    color: #526675;
    font-size: .78rem;
    font-weight: 700;
}

.filters input,
.filters select {
    width: 100%;
    min-height: 2.55rem;
    padding: .55rem .65rem;
    border: 1px solid #cbd8dd;
    border-radius: 4px;
    background: #fbfcfc;
}

.filter-actions {
    display: flex;
    gap: .5rem;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: .85rem;
    margin-bottom: 1rem;
}

.summary-grid article {
    padding: 1rem;
    border: 1px solid #dce5e8;
    border-radius: 6px;
    background: #fff;
}

.summary-grid span {
    display: block;
    color: #6a7b88;
    font-size: .78rem;
}

.summary-grid strong {
    display: block;
    margin-top: .45rem;
    font-size: 1.25rem;
}

.summary-grid .summary-total {
    color: #fff;
    border-color: #17324d;
    background: #17324d;
}

.summary-total span {
    color: #c7d7df;
}

.report-table {
    overflow: hidden;
    border: 1px solid #dce5e8;
    border-radius: 6px;
    background: #fff;
}

.table-heading {
    align-items: center;
    padding: 1.2rem 1.35rem;
    border-bottom: 1px solid #e6edef;
}

.table-heading h2 {
    margin: 0 0 .25rem;
    font-size: 1.1rem;
}

.result-count {
    padding: .35rem .6rem;
    border-radius: 999px;
    color: #97500f;
    background: #fff1d6;
    font-size: .78rem;
    font-weight: 700;
}

.table-scroll {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 900px;
    border-collapse: collapse;
}

th,
td {
    padding: .8rem .9rem;
    border-bottom: 1px solid #e6edef;
    text-align: left;
    white-space: nowrap;
}

th {
    color: #6a7b88;
    background: #f7fafb;
    font-size: .72rem;
    letter-spacing: .04em;
    text-transform: uppercase;
}

tbody tr:hover {
    background: #fffaf6;
}

.number {
    text-align: right;
}

.order-number,
.total-cell {
    color: #17324d;
    font-weight: 700;
}

tfoot td {
    border-bottom: 0;
    background: #f7fafb;
    font-weight: 800;
}

.status {
    display: inline-flex;
    padding: .25rem .55rem;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 700;
}

.status--pendente { color: #97500f; background: #fff1d6; }
.status--aprovado { color: #1d5f8a; background: #dff2ff; }
.status--entregue { color: #286843; background: #e2f6e9; }
.status--cancelado { color: #a34040; background: #ffebeb; }

.empty-state {
    display: grid;
    justify-items: center;
    gap: .5rem;
    padding: 4rem 1rem;
    color: #6a7b88;
}

.empty-state i {
    color: #ad5d3b;
    font-size: 2rem;
}

.empty-state strong {
    color: #17324d;
}

:global(.dark) .report-page {
    color: #e8eef1;
    background: #17232d;
}

:global(.dark) .report-page .subtitle,
:global(.dark) .report-page .table-heading p,
:global(.dark) .report-page .summary-grid span,
:global(.dark) .report-page .empty-state {
    color: #aebdc5;
}

:global(.dark) .report-page .filters,
:global(.dark) .report-page .summary-grid article,
:global(.dark) .report-page .report-table {
    border-color: #344752;
    background: #22333e;
}

:global(.dark) .report-page .secondary-button,
:global(.dark) .report-page .filters label,
:global(.dark) .report-page .order-number,
:global(.dark) .report-page .total-cell,
:global(.dark) .report-page .empty-state strong,
:global(.dark) .report-page .table-heading h2 {
    color: #e8eef1;
}

:global(.dark) .report-page .filters input,
:global(.dark) .report-page .filters select {
    border-color: #425864;
    color: #e8eef1;
    background: #172a35;
}

:global(.dark) .report-page .table-heading,
:global(.dark) .report-page th,
:global(.dark) .report-page tfoot td {
    border-color: #344752;
    background: #1d2d37;
}

:global(.dark) .report-page th {
    color: #b9c8cf;
}

:global(.dark) .report-page td {
    border-color: #344752;
}

:global(.dark) .report-page tbody tr:hover {
    background: #2b404c;
}

@media (max-width: 1000px) {
    .filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .filter-actions {
        grid-column: span 2;
    }

    .summary-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 650px) {
    .report-page {
        padding: 1.25rem .75rem;
    }

    .report-header,
    .table-heading {
        display: block;
    }

    .print-button {
        width: 100%;
        margin-top: 1rem;
    }

    .header-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        margin-top: 1rem;
    }

    .header-actions .print-button {
        width: auto;
        margin-top: 0;
    }

    .filters,
    .summary-grid {
        grid-template-columns: 1fr;
    }

    .filter-actions {
        grid-column: auto;
    }
}

@media print {
    .report-page {
        padding: 0;
        background: #fff;
    }

    .no-print {
        display: none !important;
    }

    .report-table,
    .summary-grid article {
        border-color: #ccc;
        color: #17324d !important;
        background: #fff !important;
    }

    :global(.dark) .report-page .filters input,
    :global(.dark) .report-page .filters select,
    :global(.dark) .report-page th,
    :global(.dark) .report-page tfoot td,
    :global(.dark) .report-page tbody tr:hover {
        color: #17324d !important;
        background: #fff !important;
    }
}
</style>
