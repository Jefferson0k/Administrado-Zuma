<template>
    <Toast />
    <Toolbar class="mb-6">
        <template #start>
            <Button label="Nuevo" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openDialog" />
            <Button label="Eliminar" icon="pi pi-trash" severity="secondary" @click="showToast" />
        </template>
        <template #end>
            <Button label="Exportar" icon="pi pi-upload" severity="secondary" @click="showToast" />
        </template>
    </Toolbar>

    <Dialog v-model:visible="visible" modal header="Nuevo Plan" :style="{ width: '450px' }">
        <form @submit.prevent="storeTermPlan">
            <div class="field">
                <label for="nombre">Nombre</label>
                <InputText id="nombre" v-model="form.nombre" class="w-full" required />
            </div>
            <div class="field">
                <label for="dias_minimos">Días mínimos</label>
                <InputText id="dias_minimos" v-model="form.dias_minimos" class="w-full" required />
            </div>
            <div class="field">
                <label for="dias_maximos">Días máximos</label>
                <InputText id="dias_maximos" v-model="form.dias_maximos" class="w-full" required />
            </div>
            <div class="flex justify-end mt-4">
                <Button label="Cancelar" severity="secondary" @click="visible = false" class="mr-2" />
                <Button type="submit" label="Guardar" />
            </div>
        </form>
    </Dialog>
</template>

<script setup>
import Toolbar from 'primevue/toolbar'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import { useToast } from 'primevue/usetoast'
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const emit = defineEmits(['plan-added'])

const toast = useToast()
const visible = ref(false)
const form = ref({
    nombre: '',
    dias_minimos: '',
    dias_maximos: ''
})

const showToast = () => {
    toast.add({
        severity: 'info',
        summary: 'Información',
        detail: 'Aún se encuentra en desarrollo',
        life: 3000
    })
}

const openDialog = () => {
    visible.value = true
}

const storeTermPlan = () => {
    router.post('/term-plans', form.value, {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Plan registrado' })
            visible.value = false
            form.value = {
                nombre: '',
                dias_minimos: '',
                dias_maximos: ''
            }
            emit('plan-added')
        },
        onError: (errors) => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Revise los campos del formulario' })
        }
    })
}
</script>
