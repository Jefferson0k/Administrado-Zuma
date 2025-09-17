<template>

    <Head title="Movimientos de Intercambio" />
    <AppLayout>
        <div>
            <template v-if="isLoading">
                <Espera />
            </template>
            <template v-else>
                <div class="card">
                    <DataTable ref="dt" :value="exchanges" dataKey="id" :paginator="true" :rows="10" :filters="filters"
                        :loading="loadingData"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} movimientos">
                        
                        <template #header>
                            <div class="flex flex-wrap gap-2 items-center justify-between">
                                <h4 class="m-0">Movimientos de Intercambio</h4>
                                <div class="flex gap-2">
                                    <IconField>
                                        <InputIcon>
                                            <i class="pi pi-search" />
                                        </InputIcon>
                                        <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                                    </IconField>
                                </div>
                            </div>
                        </template>

                        <Column field="investor.name" header="Inversor" sortable style="min-width: 15rem">
                            <template #body="slotProps">
                                <div>
                                    <div class="font-semibold">{{ slotProps.data.investor.name }}</div>
                                    <div class="text-sm text-gray-500">{{ slotProps.data.investor.email }}</div>
                                </div>
                            </template>
                        </Column>

                        <Column field="type" header="Tipo" style="min-width: 10rem">
                            <template #body="slotProps">
                                <Tag :value="getTypeLabel(slotProps.data.type)"
                                    :severity="getTypeSeverity(slotProps.data.type)" />
                            </template>
                        </Column>

                        <Column field="amount" header="Monto" style="min-width: 10rem">
                            <template #body="slotProps">
                                <span class="font-semibold" :class="getAmountClass(slotProps.data.type)">
                                    {{ slotProps.data.currency }} {{ formatCurrency(slotProps.data.amount) }}
                                </span>
                            </template>
                        </Column>

                        <Column field="currency" header="Moneda" style="min-width: 8rem">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.currency" severity="info" />
                            </template>
                        </Column>

                        <Column field="status" header="Estado" style="min-width: 10rem">
                            <template #body="slotProps">
                                <Tag :value="getStatusLabel(slotProps.data.status)"
                                    :severity="getStatusSeverity(slotProps.data.status)" />
                            </template>
                        </Column>

                        <Column field="origin" header="Origen" style="min-width: 8rem">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.origin" severity="secondary" />
                            </template>
                        </Column>

                        <Column field="created_at" header="Fecha Creación" sortable style="min-width: 12rem">
                            <template #body="slotProps">
                                <div class="text-sm">
                                    {{ formatDate(slotProps.data.created_at) }}
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </template>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AppLayout from '@/layout/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Espera from '@/components/Espera.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import InputIcon from 'primevue/inputicon';
import IconField from 'primevue/iconfield';
import Tag from 'primevue/tag';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';

const toast = useToast();
const dt = ref();
const isLoading = ref(true);
const loadingData = ref(false);
const exchanges = ref([]);

const filters = ref({
    'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
});

// Cargar movimientos de intercambio desde la API
const loadExchangeRates = async () => {
    loadingData.value = true;
    try {
        const response = await fetch('/exchange/list');
        const data = await response.json();

        if (data.success) {
            exchanges.value = data.data.data;
            // Ordenar por fecha más reciente primero
            exchanges.value.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            
            toast.add({
                severity: 'success',
                summary: 'Éxito',
                detail: 'Movimientos de intercambio cargados correctamente',
                life: 3000
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Error al cargar movimientos de intercambio',
                life: 3000
            });
        }
    } catch (error) {
        console.error('Error loading exchange movements:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error de conexión al servidor',
            life: 3000
        });
    } finally {
        loadingData.value = false;
    }
};

// Formatear moneda
const formatCurrency = (value: string | number) => {
    if (value) {
        return parseFloat(value.toString()).toLocaleString('es-PE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    return '0.00';
};

// Formatear fecha
const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Obtener etiqueta de tipo de movimiento
const getTypeLabel = (type: string) => {
    switch (type) {
        case 'exchange_up':
            return 'Intercambio Subida';
        case 'exchange_down':
            return 'Intercambio Bajada';
        default:
            return type;
    }
};

// Obtener severidad de tipo para Tag
const getTypeSeverity = (type: string) => {
    switch (type) {
        case 'exchange_up':
            return 'success';
        case 'exchange_down':
            return 'danger';
        default:
            return 'info';
    }
};

// Obtener clase CSS para el monto según el tipo
const getAmountClass = (type: string) => {
    switch (type) {
        case 'exchange_up':
            return 'text-green-600';
        case 'exchange_down':
            return 'text-red-600';
        default:
            return 'text-gray-600';
    }
};

// Obtener etiqueta de estado
const getStatusLabel = (status: string) => {
    switch (status) {
        case 'valid':
            return 'Válido';
        case 'invalid':
            return 'Inválido';
        case 'pending':
            return 'Pendiente';
        default:
            return status;
    }
};

// Obtener severidad de estado para Tag
const getStatusSeverity = (status: string) => {
    switch (status) {
        case 'valid':
            return 'success';
        case 'invalid':
            return 'danger';
        case 'pending':
            return 'warning';
        default:
            return 'info';
    }
};

onMounted(async () => {
    // Cargar datos de movimientos de intercambio
    await loadExchangeRates();

    // Simular carga inicial
    setTimeout(() => {
        isLoading.value = false;
    }, 1000);
});
</script>