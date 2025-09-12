<template>
    <Head title="Blog" />
    <AppLayout>
        <template v-if="isLoading">
            <Espera />
        </template>

        <template v-else>
            <div class="card">
                <AddCategory @agregado="refrescar" />
                <ListCategory :refresh="refreshKey" />
            </div>
        </template>
    </AppLayout>
</template>

<script setup lang="ts">
import Espera from '@/components/Espera.vue';
import AppLayout from '@/layout/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import AddCategory from './components/AddCategory.vue';
import ListCategory from './components/ListCategory.vue';

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