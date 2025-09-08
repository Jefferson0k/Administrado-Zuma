<template>
    <Toolbar class="mb-6">
        <template #start>
            <Button label="Nuevo" icon="pi pi-plus" severity="contrast" class="mr-2" @click="openNew" />
            <Button label="Eliminar" icon="pi pi-trash" severity="danger" variant="outlined"
                @click="confirmDeleteSelected" :disabled="!selectedExchanges || !selectedExchanges.length" />
        </template>

        <template #end>
            <Button label="Exportar" icon="pi pi-upload" severity="secondary" @click="exportCSV($event)" />
        </template>
    </Toolbar>

    <DataTable ref="dt" v-model:selection="selectedExchanges" :value="exchanges" dataKey="id" :paginator="true"
        :rows="10" :filters="filters"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25]"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} tipos de cambio" :loading="loading" >
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Gestionar Tipos de Cambio</h4>
                <IconField>
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                </IconField>
            </div>
        </template>

        <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
        <Column field="currency" header="Moneda" sortable style="min-width: 8rem">
            <template #body="slotProps">
                <Tag :value="slotProps.data.currency"
                    :severity="slotProps.data.currency === 'USD' ? 'info' : 'success'" />
            </template>
        </Column>
        <Column field="exchange_rate_sell" header="Tasa Venta" sortable style="min-width: 10rem">
            <template #body="slotProps">
                {{ formatCurrency(slotProps.data.exchange_rate_sell) }}
            </template>
        </Column>
        <Column field="exchange_rate_buy" header="Tasa Compra" sortable style="min-width: 10rem">
            <template #body="slotProps">
                {{ formatCurrency(slotProps.data.exchange_rate_buy) }}
            </template>
        </Column>
        <Column field="status" header="Estado" sortable style="min-width: 10rem">
            <template #body="slotProps">
                <Tag :value="slotProps.data.status === 'active' ? 'ACTIVO' : 'INACTIVO'"
                    :severity="getStatusSeverity(slotProps.data.status)" />
            </template>
        </Column>
        <Column field="creacion" header="Fecha Creación" sortable style="min-width: 12rem"></Column>
        <Column field="actualizacion" header="Última Actualización" sortable style="min-width: 12rem"></Column>
        <Column :exportable="false" style="min-width: 8rem">
            <template #body="slotProps">
                <Button type="button" icon="pi pi-ellipsis-v" variant="outlined" @click="toggle($event, slotProps.data)" aria-haspopup="true" aria-controls="overlay_menu" />
                <Menu ref="menu" id="overlay_menu" :model="getMenuItems(slotProps.data)" :popup="true" />
            </template>
        </Column>
    </DataTable>
    
    <!-- Dialog para crear/editar -->
    <Dialog v-model:visible="exchangeDialog" :style="{ width: '450px' }" header="Detalles del Tipo de Cambio"
        :modal="true">
        <div class="flex flex-col gap-6">
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-6">
                    <label for="exchange_rate_buy" class="block font-bold mb-3">Compra</label>
                    <InputNumber id="exchange_rate_buy" v-model="exchange.exchange_rate_buy" mode="decimal"
                        :minFractionDigits="2" :maxFractionDigits="4" fluid
                        :invalid="submitted && !exchange.exchange_rate_buy" />
                    <small v-if="submitted && !exchange.exchange_rate_buy" class="text-red-500">La tasa de compra es
                        requerida.</small>
                </div>
                <div class="col-span-6">
                    <label for="exchange_rate_sell" class="block font-bold mb-3">Venta</label>
                    <InputNumber id="exchange_rate_sell" v-model="exchange.exchange_rate_sell" mode="decimal"
                        :minFractionDigits="2" :maxFractionDigits="4" fluid
                        :invalid="submitted && !exchange.exchange_rate_sell" />
                    <small v-if="submitted && !exchange.exchange_rate_sell" class="text-red-500">La tasa de venta es
                        requerida.</small>
                </div>
            </div>
        </div>

        <template #footer>
            <Button label="Cancelar" severity="secondary" icon="pi pi-times" text @click="hideDialog" />
            <Button label="Guardar" icon="pi pi-check" @click="saveExchange" :loading="saving" />
        </template>
    </Dialog>

    <!-- Dialog de confirmación para eliminar un registro -->
    <Dialog v-model:visible="deleteExchangeDialog" :style="{ width: '450px' }" header="Confirmar" :modal="true">
        <div class="flex items-center gap-4">
            <i class="pi pi-exclamation-triangle !text-3xl" />
            <span v-if="exchange">
                ¿Está seguro de que desea eliminar el tipo de cambio <b>{{ exchange.currency }}</b>?
            </span>
        </div>
        <template #footer>
            <Button label="No" icon="pi pi-times" text @click="deleteExchangeDialog = false" severity="secondary"
                variant="text" />
            <Button label="Sí" icon="pi pi-check" @click="deleteExchange" severity="danger" :loading="deleting" />
        </template>
    </Dialog>

    <!-- Dialog de confirmación para eliminar múltiples registros -->
    <Dialog v-model:visible="deleteExchangesDialog" :style="{ width: '450px' }" header="Confirmar" :modal="true">
        <div class="flex items-center gap-4">
            <i class="pi pi-exclamation-triangle !text-3xl" />
            <span>¿Está seguro de que desea eliminar los tipos de cambio seleccionados?</span>
        </div>
        <template #footer>
            <Button label="No" icon="pi pi-times" text @click="deleteExchangesDialog = false" severity="secondary"
                variant="text" />
            <Button label="Sí" icon="pi pi-check" text @click="deleteSelectedExchanges" severity="danger"
                :loading="deleting" />
        </template>
    </Dialog>

    <!-- Dialog de confirmación para activar -->
    <Dialog v-model:visible="activateDialog" :style="{ width: '450px' }" header="Confirmar Activación" :modal="true">
        <div class="flex items-center gap-4">
            <i class="pi pi-check-circle !text-3xl text-green-500" />
            <span v-if="exchange">
                ¿Está seguro de que desea activar el tipo de cambio <b>{{ exchange.currency }}</b>?
            </span>
        </div>
        <template #footer>
            <Button label="No" icon="pi pi-times" text @click="activateDialog = false" severity="secondary"
                variant="text" />
            <Button label="Sí" icon="pi pi-check" @click="activateExchange" severity="success" :loading="activating" />
        </template>
    </Dialog>

    <!-- Dialog de confirmación para inactivar -->
    <Dialog v-model:visible="inactivateDialog" :style="{ width: '450px' }" header="Confirmar Inactivación" :modal="true">
        <div class="flex items-center gap-4">
            <i class="pi pi-times-circle !text-3xl text-orange-500" />
            <span v-if="exchange">
                ¿Está seguro de que desea inactivar el tipo de cambio <b>{{ exchange.currency }}</b>?
            </span>
        </div>
        <template #footer>
            <Button label="No" icon="pi pi-times" text @click="inactivateDialog = false" severity="secondary"
                variant="text" />
            <Button label="Sí" icon="pi pi-check" @click="inactivateExchange" severity="warn" :loading="inactivating" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Menu from 'primevue/menu';

const toast = useToast();
const dt = ref();
const menu = ref();
const exchanges = ref([]);
const exchangeDialog = ref(false);
const deleteExchangeDialog = ref(false);
const deleteExchangesDialog = ref(false);
const activateDialog = ref(false);
const inactivateDialog = ref(false);
const exchange = ref({});
const selectedExchanges = ref();
const filters = ref({
    'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
});
const submitted = ref(false);
const loading = ref(false);
const saving = ref(false);
const deleting = ref(false);
const activating = ref(false);
const inactivating = ref(false);

// Cargar datos al montar el componente
onMounted(() => {
    loadExchanges();
});

const loadExchanges = async () => {
    try {
        loading.value = true;
        const response = await axios.get('/exchange');
        exchanges.value = response.data.data || response.data;
    } catch (error) {
        console.error('Error al cargar tipos de cambio:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error al cargar los tipos de cambio',
            life: 3000
        });
    } finally {
        loading.value = false;
    }
};

const formatCurrency = (value) => {
    if (value) {
        return value.toLocaleString('es-PE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 4
        });
    }
    return '0.00';
};

const getStatusSeverity = (status) => {
    return status === 'active' ? 'success' : 'danger';
};

const toggle = (event, data) => {
    exchange.value = data;
    menu.value.toggle(event);
};

const getMenuItems = (data) => {
    const items = [];
    
    if (data.status === 'active') {
        // Solo mostrar inactivar cuando esté activo
        items.push({
            label: 'Inactivar',
            icon: 'pi pi-times-circle',
            command: () => confirmInactivateExchange(data)
        });
    } else {
        // Cuando esté inactivo, mostrar todas las opciones
        items.push(
            {
                label: 'Editar',
                icon: 'pi pi-pencil',
                command: () => editExchange(data)
            },
            {
                label: 'Activar',
                icon: 'pi pi-check-circle',
                command: () => confirmActivateExchange(data)
            },
            {
                label: 'Eliminar',
                icon: 'pi pi-trash',
                command: () => confirmDeleteExchange(data)
            }
        );
    }
    
    return items;
};

const openNew = () => {
    exchange.value = {};
    submitted.value = false;
    exchangeDialog.value = true;
};

const hideDialog = () => {
    exchangeDialog.value = false;
    submitted.value = false;
};

const saveExchange = async () => {
    submitted.value = true;

    if (exchange.value.exchange_rate_sell && exchange.value.exchange_rate_buy) {
        try {
            saving.value = true;

            // Solo enviamos las tasas de cambio
            const payload = {
                exchange_rate_sell: exchange.value.exchange_rate_sell,
                exchange_rate_buy: exchange.value.exchange_rate_buy
            };

            if (exchange.value.id) {
                // Actualizar
                const response = await axios.put(`/exchange/${exchange.value.id}`, payload);

                // Actualizar en la lista local
                const index = exchanges.value.findIndex(item => item.id === exchange.value.id);
                if (index !== -1) {
                    exchanges.value[index] = response.data.data;
                }

                toast.add({
                    severity: 'success',
                    summary: 'Éxito',
                    detail: response.data.message,
                    life: 3000
                });
            } else {
                // Crear nuevo
                const response = await axios.post('/exchange', payload);

                exchanges.value.unshift(response.data.data);

                toast.add({
                    severity: 'success',
                    summary: 'Éxito',
                    detail: response.data.message,
                    life: 3000
                });
            }

            exchangeDialog.value = false;
            exchange.value = {};
        } catch (error) {
            console.error('Error al guardar:', error);
            const errorMessage = error.response?.data?.message || 'Error al guardar el tipo de cambio';
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: errorMessage,
                life: 3000
            });
        } finally {
            saving.value = false;
        }
    }
};

const editExchange = (exchangeData) => {
    exchange.value = { ...exchangeData };
    exchangeDialog.value = true;
};

const confirmDeleteExchange = (exchangeData) => {
    exchange.value = exchangeData;
    deleteExchangeDialog.value = true;
};

const deleteExchange = async () => {
    try {
        deleting.value = true;
        const response = await axios.delete(`/exchange/${exchange.value.id}`);

        exchanges.value = exchanges.value.filter(val => val.id !== exchange.value.id);
        deleteExchangeDialog.value = false;
        exchange.value = {};

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data.message,
            life: 3000
        });
    } catch (error) {
        console.error('Error al eliminar:', error);
        const errorMessage = error.response?.data?.message || 'Error al eliminar el tipo de cambio';
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage,
            life: 3000
        });
    } finally {
        deleting.value = false;
    }
};

const confirmActivateExchange = (exchangeData) => {
    exchange.value = exchangeData;
    activateDialog.value = true;
};

const activateExchange = async () => {
    try {
        activating.value = true;
        const response = await axios.put(`/exchange/${exchange.value.id}/activacion`);

        // Actualizar en la lista local
        const index = exchanges.value.findIndex(item => item.id === exchange.value.id);
        if (index !== -1) {
            exchanges.value[index] = response.data.data;
        }

        activateDialog.value = false;
        exchange.value = {};

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data.message,
            life: 3000
        });
    } catch (error) {
        console.error('Error al activar:', error);
        const errorMessage = error.response?.data?.message || 'Error al activar el tipo de cambio';
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage,
            life: 3000
        });
    } finally {
        activating.value = false;
    }
};

const confirmInactivateExchange = (exchangeData) => {
    exchange.value = exchangeData;
    inactivateDialog.value = true;
};

const inactivateExchange = async () => {
    try {
        inactivating.value = true;
        // Usando POST según las rutas definidas
        const response = await axios.post(`/exchange/${exchange.value.id}/inactivo`);

        // Actualizar en la lista local
        const index = exchanges.value.findIndex(item => item.id === exchange.value.id);
        if (index !== -1) {
            exchanges.value[index] = response.data.data;
        }

        inactivateDialog.value = false;
        exchange.value = {};

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data.message,
            life: 3000
        });
    } catch (error) {
        console.error('Error al inactivar:', error);
        const errorMessage = error.response?.data?.message || 'Error al inactivar el tipo de cambio';
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage,
            life: 3000
        });
    } finally {
        inactivating.value = false;
    }
};

const exportCSV = () => {
    dt.value.exportCSV();
};

const confirmDeleteSelected = () => {
    deleteExchangesDialog.value = true;
};

const deleteSelectedExchanges = async () => {
    try {
        deleting.value = true;
        const deletePromises = selectedExchanges.value.map(exchangeItem =>
            axios.delete(`/exchange/${exchangeItem.id}`)
        );

        await Promise.all(deletePromises);

        exchanges.value = exchanges.value.filter(val => !selectedExchanges.value.includes(val));
        deleteExchangesDialog.value = false;
        selectedExchanges.value = null;

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Tipos de cambio eliminados correctamente',
            life: 3000
        });
    } catch (error) {
        console.error('Error al eliminar seleccionados:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error al eliminar los tipos de cambio seleccionados',
            life: 3000
        });
    } finally {
        deleting.value = false;
    }
};
</script>