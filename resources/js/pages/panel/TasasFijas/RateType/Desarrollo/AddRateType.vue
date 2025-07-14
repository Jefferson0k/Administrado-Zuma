<template>
    <div>
        <Toast />
        <Toolbar class="mb-4">
            <template #start>
                <Button label="Nuevo" icon="pi pi-plus" severity="secondary" class="mr-2" @click="open" />
            </template>
        </Toolbar>

        <Dialog v-model:visible="visible" modal header="Nuevo Tipo de Tasa" :style="{ width: '450px' }">
            <div class="p-fluid">
                <div class="mb-3">
                    <label class="font-bold mb-1 block">Nombre <span class="text-red-500">*</span></label>
                    <InputText v-model="form.nombre" fluid/>
                </div>
                <div class="mb-3">
                    <label class="font-bold mb-1 block">Descripción <span class="text-red-500">*</span></label>
                    <InputText v-model="form.descripcion" fluid/>
                </div>
            </div>

            <template #footer>
                <Button label="Cancelar" icon="pi pi-times" text @click="visible = false" severity="secondary"/>
                <Button label="Guardar" icon="pi pi-check" @click="store" severity="contrast"/>
            </template>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, defineExpose } from 'vue'
import Toolbar from 'primevue/toolbar'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

const visible = ref(false)
const toast = useToast()
const emit = defineEmits(['updated'])

const form = ref({
    nombre: '',
    descripcion: ''
})

const open = () => {
    form.value = { nombre: '', descripcion: '' }
    visible.value = true
}

const store = async () => {
    try {
        await axios.post('/rate-types', form.value)
        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Tipo de tasa creado correctamente',
            life: 3000
        })
        visible.value = false
        emit('updated') // Notifica al padre que se actualice el listado
    } catch (err) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Hubo un problema al guardar',
            life: 3000
        })
    }
}

defineExpose({ open }) // Permite llamarlo desde el padre
</script>
