<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { FilterMatchMode } from '@primevue/core/api'
import { useToast } from 'primevue/usetoast'

// PrimeVue
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Button from 'primevue/button'

// CRUD internos
import AddRateType from './AddRateType.vue'
import UpdateRateType from './UpdateRateType.vue'
import DeleteRateType from './DeleteRateType.vue'

const toast = useToast()
const rateTypes = ref<any[]>([])
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

const formCreate = ref()
const formUpdate = ref()
const formDelete = ref()

const fetchRateTypes = async () => {
    try {
        const res = await axios.get('/rate-types')
        rateTypes.value = res.data.data
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar el listado' })
    }
}

onMounted(fetchRateTypes)
</script>

<template>
        <AddRateType ref="formCreate" @updated="fetchRateTypes" />

    <DataTable :value="rateTypes" dataKey="id" :filters="filters" :paginator="true" :rows="10" class="p-datatable-sm">
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Tipos de Tasa</h4>
                <IconField>
                    <InputIcon><i class="pi pi-search" /></InputIcon>
                    <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                </IconField>
            </div>
        </template>
        <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
        <Column field="nombre" header="Nombre" sortable />
        <Column field="descripcion" header="Descripción" sortable />
        <Column field="created_at" header="Creado" sortable />
        <Column field="updated_at" header="Actualizado" sortable />
        <Column header="">
            <template #body="{ data }">
                <div class="flex gap-2">
                    <Button icon="pi pi-pencil" outlined rounded severity="contrast" class="mr-2" @click="formUpdate.open(data)" />
                    <Button icon="pi pi-trash" outlined rounded severity="danger"  @click="formDelete.open(data)" />
                </div>
            </template>
        </Column>
    </DataTable>

    <!-- Formularios -->
    <UpdateRateType ref="formUpdate" @updated="fetchRateTypes" />
    <DeleteRateType ref="formDelete" @updated="fetchRateTypes" />
</template>
