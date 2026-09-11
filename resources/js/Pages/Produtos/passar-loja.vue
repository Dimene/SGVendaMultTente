<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import produtosPasarLoja from './produtos-pasar-loja.vue'
import { nextTick, ref } from 'vue';
const props = defineProps({
    armazem: {
        type: Array,
        default: () => []
    },
    lojas: {
        type: Array,
        default: () => []
    }
})

const visualizardetalhes= ref(false);
const produtosdetalhes=ref([]);

const gruposDistintos = (produtositem) => {
    const grupos = produtositem
        .map(item => item.produto?.grupo)
        .filter(grupo => grupo)

    return Array.from(
        new Map(
            grupos.map(grupo => [grupo.id, grupo])
        ).values()
    )
}

const QuantidadeGrupo = (grupoId, produtositem) => {
    const produtosDoGrupo = produtositem.filter(item => {
        return item.produto?.grupo?.id === grupoId
    })

    const quantidade = produtosDoGrupo.reduce((total, item) => {
        return total + Number(item.estoque || 0)
    }, 0)

    return quantidade
}



// Formatar número com separadores
const formatNumber = (value) => {
    return new Intl.NumberFormat('pt-MZ').format(value)
}


async   function getdetalhes(grupo,dados){
 const produtosDoGrupo = dados.filter(item => {
        return item.produto?.grupo?.id === grupo
    })

    produtosdetalhes.value=produtosDoGrupo;
   await nextTick();
visualizardetalhes.value=true;

}

</script>

<template>
    <AuthenticatedLayout>

        <!-- HEADER -->
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">

                <div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        🏪 Produtos no Armazém
                    </h2>

                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Passar produtos para as lojas
                    </p>
                </div>

                <div class="flex gap-3">
                    <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Atualizar
                    </button>
                </div>

            </div>
        </template>


        <!-- CONTEÚDO -->
        <div class="p-6"   v-show="!visualizardetalhes" >

            <!-- TÍTULO -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                    Armazéns disponíveis
                </h3>

                <p class="text-sm text-gray-500">
                    Selecione o armazém que deseja consultar.
                </p>
            </div>


            <!-- CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <div
                    v-for="item in props.armazem"
                    :key="item.id"
                    class="group bg-white dark:bg-gray-800
                           rounded-2xl
                           border border-gray-100 dark:border-gray-700
                           shadow-sm
                           p-6
                           cursor-pointer
                           hover:shadow-xl
                           hover:-translate-y-1
                           transition-all duration-300
                           relative
                           overflow-hidden"
                >

                    <!-- Fundo gradiente no hover -->
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-50/0 to-blue-50/0
                     group-hover:from-blue-50/30 group-hover:to-blue-100/20 dark:group-hover:from-blue-900/10 dark:group-hover:to-blue-800/5 transition-all duration-300"></div>

                    <!-- ÍCONE + NOME -->
                    <div class="flex items-center justify-between relative z-10">

                        <div
                            class="w-14 h-14 rounded-xl
                                   bg-gradient-to-br from-blue-100 to-blue-200
                                   dark:from-blue-900/30 dark:to-blue-800/20
                                   flex items-center justify-center
                                   group-hover:scale-110
                                   group-hover:rotate-6
                                   transition-all duration-300"
                        >
                            <span class="text-3xl">
                                🏪
                            </span>
                        </div>

                        <span
                            class="text-xs font-medium
                                   px-3 py-1.5
                                   rounded-full
                                   bg-green-100 dark:bg-green-900/30
                                   text-green-700 dark:text-green-400
                                   flex items-center gap-1.5"
                        >
                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                            Ativo
                        </span>

                    </div>


                    <!-- INFORMAÇÕES -->
                    <div class="mt-5 relative z-10">

                        <h4 class="text-lg font-bold text-gray-800 dark:text-white">
                            {{ item.Descricao }}
                        </h4>

                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Armazém #{{ item.id }}
                        </p>

                        <hr class="my-4 border-gray-200 dark:border-gray-700">

                        <!-- Tabela de Grupos -->
                        <div class="space-y-3">
                            <!-- Cabeçalho -->
                            <div class="grid grid-cols-2 gap-3 px-3 py-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    Grupo
                                </div>
                                <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider text-right">
                                    Quantidade
                                </div>
                            </div>

                            <!-- Linhas -->
                            <div
                                v-for="grupo in gruposDistintos(item.produtositem)"
                                :key="grupo.id"
                                class="grid grid-cols-2 gap-3 px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors duration-200"
                          @click="getdetalhes(grupo.id,item.produtositem)"   >
                                <div class="text-sm text-gray-700 dark:text-gray-300 flex items-center gap-2" >
                                    <span class="text-lg">📦</span>
                                    {{ grupo.nome }}
                                </div>
                                <div class="text-sm font-semibold text-blue-600 dark:text-blue-400 text-right">
                                    {{ formatNumber(QuantidadeGrupo(grupo.id, item.produtositem)) }}
                                </div>
                            </div>

                            <!-- Total -->
                            <div class="grid grid-cols-2 gap-3 px-3 py-3 mt-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800/30">
                                <div class="text-sm font-bold text-gray-800 dark:text-white">
                                    Total Geral
                                </div>
                                <div class="text-sm font-bold text-blue-600 dark:text-blue-400 text-right">
                                    {{ formatNumber(item.produtositem?.reduce((total, p) => total + Number(p.estoque || 0), 0) || 0) }}
                                </div>
                            </div>
                        </div>

                    </div>


                    <!-- RODAPÉ -->
                    <div
                        class="mt-5 pt-4
                               border-t border-gray-100 dark:border-gray-700
                               flex items-center justify-between
                               relative z-10"
                    >

                        <span class="text-sm text-gray-500 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                            Ver produtos →
                        </span>

                        <div
                            class="w-9 h-9 rounded-xl
                                   bg-gray-100 dark:bg-gray-700
                                   flex items-center justify-center
                                   group-hover:bg-blue-600
                                   group-hover:text-white
                                   group-hover:scale-110
                                   transition-all duration-300
                                   text-gray-600 dark:text-gray-300"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>

                    </div>

                </div>

            </div>


            <!-- SEM ARMAZÉNS -->
            <div
                v-if="props.armazem.length === 0"
                class="bg-white dark:bg-gray-800
                       rounded-2xl
                       border-2 border-dashed border-gray-300 dark:border-gray-600
                       p-12
                       text-center
                       transition-all duration-300
                       hover:border-blue-400 dark:hover:border-blue-500"
            >

                <div class="text-6xl mb-4 animate-bounce">
                    🏪
                </div>

                <h3 class="text-xl font-semibold text-gray-800 dark:text-white">
                    Nenhum armazém encontrado
                </h3>

                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 max-w-md mx-auto">
                    Não existem armazéns disponíveis neste momento.
                    <br class="hidden sm:block">
                    Tente novamente mais tarde.
                </p>

                <button class="mt-6 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200">
                    Recarregar
                </button>

            </div>

        </div>
<produtosPasarLoja
    v-show="visualizardetalhes"
    v-model:visualizardetalhes="visualizardetalhes"
    :produtosdetalhes="produtosdetalhes"
    :lojas="lojas"
/>



    </AuthenticatedLayout>
</template>

<style scoped>
/* Animações extras */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.5;
        transform: scale(1.2);
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Transição suave para todos os elementos */
.group {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Efeito de brilho no hover */
.group:hover {
    box-shadow: 0 20px 60px -15px rgba(0, 0, 0, 0.15);
}

/* Dark mode */
.dark .group:hover {
    box-shadow: 0 20px 60px -15px rgba(0, 0, 0, 0.4);
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

/* Responsividade para telas menores */
@media (max-width: 640px) {
    .grid-cols-2 {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
}
</style>
