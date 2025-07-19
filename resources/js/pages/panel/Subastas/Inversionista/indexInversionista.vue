<template>
    <Head title="Pagos" />
    <AppLayout>
        <div>
            <template v-if="isLoading">
                <Espera />
            </template>

            <template v-else>
                <div class="card">
                    <AddInversionista/>
                    <ListInversionita/>
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
import AddInversionista from './Desarrollo/AddInversionista.vue';
import ListInversionita from './Desarrollo/ListInversionita.vue';

const isLoading = ref(true);

onMounted(() => {
    setTimeout(() => {
        isLoading.value = false;
    }, 1000);
});
</script>
