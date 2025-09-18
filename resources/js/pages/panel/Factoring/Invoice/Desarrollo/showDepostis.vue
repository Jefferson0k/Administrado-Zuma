<template>
    <Dialog v-model:visible="dialogVisible" modal :closable="false" :draggable="false" class="mx-4"
        style="width: 95vw; max-width: 1200px;" @hide="onCancel">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <i class="pi pi-money-bill text-xl text-blue-600"></i>
                    <span class="text-xl font-semibold">Reembolsos del Inversionista</span>
                    <Tag v-if="investorData" :value="investorData.name" severity="info" class="ml-2" />
                </div>
                <Button icon="pi pi-times" severity="secondary" text rounded size="small" class="ml-auto"
                    @click="onCancel" v-tooltip.left="'Cerrar'" />
            </div>
        </template>

        <div v-if="loading" class="flex justify-center items-center py-8">
            <i class="pi pi-spinner pi-spin text-3xl text-blue-500"></i>
            <span class="ml-2 text-gray-600">Cargando reembolsos...</span>
        </div>

        <div v-else-if="refundsData && refundsData.length > 0" class="space-y-6">
            <div v-if="investorData" class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <h3 class="text-lg font-semibold mb-4 text-blue-800 flex items-center gap-2">
                    <i class="pi pi-user"></i>
                    Información del Inversionista
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm font-medium text-blue-600">Nombre Completo</label>
                        <p class="text-sm text-gray-800">{{ investorData.name }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-blue-600">Documento</label>
                        <p class="text-sm text-gray-800">{{ investorData.document }}</p>
                    </div>
                </div>
            </div>

            <!-- Tabla de Reembolsos -->
            <div>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Reembolsos</h3>
                    <Tag severity="contrast" :value="`${refundsData.length} reembolsos`" />
                </div>

                <DataTable :value="refundsData" class="p-datatable-sm" :paginator="refundsData.length > 10" :rows="10"
                    :rowsPerPageOptions="[5, 10, 20, 50]" scrollable scrollHeight="500px" sortMode="multiple">

                    <Column field="deposit_reembloso.nro_operation" header="Nº Operación" sortable
                        style="min-width: 10rem">
                        <template #body="slotProps">
                            {{ slotProps.data.deposit_reembloso?.nro_operation || 'N/A' }}
                        </template>
                    </Column>

                    <Column field="currency" header="Moneda" sortable style="min-width: 6rem">
                        <template #body="slotProps">
                            <Tag :value="slotProps.data.currency"
                                :severity="slotProps.data.currency === 'PEN' ? 'info' : 'success'" />
                        </template>
                    </Column>

                    <Column field="amount" header="Monto Reembolso" sortable style="min-width: 11rem">
                        <template #body="slotProps">
                            <span class="font-semibold text-blue-600">
                                {{ formatCurrency(slotProps.data.amount, slotProps.data.currency) }}
                            </span>
                        </template>
                    </Column>

                    <Column field="invoice.codigo" header="Código Factura" sortable style="min-width: 10rem">
                        <template #body="slotProps">
                            <Tag :value="slotProps.data.invoice?.codigo || 'N/A'" severity="secondary" />
                        </template>
                    </Column>

                    <Column field="due_date" header="Fecha Vencimiento" sortable style="min-width: 12rem">
                        <template #body="slotProps">
                            {{ formatDate(slotProps.data.due_date) }}
                        </template>
                    </Column>

                    <Column header="Comprobante" style="min-width: 10rem">
                        <template #body="slotProps">
                            <Button v-if="slotProps.data.deposit_reembloso?.resource_path" icon="pi pi-eye"
                                severity="info" size="small" text v-tooltip.top="'Ver comprobante'"
                                @click="viewReceipt(slotProps.data.deposit_reembloso.resource_path)" />
                            <span v-else class="text-gray-400 text-sm">Sin comprobante</span>
                        </template>
                    </Column>

                    <Column header="Cuenta Bancaria" style="min-width: 15rem">
                        <template #body="slotProps">
                            <div v-if="slotProps.data.deposit_reembloso?.bank_account" class="text-sm">
                                <div class="font-medium text-gray-700">
                                    {{ slotProps.data.deposit_reembloso.bank_account.bank_name }}
                                </div>
                                <div class="text-gray-600">
                                    {{ maskAccountNumber(slotProps.data.deposit_reembloso.bank_account.cc) }}
                                    ({{ slotProps.data.deposit_reembloso.bank_account.alias }})
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ slotProps.data.deposit_reembloso.bank_account.type.toUpperCase() }}
                                </div>
                            </div>
                            <span v-else class="text-gray-400 text-sm">Sin cuenta</span>
                        </template>
                    </Column>

                    <Column header="">
                        <template #body="slotProps">
                            <div class="flex gap-1">
                                <Button icon="pi pi-check" severity="success" size="small" text
                                    v-tooltip.top="'Confirmar reembolso'" @click="confirmRefund(slotProps.data)" />
                                <Button icon="pi pi-times" severity="danger" size="small" text
                                    v-tooltip.top="'Rechazar reembolso'" @click="rejectRefund(slotProps.data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <div v-else class="text-center py-12">
            <i class="pi pi-inbox text-6xl text-gray-400 mb-4 block"></i>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">No hay reembolsos registrados</h3>
            <p class="text-gray-500">Este inversionista no tiene reembolsos en el sistema</p>
        </div>

        <template #footer>
            <div class="flex justify-between items-center">
                <div v-if="refundsData && refundsData.length > 0" class="text-sm text-gray-600">
                    Total: {{ refundsData.length }} reembolso(s)
                </div>
                <div class="flex gap-2 ml-auto">
                    <Button label="Cerrar" icon="pi pi-times" severity="secondary" text @click="onCancel" />
                </div>
            </div>
        </template>
    </Dialog>

    <!-- Dialog de Confirmación de Reembolso -->
    <Dialog v-model:visible="confirmRefundDialog" modal :closable="false" style="width: 600px;" class="mx-4">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <span class="text-lg font-semibold">Confirmar Reembolso</span>
                <Button icon="pi pi-times" severity="secondary" text rounded size="small"
                    @click="confirmRefundDialog = false" v-tooltip.left="'Cerrar'" />
            </div>
        </template>

        <div v-if="selectedRefund" class="space-y-4">
            <div class="flex items-start gap-3">
                <i class="pi pi-check-circle text-green-500 text-2xl"></i>
                <div class="flex-1">
                    <p class="mb-3">
                        ¿Está seguro que desea confirmar el reembolso por
                        <strong>{{ formatCurrency(selectedRefund.amount, selectedRefund.currency) }}</strong>?
                    </p>
                    <div class="bg-gray-50 p-4 rounded">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600">Nº Operación:</span>
                                <div class="font-medium">{{ selectedRefund.deposit_reembloso?.nro_operation || 'N/A' }}
                                </div>
                            </div>
                            <div>
                                <span class="text-gray-600">Factura:</span>
                                <div class="font-medium">{{ selectedRefund.invoice?.codigo || 'N/A' }}</div>
                            </div>
                            <div>
                                <span class="text-gray-600">Fecha Vencimiento:</span>
                                <div class="font-medium">{{ formatDate(selectedRefund.due_date) }}</div>
                            </div>
                            <div>
                                <span class="text-gray-600">Banco:</span>
                                <div class="font-medium">{{ selectedRefund.deposit_reembloso?.bank_account?.bank_name ||
                                    'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Message severity="info" :closable="false">
                <span class="text-sm">
                    Esta acción no enviará ninguna petición por ahora, es solo para demostración.
                </span>
            </Message>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cancelar" severity="secondary" text @click="confirmRefundDialog = false" />
                <Button label="Confirmar Reembolso" severity="success" @click="processRefundConfirmation" />
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
import Message from 'primevue/message';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    investorId: {
        type: String,
        default: null
    }
});

const emit = defineEmits(['update:modelValue', 'cancelled']);

const toast = useToast();
const loading = ref(false);
const refundsData = ref([]);
const investorData = ref(null);
const confirmRefundDialog = ref(false);
const selectedRefund = ref(null);

const dialogVisible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

const formatCurrency = (value, currency) => {
    if (!value) return '';
    const number = parseFloat(value);
    let symbol = currency === 'PEN' ? 'S/' : 'US$';
    return `${symbol} ${number.toLocaleString('es-PE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })}`;
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
};

const maskAccountNumber = (accountNumber) => {
    if (!accountNumber) return '';
    const str = accountNumber.toString();
    if (str.length <= 4) return str;
    return str.slice(0, 4) + '*'.repeat(Math.max(0, str.length - 8)) + str.slice(-4);
};

const confirmRefund = (refund) => {
    selectedRefund.value = refund;
    confirmRefundDialog.value = true;
};

const rejectRefund = (refund) => {
    toast.add({
        severity: 'warn',
        summary: 'Acción Simulada',
        detail: `Reembolso rechazado para ${formatCurrency(refund.amount, refund.currency)}`,
        life: 4000
    });
};

const processRefundConfirmation = () => {
    toast.add({
        severity: 'success',
        summary: 'Confirmación Simulada',
        detail: `Reembolso confirmado por ${formatCurrency(selectedRefund.value.amount, selectedRefund.value.currency)}`,
        life: 4000
    });

    confirmRefundDialog.value = false;
    selectedRefund.value = null;
};

const viewReceipt = (resourcePath) => {
    if (resourcePath) {
        window.open(resourcePath, '_blank');
    }
};

const loadRefundsData = async () => {
    if (!props.investorId) return;

    loading.value = true;
    try {
        const response = await axios.get(`/payments/deposits/investor/${props.investorId}`);
        if (response.data.data) {
            if (!Array.isArray(response.data.data)) {
                refundsData.value = [response.data.data];
            } else {
                refundsData.value = response.data.data;
            }
            if (refundsData.value.length > 0 && refundsData.value[0].investor) {
                investorData.value = refundsData.value[0].investor;
            }
        } else {
            refundsData.value = [];
        }
    } catch (error) {
        console.error('Error al cargar reembolsos:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error al cargar los reembolsos del inversionista',
            life: 5000
        });
    } finally {
        loading.value = false;
    }
};

const onCancel = () => {
    dialogVisible.value = false;
    emit('cancelled');
};

watch(() => props.modelValue, (newValue) => {
    if (newValue && props.investorId) {
        loadRefundsData();
    }
});

watch(() => props.investorId, () => {
    refundsData.value = [];
    investorData.value = null;
});
</script>