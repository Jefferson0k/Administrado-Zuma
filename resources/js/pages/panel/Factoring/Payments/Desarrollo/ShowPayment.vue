<template>
    <Toast />

    <div class="show-payment-container">
        <div v-if="loading" class="flex justify-center items-center p-6">
            <ProgressSpinner />
        </div>

        <div v-else-if="invoice" class="space-y-4">
            <!-- Información básica -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Información General -->
                <div class="border rounded-lg p-4">
                    <h5 class="font-bold text-lg mb-3 flex items-center gap-2">
                        <i class="pi pi-file-edit"></i>
                        Información General
                    </h5>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between border-b pb-2">
                            <span class="font-semibold">Código:</span>
                            <span>{{ invoice.codigo }}</span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="font-semibold">Razón Social:</span>
                            <span>{{ invoice.razonSocial }}</span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="font-semibold">RUC:</span>
                            <span>{{ invoice.ruc_cliente }}</span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="font-semibold">Estado:</span>
                            <Tag :value="getStatusLabel(invoice.estado)" :severity="getStatusSeverity(invoice.estado)" />
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="font-semibold">Moneda:</span>
                            <span>{{ invoice.moneda }}</span>
                        </div>
                    </div>
                </div>

                <!-- Montos -->
                <div class="border rounded-lg p-4">
                    <h5 class="font-bold text-lg mb-3 flex items-center gap-2">
                        <i class="pi pi-dollar"></i>
                        Montos
                    </h5>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between border-b pb-2">
                            <span class="font-semibold">Monto Factura:</span>
                            <span class="text-lg font-bold text-green-600">{{ invoice.moneda }} {{ invoice.montoFactura }}</span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="font-semibold">Monto Asumido Zuma:</span>
                            <span class="text-lg font-bold text-blue-600">{{ invoice.moneda }} {{ invoice.montoAsumidoZuma }}</span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="font-semibold">Monto Disponible:</span>
                            <span class="text-lg font-bold text-orange-600">{{ invoice.moneda }} {{ invoice.montoDisponible }}</span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="font-semibold">Tasa:</span>
                            <span class="text-lg font-bold">{{ invoice.tasa }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Pagos -->
            <div v-if="invoice.pagos && invoice.pagos.length > 0" class="border rounded-lg p-4 bg-white shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h5 class="font-bold text-xl flex items-center gap-2">
                        <i class="pi pi-credit-card text-blue-600"></i>
                        Gestión de Pagos
                    </h5>
                    <Tag :value="`${invoice.pagos.length} Pago(s)`" severity="info" />
                </div>
                
                <div class="space-y-4">
                    <div v-for="pago in invoice.pagos" :key="pago.id" class="border rounded-lg p-4 hover:shadow-md transition-shadow bg-gray-50">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <!-- Columna 1: Información del Pago -->
                            <div class="space-y-2">
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="pi pi-hashtag text-gray-500"></i>
                                    <span class="font-bold text-lg">Pago #{{ pago.id }}</span>
                                    <Tag :value="getTipoPagoLabel(pago.pay_type)" 
                                         :severity="getTipoPagoSeverity(pago.pay_type)" />
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-dollar text-green-600"></i>
                                    <div>
                                        <span class="text-xs text-gray-500 block">Monto</span>
                                        <span class="font-bold text-lg text-green-600">
                                            {{ pago.amount_to_be_paid_money.currency }} {{ formatAmount(pago.amount_to_be_paid) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <i class="pi pi-calendar text-blue-600"></i>
                                    <div>
                                        <span class="text-xs text-gray-500 block">Fecha de Pago</span>
                                        <span class="font-semibold">{{ pago.pay_date }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Columna 2: Recaudación (5%) -->
                            <div class="bg-orange-50 rounded-lg p-3 border border-orange-200">
                                <div class="text-center">
                                    <i class="pi pi-percentage text-orange-600 text-xl mb-2"></i>
                                    <label class="text-xs font-semibold text-gray-700 block mb-1">Recaudación (5%)</label>
                                    <p class="text-xl font-bold text-orange-700">
                                        {{ pago.amount_to_be_paid_money.currency }} {{ calculateRecaudacion(pago.amount_to_be_paid) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Columna 3: Monto Efectivizado -->
                            <div class="bg-purple-50 rounded-lg p-3 border border-purple-200">
                                <div class="text-center">
                                    <i class="pi pi-money-bill text-purple-600 text-xl mb-2"></i>
                                    <label class="text-xs font-semibold text-gray-700 block mb-1">Monto Efectivizado</label>
                                    <p class="text-xl font-bold text-purple-700">
                                        {{ pago.amount_to_be_paid_money.currency }} {{ calculateMontoEfectivizado(pago.amount_to_be_paid) }}
                                    </p>
                                    <span class="text-xs text-gray-600">A pagar</span>
                                </div>
                            </div>

                            <!-- Columna 4 y 5: Aprobaciones y Acciones -->
                            <div class="space-y-3 md:col-span-2">
                                <div class="bg-white rounded p-3 border">
                                    <div class="space-y-2">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-semibold">Aprobación 1:</span>
                                            <Tag :value="getApprovalLabel(pago.approval1_status)" 
                                                 :severity="getApprovalSeverity(pago.approval1_status)" />
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-semibold">Aprobación 2:</span>
                                            <Tag :value="getApprovalLabel(pago.approval2_status)" 
                                                 :severity="getApprovalSeverity(pago.approval2_status)" />
                                        </div>
                                    </div>
                                </div>

                                <Button 
                                    label="Aprobar Pago" 
                                    icon="pi pi-check-circle" 
                                    severity="success"
                                    size="small"
                                    class="w-full"
                                    @click="aprobarPago(pago)"
                                    :disabled="pago.approval1_status === 'approved' && pago.approval2_status === 'approved'"
                                />

                                <div v-if="pago.approval1_status === 'approved' && pago.approval2_status === 'approved'" 
                                     class="text-center">
                                    <i class="pi pi-check-circle text-green-600 text-xl"></i>
                                    <p class="text-xs text-green-600 font-semibold">Completamente Aprobado</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Inversiones -->
            <div v-if="invoice.investments && invoice.investments.length > 0" class="border rounded-lg p-4 bg-white shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h5 class="font-bold text-xl flex items-center gap-2">
                        <i class="pi pi-wallet text-green-600"></i>
                        Inversiones Realizadas
                    </h5>
                    <Tag :value="`${invoice.investments.length} Inversor(es)`" severity="success" />
                </div>
                
                <DataTable 
                    :value="invoice.investments" 
                    :paginator="true" 
                    :rows="10"
                    :rowsPerPageOptions="[5, 10, 20]"
                    responsiveLayout="scroll"
                    class="p-datatable-sm"
                >
                    <Column field="id" header="#" style="width: 5rem">
                        <template #body="slotProps">
                            {{ slotProps.index + 1 }}
                        </template>
                    </Column>

                    <Column field="inversionista" header="Inversionista" sortable style="min-width: 15rem" />

                    <Column field="document" header="Documento" sortable style="min-width: 10rem" />

                    <Column field="correo" header="Correo" sortable style="min-width: 15rem" />

                    <Column field="telephone" header="Teléfono" sortable style="min-width: 10rem" />

                    <Column field="amount" header="Monto" sortable style="min-width: 10rem">
                        <template #body="slotProps">
                            <span class="font-bold text-green-600">
                                {{ slotProps.data.currency }} {{ slotProps.data.amount }}
                            </span>
                        </template>
                    </Column>

                    <Column field="return" header="Retorno" sortable style="min-width: 10rem">
                        <template #body="slotProps">
                            <span class="font-semibold text-blue-600">
                                {{ slotProps.data.currency }} {{ slotProps.data.return }}
                            </span>
                        </template>
                    </Column>

                    <!-- Nueva Columna: Recaudación (5%) -->
                    <Column header="Recaudación (5%)" sortable style="min-width: 11rem">
                        <template #body="slotProps">
                            <div class="text-center">
                                <i class="pi pi-percentage text-orange-600 text-sm mr-1"></i>
                                <span class="font-bold text-orange-700">
                                    {{ slotProps.data.currency }} {{ calculateRecaudacionInversion(slotProps.data.return) }}
                                </span>
                            </div>
                        </template>
                    </Column>

                    <!-- Nueva Columna: Monto Efectivizado -->
                    <Column header="Monto Efectivizado" sortable style="min-width: 12rem">
                        <template #body="slotProps">
                            <div class="text-center">
                                <i class="pi pi-money-bill text-purple-600 text-sm mr-1"></i>
                                <span class="font-bold text-purple-700">
                                    {{ slotProps.data.currency }} {{ calculateMontoEfectivizadoInversion(slotProps.data.return) }}
                                </span>
                                <div class="text-xs text-gray-600 mt-1">Neto a recibir</div>
                            </div>
                        </template>
                    </Column>

                    <Column field="rate" header="Tasa" sortable style="min-width: 8rem">
                        <template #body="slotProps">
                            {{ slotProps.data.rate }}%
                        </template>
                    </Column>
                    <Column field="due_date" header="Fecha Vencimiento" sortable style="min-width: 12rem" />

                    <template #empty>
                        <div class="text-center p-4 text-gray-500">
                            <i class="pi pi-inbox text-4xl mb-2"></i>
                            <p>No hay inversiones registradas</p>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <div v-else class="text-center p-6">
            <Message severity="warn">No se encontraron detalles de la factura</Message>
        </div>

        <!-- Dialog de confirmación -->
        <Dialog v-model:visible="dialogConfirmar" modal header="Confirmar Pago" :style="{ width: '400px' }">
            <div class="flex items-center gap-3 mb-4">
                <i class="pi pi-exclamation-triangle text-4xl text-orange-500"></i>
                <div>
                    <p class="font-semibold mb-2">¿Está seguro de confirmar el pago?</p>
                    <p class="text-sm text-gray-600">Esta acción actualizará el estado de la factura.</p>
                </div>
            </div>
            <div class="bg-blue-50 p-3 rounded text-sm">
                <div><strong>Factura:</strong> {{ invoice?.codigo }}</div>
                <div><strong>Monto:</strong> {{ invoice?.moneda }} {{ invoice?.montoFactura }}</div>
            </div>
            <template #footer>
                <Button label="Cancelar" icon="pi pi-times" text @click="dialogConfirmar = false" />
                <Button label="Sí, Confirmar" icon="pi pi-check" severity="success" @click="confirmarPago" :loading="confirmando" />
            </template>
        </Dialog>

        <!-- Dialog de Aprobación -->
        <Dialog v-model:visible="dialogAprobar" modal header="Aprobar Pago" :style="{ width: '500px' }">
            <div v-if="pagoSeleccionado" class="space-y-4">
                <div>
                    <label class="block font-semibold mb-2">Comentario de aprobación (opcional):</label>
                    <textarea 
                        v-model="comentarioAprobacion"
                        class="w-full border rounded p-2"
                        rows="3"
                        placeholder="Ingrese un comentario..."
                    ></textarea>
                </div>

                <div class="bg-yellow-50 p-3 rounded text-sm">
                    <i class="pi pi-info-circle text-yellow-600"></i>
                    <span class="ml-2">Esta acción aprobará el pago y actualizará su estado.</span>
                </div>
            </div>
            <template #footer>
                <Button label="Cancelar" icon="pi pi-times" text @click="dialogAprobar = false" />
                <Button label="Aprobar" icon="pi pi-check" severity="success" @click="confirmarAprobacion" :loading="aprobando" />
            </template>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';
import Message from 'primevue/message';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

interface Props {
    invoiceId: string;
}

const props = defineProps<Props>();
const emit = defineEmits(['pago-confirmado']);

const invoice = ref<any>(null);
const loading = ref(false);
const dialogConfirmar = ref(false);
const confirmando = ref(false);
const dialogAprobar = ref(false);
const pagoSeleccionado = ref<any>(null);
const comentarioAprobacion = ref('');
const aprobando = ref(false);

const loadInvoiceDetails = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/invoices/${props.invoiceId}`);
        invoice.value = response.data.data || response.data;
    } catch (error) {
        console.error('Error al cargar detalles:', error);
    } finally {
        loading.value = false;
    }
};

const confirmarPago = async () => {
    confirmando.value = true;
    try {
        await axios.post(`/invoices/${props.invoiceId}/confirm-payment`);
        alert('Pago confirmado exitosamente');
        dialogConfirmar.value = false;
        await loadInvoiceDetails();
        emit('pago-confirmado');
    } catch (error) {
        console.error('Error al confirmar pago:', error);
        alert('Error al confirmar el pago');
    } finally {
        confirmando.value = false;
    }
};

const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        'inactive': 'Inactivo', 'active': 'Activo', 'expired': 'Expirado',
        'judicialized': 'Judicializado', 'reprogramed': 'Reprogramado',
        'paid': 'Pagado', 'canceled': 'Cancelado', 'daStandby': 'En Espera'
    };
    return labels[status] || status;
};

const getStatusSeverity = (status: string) => {
    const severities: Record<string, string> = {
        'active': 'success', 'paid': 'success', 'inactive': 'secondary',
        'daStandby': 'warn', 'reprogramed': 'warn', 'expired': 'danger',
        'judicialized': 'danger', 'canceled': 'danger'
    };
    return severities[status] || 'info';
};

const getInvestmentStatusSeverity = (status: string) => {
    const severities: Record<string, string> = {
        'paid': 'success',
        'active': 'info',
        'pending': 'warn',
        'canceled': 'danger'
    };
    return severities[status] || 'info';
};

const getApprovalLabel = (status: string) => {
    const labels: Record<string, string> = {
        'pending': 'Pendiente',
        'approved': 'Aprobado',
        'rejected': 'Rechazado'
    };
    return labels[status] || status;
};

const getApprovalSeverity = (status: string) => {
    const severities: Record<string, string> = {
        'pending': 'warn',
        'approved': 'success',
        'rejected': 'danger'
    };
    return severities[status] || 'info';
};

const aprobarPago = (pago: any) => {
    pagoSeleccionado.value = pago;
    comentarioAprobacion.value = '';
    dialogAprobar.value = true;
};

const confirmarAprobacion = async () => {
    aprobando.value = true;
    try {
        let url = '';

        if (pagoSeleccionado.value.approval1_status !== 'approved') {
            url = `/payments/${pagoSeleccionado.value.id}/approve`;
        } else if (pagoSeleccionado.value.approval2_status !== 'approved') {
            url = `/payments/${pagoSeleccionado.value.id}/approve-level-2`;
        }

        await axios.put(url, {
            comment: comentarioAprobacion.value
        });

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Pago aprobado exitosamente',
            life: 3000
        });

        dialogAprobar.value = false;
        await loadInvoiceDetails();
    } catch (error) {
        console.error('Error al aprobar pago:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo aprobar el pago',
            life: 4000
        });
    } finally {
        aprobando.value = false;
    }
};

const formatAmount = (amount: number) => {
    return (amount / 100).toFixed(2);
};

const calculateRecaudacion = (amount: number) => {
    const amountDivided = amount / 100;
    return (amountDivided * 0.05).toFixed(2);
};

const calculateMontoEfectivizado = (amount: number) => {
    const amountDivided = amount / 100;
    const recaudacion = amountDivided * 0.05;
    return (amountDivided - recaudacion).toFixed(2);
};

// Nuevas funciones para inversiones
const calculateRecaudacionInversion = (returnAmount: number) => {
    return (returnAmount * 0.05).toFixed(2);
};

const calculateMontoEfectivizadoInversion = (returnAmount: number) => {
    const recaudacion = returnAmount * 0.05;
    return (returnAmount - recaudacion).toFixed(2);
};

const getTipoPagoLabel = (payType: string) => {
    const labels: Record<string, string> = {
        'intereses': 'Intereses',
        'partial': 'Parcial',
        'total': 'Total'
    };
    return labels[payType] || payType;
};

const getTipoPagoSeverity = (payType: string) => {
    const severities: Record<string, string> = {
        'intereses': 'info',
        'partial': 'warn',
        'total': 'success'
    };
    return severities[payType] || 'info';
};

onMounted(() => {
    loadInvoiceDetails();
});
</script>