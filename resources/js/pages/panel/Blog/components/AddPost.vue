<template>
    <Toolbar class="mb-6">
        <template #start>
            <Button label="Nuevo" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" />
            <Button label="Eliminar" icon="pi pi-trash" severity="secondary" @click="showToast" />
        </template>
        <template #end>
            <!-- <Button label="Exportar" icon="pi pi-upload" severity="secondary" @click="showToast" /> -->
        </template>
    </Toolbar>

    <Dialog v-model:visible="AgregarDialog" :style="{ width: '700px' }" header="Registro de Publicación" :modal="true">
        <div class="flex flex-col gap-6">
            <div>
                <label class="block font-bold mb-3">Título <span class="text-red-500">*</span></label>
                <InputText v-model="post.titulo" :useGrouping="false" placeholder="Ingresa el título" inputId="titulo" class="w-full" />
            </div>
            <div>
                <label class="block font-bold mb-3">Producto <span class="text-red-500">*</span></label>
                <Select v-model="selectedProduct" :options="products" optionLabel="nombre" optionValue="id" placeholder="Seleccione el producto" class="w-full" />
            </div>
            <div>
                <label class="block font-bold mb-3">Categoría(s) <span class="text-red-500">*</span></label>
                <MultiSelect v-model="post.category_id" display="chip" :options="categories" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione la categoría" 
                    :maxSelectedLabels="3" class="w-full" />
            </div>
            <div>
                <label class="block font-bold mb-3">Resumen <span class="text-red-500">*</span></label>
                <InputText v-model="post.resumen" :useGrouping="false" placeholder="Ingresa el resumen" inputId="resumen" class="w-full" />
            </div>
            <div>
                <label class="block font-bold mb-3">Contenido <span class="text-red-500">*</span></label>
                <!-- <Textarea v-model="post.contenido" placeholder="Ingresa el contenido" rows="3" class="w-full" /> -->
                <QuillEditor
                    v-model:content="post.contenido"
                    contentType="html"
                    placeholder="Ingresa el contenido"
                    class="w-full"
                />
            </div>
            <div>
                <label class="block font-bold mb-3">Fecha Programada <span class="text-red-500">*</span></label>
                <Calendar
                    v-model="post.fecha_programada"
                    dateFormat="dd/mm/yy"
                    placeholder="Selecciona la fecha"
                    showIcon
                    showTime hourFormat="12"
                    class="w-full"
                />
            </div>
            <div>
                <label class="block font-bold mb-3">Imagén para mostrar <span class="text-red-500">*</span></label>
                <FileUpload
                    mode="advanced"
                    name="img"
                    accept=".jpg"
                    :auto="true"
                    customUpload
                    :maxFileSize="10000000"
                    @uploader="onUploadImage"
                    :chooseLabel="'Seleccionar Imagen'"
                    :uploadLabel="'Subir'"
                    :cancelLabel="'Cancelar'"
                    class="w-full"
                />
            </div>
        </div>

        <!-- Botones -->
        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog" severity="secondary"/>
            <Button label="Guardar" icon="pi pi-check" @click="guardarPost" severity="contrast"/>
        </template>
    </Dialog>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { defineEmits } from 'vue';

import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import FileUpload from 'primevue/fileupload';
import MultiSelect from 'primevue/multiselect';
import Calendar from 'primevue/calendar';

import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'
import Select from 'primevue/select';

const toast = useToast();
const emit = defineEmits(['agregado']);

const AgregarDialog = ref(false);
const submitted = ref(false);
const archivoImg = ref(null);

const post = ref({
    titulo: '',
    category_id: null,
    resumen: '',
    contenido: '',
    fecha_programada: null,
});

const products = ref([])
const selectedProduct = ref([])
const categories = ref([]);

function openNew() {
    resetPost();
    AgregarDialog.value = true;
}

function hideDialog() {
    AgregarDialog.value = false;
    resetPost();
}

function resetPost() {
    post.value = {
        titulo: '',
        category_id: null,
        resumen: '',
        contenido: '',
        fecha_programada: null,
    };
    archivoImg.value = null;
    submitted.value = false;
}

function onUploadImage(event) {
    const file = event.files[0];
    const allowedTypes = ['image/jpeg', 'image/png'];

    if (file && allowedTypes.includes(file.type)) {
        archivoImg.value = file;
        toast.add({
            severity: 'success',
            summary: 'Imagen cargada',
            detail: `Archivo "${file.name}" listo para enviar`,
            life: 3000
        });
    } else {
        toast.add({
            severity: 'error',
            summary: 'Archivo inválido',
            detail: 'Debe subir un archivo del tipo jpg o png.',
            life: 4000
        });
    }
}

function guardarPost() {
    submitted.value = true;

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    /*if (!post.value.ruc || post.value.ruc.toString().length !== 11 || isNaN(post.value.ruc)) {
        toast.add({ severity: 'warn', summary: 'RUC inválido', detail: 'Debe ingresar un RUC válido', life: 3000 });
        return;
    }

    if (!post.value.razonSocial || !post.value.direccion || !post.value.actividadEconomica || !post.value.estado) {
        toast.add({ severity: 'warn', summary: 'Campos incompletos', detail: 'Debe consultar el RUC primero', life: 3000 });
        return;
    }

    if (!post.value.tipo || !tiposEntidad.includes(post.value.tipo)) {
        toast.add({ severity: 'warn', summary: 'Tipo inválido', detail: 'Seleccione un tipo válido', life: 3000 });
        return;
    }

    if (!post.value.telefono || isNaN(post.value.telefono)) {
        toast.add({ severity: 'warn', summary: 'Teléfono inválido', detail: 'Debe ingresar solo números', life: 3000 });
        return;
    }

    if (!post.value.email || !emailRegex.test(post.value.email)) {
        toast.add({ severity: 'warn', summary: 'Email inválido', detail: 'Ingrese un correo válido', life: 3000 });
        return;
    }

    if (!archivoImg.value) {
        toast.add({ severity: 'warn', summary: 'Falta PDF', detail: 'Debe subir un archivo PDF', life: 3000 });
        return;
    }*/

    const formData = new FormData()
    formData.append('user_id', 1)
    formData.append('titulo', post.value.titulo)
    formData.append('category_id', post.value.category_id)
    formData.append('resumen', post.value.resumen)
    formData.append('contenido', post.value.contenido)
    //formData.append('fecha_programada', post.value.fecha_programada.toISOString().split('T')[0])
    formData.append('fecha_programada', formatDateRequest(post.value.fecha_programada))
    formData.append('state_id', 1)
    formData.append('imagen', archivoImg.value)

    axios.post('/api/blog/guardar', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    }).then(() => {
        toast.add({
            severity: 'success',
            summary: 'Publicación registrada',
            detail: 'Los datos se guardaron correctamente',
            life: 3000
        });
        emit('agregado');
        hideDialog();
    })
    .catch((error) => {
        console.error('Error al guardar la publicación:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Ocurrió un error al guardar la publicación',
            life: 5000
        });
    });
}

const formatDateRequest = (date) => {
  const d = new Date(date)
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  const hours = String(d.getHours()).padStart(2, '0')
  const minutes = String(d.getMinutes()).padStart(2, '0')
  const seconds = String(d.getSeconds()).padStart(2, '0')
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`
}

function showToast() {
    toast.add({
        severity: 'info',
        summary: 'Información',
        detail: 'Aún se encuentra en desarrollo',
        life: 3000
    });
}

async function obtenerProductos() {
  try {
    const res = await axios.get('/api/blog/productos')
    products.value = res.data
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar productos',
      life: 3000
    })
  }
}


/*async function obtenerCategorias() {
  try {
    const res = await axios.get('/api/blog/listar-categoria')
    categories.value = res.data
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar categorias',
      life: 3000
    })
  }
}*/

async function obtenerCategorias() {
  try {
    const res = await axios.get(`/api/blog/listar-categoria-filtrada/${selectedProduct.value}`)
    categories.value = res.data
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar categorias',
      life: 3000
    })
  }
}

onMounted(() => {
    obtenerProductos()
  //obtenerCategorias()
})

watch(() => products.refresh, () => {
  obtenerProductos()
})

watch(() => categories.refresh, () => {
  obtenerCategorias()
})

watch(selectedProduct, (newVal, oldVal) => {
  obtenerCategorias()
})
</script>
