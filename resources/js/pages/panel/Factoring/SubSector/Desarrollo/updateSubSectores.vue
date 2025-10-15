<script setup>
import { ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    visible: {
        type: Boolean,
        required: true,
    },
    subsector: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['update:visible', 'subsector-updated']);

const toast = useToast();

const localVisible = ref(props.visible);
const loading = ref(false);
const formData = ref({
    name: '',
});

const errors = ref({});

watch(() => props.visible, (newVal) => {
    localVisible.value = newVal;
    if (newVal && props.subsector) {
        formData.value.name = props.subsector.name;
        errors.value = {};
    }
});

watch(localVisible, (newVal) => {
    emit('update:visible', newVal);
});

const closeDialog = () => {
    localVisible.value = false;
    formData.value.name = '';
    errors.value = {};
};

const validateForm = () => {
    errors.value = {};
    let isValid = true;

    if (!formData.value.name || formData.value.name.trim() === '') {
        errors.value.name = 'El nombre es obligatorio';
        isValid = false;
    } else if (formData.value.name.length < 3) {
        errors.value.name = 'El nombre debe tener al menos 3 caracteres';
        isValid = false;
    } else if (formData.value.name.length > 100) {
        errors.value.name = 'El nombre no puede exceder 100 caracteres';
        isValid = false;
    }

    return isValid;
};

const updateSubSector = async () => {
    if (!validateForm()) {
        return;
    }

    loading.value = true;
    try {
        await axios.put(`/subsectors/${props.subsector.id}`, {
            name: formData.value.name.trim(),
        });

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Subsector actualizado correctamente',
            life: 3000
        });

        emit('subsector-updated');
        closeDialog();
    } catch (error) {
        console.error('Error al actualizar subsector:', error);
        
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
            toast.add({
                severity: 'error',
                summary: 'Error de validación',
                detail: 'Por favor, verifica los datos ingresados',
                life: 3000
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response?.data?.message || 'No se pudo actualizar el subsector',
                life: 3000
            });
        }
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <Dialog v-model:visible="localVisible" modal header="Actualizar Subsector" :style="{ width: '30rem' }"
        :closable="!loading" @hide="closeDialog">
        <div class="flex flex-col gap-4 py-4">
            <div class="flex flex-col gap-2">
                <label for="name" class="font-semibold">
                    Nombre <span class="text-red-500">*</span>
                </label>
                <InputText 
                    id="name" 
                    v-model="formData.name" 
                    :disabled="loading"
                    :class="{ 'p-invalid': errors.name }"
                    placeholder="Ingrese el nombre del subsector"
                    autofocus
                />
                <small v-if="errors.name" class="text-red-500">
                    {{ errors.name }}
                </small>
            </div>
        </div>

        <template #footer>
            <Button 
                label="Cancelar" 
                severity="secondary" 
                @click="closeDialog" 
                :disabled="loading"
            />
            <Button 
                label="Actualizar" 
                @click="updateSubSector" 
                :loading="loading"
            />
        </template>
    </Dialog>
</template>