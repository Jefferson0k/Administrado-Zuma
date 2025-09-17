<template>
  <Toast />

  <Toolbar class="mb-6">
    <template #start>
      <Button label="Nuevo" icon="pi pi-plus" severity="secondary" class="mr-2" @click="modalVisible = true" />
    </template>
  </Toolbar>

  <Dialog v-model:visible="modalVisible" header="Registro del inmueble" :modal="true" :style="{ width: '600px' }">
    <form @submit.prevent="saveProperty" class="p-fluid">
      <div class="flex flex-col gap-4">
        <!-- Paso 1: Cliente -->
        <div v-if="!mostrarInmueble" class="flex flex-col gap-4 p-3 border-1 border-300 border-round">
          <div>
            <label class="font-bold mb-1">DNI <span class="text-red-500">*</span></label>
            <InputText v-model="form.dni" fluid maxlength="8" @input="handleDniInput" />
          </div>

          <div class="flex gap-4">
            <div class="w-1/2">
              <label class="font-bold mb-1">Nombres <span class="text-red-500">*</span></label>
              <InputText v-model="form.nombres" fluid :disabled="investorExists" />
            </div>
            <div class="w-1/2">
              <label class="font-bold mb-1">Apellido Paterno <span class="text-red-500">*</span></label>
              <InputText v-model="form.apellido_paterno" fluid :disabled="investorExists" />
            </div>
          </div>

          <div>
            <label class="font-bold mb-1">Apellido Materno <span class="text-red-500">*</span></label>
            <InputText v-model="form.apellido_materno" fluid :disabled="investorExists" />
          </div>

          <!-- Mostrar información adicional si es consulta externa -->
          <div v-if="dniConsultaExterna && !investorExists"
            class="p-3 bg-blue-50 border-1 border-blue-200 border-round">
            <p class="text-sm text-blue-800 mb-2">Información adicional encontrada:</p>
            <div class="grid grid-cols-2 gap-2 text-sm">
              <div v-if="dniConsultaExterna.fecha_nacimiento">
                <strong>Fecha nacimiento:</strong> {{ dniConsultaExterna.fecha_nacimiento }}
              </div>
              <div v-if="dniConsultaExterna.sexo">
                <strong>Sexo:</strong> {{ dniConsultaExterna.sexo }}
              </div>
              <div v-if="dniConsultaExterna.estado_civil">
                <strong>Estado civil:</strong> {{ dniConsultaExterna.estado_civil }}
              </div>
              <div v-if="dniConsultaExterna.direccion_completa" class="col-span-2">
                <strong>Dirección:</strong> {{ dniConsultaExterna.direccion_completa }}
              </div>
            </div>
          </div>

          <!-- Botones dinámicos -->
          <div class="flex gap-2">
            <Button v-if="investorExists && puedeContinuarCliente" label="Siguiente" icon="pi pi-arrow-right" fluid
              severity="contrast" rounded @click="continuarConInversorExistente" />
            <Button v-if="!investorExists && puedeContinuarCliente" label="Registrar y Continuar" icon="pi pi-user-plus"
              fluid severity="success" rounded @click="registrarNuevoInversor" />
          </div>
        </div>

        <!-- Paso 2: Inmueble -->
        <div v-if="mostrarInmueble" class="flex flex-col gap-4">
          <!-- Información del inversor seleccionado -->
          <div class="p-3 bg-green-50 border-1 border-green-200 border-round mb-4">
            <p class="text-sm text-green-800 mb-1">
              <i class="pi pi-user mr-2"></i>
              <strong>Cliente:</strong> {{ form.nombres }} {{ form.apellido_paterno }} {{ form.apellido_materno }}
            </p>
            <p class="text-xs text-green-600">DNI: {{ form.dni }}</p>
          </div>

          <div>
            <label class="font-bold mb-1">Nombre <span class="text-red-500">*</span></label>
            <InputText v-model="form.nombre" class="w-full" />
          </div>

          <div class="flex gap-4">
            <div class="w-1/2">
              <label class="font-bold mb-1">Departamento <span class="text-red-500">*</span></label>
              <Select v-model="form.departamento" :options="departamentos" optionLabel="ubigeo_name" dataKey="ubigeo_id"
                placeholder="Seleccione departamento" class="w-full" @change="onDepartamentoChange" />
            </div>
            <div class="w-1/2">
              <label class="font-bold mb-1">Provincia <span class="text-red-500">*</span></label>
              <Select v-model="form.provincia" :options="provincias" optionLabel="ubigeo_name" dataKey="ubigeo_id"
                placeholder="Seleccione provincia" class="w-full" :disabled="!form.departamento"
                @change="onProvinciaChange" />
            </div>
          </div>

          <div>
            <label class="font-bold mb-1">Distrito <span class="text-red-500">*</span></label>
            <Select v-model="form.distrito" :options="distritos" optionLabel="ubigeo_name" dataKey="ubigeo_id"
              placeholder="Seleccione distrito" class="w-full" :disabled="!form.provincia" />
          </div>

          <div>
            <label class="font-bold mb-1">Dirección <span class="text-red-500">*</span></label>
            <InputText v-model="form.direccion" class="w-full" />
          </div>

          <div>
            <label class="font-bold mb-1">Descripción</label>
            <Textarea v-model="form.descripcion" rows="3" class="w-full" autoResize />
          </div>

          <div class="flex gap-4">
            <div class="w-1/2">
              <label class="font-bold mb-1">Moneda <span class="text-red-500">*</span></label>
              <Select v-model="form.currency_id" :options="monedas" optionLabel="label" optionValue="value"
                placeholder="Selecciona moneda" class="w-full" />
            </div>
            <div class="w-1/2">
              <label class="font-bold mb-1">Valor de la propiedad <span class="text-red-500">*</span></label>
              <InputNumber v-model="form.valor_estimado" class="w-full" :useGrouping="true" :locale="'es-PE'" />
            </div>
          </div>

          <div>
            <label class="font-bold mb-1">Monto requerido <span class="text-red-500">*</span></label>
            <InputNumber v-model="form.valor_requerido" class="w-full" :useGrouping="true" :locale="'es-PE'" />
          </div>

          <!-- Sección de imágenes mejorada -->
          <div class="flex flex-col gap-4">
            <div>
              <label class="block font-bold mb-2">Imágenes <span class="text-red-500">*</span></label>
              <p class="text-sm text-gray-600 mb-2">Se requieren al menos 5 imágenes con sus respectivas descripciones</p>
              <FileUpload ref="fileUpload" name="imagenes[]" :multiple="true" accept="image/*" :maxFileSize="1000000"
                customUpload :auto="false" @select="onSelectedFiles" @upload="onTemplatedUpload"
                :showUploadButton="false" :showCancelButton="false" />
            </div>

            <!-- Botón para limpiar todas las imágenes -->
            <div v-if="imagenesConDescripcion.length > 0" class="flex justify-end">
              <Button 
                label="Limpiar todas" 
                icon="pi pi-trash" 
                severity="danger" 
                size="small" 
                outlined
                @click="clearAllImages" 
              />
            </div>

            <!-- Lista de imágenes seleccionadas con campos de descripción -->
            <div v-if="imagenesConDescripcion.length > 0" class="flex flex-col gap-3">
              <h4 class="font-bold text-sm">Imágenes seleccionadas ({{ imagenesConDescripcion.length }})</h4>
              <div v-for="(imagen, index) in imagenesConDescripcion" :key="index"
                class="flex gap-3 p-3 border-1 border-300 border-round items-start">
                
                <!-- Preview de la imagen -->
                <div class="flex-shrink-0">
                  <img :src="imagen.preview" :alt="`Imagen ${index + 1}`" 
                    class="w-20 h-20 object-cover border-round" />
                </div>

                <!-- Información y descripción -->
                <div class="flex-grow flex flex-col gap-2">
                  <div class="text-sm">
                    <strong>{{ imagen.file.name }}</strong>
                    <span class="text-gray-500 ml-2">({{ formatFileSize(imagen.file.size) }})</span>
                  </div>
                  
                  <!-- Campo de descripción con límite de 35 caracteres -->
                  <div>
                    <label class="text-xs font-semibold mb-1 block">
                      Descripción (máx. 35 caracteres) <span class="text-red-500">*</span>
                    </label>
                    <InputText 
                      v-model="imagen.description" 
                      placeholder="Ej: Fachada, Sala, Cocina, etc."
                      class="w-full text-sm"
                      :class="{ 'p-invalid': submitted && !imagen.description?.trim() }"
                      maxlength="35"
                      @input="onDescriptionInput($event, index)"
                    />
                    <div class="flex justify-between items-center mt-1">
                      <small v-if="submitted && !imagen.description?.trim()" class="text-red-500">
                        La descripción es requerida
                      </small>
                      <small class="text-xs" :class="{
                        'text-orange-500': imagen.description?.length >= 30,
                        'text-red-500': imagen.description?.length >= 35,
                        'text-gray-500': !imagen.description?.length || imagen.description.length < 30
                      }">
                        {{ imagen.description?.length || 0 }}/35
                      </small>
                    </div>
                  </div>
                </div>

                <!-- Botón para eliminar -->
                <Button icon="pi pi-times" severity="danger" size="small" outlined
                  @click="removeImage(index)" />
              </div>

              <!-- Contador y validación -->
              <div class="text-sm">
                <span v-if="imagenesConDescripcion.length < 5" class="text-orange-600">
                  <i class="pi pi-exclamation-triangle mr-1"></i>
                  Necesitas al menos {{ 5 - imagenesConDescripcion.length }} imagen(es) más
                </span>
                <span v-else class="text-green-600">
                  <i class="pi pi-check-circle mr-1"></i>
                  Imágenes suficientes ({{ imagenesConDescripcion.length }}/5 mínimo)
                </span>
              </div>
            </div>

            <div v-else class="text-sm text-gray-500 p-3 border-1 border-300 border-round text-center">
              <i class="pi pi-images mr-2"></i>
              No hay imágenes seleccionadas
            </div>
          </div>

          <!-- Botón para regresar al paso anterior -->
          <Button label="Volver a datos del cliente" icon="pi pi-arrow-left" severity="secondary" outlined
            @click="volverADatosCliente" class="mt-2" />
        </div>
      </div>
    </form>

    <template #footer>
      <Button label="Cancelar" icon="pi pi-times" severity="secondary" text @click="cancelForm" />
      <Button v-if="mostrarInmueble" label="Guardar Propiedad" icon="pi pi-check" severity="contrast"
        @click="saveProperty" :loading="guardandoPropiedad" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Toast from 'primevue/toast'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import FileUpload from 'primevue/fileupload'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'

interface ImagenConDescripcion {
  file: File
  description: string
  preview: string
}

const toast = useToast()
const emit = defineEmits(['agregado'])
const modalVisible = ref(false)
const submitted = ref(false)
const fileUpload = ref()
const mostrarInmueble = ref(false)
const guardandoPropiedad = ref(false)

// Estados para manejo de inversor
const investorExists = ref(false)
const investorId = ref<number | null>(null)
const dniConsultaExterna = ref<any>(null)

const initialForm = {
  dni: '',
  nombres: '',
  apellido_paterno: '',
  apellido_materno: '',
  nombre: '',
  departamento: null,
  provincia: null,
  distrito: null,
  direccion: '',
  descripcion: '',
  valor_estimado: null,
  valor_requerido: null,
  currency_id: null,
}

const form = ref({ ...initialForm })

const monedas = [
  { label: 'PEN (S/)', value: 1 },
  { label: 'USD ($)', value: 2 }
]

// Nueva estructura para manejar imágenes con descripciones
const imagenesConDescripcion = ref<ImagenConDescripcion[]>([])
const totalSize = ref(0)
const totalSizePercent = ref(0)

const departamentos = ref<any[]>([])
const provincias = ref<any[]>([])
const distritos = ref<any[]>([])

onMounted(async () => {
  try {
    const { data } = await axios.get('https://novalink.oswa.workers.dev/api/v1/peru/ubigeo')
    departamentos.value = data
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al cargar departamentos', life: 3000 })
  }
})

const resetForm = () => {
  form.value = { ...initialForm }
  mostrarInmueble.value = false
  investorExists.value = false
  investorId.value = null
  dniConsultaExterna.value = null
  provincias.value = []
  distritos.value = []
  imagenesConDescripcion.value = []
  totalSize.value = 0
  totalSizePercent.value = 0
  if (fileUpload.value) fileUpload.value.clear()
  submitted.value = false
}

const cancelForm = () => {
  resetForm()
  modalVisible.value = false
}

const volverADatosCliente = () => {
  mostrarInmueble.value = false
}

const onDepartamentoChange = () => {
  form.value.provincia = null
  form.value.distrito = null
  provincias.value = form.value.departamento?.provinces || []
  distritos.value = []
}

const onProvinciaChange = () => {
  form.value.distrito = null
  distritos.value = form.value.provincia?.districts || []
}

// Función para manejar el input de descripción con límite de caracteres
const onDescriptionInput = (event: Event, index: number) => {
  const target = event.target as HTMLInputElement
  const value = target.value
  
  // Limitar a 35 caracteres
  if (value.length > 35) {
    target.value = value.substring(0, 35)
    imagenesConDescripcion.value[index].description = target.value
    
    toast.add({
      severity: 'warn',
      summary: 'Límite alcanzado',
      detail: 'La descripción no puede exceder 35 caracteres',
      life: 2000
    })
  }
}

// FUNCIÓN CORREGIDA - Evita duplicación de imágenes
const onSelectedFiles = (event: any) => {
  const files = [...event.files]
  
  // LIMPIAR el array antes de agregar las nuevas imágenes
  imagenesConDescripcion.value = []
  
  // Crear previews y estructura para cada imagen
  files.forEach(file => {
    const reader = new FileReader()
    reader.onload = (e) => {
      imagenesConDescripcion.value.push({
        file: file,
        description: '',
        preview: e.target?.result as string
      })
    }
    reader.readAsDataURL(file)
  })

  totalSize.value = files.reduce((acc, file) => acc + file.size, 0)
  totalSizePercent.value = totalSize.value / 10000
}

// Nueva función para limpiar todas las imágenes
const clearAllImages = () => {
  imagenesConDescripcion.value = []
  totalSize.value = 0
  totalSizePercent.value = 0
  if (fileUpload.value) {
    fileUpload.value.clear()
  }
  toast.add({
    severity: 'info',
    summary: 'Imágenes eliminadas',
    detail: 'Todas las imágenes han sido eliminadas',
    life: 2000
  })
}

const removeImage = (index: number) => {
  imagenesConDescripcion.value.splice(index, 1)
  
  // Recalcular el tamaño total
  totalSize.value = imagenesConDescripcion.value.reduce((acc, img) => acc + img.file.size, 0)
  totalSizePercent.value = totalSize.value / 10000
  
  // Actualizar el FileUpload con los archivos restantes
  if (fileUpload.value) {
    const remainingFiles = imagenesConDescripcion.value.map(img => img.file)
    fileUpload.value.files = remainingFiles
  }
}

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const onTemplatedUpload = () => {
  toast.add({ severity: 'info', summary: 'Éxito', detail: 'Archivo subido', life: 3000 })
}

const handleDniInput = async () => {
  const dni = form.value.dni

  // Resetear estados
  investorExists.value = false
  investorId.value = null
  dniConsultaExterna.value = null

  if (dni.length === 8) {
    try {
      const { data } = await axios.get(`/dni/${dni}`)

      if (data.success && data.data) {
        // Verificar si es consulta local (inversor existe) o externa
        if (data.message === 'Datos obtenidos localmente.') {
          // Inversor ya existe en la base de datos
          investorExists.value = true
          investorId.value = data.data.id || null
          form.value.nombres = data.data.nombres || ''
          form.value.apellido_paterno = data.data.apellido_paterno || ''
          form.value.apellido_materno = data.data.apellido_materno || ''

          toast.add({
            severity: 'info',
            summary: 'Cliente encontrado',
            detail: 'El cliente ya está registrado en el sistema',
            life: 3000
          })
        } else {
          // Consulta externa exitosa (RENIEC)
          investorExists.value = false
          dniConsultaExterna.value = data.data

          form.value.nombres = data.data.nombres || ''
          form.value.apellido_paterno = data.data.apellido_paterno || ''
          form.value.apellido_materno = data.data.apellido_materno || ''

          toast.add({
            severity: 'success',
            summary: 'Datos encontrados',
            detail: 'Datos obtenidos de RENIEC. Presiona "Registrar y Continuar"',
            life: 4000
          })
        }
      } else {
        toast.add({
          severity: 'warn',
          summary: 'No encontrado',
          detail: 'No se encontraron datos para el DNI',
          life: 3000
        })
      }
    } catch (error) {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Error al consultar DNI',
        life: 3000
      })
    }
  } else {
    // Limpiar campos si el DNI no tiene 8 dígitos
    form.value.nombres = ''
    form.value.apellido_paterno = ''
    form.value.apellido_materno = ''
  }
}

const puedeContinuarCliente = computed(() => {
  return (
    form.value.dni.length === 8 &&
    form.value.nombres.trim() !== '' &&
    form.value.apellido_paterno.trim() !== '' &&
    form.value.apellido_materno.trim() !== ''
  )
})

const continuarConInversorExistente = () => {
  mostrarInmueble.value = true
  toast.add({
    severity: 'info',
    summary: 'Continuar',
    detail: 'Ahora ingresa los datos de la propiedad',
    life: 3000
  })
}

const registrarNuevoInversor = async () => {
  try {
    const investorData = {
      name: form.value.nombres,
      first_last_name: form.value.apellido_paterno,
      second_last_name: form.value.apellido_materno,
      document: form.value.dni
    }

    const response = await axios.post('/customer', investorData)

    if (response.data.success) {
      investorId.value = response.data.data.id
      investorExists.value = true
      mostrarInmueble.value = true

      toast.add({
        severity: 'success',
        summary: 'Cliente registrado',
        detail: 'Cliente registrado exitosamente. Ahora ingresa los datos de la propiedad',
        life: 4000
      })
    }
  } catch (error: any) {
    const errorMessage = error.response?.data?.message || 'Error al registrar el cliente'
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: errorMessage,
      life: 3000
    })
  }
}

const saveProperty = async () => {
  submitted.value = true
  guardandoPropiedad.value = true

  if (!mostrarInmueble.value) {
    toast.add({
      severity: 'warn',
      summary: 'Validación',
      detail: 'Completa los datos del cliente primero',
      life: 3000
    })
    guardandoPropiedad.value = false
    return
  }

  if (!investorId.value) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se ha seleccionado un inversor válido',
      life: 3000
    })
    guardandoPropiedad.value = false
    return
  }

  // Validar campos requeridos del inmueble
  if (
    !form.value.nombre ||
    !form.value.departamento ||
    !form.value.provincia ||
    !form.value.distrito ||
    !form.value.direccion ||
    !form.value.currency_id ||
    !form.value.valor_requerido ||
    !form.value.valor_estimado
  ) {
    toast.add({
      severity: 'warn',
      summary: 'Validación',
      detail: 'Completa todos los campos requeridos del inmueble',
      life: 3000
    })
    guardandoPropiedad.value = false
    return
  }

  // Validación de imágenes
  if (!imagenesConDescripcion.value || imagenesConDescripcion.value.length === 0) {
    toast.add({
      severity: 'warn',
      summary: 'Imágenes requeridas',
      detail: 'Debes subir al menos 5 imágenes de la propiedad',
      life: 4000
    })
    guardandoPropiedad.value = false
    return
  }

  if (imagenesConDescripcion.value.length < 5) {
    toast.add({
      severity: 'warn',
      summary: 'Imágenes insuficientes',
      detail: `Has subido ${imagenesConDescripcion.value.length} imagen(es). Se requieren al menos 5 imágenes.`,
      life: 4000
    })
    guardandoPropiedad.value = false
    return
  }

  // Validar que todas las imágenes tengan descripción
  const imagenessSinDescripcion = imagenesConDescripcion.value.filter(img => !img.description?.trim())
  if (imagenessSinDescripcion.length > 0) {
    toast.add({
      severity: 'warn',
      summary: 'Descripciones requeridas',
      detail: `${imagenessSinDescripcion.length} imagen(es) no tienen descripción. Todas las imágenes deben tener una descripción.`,
      life: 4000
    })
    guardandoPropiedad.value = false
    return
  }

  try {
    const formData = new FormData()

    // Agregar el investor_id
    formData.append('investor_id', investorId.value.toString())

    // Agregar campos del inmueble
    formData.append('nombre', form.value.nombre)
    formData.append('direccion', form.value.direccion)
    formData.append('descripcion', form.value.descripcion || '')
    formData.append('valor_estimado', form.value.valor_estimado?.toString() || '')
    formData.append('valor_requerido', form.value.valor_requerido?.toString() || '')
    formData.append('currency_id', form.value.currency_id?.toString() || '')

    // Agregar datos de ubicación
    if (form.value.departamento) {
      formData.append('departamento', form.value.departamento.ubigeo_name)
      formData.append('departamento_id', form.value.departamento.ubigeo_id)
    }
    if (form.value.provincia) {
      formData.append('provincia', form.value.provincia.ubigeo_name)
      formData.append('provincia_id', form.value.provincia.ubigeo_id)
    }
    if (form.value.distrito) {
      formData.append('distrito', form.value.distrito.ubigeo_name)
      formData.append('distrito_id', form.value.distrito.ubigeo_id)
    }

    // Agregar imágenes y descripciones
    imagenesConDescripcion.value.forEach((imagen, index) => {
      formData.append('imagenes[]', imagen.file)
      formData.append(`descriptions[${index}]`, imagen.description)
    })

    const response = await axios.post('/property', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Propiedad registrada correctamente',
      life: 3000
    })

    resetForm()
    modalVisible.value = false
    emit('agregado')

  } catch (error: any) {
    console.error('Error al guardar propiedad:', error)
    
    // Manejo específico de errores de validación del backend
    if (error.response?.status === 422 && error.response?.data?.errors) {
      const errors = error.response.data.errors
      
      // Verificar si hay errores relacionados con imágenes
      if (errors.imagenes || errors['imagenes.*']) {
        let imageErrorMessage = ''
        
        if (errors.imagenes) {
          // Errores del array de imágenes
          errors.imagenes.forEach(msg => {
            if (msg.includes('menos 5') || msg.includes('min:5')) {
              imageErrorMessage = 'Se requieren al menos 5 imágenes de la propiedad'
            } else if (msg.includes('obligatori') || msg.includes('required')) {
              imageErrorMessage = 'Las imágenes son obligatorias'
            } else {
              imageErrorMessage = msg
            }
          })
        }
        
        if (errors['imagenes.*']) {
          // Errores de imágenes individuales
          errors['imagenes.*'].forEach(msg => {
            if (msg.includes('imagen válida') || msg.includes('image')) {
              imageErrorMessage = imageErrorMessage || 'Una o más imágenes no tienen un formato válido'
            } else if (msg.includes('2MB') || msg.includes('max')) {
              imageErrorMessage = imageErrorMessage || 'Una o más imágenes superan el tamaño máximo de 2MB'
            } else if (msg.includes('jpeg') || msg.includes('mimes')) {
              imageErrorMessage = imageErrorMessage || 'Las imágenes deben ser de tipo: JPEG, PNG, JPG, GIF o WEBP'
            }
          })
        }
        
        toast.add({
          severity: 'warn',
          summary: 'Error en las imágenes',
          detail: imageErrorMessage || 'Verifica las imágenes seleccionadas',
          life: 5000
        })
      } else {
        // Otros errores de validación
        const firstError = Object.values(errors)[0]
        const errorMessage = Array.isArray(firstError) ? firstError[0] : firstError
        
        toast.add({
          severity: 'warn',
          summary: 'Error de validación',
          detail: errorMessage || 'Verifica los datos ingresados',
          life: 4000
        })
      }
    } else {
      // Errores generales
      const errorMessage = error.response?.data?.message || 'Error al guardar la propiedad'
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: errorMessage,
        life: 3000
      })
    }
  } finally {
    guardandoPropiedad.value = false
  }
}
</script>