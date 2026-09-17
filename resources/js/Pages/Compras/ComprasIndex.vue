<template>
    <AuthenticatedLayout>
        <div class="page-shell">
            <header class="page-header">
                <div>
                    <p class="eyebrow">Gestão de compras</p>
                    <h1>Compras registadas</h1>
                    <p class="page-description">Acompanhe pedidos, fornecedores e o stock disponível.</p>
                </div>

                <div class="header-count">
                    <strong>{{ rowData.length }}</strong>
                    <span>{{ rowData.length === 1 ? 'compra' : 'compras' }}</span>
                </div>
            </header>

            <section class="summary-grid" aria-label="Resumo das compras">
                <article class="summary-card">
                    <span class="summary-label">Stock em armazém</span>
                    <strong>{{ formatNumber(totalArmazem) }}</strong>
                    <span class="summary-detail">unidades disponíveis</span>
                </article>
                <article class="summary-card summary-card--accent">
                    <span class="summary-label">Stock nas lojas</span>
                    <strong>{{ formatNumber(totalLojas) }}</strong>
                    <span class="summary-detail">unidades distribuídas</span>
                </article>
                <article class="summary-card">
                    <span class="summary-label">Valor em stock</span>
                    <strong>{{ formatCurrency(valorEmStock) }}</strong>
                    <span class="summary-detail">preço de compra</span>
                </article>
            </section>

            <section class="table-panel">
                <div class="table-toolbar">
                    <div>
                        <h2>Lista de pedidos</h2>
                        <p>Pesquise e ordene os registos para encontrar uma compra rapidamente.</p>
                    </div>
                    <label class="search-box">
                        <span class="search-icon" aria-hidden="true">⌕</span>
                        <input
                            v-model="searchText"
                            type="search"
                            placeholder="Pesquisar pedido, fornecedor ou status"
                            aria-label="Pesquisar compras"
                        />
                    </label>
                </div>

                <div v-if="rowData.length" class="grid-wrapper">
                    <ag-grid-vue
                        :rowData="rowData"
                        :columnDefs="colDefs"
                        :defaultColDef="defaultColDef"
                        class="ag-theme-alpine purchases-grid"
                        :quickFilterText="searchText"
                        :pagination="true"
                        :paginationPageSize="15"
                        :suppressCellFocus="true"
                        @grid-ready="onGridReady"
                    />
                </div>
                <div v-else class="empty-state">
                    <span class="empty-icon" aria-hidden="true">▱</span>
                    <h3>Nenhuma compra encontrada</h3>
                    <p>Os pedidos registados aparecerão aqui.</p>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import { computed, ref, watch } from 'vue'
import { AgGridVue } from 'ag-grid-vue3'
import { AllCommunityModule, ModuleRegistry } from 'ag-grid-community'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

ModuleRegistry.registerModules([AllCommunityModule])

export default {
    name: 'ComprasTable',
    components: { AgGridVue, AuthenticatedLayout },
    props: {
        compras: {
            type: Array,
            default: () => []
        }
    },
    setup(props) {
        const searchText = ref('')
        const gridApi = ref(null)
        const rowData = ref([])

        watch(() => props.compras, (compras) => {
            rowData.value = Array.isArray(compras) ? compras : []
        }, { immediate: true })

        const formatNumber = (value) => new Intl.NumberFormat('pt-MZ').format(Number(value || 0))
        const formatCurrency = (value) => new Intl.NumberFormat('pt-MZ', {
            style: 'currency',
            currency: 'MZN',
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }).format(Number(value || 0))
        const formatDate = (value) => value
            ? new Date(value).toLocaleDateString('pt-PT')
            : '-'
        const getStatusText = (status) => ({
            PENDENTE: 'Pendente',
            APROVADO: 'Aprovado',
            ENTREGUE: 'Entregue',
            CANCELADO: 'Cancelado'
        }[status] || status || '-')

        const totalArmazem = computed(() => rowData.value.reduce(
            (total, compra) => total + Number(compra.quantidade || 0), 0
        ))
        const totalLojas = computed(() => rowData.value.reduce(
            (total, compra) => total + Number(compra.loja_quantidade || 0), 0
        ))
        const valorEmStock = computed(() => rowData.value.reduce(
            (total, compra) => total + Number(compra.valor_estoque_total || 0), 0
        ))

        const defaultColDef = {
            sortable: true,
            filter: true,
            resizable: true,
            flex: 1,
            minWidth: 110
        }

        const abrirCompra = (numeroPedido) => {
            if (!numeroPedido) return

            router.visit(`/compras/create?pedido=${encodeURIComponent(numeroPedido)}`)
        }

        const colDefs = [
            {
                field: 'numero_pedido',
                headerName: 'Pedido',
                minWidth: 145,
                cellStyle: { fontWeight: '700', color: '#17324d' }
            },
            {
                headerName: 'Fornecedor',
                minWidth: 180,
                valueGetter: ({ data }) => data.fornecedor?.nome || data.fornecedor_nome || '-'
            },
            {
                field: 'status',
                headerName: 'Status',
                minWidth: 130,
                cellRenderer: ({ value }) => `<span class="status status--${String(value || '').toLowerCase()}">${getStatusText(value)}</span>`
            },
            {
                field: 'quantidade',
                headerName: 'Armazém',
                type: 'numericColumn',
                valueFormatter: ({ value }) => formatNumber(value)
            },
            {
                field: 'loja_quantidade',
                headerName: 'Lojas',
                type: 'numericColumn',
                valueFormatter: ({ value }) => formatNumber(value)
            },
            {
                field: 'valor_estoque_total',
                headerName: 'Valor em stock',
                minWidth: 165,
                type: 'numericColumn',
                valueFormatter: ({ value }) => formatCurrency(value)
            },
            {
                field: 'data_compra',
                headerName: 'Data',
                minWidth: 120,
                valueFormatter: ({ value, data }) => formatDate(value || data.created_at)
            },
            {
                headerName: 'Ações',
                field: 'id',
                minWidth: 100,
                maxWidth: 110,
                sortable: false,
                filter: false,
                cellRenderer: ({ data }) => {
                    const button = document.createElement('button')
                    button.type = 'button'
                    button.className = 'view-button'
                    button.title = `Visualizar compra ${data.numero_pedido || ''}`
                    button.setAttribute('aria-label', `Visualizar compra ${data.numero_pedido || ''}`)
                    button.innerHTML = '<i class="fas fa-eye" aria-hidden="true"></i>'
                    button.addEventListener('click', () => abrirCompra(data.numero_pedido))
                    return button
                }
            }
        ]

        const onGridReady = ({ api }) => {
            gridApi.value = api
            api.sizeColumnsToFit()
        }

        return {
            rowData,
            colDefs,
            defaultColDef,
            searchText,
            totalArmazem,
            totalLojas,
            valorEmStock,
            formatNumber,
            formatCurrency,
            abrirCompra,
            onGridReady,
            gridApi
        }
    }
}
</script>

<style scoped>
@import 'ag-grid-community/styles/ag-grid.css';
@import 'ag-grid-community/styles/ag-theme-alpine.css';

.page-shell {
    min-height: calc(100vh - 7rem);
    padding: 2rem clamp(1rem, 4vw, 3.5rem);
    color: #17324d;
    background: #f4f7f8;
}

.page-header,
.table-toolbar {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}

.eyebrow,
.summary-label {
    margin: 0;
    color: #ad5d3b;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
}

h1,
h2,
h3,
p {
    margin-top: 0;
}

h1 {
    margin-bottom: .4rem;
    font-family: Georgia, serif;
    font-size: clamp(2rem, 4vw, 3.2rem);
    font-weight: 500;
    letter-spacing: 0;
}

.page-description,
.table-toolbar p,
.summary-detail,
.empty-state p {
    margin-bottom: 0;
    color: #6a7b88;
}

.header-count {
    display: grid;
    min-width: 6rem;
    padding: .8rem 1rem;
    border-left: 3px solid #d47a50;
    background: #fff;
}

.header-count strong {
    font-size: 1.6rem;
    line-height: 1;
}

.header-count span {
    margin-top: .3rem;
    color: #6a7b88;
    font-size: .8rem;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
    margin: 2rem 0;
}

.summary-card {
    padding: 1.25rem;
    border: 1px solid #dce5e8;
    border-radius: 6px;
    background: #fff;
}

.summary-card--accent {
    border-color: #d47a50;
    background: #fff8f3;
}

.summary-card strong {
    display: block;
    margin: .65rem 0 .2rem;
    font-size: 1.7rem;
    font-weight: 700;
}

.summary-detail {
    font-size: .8rem;
}

.table-panel {
    overflow: hidden;
    border: 1px solid #dce5e8;
    border-radius: 6px;
    background: #fff;
}

.table-toolbar {
    align-items: center;
    padding: 1.25rem 1.35rem;
    border-bottom: 1px solid #e6edef;
}

.table-toolbar h2 {
    margin-bottom: .25rem;
    font-size: 1.1rem;
}

.table-toolbar p {
    font-size: .85rem;
}

.search-box {
    display: flex;
    align-items: center;
    width: min(100%, 20rem);
    min-width: 18rem;
    border: 1px solid #cbd8dd;
    border-radius: 4px;
    background: #fbfcfc;
}

.search-icon {
    padding-left: .75rem;
    color: #ad5d3b;
    font-size: 1.4rem;
}

.search-box input {
    width: 100%;
    padding: .7rem .8rem;
    border: 0;
    outline: 0;
    background: transparent;
    color: #17324d;
}

.grid-wrapper {
    width: 100%;
}

.purchases-grid {
    width: 100%;
    height: 29rem;
    --ag-header-background-color: #17324d;
    --ag-header-foreground-color: #fff;
    --ag-header-font-weight: 700;
    --ag-row-hover-color: #fff5ee;
    --ag-border-color: #e2eaed;
    --ag-font-family: inherit;
}

:deep(.status) {
    display: inline-flex;
    align-items: center;
    padding: .25rem .55rem;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 700;
}

:deep(.status--pendente) {
    color: #97500f;
    background: #fff1d6;
}

:deep(.status--aprovado) {
    color: #1d5f8a;
    background: #dff2ff;
}

:deep(.status--entregue) {
    color: #286843;
    background: #e2f6e9;
}

:deep(.status--cancelado) {
    color: #a34040;
    background: #ffebeb;
}

:deep(.view-button) {
    display: inline-grid;
    width: 2rem;
    height: 2rem;
    place-items: center;
    border: 1px solid #cbd8dd;
    border-radius: 4px;
    color: #ad5d3b;
    background: #fff;
    cursor: pointer;
    transition: color .2s ease, background-color .2s ease;
}

:deep(.view-button:hover) {
    color: #fff;
    background: #ad5d3b;
}

.empty-state {
    padding: 4rem 1rem;
    text-align: center;
}

.empty-icon {
    display: block;
    margin-bottom: .75rem;
    color: #d47a50;
    font-size: 2.4rem;
}

.empty-state h3 {
    margin-bottom: .4rem;
}

@media (max-width: 760px) {
    .page-shell {
        padding: 1.25rem .75rem;
    }

    .page-header,
    .table-toolbar {
        display: block;
    }

    .header-count {
        display: inline-grid;
        margin-top: 1rem;
    }

    .summary-grid {
        grid-template-columns: 1fr;
        margin: 1.25rem 0;
    }

    .search-box {
        width: 100%;
        min-width: 0;
        margin-top: 1rem;
    }

    .purchases-grid {
        height: 32rem;
    }
}
</style>
