<template>
    <Dialog v-model:visible="visible" modal header="Cronograma de Pagos" :style="{ width: '90vw', maxWidth: '1000px' }"
        :closable="true" :dismissableMask="true">

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-8">
            <i class="pi pi-spinner pi-spin text-2xl"></i>
            <span class="ml-2">Generando cronograma...</span>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="text-center py-8">
            <i class="pi pi-exclamation-triangle text-red-500 text-3xl mb-4"></i>
            <p class="text-red-600 mb-4">{{ error }}</p>
            <Button label="Reintentar" icon="pi pi-refresh" @click="generarCronograma" />
        </div>

        <!-- Cronograma Content -->
        <div v-else-if="cronograma" class="space-y-6">
            <div class="overflow-x-auto">
                <DataTable :value="cronograma.cronograma_final.pagos"  :paginator="true"
                    :rows="10" class="w-full">
                    <Column field="cuota" header="Nº" :sortable="true" class="w-16">
                        <template #body="slotProps">
                            <span class="font-semibold">{{ slotProps.data.cuota }}</span>
                        </template>
                    </Column>
                    <Column field="saldo_inicial" header="Saldo Inicial" :sortable="true" class="text-right">
                        <template #body="slotProps">
                            {{ formatCurrency(slotProps.data.saldo_inicial) }}
                        </template>
                    </Column>
                    <Column field="cuota_neta" header="Cuota" :sortable="true" class="text-right">
                        <template #body="slotProps">
                            <span class="font-semibold text-blue-600">
                                {{ formatCurrency(slotProps.data.cuota_neta) }}
                            </span>
                        </template>
                    </Column>
                    <Column field="interes" header="Interés" :sortable="true" class="text-right">
                        <template #body="slotProps">
                            <span class="text-red-600">
                                {{ formatCurrency(slotProps.data.interes) }}
                            </span>
                        </template>
                    </Column>
                    <Column field="capital" header="Capital" :sortable="true" class="text-right">
                        <template #body="slotProps">
                            <span class="text-green-600">
                                {{ formatCurrency(slotProps.data.capital) }}
                            </span>
                        </template>
                    </Column>
                    <Column field="saldo_final" header="Saldo Final" :sortable="true" class="text-right">
                        <template #body="slotProps">
                            {{ formatCurrency(slotProps.data.saldo_final) }}
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-3">
                <Button label="Cerrar" icon="pi pi-times" severity="secondary" @click="cerrarDialog" />
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
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

interface Props {
    visible: boolean
    propiedadData: any
    parametros: {
        tea: string
        tem: string
        cronograma: string
        duracion_meses: number
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

    try {
        const endpoint = props.parametros.cronograma === 'frances'
            ? '/simulacion/preview-frances'
            : '/simulacion/preview-americano'

        // Usar el valor_requerido de los parámetros (que ahora sí se pasa)
        const valorRequerido = props.parametros.valor_requerido || 
                              parseFloat(props.propiedadData.valor_requerido) || 0

        const payload = {
            valor_requerido: valorRequerido,  // ← CAMBIAR ESTA LÍNEA
            tem: props.parametros.tem,
            tea: props.parametros.tea,
            plazo: props.parametros.duracion_meses,
            moneda_id: 1
        }

        console.log('Payload enviado:', payload)

        const response = await axios.post(endpoint, payload)

        cronograma.value = response.data

        toast.add({
            severity: 'success',
            summary: 'Cronograma generado',
            detail: 'El cronograma se ha generado exitosamente',
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

const formatCurrency = (value) => {
    if (!value) return 'S/ 0.00'
    return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN'
    }).format(parseFloat(value))
}

const cerrarDialog = () => {
    visible.value = false
    cronograma.value = null
    error.value = ''
}
</script>
