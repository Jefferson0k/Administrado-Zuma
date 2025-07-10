<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import { FilterMatchMode } from '@primevue/core/api'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

// Definir props
const props = defineProps({
    refreshTrigger: {
        type: Number,
        default: 0
    }
})

const planes = ref([])
const selectedPlanes = ref([])
const dt = ref()

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

const fetchPlanes = async () => {
    try {
        const response = await axios.get('/term-plans')
        planes.value = response.data.data
    } catch (error) {
        console.error('Error cargando los planes:', error)
    }
}

// Observar cambios en refreshTrigger
watch(() => props.refreshTrigger, () => {
    if (props.refreshTrigger > 0) {
        fetchPlanes()
    }
})

onMounted(fetchPlanes)
</script>

<template>
    <DataTable ref="dt" v-model:selection="selectedPlanes" :value="planes" dataKey="id" :paginator="true" :rows="20"
        :filters="filters"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[20 , 50, 100]"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} registros" class="p-datatable-sm">
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Planes de Plazo Fijo</h4>
                <IconField>
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                </IconField>
            </div>
        </template>

        <Column selectionMode="multiple" style="width: 3rem" :exportable="false" />
        <Column field="nombre" header="Nombre" sortable style="min-width: 12rem" />
        <Column field="dias_minimos" header="Días Mínimos" sortable style="min-width: 10rem" />
        <Column field="dias_maximos" header="Días Máximos" sortable style="min-width: 10rem" />
    </DataTable>
</template>
