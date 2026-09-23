<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    cliente: {
        type: Object,
        required: true
    }
});

function formatarMoeda(v) {
    return new Intl.NumberFormat('pt-MZ', {
        style: 'currency',
        currency: 'MZN'
    }).format(v || 0);
}
</script>

<template>
   

    <AuthenticatedLayout>
    </AuthenticatedLayout>
    </template>