<script setup>
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const toast = useToast();
const dt = ref();
const products = ref();
const selectedProducts = ref();
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

</script>

<template>
    <DataTable ref="dt" v-model:selection="selectedProducts" :value="products" dataKey="id" :paginator="true" :rows="10"
        :filters="filters"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25]"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} facturas"
        class="p-datatable-sm">
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Facturas</h4>
                <IconField>
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText v-model="filters['global'].value" placeholder="Search..." />
                </IconField>
            </div>
        </template>

        <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
        <Column field="code" header="Codigo" sortable style="min-width: 12rem"></Column>
        <Column field="name" header="Razon Social" sortable style="min-width: 16rem"></Column>
        <Column field="category" header="Moneda" sortable style="min-width: 10rem"></Column>
        <Column field="category" header="Monto" sortable style="min-width: 10rem"></Column>
        <Column field="category" header="Tasa %" sortable style="min-width: 15rem"></Column>
        <Column field="category" header="Monto financiado" sortable style="min-width: 15rem"></Column>
        <Column field="category" header="vencimiento" sortable style="min-width: 15rem"></Column>
        <Column field="category" header="Actualizacion" sortable style="min-width: 10rem"></Column>
        <Column field="category" header="Inversionistas" sortable style="min-width: 10rem"></Column>
        <Column field="category" header="Estado " sortable style="min-width: 10rem"></Column>

    </DataTable>
</template>
