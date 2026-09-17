<script setup>
import { Head,useForm } from '@inertiajs/vue3'

import { ref, computed, nextTick, watch, onMounted } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'
import Swal from 'sweetalert2'

const emit = defineEmits([
    'compraCriada',
    'compraselecionada',
    'update:fornecedorID',
     'update:totalDespesa',
     'update:guadardad',
     'validarForm'

]);

const props=defineProps({
initialPedido: {
    type: String,
    default: ''
},
resetarForm:{
    type:Number,
    default:0
},
fornecedorID:{
  type:Number,
    default:0
},
guadardad:{
    type:Number,
    default:0
},
compra_id:{
    type:Number,
    default:0
},
totaldespesa:{
    type:Number,
    default:0
},

})


 const fornecedorid=ref(props.fornecedorID);

const QuantidadesDespesas = ref([

]);

// Estado de validação
const errors = ref({
    Fatura: '',
    estado: '',
    dataCompra: '',
    tipoCompra: '',
    fornecedorid:'',

    despesas: []
});

const form = useForm({
    Fatura: props.initialPedido,
    estado: '',
    dataCompra: '',
    tipoCompra: '',
    fornecedorid:'',
    observacoes: '',
    despesas: []
});

// Funções de validação
const validarCamposObrigatorios = () => {
    let isValid = true;
    errors.value = {
        Fatura: '',
        estado: '',
        dataCompra: '',
        tipoCompra: '',
        fornecedorid:'',
        despesas: []
    };

    // Validar Fatura
    if (!form.Fatura || form.Fatura.trim() === '') {
        errors.value.Fatura = 'O número do pedido/fatura é obrigatório';
        isValid = false;
    } else if (form.Fatura.length < 5) {
        errors.value.Fatura = 'O número do pedido deve ter pelo menos 5 caracteres';
        isValid = false;
    }


    if(!form.fornecedorid||form.fornecedorid===0){

    errors.value.fornecedorid = 'fornecedor nao deve  estar vazio';
    emit('validarForm','fornecedor nao deve  estar vazio');
    }

    // Validar Estado
    if (!form.estado || form.estado === '') {
        errors.value.estado = 'O status é obrigatório';
        isValid = false;
    }

    // Validar Data da Compra
    if (!form.dataCompra || form.dataCompra === '') {
        errors.value.dataCompra = 'A data da compra é obrigatória';
        isValid = false;
    } else {
        // Validar se a data não é futura
        // const dataSelecionada = new Date(form.dataCompra);
        const dataSelecionada = new Date(form.dataCompra + "T00:00:00")
        const dataAtual = new Date();
        dataAtual.setHours(0, 0, 0, 0);
        if (dataSelecionada > dataAtual) {
            errors.value.dataCompra = 'A data não pode ser futura';
            isValid = false;
        }
    }

    // Validar Tipo de Compra
    if (!form.tipoCompra || form.tipoCompra === '') {
        errors.value.tipoCompra = 'O tipo de compra é obrigatório';
        isValid = false;
    }

    // Validar Despesas
    const despesasErrors = [];
    let hasDespesaError = false;

    QuantidadesDespesas.value.forEach((despesa, index) => {
        const despesaError = {};

        if (!despesa.tipo || despesa.tipo === '' || despesa.tipo === 'selecione o tipo') {
            despesaError.tipo = 'Selecione um tipo de despesa';
            hasDespesaError = true;
        }

        if (!despesa.valor || despesa.valor <= 0) {
            despesaError.valor = 'O valor deve ser maior que 0';
            hasDespesaError = true;
        } else if (isNaN(despesa.valor)) {
            despesaError.valor = 'O valor deve ser um número válido';
            hasDespesaError = true;
        }

        if (despesa.tipo && despesa.tipo !== '' && despesa.tipo !== 'selecione o tipo') {
            if (!despesa.descricao || despesa.descricao.trim() === '') {
                despesaError.descricao = 'A descrição é obrigatória';
                hasDespesaError = true;
            } else if (despesa.descricao.length < 3) {
                despesaError.descricao = 'A descrição deve ter pelo menos 3 caracteres';
                hasDespesaError = true;
            }
        }

        despesasErrors.push(despesaError);
    });

    if (hasDespesaError) {
        errors.value.despesas = despesasErrors;
        isValid = false;
    }

    return isValid;
};

function adicionarLinha() {
    QuantidadesDespesas.value.push({
        tipo: '',
        valor: '',
        descricao: ''
    });
    // Limpar erros da nova linha
    errors.value.despesas.push({});
}

function limparlinha(index) {

        QuantidadesDespesas.value.splice(index, 1);
        errors.value.despesas.splice(index, 1);

}

// Computed para total das despesas
const totalDespesas = computed(() => {
    return QuantidadesDespesas.value.reduce((total, item) => {
        const valor = parseFloat(item.valor) || 0;


        return total + valor;
    }, 0);
});

// Verificar se há erros em uma despesa específica
const hasDespesaError = (index) => {
    const despesaErrors = errors.value.despesas[index] || {};
    return Object.keys(despesaErrors).some(key => despesaErrors[key] !== '');
};

const handleFieldKeydown = (event) => {
    const field = event.target;
    if (!field || !['INPUT', 'TEXTAREA'].includes(field.tagName)) return;

    const isEnter = event.key === 'Enter';
    const isArrowNavigation = ['ArrowDown', 'ArrowRight', 'ArrowUp', 'ArrowLeft'].includes(event.key);

    if (isEnter && field.tagName !== 'TEXTAREA') {
        event.preventDefault();
        submit();
        return;
    }

    if (!isArrowNavigation || field.tagName === 'TEXTAREA') return;

    const formElement = field.closest('form');
    if (!formElement) return;

    const fields = Array.from(
        formElement.querySelectorAll('input:not([disabled]), textarea:not([disabled]), select:not([disabled])')
    );

    const currentIndex = fields.indexOf(field);
    if (currentIndex === -1) return;

    event.preventDefault();

    const nextField = event.key === 'ArrowDown' || event.key === 'ArrowRight'
        ? fields[currentIndex + 1] || fields[0]
        : fields[currentIndex - 1] || fields[fields.length - 1];

    if (nextField) {
        nextField.focus();
    }
};

const submit = async () => {
    // Validar antes de submeter
    if (!validarCamposObrigatorios()) {
        // Rolar para o primeiro erro
        const firstError = document.querySelector('.error-message');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return;
    }

    try {
        // Preparar dados para envio
        const dadosParaEnvio = {
            ...form,
            despesas: QuantidadesDespesas.value.filter(item =>
                item.tipo && item.tipo !== '' && item.tipo !== 'selecione o tipo' &&
                item.valor && item.valor > 0
            )
        };
// alert(dadosParaEnvio);

        const response = await axios.post('/compras', dadosParaEnvio);

// alert("ddddddd d d d",response.data);
        // Resetar formulário após sucesso
        // form.reset();
        // QuantidadesDespesas.value = [{ tipo: '', valor: '', descricao: '' }];


console.log("podfpogpgp ertpotrpotr ",response.data.compra_id);
   if (parseInt(response.data.compra_id)>0) {

console.log("a eminti---",response.data.compra_id);
    // emit('update:compraCriada', response.data.compra_id);
    emit('compraCriada',  response.data.compra_id);


}
        // alert('Compra registrada com sucesso!');
    } catch (error) {
        console.error('Erro ao salvar:', error.response?.data || error.message);
        // Processar erros do backend se houver
        if (error.response?.data?.errors) {
            // Mapear erros do backend
            const backendErrors = error.response.data.errors;
            if (backendErrors.Fatura) {
                errors.value.Fatura = backendErrors.Fatura[0];
            }
            if (backendErrors.estado) {
                errors.value.estado = backendErrors.estado[0];
            }
            // etc...
        }
        Swal.fire({
            icon: 'error',
            title: 'Erro ao salvar compra',
            text: 'Verifique os dados e tente novamente.'
        });
    }
};

// Função para limpar erros quando o usuário começa a digitar
const limparErro = (campo) => {
    if (campo === 'despesas') {
        // Limpar erros de despesas é feito individualmente
    } else {
        errors.value[campo] = '';
    }
};

// Resetar formulário
const resetarForm = () => {
    form.Fatura = '';
    form.estado = '';
    form.dataCompra = '';
    form.tipoCompra = '';
    form.observacoes = '';
    QuantidadesDespesas.value = [{ tipo: '', valor: '', descricao: '' }];
    errors.value = {
        Fatura: '',
        estado: '',
        dataCompra: '',
        tipoCompra: '',
        despesas: []
    };
};

// Limpar todas as despesas
const limparTodasDespesas = () => {
    QuantidadesDespesas.value = [{ tipo: '', valor: '', descricao: '' }];
    errors.value.despesas = [{}];
};


watch(
    () => props.resetarForm,
    (novo) => {

        console.log(novo);
        if (novo) {
            resetarForm();

        }
    }
)

// escutar clique para guadar registo
watch(
    () => props.guadardad,
    async (novo, antigo) => {

        if (novo === antigo) return;
        if (!novo) return;

        console.log('🚀 Iniciando gravação...');

        await submit();
    }
);
// escutar clique para guadar registo
watch(
    () => props.fornecedorID,
    (novo) => {

        // console.log(novo);
       form.fornecedorid=novo;
    }
)

let timeout = null;

watch(() => form.Fatura, (novo) => {
    clearTimeout(timeout);

    if (!novo || novo.length < 3) return;

    timeout = setTimeout(() => {
        buscarCompra(novo);
    }, 500);
});



async function buscarCompra(id) {
    try {
        const response = await axios.get(`/compras/${id}`);
        const dados = response.data;

        if (!dados) return;

        console.log(dados);

        form.estado = dados.status;
        form.observacoes = dados.observacoes;
        form.dataCompra = dados.data_compra;
        form.tipoCompra = dados.tipo?.Descricao;

        // 🔥 limpar array correto
        QuantidadesDespesas.value = [];

        (dados.despesas ?? []).forEach((element) => {
            QuantidadesDespesas.value.push({
                tipo: element.tipo,
                valor: element.valor,
                descricao: element.descricao
            });
        });

        emit('update:fornecedorID', dados.fornecedor_id);

        console.log('Emitindo compra_id:', dados.id);
emit('compraselecionada', dados.id);

        // emit("update:compra_id",dados.id)


    } catch (error) {
        console.error('Erro ao buscar compra:', error);
    }
}

onMounted(() => {
    if (props.initialPedido) {
        buscarCompra(props.initialPedido)
    }
})



// Watch para emitir sempre que o total mudar
watch(totalDespesas, (novoTotal) => {
    emit('update:totalDespesa', novoTotal);
}, { immediate: true }); // immediate: true emite o valor inicial também

</script>

<template>
    <div class="bg-white rounded-lg shadow border-t-4 border-gray-900">




        <div class="p-6">
            <form @submit.prevent="submit" @keydown="handleFieldKeydown">
            <!-- <input type="number" v-modal="form.fornecedorid"> -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-hashtag mr-2 text-blue-500"></i>
                            Número do Pedido/Fatura
                            <span class="text-red-500 ml-1">*</span>
                        </label>
                        <input
                            v-model="form.Fatura"
                            @input="limparErro('Fatura')"
                            type="text"
                            placeholder="EX: PED-2024-001"
                            :class="[
                                'w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all',
                                errors.Fatura ? 'border-red-500' : 'border-gray-300'
                            ]"
                        >
                        <p v-if="errors.Fatura" class="mt-1 text-sm text-red-500">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            {{ errors.Fatura }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-flag mr-2 text-blue-500"></i>
                            Status
                            <span class="text-red-500 ml-1">*</span>
                        </label>
                        <select
                            v-model="form.estado"
                            @change="limparErro('estado')"
                            :class="[
                                'w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all',
                                errors.estado ? 'border-red-500' : 'border-gray-300'
                            ]"
                        >
                            <option value="">Selecione um status</option>
                            <option value="pendente">⏳ Pendente</option>
                            <option value="APROVADO">✅ Aprovado</option>
                            <option value="em_andamento">🔄 Em Andamento</option>
                            <option value="concluido">✔️ Concluído</option>
                            <option value="cancelado">❌ Cancelado</option>
                            <option value="entregue">📦 Entregue</option>
                        </select>
                        <p v-if="errors.estado" class="mt-1 text-sm text-red-500">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            {{ errors.estado }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>
                            Data da Compra
                            <span class="text-red-500 ml-1">*</span>
                        </label>
                        <input
                            v-model="form.dataCompra"
                            @input="limparErro('dataCompra')"
                            type="date"
                            :class="[
                                'w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all',
                                errors.dataCompra ? 'border-red-500' : 'border-gray-300'
                            ]"
                        >
                        <p v-if="errors.dataCompra" class="mt-1 text-sm text-red-500">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            {{ errors.dataCompra }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-tag mr-2 text-blue-500"></i>
                            Tipo de Compra
                            <span class="text-red-500 ml-1">*</span>
                        </label>
                        <select
                            v-model="form.tipoCompra"
                            @change="limparErro('tipoCompra')"
                            :class="[
                                'w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all',
                                errors.tipoCompra ? 'border-red-500' : 'border-gray-300'
                            ]"
                        >
                            <option value="">Selecione o tipo</option>
                            <option value="nacional">🇲🇿 Nacional</option>
                            <option value="internacional">🌍 Internacional</option>
                            <option value="importacao">📦 Importação</option>
                            <option value="local">🏪 Local</option>
                        </select>
                        <p v-if="errors.tipoCompra" class="mt-1 text-sm text-red-500">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            {{ errors.tipoCompra }}
                        </p>
                    </div>
                </div>

                <!-- DESPESAS -->
                <div class="mt-8 border-t-2 border-gray-200 pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <label class="block text-lg font-semibold text-gray-800">
                            <i class="fas fa-coins mr-2 text-yellow-500"></i>
                            Despesas da Compra
                        </label>
                        <button
                            type="button"
                            @click="adicionarLinha"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-all duration-200 shadow-md hover:shadow-lg"
                        >
                            <i class="fas fa-plus-circle"></i>
                            Adicionar Despesa
                        </button>
                    </div>

                    <div
                        v-for="(item, index) in QuantidadesDespesas"
                        :key="index"
                        class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-4 p-4 bg-gray-50 rounded-lg"
                        :class="{ 'border-2 border-red-300': hasDespesaError(index) }"
                    >
                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                <i class="fas fa-tag mr-1"></i>
                                Tipo de Despesa
                                <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="item.tipo"
                                :class="[
                                    'w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm',
                                    errors.despesas[index]?.tipo ? 'border-red-500' : 'border-gray-300'
                                ]"
                            >
                                <option value="">Selecione o tipo</option>
                                <option value="frete">🚚 Frete</option>
                                <option value="seguro">🛡️ Seguro</option>
                                <option value="imposto">📄 Imposto</option>
                                <option value="taxa">💳 Taxa</option>
                                <option value="armazenagem">🏚️ Armazenagem</option>
                                <option value="transporte">🚛 Transporte</option>
                                <option value="embalagem">📦 Embalagem</option>
                                <option value="desalfandegamento">🛃 Desalfandegamento</option>
                                <option value="outros">📌 Outros</option>
                            </select>
                            <p v-if="errors.despesas[index]?.tipo" class="mt-1 text-xs text-red-500">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                {{ errors.despesas[index].tipo }}
                            </p>
                        </div>

                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                <i class="fas fa-money-bill-wave mr-1"></i>
                                Valor (MZN)
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model.number="item.valor"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                :class="[
                                    'w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm',
                                    errors.despesas[index]?.valor ? 'border-red-500' : 'border-gray-300'
                                ]"
                            >
                            <p v-if="errors.despesas[index]?.valor" class="mt-1 text-xs text-red-500">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                {{ errors.despesas[index].valor }}
                            </p>
                        </div>

                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                <i class="fas fa-pencil-alt mr-1"></i>
                                Descrição
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="item.descricao"
                                type="text"
                                placeholder="Descrição..."
                                :class="[
                                    'w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm',
                                    errors.despesas[index]?.descricao ? 'border-red-500' : 'border-gray-300'
                                ]"
                            >
                            <p v-if="errors.despesas[index]?.descricao" class="mt-1 text-xs text-red-500">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                {{ errors.despesas[index].descricao }}
                            </p>
                        </div>

                        <div class="flex items-end">
                            <button
                                type="button"
                                @click="limparlinha(index)"

                                class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <i class="fas fa-trash-alt text-lg"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 p-4 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-lg border border-yellow-200">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <span class="text-sm font-medium text-gray-700">
                                    <i class="fas fa-calculator mr-2"></i>
                                    Total de Despesas:
                                </span>
                                <span class="text-xl font-bold text-yellow-700 ml-2">
                                    {{ new Intl.NumberFormat('pt-MZ', { style: 'currency', currency: 'MZN' }).format(totalDespesas) }}
                                </span>
                            </div>
                            <div>
                                <button
                                    @click="limparTodasDespesas"
                                    type="button"
                                    class="text-sm text-red-600 hover:text-red-800 font-medium"
                                >
                                    <i class="fas fa-times"></i> Limpar todas
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- OBSERVAÇÕES -->
                <div class="mt-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-comment-dots mr-2 text-blue-500"></i>
                        Observações
                    </label>
                    <textarea
                        v-model="form.observacoes"
                        rows="3"
                        placeholder="Informações adicionais sobre a compra..."
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none"
                    ></textarea>
                </div>


            </form>
        </div>
    </div>
</template>

<style scoped>
.error-message {
    animation: shake 0.5s;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

input.error, select.error, textarea.error {
    border-color: #ef4444;
}
</style>
