<template>
    <!-- Modal principal de factura -->
    <Dialog v-model:visible="visible" modal header="Detalles de la Factura" :style="{ width: '500px' }" :closable="true"
        @hide="onHide">
        <div v-if="facturaData">
            <div>
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
                        <span class="font-medium">Tasa:</span>
                        <p class="">{{ facturaData.tasa }}%</p>
                    </div>

                    <div>
                        <span class="font-medium">Monto Factura:</span>
                        <p class="">{{ formatCurrency(facturaData.montoFactura, facturaData.moneda) }}</p>
                    </div>

                    <div>
                        <span class="font-medium">Monto Asumido ZUMA:</span>
                        <p class="">{{ formatCurrency(facturaData.montoAsumidoZuma, facturaData.moneda) }}</p>
                    </div>

                    <div>
                        <span class="font-medium">Estado:</span>
                        <p class="">{{ facturaData.estado }}</p>
                    </div>

                    <div>
                        <span class="font-medium">Fecha de Pago:</span>
                        <p class="">{{ facturaData.fechaPago }}</p>
                    </div>

                    <div>
                        <span class="font-medium">Número del Préstamo:</span>
                        <p class="">{{ facturaData.loan_number }}</p>
                    </div>

                    <div>
                        <span class="font-medium">Número de la Factura:</span>
                        <p class="">{{ facturaData.invoice_number }}</p>
                    </div>

                    <div>
                        <span class="font-medium">RUC del Proveedor:</span>
                        <p class="">{{ facturaData.ruc_proveedor }}</p>
                    </div>
                </div>

                <!-- Resumen de Inversionistas - Solo visible si estado es "daStandby" -->
                <Message v-if="facturaData.estado === 'daStandby' && facturaData.investments && facturaData.investments.length > 0" 
                        severity="info" 
                        class="mt-6 w-full">
                    
                    <div class="p-2">
                        <h4 class="font-semibold text-base mb-3 text-blue-900 flex items-center">
                            <i class="pi pi-users mr-2 text-blue-700"></i>
                            Resumen de Inversiones
                        </h4>
                        
                        <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                            Monto Total Invertido:
                                <p class="text-lg font-bold text-green-600">
                                    {{ formatCurrency(investmentStats.totalAmount, facturaData.moneda) }}
                                </p>
                        </div>

                        <Button 
                            label="Ver Lista Completa de Inversionistas" 
                            icon="pi pi-eye" 
                            @click="openInvestorsModal" 
                            fluid
                            severity="info"
                        />
                    </div>
                </Message>
            </div>

            <div class="mb-6">
                <h4 class="font-semibold mb-4 flex items-center gap-2">
                    <i class="pi pi-users"></i>
                    Estado de Aprobaciones
                </h4>
                
                <div class="space-y-4">
                    <!-- Primera Aprobación -->
                    <div class="flex items-center gap-4 p-4 bg-white rounded-lg border-2" :class="getApprovalBorderClass(facturaData.PrimerStado)">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center" :class="getApprovalBgClass(facturaData.PrimerStado)">
                                <i :class="getApprovalIconClass(facturaData.PrimerStado)"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="font-semibold text-gray-800">Primera Aprobación</div>
                            <div class="text-sm" :class="getApprovalTextClass(facturaData.PrimerStado)">
                                {{ getApprovalStatusText(facturaData.PrimerStado) }}
                            </div>
                            <div class="text-sm text-gray-600 mt-1">
                                <b>Usuario:</b> {{ facturaData.userprimerNombre }}
                            </div>
                            <div class="text-sm text-gray-600 mt-1">
                                <b>Comentarios:</b> {{ facturaData.approval1_comment || 'Sin comentarios' }}
                            </div>
                            <div v-if="facturaData.tiempoUno" class="text-xs text-gray-500 mt-1">
                                <b>{{ facturaData.tiempoUno }}</b>
                            </div>
                        </div>
                    </div>
                    <!-- Segunda Aprobación -->
                    <div class="flex items-center gap-4 p-4 bg-white rounded-lg border-2" :class="getApprovalBorderClass(facturaData.SegundaStado)">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center" :class="getApprovalBgClass(facturaData.SegundaStado)">
                                <i :class="getApprovalIconClass(facturaData.SegundaStado)"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="font-semibold text-gray-800">Segunda Aprobación</div>
                            <div class="text-sm" :class="getApprovalTextClass(facturaData.SegundaStado)">
                                {{ getApprovalStatusText(facturaData.SegundaStado) }}
                            </div>
                            <div class="text-sm text-gray-600 mt-1">
                                <b>Usuario:</b> {{ facturaData.userdosNombre }}
                            </div>
                            <div class="text-sm text-gray-600 mt-1">
                                <b>Comentarios:</b> {{ facturaData.approval2_comment || 'Sin comentarios' }}
                            </div>
                            <div v-if="facturaData.tiempoDos" class="text-xs text-gray-500 mt-1">
                                <b>{{ facturaData.tiempoDos }}</b>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Botones de acción -->
        <template #footer>
            <Button label="Cancelar" severity="secondary" text @click="onCancel" :disabled="loading" />
        </template>
    </Dialog>

    <!-- Modal de Inversionistas -->
    <Dialog v-model:visible="investorsModalVisible" modal header="Lista de Inversionistas" :style="{ width: '80vw', maxWidth: '1000px' }" 
            :closable="true" maximizable>
        <div class="space-y-4">
            <!-- Barra de búsqueda y filtros -->
            <div class="flex gap-4 items-center">
                <div class="flex-1">
                    <input 
                        v-model="searchTerm" 
                        type="text" 
                        placeholder="Buscar por nombre o DNI..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>
                <div>
                    <select v-model="selectedStatus" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos los estados</option>
                        <option value="active">Activo</option>
                        <option value="paid">Pagado</option>
                        <option value="inactive">Inactivo</option>
                        <option value="reprogramed">Reprogramado</option>
                    </select>
                </div>
                <Button 
                    label="Exportar" 
                    icon="pi pi-download" 
                    @click="exportToExcel" 
                    severity="success"
                    size="small"
                />
            </div>

            <!-- Lista de inversionistas -->
            <div class="grid gap-4 max-h-96 overflow-y-auto">
                <div v-for="investment in filteredInvestments" :key="investment.id" 
                     class="bg-white border border-gray-200 p-4 rounded-lg hover:shadow-md transition-shadow">
                    <div class="grid grid-cols-12 gap-3 items-center text-sm">
                        <!-- Nombre e información personal -->
                        <div class="col-span-4">
                            <p class="font-semibold text-gray-900 mb-1">{{ investment.inversionista }}</p>
                            <p class="text-gray-600 text-xs">DNI: {{ investment.document }}</p>
                            <p class="text-gray-600 text-xs">{{ investment.correo }}</p>
                        </div>
                        <!-- Monto y retorno -->
                        <div class="col-span-3 text-center">
                            <p class="text-gray-700 font-medium">
                                {{ formatCurrency(investment.amount, investment.currency) }}
                            </p>
                        </div>
                        <!-- Estado -->
                        <div class="col-span-2 text-center">
                            <span :class="getStatusBadgeClass(investment.status)">
                                {{ getStatusText(investment.status) }}
                            </span>
                        </div>
                        <!-- Fecha de creación -->
                        <div class="col-span-2 text-center">
                            <p class="text-gray-600 text-xs">{{ investment.creacion }}</p>
                        </div>
                        <!-- Acciones -->
                        <div class="col-span-1 text-center">
                            <Button 
                                icon="pi pi-phone" 
                                @click="contactInvestor(investment)"
                                severity="info"
                                size="small"
                                text
                                v-tooltip.top="'Contactar'"
                            />
                        </div>
                    </div>
                </div>

                <!-- Mensaje si no hay resultados -->
                <div v-if="filteredInvestments.length === 0" class="text-center py-8 text-gray-500">
                    <i class="pi pi-search text-4xl mb-4"></i>
                    <p>No se encontraron inversionistas con los criterios seleccionados</p>
                </div>
            </div>

            <!-- Paginación simple -->
            <div v-if="totalPages > 1" class="flex justify-center items-center gap-2">
                <Button 
                    icon="pi pi-chevron-left" 
                    @click="currentPage--" 
                    :disabled="currentPage === 1"
                    text
                    size="small"
                />
                <span class="text-sm text-gray-600">
                    Página {{ currentPage }} de {{ totalPages }}
                </span>
                <Button 
                    icon="pi pi-chevron-right" 
                    @click="currentPage++" 
                    :disabled="currentPage === totalPages"
                    text
                    size="small"
                />
            </div>
        </div>

        <template #footer>
            <Button label="Cerrar" severity="secondary" @click="investorsModalVisible = false" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import Message from 'primevue/message';

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

// Modal de inversionistas
const investorsModalVisible = ref(false);
const searchTerm = ref('');
const selectedStatus = ref('');
const currentPage = ref(1);
const itemsPerPage = 20;

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

// Computed properties
const investmentStats = computed(() => {
    if (!facturaData.value?.investments) return {
        total: 0,
        active: 0,
        paid: 0,
        inactive: 0,
        reprogramed: 0,
        totalAmount: 0,
        totalReturn: 0
    };

    const investments = facturaData.value.investments;
    const stats = {
        total: investments.length,
        active: 0,
        paid: 0,
        inactive: 0,
        reprogramed: 0,
        totalAmount: 0,
        totalReturn: 0
    };

    investments.forEach(inv => {
        stats[inv.status.toLowerCase()]++;
        stats.totalAmount += parseFloat(inv.amount || 0);
        stats.totalReturn += parseFloat(inv.return || 0);
    });

    return stats;
});

const filteredInvestments = computed(() => {
    if (!facturaData.value?.investments) return [];
    
    let filtered = facturaData.value.investments;

    // Filtro por búsqueda
    if (searchTerm.value) {
        const search = searchTerm.value.toLowerCase();
        filtered = filtered.filter(inv => 
            inv.inversionista.toLowerCase().includes(search) ||
            inv.document.includes(search) ||
            inv.correo.toLowerCase().includes(search)
        );
    }

    // Filtro por estado
    if (selectedStatus.value) {
        filtered = filtered.filter(inv => inv.status.toLowerCase() === selectedStatus.value);
    }

    // Paginación
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    
    return filtered.slice(start, end);
});

const totalPages = computed(() => {
    if (!facturaData.value?.investments) return 1;
    
    let filtered = facturaData.value.investments;

    if (searchTerm.value) {
        const search = searchTerm.value.toLowerCase();
        filtered = filtered.filter(inv => 
            inv.inversionista.toLowerCase().includes(search) ||
            inv.document.includes(search) ||
            inv.correo.toLowerCase().includes(search)
        );
    }

    if (selectedStatus.value) {
        filtered = filtered.filter(inv => inv.status.toLowerCase() === selectedStatus.value);
    }

    return Math.ceil(filtered.length / itemsPerPage);
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

const openInvestorsModal = () => {
    investorsModalVisible.value = true;
    currentPage.value = 1;
    searchTerm.value = '';
    selectedStatus.value = '';
};

const contactInvestor = (investment) => {
    toast.add({
        severity: 'info',
        summary: 'Contactar Inversionista',
        detail: `Contactando a ${investment.inversionista} - ${investment.correo}`,
        life: 3000
    });
    // Aquí podrías abrir un modal de contacto, enviar email, etc.
};

const exportToExcel = () => {
    // Simular exportación
    toast.add({
        severity: 'success',
        summary: 'Exportación',
        detail: 'Lista de inversionistas exportada exitosamente',
        life: 3000
    });
    
    // Aquí implementarías la lógica real de exportación
    // Por ejemplo, usando una librería como xlsx o enviando al backend
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

const getStatusBadgeClass = (status) => {
    const baseClasses = 'px-2 py-1 rounded-full text-xs font-medium';
    switch (status?.toLowerCase()) {
        case 'active':
            return `${baseClasses} bg-green-100 text-green-800`;
        case 'paid':
            return `${baseClasses} bg-blue-100 text-blue-800`;
        case 'inactive':
            return `${baseClasses} bg-gray-100 text-gray-800`;
        case 'reprogramed':
            return `${baseClasses} bg-orange-100 text-orange-800`;
        default:
            return `${baseClasses} bg-gray-100 text-gray-800`;
    }
};

const getStatusText = (status) => {
    const statusTranslations = {
        'inactive': 'Inactivo',
        'active': 'Activo',
        'paid': 'Pagado',
        'reprogramed': 'Reprogramado',
        'annulled': 'Anulado'
    };
    return statusTranslations[status?.toLowerCase()] || status;
};

// Funciones para el estado de aprobaciones - ACTUALIZADAS CON OBSERVED
const getApprovalBorderClass = (status) => {
    switch (status) {
        case 'approved': return 'border-green-200 bg-green-50';
        case 'pending': return 'border-orange-200 bg-orange-50';
        case 'rejected': return 'border-red-200 bg-red-50';
        case 'observed': return 'border-yellow-200 bg-yellow-50';
        default: return 'border-gray-200 bg-gray-50';
    }
};

const getApprovalIconClass = (status) => {
    switch (status) {
        case 'approved': return 'pi pi-check text-white';
        case 'pending': return 'pi pi-clock text-white';
        case 'rejected': return 'pi pi-times text-white';
        case 'observed': return 'pi pi-eye text-white';
        default: return 'pi pi-question text-white';
    }
};

const getApprovalTextClass = (status) => {
    switch (status) {
        case 'approved': return 'text-green-600 font-semibold';
        case 'pending': return 'text-orange-600 font-semibold';
        case 'rejected': return 'text-red-600 font-semibold';
        case 'observed': return 'text-yellow-600 font-semibold';
        default: return 'text-gray-600';
    }
};

const getApprovalBgClass = (status) => {
    switch (status) {
        case 'approved': return 'bg-green-500';
        case 'pending': return 'bg-orange-500';
        case 'rejected': return 'bg-red-500';
        case 'observed': return 'bg-yellow-500';
        default: return 'bg-gray-400';
    }
};

const getApprovalStatusText = (status) => {
    switch (status) {
        case 'approved': return 'Aprobado';
        case 'pending': return 'Pendiente';
        case 'rejected': return 'Rechazado';
        case 'observed': return 'Observado';
        default: return 'Sin estado';
    }
};

// Reset page when filters change
watch([searchTerm, selectedStatus], () => {
    currentPage.value = 1;
});
</script>