<template>
  <div class="space-y-6">
    <!-- Selects de rango de monto y tipo de tasa -->
    <div class="flex flex-col md:flex-row md:items-end gap-4">
      <!-- Rango de monto -->
      <div class="flex-1">
        <label class="text-sm font-medium mb-1 block">Seleccione un rango de monto</label>
        <Select v-model="rangoSeleccionado" :options="rangos" optionValue="id" placeholder="Seleccione un rango"
          :filter="true" class="w-full">
          <!-- Cómo se muestra cada opción en la lista -->
          <template #option="{ option }">
            <div class="">
              <span class="font-medium">{{ mostrarRango(option) }}</span>
              <Tag :value="option.estado" :severity="option.estado === 'completo' ? 'success' : 'warn'" />
            </div>
          </template>

          <!-- Cómo se muestra la opción seleccionada -->
          <template #value="{ value }">
            <div v-if="value">
              <span class="font-medium">
                {{mostrarRango(rangos.find(r => r.id === value))}}
              </span>
              <Tag :value="rangos.find(r => r.id === value)?.estado"
                :severity="rangos.find(r => r.id === value)?.estado === 'completo' ? 'success' : 'warn'" class="ml-2" />
            </div>
            <span v-else class="text-gray-400">Seleccione un rango</span>
          </template>
        </Select>

      </div>

      <!-- Tipo de tasa -->
      <div class="w-64">
        <label class="text-sm font-medium mb-1 block">Seleccione un tipo de tasa</label>
        <Select v-model="tipoSeleccionado" :options="tiposTasa" optionValue="id" optionLabel="nombre"
          placeholder="Tipo de tasa" class="w-full" :filter="true">
          <template #option="slotProps">
            {{ slotProps.option.nombre }} - {{ slotProps.option.descripcion }}
          </template>
          <template #value="slotProps">
            <span v-if="slotProps.value">
              {{mostrarTipoTasa(tiposTasa.find(t => t.id === slotProps.value))}}
            </span>
            <span v-else class="text-gray-400">Tipo de tasa</span>
          </template>
        </Select>
      </div>
    </div>

    <!-- Plazos -->
    <div>
      <h6 class="text-sm font-semibold mb-2">Plazos</h6>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
        <div v-for="plazo in plazos" :key="plazo.id" class="flex items-center gap-2  p-2 rounded shadow-sm border">
          <Checkbox v-model="seleccionados" :inputId="`plazo-${plazo.id}`" :value="plazo.id" />
          <label :for="`plazo-${plazo.id}`" class="text-sm">
            {{ plazo.nombre }} ({{ plazo.dias_minimos }} días)
          </label>
        </div>
      </div>
    </div>

    <!-- Botones -->
    <!-- Botones -->
    <div class="flex justify-between items-center mt-4">
      <Button label="Agregar un nuevo plazo" icon="pi pi-plus" severity="secondary" @click="visible = true" />
      <Button label="Registrar" icon="pi pi-save" severity="contrast" @click="guardarPlazos" :loading="guardando" />
    </div>

    <!-- Diálogo para nuevo plazo -->
    <Dialog v-model:visible="visible" modal header="Nuevo Plan" :style="{ width: '450px' }">
      <form @submit.prevent="storeTermPlan">
        <div class="flex flex-col gap-6">
          <div>
            <label for="nombre" class="block font-bold mb-3">Nombre <span class="text-red-500">*</span></label>
            <InputText id="nombre" v-model="form.nombre" fluid required />
          </div>
          <div>
            <label for="dias_minimos" class="block font-bold mb-3">Días mínimos <span
                class="text-red-500">*</span></label>
            <InputText id="dias_minimos" v-model="form.dias_minimos" class="w-full" required />
          </div>
          <div>
            <label for="dias_maximos" class="block font-bold mb-3">Días máximos <span
                class="text-red-500">*</span></label>
            <InputText id="dias_maximos" v-model="form.dias_maximos" class="w-full" required />
          </div>
        </div>
      </form>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="visible = false" class="mr-2" />
        <Button type="submit" label="Guardar" severity="contrast"/>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'

import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import Select from 'primevue/select'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag';

const props = defineProps({ empresaId: Number })
const toast = useToast()

const plazos = ref([])
const seleccionados = ref([])
const guardando = ref(false)

const rangos = ref([])
const rangoSeleccionado = ref(null)

const tiposTasa = ref([])
const tipoSeleccionado = ref(null)

const visible = ref(false)
const form = ref({
  nombre: '',
  dias_minimos: '',
  dias_maximos: ''
})

// Cargar plazos, rangos, tipos de tasa
async function cargarPlazos() {
  const res = await axios.get('/term-plans')
  plazos.value = res.data.data
}

async function cargarRangos() {
  try {
    const res = await axios.get(`/amount-ranges/${props.empresaId}/pendientes`)
    rangos.value = res.data.data
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar los rangos', life: 3000 })
  }
}

async function cargarTiposTasa() {
  try {
    const res = await axios.get('/rate-types')
    tiposTasa.value = res.data.data
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar tipos de tasa', life: 3000 })
  }
}

// Guardar nuevos plazos
async function guardarPlazos() {
  if (!rangoSeleccionado.value) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Seleccione un rango de monto', life: 3000 })
    return
  }

  if (!tipoSeleccionado.value) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Seleccione un tipo de tasa', life: 3000 })
    return
  }

  if (seleccionados.value.length === 0) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Seleccione al menos un plazo', life: 3000 })
    return
  }

  guardando.value = true

  try {
    const response = await axios.post('/fixed-term-rates', {
      corporate_entity_id: props.empresaId,
      amount_range_id: rangoSeleccionado.value,
      rate_type_id: tipoSeleccionado.value,
      term_plan_id: seleccionados.value
    })

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Éxito',
        detail: `Se crearon ${response.data.total_creados} registros correctamente`,
        life: 3000
      })

      seleccionados.value = []
      rangoSeleccionado.value = null
      tipoSeleccionado.value = null
    }

  } catch (error) {
    let mensaje = 'Error al guardar los registros'
    if (error.response?.data?.message) mensaje = error.response.data.message
    else if (error.response?.data?.error) mensaje = error.response.data.error

    toast.add({ severity: 'error', summary: 'Error', detail: mensaje, life: 5000 })
  } finally {
    guardando.value = false
  }
}

// Guardar nuevo plazo desde el diálogo
const storeTermPlan = async () => {
  try {
    await axios.post('/term-plans', form.value)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Plan registrado correctamente', life: 3000 })
    visible.value = false
    form.value = { nombre: '', dias_minimos: '', dias_maximos: '' }
    await cargarPlazos()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Revise los campos del formulario' })
  }
}

onMounted(() => {
  console.log('empresaId:', props.empresaId) // <-- este log
  cargarPlazos()
  cargarTiposTasa()
  if (props.empresaId) cargarRangos()
})

watch(() => props.empresaId, () => {
  if (props.empresaId) cargarRangos()
})

// Helpers
function mostrarRango(rango) {
  if (!rango) return ''
  const desde = formatMoney(rango.desde, rango.moneda)
  const hasta = rango.hasta ? formatMoney(rango.hasta, rango.moneda) : 'En adelante'
  return `${desde} - ${hasta} (${rango.nombre})`
}

function mostrarTipoTasa(tipo) {
  return tipo ? `${tipo.nombre} - ${tipo.descripcion}` : ''
}

function formatMoney(value, currency = 'PEN') {
  return new Intl.NumberFormat('es-PE', {
    style: 'currency',
    currency: currency
  }).format(parseFloat(value || 0))
}
</script>
