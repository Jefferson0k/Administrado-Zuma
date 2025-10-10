<template>
    <div>
        <DataTable 
            ref="dt" 
            v-model:expandedRowGroups="expandedRowGroups" 
            :value="products" 
            :lazy="true" 
            dataKey="id"
            :paginator="true" 
            :rows="rows" 
            :totalRecords="totalRecords" 
            :loading="loading"
            @page="onPageChange" 
            :filters="filters" 
            expandableRowGroups 
            rowGroupMode="subheader"
            :groupRowsBy="groupByField" 
            @rowgroup-expand="onRowGroupExpand" 
            @rowgroup-collapse="onRowGroupCollapse"
            sortMode="single" 
            :sortField="sortField" 
            :sortOrder="sortOrder"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5, 10, 25]" 
            :currentPageReportTemplate="`Mostrando {first} a {last} de {totalRecords} pujas`"
            class="p-datatable-sm"
        >
            <template #header>
                <div class="flex flex-wrap gap-4 items-center justify-between">
                    <h4 class="m-0">{{ isAuctionMode ? 'Histórico de Pujas por Subasta' : 'Histórico de Pujas por Solicitud' }}</h4>
                    <div class="flex gap-3 items-center">
                        <SelectButton 
                            v-model="selectedType" 
                            :options="typeOptions" 
                            optionLabel="label" 
                            optionValue="value" 
                        />
                        <IconField>
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText 
                                v-model="filters['global'].value" 
                                :placeholder="isAuctionMode ? 'Buscar subastas, inversionistas...' : 'Buscar solicitudes, inversionistas...'" 
                            />
                        </IconField>
                    </div>
                </div>
            </template>

            <!-- Header del grupo para SUBASTAS -->
            <template #groupheader="slotProps" v-if="isAuctionMode">
                <div class="flex items-center gap-3 p-3 bg-blue-50 border-l-4 border-blue-500">
                    <i class="pi pi-home text-blue-600 text-xl"></i>
                    <div class="flex-1">
                        <span class="font-bold text-lg text-blue-900">{{ slotProps.data.subasta?.nombre }}</span>
                        <div class="text-sm text-blue-700 mt-1">
                            <span>
                                <i class="pi pi-users mr-1"></i>
                                {{ calculateBidsInAuction(slotProps.data.subasta?.nombre) }} participantes
                            </span>
                        </div>
                    </div>
                    <Tag 
                        :value="slotProps.data.subasta?.estado"
                        :severity="getEstadoSeverity(slotProps.data.subasta?.estado)" 
                        class="ml-2" 
                    />
                </div>
            </template>

            <!-- Header del grupo para SOLICITUDES -->
            <template #groupheader="slotProps" v-else>
                <div class="flex items-center gap-3 p-3 bg-purple-50 border-l-4 border-purple-500">
                    <i class="pi pi-file text-purple-600 text-xl"></i>
                    <div class="flex-1">
                        <span class="font-bold text-lg text-purple-900">{{ slotProps.data.solicitud?.codigo }}</span>
                        <div class="text-sm text-purple-700 mt-1">
                            <span>
                                <i class="pi pi-money-bill mr-1"></i>
                                {{ formatCurrency(slotProps.data.solicitud?.monto_requerido) }} {{ slotProps.data.solicitud?.moneda }}
                            </span>
                            <span class="ml-4">
                                <i class="pi pi-users mr-1"></i>
                                {{ calculateBidsInSolicitud(slotProps.data.solicitud?.codigo) }} inversionistas
                            </span>
                        </div>
                    </div>
                    <Tag 
                        :value="getEstadoSolicitud(slotProps.data.solicitud?.estado)"
                        :severity="getSolicitudEstadoSeverity(slotProps.data.solicitud?.estado)" 
                        class="ml-2" 
                    />
                </div>
            </template>

            <!-- Columna oculta para agrupación -->
            <Column :field="groupByField" header="Referencia" style="display: none;"></Column>

            <!-- Puesto (solo para subastas) -->
            <Column v-if="isAuctionMode" header="Posición" style="width: 12rem">
                <template #body="slotProps">
                    <Tag 
                        v-if="slotProps.data.subasta?.puesto" 
                        :severity="getPuestoTag(slotProps.data.subasta.puesto).severity"
                        :value="getPuestoTag(slotProps.data.subasta.puesto).value" 
                    />
                    <span v-else class="text-gray-400">-</span>
                </template>
            </Column>

            <!-- Ganador (solo para subastas) -->
            <Column v-if="isAuctionMode" header="Ganador" style="width: 10rem">
                <template #body="slotProps">
                    <Tag 
                        v-if="slotProps.data.subasta?.es_ganador" 
                        severity="success" 
                        value="Ganador" 
                        icon="pi pi-trophy"
                    />
                    <span v-else class="text-gray-400">-</span>
                </template>
            </Column>

            <!-- Inversionista -->
            <Column field="investor.nombre" header="Inversionista" style="min-width: 20rem">
                <template #body="slotProps">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-user text-gray-500"></i>
                        <div class="flex flex-col">
                            <span class="font-medium">{{ slotProps.data.investor?.nombre }}</span>
                            <span class="text-xs text-gray-500">Doc: {{ slotProps.data.investor?.document }}</span>
                        </div>
                    </div>
                </template>
            </Column>

            <!-- Fecha de puja -->
            <Column field="created_at" header="Fecha de Puja" style="min-width: 15rem">
                <template #body="slotProps">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-calendar text-gray-500"></i>
                        <span>{{ formatDate(slotProps.data.created_at) }}</span>
                    </div>
                </template>
            </Column>

            <!-- Monto (solo para solicitudes) -->
            <Column v-if="!isAuctionMode" header="Monto Solicitado" style="min-width: 12rem">
                <template #body="slotProps">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-money-bill text-gray-500"></i>
                        <span>{{ formatCurrency(slotProps.data.solicitud?.monto_requerido) }} {{ slotProps.data.solicitud?.moneda }}</span>
                    </div>
                </template>
            </Column>

            <!-- Footer del grupo para SUBASTAS -->
            <template #groupfooter="slotProps" v-if="isAuctionMode">
                <div class="flex justify-between items-center p-3 bg-gray-50 border-t">
                    <div class="flex items-center gap-4 text-sm text-gray-600">
                        <span>
                            <i class="pi pi-users mr-1"></i>
                            Total de participantes: <strong>{{ calculateBidsInAuction(slotProps.data.subasta?.nombre) }}</strong>
                        </span>
                        <span v-if="slotProps.data.subasta?.ganador_nombre">
                            <i class="pi pi-trophy mr-1"></i>
                            Ganador: <strong>{{ slotProps.data.subasta.ganador_nombre }}</strong>
                        </span>
                    </div>
                </div>
            </template>

            <!-- Footer del grupo para SOLICITUDES -->
            <template #groupfooter="slotProps" v-else>
                <div class="flex justify-between items-center p-3 bg-gray-50 border-t">
                    <div class="flex items-center gap-4 text-sm text-gray-600">
                        <span>
                            <i class="pi pi-users mr-1"></i>
                            Total de ofertas: <strong>{{ calculateBidsInSolicitud(slotProps.data.solicitud?.codigo) }}</strong>
                        </span>
                        <span>
                            <i class="pi pi-money-bill mr-1"></i>
                            Monto total: <strong>{{ formatCurrency(slotProps.data.solicitud?.monto_requerido) }} {{ slotProps.data.solicitud?.moneda }}</strong>
                        </span>
                    </div>
                </div>
            </template>

            <template #empty>
                <div class="text-center py-8 text-gray-500">
                    <i class="pi pi-inbox text-4xl mb-2"></i>
                    <p>No se encontraron pujas</p>
                </div>
            </template>

            <template #loading>
                <div class="text-center py-8">
                    <i class="pi pi-spinner pi-spin text-2xl"></i>
                    <p class="mt-2">Cargando pujas...</p>
                </div>
            </template>
        </DataTable>
        <Toast />
    </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { FilterMatchMode } from '@primevue/core/api'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'
import SelectButton from 'primevue/selectbutton'
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

// Filtro por tipo
const selectedType = ref('solicitud')
const typeOptions = ref([
    { label: 'Solicitudes', value: 'solicitud' },
    { label: 'Subastas', value: 'auction' }
])

// Computed properties
const isAuctionMode = computed(() => selectedType.value === 'auction')
const groupByField = computed(() => isAuctionMode.value ? 'subasta.nombre' : 'solicitud.codigo')
const sortField = computed(() => isAuctionMode.value ? 'subasta.puesto' : 'created_at')
const sortOrder = computed(() => isAuctionMode.value ? 1 : -1)

const loadData = async () => {
    loading.value = true
    try {
        const response = await axios.get(`/bids?page=${currentPage.value}&per_page=${rows.value}&type=${selectedType.value}`)
        products.value = response.data.data
        totalRecords.value = response.data.meta.total
    } catch (error) {
        console.error('Error loading data:', error)
        toast.add({ 
            severity: 'error', 
            summary: 'Error', 
            detail: 'No se pudo cargar las pujas', 
            life: 3000 
        })
    } finally {
        loading.value = false
    }
}

// Helper functions
const getPuestoTag = (puesto) => {
    switch (puesto) {
        case 1:
            return { severity: 'success', value: '🥇 1er Lugar' }
        case 2:
            return { severity: 'warning', value: '🥈 2do Lugar' }
        case 3:
            return { severity: 'info', value: '🥉 3er Lugar' }
        default:
            return { severity: 'secondary', value: `${puesto}° Lugar` }
    }
}

const getEstadoSeverity = (estado) => {
    switch (estado) {
        case 'Finalizada':
            return 'success'
        case 'En progreso':
            return 'info'
        case 'Cancelada':
            return 'danger'
        default:
            return 'secondary'
    }
}

const getSolicitudEstadoSeverity = (estado) => {
    switch (estado) {
        case 'activa':
            return 'success'
        case 'pendiente':
            return 'warning'
        case 'cancelada':
            return 'danger'
        default:
            return 'secondary'
    }
}

const getEstadoSolicitud = (estado) => {
    const estados = {
        'activa': 'Activa',
        'pendiente': 'Pendiente',
        'cancelada': 'Cancelada'
    }
    return estados[estado] || estado
}

const calculateBidsInAuction = (nombreSubasta) => {
    if (!products.value || !nombreSubasta) return 0
    return products.value.filter(product => 
        product.subasta?.nombre === nombreSubasta
    ).length
}

const calculateBidsInSolicitud = (codigoSolicitud) => {
    if (!products.value || !codigoSolicitud) return 0
    return products.value.filter(product => 
        product.solicitud?.codigo === codigoSolicitud
    ).length
}

const formatCurrency = (amount) => {
    if (!amount) return '0.00'
    return new Intl.NumberFormat('es-PE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount)
}

const formatDate = (dateString) => {
    if (!dateString) return ''
    return dateString
}

const onPageChange = (event) => {
    currentPage.value = event.page + 1
}

const onRowGroupExpand = (event) => {
    const message = isAuctionMode.value 
        ? `Viendo pujas de: ${event.data}` 
        : `Viendo pujas de solicitud: ${event.data}`
    
    toast.add({
        severity: 'info',
        summary: isAuctionMode.value ? 'Subasta Expandida' : 'Solicitud Expandida',
        detail: message,
        life: 2000
    })
}

const onRowGroupCollapse = (event) => {
    toast.add({
        severity: 'secondary',
        summary: isAuctionMode.value ? 'Subasta Colapsada' : 'Solicitud Colapsada',
        detail: `Ocultando: ${event.data}`,
        life: 2000
    })
}

// Watchers
onMounted(loadData)
watch(currentPage, loadData)
watch(rows, loadData)
watch(selectedType, () => {
    currentPage.value = 1
    loadData()
})
</script>