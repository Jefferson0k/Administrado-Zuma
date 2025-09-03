<template>
  <Toolbar class="mb-6">
    <template #start>
      <Button label="Nuevo Pago" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" />
    </template>
  </Toolbar>

  <Dialog v-model:visible="sectorDialog" :style="{ width: '90vw', maxWidth: '1300px' }" header="Registro de pagos"
    :modal="true">
    <div class="flex flex-col gap-6">
      <div class="grid grid-cols-12 gap-4">
        <!-- FileUpload automático -->
        <div class="col-span-12">
          <FileUpload mode="basic" name="excel_file" :multiple="false" accept=".xlsx,.xls,.csv" :maxFileSize="10000000"
            :auto="true" chooseLabel="Seleccionar archivo Excel/CSV" class="w-full" @upload="handleFileUpload"
            @select="handleFileSelect" :disabled="loading" :customUpload="true">
            <template #empty>
              <div class="flex flex-col items-center justify-center py-8">
                <i class="pi pi-cloud-upload text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-600 mb-2">Arrastra y suelta tu archivo aquí</p>
                <p class="text-sm text-gray-400">o haz clic para seleccionar</p>
                <p class="text-xs text-gray-400 mt-2">Formatos soportados: .xlsx, .xls, .csv (máx. 10MB)</p>
                <p class="text-xs text-green-600 mt-1">Se procesará automáticamente al seleccionar</p>
              </div>
            </template>
          </FileUpload>

          <!-- Barra de progreso -->
          <div class="mt-4" v-if="loading">
            <div class="flex items-center gap-3 mb-2">
              <i class="pi pi-spin pi-cog text-blue-600"></i>
              <span class="text-sm font-medium text-gray-700">Procesando archivo...</span>
            </div>
            <ProgressBar mode="indeterminate" style="height: 6px" />
          </div>
        </div>

        <!-- Tabla de resultados -->
        <div class="col-span-12" v-if="extractedData.length > 0">
          <div class="mb-4">
            <h3 class="text-lg font-semibold mb-2">Resultados del procesamiento</h3>
            <div class="flex gap-4 text-sm">
              <span class="flex items-center gap-2">
                <i class="pi pi-check-circle text-green-600"></i>
                Coincidencias: {{ coincidencias }}
              </span>
              <span class="flex items-center gap-2">
                <i class="pi pi-times-circle text-red-600"></i>
                No coinciden: {{ noCoincidencias }}
              </span>
            </div>
          </div>

          <DataTable :value="extractedData" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]"
            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
            currentPageReportTemplate="{first} a {last} de {totalRecords} registros" tableStyle="min-width: 50rem"
            class="p-datatable-sm" stripedRows>
            <template #header>
              <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">
                  Pagos
                  <Tag severity="contrast" :value="extractedData.length" />
                </h4>
                <div class="flex flex-wrap gap-2">
                  <IconField>
                    <InputIcon>
                      <i class="pi pi-search" />
                    </InputIcon>
                    <InputText v-model="globalFilterValue" @input="onGlobalSearch" placeholder="Buscar..." />
                  </IconField>
                </div>
              </div>
            </template>

            <Column field="document" header="RUC Proveedor" sortable style="min-width: 3rem">
              <template #body="slotProps">
                <span class="font-mono">{{ slotProps.data.document }}</span>
              </template>
            </Column>

            <Column field="RUC_client" header="RUC Cliente" sortable style="min-width: 13rem">
              <template #body="slotProps">
                <span class="font-mono">{{ slotProps.data.RUC_client }}</span>
              </template>
            </Column>

            <Column field="estimated_pay_date" header="Fecha Pago" sortable style="min-width: 13rem">
              <template #body="slotProps">
                <span class="font-mono text-sm">{{ slotProps.data.estimated_pay_date }}</span>
              </template>
            </Column>

            <Column field="currency" header="Moneda" sortable style="min-width: 5rem">
              <template #body="slotProps">
                <Tag :value="slotProps.data.currency"
                  :severity="slotProps.data.currency === 'PEN' ? 'info' : 'warning'" />
              </template>
            </Column>

            <Column field="amount" header="Monto de la factura" sortable style="min-width: 13rem">
              <template #body="slotProps">
                <span class="font-mono font-semibold">
                  {{ formatCurrency(slotProps.data.amount, slotProps.data.currency) }}
                </span>
              </template>
            </Column>

            <!-- Nueva columna para Tipo de Pago -->
            <Column field="tipo_pago" header="Tipo de Pago" sortable style="min-width: 10rem">
              <template #body="slotProps">
                <Tag :value="slotProps.data.tipo_pago || 'Transferencia'" severity="info" icon="pi pi-credit-card" />
              </template>
            </Column>

            <Column field="estado" header="Estado" sortable style="min-width: 13rem">
              <template #body="slotProps">
                <Tag :value="slotProps.data.estado" :severity="getEstadoSeverity(slotProps.data.estado)"
                  :icon="getEstadoIcon(slotProps.data.estado)" />
              </template>
            </Column>

            <Column header="">
              <template #body="slotProps">
                <div class="flex gap-2 justify-center">
                  <Button icon="pi pi-credit-card" severity="success" text rounded @click="realizarPago(slotProps.data)"
                    v-tooltip="'Realizar pago'" :disabled="slotProps.data.estado !== 'Coincide'" />
                </div>
              </template>
            </Column>
          </DataTable>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-between items-center w-full">
        <small class="italic text-sm">
          Los campos marcados con <span class="text-red-500">*</span> son obligatorios.
        </small>
        <div class="flex gap-2">
          <Button label="Cancelar" icon="pi pi-times" text severity="secondary" @click="hideDialog" />
        </div>
      </div>
    </template>
  </Dialog>

  <!-- Componente de procesamiento de pago -->
  <addPaymensts :visible="showPaymentDialog" :payment-data="selectedPaymentData"
    @update:visible="showPaymentDialog = $event" @payment-processed="onPaymentProcessed"
    @cancelled="onPaymentCancelled" />
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import Toolbar from 'primevue/toolbar';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import FileUpload from 'primevue/fileupload';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import ProgressBar from 'primevue/progressbar';
import { useToast } from 'primevue/usetoast';
import { defineEmits } from 'vue';
import addPaymensts from './addPaymensts.vue';

const toast = useToast();
const emit = defineEmits(['agregado']);

const sectorDialog = ref(false);
const loading = ref(false);
const extractedData = ref([]);
const selectedFile = ref(null);
const globalFilterValue = ref('');
const showPaymentDialog = ref(false);
const selectedPaymentData = ref({});

// Computed properties para estadísticas
const coincidencias = computed(() => {
  return extractedData.value.filter(item => item.estado === 'Coincide').length;
});

const noCoincidencias = computed(() => {
  return extractedData.value.filter(item => item.estado === 'No coincide').length;
});

function resetSector() {
  loading.value = false;
  extractedData.value = [];
  selectedFile.value = null;
}

function openNew() {
  resetSector();
  sectorDialog.value = true;
}

function hideDialog() {
  sectorDialog.value = false;
  resetSector();
}

// Función para obtener la severidad del estado
function getEstadoSeverity(estado) {
  return estado === 'Coincide' ? 'success' : 'danger';
}

// Función para obtener el ícono del estado
function getEstadoIcon(estado) {
  return estado === 'Coincide' ? 'pi pi-check' : 'pi pi-times';
}

// Función para formatear moneda
function formatCurrency(amount, currency) {
  const symbol = currency === 'PEN' ? 'S/' : '$';
  return `${symbol} ${Number(amount).toLocaleString('es-PE', { minimumFractionDigits: 2 })}`;
}

// Función actualizada para realizar pago
function realizarPago(record) {
  selectedPaymentData.value = record;
  showPaymentDialog.value = true;
}

// Función para manejar cuando se procesa un pago
function onPaymentProcessed(data) {
  // Actualizar el estado del registro en la tabla
  const index = extractedData.value.findIndex(item =>
    item.document === data.document && item.amount === data.amount
  );

  if (index !== -1) {
    extractedData.value[index].estado = 'Pagado';
  }

  emit('agregado'); // Emitir evento para refrescar datos padre si es necesario
}

// Función para manejar cancelación
function onPaymentCancelled() {
  showPaymentDialog.value = false;
}

// Función para procesar todos los pagos (validación)
function procesarPagos() {
  toast.add({
    severity: 'info',
    summary: 'Procesando pagos',
    detail: `Se procesarán ${extractedData.value.length} registros`,
    life: 3000,
  });
}

function handleFileSelect(event) {
  selectedFile.value = event.files[0];
  processFile();
}

function handleFileUpload(event) {
  processFile();
}

async function processFile() {
  if (!selectedFile.value) return;

  const formData = new FormData();
  formData.append('excel_file', selectedFile.value);
  loading.value = true;

  try {
    toast.add({
      severity: 'info',
      summary: 'Procesando',
      detail: `Analizando ${selectedFile.value.name}...`,
      life: 2000,
    });

    const response = await axios.post('/payments/extraer', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    if (response.data.success && response.data.data) {
      extractedData.value = response.data.data;

      toast.add({
        severity: 'success',
        summary: 'Procesamiento completado',
        detail: `${extractedData.value.length} registros encontrados. ${coincidencias.value} coincidencias.`,
        life: 4000,
      });
    } else {
      throw new Error('Formato de respuesta inválido');
    }

  } catch (error) {
    console.error('Error al procesar el archivo:', error);
    toast.add({
      severity: 'error',
      summary: 'Error de procesamiento',
      detail: 'No se pudo procesar el archivo. Verifica el formato.',
      life: 4000,
    });
    extractedData.value = [];
  } finally {
    loading.value = false;
  }
}

function onGlobalSearch() {
  // Implementar lógica de filtrado global si es necesario
}
</script>