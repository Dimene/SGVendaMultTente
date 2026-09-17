<script setup>
import { computed, ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    fornecedores: { type: Array, default: () => [] },
    distritos: { type: Array, default: () => [] },
    provincias: { type: Array, default: () => [] },
    tiposContacto: { type: Array, default: () => [] }
})

const pesquisa = ref('')
const filtroEstado = ref('')
const modalAberto = ref(false)
const fornecedorEditando = ref(null)

const novoContacto = () => ({ tipo_id: props.tiposContacto[0]?.id || '', valor: '' })
const form = useForm({
    nome: '', nuit: '', email: '', endereco: '', distrito: '', provincia: '',
    observacoes: '', ativo: true, contactos: [novoContacto()]
})

const fornecedoresFiltrados = computed(() => props.fornecedores.filter((fornecedor) => {
    const termo = pesquisa.value.trim().toLowerCase()
    const correspondeTexto = !termo || [fornecedor.nome, fornecedor.documento, fornecedor.email]
        .some(valor => String(valor || '').toLowerCase().includes(termo))
    const correspondeEstado = filtroEstado.value === '' || String(Number(fornecedor.ativo)) === filtroEstado.value
    return correspondeTexto && correspondeEstado
}))

function abrirNovo() {
    fornecedorEditando.value = null
    form.reset()
    form.clearErrors()
    form.ativo = true
    form.contactos = [novoContacto()]
    modalAberto.value = true
}

function editar(fornecedor) {
    fornecedorEditando.value = fornecedor
    form.clearErrors()
    form.nome = fornecedor.nome || ''
    form.nuit = fornecedor.documento || ''
    form.email = fornecedor.email || ''
    form.endereco = fornecedor.endereco || ''
    form.distrito = fornecedor.distrito?.id || fornecedor.cidade || ''
    form.provincia = fornecedor.provincia?.id || fornecedor.provincia || ''
    form.observacoes = fornecedor.observacoes || ''
    form.ativo = Boolean(fornecedor.ativo)
    form.contactos = fornecedor.contactos?.length
        ? fornecedor.contactos.map(contacto => ({ tipo_id: contacto.tipo_id, valor: contacto.valor }))
        : [novoContacto()]
    modalAberto.value = true
}

function fechar() {
    modalAberto.value = false
    form.reset()
    form.clearErrors()
}

function adicionarContacto() {
    form.contactos.push(novoContacto())
}

function removerContacto(index) {
    if (form.contactos.length > 1) form.contactos.splice(index, 1)
}

function guardar() {
    const opcoes = { onSuccess: fechar }
    if (fornecedorEditando.value) {
        form.put(route('fornecedor.update', fornecedorEditando.value.id), opcoes)
    } else {
        form.post(route('fornecedor.store'), opcoes)
    }
}

function excluir(fornecedor) {
    if (!window.confirm(`Excluir o fornecedor ${fornecedor.nome}?`)) return
    useForm({}).delete(route('fornecedor.destroy', fornecedor.id))
}

function alternarEstado(fornecedor) {
    const dados = {
        nome: fornecedor.nome,
        nuit: fornecedor.documento,
        email: fornecedor.email,
        endereco: fornecedor.endereco,
        distrito: fornecedor.cidade || fornecedor.distrito?.id,
        provincia: fornecedor.provincia || fornecedor.provincia?.id,
        observacoes: fornecedor.observacoes,
        ativo: !fornecedor.ativo,
        contactos: fornecedor.contactos?.map(contacto => ({ tipo_id: contacto.tipo_id, valor: contacto.valor })) || [novoContacto()]
    }
    useForm(dados).put(route('fornecedor.update', fornecedor.id))
}
</script>

<template>
    <Head title="Fornecedores" />
    <AuthenticatedLayout>
        <div class="supplier-page">
            <header class="page-header">
                <div>
                    <p class="eyebrow">Compras</p>
                    <h1>Fornecedores</h1>
                    <p>Cadastre e mantenha os dados dos seus fornecedores.</p>
                </div>
                <button class="primary-button" type="button" @click="abrirNovo">
                    <i class="fas fa-plus" aria-hidden="true"></i> Novo fornecedor
                </button>
            </header>

            <section class="toolbar">
                <label class="search-field">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <input v-model="pesquisa" type="search" placeholder="Pesquisar por nome, NUIT ou email" />
                </label>
                <select v-model="filtroEstado" aria-label="Filtrar por estado">
                    <option value="">Todos os estados</option>
                    <option value="1">Ativos</option>
                    <option value="0">Inativos</option>
                </select>
                <span class="counter">{{ fornecedoresFiltrados.length }} fornecedor(es)</span>
            </section>

            <section class="table-card">
                <div v-if="!fornecedoresFiltrados.length" class="empty-state">
                    <i class="fas fa-truck" aria-hidden="true"></i>
                    <strong>Nenhum fornecedor encontrado</strong>
                    <span>Cadastre um fornecedor ou ajuste a pesquisa.</span>
                </div>
                <div v-else class="table-scroll">
                    <table>
                        <thead><tr><th>Fornecedor</th><th>NUIT</th><th>Contacto</th><th>Localização</th><th>Estado</th><th class="actions-column">Ações</th></tr></thead>
                        <tbody>
                            <tr v-for="fornecedor in fornecedoresFiltrados" :key="fornecedor.id">
                                <td><strong>{{ fornecedor.nome }}</strong><small>{{ fornecedor.email || 'Sem email' }}</small></td>
                                <td>{{ fornecedor.documento || '-' }}</td>
                                <td>{{ fornecedor.contactos?.[0]?.valor || '-' }}</td>
                                <td>{{ fornecedor.distrito?.Nome || '-' }}<small>{{ fornecedor.provincia?.Nome || '' }}</small></td>
                                <td><span :class="fornecedor.ativo ? 'status status-active' : 'status status-inactive'">{{ fornecedor.ativo ? 'Ativo' : 'Inativo' }}</span></td>
                                <td class="actions-column">
                                    <button class="icon-button" title="Editar fornecedor" @click="editar(fornecedor)"><i class="fas fa-pen" aria-hidden="true"></i></button>
                                    <button class="icon-button" title="Alterar estado" @click="alternarEstado(fornecedor)"><i class="fas fa-power-off" aria-hidden="true"></i></button>
                                    <button class="icon-button danger" title="Excluir fornecedor" @click="excluir(fornecedor)"><i class="fas fa-trash" aria-hidden="true"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <div v-if="modalAberto" class="modal-backdrop" @click.self="fechar">
                <section class="modal-card" role="dialog" aria-modal="true">
                    <header class="modal-header">
                        <div><p class="eyebrow">Cadastro</p><h2>{{ fornecedorEditando ? 'Editar fornecedor' : 'Novo fornecedor' }}</h2></div>
                        <button class="close-button" type="button" @click="fechar"><i class="fas fa-times" aria-hidden="true"></i></button>
                    </header>
                    <form @submit.prevent="guardar">
                        <div class="form-grid">
                            <label>Nome *<input v-model="form.nome" type="text" required /><small v-if="form.errors.nome">{{ form.errors.nome }}</small></label>
                            <label>NUIT *<input v-model="form.nuit" type="text" inputmode="numeric" maxlength="9" required /><small v-if="form.errors.nuit">{{ form.errors.nuit }}</small></label>
                            <label>Email<input v-model="form.email" type="email" /><small v-if="form.errors.email">{{ form.errors.email }}</small></label>
                            <label>Província *<select v-model="form.provincia" required><option value="">Selecione</option><option v-for="item in provincias" :key="item.id" :value="item.id">{{ item.Nome }}</option></select></label>
                            <label>Distrito *<select v-model="form.distrito" required><option value="">Selecione</option><option v-for="item in distritos.filter(item => !form.provincia || item.provincia_id == form.provincia)" :key="item.id" :value="item.id">{{ item.Nome }}</option></select></label>
                            <label>Endereço<input v-model="form.endereco" type="text" /></label>
                            <label class="full-width">Observações<textarea v-model="form.observacoes" rows="2"></textarea></label>
                        </div>
                        <div class="contacts-section">
                            <div class="section-title"><strong>Contactos</strong><button type="button" class="link-button" @click="adicionarContacto"><i class="fas fa-plus" aria-hidden="true"></i> Adicionar</button></div>
                            <div v-for="(contacto, index) in form.contactos" :key="index" class="contact-row">
                                <select v-model="contacto.tipo_id" required><option value="">Tipo</option><option v-for="tipo in tiposContacto" :key="tipo.id" :value="tipo.id">{{ tipo.Descricao }}</option></select>
                                <input v-model="contacto.valor" type="text" placeholder="Número ou contacto" required />
                                <button v-if="form.contactos.length > 1" type="button" class="icon-button danger" @click="removerContacto(index)"><i class="fas fa-trash" aria-hidden="true"></i></button>
                            </div>
                        </div>
                        <label class="check-line"><input v-model="form.ativo" type="checkbox" /> Fornecedor ativo</label>
                        <footer class="modal-footer"><button type="button" class="secondary-button" @click="fechar">Cancelar</button><button class="primary-button" type="submit" :disabled="form.processing">{{ form.processing ? 'A guardar...' : 'Guardar fornecedor' }}</button></footer>
                    </form>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.supplier-page { min-height: calc(100vh - 7rem); padding: 2rem clamp(1rem, 4vw, 3.5rem); color: #17324d; background: #f4f7f8; }
.page-header, .toolbar, .modal-header, .modal-footer, .section-title { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.eyebrow { margin: 0 0 .35rem; color: #ad5d3b; font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
h1 { margin: 0 0 .4rem; font: 500 clamp(2rem, 4vw, 3.2rem) Georgia, serif; } .page-header p:not(.eyebrow) { margin: 0; color: #6a7b88; }
.primary-button, .secondary-button, .icon-button, .link-button, .close-button { border: 0; border-radius: 4px; cursor: pointer; font-weight: 700; }
.primary-button { display: inline-flex; align-items: center; gap: .5rem; padding: .7rem 1rem; color: #fff; background: #ad5d3b; } .primary-button:hover { background: #914b30; }
.secondary-button { padding: .7rem 1rem; color: #17324d; background: #e5edef; }
.toolbar { margin: 2rem 0 1rem; padding: 1rem; border: 1px solid #dce5e8; border-radius: 6px; background: #fff; }
.search-field { display: flex; align-items: center; flex: 1; gap: .6rem; color: #ad5d3b; } .search-field input { width: 100%; border: 0; outline: 0; } .toolbar select { min-width: 10rem; }
.toolbar select, .filters select, .form-grid input, .form-grid select, .form-grid textarea, .contact-row input, .contact-row select { padding: .65rem; border: 1px solid #cbd8dd; border-radius: 4px; background: #fbfcfc; }
.counter { color: #6a7b88; font-size: .85rem; white-space: nowrap; }
.table-card, .modal-card { overflow: hidden; border: 1px solid #dce5e8; border-radius: 6px; background: #fff; } .table-scroll { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; min-width: 800px; } th, td { padding: .9rem; border-bottom: 1px solid #e6edef; text-align: left; white-space: nowrap; } th { color: #6a7b88; background: #f7fafb; font-size: .72rem; text-transform: uppercase; } td small { display: block; margin-top: .2rem; color: #6a7b88; font-size: .78rem; } tbody tr:hover { background: #fffaf6; }
.status { display: inline-flex; padding: .25rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 700; } .status-active { color: #286843; background: #e2f6e9; } .status-inactive { color: #a34040; background: #ffebeb; }
.actions-column { text-align: right; } .icon-button { width: 2rem; height: 2rem; margin-left: .25rem; color: #ad5d3b; background: #f1f5f6; } .icon-button:hover { background: #e5edef; } .icon-button.danger { color: #b44343; }
.empty-state { display: grid; justify-items: center; gap: .5rem; padding: 4rem 1rem; color: #6a7b88; } .empty-state i { color: #ad5d3b; font-size: 2rem; }
.modal-backdrop { position: fixed; inset: 0; z-index: 50; display: grid; place-items: center; padding: 1rem; background: rgb(0 0 0 / .55); } .modal-card { width: min(100%, 44rem); max-height: 90vh; overflow-y: auto; } .modal-header { padding: 1.25rem; border-bottom: 1px solid #e6edef; } .modal-header h2 { margin: 0; font-size: 1.25rem; } .close-button { width: 2rem; height: 2rem; color: #6a7b88; background: transparent; }
form { padding: 1.25rem; } .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; } .form-grid label { display: grid; gap: .4rem; color: #526675; font-size: .8rem; font-weight: 700; } .form-grid .full-width { grid-column: 1 / -1; } .form-grid small { color: #b44343; } .contacts-section { margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e6edef; } .contact-row { display: grid; grid-template-columns: 10rem 1fr 2rem; gap: .5rem; margin-top: .6rem; } .link-button { padding: .3rem; color: #ad5d3b; background: transparent; } .check-line { display: flex; align-items: center; gap: .5rem; margin-top: 1rem; color: #526675; font-size: .85rem; } .modal-footer { justify-content: flex-end; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e6edef; }
:global(.dark) .supplier-page { color: #f3f4f6; background: #17232d; } :global(.dark) .toolbar, :global(.dark) .table-card, :global(.dark) .modal-card { border-color: #344752; background: #22333e; } :global(.dark) .search-field input, :global(.dark) .toolbar select, :global(.dark) .form-grid input, :global(.dark) .form-grid select, :global(.dark) .form-grid textarea, :global(.dark) .contact-row input, :global(.dark) .contact-row select { color: #f3f4f6; border-color: #425864; background: #172a35; } :global(.dark) th { color: #b9c8cf; background: #1d2d37; } :global(.dark) td, :global(.dark) .modal-header, :global(.dark) .contacts-section, :global(.dark) .modal-footer { border-color: #344752; } :global(.dark) tbody tr:hover { background: #2b404c; } :global(.dark) .secondary-button, :global(.dark) .icon-button { color: #e8eef1; background: #344752; }
@media (max-width: 700px) { .page-header, .toolbar { align-items: stretch; flex-direction: column; } .form-grid { grid-template-columns: 1fr; } .form-grid .full-width { grid-column: auto; } .contact-row { grid-template-columns: 1fr 2rem; } .contact-row select { grid-column: 1 / -1; } }
</style>
