<template>
  <Toolbar class="mb-6">
    <template #start>
    </template>

    <template #end>
      <div class="flex gap-2">
        <Button label="Export Excel" icon="pi pi-file-excel" severity="secondary" @click="exportExcel"
          :loading="exportingExcel" />
      </div>
    </template>
  </Toolbar>

  <DataTable ref="dt" v-model:selection="selectedDeposits" :value="deposits" dataKey="id" :paginator="true"
    :rows="rowsPerPage" :totalRecords="totalRecords" :first="(currentPage - 1) * rowsPerPage" :loading="loading"
    :rowsPerPageOptions="[5, 10, 20, 50]"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} depósitos" @page="onPage" scrollable
    scrollHeight="574px" class="p-datatable-sm">
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
            <InputText v-model="globalFilter" @input="onGlobalSearch"
              placeholder="Buscar inversor, banco u operación..." />
          </IconField>
          <Button icon="pi pi-refresh" outlined severity="contrast" rounded aria-label="Refrescar"
            @click="cargarDepositos" />
        </div>
      </div>
    </template>
    <Column selectionMode="multiple" style="width: 1rem" :exportable="false" />
    <Column field="investor" header="Inversor" sortable style="min-width: 25rem" />
    <Column field="nomBanco" header="Banco" sortable style="min-width: 20rem" />
    <Column field="nro_operation" header="Nº Operación" sortable style="min-width: 10rem" />
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
    <Column field="estado" header="1ª Estado" sortable style="min-width: 7rem">
      <template #body="{ data }">
        <Tag :value="traducirEstado(data.estado)" :severity="obtenerColorEstado(data.estado)" />
      </template>
    </Column>

    <!-- Columna T. 1ª Aprobación actualizada -->
    <Column field="fecha_aprobacion_1" header="T. 1ª Aprobación" sortable style="min-width: 12rem">
    </Column>
    <Column field="aprobado_por_1_nombre" header="1ª Usuario" sortable style="min-width: 20rem">
    </Column>

    <Column field="estadoConfig" header="2ª Estado" sortable style="min-width: 7rem">
      <template #body="{ data }">
        <Tag :value="traducirEstado(data.estadoConfig)" :severity="obtenerColorEstado(data.estadoConfig)" />
      </template>
    </Column>
    <Column field="fecha_aprobacion_2" header="T. 2ª Aprobación" sortable style="min-width: 12rem">
    </Column>
    <Column field="aprobado_por_2_nombre" header="2ª Usuario" sortable style="min-width: 20rem">
    </Column>
    <Column field="creacion" header="Fecha Creación" sortable style="min-width: 12rem">
      <template #body="{ data }">
        <div class="flex flex-col">
          <div class="font-medium">{{ formatFecha(data.creacion) }}</div>
        </div>
      </template>
    </Column>
    <Column header="">
      <template #body="{ data }">
        <Button icon="pi pi-eye" severity="contrast" outlined rounded @click="verDeposito(data)"
          v-tooltip="'Ver Detalle'" />
      </template>
    </Column>
  </DataTable>
  <ShowDeposit v-if="selectedDeposit" :deposit="selectedDeposit" @close="cerrarDetalle" @refresh="cargarDepositos" />
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
import ShowDeposit from './showDeposit.vue';
import Toolbar from 'primevue/toolbar';

const deposits = ref<any[]>([]);
const selectedDeposits = ref<any[]>([]);
const loading = ref(false);
const totalRecords = ref(0);
const globalFilter = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);
const selectedDeposit = ref(null);

// Loading states para los botones de exportación
const exportingExcel = ref(false);
const exportingCSV = ref(false);
const exportingSelected = ref(false);

const cargarDepositos = async (event: any = {}) => {
  loading.value = true;
  const page = event.page != null ? event.page + 1 : currentPage.value;
  const perPage = event.rows != null ? Number(event.rows) : rowsPerPage.value;
  try {
    const response = await axios.get('/deposit', {
      params: { search: globalFilter.value, page, perPage },
    });
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
  cargarDepositos();
}, 500);

const onPage = (event: any) => {
  cargarDepositos(event);
};

function verDeposito(depositData: any) {
  selectedDeposit.value = depositData;
}

function cerrarDetalle() {
  selectedDeposit.value = null;
}

// Función para descargar archivos desde URL
const downloadFile = async (url: string, filename: string, params: any = {}, loadingRef: any) => {
  loadingRef.value = true;

  try {
    const response = await axios.get(url, {
      params,
      responseType: 'blob',
      timeout: 60000 // 60 segundos para archivos grandes
    });

    // Verificar que la respuesta tenga contenido
    if (response.data.size === 0) {
      throw new Error('El archivo descargado está vacío');
    }

    // Obtener el tipo MIME correcto
    const contentType = response.headers['content-type'] || 'application/octet-stream';

    // Crear blob
    const blob = new Blob([response.data], { type: contentType });

    // Crear URL temporal
    const downloadUrl = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = filename;

    // Agregar al DOM y hacer click
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();

    // Limpiar después de un breve delay
    setTimeout(() => {
      document.body.removeChild(link);
      window.URL.revokeObjectURL(downloadUrl);
    }, 100);

    return true;

  } catch (error) {
    console.error(`Error al descargar archivo desde ${url}:`, error);

    if (error.response?.status === 404) {
      console.error('Endpoint de descarga no encontrado');
    } else if (error.response?.status === 500) {
      console.error('Error del servidor al generar el archivo');
    } else if (error.code === 'ECONNABORTED') {
      console.error('Timeout: La descarga está tomando demasiado tiempo');
    }

    return false;
  } finally {
    loadingRef.value = false;
  }
};

// Exportar a Excel
const exportExcel = async () => {
  const fecha = new Date().toISOString().split('T')[0];
  const filename = `depositos_excel_${fecha}.xlsx`;

  const params = {
    search: globalFilter.value,
    format: 'excel'
  };

  await downloadFile('/deposit/export/excel', filename, params, exportingExcel);
};

const formatAmount = (amount: number | string) => {
  return new Intl.NumberFormat('es-PE', {
    style: 'currency',
    currency: 'PEN',
  }).format(Number(amount));
};

const formatFecha = (fecha: string) => {
  return fecha;
};

function obtenerColorEstado(estado: string) {
  switch (estado) {
    case 'valid':
      return 'success';
    case 'invalid':
      return 'danger';
    case 'pending':
      return 'warn';
    case 'rejected':
      return 'danger';
    case 'confirmed':
      return 'info';
    default:
      return 'contrast';
  }
}

function traducirEstado(estado: string) {
  switch (estado) {
    case 'valid':
      return 'Válido';
    case 'invalid':
      return 'Inválido';
    case 'pending':
      return 'Pendiente';
    case 'rejected':
      return 'Rechazado';
    case 'confirmed':
      return 'Confirmado';
    default:
      return estado;
  }
}

onMounted(() => {
  cargarDepositos();
});
</script>