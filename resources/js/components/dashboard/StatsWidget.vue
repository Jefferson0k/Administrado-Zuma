<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const totalVisitas = ref(0)

const productos = ref([
  {
    id: 1,
    nombre: 'Factoring',
    descripcion: 'Compra de facturas para financiamiento.',
    visitas: 0,
    icono: 'pi-briefcase',
    color: 'bg-orange-100',
    textColor: 'text-orange-500',
  },
  {
    id: 2,
    nombre: 'Hipotecas',
    descripcion: 'Inversión respaldada por garantías hipotecarias.',
    visitas: 0,
    icono: 'pi-home',
    color: 'bg-blue-100',
    textColor: 'text-blue-500',
  },
  {
    id: 3,
    nombre: 'Tasa Fija',
    descripcion: 'Interés fijo mensual garantizado.',
    visitas: 0,
    icono: 'pi-percentage',
    color: 'bg-green-100',
    textColor: 'text-green-500',
  },
])

async function cargarVisitas() {
  try {
    const response = await axios.get('/api/visitas-producto')
    const visitas = response.data.data
    totalVisitas.value = response.data.total_visitas // NUEVO

    productos.value.forEach(producto => {
      const encontrado = visitas.find(v => v.producto_id === producto.id)
      producto.visitas = encontrado ? encontrado.total : 0
    })
  } catch (error) {
    console.error('Error cargando visitas', error)
  }
}

onMounted(() => {
  cargarVisitas()
})
</script>

<template>
  <div class="grid grid-cols-12 gap-6">
    <!-- Card global -->
    <div class="col-span-12 lg:col-span-6 xl:col-span-3">
      <div class="card mb-0">
        <div class="flex justify-between mb-4">
          <div>
            <span class="block text-muted-color font-medium mb-4">Visitas Totales</span>
            <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
              {{ totalVisitas }} Visitas
            </div>
          </div>
          <div class="flex items-center justify-center rounded-border bg-purple-100"
            style="width: 2.5rem; height: 2.5rem">
            <span class="text-purple-500 font-bold text-xl">Z</span>
          </div>
        </div>
        <span class="text-primary font-medium">Cantidad total de visitas a todos los productos.</span>
      </div>
    </div>

    <!-- Cards por producto -->
    <div v-for="producto in productos" :key="producto.id" class="col-span-12 lg:col-span-6 xl:col-span-3">
      <div class="card mb-0">
        <div class="flex justify-between mb-4">
          <div>
            <span class="block text-muted-color font-medium mb-4">{{ producto.nombre }}</span>
            <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
              {{ producto.visitas }} Visitas
            </div>
          </div>
          <div class="flex items-center justify-center rounded-border" :class="producto.color"
            style="width: 2.5rem; height: 2.5rem">
            <i class="pi !text-xl" :class="[producto.icono, producto.textColor]"></i>
          </div>
        </div>
        <span class="text-primary font-medium">{{ producto.descripcion }}</span>
      </div>
    </div>
  </div>
</template>
