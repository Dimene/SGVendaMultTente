<template>
    <AuthenticatedLayout>
        <div>
            <!-- Barra de Pesquisa -->
            <div class="actions-container">
                <input
                    type="text"
                    v-model="searchText"
                    placeholder="Pesquisar por fornecedor, número pedido, status..."
                    class="search-input"
                />
            </div>

            <!-- Tabela AG Grid -->
            <ag-grid-vue
                :rowData="rowData"
                :columnDefs="colDefs"
                style="height: 500px"
                class="ag-theme-alpine"
                :quickFilterText="searchText"
                :pagination="true"
                :paginationPageSize="15"
                @grid-ready="onGridReady"
            >
            </ag-grid-vue>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import { ref, watch } from 'vue'
import { AgGridVue } from "ag-grid-vue3"
import { ModuleRegistry, AllCommunityModule } from 'ag-grid-community'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

// Registrar módulos do AG Grid
ModuleRegistry.registerModules([AllCommunityModule])

export default {
    name: "ComprasTable",
    components: {
        AgGridVue,
        AuthenticatedLayout
    },
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

        // Observar mudanças nos dados
        watch(() => props.compras, (newCompras) => {
            if (newCompras && newCompras.length > 0) {
                rowData.value = newCompras.map(item => ({
                    ...item,
                    id: item.id
                }))
                console.log('Compras carregadas:', rowData.value.length)
            } else {
                rowData.value = []
            }
        }, { immediate: true, deep: true })

        // Formatar moeda
        const formatCurrency = (value) => {
            if (!value && value !== 0) return '0 MT'
            return new Intl.NumberFormat('pt-MZ', {
                style: 'currency',
                currency: 'MZN',
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            }).format(value)
        }

        // Formatar data
        const formatDate = (date) => {
            if (!date) return '-'
            return new Date(date).toLocaleDateString('pt-PT')
        }

        // Mapear status para texto
        const getStatusText = (status) => {
            const statusMap = {
                'PENDENTE': '⏳ Pendente',
                'APROVADO': '✅ Aprovado',
                'ENTREGUE': '📦 Entregue',
                'CANCELADO': '❌ Cancelado'
            }
            return statusMap[status] || status || '-'
        }

        // Configuração das colunas
        const colDefs = ref([
            {
                field: "numero_pedido",
                headerName: "🔢 Nº Pedido",
                width: 150,
                sortable: true,
                filter: true,
                cellStyle: { fontWeight: '500', fontFamily: 'monospace' }
            },
            {
                field: "fornecedor_nome",
                headerName: "🏢 Fornecedor",
                width: 200,
                sortable: true,
                filter: true
            },
            {
                field: "status",
                headerName: "📌 Status",
                width: 140,
                sortable: true,
                filter: true,
                cellStyle: (params) => {
                    const styles = {
                        'PENDENTE': { color: '#ff9800', fontWeight: 'bold' },
                        'APROVADO': { color: '#2196f3', fontWeight: 'bold' },
                        'ENTREGUE': { color: '#4caf50', fontWeight: 'bold' },
                        'CANCELADO': { color: '#f44336', fontWeight: 'bold' }
                    }
                    return styles[params.value] || null
                },
                valueFormatter: (params) => getStatusText(params.value)
            },
            {
                field: "subtotal",
                headerName: "💰 Subtotal",
                width: 130,
                sortable: true,
                filter: true,
                cellStyle: { textAlign: 'right' },
                valueFormatter: (params) => formatCurrency(params.value)
            },
            {
                field: "total",
                headerName: "💵 Total",
                width: 130,
                sortable: true,
                filter: true,
                cellStyle: { textAlign: 'right', fontWeight: 'bold', color: '#4caf50' },
                valueFormatter: (params) => formatCurrency(params.value)
            },
            {
                field: "created_at",
                headerName: "📅 Data Criação",
                width: 120,
                sortable: true,
                filter: true,
                valueFormatter: (params) => formatDate(params.value)
            }
        ])

        const onGridReady = (params) => {
            gridApi.value = params.api
            params.api.sizeColumnsToFit()

            // Ajustar tamanho ao redimensionar a janela
            window.addEventListener('resize', () => {
                params.api.sizeColumnsToFit()
            })
        }

        return {
            rowData,
            colDefs,
            searchText,
            onGridReady
        }
    }
}
</script>

<style scoped>
@import 'ag-grid-community/styles/ag-grid.css';
@import 'ag-grid-community/styles/ag-theme-alpine.css';

.actions-container {
    margin-bottom: 20px;
    background: white;
    padding: 15px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.search-input {
    width: 100%;
    padding: 10px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Estilização adicional do AG Grid */
.ag-theme-alpine {
    --ag-header-background-color: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --ag-header-foreground-color: white;
    --ag-header-font-weight: 600;
    --ag-row-hover-color: rgba(102, 126, 234, 0.1);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
</style>
