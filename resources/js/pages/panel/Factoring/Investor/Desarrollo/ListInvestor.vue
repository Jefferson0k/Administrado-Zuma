<template>
    <DataTable
      ref="dt"
      :value="investors"
      dataKey="id"
      v-model:selection="selectedInvestors"
      :paginator="true"
      :rows="rowsPerPage"
      :totalRecords="totalRecords"
      :first="(currentPage - 1) * rowsPerPage"
      :loading="loading"
      :rowsPerPageOptions="[5, 10, 20, 50]"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} inversionistas"
      @page="onPage"
      scrollable
      scrollHeight="574px"
      class="p-datatable-sm"
    >
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
              <InputText
                v-model="globalFilter"
                @input="onGlobalSearch"
                placeholder="Buscar por nombre, alias, email o estado..."
              />
            </IconField>
            <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" @click="loadInvestors" />
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
          <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
        </template>
      </Column>

      <Column field="creacion" header="Creación" sortable style="min-width: 15rem" />

      <!-- Menú de 3 puntos -->
      <Column header="" style="min-width: 5rem">
        <template #body="{ data }">
          <Button icon="pi pi-ellipsis-v" text rounded @click="toggleMenu($event, data)" />
          <Menu :model="getMenuItems(data)" :popup="true" ref="menu" />
        </template>
      </Column>
    </DataTable>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Menu from 'primevue/menu';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import { debounce } from 'lodash';

const investors = ref<any[]>([]);
const selectedInvestors = ref<any[]>([]);
const loading = ref(false);
const totalRecords = ref(0);
const globalFilter = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);

const menu = ref();
const selectedMenuInvestor = ref<any>(null);

// Cargar inversionistas
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

// Búsqueda global con debounce
const onGlobalSearch = debounce(() => {
  currentPage.value = 1;
  loadInvestors();
}, 500);

// Paginación
const onPage = (event: any) => {
  loadInvestors(event);
};

// Severidad del estado
const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'Validado': return 'success';
    case 'No validado': return 'info';
    case 'Rechazado': return 'danger';
    default: return 'warn';
  }
};

// Menú de acciones
const toggleMenu = (event: any, investor: any) => {
  selectedMenuInvestor.value = investor;
  menu.value.toggle(event);
};

const getMenuItems = (investor: any) => [
  { label: 'Validar', icon: 'pi pi-check', command: () => updateStatus(investor.id, 'Validado') },
  { label: 'Rechazar', icon: 'pi pi-times', command: () => updateStatus(investor.id, 'Rechazado') },
  { label: 'Volver a intentar', icon: 'pi pi-refresh', command: () => updateStatus(investor.id, 'No validado') }
];

const updateStatus = async (id: string, status: string) => {
  try {
    await axios.put(`/investor/${id}/status`, { status });
    loadInvestors();
  } catch (e) {
    console.error('Error actualizando estado:', e);
  }
};

onMounted(() => {
  loadInvestors();
});
</script>
