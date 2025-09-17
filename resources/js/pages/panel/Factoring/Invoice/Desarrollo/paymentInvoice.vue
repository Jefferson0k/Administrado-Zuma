<template>
    <Dialog v-model:visible="dialogVisible" modal :closable="true" :draggable="false" class="mx-4"
        style="width: 90vw; max-width: 1500px;" @hide="onCancel">
        <template #header>
            <div class="flex items-center gap-2">
                <i class="pi pi-wallet text-xl"></i>
                <span class="text-xl font-semibold">Gestión de Pago - {{ facturaData?.codigo }}</span>
            </div>
        </template>
        <div v-if="loading" class="flex justify-center items-center py-8">
            <i class="pi pi-spinner pi-spin text-3xl text-blue-500"></i>
            <span class="ml-2 text-gray-600">Cargando información...</span>
        </div>
        <div v-else-if="facturaData" class="space-y-6">
            <!-- Información de la Factura -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold mb-4 text-gray-800">Información de la Factura</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Razón Social</label>
                        <p class="text-sm text-gray-800">{{ facturaData.razonSocial }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Código</label>
                        <p class="text-sm text-gray-800">{{ facturaData.codigo }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">RUC</label>
                        <p class="text-sm text-gray-800">{{ facturaData.RUC_client }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Monto Factura</label>
                        <p class="text-sm text-gray-800">{{ formatCurrency(facturaData.montoFactura, facturaData.moneda) }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Monto Disponible</label>
                        <p class="text-sm text-gray-800">{{ formatCurrency(facturaData.montoDisponible, facturaData.moneda) }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Tasa</label>
                        <p class="text-sm text-gray-800">{{ facturaData.tasa }}%</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Fecha de Pago</label>
                        <p class="text-sm text-gray-800">{{ facturaData.fechaPago }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Estado</label>
                        <Tag :value="getStatusLabel(facturaData.estado)" :severity="getStatusSeverity(facturaData.estado)" />
                    </div>
                </div>
            </div>

            <!-- Tabla de Inversionistas -->
            <div>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Inversionistas</h3>
                    <Tag severity="contrast" :value="facturaData.investments?.length || 0" />
                </div>

                <DataTable :value="facturaData.investments" class="p-datatable-sm"
                    :paginator="(facturaData.investments?.length || 0) > 10" :rows="10"
                    :rowsPerPageOptions="[5, 10, 20, 50]" scrollable scrollHeight="400px">
                    <template #empty>
                        <div class="text-center p-4">
                            <i class="pi pi-users text-4xl text-gray-400 mb-4 block"></i>
                            <p class="text-gray-500">No hay inversionistas registrados</p>
                        </div>
                    </template>

                    <Column field="inversionista" header="Inversionista" style="min-width: 15rem" />
                    <Column field="document" header="Documento" style="min-width: 8rem" />
                    <Column field="correo" header="Correo" style="min-width: 12rem" />
                    <Column field="telephone" header="Teléfono" style="min-width: 10rem" />
                    <Column field="amount" header="Monto Invertido" style="min-width: 10rem">
                        <template #body="slotProps">
                            {{ formatCurrency(slotProps.data.amount, slotProps.data.currency) }}
                        </template>
                    </Column>
                    <Column field="return" header="Retorno" style="min-width: 8rem">
                        <template #body="slotProps">
                            {{ formatCurrency(slotProps.data.return, slotProps.data.currency) }}
                        </template>
                    </Column>
                    <Column field="rate" header="Tasa (%)" style="min-width: 6rem" />
                    <Column field="due_date" header="Fecha Pago" style="min-width: 10rem" />
                    <Column field="creacion" header="Fecha Creación" style="min-width: 12rem" />
                    <Column style="min-width: 6rem">
                        <template #body="slotProps">
                            <div class="flex gap-1">
                                <!-- Botón de solicitar reembolso -->
                                <Button 
                                    icon="pi pi-money-bill" 
                                    severity="warn" 
                                    size="small" 
                                    text
                                    v-tooltip.top="'Solicitar reembolso'" 
                                    @click="openRefundDialog(slotProps.data)"
                                    v-if="['active', 'paid'].includes(slotProps.data.status)" 
                                />

                                <Button 
                                    icon="pi pi-eye" 
                                    severity="info" 
                                    size="small" 
                                    text
                                    v-tooltip.top="'Ver detalles'" 
                                    @click="viewDetails(slotProps.data)" 
                                />

                                <!-- Botón de confirmar -->
                                <Button 
                                    icon="pi pi-check" 
                                    severity="success" 
                                    size="small" 
                                    text
                                    v-tooltip.top="'Confirmar'" 
                                    @click="confirmRefund(slotProps.data)" 
                                    v-if="slotProps.data.status === 'pending'" 
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cerrar" text icon="pi pi-times" severity="secondary" @click="onCancel" />
                <Button label="Anular" severity="danger" icon="pi pi-ban" @click="openAnnulDialog" />
            </div>
        </template>
    </Dialog>

    <!-- Dialog de Anulación de Factura -->
    <Dialog v-model:visible="annulDialogVisible" modal :closable="true" :draggable="false" class="mx-4"
        style="width: 90vw; max-width: 600px;" @hide="closeAnnulDialog">
        <template #header>
            <div class="flex items-center gap-2">
                <i class="pi pi-ban text-xl text-red-600"></i>
                <span class="text-xl font-semibold text-red-800">Anular Factura</span>
            </div>
        </template>
        
        <div v-if="facturaData" class="space-y-6">
            <!-- Información de la Factura a Anular -->
            <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                <h4 class="text-md font-semibold mb-3 text-red-800 flex items-center gap-2">
                    <i class="pi pi-exclamation-triangle"></i>
                    Información de la Factura
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium text-red-600">Código Actual</label>
                        <p class="text-sm text-gray-800 font-mono">{{ facturaData.codigo }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-red-600">Razón Social</label>
                        <p class="text-sm text-gray-800">{{ facturaData.razonSocial }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-red-600">Monto</label>
                        <p class="text-sm text-gray-800">{{ formatCurrency(facturaData.montoFactura, facturaData.moneda) }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-red-600">Numero de la factura</label>
                        <p class="text-sm text-gray-800 font-mono">{{ facturaData.invoice_number }}</p>
                    </div>
                </div>
            </div>

            <!-- Información del Nuevo Código -->
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <h4 class="text-md font-semibold mb-3 text-blue-800 flex items-center gap-2">
                    <i class="pi pi-refresh"></i>
                    Nuevo Código de Factura
                </h4>
                <div class="text-center">
                    <label class="text-sm font-medium text-blue-600 block mb-2">El código será actualizado a:</label>
                    <div class="bg-white p-3 rounded border-2 border-blue-300 inline-block">
                        <span class="text-lg font-mono font-bold text-blue-800">
                            {{ generateNewCode(facturaData.codigo) }}
                        </span>
                    </div>
                </div>
                <br>
                <div class="text-center">
                    <label class="text-sm font-medium text-blue-600 block mb-2">El numero de la factura será actualizado a:</label>
                    <div class="bg-white p-3 rounded border-2 border-blue-300 inline-block">
                        <span class="text-lg font-mono font-bold text-blue-800">
                            {{ generateNewCode(facturaData.invoice_number) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Advertencia -->
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                <div class="flex items-start gap-3">
                    <i class="pi pi-exclamation-triangle text-yellow-600 mt-1"></i>
                    <div>
                        <h5 class="font-medium text-yellow-800 mb-1">¡Atención!</h5>
                        <ul class="text-sm text-yellow-700 space-y-1">
                            <li>• Esta acción no se puede deshacer</li>
                            <li>• La factura quedará marcada como anulada</li>
                            <li>• Se liberará el código actual y se asignará uno nuevo</li>
                            <li>• Los inversionistas serán notificados del cambio</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Formulario de Anulación -->
            <div>
                <div class="field">
                    <label for="annulComment" class="block text-sm font-medium mb-2">
                        <i class="pi pi-comment mr-1"></i>
                        Comentario de Anulación <span class="text-red-500">*</span>
                    </label>
                    <Textarea 
                        id="annulComment" 
                        v-model="annulForm.comment" 
                        rows="4" 
                        class="w-full"
                        :class="{ 'p-invalid': annulFormErrors.comment }"
                        placeholder="Ingrese el motivo de la anulación de la factura"
                        :maxlength="500" 
                    />
                    <div class="flex justify-between items-center mt-1">
                        <small v-if="annulFormErrors.comment" class="p-error">
                            {{ annulFormErrors.comment }}
                        </small>
                        <small class="text-gray-500 ml-auto">
                            {{ annulForm.comment?.length || 0 }}/500
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between items-center w-full">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <i class="pi pi-info-circle"></i>
                    <span>Acción irreversible</span>
                </div>
                <div class="flex gap-2">
                    <Button 
                        label="Cancelar" 
                        icon="pi pi-times" 
                        severity="secondary" 
                        text 
                        @click="closeAnnulDialog" 
                        :disabled="processingAnnul" 
                    />
                    <Button 
                        label="Anular Factura" 
                        icon="pi pi-ban" 
                        severity="danger" 
                        @click="confirmAnnulInvoice" 
                        :loading="processingAnnul"
                        :disabled="!isAnnulFormValid"
                    />
                </div>
            </div>
        </template>
    </Dialog>

    <!-- Dialog de Confirmación de Anulación -->
    <Dialog v-model:visible="confirmAnnulDialogVisible" modal header="Confirmar Anulación" 
        style="width: 500px;" class="mx-4">
        <div class="flex items-start gap-3">
            <i class="pi pi-exclamation-triangle text-red-500 text-2xl"></i>
            <div>
                <p class="mb-3">
                    ¿Está completamente seguro que desea anular la factura 
                    <strong>{{ facturaData?.codigo }}</strong>?
                </p>
                <div class="bg-gray-50 p-3 rounded mt-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">Cambios que se realizarán:</p>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Código actual: <span class="font-mono">{{ facturaData?.codigo }}</span></li>
                        <li>• Nuevo código: <span class="font-mono">{{ generateNewCode(facturaData?.codigo) }}</span></li>
                        <li>• Numero de factura actual: <span class="font-mono">{{ facturaData?.invoice_number }}</span></li>
                        <li>• Nuevo numero de factura: <span class="font-mono">{{ generateNewCode(facturaData?.invoice_number) }}</span></li>
                        <li>• Estado: Anulada</li>
                    </ul>
                </div>
                <p class="text-sm text-red-600 mt-3 font-medium">
                    Esta acción es irreversible y no se puede deshacer.
                </p>
            </div>
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <Button 
                    label="Cancelar" 
                    severity="secondary" 
                    text 
                    @click="confirmAnnulDialogVisible = false" 
                    :disabled="processingAnnul"
                />
                <Button 
                    label="Sí, Anular Factura" 
                    severity="danger" 
                    @click="processAnnulInvoice" 
                    :loading="processingAnnul" 
                />
            </div>
        </template>
    </Dialog>

    <!-- Dialog de Solicitud de Reembolso -->
    <Dialog v-model:visible="refundDialogVisible" modal :closable="true" :draggable="false" class="mx-4"
        style="width: 95vw; max-width: 900px;" @hide="closeRefundDialog">
        <template #header>
            <div class="flex items-center gap-2">
                <i class="pi pi-money-bill text-xl text-orange-600"></i>
                <span class="text-xl font-semibold">Solicitar Reembolso</span>
            </div>
        </template>
        
        <div v-if="selectedInvestment" class="space-y-6">
            <!-- Información del Inversionista -->
            <div class="bg-orange-50 p-4 rounded-lg border border-orange-200">
                <h4 class="text-md font-semibold mb-3 text-orange-800 flex items-center gap-2">
                    <i class="pi pi-user"></i>
                    Información del Inversionista
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium text-orange-600">Inversionista</label>
                        <p class="text-sm text-gray-800">{{ selectedInvestment.inversionista }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-orange-600">Documento</label>
                        <p class="text-sm text-gray-800">{{ selectedInvestment.document }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-orange-600">Monto Invertido</label>
                        <p class="text-sm text-gray-800">{{ formatCurrency(selectedInvestment.amount, selectedInvestment.currency) }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-orange-600">Total a Reembolsar</label>
                        <p class="text-sm text-gray-800 font-semibold">
                            {{ formatCurrency(selectedInvestment.amount, selectedInvestment.currency) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Selección de Cuenta Bancaria -->
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <h4 class="text-md font-semibold mb-3 text-blue-800 flex items-center gap-2">
                    <i class="pi pi-credit-card"></i>
                    Cuenta Bancaria de Destino
                </h4>
                
                <div v-if="selectedInvestment.bank_accounts && selectedInvestment.bank_accounts.length > 0">
                    <div class="field">
                        <label for="bankAccountSelect" class="block text-sm font-medium mb-2">
                            Seleccionar Cuenta <span class="text-red-500">*</span>
                        </label>
                        <Dropdown
                            id="bankAccountSelect"
                            v-model="refundForm.selectedBankAccount"
                            :options="selectedInvestment.bank_accounts"
                            optionLabel="displayLabel"
                            placeholder="Seleccione una cuenta bancaria"
                            class="w-full"
                            :class="{ 'p-invalid': formErrors.selectedBankAccount }"
                        >
                            <template #option="slotProps">
                                <div class="flex items-center gap-3 p-2">
                                    <i class="pi pi-building text-blue-600"></i>
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-800">{{ slotProps.option.bank }}</div>
                                        <div class="text-sm text-gray-600">
                                            {{ getAccountTypeLabel(slotProps.option.type) }} - {{ slotProps.option.currency }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ maskAccountNumber(slotProps.option.cc) }} | {{ slotProps.option.alias }}
                                        </div>
                                    </div>
                                    <div>
                                        <Tag 
                                            :value="getAccountStatusLabel(slotProps.option.status)" 
                                            :severity="getAccountStatusSeverity(slotProps.option.status)" 
                                            class="text-xs" 
                                        />
                                    </div>
                                </div>
                            </template>
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center gap-2">
                                    <i class="pi pi-building text-blue-600"></i>
                                    <span>{{ slotProps.value.bank }} - {{ maskAccountNumber(slotProps.value.cc) }}</span>
                                </div>
                            </template>
                        </Dropdown>
                        <small v-if="formErrors.selectedBankAccount" class="p-error">
                            {{ formErrors.selectedBankAccount }}
                        </small>
                    </div>

                    <!-- Detalles de la cuenta seleccionada -->
                    <div v-if="refundForm.selectedBankAccount" class="mt-4 bg-white p-3 rounded border">
                        <h5 class="text-sm font-medium text-gray-700 mb-2">Detalles de la cuenta seleccionada:</h5>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                            <div>
                                <label class="text-xs font-medium text-gray-500">Banco</label>
                                <p class="text-gray-800">{{ refundForm.selectedBankAccount.bank }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500">Tipo de Cuenta</label>
                                <p class="text-gray-800">{{ getAccountTypeLabel(refundForm.selectedBankAccount.type) }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500">Número de Cuenta</label>
                                <p class="text-gray-800 font-mono">{{ refundForm.selectedBankAccount.cc }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500">CCI</label>
                                <p class="text-gray-800 font-mono">{{ refundForm.selectedBankAccount.cci }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500">Alias</label>
                                <p class="text-gray-800">{{ refundForm.selectedBankAccount.alias }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500">Moneda</label>
                                <p class="text-gray-800">{{ refundForm.selectedBankAccount.currency }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div v-else class="text-center py-4">
                    <i class="pi pi-exclamation-triangle text-orange-500 text-2xl mb-2 block"></i>
                    <p class="text-orange-700 font-medium">Sin cuentas bancarias registradas</p>
                    <p class="text-sm text-orange-600 mt-1">
                        El inversionista debe registrar al menos una cuenta bancaria para procesar reembolsos
                    </p>
                </div>
            </div>

            <!-- Alerta de Proceso de Aprobación -->
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <div class="flex items-start gap-3">
                    <i class="pi pi-info-circle text-blue-600 mt-1"></i>
                    <div>
                        <h5 class="font-medium text-blue-800 mb-1">Proceso de Aprobación</h5>
                        <p class="text-sm text-blue-700">
                            Esta solicitud será enviada para aprobación y no se procesará inmediatamente. 
                            Recibirás una notificación una vez que sea aprobada o rechazada.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Formulario de Reembolso -->
            <div v-if="selectedInvestment.bank_accounts && selectedInvestment.bank_accounts.length > 0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="field">
                        <label for="refundDate" class="block text-sm font-medium mb-2">
                            <i class="pi pi-calendar mr-1"></i>
                            Fecha de Pago <span class="text-red-500">*</span>
                        </label>
                        <Calendar id="refundDate" v-model="refundForm.payDate" dateFormat="dd-mm-yy" 
                            :showIcon="true" class="w-full" :class="{ 'p-invalid': formErrors.payDate }" />
                        <small v-if="formErrors.payDate" class="p-error">{{ formErrors.payDate }}</small>
                    </div>

                    <div class="field">
                        <label for="refundAmount" class="block text-sm font-medium mb-2">
                            <i class="pi pi-dollar mr-1"></i>
                            Monto del Reembolso <span class="text-red-500">*</span>
                        </label>
                        <InputNumber id="refundAmount" v-model="refundForm.amount" mode="currency"
                            :currency="selectedInvestment.currency" locale="es-PE" :min="0" class="w-full"
                            :class="{ 'p-invalid': formErrors.amount }" disabled />
                        <small v-if="formErrors.amount" class="p-error">{{ formErrors.amount }}</small>
                    </div>

                    <div class="field md:col-span-2">
                        <label for="refundOperationNumber" class="block text-sm font-medium mb-2">
                            <i class="pi pi-hashtag mr-1"></i>
                            Número de Operación <span class="text-red-500">*</span>
                        </label>
                        <InputText id="refundOperationNumber" v-model="refundForm.operationNumber" class="w-full"
                            :class="{ 'p-invalid': formErrors.operationNumber }"
                            placeholder="Ingrese el número de operación bancaria" />
                        <small v-if="formErrors.operationNumber" class="p-error">{{ formErrors.operationNumber }}</small>
                    </div>

                    <div class="field md:col-span-2">
                        <label class="block text-sm font-medium mb-2">
                            <i class="pi pi-file mr-1"></i>
                            Comprobante de Pago <span class="text-red-500">*</span>
                        </label>
                        <FileUpload ref="refundFileUpload" mode="advanced" accept=".pdf,.jpg,.jpeg,.png"
                            :maxFileSize="10240000" :fileLimit="1" @select="onRefundFilesSelect"
                            @remove="onRefundFileRemove" :class="{ 'p-invalid': formErrors.receipt }" :auto="false"
                            chooseLabel="Seleccionar Comprobante" uploadLabel="Subir" cancelLabel="Cancelar">
                            <template #empty>
                                <div class="text-center py-6">
                                    <i class="pi pi-cloud-upload text-4xl text-gray-400"></i>
                                    <p class="mt-2 text-sm text-gray-600">
                                        Selecciona el comprobante de pago
                                    </p>
                                    <p class="text-xs mt-1 text-gray-500">
                                        Formatos: PDF, JPG, PNG (Max 10MB)
                                    </p>
                                </div>
                            </template>
                        </FileUpload>
                        <small v-if="formErrors.receipt" class="p-error">{{ formErrors.receipt }}</small>
                    </div>

                    <div class="field md:col-span-2">
                        <label for="refundComments" class="block text-sm font-medium mb-2">
                            <i class="pi pi-comment mr-1"></i>
                            Comentarios
                        </label>
                        <Textarea id="refundComments" v-model="refundForm.comments" rows="4" class="w-full"
                            placeholder="Ingrese comentarios sobre el reembolso (opcional)" :maxlength="500" />
                        <div class="text-right">
                            <small class="text-gray-500">
                                {{ refundForm.comments?.length || 0 }}/500
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between items-center w-full">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <i class="pi pi-clock"></i>
                    <span>Pendiente de aprobación tras envío</span>
                </div>
                <div class="flex gap-2">
                    <Button label="Cancelar" icon="pi pi-times" severity="secondary" text 
                        @click="closeRefundDialog" :disabled="processingRefund" />
                    <Button label="Enviar Solicitud" icon="pi pi-send" severity="warn" 
                        @click="submitRefundRequest" :loading="processingRefund" 
                        :disabled="!isRefundFormValid || !hasValidBankAccounts" />
                </div>
            </div>
        </template>
    </Dialog>

    <!-- Dialog de Confirmación -->
    <Dialog v-model:visible="confirmDialogVisible" modal header="Confirmar Solicitud" 
        style="width: 500px;" class="mx-4">
        <div class="flex items-start gap-3">
            <i class="pi pi-exclamation-triangle text-orange-500 text-2xl"></i>
            <div>
                <p class="mb-3">
                    ¿Está seguro que desea enviar la solicitud de reembolso por 
                    <strong>{{ formatCurrency(refundForm.amount, selectedInvestment?.currency) }}</strong>?
                </p>
                <div v-if="refundForm.selectedBankAccount" class="bg-gray-50 p-3 rounded mt-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">Cuenta de destino:</p>
                    <p class="text-sm text-gray-600">
                        {{ refundForm.selectedBankAccount.bank }} - 
                        {{ maskAccountNumber(refundForm.selectedBankAccount.cc) }}
                    </p>
                </div>
                <p class="text-sm text-gray-600 mt-3">
                    Esta acción no se puede deshacer y la solicitud quedará pendiente de aprobación.
                </p>
            </div>
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cancelar" severity="secondary" text @click="confirmDialogVisible = false" />
                <Button label="Confirmar Envío" severity="warn" @click="processRefundRequest" 
                    :loading="processingRefund" />
            </div>
        </template>
    </Dialog>
    <showDepostis
        v-model="depositsDialogVisible"
        :investorId="selectedInvestorId"
        @cancelled="onDepositsDialogCancelled"
    />
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import FileUpload from 'primevue/fileupload';
import Calendar from 'primevue/calendar';
import Dropdown from 'primevue/dropdown';
import { useToast } from 'primevue/usetoast';
import showDepostis from './showDepostis.vue';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    facturaId: {
        type: String,
        default: null
    }
});

const emit = defineEmits(['update:modelValue', 'cancelled', 'refundRequested', 'invoiceAnnulled']);

const toast = useToast();
const loading = ref(false);
const facturaData = ref(null);

const refundDialogVisible = ref(false);
const confirmDialogVisible = ref(false);
const selectedInvestment = ref(null);
const processingRefund = ref(false);
const refundFileUpload = ref(null);
const depositsDialogVisible = ref(false)
const selectedInvestorId = ref(null)

// Variables para anulación de factura
const annulDialogVisible = ref(false);
const confirmAnnulDialogVisible = ref(false);
const processingAnnul = ref(false);

const annulForm = ref({
    comment: ''
});

const annulFormErrors = ref({
    comment: ''
});

const refundForm = ref({
    payDate: new Date(),
    amount: null,
    operationNumber: '',
    receipt: null,
    comments: '',
    selectedBankAccount: null
});

const formErrors = ref({
    amount: '',
    operationNumber: '',
    receipt: '',
    payDate: '',
    selectedBankAccount: ''
});

const dialogVisible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

const hasValidBankAccounts = computed(() => {
    return selectedInvestment.value?.bank_accounts && 
           selectedInvestment.value.bank_accounts.length > 0;
});

const isRefundFormValid = computed(() => {
    return refundForm.value.amount > 0 &&
        refundForm.value.operationNumber.trim() !== '' &&
        refundForm.value.receipt !== null &&
        refundForm.value.payDate !== null &&
        refundForm.value.selectedBankAccount !== null;
});

const isAnnulFormValid = computed(() => {
    return annulForm.value.comment && annulForm.value.comment.trim().length >= 10;
});

const formatCurrency = (value, moneda) => {
    if (!value) return '';
    const number = parseFloat(value);
    let currencySymbol = '';
    if (moneda === 'PEN') currencySymbol = 'S/';
    if (moneda === 'USD') currencySymbol = 'US';
    return `${currencySymbol} ${number.toLocaleString('es-PE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })}`;
};

function getStatusLabel(status) {
    const statusLabels = {
        'inactive': 'Inactivo',
        'active': 'Activo',
        'expired': 'Vencido',
        'judicialized': 'Judicializado',
        'reprogramed': 'Reprogramado',
        'paid': 'Pagado',
        'canceled': 'Cancelado',
        'daStandby': 'En Standby'
    };
    return statusLabels[status] || status;
}

function getStatusSeverity(status) {
    switch (status) {
        case 'inactive': return 'secondary';
        case 'active': return 'success';
        case 'expired': return 'danger';
        case 'judicialized': return 'warn';
        case 'reprogramed': return 'info';
        case 'paid': return 'contrast';
        case 'canceled': return 'danger';
        case 'daStandby': return 'warn';
        default: return 'secondary';
    }
}

function getAccountTypeLabel(type) {
    const typeLabels = {
        'corriente': 'Cuenta Corriente',
        'ahorro': 'Cuenta de Ahorros',
        'plazo_fijo': 'Plazo Fijo',
        'maestra': 'Cuenta Maestra'
    };
    return typeLabels[type] || type;
}

function getAccountStatusLabel(status) {
    const statusLabels = {
        'active': 'Activa',
        'inactive': 'Inactiva',
        'pending': 'Pendiente',
        'pre_approved': 'Pre-aprobada',
        'approved': 'Aprobada',
        'rejected': 'Rechazada'
    };
    return statusLabels[status] || status;
}

function getAccountStatusSeverity(status) {
    switch (status) {
        case 'active': return 'success';
        case 'approved': return 'success';
        case 'pre_approved': return 'info';
        case 'pending': return 'warn';
        case 'inactive': return 'secondary';
        case 'rejected': return 'danger';
        default: return 'secondary';
    }
}

function maskAccountNumber(accountNumber) {
    if (!accountNumber) return '';
    const str = accountNumber.toString();
    if (str.length <= 4) return str;
    return str.slice(0, 4) + '*'.repeat(str.length - 8) + str.slice(-4);
}

function viewDetails(investment) {
    selectedInvestorId.value = investment.id; // Usar el ID del investment (2), no el investor_id
    depositsDialogVisible.value = true;
}

function onDepositsDialogCancelled() {
    depositsDialogVisible.value = false;
    selectedInvestorId.value = null;
}

function generateNewCode(currentCode) {
    if (!currentCode) return '';
    // Agregar sufijo -ANULADA al código actual
    return `${currentCode}-ANULADA`;
}

function openAnnulDialog() {
    annulForm.value = {
        comment: ''
    };
    resetAnnulFormErrors();
    annulDialogVisible.value = true;
}

function closeAnnulDialog() {
    annulDialogVisible.value = false;
    confirmAnnulDialogVisible.value = false;
    annulForm.value = {
        comment: ''
    };
    resetAnnulFormErrors();
}

function resetAnnulFormErrors() {
    annulFormErrors.value = {
        comment: ''
    };
}

function validateAnnulForm() {
    resetAnnulFormErrors();
    let isValid = true;

    if (!annulForm.value.comment || annulForm.value.comment.trim().length < 10) {
        annulFormErrors.value.comment = 'El comentario es requerido y debe tener al menos 10 caracteres';
        isValid = false;
    }

    return isValid;
}

function confirmAnnulInvoice() {
    if (!validateAnnulForm()) {
        return;
    }
    confirmAnnulDialogVisible.value = true;
}

async function processAnnulInvoice() {
    confirmAnnulDialogVisible.value = false;
    processingAnnul.value = true;

    try {
        const response = await axios.post(`/invoices/${props.facturaId}/anular`, {
            comment: annulForm.value.comment.trim()
        });

        toast.add({
            severity: 'success',
            summary: 'Factura Anulada',
            detail: 'La factura ha sido anulada correctamente',
            life: 5000
        });

        // Recargar los datos para mostrar el nuevo estado
        await loadFacturaData();
        
        // Cerrar el diálogo de anulación
        closeAnnulDialog();

        // Emitir evento para que el componente padre pueda reaccionar
        emit('invoiceAnnulled', {
            invoiceId: props.facturaId,
            oldCode: facturaData.value?.codigo,
            numeroFactura: facturaData.value?.numeroFactura,
            newCode: response.data.invoice?.codigo,
            response: response.data
        });

    } catch (error) {
        console.error('Error al anular factura:', error);
        let errorMessage = 'Error al anular la factura';
        
        if (error.response?.data?.error) {
            errorMessage = error.response.data.error;
        } else if (error.response?.data?.message) {
            errorMessage = error.response.data.message;
        }

        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage,
            life: 5000
        });
    } finally {
        processingAnnul.value = false;
    }
}

function openRefundDialog(investment) {
    selectedInvestment.value = investment;
    
    // Preparar las cuentas bancarias con etiquetas para el dropdown
    if (investment.bank_accounts) {
        investment.bank_accounts.forEach(account => {
            account.displayLabel = `${account.bank} - ${maskAccountNumber(account.cc)} (${account.alias})`;
        });
    }
    
    // Solo el monto invertido, sin retorno
    const refundAmount = parseFloat(investment.amount);
    refundForm.value = {
        payDate: new Date(),
        amount: refundAmount,
        operationNumber: '',
        receipt: null,
        comments: '',
        selectedBankAccount: null
    };
    resetFormErrors();
    refundDialogVisible.value = true;
}

function closeRefundDialog() {
    refundDialogVisible.value = false;
    confirmDialogVisible.value = false;
    selectedInvestment.value = null;
    refundForm.value = {
        payDate: new Date(),
        amount: null,
        operationNumber: '',
        receipt: null,
        comments: '',
        selectedBankAccount: null
    };
    resetFormErrors();
    if (refundFileUpload.value) {
        refundFileUpload.value.clear();
    }
}

function resetFormErrors() {
    formErrors.value = {
        amount: '',
        operationNumber: '',
        receipt: '',
        payDate: '',
        selectedBankAccount: ''
    };
}

function onRefundFilesSelect(event) {
    if (event.files && event.files.length > 0) {
        refundForm.value.receipt = event.files[0];
        if (formErrors.value.receipt) {
            formErrors.value.receipt = '';
        }
    }
}

function onRefundFileRemove() {
    refundForm.value.receipt = null;
}

function validateRefundForm() {
    resetFormErrors();
    let isValid = true;

    if (!refundForm.value.amount || refundForm.value.amount <= 0) {
        formErrors.value.amount = 'El monto es requerido y debe ser mayor a 0';
        isValid = false;
    }

    if (!refundForm.value.operationNumber.trim()) {
        formErrors.value.operationNumber = 'El número de operación es requerido';
        isValid = false;
    }

    if (!refundForm.value.receipt) {
        formErrors.value.receipt = 'Debe adjuntar el comprobante de pago';
        isValid = false;
    }

    if (!refundForm.value.payDate) {
        formErrors.value.payDate = 'La fecha de pago es requerida';
        isValid = false;
    }

    if (!refundForm.value.selectedBankAccount) {
        formErrors.value.selectedBankAccount = 'Debe seleccionar una cuenta bancaria';
        isValid = false;
    }

    return isValid;
}

function submitRefundRequest() {
    if (!validateRefundForm()) {
        return;
    }
    confirmDialogVisible.value = true;
}

async function processRefundRequest() {
    confirmDialogVisible.value = false;
    processingRefund.value = true;

    try {
        const formData = new FormData();
        formData.append('invoice_id', props.facturaId);
        formData.append('pay_type', 'reembloso');
        formData.append('pay_date', refundForm.value.payDate.toISOString().split('T')[0]);
        formData.append('currency', selectedInvestment.value?.currency || 'PEN');
        formData.append('investor_id', selectedInvestment.value?.investor_id);
        formData.append('amount', refundForm.value.amount);
        formData.append('nro_operation', refundForm.value.operationNumber);
        
        // Agregar el ID de la cuenta bancaria seleccionada
        formData.append('bank_account_id', refundForm.value.selectedBankAccount.id);

        if (refundForm.value.comments) {
            formData.append('comment', refundForm.value.comments);
        }

        if (refundForm.value.receipt) {
            formData.append('resource_path', refundForm.value.receipt);
        }

        const response = await axios.post(
            `/payments/${props.facturaId}/reembloso`,
            formData,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );

        toast.add({
            severity: 'success',
            summary: 'Solicitud Enviada',
            detail: 'La solicitud de reembolso ha sido enviada para aprobación',
            life: 5000
        });

        emit('refundRequested', {
            investment: selectedInvestment.value,
            refundData: refundForm.value,
            response: response.data
        });

        closeRefundDialog();

    } catch (error) {
        console.error('Error al enviar solicitud de reembolso:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.error || 'Error al enviar la solicitud de reembolso',
            life: 5000
        });
    } finally {
        processingRefund.value = false;
    }
}

async function confirmRefund(investment) {
    try {
        // Mostrar diálogo de confirmación
        const confirmed = confirm('¿Está seguro que desea confirmar este reembolso?');
        if (!confirmed) return;

        const response = await axios.post(`/payments/${investment.payment_id}/approve`, {
            status: 'approved',
            comment: 'Reembolso confirmado desde la gestión de pagos'
        });

        toast.add({
            severity: 'success',
            summary: 'Reembolso Confirmado',
            detail: 'El reembolso ha sido aprobado correctamente',
            life: 5000
        });

        // Recargar los datos de la factura para reflejar los cambios
        loadFacturaData();

    } catch (error) {
        console.error('Error al confirmar reembolso:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.error || 'Error al confirmar el reembolso',
            life: 5000
        });
    }
}

async function loadFacturaData() {
    if (!props.facturaId) return;

    loading.value = true;
    try {
        const response = await axios.get(`/invoices/${props.facturaId}`);
        facturaData.value = response.data.data;
    } catch (error) {
        console.error('Error al cargar datos de la factura:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error al cargar los datos de la factura',
            life: 3000
        });
    } finally {
        loading.value = false;
    }
}

function onCancel() {
    dialogVisible.value = false;
    emit('cancelled');
}

watch(() => props.modelValue, (newValue) => {
    if (newValue && props.facturaId) {
        loadFacturaData();
    }
});

watch(() => props.facturaId, () => {
    facturaData.value = null;
});
</script>

<style scoped>
.field {
    margin-bottom: 1rem;
}

.p-invalid {
    border-color: #ef4444;
}

.p-error {
    color: #ef4444;
    font-size: 0.75rem;
}

/* Estilos para el dropdown */
:deep(.p-dropdown-panel) {
    max-width: 600px;
}

:deep(.p-dropdown-item) {
    padding: 0.75rem !important;
}

:deep(.p-dropdown-item:hover) {
    background-color: #f3f4f6 !important;
}
</style>