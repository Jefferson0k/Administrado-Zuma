<template>
    <DataTable
        ref="dt"
        :value="investments"
        dataKey="id"
        v-model:selection="selectedInvestments"
        :paginator="true"
        :rows="rowsPerPage"
        :totalRecords="totalRecords"
        :first="(currentPage - 1) * rowsPerPage"
        :loading="loading"
        :rowsPerPageOptions="[5, 10, 20, 50]"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} inversiones"
        @page="onPage"
        scrollable
        scrollHeight="574px"
        class="p-datatable-sm"
    >
        <!-- Header -->
        <template #header>
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <h4 class="m-0">
                    Inversiones
                    <Tag severity="contrast" :value="totalRecords" />
                </h4>
                <div class="flex flex-wrap gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <MultiSelect
                            v-model="selectedFilters"
                            :options="filterOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Selecciona filtros..."
                            :maxSelectedLabels="3"
                            class="md:w-20rem w-full"
                        />
                    </div>

                    <div class="flex flex-wrap items-end gap-3">
                        <div class="flex-column flex gap-2">
                            <IconField>
                                <InputIcon>
                                    <i class="pi pi-search" />
                                </InputIcon>
                                <InputText
                                    v-model="filters.razon_social"
                                    @input="onFilterSearch"
                                    placeholder="Buscar por razón social..."
                                    class="md:w-20rem w-full"
                                />
                            </IconField>
                        </div>

                        <!-- Filtro por Moneda -->
                        <div v-if="selectedFilters.includes('moneda')" class="flex-column flex gap-2">
                            <Select
                                v-model="filters.currency"
                                :options="currencyOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Selecciona moneda"
                                @change="onFilterSearch"
                                class="md:w-12rem w-full"
                            />
                        </div>

                        <!-- Filtro por Estado -->
                        <div v-if="selectedFilters.includes('estado')" class="flex-column flex gap-2">
                            <MultiSelect
                                v-model="filters.status"
                                :options="statusOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Selecciona estados"
                                @change="onFilterSearch"
                                :maxSelectedLabels="2"
                                class="md:w-16rem w-full"
                            />
                        </div>

                        <!-- Filtro por Código -->
                        <div v-if="selectedFilters.includes('codigo')" class="flex-column flex gap-2">
                            <InputText
                                v-model="filters.codigo"
                                @input="onFilterSearch"
                                placeholder="Buscar por código..."
                                class="md:w-20rem w-full"
                            />
                        </div>

                        <!-- Botones de acción -->
                        <div class="flex gap-2">
                            <Button icon="pi pi-filter-slash" severity="secondary" outlined @click="clearFilters" />
                        </div>
                    </div>
                    <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" severity="contrast" @click="loadInvestments" />
                </div>
            </div>
        </template>

        <!-- Columns -->
        <Column selectionMode="multiple" style="width: 1rem" />

        <Column field="codigo" header="Código" sortable style="min-width: 7rem" />
        <Column field="inversionista" header="Inversionista" sortable style="min-width: 25rem" />
        <Column field="document" header="DNI" sortable style="min-width: 10rem" />
        <Column field="company" header="Razón Social" sortable style="min-width: 30rem" />
        <Column field="amount" header="Monto" sortable style="min-width: 10rem">
            <template #body="{ data }"> {{ data.currency }} {{ formatCurrency(data.amount, data.currency) }} </template>
        </Column>
        <Column field="return" header="Retorno" sortable style="min-width: 8rem">
            <template #body="{ data }"> {{ data.currency }} {{ formatCurrency(data.return, data.currency) }} </template>
        </Column>
        <Column field="rate" header="Tasa" sortable style="min-width: 5rem">
            <template #body="{ data }"> {{ data.rate }}% </template>
        </Column>
        <Column field="currency" header="Moneda" sortable style="min-width: 5rem" />
        <Column field="due_date" header="Fecha Vencimiento" sortable style="min-width: 12rem" />

        <!-- Estado con Tag -->
        <Column field="status" header="Estado" sortable style="min-width: 7rem">
            <template #body="{ data }">
                <Tag :value="getStatusLabel(data.status)" :severity="getStatusSeverity(data.status)" />
            </template>
        </Column>

        <Column field="creacion" header="Creación" sortable style="min-width: 15rem" />
    </DataTable>
</template>

<script setup lang="ts">
import axios from 'axios';
import { debounce } from 'lodash';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { computed, onMounted, ref } from 'vue';

const investments = ref<any[]>([]);
const selectedInvestments = ref<any[]>([]);
const loading = ref(false);
const totalRecords = ref(0);

const rowsPerPage = ref(10);
const currentPage = ref(1);

// Filtros disponibles (removido 'monto')
const filterOptions = ref([
    { label: 'Razón Social', value: 'razon_social' },
    { label: 'Moneda', value: 'moneda' },
    { label: 'Estado', value: 'estado' },
    { label: 'Código', value: 'codigo' },
]);

// Filtros seleccionados
const selectedFilters = ref(['razon_social']);

// Valores de filtros (removidos monto_min y monto_max)
const filters = ref({
    razon_social: '',
    currency: null,
    status: [],
    codigo: '',
});

// Opciones de estado
const statusOptions = ref([
    { label: 'Inactivo', value: 'inactive' },
    { label: 'Activo', value: 'active' },
    { label: 'Vencido', value: 'expired' },
    { label: 'Judicializado', value: 'judicialized' },
    { label: 'Reprogramado', value: 'reprogramed' },
    { label: 'Pagado', value: 'paid' },
    { label: 'Cancelado', value: 'canceled' },
    { label: 'En Standby', value: 'daStandby' },
]);

// Opciones de moneda
const currencyOptions = ref([
    { label: 'Todas las monedas', value: null },
    { label: 'Soles (PEN)', value: 'PEN' },
    { label: 'Dólares (USD)', value: 'USD' },
]);

// Computed para verificar si hay filtros activos
const hasActiveFilters = computed(() => {
    return filters.value.razon_social || filters.value.currency || (filters.value.status && filters.value.status.length > 0);
});

// Cargar inversiones
const loadInvestments = async (event: any = {}) => {
    loading.value = true;
    const page = event.page != null ? event.page + 1 : currentPage.value;
    const perPage = event.rows != null ? Number(event.rows) : rowsPerPage.value;

    try {
        const params: any = {
            page,
            per_page: perPage,
        };

        // Agregar filtros activos
        if (filters.value.razon_social) {
            params.razon_social = filters.value.razon_social;
        }

        if (filters.value.currency) {
            params.currency = filters.value.currency;
        }

        // FIX: Enviar cada estado como un parámetro separado en lugar de array
        if (filters.value.status && filters.value.status.length > 0) {
            // Si solo hay un estado, enviarlo directamente
            if (filters.value.status.length === 1) {
                params.status = filters.value.status[0];
            } else {
                // Si hay múltiples estados, enviarlos como string separado por comas
                params.status = filters.value.status.join(',');
            }
        }

        // Agregar filtro por código
        if (filters.value.codigo) {
            params.codigo = filters.value.codigo;
        }

        const response = await axios.get('/investment/all', { params });

        investments.value = response.data.data;
        totalRecords.value = response.data.total;
        currentPage.value = page;
        rowsPerPage.value = perPage;
    } catch (error) {
        console.error('Error al cargar inversiones:', error);
    } finally {
        loading.value = false;
    }
};

// Paginación del DataTable
const onPage = (event: any) => {
    loadInvestments(event);
};

// Búsqueda con filtros con debounce
const onFilterSearch = debounce(() => {
    currentPage.value = 1;
    loadInvestments();
}, 500);

// Limpiar filtros
const clearFilters = () => {
    filters.value = {
        razon_social: '',
        currency: null,
        status: [],
        codigo: '',
    };
    selectedFilters.value = ['razon_social'];
    loadInvestments();
};

// Función para obtener etiqueta del estado
function getStatusLabel(status: string) {
    const statusLabels = {
        inactive: 'Inactivo',
        active: 'Activo',
        expired: 'Vencido',
        judicialized: 'Judicializado',
        reprogramed: 'Reprogramado',
        paid: 'Pagado',
        canceled: 'Cancelado',
        daStandby: 'En Standby',
    };
    return statusLabels[status] || status;
}

// Función para obtener severidad del estado
function getStatusSeverity(status: string) {
    switch (status) {
        case 'inactive':
            return 'secondary';
        case 'active':
            return 'success';
        case 'expired':
            return 'danger';
        case 'judicialized':
            return 'warn';
        case 'reprogramed':
            return 'info';
        case 'paid':
            return 'contrast';
        case 'canceled':
            return 'danger';
        case 'daStandby':
            return 'warn';
        default:
            return 'secondary';
    }
}

// Función para formatear moneda
function formatCurrency(amount: string | number, currency: string) {
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    return new Intl.NumberFormat('es-PE', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(num);
}

onMounted(() => {
    loadInvestments();
});
</script>
