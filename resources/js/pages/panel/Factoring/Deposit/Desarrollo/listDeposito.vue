<template>
    <DataTable
      ref="dt"
      v-model:selection="selectedDeposits"
      :value="deposits"
      dataKey="id"
      :paginator="true"
      :rows="rowsPerPage"
      :totalRecords="totalRecords"
      :first="(currentPage - 1) * rowsPerPage"
      :loading="loading"
      :rowsPerPageOptions="[5, 10, 20, 50]"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} depósitos"
      @page="onPage"
      scrollable
      scrollHeight="574px"
      class="p-datatable-sm"
    >
      <!-- Header -->
      <template #header>
        <div class="flex flex-wrap gap-2 items-center justify-between">
          <h4 class="m-0">
            Depósitos
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
                placeholder="Buscar inversor, banco o operación..."
              />
            </IconField>
            <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" @click="loadDeposits" />
          </div>
        </div>
      </template>

      <!-- Columns -->
      <Column selectionMode="multiple" style="width: 1rem" :exportable="false" />
      <Column field="investor" header="Inversor" sortable style="min-width: 15rem" />
      <Column field="nomBanco" header="Banco" sortable style="min-width: 12rem" />
      <Column field="nro_operation" header="Nº Operación" sortable style="min-width: 8rem" />
      <Column field="currency" header="Moneda" sortable style="min-width: 6rem">
        <template #body="{ data }">
          <Tag :value="data.currency" :severity="data.currency === 'PEN' ? 'success' : 'info'" />
        </template>
      </Column>
      <Column field="amount" header="Monto" sortable style="min-width: 8rem">
        <template #body="{ data }">
          <span class="font-semibold">{{ formatAmount(data.amount) }}</span>
        </template>
      </Column>
      <Column field="creacion" header="Fecha Creación" sortable style="min-width: 12rem" />
      <Column header="Imagen" style="min-width: 8rem">
        <template #body="{ data }">
          <Image
            v-if="data.foto"
            :src="data.foto"
            class="rounded"
            alt="Foto del cliente"
            preview
            width="50"
            style="width: 64px"
          />
          <span v-else>-</span>
        </template>
      </Column>
      <Column header="Acciones" style="min-width: 10rem">
        <template #body="{ data }">
          <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editDeposit(data)" v-tooltip="'Editar'" />
          <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDelete(data)" v-tooltip="'Eliminar'" />
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
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Image from 'primevue/image';
import { debounce } from 'lodash';

const deposits = ref<any[]>([]);
const selectedDeposits = ref<any[]>([]);
const loading = ref(false);
const totalRecords = ref(0);
const globalFilter = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);

const deleteDialog = ref(false);
const updateDialog = ref(false);
const deposit = ref({});
const selectedDepositId = ref(null);

const loadDeposits = async (event: any = {}) => {
  loading.value = true;
  const page = event.page != null ? event.page + 1 : currentPage.value;
  const perPage = event.rows != null ? Number(event.rows) : rowsPerPage.value;

  try {
    const response = await axios.get('/deposit', { params: { search: globalFilter.value, page, perPage } });
    deposits.value = response.data.data;
    totalRecords.value = response.data.total;
    currentPage.value = page;
    rowsPerPage.value = perPage;
  } catch (error) {
    console.error('Error al cargar depósitos:', error);
  } finally {
    loading.value = false;
  }
};

const onGlobalSearch = debounce(() => {
  currentPage.value = 1;
  loadDeposits();
}, 500);

const onPage = (event: any) => {
  loadDeposits(event);
};

function editDeposit(depositData: any) {
  selectedDepositId.value = depositData.id;
  updateDialog.value = true;
}

function confirmDelete(depositData: any) {
  deposit.value = depositData;
  deleteDialog.value = true;
}

const formatAmount = (amount: number | string) => {
  return new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(Number(amount));
};

onMounted(() => {
  loadDeposits();
});
</script>
