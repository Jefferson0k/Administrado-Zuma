<template>
    <!-- Dialog principal -->
    <Dialog v-model:visible="dialogVisible" modal header="Detalle del Depósito" :style="{ width: '63rem' }"
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }" @hide="handleClose">
        
        <div class="p-6">
            <!-- Header con información principal -->
            <div class="flex justify-between items-start mb-8">
                <div>
                    <h2 class="text-2xl font-bold mb-2">{{ deposit.investor }}</h2>
                    <div class="flex items-center gap-4 text-sm">
                        <span class="flex items-center gap-1">
                            <i class="pi pi-building"></i>
                            {{ deposit.nomBanco }}
                        </span>
                        <span class="flex items-center gap-1">
                            <i class="pi pi-calendar"></i>
                            {{ deposit.creacion }}
                        </span>
                        <span class="flex items-center gap-1">
                            <i class="pi pi-hashtag"></i>
                            {{ deposit.nro_operation }}
                        </span>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-green-600 mb-1">{{ formatAmount(deposit.amount) }}</div>
                    <div class="text-sm text-gray-500 uppercase tracking-wide">{{ deposit.currency }}</div>
                </div>
            </div>

            <!-- Alerta de cuenta bancaria -->
            <div v-if="deposit.estado_bank_account !== 'valid'" class="mb-8">
                <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-l-4 border-amber-400 p-6 rounded-r-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="pi pi-exclamation-triangle text-amber-500 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-amber-900 mb-2">
                                Cuenta Bancaria Pendiente de Validación
                            </h3>
                            <p class="text-amber-700 mb-3">
                                La cuenta bancaria asociada requiere aprobación antes de proceder con las validaciones del depósito.
                            </p>
                            <div class="inline-flex items-center gap-2">
                                <span class="text-sm text-amber-600">Estado:</span>
                                <Tag :value="getBankAccountStatusText(deposit.estado_bank_account)"
                                    :severity="getBankAccountSeverity(deposit.estado_bank_account)" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido principal -->
            <div v-if="deposit.estado_bank_account === 'valid'" class="grid grid-cols-1 xl:grid-cols-5 gap-8">
                
                <!-- Voucher del depósito -->
                <div class="xl:col-span-3">
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold text-gray-900 flex items-center gap-2">
                                <i class="pi pi-image text-blue-600"></i>
                                Voucher de Depósito
                            </h3>
                            <Button v-if="deposit.foto" icon="pi pi-external-link" severity="secondary" text
                                size="small" @click="openImagePreview" label="Ver en tamaño completo" />
                        </div>
                        
                        <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                            <div v-if="deposit.foto" class="flex justify-center">
                                <div class="relative group cursor-pointer" @click="openImagePreview">
                                    <Image :src="deposit.foto" alt="Voucher del depósito" preview
                                        class="rounded-lg shadow-lg max-w-full h-auto object-contain"
                                        style="max-height: 500px; min-height: 300px;" />
                                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition-all duration-200 rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100">
                                        <i class="pi pi-search-plus text-white text-2xl"></i>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center py-20 text-gray-400">
                                <i class="pi pi-image text-6xl mb-4"></i>
                                <p class="text-lg font-medium">No hay voucher disponible</p>
                                <p class="text-sm">El comprobante no ha sido subido</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel lateral con detalles -->
                <div class="xl:col-span-2 space-y-6">
                    
                    <!-- Información de la cuenta -->
                    <div class="bg-white border border-gray-200 rounded-xl p-6">
                        <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="pi pi-credit-card text-indigo-600"></i>
                            Información de Cuenta
                        </h4>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Tipo de cuenta</span>
                                <span class="font-medium capitalize">{{ deposit.type }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Número de cuenta</span>
                                <span class="font-mono text-sm">{{ deposit.cc }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">CCI</span>
                                <span class="font-mono text-xs text-gray-500">{{ deposit.cci }}</span>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t">
                                <span class="text-sm text-gray-600">Estado cuenta</span>
                                <Tag :value="getBankAccountStatusText(deposit.estado_bank_account)"
                                    :severity="getBankAccountSeverity(deposit.estado_bank_account)" />
                            </div>
                        </div>
                    </div>

                    <!-- Estados del proceso -->
                    <div class="bg-white border border-gray-200 rounded-xl p-6">
                        <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="pi pi-cog text-purple-600"></i>
                            Estado del Proceso
                        </h4>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full" :class="deposit.estado === 'valid' ? 'bg-green-500' : deposit.estado === 'pending' ? 'bg-yellow-500' : 'bg-red-500'"></div>
                                    <span class="text-sm font-medium">Primera Validación</span>
                                </div>
                                <Tag :value="translateEstado(deposit.estado)"
                                    :severity="getSeverity(deposit.estado)" 
                                    :icon="getIcon(deposit.estado)" />
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full" :class="deposit.estadoConfig === 'valid' ? 'bg-green-500' : deposit.estadoConfig === 'pending' ? 'bg-yellow-500' : 'bg-red-500'"></div>
                                    <span class="text-sm font-medium">Aprobación Final</span>
                                </div>
                                <Tag :value="translateEstado(deposit.estadoConfig)"
                                    :severity="getSeverity(deposit.estadoConfig)"
                                    :icon="getIcon(deposit.estadoConfig)" />
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Panel de validaciones -->
            <div v-if="deposit.estado_bank_account === 'valid'" class="mt-8">
                <h3 class="text-xl font-semibold text-gray-900 mb-6 flex items-center gap-2">
                    <i class="pi pi-check-circle text-green-600"></i>
                    Panel de Validaciones
                </h3>

                <div v-if="loading" class="flex justify-center items-center py-16">
                    <ProgressSpinner size="60" strokeWidth="4" />
                    <span class="ml-4 text-gray-600">Cargando información del depósito...</span>
                </div>

                <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                    <!-- Primera Validación -->
                    <div class="bg-gradient-to-br from-yellow-50 via-amber-50 to-orange-50 border border-yellow-200 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-1 flex items-center gap-2">
                                    <i class="pi pi-shield text-yellow-600"></i>
                                    Primera Validación
                                </h4>
                                <p class="text-sm text-gray-600">Validación inicial del movimiento</p>
                            </div>
                            <Tag :value="translateEstado(deposit.estado)"
                                :severity="getSeverity(deposit.estado)" 
                                :icon="getIcon(deposit.estado)"
                                class="text-sm" />
                        </div>

                        <!-- Información del movimiento -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                            <div class="text-xs text-blue-800 space-y-1">
                                <div><strong>N° Operación:</strong> <span class="font-mono">{{ deposit.nro_operation }}</span></div>
                                <div><strong>Estado:</strong> {{ translateEstado(deposit.estado) }}</div>
                            </div>
                        </div>

                        <!-- Estados y acciones -->
                        <div v-if="deposit.estado === 'pending'" class="space-y-4">
                            <div class="bg-white border border-yellow-200 rounded-lg p-4">
                                <div class="flex items-start gap-3 mb-4">
                                    <i class="pi pi-info-circle text-blue-500 mt-1"></i>
                                    <div>
                                        <p class="font-medium text-gray-900 mb-1">Validación Pendiente</p>
                                        <p class="text-sm text-gray-600">
                                            Este depósito requiere validación antes de proceder.
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <Button @click="validateMovement" :loading="validating" 
                                        icon="pi pi-check" label="Validar" 
                                        severity="success" class="flex-1" />
                                    <Button @click="openRejectMovementDialog" 
                                        icon="pi pi-times" label="Rechazar"
                                        severity="danger" outlined class="flex-1" />
                                </div>
                            </div>
                        </div>

                        <div v-else-if="deposit.estado === 'valid'" class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center text-green-700">
                                <i class="pi pi-check-circle text-xl mr-3"></i>
                                <div>
                                    <p class="font-medium">Movimiento Validado</p>
                                    <p class="text-sm">El movimiento ha sido validado correctamente</p>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="deposit.estado === 'rejected'" class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-center text-red-700">
                                <i class="pi pi-times-circle text-xl mr-3"></i>
                                <div>
                                    <p class="font-medium">Movimiento Rechazado</p>
                                    <p class="text-sm">El movimiento ha sido rechazado</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Segunda Validación -->
                    <div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 border border-blue-200 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-1 flex items-center gap-2">
                                    <i class="pi pi-verified text-blue-600"></i>
                                    Aprobación Final
                                </h4>
                                <p class="text-sm text-gray-600">Confirmación final del depósito</p>
                            </div>
                            <Tag :value="translateEstado(deposit.estadoConfig)"
                                :severity="getSeverity(deposit.estadoConfig)"
                                :icon="getIcon(deposit.estadoConfig)"
                                class="text-sm" />
                        </div>

                        <!-- Estados y acciones -->
                        <div v-if="deposit.estado !== 'valid'" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <i class="pi pi-lock text-yellow-600 mt-1"></i>
                                <div>
                                    <p class="font-medium text-yellow-800 mb-1">Esperando Validación</p>
                                    <p class="text-sm text-yellow-700">
                                        Complete la primera validación para continuar.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="deposit.estado === 'valid' && deposit.estadoConfig === 'pending'" class="space-y-4">
                            <div class="bg-white border border-blue-200 rounded-lg p-4">
                                <div class="flex items-start gap-3 mb-4">
                                    <i class="pi pi-info-circle text-blue-500 mt-1"></i>
                                    <div>
                                        <p class="font-medium text-gray-900 mb-1">Listo para Aprobación</p>
                                        <p class="text-sm text-gray-600">
                                            El movimiento está validado. Proceda con la aprobación final.
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <Button @click="approveDeposit" :loading="approving" 
                                        icon="pi pi-check" label="Aprobar"
                                        severity="success" class="flex-1" />
                                    <Button @click="openRejectConfirmDialog" 
                                        icon="pi pi-times" label="Rechazar"
                                        severity="danger" outlined class="flex-1" />
                                </div>
                            </div>
                        </div>

                        <div v-else-if="deposit.estadoConfig === 'valid'" class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center text-green-700">
                                <i class="pi pi-check-circle text-xl mr-3"></i>
                                <div>
                                    <p class="font-medium">Depósito Aprobado</p>
                                    <p class="text-sm">El depósito ha sido procesado exitosamente</p>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="deposit.estadoConfig === 'rejected'" class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-start gap-3 text-red-700">
                                <i class="pi pi-times-circle text-xl mt-1"></i>
                                <div>
                                    <p class="font-medium">Depósito Rechazado</p>
                                    <p class="text-sm">El depósito ha sido rechazado</p>
                                    <p v-if="deposit.description" class="text-sm mt-1">
                                        <strong>Motivo:</strong> {{ deposit.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Sección de Conclusión -->
                <div class="mt-8">
                    <div class="bg-white border border-gray-200 rounded-xl p-6">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="pi pi-file-edit text-indigo-600"></i>
                            Observaciones
                        </h4>
                        <div class="space-y-4">
                            <textarea
                                v-model="conclusion"
                                class="w-full p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none"
                                rows="4"
                                placeholder="Escriba aquí las observaciones o notas adicionales sobre este depósito..."
                            ></textarea>
                            <div class="text-sm text-gray-500">
                                <span v-if="conclusion">{{ conclusion.length }} caracteres</span>
                                <span v-else>Estas observaciones se enviarán junto con la acción de validar/aprobar/rechazar</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cerrar" icon="pi pi-times" text severity="secondary" @click="handleClose" />
            </div>
        </template>
    </Dialog>

    <!-- Dialog para rechazar movimiento (primera validación) -->
    <Dialog v-model:visible="showRejectMovementDialog" modal header="Rechazar Movimiento" :style="{ width: '500px' }">
        <div class="space-y-4">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <i class="pi pi-exclamation-triangle text-red-600 mt-1"></i>
                    <div>
                        <p class="font-medium text-red-900">¿Confirmar rechazo del movimiento?</p>
                        <p class="text-sm text-red-700 mt-1">Esta acción marcará el movimiento como rechazado.</p>
                    </div>
                </div>
            </div>
            
            <!-- Campo para observaciones en el rechazo -->
            <div v-if="conclusion.trim()" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h5 class="font-medium text-blue-900 mb-2">Observaciones a incluir:</h5>
                <p class="text-sm text-blue-800 italic">"{{ conclusion }}"</p>
            </div>
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cancelar" text icon="pi pi-times" severity="secondary" @click="cancelRejectMovement" />
                <Button label="Rechazar" severity="danger" icon="pi pi-times" 
                    @click="confirmRejectMovement" :loading="rejectingMovement" />
            </div>
        </template>
    </Dialog>

    <!-- Dialog para rechazar confirmación del depósito -->
    <Dialog v-model:visible="showRejectConfirmDialog" modal header="Rechazar Aprobación del Depósito" :style="{ width: '500px' }">
        <div class="space-y-4">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <i class="pi pi-exclamation-triangle text-red-600 mt-1"></i>
                    <div>
                        <p class="font-medium text-red-900">¿Confirmar rechazo de la aprobación?</p>
                        <p class="text-sm text-red-700 mt-1">Esta acción rechazará la confirmación del depósito.</p>
                    </div>
                </div>
            </div>
            
            <!-- Campo para observaciones en el rechazo -->
            <div v-if="conclusion.trim()" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h5 class="font-medium text-blue-900 mb-2">Observaciones a incluir:</h5>
                <p class="text-sm text-blue-800 italic">"{{ conclusion }}"</p>
            </div>
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cancelar" text icon="pi pi-times" severity="secondary" @click="cancelRejectConfirm" />
                <Button label="Rechazar" severity="danger" icon="pi pi-times" 
                    @click="confirmRejectConfirm" :loading="rejectingConfirm" />
            </div>
        </template>
    </Dialog>

    <!-- Dialog para imagen completa -->
    <Dialog v-model:visible="showImageDialog" modal header="Voucher del Depósito"
        :style="{ width: '95vw', height: '90vh' }" :maximizable="true">
        <div class="flex justify-center items-center h-full p-4">
            <img :src="deposit.foto" alt="Voucher completo"
                class="max-w-full max-h-full object-contain rounded-lg shadow-lg" />
        </div>
    </Dialog>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Image from 'primevue/image';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';

interface Props {
    deposit: any;
}

const props = defineProps<Props>();
const emit = defineEmits(['close', 'refresh']);
const toast = useToast();

// Control del dialog principal
const dialogVisible = ref(true);

// Estados reactivos
const loading = ref(false);
const validating = ref(false);
const approving = ref(false);
const rejectingMovement = ref(false);
const rejectingConfirm = ref(false);
const showRejectMovementDialog = ref(false);
const showRejectConfirmDialog = ref(false);
const showImageDialog = ref(false);

// Estado para las observaciones
const conclusion = ref('');

// Manejar cierre del dialog
const handleClose = () => {
    dialogVisible.value = false;
    emit('close');
};

// Inicializar datos del componente
const initializeComponent = () => {
    // Cargar conclusion directamente desde las props si existe
    if (props.deposit.conclusion) {
        conclusion.value = props.deposit.conclusion;
        console.log('Conclusion cargada desde props:', props.deposit.conclusion);
    }
    
    // Establecer loading como false ya que tenemos todos los datos
    loading.value = false;
    
    console.log('Datos completos del depósito:', props.deposit);
};

// Funciones de traducción y estilos
function translateEstado(estado: string) {
    const translations: Record<string, string> = {
        'valid': 'Válido',
        'invalid': 'Inválido',
        'pending': 'Pendiente',
        'rejected': 'Rechazado',
        'confirmed': 'Confirmado'
    };
    return translations[estado] || estado;
}

function getSeverity(status: string) {
    const severities: Record<string, string> = {
        'valid': 'success',
        'invalid': 'danger',
        'pending': 'warning',
        'rejected': 'danger',
        'confirmed': 'info'
    };
    return severities[status] || 'contrast';
}

function getIcon(status: string) {
    const icons: Record<string, string> = {
        'valid': 'pi pi-check',
        'invalid': 'pi pi-ban',
        'pending': 'pi pi-clock',
        'rejected': 'pi pi-times',
        'confirmed': 'pi pi-check-circle'
    };
    return icons[status] || 'pi pi-question';
}

function getBankAccountStatusText(status: string) {
    const statusTexts: Record<string, string> = {
        'valid': 'Aprobada',
        'pre_approved': 'Pre-aprobada',
        'pending': 'Pendiente',
        'rejected': 'Rechazada'
    };
    return statusTexts[status] || status;
}

function getBankAccountSeverity(status: string) {
    const severities: Record<string, string> = {
        'valid': 'success',
        'pre_approved': 'warning',
        'pending': 'info',
        'rejected': 'danger'
    };
    return severities[status] || 'contrast';
}

// PRIMERA VALIDACIÓN - Validar movimiento
const validateMovement = async () => {
    if (!props.deposit.id_movimiento) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se encontró el ID del movimiento'
        });
        return;
    }

    validating.value = true;
    try {
        const payload: any = {};
        
        // Incluir observaciones si las hay
        if (conclusion.value.trim()) {
            payload.conclusion = conclusion.value.trim();
        }

        const response = await axios.post(`/deposit/${props.deposit.id_movimiento}/validate`, payload);
        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data.message || 'Movimiento validado correctamente',
            life: 3000
        });
        // Actualizar el estado local
        props.deposit.estado = 'valid';
        emit('refresh');
    } catch (error: any) {
        console.error('Error al validar movimiento:', error);
        const errorMessage = error.response?.data?.message || 'Error al validar el movimiento';
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage
        });
    } finally {
        validating.value = false;
    }
};

// PRIMERA VALIDACIÓN - Rechazar movimiento
const openRejectMovementDialog = () => {
    showRejectMovementDialog.value = true;
};

const confirmRejectMovement = async () => {
    if (!props.deposit.id_movimiento) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se encontró el ID del movimiento'
        });
        return;
    }

    rejectingMovement.value = true;
    try {
        const payload: any = {};
        
        // Incluir observaciones si las hay
        if (conclusion.value.trim()) {
            payload.conclusion = conclusion.value.trim();
        }

        const response = await axios.post(`/deposit/${props.deposit.id_movimiento}/reject`, payload);
        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data.message || 'Movimiento rechazado correctamente',
            life: 3000
        });
        showRejectMovementDialog.value = false;
        // Actualizar el estado local
        props.deposit.estado = 'rejected';
        emit('refresh');
    } catch (error: any) {
        console.error('Error al rechazar movimiento:', error);
        const errorMessage = error.response?.data?.message || 'Error al rechazar el movimiento';
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage
        });
    } finally {
        rejectingMovement.value = false;
    }
};

const cancelRejectMovement = () => {
    showRejectMovementDialog.value = false;
};

// SEGUNDA VALIDACIÓN - Aprobar depósito
const approveDeposit = async () => {
    if (!props.deposit.id_movimiento) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se encontró el ID del movimiento'
        });
        return;
    }

    approving.value = true;
    try {
        const payload: any = {};
        
        // Incluir observaciones si las hay
        if (conclusion.value.trim()) {
            payload.conclusion = conclusion.value.trim();
        }

        const response = await axios.post(`/deposit/${props.deposit.id}/${props.deposit.id_movimiento}/approve`, payload);
        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data.message || 'Depósito aprobado correctamente',
            life: 3000
        });
        // Actualizar el estado local
        props.deposit.estadoConfig = 'valid';
        emit('refresh');
    } catch (error: any) {
        console.error('Error al aprobar:', error);
        const errorMessage = error.response?.data?.message || 'Error al aprobar el depósito';
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage
        });
    } finally {
        approving.value = false;
    }
};

// SEGUNDA VALIDACIÓN - Rechazar confirmación del depósito
const openRejectConfirmDialog = () => {
    showRejectConfirmDialog.value = true;
};

const confirmRejectConfirm = async () => {
    if (!props.deposit.id_movimiento) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se encontró el ID del movimiento'
        });
        return;
    }

    rejectingConfirm.value = true;
    try {
        const payload: any = {};
        
        // Incluir observaciones si las hay
        if (conclusion.value.trim()) {
            payload.conclusion = conclusion.value.trim();
        }

        const response = await axios.post(`/deposit/${props.deposit.id}/${props.deposit.id_movimiento}/reject-confirm`, payload);
        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data.message || 'Confirmación rechazada correctamente',
            life: 3000
        });
        showRejectConfirmDialog.value = false;
        // Actualizar el estado local
        props.deposit.estadoConfig = 'rejected';
        emit('refresh');
    } catch (error: any) {
        console.error('Error al rechazar confirmación:', error);
        const errorMessage = error.response?.data?.message || 'Error al rechazar la confirmación';
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage
        });
    } finally {
        rejectingConfirm.value = false;
    }
};

const cancelRejectConfirm = () => {
    showRejectConfirmDialog.value = false;
};

// Abrir vista previa de imagen
const openImagePreview = () => {
    showImageDialog.value = true;
};

// Formatear monto
const formatAmount = (amount: number | string) => {
    return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN'
    }).format(Number(amount));
};

// Inicializar componente al montarse
onMounted(() => {
    initializeComponent();
});
</script>