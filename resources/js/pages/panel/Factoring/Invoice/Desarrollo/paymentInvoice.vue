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
                                <Button 
                                    icon="pi pi-eye" 
                                    severity="info" 
                                    size="small" 
                                    text
                                    v-tooltip.top="'Ver detalles'" 
                                    @click="viewDetails(slotProps.data)" 
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
                <Button 
                    v-if="showAnnulButton"
                    label="Anular" 
                    severity="danger" 
                    icon="pi pi-ban" 
                    @click="openAnnulDialog" 
                />
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
import Textarea from 'primevue/textarea';
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

// Computed property para controlar la visibilidad del botón de anular
const showAnnulButton = computed(() => {
    if (!facturaData.value) return false;
    return facturaData.value.estado !== 'rejected' && facturaData.value.estado !== 'canceled';
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
        'rejected': 'Anulado',
        'daStandby': 'En Standby'
    };
    return statusLabels[status] || status;
}

function getStatusSeverity(status) {
    switch (status) {
        case 'inactive': return 'secondary';
        case 'active': return 'success';
        case 'expired': return 'danger';
        case 'rejected': return 'danger';
        case 'judicialized': return 'warn';
        case 'reprogramed': return 'info';
        case 'paid': return 'contrast';
        case 'canceled': return 'danger';
        case 'daStandby': return 'warn';
        default: return 'secondary';
    }
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
    // Validación adicional de seguridad
    if (facturaData.value?.estado === 'rejected' || facturaData.value?.estado === 'canceled') {
        toast.add({
            severity: 'warn',
            summary: 'Acción no permitida',
            detail: 'La factura ya se encuentra anulada o cancelada',
            life: 3000
        });
        return;
    }
    
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
        await loadFacturaData();
        closeAnnulDialog();
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