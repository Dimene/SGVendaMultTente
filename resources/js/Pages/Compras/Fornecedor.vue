=<script setup lang="js">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { onMounted, ref, watch, computed } from 'vue'
import axios from 'axios'

// ==================== ESTADO ====================
const contactos = ref([
    { id: 1, tipo: 'telefone', valor: '', principal: true }
])

const distrito = ref("")
const provinciaref = ref("")
const datalistDistrotos = ref()
const status = ref('ativo')
const errors = ref({})
const buscando = ref(false)
const notificacao = ref({
    show: false,
    mensagem: '',
    tipo: 'info'
})

// ==================== EMITS & PROPS ====================
const emit = defineEmits(['update:modelValue'])

const props = defineProps({
    modelValue: Boolean,
    compras: Array,
    categoria: Array,
    fornecedor: Array,
    distritos: Array,
    provincia: Array,
})

// ==================== CONTACTOS ====================
const adicionarContacto = () => {
    const novoId = contactos.value.length + 1
    contactos.value.push({
        id: novoId,
        tipo: 'telefone',
        valor: '',
        principal: false
    })
}

const removerContacto = (id) => {
    if (contactos.value.length > 1) {
        contactos.value = contactos.value.filter(contacto => contacto.id !== id)
    }
}

const definirPrincipal = (id) => {
    contactos.value.forEach(contacto => {
        contacto.principal = contacto.id === id
    })
}

function fecharModal() {
    emit('update:modelValue', false)
}

// ==================== FORM ====================
const form = useForm({
    nome: '',
    nuit: '',
    email: '',
    contactos: [],
    endereco: '',
    distrito: '',
    provincia: '',
    status: 'ativo',
    observacoes: ''
})

// Computed para sincronizar contactos
const contactosParaEnvio = computed(() => {
    return contactos.value.map(c => ({
        tipo: c.tipo,
        valor: c.valor,
        principal: c.principal
    }))
})

// ==================== WATCHERS ====================
// Watch para distrito → província
watch(distrito, (novoDistrito) => {
    if (!novoDistrito || novoDistrito.trim() === '') {
        provinciaref.value = ''
        return
    }

    const distritoEncontrado = props.distritos?.find(
        d => d.Nome?.toLowerCase() === novoDistrito.toLowerCase()
    )

    if (distritoEncontrado) {
        const provinciaEncontrada = props.provincia?.find(
            p => p.id === distritoEncontrado.provincia_id
        )
        provinciaref.value = provinciaEncontrada?.Nome || ''
    } else {
        provinciaref.value = ''
    }
})

watch(provinciaref, (novaProvincia) => {
    if (!novaProvincia || novaProvincia.trim() === '') return

    const provinciaEncontrada = props.provincia?.find(
        p => p.Nome?.toLowerCase() === novaProvincia.toLowerCase()
    )

    if (!provinciaEncontrada) {
        provinciaref.value = ''
    }
})

// Reset quando modal fecha
watch(() => props.modelValue, (newValue) => {
    if (!newValue) {
        distrito.value = ''
        provinciaref.value = ''
        status.value = 'ativo'
        contactos.value = [
            { id: 1, tipo: 'telefone', valor: '', principal: true }
        ]
        errors.value = {}
        notificacao.value.show = false
        form.reset()
    }
})

// Sincronizar contactos com form
watch(contactos, (newContactos) => {
    form.contactos = newContactos.map(c => ({
        tipo: c.tipo,
        valor: c.valor,
        principal: c.principal
    }))
}, { deep: true })

// Sincronizar distrito e província com form
watch(distrito, (newDistrito) => {
    form.distrito = newDistrito
})

watch(provinciaref, (newProvincia) => {
    form.provincia = newProvincia
})

// ==================== NOTIFICAÇÕES ====================
const mostrarNotificacao = (mensagem, tipo = 'info') => {
    notificacao.value = {
        show: true,
        mensagem,
        tipo
    }

    // Auto-fechar após 5 segundos
    setTimeout(() => {
        notificacao.value.show = false
    }, 5000)
}

// ==================== VALIDAÇÃO ====================
const validarFormulario = () => {
    errors.value = {}
    let isValid = true

    // Validar Nome
    if (!form.nome || form.nome.trim() === '') {
        errors.value.nome = 'O nome do fornecedor é obrigatório'
        isValid = false
    } else if (form.nome.length < 3) {
        errors.value.nome = 'O nome deve ter pelo menos 3 caracteres'
        isValid = false
    }

    // Validar NUIT
    if (!form.nuit || form.nuit.trim() === '') {
        errors.value.nuit = 'O NUIT é obrigatório'
        isValid = false
    } else if (!/^\d{9}$/.test(form.nuit)) {
        errors.value.nuit = 'O NUIT deve ter exatamente 9 dígitos'
        isValid = false
    }

    // Validar Email
    if (form.email && form.email.trim() !== '') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        if (!emailRegex.test(form.email)) {
            errors.value.email = 'Por favor, insira um e-mail válido'
            isValid = false
        }
    }

    // Validar Contactos
    const contactosValidos = contactos.value.filter(c => c.valor && c.valor.trim() !== '')
    if (contactosValidos.length === 0) {
        errors.value.contactos = 'Adicione pelo menos um contacto válido'
        isValid = false
    } else {
        for (const contacto of contactos.value) {
            if (!contacto.valor || contacto.valor.trim() === '') {
                errors.value.contactos = 'Todos os contactos devem ter um valor preenchido'
                isValid = false
                break
            }
        }
    }

    // Validar Distrito
    if (!distrito.value || distrito.value.trim() === '') {
        errors.value.distrito = 'Selecione um distrito'
        isValid = false
    }

    // Validar Província
    if (!provinciaref.value || provinciaref.value.trim() === '') {
        errors.value.provincia = 'A província é obrigatória (selecione um distrito válido)'
        isValid = false
    }

    // Validar Endereço
    if (form.endereco && form.endereco.length < 5) {
        errors.value.endereco = 'O endereço deve ter pelo menos 5 caracteres'
        isValid = false
    }

    return isValid
}

// ==================== BUSCAR FORNECEDOR ====================
const buscarFornecedor = async () => {
    const termo = (form.nome || form.nuit || form.email || '').trim()

    if (!termo) {
        mostrarNotificacao('Digite um nome, NUIT ou e-mail para buscar', 'warning')
        return
    }

    buscando.value = true

    try {
        const res = await axios.get(route('fornecedor.show', encodeURIComponent(termo)))

        if (res.data) {
            const f = res.data

            form.nome = f.nome || ''
            form.nuit = f.documento || ''
            form.email = f.email || ''
            form.endereco = f.endereco || ''

            distrito.value = f.distrito?.Nome || ''
            provinciaref.value = f.provincia?.Nome || ''

            contactos.value = (f.contactos || []).map(c => ({
                id: c.id || Date.now() + Math.random(),
                valor: c.valor || '',
                tipo: c.tipocontacto?.Descricao?.toLowerCase() || 'telefone',
                principal: c.principal ?? false
            }))

            mostrarNotificacao('Fornecedor encontrado com sucesso!', 'success')
        }
    } catch (error) {
        console.error('Erro na busca:', error)
        mostrarNotificacao('Fornecedor não encontrado. Verifique os dados informados.', 'error')
    } finally {
        buscando.value = false
    }
}

// ==================== GUARDAR FORNECEDOR ====================
const guardarFornecedor = () => {
    errors.value = {}

    if (!validarFormulario()) {
        const primeiroErro = document.querySelector('.text-red-500')
        if (primeiroErro) {
            primeiroErro.scrollIntoView({ behavior: 'smooth', block: 'center' })
        }
        return
    }

    form.contactos = contactos.value.map(c => ({
        tipo: c.tipo,
        valor: c.valor,
        principal: c.principal
    }))

    form.status = status.value
    form.distrito = distrito.value
    form.provincia = provinciaref.value

    form.post(route('fornecedor.store'), {
        onSuccess: () => {
            mostrarNotificacao('Fornecedor guardado com sucesso!', 'success')
            fecharModal()
            form.reset()
            contactos.value = [
                { id: 1, tipo: 'telefone', valor: '', principal: true }
            ]
            distrito.value = ''
            provinciaref.value = ''
            status.value = 'ativo'
            errors.value = {}
        },
        onError: (error) => {
            errors.value = error
            mostrarNotificacao('Erro ao guardar fornecedor. Verifique os dados.', 'error')
        }
    })
}

// ==================== HANDLERS ====================
const handleBlur = () => {
    validarFormulario()
}

// ==================== LIFECYCLE ====================
onMounted(() => {
    datalistDistrotos.value = props.distritos
})
</script>

<template>
    <!-- MODAL com suporte a dark mode -->
    <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 dark:bg-black/70 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto transition-colors">

            <!-- Cabeçalho -->
            <div class="sticky top-0 z-10 flex items-center justify-between p-6 transition-colors bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-100 rounded-lg dark:bg-blue-900/50">
                        <i class="text-blue-600 fas fa-truck dark:text-blue-400"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Novo Registo de Fornecedor</h2>
                </div>
                <button
                    @click="fecharModal"
                    class="p-2 text-gray-400 transition-colors rounded-lg hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                    <i class="text-xl fas fa-times"></i>
                </button>
            </div>

            <!-- Notificação -->
            <div
                v-if="notificacao.show"
                class="flex items-center gap-3 p-4 mx-6 mt-4 border rounded-lg"
                :class="{
                    'bg-green-50 border-green-300 text-green-800 dark:bg-green-900/30 dark:border-green-700 dark:text-green-300': notificacao.tipo === 'success',
                    'bg-red-50 border-red-300 text-red-800 dark:bg-red-900/30 dark:border-red-700 dark:text-red-300': notificacao.tipo === 'error',
                    'bg-yellow-50 border-yellow-300 text-yellow-800 dark:bg-yellow-900/30 dark:border-yellow-700 dark:text-yellow-300': notificacao.tipo === 'warning',
                    'bg-blue-50 border-blue-300 text-blue-800 dark:bg-blue-900/30 dark:border-blue-700 dark:text-blue-300': notificacao.tipo === 'info'
                }"
            >
                <i
                    class="text-xl"
                    :class="{
                        'fas fa-check-circle text-green-500': notificacao.tipo === 'success',
                        'fas fa-exclamation-circle text-red-500': notificacao.tipo === 'error',
                        'fas fa-exclamation-triangle text-yellow-500': notificacao.tipo === 'warning',
                        'fas fa-info-circle text-blue-500': notificacao.tipo === 'info'
                    }"
                ></i>
                <span class="flex-1">{{ notificacao.mensagem }}</span>
                <button @click="notificacao.show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Corpo -->
            <div class="p-6">
                <!-- Alert de Erros Gerais -->
                <div v-if="Object.keys(errors).length > 0" class="p-3 mb-4 border border-red-200 rounded-lg bg-red-50 dark:bg-red-900/30 dark:border-red-700">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-medium text-red-800 dark:text-red-300">Por favor, corrija os seguintes erros:</p>
                            <ul class="mt-1 text-sm text-red-700 list-disc list-inside dark:text-red-400">
                                <li v-for="(error, key) in errors" :key="key">{{ error }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Botão de Busca -->
                <div class="flex justify-end mb-4">
                    <button
                        @click="buscarFornecedor"
                        :disabled="buscando"
                        class="flex items-center gap-2 px-4 py-2 font-medium text-white transition-all bg-blue-600 rounded-lg hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i v-if="buscando" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-search"></i>
                        {{ buscando ? 'Buscando...' : 'Buscar Fornecedor' }}
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <!-- Nome -->
                    <div class="col-span-2">
                        <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                            <i class="mr-2 text-blue-500 fas fa-building"></i>
                            Nome do Fornecedor
                            <span class="ml-1 text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="form.nome"
                            placeholder="Ex: Solotec, Lda"
                            :class="[
                                'w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all',
                                'bg-white dark:bg-gray-700 text-gray-900 dark:text-white',
                                errors.nome ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'
                            ]"
                            @blur="handleBlur"
                            @keyup.enter="buscarFornecedor"
                        >
                        <p v-if="errors.nome" class="mt-1 text-sm text-red-500">
                            <i class="mr-1 fas fa-exclamation-circle"></i>
                            {{ errors.nome }}
                        </p>
                    </div>

                    <!-- NUIT -->
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                            <i class="mr-2 text-blue-500 fas fa-id-card"></i>
                            NUIT
                            <span class="ml-1 text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.nuit"
                            type="text"
                            placeholder="000000001"
                            maxlength="9"
                            :class="[
                                'w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all',
                                'bg-white dark:bg-gray-700 text-gray-900 dark:text-white',
                                errors.nuit ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'
                            ]"
                            @blur="handleBlur"
                            @keyup.enter="buscarFornecedor"
                        >
                        <p v-if="errors.nuit" class="mt-1 text-sm text-red-500">
                            <i class="mr-1 fas fa-exclamation-circle"></i>
                            {{ errors.nuit }}
                        </p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                            <i class="mr-2 text-blue-500 fas fa-envelope"></i>
                            E-mail
                        </label>
                        <input
                            v-model="form.email"
                            type="email"
                            placeholder="fornecedor@empresa.com"
                            :class="[
                                'w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all',
                                'bg-white dark:bg-gray-700 text-gray-900 dark:text-white',
                                errors.email ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'
                            ]"
                            @blur="handleBlur"
                            @keyup.enter="buscarFornecedor"
                        >
                        <p v-if="errors.email" class="mt-1 text-sm text-red-500">
                            <i class="mr-1 fas fa-exclamation-circle"></i>
                            {{ errors.email }}
                        </p>
                    </div>

                    <!-- Contactos -->
                    <div class="col-span-2">
                        <div class="flex items-center justify-between mb-3">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="mr-2 text-blue-500 fas fa-phone-alt"></i>
                                Contactos
                                <span class="ml-1 text-red-500">*</span>
                            </label>
                            <button
                                @click="adicionarContacto"
                                type="button"
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-600 hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-800 text-white text-sm font-medium rounded-lg transition-all duration-200"
                            >
                                <i class="fas fa-plus-circle"></i>
                                Adicionar Contacto
                            </button>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="contacto in contactos"
                                :key="contacto.id"
                                class="flex items-start gap-3 p-3 transition-all border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-700/50 dark:border-gray-600 hover:border-blue-300 dark:hover:border-blue-500"
                                :class="{ 'border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-700': errors.contactos && contacto.valor === '' }"
                            >
                                <div class="flex-1">
                                    <select
                                        v-model="contacto.tipo"
                                        class="w-full px-3 py-2 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg dark:border-gray-600 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option value="telefone">📱 Telefone</option>
                                        <option value="celular">📞 Celular</option>
                                        <option value="whatsapp">💬 WhatsApp</option>
                                        <option value="fax">📠 Fax</option>
                                    </select>
                                </div>

                                <div class="flex-[2]">
                                    <input
                                        v-model="contacto.valor"
                                        type="text"
                                        :placeholder="`Número de ${contacto.tipo}`"
                                        :class="[
                                            'w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm',
                                            'bg-white dark:bg-gray-700 text-gray-900 dark:text-white',
                                            errors.contactos && contacto.valor === '' ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'
                                        ]"
                                        @blur="validarFormulario"
                                    >
                                </div>

                                <div class="flex items-center gap-1">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input
                                            type="radio"
                                            name="contacto_principal"
                                            :checked="contacto.principal"
                                            @change="definirPrincipal(contacto.id)"
                                            class="w-4 h-4 text-blue-600 form-radio"
                                        >
                                        <span class="ml-1 text-xs text-gray-600 dark:text-gray-400">Principal</span>
                                    </label>
                                </div>

                                <div>
                                    <button
                                        @click="removerContacto(contacto.id)"
                                        :disabled="contactos.length === 1"
                                        class="p-2 text-red-500 transition-all rounded-lg hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p v-if="errors.contactos" class="mt-1 text-sm text-red-500">
                            <i class="mr-1 fas fa-exclamation-circle"></i>
                            {{ errors.contactos }}
                        </p>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            <i class="fas fa-info-circle"></i>
                            Adicione pelo menos um contacto. O contacto principal será o preferencial.
                        </p>
                    </div>

                    <!-- Endereço -->
                    <div class="col-span-2">
                        <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                            <i class="mr-2 text-blue-500 fas fa-map-marker-alt"></i>
                            Endereço
                        </label>
                        <input
                            v-model="form.endereco"
                            type="text"
                            placeholder="Av. 25 de Setembro, nº 123"
                            :class="[
                                'w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all',
                                'bg-white dark:bg-gray-700 text-gray-900 dark:text-white',
                                errors.endereco ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'
                            ]"
                            @blur="validarFormulario"
                        >
                        <p v-if="errors.endereco" class="mt-1 text-sm text-red-500">
                            <i class="mr-1 fas fa-exclamation-circle"></i>
                            {{ errors.endereco }}
                        </p>
                    </div>

                    <!-- Distrito -->
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                            <i class="mr-2 text-blue-500 fas fa-city"></i>
                            Distrito
                            <span class="ml-1 text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            placeholder="Digite o distrito"
                            :class="[
                                'w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all',
                                'bg-white dark:bg-gray-700 text-gray-900 dark:text-white',
                                errors.distrito ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'
                            ]"
                            list="distritos"
                            v-model="distrito"
                            @blur="validarFormulario"
                        >
                        <datalist id="distritos">
                            <option v-for="distritosItem in datalistDistrotos" :key="distritosItem.id" :value="distritosItem.Nome">
                                {{ distritosItem.Nome }}
                            </option>
                        </datalist>
                        <p v-if="errors.distrito" class="mt-1 text-sm text-red-500">
                            <i class="mr-1 fas fa-exclamation-circle"></i>
                            {{ errors.distrito }}
                        </p>
                    </div>

                    <!-- Província -->
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                            <i class="mr-2 text-blue-500 fas fa-map"></i>
                            Província
                            <span class="ml-1 text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="provinciaref"
                            list="provincias"
                            :class="[
                                'w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all',
                                'bg-white dark:bg-gray-700 text-gray-900 dark:text-white',
                                errors.provincia ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'
                            ]"
                            placeholder="Província (auto-preenchido)"
                            readonly
                        >
                        <datalist id="provincias">
                            <option v-for="provinciaItem in provincia" :key="provinciaItem.id" :value="provinciaItem.Nome">
                                {{ provinciaItem.Nome }}
                            </option>
                        </datalist>
                        <p v-if="errors.provincia" class="mt-1 text-sm text-red-500">
                            <i class="mr-1 fas fa-exclamation-circle"></i>
                            {{ errors.provincia }}
                        </p>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                            <i class="mr-2 text-blue-500 fas fa-toggle-on"></i>
                            Status
                        </label>
                        <div class="flex items-center gap-4 mt-2">
                            <label class="inline-flex items-center">
                                <input
                                    type="radio"
                                    value="ativo"
                                    v-model="status"
                                    class="text-blue-600 form-radio"
                                >
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    <i class="mr-1 text-green-500 fas fa-check-circle"></i>
                                    Activo
                                </span>
                            </label>
                            <label class="inline-flex items-center">
                                <input
                                    type="radio"
                                    value="inativo"
                                    v-model="status"
                                    class="text-gray-400 form-radio"
                                >
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    <i class="mr-1 text-red-500 fas fa-ban"></i>
                                    Inactivo
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Observações -->
                    <div class="col-span-2">
                        <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                            <i class="mr-2 text-blue-500 fas fa-comment-dots"></i>
                            Observações
                        </label>
                        <textarea
                            v-model="form.observacoes"
                            rows="3"
                            placeholder="Informações adicionais sobre o fornecedor..."
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Rodapé -->
            <div class="sticky bottom-0 flex justify-end gap-3 p-6 transition-colors border-t border-gray-200 bg-gray-50 dark:bg-gray-800/80 dark:border-gray-700 rounded-b-xl">
                <button
                    @click="fecharModal"
                    class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-medium rounded-lg transition-all duration-200 flex items-center gap-2"
                >
                    <i class="fas fa-times"></i>
                    Cancelar
                </button>
                <button
                    @click="guardarFornecedor"
                    :disabled="form.processing"
                    class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 dark:from-blue-700 dark:to-blue-800 dark:hover:from-blue-800 dark:hover:to-blue-900 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                    <i v-else class="fas fa-save"></i>
                    {{ form.processing ? 'A guardar...' : 'Guardar Fornecedor' }}
                </button>
            </div>

        </div>
    </div>
</template>