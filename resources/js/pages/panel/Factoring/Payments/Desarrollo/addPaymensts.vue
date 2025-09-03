<template>
    <Dialog :visible="visible" :style="{ width: '600px' }" header="Procesar Pago" :modal="true" :closable="true"
        @update:visible="$emit('update:visible', $event)">
        <!-- Información del pago -->
        <div class="grid grid-cols-1 gap-4">
            <div class="rounded-lg">
                <!-- Cliente/Proveedor -->
                <div class="mb-3">
                    <label class="block text-sm font-medium  mb-1">
                        Cliente/Proveedor
                    </label>
                    <div class="p-2">
                        <span class="font-mono text-sm"><b>{{ paymentData.document || 'No especificado' }}</b></span>
                    </div>
                </div>

                <!-- Aceptante -->
                <div class="mb-3">
                    <label class="block text-sm font-medium  mb-1">
                        Aceptante
                    </label>
                    <div class="p-2">
                        <span class="font-mono text-sm"><b>{{ paymentData.RUC_client || 'No especificado' }}</b></span>
                    </div>
                </div>

                <!-- Monto -->
                <div class="mb-3">
                    <label class="block text-sm font-medium  mb-1">
                        Monto
                    </label>
                    <div class="p-2">
                        <span class="font-mono text-lg font-semibold text-green-600">
                            <b>{{ formatCurrency(paymentData.amount, paymentData.currency) }}</b>
                        </span>
                    </div>
                </div>

                <!-- Tipo de Pago -->
                <div class="mb-3">
                    <label class="block text-sm font-medium  mb-1">
                        Tipo de Pago
                    </label>
                    <div class="p-2">
                        <Tag :value="paymentData.tipo_pago || 'Transferencia'" severity="info"
                            icon="pi pi-credit-card" />
                    </div>
                </div>

                <!-- Fecha de Pago -->
                <div class="mb-3">
                    <label class="block text-sm font-medium  mb-1">
                        Fecha de Pago Estimada
                    </label>
                    <div class="p-2">
                        <span class="font-mono text-sm">
                            <b>{{ paymentData.estimated_pay_date }}</b>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Estado actual -->
            <Message severity="success" icon="pi pi-check" class="w-full flex items-center justify-center text-base">
                {{ paymentData.estado }}
            </Message>
        </div>
        <br>
        <!-- Confirmación -->
        <Message severity="info" icon="pi pi-info-circle" class="w-full">
            <div class="flex flex-col">
                <p class="text-sm font-medium mb-1">
                    Confirmación de Pago
                </p>
                <p class="text-sm">
                    ¿Estás seguro que deseas procesar este pago por
                    <b>{{ formatCurrency(paymentData.amount, paymentData.currency) }}</b>?
                </p>
            </div>
        </Message>
        <template #footer>
            <div class="flex justify-end gap-3">
                <Button label="Cancelar" icon="pi pi-times" severity="secondary" text  @click="onCancel"
                    :disabled="processing" />
                <Button label="Realizar Pago" icon="pi pi-check" severity="contrast" @click="onConfirmPayment"
                    :loading="processing" />
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import { useToast } from 'primevue/usetoast';

// Props
const props = defineProps({
    visible: {
        type: Boolean,
        default: false
    },
    paymentData: {
        type: Object,
        default: () => ({})
    }
});

// Emits
const emit = defineEmits(['update:visible', 'payment-processed', 'cancelled']);

const toast = useToast();
const processing = ref(false);

// Función para formatear moneda
function formatCurrency(amount = 0, currency = 'PEN') {
    const numAmount = Number(amount) || 0;
    const symbol = currency === 'PEN' ? 'S/' : '$';
    return `${symbol} ${numAmount.toLocaleString('es-PE', { minimumFractionDigits: 2 })}`;
}

// Confirmar pago
async function onConfirmPayment() {
    processing.value = true;

    try {
        // Simular procesamiento (aquí irías a tu API)
        await new Promise(resolve => setTimeout(resolve, 1500));

        toast.add({
            severity: 'success',
            summary: 'Pago Procesado',
            detail: `Pago realizado exitosamente por ${formatCurrency(props.paymentData.amount, props.paymentData.currency)}`,
            life: 4000,
        });

        emit('payment-processed', props.paymentData);
        emit('update:visible', false);

    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo procesar el pago. Intenta nuevamente.',
            life: 4000,
        });
    } finally {
        processing.value = false;
    }
}

// Cancelar
function onCancel() {
    emit('cancelled');
    emit('update:visible', false);
}
</script>