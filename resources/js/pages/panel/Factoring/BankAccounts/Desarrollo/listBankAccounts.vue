<template>
    <DataTable ref="dt" v-model:selection="selectedAccounts" :value="paginatedAccounts" dataKey="id" :paginator="true"
        :rows="rowsPerPage" :totalRecords="filteredAccounts.length" :first="(currentPage - 1) * rowsPerPage"
        :loading="loading" @page="onPage" :rowsPerPageOptions="[5, 10, 20]" scrollable scrollHeight="574px"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} cuentas" class="p-datatable-sm">

        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">
                    Cuentas Bancarias
                    <Tag severity="contrast" :value="contadorAccounts" />
                </h4>
                <div class="flex flex-wrap gap-2">
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="globalFilterValue" @input="onGlobalSearch" placeholder="Buscar..." />
                    </IconField>
                    <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" @click="loadAccounts" />
                </div>
            </div>
        </template>

        <Column selectionMode="multiple" style="width: 1rem" :exportable="false" />
        <Column field="banco" header="Banco" sortable style="min-width: 15rem" />
        <Column field="type" header="Tipo" sortable style="min-width: 10rem" />
        <Column field="currency" header="Moneda" sortable style="min-width: 8rem" />
        <Column field="cc" header="Cuenta" sortable style="min-width: 12rem" />
        <Column field="cci" header="CCI" sortable style="min-width: 15rem" />
        <Column field="alias" header="Alias" sortable style="min-width: 12rem" />
        <Column field="inversionista" header="Inversionista" sortable style="min-width: 18rem" />

        <!-- Estado con Tag de colores -->
        <Column field="estado" header="Estado" sortable style="min-width: 10rem">
            <template #body="slotProps">
                <Tag
                    :value="slotProps.data.estado"
                    :severity="getStatusSeverity(slotProps.data.estado)"
                />
            </template>
        </Column>
        <Column field="creacion" header="Creación" sortable style="min-width: 15rem" />
        <Column field="update" header="Actualización" sortable style="min-width: 15rem" />
        <Column header="">
            <template #body="slotProps">
                <Button icon="pi pi-ellipsis-v" text rounded @click="toggleMenu($event, slotProps.data)" />
                <Menu :model="getMenuItems(slotProps.data)" :popup="true" ref="menu" />
            </template>
        </Column>

    </DataTable>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import { debounce } from 'lodash';
import Menu from 'primevue/menu';

const accounts = ref<any[]>([]);
const selectedAccounts = ref();
const loading = ref(false);
const contadorAccounts = ref(0);

const globalFilterValue = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);
const menu = ref();
const selectedAccount = ref<any>(null);

const loadAccounts = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/ban');
        accounts.value = response.data.data;
        contadorAccounts.value = response.data.total;
        currentPage.value = 1;
    } catch (error) {
        console.error('Error al cargar cuentas bancarias:', error);
    } finally {
        loading.value = false;
    }
};

const filteredAccounts = computed(() => {
    const search = globalFilterValue.value.toLowerCase();
    return accounts.value.filter((account: any) =>
        account.banco.toLowerCase().includes(search) ||
        account.alias.toLowerCase().includes(search) ||
        account.inversionista.toLowerCase().includes(search) ||
        account.estado.toLowerCase().includes(search)
    );
});

const paginatedAccounts = computed(() => {
    const start = (currentPage.value - 1) * rowsPerPage.value;
    return filteredAccounts.value.slice(start, start + rowsPerPage.value);
});

const onGlobalSearch = debounce(() => {
    currentPage.value = 1;
}, 500);

const onPage = (event: any) => {
    currentPage.value = event.page + 1;
    rowsPerPage.value = event.rows;
};

/**
 * Devuelve el color del Tag según el estado
 */
const getStatusSeverity = (estado: string) => {
    switch (estado) {
        case 'Válido':
            return 'success';   // verde
        case 'Inválido':
            return 'danger';    // rojo
        case 'Preaprobado':
            return 'warn';   // amarillo
        default:
            return 'secondary'; // gris
    }
};

const toggleMenu = (event: any, account: any) => {
    selectedAccount.value = account;
    menu.value.toggle(event);
};

const getMenuItems = (account: any) => {
    return [
        {
            label: 'Aceptar',
            icon: 'pi pi-check',
            command: () => updateStatus(account.id, 'valid')
        },
        {
            label: 'Rechazar',
            icon: 'pi pi-times',
            command: () => updateStatus(account.id, 'invalid')
        },
        {
            label: 'Volver a intentar',
            icon: 'pi pi-refresh',
            command: () => updateStatus(account.id, 'pre_approved')
        }
    ];
};

const updateStatus = async (id: string, status: string) => {
    try {
        await axios.put(`/ban/${id}/status`, { status });
        loadAccounts();
    } catch (e) {
        console.error('Error actualizando estado:', e);
    }
};

onMounted(() => {
    loadAccounts();
});
</script>
