<script setup>
import { onMounted, ref } from 'vue'
import { FilterMatchMode } from '@primevue/core/api'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Tag from 'primevue/tag'
import axios from 'axios'

const toast = useToast()
const dt = ref()
const products = ref([])
const selectedProducts = ref()
const totalRecords = ref(0)
const loading = ref(false)
const lazyParams = ref({
  page: 0,
  rows: 10,
})

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
})

const loadData = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/pagos-tasas', {
      params: {
        page: lazyParams.value.page + 1,
        per_page: lazyParams.value.rows,
      },
    })
    products.value = data.data
    totalRecords.value = data.meta.total
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar los pagos', life: 3000 })
  } finally {
    loading.value = false
  }
}

const onPage = (event) => {
  lazyParams.value = event
  loadData()
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <DataTable
    ref="dt"
    v-model:selection="selectedProducts"
    :value="products"
    dataKey="id"
    :paginator="true"
    :rows="lazyParams.rows"
    :totalRecords="totalRecords"
    :lazy="true"
    @page="onPage"
    :filters="filters"
    :loading="loading"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    :rowsPerPageOptions="[5, 10, 25]"
    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} pagos"
    class="p-datatable-sm"
  >
    <template #header>
      <div class="flex flex-wrap gap-2 items-center justify-between">
        <h4 class="m-0">Pagos</h4>
        <IconField>
          <InputIcon>
            <i class="pi pi-search" />
          </InputIcon>
          <InputText v-model="filters['global'].value" placeholder="Buscar..." />
        </IconField>
      </div>
    </template>

    <Column selectionMode="multiple" style="width: 3rem" :exportable="false" />
    <Column field="id" header="Código" sortable style="min-width: 10rem" />
    <Column
      field="inversionista.nombre_completo"
      header="Inversionista"
      sortable
      style="min-width: 14rem"
    />
    <Column field="monto" header="Monto" sortable style="min-width: 8rem" />
    <Column field="moneda" header="Moneda" sortable style="min-width: 6rem" />
    <Column field="fecha_pago" header="Fecha Pago" sortable style="min-width: 10rem" />
    <Column header="Estado" style="min-width: 10rem">
      <template #body="{ data }">
        <Tag :value="data.cronograma.estado" :severity="getSeverity(data.cronograma.estado)" />
      </template>
    </Column>
  </DataTable>
</template>

<script>
function getSeverity(status) {
  switch (status) {
    case 'pagado':
      return 'success'
    case 'pendiente':
      return 'warning'
    case 'vencido':
      return 'danger'
    default:
      return 'info'
  }
}
</script>
