<template>

    <Head title="Movimientos de Intercambio" />
    <AppLayout>
        <div>
            <template v-if="isLoading">
                <Espera />
            </template>
            <template v-else>
                <div class="card">
                    <DataTable ref="dt" :value="groupedExchanges" dataKey="investorId" :paginator="true" :rows="10" :filters="filters"
                        :loading="loadingData" v-model:expandedRows="expandedRows" :expandedRowIcon="'pi pi-chevron-down'"
                        :collapsedRowIcon="'pi pi-chevron-right'"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} inversores">
                        
                        <template #header>
                            <div class="flex flex-wrap gap-2 items-center justify-between">
                                <h4 class="m-0">Movimientos de Intercambio</h4>
                                <div class="flex gap-2">
                                    <Button icon="pi pi-plus" label="Expandir Todo" @click="expandAll" 
                                           class="p-button-outlined p-button-sm mr-2" />
                                    <Button icon="pi pi-minus" label="Contraer Todo" @click="collapseAll" 
                                           class="p-button-outlined p-button-sm mr-2" />
                                    <IconField>
                                        <InputIcon>
                                            <i class="pi pi-search" />
                                        </InputIcon>
                                        <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                                    </IconField>
                                </div>
                            </div>
                        </template>

                        <Column expander style="width: 3rem" />

                        <Column field="name" header="Inversor" sortable style="min-width: 15rem">
                            <template #body="slotProps">
                                <div>
                                    <div class="font-semibold text-lg">{{ slotProps.data.name }}</div>
                                    <div class="text-sm text-gray-500">{{ slotProps.data.email }}</div>
                                </div>
                            </template>
                        </Column>

                        <Column field="totalMovements" header="Total Movimientos" sortable style="min-width: 8rem">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.totalMovements" severity="info" />
                            </template>
                        </Column>

                        <Column header="Totales por Moneda" style="min-width: 15rem">
                            <template #body="slotProps">
                                <div class="flex gap-2 flex-wrap">
                                    <div v-for="(total, currency) in slotProps.data.totals" :key="currency" 
                                         class="flex items-center gap-1 bg-gray-100 px-2 py-1 rounded">
                                        <Tag :value="currency" severity="secondary" />
                                        <span class="font-semibold text-sm" :class="total >= 0 ? 'text-green-600' : 'text-red-600'">
                                            {{ formatCurrency(Math.abs(total)) }}
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column field="lastMovement" header="Último Movimiento" sortable style="min-width: 12rem">
                            <template #body="slotProps">
                                <div class="text-sm">
                                    {{ formatDate(slotProps.data.lastMovement) }}
                                </div>
                            </template>
                        </Column>

                        <template #expansion="slotProps">
                            <div class="p-4">
                                <h5 class="mb-3">Movimientos de {{ slotProps.data.name }}</h5>
                                <DataTable :value="slotProps.data.exchanges" class="p-datatable-sm">
                                    <Column field="type" header="Tipo" style="min-width: 10rem">
                                        <template #body="exchangeProps">
                                            <Tag :value="getTypeLabel(exchangeProps.data.type)"
                                                :severity="getTypeSeverity(exchangeProps.data.type)" />
                                        </template>
                                    </Column>

                                    <Column field="amount" header="Monto" style="min-width: 10rem">
                                        <template #body="exchangeProps">
                                            <span class="font-semibold" :class="getAmountClass(exchangeProps.data.type)">
                                                {{ exchangeProps.data.currency }} {{ formatCurrency(exchangeProps.data.amount) }}
                                            </span>
                                        </template>
                                    </Column>

                                    <Column field="currency" header="Moneda" style="min-width: 8rem">
                                        <template #body="exchangeProps">
                                            <Tag :value="exchangeProps.data.currency" severity="info" />
                                        </template>
                                    </Column>

                                    <Column field="status" header="Estado" style="min-width: 10rem">
                                        <template #body="exchangeProps">
                                            <Tag :value="getStatusLabel(exchangeProps.data.status)"
                                                :severity="getStatusSeverity(exchangeProps.data.status)" />
                                        </template>
                                    </Column>

                                    <Column field="origin" header="Origen" style="min-width: 8rem">
                                        <template #body="exchangeProps">
                                            <Tag :value="exchangeProps.data.origin" severity="secondary" />
                                        </template>
                                    </Column>

                                    <Column field="created_at" header="Fecha Creación" style="min-width: 12rem">
                                        <template #body="exchangeProps">
                                            <div class="text-sm">
                                                {{ formatDate(exchangeProps.data.created_at) }}
                                            </div>
                                        </template>
                                    </Column>
                                </DataTable>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </template>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/layout/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Espera from '@/components/Espera.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
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
const expandedRows = ref([]);

const filters = ref({
    'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
});

// Computed property para agrupar movimientos por inversor
const groupedExchanges = computed(() => {
    const grouped = {};
    
    exchanges.value.forEach(exchange => {
        const investorId = exchange.investor.id || exchange.investor.email;
        
        if (!grouped[investorId]) {
            grouped[investorId] = {
                investorId: investorId,
                name: exchange.investor.name,
                email: exchange.investor.email,
                exchanges: [],
                totals: {},
                totalMovements: 0,
                lastMovement: exchange.created_at
            };
        }
        
        grouped[investorId].exchanges.push(exchange);
        grouped[investorId].totalMovements++;
        
        // Calcular totales por moneda
        const currency = exchange.currency;
        if (!grouped[investorId].totals[currency]) {
            grouped[investorId].totals[currency] = 0;
        }
        
        // Sumar o restar según el tipo de movimiento
        const amount = parseFloat(exchange.amount);
        if (exchange.type === 'exchange_up') {
            grouped[investorId].totals[currency] += amount;
        } else if (exchange.type === 'exchange_down') {
            grouped[investorId].totals[currency] -= amount;
        }
        
        // Actualizar último movimiento
        if (new Date(exchange.created_at) > new Date(grouped[investorId].lastMovement)) {
            grouped[investorId].lastMovement = exchange.created_at;
        }
    });
    
    // Ordenar movimientos dentro de cada grupo por fecha (más reciente primero)
    Object.values(grouped).forEach(group => {
        group.exchanges.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    });
    
    return Object.values(grouped);
});

// Expandir todas las filas
const expandAll = () => {
    expandedRows.value = groupedExchanges.value.slice();
};

// Contraer todas las filas
const collapseAll = () => {
    expandedRows.value = [];
};

// Cargar movimientos de intercambio desde la API
const loadExchangeRates = async () => {
    loadingData.value = true;
    try {
        const response = await fetch('/exchange/list'); // Cambiar la URL según tu API
        const data = await response.json();

        if (data.success) {
            exchanges.value = data.data.data; // Acceder a los datos dentro de data.data
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