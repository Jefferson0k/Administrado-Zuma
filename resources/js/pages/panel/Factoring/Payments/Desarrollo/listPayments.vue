<template>
    <DataTable ref="dt" :value="paginatedInvoices" v-model:selection="selectedInvoices" dataKey="id" :paginator="true" :rows="rowsPerPage"
        :totalRecords="filteredInvoices.length" :first="(currentPage - 1) * rowsPerPage" :loading="loading"
        @page="onPage" :rowsPerPageOptions="[5, 10, 20]" scrollable scrollHeight="574px"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} facturas" class="p-datatable-sm">
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">
                    Facturas
                    <Tag severity="contrast" :value="contadorInvoices" />
                </h4>
                <div class="flex flex-wrap gap-2">
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="globalFilterValue" @input="onGlobalSearch" placeholder="Buscar..." />
                    </IconField>
                    <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" @click="loadInvoices" />
                </div>
            </div>
        </template>
        <Column selectionMode="multiple" style="width: 1rem" :exportable="false" />
        <Column field="codigo" header="Código" sortable style="min-width: 12rem" />
        <Column field="razonSocial" header="Razón Social" sortable style="min-width: 20rem" />
        <Column field="montoFactura" header="Monto Factura" sortable style="min-width: 12rem">
            <template #body="slotProps">
                {{ slotProps.data.moneda }} {{ slotProps.data.montoFactura }}
            </template>
        </Column>
        <Column field="montoAsumidoZuma" header="Monto Asumido Zuma" sortable style="min-width: 14rem">
            <template #body="slotProps">
                {{ slotProps.data.moneda }} {{ slotProps.data.montoAsumidoZuma }}
            </template>
        </Column>
        <Column field="montoDisponible" header="Monto Disponible" sortable style="min-width: 12rem">
            <template #body="slotProps">
                {{ slotProps.data.moneda }} {{ slotProps.data.montoDisponible }}
            </template>
        </Column>
        <Column field="tasa" header="Tasa" sortable style="min-width: 8rem">
            <template #body="slotProps">
                {{ slotProps.data.tasa }}%
            </template>
        </Column>
        <Column field="moneda" header="Moneda" sortable style="min-width: 8rem" />
        <Column field="fechaPago" header="Fecha Pago" sortable style="min-width: 12rem" />
        
        <!-- Estado con Tag -->
        <Column field="estado" header="Estado" sortable style="min-width: 12rem">
            <template #body="slotProps">
                <Tag :value="getStatusLabel(slotProps.data.estado)" :severity="getStatusSeverity(slotProps.data.estado)" />
            </template>
        </Column>

        <Column field="fechaCreacion" header="Fecha Creación" sortable style="min-width: 15rem" />
    </DataTable>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import { debounce } from 'lodash';

const invoices = ref<any[]>([]);
const loading = ref(false);
const contadorInvoices = ref(0);
const selectedInvoices = ref<any[]>([]);

const globalFilterValue = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);

const loadInvoices = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/invoices/filtrado');
        invoices.value = response.data.data;
        contadorInvoices.value = response.data.total;
        currentPage.value = 1;
    } catch (error) {
        console.error('Error al cargar facturas:', error);
    } finally {
        loading.value = false;
    }
};

const filteredInvoices = computed(() => {
    const search = globalFilterValue.value.toLowerCase();
    return invoices.value.filter((inv: any) =>
        inv.codigo.toLowerCase().includes(search) ||
        inv.razonSocial.toLowerCase().includes(search) ||
        inv.moneda.toLowerCase().includes(search) ||
        inv.estado.toLowerCase().includes(search)
    );
});

const paginatedInvoices = computed(() => {
    const start = (currentPage.value - 1) * rowsPerPage.value;
    return filteredInvoices.value.slice(start, start + rowsPerPage.value);
});

const onGlobalSearch = debounce(() => {
    currentPage.value = 1;
}, 500);

const onPage = (event: any) => {
    currentPage.value = event.page + 1;
    rowsPerPage.value = event.rows;
};

const getStatusLabel = (status: string) => {
    switch (status) {
        case 'inactive':
            return 'Inactivo';
        case 'active':
            return 'Activo';
        case 'expired':
            return 'Expirado';
        case 'judicialized':
            return 'Judicializado';
        case 'reprogramed':
            return 'Reprogramado';
        case 'paid':
            return 'Pagado';
        case 'canceled':
            return 'Cancelado';
        case 'daStandby':
            return 'En Espera';
        default:
            return status;
    }
};

const getStatusSeverity = (status: string) => {
    switch (status) {
        case 'active':
            return 'success';
        case 'paid':
            return 'success';
        case 'inactive':
            return 'secondary';
        case 'daStandby':
            return 'warn';
        case 'reprogramed':
            return 'warn';
        case 'expired':
            return 'danger';
        case 'judicialized':
            return 'danger';
        case 'canceled':
            return 'danger';
        default:
            return 'info';
    }
};

onMounted(() => {
    loadInvoices();
});
</script>