<template>
    <Dialog v-model:visible="visible" modal header="Cronograma de Pagos" :style="{ width: '95vw', maxWidth: '1200px' }"
        :closable="true" :dismissableMask="true">

        <!-- Header Info Card -->
        <div v-if="propiedadData" class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center md:text-left">
                    <h3 class="text-lg font-bold text-gray-800">{{ propiedadData.nombre }}</h3>
                    <p class="text-sm text-gray-600">{{ propiedadData.departamento }}, {{ propiedadData.provincia }}</p>
                </div>
                <div class="text-center">
                    <span class="text-sm text-gray-600 block">Valor Requerido</span>
                    <span class="text-2xl font-bold text-blue-600">
                        {{ formatCurrency(parametros.valor_requerido, propiedadData.currency_symbol) }}
                    </span>
                </div>
                <div class="text-center md:text-right">
                    <Tag :value="propiedadData.currency" :severity="propiedadData.currency_id === 1 ? 'success' : 'info'" 
                         class="mb-2" />
                    <p class="text-sm text-gray-600">
                        TEM: <strong>{{ parametros.tem }}%</strong> | 
                        TEA: <strong>{{ parametros.tea }}%</strong>
                    </p>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex flex-col justify-center items-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mb-4"></div>
            <span class="text-gray-600">Generando cronograma...</span>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="text-center py-12">
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-4">
                <i class="pi pi-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                <p class="text-red-600 text-lg mb-4">{{ error }}</p>
                <Button label="Reintentar" icon="pi pi-refresh" severity="danger" outlined @click="generarCronograma" />
            </div>
        </div>

        <!-- Cronograma Content -->
        <div v-else-if="cronograma" class="space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                    <i class="pi pi-calendar text-green-600 text-2xl mb-2"></i>
                    <p class="text-sm text-gray-600">Plazo Total</p>
                    <p class="text-lg font-bold text-green-700">{{ cronograma.cronograma_final.plazo_total }} meses</p>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                    <i class="pi pi-money-bill text-blue-600 text-2xl mb-2"></i>
                    <p class="text-sm text-gray-600">Capital Otorgado</p>
                    <p class="text-lg font-bold text-blue-700">{{ cronograma.cronograma_final.capital_otorgado }}</p>
                </div>
                
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 text-center">
                    <i class="pi pi-percentage text-orange-600 text-2xl mb-2"></i>
                    <p class="text-sm text-gray-600">TEA</p>
                    <p class="text-lg font-bold text-orange-700">{{ cronograma.cronograma_final.tea_compensatoria }}</p>
                </div>
                
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
                    <i class="pi pi-chart-line text-purple-600 text-2xl mb-2"></i>
                    <p class="text-sm text-gray-600">TEM</p>
                    <p class="text-lg font-bold text-purple-700">{{ cronograma.cronograma_final.tem_compensatoria }}</p>
                </div>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                    <h4 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="pi pi-table mr-2 text-blue-600"></i>
                        Cronograma de Pagos - {{ cronograma.cronograma_final.moneda }}
                    </h4>
                </div>
                
                <div class="overflow-x-auto">
                    <DataTable :value="cronograma.cronograma_final.pagos" :paginator="true" :rows="15" 
                               class="w-full" stripedRows :rowsPerPageOptions="[10, 15, 25, 50]"
                               currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} cuotas"
                               paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown">
                        
                        <Column field="cuota" header="Nº" :sortable="true" class="w-16 text-center">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.cuota" severity="info" class="font-bold" />
                            </template>
                        </Column>
                        
                        <Column field="vcmto" header="Fecha Vcmto." :sortable="true" class="text-center">
                            <template #body="slotProps">
                                <div>
                                    <i class="pi pi-calendar text-gray-500 mr-2"></i>
                                    <span>{{ slotProps.data.vcmto }}</span>
                                </div>
                            </template>
                        </Column>
                        
                        <Column field="saldo_inicial" header="Saldo Inicial" :sortable="true" class="text-right">
                            <template #body="slotProps">
                                <span class="font-medium text-gray-700">
                                    {{ formatCurrency(slotProps.data.saldo_inicial, currencySymbol) }}
                                </span>
                            </template>
                        </Column>
                        
                        <Column field="cuota_neta" header="Cuota Total" :sortable="true" class="text-right">
                            <template #body="slotProps">
                                <div class="bg-blue-100 px-3 py-2 rounded-md">
                                    <span class="font-bold text-blue-700 text-lg">
                                        {{ formatCurrency(slotProps.data.cuota_neta, currencySymbol) }}
                                    </span>
                                </div>
                            </template>
                        </Column>
                        
                        <Column field="interes" header="Interés" :sortable="true" class="text-right">
                            <template #body="slotProps">
                                <span class="font-semibold text-red-600 bg-red-50 px-2 py-1 rounded">
                                    {{ formatCurrency(slotProps.data.interes, currencySymbol) }}
                                </span>
                            </template>
                        </Column>
                        
                        <Column field="capital" header="Capital" :sortable="true" class="text-right">
                            <template #body="slotProps">
                                <span class="font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">
                                    {{ formatCurrency(slotProps.data.capital, currencySymbol) }}
                                </span>
                            </template>
                        </Column>
                        
                        <Column field="saldo_final" header="Saldo Final" :sortable="true" class="text-right">
                            <template #body="slotProps">
                                <span class="font-medium text-gray-700">
                                    {{ formatCurrency(slotProps.data.saldo_final, currencySymbol) }}
                                </span>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>

            <!-- Totales Summary -->
            <div class="bg-gradient-to-r from-gray-50 to-blue-50 rounded-lg p-6 border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Capital</p>
                        <p class="text-xl font-bold text-green-600">
                            {{ formatCurrency(totalCapital, currencySymbol) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Intereses</p>
                        <p class="text-xl font-bold text-red-600">
                            {{ formatCurrency(totalIntereses, currencySymbol) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total a Pagar</p>
                        <p class="text-xl font-bold text-blue-600">
                            {{ formatCurrency(totalPagar, currencySymbol) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between items-center">
                <div v-if="cronograma" class="text-sm text-gray-500">
                    Cronograma generado: {{ new Date().toLocaleDateString('es-ES') }}
                </div>
                <div class="flex gap-3">
                    <Button label="Exportar CSV" icon="pi pi-file" severity="success" 
                            outlined @click="exportarCSV" :disabled="!cronograma" />
                    <Button label="Cerrar" icon="pi pi-times" severity="secondary" @click="cerrarDialog" />
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import Dialog from 'primevue/dialog'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

interface Props {
    visible: boolean
    propiedadData: any
    parametros: {
        tea: number
        tem: number
        cronograma: string
        duracion_meses: number
        valor_requerido: number
        currency_id?: number
    }
}

const props = defineProps<Props>()
const emit = defineEmits(['update:visible'])

const toast = useToast()

const loading = ref(false)
const error = ref('')
const cronograma = ref(null)

const visible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value)
})

// Computed para obtener el símbolo de moneda
const currencySymbol = computed(() => {
    if (props.propiedadData?.currency_symbol) {
        return props.propiedadData.currency_symbol
    }
    // Fallback basado en currency_id
    return props.parametros.currency_id === 1 ? 'S/' : '$'
})

// Computed para totales
const totalCapital = computed(() => {
    if (!cronograma.value?.cronograma_final?.pagos) return 0
    return cronograma.value.cronograma_final.pagos.reduce((sum, pago) => 
        sum + parseFloat(pago.capital || 0), 0
    )
})

const totalIntereses = computed(() => {
    if (!cronograma.value?.cronograma_final?.pagos) return 0
    return cronograma.value.cronograma_final.pagos.reduce((sum, pago) => 
        sum + parseFloat(pago.interes || 0), 0
    )
})

const totalPagar = computed(() => {
    return totalCapital.value + totalIntereses.value
})

watch(() => props.visible, (newVal) => {
    if (newVal && props.propiedadData && props.parametros) {
        generarCronograma()
    }
})

const generarCronograma = async () => {
    if (!props.propiedadData || !props.parametros) {
        error.value = 'Faltan datos para generar el cronograma'
        return
    }

    loading.value = true
    error.value = ''
    cronograma.value = null

    try {
        // Usar tus endpoints existentes
        const endpoint = props.parametros.cronograma === 'frances'
            ? '/simulacion/preview-frances'
            : '/simulacion/preview-americano'

        // Obtener currency_id de la propiedad
        const currencyId = props.propiedadData.currency_id || 1

        const payload = {
            valor_requerido: props.parametros.valor_requerido,
            tem: props.parametros.tem,
            tea: props.parametros.tea,
            plazo: props.parametros.duracion_meses,
            moneda_id: currencyId
        }

        console.log('Payload enviado:', payload)

        const response = await axios.post(endpoint, payload)
        cronograma.value = response.data

        toast.add({
            severity: 'success',
            summary: 'Cronograma generado',
            detail: `Cronograma ${props.parametros.cronograma} generado exitosamente`,
            life: 3000
        })

    } catch (err) {
        console.error('Error al generar cronograma:', err)
        error.value = err.response?.data?.message || 'Error al generar el cronograma'
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.value,
            life: 4000
        })
    } finally {
        loading.value = false
    }
}

const formatCurrency = (value: string | number, symbol: string = 'S/') => {
    if (!value) return `${symbol} 0.00`
    
    const numValue = typeof value === 'string' ? parseFloat(value) : value
    if (isNaN(numValue)) return `${symbol} 0.00`
    
    return `${symbol} ${numValue.toLocaleString('es-PE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })}`
}

const exportarCSV = () => {
    if (!cronograma.value?.cronograma_final?.pagos) return

    try {
        // Encabezados del CSV
        const headers = [
            'Cuota N°',
            'Fecha Vencimiento',
            'Saldo Inicial',
            'Cuota Total',
            'Interés',
            'Capital',
            'Saldo Final'
        ]

        // Preparar filas de datos
        const rows = cronograma.value.cronograma_final.pagos.map(pago => [
            pago.cuota,
            pago.vcmto,
            parseFloat(pago.saldo_inicial || 0).toFixed(2),
            parseFloat(pago.cuota_neta || 0).toFixed(2),
            parseFloat(pago.interes || 0).toFixed(2),
            parseFloat(pago.capital || 0).toFixed(2),
            parseFloat(pago.saldo_final || 0).toFixed(2)
        ])

        // Agregar línea vacía y totales
        rows.push(['', '', '', '', '', '', ''])
        rows.push([
            '',
            'TOTALES',
            '',
            totalPagar.value.toFixed(2),
            totalIntereses.value.toFixed(2),
            totalCapital.value.toFixed(2),
            ''
        ])

        // Construir el contenido CSV
        let csvContent = headers.join(',') + '\n'
        rows.forEach(row => {
            csvContent += row.map(cell => {
                // Escapar comas y comillas en los datos
                const cellStr = String(cell)
                if (cellStr.includes(',') || cellStr.includes('"') || cellStr.includes('\n')) {
                    return `"${cellStr.replace(/"/g, '""')}"`
                }
                return cellStr
            }).join(',') + '\n'
        })

        // Crear blob y descargar
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
        const link = document.createElement('a')
        
        // Generar nombre del archivo
        const tipoCronograma = props.parametros.cronograma === 'frances' ? 'Frances' : 'Americano'
        const nombrePropiedad = props.propiedadData?.nombre?.replace(/\s+/g, '_') || 'Propiedad'
        const fecha = new Date().toISOString().split('T')[0]
        const nombreArchivo = `Cronograma_${tipoCronograma}_${nombrePropiedad}_${fecha}.csv`

        link.href = URL.createObjectURL(blob)
        link.download = nombreArchivo
        link.style.display = 'none'
        
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)

        toast.add({
            severity: 'success',
            summary: 'Exportado',
            detail: 'Cronograma exportado a CSV exitosamente',
            life: 3000
        })
    } catch (err) {
        console.error('Error al exportar CSV:', err)
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo exportar el cronograma a CSV',
            life: 4000
        })
    }
}

const cerrarDialog = () => {
    visible.value = false
    cronograma.value = null
    error.value = ''
    loading.value = false
}
</script>