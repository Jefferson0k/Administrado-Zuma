<template>
  <Dialog v-model:visible="localVisible" modal :closable="true" :draggable="false" 
    :style="{ width: '900px' }" @hide="cerrar">
    <template #header>
      <div class="flex items-center gap-2">
        <i class="pi pi-eye text-xl" />
        <span class="font-semibold text-xl">Detalle de Solicitud</span>
      </div>
    </template>

    <div v-if="loading" class="flex justify-center items-center py-8">
      <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" />
    </div>

    <div v-else-if="detalleConfig" class="space-y-4">
      <!-- Información General -->
      <Card>
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-info-circle" />
            <span>Información General</span>
          </div>
        </template>
        <template #content>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-semibold text-gray-600">Nombre Solicitud</label>
              <p class="text-lg mt-1">{{ detalleConfig.nombreSolicitud }}</p>
            </div>
            <div>
              <label class="text-sm font-semibold text-gray-600">Moneda</label>
              <p class="text-lg mt-1">{{ detalleConfig.currency }}</p>
            </div>
            <div>
              <label class="text-sm font-semibold text-gray-600">Tipo de Cronograma</label>
              <p class="text-lg mt-1">{{ formatCronograma(detalleConfig.tipo_cronograma) }}</p>
            </div>
            <div>
              <label class="text-sm font-semibold text-gray-600">Riesgo</label>
              <Tag :value="detalleConfig.riesgo" :severity="getRiesgoSeverity(detalleConfig.riesgo)" class="mt-1" />
            </div>
          </div>
        </template>
      </Card>

      <!-- Información Financiera -->
      <Card>
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-dollar" />
            <span>Información Financiera</span>
          </div>
        </template>
        <template #content>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-semibold text-gray-600">Valor General</label>
              <p class="text-lg mt-1 font-semibold text-green-600">{{ formatMoney(detalleConfig.valor_general) }}</p>
            </div>
            <div>
              <label class="text-sm font-semibold text-gray-600">Valor Requerido</label>
              <p class="text-lg mt-1 font-semibold text-blue-600">{{ formatMoney(detalleConfig.valor_requerido) }}</p>
            </div>
            <div>
              <label class="text-sm font-semibold text-gray-600">TEA</label>
              <p class="text-lg mt-1">{{ formatPercent(detalleConfig.tea) }}</p>
            </div>
            <div>
              <label class="text-sm font-semibold text-gray-600">TEM</label>
              <p class="text-lg mt-1">{{ formatPercent(detalleConfig.tem) }}</p>
            </div>
          </div>
        </template>
      </Card>

      <!-- Sección de Aprobación -->
      <Card>
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-check-circle" />
            <span>Aprobación</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            <div>
              <label class="text-sm font-semibold text-gray-600 block mb-2">Estado de Aprobación *</label>
              <div class="flex gap-3">
                <div class="flex items-center">
                  <RadioButton v-model="aprobacionForm.status" inputId="approved" value="approved" />
                  <label for="approved" class="ml-2 cursor-pointer">
                    <Tag value="Aprobado" severity="success" />
                  </label>
                </div>
                <div class="flex items-center">
                  <RadioButton v-model="aprobacionForm.status" inputId="rejected" value="rejected" />
                  <label for="rejected" class="ml-2 cursor-pointer">
                    <Tag value="Rechazado" severity="danger" />
                  </label>
                </div>
                <div class="flex items-center">
                  <RadioButton v-model="aprobacionForm.status" inputId="observed" value="observed" />
                  <label for="observed" class="ml-2 cursor-pointer">
                    <Tag value="Observado" severity="warn" />
                  </label>
                </div>
              </div>
            </div>

            <div>
              <label for="comment" class="text-sm font-semibold text-gray-600 block mb-2">
                Comentario {{ aprobacionForm.status === 'observed' ? '*' : '(Opcional)' }}
              </label>
              <Textarea v-model="aprobacionForm.comment" id="comment" rows="4" 
                class="w-full" placeholder="Ingrese sus comentarios..." 
                :class="{ 'border-red-500': aprobacionForm.status === 'observed' && !aprobacionForm.comment }" />
              <small v-if="aprobacionForm.status === 'observed' && !aprobacionForm.comment" class="text-red-500">
                El comentario es obligatorio cuando el estado es "Observado"
              </small>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <template #footer>
      <div class="flex justify-end gap-2">
        <Button label="Cancelar" icon="pi pi-times" severity="secondary" @click="cerrar" />
        <Button label="Guardar Aprobación" icon="pi pi-check" :loading="enviando" 
          @click="guardarAprobacion" :disabled="!aprobacionForm.status" />
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'

import Dialog from 'primevue/dialog'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import RadioButton from 'primevue/radiobutton'
import Textarea from 'primevue/textarea'
import ProgressSpinner from 'primevue/progressspinner'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  configuracionId: { type: [Number, String], default: null }
})

const emit = defineEmits(['update:modelValue', 'aprobado'])

const toast = useToast()

const localVisible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const loading = ref(false)
const enviando = ref(false)
const detalleConfig = ref(null)
const aprobacionForm = ref({
  status: null,
  comment: ''
})

const formatMoney = (value) => {
  if (value === null || value === undefined || isNaN(value)) return '0.00'
  return new Intl.NumberFormat('es-PE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
    useGrouping: true
  }).format(Number(value))
}

const formatPercent = (value) => {
  if (!value && value !== 0) return '0.000%'
  return new Intl.NumberFormat('es-PE', {
    minimumFractionDigits: 3,
    maximumFractionDigits: 3
  }).format(value) + '%'
}

const formatCronograma = (tipo) => {
  return tipo === 'frances' ? 'Francés' : (tipo === 'americano' ? 'Americano' : tipo)
}

const getRiesgoSeverity = (riesgo) => {
  switch (riesgo) {
    case 'A+': case 'A': return 'success'
    case 'B': return 'info'
    case 'C': return 'warn'
    case 'D': return 'danger'
    default: return 'secondary'
  }
}

const cargarDetalle = async () => {
  if (!props.configuracionId) return
  
  loading.value = true
  try {
    const { data } = await axios.get(`/property/reglas/${props.configuracionId}/show`)
    detalleConfig.value = data.data
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar el detalle de la configuración',
      life: 3000
    })
    cerrar()
  } finally {
    loading.value = false
  }
}

const guardarAprobacion = async () => {
  if (!aprobacionForm.value.status) {
    toast.add({
      severity: 'warn',
      summary: 'Advertencia',
      detail: 'Debe seleccionar un estado de aprobación',
      life: 3000
    })
    return
  }

  if (aprobacionForm.value.status === 'observed' && !aprobacionForm.value.comment) {
    toast.add({
      severity: 'warn',
      summary: 'Advertencia',
      detail: 'El comentario es obligatorio cuando el estado es "Observado"',
      life: 3000
    })
    return
  }

  enviando.value = true
  try {
    const { data } = await axios.post(
      `/property/${props.configuracionId}/approve-config`,
      aprobacionForm.value
    )

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: data.message || 'Aprobación registrada correctamente',
      life: 3000
    })

    emit('aprobado')
    cerrar()
  } catch (error) {
    const errorMessage = error.response?.data?.message || 'Error al registrar la aprobación'
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: errorMessage,
      life: 3000
    })
  } finally {
    enviando.value = false
  }
}

const cerrar = () => {
  localVisible.value = false
  detalleConfig.value = null
  aprobacionForm.value = {
    status: null,
    comment: ''
  }
}

watch(() => props.modelValue, (newVal) => {
  if (newVal) {
    cargarDetalle()
  }
})
</script>

<style scoped>
:deep(.p-card-title) {
  font-size: 1.1rem;
  margin-bottom: 1rem;
  color: #3b82f6;
}
</style>