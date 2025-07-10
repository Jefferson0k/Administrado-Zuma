<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'

const props = defineProps({ model: Object })
const emit = defineEmits(['updated', 'close'])

const visible = ref(true)
const form = ref({ nombre: '', dias: 1 })

watch(() => props.model, (val) => {
  if (val) form.value = { nombre: val.nombre, dias: val.dias }
}, { immediate: true })

const toast = useToast()

const actualizar = () => {
  router.put(`/payment-frequencies/${props.model.id}`, form.value, {
    onSuccess: (page) => {
      toast.add({ severity: 'success', summary: 'Actualizado', detail: 'Frecuencia actualizada', life: 3000 })
      const updatedData = page.props.flash?.updated || { ...props.model, ...form.value }
      emit('updated', updatedData)
    }
  })
}
const hideDialog = () => {
  visible.value = false
}
</script>

<template>
  <Dialog v-model:visible="visible" modal header="Editar frecuencia" :style="{ width: '25rem' }" @hide="$emit('close')">
    <div class="flex flex-col gap-3">
      <label>Nombre <span class="text-red-500">*</span></label>
      <InputText v-model="form.nombre" />

      <label>Días <span class="text-red-500">*</span></label>
      <InputNumber v-model="form.dias" :min="1" />

    </div>
    <template #footer>
      <Button label="Cancelar" icon="pi pi-times" severity="secondary" text @click="hideDialog" />
      <Button label="Actualizar" icon="pi pi-check" @click="actualizar" severity="contrast" />
    </template>
  </Dialog>
</template>
