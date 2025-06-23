<template>
    <div class="card">
        <!-- Campo de Monto -->
        <div class="flex flex-col md:flex-row items-start md:items-end gap-4">
            <div class="flex-1 w-full">
                <label class="block font-bold mb-2">Monto a invertir <span class="text-red-500">*</span></label>
                <InputNumber v-model="monto" mode="currency" currency="PEN" locale="es-PE" class="w-full" :min="1" />
            </div>
            <Button label="Simulación" icon="pi pi-search" @click="buscarTasas" :loading="loading"
                :disabled="!monto || monto <= 0" severity="danger" />
        </div>
    </div>
    
    <div class="card space-y-6">
        <!-- Estados -->
        <div v-if="loading" class="text-center text-blue-600">
            <i class="pi pi-spin pi-spinner text-xl"></i> Consultando tasas disponibles...
        </div>
        <div v-if="error" class="text-center text-red-600">
            <i class="pi pi-exclamation-triangle"></i> {{ error }}
        </div>
        <div v-if="!loading && !error && cooperativas.length === 0 && buscado" class="text-center text-gray-600">
            <i class="pi pi-info-circle"></i> No se encontraron tasas para el monto ingresado.
        </div>

        <!-- Resultados -->
        <div v-for="(coop, index) in cooperativas" :key="index">
            <Panel :header="`${coop.orden} - ${coop.cooperativa}`" toggleable :collapsed="true">
                <!-- Iteramos por cada tipo de tasa de la cooperativa -->
                <div v-for="(tipoTasa, tipoIndex) in coop.tipos_tasa" :key="tipoIndex" class="mb-6">
                    <!-- Título del tipo de tasa -->
                    <div class="mb-3">
                        <h5 class="text-lg font-semibold text-gray-700 border-b border-gray-200 pb-2">
                            {{ tipoTasa.tipo_tasa }}
                        </h5>
                    </div>
                    
                    <!-- Tabla para este tipo de tasa -->
                    <DataTable :value="tipoTasa.tasas" v-model:selection="selecciones[`${index}_${tipoIndex}`]" 
                        dataKey="plazo_dias" selectionMode="single" :paginator="true" :rows="5" 
                        responsiveLayout="scroll" :rowsPerPageOptions="[5, 10, 25]"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} tasas"
                        class="p-datatable-sm"
                        @row-select="onTasaSeleccionada(index, tipoIndex, $event)"
                        @row-unselect="onTasaDeseleccionada(index, tipoIndex)">
                        
                        <template #header>
                            <div class="flex justify-between items-center">
                                <h4 class="m-0">Tasas {{ tipoTasa.tipo_tasa }} disponibles</h4>
                            </div>
                        </template>

                        <Column v-for="col in tipoTasa.tipo_columnas" :key="col" :field="col" 
                            :header="mapColumna(col)" sortable style="min-width: 10rem">
                            <template #body="slotProps">
                                <span>{{ slotProps.data[col] }}</span>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Formas de pago cuando hay una tasa seleccionada -->
                    <div v-if="selecciones[`${index}_${tipoIndex}`]" class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <h6 class="font-semibold mb-3 text-gray-700">Formas de pago disponibles:</h6>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div v-for="formaPago in formasPago" :key="formaPago.valor" 
                                class="border rounded-lg p-3 cursor-pointer transition-all hover:shadow-md"
                                :class="{ 
                                    'bg-blue-50 border-blue-300': formaPagoSeleccionada[`${index}_${tipoIndex}`] === formaPago.valor,
                                    'bg-white border-gray-200': formaPagoSeleccionada[`${index}_${tipoIndex}`] !== formaPago.valor
                                }"
                                @click="seleccionarFormaPago(index, tipoIndex, formaPago.valor)">
                                <div class="flex items-center space-x-2">
                                    <i :class="formaPago.icono" class="text-blue-600"></i>
                                    <div>
                                        <div class="font-medium">{{ formaPago.nombre }}</div>
                                        <div class="text-sm text-gray-500">{{ formaPago.descripcion }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botón Ver Cronograma -->
                        <div v-if="formaPagoSeleccionada[`${index}_${tipoIndex}`]" class="mt-4 text-center">
                            <Button label="Ver Cronograma" icon="pi pi-calendar" 
                                @click="verCronograma(index, tipoIndex)"
                                class="p-button-outlined" />
                        </div>
                    </div>
                </div>
            </Panel>
        </div>
    </div>

    <!-- Modal del Cronograma -->
    <Dialog v-model:visible="mostrarCronograma" modal header="Cronograma de Pagos" 
        :style="{ width: '90vw', maxWidth: '1200px' }" :maximizable="true">
        <div class="space-y-4">
            <!-- Información de la selección -->
            <div class="bg-blue-50 p-4 rounded-lg">
                <h5 class="font-semibold text-blue-800 mb-2">Detalle de la Inversión</h5>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="font-medium">Cooperativa:</span>
                        <div>{{ datosCronograma.cooperativa }}</div>
                    </div>
                    <div>
                        <span class="font-medium">Tipo de Tasa:</span>
                        <div>{{ datosCronograma.tipoTasa }}</div>
                    </div>
                    <div>
                        <span class="font-medium">Monto:</span>
                        <div>S/ {{ monto?.toLocaleString('es-PE') }}</div>
                    </div>
                    <div>
                        <span class="font-medium">Forma de Pago:</span>
                        <div>{{ datosCronograma.formaPago }}</div>
                    </div>
                </div>
            </div>

            <!-- Cronograma (vacío por ahora) -->
            <div class="text-center py-8 text-gray-500">
                <i class="pi pi-calendar text-4xl mb-4"></i>
                <div class="text-lg">Cronograma en construcción</div>
                <div class="text-sm">Los datos del cronograma se cargarán próximamente</div>
            </div>
        </div>
        
        <template #footer>
            <Button label="Cerrar" @click="mostrarCronograma = false" class="p-button-secondary" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Panel from 'primevue/panel'
import Dialog from 'primevue/dialog'
import axios from 'axios'

const monto = ref(null)
const cooperativas = ref([])
const loading = ref(false)
const error = ref('')
const buscado = ref(false)
const selecciones = reactive({})
const formaPagoSeleccionada = reactive({})
const mostrarCronograma = ref(false)
const datosCronograma = ref({})

// Formas de pago disponibles
const formasPago = [
    {
        valor: 'mensual',
        nombre: 'Mensual',
        descripcion: 'Pagos cada mes',
        icono: 'pi pi-calendar'
    },
    {
        valor: 'bimestral',
        nombre: 'Bimestral', 
        descripcion: 'Pagos cada 2 meses',
        icono: 'pi pi-calendar-plus'
    },
    {
        valor: 'trimestral',
        nombre: 'Trimestral',
        descripcion: 'Pagos cada 3 meses',
        icono: 'pi pi-calendar-times'
    }
]

const mapColumna = (key) => {
    const labels = {
        'TEA': 'Tasa Efectiva Anual (%)',
        'TEM': 'Tasa Efectiva Mensual (%)',
        'TREA': 'TREA (%)',
        'TREM': 'TREM (%)',
        'plazo_dias': 'Plazo (días)',
        'retorno': 'Monto Retornado',
        'retorno_trea': 'Retorno TREA',
        'retorno_trem': 'Retorno TREM'
    }
    return labels[key] || key
}

const onTasaSeleccionada = (coopIndex, tipoIndex, event) => {
    // Limpiar forma de pago cuando se selecciona una nueva tasa
    const key = `${coopIndex}_${tipoIndex}`
    delete formaPagoSeleccionada[key]
}

const onTasaDeseleccionada = (coopIndex, tipoIndex) => {
    // Limpiar forma de pago cuando se deselecciona una tasa
    const key = `${coopIndex}_${tipoIndex}`
    delete formaPagoSeleccionada[key]
}

const seleccionarFormaPago = (coopIndex, tipoIndex, formaPago) => {
    const key = `${coopIndex}_${tipoIndex}`
    formaPagoSeleccionada[key] = formaPago
}

const verCronograma = (coopIndex, tipoIndex) => {
    const cooperativa = cooperativas.value[coopIndex]
    const tipoTasa = cooperativa.tipos_tasa[tipoIndex]
    const key = `${coopIndex}_${tipoIndex}`
    
    // Obtener el nombre de la forma de pago seleccionada
    const formaPagoObj = formasPago.find(fp => fp.valor === formaPagoSeleccionada[key])
    
    datosCronograma.value = {
        cooperativa: cooperativa.cooperativa,
        tipoTasa: tipoTasa.tipo_tasa,
        formaPago: formaPagoObj?.nombre || 'N/A',
        tasaSeleccionada: selecciones[key]
    }
    
    mostrarCronograma.value = true
}

const buscarTasas = async () => {
    if (!monto.value || monto.value <= 0) return

    loading.value = true
    error.value = ''
    buscado.value = true
    cooperativas.value = []
    
    // Limpiar selecciones anteriores
    Object.keys(selecciones).forEach(key => delete selecciones[key])

    try {
        const response = await axios.post('/api/calculate', {
            amount: monto.value
        })
        
        cooperativas.value = response.data
        
        // Inicializar selecciones para cada tabla (cooperativa + tipo de tasa)
        response.data.forEach((coop, coopIndex) => {
            coop.tipos_tasa.forEach((_, tipoIndex) => {
                selecciones[`${coopIndex}_${tipoIndex}`] = null
            })
        })
        
    } catch (err) {
        error.value = err.response?.data?.error || 'Error al consultar tasas.'
    } finally {
        loading.value = false
    }
}
</script>