<template>
  <Dialog v-model:visible="visible" :style="{ width: '80vw' }" header="Detalle de Cooperativa" :modal="true" class="p-fluid">
    <template v-if="loading">
      <div class="text-center py-6">
        <i class="pi pi-spin pi-spinner text-3xl"></i>
        <p class="mt-2">Cargando datos...</p>
      </div>
    </template>

    <template v-else-if="data">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label class="font-semibold">Empresa:</label>
          <p>{{ data.nombre }}</p>
        </div>
        <div>
          <label class="font-semibold">Monto mínimo:</label>
          <p>{{ data.desde }}</p>
        </div>
        <div>
          <label class="font-semibold">Monto máximo:</label>
          <p>{{ data.hasta }}</p>
        </div>
        <div>
          <label class="font-semibold">RUC:</label>
          <p>{{ data.ruc }}</p>
        </div>
        <div>
          <label class="font-semibold">Estado:</label>
          <p>{{ data.estado }}</p>
        </div>
      </div>

      <!-- Tabla de tasas -->
      <div>
        <div v-for="(rangosPorMoneda, moneda) in matriz" :key="moneda" class="mb-6">
          <h6 class="font-bold mb-2">
            Moneda: {{ moneda === 'PEN' ? 'Soles (PEN)' : 'Dólares (USD)' }}
          </h6>
          <DataTable :value="rangosPorMoneda" stripedRows class="p-datatable-sm" responsiveLayout="scroll">
            <Column field="rango" header="Rango de Monto" sortable style="min-width: 15rem" />
            <Column v-for="plazo in plazos" :key="plazo.id" :header="plazo.nombre" sortable style="min-width: 3rem">
              <template #body="{ data }">
                <div class="flex align-items-center gap-2">
                  <InputNumber v-model="data.tasas[plazo.id].valor"
                    :disabled="!camposEditables[`${data.rangoId}-${plazo.id}`]" mode="decimal" :min="0" :max="100"
                    :minFractionDigits="2" :maxFractionDigits="2" suffix="%" class="w-6rem" size="small"
                    :placeholder="data.tasas[plazo.id].valor ? '' : '0.00%'" />
                </div>
              </template>
            </Column>
          </DataTable>
        </div>
      </div>
    </template>

    <template v-else>
      <p class="text-center py-6 text-gray-500">No se encontraron datos para esta cooperativa.</p>
    </template>

    <template #footer>
      <Button label="Cerrar" icon="pi pi-times" text @click="visible = false" severity="secondary" />
    </template>
  </Dialog>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputNumber from 'primevue/inputnumber'
import { useToast } from 'primevue/usetoast'

const visible = ref(false)
const data = ref(null)
const loading = ref(false)
const matriz = ref({})
const plazos = ref([])
const camposEditables = ref({})
const valoresOriginales = ref({})
const toast = useToast()

function open(item) {
  visible.value = true
  loading.value = true
  data.value = null
  matriz.value = {}

  axios.get(`/amount-ranges/${item.id}`)
    .then(response => {
      data.value = response.data.data[0]
    })
    .catch(error => {
      console.error('Error al cargar detalles:', error)
    })
    .finally(() => {
      loading.value = false
    })

  cargarMatriz(item.id)
}

function cargarMatriz(id) {
  const empresaId = id ?? data.value?.id
  if (!empresaId) return

  axios.get(`/fixed-term-matrix/${empresaId}`)
    .then(res => {
      matriz.value = res.data.matriz
      plazos.value = res.data.plazos
      camposEditables.value = {}
      valoresOriginales.value = {}
    })
    .catch(() => {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'No se pudo cargar la matriz de tasas',
        life: 3000,
      })
    })
}

defineExpose({ open })
</script>
