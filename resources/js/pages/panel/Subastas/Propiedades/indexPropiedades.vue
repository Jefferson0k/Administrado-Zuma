<template>
    <Head title="Propiedades" />
    <AppLayout>
        <div>
            <template v-if="isLoading">
                <Espera />
            </template>

            <template v-else>
                <div class="card">
                    <AddDesarrollo @agregado="refrescar" />
                    <ListDesarrollo :refresh="refreshKey" />
                </div>
            </template>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AppLayout from '@/layout/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Espera from '@/components/Espera.vue';
import AddDesarrollo from './Desarrollo/AddDesarrollo.vue';
import ListDesarrollo from './Desarrollo/ListDesarrollo.vue';

const refreshKey = ref(0);
const isLoading = ref(true);

function refrescar() {
    refreshKey.value++;
}

onMounted(() => {
    setTimeout(() => {
        isLoading.value = false;
    }, 1000);
});
</script>
