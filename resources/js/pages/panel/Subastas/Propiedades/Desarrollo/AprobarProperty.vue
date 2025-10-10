<template>
  <Toast />

  <Dialog v-model:visible="visible" header="Revisar Solicitud de Propiedades" :modal="true" :style="{ width: '600px' }"
    @hide="handleClose">
    <div v-if="loading" class="flex justify-center items-center p-6">
      <i class="pi pi-spin pi-spinner text-4xl"></i>
      <p class="mt-2">Cargando información...</p>
    </div>

    <div v-else class="flex flex-col gap-4">
      <!-- Información de la Solicitud (Solo lectura) -->
      <div class="p-3 bg-blue-50 border-1 border-blue-200 rounded-lg">
        <div class="flex justify-between items-start mb-3">
          <div class="flex-grow">
            <p class="text-sm text-blue-800 mb-1">
              <i class="pi pi-file mr-2"></i>
              <strong>Código:</strong> <span class="text-lg font-bold">{{ solicitud.codigo }}</span>
            </p>
            <p class="text-sm text-blue-800 mb-1">
              <i class="pi pi-user mr-2"></i>
              <strong>Cliente:</strong> {{ solicitud.investor.nombre }}
            </p>
            <p class="text-xs text-blue-800 mb-1">
              <i class="pi pi-id-card mr-2"></i>
              <strong>DNI:</strong> {{ solicitud.investor.documento }}
            </p>
          </div>
          <p class="text-sm text-blue-800">
            <strong>Valor General:</strong> {{ solicitud.currency }} {{ formatMoney(solicitud.valor_general) }}
          </p>
        </div>

        <!-- Moneda y Valor Requerido (Solo lectura) -->
        <div class="flex gap-3 items-end">
          <div class="flex-shrink-0" style="width: 120px;">
            <label class="font-bold text-sm mb-2 block text-blue-900">Moneda</label>
            <div class="p-2 bg-white border-1 border-blue-300 rounded-md">
              {{ solicitud.currency || '-' }}
            </div>
          </div>
          <div class="flex-grow">
            <label class="font-bold text-sm mb-2 block text-blue-900">Valor Requerido</label>
            <div class="p-2 bg-white border-1 border-blue-300 rounded-md">
              {{ formatMoney(solicitud.valor_requerido) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Navegación entre propiedades -->
      <div class="flex flex-wrap gap-2 items-center">
        <Button
          v-for="(prop, idx) in properties"
          :key="idx"
          :severity="propiedadActiva === idx ? 'contrast' : 'secondary'"
          :outlined="propiedadActiva !== idx"
          size="small"
          @click="cambiarPropiedad(idx)"
        >
          <span class="flex items-center gap-2">
            <i class="pi pi-home"></i>
            Propiedad {{ idx + 1 }}
            <i class="pi pi-check-circle text-green-500"></i>
          </span>
        </Button>
      </div>

      <!-- Formulario de la propiedad activa (Solo lectura) -->
      <div class="p-fluid">
        <div v-if="propiedadActual" class="flex flex-col gap-4 p-3 bg-gray-50 rounded-lg">
          <div class="flex justify-between items-center mb-2">
            <h3 class="text-lg font-bold">Propiedad #{{ propiedadActiva + 1 }}</h3>
          </div>

          <div class="flex gap-4">
            <div class="w-1/2">
              <label class="font-bold mb-2 block">Tipo de Inmueble</label>
              <div class="p-2 bg-white border-1 border-300 rounded-md">
                {{ getTipoInmuebleNombre(propiedadActual.id_tipo_inmueble) }}
              </div>
            </div>
            <div class="w-1/2">
              <label class="font-bold mb-2 block">Pertenece a</label>
              <div class="p-2 bg-white border-1 border-300 rounded-md">
                {{ propiedadActual.pertenece || '-' }}
              </div>
            </div>
          </div>

          <div>
            <label class="font-bold mb-1 block">Nombre</label>
            <div class="p-2 bg-white border-1 border-300 rounded-md">
              {{ propiedadActual.nombre }}
            </div>
          </div>

          <div class="flex gap-3">
            <div class="w-1/2">
              <label class="font-bold mb-1 block">Departamento</label>
              <div class="p-2 bg-white border-1 border-300 rounded-md">
                {{ propiedadActual.departamento?.ubigeo_name || '-' }}
              </div>
            </div>
            <div class="w-1/2">
              <label class="font-bold mb-1 block">Provincia</label>
              <div class="p-2 bg-white border-1 border-300 rounded-md">
                {{ propiedadActual.provincia?.ubigeo_name || '-' }}
              </div>
            </div>
          </div>

          <div>
            <label class="font-bold mb-1 block">Distrito</label>
            <div class="p-2 bg-white border-1 border-300 rounded-md">
              {{ propiedadActual.distrito?.ubigeo_name || '-' }}
            </div>
          </div>

          <div>
            <label class="font-bold mb-1 block">Dirección</label>
            <div class="p-2 bg-white border-1 border-300 rounded-md">
              {{ propiedadActual.direccion }}
            </div>
          </div>

          <div>
            <label class="font-bold mb-1 block">Valor Estimado</label>
            <div class="p-2 bg-white border-1 border-300 rounded-md">
              {{ solicitud.currency }} {{ formatMoney(propiedadActual.valor_estimado) }}
            </div>
          </div>

          <div>
            <label class="font-bold mb-1 block">Descripción</label>
            <div class="p-2 bg-white border-1 border-300 rounded-md min-h-20 whitespace-pre-wrap">
              {{ propiedadActual.descripcion }}
            </div>
          </div>

          <!-- Sección de Imágenes (Solo lectura) -->
          <div class="flex flex-col gap-3">
            <div>
              <label class="block font-bold mb-2">Imágenes ({{ propiedadActual.todasLasImagenes?.length || 0 }})</label>
            </div>

            <!-- Lista de imágenes -->
            <div v-if="propiedadActual.todasLasImagenes?.length > 0" class="flex flex-col gap-3">
              <div
                v-for="(imagen, imgIndex) in propiedadActual.todasLasImagenes"
                :key="imgIndex"
                class="flex gap-3 p-3 border-1 border-300 border-round items-start bg-white"
              >
                <div class="flex-shrink-0">
                  <img
                    :src="imagen.url"
                    :alt="`Imagen ${imgIndex + 1}`"
                    class="w-24 h-24 object-cover border-round cursor-pointer hover:opacity-80"
                    @click="verImagenCompleta(imagen.url)"
                  />
                </div>

                <div class="flex-grow flex flex-col gap-2">
                  <div class="text-sm font-semibold">
                    Imagen {{ imgIndex + 1 }}
                  </div>

                  <div>
                    <label class="text-xs font-semibold mb-1 block">Descripción</label>
                    <div class="p-2 bg-gray-50 border-1 border-300 rounded-md text-sm">
                      {{ imagen.description || 'Sin descripción' }}
                    </div>
                  </div>
                </div>
              </div>

              <div class="text-sm text-green-600">
                <i class="pi pi-check-circle mr-1"></i>
                Total de imágenes: {{ propiedadActual.todasLasImagenes.length }}
              </div>
            </div>

            <div v-else class="text-sm text-gray-500 p-3 border-1 border-300 border-round text-center">
              <i class="pi pi-images mr-2"></i>
              No hay imágenes
            </div>
          </div>
        </div>
      </div>

      <!-- Sección de Comentarios -->
      <div class="flex flex-col gap-2">
        <label class="font-bold text-sm">
          Comentarios
          <span v-if="accionSeleccionada === 'observed'" class="text-red-500">*</span>
          <span v-else class="text-gray-500 font-normal">(Opcional)</span>
        </label>
        <Textarea 
          v-model="comentario" 
          rows="3" 
          class="w-full" 
          :class="{ 'p-invalid': mostrarErrorComentario }"
          placeholder="Agrega comentarios sobre tu decisión..."
          autoResize
          maxlength="500"
        />
        <div class="flex justify-between items-center">
          <small v-if="mostrarErrorComentario" class="text-red-500">
            <i class="pi pi-exclamation-triangle mr-1"></i>
            El comentario es obligatorio al marcar como "Observado"
          </small>
          <small class="text-gray-500 ml-auto">{{ comentario.length }}/500</small>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex flex-wrap gap-2 justify-between w-full">
        <Button 
          label="Cancelar" 
          icon="pi pi-times" 
          severity="secondary" 
          text 
          @click="handleClose" 
        />
        
        <div class="flex gap-2">
          <Button
            label="Rechazar"
            icon="pi pi-times-circle"
            severity="danger"
            outlined
            @click="procesarAccion('rejected')"
            :loading="procesando && accionSeleccionada === 'rejected'"
            :disabled="loading || procesando"
          />
          
          <Button
            label="Observar"
            icon="pi pi-exclamation-circle"
            severity="warn"
            outlined
            @click="procesarAccion('observed')"
            :loading="procesando && accionSeleccionada === 'observed'"
            :disabled="loading || procesando"
          />
          
          <Button
            label="Aprobar"
            icon="pi pi-check-circle"
            severity="success"
            @click="procesarAccion('approved')"
            :loading="procesando && accionSeleccionada === 'approved'"
            :disabled="loading || procesando"
          />
        </div>
      </div>
    </template>
  </Dialog>

  <!-- Dialog para ver imagen completa -->
  <Dialog v-model:visible="mostrarImagenCompleta" :modal="true" :style="{ width: '80vw' }" header="Vista de Imagen">
    <img :src="imagenSeleccionada" class="w-full h-auto" />
  </Dialog>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Toast from 'primevue/toast'
import Textarea from 'primevue/textarea'

const props = defineProps({
  visible: { type: Boolean, default: false },
  idPropiedad: { type: [String, Number], default: null }
})

const emit = defineEmits(['update:visible', 'solicitud-procesada'])

const toast = useToast()
const loading = ref(false)
const procesando = ref(false)
const visible = ref(props.visible)
const comentario = ref('')
const accionSeleccionada = ref(null)
const mostrarErrorComentario = ref(false)
const mostrarImagenCompleta = ref(false)
const imagenSeleccionada = ref('')

const solicitud = ref({
  id: null,
  codigo: '',
  investor: {
    id: '',
    nombre: '',
    documento: ''
  },
  valor_general: 0,
  valor_requerido: 0,
  currency: '',
  currency_id: null,
  estado: '',
  created_at: ''
})

const properties = ref([])
const propiedadActiva = ref(0)
const tiposInmueble = ref([])
const departamentos = ref([])

const propiedadActual = computed(() => properties.value[propiedadActiva.value])

// Cargar catálogos
const cargarCatalogos = async () => {
  try {
    const [ubigeoRes, tiposRes] = await Promise.all([
      axios.get('https://novalink.oswa.workers.dev/api/v1/peru/ubigeo'),
      axios.get('/tipo-inmueble')
    ])
    
    departamentos.value = ubigeoRes.data || []
    tiposInmueble.value = tiposRes.data || []
  } catch (err) {
    console.error('Error al cargar catálogos:', err)
  }
}

onMounted(async () => {
  await cargarCatalogos()
})

// Watchers
watch(() => props.visible, async (newVal) => {
  visible.value = newVal
  if (newVal && props.idPropiedad) {
    await cargarDatosSolicitud()
  }
})

watch(visible, (newVal) => {
  emit('update:visible', newVal)
  if (!newVal) {
    resetForm()
  }
})

// Limpiar error cuando el usuario escribe
watch(comentario, () => {
  if (mostrarErrorComentario.value && comentario.value.trim()) {
    mostrarErrorComentario.value = false
  }
})

const cargarDatosSolicitud = async () => {
  if (!props.idPropiedad) return
  
  loading.value = true
  try {
    const { data } = await axios.get(`/property/${props.idPropiedad}/show`)
    
    solicitud.value = {
      id: data.solicitud.id,
      codigo: data.solicitud.codigo,
      investor: data.solicitud.investor,
      valor_general: data.solicitud.valor_general,
      valor_requerido: data.solicitud.valor_requerido,
      currency: data.solicitud.currency,
      currency_id: data.solicitud.currency_id,
      estado: data.solicitud.estado,
      created_at: data.solicitud.created_at
    }
    
    properties.value = data.properties.map((prop) => ({
      id: prop.id,
      nombre: prop.nombre,
      id_tipo_inmueble: prop.id_tipo_inmueble,
      pertenece: prop.pertenece,
      departamento: null,
      provincia: null,
      distrito: null,
      direccion: prop.direccion,
      descripcion: prop.descripcion,
      valor_estimado: prop.valor_estimado,
      provincias: [],
      distritos: [],
      todasLasImagenes: prop.foto.map((f) => ({
        url: f.url,
        description: f.descripcion
      }))
    }))

    // Buscar ubicaciones para cada propiedad
    await Promise.all(
      properties.value.map(async (prop, idx) => {
        const dept = departamentos.value.find(
          d => d.ubigeo_name.toLowerCase() === data.properties[idx].departamento.toLowerCase()
        )
        
        if (dept) {
          properties.value[idx].departamento = dept
          properties.value[idx].provincias = dept.provinces || []
          
          const prov = properties.value[idx].provincias.find(
            p => p.ubigeo_name.toLowerCase() === data.properties[idx].provincia.toLowerCase()
          )
          
          if (prov) {
            properties.value[idx].provincia = prov
            properties.value[idx].distritos = prov.districts || []
            
            const dist = properties.value[idx].distritos.find(
              d => d.ubigeo_name.toLowerCase() === data.properties[idx].distrito.toLowerCase()
            )
            
            if (dist) {
              properties.value[idx].distrito = dist
            }
          }
        }
      })
    )

    propiedadActiva.value = 0
  } catch (error) {
    console.error('Error al cargar solicitud:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar la información de la solicitud',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const cambiarPropiedad = (index) => {
  if (index >= 0 && index < properties.value.length) {
    propiedadActiva.value = index
  }
}

const getTipoInmuebleNombre = (id) => {
  const tipo = tiposInmueble.value.find(t => t.id_tipo_inmueble === id)
  return tipo?.nombre_tipo_inmueble || '-'
}

const formatMoney = (value) => {
  if (!value) return '0.00'
  return new Intl.NumberFormat('es-PE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(value)
}

const verImagenCompleta = (url) => {
  imagenSeleccionada.value = url
  mostrarImagenCompleta.value = true
}

const procesarAccion = async (accion) => {
  accionSeleccionada.value = accion
  mostrarErrorComentario.value = false

  // Validar comentario obligatorio para "observed"
  if (accion === 'observed' && !comentario.value.trim()) {
    mostrarErrorComentario.value = true
    toast.add({
      severity: 'warn',
      summary: 'Comentario requerido',
      detail: 'Debes agregar un comentario cuando marcas como "Observado"',
      life: 3000
    })
    return
  }

  procesando.value = true

  try {
    const payload = {
      approval1_status: accion,
      approval1_comment: comentario.value.trim() || null
    }

    await axios.put(`/solicitud-activacion/${solicitud.value.id}`, payload)

    const mensajes = {
      approved: 'Solicitud aprobada correctamente',
      rejected: 'Solicitud rechazada correctamente',
      observed: 'Solicitud marcada como observada'
    }

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: mensajes[accion],
      life: 3000
    })

    emit('solicitud-procesada')
    handleClose()
  } catch (error) {
    console.error('Error al procesar solicitud:', error)
    
    const errorMessage = error.response?.data?.message || 'Error al procesar la solicitud'
    
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: errorMessage,
      life: 3000
    })
  } finally {
    procesando.value = false
    accionSeleccionada.value = null
  }
}

const resetForm = () => {
  solicitud.value = {
    id: null,
    codigo: '',
    investor: {
      id: '',
      nombre: '',
      documento: ''
    },
    valor_general: 0,
    valor_requerido: 0,
    currency: '',
    currency_id: null,
    estado: '',
    created_at: ''
  }
  properties.value = []
  propiedadActiva.value = 0
  comentario.value = ''
  accionSeleccionada.value = null
  mostrarErrorComentario.value = false
}

const handleClose = () => {
  visible.value = false
}
</script>