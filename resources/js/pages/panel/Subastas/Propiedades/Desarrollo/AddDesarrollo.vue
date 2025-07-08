<template>
  <Toast />

  <Toolbar class="mb-6">
    <template #start>
      <Button label="Nuevo" icon="pi pi-plus" severity="secondary" class="mr-2" @click="modalVisible = true" />
    </template>
  </Toolbar>

  <!-- Modal de nueva propiedad -->
  <Dialog v-model:visible="modalVisible" header="Nueva Propiedad" :modal="true" :style="{ width: '450px' }">
    <form @submit.prevent="saveProperty" class="p-fluid">
      <div class="flex flex-col gap-4">
        <div>
          <label class="font-bold mb-1">Nombre <span class="text-red-500">*</span></label>
          <InputText v-model="form.nombre" class="w-full" />
        </div>

        <div class="flex gap-4">
          <div class="w-1/2">
            <label class="font-bold mb-1">Departamento <span class="text-red-500">*</span></label>
            <Select
              v-model="form.departamento"
              :options="departamentos"
              optionLabel="ubigeo_name"
              dataKey="ubigeo_id"
              placeholder="Seleccione departamento"
              class="w-full"
              @change="onDepartamentoChange"
            />
          </div>
          <div class="w-1/2">
            <label class="font-bold mb-1">Provincia <span class="text-red-500">*</span></label>
            <Select
              v-model="form.provincia"
              :options="provincias"
              optionLabel="ubigeo_name"
              dataKey="ubigeo_id"
              placeholder="Seleccione provincia"
              class="w-full"
              :disabled="!form.departamento"
              @change="onProvinciaChange"
            />
          </div>
        </div>

        <div>
          <label class="font-bold mb-1">Distrito <span class="text-red-500">*</span></label>
          <Select
            v-model="form.distrito"
            :options="distritos"
            optionLabel="ubigeo_name"
            dataKey="ubigeo_id"
            placeholder="Seleccione distrito"
            class="w-full"
            :disabled="!form.provincia"
          />
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
            <label class="font-bold mb-1">Valor estimado <span class="text-red-500">*</span></label>
            <InputNumber v-model="form.valor_estimado" class="w-full" :useGrouping="false" />
          </div>
        </div>

        <div class="flex gap-4">
          <div class="w-1/2">
            <label class="font-bold mb-1">TEA (%) <span class="text-red-500">*</span></label>
            <InputNumber
              v-model="form.tea"
              class="w-full"
              :minFractionDigits="2"
              :maxFractionDigits="4"
              :step="0.0001"
              :useGrouping="false"
            />
          </div>
          <div class="w-1/2">
            <label class="font-bold mb-1">TEM (%) <span class="text-red-500">*</span></label>
            <InputNumber
              v-model="form.tem"
              class="w-full"
              :minFractionDigits="2"
              :maxFractionDigits="4"
              :step="0.0001"
              :useGrouping="false"
            />
          </div>
        </div>

        <div>
          <label class="font-bold mb-1">Estado</label>
          <Select
            v-model="form.estado"
            :options="estados"
            optionLabel="label"
            optionValue="value"
            placeholder="Seleccionar estado"
            class="w-full"
          />
        </div>

        <div>
          <label class="block font-bold mb-1">Imágenes</label>
          <FileUpload
            name="imagenes[]"
            :multiple="true"
            accept="image/*"
            :maxFileSize="1000000"
            customUpload
            :auto="false"
            @select="onSelectedFiles"
            @upload="onTemplatedUpload"
          />
        </div>
      </div>
    </form>

    <template #footer>
      <Button label="Cancelar" icon="pi pi-times" text @click="modalVisible = false" />
      <Button label="Guardar" icon="pi pi-check" @click="saveProperty" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
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

const toast = useToast()
const emit = defineEmits(['agregado'])
const modalVisible = ref(false)
const submitted = ref(false)

const form = ref({
  nombre: '',
  departamento: null,
  provincia: null,
  distrito: null,
  direccion: '',
  descripcion: '',
  valor_estimado: null,
  currency_id: null,
  tea: null,
  tem: null,
  estado: ''
})

const estados = [
  { label: 'Activa', value: 'activa' },
  { label: 'Desactivada', value: 'desactivada' }
]

const monedas = [
  { label: 'PEN (S/)', value: 1 },
  { label: 'USD ($)', value: 2 }
]

const archivos = ref<File[]>([])
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

const onSelectedFiles = (event: any) => {
  archivos.value = [...event.files]
  totalSize.value = archivos.value.reduce((acc, file) => acc + file.size, 0)
  totalSizePercent.value = totalSize.value / 10000
}

const onTemplatedUpload = () => {
  toast.add({ severity: 'info', summary: 'Éxito', detail: 'Archivo subido', life: 3000 })
}

const saveProperty = () => {
  submitted.value = true

  if (
    !form.value.nombre || !form.value.departamento || !form.value.provincia || !form.value.distrito ||
    !form.value.direccion || !form.value.valor_estimado || !form.value.currency_id ||
    form.value.tea === null || form.value.tem === null
  ) {
    toast.add({
      severity: 'warn',
      summary: 'Validación',
      detail: 'Por favor completa todos los campos obligatorios',
      life: 3000
    })
    return
  }

  const formData = new FormData()
  for (const key in form.value) {
    const val = form.value[key as keyof typeof form.value]
    formData.append(key, typeof val === 'object' && val?.ubigeo_name ? val.ubigeo_name : val)
  }

  archivos.value.forEach((file) => {
    formData.append('imagenes[]', file)
  })

  axios.post('/property', formData, {
    headers: { 'Content-Type': 'multipart/form-data' }
  }).then(() => {
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Propiedad registrada correctamente', life: 3000 })
    modalVisible.value = false
    emit('agregado')
  }).catch((error) => {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al guardar',
      life: 3000
    })
  })
}
</script>
