<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3'; // 👈 Inertia router
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import axios from 'axios';
import Tag from 'primevue/tag';
import { debounce } from 'lodash';
import deleteSector from './deleteSector.vue';
import updateSector from './updateSector.vue';

const dt = ref();
const sectors = ref([]);
const selectedSectors = ref();
const loading = ref(false);
const globalFilterValue = ref('');
const deleteDialog = ref(false);
const sector = ref({});
const selectedSectorId = ref(null);
const updateDialog = ref(false);
const contadorSectores = ref(0);

const rowsPerPage = ref(10);
const currentPage = ref(1);

const props = defineProps({
    refresh: {
        type: Number,
        required: true
    }
});

watch(() => props.refresh, () => {
    loadSectors();
});

function editSector(sector) {
    selectedSectorId.value = sector.id;
    updateDialog.value = true;
}

function confirmDelete(sectorData) {
    sector.value = sectorData;
    deleteDialog.value = true;
}

function handleSectorUpdated() {
    loadSectors();
}

function handleSectorDeleted() {
    loadSectors();
}

function verSubsectores(sector) {
    router.visit(`/factoring/${sector.id}/subsectors`);
}

const loadSectors = async () => {
    loading.value = true;
    try {
        const params = {
            search: globalFilterValue.value,
        };
        const response = await axios.get('/sectors', { params });
        sectors.value = response.data.data;
        contadorSectores.value = response.data.total;
        currentPage.value = 1;
    } catch (error) {
        console.error('Error al cargar sectores:', error);
    } finally {
        loading.value = false;
    }
};

const filteredSectors = computed(() => {
    const search = globalFilterValue.value.toLowerCase();
    return sectors.value.filter((sector) =>
        sector.name.toLowerCase().includes(search)
    );
});

const paginatedSectors = computed(() => {
    const start = (currentPage.value - 1) * rowsPerPage.value;
    return filteredSectors.value.slice(start, start + rowsPerPage.value);
});

const onGlobalSearch = debounce(() => {
    currentPage.value = 1;
}, 500);

const onPage = (event) => {
    currentPage.value = event.page + 1;
    rowsPerPage.value = event.rows;
};

onMounted(() => {
    loadSectors();
});
</script>

<template>
    <DataTable ref="dt" v-model:selection="selectedSectors" :value="paginatedSectors" dataKey="id" :paginator="true"
        :rows="rowsPerPage" :totalRecords="filteredSectors.length" :first="(currentPage - 1) * rowsPerPage"
        :loading="loading" @page="onPage" :rowsPerPageOptions="[5, 10, 20]" scrollable scrollHeight="574px"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} sectores" class="p-datatable-sm">

        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">
                    Sectores
                    <Tag severity="contrast" :value="contadorSectores" />
                </h4>
                <div class="flex flex-wrap gap-2">
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="globalFilterValue" @input="onGlobalSearch" placeholder="Buscar..." />
                    </IconField>
                    <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" @click="loadSectors" />
                </div>
            </div>
        </template>

        <Column selectionMode="multiple" style="width: 1rem" :exportable="false" />
        <Column field="name" header="Nombre" sortable style="min-width: 13rem" />
        <Column field="creacion" header="Creación" sortable style="min-width: 13rem" />
        <Column field="update" header="Actualizacion" sortable style="min-width: 13rem" />

        <Column>
            <template #body="slotProps">
                <Button icon="pi pi-sitemap" outlined rounded class="mr-2" severity="contrast"
                    @click="verSubsectores(slotProps.data)" />
                <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editSector(slotProps.data)" />
                <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDelete(slotProps.data)" />
            </template>
        </Column>
    </DataTable>

    <deleteSector v-model:visible="deleteDialog" :tipoCliente="sector" @deleted="handleSectorDeleted" />
    <updateSector v-model:visible="updateDialog" :tipoClienteId="selectedSectorId" @updated="handleSectorUpdated" />
</template>
