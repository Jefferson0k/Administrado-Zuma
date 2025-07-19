<script setup>
import { ref, onMounted, watch } from 'vue'
import { FilterMatchMode } from '@primevue/core/api'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import axios from 'axios'

const toast = useToast()
const dt = ref()
const products = ref([])
const selectedProducts = ref()

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

const totalRecords = ref(0)
const loading = ref(false)
const currentPage = ref(1)
const rows = ref(10)

const loadData = async () => {
    loading.value = true
    try {
        const response = await axios.get(`/bids?page=${currentPage.value}&per_page=${rows.value}`)
        products.value = response.data.data
        totalRecords.value = response.data.meta.total
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar pujas', life: 3000 })
    } finally {
        loading.value = false
    }
}

onMounted(loadData)
watch(currentPage, loadData)
watch(rows, loadData)
</script>

<template>
    <DataTable
        ref="dt"
        v-model:selection="selectedProducts"
        :value="products"
        :lazy="true"
        dataKey="id"
        :paginator="true"
        :rows="rows"
        :totalRecords="totalRecords"
        :loading="loading"
        @page="e => { currentPage.value = e.page + 1 }"
        :filters="filters"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25]"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} pujas"
        class="p-datatable-sm"
    >
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Histórico de Pujas</h4>
                <IconField>
                    <InputIcon><i class="pi pi-search" /></InputIcon>
                    <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                </IconField>
            </div>
        </template>

        <Column selectionMode="multiple" style="width: 3rem" :exportable="false" />
        <Column field="nombre" header="Inversionista" style="min-width: 18rem" />
        <Column field="investor" header="Documento" style="min-width: 10rem" />
        <Column field="monto" header="Monto" style="min-width: 10rem" />
        <Column field="created_at" header="Fecha de inversión" style="min-width: 15rem" />
        <Column field="updated_at" header="Última modificación" style="min-width: 15rem" />
    </DataTable>
</template>
