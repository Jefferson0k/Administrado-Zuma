<template>
  <div class="">
    <DataTable
      ref="dt"
      v-model:selection="selectedAccounts"
      :value="accounts"
      dataKey="id"
      :paginator="true"
      :rows="rowsPerPage"
      :totalRecords="totalRecords"
      :first="(currentPage - 1) * rowsPerPage"
      :loading="loading"
      :rowsPerPageOptions="[5, 10, 20, 50]"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} cuentas"
      @page="onPage"
      scrollable
      scrollHeight="574px"
      class="p-datatable-sm"
    >
      <!-- Header -->
      <template #header>
        <div class="flex flex-wrap gap-2 items-center justify-between">
          <h4 class="m-0">
            Cuentas Bancarias
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
                placeholder="Buscar..."
              />
            </IconField>
            <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" @click="loadAccounts" />
          </div>
        </div>
      </template>

      <!-- Columns -->
      <Column selectionMode="multiple" style="width: 1rem" :exportable="false" />
      <Column field="banco" header="Banco" sortable style="min-width: 15rem" />
      <Column field="type" header="Tipo" sortable style="min-width: 10rem" />
      <Column field="currency" header="Moneda" sortable style="min-width: 8rem" />
      <Column field="cc" header="Cuenta" sortable style="min-width: 12rem" />
      <Column field="cci" header="CCI" sortable style="min-width: 15rem" />
      <Column field="alias" header="Alias" sortable style="min-width: 12rem" />
      <Column field="inversionista" header="Inversionista" sortable style="min-width: 18rem" />
      <Column field="estado" header="Estado" sortable style="min-width: 10rem">
        <template #body="{ data }">
          <Tag :value="data.estado" :severity="getStatusSeverity(data.estado)" />
        </template>
      </Column>
      <Column field="creacion" header="Creación" sortable style="min-width: 15rem" />
      <Column field="update" header="Actualización" sortable style="min-width: 15rem" />
      <Column header="">
        <template #body="{ data }">
          <Button icon="pi pi-ellipsis-v" text rounded @click="toggleMenu($event, data)" />
          <Menu :model="getMenuItems(data)" :popup="true" ref="menu" />
        </template>
      </Column>
    </DataTable>
  </div>
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

const accounts = ref<any[]>([]);
const selectedAccounts = ref<any[]>([]);
const loading = ref(false);
const totalRecords = ref(0);
const globalFilter = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);
const menu = ref();
const selectedAccount = ref<any>(null);

const loadAccounts = async (event: any = {}) => {
  loading.value = true;

  const page = event.page != null ? event.page + 1 : currentPage.value;
  const perPage = event.rows != null ? Number(event.rows) : rowsPerPage.value;

  try {
    const response = await axios.get('/ban', {
      params: {
        search: globalFilter.value,
        page,
        perPage
      }
    });
    accounts.value = response.data.data;
    totalRecords.value = response.data.total;
    currentPage.value = page;
    rowsPerPage.value = perPage;
  } catch (error) {
    console.error('Error al cargar cuentas bancarias:', error);
  } finally {
    loading.value = false;
  }
};

const onGlobalSearch = debounce(() => {
  currentPage.value = 1;
  loadAccounts();
}, 500);

const onPage = (event: any) => {
  loadAccounts(event);
};

const getStatusSeverity = (estado: string) => {
  switch (estado) {
    case 'Válido': return 'success';
    case 'Inválido': return 'danger';
    case 'Preaprobado': return 'warn';
    default: return 'secondary';
  }
};

const toggleMenu = (event: any, account: any) => {
  selectedAccount.value = account;
  menu.value.toggle(event);
};

const getMenuItems = (account: any) => [
  { label: 'Aceptar', icon: 'pi pi-check', command: () => updateStatus(account.id, 'valid') },
  { label: 'Rechazar', icon: 'pi pi-times', command: () => updateStatus(account.id, 'invalid') },
  { label: 'Volver a intentar', icon: 'pi pi-refresh', command: () => updateStatus(account.id, 'pre_approved') }
];

const updateStatus = async (id: string, status: string) => {
  try {
    await axios.put(`/ban/${id}/status`, { status });
    loadAccounts();
  } catch (e) {
    console.error('Error actualizando estado:', e);
  }
};

onMounted(() => {
  loadAccounts();
});
</script>
