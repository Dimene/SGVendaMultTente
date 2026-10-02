<template>
    <!-- OVERLAY -->
    <div
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="$emit('fechar')" >
        <!-- MODAL BOX -->
        <div
            ref="modalRef"
            class="bg-white w-full max-w-4xl rounded-lg shadow-lg p-6 max-h-[95vh] overflow-y-auto"
            @keydown="handleModalKeydown"
        >

            <div class="conteudo" v-if="registocategoria==false">
                <!-- HEADER -->
                <div class="flex items-center justify-between pb-3 mb-4 border-b">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg" :class="item ? 'bg-blue-100' : 'bg-green-100'">
                            <i :class="item ? 'fas fa-edit text-blue-600' : 'fas fa-plus text-green-600'" @click="registocategoria=true"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">
                                {{ item ? 'Editar' : 'Novo' }} Item
                            </h2>
                            <p class="text-sm text-gray-500" v-if="habaativada">
                                <i class="mr-1 fas fa-folder"></i>
                                Grupo: <span class="font-semibold">{{ habaativada }}</span>
                            </p>
                        </div>
                    </div>

                    <button
                        @click="$emit('fechar')"
                        class="text-xl font-bold text-gray-500 transition-colors hover:text-red-500"
                    >
                        ✖
                    </button>
                </div>

                <!-- FORM -->
                <form @submit.prevent="salvar" class="space-y-4">
                    <!-- ========== GALERIA DE FOTOS ========== -->
                    <div class="col-span-2" v-if="dadosstributo.includes('Foto')">
                        <label class="block mb-2 text-sm font-semibold">
                            <i class="mr-2 fas fa-images"></i>
                            Fotos
                            <span class="text-xs text-gray-500">({{ fotos.length }}/{{ maxFotos }})</span>
                        </label>

                        <!-- Upload Area -->
                        <div
                            class="p-4 mb-3 text-center transition-colors border-2 border-dashed rounded-lg cursor-pointer hover:border-blue-500"
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
                            <input
                                ref="cameraInput"
                                type="file"
                                accept="image/*"
                                capture="environment"
                                class="hidden"
                                @change="handleFiles"
                            />

                            <div v-if="fotos.length < maxFotos">
                                <i class="mb-1 text-3xl text-gray-400 fas fa-cloud-upload-alt"></i>
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

                        <div class="flex flex-wrap gap-2 mb-3">
                            <button type="button" class="flex items-center gap-2 px-3 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700" @click.stop="abrirCamera">
                                <i class="fas fa-camera" aria-hidden="true"></i>
                                Tirar foto
                            </button>
                            <button v-if="fotos.length" type="button" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200" @click="removerFundoDaFoto">
                                <i class="fas fa-magic" aria-hidden="true"></i>
                                Remover fundo da foto selecionada
                            </button>
                        </div>

                        <!-- Carousel/Galeria -->
                        <div v-if="fotos.length > 0" class="relative">
                            <!-- Miniaturas -->
                            <div class="flex gap-2 pb-2 overflow-x-auto">
                                <div
                                    v-for="(foto, index) in fotos"
                                    :key="index"
                                    class="relative flex-shrink-0 cursor-pointer group"
                                    @click="fotoSelecionada = index"
                                >
                                    <img
                                        :src="foto.url"
                                        :alt="foto.nome || `Foto ${index + 1}`"
                                        class="object-cover w-20 h-20 border-2 rounded-lg"
                                        :class="fotoSelecionada === index ? 'border-blue-500' : 'border-gray-200'"
                                    />

                                    <div v-if="foto.principal"
                                         class="absolute top-0 right-0 px-1 text-xs text-white bg-green-500 rounded-tr-lg rounded-bl-lg">
                                        <i class="fas fa-star"></i>
                                    </div>

                                    <div class="absolute inset-0 flex items-center justify-center gap-1 transition-opacity rounded-lg opacity-0 bg-black/50 group-hover:opacity-100">
                                        <button
                                            @click.stop="definirPrincipal(index)"
                                            class="flex items-center justify-center w-6 h-6 p-1 text-xs text-white bg-green-600 rounded-full hover:bg-green-700"
                                            title="Definir como principal"
                                        >
                                            <i class="fas fa-star"></i>
                                        </button>
                                        <button
                                            @click.stop="removerFoto(index)"
                                            class="flex items-center justify-center w-6 h-6 p-1 text-xs text-white bg-red-600 rounded-full hover:bg-red-700"
                                            title="Remover"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <button
                                            @click.stop="removerFundoDaFoto(index)"
                                            class="flex items-center justify-center w-6 h-6 p-1 text-xs text-white bg-purple-600 rounded-full hover:bg-purple-700"
                                            title="Remover fundo"
                                        >
                                            <i class="fas fa-magic"></i>
                                        </button>
                                    </div>

                                    <div v-if="foto.uploading" class="absolute bottom-0 left-0 right-0 h-1 bg-gray-200 rounded-b-lg">
                                        <div
                                            class="h-full transition-all duration-300 bg-blue-500 rounded-b-lg"
                                            :style="{ width: `${foto.progress || 0}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Foto Principal em Destaque -->
                            <div class="relative mt-3 overflow-hidden bg-gray-100 rounded-lg" style="height: 300px;">
                                <img
                                    :src="fotos[fotoSelecionada]?.url"
                                    :alt="fotos[fotoSelecionada]?.nome || 'Foto'"
                                    class="object-contain w-full h-full"
                                />

                                <button
                                    v-if="fotos.length > 1"
                                    @click="navegarFotos(-1)"
                                    class="absolute flex items-center justify-center w-8 h-8 text-white transition -translate-y-1/2 rounded-full left-2 top-1/2 bg-black/50 hover:bg-black/70"
                                >
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button
                                    v-if="fotos.length > 1"
                                    @click="navegarFotos(1)"
                                    class="absolute flex items-center justify-center w-8 h-8 text-white transition -translate-y-1/2 rounded-full right-2 top-1/2 bg-black/50 hover:bg-black/70"
                                >
                                    <i class="fas fa-chevron-right"></i>
                                </button>

                                <div class="absolute px-3 py-1 text-xs text-white -translate-x-1/2 rounded-full bottom-2 left-1/2 bg-black/50">
                                    {{ fotoSelecionada + 1 }} / {{ fotos.length }}
                                </div>

                                <div v-if="fotos[fotoSelecionada]?.principal"
                                     class="absolute flex items-center gap-1 px-2 py-1 text-xs text-white bg-green-500 rounded-lg top-2 left-2">
                                    <i class="fas fa-star"></i>
                                    Principal
                                </div>
                            </div>

                            <div class="flex gap-2 mt-2">
                                <button
                                    v-if="fotos.length > 1"
                                    @click="ordenarFotos"
                                    class="px-3 py-1 text-xs text-white transition bg-blue-600 rounded hover:bg-blue-700"
                                    type="button"
                                >
                                    <i class="mr-1 fas fa-sort"></i>
                                    Reordenar
                                </button>
                                <button
                                    @click="abrirSeletor"
                                    v-if="fotos.length < maxFotos"
                                    class="px-3 py-1 text-xs text-white transition bg-green-600 rounded hover:bg-green-700"
                                    type="button"
                                >
                                    <i class="mr-1 fas fa-plus"></i>
                                    Adicionar mais
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ========== FORMULÁRIO DINÂMICO ========== -->

                    <!-- Loading dos atributos -->
                    <div v-if="carregandoAtributos" class="col-span-2 py-8 text-center">
                        <div class="inline-block w-8 h-8 border-b-2 border-blue-600 rounded-full animate-spin"></div>
                        <p class="mt-2 text-sm text-gray-500">Carregando atributos da categoria...</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-1">
                        <label class="flex items-center gap-1 mb-1 text-sm font-semibold capitalize">Armazem</label>
                        <select v-model="form['armazem']">
                            <option :value="value.Descricao" v-for="value in armazem">
                                {{ value.Descricao }}
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <!-- ====== CAMPOS EXISTENTES ====== -->
                        <div
                            v-for="campo in camposFiltrados"
                            :key="campo"
                            class="flex flex-col"
                        >
                            <label class="flex items-center gap-1 mb-1 text-sm font-semibold capitalize">
                                <i v-if="campo === 'Nome' || campo === 'nome'" class="text-gray-500 fas fa-tag"></i>
                                <i v-else-if="campo === 'Categoria' || campo === 'categoria'" class="text-gray-500 fas fa-folder"></i>
                                <i v-else-if="campo === 'preco_venda' || campo === 'Preço Venda' || campo === 'Preco_Venda'" class="text-gray-500 fas fa-money-bill-wave"></i>
                                <i v-else-if="campo === 'preco_compra' || campo === 'Preço Compra' || campo === 'Preco_Compra'" class="text-gray-500 fas fa-shopping-bag"></i>
                                <i v-else-if="campo === 'stoque' || campo === 'Stock' || campo === 'quantidade'" class="text-gray-500 fas fa-boxes"></i>
                                <i v-else-if="campo === 'venda_iva' || campo === 'IVA' || campo === 'iva'" class="text-gray-500 fas fa-percent"></i>
                                <i v-else-if="campo === 'lucro'" class="text-gray-500 fas fa-percent"></i>
                                <i v-else-if="campo === 'descricao' || campo === 'Descrição'" class="text-gray-500 fas fa-align-left"></i>
                                <i v-else-if="campo === 'codigo' || campo === 'Código' || campo === 'Codigo'" class="text-gray-500 fas fa-barcode"></i>
                                <i v-else-if="campo === 'marca' || campo === 'Marca'" class="text-gray-500 fas fa-trademark"></i>
                                <i v-else-if="campo === 'modelo' || campo === 'Modelo'" class="text-gray-500 fas fa-cube"></i>
                                <i v-else class="text-gray-500 fas fa-pencil-alt"></i>

                                {{ getCampoLabel(campo) }}
                                <span v-if="isRequired(campo)" class="text-xs text-red-500">*</span>

                                <span class="ml-auto text-xs text-gray-400" v-if="getTipoCampo(campo)">
                                    ({{ getTipoCampo(campo) }})
                                </span>
                            </label>

                            <!-- Select para Categoria -->
                            <div v-if="campo === 'Categoria' || campo === 'categoria'" class="flex gap-2">
                                <select
                                    v-model="form[campo]"
                                    @change="buscarAtributos($event.target.value)"
                                    class="px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                                >
                                    <option
                                        v-for="categoria in categoriasgrupo"
                                        :key="categoria.id"
                                        :value="categoria.id"
                                    >
                                        {{ categoria.nome }}
                                    </option>
                                </select>

                                <Button @click="registarcategoria()" class="p-3 rounded-lg bg-slate-600">
                                    <i class="fa fa-plus"></i>
                                </Button>
                            </div>

                            <!-- Select para IVA -->
                            <select
                                v-else-if="campo === 'venda_iva' || campo === 'IVA' || campo === 'iva'"
                                v-model="form[campo]"
                                class="px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
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

                            <!-- Select para Lucro -->
                            <select
                                v-else-if="campo === 'lucro'"
                                v-model="form[campo]"
                                class="px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
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
                                class="px-3 py-2 border rounded resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
                                :placeholder="`Digite a ${campo}`"
                            ></textarea>

                            <!-- Campos Numéricos -->
                            <input
                                v-else-if="campo === 'stoque' || campo === 'Stock' || campo === 'quantidade' ||
                                           campo === 'preco_compra' || campo === 'Preço Compra' || campo === 'Preco_Compra' ||
                                           campo === 'preco_venda' || campo === 'Preço Venda' || campo === 'Preco_Venda' ||
                                           campo === 'Desconto (%)' || campo === 'desconto'"
                                v-model.number="form[campo]"
                                type="number"
                                step="0.01"
                                min="0"
                                :max="campo === 'Desconto (%)' || campo === 'desconto' ? 100 : undefined"
                                class="px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                :placeholder="`Digite o ${campo}`"
                            />

                            <!-- Campos de Data -->
                            <input
                                v-else-if="campo === 'data' || campo === 'Data' || campo === 'validade'"
                                v-model="form[campo]"
                                type="date"
                                class="px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />

                            <!-- Campos de Código/Barras -->
                            <input
                                v-else-if="campo === 'codigo' || campo === 'Código' || campo === 'Codigo' || campo === 'barcode'"
                                v-model="form[campo]"
                                class="px-3 py-2 font-mono border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                type="text"
                                :placeholder="`Digite o ${campo}`"
                            />

                            <!-- Select para Status -->
                            <select
                                v-else-if="campo === 'status' || campo === 'Status' || campo === 'estado'"
                                v-model="form[campo]"
                                class="px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                                <option value="pendente">Pendente</option>
                            </select>

                            <!-- Campo Texto (padrão) -->
                            <input
                                v-else
                                v-model="form[campo]"
                                class="px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                type="text"
                                :placeholder="`Digite o ${campo}`"
                                :required="isRequired(campo)"
                            />
                        </div>

                        <!-- ====== ATRIBUTOS DINÂMICOS ====== -->
                        <div v-if="atributosDinamicos.length > 0" class="col-span-2">
                            <div class="p-3 mb-2 border border-blue-200 rounded-lg bg-gradient-to-r from-blue-50 to-indigo-50">
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

                        <template v-for="(atributo, idx) in atributosDinamicos" :key="idx">
                            <div class="flex flex-col" :class="atributo.tipo === 'textarea' ? 'md:col-span-2' : ''">
                                <label class="flex items-center gap-1 mb-1 text-sm font-semibold capitalize">
                                    <span class="flex items-center justify-center w-6 h-6 text-xs text-gray-600 bg-gray-100 rounded-full">
                                        <i v-if="atributo.tipo === 'number'" class="fas fa-hashtag"></i>
                                        <i v-else-if="atributo.tipo === 'select'" class="fas fa-list"></i>
                                        <i v-else-if="atributo.tipo === 'date'" class="fas fa-calendar"></i>
                                        <i v-else-if="atributo.tipo === 'textarea'" class="fas fa-align-left"></i>
                                        <i v-else-if="atributo.tipo === 'boolean'" class="fas fa-check-square"></i>
                                        <i v-else class="fas fa-pencil-alt"></i>
                                    </span>

                                    {{ atributo.Descricao || atributo.nome }}
                                    <span v-if="atributo.obrigatorio" class="text-xs text-red-500">*</span>

                                    <span class="ml-auto text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full">
                                        {{ atributo.tipo || 'texto' }}
                                    </span>
                                </label>

                                <input
                                    v-if="atributo.tipo === 'number'"
                                    v-model.number="form[atributo.Descricao || atributo.nome]"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="px-3 py-2 transition border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    :placeholder="`Digite ${atributo.Descricao || atributo.nome}`"
                                    :required="atributo.obrigatorio"
                                />

                                <select
                                    v-else-if="atributo.tipo === 'select'"
                                    v-model="form[atributo.Descricao || atributo.nome]"
                                    class="px-3 py-2 transition border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
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

                                <textarea
                                    v-else-if="atributo.tipo === 'textarea'"
                                    v-model="form[atributo.Descricao || atributo.nome]"
                                    rows="3"
                                    class="px-3 py-2 transition border rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    :placeholder="`Digite ${atributo.Descricao || atributo.nome}`"
                                    :required="atributo.obrigatorio"
                                ></textarea>

                                <input
                                    v-else-if="atributo.tipo === 'date'"
                                    v-model="form[atributo.Descricao || atributo.nome]"
                                    type="date"
                                    class="px-3 py-2 transition border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    :required="atributo.obrigatorio"
                                />

                                <div v-else-if="atributo.tipo === 'boolean'" class="flex items-center gap-2 mt-1">
                                    <input
                                        v-model="form[atributo.Descricao || atributo.nome]"
                                        type="checkbox"
                                        class="w-5 h-5 text-blue-600 border rounded focus:ring-2 focus:ring-blue-500"
                                        :true-value="true"
                                        :false-value="false"
                                    />
                                    <span class="text-sm text-gray-600">Sim</span>
                                </div>

                                <input
                                    v-else
                                    v-model="form[atributo.Descricao || atributo.nome]"
                                    type="text"
                                    class="px-3 py-2 transition border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    :placeholder="`Digite ${atributo.Descricao || atributo.nome}`"
                                    :required="atributo.obrigatorio"
                                />
                            </div>
                        </template>
                    </div>
                </form>

                <!-- FOOTER -->
                <div class="flex flex-col justify-between gap-2 pt-3 mt-5 border-t sm:flex-row">
                    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500">
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

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <button
                            @click="$emit('fechar')"
                            type="button"
                            class="flex items-center gap-2 px-6 py-2 text-gray-700 transition-colors bg-gray-200 rounded-lg hover:bg-gray-300"
                        >
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            @click="salvar"
                            class="flex items-center gap-2 px-6 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700"
                            :disabled="!isFormValid || carregando"
                        >
                            <i v-if="carregando" class="fas fa-spinner fa-spin"></i>
                            <i v-else :class="item ? 'fas fa-save' : 'fas fa-plus'"></i>
                            {{ carregando ? 'Guardando...' : (item ? 'Atualizar' : 'Guardar') }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="registocategoria" class="flex flex-col w-full p-4 conteudo_categoria">
                <Categoriacreate :habaativada="props.habaativada" @categorias="atribuirnovascategorias" />
            </div>

        </div>

        <div v-if="cameraAberta" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/70" @click.self="fecharCamera">
            <div class="w-full max-w-lg p-4 bg-white shadow-2xl rounded-xl">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-bold text-gray-800">Tirar foto</h3>
                    <button type="button" class="text-gray-500 hover:text-red-600" @click="fecharCamera"><i class="fas fa-times"></i></button>
                </div>
                <video ref="videoCamera" autoplay playsinline class="w-full bg-black rounded-lg aspect-video"></video>
                <p v-if="erroCamera" class="mt-2 text-sm text-red-600">{{ erroCamera }}</p>
                <div class="flex justify-end gap-2 mt-3">
                    <button type="button" class="px-4 py-2 bg-gray-200 rounded-lg" @click="fecharCamera">Cancelar</button>
                    <button type="button" class="px-4 py-2 text-white bg-blue-600 rounded-lg disabled:opacity-50" :disabled="!!erroCamera" @click="capturarFoto"><i class="mr-1 fas fa-camera"></i>Capturar</button>
                </div>
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
    },
    armazem: {
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
    iva: {
        type: Array,
        default: () => []
    },
    camposObrigatorios: {
        type: Array,
        default: () => ['Nome', 'categoria']
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
const modalRef = ref(null)

// ========== ESTADO ==========
const form = reactive({})
const carregando = ref(false)
const carregandoAtributos = ref(false)
const fileInput = ref(null)
const cameraInput = ref(null)
const videoCamera = ref(null)
const cameraAberta = ref(false)
const erroCamera = ref('')
let streamCamera = null
const isDragOver = ref(false)
const registocategoria = ref(false)
const fotoSelecionada = ref(0)
const atributosDinamicos = ref([])
const categoriasgrupo = ref(props.categorias)
const fotos = ref([])

function getCamposNavegacao() {
    if (!modalRef.value) return []

    return Array.from(modalRef.value.querySelectorAll('input, select, textarea, button'))
        .filter((element) => {
            if (element.disabled) return false
            if (element.type === 'hidden') return false
            if (element.offsetParent === null && element.tagName !== 'BUTTON') return false
            return true
        })
}

function moverFocoNaDirecao(direcao) {
    const campos = getCamposNavegacao()
    const indiceAtual = campos.findIndex((element) => element === document.activeElement)

    if (indiceAtual === -1) return

    const proximoIndice = Math.min(
        Math.max(indiceAtual + direcao, 0),
        campos.length - 1
    )

    campos[proximoIndice]?.focus()
}

function handleModalKeydown(event) {
    const alvo = event.target
    const tecla = event.key
    const tag = alvo?.tagName
    const ehTexto = ['INPUT', 'TEXTAREA', 'SELECT'].includes(tag)
    const ehBotao = tag === 'BUTTON'

    if (['ArrowRight', 'ArrowDown', 'ArrowLeft', 'ArrowUp'].includes(tecla) && ehTexto) {
        event.preventDefault()

        const direcao = ['ArrowRight', 'ArrowDown'].includes(tecla) ? 1 : -1
        moverFocoNaDirecao(direcao)
        return
    }

    if (tecla === 'Enter' && !event.shiftKey && !ehBotao && !alvo?.isContentEditable) {
        const eTextarea = tag === 'TEXTAREA'
        const eSelect = tag === 'SELECT'

        if (!eTextarea && !eSelect) {
            event.preventDefault()
            salvar()
        }
    }
}

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

const getCampoLabel = (campo) => {
    const labels = {
        'Preço Venda cliente 1': 'Preço Venda 1 - Singular',
        'Preço Venda cliente 2': 'Preço Venda 2 - Empresa',
        'Venda com IVA cliente 1': 'Venda com IVA 1 - Singular',
        'Venda com IVA cliente 2': 'Venda com IVA 2 - Empresas',
    }

    return labels[campo] || campo
}

// ========== VERIFICAR FORMULÁRIO VÁLIDO ==========
const isFormValid = computed(() => {
    for (const campo of props.camposObrigatorios) {
        if (!form[campo] || (typeof form[campo] === 'string' && form[campo].trim() === '')) {
            return false
        }
    }

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
        'Desconto': 'number',
        'desconto': 'number',
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

// ========== DESCOBRIR NOME REAL DO CAMPO IVA ==========
function getCampoIvaNome() {
    return props.dadosstributo.find(
        c => c === 'IVA' || c === 'iva' || c === 'venda_iva'
    )
}

// ========== DESCOBRIR NOME REAL DO CAMPO LUCRO ==========
function getCampoLucroNome() {
    return props.dadosstributo.find(c => c === 'lucro')
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
        } else if (campo === 'Desconto (%)' || campo === 'desconto') {
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
            Swal.fire({
                icon: 'error',
                title: 'Erro ao carregar atributos',
                text: 'Não foi possível carregar os atributos da categoria.'
            })
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
                        .filter(f => f)
                        .map((f, index) => {

                            let urlImagem = String(f);

                            if (urlImagem.startsWith('data:image')) {
                                urlImagem = urlImagem;
                            }
                            else if (urlImagem.startsWith('http')) {
                                urlImagem = urlImagem;
                            }
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

async function abrirCamera() {
    if (fotos.value.length >= props.maxFotos) return

    if (!navigator.mediaDevices?.getUserMedia) {
        cameraInput.value?.click()
        return
    }

    erroCamera.value = ''
    cameraAberta.value = true

    try {
        streamCamera = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' } },
            audio: false
        })
        if (videoCamera.value) videoCamera.value.srcObject = streamCamera
    } catch (error) {
        console.error('Não foi possível abrir a câmara:', error)
        erroCamera.value = 'Não foi possível aceder à câmara. Verifique a permissão do navegador.'
    }
}

function fecharCamera() {
    streamCamera?.getTracks().forEach(track => track.stop())
    streamCamera = null
    cameraAberta.value = false
}

function capturarFoto() {
    const video = videoCamera.value
    if (!video?.videoWidth) return

    const canvas = document.createElement('canvas')
    canvas.width = video.videoWidth
    canvas.height = video.videoHeight
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height)

    canvas.toBlob((blob) => {
        if (blob) processarArquivos([new File([blob], `foto-${Date.now()}.jpg`, { type: 'image/jpeg' })])
        fecharCamera()
    }, 'image/jpeg', 0.92)
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
            Swal.fire({
                icon: 'warning',
                title: 'Imagem inválida',
                text: `O arquivo ${file.name} não é uma imagem válida.`
            })
            return false
        }
        if (file.size > maxSize) {
            Swal.fire({
                icon: 'warning',
                title: 'Imagem muito grande',
                text: `O arquivo ${file.name} excede o limite de 5 MB.`
            })
            return false
        }
        return true
    })

    const availableSlots = props.maxFotos - fotos.value.length
    const filesToUpload = validFiles.slice(0, availableSlots)

    if (validFiles.length > availableSlots) {
        Swal.fire({
            icon: 'warning',
            title: 'Limite de fotos atingido',
            text: `O limite é de ${props.maxFotos} fotos. ${validFiles.length - availableSlots} arquivo(s) não foram adicionados.`
        })
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

function carregarImagem(url) {
    return new Promise((resolve, reject) => {
        const imagem = new Image()
        imagem.onload = () => resolve(imagem)
        imagem.onerror = reject
        imagem.src = url
    })
}

async function removerFundoDaFoto(index = fotoSelecionada.value) {
    const foto = fotos.value[index]
    if (!foto?.url || foto.removendoFundo) return

    foto.removendoFundo = true
    try {
        const imagem = await carregarImagem(foto.url)
        const canvas = document.createElement('canvas')
        canvas.width = imagem.naturalWidth
        canvas.height = imagem.naturalHeight
        const contexto = canvas.getContext('2d')
        contexto.drawImage(imagem, 0, 0)

        const imagemData = contexto.getImageData(0, 0, canvas.width, canvas.height)
        const pixels = imagemData.data
        const pontos = [
            [0, 0], [canvas.width - 1, 0],
            [0, canvas.height - 1], [canvas.width - 1, canvas.height - 1]
        ]
        const amostras = pontos.map(([x, y]) => {
            const posicao = (y * canvas.width + x) * 4
            return [pixels[posicao], pixels[posicao + 1], pixels[posicao + 2]]
        })
        const distancia = (r, g, b, amostra) => Math.sqrt(
            (r - amostra[0]) ** 2 + (g - amostra[1]) ** 2 + (b - amostra[2]) ** 2
        )

        for (let posicao = 0; posicao < pixels.length; posicao += 4) {
            const menorDistancia = Math.min(...amostras.map(amostra => distancia(
                pixels[posicao], pixels[posicao + 1], pixels[posicao + 2], amostra
            )))
            if (menorDistancia < 45) pixels[posicao + 3] = 0
        }

        contexto.putImageData(imagemData, 0, 0)
        const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'))
        if (!blob) return

        foto.file = new File([blob], `${foto.nome.replace(/\.[^.]+$/, '')}-sem-fundo.png`, { type: 'image/png' })
        foto.nome = foto.file.name
        foto.url = URL.createObjectURL(blob)
        foto.removidoFundo = true
    } catch (error) {
        console.error('Erro ao remover fundo:', error)
        Swal.fire('Não foi possível editar a foto', 'Tente usar uma imagem JPG, PNG ou WEBP.', 'warning')
    } finally {
        foto.removendoFundo = false
    }
}

async function removerFoto(index) {
    const confirmacao = await Swal.fire({
        icon: 'warning',
        title: 'Remover foto?',
        text: 'Esta foto será removida do produto.',
        showCancelButton: true,
        confirmButtonText: 'Sim, remover',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626'
    })

    if (confirmacao.isConfirmed) {
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
            Swal.fire({
                icon: 'warning',
                title: 'Formato inválido',
                text: 'Use números separados por vírgula.'
            })
        }
    }
}

// ========== SALVAR ==========
function salvar() {
    // Validar campos obrigatórios
    for (const campo of props.camposObrigatorios) {
        const value = form[campo]
        if (!value || (typeof value === 'string' && value.trim() === '')) {
            Swal.fire({
                icon: 'warning',
                title: 'Campo obrigatório',
                text: `O campo "${campo}" é obrigatório.`
            })
            return
        }
    }

    for (const atributo of atributosDinamicos.value) {
        if (atributo.obrigatorio) {
            const nome = atributo.Descricao || atributo.nome
            const value = form[nome]
            if (!value || (typeof value === 'string' && value.trim() === '')) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo obrigatório',
                    text: `O campo "${nome}" é obrigatório.`
                })
                return
            }
        }
    }

    carregando.value = true

    // Garantir que o IVA selecionado é o que vai para a tabela
    const campoIva = getCampoIvaNome()
    if (campoIva && form[campoIva] !== undefined) {
        // não sobrescrever — apenas garantir que está no objeto
    }

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

function registarcategoria() {
    registocategoria.value = true;
}

function atribuirnovascategorias(dados) {
    registocategoria.value = false;

    categoriasgrupo.value = dados;
    form.categoria = dados[dados.length - 1].id
    form.Categoria = dados[dados.length - 1].id
    buscarAtributos(dados[dados.length - 1].id);
}

// ========== WATCH IVA (CORRIGIDO) ==========
// Só define o valor por defeito se o campo ainda estiver vazio.
// Nunca sobrescreve a escolha do utilizador.
watch(
    () => props.iva,
    (novo) => {
        if (!novo || !Array.isArray(novo)) return;

        const ivaAtivo = novo.find(item => item.status == 1);
        if (!ivaAtivo) return;

        const campoIva = getCampoIvaNome();
        if (!campoIva) return;

        // Só define se ainda não houver valor no formulário
        if (
            form[campoIva] === undefined ||
            form[campoIva] === null ||
            form[campoIva] === '' ||
            form[campoIva] === 0
        ) {
            form[campoIva] = ivaAtivo.percentagem;
        }
    },
    { immediate: true }
);

// ========== WATCH LUCRO (CORRIGIDO) ==========
watch(
    () => props.lucro,
    (novo) => {
        if (!novo || !Array.isArray(novo)) return;

        const lucroAtivo = novo.find(item => item.status == 1);
        if (!lucroAtivo) return;

        // Só define se ainda não houver valor no formulário
        if (
            form.lucro === undefined ||
            form.lucro === null ||
            form.lucro === '' ||
            form.lucro === 0
        ) {
            form.lucro = lucroAtivo.percentagem;
        }
    },
    { immediate: true }
);

// ========== WATCH ARMAZEM ==========
watch(
    () => props.armazem,
    (novo) => {
        if (novo?.length > 0 && !form['armazem']) {
            form['armazem'] = novo[0].Descricao;
        }
    },
    { immediate: true }
);

// ========== CÁLCULO DE PREÇOS ==========
function calcularPrecosVenda() {
    const precoCompra = Number(form['Preço Compra'] || form['preco_compra'] || 0)
    const percentagemLucro = Number(form.lucro || 0)
    const valorLucro = precoCompra * (percentagemLucro / 100)
    const precoVenda = precoCompra + valorLucro

    form['Preço Venda cliente 1'] = precoVenda
    form['Preço Venda cliente 2'] = precoVenda
    form['Preço Venda'] = precoVenda
}

watch(
    [
        () => form['Preço Compra'],
        () => form['preco_compra'],
        () => form.lucro
    ],
    calcularPrecosVenda,
    { immediate: true }
);

// ========== CÁLCULO DE VENDA COM IVA ==========
watch(
    [
        () => form['IVA'],
        () => form['iva'],
        () => form['venda_iva'],
        () => form['Preço Venda cliente 1'],
        () => form['Preço Venda cliente 2']
    ],
    () => {
        const campoIva = getCampoIvaNome()
        const iva = Number(form[campoIva] || form['IVA'] || form['iva'] || form['venda_iva'] || 0) / 100
        const precoVendaCliente1 = Number(form['Preço Venda cliente 1'] || 0)
        const precoVendaCliente2 = Number(form['Preço Venda cliente 2'] || 0)

        form['Venda com IVA cliente 1'] = precoVendaCliente1 + (precoVendaCliente1 * iva)
        form['Venda com IVA cliente 2'] = precoVendaCliente2 + (precoVendaCliente2 * iva)
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