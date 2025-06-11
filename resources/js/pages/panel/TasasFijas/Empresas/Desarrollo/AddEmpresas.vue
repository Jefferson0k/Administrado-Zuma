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

    <Dialog v-model:visible="AgregarDialog" :style="{ width: '450px' }" header="Registro de Empresas" :modal="true">
        <div class="flex flex-col gap-6">
            <div>
                <label for="ruc" class="block font-bold mb-3">RUC <span class="text-red-500">*</span></label>
                <InputNumber v-model="empresa.ruc" inputId="ruc" :useGrouping="false" fluid placeholder="Nº 12345678910"
                    :maxlength="11" :loading="consultandoRuc" @keydown.enter="consultarRuc" />
                <small>Presiona Enter para consultar</small>
            </div>
            <div>
                <label for="razonSocial" class="block font-bold mb-3">Razón social <span
                        class="text-red-500">*</span></label>
                <InputText type="text" v-model="empresa.razonSocial" inputId="razonSocial" fluid
                    placeholder="Razón social de la empresa" disabled />
            </div>
            <div>
                <label for="actividadEconomica" class="block font-bold mb-3">Actividad económica <span
                        class="text-red-500">*</span></label>
                <Textarea v-model="empresa.actividadEconomica" inputId="actividadEconomica" fluid
                    placeholder="Actividad económica de la empresa" disabled rows="3" />
            </div>
            <div>
                <label for="estado" class="block font-bold mb-3">Estado <span class="text-red-500">*</span></label>
                <InputText type="text" v-model="empresa.estado" inputId="estado" fluid
                    placeholder="Estado de la empresa" disabled />
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
import InputNumber from 'primevue/inputnumber';
import axios from 'axios';
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { defineEmits } from 'vue';

const toast = useToast();
const submitted = ref(false);
const AgregarDialog = ref(false);
const serverErrors = ref({});
const consultandoRuc = ref(false);
const emit = defineEmits(['agregado']);

const empresa = ref({
    ruc: null,
    razonSocial: '',
    actividadEconomica: '',
    estado: ''
});

function resetEmpresa() {
    empresa.value = {
        ruc: null,
        razonSocial: '',
        actividadEconomica: '',
        estado: ''
    };
    serverErrors.value = {};
    submitted.value = false;
}

function openNew() {
    resetEmpresa();
    AgregarDialog.value = true;
}

function hideDialog() {
    AgregarDialog.value = false;
    resetEmpresa();
}

async function consultarRuc() {
    if (!empresa.value.ruc) {
        toast.add({
            severity: 'warn',
            summary: 'Advertencia',
            detail: 'Debe ingresar un RUC',
            life: 3000
        });
        return;
    }

    if (empresa.value.ruc.toString().length !== 11) {
        toast.add({
            severity: 'warn',
            summary: 'Advertencia',
            detail: 'El RUC debe tener 11 dígitos',
            life: 3000
        });
        return;
    }

    consultandoRuc.value = true;

    try {
        const response = await axios.get(`/api/consultar-ruc/${empresa.value.ruc}`);

        if (response.data) {
            // Rellenar los campos con los datos obtenidos
            empresa.value.razonSocial = response.data.razonSocial || '';
            empresa.value.actividadEconomica = response.data.actividadEconomica || '';
            empresa.value.estado = response.data.estado || '';

            toast.add({
                severity: 'success',
                summary: 'Éxito',
                detail: 'Datos del RUC obtenidos correctamente',
                life: 3000
            });
        }
    } catch (error) {
        console.error('Error al consultar RUC:', error);

        let errorMessage = 'Error al consultar el RUC';

        if (error.response) {
            // Error del servidor
            if (error.response.status === 404) {
                errorMessage = 'RUC no encontrado';
            } else if (error.response.status === 500) {
                errorMessage = 'Error interno del servidor';
            } else {
                errorMessage = error.response.data?.message || errorMessage;
            }
        } else if (error.request) {
            // Error de red
            errorMessage = 'Error de conexión. Verifique su conexión a internet';
        }

        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage,
            life: 5000
        });

        // Limpiar los campos en caso de error
        empresa.value.razonSocial = '';
        empresa.value.actividadEconomica = '';
        empresa.value.estado = '';
    } finally {
        consultandoRuc.value = false;
    }
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