<template>
    <Dialog v-model:visible="visible" modal header="Detalle del Cronograma" :style="{ width: '60vw' }"
        :breakpoints="{ '960px': '90vw', '640px': '95vw' }">
        <template v-if="propiedad">
            <DataTable :value="cronogramaData" :paginator="true" :rows="10" :totalRecords="totalRecords"
                :loading="loading" lazy dataKey="id" @page="onPage"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} cuotas" class="p-datatable-sm"
                scrollable scrollHeight="400px">
                <Column selectionMode="multiple" style="width: 3rem" :exportable="false" />
                <Column field="cuota" header="Cuota" style="width: 80px" frozen>
                    <template #body="{ data }">
                        <div>{{ data.cuota }}</div>
                    </template>
                </Column>

                <Column field="vencimiento" header="Vencimiento" style="width: 120px">
                    <template #body="{ data }">
                        <div>{{ formatDate(data.vencimiento) }}</div>
                    </template>
                </Column>

                <Column field="saldo_inicial" header="Saldo Inicial" style="width: 120px">
                    <template #body="{ data }">
                        <div>{{ formatCurrency(data.saldo_inicial) }}</div>
                    </template>
                </Column>
                <Column field="intereses" header="Intereses" style="width: 120px">
                    <template #body="{ data }">
                        <div>{{ formatCurrency(data.intereses) }}</div>
                    </template>
                </Column>
                <Column field="capital" header="Capital" style="width: 120px">
                    <template #body="{ data }">
                        <div>{{ formatCurrency(data.capital) }}</div>
                    </template>
                </Column>
                <Column field="cuota_neta" header="Cuota Neta" style="width: 120px">
                    <template #body="{ data }">
                        <div>{{ formatCurrency(data.cuota_neta) }}</div>
                    </template>
                </Column>
                <Column field="saldo_final" header="Saldo Final" style="width: 120px">
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
        </template>

        <template #footer>
            <Button label="Exportar" icon="pi pi-download" @click="exportar" outlined class="mr-2" severity="contrast"/>
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

const emit = defineEmits(['update:modelValue'])

const toast = useToast()
const visible = ref(props.modelValue)
const cronogramaData = ref([])
const totalRecords = ref(0)
const loading = ref(false)
const currentPage = ref(1)

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
        totalRecords.value = 0
        currentPage.value = 1
    }
})

const cargarCronograma = async (page = 1) => {
    if (!props.propiedad?.id) return

    loading.value = true
    try {
        const response = await axios.get(`/propiedad/${props.propiedad.id}/cronograma`, {
            params: { page }
        })

        cronogramaData.value = response.data.data
        totalRecords.value = response.data.meta.total
        currentPage.value = response.data.meta.current_page
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo cargar el cronograma',
            life: 3000
        })
    } finally {
        loading.value = false
    }
}

const onPage = (event) => {
    cargarCronograma(event.page + 1)
}

const cerrar = () => {
    visible.value = false
}

const formatDate = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    return date.toLocaleDateString('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    })
}

const formatCurrency = (amount) => {
    if (!amount) return 'S/ 0.00'
    return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
        minimumFractionDigits: 2
    }).format(parseFloat(amount))
}

const getEstadoSeverity = (estado) => {
    switch (estado) {
        case 'pagada':
            return 'success'
        case 'pendiente':
            return 'warn'
        case 'vencida':
            return 'danger'
        default:
            return 'secondary'
    }
}

const exportar = async () => {
    try {
        // Cargar todos los datos para exportar
        const response = await axios.get(`/propiedad/${props.propiedad.id}/cronograma`, {
            params: { per_page: totalRecords.value }
        })

        const data = response.data.data

        // Crear CSV
        const headers = ['Cuota', 'Vencimiento', 'Saldo Inicial', 'Capital', 'Intereses', 'Cuota Neta', 'IGV', 'Total Cuota', 'Saldo Final', 'Estado']
        const csvContent = [
            headers.join(','),
            ...data.map(row => [
                row.cuota,
                row.vencimiento,
                row.saldo_inicial,
                row.capital,
                row.intereses,
                row.cuota_neta,
                row.igv,
                row.total_cuota,
                row.saldo_final,
                row.estado
            ].join(','))
        ].join('\n')

        // Descargar archivo
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
        const link = document.createElement('a')
        const url = URL.createObjectURL(blob)
        link.setAttribute('href', url)
        link.setAttribute('download', `cronograma_${props.propiedad.nombre}_${Date.now()}.csv`)
        link.style.visibility = 'hidden'
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)

        toast.add({
            severity: 'success',
            summary: 'Exportado',
            detail: 'El cronograma se exportó correctamente',
            life: 3000
        })
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo exportar el cronograma',
            life: 3000
        })
    }
}
</script>
