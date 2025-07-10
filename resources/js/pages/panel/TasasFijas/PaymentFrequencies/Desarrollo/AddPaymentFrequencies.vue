<script setup>
import { ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Toolbar from 'primevue/toolbar'

const emit = defineEmits(['created'])
const toast = useToast()
const visible = ref(false)

const form = ref({
    nombre: '',
    dias: ''
})

const guardar = async () => {
    try {
        const { data } = await axios.post('/payment-frequencies', form.value)
        emit('created', data.data)

        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Frecuencia registrada', life: 3000 })
        visible.value = false
        form.value = { nombre: '', dias: 1 }
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Error al registrar' })
    }
}
const hideDialog = () => {
    visible.value = false
}
</script>

<template>
    <Dialog v-model:visible="visible" modal header="Nueva frecuencia" :style="{ width: '25rem' }">
        <div class="flex flex-col gap-3">
            <label>Nombre <span class="text-red-500">*</span></label>
            <InputText v-model="form.nombre" />

            <label>Días <span class="text-red-500">*</span></label>
            <InputNumber v-model="form.dias" :min="1" />
        </div>
        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" severity="secondary" text @click="hideDialog" />
            <Button label="Guardar" icon="pi pi-check" @click="guardar" severity="contrast" />
        </template>
    </Dialog>

    <Toolbar class="mb-6">
        <template #start>
            <Button label="Nuevo" icon="pi pi-plus" @click="visible = true" severity="contrast" />
        </template>
    </Toolbar>
</template>
