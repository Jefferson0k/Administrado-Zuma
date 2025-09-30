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

    <Dialog v-model:visible="visible" maximizable modal header="Solicitud de Financiamiento" :style="{ width: '75rem' }"
        :breakpoints="{ '1199px': '90vw', '575px': '95vw' }">

        <!-- Stepper Principal -->
        <Stepper v-model:value="pasoActual" class="basis-50rem">
            <!-- Lista de Pasos -->
            <StepList>
                <StepItem value="0">
                    <Step>Propiedad</Step>
                </StepItem>
                <StepItem value="1">
                    <Step>Tasación</Step>
                </StepItem>
                <StepItem value="2">
                    <Step>Detalles</Step>
                </StepItem>
                <StepItem value="3">
                    <Step>Resumen</Step>
                </StepItem>
            </StepList>

            <!-- Paneles de Contenido -->
            <StepPanels>
                <!-- PANEL 1: Selección de Propiedad -->
                <StepPanel value="0">
                    <div class="flex flex-column h-12rem">
                        <div class="flex-auto flex justify-content-center align-items-center font-medium">
                            <div class="w-full">
                                <div class="field">
                                    <label class="font-semibold text-900 mb-3 block text-lg">
                                        Buscar Propiedad <span class="text-red-500">*</span>
                                    </label>
                                    <Select v-model="propiedadSeleccionada" :options="propiedades" optionLabel="label" showClear
                                        editable size="large" class="w-full" placeholder="Escribe para buscar una propiedad..."
                                        @update:modelValue="onPropiedadChange">
                                        <template #value="{ value, placeholder }">
                                            <span v-if="value" class="font-medium">{{ value.label }}</span>
                                            <span v-else class="text-500">{{ placeholder }}</span>
                                        </template>
                                        <template #option="{ option }">
                                            <div class="flex justify-content-between align-items-start w-full p-3 border-round">
                                                <div class="flex-1">
                                                    <div class="font-bold text-900 text-lg">{{ option.nombre }}</div>
                                                    <div class="text-sm text-600 mt-2 flex align-items-center gap-1">
                                                        <i class="pi pi-map-marker text-primary"></i>
                                                        {{ option.departamento }}, {{ option.provincia }}, {{ option.distrito }}
                                                    </div>
                                                    <div class="text-sm text-500 mt-1">{{ option.direccion }}</div>
                                                    <div class="text-lg text-primary font-bold mt-2 flex align-items-center gap-1">
                                                        {{ formatearValor(option.valor_estimado) }}
                                                    </div>
                                                </div>
                                                <Tag :value="option.estado_property"
                                                    :severity="getEstadoSeverity(option.estado_property)" class="ml-3 text-sm" />
                                            </div>
                                        </template>

                                        <template #empty>
                                            <div class="text-center p-6 text-500">
                                                <i class="pi pi-search mb-3 text-4xl"></i>
                                                <div class="text-lg">No se encontraron propiedades</div>
                                                <div class="text-sm">Intenta con otro término de búsqueda</div>
                                            </div>
                                        </template>
                                    </Select>
                                </div>
                                
                                <!-- Información del Cliente Vinculado -->
                                <div v-if="clienteVinculado" class="mt-4">
                                    <Message severity="info" class="w-full">
                                        <template #messageicon>
                                            <i class="pi pi-user text-blue-600"></i>
                                        </template>
                                        <template #default>
                                            <div class="flex align-items-center gap-4">
                                                <Avatar icon="pi pi-user" size="large" class="bg-blue-600" />
                                                <div>
                                                    <div class="font-bold text-900 text-lg">
                                                        {{ clienteVinculado.investor_name }}
                                                        {{ clienteVinculado.investor_first_last_name }}
                                                        {{ clienteVinculado.investor_second_last_name }}
                                                    </div>
                                                    <div class="text-600 flex align-items-center gap-2 mt-1">
                                                        <i class="pi pi-id-card"></i>
                                                        DNI: {{ clienteVinculado.investor_document }}
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </Message>
                                </div>
                            </div>
                        </div>
                    </div>
                </StepPanel>

                <!-- PANEL 2: Información de Tasación -->
                <StepPanel value="1">
                    <div class="flex flex-column h-12rem">
                        <div class="flex-auto flex justify-content-center align-items-center font-medium">
                            <div class="w-full">
                                <!-- Empresa Tasadora -->
                                <div class="field mb-5">
                                    <label class="font-semibold text-900 mb-3 block text-lg">
                                        Empresa Tasadora <span class="text-red-500">*</span>
                                    </label>
                                    <Textarea v-model="form.empresa_tasadora" autoResize rows="3" class="w-full text-lg"
                                        :maxlength="150" placeholder="Ingresa el nombre de la empresa que realizó la tasación..." />
                                    <small class="text-500 float-right mt-2">
                                        {{ form.empresa_tasadora.length }}/150 caracteres
                                    </small>
                                </div>

                                <!-- Datos Financieros -->
                                <div class="grid mt-6">
                                    <!-- Monto Tasación -->
                                    <div class="col-12 md:col-6 mb-4">
                                        <label for="monto_tasacion" class="font-semibold block mb-2">
                                            Monto Tasación *
                                        </label>
                                        <InputNumber id="monto_tasacion" v-model="form.monto_tasacion" mode="currency" currency="PEN"
                                            locale="es-PE" class="w-full" size="large" />
                                    </div>

                                    <!-- Porcentaje para Préstamo -->
                                    <div class="col-12 md:col-6 mb-4">
                                        <label for="porcentaje_prestamo" class="font-semibold block mb-2">
                                            % para Préstamo *
                                        </label>
                                        <InputNumber id="porcentaje_prestamo" v-model="form.porcentaje_prestamo" suffix="%" :min="1"
                                            :max="100" class="w-full" size="large" @input="calcularMontos" />
                                    </div>

                                    <!-- Automático: Monto a Invertir -->
                                    <div class="col-12 md:col-6 mb-4">
                                        <label for="monto_invertir" class="font-semibold block mb-2">
                                            Monto a Invertir *
                                        </label>
                                        <InputNumber id="monto_invertir" v-model="form.monto_invertir" mode="currency" currency="PEN"
                                            locale="es-PE" disabled fluid />
                                    </div>

                                    <!-- Automático: Monto del Préstamo -->
                                    <div class="col-12 md:col-6 mb-4">
                                        <label for="monto_prestamo" class="font-semibold block mb-2">
                                            Monto del Préstamo *
                                        </label>
                                        <InputNumber id="monto_prestamo" v-model="form.monto_prestamo" mode="currency" currency="PEN"
                                            locale="es-PE" disabled fluid />
                                    </div>
                                </div>

                                <!-- Información adicional sobre cálculos -->
                                <Message v-if="form.monto_tasacion && form.porcentaje_prestamo" severity="success" class="w-full mt-4">
                                    <template #messageicon>
                                        <i class="pi pi-calculator text-green-600"></i>
                                    </template>

                                    <template #default>
                                        <h6 class="font-bold text-900 mb-2 flex align-items-center gap-2">
                                            Cálculos Automáticos
                                        </h6>
                                        <div class="text-sm text-600 mb-2">
                                            Los montos se calculan automáticamente basados en el porcentaje ingresado.
                                        </div>

                                        <div v-if="propiedadSeleccionada?.valor_estimado?.currency === 'USD'"
                                            class="text-xs text-orange-600 bg-orange-50 p-2 border-round mt-2">
                                            <i class="pi pi-info-circle mr-1"></i>
                                            Nota: La propiedad está valorizada en dólares
                                            ({{ formatearValor(propiedadSeleccionada.valor_estimado) }}).
                                            La tasación se convirtió a soles usando una tasa referencial.
                                        </div>
                                    </template>
                                </Message>
                            </div>
                        </div>
                    </div>
                </StepPanel>

                <!-- PANEL 3: Detalles del Préstamo -->
                <StepPanel value="2">
                    <div class="flex flex-column h-12rem">
                        <div class="border-2 border-dashed surface-border border-round surface-ground flex-auto flex justify-content-center align-items-center font-medium p-4">
                            <div class="w-full">
                                <div class="grid">
                                    <!-- Ocupación/Profesión -->
                                    <!-- <div class="col-12 md:col-6 field">
                                        <label class="font-semibold text-900 mb-3 block text-lg">
                                            Ocupación/Profesión <span class="text-red-500">*</span>
                                        </label>
                                        <Textarea v-model="form.ocupacion_profesion" autoResize rows="3" class="w-full"
                                            :maxlength="200"
                                            placeholder="Ej: Ingeniero Civil, Comerciante, Profesional independiente..." />
                                        <small class="text-500 float-right mt-2">{{ form.ocupacion_profesion.length }}/200
                                            caracteres</small>
                                    </div> -->
                                    <!-- Motivo del Préstamo -->
                                    <div class="col-12 md:col-6 field">
                                        <label class="font-semibold text-900 mb-3 block text-lg">
                                            Motivo del Préstamo <span class="text-red-500">*</span>
                                        </label>
                                        <Textarea v-model="form.motivo_prestamo" autoResize rows="3" class="w-full"
                                            :maxlength="300"
                                            placeholder="Describe el motivo principal para solicitar el préstamo..." />
                                        <small class="text-500 float-right mt-2">{{ form.motivo_prestamo.length }}/300
                                            caracteres</small>
                                    </div>
                                    <!-- Descripción del Financiamiento -->
                                    <div class="col-12 field">
                                        <label class="font-semibold text-900 mb-3 block text-lg">
                                            Descripción del Financiamiento <span class="text-red-500">*</span>
                                        </label>
                                        <Textarea v-model="form.descripcion_financiamiento" autoResize rows="4" class="w-full"
                                            :maxlength="500"
                                            placeholder="Detalla las características del financiamiento solicitado..." />
                                        <small class="text-500 float-right mt-2">{{ form.descripcion_financiamiento.length
                                        }}/500 caracteres</small>
                                    </div>
                                    <!-- Descripción del Financiamiento -->
                                    <div class="col-12 field">
                                        <label class="font-semibold text-900 mb-3 block text-lg">
                                            Sobre la garantia <span class="text-red-500">*</span>
                                        </label>
                                        <Textarea v-model="form.solicitud_prestamo_para" autoResize rows="4" class="w-full"
                                            :maxlength="500"
                                            placeholder="Detalla las características del financiamiento solicitado..." />
                                        <small class="text-500 float-right mt-2">{{ form.solicitud_prestamo_para.length
                                        }}/500 caracteres</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </StepPanel>

                <!-- PANEL 4: Resumen -->
                <StepPanel value="3">
                    <div class="flex flex-column h-12rem">
                        <div class="border-2 border-dashed surface-border border-round surface-ground flex-auto flex justify-content-center align-items-center font-medium p-4">
                            <div class="w-full">
                                <!-- Propiedad Seleccionada -->
                                <div class="mb-4">
                                    <div class="p-4 bg-gradient-to-r from-blue-50 to-cyan-50 border-round-lg border-2 border-blue-200">
                                        <h6 class="font-bold text-900 mb-3 flex align-items-center gap-2">
                                            <i class="pi pi-home text-blue-600"></i>
                                            Propiedad Seleccionada
                                        </h6>
                                        <div class="text-900 font-semibold text-lg">{{ propiedadSeleccionada?.nombre }}</div>
                                        <div class="text-600">{{ propiedadSeleccionada?.departamento }}, {{ propiedadSeleccionada?.provincia }}</div>
                                        <div class="text-primary font-bold text-lg mt-2">{{ formatearValor(propiedadSeleccionada?.valor_estimado) }}</div>
                                    </div>
                                </div>

                                <div class="grid">
                                    <!-- Datos Financieros -->
                                    <div class="col-12 md:col-6 mb-4">
                                        <div class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 border-round-lg border-2 border-green-200">
                                            <h6 class="font-bold text-900 mb-3 flex align-items-center gap-2">
                                                <i class="pi pi-calculator text-green-600"></i>
                                                Datos Financieros
                                            </h6>
                                            <div class="text-sm">
                                                <div class="flex justify-content-between mb-2">
                                                    <span>Valor Original Propiedad:</span>
                                                    <span class="font-semibold">{{ formatearValor(propiedadSeleccionada?.valor_estimado) }}</span>
                                                </div>
                                                <div class="flex justify-content-between mb-2">
                                                    <span>Monto Tasación:</span>
                                                    <span class="font-semibold">S/. {{ form.monto_tasacion?.toLocaleString() }}</span>
                                                </div>
                                                <div class="flex justify-content-between mb-2">
                                                    <span>% para Préstamo:</span>
                                                    <span class="font-semibold">{{ form.porcentaje_prestamo }}%</span>
                                                </div>
                                                <div class="flex justify-content-between mb-2">
                                                    <span>Monto del Préstamo:</span>
                                                    <span class="font-semibold text-primary">S/. {{ form.monto_prestamo?.toLocaleString() }}</span>
                                                </div>
                                                <div class="flex justify-content-between">
                                                    <span>Monto a Invertir:</span>
                                                    <span class="font-semibold">S/. {{ form.monto_invertir?.toLocaleString() }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Empresa Tasadora -->
                                    <div class="col-12 md:col-6 mb-4">
                                        <div class="p-4 bg-gradient-to-r from-purple-50 to-pink-50 border-round-lg border-2 border-purple-200">
                                            <h6 class="font-bold text-900 mb-3 flex align-items-center gap-2">
                                                <i class="pi pi-building text-purple-600"></i>
                                                Empresa Tasadora
                                            </h6>
                                            <div class="text-900">{{ form.empresa_tasadora }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Detalles del Préstamo -->
                                <div class="grid">
                                    <!-- <div class="col-12 md:col-6 mb-4">
                                        <div class="p-4 bg-gradient-to-r from-orange-50 to-yellow-50 border-round-lg border-2 border-orange-200">
                                            <h6 class="font-bold text-900 mb-3 flex align-items-center gap-2">
                                                <i class="pi pi-briefcase text-orange-600"></i>
                                                Ocupación/Profesión
                                            </h6>
                                            <div class="text-900">{{ form.ocupacion_profesion }}</div>
                                        </div>
                                    </div> -->

                                    <div class="col-12 md:col-6 mb-4">
                                        <div class="p-4 bg-gradient-to-r from-teal-50 to-cyan-50 border-round-lg border-2 border-teal-200">
                                            <h6 class="font-bold text-900 mb-3 flex align-items-center gap-2">
                                                <i class="pi pi-question-circle text-teal-600"></i>
                                                Motivo del Préstamo
                                            </h6>
                                            <div class="text-900">{{ form.motivo_prestamo }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Descripción del Financiamiento -->
                                <div class="mb-4">
                                    <div class="p-4 bg-gradient-to-r from-indigo-50 to-purple-50 border-round-lg border-2 border-indigo-200">
                                        <h6 class="font-bold text-900 mb-3 flex align-items-center gap-2">
                                            <i class="pi pi-file-edit text-indigo-600"></i>
                                            Descripción del Financiamiento
                                        </h6>
                                        <div class="text-900">{{ form.descripcion_financiamiento }}</div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="p-4 bg-gradient-to-r from-indigo-50 to-purple-50 border-round-lg border-2 border-indigo-200">
                                        <h6 class="font-bold text-900 mb-3 flex align-items-center gap-2">
                                            <i class="pi pi-file-edit text-indigo-600"></i>
                                            Sobre la garantia
                                        </h6>
                                        <div class="text-900">{{ form.solicitud_prestamo_para }}</div>
                                    </div>
                                </div>

                                <!-- Confirmación Final -->
                                <div class="p-4 bg-gradient-to-r from-orange-50 to-yellow-50 border-round-lg border-2 border-orange-200 mt-4">
                                    <div class="flex align-items-center gap-3">
                                        <i class="pi pi-exclamation-triangle text-orange-600 text-2xl"></i>
                                        <div>
                                            <div class="font-bold text-900">¿Confirmas que toda la información es correcta?</div>
                                            <div class="text-600 text-sm">Una vez guardada, podrás editarla posteriormente si es necesario.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </StepPanel>
            </StepPanels>
        </Stepper>

        <!-- Botones de navegación -->
        <template #footer>
            <div class="flex justify-content-between align-items-center p-4">
                <Button v-if="pasoActual > 0" label="Anterior" icon="pi pi-arrow-left" severity="secondary" outlined
                    text @click="pasoAnterior" />
                <div v-else></div>

                <div class="flex gap-2">
                    <Button label="Cancelar" icon="pi pi-times" severity="secondary" text
                        @click="cerrarDialog" />
                    <Button v-if="pasoActual < 3" :label="siguienteLabel" icon="pi pi-arrow-right"
                        iconPos="right" severity="contrast"  :disabled="!puedeAvanzar"
                        @click="pasoSiguiente" />
                    <Button v-else label="Guardar Solicitud" icon="pi pi-check" severity="contrast"
                        :loading="guardando" :disabled="!formularioCompleto" @click="guardarFormulario" />
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import Toolbar from 'primevue/toolbar'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import Dialog from 'primevue/dialog'
import Stepper from 'primevue/stepper'
import StepList from 'primevue/steplist'
import StepPanels from 'primevue/steppanels'
import StepItem from 'primevue/stepitem'
import Step from 'primevue/step'
import StepPanel from 'primevue/steppanel'
import Textarea from 'primevue/textarea'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Avatar from 'primevue/avatar'
import Message from 'primevue/message'
import { useToast } from 'primevue/usetoast'
import { debounce } from 'lodash'
import axios from 'axios'

const toast = useToast()
const visible = ref(false)
const pasoActual = ref('0') // Stepper usa strings para los valores
const guardando = ref(false)

const propiedadSeleccionada = ref(null)
const propiedades = ref([])
const clienteVinculado = ref(null)

const form = ref({
    // ocupacion_profesion: '',
    empresa_tasadora: '',
    monto_tasacion: null,
    porcentaje_prestamo: null,
    monto_invertir: null,
    monto_prestamo: null,
    motivo_prestamo: '',
    descripcion_financiamiento: '',
    solicitud_prestamo_para: '',
})

// Watch para recalcular automáticamente cuando cambien los valores o la propiedad
watch([() => form.value.monto_tasacion, () => form.value.porcentaje_prestamo, () => propiedadSeleccionada.value], () => {
    calcularMontos()
})

// Watch para actualizar la tasación cuando se seleccione una propiedad
watch(() => propiedadSeleccionada.value, (nuevaPropiedad) => {
    if (nuevaPropiedad?.valor_estimado) {
        // Si es USD, convertir a PEN (usando tasa aproximada de 3.8)
        const tasaUSDtoPEN = 3.8
        const valorDecimal = parseFloat(nuevaPropiedad.valor_estimado.decimal)

        if (nuevaPropiedad.valor_estimado.currency === 'USD') {
            form.value.monto_tasacion = Math.round(valorDecimal * tasaUSDtoPEN)
        } else {
            form.value.monto_tasacion = valorDecimal
        }
    }
})

// Computed properties
const siguienteLabel = computed(() => {
    const labels = ['Continuar con Tasación', 'Continuar con Detalles', 'Ver Resumen']
    return labels[parseInt(pasoActual.value)] || 'Siguiente'
})

const puedeAvanzar = computed(() => {
    const paso = parseInt(pasoActual.value)
    switch (paso) {
        case 0: 
            return propiedadSeleccionada.value !== null
        case 1: 
            return form.value.empresa_tasadora.trim() &&
                   form.value.monto_tasacion &&
                   form.value.porcentaje_prestamo &&
                   form.value.monto_invertir &&
                   form.value.monto_prestamo
        case 2: 
            // return form.value.ocupacion_profesion.trim() &&
                   form.value.motivo_prestamo.trim() &&
                   form.value.descripcion_financiamiento.trim() &&
                   form.value.solicitud_prestamo_para.trim()
        default: 
            return true
    }
})

const formularioCompleto = computed(() => {
    return propiedadSeleccionada.value &&
        // form.value.ocupacion_profesion.trim() &&
        form.value.empresa_tasadora.trim() &&
        form.value.monto_tasacion &&
        form.value.porcentaje_prestamo &&
        form.value.monto_invertir &&
        form.value.monto_prestamo &&
        form.value.motivo_prestamo.trim() &&
        form.value.descripcion_financiamiento.trim() &&
        form.value.solicitud_prestamo_para
})

const calcularMontos = () => {
    if (form.value.monto_tasacion && form.value.porcentaje_prestamo) {
        const porcentajeDecimal = form.value.porcentaje_prestamo / 100
        form.value.monto_prestamo = Math.round(form.value.monto_tasacion * porcentajeDecimal)
        form.value.monto_invertir = form.value.monto_tasacion - form.value.monto_prestamo
    } else {
        form.value.monto_prestamo = null
        form.value.monto_invertir = null
    }
}

// Función para formatear el valor estimado
const formatearValor = (valorEstimado: any) => {
    if (!valorEstimado) return 'No disponible'

    const moneda = valorEstimado.currency
    const decimal = parseFloat(valorEstimado.decimal)

    if (moneda === 'USD') {
        return `US$ ${decimal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
    } else if (moneda === 'PEN') {
        return `S/. ${decimal.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
    } else {
        return `${moneda} ${decimal.toLocaleString()}`
    }
}

// Navegación
const pasoSiguiente = () => {
    const pasoNumerico = parseInt(pasoActual.value)
    if (puedeAvanzar.value && pasoNumerico < 3) {
        pasoActual.value = (pasoNumerico + 1).toString()
    }
}

const pasoAnterior = () => {
    const pasoNumerico = parseInt(pasoActual.value)
    if (pasoNumerico > 0) {
        pasoActual.value = (pasoNumerico - 1).toString()
    }
}

const openDialog = () => {
    visible.value = true
    resetForm()
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

const onPropiedadChange = (valor: any) => {
    if (typeof valor === 'string') {
        buscarPropiedades(valor)
        clienteVinculado.value = null
    } else if (valor) {
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

const getEstadoSeverity = (estado: string) => {
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

const buscarPropiedades = debounce(async (texto: string) => {
    if (!texto || texto.length < 2) {
        propiedades.value = []
        return
    }

    try {
        const response = await axios.get("/propiedades/activas", {
            params: { search: texto },
        })

        propiedades.value = response.data.data.map((propiedad: any) => ({
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
            tea: propiedad.tea,
            tem: propiedad.tem,
            moneda: propiedad.moneda,
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
    if (!formularioCompleto.value) {
        toast.add({
            severity: "warn",
            summary: "Formulario Incompleto",
            detail: "Por favor, completa todos los campos obligatorios",
            life: 4000,
        })
        return
    }

    guardando.value = true

    try {
        const payload = {
            property_id: propiedadSeleccionada.value.value,
            config_id: propiedadSeleccionada.value.config_id,
            investor_id: clienteVinculado.value?.cliente_id,
            ...form.value
        }

        await axios.post('/property-loan-details', payload)

        toast.add({
            severity: 'success',
            summary: '¡Éxito!',
            detail: 'Solicitud de financiamiento guardada correctamente',
            life: 4000
        })

        cerrarDialog()
    } catch (error: any) {
        console.error('Error al guardar:', error)
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Error al guardar la solicitud',
            life: 4000
        })
    } finally {
        guardando.value = false
    }
}

const resetForm = () => {
    form.value = {
        // ocupacion_profesion: '',
        empresa_tasadora: '',
        monto_tasacion: null,
        porcentaje_prestamo: null,
        monto_invertir: null,
        monto_prestamo: null,
        motivo_prestamo: '',
        descripcion_financiamiento: '',
        solicitud_prestamo_para: '',
    }
    propiedadSeleccionada.value = null
    clienteVinculado.value = null
    propiedades.value = []
    pasoActual.value = '0'
}
</script>
