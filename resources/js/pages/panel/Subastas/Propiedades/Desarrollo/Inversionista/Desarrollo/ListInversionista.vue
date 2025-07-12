<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import A4 from './A4.vue';
import Congiguracion from './Congiguracion.vue';

const toast = useToast();
const dt = ref();
const products = ref([]);
const selectedProducts = ref([]);
const showPrintDialog = ref(false);
const prestamosId = ref(null);
const showModal = ref(false);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

const getData = async () => {
    try {
        const response = await axios.get('/property-loan-details');
        products.value = response.data.data;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar los datos' });
    }
};

const handleClosePrestamo = () => {
    showPrintDialog.value = false;
    prestamosId.value = null;
};

const verDetalle = (prestamo) => {
    prestamosId.value = prestamo.id;
    showPrintDialog.value = true;
};

const abrirConfiguracion = (data) => {
    prestamosId.value = data.property_id;
    showModal.value = true;
};

const editarPrestamo = (data) => {
    toast.add({ severity: 'info', summary: 'Editar', detail: `Editar préstamo ${data.id}` });
    // Lógica para editar
};

const eliminarPrestamo = (data) => {
    toast.add({ severity: 'warn', summary: 'Eliminar', detail: `Eliminar préstamo ${data.id}` });
    // Lógica para eliminar
};

const getEstadoSeverity = (estado) => {
    switch (estado) {
        case 'activa':
            return 'success';
        case 'pendiente':
            return 'warn';
        case 'subastada':
            return 'info';
        case 'desactivada':
            return 'danger';
        default:
            return 'secondary';
    }
};

const getRiesgoSeverity = (riesgo) => {
    switch (riesgo) {
        case 'A+':
        case 'A':
            return 'success';
        case 'B':
            return 'info';
        case 'C':
            return 'warn';
        case 'D':
            return 'danger';
        default:
            return 'secondary';
    }
};

onMounted(getData);
</script>

<template>
    <DataTable ref="dt" v-model:selection="selectedProducts" :value="products" dataKey="id" :paginator="true" :rows="10"
        :filters="filters"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25]"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} registros" class="p-datatable-sm">

        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Informes</h4>
                <IconField>
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                </IconField>
            </div>
        </template>

        <Column selectionMode="multiple" style="width: 3rem" :exportable="false" />
        <Column field="Dni" header="DNI" />
        <Column field="cliente" header="Cliente" />
        <Column field="propiedad" header="Propiedad" />
        <Column field="requerido" header="Valor Requerido" />
        <Column field="cronograma" header="Tipo Cronograma" />
        <Column field="dias" header="Plazo" />
        <Column field="riesgo" header="Riesgo">
            <template #body="{ data }">
                <Tag :value="data.riesgo" :severity="getRiesgoSeverity(data.riesgo)" />
            </template>
        </Column>
        <Column field="estado_nombre" header="Estado">
            <template #body="{ data }">
                <Tag :value="data.estado_nombre" :severity="getEstadoSeverity(data.estado)" />
            </template>
        </Column>

        <Column header="">
            <template #body="{ data }">
                <div class="flex gap-2 justify-center">
                    <Button icon="pi pi-eye" rounded severity="info" variant="outlined" @click="verDetalle(data)" />

                    <template v-if="data.estado === 'activa'">
                        <Button icon="pi pi-cog" outlined rounded severity="contrast" @click="abrirConfiguracion(data)" />
                        <Button icon="pi pi-pencil" rounded severity="warning" variant="outlined" @click="editarPrestamo(data)" />
                        <Button icon="pi pi-trash" rounded severity="danger" variant="outlined" @click="eliminarPrestamo(data)" />
                    </template>
                    <template v-else>
                        <Button icon="pi pi-cog" outlined rounded severity="contrast" disabled />
                        <Button icon="pi pi-pencil" rounded severity="warning" variant="outlined" disabled />
                        <Button icon="pi pi-trash" rounded severity="danger" variant="outlined" disabled />
                    </template>
                </div>
            </template>
        </Column>

    </DataTable>

    <A4 v-if="showPrintDialog" :prestamosId="prestamosId" v-model:visible="showPrintDialog"
        @close="handleClosePrestamo" />
    <Congiguracion v-model:visible="showModal" :idPropiedad="prestamosId" @configuracion-guardada="getData" />
</template>
