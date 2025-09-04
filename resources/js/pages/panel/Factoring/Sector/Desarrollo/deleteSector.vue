<template>
    <Dialog v-model:visible="localVisible" :style="{ width: '32rem' }" header="Confirmar eliminación" modal
        :closable="false" @hide="closeDialog">
        <div class="flex items-center gap-4 p-4">
            <!-- Icono de advertencia -->
            <i class="pi pi-exclamation-triangle !text-3xl" />

            <!-- Mensaje -->
            <span>
                ¿Estás seguro de que deseas eliminar el sector
                <b>{{ tipoCliente?.name }}</b>?
            </span>
        </div>

        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" severity="secondary" text @click="closeDialog" />
            <Button label="Eliminar" icon="pi pi-trash" severity="danger" :loading="loading" @click="deleteSector" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import axios from 'axios'

const props = defineProps({
    visible: { type: Boolean, required: true },
    tipoCliente: { type: Object, required: true }
})
const emit = defineEmits(['update:visible', 'deleted'])

// Prop local para poder usar v-model en Dialog
const localVisible = ref(props.visible)
watch(() => props.visible, val => localVisible.value = val)

// Loading del botón
const loading = ref(false)

// Cerrar diálogo
function closeDialog() {
    localVisible.value = false
    emit('update:visible', false)
}

// Función de eliminar sector
async function deleteSector() {
    if (!props.tipoCliente?.id) return
    loading.value = true
    try {
        await axios.delete(`/sectors/${props.tipoCliente.id}`)
        emit('deleted') // notifica al padre para refrescar la tabla
        closeDialog()
    } catch (error) {
        console.error('Error eliminando sector:', error)
    } finally {
        loading.value = false
    }
}
</script>

<style scoped>
/* Opcional: Ajustes menores para el icono y espaciado */
</style>
