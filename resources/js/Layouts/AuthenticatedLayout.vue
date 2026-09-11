<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import { Link } from '@inertiajs/vue3'

const sidebarOpen = ref(true)
const mobileMenuOpen = ref(false)
const loading = ref(false)
const loadingLink = ref(null)
const hoveredMenu = ref(null)
let hoverTimeout = null
const isMobile = ref(false)

// ============ DARK MODE ============
const darkMode = ref(false)

function toggleDark() {
    darkMode.value = !darkMode.value
    document.documentElement.classList.toggle('dark', darkMode.value)
    localStorage.setItem('darkMode', JSON.stringify(darkMode.value))
}

// Inicializar tema escuro
function initDarkMode() {
    const savedTheme = localStorage.getItem('darkMode')
    if (savedTheme !== null) {
        darkMode.value = JSON.parse(savedTheme)
        if (darkMode.value) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
        darkMode.value = true
        document.documentElement.classList.add('dark')
    }
}

// Estado para submenus abertos (quando sidebar está expandida)
const openSubmenus = ref({
    compras: false,
    vendas: false,
    financeiro: false,
    produtos: false,
    configuracoes: false
})

// Configuração dos submenus
const menuItems = {
    produtos: {
        items: [
            { name: 'Listar Produtos', route: 'produtos.index', icon: '📋' },
            { name: 'Cadastrar Produto', route: 'produtos.create', icon: '➕' },
            { name: 'Categorias', route: 'produtos.categorias', icon: '🏷️' }
        ]
    },
    vendas: {
        items: [
            { name: 'Visualizar Vendas', route: 'vendas.index', icon: '👁️' },
            { name: 'Nova Venda', route: 'vendas.create', icon: '🛒' },
            { name: 'Relatórios', route: 'vendas.relatorios', icon: '📊' },
            { name: 'Passar a Loja', route: 'vendas.passarLoja', icon: '🏪' }
        ]
    },
    compras: {
        items: [
            { name: 'Visualizar Compras', route: 'compras.index', icon: '👁️' },
            { name: 'Registrar Compra', route: 'compras.create', icon: '📝' },
            { name: 'Relatórios', route: 'compras.relatorios', icon: '📊' },
            { name: 'Componentes Compra', route: 'compras.dados', icon: '📊' },
            { name: 'Fornecedores', route: 'compras.fornecedores', icon: '🏢' }
        ]
    },
    financeiro: {
        items: [
            { name: 'Contas a Pagar', route: 'financeiro.contas-pagar', icon: '📤' },
            { name: 'Contas a Receber', route: 'financeiro.contas-receber', icon: '📥' },
            { name: 'Fluxo de Caixa', route: 'financeiro.fluxo-caixa', icon: '💰' },
            { name: 'Relatórios Financeiros', route: 'financeiro.relatorios', icon: '📊' }
        ]
    },
    configuracoes: {
        items: [
            { name: 'Dados da Empresa', route: 'configuracoes.empresa', icon: '🏢' },
            { name: 'Usuários', route: 'configuracoes.usuarios', icon: '👥' },
            { name: 'Permissões', route: 'configuracoes.permissoes', icon: '🔐' },
            { name: 'Backup', route: 'configuracoes.backup', icon: '💾' }
        ]
    }
}

// Função para verificar se um item está ativo
const isActive = (routePattern) => {
    if (typeof routePattern === 'string') {
        return route().current(routePattern)
    }
    return route().current(routePattern)
}

// Função para verificar se algum subitem do menu está ativo
const isSubmenuActive = (subItems) => {
    return subItems.some(item => route().current(item.route))
}

// Função para verificar se um menu específico está ativo
const isMenuActive = (menuKey) => {
    const menu = menuItems[menuKey]
    if (!menu) return false
    return isSubmenuActive(menu.items)
}

// Função para obter o menu ativo atual
const getActiveMenu = () => {
    for (const [key, menu] of Object.entries(menuItems)) {
        if (isSubmenuActive(menu.items)) {
            return key
        }
    }
    return null
}

// Inicializar submenus abertos baseado na rota atual
const initializeOpenSubmenus = () => {
    const activeMenu = getActiveMenu()
    if (activeMenu) {
        openSubmenus.value[activeMenu] = true
        if (!sidebarOpen.value && !isMobile.value) {
            hoveredMenu.value = activeMenu
        }
    }
}

// Função para alternar sidebar - TOGGLE EM TODOS OS TAMANHOS
const toggleSidebar = () => {
    const mobile = window.innerWidth < 768

    if (mobile) {
        mobileMenuOpen.value = !mobileMenuOpen.value
        if (mobileMenuOpen.value) {
            hoveredMenu.value = null
            document.body.style.overflow = 'hidden'
        } else {
            document.body.style.overflow = ''
        }
    } else {
        sidebarOpen.value = !sidebarOpen.value

        if (!sidebarOpen.value) {
            const activeMenu = getActiveMenu()
            Object.keys(openSubmenus.value).forEach(key => {
                openSubmenus.value[key] = key === activeMenu
            })
            hoveredMenu.value = activeMenu
        } else {
            const activeMenu = getActiveMenu()
            if (activeMenu) {
                openSubmenus.value[activeMenu] = true
            }
            hoveredMenu.value = null
        }
    }
}

// Alternar submenu (modo expandido)
const toggleSubmenu = (menu) => {
    if (sidebarOpen.value) {
        openSubmenus.value[menu] = !openSubmenus.value[menu]
    }
}

// Hover para mostrar submenu flutuante (modo recolhido)
const showFloatingSubmenu = (menu) => {
    if (!sidebarOpen.value && !isMobile.value && !mobileMenuOpen.value) {
        if (hoverTimeout) clearTimeout(hoverTimeout)
        if (menu === null) {
            hoveredMenu.value = null
            return
        }
        if (menuItems[menu]) {
            hoveredMenu.value = menu
        }
    }
}

const hideFloatingSubmenu = () => {
    if (!sidebarOpen.value && !isMobile.value && !mobileMenuOpen.value) {
        const activeMenu = getActiveMenu()
        if (hoveredMenu.value === activeMenu) {
            return
        }
        hoverTimeout = setTimeout(() => {
            hoveredMenu.value = null
        }, 200)
    }
}

const cancelHide = () => {
    if (hoverTimeout) {
        clearTimeout(hoverTimeout)
        hoverTimeout = null
    }
}

// Fechar menu mobile ao clicar em um link ou no overlay
const closeMobileMenu = () => {
    mobileMenuOpen.value = false
    document.body.style.overflow = ''
}

// Função para navegação com loading
const navigateWithLoading = (url, linkName) => {
    loading.value = true
    loadingLink.value = linkName

    if (mobileMenuOpen.value) {
        closeMobileMenu()
    }

    router.visit(url, {
        onSuccess: () => {
            setTimeout(() => {
                loading.value = false
                loadingLink.value = null
                const activeMenu = getActiveMenu()
                if (activeMenu) {
                    openSubmenus.value[activeMenu] = true
                    if (!sidebarOpen.value && !isMobile.value) {
                        hoveredMenu.value = activeMenu
                    }
                }
            }, 500)
        },
        onError: () => {
            setTimeout(() => {
                loading.value = false
                loadingLink.value = null
            }, 500)
        }
    })
}

// Verificar tamanho da tela
const checkScreenSize = () => {
    const wasMobile = isMobile.value
    isMobile.value = window.innerWidth < 768

    if (!wasMobile && isMobile.value) {
        sidebarOpen.value = false
        mobileMenuOpen.value = false
        hoveredMenu.value = null
        document.body.style.overflow = ''
    } else if (wasMobile && !isMobile.value) {
        sidebarOpen.value = true
        mobileMenuOpen.value = false
        document.body.style.overflow = ''
        initializeOpenSubmenus()
    }
}

// Lifecycle hooks
onMounted(() => {
    checkScreenSize()
    window.addEventListener('resize', checkScreenSize)
    setTimeout(initializeOpenSubmenus, 100)
    initDarkMode() // Inicializar tema escuro
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', checkScreenSize)
    document.body.style.overflow = ''
})

// Computed para verificar se deve mostrar submenu flutuante
const shouldShowFloating = (menu) => {
    if (isMobile.value || sidebarOpen.value || mobileMenuOpen.value) return false
    return hoveredMenu.value === menu || (isMenuActive(menu) && openSubmenus.value[menu])
}

// Computed para controlar a visibilidade da sidebar
const sidebarVisible = computed(() => {
    if (isMobile.value) {
        return mobileMenuOpen.value
    }
    return sidebarOpen.value
})
</script>

<template>
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 transition-colors duration-300">

    <!-- Overlay de Loading Global -->
    <div v-if="loading" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-2xl flex flex-col items-center space-y-4">
            <div class="relative">
                <div class="w-16 h-16 border-4 border-blue-200 dark:border-blue-800 rounded-full animate-spin border-t-blue-600 dark:border-t-blue-400"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </div>
            </div>
            <p class="text-gray-700 dark:text-gray-300 font-medium">Carregando {{ loadingLink }}...</p>
        </div>
    </div>

    <!-- ================= SIDEBAR MODERNA ================= -->
    <aside
        :class="[
            'fixed inset-y-0 left-0 z-30 transform transition-all duration-300 ease-in-out',
            'bg-gradient-to-b from-slate-800 to-slate-900 dark:from-slate-900 dark:to-slate-950 shadow-2xl',
            sidebarVisible ? 'translate-x-0' : '-translate-x-full',
            isMobile ? 'w-64' : (sidebarOpen ? 'w-64' : 'w-20')
        ]"
    >
        <!-- LOGO CENTRAL -->
        <div class="flex items-center justify-center h-20 border-b border-white/10 dark:border-white/5">
            <Link :href="route('dashboard')" class="flex items-center space-x-2 group" @click="closeMobileMenu">
                <div class="relative">
                    <ApplicationLogo class="h-10 w-auto transition-transform group-hover:scale-105" />
                    <div
                        v-if="route().current('dashboard')"
                        class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full animate-pulse"
                    ></div>
                </div>
                <span
                    v-show="sidebarOpen || isMobile"
                    class="text-white font-bold text-xl bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent"
                >
                    ERP System
                </span>
            </Link>
        </div>

        <!-- MENU PRINCIPAL -->
        <nav class="flex-1 px-3 py-6 space-y-1.5 overflow-y-auto">

            <!-- Dashboard -->
            <div
                class="relative"
                @mouseenter="showFloatingSubmenu(null)"
                @mouseleave="hideFloatingSubmenu"
            >
                <button
                    @click="navigateWithLoading(route('dashboard'), 'Dashboard')"
                    class="w-full group flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 hover:bg-white/10 relative"
                    :class="[
                        (sidebarOpen || isMobile) ? 'justify-start' : 'justify-center',
                        route().current('dashboard') ? 'bg-white/10 text-white' : 'text-gray-300 hover:text-white'
                    ]"
                >
                    <div class="relative flex items-center gap-3">
                        <div class="relative">
                            <svg v-if="loadingLink === 'Dashboard'" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <div
                                v-if="route().current('dashboard')"
                                class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-green-500 rounded-full animate-pulse"
                            ></div>
                        </div>
                        <span v-show="sidebarOpen || isMobile" class="text-sm font-medium">Dashboard</span>
                    </div>
                    <div v-if="!sidebarOpen && !isMobile" class="absolute left-14 bg-slate-700 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50">
                        Dashboard
                    </div>
                </button>
            </div>

            <!-- Clientes -->
            <div
                class="relative"
                @mouseenter="showFloatingSubmenu(null)"
                @mouseleave="hideFloatingSubmenu"
            >
                <button
                    @click="navigateWithLoading(route('clientes.index'), 'Clientes')"
                    class="w-full group flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 hover:bg-white/10 relative"
                    :class="[
                        (sidebarOpen || isMobile) ? 'justify-start' : 'justify-center',
                        route().current('clientes.*') ? 'bg-white/10 text-white' : 'text-gray-300 hover:text-white'
                    ]"
                >
                    <div class="relative flex items-center gap-3">
                        <div class="relative">
                            <svg v-if="loadingLink === 'Clientes'" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <div
                                v-if="route().current('clientes.*')"
                                class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-green-500 rounded-full animate-pulse"
                            ></div>
                        </div>
                        <span v-show="sidebarOpen || isMobile" class="text-sm font-medium">Clientes</span>
                    </div>
                    <div v-if="!sidebarOpen && !isMobile" class="absolute left-14 bg-slate-700 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50">
                        Clientes
                    </div>
                </button>
            </div>

            <!-- Estoque -->
            <div
                class="relative"
                @mouseenter="showFloatingSubmenu(null)"
                @mouseleave="hideFloatingSubmenu"
            >
                <button
                    @click="navigateWithLoading(route('estoque.index'), 'Estoque')"
                    class="w-full group flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 hover:bg-white/10 relative"
                    :class="[
                        (sidebarOpen || isMobile) ? 'justify-start' : 'justify-center',
                        route().current('estoque.*') ? 'bg-white/10 text-white' : 'text-gray-300 hover:text-white'
                    ]"
                >
                    <div class="relative flex items-center gap-3">
                        <div class="relative">
                            <svg v-if="loadingLink === 'Estoque'" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            <div
                                v-if="route().current('estoque.*')"
                                class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-green-500 rounded-full animate-pulse"
                            ></div>
                        </div>
                        <span v-show="sidebarOpen || isMobile" class="text-sm font-medium">Estoque</span>
                    </div>
                    <div v-if="!sidebarOpen && !isMobile" class="absolute left-14 bg-slate-700 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50">
                        Estoque
                    </div>
                </button>
            </div>

            <!-- Vendas -->
            <div
                class="relative"
                @mouseenter="showFloatingSubmenu('vendas')"
                @mouseleave="hideFloatingSubmenu"
            >
                <button
                    @click="sidebarOpen ? toggleSubmenu('vendas') : null"
                    class="w-full group flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 hover:bg-white/10 relative"
                    :class="[
                        (sidebarOpen || isMobile) ? 'justify-between' : 'justify-center',
                        route().current('vendas.*') || isSubmenuActive(menuItems.vendas.items) ? 'bg-white/10 text-white' : 'text-gray-300 hover:text-white'
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <svg v-if="loadingLink === 'Vendas'" class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            <div
                                v-if="route().current('vendas.*') || isSubmenuActive(menuItems.vendas.items)"
                                class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-green-500 rounded-full animate-pulse"
                            ></div>
                        </div>
                        <span v-show="sidebarOpen || isMobile" class="text-sm font-medium">Vendas</span>
                    </div>
                    <svg
                        v-show="sidebarOpen || isMobile"
                        class="w-4 h-4 transition-transform duration-200"
                        :class="{ 'rotate-180': openSubmenus.vendas }"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div v-if="!sidebarOpen && !isMobile" class="absolute left-14 bg-slate-700 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50">
                    Vendas
                </div>

                <!-- Submenu flutuante -->
                <div
                    v-if="shouldShowFloating('vendas') && !isMobile"
                    class="fixed left-20 bg-slate-800 dark:bg-slate-900 rounded-xl shadow-2xl py-2 min-w-48 z-50 border border-white/10 dark:border-white/5"
                    @mouseenter="cancelHide"
                    @mouseleave="hideFloatingSubmenu"
                >
                    <button
                        v-for="item in menuItems.vendas.items"
                        :key="item.route"
                        @click="navigateWithLoading(route(item.route), item.name)"
                        class="w-full text-left px-4 py-2.5 text-gray-300 hover:text-white hover:bg-white/10 transition-all text-sm flex items-center gap-2"
                        :class="{ 'bg-white/5 text-white': route().current(item.route) }"
                    >
                        <span>{{ item.icon }}</span>
                        <span>{{ item.name }}</span>
                        <div
                            v-if="route().current(item.route)"
                            class="ml-auto w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"
                        ></div>
                    </button>
                </div>

                <!-- Submenu normal -->
                <div
                    v-show="(sidebarOpen || isMobile) && openSubmenus.vendas"
                    class="ml-8 mt-1 space-y-1 border-l border-white/10 dark:border-white/5 pl-3"
                >
                    <button
                        v-for="item in menuItems.vendas.items"
                        :key="item.route"
                        @click="navigateWithLoading(route(item.route), item.name)"
                        class="w-full text-left px-3 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/10 text-sm transition-all relative flex items-center gap-2"
                        :class="{ 'bg-white/5 text-white': route().current(item.route) }"
                    >
                        <span>{{ item.icon }}</span>
                        <span>{{ item.name }}</span>
                        <div
                            v-if="route().current(item.route)"
                            class="absolute left-0 top-1/2 transform -translate-y-1/2 w-1 h-4 bg-green-500 rounded-full animate-pulse"
                        ></div>
                    </button>
                </div>
            </div>

            <!-- Compras -->
            <div
                class="relative"
                @mouseenter="showFloatingSubmenu('compras')"
                @mouseleave="hideFloatingSubmenu"
            >
                <button
                    @click="sidebarOpen ? toggleSubmenu('compras') : null"
                    class="w-full group flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 hover:bg-white/10 relative"
                    :class="[
                        (sidebarOpen || isMobile) ? 'justify-between' : 'justify-center',
                        route().current('compras.*') || isSubmenuActive(menuItems.compras.items) ? 'bg-white/10 text-white' : 'text-gray-300 hover:text-white'
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <svg v-if="loadingLink === 'Compras'" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <div
                                v-if="route().current('compras.*') || isSubmenuActive(menuItems.compras.items)"
                                class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-green-500 rounded-full animate-pulse"
                            ></div>
                        </div>
                        <span v-show="sidebarOpen || isMobile" class="text-sm font-medium">Compras</span>
                    </div>
                    <svg
                        v-show="sidebarOpen || isMobile"
                        class="w-4 h-4 transition-transform duration-200"
                        :class="{ 'rotate-180': openSubmenus.compras }"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div v-if="!sidebarOpen && !isMobile" class="absolute left-14 bg-slate-700 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50">
                    Compras
                </div>

                <!-- Submenu flutuante -->
                <div
                    v-if="shouldShowFloating('compras') && !isMobile"
                    class="fixed left-20 bg-slate-800 dark:bg-slate-900 rounded-xl shadow-2xl py-2 min-w-48 z-50 border border-white/10 dark:border-white/5"
                    @mouseenter="cancelHide"
                    @mouseleave="hideFloatingSubmenu"
                >
                    <button
                        v-for="item in menuItems.compras.items"
                        :key="item.route"
                        @click="navigateWithLoading(route(item.route), item.name)"
                        class="w-full text-left px-4 py-2.5 text-gray-300 hover:text-white hover:bg-white/10 transition-all text-sm flex items-center gap-2"
                        :class="{ 'bg-white/5 text-white': route().current(item.route) }"
                    >
                        <span>{{ item.icon }}</span>
                        <span>{{ item.name }}</span>
                        <div
                            v-if="route().current(item.route)"
                            class="ml-auto w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"
                        ></div>
                    </button>
                </div>

                <!-- Submenu normal -->
                <div
                    v-show="(sidebarOpen || isMobile) && openSubmenus.compras"
                    class="ml-8 mt-1 space-y-1 border-l border-white/10 dark:border-white/5 pl-3"
                >
                    <button
                        v-for="item in menuItems.compras.items"
                        :key="item.route"
                        @click="navigateWithLoading(route(item.route), item.name)"
                        class="w-full text-left px-3 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/10 text-sm transition-all relative flex items-center gap-2"
                        :class="{ 'bg-white/5 text-white': route().current(item.route) }"
                    >
                        <span>{{ item.icon }}</span>
                        <span>{{ item.name }}</span>
                        <div
                            v-if="route().current(item.route)"
                            class="absolute left-0 top-1/2 transform -translate-y-1/2 w-1 h-4 bg-green-500 rounded-full animate-pulse"
                        ></div>
                    </button>
                </div>
            </div>

            <!-- Financeiro -->
            <div
                class="relative"
                @mouseenter="showFloatingSubmenu('financeiro')"
                @mouseleave="hideFloatingSubmenu"
            >
                <button
                    @click="sidebarOpen ? toggleSubmenu('financeiro') : null"
                    class="w-full group flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 hover:bg-white/10 relative"
                    :class="[
                        (sidebarOpen || isMobile) ? 'justify-between' : 'justify-center',
                        route().current('financeiro.*') || isSubmenuActive(menuItems.financeiro.items) ? 'bg-white/10 text-white' : 'text-gray-300 hover:text-white'
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <svg v-if="loadingLink === 'Financeiro'" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div
                                v-if="route().current('financeiro.*') || isSubmenuActive(menuItems.financeiro.items)"
                                class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-green-500 rounded-full animate-pulse"
                            ></div>
                        </div>
                        <span v-show="sidebarOpen || isMobile" class="text-sm font-medium">Financeiro</span>
                    </div>
                    <svg
                        v-show="sidebarOpen || isMobile"
                        class="w-4 h-4 transition-transform duration-200"
                        :class="{ 'rotate-180': openSubmenus.financeiro }"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div v-if="!sidebarOpen && !isMobile" class="absolute left-14 bg-slate-700 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50">
                    Financeiro
                </div>

                <!-- Submenu flutuante -->
                <div
                    v-if="shouldShowFloating('financeiro') && !isMobile"
                    class="fixed left-20 bg-slate-800 dark:bg-slate-900 rounded-xl shadow-2xl py-2 min-w-48 z-50 border border-white/10 dark:border-white/5"
                    @mouseenter="cancelHide"
                    @mouseleave="hideFloatingSubmenu"
                >
                    <button
                        v-for="item in menuItems.financeiro.items"
                        :key="item.route"
                        @click="navigateWithLoading(route(item.route), item.name)"
                        class="w-full text-left px-4 py-2.5 text-gray-300 hover:text-white hover:bg-white/10 transition-all text-sm flex items-center gap-2"
                        :class="{ 'bg-white/5 text-white': route().current(item.route) }"
                    >
                        <span>{{ item.icon }}</span>
                        <span>{{ item.name }}</span>
                        <div
                            v-if="route().current(item.route)"
                            class="ml-auto w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"
                        ></div>
                    </button>
                </div>

                <!-- Submenu normal -->
                <div
                    v-show="(sidebarOpen || isMobile) && openSubmenus.financeiro"
                    class="ml-8 mt-1 space-y-1 border-l border-white/10 dark:border-white/5 pl-3"
                >
                    <button
                        v-for="item in menuItems.financeiro.items"
                        :key="item.route"
                        @click="navigateWithLoading(route(item.route), item.name)"
                        class="w-full text-left px-3 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/10 text-sm transition-all relative flex items-center gap-2"
                        :class="{ 'bg-white/5 text-white': route().current(item.route) }"
                    >
                        <span>{{ item.icon }}</span>
                        <span>{{ item.name }}</span>
                        <div
                            v-if="route().current(item.route)"
                            class="absolute left-0 top-1/2 transform -translate-y-1/2 w-1 h-4 bg-green-500 rounded-full animate-pulse"
                        ></div>
                    </button>
                </div>
            </div>

            <!-- Configurações -->
            <div
                class="relative"
                @mouseenter="showFloatingSubmenu('configuracoes')"
                @mouseleave="hideFloatingSubmenu"
            >
                <button
                    @click="sidebarOpen ? toggleSubmenu('configuracoes') : null"
                    class="w-full group flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 hover:bg-white/10 relative"
                    :class="[
                        (sidebarOpen || isMobile) ? 'justify-between' : 'justify-center',
                        route().current('configuracoes.*') || isSubmenuActive(menuItems.configuracoes.items) ? 'bg-white/10 text-white' : 'text-gray-300 hover:text-white'
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <svg v-if="loadingLink === 'Configurações'" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <div
                                v-if="route().current('configuracoes.*') || isSubmenuActive(menuItems.configuracoes.items)"
                                class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-green-500 rounded-full animate-pulse"
                            ></div>
                        </div>
                        <span v-show="sidebarOpen || isMobile" class="text-sm font-medium">Configurações</span>
                    </div>
                    <svg
                        v-show="sidebarOpen || isMobile"
                        class="w-4 h-4 transition-transform duration-200"
                        :class="{ 'rotate-180': openSubmenus.configuracoes }"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div v-if="!sidebarOpen && !isMobile" class="absolute left-14 bg-slate-700 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50">
                    Configurações
                </div>

                <!-- Submenu flutuante -->
                <div
                    v-if="shouldShowFloating('configuracoes') && !isMobile"
                    class="fixed left-20 bg-slate-800 dark:bg-slate-900 rounded-xl shadow-2xl py-2 min-w-48 z-50 border border-white/10 dark:border-white/5"
                    @mouseenter="cancelHide"
                    @mouseleave="hideFloatingSubmenu"
                >
                    <button
                        v-for="item in menuItems.configuracoes.items"
                        :key="item.route"
                        @click="navigateWithLoading(route(item.route), item.name)"
                        class="w-full text-left px-4 py-2.5 text-gray-300 hover:text-white hover:bg-white/10 transition-all text-sm flex items-center gap-2"
                        :class="{ 'bg-white/5 text-white': route().current(item.route) }"
                    >
                        <span>{{ item.icon }}</span>
                        <span>{{ item.name }}</span>
                        <div
                            v-if="route().current(item.route)"
                            class="ml-auto w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"
                        ></div>
                    </button>
                </div>

                <!-- Submenu normal -->
                <div
                    v-show="(sidebarOpen || isMobile) && openSubmenus.configuracoes"
                    class="ml-8 mt-1 space-y-1 border-l border-white/10 dark:border-white/5 pl-3"
                >
                    <button
                        v-for="item in menuItems.configuracoes.items"
                        :key="item.route"
                        @click="navigateWithLoading(route(item.route), item.name)"
                        class="w-full text-left px-3 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/10 text-sm transition-all relative flex items-center gap-2"
                        :class="{ 'bg-white/5 text-white': route().current(item.route) }"
                    >
                        <span>{{ item.icon }}</span>
                        <span>{{ item.name }}</span>
                        <div
                            v-if="route().current(item.route)"
                            class="absolute left-0 top-1/2 transform -translate-y-1/2 w-1 h-4 bg-green-500 rounded-full animate-pulse"
                        ></div>
                    </button>
                </div>
            </div>

        </nav>
    </aside>

    <!-- ================= CONTEÚDO PRINCIPAL ================= -->
    <div
        :class="[
            'flex-1 flex flex-col min-h-screen transition-all duration-300',
            !isMobile && sidebarOpen ? 'md:ml-64' : '',
            !isMobile && !sidebarOpen ? 'md:ml-20' : '',
            isMobile ? 'ml-0' : ''
        ]"
    >

        <!-- TOP BAR MODERNO -->
        <header class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-lg sticky top-0 z-20 border-b border-gray-200/50 dark:border-gray-700/50 transition-colors duration-300">
            <div class="flex items-center justify-between h-16 px-4">

                <!-- Botão toggle sidebar -->
                <button
                    @click="toggleSidebar"
                    class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200 focus:outline-none group"
                    :title="(isMobile ? mobileMenuOpen : sidebarOpen) ? 'Fechar menu' : 'Abrir menu'"
                >
                    <div class="relative w-6 h-6 flex items-center justify-center">
                        <div class="absolute inset-0 flex flex-col items-center justify-center gap-1.5 transition-all duration-300">
                            <span
                                class="block h-0.5 bg-gray-600 dark:bg-gray-300 transition-all duration-300 origin-center"
                                :class="[
                                    (isMobile ? mobileMenuOpen : sidebarOpen) ? 'w-6 rotate-45 translate-y-2' : 'w-6'
                                ]"
                            ></span>
                            <span
                                class="block h-0.5 bg-gray-600 dark:bg-gray-300 transition-all duration-300"
                                :class="[
                                    (isMobile ? mobileMenuOpen : sidebarOpen) ? 'w-0 opacity-0' : 'w-6'
                                ]"
                            ></span>
                            <span
                                class="block h-0.5 bg-gray-600 dark:bg-gray-300 transition-all duration-300 origin-center"
                                :class="[
                                    (isMobile ? mobileMenuOpen : sidebarOpen) ? 'w-6 -rotate-45 -translate-y-2' : 'w-6'
                                ]"
                            ></span>
                        </div>
                    </div>
                </button>

                <!-- Título da página -->
                <div class="text-gray-800 dark:text-gray-200 font-semibold text-lg hidden md:block">
                    <slot name="header"></slot>
                </div>

                <!-- Informações do usuário -->
                <div class="flex items-center space-x-4">

                    <!-- Botão Dark Mode -->
                    <button
                        @click="toggleDark"
                        class="relative p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all group"
                        :title="darkMode ? 'Modo Claro' : 'Modo Escuro'"
                    >
                        <svg v-if="darkMode" class="w-5 h-5 text-yellow-400 group-hover:animate-spin" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
                        </svg>
                        <svg v-else class="w-5 h-5 group-hover:animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                        </svg>
                    </button>

                    <!-- Notificações -->
                    <button class="relative p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all group">
                        <svg class="w-5 h-5 group-hover:animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full animate-ping"></span>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>

                    <!-- Dropdown do usuário -->
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white focus:outline-none group">
                                <div class="relative">
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold">
                                        {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 rounded-full border-2 border-white dark:border-gray-800 animate-pulse"></div>
                                </div>
                                <span class="hidden md:inline-block font-medium">{{ $page.props.auth.user.name }}</span>
                                <svg class="w-4 h-4 text-gray-400 group-hover:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </template>

                        <template #content>
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $page.props.auth.user.name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $page.props.auth.user.email }}</p>
                            </div>
                            <DropdownLink :href="route('profile.edit')" class="flex items-center gap-2 dark:text-gray-300 dark:hover:text-white">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Meu Perfil
                            </DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button" class="flex items-center gap-2 text-red-600 dark:text-red-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Sair
                            </DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </div>
        </header>

        <!-- PAGE CONTENT -->
        <main class="flex-1 p-4">
            <div class="animate-fadeIn">
                <slot />
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-t border-gray-200 dark:border-gray-700 py-4 px-6 text-center text-sm text-gray-500 dark:text-gray-400 transition-colors duration-300">
            © {{ new Date().getFullYear() }} ERP System. Todos os direitos reservados.
        </footer>

    </div>

    <!-- Overlay para mobile -->
    <div
        v-if="mobileMenuOpen && isMobile"
        @click="closeMobileMenu"
        class="fixed inset-0 bg-black/50 z-20"
    ></div>

</div>
</template>

<style scoped>
/* Animações personalizadas */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

@keyframes ping {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    75%, 100% {
        transform: scale(2);
        opacity: 0;
    }
}

@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-5px);
    }
}

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

.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}

.animate-spin {
    animation: spin 1s linear infinite;
}

.animate-ping {
    animation: ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;
}

.animate-bounce {
    animation: bounce 0.5s ease infinite;
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Scrollbar personalizada para a sidebar */
.overflow-y-auto::-webkit-scrollbar {
    width: 5px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}

/* Transições suaves */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 300ms;
}

/* Efeito de hover nos links */
.group:hover .group-hover\:opacity-100 {
    opacity: 1;
}

.group:hover .group-hover\:animate-bounce {
    animation: bounce 0.5s ease infinite;
}

.group:hover .group-hover\:rotate-180 {
    transform: rotate(180deg);
}

/* Dark mode - Scrollbar */
.dark .overflow-y-auto::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Dark mode - Overlay */
.dark .bg-white\/80 {
    background-color: rgba(31, 41, 55, 0.8);
}

.dark .bg-gray-50 {
    background-color: rgb(17, 24, 39);
}
</style>
