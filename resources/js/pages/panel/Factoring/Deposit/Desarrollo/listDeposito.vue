<template>
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
    <Column field="estado" header="Estado" sortable style="min-width: 5rem">
      <template #body="{ data }">
        <Tag :value="traducirEstado(data.estado)" :severity="obtenerColorEstado(data.estado)" />
      </template>
    </Column>
    <Column field="estadoConfig" header="E. Confirmación" sortable style="min-width: 10rem">
      <template #body="{ data }">
        <Tag :value="traducirEstado(data.estadoConfig)" :severity="obtenerColorEstado(data.estadoConfig)" />
      </template>
    </Column>
    
    <!-- Columna T. 1ª Aprobación actualizada -->
    <Column header="T. 1ª Aprobación" sortable style="min-width: 12rem">
      <template #body="{ data }">
        <div class="flex flex-col gap-1">
          <div class="flex items-center gap-2 px-2 py-1 rounded-md text-sm font-semibold transition-all duration-200 hover:transform hover:-translate-y-0.5 hover:shadow-sm"
               :class="getTiempoColorClass(data.tiempo_info?.creacion_a_aprobacion_1?.color)">
            <i class="pi pi-clock text-xs"></i>
            <span>
              {{ data.tiempo_info?.creacion_a_aprobacion_1?.texto || '--' }}
            </span>
          </div>
          <div class="text-xs text-gray-500 text-center">
            {{ getTiempoDescripcion(data.tiempo_info?.creacion_a_aprobacion_1?.categoria) }}
          </div>
        </div>
      </template>
    </Column>
    
    <!-- Columna T. 2ª Aprobación actualizada -->
    <Column header="T. 2ª Aprobación" sortable style="min-width: 12rem">
      <template #body="{ data }">
        <div class="flex flex-col gap-1">
          <div class="flex items-center gap-2 px-2 py-1 rounded-md text-sm font-semibold transition-all duration-200 hover:transform hover:-translate-y-0.5 hover:shadow-sm"
               :class="getTiempoColorClass(data.tiempo_info?.aprobacion_1_a_aprobacion_2?.color)">
            <i class="pi pi-forward text-xs"></i>
            <span>
              {{ data.tiempo_info?.aprobacion_1_a_aprobacion_2?.texto || '--' }}
            </span>
          </div>
          <div class="text-xs text-gray-500 text-center">
            {{ getTiempoDescripcion(data.tiempo_info?.aprobacion_1_a_aprobacion_2?.categoria) }}
          </div>
        </div>
      </template>
    </Column>
    
    <!-- Columna T. Total actualizada -->
    <Column header="T. Total" sortable style="min-width: 10rem">
      <template #body="{ data }">
        <div class="flex flex-col gap-1 border border-blue-300 rounded-lg p-2 bg-blue-50">
          <div class="flex items-center gap-2 px-2 py-1 rounded-md text-sm font-bold transition-all duration-200 hover:transform hover:-translate-y-0.5 hover:shadow-sm"
               :class="getTiempoColorClass(data.tiempo_info?.total?.color)">
            <i class="pi pi-stopwatch text-xs"></i>
            <span>
              {{ data.tiempo_info?.total?.texto || '--' }}
            </span>
          </div>
          <div class="text-xs text-gray-600 text-center font-medium">
            Total del proceso
          </div>
        </div>
      </template>
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

const deposits = ref<any[]>([]);
const selectedDeposits = ref<any[]>([]);
const loading = ref(false);
const totalRecords = ref(0);
const globalFilter = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);
const selectedDeposit = ref(null);

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

const formatAmount = (amount: number | string) => {
  return new Intl.NumberFormat('es-PE', {
    style: 'currency',
    currency: 'PEN',
  }).format(Number(amount));
};

const formatFecha = (fecha: string) => {
  return fecha;
};

// Funciones actualizadas según tus especificaciones de tiempo
const getTiempoColorClass = (color: string | undefined) => {
  const colorMap = {
    'green': 'bg-green-100 text-green-800 border border-green-200',      // 1-30 min - Excelente
    'blue': 'bg-blue-100 text-blue-800 border border-blue-200',          // 30min-1h - Muy bien
    'yellow': 'bg-yellow-100 text-yellow-800 border border-yellow-200',  // 1h - Normal
    'orange': 'bg-orange-100 text-orange-800 border border-orange-200',  // >1h-2h - Estás tardando
    'red': 'bg-red-100 text-red-800 border border-red-200 animate-pulse', // >2h - Crítico
    'gray': 'bg-gray-100 text-gray-600 border border-gray-200'           // Sin datos
  };
  return colorMap[color as keyof typeof colorMap] || 'bg-gray-100 text-gray-600 border border-gray-200';
};

const getTiempoDescripcion = (categoria: string | undefined) => {
  const descripciones = {
    'excelente': '¡Excelente!',        // 1-30 minutos
    'muy_bien': 'Muy bien',            // 30min-1h  
    'normal': 'Normal',                // 1h
    'tardando': 'Estás tardando',      // >1h-2h
    'critico': 'Crítico',              // >2h
    'sin_datos': '00:00:00',
    // Mantengo compatibilidad con los nombres antiguos por si acaso
    'muy_rapido': '¡Excelente!',
    'rapido': 'Muy bien',
    'lento': 'Estás tardando',
    'muy_lento': 'Crítico'
  };
  return descripciones[categoria as keyof typeof descripciones] || 'N/A';
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