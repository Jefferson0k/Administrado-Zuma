<template>
    <Dialog v-model:visible="visible" modal :header="'Eliminar Factura'" :style="{ width: '32rem' }" :closable="false">
        <div class="flex items-center gap-4 mb-4">
            <i class="pi pi-exclamation-triangle text-red-500 text-3xl"></i>
            <div>
                <p class="font-semibold mb-2">¿Está seguro de que desea eliminar esta factura?</p>
                <p class="text-sm text-gray-600 mb-2">
                    <strong>Código:</strong> {{ facturaData?.codigo || 'N/A' }}
                </p>
                <p class="text-sm text-gray-600">
                    <strong>Razón Social:</strong> {{ facturaData?.razonSocial || 'N/A' }}
                </p>
                <p class="text-red-600 text-sm mt-2">
                    Esta acción no se puede deshacer.
                </p>
            </div>
        </div>

        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" @click="handleCancel" severity="secondary"
                :disabled="loading" />
            <Button label="Eliminar" icon="pi pi-trash" @click="handleDelete" severity="danger" :loading="loading" />
        </template>
    </Dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';

interface FacturaData {
    id: string;
    codigo: string;
    razonSocial: string;
    moneda: string;
    montoFactura: string;
    estado: string;
}

interface Props {
    modelValue: boolean;
    facturaId: string | null;
    facturaData?: FacturaData | null;
}

interface Emits {
    (e: 'update:modelValue', value: boolean): void;
    (e: 'confirmed', response: any): void;
    (e: 'cancelled'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
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

    } catch (error: any) {
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
</script>