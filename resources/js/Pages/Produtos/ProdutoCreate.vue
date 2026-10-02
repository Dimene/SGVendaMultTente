<script setup>
import { Head } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";
import { AgGridVue } from "ag-grid-vue3";
import { ModuleRegistry, AllCommunityModule } from "ag-grid-community";
import ModalItem from "../Compras/modalItem.vue";
import Swal from 'sweetalert2'
import * as XLSX from "xlsx";
import { saveAs } from "file-saver";

// Registrar módulos AG Grid
ModuleRegistry.registerModules([AllCommunityModule]);

// ===================== PROPS =====================
const props = defineProps({
    compras: {
        type: Array,
        default: () => []
    },
    compra_id: {
        type: Number,
        default: 0
    },
    armazem: {
        type: Array,
        default: () => []
    },
    dadosconpra: {
        type: Array,
        default: () => []
    },
    iva: {
        type: Array,
        default: () => []
    },
    lucro: {
        type: Array,
        default: () => []
    },
    guardarProduto: {
        type: Number,
        default: 0
    },
    comprafeita: {
        type: Number,
        default: 0
    },
    grupoItem: {
        type: Array,
        default: () => []
    },
    resetarForm: {
        type: Number,
        default: 0
    },
    totalCompra: {
        type: Number,
        default: 0
    },
    totalvenda: {
        type: Number,
        default: 0
    },
    totalvendaIva: {
        type: Number,
        default: 0
    }
});

// ===================== EMITS =====================
const emit = defineEmits([
    'salvar',
    'update:guadardad',
    'update:totalCompra',
    'update:totalvenda',
    'update:totalvendaIva',
]);

// ===================== MODAL =====================
const abrirModal = ref(false);
const itemSelecionado = ref(null);

// ===================== GRUPOS =====================
const grupo = ref([...props.grupoItem]);

const habaativada = ref(
    props.grupoItem.length ? props.grupoItem[0].nome : ""
);

// ===================== TABELAS DINÂMICAS =====================
const dadosstributo = ref([]);
const categoriaDados = ref([]);
const colDefs = ref([]);

// ===================== AG GRID =====================
const searchText = ref("");
const rowData = ref([]);
const totalCompra = ref([]);
const totalvenda = ref([]);
const totalvendaIva = ref([]);

const rowDataFiltrado = computed(() => {
    return rowData.value.filter(
        item => item.grupo === habaativada.value
    );
});

// Dados do formulário (para adicionar itens)
const formData = ref({});

// Atualizar compras
watch(
    () => props.compras,
    (dados) => {
        rowData.value = dados || [];
    },
    { immediate: true }
);

watch(() => props.resetarForm, (novo) => {
    rowData.value = [];
});

// ===================== REMOVER GRUPO =====================
function removergrupo(index) {
    if (grupo.value.length <= 1) return;

    grupo.value.splice(index, 1);

    if (
        habaativada.value &&
        !grupo.value.find((g) => g.nome === habaativada.value)
    ) {
        habaativada.value = grupo.value[0]?.nome ?? "";
    }
}

// ===================== MONTAR COLUNAS =====================
function atributosDados(nomeGrupo) {
    const grupoSelecionado = props.grupoItem.find(
        (g) => g.nome === nomeGrupo
    );

    if (!grupoSelecionado) {
        dadosstributo.value = [];
        colDefs.value = [];
        return;
    }

    const colunas = [
        "Nr",
        "Foto",
        "categoria",
        "Nome",
        "IVA",
        "lucro",
        "Desconto (%)",
    ];

    const colunastabela = [
        {
            field: "id",
            headerName: "Nr",
            sortable: true,
            filter: true,
            width: 80,
            valueGetter: (params) => {
                return params.node.rowIndex + 1;
            }
        },
        {
            field: "foto",
            headerName: "Foto",
            sortable: false,
            filter: false,
            width: 100,
            cellRenderer: (params) => {
                if (params.value) {
                    return `<img src="${params.value}" class="object-cover w-10 h-10 rounded" />`;
                }
                return `<i class="text-2xl text-gray-300 fas fa-image"></i>`;
            }
        },
        {
            field: "armazem",
            headerName: "Armazém",
            sortable: true,
            filter: true,
            width: 150
        },
        {
            field: "categoria",
            headerName: "categoria",
            sortable: true,
            filter: true,
            width: 150,
            valueGetter: (params) => {
                const categoria = categoriaDados.value.find(c => c.id === params.data?.categoria);
                return categoria ? categoria.nome : params.data?.categoria || '';
            }
        },
        {
            field: "IVA",
            headerName: "IVA",
            sortable: true,
            filter: true,
            width: 150,
        },
        {
            field: "Nome",
            headerName: "Nome",
            sortable: true,
            filter: true,
            width: 150
        },
        {
            field: "Desconto (%)",
            headerName: "Desconto (%)",
            sortable: true,
            filter: true,
            width: 130,
            valueFormatter: (params) => `${Number(params.value || 0).toFixed(2)}%`
        }
    ];

    const categorias = [];

    // atributos dinâmicos
    grupoSelecionado.listaatributo?.forEach((atributo) => {
        colunas.push(atributo.Descricao);
        colunastabela.push({
            field: atributo.Descricao,
            headerName: atributo.Descricao,
            sortable: true,
            filter: true,
            width: 150
        });
    });

    colunastabela.push({
        field: "outros_Atributos",
        headerName: "Outros Atributos",
        sortable: true,
        filter: true,
        width: 300,
        autoHeight: true,
        wrapText: true
    });

    // colunas fixas
    colunas.push(
        "Stock",
        "Preço Compra",
        "Preço Venda cliente 1",
        "Preço Venda cliente 2",
        "Venda com IVA cliente 1",
        "Venda com IVA cliente 2"
    );

    colunastabela.push(
        {
            field: "Stock",
            headerName: "Stock",
            sortable: true,
            filter: true,
            width: 100,
            cellRenderer: (params) => {
                const value = params.value || 0;
                const color = value <= 5 ? 'text-red-600' : value <= 20 ? 'text-yellow-600' : 'text-green-600';
                return `<span class="font-semibold ${color}">${value}</span>`;
            }
        },
        {
            field: "Preço Compra",
            headerName: "Preço Compra/unid.",
            sortable: true,
            filter: true,
            width: 160,
            valueFormatter: (params) => {
                return params.value ? `MZ ${Number(params.value).toFixed(2)}` : ' 0.00';
            }
        },
        {
            field: "Preço Venda cliente 1",
            headerName: "Preço Venda 1 - Singular",
            sortable: true,
            filter: true,
            width: 180,
            valueFormatter: (params) => {
                const valor = params.value ?? params.data?.['Preço Venda'] ?? 0;
                return valor ? `MZ ${Number(valor).toFixed(2)}` : 'MZ 0.00';
            }
        },
        {
            field: "Preço Venda cliente 2",
            headerName: "Preço Venda 2 - Empresa",
            sortable: true,
            filter: true,
            width: 180,
            valueFormatter: (params) => {
                return params.value ? `MZ ${Number(params.value).toFixed(2)}` : 'MZ 0.00';
            }
        },
        {
            field: "Venda com IVA cliente 1",
            headerName: "Venda c/ IVA cliente 1.",
            sortable: true,
            filter: true,
            width: 180,
            valueFormatter: (params) => {
                return `MZ ${Number(params.value || 0).toFixed(2)}`;
            }
        },
        {
            field: "Venda com IVA cliente 2",
            headerName: "Venda c/ IVA cliente 2.",
            sortable: true,
            filter: true,
            width: 180,
            valueFormatter: (params) => {
                return `MZ ${Number(params.value || 0).toFixed(2)}`;
            }
        },
        {
            field: "accoes",
            headerName: "Ações",
            width: 120,
            sortable: false,
            filter: false,
            cellRenderer: () => {
                return `
                    <div class="flex items-center justify-center gap-2">
                        <button class="inline-flex items-center justify-center w-8 h-8 text-sm text-white transition-all duration-200 bg-indigo-600 rounded-lg shadow-sm btn-visualizar hover:bg-indigo-700" title="Visualizar">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="inline-flex items-center justify-center w-8 h-8 text-sm text-white transition-all duration-200 bg-red-600 rounded-lg shadow-sm btn-eliminar hover:bg-red-700" title="Apagar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
            },
            onCellClicked: (params) => {
                const button = params.event.target.closest("button");

                if (!button) return;

                if (button.classList.contains("btn-visualizar")) {
                    editarItem(params.data);
                }

                if (button.classList.contains("btn-eliminar")) {
                    eliminarItem(params.data);
                }
            }
        }
    );

    grupoSelecionado.categoria?.forEach((cat) => {
        categorias.push(cat);
    });

    dadosstributo.value = colunas;
    categoriaDados.value = categorias;
    colDefs.value = colunastabela;
}

// ===================== AÇÕES DO GRID =====================
function editarItem(item) {
    itemSelecionado.value = item;
    abrirModal.value = true;
}

async function eliminarItem(item) {

    console.log("Produto a eliminar:", item);

    const resultado = await Swal.fire({
        title: 'Tem certeza?',
        text: `Eliminar ${item.Nome}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, eliminar'
    });

    if (!resultado.isConfirmed) {
        return;
    }

    try {
        console.log("dfkjdfdfkdfiofdcvvbbvbv33444", props.comprafeita)
        const resposta = await axios.delete('/produtos/delete', {
            data: {
                item: item,
                compra_id: props.comprafeita
            }
        });

        if (resposta.data.success) {

            rowData.value = rowData.value.filter(
                produto => produto.id !== item.id
            );

            Swal.fire({
                icon: 'success',
                title: 'Eliminado!',
                text: resposta.data.message
            });
        }

    } catch (error) {

        console.error(error);

        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: 'Não foi possível eliminar'
        });

    }
}

// ===================== SALVAR ITEM =====================
function salvarItem(dados) {

    console.log("Dados recebidos:", dados);

    const camposTabela = new Set([
        "id",
        "grupo",
        "foto",
        "fotos",
        "armazem",
        "categoria",
        "Nome",
        "IVA",
        "lucro",
        "Desconto (%)",
        "Stock",
        "Preço Compra",
        "Preço Venda",
        "Preço Venda cliente 1",
        "Preço Venda cliente 2",
        "Venda com IVA cliente 1",
        "Venda com IVA cliente 2",
        "outros_Atributos"
    ]);

    grupo.value
        .find(g => g.nome === habaativada.value)
        ?.listaatributo
        ?.forEach(atributo => {
            const campo = atributo.nome || atributo.Descricao;
            if (campo) {
                camposTabela.add(campo);
            }
        });

    console.log("Campos que pertencem à tabela:", [...camposTabela]);

    const outros = [];

    Object.entries(dados).forEach(([campo, valor]) => {

        if (valor === undefined || valor === null || valor === '') {
            return;
        }

        if (!camposTabela.has(campo)) {
            outros.push(`${campo}: ${valor}`);
        }

    });

    const itemTabela = {
        ...dados,
        grupo: dados.grupo || habaativada.value,
        outros_Atributos: outros.length
            ? outros.join(" | ")
            : (dados.outros_Atributos || '')
    };

    console.log("Outros atributos:", itemTabela.outros_Atributos);

    if (itemTabela.id) {

        const index = rowData.value.findIndex(
            i => i.id === itemTabela.id
        );

        if (index !== -1) {
            rowData.value[index] = {
                ...rowData.value[index],
                ...itemTabela
            };
        } else {
            rowData.value.push(itemTabela);
        }

    } else {

        itemTabela.id =
            crypto.randomUUID?.() || Date.now();

        rowData.value.push(itemTabela);
    }

    rowData.value = [...rowData.value];

    fecharModal();
}

// ===================== FECHAR MODAL =====================
function fecharModal() {
    abrirModal.value = false;
    itemSelecionado.value = null;
}

// Atualizar ao mudar grupo
watch(
    habaativada,
    (novoValor) => {
        atributosDados(novoValor);
    },
    { immediate: true }
);

// ===================== GRID READY =====================
function onGridReady(params) {
    params.api.sizeColumnsToFit();

    window.addEventListener("resize", () => {
        params.api.sizeColumnsToFit();
    });
}

watch(
    () => props.guardarProduto,
    (novo) => {
        console.log('Mudou:', novo)

        if (novo > 0) {
            guardardados(novo)
            emit('update:guadardad', 0);
        }
    }
)

async function guardardados() {

    const itemsToSend = rowData.value.map(item => ({ ...item }));

    if (itemsToSend.length === 0) {
        await Swal.fire({
            icon: 'warning',
            title: 'Aviso',
            text: 'Não há produtos para guardar.'
        });

        return;
    }

    console.log('COMPRA ID recebido:', props.comprafeita);
    console.log('COMPRA ID recebido:', props.compra_id);
    console.log('Produtos:', itemsToSend);

    try {

        const response = await axios.post('/Produto', {
            compra_id: props.comprafeita,
            items: itemsToSend
        });

        if (response.data.ok) {

            await Swal.fire({
                icon: 'success',
                title: 'Sucesso',
                text: 'Produtos guardados com sucesso'
            });
        }

    } catch (error) {

        console.error(
            'Erro ao guardar produtos:',
            error.response?.data || error
        );

        await Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: 'Falha ao guardar produtos. Tente novamente.'
        });
    }
}

watch(
    () => rowData.value,
    (novoItem) => {

        let totalCompraTemp = 0;
        let totalVendaTemp = 0;
        let totalVendaIvaTemp = 0;

        novoItem.forEach(item => {
            const precoVendaCliente1 = Number(item["Preço Venda cliente 1"] ?? item["Preço Venda"] ?? 0);
            const precoVendaCliente2 = Number(item["Preço Venda cliente 2"] ?? 0);
            const vendaComIvaCliente1 = Number(item["Venda com IVA cliente 1"] ?? 0);
            const vendaComIvaCliente2 = Number(item["Venda com IVA cliente 2"] ?? 0);
            totalCompraTemp += Number(item["Preço Compra"] || 0) * Number(item["Stock"] || 0);
            totalVendaTemp += (precoVendaCliente1 + precoVendaCliente2) * Number(item["Stock"] || 0);
            totalVendaIvaTemp += (vendaComIvaCliente1 + vendaComIvaCliente2) * Number(item["Stock"] || 0);
        });

        totalCompra.value = totalCompraTemp;
        totalvenda.value = totalVendaTemp;
        totalvendaIva.value = totalVendaIvaTemp;

        emit("update:totalCompra", totalCompra.value);
        emit("update:totalvenda", totalvenda.value);
        emit("update:totalvendaIva", totalvendaIva.value);

    },
    {
        deep: true,
        immediate: true
    }
);

watch(
    () => props.dadosconpra,
    (nova) => {
        if (!nova || !Array.isArray(nova)) return;

        const itensPorGrupo = nova.reduce((acc, item) => {
            const grupo = item.grupo || 'Sem grupo';
            if (!acc[grupo]) {
                acc[grupo] = [];
            }
            acc[grupo].push(item);
            return acc;
        }, {});

        const gruposComItens = Object.keys(itensPorGrupo);

        const gruposValidos = gruposComItens.filter(grupoNome =>
            props.grupoItem.some(g => g.nome === grupoNome)
        );

        if (gruposValidos.length === 0) {
            console.warn('Nenhum grupo válido encontrado para os itens');
            return;
        }

        const grupoAtivo = gruposValidos.reduce((a, b) =>
            itensPorGrupo[a].length > itensPorGrupo[b].length ? a : b
        );

        habaativada.value = grupoAtivo;
        atributosDados(grupoAtivo);

        gruposValidos.forEach(grupoNome => {
            itensPorGrupo[grupoNome].forEach(item => {
                item.grupo = grupoNome;
                salvarItem(item);
            });
        });
    },
    { immediate: true }
);

// ===================== GERAR EXCEL =====================
const produtosPorGrupo = computed(() => {
    return rowData.value.reduce((grupos, item) => {

        const nomeGrupo = item.grupo || 'Sem grupo';

        if (!grupos[nomeGrupo]) {
            grupos[nomeGrupo] = [];
        }

        grupos[nomeGrupo].push(item);

        return grupos;

    }, {});
});

async function gerarModeloExcel() {
    if (!habaativada.value) {
        await Swal.fire({
            icon: 'warning',
            title: 'Aviso',
            text: 'Selecione um grupo antes de gerar o modelo.'
        });
        return;
    }

    try {
        const response = await axios.get(
            `/produtos/modelo/import/${encodeURIComponent(habaativada.value)}`,
            { responseType: 'blob' }
        );

        saveAs(
            response.data,
            `Modelo_${habaativada.value.replace(/\s+/g, '_')}.xlsx`
        );
    } catch (e) {
        console.error(e);
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: 'Não foi possível gerar o modelo.'
        });
    }
}

// ===================== IMPORTAR EXCEL =====================
const inputExcel = ref(null);


// Abas auxiliares reconhecidas (nome normalizado → campo alvo)
const ABAS_AUXILIARES = {
    "armazens": "armazem",
    "armazém": "armazem",
    "armazem": "armazem",
    "categorias": "categoria",
    "categoria": "categoria",
    "listas": null, // abas de listas para dropdown (ignoradas na importação)
};

// Normaliza texto (remove acentos, espaços, minúsculas)
function normalizar(txt) {
    return String(txt || "")
        .trim()
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "");
}

// Constrói dicionário de lookup a partir de uma aba auxiliar
// Ex: { "1": "Armazém Central", "2": "Armazém Norte" }
function construirLookup(worksheet) {
    const linhas = XLSX.utils.sheet_to_json(worksheet, {
        defval: "",
        raw: false,
    });

    const mapa = {};

    linhas.forEach((linha) => {
        // Procura colunas típicas: ID/Codigo, Nome/Descricao
        const chavesPossiveis = ["id", "codigo", "código", "cod"];
        const valoresPossiveis = ["nome", "descricao", "descrição", "designacao", "designação"];

        const chaveCol = Object.keys(linha).find((k) =>
            chavesPossiveis.includes(normalizar(k))
        );
        const valorCol = Object.keys(linha).find((k) =>
            valoresPossiveis.includes(normalizar(k))
        );

        if (chaveCol && valorCol) {
            const chave = String(linha[chaveCol]).trim();
            const valor = String(linha[valorCol]).trim();
            if (chave && valor) {
                mapa[chave] = valor;
            }
        }
    });

    return mapa;
}

async function importarExcel(event) {
    const ficheiro = event.target.files?.[0];

    if (!ficheiro) return;

    try {
        const buffer = await ficheiro.arrayBuffer();
        const workbook = XLSX.read(buffer, { type: "array" });

        // =====================================================
        // 1) PRIMEIRA PASSAGEM: ler abas auxiliares (lookup)
        // =====================================================
        const lookups = {
            armazem: {},
            categoria: {},
        };

        const abasAuxiliaresEncontradas = [];

        workbook.SheetNames.forEach((nomeAba) => {
            const nomeNorm = normalizar(nomeAba);
            const campoAlvo = ABAS_AUXILIARES[nomeNorm];

            if (campoAlvo) {
                const worksheet = workbook.Sheets[nomeAba];
                lookups[campoAlvo] = construirLookup(worksheet);
                abasAuxiliaresEncontradas.push(nomeAba);
            }
        });

        console.log("🔍 Lookups construídos:", lookups);
        console.log("📋 Abas auxiliares:", abasAuxiliaresEncontradas);

        // =====================================================
        // 2) SEGUNDA PASSAGEM: processar abas de grupos
        // =====================================================
        const gruposPorNome = props.grupoItem.reduce((acc, g) => {
            acc[normalizar(g.nome)] = g;
            return acc;
        }, {});

        const itensPorAba = {};
        let totalImportado = 0;
        const abasIgnoradas = [];

        workbook.SheetNames.forEach((nomeAba) => {
            const nomeNorm = normalizar(nomeAba);

            // Ignora abas auxiliares e "listas"
            if (ABAS_AUXILIARES[nomeNorm] !== undefined) return;
            if (nomeNorm === "listas") return;

            const grupo = gruposPorNome[nomeNorm];

            if (!grupo) {
                abasIgnoradas.push(nomeAba);
                return;
            }

            const worksheet = workbook.Sheets[nomeAba];
            const linhas = XLSX.utils.sheet_to_json(worksheet, {
                range: 2,
                defval: "",
                raw: false,
            });

            const limpas = linhas.filter((l) => {
                const nome = String(l["Nome"] || "").trim().toLowerCase();
                return nome && nome !== "produto exemplo";
            });

            if (limpas.length === 0) return;

            itensPorAba[grupo.nome] = limpas;
            totalImportado += limpas.length;
        });

        if (totalImportado === 0) {
            await Swal.fire({
                icon: "info",
                title: "Nada a importar",
                text: "Nenhuma aba corresponde a um grupo com itens.",
            });
            if (inputExcel.value) inputExcel.value.value = "";
            return;
        }

        // Confirmação com resumo
        const confirmacao = await Swal.fire({
            icon: "question",
            title: "Importar Excel?",
            html: `
                <p>Serão importados <strong>${totalImportado}</strong> itens.</p>
                ${
                    abasAuxiliaresEncontradas.length
                        ? `<p class="mt-2 text-sm text-green-600">
                            🔗 Abas auxiliares: ${abasAuxiliaresEncontradas.join(", ")}
                           </p>`
                        : ""
                }
                ${
                    abasIgnoradas.length
                        ? `<p class="mt-2 text-sm text-red-500">
                            Abas ignoradas: ${abasIgnoradas.join(", ")}
                           </p>`
                        : ""
                }
            `,
            showCancelButton: true,
            confirmButtonText: "Sim, importar",
            cancelButtonText: "Cancelar",
        });

        if (!confirmacao.isConfirmed) {
            if (inputExcel.value) inputExcel.value.value = "";
            return;
        }

        // Ativa o primeiro grupo antes de salvar
        const primeiroGrupo = Object.keys(itensPorAba)[0];
        if (primeiroGrupo) {
            habaativada.value = primeiroGrupo;
            atributosDados(primeiroGrupo);
        }

        // =====================================================
        // 3) SALVAR ITENS aplicando lookups
        // =====================================================
        Object.entries(itensPorAba).forEach(([nomeGrupo, itens]) => {
            itens.forEach((linha) => {
                // --- Resolver Armazém via lookup ---
                let armazemValor =
                    linha["Armazém"] || linha["armazem"] || linha["Armazem"] || "";

                // Se for um ID numérico, procura no lookup
                if (armazemValor && lookups.armazem[String(armazemValor).trim()]) {
                    armazemValor = lookups.armazem[String(armazemValor).trim()];
                }

                // --- Resolver Categoria via lookup ---
                let categoriaValor = linha["categoria"] || linha["Categoria"] || "";

                if (categoriaValor && lookups.categoria[String(categoriaValor).trim()]) {
                    categoriaValor = lookups.categoria[String(categoriaValor).trim()];
                }

                const item = {
                    grupo: nomeGrupo,
                    foto: linha["Foto"] || "",
                    armazem: armazemValor,
                    categoria: categoriaValor,
                    Nome: linha["Nome"] || "",
                    IVA: linha["IVA"] || "",
                    lucro: linha["lucro"] || "",
                    "Desconto (%)": linha["Desconto (%)"] || "",
                    Stock: Number(linha["Stock"] || 0),
                    "Preço Compra": Number(linha["Preço Compra"] || 0),
                    "Preço Venda cliente 1": Number(
                        linha["Preço Venda Singulares"] ||
                            linha["Preço Venda cliente 1"] ||
                            0
                    ),
                    "Preço Venda cliente 2": Number(
                        linha["Preço Venda Empresas"] ||
                            linha["Preço Venda cliente 2"] ||
                            0
                    ),
                    "Venda com IVA cliente 1": Number(
                        linha["Venda com IVA cliente 1"] || 0
                    ),
                    "Venda com IVA cliente 2": Number(
                        linha["Venda com IVA cliente 2"] || 0
                    ),
                    outros_Atributos: linha["Outros Atributos"] || "",
                };

                const fixos = new Set([
                    "Foto", "Armazém", "armazem", "Armazem", "categoria", "Categoria",
                    "Nome", "IVA", "lucro", "Desconto (%)", "Stock", "Preço Compra",
                    "Preço Venda Singulares", "Preço Venda Empresas",
                    "Preço Venda cliente 1", "Preço Venda cliente 2",
                    "Venda com IVA cliente 1", "Venda com IVA cliente 2",
                    "Outros Atributos",
                ]);

                Object.entries(linha).forEach(([campo, valor]) => {
                    if (!fixos.has(campo) && valor !== "" && valor != null) {
                        item[campo] = valor;
                    }
                });

                salvarItem(item);
            });
        });

        if (inputExcel.value) inputExcel.value.value = "";

        await Swal.fire({
            icon: "success",
            title: "Importado!",
            html: `
                <p>${totalImportado} itens carregados na tabela.</p>
                ${
                    abasAuxiliaresEncontradas.length
                        ? `<p class="mt-2 text-xs text-gray-500">
                            Dados cruzados com: ${abasAuxiliaresEncontradas.join(", ")}
                           </p>`
                        : ""
                }
            `,
        });
    } catch (erro) {
        console.error("Erro ao importar Excel:", erro);
        await Swal.fire({
            icon: "error",
            title: "Erro",
            text: "Não foi possível ler o ficheiro Excel.",
        });
        if (inputExcel.value) inputExcel.value.value = "";
    }
}
</script>

<template>
    <Head title="Compras" />

    <div class="bg-white rounded-lg shadow">
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-4 p-4 border-b">
            <h1 class="text-xl font-bold text-gray-800">
                <i class="mr-2 text-blue-600 fas fa-shopping-cart"></i>
                Compras
            </h1>

            <button
                class="flex items-center gap-2 px-4 py-2 text-white transition bg-green-600 rounded-lg hover:bg-green-700"
                @click="abrirModal = true"
            >
                <i class="fas fa-plus"></i>
                Novo {{ habaativada }}
            </button>
        </div>

        <!-- Botões Excel -->
        <div class="flex flex-wrap items-center gap-3 px-4 py-3 border-b">

            <button
                class="px-4 py-2 text-white bg-green-600 rounded hover:bg-green-700"
                @click="gerarModeloExcel"
            >
                <i class="fas fa-file-excel"></i>
                Baixar Modelo Excel
            </button>

            <input
                ref="inputExcel"
                type="file"
                accept=".xlsx,.xls"
                class="hidden"
                @change="importarExcel"
            />

            <button
                class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700"
                @click="inputExcel.click()"
            >
                <i class="fas fa-file-import"></i>
                Importar Excel
            </button>

        </div>

        <!-- Grupos -->
        <nav class="flex flex-wrap gap-2 px-4 pt-2 border-b">
            <div
                v-for="(item, index) in grupo"
                :key="item.nome"
                class="flex items-center gap-1"
            >
                <button
                    @click="habaativada = item.nome"
                    :class="[
                        'px-4 py-2 rounded-t-lg transition flex items-center gap-2',
                        habaativada === item.nome
                            ? 'bg-blue-700 text-white'
                            : 'bg-gray-100 hover:bg-gray-200'
                    ]"
                >
                    <i :class="['fas', item.icone]"></i>
                    {{ item.nome }}
                </button>

                <button
                    v-if="grupo.length > 1"
                    @click="removergrupo(index)"
                    class="p-2 rounded hover:bg-gray-200"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </nav>

        <!-- Conteúdo -->
        <div class="p-4">
            <!-- Pesquisa -->
            <div class="mb-4">
                <div class="relative">
                    <i class="absolute text-gray-400 fas fa-search left-3 top-3"></i>
                    <input
                        v-model="searchText"
                        type="text"
                        placeholder="Pesquisar produtos..."
                        class="w-full py-2 pl-10 pr-4 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>
            </div>

            <!-- Tabela -->
            <div class="overflow-x-auto">
                <AgGridVue
                    :rowData="rowDataFiltrado"
                    :columnDefs="colDefs"
                    :quickFilterText="searchText"
                    :pagination="true"
                    :paginationPageSize="15"
                    class="ag-theme-alpine"
                    style="height: 400px; width: 100%;"
                    @grid-ready="onGridReady"
                    :defaultColDef="{
                        resizable: true,
                        cellStyle: {
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center'
                        }
                    }"
                />
            </div>

            <!-- Rodapé com contagem -->
            <div class="flex items-center justify-between mt-4 text-sm text-gray-500">
                <span>
                    <i class="mr-1 fas fa-boxes"></i>
                    Total: {{ rowData.length }} itens
                </span>
                <span>
                    Grupo: <strong class="text-gray-700">{{ habaativada }}</strong>
                </span>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <ModalItem
        v-if="abrirModal"
        :dadosstributo="dadosstributo"
        :item="itemSelecionado"
        :habaativada="habaativada"
        :categorias="categoriaDados"
        :armazem="armazem"
        @fechar="fecharModal"
        @guardar="salvarItem"
        :iva="iva"
        :lucro="lucro"
    />

    <!-- Debug (remover em produção) -->
    <pre v-if="false" class="p-4 mt-4 overflow-auto text-xs bg-gray-100 rounded">
        {{ categoriaDados }}
    </pre>
</template>

<style scoped>
/* Estilos para AG Grid */
:deep(.ag-theme-alpine) {
    --ag-header-background-color: #f3f4f6;
    --ag-header-foreground-color: #1f2937;
    --ag-row-hover-color: #eff6ff;
}

/* Botões nas células do grid */
:deep(.ag-cell) {
    display: flex;
    align-items: center;
}

/* Animações */
button {
    transition: all 0.2s ease;
}
</style>