<script setup>
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    linhas:   { type: Array, default: () => [] },
    armazens: { type: Array, default: () => [] },
    lojas:    { type: Array, default: () => [] },
})

/* ---------------- Pesquisa ---------------- */
const pesquisa = ref('')

/* ---------------- Totais globais (KPIs) ---------------- */
const totalArmazem = computed(() =>
    props.armazens.reduce((soma, a) =>
        soma + props.linhas.reduce((s, item) => s + (Number(item[a.id + 'armazem']) || 0), 0)
    , 0)
)

const totalLoja = computed(() =>
    props.lojas.reduce((soma, l) =>
        soma + props.linhas.reduce((s, item) => s + (Number(item[l.id + 'loja']) || 0), 0)
    , 0)
)

const totalItens = computed(() => totalArmazem.value + totalLoja.value)

const totalFinanceiro = computed(() =>
    dadostabela.value.reduce((s, item) =>
        s + (Number(item.quantidadetotoal) || 0) * (Number(item.preco_venda1) || 0)
    , 0)
)

/* ---------------- Tabela ---------------- */
const totalColunas = computed(() => 3 + props.armazens.length)

const dadostabela = computed(() =>
    props.linhas.map(item => ({
        ...item,
        total: props.armazens.reduce(
            (s, a) => s + (item.stocks?.[a.id] ?? 0),
            0
        ),
    }))
)

/* ---------------- Filtro da pesquisa ---------------- */
const dadosFiltrados = computed(() => {
    const termo = pesquisa.value.trim().toLowerCase()
    if (!termo) return dadostabela.value

    return dadostabela.value.filter(item => {
        const campos = [
            item.id,
            item.nome,
            item.categoria,
        ]
        return campos.some(v =>
            v != null && String(v).toLowerCase().includes(termo)
        )
    })
})

/* ---------------- Helpers ---------------- */
function formatMZN(valor) {
    const n = Number(valor) || 0
    return n.toLocaleString('pt-MZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatNum(valor) {
    return (Number(valor) || 0).toLocaleString('pt-MZ')
}
</script>

<template>
    <Head title="Inventário" />

    <AuthenticatedLayout>
        <div class="p-4 space-y-6 sm:p-6 lg:p-8">

            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    
                    <p class="mt-1 text-sm text-gray-500">
                        Visão geral de stock por armazém e loja
                    </p>
                </div>
                <span class="inline-flex items-center self-start px-3 py-1 text-xs font-medium text-indigo-700 rounded-full bg-indigo-50 ring-1 ring-inset ring-indigo-200">
                    {{ dadosFiltrados.length }} {{ dadosFiltrados.length === 1 ? 'artigo' : 'artigos' }}
                </span>
            </div>

          
            <!-- Barra de pesquisa -->
            <div class="p-4 bg-white border border-gray-200 shadow-sm rounded-xl">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                    </span>
                    <input
                        v-model="pesquisa"
                        type="text"
                        placeholder="Pesquisar por ID, nome ou categoria..."
                        class="block w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-10 text-sm text-gray-900 placeholder-gray-400 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                    />
                    <button
                        v-if="pesquisa"
                        @click="pesquisa = ''"
                        type="button"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                        aria-label="Limpar pesquisa"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <p v-if="pesquisa" class="mt-2 text-xs text-gray-500">
                    {{ dadosFiltrados.length }} resultado(s) para "<span class="font-medium text-gray-700">{{ pesquisa }}</span>"
                </p>
            </div>

            <!-- Tabela -->
            <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm divide-y divide-gray-200">
                        <thead>
                            <tr class="bg-gradient-to-b from-gray-50 to-gray-100">
                                <th rowspan="2" class="sticky left-0 z-10 px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase bg-gray-100 border-b border-r border-gray-200">
                                    #
                                </th>
                                <th rowspan="2" class="sticky left-[60px] z-10 bg-gray-100 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 border-r border-b border-gray-200 min-w-[180px]">
                                    Nome
                                </th>
                                <th rowspan="2" class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase border-b border-r border-gray-200">
                                    Categoria
                                </th>

                                <th
                                    v-if="props.armazens.length"
                                    :colspan="props.armazens.length"
                                    class="px-4 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-blue-700 bg-blue-50 border-r border-b border-gray-200"
                                >
                                    Armazéns
                                </th>

                                <th
                                    v-if="props.lojas.length"
                                    :colspan="props.lojas.length"
                                    class="px-4 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-emerald-700 bg-emerald-50 border-r border-b border-gray-200"
                                >
                                    Lojas
                                </th>

                                <th rowspan="2" class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-gray-600 uppercase border-b border-r border-gray-200 bg-gray-50">
                                    Total Itens
                                </th>

                                <th colspan="3" class="px-4 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-purple-700 bg-purple-50 border-r border-b border-gray-200">
                                    Preço de Venda
                                </th>

                                
                            </tr>

                            <tr class="bg-gray-50">
                                <th
                                    v-for="armazem in props.armazens"
                                    :key="'h-a-' + armazem.id"
                                    class="px-4 py-2.5 text-right text-xs font-medium text-blue-800 border-r border-b border-gray-200 whitespace-nowrap"
                                >
                                    {{ armazem.Descricao }}
                                </th>

                                <th
                                    v-for="loja in props.lojas"
                                    :key="'h-l-' + loja.id"
                                    class="px-4 py-2.5 text-right text-xs font-medium text-emerald-800 border-r border-b border-gray-200 whitespace-nowrap"
                                >
                                    {{ loja.Desc }}
                                </th>

                                <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-700 border-r border-b border-gray-200 whitespace-nowrap">
                                    Singulares
                                </th>
                                <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-700 border-r border-b border-gray-200 whitespace-nowrap">
                                    Empresas
                                </th>
                                <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-700 border-r border-b border-gray-200 whitespace-nowrap">
                                    IVA
                                </th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr
                                v-for="dadositem in dadosFiltrados"
                                :key="dadositem.id"
                                class="transition-colors group hover:bg-indigo-50/40"
                            >
                                <td class="sticky left-0 z-10 px-4 py-3 font-mono text-xs text-gray-500 bg-white border-r border-gray-100 group-hover:bg-indigo-50/40">
                                    {{ dadositem.id }}
                                </td>
                                <td class="sticky left-[60px] z-10 bg-white group-hover:bg-indigo-50/40 px-4 py-3 font-medium text-gray-900 border-r border-gray-100">
                                    {{ dadositem.nome }}
                                </td>
                                <td class="px-4 py-3 border-r border-gray-100">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded-md">
                                        {{ dadositem.categoria }}
                                    </span>
                                </td>

                                <td
                                    v-for="armazem in props.armazens"
                                    :key="'a-' + armazem.id"
                                    class="px-4 py-3 text-right text-gray-700 border-r border-gray-100 tabular-nums"
                                >
                                    {{ dadositem[armazem.id + 'armazem'] }}
                                </td>

                                <td
                                    v-for="loja in props.lojas"
                                    :key="'l-' + loja.id"
                                    class="px-4 py-3 text-right text-gray-700 border-r border-gray-100 tabular-nums"
                                >
                                    {{ dadositem[loja.id + 'loja'] }}
                                </td>

                                <td class="px-4 py-3 font-semibold text-right text-gray-900 border-r border-gray-100 tabular-nums bg-gray-50/60">
                                    {{ dadositem.quantidadetotoal }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-700 border-r border-gray-100 tabular-nums">
                                    {{ formatMZN(dadositem.preco_venda1) }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-700 border-r border-gray-100 tabular-nums">
                                    {{ formatMZN(dadositem.preco_venda2) }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-700 border-r border-gray-100 tabular-nums">
                                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-200">
                                        {{ dadositem.iva_percentual }}%
                                    </span>
                                </td>
                                
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="!dadosFiltrados.length">
                                <td :colspan="totalColunas + 6" class="px-4 py-16 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                                        </svg>
                                        <p class="text-sm font-medium text-gray-500">
                                            {{ pesquisa ? 'Nenhum resultado encontrado' : 'Sem dados para mostrar' }}
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            {{ pesquisa ? 'Tente ajustar os termos da pesquisa.' : 'Ajuste os filtros ou adicione novos artigos.' }}
                                        </p>
                                        <button
                                            v-if="pesquisa"
                                            @click="pesquisa = ''"
                                            class="mt-2 inline-flex items-center rounded-md bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100"
                                        >
                                            Limpar pesquisa
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>