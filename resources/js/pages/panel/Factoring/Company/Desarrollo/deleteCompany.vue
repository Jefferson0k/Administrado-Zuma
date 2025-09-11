<template>
    <Dialog :visible="visible" @update:visible="$emit('update:visible', $event)" :style="{ width: '32rem' }" header="Confirmar Eliminación" :modal="true">
        <div class="flex items-center gap-3 mb-4">
            <i class="pi pi-exclamation-triangle !text-3xl" />
            <span v-if="company">
                ¿Está seguro de que desea eliminar la empresa 
                <b>{{ company.business_name }}</b>?
            </span>
        </div>
        <template #footer>
            <Button label="No" icon="pi pi-times" text @click="closeDialog" severity="secondary" variant="text" />
            <Button label="Eliminar" severity="danger" @click="confirmDelete" :loading="deleting" />
        </template>
    </Dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';

interface Company {
    id: number;
    document: string;
    business_name: string;
    name?: string;
    risk: number;
    sectornom: string;
    subsectornom?: string;
    creacion: string;
}

interface Props {
    visible: boolean;
    company: Company | null;
}

interface Emits {
    (e: 'update:visible', value: boolean): void;
    (e: 'deleted'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const toast = useToast();
const deleting = ref(false);

const closeDialog = () => {
    emit('update:visible', false);
};

const confirmDelete = async () => {
    if (!props.company) return;
    
    deleting.value = true;
    
    try {
        await axios.delete(`/companies/${props.company.id}`);
        
        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Empresa eliminada correctamente',
            life: 3000
        });
        
        emit('deleted');
        closeDialog();
        
    } catch (error: any) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Error al eliminar la empresa',
            life: 5000
        });
    } finally {
        deleting.value = false;
    }
};
</script>