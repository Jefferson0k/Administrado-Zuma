<template>
    <DataTable ref="dt" :value="withdraws" dataKey="id" :paginator="true" :rows="10" :filters="filters"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25]"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} retiros" class="p-datatable-sm">
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Gestionar Retiros</h4>
                <IconField>
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                </IconField>
            </div>
        </template>
        <Column selectionMode="multiple" style="width: 1rem" :exportable="false" />
        <Column field="invesrionista" header="Inversionista" sortable style="min-width: 30rem"></Column>
        <Column field="documento" header="Documento" sortable style="min-width: 7rem"></Column>
        <Column field="currency" header="Moneda" sortable style="min-width: 7rem"></Column>
        <Column field="amount" header="Monto" sortable style="min-width: 10rem">
            <template #body="slotProps">
                {{ formatCurrency(slotProps.data.amount, slotProps.data.currency) }}
            </template>
        </Column>
        <Column field="tipo_banco" header="T. Cuenta" sortable style="min-width: 7rem"></Column>
        <Column field="cc" header="N. de cuenta CC" sortable style="min-width: 10rem"></Column>
        <Column field="cci" header="N. de cuenta interbancario CCI" sortable style="min-width: 17rem"></Column>
        <Column field="created_at" header="Fecha de Creación" sortable style="min-width: 12rem"></Column>
        <Column field="approval1_status" header="1ª Aprobador" sortable style="min-width: 9rem">
            <template #body="slotProps">
                <template v-if="slotProps.data.approval1_status">
                    <Tag :value="getStatusLabel(slotProps.data.approval1_status)"
                        :severity="getStatusSeverity(slotProps.data.approval1_status)" />
                </template>
                <template v-else>
                    <span class="italic text-gray-500">Sin dato</span>
                </template>
            </template>
        </Column>
        <Column field="approval1_by" header="1ª Usuario" sortable style="min-width: 16rem">
            <template #body="slotProps">
                <span :class="slotProps.data.approval1_by === 'Sin aprobar' ? 'italic' : ''">
                    {{ slotProps.data.approval1_by || 'Sin asignar' }}
                </span>
            </template>
        </Column>
        <Column field="approval1_at" header="T. 1ª Aprobación" sortable style="min-width: 12rem">
            <template #body="slotProps">
                <span :class="!slotProps.data.approval1_at ? 'italic' : ''">
                    {{ slotProps.data.approval1_at || 'Sin tiempo' }}
                </span>
            </template>
        </Column>
        <Column field="approval2_status" header="2ª Aprobador" sortable style="min-width: 9rem">
            <template #body="slotProps">
                <template v-if="slotProps.data.approval2_status">
                    <Tag :value="getStatusLabel(slotProps.data.approval2_status)"
                        :severity="getStatusSeverity(slotProps.data.approval2_status)" />
                </template>
                <template v-else>
                    <span class="italic text-gray-500">Sin dato</span>
                </template>
            </template>
        </Column>
        <Column field="approval2_by" header="2do Usuario" sortable style="min-width: 16rem">
            <template #body="slotProps">
                <span :class="slotProps.data.approval2_by === 'Sin aprobar' ? 'italic' : ''">
                    {{ slotProps.data.approval2_by || 'Sin asignar' }}
                </span>
            </template>
        </Column>
        <Column field="approval2_at" header="T. 2ª Aprobación" sortable style="min-width: 12rem">
            <template #body="slotProps">
                <span :class="!slotProps.data.approval2_at ? 'italic' : ''">
                    {{ slotProps.data.approval2_at || 'Sin tiempo' }}
                </span>
            </template>
        </Column>
        <Column field="status" header="Estado Conclusión" sortable style="min-width: 11rem">
            <template #body="slotProps">
                <template v-if="!slotProps.data.status">
                    <span class="italic">Sin estado</span>
                </template>
                <template v-else>
                    <Tag :value="getStatusLabel(slotProps.data.status)"
                        :severity="getStatusSeverity(slotProps.data.status)" />
                </template>
            </template>
        </Column>
        <Column header="" style="min-width: 20rem">
            <template #body="slotProps">
                <div class="flex gap-2">
                    <!-- Botón para primera validación -->
                    <Button 
                        v-if="!slotProps.data.approval1_status"
                        label="1ª Valid." 
                        icon="pi pi-check-circle" 
                        size="small" 
                        severity="warning"
                        @click="openFirstApprovalDialog(slotProps.data)" 
                    />
                    
                    <!-- Botón para segunda validación -->
                    <Button 
                        v-else-if="slotProps.data.approval1_status === 'approved' && !slotProps.data.approval2_status"
                        label="2ª Valid." 
                        icon="pi pi-shield" 
                        size="small" 
                        severity="success"
                        @click="openSecondApprovalDialog(slotProps.data)" 
                    />
                    
                    <!-- Botón Pagar - siempre deshabilitado -->
                    <Button 
                        label="Pagar" 
                        icon="pi pi-credit-card" 
                        size="small" 
                        severity="secondary"
                        disabled
                        class="opacity-60"
                    />

                     <Button 
                        label="Detalles" 
                        icon="pi pi-eye" 
                        size="small" 
                        severity="info"
                        outlined
                        @click="openDetailsDialog(slotProps.data)" 
                    />
                </div>
            </template>
        </Column>
    </DataTable>



    <!-- Dialog para primera validación -->
    <Dialog v-model:visible="firstApprovalDialog" :style="{ width: '600px' }" header="Primera Validación" :modal="true">
        <div class="flex flex-col gap-6">
            <!-- Información del retiro -->
            <div class="flex items-center gap-4 p-4 bg-orange-50 rounded-lg border border-orange-200">
                <i class="pi pi-check-circle text-3xl text-orange-500" />
                <div class="flex-1">
                    <p class="font-semibold text-lg">{{ selectedWithdraw?.invesrionista }}</p>
                    <p class="text-sm text-gray-600">
                        Documento: {{ selectedWithdraw?.documento }}
                    </p>
                    <p class="text-sm text-gray-600">
                        Monto: {{ formatCurrency(selectedWithdraw?.amount, selectedWithdraw?.currency) }}
                    </p>
                    <p class="text-sm text-gray-600">
                        Cuenta: {{ selectedWithdraw?.cc }} | CCI: {{ selectedWithdraw?.cci }}
                    </p>
                </div>
            </div>

            <!-- Sección de Voucher -->
            <div class="border rounded-lg p-4 bg-blue-50/30 border-blue-200">
                <h4 class="font-medium text-gray-800 mb-3 flex items-center gap-2">
                    <i class="pi pi-upload text-blue-500" />
                    Voucher de Transferencia
                </h4>
                
                <div v-if="!voucherForm.file" class="space-y-3">
                    <p class="text-sm text-gray-600 mb-3">
                        Sube el voucher que confirma la transferencia realizada al inversionista
                    </p>
                    
                    <FileUpload 
                        ref="voucherFileUpload" 
                        mode="basic" 
                        name="file" 
                        accept="image/*,application/pdf" 
                        :maxFileSize="2000000"
                        :auto="false" 
                        chooseLabel="Seleccionar voucher" 
                        class="p-button-outlined"
                        @select="onVoucherFileSelect" 
                    />
                    <p class="text-xs text-gray-500">
                        Formatos: JPG, PNG, PDF (máx. 2MB)
                    </p>
                </div>
                
                <div v-else class="space-y-3">
                    <div class="flex items-center gap-3 p-3 bg-green-100 rounded-lg">
                        <i class="pi pi-check-circle text-green-600" />
                        <div class="flex-1">
                            <p class="font-medium text-green-800">{{ voucherForm.file.name }}</p>
                            <p class="text-sm text-green-600">
                                {{ formatFileSize(voucherForm.file.size) }}
                            </p>
                        </div>
                        <Button 
                            icon="pi pi-times" 
                            size="small"
                            severity="danger"
                            text
                            @click="clearSelectedFile" 
                        />
                    </div>
                </div>
                
                <small v-if="firstApprovalSubmitted && !voucherForm.file" class="p-error block mt-2">
                    <i class="pi pi-exclamation-triangle mr-1" />
                    El voucher es requerido para continuar
                </small>
            </div>

            <!-- Formulario de validación -->
            <form @submit.prevent="confirmFirstApproval" class="grid grid-cols-1 gap-4">
                <div class="flex flex-col gap-1">
                    <label for="nro_operation" class="font-medium">
                        Número de Operación <span class="text-red-500">*</span>
                    </label>
                    <InputText 
                        id="nro_operation" 
                        v-model="firstApprovalForm.nro_operation" 
                        placeholder="Ej: OP123456789"
                        maxlength="50"
                        :class="{ 'p-invalid': firstApprovalSubmitted && !firstApprovalForm.nro_operation }" 
                    />
                    <small v-if="firstApprovalSubmitted && !firstApprovalForm.nro_operation" class="p-error">
                        El número de operación es requerido
                    </small>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="deposit_pay_date" class="font-medium">
                        Fecha de Pago <span class="text-red-500">*</span>
                    </label>
                    <DatePicker 
                        id="deposit_pay_date" 
                        v-model="firstApprovalForm.deposit_pay_date" 
                        dateFormat="dd/mm/yy"
                        showIcon 
                        placeholder="Seleccionar fecha"
                        :class="{ 'p-invalid': firstApprovalSubmitted && !firstApprovalForm.deposit_pay_date }" 
                    />
                    <small v-if="firstApprovalSubmitted && !firstApprovalForm.deposit_pay_date" class="p-error">
                        La fecha de pago es requerida
                    </small>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="description" class="font-medium">Descripción</label>
                    <Textarea 
                        id="description" 
                        v-model="firstApprovalForm.description" 
                        rows="3"
                        placeholder="Descripción del pago..." 
                    />
                </div>

                <div class="flex flex-col gap-1">
                    <label for="approval1_comment" class="font-medium">Comentario de Validación (Opcional)</label>
                    <Textarea 
                        id="approval1_comment" 
                        v-model="firstApprovalForm.approval1_comment" 
                        rows="2"
                        placeholder="Comentarios sobre la validación..." 
                    />
                </div>
            </form>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button 
                    label="Cancelar" 
                    icon="pi pi-times" 
                    severity="secondary" 
                    outlined
                    :disabled="firstApprovalProcessing" 
                    @click="closeFirstApprovalDialog" 
                />
                <Button 
                    label="Aprobar Primera Validación" 
                    icon="pi pi-check" 
                    severity="warning" 
                    :loading="firstApprovalProcessing" 
                    @click="confirmFirstApproval" 
                />
            </div>
        </template>
    </Dialog>

    <!-- Dialog para segunda validación -->
    <Dialog v-model:visible="secondApprovalDialog" :style="{ width: '450px' }" header="Segunda Validación" :modal="true">
        <div class="flex flex-col gap-4">
            <div class="flex items-center gap-4 p-4 bg-green-50 rounded">
                <i class="pi pi-shield text-2xl text-green-500" />
                <div>
                    <p class="font-semibold">{{ selectedWithdraw?.invesrionista }}</p>
                    <p class="text-sm text-gray-600">
                        Monto: {{ formatCurrency(selectedWithdraw?.amount, selectedWithdraw?.currency) }}
                    </p>
                    <p class="text-sm text-gray-600">
                        Nº Op: {{ selectedWithdraw?.nro_operation }}
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <label for="approval2_comment" class="font-medium">Comentario de Segunda Validación</label>
                <Textarea 
                    id="approval2_comment" 
                    v-model="secondApprovalForm.approval2_comment" 
                    rows="3"
                    placeholder="Comentarios finales de validación..." 
                />
            </div>

            <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400">
                <p class="text-sm">
                    <strong>⚠️ Importante:</strong> Esta acción aprobará definitivamente el retiro y 
                    marcará el movimiento como válido. Se enviará notificación al inversionista.
                </p>
            </div>
        </div>

        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" text severity="secondary" 
                :disabled="secondApprovalProcessing" @click="closeSecondApprovalDialog" />
            <Button label="Aprobar Definitivamente" icon="pi pi-check" severity="success" 
                :loading="secondApprovalProcessing" @click="confirmSecondApproval" />
        </template>
    </Dialog>
    <!-- Dialog para Ver Detalles del Inversionista -->
<AddWithdraw 
    v-model:visible="detailsDialog" 
    :investor-id="selectedInvestorId"
    @close="closeDetailsDialog" 
/>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Column from 'primevue/column';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import FileUpload from 'primevue/fileupload';
import Textarea from 'primevue/textarea';
import DatePicker from 'primevue/datepicker';
import Tag from 'primevue/tag';
import axios from 'axios';
import AddWithdraw from './AddWithdraw.vue'; 
// Configuración de Axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

axios.interceptors.request.use((config) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token;
    }
    return config;
});

const toast = useToast();
const dt = ref();
const withdraws = ref([]);
const detailsDialog = ref(false);
const selectedInvestorId = ref(''); 
// Estados de diálogos
const voucherDialog = ref(false);
const firstApprovalDialog = ref(false);
const secondApprovalDialog = ref(false);

// Estados de procesamiento
const voucherProcessing = ref(false);
const firstApprovalProcessing = ref(false);
const secondApprovalProcessing = ref(false);

// Estados de validación
const voucherSubmitted = ref(false);
const firstApprovalSubmitted = ref(false);

const selectedWithdraw = ref(null);
const voucherFileUpload = ref();

const filters = ref({
    'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
});

// Formularios
const voucherForm = ref({
    file: null
});

const firstApprovalForm = ref({
    nro_operation: '',
    deposit_pay_date: null,
    description: '',
    approval1_comment: ''
});

const secondApprovalForm = ref({
    approval2_comment: ''
});

const openDetailsDialog = (withdraw) => {
    selectedInvestorId.value = withdraw.investor_id || 
                          withdraw.user_id || 
                          withdraw.documento || 
                          extractInvestorId(withdraw.invesrionista);
    
    console.log('Abriendo detalles para inversionista ID:', selectedInvestorId.value);
    detailsDialog.value = true;
};
const closeDetailsDialog = () => {
    detailsDialog.value = false;
    selectedInvestorId.value = '';
    console.log('Cerrando diálogo de detalles');
};

const extractInvestorId = (investorText) => {
    if (!investorText) return '';
    const match = investorText.match(/\(ID:\s*(\d+)\)/);
    return match ? match[1] : investorText;
};
onMounted(() => {
    loadWithdraws();
});

const loadWithdraws = async () => {
    try {
        const response = await axios.get('/withdraws');
        withdraws.value = response.data.data || response.data;
    } catch (error) {
        console.error('Error al cargar retiros:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error al cargar los retiros',
            life: 5000
        });
    }
};

// Funciones para voucher
const openVoucherDialog = (withdraw) => {
    selectedWithdraw.value = withdraw;
    resetVoucherForm();
    voucherDialog.value = true;
};

const closeVoucherDialog = () => {
    voucherDialog.value = false;
    resetVoucherForm();
    selectedWithdraw.value = null;
};

const resetVoucherForm = () => {
    voucherForm.value = { file: null };
    voucherSubmitted.value = false;
    voucherProcessing.value = false;
    
    // Limpiar el FileUpload component
    if (voucherFileUpload.value) {
        voucherFileUpload.value.clear();
    }
};

const onVoucherFileSelect = (event) => {
    voucherForm.value.file = event.files[0];
};

const clearSelectedFile = () => {
    voucherForm.value.file = null;
    if (voucherFileUpload.value) {
        voucherFileUpload.value.clear();
    }
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const confirmVoucherUpload = async () => {
    voucherSubmitted.value = true;

    if (!voucherForm.value.file) {
        toast.add({
            severity: 'warn',
            summary: 'Validación',
            detail: 'Seleccione un archivo',
            life: 3000
        });
        return;
    }

    voucherProcessing.value = true;

    try {
        const formData = new FormData();
        formData.append('file', voucherForm.value.file);

        const response = await axios.post(`/withdraws/${selectedWithdraw.value.id}/upload-voucher`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        // Actualizar el retiro en la lista
        const withdrawIndex = withdraws.value.findIndex(w => w.id === selectedWithdraw.value.id);
        if (withdrawIndex !== -1) {
            withdraws.value[withdrawIndex] = {
                ...withdraws.value[withdrawIndex],
                resource_path: response.data.file_path
            };
        }

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data.message || 'Voucher subido correctamente',
            life: 3000
        });

        closeVoucherDialog();

    } catch (error) {
        console.error('Error al subir voucher:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Error al subir el voucher',
            life: 5000
        });
    } finally {
        voucherProcessing.value = false;
    }
};

// Funciones para primera validación
const openFirstApprovalDialog = (withdraw) => {
    selectedWithdraw.value = withdraw;
    resetFirstApprovalForm();
    firstApprovalDialog.value = true;
};

const closeFirstApprovalDialog = () => {
    firstApprovalDialog.value = false;
    resetFirstApprovalForm();
    selectedWithdraw.value = null;
};

const resetFirstApprovalForm = () => {
    firstApprovalForm.value = {
        nro_operation: '',
        deposit_pay_date: null,
        description: '',
        approval1_comment: ''
    };
    voucherForm.value = { file: null };
    firstApprovalSubmitted.value = false;
    firstApprovalProcessing.value = false;
    
    // Limpiar el FileUpload component
    if (voucherFileUpload.value) {
        voucherFileUpload.value.clear();
    }
};

const confirmFirstApproval = async () => {
    firstApprovalSubmitted.value = true;

    if (!firstApprovalForm.value.nro_operation || !firstApprovalForm.value.deposit_pay_date || !voucherForm.value.file) {
        toast.add({
            severity: 'warn',
            summary: 'Validación',
            detail: 'Complete todos los campos requeridos y suba el voucher',
            life: 3000
        });
        return;
    }

    firstApprovalProcessing.value = true;

    try {
        // Primero subir el voucher
        const formData = new FormData();
        formData.append('file', voucherForm.value.file);

        const voucherResponse = await axios.post(`/withdraws/${selectedWithdraw.value.id}/upload-voucher`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        // Luego procesar la primera aprobación
        const payload = {
            nro_operation: firstApprovalForm.value.nro_operation,
            deposit_pay_date: formatDateForBackend(firstApprovalForm.value.deposit_pay_date),
            description: firstApprovalForm.value.description || null,
            approval1_comment: firstApprovalForm.value.approval1_comment || null
        };

        const response = await axios.post(`/withdraws/${selectedWithdraw.value.id}/approve-step-one`, payload);

        // Actualizar el retiro en la lista
        const withdrawIndex = withdraws.value.findIndex(w => w.id === selectedWithdraw.value.id);
        if (withdrawIndex !== -1) {
            withdraws.value[withdrawIndex] = {
                ...response.data.data,
                resource_path: voucherResponse.data.file_path
            };
        }

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Primera validación completada y voucher subido',
            life: 3000
        });

        closeFirstApprovalDialog();

    } catch (error) {
        console.error('Error en primera validación:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Error en la primera validación',
            life: 5000
        });
    } finally {
        firstApprovalProcessing.value = false;
    }
};

// Funciones para segunda validación
const openSecondApprovalDialog = (withdraw) => {
    selectedWithdraw.value = withdraw;
    resetSecondApprovalForm();
    secondApprovalDialog.value = true;
};

const closeSecondApprovalDialog = () => {
    secondApprovalDialog.value = false;
    resetSecondApprovalForm();
    selectedWithdraw.value = null;
};

const resetSecondApprovalForm = () => {
    secondApprovalForm.value = {
        approval2_comment: ''
    };
    secondApprovalProcessing.value = false;
};

const confirmSecondApproval = async () => {
    secondApprovalProcessing.value = true;

    try {
        const payload = {
            approval2_comment: secondApprovalForm.value.approval2_comment || null
        };

        const response = await axios.post(`/withdraws/${selectedWithdraw.value.id}/approve-step-two`, payload);

        // Actualizar el retiro en la lista
        const withdrawIndex = withdraws.value.findIndex(w => w.id === selectedWithdraw.value.id);
        if (withdrawIndex !== -1) {
            withdraws.value[withdrawIndex] = response.data.data;
        }

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data.message || 'Segunda validación completada',
            life: 3000
        });

        closeSecondApprovalDialog();

    } catch (error) {
        console.error('Error en segunda validación:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Error en la segunda validación',
            life: 5000
        });
    } finally {
        secondApprovalProcessing.value = false;
    }
};

// Funciones utilitarias
const getStatusLabel = (status) => {
    const statusMap = {
        'approved': 'Aprobado',
        'rejected': 'Rechazado',
        'observed': 'Observado',
        'pending': 'Pendiente',
        'valid': 'Válido'
    };
    return statusMap[status] || status;
};

const getStatusSeverity = (status) => {
    const severityMap = {
        'approved': 'success',
        'rejected': 'danger',
        'observed': 'warn',
        'pending': 'info',
        'valid': 'success'
    };
    return severityMap[status] || 'info';
};

const formatCurrency = (value, currency = 'PEN') => {
    if (value) {
        const numValue = parseFloat(value);
        const currencyMap = {
            'PEN': { locale: 'es-PE', currency: 'PEN' },
            'USD': { locale: 'en-US', currency: 'USD' },
            'EUR': { locale: 'de-DE', currency: 'EUR' }
        };

        const config = currencyMap[currency] || currencyMap['PEN'];
        return numValue.toLocaleString(config.locale, {
            style: 'currency',
            currency: config.currency
        });
    }
    return '';
};

const formatDateForBackend = (date) => {
    if (!date) return '';
    return new Date(date).toISOString().split('T')[0];
};
</script>