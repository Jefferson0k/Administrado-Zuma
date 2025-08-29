<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import axios from 'axios';
import Tag from 'primevue/tag';
import { debounce } from 'lodash';
import Image from 'primevue/image';

const dt = ref();
const deposits = ref([]);
const selectedDeposits = ref();
const loading = ref(false);
const globalFilterValue = ref('');
const deleteDialog = ref(false);
const deposit = ref({});
const selectedDepositId = ref(null);
const updateDialog = ref(false);
const contadorDepositos = ref(0);

const rowsPerPage = ref(10);
const currentPage = ref(1);

const props = defineProps({
    refresh: {
        type: Number,
        required: true
    }
});

watch(() => props.refresh, () => {
    loadDeposits();
});

function editDeposit(deposit) {
    selectedDepositId.value = deposit.id;
    updateDialog.value = true;
}

function confirmDelete(depositData) {
    deposit.value = depositData;
    deleteDialog.value = true;
}

const loadDeposits = async () => {
    loading.value = true;
    try {
        const params = {
            search: globalFilterValue.value,
        };
        const response = await axios.get('/deposit', { params });
        deposits.value = response.data.data;
        contadorDepositos.value = response.data.total;
        currentPage.value = 1;
    } catch (error) {
        console.error('Error al cargar depósitos:', error);
    } finally {
        loading.value = false;
    }
};

const filteredDeposits = computed(() => {
    const search = globalFilterValue.value.toLowerCase();
    return deposits.value.filter((deposit) =>
        deposit.investor.toLowerCase().includes(search) ||
        deposit.nomBanco.toLowerCase().includes(search) ||
        deposit.nro_operation.toLowerCase().includes(search)
    );
});

const paginatedDeposits = computed(() => {
    const start = (currentPage.value - 1) * rowsPerPage.value;
    return filteredDeposits.value.slice(start, start + rowsPerPage.value);
});

const onGlobalSearch = debounce(() => {
    currentPage.value = 1;
}, 500);

const onPage = (event) => {
    currentPage.value = event.page + 1;
    rowsPerPage.value = event.rows;
};

const formatAmount = (amount) => {
    return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN'
    }).format(parseFloat(amount));
};

onMounted(() => {
    loadDeposits();
});
</script>

<template>
    <DataTable ref="dt" v-model:selection="selectedDeposits" :value="paginatedDeposits" dataKey="id" :paginator="true"
        :rows="rowsPerPage" :totalRecords="filteredDeposits.length" :first="(currentPage - 1) * rowsPerPage"
        :loading="loading" @page="onPage" :rowsPerPageOptions="[5, 10, 20]" scrollable scrollHeight="574px"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} depósitos" class="p-datatable-sm">

        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">
                    Depósitos
                    <Tag severity="contrast" :value="contadorDepositos" />
                </h4>
                <div class="flex flex-wrap gap-2">
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="globalFilterValue" @input="onGlobalSearch" placeholder="Buscar inversor, banco o operación..." />
                    </IconField>
                    <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" @click="loadDeposits" />
                </div>
            </div>
        </template>

        <Column selectionMode="multiple" style="width: 1rem" :exportable="false" />
        
        <Column field="investor" header="Inversor" sortable style="min-width: 15rem" />
        
        <Column field="nomBanco" header="Banco" sortable style="min-width: 12rem" />
        
        <Column field="nro_operation" header="Nº Operación" sortable style="min-width: 8rem" />
        
        <Column field="currency" header="Moneda" sortable style="min-width: 6rem">
            <template #body="slotProps">
                <Tag :value="slotProps.data.currency" :severity="slotProps.data.currency === 'PEN' ? 'success' : 'info'" />
            </template>
        </Column>
        
        <Column field="amount" header="Monto" sortable style="min-width: 8rem">
            <template #body="slotProps">
                <span class="font-semibold">{{ formatAmount(slotProps.data.amount) }}</span>
            </template>
        </Column>
        
        <Column field="creacion" header="Fecha Creación" sortable style="min-width: 12rem" />

        <Column header="Imagen" style="min-width: 8rem">
                <template #body="slotProps">
                <Image v-if="slotProps.data.foto" :src="slotProps.data.foto" class="rounded" alt="Foto del cliente"
                    preview width="50" style="width: 64px" />
                <span v-else>-</span>
            </template>
        </Column>

        <Column header="Acciones" style="min-width: 10rem">
            <template #body="slotProps">
                <Button icon="pi pi-pencil" outlined rounded class="mr-2" 
                    @click="editDeposit(slotProps.data)" v-tooltip="'Editar'" />
                <Button icon="pi pi-trash" outlined rounded severity="danger" 
                    @click="confirmDelete(slotProps.data)" v-tooltip="'Eliminar'" />
            </template>
        </Column>
    </DataTable>
</template>