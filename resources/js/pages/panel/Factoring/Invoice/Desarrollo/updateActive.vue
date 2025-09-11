<template>
    <Dialog v-model:visible="visible" modal header="Confirmar Acción" :style="{ width: '650px' }" :closable="true"
        @hide="onHide">
        <div v-if="facturaData" class="">
            <!-- Mensaje de confirmación -->
            <div class="mb-6 text-center">
                <i class="pi pi-exclamation-triangle text-orange-500 text-4xl mb-4"></i>
                <p class="text-xl font-semibold mb-2 text-gray-800">¿Qué acción deseas realizar con esta factura?</p>
                <p class="text-sm text-gray-600">Tu decisión será registrada en el sistema</p>
            </div>

            <!-- Detalles de la factura -->
            <div class="">
                <h4 class="font-semibold mb-3">Detalles de la Factura</h4>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <span class="font-medium">Código:</span>
                        <p>{{ facturaData.codigo }}</p>
                    </div>

                    <div class="col-span-2">
                        <span class="font-medium">Razón Social:</span>
                        <p>{{ facturaData.razonSocial }}</p>
                    </div>

                    <div>
                        <span class="font-medium">Moneda:</span>
                        <p>{{ facturaData.moneda }}</p>
                    </div>
                    <div>
                        <span class="font-medium">Monto Factura:</span>
                        <p class="">{{ formatCurrency(facturaData.montoFactura, facturaData.moneda) }}</p>
                    </div>

                    <div>
                        <span class="font-medium">Monto Asumido ZUMA:</span>
                        <p class="">{{ formatCurrency(facturaData.montoAsumidoZuma, facturaData.moneda) }}
                        </p>
                    </div>

                    <div>
                        <span class="font-medium">Monto Disponible:</span>
                        <p class="">{{ formatCurrency(facturaData.montoDisponible, facturaData.moneda) }}
                        </p>
                    </div>

                    <div>
                        <span class="font-medium">Tasa:</span>
                        <p class="">{{ facturaData.tasa }}%</p>
                    </div>

                    <div>
                        <span class="font-medium">Fecha de Pago:</span>
                        <p class="">{{ facturaData.fechaPago }}</p>
                    </div>

                    <div>
                        <span class="font-medium">Fecha de Creación:</span>
                        <p class="">{{ facturaData.fechaCreacion }}</p>
                    </div>
                </div>
            </div>
            <!-- Estado de Aprobaciones -->
            <div class="mb-6">
                <h4 class="font-semibold mb-4 text-gray-800 flex items-center gap-2">
                    <i class="pi pi-users text-green-500"></i>
                    Estado de Aprobaciones
                </h4>
                
                <div class="space-y-4">
                    <!-- Primera Aprobación -->
                    <div class="flex items-center gap-4 p-4 bg-white rounded-lg border-2" :class="getApprovalBorderClass(facturaData.PrimerStado)">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center" :class="getApprovalBgClass(facturaData.PrimerStado)">
                                <i :class="getApprovalIconClass(facturaData.PrimerStado)"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="font-semibold text-gray-800">Primera Aprobación</div>
                            <div class="text-sm" :class="getApprovalTextClass(facturaData.PrimerStado)">
                                {{ getApprovalStatusText(facturaData.PrimerStado) }}
                            </div>
                            <div class="text-sm text-gray-600 mt-1">
                                Usuario: {{ facturaData.userprimerNombre }}
                            </div>
                            <div class="text-sm text-gray-600 mt-1">
                                <b>Comentarios:</b> {{ facturaData.approval2_comment }}
                            </div>
                            <div v-if="facturaData.tiempoUno" class="text-xs text-gray-500 mt-1">
                                {{ facturaData.tiempoUno }}
                            </div>
                        </div>
                    </div>

                    <!-- Segunda Aprobación -->
                    <div class="flex items-center gap-4 p-4 bg-white rounded-lg border-2" :class="getApprovalBorderClass(facturaData.SegundaStado)">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center" :class="getApprovalBgClass(facturaData.SegundaStado)">
                                <i :class="getApprovalIconClass(facturaData.SegundaStado)"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="font-semibold text-gray-800">Segunda Aprobación</div>
                            <div class="text-sm" :class="getApprovalTextClass(facturaData.SegundaStado)">
                                {{ getApprovalStatusText(facturaData.SegundaStado) }}
                            </div>
                            <div class="text-sm text-gray-600 mt-1">
                                Usuario: {{ facturaData.userdosNombre }}
                            </div>
                            <div v-if="facturaData.tiempoDos" class="text-xs text-gray-500 mt-1">
                                {{ facturaData.tiempoDos }}
                            </div>
                            <div v-if="facturaData.PrimerStado === 'pending'" class="text-xs text-orange-600 mt-1 italic">
                                * Requiere primera aprobación antes de proceder
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de Comentario - Solo si puede aprobar/rechazar -->
            <div v-if="canUserTakeAction" class="border-t pt-6">
                <h4 class="font-semibold mb-4 text-gray-800 flex items-center gap-2">
                    <i class="pi pi-comment text-blue-500"></i>
                    Comentario
                </h4>
                
                <!-- Comentario estilo Facebook -->
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
                    <div class="p-4">
                        <div class="flex gap-3">
                            <!-- Avatar del usuario -->
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                                    <i class="pi pi-user text-white text-sm"></i>
                                </div>
                            </div>
                            
                            <!-- Área de comentario -->
                            <div class="flex-1">
                                <div class="bg-gray-50 rounded-2xl px-4 py-3 border border-gray-200 hover:bg-gray-100 transition-colors focus-within:bg-white focus-within:border-blue-300">
                                    <Textarea 
                                        v-model="comentario" 
                                        rows="3" 
                                        class="w-full border-0 bg-transparent resize-none text-sm focus:ring-0 focus:outline-none p-0 placeholder-gray-500"
                                        :placeholder="getCommentPlaceholder()"
                                        :maxlength="300"
                                        ref="textareaRef"
                                    />
                                </div>
                                
                                <!-- Información y contador -->
                                <div class="flex justify-between items-center mt-3 text-xs">
                                    <div class="flex items-center gap-2 text-gray-500">
                                        <i class="pi pi-info-circle"></i>
                                        <span>{{ getActionStepInfo() }}</span>
                                    </div>
                                    <span class="text-gray-500" :class="{ 'text-red-500': comentario.length > 250 }">
                                        {{ comentario.length }}/300
                                    </span>
                                </div>

                                <!-- Indicador visual del paso -->
                                <div class="mt-3 p-2 rounded-lg" :class="getCurrentStepIndicatorClass()">
                                    <div class="flex items-center gap-2 text-sm">
                                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-white font-bold text-xs" 
                                             :class="getCurrentStepNumberClass()">
                                            {{ getCurrentApprovalStep() }}
                                        </div>
                                        <span class="font-medium">{{ getCurrentStepLabel() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensaje de validación si no puede tomar acción -->
                <div v-if="!canTakeAction && canUserTakeAction" class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex gap-3">
                        <i class="pi pi-exclamation-triangle text-yellow-600 text-lg"></i>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-yellow-800 mb-1">
                                No puedes realizar acciones en este momento
                            </div>
                            <p class="text-sm text-yellow-700">
                                {{ getCannotTakeActionReason() }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mensaje si no tiene permisos -->
            <div v-else class="border-t pt-6">
                <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-center">
                    <i class="pi pi-lock text-gray-400 text-2xl mb-2"></i>
                    <p class="text-sm text-gray-600">No tienes permisos para realizar acciones sobre esta factura</p>
                </div>
            </div>
        </div>

        <!-- Botones de acción -->
        <template #footer>
            <div class="flex justify-between items-center w-full">
                <div class="text-sm text-gray-600">
                    <i v-if="facturaData?.estado === 'active'" class="pi pi-check-circle text-green-500 mr-1"></i>
                    <i v-else-if="facturaData?.estado === 'rejected'" class="pi pi-times-circle text-red-500 mr-1"></i>
                    <span v-if="facturaData?.estado === 'active'" class="text-green-600 font-medium">
                        Factura Activada
                    </span>
                    <span v-else-if="facturaData?.estado === 'rejected'" class="text-red-600 font-medium">
                        Factura Rechazada
                    </span>
                    <span v-else-if="isWaitingForApprovals" class="text-orange-600">
                        {{ getWaitingMessage() }}
                    </span>
                </div>
                
                <div class="flex gap-3">
                    <Button 
                        label="Cancelar" 
                        severity="secondary" 
                        text 
                        @click="onCancel" 
                        :disabled="loading" 
                        icon="pi pi-times"
                    />
                    
                    <!-- Botón de Rechazo -->
                    <Button 
                        v-if="canTakeAction && canUserTakeAction"
                        label="Rechazar"
                        @click="onReject" 
                        :loading="loading && actionType === 'reject'" 
                        :disabled="!canTakeActionWithComment"
                        icon="pi pi-times" 
                        severity="danger"
                        class="px-6"
                    />
                    
                    <!-- Botón de Aprobación -->
                    <Button 
                        v-if="canTakeAction && canUserTakeAction"
                        :label="getApproveButtonLabel()"
                        @click="onApprove" 
                        :loading="loading && actionType === 'approve'" 
                        :disabled="!canTakeActionWithComment"
                        icon="pi pi-check" 
                        severity="success"
                        class="px-6"
                    />
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

// Props
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

// Emits
const emit = defineEmits(['update:modelValue', 'approved', 'rejected', 'cancelled']);

// Reactive data
const visible = ref(props.modelValue);
const facturaData = ref(null);
const loading = ref(false);
const comentario = ref('');
const textareaRef = ref(null);
const actionType = ref(''); // 'approve' o 'reject'

// Computed
const canUserTakeAction = computed(() => {
    // Aquí deberías verificar si el usuario tiene permisos para aprobar/rechazar
    // Por ejemplo, verificando roles o permisos específicos
    return true; // Placeholder - implementar según tu lógica de permisos
});

const canTakeAction = computed(() => {
    if (!facturaData.value || !canUserTakeAction.value) return false;
    
    const { PrimerStado, SegundaStado, estado } = facturaData.value;
    
    // No puede tomar acción si ya está activa o rechazada
    if (estado === 'active' || estado === 'rejected') return false;
    
    const userId = getCurrentUserId();
    
    // Lógica: puede tomar acción si está en pending y no es el mismo usuario que ya aprobó
    return PrimerStado === 'pending' || 
           (PrimerStado === 'approved' && SegundaStado === 'pending' && !hasUserAlreadyApproved(userId));
});

const canTakeActionWithComment = computed(() => {
    return canTakeAction.value && comentario.value.trim().length > 0;
});

const isWaitingForApprovals = computed(() => {
    if (!facturaData.value) return false;
    const { estado } = facturaData.value;
    return estado !== 'active' && estado !== 'rejected' && 
           (facturaData.value.PrimerStado === 'pending' || facturaData.value.SegundaStado === 'pending');
});

// Watchers
watch(() => props.modelValue, (newValue) => {
    visible.value = newValue;
    if (newValue && props.facturaId) {
        fetchFacturaData();
        resetForm();
    }
});

watch(visible, (newValue) => {
    emit('update:modelValue', newValue);
});

// Methods
const resetForm = () => {
    comentario.value = '';
    actionType.value = '';
};

const getCurrentUserId = () => {
    // Implementa aquí la lógica para obtener el ID del usuario actual
    return 1; // Placeholder
};

const hasUserAlreadyApproved = (userId) => {
    // Implementa la lógica para verificar si el usuario ya aprobó
    // Esto dependerá de cómo manejes la información del usuario en el backend
    return false; // Placeholder
};

const fetchFacturaData = async () => {
    if (!props.facturaId) return;

    try {
        loading.value = true;
        const response = await axios.get(`/invoices/${props.facturaId}`);
        facturaData.value = response.data?.data || null;
    } catch (error) {
        console.error('Error al cargar datos de la factura:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudieron cargar los datos de la factura',
            life: 5000
        });
        onCancel();
    } finally {
        loading.value = false;
    }
};

const onApprove = async () => {
    if (!props.facturaId || !canTakeActionWithComment.value) return;

    try {
        loading.value = true;
        actionType.value = 'approve';

        const payload = {
            comment: comentario.value.trim()
        };

        const response = await axios.patch(`/invoices/${props.facturaId}/activacion`, payload);

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data?.message || 'Aprobación registrada correctamente',
            life: 5000
        });
        emit('confirmed', response.data);
        visible.value = false;

    } catch (error) {
        console.error('Error al aprobar factura:', error);

        const errorMessage = error.response?.data?.message || 'Error al procesar la aprobación';

        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage,
            life: 5000
        });
    } finally {
        loading.value = false;
        actionType.value = '';
    }
};

const onReject = async () => {
    if (!props.facturaId || !canTakeActionWithComment.value) return;

    try {
        loading.value = true;
        actionType.value = 'reject';

        const payload = {
            comment: comentario.value.trim()
        };

        const response = await axios.patch(`/invoices/${props.facturaId}/rechazar`, payload);

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data?.message || 'Rechazo registrado correctamente',
            life: 5000
        });
        emit('confirmed', response.data);
        visible.value = false;

    } catch (error) {
        console.error('Error al rechazar factura:', error);

        const errorMessage = error.response?.data?.message || 'Error al procesar el rechazo';

        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage,
            life: 5000
        });
    } finally {
        loading.value = false;
        actionType.value = '';
    }
};

const onCancel = () => {
    resetForm();
    emit('cancelled');
    visible.value = false;
};

const onHide = () => {
    if (!loading.value) {
        onCancel();
    }
};


const getApprovalBorderClass = (status) => {
    switch (status) {
        case 'approved': return 'border-green-200 bg-green-50';
        case 'pending': return 'border-orange-200 bg-orange-50';
        case 'rejected': return 'border-red-200 bg-red-50';
        default: return 'border-gray-200';
    }
};

const getApprovalBgClass = (status) => {
    switch (status) {
        case 'approved': return 'bg-green-500';
        case 'pending': return 'bg-orange-500';
        case 'rejected': return 'bg-red-500';
        default: return 'bg-gray-400';
    }
};

const getApprovalIconClass = (status) => {
    switch (status) {
        case 'approved': return 'pi pi-check text-white';
        case 'pending': return 'pi pi-clock text-white';
        case 'rejected': return 'pi pi-times text-white';
        default: return 'pi pi-question text-white';
    }
};

const getApprovalTextClass = (status) => {
    switch (status) {
        case 'approved': return 'text-green-600';
        case 'pending': return 'text-orange-600';
        case 'rejected': return 'text-red-600';
        default: return 'text-gray-600';
    }
};

const getApprovalStatusText = (status) => {
    switch (status) {
        case 'approved': return 'Aprobado';
        case 'pending': return 'Pendiente';
        case 'rejected': return 'Rechazado';
        default: return 'Sin estado';
    }
};

const getCurrentApprovalStep = () => {
    if (!facturaData.value) return '';
    return facturaData.value.PrimerStado === 'pending' ? '1' : '2';
};

const getCurrentStepLabel = () => {
    if (!facturaData.value) return '';
    return facturaData.value.PrimerStado === 'pending' ? 'Primera Aprobación' : 'Segunda Aprobación';
};

const getCurrentStepIndicatorClass = () => {
    if (!facturaData.value) return '';
    return facturaData.value.PrimerStado === 'pending' ? 'bg-blue-50 border border-blue-200' : 'bg-green-50 border border-green-200';
};

const getCurrentStepNumberClass = () => {
    if (!facturaData.value) return '';
    return facturaData.value.PrimerStado === 'pending' ? 'bg-blue-500' : 'bg-green-500';
};

const getCommentPlaceholder = () => {
    if (!facturaData.value) return 'Escribe tu comentario...';
    return facturaData.value.PrimerStado === 'pending' 
        ? 'Escribe tu comentario para la primera aprobación/rechazo...' 
        : 'Escribe tu comentario para la segunda aprobación/rechazo...';
};

const getActionStepInfo = () => {
    if (!facturaData.value) return '';
    return facturaData.value.PrimerStado === 'pending' 
        ? 'Tu comentario será registrado con tu decisión sobre la primera aprobación' 
        : 'Tu comentario será registrado con tu decisión sobre la segunda aprobación';
};

const getApproveButtonLabel = () => {
    if (!facturaData.value) return 'Aprobar';
    return facturaData.value.PrimerStado === 'pending' ? 'Dar Primera Aprobación' : 'Dar Segunda Aprobación';
};

const getCannotTakeActionReason = () => {
    if (!facturaData.value) return '';
    
    const { PrimerStado, SegundaStado, estado } = facturaData.value;
    
    if (estado === 'active') {
        return 'Esta factura ya ha sido activada.';
    }
    
    if (estado === 'rejected') {
        return 'Esta factura ya ha sido rechazada.';
    }
    
    if (PrimerStado === 'approved' && SegundaStado === 'approved') {
        return 'Esta factura ya ha sido completamente aprobada.';
    }
    
    if (hasUserAlreadyApproved(getCurrentUserId())) {
        return 'Ya realizaste una acción sobre esta factura. No puedes realizar ambas aprobaciones.';
    }
    
    return 'No cumples los requisitos para realizar acciones sobre esta factura.';
};

const getWaitingMessage = () => {
    if (!facturaData.value) return '';
    
    if (facturaData.value.PrimerStado === 'pending') {
        return 'Esperando primera aprobación';
    }
    if (facturaData.value.SegundaStado === 'pending') {
        return 'Esperando segunda aprobación';
    }
    return '';
};

// Utility functions
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
</script>
