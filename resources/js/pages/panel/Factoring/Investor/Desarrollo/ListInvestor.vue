<template>
  <DataTable ref="dt" :value="investors" dataKey="id" v-model:selection="selectedInvestors" :paginator="true"
    :lazy="true" :rows="rowsPerPage" :totalRecords="totalRecords" :first="(currentPage - 1) * rowsPerPage"
    :loading="loading" :rowsPerPageOptions="[5, 10, 20, 50]"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} inversionistas" scrollable
    scrollHeight="574px" class="p-datatable-sm" :customSort="true" :sortField="sortField" :sortOrder="sortOrder"
    @sort="onSort" @page="onPage">
    <!-- Header -->
    <template #header>
      <div class="flex flex-wrap gap-2 items-center justify-between">
        <h4 class="m-0">
          Inversionistas
          <Tag severity="contrast" :value="totalRecords" />
        </h4>
        <div class="flex flex-wrap gap-2">
          <IconField>
            <InputIcon>
              <i class="pi pi-search" />
            </InputIcon>
            <InputText v-model="globalFilter" @input="onGlobalSearch"
              placeholder="Buscar por nombre, alias, email o estado..." />
          </IconField>
          <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" severity="contrast"
            @click="reloadFirstPage" />
        </div>
      </div>
    </template>

    <!-- Columns (sortable = local/current-page only) -->
    <Column selectionMode="multiple" style="width: 1rem" />

    <Column field="name" header="Nombre" sortable style="min-width: 25rem" />
    <Column field="document" header="Documento" sortable style="min-width: 7rem" />
    <Column field="alias" header="Alias" sortable style="min-width: 10rem" />
    <Column field="telephone" header="Teléfono" sortable style="min-width: 7rem" />
    <Column field="email" header="Email" sortable style="min-width: 18rem" />

    <!-- Validación 1 -->
    <Column field="approval1_status" header="1ª Aprobador" sortable style="min-width: 9rem">
      <template #body="slotProps">
        <Tag v-if="slotProps.data.approval1_status" :value="getStatusLabel(slotProps.data.approval1_status)"
          :severity="getStatusSeverity(slotProps.data.approval1_status)" />
        <span v-else class="italic">Sin estado</span>
      </template>
    </Column>
    <Column field="approval1_by" header="1ª User Aprobador" sortable style="min-width: 15rem">
      <template #body="slotProps">
        <span :class="!slotProps.data.approval1_by ? 'italic' : ''">
          {{ slotProps.data.approval1_by || 'Sin usuario' }}
        </span>
      </template>
    </Column>
    <Column field="approval1_at" header="T. 1º Aprobador" sortable style="min-width: 13rem">
      <template #body="slotProps">
        <span :class="!slotProps.data.approval1_at ? 'italic' : ''">
          {{ slotProps.data.approval1_at || 'Sin fecha' }}
        </span>
      </template>
    </Column>

    <!-- Validación 2 -->
    <Column field="approval2_status" header="2º Aprobador" sortable style="min-width: 9rem">
      <template #body="slotProps">
        <Tag v-if="slotProps.data.approval2_status" :value="getStatusLabel(slotProps.data.approval2_status)"
          :severity="getStatusSeverity(slotProps.data.approval2_status)" />
        <span v-else class="italic">Sin estado</span>
      </template>
    </Column>
    <Column field="approval2_by" header="2º User Aprob" sortable style="min-width: 15rem">
      <template #body="slotProps">
        <span :class="!slotProps.data.approval2_by ? 'italic' : ''">
          {{ slotProps.data.approval2_by || 'Sin usuario' }}
        </span>
      </template>
    </Column>
    <Column field="approval2_at" header="T. 2ª Aprobación" sortable style="min-width: 12rem">
      <template #body="slotProps">
        <span :class="!slotProps.data.approval2_at ? 'italic' : ''">
          {{ slotProps.data.approval2_at || 'Sin fecha' }}
        </span>
      </template>
    </Column>

    <!-- Estado principal -->
    <Column field="status" header="Estado Conclusión" sortable style="min-width: 11rem">
      <template #body="slotProps">
        <Tag v-if="slotProps.data.status" :value="getStatusLabel(slotProps.data.status)"
          :severity="getStatusSeverity(slotProps.data.status)" />
        <span v-else class="italic">Sin estado</span>
      </template>
    </Column>

    <!-- Creación -->
    <Column field="created_at" header="Creación" sortable style="min-width: 12rem">
      <template #body="{ data }">
        <span>{{ data.creacion || '—' }}</span>
      </template>
    </Column>

    <!-- Botón de detalle -->
    <Column header="">
      <template #body="{ data }">
        <Button icon="pi pi-eye" text rounded @click="viewInvestorDetail(data)" aria-label="Ver detalle"
          title="Ver detalle" />
      </template>
    </Column>


    <!-- Botón de historial -->
    <Column header="">
      <template #body="{ data }">
        <Button icon="pi pi-history" text rounded @click="openHistory(data)" aria-label="Ver historial"
          title="Ver historial" />
      </template>
    </Column>

  </DataTable>

  <!-- Modal de detalle -->
  <showInvertor v-if="selectedInvestorForDetail" :investor="selectedInvestorForDetail" :visible="showDetailDialog"
    @update:visible="showDetailDialog = $event" @status-updated="handleStatusUpdate" />



  <!-- Modal de Historial -->
  <Dialog v-model:visible="showHistoryDialog" :style="{ width: '760px', maxWidth: '95vw' }"
    :header="selectedInvestorForHistory ? `Historial — ${selectedInvestorForHistory.name}` : 'Historial'" :modal="true">
    <div class="space-y-3">
      <div v-if="loadingHistory" class="text-sm text-gray-600">Cargando historial...</div>

      <template v-else>
        <div v-if="!historyRows.length" class="text-sm text-gray-600">
          No hay registros de historial.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full border border-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 border-b text-left">ID</th>
                <th class="px-3 py-2 border-b text-left">1ª Estado</th>
                <th class="px-3 py-2 border-b text-left">1ª Por</th>
                <th class="px-3 py-2 border-b text-left">1ª Fecha</th>
                <th class="px-3 py-2 border-b text-left">1ª Comentario</th>
                <th class="px-3 py-2 border-b text-left">2ª Estado</th>
                <th class="px-3 py-2 border-b text-left">2ª Por</th>
                <th class="px-3 py-2 border-b text-left">2ª Fecha</th>
                <th class="px-3 py-2 border-b text-left">2ª Comentario</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in historyRows" :key="row.id" class="odd:bg-white even:bg-gray-50 align-top">
                <td class="px-3 py-2 border-b">{{ row.id }}</td>

                <td class="px-3 py-2 border-b">
                  <Tag :value="getStatusLabel(row.approval1_status || '—')"
                    :severity="getStatusSeverity(row.approval1_status || '')" />
                </td>
                <td class="px-3 py-2 border-b">{{ row.approval1By?.name || row.approval1_by || '—' }}</td>
                <td class="px-3 py-2 border-b">{{ row.approval1_at || '—' }}</td>
                <td class="px-3 py-2 border-b whitespace-pre-wrap">{{ row.approval1_comment || '—' }}</td>

                <td class="px-3 py-2 border-b">
                  <Tag :value="getStatusLabel(row.approval2_status || '—')"
                    :severity="getStatusSeverity(row.approval2_status || '')" />
                </td>
                <td class="px-3 py-2 border-b">{{ row.approval2By?.name || row.approval2_by || '—' }}</td>
                <td class="px-3 py-2 border-b">{{ row.approval2_at || '—' }}</td>
                <td class="px-3 py-2 border-b whitespace-pre-wrap">{{ row.approval2_comment || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>

    <template #footer>
      <Button label="Cerrar" size="small" severity="secondary" text @click="showHistoryDialog = false" />
    </template>
  </Dialog>

</template>

<script setup lang="ts">
import axios from 'axios'
import { debounce } from 'lodash'
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'
import { onMounted, ref } from 'vue'
import showInvertor from './showInvertor.vue'
import Dialog from 'primevue/dialog'


const toast = useToast()
const investors = ref<any[]>([])
const selectedInvestors = ref<any[]>([])
const loading = ref(false)
const totalRecords = ref(0)
const globalFilter = ref('')
const rowsPerPage = ref(10)
const currentPage = ref(1)

// local (current-page) sort state
const sortField = ref<string>('') // no default local sort
const sortOrder = ref<number>(1)   // 1 asc, -1 desc

const showDetailDialog = ref(false)
const selectedInvestorForDetail = ref<any>(null)

// --- Historial por inversionista ---
interface HistoryRow {
  id: number
  investor_id: number
  approval1_status: string | null
  approval1_by: number | null
  approval1_comment: string | null
  approval1_at: string | null
  approval2_status: string | null
  approval2_by: number | null
  approval2_comment: string | null
  approval2_at: string | null
  approval1By?: { id: number; name: string } | null
  approval2By?: { id: number; name: string } | null
}

const showHistoryDialog = ref(false)
const historyRows = ref<HistoryRow[]>([])
const loadingHistory = ref(false)
const selectedInvestorForHistory = ref<any>(null)

const openHistory = async (investor: any) => {
  selectedInvestorForHistory.value = investor
  showHistoryDialog.value = true
  await loadApprovalHistory(investor?.id)
}

const loadApprovalHistory = async (investorId?: number | string) => {
  if (!investorId) return
  loadingHistory.value = true
  try {
    const { data } = await axios.get(`/investor/${investorId}/approval-history`)
    historyRows.value = (data?.data || []).map((r: any) => ({
      ...r,
      // normaliza eager loaded users si vienen como approval1By/approval2By
      approval1By: r.approval1By ?? r.approval1_by,
      approval2By: r.approval2By ?? r.approval2_by,
    }))
  } catch (e) {
    // opcional: toast
  } finally {
    loadingHistory.value = false
  }
}


const loadInvestors = async (event: any = {}) => {
  loading.value = true
  const page = event.page != null ? event.page + 1 : currentPage.value
  const perPage = event.rows != null ? Number(event.rows) : rowsPerPage.value

  try {
    // No sort params to server — only pagination + filter
    const response = await axios.get('/investor', {
      params: { search: globalFilter.value, page, per_page: perPage }
    })
    investors.value = response.data.data ?? []
    totalRecords.value = response.data.total ?? 0
    currentPage.value = page
    rowsPerPage.value = perPage

    // Re-aplicar el sort local si existe
    applyLocalSort()
  } catch (error) {
    console.error('Error al cargar inversionistas:', error)
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar los inversionistas' })
  } finally {
    loading.value = false
  }
}

const reloadFirstPage = () => {
  currentPage.value = 1
  loadInvestors({ page: 0, rows: rowsPerPage.value })
}

const onGlobalSearch = debounce(() => {
  reloadFirstPage()
}, 500)

const onPage = (event: any) => {
  loadInvestors(event)
}

const onSort = (event: any) => {
  // event.sortField can be undefined if user clears sort
  sortField.value = event.sortField || ''
  sortOrder.value = typeof event.sortOrder === 'number' ? event.sortOrder : 1
  applyLocalSort()
}

// ---------- Local sort helpers (current page only) ----------
function applyLocalSort() {
  const field = sortField.value
  const order = sortOrder.value
  if (!field || !investors.value?.length) return

  // stable copy
  const arr = investors.value.slice()

  const get = (obj: any, path: string) =>
    path.split('.').reduce((o, k) => (o != null ? o[k] : undefined), obj)

  const cmp = (a: any, b: any): number => {
    const va = get(a, field)
    const vb = get(b, field)

    // null/undefined last
    const na = va == null
    const nb = vb == null
    if (na && nb) return 0
    if (na) return 1
    if (nb) return -1

    // numeric
    const an = typeof va === 'number' ? va : (Number.isFinite(+va) ? +va : null)
    const bn = typeof vb === 'number' ? vb : (Number.isFinite(+vb) ? +vb : null)
    if (an !== null && bn !== null) return an === bn ? 0 : (an < bn ? -1 : 1)

    // date
    const ad = Date.parse(va)
    const bd = Date.parse(vb)
    if (!isNaN(ad) && !isNaN(bd)) return ad === bd ? 0 : (ad < bd ? -1 : 1)

    // string
    return String(va).localeCompare(String(vb), undefined, { sensitivity: 'base', numeric: true })
  }

  investors.value = order === 1 ? arr.sort(cmp) : arr.sort((a, b) => -cmp(a, b))
}
// -----------------------------------------------------------

const getStatusLabel = (status: string) => {
  const normalized = status?.toLowerCase()
  switch (normalized) {
    case 'validated':
    case 'approved':
      return 'Aprobado'
    case 'observed':
      return 'Observado'
    case 'rejected':
      return 'Rechazado'
    case 'no validado':
    case 'pending':
      return 'Pendiente'
    default:
      return status ?? '—'
  }
}

const getStatusSeverity = (status: string) => {
  const normalized = status?.toLowerCase()
  switch (normalized) {
    case 'validated':
    case 'approved':
      return 'success'
    case 'observed':
      return 'warn'
    case 'rejected':
      return 'danger'
    case 'no validado':
    case 'pending':
      return 'contrast'
    default:
      return 'success'
  }
}

const viewInvestorDetail = async (investor: any) => {
  try {
    const response = await axios.get(`/investor/${investor.id}`)
    selectedInvestorForDetail.value = response.data.data
    showDetailDialog.value = true
  } catch (error) {
    console.error('Error al obtener detalles del inversionista:', error)
    selectedInvestorForDetail.value = investor
    showDetailDialog.value = true
  }
}

const handleStatusUpdate = (updatedInvestor: any) => {
  const index = investors.value.findIndex((inv) => inv.id === updatedInvestor.id)
  if (index !== -1) investors.value[index] = updatedInvestor
  if (selectedInvestorForDetail.value?.id === updatedInvestor.id) {
    selectedInvestorForDetail.value = updatedInvestor
  }
  showDetailDialog.value = false
}

onMounted(() => {
  loadInvestors({ page: 0, rows: rowsPerPage.value })
})
</script>
