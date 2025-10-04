<template>
  <Toast />

  <Dialog v-model:visible="visible" header="Editar Propiedades de Solicitud" :modal="true" :style="{ width: '600px' }"
    @hide="handleClose">
    <div v-if="loading" class="flex justify-center items-center p-6">
      <i class="pi pi-spin pi-spinner text-4xl"></i>
      <p class="mt-2">Cargando información...</p>
    </div>

    <div v-else class="flex flex-col gap-4">
      <!-- Información de la Solicitud (Editable) -->
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

        <!-- Moneda y Valor Requerido Editables -->
        <div class="flex gap-3 items-end">
          <div class="flex-shrink-0" style="width: 120px;">
            <label class="font-bold text-sm mb-2 block text-blue-900">Moneda</label>
            <Select
              v-model="solicitud.currency_id"
              :options="monedas"
              option-label="codigo"
              option-value="id"
              placeholder="Moneda"
              class="w-full"
            />
          </div>
          <div class="flex-grow">
            <label class="font-bold text-sm mb-2 block text-blue-900">Valor Requerido</label>
            <InputNumber
              v-model="solicitud.valor_requerido"
              class="w-full"
              :useGrouping="true"
              :locale="'es-PE'"
              :min="0"
            />
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
            <i v-if="validarPropiedadCompleta(prop)" class="pi pi-check-circle text-green-500"></i>
          </span>
        </Button>
        
        <!-- Botón para agregar nueva propiedad -->
        <Button
          icon="pi pi-plus"
          label="Agregar Propiedad"
          severity="success"
          size="small"
          outlined
          @click="agregarPropiedad"
        />
      </div>

      <!-- Formulario de la propiedad activa -->
      <form @submit.prevent="guardarCambios" class="p-fluid">
        <div v-if="propiedadActual" class="flex flex-col gap-4 p-3 bg-gray-50 rounded-lg">
          <div class="flex justify-between items-center mb-2">
            <h3 class="text-lg font-bold">Propiedad #{{ propiedadActiva + 1 }}</h3>
            <Button
              v-if="properties.length > 1"
              icon="pi pi-trash"
              label="Eliminar"
              severity="danger"
              size="small"
              outlined
              @click="eliminarPropiedad(propiedadActiva)"
            />
          </div>

          <div class="flex gap-4">
            <div class="w-1/2">
              <label class="font-bold mb-2">Tipo de Inmueble</label>
              <Select
                v-model="propiedadActual.id_tipo_inmueble"
                :options="tiposInmueble"
                option-label="nombre_tipo_inmueble"
                option-value="id_tipo_inmueble"
                placeholder="Selecciona tipo"
                class="w-full"
              />
            </div>
            <div class="w-1/2">
              <label class="font-bold mb-2">Pertenece a</label>
              <InputText v-model="propiedadActual.pertenece" class="w-full" placeholder="Ej: Familia, Personal" />
            </div>
          </div>

          <div>
            <label class="font-bold mb-1">Nombre <span class="text-red-500">*</span></label>
            <InputText v-model="propiedadActual.nombre" class="w-full" placeholder="Nombre de la propiedad" />
          </div>

          <div class="flex gap-3">
            <div class="w-1/2">
              <label class="font-bold mb-1">Departamento <span class="text-red-500">*</span></label>
              <Select
                v-model="propiedadActual.departamento"
                :options="departamentos"
                optionLabel="ubigeo_name"
                dataKey="ubigeo_id"
                placeholder="Seleccione depart..."
                class="w-full"
                @change="onDepartamentoChange(propiedadActual)"
                appendTo="self"
              />
            </div>
            <div class="w-1/2">
              <label class="font-bold mb-1">Provincia <span class="text-red-500">*</span></label>
              <Select
                v-model="propiedadActual.provincia"
                :options="propiedadActual.provincias"
                optionLabel="ubigeo_name"
                dataKey="ubigeo_id"
                placeholder="Seleccione provincia"
                class="w-full"
                :disabled="!propiedadActual.departamento"
                @change="onProvinciaChange(propiedadActual)"
                appendTo="self"
              />
            </div>
          </div>

          <div>
            <label class="font-bold mb-1">Distrito <span class="text-red-500">*</span></label>
            <Select
              v-model="propiedadActual.distrito"
              :options="propiedadActual.distritos"
              optionLabel="ubigeo_name"
              dataKey="ubigeo_id"
              placeholder="Seleccione distrito"
              class="w-full"
              :disabled="!propiedadActual.provincia"
              appendTo="self"
            />
          </div>

          <div>
            <label class="font-bold mb-1">Dirección <span class="text-red-500">*</span></label>
            <InputText v-model="propiedadActual.direccion" class="w-full" placeholder="Dirección completa" />
          </div>

          <div>
            <label class="font-bold mb-1">Valor Estimado <span class="text-red-500">*</span></label>
            <InputNumber
              v-model="propiedadActual.valor_estimado"
              class="w-full"
              :useGrouping="true"
              :locale="'es-PE'"
              :min="0"
            />
          </div>

          <div>
            <label class="font-bold mb-1">Descripción <span class="text-red-500">*</span></label>
            <Textarea v-model="propiedadActual.descripcion" rows="3" class="w-full" autoResize placeholder="Describe la propiedad" />
          </div>

          <!-- Sección de Imágenes -->
          <div class="flex flex-col gap-3">
            <div>
              <label class="block font-bold mb-2">Imágenes <span class="text-red-500">*</span></label>
              <p class="text-sm text-gray-600 mb-2">Se requieren al menos 3 imágenes con descripción</p>

              <FileUpload
                mode="basic"
                :name="`imagenes-${propiedadActiva}`"
                accept="image/*"
                :maxFileSize="2000000"
                :multiple="true"
                :auto="false"
                chooseLabel="Agregar más imágenes"
                class="w-full"
                @select="onFileSelect($event, propiedadActual)"
              />
            </div>

            <!-- Lista de imágenes -->
            <div v-if="propiedadActual.todasLasImagenes?.length > 0" class="flex flex-col gap-3">
              <div class="flex justify-between items-center">
                <h4 class="font-bold text-sm">
                  Imágenes ({{ propiedadActual.todasLasImagenes.length }})
                </h4>
              </div>

              <div
                v-for="(imagen, imgIndex) in propiedadActual.todasLasImagenes"
                :key="imgIndex"
                class="flex gap-3 p-3 border-1 border-300 border-round items-start"
              >
                <div class="flex-shrink-0">
                  <img
                    :src="imagen.preview || imagen.url"
                    :alt="`Imagen ${imgIndex + 1}`"
                    class="w-20 h-20 object-cover border-round"
                  />
                </div>

                <div class="flex-grow flex flex-col gap-2">
                  <div class="text-sm">
                    <strong>{{ imagen.file?.name || 'Imagen existente' }}</strong>
                    <span v-if="imagen.file" class="text-gray-500 ml-2">({{ formatFileSize(imagen.file.size) }})</span>
                    <span v-else class="text-blue-500 ml-2">(Ya guardada)</span>
                  </div>

                  <div>
                    <label class="text-xs font-semibold mb-1 block">
                      Descripción (máx. 35 caracteres) <span class="text-red-500">*</span>
                    </label>
                    <InputText
                      v-model="imagen.description"
                      placeholder="Ej: Fachada, Sala, Cocina"
                      class="w-full text-sm"
                      :class="{ 'p-invalid': submitted && !imagen.description?.trim() }"
                      maxlength="35"
                    />
                    <div class="flex justify-between items-center mt-1">
                      <small v-if="submitted && !imagen.description?.trim()" class="text-red-500">
                        La descripción es requerida
                      </small>
                      <small
                        class="text-xs"
                        :class="{
                          'text-orange-500': imagen.description?.length >= 30,
                          'text-red-500': imagen.description?.length >= 35,
                          'text-gray-500': !imagen.description?.length || imagen.description.length < 30
                        }"
                      >
                        {{ imagen.description?.length || 0 }}/35
                      </small>
                    </div>
                  </div>
                </div>

                <Button
                  icon="pi pi-times"
                  severity="danger"
                  size="small"
                  outlined
                  @click="removeImage(propiedadActual, imgIndex)"
                />
              </div>

              <div class="text-sm">
                <span v-if="propiedadActual.todasLasImagenes.length < 3" class="text-orange-600">
                  <i class="pi pi-exclamation-triangle mr-1"></i>
                  Necesitas al menos {{ 3 - propiedadActual.todasLasImagenes.length }} imagen(es) más
                </span>
                <span v-else class="text-green-600">
                  <i class="pi pi-check-circle mr-1"></i>
                  Imágenes suficientes ({{ propiedadActual.todasLasImagenes.length }}/3 mínimo)
                </span>
              </div>
            </div>

            <div v-else class="text-sm text-gray-500 p-3 border-1 border-300 border-round text-center">
              <i class="pi pi-images mr-2"></i>
              No hay imágenes
            </div>
          </div>
        </div>
      </form>
    </div>

    <template #footer>
      <Button label="Cancelar" icon="pi pi-times" severity="secondary" text @click="handleClose" />
      <Button
        label="Guardar Cambios"
        icon="pi pi-check"
        severity="contrast"
        @click="guardarCambios"
        :loading="guardando"
        :disabled="loading"
      />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Toast from 'primevue/toast'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import FileUpload from 'primevue/fileupload'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'

const props = defineProps({
  visible: { type: Boolean, default: false },
  idPropiedad: { type: [String, Number], default: null }
})

const emit = defineEmits(['update:visible', 'propiedad-actualizada'])

const toast = useToast()
const loading = ref(false)
const guardando = ref(false)
const submitted = ref(false)
const visible = ref(props.visible)

// Inicializar con valores por defecto
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
const monedas = ref([])

const propiedadActual = computed(() => properties.value[propiedadActiva.value])

// Cargar catálogos
const cargarCatalogos = async () => {
  try {
    const [ubigeoRes, tiposRes, monedasRes] = await Promise.all([
      axios.get('https://novalink.oswa.workers.dev/api/v1/peru/ubigeo'),
      axios.get('/tipo-inmueble'),
      axios.get('/currencies')
    ])
    
    departamentos.value = ubigeoRes.data || []
    tiposInmueble.value = tiposRes.data || []
    
    // Manejar la respuesta de monedas con la nueva estructura
    monedas.value = monedasRes.data?.data || []
    
    console.log('Monedas cargadas:', monedas.value)
  } catch (err) {
    console.error('Error al cargar catálogos:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Error al cargar catálogos',
      life: 3000
    })
  }
}

// Cargar datos cuando el componente se monta
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

const cargarDatosSolicitud = async () => {
  if (!props.idPropiedad) return
  
  loading.value = true
  try {
    const { data } = await axios.get(`/property/${props.idPropiedad}/show`)
    
    console.log('Datos recibidos:', data)
    
    // Asignar solicitud directamente - ya viene con currency_id
    solicitud.value = {
      id: data.solicitud.id,
      codigo: data.solicitud.codigo,
      investor: data.solicitud.investor,
      valor_general: data.solicitud.valor_general,
      valor_requerido: data.solicitud.valor_requerido,
      currency: data.solicitud.currency,
      currency_id: data.solicitud.currency_id, // Ya viene directamente
      estado: data.solicitud.estado,
      created_at: data.solicitud.created_at
    }
    
    console.log('Solicitud procesada:', solicitud.value)
    
    // Transformar properties del JSON
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
      imagenesExistentes: prop.foto.map((f) => ({
        url: f.url,
        description: f.descripcion,
        esNueva: false
      })),
      imagenesNuevas: [],
      imagenesAEliminar: [],
      todasLasImagenes: prop.foto.map((f) => ({
        url: f.url,
        description: f.descripcion,
        esNueva: false
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

const agregarPropiedad = () => {
  const nuevaPropiedad = {
    id: null,
    nombre: '',
    id_tipo_inmueble: null,
    pertenece: '',
    departamento: null,
    provincia: null,
    distrito: null,
    direccion: '',
    descripcion: '',
    valor_estimado: 0,
    provincias: [],
    distritos: [],
    imagenesExistentes: [],
    imagenesNuevas: [],
    imagenesAEliminar: [],
    todasLasImagenes: []
  }
  
  properties.value.push(nuevaPropiedad)
  propiedadActiva.value = properties.value.length - 1
  
  toast.add({
    severity: 'success',
    summary: 'Propiedad agregada',
    detail: 'Nueva propiedad creada. Completa los datos requeridos.',
    life: 3000
  })
}

const eliminarPropiedad = (index) => {
  if (properties.value.length <= 1) {
    toast.add({
      severity: 'warn',
      summary: 'Advertencia',
      detail: 'Debe existir al menos una propiedad',
      life: 3000
    })
    return
  }
  
  properties.value.splice(index, 1)
  
  if (propiedadActiva.value >= properties.value.length) {
    propiedadActiva.value = properties.value.length - 1
  }
  
  toast.add({
    severity: 'info',
    summary: 'Propiedad eliminada',
    detail: 'La propiedad se eliminará al guardar los cambios',
    life: 3000
  })
}

const onDepartamentoChange = (propiedad) => {
  propiedad.provincia = null
  propiedad.distrito = null
  propiedad.provincias = propiedad.departamento?.provinces || []
  propiedad.distritos = []
}

const onProvinciaChange = (propiedad) => {
  propiedad.distrito = null
  propiedad.distritos = propiedad.provincia?.districts || []
}

const onFileSelect = (event, propiedad) => {
  const files = event.files || []

  files.forEach((file) => {
    const reader = new FileReader()
    reader.onload = (e) => {
      const nuevaImagen = {
        file: file,
        description: '',
        preview: e.target?.result,
        esNueva: true
      }
      propiedad.imagenesNuevas.push(nuevaImagen)
      propiedad.todasLasImagenes.push(nuevaImagen)
    }
    reader.readAsDataURL(file)
  })

  toast.add({
    severity: 'success',
    summary: 'Archivos seleccionados',
    detail: `${files.length} imagen(es) agregada(s)`,
    life: 2000
  })
}

const removeImage = (propiedad, imageIndex) => {
  const imagen = propiedad.todasLasImagenes[imageIndex]
  
  if (imagen.esNueva) {
    const idx = propiedad.imagenesNuevas.findIndex(img => img === imagen)
    if (idx !== -1) propiedad.imagenesNuevas.splice(idx, 1)
  } else {
    propiedad.imagenesAEliminar.push(imagen.url)
  }
  
  propiedad.todasLasImagenes.splice(imageIndex, 1)
  
  toast.add({
    severity: 'info',
    summary: 'Imagen eliminada',
    detail: 'La imagen será eliminada al guardar',
    life: 2000
  })
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatMoney = (value) => {
  if (!value) return '0.00'
  return new Intl.NumberFormat('es-PE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(value)
}

const validarPropiedadCompleta = (propiedad) => {
  return !!(
    propiedad.nombre?.trim() &&
    propiedad.direccion?.trim() &&
    propiedad.departamento?.ubigeo_id &&
    propiedad.provincia?.ubigeo_id &&
    propiedad.distrito?.ubigeo_id &&
    propiedad.descripcion?.trim() &&
    propiedad.valor_estimado &&
    propiedad.todasLasImagenes?.length >= 3 &&
    propiedad.todasLasImagenes.every((img) => img.description?.trim())
  )
}

const guardarCambios = async () => {
  submitted.value = true
  guardando.value = true

  // Validar todas las propiedades
  for (const [idx, prop] of properties.value.entries()) {
    if (!validarPropiedadCompleta(prop)) {
      toast.add({
        severity: 'warn',
        summary: 'Validación',
        detail: `Completa todos los campos de la propiedad #${idx + 1}`,
        life: 3000
      })
      guardando.value = false
      propiedadActiva.value = idx
      return
    }
  }

  try {
    const formData = new FormData()

    formData.append('valor_requerido', solicitud.value.valor_requerido)
    formData.append('currency_id', solicitud.value.currency_id)

    properties.value.forEach((prop, idx) => {
      if (prop.id) {
        formData.append(`properties[${idx}][id]`, prop.id)
      }
      
      formData.append(`properties[${idx}][nombre]`, prop.nombre)
      formData.append(`properties[${idx}][direccion]`, prop.direccion)
      formData.append(`properties[${idx}][id_tipo_inmueble]`, prop.id_tipo_inmueble || '')
      formData.append(`properties[${idx}][pertenece]`, prop.pertenece || '')
      formData.append(`properties[${idx}][valor_estimado]`, prop.valor_estimado?.toString() || '0')
      formData.append(`properties[${idx}][descripcion]`, prop.descripcion || '')

      if (prop.departamento) {
        formData.append(`properties[${idx}][departamento]`, prop.departamento.ubigeo_name)
        formData.append(`properties[${idx}][departamento_id]`, prop.departamento.ubigeo_id)
      }
      if (prop.provincia) {
        formData.append(`properties[${idx}][provincia]`, prop.provincia.ubigeo_name)
        formData.append(`properties[${idx}][provincia_id]`, prop.provincia.ubigeo_id)
      }
      if (prop.distrito) {
        formData.append(`properties[${idx}][distrito]`, prop.distrito.ubigeo_name)
        formData.append(`properties[${idx}][distrito_id]`, prop.distrito.ubigeo_id)
      }

      prop.imagenesNuevas.forEach((imagen) => {
        formData.append(`properties[${idx}][imagenes][]`, imagen.file)
        formData.append(`properties[${idx}][descriptions][]`, imagen.description || '')
      })

      prop.imagenesAEliminar.forEach(url => {
        formData.append(`properties[${idx}][imagenes_eliminar][]`, url)
      })
    })

    await axios.post(`/property/${props.idPropiedad}/actualizar`, formData, {
      headers: { 
        'Content-Type': 'multipart/form-data',
        'X-HTTP-Method-Override': 'PUT'
      }
    })

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Propiedades actualizadas correctamente',
      life: 3000
    })

    emit('propiedad-actualizada')
    handleClose()
  } catch (error) {
    console.error('Error al actualizar:', error)
    
    const errorMessage = error.response?.data?.message || 'Error al actualizar las propiedades'
    const errors = error.response?.data?.errors
    
    if (errors) {
      const firstError = Object.values(errors)[0][0]
      toast.add({
        severity: 'error',
        summary: 'Error de Validación',
        detail: firstError,
        life: 5000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: errorMessage,
        life: 3000
      })
    }
  } finally {
    guardando.value = false
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
  submitted.value = false
}

const handleClose = () => {
  visible.value = false
}
</script>