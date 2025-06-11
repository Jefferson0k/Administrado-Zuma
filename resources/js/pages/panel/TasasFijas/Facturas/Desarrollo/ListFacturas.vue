<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { FilterMatchMode } from '@primevue/core/api';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const toast = useToast();

const invoices = ref([]);
const selectedInvoices = ref([]);
const isLoading = ref(true);
const dataTableRef = ref();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

const fetchInvoices = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/invoices');
        invoices.value = response.data.data;
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudieron cargar las facturas',
            life: 3000
        });
    } finally {
        isLoading.value = false;
    }
};

const handleEdit = (invoice) => {
    toast.add({
        severity: 'info',
        summary: 'Función en desarrollo',
        detail: `Edición de factura ${invoice.invoice_code} no disponible aún.`,
        life: 3000
    });
};

const handleDelete = (invoice) => {
    toast.add({
        severity: 'warn',
        summary: 'Función en desarrollo',
        detail: `Eliminación de factura ${invoice.invoice_code} no disponible aún.`,
        life: 3000
    });
};

onMounted(fetchInvoices);
</script>

<template>
    <DataTable ref="dataTableRef" v-model:selection="selectedInvoices" :value="invoices" dataKey="id" :paginator="true"
        :rows="10" :filters="filters" :loading="isLoading"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25]"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} facturas" class="p-datatable-sm">

        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Facturas</h4>
                <IconField>
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                </IconField>
            </div>
        </template>

        <!-- Columnas de datos -->
        <Column selectionMode="multiple" style="width: 3rem" :exportable="false" />
        <Column field="invoice_code" header="Código" sortable style="min-width: 20rem" />
        <Column field="amount" header="Monto" sortable style="min-width: 5rem"/>
        <Column field="financed_amount" header="Monto Financiado" sortable style="min-width: 11rem"/>
        <Column field="financed_amount_by_garantia" header="Financ. Garantía" sortable style="min-width: 10rem"/>
        <Column field="paid_amount" header="Pagado" sortable />
        <Column field="rate" header="Tasa %" sortable style="min-width: 6rem"/>
        <Column field="due_date" header="Vencimiento" sortable />

        <!-- Estado con etiqueta visual -->
        <Column field="status" header="Estado" sortable>
            <template #body="{ data }">
                <Tag :value="data.status === 'active' ? 'Activo' : 'Inactivo'"
                    :severity="data.status === 'active' ? 'success' : 'danger'" rounded />
            </template>
        </Column>

        <!-- Acciones -->
        <Column header="">
            <template #body="{ data }">
                <div class="flex gap-2">
                    <Button icon="pi pi-pencil" rounded outlined @click="handleEdit(data)" aria-label="Editar" />
                    <Button icon="pi pi-trash" rounded outlined severity="danger" @click="handleDelete(data)"
                        aria-label="Eliminar" />
                </div>
            </template>
        </Column>
    </DataTable>
</template>
