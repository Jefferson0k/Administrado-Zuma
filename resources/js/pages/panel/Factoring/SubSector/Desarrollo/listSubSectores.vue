<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import axios from 'axios';
import Tag from 'primevue/tag';
import { debounce } from 'lodash';
// Aquí luego cambias DeleteSubSectores y UpdateSubSectores
// cuando tengas los componentes listos para subsector
// import DeleteSubSectores from './DeleteSubSectores.vue';
// import UpdateSubSectores from './UpdateSubSectores.vue';

const props = defineProps({
    refresh: {
        type: Number,
        required: true,
    },
    sectorId: {
        type: Number,
        required: true,
    },
});

const dt = ref();
const subSectores = ref([]);
const selectedSubSectores = ref();
const loading = ref(false);
const globalFilterValue = ref('');
const contadorSubSectores = ref(0);

const rowsPerPage = ref(10);
const currentPage = ref(1);

watch(() => props.refresh, () => {
    loadSubSectores();
});

const loadSubSectores = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/subsectors/sector/${props.sectorId}`);
        subSectores.value = response.data.data;
        contadorSubSectores.value = response.data.total;
        currentPage.value = 1;
    } catch (error) {
        console.error('Error al cargar subsectores:', error);
    } finally {
        loading.value = false;
    }
};

const filteredSubSectores = computed(() => {
    const search = globalFilterValue.value.toLowerCase();
    return subSectores.value.filter((sa) =>
        sa.name.toLowerCase().includes(search) ||
        (sa.estado && sa.estado.toLowerCase().includes(search))
    );
});

const paginatedSubSectores = computed(() => {
    const start = (currentPage.value - 1) * rowsPerPage.value;
    return filteredSubSectores.value.slice(start, start + rowsPerPage.value);
});

const onGlobalSearch = debounce(() => {
    currentPage.value = 1;
}, 500);

const onPage = (event) => {
    currentPage.value = event.page + 1;
    rowsPerPage.value = event.rows;
};

onMounted(() => {
    loadSubSectores();
});
</script>

<template>
    <DataTable ref="dt" v-model:selection="selectedSubSectores" :value="paginatedSubSectores" dataKey="id" :paginator="true"
        :rows="rowsPerPage" :totalRecords="filteredSubSectores.length" :first="(currentPage - 1) * rowsPerPage"
        :loading="loading" @page="onPage" :rowsPerPageOptions="[5, 10, 20]" scrollable scrollHeight="574px"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} subsectores" class="p-datatable-sm">

        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">
                    Subsectores
                    <Tag severity="contrast" :value="contadorSubSectores" />
                </h4>
                <div class="flex flex-wrap gap-2">
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="globalFilterValue" @input="onGlobalSearch" placeholder="Buscar..." />
                    </IconField>
                    <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" @click="loadSubSectores" />
                </div>
            </div>
        </template>

        <Column selectionMode="multiple" style="width: 1rem" :exportable="false" />
        <Column field="name" header="Nombre" sortable style="min-width: 13rem" />
        <Column field="creacion" header="Creación" sortable style="min-width: 13rem" />
        <Column field="update" header="Actualización" sortable style="min-width: 13rem" />
        <Column>
            <template #body="slotProps">
                <Button icon="pi pi-pencil" outlined rounded class="mr-2" />
                <Button icon="pi pi-trash" outlined rounded severity="danger" />
            </template>
        </Column>
    </DataTable>
</template>
