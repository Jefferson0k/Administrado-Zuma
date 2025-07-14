<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppLayout from '@/layout/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import Espera from '@/components/Espera.vue'
import AddPaymentFrequencies from './Desarrollo/AddPaymentFrequencies.vue'
import ListPaymentFrequencies from './Desarrollo/ListPaymentFrequencies.vue'
import axios from 'axios'

const isLoading = ref(true)
const frequencies = ref([])

const loadFrequencies = async () => {
  const { data } = await axios.get('/payment-frequencies')
  frequencies.value = data.data
}

const addFrequency = (nueva) => {
  frequencies.value.push(nueva)
}

const updateFrequency = (actualizada) => {
  const index = frequencies.value.findIndex(f => f.id === actualizada.id)
  if (index !== -1) {
    frequencies.value[index] = actualizada
  }
}

const deleteFrequency = (id) => {
  frequencies.value = frequencies.value.filter(f => f.id !== id)
}

onMounted(async () => {
  await loadFrequencies()
  isLoading.value = false
})
</script>

<template>
  <Head title="Frecuencia de pagos" />
  <AppLayout>
    <div>
      <template v-if="isLoading">
        <Espera />
      </template>
      <template v-else>
        <div class="card">
          <AddPaymentFrequencies @created="addFrequency" />
          <ListPaymentFrequencies
            :frequencies="frequencies"
            @updated="updateFrequency"
            @deleted="deleteFrequency"
          />
        </div>
      </template>
    </div>
  </AppLayout>
</template>
