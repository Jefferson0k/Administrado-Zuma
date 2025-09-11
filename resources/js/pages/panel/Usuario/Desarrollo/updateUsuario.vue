<script setup>
import { ref, watch, onMounted } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import Tag from 'primevue/tag';
import Checkbox from 'primevue/checkbox';
import Password from 'primevue/password';
import Select from 'primevue/select';

const props = defineProps({
    visible: Boolean,
    UsuarioId: Number
});
const emit = defineEmits(['update:visible', 'updated']);

const serverErrors = ref({});
const submitted = ref(false);
const toast = useToast();
const user = ref({});
const password = ref('');
const loading = ref(false);
const originalEmail = ref('');
const roles = ref([]);
const cargos = ref([]);

const dialogVisible = ref(props.visible);
watch(() => props.visible, (val) => dialogVisible.value = val);
watch(dialogVisible, (val) => emit('update:visible', val));

watch(() => props.visible, (newVal) => {
    if (newVal && props.UsuarioId) {
        fetchUser();
    }
});

const fetchUser = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/usuarios/${props.UsuarioId}`);
        user.value = response.data.user;
        originalEmail.value = response.data.user.email;
        user.value.status = response.data.user.status === true ||
            response.data.user.status === 1 ||
            response.data.user.status === 'activo' ? true : false;
        
        // Ensure role_id is properly converted to number
        if (user.value.role_id) {
            user.value.role_id = Number(user.value.role_id);
        }
        
        // Ensure cargo_id is properly converted to number
        if (user.value.cargo_id) {
            user.value.cargo_id = Number(user.value.cargo_id);
        }
        
        password.value = '';
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar el usuario', life: 3000 });
        console.error(error);
    } finally {
        loading.value = false;
    }
};

const updateUser = async () => {
    submitted.value = true;
    serverErrors.value = {};

    try {
        const statusValue = user.value.status === true;
        const userData = {
            dni: user.value.dni,
            name: user.value.name,
            apellidos: user.value.apellidos,
            email: user.value.email,
            status: statusValue,
            role_id: user.value.role_id,
            cargo_id: user.value.cargo_id,
        };

        if (password.value && password.value.trim() !== '') {
            userData.password = password.value;
        }

        await axios.put(`/usuarios/${props.UsuarioId}`, userData);

        toast.add({
            severity: 'success',
            summary: 'Actualizado',
            detail: 'Usuario actualizado correctamente',
            life: 3000
        });

        dialogVisible.value = false;
        emit('updated');
    } catch (error) {
        if (error.response && error.response.data && error.response.data.errors) {
            serverErrors.value = error.response.data.errors;
            toast.add({
                severity: 'error',
                summary: 'Error de validación',
                detail: 'Revisa los campos e intenta nuevamente.',
                life: 5000
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se pudo actualizar el usuario',
                life: 3000
            });
        }
        console.error(error);
    }
};

const consultarusuarioPorDNI = async () => {
    if (user.value.dni.length !== 8) {
        toast.add({
            severity: 'warn',
            summary: 'Advertencia',
            detail: 'El DNI debe tener 8 dígitos.',
            life: 3000
        })
        return
    }

    try {
        const response = await axios.get(`/api/consultar-dni/${user.value.dni}`)
        const data = response.data

        if (data.success && data.data) {
            const nombres = data.data.nombres || '';
            const apellido_paterno = data.data.apellido_paterno || '';
            const apellido_materno = data.data.apellido_materno || '';

            user.value.name = nombres;
            user.value.apellidos = `${apellido_paterno} ${apellido_materno}`.trim();
            
            toast.add({ 
                severity: 'success', 
                summary: 'Datos encontrados', 
                detail: 'Información cargada correctamente', 
                life: 3000 
            });
        } else {
            toast.add({
                severity: 'warn',
                summary: 'Advertencia',
                detail: 'No se encontraron datos para este DNI.',
                life: 3000
            })
        }
    } catch (error) {
        console.error(error)
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo consultar el DNI.',
            life: 3000
        })
    }
}

onMounted(() => {
    // Cargar roles
    axios.get('/rol')
        .then(response => {
            roles.value = response.data.data;
            // If we already have a user loaded, ensure role_id is a number
            if (user.value && user.value.role_id) {
                user.value.role_id = Number(user.value.role_id);
            }
        })
        .catch(() => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar los roles', life: 3000 });
        });

    // Cargar cargos
    axios.get('/cargos')
        .then(response => {
            cargos.value = response.data.data || [];
            // If we already have a user loaded, ensure cargo_id is a number
            if (user.value && user.value.cargo_id) {
                user.value.cargo_id = Number(user.value.cargo_id);
            }
        })
        .catch(() => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar los cargos', life: 3000 });
        });
});
</script>

<template>
    <Dialog v-model:visible="dialogVisible" header="Editar Usuario" modal :closable="true" :closeOnEscape="true"
        :style="{ width: '700px' }">
        <div class="flex flex-col gap-6">
            <!-- DNI y Estado -->
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-9">
                    <label for="dni" class="block font-bold mb-3">DNI <span class="text-red-500">*</span></label>
                    <InputText v-model="user.dni" maxlength="8" required @keydown.enter="consultarusuarioPorDNI" fluid />
                    <small v-if="serverErrors.dni" class="text-red-500">{{ serverErrors.dni[0] }}</small>
                </div>
                <div class="col-span-3">
                    <label for="status" class="block font-bold mb-2">Estado <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-3">
                        <Checkbox v-model="user.status" :binary="true" inputId="status" />
                        <Tag :value="user.status ? 'Con Acceso' : 'Sin Acceso'"
                            :severity="user.status ? 'success' : 'danger'" />
                    </div>
                    <small v-if="serverErrors.status" class="text-red-500">{{ serverErrors.status[0] }}</small>
                </div>
            </div>

            <!-- Nombres y Apellidos -->
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-6">
                    <label for="name" class="block font-bold mb-3">Nombres <span class="text-red-500">*</span></label>
                    <InputText v-model="user.name" required disabled maxlength="100" fluid />
                    <small v-if="serverErrors.name" class="text-red-500">{{ serverErrors.name[0] }}</small>
                </div>
                <div class="col-span-6">
                    <label for="apellidos" class="block font-bold mb-3">Apellidos <span class="text-red-500">*</span></label>
                    <InputText v-model="user.apellidos" required disabled maxlength="100" fluid />
                    <small v-if="serverErrors.apellidos" class="text-red-500">{{ serverErrors.apellidos[0] }}</small>
                </div>
            </div>

            <!-- Email y Cargo -->
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-6">
                    <label for="email" class="block font-bold mb-3">Email <span class="text-red-500">*</span></label>
                    <InputText v-model="user.email" maxlength="120" fluid />
                    <small v-if="serverErrors.email" class="text-red-500">{{ serverErrors.email[0] }}</small>
                </div>
                <div class="col-span-6">
                    <label for="cargo" class="block font-bold mb-3">Cargo <span class="text-red-500">*</span></label>
                    <Select v-model="user.cargo_id" :options="cargos" optionLabel="nombre" optionValue="id"
                        placeholder="Seleccione un cargo" fluid />
                    <small v-if="submitted && !user.cargo_id" class="text-red-500">El cargo es obligatorio.</small>
                    <small v-else-if="serverErrors.cargo_id" class="text-red-500">{{ serverErrors.cargo_id[0] }}</small>
                </div>
            </div>

            <!-- Contraseña y Rol -->
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-6">
                    <label for="password" class="block font-bold mb-3">Contraseña <small>(Dejar vacío para mantener la actual)</small></label>
                    <Password v-model="password" toggleMask placeholder="Nueva contraseña" :feedback="false"
                        inputId="password" fluid />
                    <small v-if="serverErrors.password" class="text-red-500">{{ serverErrors.password[0] }}</small>
                </div>
                <div class="col-span-6">
                    <label for="role" class="block font-bold mb-3">Rol <span class="text-red-500">*</span></label>
                    <Select v-model="user.role_id" :options="roles" optionLabel="name" optionValue="id"
                        placeholder="Seleccione un rol" fluid />
                    <small v-if="submitted && !user.role_id" class="text-red-500">El rol es obligatorio.</small>
                    <small v-else-if="serverErrors.role_id" class="text-red-500">{{ serverErrors.role_id[0] }}</small>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between items-center w-full">
                <small class="text-gray-600">Los campos marcados con <span class="text-red-500">*</span> son obligatorios</small>
                <div class="flex gap-2">
                    <Button label="Cancelar" icon="pi pi-times" text @click="dialogVisible = false" />
                    <Button label="Guardar" icon="pi pi-check" @click="updateUser" :loading="loading" />
                </div>
            </div>
        </template>
    </Dialog>
</template>