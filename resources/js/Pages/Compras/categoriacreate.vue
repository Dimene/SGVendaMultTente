<template>


  <!-- Título -->
    <h1 class="text-center text-xl font-bold mb-4">
        Registar Categoria para {{props. habaativada }}
    </h1>

    <!-- Descrição da categoria -->
    <div class="flex flex-col w-full mb-4">
        <label class="mb-1">Descrição</label>
        <input
        v-model="form.categoria"
            type="text"
            class="w-full border rounded-lg px-3 py-2"
            placeholder="Digite a descrição da categoria"
        />
    </div>

    <!-- Botão adicionar atributo -->
    <div class="w-full mb-4">
        <button @click="numeroatributos++"
            type="button"
            class="float-right px-4 py-2 bg-blue-500 text-white rounded-lg"
        >
            Adicionar atributos à categoria  {{   atributoscategoria.length }}
        </button>
    </div>

    <!-- Exemplo de atributo -->
    <div class="flex w-full gap-3 items-center mt-4" v-for="(value , index) in atributoscategoria">
    <span>{{ index+1 }}</span>
        <input
            type="text"   v-model="value.nome"
            placeholder="Nome do atributo"
            class="flex-1 border rounded-lg px-3 py-2"
        />

        <button
            type="button"
            class="bg-red-500 text-white px-4 py-2 rounded-lg"
        >
            <i class="fa fa-trash"  @click="removeratributo(index)"></i>
        </button>
    </div>

 <!-- Botão adicionar atributo -->
    <div class="w-full mb-5 p-6">
          <button   @click="guardar()">Save</button>
    </div>

</template>

<script setup >
import { Head,useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
const props=defineProps({
 habaativada: {
        type: String,
        default: ''
    },
})
const emit = defineEmits(["voltar",'categorias'])
 const registocategoria=false;
const  numeroatributos=ref(0);


const atributoscategoria=ref([]);

watch(numeroatributos,(novo)=>{
   atributoscategoria.value.push({
    id:novo,
    nome:""

})

})


const form =useForm({
 grupo:props.habaativada,
 categoria:''

})





function removeratributo(index){




    atributoscategoria.value.splice(index, 1)

    // numeroatributos.value=atributoscategoria.length;
}
function guardar(){

 const dadosParaEnvio = {
            ...form,
            atributos: atributoscategoria.value.filter(item =>
                item.id && item.id !== '' && item.nome !== ''
            )}
    axios
        .post("/categoria/store",dadosParaEnvio)
        .then((response) => {

            // console.log(response.data);
            if(response.data.sucesss==1){

            emit('categorias',response.data.categorias);

            }


        });



}





</script>
