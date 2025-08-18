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
    <Dialog v-model:visible="visible" modal header="Reglas del inmueble" :style="{ width: '1100px' }">
        <div class="flex flex-col gap-6">
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

            <!-- Campos generales -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Tipo Cronograma -->
                <div>
                    <label class="block font-semibold mb-2">
                        Tipo Cronograma <span class="text-red-500">*</span>
                    </label>
                    <Select v-model="cronograma" :options="cronogramaOpciones" optionLabel="label" optionValue="value"
                        placeholder="Seleccionar tipo..." class="w-full" />
                </div>

                <!-- Plazo -->
                <div>
                    <label class="block font-semibold mb-2">
                        Plazo del crédito <span class="text-red-500">*</span>
                    </label>
                    <Select v-model="deadlines_id" :options="plazos" optionLabel="nombre" optionValue="id"
                        placeholder="Seleccione un plazo" class="w-full" />
                </div>
            </div>

            <!-- SECCIÓN INVERSIONISTA -->
            <div class="border border-blue-200 rounded-lg p-4 bg-blue-50">
                <h3 class="text-lg font-semibold text-blue-800 mb-4 flex items-center">
                    <i class="pi pi-chart-line mr-2"></i>
                    Configuración para Inversionista
                </h3>
                
                <div class="grid grid-cols-3 gap-4">
                    <!-- TEM Inversionista -->
                    <div>
                        <label class="block font-semibold mb-2">
                            TEM Inversionista (%) <span class="text-red-500">*</span>
                        </label>
                        <InputNumber v-model="temInversionista" 
                            @update:modelValue="calcularTeaDesdeTemInversionista"
                            class="w-full"
                            placeholder="Ej. 1.05" 
                            :minFractionDigits="3"
                            :maxFractionDigits="3"
                            suffix="%" 
                            :min="0" 
                            :max="20" />
                    </div>

                    <!-- TEA Inversionista -->
                    <div>
                        <label class="block font-semibold mb-2">
                            TEA Inversionista (%) <span class="text-red-500">*</span>
                        </label>
                        <InputNumber v-model="teaInversionista" 
                            @update:modelValue="calcularTemDesdeTeaInversionista"
                            class="w-full"
                            placeholder="Ej. 12.5" 
                            :minFractionDigits="3"
                            :maxFractionDigits="3"
                            suffix="%" 
                            :min="0" 
                            :max="100" />
                        <small class="text-gray-600 mt-1 block">
                            Conversión automática TEM ↔ TEA
                        </small>
                    </div>

                    <!-- Riesgo Inversionista -->
                    <div>
                        <label class="block font-semibold mb-2">
                            Riesgo Inversionista <span class="text-red-500">*</span>
                        </label>
                        <Select v-model="riesgoInversionista" :options="riesgos" optionLabel="label" optionValue="value"
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

                <div class="flex justify-between items-center mt-4">
                    <Button label="Previsualizar cronograma" icon="pi pi-eye" severity="info" outlined
                        :disabled="!canPreviewCronogramaInversionista" @click="previsualizarCronograma('inversionista')" />
                    
                    <Button label="Guardar Inversionista" icon="pi pi-check" severity="primary"
                        :disabled="!canSaveInversionista" @click="actualizarPropiedad(1)" />
                </div>
            </div>

            <!-- SECCIÓN CLIENTE -->
            <div class="border border-green-200 rounded-lg p-4 bg-green-50">
                <h3 class="text-lg font-semibold text-green-800 mb-4 flex items-center">
                    <i class="pi pi-user mr-2"></i>
                    Configuración para Cliente
                </h3>
                
                <div class="grid grid-cols-3 gap-4">
                    <!-- TEM Cliente -->
                    <div>
                        <label class="block font-semibold mb-2">
                            TEM Cliente (%) <span class="text-red-500">*</span>
                        </label>
                        <InputNumber v-model="temCliente" 
                            @update:modelValue="calcularTeaDesdeTemCliente"
                            class="w-full"
                            placeholder="Ej. 1.05" 
                            :minFractionDigits="3"
                            :maxFractionDigits="3"
                            suffix="%" 
                            :min="0" 
                            :max="20" />
                    </div>

                    <!-- TEA Cliente -->
                    <div>
                        <label class="block font-semibold mb-2">
                            TEA Cliente (%) <span class="text-red-500">*</span>
                        </label>
                        <InputNumber v-model="teaCliente" 
                            @update:modelValue="calcularTemDesdeTeaCliente"
                            class="w-full"
                            placeholder="Ej. 15.0" 
                            :minFractionDigits="3"
                            :maxFractionDigits="3"
                            suffix="%" 
                            :min="0" 
                            :max="100" />
                        <small class="text-gray-600 mt-1 block">
                            Conversión automática TEM ↔ TEA
                        </small>
                    </div>

                    <!-- Riesgo Cliente -->
                    <div>
                        <label class="block font-semibold mb-2">
                            Riesgo Cliente <span class="text-red-500">*</span>
                        </label>
                        <Select v-model="riesgoCliente" :options="riesgos" optionLabel="label" optionValue="value"
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

                <div class="flex justify-between items-center mt-4">
                    <Button label="Previsualizar cronograma" icon="pi pi-eye" severity="info" outlined
                        :disabled="!canPreviewCronogramaCliente" @click="previsualizarCronograma('cliente')" />
                    
                    <Button label="Guardar Cliente" icon="pi pi-check" severity="success"
                        :disabled="!canSaveCliente" @click="actualizarPropiedad(2)" />
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cancelar" icon="pi pi-times" severity="secondary" text
                    @click="() => { visible = false; resetForm() }" />
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
import InputNumber from 'primevue/inputnumber'
import VerCronograma from './verCronograma.vue'

const toast = useToast()

const visible = ref(false)
const cronogramaVisible = ref(false)

const propiedades = ref([])
const propiedadSeleccionada = ref(null)
const propiedadSeleccionadaData = ref(null)

// Campos generales
const cronograma = ref(null)
const deadlines_id = ref(null)
const plazos = ref([])

// Campos específicos para inversionista
const temInversionista = ref(null)
const teaInversionista = ref(null)
const riesgoInversionista = ref(null)

// Campos específicos para cliente
const temCliente = ref(null)
const teaCliente = ref(null)
const riesgoCliente = ref(null)

const tipoUsuarioActual = ref(null) // Para saber qué tipo se está previsualizando

// Variables para mostrar los cálculos automáticos - ELIMINADAS
// const temCalculadoInversionista = ref('')
// const temCalculadoCliente = ref('')

// Funciones de conversión TEA/TEM
const convertirTeaATem = (tea) => {
    if (!tea || tea === 0) return 0
    // Fórmula: TEM = (1 + TEA)^(1/12) - 1
    return Math.pow(1 + (tea / 100), 1/12) - 1
}

const convertirTemATea = (tem) => {
    if (!tem || tem === 0) return 0
    // Fórmula: TEA = (1 + TEM)^12 - 1
    return Math.pow(1 + (tem / 100), 12) - 1
}

// Variables para prevenir bucles infinitos
const actualizandoInversionista = ref(false)
const actualizandoCliente = ref(false)

// Funciones para calcular automáticamente - Inversionista
const calcularTeaDesdeTemInversionista = () => {
    if (actualizandoInversionista.value) return
    
    if (temInversionista.value !== null && temInversionista.value !== undefined && temInversionista.value !== '') {
        actualizandoInversionista.value = true
        const teaCalculado = convertirTemATea(parseFloat(temInversionista.value))
        teaInversionista.value = parseFloat((teaCalculado * 100).toFixed(3))
        setTimeout(() => {
            actualizandoInversionista.value = false
        }, 100)
    } else {
        if (!actualizandoInversionista.value) {
            teaInversionista.value = null
        }
    }
}

const calcularTemDesdeTeaInversionista = () => {
    if (actualizandoInversionista.value) return
    
    if (teaInversionista.value !== null && teaInversionista.value !== undefined && teaInversionista.value !== '') {
        actualizandoInversionista.value = true
        const temCalculado = convertirTeaATem(parseFloat(teaInversionista.value))
        temInversionista.value = parseFloat((temCalculado * 100).toFixed(3))
        setTimeout(() => {
            actualizandoInversionista.value = false
        }, 100)
    } else {
        if (!actualizandoInversionista.value) {
            temInversionista.value = null
        }
    }
}

// Funciones para calcular automáticamente - Cliente
const calcularTeaDesdeTemCliente = () => {
    if (actualizandoCliente.value) return
    
    if (temCliente.value !== null && temCliente.value !== undefined && temCliente.value !== '') {
        actualizandoCliente.value = true
        const teaCalculado = convertirTemATea(parseFloat(temCliente.value))
        teaCliente.value = parseFloat((teaCalculado * 100).toFixed(3))
        setTimeout(() => {
            actualizandoCliente.value = false
        }, 100)
    } else {
        if (!actualizandoCliente.value) {
            teaCliente.value = null
        }
    }
}

const calcularTemDesdeTeaCliente = () => {
    if (actualizandoCliente.value) return
    
    if (teaCliente.value !== null && teaCliente.value !== undefined && teaCliente.value !== '') {
        actualizandoCliente.value = true
        const temCalculado = convertirTeaATem(parseFloat(teaCliente.value))
        temCliente.value = parseFloat((temCalculado * 100).toFixed(3))
        setTimeout(() => {
            actualizandoCliente.value = false
        }, 100)
    } else {
        if (!actualizandoCliente.value) {
            temCliente.value = null
        }
    }
}

const resetForm = () => {
    propiedadSeleccionada.value = null
    cronograma.value = null
    deadlines_id.value = null
    temInversionista.value = null
    teaInversionista.value = null
    riesgoInversionista.value = null
    temCliente.value = null
    teaCliente.value = null
    riesgoCliente.value = null
    propiedadSeleccionadaData.value = null
    propiedades.value = []
    tipoUsuarioActual.value = null
    actualizandoInversionista.value = false
    actualizandoCliente.value = false
}

const parametrosCronograma = computed(() => {
    const plazoSeleccionado = plazos.value.find(p => p.id === deadlines_id.value)
    const propiedadData = propiedades.value.find(p => p.value === propiedadSeleccionada.value)
    const valorRequerido = propiedadData ? parseFloat(propiedadData.valor_requerido) : 0

    const teaActual = tipoUsuarioActual.value === 'inversionista' ? teaInversionista.value : teaCliente.value
    const temActual = tipoUsuarioActual.value === 'inversionista' ? temInversionista.value : temCliente.value
    const riesgoActual = tipoUsuarioActual.value === 'inversionista' ? riesgoInversionista.value : riesgoCliente.value

    return {
        tea: teaActual,
        tem: temActual,
        riesgo: riesgoActual,
        cronograma: cronograma.value,
        deadlines_id: deadlines_id.value,
        duracion_meses: plazoSeleccionado ? plazoSeleccionado.duracion_meses : 0,
        valor_requerido: valorRequerido,
        currency_id: propiedadData?.currency_id || 1,
        currency: propiedadData?.currency || 'PEN',
        currency_symbol: propiedadData?.currency_symbol || 'S/'
    }
})

const previsualizarCronograma = async (tipo) => {
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

    tipoUsuarioActual.value = tipo

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

// Computed para validar campos generales
const camposGenerales = computed(() => {
    return propiedadSeleccionada.value !== null &&
        cronograma.value !== null &&
        deadlines_id.value !== null
})

// Computed para validar previsualización inversionista
const canPreviewCronogramaInversionista = computed(() => {
    return camposGenerales.value && 
        temInversionista.value !== null && temInversionista.value !== '' &&
        teaInversionista.value !== null && teaInversionista.value !== '' &&
        riesgoInversionista.value !== null
})

// Computed para validar previsualización cliente
const canPreviewCronogramaCliente = computed(() => {
    return camposGenerales.value && 
        temCliente.value !== null && temCliente.value !== '' &&
        teaCliente.value !== null && teaCliente.value !== '' &&
        riesgoCliente.value !== null
})

// Computed para validar guardado inversionista
const canSaveInversionista = computed(() => {
    return camposGenerales.value && 
        temInversionista.value !== null && temInversionista.value !== '' &&
        teaInversionista.value !== null && teaInversionista.value !== '' &&
        riesgoInversionista.value !== null
})

// Computed para validar guardado cliente
const canSaveCliente = computed(() => {
    return camposGenerales.value && 
        temCliente.value !== null && temCliente.value !== '' &&
        teaCliente.value !== null && teaCliente.value !== '' &&
        riesgoCliente.value !== null
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
            valor_requerido: propiedad.valor_requerido,
            currency_id: propiedad.currency_id,
            currency: propiedad.currency,
            currency_symbol: propiedad.currency_symbol
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

const actualizarPropiedad = async (tipoUsuario) => {
    if (!propiedadSeleccionada.value) {
        toast.add({ severity: 'warn', summary: 'Validación', detail: 'Seleccione una propiedad', life: 3000 })
        return
    }

    // Validar campos según el tipo de usuario
    const tea = tipoUsuario === 1 ? teaInversionista.value : teaCliente.value
    const tem = tipoUsuario === 1 ? temInversionista.value : temCliente.value
    const riesgo = tipoUsuario === 1 ? riesgoInversionista.value : riesgoCliente.value

    if (!tea || !tem || !deadlines_id.value || !riesgo || !cronograma.value) {
        const tipoTexto = tipoUsuario === 1 ? 'inversionista' : 'cliente'
        toast.add({
            severity: 'warn',
            summary: 'Validación',
            detail: `Todos los campos para ${tipoTexto} son requeridos`,
            life: 3000
        })
        return
    }

    try {
        const response = await axios.put(`/property/${propiedadSeleccionada.value}/estado`, {
            tea: tea,
            tem: tem,
            deadlines_id: deadlines_id.value,
            riesgo: riesgo,
            tipo_cronograma: cronograma.value,
            estado_configuracion: tipoUsuario,
        })

        const tipoTexto = tipoUsuario === 1 ? 'inversionista' : 'cliente'
        toast.add({
            severity: 'success',
            summary: 'Actualizado',
            detail: `Configuración para ${tipoTexto} guardada exitosamente`,
            life: 3000
        })

        // Limpiar solo los campos del tipo guardado
        if (tipoUsuario === 1) {
            temInversionista.value = null
            teaInversionista.value = null
            riesgoInversionista.value = null
        } else {
            temCliente.value = null
            teaCliente.value = null
            riesgoCliente.value = null
        }

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