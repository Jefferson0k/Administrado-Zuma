<template>
    <Dialog v-model:visible="dialogVisible" :style="{ width: '900px' }" header="Detalle del Retiro" :modal="true">
        <div class="flex flex-col gap-6" v-if="withdrawData">
            <!-- Información Principal -->
            <div class="grid grid-cols-2 gap-6">
                <div class="p-4 border rounded-lg">
                    <h5 class="font-bold text-lg mb-4 text-blue-600 flex items-center gap-2">
                        <i class="pi pi-info-circle" />
                        Información General
                    </h5>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="font-medium text-gray-600">ID:</span>
                            <span class="font-mono text-sm">{{ withdrawData.id }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium text-gray-600">Tipo:</span>
                            <Tag :value="withdrawData.type" severity="info" />
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium text-gray-600">Monto:</span>
                            <span class="font-semibold text-lg">
                                {{ formatCurrency(withdrawData.amount, withdrawData.currency) }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium text-gray-600">Moneda:</span>
                            <Tag :value="withdrawData.currency" severity="secondary" />
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium text-gray-600">Estado:</span>
                            <Tag :value="getStatusLabel(withdrawData.status)" 
                                :severity="getStatusSeverity(withdrawData.status)" />
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium text-gray-600">Fecha:</span>
                            <span>{{ formatDate(withdrawData.date) }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="p-4 border rounded-lg" v-if="withdrawData.related">
                    <h5 class="font-bold text-lg mb-4 text-green-600 flex items-center gap-2">
                        <i class="pi pi-credit-card" />
                        Información de Pago
                    </h5>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="font-medium text-gray-600">N° Operación:</span>
                            <span class="font-mono">
                                {{ withdrawData.related.nro_operation || 'Sin asignar' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium text-gray-600">Estado:</span>
                            <Tag :value="getStatusLabel(withdrawData.related.status)" 
                                :severity="getStatusSeverity(withdrawData.related.status)" />
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium text-gray-600">Fecha de Pago:</span>
                            <span>{{ formatDate(withdrawData.related.deposit_pay_date) || 'Sin definir' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium text-gray-600">Investor ID:</span>
                            <span class="font-mono text-sm text-blue-600">
                                {{ withdrawData.related.investor_id }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium text-gray-600">Bank Account ID:</span>
                            <span class="font-mono text-sm">
                                {{ withdrawData.related.bank_account_id }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Propósito -->
            <div class="p-4 border rounded-lg bg-yellow-50" v-if="withdrawData.related?.purpouse">
                <h5 class="font-bold text-lg mb-3 text-yellow-700 flex items-center gap-2">
                    <i class="pi pi-exclamation-triangle" />
                    Propósito del Retiro
                </h5>
                <p class="text-gray-700 italic">{{ withdrawData.related.purpouse }}</p>
            </div>

            <!-- Estado de Aprobaciones -->
            <div class="grid grid-cols-2 gap-4" v-if="withdrawData.related">
                <div class="p-4 border rounded-lg">
                    <h6 class="font-semibold mb-3 text-purple-600 flex items-center gap-2">
                        <i class="pi pi-check-circle" />
                        Primera Aprobación
                    </h6>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="font-medium">Estado:</span>
                            <Tag v-if="withdrawData.related.approval1_status"
                                :value="getStatusLabel(withdrawData.related.approval1_status)" 
                                :severity="getStatusSeverity(withdrawData.related.approval1_status)" 
                                class="text-xs" />
                            <span v-else class="text-gray-500 italic">Pendiente</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Aprobado por:</span>
                            <span>{{ withdrawData.related.approval1_by || 'Sin asignar' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Fecha:</span>
                            <span>{{ formatDateTime(withdrawData.related.approval1_at) || 'Sin fecha' }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="font-medium">Comentario:</span>
                            <span class="text-gray-600 italic text-xs">
                                {{ withdrawData.related.approval1_comment || 'Sin comentario' }}
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="p-4 border rounded-lg">
                    <h6 class="font-semibold mb-3 text-purple-600 flex items-center gap-2">
                        <i class="pi pi-verified" />
                        Segunda Aprobación
                    </h6>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="font-medium">Estado:</span>
                            <Tag v-if="withdrawData.related.approval2_status"
                                :value="getStatusLabel(withdrawData.related.approval2_status)" 
                                :severity="getStatusSeverity(withdrawData.related.approval2_status)" 
                                class="text-xs" />
                            <span v-else class="text-gray-500 italic">Pendiente</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Aprobado por:</span>
                            <span>{{ withdrawData.related.approval2_by || 'Sin asignar' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Fecha:</span>
                            <span>{{ formatDateTime(withdrawData.related.approval2_at) || 'Sin fecha' }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="font-medium">Comentario:</span>
                            <span class="text-gray-600 italic text-xs">
                                {{ withdrawData.related.approval2_comment || 'Sin comentario' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historial de Cambios -->
            <div class="p-4 border rounded-lg bg-gray-50" v-if="withdrawData.related">
                <h5 class="font-bold text-lg mb-3 text-gray-700 flex items-center gap-2">
                    <i class="pi pi-history" />
                    Historial
                </h5>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm"><strong>Creado:</strong></p>
                        <p class="text-xs text-gray-600">{{ formatDateTime(withdrawData.related.created_at) }}</p>
                        <p class="text-xs text-gray-500">Por: {{ withdrawData.related.created_by || 'Sistema' }}</p>
                    </div>
                    <div>
                        <p class="text-sm"><strong>Última actualización:</strong></p>
                        <p class="text-xs text-gray-600">{{ formatDateTime(withdrawData.related.updated_at) }}</p>
                        <p class="text-xs text-gray-500">Por: {{ withdrawData.related.updated_by || 'Sistema' }}</p>
                    </div>
                </div>
            </div>

            <!-- Información de Descripción -->
            <div class="p-4 border rounded-lg bg-blue-50" v-if="withdrawData.description || withdrawData.related?.description">
                <h5 class="font-bold text-lg mb-3 text-blue-700 flex items-center gap-2">
                    <i class="pi pi-file-text" />
                    Descripción
                </h5>
                <p class="text-gray-700">
                    {{ withdrawData.description || withdrawData.related?.description || 'Sin descripción adicional' }}
                </p>
            </div>

            <!-- Información Técnica -->
            <div class="p-3 bg-gray-100 rounded-lg">
                <details>
                    <summary class="cursor-pointer font-medium text-gray-600 hover:text-gray-800">
                        Información Técnica (Desarrollador)
                    </summary>
                    <div class="mt-3 p-2 bg-white rounded text-xs font-mono space-y-1">
                        <p><strong>Withdraw ID:</strong> {{ withdrawData.id }}</p>
                        <p><strong>Related ID:</strong> {{ withdrawData.related?.id }}</p>
                        <p><strong>Movement ID:</strong> {{ withdrawData.related?.movement_id }}</p>
                        <p><strong>Investor ID:</strong> {{ withdrawData.related?.investor_id }}</p>
                        <p><strong>Bank Account ID:</strong> {{ withdrawData.related?.bank_account_id }}</p>
                        <p><strong>Resource Path:</strong> {{ withdrawData.related?.resource_path || 'N/A' }}</p>
                    </div>
                </details>
            </div>
        </div>

        <template #footer>
            <Button label="Cerrar" icon="pi pi-times" @click="closeDialog" />
        </template>
    </Dialog>
</template>

<script setup>
import { computed } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

// Props
const props = defineProps({
    visible: {
        type: Boolean,
        default: false
    },
    withdrawData: {
        type: Object,
        default: null
    }
});

// Emits
const emit = defineEmits(['close']);

// Computed
const dialogVisible = computed({
    get: () => props.visible,
    set: (value) => {
        if (!value) emit('close');
    }
});

// Métodos
const closeDialog = () => {
    emit('close');
};

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

const formatDate = (dateString) => {
    if (!dateString) return null;
    return new Date(dateString).toLocaleDateString('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
};

const formatDateTime = (dateString) => {
    if (!dateString) return null;
    return new Date(dateString).toLocaleString('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
};
</script>