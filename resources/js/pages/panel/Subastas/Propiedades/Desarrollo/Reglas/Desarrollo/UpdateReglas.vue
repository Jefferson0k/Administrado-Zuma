<template>
    <Dialog v-model:visible="visible" modal header="Actualizar Regla" :style="{ width: '450px' }">
        <div class="flex flex-col gap-4">
            <div>
                <label class="font-bold mb-1">Propiedad <span class="text-red-500">*</span></label>
                <Select v-model="propiedadSeleccionada" :options="propiedades" :style="{ width: '100%' }" editable disabled
                    optionLabel="label" optionValue="value" showClear placeholder="Buscar propiedad..."
                    @update:modelValue="onInputChange">
                    <template #value="{ value }">
                        <span>
                            {{ getPropiedadLabel(value) }}
                        </span>
                    </template>
                    <template #option="{ option }">
                        <div class="flex justify-between items-center">
                            <div>
                                <strong>{{ option.label }}</strong>
                                <div class="text-sm text-gray-500">{{ option.sublabel }}</div>
                            </div>
                            <Tag v-if="option.estado" :value="option.estado"
                                :severity="getEstadoSeverity(option.estado)" class="ml-3" />
                        </div>
                    </template>
                    <template #empty>Propiedad no encontrada.</template>
                </Select>
            </div>
            <div>
                <label class="font-bold mb-1">TEA <span class="text-red-500">*</span></label>
                <input type="number" v-model="tea" class="p-inputtext p-component w-full" placeholder="Ej. 12.5"
                    step="0.01" />
            </div>

            <!-- TEM -->
            <div>
                <label class="font-bold mb-1">TEM <span class="text-red-500">*</span></label>
                <input type="number" v-model="tem" class="p-inputtext p-component w-full" placeholder="Ej. 1.05"
                    step="0.01" />
            </div>
            <!-- Tipo Cronograma -->
            <div>
                <label class="font-bold mb-1">Tipo Cronograma <span class="text-red-500">*</span></label>
                <Select v-model="cronograma" :options="cronogramaOpciones" optionLabel="label" optionValue="value"
                    placeholder="Seleccionar tipo..." class="w-full" />
            </div>

            <!-- Plazo del crédito -->
            <div>
                <label for="plazo" class="block font-bold mb-2">Cronograma <span class="text-red-500">*</span></label>
                <Select v-model="deadlines_id" :options="plazos" optionLabel="nombre" optionValue="id"
                    placeholder="Seleccione un plazo" class="w-full" />
            </div>

            <!-- Riesgo -->
            <div>
                <label class="font-bold mb-1">Riesgo <span class="text-red-500">*</span></label>
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
            <!-- Estado -->
            <div>
                <label class="font-bold mb-1">Estado <span class="text-red-500">*</span></label>
                <Select v-model="estado" :options="estadoOpciones" optionLabel="label" optionValue="value"
                    placeholder="Seleccionar estado..." class="w-full">
                    <template #value="{ value }">
                        <Tag v-if="value" :value="value" :severity="getEstadoSeverity(value)" class="px-2 py-1" />
                    </template>
                    <template #option="{ option }">
                        <Tag :value="option.label" :severity="getEstadoSeverity(option.value)" class="px-2 py-1" />
                    </template>
                </Select>
            </div>

        </div>
        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" severity="secondary" text @click="cerrarModal" />
            <Button label="Actualizar" icon="pi pi-check" severity="contrast" @click="saveProperty"
                :loading="loading" />
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
const estado = ref('')

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
    { label: 'Activa', value: 'activa' },
    { label: 'Desactivada', value: 'desactivada' }
]

// Computed
const getPropiedadLabel = computed(() => {
    return (value) => {
        if (!value) return ''
        const propiedad = propiedades.value.find(p => p.value === value)
        return propiedad ? propiedad.label : value
    }
})

// Utility functions
const getEstadoSeverity = (estado) => {
    switch (estado) {
        case 'pendiente':
            return 'warn'
        case 'activa':
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
            estado: propiedad.estado
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
        propiedadSeleccionada.value = reglaData.id
        tea.value = reglaData.tea
        tem.value = reglaData.tem
        cronograma.value = reglaData.tipo_cronograma
        deadlines_id.value = reglaData.deadlines_id
        riesgo.value = reglaData.riesgo
        estado.value = reglaData.estado

        // Cargar la propiedad actual en el select
        if (props.regla.nombre) {
            propiedades.value = [{
                label: props.regla.nombre,
                value: reglaData.id,
                estado: reglaData.estado
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
    propiedades.value = []
}

const cerrarModal = () => {
    visible.value = false
    resetForm()
}

const onInputChange = (value) => {
    if (value) {
        buscarPropiedades(value)
    }
}

const saveProperty = async () => {
    if (!tea.value || !tem.value || !cronograma.value || !deadlines_id.value || !riesgo.value) {
        toast.add({
            severity: 'warn',
            summary: 'Campos requeridos',
            detail: 'Por favor complete todos los campos obligatorios',
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
        const data = {
            tea: tea.value,
            tem: tem.value,
            tipo_cronograma: cronograma.value,
            deadlines_id: deadlines_id.value,
            riesgo: riesgo.value,
            estado: estado.value
        }

        await axios.put(`/property/${propiedadSeleccionada.value}/estado`, data)

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Regla actualizada correctamente',
            life: 3000
        })

        emit('updated')
        cerrarModal()
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Error al actualizar la regla',
            life: 3000
        })
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