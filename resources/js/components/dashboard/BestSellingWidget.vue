<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const productos = ref([
  {
    id: 1,
    nombre: 'Factoring',
    descripcion: 'Financiamiento a través de la compra de facturas por cobrar.',
    progreso: 75,
    color: 'bg-orange-500'
  },
  {
    id: 2,
    nombre: 'Hipotecas',
    descripcion: 'Producto de inversión respaldado por hipotecas.',
    progreso: 50,
    color: 'bg-blue-500'
  },
  {
    id: 3,
    nombre: 'Tasa Fija',
    descripcion: 'Inversión con tasa fija mensual garantizada.',
    progreso: 65,
    color: 'bg-green-500'
  }
])

function registrarClick(productoId) {
  router.post(`/api/producto/${productoId}/click`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      // Redirige al login del producto luego de registrar la visita
      router.visit(`/producto/${productoId}/login`)
    },
    onError: (errors) => {
      console.error(errors)
    }
  })
}
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div
      v-for="producto in productos"
      :key="producto.id"
      class="card shadow-md p-5 rounded-xl border border-gray-200 dark:border-gray-700"
    >
      <div class="text-xl font-semibold mb-2">{{ producto.nombre }}</div>
      <div class="text-gray-500 mb-4 dark:text-gray-400">
        {{ producto.descripcion }}
      </div>
      <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded mb-3">
        <div
          class="h-full rounded"
          :class="producto.color"
          :style="{ width: producto.progreso + '%' }"
        ></div>
      </div>
      <div class="flex justify-between items-center">
        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
          {{ producto.progreso }}% interés estimado
        </span>
        <Button
          label="Ingresar"
          icon="pi pi-sign-in"
          class="p-button-sm"
          @click="registrarClick(producto.id)"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.card {
  background-color: white;
}
.dark .card {
  background-color: #1f2937;
}
</style>
