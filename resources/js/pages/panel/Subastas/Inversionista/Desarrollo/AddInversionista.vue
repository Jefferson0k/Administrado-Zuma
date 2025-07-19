<template>
    <Toast />
    <Toolbar>
        <template #start>
            <Button label="Nuevo Pago Hipoteca" icon="pi pi-plus" severity="contrast" @click="openDialog" />
        </template>
    </Toolbar>

    <!-- Diálogo principal -->
    <Dialog v-model:visible="showDialog" modal header="Gestión de Pagos de Hipotecas" :style="{ width: '80vw' }"
        :breakpoints="{ '960px': '100vw' }" class="custom-dialog">
        <div class="flex flex-col gap-8">
            <!-- Buscador -->
            <div class="bg-gray-50 p-6 rounded-lg border">
                <label class="block text-lg font-semibold text-gray-800 mb-3">
                    Seleccionar inversionista <span class="text-red-500">*</span>
                </label>
                <Select v-model="hipotecaSeleccionada" :options="hipotecas" :style="{ width: '100%' }" editable
                    optionLabel="label" optionValue="value" showClear
                    placeholder="Buscar por inversionista, documento o propiedad..." @update:modelValue="onInputChange"
                    class="custom-select">
                    <template #value="{ value }">
                        <span class="font-medium">{{ getHipotecaLabel(value) }}</span>
                    </template>
                    <template #option="{ option }">
                        <div class="flex justify-between items-center py-3 px-2 hover:bg-gray-50 rounded">
                            <div class="flex-1">
                                <div class="font-semibold text-gray-900">{{ option.label }}</div>
                                <div class="text-sm text-gray-600 mt-1">{{ option.sublabel }}</div>
                            </div>
                            <Tag v-if="option.estado" :value="option.estado"
                                :severity="getEstadoSeverity(option.estado)" class="ml-4" />
                        </div>
                    </template>
                    <template #empty>
                        <div class="text-center py-4 text-gray-500">
                            <i class="pi pi-search text-2xl mb-2"></i>
                            <div>No se encontró hipoteca</div>
                        </div>
                    </template>
                </Select>
            </div>

            <!-- Información de la hipoteca -->
            <div v-if="hipotecaDetalle" class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-gray-900">Información de la Hipoteca</h3>
                    <Tag :value="hipotecaDetalle.status" :severity="getEstadoSeverity(hipotecaDetalle.status)"
                        class="text-sm font-medium" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <div class="flex items-center space-x-2">
                            <i class="pi pi-user text-gray-500"></i>
                            <span class="text-sm font-medium text-gray-700">Inversionista</span>
                        </div>
                        <p class="text-lg font-semibold text-gray-900">{{ hipotecaDetalle.nombreinvestor }}</p>
                        <p class="text-sm text-gray-600">Doc: {{ hipotecaDetalle.document }}</p>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center space-x-2">
                            <i class="pi pi-building text-gray-500"></i>
                            <span class="text-sm font-medium text-gray-700">Propiedad</span>
                        </div>
                        <p class="text-lg font-semibold text-gray-900">{{ hipotecaDetalle.nombre }}</p>
                        <p class="text-sm text-gray-600">ID: {{ hipotecaDetalle.property_id }}</p>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center space-x-2">
                            <i class="pi pi-dollar text-gray-500"></i>
                            <span class="text-sm font-medium text-gray-700">Monto Hipoteca</span>
                        </div>
                        <p class="text-lg font-semibold text-gray-900">S/ {{ formatCurrency(hipotecaDetalle.amount) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Resumen de pagos -->
            <div v-if="cronograma.length" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Cuotas</p>
                            <p class="text-2xl font-bold text-gray-800">{{ paginacionInfo.total || resumenPagos.total }}
                            </p>
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

            <!-- Tabla de pagos -->
            <div v-if="cronograma.length" class="rounded-lg shadow-sm border">
                <div class="p-6 border-b bg-gray-50">
                    <h3 class="text-xl font-semibold text-gray-900 mb-1">Cronograma de Pagos</h3>
                    <p class="text-gray-600">
                        Detalle de todos los pagos programados
                        <span v-if="paginacionInfo.total" class="font-medium">
                            ({{ paginacionInfo.total }} registros en total)
                        </span>
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <DataTable :value="cronograma" responsiveLayout="scroll" class="p-datatable-sm" stripedRows
                        :lazy="true" :paginator="true" :rows="paginacionInfo.per_page || 10"
                        :totalRecords="paginacionInfo.total || 0"
                        :first="(paginacionInfo.current_page - 1) * paginacionInfo.per_page || 0" @page="onPageChange"
                        paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                        :currentPageReportTemplate="`Mostrando ${paginacionInfo.from || 0} a ${paginacionInfo.to || 0} de ${paginacionInfo.total || 0} registros`"
                        :loading="cargandoCronograma">

                        <Column field="cuota" header="Cuota" sortable style="min-width: 4rem">
                            <template #body="{ data }">
                                <span class="font-medium"># {{ data.cuota }}</span>
                            </template>
                        </Column>

                        <Column field="vencimiento" header="Fecha Pago" sortable style="min-width: 9rem">
                            <template #body="{ data }">
                                <span class="text-sm">{{ formatDate(data.vencimiento) }}</span>
                            </template>
                        </Column>

                        <Column field="saldo_inicial" header="Saldo inicial" sortable style="min-width: 8rem">
                            <template #body="{ data }">
                                <span class="font-medium text-blue-600">S/ {{ formatCurrency(data.saldo_inicial)
                                    }}</span>
                            </template>
                        </Column>

                        <Column field="capital" header="Capital" sortable style="min-width: 8rem">
                            <template #body="{ data }">
                                <span class="font-medium text-green-600">S/ {{ formatCurrency(data.capital) }}</span>
                            </template>
                        </Column>

                        <Column field="intereses" header="Interes" sortable style="min-width: 8rem">
                            <template #body="{ data }">
                                <span class="font-medium text-purple-600">S/ {{ formatCurrency(data.intereses) }}</span>
                            </template>
                        </Column>

                        <Column field="cuota_neta" header="Cuota neta" sortable style="min-width: 8rem">
                            <template #body="{ data }">
                                <span class="font-medium text-gray-700">S/ {{ formatCurrency(data.cuota_neta) }}</span>
                            </template>
                        </Column>

                        <Column field="total_cuota" header="Total Cuota" sortable style="min-width: 8rem">
                            <template #body="{ data }">
                                <span class="font-medium text-gray-700">S/ {{ formatCurrency(data.total_cuota) }}</span>
                            </template>
                        </Column>

                        <Column field="saldo_final" header="Saldo Final" sortable style="min-width: 8rem">
                            <template #body="{ data }">
                                <span class="font-medium text-gray-700">S/ {{ formatCurrency(data.saldo_final) }}</span>
                            </template>
                        </Column>

                        <Column field="estado" header="Estado" sortable style="min-width: 8rem">
                            <template #body="{ data }">
                                <Tag :value="data.estado" :severity="getEstadoSeverity(data.estado)"
                                    class="font-medium" />
                            </template>
                        </Column>

                        <Column header="" :exportable="false" style="min-width: 8rem">
                            <template #body="{ data }">
                                <div class="flex gap-2">
                                    <Button v-if="data.estado === 'pendiente'" label="Pagar" icon="pi pi-credit-card"
                                        severity="success" size="small" @click="procesarPago(data)" />
                                    <Button v-else label="Ver" icon="pi pi-eye" severity="info" size="small"
                                        @click="verDetalle(data)" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>

            <!-- Estado vacío -->
            <div v-else-if="hipotecaSeleccionada && !cargandoCronograma"
                class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                <i class="pi pi-calendar-times text-6xl text-gray-400 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay cronograma disponible</h3>
                <p class="text-gray-500">No se encontró información de pagos para esta hipoteca</p>
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

    <!-- Diálogo de confirmación de pago -->
    <Dialog v-model:visible="showPagoDialog" modal header="Confirmar Pago" :style="{ width: '600px' }">
        <div v-if="pagoSeleccionado" class="flex flex-col gap-6">
            <!-- Información del pago -->
            <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                <h4 class="font-semibold text-blue-900 mb-4 flex items-center">
                    <i class="pi pi-info-circle mr-2"></i>
                    Detalles del Pago # {{ pagoSeleccionado.id }}
                </h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-700">Cuota:</span>
                        <p class="text-gray-900"># {{ pagoSeleccionado.cuota }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Fecha Vencimiento:</span>
                        <p class="text-gray-900">{{ formatDate(pagoSeleccionado.vencimiento) }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Monto Total:</span>
                        <p class="text-gray-900">S/ {{ formatCurrency(pagoSeleccionado.total_cuota) }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Interés:</span>
                        <p class="text-green-600">S/ {{ formatCurrency(pagoSeleccionado.intereses) }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Capital:</span>
                        <p class="text-purple-600">S/ {{ formatCurrency(pagoSeleccionado.capital) }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Saldo Restante:</span>
                        <p class="text-gray-900">S/ {{ formatCurrency(pagoSeleccionado.saldo_final) }}</p>
                    </div>
                </div>
            </div>

            <!-- Total a depositar destacado -->
            <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                <div class="flex items-center justify-between">
                    <span class="text-lg font-semibold text-green-900">Total a Depositar:</span>
                    <span class="text-2xl font-bold text-green-700">S/ {{ formatCurrency(pagoSeleccionado.total_cuota)
                        }}</span>
                </div>
            </div>

            <!-- Advertencia -->
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                <div class="flex items-start">
                    <i class="pi pi-exclamation-triangle text-yellow-600 mr-3 mt-1"></i>
                    <div>
                        <p class="font-medium text-yellow-800">¿Está seguro de procesar este pago?</p>
                        <p class="text-sm text-yellow-700 mt-1">
                            Esta acción registrará el pago y actualizará el cronograma de la hipoteca.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-3">
                <Button label="Cancelar" icon="pi pi-times" text severity="secondary" @click="showPagoDialog = false" />
                <Button label="Confirmar Pago" icon="pi pi-check" severity="success" :loading="procesandoPago"
                    @click="confirmarPago" />
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
const hipotecaSeleccionada = ref(null)
const hipotecas = ref([])
const cronograma = ref([])
const pagoSeleccionado = ref(null)
const cargandoCronograma = ref(false)
const procesandoPago = ref(false)
const hipotecaDetalle = ref(null)

// Nueva variable para manejar la información de paginación
const paginacionInfo = ref({
    current_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0,
    last_page: 1
})

// Computed para el resumen de pagos (basado en la página actual)
const resumenPagos = computed(() => {
    const total = cronograma.value.length
    const pendientes = cronograma.value.filter(p => p.estado === 'pendiente').length
    const pagadas = cronograma.value.filter(p => p.estado === 'pagado').length

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
        case 'vencido':
            return 'danger'
        default:
            return 'info'
    }
}

const getHipotecaLabel = (id) => {
    const hipoteca = hipotecas.value.find(h => h.value === id)
    return hipoteca ? hipoteca.label : id
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('es-PE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount || 0)
}

const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleDateString('es-PE')
}

const buscarHipotecas = debounce(async (texto) => {
    if (!texto) {
        hipotecas.value = []
        return
    }
    try {
        const { data } = await axios.get('/pagos/cliente/pendiente', {
            params: { search: texto }
        })

        hipotecas.value = data.data.map((item) => ({
            label: `${item.nombreinvestor} - ${item.nombre}`,
            sublabel: `Doc: ${item.document} | Monto: S/ ${formatCurrency(item.amount)}`,
            value: item.id,
            estado: item.status,
            detalles: item
        }))
    } catch (error) {
        console.error('Error al buscar hipotecas:', error)
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error al buscar hipotecas',
            life: 3000
        })
    }
}, 500)

// Nueva función para cargar cronograma con paginación
const cargarCronograma = async (hipotecaId, page = 1) => {
    if (!hipotecaId) return

    cargandoCronograma.value = true
    try {
        const { data } = await axios.get(`/cronograma/${hipotecaId}`, {
            params: { page }
        })

        // Extraer los datos correctamente del JSON paginado
        cronograma.value = data.data || []

        // Actualizar información de paginación
        if (data.meta) {
            paginacionInfo.value = {
                current_page: data.meta.current_page,
                per_page: data.meta.per_page,
                total: data.meta.total,
                from: data.meta.from,
                to: data.meta.to,
                last_page: data.meta.last_page
            }
        }

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Cronograma cargado correctamente',
            life: 2000
        })
    } catch (error) {
        console.error('Error al cargar cronograma:', error)
        cronograma.value = []
        paginacionInfo.value = {
            current_page: 1,
            per_page: 10,
            total: 0,
            from: 0,
            to: 0,
            last_page: 1
        }
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo cargar el cronograma',
            life: 3000
        })
    } finally {
        cargandoCronograma.value = false
    }
}

// Función para manejar el cambio de página
const onPageChange = (event) => {
    const page = Math.floor(event.first / event.rows) + 1
    cargarCronograma(hipotecaSeleccionada.value, page)
}

const onInputChange = async (valor) => {
    if (typeof valor === 'string') {
        buscarHipotecas(valor)
    } else if (valor && typeof valor === 'number') {
        const hipotecaInfo = hipotecas.value.find(h => h.value === valor)
        if (hipotecaInfo) {
            hipotecaDetalle.value = hipotecaInfo.detalles
        }

        // Cargar cronograma usando la nueva función
        await cargarCronograma(valor, 1)

    } else if (valor === null || valor === undefined) {
        cronograma.value = []
        hipotecaDetalle.value = null
        paginacionInfo.value = {
            current_page: 1,
            per_page: 10,
            total: 0,
            from: 0,
            to: 0,
            last_page: 1
        }
    }
}

const procesarPago = (pago) => {
    pagoSeleccionado.value = pago
    showPagoDialog.value = true
}

const confirmarPago = async () => {
    if (!pagoSeleccionado.value || !hipotecaDetalle.value) {
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
        // Preparar los datos según las reglas de StorePaymentRequest
        const pagoData = {
            id_payment_schedule: pagoSeleccionado.value.id, // ID del cronograma seleccionado
            id_inversionista: hipotecaDetalle.value.investor_id, // ID del inversionista desde los datos de búsqueda
            moneda: 'PEN', // Moneda por defecto
            monto: parseFloat(pagoSeleccionado.value.total_cuota) // Monto de la cuota seleccionada
        }

        console.log('Datos del pago a enviar:', pagoData)

        // Enviar la petición usando el endpoint correcto
        const response = await axios.post('/pagos-inversinotas', pagoData)

        // Actualizar el estado del pago en la tabla local
        const index = cronograma.value.findIndex(p => p.id === pagoSeleccionado.value.id)
        if (index !== -1) {
            cronograma.value[index].estado = 'pagado'
        }

        toast.add({
            severity: 'success',
            summary: 'Pago Procesado',
            detail: response.data.message || 'El pago se ha registrado correctamente',
            life: 3000
        })

        // Cerrar el diálogo de confirmación
        showPagoDialog.value = false
        pagoSeleccionado.value = null

        // Recargar el cronograma para reflejar los cambios
        await cargarCronograma(hipotecaSeleccionada.value, paginacionInfo.value.current_page)

    } catch (error) {
        console.error('Error al procesar pago:', error)
        let errorMessage = 'Error al procesar el pago'

        if (error.response?.status === 422) {
            // Errores de validación
            if (error.response.data.errors) {
                const errores = Object.entries(error.response.data.errors).map(([campo, mensajes]) => {
                    return `${campo}: ${Array.isArray(mensajes) ? mensajes.join(', ') : mensajes}`
                }).join('\n')
                errorMessage = `Errores de validación:\n${errores}`
            } else {
                errorMessage = error.response.data.message || 'Error de validación en los datos enviados'
            }
        } else if (error.response?.status === 404) {
            errorMessage = 'No se encontró el cronograma de pago o el inversionista'
        } else if (error.response?.status === 400) {
            errorMessage = error.response.data.message || 'Solicitud inválida'
        } else if (error.response?.data?.message) {
            errorMessage = error.response.data.message
        }

        toast.add({
            severity: 'error',
            summary: 'Error de Pago',
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
        detail: `Mostrando detalles del pago # ${pago.id}`,
        life: 3000
    })
}

const exportarCronograma = () => {
    const csvContent = "data:text/csv;charset=utf-8," +
        "ID,Cuota,Fecha Vencimiento,Saldo Inicial,Capital,Intereses,Cuota Neta,Total Cuota,Saldo Final,Estado\n" +
        cronograma.value.map(row =>
            `${row.id},${row.cuota},${formatDate(row.vencimiento)},${row.saldo_inicial},${row.capital},${row.intereses},${row.cuota_neta},${row.total_cuota},${row.saldo_final},${row.estado}`
        ).join("\n")

    const encodedUri = encodeURI(csvContent)
    const link = document.createElement("a")
    link.setAttribute("href", encodedUri)
    link.setAttribute("download", `cronograma_hipoteca_${hipotecaDetalle.value?.nombreinvestor || 'pago'}.csv`)
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
    hipotecaSeleccionada.value = null
    cronograma.value = []
    hipotecaDetalle.value = null
    hipotecas.value = []
    paginacionInfo.value = {
        current_page: 1,
        per_page: 10,
        total: 0,
        from: 0,
        to: 0,
        last_page: 1
    }
}
</script>

<style scoped></style>