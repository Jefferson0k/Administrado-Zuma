<template>
  <DataTable ref="dt" v-model:selection="selectedPost" :value="posts" dataKey="id" :paginator="true" :rows="rows"
    :totalRecords="totalRecords" :first="(page - 1) * rows" :lazy="true" :loading="loading" :sortField="sortField"
    :sortOrder="sortOrder" @page="onPage" @sort="onSort" @filter="onFilter" :filters="filters"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    :rowsPerPageOptions="[5, 10, 25]" currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} posts"
    class="p-datatable-sm">

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

    <Column field="titulo" header="Titulo" sortable />
    <Column field="contenido" header="Contenido" sortable>
      <template #body="{ data }">
        <div class="line-clamp-4">
          {{ data.contenido }}
        </div>
      </template>
    </Column>
    <Column field="categories" header="Categorías" sortable>
      <template #body="{ data }">
        <div class="flex items-center space-x-2">
          <Rating :modelValue="Number(data.rating_avg || 0)" readonly :cancel="false" />
          <span>({{ Number(data.rating_avg || 0).toFixed(1) }})</span>
        </div>
      </template>
    </Column>
    <Column field="fecha_programada" header="Fecha Programada" sortable>
      <template #body="{ data }">
        {{ formatDate(data.fecha_programada) }}
      </template>
    </Column>
    <Column field="state_id" header="Estado" sortable>
      <template #body="{ data }">
        <Tag :value="getEstadoLabel(data.state_id)" :severity="getEstadoSeverity(data.state_id)" rounded />
      </template>
    </Column>
    <Column header="" style="width: 3rem;">
      <template #body="{ data }">
        <div>
          <Button icon="pi pi-ellipsis-v" rounded text @click="toggleMenu($event, data)" />
        </div>
      </template>
    </Column>
  </DataTable>

  <Menu ref="menu" :model="menuItems" popup />
  <VerDialog ref="viewDialogRef" />

  <Dialog v-model:visible="editDialog" :style="{ width: '820px' }" header="Editar Publicación" :modal="true"
    @hide="cleanupPreview">
    <div class="flex flex-col gap-6">
      <!-- Título -->
      <div>
        <label class="block font-bold mb-3">Titulo <span class="text-red-500">*</span></label>
        <InputText v-model="editForm.titulo" placeholder="Ingresa el titulo" inputId="titulo" class="w-full" />
      </div>

      <!-- Producto -->
      <div>
        <label class="block font-bold mb-3">Producto <span class="text-red-500">*</span></label>
        <Select v-model="selectedProduct" :options="products" optionLabel="nombre" optionValue="id"
          placeholder="Seleccione el producto" class="w-full" />
      </div>

      <!-- Categorías -->
      <div>
        <label class="block font-bold mb-3">Categoría(s) <span class="text-red-500">*</span></label>
        <MultiSelect v-model="editForm.category_id" display="chip" :options="categories" optionLabel="nombre"
          optionValue="id" filter placeholder="Seleccione la categoría" :maxSelectedLabels="3" class="w-full" />
      </div>

      <!-- Galería -->
      <div>
        <div class="flex items-center justify-between">
          <label class="block font-bold mb-3">Imágenes del post</label>
          <small class="text-gray-500">Click ★ para marcar portada • 🗑️ para eliminar</small>
        </div>

        <div v-if="allImages.length" class="grid grid-cols-5 sm:grid-cols-6 md:grid-cols-8 gap-2">
          <div v-for="img in allImages" :key="img.key" class="relative group rounded overflow-hidden border bg-white"
            :class="img.markedForDeletion ? 'opacity-50 grayscale' : ''">
            <img :src="img.src" :alt="img.alt" class="thumb-img" @error="onThumbError($event, img.path)" />


            <!-- Badge portada -->
            <span v-if="img.isMain || coverImageId === img.id"
              class="absolute top-1 left-1 px-1.5 py-0.5 text-[10px] rounded bg-blue-600 text-white shadow">
              Portada
            </span>

            <!-- Acciones -->
            <div
              class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-1 pb-1 opacity-0 group-hover:opacity-100 transition">
              <Button icon="pi pi-search" text rounded class="p-button-sm btn-icon-xs" @click="previewImg(img.src)" />
              <Button icon="pi pi-star" text rounded class="p-button-sm btn-icon-xs" @click="setAsCover(img)"
                :disabled="img.isNew" />
              <Button icon="pi pi-trash" text rounded severity="danger" class="p-button-sm btn-icon-xs"
                @click="toggleDelete(img)" :disabled="img.isMain && coverImageId == null" />
            </div>
          </div>
        </div>
        <div v-else class="text-sm text-gray-500">No hay imágenes todavía.</div>
      </div>

      <!-- Agregar nuevas imágenes -->
      <div>
        <label class="block font-bold mb-3">Agregar imagen(es) a la galería</label>
        <FileUpload mode="advanced" name="imgs" :multiple="true" accept=".jpg,.jpeg,.png" :auto="true" customUpload
          :maxFileSize="10000000" @uploader="onUploadExtraImages" :chooseLabel="'Seleccionar'" :uploadLabel="'Agregar'"
          :cancelLabel="'Cancelar'" class="w-full" />
        <div v-if="newImagesPreview.length" class="mt-3">
          <div class="grid grid-cols-5 sm:grid-cols-6 md:grid-cols-8 gap-2">
            <div v-for="(src, i) in newImagesPreview" :key="'new-prev-' + i"
              class="relative rounded overflow-hidden border">
              <img :src="src" alt="Nueva imagen" class="thumb-img object-cover" />
              <button type="button" class="absolute top-1 right-1 p-1 rounded bg-white/90 hover:bg-white shadow"
                @click="removeNewImage(i)" title="Quitar de nuevas">
                <i class="pi pi-times text-xs"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Contenido -->
      <div>
        <label class="block font-bold mb-3">Contenido <span class="text-red-500">*</span></label>
        <QuillEditor v-model:content="editForm.contenido" contentType="html" placeholder="Ingresa el contenido"
          class="w-full" />
      </div>

      <!-- Fecha -->
      <div>
        <label class="block font-bold mb-3">Fecha Programada <span class="text-red-500">*</span></label>
        <Calendar v-model="editForm.fecha_programada" dateFormat="dd/mm/yy" placeholder="Selecciona la fecha" showIcon
          showTime hourFormat="12" class="w-full" />
      </div>
    </div>

    <template #footer>
      <Button label="Cancelar" icon="pi pi-times" text @click="editDialog = false" severity="secondary" />
      <Button label="Guardar" icon="pi pi-check" @click="actualizarPost" severity="contrast" />
    </template>
  </Dialog>

  <ConfirmDialog />



  <Toast position="top-center" group="confirm">
    <template #message="slotProps">
      <div class="flex items-start gap-3">
        <i class="pi pi-exclamation-triangle mt-1"></i>
        <div class="flex-1">
          <div class="font-semibold">¿Publicar ahora?</div>
          <div class="text-sm opacity-80">"{{ slotProps.message?.data?.item?.titulo || 'Sin título' }}"</div>
          <div class="mt-2 flex gap-2">
            <Button size="small" label="Sí, publicar" @click="onConfirmToast(slotProps)" />
            <Button size="small" label="Cancelar" text @click="onRejectToast(slotProps)" />
          </div>
        </div>
      </div>
    </template>
  </Toast>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import axios from 'axios'
import { FilterMatchMode } from '@primevue/core/api'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

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
import Toast from 'primevue/toast'
import VerDialog from './ver.vue'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import Calendar from 'primevue/calendar'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'



const toast = useToast()
const confirm = useConfirm()
const dt = ref()
const menu = ref()

const posts = ref([])
const selectedPost = ref([])
const selectedItem = ref(null)
const loading = ref(false)
const viewDialogRef = ref(null)
const editDialog = ref(false)

// -------- Form --------
const editForm = ref({
  id: null,
  titulo: '',
  category_id: [],
  resumen: '',
  contenido: '',
  fecha_programada: null,
})

const products = ref([])
const selectedProduct = ref(null)
const categories = ref([])

// IMÁGENES
const extraImages = ref([])             // existentes (PostImage[])
const newImages = ref([])               // File[]
const newImagesPreview = ref([])        // blob urls
const deletedImageIds = ref(new Set())  // ids marcados para borrar
const coverImageId = ref(null)          // id de PostImage como portada (opcional)
const mainTry = ref(0)                  // fallback idx para portada

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

const props = defineProps({
  refresh: Number,
  user: {
    id: Number,
    name: String,
    email: String,
  }
})

const page = ref(1)
const rows = ref(10)
const totalRecords = ref(0)
const sortField = ref('created_at')
const sortOrder = ref(-1) // -1 desc, 1 asc (PrimeVue)


let searchTimer
watch(() => filters.value.global?.value, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    obtenerPost()
  }, 300)
})



// ==== Menú contextual ====
const menuItems = ref([])
function toggleMenu(event, item) {
  selectedItem.value = item
  menuItems.value = [
    { label: 'Publicar', icon: 'pi pi-play', command: () => publicar(item) },
    { label: 'Publicar (confirmar)', icon: 'pi pi-check-circle', command: () => confirmarPorToast(item) },

    { label: 'Ver imagen', icon: 'pi pi-image', command: () => verImagen(item), disabled: !item.imagen_url && !item.imagen },

    { label: 'Editar', icon: 'pi pi-pencil', command: () => editar(item) },
    { label: 'Eliminar', icon: 'pi pi-trash', command: () => eliminar(item) },
  ]
  menu.value.toggle(event)
}



function confirmarPorToast(item) {
  toast.add({
    group: 'confirm',
    severity: 'warn',
    summary: 'Confirmar publicación',
    data: { item },
    life: 0,         // sticky
    closable: false, // obliga a usar los botones
  })
}

async function onConfirmToast({ message, close }) {
  const item = message?.data?.item
  // Reutiliza tu flujo actual sin tocar publicar()
  await publicar(item)
  close && close()
}

function onRejectToast({ close }) {
  toast.add({ severity: 'info', summary: 'Cancelado', detail: 'Acción cancelada por el usuario', life: 2000 })
  close && close()
}
// ==== Utils de rutas para imágenes ====
// If backend sends absolute URLs (S3/MinIO), use them.
// Otherwise, fall back to legacy local paths.
// If backend sends absolute URLs (S3/MinIO), use them.
// Otherwise, fall back to legacy local paths.
function srcCandidates(name) {
  if (!name) return []
  if (/^https?:\/\//i.test(name)) return [name] // backend gave full S3/MinIO URL
  return [`/image/${name}`] // single local fallback (optional)
}


function bestSrc(nameOrUrl) {
  const list = srcCandidates(nameOrUrl)
  return list[0] || ''
}
function onThumbError(e, nameOrUrl) {
  const list = srcCandidates(nameOrUrl)
  const current = e.target.src
  const idx = list.findIndex(u => u === current)
  // If absolute single URL (S3), there’s nothing else to try — optionally set a placeholder
  if (list.length <= 1) {
    e.target.src = ''
    return
  }
  e.target.src = list[Math.min(idx + 1, list.length - 1)] || list[list.length - 1]
}


// Todas las imágenes (portada + relacionadas + nuevas)
const allImages = computed(() => {
  const arr = []

  // Principal (de Post.imagen / imagen_url)
  const mainUrl = selectedItem.value?.imagen_url || bestSrc(selectedItem.value?.imagen)
  if (mainUrl) {
    arr.push({
      key: `main-${selectedItem.value?.imagen || 'url'}`,
      id: null,                // no pertenece a post_images
      src: mainUrl,
      path: selectedItem.value?.imagen_url || selectedItem.value?.imagen,
      alt: 'Portada actual',
      isMain: true,
      isNew: false,
      markedForDeletion: false
    })
  }

  // Relacionadas (PostImage[]), preferir image.url si viene del backend
  for (const im of extraImages.value || []) {
    if (!im) continue
    const src = im.url || bestSrc(im.image_path)
    const marked = deletedImageIds.value.has(im.id)
    arr.push({
      key: `extra-${im.id}-${im.image_path || 'url'}`,
      id: im.id ?? null,
      src,
      path: im.url || im.image_path,
      alt: 'Imagen relacionada',
      isMain: false,
      isNew: false,
      markedForDeletion: marked
    })
  }

  // Nuevas (File previews)
  for (let i = 0; i < newImagesPreview.value.length; i++) {
    arr.push({
      key: `new-${i}`,
      id: null,
      src: newImagesPreview.value[i],
      path: newImages.value[i]?.name || `new-${i}`,
      alt: 'Nueva imagen',
      isMain: false,
      isNew: true,
      markedForDeletion: false
    })
  }
  return arr
})

// ===== Acciones en miniaturas =====
function previewImg(src) {
  window.open(src, '_blank', 'noopener')
}
function setAsCover(img) {
  if (img.isNew) {
    toast.add({ severity: 'warn', summary: 'Portada', detail: 'Solo puedes elegir portada desde imágenes ya guardadas.', life: 2500 })
    return
  }
  if (!img.id) {
    // es la portada actual
    coverImageId.value = null
    toast.add({ severity: 'info', summary: 'Portada', detail: 'Ya es la portada actual.', life: 1800 })
    return
  }
  coverImageId.value = img.id
  toast.add({ severity: 'success', summary: 'Portada', detail: 'Se marcará como portada al guardar.', life: 2000 })
}
function toggleDelete(img) {
  if (img.isNew) {
    // quitar de la cola de nuevas
    const idx = newImagesPreview.value.findIndex((u) => u === img.src)
    if (idx > -1) removeNewImage(idx)
    return
  }
  if (img.isMain && coverImageId.value == null) {
    toast.add({ severity: 'warn', summary: 'No permitido', detail: 'Elige otra portada antes de eliminar la actual.', life: 2500 })
    return
  }
  if (img.id == null) return // portada actual sin id (no es PostImage)
  if (deletedImageIds.value.has(img.id)) {
    deletedImageIds.value.delete(img.id)
  } else {
    deletedImageIds.value.add(img.id)
  }
}

// ========= Editar =========
async function editar(item) {
  cleanupPreview()
  selectedItem.value = item

  // cargar relacionadas desde la lista si existen
  extraImages.value = Array.isArray(item.images) ? item.images : []

  // fallback para asegurar que tenemos images e ids
  if (!extraImages.value.length) {
    try {
      const { data } = await axios.get(`/api/blog/showpost/${item.id}`)
      if (Array.isArray(data?.images)) extraImages.value = data.images
      if (data?.imagen) selectedItem.value.imagen = data.imagen
      if (data?.imagen_url) selectedItem.value.imagen_url = data.imagen_url
    } catch { }
  }


  // deduce product desde categorías (si category tiene product_id)
  let pid = null
  if (Array.isArray(item.categories) && item.categories.length) {
    pid = Number(item.categories[0]?.product_id)
  }
  selectedProduct.value = Number.isFinite(pid) ? pid : null
  await obtenerCategorias()

  // form
  editForm.value = {
    id: item.id,
    titulo: item.titulo || '',
    resumen: item.resumen || '',
    contenido: item.contenido || '',
    fecha_programada: item.fecha_programada
      ? new Date(String(item.fecha_programada).replace(' ', 'T'))
      : null,
    category_id: (item.categories || []).map(c => Number(c.id)).filter(Boolean)
  }

  editDialog.value = true
}

// ========= Guardar =========
async function actualizarPost() {
  try {
    loading.value = true

    const formData = new FormData()
    formData.append('user_id', String(props.user?.id ?? 1))
    formData.append('titulo', editForm.value.titulo)
    formData.append('category_id', (editForm.value.category_id || []).join(','))
    formData.append('resumen', editForm.value.resumen || '')
    formData.append('contenido', editForm.value.contenido || '')
    formData.append('fecha_programada', formatDateRequest(editForm.value.fecha_programada))
    formData.append('state_id', '1')

    // NUEVAS imágenes (agregar a la galería)
    for (const file of newImages.value) {
      formData.append('new_images[]', file)
    }

    // IMÁGENES a eliminar
    if (deletedImageIds.value.size) {
      for (const id of deletedImageIds.value) {
        formData.append('delete_image_ids[]', String(id))
      }
    }

    // Cambiar portada (usar una existente de post_images)
    if (coverImageId.value != null) {
      formData.append('cover_image_id', String(coverImageId.value))
    }

    await axios.post(`/blog/actualizar/${editForm.value.id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Publicación actualizada correctamente', life: 3000 })
    editDialog.value = false
    await obtenerPost()
  } catch (error) {
    const detail = error?.response?.data?.error || 'No se pudo actualizar la publicación'
    toast.add({ severity: 'error', summary: 'Error', detail, life: 4000 })
  } finally {
    loading.value = false
  }
}

// ========= Carga de datos =========
async function obtenerPost() {
  try {
    loading.value = true
    const params = {
      page: page.value,
      rows: rows.value,
      sortField: sortField.value,
      sortOrder: sortOrder.value,
      global: filters.value.global?.value || ''
    }
    const { data } = await axios.get('/blog/lista', { params })
    posts.value = Array.isArray(data?.data) ? data.data : []
    totalRecords.value = Number(data?.total || posts.value.length)
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar posts', life: 3000 })
    posts.value = []
    totalRecords.value = 0
  } finally {
    loading.value = false
  }
}

async function obtenerCategorias() {
  try {
    let url = '/api/blog/listar-categoria'
    if (selectedProduct.value) url = `/api/blog/listar-categoria-filtrada/${selectedProduct.value}`
    const res = await axios.get(url)
    const data = Array.isArray(res.data?.data) ? res.data.data : (Array.isArray(res.data) ? res.data : [])
    categories.value = data
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar categorias', life: 3000 })
  }
}

async function obtenerProductos() {
  try {
    const res = await axios.get('/api/blog/productos')
    products.value = res.data || []
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar productos', life: 3000 })
  }
}

// ========= Upload / Preview =========
function onUploadExtraImages(event) {
  const files = event.files || []
  const allowed = ['image/jpeg', 'image/png']
  const valid = []
  for (const f of files) {
    if (allowed.includes(f.type)) valid.push(f)
  }
  if (!valid.length) {
    toast.add({ severity: 'error', summary: 'Archivo inválido', detail: 'Solo JPG o PNG.', life: 4000 })
    return
  }
  for (const f of valid) {
    newImages.value.push(f)
    const url = URL.createObjectURL(f)
    newImagesPreview.value.push(url)
  }
  toast.add({ severity: 'success', summary: 'Agregadas', detail: `${valid.length} imagen(es) en cola`, life: 2500 })
}

function removeNewImage(index) {
  const url = newImagesPreview.value[index]
  if (url) URL.revokeObjectURL(url)
  newImagesPreview.value.splice(index, 1)
  newImages.value.splice(index, 1)
}

// ========= Acciones varias =========
function ver(item) {
  selectedItem.value = item
  viewDialogRef.value?.open?.(item)
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
        await axios.delete(`/blog/eliminar/${item.id}`)
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Publicación eliminada correctamente', life: 3000 })
        await obtenerPost()
      } catch {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo eliminar la publicación', life: 3000 })
      } finally {
        loading.value = false
      }
    }
  })
}

function verImagen(item) {
  const url = item?.imagen_url || bestSrc(item?.imagen)
  if (!url) {
    toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'Esta publicación no tiene una imagen asociada', life: 3000 })
    return
  }
  window.open(url, '_blank', 'noopener')
}


function onPage(e) {
  page.value = Math.floor(e.first / e.rows) + 1
  rows.value = e.rows
  obtenerPost()
}

function onSort(e) {
  sortField.value = e.sortField || 'created_at'
  sortOrder.value = e.sortOrder ?? -1
  obtenerPost()
}

function onFilter() {
  // PrimeVue passes filters via v-model already; just reset to page 1
  page.value = 1
  obtenerPost()
}



async function publicar(item) {
  try {
    loading.value = true
    await axios.get(`/blog/publicar/${props.user.id}/${item.id}/2`)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Publicación realizada correctamente', life: 3000 })
    editDialog.value = false
    await obtenerPost()
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar la acción', life: 3000 })
  } finally {
    loading.value = false
  }
}

// ========= Limpieza =========
function cleanupPreview() {
  for (const url of newImagesPreview.value) {
    URL.revokeObjectURL(url)
  }
  newImagesPreview.value = []
  newImages.value = []
  deletedImageIds.value = new Set()
  coverImageId.value = null
  mainTry.value = 0
  extraImages.value = []
}

// ========= Utils =========
const formatDate = (date) => {
  if (!date) return ''
  const d = new Date(String(date).replace(' ', 'T'))
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

const formatDateRequest = (date) => {
  if (!date) return ''
  const d = new Date(date)
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  const hours = String(d.getHours()).padStart(2, '0')
  const minutes = String(d.getMinutes()).padStart(2, '0')
  const seconds = String(d.getSeconds()).padStart(2, '0')
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`
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

// ========= Lifecycles =========
onMounted(async () => {
  await Promise.all([obtenerPost(), obtenerProductos()])

  // 🔔 show toast after redirect if ?toast=post_created
  const params = new URLSearchParams(window.location.search)
  if (params.get('toast') === 'post_created') {
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Publicación creada correctamente',
      life: 3000
    })
    // clean the URL so it doesn't re-trigger on refresh
    params.delete('toast')
    const newUrl =
      window.location.pathname +
      (params.toString() ? `?${params.toString()}` : '') +
      window.location.hash
    window.history.replaceState({}, '', newUrl)
  }

})

watch(() => props.refresh, () => {
  obtenerPost()
})

watch(selectedProduct, async () => {
  await obtenerCategorias()
  const available = new Set(categories.value.map(c => Number(c.id)))
  editForm.value.category_id = (editForm.value.category_id || []).filter(id => available.has(Number(id)))
})
</script>

<style scoped>
.line-clamp-4 {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  overflow: hidden;
  -webkit-line-clamp: 4;
}

.thumb-img {
  width: 100%;
  height: 80px;
  object-fit: cover;
  display: block;
}

/* Iconitos pequeños */
.btn-icon-xs :deep(.p-button-icon) {
  font-size: 0.7rem;
}

.btn-icon-xs :deep(.p-button) {
  padding: 0.15rem 0.3rem;
}
</style>
