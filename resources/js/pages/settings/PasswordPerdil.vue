<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 p-4">
    <div class="max-w-4xl mx-auto">
      <!-- Header Card -->
      <Card class="mb-6 shadow-lg">
        <template #header>
          <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-6 rounded-t-lg">
            <div class="flex items-center space-x-4">
              <Avatar 
                :label="perfil.name ? perfil.name.charAt(0) + (perfil.first_last_name ? perfil.first_last_name.charAt(0) : '') : 'U'" 
                size="xlarge" 
                shape="circle" 
                class="bg-white text-blue-600 font-bold"
              />
              <div>
                <h1 class="text-2xl font-bold">
                  {{ perfil.name }} {{ perfil.first_last_name }} {{ perfil.second_last_name }}
                </h1>
                <p class="text-blue-100 flex items-center">
                  <i class="pi pi-envelope mr-2"></i>
                  {{ perfil.email }}
                </p>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Main Form Card -->
      <Card class="shadow-lg">
        <template #header>
          <div class="p-4 border-b">
            <h2 class="text-xl font-semibold text-gray-800 flex items-center">
              <i class="pi pi-user-edit mr-2 text-blue-600"></i>
              Información del Perfil
            </h2>
            <p class="text-gray-600 mt-1">Complete los siguientes datos para verificar su identidad</p>
          </div>
        </template>

        <template #content>
          <div class="space-y-8">
            <!-- Sección PEP -->
            <div class="bg-gradient-to-r from-amber-50 to-orange-50 p-6 rounded-lg border-l-4 border-amber-400">
              <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="pi pi-shield mr-2 text-amber-600"></i>
                Persona Expuesta Políticamente (PEP)
              </h3>
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-2">
                  <label class="block font-medium text-gray-700">
                    ¿Eres o has sido Persona Expuesta Políticamente (PEP)?
                  </label>
                  <div class="flex items-center space-x-3">
                    <ToggleSwitch v-model="form.is_pep" />
                    <span class="text-sm" :class="form.is_pep ? 'text-green-600 font-medium' : 'text-gray-500'">
                      {{ form.is_pep ? 'Sí' : 'No' }}
                    </span>
                  </div>
                  <small class="text-gray-500">Incluye funcionarios públicos, políticos, etc.</small>
                </div>
                
                <div class="space-y-2">
                  <label class="block font-medium text-gray-700">
                    ¿Eres pariente, cónyuge o conviviente de alguna persona PEP?
                  </label>
                  <div class="flex items-center space-x-3">
                    <ToggleSwitch v-model="form.has_relationship_pep" />
                    <span class="text-sm" :class="form.has_relationship_pep ? 'text-green-600 font-medium' : 'text-gray-500'">
                      {{ form.has_relationship_pep ? 'Sí' : 'No' }}
                    </span>
                  </div>
                  <small class="text-gray-500">Relación familiar o sentimental con PEP</small>
                </div>
              </div>
            </div>

            <!-- Sección Ubicación -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-6 rounded-lg border-l-4 border-green-400">
              <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="pi pi-map-marker mr-2 text-green-600"></i>
                Ubicación Geográfica
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-2">
                  <label class="block font-medium text-gray-700">Departamento *</label>
                  <Select 
                    v-model="form.departamento" 
                    :options="departamentos" 
                    optionLabel="ubigeo_name" 
                    dataKey="ubigeo_id"
                    placeholder="Seleccione departamento" 
                    class="w-full"
                    :class="{ 'p-invalid': errors.departamento }"
                    @change="onDepartamentoChange"
                    :loading="loadingDepartamentos"
                  >
                    <template #option="slotProps">
                      <div class="flex items-center">
                        <i class="pi pi-map mr-2"></i>
                        {{ slotProps.option.ubigeo_name }}
                      </div>
                    </template>
                  </Select>
                  <small v-if="errors.departamento" class="text-red-500">{{ errors.departamento }}</small>
                </div>
                
                <div class="space-y-2">
                  <label class="block font-medium text-gray-700">Provincia *</label>
                  <Select 
                    v-model="form.provincia" 
                    :options="provincias" 
                    optionLabel="ubigeo_name" 
                    dataKey="ubigeo_id"
                    placeholder="Seleccione provincia" 
                    class="w-full"
                    :class="{ 'p-invalid': errors.provincia }"
                    :disabled="!form.departamento || loadingProvincias"
                    @change="onProvinciaChange"
                    :loading="loadingProvincias"
                  >
                    <template #option="slotProps">
                      <div class="flex items-center">
                        <i class="pi pi-building mr-2"></i>
                        {{ slotProps.option.ubigeo_name }}
                      </div>
                    </template>
                  </Select>
                  <small v-if="errors.provincia" class="text-red-500">{{ errors.provincia }}</small>
                </div>
                
                <div class="space-y-2">
                  <label class="block font-medium text-gray-700">Distrito *</label>
                  <Select 
                    v-model="form.distrito" 
                    :options="distritos" 
                    optionLabel="ubigeo_name" 
                    dataKey="ubigeo_id"
                    placeholder="Seleccione distrito" 
                    class="w-full"
                    :class="{ 'p-invalid': errors.distrito }"
                    :disabled="!form.provincia || loadingDistritos"
                    :loading="loadingDistritos"
                  >
                    <template #option="slotProps">
                      <div class="flex items-center">
                        <i class="pi pi-home mr-2"></i>
                        {{ slotProps.option.ubigeo_name }}
                      </div>
                    </template>
                  </Select>
                  <small v-if="errors.distrito" class="text-red-500">{{ errors.distrito }}</small>
                </div>
              </div>
            </div>

            <!-- Dirección -->
            <div class="space-y-2">
              <label class="block font-medium text-gray-700">
                <i class="pi pi-home mr-2 text-blue-600"></i>
                Dirección Completa *
              </label>
              <InputText 
                v-model="form.address" 
                class="w-full"
                :class="{ 'p-invalid': errors.address }"
                placeholder="Ej: Av. Javier Prado 123, Miraflores"
              />
              <small v-if="errors.address" class="text-red-500">{{ errors.address }}</small>
              <small class="text-gray-500">Incluya calle, número, urbanización, etc.</small>
            </div>

            <!-- Documentos de Identidad -->
            <div class="bg-gradient-to-r from-blue-50 to-cyan-50 p-6 rounded-lg border-l-4 border-blue-400">
              <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="pi pi-id-card mr-2 text-blue-600"></i>
                Documentos de Identidad
              </h3>
              <p class="text-gray-600 mb-6">Suba imágenes claras de ambos lados de su DNI para verificar su identidad</p>
              
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- DNI Frontal -->
                <div class="space-y-3">
                  <label class="block font-medium text-gray-700">DNI - Parte Delantera *</label>
                  <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-blue-400 transition-colors">
                    <FileUpload 
                      mode="basic" 
                      name="dni_front"
                      customUpload 
                      auto 
                      @uploader="onUploadFront" 
                      chooseLabel="Seleccionar imagen" 
                      accept="image/*"
                      :maxFileSize="5000000"
                      class="w-full"
                    >
                      <template #empty>
                        <div class="text-center py-4">
                          <i class="pi pi-image text-4xl text-gray-400 mb-2"></i>
                          <p class="text-gray-500">Haga clic para subir la parte frontal del DNI</p>
                          <small class="text-gray-400">Máximo 5MB - JPG, PNG</small>
                        </div>
                      </template>
                    </FileUpload>
                  </div>
                  
                  <!-- Vista previa frontal -->
                  <div v-if="frontImagePreview" class="mt-3">
                    <div class="relative inline-block">
                      <img :src="frontImagePreview" alt="DNI Frontal" class="max-w-full h-32 object-cover rounded-lg shadow-md" />
                      <Button 
                        icon="pi pi-times" 
                        class="p-button-danger p-button-rounded p-button-sm absolute -top-2 -right-2"
                        @click="removeFrontImage"
                      />
                    </div>
                    <p class="text-sm text-green-600 mt-1">
                      <i class="pi pi-check-circle mr-1"></i>
                      Imagen cargada correctamente
                    </p>
                  </div>
                  <small v-if="errors.dni_front" class="text-red-500">{{ errors.dni_front }}</small>
                </div>

                <!-- DNI Trasero -->
                <div class="space-y-3">
                  <label class="block font-medium text-gray-700">DNI - Parte Trasera *</label>
                  <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-blue-400 transition-colors">
                    <FileUpload 
                      mode="basic" 
                      name="dni_back"
                      customUpload 
                      auto 
                      @uploader="onUploadBack" 
                      chooseLabel="Seleccionar imagen" 
                      accept="image/*"
                      :maxFileSize="5000000"
                      class="w-full"
                    >
                      <template #empty>
                        <div class="text-center py-4">
                          <i class="pi pi-image text-4xl text-gray-400 mb-2"></i>
                          <p class="text-gray-500">Haga clic para subir la parte trasera del DNI</p>
                          <small class="text-gray-400">Máximo 5MB - JPG, PNG</small>
                        </div>
                      </template>
                    </FileUpload>
                  </div>
                  
                  <!-- Vista previa trasera -->
                  <div v-if="backImagePreview" class="mt-3">
                    <div class="relative inline-block">
                      <img :src="backImagePreview" alt="DNI Trasero" class="max-w-full h-32 object-cover rounded-lg shadow-md" />
                      <Button 
                        icon="pi pi-times" 
                        class="p-button-danger p-button-rounded p-button-sm absolute -top-2 -right-2"
                        @click="removeBackImage"
                      />
                    </div>
                    <p class="text-sm text-green-600 mt-1">
                      <i class="pi pi-check-circle mr-1"></i>
                      Imagen cargada correctamente
                    </p>
                  </div>
                  <small v-if="errors.dni_back" class="text-red-500">{{ errors.dni_back }}</small>
                </div>
              </div>

              <!-- Consejos para las fotos -->
              <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                <h4 class="font-medium text-blue-800 mb-2">
                  <i class="pi pi-info-circle mr-2"></i>
                  Consejos para una buena foto:
                </h4>
                <ul class="text-sm text-blue-700 space-y-1">
                  <li>• Asegúrese de que el DNI esté completamente visible</li>
                  <li>• Use buena iluminación, evite sombras</li>
                  <li>• La imagen debe estar enfocada y ser legible</li>
                  <li>• No use flash si causa reflejos</li>
                </ul>
              </div>
            </div>

            <!-- Aceptación de Contrato -->
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 p-6 rounded-lg border-l-4 border-purple-400">
              <div class="flex items-start space-x-3">
                <Checkbox 
                  v-model="form.contractAccepted" 
                  :binary="true" 
                  inputId="contract"
                  :class="{ 'p-invalid': errors.contractAccepted }"
                />
                <div class="flex-1">
                  <label for="contract" class="font-medium text-gray-700 cursor-pointer">
                    He leído y acepto el 
                    <Button 
                      label="Contrato de Inversionista" 
                      link 
                      class="p-0 text-blue-600 underline"
                      @click="showContract = true"
                    />
                  </label>
                  <p class="text-sm text-gray-500 mt-1">
                    Es obligatorio aceptar los términos y condiciones para continuar
                  </p>
                  <small v-if="errors.contractAccepted" class="text-red-500">{{ errors.contractAccepted }}</small>
                </div>
              </div>
            </div>

            <!-- Botones de Acción -->
            <div class="flex justify-end space-x-4 pt-6 border-t">
              <Button 
                label="Cancelar" 
                icon="pi pi-times" 
                class="p-button-secondary"
                @click="resetForm"
              />
              <Button 
                label="Guardar Perfil" 
                icon="pi pi-save" 
                class="p-button-success"
                @click="guardarPerfil"
                :loading="saving"
                :disabled="!isFormValid"
              />
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Dialog del Contrato -->
    <Dialog 
      v-model:visible="showContract" 
      header="Contrato de Inversionista" 
      :style="{ width: '80vw' }" 
      modal
    >
      <div class="max-h-96 overflow-y-auto">
        <p class="mb-4">
          Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor 
          incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis 
          nostrud exercitation ullamco laboris...
        </p>
        <!-- Aquí iría el contenido real del contrato -->
      </div>
      <template #footer>
        <Button label="Cerrar" @click="showContract = false" />
      </template>
    </Dialog>

    <!-- Toast para notificaciones -->
    <Toast />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Card from 'primevue/card'
import Avatar from 'primevue/avatar'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import ToggleSwitch from 'primevue/inputswitch'
import FileUpload from 'primevue/fileupload'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
// import profileService from '@/services/profileService'
import axios from 'axios'

const toast = useToast()

const perfil = ref({
  name: 'Juan Carlos',
  first_last_name: 'García',
  second_last_name: 'López',
  email: 'juan.garcia@email.com'
})

const form = ref({
  is_pep: false,
  has_relationship_pep: false,
  departamento: null,
  provincia: null,
  distrito: null,
  address: '',
  contractAccepted: false,
  dni_front: null,
  dni_back: null
})

const errors = ref({})
const departamentos = ref([])
const provincias = ref([])
const distritos = ref([])
const loadingDepartamentos = ref(false)
const loadingProvincias = ref(false)
const loadingDistritos = ref(false)
const saving = ref(false)
const showContract = ref(false)
const frontImagePreview = ref(null)
const backImagePreview = ref(null)

// Computed para validar el formulario
const isFormValid = computed(() => {
  return form.value.departamento &&
         form.value.provincia &&
         form.value.distrito &&
         form.value.address.trim() &&
         form.value.dni_front &&
         form.value.dni_back &&
         form.value.contractAccepted
})

onMounted(async () => {
  try {
    loadingDepartamentos.value = true
    // const { data } = await profileService.getProfile()
    // perfil.value = data.data

    const res = await axios.get('https://novalink.oswa.workers.dev/api/v1/peru/ubigeo')
    departamentos.value = res.data
  } catch (err) {
    console.error('Error al cargar datos:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudieron cargar los departamentos',
      life: 3000
    })
  } finally {
    loadingDepartamentos.value = false
  }
})

const onDepartamentoChange = () => {
  form.value.provincia = null
  form.value.distrito = null
  provincias.value = form.value.departamento?.provinces || []
  distritos.value = []
  errors.value.departamento = ''
}

const onProvinciaChange = () => {
  form.value.distrito = null
  distritos.value = form.value.provincia?.districts || []
  errors.value.provincia = ''
}

// Manejo de archivos con vista previa
const onUploadFront = (event) => {
  const file = event.files[0]
  form.value.dni_front = file
  
  // Crear vista previa
  const reader = new FileReader()
  reader.onload = (e) => {
    frontImagePreview.value = e.target.result
  }
  reader.readAsDataURL(file)
  errors.value.dni_front = ''
}

const onUploadBack = (event) => {
  const file = event.files[0]
  form.value.dni_back = file
  
  // Crear vista previa
  const reader = new FileReader()
  reader.onload = (e) => {
    backImagePreview.value = e.target.result
  }
  reader.readAsDataURL(file)
  errors.value.dni_back = ''
}

const removeFrontImage = () => {
  form.value.dni_front = null
  frontImagePreview.value = null
}

const removeBackImage = () => {
  form.value.dni_back = null
  backImagePreview.value = null
}

// Validación del formulario
const validateForm = () => {
  errors.value = {}
  
  if (!form.value.departamento) {
    errors.value.departamento = 'Debe seleccionar un departamento'
  }
  if (!form.value.provincia) {
    errors.value.provincia = 'Debe seleccionar una provincia'
  }
  if (!form.value.distrito) {
    errors.value.distrito = 'Debe seleccionar un distrito'
  }
  if (!form.value.address.trim()) {
    errors.value.address = 'La dirección es obligatoria'
  }
  if (!form.value.dni_front) {
    errors.value.dni_front = 'Debe subir la parte frontal del DNI'
  }
  if (!form.value.dni_back) {
    errors.value.dni_back = 'Debe subir la parte trasera del DNI'
  }
  if (!form.value.contractAccepted) {
    errors.value.contractAccepted = 'Debe aceptar el contrato'
  }
  
  return Object.keys(errors.value).length === 0
}

const resetForm = () => {
  form.value = {
    is_pep: false,
    has_relationship_pep: false,
    departamento: null,
    provincia: null,
    distrito: null,
    address: '',
    contractAccepted: false,
    dni_front: null,
    dni_back: null
  }
  errors.value = {}
  frontImagePreview.value = null
  backImagePreview.value = null
}

const guardarPerfil = async () => {
  if (!validateForm()) {
    toast.add({
      severity: 'warn',
      summary: 'Formulario incompleto',
      detail: 'Por favor complete todos los campos obligatorios',
      life: 3000
    })
    return
  }

  try {
    saving.value = true
    
    // Preparar FormData para envío
    const formData = new FormData()
    Object.keys(form.value).forEach(key => {
      if (form.value[key] !== null && form.value[key] !== '') {
        if (key === 'departamento' || key === 'provincia' || key === 'distrito') {
          formData.append(key, form.value[key].ubigeo_id)
        } else {
          formData.append(key, form.value[key])
        }
      }
    })

    console.log('Formulario a enviar:', form.value)
    
    // Simular envío
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    toast.add({
      severity: 'success',
      summary: 'Perfil guardado',
      detail: 'Su información ha sido actualizada correctamente',
      life: 3000
    })
    
    // Aquí enviarías con profileService.updateProfile(formData)
    
  } catch (error) {
    console.error('Error al guardar:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo guardar la información. Intente nuevamente.',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.p-invalid {
  border-color: #ef4444 !important;
}

.p-fileupload-basic {
  width: 100%;
}

.p-fileupload-basic .p-button {
  width: 100%;
}
</style>