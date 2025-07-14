<template>
  <div>
    <DataTable :value="plazos" stripedRows class="p-datatable-sm mb-4">
      <Column field="nombre" header="Nombre" />
      <Column field="dias_minimos" header="Días" />
      <Column header="">
        <template #body="{ data }">
          <Button icon="pi pi-trash" severity="danger" text @click="eliminar(data.id)" />
        </template>
      </Column>
    </DataTable>

    <Divider />

    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
      <div>
        <label class="text-sm font-medium">Días</label>
        <InputNumber v-model="nuevoPlazo" fuild :min="1" />
      </div>
      <div class="flex items-end">
        <Button label="Agregar" icon="pi pi-plus" @click="agregar" severity="contrast" :loading="loading" />
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
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import Divider from 'primevue/divider'

const toast = useToast()
const plazos = ref([])
const nuevoPlazo = ref(null)
const loading = ref(false)

async function cargar() {
  const res = await axios.get('/api/term-plans')
  plazos.value = res.data
}

async function agregar() {
  loading.value = true
  try {
    await axios.post('/term-plans', {
      dias_minimos: nuevoPlazo.value,
      dias_maximos: nuevoPlazo.value,
      nombre: `${nuevoPlazo.value} días`,
    })
    toast.add({ summary: 'Plazo agregado', severity: 'success', life: 3000 })
    nuevoPlazo.value = null
    await cargar()
  } finally {
    loading.value = false
  }
}

async function eliminar(id) {
  await axios.delete(`  /term-plans/${id}`)
  await cargar()
  toast.add({ summary: 'Plazo eliminado', severity: 'info', life: 3000 })
}

onMounted(cargar)
</script>
