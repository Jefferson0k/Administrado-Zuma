<template>
  <Card class="mb-4">
  <template #title>
    <i class="pi pi-calculator"></i>
    Simulador
  </template>

  <template #content>
    <!-- Fila horizontal -->
    <div class="flex items-end justify-between gap-3 flex-wrap">
      <!-- Label + Input -->
      <div class="flex flex-col flex-grow">
        <label for="amount" class="mb-2">Monto de Inversión (S/)</label>
        <InputNumber
          id="amount"
          v-model="simulationForm.amount"
          mode="currency"
          currency="PEN"
          locale="es-PE"
          :min="100"
          :max="1000000"
          placeholder="Ingrese el monto"
          @input="onAmountChange"
        />
      </div>

      <!-- Botón alineado a la derecha -->
      <Button
        label="Simular"
        icon="pi pi-search"
        :loading="loading.simulate"
        :disabled="!simulationForm.amount || simulationForm.amount < 100"
        @click="simulateByAmount"
      />
    </div>

    <!-- Estadísticas Rápidas -->
    <div v-if="simulationResults.length > 0" class="mt-4">
      <Divider align="center" type="dashed">
        <b>Resumen</b>
      </Divider>

      <div class="grid text-center">
        <div class="col-4">
          <div class="text-2xl font-bold text-primary">{{ simulationResults.length }}</div>
          <div class="text-sm text-600">Cooperativas</div>
        </div>
        <div class="col-4">
          <div class="text-2xl font-bold text-green-500">{{ bestRate }}%</div>
          <div class="text-sm text-600">Mejor TEA</div>
        </div>
      </div>
    </div>
  </template>
</Card>

  <Card v-if="selectedRate" class="mb-4">
  <template #title>
    <div class="flex items-center">
      <i class="pi pi-calendar mr-2 text-primary"></i>
      Configurar Cronograma
    </div>
  </template>

  <template #content>
    <!-- Fila con Dropdown, Calendar y Botón -->
    <div class="flex flex-wrap gap-3 items-end">
      <!-- Frecuencia de Pago -->
      <div class="flex flex-col flex-1 min-w-[200px]">
        <label class="block text-900 font-medium mb-2">Frecuencia de Pago</label>
        <Dropdown
          v-model="scheduleForm.paymentFrequencyId"
          :options="paymentFrequencies"
          option-label="nombre"
          option-value="id"
          placeholder="Seleccionar frecuencia"
          class="w-full"
          @change="generateSchedule"
        />
      </div>

      <!-- Fecha de Inicio -->
      <div class="flex flex-col flex-1 min-w-[200px]">
        <label class="block text-900 font-medium mb-2">Fecha de Inicio</label>
        <Calendar
          v-model="scheduleForm.startDate"
          date-format="dd/mm/yy"
          placeholder="Seleccionar fecha"
          class="w-full"
          :min-date="new Date()"
          @date-select="generateSchedule"
        />
      </div>

      <!-- Botón -->
      <div class="flex">
        <Button
          label="Generar Cronograma"
          icon="pi pi-table"
          :loading="loading.schedule"
          :disabled="!selectedRate || !scheduleForm.paymentFrequencyId"
          @click="generateSchedule"
        />
      </div>
    </div>
  </template>
</Card>


  <TabView v-model:activeIndex="activeTab">
    <!-- Tab 1: Mejores Tasas -->
    <TabPanel header="Mejores Tasas" leftIcon="pi pi-star">
      <div v-if="loading.simulate" class="text-center p-4">
        <ProgressSpinner />
        <p class="mt-2">Buscando las mejores tasas...</p>
      </div>

      <div v-else-if="simulationResults.length === 0" class="text-center p-6">
        <i class="pi pi-info-circle text-6xl text-300 mb-3"></i>
        <h3 class="text-600">Ingresa un monto para comenzar</h3>
        <p class="text-500">Te mostraremos las mejores opciones de inversión disponibles</p>
      </div>

      <div v-else>
        <Accordion :multiple="true" :activeIndex="[0]">
          <AccordionTab v-for="(coop, index) in simulationResults" :key="index"
            :header="`${coop.orden} - ${coop.cooperativa}`">
            <template #headericon>
              <Badge v-if="index === 0" value="MEJOR" severity="success" class="ml-2" />
            </template>

            <div v-for="(tipoTasa, tipoIndex) in coop.tipos_tasa" :key="tipoIndex" class="mb-4">
              <h4 class="text-primary mb-3">{{ tipoTasa.tipo_tasa }}</h4>
              <DataTable
  :value="tipoTasa.tasas"
  size="small"
  :paginator="tipoTasa.tasas.length > 5"
  :rows="5"
  selection-mode="single"
  v-model:selection="selectedRate"
  @row-select="onRateSelect"
  class="p-datatable-sm"
>
  <Column selection-mode="single" header-style="width: 3rem" />

  <Column
    v-for="col in tipoTasa.tipo_columnas.filter(c => !c.toLowerCase().includes('retorno'))"
    :key="col"
    :field="col"
    :header="getColumnHeader(col)"
    :class="getColumnClass(col)"
  >
    <template #body="slotProps">
      <span v-if="col === 'plazo_dias'" class="text-600">
        {{ slotProps.data[col] }} días
      </span>
      <span v-else-if="col.includes('TEA') || col.includes('TEM')" class="font-semibold text-primary">
        {{ slotProps.data[col] }}
      </span>
      <span v-else>{{ slotProps.data[col] }}</span>
    </template>
  </Column>
</DataTable>

            </div>
          </AccordionTab>
        </Accordion>
      </div>
    </TabPanel>

    <!-- Tab 2: Cronograma -->
    <TabPanel header="Cronograma" leftIcon="pi pi-calendar">
      <div v-if="!selectedRate" class="text-center p-6">
        <i class="pi pi-calendar text-6xl text-300 mb-3"></i>
        <h3 class="text-600">Selecciona una tasa</h3>
        <p class="text-500">Elige una opción de la tabla anterior para ver el cronograma detallado</p>
      </div>

      <div v-else-if="loading.schedule" class="text-center p-4">
        <ProgressSpinner />
        <p class="mt-2">Generando cronograma...</p>
      </div>

      <div v-else-if="scheduleData">
        <Card>
  <template #title>
    <i class="pi pi-table mr-2"></i>
    Cronograma de Pagos - {{ scheduleData.frecuencia_pago }}
  </template>

  <template #content>
    <DataTable
      :value="scheduleData.cronograma"
      :paginator="true"
      :rows="10"
      size="small"
      :scrollable="true"
      scroll-height="400px"
    >
      <Column field="numero_pago" header="Mes" class="text-center" sortable style="min-width: 4rem" />

      <Column field="fecha_cronograma" header="Cronograma" sortable style="min-width: 8rem">
        <template #body="slotProps">
          <span>{{ slotProps.data.fecha_cronograma }}</span>
        </template>
      </Column>

      <Column field="fecha_pago" header="Fecha de Pago" sortable style="min-width: 9rem">
        <template #body="slotProps">
          <span>{{ slotProps.data.fecha_pago ?? '-' }}</span>
        </template>
      </Column>

      <Column field="dias_periodo" header="Días" class="text-center" sortable style="min-width: 4rem">
        <template #body="slotProps">
          <span>{{ slotProps.data.dias_periodo ?? '-' }}</span>
        </template>
      </Column>

      <Column field="monto_base" header="Monto base" class="text-right" sortable style="min-width: 8rem">
        <template #body="slotProps">
          <span class="text-900">S/ {{ formatMoney(slotProps.data.monto_base) }}</span>
        </template>
      </Column>

      <Column field="interes_bruto" header="Interés base (S/.)" class="text-right" sortable style="min-width: 10rem">
        <template #body="slotProps">
          <span v-if="slotProps.data.interes_bruto > 0" class="text-green-600">
            S/ {{ formatMoney(slotProps.data.interes_bruto) }}
          </span>
          <span v-else>-</span>
        </template>
      </Column>

      <Column field="impuesto_2da" header="Impuesto 2da categoría" class="text-right" sortable style="min-width: 14rem">
        <template #body="slotProps">
          <span v-if="slotProps.data.impuesto_2da > 0" class="text-red-500">
            S/ {{ formatMoney(slotProps.data.impuesto_2da) }}
          </span>
          <span v-else>-</span>
        </template>
      </Column>

      <Column field="interes_neto" header="Interés a depositar"  sortable style="min-width: 11rem">
        <template #body="slotProps">
          <span v-if="slotProps.data.interes_neto > 0" class="text-blue-600 font-semibold">
            S/ {{ formatMoney(slotProps.data.interes_neto) }}
          </span>
          <span v-else>-</span>
        </template>
      </Column>

      <Column field="devolucion_capital" header="Devolución capital" sortable style="min-width: 11rem">
        <template #body="slotProps">
          <span v-if="slotProps.data.devolucion_capital > 0" class="text-orange-600 font-semibold">
            S/ {{ formatMoney(slotProps.data.devolucion_capital) }}
          </span>
          <span v-else>-</span>
        </template>
      </Column>

      <Column field="saldo_capital" header="Saldo de capital" sortable style="min-width: 10rem">
        <template #body="slotProps">
          <span class="text-900">S/ {{ formatMoney(slotProps.data.saldo_capital) }}</span>
        </template>
      </Column>

      <Column field="total_a_depositar" header="Total a depositar" sortable style="min-width: 10rem">
        <template #body="slotProps">
          <span v-if="slotProps.data.total_a_depositar > 0" class="font-bold text-primary">
            S/ {{ formatMoney(slotProps.data.total_a_depositar) }}
          </span>
          <span v-else>-</span>
        </template>
      </Column>
    </DataTable>
  </template>
</Card>

      </div>
    </TabPanel>

    <!-- Tab 3: Comparar Tasas -->
    <TabPanel header="Comparar" leftIcon="pi pi-chart-line">
      <div class="mb-4">
        <div class="flex gap-3 align-items-end">
          <div class="field">
            <label class="block text-900 font-medium mb-2">Seleccionar Tasas a Comparar (máx. 5)</label>
            <MultiSelect v-model="compareForm.selectedRates" :options="availableRatesForComparison" option-label="label"
              option-value="id" placeholder="Elegir tasas" :max-selected-labels="3" class="w-full md:w-20rem" />
          </div>
          <div class="field">
            <label class="block text-900 font-medium mb-2">Frecuencia</label>
            <Dropdown v-model="compareForm.paymentFrequencyId" :options="paymentFrequencies" option-label="nombre"
              option-value="id" placeholder="Frecuencia" class="w-full md:w-12rem" />
          </div>
          <Button label="Comparar" icon="pi pi-chart-bar" :loading="loading.compare"
            :disabled="compareForm.selectedRates.length < 2 || !compareForm.paymentFrequencyId" @click="compareRates" />
        </div>
      </div>

      <div v-if="loading.compare" class="text-center p-4">
        <ProgressSpinner />
        <p class="mt-2">Comparando tasas...</p>
      </div>

      <div v-else-if="comparisonResults.length > 0">
        <DataTable :value="comparisonResults" :row-hover="true" size="small" responsive-layout="scroll">
          <Column field="cooperativa" header="Cooperativa" class="font-semibold">
            <template #body="slotProps">
              <div class="flex align-items-center">
                <Badge v-if="slotProps.index === 0" value="MEJOR" severity="success" size="small" class="mr-2" />
                {{ slotProps.data.cooperativa }}
              </div>
            </template>
          </Column>

          <Column field="tea" header="TEA" class="text-center">
            <template #body="slotProps">
              <span class="font-semibold text-primary">{{ slotProps.data.tea }}%</span>
            </template>
          </Column>

          <Column field="rentabilidad_neta" header="Rentabilidad" class="text-center">
            <template #body="slotProps">
              <span class="font-semibold text-green-600">{{ slotProps.data.rentabilidad_neta.toFixed(2) }}%</span>
            </template>
          </Column>

          <Column field="total_interes_neto" header="Interés Neto" class="text-right">
            <template #body="slotProps">
              <span class="text-green-600">S/ {{ formatMoney(slotProps.data.total_interes_neto) }}</span>
            </template>
          </Column>

          <Column field="total_a_recibir" header="Total a Recibir" class="text-right">
            <template #body="slotProps">
              <span class="font-bold text-primary">S/ {{ formatMoney(slotProps.data.total_a_recibir) }}</span>
            </template>
          </Column>

          <Column header="Acciones" class="text-center" style="width: 100px">
            <template #body="slotProps">
              <Button icon="pi pi-eye" size="small" outlined @click="viewDetailedSchedule(slotProps.data)" />
            </template>
          </Column>
        </DataTable>
      </div>

      <div v-else class="text-center p-6">
        <i class="pi pi-chart-line text-6xl text-300 mb-3"></i>
        <h3 class="text-600">Comparar múltiples opciones</h3>
        <p class="text-500">Selecciona 2 o más tasas para ver una comparación detallada</p>
      </div>
    </TabPanel>
  </TabView>
  <Toast />
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

// Imports de componentes PrimeVue
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputNumber from 'primevue/inputnumber'
import Divider from 'primevue/divider'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import ProgressSpinner from 'primevue/progressspinner'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import Slider from 'primevue/slider'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Accordion from 'primevue/accordion'
import AccordionTab from 'primevue/accordiontab'
import Badge from 'primevue/badge'
import MultiSelect from 'primevue/multiselect'
import Toast from 'primevue/toast'

// Composables
const toast = useToast()

// Reactive data
const activeTab = ref(0)
const loading = ref({
  simulate: false,
  schedule: false,
  compare: false,
  export: false
})

// Formularios
const simulationForm = ref({
  amount: null
})

const scheduleForm = ref({
  paymentFrequencyId: null,
  startDate: new Date(),
  taxRate: 0.05
})

const compareForm = ref({
  selectedRates: [],
  paymentFrequencyId: null
})

// Datos
const simulationResults = ref([])
const paymentFrequencies = ref([])
const selectedRate = ref(null)
const scheduleData = ref(null)
const comparisonResults = ref([])

// Computed properties
const bestRate = computed(() => {
  if (simulationResults.value.length === 0) return 0

  let maxRate = 0
  simulationResults.value.forEach(coop => {
    coop.tipos_tasa.forEach(tipo => {
      tipo.tasas.forEach(tasa => {
        const rate = extractRateValue(tasa)
        if (rate > maxRate) maxRate = rate
      })
    })
  })
  return maxRate.toFixed(2)
})

const totalOptions = computed(() => {
  let total = 0
  simulationResults.value.forEach(coop => {
    coop.tipos_tasa.forEach(tipo => {
      total += tipo.tasas.length
    })
  })
  return total
})

const availableRatesForComparison = computed(() => {
  const rates = []
  simulationResults.value.forEach(coop => {
    coop.tipos_tasa.forEach(tipo => {
      tipo.tasas.forEach(tasa => {
        rates.push({
          id: tasa.id,
          label: `${coop.cooperativa} - ${tipo.tipo_tasa} (${extractRateValue(tasa)}%)`,
          cooperativa: coop.cooperativa,
          tipo_tasa: tipo.tipo_tasa
        })
      })
    })
  })
  return rates
})

// Utility functions
const extractRateValue = (tasa) => {
  // Buscar en las propiedades de la tasa un valor numérico que represente la TEA
  for (const [key, value] of Object.entries(tasa)) {
    if (key.includes('TEA') && typeof value === 'string') {
      const match = value.match(/(\d+\.?\d*)/)
      if (match) return parseFloat(match[1])
    }
  }
  return 0
}

const getColumnHeader = (col) => {
  const headers = {
    'plazo_dias': 'Plazo',
    'TEA': 'TEA',
    'TEM': 'TEM',
    'retorno_total': 'Retorno Total',
    'retorno_neto': 'Retorno Neto'
  }
  return headers[col] || col.replace('_', ' ').toUpperCase()
}

const getColumnClass = (col) => {
  if (col.includes('retorno')) return 'text-right'
  if (col.includes('TEA') || col.includes('TEM')) return 'text-right'
  return ''
}

const formatMoney = (amount) => {
  if (!amount) return '0.00'
  return new Intl.NumberFormat('es-PE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount)
}

const formatDate = (date) => {
  if (!date) return null
  return date.toISOString().split('T')[0]
}

// Event handlers
const onAmountChange = () => {
  // Limpiar resultados cuando cambie el monto
  if (!simulationForm.value.amount) {
    simulationResults.value = []
    selectedRate.value = null
    scheduleData.value = null
    comparisonResults.value = []
  }
}

const onRateSelect = (event) => {
  selectedRate.value = event.data
  scheduleData.value = null // Limpiar cronograma anterior

  toast.add({
    severity: 'info',
    summary: 'Tasa Seleccionada',
    detail: 'Configure la frecuencia de pago para generar el cronograma',
    life: 3000
  })
}

const viewDetailedSchedule = async (rateData) => {
  selectedRate.value = rateData
  activeTab.value = 1 // Cambiar al tab de cronograma

  // Generar cronograma automáticamente si hay frecuencia seleccionada
  if (scheduleForm.value.paymentFrequencyId) {
    await generateSchedule()
  }
}

// Methods
const simulateByAmount = async () => {
  if (!simulationForm.value.amount || simulationForm.value.amount < 100) {
    toast.add({
      severity: 'warn',
      summary: 'Atención',
      detail: 'Ingrese un monto válido (mínimo S/ 100)',
      life: 3000
    })
    return
  }

  loading.value.simulate = true

  try {
    const response = await axios.post('/api/investments/simulate-by-amount', {
      amount: simulationForm.value.amount
    })

    if (response.data.success) {
      simulationResults.value = response.data.data
      selectedRate.value = null
      scheduleData.value = null
      comparisonResults.value = []

      toast.add({
        severity: 'success',
        summary: 'Éxito',
        detail: `Se encontraron ${simulationResults.value.length} opciones`,
        life: 3000
      })
    }
  } catch (error) {
    console.error('Error simulando:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al buscar tasas',
      life: 5000
    })
  } finally {
    loading.value.simulate = false
  }
}

const generateSchedule = async () => {
  if (!selectedRate.value || !scheduleForm.value.paymentFrequencyId) {
    toast.add({
      severity: 'warn',
      summary: 'Atención',
      detail: 'Seleccione una tasa y configure la frecuencia de pago',
      life: 3000
    })
    return
  }

  loading.value.schedule = true

  try {
    const response = await axios.post('/api/investments/generate-schedule', {
      rate_id: selectedRate.value.id,
      amount: simulationForm.value.amount,
      payment_frequency_id: scheduleForm.value.paymentFrequencyId,
      start_date: scheduleForm.value.startDate ? formatDate(scheduleForm.value.startDate) : null,
      tax_rate: scheduleForm.value.taxRate
    })

    if (response.data.success) {
      scheduleData.value = response.data.data
      activeTab.value = 1 // Cambiar al tab de cronograma

      toast.add({
        severity: 'success',
        summary: 'Cronograma Generado',
        detail: 'Se ha generado el cronograma de pagos',
        life: 3000
      })
    }
  } catch (error) {
    console.error('Error generando cronograma:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al generar cronograma',
      life: 5000
    })
  } finally {
    loading.value.schedule = false
  }
}

const compareRates = async () => {
  if (compareForm.value.selectedRates.length < 2) {
    toast.add({
      severity: 'warn',
      summary: 'Atención',
      detail: 'Seleccione al menos 2 tasas para comparar',
      life: 3000
    })
    return
  }

  if (!compareForm.value.paymentFrequencyId) {
    toast.add({
      severity: 'warn',
      summary: 'Atención',
      detail: 'Seleccione una frecuencia de pago',
      life: 3000
    })
    return
  }

  loading.value.compare = true

  try {
    const response = await axios.post('/api/investments/compare-rates', {
      rate_ids: compareForm.value.selectedRates,
      amount: simulationForm.value.amount,
      payment_frequency_id: compareForm.value.paymentFrequencyId,
      tax_rate: scheduleForm.value.taxRate
    })

    if (response.data.success) {
      comparisonResults.value = response.data.data.comparisons

      toast.add({
        severity: 'success',
        summary: 'Comparación Lista',
        detail: `Se compararon ${comparisonResults.value.length} opciones`,
        life: 3000
      })
    }
  } catch (error) {
    console.error('Error comparando:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al comparar tasas',
      life: 5000
    })
  } finally {
    loading.value.compare = false
  }
}

const exportSchedule = async (format) => {
  if (!scheduleData.value) {
    toast.add({
      severity: 'warn',
      summary: 'Atención',
      detail: 'No hay cronograma para exportar',
      life: 3000
    })
    return
  }

  loading.value.export = true

  try {
    const response = await axios.post('/api/investments/export-schedule', {
      schedule_data: scheduleData.value,
      format: format
    }, {
      responseType: 'blob'
    })

    // Crear enlace de descarga
    const blob = new Blob([response.data])
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url

    const extension = format === 'excel' ? 'xlsx' : 'pdf'
    const filename = `cronograma_${new Date().toISOString().split('T')[0]}.${extension}`
    link.download = filename

    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)

    toast.add({
      severity: 'success',
      summary: 'Exportado',
      detail: `Cronograma exportado como ${format.toUpperCase()}`,
      life: 3000
    })
  } catch (error) {
    console.error('Error exportando:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Error al exportar el cronograma',
      life: 5000
    })
  } finally {
    loading.value.export = false
  }
}

const loadPaymentFrequencies = async () => {
  try {
    const response = await axios.get('/api/investments/payment-frequencies')
    if (response.data.success) {
      paymentFrequencies.value = response.data.data
    }
  } catch (error) {
    console.error('Error cargando frecuencias:', error)
  }
}

// Watchers
watch(() => scheduleForm.value.taxRate, () => {
  if (scheduleData.value) {
    generateSchedule()
  }
})

watch(() => scheduleForm.value.startDate, () => {
  if (scheduleData.value) {
    generateSchedule()
  }
})

// Lifecycle
onMounted(async () => {
  await loadPaymentFrequencies()

  // Establecer fecha por defecto
  scheduleForm.value.startDate = new Date()
})
</script>