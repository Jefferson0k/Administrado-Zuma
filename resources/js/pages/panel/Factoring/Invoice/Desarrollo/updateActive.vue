<template>
    <Dialog v-model:visible="visible" modal header="Confirmar Acción" :style="{ width: '650px' }" :closable="true"
        @hide="onHide">
        <div v-if="facturaData" class="">
            <!-- Mensaje de confirmación -->
            <div class="mb-6 text-center">
                <i class="pi pi-exclamation-triangle text-orange-500 text-4xl mb-4"></i>
                <p class="text-xl font-semibold mb-2 text-gray-800">
                    {{ isFacturaInReadOnlyState ? 'Detalles de la Factura' : '¿Qué acción deseas realizar con esta factura?' }}
                </p>
                <p class="text-sm text-gray-600">
                    {{ isFacturaInReadOnlyState ? 'La factura ya ha sido procesada' : 'Tu decisión será registrada en el sistema' }}
                </p>
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
            
            <!-- Sección de Comentario -->
            <div class="border-t pt-6">
                <h4 class="font-semibold mb-4 flex items-center gap-2">
                    <i class="pi pi-comment"></i>
                    {{ getCommentSectionTitle() }}
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
                                <div class="bg-gray-50 rounded-2xl px-4 py-3 border border-gray-200 hover:bg-gray-100 transition-colors focus-within:bg-white focus-within:border-blue-300"
                                     :class="{ 'bg-gray-100 opacity-60': isFacturaInReadOnlyState }">
                                    <Textarea 
                                        v-model="comentario" 
                                        rows="3" 
                                        class="w-full border-0 bg-transparent resize-none text-sm focus:ring-0 focus:outline-none p-0 placeholder-gray-500"
                                        :placeholder="getCommentPlaceholder()"
                                        :maxlength="300"
                                        :disabled="isFacturaInReadOnlyState"
                                        ref="textareaRef"
                                    />
                                </div>
                                
                                <!-- Información y contador -->
                                <div class="flex justify-between items-center mt-3 text-xs">
                                    <div class="flex items-center gap-2 text-gray-500">
                                        <i class="pi pi-info-circle"></i>
                                        <span>{{ getActionStepInfo() }}</span>
                                    </div>
                                    <span v-if="!isFacturaInReadOnlyState" class="text-gray-500" :class="{ 'text-red-500': comentario.length > 250 }">
                                        {{ comentario.length }}/300
                                    </span>
                                </div>

                                <!-- Información general de acción -->
                                <div class="mt-3 p-2 rounded-lg" :class="getInfoBoxClass()">
                                    <div class="flex items-center gap-2 text-sm">
                                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-white font-bold text-xs" 
                                             :class="getInfoBoxIconClass()">
                                            <i :class="getInfoBoxIcon()" class="text-xs"></i>
                                        </div>
                                        <span class="font-medium">{{ getInfoBoxText() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de acción -->
        <template #footer>
            <div class="flex justify-between items-center w-full">
                <div class="text-sm text-gray-600">
                    <i v-if="facturaData?.estado === 'active'" class="pi pi-check-circle text-green-500 mr-1"></i>
                    <i v-else-if="facturaData?.estado === 'inactive'" class="pi pi-clock text-gray-500 mr-1"></i>
                    <i v-else-if="facturaData?.estado === 'expired'" class="pi pi-exclamation-circle text-orange-500 mr-1"></i>
                    <i v-else-if="facturaData?.estado === 'judicialized'" class="pi pi-ban text-red-500 mr-1"></i>
                    <i v-else-if="facturaData?.estado === 'reprogramed'" class="pi pi-calendar text-blue-500 mr-1"></i>
                    <i v-else-if="facturaData?.estado === 'paid'" class="pi pi-check text-green-600 mr-1"></i>
                    <i v-else-if="facturaData?.estado === 'canceled'" class="pi pi-times-circle text-red-500 mr-1"></i>
                    <i v-else-if="facturaData?.estado === 'daStandby'" class="pi pi-pause text-yellow-500 mr-1"></i>
                    <i v-else-if="facturaData?.PrimerStado === 'observed'" class="pi pi-eye text-orange-600 mr-1"></i>
                    <i v-else-if="facturaData?.PrimerStado === 'rejected'" class="pi pi-times-circle text-red-600 mr-1"></i>
                    <i v-else-if="facturaData?.estado === 'annulled'" class="pi pi-times-circle text-gray-500 mr-1"></i>
                    
                    <span :class="getStatusTextClass()">
                        {{ getStatusLabel() }}
                    </span>
                </div>
                
                <div class="flex gap-2">
                    <Button 
                        label="Cerrar" 
                        severity="secondary" 
                        text 
                        @click="onCancel" 
                        :disabled="loading" 
                        icon="pi pi-times"
                    />
                    
                    <!-- Botones de acción - Solo se muestran si la factura NO está en estado readonly -->
                    <template v-if="!isFacturaInReadOnlyState">
                        <!-- Botón de Observación -->
                        <Button 
                            label="Observar"
                            @click="onObservar" 
                            :loading="loading && actionType === 'observe'" 
                            :disabled="isObservacionAction && !comentario.trim()"
                            icon="pi pi-eye" 
                            severity="warn"
                            class="px-4"
                        />
                        
                        <!-- Botón de Rechazo -->
                        <Button 
                            label="Rechazar"
                            @click="onReject" 
                            :loading="loading && actionType === 'reject'" 
                            :disabled="isRechazoAction && !comentario.trim()"
                            icon="pi pi-times" 
                            severity="danger"
                            class="px-4"
                        />
                        
                        <!-- Botón de Aprobación -->
                        <Button 
                            label="Aprobar"
                            @click="onApprove" 
                            :loading="loading && actionType === 'approve'" 
                            icon="pi pi-check" 
                            severity="success"
                            class="px-4"
                        />
                    </template>
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
const emit = defineEmits(['update:modelValue', 'approved', 'rejected', 'observed', 'cancelled', 'confirmed']);

// Reactive data
const visible = ref(props.modelValue);
const facturaData = ref(null);
const loading = ref(false);
const comentario = ref('');
const textareaRef = ref(null);
const actionType = ref(''); // 'approve', 'reject', 'observe'

// Computed properties
const isObservacionAction = computed(() => {
    return actionType.value === 'observe';
});

const isRechazoAction = computed(() => {
    return actionType.value === 'reject';
});

const isFacturaInReadOnlyState = computed(() => {
    if (!facturaData.value) return false;
    return facturaData.value.PrimerStado === 'observed' || facturaData.value.PrimerStado === 'rejected';
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

const fetchFacturaData = async () => {
    if (!props.facturaId) return;

    try {
        loading.value = true;
        const response = await axios.get(`/invoices/${props.facturaId}`);
        facturaData.value = response.data?.data || null;
        
        // Si la factura está en estado readonly, cargar el comentario existente
        if (isFacturaInReadOnlyState.value && facturaData.value?.approval1_comment) {
            comentario.value = facturaData.value.approval1_comment;
        }
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
    if (!props.facturaId) return;

    try {
        loading.value = true;
        actionType.value = 'approve';

        const payload = {};
        
        if (comentario.value.trim()) {
            payload.comment = comentario.value.trim();
        }

        const response = await axios.patch(`/invoices/${props.facturaId}/activacion`, payload);

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data?.message || 'Aprobación registrada correctamente',
            life: 5000
        });
        
        emit('approved', response.data);
        emit('confirmed', response.data);
        visible.value = false;

    } catch (error) {
        console.error('Error al aprobar factura:', error);
        handleError(error, 'Error al procesar la aprobación');
    } finally {
        loading.value = false;
        actionType.value = '';
    }
};

const onReject = async () => {
    if (!props.facturaId || !comentario.value.trim()) {
        toast.add({
            severity: 'warn',
            summary: 'Comentario requerido',
            detail: 'Debes agregar un comentario para rechazar la factura',
            life: 5000
        });
        return;
    }

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
        
        emit('rejected', response.data);
        emit('confirmed', response.data);
        visible.value = false;

    } catch (error) {
        console.error('Error al rechazar factura:', error);
        handleError(error, 'Error al procesar el rechazo');
    } finally {
        loading.value = false;
        actionType.value = '';
    }
};

const onObservar = async () => {
    if (!props.facturaId || !comentario.value.trim()) {
        toast.add({
            severity: 'warn',
            summary: 'Comentario requerido',
            detail: 'Debes agregar un comentario para observar la factura',
            life: 5000
        });
        return;
    }

    try {
        loading.value = true;
        actionType.value = 'observe';

        const payload = {
            comment: comentario.value.trim()
        };

        const response = await axios.patch(`/invoices/${props.facturaId}/observacion`, payload);

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data?.message || 'Observación registrada correctamente',
            life: 5000
        });
        
        emit('observed', response.data);
        emit('confirmed', response.data);
        visible.value = false;

    } catch (error) {
        console.error('Error al observar factura:', error);
        handleError(error, 'Error al procesar la observación');
    } finally {
        loading.value = false;
        actionType.value = '';
    }
};

const handleError = (error, defaultMessage) => {
    const errorMessage = error.response?.data?.message || defaultMessage;
    toast.add({
        severity: 'error',
        summary: 'Error',
        detail: errorMessage,
        life: 5000
    });
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

const getCommentSectionTitle = () => {
    if (isFacturaInReadOnlyState.value) {
        return 'Comentario registrado';
    }
    
    if (isObservacionAction.value || isRechazoAction.value) {
        return 'Comentario (Requerido)';
    }
    
    return 'Comentario';
};

const getCommentPlaceholder = () => {
    if (isFacturaInReadOnlyState.value) {
        return 'Comentario registrado en el sistema';
    }
    
    if (isObservacionAction.value) {
        return 'Describe la observación que deseas realizar (requerido)...';
    }
    
    if (isRechazoAction.value) {
        return 'Describe el motivo del rechazo (requerido)...';
    }
    
    return 'Escribe tu comentario...';
};

const getActionStepInfo = () => {
    if (isFacturaInReadOnlyState.value) {
        const estado = facturaData.value?.PrimerStado;
        if (estado === 'observed') {
            return 'Esta factura ha sido marcada como observada';
        }
        if (estado === 'rejected') {
            return 'Esta factura ha sido rechazada';
        }
        return 'Esta factura ya ha sido procesada';
    }
    
    if (isObservacionAction.value) {
        return 'La observación cambiará el estado de la factura y requerirá revisión';
    }
    
    if (isRechazoAction.value) {
        return 'El rechazo cambiará el estado de la factura permanentemente';
    }
    
    return 'Tu comentario será registrado con tu decisión sobre la factura';
};

const getInfoBoxClass = () => {
    if (isFacturaInReadOnlyState.value) {
        const estado = facturaData.value?.PrimerStado;
        if (estado === 'observed') {
            return 'bg-orange-50 border border-orange-200';
        }
        if (estado === 'rejected') {
            return 'bg-red-50 border border-red-200';
        }
    }
    return 'bg-blue-50 border border-blue-200';
};

const getInfoBoxIconClass = () => {
    if (isFacturaInReadOnlyState.value) {
        const estado = facturaData.value?.PrimerStado;
        if (estado === 'observed') {
            return 'bg-orange-500';
        }
        if (estado === 'rejected') {
            return 'bg-red-500';
        }
    }
    return 'bg-blue-500';
};

const getInfoBoxIcon = () => {
    if (isFacturaInReadOnlyState.value) {
        const estado = facturaData.value?.PrimerStado;
        if (estado === 'observed') {
            return 'pi pi-eye';
        }
        if (estado === 'rejected') {
            return 'pi pi-times';
        }
    }
    return 'pi pi-user';
};

const getInfoBoxText = () => {
    if (isFacturaInReadOnlyState.value) {
        const estado = facturaData.value?.PrimerStado;
        if (estado === 'observed') {
            return 'Factura Observada';
        }
        if (estado === 'rejected') {
            return 'Factura Rechazada';
        }
    }
    return 'Acción de Usuario';
};

const getStatusLabel = () => {
    if (!facturaData.value) return '';
    
    // Prioridad al PrimerStado si existe
    if (facturaData.value.PrimerStado === 'observed') {
        return 'Observada';
    }
    if (facturaData.value.PrimerStado === 'rejected') {
        return 'Rechazada';
    }
    
    // Si no hay PrimerStado, usar el estado normal
    const statusLabels = {
        'inactive': 'Inactiva',
        'active': 'Activa',
        'expired': 'Expirada',
        'judicialized': 'Judicializada',
        'reprogramed': 'Reprogramada',
        'paid': 'Pagada',
        'canceled': 'Cancelada',
        'daStandby': 'En Espera DA',
        'annulled': 'Anulada'
    };
    return statusLabels[facturaData.value.estado] || facturaData.value.estado;
};

const getStatusTextClass = () => {
    if (!facturaData.value) return 'text-gray-600';
    
    // Prioridad al PrimerStado si existe
    if (facturaData.value.PrimerStado === 'observed') {
        return 'text-orange-600 font-medium';
    }
    if (facturaData.value.PrimerStado === 'rejected') {
        return 'text-red-600 font-medium';
    }
    
    // Si no hay PrimerStado, usar el estado normal
    const statusClasses = {
        'inactive': 'text-gray-600 font-medium',
        'active': 'text-green-600 font-medium',
        'expired': 'text-orange-600 font-medium',
        'judicialized': 'text-red-600 font-medium',
        'reprogramed': 'text-blue-600 font-medium',
        'paid': 'text-green-700 font-medium',
        'canceled': 'text-red-600 font-medium',
        'daStandby': 'text-yellow-600 font-medium',
        'annulled': 'text-gray-500 font-medium'
    };
    return statusClasses[facturaData.value.estado] || 'text-gray-600';
};

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