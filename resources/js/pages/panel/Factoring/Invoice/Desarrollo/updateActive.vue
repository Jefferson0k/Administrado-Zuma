<template>
    <Dialog v-model:visible="visible" modal header="Confirmar Acción" :style="{ width: '510px' }" :closable="true"
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
                        <span class="font-medium">Tasa:</span>
                        <p class="">{{ facturaData.tasa }}%</p>
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
                        <span class="font-medium">Estado:</span>
                        <p class="">{{ facturaData.estado }}
                        </p>
                    </div>


                    <div>
                        <span class="font-medium">Fecha de Pago:</span>
                        <p class="">{{ facturaData.fechaPago }}</p>
                    </div>

                    <div>
                        <span class="font-medium">Número del Préstamo:</span>
                        <p class="">{{ facturaData.loan_number }}</p>
                    </div>

                    <div>
                        <span class="font-medium">Número de la Factura:</span>
                        <p class="">{{ facturaData.invoice_number }}</p>
                    </div>
                    <div>
                        <span class="font-medium">RUC del Proveedor:</span>
                        <p class="">{{ facturaData.RUC_client }}</p>
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
                                    <Textarea v-model="comentario" rows="3"
                                        class="w-full border-0 bg-transparent resize-none text-sm focus:ring-0 focus:outline-none p-0 placeholder-gray-500"
                                        :placeholder="getCommentPlaceholder()" :maxlength="300"
                                        :disabled="isFacturaInReadOnlyState" ref="textareaRef" />
                                </div>

                                <!-- Información y contador -->
                                <div class="flex justify-between items-center mt-3 text-xs">
                                    <div class="flex items-center gap-2 text-gray-500">
                                        <i class="pi pi-info-circle"></i>
                                        <span>{{ getActionStepInfo() }}</span>
                                    </div>
                                    <span v-if="!isFacturaInReadOnlyState" class="text-gray-500"
                                        :class="{ 'text-red-500': comentario.length > 250 }">
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
                    <i v-else-if="facturaData?.estado === 'expired'"
                        class="pi pi-exclamation-circle text-orange-500 mr-1"></i>
                    <i v-else-if="facturaData?.estado === 'judicialized'" class="pi pi-ban text-red-500 mr-1"></i>
                    <i v-else-if="facturaData?.estado === 'reprogramed'" class="pi pi-calendar text-blue-500 mr-1"></i>
                    <i v-else-if="facturaData?.estado === 'paid'" class="pi pi-check text-green-600 mr-1"></i>
                    <i v-else-if="facturaData?.estado === 'canceled'" class="pi pi-times-circle text-red-500 mr-1"></i>
                    <i v-else-if="facturaData?.estado === 'daStandby'" class="pi pi-pause text-yellow-500 mr-1"></i>
                    <i v-else-if="facturaData?.PrimerStado === 'observed'" class="pi pi-eye text-orange-600 mr-1"></i>
                    <i v-else-if="facturaData?.PrimerStado === 'rejected'"
                        class="pi pi-times-circle text-red-600 mr-1"></i>
                    <i v-else-if="facturaData?.estado === 'annulled'" class="pi pi-times-circle text-gray-500 mr-1"></i>

                    <span :class="getStatusTextClass()">
                        {{ getStatusLabel() }}
                    </span>
                </div>

                <div class="flex gap-2">
                    <Button label="Cerrar" severity="secondary" text @click="onCancel" :disabled="loading"
                        icon="pi pi-times" />

                    <!-- Botones de acción - Solo si NO está bloqueado globalmente -->
                    <template v-if="!isFacturaInReadOnlyState">
                        <!-- Mostrar SOLO Nivel 1 -->
                        <template v-if="showLevel1Group">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-gray-600">Nivel 1:</span>

                                <Button label="Observar" @click="() => { actionType = 'observe'; onObservar(); }"
                                    :loading="loading && actionType === 'observe'" icon="pi pi-eye" severity="warn"
                                    class="px-3" />

                                

                                <Button label="Aprobar" @click="() => { actionType = 'approve'; onApprove(); }"
                                    :loading="loading && actionType === 'approve'" icon="pi pi-check" severity="success"
                                    class="px-3" />
                            </div>
                        </template>

                        <!-- Mostrar SOLO Nivel 2 -->
                        <template v-else-if="showLevel2Group">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-gray-600">Nivel 2:</span>

                                <Button label="Observar" @click="() => { actionType = 'observe2'; onObservar2(); }"
                                    :loading="loading && actionType === 'observe2'" icon="pi pi-eye" severity="warn"
                                    class="px-3" />


                                <Button label="Aprobar" @click="() => { actionType = 'approve2'; onApprove2(); }"
                                    :loading="loading && actionType === 'approve2'" icon="pi pi-check"
                                    severity="success" class="px-3" />
                            </div>
                        </template>
                    </template>



                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch, computed, watchEffect } from 'vue';

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
const emit = defineEmits(['update:modelValue', 'approved', 'observed', 'cancelled', 'confirmed']);

// Reactive data
const visible = ref(props.modelValue);
const facturaData = ref(null);
const loading = ref(false);
const comentario = ref('');
const textareaRef = ref(null);
const actionType = ref(''); // 'approve', 'reject', 'observe'

// Computed properties
const isObservacionAction = computed(() => {
    return actionType.value === 'observe' || actionType.value === 'observe2';
});

const isRechazoAction = computed(() => {
    return actionType.value === 'reject' || actionType.value === 'reject2';
});


const _norm = (s) => (typeof s === 'string' ? s.trim().toLowerCase() : s ?? null);

const a1Status = computed(() => _norm(
    facturaData.value?.approval1_status ?? facturaData.value?.PrimerStado
));
const a2Status = computed(() => _norm(
    facturaData.value?.approval2_status ?? facturaData.value?.SegundaStado
));


const isAnyRejected = computed(() =>
    a1Status.value === 'rejected' || a2Status.value === 'rejected'
);

const areBothApproved = computed(() =>
    a1Status.value === 'approved' && a2Status.value === 'approved'
);

// BLOCK everything if one is rejected OR both are approved
const isFacturaInReadOnlyState = computed(() => isAnyRejected.value || areBothApproved.value);

// Buttons availability per your rules
// L1: available if not locked, and either L1 not approved yet OR L2 is observed
const canActLevel1 = computed(() => {

    if (!facturaData.value) return false;
    if (isFacturaInReadOnlyState.value) return false;
    return a1Status.value !== 'approved' || a2Status.value === 'observed';
});


// L2: available only if L1 is approved and L2 not decided yet (not approved/rejected/observed)
const canActLevel2 = computed(() => {
    if (!facturaData.value) return false;
    if (isFacturaInReadOnlyState.value) return false;
    const s2 = a2Status.value ?? '';
    return a1Status.value === 'approved' && !['approved', 'rejected', 'observed'].includes(s2);
});


// Mostrar SOLO un grupo de botones a la vez (sin botones deshabilitados)
const showLevel1Group = computed(() => {
  if (!facturaData.value) return false;
  if (isFacturaInReadOnlyState.value) return false;

  // Nivel 1 only if it’s still pending or observed itself
  return ['pending', 'observed', null, undefined, ''].includes(a1Status.value);
});



const showLevel2Group = computed(() => {
    if (!facturaData.value) return false;                    // 👈 guard
    if (isFacturaInReadOnlyState.value) return false;

    const n1Approved =
        a1Status.value === 'approved' ||
        !!facturaData.value?.approval1_at ||
        !!facturaData.value?.approval1_by;

    return n1Approved && !['approved', 'rejected'].includes(a2Status.value ?? '');
});



// ---- DEBUG ----
const showDebug = ref(true); // ponlo en false si no quieres ver el panel visual
const debugDump = computed(() => JSON.stringify({
    a1Status: a1Status.value,
    a2Status: a2Status.value,
    approval1_at: facturaData.value?.approval1_at ?? facturaData.value?.tiempoUno ?? null,
    approval1_by: facturaData.value?.approval1_by ?? facturaData.value?.userprimerNombre ?? facturaData.value?.userprimer ?? null,

    approval2_at: facturaData.value?.approval2_at ?? facturaData.value?.tiempoDos ?? null,
    approval2_by: facturaData.value?.approval2_by ?? facturaData.value?.userdosNombre ?? facturaData.value?.userdos ?? null,

    isAnyRejected: isAnyRejected.value,
    areBothApproved: areBothApproved.value,
    isFacturaInReadOnlyState: isFacturaInReadOnlyState.value,
    canActLevel1: canActLevel1.value,
    canActLevel2: canActLevel2.value,
    showLevel1Group: showLevel1Group.value,
    showLevel2Group: showLevel2Group.value,
    actionType: actionType.value,
}, null, 2));

watchEffect(() => {
    console.log('[DEBUG] Estados actuales =>', {
        a1Status: a1Status.value,
        a2Status: a2Status.value,
        approval1_at: facturaData.value?.approval1_at ?? facturaData.value?.tiempoUno ?? null,
        approval1_by: facturaData.value?.approval1_by ?? facturaData.value?.userprimerNombre ?? facturaData.value?.userprimer ?? null,
        approval2_at: facturaData.value?.approval2_at ?? facturaData.value?.tiempoDos ?? null,
        approval2_by: facturaData.value?.approval2_by ?? facturaData.value?.userdosNombre ?? facturaData.value?.userdos ?? null,
        isAnyRejected: isAnyRejected.value,
        areBothApproved: areBothApproved.value,
        isFacturaInReadOnlyState: isFacturaInReadOnlyState.value,
        canActLevel1: canActLevel1.value,
        canActLevel2: canActLevel2.value,
        showLevel1Group: showLevel1Group.value,
        showLevel2Group: showLevel2Group.value,
        actionType: actionType.value,
        raw: facturaData.value
    });
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
        // Prefill comentario when locked or reviewing
        const preferredComment = facturaData.value?.approval2_comment
            ?? facturaData.value?.approval1_comment
            ?? '';
        if (isFacturaInReadOnlyState.value && preferredComment) {
            comentario.value = preferredComment;
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
        // No cierres: refresca y muestra Nivel 2
        await fetchFacturaData();
        actionType.value = '';
        comentario.value = '';
    } catch (error) {
        console.error('Error al aprobar factura:', error);
        handleError(error, 'Error al procesar la aprobación');
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

const onApprove2 = async () => {
    if (!props.facturaId) return;
    try {
        loading.value = true;
        actionType.value = 'approve2';

        const payload = {};
        if (comentario.value.trim()) payload.comment = comentario.value.trim();

        const response = await axios.patch(`/invoices/${props.facturaId}/activacion2`, payload);

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data?.message || 'Aprobación (nivel 2) registrada correctamente',
            life: 5000
        });

        emit('approved', response.data);
        emit('confirmed', response.data);
        visible.value = false;
    } catch (error) {
        console.error('Error al aprobar factura (nivel 2):', error);
        handleError(error, 'Error al procesar la aprobación (nivel 2)');
    } finally {
        loading.value = false;
        actionType.value = '';
    }
};



const onObservar2 = async () => {
    if (!props.facturaId || !comentario.value.trim()) {
        toast.add({
            severity: 'warn',
            summary: 'Comentario requerido',
            detail: 'Debes agregar un comentario para observar en nivel 2',
            life: 5000
        });
        return;
    }

    try {
        loading.value = true;
        actionType.value = 'observe2';

        const payload = { comment: comentario.value.trim() };
        const response = await axios.patch(`/invoices/${props.facturaId}/observacion2`, payload);

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data?.message || 'Observación (nivel 2) registrada correctamente',
            life: 5000
        });

        emit('observed', response.data);
        emit('confirmed', response.data);
        visible.value = false;
    } catch (error) {
        console.error('Error al observar factura (nivel 2):', error);
        handleError(error, 'Error al procesar la observación (nivel 2)');
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
        if (isAnyRejected.value) {
            const nivel = a2Status.value === 'rejected' ? ' (nivel 2)' : a1Status.value === 'rejected' ? ' (nivel 1)' : '';
            return `Esta factura ha sido rechazada${nivel}`;
        }
        if (areBothApproved.value) {
            return 'Esta factura ha sido aprobada en ambos niveles';
        }
    }

    // Estado intermedio (no bloqueado)
    if (a2Status.value === 'observed') {
        return 'La observación en nivel 2 requiere revisión; nivel 1 está habilitado.';
    }
    if (a1Status.value === 'observed') {
        return 'La observación en nivel 1 requiere corrección antes de continuar.';
    }

    // Según la acción elegida
    if (isObservacionAction.value) {
        return actionType.value === 'observe2'
            ? 'La observación en nivel 2 enviará el caso a revisión y bloqueará nivel 2.'
            : 'La observación en nivel 1 enviará el caso a revisión.';
    }
    if (isRechazoAction.value) {
        return actionType.value === 'reject2'
            ? 'El rechazo en nivel 2 bloqueará el expediente definitivamente.'
            : 'El rechazo en nivel 1 bloqueará el expediente definitivamente.';
    }

    // Default
    return 'Tu comentario será registrado con tu decisión sobre la factura';
};


const getInfoBoxClass = () => {
    if (isAnyRejected.value) return 'bg-red-50 border border-red-200';
    if (areBothApproved.value) return 'bg-green-50 border border-green-200';
    if (a2Status.value === 'observed' || a1Status.value === 'observed') return 'bg-orange-50 border border-orange-200';
    return 'bg-blue-50 border border-blue-200';
};


const getInfoBoxIconClass = () => {
    if (isAnyRejected.value) return 'bg-red-500';
    if (areBothApproved.value) return 'bg-green-600';
    if (a2Status.value === 'observed' || a1Status.value === 'observed') return 'bg-orange-500';
    return 'bg-blue-500';
};


const getInfoBoxIcon = () => {
    if (isAnyRejected.value) return 'pi pi-times';
    if (areBothApproved.value) return 'pi pi-check-circle';
    if (a2Status.value === 'observed' || a1Status.value === 'observed') return 'pi pi-eye';
    return 'pi pi-user';
};


const getInfoBoxText = () => {
    if (isAnyRejected.value) {
        return a2Status.value === 'rejected' ? 'Rechazo (Nivel 2)' : 'Rechazo (Nivel 1)';
    }
    if (areBothApproved.value) return 'Aprobada en ambos niveles';
    if (a2Status.value === 'observed') return 'Observada (Nivel 2)';
    if (a1Status.value === 'observed') return 'Observada (Nivel 1)';
    if (a1Status.value === 'approved' && a2Status.value == null) return 'Pendiente de aprobación (Nivel 2)';
    return 'Acción de Usuario';
};


const getStatusLabel = () => {
    if (!facturaData.value) return '';

    // Reglas de prioridad sobre aprobaciones/observaciones/rechazos
    if (isAnyRejected.value) return 'Rechazada';
    if (areBothApproved.value) return 'Aprobada (N1 y N2)';
    if (a2Status.value === 'observed') return 'Observada (N2)';
    if (a1Status.value === 'observed') return 'Observada (N1)';
    if (a1Status.value === 'approved' && !a2Status.value) return 'Aprobada (N1)';

    // Si nada de lo anterior aplica, mostrar estado general
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
    return statusLabels[facturaData.value.estado] || facturaData.value.estado || '';
};


const getStatusTextClass = () => {
    if (!facturaData.value) return 'text-gray-600';

    if (isAnyRejected.value) return 'text-red-600 font-medium';
    if (areBothApproved.value) return 'text-green-700 font-medium';
    if (a2Status.value === 'observed' || a1Status.value === 'observed') return 'text-orange-600 font-medium';
    if (a1Status.value === 'approved' && !a2Status.value) return 'text-green-600 font-medium';

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