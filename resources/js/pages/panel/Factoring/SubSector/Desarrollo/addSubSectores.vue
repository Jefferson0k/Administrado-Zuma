<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

const { sector } = defineProps({
    sector: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['agregado'])
const toast = useToast()
const subSectorDialog = ref(false)
const submitted = ref(false)

const form = useForm({
    name: '',
    sector_id: null
})

function openNew() {
    form.reset()
    form.clearErrors()
    form.sector_id = sector.id
    subSectorDialog.value = true
    submitted.value = false
}

function hideDialog() {
    subSectorDialog.value = false
    form.reset()
    form.clearErrors()
    submitted.value = false
}

function volver() {
    router.visit('/factoring/sectores')
}

async function guardarSubSector() {
    submitted.value = true
    form.processing = true

    try {
        await axios.post('/subsectors', {
            name: form.name,
            sector_id: form.sector_id
        })

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Subsector registrado correctamente',
            life: 3000
        })

        hideDialog()
        emit('agregado')
    } catch (error) {
        if (error.response && error.response.status === 422) {
            form.errors = error.response.data.errors || {}
        }

        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo registrar el subsector',
            life: 3000
        })
    } finally {
        form.processing = false
    }
}

defineExpose({ openNew })
</script>

<template>
    <Toolbar class="mb-6">
        <template #start>
            <p class="text-xl font-semibold">
                Lista de Subsectores de: {{ sector.name }}
            </p>
        </template>

        <template #end>
            <Button label="Nuevo" icon="pi pi-plus" severity="contrast" class="mr-2" @click="openNew" />
            <Button label="Volver" icon="pi pi-arrow-left" severity="secondary" class="mr-2" @click="volver" />
        </template>
    </Toolbar>

    <Dialog v-model:visible="subSectorDialog" :style="{ width: '450' }" header="Registro de Subsector" :modal="true">
        <div class="flex flex-col gap-6">
            <div class="grid grid-cols-12 gap-4">
                <!-- Nombre -->
                <div class="col-span-12">
                    <label for="name" class="block font-bold mb-2">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <InputText id="name" v-model.trim="form.name" maxlength="255" class="w-full"
                        :class="{ 'p-invalid': submitted && form.errors.name }" />
                    <small v-if="submitted && form.errors.name" class="text-red-500">
                        {{ Array.isArray(form.errors.name) ? form.errors.name[0] : form.errors.name }}
                    </small>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between items-center w-full">
                <small class="italic text-sm">
                    Los campos marcados con <span class="text-red-500">*</span> son obligatorios.
                </small>
                <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Guardar" icon="pi pi-check" :loading="form.processing"
                    :disabled="!form.name || form.processing" @click="guardarSubSector" />
            </div>
        </template>
    </Dialog>
</template>
