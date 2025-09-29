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

    <Dialog v-model:visible="AgregarDialog" :style="{ width: '600px' }" header="Seleccionar Categorias" :modal="true">
        <div class="flex flex-col gap-6">
            <div>
                <label class="block font-bold mb-3">Producto <span class="text-red-500">*</span></label>
                <Select v-model="selectedProduct" :options="products" optionLabel="nombre" optionValue="id"
                    placeholder="Seleccione el producto" class="w-full" />
            </div>
             <div class="col-span-2">
            <label class="block font-semibold mb-2">Agregar Categorias</label>
            <div class="flex gap-2">
                <InputText v-model="nuevaCategoria" placeholder="Ingresa el nombre" class="w-full" />
                <Button label="Agregar" icon="pi pi-plus" @click="agregarCategoria" />
            </div>

            <!-- Lista de enlaces agregados -->
            <ul class="mt-3 space-y-2">
                <li v-for="(link, index) in categorias" :key="index" class="flex items-center justify-between bg-gray-100 px-3 py-2 rounded">
                <a :href="link" target="_blank" class="text-blue-600 underline">{{ link }}</a>
                <button @click="eliminarCategoria(index)" class="text-red-500 hover:text-red-700">✕</button>
                </li>
            </ul>
            </div>
            <!-- <div>
                <label class="block font-bold mb-3">Categoría(s) <span class="text-red-500">*</span></label>
                <MultiSelect v-model="selectedCategories" display="chip" :options="products" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione la categoría" 
                    :maxSelectedLabels="3" class="w-full" />
            </div> -->
            <!-- <div>
                <label class="block font-bold mb-3">Nombre <span class="text-red-500">*</span></label>
                <InputText v-model="category.nombre" :useGrouping="false" placeholder="Ingresa el nombre" inputId="nombre" class="w-full" />
            </div>
            <div>
                <label class="block font-bold mb-3">Descripción <span class="text-red-500">*</span></label>
                <InputText v-model="category.descripcion" :useGrouping="false" placeholder="Ingresa la descripción" inputId="descripcion" class="w-full" />
            </div> -->
        </div>

        <!-- Botones -->
        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog" severity="secondary" />
            <Button label="Guardar" icon="pi pi-check" @click="guardarCategoria" severity="contrast" />
        </template>
    </Dialog>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import { defineEmits } from 'vue'

import Toolbar from 'primevue/toolbar'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'

const toast = useToast()
const emit = defineEmits(['agregado'])

const AgregarDialog = ref(false)
const consultandoRuc = ref(false)
const submitted = ref(false)
const archivoImg = ref(null)

const tiposEntidad = ['banco', 'cooperativa', 'caja', 'financiera']
const estados = ['activo', 'inactivo']

const category = ref({
    nombre: '',
    descripcion: '',
})

const products = ref([])
const selectedProduct = ref()
const selectedCategories = ref([])
const categorias = ref([])          // lista de categoria
const nuevaCategoria = ref('')  

function openNew() {
    resetEmpresa()
    AgregarDialog.value = true
}

function hideDialog() {
    AgregarDialog.value = false
    resetEmpresa()
}

//eliminar un enlace de la lista
function agregarCategoria() {
  if (nuevaCategoria.value.trim() !== '') {
    categorias.value.push(nuevaCategoria.value.trim())
    nuevaCategoria.value = ''
  }
}
// elimina un enlace de la lista
function eliminarCategoria(index) {
  categorias.value.splice(index, 1)
}

function resetEmpresa() {
    category.value = {
        nombre: '',
        descripcion: '',
    }
    archivoImg.value = null
    submitted.value = false
}

function guardarCategoria() {
    submitted.value = true

    axios.post('/blog/guardar-categoria', {
        product_id: selectedProduct.value,   // id del producto
        categorias: categorias.value        // array con las categorías agregadas
    })
    .then(() => {
        toast.add({
            severity: 'success',
            summary: 'Categorías registradas',
            detail: 'Los datos se guardaron correctamente',
            life: 3000
        })
        emit('agregado')
        hideDialog()
    })
    .catch((error) => {
        console.error('Error al guardar la categoría:', error)
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Ocurrió un error al guardar la categoría',
            life: 5000
        })
    })
}

function showToast() {
    toast.add({
        severity: 'info',
        summary: 'Información',
        detail: 'Aún se encuentra en desarrollo',
        life: 3000
    })
}

async function obtenerProductos() {
    try {
        const res = await axios.get('/api/blog/productos')
        products.value = res.data
        console.log(products.value)
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo cargar los productos',
            life: 3000
        })
    }
}



onMounted(() => {
    obtenerProductos()
})

watch(() => products.refresh, () => {
    obtenerProductos()
})

</script>
