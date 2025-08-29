<template>
    <Toolbar class="mb-6 p-3 border-round shadow-2 flex justify-content-between align-items-center">
        <template #start>
            <div v-if="invoice" class="flex flex-wrap gap-4">
                <div class="p-3 surface-card border-round flex flex-column">
                    <strong>Código:</strong>
                    <span>{{ invoice.codigo || '-' }}</span>
                </div>
                <div class="p-3 surface-card border-round flex flex-column">
                    <strong>Monto Factura:</strong>
                    <span>{{ formatNumber(invoice.amount) }}</span>
                </div>
                <div class="p-3 surface-card border-round flex flex-column">
                    <strong>Estado:</strong>
                    <span>{{ invoice.status || '-' }}</span>
                </div>
                <div class="p-3 surface-card border-round flex flex-column">
                    <strong>Fecha de Pago Estimada:</strong>
                    <span>{{ formatDate(invoice.estimated_pay_date) }}</span>
                </div>
            </div>
        </template>
        <template #end>
            <Button label="Volver" icon="pi pi-arrow-left" severity="secondary" class="ml-2" @click="volver" />
        </template>
    </Toolbar>

    <!-- Tabla de inversiones -->
    <DataTable v-if="investmentData && investmentData.data" :value="investmentData.data" class="mt-4" responsiveLayout="scroll">
        <Column field="inversionista" header="Inversionista"></Column>
        <Column field="amount" header="Monto"></Column>
        <Column field="return" header="Retorno"></Column>
        <Column field="rate" header="Tasa"></Column>
        <Column field="currency" header="Moneda"></Column>
        <Column field="status" header="Estado"></Column>
        <Column field="due_date" header="Fecha Vencimiento"></Column>
        <Column field="creacion" header="Creación"></Column>
    </DataTable>
</template>

<script setup lang="ts">
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import axios from 'axios';

const props = defineProps<{
    invoice: any;
    investments: any[];
}>();

const invoice = props.invoice;
const investmentData = ref<any>(null);

const volver = () => {
    router.get('/factoring/facturas');
};

const formatNumber = (value: any) => {
    if (value == null) return '-';
    const number = parseFloat(value);
    if (isNaN(number)) return '-';
    const parts = number.toFixed(2).split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    return parts.join('.');
};

const formatDate = (dateStr: any) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return '-';
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
};

onMounted(async () => {
    if (!invoice || !invoice.id) return;

    try {
        const response = await axios.get(`/investment/${invoice.id}`);
        investmentData.value = response.data;
        console.log(investmentData.value);
    } catch (error) {
        console.error('Error al consultar la inversión:', error);
    }
});
</script>
