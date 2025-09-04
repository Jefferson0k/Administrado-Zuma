<template>
    <Toolbar class="mb-6 p-4 border-round shadow-2 flex justify-content-between align-items-center">
        <template #start>
            <div v-if="invoice" class="flex flex-wrap gap-6">
                <div class="p-4 surface-card border-round flex flex-column shadow-1">
                    <b class="text-900 mb-2">Código: </b>
                    <span class="text-700">{{ invoice.codigo || '-' }}</span>
                </div>
                <div class="p-4 surface-card border-round flex flex-column shadow-1">
                    <b class="text-900 mb-2">Monto Factura:</b>
                    <span class="text-700">{{ formatNumber(invoice.amount) }}</span>
                </div>
                <div class="p-4 surface-card border-round flex flex-column shadow-1">
                    <b class="text-900 mb-2">Estado:</b>
                    <Tag :value="getStatusText(invoice.status)" :severity="getStatusSeverity(invoice.status)" />
                </div>
                <div class="p-4 surface-card border-round flex flex-column shadow-1">
                    <b class="text-900 mb-2">Fecha de Pago Estimada:</b>
                    <span class="text-700">{{ formatDate(invoice.estimated_pay_date) }}</span>
                </div>
            </div>
        </template>
        <template #end>
            <Button label="Volver" icon="pi pi-arrow-left" severity="secondary" class="ml-2" @click="volver" />
        </template>
    </Toolbar>

    <!-- Tabla de inversiones -->
    <DataTable 
        ref="dt"
        v-if="investmentData && investmentData.data" 
        :value="investmentData.data" 
        dataKey="id"
        :paginator="true"
        :rows="10"
        :filters="filters"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25]"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} inversiones"
        responsiveLayout="scroll"
        class="p-datatable-sm" 
    >
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0 text-900">Inversionistas</h4>
                <IconField>
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                </IconField>
            </div>
        </template>
        <Column selectionMode="multiple" style="width: 1rem" />
        <Column field="inversionista" header="Inversionista" sortable style="min-width: 12rem"></Column>
        <Column field="amount" header="Monto" sortable style="min-width: 10rem">
            <template #body="slotProps">
                {{ formatNumber(slotProps.data.amount) }}
            </template>
        </Column>
        <Column field="return" header="Retorno" sortable style="min-width: 10rem">
            <template #body="slotProps">
                {{ formatNumber(slotProps.data.return) }}
            </template>
        </Column>
        <Column field="rate" header="Tasa" sortable style="min-width: 8rem">
            <template #body="slotProps">
                {{ slotProps.data.rate }}%
            </template>
        </Column>
        <Column field="currency" header="Moneda" sortable style="min-width: 8rem"></Column>
        <Column field="status" header="Estado" sortable style="min-width: 10rem">
            <template #body="slotProps">
                <Tag :value="getStatusText(slotProps.data.status)" :severity="getStatusSeverity(slotProps.data.status)" />
            </template>
        </Column>
        <Column field="due_date" header="Fecha Vencimiento" sortable style="min-width: 12rem">
            <template #body="slotProps">
                {{ slotProps.data.due_date }}
            </template>
        </Column>
        <Column field="creacion" header="Creación" sortable style="min-width: 12rem">
            <template #body="slotProps">
                {{ slotProps.data.creacion }}
            </template>
        </Column>
    </DataTable>
</template>

<script setup lang="ts">
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';
import axios from 'axios';

const props = defineProps<{
    invoice: any;
    investments: any[];
}>();

const invoice = props.invoice;
const investmentData = ref<any>(null);

// Filtros para la tabla
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

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

// Función para obtener el texto del estado en español
const getStatusText = (status: string) => {
    const statusMap: { [key: string]: string } = {
        'inactive': 'Inactivo',
        'active': 'Activo',
        'paid': 'Pagado',
        'reprogramed': 'Reprogramado'
    };
    return statusMap[status] || status;
};

// Función para obtener la severidad del estado para el componente Tag
const getStatusSeverity = (status: string) => {
    const severityMap: { [key: string]: string } = {
        'inactive': 'secondary',
        'active': 'info',
        'paid': 'success',
        'reprogramed': 'warning'
    };
    return severityMap[status] || 'secondary';
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