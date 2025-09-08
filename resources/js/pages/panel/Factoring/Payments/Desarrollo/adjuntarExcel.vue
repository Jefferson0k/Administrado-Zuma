<template>
  <Toolbar class="mb-6">
    <template #start>
      <Button label="Nuevo Pago" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" />
    </template>
  </Toolbar>

  <Dialog v-model:visible="sectorDialog" :style="{ width: '95vw', maxWidth: '1400px' }" header="Registro de pagos"
    :modal="true">
    <div class="flex flex-col gap-6">
      <div class="grid grid-cols-12 gap-4">
        <!-- Subida de archivo -->
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
              <span class="flex items-center gap-2">
                <i class="pi pi-info-circle text-blue-600"></i>
                Procesados: {{ procesados }}
              </span>
            </div>
          </div>

          <DataTable :value="extractedData" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]"
            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
            currentPageReportTemplate="{first} a {last} de {totalRecords} registros" tableStyle="min-width: 70rem"
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

            <!-- Columnas de la tabla -->
            <Column field="loan_number" header="Nro. Prestamo" sortable style="min-width: 10rem">
              <template #body="slotProps">
                <span class="font-mono">{{ slotProps.data.loan_number }}</span>
              </template>
            </Column>

            <Column field="document" header="RUC Proveedor" sortable style="min-width: 10rem">
              <template #body="slotProps">
                <span class="font-mono">{{ slotProps.data.document }}</span>
              </template>
            </Column>

            <Column field="invoice_number" header="Nro. Factura" sortable style="min-width: 10rem">
              <template #body="slotProps">
                <span class="font-mono">{{ slotProps.data.invoice_number }}</span>
              </template>
            </Column>

            <Column field="RUC_client" header="RUC Cliente" sortable style="min-width: 10rem">
              <template #body="slotProps">
                <span class="font-mono">{{ slotProps.data.RUC_client }}</span>
              </template>
            </Column>

            <Column field="currency" header="Moneda" sortable style="min-width: 6rem">
              <template #body="slotProps">
                <Tag :value="slotProps.data.currency"
                  :severity="slotProps.data.currency === 'PEN' ? 'info' : 'warning'" />
              </template>
            </Column>

            <Column field="amount" header="Monto" sortable style="min-width: 10rem">
              <template #body="slotProps">
                <span class="font-mono font-semibold">
                  {{ formatCurrency(slotProps.data.amount, slotProps.data.currency) }}
                </span>
              </template>
            </Column>

            <Column field="estimated_pay_date" header="Fecha Estimada" sortable style="min-width: 10rem">
              <template #body="slotProps">
                <span class="font-mono text-sm">{{ slotProps.data.estimated_pay_date }}</span>
              </template>
            </Column>

            <Column field="saldo" header="Monto Pago" sortable style="min-width: 10rem">
              <template #body="slotProps">
                <span class="font-mono text-blue-600 font-medium">
                  {{ formatCurrency(slotProps.data.saldo, slotProps.data.currency) }}
                </span>
              </template>
            </Column>

            <Column field="tipo_pago" header="T. Pago" sortable style="min-width: 12rem">
              <template #body="slotProps">
                <Tag :value="slotProps.data.tipo_pago" 
                     :severity="getTipoPagoSeverity(slotProps.data.tipo_pago)" />
              </template>
            </Column>

            <Column field="status" header="Estado" sortable style="min-width: 12rem">
              <template #body="slotProps">
                <Tag :value="slotProps.data.status"
                  :severity="getStatusSeverity(slotProps.data.status)" />
              </template>
            </Column>

            <Column field="estado" header="Comparación" sortable style="min-width: 10rem">
              <template #body="slotProps">
                <Tag :value="slotProps.data.estado" :severity="getEstadoSeverity(slotProps.data.estado)"
                  :icon="getEstadoIcon(slotProps.data.estado)" />
              </template>
            </Column>

            <Column header="Acciones" style="min-width: 10rem">
              <template #body="slotProps">
                <div class="flex gap-2 justify-center">
                  <!-- Botón para realizar pago (solo si coincide y no está procesado) -->
                  <Button 
                    v-if="slotProps.data.estado === 'Coincide'" 
                    icon="pi pi-credit-card" 
                    severity="success" 
                    text 
                    rounded 
                    @click="realizarPago(slotProps.data)"
                    v-tooltip="'Realizar pago'" 
                  />
                  
                  <!-- Botón para pagos ya procesados -->
                  <Button 
                    v-else-if="slotProps.data.estado === 'Procesado'" 
                    icon="pi pi-check-circle" 
                    severity="info" 
                    text 
                    rounded 
                    disabled
                    v-tooltip="'Pago ya procesado'" 
                  />
                  
                  <!-- Botón para registros que no coinciden -->
                  <Button 
                    v-else
                    icon="pi pi-exclamation-triangle" 
                    severity="warning" 
                    text 
                    rounded 
                    disabled
                    v-tooltip="'No se puede procesar: ' + slotProps.data.estado" 
                  />
                  
                  <!-- Botón de ver detalles siempre disponible -->
                  <Button 
                    icon="pi pi-info-circle" 
                    severity="secondary" 
                    text 
                    rounded 
                    @click="verDetalles(slotProps.data)"
                    v-tooltip="'Ver detalles'" 
                  />
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

// Estadísticas computadas
const coincidencias = computed(() => extractedData.value.filter(item => item.estado === 'Coincide').length);
const noCoincidencias = computed(() => extractedData.value.filter(item => item.estado === 'No coincide').length);
const procesados = computed(() => extractedData.value.filter(item => item.estado === 'Procesado').length);

// Funciones de reseteo y diálogo
function resetSector() {
  loading.value = false;
  extractedData.value = [];
  selectedFile.value = null;
  globalFilterValue.value = '';
}

function openNew() {
  resetSector();
  sectorDialog.value = true;
}

function hideDialog() {
  sectorDialog.value = false;
  resetSector();
}

// Funciones para severidades de tags
function getEstadoSeverity(estado) {
  switch(estado) {
    case 'Coincide': return 'success';
    case 'No coincide': return 'danger';
    case 'Procesado': return 'info';
    default: return 'secondary';
  }
}

function getEstadoIcon(estado) {
  switch(estado) {
    case 'Coincide': return 'pi pi-check';
    case 'No coincide': return 'pi pi-times';
    case 'Procesado': return 'pi pi-check-circle';
    default: return 'pi pi-question';
  }
}

function getStatusSeverity(status) {
  switch (status) {
    case 'active': return 'success';
    case 'inactive': return 'secondary';
    case 'expired': return 'warning';
    case 'judicialized': return 'danger';
    case 'reprogramed': return 'info';
    case 'paid': return 'success';
    case 'canceled': return 'secondary';
    case 'daStandby': return 'contrast';
    default: return 'secondary';
  }
}

function getTipoPagoSeverity(tipoPago) {
  switch (tipoPago) {
    case 'Pago normal': return 'success';
    case 'Pago parcial': return 'warning';
    case 'Sin determinar': return 'secondary';
    default: return 'secondary';
  }
}

// Función para formatear moneda
function formatCurrency(amount, currency) {
  if (!amount) return '-';
  const symbol = currency === 'PEN' ? 'S/' : '$';
  return `${symbol} ${Number(amount).toLocaleString('es-PE', { minimumFractionDigits: 2 })}`;
}

// Función para realizar pago
function realizarPago(record) {
  // Validar que el registro tenga los datos necesarios
  if (!record.id_pago) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se puede procesar el pago. ID de factura no encontrado.',
      life: 4000
    });
    return;
  }

  if (record.estado !== 'Coincide') {
    toast.add({
      severity: 'warn',
      summary: 'Advertencia',
      detail: 'Solo se pueden procesar pagos que coincidan con la base de datos.',
      life: 4000
    });
    return;
  }

  selectedPaymentData.value = record;
  showPaymentDialog.value = true;
}

// Función para ver detalles
function verDetalles(record) {
  // Mostrar toast con detalles del procesamiento
  const detalles = record.detalle || 'Sin detalles disponibles';
  
  toast.add({
    severity: 'info',
    summary: `Detalles - ${record.invoice_number}`,
    detail: detalles,
    life: 8000
  });
  
  console.log('Detalles del registro:', {
    invoice_number: record.invoice_number,
    loan_number: record.loan_number,
    document: record.document,
    RUC_client: record.RUC_client,
    estado: record.estado,
    tipo_pago: record.tipo_pago,
    amount: record.amount,
    saldo: record.saldo,
    currency: record.currency,
    estimated_pay_date: record.estimated_pay_date,
    id_pago: record.id_pago,
    detalle: record.detalle,
    processed_at: record.processed_at,
    processed_type: record.processed_type,
    processed_amount: record.processed_amount
  });
}

// Función para manejar pago procesado
function onPaymentProcessed(data) {
  // Actualizar el estado del registro en la tabla
  const index = extractedData.value.findIndex(item => 
    item.id_pago === data.id_pago
  );
  
  if (index !== -1) {
    // Actualizar el estado del registro
    extractedData.value[index] = {
      ...extractedData.value[index],
      estado: 'Procesado',
      processed_at: new Date().toISOString(),
      processed_type: data.processed_type,
      processed_amount: data.processed_amount
    };

    toast.add({
      severity: 'success',
      summary: 'Estado Actualizado',
      detail: `Registro actualizado: ${data.processed_type === 'total' ? 'Pago Total' : 'Pago Parcial'} procesado`,
      life: 4000
    });
  }
  
  // Emitir evento para actualizar listas padre si es necesario
  emit('agregado');
  
  // Cerrar el diálogo
  showPaymentDialog.value = false;
}

function onPaymentCancelled() {
  showPaymentDialog.value = false;
  selectedPaymentData.value = {};
}

// Funciones para manejo de archivos
function handleFileSelect(event) {
  selectedFile.value = event.files[0];
  processFile();
}

function handleFileUpload() {
  processFile();
}

// Función principal para procesar archivo
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
      life: 2000 
    });
    
    const response = await axios.post('/payments/extraer', formData, { 
      headers: { 'Content-Type': 'multipart/form-data' } 
    });

    if (response.data.success && response.data.data) {
      extractedData.value = response.data.data;
      
      // Validar que los registros tengan los campos necesarios
      const validRecords = extractedData.value.filter(record => record.id_pago);
      const invalidRecords = extractedData.value.length - validRecords.length;
      
      if (invalidRecords > 0) {
        toast.add({
          severity: 'warn',
          summary: 'Advertencia',
          detail: `${invalidRecords} registros sin ID de factura no podrán ser procesados`,
          life: 4000,
        });
      }
      
      toast.add({
        severity: 'success',
        summary: 'Procesamiento completado',
        detail: `${extractedData.value.length} registros encontrados. ${coincidencias.value} coincidencias, ${validRecords.length} procesables.`,
        life: 4000,
      });
    } else {
      throw new Error(response.data.message || 'Formato de respuesta inválido');
    }
  } catch (error) {
    console.error('Error al procesar el archivo:', error);
    
    let errorMessage = 'No se pudo procesar el archivo. Verifica el formato.';
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
    }
    
    toast.add({ 
      severity: 'error', 
      summary: 'Error de procesamiento', 
      detail: errorMessage, 
      life: 4000 
    });
    extractedData.value = [];
  } finally {
    loading.value = false;
  }
}

// Función de búsqueda global (placeholder)
function onGlobalSearch() {
  // TODO: implementar búsqueda global si es necesario
  console.log('Búsqueda global:', globalFilterValue.value);
}
</script>

<style scoped>
.p-datatable-sm .p-datatable-tbody > tr > td {
  padding: 0.5rem;
}

.font-mono {
  font-family: 'Courier New', monospace;
}
</style>