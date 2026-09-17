<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Fornecedor from './Fornecedor.vue'
import { Head } from '@inertiajs/vue3'
import { ref, computed, nextTick, reactive, watch, onMounted } from 'vue' // ← ADICIONADO onMounted
import ProdutoCreate from '../Produtos/ProdutoCreate.vue'
import DadosCompra from './DadosCompra.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import Swal from 'sweetalert2'

// ==================== PROPS ====================
const props = defineProps({
    compras: Array,
    initialPedido: {
        type: String,
        default: ''
    },
    categoria: Array,
    armazem: Array,
    fornecedor: Array,
    distritos: Array,
    provincia: Array,
    grupoItem: Array,
    produtos: Array,
    iva: Array,
    lucro: Array,
})

const errors = reactive({
   fornecedor: '',
})

const modelValue = ref(false)
const abaAtiva = ref('compra')
const resetarForm = ref(0)
const guadardad = ref(0)
const guardarProduto = ref(0)
const selecionarproduto = ref(0)
const compra_id = ref(0)
const totalDespesa = ref(0)
const totalCompra = ref(0)
const totalvenda = ref(0)
const totalvendaIva = ref(0)

const comprafeita = ref(0)
const fornecedorID = ref(0)
const dadosconpra = ref([])

function reset() {
    resetarForm.value++
}

async function CriadaComprar(id) {
    console.log("passado ao pai ", id)
    compra_id.value = Number(id)
    comprafeita.value = Number(id)
    await nextTick()
    guardarProduto.value++
}

async function selecionarCompra(id) {
    console.log("passado ao pai para selecao ", id)
    compra_id.value = Number(id)
    comprafeita.value = Number(id)
    await nextTick()
    selecionarproduto.value++
}

async function guadardados() {
    if (!fornecedorID.value || fornecedorID.value <= 0) {
        await Swal.fire({
            title: 'Fornecedor obrigatório',
            text: 'Por favor, selecione um fornecedor antes de continuar.',
            icon: 'warning',
            confirmButtonText: 'OK'
        })
        return
    }
    guadardad.value++
}

watch(fornecedorID, (novo) => {
    if (novo > 0) {
        errors.fornecedor = ''
    }
})

function abrirModal() {
    modelValue.value = true
    console.log('Abrir modal:', modelValue.value)
}

function mostrarErro(info) {
    errors.fornecedor = info
}

const gastoTotal = computed(() => {
    return Number(totalDespesa.value) + Number(totalCompra.value)
})

const lucroSemIVA = computed(() => {
    return Number(totalvenda.value) - gastoTotal.value
})

const valorIVA = computed(() => {
    return Number(totalvendaIva.value) - Number(totalvenda.value)
})

const receitaTotal = computed(() => {
    return Number(totalvendaIva.value)
})

const lucroComIVA = computed(() => {
    return receitaTotal.value - gastoTotal.value
})

const moeda = (valor) => {
    return new Intl.NumberFormat('pt-MZ', {
        style: 'currency',
        currency: 'MZN'
    }).format(Number(valor || 0))
}

watch(compra_id, (novo) => {
    getdadosdaconpra(novo)
})

async function getdadosdaconpra(compra_id) {
    try {
        const response = await axios.get(`/Produto/${compra_id}`)
        console.log("chegou ", response.data.dados)
        dadosconpra.value = response.data.dados
        comprafeita.value = compra_id
    } catch (error) {
        console.error(error)
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: 'Falha ao carregar os produtos.'
        })
    }
}

// Corrigido: Adicionei a função carregarDados que faltava
async function carregarDados() {
    isLoading.value = true
    try {
        // Sua lógica de carregamento aqui
        await new Promise(resolve => setTimeout(resolve, 1000)) // Simulação
    } catch (error) {
        console.error(error)
    } finally {
        isLoading.value = false
    }
}

const isLoading = ref(false)
const darkMode = ref(false)

function toggleDark() {
    darkMode.value = !darkMode.value
    document.documentElement.classList.toggle('dark', darkMode.value)
    localStorage.setItem('darkMode', darkMode.value)
}

// CORREÇÃO PRINCIPAL: onMounted agora está importado
onMounted(() => {
    const savedTheme = localStorage.getItem('darkMode')
    if (savedTheme === 'true') {
        darkMode.value = true
        document.documentElement.classList.add('dark')
    }
})
</script>
<template>
    <Head title="Compras" />

    <AuthenticatedLayout>
         <template #header>
            <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
                <div>
                    <h2 class="flex items-center gap-2 text-2xl font-bold text-gray-800 dark:text-white">
                        📊 Vendas Diarias
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                            <!-- {{ dataAtualizacao.toLocaleTimeString('pt-MZ') }} -->
                        </span>
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Dados das vendas Realizadas *:
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



<div class="flex items-start w-full gap-4 p-4 bg-white">
    <div class="flex-1">
        <select
            v-model="fornecedorID"
            :class="[
                'w-full rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all',
                errors.fornecedor ? 'border-red-500' : 'border-gray-300'
            ]"
        >
            <option :value="0">Selecione um fornecedor</option>

            <option
                v-for="value in fornecedor"
                :key="value.id"
                :value="value.id"
            >
                {{ value.nome }}
            </option>
        </select>

        <p
            v-if="errors.fornecedor"
            class="mt-1 text-sm text-red-500"
        >
            <i class="mr-1 fas fa-exclamation-circle"></i>
            {{ errors.fornecedor }}
        </p>
    </div>

    <PrimaryButton @click="abrirModal">
        Novo Fornecedor
    </PrimaryButton>
</div>

     <div class="flex gap-2 p-4 bg-white border-b">
        <button
            @click="abaAtiva='compra'"
            :class="[
                'px-4 py-2 rounded-t-lg',
                abaAtiva === 'compra'
                    ? 'bg-blue-600 text-white'
                    : 'bg-slate-300'
            ]"
        >
            Dados da compra
        </button>

        <button
            @click="abaAtiva='itens'"
            :class="[
                'px-4 py-2 rounded-t-lg',
                abaAtiva === 'itens'
                    ? 'bg-blue-600 text-white'
                    : 'bg-slate-300'
            ]"
        >
            Dados dos itens comprados
        </button>
    </div>

    <div  v-show="abaAtiva === 'compra'" class="p-4 bg-white">
    <DadosCompra  :resetarForm="resetarForm"   :guadardad="guadardad"
    :initialPedido="initialPedido"
    @compraCriada="CriadaComprar"
      @compraselecionada="selecionarCompra"

     v-model:fornecedorID="fornecedorID"
     @validarForm="mostrarErro"
v-model:totalDespesa="totalDespesa"
      ></DadosCompra>
    </div>



    <div  v-show="abaAtiva === 'itens' " class="p-2 bg-white">

    <ProdutoCreate
    :grupoItem="grupoItem"
    :resetarForm="resetarForm"
    :comprafeita="comprafeita"
    :compra_id="compra_id"
    :guardarProduto="guardarProduto"
    :selecionarproduto="selecionarproduto"
    :dadosconpra="dadosconpra"
    :armazem="armazem"

    v-model:totalCompra="totalCompra"
    v-model:totalvenda="totalvenda"
    v-model:totalvendaIva="totalvendaIva"

    :iva="iva"
    :lucro="lucro"
/>


    </div>


    <Fornecedor
  v-model="modelValue"
       :compras="compras"
    :categoria="categoria"


       :fornecedor="fornecedor"
       :distritos="distritos"
       :provincia="provincia"


     />






 <!-- Totais -->
                <div class="p-6 border-t border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
                    <div class="overflow-x-auto">
                        <table class="w-full overflow-hidden border-collapse shadow-sm rounded-xl">
                            <thead>
                                <tr class="text-white bg-gradient-to-r from-blue-600 to-indigo-600">
                                    <th class="p-4 font-semibold text-left">
                                        <i class="mr-2 fas fa-receipt"></i>Despesa
                                    </th>
                                    <th class="p-4 font-semibold text-left">
                                        <i class="mr-2 fas fa-shopping-bag"></i>Compra
                                    </th>
                                    <th class="p-4 font-semibold text-left">
                                        <i class="mr-2 fas fa-tag"></i>Venda
                                    </th>
                                    <th class="p-4 font-semibold text-left">
                                        <i class="mr-2 fas fa-percent"></i>Venda c/ IVA total
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="transition-colors duration-200 bg-white hover:bg-blue-50">
                                    <td class="p-4 font-medium text-gray-700">
                                    {{moeda(totalDespesa) }}



                                    </td>
                                    <td class="p-4 font-medium text-gray-700">
                                  {{ moeda(totalCompra) }}
                                    </td>
                                    <td class="p-4 font-medium text-green-600">

                                  {{ moeda(totalvenda) }}
                                    </td>
                                    <td class="p-4 font-medium text-blue-600">{{ moeda(totalvendaIva) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Resumo Financeiro -->
                    <div class="grid grid-cols-1 gap-4 mt-6 md:grid-cols-4">
                        <div class="p-4 bg-white border border-gray-200 shadow-sm rounded-xl">
                            <p class="mb-1 text-sm text-gray-500">
                                <i class="mr-1 fas fa-wallet"></i>Gasto Total
                            </p>
                            <p class="text-xl font-bold text-gray-800">
                               {{ moeda(gastoTotal) }}
                            </p>
                        </div>

                        <div class="p-4 bg-white border border-gray-200 shadow-sm rounded-xl">
                            <p class="mb-1 text-sm text-gray-500">
                                <i class="mr-1 fas fa-chart-line"></i>Lucro sem IVA
                            </p>
                           <p
:class="[
    lucroSemIVA >= 0
        ? 'text-green-600'
        : 'text-red-600',
    'text-xl font-bold'
]">

{{ moeda(lucroSemIVA) }}

</p>
                        </div>

                        <div class="p-4 bg-white border border-gray-200 shadow-sm rounded-xl">
                            <p class="mb-1 text-sm text-gray-500">
                                <i class="mr-1 fas fa-calculator"></i>IVA
                            </p>
                            <p class="text-xl font-bold text-orange-600">
                            {{ moeda(valorIVA) }}
                            </p>
                        </div>

                        <div class="p-4 bg-white border border-gray-200 shadow-sm rounded-xl">
                            <p class="mb-1 text-sm text-gray-500">
                                <i class="mr-1 fas fa-money-bill-wave"></i>Lucro com IVA
                            </p>
                            <p :class="[
                                lucroComIVA >= 0
        ? 'text-green-600'
        : 'text-red-600',
    'text-xl font-bold'

                            ]">

                            {{ moeda(lucroComIVA) }}

                            </p>
                        </div>
                    </div>
                </div>



   <!-- Botões -->
                <div class="flex flex-wrap justify-end gap-3 pt-6 mt-6 border-t border-gray-200">
                    <button
                        type="button"
                        @click="reset()"
                        class="px-6 py-2.5 bg-yellow-500 hover:bg-yellow-600 text-white font-medium rounded-lg transition-all duration-200 flex items-center gap-2"
                    >
                        <i class="fas fa-undo"></i>
                        Resetar
                    </button>
                    <button
  @click="guadardados()"

                        type="submit"
                        class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2"
                    >
                        <i class="fas fa-save"></i>
                        Guardar Compra
                    </button>
                </div>




    </AuthenticatedLayout>





    </template>

<style scoped>



.totaldosvalores {
    margin-top: 20px;
    width: 100%;
}

.tabela-valores {
    width: 100%;
    border-collapse: collapse;
    font-family: Arial, sans-serif;
}

.tabela-valores thead {
    background: #f2f2f2;
}

.tabela-valores th,
.tabela-valores td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: center;
}

.tabela-valores th {
    font-weight: bold;
    color: #333;
}

.tabela-valores tbody tr:nth-child(even) {
    background: #fafafa;
}

.tabela-valores tbody tr:hover {
    background: #f1f7ff;
}



/* Estilos personalizados se necessário */
.btn-eliminar:hover {
    transform: scale(1.05);
}

.btn-visualizar:hover {
    transform: scale(1.05);
}

/* Estilo para o select com seta personalizada */
select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    padding-right: 2.5rem;
}

/* Animações */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
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
