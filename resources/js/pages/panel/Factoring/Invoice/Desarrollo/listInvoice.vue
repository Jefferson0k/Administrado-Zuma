<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
import Tag from 'primevue/tag';
import Menu from 'primevue/menu';
import { useToast } from 'primevue/usetoast';
import UpdateStandby from './UpdateStandby.vue';
import updateActive from './updateActive.vue';
import deleteInvoice from './deleteInvoice.vue';
import showFacturas from './showFacturas.vue';
import updateInvoice from './updateInvoice.vue';
import paymentInvoice from './paymentInvoice.vue';

const props = defineProps({
  refresh: { type: Number, default: 0 }
});

const emit = defineEmits(['filters-changed']);

const toast = useToast();
const dt = ref();
const facturas = ref([]);
const selectedFacturas = ref();
const loading = ref(false);
const menu = ref();
const menuItems = ref([]);

// ▸ NUEVO: estado de ordenamiento (server-side)
const sortField = ref(null); // 'razonSocial' | 'codigo' | 'moneda' | 'montoFactura' | 'montoAsumidoZuma' | 'montoDisponible' | 'tasa' | 'fechaPago' | 'fechaCreacion' | 'estado'
const sortOrder = ref(null); // 1 | -1
const showPaymentDialog = ref(false);
const filters = ref({
  search: '',
  status: null,
  currency: null,
  min_amount: null,
  max_amount: null,
  min_rate: null,
  max_rate: null
});

// Emitir cambios de filtros al padre
watch(filters, () => emit('filters-changed', filters.value), { deep: true });

const selectedFilterFields = ref(['razonSocial']);

const filterFieldOptions = ref([
  { label: 'Razón Social', value: 'razonSocial' },
  { label: 'Código', value: 'codigo' },
  { label: 'Moneda', value: 'moneda' },
  { label: 'Tasa', value: 'tasa' },
  { label: 'Estado', value: 'estado' }
]);

const showSearchField = computed(() => selectedFilterFields.value.includes('razonSocial') || selectedFilterFields.value.includes('codigo'));
const showCurrencyField = computed(() => selectedFilterFields.value.includes('moneda'));
const showAmountFields = computed(() => selectedFilterFields.value.includes('montoFactura'));
const showRateFields = computed(() => selectedFilterFields.value.includes('tasa'));
const showStatusField = computed(() => selectedFilterFields.value.includes('estado'));

const pagination = ref({
  page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
  last_page: 1
});

const statusOptions = ref([
  { label: 'Todos los estados', value: null },
  { label: 'Inactivo', value: 'inactive' },
  { label: 'Activo', value: 'active' },
  { label: 'Vencido', value: 'expired' },
  { label: 'Judicializado', value: 'judicialized' },
  { label: 'Reprogramado', value: 'reprogramed' },
  { label: 'Pagado', value: 'paid' },
  { label: 'Cancelado', value: 'canceled' },
  { label: 'En Standby', value: 'daStandby' }
]);

const currencyOptions = ref([
  { label: 'Todas las monedas', value: null },
  { label: 'Soles (PEN)', value: 'PEN' },
  { label: 'Dólares (USD)', value: 'USD' }
]);

const showStandbyDialog = ref(false);
const showActiveDialog = ref(false);
const showFacturaDialog = ref(false);
const showUpdateDialog = ref(false);
const showDeleteDialog = ref(false);
const selectedFacturaId = ref(null);
const selectedFacturaData = ref(null);

// Exportar a Excel (respeta filtros + orden actual)
async function exportToExcel() {
  try {
    const params = Object.fromEntries(
      Object.entries(filters.value).filter(([_, v]) => v !== null && v !== '')
    );
    if (sortField.value) {
      params.sort_field = sortField.value;
      params.sort_order = sortOrder.value === 1 ? 'asc' : 'desc';
    }
    const queryString = new URLSearchParams(params).toString();
    const exportUrl = `/invoices/export/excel?${queryString}`;

    const link = document.createElement('a');
    link.href = exportUrl;
    link.download = `facturas_${new Date().toISOString().split('T')[0]}.xlsx`;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Exportación iniciada correctamente', life: 3000 });
  } catch (error) {
    console.error('Error al exportar:', error);
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al exportar las facturas', life: 3000 });
  }
}

// Exponer la función de exportación para que el componente padre pueda usarla
defineExpose({
  exportToExcel
});

// Funciones para los estados de aprobación
function getApprovalStatusLabel(status) {
  const approvalLabels = {
    'pending': 'Inactivo',
    'approved': 'Aprobado',
    'rejected': 'Anulado',
    'active': 'Activado',
    'inactive': 'Inactivo',
    'daStandby': 'Standby',
    'observed': 'Observado'
  };
  return approvalLabels[status] || status;
}

function getApprovalStatusSeverity(status) {
  switch (status) {
    case 'pending': return 'secondary';
    case 'approved': return 'success';
    case 'rejected': return 'danger';
    case 'daStandby': return 'warn';
    case 'observed': return 'info';
    case 'active': return 'success';
    default: return 'secondary';
  }
}

function getStatusLabel(status) {
  const statusLabels = {
    'approved': 'Aprobado',
    'rejected': 'Anulado',
    'inactive': 'Inactivo',
    'active': 'Activo',
    'expired': 'Vencido',
    'judicialized': 'Judicializado',
    'reprogramed': 'Reprogramado',
    'paid': 'Pagado',
    'canceled': 'Cancelado',
    'daStandby': 'Standby',
    'observed': 'Observado',
    'annulled': 'Anulado'
  };
  return statusLabels[status] || status;
}

function getStatusSeverity(status) {
  switch (status) {
    case 'approved': return 'success';
    case 'inactive': return 'secondary';
    case 'active': return 'success';
    case 'expired': return 'danger';
    case 'rejected': return 'danger';
    case 'judicialized': return 'warn';
    case 'reprogramed': return 'info';
    case 'paid': return 'contrast';
    case 'canceled': return 'danger';
    case 'daStandby': return 'warn';
    case 'observed': return 'info';
    case 'annulled': return 'danger'; // 👈 mismo estilo que canceled
    default: return 'secondary';
  }
}

const formatCurrency = (value, moneda) => {
  if (!value) return '';
  const number = parseFloat(value);
  const currencySymbol = moneda === 'USD' ? 'US$' : 'S/';
  return `${currencySymbol} ${number.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

function clearUnselectedFilters() {
  if (!showCurrencyField.value) filters.value.currency = null;
  if (!showAmountFields.value) { filters.value.min_amount = null; filters.value.max_amount = null; }
  if (!showRateFields.value) { filters.value.min_rate = null; filters.value.max_rate = null; }
  if (!showStatusField.value) filters.value.status = null;
  if (!showSearchField.value) filters.value.search = '';
}

async function loadData() {
  loading.value = true;
  try {
    const params = {
      page: pagination.value.page,
      per_page: pagination.value.per_page,
      ...Object.fromEntries(
        Object.entries(filters.value).filter(([_, v]) => v !== null && v !== '')
      )
    };

    // ▸ incluir el orden actual si existe
    if (sortField.value) {
      params.sort_field = sortField.value;
      params.sort_order = sortOrder.value === 1 ? 'asc' : 'desc';
    }

    const response = await axios.get('/invoices', { params });

    // Suponiendo una Laravel Resource Collection
    if (response.data?.data && Array.isArray(response.data.data)) {
      facturas.value = response.data.data;
      if (response.data?.meta) {
        pagination.value = {
          page: response.data.meta.current_page,
          per_page: response.data.meta.per_page,
          total: response.data.meta.total,
          from: response.data.meta.from,
          to: response.data.meta.to,
          last_page: response.data.meta.last_page
        };
      } else {
        const total = response.data.data.length;
        pagination.value = { page: 1, per_page: total, total, from: total ? 1 : 0, to: total, last_page: 1 };
      }
    } else {
      facturas.value = [];
      pagination.value = { page: 1, per_page: 10, total: 0, from: 0, to: 0, last_page: 1 };
    }
  } catch (error) {
    console.error('Error al cargar facturas:', error);
  } finally {
    loading.value = false;
  }
}

function gestionarPago(factura) {
  selectedFacturaId.value = factura.id;
  showPaymentDialog.value = true;
}

function onPaymentCancelled() {
  selectedFacturaId.value = null;
  showPaymentDialog.value = false;
}

function clearFilters() {
  filters.value = {
    search: '',
    status: null,
    currency: null,
    min_amount: null,
    max_amount: null,
    min_rate: null,
    max_rate: null
  };
  pagination.value.page = 1;
  loadData();
}

function applyFilters() {
  pagination.value.page = 1;
  loadData();
}

const onPage = (event) => {
  pagination.value.page = event.page + 1;
  pagination.value.per_page = event.rows;
  loadData();
};

// ▸ handler de orden (PrimeVue DataTable lazy)
function onSort(event) {
  // event.sortField (string) | event.sortOrder (1|-1)
  sortField.value = event.sortField;
  sortOrder.value = event.sortOrder;
  pagination.value.page = 1;
  loadData();
}

function verFactura(factura) {
  selectedFacturaId.value = factura.id;
  showFacturaDialog.value = true;
}
function editFactura(factura) {
  selectedFacturaId.value = factura.id;
  showUpdateDialog.value = true;
}
function confirmDelete(factura) {
  selectedFacturaId.value = factura.id;
  selectedFacturaData.value = factura;
  showDeleteDialog.value = true;
}
function verInversionistas(factura) {
  router.get(`/factoring/${factura.id}/inversionistas`);
}


function ponerEnStandby(factura) {
  selectedFacturaId.value = factura.id;
  showStandbyDialog.value = true;
}
function ponerActivo(factura) {
  selectedFacturaId.value = factura.id;
  showActiveDialog.value = true;
}
function onStandbyConfirmed() { loadData(); }
function onStandbyCancelled() { selectedFacturaId.value = null; }
function onFacturaDialogCancelled() { selectedFacturaId.value = null; showFacturaDialog.value = false; }
function onDeleteConfirmed() { loadData(); showDeleteDialog.value = false; selectedFacturaId.value = null; selectedFacturaData.value = null; }
function onDeleteCancelled() { selectedFacturaId.value = null; selectedFacturaData.value = null; showDeleteDialog.value = false; }
function onUpdateConfirmed() { loadData(); showUpdateDialog.value = false; selectedFacturaId.value = null; }
function onUpdateCancelled() { selectedFacturaId.value = null; showUpdateDialog.value = false; }

const toggleMenu = (event, factura) => {
  let items = [
    { label: 'Ver factura', icon: 'pi pi-file', command: () => verFactura(factura) }
  ];

  // 👇 inactive y observed tendrán las mismas opciones
  if (['inactive', 'observed'].includes(factura.estado?.toLowerCase().trim())) {
    items = items.concat([
      {
        label: 'Activo',
        icon: 'pi pi-check-circle',
        command: () => ponerActivo(factura)
      },
      { separator: true },
      {
        label: 'Editar',
        icon: 'pi pi-pencil',
        command: () => editFactura(factura)
      },
      {
        label: 'Eliminar',
        icon: 'pi pi-trash',
        command: () => confirmDelete(factura),
        class: 'p-menuitem-link-danger'
      }
    ]);
  } else if (factura.estado === 'active') {
    items = items.concat([
      {
        label: 'Ver inversionistas',
        icon: 'pi pi-eye',
        command: () => verInversionistas(factura)
      },
      { separator: true },
      {
        label: 'Poner en standby',
        icon: 'pi pi-pause',
        command: () => ponerEnStandby(factura)
      }
    ]);
  } else if (factura.estado === 'daStandby') {
    items = items.concat([
      {
        label: 'Gestionar pago',
        icon: 'pi pi-wallet',
        command: () => gestionarPago(factura)
      }
    ]);
  } else if (factura.estado === 'reprogramed') {
    items = items.concat([
      {
        label: 'Ver inversionistas',
        icon: 'pi pi-eye',
        command: () => verInversionistas(factura)
      }
    ]);
  } else {
    items = items.concat([
      {
        label: 'Ver inversionistas',
        icon: 'pi pi-eye',
        command: () => verInversionistas(factura)
      }
    ]);
  }

  menuItems.value = items;
  menu.value.toggle(event);
};

let searchTimeout;
watch(() => filters.value.search, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => applyFilters(), 500);
});

const translateTipo = (value) => {
  if (!value) return null;

  const traducciones = {
    'annulled': 'Anulado',
    'pending': 'Pendiente',
    'approved': 'Aprobado',
    'rejected': 'Rechazado'
  };

  return traducciones[value] || value;
};

watch([
  () => filters.value.status,
  () => filters.value.currency,
  () => filters.value.min_amount,
  () => filters.value.max_amount,
  () => filters.value.min_rate,
  () => filters.value.max_rate
], () => applyFilters());
watch(selectedFilterFields, () => { clearUnselectedFilters(); applyFilters(); });
watch(() => props.refresh, () => loadData());

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="space-y-4">
    <DataTable ref="dt" v-model:selection="selectedFacturas" :value="facturas" dataKey="codigo" :paginator="true"
      :rows="pagination.per_page" :totalRecords="pagination.total" :first="(pagination.page - 1) * pagination.per_page"
      :loading="loading" @page="onPage" :rowsPerPageOptions="[5, 10, 20, 50]" scrollable scrollHeight="500px" lazy
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      :currentPageReportTemplate="`Mostrando ${pagination.from} a ${pagination.to} de ${pagination.total} facturas`"
      class="p-datatable-sm" :sortField="sortField" :sortOrder="sortOrder" sortMode="single" @sort="onSort">
      <template #header>
        <div class="flex flex-wrap gap-2 items-center justify-between">
          <h4 class="m-0">
            Facturas
            <Tag severity="contrast" :value="pagination.total" />
          </h4>
          <div class="flex flex-wrap gap-2">
            <MultiSelect v-model="selectedFilterFields" :options="filterFieldOptions" optionLabel="label"
              optionValue="value" placeholder="Seleccionar campos de filtro" class="w-15" display="chip" />
            <div v-if="showSearchField" class="lg:col-span-2">
              <IconField>
                <InputIcon><i class="pi pi-search" /></InputIcon>
                <InputText v-model="filters.search" placeholder="Buscar por razón social, código..." class="w-15" />
              </IconField>
            </div>
            <div v-if="showCurrencyField">
              <Select v-model="filters.currency" :options="currencyOptions" optionLabel="label" optionValue="value"
                placeholder="Seleccionar moneda" class="w-15" />
            </div>
            <div v-if="showStatusField">
              <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value"
                placeholder="Seleccionar estado" class="w-15" />
            </div>

            <template v-if="showRateFields">
              <div>
                <InputNumber v-model="filters.min_rate" :minFractionDigits="2" :maxFractionDigits="2" :min="0"
                  :max="100" placeholder="0.00" class="w-full" />
              </div>
              <div>
                <InputNumber v-model="filters.max_rate" :minFractionDigits="2" :maxFractionDigits="2" :min="0"
                  :max="100" placeholder="0.00" class="w-full" />
              </div>
            </template>

            <Button icon="pi pi-filter-slash" severity="secondary" outlined @click="clearFilters" />
            <Button icon="pi pi-filter" severity="contrast" @click="applyFilters" />
            <Button icon="pi pi-refresh" severity="contrast" outlined rounded aria-label="Refresh" @click="loadData" />
          </div>
        </div>
      </template>

      <template #empty>
        <div class="text-center p-4">
          <i class="pi pi-inbox text-4xl text-gray-400 mb-4 block"></i>
          <p class="text-gray-500">No hay facturas que coincidan con los filtros</p>
        </div>
      </template>

      <template #loading>
        <div class="text-center p-4">
          <i class="pi pi-spinner pi-spin text-2xl text-blue-500 mb-4 block"></i>
          <p class="text-gray-500">Cargando facturas...</p>
        </div>
      </template>

      <Column selectionMode="multiple" style="width: 1rem" :exportable="false" />
      <Column field="razonSocial" header="Razón Social" sortable style="min-width: 9rem">
        <template #body="slotProps">
          <span 
            class="truncate block max-w-[5rem] cursor-pointer"
            v-tooltip.top="slotProps.data.razonSocial"
          >
            {{ slotProps.data.razonSocial }}
          </span>
        </template>
      </Column>
      <Column field="ruc" header="Ruc" sortable style="min-width: 7rem" />
      <Column field="codigo" header="Código" sortable style="min-width: 12rem" />
      <Column field="moneda" header="Moneda" sortable style="min-width: 5rem" />
      <Column field="montoFactura" header="M. Factura" sortable style="min-width: 8rem">
        <template #body="slotProps">
          {{ formatCurrency(slotProps.data.montoFactura, slotProps.data.moneda) }}
        </template>
      </Column>
      <Column field="montoAsumidoZuma" header="M. asumido zuma" sortable style="min-width: 11rem">
        <template #body="slotProps">
          {{ formatCurrency(slotProps.data.montoAsumidoZuma, slotProps.data.moneda) }}
        </template>
      </Column>
      <Column field="montoDisponible" header="Monto Disponible" sortable style="min-width: 11rem">
        <template #body="slotProps">
          {{ formatCurrency(slotProps.data.montoDisponible, slotProps.data.moneda) }}
        </template>
      </Column>
      <Column field="tasa" header="Tasa (%)" sortable style="min-width: 7rem" />
      <Column field="fechaPago" header="Fecha de Pago" sortable style="min-width: 10rem" />
      <Column field="PrimerStado" header="1ª Aprobador" sortable style="min-width: 9rem">
        <template #body="slotProps">
          <template v-if="slotProps.data.PrimerStado">
            <Tag 
              :value="getStatusLabel(slotProps.data.PrimerStado)" 
              :severity="getStatusSeverity(slotProps.data.PrimerStado)" 
            />
          </template>
          <template v-else>
            <span class="italic text-gray-500"> - </span>
          </template>
        </template>
      </Column>

      <Column field="userprimerNombre" header="1ª Usuario" sortable style="min-width: 16rem">
        <template #body="slotProps">
          <span v-if="slotProps.data.userprimerNombre">
            {{ slotProps.data.userprimerNombre }}
          </span>
          <span v-else class="italic text-gray-500"> - </span>
        </template>
      </Column>

      <Column field="tiempoUno" header="T. 1ª Aprobación" sortable style="min-width: 12rem">
        <template #body="slotProps">
          <span v-if="slotProps.data.tiempoUno">
            {{ slotProps.data.tiempoUno }}
          </span>
          <span v-else class="italic text-gray-500"> - </span>
        </template>
      </Column>

      <Column field="SegundaStado" header="2ª Aprobador" sortable style="min-width: 9rem">
        <template #body="slotProps">
          <template v-if="slotProps.data.SegundaStado">
            <Tag 
              :value="getApprovalStatusLabel(slotProps.data.SegundaStado)" 
              :severity="getApprovalStatusSeverity(slotProps.data.SegundaStado)" 
            />
          </template>
          <template v-else>
            <span class="italic text-gray-500"> - </span>
          </template>
        </template>
      </Column>

      <Column field="userdosNombre" header="2do Usuario" sortable style="min-width: 16rem">
        <template #body="slotProps">
          <span v-if="slotProps.data.userdosNombre">
            {{ slotProps.data.userdosNombre }}
          </span>
          <span v-else class="italic text-gray-500"> - </span>
        </template>
      </Column>

      <Column field="tiempoDos" header="T. 2ª Aprobación" sortable style="min-width: 12rem">
        <template #body="slotProps">
          <span v-if="slotProps.data.tiempoDos">
            {{ slotProps.data.tiempoDos }}
          </span>
          <span v-else class="italic text-gray-500"> - </span>
        </template>
      </Column>

      <Column field="estado" header="Estado Conclusion" sortable style="min-width: 11rem">
        <template #body="slotProps">
          <template v-if="!slotProps.data.estado">
            <span class="italic">Sin estado</span>
          </template>
          <template v-else>
            <Tag :value="getApprovalStatusLabel(slotProps.data.estado)"
              :severity="getApprovalStatusSeverity(slotProps.data.estado)" />
          </template>
        </template>
      </Column>
      <Column field="tipo" header="Tipo" sortable style="min-width: 5rem">
      <template #body="slotProps">
        <span class="italic text-gray-500" v-if="!slotProps.data.tipo">-</span>
        <span v-else>{{ translateTipo(slotProps.data.tipo) }}</span>
      </template>
    </Column>
      <Column field="situacion" header="Situacion" sortable style="min-width: 10rem">
        <template #body="slotProps">
          <span :class="!slotProps.data.situacion ? 'italic' : ''">
            {{ slotProps.data.situacion || '-' }}
          </span>
        </template>
      </Column>
      <Column field="condicionOportunidadInversion" header="Cond. Oportunidad de Inversión" sortable
        style="min-width: 18rem">
        <template #body="slotProps">
          <span :class="!slotProps.data.condicionOportunidadInversion ? 'italic' : ''">
            {{ slotProps.data.condicionOportunidadInversion || '-' }}
          </span>
        </template>
      </Column>
      <Column field="fechaHoraCierreInversion" header="Fecha y Hora Cierre de Inversión" sortable
        style="min-width: 18rem">
        <template #body="slotProps">
          <span :class="!slotProps.data.fechaHoraCierreInversion ? 'italic' : ''">
            {{ slotProps.data.fechaHoraCierreInversion || '-' }}
          </span>
        </template>
      </Column>
      <Column field="porcentajeMetaTerceros" header="% Obj Terceros" sortable style="min-width: 10rem">
        <template #body="slotProps">
          <span :class="!slotProps.data.porcentajeMetaTerceros ? 'italic' : ''">
            {{ slotProps.data.porcentajeMetaTerceros || '-' }}
          </span>
        </template>
      </Column>

      <Column field="porcentajeInversionTerceros" header="% Invertido Terceros" sortable style="min-width: 12rem">
        <template #body="slotProps">
          <span :class="!slotProps.data.porcentajeInversionTerceros ? 'italic' : ''">
            {{ slotProps.data.porcentajeInversionTerceros || '-' }}
          </span>
        </template>
      </Column>

      <Column field="fechaCreacion" header="Fecha Creación" sortable style="min-width: 13rem" />
      <Column header="" :exportable="false">
        <template #body="slotProps">
          <Button icon="pi pi-ellipsis-v" text rounded severity="secondary" @click="toggleMenu($event, slotProps.data)"
            aria-label="Opciones" />
        </template>
      </Column>
    </DataTable>
    <Menu ref="menu" :model="menuItems" :popup="true" />
    <!-- Dialogs -->
    <UpdateStandby v-model="showStandbyDialog" :factura-id="selectedFacturaId" @confirmed="onStandbyConfirmed"
      @cancelled="onStandbyCancelled" />
    <updateActive v-model="showActiveDialog" :factura-id="selectedFacturaId" @confirmed="onStandbyConfirmed"
      @cancelled="onStandbyCancelled" />
    <deleteInvoice v-model="showDeleteDialog" :factura-id="selectedFacturaId" :factura-data="selectedFacturaData"
      @confirmed="onDeleteConfirmed" @cancelled="onDeleteCancelled" />
    <showFacturas v-model="showFacturaDialog" :factura-id="selectedFacturaId" @cancelled="onFacturaDialogCancelled" />
    <updateInvoice v-model="showUpdateDialog" :factura-id="selectedFacturaId" @updated="onUpdateConfirmed"
      @cancelled="onUpdateCancelled" />
    <paymentInvoice v-model="showPaymentDialog" :factura-id="selectedFacturaId" @cancelled="onPaymentCancelled" />
  </div>
</template>