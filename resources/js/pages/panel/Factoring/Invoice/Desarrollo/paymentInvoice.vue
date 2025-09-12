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
                    <Column style="min-width: 3rem">
                        <template #body="slotProps">
                            <div class="flex gap-1">
                                <Button icon="pi pi-money-bill" severity="warn" size="small" text
                                    v-tooltip.top="'Solicitar reembolso'" @click="openRefundDialog(slotProps.data)"
                                    v-if="['active', 'paid'].includes(slotProps.data.status)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cerrar" text icon="pi pi-times" severity="secondary" @click="onCancel" />
            </div>
        </template>
    </Dialog>

    <!-- Dialog de Solicitud de Reembolso -->
    <Dialog v-model:visible="refundDialogVisible" modal :closable="true" :draggable="false" class="mx-4"
        style="width: 90vw; max-width: 600px;" @hide="closeRefundDialog">
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
                            {{ formatCurrency(selectedInvestment.amount + selectedInvestment.return, selectedInvestment.currency) }}
                        </p>
                    </div>
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

            <!-- Formulario -->
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
                        :disabled="!isRefundFormValid" />
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
                <p class="text-sm text-gray-600">
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
import { useToast } from 'primevue/usetoast';

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

const emit = defineEmits(['update:modelValue', 'cancelled', 'refundRequested']);

const toast = useToast();
const loading = ref(false);
const facturaData = ref(null);

const refundDialogVisible = ref(false);
const confirmDialogVisible = ref(false);
const selectedInvestment = ref(null);
const processingRefund = ref(false);
const refundFileUpload = ref(null);

const refundForm = ref({
    payDate: new Date(),
    amount: null,
    operationNumber: '',
    receipt: null,
    comments: ''
});

const formErrors = ref({
    amount: '',
    operationNumber: '',
    receipt: '',
    payDate: ''
});

const dialogVisible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

const isRefundFormValid = computed(() => {
    return refundForm.value.amount > 0 &&
        refundForm.value.operationNumber.trim() !== '' &&
        refundForm.value.receipt !== null &&
        refundForm.value.payDate !== null;
});

const formatCurrency = (value, moneda) => {
    if (!value) return '';
    const number = parseFloat(value);
    let currencySymbol = '';
    if (moneda === 'PEN') currencySymbol = 'S/';
    if (moneda === 'USD') currencySymbol = 'US$';
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

function openRefundDialog(investment) {
    selectedInvestment.value = investment;
    const totalAmount = parseFloat(investment.amount);
    refundForm.value = {
        payDate: new Date(),
        amount: totalAmount,
        operationNumber: '',
        receipt: null,
        comments: ''
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
        comments: ''
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
        payDate: ''
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

        // ✅ primero currency
        formData.append('currency', selectedInvestment.value?.currency || 'PEN');

        // ✅ investor_id desde la inversión seleccionada
        formData.append('investor_id', selectedInvestment.value?.investor_id);

        // luego amount y demás
        formData.append('amount', refundForm.value.amount);
        formData.append('nro_operation', refundForm.value.operationNumber);

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
</style>