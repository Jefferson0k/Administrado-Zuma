<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import Menu from 'primevue/menu';
import UpdateStandby from './UpdateStandby.vue';
import updateActive from './updateActive.vue';
import deleteInvoice from './deleteInvoice.vue';

const props = defineProps({
    refresh: {
        type: Number,
        default: 0
    }
});

const dt = ref();
const facturas = ref([]); 
const selectedFacturas = ref();
const loading = ref(false);
const globalFilterValue = ref('');
const contadorFacturas = ref(0);
const menu = ref();
const menuItems = ref([]);

const rowsPerPage = ref(10);
const currentPage = ref(1);

const showStandbyDialog = ref(false);
const showActiveDialog = ref(false);
const selectedFacturaId = ref(null);

// Función para traducir estados al español
function getStatusLabel(status) {
    const statusLabels = {
        'inactive': 'Inactivo',
        'active': 'Activo',
        'expired': 'Vencido',
        'judicialized': 'Judicializado',
        'reprogramed': 'Reprogramado',
        'paid': 'Pagado',
        'canceled': 'Cancelado',
        'daStandby': 'En Espera'
    };
    return statusLabels[status] || status;
}

function editFactura(factura) {
    console.log('Editar factura:', factura);
}

function confirmDelete(factura) {
    console.log('Confirmar eliminar factura:', factura);
}

function verDetalles(factura) {
    // Navegar a la página de inversionistas usando el ID de la factura
    router.get(`/factoring/${factura.id}/inversionistas`);
}

function gestionarPago(factura) {
    console.log('Gestionar pago factura:', factura);
}

function ponerEnStandby(factura) {
    selectedFacturaId.value = factura.id;
    showStandbyDialog.value = true;
}

function ponerActivo(factura) {
    selectedFacturaId.value = factura.id;
    showActiveDialog.value = true;
}

function onStandbyConfirmed(response) {
    console.log('Factura puesta en standby:', response);
    refreshTable();
}

function onStandbyCancelled() {
    selectedFacturaId.value = null;
}

async function refreshTable() {
    loading.value = true;
    try {
        const response = await axios.get('/invoices');
        facturas.value = response.data?.data ?? [];
        contadorFacturas.value = response.data?.total ?? facturas.value.length;
    } catch (error) {
        console.error('Error al cargar facturas:', error);
    } finally {
        loading.value = false;
    }
}

function getStatusSeverity(status) {
    switch (status) {
        case 'inactive': return 'secondary';
        case 'active': return 'success';
        case 'expired': return 'danger';
        case 'judicialized': return 'warn';
        case 'reprogramed': return 'info';
        case 'paid': return 'contrast';
        case 'canceled': return 'danger';
        case 'daStandby': return 'warn';
        default: return 'secondary';
    }
}

const onPage = (event) => {
    currentPage.value = event.page + 1;
    rowsPerPage.value = event.rows;
};

const formatCurrency = (value, moneda) => {
    if (!value) return '';
    const number = parseFloat(value);
    let currencySymbol = '';
    if (moneda === 'PEN') currencySymbol = 'S/';
    if (moneda === 'USD') currencySymbol = 'US$';
    return `${currencySymbol} ${number.toLocaleString('es-PE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })}`;
};

const toggleMenu = (event, factura) => {
    let items = [];

    if (factura.estado?.toLowerCase().trim() === 'inactive') {
        items = [
            {
                label: 'Ver detalles',
                icon: 'pi pi-eye',
                command: () => verDetalles(factura)
            },
            {
                label: 'Activo',
                icon: 'pi pi-check-circle',
                command: () => ponerActivo(factura)
            },
            { separator: true },
            {
                label: 'Eliminar',
                icon: 'pi pi-trash',
                command: () => confirmDelete(factura),
                class: 'p-menuitem-link-danger'
            }
        ];
    } else if (factura.estado === 'active') {
        items = [
            {
                label: 'Ver detalles',
                icon: 'pi pi-eye',
                command: () => verDetalles(factura)
            },
            {
                label: 'Poner en standby',
                icon: 'pi pi-pause',
                command: () => ponerEnStandby(factura)
            }
        ];
    } else if (factura.estado === 'daStandby') {
        items = [
            {
                label: 'Ver detalles',
                icon: 'pi pi-eye',
                command: () => verDetalles(factura)
            },
            {
                label: 'Gestionar pago',
                icon: 'pi pi-wallet',
                command: () => gestionarPago(factura)
            }
        ];
    } else if (factura.estado === 'reprogramed') {
        // Para estado reprogramado: solo ver detalles, sin editar
        items = [
            {
                label: 'Ver detalles',
                icon: 'pi pi-eye',
                command: () => verDetalles(factura)
            }
        ];
    } else {
        // Para todos los otros estados: expired, judicialized, paid, canceled
        // Sin opción de editar
        items = [
            {
                label: 'Ver detalles',
                icon: 'pi pi-eye',
                command: () => verDetalles(factura)
            }
        ];
    }

    menuItems.value = items;
    menu.value.toggle(event);
};

onMounted(() => {
    refreshTable();
});
</script>

<template>
    <div>
        <DataTable ref="dt" v-model:selection="selectedFacturas" :value="facturas" dataKey="codigo" :paginator="true"
            :rows="rowsPerPage" :totalRecords="contadorFacturas" :first="(currentPage - 1) * rowsPerPage"
            :loading="loading" @page="onPage" :rowsPerPageOptions="[5, 10, 20]" scrollable scrollHeight="574px"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} facturas" class="p-datatable-sm">

            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <h4 class="m-0">
                        Facturas
                        <Tag severity="contrast" :value="contadorFacturas" />
                    </h4>
                    <div class="flex flex-wrap gap-2">
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="globalFilterValue" placeholder="Buscar..." />
                        </IconField>
                        <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" @click="refreshTable" />
                    </div>
                </div>
            </template>

            <template #empty>
                <div class="text-center p-4">
                    <i class="pi pi-inbox text-4xl text-gray-400 mb-4 block"></i>
                    <p class="text-gray-500">No hay facturas registradas</p>
                </div>
            </template>

            <Column selectionMode="multiple" style="width: 1rem" :exportable="false" />
            <Column field="razonSocial" header="Razón Social" sortable style="min-width: 20rem" />
            <Column field="codigo" header="Código" sortable style="min-width: 10rem" />
            <Column field="moneda" header="Moneda" sortable style="min-width: 8rem" />
            <Column field="montoFactura" header="M. Factura" sortable style="min-width: 12rem">
                <template #body="slotProps">
                    {{ formatCurrency(slotProps.data.montoFactura, slotProps.data.moneda) }}
                </template>
            </Column>
            <Column field="montoAsumidoZuma" header="M. asumido ZUMA" sortable style="min-width: 15rem">
                <template #body="slotProps">
                    {{ formatCurrency(slotProps.data.montoAsumidoZuma, slotProps.data.moneda) }}
                </template>
            </Column>
            <Column field="montoDisponible" header="Monto Disponible" sortable style="min-width: 12rem">
                <template #body="slotProps">
                    {{ formatCurrency(slotProps.data.montoDisponible, slotProps.data.moneda) }}
                </template>
            </Column>
            <Column field="tasa" header="Tasa (%)" sortable style="min-width: 8rem" />
            <Column field="fechaPago" header="Fecha de Pago" sortable style="min-width: 10rem" />
            <Column field="fechaCreacion" header="Fecha Creación" sortable style="min-width: 13rem" />
            <!-- Estado con colores y traducido al español -->
            <Column field="estado" header="Estado" sortable style="min-width: 8rem">
                <template #body="slotProps">
                    <Tag :value="getStatusLabel(slotProps.data.estado)" :severity="getStatusSeverity(slotProps.data.estado)" />
                </template>
            </Column>

            <Column header="" :exportable="false">
                <template #body="slotProps">
                    <Button 
                        icon="pi pi-ellipsis-v" 
                        text 
                        rounded 
                        severity="secondary" 
                        @click="toggleMenu($event, slotProps.data)"
                        aria-label="Opciones"
                    />
                </template>
            </Column>
        </DataTable>
        <Menu ref="menu" :model="menuItems" :popup="true" />
        <UpdateStandby 
            v-model="showStandbyDialog"
            :factura-id="selectedFacturaId"
            @confirmed="onStandbyConfirmed"
            @cancelled="onStandbyCancelled"
        />

        <updateActive 
            v-model="showActiveDialog"
            :factura-id="selectedFacturaId"
            @confirmed="onStandbyConfirmed"
            @cancelled="onStandbyCancelled"
        />
        <deleteInvoice
            v-model="showDeleteDialog"
            :factura-id="selectedFacturaId"
            :factura-data="selectedFacturaData"
            @confirmed="onDeleteConfirmed"
            @cancelled="onDeleteCancelled"
        />
    </div>
</template>