<template>
  <DataTable ref="dt" v-model:selection="selectedPost" :value="posts" dataKey="id" :paginator="true"
    :rows="10" :filters="filters"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    :rowsPerPageOptions="[5, 10, 25]"
    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} posts" class="p-datatable-sm">
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
    <Column selectionMode="multiple" style="width: 3rem" :exportable="false" />
    <Column field="titulo" header="Titulo" sortable />
    <Column field="categories" header="Categorías" sortable>
      <template #body="{ data }">
        <div class="flex flex-wrap gap-2">
          <Tag
            v-for="p in data.categories"
            :key="p.id"
            :value="p.nombre"
            severity="info"
            rounded
          />
        </div>
      </template>
    </Column>
    <Column field="fecha_programada" header="Fecha Programada" sortable>
      <template #body="{ data }">
        {{ formatDate(data.fecha_programada) }}
      </template>
    </Column>
    <Column field="ratings" header="Calificación" sortable>
      <template #body="{ data }">
        <div class="flex items-center space-x-2">
          <Rating 
            :modelValue="getPromedioRating(data.ratings)" 
            readonly 
            :cancel="false" 
          />
          <span>
            ({{ getPromedioRating(data.ratings) }})
          </span>
        </div>
      </template>
    </Column>
    <Column field="state_id" header="Estado" sortable>
      <template #body="{ data }">
        <Tag 
          :value="getEstadoLabel(data.state_id)" 
          :severity="getEstadoSeverity(data.state_id)" 
          rounded
        />
      </template>
    </Column>
    <Column field="user" header="Creado Por" sortable>
      <template #body="{ data }">
        <span>
          {{ data.user?.name || 'Sin asignar' }}
        </span>
      </template>
    </Column>
    <Column field="updated_user" header="Modificado Por" sortable>
      <template #body="{ data }">
        <span>
          {{ data.updated_user?.name || 'Sin modificar' }}
        </span>
      </template>
    </Column>
    <!-- <Column header="" style="width: 3rem;">
      <template #body="{ data }">
        <div>
          <Button icon="pi pi-ellipsis-v" rounded text @click="toggleMenu($event, data)" />
        </div>
      </template>
    </Column> -->
  </DataTable>

  <Menu ref="menu" :model="menuItems" popup />
  <ConfigPost ref="configDialog" />

  <VerDialog ref="viewDialogRef" />

  <Dialog v-model:visible="editDialog" :style="{ width: '600px' }" header="Editar Publicación" :modal="true">
        <div class="flex flex-col gap-6">
            <div>
                <label class="block font-bold mb-3">Titulo <span class="text-red-500">*</span></label>
                <InputText v-model="editForm.titulo" :useGrouping="false" placeholder="Ingresa el titulo" inputId="titulo" class="w-full" />
            </div>

            <div>
                <label class="block font-bold mb-3">Categoría(s) <span class="text-red-500">*</span></label>
                <MultiSelect
                  v-model="editForm.products"
                  display="chip"
                  :options="products"
                  optionLabel="nombre"
                  optionValue="id"
                  filter
                  placeholder="Seleccione la categoría"
                  :maxSelectedLabels="3"
                  class="w-full"
                />
            </div>

            <!-- <div>
              <label class="block font-bold mb-3">Categoría <span class="text-red-500">*</span></label>
              <Select v-model="editForm.product_id" :options="products" optionLabel="nombre" optionValue="id" placeholder="Seleccione la categoría" class="w-full" />
            </div> -->
            <div>
                <label class="block font-bold mb-3">Resumen <span class="text-red-500">*</span></label>
                <InputText v-model="editForm.resumen" :useGrouping="false" placeholder="Ingresa el resumen" inputId="resumen" class="w-full" />
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
                <label class="block font-bold mb-3">Imagén para mostrar <span class="text-red-500">*</span></label>
                <FileUpload
                    mode="advanced"
                    name="img"
                    accept=".jpg"
                    :auto="true"
                    customUpload
                    :maxFileSize="10000000"
                    @uploader="onUploadImage"
                    :chooseLabel="'Seleccionar Imagen'"
                    :uploadLabel="'Subir'"
                    :cancelLabel="'Cancelar'"
                    class="w-full"
                />
            </div>
        </div>

        <!-- Botones -->
        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" text @click="editDialog = false" severity="secondary"/>
            <Button label="Guardar" icon="pi pi-check" @click="actualizarPost" severity="contrast"/>
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
import Rating from 'primevue/rating'

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
// Diálogos
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
  //product_id: null,
  products: null,
  resumen: '',
  contenido: '',
  fecha_programada: null,
})
const products = ref([])
const archivoImg = ref(null)

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

const props = defineProps({ refresh: Number })

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
    //product_id: item.product_id,
    products: item.products.map(p => Number(p.id)),
    resumen: item.resumen,
    contenido: item.contenido,
    fecha_programada: item.fecha_programada ? new Date(item.fecha_programada) : null,
  }

  editDialog.value = true


  console.log('i<zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz>')
  console.log(editForm.value.products)
  console.log('i<zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz>')
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
        await axios.delete(`api/blog/eliminar/${item.id}`)
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
  try {
    loading.value = true

    await axios.get(`/api/blog/publicar/${item.id}/2`)

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
    //formData.append('product_id', editForm.value.product_id)
    formData.append('product_id', JSON.stringify(editForm.value.products))
    formData.append('resumen', editForm.value.resumen)
    formData.append('contenido', editForm.value.contenido)
    formData.append('fecha_programada', editForm.value.fecha_programada.toISOString().split('T')[0])
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
    console.log(res.data.posts)
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
    products.value = res.data
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar posts',
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
  if (!ratings || ratings.length === 0) return 0;
  const total = ratings.reduce((sum, rating) => {
    return sum + parseFloat(rating.estrellas);
  }, 0);

  return total / ratings.length;
}

onMounted(() => {
  obtenerPost()
  obtenerProductos()
})

watch(() => props.refresh, () => {
  obtenerPost()
})

watch(() => products.refresh, () => {
  obtenerProductos()
})
</script>