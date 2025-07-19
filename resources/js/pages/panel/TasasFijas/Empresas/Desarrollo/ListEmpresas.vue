<template>
  <DataTable ref="dt" v-model:selection="selectedCooperativas" :value="cooperativas" dataKey="id" :paginator="true"
    :rows="10" :filters="filters"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    :rowsPerPageOptions="[5, 10, 25]"
    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} cooperativas" class="p-datatable-sm">
    <template #header>
      <div class="flex flex-wrap gap-2 items-center justify-between">
        <h4 class="m-0">Cooperativas
          <Tag severity="contrast" :value="cooperativas.length" />
        </h4>
        <IconField>
          <InputIcon><i class="pi pi-search" /></InputIcon>
          <InputText v-model="filters['global'].value" placeholder="Buscar..." />
        </IconField>
      </div>
    </template>
    <Column selectionMode="multiple" style="width: 3rem" :exportable="false" />
    <Column field="nombre" header="Nombre" sortable style="min-width: 20rem" />
    <Column field="ruc" header="RUC" sortable style="min-width: 8rem" />
    <Column field="direccion" header="Dirección" sortable style="min-width: 20rem" />
    <Column field="telefono" header="Teléfono" sortable style="min-width: 8rem" />
    <Column field="email" header="Email" sortable style="min-width: 20rem" />
    <Column field="tipo_entidad" header="Tipo" sortable style="min-width: 8rem" />
    <Column field="estado" header="Estado" sortable style="min-width: 8rem">
      <template #body="{ data }">
        <Tag :severity="data.estado === 'activo' ? 'success' : 'danger'"
          :value="data.estado === 'activo' ? 'Activo' : 'Inactivo'" />
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
  <ConfigEmpresa ref="configDialog" />

  <VerDialog ref="viewDialogRef" />

  <!-- Dialog para editar cooperativa -->
  <Dialog v-model:visible="editDialog" :style="{ width: '600px' }" header="Editar Cooperativa" :modal="true"
    class="p-fluid">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
      <div class="field">
        <label for="nombre" class="block font-bold mb-3">Nombre <span class="text-red-500">*</span></label>
        <InputText id="nombre" v-model="editForm.nombre" required fluid />
      </div>
      <div class="field">
        <label for="ruc" class="block font-bold mb-3">RUC <span class="text-red-500">*</span></label>
        <InputText id="ruc" v-model="editForm.ruc" required fluid />
      </div>

      <div class="field">
        <label for="direccion" class="block font-bold mb-3">Dirección <span class="text-red-500">*</span></label>
        <InputText id="direccion" v-model="editForm.direccion" required fluid />
      </div>
      <div class="field">
        <label for="telefono" class="block font-bold mb-3">Teléfono <span class="text-red-500">*</span></label>
        <InputText id="telefono" v-model="editForm.telefono" fluid />
      </div>

      <div class="field">
        <label for="email" class="block font-bold mb-3">Email <span class="text-red-500">*</span></label>
        <InputText id="email" v-model="editForm.email" type="email" fluid />
      </div>
      <div class="field">
        <label for="tipo_entidad" class="block font-bold mb-3">Tipo de Entidad <span class="text-red-500">*</span></label>
        <InputText id="tipo_entidad" v-model="editForm.tipo_entidad" fluid />
      </div>

      <div class="field md:col-span-2">
        <label for="estado" class="block font-bold mb-3">Estado <span class="text-red-500">*</span></label>
        <Select v-model="editForm.estado" :options="estadoOptions" optionLabel="label" optionValue="value"
          placeholder="Seleccione un estado" fluid />
      </div>

      <div class="field md:col-span-2">
        <label for="pdf" class="block font-bold mb-3">PDF (opcional)</label>
        <FileUpload mode="basic" accept="application/pdf" :maxFileSize="2048000" customUpload @select="onFileSelect"
          :auto="false" chooseLabel="Seleccionar PDF" class="w-full" />
        <small class="p-error block mt-1" v-if="editForm.pdf">
          Archivo seleccionado: {{ editForm.pdf.name }}
        </small>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-2">
        <Button label="Cancelar" icon="pi pi-times" severity="secondary" text @click="editDialog = false" />
        <Button label="Guardar" icon="pi pi-check" severity="contrast" :loading="loading" @click="actualizarCooperativa" />
      </div>
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
import ConfigEmpresa from './ConfigEmpresa.vue'
import VerDialog from './ver.vue'
import Select from 'primevue/select'

const toast = useToast()
const confirm = useConfirm()
const dt = ref()
const menu = ref()
const configDialog = ref(null)
const cooperativas = ref([])
const selectedCooperativas = ref([])
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
  nombre: '',
  ruc: '',
  direccion: '',
  telefono: '',
  email: '',
  tipo_entidad: '',
  estado: 'activo',
  pdf: null
})

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

const props = defineProps({ refresh: Number })

const menuItems = ref([
  { label: 'Ver', icon: 'pi pi-eye', command: () => ver(selectedItem.value) },
  { label: 'Ver PDF', icon: 'pi pi-file-pdf', command: () => verPDF(selectedItem.value) },
  { label: 'Editar', icon: 'pi pi-pencil', command: () => editar(selectedItem.value) },
  { label: 'Eliminar', icon: 'pi pi-trash', command: () => eliminar(selectedItem.value) },
  { label: 'Configurar', icon: 'pi pi-cog', command: () => configurar(selectedItem.value) }
])

function toggleMenu(event, item) {
  selectedItem.value = item

  menuItems.value = [
    { label: 'Ver', icon: 'pi pi-eye', command: () => ver(item) },
    {
      label: 'Ver PDF',
      icon: 'pi pi-file-pdf',
      command: () => verPDF(item),
      disabled: !item.pdf_url
    },
    { label: 'Editar', icon: 'pi pi-pencil', command: () => editar(item) },
    { label: 'Eliminar', icon: 'pi pi-trash', command: () => eliminar(item) },
    { label: 'Configurar', icon: 'pi pi-cog', command: () => configurar(item) }
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
    nombre: item.nombre,
    ruc: item.ruc,
    direccion: item.direccion,
    telefono: item.telefono,
    email: item.email,
    tipo_entidad: item.tipo_entidad,
    estado: item.estado,
    pdf: null
  }
  editDialog.value = true
}

function eliminar(item) {
  confirm.require({
    message: `¿Estás seguro de que quieres eliminar la cooperativa "${item.nombre}"?`,
    header: 'Confirmar eliminación',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Sí, eliminar',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        loading.value = true
        await axios.delete(`/coperativa/${item.id}`)
        toast.add({
          severity: 'success',
          summary: 'Éxito',
          detail: 'Cooperativa eliminada correctamente',
          life: 3000
        })
        obtenerCooperativas()
      } catch (error) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'No se pudo eliminar la cooperativa',
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

function verPDF(item) {
  if (!item.pdf_url) {
    toast.add({
      severity: 'warn',
      summary: 'Advertencia',
      detail: 'Esta cooperativa no tiene un PDF asociado',
      life: 3000
    })
    return
  }

  const url = `/coperativa/${item.id}/pdf`
  window.open(url, '_blank') // Abre en nueva pestaña
}

function onFileSelect(event) {
  editForm.value.pdf = event.files[0]
}

async function actualizarCooperativa() {
  try {
    loading.value = true

    const formData = new FormData()
    formData.append('_method', 'PUT')
    formData.append('nombre', editForm.value.nombre)
    formData.append('ruc', editForm.value.ruc)
    formData.append('direccion', editForm.value.direccion)
    formData.append('telefono', editForm.value.telefono)
    formData.append('email', editForm.value.email)
    formData.append('tipo_entidad', editForm.value.tipo_entidad)
    formData.append('estado', editForm.value.estado)

    if (editForm.value.pdf) {
      formData.append('pdf', editForm.value.pdf)
    }

    await axios.post(`/coperativa/${editForm.value.id}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Cooperativa actualizada correctamente',
      life: 3000
    })

    editDialog.value = false
    obtenerCooperativas()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo actualizar la cooperativa',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

async function obtenerCooperativas() {
  try {
    const res = await axios.get('/coperativa')
    cooperativas.value = res.data.data
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar cooperativas',
      life: 3000
    })
  }
}

onMounted(() => {
  obtenerCooperativas()
})

watch(() => props.refresh, () => {
  obtenerCooperativas()
})
</script>