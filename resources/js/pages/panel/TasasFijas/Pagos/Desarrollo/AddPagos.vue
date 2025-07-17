<template>
    <Toast />
    <Toolbar>
        <template #start>
            <Button label="Nuevo Pago" icon="pi pi-plus" severity="contrast" @click="openDialog" />
        </template>
    </Toolbar>

    <!-- Diálogo mejorado -->
    <Dialog v-model:visible="showDialog" modal header="Gestión de Pagos" :style="{ width: '80vw' }"
        :breakpoints="{ '960px': '100vw' }" class="custom-dialog">
        <div class="flex flex-col gap-8">
            <!-- Buscador con mejor diseño -->
            <div class="bg-gray-50 p-6 rounded-lg border">
                <label class="block text-lg font-semibold text-gray-800 mb-3">
                    Seleccionar Inversionista <span class="text-red-500">*</span>
                </label>
                <Select v-model="propiedadSeleccionada" :options="propiedades" :style="{ width: '100%' }" editable
                    optionLabel="label" optionValue="value" showClear
                    placeholder="Buscar inversión por nombre, DNI o RUC..." @update:modelValue="onInputChange"
                    class="custom-select">
                    <template #value="{ value }">
                        <span class="font-medium">{{ getPropiedadLabel(value) }}</span>
                    </template>
                    <template #option="{ option }">
                        <div class="flex justify-between items-center py-3 px-2 hover:bg-gray-50 rounded">
                            <div class="flex-1">
                                <div class="font-semibold text-gray-900">{{ option.label }}</div>
                                <div class="text-sm text-gray-600 mt-1">{{ option.sublabel }}</div>
                            </div>
                            <Tag v-if="option.estado" :value="option.estado"
                                :severity="getEstadoSeverity(option.estado)" class="ml-4"/>
                        </div>
                    </template>
                    <template #empty>
                        <div class="text-center py-4 text-gray-500">
                            <i class="pi pi-search text-2xl mb-2"></i>
                            <div>No se encontró inversión</div>
                        </div>
                    </template>
                </Select>
            </div>

            <!-- Información del inversionista -->
            <div v-if="inversionDetalle" class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-gray-900">Información de la Inversión</h3>
                    <Tag :value="inversionDetalle.status" :severity="getEstadoSeverity(inversionDetalle.status)" 
                         class="text-sm font-medium"/>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <div class="flex items-center space-x-2">
                            <i class="pi pi-user text-gray-500"></i>
                            <span class="text-sm font-medium text-gray-700">Inversor</span>
                        </div>
                        <p class="text-lg font-semibold text-gray-900">{{ inversionDetalle.nombre }}</p>
                        <p class="text-sm text-gray-600">DNI: {{ inversionDetalle.dni }}</p>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex items-center space-x-2">
                            <i class="pi pi-building text-gray-500"></i>
                            <span class="text-sm font-medium text-gray-700">Entidad</span>
                        </div>
                        <p class="text-lg font-semibold text-gray-900">{{ inversionDetalle.entidad }}</p>
                        <p class="text-sm text-gray-600">RUC: {{ inversionDetalle.ruc }}</p>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex items-center space-x-2">
                            <i class="pi pi-dollar text-gray-500"></i>
                            <span class="text-sm font-medium text-gray-700">Inversión</span>
                        </div>
                        <p class="text-lg font-semibold text-gray-900">S/ {{ formatCurrency(inversionDetalle.amount) }}</p>
                        <p class="text-sm text-gray-600">{{ inversionDetalle.rate }}% {{ inversionDetalle.rate_type }} - {{ inversionDetalle.term_plan }}</p>
                    </div>
                </div>
            </div>

            <!-- Resumen de pagos -->
            <div v-if="cronograma.length" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Cuotas</p>
                            <p class="text-2xl font-bold text-gray-800">{{ resumenPagos.total }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="pi pi-calendar text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Pendientes</p>
                            <p class="text-2xl font-bold text-yellow-600">{{ resumenPagos.pendientes }}</p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                            <i class="pi pi-clock text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Pagadas</p>
                            <p class="text-2xl font-bold text-green-600">{{ resumenPagos.pagadas }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="pi pi-check-circle text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de pagos mejorada -->
            <div v-if="cronograma.length" class="bg-white rounded-lg shadow-sm border">
                <div class="p-6 border-b bg-gray-50">
                    <h3 class="text-xl font-semibold text-gray-900 mb-1">Cronograma de Pagos</h3>
                    <p class="text-gray-600">Detalle de todos los pagos programados</p>
                </div>

                <div class="overflow-x-auto">
                    <DataTable :value="cronograma" responsiveLayout="scroll" class="p-datatable-sm" stripedRows
                        :paginator="cronograma.length > 10" :rows="10"
                        paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} registros"
                        :loading="cargandoCronograma">
                        <Column field="month" header="Mes" sortable style="min-width: 4rem">
                            <template #body="{ data }">
                                <span class="font-medium">{{ data.month }}</span>
                            </template>
                        </Column>
                        <Column field="payment_date" header="Fecha Pago" sortable style="min-width: 9rem">
                            <template #body="{ data }">
                                <span class="text-sm">{{ data.payment_date }}</span>
                            </template>
                        </Column>
                        <Column field="days" header="Días" sortable style="min-width: 4rem">
                            <template #body="{ data }">
                                <span class="text-sm">{{ data.days }}</span>
                            </template>
                        </Column>
                        <Column field="base_amount" header="Capital Base" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                <span class="font-medium text-blue-600">S/ {{ formatCurrency(data.base_amount) }}</span>
                            </template>
                        </Column>
                        <Column field="interest_amount" header="Interés" sortable style="min-width: 8rem">
                            <template #body="{ data }">
                                <span class="font-medium text-green-600">S/ {{ formatCurrency(data.interest_amount) }}</span>
                            </template>
                        </Column>
                        <Column field="second_category_tax" header="Imp. Renta" sortable style="min-width: 9rem">
                            <template #body="{ data }">
                                <span class="font-medium text-red-600">S/ {{ formatCurrency(data.second_category_tax) }}</span>
                            </template>
                        </Column>
                        <Column field="interest_to_deposit" header="Int. a Depositar" sortable style="min-width: 11rem">
                            <template #body="{ data }">
                                <span class="font-medium text-indigo-600">S/ {{ formatCurrency(data.interest_to_deposit) }}</span>
                            </template>
                        </Column>
                        <Column field="capital_return" header="Devol. Capital" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                <span class="font-medium text-purple-600">S/ {{ formatCurrency(data.capital_return) }}</span>
                            </template>
                        </Column>
                        <Column field="capital_balance" header="Saldo Capital" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                <span class="font-medium text-gray-700">S/ {{ formatCurrency(data.capital_balance) }}</span>
                            </template>
                        </Column>
                        <Column field="total_to_deposit" header="Total a Depositar" sortable style="min-width: 12rem">
                            <template #body="{ data }">
                                <span class="font-bold text-green-700 text-lg">S/ {{ formatCurrency(data.total_to_deposit) }}</span>
                            </template>
                        </Column>
                        <Column field="status" header="Estado" sortable style="min-width: 8rem">
                            <template #body="{ data }">
                                <Tag :value="data.status" :severity="getEstadoSeverity(data.status)" class="font-medium" />
                            </template>
                        </Column>
                        <Column header="Acciones" :exportable="false" style="min-width: 8rem">
                            <template #body="{ data }">
                                <div class="flex gap-2">
                                    <Button v-if="data.status === 'pendiente'" 
                                            label="Pagar" 
                                            icon="pi pi-credit-card"
                                            severity="success" 
                                            size="small"
                                            @click="procesarPago(data)" />
                                    <Button v-else 
                                            label="Ver" 
                                            icon="pi pi-eye"
                                            severity="info" 
                                            size="small"
                                            @click="verDetalle(data)" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>

            <!-- Estado vacío mejorado -->
            <div v-else-if="propiedadSeleccionada && !cargandoCronograma"
                class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                <i class="pi pi-calendar-times text-6xl text-gray-400 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay cronograma disponible</h3>
                <p class="text-gray-500">No se encontró información de pagos para esta inversión</p>
            </div>

            <!-- Estado de carga -->
            <div v-else-if="cargandoCronograma" class="text-center py-12">
                <i class="pi pi-spin pi-spinner text-4xl text-blue-500 mb-4"></i>
                <p class="text-gray-600">Cargando cronograma...</p>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-3">
                <Button label="Cerrar" icon="pi pi-times" text severity="secondary" @click="showDialog = false" />
                <Button v-if="cronograma.length > 0" label="Exportar" icon="pi pi-download" severity="info"
                    @click="exportarCronograma" />
            </div>
        </template>
    </Dialog>

    <!-- Diálogo de confirmación de pago mejorado -->
    <Dialog v-model:visible="showPagoDialog" modal header="Confirmar Pago" :style="{ width: '600px' }">
        <div v-if="pagoSeleccionado" class="flex flex-col gap-6">
            <!-- Información del pago -->
            <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                <h4 class="font-semibold text-blue-900 mb-4 flex items-center">
                    <i class="pi pi-info-circle mr-2"></i>
                    Detalles del Pago - Mes {{ pagoSeleccionado.month }}
                </h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-700">Fecha de Pago:</span>
                        <p class="text-gray-900">{{ pagoSeleccionado.payment_date }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Días:</span>
                        <p class="text-gray-900">{{ pagoSeleccionado.days }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Capital Base:</span>
                        <p class="text-gray-900">S/ {{ formatCurrency(pagoSeleccionado.base_amount) }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Interés Bruto:</span>
                        <p class="text-gray-900">S/ {{ formatCurrency(pagoSeleccionado.interest_amount) }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Impuesto Renta:</span>
                        <p class="text-red-600">-S/ {{ formatCurrency(pagoSeleccionado.second_category_tax) }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Interés Neto:</span>
                        <p class="text-green-600">S/ {{ formatCurrency(pagoSeleccionado.interest_to_deposit) }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Devolución Capital:</span>
                        <p class="text-purple-600">S/ {{ formatCurrency(pagoSeleccionado.capital_return) }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Saldo Restante:</span>
                        <p class="text-gray-900">S/ {{ formatCurrency(pagoSeleccionado.capital_balance) }}</p>
                    </div>
                </div>
            </div>

            <!-- Total a depositar destacado -->
            <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                <div class="flex items-center justify-between">
                    <span class="text-lg font-semibold text-green-900">Total a Depositar:</span>
                    <span class="text-2xl font-bold text-green-700">S/ {{ formatCurrency(pagoSeleccionado.total_to_deposit) }}</span>
                </div>
            </div>

            <!-- Advertencia -->
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                <div class="flex items-start">
                    <i class="pi pi-exclamation-triangle text-yellow-600 mr-3 mt-1"></i>
                    <div>
                        <p class="font-medium text-yellow-800">¿Está seguro de procesar este pago?</p>
                        <p class="text-sm text-yellow-700 mt-1">
                            Esta acción registrará el pago, creará el movimiento contable y actualizará el balance del inversionista.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-3">
                <Button label="Cancelar" icon="pi pi-times" text severity="secondary" @click="showPagoDialog = false" />
                <Button label="Confirmar Pago" icon="pi pi-check" severity="success" 
                    :loading="procesandoPago" @click="confirmarPago" />
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, computed } from 'vue'
import { debounce } from 'lodash'
import axios from 'axios'

import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Toolbar from 'primevue/toolbar'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

const toast = useToast()
const showDialog = ref(false)
const showPagoDialog = ref(false)
const propiedadSeleccionada = ref(null)
const propiedades = ref([])
const cronograma = ref([])
const pagoSeleccionado = ref(null)
const cargandoCronograma = ref(false)
const procesandoPago = ref(false)
const inversionistaSeleccionado = ref(null)
const inversionDetalle = ref(null)

// Computed para el resumen de pagos
const resumenPagos = computed(() => {
    const total = cronograma.value.length
    const pendientes = cronograma.value.filter(p => p.status === 'pendiente').length
    const pagadas = cronograma.value.filter(p => p.status === 'pagado').length
    
    return { total, pendientes, pagadas }
})

const getEstadoSeverity = (estado) => {
    switch (estado) {
        case 'pendiente':
            return 'warn'
        case 'pagado':
            return 'success'
        case 'activo':
            return 'success'
        case 'rechazado':
            return 'danger'
        default:
            return 'info'
    }
}

const getPropiedadLabel = (id) => {
    const prop = propiedades.value.find(p => p.value === id)
    return prop ? prop.label : id
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('es-PE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount || 0)
}

const buscarPropiedades = debounce(async (texto) => {
    if (!texto) {
        propiedades.value = []
        return
    }
    try {
        const { data } = await axios.get('/pagos/pendientes', {
            params: { search: texto }
        })

        propiedades.value = data.data.map((item) => ({
            label: `${item.nombre} (${item.entidad})`,
            sublabel: `DNI: ${item.dni} | RUC: ${item.ruc} | Monto: S/ ${formatCurrency(item.amount)}`,
            value: item.id,
            estado: item.status,
            inversionista_id: item.inversionista_id || item.investor_id,
            moneda: item.currency || 'PEN',
            detalles: item // Guardamos toda la información
        }))
    } catch (error) {
        console.error('Error al buscar propiedades:', error)
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error al buscar propiedades',
            life: 3000
        })
    }
}, 500)

const onInputChange = async (valor) => {
    if (typeof valor === 'string') {
        buscarPropiedades(valor)
    } else if (valor && typeof valor === 'number') {
        const propiedadInfo = propiedades.value.find(p => p.value === valor)
        if (propiedadInfo) {
            inversionistaSeleccionado.value = propiedadInfo.inversionista_id
            inversionDetalle.value = propiedadInfo.detalles
        }
        
        cargandoCronograma.value = true
        try {
            const { data } = await axios.get(`/fixed-term-schedules/${valor}/cronograma`)
            cronograma.value = data.data
            
            toast.add({
                severity: 'success',
                summary: 'Éxito',
                detail: 'Cronograma cargado correctamente',
                life: 2000
            })
        } catch (error) {
            console.error('Error al cargar cronograma:', error)
            cronograma.value = []
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se pudo cargar el cronograma',
                life: 3000
            })
        } finally {
            cargandoCronograma.value = false
        }
    } else if (valor === null || valor === undefined) {
        cronograma.value = []
        inversionistaSeleccionado.value = null
        inversionDetalle.value = null
    }
}

const procesarPago = (pago) => {
    pagoSeleccionado.value = pago
    showPagoDialog.value = true
}

const confirmarPago = async () => {
    if (!pagoSeleccionado.value || !inversionistaSeleccionado.value) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Faltan datos para procesar el pago',
            life: 3000
        })
        return
    }

    procesandoPago.value = true
    
    try {
        const propiedadInfo = propiedades.value.find(p => p.value === propiedadSeleccionada.value)
        
        const pagoData = {
            mes: pagoSeleccionado.value.month,
            monto: pagoSeleccionado.value.total_to_deposit, // Cambié a total_to_deposit
            moneda: propiedadInfo?.moneda || 'PEN',
            id_fixed_term_schedule: pagoSeleccionado.value.id,
            id_inversionista: inversionistaSeleccionado.value
        }

        const response = await axios.post('/pagos-tasas', pagoData)

        // Actualizar el estado del pago en la tabla
        const index = cronograma.value.findIndex(p => p.id === pagoSeleccionado.value.id)
        if (index !== -1) {
            cronograma.value[index].status = 'pagado'
        }

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data.message || 'Pago procesado correctamente',
            life: 3000
        })

        showPagoDialog.value = false
        pagoSeleccionado.value = null
        
    } catch (error) {
        console.error('Error al procesar pago:', error)
        let errorMessage = 'Error al procesar el pago'
        
        if (error.response?.status === 422) {
            errorMessage = error.response.data.message || 'La cuota ya fue pagada o está en estado inválido'
        } else if (error.response?.data?.message) {
            errorMessage = error.response.data.message
        } else if (error.response?.data?.errors) {
            const errors = Object.values(error.response.data.errors).flat()
            errorMessage = errors.join(', ')
        }
        
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage,
            life: 5000
        })
    } finally {
        procesandoPago.value = false
    }
}

const verDetalle = (pago) => {
    toast.add({
        severity: 'info',
        summary: 'Información',
        detail: `Mostrando detalles del pago del mes ${pago.month}`,
        life: 3000
    })
}

const exportarCronograma = () => {
    // Lógica para exportar cronograma
    const csvContent = "data:text/csv;charset=utf-8," + 
        "Mes,Fecha,Capital Base,Interés,Impuesto,Total\n" +
        cronograma.value.map(row => 
            `${row.month},${row.payment_date},${row.base_amount},${row.interest_amount},${row.second_category_tax},${row.total_to_deposit}`
        ).join("\n")
    
    const encodedUri = encodeURI(csvContent)
    const link = document.createElement("a")
    link.setAttribute("href", encodedUri)
    link.setAttribute("download", `cronograma_${inversionDetalle.value?.nombre || 'pago'}.csv`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    toast.add({
        severity: 'success',
        summary: 'Éxito',
        detail: 'Cronograma exportado correctamente',
        life: 3000
    })
}

const openDialog = () => {
    showDialog.value = true
    propiedadSeleccionada.value = null
    cronograma.value = []
    inversionistaSeleccionado.value = null
    inversionDetalle.value = null
    propiedades.value = []
}
</script>
