<script setup>
import { onMounted, ref } from 'vue';
import axios from 'axios';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Menu from 'primevue/menu';
import Button from 'primevue/button';
import ConfirmDialog from 'primevue/confirmdialog';

const toast = useToast();
const confirm = useConfirm();

const dt = ref();
const products = ref([]);
const totalRecords = ref(0);
const loading = ref(false);
const currentPage = ref(1);
const selectedProducts = ref();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

const selectedRow = ref(null);
const menu = ref();

const openMenu = (event, row) => {
    selectedRow.value = row;
    menu.value.toggle(event);
};

const aceptar = async () => {
    if (!selectedRow.value) {
        toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'No hay fila seleccionada.', life: 3000 });
        return;
    }

    confirm.require({
        message: '¿Estás seguro que deseas aceptar este depósito?',
        header: 'Confirmar',
        icon: 'pi pi-exclamation-triangle',
        accept: async () => {
            try {
                const id = selectedRow.value.id;
                await axios.post(`/movements/${id}/aceptar-hipotecas`);
                toast.add({ severity: 'success', summary: 'Éxito', detail: 'Depósito aceptado correctamente.', life: 3000 });

                // Recargar tabla
                loadData(currentPage.value);
            } catch (error) {
                console.error(error);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: error.response?.data?.message || 'No se pudo aceptar el depósito.',
                    life: 3000,
                });
            }
        }
    });
};

const rechazar = async () => {
    if (!selectedRow.value) {
        toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'No hay fila seleccionada.', life: 3000 });
        return;
    }

    confirm.require({
        message: '¿Estás seguro que deseas rechazar este depósito?',
        header: 'Confirmar rechazo',
        icon: 'pi pi-exclamation-triangle',
        accept: async () => {
            try {
                const id = selectedRow.value.id;
                await axios.post(`/movements/${id}/rechazar-hipotecas`);
                toast.add({ severity: 'success', summary: 'Rechazado', detail: 'Depósito rechazado correctamente.', life: 3000 });

                // Recargar la tabla
                loadData(currentPage.value);
            } catch (error) {
                console.error(error);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: error.response?.data?.message || 'No se pudo rechazar el depósito.',
                    life: 3000,
                });
            }
        }
    });
};

const menuItems = ref([
    {
        label: 'Aceptar',
        icon: 'pi pi-check',
        command: () => aceptar()
    },
    {
        label: 'Rechazar',
        icon: 'pi pi-times',
        command: () => rechazar()
    }
]);

const loadData = async (page = 1) => {
    loading.value = true;
    try {
        const response = await axios.get(`/movements/hipotecas?page=${page}`);
        const res = response.data;

        products.value = res.data;
        totalRecords.value = res.meta.total;
        currentPage.value = res.meta.current_page;
    } catch (err) {
        console.error(err);
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar los datos', life: 3000 });
    } finally {
        loading.value = false;
    }
};

const onPage = (event) => {
    const newPage = event.page + 1;
    loadData(newPage);
};

onMounted(() => {
    loadData();
});
</script>

<template>
    <DataTable ref="dt" v-model:selection="selectedProducts" :value="products" dataKey="id" :paginator="true" :rows="10"
        :totalRecords="totalRecords" :lazy="true" :loading="loading" :filters="filters" @page="onPage"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25]"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} depósitos" class="p-datatable-sm">

        <!-- Header con buscador -->
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Credito - Inversionitas</h4>
                <IconField>
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                </IconField>
            </div>
        </template>

        <Column selectionMode="multiple" style="width: 3rem" :exportable="false" />
        <Column field="nomInvestor" header="Inversionista" style="min-width: 16rem" />
        <Column field="codInvestor" header="DNI" style="min-width: 10rem" />
        <Column field="deposit.nro_operation" header="N° Operación" style="min-width: 14rem" />
        <Column field="currency" header="Moneda" style="min-width: 8rem" />
        <Column field="amount" header="Monto" style="min-width: 10rem" />
        <Column field="status" header="Estado" style="min-width: 10rem" />
        <Column field="confirm_status" header="Confirmación" style="min-width: 12rem" />
        <Column field="deposit.payment_source" header="Medio de pago" style="min-width: 12rem" />
        <Column field="deposit.resource_path" header="Voucher" style="min-width: 15rem">
            <template #body="slotProps">
                <a v-if="slotProps.data.deposit?.resource_path && slotProps.data.deposit.resource_path !== '0'"
                    :href="`${slotProps.data.deposit.resource_path}`" target="_blank"
                    class="text-blue-600 underline">
                    Ver voucher
                </a>
                <span v-else class="text-gray-400">Sin voucher</span>
            </template>
        </Column>
        <Column field="deposit.created_at" header="Fecha de registro" style="min-width: 15rem" />

        <!-- Columna con botón de acciones (3 puntos) -->
        <Column style="min-width: 6rem" header="">
            <template #body="slotProps">
                <Button v-if="slotProps.data.status === 'Pendiente' && slotProps.data.confirm_status === 'Pendiente'"
                    icon="pi pi-ellipsis-v" text rounded @click="(event) => openMenu(event, slotProps.data)" />
            </template>
        </Column>

    </DataTable>

    <!-- Menú contextual -->
    <Menu ref="menu" :popup="true" :model="menuItems" />

    <!-- Diálogo de confirmación -->
    <ConfirmDialog />
</template>
