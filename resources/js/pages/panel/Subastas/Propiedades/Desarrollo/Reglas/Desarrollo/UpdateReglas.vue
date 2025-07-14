<template>
    <Dialog v-model:visible="visible" modal header="Actualizar Regla" :style="{ width: '950px' }">
        <div class="flex flex-col gap-4">
            <!-- Propiedad (fila completa) -->
            <div>
                <label class="block font-semibold mb-2">Propiedad <span class="text-red-500">*</span></label>
                <Select v-model="propiedadSeleccionada" :options="propiedades" :style="{ width: '100%' }" editable disabled
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

            <!-- Estado (fila completa) -->
            <div>
                <label class="block font-semibold mb-2">
                    Estado <span class="text-red-500">*</span>
                </label>
                <Select v-model="estado" :options="estadoOpciones" optionLabel="label" optionValue="value"
                    placeholder="Seleccionar estado..." class="w-full">
                    <template #value="{ value }">
                        <div class="flex items-center gap-2">
                            <i :class="getEstadoIcon(value)" 
                               :style="{ color: getEstadoColor(value) }"></i>
                            <span>{{ getEstadoLabel(value) }}</span>
                        </div>
                    </template>
                    <template #option="{ option }">
                        <div class="flex items-center gap-2">
                            <i :class="getEstadoIcon(option.value)" 
                               :style="{ color: getEstadoColor(option.value) }"></i>
                            <span>{{ option.label }}</span>
                        </div>
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
            <div class="grid grid-cols-3 gap-4">
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
                        placeholder="Seleccione un tipo..." class="w-full" disabled/>
                </div>
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
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cancelar" icon="pi pi-times" severity="secondary" text @click="cerrarModal" />
                <Button label="Actualizar" icon="pi pi-check" severity="contrast"
                    @click="saveProperty" :loading="loading" />
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import axios from 'axios'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import { debounce } from 'lodash'
import { useToast } from 'primevue/usetoast'

const toast = useToast()

const props = defineProps({
    regla: Object,
    modelValue: Boolean,
})

const emit = defineEmits(['update:modelValue', 'updated'])

// Reactive data
const visible = ref(props.modelValue)
const loading = ref(false)
const propiedades = ref([])
const plazos = ref([])

// Form data
const propiedadSeleccionada = ref(null)
const tea = ref('')
const tem = ref('')
const cronograma = ref('')
const deadlines_id = ref(null)
const riesgo = ref('')
const estado = ref('')
const tipoUsuario = ref(null)

// Options
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

const estadoOpciones = [
    { label: 'Activo', value: 'activa' },
    { label: 'Desactivo', value: 'desactivada' }
]

const tipoUsuarioOpciones = [
    { label: 'Inversionista', value: 1 },
    { label: 'Cliente', value: 2 }
]

// Computed properties
const getPropiedadLabel = (id) => {
    const prop = propiedades.value.find(p => p.value === id)
    return prop ? prop.label : id
}

// Utility functions
const getEstadoSeverity = (estado) => {
    switch (estado) {
        case 'pendiente':
            return 'warn'
        case 'activa':
        case 'activo':
            return 'success'
        case 'desactivada':
        case 'desactivo':
            return 'danger'
        case 'rechazado':
            return 'danger'
        default:
            return 'info'
    }
}

const getEstadoIcon = (estado) => {
    switch (estado) {
        case 'activa':
        case 'activo':
            return 'pi pi-check-circle'
        case 'desactivada':
        case 'desactivo':
            return 'pi pi-times-circle'
        default:
            return 'pi pi-circle'
    }
}

const getEstadoColor = (estado) => {
    switch (estado) {
        case 'activa':
        case 'activo':
            return '#10b981' // Verde
        case 'desactivada':
        case 'desactivo':
            return '#ef4444' // Rojo
        default:
            return '#6b7280' // Gris
    }
}

const getEstadoLabel = (estado) => {
    const opcion = estadoOpciones.find(opt => opt.value === estado)
    return opcion ? opcion.label : estado
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

// Methods
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
    } catch (error) {
        toast.add({
            severity: 'warn',
            summary: 'Error',
            detail: 'No se pudieron cargar los plazos',
            life: 3000
        })
    }
}

const cargarDatosRegla = async () => {
    if (!props.regla?.id) return

    try {
        const response = await axios.get(`/property/reglas/${props.regla.id}/show`)
        const reglaData = response.data.data

        // Llenar los campos del formulario
        propiedadSeleccionada.value = reglaData.idProperty
        tea.value = reglaData.tea
        tem.value = reglaData.tem
        cronograma.value = reglaData.tipo_cronograma
        deadlines_id.value = reglaData.deadlines_id
        riesgo.value = reglaData.riesgo
        estado.value = reglaData.estado
        tipoUsuario.value = reglaData.estado_configuracion

        // Cargar la propiedad actual en el select
        if (props.regla.nombre) {
            propiedades.value = [{
                label: props.regla.nombre,
                sublabel: `${props.regla.distrito || ''} | ${props.regla.direccion || ''}`,
                value: reglaData.id,
                estado: reglaData.estado,
                valor_requerido: props.regla.valor_requerido || 0
            }]
        }
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Error al cargar los datos de la regla",
            life: 3000,
        })
    }
}


const resetForm = () => {
    propiedadSeleccionada.value = null
    tea.value = ''
    tem.value = ''
    cronograma.value = ''
    deadlines_id.value = null
    riesgo.value = ''
    estado.value = ''
    tipoUsuario.value = null
    propiedades.value = []
}

const cerrarModal = () => {
    visible.value = false
    resetForm()
}

const onInputChange = (value) => {
    if (value && typeof value === 'string') {
        buscarPropiedades(value)
    }
}

const saveProperty = async () => {
    // Validar que todos los campos requeridos estén llenos
    if (!estado.value || !tea.value || !tem.value || !deadlines_id.value || !riesgo.value || !cronograma.value || !tipoUsuario.value) {
        toast.add({ 
            severity: 'warn', 
            summary: 'Validación', 
            detail: 'Todos los campos son requeridos', 
            life: 3000 
        })
        return
    }

    if (!propiedadSeleccionada.value) {
        toast.add({
            severity: 'warn',
            summary: 'Falta propiedad',
            detail: 'Seleccione una propiedad válida',
            life: 3000
        })
        return
    }

    loading.value = true
    try {
        const response = await axios.put(`/property/${propiedadSeleccionada.value}/estado`, {
            tea: tea.value,
            tem: tem.value,
            deadlines_id: deadlines_id.value,
            riesgo: riesgo.value,
            tipo_cronograma: cronograma.value,
            estado_property: estado.value, // Para la tabla Property ('activa' o 'desactiva')
            estado_configuracion: tipoUsuario.value, // Para la tabla PropertyConfiguracion (1 o 2)
        })

        toast.add({
            severity: 'success',
            summary: 'Actualizado',
            detail: response.data.message,
            life: 3000
        })

        emit('updated')
        cerrarModal()
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
    } finally {
        loading.value = false
    }
}

// Watchers
watch(() => props.modelValue, (val) => {
    visible.value = val
    if (val && props.regla) {
        cargarDatosRegla()
    }
})

watch(() => visible.value, (val) => {
    emit('update:modelValue', val)
    if (!val) {
        resetForm()
    }
})

watch(() => props.regla, (newRegla) => {
    if (newRegla && visible.value) {
        cargarDatosRegla()
    }
})

// Lifecycle
onMounted(() => {
    cargarPlazos()
})
</script>