 <template>
  <DataTable ref="dt" v-model:selection="selectedProducts" :value="categoriesArray" dataKey="id" :paginator="true" :loading="loading"
    :rows="10" :filters="filters"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    :rowsPerPageOptions="[5, 10, 25]"
    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} categories" class="p-datatable-sm">
    <template #header>
      <div class="flex flex-wrap gap-2 items-center justify-between">
        <h4 class="m-0">Categorias
          <Tag severity="contrast" :value="categoriesCount" />
        </h4>
        <IconField>
          <InputIcon><i class="pi pi-search" /></InputIcon>
          <InputText v-model="filters['global'].value" placeholder="Buscar..." />
        </IconField>
      </div>
    </template>
    <Column selectionMode="multiple" style="width: 3rem" :exportable="false" />
    <Column field="products" header="Tipo de Producto" sortable>
      <template #body="{ data }">
        <span>
          {{ data.products[0]?.nombre || 'Sin asignar' }}
        </span>
      </template>
    </Column>
    <Column field="nombre" header="Nombre de Categoría" sortable />
    
    <Column header="" style="width: 3rem;">
      <template #body="{ data }">
        <div>
          <Button icon="pi pi-ellipsis-v" rounded text @click="toggleMenu($event, data)" />
        </div>
      </template>
    </Column>
  </DataTable>

  <Menu ref="menu" :model="menuItems" popup />
  <ConfigPost ref="configDialog" />

  <VerDialog ref="viewDialogRef" />

  <!-- <Dialog v-model:visible="editDialog" :style="{ width: '600px' }" header="Editar Publicación" :modal="true">
      <div class="flex flex-col gap-6">
          <div>
              <label class="block font-bold mb-3">Nombre <span class="text-red-500">*</span></label>
              <InputText v-model="editForm.nombre" :useGrouping="false" placeholder="Ingresa el nombre" inputId="nombre" class="w-full" />
          </div>
          <div>
              <label class="block font-bold mb-3">Descripción <span class="text-red-500">*</span></label>
              <InputText v-model="editForm.descripcion" :useGrouping="false" placeholder="Ingresa el descripción" inputId="descripcion" class="w-full" />
          </div>
      </div>

      <template #footer>
          <Button label="Cancelar" icon="pi pi-times" text @click="editDialog = false" severity="secondary"/>
          <Button label="Guardar" icon="pi pi-check" @click="actualizarCategoria" severity="contrast"/>
      </template>
  </Dialog> -->

  <ConfirmDialog />
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
import VerDialog from './ver.vue'
import Select from 'primevue/select'
import ConfigPost from './ConfigPost.vue'

const toast = useToast()
const confirm = useConfirm()
const dt = ref()
const menu = ref()
const configDialog = ref(null)
const posts = ref([])
const selectedProducts = ref([])
const selectedItem = ref(null)
const loading = ref(false)
const viewDialogRef = ref(null)
const editDialog = ref(false)
const viewDialog = ref(false)



// Formulario de edición
const editForm = ref({
  nombre: '',
  descripcion: '',
})

const categories = ref([])
// Always give the DataTable a real array
const categoriesArray = computed(() =>
  Array.isArray(categories.value)
    ? categories.value
    : (Array.isArray(categories.value?.data) ? categories.value.data : [])
)
const categoriesCount = computed(() => categoriesArray.value.length)

const selectedCategory = ref([])


const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

const props = defineProps({ 
  refresh: Number
})

const menuItems = ref([
  //{ label: 'Editar', icon: 'pi pi-pencil', command: () => editar(selectedItem.value) },
  { label: 'Eliminar', icon: 'pi pi-trash', command: () => eliminar(selectedItem.value) },
])

function toggleMenu(event, item) {
  selectedItem.value = item

  menuItems.value = [
    //{ label: 'Editar', icon: 'pi pi-pencil', command: () => editar(item) },
    { label: 'Eliminar', icon: 'pi pi-trash', command: () => eliminar(item) },
  ]

  menu.value.toggle(event)
}
/*
function editar(item) {

  selectedItem.value = item
  editForm.value = {
    id: item.id,
    nombre: item.nombre,
    descripcion: item.descripcion,
  }

  editDialog.value = true
}*/

function eliminar(item) {
  console.log(item.id)
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
        await axios.get(`/api/blog/eliminar-categoria/${item.id}`)
        toast.add({
          severity: 'success',
          summary: 'Éxito',
          detail: 'Publicación eliminada correctamente',
          life: 3000
        })
        obtenerCategorias()
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
/*
async function actualizarCategoria() {
  try {
    loading.value = true

    const formData = new FormData()
    formData.append('nombre', editForm.value.nombre)
    formData.append('descripcion', editForm.value.descripcion)

    console.log(editForm.value.id)

    await axios.post(`/api/blog/actualizar-categoria/${editForm.value.id}`, formData, {
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
    obtenerCategorias()
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
}*/


async function obtenerCategorias() {
  try {
    const res = await axios.get('/api/blog/listar-categoria')
    categories.value = Array.isArray(res.data) ? res.data : (res.data?.data ?? [])
    console.log(categories.value)
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar categorias',
      life: 3000
    })
  }
}

onMounted(() => {
  obtenerCategorias()
})

watch(() => props.refresh, () => {
  obtenerCategorias()
})
</script>