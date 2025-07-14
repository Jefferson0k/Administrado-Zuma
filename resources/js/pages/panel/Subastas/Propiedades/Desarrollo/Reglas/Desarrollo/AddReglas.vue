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
    <Dialog v-model:visible="visible" modal header="Reglas del inmueble" :style="{ width: '950px' }">
        <div class="flex flex-col gap-4">
            <!-- Propiedad (fila completa) -->
            <div>
                <label class="block font-semibold mb-2">Propiedad <span class="text-red-500">*</span></label>
                <Select v-model="propiedadSeleccionada" :options="propiedades" :style="{ width: '100%' }" editable
                    optionLabel="label" optionValue="value" showClear placeholder="Buscar propiedad..."
                    @update:modelValue="onInputChange">
                    <template #value="{ value }">
                        <span>{{ getPropiedadLabel(value) }}</span>
                    </template>
                    <template #option="{ option }">
                        <div class="flex justify-between items-center py-2">
                            <div>
                                <strong>{{ option.label }}</strong>
                                <div class="text-sm">{{ option.sublabel }}</div>
                            </div>
                            <Tag v-if="option.estado" :value="option.estado"
                                :severity="getEstadoSeverity(option.estado)" class="ml-3" />
                        </div>
                    </template>
                    <template #empty>
                        <div class="text-center py-2">Propiedad no encontrada</div>
                    </template>
                </Select>
            </div>

            <!-- Primera fila de 3 columnas -->
            <div class="grid grid-cols-3 gap-4">
                <!-- TEA -->
                <div>
                    <label class="block font-semibold mb-2">
                        TEA (%) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" v-model="tea" class="p-inputtext p-component w-full pr-8"
                            placeholder="Ej. 12.5" step="0.01" min="0" max="100" />
                        <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">%</span>
                    </div>
                </div>

                <!-- TEM -->
                <div>
                    <label class="block font-semibold mb-2">
                        TEM (%) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" v-model="tem" class="p-inputtext p-component w-full pr-8"
                            placeholder="Ej. 1.05" step="0.01" min="0" max="20" />
                        <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">%</span>
                    </div>
                </div>

                <!-- Tipo Cronograma -->
                <div>
                    <label class="block font-semibold mb-2">
                        Tipo Cronograma <span class="text-red-500">*</span>
                    </label>
                    <Select v-model="cronograma" :options="cronogramaOpciones" optionLabel="label" optionValue="value"
                        placeholder="Seleccionar tipo..." class="w-full" />
                </div>
            </div>

            <!-- Segunda fila de 2 columnas -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Plazo -->
                <div>
                    <label class="block font-semibold mb-2">
                        Plazo del crédito <span class="text-red-500">*</span>
                    </label>
                    <Select v-model="deadlines_id" :options="plazos" optionLabel="nombre" optionValue="id"
                        placeholder="Seleccione un plazo" class="w-full" />
                </div>

                <!-- Tipo de usuario -->
                <div>
                    <label class="block font-semibold mb-2">
                        Tipo de usuario <span class="text-red-500">*</span>
                    </label>
                    <Select v-model="tipoUsuario" :options="tipoUsuarioOpciones" optionLabel="label" optionValue="value"
                        placeholder="Seleccione un tipo..." class="w-full" />
                </div>
            </div>

            <!-- Tercera fila: Riesgo y Botón -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Riesgo -->
                <div>
                    <label class="block font-semibold mb-2">
                        Riesgo <span class="text-red-500">*</span>
                    </label>
                    <Select v-model="riesgo" :options="riesgos" optionLabel="label" optionValue="value"
                        placeholder="Seleccionar riesgo..." class="w-full">
                        <template #value="{ value }">
                            <Tag v-if="value" :value="value" :severity="getRiesgoSeverity(value)" class="px-2 py-1" />
                        </template>
                        <template #option="{ option }">
                            <Tag :value="option.label" :severity="getRiesgoSeverity(option.value)" class="px-2 py-1" />
                        </template>
                    </Select>
                </div>

                <!-- Botón Previsualizar -->
                <div class="flex items-end">
                    <Button label="Previsualizar cronograma" icon="pi pi-eye" severity="info"
                        :disabled="!canPreviewCronograma" @click="previsualizarCronograma" class="w-full" />
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cancelar" icon="pi pi-times" severity="secondary" text
                    @click="() => { visible = false; resetForm() }" />

                <Button label="Guardar" icon="pi pi-check" severity="contrast" @click="actualizarPropiedad" />
            </div>
        </template>
    </Dialog>
    <!-- Componente para ver cronograma -->
    <VerCronograma v-model:visible="cronogramaVisible" :propiedad-data="propiedadSeleccionadaData"
        :parametros="parametrosCronograma" />
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import Toolbar from 'primevue/toolbar'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import axios from 'axios'
import { debounce } from 'lodash'
import VerCronograma from './verCronograma.vue'

const toast = useToast()

const visible = ref(false)
const cronogramaVisible = ref(false)

const propiedades = ref([])
const propiedadSeleccionada = ref(null)
const propiedadSeleccionadaData = ref(null)
const tea = ref('')
const tem = ref('')
const cronograma = ref(null)
const deadlines_id = ref(null)
const riesgo = ref(null)
const plazos = ref([])
const tipoUsuario = ref(null)
const estado = ref(null) // Nuevo campo para el estado


const resetForm = () => {
    propiedadSeleccionada.value = null
    tea.value = ''
    tem.value = ''
    cronograma.value = null
    deadlines_id.value = null
    riesgo.value = null
    tipoUsuario.value = null
    estado.value = null
    propiedadSeleccionadaData.value = null
    propiedades.value = []
}


const parametrosCronograma = computed(() => {
    const plazoSeleccionado = plazos.value.find(p => p.id === deadlines_id.value)

    const propiedadData = propiedades.value.find(p => p.value === propiedadSeleccionada.value)
    const valorRequerido = propiedadData ? parseFloat(propiedadData.valor_requerido) : 0

    return {
        tea: tea.value,
        tem: tem.value,
        cronograma: cronograma.value,
        deadlines_id: deadlines_id.value,
        duracion_meses: plazoSeleccionado ? plazoSeleccionado.duracion_meses : 0,
        valor_requerido: valorRequerido
    }
})

const previsualizarCronograma = async () => {
    if (!propiedadSeleccionada.value) {
        toast.add({
            severity: 'warn',
            summary: 'Validación',
            detail: 'Debe seleccionar una propiedad',
            life: 3000
        })
        return
    }

    const propiedadData = propiedades.value.find(p => p.value === propiedadSeleccionada.value)
    if (!propiedadData) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se encontraron los datos de la propiedad seleccionada',
            life: 3000
        })
        return
    }

    try {
        const response = await axios.get(`/property/${propiedadSeleccionada.value}`)
        propiedadSeleccionadaData.value = response.data.data

        cronogramaVisible.value = true

    } catch (error) {
        propiedadSeleccionadaData.value = {
            ...propiedadData,
            valor_requerido: propiedadData.valor_requerido || 0
        }

        cronogramaVisible.value = true
    }
}

const canPreviewCronograma = computed(() => {
    return propiedadSeleccionada.value !== null &&
        tea.value !== '' &&
        tem.value !== '' &&
        cronograma.value !== null &&
        deadlines_id.value !== null
})

const cronogramaOpciones = [
    { label: 'Francés', value: 'frances' },
    { label: 'Americano', value: 'americano' }
]

const riesgos = [
    { label: 'A+', value: 'A+' },
    { label: 'A', value: 'A' },
    { label: 'B', value: 'B' },
    { label: 'C', value: 'C' },
    { label: 'D', value: 'D' }
]

const tipoUsuarioOpciones = [
    { label: 'Inversionista', value: 1 },
    { label: 'Cliente', value: 2 }
]

const showToast = () => {
    toast.add({
        severity: 'info',
        summary: 'Información',
        detail: 'Aún se encuentra en desarrollo',
        life: 3000
    })
}

const openDialog = () => {
    resetForm()
    cargarPlazos()
    visible.value = true
}

const getPropiedadLabel = (id) => {
    const prop = propiedades.value.find(p => p.value === id)
    return prop ? prop.label : id
}

const getEstadoSeverity = (estado) => {
    switch (estado) {
        case 'pendiente':
            return 'warn'
        case 'activo':
            return 'success'
        case 'rechazado':
            return 'danger'
        default:
            return 'info'
    }
}

const getRiesgoSeverity = (riesgo) => {
    switch (riesgo) {
        case 'A+':
        case 'A':
            return 'success'
        case 'B':
            return 'info'
        case 'C':
            return 'warn'
        case 'D':
            return 'danger'
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
        const response = await axios.get("/propiedades/pendientes", {
            params: { search: texto },
        })

        propiedades.value = response.data.data.map((propiedad) => ({
            label: `${propiedad.nombre} - ${propiedad.departamento}, ${propiedad.provincia}`,
            sublabel: `${propiedad.distrito} | ${propiedad.direccion}`,
            value: propiedad.id,
            estado: propiedad.estado,
            valor_requerido: propiedad.valor_requerido
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

const cargarPlazos = async () => {
    try {
        const response = await axios.get('/deadlines')
        plazos.value = response.data.data
    } catch (e) {
        toast.add({ severity: 'warn', summary: 'Error', detail: 'No se pudieron cargar los plazos', life: 3000 })
    }
}

const onInputChange = (valor) => {
    if (typeof valor === 'string') {
        buscarPropiedades(valor)
    }
}

const actualizarPropiedad = async () => {
    if (!propiedadSeleccionada.value) {
        toast.add({ severity: 'warn', summary: 'Validación', detail: 'Seleccione una propiedad', life: 3000 })
        return
    }

    if (!tea.value || !tem.value || !deadlines_id.value || !riesgo.value || !cronograma.value || !tipoUsuario.value) {
        toast.add({
            severity: 'warn',
            summary: 'Validación',
            detail: 'Todos los campos son requeridos',
            life: 3000
        })
        return
    }

    try {
        const response = await axios.put(`/property/${propiedadSeleccionada.value}/estado`, {
            tea: tea.value,
            tem: tem.value,
            deadlines_id: deadlines_id.value,
            riesgo: riesgo.value,
            tipo_cronograma: cronograma.value,
            //estado_property: estado.value,
            estado_configuracion: tipoUsuario.value,
        })

        toast.add({
            severity: 'success',
            summary: 'Actualizado',
            detail: response.data.message,
            life: 3000
        })

        visible.value = false
        resetForm()

    } catch (error) {
        const errores = error.response?.data?.errors
        if (errores) {
            Object.values(errores).forEach((mensajeArray) => {
                toast.add({
                    severity: 'error',
                    summary: 'Error de validación',
                    detail: mensajeArray[0],
                    life: 4000,
                })
            })
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response?.data?.message || 'No se pudo actualizar',
                life: 4000,
            })
        }
    }
}
</script>