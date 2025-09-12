<template>
    <Dialog v-model:visible="dialogVisible" modal :closable="false" :draggable="false" class="mx-4"
        style="width: 95vw; max-width: 1200px;" @hide="onCancel">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <i class="pi pi-money-bill text-xl text-green-600"></i>
                    <span class="text-xl font-semibold">Depósitos del Inversionista</span>
                    <Tag v-if="investorData" :value="investorData.name + ' ' + investorData.first_last_name" 
                        severity="info" class="ml-2" />
                </div>
                <Button 
                    icon="pi pi-times" 
                    severity="secondary" 
                    text 
                    rounded 
                    size="small"
                    class="ml-auto"
                    @click="onCancel"
                    v-tooltip.left="'Cerrar'"
                />
            </div>
        </template>

        <div v-if="loading" class="flex justify-center items-center py-8">
            <i class="pi pi-spinner pi-spin text-3xl text-green-500"></i>
            <span class="ml-2 text-gray-600">Cargando depósitos...</span>
        </div>

        <div v-else-if="depositsData && depositsData.length > 0" class="space-y-6">
            <!-- Información del Inversionista -->
            <div v-if="investorData" class="bg-green-50 p-4 rounded-lg border border-green-200">
                <h3 class="text-lg font-semibold mb-4 text-green-800 flex items-center gap-2">
                    <i class="pi pi-user"></i>
                    Información del Inversionista
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm font-medium text-green-600">Nombre Completo</label>
                        <p class="text-sm text-gray-800">
                            {{ investorData.name }} {{ investorData.first_last_name }} {{ investorData.second_last_name }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-green-600">Documento</label>
                        <p class="text-sm text-gray-800">{{ investorData.document }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-green-600">Email</label>
                        <p class="text-sm text-gray-800">{{ investorData.email }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-green-600">Teléfono</label>
                        <p class="text-sm text-gray-800">{{ investorData.telephone }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-green-600">Código</label>
                        <p class="text-sm text-gray-800">{{ investorData.codigo }}</p>
                    </div>
                </div>
            </div>
            <!-- Tabla de Depósitos -->
            <div>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Historial de Depósitos</h3>
                    <Tag severity="contrast" :value="`${depositsData.length} depósitos`" />
                </div>

                <DataTable :value="depositsData" class="p-datatable-sm"
                    :paginator="depositsData.length > 10" :rows="10"
                    :rowsPerPageOptions="[5, 10, 20, 50]" 
                    scrollable scrollHeight="500px"
                    sortMode="multiple">
                    
                    <Column field="nro_operation" header="Nº Operación" sortable style="min-width: 10rem" />
                    
                    <Column field="currency" header="Moneda" sortable style="min-width: 6rem">
                        <template #body="slotProps">
                            <Tag :value="slotProps.data.currency" 
                                :severity="slotProps.data.currency === 'PEN' ? 'info' : 'success'" />
                        </template>
                    </Column>
                    
                    <Column field="amount" header="Monto" sortable style="min-width: 10rem">
                        <template #body="slotProps">
                            <span class="font-semibold">
                                {{ formatCurrency(slotProps.data.amount, slotProps.data.currency) }}
                            </span>
                        </template>
                    </Column>
                    
                    <Column field="description" header="Descripción" style="min-width: 20rem" />
                    
                    <Column field="created_at" header="Fecha Creación" sortable style="min-width: 12rem">
                        <template #body="slotProps">
                            {{ formatDate(slotProps.data.created_at) }}
                        </template>
                    </Column>
                    
                    <Column header="Comprobante" style="min-width: 10rem">
                        <template #body="slotProps">
                            <Button v-if="slotProps.data.resource_path" 
                                icon="pi pi-eye" 
                                severity="info" 
                                size="small" 
                                text
                                v-tooltip.top="'Ver comprobante'" 
                                @click="viewReceipt(slotProps.data.resource_path)" />
                            <span v-else class="text-gray-400 text-sm">Sin comprobante</span>
                        </template>
                    </Column>
                    
                    <Column header="Cuenta Bancaria" style="min-width: 15rem">
                        <template #body="slotProps">
                            <div v-if="slotProps.data.bank_account" class="text-sm">
                                <div class="text-gray-600">
                                    {{ maskAccountNumber(slotProps.data.bank_account.cc) }} 
                                    ({{ slotProps.data.bank_account.alias }})
                                </div>
                            </div>
                            <span v-else class="text-gray-400 text-sm">Sin cuenta</span>
                        </template>
                    </Column>
                    
                    <Column header="">
                        <template #body="slotProps">
                            <div class="flex gap-1">
                                <Button 
                                    icon="pi pi-check" 
                                    severity="success" 
                                    size="small" 
                                    text
                                    v-tooltip.top="'Confirmar pago'" 
                                    @click="confirmPayment(slotProps.data)" 
                                />
                                <Button 
                                    icon="pi pi-times" 
                                    severity="danger" 
                                    size="small" 
                                    text
                                    v-tooltip.top="'Rechazar pago'" 
                                    @click="confirmPayment(slotProps.data)" 
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <div v-else class="text-center py-12">
            <i class="pi pi-inbox text-6xl text-gray-400 mb-4 block"></i>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">No hay depósitos registrados</h3>
            <p class="text-gray-500">Este inversionista no tiene depósitos en el sistema</p>
        </div>

        <template #footer>
            <div class="flex justify-between items-center">
                <div v-if="depositsData && depositsData.length > 0" class="text-sm text-gray-600">
                    Total: {{ depositsData.length }} depósito(s)
                </div>
                <div class="flex gap-2 ml-auto">
                    <Button label="Cerrar" icon="pi pi-times" severity="secondary" text @click="onCancel" />
                </div>
            </div>
        </template>
    </Dialog>

    <!-- Dialog de Confirmación de Pago -->
    <Dialog v-model:visible="confirmPaymentDialog" modal :closable="false"
        style="width: 500px;" class="mx-4">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <span class="text-lg font-semibold">Confirmar Pago</span>
                <Button 
                    icon="pi pi-times" 
                    severity="secondary" 
                    text 
                    rounded 
                    size="small"
                    @click="confirmPaymentDialog = false"
                    v-tooltip.left="'Cerrar'"
                />
            </div>
        </template>
        
        <div v-if="selectedDeposit" class="space-y-4">
            <div class="flex items-start gap-3">
                <i class="pi pi-check-circle text-green-500 text-2xl"></i>
                <div>
                    <p class="mb-3">
                        ¿Está seguro que desea confirmar el pago del depósito por 
                        <strong>{{ formatCurrency(selectedDeposit.amount, selectedDeposit.currency) }}</strong>?
                    </p>
                    <div class="bg-gray-50 p-3 rounded">
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-gray-600">Nº Operación:</span>
                                <div class="font-medium">{{ selectedDeposit.nro_operation }}</div>
                            </div>
                            <div>
                                <span class="text-gray-600">Fecha:</span>
                                <div class="font-medium">{{ formatDate(selectedDeposit.created_at) }}</div>
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
                <Button label="Cancelar" severity="secondary" text @click="confirmPaymentDialog = false" />
                <Button label="Confirmar Pago" severity="success" @click="processPaymentConfirmation" />
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
const depositsData = ref([]);
const investorData = ref(null);
const confirmPaymentDialog = ref(false);
const selectedDeposit = ref(null);

const dialogVisible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

// Funciones de utilidad
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
    return new Date(dateString).toLocaleString('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const maskAccountNumber = (accountNumber) => {
    if (!accountNumber) return '';
    const str = accountNumber.toString();
    if (str.length <= 4) return str;
    return str.slice(0, 4) + '*'.repeat(str.length - 8) + str.slice(-4);
};

// Funciones de acciones
const confirmPayment = (deposit) => {
    selectedDeposit.value = deposit;
    confirmPaymentDialog.value = true;
};

const processPaymentConfirmation = () => {
    // Por ahora solo mostramos un mensaje, no enviamos petición
    toast.add({
        severity: 'success',
        summary: 'Confirmación Simulada',
        detail: `Pago confirmado para el depósito de ${formatCurrency(selectedDeposit.value.amount, selectedDeposit.value.currency)}`,
        life: 4000
    });
    
    confirmPaymentDialog.value = false;
    selectedDeposit.value = null;
};

const viewReceipt = (resourcePath) => {
    if (resourcePath) {
        window.open(resourcePath, '_blank');
    }
};

// Cargar datos de depósitos
const loadDepositsData = async () => {
    if (!props.investorId) return;

    loading.value = true;
    try {
        const response = await axios.get(`/payments/deposits/investor/${props.investorId}`);
        
        // Asumiendo que la respuesta tiene la estructura del JSON que proporcionaste
        depositsData.value = response.data.data || [];
        
        // Extraer información del inversionista del primer depósito
        if (depositsData.value.length > 0 && depositsData.value[0].investor) {
            investorData.value = depositsData.value[0].investor;
        }
        
    } catch (error) {
        console.error('Error al cargar depósitos:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error al cargar los depósitos del inversionista',
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

// Watchers
watch(() => props.modelValue, (newValue) => {
    if (newValue && props.investorId) {
        loadDepositsData();
    }
});

watch(() => props.investorId, () => {
    depositsData.value = [];
    investorData.value = null;
});
</script>

<style scoped>
.field {
    margin-bottom: 1rem;
}
</style>