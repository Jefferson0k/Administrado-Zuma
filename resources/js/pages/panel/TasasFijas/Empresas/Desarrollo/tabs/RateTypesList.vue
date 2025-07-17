<template>
  <div>
    <!-- Selección de tipo de tasa -->
    <div class="mb-4">
      <h6 class="mb-2 font-semibold">Selecciona el tipo de tasa</h6>
      <div class="flex flex-wrap gap-4">
        <div
          v-for="tipo in tipos"
          :key="tipo.id"
          class="flex items-center gap-2 p-2 border rounded shadow-sm bg-white"
        >
          <Checkbox v-model="tiposSeleccionados" :inputId="`tipo-${tipo.id}`" :value="tipo.id" />
          <label :for="`tipo-${tipo.id}`" class="text-sm">
            {{ tipo.nombre }} — {{ tipo.descripcion }}
          </label>
        </div>
      </div>
    </div>

    <!-- Tabla de tasas por moneda -->
    <div v-for="(rangosPorMoneda, moneda) in matriz" :key="moneda" class="mb-6">
      <h6 class="font-bold mb-2">
        Moneda: {{ moneda === 'PEN' ? 'Soles (PEN)' : 'Dólares (USD)' }}
      </h6>

      <DataTable :value="rangosPorMoneda" stripedRows class="p-datatable-sm" responsiveLayout="scroll">
        <Column field="rango" header="Rango de Monto" />
        <Column v-for="plazo in plazos" :key="plazo.id" :header="plazo.nombre">
          <template #body="{ data }">
            <InputNumber
              v-model="data.tasas[plazo.id]"
              inputClass="w-full"
              :minFractionDigits="2"
              :maxFractionDigits="2"
              suffix="%"
              @blur="guardarTasa(data, plazo.id)"
            />
          </template>
        </Column>
      </DataTable>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import Checkbox from 'primevue/checkbox'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputNumber from 'primevue/inputnumber'
import { useToast } from 'primevue/usetoast'

const props = defineProps({
  empresaId: Number
})

const toast = useToast()
const matriz = ref({})
const plazos = ref([])
const tipos = ref([])
const tiposSeleccionados = ref([])

async function cargarTodo() {
  try {
    const [matrizRes, tiposRes] = await Promise.all([
      axios.get(`/fixed-term-matrix/${props.empresaId}`),
      axios.get('/rate-types')
    ])
    matriz.value = matrizRes.data.matriz
    plazos.value = matrizRes.data.plazos
    tipos.value = tiposRes.data.data
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar la información',
      life: 3000
    })
  }
}

async function guardarTasa(rango, plazoId) {
  const valor = rango.tasas[plazoId]
  if (valor === null || valor === undefined || tiposSeleccionados.value.length === 0) return

  // Guardar por cada tipo de tasa seleccionado
  for (const tipoId of tiposSeleccionados.value) {
    try {
      await axios.post('/fixed-term-rates', {
        corporate_entity_id: props.empresaId,
        amount_range_id: rango.id,
        term_plan_id: plazoId,
        rate_type_id: tipoId,
        valor: valor
      })

      toast.add({
        severity: 'success',
        summary: 'Tasa guardada',
        detail: `Rango ${rango.rango} - ${plazos.value.find(p => p.id === plazoId)?.nombre}`,
        life: 1500
      })
    } catch (err) {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'No se pudo guardar una de las tasas',
        life: 3000
      })
    }
  }
}

watch(() => props.empresaId, () => {
  if (props.empresaId) cargarTodo()
})

onMounted(() => {
  if (props.empresaId) cargarTodo()
})
</script>
