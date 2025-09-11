<template>
    <Toolbar class="mb-6">
        <template #start>
            <Button label="New" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" />
        </template>
        <template #end>
        </template>
    </Toolbar>

    <Dialog v-model:visible="usuarioDialog" :style="{ width: '700px' }" header="Registro de usuarios" :modal="true">
        <div class="flex flex-col gap-6">
            <!-- DNI y Estado -->
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-9">
                    <label for="dni" class="block font-bold mb-3">DNI <span class="text-red-500">*</span></label>
                    <InputText id="dni" v-model.trim="usuario.dni" required autofocus fluid
                        :invalid="(submitted && !usuario.dni) || serverErrors.dni" maxlength="8"
                        @keydown.enter="consultarusuarioPorDNI" />
                    <small v-if="submitted && !usuario.dni" class="text-red-500">El DNI es obligatorio.</small>
                    <small v-else-if="submitted && usuario.dni.length !== 8" class="text-red-500">El DNI debe tener 8
                        dígitos.</small>
                    <small v-else-if="serverErrors.dni" class="text-red-500">{{ serverErrors.dni[0] }}</small>
                </div>
                <div class="col-span-3">
                    <label for="status" class="block font-bold mb-2">Estado <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-3">
                        <Checkbox v-model="usuario.status" :binary="true" inputId="status" />
                        <Tag :value="usuario.status ? 'Con Acceso' : 'Sin Acceso'"
                            :severity="usuario.status ? 'success' : 'danger'" />
                    </div>
                    <small v-if="serverErrors.status" class="text-red-500">{{ serverErrors.status[0] }}</small>
                </div>
            </div>

            <!-- Nombres y Apellidos -->
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-6">
                    <label for="name" class="block font-bold mb-3">Nombres <span class="text-red-500">*</span></label>
                    <InputText id="name" v-model.trim="usuario.name" required maxlength="100" disabled fluid />
                    <small v-if="submitted && !usuario.name" class="text-red-500">El nombre es obligatorio.</small>
                    <small v-else-if="submitted && usuario.name && usuario.name.length < 2" class="text-red-500">El
                        nombre debe tener al menos 2 caracteres.</small>
                    <small v-else-if="serverErrors.name" class="text-red-500">{{ serverErrors.name[0] }}</small>
                </div>
                <div class="col-span-6">
                    <label for="apellidos" class="block font-bold mb-3">Apellidos <span
                            class="text-red-500">*</span></label>
                    <InputText id="apellidos" v-model.trim="usuario.apellidos" required maxlength="100" disabled fluid />
                    <small v-if="submitted && !usuario.apellidos" class="text-red-500">Los apellidos son
                        obligatorios.</small>
                    <small v-else-if="submitted && usuario.apellidos && usuario.apellidos.length < 2"
                        class="text-red-500">Los apellidos deben tener al menos 2 caracteres.</small>
                    <small v-else-if="serverErrors.apellidos" class="text-red-500">{{ serverErrors.apellidos[0] }}</small>
                </div>
            </div>



            <!-- Email y Cargo -->
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-6">
                    <label for="email" class="block font-bold mb-3">Email <span class="text-red-500">*</span></label>
                    <InputText id="email" v-model.trim="usuario.email" required maxlength="120" fluid 
                        :invalid="(submitted && !usuario.email) || serverErrors.email" />
                    <small v-if="submitted && !usuario.email" class="text-red-500">El correo electrónico es
                        obligatorio.</small>
                    <small v-else-if="submitted && usuario.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(usuario.email)"
                        class="text-red-500">El correo electrónico debe ser válido.</small>
                    <small v-else-if="serverErrors.email" class="text-red-500">{{ serverErrors.email[0] }}</small>
                </div>
                <div class="col-span-6">
                    <label for="cargo" class="block font-bold mb-3">Cargo <span class="text-red-500">*</span></label>
                    <Select v-model="usuario.cargo_id" :options="cargos" optionLabel="nombre" optionValue="id"
                        placeholder="Seleccione un cargo" fluid 
                        :invalid="(submitted && !usuario.cargo_id) || serverErrors.cargo_id" />
                    <small v-if="submitted && !usuario.cargo_id" class="text-red-500">El cargo es obligatorio.</small>
                    <small v-else-if="serverErrors.cargo_id" class="text-red-500">{{ serverErrors.cargo_id[0] }}</small>
                </div>
            </div>

            <!-- Contraseña y Rol -->
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-6">
                    <label for="password" class="block font-bold mb-3">Contraseña <span class="text-red-500">*</span></label>
                    <Password v-model="usuario.password" toggleMask placeholder="contraseña" fluid :feedback="false"
                        inputId="password" :invalid="(submitted && !usuario.password) || serverErrors.password" />
                    <small v-if="submitted && !usuario.password" class="text-red-500">La Contraseña es obligatoria.</small>
                    <small v-else-if="submitted && usuario.password && usuario.password.length < 8"
                        class="text-red-500">La Contraseña debe tener al menos 8 caracteres.</small>
                    <small v-else-if="serverErrors.password" class="text-red-500">{{ serverErrors.password[0] }}</small>
                </div>
                <div class="col-span-6">
                    <label for="role" class="block font-bold mb-3">Rol <span class="text-red-500">*</span></label>
                    <Select v-model="usuario.role_id" :options="roles" optionLabel="name" optionValue="id"
                        placeholder="Seleccione un rol" fluid 
                        :invalid="(submitted && !usuario.role_id) || serverErrors.role_id" />
                    <small v-if="submitted && !usuario.role_id" class="text-red-500">El rol es obligatorio.</small>
                    <small v-else-if="serverErrors.role_id" class="text-red-500">{{ serverErrors.role_id[0] }}</small>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between items-center w-full">
                <small class="text-gray-600">Los campos marcados con <span class="text-red-500">*</span> son obligatorios</small>
                <div class="flex gap-2">
                    <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog" />
                    <Button label="Guardar" icon="pi pi-check" @click="guardarUsuario" />
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';
import Password from 'primevue/password';

import Select from 'primevue/select';
import { useToast } from 'primevue/usetoast';
import { defineEmits } from 'vue';

const toast = useToast();
const roles = ref([]);
const cargos = ref([]);
const submitted = ref(false);
const usuarioDialog = ref(false);
const serverErrors = ref({});
const emit = defineEmits(['usuario-agregado']);

const usuario = ref({
    dni: '',
    name: '',
    apellidos: '',
    email: '',
    password: '',
    status: true,
    cargo_id: null,
    role_id: null,
});

function openNew() {
    // Limpiar el formulario al abrir el diálogo
    usuario.value = {
        dni: '',
        name: '',
        apellidos: '',
        email: '',
        password: '',
        status: true,
        cargo_id: null,
        role_id: null,
    };
    submitted.value = false;
    serverErrors.value = {};
    usuarioDialog.value = true;
}

function hideDialog() {
    usuarioDialog.value = false;
    submitted.value = false;
    serverErrors.value = {};
}

function consultarusuarioPorDNI() {
    const dni = usuario.value.dni;
    if (dni && dni.length === 8) {
        axios.get(`/api/consultar-dni/${dni}`)
            .then(response => {
                const data = response.data;
                if (data.success && data.data) {
                    // Asignar los datos según la estructura real del JSON
                    const nombres = data.data.nombres || '';
                    const apellido_paterno = data.data.apellido_paterno || '';
                    const apellido_materno = data.data.apellido_materno || '';

                    usuario.value.name = nombres;
                    usuario.value.apellidos = `${apellido_paterno} ${apellido_materno}`.trim();
                    
                    toast.add({ 
                        severity: 'success', 
                        summary: 'Datos encontrados', 
                        detail: 'Información cargada correctamente', 
                        life: 3000 
                    });
                } else {
                    toast.add({ 
                        severity: 'warn', 
                        summary: 'No encontrado', 
                        detail: 'No se encontraron datos para este DNI', 
                        life: 3000 
                    });
                }
            })
            .catch(error => {
                console.error('Error al consultar DNI:', error);
                toast.add({ 
                    severity: 'error', 
                    summary: 'Error', 
                    detail: 'No se pudo consultar el DNI', 
                    life: 3000 
                });
            });
    } else {
        toast.add({ 
            severity: 'warn', 
            summary: 'DNI incompleto', 
            detail: 'El DNI debe tener 8 dígitos', 
            life: 3000 
        });
    }
}

function guardarUsuario() {
    submitted.value = true;
    serverErrors.value = {};

    // Validación básica antes de enviar
    if (!usuario.value.dni || usuario.value.dni.length !== 8) {
        return;
    }
    if (!usuario.value.name) {
        return;
    }
    if (!usuario.value.apellidos) {
        return;
    }
    if (!usuario.value.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(usuario.value.email)) {
        return;
    }
    if (!usuario.value.password || usuario.value.password.length < 8) {
        return;
    }
    if (!usuario.value.cargo_id) {
        return;
    }
    if (!usuario.value.role_id) {
        return;
    }

    // Enviar datos directamente sin formatear fecha
    axios.post('/usuarios', usuario.value)
        .then(response => {
            toast.add({ 
                severity: 'success', 
                summary: 'Éxito', 
                detail: 'Usuario registrado correctamente', 
                life: 3000 
            });
            hideDialog();
            emit('usuario-agregado');
        })
        .catch(error => {
            if (error.response && error.response.status === 422) {
                const errors = error.response.data.errors || {};
                serverErrors.value = {
                    dni: errors.dni,
                    name: errors.name,
                    apellidos: errors.apellidos,
                    email: errors.email,
                    password: errors.password,
                    cargo_id: errors.cargo_id,
                    role_id: errors.role_id,
                    status: errors.status
                };
                toast.add({ 
                    severity: 'error', 
                    summary: 'Error de validación', 
                    detail: 'Verifique los datos ingresados', 
                    life: 3000 
                });
            } else {
                toast.add({ 
                    severity: 'error', 
                    summary: 'Error', 
                    detail: 'No se pudo registrar el usuario', 
                    life: 3000 
                });
            }
        });
}

onMounted(() => {
    // Cargar roles
    axios.get('/rol')
        .then(response => {
            roles.value = response.data.data || [];
        })
        .catch(error => {
            console.error('Error al cargar roles:', error);
            toast.add({ 
                severity: 'error', 
                summary: 'Error', 
                detail: 'No se pudieron cargar los roles', 
                life: 3000 
            });
        });

    // Cargar cargos
    axios.get('/cargos')
        .then(response => {
            cargos.value = response.data.data || [];
        })
        .catch(error => {
            console.error('Error al cargar cargos:', error);
            toast.add({ 
                severity: 'error', 
                summary: 'Error', 
                detail: 'No se pudieron cargar los cargos', 
                life: 3000 
            });
        });
});
</script>