<template>
    <Head title="Empresa" />
    <AppLayout>
        <div>
            <template v-if="isLoading">
                <Espera />
            </template>
            <template v-else>
                <div class="card">
                    <addCompany @agregado="refrescarListado" />
                    <listCompany :refresh="refreshKey" />
                </div>
            </template>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AppLayout from '@/layout/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Espera from '@/components/Espera.vue';
import listCompany from './Desarrollo/listCompany.vue';
import addCompany from './Desarrollo/addCompany.vue';

const isLoading = ref(true);
const refreshKey = ref(0);

function refrescarListado() {
    refreshKey.value++;
}

onMounted(() => {
    // Simular carga inicial
    setTimeout(() => {
        isLoading.value = false;
    }, 1000);
});
</script>