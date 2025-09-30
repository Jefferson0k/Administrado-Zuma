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
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';

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

const emit = defineEmits(['edit-subsector']);

const confirm = useConfirm();
const toast = useToast();

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
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudieron cargar los subsectores',
            life: 3000
        });
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

const editSubSector = (subsector) => {
    emit('edit-subsector', subsector);
};

const confirmDelete = (subsector) => {
    if (subsector.vinculado === 1) {
        toast.add({
            severity: 'warn',
            summary: 'No permitido',
            detail: 'No se puede eliminar un subsector vinculado',
            life: 3000
        });
        return;
    }

    confirm.require({
        message: `¿Estás seguro de eliminar el subsector "${subsector.name}"?`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        accept: () => deleteSubSector(subsector.id)
    });
};

const deleteSubSector = async (id) => {
    try {
        await axios.delete(`/subsectors/${id}`);
        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Subsector eliminado correctamente',
            life: 3000
        });
        loadSubSectores();
    } catch (error) {
        console.error('Error al eliminar subsector:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'No se pudo eliminar el subsector',
            life: 3000
        });
    }
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
        <Column header="Estado" style="min-width: 8rem">
            <template #body="slotProps">
                <Tag v-if="slotProps.data.vinculado === 1" severity="success" value="Vinculado" />
                <Tag v-else severity="secondary" value="Sin vincular" />
            </template>
        </Column>
        <Column header="Acciones" style="min-width: 10rem">
            <template #body="slotProps">
                <Button 
                    icon="pi pi-pencil" 
                    outlined 
                    rounded 
                    class="mr-2" 
                    @click="editSubSector(slotProps.data)"
                    v-tooltip.top="'Editar subsector'"
                />
                <Button 
                    v-if="slotProps.data.vinculado === 0"
                    icon="pi pi-trash" 
                    outlined 
                    rounded 
                    severity="danger" 
                    @click="confirmDelete(slotProps.data)"
                    v-tooltip.top="'Eliminar subsector'"
                />
                <Button 
                    v-else
                    icon="pi pi-trash" 
                    outlined 
                    rounded 
                    severity="danger" 
                    disabled
                    v-tooltip.top="'No se puede eliminar un subsector vinculado'"
                />
            </template>
        </Column>
    </DataTable>
</template>