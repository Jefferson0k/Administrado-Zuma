<template>
    <Dialog 
        v-model:visible="isVisible" 
        :header="`Detalle de Solicitud - ID: ${loanId}`"
        :style="{ width: '95vw', height: '95vh' }"
        :breakpoints="{ '960px': '95vw', '641px': '95vw' }"
        modal
        maximizable
        @hide="handleClose"
    >
        <div class="h-full overflow-y-auto">
            <!-- Loading State -->
            <div v-if="loading" class="flex justify-center items-center py-12">
                <ProgressSpinner />
            </div>

            <!-- Error State -->
            <Message v-else-if="error" severity="error" :closable="false">
                {{ error }}
            </Message>

            <!-- Contenido Principal -->
            <div v-else-if="loanData" class="space-y-6 p-4">
                <!-- Header con Estado -->
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ loanData.inversionista?.nombre_completo }}</h2>
                        <p class="text-gray-500">DNI: {{ loanData.inversionista?.documento }}</p>
                    </div>
                    <Tag 
                        :value="loanData.estado_conclusion || 'Pendiente'" 
                        :severity="getEstadoSeverity(loanData.estado_conclusion)"
                        class="text-lg px-4 py-2"
                    />
                </div>

                <!-- BOTONES DE APROBACIÓN PRINCIPALES - SUPER VISIBLES -->
                <Card v-if="canApprove" class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200">
                    <template #content>
                        <div class="text-center py-4">
                            <div class="mb-4">
                                <i class="pi pi-check-circle text-6xl text-blue-600 mb-3"></i>
                                <h3 class="text-2xl font-bold text-gray-800 mb-2">¿Desea aprobar esta solicitud?</h3>
                                <p class="text-gray-600 mb-6">Revise la información y tome una decisión</p>
                            </div>
                            <div class="flex justify-center gap-4">
                                <Button 
                                    label="APROBAR" 
                                    icon="pi pi-check"
                                    severity="success"
                                    size="large"
                                    raised
                                    class="px-8 py-3 text-lg font-bold"
                                    @click="selectedStatus = 'approved'; showApprovalDialog = true"
                                />
                                <Button 
                                    label="RECHAZAR" 
                                    icon="pi pi-times"
                                    severity="danger"
                                    size="large"
                                    raised
                                    class="px-8 py-3 text-lg font-bold"
                                    @click="selectedStatus = 'rejected'; showApprovalDialog = true"
                                />
                                <Button 
                                    label="OBSERVAR" 
                                    icon="pi pi-exclamation-triangle"
                                    severity="warn"
                                    size="large"
                                    raised
                                    class="px-8 py-3 text-lg font-bold"
                                    @click="selectedStatus = 'observed'; showApprovalDialog = true"
                                />
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Mensaje si ya fue aprobado -->
                <Card v-else-if="isApproved" class="bg-green-50 border-2 border-green-200">
                    <template #content>
                        <div class="text-center py-4">
                            <i class="pi pi-check-circle text-6xl text-green-600 mb-3"></i>
                            <h3 class="text-2xl font-bold text-green-800 mb-2">Solicitud Aprobada</h3>
                            <p class="text-gray-600">Esta solicitud ya ha sido aprobada</p>
                        </div>
                    </template>
                </Card>

                <!-- Mensaje si ya fue rechazado -->
                <Card v-else-if="isRejected" class="bg-red-50 border-2 border-red-200">
                    <template #content>
                        <div class="text-center py-4">
                            <i class="pi pi-times-circle text-6xl text-red-600 mb-3"></i>
                            <h3 class="text-2xl font-bold text-red-800 mb-2">Solicitud Rechazada</h3>
                            <p class="text-gray-600">Esta solicitud ya ha sido rechazada</p>
                        </div>
                    </template>
                </Card>


                <!-- Información del Inversionista -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-3">
                            <i class="pi pi-user text-2xl text-blue-600"></i>
                            <span>Información del Inversionista</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label class="text-sm font-semibold text-gray-600">Nombre Completo</label>
                                <p class="text-lg mt-1">{{ loanData.inversionista?.nombre_completo || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-600">Documento</label>
                                <p class="text-lg mt-1">{{ loanData.inversionista?.documento || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-600">Profesión/Ocupación</label>
                                <p class="text-lg mt-1">{{ loanData.profesion_ocupacion || '-' }}</p>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Información Financiera -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-3">
                            <i class="pi pi-money-bill text-2xl text-green-600"></i>
                            <span>Información Financiera</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <label class="text-sm font-semibold text-blue-600">Monto General</label>
                                <p class="text-2xl font-bold text-blue-700 mt-2">{{ loanData.monto_general }}</p>
                            </div>
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <label class="text-sm font-semibold text-purple-600">Monto Requerido</label>
                                <p class="text-2xl font-bold text-purple-700 mt-2">{{ loanData.monto_requerido }}</p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <label class="text-sm font-semibold text-green-600">Monto Invertir</label>
                                <p class="text-2xl font-bold text-green-700 mt-2">{{ formatCurrency(loanData.monto_invertir) }}</p>
                            </div>
                            <div class="bg-orange-50 p-4 rounded-lg">
                                <label class="text-sm font-semibold text-orange-600">Monto Préstamo</label>
                                <p class="text-2xl font-bold text-orange-700 mt-2">{{ formatCurrency(loanData.monto_prestamo) }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-600">TEA</label>
                                <p class="text-lg mt-1">{{ loanData.tea }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-600">TEM</label>
                                <p class="text-lg mt-1">{{ loanData.tem }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-600">Plazo</label>
                                <p class="text-lg mt-1">{{ loanData.plazo }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-600">Esquema</label>
                                <p class="text-lg mt-1">{{ loanData.esquema }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-600">Riesgo</label>
                                <Tag :value="loanData.riesgo" :severity="getRiesgoSeverity(loanData.riesgo)" class="mt-1" />
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-600">Ingreso Promedio</label>
                                <p class="text-lg mt-1">{{ formatCurrency(loanData.ingreso_promedio) }}</p>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Información del Préstamo -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-3">
                            <i class="pi pi-file-edit text-2xl text-indigo-600"></i>
                            <span>Detalles del Préstamo</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-semibold text-gray-600">Motivo del Préstamo</label>
                                <p class="text-base mt-1 text-gray-700">{{ loanData.motivo_prestamo || '-' }}</p>
                            </div>
                            <Divider />
                            <div>
                                <label class="text-sm font-semibold text-gray-600">Descripción del Financiamiento</label>
                                <p class="text-base mt-1 text-gray-700">{{ loanData.descripcion_financiamiento || '-' }}</p>
                            </div>
                            <Divider />
                            <div>
                                <label class="text-sm font-semibold text-gray-600">Solicitud del Préstamo Para</label>
                                <p class="text-base mt-1 text-gray-700">{{ loanData.solicitud_prestamo_para || '-' }}</p>
                            </div>
                            <Divider />
                            <div>
                                <label class="text-sm font-semibold text-gray-600">Fuente de Ingreso</label>
                                <p class="text-base mt-1 text-gray-700">{{ loanData.fuente_ingreso || '-' }}</p>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Información de Tasación -->
                <Card v-if="loanData.empresa_tasadora">
                    <template #title>
                        <div class="flex items-center gap-3">
                            <i class="pi pi-building text-2xl text-teal-600"></i>
                            <span>Tasación</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="text-sm font-semibold text-gray-600">Empresa Tasadora</label>
                                <p class="text-lg mt-1">{{ loanData.empresa_tasadora }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-600">Monto de Tasación</label>
                                <p class="text-lg mt-1">{{ formatCurrency(loanData.monto_tasacion) }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-600">Porcentaje Préstamo</label>
                                <p class="text-lg mt-1">{{ loanData.porcentaje_prestamo }}%</p>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Propiedades -->
                <Card v-if="loanData.propiedades && loanData.propiedades.length > 0">
                    <template #title>
                        <div class="flex items-center gap-3">
                            <i class="pi pi-home text-2xl text-amber-600"></i>
                            <span>Propiedades</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-6">
                            <div 
                                v-for="(propiedad, index) in loanData.propiedades" 
                                :key="index"
                                class="border rounded-lg p-4 bg-gray-50"
                            >
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <label class="text-sm font-semibold text-gray-600">Nombre</label>
                                        <p class="text-base mt-1">{{ propiedad.nombre }}</p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-semibold text-gray-600">Tipo de Inmueble</label>
                                        <p class="text-base mt-1">{{ propiedad.tipo_inmueble }}</p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-semibold text-gray-600">Pertenece a</label>
                                        <p class="text-base mt-1">{{ propiedad.pertenece }}</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="text-sm font-semibold text-gray-600">Dirección</label>
                                        <p class="text-base mt-1">{{ propiedad.direccion }}</p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-semibold text-gray-600">Ubicación</label>
                                        <p class="text-base mt-1">
                                            {{ propiedad.distrito }}, {{ propiedad.provincia }}, {{ propiedad.departamento }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-semibold text-gray-600">Valor Estimado</label>
                                        <p class="text-lg font-semibold text-green-600 mt-1">{{ propiedad.valor_estimado }}</p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-semibold text-gray-600">Valor Requerido</label>
                                        <p class="text-lg font-semibold text-blue-600 mt-1">{{ propiedad.valor_requerido }}</p>
                                    </div>
                                </div>
                                <div v-if="propiedad.descripcion" class="mb-4">
                                    <label class="text-sm font-semibold text-gray-600">Descripción</label>
                                    <p class="text-base mt-1 text-gray-700">{{ propiedad.descripcion }}</p>
                                </div>
                                
                                <!-- Galería de imágenes -->
                                <div v-if="propiedad.imagenes && propiedad.imagenes.length > 0">
                                    <label class="text-sm font-semibold text-gray-600 block mb-3">Imágenes de la Propiedad</label>
                                    <Galleria 
                                        :value="propiedad.imagenes" 
                                        :responsiveOptions="responsiveOptions"
                                        :numVisible="5"
                                        containerStyle="max-width: 100%"
                                        :showThumbnails="true"
                                        :showItemNavigators="true"
                                    >
                                        <template #item="slotProps">
                                            <img 
                                                :src="slotProps.item.url" 
                                                :alt="slotProps.item.descripcion"
                                                class="w-full h-96 object-cover rounded-lg"
                                            />
                                        </template>
                                        <template #thumbnail="slotProps">
                                            <img 
                                                :src="slotProps.item.url" 
                                                :alt="slotProps.item.descripcion"
                                                class="w-full h-20 object-cover"
                                            />
                                        </template>
                                    </Galleria>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Cronograma de Pagos -->
                <Card v-if="loanData.cronograma && loanData.cronograma.length > 0">
                    <template #title>
                        <div class="flex items-center gap-3">
                            <i class="pi pi-calendar text-2xl text-pink-600"></i>
                            <span>Cronograma de Pagos</span>
                        </div>
                    </template>
                    <template #content>
                        <DataTable 
                            :value="loanData.cronograma" 
                            :paginator="true" 
                            :rows="12"
                            stripedRows
                            class="p-datatable-sm"
                        >
                            <Column field="cuota" header="N° Cuota" style="width: 100px"></Column>
                            <Column field="vencimiento" header="Vencimiento" style="width: 150px"></Column>
                            <Column field="saldo_inicial" header="Saldo Inicial">
                                <template #body="{ data }">
                                    {{ formatCurrency(data.saldo_inicial) }}
                                </template>
                            </Column>
                            <Column field="capital" header="Capital">
                                <template #body="{ data }">
                                    {{ formatCurrency(data.capital) }}
                                </template>
                            </Column>
                            <Column field="intereses" header="Intereses">
                                <template #body="{ data }">
                                    {{ formatCurrency(data.intereses) }}
                                </template>
                            </Column>
                            <Column field="total_cuota" header="Total Cuota">
                                <template #body="{ data }">
                                    <span class="font-semibold">{{ formatCurrency(data.total_cuota) }}</span>
                                </template>
                            </Column>
                            <Column field="saldo_final" header="Saldo Final">
                                <template #body="{ data }">
                                    {{ formatCurrency(data.saldo_final) }}
                                </template>
                            </Column>
                            <Column field="estado" header="Estado">
                                <template #body="{ data }">
                                    <Tag :value="data.estado" :severity="data.estado === 'pagado' ? 'success' : 'warn'" />
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>

                <!-- Historial de Aprobaciones -->
                <Card v-if="loanData.approval1_status">
                    <template #title>
                        <div class="flex items-center gap-3">
                            <i class="pi pi-history text-2xl text-gray-600"></i>
                            <span>Historial de Aprobación</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div class="border-l-4 border-blue-500 pl-4 py-2">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <p class="font-semibold text-lg">Primera Aprobación</p>
                                        <p class="text-sm text-gray-600">Por: {{ loanData.approval1_by || 'N/A' }}</p>
                                    </div>
                                    <Tag 
                                        :value="loanData.approval1_status" 
                                        :severity="getEstadoSeverity(loanData.approval1_status)"
                                    />
                                </div>
                                <p class="text-sm text-gray-500">
                                    Fecha: {{ loanData.approval1_at ? formatDate(loanData.approval1_at) : 'N/A' }}
                                </p>
                                <p v-if="loanData.approval1_comment" class="text-sm mt-2 text-gray-700">
                                    <strong>Comentario:</strong> {{ loanData.approval1_comment }}
                                </p>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>

        <!-- Dialog de Confirmación de Aprobación -->
        <Dialog 
            v-model:visible="showApprovalDialog" 
            :header="getDialogTitle()"
            :style="{ width: '500px' }"
            modal
        >
            <div class="p-4">
                <div class="mb-4">
                    <Message :severity="getMessageSeverity()" :closable="false">
                        {{ getDialogMessage() }}
                    </Message>
                </div>
                
                <div class="mb-4">
                    <label for="comment" class="block text-sm font-medium mb-2">
                        Comentario {{ selectedStatus === 'observed' ? '(Requerido)' : '(Opcional)' }}
                    </label>
                    <Textarea 
                        id="comment"
                        v-model="approvalComment" 
                        rows="4" 
                        placeholder="Ingrese un comentario..."
                        class="w-full"
                        maxlength="500"
                    />
                    <small class="text-gray-500">{{ approvalComment.length }}/500 caracteres</small>
                </div>

                <Message v-if="selectedStatus === 'approved'" severity="info" :closable="false">
                    Al aprobar, la solicitud quedará activa y disponible para inversión.
                </Message>
            </div>
            
            <template #footer>
                <Button 
                    label="Cancelar" 
                    severity="secondary" 
                    @click="showApprovalDialog = false; approvalComment = ''"
                />
                <Button 
                    :label="getConfirmButtonLabel()" 
                    :icon="getConfirmButtonIcon()"
                    :severity="getButtonSeverity()"
                    @click="submitApproval" 
                    :loading="submitting"
                    :disabled="selectedStatus === 'observed' && !approvalComment"
                />
            </template>
        </Dialog>
    </Dialog>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import ProgressSpinner from 'primevue/progressspinner';
import Textarea from 'primevue/textarea';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Divider from 'primevue/divider';
import Galleria from 'primevue/galleria';

const props = defineProps({
    visible: {
        type: Boolean,
        required: true
    },
    prestamosId: {
        type: [String, Number],
        required: true
    }
});

const emit = defineEmits(['update:visible', 'close', 'approved']);

const toast = useToast();
const loanId = ref(props.prestamosId);
const loanData = ref(null);
const loading = ref(false);
const error = ref(null);
const showApprovalDialog = ref(false);
const selectedStatus = ref(null);
const approvalComment = ref('');
const submitting = ref(false);

const isVisible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value)
});

// Computed para determinar si mostrar botones de aprobación
const canApprove = computed(() => {
    if (!loanData.value) return false;
    const estado = loanData.value.estado_conclusion;
    return estado === 'pending' || 
           estado === 'observed' || 
           estado === 'Pendiente' || 
           estado === 'Observado' || 
           estado === null || 
           estado === undefined ||
           estado === '';
});

const isApproved = computed(() => {
    if (!loanData.value) return false;
    const estado = loanData.value.estado_conclusion;
    return estado === 'approved' || estado === 'Aprobado' || estado === 'aprobado';
});

const isRejected = computed(() => {
    if (!loanData.value) return false;
    const estado = loanData.value.estado_conclusion;
    return estado === 'rejected' || estado === 'Rechazado' || estado === 'rechazado';
});

// Opciones responsivas para la galería
const responsiveOptions = ref([
    { breakpoint: '1024px', numVisible: 5 },
    { breakpoint: '768px', numVisible: 3 },
    { breakpoint: '560px', numVisible: 1 }
]);

// Watch para cargar datos cuando se abre el dialog
watch(() => props.visible, (newVal) => {
    if (newVal && props.prestamosId) {
        loanId.value = props.prestamosId;
        getData();
    }
});

// Función para obtener datos
const getData = async () => {
    loading.value = true;
    error.value = null;
    
    try {
        const response = await axios.get(`/property-loan-details/${loanId.value}`);
        
        console.log('Respuesta completa del API:', response.data);
        
        if (response.data && response.data.data) {
            loanData.value = response.data.data;
            console.log('Estado de conclusión:', loanData.value.estado_conclusion);
        } else {
            throw new Error('Estructura de datos inesperada');
        }
    } catch (err) {
        console.error('Error al cargar los datos:', err);
        error.value = err.response?.data?.message || 'No se pudo cargar la información del préstamo';
        
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.value,
            life: 5000
        });
    } finally {
        loading.value = false;
    }
};

// Función para enviar la aprobación
const submitApproval = async () => {
    if (selectedStatus.value === 'observed' && !approvalComment.value) {
        toast.add({
            severity: 'warn',
            summary: 'Atención',
            detail: 'Debe ingresar un comentario para observar la solicitud',
            life: 3000
        });
        return;
    }

    submitting.value = true;

    try {
        const payload = {
            status: selectedStatus.value,
            comment: approvalComment.value || null
        };

        const response = await axios.post(
            `/property-loan-details/${loanId.value}/approve`,
            payload
        );

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data.message || 'Aprobación registrada correctamente',
            life: 3000
        });

        await getData();
        
        showApprovalDialog.value = false;
        approvalComment.value = '';
        selectedStatus.value = null;

        emit('approved');

    } catch (err) {
        console.error('Error al registrar la aprobación:', err);
        const errorMessage = err.response?.data?.message || 'Error al registrar la aprobación';
        
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage,
            life: 5000
        });
    } finally {
        submitting.value = false;
    }
};

const handleClose = () => {
    emit('close');
    emit('update:visible', false);
};

const formatCurrency = (value) => {
    if (!value || value === '0' || value === 0) return '-';
    const number = parseFloat(value);
    return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
        minimumFractionDigits: 2
    }).format(number);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('es-PE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getEstadoSeverity = (estado) => {
    const severityMap = {
        'approved': 'success',
        'pending': 'warn',
        'rejected': 'danger',
        'observed': 'info',
        'activo': 'success',
        'activa': 'success',
        'Aprobado': 'success',
        'Pendiente': 'warn',
        'Rechazado': 'danger',
        'Observado': 'info'
    };
    return severityMap[estado] || 'secondary';
};

const getRiesgoSeverity = (riesgo) => {
    const severityMap = {
        'A+': 'success',
        'A': 'success',
        'B': 'info',
        'C': 'warn',
        'D': 'danger'
    };
    return severityMap[riesgo] || 'secondary';
};

const getDialogTitle = () => {
    const titles = {
        'approved': 'Confirmar Aprobación',
        'rejected': 'Confirmar Rechazo',
        'observed': 'Agregar Observación'
    };
    return titles[selectedStatus.value] || 'Confirmación';
};

const getDialogMessage = () => {
    const messages = {
        'approved': '¿Está seguro de que desea aprobar esta solicitud? La solicitud quedará activa.',
        'rejected': '¿Está seguro de que desea rechazar esta solicitud? Esta acción no se puede deshacer fácilmente.',
        'observed': 'Agregue una observación a esta solicitud. El solicitante será notificado.'
    };
    return messages[selectedStatus.value] || '';
};

const getMessageSeverity = () => {
    const severities = {
        'approved': 'success',
        'rejected': 'error',
        'observed': 'warn'
    };
    return severities[selectedStatus.value] || 'info';
};

const getConfirmButtonLabel = () => {
    const labels = {
        'approved': 'Confirmar Aprobación',
        'rejected': 'Confirmar Rechazo',
        'observed': 'Guardar Observación'
    };
    return labels[selectedStatus.value] || 'Confirmar';
};

const getConfirmButtonIcon = () => {
    const icons = {
        'approved': 'pi pi-check',
        'rejected': 'pi pi-times',
        'observed': 'pi pi-exclamation-triangle'
    };
    return icons[selectedStatus.value] || 'pi pi-check';
};

const getButtonSeverity = () => {
    const severities = {
        'approved': 'success',
        'rejected': 'danger',
        'observed': 'warn'
    };
    return severities[selectedStatus.value] || 'primary';
};
</script>

<style scoped>
:deep(.p-card-title) {
    font-size: 1.25rem;
    font-weight: 600;
}

:deep(.p-card-content) {
    padding-top: 1rem;
}

:deep(.p-galleria-thumbnail-container) {
    background: rgba(0, 0, 0, 0.9);
}

:deep(.p-dialog-content) {
    overflow: hidden;
}
</style>