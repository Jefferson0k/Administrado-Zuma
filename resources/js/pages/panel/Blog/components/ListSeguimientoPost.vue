<template>
  <DataTable
    ref="dt"
    v-model:selection="selectedPost"
    :value="posts"
    dataKey="id"
    :paginator="true"
    :rows="10"
    :filters="filters"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    :rowsPerPageOptions="[5, 10, 25]"
    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} posts"
    class="p-datatable-sm"
  >
    <template #header>
      <div class="flex flex-wrap gap-2 items-center justify-between">
        <h4 class="m-0">
          Publicaciones
          <Tag severity="contrast" :value="posts.length" />
        </h4>
        <IconField>
          <InputIcon><i class="pi pi-search" /></InputIcon>
          <InputText v-model="filters['global'].value" placeholder="Buscar..." />
        </IconField>
      </div>
    </template>

    <Column selectionMode="multiple" style="width: 3rem" :exportable="false" />
    <Column field="titulo" header="Titulo" sortable />

    <Column field="categories" header="Categorías" sortable>
      <template #body="{ data }">
        <div class="flex flex-wrap gap-2">
          <Tag v-for="p in data.categories" :key="p.id" :value="p.nombre" severity="info" rounded />
        </div>
      </template>
    </Column>

    <Column field="fecha_programada" header="Fecha Programada" sortable>
      <template #body="{ data }">
        {{ formatDate(data.fecha_programada) }}
      </template>
    </Column>

    <!-- NUEVA COLUMNA: Visitas -->
    <Column field="views_total" header="Visitas" sortable style="width: 9rem;">
      <template #body="{ data }">
        <div class="flex items-center gap-2">
          <i class="pi pi-eye text-gray-500"></i>
          <span class="font-medium">{{ formatNumber(data.views_total ?? 0) }}</span>
        </div>
      </template>
    </Column>

    <Column field="ratings" header="Calificación" sortable>
      <template #body="{ data }">
        <div class="flex items-center space-x-2">
          <Rating :modelValue="getPromedioRating(data.ratings)" readonly :cancel="false" />
          <span>({{ getPromedioRating(data.ratings) }})</span>
        </div>
      </template>
    </Column>

    <Column field="state_id" header="Estado" sortable>
      <template #body="{ data }">
        <Tag :value="getEstadoLabel(data.state_id)" :severity="getEstadoSeverity(data.state_id)" rounded />
      </template>
    </Column>

    <Column field="user" header="Creado Por" sortable>
      <template #body="{ data }">
        <span>{{ data.user?.name || 'Sin asignar' }}</span>
      </template>
    </Column>

    <Column field="updated_user" header="Modificado Por" sortable>
      <template #body="{ data }">
        <span>{{ data.updated_user?.name || 'Sin modificar' }}</span>
      </template>
    </Column>

    <!-- Si quieres reactivar el menú por fila, descomenta esto y usa toggleMenu/menuItems -->
    <!--
    <Column header="" style="width: 3rem;">
      <template #body="{ data }">
        <div>
          <Button icon="pi pi-ellipsis-v" rounded text @click="toggleMenu($event, data)" />
        </div>
      </template>
    </Column>
    -->
  </DataTable>

  <Menu ref="menu" :model="menuItems" popup />
  <ConfigPost ref="configDialog" />
  <VerDialog ref="viewDialogRef" />

  <Dialog v-model:visible="editDialog" :style="{ width: '600px' }" header="Editar Publicación" :modal="true">
    <div class="flex flex-col gap-6">
      <div>
        <label class="block font-bold mb-3">Titulo <span class="text-red-500">*</span></label>
        <InputText
          v-model="editForm.titulo"
          :useGrouping="false"
          placeholder="Ingresa el titulo"
          inputId="titulo"
          class="w-full"
        />
      </div>

      <!-- Categoría(s) -->
      <div>
        <label class="block font-bold mb-3">Categoría(s) <span class="text-red-500">*</span></label>
        <MultiSelect
          v-model="editForm.category_ids"
          display="chip"
          :options="categories"
          optionLabel="nombre"
          optionValue="id"
          filter
          placeholder="Seleccione la(s) categoría(s)"
          :maxSelectedLabels="3"
          class="w-full"
        />
      </div>

      <div>
        <label class="block font-bold mb-3">Resumen <span class="text-red-500">*</span></label>
        <InputText
          v-model="editForm.resumen"
          :useGrouping="false"
          placeholder="Ingresa el resumen"
          inputId="resumen"
          class="w-full"
        />
      </div>

      <div>
        <label class="block font-bold mb-3">Contenido <span class="text-red-500">*</span></label>
        <Textarea v-model="editForm.contenido" placeholder="Ingresa el contenido" rows="3" class="w-full" />
      </div>

      <div>
        <label class="block font-bold mb-3">Fecha Programada <span class="text-red-500">*</span></label>
        <Calendar
          v-model="editForm.fecha_programada"
          dateFormat="dd/mm/yy"
          placeholder="Selecciona la fecha"
          showIcon
          class="w-full"
        />
      </div>

      <div>
        <label class="block font-bold mb-3">Imagén para mostrar</label>
        <FileUpload
          mode="advanced"
          name="img"
          accept=".jpg,.jpeg,.png"
          :auto="true"
          customUpload
          :maxFileSize="10000000"
          @uploader="onUploadImage"
          :chooseLabel="'Seleccionar Imagen'"
          :uploadLabel="'Subir'"
          :cancelLabel="'Cancelar'"
          class="w-full"
        />
        <small class="text-gray-500">Opcional: si no seleccionas una nueva imagen, se mantiene la actual.</small>
      </div>
    </div>

    <template #footer>
      <Button label="Cancelar" icon="pi pi-times" text @click="editDialog = false" severity="secondary" />
      <Button label="Guardar" icon="pi pi-check" @click="actualizarPost" severity="contrast" />
    </template>
  </Dialog>

  <ConfirmDialog />
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import { FilterMatchMode } from '@primevue/core/api'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

// PrimeVue
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Menu from 'primevue/menu'
import Dialog from 'primevue/dialog'
import FileUpload from 'primevue/fileupload'
import ConfirmDialog from 'primevue/confirmdialog'
import Rating from 'primevue/rating'
import MultiSelect from 'primevue/multiselect'
import Calendar from 'primevue/calendar'
import Textarea from 'primevue/textarea'

// Local
import VerDialog from './ver.vue'
import ConfigPost from './ConfigPost.vue'

const toast = useToast()
const confirm = useConfirm()
const dt = ref()
const menu = ref()
const configDialog = ref(null)
const posts = ref([])
const selectedPost = ref([])
const selectedItem = ref(null)
const loading = ref(false)
const viewDialogRef = ref(null)

const editDialog = ref(false)
const viewDialog = ref(false)

// Props (we need user.id for publicar)
const props = defineProps({
  refresh: Number,
  user: {
    type: Object,
    default: () => ({ id: null, name: '', email: '' })
  }
})

// Formulario de edición
const editForm = ref({
  id: null,
  titulo: '',
  resumen: '',
  contenido: '',
  fecha_programada: null,   // Date
  category_ids: []          // array<number>
})

const categories = ref([])   // options for MultiSelect
const archivoImg = ref(null) // File | null

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

const menuItems = ref([
  { label: 'Publicar', icon: 'pi pi-play', command: () => publicar(selectedItem.value) },
  { label: 'Ver imagen', icon: 'pi pi-image', command: () => verImagen(selectedItem.value) },
  { label: 'Editar', icon: 'pi pi-pencil', command: () => editar(selectedItem.value) },
  { label: 'Eliminar', icon: 'pi pi-trash', command: () => eliminar(selectedItem.value) }
])

function toggleMenu(event, item) {
  selectedItem.value = item
  menu.value.toggle(event)
}

function ver(item) {
  configDialog.value?.close?.()
  selectedItem.value = item
  viewDialog.value = true
  viewDialogRef.value.open(item)
}

function editar(item) {
  selectedItem.value = item
  editForm.value = {
    id: item.id,
    titulo: item.titulo,
    resumen: item.resumen,
    contenido: item.contenido,
    fecha_programada: item.fecha_programada
      ? new Date(String(item.fecha_programada).replace(' ', 'T'))
      : null,
    // map from categories on the post
    category_ids: (item.categories || []).map(c => Number(c.id))
  }
  archivoImg.value = null
  editDialog.value = true
}

function eliminar(item) {
  confirm.require({
    message: `¿Estás seguro de que quieres eliminar la publicación "${item.titulo}"?`,
    header: 'Confirmar eliminación',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Sí, eliminar',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        loading.value = true
        await axios.delete(`/api/blog/eliminar/${item.id}`)
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Publicación eliminada correctamente', life: 3000 })
        obtenerPost()
      } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo eliminar la publicación', life: 3000 })
      } finally {
        loading.value = false
      }
    }
  })
}

function configurar(item) {
  configDialog.value.open(item)
}

function verImagen(item) {
  if (!item?.imagen) {
    toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'Esta publicación no tiene una imagen asociada', life: 3000 })
    return
  }
  const url = `/image/${item.imagen}`
  window.open(url, '_blank')
}

async function publicar(item) {
  try {
    loading.value = true
    const userId = props?.user?.id ?? 1
    await axios.get(`/api/blog/publicar/${userId}/${item.id}/2`)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Publicación realizada correctamente', life: 3000 })
    editDialog.value = false
    obtenerPost()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar la acción', life: 3000 })
  } finally {
    loading.value = false
  }
}

function formatDateRequest(date) {
  if (!date) return ''
  const d = new Date(date)
  const yyyy = d.getFullYear()
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const dd = String(d.getDate()).padStart(2, '0')
  const hh = String(d.getHours()).padStart(2, '0')
  const mi = String(d.getMinutes()).padStart(2, '0')
  const ss = String(d.getSeconds()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd} ${hh}:${mi}:${ss}`
}

async function actualizarPost() {
  try {
    loading.value = true

    const formData = new FormData()
    formData.append('user_id', props?.user?.id ?? 1)
    formData.append('titulo', editForm.value.titulo || '')
    formData.append('resumen', editForm.value.resumen || '')
    formData.append('contenido', editForm.value.contenido || '')
    formData.append('fecha_programada', formatDateRequest(editForm.value.fecha_programada))
    formData.append('state_id', 1)

    // ✅ categories as CSV string
    formData.append('category_id', (editForm.value.category_ids || []).join(','))

    // ✅ Only append image if user picked a new file
    if (archivoImg.value) {
      formData.append('imagen', archivoImg.value)
    }

    await axios.post(`/api/blog/actualizar/${editForm.value.id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Publicación actualizada correctamente', life: 3000 })
    editDialog.value = false
    obtenerPost()
  } catch (error) {
    const detail = error?.response?.data?.message || error?.response?.data?.error || 'No se pudo actualizar la publicación'
    toast.add({ severity: 'error', summary: 'Error', detail, life: 3000 })
  } finally {
    loading.value = false
  }
}

async function obtenerPost() {
  try {
    const res = await axios.get('/api/blog/lista')
    posts.value = res.data.posts || []
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar posts', life: 3000 })
  }
}

function onUploadImage(event) {
  const file = event.files?.[0]
  const allowedTypes = ['image/jpeg', 'image/png']
  if (file && allowedTypes.includes(file.type)) {
    archivoImg.value = file
    toast.add({ severity: 'success', summary: 'Imagen cargada', detail: `Archivo "${file.name}" listo para enviar`, life: 3000 })
  } else {
    toast.add({ severity: 'error', summary: 'Archivo inválido', detail: 'Debe subir un archivo JPG o PNG.', life: 4000 })
  }
}

async function obtenerCategorias() {
  try {
    const res = await axios.get('/api/blog/listar-categoria', { params: { per_page: 1000 } })
    categories.value = Array.isArray(res.data?.data) ? res.data.data : []
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar categorías', life: 3000 })
  }
}

const formatDate = (date) => {
  if (!date) return ''
  const d = new Date(date)
  if (isNaN(d)) return ''
  const day = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const year = d.getFullYear()
  let hours = d.getHours()
  const minutes = String(d.getMinutes()).padStart(2, '0')
  const ampm = hours >= 12 ? 'pm' : 'am'
  hours = hours % 12
  hours = hours ? hours : 12
  hours = String(hours).padStart(2, '0')
  return `${day}/${month}/${year} ${hours}:${minutes} ${ampm}`
}

function getEstadoLabel(stateId) {
  switch (stateId) {
    case 1: return 'Creado'
    case 2: return 'Publicado'
    case 3: return 'Eliminado'
    default: return 'Desconocido'
  }
}

function getEstadoSeverity(stateId) {
  switch (stateId) {
    case 1: return 'warning'
    case 2: return 'success'
    case 3: return 'danger'
    default: return 'info'
  }
}

function getPromedioRating(ratings) {
  if (!Array.isArray(ratings) || ratings.length === 0) return 0
  const total = ratings.reduce((sum, r) => sum + parseFloat(r.estrellas || 0), 0)
  return Math.round((total / ratings.length) * 10) / 10 // 1 decimal
}

// 👁️ Helper para formatear visitas
function formatNumber(n) {
  n = Number(n || 0)
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1).replace(/\.0$/, '') + 'M'
  if (n >= 1_000) return (n / 1_000).toFixed(1).replace(/\.0$/, '') + 'K'
  return String(n)
}

onMounted(() => {
  obtenerPost()
  obtenerCategorias()
})

watch(() => props.refresh, () => {
  obtenerPost()
})
</script>
