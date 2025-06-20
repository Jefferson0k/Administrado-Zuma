<template>
  <Head title="Simulación" />
  <AppLayout>
    <div class="card space-y-4">
      <div class="text-xl font-semibold mb-2">Simular inversión</div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Monto -->
        <div>
          <label for="amount" class="block mb-1 text-sm">Monto a invertir</label>
          <InputText id="amount" v-model="amount" type="number" placeholder="Ej: 1000" class="w-full" />
        </div>

        <!-- Entidad -->
        <div>
          <label class="block mb-1 text-sm">Entidad financiera</label>
          <Select v-model="selectedEntity" :options="entities" optionLabel="name" placeholder="Selecciona"
            class="w-full" />
        </div>

        <!-- Días -->
        <div>
          <label class="block mb-1 text-sm">Duración (días)</label>
          <Select v-model="selectedDays" :options="daysOptions" optionLabel="name" placeholder="Selecciona"
            class="w-full" />
        </div>

        <!-- Frecuencia -->
        <div>
          <label class="block mb-1 text-sm">Frecuencia de pago</label>
          <Select v-model="selectedFrequency" :options="frequencyOptions" optionLabel="name" placeholder="Selecciona"
            class="w-full" />
        </div>
      </div>

      <!-- Resultado -->
      <div v-if="result" class="mt-6">
        <!-- Resumen -->
        <Panel header="Resumen de Inversión" toggleable collapsed>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm p-2">
            <div><strong>Monto a invertir:</strong> S/ {{ result.monto_invertir }}</div>
            <div><strong>TEA:</strong> {{ result.tea }}</div>
            <div><strong>Días:</strong> {{ result.dias }}</div>
            <div><strong>Tasa total:</strong> {{ result.tasa_retornar }}%</div>
            <div><strong>Monto a retornar:</strong> S/ {{ result.monto_retornar }}</div>
          </div>
        </Panel>

        <!-- Pagos -->
        <div class="mt-4">
          <div class="font-semibold text-lg mb-2">Pagos Programados</div>
          <DataTable :value="result.pagos" :rows="5" paginator responsiveLayout="scroll">
            <Column field="periodo" header="Periodo"></Column>
            <Column field="pago" header="Monto de Pago" :body="row => 'S/ ' + parseFloat(row.pago).toFixed(2)"></Column>
          </DataTable>
        </div>
      </div>

      <!-- Error -->
      <div v-if="error" class="text-red-500 text-sm mt-2">
        {{ error }}
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import AppLayout from '@/layout/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Panel from 'primevue/panel'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import axios from 'axios'

// Form data
const amount = ref('')
const selectedEntity = ref(null)
const selectedDays = ref(null)
const selectedFrequency = ref(null)
const result = ref(null)
const error = ref(null)

// Options
const entities = ref([
  { name: 'COOPAC SAN CRISTOBAL', code: 1 },
])

const daysOptions = ref([
  { name: '90 días', code: 90 },
  { name: '180 días', code: 180 },
  { name: '270 días', code: 270 },
  { name: '360 días', code: 360 },
  { name: '540 días', code: 540 },
  { name: '720 días', code: 720 },
  { name: '1080 días', code: 1080 },
  { name: '1440 días', code: 1440 },
  { name: '1800 días', code: 1800 },
])

const frequencyOptions = ref([
  { name: 'MENSUAL', code: 1 },
  { name: 'BIMESTRAL', code: 2 },
  { name: 'TRIMESTRAL', code: 3 },
])

// Watch to auto-submit when form is filled
watch([amount, selectedEntity, selectedDays, selectedFrequency], async () => {
  if (
    amount.value &&
    selectedEntity.value &&
    selectedDays.value &&
    selectedFrequency.value
  ) {
    try {
      error.value = null
      const response = await axios.post('/calculate-investment', {
        amount: parseFloat(amount.value),
        corporate_entity_id: selectedEntity.value.code,
        days: selectedDays.value.code,
        payment_frequency_id: selectedFrequency.value.code
      })

      result.value = response.data
    } catch (err) {
      error.value = err.response?.data?.error || 'Error al calcular la inversión'
      result.value = null
    }
  }
})
</script>
