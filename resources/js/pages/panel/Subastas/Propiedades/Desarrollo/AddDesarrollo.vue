<template>
  <Toast />

  <Toolbar class="mb-6">
    <template #start>
      <Button label="Nuevo" icon="pi pi-plus" severity="secondary" class="mr-2" @click="modalVisible = true" />
    </template>
  </Toolbar>

  <Dialog v-model:visible="modalVisible" :header="mostrarInmueble ? 'Registro Inmueble' : 'Registro Solicitud'"  :modal="true" :style="{ width: '650px' }">
    <form @submit.prevent="saveProperty" class="p-fluid">
      <div class="flex flex-col gap-4">
        <!-- Paso 1: Cliente -->
        <div v-if="!mostrarInmueble" class="flex flex-col gap-4 p-3 border-1 border-300 border-round">
          <div>
            <label class="font-bold mb-1">DNI <span class="text-red-500">(*)</span></label>
            <InputText v-model="form.dni" fluid maxlength="8" @input="handleDniInput" />
          </div>
          <!-- NOMBRES -->
          <div class="flex gap-4">
            <div class="w-1/2">
              <label class="font-bold mb-1">Nombres <span class="text-red-500">(*)</span></label>
              <InputText v-model="form.nombres" fluid :disabled="investorExists" />
            </div>
            <!-- APELLIDOS -->
            <div class="w-1/2">
              <label class="font-bold mb-1">Apellidos Paterno/Materno <span class="text-red-500">(*)</span></label>
              <InputText v-model="form.apellidos" fluid :disabled="investorExists" />
            </div>
          </div>      
          <!-- Campos de Detalles del Cliente -->          
          <h6 class="font-bold text-900 mb-3 flex items-center gap-2">
            <i class="pi pi-briefcase text-orange-600"></i>
            Detalles del Cliente
          </h6>
          <div>
            <label class="font-bold mb-1">Fuente de Ingresos <span class="text-red-500">(*)</span></label>
            <InputText v-model="form.fuente_ingreso" fluid />
          </div>
          <div>
           <label class="font-bold mb-1">Profesión / Ocupación <span class="text-red-500">(*)</span></label>
            <InputText v-model="form.profesion_ocupacion" fluid />
          </div>
          <div>
            <label class="font-bold mb-1">Ingreso Promedio (S/.) <span class="text-red-500">(*)</span></label>
            <InputNumber v-model="form.ingreso_promedio" mode="currency" currency="PEN" locale="es-PE" fluid />
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
          <div class="p-3 bg-green-50 border-1 border-green-200 rounded-lg border-round mb-4">
            <p class="text-sm text-green-800 mb-1">
              <i class="pi pi-file mr-2"></i>
              <strong>Código de Solicitud:</strong> <span class="text-lg font-bold">{{ form.numero_solicitud }}</span>
            </p>
            <p class="text-sm text-green-800 mb-1">
              <i class="pi pi-user mr-2"></i>
              <strong>Cliente:</strong> {{ form.nombres }} {{ form.apellidos }}
            </p>
            <p class="text-xs text-green-800 mb-1">
              <i class="pi pi-id-card mr-2"></i>
              <strong>DNI: </strong> {{ form.dni }}
            </p>
          </div> 
          <!-- Sección de Datos del Inmueble -->
          <div v-for="(inmueble, index) in inmuebles" :key="index" class="flex flex-col gap-4 p-3 bg-gray-100 rounded-lg shadow-sm w-full">
          <!-- Tipo de inmueble y La propierdad pertenece a -->
           <Button label="Eliminar inmueble" icon="pi pi-trash" severity="danger"
            v-if="inmuebles.length > 1"
            @click="removeInmueble(index)" />
            <div class="flex gap-4">
              <div class="w-1/2">
                <label class="font-bold mb-2">
                  Tipo de Inmueble <span class="text-red-500">(*)</span>
                </label>
                <Select
                  v-model="inmueble.id_tipo_inmueble"
                  :options="tiposInmueble"
                  option-label="nombre_tipo_inmueble"
                  option-value="id_tipo_inmueble"
                  placeholder="Selecciona un tipo de inmueble"
                  class="w-full"
                />
              </div>
              <div class="w-1/2">
                <label class="font-bold mb-2">
                  La Propierdad Pertenece a <span class="text-red-500">(*)</span>
                </label>
                <InputText v-model="inmueble.pertenece" class="w-full" />
              </div>
            </div>

            <div>
              <label class="font-bold mb-1">Nombre <span class="text-red-500">(*)</span></label>
              <InputText v-model="inmueble.nombre" class="w-full" />
            </div>
            <!-- Departamento/Porinvia/Distrito -->
            <div class="flex gap-3">
              <div class="w-1/2">
                <label class="font-bold mb-1">Departamento <span class="text-red-500">(*)</span></label>
                <Select v-model="inmueble.departamento" :options="departamentos" optionLabel="ubigeo_name" dataKey="ubigeo_id"
                  placeholder="Seleccione departa..." class="w-full" @change="onDepartamentoChange(inmueble)" appendTo="self" />
              </div>
              <div class="w-1/2">
                <label class="font-bold mb-1">Provincia <span class="text-red-500">(*)</span></label>
                <Select v-model="inmueble.provincia" :options="inmueble.provincias" optionLabel="ubigeo_name" dataKey="ubigeo_id"
                  placeholder="Seleccione provincia" class="w-full" :disabled="!inmueble.departamento"
                  @change="onProvinciaChange(inmueble)" appendTo="self"/>
              </div>
              <div>
                <label class="font-bold mb-1">Distrito <span class="text-red-500">(*)</span></label>
                <Select v-model="inmueble.distrito" :options="inmueble.distritos" optionLabel="ubigeo_name" dataKey="ubigeo_id"
                  placeholder="Seleccione distrito" class="w-full" :disabled="!inmueble.provincia" appendTo="self" />
              </div>
            </div> 
            <!-- Dirección -->      
            <div>
              <label class="font-bold mb-1">Dirección <span class="text-red-500">(*)</span></label>
              <InputText v-model="inmueble.direccion" class="w-full" />
            </div>
            <!-- Descripción -->
            <div>
              <label class="font-bold mb-1">Descripción <span class="text-red-500">(*)</span></label>
              <Textarea v-model="inmueble.descripcion" rows="3" class="w-full" autoResize />
            </div>
            
            <!-- Sección de imágenes mejorada -->
            <div class="flex flex-col gap-4">
              <div>
                <label class="block font-bold mb-2">Imágenes <span class="text-red-500">(*)</span></label>
                <p class="text-sm text-gray-600 mb-2">Se requieren al menos 3 imágenes con sus respectivas descripciones</p>
                <FileUpload ref="`fileUpload-${index}`" name="imagenes[]" :multiple="true" accept="image/*" :maxFileSize="1000000"
                  customUpload :auto="false" @select="onSelectedFiles($event, inmueble)" @upload="onTemplatedUpload"
                  :showUploadButton="false" :showCancelButton="false" />
              </div>

              <!-- Botón para limpiar todas las imágenes -->
              <div v-if="inmueble.imagenesConDescripcion.length > 0" class="flex justify-end">
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
              <div v-if="inmueble.imagenesConDescripcion?.length > 0" class="flex flex-col gap-3">
                <h4 class="font-bold text-sm">Imágenes seleccionadas ({{ inmueble.imagenesConDescripcion.length }})</h4>
                <div v-for="(imagen, imgIndex) in inmueble.imagenesConDescripcion" :key="index"
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
                        @input="onDescriptionInput($event, imgIndex, index)"
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
                    @click="removeImage(index, imgIndex)" />
                </div>

                <!-- Contador y validación -->
                <div class="text-sm">
                  <span v-if="inmueble.imagenesConDescripcion.length < 3" class="text-orange-600">
                    <i class="pi pi-exclamation-triangle mr-1"></i>
                    Necesitas al menos {{ 3 - inmueble.imagenesConDescripcion.length }} imagen(es) más
                  </span>
                  <span v-else class="text-green-600">
                    <i class="pi pi-check-circle mr-1"></i>
                    Imágenes suficientes ({{ inmueble.imagenesConDescripcion.length }}/3 mínimo)
                  </span>
                </div>
              </div>

              <div v-else class="text-sm text-gray-500 p-3 border-1 border-300 border-round text-center">
                <i class="pi pi-images mr-2"></i>
                No hay imágenes seleccionadas
              </div>
            </div>
          </div>
          <Button label="Agregar inmueble" icon="pi pi-plus" @click="addInmueble" />

          <!-- Moneda/Valor de la Porpiedad/Monto requerido -->
          <div class="flex gap-3">
            <div class="w-1/2">
              <label class="font-bold mb-1">Moneda <span class="text-red-500">(*)</span></label>
              <Select v-model="form.currency_id" :options="monedas" optionLabel="label" optionValue="value"
                placeholder="Selecciona moneda" class="w-full" />
            </div>
            <div class="w-1/2">
              <label class="font-bold mb-1">Valor de la Propiedad <span class="text-red-500">(*)</span></label>
              <InputNumber v-model="form.valor_estimado" class="w-full" :useGrouping="true" :locale="'es-PE'" />
            </div>
            <div>
              <label class="font-bold mb-1">Monto Requerido <span class="text-red-500"><span class="text-red-500">(*)</span></span></label>
              <InputNumber v-model="form.valor_requerido" class="w-full" :useGrouping="true" :locale="'es-PE'" />
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
import { ref, onMounted, computed, nextTick } from 'vue'
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
  preview: string | ArrayBuffer | null
}

const toast = useToast()
const emit = defineEmits(['agregado'])
const modalVisible = ref(false)
const submitted = ref(false)
const fileUpload = ref()
const mostrarInmueble = ref(false)
const guardandoPropiedad = ref(false)
// Carga de Tipo Inmueble
const tiposInmueble = ref<any[]>([])

// Estados para manejo de inversor
const investorExists = ref(false)
const investorId = ref<number | null>(null)
const dniConsultaExterna = ref<any>(null)

// Datos generales del cliente y la solicitud
const form = ref({
  // Cliente
  dni: '',
  nombres: '',
  apellidos: '',
  fuente_ingreso: '',
  profesion_ocupacion: '',
  ingreso_promedio: null,
  // Código de Solicitud
  numero_solicitud: '',
  // Datos globales (fuera del bucle de inmuebles)
  valor_estimado: null,
  valor_requerido: null,
  currency_id: null,
})

// Estructura base de un inmueble
const initialInmueble = {
  id_tipo_inmueble: null,
  nombre: '',
  pertenece: null,
  departamento: null,
  provincia: null,
  distrito: null,
  direccion: '',
  descripcion: '',
  provincias: [],
  distritos: [],
  imagenesConDescripcion: [] as { file: File; preview: string | ArrayBuffer | null; description: string }[],
}

// Lista de inmuebles (inicia con uno)
const inmuebles = ref<any[]>([
  { ...initialInmueble }
])

// Función para agregar inmueble
const addInmueble = () => {
  inmuebles.value.push({ ...initialInmueble })
}

// Función para eliminar inmueble
const removeInmueble = (index: number) => {
  inmuebles.value.splice(index, 1)
}


const monedas = [
  { label: 'PEN (S/)', value: 1 },
  { label: 'USD ($)', value: 2 }
]
// GENERAR CÓDIGO DE SOLICITUD
const generarNumeroSolicitud = (nombreCompleto: string): string => {
  // Separar el nombre completo en palabras
  const partes = nombreCompleto.trim().split(' ')

  // Tomar el primer nombre (ejemplo: "Juan")
  const primerNombre = partes[0] || ''

  // Tomar las 3 primeras letras en mayúsculas
  const cliente = primerNombre.substring(0, 3).toUpperCase()

  // Generar 2 letras aleatorias
  const letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
  let letrasAleatorias = ''
  for (let i = 0; i < 2; i++) {
    const index = Math.floor(Math.random() * letras.length)
    letrasAleatorias += letras[index]
  }

  // Generar número aleatorio de 1 a 9 (o más grande si quieres)
  const numero = Math.floor(Math.random() * 9) + 1

  // Año actual
  const año = new Date().getFullYear()

  return `${cliente}${letrasAleatorias}${numero}-${año}`
}

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

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/tipo-inmueble')
    tiposInmueble.value = data
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar los tipos de inmueble', life: 3000 })
  }
})

const resetForm = () => {
   form.value = {
    dni: '',
    nombres: '',
    apellidos: '',
    fuente_ingreso: '',
    profesion_ocupacion: '',
    ingreso_promedio: null,
    numero_solicitud: '',
    valor_estimado: null,
    valor_requerido: null,
    currency_id: null,
  }
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

const onDepartamentoChange = (inmueble: any) => {
  inmueble.provincia = null
  inmueble.distrito = null
  inmueble.provincias = inmueble.departamento?.provinces || []
  inmueble.distritos = []
}

const onProvinciaChange = (inmueble: any) => {
  inmueble.distrito = null
  inmueble.distritos = inmueble.provincia?.districts || []
}

// Función para manejar el input de descripción con límite de caracteres
const onDescriptionInput = (event: Event, imgIndex: number, inmuebleIndex: number) => {
  const target = event.target as HTMLInputElement
  let value = target.value

  if (value.length > 35) {
    value = value.substring(0, 35)
    toast.add({
      severity: 'warn',
      summary: 'Límite alcanzado',
      detail: 'La descripción no puede exceder 35 caracteres',
      life: 2000
    })
  }

  // Actualizar el valor en el inmueble correspondiente
  inmuebles.value[inmuebleIndex].imagenesConDescripcion[imgIndex].description = value
}

  
// FUNCIÓN CORREGIDA - Evita duplicación de imágenes
const onSelectedFiles = (event: any, inmueble: typeof initialInmueble) => {
  const files = [...event.files]
  
  inmueble.imagenesConDescripcion = []
  
  // Crear previews y estructura para cada imagen
  files.forEach(file => {
    const reader = new FileReader()
    reader.onload = (e) => {
      inmueble.imagenesConDescripcion.push({
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

const removeImage = (inmuebleIndex: number, imageIndex: number) => {
  inmuebles.value[inmuebleIndex].imagenesConDescripcion.splice(imageIndex, 1)
  
  // Recalcular el tamaño total
  totalSize.value = imagenesConDescripcion.value.reduce((acc, img) => acc + img.file.size, 0)
  totalSizePercent.value = totalSize.value / 10000
  const refName = `fileUpload-${inmuebleIndex}`
  const fu = ( (ref as any)[refName] )
  if (fu) {
    fu.files = inmuebles.value[inmuebleIndex].imagenesConDescripcion.map((i:any) => i.file)
  }
  
  // Actualizar el FileUpload con los archivos restantes
  // if (fileUpload.value) {
  //   const remainingFiles = imagenesConDescripcion.value.map(img => img.file)
  //   fileUpload.value.files = remainingFiles
  // }
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
          form.value.apellidos = `${data.data.apellido_paterno || ''} ${data.data.apellido_materno || ''}`.trim()
          // form.value.apellido_paterno = data.data.apellido_paterno || ''
          // form.value.apellido_materno = data.data.apellido_materno || ''

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
          form.value.apellidos = `${data.data.apellido_paterno || ''} ${data.data.apellido_materno || ''}`.trim()
          // form.value.apellido_paterno = data.data.apellido_paterno || ''
          // form.value.apellido_materno = data.data.apellido_materno || ''

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
    form.value.apellidos = ''
    // form.value.apellido_paterno = ''
    // form.value.apellido_materno = ''
  }
}

const puedeContinuarCliente = computed(() => {
  return (
    (form.value.dni?.length === 8) &&
    (form.value.nombres?.trim() !== '') &&
    (form.value.apellidos?.trim() !== '') &&
    (form.value.fuente_ingreso?.trim() !== '') &&
    (form.value.profesion_ocupacion?.trim() !== '') &&
    (form.value.ingreso_promedio !== null && form.value.ingreso_promedio !== '')
    // form.value.apellido_paterno.trim() !== '' &&
    // form.value.apellido_materno.trim() !== ''
  )
})

const continuarConInversorExistente = async () => {
  try {
    // 1. Guardar detalle en detalle_inversionista_hipoteca
    await axios.post('/api/detalle-inversionista', {
      investor_id: investorId.value, // este ya lo tienes guardado
      fuente_ingreso: form.value.fuente_ingreso,
      profesion_ocupacion: form.value.profesion_ocupacion,
      ingreso_promedio: form.value.ingreso_promedio,
    })

    // 2. Mostrar siguiente paso
    form.value.numero_solicitud = generarNumeroSolicitud(form.value.nombres)
    mostrarInmueble.value = true
    toast.add({
      severity: 'success',
      summary: 'Detalles guardados',
      detail: 'Se registraron los detalles del cliente. Ahora ingresa los datos de la propiedad',
      life: 3000
    })
  } catch (error: any) {
    const errorMessage = error.response?.data?.message || 'Error al guardar los detalles'
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: errorMessage,
      life: 3000
    })
  }
}

const registrarNuevoInversor = async () => {
  try {
    const [paterno, materno = ''] = form.value.apellidos.split(' ')

    // Datos base para el inversor
    const investorData = {
      name: form.value.nombres,
      first_last_name: paterno,
      second_last_name: materno,
      document: form.value.dni,
    }

    let nuevoInvestorId = null

    try {
      // 1. Intentamos registrar un nuevo inversor
      const response = await axios.post('/customer', investorData)
      if (response.data.success) {
        nuevoInvestorId = response.data.data.id
      }
    } catch (error: any) {
      // 2. Si el DNI ya existe, obtenemos el ID del inversor existente
      if (error.response?.status === 422 &&
          error.response?.data?.errors?.document?.includes('has already been taken')) {

        // Buscar investor existente por DNI
        const res = await axios.get(`/investors/by-document/${form.value.dni}`)
        nuevoInvestorId = res.data.id
      } else {
        throw error // Si es otro error, lo lanzamos
      }
    }

    // 3. Guardamos el detalle en la tabla detalle_inversionista_hipoteca
    await axios.post('/api/detalle-inversionista', {
      investor_id: nuevoInvestorId,
      fuente_ingreso: form.value.fuente_ingreso,
      profesion_ocupacion: form.value.profesion_ocupacion,
      ingreso_promedio: form.value.ingreso_promedio,
    })

    investorId.value = nuevoInvestorId
    investorExists.value = true
    mostrarInmueble.value = true
    form.value.numero_solicitud = generarNumeroSolicitud(form.value.nombres)

    toast.add({
      severity: 'success',
      summary: 'Cliente registrado',
      detail: 'Cliente y sus detalles registrados exitosamente. Ahora ingresa los datos de la propiedad',
      life: 4000
    })
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

  console.log('Validando inmuebles:', JSON.stringify(inmuebles.value, null, 2));

  // ✅ Validar campos globales antes del loop de inmuebles
  if (!form.value.currency_id || !form.value.valor_estimado || !form.value.valor_requerido) {
    toast.add({
      severity: 'warn',
      summary: 'Validación',
      detail: 'Completa todos los campos generales de la propiedad (moneda, valor estimado, monto requerido)',
      life: 4000
    })
    guardandoPropiedad.value = false
    return
  }

  // Validar campos requeridos de cada inmueble
  for (const [idx, inm] of inmuebles.value.entries()) {
    if (
      !inm.nombre?.trim() ||
      !inm.direccion?.trim() ||
      !inm.id_tipo_inmueble ||
      !inm.departamento?.ubigeo_id ||
      !inm.provincia?.ubigeo_id ||
      !inm.distrito?.ubigeo_id
    ) {
      toast.add({
        severity: 'warn',
        summary: 'Validación',
        detail: `Completa todos los campos requeridos del inmueble #${idx + 1}`,
        life: 3000
      })
      guardandoPropiedad.value = false
      return
    }

    // Validación de imágenes de cada inmueble
    const imgs = inm.imagenesConDescripcion || []

    if (imgs.length < 3) {
      toast.add({
        severity: 'warn',
        summary: 'Imágenes insuficientes',
        detail: `El inmueble #${idx + 1} tiene ${imgs.length} imagen(es). Se requieren al menos 3 imágenes.`,
        life: 4000
      })
      guardandoPropiedad.value = false
      return
    }

    const sinDescripcion = imgs.filter(img => !img.description?.trim())
    if (sinDescripcion.length > 0) {
      toast.add({
        severity: 'warn',
        summary: 'Descripciones requeridas',
        detail: `El inmueble #${idx + 1} tiene ${sinDescripcion.length} imagen(es) sin descripción.`,
        life: 4000
      })
      guardandoPropiedad.value = false
      return
    }
  }

  try {
    const formData = new FormData()

    // Agregar campos globales
    formData.append('investor_id', investorId.value.toString())
    formData.append('numero_solicitud', form.value.numero_solicitud)
    // formData.append('currency_id', form.value.currency_id)
    // formData.append('valor_estimado', form.value.valor_estimado)
    // formData.append('valor_requerido', form.value.valor_requerido)

    // Agregar cada inmueble
    inmuebles.value.forEach((inmueble, idx) => {
      formData.append(`properties[${idx}][nombre]`, inmueble.nombre)
      formData.append(`properties[${idx}][direccion]`, inmueble.direccion)
      formData.append(`properties[${idx}][id_tipo_inmueble]`, inmueble.id_tipo_inmueble)
      formData.append(`properties[${idx}][valor_estimado]`, inmueble.valor_estimado ?? '0');
      formData.append(`properties[${idx}][valor_requerido]`, inmueble.valor_requerido ?? '0');
      formData.append(`properties[${idx}][currency_id]`, inmueble.currency_id?.toString() || '1');
      formData.append(`properties[${idx}][descripcion]`, inmueble.descripcion || '')
      formData.append(`properties[${idx}][pertenece]`, inmueble.pertenece || '')

      
      if (inmueble.departamento) {
        formData.append(`properties[${idx}][departamento]`, inmueble.departamento.ubigeo_name)
        formData.append(`properties[${idx}][departamento_id]`, inmueble.departamento.ubigeo_id)
      }
      if (inmueble.provincia) {
        formData.append(`properties[${idx}][provincia]`, inmueble.provincia.ubigeo_name)
        formData.append(`properties[${idx}][provincia_id]`, inmueble.provincia.ubigeo_id)
      }
      if (inmueble.distrito) {
        formData.append(`properties[${idx}][distrito]`, inmueble.distrito.ubigeo_name)
        formData.append(`properties[${idx}][distrito_id]`, inmueble.distrito.ubigeo_id)
      }

      // imágenes
      inmueble.imagenesConDescripcion?.forEach((imagen: ImagenConDescripcion, imgIdx: number) => {
        formData.append(`properties[${idx}][imagenes][]`, imagen.file)
        // Si quieres, puedes descomentar la descripción:
        // formData.append(`properties[${idx}][descriptions][${imgIdx}]`, imagen.description)
      })
    })

    await axios.post('/property', formData, {
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
    // aquí se mantiene toda tu lógica de manejo de errores
  } finally {
    guardandoPropiedad.value = false
  }
}

</script>