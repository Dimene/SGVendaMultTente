<script setup lang="js">
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
    // Limpa espaços extras e normaliza o termo de busca
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

            // Preencher dados do fornecedor
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

        // Limpar campos se não encontrado (opcional)
        // form.nome = ''
        // form.nuit = ''
        // form.email = ''
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

    // Atualizar form com dados mais recentes
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
    <!-- MODAL -->
    <div v-if="modelValue" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">

            <!-- Cabeçalho -->
            <div class="sticky top-0 bg-white z-10 flex items-center justify-between p-6 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-100 rounded-lg p-2">
                        <i class="fas fa-truck text-blue-600"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Novo Registo de Fornecedor</h2>
                </div>
                <button
                    @click="fecharModal"
                    class="text-gray-400 hover:text-gray-600 transition-colors hover:bg-gray-100 rounded-lg p-2"
                >
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Notificação -->
            <div
                v-if="notificacao.show"
                class="mx-6 mt-4 p-4 rounded-lg border flex items-center gap-3"
                :class="{
                    'bg-green-50 border-green-300 text-green-800': notificacao.tipo === 'success',
                    'bg-red-50 border-red-300 text-red-800': notificacao.tipo === 'error',
                    'bg-yellow-50 border-yellow-300 text-yellow-800': notificacao.tipo === 'warning',
                    'bg-blue-50 border-blue-300 text-blue-800': notificacao.tipo === 'info'
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
                <button @click="notificacao.show = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Corpo -->
            <div class="p-6">
                <!-- Alert de Erros Gerais -->
                <div v-if="Object.keys(errors).length > 0" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-medium text-red-800">Por favor, corrija os seguintes erros:</p>
                            <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
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
                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i v-if="buscando" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-search"></i>
                        {{ buscando ? 'Buscando...' : 'Buscar Fornecedor' }}
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Nome -->
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-building mr-2 text-blue-500"></i>
                            Nome do Fornecedor
                            <span class="text-red-500 ml-1">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="form.nome"
                            placeholder="Ex: Solotec, Lda"
                            :class="[
                                'w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all',
                                errors.nome ? 'border-red-500' : 'border-gray-300'
                            ]"
                            @blur="handleBlur"
                            @keyup.enter="buscarFornecedor"
                        >
                        <p v-if="errors.nome" class="mt-1 text-sm text-red-500">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            {{ errors.nome }}
                        </p>
                    </div>

                    <!-- NUIT -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-id-card mr-2 text-blue-500"></i>
                            NUIT
                            <span class="text-red-500 ml-1">*</span>
                        </label>
                        <input
                            v-model="form.nuit"
                            type="text"
                            placeholder="000000001"
                            maxlength="9"
                            :class="[
                                'w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all',
                                errors.nuit ? 'border-red-500' : 'border-gray-300'
                            ]"
                            @blur="handleBlur"
                            @keyup.enter="buscarFornecedor"
                        >
                        <p v-if="errors.nuit" class="mt-1 text-sm text-red-500">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            {{ errors.nuit }}
                        </p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2 text-blue-500"></i>
                            E-mail
                        </label>
                        <input
                            v-model="form.email"
                            type="email"
                            placeholder="fornecedor@empresa.com"
                            :class="[
                                'w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all',
                                errors.email ? 'border-red-500' : 'border-gray-300'
                            ]"
                            @blur="handleBlur"
                            @keyup.enter="buscarFornecedor"
                        >
                        <p v-if="errors.email" class="mt-1 text-sm text-red-500">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            {{ errors.email }}
                        </p>
                    </div>

                    <!-- Contactos -->
                    <div class="col-span-2">
                        <div class="flex items-center justify-between mb-3">
                            <label class="block text-sm font-semibold text-gray-700">
                                <i class="fas fa-phone-alt mr-2 text-blue-500"></i>
                                Contactos
                                <span class="text-red-500 ml-1">*</span>
                            </label>
                            <button
                                @click="adicionarContacto"
                                type="button"
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-all duration-200"
                            >
                                <i class="fas fa-plus-circle"></i>
                                Adicionar Contacto
                            </button>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="contacto in contactos"
                                :key="contacto.id"
                                class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200 hover:border-blue-300 transition-all"
                                :class="{ 'border-red-300 bg-red-50': errors.contactos && contacto.valor === '' }"
                            >
                                <div class="flex-1">
                                    <select
                                        v-model="contacto.tipo"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
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
                                            errors.contactos && contacto.valor === '' ? 'border-red-500' : 'border-gray-300'
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
                                            class="form-radio text-blue-600 w-4 h-4"
                                        >
                                        <span class="ml-1 text-xs text-gray-600">Principal</span>
                                    </label>
                                </div>

                                <div>
                                    <button
                                        @click="removerContacto(contacto.id)"
                                        :disabled="contactos.length === 1"
                                        class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p v-if="errors.contactos" class="mt-1 text-sm text-red-500">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            {{ errors.contactos }}
                        </p>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-info-circle"></i>
                            Adicione pelo menos um contacto. O contacto principal será o preferencial.
                        </p>
                    </div>

                    <!-- Endereço -->
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-map-marker-alt mr-2 text-blue-500"></i>
                            Endereço
                        </label>
                        <input
                            v-model="form.endereco"
                            type="text"
                            placeholder="Av. 25 de Setembro, nº 123"
                            :class="[
                                'w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all',
                                errors.endereco ? 'border-red-500' : 'border-gray-300'
                            ]"
                            @blur="validarFormulario"
                        >
                        <p v-if="errors.endereco" class="mt-1 text-sm text-red-500">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            {{ errors.endereco }}
                        </p>
                    </div>

                    <!-- Distrito -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-city mr-2 text-blue-500"></i>
                            Distrito
                            <span class="text-red-500 ml-1">*</span>
                        </label>
                        <input
                            type="text"
                            placeholder="Digite o distrito"
                            :class="[
                                'w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all',
                                errors.distrito ? 'border-red-500' : 'border-gray-300'
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
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            {{ errors.distrito }}
                        </p>
                    </div>

                    <!-- Província -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-map mr-2 text-blue-500"></i>
                            Província
                            <span class="text-red-500 ml-1">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="provinciaref"
                            list="provincias"
                            :class="[
                                'w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all',
                                errors.provincia ? 'border-red-500' : 'border-gray-300'
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
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            {{ errors.provincia }}
                        </p>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-toggle-on mr-2 text-blue-500"></i>
                            Status
                        </label>
                        <div class="flex items-center gap-4 mt-2">
                            <label class="inline-flex items-center">
                                <input
                                    type="radio"
                                    value="ativo"
                                    v-model="status"
                                    class="form-radio text-blue-600"
                                >
                                <span class="ml-2 text-sm text-gray-700">
                                    <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                    Activo
                                </span>
                            </label>
                            <label class="inline-flex items-center">
                                <input
                                    type="radio"
                                    value="inativo"
                                    v-model="status"
                                    class="form-radio text-gray-400"
                                >
                                <span class="ml-2 text-sm text-gray-700">
                                    <i class="fas fa-ban text-red-500 mr-1"></i>
                                    Inactivo
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Observações -->
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-comment-dots mr-2 text-blue-500"></i>
                            Observações
                        </label>
                        <textarea
                            v-model="form.observacoes"
                            rows="3"
                            placeholder="Informações adicionais sobre o fornecedor..."
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Rodapé -->
            <div class="sticky bottom-0 bg-gray-50 flex justify-end gap-3 p-6 border-t border-gray-200 rounded-b-xl">
                <button
                    @click="fecharModal"
                    class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition-all duration-200 flex items-center gap-2"
                >
                    <i class="fas fa-times"></i>
                    Cancelar
                </button>
                <button
                    @click="guardarFornecedor"
                    :disabled="form.processing"
                    class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                    <i v-else class="fas fa-save"></i>
                    {{ form.processing ? 'A guardar...' : 'Guardar Fornecedor' }}
                </button>
            </div>

        </div>
    </div>
</template>
