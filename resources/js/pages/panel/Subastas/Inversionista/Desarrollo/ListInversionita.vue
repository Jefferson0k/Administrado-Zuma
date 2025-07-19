<template>
    <br>
  <DataTable
    :value="depositos"
    :lazy="true"
    :paginator="true"
    :rows="perPage"
    :totalRecords="totalRecords"
    :loading="loading"
    :first="(currentPage - 1) * perPage"
    @page="onPage"
    responsiveLayout="scroll"
  >
    <Column field="nro_operation" header="Operación" />
    <Column field="amount" header="Monto" />
    <Column field="currency" header="Moneda" />
    <Column field="description" header="Descripción" />
    <Column field="persona" header="Persona" />
    <Column field="dni" header="DNI" />
    <Column field="created_at" header="Fecha" />
  </DataTable>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import  DataTable  from 'primevue/datatable'
import Column from 'primevue/column'
import axios from 'axios'

const depositos = ref([])
const totalRecords = ref(0)
const loading = ref(false)
const currentPage = ref(1)
const perPage = ref(15)

const fetchDeposits = async (page = 1) => {
  loading.value = true
  try {
    const { data } = await axios.get('/deposits/historial', {
      params: { page, per_page: perPage.value }
    })
    depositos.value = data.data
    totalRecords.value = data.meta.total
    currentPage.value = data.meta.current_page
  } catch (err) {
    console.error('Error al cargar depósitos:', err)
  } finally {
    loading.value = false
  }
}

const onPage = (event) => {
  const page = Math.floor(event.first / event.rows) + 1
  fetchDeposits(page)
}

onMounted(() => {
  fetchDeposits()
})
</script>
