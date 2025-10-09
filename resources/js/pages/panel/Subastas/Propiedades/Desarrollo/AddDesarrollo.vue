<template>
  <Toast />

  <Toolbar class="mb-6">
    <template #start>
      <Button label="Nuevo" icon="pi pi-plus" severity="secondary" class="mr-2" @click="modalVisible = true" />
    </template>
  </Toolbar>

  <Dialog v-model:visible="modalVisible" :header="mostrarInmueble ? 'Registro Inmueble' : 'Registro Solicitud'"
    :modal="true" :style="{ width: '500px' }">
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
              <label class="font-bold mb-1">Apellidos <span class="text-red-500">*</span></label>
              <InputText v-model="form.apellidos" fluid :disabled="investorExists" />
            </div>
          </div>
          <div v-if="!investorExists">
            <label class="font-bold mb-1">Detalles del cliente</label>
            <div>
              <label class="font-bold mb-1">Fuente de Ingresos <span class="text-red-500">*</span></label>
              <InputText v-model="form.fuente_ingreso" fluid />
            </div>

            <div>
              <label class="font-bold mb-1">Profesión / Ocupación <span class="text-red-500">*</span></label>
              <InputText v-model="form.profesion_ocupacion" fluid />
            </div>

            <div>
              <label class="font-bold mb-1">Ingreso Promedio (S/.) <span class="text-red-500">*</span></label>
              <InputNumber v-model="form.ingreso_promedio" mode="currency" currency="PEN" locale="es-PE" fluid />
            </div>
          </div>

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

          <div class="flex gap-2">
            <Button v-if="investorExists && puedeContinuarCliente" label="Siguiente" icon="pi pi-arrow-right" fluid
              severity="contrast" rounded @click="continuarConInversorExistente" />
            <Button v-if="!investorExists && puedeContinuarCliente" label="Registrar y Continuar" icon="pi pi-user-plus"
              fluid severity="success" rounded @click="registrarNuevoInversor" />
          </div>
        </div>

        <!-- Paso 2: Inmueble -->
        <div v-if="mostrarInmueble" class="flex flex-col gap-4">
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

          <!-- Navegación de inmuebles si hay más de uno -->
          <div v-if="inmuebles.length > 1" class="flex flex-wrap gap-2 mb-4">
            <Button
              v-for="(inm, idx) in inmuebles"
              :key="idx"
              :severity="inmuebleActivo === idx ? 'contrast' : 'secondary'"
              :outlined="inmuebleActivo !== idx"
              size="small"
              @click="editarInmueble(idx)"
            >
              <template #default>
                <span class="flex items-center gap-2">
                  <i :class="inm.bloqueado ? 'pi pi-lock' : 'pi pi-home'"></i>
                  Inmueble {{ idx + 1 }}
                  <i v-if="validarInmuebleCompleto(inm)" class="pi pi-check-circle text-green-500"></i>
                </span>
              </template>
            </Button>
          </div>

          <!-- Mostrar solo el inmueble activo -->
          <div class="flex flex-col gap-4 p-3 bg-gray-100 rounded-lg shadow-sm w-full">
            <div class="flex justify-between items-center mb-2">
              <h3 class="text-lg font-bold">Inmueble #{{ inmuebleActivo + 1 }}</h3>
              <Button
                v-if="inmuebles.length > 1"
                label="Eliminar"
                icon="pi pi-trash"
                severity="danger"
                size="small"
                outlined
                @click="removeInmueble(inmuebleActivo)"
              />
            </div>

            <div class="flex gap-4">
              <div class="w-1/2">
                <label class="font-bold mb-2">Tipo de Inmueble <span class="text-red-500">(*)</span></label>
                <Select
                  v-model="inmuebles[inmuebleActivo].id_tipo_inmueble"
                  :options="tiposInmueble"
                  option-label="nombre_tipo_inmueble"
                  option-value="id_tipo_inmueble"
                  placeholder="Selecciona un tipo de inmueble"
                  class="w-full"
                  :disabled="inmuebles[inmuebleActivo].bloqueado"
                />
              </div>
              <div class="w-1/2">
                <label class="font-bold mb-2">La Propiedad Pertenece a <span class="text-red-500">(*)</span></label>
                <Select
                  v-model="inmuebles[inmuebleActivo].pertenece"
                  :options="tiposPertenencia"
                  option-label="label"
                  option-value="value"
                  placeholder="Selecciona a quién pertenece"
                  class="w-full"
                  :disabled="inmuebles[inmuebleActivo].bloqueado"
                />
              </div>
            </div>

            <div>
              <label class="font-bold mb-1">Nombre <span class="text-red-500">(*)</span></label>
              <InputText
                v-model="inmuebles[inmuebleActivo].nombre"
                class="w-full"
                :disabled="inmuebles[inmuebleActivo].bloqueado"
              />
            </div>

            <div class="flex gap-3">
              <div class="w-1/2">
                <label class="font-bold mb-1">Departamento <span class="text-red-500">(*)</span></label>
                <Select
                  v-model="inmuebles[inmuebleActivo].departamento"
                  :options="departamentos"
                  optionLabel="ubigeo_name"
                  dataKey="ubigeo_id"
                  placeholder="Seleccione departa..."
                  class="w-full"
                  @change="onDepartamentoChange(inmuebles[inmuebleActivo])"
                  appendTo="self"
                  :disabled="inmuebles[inmuebleActivo].bloqueado"
                />
              </div>
              <div class="w-1/2">
                <label class="font-bold mb-1">Provincia <span class="text-red-500">(*)</span></label>
                <Select
                  v-model="inmuebles[inmuebleActivo].provincia"
                  :options="inmuebles[inmuebleActivo].provincias"
                  optionLabel="ubigeo_name"
                  dataKey="ubigeo_id"
                  placeholder="Seleccione provincia"
                  class="w-full"
                  :disabled="!inmuebles[inmuebleActivo].departamento || inmuebles[inmuebleActivo].bloqueado"
                  @change="onProvinciaChange(inmuebles[inmuebleActivo])"
                  appendTo="self"
                />
              </div>
            </div>

            <div>
              <label class="font-bold mb-1">Distrito <span class="text-red-500">(*)</span></label>
              <Select
                v-model="inmuebles[inmuebleActivo].distrito"
                :options="inmuebles[inmuebleActivo].distritos"
                optionLabel="ubigeo_name"
                dataKey="ubigeo_id"
                placeholder="Seleccione distrito"
                class="w-full"
                :disabled="!inmuebles[inmuebleActivo].provincia || inmuebles[inmuebleActivo].bloqueado"
                appendTo="self"
              />
            </div>
            
            <div>
              <label class="font-bold mb-1">Dirección <span class="text-red-500">(*)</span></label>
              <InputText
                v-model="inmuebles[inmuebleActivo].direccion"
                class="w-full"
                :disabled="inmuebles[inmuebleActivo].bloqueado"
              />
            </div>

            <div>
              <label class="font-bold mb-1">Valor del Inmueble <span class="text-red-500">(*)</span></label>
              <InputNumber
                v-model="inmuebles[inmuebleActivo].valor_individual"
                class="w-full"
                :useGrouping="true"
                :locale="'es-PE'"
                :disabled="inmuebles[inmuebleActivo].bloqueado"
              />
            </div>

            <div>
              <label class="font-bold mb-1">Descripción <span class="text-red-500">(*)</span></label>
              <Textarea
                v-model="inmuebles[inmuebleActivo].descripcion"
                rows="3"
                class="w-full"
                autoResize
                :disabled="inmuebles[inmuebleActivo].bloqueado"
              />
            </div>

            <!-- SECCIÓN DE IMÁGENES -->
            <div class="flex flex-col gap-3">
              <div>
                <label class="block font-bold mb-2">Imágenes <span class="text-red-500">(*)</span></label>
                <p class="text-sm text-gray-600 mb-2">Se requieren al menos 3 imágenes con sus respectivas descripciones</p>

                <FileUpload
                  v-if="!inmuebles[inmuebleActivo].bloqueado"
                  mode="basic"
                  :name="`imagenes-${inmuebleActivo}`"
                  accept="image/*"
                  :maxFileSize="2048000"
                  :multiple="true"
                  :auto="false"
                  chooseLabel="Seleccionar imágenes"
                  class="w-full"
                  @select="onFileSelect($event, inmuebles[inmuebleActivo])"
                />
              </div>

              <!-- Lista de imágenes seleccionadas -->
              <div v-if="inmuebles[inmuebleActivo].imagenesConDescripcion?.length > 0" class="flex flex-col gap-3">
                <div class="flex justify-between items-center">
                  <h4 class="font-bold text-sm">
                    Imágenes seleccionadas ({{ inmuebles[inmuebleActivo].imagenesConDescripcion.length }})
                  </h4>
                  <Button
                    v-if="!inmuebles[inmuebleActivo].bloqueado"
                    label="Limpiar todas"
                    icon="pi pi-trash"
                    severity="danger"
                    size="small"
                    outlined
                    @click="clearImagesInmueble(inmuebles[inmuebleActivo])"
                  />
                </div>

                <div
                  v-for="(imagen, imgIndex) in inmuebles[inmuebleActivo].imagenesConDescripcion"
                  :key="imgIndex"
                  class="flex gap-3 p-3 border-1 border-300 border-round items-start"
                >
                  <div class="flex-shrink-0">
                    <img
                      :src="imagen.preview"
                      :alt="`Imagen ${imgIndex + 1}`"
                      class="w-20 h-20 object-cover border-round"
                    />
                  </div>

                  <div class="flex-grow flex flex-col gap-2">
                    <div class="text-sm">
                      <strong>{{ imagen.file.name }}</strong>
                      <span class="text-gray-500 ml-2">({{ formatFileSize(imagen.file.size) }})</span>
                    </div>

                    <div>
                      <label class="text-xs font-semibold mb-1 block">
                        Descripción (máx. 255 caracteres) <span class="text-red-500">*</span>
                      </label>
                      <InputText
                        v-model="imagen.description"
                        placeholder="Ej: Fachada principal, Sala de estar, Cocina moderna, etc."
                        class="w-full text-sm"
                        :class="{ 'p-invalid': submitted && !imagen.description?.trim() }"
                        maxlength="255"
                        :disabled="inmuebles[inmuebleActivo].bloqueado"
                      />
                      <div class="flex justify-between items-center mt-1">
                        <small v-if="submitted && !imagen.description?.trim()" class="text-red-500">
                          La descripción es requerida
                        </small>
                        <small
                          class="text-xs"
                          :class="{
                            'text-orange-500': imagen.description?.length >= 200,
                            'text-red-500': imagen.description?.length >= 250,
                            'text-gray-500': !imagen.description?.length || imagen.description.length < 200
                          }"
                        >
                          {{ imagen.description?.length || 0 }}/255
                        </small>
                      </div>
                    </div>
                  </div>

                  <Button
                    v-if="!inmuebles[inmuebleActivo].bloqueado"
                    icon="pi pi-times"
                    severity="danger"
                    size="small"
                    outlined
                    @click="removeImage(inmuebles[inmuebleActivo], imgIndex)"
                  />
                </div>

                <div class="text-sm">
                  <span v-if="inmuebles[inmuebleActivo].imagenesConDescripcion.length < 3" class="text-orange-600">
                    <i class="pi pi-exclamation-triangle mr-1"></i>
                    Necesitas al menos {{ 3 - inmuebles[inmuebleActivo].imagenesConDescripcion.length }} imagen(es) más
                  </span>
                  <span v-else class="text-green-600">
                    <i class="pi pi-check-circle mr-1"></i>
                    Imágenes suficientes ({{ inmuebles[inmuebleActivo].imagenesConDescripcion.length }}/3 mínimo)
                  </span>
                </div>
              </div>

              <div v-else class="text-sm text-gray-500 p-3 border-1 border-300 border-round text-center">
                <i class="pi pi-images mr-2"></i>
                No hay imágenes seleccionadas
              </div>
            </div>
          </div>

          <!-- Resumen de inmuebles -->
          <div v-if="inmuebles.length > 0" class="p-3 bg-blue-50 border-1 border-blue-200 rounded-lg border-round">
            <h4 class="font-bold mb-2">Resumen de Inmuebles</h4>
            <div class="flex flex-col gap-2">
              <div v-for="(inm, idx) in inmuebles" :key="idx" class="flex justify-between items-center text-sm">
                <span>
                  <i class="pi pi-home mr-2"></i>
                  {{ inm.nombre || `Inmueble ${idx + 1}` }}
                </span>
                <span class="font-bold">
                  {{ inm.valor_individual ? `S/ ${inm.valor_individual.toLocaleString('es-PE')}` : 'S/ 0' }}
                </span>
              </div>
              <hr class="my-2" />
              <div class="flex justify-between items-center font-bold text-base">
                <span>Total:</span>
                <span class="text-green-700">S/ {{ valorTotalEstimado.toLocaleString('es-PE') }}</span>
              </div>
            </div>
          </div>

          <div class="flex gap-3">
            <div class="w-1/2">
              <label class="font-bold mb-1">Moneda <span class="text-red-500">(*)</span></label>
              <Select
                v-model="form.currency_id"
                :options="monedas"
                optionLabel="label"
                optionValue="value"
                placeholder="Selecciona moneda"
                class="w-full"
              />
            </div>
            <div class="w-1/2">
              <label class="font-bold mb-1">Valor Total Estimado <span class="text-red-500">(*)</span></label>
              <InputNumber
                v-model="form.valor_estimado"
                class="w-full"
                :useGrouping="true"
                :locale="'es-PE'"
                disabled
              />
            </div>
          </div>

          <div>
            <label class="font-bold mb-1">Monto Requerido <span class="text-red-500">(*)</span></label>
            <InputNumber v-model="form.valor_requerido" class="w-full" :useGrouping="true" :locale="'es-PE'" />
          </div>
          
          <Button label="Agregar otro inmueble" icon="pi pi-plus" severity="contrast" rounded @click="addInmueble" />
          <Button
            label="Volver a datos del cliente"
            icon="pi pi-arrow-left"
            severity="secondary"
            rounded
            @click="volverADatosCliente"
            class="mt-2"
          />
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
import { ref, onMounted, computed, watch } from 'vue'
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
const mostrarInmueble = ref(false)
const guardandoPropiedad = ref(false)
const tiposInmueble = ref<any[]>([])
const investorExists = ref(false)
const investorId = ref<number | null>(null)
const dniConsultaExterna = ref<any>(null)

const form = ref({
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
})

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
  imagenesConDescripcion: [] as ImagenConDescripcion[],
  valor_individual: null,
  bloqueado: false,
}

const inmuebles = ref<any[]>([{ ...initialInmueble, imagenesConDescripcion: [] }])
const inmuebleActivo = ref(0)

const valorTotalEstimado = computed(() => {
  return inmuebles.value.reduce((total, inm) => {
    const valor = parseFloat(inm.valor_individual) || 0
    return total + valor
  }, 0)
})

watch(valorTotalEstimado, (newValue) => {
  form.value.valor_estimado = newValue
})

const validarInmuebleCompleto = (inmueble: any): boolean => {
  return !!(
    inmueble.nombre?.trim() &&
    inmueble.direccion?.trim() &&
    inmueble.id_tipo_inmueble &&
    inmueble.pertenece?.trim() &&
    inmueble.departamento?.ubigeo_id &&
    inmueble.provincia?.ubigeo_id &&
    inmueble.distrito?.ubigeo_id &&
    inmueble.descripcion?.trim() &&
    inmueble.valor_individual &&
    inmueble.imagenesConDescripcion?.length >= 3 &&
    inmueble.imagenesConDescripcion.every((img: ImagenConDescripcion) => img.description?.trim())
  )
}

const addInmueble = () => {
  const inmuebleActual = inmuebles.value[inmuebleActivo.value]
  
  if (!validarInmuebleCompleto(inmuebleActual)) {
    toast.add({
      severity: 'warn',
      summary: 'Validación',
      detail: 'Completa todos los datos del inmueble actual antes de agregar otro',
      life: 3000
    })
    return
  }

  inmuebleActual.bloqueado = true
  
  inmuebles.value.push({ ...initialInmueble, imagenesConDescripcion: [] })
  inmuebleActivo.value = inmuebles.value.length - 1
  
  toast.add({
    severity: 'success',
    summary: 'Inmueble agregado',
    detail: `Ahora ingresa los datos del inmueble #${inmuebles.value.length}`,
    life: 2000
  })
}

const removeInmueble = (index: number) => {
  if (inmuebles.value.length === 1) {
    toast.add({
      severity: 'warn',
      summary: 'Atención',
      detail: 'Debe haber al menos un inmueble',
      life: 2000
    })
    return
  }
  
  inmuebles.value.splice(index, 1)
  
  if (inmuebleActivo.value >= inmuebles.value.length) {
    inmuebleActivo.value = inmuebles.value.length - 1
  }
  
  toast.add({
    severity: 'info',
    summary: 'Inmueble eliminado',
    detail: 'El inmueble ha sido eliminado',
    life: 2000
  })
}

const editarInmueble = (index: number) => {
  inmuebleActivo.value = index
  toast.add({
    severity: 'info',
    summary: 'Editando inmueble',
    detail: `Editando inmueble #${index + 1}`,
    life: 2000
  })
}

const monedas = [
  { label: 'PEN (S/)', value: 1 },
  { label: 'USD ($)', value: 2 }
]

const tiposPertenencia = [
  { label: 'Personal', value: 'Personal' },
  { label: 'Familiar', value: 'Familiar' },
  { label: 'Conyugal', value: 'Conyugal' },
  { label: 'Herencia', value: 'Herencia' },
  { label: 'Sociedad', value: 'Sociedad' },
  { label: 'Empresa', value: 'Empresa' }
]

const generarNumeroSolicitud = (nombreCompleto: string): string => {
  const partes = nombreCompleto.trim().split(' ')
  const primerNombre = partes[0] || ''
  const cliente = primerNombre.substring(0, 3).toUpperCase()
  const letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
  let letrasAleatorias = ''
  for (let i = 0; i < 2; i++) {
    const index = Math.floor(Math.random() * letras.length)
    letrasAleatorias += letras[index]
  }
  const numero = Math.floor(Math.random() * 9) + 1
  const año = new Date().getFullYear()
  return `${cliente}${letrasAleatorias}${numero}-${año}`
}

const departamentos = ref<any[]>([])

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
  inmuebles.value = [{ ...initialInmueble, imagenesConDescripcion: [] }]
  inmuebleActivo.value = 0
  mostrarInmueble.value = false
  investorExists.value = false
  investorId.value = null
  dniConsultaExterna.value = null
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

const onFileSelect = (event: any, inmueble: any) => {
  const files = event.files || []

  files.forEach((file: File) => {
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

  toast.add({
    severity: 'success',
    summary: 'Archivos seleccionados',
    detail: `${files.length} imagen(es) agregada(s)`,
    life: 2000
  })
}

const clearImagesInmueble = (inmueble: any) => {
  inmueble.imagenesConDescripcion = []
  toast.add({
    severity: 'info',
    summary: 'Imágenes eliminadas',
    detail: 'Todas las imágenes han sido eliminadas',
    life: 2000
  })
}

const removeImage = (inmueble: any, imageIndex: number) => {
  inmueble.imagenesConDescripcion.splice(imageIndex, 1)
  toast.add({
    severity: 'info',
    summary: 'Imagen eliminada',
    detail: 'La imagen ha sido eliminada',
    life: 2000
  })
}

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const handleDniInput = async () => {
  const dni = form.value.dni
  investorExists.value = false
  investorId.value = null
  dniConsultaExterna.value = null

  if (dni.length === 8) {
    try {
      const { data } = await axios.get(`/dni/${dni}`)

      if (data.success && data.data) {
        if (data.message === 'Datos obtenidos localmente.') {
          investorExists.value = true
          investorId.value = data.data.id || null
          form.value.nombres = data.data.nombres || ''
          form.value.apellidos = `${data.data.apellido_paterno || ''} ${data.data.apellido_materno || ''}`.trim()

          toast.add({
            severity: 'info',
            summary: 'Cliente encontrado',
            detail: 'El cliente ya está registrado en el sistema',
            life: 3000
          })
        } else {
          investorExists.value = false
          dniConsultaExterna.value = data.data
          form.value.nombres = data.data.nombres || ''
          form.value.apellidos = `${data.data.apellido_paterno || ''} ${data.data.apellido_materno || ''}`.trim()

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
    form.value.nombres = ''
    form.value.apellidos = ''
  }
}

const puedeContinuarCliente = computed(() => {
  // Si el inversor existe, solo necesita DNI, nombres y apellidos
  if (investorExists.value) {
    return (
      (form.value.dni?.length === 8) &&
      (form.value.nombres?.trim() !== '') &&
      (form.value.apellidos?.trim() !== '')
    )
  }
  
  // Si es nuevo, necesita todos los campos
  return (
    (form.value.dni?.length === 8) &&
    (form.value.nombres?.trim() !== '') &&
    (form.value.apellidos?.trim() !== '') &&
    (form.value.fuente_ingreso?.trim() !== '') &&
    (form.value.profesion_ocupacion?.trim() !== '') &&
    (form.value.ingreso_promedio !== null && form.value.ingreso_promedio !== '')
  )
})

const continuarConInversorExistente = async () => {
  try {
    // No enviamos detalles porque el inversor ya existe y ya tiene sus detalles registrados
    form.value.numero_solicitud = generarNumeroSolicitud(form.value.nombres)
    mostrarInmueble.value = true
    toast.add({
      severity: 'success',
      summary: 'Cliente encontrado',
      detail: 'Cliente existente. Ahora ingresa los datos de la propiedad',
      life: 3000
    })
  } catch (error: any) {
    const errorMessage = error.response?.data?.message || 'Error al continuar'
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

    const investorData = {
      name: form.value.nombres,
      first_last_name: paterno,
      second_last_name: materno,
      document: form.value.dni,
    }

    let nuevoInvestorId = null

    try {
      const response = await axios.post('/customer', investorData)
      if (response.data.success) {
        nuevoInvestorId = response.data.data.id
      }
    } catch (error: any) {
      if (error.response?.status === 422 &&
        error.response?.data?.errors?.document?.includes('has already been taken')) {
        const res = await axios.get(`/investors/by-document/${form.value.dni}`)
        nuevoInvestorId = res.data.id
      } else {
        throw error
      }
    }

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
  
  // Validación de cliente
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

  // Validación de inversor
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

  // Validación de inmuebles
  for (const [idx, inm] of inmuebles.value.entries()) {
    if (
      !inm.nombre?.trim() ||
      !inm.direccion?.trim() ||
      !inm.id_tipo_inmueble ||
      !inm.pertenece ||
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

    const imgs = inm.imagenesConDescripcion || []

    // Al menos 3 imágenes
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

    // Todas deben tener descripción
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

    // Datos de la solicitud
    formData.append('investor_id', investorId.value.toString())
    formData.append('codigo', form.value.numero_solicitud)
    formData.append('valor_general', form.value.valor_estimado?.toString() || '0')
    formData.append('valor_requerido', form.value.valor_requerido?.toString() || '0')
    formData.append('currency_id', form.value.currency_id?.toString() || '1')
    
    // Agregar los campos faltantes
    formData.append('fuente_ingreso', form.value.fuente_ingreso || '')
    formData.append('profesion_ocupacion', form.value.profesion_ocupacion || '')
    formData.append('ingreso_promedio', form.value.ingreso_promedio?.toString() || '0')

    // Datos de los inmuebles
    inmuebles.value.forEach((inmueble, idx) => {
      formData.append(`properties[${idx}][nombre]`, inmueble.nombre)
      formData.append(`properties[${idx}][direccion]`, inmueble.direccion)
      formData.append(`properties[${idx}][id_tipo_inmueble]`, inmueble.id_tipo_inmueble)
      formData.append(`properties[${idx}][valor_estimado]`, inmueble.valor_individual?.toString() || '0')
      formData.append(`properties[${idx}][valor_requerido]`, form.value.valor_requerido?.toString() || '0')
      formData.append(`properties[${idx}][currency_id]`, form.value.currency_id?.toString() || '1')
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

      // Imágenes y descripciones
      inmueble.imagenesConDescripcion?.forEach((imagen: ImagenConDescripcion, imgIdx: number) => {
        formData.append(`properties[${idx}][imagenes][${imgIdx}]`, imagen.file)
        formData.append(`properties[${idx}][description][${imgIdx}]`, imagen.description)
      })
    })

    // Para debug: mostrar lo que se está enviando
    console.log('Datos enviados:')
    for (let [key, value] of formData.entries()) {
      console.log(key, value instanceof File ? `File: ${value.name}` : value)
    }

    // Enviar al backend
    await axios.post('/property', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Solicitud registrada correctamente',
      life: 3000
    })

    resetForm()
    modalVisible.value = false
    emit('agregado')

  } catch (error: any) {
    console.error('Error al guardar solicitud:', error)
    
    const errorMessage = error.response?.data?.message || 'Error al registrar la solicitud'
    const errors = error.response?.data?.errors
    
    if (errors) {
      Object.keys(errors).forEach(key => {
        toast.add({
          severity: 'error',
          summary: 'Error de validación',
          detail: errors[key][0],
          life: 4000
        })
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
    guardandoPropiedad.value = false
  }
}
</script>