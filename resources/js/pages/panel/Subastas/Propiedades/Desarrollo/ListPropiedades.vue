<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Button from 'primevue/button';
import MultiSelect from 'primevue/multiselect';
import Select from 'primevue/select';
import Image from 'primevue/image';
import ConfigPropiedades from './ConfigPropiedades.vue';

// Declarar las props que este componente puede recibir
const props = defineProps({
  refresh: {
    type: Number,
    default: 0
  }
});

const toast = useToast();
const dt = ref();
const products = ref([]);
const selectedProducts = ref([]);
const loading = ref(false);
const totalRecords = ref(0);
const currentPage = ref(1);
const perPage = ref(10);
const search = ref('');
const selectedColumns = ref([]);
const showModal = ref(false);
const selectedId = ref(null);

const selectedEstado = ref(null);
const selectedOpcions = ref([
    { name: 'En subasta', value: 'en_subasta' },
    { name: 'Subasta programada', value: 'programada' },
    { name: 'No subastada', value: 'no_subastada' },
    { name: 'Subastada', value: 'subastada' },
    { name: 'Desierta', value: 'desierta' },
]);

let searchTimeout;

const loadData = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/property', {
            params: {
                page: currentPage.value,
                per_page: perPage.value,
                search: search.value,
                estado: selectedEstado.value?.value || null,
            },
        });
        products.value = response.data.data;
        totalRecords.value = response.data.meta.total;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar las propiedades', life: 3000 });
    } finally {
        loading.value = false;
    }
};

const updatePropertyStatus = async (propertyId, isEnSubasta) => {
    try {
        const newStatus = isEnSubasta ? 'en_subasta' : 'no_subastada';
        
        await axios.put(`/property/${propertyId}/estado`, {
            estado: newStatus
        });

        const propertyIndex = products.value.findIndex(p => p.id === propertyId);
        if (propertyIndex !== -1) {
            products.value[propertyIndex].estado = newStatus;
        }

        toast.add({ 
            severity: 'success', 
            summary: 'Éxito', 
            detail: `Estado actualizado a: ${isEnSubasta ? 'En subasta' : 'No subastada'}`, 
            life: 3000 
        });
    } catch (error) {
        toast.add({ 
            severity: 'error', 
            summary: 'Error', 
            detail: 'No se pudo actualizar el estado de la propiedad', 
            life: 3000 
        });
        
        loadData();
    }
};

onMounted(loadData);

// Observar cambios en la prop refresh para recargar datos
watch(() => props.refresh, () => {
    loadData();
});

watch([search, perPage, selectedEstado], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage.value = 1;
        loadData();
    }, 500);
});

const onPage = (event) => {
    currentPage.value = event.page + 1;
    perPage.value = event.rows;
    loadData();
};

const isColumnSelected = (fieldName) => {
    return selectedColumns.value.some(col => col.field === fieldName);
};

const optionalColumns = ref([
    { field: 'descripcion', header: 'Descripcion' },
    { field: 'foto', header: 'Imagen' },
]);

const abrirConfiguracion = (data) => {
    selectedId.value = data.id;
    showModal.value = true;
};

// Funciones que parecen faltar
const onEditar = (data) => {
    console.log('Editar:', data);
};

const onEliminar = (data) => {
    console.log('Eliminar:', data);
};
</script>

<template>
    <DataTable ref="dt" v-model:selection="selectedProducts" :value="products" dataKey="id" :paginator="true"
        :rows="perPage" :first="(currentPage - 1) * perPage" :totalRecords="totalRecords" :loading="loading" lazy
        @page="onPage"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[10, 15, 25]"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} propiedades" class="p-datatable-sm">
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <div class="flex items-center gap-2">
                    <h4 class="m-0">Propiedades</h4>
                </div>

                <div class="flex flex-wrap gap-2">
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="search" placeholder="Buscar..." />
                    </IconField>

                    <Select v-model="selectedEstado" :options="selectedOpcions" optionLabel="name" placeholder="Estado"
                        class="w-full md:w-auto" showClear />
                    <MultiSelect v-model="selectedColumns" :options="optionalColumns" optionLabel="header"
                        display="chip" placeholder="Seleccionar Columnas" />
                    <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" @click="loadData" />
                </div>
            </div>
        </template>

        <Column selectionMode="multiple" style="width: 3rem" :exportable="false" />
        <Column field="nombre" header="Nombre" sortable style="min-width: 15rem" />
        <Column field="distrito" header="Distrito" sortable style="min-width: 15rem" />
        <Column v-if="isColumnSelected('descripcion')" field="descripcion" header="Descripción" sortable
            style="min-width: 41rem">
        </Column>
        <Column v-if="isColumnSelected('foto')" header="Imagen">
            <template #body="slotProps">
                <Image v-if="slotProps.data.foto" :src="slotProps.data.foto" class="rounded" alt="Foto" preview
                    width="50" style="width: 64px" />
                <span v-else>-</span>
            </template>
        </Column>
        <Column field="validado" header="Validado" style="min-width: 5rem" sortable>
            <template #body="slotProps">
                <span>{{ slotProps.data.validado ? 'Sí' : 'No' }}</span>
            </template>
        </Column>
        <Column field="fecha_inversion" header="Fecha de inversión" style="min-width: 11rem" sortable/>
        <Column field="estado_nombre" header="Estado" style="min-width: 5rem" sortable/>
        <Column :exportable="false" style="min-width: 10rem">
            <template #body="slotProps">
                <Button icon="pi pi-cog" outlined rounded class="mr-2" severity="info" @click="abrirConfiguracion(slotProps.data)" />
                <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="onEditar(slotProps.data)" />
                <Button icon="pi pi-trash" outlined rounded severity="danger" @click="onEliminar(slotProps.data)" />
            </template>
        </Column>
    </DataTable>
    <ConfigPropiedades v-model:visible="showModal" :idPropiedad="selectedId" @configuracion-guardada="loadData" />
</template>