<template>
    <DataTable
      ref="dt"
      :value="investments"
      dataKey="id"
      v-model:selection="selectedInvestments"
      :paginator="true"
      :rows="rowsPerPage"
      :totalRecords="totalRecords"
      :first="(currentPage - 1) * rowsPerPage"
      :loading="loading"
      :rowsPerPageOptions="[5, 10, 20, 50]"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} inversiones"
      @page="onPage"
      scrollable
      scrollHeight="574px"
      class="p-datatable-sm"
    >
      <!-- Header -->
      <template #header>
        <div class="flex flex-wrap gap-2 items-center justify-between">
          <h4 class="m-0">
            Inversiones
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
                placeholder="Buscar por código, inversionista, moneda o estado..."
              />
            </IconField>
            <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" @click="loadInvestments" />
          </div>
        </div>
      </template>

      <!-- Columns -->
      <Column selectionMode="multiple" style="width: 1rem" />

      <Column field="codigo" header="Código" sortable style="min-width: 12rem" />
      <Column field="inversionista" header="Inversionista" sortable style="min-width: 18rem" />
      <Column field="amount" header="Monto" sortable style="min-width: 10rem">
        <template #body="{ data }">
          {{ data.currency }} {{ data.amount }}
        </template>
      </Column>
      <Column field="return" header="Retorno" sortable style="min-width: 10rem">
        <template #body="{ data }">
          {{ data.currency }} {{ data.return }}
        </template>
      </Column>
      <Column field="rate" header="Tasa" sortable style="min-width: 8rem">
        <template #body="{ data }">
          {{ data.rate }}%
        </template>
      </Column>
      <Column field="currency" header="Moneda" sortable style="min-width: 8rem" />
      <Column field="due_date" header="Fecha Vencimiento" sortable style="min-width: 12rem" />

      <!-- Estado con Tag -->
      <Column field="status" header="Estado" sortable style="min-width: 10rem">
        <template #body="{ data }">
          <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
        </template>
      </Column>

      <Column field="creacion" header="Creación" sortable style="min-width: 15rem" />
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

const investments = ref<any[]>([]);
const selectedInvestments = ref<any[]>([]);
const loading = ref(false);
const totalRecords = ref(0);

const globalFilter = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);

// Cargar inversiones (soporta paginación de servidor si quieres)
const loadInvestments = async (event: any = {}) => {
  loading.value = true;
  const page = event.page != null ? event.page + 1 : currentPage.value;
  const perPage = event.rows != null ? Number(event.rows) : rowsPerPage.value;

  try {
    const response = await axios.get('/investment/all', {
      params: { search: globalFilter.value, page, perPage }
    });

    investments.value = response.data.data;
    totalRecords.value = response.data.total;
    currentPage.value = page;
    rowsPerPage.value = perPage;
  } catch (error) {
    console.error('Error al cargar inversiones:', error);
  } finally {
    loading.value = false;
  }
};

// Paginación del DataTable
const onPage = (event: any) => {
  loadInvestments(event);
};

// Búsqueda global con debounce
const onGlobalSearch = debounce(() => {
  currentPage.value = 1;
  loadInvestments();
}, 500);

// Severidad del Tag
const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'active': return 'success';
    case 'pending': return 'warn';
    case 'completed': return 'info';
    case 'cancelled': return 'danger';
    default: return 'secondary';
  }
};

onMounted(() => {
  loadInvestments();
});
</script>
