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
        <Column header="">
            <template #body="slotProps">
                <Button label="Pagar" icon="pi pi-credit-card" size="small" @click="processPayment(slotProps.data)"
                    :disabled="slotProps.data.nro_operation !== null"
                    :severity="slotProps.data.nro_operation !== null ? 'secondary' : 'success'" />
            </template>
        </Column>
    </DataTable>

    <Dialog v-model:visible="paymentDialog" :style="{ width: '500px' }" header="Procesar Pago" :modal="true">
        <div class="flex flex-col gap-6">
            <!-- Información del retiro -->
            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded">
                <i class="pi pi-credit-card text-3xl text-green-500" />
                <div>
                    <p class="font-semibold">{{ selectedWithdraw?.invesrionista }}</p>
                    <p class="text-sm text-gray-600">
                        Monto: {{ formatCurrency(selectedWithdraw?.amount, selectedWithdraw?.currency) }}
                    </p>
                    <p class="text-sm text-gray-600">
                        Documento: {{ selectedWithdraw?.documento }}
                    </p>
                </div>
            </div>

            <!-- Formulario -->
            <form @submit.prevent="confirmPayment" class="grid grid-cols-1 gap-5">
                <!-- Fecha de transferencia -->
                <div class="flex flex-col gap-1">
                    <label for="deposit_pay_date" class="font-medium">
                        Fecha de Transferencia <span class="text-red-500">*</span>
                    </label>
                    <DatePicker id="deposit_pay_date" v-model="paymentForm.deposit_pay_date" dateFormat="dd/mm/yy"
                        showIcon placeholder="Seleccionar fecha"
                        :class="{ 'p-invalid': submitted && !paymentForm.deposit_pay_date }" />
                    <small v-if="submitted && !paymentForm.deposit_pay_date" class="p-error">
                        La fecha de transferencia es requerida
                    </small>
                </div>

                <!-- Número de operación -->
                <div class="flex flex-col gap-1">
                    <label for="nro_operation" class="font-medium">
                        Número de Operación <span class="text-red-500">*</span>
                    </label>
                    <InputText id="nro_operation" v-model="paymentForm.nro_operation" placeholder="Ej: OP123456789"
                        :class="{ 'p-invalid': submitted && !paymentForm.nro_operation }" />
                    <small v-if="submitted && !paymentForm.nro_operation" class="p-error">
                        El número de operación es requerido
                    </small>
                </div>

                <!-- Subir voucher -->
                <div class="flex flex-col gap-1">
                    <label for="file" class="font-medium">
                        Voucher de Transferencia <span class="text-red-500">*</span>
                    </label>
                    <FileUpload ref="fileUpload" mode="basic" name="file" accept="image/*" :maxFileSize="10000000"
                        :auto="false" chooseLabel="Seleccionar archivo" class="p-button-outlined"
                        @select="onFileSelect" />
                    <small v-if="submitted && !paymentForm.file" class="p-error">
                        El voucher es requerido
                    </small>
                    <small v-if="paymentForm.file" class="p-info">
                        Archivo seleccionado: {{ paymentForm.file.name }}
                    </small>
                </div>

                <!-- Descripción opcional -->
                <div class="flex flex-col gap-1">
                    <label for="description" class="font-medium">
                        Descripción (Opcional)
                    </label>
                    <Textarea id="description" v-model="paymentForm.description" rows="3"
                        placeholder="Observaciones adicionales..." />
                </div>
            </form>
        </div>

        <!-- Footer -->
        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" text severity="secondary" :disabled="processing"
                @click="closePaymentDialog" />
            <Button label="Procesar Pago" icon="pi pi-check" severity="success" :loading="processing"
                @click="confirmPayment" />
        </template>
    </Dialog>

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
import axios from 'axios';

// Configuración global de Axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

// Interceptor para manejar el token CSRF automáticamente
axios.interceptors.request.use((config) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token;
    }
    return config;
});

onMounted(() => {
    loadWithdraws();
});

const toast = useToast();
const dt = ref();
const withdraws = ref([]);
const paymentDialog = ref(false);
const selectedWithdraw = ref(null);
const submitted = ref(false);
const processing = ref(false);

const filters = ref({
    'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const paymentForm = ref({
    nro_operation: '',
    deposit_pay_date: null,
    description: '',
    file: null
});

const loadWithdraws = async () => {
    try {
        const response = await axios.get('/withdraws');
        withdraws.value = response.data.data || response.data;
        
    } catch (error) {
        console.error('Error al cargar retiros:', error);
        
        let errorMessage = 'Error al cargar los retiros';
        
        if (error.response) {
            errorMessage = error.response.data?.message || `Error ${error.response.status}: ${error.response.statusText}`;
        } else if (error.request) {
            errorMessage = 'No se pudo conectar con el servidor';
        } else {
            errorMessage = error.message || 'Error desconocido';
        }
        
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage,
            life: 5000
        });
    }
};

const processPayment = (withdraw) => {
    selectedWithdraw.value = withdraw;
    resetPaymentForm();
    paymentDialog.value = true;
};

const resetPaymentForm = () => {
    paymentForm.value = {
        nro_operation: '',
        deposit_pay_date: null,
        description: '',
        file: null
    };
    submitted.value = false;
    processing.value = false;
};

const closePaymentDialog = () => {
    paymentDialog.value = false;
    resetPaymentForm();
    selectedWithdraw.value = null;
};

const onFileSelect = (event) => {
    paymentForm.value.file = event.files[0];
};

// Función para validar número de operación según regex del FormRequest
const isValidOperationNumber = (value) => {
    const regex = /^[A-Za-z0-9]+$/;
    return regex.test(value);
};

const confirmPayment = async () => {
    submitted.value = true;

    // Validación mejorada según FormRequest
    if (!paymentForm.value.nro_operation ||
        !paymentForm.value.deposit_pay_date ||
        !paymentForm.value.file ||
        !isValidOperationNumber(paymentForm.value.nro_operation)) {
        
        let errorDetail = 'Complete los campos requeridos correctamente';
        
        if (!paymentForm.value.nro_operation) {
            errorDetail = 'El número de operación es requerido';
        } else if (!isValidOperationNumber(paymentForm.value.nro_operation)) {
            errorDetail = 'El número de operación solo puede contener letras y números';
        } else if (!paymentForm.value.deposit_pay_date) {
            errorDetail = 'La fecha de transferencia es requerida';
        } else if (!paymentForm.value.file) {
            errorDetail = 'El voucher de transferencia es requerido';
        }
        
        toast.add({
            severity: 'warn',
            summary: 'Validación',
            detail: errorDetail,
            life: 3000
        });
        return;
    }

    // Verificar que tenemos todos los campos requeridos del backend
    if (!selectedWithdraw.value?.movement_id || 
        !selectedWithdraw.value?.investor_id || 
        !selectedWithdraw.value?.bank_account_id) {
        
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Faltan datos requeridos del retiro seleccionado',
            life: 3000
        });
        return;
    }

    processing.value = true;

    try {
        const formData = new FormData();
        
        // CAMPOS REQUERIDOS según tu FormRequest
        formData.append('nro_operation', paymentForm.value.nro_operation);
        formData.append('amount', selectedWithdraw.value.amount.toString());
        formData.append('currency', selectedWithdraw.value.currency);
        formData.append('deposit_pay_date', formatDateForBackend(paymentForm.value.deposit_pay_date));
        formData.append('movement_id', selectedWithdraw.value.movement_id.toString());
        formData.append('investor_id', selectedWithdraw.value.investor_id.toString());
        formData.append('bank_account_id', selectedWithdraw.value.bank_account_id.toString());
        
        // CAMPOS OPCIONALES
        if (paymentForm.value.description && paymentForm.value.description.trim()) {
            formData.append('description', paymentForm.value.description.trim());
        }
        
        if (selectedWithdraw.value.purpouse && selectedWithdraw.value.purpouse.trim()) {
            formData.append('purpouse', selectedWithdraw.value.purpouse.trim());
        }
        
        // ARCHIVO (requerido según tu validación)
        if (paymentForm.value.file) {
            formData.append('file', paymentForm.value.file);
        }

        // Debug: Ver qué se está enviando
        console.log('Datos que se envían:');
        for (let [key, value] of formData.entries()) {
            console.log(key, ':', value);
        }

        // Llamada PUT con Axios
        const response = await axios.post(`/withdraws/${selectedWithdraw.value.id}/approve`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'X-HTTP-Method-Override': 'PUT' // Laravel method spoofing para multipart
            }
        });

        // Actualizar el retiro en la tabla
        const withdrawIndex = withdraws.value.findIndex(w => w.id === selectedWithdraw.value.id);
        if (withdrawIndex !== -1) {
            withdraws.value[withdrawIndex] = {
                ...withdraws.value[withdrawIndex],
                nro_operation: paymentForm.value.nro_operation,
                deposit_pay_date: formatDateForBackend(paymentForm.value.deposit_pay_date),
                description: paymentForm.value.description
            };
        }

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data.message || 'Retiro procesado correctamente',
            life: 3000
        });

        closePaymentDialog();

    } catch (error) {
        console.error('Error completo:', error);
        
        let errorMessage = 'Error al procesar el retiro';
        
        if (error.response) {
            console.log('Error response data:', error.response.data);
            
            errorMessage = error.response.data?.message || `Error ${error.response.status}`;
            
            // Manejo especial para errores de validación (422)
            if (error.response.status === 422 && error.response.data?.errors) {
                const validationErrors = Object.values(error.response.data.errors).flat();
                errorMessage = validationErrors.join(', ');
            }
        } else if (error.request) {
            errorMessage = 'Error de conexión. Intente nuevamente.';
        } else {
            errorMessage = error.message || 'Error desconocido';
        }
        
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage,
            life: 5000
        });
    } finally {
        processing.value = false;
    }
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

const formatDate = (dateString) => {
    if (dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-PE', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    return '';
};

const formatDateForBackend = (date) => {
    if (!date) return '';
    return new Date(date).toISOString().split('T')[0]; // YYYY-MM-DD format
};
</script>