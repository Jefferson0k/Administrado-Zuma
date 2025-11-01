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
          <h2 class="text-2xl font-bold m-0">Procesar Pago de Factura</h2>
          <p class="text-sm m-0 mt-1">Revisión y confirmación de pago</p>
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
      <div class="rounded-xl border-l-4 p-4 shadow-sm"
        :class="isPagoAdelantado ? 'bg-orange-50 border-orange-400 text-orange-800' : getPaymentTypeAlertClass(paymentData?.tipo_pago)">
        <div class="flex items-start gap-3">
          <i :class="isPagoAdelantado ? 'pi pi-clock text-orange-600' : getPaymentTypeIcon(paymentData?.tipo_pago)" class="text-xl mt-0.5"></i>
          <div>
            <p class="font-semibold text-base mb-1">
              {{ isPagoAdelantado ? 'PAGO ADELANTADO' : paymentData?.tipo_pago }}
            </p>
            <p class="text-sm opacity-90">{{ getPaymentTypeDescription(paymentData?.tipo_pago) }}</p>
          </div>
        </div>
      </div>

      <!-- Información de Pago Adelantado -->
      <div v-if="isPagoAdelantado" class="rounded-xl border-l-4 border-orange-400 bg-orange-50 p-4">
        <div class="flex items-center gap-3">
          <i class="pi pi-exclamation-triangle text-orange-600 text-xl"></i>
          <div>
            <p class="font-semibold text-orange-800">Pago Adelantado Detectado</p>
            <p class="text-sm text-orange-700">
              Este pago se realiza <strong>{{ diasAdelantados }} días antes</strong> del vencimiento. 
              El retorno se recalcula proporcionalmente y se aplicará la recaudación del 5%.
            </p>
            <p class="text-xs text-orange-600 mt-1">
              <strong>Fórmula:</strong> I = ((1+tasa)^(días_invertidos/30)-1) × Capital. Luego se aplica recaudación del 5% sobre el retorno.
            </p>
          </div>
        </div>
      </div>

      <!-- Cards de Información Principal -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
          <p v-if="isPagoAdelantado" class="text-xs text-orange-600 mt-1 font-semibold">
            ⚠️ Días adelantados: {{ diasAdelantados }}
          </p>
        </div>
      </div>

      <!-- Cards de Montos -->
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-5 border border-blue-200 shadow-sm">
          <p class="text-sm font-semibold text-blue-800 mb-2">MONTO TOTAL FACTURA</p>
          <p class="text-2xl font-bold text-blue-700">
            {{ formatCurrency(invoiceData?.montoFactura, invoiceData?.moneda) }}
          </p>
        </div>
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-5 border border-green-200 shadow-sm">
      <p class="text-sm font-semibold text-green-800 mb-2">MONTO A PAGAR (EXCEL)</p>
      <p class="text-2xl font-bold text-green-700">
        {{ formatCurrency(montoAPagarDelExcel, invoiceData?.moneda) }}
      </p>
      <p class="text-xs text-green-600 mt-1">
        Monto especificado en el archivo
      </p>
    </div>
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-5 border border-purple-200 shadow-sm">
          <p class="text-sm font-semibold text-purple-800 mb-2">INVERSIÓN TERCEROS</p>
          <p class="text-2xl font-bold text-purple-700">
            {{ formatCurrency(totalInversionTerceros, invoiceData?.moneda) }}
          </p>
          <p class="text-xs text-purple-600 mt-1">
            Capital + Retorno bruto
          </p>
        </div>
        
        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-5 border border-orange-200 shadow-sm">
          <p class="text-sm font-semibold text-orange-800 mb-2">MONTO ASUMIDO ZUMA</p>
          <p class="text-2xl font-bold text-orange-700">
            {{ formatCurrency(invoiceData?.montoAsumidoZuma, invoiceData?.moneda) }}
          </p>
          <p class="text-xs text-orange-600 mt-1">
            Asumido por Zuma
          </p>
        </div>

        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-5 border border-yellow-200 shadow-sm">
          <p class="text-sm font-semibold text-yellow-800 mb-2">MONTO DISPONIBLE ZUMA</p>
          <p class="text-2xl font-bold text-yellow-700">
            {{ formatCurrency(invoiceData?.montoDisponible, invoiceData?.moneda) }}
          </p>
          <p class="text-xs text-yellow-600 mt-1">
            También asumido por Zuma
          </p>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-5 border border-green-200 shadow-sm">
          <p class="text-sm font-semibold text-green-800 mb-2">MONTO A PAGAR</p>
          <p class="text-2xl font-bold text-green-700">
            {{ formatCurrency(invoiceData?.montoFactura, invoiceData?.moneda) }}
          </p>
          <p class="text-xs text-green-600 mt-1">
            Monto total de la factura
          </p>
        </div>
      </div>

      <!-- Información específica para Pago Adelantado -->
      <div v-if="isPagoAdelantado" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-5 border border-blue-200 shadow-sm">
          <p class="text-sm font-semibold text-blue-800 mb-2">RETORNO COMPLETO (Vencimiento)</p>
          <p class="text-2xl font-bold text-blue-700">
            {{ formatCurrency(totalRetornoCompleto, invoiceData?.moneda) }}
          </p>
          <p class="text-xs text-blue-600 mt-1">
            Retorno que se pagaría al vencimiento ({{ formatDate(invoiceData?.fechaPago) }})
          </p>
        </div>

        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-5 border border-orange-200 shadow-sm">
          <p class="text-sm font-semibold text-orange-800 mb-2">RETORNO RECALCULADO</p>
          <p class="text-2xl font-bold text-orange-700">
            {{ formatCurrency(totalRetornoBruto, invoiceData?.moneda) }}
          </p>
          <p class="text-xs text-orange-600 mt-1">
            Retorno reducido por pago adelantado ({{ diasAdelantados }} días antes)
          </p>
        </div>
      </div>

      <!-- Card de Recaudación - SIEMPRE visible cuando es adelantado -->
      <div v-if="isPagoAdelantado" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-5 border border-red-200 shadow-sm">
          <p class="text-sm font-semibold text-red-800 mb-2">RECAUDACIÓN TOTAL (5%)</p>
          <p class="text-2xl font-bold text-red-600">
            {{ formatCurrency(totalRecaudacionEstimada, invoiceData?.moneda) }}
          </p>
          <p class="text-xs text-red-600 mt-1">
            5% sobre retornos recalculados ({{ formatCurrency(totalRetornoBruto, invoiceData?.moneda) }})
          </p>
        </div>

        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl p-5 border border-indigo-200 shadow-sm">
          <p class="text-sm font-semibold text-indigo-800 mb-2">RETORNO NETO TOTAL</p>
          <p class="text-2xl font-bold text-indigo-700">
            {{ formatCurrency(totalRetornoNeto, invoiceData?.moneda) }}
          </p>
          <p class="text-xs text-indigo-600 mt-1">
            Después de recaudación del 5%
          </p>
        </div>
      </div>

      <!-- Card de Recaudación para Pago de Intereses -->
      <div v-else-if="isPagoIntereses" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-5 border border-red-200 shadow-sm">
          <p class="text-sm font-semibold text-red-800 mb-2">RECAUDACIÓN TOTAL (5%)</p>
          <p class="text-2xl font-bold text-red-600">
            {{ formatCurrency(totalRecaudacionEstimada, invoiceData?.moneda) }}
          </p>
          <p class="text-xs text-red-600 mt-1">
            5% sobre retornos brutos ({{ formatCurrency(totalRetornoBruto, invoiceData?.moneda) }})
          </p>
        </div>

        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl p-5 border border-indigo-200 shadow-sm">
          <p class="text-sm font-semibold text-indigo-800 mb-2">RETORNO NETO TOTAL</p>
          <p class="text-2xl font-bold text-indigo-700">
            {{ formatCurrency(totalRetornoNeto, invoiceData?.moneda) }}
          </p>
          <p class="text-xs text-indigo-600 mt-1">
            Después de recaudación del 5%
          </p>
        </div>
      </div>

      <!-- Información de Pago Parcial -->
      <div v-if="isPagoParcial" class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-5 border border-orange-200 shadow-sm">
        <div class="flex items-center gap-3">
          <i class="pi pi-chart-pie text-orange-600 text-xl"></i>
          <div>
            <p class="font-semibold text-orange-800">Pago Parcial</p>
            <p class="text-sm text-orange-700">
              Se distribuirá el pago proporcionalmente entre los inversionistas según su capital.
              Monto a distribuir: {{ formatCurrency(montoPagoParcial, invoiceData?.moneda) }}
            </p>
          </div>
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
                  {{ formatCurrency(totalCapitalMasRetornoNeto, invoiceData?.moneda) }}
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

          <!-- Columna para días transcurridos en pago adelantado -->
          <Column v-if="isPagoAdelantado" header="Días Invertidos" :sortable="true" style="min-width: 120px">
            <template #body="slotProps">
              <div>
                <span class="font-semibold text-gray-700">
                  {{ calcularDiasInvertidos(slotProps.data) }} días
                </span>
                <div class="text-xs text-gray-500 mt-1">
                  Desde {{ formatDate(slotProps.data.creacion) }}
                </div>
              </div>
            </template>
          </Column>

          <Column header="Retorno Bruto" :sortable="true" style="min-width: 130px">
            <template #body="slotProps">
              <div>
                <span class="font-semibold" :class="isPagoAdelantado ? 'text-orange-600' : 'text-gray-600'">
                  {{ formatCurrency(calculateRetornoBruto(slotProps.data), slotProps.data.currency) }}
                </span>
                <div v-if="isPagoAdelantado" class="text-xs text-gray-500 mt-1">
                  <div class="text-orange-600 font-semibold">Recalculado (días invertidos)</div>
                  <div>Original: {{ formatCurrency(slotProps.data.return, slotProps.data.currency) }}</div>
                </div>
                <div v-else class="text-xs text-gray-500 mt-1">
                  Antes de recaudación
                </div>
              </div>
            </template>
          </Column>

          <Column header="Recaudación 5%" :sortable="true" style="min-width: 130px">
            <template #body="slotProps">
              <div>
                <span class="font-semibold text-red-600">
                  -{{ formatCurrency(calculateRecaudacion(slotProps.data), slotProps.data.currency) }}
                </span>
                <div class="text-xs text-gray-500 mt-1">
                  5% del retorno {{ isPagoAdelantado ? 'recalculado' : 'bruto' }}
                </div>
              </div>
            </template>
          </Column>

          <Column header="Retorno Neto" :sortable="true" style="min-width: 130px">
            <template #body="slotProps">
              <div>
                <span class="font-semibold text-green-600">
                  {{ formatCurrency(calculateRetornoNeto(slotProps.data), slotProps.data.currency) }}
                </span>
                <div class="text-xs text-gray-500 mt-1">
                  Después de recaudación
                </div>
              </div>
            </template>
          </Column>

          <Column header="Total a Pagar" :sortable="true" style="min-width: 150px">
            <template #body="slotProps">
              <div>
                <span class="font-bold text-indigo-700">
                  {{ formatCurrency(calculateTotalPagar(slotProps.data), slotProps.data.currency) }}
                </span>
                <div class="text-xs text-gray-500 mt-1">
                  <span v-if="isPagoIntereses">Solo intereses netos</span>
                  <span v-else>Capital + Retorno Neto</span>
                </div>
              </div>
            </template>
          </Column>

          <!-- Columna adicional para Pago Parcial -->
          <Column v-if="isPagoParcial" header="Proporción" :sortable="true" style="min-width: 100px">
            <template #body="slotProps">
              <div>
                <span class="font-semibold text-purple-600">
                  {{ calculateProporcionParcial(slotProps.data) }}%
                </span>
                <div class="text-xs text-gray-500 mt-1">
                  Del pago parcial
                </div>
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
              <span class="text-sm font-semibold text-gray-600">Monto Total Factura:</span>
              <span class="font-bold text-lg text-blue-600">
                {{ formatCurrency(totalFactura, invoiceData?.moneda) }}
              </span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm font-semibold text-gray-600">Capital Invertido:</span>
              <span class="font-bold text-lg text-purple-600">
                {{ formatCurrency(totalCapitalInvertido, invoiceData?.moneda) }}
              </span>
            </div>
            
            <!-- Información específica para pago adelantado -->
            <div v-if="isPagoAdelantado" class="flex justify-between items-center">
              <span class="text-sm font-semibold text-gray-600">Retorno Completo (Vencimiento):</span>
              <span class="font-bold text-lg text-blue-600">
                {{ formatCurrency(totalRetornoCompleto, invoiceData?.moneda) }}
              </span>
            </div>
            
            <div v-if="isPagoAdelantado" class="flex justify-between items-center">
              <span class="text-sm font-semibold text-gray-600">Retorno Recalculado:</span>
              <span class="font-bold text-lg text-orange-600">
                {{ formatCurrency(totalRetornoBruto, invoiceData?.moneda) }}
              </span>
            </div>
            
            <div v-else class="flex justify-between items-center">
              <span class="text-sm font-semibold text-gray-600">Retorno Bruto Total:</span>
              <span class="font-bold text-lg text-gray-600">
                {{ formatCurrency(totalRetornoBruto, invoiceData?.moneda) }}
              </span>
            </div>
            
            <div v-if="isPagoIntereses || isPagoAdelantado" class="flex justify-between items-center">
              <span class="text-sm font-semibold text-gray-600">Recaudación (5%):</span>
              <span class="font-bold text-lg text-red-600">
                -{{ formatCurrency(totalRecaudacionEstimada, invoiceData?.moneda) }}
              </span>
            </div>
            <div v-if="isPagoIntereses || isPagoAdelantado" class="flex justify-between items-center">
              <span class="text-sm font-semibold text-gray-600">Retorno Neto Total:</span>
              <span class="font-bold text-lg text-green-600">
                {{ formatCurrency(totalRetornoNeto, invoiceData?.moneda) }}
              </span>
            </div>
            <div class="pt-3 border-t border-gray-300">
              <div class="flex justify-between items-center">
                <span class="text-sm font-semibold text-gray-600">Total a Inversionistas:</span>
                <span class="font-bold text-lg text-indigo-600">
                  {{ formatCurrency(totalCapitalMasRetornoNeto, invoiceData?.moneda) }}
                </span>
              </div>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm font-semibold text-gray-600">Monto Asumido Zuma:</span>
              <span class="font-bold text-lg text-orange-600">
                {{ formatCurrency(invoiceData?.montoAsumidoZuma, invoiceData?.moneda) }}
              </span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm font-semibold text-gray-600">Monto Disponible Zuma:</span>
              <span class="font-bold text-lg text-yellow-600">
                {{ formatCurrency(invoiceData?.montoDisponible, invoiceData?.moneda) }}
              </span>
            </div>
            <div class="pt-3 border-t border-gray-300">
              <div class="flex justify-between items-center">
                <span class="text-sm font-semibold text-gray-700">Total Asumido por Zuma:</span>
                <span class="font-bold text-lg text-orange-700">
                  {{ formatCurrency(totalAsumidoZuma, invoiceData?.moneda) }}
                </span>
              </div>
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

const montoAPagarDelExcel = computed(() => {
  return parseFloat(props.paymentData?.amount_to_pay || props.paymentData?.saldo || 0);
});

const isPagoIntereses = computed(() => {
  const tipoPago = mapPaymentType(props.paymentData?.tipo_pago);
  return tipoPago === 'intereses';
});

const isPagoParcial = computed(() => {
  const tipoPago = mapPaymentType(props.paymentData?.tipo_pago);
  return tipoPago === 'partial';
});

const isPagoTotal = computed(() => {
  const tipoPago = mapPaymentType(props.paymentData?.tipo_pago);
  return tipoPago === 'total';
});

// Calcular días adelantados
const diasAdelantados = computed(() => {
  if (!isPagoAdelantado.value) return 0;
  
  const dueDate = parseDateDDMMYYYY(invoiceData.value.fechaPago);
  const payDate = parseDateDDMMYYYY(props.paymentData.estimated_pay_date);
  
  if (!dueDate || !payDate) return 0;
  
  return Math.ceil((dueDate - payDate) / (1000 * 60 * 60 * 24));
});

const totalFactura = computed(() => {
  return parseFloat(invoiceData.value?.montoFactura || 0);
});

const totalCapitalInvertido = computed(() => {
  return investments.value.reduce((sum, inv) => sum + parseFloat(inv.amount || 0), 0);
});

// Para pago parcial, el monto viene del Excel o se calcula como porcentaje de la factura
const montoPagoParcial = computed(() => {
  if (!isPagoParcial.value) return 0;
  
  // USAR EL MONTO PAGADO DEL EXCEL si está disponible
  const montoDelExcel = parseFloat(props.paymentData?.amount_to_pay || props.paymentData?.saldo || 0);
  
  if (montoDelExcel > 0) {
    console.log('💰 Usando monto del Excel para pago parcial:', montoDelExcel);
    return montoDelExcel;
  }
  
  // Si no hay monto específico, usar el total de la factura
  console.log('ℹ️ No se encontró monto específico, usando total de factura');
  return parseFloat(props.paymentData?.monto_parcial || totalFactura.value);
});
// Retorno completo (lo que se pagaría al vencimiento)
const totalRetornoCompleto = computed(() => {
  return investments.value.reduce((sum, inv) => sum + parseFloat(inv.return || 0), 0);
});

// Retorno bruto calculado según tipo de pago
const totalRetornoBruto = computed(() => {
  return investments.value.reduce((sum, inv) => {
    const montos = calcularMontosPorTipoPago(inv);
    return sum + montos.retornoBruto;
  }, 0);
});

const totalRecaudacionEstimada = computed(() => {
  return investments.value.reduce((sum, inv) => {
    const montos = calcularMontosPorTipoPago(inv);
    return sum + montos.recaudacion;
  }, 0);
});

const totalRetornoNeto = computed(() => {
  return investments.value.reduce((sum, inv) => {
    const montos = calcularMontosPorTipoPago(inv);
    return sum + montos.retornoNeto;
  }, 0);
});

const totalCapitalMasRetornoNeto = computed(() => {
  return investments.value.reduce((sum, inv) => {
    const montos = calcularMontosPorTipoPago(inv);
    return sum + montos.totalPagar;
  }, 0);
});

const totalInversionTerceros = computed(() => {
  return totalCapitalInvertido.value + totalRetornoBruto.value;
});

const totalAsumidoZuma = computed(() => {
  const montoAsumido = parseFloat(invoiceData.value?.montoAsumidoZuma || 0);
  const montoDisponible = parseFloat(invoiceData.value?.montoDisponible || 0);
  return montoAsumido + montoDisponible;
});

const investmentsBeneficiados = computed(() => {
  return investments.value.filter(inv => 
    inv.status === 'active' || inv.status === 'pending'
  ).length;
});

const validacionesPasadas = computed(() => {
  const montoDisponible = totalFactura.value > 0;
  const inversionistasActivos = investmentsBeneficiados.value > 0;
  const fechas = props.paymentData?.estimated_pay_date && invoiceData.value?.fechaPago;
  
  return montoDisponible && inversionistasActivos && fechas;
});

// Validaciones computadas
const validationClasses = computed(() => {
  const montoDisponible = totalFactura.value > 0;
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
  return {
    montoDisponible: totalFactura.value > 0
      ? `Monto válido de factura: ${formatCurrency(totalFactura.value, invoiceData.value?.moneda)}`
      : `No se encontró monto de factura`,
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

// ============================================
// MÉTODOS PRINCIPALES DE CÁLCULO - CORREGIDOS
// ============================================

function calcularMontosPorTipoPago(investment) {
  const tipoPago = mapPaymentType(props.paymentData?.tipo_pago);
  const capital = parseFloat(investment.amount || 0);
  const retornoCompleto = parseFloat(investment.return || 0);
  
  console.log('=== CALCULANDO MONTOS PARA:', investment.inversionista);
  console.log('Capital:', capital);
  console.log('Retorno completo:', retornoCompleto);
  console.log('Tipo pago:', tipoPago);
  console.log('Es adelantado:', isPagoAdelantado.value);
  
  let retornoBruto = 0;
  let recaudacion = 0;
  let retornoNeto = 0;
  let totalPagar = 0;

  // Si es pago adelantado, siempre calcular el retorno proporcional y aplicar recaudación del 5%
  if (isPagoAdelantado.value) {
    retornoBruto = calcularInteresProporcional(investment);
    recaudacion = retornoBruto * 0.05;
    retornoNeto = retornoBruto - recaudacion;
    totalPagar = capital + retornoNeto;
    console.log('PAGO ADELANTADO: retornoBruto=', retornoBruto, 'recaudacion=', recaudacion, 'retornoNeto=', retornoNeto, 'totalPagar=', totalPagar);
  } else {
    // Si NO es adelantado, usar la lógica según el tipo de pago
    switch (tipoPago) {
      case 'intereses':
        // PAGO DE INTERESES: Solo se paga intereses completos con recaudación del 5%
        retornoBruto = retornoCompleto;
        recaudacion = retornoBruto * 0.05;
        retornoNeto = retornoBruto - recaudacion;
        totalPagar = retornoNeto; // SOLO INTERESES NETOS, NO SE DEVUELVE CAPITAL
        break;
        
      case 'partial':
        // PAGO PARCIAL: Devuelve SOLO capital proporcional, SIN intereses
        // Proporción del inversionista sobre el total invertido
        const proporcion = capital / totalCapitalInvertido.value;
        // Lo que le toca del pago parcial (proporción de su capital)
        const montoParcialInversor = montoPagoParcial.value * proporcion;
        
        retornoBruto = 0;  // NO hay intereses en pago parcial
        recaudacion = 0;    // NO hay recaudación
        retornoNeto = 0;    // NO hay retorno neto
        totalPagar = Math.min(montoParcialInversor, capital); // Máximo su capital, nunca más
        
        console.log('PAGO PARCIAL:');
        console.log('  - Capital inversor:', capital);
        console.log('  - Total capital invertido:', totalCapitalInvertido.value);
        console.log('  - Proporción:', proporcion);
        console.log('  - Monto pago parcial total:', montoPagoParcial.value);
        console.log('  - Monto parcial inversor:', montoParcialInversor);
        console.log('  - Total a pagar (min con capital):', totalPagar);
        break;
        
      case 'total':
        // PAGO TOTAL: Se paga capital + intereses completos sin recaudación
        retornoBruto = retornoCompleto;
        recaudacion = 0;
        retornoNeto = retornoBruto;
        totalPagar = capital + retornoNeto;
        break;
        
      default:
        retornoBruto = retornoCompleto;
        recaudacion = 0;
        retornoNeto = retornoBruto;
        totalPagar = capital + retornoNeto;
    }
  }
  
  console.log('Resultados finales:', { retornoBruto, recaudacion, retornoNeto, totalPagar });
  
  return {
    retornoBruto,
    recaudacion,
    retornoNeto,
    totalPagar
  };
}

// FÓRMULA CORREGIDA - CALCULAR RETORNO BASADO SOLO EN DÍAS TRANSCURRIDOS
function calcularInteresProporcional(investment) {
  if (!isPagoAdelantado.value) {
    return parseFloat(investment.return || 0);
  }
  
  const fechaInversion = parseDateTime(investment.creacion);
  const fechaVencimiento = parseDateDDMMYYYY(invoiceData.value?.fechaPago);
  const fechaPago = parseDateDDMMYYYY(props.paymentData?.estimated_pay_date);
  
  if (!fechaInversion || !fechaVencimiento || !fechaPago) {
    console.warn('Fechas incompletas para cálculo proporcional');
    return parseFloat(investment.return || 0);
  }
  
  const capital = parseFloat(investment.amount || 0);
  
  // Obtener tasa de interés: primero del investment, si no existe usar la de la factura
  let tasaInteres = parseFloat(investment.interest_rate || investment.rate || invoiceData.value?.tasa || 0) / 100;
  
  if (capital === 0 || !tasaInteres) {
    console.warn('Capital o tasa de interés no válidos');
    return 0;
  }
  
  // Días transcurridos desde la inversión hasta el pago adelantado
  const diasTranscurridos = Math.max(1, Math.ceil((fechaPago - fechaInversion) / (1000 * 60 * 60 * 24)));
  
  // Calcular retorno SOLO basado en los días transcurridos usando fórmula de interés compuesto
  // I = ((1 + tasa_mensual)^(días_transcurridos/30) - 1) × Capital
  const factorInteres = Math.pow(1 + tasaInteres, diasTranscurridos / 30) - 1;
  const retornoCalculado = factorInteres * capital;
  
  console.log('=== CÁLCULO RETORNO ADELANTADO ===');
  console.log('Inversionista:', investment.inversionista);
  console.log('Capital:', capital);
  console.log('Tasa mensual:', tasaInteres, '(', (tasaInteres * 100).toFixed(2), '%)');
  console.log('Días transcurridos:', diasTranscurridos);
  console.log('Factor interés:', factorInteres.toFixed(6));
  console.log('Retorno NUEVO calculado:', retornoCalculado.toFixed(2));
  console.log('Retorno completo (IGNORADO):', investment.return);
  
  // No permitir retorno negativo
  return Math.max(0, retornoCalculado);
}

function calcularDiasInvertidos(investment) {
  const fechaInversion = parseDateTime(investment.creacion);
  const fechaPago = parseDateDDMMYYYY(props.paymentData?.estimated_pay_date);
  
  if (!fechaInversion || !fechaPago) return 0;
  
  const dias = Math.ceil((fechaPago - fechaInversion) / (1000 * 60 * 60 * 24));
  return Math.max(1, dias);
}

function calculateProporcionParcial(investment) {
  if (!isPagoParcial.value) return 0;
  
  const capital = parseFloat(investment.amount || 0);
  const proporcion = (capital / totalCapitalInvertido.value) * 100;
  
  return proporcion.toFixed(2);
}

// Métodos de cálculo para la tabla
function calculateRetornoBruto(investment) {
  const montos = calcularMontosPorTipoPago(investment);
  return montos.retornoBruto;
}

function calculateRecaudacion(investment) {
  const montos = calcularMontosPorTipoPago(investment);
  return montos.recaudacion;
}

function calculateRetornoNeto(investment) {
  const montos = calcularMontosPorTipoPago(investment);
  return montos.retornoNeto;
}

function calculateTotalPagar(investment) {
  const montos = calcularMontosPorTipoPago(investment);
  return montos.totalPagar;
}

// ============================================
// MÉTODOS DE UTILIDAD Y FORMATO
// ============================================

function formatCurrency(amount, currency) {
  if (amount === null || amount === undefined) return '-';
  const symbol = (currency === 'PEN' || currency === 'S/') ? 'S/' : 'US$';
  const formattedAmount = Number(amount).toLocaleString('es-PE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
  return `${symbol} ${formattedAmount}`;
}

function formatDate(dateString) {
  if (!dateString) return '-';
  try {
    if (dateString.includes('-')) {
      return dateString;
    }
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

function parseDateTime(dateTimeString) {
  if (!dateTimeString) return null;
  try {
    const [datePart, timePart, period] = dateTimeString.split(' ');
    const [day, month, year] = datePart.split('-');
    const [time, seconds] = timePart.split(':');
    
    let hours = parseInt(time, 10);
    const minutes = parseInt(seconds, 10);
    
    if (period === 'PM' && hours < 12) {
      hours += 12;
    } else if (period === 'AM' && hours === 12) {
      hours = 0;
    }
    
    return new Date(year, month - 1, day, hours, minutes);
  } catch (error) {
    return parseDateDDMMYYYY(dateTimeString);
  }
}

function getInitials(name) {
  if (!name) return '?';
  return name.split(' ').map(n => n.charAt(0)).join('').toUpperCase().substring(0, 2);
}

// ============================================
// MÉTODOS PARA TIPOS DE PAGO
// ============================================

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
    case 'Pago parcial': return 'pi pi-chart-pie text-yellow-600';
    case 'Pago de intereses': return 'pi pi-percentage text-blue-600';
    default: return 'pi pi-info-circle text-gray-600';
  }
}

function getPaymentTypeDescription(tipo) {
  if (!tipo) return 'Tipo de pago no especificado';
  
  // Si es pago adelantado, mostrar descripción específica
  if (isPagoAdelantado.value) {
    return `Pago adelantado: ${diasAdelantados.value} días antes del vencimiento. Retorno recalculado proporcionalmente con recaudación del 5%`;
  }
  
  switch (tipo) {
    case 'Paga toda la factura': 
      return 'Liquidación completa de la factura (capital + intereses completos sin recaudación)';
    case 'Pago parcial': 
      return 'Devolución parcial SOLO del capital, distribuido proporcionalmente. NO incluye intereses ni recaudación';
    case 'Pago de intereses': 
      return 'Solo intereses completos con recaudación del 5%. El capital no se devuelve';
    default: 
      return 'Tipo de pago no especificado';
  }
}

function mapPaymentType(excelType) {
  if (!excelType) return 'total';
  
  const mapping = {
    'Pago de intereses': 'intereses',
    'Pago parcial': 'partial',
    'Paga toda la factura': 'total'
  };
  
  return mapping[excelType] || 'total';
}

function getMensajePagoAdelantado() {
  if (!isPagoAdelantado.value) return '';
  
  return `Se realiza ${diasAdelantados.value} día(s) antes del vencimiento. El retorno se calcula proporcionalmente a los días transcurridos de inversión.`;
}

function getMensajeFinal() {
  const tipoPago = mapPaymentType(props.paymentData?.tipo_pago);
  const montoAPagar = parseFloat(props.paymentData?.amount_to_pay || props.paymentData?.saldo || totalFactura.value);
  
  if (isPagoAdelantado.value) {
    return `Pago adelantado por ${formatCurrency(montoAPagar, invoiceData.value?.moneda)}: Retornos reducidos de ${formatCurrency(totalRetornoCompleto.value, invoiceData.value?.moneda)} a ${formatCurrency(totalRetornoBruto.value, invoiceData.value?.moneda)} (${diasAdelantados.value} días antes). Se aplicará recaudación del 5%`;
  }
  
  switch (tipoPago) {
    case 'intereses': 
      return `Se procesará pago de intereses por ${formatCurrency(montoAPagar, invoiceData.value?.moneda)} con recaudación del 5%. El capital se mantiene`;
    case 'total': 
      return `Se liquidará completamente la factura por ${formatCurrency(montoAPagar, invoiceData.value?.moneda)}: capital + intereses completos sin recaudación`;
    case 'partial': 
      return `Devolución parcial de ${formatCurrency(montoAPagar, invoiceData.value?.moneda)} distribuido proporcionalmente. Solo capital, sin intereses`;
    default: 
      return `Verifique los montos antes de procesar - Monto: ${formatCurrency(montoAPagar, invoiceData.value?.moneda)}`;
  }
}

// ============================================
// MÉTODO PRINCIPAL DE PROCESAMIENTO
// ============================================

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
    
    // USAR EL MONTO PAGADO DEL EXCEL para amount_to_be_paid
    const amountToBePaid = parseFloat(props.paymentData?.amount_to_pay || props.paymentData?.saldo || totalFactura.value);
    
    const investorPayments = investments.value.map(inv => {
      const capital = parseFloat(inv.amount);
      const montos = calcularMontosPorTipoPago(inv);
      const retornoCompleto = parseFloat(inv.return || 0);
      const diasInvertidos = calcularDiasInvertidos(inv);
      
      return {
        investor_id: inv.investor_id,
        capital: capital,
        retorno_completo: retornoCompleto,
        retorno_bruto: montos.retornoBruto,
        recaudacion: montos.recaudacion,
        retorno_neto: montos.retornoNeto,
        total_a_pagar: montos.totalPagar,
        tipo_pago: payType,
        es_adelantado: isPagoAdelantado.value,
        dias_invertidos: diasInvertidos,
        fecha_creacion: inv.creacion,
        proporcion_parcial: isPagoParcial.value ? calculateProporcionParcial(inv) : null
      };
    });

    const paymentPayload = {
      amount_to_be_paid: totalFactura.value, // ← Monto total de la factura
      amount_to_pay: amountToBePaid, // ← Monto específico del Excel
      pay_type: payType,
      pay_date: props.paymentData.estimated_pay_date,
      investor_payments: investorPayments,
      es_adelantado: isPagoAdelantado.value,
      dias_adelantados: isPagoAdelantado.value ? diasAdelantados.value : null,
      monto_parcial: isPagoParcial.value ? montoPagoParcial.value : null
    };

    console.log('=== PROCESANDO PAGO ===');
    console.log('Tipo:', payType);
    console.log('Adelantado:', isPagoAdelantado.value);
    console.log('Días adelantados:', diasAdelantados.value);
    console.log('Monto a pagar (amount_to_be_paid):', amountToBePaid);
    console.log('Payload:', paymentPayload);

    const response = await axios.post(
      `/payments/${props.paymentData.id_pago}`,
      paymentPayload
    );
    
    if (response.data) {
      toast.add({
        severity: 'success',
        summary: 'Pago Procesado',
        detail: `Pago ${payType} por ${formatCurrency(amountToBePaid, invoiceData.value?.moneda)} registrado correctamente para ${investments.value.length} inversionista(s)`,
        life: 5000
      });

      emit('payment-processed', {
        payment_id: response.data.payment?.id,
        invoice_id: props.paymentData.id_pago,
        processed_type: payType,
        processed_amount: amountToBePaid, // ← USAR EL MONTO REAL PAGADO
        investor_details: investorPayments,
        es_adelantado: isPagoAdelantado.value,
        dias_adelantados: diasAdelantados.value
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
      
      console.log('=== DATOS CARGADOS ===');
      console.log('Fecha vencimiento:', invoiceData.value?.fechaPago);
      console.log('Fecha pago:', props.paymentData?.estimated_pay_date);
      console.log('Es adelantado:', isPagoAdelantado.value);
      console.log('Días adelantados:', diasAdelantados.value);
      console.log('Total inversiones:', investments.value.length);
      
      // DEBUG: Mostrar datos de cada inversión
      investments.value.forEach((inv, index) => {
        console.log(`Inversión ${index + 1}:`, {
          inversionista: inv.inversionista,
          capital: inv.amount,
          retorno_completo: inv.return,
          tasa_interes: inv.interest_rate,
          fecha_creacion: inv.creacion,
          dias_invertidos: calcularDiasInvertidos(inv)
        });
      });
      
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

function onCancel() {
  emit('cancelled');
  onClose();
}

function onClose() {
  dialogVisible.value = false;
  emit('update:visible', false);
  
  setTimeout(() => {
    invoiceData.value = null;
    investments.value = [];
  }, 300);
}
</script>