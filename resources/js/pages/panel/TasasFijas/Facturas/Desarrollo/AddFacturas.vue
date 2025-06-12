<template>
    <Toolbar class="mb-6">
        <template #start>
            <Button label="Nuevo" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" />
            <Button label="Eliminar" icon="pi pi-trash" severity="secondary" @click="showToast" />
        </template>
        <template #end>
            <Button label="Exportar" icon="pi pi-upload" severity="secondary" @click="showToast" />
        </template>
    </Toolbar>

    <Dialog v-model:visible="AgregarDialog" :style="{ width: '450px' }" header="Registro de Facturas" :modal="true">
        <div class="flex flex-col gap-6">
                        <div>
                <label for="tipoCliente_id" class="block font-bold mb-3">Tipo de cliente <span
                        class="text-red-500">*</span></label>
            </div>

        </div>
        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog" />
            <Button label="Guardar" icon="pi pi-check" @click="showToast" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref } from 'vue';
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import { useToast } from 'primevue/usetoast';
import { defineEmits } from 'vue';

const toast = useToast();
const AgregarDialog = ref(false);
const emit = defineEmits(['agregado']);

function openNew() {
    AgregarDialog.value = true;
}

function hideDialog() {
    AgregarDialog.value = false;
}

const showToast = () => {
    toast.add({
        severity: 'info',
        summary: 'Información',
        detail: 'Aún se encuentra en desarrollo',
        life: 3000
    });
}
</script>