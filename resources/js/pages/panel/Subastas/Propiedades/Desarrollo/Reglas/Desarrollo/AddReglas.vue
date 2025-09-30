<template>
    <Toast />
    <Toolbar class="mb-6">
        <template #start>
            <Button label="Nuevo" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openDialog" />
        </template>
        <template #end>
        </template>
    </Toolbar>
    <Dialog v-model:visible="visible" modal header="Reglas del inmueble" :style="{ width: '1100px' }">
        <div class="flex flex-col gap-6">
            <!-- Propiedad (fila completa) -->
            <div>
                <label class="block font-semibold mb-2">Propiedad <span class="text-red-500">*</span></label>
                <Select v-model="propiedadSeleccionada" 
                    :options="propiedades" 
                    :style="{ width: '100%' }" 
                    :disabled="camposGeneralesBloqueados"
                    editable
                    optionLabel="label" 
                    optionValue="value" 
                    showClear 
                    placeholder="Buscar propiedad..."
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
                    <Select v-model="cronograma" 
                        :options="cronogramaOpciones" 
                        :disabled="camposGeneralesBloqueados"
                        optionLabel="label" 
                        optionValue="value"
                        placeholder="Seleccionar tipo..." 
                        class="w-full" />
                </div>

                <!-- Plazo -->
                <div>
                    <label class="block font-semibold mb-2">
                        Plazo del crédito <span class="text-red-500">*</span>
                    </label>
                    <Select v-model="deadlines_id" 
                        :options="plazos" 
                        :disabled="camposGeneralesBloqueados"
                        optionLabel="nombre" 
                        optionValue="id"
                        placeholder="Seleccione un plazo" 
                        class="w-full" />
                </div>
            </div>
            <!-- SECCIÓN INVERSIONISTA -->
            <Message severity="info" :closable="false" class="fluid">
                <template #messageicon>
                    <i class="pi pi-chart-line"></i>
                </template>
                <template #default>
                    <h3 class="text-lg font-semibold mb-4">
                        Configuración para Inversionista
                    </h3>
                    <div class="flex items-end gap-6 flex-wrap">
                        <!-- TEM Inversionista -->
                        <div>
                            <label class="font-semibold mb-2 block">
                                TEM Inversionista (%) <span class="text-red-500">*</span>
                            </label>
                            <InputNumber v-model="temInversionista" 
                                @update:modelValue="calcularTeaDesdeTemInversionista"
                                :disabled="inversionistaGuardado"
                                fluid
                                placeholder="Ej. 1.05" 
                                :minFractionDigits="3"
                                :maxFractionDigits="3"
                                suffix="%" 
                                :min="0" 
                                :max="20" />
                        </div>

                        <!-- TEA Inversionista -->
                        <div>
                            <label class="font-semibold mb-2 block">
                                TEA Inversionista (%) <span class="text-red-500">*</span>
                            </label>
                            <InputNumber v-model="teaInversionista" 
                                @update:modelValue="calcularTemDesdeTeaInversionista"
                                :disabled="inversionistaGuardado"
                                fluid
                                placeholder="Ej. 12.5" 
                                :minFractionDigits="3"
                                :maxFractionDigits="3"
                                suffix="%" 
                                :min="0" 
                                :max="100" />
                        </div>

                        <!-- Botones -->
                        <Button label="Previsualizar cronograma" 
                            icon="pi pi-eye" 
                            severity="info" 
                            outlined
                            :disabled="!canPreviewCronogramaInversionista" 
                            @click="previsualizarCronograma('inversionista')" />

                        <Button label="Guardar Inversionista" 
                            icon="pi pi-check" 
                            severity="contrast"
                            :disabled="!canSaveInversionista || inversionistaGuardado" 
                            @click="actualizarPropiedad(1)" />
                    </div>

                </template>
            </Message>

            <!-- SECCIÓN CLIENTE -->
            <Message severity="success" :closable="false" class="fluid mt-6">
                <template #messageicon>
                    <i class="pi pi-user"></i>
                </template>
                <template #default>
                    <h3 class="text-lg font-semibold mb-4">
                        Configuración para Cliente
                    </h3>

                    <div class="flex items-end gap-6 flex-wrap">
                    <!-- TEM Cliente -->
                    <div>
                        <label class="font-semibold mb-2 block">
                            TEM Cliente (%) <span class="text-red-500">*</span>
                        </label>
                        <InputNumber v-model="temCliente" 
                            @update:modelValue="calcularTeaDesdeTemCliente"
                            :disabled="clienteGuardado"
                            fluid
                            placeholder="Ej. 1.05" 
                            :minFractionDigits="3"
                            :maxFractionDigits="3"
                            suffix="%" 
                            :min="0" 
                            :max="20" />
                    </div>

                    <!-- TEA Cliente -->
                    <div>
                        <label class="font-semibold mb-2 block">
                            TEA Cliente (%) <span class="text-red-500">*</span>
                        </label>
                        <InputNumber v-model="teaCliente" 
                            @update:modelValue="calcularTemDesdeTeaCliente"
                            :disabled="clienteGuardado"
                            fluid
                            placeholder="Ej. 15.0" 
                            :minFractionDigits="3"
                            :maxFractionDigits="3"
                            suffix="%" 
                            :min="0" 
                            :max="100" />
                    </div>

                    <!-- Riesgo Cliente con imágenes -->
                    <div>
                        <label class="font-semibold mb-2 block">
                            Riesgo Cliente <span class="text-red-500">*</span>
                        </label>
                        <Select v-model="riesgoCliente" 
                            :options="riesgos" 
                            :disabled="clienteGuardado"
                            optionLabel="label" 
                            optionValue="value"
                            placeholder="Seleccionar riesgo..." 
                            fluid>
                            <template #value="{ value }">
                                <div v-if="value" class="flex items-center gap-2">
                                    <img :src="`/imagenes/riesgos/${value}.png`" 
                                        :alt="`Riesgo ${value}`" 
                                        class="w-6 h-6 object-contain" />
                                </div>
                            </template>
                            <template #option="{ option }">
                                <div class="flex items-center gap-2">
                                    <img :src="`/imagenes/riesgos/${option.value}.png`" 
                                        :alt="`Riesgo ${option.value}`" 
                                        class="w-6 h-6 object-contain" />
                                </div>
                            </template>
                        </Select>
                    </div>
                    <!-- Botones -->
                    <Button label="Previsualizar cronograma" 
                        icon="pi pi-eye" 
                        severity="info" 
                        outlined
                        :disabled="!canPreviewCronogramaCliente" 
                        @click="previsualizarCronograma('cliente')" />

                    <Button label="Guardar Cliente" 
                        icon="pi pi-check" 
                        severity="contrast"
                        :disabled="!canSaveCliente || clienteGuardado" 
                        @click="actualizarPropiedad(2)" />
                </div>

                </template>
            </Message>

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
import VerCronograma from './showCronograma.vue'
import Message from 'primevue/message'

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

// Campos específicos para cliente
const temCliente = ref(null)
const teaCliente = ref(null)
const riesgoCliente = ref(null)

const tipoUsuarioActual = ref(null)

// Estados de guardado
const inversionistaGuardado = ref(false)
const clienteGuardado = ref(false)

// Computed para determinar si los campos generales deben estar bloqueados
const camposGeneralesBloqueados = computed(() => {
    return inversionistaGuardado.value || clienteGuardado.value
})

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
    if (actualizandoInversionista.value || inversionistaGuardado.value) return
    
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
    if (actualizandoInversionista.value || inversionistaGuardado.value) return
    
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
    if (actualizandoCliente.value || clienteGuardado.value) return
    
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
    if (actualizandoCliente.value || clienteGuardado.value) return
    
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
    temCliente.value = null
    teaCliente.value = null
    riesgoCliente.value = null
    propiedadSeleccionadaData.value = null
    propiedades.value = []
    tipoUsuarioActual.value = null
    actualizandoInversionista.value = false
    actualizandoCliente.value = false
    // Resetear estados de guardado
    inversionistaGuardado.value = false
    clienteGuardado.value = false
}

const parametrosCronograma = computed(() => {
    const plazoSeleccionado = plazos.value.find(p => p.id === deadlines_id.value)
    const propiedadData = propiedades.value.find(p => p.value === propiedadSeleccionada.value)
    const valorRequerido = propiedadData ? parseFloat(propiedadData.valor_requerido) : 0

    const teaActual = tipoUsuarioActual.value === 'inversionista' ? teaInversionista.value : teaCliente.value
    const temActual = tipoUsuarioActual.value === 'inversionista' ? temInversionista.value : temCliente.value
    const riesgoActual = tipoUsuarioActual.value === 'cliente' ? riesgoCliente.value : null

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
        teaInversionista.value !== null && teaInversionista.value !== ''
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
        teaInversionista.value !== null && teaInversionista.value !== ''
})

// Computed para validar guardado cliente
const canSaveCliente = computed(() => {
    return camposGenerales.value && 
        temCliente.value !== null && temCliente.value !== '' &&
        teaCliente.value !== null && teaCliente.value !== '' &&
        riesgoCliente.value !== null
})

const cronogramaOpciones = [
    { label: 'Cuota Fija', value: 'frances' },
    { label: 'Libre Amortización', value: 'americano' }
]

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
    if (typeof valor === 'string' && !camposGeneralesBloqueados.value) {
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
    const riesgo = tipoUsuario === 2 ? riesgoCliente.value : null

    // Para inversionista no se requiere riesgo
    const riesgoRequerido = tipoUsuario === 2 ? riesgo : true
    
    if (!tea || !tem || !deadlines_id.value || !riesgoRequerido || !cronograma.value) {
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
        const payload = {
            tea: tea,
            tem: tem,
            deadlines_id: deadlines_id.value,
            tipo_cronograma: cronograma.value,
            estado_configuracion: tipoUsuario,
        }

        // Solo agregar riesgo si es cliente
        if (tipoUsuario === 2) {
            payload.riesgo = riesgo
        }

        const response = await axios.put(`/property/${propiedadSeleccionada.value}/estado`, payload)

        const tipoTexto = tipoUsuario === 1 ? 'inversionista' : 'cliente'
        toast.add({
            severity: 'success',
            summary: 'Actualizado',
            detail: `Configuración para ${tipoTexto} guardada exitosamente`,
            life: 3000
        })

        // Marcar como guardado según el tipo de usuario
        if (tipoUsuario === 1) {
            inversionistaGuardado.value = true
        } else {
            clienteGuardado.value = true
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
const riesgos = [
    { label: 'Amas', value: 'Amas' },
    { label: 'A', value: 'A' },
    { label: 'B', value: 'B' },
    { label: 'C', value: 'C' }
]
</script>