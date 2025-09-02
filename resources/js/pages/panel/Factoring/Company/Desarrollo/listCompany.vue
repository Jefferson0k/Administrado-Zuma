<template>
    <div>
        <DataTable  ref="dt" v-model:selection="selectedCompanies" :value="companies" dataKey="id" :paginator="true" :rows="rows"
            :totalRecords="totalRecords" :first="first" :loading="loading" :rowsPerPageOptions="[25, 50, 100]"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} empresas" @page="onPage" scrollable
            scrollHeight="574px" class="p-datatable-sm">
            
            <!-- Header -->
            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <h4 class="m-0">
                        Empresas
                        <Tag severity="contrast" :value="totalRecords" />
                    </h4>
                    <div class="flex flex-wrap gap-2">
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="globalFilter" @input="onGlobalSearch"
                                placeholder="Buscar por RUC, nombre, razón social..." />
                        </IconField>
                        <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" @click="loadCompanies" />
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
const rows = ref(25);
const viewDialog = ref(false);
const editDialog = ref(false);
const deleteDialog = ref(false);
const selectedCompany = ref(null);

const props = defineProps({
    refresh: { type: Number, required: true }
});

// Actualiza la lista si cambia la prop refresh
watch(() => props.refresh, () => loadCompanies());

// Funciones auxiliares de calificación
const getRiskSeverity = (risk) => {
    const riskNum = parseInt(risk);
    return ['success', 'info', 'warn', 'danger', 'secondary'][riskNum] || 'secondary';
};

const getRiskLabel = (risk) => {
    const labels = ['A', 'B', 'C', 'D', 'E'];
    return labels[parseInt(risk)] || 'N/A';
};

// Acciones para mostrar diálogos
const showCompanyDetails = async (company) => {
    try {
        loading.value = true;
        const response = await axios.get(`/companies/${company.id}`);
        selectedCompany.value = response.data.data; // 👈 aquí los datos del backend
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
const loadCompanies = async (event = { page: 0, rows: 25 }) => {
    loading.value = true;
    const page = event.page ? event.page + 1 : 1;
    const perPage = Number(event.rows) || 25;

    try {
        const response = await axios.get('/companies', {
            params: {
                search: globalFilter.value,
                page,
                perPage
            }
        });
        companies.value = response.data.data;
        totalRecords.value = response.data.total;
        first.value = (page - 1) * perPage;
    } catch (error) {
        console.error('Error al cargar empresas:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error al cargar las empresas',
            life: 5000
        });
    } finally {
        loading.value = false;
    }
};

const onGlobalSearch = debounce(() => {
    first.value = 0;
    loadCompanies();
}, 500);

const onPage = (event) => {
    loadCompanies(event);
};

const exportCSV = () => {
    dt.value.exportCSV();
};

onMounted(() => {
    loadCompanies();
});
defineExpose({ exportCSV });
</script>