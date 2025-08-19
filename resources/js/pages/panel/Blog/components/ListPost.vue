<template>
  <DataTable ref="dt" v-model:selection="selectedPost" :value="posts" dataKey="id" :paginator="true" :rows="10"
    :filters="filters"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    :rowsPerPageOptions="[5, 10, 25]" currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} posts"
    class="p-datatable-sm">
    <template #header>
      <div class="flex flex-wrap gap-2 items-center justify-between">
        <h4 class="m-0">Publicaciones
          <Tag severity="contrast" :value="posts.length" />
        </h4>
        <IconField>
          <InputIcon><i class="pi pi-search" /></InputIcon>
          <InputText v-model="filters['global'].value" placeholder="Buscar..." />
        </IconField>
      </div>
    </template>
    <Column field="titulo" header="Titulo" sortable />
    <!--<Column field="resumen" header="Resumen" sortable />-->
    <Column field="contenido" header="Contenido" sortable>
  <template #body="{ data }">
    <div class="line-clamp-4">
      {{ data.contenido }}
    </div>
  </template>
</Column>
    <Column field="categories" header="Categorías" sortable>
      <template #body="{ data }">
        <div class="flex flex-wrap gap-1">
          <Tag v-for="p in data.categories" :key="p.id" :value="p.nombre" severity="info" rounded />
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

  <Dialog v-model:visible="editDialog" :style="{ width: '700px' }" header="Editar Publicación" :modal="true">
    <div class="flex flex-col gap-6">
      <div>
        <label class="block font-bold mb-3">Titulo <span class="text-red-500">*</span></label>
        <InputText v-model="editForm.titulo" :useGrouping="false" placeholder="Ingresa el titulo" inputId="titulo"
          class="w-full" />
      </div>
      <div>
        <label class="block font-bold mb-3">Producto <span class="text-red-500">*</span></label>
        <Select v-model="selectedProduct" :options="products" optionLabel="nombre" optionValue="id"
          placeholder="Seleccione el producto" class="w-full" />
      </div>
      <div>
        <label class="block font-bold mb-3">Categoría(s) <span class="text-red-500">*</span></label>
        <MultiSelect v-model="editForm.category_id" display="chip" :options="categories" optionLabel="nombre"
          optionValue="id" filter placeholder="Seleccione la categoría" :maxSelectedLabels="3" class="w-full" />
      </div>
      <div>
        <label class="block font-bold mb-3">Contenido <span class="text-red-500">*</span></label>
        <QuillEditor v-model:content="editForm.contenido" contentType="html" placeholder="Ingresa el contenido"
          class="w-full" />
      </div>

      <div>
        <label class="block font-bold mb-3">Fecha Programada <span class="text-red-500">*</span></label>
        <Calendar v-model="editForm.fecha_programada" dateFormat="dd/mm/yy" placeholder="Selecciona la fecha" showIcon
          showTime hourFormat="12" class="w-full" />
      </div>

      <div>
        <label class="block font-bold mb-3">Imagén para mostrar <span class="text-red-500">*</span></label>
        <FileUpload mode="advanced" name="img" accept=".jpg" :auto="true" customUpload :maxFileSize="10000000"
          @uploader="onUploadImage" :chooseLabel="'Seleccionar Imagen'" :uploadLabel="'Subir'" :cancelLabel="'Cancelar'"
          class="w-full" />
      </div>
    </div>

    <!-- Botones -->
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
import VerDialog from './ver.vue'
import Select from 'primevue/select'
import ConfigPost from './ConfigPost.vue'

import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'
import MultiSelect from 'primevue/multiselect'
import Calendar from 'primevue/calendar'

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


// Opciones para el dropdown de estado
const estadoOptions = ref([
  { label: 'Activo', value: 'activo' },
  { label: 'Inactivo', value: 'inactivo' }
])

// Formulario de edición
const editForm = ref({
  id: null,
  titulo: '',
  category_id: null,
  products: null,
  resumen: '',
  contenido: '',
  fecha_programada: null,
})

const products = ref([])
const selectedProduct = ref()
const categories = ref([])
const archivoImg = ref(null)

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

const menuItems = ref([
  //{ label: 'Ver', icon: 'pi pi-eye', command: () => ver(selectedItem.value) },
  { label: 'Publicar', icon: 'pi pi-play', command: () => publicar(item) },
  { label: 'Ver imagen', icon: 'pi pi-image', command: () => verImagen(selectedItem.value) },
  { label: 'Editar', icon: 'pi pi-pencil', command: () => editar(selectedItem.value) },
  { label: 'Eliminar', icon: 'pi pi-trash', command: () => eliminar(selectedItem.value) },
])

function toggleMenu(event, item) {
  selectedItem.value = item

  menuItems.value = [
    //{ label: 'Ver', icon: 'pi pi-eye', command: () => ver(item) },
    { label: 'Publicar', icon: 'pi pi-play', command: () => publicar(item) },
    { label: 'Ver imagen', icon: 'pi pi-image', command: () => verImagen(item), disabled: !item.imagen },
    { label: 'Editar', icon: 'pi pi-pencil', command: () => editar(item) },
    { label: 'Eliminar', icon: 'pi pi-trash', command: () => eliminar(item) },
  ]

  menu.value.toggle(event)
}

function ver(item) {
  configDialog.value?.close?.() // si quieres cerrar otros diálogos
  selectedItem.value = item
  viewDialog.value = true
  viewDialogRef.value.open(item) // este es el punto importante
}


function editar(item) {
  selectedItem.value = item
  editForm.value = {
    id: item.id,
    titulo: item.titulo,
    resumen: item.resumen,
    contenido: item.contenido,
    fecha_programada: item.fecha_programada ? new Date(item.fecha_programada.replace(' ', 'T')) : null,
    category_id: item.categories?.map(c => Number(c.id)) || [], // ✅ Cargar IDs de categorías
    product_id: item.product?.id || null,
  }
  editDialog.value = true
}

function eliminar(item) {
  confirm.require({
    message: `¿Estás seguro de que quieres eliminar la publicación"${item.nombre}"?`,
    header: 'Confirmar eliminación',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Sí, eliminar',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        loading.value = true
        await axios.delete(`/api/blog/eliminar/${item.id}`)
        toast.add({
          severity: 'success',
          summary: 'Éxito',
          detail: 'Publicación eliminada correctamente',
          life: 3000
        })
        obtenerPost()
      } catch (error) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'No se pudo eliminar la publicación',
          life: 3000
        })
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
  if (!item.imagen) {
    toast.add({
      severity: 'warn',
      summary: 'Advertencia',
      detail: 'Esta publicación no tiene una imagen asociada',
      life: 3000
    })
    return
  }

  const url = `/image/${item.imagen}`
  window.open(url, '_blank') // Abre en nueva pestaña
}
/* onFileSelect(event) {
  editForm.value.pdf = event.files[0]
}*/


async function publicar(item) {

  //console.log(user.value)
  try {
    loading.value = true

    await axios.get(`/api/blog/publicar/${props.user.id}/${item.id}/2`)

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Publicación realizada correctamente',
      life: 3000
    })

    editDialog.value = false
    obtenerPost()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo actualizar la acción',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

async function actualizarPost() {
  try {
    loading.value = true

    const formData = new FormData()
    formData.append('user_id', 1)
    formData.append('titulo', editForm.value.titulo)
    formData.append('category_id', editForm.value.category_id.join(','))
    formData.append('resumen', editForm.value.resumen)
    formData.append('contenido', editForm.value.contenido)
    formData.append('fecha_programada', formatDateRequest(editForm.value.fecha_programada))
    formData.append('state_id', 1)
    formData.append('imagen', archivoImg.value)

    /*if (editForm.value.pdf) {
      formData.append('pdf', editForm.value.pdf)
    }*/

    await axios.post(`/api/blog/actualizar/${editForm.value.id}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Publicación actualizada correctamente',
      life: 3000
    })

    editDialog.value = false
    obtenerPost()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo actualizar la publicación',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

async function obtenerPost() {
  try {
    const res = await axios.get('/api/blog/lista')
    posts.value = res.data.posts
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar posts',
      life: 3000
    })
  }
}

/*async function obtenerCategorias() {
  try {
    const res = await axios.get('/api/blog/listar-categoria')
    categories.value = res.data
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar categorias',
      life: 3000
    })
  }
}*/

async function obtenerCategorias() {
  try {
    const res = await axios.get(`/api/blog/listar-categoria-filtrada/${selectedProduct.value}`)
    categories.value = res.data
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar categorias',
      life: 3000
    })
  }
}

function onUploadImage(event) {
  const file = event.files[0];
  const allowedTypes = ['image/jpeg', 'image/png'];

  if (file && allowedTypes.includes(file.type)) {
    archivoImg.value = file;
    toast.add({
      severity: 'success',
      summary: 'Imagen cargada',
      detail: `Archivo "${file.name}" listo para enviar`,
      life: 3000
    });
  } else {
    toast.add({
      severity: 'error',
      summary: 'Archivo inválido',
      detail: 'Debe subir un archivo del tipo jpg o png.',
      life: 4000
    });
  }
}

async function obtenerProductos() {
  try {
    const res = await axios.get('/api/blog/productos')
    //products.value = res.data.map(p => ({ ...p, id: Number(p.id) }));
    products.value = res.data
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar productos',
      life: 3000
    })
  }
}

const formatDate = (date) => {
  if (!date) return ''

  const d = new Date(date)
  const day = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const year = d.getFullYear()

  let hours = d.getHours()
  const minutes = String(d.getMinutes()).padStart(2, '0')
  const ampm = hours >= 12 ? 'pm' : 'am'

  hours = hours % 12
  hours = hours ? hours : 12 // 0 debe mostrarse como 12
  hours = String(hours).padStart(2, '0')

  return `${day}/${month}/${year} ${hours}:${minutes} ${ampm}`
}

const formatDateRequest = (date) => {
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


/*
async function obtenerUsuario() {
  try {
    const res = await axios.get('/api/user', {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}` // o desde cookies
      }
    })
    user.value = res.data
    console.log('Usuario ID:', user.value.id)
  } catch (error) {
    console.error('No autenticado')
  }
}*/

onMounted(() => {
  obtenerPost()
  obtenerProductos()
  //obtenerCategorias()
})

watch(() => props.refresh, () => {
  obtenerPost()
})

watch(() => products.refresh, () => {
  obtenerProductos()
})

watch(() => categories.refresh, () => {
  obtenerCategorias()
})

watch(selectedProduct, (newVal, oldVal) => {
  obtenerCategorias()
})
</script>