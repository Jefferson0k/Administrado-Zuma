<template>
    <Dialog v-model:visible="visible" modal :header="'Eliminar Factura'" :style="{ width: '32rem' }" :closable="false" >
        <div class="mb-4 text-center">
            <i class="pi pi-exclamation-triangle text-orange-500 text-3xl mb-3"></i>
            <p class="text-lg font-medium mb-2">¿Está seguro de que desea eliminar esta factura?</p>
        </div>

        <div v-if="facturaData" class="mb-4">
            <div class="grid grid-cols-1 gap-2 text-sm">
                <div>
                    <span class="font-medium">Código:</span>
                    <span class="ml-2">{{ facturaData.codigo || 'N/A' }}</span>
                </div>
                <div>
                    <span class="font-medium">Razón Social:</span>
                    <span class="ml-2">{{ facturaData.razonSocial || 'N/A' }}</span>
                </div>
                <div v-if="facturaData.moneda && facturaData.montoFactura">
                    <span class="font-medium">Monto:</span>
                    <span class="ml-2">{{ formatCurrency(facturaData.montoFactura, facturaData.moneda) }}</span>
                </div>
            </div>
        </div>

        <div class="text-center">
            <p class="text-red-600 text-sm font-medium">
                Esta acción no se puede deshacer.
            </p>
        </div>

        <template #footer>
            <Button label="No" text icon="pi pi-times" @click="handleCancel" severity="secondary"
                :disabled="loading" />
            <Button label="Eliminar" @click="handleDelete" severity="danger" :loading="loading" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    facturaId: {
        type: [String, Number],
        default: null
    },
    facturaData: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['update:modelValue', 'confirmed', 'cancelled']);
const toast = useToast();

const loading = ref(false);

const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

const handleCancel = () => {
    if (!loading.value) {
        visible.value = false;
        emit('cancelled');
    }
};

const handleDelete = async () => {
    if (!props.facturaId) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo identificar la factura a eliminar',
            life: 3000
        });
        return;
    }

    loading.value = true;
    try {
        const response = await axios.delete(`/invoices/${props.facturaId}`);

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Factura eliminada correctamente',
            life: 3000
        });

        visible.value = false;
        emit('confirmed', response.data);

    } catch (error) {
        console.error('Error al eliminar factura:', error);

        const errorMessage = error.response?.data?.message ||
            error.response?.data?.error ||
            'Error al eliminar la factura';

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

// Reset loading cuando se cierra el dialog
watch(visible, (newValue) => {
    if (!newValue) {
        loading.value = false;
    }
});

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