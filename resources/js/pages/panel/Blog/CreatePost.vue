<template>
    <Head title="Blog" />
    <AppLayout>
    <div class="card">
      <div class="p-4 md:p-8 max-w-5xl mx-auto">
    <!-- Título -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Registro de Publicación</h1>
      <div class="flex gap-2 mt-4 md:mt-0">
        <Button label="Cancelar" icon="pi pi-times" severity="secondary" @click="cancelar" />
        <Button label="Guardar" icon="pi pi-check" severity="contrast" @click="guardarPost" />
      </div>
    </div>

    <!-- Formulario -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Título -->
      <div class="col-span-2">
        <label class="block font-semibold mb-2">Título <span class="text-red-500">*</span></label>
        <InputText v-model="post.titulo" placeholder="Ingresa el título" class="w-full" />
      </div>

      <!-- Producto -->
      <div>
        <label class="block font-semibold mb-2">Producto <span class="text-red-500">*</span></label>
        <Select v-model="selectedProduct" :options="products" optionLabel="nombre" optionValue="id" placeholder="Seleccione el producto" class="w-full" />
      </div>

      <!-- Categorías -->
      <div>
        <label class="block font-semibold mb-2">Categoría(s) <span class="text-red-500">*</span></label>
        <MultiSelect v-model="post.category_id" display="chip" :options="categories" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione la categoría" :maxSelectedLabels="3" class="w-full" />
      </div>

      <!-- Resumen
        <div class="col-span-2">
        <label class="block font-semibold mb-2">Resumen <span class="text-red-500">*</span></label>
        <InputText v-model="post.resumen" placeholder="Ingresa el resumen" class="w-full" />
      </div>
       
      -->
     

      <!-- Contenido -->
      <div class="col-span-2">
        <label class="block font-semibold mb-2">Contenido <span class="text-red-500">*</span></label>
        <QuillEditor v-model:content="post.contenido" contentType="html" placeholder="Ingresa el contenido" class="w-full min-h-[200px]" />
      </div>

      <!-- Fecha -->
      <div>
        <label class="block font-semibold mb-2">Fecha Programada <span class="text-red-500">*</span></label>
        <Calendar v-model="post.fecha_programada" dateFormat="dd/mm/yy" placeholder="Selecciona la fecha" showIcon showTime hourFormat="12" class="w-full" />
      </div>

      <!-- Imágenes -->
<div class="col-span-2">
  <label class="block font-semibold mb-2">Imágenes para mostrar <span class="text-red-500">*</span></label>
  <FileUpload
    mode="advanced"
    name="imgs[]"
    accept=".jpg,.png"
    :multiple="true"
    :auto="true"
    customUpload
    :maxFileSize="10000000"
    @uploader="onUploadImage"
    :chooseLabel="'Seleccionar Imágenes'"
    :uploadLabel="'Subir'"
    :cancelLabel="'Cancelar'"
    class="w-full"
  />

  <!-- Previsualización -->
  <div class="mt-3 flex flex-wrap gap-3">
    <div v-for="(img, index) in previewImgs" :key="index" class="relative">
      <img :src="img" class="w-32 h-32 object-cover rounded-lg border shadow" />
      <button
        type="button"
        @click="removeImage(index)"
        class="absolute top-1 right-1 bg-red-500 text-white rounded-full px-2 py-1 text-xs"
      >
        ✕
      </button>
    </div>
  </div>
</div>

    </div>
    </div>
    </div>
      
    </AppLayout>
 
</template>

<script setup >
import { onMounted, ref, watch } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import FileUpload from 'primevue/fileupload'
import MultiSelect from 'primevue/multiselect'
import Calendar from 'primevue/calendar'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'
import Select from 'primevue/select'
import AppLayout from '@/layout/AppLayout.vue';
import { Head } from '@inertiajs/vue3';



const toast = useToast()
const post = ref({
  titulo: '',
  category_id: null,
  resumen: '',
  contenido: '',
  fecha_programada: null,
})
const products = ref([])
const selectedProduct = ref(null)
const categories = ref([])
const archivoImgs = ref([])       // para guardar archivos
const previewImgs = ref([])    

function cancelar() {
  window.history.back()
}

function onUploadImage(event) {
  const allowedTypes = ['image/jpeg', 'image/png']
  for (const file of event.files) {
    if (file && allowedTypes.includes(file.type)) {
      archivoImgs.value.push(file)
      previewImgs.value.push(URL.createObjectURL(file))
      toast.add({ severity: 'success', summary: 'Imagen cargada', detail: `Archivo "${file.name}" listo para enviar`, life: 3000 })
    } else {
      toast.add({ severity: 'error', summary: 'Archivo inválido', detail: 'Debe subir un archivo JPG o PNG.', life: 4000 })
    }
  }
}

// Eliminar imagen de la lista
function removeImage(index) {
  archivoImgs.value.splice(index, 1)
  previewImgs.value.splice(index, 1)
}

// Guardar post con múltiples imágenes
function guardarPost() {
  const formData = new FormData()
  formData.append('user_id', 1)
  formData.append('titulo', post.value.titulo)
  formData.append('category_id', post.value.category_id)
  formData.append('resumen', post.value.resumen)
  formData.append('contenido', post.value.contenido)
  formData.append('fecha_programada', formatDateRequest(post.value.fecha_programada))
  formData.append('state_id', 1)

  // Agregar todas las imágenes
  archivoImgs.value.forEach((img, i) => {
    formData.append('imagenes[]', img)
  })

  axios.post('/api/blog/guardar', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
    .then(() => {
      toast.add({ severity: 'success', summary: 'Publicación registrada', detail: 'Datos guardados correctamente', life: 3000 })
      cancelar()
    })
    .catch((error) => {
      toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Ocurrió un error', life: 5000 })
    })
}

const formatDateRequest = (date) => {
  const d = new Date(date)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}:${String(d.getSeconds()).padStart(2, '0')}`
}

async function obtenerProductos() {
  try {
    const res = await axios.get('/api/blog/productos')
    products.value = res.data
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar productos', life: 3000 })
  }
}

async function obtenerCategorias() {
  try {
    const res = await axios.get(`/api/blog/listar-categoria-filtrada/${selectedProduct.value}`)
    categories.value = res.data
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar categorías', life: 3000 })
  }
}

onMounted(() => {
  obtenerProductos()
})

watch(selectedProduct, () => {
  obtenerCategorias()
})
</script>

<style scoped>
/* Mejorar editor Quill */
.ql-editor {
  min-height: 150px;
}
</style>
