<script setup>
import { ref, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputIcon from 'primevue/inputicon';
import IconField from 'primevue/iconfield';
import Tag from 'primevue/tag';
import { debounce } from 'lodash';
import deleteSector from './deleteSector.vue';
import updateSector from './updateSector.vue';

const sectors = ref([]);
const selectedSectors = ref([]);
const loading = ref(false);
const globalFilter = ref('');
const deleteDialog = ref(false);
const updateDialog = ref(false);
const sector = ref({});
const selectedSectorId = ref(null);
const totalRecords = ref(0);

const rowsPerPage = ref(10);
const currentPage = ref(1);

const props = defineProps({ refresh: { type: Number, required: true } });
watch(() => props.refresh, () => loadSectors());

const loadSectors = async (event = {}) => {
    loading.value = true;

    const page = event.page != null ? event.page + 1 : currentPage.value;
    const perPage = event.rows != null ? Number(event.rows) : rowsPerPage.value;

    try {
        const response = await axios.get('/sectors', {
            params: {
                search: globalFilter.value,
                page,
                perPage
            }
        });
        sectors.value = response.data.data;
        totalRecords.value = response.data.total;
        currentPage.value = page;
        rowsPerPage.value = perPage;
    } catch (error) {
        console.error('Error al cargar sectores:', error);
    } finally {
        loading.value = false;
    }
};

const onGlobalSearch = debounce(() => {
    currentPage.value = 1;
    loadSectors();
}, 500);

const onPage = (event) => {
    loadSectors(event);
};

function editSector(s) {
    selectedSectorId.value = s.id;
    updateDialog.value = true;
}

function confirmDelete(s) {
    sector.value = s;
    deleteDialog.value = true;
}

function handleSectorUpdated() {
    loadSectors();
}

function handleSectorDeleted() {
    loadSectors();
}

function verSubsectores(s) {
    router.visit(`/factoring/${s.id}/subsectors`);
}

onMounted(() => {
    loadSectors();
});
</script>

<template>
  <DataTable
    ref="dt"
    v-model:selection="selectedSectors"
    :value="sectors"
    dataKey="id"
    :paginator="true"
    :rows="rowsPerPage"
    :totalRecords="totalRecords"
    :first="(currentPage - 1) * rowsPerPage"
    :loading="loading"
    @page="onPage"
    :rowsPerPageOptions="[5, 10, 20, 50]"
    scrollable
    scrollHeight="574px"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} sectores"
    class="p-datatable-sm"
  >
    <template #header>
      <div class="flex flex-wrap gap-2 items-center justify-between">
        <h4 class="m-0">
          Sectores
          <Tag severity="contrast" :value="totalRecords" />
        </h4>
        <div class="flex flex-wrap gap-2">
          <IconField>
            <InputIcon>
              <i class="pi pi-search" />
            </InputIcon>
            <InputText v-model="globalFilter" @input="onGlobalSearch" placeholder="Buscar..." />
          </IconField>
          <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" @click="loadSectors" />
        </div>
      </div>
    </template>

    <Column selectionMode="multiple" style="width: 1rem" :exportable="false" />
    <Column field="name" header="Nombre" sortable style="min-width: 13rem" />
    <Column field="creacion" header="Creación" sortable style="min-width: 13rem" />
    <Column field="update" header="Actualización" sortable style="min-width: 13rem" />

    <Column>
      <template #body="{ data }">
        <Button icon="pi pi-sitemap" outlined rounded class="mr-2" severity="contrast"
          @click="verSubsectores(data)" />
        <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editSector(data)" />
        <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDelete(data)" />
      </template>
    </Column>
  </DataTable>

  <deleteSector v-model:visible="deleteDialog" :tipoCliente="sector" @deleted="handleSectorDeleted" />
  <updateSector v-model:visible="updateDialog" :tipoClienteId="selectedSectorId" @updated="handleSectorUpdated" />
</template>
