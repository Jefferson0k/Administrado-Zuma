<template>
    <Dialog v-model:visible="visible" modal header="Actualizar Regla" :style="{ width: '950px' }">
        <div class="flex flex-col gap-4">
            <!-- Información de la Solicitud (solo lectura) -->
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <h3 class="font-semibold text-blue-800 mb-2">Información de la Solicitud</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-blue-700">Solicitud:</span>
                        <span class="ml-2 text-blue-900">{{ solicitudData.nombreSolicitud || 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-blue-700">Valor General:</span>
                        <span class="ml-2 text-blue-900">{{ solicitudData.valor_general || 'N/A' }} {{ solicitudData.currency || '' }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-blue-700">Valor Requerido:</span>
                        <span class="ml-2 text-blue-900">{{ solicitudData.valor_requerido || 'N/A' }} {{ solicitudData.currency || '' }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-blue-700">Moneda:</span>
                        <span class="ml-2 text-blue-900">{{ solicitudData.currency || 'N/A' }}</span>
                    </div>
                </div>
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
                        placeholder="Seleccione un tipo..." class="w-full" />
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
import { ref, watch, onMounted } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import axios from 'axios'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
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
const plazos = ref([])
const solicitudData = ref({})

// Form data
const tea = ref('')
const tem = ref('')
const cronograma = ref('')
const deadlines_id = ref(null)
const riesgo = ref('')
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

const tipoUsuarioOpciones = [
    { label: 'Inversionista', value: 1 },
    { label: 'Cliente', value: 2 }
]

// Utility functions
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

        // Guardar todos los datos de la solicitud
        solicitudData.value = reglaData

        // Llenar los campos del formulario
        tea.value = reglaData.tea
        tem.value = reglaData.tem
        cronograma.value = reglaData.tipo_cronograma
        deadlines_id.value = reglaData.deadlines_id
        riesgo.value = reglaData.riesgo
        tipoUsuario.value = reglaData.estado_configuracion

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
    tea.value = ''
    tem.value = ''
    cronograma.value = ''
    deadlines_id.value = null
    riesgo.value = ''
    tipoUsuario.value = null
    solicitudData.value = {}
}

const cerrarModal = () => {
    visible.value = false
    resetForm()
}

const saveProperty = async () => {
    // Validar que todos los campos requeridos estén llenos
    if (!tea.value || !tem.value || !deadlines_id.value || !riesgo.value || !cronograma.value || !tipoUsuario.value) {
        toast.add({ 
            severity: 'warn', 
            summary: 'Validación', 
            detail: 'Todos los campos son requeridos', 
            life: 3000 
        })
        return
    }

    loading.value = true
    try {
        const response = await axios.put(`/property/reglas/${props.regla.id}/update`, {
            tea: tea.value,
            tem: tem.value,
            deadlines_id: deadlines_id.value,
            riesgo: riesgo.value,
            tipo_cronograma: cronograma.value,
            estado_configuracion: tipoUsuario.value,
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