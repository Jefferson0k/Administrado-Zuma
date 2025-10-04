<template>
  <Toolbar class="mb-6">
    <template #start></template>

    <template #end>
      <div class="flex gap-2">
        <Button label="Export Excel" icon="pi pi-file-excel" severity="secondary" @click="exportExcel"
          :loading="exportingExcel" />
      </div>
    </template>
  </Toolbar>

  <DataTable ref="dt" v-model:selection="selectedDeposits" :value="deposits" dataKey="id" :lazy="true" :paginator="true"
    :rows="rowsPerPage" :totalRecords="totalRecords" :first="(currentPage - 1) * rowsPerPage" :loading="loading"
    :rowsPerPageOptions="[5, 10, 20, 50]"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} depósitos" scrollable scrollHeight="574px"
    class="p-datatable-sm" @page="onPage" @sort="onSort">
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

          <Button icon="pi pi-refresh" outlined severity="contrast" rounded aria-label="Refrescar" @click="refreshNow"
            :disabled="loading" />
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

    <Column field="status0" header="1º Aprobador" sortable style="min-width: 7rem">
      <template #body="{ data }">
        <Tag :value="traducirEstado(data.status0)" :severity="obtenerColorEstado(data.status0)" />
      </template>
    </Column>

    <Column field="fecha_aprobacion_1" header="T. 1ª Aprobación" sortable style="min-width: 12rem">
      <template #body="{ data }">
        <span>{{ formatFecha(data.fecha_aprobacion_1) }}</span>
      </template>
    </Column>

    <Column field="aprobado_por_1_nombre" header="1º Usuario" sortable style="min-width: 10rem" />

    <Column field="status" header="2º Aprobador" sortable style="min-width: 7rem">
      <template #body="{ data }">
        <Tag :value="traducirEstado(data.status)" :severity="obtenerColorEstado(data.status)" />
      </template>
    </Column>

    <Column field="fecha_aprobacion_2" header="T. 2ª Aprobación" sortable style="min-width: 12rem">
      <template #body="{ data }">
        <span>{{ formatFecha(data.fecha_aprobacion_2) }}</span>
      </template>
    </Column>

    <Column field="aprobado_por_2_nombre" header="2º Usuario" sortable style="min-width: 10rem" />


    <Column field="status_conclusion" header="Conclusión" sortable style="min-width: 8rem">
      <template #body="{ data }">
        <Tag :value="traducirEstado(data.status_conclusion)" :severity="obtenerColorEstado(data.status_conclusion)" />
      </template>
    </Column>

    <Column field="creacion" header="Fecha Creación" sortable style="min-width: 12rem">
      <template #body="{ data }">
        <div class="flex flex-col">
          <div class="font-medium">{{ formatFecha(data.creacion) }}</div>
        </div>
      </template>
    </Column>

    <Column header="" :exportable="false" style="width: 8.5rem">
      <template #body="{ data }">
        <div class="flex gap-2">
          <Button icon="pi pi-eye" severity="contrast" outlined rounded @click="verDeposito(data)"
            v-tooltip="'Ver Detalle'" />
          <Button icon="pi pi-history" severity="secondary" outlined rounded @click="verHistorialAprobacion(data)"
            v-tooltip="'Historial de aprobaciones'" />
        </div>
      </template>
    </Column>

  </DataTable>

  <ShowDeposit v-if="selectedDeposit" :deposit="selectedDeposit" @close="cerrarDetalle" @refresh="cargarDepositos" />

  <Dialog v-model:visible="historyVisible" :style="{ width: '900px', maxWidth: '95vw' }"
    header="Historial de Aprobaciones" :modal="true" :closable="true" @hide="cerrarHistorial">
    <div v-if="historyLoading" class="p-4 text-center">
      <i class="pi pi-spin pi-spinner text-2xl"></i>
      <p class="mt-2">Cargando historial...</p>
    </div>

    <div v-else>
      <div v-if="historyRows.length === 0" class="p-4 text-center text-sm text-gray-500">
        Sin registros de historial.
      </div>

      <DataTable v-else :value="historyRows" dataKey="id" class="p-datatable-sm">
        <Column field="id" header="#" style="width: 5rem" />

        <Column header="1º Aprobación" style="min-width: 10rem">
          <template #body="{ data }">
            <div class="space-y-1">
              <div>

                <Tag :value="traducirEstado(data.approval1_status)"
                  :severity="obtenerColorEstado(data.approval1_status)" />
              </div>
            </div>
          </template>
        </Column>

        <Column header="1º Usuario" style="min-width: 10rem">
          <template #body="{ data }">
            <div class="space-y-1">

              <div> {{ resolveUserName(data, 'approval1_by') }}</div>

            </div>
          </template>
        </Column>

        <Column header="1º T Aprobación" style="min-width: 10rem">
          <template #body="{ data }">
            <div class="space-y-1">


              <div> {{ formatFecha(data.approval1_at) }}</div>

            </div>
          </template>
        </Column>

        <Column header="1º Comentario" style="min-width: 10rem">
          <template #body="{ data }">
            <div class="space-y-1">
              <div v-if="data.approval1_comment"> {{ data.approval1_comment }}</div>
            </div>
          </template>
        </Column>

        <Column header="2º Aprobación" style="min-width: 10rem">
          <template #body="{ data }">
            <div class="space-y-1">
              <div>

                <Tag :value="traducirEstado(data.approval2_status)"
                  :severity="obtenerColorEstado(data.approval2_status)" />
              </div>

            </div>
          </template>
        </Column>

        <Column header="2º Usuario" style="min-width: 10rem">
          <template #body="{ data }">
            <div class="space-y-1">

              <div>{{ resolveUserName(data, 'approval2_by') }}</div>
            </div>
          </template>
        </Column>

        <Column header="2º T Aprobación" style="min-width: 10rem">
          <template #body="{ data }">
            <div class="space-y-1">

              <div> {{ formatFecha(data.approval2_at) }}</div>

            </div>
          </template>
        </Column>

        <Column header="2º Comentario" style="min-width: 10rem">
          <template #body="{ data }">
            <div class="space-y-1">
              <div v-if="data.approval2_comment"> {{ data.approval2_comment }}</div>
            </div>
          </template>
        </Column>
      </DataTable>
    </div>
  </Dialog>

</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import DataTable, { DataTablePageEvent, DataTableSortEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Toolbar from 'primevue/toolbar';
import { useToast } from 'primevue/usetoast';
import { debounce } from 'lodash';
import ShowDeposit from './showDeposit.vue';
import Dialog from 'primevue/dialog';


type SortOrder = 1 | 0 | -1;

const toast = useToast();

// state
const deposits = ref<any[]>([]);
const selectedDeposits = ref<any[]>([]);
const loading = ref(false);
const totalRecords = ref(0);

const globalFilter = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);

// server sort state
const sortField = ref<string | null>(null);
const sortOrder = ref<SortOrder>(0); // 1 asc, -1 desc, 0 none

const selectedDeposit = ref<any | null>(null);

// export loading
const exportingExcel = ref(false);


// -------- historial modal ----------
const historyVisible = ref(false);
const historyLoading = ref(false);
const historyRows = ref<any[]>([]);
const historyForDeposit = ref<any | null>(null);

async function verHistorialAprobacion(depositData: any) {
  historyForDeposit.value = depositData;
  historyVisible.value = true;
  historyLoading.value = true;

  try {
    const { data } = await axios.get(`/deposit/${depositData.id}/approval-history`);
    historyRows.value = Array.isArray(data?.data) ? data.data : [];
  } catch (error: any) {
    console.error('Error al cargar historial:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'No se pudo cargar el historial de aprobaciones',
      life: 4000
    });
  } finally {
    historyLoading.value = false;
  }
}

function cerrarHistorial() {
  historyVisible.value = false;
  historyForDeposit.value = null;
  historyRows.value = [];
}

// Evita choque de nombres entre columnas y relaciones en JSON (approval1_by / approval1By)
function resolveUserName(row: any, key: 'approval1_by' | 'approval2_by') {
  // intenta relación en snake_case (probable), luego camelCase, o muestra raya
  return row?.[key]?.name || row?.[key.replace('_by', 'By')]?.name || '—';
}


// -------- data loading ----------
const buildQueryParams = () => {
  const params: Record<string, any> = {
    search: globalFilter.value || undefined,
    page: currentPage.value,
    perPage: rowsPerPage.value
  };

  if (sortField.value && sortOrder.value) {
    params.sortField = sortField.value;
    params.sortDir = sortOrder.value === 1 ? 'asc' : 'desc';
  }

  return params;
};

const cargarDepositos = async (ctx?: Partial<{ page: number; rows: number }>) => {
  loading.value = true;

  if (ctx?.page != null) currentPage.value = ctx.page;
  if (ctx?.rows != null) rowsPerPage.value = ctx.rows;

  try {
    const { data } = await axios.get('/deposit', { params: buildQueryParams() });

    // Esperamos: { total, data: [] }
    deposits.value = data.data ?? [];
    totalRecords.value = Number(data.total ?? deposits.value.length);
  } catch (error: any) {
    console.error('Error al cargar depósitos:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'No se pudo cargar la lista de depósitos',
      life: 4000
    });
  } finally {
    loading.value = false;
  }
};

const refreshNow = () => {
  // conservar página y orden actual
  cargarDepositos();
};

// eventos DataTable
const onGlobalSearch = debounce(() => {
  currentPage.value = 1;
  cargarDepositos();
}, 500);

const onPage = (event: DataTablePageEvent) => {
  // event.page es base 0 → sumamos 1
  cargarDepositos({ page: (event.page ?? 0) + 1, rows: Number(event.rows ?? rowsPerPage.value) });
};

const onSort = (event: DataTableSortEvent) => {
  // update server-side sort state
  sortField.value = (event.sortField as string) || null;
  sortOrder.value = (event.sortOrder as SortOrder) ?? 0;

  // IMPORTANT: do NOT touch currentPage here.
  // PrimeVue resets its internal paginator on sort, but since
  // we bind :first="(currentPage - 1) * rowsPerPage", keeping
  // currentPage unchanged preserves the visible page.
  cargarDepositos();
};


// -------- detalle modal ----------
function verDeposito(depositData: any) {
  selectedDeposit.value = depositData;
}

function cerrarDetalle() {
  selectedDeposit.value = null;
}

// -------- descarga util ----------
const downloadFile = async (
  url: string,
  filename: string,
  params: any,
  loadingRef: { value: boolean }
) => {
  loadingRef.value = true;
  try {
    const response = await axios.get(url, {
      params,
      responseType: 'blob',
      timeout: 60000
    });

    if (!response.data || response.data.size === 0) {
      throw new Error('El archivo descargado está vacío');
    }

    const contentType = response.headers['content-type'] || 'application/octet-stream';
    const blob = new Blob([response.data], { type: contentType });

    const downloadUrl = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = filename;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();

    setTimeout(() => {
      document.body.removeChild(link);
      window.URL.revokeObjectURL(downloadUrl);
    }, 100);

    toast.add({ severity: 'success', summary: 'Descargado', detail: filename, life: 2500 });
    return true;
  } catch (error: any) {
    console.error(`Error al descargar ${url}:`, error);
    toast.add({
      severity: 'error',
      summary: 'Error al exportar',
      detail:
        error?.response?.data?.message ||
        error?.message ||
        'Ocurrió un problema generando el archivo',
      life: 4500
    });
    return false;
  } finally {
    loadingRef.value = false;
  }
};

// -------- export --------
const exportExcel = async () => {
  const fecha = new Date().toISOString().split('T')[0];
  const filename = `depositos_${fecha}.xlsx`;

  const params = {
    search: globalFilter.value || undefined,
    // Si tu backend soporta export con orden/filtrado, lo pasamos también:
    sortField: sortField.value || undefined,
    sortDir: sortOrder.value ? (sortOrder.value === 1 ? 'asc' : 'desc') : undefined,
    format: 'excel'
  };

  await downloadFile('/deposit/export/excel', filename, params, exportingExcel);
};

// -------- format helpers --------
const formatAmount = (amount: number | string) => {
  return new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(
    Number(amount)
  );
};

const formatFecha = (fecha?: string | null) => {
  if (!fecha) return '—';
  // Intentar parsear ISO o string común
  const d = new Date(fecha);
  if (isNaN(d.getTime())) return fecha; // fallback: mostrar crudo si no parsea
  return new Intl.DateTimeFormat('es-PE', {
    dateStyle: 'medium',
    timeStyle: 'short'
  }).format(d);
};

function obtenerColorEstado(status: string) {
  switch (status) {
    case 'approved':
      return 'success';
    case 'observed':
      return 'info';
    case 'pending':
      return 'warn';
    case 'rejected':
      return 'danger';
    default:
      return 'contrast';
  }
}

function traducirEstado(status: string) {
  switch (status) {
    case 'approved':
      return 'Aprobado';
    case 'observed':
      return 'Observado';
    case 'pending':
      return 'Pendiente';
    case 'rejected':
      return 'Rechazado';
    default:
      return status;
  }
}

// init
onMounted(() => {
  cargarDepositos();
});
</script>
