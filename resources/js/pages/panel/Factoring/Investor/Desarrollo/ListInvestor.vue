<template>
  <DataTable ref="dt" :value="investors" dataKey="id" v-model:selection="selectedInvestors" :paginator="true"
    :rows="rowsPerPage" :totalRecords="totalRecords" :first="(currentPage - 1) * rowsPerPage" :loading="loading"
    :rowsPerPageOptions="[5, 10, 20, 50]"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} inversionistas" @page="onPage" scrollable
    scrollHeight="574px" class="p-datatable-sm">
    <!-- Header -->
    <template #header>
      <div class="flex flex-wrap gap-2 items-center justify-between">
        <h4 class="m-0">
          Inversionistas
          <Tag severity="contrast" :value="totalRecords" />
        </h4>
        <div class="flex flex-wrap gap-2">
          <IconField>
            <InputIcon>
              <i class="pi pi-search" />
            </InputIcon>
            <InputText v-model="globalFilter" @input="onGlobalSearch"
              placeholder="Buscar por nombre, alias, email o estado..." />
          </IconField>
          <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" severity="contrast"
            @click="loadInvestors" />
        </div>
      </div>
    </template>

    <!-- Columns -->
    <Column selectionMode="multiple" style="width: 1rem" />
    <Column field="name" header="Nombre" sortable style="min-width: 15rem" />
    <Column field="document" header="Documento" sortable style="min-width: 10rem" />
    <Column field="alias" header="Alias" sortable style="min-width: 15rem" />
    <Column field="telephone" header="Teléfono" sortable style="min-width: 12rem" />
    <Column field="email" header="Email" sortable style="min-width: 18rem" />

    <!-- Estado con Tag -->
    <Column field="status" header="Estado" sortable style="min-width: 10rem">
      <template #body="{ data }">
        <Tag :value="getStatusLabel(data.status)" :severity="getStatusSeverity(data.status)" />
      </template>
    </Column>

    <Column field="creacion" header="Creación" sortable style="min-width: 15rem" />

    <!-- Botón de ojo para ver detalle -->
    <Column header="" style="min-width: 5rem">
      <template #body="{ data }">
        <Button icon="pi pi-eye" text rounded @click="viewInvestorDetail(data)" aria-label="Ver detalle"
          title="Ver detalle" />
      </template>
    </Column>
  </DataTable>
  <showInvertor v-if="selectedInvestorForDetail" :investor="selectedInvestorForDetail" :visible="showDetailDialog"
    @update:visible="showDetailDialog = $event" @status-updated="handleStatusUpdate" />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import { debounce } from 'lodash';
import showInvertor from './showInvertor.vue';
import { useToast } from 'primevue/usetoast';

const toast = useToast();
const investors = ref<any[]>([]);
const selectedInvestors = ref<any[]>([]);
const loading = ref(false);
const totalRecords = ref(0);
const globalFilter = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);

const showDetailDialog = ref(false);
const selectedInvestorForDetail = ref<any>(null);

const loadInvestors = async (event: any = {}) => {
  loading.value = true;
  const page = event.page != null ? event.page + 1 : currentPage.value;
  const perPage = event.rows != null ? Number(event.rows) : rowsPerPage.value;

  try {
    const response = await axios.get('/investor', { params: { search: globalFilter.value, page, perPage } });
    investors.value = response.data.data;
    totalRecords.value = response.data.total;
    currentPage.value = page;
    rowsPerPage.value = perPage;
  } catch (error) {
    console.error('Error al cargar inversionistas:', error);
  } finally {
    loading.value = false;
  }
};

const onGlobalSearch = debounce(() => {
  currentPage.value = 1;
  loadInvestors();
}, 500);

const onPage = (event: any) => {
  loadInvestors(event);
};

// Función para mostrar las etiquetas correctas
const getStatusLabel = (status: string) => {
  switch (status) {
    case 'validated': return 'Validado';
    case 'No validado': return 'Pendiente';
    case 'rejected': return 'Rechazado';
    default: return status;
  }
};

const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'validated': return 'success';
    case 'Validado': return 'success';
    case 'No validado': return 'warn';
    case 'Rechazado': return 'danger';
    case 'rejected': return 'danger';
    default: return 'warn';
  }
};

const viewInvestorDetail = async (investor: any) => {
  try {
    const response = await axios.get(`/investor/${investor.id}`);
    selectedInvestorForDetail.value = response.data.data;
    showDetailDialog.value = true;
  } catch (error) {
    console.error('Error al obtener detalles del inversionista:', error);
    selectedInvestorForDetail.value = investor;
    showDetailDialog.value = true;
  }
};

// ESTA ES LA FUNCIÓN CORREGIDA
const handleStatusUpdate = (updatedInvestor: any) => {
  console.log('Inversionista actualizado:', updatedInvestor);
  
  // Actualizar el inversionista en la lista local
  const index = investors.value.findIndex(inv => inv.id === updatedInvestor.id);
  if (index !== -1) {
    investors.value[index] = updatedInvestor;
  }
  
  // También actualizar el inversionista seleccionado si es el mismo
  if (selectedInvestorForDetail.value && selectedInvestorForDetail.value.id === updatedInvestor.id) {
    selectedInvestorForDetail.value = updatedInvestor;
  }
  
  // Cerrar el dialog
  showDetailDialog.value = false;
  
  // Opcional: recargar la lista para estar seguro
  // loadInvestors();
};

onMounted(() => {
  loadInvestors();
});
</script>