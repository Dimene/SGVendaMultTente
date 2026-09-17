<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    empresa: { type: Object, default: () => ({}) },
    lojas: { type: Array, default: () => [] },
    armazens: { type: Array, default: () => [] }
})

const logoPreview = ref(props.empresa.logo ? `/storage/${props.empresa.logo}` : '')
const form = useForm({
    _method: 'POST',
    nome: props.empresa.nome || '',
    nome_fantasia: props.empresa.nome_fantasia || '',
    documento: props.empresa.documento || '',
    email: props.empresa.email || '',
    telefone: props.empresa.telefone || '',
    celular: props.empresa.celular || '',
    endereco: props.empresa.endereco || '',
    cidade: props.empresa.cidade || '',
    provincia: props.empresa.provincia || '',
    pais: props.empresa.pais || 'Moçambique',
    site: props.empresa.site || '',
    logo: null,
    lojas: props.lojas.map(loja => ({ id: loja.id, nome: loja.Desc })),
    armazens: props.armazens.map(armazem => ({ id: armazem.id, nome: armazem.Descricao }))
})

function escolherLogo(event) {
    const arquivo = event.target.files?.[0]
    if (!arquivo) return
    form.logo = arquivo
    logoPreview.value = URL.createObjectURL(arquivo)
}

function adicionarLoja() { form.lojas.push({ id: null, nome: '' }) }
function adicionarArmazem() { form.armazens.push({ id: null, nome: '' }) }
function removerLoja(index) { form.lojas.splice(index, 1) }
function removerArmazem(index) { form.armazens.splice(index, 1) }

function guardar() {
    form.post(route('configuracoes.empresa.update', props.empresa.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            if (form.logo) logoPreview.value = URL.createObjectURL(form.logo)
        }
    })
}
</script>

<template>
    <Head title="Dados da Empresa" />
    <AuthenticatedLayout>
        <div class="company-page">
            <header class="page-header">
                <div><p class="eyebrow">Configurações</p><h1>Dados da empresa</h1><p>Defina a identidade da empresa e a estrutura de lojas e armazéns.</p></div>
                <button class="primary-button" type="button" :disabled="form.processing" @click="guardar"><i class="fas fa-save" aria-hidden="true"></i>{{ form.processing ? 'A guardar...' : 'Guardar alterações' }}</button>
            </header>

            <form class="settings-form" @submit.prevent="guardar">
                <section class="settings-card logo-card">
                    <div class="section-heading"><div><h2>Logotipo</h2><p>Este logo será usado no menu principal.</p></div></div>
                    <div class="logo-editor">
                        <div class="logo-preview"><img v-if="logoPreview" :src="logoPreview" alt="Logotipo da empresa" /><i v-else class="fas fa-building" aria-hidden="true"></i></div>
                        <div><label class="upload-button" for="logo"><i class="fas fa-upload" aria-hidden="true"></i> Escolher logotipo</label><input id="logo" class="file-input" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" @change="escolherLogo" /><small>PNG, JPG, WEBP ou SVG. Máximo 2 MB.</small><p v-if="form.errors.logo" class="error">{{ form.errors.logo }}</p></div>
                    </div>
                </section>

                <section class="settings-card">
                    <div class="section-heading"><div><h2>Informações principais</h2><p>Dados apresentados nos documentos e no sistema.</p></div></div>
                    <div class="form-grid">
                        <label>Nome da empresa *<input v-model="form.nome" type="text" required /><small v-if="form.errors.nome" class="error">{{ form.errors.nome }}</small></label>
                        <label>Nome fantasia<input v-model="form.nome_fantasia" type="text" /></label>
                        <label>NUIT / Documento<input v-model="form.documento" type="text" /></label>
                        <label>Email<input v-model="form.email" type="email" /></label>
                        <label>Telefone<input v-model="form.telefone" type="text" /></label>
                        <label>Celular<input v-model="form.celular" type="text" /></label>
                        <label>País<input v-model="form.pais" type="text" /></label>
                        <label>Província<input v-model="form.provincia" type="text" /></label>
                        <label>Cidade<input v-model="form.cidade" type="text" /></label>
                        <label>Site<input v-model="form.site" type="url" placeholder="https://" /></label>
                        <label class="full-width">Endereço<textarea v-model="form.endereco" rows="2"></textarea></label>
                    </div>
                </section>

                <div class="structure-grid">
                    <section class="settings-card">
                        <div class="section-heading"><div><h2>Lojas <span class="count">{{ form.lojas.length }}</span></h2><p>Registe os nomes das lojas.</p></div><button type="button" class="link-button" @click="adicionarLoja"><i class="fas fa-plus" aria-hidden="true"></i> Adicionar</button></div>
                        <div v-if="!form.lojas.length" class="empty-small">Nenhuma loja registada.</div>
                        <div v-for="(loja, index) in form.lojas" :key="loja.id || index" class="name-row"><input v-model="loja.nome" type="text" placeholder="Nome da loja" required /><button type="button" class="remove-button" title="Remover loja" @click="removerLoja(index)"><i class="fas fa-trash" aria-hidden="true"></i></button></div>
                    </section>
                    <section class="settings-card">
                        <div class="section-heading"><div><h2>Armazéns <span class="count">{{ form.armazens.length }}</span></h2><p>Registe os nomes dos armazéns.</p></div><button type="button" class="link-button" @click="adicionarArmazem"><i class="fas fa-plus" aria-hidden="true"></i> Adicionar</button></div>
                        <div v-if="!form.armazens.length" class="empty-small">Nenhum armazém registado.</div>
                        <div v-for="(armazem, index) in form.armazens" :key="armazem.id || index" class="name-row"><input v-model="armazem.nome" type="text" placeholder="Nome do armazém" required /><button type="button" class="remove-button" title="Remover armazém" @click="removerArmazem(index)"><i class="fas fa-trash" aria-hidden="true"></i></button></div>
                    </section>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.company-page { min-height: calc(100vh - 7rem); padding: 2rem clamp(1rem, 4vw, 3.5rem); color: #17324d; background: #f4f7f8; }
.page-header, .section-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; } .eyebrow { margin: 0 0 .35rem; color: #ad5d3b; font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; } h1 { margin: 0 0 .4rem; font: 500 clamp(2rem, 4vw, 3.2rem) Georgia, serif; } .page-header p:not(.eyebrow), .section-heading p { margin: 0; color: #6a7b88; }
.primary-button, .upload-button, .link-button, .remove-button { border: 0; border-radius: 4px; cursor: pointer; font-weight: 700; } .primary-button { display: inline-flex; align-items: center; gap: .5rem; padding: .7rem 1rem; color: #fff; background: #ad5d3b; } .primary-button:hover { background: #914b30; } .primary-button:disabled { opacity: .6; cursor: wait; }
.settings-form { display: grid; gap: 1rem; margin-top: 2rem; } .settings-card { padding: 1.3rem; border: 1px solid #dce5e8; border-radius: 6px; background: #fff; } .section-heading { align-items: center; margin-bottom: 1.2rem; } h2 { margin: 0 0 .3rem; font-size: 1.1rem; } .count { display: inline-flex; min-width: 1.5rem; justify-content: center; padding: .15rem .35rem; border-radius: 999px; color: #97500f; background: #fff1d6; font-size: .75rem; }
.logo-editor { display: flex; align-items: center; gap: 1rem; } .logo-preview { display: grid; width: 7rem; height: 7rem; place-items: center; overflow: hidden; border: 1px dashed #cbd8dd; border-radius: 6px; color: #ad5d3b; background: #f7fafb; font-size: 2rem; } .logo-preview img { width: 100%; height: 100%; object-fit: contain; } .upload-button { display: inline-flex; align-items: center; gap: .5rem; padding: .6rem .8rem; color: #fff; background: #17324d; } .file-input { display: none; } .logo-editor small { display: block; margin-top: .55rem; color: #6a7b88; }
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; } label { display: grid; gap: .4rem; color: #526675; font-size: .8rem; font-weight: 700; } input, textarea { width: 100%; padding: .65rem; border: 1px solid #cbd8dd; border-radius: 4px; background: #fbfcfc; } input:focus, textarea:focus { outline: 2px solid #d47a50; outline-offset: 1px; } .full-width { grid-column: 1 / -1; } .error { margin: 0; color: #b44343; font-size: .75rem; }
.structure-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; } .link-button { padding: .35rem; color: #ad5d3b; background: transparent; } .name-row { display: grid; grid-template-columns: 1fr 2rem; gap: .5rem; margin-top: .6rem; } .remove-button { color: #b44343; background: #ffebeb; } .empty-small { padding: 1rem 0; color: #6a7b88; font-size: .85rem; }
:global(.dark) .company-page { color: #f3f4f6; background: #17232d; } :global(.dark) .settings-card { border-color: #344752; background: #22333e; } :global(.dark) input, :global(.dark) textarea { color: #f3f4f6; border-color: #425864; background: #172a35; } :global(.dark) .logo-preview { border-color: #425864; background: #1d2d37; } :global(.dark) .upload-button { background: #344752; } :global(.dark) .remove-button { background: #512f36; }
@media (max-width: 700px) { .page-header, .section-heading { display: block; } .page-header .primary-button { width: 100%; justify-content: center; margin-top: 1rem; } .form-grid, .structure-grid { grid-template-columns: 1fr; } .full-width { grid-column: auto; } .logo-editor { align-items: flex-start; flex-direction: column; } }
</style>
