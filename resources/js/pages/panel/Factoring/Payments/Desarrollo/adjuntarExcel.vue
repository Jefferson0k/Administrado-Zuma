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
                Total procesados: {{ extractedData.length }}
              </span>
            </div>
          </div>
          <DataTable :value="extractedData" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]"
            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
            currentPageReportTemplate="{first} a {last} de {totalRecords} registros" tableStyle="min-width: 90rem"
            class="p-datatable-sm" stripedRows>
  
            <template #header>
              <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">
                  Validación de Pagos
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

            <!-- Columna Nro. Préstamo -->
            <Column field="NRO PRESTAMO" header="Nro. Préstamo" sortable style="min-width: 12rem">
              <template #body="slotProps">
                <div class="flex items-center gap-3">
                  <div class="flex-shrink-0">
                    <div v-if="getFieldValidation(slotProps.data, 'loan_number') === 'success'"
                         class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center"
                         title="✓ Préstamo coincide">
                      <i class="pi pi-check text-green-600 text-lg font-bold"></i>
                    </div>
                    <div v-else-if="getFieldValidation(slotProps.data, 'loan_number') === 'error'"
                         class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center"
                         title="✗ Préstamo no coincide">
                      <i class="pi pi-times text-red-600 text-lg font-bold"></i>
                    </div>
                    <div v-else-if="getFieldValidation(slotProps.data, 'loan_number') === 'warning'"
                         class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center"
                         title="⚠ Préstamo no encontrado">
                      <i class="pi pi-exclamation-triangle text-yellow-600 text-lg"></i>
                    </div>
                    <div v-else
                         class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center"
                         title="? Estado desconocido">
                      <i class="pi pi-question text-gray-500 text-lg"></i>
                    </div>
                  </div>
                  <span class="font-mono">{{ slotProps.data['NRO PRESTAMO'] }}</span>
                </div>
              </template>
            </Column>

            <!-- Columna RUC Proveedor -->
            <Column field="RUC PROVEEDOR" header="RUC Proveedor" sortable style="min-width: 12rem">
              <template #body="slotProps">
                <div class="flex items-center gap-3">
                  <div class="flex-shrink-0">
                    <div v-if="getFieldValidation(slotProps.data, 'RUC_proveedor') === 'success'"
                         class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center"
                         title="✓ RUC Proveedor coincide">
                      <i class="pi pi-check text-green-600 text-lg font-bold"></i>
                    </div>
                    <div v-else-if="getFieldValidation(slotProps.data, 'RUC_proveedor') === 'error'"
                         class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center"
                         title="✗ RUC Proveedor no coincide">
                      <i class="pi pi-times text-red-600 text-lg font-bold"></i>
                    </div>
                    <div v-else-if="getFieldValidation(slotProps.data, 'RUC_proveedor') === 'warning'"
                         class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center"
                         title="⚠ Proveedor no encontrado">
                      <i class="pi pi-exclamation-triangle text-yellow-600 text-lg"></i>
                    </div>
                    <div v-else
                         class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center"
                         title="? Estado desconocido">
                      <i class="pi pi-question text-gray-500 text-lg"></i>
                    </div>
                  </div>
                  <span class="font-mono">{{ slotProps.data['RUC PROVEEDOR'] }}</span>
                </div>
              </template>
            </Column>

            <!-- Columna Nro. Factura -->
            <Column field="NRO FACTURA" header="Nro. Factura" sortable style="min-width: 12rem">
              <template #body="slotProps">
                <div class="flex items-center gap-3">
                  <div class="flex-shrink-0">
                    <div v-if="getFieldValidation(slotProps.data, 'invoice_number') === 'success'"
                         class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center"
                         title="✓ Factura coincide">
                      <i class="pi pi-check text-green-600 text-lg font-bold"></i>
                    </div>
                    <div v-else-if="getFieldValidation(slotProps.data, 'invoice_number') === 'error'"
                         class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center"
                         title="✗ Factura no coincide">
                      <i class="pi pi-times text-red-600 text-lg font-bold"></i>
                    </div>
                    <div v-else-if="getFieldValidation(slotProps.data, 'invoice_number') === 'warning'"
                         class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center"
                         title="⚠ Factura no encontrada">
                      <i class="pi pi-exclamation-triangle text-yellow-600 text-lg"></i>
                    </div>
                    <div v-else
                         class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center"
                         title="? Estado desconocido">
                      <i class="pi pi-question text-gray-500 text-lg"></i>
                    </div>
                  </div>
                  <span class="font-mono">{{ slotProps.data['NRO FACTURA'] }}</span>
                </div>
              </template>
            </Column>

            <!-- Columna RUC Aceptante -->
            <Column field="RUC ACEPTANTE" header="RUC Aceptante" sortable style="min-width: 12rem">
              <template #body="slotProps">
                <div class="flex items-center gap-3">
                  <div class="flex-shrink-0">
                    <div v-if="getFieldValidation(slotProps.data, 'RUC_aceptante') === 'success'"
                         class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center"
                         title="✓ RUC Aceptante coincide">
                      <i class="pi pi-check text-green-600 text-lg font-bold"></i>
                    </div>
                    <div v-else-if="getFieldValidation(slotProps.data, 'RUC_aceptante') === 'error'"
                         class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center"
                         title="✗ RUC Aceptante no coincide">
                      <i class="pi pi-times text-red-600 text-lg font-bold"></i>
                    </div>
                    <div v-else-if="getFieldValidation(slotProps.data, 'RUC_aceptante') === 'warning'"
                         class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center"
                         title="⚠ Aceptante no encontrado">
                      <i class="pi pi-exclamation-triangle text-yellow-600 text-lg"></i>
                    </div>
                    <div v-else
                         class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center"
                         title="? Estado desconocido">
                      <i class="pi pi-question text-gray-500 text-lg"></i>
                    </div>
                  </div>
                  <span class="font-mono">{{ slotProps.data['RUC ACEPTANTE'] }}</span>
                </div>
              </template>
            </Column>

            <!-- Columna Moneda -->
            <Column field="MONEDA" header="Moneda" sortable style="min-width: 8rem">
              <template #body="slotProps">
                <div class="flex items-center gap-3">
                  <div class="flex-shrink-0">
                    <div v-if="getFieldValidation(slotProps.data, 'currency') === 'success'"
                         class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center"
                         title="✓ Moneda coincide">
                      <i class="pi pi-check text-green-600 text-lg font-bold"></i>
                    </div>
                    <div v-else-if="getFieldValidation(slotProps.data, 'currency') === 'error'"
                         class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center"
                         title="✗ Moneda no coincide">
                      <i class="pi pi-times text-red-600 text-lg font-bold"></i>
                    </div>
                    <div v-else-if="getFieldValidation(slotProps.data, 'currency') === 'warning'"
                         class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center"
                         title="⚠ Moneda no validada">
                      <i class="pi pi-exclamation-triangle text-yellow-600 text-lg"></i>
                    </div>
                    <div v-else
                         class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center"
                         title="? Estado desconocido">
                      <i class="pi pi-question text-gray-500 text-lg"></i>
                    </div>
                  </div>
                  <Tag :value="slotProps.data['MONEDA']"
                    :severity="getCurrencySeverity(slotProps.data['MONEDA'])" />
                </div>
              </template>
            </Column>

            <!-- Columna Monto Documento -->
            <Column field="MONTO DOCUMENTO" header="Monto Documento" sortable style="min-width: 12rem">
              <template #body="slotProps">
                <div class="flex items-center gap-3">
                  <div class="flex-shrink-0">
                    <div v-if="getFieldValidation(slotProps.data, 'monto_documento') === 'success'"
                         class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center"
                         title="✓ Monto coincide">
                      <i class="pi pi-check text-green-600 text-lg font-bold"></i>
                    </div>
                    <div v-else-if="getFieldValidation(slotProps.data, 'monto_documento') === 'error'"
                         class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center"
                         title="✗ Monto no coincide">
                      <i class="pi pi-times text-red-600 text-lg font-bold"></i>
                    </div>
                    <div v-else-if="getFieldValidation(slotProps.data, 'monto_documento') === 'warning'"
                         class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center"
                         title="⚠ Monto no validado">
                      <i class="pi pi-exclamation-triangle text-yellow-600 text-lg"></i>
                    </div>
                    <div v-else
                         class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center"
                         title="? Estado desconocido">
                      <i class="pi pi-question text-gray-500 text-lg"></i>
                    </div>
                  </div>
                  <span class="font-mono font-semibold">
                    {{ formatCurrency(slotProps.data['MONTO DOCUMENTO'], slotProps.data['MONEDA']) }}
                  </span>
                </div>
              </template>
            </Column>

            <!-- Columna Fecha Pago -->
            <Column field="FECHA PAGO" header="Fecha Pago" sortable style="min-width: 12rem">
              <template #body="slotProps">
                <div class="flex items-center gap-3">
                  <div class="flex-shrink-0">
                    <div v-if="getFieldValidation(slotProps.data, 'fecha_pago') === 'success'"
                         class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center"
                         title="✓ Fecha coincide">
                      <i class="pi pi-check text-green-600 text-lg font-bold"></i>
                    </div>
                    <div v-else-if="getFieldValidation(slotProps.data, 'fecha_pago') === 'error'"
                         class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center"
                         title="✗ Fecha no coincide">
                      <i class="pi pi-times text-red-600 text-lg font-bold"></i>
                    </div>
                    <div v-else-if="getFieldValidation(slotProps.data, 'fecha_pago') === 'warning'"
                         class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center"
                         title="⚠ Fecha no validada">
                      <i class="pi pi-exclamation-triangle text-yellow-600 text-lg"></i>
                    </div>
                    <div v-else
                         class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center"
                         title="? Estado desconocido">
                      <i class="pi pi-question text-gray-500 text-lg"></i>
                    </div>
                  </div>
                  <span class="font-mono text-sm">{{ slotProps.data['FECHA PAGO'] }}</span>
                </div>
              </template>
            </Column>

            <!-- Columna Monto Pagado -->
            <Column field="MONTO PAGADO" header="Monto Pagado" sortable style="min-width: 12rem">
              <template #body="slotProps">
                <span class="font-mono text-blue-600 font-medium">
                  {{ formatCurrency(slotProps.data['MONTO PAGADO'], slotProps.data['MONEDA']) }}
                </span>
              </template>
            </Column>

            <!-- Columna Tipo Pago -->
            <Column field="tipo_pago" header="T. Pago" sortable style="min-width: 12rem">
              <template #body="slotProps">
                <Tag :value="slotProps.data.tipo_pago" 
                     :severity="getTipoPagoSeverity(slotProps.data.tipo_pago)" />
              </template>
            </Column>

            <!-- Columna Estado General -->
            <Column field="estado" header="Resultado General" sortable style="min-width: 12rem">
              <template #body="slotProps">
                <div class="flex items-center justify-center">
                  <div class="flex items-center gap-2">
                    <div v-if="slotProps.data.estado === 'Coincide'"
                         class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center shadow-md"
                         title="✓ Todos los campos coinciden">
                      <i class="pi pi-check text-white text-xl font-bold"></i>
                    </div>
                    <div v-else-if="slotProps.data.estado === 'No coincide'"
                         class="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center shadow-md"
                         title="✗ Algunos campos no coinciden">
                      <i class="pi pi-times text-white text-xl font-bold"></i>
                    </div>
                    <div v-else-if="slotProps.data.estado === 'Procesado'"
                         class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center shadow-md"
                         title="✓✓ Ya procesado">
                      <i class="pi pi-verified text-white text-lg"></i>
                    </div>
                    <div v-else
                         class="w-10 h-10 bg-gray-400 rounded-full flex items-center justify-center shadow-md"
                         title="? Estado desconocido">
                      <i class="pi pi-question text-white text-lg"></i>
                    </div>
                    
                    <span class="font-medium text-sm"
                          :class="{
                            'text-green-700': slotProps.data.estado === 'Coincide',
                            'text-red-700': slotProps.data.estado === 'No coincide',
                            'text-blue-700': slotProps.data.estado === 'Procesado',
                            'text-gray-600': !['Coincide', 'No coincide', 'Procesado'].includes(slotProps.data.estado)
                          }">
                      {{ slotProps.data.estado }}
                    </span>
                  </div>
                </div>
              </template>
            </Column>

            <!-- Columna Acciones -->
            <Column header="Acciones" style="min-width: 12rem">
              <template #body="slotProps">
                <div class="flex gap-2 justify-center">
                  <Button 
                    v-if="slotProps.data.estado === 'Coincide' && slotProps.data.id_pago" 
                    icon="pi pi-credit-card" 
                    severity="success" 
                    text 
                    rounded 
                    @click="realizarPago(slotProps.data)"
                    v-tooltip="'Realizar pago'" 
                  />
                  
                  <Button 
                    v-else-if="slotProps.data.estado === 'Procesado'" 
                    icon="pi pi-check-circle" 
                    severity="info" 
                    text 
                    rounded 
                    disabled
                    v-tooltip="'Pago ya procesado'" 
                  />
                  
                  <Button 
                    v-else
                    icon="pi pi-exclamation-triangle" 
                    severity="warning" 
                    text 
                    rounded 
                    disabled
                    v-tooltip="'No se puede procesar: ' + slotProps.data.estado" 
                  />
                  
                  <Button 
                    icon="pi pi-info-circle" 
                    severity="secondary" 
                    text 
                    rounded 
                    @click="verDetalles(slotProps.data)"
                    v-tooltip="'Ver detalles completos'" 
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
          <span class="flex items-center gap-4 text-xs">
            <span class="flex items-center gap-1">
              <i class="pi pi-check text-green-600"></i> = Coincide
            </span>
            <span class="flex items-center gap-1">
              <i class="pi pi-times text-red-600"></i> = No coincide
            </span>
            <span class="flex items-center gap-1">
              <i class="pi pi-exclamation-triangle text-yellow-600"></i> = No encontrado
            </span>
            <span class="flex items-center gap-1">
              <i class="pi pi-ban text-orange-500"></i> = Sin registro
            </span>
          </span>
        </small>
        <div class="flex gap-2">
          <Button label="Cancelar" icon="pi pi-times" text severity="secondary" @click="hideDialog" />
        </div>
      </div>
    </template>
  </Dialog>

  <!-- Diálogo para mostrar detalles de validación -->
  <Dialog v-model:visible="detailsDialog" :style="{ width: '70vw', maxWidth: '800px' }" 
    :header="`Detalles de Validación - ${selectedRecord?.['NRO FACTURA'] || ''}`" :modal="true">
    <div v-if="selectedRecord" class="flex flex-col gap-4">
      <!-- Información general -->
      <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
          <h4 class="font-semibold mb-2 text-gray-800">Información del Registro</h4>
          <div class="space-y-2 text-sm">
            <div><strong>Factura:</strong> {{ selectedRecord['NRO FACTURA'] }}</div>
            <div><strong>Préstamo:</strong> {{ selectedRecord['NRO PRESTAMO'] }}</div>
            <div><strong>Aceptante:</strong> {{ selectedRecord['RUC ACEPTANTE'] }}</div>
            <div><strong>Proveedor:</strong> {{ selectedRecord['RUC PROVEEDOR'] }}</div>
          </div>
        </div>
        <div class="p-4 border border-gray-200 rounded-lg" 
          :class="selectedRecord.estado === 'Coincide' ? 'bg-green-50' : 'bg-red-50'">
          <h4 class="font-semibold mb-2" 
            :class="selectedRecord.estado === 'Coincide' ? 'text-green-800' : 'text-red-800'">
            Resultado General
          </h4>
          <div class="flex items-center gap-2">
            <i :class="selectedRecord.estado === 'Coincide' ? 'pi pi-check-circle text-green-600' : 'pi pi-times-circle text-red-600'"></i>
            <span class="font-medium">{{ selectedRecord.estado }}</span>
          </div>
        </div>
      </div>

      <!-- Detalles de validación por campo -->
      <div class="space-y-3">
        <h4 class="font-semibold text-gray-800 border-b pb-2">Validación por Campo</h4>
        
        <div v-for="validation in getDetailedValidations(selectedRecord)" :key="validation.field"
          class="flex items-center justify-between p-3 border border-gray-200 rounded-lg"
          :class="validation.status === 'success' ? 'bg-green-50 border-green-200' : 
                 validation.status === 'error' ? 'bg-red-50 border-red-200' : 'bg-yellow-50 border-yellow-200'">
          <div class="flex items-center gap-3">
            <i :class="validation.status === 'success' ? 'pi pi-check text-green-600' : 
                      validation.status === 'error' ? 'pi pi-times text-red-600' : 'pi pi-exclamation-triangle text-yellow-600'"></i>
            <div>
              <span class="font-medium">{{ validation.label }}</span>
              <div class="text-sm text-gray-600">{{ validation.message }}</div>
            </div>
          </div>
          <div class="text-right text-sm">
            <div v-if="validation.dbValue"><strong>BD:</strong> {{ validation.dbValue }}</div>
            <div v-if="validation.excelValue"><strong>Excel:</strong> {{ validation.excelValue }}</div>
          </div>
        </div>
      </div>

      <!-- Información adicional -->
      <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <h4 class="font-semibold text-blue-800 mb-2">Información de Pago</h4>
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div><strong>Tipo de Pago:</strong> {{ selectedRecord.tipo_pago }}</div>
          <div><strong>Monto Documento:</strong> {{ formatCurrency(selectedRecord['MONTO DOCUMENTO'], selectedRecord['MONEDA']) }}</div>
          <div><strong>Monto a Pagar:</strong> {{ formatCurrency(selectedRecord['MONTO PAGADO'], selectedRecord['MONEDA']) }}</div>
          <div><strong>Moneda:</strong> {{ selectedRecord['MONEDA'] }}</div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-2">
        <Button label="Cerrar" icon="pi pi-times" text severity="secondary" @click="detailsDialog = false" />
        <Button v-if="selectedRecord?.estado === 'Coincide' && selectedRecord?.id_pago" 
          label="Realizar Pago" icon="pi pi-credit-card" severity="success" 
          @click="realizarPagoFromDetails()" />
      </div>
    </template>
  </Dialog>

  <addPaymensts 
    :visible="showPaymentDialog" 
    :payment-data="selectedPaymentData"
    @update:visible="(value) => showPaymentDialog = value" 
    @payment-processed="onPaymentProcessed"
    @cancelled="onPaymentCancelled" 
  />
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
const detailsDialog = ref(false);
const loading = ref(false);
const extractedData = ref([]);
const selectedFile = ref(null);
const globalFilterValue = ref('');
const showPaymentDialog = ref(false);
const selectedPaymentData = ref({});
const selectedRecord = ref(null);

// Estadísticas computadas
const coincidencias = computed(() => extractedData.value.filter(item => item.estado === 'Coincide').length);
const noCoincidencias = computed(() => extractedData.value.filter(item => item.estado === 'No coincide').length);

// Función para obtener el estado de validación de un campo específico
function getFieldValidation(record, field) {
  if (!record.detalle) return 'unknown';
  
  const detalle = record.detalle;
  
  // Mapeo de campos a sus patrones en el detalle
  const fieldPatterns = {
    'loan_number': 'Nro Prestamo: OK',
    'RUC_proveedor': 'RUC Proveedor: OK',
    'invoice_number': 'Nro Factura: OK',
    'RUC_aceptante': 'RUC Aceptante: OK',
    'currency': 'Moneda: OK',
    'monto_documento': 'Monto documento: OK',
    'fecha_pago': 'Fecha estimada: OK'
  };

  if (record.estado === 'No coincide' && detalle.includes('no registrada')) {
    return 'warning';
  }

  if (record.estado === 'No coincide' && detalle.includes('no encontrada')) {
    return 'warning';
  }

  const pattern = fieldPatterns[field];
  if (pattern && detalle.includes(pattern)) {
    return 'success';
  } else if (detalle.includes(`${field}:`) || detalle.includes(pattern?.replace(': OK', ': Diferente'))) {
    return 'error';
  }

  return 'unknown';
}

// Función para obtener validaciones detalladas para el diálogo
function getDetailedValidations(record) {
  if (!record || !record.detalle) return [];
  
  const detalle = record.detalle;
  const validations = [];

  // Parsear el detalle para extraer información específica
  const details = detalle.split(' | ');
  
  details.forEach(detail => {
    if (detail.includes('RUC Aceptante:')) {
      const status = detail.includes('OK') ? 'success' : 'error';
      validations.push({
        field: 'RUC_aceptante',
        label: 'RUC Aceptante',
        status,
        message: detail,
        dbValue: status === 'error' ? extractValue(detail, 'BD:') : null,
        excelValue: status === 'error' ? extractValue(detail, 'Excel:') : null
      });
    }
    
    if (detail.includes('Nro Prestamo:')) {
      const status = detail.includes('OK') ? 'success' : 'error';
      validations.push({
        field: 'loan_number',
        label: 'Número de Préstamo',
        status,
        message: detail,
        dbValue: status === 'error' ? extractValue(detail, 'BD:') : null,
        excelValue: status === 'error' ? extractValue(detail, 'Excel:') : null
      });
    }
    
    if (detail.includes('Nro Factura:')) {
      const status = detail.includes('OK') ? 'success' : 'error';
      validations.push({
        field: 'invoice_number',
        label: 'Número de Factura',
        status,
        message: detail,
        dbValue: status === 'error' ? extractValue(detail, 'BD:') : null,
        excelValue: status === 'error' ? extractValue(detail, 'Excel:') : null
      });
    }
    
    if (detail.includes('Monto documento:')) {
      const status = detail.includes('OK') ? 'success' : 'error';
      validations.push({
        field: 'monto_documento',
        label: 'Monto Documento',
        status,
        message: detail,
        dbValue: status === 'error' ? extractValue(detail, 'BD:') : null,
        excelValue: status === 'error' ? extractValue(detail, 'Excel:') : null
      });
    }
    
    if (detail.includes('Fecha estimada:')) {
      const status = detail.includes('OK') ? 'success' : 'error';
      validations.push({
        field: 'fecha_pago',
        label: 'Fecha Estimada',
        status,
        message: detail,
        dbValue: status === 'error' ? extractValue(detail, 'BD:') : null,
        excelValue: status === 'error' ? extractValue(detail, 'Excel:') : null
      });
    }
    
    if (detail.includes('Moneda:')) {
      const status = detail.includes('OK') ? 'success' : 'error';
      validations.push({
        field: 'currency',
        label: 'Moneda',
        status,
        message: detail,
        dbValue: status === 'error' ? extractValue(detail, 'BD:') : null,
        excelValue: status === 'error' ? extractValue(detail, 'Excel:') : null
      });
    }
    
    if (detail.includes('no registrada')) {
      validations.push({
        field: 'company',
        label: 'Empresa',
        status: 'warning',
        message: detail
      });
    }
    
    if (detail.includes('no encontrada')) {
      validations.push({
        field: 'invoice',
        label: 'Factura',
        status: 'warning',
        message: detail
      });
    }
  });

  return validations;
}

// Función auxiliar para extraer valores de los mensajes de detalle
function extractValue(detail, prefix) {
  const index = detail.indexOf(prefix);
  if (index === -1) return null;
  
  const afterPrefix = detail.substring(index + prefix.length);
  const endIndex = afterPrefix.indexOf(' vs ') !== -1 ? afterPrefix.indexOf(' vs ') : afterPrefix.indexOf(')');
  
  return endIndex !== -1 ? afterPrefix.substring(0, endIndex).trim() : afterPrefix.trim();
}

// Funciones de reseteo y diálogo
function resetSector() {
  loading.value = false;
  extractedData.value = [];
  selectedFile.value = null;
  globalFilterValue.value = '';
  selectedRecord.value = null;
}

function openNew() {
  resetSector();
  sectorDialog.value = true;
}

function hideDialog() {
  sectorDialog.value = false;
  detailsDialog.value = false;
  resetSector();
}

// Funciones para severidades de tags
function getCurrencySeverity(currency) {
  switch(currency) {
    case 'PEN': return 'info';
    case 'USD': return 'warning';
    case 'S/': return 'info';
    default: return 'secondary';
  }
}

function getTipoPagoSeverity(tipoPago) {
  switch (tipoPago) {
    case 'Paga toda la factura': return 'success';
    case 'Pago parcial': return 'warning';
    case 'Pago de intereses': return 'info';
    case 'Sin determinar': return 'secondary';
    default: return 'secondary';
  }
}

// Función para formatear moneda
function formatCurrency(amount, currency) {
  if (!amount) return '-';
  const symbol = currency === 'PEN' || currency === 'S/' ? 'S/' : '$';
  return `${symbol} ${Number(amount).toLocaleString('es-PE', { minimumFractionDigits: 2 })}`;
}

function realizarPago(record) {
  console.log('Iniciando proceso de pago para:', record);
  
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

  // Preparar los datos para el pago - AÑADIR MONTO PAGADO
  selectedPaymentData.value = {
    id_pago: record.id_pago,
    invoice_number: record['NRO FACTURA'],
    loan_number: record['NRO PRESTAMO'],
    document: record.document || 'Proveedor',
    ruc_proveedor: record['RUC ACEPTANTE'],
    amount: record['MONTO DOCUMENTO'],
    saldo: record['MONTO PAGADO'], // ← AÑADIR ESTA LÍNEA
    amount_to_pay: record['MONTO PAGADO'], // ← AÑADIR ESTA LÍNEA (nombre que usa el backend)
    currency: record['MONEDA'],
    estimated_pay_date: record['FECHA PAGO'],
    tipo_pago: record.tipo_pago
  };

  console.log('Datos preparados para pago:', selectedPaymentData.value);
  
  // Mostrar el diálogo
  showPaymentDialog.value = true;
  
  toast.add({
    severity: 'info',
    summary: 'Procesar Pago',
    detail: `Preparando pago para factura ${record['NRO FACTURA']} - Monto: ${formatCurrency(record['MONTO PAGADO'], record['MONEDA'])}`,
    life: 3000
  });
}

function realizarPagoFromDetails() {
  detailsDialog.value = false;
  realizarPago(selectedRecord.value);
}

// Función para ver detalles completos
function verDetalles(record) {
  selectedRecord.value = record;
  detailsDialog.value = true;
}

// Función para manejar pago procesado
function onPaymentProcessed(data) {
  const index = extractedData.value.findIndex(item => 
    item.id_pago === data.id_pago
  );
  
  if (index !== -1) {
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
  
  emit('agregado');
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

// Función de búsqueda global
function onGlobalSearch() {
  console.log('Búsqueda global:', globalFilterValue.value);
}
</script>

<style scoped>
.font-mono {
  font-family: 'Courier New', monospace;
}

/* Estilos para los iconos de validación */
.validation-icon {
  width: 16px;
  height: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* Mejoras visuales para el diálogo de detalles */
.details-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.validation-item {
  transition: all 0.3s ease;
}

.validation-item:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Estilos responsivos */
@media (max-width: 768px) {
  .details-grid {
    grid-template-columns: 1fr;
  }
}
</style>