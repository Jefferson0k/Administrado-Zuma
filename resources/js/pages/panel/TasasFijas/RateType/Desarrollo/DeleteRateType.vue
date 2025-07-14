<template>
    <Dialog v-model:visible="visible" header="Confirmar Eliminación" modal :style="{ width: '450px' }">
        <p>¿Estás seguro de eliminar el tipo de tasa <b>{{ selected?.nombre }}</b>?</p>
        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" text @click="visible = false" severity="secondary"/>
            <Button label="Eliminar" icon="pi pi-trash" severity="danger" @click="destroy" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, defineExpose } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'

const visible = ref(false)
const selected = ref(null)
const toast = useToast()

const open = (rateType) => {
    selected.value = rateType
    visible.value = true
}

const destroy = async () => {
    try {
        await axios.delete(`/rate-types/${selected.value.id}`)
        toast.add({ severity: 'success', summary: 'Eliminado', detail: 'Tipo de tasa eliminado', life: 3000 })
        visible.value = false
        emit('updated')
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo eliminar' })
    }
}

const emit = defineEmits(['updated'])
defineExpose({ open })
</script>
