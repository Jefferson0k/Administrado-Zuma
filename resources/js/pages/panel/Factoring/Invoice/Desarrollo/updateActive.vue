<template>
    <Dialog v-model:visible="visible" modal header="Confirmar Activo" :style="{ width: '450px' }" :closable="true"
        @hide="onHide">
        <div v-if="facturaData" class="">
            <!-- Mensaje de confirmación -->
            <div class="mb-4 text-center">
                <i class="pi pi-exclamation-triangle text-orange-500 text-3xl mb-3"></i>
                <p class="text-lg font-medium mb-2">¿Estás seguro que deseas activar esta factura?</p>
                <p class="text-sm">Esta acción cambiará el estado de la factura a "Activo"</p>
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
        </div>

        <!-- Botones de acción -->
        <template #footer>
            <Button label="Cancelar" severity="secondary" outlined @click="onCancel" :disabled="loading" />
            <Button label="Sí, activar factura" @click="onConfirm" :loading="loading" icon="pi pi-check-circle" />
        </template>

    </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
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
const emit = defineEmits(['update:modelValue', 'confirmed', 'cancelled']);

// Reactive data
const visible = ref(props.modelValue);
const facturaData = ref(null);
const loading = ref(false);

// Watchers
watch(() => props.modelValue, (newValue) => {
    visible.value = newValue;
    if (newValue && props.facturaId) {
        fetchFacturaData();
    }
});

watch(visible, (newValue) => {
    emit('update:modelValue', newValue);
});

// Methods
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

const onConfirm = async () => {
    if (!props.facturaId) return;

    try {
        loading.value = true;

        const response = await axios.patch(`/invoices/${props.facturaId}/activacion`);

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data?.message || 'Factura puesta en standby correctamente',
            life: 5000
        });

        emit('confirmed', response.data);
        visible.value = false;

    } catch (error) {
        console.error('Error al poner factura en standby:', error);

        const errorMessage = error.response?.data?.message || 'Error al poner la factura en standby';

        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage,
            life: 5000
        });
    } finally {
        loading.value = false;
    }
};

const onCancel = () => {
    emit('cancelled');
    visible.value = false;
};

const onHide = () => {
    if (!loading.value) {
        onCancel();
    }
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

const getStatusSeverity = (status) => {
    switch (status) {
        case 'inactive': return 'secondary';
        case 'active': return 'success';
        case 'expired': return 'danger';
        case 'judicialized': return 'warn';
        case 'reprogramed': return 'info';
        case 'paid': return 'contrast';
        case 'canceled': return 'danger';
        case 'daStandby': return 'warn';
        default: return 'secondary';
    }
};
</script>