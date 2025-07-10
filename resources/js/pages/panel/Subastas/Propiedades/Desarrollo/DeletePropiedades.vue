<template>
    <Dialog v-model:visible="visible" modal header="Confirmar Eliminación" :style="{ width: '25rem' }">
        <template #header>
            <div class="flex align-items-center gap-2">
                <i class="pi pi-exclamation-triangle text-orange-500"></i>
                <span class="font-bold">Confirmar Eliminación</span>
            </div>
        </template>

        <div class="flex align-items-center gap-3 mb-3">
            <i class="pi pi-exclamation-triangle text-orange-500" style="font-size: 2rem"></i>
            <span>¿Estás seguro de que deseas eliminar esta propiedad?</span>
        </div>

        <div v-if="property" class="p-3 border-round surface-border border-1 mb-3">
            <div class="font-medium mb-2">{{ property.nombre }}</div>
            <div class="text-sm text-color-secondary">
                <div v-if="property.direccion">{{ property.direccion }}</div>
                <div>{{ property.distrito }}, {{ property.provincia }}, {{ property.departamento }}</div>
            </div>
        </div>

        <div class="text-sm text-color-secondary mb-3">
            <strong>Nota:</strong> Esta acción no se puede deshacer. Se eliminarán todos los datos y archivos asociados
            a esta
            propiedad.
        </div>

        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" severity="secondary" @click="cerrarModal" text />
            <Button label="Eliminar" icon="pi pi-trash" severity="danger" @click="eliminarPropiedad"
                :loading="loading" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false
    },
    idPropiedad: {
        type: [String, Number],
        default: null
    }
});

const emit = defineEmits(['update:visible', 'propiedad-eliminada']);

const toast = useToast();
const loading = ref(false);
const property = ref(null);

const visible = ref(props.visible);

watch(() => props.visible, (newVal) => {
    visible.value = newVal;
    if (newVal && props.idPropiedad) {
        cargarPropiedad();
    }
});

watch(visible, (newVal) => {
    emit('update:visible', newVal);
});

const cargarPropiedad = async () => {
    if (!props.idPropiedad) return;

    try {
        loading.value = true;
        const response = await axios.get(`/property/${props.idPropiedad}/show`);
        property.value = response.data;
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo cargar la información de la propiedad',
            life: 3000
        });
        cerrarModal();
    } finally {
        loading.value = false;
    }
};

const eliminarPropiedad = async () => {
    if (!props.idPropiedad) return;

    try {
        loading.value = true;
        await axios.delete(`/property/${props.idPropiedad}`);

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Propiedad eliminada correctamente',
            life: 3000
        });

        emit('propiedad-eliminada');
        cerrarModal();
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'No se pudo eliminar la propiedad',
            life: 3000
        });
    } finally {
        loading.value = false;
    }
};

const cerrarModal = () => {
    visible.value = false;
    property.value = null;
};
</script>