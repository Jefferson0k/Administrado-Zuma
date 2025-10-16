<template>
    <div>
        <DataTable ref="dt" v-model:selection="selectedCompanies" :value="companies" dataKey="id" :paginator="true"
            :rows="rows" :totalRecords="totalRecords" :first="first" :loading="loading"
            :rowsPerPageOptions="[15, 25, 50, 100]"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} empresas" @page="onPage" scrollable
            scrollHeight="574px" class="p-datatable-sm" :lazy="true" :sortField="sortField" :sortOrder="sortOrder"
            :sortMode="'single'" @sort="onSort">
            <!-- Header -->
            <template #header>
                <div class="flex flex-col gap-4">
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">
                            Empresas
                            <Tag severity="contrast" :value="totalRecords" />
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <Select v-model="selectedSector" :options="sectors" optionLabel="name" optionValue="id"
                                placeholder="Todos los sectores" :loading="loadingSectors" showClear
                                @change="onSectorChange" class="w-15" />
                            <Select v-model="selectedSubsector" :options="subsectors" optionLabel="name"
                                optionValue="id" placeholder="Todos los subsectores" :loading="loadingSubsectors"
                                :disabled="!selectedSector" showClear @change="onSubsectorChange" class="w-15" />
                            <Select v-model="selectedRisk" :options="riskOptions" optionLabel="label"
                                optionValue="value" placeholder="Todas las calificaciones" showClear
                                @change="onRiskChange" class="w-15" />
                            <IconField>
                                <InputIcon>
                                    <i class="pi pi-search" />
                                </InputIcon>
                                <InputText v-model="globalFilter" @input="onGlobalSearch"
                                    placeholder="Buscar por RUC, nombre, razón social..." />
                            </IconField>
                            <Button icon="pi pi-filter-slash" outlined severity="contrast" @click="clearFilters" />
                            <Button icon="pi pi-refresh" severity="contrast" outlined rounded aria-label="Refresh"
                                @click="refreshData" />
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

            <Column field="nuevonombreempresa" header="Nuevo nombre de empresa" sortable style="min-width: 12rem">
                <template #body="{ data }">
                    <div class="max-w-xs">
                        <span class="truncate block font-medium" :title="data.nuevonombreempresa">{{
                            data.nuevonombreempresa }}</span>
                    </div>
                </template>
            </Column>

            <Column field="risk" header="Calificación" sortable style="min-width: 6rem"></Column>

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

            <Column style="min-width: 2rem">
                <template #body="slotProps">
                    <div class="flex gap-1">
                        <Button icon="pi pi-eye" outlined rounded class="mr-1" severity="info" size="small"
                            @click="showCompanyDetails(slotProps.data)" v-tooltip.top="'Ver detalles'" />
                        <Button icon="pi pi-pencil" outlined rounded class="mr-1" size="small"
                            @click="editCompany(slotProps.data)" v-tooltip.top="'Editar'" />
                        <!--<Button
                            icon="pi pi-trash"
                            outlined
                            rounded
                            severity="danger"
                            size="small"
                            @click="confirmDelete(slotProps.data)"
                            v-tooltip.top="'Eliminar'"
                        />-->
                    </div>
                </template>
            </Column>
        </DataTable>

        <!-- Diálogos -->
        <ShowCompany v-model:visible="viewDialog" :company="selectedCompany" />
        <UpdateCompany v-model:visible="editDialog" :company="selectedCompany" @updated="onCompanyUpdated" />
        <DeleteCompany v-model:visible="deleteDialog" :company="selectedCompany" @deleted="onCompanyDeleted" />
    </div>
</template>

<script setup>
/** DEBUG SWITCHES **/
const DEBUG = true;        // logs en consola
const API_DEBUG = false;   // añade ?debug=1 al backend

import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { debounce } from 'lodash';
import { useToast } from 'primevue/usetoast';

// PrimeVue
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';

// Custom
import ShowCompany from './showCompany.vue';
import UpdateCompany from './updateCompany.vue';
import DeleteCompany from './deleteCompany.vue';

/** Axios interceptors (once) **/
if (DEBUG && typeof window !== 'undefined' && !window.__AXIOS_LOG_INSTALLED__) {
    axios.interceptors.request.use((config) => {
        console.groupCollapsed('%cAXIOS REQ', 'color:#0aa;font-weight:bold');
        console.log('URL:', config.url);
        console.log('Method:', config.method);
        console.log('Params:', config.params);
        console.log('Data:', config.data);
        console.groupEnd();
        return config;
    }, (error) => {
        console.error('AXIOS REQ ERROR:', error);
        return Promise.reject(error);
    });

    axios.interceptors.response.use((response) => {
        console.groupCollapsed('%cAXIOS RESP', 'color:#0a0;font-weight:bold');
        console.log('URL:', response.config?.url);
        console.log('Status:', response.status);
        console.log('Data:', response.data);
        console.groupEnd();
        return response;
    }, (error) => {
        console.groupCollapsed('%cAXIOS RESP ERROR', 'color:#a00;font-weight:bold');
        console.log('URL:', error.config?.url);
        console.log('Status:', error.response?.status);
        console.log('Data:', error.response?.data);
        console.log('Message:', error.message);
        console.groupEnd();
        return Promise.reject(error);
    });

    window.__AXIOS_LOG_INSTALLED__ = true;
}

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

// Ordenamiento
const sortField = ref(null); // 'document' | 'business_name' | 'name' | 'risk' | 'sectornom' | 'subsectornom' | 'creacion'
const sortOrder = ref(null); // 1 (asc) | -1 (desc)

// Props
const props = defineProps({
    refresh: { type: Number, required: true }
});

// Watch refresh
watch(() => props.refresh, () => refreshData());

// Helpers calificación
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
        toast.add({ severity: 'error', summary: 'Error', detail: 'Error al cargar los sectores', life: 5000 });
        sectors.value = [];
    } finally {
        loadingSectors.value = false;
    }
};

// Cargar subsectores
const loadSubsectors = async (sectorId) => {
    if (!sectorId) { subsectors.value = []; return; }
    loadingSubsectors.value = true;
    try {
        const response = await axios.get(`/subsectors/search/${sectorId}`);
        subsectors.value = response.data.data || [];
    } catch (error) {
        console.error('Error al cargar subsectores:', error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Error al cargar los subsectores', life: 5000 });
        subsectors.value = [];
    } finally {
        loadingSubsectors.value = false;
    }
};

// Handlers filtros
const onSectorChange = (event) => {
    selectedSubsector.value = null;
    if (event.value) loadSubsectors(event.value);
    else subsectors.value = [];
    applyFilters();
};
const onSubsectorChange = () => applyFilters();
const onRiskChange = () => applyFilters();

// Debounced filter apply
const applyFilters = debounce(() => {
    first.value = 0;
    loadCompanies();
}, 300);

// Limpiar filtros
const clearFilters = () => {
    globalFilter.value = '';
    selectedSector.value = null;
    selectedSubsector.value = null;
    selectedRisk.value = null;
    subsectors.value = [];
    first.value = 0;
    sortField.value = null;
    sortOrder.value = null;
    loadCompanies();
};

// Diálogos
const showCompanyDetails = async (company) => {
    try {
        loading.value = true;
        const response = await axios.get(`/companies/${company.id}`);
        selectedCompany.value = response.data.data;
        viewDialog.value = true;
    } catch (error) {
        console.error("Error al cargar detalles:", error);
        toast.add({ severity: "error", summary: "Error", detail: "No se pudieron cargar los detalles de la empresa", life: 5000 });
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
        toast.add({ severity: "error", summary: "Error", detail: "No se pudieron cargar los detalles de la empresa", life: 5000 });
    } finally {
        loading.value = false;
    }
};
const confirmDelete = (company) => {
    selectedCompany.value = { ...company };
    deleteDialog.value = true;
};

// Eventos hijos
const onCompanyUpdated = () => loadCompanies();
const onCompanyDeleted = () => loadCompanies();

// Cargar empresas
const loadCompanies = async () => {
    loading.value = true;
    const currentPage = Math.floor(first.value / rows.value) + 1;

    const params = {
        search: globalFilter.value || '',
        page: currentPage,
        per_page: rows.value
    };
    if (selectedSector.value !== null) params.sector_id = selectedSector.value;
    if (selectedSubsector.value !== null) params.subsector_id = selectedSubsector.value;
    if (selectedRisk.value !== null) params.risk = selectedRisk.value;

    if (API_DEBUG) params.debug = 1;

    if (DEBUG) {
        console.groupCollapsed('%cLOAD COMPANIES PARAMS', 'color:#06c;font-weight:bold');
        console.log(params);
        console.groupEnd();
    }

    try {
        const response = await axios.get('/companies', { params });
        companies.value = response.data.data || [];
        totalRecords.value = response.data.total || 0;

        // 🔽 Client-side sort of the CURRENT PAGE ONLY
        if (sortField.value && sortOrder.value) {
            const dir = sortOrder.value === 1 ? 1 : -1;
            const field = String(sortField.value);

            companies.value = [...companies.value].sort((a, b) => {
                const va = a?.[field];
                const vb = b?.[field];

                // Try number compare first
                const na = typeof va === 'string' ? Number(va) : va;
                const nb = typeof vb === 'string' ? Number(vb) : vb;
                const bothNums = Number.isFinite(na) && Number.isFinite(nb);

                if (bothNums) return (na - nb) * dir;

                // Fallback to localeCompare on strings
                const sa = (va ?? '').toString();
                const sb = (vb ?? '').toString();
                return sa.localeCompare(sb, undefined, { numeric: true, sensitivity: 'base' }) * dir;
            });
        }

    } catch (error) {
        console.error('Error al cargar empresas:', error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Error al cargar las empresas', life: 5000 });
        companies.value = [];
        totalRecords.value = 0;
    } finally {
        loading.value = false;
    }
};

// Búsqueda global
const onGlobalSearch = debounce(() => {
    first.value = 0;
    loadCompanies();
}, 800);

// Paginación
const onPage = (event) => {
    if (DEBUG) {
        console.groupCollapsed('%cDT PAGE', 'color:#884;font-weight:bold');
        console.log('event:', event);
        console.groupEnd();
    }
    first.value = event.first;
    rows.value = event.rows;
    loadCompanies();
};

// Refresh
const refreshData = () => {
    globalFilter.value = '';
    selectedSector.value = null;
    selectedSubsector.value = null;
    selectedRisk.value = null;
    subsectors.value = [];
    first.value = 0;
    rows.value = 15;
    sortField.value = null;
    sortOrder.value = null;
    loadCompanies();
};

// Exportar
const exportToExcel = async () => {
    try {
        loading.value = true;
        const params = { search: globalFilter.value || '' };
        if (selectedSector.value !== null) params.sector_id = selectedSector.value;
        if (selectedSubsector.value !== null) params.subsector_id = selectedSubsector.value;
        if (selectedRisk.value !== null) params.risk = selectedRisk.value;
        if (sortField.value) {
            params.sort_field = sortField.value;
            params.sort_order = sortOrder.value === 1 ? 'asc' : 'desc';
        }
        if (API_DEBUG) params.debug = 1;

        const response = await axios.get('/companies/export-excel', {
            params,
            responseType: 'blob',
            headers: { 'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }
        });

        const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `empresas_${new Date().toISOString().split('T')[0]}.xlsx`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);

        toast.add({ severity: 'success', summary: 'Éxito', detail: 'El archivo Excel se ha descargado correctamente', life: 3000 });
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

const exportCSV = () => dt.value.exportCSV();

// Sort handler
const onSort = (event) => {
    if (DEBUG) {
        console.groupCollapsed('%cDT SORT', 'color:#884;font-weight:bold');
        console.log('event.sortField:', event.sortField);
        console.log('event.sortOrder:', event.sortOrder); // 1 asc, -1 desc
        console.groupEnd();
    }
    sortField.value = event.sortField;
    sortOrder.value = event.sortOrder;
    first.value = 0;
    loadCompanies();
};

onMounted(() => {
    loadSectors();
    loadCompanies();
});

defineExpose({ exportCSV, exportToExcel });
</script>
