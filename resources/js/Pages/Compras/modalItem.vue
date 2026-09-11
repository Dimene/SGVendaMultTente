<template>
    <!-- OVERLAY -->
    <div
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
        @click.self="$emit('fechar')" >
        <!-- MODAL BOX -->
        <div class="bg-white w-full max-w-4xl rounded-lg shadow-lg p-6 max-h-[95vh] overflow-y-auto" >



<div class="conteudo"  v-if="registocategoria==false" >
            <!-- HEADER -->
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg" :class="item ? 'bg-blue-100' : 'bg-green-100'">
                        <i :class="item ? 'fas fa-edit text-blue-600' : 'fas fa-plus text-green-600'"  @click="registocategoria=true"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">
                            {{ item ? 'Editar' : 'Novo' }} Item
                        </h2>
                        <p class="text-sm text-gray-500" v-if="habaativada">
                            <i class="fas fa-folder mr-1"></i>
                            Grupo: <span class="font-semibold">{{ habaativada }}</span>
                        </p>
                    </div>
                </div>

                <button
                    @click="$emit('fechar')"
                    class="text-gray-500 hover:text-red-500 text-xl font-bold transition-colors"
                >
                    ✖
                </button>
            </div>

            <!-- FORM -->
            <form @submit.prevent="salvar" class="space-y-4"   >
                <!-- ========== GALERIA DE FOTOS ========== -->
                <div class="col-span-2" v-if="dadosstributo.includes('Foto')">
                    <label class="text-sm font-semibold mb-2 block">
                        <i class="fas fa-images mr-2"></i>
                        Fotos
                        <span class="text-xs text-gray-500">({{ fotos.length }}/{{ maxFotos }})</span>
                    </label>

                    <!-- Upload Area -->
                    <div
                        class="border-2 border-dashed rounded-lg p-4 text-center hover:border-blue-500 transition-colors cursor-pointer mb-3"
                        :class="isDragOver ? 'border-blue-500 bg-blue-50' : 'border-gray-300'"
                        @dragover.prevent="isDragOver = true"
                        @dragleave.prevent="isDragOver = false"
                        @drop.prevent="handleDrop"
                        @click="abrirSeletor"
                    >
                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/*"
                            multiple
                            class="hidden"
                            @change="handleFiles"
                        />

                        <div v-if="fotos.length < maxFotos">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-1"></i>
                            <p class="text-sm text-gray-600">
                                Arraste ou clique para adicionar fotos
                            </p>
                            <p class="text-xs text-gray-400">
                                Máx {{ maxFotos }} fotos • JPG, PNG, GIF, WEBP (até 5MB)
                            </p>
                        </div>
                        <div v-else class="text-yellow-600">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p class="text-sm">Limite máximo de {{ maxFotos }} fotos atingido</p>
                        </div>
                    </div>

                    <!-- Carousel/Galeria -->
                    <div v-if="fotos.length > 0" class="relative">
                        <!-- Miniaturas -->
                        <div class="flex gap-2 overflow-x-auto pb-2">
                            <div
                                v-for="(foto, index) in fotos"
                                :key="index"
                                class="relative flex-shrink-0 group cursor-pointer"
                                @click="fotoSelecionada = index"
                            >
                                <img
                                    :src="foto.url"
                                    :alt="foto.nome || `Foto ${index + 1}`"
                                    class="h-20 w-20 object-cover rounded-lg border-2"
                                    :class="fotoSelecionada === index ? 'border-blue-500' : 'border-gray-200'"
                                />

                                <div v-if="foto.principal"
                                     class="absolute top-0 right-0 bg-green-500 text-white text-xs px-1 rounded-tr-lg rounded-bl-lg">
                                    <i class="fas fa-star"></i>
                                </div>

                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center gap-1">
                                    <button
                                        @click.stop="definirPrincipal(index)"
                                        class="bg-green-600 hover:bg-green-700 text-white p-1 rounded-full w-6 h-6 flex items-center justify-center text-xs"
                                        title="Definir como principal"
                                    >
                                        <i class="fas fa-star"></i>
                                    </button>
                                    <button
                                        @click.stop="removerFoto(index)"
                                        class="bg-red-600 hover:bg-red-700 text-white p-1 rounded-full w-6 h-6 flex items-center justify-center text-xs"
                                        title="Remover"
                                    >
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>

                                <div v-if="foto.uploading" class="absolute bottom-0 left-0 right-0 h-1 bg-gray-200 rounded-b-lg">
                                    <div
                                        class="h-full bg-blue-500 transition-all duration-300 rounded-b-lg"
                                        :style="{ width: `${foto.progress || 0}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Foto Principal em Destaque -->
                        <div class="mt-3 relative bg-gray-100 rounded-lg overflow-hidden" style="height: 300px;">
                            <img
                                :src="fotos[fotoSelecionada]?.url"
                                :alt="fotos[fotoSelecionada]?.nome || 'Foto'"
                                class="w-full h-full object-contain"
                            />

                            <button
                                v-if="fotos.length > 1"
                                @click="navegarFotos(-1)"
                                class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full w-8 h-8 flex items-center justify-center transition"
                            >
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button
                                v-if="fotos.length > 1"
                                @click="navegarFotos(1)"
                                class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full w-8 h-8 flex items-center justify-center transition"
                            >
                                <i class="fas fa-chevron-right"></i>
                            </button>

                            <div class="absolute bottom-2 left-1/2 -translate-x-1/2 bg-black/50 text-white px-3 py-1 rounded-full text-xs">
                                {{ fotoSelecionada + 1 }} / {{ fotos.length }}
                            </div>

                            <div v-if="fotos[fotoSelecionada]?.principal"
                                 class="absolute top-2 left-2 bg-green-500 text-white px-2 py-1 rounded-lg text-xs flex items-center gap-1">
                                <i class="fas fa-star"></i>
                                Principal
                            </div>
                        </div>

                        <div class="flex gap-2 mt-2">
                            <button
                                v-if="fotos.length > 1"
                                @click="ordenarFotos"
                                class="text-xs bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition"
                                type="button"
                            >
                                <i class="fas fa-sort mr-1"></i>
                                Reordenar
                            </button>
                            <button
                                @click="abrirSeletor"
                                v-if="fotos.length < maxFotos"
                                class="text-xs bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 transition"
                                type="button"
                            >
                                <i class="fas fa-plus mr-1"></i>
                                Adicionar mais
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ========== FORMULÁRIO DINÂMICO ========== -->

                <!-- Loading dos atributos -->
                <div v-if="carregandoAtributos" class="col-span-2 text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p class="text-sm text-gray-500 mt-2">Carregando atributos da categoria...</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                 <label class="text-sm font-semibold mb-1 capitalize flex items-center gap-1">Armazem</label>
                 <select  v-model="form['armazem']">

                 <option   :value="value.Descricao" v-for="value in armazem">
                 {{ value.Descricao }}
                 </option>
                 </select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <!-- ====== CAMPOS EXISTENTES ====== -->
                    <!-- Campos dinâmicos baseados nos atributos existentes -->
                    <div
                        v-for="campo in camposFiltrados"
                        :key="campo"
                        class="flex flex-col"
                    >
                        <label class="text-sm font-semibold mb-1 capitalize flex items-center gap-1">
                            <i v-if="campo === 'Nome' || campo === 'nome'" class="fas fa-tag text-gray-500"></i>
                            <i v-else-if="campo === 'Categoria' || campo === 'categoria'" class="fas fa-folder text-gray-500"></i>
                            <i v-else-if="campo === 'preco_venda' || campo === 'Preço Venda' || campo === 'Preco_Venda'" class="fas fa-money-bill-wave text-gray-500"></i>
                            <i v-else-if="campo === 'preco_compra' || campo === 'Preço Compra' || campo === 'Preco_Compra'" class="fas fa-shopping-bag text-gray-500"></i>
                            <i v-else-if="campo === 'stoque' || campo === 'Stock' || campo === 'quantidade'" class="fas fa-boxes text-gray-500"></i>
                            <i v-else-if="campo === 'venda_iva' || campo === 'IVA' || campo === 'iva'" class="fas fa-percent text-gray-500"></i>
                            <i v-else-if="campo === 'lucro'" class="fas fa-percent text-gray-500"></i>
                            <i v-else-if="campo === 'descricao' || campo === 'Descrição'" class="fas fa-align-left text-gray-500"></i>
                            <i v-else-if="campo === 'codigo' || campo === 'Código' || campo === 'Codigo'" class="fas fa-barcode text-gray-500"></i>
                            <i v-else-if="campo === 'marca' || campo === 'Marca'" class="fas fa-trademark text-gray-500"></i>
                            <i v-else-if="campo === 'modelo' || campo === 'Modelo'" class="fas fa-cube text-gray-500"></i>
                            <i v-else class="fas fa-pencil-alt text-gray-500"></i>

                            {{ campo }}
                            <span v-if="isRequired(campo)" class="text-red-500 text-xs">*</span>

                            <span class="text-xs text-gray-400 ml-auto" v-if="getTipoCampo(campo)">
                                ({{ getTipoCampo(campo) }})
                            </span>
                        </label>

                        <!-- Select para Categoria -->

                       <div     v-if="campo === 'Categoria' || campo === 'categoria'"  class="flex gap-2 ">



                        <select

                            v-model="form[campo]"
                            @change="buscarAtributos($event.target.value)"
                            class="border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500"
                        >

                            <option
                                v-for="categoria in categoriasgrupo"
                                :key="categoria.id"
                                :value="categoria.id"
                            >
                                {{ categoria.nome }}
                            </option>
                        </select>


                        <Button  @click="registarcategoria()" class="bg-slate-600 p-3  rounded-lg" >
                         <i class=" fa fa-plus"></i>


                         </Button>
                       </div>




                        <!-- Select para IVA -->
                        <select
                            v-else-if="campo === 'venda_iva' || campo === 'IVA' || campo === 'iva'"
                            v-model="form[campo]"
                            class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >


                          <option value="0">0% (Isento)</option>

<option
    v-for="ivaitem in iva"
    :key="ivaitem.percentagem"
    :value="ivaitem.percentagem"



>
    {{ ivaitem.percentagem }} %
</option>

                        </select>


   <!-- Select para IVA -->
                        <select
                            v-else-if="campo === 'lucro'"
                            v-model="form[campo]"
                            class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >




<option
    v-for="item in lucro"
    :key="item.percentagem"
    :value="item.percentagem"



>
    {{ item.percentagem }} %
</option>

                        </select>

                        <!-- Textarea para descrições longas -->
                        <textarea
                            v-else-if="campo === 'descricao' || campo === 'Descrição' || campo === 'observacoes'"
                            v-model="form[campo]"
                            rows="2"
                            class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                            :placeholder="`Digite a ${campo}`"
                        ></textarea>

                        <!-- Campos Numéricos -->
                        <input
                            v-else-if="campo === 'stoque' || campo === 'Stock' || campo === 'quantidade' ||
                                       campo === 'preco_compra' || campo === 'Preço Compra' || campo === 'Preco_Compra' ||
                                       campo === 'preco_venda' || campo === 'Preço Venda' || campo === 'Preco_Venda'"
                            v-model.number="form[campo]"
                            type="number"
                            step="0.01"
                            min="0"
                            class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :placeholder="`Digite o ${campo}`"
                        />

                        <!-- Campos de Data -->
                        <input
                            v-else-if="campo === 'data' || campo === 'Data' || campo === 'validade'"
                            v-model="form[campo]"
                            type="date"
                            class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />

                        <!-- Campos de Código/Barras -->
                        <input
                            v-else-if="campo === 'codigo' || campo === 'Código' || campo === 'Codigo' || campo === 'barcode'"
                            v-model="form[campo]"
                            class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
                            type="text"
                            :placeholder="`Digite o ${campo}`"
                        />

                        <!-- Select para Status -->
                        <select
                            v-else-if="campo === 'status' || campo === 'Status' || campo === 'estado'"
                            v-model="form[campo]"
                            class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                            <option value="pendente">Pendente</option>
                        </select>

                        <!-- Campo Texto (padrão) -->
                        <input
                            v-else
                            v-model="form[campo]"
                            class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            type="text"
                            :placeholder="`Digite o ${campo}`"
                            :required="isRequired(campo)"
                        />
                    </div>

                    <!-- ====== ATRIBUTOS DINÂMICOS (ADICIONADOS) ====== -->
                    <!-- Seção de Atributos Específicos -->
                    <div v-if="atributosDinamicos.length > 0" class="col-span-2">
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-3 mb-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 text-blue-700">
                                    <i class="fas fa-cog"></i>
                                    <span class="text-sm font-semibold">Atributos Específicos</span>
                                    <span class="text-xs bg-blue-200 text-blue-800 px-2 py-0.5 rounded-full">
                                        {{ atributosDinamicos.length }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <i class="fas fa-info-circle"></i>
                                    Campos específicos da categoria selecionada
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Renderizar atributos dinâmicos adicionados -->
                    <template v-for="(atributo, idx) in atributosDinamicos" :key="idx">
                        <div class="flex flex-col" :class="atributo.tipo === 'textarea' ? 'md:col-span-2' : ''">
                            <label class="text-sm font-semibold mb-1 capitalize flex items-center gap-1">
                                <span class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-xs text-gray-600">
                                    <i v-if="atributo.tipo === 'number'" class="fas fa-hashtag"></i>
                                    <i v-else-if="atributo.tipo === 'select'" class="fas fa-list"></i>
                                    <i v-else-if="atributo.tipo === 'date'" class="fas fa-calendar"></i>
                                    <i v-else-if="atributo.tipo === 'textarea'" class="fas fa-align-left"></i>
                                    <i v-else-if="atributo.tipo === 'boolean'" class="fas fa-check-square"></i>
                                    <i v-else class="fas fa-pencil-alt"></i>
                                </span>

                                {{ atributo.Descricao || atributo.nome }}
                                <span v-if="atributo.obrigatorio" class="text-red-500 text-xs">*</span>

                                <span class="ml-auto text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full">
                                    {{ atributo.tipo || 'texto' }}
                                </span>
                            </label>

                            <!-- Campo Número -->
                            <input
                                v-if="atributo.tipo === 'number'"
                                v-model.number="form[atributo.Descricao || atributo.nome]"
                                type="number"
                                step="0.01"
                                min="0"
                                class="border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                                :placeholder="`Digite ${atributo.Descricao || atributo.nome}`"
                                :required="atributo.obrigatorio"
                            />

                            <!-- Select com opções -->
                            <select
                                v-else-if="atributo.tipo === 'select'"
                                v-model="form[atributo.Descricao || atributo.nome]"
                                class="border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                                :required="atributo.obrigatorio"
                            >
                                <option value="">Selecione uma opção</option>
                                <option
                                    v-for="opcao in (atributo.opcoes || [])"
                                    :key="opcao"
                                    :value="opcao"
                                >
                                    {{ opcao }}
                                </option>
                            </select>

                            <!-- Textarea -->
                            <textarea
                                v-else-if="atributo.tipo === 'textarea'"
                                v-model="form[atributo.Descricao || atributo.nome]"
                                rows="3"
                                class="border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none transition"
                                :placeholder="`Digite ${atributo.Descricao || atributo.nome}`"
                                :required="atributo.obrigatorio"
                            ></textarea>

                            <!-- Date -->
                            <input
                                v-else-if="atributo.tipo === 'date'"
                                v-model="form[atributo.Descricao || atributo.nome]"
                                type="date"
                                class="border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                                :required="atributo.obrigatorio"
                            />

                            <!-- Boolean (checkbox) -->
                            <div v-else-if="atributo.tipo === 'boolean'" class="flex items-center gap-2 mt-1">
                                <input
                                    v-model="form[atributo.Descricao || atributo.nome]"
                                    type="checkbox"
                                    class="h-5 w-5 text-blue-600 border rounded focus:ring-2 focus:ring-blue-500"
                                    :true-value="true"
                                    :false-value="false"
                                />
                                <span class="text-sm text-gray-600">Sim</span>
                            </div>

                            <!-- Text (padrão) -->
                            <input
                                v-else
                                v-model="form[atributo.Descricao || atributo.nome]"
                                type="text"
                                class="border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                                :placeholder="`Digite ${atributo.Descricao || atributo.nome}`"
                                :required="atributo.obrigatorio"
                            />
                        </div>
                    </template>
                </div>
            </form>

            <!-- FOOTER -->
            <div class="flex flex-col sm:flex-row justify-between gap-2 mt-5 border-t pt-3">
                <div class="text-sm text-gray-500 flex items-center gap-2 flex-wrap">
                    <i class="fas fa-info-circle"></i>
                    <span>Campos com <span class="text-red-500">*</span> são obrigatórios</span>

                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full flex items-center gap-1">
                        <i class="fas fa-images"></i>
                        {{ fotos.length }} foto(s)
                    </span>

                    <span v-if="atributosDinamicos.length > 0" class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full flex items-center gap-1">
                        <i class="fas fa-cog"></i>
                        {{ atributosDinamicos.length }} atributo(s)
                    </span>

                    <span class="text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded-full flex items-center gap-1">
                        <i class="fas fa-list"></i>
                        {{ camposFiltrados.length }} campo(s)
                    </span>
                </div>

                <div class="flex flex-col sm:flex-row gap-2">
                    <button
                        @click="$emit('fechar')"
                        type="button"
                        class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors flex items-center gap-2"
                    >
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        @click="salvar"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center gap-2"
                        :disabled="!isFormValid || carregando"
                    >
                        <i v-if="carregando" class="fas fa-spinner fa-spin"></i>
                        <i v-else :class="item ? 'fas fa-save' : 'fas fa-plus'"></i>
                        {{ carregando ? 'Guardando...' : (item ? 'Atualizar' : 'Guardar') }}
                    </button>
                </div>
            </div>
</div>
<div v-if="registocategoria" class="conteudo_categoria flex flex-col w-full p-4">

<Categoriacreate   :habaativada="props.habaativada"     @categorias="atribuirnovascategorias" />

</div>


        </div>




        </div>



</template>

<script setup>
import { reactive, watch, computed, ref } from "vue"
import axios from "axios"
import Categoriacreate from "./categoriacreate.vue"
import { Form } from "@inertiajs/vue3"

const props = defineProps({
    dadosstributo: {
        type: Array,
        default: () => []
    }, armazem: {
        type: Array,
        default: () => []
    },
    item: {
        type: Object,
        default: null
    },
    categorias: {
        type: Array,
        default: () => []
    },
   lucro: {
        type: Array,
        default: () => []
    },

    iva:{
        type: Array,
        default: () => []
    },
    camposObrigatorios: {
        type: Array,
        default: () => ['Nome','categoria']
    },
    habaativada: {
        type: String,
        default: ''
    },
    maxFotos: {
        type: Number,
        default: 6
    }
})

const emit = defineEmits(["fechar", "guardar"])

// ========== ESTADO ==========
const form = reactive({})
const carregando = ref(false)
const carregandoAtributos = ref(false)
const fileInput = ref(null)
const isDragOver = ref(false)
const registocategoria = ref(false)
const fotoSelecionada = ref(0)
const atributosDinamicos = ref([])
const categoriasgrupo=ref(props.categorias)
const fotos = ref([])








// ========== CAMPOS IGNORADOS ==========
const camposIgnorados = ['created_at', 'updated_at', 'id', 'deleted_at', 'fotos']

// ========== FILTRAR CAMPOS ==========
const camposFiltrados = computed(() => {
    return props.dadosstributo.filter(campo =>
        !camposIgnorados.includes(campo) &&
        campo !== 'Foto' &&
        campo !== 'Nr' &&
        campo !== 'Ações' &&
        campo !== 'accoes' &&
        campo !== 'outros_Atributos'
    )
})

// ========== VERIFICAR OBRIGATÓRIO ==========
const isRequired = (campo) => {
    return props.camposObrigatorios.includes(campo)
}

// ========== VERIFICAR FORMULÁRIO VÁLIDO ==========
const isFormValid = computed(() => {
    // Campos base obrigatórios
    for (const campo of props.camposObrigatorios) {
        if (!form[campo] || (typeof form[campo] === 'string' && form[campo].trim() === '')) {
            return false
        }
    }

    // Atributos dinâmicos obrigatórios
    for (const atributo of atributosDinamicos.value) {
        if (atributo.obrigatorio) {
            const nome = atributo.Descricao || atributo.nome
            const valor = form[nome]
            if (!valor || (typeof valor === 'string' && valor.trim() === '')) {
                return false
            }
        }
    }

    return true
})

// ========== DETECTAR TIPO DO CAMPO ==========
function getTipoCampo(campo) {
    const atributo = atributosDinamicos.value.find(a =>
        a.Descricao === campo || a.nome === campo
    )
    if (atributo) {
        return atributo.tipo || 'text'
    }

    const tipos = {
        'preco': 'number',
        'Preço': 'number',
        'Preco': 'number',
        'stoque': 'number',
        'Stock': 'number',
        'quantidade': 'number',
        'data': 'date',
        'Data': 'date',
        'validade': 'date',
        'codigo': 'text',
        'Código': 'text',
        'Codigo': 'text',
        'barcode': 'text',
        'categoria': 'select',
        'Categoria': 'select',
        'iva': 'select',
        'IVA': 'select',
        'lucro': 'select',
        'venda_iva': 'select',
        'status': 'select',
        'Status': 'select',
        'estado': 'select',
        'descricao': 'textarea',
        'Descrição': 'textarea',
        'observacoes': 'textarea'
    }

    for (const [key, value] of Object.entries(tipos)) {
        if (campo.includes(key) || key.includes(campo)) {
            return value
        }
    }
    return 'text'
}

// ========== LIMPAR FORMULÁRIO ==========
function limparForm() {
    props.dadosstributo.forEach((campo) => {
        if (['Foto', 'Nr', 'Ações', 'accoes', 'outros_Atributos'].includes(campo)) {
            return
        }

        if (campo === 'venda_iva' || campo === 'IVA' || campo === 'iva') {
            form[campo] = '23'
        } else if (campo === 'stoque' || campo === 'Stock' || campo === 'quantidade') {
            form[campo] = 0
        } else if (campo === 'preco_compra' || campo === 'Preço Compra' || campo === 'Preco_Compra') {
            form[campo] = 0
        } else if (campo === 'preco_venda' || campo === 'Preço Venda' || campo === 'Preco_Venda') {
            form[campo] = 0
        } else if (campo === 'Categoria' || campo === 'categoria') {
            form[campo] = ''
        } else if (campo === 'status' || campo === 'Status') {
            form[campo] = 'ativo'
        } else {
            form[campo] = ''
        }
    })

    atributosDinamicos.value.forEach((atributo) => {
        const nome = atributo.Descricao || atributo.nome
        if (atributo.tipo === 'number') {
            form[nome] = 0
        } else if (atributo.tipo === 'boolean') {
            form[nome] = false
        } else {
            form[nome] = ''
        }
    })

    fotos.value = []
    fotoSelecionada.value = 0
    if (fileInput.value) {
        fileInput.value.value = ''
    }
}

// ========== BUSCAR ATRIBUTOS ==========
function buscarAtributos(categoriaId) {
    if (!categoriaId) {
        atributosDinamicos.value = []
        return
    }

    carregandoAtributos.value = true

    axios
        .get("/categoria/atributos/" + categoriaId)
        .then((response) => {
            let atributos = []
            if (response.data) {
                if (response.data.atributos) {
                    atributos = response.data.atributos
                } else if (Array.isArray(response.data)) {
                    atributos = response.data
                } else if (response.data.data) {
                    atributos = response.data.data
                }
            }

            atributosDinamicos.value = atributos

            atributos.forEach((atributo) => {
                const nome = atributo.Descricao || atributo.nome
                if (!(nome in form)) {
                    if (atributo.tipo === 'number') {
                        form[nome] = 0
                    } else if (atributo.tipo === 'boolean') {
                        form[nome] = false
                    } else {
                        form[nome] = ''
                    }
                }
            })
        })
        .catch((error) => {
            console.error('Erro ao buscar atributos:', error)
            alert('Erro ao carregar atributos da categoria')
            atributosDinamicos.value = []
        })
        .finally(() => {
            carregandoAtributos.value = false
        })
}

// ========== PREENCHER FORM ==========
watch(
    () => props.item,
    (novo) => {

        if (novo) {

            Object.keys(novo).forEach(key => {

                if (key === 'fotos' && Array.isArray(novo.fotos)) {


                    fotos.value = novo.fotos
                        .filter(f => f) // remove null, undefined e vazio
                        .map((f, index) => {

                            let urlImagem = String(f);


                            // Foto base64 nova
                            if (urlImagem.startsWith('data:image')) {

                                urlImagem = urlImagem;

                            }

                            // Foto URL completa
                            else if (urlImagem.startsWith('http')) {

                                urlImagem = urlImagem;

                            }

                            // Foto vinda do Laravel storage
                            else {

                                urlImagem = `${window.location.origin}/storage/${urlImagem}`;

                            }


                            return {
                                url: urlImagem,
                                nome: `Foto ${index + 1}`,
                                principal: index === 0,
                                uploading: false,
                                progress: 100
                            };

                        });


                } else {

                    form[key] = novo[key];

                }

            });


            fotoSelecionada.value = 0;


            if (novo.categoria) {
                buscarAtributos(novo.categoria);
            }


        } else {

            limparForm();

        }

    },
    {
        immediate: true
    }
);

// ========== GERENCIAR FOTOS ==========
function abrirSeletor() {
    if (fotos.value.length < props.maxFotos) {
        fileInput.value.click()
    }
}

function handleFiles(event) {
    const files = Array.from(event.target.files)
    processarArquivos(files)
    event.target.value = ''
}

function handleDrop(event) {
    isDragOver.value = false
    const files = Array.from(event.dataTransfer.files)
    processarArquivos(files)
}

function processarArquivos(files) {
    const maxSize = 5 * 1024 * 1024
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']

    const validFiles = files.filter(file => {
        if (!allowedTypes.includes(file.type)) {
            alert(`O arquivo ${file.name} não é uma imagem válida`)
            return false
        }
        if (file.size > maxSize) {
            alert(`O arquivo ${file.name} excede o limite de 5MB`)
            return false
        }
        return true
    })

    const availableSlots = props.maxFotos - fotos.value.length
    const filesToUpload = validFiles.slice(0, availableSlots)

    if (validFiles.length > availableSlots) {
        alert(`Limite máximo de ${props.maxFotos} fotos. ${validFiles.length - availableSlots} arquivo(s) não foram adicionados.`)
    }

    filesToUpload.forEach(file => {
        const reader = new FileReader()
        const fotoItem = {
            file: file,
            nome: file.name,
            url: null,
            principal: fotos.value.length === 0,
            uploading: true,
            progress: 0
        }

        fotos.value.push(fotoItem)

        reader.onprogress = (event) => {
            if (event.lengthComputable) {
                fotoItem.progress = Math.round((event.loaded / event.total) * 100)
            }
        }

        reader.onload = (e) => {
            fotoItem.url = e.target.result
            fotoItem.uploading = false
            fotoItem.progress = 100
        }

        reader.readAsDataURL(file)
    })
}

function removerFoto(index) {
    if (confirm('Tem certeza que deseja remover esta foto?')) {
        const eraPrincipal = fotos.value[index]?.principal
        fotos.value.splice(index, 1)

        if (fotos.value.length > 0 && eraPrincipal) {
            fotos.value[0].principal = true
        }

        if (fotoSelecionada.value >= fotos.value.length) {
            fotoSelecionada.value = Math.max(0, fotos.value.length - 1)
        }
    }
}

function definirPrincipal(index) {
    fotos.value.forEach((f, i) => {
        f.principal = i === index
    })
}

function navegarFotos(direcao) {
    const novaPosicao = fotoSelecionada.value + direcao
    if (novaPosicao >= 0 && novaPosicao < fotos.value.length) {
        fotoSelecionada.value = novaPosicao
    }
}

function ordenarFotos() {
    const ordem = prompt('Digite a ordem das fotos (ex: 2,1,3,4):')
    if (ordem) {
        try {
            const indices = ordem.split(',').map(Number).filter(i => i > 0 && i <= fotos.value.length)
            if (indices.length === fotos.value.length) {
                const novasFotos = indices.map(i => fotos.value[i - 1])
                fotos.value = novasFotos
            }
        } catch (e) {
            alert('Formato inválido. Use números separados por vírgula.')
        }
    }
}

// ========== SALVAR ==========
function salvar() {
    // Validar campos obrigatórios
    for (const campo of props.camposObrigatorios) {
        const value = form[campo]
        if (!value || (typeof value === 'string' && value.trim() === '')) {
            alert(`O campo "${campo}" é obrigatório.`)
            return
        }
    }

    for (const atributo of atributosDinamicos.value) {
        if (atributo.obrigatorio) {
            const nome = atributo.Descricao || atributo.nome
            const value = form[nome]
            if (!value || (typeof value === 'string' && value.trim() === '')) {
                alert(`O campo "${nome}" é obrigatório.`)
                return
            }
        }
    }

    carregando.value = true

    const dadosParaSalvar = {
        ...form,
        fotos: fotos.value.map(f => f.url),
        grupo: props.habaativada
    }

    emit('guardar', dadosParaSalvar)

    setTimeout(() => {
        carregando.value = false
    }, 500)
}

// ========== EXPOSED ==========
defineExpose({
    form,
    limparForm,
    fotos,
    atributosDinamicos,
    buscarAtributos
})



function registarcategoria(){
   registocategoria.value=true;
}


function atribuirnovascategorias(dados){

  registocategoria.value=false;


//   console.log("ados categorioa",dados);
  categoriasgrupo.value=dados;
  form.categoria=dados[dados.length-1].id
  form.Categoria=dados[dados.length-1].id
  buscarAtributos(dados[dados.length-1].id);
}





watch(
    () => props.iva,
    (novo) => {
        const ivaAtivo = novo.find(item => item.status == 1);

        if (ivaAtivo) {
            form.IVA = ivaAtivo.percentagem;
            form.iva = ivaAtivo.percentagem;
        }
    },
    { immediate: true }
);


watch(
    () => props.armazem,
    (novo) => {
        if (novo?.length > 0 && !form['armazem']) {
            form['armazem'] = novo[0].Descricao;
        }
    },
    { immediate: true }
);



watch(
    () => props.lucro,
    (novo) => {
        const lucroAtivo = novo.find(item => item.status == 1);

 if (lucroAtivo) {
            form.lucro = lucroAtivo.percentagem;

        }
    },
    { immediate: true }
);


watch(
    [
        () => form['Preço Compra'],

        () => form['IVA'],
        () => form['lucro']
    ],
    () => {

        form['Preço Venda'] =
            ((Number(form["lucro"]) / 100) * Number(form['Preço Compra'])) +
            Number(form['Preço Compra']);

        const iva = Number(form['IVA']) / 100;

        form['Venda com IVA'] =
            Number(form['Preço Venda']) +
            (Number(form['Preço Venda']) * iva);

    }
);


watch(

        () => form['Preço Venda'],


    () => {



        const iva = Number(form['IVA']) / 100;

        form['Venda com IVA'] =
            Number(form['Preço Venda']) +
            (Number(form['Preço Venda']) * iva);

    }
);
</script>

<style scoped>
/* Animações */
.modal-enter-active {
    animation: modalIn 0.3s ease;
}

@keyframes modalIn {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Scrollbar */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Focus */
input:focus, select:focus, textarea:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
}

/* Upload area */
.border-dashed {
    transition: all 0.3s ease;
}

/* Miniaturas */
.flex-shrink-0 {
    transition: transform 0.2s ease;
}

.flex-shrink-0:hover {
    transform: scale(1.05);
}

/* Carousel */
.absolute {
    transition: all 0.3s ease;
}

/* Botões desabilitados */
button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Loading spinner */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Badges */
.text-xs.bg-blue-100,
.text-xs.bg-purple-100,
.text-xs.bg-gray-100 {
    transition: all 0.2s ease;
}
</style>
