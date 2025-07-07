<script setup>
import { onMounted, ref } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Tag from 'primevue/tag';

const toast = useToast();
const dt = ref();
const cooperativas = ref([]);
const selectedCooperativas = ref([]);
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

onMounted(async () => {
    try {
        const res = await axios.get('/coperativa');
        cooperativas.value = res.data.data;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar cooperativas', life: 3000 });
    }
});
</script>

<template>
    <DataTable ref="dt" v-model:selection="selectedCooperativas" :value="cooperativas" dataKey="id" :paginator="true"
        :rows="10" :filters="filters"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25]"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} cooperativas" class="p-datatable-sm">
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Cooperativas
                    <Tag severity="contrast" :value="cooperativas.length" />
                </h4>
                <IconField>
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                </IconField>
            </div>
        </template>

        <Column selectionMode="multiple" style="width: 3rem" :exportable="false" />
        <Column field="nombre" header="Nombre" sortable style="min-width: 12rem" />
        <Column field="ruc" header="RUC" sortable style="min-width: 12rem" />
        <Column field="direccion" header="Dirección" sortable style="min-width: 12rem" />
        <Column field="telefono" header="Teléfono" sortable style="min-width: 12rem" />
        <Column field="email" header="Email" sortable style="min-width: 12rem" />
        <Column field="tipo_entidad" header="Tipo" sortable style="min-width: 10rem" />
        <Column field="estado" header="Estado" sortable style="min-width: 10rem" />
    </DataTable>
</template>
