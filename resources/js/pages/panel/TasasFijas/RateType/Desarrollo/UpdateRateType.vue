<template>
    <Dialog v-model:visible="visible" header="Editar Tipo de Tasa" modal :style="{ width: '450px' }">
        <div class="p-fluid">
            <div class="mb-3">
                <label class="font-bold mb-1 block">Nombre <span class="text-red-500">*</span></label>
                <InputText v-model="form.nombre" fluid />
            </div>
            <div class="mb-3">
                <label class="font-bold mb-1 block">Descripción <span class="text-red-500">*</span></label>
                <InputText v-model="form.descripcion" fluid />
            </div>
        </div>

        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" text @click="visible = false" severity="secondary" />
            <Button label="Actualizar" icon="pi pi-check" @click="update" severity="contrast" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, defineExpose } from 'vue'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'

const toast = useToast()
const visible = ref(false)
const form = ref({ id: null, nombre: '', descripcion: '' })

const open = (rateType) => {
    form.value = { ...rateType }
    visible.value = true
}

const update = async () => {
    try {
        await axios.put(`/rate-types/${form.value.id}`, form.value)
        toast.add({ severity: 'success', summary: 'Actualizado', detail: 'Tipo de tasa actualizado', life: 3000 })
        visible.value = false
        emit('updated')
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar' })
    }
}

const emit = defineEmits(['updated'])
defineExpose({ open })
</script>
