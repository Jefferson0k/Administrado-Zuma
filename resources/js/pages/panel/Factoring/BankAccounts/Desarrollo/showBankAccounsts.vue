<template>

    <Head title="Detalle de la cuenta bancaria" />
    <AppLayout>
        <div>
            <template v-if="isLoading">
                <Espera />
            </template>
            <template v-else>
                <div class="card">
                    <!-- Header de la cuenta bancaria -->
                    <div class="surface-section px-4 py-8 md:px-6 lg:px-8">
                        <div class="text-700 text-center">
                            <div class="text-blue-600 font-bold mb-3">
                                <i class="pi pi-building mr-2"></i>VALIDACIÓN DE CUENTA BANCARIA
                            </div>
                            <div class="text-900 font-bold text-5xl mb-3">Verificación</div>
                            <div class="text-700 text-2xl mb-5">Revise los depósitos realizados desde esta cuenta</div>
                        </div>
                    </div>

                    <!-- Información de la cuenta -->
                    <div class="surface-card p-4 shadow-2 border-round mb-4">
                        <div class="grid">
                            <div class="col-12 md:col-6">
                                <h3 class="text-900 font-medium text-xl mb-3">
                                    <i class="pi pi-check-circle mr-2 text-green-500"></i>
                                    Estado de Validación
                                </h3>
                                <div class="surface-50 p-3 border-round">
                                    <div class="text-center">
                                        <Tag value="Pendiente de Aprobación" severity="warn"
                                            class="text-lg px-3 py-2" />
                                        <div class="text-600 text-sm mt-2">
                                            Revise los depósitos para validar la propiedad
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-content-between align-items-center mt-4 pt-4 border-top-1 surface-border">
                        <!-- Botón volver alineado a la izquierda -->
                        <Button 
                            label="Volver al Listado" 
                            icon="pi pi-arrow-left" 
                            severity="contrast" 
                            class="mr-2"
                            @click="goBack" 
                        />

                        <!-- Botones de acción alineados a la derecha -->
                        <div class="flex gap-2">
                            <Button 
                                label="Aprobar Cuenta" 
                                icon="pi pi-check" 
                                class="p-button-success mr-2"
                                @click="confirmApproval" 
                                :disabled="deposits.length === 0" 
                            />
                            <Button 
                                label="Rechazar Cuenta" 
                                icon="pi pi-times" 
                                class="p-button-danger p-button-outlined"
                                @click="confirmRejection" 
                            />
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <br>
        <div class="card">
            <DataTable :value="deposits" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 20]"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} depósitos"
                responsiveLayout="scroll" class="p-datatable-sm">
                <template #header>
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">
                            <i class="pi pi-money-bill mr-2"></i>
                            Historial de Depósitos
                        </h4>
                    </div>
                </template>

                <!-- Columnas -->
                <Column field="nro_operation" header="Nº Operación" sortable style="min-width: 12rem">
                    <template #body="{ data }">
                        <span class="font-mono text-sm">{{ data.nro_operation }}</span>
                    </template>
                </Column>

                <Column field="currency" header="Moneda" sortable style="min-width: 8rem">
                    <template #body="{ data }">
                        <Tag :value="data.currency" :severity="data.currency === 'USD' ? 'success' : 'info'" />
                    </template>
                </Column>

                <Column field="amount" header="Monto" sortable style="min-width: 10rem">
                    <template #body="{ data }">
                        <span class="font-semibold text-green-600">
                            {{ data.currency }} {{ formatAmount(data.amount) }}
                        </span>
                    </template>
                </Column>

                <Column field="investor.name" header="Inversionista" sortable style="min-width: 15rem">
                    <template #body="{ data }">
                        <div class="flex align-items-center gap-2">
                            <Avatar :label="data.investor?.name?.charAt(0)" class="mr-2" size="small"
                                style="background-color: #dee2e6; color: #495057" />
                            <span>{{ data.investor?.name || 'Sin nombre' }}</span>
                        </div>
                    </template>
                </Column>

                <Column field="movement.status" header="Estado Movimiento" sortable style="min-width: 12rem">
                    <template #body="{ data }">
                        <Tag :value="getStatusLabel(data.movement?.status)"
                            :severity="getStatusSeverity(data.movement?.status)" />
                    </template>
                </Column>

                <Column field="movement.confirm" header="Confirmación" sortable style="min-width: 10rem">
                    <template #body="{ data }">
                        <Tag :value="getConfirmLabel(data.movement?.confirm)"
                            :severity="getConfirmSeverity(data.movement?.confirm)" />
                    </template>
                </Column>

                <Column header="Acciones" style="min-width: 8rem">
                    <template #body="{ data }">
                        <Button v-if="data.resource" icon="pi pi-download"
                            class="p-button-rounded p-button-text p-button-sm" @click="downloadResource(data.resource)"
                            v-tooltip="'Descargar comprobante'" />
                    </template>
                </Column>
            </DataTable>

        </div>

        <!-- Dialog de confirmación -->
        <ConfirmDialog />
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import AppLayout from '@/layout/AppLayout.vue';
import Espera from '@/components/Espera.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import ConfirmDialog from 'primevue/confirmdialog';

// Props del backend
interface Props {
    bank_account_id: string;
    total: number;
    deposits: Array<{
        deposit_id: number;
        nro_operation: string;
        currency: string;
        amount: number;
        resource: string | null;
        investor: {
            id: number;
            name: string;
        } | null;
        movement: {
            id: number;
            type: string;
            status: string;
            confirm: string;
            amount: number;
            formatted: string;
        } | null;
    }>;
}

const props = defineProps<Props>();
const toast = useToast();
const confirm = useConfirm();
const isLoading = ref(true);

onMounted(() => {
    setTimeout(() => {
        isLoading.value = false;
    }, 1000);
});

// Funciones de formateo
const formatAmount = (amount: number): string => {
    return new Intl.NumberFormat('es-PE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
};

const getStatusLabel = (status: string): string => {
    const labels: Record<string, string> = {
        'pending': 'Pendiente',
        'approved': 'Aprobado',
        'rejected': 'Rechazado',
        'processed': 'Procesado'
    };
    return labels[status] || status;
};

const getStatusSeverity = (status: string): string => {
    const severities: Record<string, string> = {
        'pending': 'warn',
        'approved': 'success',
        'rejected': 'danger',
        'processed': 'info'
    };
    return severities[status] || 'secondary';
};

const getConfirmLabel = (confirm: string): string => {
    const labels: Record<string, string> = {
        'confirmed': 'Confirmado',
        'pending': 'Pendiente',
        'rejected': 'Rechazado'
    };
    return labels[confirm] || confirm;
};

const getConfirmSeverity = (confirm: string): string => {
    const severities: Record<string, string> = {
        'confirmed': 'success',
        'pending': 'warn',
        'rejected': 'danger'
    };
    return severities[confirm] || 'secondary';
};

// Acciones
const confirmApproval = () => {
    confirm.require({
        message: '¿Está seguro que desea aprobar esta cuenta bancaria? Una vez aprobada, el inversionista podrá utilizarla para sus operaciones.',
        header: 'Confirmar Aprobación de Cuenta',
        icon: 'pi pi-check-circle',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Aprobar Cuenta',
        acceptClass: 'p-button-success',
        accept: () => {
            approveAccount();
        },
        reject: () => {
            toast.add({
                severity: 'info',
                summary: 'Cancelado',
                detail: 'Aprobación cancelada',
                life: 3000
            });
        }
    });
};

const confirmRejection = () => {
    confirm.require({
        message: '¿Está seguro que desea rechazar esta cuenta bancaria? El inversionista deberá registrar una nueva cuenta.',
        header: 'Confirmar Rechazo de Cuenta',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Rechazar Cuenta',
        acceptClass: 'p-button-danger',
        accept: () => {
            rejectAccount();
        }
    });
};

const approveAccount = async () => {
    try {
        await router.post(`/ban/${props.bank_account_id}/validate`, {}, {
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Cuenta Aprobada',
                    detail: 'La cuenta bancaria ha sido aprobada exitosamente',
                    life: 4000
                });
                // Redirigir al listado después de 2 segundos
                setTimeout(() => {
                    router.visit('/ban');
                }, 2000);
            },
            onError: (errors) => {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Error al aprobar la cuenta bancaria',
                    life: 5000
                });
            }
        });
    } catch (error) {
        console.error('Error:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error inesperado al aprobar la cuenta',
            life: 5000
        });
    }
};

const rejectAccount = async () => {
    try {
        await router.post(`/ban/${props.bank_account_id}/reject`, {}, {
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Cuenta Rechazada',
                    detail: 'La cuenta bancaria ha sido rechazada',
                    life: 4000
                });
                // Redirigir al listado después de 2 segundos
                setTimeout(() => {
                    router.visit('/ban');
                }, 2000);
            },
            onError: (errors) => {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Error al rechazar la cuenta bancaria',
                    life: 5000
                });
            }
        });
    } catch (error) {
        console.error('Error:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error inesperado al rechazar la cuenta',
            life: 5000
        });
    }
};

const downloadResource = (resourcePath: string) => {
    if (resourcePath) {
        window.open(resourcePath, '_blank');
    }
};

const goBack = () => {
    router.visit('/factoring/cuentas-bancarias');
};
</script>