<template>
    <div>
        <DataTable ref="dt" v-model:selection="selectedCompanies" :value="companies" dataKey="id" :paginator="true" :rows="rows"
            :totalRecords="totalRecords" :first="first" :loading="loading" :rowsPerPageOptions="[15, 25, 50, 100]"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} empresas" @page="onPage" scrollable
            scrollHeight="574px" class="p-datatable-sm" :lazy="true">
            
            <!-- Header -->
            <template #header>
                <div class="flex flex-col gap-4">
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">
                            Empresas
                            <Tag severity="contrast" :value="totalRecords" />
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <Select 
                                v-model="selectedSector" 
                                :options="sectors" 
                                optionLabel="name" 
                                optionValue="id"
                                placeholder="Todos los sectores"
                                :loading="loadingSectors"
                                showClear
                                @change="onSectorChange"
                                class="w-15"
                            />
                            <Select 
                                v-model="selectedSubsector" 
                                :options="subsectors" 
                                optionLabel="name" 
                                optionValue="id"
                                placeholder="Todos los subsectores"
                                :loading="loadingSubsectors"
                                :disabled="!selectedSector"
                                showClear
                                @change="onSubsectorChange"
                                class="w-15"
                            />
                            <Select 
                                v-model="selectedRisk" 
                                :options="riskOptions" 
                                optionLabel="label" 
                                optionValue="value"
                                placeholder="Todas las calificaciones"
                                showClear
                                @change="onRiskChange"
                                class="w-15"
                            />
                            <IconField>
                                <InputIcon>
                                    <i class="pi pi-search" />
                                </InputIcon>
                                <InputText v-model="globalFilter" @input="onGlobalSearch"
                                    placeholder="Buscar por RUC, nombre, razón social..." />
                            </IconField>
                            <Button 
                                icon="pi pi-filter-slash" 
                                outlined 
                                severity="contrast"
                                @click="clearFilters"
                            />
                            <Button icon="pi pi-refresh" severity="contrast" outlined rounded aria-label="Refresh" @click="refreshData" />
                        </div>
                    </div>
                </div>
            </template>

            <!-- Columns -->
            <Column selectionMode="multiple" style="width: 1rem" />

            <Column field="document" header="RUC" sortable style="min-width: 8rem">
                <template #body="{ data }">
                    <span class="font-mono text-sm">{{ data.document }}</span>
                </template>
            </Column>

            <Column field="business_name" header="Razón Social" sortable style="min-width: 15rem">
                <template #body="{ data }">
                    <div class="max-w-xs">
                        <span class="truncate block" :title="data.business_name">{{ data.business_name }}</span>
                    </div>
                </template>
            </Column>

            <Column field="name" header="Nombre Comercial" sortable style="min-width: 12rem">
                <template #body="{ data }">
                    <div class="max-w-xs">
                        <span class="truncate block font-medium" :title="data.name">{{ data.name }}</span>
                    </div>
                </template>
            </Column>

            <Column field="risk" header="Calificación" sortable style="min-width: 6rem">
                <template #body="{ data }">
                    <Tag :value="getRiskLabel(data.risk)" :severity="getRiskSeverity(data.risk)"
                        class="px-3 py-1 rounded-lg font-bold" />
                </template>
            </Column>

            <Column field="sectornom" header="Sector" sortable style="min-width: 10rem">
                <template #body="{ data }">
                    <span class="text-blue-600 font-medium">{{ data.sectornom }}</span>
                </template>
            </Column>

            <Column field="subsectornom" header="Subsector" sortable style="min-width: 12rem">
                <template #body="{ data }">
                    <span>{{ data.subsectornom }}</span>
                </template>
            </Column>

            <Column field="creacion" header="Fecha Creación" sortable style="min-width: 10rem">
                <template #body="{ data }">
                    <span class="text-sm">{{ data.creacion }}</span>
                </template>
            </Column>

            <Column style="min-width: 8rem">
                <template #body="slotProps">
                    <div class="flex gap-1">
                        <Button icon="pi pi-eye" outlined rounded class="mr-1" severity="info" size="small"
                            @click="showCompanyDetails(slotProps.data)" v-tooltip.top="'Ver detalles'" />
                        <Button icon="pi pi-pencil" outlined rounded class="mr-1" size="small"
                            @click="editCompany(slotProps.data)" v-tooltip.top="'Editar'" />
                        <Button icon="pi pi-trash" outlined rounded severity="danger" size="small"
                            @click="confirmDelete(slotProps.data)" v-tooltip.top="'Eliminar'" />
                    </div>
                </template>
            </Column>
        </DataTable>

        <!-- Componentes de diálogos -->
        <ShowCompany 
            v-model:visible="viewDialog" 
            :company="selectedCompany" 
        />
        
        <UpdateCompany 
            v-model:visible="editDialog" 
            :company="selectedCompany" 
            @updated="onCompanyUpdated" 
        />
        
        <DeleteCompany 
            v-model:visible="deleteDialog" 
            :company="selectedCompany" 
            @deleted="onCompanyDeleted" 
        />
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { debounce } from 'lodash';
import { useToast } from 'primevue/usetoast';

// Componentes PrimeVue
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';
// Componentes personalizados
import ShowCompany from './showCompany.vue';
import UpdateCompany from './updateCompany.vue';
import DeleteCompany from './deleteCompany.vue';

const toast = useToast();
const dt = ref();
const companies = ref([]);
const selectedCompanies = ref([]);
const loading = ref(false);
const totalRecords = ref(0);
const globalFilter = ref('');
const first = ref(0);
const rows = ref(15);
const viewDialog = ref(false);
const editDialog = ref(false);
const deleteDialog = ref(false);
const selectedCompany = ref(null);

// Filtros
const sectors = ref([]);
const subsectors = ref([]);
const selectedSector = ref(null);
const selectedSubsector = ref(null);
const selectedRisk = ref(null);
const loadingSectors = ref(false);
const loadingSubsectors = ref(false);

// Opciones de riesgo
const riskOptions = ref([
    { label: 'A', value: 0 },
    { label: 'B', value: 1 },
    { label: 'C', value: 2 },
    { label: 'D', value: 3 },
    { label: 'E', value: 4 }
]);

const props = defineProps({
    refresh: { type: Number, required: true }
});

// Actualiza la lista si cambia la prop refresh
watch(() => props.refresh, () => refreshData());

// Funciones auxiliares de calificación
const getRiskSeverity = (risk) => {
    const riskNum = parseInt(risk);
    return ['success', 'info', 'warn', 'danger', 'secondary'][riskNum] || 'secondary';
};

const getRiskLabel = (risk) => {
    const labels = ['A', 'B', 'C', 'D', 'E'];
    return labels[parseInt(risk)] || 'N/A';
};

// Cargar sectores
const loadSectors = async () => {
    loadingSectors.value = true;
    try {
        const response = await axios.get('/sectors/search');
        sectors.value = response.data.data || [];
    } catch (error) {
        console.error('Error al cargar sectores:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error al cargar los sectores',
            life: 5000
        });
        sectors.value = [];
    } finally {
        loadingSectors.value = false;
    }
};

// Cargar subsectores según el sector seleccionado
const loadSubsectors = async (sectorId) => {
    if (!sectorId) {
        subsectors.value = [];
        return;
    }
    
    loadingSubsectors.value = true;
    try {
        const response = await axios.get(`/subsectors/search/${sectorId}`);
        subsectors.value = response.data.data || [];
    } catch (error) {
        console.error('Error al cargar subsectores:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error al cargar los subsectores',
            life: 5000
        });
        subsectors.value = [];
    } finally {
        loadingSubsectors.value = false;
    }
};

// Eventos de cambio de filtros
const onSectorChange = (event) => {
    selectedSubsector.value = null; // Limpiar subsector cuando cambie el sector
    if (event.value) {
        loadSubsectors(event.value);
    } else {
        subsectors.value = [];
    }
    applyFilters();
};

const onSubsectorChange = () => {
    applyFilters();
};

const onRiskChange = () => {
    applyFilters();
};

// Aplicar filtros con debounce
const applyFilters = debounce(() => {
    first.value = 0; // Resetear a la primera página
    loadCompanies();
}, 300);

// Limpiar todos los filtros
const clearFilters = () => {
    globalFilter.value = '';
    selectedSector.value = null;
    selectedSubsector.value = null;
    selectedRisk.value = null;
    subsectors.value = [];
    first.value = 0;
    loadCompanies();
};

// Acciones para mostrar diálogos
const showCompanyDetails = async (company) => {
    try {
        loading.value = true;
        const response = await axios.get(`/companies/${company.id}`);
        selectedCompany.value = response.data.data;
        viewDialog.value = true;
    } catch (error) {
        console.error("Error al cargar detalles:", error);
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudieron cargar los detalles de la empresa",
            life: 5000
        });
    } finally {
        loading.value = false;
    }
};

const editCompany = async (company) => {
    try {
        loading.value = true;
        const response = await axios.get(`/companies/${company.id}`);
        selectedCompany.value = response.data.data;
        editDialog.value = true;
    } catch (error) {
        console.error("Error al cargar detalles:", error);
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudieron cargar los detalles de la empresa",
            life: 5000
        });
    } finally {
        loading.value = false;
    }
};

const confirmDelete = (company) => {
    selectedCompany.value = { ...company };
    deleteDialog.value = true;
};

// Eventos de los componentes hijos
const onCompanyUpdated = () => {
    loadCompanies();
};

const onCompanyDeleted = () => {
    loadCompanies();
};

// Cargar empresas desde API (paginado y filtrado)
const loadCompanies = async () => {
    loading.value = true;
    
    // Calcular la página actual basada en first y rows
    const currentPage = Math.floor(first.value / rows.value) + 1;
    
    try {
        const params = {
            search: globalFilter.value || '',
            page: currentPage,
            per_page: rows.value
        };

        // Agregar filtros si están seleccionados
        if (selectedSector.value !== null) {
            params.sector_id = selectedSector.value;
        }
        
        if (selectedSubsector.value !== null) {
            params.subsector_id = selectedSubsector.value;
        }
        
        if (selectedRisk.value !== null) {
            params.risk = selectedRisk.value;
        }

        const response = await axios.get('/companies', { params });
        
        console.log('Response:', response.data); // Para debugging
        
        companies.value = response.data.data || [];
        totalRecords.value = response.data.total || 0;
        
    } catch (error) {
        console.error('Error al cargar empresas:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error al cargar las empresas',
            life: 5000
        });
        companies.value = [];
        totalRecords.value = 0;
    } finally {
        loading.value = false;
    }
};

// Función para búsqueda con debounce
const onGlobalSearch = debounce(() => {
    console.log('Buscando:', globalFilter.value); // Para debugging
    first.value = 0; // Resetear a la primera página
    loadCompanies();
}, 800);

// Evento de paginación
const onPage = (event) => {
    console.log('Page event:', event); // Para debugging
    first.value = event.first;
    rows.value = event.rows;
    loadCompanies();
};

// Función para refrescar datos
const refreshData = () => {
    globalFilter.value = '';
    selectedSector.value = null;
    selectedSubsector.value = null;
    selectedRisk.value = null;
    subsectors.value = [];
    first.value = 0;
    rows.value = 15;
    loadCompanies();
};

const exportToExcel = async () => {
    try {
        loading.value = true;
        const params = {
            search: globalFilter.value || '',
        };
        if (selectedSector.value !== null) {
            params.sector_id = selectedSector.value;
        }
        
        if (selectedSubsector.value !== null) {
            params.subsector_id = selectedSubsector.value;
        }
        
        if (selectedRisk.value !== null) {
            params.risk = selectedRisk.value;
        }

        const response = await axios.get('/companies/export-excel', {
            params: params,
            responseType: 'blob',
            headers: {
                'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            }
        });
        const blob = new Blob([response.data], {
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `empresas_${new Date().toISOString().split('T')[0]}.xlsx`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'El archivo Excel se ha descargado correctamente',
            life: 3000
        });

    } catch (error) {
        console.error('Error al exportar:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'No se pudo generar el archivo Excel',
            life: 5000
        });
    } finally {
        loading.value = false;
    }
};

const exportCSV = () => {
    dt.value.exportCSV();
};

onMounted(() => {
    loadSectors();
    loadCompanies();
});

defineExpose({ exportCSV, exportToExcel });
</script>