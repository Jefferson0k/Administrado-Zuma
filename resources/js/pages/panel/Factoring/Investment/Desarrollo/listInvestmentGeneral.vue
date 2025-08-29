<template>
    <DataTable ref="dt" :value="paginatedInvestments" dataKey="id" :paginator="true" :rows="rowsPerPage"
        :totalRecords="filteredInvestments.length" :first="(currentPage - 1) * rowsPerPage" :loading="loading"
        @page="onPage" :rowsPerPageOptions="[5, 10, 20]" scrollable scrollHeight="574px"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} inversiones" class="p-datatable-sm">
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">
                    Inversiones
                    <Tag severity="contrast" :value="contadorInvestments" />
                </h4>
                <div class="flex flex-wrap gap-2">
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="globalFilterValue" @input="onGlobalSearch" placeholder="Buscar..." />
                    </IconField>
                    <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" @click="loadInvestments" />
                </div>
            </div>
        </template>
        
        <Column selectionMode="multiple" style="width: 1rem" :exportable="false" />
        <Column field="codigo" header="Código" sortable style="min-width: 12rem" />
        <Column field="inversionista" header="Inversionista" sortable style="min-width: 18rem" />
        <Column field="amount" header="Monto" sortable style="min-width: 10rem">
            <template #body="slotProps">
                {{ slotProps.data.currency }} {{ slotProps.data.amount }}
            </template>
        </Column>
        <Column field="return" header="Retorno" sortable style="min-width: 10rem">
            <template #body="slotProps">
                {{ slotProps.data.currency }} {{ slotProps.data.return }}
            </template>
        </Column>
        <Column field="rate" header="Tasa" sortable style="min-width: 8rem">
            <template #body="slotProps">
                {{ slotProps.data.rate }}%
            </template>
        </Column>
        <Column field="currency" header="Moneda" sortable style="min-width: 8rem" />
        <Column field="due_date" header="Fecha Vencimiento" sortable style="min-width: 12rem" />
        
        <!-- Estado con Tag -->
        <Column field="status" header="Estado" sortable style="min-width: 10rem">
            <template #body="slotProps">
                <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" />
            </template>
        </Column>

        <Column field="creacion" header="Creación" sortable style="min-width: 15rem" />
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

const investments = ref<any[]>([]);
const loading = ref(false);
const contadorInvestments = ref(0);

const globalFilterValue = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);

const loadInvestments = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/investment/all');
        investments.value = response.data.data;
        contadorInvestments.value = response.data.total;
        currentPage.value = 1;
    } catch (error) {
        console.error('Error al cargar inversiones:', error);
    } finally {
        loading.value = false;
    }
};

const filteredInvestments = computed(() => {
    const search = globalFilterValue.value.toLowerCase();
    return investments.value.filter((inv: any) =>
        inv.codigo.toLowerCase().includes(search) ||
        inv.inversionista.toLowerCase().includes(search) ||
        inv.currency.toLowerCase().includes(search) ||
        inv.status.toLowerCase().includes(search)
    );
});

const paginatedInvestments = computed(() => {
    const start = (currentPage.value - 1) * rowsPerPage.value;
    return filteredInvestments.value.slice(start, start + rowsPerPage.value);
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
        case 'active':
            return 'success';
        case 'pending':
            return 'warn';
        case 'completed':
            return 'info';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
};

onMounted(() => {
    loadInvestments();
});
</script>