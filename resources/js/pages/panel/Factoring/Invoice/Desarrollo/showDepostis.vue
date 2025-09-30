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
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
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