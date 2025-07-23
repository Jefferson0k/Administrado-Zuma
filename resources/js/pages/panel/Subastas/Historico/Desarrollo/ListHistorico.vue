<script setup>
import { ref, onMounted, watch } from 'vue'
import { FilterMatchMode } from '@primevue/core/api'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'
import axios from 'axios'

const toast = useToast()
const dt = ref()
const products = ref([])
const expandedRowGroups = ref()

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

const totalRecords = ref(0)
const loading = ref(false)
const currentPage = ref(1)
const rows = ref(10)

const loadData = async () => {
    loading.value = true
    try {
        const response = await axios.get(`/bids?page=${currentPage.value}&per_page=${rows.value}`)
        products.value = response.data.data
        totalRecords.value = response.data.meta.total
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar pujas', life: 3000 })
    } finally {
        loading.value = false
    }
}

const getPuestoTag = (puesto) => {
    switch (puesto) {
        case 1:
            return { severity: 'success', value: '🥇 1er Lugar', icon: 'pi pi-crown' }
        case 2:
            return { severity: 'warn', value: '🥈 2do Lugar', icon: 'pi pi-star' }
        case 3:
            return { severity: 'info', value: '🥉 3er Lugar', icon: 'pi pi-star-fill' }
        default:
            return { severity: 'secondary', value: `${puesto}° Lugar`, icon: 'pi pi-circle' }
    }
}

const calculateBidsInAuction = (nombreSubasta) => {
    let total = 0
    if (products.value) {
        for (let product of products.value) {
            if (product.nombre_subasta === nombreSubasta) {
                total++
            }
        }
    }
    return total
}

const getHighestBidInAuction = (nombreSubasta) => {
    if (!products.value) return 0

    const auctionBids = products.value.filter(p => p.nombre_subasta === nombreSubasta)
    if (auctionBids.length === 0) return 0

    return Math.max(...auctionBids.map(bid => bid.monto))
}

const onRowGroupExpand = (event) => {
    toast.add({
        severity: 'info',
        summary: 'Subasta Expandida',
        detail: `Viendo pujas de: ${event.data}`,
        life: 2000
    })
}

const onRowGroupCollapse = (event) => {
    toast.add({
        severity: 'secondary',
        summary: 'Subasta Colapsada',
        detail: `Ocultando: ${event.data}`,
        life: 2000
    })
}

onMounted(loadData)
watch(currentPage, loadData)
watch(rows, loadData)
</script>

<template>
    <DataTable ref="dt" v-model:expandedRowGroups="expandedRowGroups" :value="products" :lazy="true" dataKey="id"
        :paginator="true" :rows="rows" :totalRecords="totalRecords" :loading="loading"
        @page="e => { currentPage.value = e.page + 1 }" :filters="filters" expandableRowGroups rowGroupMode="subheader"
        groupRowsBy="nombre_subasta" @rowgroup-expand="onRowGroupExpand" @rowgroup-collapse="onRowGroupCollapse"
        sortMode="single" sortField="nombre_subasta" :sortOrder="1"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25]" currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} pujas"
        class="p-datatable-sm">
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Histórico de Pujas por Subasta</h4>
                <IconField>
                    <InputIcon><i class="pi pi-search" /></InputIcon>
                    <InputText v-model="filters['global'].value" placeholder="Buscar subastas, inversionistas..." />
                </IconField>
            </div>
        </template>

        <!-- Header del grupo (nombre de la subasta) -->
        <template #groupheader="slotProps">
            <div class="flex items-center gap-3 p-3 bg-blue-50 border-l-4 border-blue-500">
                <i class="pi pi-home text-blue-600 text-xl"></i>
                <div class="flex-1">
                    <span class="font-bold text-lg text-blue-900">{{ slotProps.data.nombre_subasta }}</span>
                    <div class="text-sm text-blue-700 mt-1">
                        <span class="mr-4">
                            <i class="pi pi-users mr-1"></i>
                            {{ calculateBidsInAuction(slotProps.data.nombre_subasta) }} participantes
                        </span>
                        <span>
                            <i class="pi pi-dollar mr-1"></i>
                            Puja más alta: {{ new Intl.NumberFormat('es-PE', {
                                style: 'currency',
                                currency: 'PEN'
                            }).format(getHighestBidInAuction(slotProps.data.nombre_subasta)) }}
                        </span>
                    </div>
                </div>
                <Tag :value="slotProps.data.estado_subasta"
                    :severity="slotProps.data.estado_subasta === 'Finalizada' ? 'success' : 'info'" class="ml-2" />
            </div>
        </template>

        <!-- Columnas de datos -->
        <Column field="nombre_subasta" header="Subasta" style="display: none;"></Column>

        <!-- Puesto -->
        <Column header="Posición" style="width: 10rem">
            <template #body="slotProps">
                <Tag v-if="slotProps.data.puesto" :severity="getPuestoTag(slotProps.data.puesto).severity"
                    :value="getPuestoTag(slotProps.data.puesto).value" />
                <span v-else class="text-gray-400">-</span>
            </template>
        </Column>

        <!-- Inversionista -->
        <Column field="nombre" header="Inversionista" style="min-width: 18rem">
            <template #body="slotProps">
                <div class="flex items-center gap-2">
                    <i class="pi pi-user text-gray-500"></i>
                    <span>{{ slotProps.data.nombre }}</span>
                </div>
            </template>
        </Column>

        <Column field="investor" header="Documento" style="min-width: 10rem">
            <template #body="slotProps">
                <span class="font-mono text-sm">{{ slotProps.data.investor }}</span>
            </template>
        </Column>

        <!-- Monto -->
        <Column field="monto" header="Monto Pujado" style="min-width: 12rem">
            <template #body="slotProps">
                <div class="flex items-center gap-2">
                    <i class="pi pi-dollar text-green-600"></i>
                    <span class="font-semibold text-green-700">
                        {{ new Intl.NumberFormat('es-PE', {
                            style: 'currency',
                            currency: 'PEN'
                        }).format(slotProps.data.monto) }}
                    </span>
                </div>
            </template>
        </Column>

        <!-- Fecha de puja -->
        <Column field="created_at" header="Fecha de Puja" style="min-width: 15rem">
            <template #body="slotProps">
                <div class="flex items-center gap-2">
                    <i class="pi pi-calendar text-gray-500"></i>
                    <span>{{ slotProps.data.created_at }}</span>
                </div>
            </template>
        </Column>

        <!-- Footer del grupo (resumen) -->
        <template #groupfooter="slotProps">
            <div class="flex justify-between items-center p-3 bg-gray-50 border-t">
                <div class="flex items-center gap-4 text-sm text-gray-600">
                    <span>
                        <i class="pi pi-users mr-1"></i>
                        Total de pujas: <strong>{{ calculateBidsInAuction(slotProps.data.nombre_subasta) }}</strong>
                    </span>
                    <span v-if="slotProps.data.ganador_nombre">
                        <i class="pi pi-crown mr-1 text-yellow-500"></i>
                        Ganador: <strong class="text-yellow-700">{{ slotProps.data.ganador_nombre }}</strong>
                    </span>
                </div>
                <div class="text-right">
                    <div class="text-lg font-bold text-green-700">
                        Puja ganadora: {{ new Intl.NumberFormat('es-PE', {
                            style: 'currency',
                            currency: 'PEN'
                        }).format(getHighestBidInAuction(slotProps.data.nombre_subasta)) }}
                    </div>
                </div>
            </div>
        </template>
    </DataTable>
    <Toast />
</template>