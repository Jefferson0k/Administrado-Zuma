<template>
  <Toast />
  <Toolbar class="mb-6">
    <template #start>
      <Button label="Nuevo" icon="pi pi-plus" severity="secondary" class="mr-2" @click="showToast" />
      <Button label="Eliminar" icon="pi pi-trash" severity="secondary" @click="showToast" />
    </template>
    <template #end>
      <Button label="Exportar" icon="pi pi-upload" severity="secondary" @click="showToast" />
    </template>
  </Toolbar>
</template>

<script setup lang="ts">
import Toolbar from 'primevue/toolbar'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'

const emit = defineEmits(['agregado'])

const toast = useToast()

const showToast = () => {
  toast.add({
    severity: 'info',
    summary: 'Información',
    detail: 'Aún se encuentra en desarrollo',
    life: 3000
  })
  emit('agregado')
}
</script>