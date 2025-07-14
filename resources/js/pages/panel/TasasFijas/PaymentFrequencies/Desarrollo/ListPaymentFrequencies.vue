<script setup lang="ts">
import { ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import axios from 'axios'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import ConfirmDialog from 'primevue/confirmdialog'
import { FilterMatchMode } from '@primevue/core/api';

import UpdatePaymentFrequencie from './UpdatePaymentFrequencie.vue'

const props = defineProps({ frequencies: Array })
const emit = defineEmits(['updated', 'deleted'])

const toast = useToast()
const confirm = useConfirm()
const selectedItem = ref(null)
const showUpdate = ref(false)

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

const abrirEditar = (item) => {
    selectedItem.value = { ...item }
    showUpdate.value = true
}


const eliminar = (item) => {
  confirm.require({
    message: `¿Estás seguro que deseas eliminar "${item.nombre}"?`,
    header: 'Confirmar eliminación',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Cancelar',
    accept: async () => {
      try {
        await axios.delete(`/payment-frequencies/${item.id}`, {
          headers: {
            'X-Inertia': false
          }
        })

        toast.add({
          severity: 'success',
          summary: 'Eliminado',
          detail: 'Frecuencia eliminada',
          life: 3000 
        })
        emit('deleted', item.id)

      } catch (error) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'No se pudo eliminar'
        })
      }
    }
  })
}


</script>

<template>
    <UpdatePaymentFrequencie v-if="showUpdate" :model="selectedItem"
        @updated="(f) => { emit('updated', f); showUpdate = false }" @close="showUpdate = false" />

    <ConfirmDialog />

    <DataTable :value="frequencies" dataKey="id" :paginator="true" :rows="10" :filters="filters"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} registros"
        :rowsPerPageOptions="[5, 10, 25]" class="p-datatable-sm">
        <template #header>
            <div class="flex justify-between items-center">
                <h4 class="m-0">Frecuencias de Pago</h4>
                <InputText v-model="filters['global'].value" placeholder="Buscar..." />
            </div>
        </template>
        <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
        <Column field="nombre" header="Nombre" />
        <Column field="dias" header="Días" />
        <Column field="created_at" header="Creado" />
        <Column field="updated_at" header="Actualizado" />
        <Column header="Acciones" style="min-width: 10rem">
            <template #body="slotProps">
              <Button icon="pi pi-pencil" outlined rounded severity="contrast" class="mr-2" @click="abrirEditar(slotProps.data)" />
              <Button icon="pi pi-trash" outlined rounded severity="danger" @click="eliminar(slotProps.data)" />  
            </template>
        </Column>
    </DataTable>
</template>
