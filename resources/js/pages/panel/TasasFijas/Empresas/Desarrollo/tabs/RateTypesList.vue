<template>
  <div>
    <h6 class="mb-3 font-semibold">Tipos de tasa registrados</h6>

    <DataTable :value="tipos" stripedRows class="p-datatable-sm mb-4">
      <Column field="nombre" header="Nombre" />
      <Column field="descripcion" header="Descripción" />
      <Column header="Acciones">
        <template #body="{ data }">
          <Button icon="pi pi-trash" severity="danger" text @click="eliminar(data.id)" />
        </template>
      </Column>
    </DataTable>

    <Divider />

    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
      <div>
        <label class="text-sm font-medium">Nombre</label>
        <InputText v-model="form.nombre" class="w-full" />
      </div>
      <div>
        <label class="text-sm font-medium">Descripción</label>
        <InputText v-model="form.descripcion" class="w-full" />
      </div>
      <div class="flex items-end">
        <Button label="Agregar" icon="pi pi-plus" @click="agregar" :loading="loading" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Divider from 'primevue/divider'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'

const toast = useToast()
const tipos = ref([])
const form = ref({ nombre: '', descripcion: '' })
const loading = ref(false)

async function cargar() {
  const res = await axios.get('/api/rate-types')
  tipos.value = res.data
}

async function agregar() {
  loading.value = true
  try {
    await axios.post('/api/rate-types', form.value)
    toast.add({ summary: 'Tipo agregado', severity: 'success', life: 3000 })
    form.value.nombre = ''
    form.value.descripcion = ''
    await cargar()
  } finally {
    loading.value = false
  }
}

async function eliminar(id) {
  await axios.delete(`/api/rate-types/${id}`)
  await cargar()
  toast.add({ summary: 'Tipo eliminado', severity: 'info', life: 3000 })
}

onMounted(cargar)
</script>
