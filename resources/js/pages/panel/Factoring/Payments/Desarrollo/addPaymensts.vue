<template>
    <Dialog :visible="visible" :style="{ width: '700px' }" header="Procesar Pago" :modal="true" :closable="true"
        @update:visible="$emit('update:visible', $event)">
        
        <!-- Información del pago -->
        <div class="grid grid-cols-1 gap-4">
            <div class="border rounded-lg p-4 bg-gray-50">
                <h4 class="text-lg font-medium mb-3">Información de la Factura</h4>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente/Proveedor</label>
                        <span class="font-mono text-sm font-semibold">{{ paymentData.document || 'No especificado' }}</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Aceptante</label>
                        <span class="font-mono text-sm font-semibold">{{ paymentData.RUC_client || 'No especificado' }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nro. Factura</label>
                        <span class="font-mono text-sm font-semibold">{{ paymentData.invoice_number || 'No especificado' }}</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nro. Préstamo</label>
                        <span class="font-mono text-sm font-semibold">{{ paymentData.loan_number || 'No especificado' }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monto Factura</label>
                        <span class="font-mono text-lg font-semibold text-blue-600">
                            {{ formatCurrency(paymentData.amount, paymentData.currency) }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monto a Pagar</label>
                        <span class="font-mono text-lg font-semibold text-green-600">
                            {{ formatCurrency(paymentData.saldo, paymentData.currency) }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Estimada</label>
                        <span class="font-mono text-sm">{{ paymentData.estimated_pay_date }}</span>
                    </div>
                </div>
            </div>

            <!-- Formulario de pago -->
            <div class="border rounded-lg p-4">
                <h4 class="text-lg font-medium mb-4">Configuración del Pago</h4>
                
                <!-- Tipo de pago -->
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Tipo de Pago <span class="text-red-500">*</span></label>
                    <div class="flex gap-4">
                        <div class="flex items-center">
                            <RadioButton v-model="form.pay_type" inputId="total" value="total" />
                            <label for="total" class="ml-2 cursor-pointer">Pago Total</label>
                        </div>
                        <div class="flex items-center">
                            <RadioButton v-model="form.pay_type" inputId="partial" value="partial" />
                            <label for="partial" class="ml-2 cursor-pointer">Pago Parcial</label>
                        </div>
                    </div>
                </div>

                <!-- Monto a pagar -->
<!-- Monto a pagar -->
<div class="mb-4">
    <label class="block text-sm font-medium mb-2">
        Monto a Pagar <span class="text-red-500">*</span>
    </label>
    <InputNumber 
        v-model="form.amount_to_be_paid" 
        mode="currency" 
        :currency="getValidCurrency(paymentData.currency)" 
        :locale="'es-PE'"
        :minFractionDigits="2"
        :maxFractionDigits="2"
        :max="parseFloat(paymentData.saldo)"
        :min="0.01"
        :disabled="form.pay_type === 'total'"
        class="w-full"
        placeholder="Ingrese el monto a pagar"
        :class="{ 'p-invalid': errors.amount_to_be_paid }"
    />
    <small v-if="errors.amount_to_be_paid" class="text-red-500 block mt-1">
        {{ errors.amount_to_be_paid }}
    </small>
    <small class="text-gray-600 block mt-1">
        Disponible: {{ formatCurrency(paymentData.saldo, paymentData.currency) }}
    </small>
</div>
                <!-- Fecha de pago -->
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">
                        Fecha de Pago <span class="text-red-500">*</span>
                    </label>
                    <Calendar 
                        v-model="form.pay_date" 
                        dateFormat="yy-mm-dd" 
                        :showIcon="true"
                        class="w-full"
                        placeholder="Seleccione la fecha de pago"
                        :class="{ 'p-invalid': errors.pay_date }"
                    />
                    <small v-if="errors.pay_date" class="text-red-500 block mt-1">
                        {{ errors.pay_date }}
                    </small>
                </div>

                <!-- Campo para adjuntar múltiples archivos -->
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">
                        Adjuntar Evidencias <span class="text-red-500">*</span>
                    </label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-blue-400 transition-colors">
                        <input
                            ref="fileInput"
                            type="file"
                            @change="onFileSelect"
                            accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xlsx,.xls,.txt"
                            class="hidden"
                            id="file-upload"
                            multiple
                        />
                        <label 
                            for="file-upload" 
                            class="cursor-pointer flex flex-col items-center justify-center py-3"
                            :class="{ 'text-red-500': errors.attachments }"
                        >
                            <i v-if="form.attachments.length === 0" class="pi pi-cloud-upload text-2xl mb-2 text-gray-400"></i>
                            <i v-else class="pi pi-files text-2xl mb-2 text-green-500"></i>
                            
                            <span v-if="form.attachments.length === 0" class="text-sm text-gray-600 text-center">
                                <strong>Haga clic para subir archivos</strong> o arrastre los archivos aquí
                                <br>
                                <span class="text-xs text-gray-500">PDF, JPG, PNG, DOC, XLSX, etc. (máx. 10MB cada uno)</span>
                            </span>
                            <div v-else class="text-center">
                                <span class="text-sm font-medium text-green-700 block">
                                    {{ form.attachments.length }} archivo(s) seleccionado(s)
                                </span>
                                <span class="text-xs text-gray-500 block">
                                    {{ formatTotalFileSize() }}
                                </span>
                            </div>
                        </label>
                    </div>
                    
                    <!-- Lista de archivos seleccionados -->
                    <div v-if="form.attachments.length > 0" class="mt-3 space-y-2 max-h-40 overflow-y-auto">
                        <div 
                            v-for="(file, index) in form.attachments" 
                            :key="index"
                            class="flex items-center justify-between bg-gray-50 p-2 rounded border"
                        >
                            <div class="flex items-center space-x-2 flex-1 min-w-0">
                                <i :class="getFileIcon(file.type)" class="text-blue-500 flex-shrink-0"></i>
                                <div class="min-w-0 flex-1">
                                    <span class="text-sm font-medium text-gray-700 block truncate" :title="file.name">
                                        {{ file.name }}
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        {{ formatFileSize(file.size) }}
                                    </span>
                                </div>
                            </div>
                            <Button
                                icon="pi pi-times"
                                size="small"
                                severity="danger"
                                text
                                rounded
                                @click="removeFile(index)"
                                class="flex-shrink-0 ml-2"
                            />
                        </div>
                    </div>
                    
                    <!-- Botón para agregar más archivos -->
                    <div v-if="form.attachments.length > 0" class="mt-2 text-center">
                        <Button
                            icon="pi pi-plus"
                            label="Agregar más archivos"
                            severity="secondary"
                            size="small"
                            text
                            @click="triggerFileInput"
                        />
                    </div>

                    <small v-if="errors.attachments" class="text-red-500 block mt-1">
                        {{ errors.attachments }}
                    </small>
                </div>

                <!-- Campos para pago parcial -->
                <div v-if="form.pay_type === 'partial'" class="border-t pt-4 mt-4">
                    <h5 class="text-md font-medium mb-3 text-orange-600">
                        <i class="pi pi-clock mr-2"></i>
                        Configuración de Reprogramación
                    </h5>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Fecha de reprogramación -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2">
                                Fecha de Reprogramación <span class="text-red-500">*</span>
                            </label>
                            <Calendar 
                                v-model="form.reprogramation_date" 
                                dateFormat="yy-mm-dd" 
                                :showIcon="true"
                                class="w-full"
                                placeholder="Nueva fecha de vencimiento"
                                :class="{ 'p-invalid': errors.reprogramation_date }"
                                :minDate="new Date()"
                            />
                            <small v-if="errors.reprogramation_date" class="text-red-500 block mt-1">
                                {{ errors.reprogramation_date }}
                            </small>
                        </div>

                        <!-- Tasa de reprogramación -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2">
                                Tasa de Reprogramación (%) <span class="text-red-500">*</span>
                            </label>
                            <InputNumber 
                                v-model="form.reprogramation_rate" 
                                :minFractionDigits="2"
                                :maxFractionDigits="4"
                                suffix="%"
                                :min="0"
                                :max="100"
                                class="w-full"
                                placeholder="Ej: 12.50"
                                :class="{ 'p-invalid': errors.reprogramation_rate }"
                            />
                            <small v-if="errors.reprogramation_rate" class="text-red-500 block mt-1">
                                {{ errors.reprogramation_rate }}
                            </small>
                        </div>
                    </div>

                    <!-- Porcentaje calculado automáticamente -->
                    <div class="mb-4 bg-blue-50 p-3 rounded border">
                        <label class="block text-sm font-medium mb-2">
                            <i class="pi pi-calculator mr-1"></i>
                            Porcentaje de Pago (Calculado automáticamente)
                        </label>
                        <div class="flex items-center gap-3">
                            <ProgressBar :value="percentageCalculated" class="flex-1" />
                            <span class="text-lg font-mono font-bold text-blue-600">
                                {{ percentageCalculated.toFixed(2) }}%
                            </span>
                        </div>
                        <small class="text-gray-600 block mt-2">
                            <i class="pi pi-info-circle mr-1"></i>
                            Cálculo: ({{ formatCurrency(form.amount_to_be_paid, paymentData.currency) }} / {{ formatCurrency(paymentData.saldo, paymentData.currency) }}) × 100
                        </small>
                    </div>
                </div>
            </div>

            <!-- Resumen del pago -->
            <div class="bg-gradient-to-r from-gray-50 to-blue-50 p-4 rounded-lg border">
                <h5 class="font-medium mb-3 text-gray-800">
                    <i class="pi pi-file-check mr-2"></i>
                    Resumen del Pago
                </h5>
                <div class="text-sm space-y-2">
                    <div class="flex justify-between items-center">
                        <span>Tipo de pago:</span>
                        <Tag :value="form.pay_type === 'total' ? 'Pago Total' : 'Pago Parcial'" 
                             :severity="form.pay_type === 'total' ? 'success' : 'warning'"
                             :icon="form.pay_type === 'total' ? 'pi pi-check-circle' : 'pi pi-clock'" />
                    </div>
                    <div class="flex justify-between items-center">
                        <span>Monto a pagar:</span>
                        <span class="font-mono font-semibold text-green-600">
                            {{ formatCurrency(form.amount_to_be_paid, paymentData.currency) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span>Fecha de pago:</span>
                        <span class="font-mono">{{ formatDate(form.pay_date) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span>Comprobantes:</span>
                        <Tag v-if="form.attachments.length > 0" 
                             :value="`${form.attachments.length} archivo(s)`" 
                             severity="info" 
                             icon="pi pi-paperclip" />
                        <span v-else class="text-gray-500 italic">Sin adjuntar</span>
                    </div>
                    <div v-if="form.pay_type === 'partial'" class="flex justify-between items-center">
                        <span>Saldo restante:</span>
                        <span class="font-mono text-orange-600 font-semibold">
                            {{ formatCurrency(remainingBalance, paymentData.currency) }}
                        </span>
                    </div>
                    <div v-if="form.pay_type === 'partial'" class="flex justify-between items-center">
                        <span>Nueva fecha vencimiento:</span>
                        <span class="font-mono">{{ formatDate(form.reprogramation_date) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between items-center w-full">
                <small class="text-gray-500 italic">
                    <i class="pi pi-exclamation-triangle mr-1"></i>
                    Los campos marcados con <span class="text-red-500">*</span> son obligatorios
                </small>
                <div class="flex gap-3">
                    <Button 
                        label="Cancelar" 
                        icon="pi pi-times" 
                        severity="secondary" 
                        text  
                        @click="onCancel"
                        :disabled="processing" 
                    />
                    <Button 
                        :label="form.pay_type === 'total' ? 'Procesar Pago Total' : 'Procesar Pago Parcial'" 
                        :icon="form.pay_type === 'total' ? 'pi pi-check-circle' : 'pi pi-clock'"
                        severity="success" 
                        @click="onConfirmPayment"
                        :loading="processing" 
                    />
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import RadioButton from 'primevue/radiobutton';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import ProgressBar from 'primevue/progressbar';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

// Props
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

// Emits
const emit = defineEmits(['update:visible', 'payment-processed', 'cancelled']);

const toast = useToast();
const processing = ref(false);
const errors = ref({});
const fileInput = ref(null);

// Formulario reactivo - Cambio: attachments es ahora un array
const form = ref({
    pay_type: 'total',
    amount_to_be_paid: 0,
    pay_date: new Date(),
    reprogramation_date: null,
    reprogramation_rate: null,
    attachments: [] // Cambio: de attachment a attachments (array)
});

// Computed values
const percentageCalculated = computed(() => {
    if (!form.value.amount_to_be_paid || !props.paymentData.saldo) return 0;
    return (form.value.amount_to_be_paid / props.paymentData.saldo) * 100;
});

const remainingBalance = computed(() => {
    if (!form.value.amount_to_be_paid || !props.paymentData.saldo) return props.paymentData.saldo;
    return props.paymentData.saldo - form.value.amount_to_be_paid;
});

// Watchers
watch(() => form.value.pay_type, (newType) => {
    clearErrors();
    
    if (newType === 'total') {
        form.value.amount_to_be_paid = parseFloat(props.paymentData.saldo) || 0;
        form.value.reprogramation_date = null;
        form.value.reprogramation_rate = null;
    } else {
        form.value.amount_to_be_paid = 0;
        const futureDate = new Date();
        futureDate.setMonth(futureDate.getMonth() + 1);
        form.value.reprogramation_date = futureDate;
        form.value.reprogramation_rate = null;
    }
});

watch(() => props.visible, (newVisible) => {
    if (newVisible) {
        resetForm();
    }
});

// Methods
function resetForm() {
    form.value = {
        pay_type: 'total',
        amount_to_be_paid: parseFloat(props.paymentData.saldo) || 0,
        pay_date: new Date(),
        reprogramation_date: null,
        reprogramation_rate: null,
        attachments: [] // Cambio: resetear array de archivos
    };
    errors.value = {};
    
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function clearErrors() {
    errors.value = {};
}

// Función para obtener el icono según el tipo de archivo
function getFileIcon(fileType) {
    const iconMap = {
        'application/pdf': 'pi pi-file-pdf',
        'image/jpeg': 'pi pi-image',
        'image/jpg': 'pi pi-image',
        'image/png': 'pi pi-image',
        'image/gif': 'pi pi-image',
        'application/msword': 'pi pi-file-word',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'pi pi-file-word',
        'application/vnd.ms-excel': 'pi pi-file-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'pi pi-file-excel',
        'text/plain': 'pi pi-file'
    };
    return iconMap[fileType] || 'pi pi-file';
}

// Función para disparar el input de archivos manualmente
function triggerFileInput() {
    if (fileInput.value) {
        fileInput.value.click();
    }
}

// Funciones para manejo de archivos - Modificadas para múltiples archivos
function onFileSelect(event) {
    const files = Array.from(event.target.files);
    
    if (files.length === 0) return;

    const maxSize = 10 * 1024 * 1024; // 10MB en bytes
    const allowedTypes = [
        'application/pdf',
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain'
    ];

    const validFiles = [];
    const rejectedFiles = [];

    files.forEach(file => {
        // Validar tamaño
        if (file.size > maxSize) {
            rejectedFiles.push({
                name: file.name,
                reason: 'Archivo muy grande (máx. 10MB)'
            });
            return;
        }

        // Validar tipo
        if (!allowedTypes.includes(file.type)) {
            rejectedFiles.push({
                name: file.name,
                reason: 'Tipo de archivo no válido'
            });
            return;
        }

        // Verificar que no esté duplicado
        const isDuplicate = form.value.attachments.some(existing => 
            existing.name === file.name && existing.size === file.size
        );

        if (isDuplicate) {
            rejectedFiles.push({
                name: file.name,
                reason: 'Archivo ya existe'
            });
            return;
        }

        validFiles.push(file);
    });

    // Agregar archivos válidos
    if (validFiles.length > 0) {
        form.value.attachments = [...form.value.attachments, ...validFiles];
        clearErrors();
        
        toast.add({
            severity: 'success',
            summary: 'Archivos agregados',
            detail: `${validFiles.length} archivo(s) agregado(s) correctamente`,
            life: 3000
        });
    }

    // Mostrar archivos rechazados
    if (rejectedFiles.length > 0) {
        rejectedFiles.forEach(rejected => {
            toast.add({
                severity: 'warn',
                summary: 'Archivo rechazado',
                detail: `${rejected.name}: ${rejected.reason}`,
                life: 4000
            });
        });
    }

    // Limpiar input para permitir seleccionar los mismos archivos nuevamente si es necesario
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function removeFile(index) {
    const fileName = form.value.attachments[index].name;
    form.value.attachments.splice(index, 1);
    
    toast.add({
        severity: 'info',
        summary: 'Archivo eliminado',
        detail: `${fileName} ha sido eliminado`,
        life: 2000
    });
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function formatTotalFileSize() {
    const totalBytes = form.value.attachments.reduce((sum, file) => sum + file.size, 0);
    return `Total: ${formatFileSize(totalBytes)}`;
}
// Función para obtener código de moneda válido
function getValidCurrency(currency) {
    const currencyMap = {
        'S/': 'PEN',
        'PEN': 'PEN',
        'USD': 'USD',
        '$': 'USD'
    };
    return currencyMap[currency] || 'PEN';
}

// También actualiza la función formatCurrency:
function formatCurrency(amount = 0, currency = 'PEN') {
    const numAmount = Number(amount) || 0;
    const symbol = currency === 'PEN' || currency === 'S/' ? 'S/' : '$';
    return `${symbol} ${numAmount.toLocaleString('es-PE', { minimumFractionDigits: 2 })}`;
}
function validateForm() {
    errors.value = {};
    
    // Validar monto
    if (!form.value.amount_to_be_paid || form.value.amount_to_be_paid <= 0) {
        errors.value.amount_to_be_paid = 'El monto es requerido y debe ser mayor a 0';
    }
    
    if (form.value.amount_to_be_paid > parseFloat(props.paymentData.saldo)) {
        errors.value.amount_to_be_paid = 'El monto no puede ser mayor al saldo disponible';
    }
    
    // Validar fecha de pago
    if (!form.value.pay_date) {
        errors.value.pay_date = 'La fecha de pago es requerida';
    }
    
    // Validar archivos adjuntos - Cambio: validar que haya al menos un archivo
    if (!form.value.attachments || form.value.attachments.length === 0) {
        errors.value.attachments = 'Debe adjuntar al menos un comprobante de pago';
    }
    
    // Validaciones específicas para pago parcial
    if (form.value.pay_type === 'partial') {
        if (!form.value.reprogramation_date) {
            errors.value.reprogramation_date = 'La fecha de reprogramación es requerida para pagos parciales';
        }
        
        if (!form.value.reprogramation_rate || form.value.reprogramation_rate <= 0) {
            errors.value.reprogramation_rate = 'La tasa de reprogramación es requerida y debe ser mayor a 0';
        }
        
        if (form.value.reprogramation_rate > 100) {
            errors.value.reprogramation_rate = 'La tasa no puede ser mayor a 100%';
        }
        
        if (form.value.reprogramation_date && form.value.reprogramation_date <= new Date()) {
            errors.value.reprogramation_date = 'La fecha de reprogramación debe ser futura';
        }
    }
    
    return Object.keys(errors.value).length === 0;
}


function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-PE');
}

// Función principal para confirmar pago - Modificada para múltiples archivos
async function onConfirmPayment() {
    if (!validateForm()) {
        toast.add({
            severity: 'warn',
            summary: 'Datos Incompletos',
            detail: 'Por favor complete todos los campos requeridos correctamente',
            life: 4000
        });
        return;
    }

    processing.value = true;
    
    try {
        const invoiceId = props.paymentData.id_pago;
        
        if (!invoiceId) {
            throw new Error('No se encontró el ID de la factura para procesar el pago');
        }

        // Crear FormData para enviar múltiples archivos
        const formData = new FormData();
        formData.append('amount_to_be_paid', form.value.amount_to_be_paid);
        formData.append('pay_date', form.value.pay_date.toISOString().split('T')[0]);
        formData.append('pay_type', form.value.pay_type);
        
        if (form.value.reprogramation_date) {
            formData.append('reprogramation_date', form.value.reprogramation_date.toISOString().split('T')[0]);
        }
        
        if (form.value.reprogramation_rate) {
            formData.append('reprogramation_rate', form.value.reprogramation_rate);
        }
        
        // Adjuntar múltiples archivos - Cambio principal
        form.value.attachments.forEach((file, index) => {
            formData.append(`payment_attachments[${index}]`, file);
        });

        console.log('Enviando datos del pago con múltiples archivos adjuntos');
        console.log('Invoice ID:', invoiceId);
        console.log('Número de archivos:', form.value.attachments.length);
        console.log('Archivos:', form.value.attachments.map(f => f.name));
        
        // Llamar a la API de pagos
        const response = await axios.post(`/payments/${invoiceId}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (response.data) {
            const successMessage = response.data.message || 
                `${form.value.pay_type === 'total' ? 'Pago total' : 'Pago parcial'} procesado exitosamente`;
            
            toast.add({
                severity: 'success',
                summary: 'Pago Procesado',
                detail: successMessage,
                life: 5000,
            });

            // Emitir evento con datos actualizados para el componente padre
            emit('payment-processed', {
                ...props.paymentData,
                payment_response: response.data,
                processed_amount: form.value.amount_to_be_paid,
                processed_type: form.value.pay_type,
                processed_date: form.value.pay_date,
                attachments_info: form.value.attachments.map(file => ({
                    name: file.name,
                    size: file.size,
                    type: file.type
                })), // Cambio: enviar info de múltiples archivos
                reprogramation_info: form.value.pay_type === 'partial' ? {
                    date: form.value.reprogramation_date,
                    rate: form.value.reprogramation_rate
                } : null
            });
            
            // Cerrar el diálogo
            emit('update:visible', false);
        }

    } catch (error) {
        console.error('Error al procesar pago:', error);
        
        let errorMessage = 'No se pudo procesar el pago. Intenta nuevamente.';
        
        // Manejo de errores más específico
        if (error.response?.status === 422) {
            const validationErrors = error.response.data?.errors || {};
            if (Object.keys(validationErrors).length > 0) {
                errorMessage = 'Errores de validación: ' + 
                    Object.values(validationErrors).flat().join(', ');
            }
        } else if (error.response?.data?.message) {
            errorMessage = error.response.data.message;
        } else if (error.response?.data?.error) {
            errorMessage = error.response.data.error;
        } else if (error.message) {
            errorMessage = error.message;
        }
        
        toast.add({
            severity: 'error',
            summary: 'Error de Procesamiento',
            detail: errorMessage,
            life: 6000,
        });
    } finally {
        processing.value = false;
    }
}
watch(() => props.visible, (newVisible) => {
  console.log('🔄 addPaymensts - visible cambió:', newVisible);
  console.log('📦 addPaymensts - paymentData recibido:', props.paymentData);
  
  if (newVisible) {
    console.log('✅ Diálogo se está abriendo, resetear formulario');
    resetForm();
  } else {
    console.log('❌ Diálogo se está cerrando');
  }
});
// Función para cancelar
function onCancel() {
    emit('cancelled');
    emit('update:visible', false);
}

// Initialize on mount
onMounted(() => {
    if (props.visible) {
        resetForm();
    }
});
</script>

<style scoped>
.p-invalid {
    border-color: #ef4444 !important;
}

.p-invalid:focus {
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2) !important;
}

.font-mono {
    font-family: 'Courier New', monospace;
}

/* Estilos para drag and drop */
.border-dashed:hover {
    border-color: #3b82f6;
    background-color: #f8fafc;
}

/* Animación para el área de carga de archivos */
.border-dashed {
    transition: all 0.2s ease-in-out;
}

/* Estilos para la lista de archivos */
.space-y-2 > * + * {
    margin-top: 0.5rem;
}

/* Scroll personalizado para la lista de archivos */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>