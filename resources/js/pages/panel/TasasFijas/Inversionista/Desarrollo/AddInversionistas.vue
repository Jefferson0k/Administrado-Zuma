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

    <Dialog v-model:visible="AgregarDialog" :style="{ width: '450px' }" header="Registro de Inversionistas"
        :modal="true">
        <div class="flex flex-col gap-6">
            <div>
                <label for="dni" class="block font-bold mb-3">DNI <span class="text-red-500">*</span></label>
                <InputNumber v-model="inversionistas.dni" inputId="dni" :useGrouping="false" fluid
                    placeholder="Nº 12345678" :maxlength="8" :loading="consultandoDni" @keydown.enter="consultarDni"
                    :disabled="consultandoDni" />
                <small>Presiona Enter para consultar</small>
            </div>

            <div>
                <label for="nombres" class="block font-bold mb-3">Nombres <span class="text-red-500">*</span></label>
                <InputText v-model="inversionistas.nombres" inputId="nombres" fluid placeholder="Nombres completos"
                    :readonly="true" disabled />
            </div>

            <div>
                <label for="apellidos" class="block font-bold mb-3">Apellidos <span
                        class="text-red-500">*</span></label>
                <InputText v-model="inversionistas.apellidos" inputId="apellidos" fluid
                    placeholder="Apellidos completos" :readonly="true" disabled />
            </div>

            <div>
                <label for="email" class="block font-bold mb-3">Email <span class="text-red-500">*</span></label>
                <InputText v-model="inversionistas.email" inputId="email" fluid type="email"
                    placeholder="correo@ejemplo.com" />
            </div>

            <div>
                <label for="celular" class="block font-bold mb-3">Celular <span class="text-red-500">*</span></label>
                <InputText v-model="inversionistas.celular" inputId="celular" fluid placeholder="999 999 999"
                    maxlength="15" />
            </div>

            <div>
                <label for="operador" class="block font-bold mb-3">Operador <span class="text-red-500">*</span></label>
                <Select v-model="inversionistas.operador" inputId="operador" :options="operadores"
                    optionLabel="nombre" optionValue="codigo" placeholder="Selecciona un operador" fluid
                    class="w-full" />
            </div>
        </div>

        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog" />
            <Button label="Guardar" icon="pi pi-check" @click="showToast"/>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, computed } from 'vue';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import axios from 'axios';
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import { useToast } from 'primevue/usetoast';
import { defineEmits } from 'vue';
import Select from 'primevue/select';

const toast = useToast();
const AgregarDialog = ref(false);
const consultandoDni = ref(false);
const emit = defineEmits(['agregado']);

// Modelo de datos del inversionista
const inversionistas = ref({
    dni: null,
    nombres: '',
    apellidos: '',
    email: '',
    celular: '',
    operador: null
});

// Operadores móviles de Perú
const operadores = ref([
    { codigo: 'movistar', nombre: 'Movistar' },
    { codigo: 'claro', nombre: 'Claro' },
    { codigo: 'entel', nombre: 'Entel' },
    { codigo: 'bitel', nombre: 'Bitel' },
    { codigo: 'virgin', nombre: 'Virgin Mobile' }
]);

// Validación del formulario
const isFormValid = computed(() => {
    return inversionistas.value.dni &&
        inversionistas.value.nombres.trim() &&
        inversionistas.value.apellidos.trim() &&
        inversionistas.value.email.trim() &&
        inversionistas.value.celular.trim() &&
        inversionistas.value.operador;
});

function openNew() {
    // Limpiar formulario
    inversionistas.value = {
        dni: null,
        nombres: '',
        apellidos: '',
        email: '',
        celular: '',
        operador: null
    };
    AgregarDialog.value = true;
}

function hideDialog() {
    AgregarDialog.value = false;
}

async function consultarDni() {
    if (!inversionistas.value.dni || inversionistas.value.dni.toString().length !== 8) {
        toast.add({
            severity: 'warn',
            summary: 'Advertencia',
            detail: 'Ingresa un DNI válido de 8 dígitos',
            life: 3000
        });
        return;
    }

    consultandoDni.value = true;

    try {
        const response = await axios.get(`/api/consultar-dni/${inversionistas.value.dni}`);

        if (response.data.success) {
            const data = response.data.data;

            // Llenar campos automáticamente
            inversionistas.value.nombres = data.nombres || '';
            inversionistas.value.apellidos = `${data.apellido_paterno || ''} ${data.apellido_materno || ''}`.trim();

            toast.add({
                severity: 'success',
                summary: 'Éxito',
                detail: 'Datos del DNI consultados correctamente',
                life: 3000
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se pudieron obtener los datos del DNI',
                life: 3000
            });
        }
    } catch (error) {
        console.error('Error al consultar DNI:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error al consultar la API de DNI',
            life: 3000
        });
    } finally {
        consultandoDni.value = false;
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