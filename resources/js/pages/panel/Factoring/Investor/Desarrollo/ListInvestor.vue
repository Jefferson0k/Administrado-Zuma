<template>
    <DataTable ref="dt" :value="paginatedInvestors" dataKey="id" :paginator="true" :rows="rowsPerPage"
        :totalRecords="filteredInvestors.length" :first="(currentPage - 1) * rowsPerPage" :loading="loading"
        @page="onPage" :rowsPerPageOptions="[5, 10, 20]" scrollable scrollHeight="574px"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} inversionistas" class="p-datatable-sm">
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">
                    Inversionistas
                    <Tag severity="contrast" :value="contadorInvestors" />
                </h4>
                <div class="flex flex-wrap gap-2">
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="globalFilterValue" @input="onGlobalSearch" placeholder="Buscar..." />
                    </IconField>
                    <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" @click="loadInvestors" />
                </div>
            </div>
        </template>

        <Column selectionMode="multiple" style="width: 1rem" :exportable="false" />
        <Column field="name" header="Nombre" sortable style="min-width: 15rem" />
        <Column field="document" header="Documento" sortable style="min-width: 10rem" />
        <Column field="alias" header="Alias" sortable style="min-width: 15rem" />
        <Column field="telephone" header="Teléfono" sortable style="min-width: 12rem" />
        <Column field="email" header="Email" sortable style="min-width: 18rem" />

        <!-- Estado con Tag -->
        <Column field="status" header="Estado" sortable style="min-width: 10rem">
            <template #body="slotProps">
                <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" />
            </template>
        </Column>

        <Column field="creacion" header="Creación" sortable style="min-width: 15rem" />

        <!-- Menú de 3 puntos -->
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
import Menu from 'primevue/menu';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import { debounce } from 'lodash';

const investors = ref<any[]>([]);
const loading = ref(false);
const contadorInvestors = ref(0);
const menu = ref();
const selectedMenuInvestor = ref<any>(null);

const globalFilterValue = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);

const loadInvestors = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/investor');
        investors.value = response.data.data;
        contadorInvestors.value = response.data.total;
        currentPage.value = 1;
    } catch (error) {
        console.error('Error al cargar inversionistas:', error);
    } finally {
        loading.value = false;
    }
};

const filteredInvestors = computed(() => {
    const search = globalFilterValue.value.toLowerCase();
    return investors.value.filter((inv: any) =>
        inv.name.toLowerCase().includes(search) ||
        inv.alias.toLowerCase().includes(search) ||
        inv.email.toLowerCase().includes(search) ||
        inv.status.toLowerCase().includes(search)
    );
});

const paginatedInvestors = computed(() => {
    const start = (currentPage.value - 1) * rowsPerPage.value;
    return filteredInvestors.value.slice(start, start + rowsPerPage.value);
});

const onGlobalSearch = debounce(() => {
    currentPage.value = 1;
}, 500);

const onPage = (event: any) => {
    currentPage.value = event.page + 1;
    rowsPerPage.value = event.rows;
};

const getStatusSeverity = (status: string) => {
    switch (status) {
        case 'Validado':
            return 'success';
        case 'No validado':
            return 'info';
        case 'Rechazado':
            return 'danger';
        default:
            return 'warn';
    }
};

// Menú de 3 puntos
const toggleMenu = (event: any, investor: any) => {
    selectedMenuInvestor.value = investor;
    menu.value.toggle(event);
};

const getMenuItems = (investor: any) => {
    return [
        {
            label: 'Validar',
            icon: 'pi pi-check',
            command: () => updateStatus(investor.id, 'Validado')
        },
        {
            label: 'Rechazar',
            icon: 'pi pi-times',
            command: () => updateStatus(investor.id, 'Rechazado')
        },
        {
            label: 'Volver a intentar',
            icon: 'pi pi-refresh',
            command: () => updateStatus(investor.id, 'No validado')
        }
    ];
};

const updateStatus = async (id: string, status: string) => {
    try {
        await axios.put(`/investor/${id}/status`, { status });
        loadInvestors();
    } catch (e) {
        console.error('Error actualizando estado:', e);
    }
};

onMounted(() => {
    loadInvestors();
});
</script>
