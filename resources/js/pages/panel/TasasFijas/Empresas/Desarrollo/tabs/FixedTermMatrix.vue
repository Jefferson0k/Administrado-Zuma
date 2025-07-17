<template>
  <div>
    <div v-for="(rangosPorMoneda, moneda) in matriz" :key="moneda" class="mb-6">
      <h6 class="font-bold mb-2">
        Moneda: {{ moneda === 'PEN' ? 'Soles (PEN)' : 'Dólares (USD)' }}
      </h6>
      <DataTable :value="rangosPorMoneda" stripedRows class="p-datatable-sm" responsiveLayout="scroll">
        <Column field="rango" header="Rango de Monto" sortable style="min-width: 15rem"/>
        <Column v-for="plazo in plazos" :key="plazo.id" :header="plazo.nombre" sortable style="min-width: 3rem">
          <template #body="{ data }">
            <div class="flex align-items-center gap-2">
              <InputNumber v-model="data.tasas[plazo.id].valor"
                :disabled="!camposEditables[`${data.rangoId}-${plazo.id}`]" mode="decimal" :min="0" :max="100"
                :minFractionDigits="2" :maxFractionDigits="2" suffix="%" class="w-6rem" size="small"
                :placeholder="data.tasas[plazo.id].valor ? '' : '0.00%'" />
              <Button v-if="!camposEditables[`${data.rangoId}-${plazo.id}`]" icon="pi pi-unlock"
                class="p-button-sm p-button-text p-button-secondary" @click="desbloquearCampo(data.rangoId, plazo.id)"
                v-tooltip="'Desbloquear para editar'" />
              <div v-else class="flex gap-1">
                <Button icon="pi pi-check" class="p-button-sm p-button-text p-button-success"
                  @click="actualizarTasa(data.rangoId, plazo.id, data.tasas[plazo.id].valor)" v-tooltip="'Guardar'" />
                <Button icon="pi pi-times" class="p-button-sm p-button-text p-button-danger"
                  @click="cancelarEdicion(data.rangoId, plazo.id)" v-tooltip="'Cancelar'" />
              </div>
            </div>
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
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'

const toast = useToast()

const props = defineProps({
  empresaId: Number,
})

const matriz = ref({})
const plazos = ref([])
const camposEditables = ref({}) // Para controlar qué campos están desbloqueados
const valoresOriginales = ref({}) // Para restaurar valores en caso de cancelar

async function cargarMatriz() {
  try {
    const res = await axios.get(`/fixed-term-matrix/${props.empresaId}`)
    matriz.value = res.data.matriz
    plazos.value = res.data.plazos

    // Reiniciar campos editables
    camposEditables.value = {}
    valoresOriginales.value = {}

  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar la matriz de tasas',
      life: 3000,
    })
  }
}

function desbloquearCampo(rangoId, plazoId) {
  const key = `${rangoId}-${plazoId}`
  camposEditables.value[key] = true

  // Guardar valor original para poder cancelar
  const moneda = Object.keys(matriz.value)[0] // Asumiendo que trabajamos con la primera moneda
  const rango = matriz.value[moneda].find(r => r.rangoId === rangoId)
  valoresOriginales.value[key] = rango.tasas[plazoId].valor
}

function cancelarEdicion(rangoId, plazoId) {
  const key = `${rangoId}-${plazoId}`

  // Restaurar valor original
  const moneda = Object.keys(matriz.value)[0]
  const rango = matriz.value[moneda].find(r => r.rangoId === rangoId)
  rango.tasas[plazoId].valor = valoresOriginales.value[key]

  // Bloquear campo
  camposEditables.value[key] = false
  delete valoresOriginales.value[key]
}

async function actualizarTasa(rangoId, plazoId, nuevoValor) {
  const key = `${rangoId}-${plazoId}`

  try {
    // Encontrar el ID de la tasa para actualizar
    const moneda = Object.keys(matriz.value)[0]
    const rango = matriz.value[moneda].find(r => r.rangoId === rangoId)
    const tasaId = rango.tasas[plazoId].id

    // Hacer la petición de actualización
    await axios.put(`/fixed-term-rates/${tasaId}`, {
      valor: nuevoValor
    })

    // Bloquear campo después de actualizar
    camposEditables.value[key] = false
    delete valoresOriginales.value[key]

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Tasa actualizada correctamente',
      life: 3000,
    })

  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo actualizar la tasa',
      life: 3000,
    })

    // Restaurar valor original en caso de error
    cancelarEdicion(rangoId, plazoId)
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

<style scoped></style>