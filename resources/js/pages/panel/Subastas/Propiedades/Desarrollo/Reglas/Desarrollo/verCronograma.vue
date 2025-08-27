<template>
    <Dialog v-model:visible="visible" modal header="Detalle del Cronograma" :style="{ width: '70vw' }"
        :breakpoints="{ '960px': '90vw', '640px': '95vw' }">
        <template v-if="propiedad">
            <!-- Información de la propiedad -->
            <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                <h5 class="m-0 mb-2">{{ propiedad.nombre }}</h5>
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div><strong>Valor:</strong> {{ formatCurrency(propiedad.valor_estimado) }}</div>
                    <div><strong>Requerido:</strong> {{ formatCurrency(propiedad.requerido) }}</div>
                    <div><strong>TEA:</strong> {{ propiedad.tea }}%</div>
                    <div><strong>TEM:</strong> {{ propiedad.tem }}%</div>
                </div>
            </div>

            <!-- Resumen del cronograma -->
            <div v-if="resumenData && Object.keys(resumenData).length" class="mb-4 p-3 bg-blue-50 rounded-lg">
                <h6 class="m-0 mb-2">Resumen del Cronograma</h6>
                <div class="grid grid-cols-4 gap-4 text-sm">
                    <div><strong>Total Cuotas:</strong> {{ totalesData.numero_cuotas || 'N/A' }}</div>
                    <div><strong>Primera Cuota:</strong> {{ resumenData.primera_cuota || 'N/A' }}</div>
                    <div><strong>Última Cuota:</strong> {{ resumenData.ultima_cuota || 'N/A' }}</div>
                    <div><strong>Estado:</strong> {{ resumenData.estado_property_investor || 'N/A' }}</div>
                </div>
                <div class="grid grid-cols-3 gap-4 text-sm mt-2">
                    <div><strong>Total Capital:</strong> {{ formatCurrency(totalesData.total_capital) }}</div>
                    <div><strong>Total Intereses:</strong> {{ formatCurrency(totalesData.total_intereses) }}</div>
                    <div><strong>Total a Pagar:</strong> {{ formatCurrency(totalesData.total_cuotas) }}</div>
                </div>
            </div>

            <DataTable :value="cronogramaData" :paginator="false" 
                class="p-datatable-sm" scrollable scrollHeight="400px"
                :loading="loading">
                
                <Column field="cuota" header="Cuota" style="width: 80px" frozen>
                    <template #body="{ data }">
                        <div class="font-semibold">{{ data.cuota }}</div>
                    </template>
                </Column>

                <Column field="vencimiento" header="Vencimiento" style="width: 120px">
                    <template #body="{ data }">
                        <div>{{ formatDate(data.vencimiento) }}</div>
                    </template>
                </Column>

                <Column field="saldo_inicial" header="Saldo Inicial" style="width: 140px">
                    <template #body="{ data }">
                        <div>{{ formatCurrency(data.saldo_inicial) }}</div>
                    </template>
                </Column>
                
                <Column field="capital" header="Capital" style="width: 140px">
                    <template #body="{ data }">
                        <div class="font-semibold text-green-600">{{ formatCurrency(data.capital) }}</div>
                    </template>
                </Column>
                
                <Column field="intereses" header="Intereses" style="width: 140px">
                    <template #body="{ data }">
                        <div class="text-orange-600">{{ formatCurrency(data.intereses) }}</div>
                    </template>
                </Column>
                
                <Column field="cuota_neta" header="Cuota Neta" style="width: 140px">
                    <template #body="{ data }">
                        <div class="font-semibold">{{ formatCurrency(data.cuota_neta) }}</div>
                    </template>
                </Column>
                <Column field="total_cuota" header="Total Cuota" style="width: 140px">
                    <template #body="{ data }">
                        <div class="font-bold text-blue-600">{{ formatCurrency(data.total_cuota) }}</div>
                    </template>
                </Column>

                <Column field="saldo_final" header="Saldo Final" style="width: 140px">
                    <template #body="{ data }">
                        <div>{{ formatCurrency(data.saldo_final) }}</div>
                    </template>
                </Column>

                <Column field="estado" header="Estado" style="width: 100px">
                    <template #body="{ data }">
                        <Tag :value="data.estado" :severity="getEstadoSeverity(data.estado)" />
                    </template>
                </Column>
            </DataTable>

            <!-- Mensaje si no hay datos -->
            <div v-if="!loading && cronogramaData.length === 0" class="text-center p-4">
                <i class="pi pi-info-circle text-4xl text-gray-400 mb-2"></i>
                <p class="text-gray-600">No hay cronograma disponible para esta propiedad</p>
            </div>
        </template>

        <template #footer>
            <Button 
                label="Exportar" 
                icon="pi pi-download" 
                @click="exportar" 
                outlined 
                class="mr-2" 
                severity="contrast"
                :disabled="cronogramaData.length === 0"
            />
            <Button label="Cerrar" icon="pi pi-times" @click="cerrar" text severity="secondary"/>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'

const props = defineProps({
    modelValue: Boolean,
    propiedad: Object
})

const emit = defineEmits(['update:modelValue', 'cerrar'])

const toast = useToast()
const visible = ref(props.modelValue)
const cronogramaData = ref([])
const totalesData = ref({})
const resumenData = ref({})
const loading = ref(false)

watch(() => props.modelValue, (val) => {
    visible.value = val
    if (val && props.propiedad) {
        cargarCronograma()
    }
})

watch(visible, (val) => {
    emit('update:modelValue', val)
    if (!val) {
        // Limpiar datos al cerrar
        cronogramaData.value = []
        totalesData.value = {}
        resumenData.value = {}
        emit('cerrar')
    }
})

const cargarCronograma = async () => {
    // ✅ USAR EL ID NUMÉRICO PARA LA URL
    const propertyId = props.propiedad?.id
    
    if (!propertyId) {
        console.error('No se encontró id numérico en:', props.propiedad)
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'ID de propiedad no válido',
            life: 3000
        })
        return
    }

    loading.value = true
    console.log('🔍 Cargando cronograma para ID:', propertyId)
    console.log('📝 Propiedad completa:', props.propiedad)
    
    try {
        // ✅ USAR EL ID NUMÉRICO EN LA URL
        const response = await axios.get(`/propiedad/${propertyId}/cronograma`)
        
        console.log('✅ Respuesta del cronograma:', response.data)
        
        if (response.data.success && response.data.data) {
            const { cronograma, totales, resumen } = response.data.data
            
            cronogramaData.value = cronograma || []
            totalesData.value = totales || {}
            resumenData.value = resumen || {}
            
            console.log('📊 Cronograma cargado:', {
                cuotas: cronogramaData.value.length,
                totales: totalesData.value,
                resumen: resumenData.value
            })
        } else {
            throw new Error('Estructura de respuesta inválida')
        }
        
    } catch (error) {
        console.error('❌ Error al cargar cronograma:', error)
        
        // Mostrar error más específico
        let errorMessage = 'No se pudo cargar el cronograma'
        if (error.response?.status === 404) {
            errorMessage = 'Cronograma no encontrado para esta propiedad'
        } else if (error.response?.status === 500) {
            errorMessage = 'Error interno del servidor'
        }
        
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage,
            life: 3000
        })
    } finally {
        loading.value = false
    }
}

const cerrar = () => {
    visible.value = false
}

const formatDate = (dateString) => {
    if (!dateString) return ''
    
    // Si viene en formato DD-MM-YYYY, convertir
    if (dateString.includes('-') && dateString.split('-')[0].length === 2) {
        const [day, month, year] = dateString.split('-')
        return `${day}/${month}/${year}`
    }
    
    // Si viene en formato estándar de fecha
    const date = new Date(dateString)
    if (isNaN(date.getTime())) return dateString // Si no es válida, devolver original
    
    return date.toLocaleDateString('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    })
}

const formatCurrency = (amount) => {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return 'S/ 0.00'
    }
    
    return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(parseFloat(amount))
}

const getEstadoSeverity = (estado) => {
    switch (estado?.toLowerCase()) {
        case 'pagada':
        case 'pagado':
            return 'success'
        case 'pendiente':
            return 'warn'
        case 'vencida':
        case 'vencido':
            return 'danger'
        default:
            return 'secondary'
    }
}

const exportar = async () => {
    try {
        if (!cronogramaData.value.length) {
            toast.add({
                severity: 'warn',
                summary: 'Advertencia',
                detail: 'No hay datos para exportar',
                life: 3000
            })
            return
        }

        // Crear CSV con mejor formato
        const headers = [
            'Cuota', 
            'Vencimiento', 
            'Saldo Inicial', 
            'Capital', 
            'Intereses', 
            'Cuota Neta',
            'IGV', 
            'Total Cuota', 
            'Saldo Final', 
            'Estado'
        ]
        
        const csvRows = cronogramaData.value.map(row => [
            row.cuota,
            formatDate(row.vencimiento),
            parseFloat(row.saldo_inicial || 0).toFixed(2),
            parseFloat(row.capital || 0).toFixed(2),
            parseFloat(row.intereses || 0).toFixed(2),
            parseFloat(row.cuota_neta || 0).toFixed(2),
            parseFloat(row.igv || 0).toFixed(2),
            parseFloat(row.total_cuota || 0).toFixed(2),
            parseFloat(row.saldo_final || 0).toFixed(2),
            row.estado
        ])
        
        // Agregar fila de totales si existe
        if (totalesData.value.total_capital) {
            csvRows.push([
                'TOTALES',
                '',
                '',
                parseFloat(totalesData.value.total_capital).toFixed(2),
                parseFloat(totalesData.value.total_intereses).toFixed(2),
                '',
                parseFloat(totalesData.value.total_igv || 0).toFixed(2),
                parseFloat(totalesData.value.total_cuotas).toFixed(2),
                '',
                ''
            ])
        }
        
        const csvContent = [
            headers.join(','),
            ...csvRows.map(row => row.join(','))
        ].join('\n')

        // Descargar archivo
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
        const link = document.createElement('a')
        const url = URL.createObjectURL(blob)
        link.setAttribute('href', url)
        link.setAttribute('download', `cronograma_${props.propiedad?.nombre || 'propiedad'}_${Date.now()}.csv`)
        link.style.visibility = 'hidden'
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        URL.revokeObjectURL(url)

        toast.add({
            severity: 'success',
            summary: 'Exportado',
            detail: 'El cronograma se exportó correctamente',
            life: 3000
        })
    } catch (error) {
        console.error('❌ Error al exportar:', error)
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo exportar el cronograma',
            life: 3000
        })
    }
}
</script>
