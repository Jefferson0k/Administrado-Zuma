<template>
    <Toolbar class="mb-6">
        <template #start>
            <Button label="Nuevo Sector" icon="pi pi-plus" severity="contrast" class="mr-2" @click="openNew" />
        </template>
    </Toolbar>
    <Dialog v-model:visible="sectorDialog" :style="{ width: '600px' }" header="Registro de sectores" :modal="true">
        <div class="flex flex-col gap-6">
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12">
                    <label for="name" class="block font-bold mb-2">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <InputText id="name" v-model.trim="sector.name" maxlength="255" fluid
                        :class="{ 'p-invalid': submitted && (!sector.name || serverErrors.name) }" />
                    <small v-if="submitted && !sector.name" class="text-red-500">
                        El nombre es obligatorio.
                    </small>
                    <small v-else-if="serverErrors.name" class="text-red-500">
                        {{ serverErrors.name[0] }}
                    </small>
                </div>
            </div>
        </div>
        <template #footer>
            <div class="flex justify-between items-center w-full">
                <small class="italic text-sm">
                    Los campos marcados con <span class="text-red-500">*</span> son obligatorios.
                </small>
                <div class="flex gap-2">
                    <Button label="Cancelar" icon="pi pi-times" severity="secondary" text @click="hideDialog" />
                    <Button label="Guardar" icon="pi pi-check" severity="contrast" :loading="loading"
                        :disabled="!sector.name || sector.name.length > 255" @click="guardarSector" />
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import Toolbar from 'primevue/toolbar';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import { useToast } from 'primevue/usetoast';
import { defineEmits } from 'vue';

const toast = useToast();
const emit = defineEmits(['agregado']);

const sectorDialog = ref(false);
const submitted = ref(false);
const loading = ref(false);
const serverErrors = ref({});

const sector = ref({
    name: '',
});

function resetSector() {
    sector.value = { name: '' };
    serverErrors.value = {};
    submitted.value = false;
    loading.value = false;
}

function openNew() {
    resetSector();
    sectorDialog.value = true;
}

function hideDialog() {
    sectorDialog.value = false;
    resetSector();
}

async function guardarSector() {
    submitted.value = true;
    serverErrors.value = {};
    loading.value = true;
    try {
        await axios.post('/sectors', sector.value);
        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Sector registrado correctamente',
            life: 3000,
        });
        hideDialog();
        emit('agregado');
    } catch (error) {
        if (error.response && error.response.status === 422) {
            serverErrors.value = error.response.data.errors || {};
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se pudo registrar el sector',
                life: 3000,
            });
        }
    } finally {
        loading.value = false;
    }
}
</script>
