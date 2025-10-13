<template>
  <Dialog v-model:visible="dialogVisible" :style="{ width: '95vw', maxWidth: '1400px' }" 
    :modal="true" :closable="true" @update:visible="onClose" :pt="{ root: 'rounded-xl' }">
    
    <!-- Header personalizado -->
    <template #header>
      <div class="flex items-center gap-3">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-3 rounded-lg shadow-md">
          <i class="pi pi-money-bill text-white text-2xl"></i>
        </div>
        <div>
          <h2 class="text-2xl font-bold text-gray-800 m-0">Procesar Pago de Factura</h2>
          <p class="text-sm text-gray-500 m-0 mt-1">Revisión y confirmación de pago</p>
        </div>
      </div>
    </template>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="text-center">
        <ProgressSpinner strokeWidth="4" />
        <p class="text-gray-600 mt-4">Cargando información...</p>
      </div>
    </div>

    <div v-else class="flex flex-col gap-6">
      <!-- Alert del tipo de pago -->
      <div v-if="paymentData?.tipo_pago" class="rounded-xl border-l-4 p-4 shadow-sm"
        :class="getPaymentTypeAlertClass(paymentData.tipo_pago)">
        <div class="flex items-start gap-3">
          <i :class="getPaymentTypeIcon(paymentData.tipo_pago)" class="text-xl mt-0.5"></i>
          <div>
            <p class="font-semibold text-base mb-1">{{ paymentData.tipo_pago }}</p>
            <p class="text-sm opacity-90">{{ getPaymentTypeDescription(paymentData.tipo_pago) }}</p>
          </div>
        </div>
      </div>

      <!-- Información de Pago Adelantado -->
      <div v-if="isPagoAdelantado" class="rounded-xl border-l-4 border-yellow-400 bg-yellow-50 p-4">
        <div class="flex items-center gap-3">
          <i class="pi pi-clock text-yellow-600 text-xl"></i>
          <div>
            <p class="font-semibold text-yellow-800">Pago Adelantado</p>
            <p class="text-sm text-yellow-700">
              Este pago se realiza antes de la fecha de vencimiento. 
              {{ getMensajePagoAdelantado() }}
            </p>
          </div>
        </div>
      </div>

      <!-- Cards de Información Principal -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Card Factura -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3 mb-3">
            <div class="bg-blue-100 p-2 rounded-lg">
              <i class="pi pi-file text-blue-600 text-lg"></i>
            </div>
            <span class="text-sm font-semibold text-gray-600">FACTURA</span>
          </div>
          <p class="text-xl font-bold text-gray-900 mb-1">{{ invoiceData?.invoice_number || '-' }}</p>
          <p class="text-sm text-gray-500">{{ invoiceData?.loan_number || '-' }}</p>
        </div>

        <!-- Card Cliente -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3 mb-3">
            <div class="bg-green-100 p-2 rounded-lg">
              <i class="pi pi-user text-green-600 text-lg"></i>
            </div>
            <span class="text-sm font-semibold text-gray-600">CLIENTE</span>
          </div>
          <p class="text-base font-bold text-gray-900 mb-1">{{ invoiceData?.razonSocial || '-' }}</p>
          <p class="text-sm text-gray-500">RUC: {{ invoiceData?.ruc_cliente || '-' }}</p>
        </div>

        <!-- Card Fechas -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3 mb-3">
            <div class="bg-purple-100 p-2 rounded-lg">
              <i class="pi pi-calendar text-purple-600 text-lg"></i>
            </div>
            <span class="text-sm font-semibold text-gray-600">FECHAS</span>
          </div>
          <p class="text-sm font-semibold text-gray-900">Pago: {{ formatDate(paymentData?.estimated_pay_date) }}</p>
          <p class="text-sm text-gray-500">Vence: {{ formatDate(invoiceData?.fechaPago) }}</p>
        </div>

        <!-- Card Estado -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3 mb-3">
            <div class="bg-orange-100 p-2 rounded-lg">
              <i class="pi pi-info-circle text-orange-600 text-lg"></i>
            </div>
            <span class="text-sm font-semibold text-gray-600">ESTADO</span>
          </div>
          <Tag :value="invoiceData?.estado || 'active'" 
            :severity="getStatusSeverity(invoiceData?.estado)" 
            class="font-semibold" />
          <p class="text-xs text-gray-500 mt-1">{{ getEstadoDescripcion() }}</p>
        </div>
      </div>

      <!-- Cards de Montos -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-5 border border-blue-200 shadow-sm">
          <p class="text-sm font-semibold text-blue-800 mb-2">MONTO TOTAL FACTURA</p>
          <p class="text-2xl font-bold text-blue-700">
            {{ formatCurrency(invoiceData?.montoFactura, invoiceData?.moneda) }}
          </p>
        </div>
        
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-5 border border-green-200 shadow-sm">
          <p class="text-sm font-semibold text-green-800 mb-2">MONTO A PAGAR</p>
          <p class="text-2xl font-bold text-green-700">
            {{ formatCurrency(paymentData?.saldo, invoiceData?.moneda) }}
          </p>
          <p class="text-xs text-green-600 mt-1">
            {{ getPorcentajePago() }}% del total
          </p>
        </div>
        
        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-5 border border-orange-200 shadow-sm">
          <p class="text-sm font-semibold text-orange-800 mb-2">RECAUDACIÓN (5%)</p>
          <p class="text-2xl font-bold text-orange-600">
            {{ formatCurrency(totalRecaudacionEstimada, invoiceData?.moneda) }}
          </p>
        </div>

        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-5 border border-purple-200 shadow-sm">
          <p class="text-sm font-semibold text-purple-800 mb-2">NETO A INVERSIONISTAS</p>
          <p class="text-2xl font-bold text-purple-700">
            {{ formatCurrency(totalNetoInversionistas, invoiceData?.moneda) }}
          </p>
        </div>
      </div>

      <!-- Tabla de Inversionistas -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="bg-blue-600 p-2 rounded-lg">
                <i class="pi pi-users text-white"></i>
              </div>
              <div>
                <h3 class="text-lg font-bold text-gray-800">Distribución de Pagos</h3>
                <p class="text-sm text-gray-600">Desglose por inversionista</p>
              </div>
            </div>
            <div class="flex items-center gap-4">
              <Tag :value="`${investments.length} inversionista${investments.length !== 1 ? 's' : ''}`" 
                severity="info" class="px-4 py-2" />
              <div class="text-right">
                <p class="text-sm font-semibold text-gray-600">Total a distribuir</p>
                <p class="text-lg font-bold text-green-600">
                  {{ formatCurrency(paymentData?.saldo, invoiceData?.moneda) }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <DataTable :value="investments" stripedRows class="p-datatable-sm" 
          :paginator="investments.length > 8" :rows="8" responsiveLayout="scroll"
          :loading="loading">
          
          <Column field="inversionista" header="Inversionista" :sortable="true" style="min-width: 200px">
            <template #body="slotProps">
              <div class="flex items-center gap-2">
                <Avatar :label="getInitials(slotProps.data.inversionista)" size="small" 
                  class="bg-blue-100 text-blue-800 font-semibold" />
                <div>
                  <span class="font-medium text-gray-900">{{ slotProps.data.inversionista }}</span>
                  <div class="text-xs text-gray-500">{{ slotProps.data.document }}</div>
                </div>
              </div>
            </template>
          </Column>

          <Column field="amount" header="Capital" :sortable="true" style="min-width: 120px">
            <template #body="slotProps">
              <span class="font-semibold text-blue-600">
                {{ formatCurrency(slotProps.data.amount, slotProps.data.currency) }}
              </span>
            </template>
          </Column>

          <Column field="return" header="Retorno Bruto" :sortable="true" style="min-width: 130px">
            <template #body="slotProps">
              <span class="font-semibold text-green-600">
                {{ formatCurrency(slotProps.data.return, slotProps.data.currency) }}
              </span>
            </template>
          </Column>

          <Column header="Retorno Pendiente" style="min-width: 140px">
            <template #body="slotProps">
              <span class="font-semibold" :class="getRetornoPendienteClass(slotProps.data)">
                {{ formatCurrency(calculateRetornoPendiente(slotProps.data), slotProps.data.currency) }}
              </span>
            </template>
          </Column>

          <Column field="rate" header="Tasa" :sortable="true" style="min-width: 90px">
            <template #body="slotProps">
              <Tag :value="`${slotProps.data.rate}%`" 
                :severity="getTasaSeverity(slotProps.data.rate)" 
                class="font-semibold" />
            </template>
          </Column>

          <Column header="Estado" style="min-width: 120px">
            <template #body="slotProps">
              <Tag :value="slotProps.data.status" 
                :severity="getInvestmentStatusSeverity(slotProps.data.status)"
                class="capitalize" />
            </template>
          </Column>

          <Column header="Proporción" style="min-width: 100px">
            <template #body="slotProps">
              <div class="text-center">
                <span class="font-semibold text-gray-700">{{ calculateProporcion(slotProps.data) }}%</span>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                  <div class="bg-blue-600 h-2 rounded-full" 
                    :style="{ width: `${calculateProporcion(slotProps.data)}%` }"></div>
                </div>
              </div>
            </template>
          </Column>

          <Column header="Monto Estimado" style="min-width: 140px">
            <template #body="slotProps">
              <div class="bg-gradient-to-r from-purple-50 to-purple-100 px-3 py-2 rounded-lg border border-purple-200">
                <div class="font-bold text-purple-700 text-sm">
                  {{ formatCurrency(calculateMontoEstimado(slotProps.data), slotProps.data.currency) }}
                </div>
                <div class="text-xs text-gray-500 mt-0.5">Estimado</div>
              </div>
            </template>
          </Column>
        </DataTable>
      </div>

      <!-- Resumen y Validaciones -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Resumen de Distribución -->
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl border border-gray-200 p-6 shadow-sm">
          <h4 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="pi pi-chart-bar text-blue-600"></i>
            Resumen de Distribución
          </h4>
          <div class="space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-sm font-semibold text-gray-600">Total a Pagar:</span>
              <span class="font-bold text-lg text-green-600">
                {{ formatCurrency(paymentData?.saldo, invoiceData?.moneda) }}
              </span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm font-semibold text-gray-600">Recaudación Estimada (5%):</span>
              <span class="font-bold text-lg text-orange-600">
                {{ formatCurrency(totalRecaudacionEstimada, invoiceData?.moneda) }}
              </span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm font-semibold text-gray-600">Neto a Inversionistas:</span>
              <span class="font-bold text-lg text-purple-600">
                {{ formatCurrency(totalNetoInversionistas, invoiceData?.moneda) }}
              </span>
            </div>
            <div class="pt-3 border-t border-gray-200">
              <div class="flex justify-between items-center">
                <span class="text-sm font-semibold text-gray-600">Inversionistas Beneficiados:</span>
                <Tag :value="investmentsBeneficiados" severity="success" />
              </div>
            </div>
          </div>
        </div>

        <!-- Validaciones -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
          <h4 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="pi pi-shield-check text-green-600"></i>
            Validaciones
          </h4>
          <div class="space-y-3">
            <div class="flex items-center gap-3 p-3 rounded-lg" 
              :class="validationClasses.montoDisponible">
              <i :class="validationIcons.montoDisponible" class="text-lg"></i>
              <div>
                <p class="font-semibold text-sm">Monto Disponible</p>
                <p class="text-xs opacity-75">{{ validationMessages.montoDisponible }}</p>
              </div>
            </div>
            
            <div class="flex items-center gap-3 p-3 rounded-lg" 
              :class="validationClasses.inversionistasActivos">
              <i :class="validationIcons.inversionistasActivos" class="text-lg"></i>
              <div>
                <p class="font-semibold text-sm">Inversionistas Activos</p>
                <p class="text-xs opacity-75">{{ validationMessages.inversionistasActivos }}</p>
              </div>
            </div>
            
            <div class="flex items-center gap-3 p-3 rounded-lg" 
              :class="validationClasses.fechas">
              <i :class="validationIcons.fechas" class="text-lg"></i>
              <div>
                <p class="font-semibold text-sm">Fechas</p>
                <p class="text-xs opacity-75">{{ validationMessages.fechas }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-between items-center w-full pt-4 border-t border-gray-200">
        <div class="flex items-center gap-2 text-gray-500">
          <i class="pi pi-info-circle"></i>
          <small class="italic">{{ getMensajeFinal() }}</small>
        </div>
        <div class="flex gap-3">
          <Button label="Cancelar" icon="pi pi-times" severity="secondary" outlined
            @click="onCancel" class="px-6" />
          <Button label="Procesar Pago" icon="pi pi-check-circle" severity="success" 
            @click="procesarPago" :loading="processing" :disabled="!validacionesPasadas"
            class="px-6 shadow-md" />
        </div>
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import Avatar from 'primevue/avatar';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  paymentData: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['update:visible', 'payment-processed', 'cancelled']);
const toast = useToast();

const dialogVisible = ref(false);
const loading = ref(false);
const processing = ref(false);
const invoiceData = ref(null);
const investments = ref([]);

// Computed properties
const isPagoAdelantado = computed(() => {
  if (!invoiceData.value?.fechaPago || !props.paymentData?.estimated_pay_date) return false;
  
  const dueDate = parseDateDDMMYYYY(invoiceData.value.fechaPago);
  const payDate = parseDateDDMMYYYY(props.paymentData.estimated_pay_date);
  
  if (!dueDate || !payDate) return false;
  
  return payDate < dueDate;
});

const totalFactura = computed(() => {
  return parseFloat(invoiceData.value?.montoFactura || 0);
});

const totalInvertido = computed(() => {
  return investments.value.reduce((sum, inv) => sum + parseFloat(inv.amount || 0), 0);
});

const totalRetorno = computed(() => {
  return investments.value.reduce((sum, inv) => sum + parseFloat(inv.return || 0), 0);
});

const totalRecaudacionEstimada = computed(() => {
  const montoPago = parseFloat(props.paymentData?.saldo || 0);
  const tipoPago = mapPaymentType(props.paymentData?.tipo_pago);
  
  if (tipoPago === 'intereses') {
    return montoPago * 0.05;
  } else {
    // Para pagos parciales/totales, estimamos que el 5% se aplica solo a la parte de retorno
    const proporcionRetorno = totalFactura.value > 0 ? (totalRetorno.value / totalFactura.value) : 0;
    return (montoPago * proporcionRetorno) * 0.05;
  }
});

const totalNetoInversionistas = computed(() => {
  const montoPago = parseFloat(props.paymentData?.saldo || 0);
  return montoPago - totalRecaudacionEstimada.value;
});

const investmentsBeneficiados = computed(() => {
  return investments.value.filter(inv => 
    inv.status === 'active' || inv.status === 'pending'
  ).length;
});

const validacionesPasadas = computed(() => {
  const montoDisponible = parseFloat(props.paymentData?.saldo || 0) <= totalFactura.value;
  const inversionistasActivos = investmentsBeneficiados.value > 0;
  const fechas = props.paymentData?.estimated_pay_date && invoiceData.value?.fechaPago;
  
  return montoDisponible && inversionistasActivos && fechas;
});

// Validaciones computadas
const validationClasses = computed(() => {
  const montoDisponible = parseFloat(props.paymentData?.saldo || 0) <= totalFactura.value;
  const inversionistasActivos = investmentsBeneficiados.value > 0;
  const fechas = props.paymentData?.estimated_pay_date && invoiceData.value?.fechaPago;

  return {
    montoDisponible: montoDisponible ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200',
    inversionistasActivos: inversionistasActivos ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200',
    fechas: fechas ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200'
  };
});

const validationIcons = computed(() => {
  return {
    montoDisponible: validationClasses.value.montoDisponible.includes('green') ? 'pi pi-check-circle text-green-600' : 'pi pi-times-circle text-red-600',
    inversionistasActivos: validationClasses.value.inversionistasActivos.includes('green') ? 'pi pi-check-circle text-green-600' : 'pi pi-times-circle text-red-600',
    fechas: validationClasses.value.fechas.includes('green') ? 'pi pi-check-circle text-green-600' : 'pi pi-exclamation-triangle text-yellow-600'
  };
});

const validationMessages = computed(() => {
  const montoPago = parseFloat(props.paymentData?.saldo || 0);
  
  return {
    montoDisponible: montoPago <= totalFactura.value 
      ? `Monto dentro del límite (${formatCurrency(totalFactura.value, invoiceData.value?.moneda)})`
      : `Monto excede el total de la factura`,
    inversionistasActivos: investmentsBeneficiados.value > 0
      ? `${investmentsBeneficiados.value} inversionista(s) activo(s)`
      : 'No hay inversionistas activos',
    fechas: props.paymentData?.estimated_pay_date && invoiceData.value?.fechaPago
      ? `Pago: ${formatDate(props.paymentData.estimated_pay_date)}, Vence: ${formatDate(invoiceData.value.fechaPago)}`
      : 'Fechas incompletas'
  };
});

// Watchers
watch(() => props.visible, (newVal) => {
  dialogVisible.value = newVal;
  if (newVal && props.paymentData?.id_pago) {
    loadInvoiceData();
  }
});

// Métodos
async function loadInvoiceData() {
  if (!props.paymentData?.id_pago) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se encontró el ID de la factura', life: 3000 });
    return;
  }

  loading.value = true;
  try {
    const response = await axios.get(`/invoices/${props.paymentData.id_pago}`);
    
    if (response.data?.data) {
      invoiceData.value = response.data.data;
      investments.value = response.data.data.investments || [];
      
      if (investments.value.length === 0) {
        toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'No hay inversiones activas', life: 4000 });
      }
    }
  } catch (error) {
    console.error('Error al cargar datos:', error);
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: 'Error al cargar la información de la factura', 
      life: 4000 
    });
    onClose();
  } finally {
    loading.value = false;
  }
}

// Métodos de utilidad
function formatCurrency(amount, currency) {
  if (!amount && amount !== 0) return '-';
  const symbol = currency === 'PEN' || currency === 'S/' ? 'S/' : 'US$';
  return `${symbol} ${Number(amount).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function formatDate(dateString) {
  if (!dateString) return '-';
  try {
    // Si ya está en formato legible, devolverlo tal cual
    if (dateString.includes('-')) {
      return dateString;
    }
    // Si está en formato DD-MM-YYYY, convertirlo
    const date = parseDateDDMMYYYY(dateString);
    return date ? date.toLocaleDateString('es-PE') : dateString;
  } catch {
    return dateString;
  }
}

function parseDateDDMMYYYY(dateString) {
  if (!dateString) return null;
  try {
    const parts = dateString.split('-');
    if (parts.length === 3) {
      const day = parseInt(parts[0], 10);
      const month = parseInt(parts[1], 10) - 1;
      const year = parseInt(parts[2], 10);
      return new Date(year, month, day);
    }
    return new Date(dateString);
  } catch {
    return null;
  }
}

function getInitials(name) {
  if (!name) return '?';
  return name.split(' ').map(n => n.charAt(0)).join('').toUpperCase().substring(0, 2);
}

// Métodos para tipos de pago
function getPaymentTypeAlertClass(tipo) {
  if (!tipo) return 'bg-gray-50 border-gray-400 text-gray-800';
  
  switch (tipo) {
    case 'Paga toda la factura': 
      return 'bg-green-50 border-green-400 text-green-800';
    case 'Pago parcial': 
      return 'bg-yellow-50 border-yellow-400 text-yellow-800';
    case 'Pago de intereses': 
      return 'bg-blue-50 border-blue-400 text-blue-800';
    default: 
      return 'bg-gray-50 border-gray-400 text-gray-800';
  }
}

function getPaymentTypeIcon(tipo) {
  if (!tipo) return 'pi pi-info-circle text-gray-600';
  
  switch (tipo) {
    case 'Paga toda la factura': return 'pi pi-check-circle text-green-600';
    case 'Pago parcial': return 'pi pi-exclamation-triangle text-yellow-600';
    case 'Pago de intereses': return 'pi pi-info-circle text-blue-600';
    default: return 'pi pi-info-circle text-gray-600';
  }
}

function getPaymentTypeDescription(tipo) {
  if (!tipo) return 'Tipo de pago no especificado';
  
  switch (tipo) {
    case 'Paga toda la factura': 
      return 'Este pago liquidará completamente la factura';
    case 'Pago parcial': 
      return 'Solo se pagará una parte de la factura';
    case 'Pago de intereses': 
      return 'Este es un pago de intereses. No afecta el saldo principal';
    default: 
      return 'Tipo de pago no especificado';
  }
}

function getTipoPagoSeverity(tipoPago) {
  if (!tipoPago) return 'secondary';
  
  switch (tipoPago) {
    case 'Paga toda la factura': return 'success';
    case 'Pago parcial': return 'warn';
    case 'Pago de intereses': return 'info';
    default: return 'secondary';
  }
}

function mapPaymentType(excelType) {
  if (!excelType) return 'partial';
  
  const mapping = {
    'Pago de intereses': 'intereses',
    'Pago parcial': 'partial',
    'Paga toda la factura': 'total',
    'Reprogramación': 'reprogramacion'
  };
  return mapping[excelType] || 'partial';
}

// Métodos de cálculo
function calculateRetornoPendiente(investment) {
  // En tu estructura actual, no hay return_efectivizado, así que todo está pendiente
  return parseFloat(investment.return || 0);
}

function calculateProporcion(investment) {
  const totalInversion = parseFloat(investment.amount || 0) + parseFloat(investment.return || 0);
  const totalGeneral = totalFactura.value;
  return totalGeneral > 0 ? ((totalInversion / totalGeneral) * 100).toFixed(1) : 0;
}

function calculateMontoEstimado(investment) {
  const proporcion = calculateProporcion(investment) / 100;
  const montoPago = parseFloat(props.paymentData?.saldo || 0);
  return (montoPago * proporcion).toFixed(2);
}

function getRetornoPendienteClass(investment) {
  const pendiente = calculateRetornoPendiente(investment);
  return pendiente > 0 ? 'text-orange-600' : 'text-green-600';
}

function getTasaSeverity(tasa) {
  if (!tasa) return 'info';
  if (tasa >= 15) return 'danger';
  if (tasa >= 10) return 'warning';
  return 'info';
}

function getInvestmentStatusSeverity(status) {
  if (!status) return 'secondary';
  
  const statusMap = {
    'active': 'success',
    'pending': 'warning',
    'paid': 'info',
    'reprogramed': 'secondary',
    'inactive': 'danger'
  };
  return statusMap[status] || 'secondary';
}

function getStatusSeverity(status) {
  if (!status) return 'secondary';
  
  const statusMap = {
    'active': 'success',
    'pending': 'warning',
    'paid': 'info',
    'overdue': 'danger',
    'partial': 'info'
  };
  return statusMap[status] || 'secondary';
}

function getMensajePagoAdelantado() {
  if (!isPagoAdelantado.value) return '';
  
  try {
    const dueDate = parseDateDDMMYYYY(invoiceData.value.fechaPago);
    const payDate = parseDateDDMMYYYY(props.paymentData.estimated_pay_date);
    
    if (!dueDate || !payDate) return 'Pago realizado antes del vencimiento.';
    
    const diffDays = Math.ceil((dueDate - payDate) / (1000 * 60 * 60 * 24));
    
    return `Se realiza ${diffDays} día(s) antes del vencimiento.`;
  } catch {
    return 'Pago realizado antes del vencimiento.';
  }
}

function getPorcentajePago() {
  const montoPago = parseFloat(props.paymentData?.saldo || 0);
  return totalFactura.value > 0 ? ((montoPago / totalFactura.value) * 100).toFixed(1) : 0;
}

function getEstadoDescripcion() {
  const paidAmount = parseFloat(invoiceData.value?.paid_amount || 0);
  const total = totalFactura.value;
  
  if (paidAmount >= total) return 'Factura completamente pagada';
  if (paidAmount > 0) return `Factura parcialmente pagada (${((paidAmount / total) * 100).toFixed(1)}%)`;
  return 'Factura pendiente de pago';
}

function getMensajeFinal() {
  const tipoPago = mapPaymentType(props.paymentData?.tipo_pago);
  switch (tipoPago) {
    case 'intereses': return 'Se procesará solo el pago de intereses con el 5% de recaudación';
    case 'total': return 'Se liquidará completamente la factura';
    case 'partial': return 'Se aplicará un pago parcial proporcional';
    case 'reprogramacion': return 'Se reprogramará el pago de la factura';
    default: return 'Verifique los montos antes de procesar';
  }
}

// Método principal de procesamiento
async function procesarPago() {
  if (!validacionesPasadas.value) {
    toast.add({
      severity: 'error',
      summary: 'Validación Fallida',
      detail: 'Corrija las validaciones antes de continuar',
      life: 4000
    });
    return;
  }

  processing.value = true;
  try {
    const payType = mapPaymentType(props.paymentData.tipo_pago);
    
    const paymentPayload = {
      amount_to_be_paid: parseFloat(props.paymentData.saldo),
      pay_type: payType,
      pay_date: props.paymentData.estimated_pay_date, // Usar la fecha del Excel
    };

    console.log('Enviando pago:', paymentPayload);

    const response = await axios.post(
      `/payments/${props.paymentData.id_pago}`,
      paymentPayload
    );
    
    if (response.data) {
      toast.add({
        severity: 'success',
        summary: 'Pago Procesado',
        detail: `Pago registrado correctamente para ${investments.value.length} inversionista(s)`,
        life: 5000
      });

      emit('payment-processed', {
        payment_id: response.data.payment?.id,
        invoice_id: props.paymentData.id_pago,
        processed_type: payType,
        processed_amount: props.paymentData.saldo
      });
      
      onClose();
    }
  } catch (error) {
    console.error('Error al procesar pago:', error);
    
    let errorMessage = 'No se pudo procesar el pago';
    if (error.response?.data?.error) {
      errorMessage = error.response.data.error;
    } else if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
    }
    
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: errorMessage,
      life: 6000
    });
  } finally {
    processing.value = false;
  }
}

function onCancel() {
  emit('cancelled');
  onClose();
}

function onClose() {
  dialogVisible.value = false;
  emit('update:visible', false);
  
  // Resetear estado después de cerrar
  setTimeout(() => {
    invoiceData.value = null;
    investments.value = [];
  }, 300);
}
</script>