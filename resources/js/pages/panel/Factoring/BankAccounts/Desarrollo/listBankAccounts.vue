<template>
  <DataTable ref="dt" v-model:selection="selectedAccounts" :value="accounts" dataKey="id" :paginator="true"
    :rows="rowsPerPage" :totalRecords="totalRecords" :first="(currentPage - 1) * rowsPerPage" :loading="loading"
    :rowsPerPageOptions="[5, 10, 20, 50]"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} cuentas" @page="onPage" scrollable
    scrollHeight="574px" class="p-datatable-sm">

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
            <InputText v-model="globalFilter" @input="onGlobalSearch" placeholder="Buscar..." />
          </IconField>
          <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" severity="contrast"
            @click="loadAccounts" />
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
    <!-- <Column field="alias" header="Alias" sortable style="min-width: 12rem" /> -->
    <Column field="inversionista" header="Inversionista" sortable style="min-width: 18rem" />
    <Column field="estado" header="Estado" sortable style="min-width: 10rem">
      <template #body="{ data }">
        <Tag :value="data.estado" :severity="getStatusSeverity(data.estado)" />
      </template>
    </Column>
    <Column field="creacion" header="Creación" sortable style="min-width: 15rem" />
    <Column field="update" header="Actualización" sortable style="min-width: 15rem" />
    <Column header="" style="min-width: 5rem">
      <template #body="{ data }">
        <Button v-if="data.estado === 'Preaprobado'" icon="pi pi-ellipsis-v" text rounded
          @click="toggleMenu($event, data)" />
        <Menu :model="getMenuItems()" :popup="true" ref="menu" />
      </template>
    </Column>
  </DataTable>

  <!-- Confirmation Dialog -->
  <ConfirmDialog />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Menu from 'primevue/menu';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import ConfirmDialog from 'primevue/confirmdialog';
import { debounce } from 'lodash';
import { router } from '@inertiajs/vue3';

const toast = useToast();
const confirm = useConfirm();

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
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Error al cargar las cuentas bancarias',
      life: 5000
    });
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

const getMenuItems = () => [
  {
    label: 'Ver detalle',
    icon: 'pi pi-eye',
    command: () => viewDetail()
  },
  {
    label: 'Aceptar',
    icon: 'pi pi-check',
    command: () => confirmAction('validate', 'aceptar')
  },
  {
    label: 'Rechazar',
    icon: 'pi pi-times',
    command: () => confirmAction('reject', 'rechazar')
  },
];

const viewDetail = () => {
  if (!selectedAccount.value) {
    toast.add({
      severity: 'warn',
      summary: 'Advertencia',
      detail: 'No se ha seleccionado ninguna cuenta',
      life: 3000
    });
    return;
  }

  // Redirigir a la vista de detalle usando Inertia
  router.visit(`/ban/${selectedAccount.value.id}/filtrar`);
};

const confirmAction = (action: string, actionText: string) => {
  if (!selectedAccount.value) {
    toast.add({
      severity: 'warn',
      summary: 'Advertencia',
      detail: 'No se ha seleccionado ninguna cuenta',
      life: 3000
    });
    return;
  }

  confirm.require({
    message: `¿Está seguro que desea ${actionText} la cuenta bancaria de ${selectedAccount.value.inversionista}?`,
    header: `Confirmar ${actionText.charAt(0).toUpperCase() + actionText.slice(1)}`,
    icon: action === 'validate' ? 'pi pi-check-circle' : 'pi pi-exclamation-triangle',
    rejectClass: 'p-button-secondary p-button-outlined',
    rejectLabel: 'Cancelar',
    acceptLabel: actionText.charAt(0).toUpperCase() + actionText.slice(1),
    accept: () => {
      updateStatus(selectedAccount.value.id, action);
    },
    reject: () => {
      toast.add({
        severity: 'info',
        summary: 'Cancelado',
        detail: 'Operación cancelada',
        life: 3000
      });
    }
  });
};

const updateStatus = async (id: string, action: string) => {
  if (!id) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'ID de cuenta no válido',
      life: 3000
    });
    return;
  }

  loading.value = true;

  try {
    let response;
    let successMessage = '';

    if (action === 'validate') {
      response = await axios.post(`/ban/${id}/validate`);
      successMessage = 'Cuenta bancaria validada exitosamente';
    } else if (action === 'reject') {
      response = await axios.post(`/ban/${id}/reject`);
      successMessage = 'Cuenta bancaria rechazada exitosamente';
    } else {
      throw new Error('Acción no válida');
    }

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: successMessage,
      life: 4000
    });

    await loadAccounts({}, true);

  } catch (error: any) {
    console.error('Error actualizando estado:', error);

    const errorMessage = error.response?.data?.message ||
      error.message ||
      'Error al actualizar el estado de la cuenta';

    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: errorMessage,
      life: 5000
    });
  } finally {
    loading.value = false;
    selectedAccount.value = null;
  }
};

onMounted(() => {
  loadAccounts();
});
</script>