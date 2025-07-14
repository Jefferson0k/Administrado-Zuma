<template>
  <div>
    <h5 class="mb-3 font-semibold">Matriz de tasas</h5>

    <div v-for="(rangosPorMoneda, moneda) in matriz" :key="moneda" class="mb-6">
      <h6 class="font-bold mb-2">
        Moneda: {{ moneda === 'PEN' ? 'Soles (PEN)' : 'Dólares (USD)' }}
      </h6>

      <DataTable :value="rangosPorMoneda" stripedRows class="p-datatable-sm" responsiveLayout="scroll">
        <!-- Primera columna: Rango de Monto -->
        <Column field="rango" header="Rango de Monto" />

        <!-- Columnas dinámicas: una por cada plazo -->
        <Column v-for="plazo in plazos" :key="plazo.id" :header="plazo.nombre">
          <template #body="{ data }">
            {{ data.tasas[plazo.id] ? data.tasas[plazo.id] + '%' : '-' }}
          </template>
        </Column>
      </DataTable>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { useToast } from 'primevue/usetoast'

const toast = useToast()

const props = defineProps({
  empresaId: Number,
})

const matriz = ref({})
const plazos = ref([])

async function cargarMatriz() {
  try {
    const res = await axios.get(`/fixed-term-matrix/${props.empresaId}`)
    matriz.value = res.data.matriz
    plazos.value = res.data.plazos
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar la matriz de tasas',
      life: 3000,
    })
  }
}

// Se vuelve a cargar cuando cambia la empresa
watch(() => props.empresaId, () => {
  if (props.empresaId) {
    cargarMatriz()
  }
})

onMounted(() => {
  if (props.empresaId) {
    cargarMatriz()
  }
})
</script>

