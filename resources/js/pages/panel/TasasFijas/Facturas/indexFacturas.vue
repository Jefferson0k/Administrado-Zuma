<template>
    <Head title="Facturas" />
    <AppLayout>
        <div>
            <template v-if="isLoading">
                <Espera />
            </template>

            <template v-else>
                <div class="card">
                    <AddFacturas @agregado="refrescar" />
                    <ListFacturas :refresh="refreshKey" />
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
import ListFacturas from './Desarrollo/ListFacturas.vue';
import AddFacturas from './Desarrollo/AddFacturas.vue';

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
