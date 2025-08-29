<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import axios from 'axios';
import Tag from 'primevue/tag';
import { debounce } from 'lodash';
// import deleteCompany from './deleteCompany.vue';
// import updateCompany from './updateCompany.vue';

const dt = ref();
const companies = ref([]);
const selectedCompanies = ref();
const loading = ref(false);
const globalFilterValue = ref('');
const deleteDialog = ref(false);
const company = ref({});
const selectedCompanyId = ref(null);
const updateDialog = ref(false);
const contadorEmpresas = ref(0);

const rowsPerPage = ref(100);
const currentPage = ref(1);

const props = defineProps({
    refresh: {
        type: Number,
        required: true
    }
});

watch(() => props.refresh, () => {
    loadCompanies();
});

// Función para obtener el color de la calificación
const getRiskSeverity = (risk) => {
    const riskNum = parseInt(risk);
    switch (riskNum) {
        case 0: return 'success';  // A - Verde
        case 1: return 'info';     // B - Azul
        case 2: return 'warn';     // C - Amarillo
        case 3: return 'danger';   // D - Rojo
        case 4: return 'secondary'; // E - Gris
        default: return 'secondary';
    }
};

// Función para obtener la letra de la calificación
const getRiskLabel = (risk) => {
    const riskNum = parseInt(risk);
    const labels = ['A', 'B', 'C', 'D', 'E'];
    return labels[riskNum] || 'N/A';
};

function editCompany(company) {
    selectedCompanyId.value = company.id;
    updateDialog.value = true;
}

function confirmDelete(companyData) {
    company.value = companyData;
    deleteDialog.value = true;
}

function handleCompanyUpdated() {
    loadCompanies();
}

function handleCompanyDeleted() {
    loadCompanies();
}

// Función para ver detalles de la empresa
function verDetalles(company) {
    router.visit(`/companies/${company.id}`);
}

const loadCompanies = async () => {
    loading.value = true;
    try {
        const params = {
            search: globalFilterValue.value,
        };
        const response = await axios.get('/companies', { params });
        companies.value = response.data.data;
        contadorEmpresas.value = response.data.total;
        currentPage.value = 1;
    } catch (error) {
        console.error('Error al cargar empresas:', error);
    } finally {
        loading.value = false;
    }
};

const filteredCompanies = computed(() => {
    const search = globalFilterValue.value.toLowerCase();
    return companies.value.filter((company) =>
        company.name.toLowerCase().includes(search) ||
        company.business_name.toLowerCase().includes(search) ||
        company.document.toString().includes(search) ||
        company.sectornom.toLowerCase().includes(search)
    );
});

const paginatedCompanies = computed(() => {
    const start = (currentPage.value - 1) * rowsPerPage.value;
    return filteredCompanies.value.slice(start, start + rowsPerPage.value);
});

const onGlobalSearch = debounce(() => {
    currentPage.value = 1;
}, 500);

const onPage = (event) => {
    currentPage.value = event.page + 1;
    rowsPerPage.value = event.rows;
};

onMounted(() => {
    loadCompanies();
});
</script>

<template>
    <DataTable ref="dt" v-model:selection="selectedCompanies" :value="paginatedCompanies" dataKey="id" :paginator="true"
        :rows="rowsPerPage" :totalRecords="filteredCompanies.length" :first="(currentPage - 1) * rowsPerPage"
        :loading="loading" @page="onPage" :rowsPerPageOptions="[25, 100, 50]" scrollable scrollHeight="574px"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} empresas" class="p-datatable-sm">

        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">
                    Empresas
                    <Tag severity="contrast" :value="contadorEmpresas" />
                </h4>
                <div class="flex flex-wrap gap-2">
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="globalFilterValue" @input="onGlobalSearch" 
                            placeholder="Buscar por RUC, nombre, razón social..." />
                    </IconField>
                    <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" @click="loadCompanies" />
                </div>
            </div>
        </template>

        <Column selectionMode="multiple" style="width: 1rem" :exportable="false" />
        
        <!-- RUC -->
        <Column field="document" header="RUC" sortable style="min-width: 8rem">
            <template #body="{ data }">
                <span class="font-mono text-sm">{{ data.document }}</span>
            </template>
        </Column>

        <!-- Razón Social -->
        <Column field="business_name" header="Razón Social" sortable style="min-width: 15rem">
            <template #body="{ data }">
                <div class="max-w-xs">
                    <span class="truncate block" :title="data.business_name">{{ data.business_name }}</span>
                </div>
            </template>
        </Column>

        <!-- Nombre de la Empresa -->
        <Column field="name" header="Nombre Comercial" sortable style="min-width: 12rem">
            <template #body="{ data }">
                <div class="max-w-xs">
                    <span class="truncate block font-medium" :title="data.name">{{ data.name }}</span>
                </div>
            </template>
        </Column>

        <!-- Calificación -->
        <Column field="risk" header="Calificación" sortable style="min-width: 6rem">
            <template #body="{ data }">
                <Tag :value="getRiskLabel(data.risk)" :severity="getRiskSeverity(data.risk)" 
                    class="px-3 py-1 rounded-lg font-bold" />
            </template>
        </Column>

        <!-- Sector -->
        <Column field="sectornom" header="Sector" sortable style="min-width: 10rem">
            <template #body="{ data }">
                <span class="text-blue-600 font-medium">{{ data.sectornom }}</span>
            </template>
        </Column>

        <!-- Subsector -->
        <Column field="subsectornom" header="Subsector" sortable style="min-width: 12rem">
            <template #body="{ data }">
                <span class="">{{ data.subsectornom }}</span>
            </template>
        </Column>

        <!-- Fecha de Creación -->
        <Column field="creacion" header="Fecha Creación" sortable style="min-width: 10rem">
            <template #body="{ data }">
                <span class="text-sm">{{ data.creacion }}</span>
            </template>
        </Column>

        <!-- Acciones -->
        <Column style="min-width: 8rem">
            <template #body="slotProps">
                <div class="flex gap-1">
                    <Button icon="pi pi-eye" outlined rounded class="mr-1" severity="info" size="small"
                        @click="verDetalles(slotProps.data)" v-tooltip.top="'Ver detalles'" />
                    <Button icon="pi pi-pencil" outlined rounded class="mr-1" size="small"
                        @click="editCompany(slotProps.data)" v-tooltip.top="'Editar'" />
                    <Button icon="pi pi-trash" outlined rounded severity="danger" size="small"
                        @click="confirmDelete(slotProps.data)" v-tooltip.top="'Eliminar'" />
                </div>
            </template>
        </Column>
    </DataTable>

    <!-- Dialogs comentados hasta que los implementes -->
    <!-- <deleteCompany v-model:visible="deleteDialog" :tipoCliente="company" @deleted="handleCompanyDeleted" /> -->
    <!-- <updateCompany v-model:visible="updateDialog" :tipoClienteId="selectedCompanyId" @updated="handleCompanyUpdated" /> -->
</template>
