<template>
  <div>
    <Tabs value="1" class="mb-4">
      <TabList>
        <Tab
          v-for="tab in tabs"
          :key="tab.value"
          :value="tab.value"
          @click="cambiarTab(tab.value)"
        >
          <i :class="tab.icon" class="mr-2" />
          {{ tab.title }}
        </Tab>
      </TabList>

      <TabPanels>
        <TabPanel
          v-for="tab in tabs"
          :key="tab.value"
          :value="tab.value"
        >
          <DataTable
            ref="dt"
            :value="products"
            :paginator="true"
            :rows="perPage"
            :rowsPerPageOptions="[5, 10, 25]"
            :totalRecords="totalRecords"
            :loading="loading"
            lazy
            dataKey="id"
            :first="(currentPage - 1) * perPage"
            @page="onPage"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} reglas"
            class="p-datatable-sm"

            :sortField="sortField"
            :sortOrder="sortOrder"
            sortMode="single"
            @sort="onSort"
          >
            <template #header>
              <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">
                  {{ estadoSeleccionado === '1' ? 'Reglas Inversionista' : 'Reglas Cliente' }}
                </h4>
                <IconField>
                  <template #icon><i class="pi pi-search" /></template>
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
            <Column field="Moneda" header="Moneda" sortable style="width: 5rem" />
            <Column field="valor_estimado" header="Valor de la propiedad" sortable style="width: 16rem">
              <template #body="{ data }">{{ formatMoney(data.valor_estimado) }}</template>
            </Column>
            <Column field="requerido" header="Monto Requerido" sortable style="width: 14rem">
              <template #body="{ data }">{{ formatMoney(data.requerido) }}</template>
            </Column>
            <Column field="tea" header="TEA" sortable style="width: 8rem">
              <template #body="{ data }">{{ data.tea }}%</template>
            </Column>
            <Column field="tem" header="TEM" sortable style="width: 8rem">
              <template #body="{ data }">{{ data.tem }}%</template>
            </Column>
            <Column field="tipo_cronograma" header="Cronograma" sortable style="width: 10rem">
              <template #body="{ data }">{{ formatCronograma(data.tipo_cronograma) }}</template>
            </Column>
            <Column field="deadlines_id" header="Plazo" sortable style="width: 10rem">
              <template #body="{ data }">{{ data.plazo_nombre || data.deadlines_id }}</template>
            </Column>
            <Column field="riesgo" header="Riesgo" sortable style="width: 5rem">
              <template #body="{ data }">
                <Tag :value="data.riesgo" :severity="getRiesgoSeverity(data.riesgo)" />
              </template>
            </Column>
            <Column field="estado_nombre" header="Usuario" sortable style="width: 8rem">
              <template #body="{ data }">
                <Tag :value="data.estado_nombre" :severity="getEstadoSeverity(data.estado_nombre)" />
              </template>
            </Column>
            <Column field="estadoProperty" header="Estado" sortable style="width: 8rem" />
            <Column header="" style="width: 1rem">
              <template #body="{ data }">
                <Button 
                  icon="pi pi-ellipsis-v" 
                  text 
                  rounded 
                  severity="secondary" 
                  @click="toggleMenu($event, data)" 
                  aria-haspopup="true" 
                  aria-controls="overlay_menu"
                  v-tooltip.bottom="'Opciones'"
                />
              </template>
            </Column>
          </DataTable>
        </TabPanel>
      </TabPanels>
    </Tabs>

    <!-- Menú contextual -->
    <Menu ref="menu" id="overlay_menu" :model="menuItems" :popup="true" />

    <!-- Diálogos -->
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
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import debounce from 'lodash/debounce'
import { useToast } from 'primevue/usetoast'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Menu from 'primevue/menu'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'

import UpdateReglas from './UpdateReglas.vue'
import VerCronograma from './VerCronograma.vue'

const dt = ref()
const menu = ref()
const toast = useToast()

const products = ref([])
const totalRecords = ref(0)
const currentPage = ref(1)
const perPage = ref(10)           // ◂ server page size
const loading = ref(false)
const searchText = ref('')
const reglaSeleccionada = ref(null)
const dialogVisible = ref(false)
const dialogCronograma = ref(false)

// estado activo (1 = Inversionista, 2 = Cliente)
const estadoSeleccionado = ref('1')

// server-side sorting state
const sortField = ref(null)       // e.g. 'nombre', 'Moneda', 'tea', ...
const sortOrder = ref(1)          // 1 asc | -1 desc

const tabs = ref([
  { title: 'Inversionista', value: '1', icon: 'pi pi-user' },
  { title: 'Cliente', value: '2', icon: 'pi pi-users' }
])

const menuItems = ref([
  { label: 'Copiar ID', icon: 'pi pi-copy',     command: () => copiarId() },
  { label: 'Editar',    icon: 'pi pi-pencil',   command: () => editarRegla() },
  { label: 'Ver Cronograma', icon: 'pi pi-calendar', command: () => verCronograma() }
])

// dinero pretty
const formatMoney = (value) => {
  if (value === null || value === undefined || isNaN(value)) return '0.00'
  return new Intl.NumberFormat('es-PE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
    useGrouping: true
  }).format(Number(value))
}

const toggleMenu = (event, rowData) => {
  reglaSeleccionada.value = { ...rowData }
  menu.value.toggle(event)
}

const cambiarTab = (newValue) => {
  estadoSeleccionado.value = newValue
  currentPage.value = 1
  searchText.value = ''
  sortField.value = null
  sortOrder.value = 1
  cargarPropiedades(1, '')
}

const buscarReglas = debounce(() => {
  currentPage.value = 1
  cargarPropiedades(1, searchText.value)
}, 500)

const cargarPropiedades = async (page = 1, search = '') => {
  loading.value = true
  try {
    const params = {
      page,
      per_page: perPage.value,
      search,
      estado: parseInt(estadoSeleccionado.value, 10)
    }

    // include sorting only if set
    if (sortField.value) {
      params.sort_field = sortField.value
      params.sort_order = sortOrder.value === 1 ? 'asc' : 'desc'
    }

    const { data } = await axios.get('/property/reglas', { params })

    products.value     = data.data || []
    totalRecords.value = data.meta?.total || 0
    currentPage.value  = data.meta?.current_page || 1
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar las reglas', life: 3000 })
  } finally {
    loading.value = false
  }
}

const formatCronograma = (tipo) =>
  tipo === 'frances' ? 'Francés' : (tipo === 'americano' ? 'Americano' : tipo)

const getEstadoSeverity = (estado) =>
  estado === 'Inversionista' ? 'success' : (estado === 'Cliente' ? 'info' : 'secondary')

const getRiesgoSeverity = (riesgo) => {
  switch (riesgo) {
    case 'A+': case 'A': return 'success'
    case 'B': return 'info'
    case 'C': return 'warn'
    case 'D': return 'danger'
    default: return 'secondary'
  }
}

const onPage = (event) => {
  currentPage.value = event.page + 1
  perPage.value = event.rows
  cargarPropiedades(currentPage.value, searchText.value)
}

// PrimeVue sort event (lazy)
const onSort = (event) => {
  // event.sortField (string) | event.sortOrder (1|-1)
  sortField.value = event.sortField || null
  sortOrder.value = typeof event.sortOrder === 'number' ? event.sortOrder : 1
  currentPage.value = 1
  cargarPropiedades(1, searchText.value)
}

const editarRegla = () => { dialogVisible.value = true }
const verCronograma = () => { dialogCronograma.value = true }

const copiarId = async () => {
  try {
    const property_id = `${reglaSeleccionada.value.property_id}`
    await navigator.clipboard.writeText(property_id)
    toast.add({ severity: 'success', summary: 'ID copiado', detail: `Property ID: ${property_id}`, life: 2000 })
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo copiar el ID', life: 3000 })
  }
}

onMounted(() => {
  cargarPropiedades(1, '')
})
</script>
