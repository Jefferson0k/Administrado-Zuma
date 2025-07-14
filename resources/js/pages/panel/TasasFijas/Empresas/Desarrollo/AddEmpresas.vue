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
            <!-- RUC -->
            <div>
                <label class="block font-bold mb-3">RUC <span class="text-red-500">*</span></label>
                <InputNumber v-model="empresa.ruc" :useGrouping="false" :maxlength="11" :loading="consultandoRuc"
                    @keydown.enter="consultarRuc" placeholder="Nº 12345678910" inputId="ruc" class="w-full" />
                <small>Presiona Enter para consultar</small>
            </div>

            <!-- Razón Social -->
            <div>
                <label class="block font-bold mb-3">Razón social <span class="text-red-500">*</span></label>
                <InputText v-model="empresa.razonSocial" disabled placeholder="Razón social de la empresa" class="w-full" />
            </div>

            <!-- Dirección -->
            <div>
                <label class="block font-bold mb-3">Dirección <span class="text-red-500">*</span></label>
                <InputText v-model="empresa.direccion" disabled placeholder="Dirección de la empresa" class="w-full" />
            </div>

            <!-- Actividad Económica -->
            <div>
                <label class="block font-bold mb-3">Actividad económica <span class="text-red-500">*</span></label>
                <Textarea v-model="empresa.actividadEconomica" disabled placeholder="Actividad económica" rows="3"
                    class="w-full" />
            </div>

            <!-- Estado (Dropdown) -->
            <div>
                <label class="block font-bold mb-3">Estado <span class="text-red-500">*</span></label>
                <Dropdown v-model="empresa.estado" :options="estados" placeholder="Seleccione el estado"
                    class="w-full" />
            </div>

            <!-- Tipo de empresa (Dropdown) -->
            <div>
                <label class="block font-bold mb-3">Tipo <span class="text-red-500">*</span></label>
                <Dropdown v-model="empresa.tipo" :options="tiposEntidad" placeholder="Seleccione el tipo de entidad"
                    class="w-full" />
            </div>

            <!-- Teléfono editable -->
            <div>
                <label class="block font-bold mb-3">Teléfono <span class="text-red-500">*</span></label>
                <InputText v-model="empresa.telefono" placeholder="Ingrese el teléfono" class="w-full" />
            </div>

            <!-- Email editable -->
            <div>
                <label class="block font-bold mb-3">Email <span class="text-red-500">*</span></label>
                <InputText v-model="empresa.email" placeholder="Ingrese el email" class="w-full" />
            </div>
        </div>

        <!-- Botones -->
        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog" />
            <Button label="Guardar" icon="pi pi-check" @click="guardarEmpresa" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { defineEmits } from 'vue';

// PrimeVue Components
import InputNumber from 'primevue/inputnumber';
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';

const toast = useToast();
const submitted = ref(false);
const AgregarDialog = ref(false);
const consultandoRuc = ref(false);
const emit = defineEmits(['agregado']);

// Listas
const tiposEntidad = ['banco', 'cooperativa', 'caja', 'financiera'];
const estados = ['activo', 'inactivo'];

// Modelo de datos
const empresa = ref({
    ruc: null,
    razonSocial: '',
    direccion: '',
    actividadEconomica: '',
    estado: '',
    tipo: '',
    telefono: '',
    email: ''
});

function resetEmpresa() {
    empresa.value = {
        ruc: null,
        razonSocial: '',
        direccion: '',
        actividadEconomica: '',
        estado: '',
        tipo: '',
        telefono: '',
        email: ''
    };
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
    if (!empresa.value.ruc || empresa.value.ruc.toString().length !== 11) {
        toast.add({
            severity: 'warn',
            summary: 'Advertencia',
            detail: 'Debe ingresar un RUC válido de 11 dígitos',
            life: 3000
        });
        return;
    }

    consultandoRuc.value = true;

    try {
        const response = await axios.get(`/api/consultar-ruc/${empresa.value.ruc}`);

        if (response.data) {
            const data = response.data;

            empresa.value.razonSocial = data.razonSocial || '';
            empresa.value.direccion = data.direccion || '';
            empresa.value.actividadEconomica = data.actividadEconomica || '';
            empresa.value.estado = data.estado?.toLowerCase() || 'activo'; // por defecto 'activo'

            toast.add({
                severity: 'success',
                summary: 'Éxito',
                detail: 'Datos del RUC obtenidos correctamente',
                life: 3000
            });
        }
    } catch (error) {
        console.error('Error al consultar RUC:', error);

        const mensaje = error.response?.data?.message || 'Ocurrió un error al consultar el RUC';
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: mensaje,
            life: 5000
        });

        resetEmpresa();
    } finally {
        consultandoRuc.value = false;
    }
}

function guardarEmpresa() {
    submitted.value = true;

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!empresa.value.ruc || empresa.value.ruc.toString().length !== 11 || isNaN(empresa.value.ruc)) {
        toast.add({ severity: 'warn', summary: 'RUC inválido', detail: 'Debe ingresar un RUC de 11 dígitos', life: 3000 });
        return;
    }

    if (!empresa.value.razonSocial || !empresa.value.direccion || !empresa.value.actividadEconomica || !empresa.value.estado) {
        toast.add({ severity: 'warn', summary: 'Campos incompletos', detail: 'Debe consultar el RUC primero', life: 3000 });
        return;
    }

    if (!empresa.value.tipo || !tiposEntidad.includes(empresa.value.tipo)) {
        toast.add({ severity: 'warn', summary: 'Tipo de entidad requerido', detail: 'Debe seleccionar un tipo válido', life: 3000 });
        return;
    }

    if (!empresa.value.telefono || isNaN(empresa.value.telefono)) {
        toast.add({ severity: 'warn', summary: 'Teléfono inválido', detail: 'Debe ingresar solo números', life: 3000 });
        return;
    }

    if (!empresa.value.email || !emailRegex.test(empresa.value.email)) {
        toast.add({ severity: 'warn', summary: 'Email inválido', detail: 'Debe ingresar un correo electrónico válido', life: 3000 });
        return;
    }

    // Payload con los nombres correctos
    const payload = {
        nombre: empresa.value.razonSocial,
        ruc: empresa.value.ruc.toString(),
        direccion: empresa.value.direccion,
        telefono: empresa.value.telefono.toString(),
        email: empresa.value.email,
        tipo_entidad: empresa.value.tipo,
        estado: empresa.value.estado.toLowerCase() // 'activo' o 'inactivo'
    };

    axios.post('/coperativa', payload)
        .then(() => {
            toast.add({
                severity: 'success',
                summary: 'Empresa registrada',
                detail: 'Los datos se guardaron correctamente',
                life: 3000
            });
            emit('agregado');
            hideDialog();
        })
        .catch((error) => {
            console.error('Error al guardar empresa:', error);
            const mensaje = error.response?.data?.message || 'Ocurrió un error al guardar la empresa';
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: mensaje,
                life: 5000
            });
        });
}

function showToast() {
    toast.add({
        severity: 'info',
        summary: 'Información',
        detail: 'Aún se encuentra en desarrollo',
        life: 3000
    });
}
</script>
