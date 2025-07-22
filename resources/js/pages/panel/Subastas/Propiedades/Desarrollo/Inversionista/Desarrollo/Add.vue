<template>
    <Toast />

    <Toolbar class="mb-6">
        <template #start>
            <Button label="Nuevo" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openDialog" />
            <Button label="Eliminar" icon="pi pi-trash" severity="secondary" @click="showToast" />
        </template>
        <template #end>
            <Button label="Exportar" icon="pi pi-upload" severity="secondary" @click="showToast" />
        </template>
    </Toolbar>

    <Dialog v-model:visible="visible" maximizable modal header="Información del Inversionista"
        :style="{ width: '60rem' }" :breakpoints="{ '1199px': '85vw', '575px': '95vw' }">
        <div class="flex flex-col gap-6">
            <!-- Selección de Propiedad -->
            <div class="field">
                <label class="font-semibold text-900 mb-2 block">
                    Propiedad <span class="text-red-500">*</span>
                </label>
                <Select 
                    v-model="propiedadSeleccionada" 
                    :options="propiedades" 
                    optionLabel="label" 
                    showClear 
                    editable
                    class="w-full" 
                    placeholder="Buscar propiedad..." 
                    @update:modelValue="onPropiedadChange"
                >
                    <template #value="{ value, placeholder }">
                        <span v-if="value" class="font-medium">{{ value.label }}</span>
                        <span v-else class="text-500">{{ placeholder }}</span>
                    </template>
                    <template #option="{ option }">
                        <div class="flex justify-content-between align-items-start w-full">
                            <div class="flex-1">
                                <div class="font-semibold text-900">{{ option.nombre }}</div>
                                <div class="text-sm text-600 mt-1">
                                    📍 {{ option.departamento }}, {{ option.provincia }}, {{ option.distrito }}
                                </div>
                                <div class="text-sm text-500">{{ option.direccion }}</div>
                                <div class="text-sm text-primary font-medium mt-1">
                                    💰 S/. {{ parseFloat(option.valor_estimado).toLocaleString() }}
                                </div>
                            </div>
                            <Tag
                                :value="option.estado_property"
                                :severity="getEstadoSeverity(option.estado_property)"
                                class="ml-2"
                            />
                        </div>
                    </template>
                    <template #empty>
                        <div class="text-center p-3 text-500">
                            <i class="pi pi-search mb-2"></i>
                            <div>Propiedad no encontrada</div>
                        </div>
                    </template>
                </Select>
            </div>

            <!-- Información del Cliente Vinculado (Solo lectura) -->
            <div v-if="clienteVinculado" class="field">
                <label class="font-semibold text-900 mb-2 block">Cliente Vinculado</label>
                <div class="p-3 bg-blue-50 border-round border-1 border-blue-200">
                    <div class="flex align-items-center gap-3">
                        <i class="pi pi-user text-blue-600 text-xl"></i>
                        <div>
                            <div class="font-semibold text-900">
                                {{ clienteVinculado.investor_name }} {{ clienteVinculado.investor_first_last_name }} {{ clienteVinculado.investor_second_last_name }}
                            </div>
                            <div class="text-sm text-600">DNI: {{ clienteVinculado.investor_document }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campos del formulario organizados en grid -->
            <div class="grid">
                <!-- Ocupación/Profesión -->
                <div class="col-12 md:col-6 field">
                    <label class="font-semibold text-900 mb-2 block">
                        Ocupación/Profesión <span class="text-red-500">*</span>
                    </label>
                    <Textarea 
                        v-model="form.ocupacion_profesion" 
                        autoResize 
                        rows="2" 
                        class="w-full" 
                        :maxlength="200"
                        placeholder="Ej: Ingeniero Civil, Comerciante, Profesional independiente..."
                    />
                    <small class="text-500">{{ form.ocupacion_profesion.length }}/200 caracteres</small>
                </div>

                <!-- Empresa Tasadora -->
                <div class="col-12 md:col-6 field">
                    <label class="font-semibold text-900 mb-2 block">
                        Empresa Tasadora <span class="text-red-500">*</span>
                    </label>
                    <Textarea 
                        v-model="form.empresa_tasadora" 
                        autoResize 
                        rows="2" 
                        class="w-full" 
                        :maxlength="150"
                        placeholder="Nombre de la empresa que realizó la tasación..."
                    />
                    <small class="text-500">{{ form.empresa_tasadora.length }}/150 caracteres</small>
                </div>

                <!-- Motivo del Préstamo -->
                <div class="col-12 field">
                    <label class="font-semibold text-900 mb-2 block">
                        Motivo del Préstamo <span class="text-red-500">*</span>
                    </label>
                    <Textarea 
                        v-model="form.motivo_prestamo" 
                        autoResize 
                        rows="2" 
                        class="w-full" 
                        :maxlength="300"
                        placeholder="Describe el motivo principal para solicitar el préstamo..."
                    />
                    <small class="text-500">{{ form.motivo_prestamo.length }}/300 caracteres</small>
                </div>

                <!-- Descripción del Financiamiento -->
                <div class="col-12 field">
                    <label class="font-semibold text-900 mb-2 block">
                        Descripción del Financiamiento <span class="text-red-500">*</span>
                    </label>
                    <Textarea 
                        v-model="form.descripcion_financiamiento" 
                        autoResize 
                        rows="3" 
                        class="w-full" 
                        :maxlength="500"
                        placeholder="Detalla las características del financiamiento solicitado..."
                    />
                    <small class="text-500">{{ form.descripcion_financiamiento.length }}/500 caracteres</small>
                </div>

                <!-- Solicitud del Préstamo para -->
                <div class="col-12 md:col-6 field">
                    <label class="font-semibold text-900 mb-2 block">
                        Solicitud del Préstamo para <span class="text-red-500">*</span>
                    </label>
                    <Textarea 
                        v-model="form.solicitud_prestamo_para" 
                        autoResize 
                        rows="2" 
                        class="w-full" 
                        :maxlength="250"
                        placeholder="Especifica el destino o uso del préstamo..."
                    />
                    <small class="text-500">{{ form.solicitud_prestamo_para.length }}/250 caracteres</small>
                </div>

                <!-- Garantía -->
                <div class="col-12 md:col-6 field">
                    <label class="font-semibold text-900 mb-2 block">
                        Garantía <span class="text-red-500">*</span>
                    </label>
                    <Textarea 
                        v-model="form.garantia" 
                        autoResize 
                        rows="2" 
                        class="w-full" 
                        :maxlength="250"
                        placeholder="Describe la garantía ofrecida para el préstamo..."
                    />
                    <small class="text-500">{{ form.garantia.length }}/250 caracteres</small>
                </div>

                <!-- Perfil del Riesgo -->
                <div class="col-12 field">
                    <label class="font-semibold text-900 mb-2 block">
                        Perfil del Riesgo <span class="text-red-500">*</span>
                    </label>
                    <Textarea 
                        v-model="form.perfil_riesgo" 
                        autoResize 
                        rows="3" 
                        class="w-full" 
                        :maxlength="400"
                        placeholder="Evalúa y describe el perfil de riesgo del solicitante..."
                    />
                    <small class="text-500">{{ form.perfil_riesgo.length }}/400 caracteres</small>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-content-end gap-2">
                <Button 
                    label="Cancelar" 
                    icon="pi pi-times" 
                    severity="secondary" 
                    text 
                    @click="cerrarDialog" 
                />
                <Button 
                    label="Guardar" 
                    icon="pi pi-check" 
                    severity="primary" 
                    @click="guardarFormulario"
                    :disabled="!formularioValido"
                />
            </div>
        </template>
    </Dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import Toolbar from 'primevue/toolbar'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'
import { debounce } from 'lodash'
import axios from 'axios'

const toast = useToast()
const visible = ref(false)

const propiedadSeleccionada = ref(null)
const propiedades = ref([])
const clienteVinculado = ref(null)

const form = ref({
    ocupacion_profesion: '',
    empresa_tasadora: '',
    motivo_prestamo: '',
    descripcion_financiamiento: '',
    solicitud_prestamo_para: '',
    garantia: '',
    perfil_riesgo: ''
})

// Computed para validar si el formulario está completo
const formularioValido = computed(() => {
    return propiedadSeleccionada.value && 
           form.value.ocupacion_profesion.trim() &&
           form.value.empresa_tasadora.trim() &&
           form.value.motivo_prestamo.trim() &&
           form.value.descripcion_financiamiento.trim() &&
           form.value.solicitud_prestamo_para.trim() &&
           form.value.garantia.trim() &&
           form.value.perfil_riesgo.trim()
})

const openDialog = () => {
    visible.value = true
}

const cerrarDialog = () => {
    visible.value = false
    resetForm()
}

const showToast = () => {
    toast.add({
        severity: 'info',
        summary: 'Información',
        detail: 'Aún se encuentra en desarrollo',
        life: 3000
    })
}

const onPropiedadChange = (valor) => {
    if (typeof valor === 'string') {
        buscarPropiedades(valor)
        clienteVinculado.value = null
    } else if (valor) {
        // Cuando se selecciona una propiedad, establecer el cliente vinculado
        clienteVinculado.value = {
            cliente_id: valor.cliente_id,
            investor_name: valor.investor_name,
            investor_first_last_name: valor.investor_first_last_name,
            investor_second_last_name: valor.investor_second_last_name,
            investor_document: valor.investor_document
        }
    } else {
        clienteVinculado.value = null
    }
}

const getEstadoSeverity = (estado) => {
    switch (estado) {
        case 'completo':
        case 'activa':
            return 'success'
        case 'pendiente':
            return 'warning'
        case 'desactivada':
            return 'danger'
        case 'subastada':
            return 'info'
        default:
            return 'secondary'
    }
}

const buscarPropiedades = debounce(async (texto) => {
    if (!texto || texto.length < 2) {
        propiedades.value = []
        return
    }

    try {
        const response = await axios.get("/propiedades/activas", {
            params: { search: texto },
        })

        propiedades.value = response.data.data.map((propiedad) => ({
            label: `${propiedad.nombre} - ${propiedad.departamento}, ${propiedad.provincia}`,
            value: propiedad.property_id,
            config_id: propiedad.config_id,
            nombre: propiedad.nombre,
            departamento: propiedad.departamento,
            provincia: propiedad.provincia,
            distrito: propiedad.distrito,
            direccion: propiedad.direccion,
            estado_property: propiedad.estado_property,
            valor_estimado: propiedad.valor_estimado,
            // Información del cliente vinculado
            cliente_id: propiedad.cliente_id,
            investor_name: propiedad.investor_name,
            investor_first_last_name: propiedad.investor_first_last_name,
            investor_second_last_name: propiedad.investor_second_last_name,
            investor_document: propiedad.investor_document
        }))
    } catch (error) {
        console.error('Error al buscar propiedades:', error)
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Error al buscar propiedades",
            life: 3000,
        })
    }
}, 300)

const guardarFormulario = async () => {
    if (!formularioValido.value) {
        toast.add({
            severity: "warn",
            summary: "Formulario Incompleto",
            detail: "Por favor, completa todos los campos obligatorios",
            life: 4000,
        })
        return
    }

    try {
        const payload = {
            property_id: propiedadSeleccionada.value.value,
            config_id: propiedadSeleccionada.value.config_id,
            investor_id: clienteVinculado.value.cliente_id,
            ...form.value
        }

        await axios.post('/property-loan-details', payload)

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Información del financiamiento guardada correctamente',
            life: 3000
        })

        cerrarDialog()
    } catch (error) {
        console.error('Error al guardar:', error)
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Error al guardar la información',
            life: 4000
        })
    }
}

const resetForm = () => {
    form.value = {
        ocupacion_profesion: '',
        empresa_tasadora: '',
        motivo_prestamo: '',
        descripcion_financiamiento: '',
        solicitud_prestamo_para: '',
        garantia: '',
        perfil_riesgo: ''
    }
    propiedadSeleccionada.value = null
    clienteVinculado.value = null
    propiedades.value = []
}
</script>

<style scoped>
.field {
    margin-bottom: 0;
}

.field label {
    display: block;
    margin-bottom: 0.5rem;
}

.field small {
    display: block;
    margin-top: 0.25rem;
    text-align: right;
}

/* Mejorar la apariencia del diálogo */
:deep(.p-dialog .p-dialog-header) {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

:deep(.p-dialog .p-dialog-content) {
    padding: 2rem;
}

:deep(.p-dialog .p-dialog-footer) {
    padding: 1.5rem 2rem;
    border-top: 1px solid #e9ecef;
    background-color: #f8f9fa;
}

/* Estilo para los campos de texto */
:deep(.p-inputtextarea) {
    border-radius: 6px;
    border: 1px solid #ced4da;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

:deep(.p-inputtextarea:focus) {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Estilo para el select */
:deep(.p-select) {
    border-radius: 6px;
}

:deep(.p-select:focus) {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
</style>