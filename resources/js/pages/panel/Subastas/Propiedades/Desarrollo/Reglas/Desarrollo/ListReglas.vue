<template>
    <DataTable
        ref="dt"
        :value="products"
        :paginator="true"
        :rows="10"
        :rowsPerPageOptions="[5, 10, 25]"
        :totalRecords="totalRecords"
        :loading="loading"
        lazy
        dataKey="id"
        @page="onPage"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} reglas"
        class="p-datatable-sm"
    >
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Reglas activas</h4>
                <IconField>
                    <template #icon>
                        <i class="pi pi-search" />
                    </template>
                    <InputText
                        v-model="searchText"
                        placeholder="Buscar por nombre, cronograma o riesgo..."
                        @input="buscarReglas"
                    />
                </IconField>
            </div>
        </template>
        
        <Column selectionMode="multiple" style="width: 3rem" :exportable="false" />
        <Column field="nombre" header="Propiedad" sortable style="width: 25rem" />
        <Column field="tea" header="TEA" sortable style="width: 8rem">
            <template #body="{ data }">
                {{ data.tea }}%
            </template>
        </Column>
        <Column field="tem" header="TEM" sortable style="width: 8rem">
            <template #body="{ data }">
                {{ data.tem }}%
            </template>
        </Column>
        <Column field="tipo_cronograma" header="Cronograma" sortable style="width: 10rem">
            <template #body="{ data }">
                {{ formatCronograma(data.tipo_cronograma) }}
            </template>
        </Column>
        <Column field="deadlines_id" header="Plazo" sortable style="width: 10rem">
            <template #body="{ data }">
                {{ data.plazo_nombre || data.deadlines_id }}
            </template>
        </Column>

        <Column field="riesgo" header="Riesgo" style="width: 10rem">
            <template #body="{ data }">
                <Tag :value="data.riesgo" :severity="getRiesgoSeverity(data.riesgo)" />
            </template>
        </Column>

        <Column field="estado_nombre" header="Estado" style="width: 10rem">
            <template #body="{ data }">
                <Tag :value="data.estado_nombre || data.estado" :severity="getEstadoSeverity(data.estado)" />
            </template>
        </Column>

        <Column header="" style="width: 10rem">
            <template #body="{ data }">
                <Button icon="pi pi-copy" outlined rounded severity="info" class="mr-2"
                    @click="copiarId(data.id)" v-tooltip.bottom="'Copiar ID'" />
                <Button icon="pi pi-pencil" outlined rounded severity="contrast" class="mr-2"
                    @click="editarRegla(data)" v-tooltip.bottom="'Editar'" />
                <Button icon="pi pi-calendar" outlined rounded severity="help"
                    @click="verCronograma(data)" v-tooltip.bottom="'Ver Cronograma'" />
            </template>
        </Column>

    </DataTable>

    <UpdateReglas
        v-model="dialogVisible"
        :regla="reglaSeleccionada"
        @updated="cargarPropiedades(currentPage, searchText)"
    />
    <VerCronograma
        v-model="dialogCronograma"
        :propiedad="reglaSeleccionada"
        @cerrar="dialogCronograma = false"
    />

</template>

<script setup>
import { onMounted, ref } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import debounce from 'lodash/debounce'
import VerCronograma from './VerCronograma.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import UpdateReglas from './UpdateReglas.vue'

const toast = useToast()
const dt = ref()
const products = ref([])
const totalRecords = ref(0)
const currentPage = ref(1)
const loading = ref(false)
const searchText = ref('')
const reglaSeleccionada = ref(null)
const dialogVisible = ref(false)
const dialogCronograma = ref(false)

const verCronograma = (row) => {
    reglaSeleccionada.value = { ...row }
    dialogCronograma.value = true
}
const buscarReglas = debounce(async () => {
    currentPage.value = 1
    await cargarPropiedades(1, searchText.value)
}, 500)

const cargarPropiedades = async (page = 1, search = '') => {
    loading.value = true
    try {
        const response = await axios.get('/property/reglas', {
            params: { page, search }
        })

        products.value = response.data.data
        totalRecords.value = response.data.meta.total
        currentPage.value = response.data.meta.current_page
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudieron cargar las reglas',
            life: 3000,
        })
    } finally {
        loading.value = false
    }
}

const formatCronograma = (tipo) => {
    switch (tipo) {
        case 'frances':
            return 'Francés'
        case 'americano':
            return 'Americano'
        default:
            return tipo
    }
}

onMounted(() => {
    cargarPropiedades()
})

const onPage = (event) => {
    cargarPropiedades(event.page + 1, searchText.value)
}

const getEstadoSeverity = (estado) => {
    switch (estado) {
        case 'activa':
            return 'success'
        case 'pendiente':
            return 'warn'
        case 'subastada':
            return 'info'
        case 'desactivada':
            return 'danger'
        default:
            return 'secondary'
    }
}

const getRiesgoSeverity = (riesgo) => {
    switch (riesgo) {
        case 'A+':
        case 'A':
            return 'success'
        case 'B':
            return 'info'
        case 'C':
            return 'warn'
        case 'D':
            return 'danger'
        default:
            return 'secondary'
    }
}

const editarRegla = (row) => {
    console.log('Editando regla:', row)
    reglaSeleccionada.value = { ...row }
    dialogVisible.value = true
}

const copiarId = async (id) => {
    try {
        await navigator.clipboard.writeText(id)
        toast.add({
            severity: 'success',
            summary: 'ID copiado',
            detail: `ID: ${id}`,
            life: 2000
        })
    } catch (err) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo copiar el ID',
            life: 3000
        })
    }
}
</script>