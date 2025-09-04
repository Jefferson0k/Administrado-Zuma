<template>

    <Head title="Facturas" />
    <AppLayout>
        <div>
            <template v-if="isLoading">
                <Espera />
            </template>
            <template v-else>
                <div class="card">
                    <addInvoice @agregado="refrescarListado" @export-requested="handleExportRequest" />
                    <listInvoice ref="listInvoiceRef" :refresh="refreshKey" @filters-changed="onFiltersChanged" />
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
import listInvoice from './Desarrollo/listInvoice.vue';
import addInvoice from './Desarrollo/addInvoice.vue';

const isLoading = ref(true);
const refreshKey = ref(0);
const listInvoiceRef = ref(null);
const currentFilters = ref({});

function refrescarListado() {
    refreshKey.value++;
}

function onFiltersChanged(filters: any) {
    currentFilters.value = filters;
}

function handleExportRequest() {
    if (listInvoiceRef.value && listInvoiceRef.value.exportToExcel) {
        listInvoiceRef.value.exportToExcel();
    }
}

onMounted(() => {
    setTimeout(() => {
        isLoading.value = false;
    }, 1000);
});
</script>