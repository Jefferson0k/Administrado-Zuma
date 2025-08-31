<script setup>
import { ref, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { debounce } from 'lodash';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const companies = ref([]);
const selectedCompanies = ref([]);
const loading = ref(false);
const totalRecords = ref(0);
const globalFilter = ref('');
const first = ref(0);
const rows = ref(25);

const deleteDialog = ref(false);
const updateDialog = ref(false);
const company = ref({});
const selectedCompanyId = ref(null);

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

// Acciones
const editCompany = (company) => {
    selectedCompanyId.value = company.id;
    updateDialog.value = true;
};
const confirmDelete = (companyData) => {
    company.value = companyData;
    deleteDialog.value = true;
};
const verDetalles = (company) => {
    router.visit(`/companies/${company.id}`);
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
    } finally {
        loading.value = false;
    }
};


// Búsqueda global con debounce
const onGlobalSearch = debounce(() => {
    first.value = 0;
    loadCompanies();
}, 500);

// Manejo de paginación
const onPage = (event) => {
    loadCompanies(event);
};

// Cargar datos al montar
onMounted(() => {
    loadCompanies();
});
</script>

<template>
        <DataTable
            v-model:selection="selectedCompanies"
            :value="companies"
            dataKey="id"
            :paginator="true"
            :rows="rows"
            :totalRecords="totalRecords"
            :first="first"
            :loading="loading"
            :rowsPerPageOptions="[25,50,100]"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} empresas"
            @page="onPage"
            scrollable
            scrollHeight="574px"
            class="p-datatable-sm"
        >
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
                            <InputText
                                v-model="globalFilter"
                                @input="onGlobalSearch"
                                placeholder="Buscar por RUC, nombre, razón social..."
                            />
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
                    <Tag :value="getRiskLabel(data.risk)" :severity="getRiskSeverity(data.risk)" class="px-3 py-1 rounded-lg font-bold" />
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
                            @click="verDetalles(slotProps.data)" v-tooltip.top="'Ver detalles'" />
                        <Button icon="pi pi-pencil" outlined rounded class="mr-1" size="small"
                            @click="editCompany(slotProps.data)" v-tooltip.top="'Editar'" />
                        <Button icon="pi pi-trash" outlined rounded severity="danger" size="small"
                            @click="confirmDelete(slotProps.data)" v-tooltip.top="'Eliminar'" />
                    </div>
                </template>
            </Column>
        </DataTable>
</template>
