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
        :style="{ width: '50rem' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
        <div class="flex flex-col gap-4">
            <!-- Selección de Propiedad -->
            <div>
                <label class="font-bold mb-1">Propiedad <span class="text-red-500">*</span></label>
                <Select v-model="propiedadSeleccionada" :options="propiedades" optionLabel="label" showClear editable
                    :style="{ width: '100%' }" placeholder="Buscar propiedad..." @update:modelValue="onInputChange">
                    <template #value="{ value, placeholder }">
                        <span v-if="value">{{ value.label }}</span>
                        <span v-else class="text-gray-400">{{ placeholder }}</span>
                    </template>
                    <template #option="{ option }">
                        <div class="flex justify-between items-center">
                            <div>
                                <strong>{{ option.label }}</strong>
                                <div class="text-sm text-gray-500">DNI: {{ option.documento }}</div>
                            </div>
                            <!-- Mostrar estado como Tag -->
                            <Tag
                                :value="option.asignado === 0 ? 'Pendiente' : 'Asignado'"
                                :severity="option.asignado === 0 ? 'warning' : 'success'"
                                class="ml-3"
                            />
                        </div>
                    </template>
                    <template #empty>Propiedad no encontrada.</template>
                </Select>

            </div>

            <!-- Selección de Cliente -->
            <div>
                <label class="font-bold mb-1">Cliente <span class="text-red-500">*</span></label>
                <Select v-model="clienteSeleccionado" :options="clientes" optionLabel="label" showClear editable
                    :style="{ width: '100%' }" placeholder="Buscar cliente..." @update:modelValue="onClienteChange">
                    <template #value="{ value, placeholder }">
                        <span v-if="value">{{ value.label }}</span>
                        <span v-else class="text-gray-400">{{ placeholder }}</span>
                    </template>
                    <template #option="{ option }">
                        <div class="flex justify-between items-center">
                            <div>
                                <strong>{{ option.label }}</strong>
                                <div class="text-sm text-gray-500">{{ option.sublabel }}</div>
                            </div>
                            <Tag v-if="option.estado" value="activo" severity="success" class="ml-3" />
                        </div>
                    </template>
                    <template #empty>Cliente no encontrado.</template>
                </Select>
            </div>

            <!-- Campos del formulario -->
            <div>
                <label class="font-bold mb-1">Ocupación/Profesión <span class="text-red-500">*</span></label>
                <Textarea v-model="form.ocupacion_profesion" autoResize rows="2" class="w-full" />
            </div>
            <div>
                <label class="font-bold mb-1">Motivo del Préstamo <span class="text-red-500">*</span></label>
                <Textarea v-model="form.motivo_prestamo" autoResize rows="2" class="w-full" />
            </div>
            <div>
                <label class="font-bold mb-1">Descripción del Financiamiento <span class="text-red-500">*</span></label>
                <Textarea v-model="form.descripcion_financiamiento" autoResize rows="3" class="w-full" />
            </div>
            <div>
                <label class="font-bold mb-1">Solicitud del Préstamo para <span class="text-red-500">*</span></label>
                <Textarea v-model="form.solicitud_prestamo_para" autoResize rows="2" class="w-full" />
            </div>
            <div>
                <label class="font-bold mb-1">Garantía <span class="text-red-500">*</span></label>
                <Textarea v-model="form.garantia" autoResize rows="2" class="w-full" />
            </div>
            <div>
                <label class="font-bold mb-1">Perfil del Riesgo <span class="text-red-500">*</span></label>
                <Textarea v-model="form.perfil_riesgo" autoResize rows="2" class="w-full" />
            </div>
        </div>

        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" severity="secondary" text @click="visible = false" />
            <Button label="Guardar" icon="pi pi-check" severity="contrast" @click="guardarFormulario" />
        </template>
    </Dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue'
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

const clienteSeleccionado = ref(null)
const clientes = ref([])

const form = ref({
    ocupacion_profesion: '',
    motivo_prestamo: '',
    descripcion_financiamiento: '',
    solicitud_prestamo_para: '',
    garantia: '',
    perfil_riesgo: ''
})

const openDialog = () => {
    visible.value = true
}

const showToast = () => {
    toast.add({
        severity: 'info',
        summary: 'Información',
        detail: 'Aún se encuentra en desarrollo',
        life: 3000
    })
}

const onInputChange = (valor) => {
    if (typeof valor === 'string') {
        buscarPropiedades(valor)
    }
}

const onClienteChange = (valor) => {
    if (typeof valor === 'string') {
        buscarClientes(valor)
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
    if (!texto) {
        propiedades.value = []
        return
    }

    try {
        const response = await axios.get("/propiedades/activas", {
            params: { search: texto },
        })

        propiedades.value = response.data.data.map((propiedad) => ({
            label: `${propiedad.nombre} - ${propiedad.departamento}, ${propiedad.provincia}`,
            sublabel: `${propiedad.distrito} | ${propiedad.direccion}`,
            value: propiedad.property_id,
            config_id: propiedad.config_id, // ✅ necesario para backend
            estado_property: propiedad.estado_property,
            valor_estimado: propiedad.valor_estimado
        }))
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Error al buscar propiedades",
            life: 3000,
        })
    }
}, 500)

const buscarClientes = debounce(async (texto) => {
    if (!texto) {
        clientes.value = []
        return
    }

    try {
        const response = await axios.get("/clientes/activos", {
            params: { search: texto },
        })

        clientes.value = response.data.data.map((cliente) => ({
            label: cliente.nombre_completo,
            sublabel: `DNI: ${cliente.documento}`,
            value: cliente.id,
            asignado: cliente.asignado,
        }))
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Error al buscar clientes",
            life: 3000,
        })
    }
}, 500)

const guardarFormulario = async () => {
    if (!propiedadSeleccionada.value) {
        toast.add({
            severity: "warn",
            summary: "Atención",
            detail: "Debes seleccionar una propiedad",
            life: 3000,
        })
        return
    }

    if (!clienteSeleccionado.value) {
        toast.add({
            severity: "warn",
            summary: "Atención",
            detail: "Debes seleccionar un cliente",
            life: 3000,
        })
        return
    }

    try {
        const payload = {
            property_id: propiedadSeleccionada.value.value,
            config_id: propiedadSeleccionada.value.config_id, // ✅ se envía al backend
            investor_id: clienteSeleccionado.value.value,
            ...form.value
        }

        await axios.post('/property-loan-details', payload)

        toast.add({
            severity: 'success',
            summary: 'Guardado',
            detail: 'Información del financiamiento guardada correctamente',
            life: 3000
        })

        visible.value = false
        resetForm()
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Error al guardar',
            life: 3000
        })
    }
}

const resetForm = () => {
    form.value = {
        ocupacion_profesion: '',
        motivo_prestamo: '',
        descripcion_financiamiento: '',
        solicitud_prestamo_para: '',
        garantia: '',
        perfil_riesgo: ''
    }
    propiedadSeleccionada.value = null
    clienteSeleccionado.value = null
}
</script>
