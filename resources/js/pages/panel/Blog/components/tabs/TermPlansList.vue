<template>
  <div class="space-y-6">
    <!-- Botón para recargar los datos -->
    <div class="mb-4">
      <Button label="Recargar datos" icon="pi pi-refresh" severity="info" @click="recargarDatos"
        :disabled="!deshabilitado" />
    </div>

    <!-- Selects de rango de monto y tipo de tasa -->
    <div class="flex flex-col md:flex-row md:items-end gap-4">
      <!-- Rango de monto -->
      <div class="flex-1">
        <label class="text-sm font-medium mb-1 block">Seleccione un rango de monto</label>
        <Select v-model="rangoSeleccionado" :options="rangos" optionValue="id" placeholder="Seleccione un rango"
          :filter="true" class="w-full" @change="onRangoChange" :disabled="deshabilitado">
          <template #option="{ option }">
            <div>
              <span class="font-medium">{{ mostrarRango(option) }}</span>
              <Tag :value="option.estado" :severity="option.estado === 'completo' ? 'success' : 'warn'" />
            </div>
          </template>

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
          placeholder="Tipo de tasa" class="w-full" :filter="true" :disabled="deshabilitado">
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

      <!-- 3 columnas por fila (hasta en pantallas grandes) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="plazo in plazos" :key="plazo.id" class="flex flex-col p-4 rounded shadow-sm border space-y-3">
          <!-- Fila con checkbox, nombre y botón de desbloquear -->
          <div class="flex justify-between items-center">
            <div class="flex items-center gap-2">
              <Checkbox v-model="seleccionados" :inputId="`plazo-${plazo.id}`" :value="plazo.id"
                :disabled="deshabilitado" />
              <label :for="`plazo-${plazo.id}`" class="text-sm font-semibold">
                {{ plazo.nombre }}
              </label>
            </div>

            <!-- Botón desbloquear o acciones -->
            <div>
              <Button v-if="!camposEditables[plazo.id]" icon="pi pi-unlock"
                class="p-button-sm p-button-text p-button-secondary" @click="desbloquearCampo(plazo)"
                v-tooltip="'Desbloquear para editar'" />
              <div v-else class="flex gap-1">
                <Button icon="pi pi-check" class="p-button-sm p-button-text p-button-success"
                  @click="guardarEdicion(plazo.id)" v-tooltip="'Guardar'" />
                <Button icon="pi pi-times" class="p-button-sm p-button-text p-button-danger"
                  @click="cancelarEdicion(plazo.id)" v-tooltip="'Cancelar'" />
              </div>
            </div>
          </div>

          <!-- Información de días -->
          <div v-if="!camposEditables[plazo.id]" class="text-sm text-gray-700 pl-6">
            {{ plazo.dias_minimos }} - {{ plazo.dias_maximos }} días
          </div>

          <!-- Campos editables -->
          <div v-else class="grid grid-cols-1 gap-3">
            <div>
              <label class="text-xs">Nombre</label>
              <InputText v-model="plazoEditado[plazo.id].nombre" class="w-full" />
            </div>
            <div>
              <label class="text-xs">Días mínimos</label>
              <InputText v-model="plazoEditado[plazo.id].dias_minimos" class="w-full" />
            </div>
            <div>
              <label class="text-xs">Días máximos</label>
              <InputText v-model="plazoEditado[plazo.id].dias_maximos" class="w-full" />
            </div>
          </div>
        </div>
      </div>
    </div>


    <!-- Botones -->
    <div class="flex justify-between items-center mt-4">
      <Button label="Agregar un nuevo plazo" icon="pi pi-plus" severity="secondary" @click="visible = true"
        :disabled="deshabilitado" />
      <Button label="Registrar" icon="pi pi-save" severity="contrast" @click="guardarPlazos" :loading="guardando"
        :disabled="deshabilitado" />
    </div>

    <!-- Diálogo para nuevo plazo -->
    <Dialog v-model:visible="visible" modal header="Nuevo Plan" :style="{ width: '450px' }">
      <form @submit.prevent="storeTermPlan">
        <div class="flex flex-col gap-6">
          <div>
            <label for="nombre" class="block font-bold mb-3">Etiqueta por mes <span
                class="text-red-500">*</span></label>
            <InputText id="nombre" v-model="form.nombre" fluid required />
          </div>
          <div>
            <label for="dias_minimos" class="block font-bold mb-3">cantidad de dias minimos <span
                class="text-red-500">*</span></label>
            <InputText id="dias_minimos" v-model="form.dias_minimos" class="w-full" required />
          </div>
          <div>
            <label for="dias_maximos" class="block font-bold mb-3">cantidad de dias maximo <span
                class="text-red-500">*</span></label>
            <InputText id="dias_maximos" v-model="form.dias_maximos" class="w-full" required />
          </div>
        </div>
      </form>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="visible = false" class="mr-2" />
        <Button type="submit" label="Guardar" severity="contrast" @click="storeTermPlan" />
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
import Tag from 'primevue/tag'

const props = defineProps({ empresaId: Number })
const toast = useToast()

const plazos = ref([])
const seleccionados = ref([])
const guardando = ref(false)

const camposEditables = ref({})
const plazoEditado = ref({})

const rangos = ref([])
const rangoSeleccionado = ref(null)

const tiposTasa = ref([])
const tipoSeleccionado = ref(null)

const visible = ref(false)
const deshabilitado = ref(true)

function desbloquearCampo(plazo) {
  camposEditables.value[plazo.id] = true
  plazoEditado.value[plazo.id] = {
    nombre: plazo.nombre,
    dias_minimos: plazo.dias_minimos,
    dias_maximos: plazo.dias_maximos
  }
}

function cancelarEdicion(plazoId) {
  delete camposEditables.value[plazoId]
  delete plazoEditado.value[plazoId]
}

async function guardarEdicion(plazoId) {
  const datos = plazoEditado.value[plazoId]

  try {
    await axios.put(`/term-plans/${plazoId}`, datos)
    toast.add({ severity: 'success', summary: 'Actualizado', detail: 'Plazo actualizado correctamente' })

    // Refrescar lista
    await cargarPlazos()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar el plazo' })
  } finally {
    cancelarEdicion(plazoId)
  }
}

const form = ref({
  nombre: '',
  dias_minimos: '',
  dias_maximos: ''
})

// Función para recargar todos los datos y habilitar inputs
async function recargarDatos() {
  deshabilitado.value = true
  try {
    await Promise.all([
      cargarPlazos(),
      cargarTiposTasa(),
      cargarRangos()
    ])
    deshabilitado.value = false
    toast.add({ severity: 'success', summary: 'Listo', detail: 'Datos cargados correctamente', life: 3000 })
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al cargar los datos', life: 3000 })
  }
}

// Evento al cambiar el select de rango
function onRangoChange(e) {
  console.log('Rango seleccionado:', e.value)
}

// Cargar plazos, rangos y tipos de tasa
async function cargarPlazos() {
  const res = await axios.get('/term-plans')
  plazos.value = res.data.data
}

async function cargarRangos() {
  const res = await axios.get(`/amount-ranges/${props.empresaId}/pendientes`)
  rangos.value = res.data.data
}

async function cargarTiposTasa() {
  const res = await axios.get('/rate-types')
  tiposTasa.value = res.data.data
}

// Guardar nuevos plazos
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
    }

  } catch (error) {
    let mensaje = 'Error al guardar los registros'
    if (error.response?.data?.message) mensaje = error.response.data.message
    else if (error.response?.data?.error) mensaje = error.response.data.error

    toast.add({ severity: 'error', summary: 'Error', detail: mensaje, life: 5000 })
  } finally {
    // Limpiar selección y deshabilitar campos
    seleccionados.value = []
    rangoSeleccionado.value = null
    tipoSeleccionado.value = null
    deshabilitado.value = true
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
  // Nada se carga al inicio, hasta que el usuario presione "Recargar"
})

watch(() => props.empresaId, () => {
  if (!deshabilitado.value && props.empresaId) cargarRangos()
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
