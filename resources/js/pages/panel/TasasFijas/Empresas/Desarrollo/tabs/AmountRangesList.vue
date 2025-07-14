<template>
    <div>
        <h6 class="mb-3 font-semibold">Rangos registrados</h6>

        <DataTable :value="rangos" stripedRows class="p-datatable-sm mb-4">
            <Column field="desde" header="Desde">
                <template #body="{ data }">
                    {{ formatMoney(data.desde, data.moneda) }}
                </template>
            </Column>
            <Column field="hasta" header="Hasta">
                <template #body="{ data }">
                    {{ data.hasta ? formatMoney(data.hasta, data.moneda) : 'En adelante' }}
                </template>
            </Column>
            <Column field="moneda" header="Moneda" />
            <Column header="">
                <template #body="{ data }">
                    <Button icon="pi pi-trash" severity="danger" text @click="eliminar(data.id)" />
                </template>
            </Column>
        </DataTable>

        <Divider />

        <h6 class="font-semibold mb-2">Agregar nuevo rango</h6>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="text-sm font-medium">Desde</label>
                <InputNumber v-model="form.desde" class="w-full" :min="0" :mode="'decimal'" :minFractionDigits="2"
                    :maxFractionDigits="2" />
            </div>
            <div>
                <label class="text-sm font-medium">Hasta</label>
                <InputNumber v-model="form.hasta" class="w-full" :min="0" :mode="'decimal'" :minFractionDigits="2"
                    :maxFractionDigits="2" />
            </div>
            <div>
                <label class="text-sm font-medium">Moneda</label>
                <Dropdown v-model="form.moneda" :options="monedas" optionLabel="label" optionValue="value"
                    placeholder="Seleccione" class="w-full" />
            </div>
            <div class="flex items-end">
                <Button label="Agregar" icon="pi pi-plus" severity="contrast" @click="guardar" :loading="loading" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'

// PrimeVue Components
import Button from 'primevue/button'
import InputNumber from 'primevue/inputnumber'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Divider from 'primevue/divider'
import Dropdown from 'primevue/dropdown'

const props = defineProps({ empresaId: Number })

const toast = useToast()
const rangos = ref([])
const loading = ref(false)
const form = ref({ desde: null, hasta: null, moneda: null })

const monedas = [
    { label: 'Soles (PEN)', value: 'PEN' },
    { label: 'Dólares (USD)', value: 'USD' }
]

function formatMoney(value, currency = 'PEN') {
    return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: currency
    }).format(parseFloat(value || 0))
}

async function cargarRangos() {
    try {
        const res = await axios.get(`/amount-ranges/${props.empresaId}`)
        rangos.value = res.data.data
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar', life: 3000 })
    }
}

async function guardar() {
    loading.value = true
    try {
        await axios.post(`/amount-ranges`, {
            corporate_entity_id: props.empresaId,
            desde: form.value.desde,
            hasta: form.value.hasta,
            moneda: form.value.moneda
        })
        toast.add({ severity: 'success', summary: 'Rango agregado', life: 3000 })
        form.value = { desde: null, hasta: null, moneda: null }
        await cargarRangos()
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo guardar', life: 3000 })
    } finally {
        loading.value = false
    }
}

async function eliminar(id) {
    try {
        await axios.delete(`/amount-ranges/${id}`)
        await cargarRangos()
        toast.add({ severity: 'info', summary: 'Rango eliminado', life: 3000 })
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo eliminar', life: 3000 })
    }
}

watch(() => props.empresaId, () => {
    if (props.empresaId) cargarRangos()
})

onMounted(() => {
    if (props.empresaId) cargarRangos()
})
</script>
