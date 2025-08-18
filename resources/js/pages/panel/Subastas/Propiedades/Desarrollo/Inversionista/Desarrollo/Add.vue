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

    <Dialog v-model:visible="visible" maximizable modal header="Solicitud de Financiamiento"
        :style="{ width: '70rem' }" :breakpoints="{ '1199px': '90vw', '575px': '95vw' }">
        
        <!-- Steps para navegación -->
        <Steps :model="pasos" :activeIndex="pasoActual" class="mb-6" />

        <!-- Contenido dinámico por paso -->
        <div class="min-h-20rem">
            <!-- PASO 1: Selección de Propiedad -->
            <div v-if="pasoActual === 0" class="fade-in">
                <Card class="shadow-2 border-round-xl">
                    <template #title>
                        <div class="flex align-items-center gap-2 text-2xl text-primary">
                            <i class="pi pi-home"></i>
                            Selecciona la Propiedad
                        </div>
                    </template>
                    <template #content>
                        <div class="field">
                            <label class="font-semibold text-900 mb-3 block text-lg">
                                Buscar Propiedad <span class="text-red-500">*</span>
                            </label>
                            <Select 
                                v-model="propiedadSeleccionada" 
                                :options="propiedades" 
                                optionLabel="label" 
                                showClear 
                                editable
                                size="large"
                                class="w-full" 
                                placeholder="Escribe para buscar una propiedad..." 
                                @update:modelValue="onPropiedadChange"
                            >
                                <template #value="{ value, placeholder }">
                                    <span v-if="value" class="font-medium">{{ value.label }}</span>
                                    <span v-else class="text-500">{{ placeholder }}</span>
                                </template>
                                <template #option="{ option }">
                                    <div class="flex justify-content-between align-items-start w-full p-3 border-round hover:bg-primary-50 transition-colors">
                                        <div class="flex-1">
                                            <div class="font-bold text-900 text-lg">{{ option.nombre }}</div>
                                            <div class="text-sm text-600 mt-2 flex align-items-center gap-1">
                                                <i class="pi pi-map-marker text-primary"></i>
                                                {{ option.departamento }}, {{ option.provincia }}, {{ option.distrito }}
                                            </div>
                                            <div class="text-sm text-500 mt-1">{{ option.direccion }}</div>
                                            <div class="text-lg text-primary font-bold mt-2 flex align-items-center gap-1">
                                                <i class="pi pi-dollar"></i>
                                                {{ formatearValor(option.valor_estimado) }}
                                            </div>
                                        </div>
                                        <Tag
                                            :value="option.estado_property"
                                            :severity="getEstadoSeverity(option.estado_property)"
                                            class="ml-3 text-sm"
                                        />
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
                            <div class="p-4 bg-gradient-to-r from-blue-50 to-cyan-50 border-round-lg border-2 border-blue-200">
                                <h6 class="font-bold text-900 mb-3 text-lg flex align-items-center gap-2">
                                    <i class="pi pi-user text-blue-600"></i>
                                    Cliente Vinculado
                                </h6>
                                <div class="flex align-items-center gap-4">
                                    <Avatar icon="pi pi-user" size="large" class="bg-blue-600" />
                                    <div>
                                        <div class="font-bold text-900 text-lg">
                                            {{ clienteVinculado.investor_name }} {{ clienteVinculado.investor_first_last_name }} {{ clienteVinculado.investor_second_last_name }}
                                        </div>
                                        <div class="text-600 flex align-items-center gap-2 mt-1">
                                            <i class="pi pi-id-card"></i>
                                            DNI: {{ clienteVinculado.investor_document }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- PASO 2: Información de Tasación -->
            <div v-else-if="pasoActual === 1" class="fade-in">
                <Card class="shadow-2 border-round-xl">
                    <template #title>
                        <div class="flex align-items-center gap-2 text-2xl text-primary">
                            <i class="pi pi-building"></i>
                            Información de Tasación
                        </div>
                    </template>
                    <template #content>
                        <!-- Empresa Tasadora -->
                        <div class="field mb-5">
                            <label class="font-semibold text-900 mb-3 block text-lg">
                                Empresa Tasadora <span class="text-red-500">*</span>
                            </label>
                            <Textarea 
                                v-model="form.empresa_tasadora" 
                                autoResize 
                                rows="3" 
                                class="w-full text-lg" 
                                :maxlength="150"
                                placeholder="Ingresa el nombre de la empresa que realizó la tasación..."
                            />
                            <small class="text-500 float-right mt-2">{{ form.empresa_tasadora.length }}/150 caracteres</small>
                        </div>

                        <!-- Datos Financieros -->
                        <div class="grid mt-6">
                            <div class="col-12 md:col-6">
                                <FloatLabel class="mb-4">
                                    <InputNumber 
                                        id="monto_tasacion"
                                        v-model="form.monto_tasacion" 
                                        mode="currency" 
                                        currency="PEN" 
                                        locale="es-PE"
                                        class="w-full"
                                        size="large"
                                    />
                                    <label for="monto_tasacion" class="font-semibold">Monto Tasación *</label>
                                </FloatLabel>
                            </div>

                            <div class="col-12 md:col-6">
                                <FloatLabel class="mb-4">
                                    <InputNumber 
                                        id="porcentaje_prestamo"
                                        v-model="form.porcentaje_prestamo" 
                                        suffix="%" 
                                        :min="1" 
                                        :max="100"
                                        class="w-full"
                                        size="large"
                                        @input="calcularMontos"
                                    />
                                    <label for="porcentaje_prestamo" class="font-semibold">% para Préstamo *</label>
                                </FloatLabel>
                            </div>

                            <div class="col-12 md:col-6">
                                <FloatLabel class="mb-4">
                                    <InputNumber 
                                        id="monto_invertir"
                                        v-model="form.monto_invertir" 
                                        mode="currency" 
                                        currency="PEN" 
                                        locale="es-PE"
                                        class="w-full"
                                        size="large"
                                        :readonly="true"
                                    />
                                    <label for="monto_invertir" class="font-semibold">Monto a Invertir *</label>
                                </FloatLabel>
                            </div>

                            <div class="col-12 md:col-6">
                                <FloatLabel class="mb-4">
                                    <InputNumber 
                                        id="monto_prestamo"
                                        v-model="form.monto_prestamo" 
                                        mode="currency" 
                                        currency="PEN" 
                                        locale="es-PE"
                                        class="w-full"
                                        size="large"
                                        :readonly="true"
                                    />
                                    <label for="monto_prestamo" class="font-semibold">Monto del Préstamo *</label>
                                </FloatLabel>
                            </div>
                        </div>

                        <!-- Información adicional sobre cálculos -->
                        <div v-if="form.monto_tasacion && form.porcentaje_prestamo" 
                             class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 border-round-lg border-2 border-green-200 mt-4">
                            <h6 class="font-bold text-900 mb-2 flex align-items-center gap-2">
                                <i class="pi pi-calculator text-green-600"></i>
                                Cálculos Automáticos
                            </h6>
                            <div class="text-sm text-600 mb-2">
                                Los montos se calculan automáticamente basados en el porcentaje ingresado
                            </div>
                            <div v-if="propiedadSeleccionada?.valor_estimado?.currency === 'USD'" 
                                 class="text-xs text-orange-600 bg-orange-50 p-2 border-round mt-2">
                                <i class="pi pi-info-circle mr-1"></i>
                                Nota: La propiedad está valorizada en dólares ({{ formatearValor(propiedadSeleccionada.valor_estimado) }}). 
                                La tasación se convirtió a soles usando una tasa referencial.
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- PASO 3: Detalles del Préstamo -->
            <div v-else-if="pasoActual === 2" class="fade-in">
                <Card class="shadow-2 border-round-xl">
                    <template #title>
                        <div class="flex align-items-center gap-2 text-2xl text-primary">
                            <i class="pi pi-file-edit"></i>
                            Detalles del Financiamiento
                        </div>
                    </template>
                    <template #content>
                        <div class="grid">
                            <!-- Ocupación/Profesión -->
                            <div class="col-12 md:col-6 field">
                                <label class="font-semibold text-900 mb-3 block text-lg">
                                    Ocupación/Profesión <span class="text-red-500">*</span>
                                </label>
                                <Textarea 
                                    v-model="form.ocupacion_profesion" 
                                    autoResize 
                                    rows="3" 
                                    class="w-full" 
                                    :maxlength="200"
                                    placeholder="Ej: Ingeniero Civil, Comerciante, Profesional independiente..."
                                />
                                <small class="text-500 float-right mt-2">{{ form.ocupacion_profesion.length }}/200 caracteres</small>
                            </div>

                            <!-- Solicitud del Préstamo para -->
                            <div class="col-12 md:col-6 field">
                                <label class="font-semibold text-900 mb-3 block text-lg">
                                    Solicitud del Préstamo para <span class="text-red-500">*</span>
                                </label>
                                <Textarea 
                                    v-model="form.solicitud_prestamo_para" 
                                    autoResize 
                                    rows="3" 
                                    class="w-full" 
                                    :maxlength="250"
                                    placeholder="Especifica el destino o uso del préstamo..."
                                />
                                <small class="text-500 float-right mt-2">{{ form.solicitud_prestamo_para.length }}/250 caracteres</small>
                            </div>

                            <!-- Garantía -->
                            <div class="col-12 field">
                                <label class="font-semibold text-900 mb-3 block text-lg">
                                    Garantía <span class="text-red-500">*</span>
                                </label>
                                <Textarea 
                                    v-model="form.garantia" 
                                    autoResize 
                                    rows="3" 
                                    class="w-full" 
                                    :maxlength="250"
                                    placeholder="Describe la garantía ofrecida para el préstamo..."
                                />
                                <small class="text-500 float-right mt-2">{{ form.garantia.length }}/250 caracteres</small>
                            </div>

                            <!-- Motivo del Préstamo -->
                            <div class="col-12 field">
                                <label class="font-semibold text-900 mb-3 block text-lg">
                                    Motivo del Préstamo <span class="text-red-500">*</span>
                                </label>
                                <Textarea 
                                    v-model="form.motivo_prestamo" 
                                    autoResize 
                                    rows="3" 
                                    class="w-full" 
                                    :maxlength="300"
                                    placeholder="Describe el motivo principal para solicitar el préstamo..."
                                />
                                <small class="text-500 float-right mt-2">{{ form.motivo_prestamo.length }}/300 caracteres</small>
                            </div>

                            <!-- Descripción del Financiamiento -->
                            <div class="col-12 field">
                                <label class="font-semibold text-900 mb-3 block text-lg">
                                    Descripción del Financiamiento <span class="text-red-500">*</span>
                                </label>
                                <Textarea 
                                    v-model="form.descripcion_financiamiento" 
                                    autoResize 
                                    rows="4" 
                                    class="w-full" 
                                    :maxlength="500"
                                    placeholder="Detalla las características del financiamiento solicitado..."
                                />
                                <small class="text-500 float-right mt-2">{{ form.descripcion_financiamiento.length }}/500 caracteres</small>
                            </div>

                            <!-- Perfil del Riesgo -->
                            <div class="col-12 field">
                                <label class="font-semibold text-900 mb-3 block text-lg">
                                    Perfil del Riesgo <span class="text-red-500">*</span>
                                </label>
                                <Textarea 
                                    v-model="form.perfil_riesgo" 
                                    autoResize 
                                    rows="4" 
                                    class="w-full" 
                                    :maxlength="400"
                                    placeholder="Evalúa y describe el perfil de riesgo del solicitante..."
                                />
                                <small class="text-500 float-right mt-2">{{ form.perfil_riesgo.length }}/400 caracteres</small>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- PASO 4: Resumen -->
            <div v-else-if="pasoActual === 3" class="fade-in">
                <Card class="shadow-2 border-round-xl">
                    <template #title>
                        <div class="flex align-items-center gap-2 text-2xl text-primary">
                            <i class="pi pi-check-circle"></i>
                            Resumen de la Solicitud
                        </div>
                    </template>
                    <template #content>
                        <div class="grid">
                            <!-- Propiedad Seleccionada -->
                            <div class="col-12 mb-4">
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
                    </template>
                </Card>
            </div>
        </div>

        <!-- Botones de navegación -->
        <template #footer>
            <div class="flex justify-content-between align-items-center p-4">
                <Button 
                    v-if="pasoActual > 0"
                    label="Anterior" 
                    icon="pi pi-arrow-left" 
                    severity="secondary" 
                    outlined
                    size="large"
                    @click="pasoAnterior" 
                />
                <div v-else></div>

                <div class="flex gap-2">
                    <Button 
                        label="Cancelar" 
                        icon="pi pi-times" 
                        severity="secondary" 
                        text 
                        size="large"
                        @click="cerrarDialog" 
                    />
                    <Button 
                        v-if="pasoActual < pasos.length - 1"
                        :label="siguienteLabel"
                        icon="pi pi-arrow-right" 
                        iconPos="right"
                        severity="primary" 
                        size="large"
                        :disabled="!puedeAvanzar"
                        @click="pasoSiguiente"
                    />
                    <Button 
                        v-else
                        label="Guardar Solicitud" 
                        icon="pi pi-check" 
                        severity="success" 
                        size="large"
                        :loading="guardando"
                        :disabled="!formularioCompleto"
                        @click="guardarFormulario"
                    />
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
import Steps from 'primevue/steps'
import Card from 'primevue/card'
import Textarea from 'primevue/textarea'
import InputNumber from 'primevue/inputnumber'
import FloatLabel from 'primevue/floatlabel'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Avatar from 'primevue/avatar'
import { useToast } from 'primevue/usetoast'
import { debounce } from 'lodash'
import axios from 'axios'

const toast = useToast()
const visible = ref(false)
const pasoActual = ref(0)
const guardando = ref(false)

const pasos = ref([
    { label: 'Propiedad', icon: 'pi pi-home' },
    { label: 'Tasación', icon: 'pi pi-building' },
    { label: 'Detalles', icon: 'pi pi-file-edit' },
    { label: 'Resumen', icon: 'pi pi-check-circle' }
])

const propiedadSeleccionada = ref(null)
const propiedades = ref([])
const clienteVinculado = ref(null)

const form = ref({
    ocupacion_profesion: '',
    empresa_tasadora: '',
    monto_tasacion: null,
    porcentaje_prestamo: null,
    monto_invertir: null,
    monto_prestamo: null,
    motivo_prestamo: '',
    descripcion_financiamiento: '',
    solicitud_prestamo_para: '',
    garantia: '',
    perfil_riesgo: ''
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
    return labels[pasoActual.value] || 'Siguiente'
})

const puedeAvanzar = computed(() => {
    switch(pasoActual.value) {
        case 0: return propiedadSeleccionada.value !== null
        case 1: return form.value.empresa_tasadora.trim() && 
                      form.value.monto_tasacion && 
                      form.value.porcentaje_prestamo &&
                      form.value.monto_invertir &&
                      form.value.monto_prestamo
        case 2: return form.value.ocupacion_profesion.trim() &&
                      form.value.solicitud_prestamo_para.trim() &&
                      form.value.garantia.trim() &&
                      form.value.motivo_prestamo.trim() &&
                      form.value.descripcion_financiamiento.trim() &&
                      form.value.perfil_riesgo.trim()
        default: return true
    }
})

const formularioCompleto = computed(() => {
    return propiedadSeleccionada.value && 
           form.value.ocupacion_profesion.trim() &&
           form.value.empresa_tasadora.trim() &&
           form.value.monto_tasacion &&
           form.value.porcentaje_prestamo &&
           form.value.monto_invertir &&
           form.value.monto_prestamo &&
           form.value.motivo_prestamo.trim() &&
           form.value.descripcion_financiamiento.trim() &&
           form.value.solicitud_prestamo_para.trim() &&
           form.value.garantia.trim() &&
           form.value.perfil_riesgo.trim()
})

// Función para obtener el estilo del paso (ya no se necesita para Steps)
// const getStepClass = (stepIndex: number) => {
//     if (stepIndex < pasoActual.value) {
//         return 'bg-green-500 text-white shadow-lg'
//     } else if (stepIndex === pasoActual.value) {
//         return 'bg-primary-500 text-white shadow-lg'
//     } else {
//         return 'bg-gray-200 text-gray-600'
//     }
// }

// Función para calcular montos automáticamente con conversión de moneda
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
    if (puedeAvanzar.value && pasoActual.value < pasos.value.length - 1) {
        pasoActual.value++
    }
}

const pasoAnterior = () => {
    if (pasoActual.value > 0) {
        pasoActual.value--
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
        ocupacion_profesion: '',
        empresa_tasadora: '',
        monto_tasacion: null,
        porcentaje_prestamo: null,
        monto_invertir: null,
        monto_prestamo: null,
        motivo_prestamo: '',
        descripcion_financiamiento: '',
        solicitud_prestamo_para: '',
        garantia: '',
        perfil_riesgo: ''
    }
    propiedadSeleccionada.value = null
    clienteVinculado.value = null
    propiedades.value = []
    pasoActual.value = 0
}
</script>

<style scoped>
.field {
    margin-bottom: 1.5rem;
}

.field label {
    display: block;
    margin-bottom: 0.75rem;
}

.field small {
    display: block;
    margin-top: 0.5rem;
}

/* Animaciones */
.fade-in {
    animation: fadeInUp 0.4s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Mejoras en los Steps */
:deep(.p-steps .p-steps-item) {
    padding: 1rem;
}

:deep(.p-steps .p-steps-item .p-steps-number) {
    background: #e9ecef;
    color: #495057;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

:deep(.p-steps .p-steps-item.p-highlight .p-steps-number) {
    background: #667eea;
    color: white;
}

:deep(.p-steps .p-steps-item.p-highlight .p-steps-title) {
    color: #667eea;
    font-weight: 600;
}

/* Mejorar la apariencia del diálogo */
:deep(.p-dialog .p-dialog-header) {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem 2rem;
}

:deep(.p-dialog .p-dialog-header .p-dialog-title) {
    font-size: 1.5rem;
    font-weight: 600;
}

:deep(.p-dialog .p-dialog-content) {
    padding: 2rem;
    max-height: 70vh;
    overflow-y: auto;
}

:deep(.p-dialog .p-dialog-footer) {
    padding: 0;
    border-top: 1px solid #e9ecef;
    background-color: #f8f9fa;
}

/* Estilo para los campos de texto y números */
:deep(.p-inputtextarea),
:deep(.p-inputnumber-input),
:deep(.p-select) {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

:deep(.p-inputtextarea:focus),
:deep(.p-inputnumber-input:focus),
:deep(.p-select:focus) {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Estilo para las tarjetas */
:deep(.p-card) {
    border: none;
    border-radius: 12px;
}

:deep(.p-card .p-card-title) {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f1f3f4;
}

:deep(.p-card .p-card-content) {
    padding-top: 0;
}

/* Botones mejorados */
:deep(.p-button) {
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

:deep(.p-button:hover) {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Select dropdown mejorado */
:deep(.p-select-panel) {
    border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    border: 2px solid #e9ecef;
}

:deep(.p-select-item) {
    border-radius: 6px;
    margin: 4px;
    transition: all 0.2s ease;
}

:deep(.p-select-item:hover) {
    background-color: #667eea !important;
    color: white;
}

/* Tags mejorados */
:deep(.p-tag) {
    border-radius: 6px;
    font-weight: 600;
    padding: 0.5rem 0.75rem;
}

/* Avatar mejorado */
:deep(.p-avatar) {
    border-radius: 12px;
}

/* Float Label mejorado */
:deep(.p-floatlabel label) {
    font-weight: 600;
    color: #495057;
    transition: all 0.3s ease;
}

:deep(.p-floatlabel:focus-within label) {
    color: #667eea;
}

/* Stepper mejorado - ya no es necesario */

/* Responsive design */
@media (max-width: 768px) {
    :deep(.p-dialog .p-dialog-content) {
        padding: 1rem;
    }
    
    :deep(.p-stepper) {
        padding: 0.5rem;
    }
    
    .grid > .col-12.md\\:col-6 {
        margin-bottom: 1rem;
    }
}

/* Scroll suave */
:deep(.p-dialog-content) {
    scroll-behavior: smooth;
}

/* Loading states */
:deep(.p-button[aria-label*="loading"]) {
    position: relative;
    overflow: hidden;
}

:deep(.p-button[aria-label*="loading"]:after) {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% { left: -100%; }
    100% { left: 100%; }
}

/* Gradientes CSS personalizados para los navegadores que no soportan bg-gradient-to-r de Tailwind */
.bg-gradient-to-r.from-blue-50.to-cyan-50 {
    background: linear-gradient(to right, #eff6ff, #ecfeff);
}

.bg-gradient-to-r.from-green-50.to-emerald-50 {
    background: linear-gradient(to right, #f0fdf4, #ecfdf5);
}

.bg-gradient-to-r.from-purple-50.to-pink-50 {
    background: linear-gradient(to right, #faf5ff, #fdf2f8);
}

.bg-gradient-to-r.from-orange-50.to-yellow-50 {
    background: linear-gradient(to right, #fff7ed, #fefce8);
}
</style>
