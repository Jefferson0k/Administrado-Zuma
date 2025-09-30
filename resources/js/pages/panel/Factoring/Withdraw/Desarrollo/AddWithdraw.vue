<template>
    <Dialog v-model:visible="dialogVisible" :style="{ width: '40vw' }" :header="headerTitle" :modal="true" maximizable>


        <div class="flex flex-col h-full">
            <!-- Información del inversionista -->
            <div class="p-4 bg-blue-50 rounded-lg mb-4" v-if="investorInfo">
                <div class="flex items-center gap-3">
                    <i class="pi pi-user text-2xl text-blue-600" />
                    <div>
                        <h4 class="font-semibold text-blue-800 mb-1">{{ investorInfo.name }}</h4>
                        <p class="text-sm text-blue-600">{{ investorInfo.email }}</p>
                        <p class="text-xs text-blue-500">ID: {{ investorInfo.id }}</p>
                    </div>
                </div>
            </div>

            <!-- Loading state -->
            <div class="text-center py-8" v-if="loading">
                <i class="pi pi-spinner pi-spin text-3xl text-blue-500 mb-3" />
                <p>Cargando movimientos del inversionista...</p>
            </div>

            <!-- Tabs para Ingresos vs Egresos -->
            <div v-else-if="movements.length > 0" class="flex flex-col h-full">
                <TabView class="h-full">
                    <!-- Tab de Ingresos -->
                    <TabPanel header="Ingresos">
                        <div class="flex justify-between items-center mb-4">
                            <h5 class="m-0 text-green-700">
                                Ingresos Totales: {{ formatCurrency(totalIncome, 'PEN') }}
                            </h5>
                            <IconField>
                                <InputIcon>
                                    <i class="pi pi-search" />
                                </InputIcon>
                                <InputText v-model="filters.income" placeholder="Buscar ingresos..." />
                            </IconField>
                        </div>

                        <DataTable :value="filteredIncome" dataKey="id" :paginator="true" :rows="10" scrollable
                            scrollHeight="flex"
                            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                            :rowsPerPageOptions="[5, 10, 25]"
                            currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} ingresos"
                            class="p-datatable-sm">

                            <Column field="type" header="Tipo" sortable style="min-width: 5rem">
                                <template #body="slotProps">
                                    <Tag :value="incomeTypeLabel(slotProps.data)" severity="success" />
                                </template>
                            </Column>

                            <Column field="amount" header="Monto" sortable style="min-width: 12rem">
                                <template #body="slotProps">
                                    <span class="font-semibold text-lg text-green-600">
                                        + {{ formatCurrency(incomeDisplayAmount(slotProps.data),
                                            slotProps.data.currency) }}
                                    </span>
                                </template>
                            </Column>

                            <Column field="date" header="Fecha" sortable style="min-width: 10rem">
                                <template #body="slotProps">
                                    <div class="flex flex-col">
                                        <span>{{ formatDate(slotProps.data.date) }}</span>
                                        <span class="text-xs text-gray-500">{{
                                            formatTime(slotProps.data.related?.created_at) }}</span>
                                    </div>
                                </template>
                            </Column>

                            <Column header="N° Operación" style="min-width: 10rem">
                                <template #body="slotProps">
                                    <span v-if="slotProps.data.related?.nro_operation" class="font-mono">
                                        {{ slotProps.data.related.nro_operation }}
                                    </span>
                                    <span v-else class="text-gray-400 italic">N/A</span>
                                </template>
                            </Column>

                            <Column header="">
                                <template #body="slotProps">
                                    <!-- Solo mostrar botón para depósitos que tengan voucher -->
                                    <Button
                                        v-if="slotProps.data.type === 'deposit' && slotProps.data.related?.resource_path"
                                        icon="pi pi-eye" size="small" severity="info" outlined
                                        @click="viewVoucher(slotProps.data)" v-tooltip.top="'Ver voucher'" />
                                    <span v-else class="text-gray-400 text-xs">N/A</span>
                                </template>
                            </Column>
                        </DataTable>
                    </TabPanel>

                    <!-- Tab de Egresos -->
                    <TabPanel header="Egresos">
                        <div class="flex justify-between items-center mb-4">
                            <h5 class="m-0 text-red-700">
                                Egresos Totales: {{ formatCurrency(totalExpenses, 'PEN') }}
                            </h5>
                            <IconField>
                                <InputIcon>
                                    <i class="pi pi-search" />
                                </InputIcon>
                                <InputText v-model="filters.expenses" placeholder="Buscar egresos..." />
                            </IconField>
                        </div>

                        <DataTable :value="filteredExpenses" dataKey="id" :paginator="true" :rows="10" scrollable
                            scrollHeight="flex"
                            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                            :rowsPerPageOptions="[5, 10, 25]"
                            currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} egresos"
                            class="p-datatable-sm">

                            <Column field="type" header="Tipo" sortable style="min-width: 10rem">
                                <template #body="slotProps">
                                    <Tag :value="getTypeLabel(slotProps.data.type)" severity="danger" />
                                </template>
                            </Column>

                            <Column field="amount" header="Monto" sortable style="min-width: 12rem">
                                <template #body="slotProps">
                                    <span class="font-semibold text-lg text-red-600">
                                        - {{ formatCurrency(slotProps.data.amount, slotProps.data.currency) }}
                                    </span>
                                </template>
                            </Column>

                            <Column field="date" header="Fecha" sortable style="min-width: 10rem">
                                <template #body="slotProps">
                                    <div class="flex flex-col">
                                        <span>{{ formatDate(slotProps.data.date) }}</span>
                                        <span class="text-xs text-gray-500">{{
                                            formatTime(slotProps.data.related?.created_at) }}</span>
                                    </div>
                                </template>
                            </Column>

                            <Column header="N° Operación" style="min-width: 10rem">
                                <template #body="slotProps">
                                    <span v-if="slotProps.data.related?.nro_operation" class="font-mono">
                                        {{ slotProps.data.related.nro_operation }}
                                    </span>
                                    <span v-else class="text-gray-400 italic">N/A</span>
                                </template>
                            </Column>

                            <Column header="Propósito" style="min-width: 12rem">
                                <template #body="slotProps">
                                    <span v-if="slotProps.data.related?.purpouse" class="text-sm">
                                        {{ slotProps.data.related.purpouse }}
                                    </span>
                                    <span v-else class="text-gray-400 italic">N/A</span>
                                </template>
                            </Column>
                        </DataTable>
                    </TabPanel>

                    <!-- Tab de Resumen -->
                    <TabPanel header="Resumen">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <!-- Card Ingresos -->
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="flex items-center gap-3">
                                    <i class="pi pi-arrow-down-left text-2xl text-green-600"></i>
                                    <div>
                                        <p class="text-sm font-medium text-green-800">Total Ingresos</p>
                                        <p class="text-2xl font-bold text-green-600">
                                            {{ formatCurrency(totalIncome, 'PEN') }}
                                        </p>
                                        <p class="text-xs text-green-500">{{ incomeMovements.length }} movimientos</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Egresos -->
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <div class="flex items-center gap-3">
                                    <i class="pi pi-arrow-up-right text-2xl text-red-600"></i>
                                    <div>
                                        <p class="text-sm font-medium text-red-800">Total Egresos</p>
                                        <p class="text-2xl font-bold text-red-600">
                                            {{ formatCurrency(totalExpenses, 'PEN') }}
                                        </p>
                                        <p class="text-xs text-red-500">{{ expenseMovements.length }} movimientos</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Balance -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-center gap-3">
                                    <i class="pi pi-chart-line text-2xl text-blue-600"></i>
                                    <div>
                                        <p class="text-sm font-medium text-blue-800">Balance Neto</p>
                                        <p class="text-2xl font-bold"
                                            :class="netBalance >= 0 ? 'text-green-600' : 'text-red-600'">
                                            {{ formatCurrency(netBalance, 'PEN') }}
                                        </p>
                                        <p class="text-xs text-blue-500">Saldo actual</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfico simple de distribución -->
                        <div class="bg-white border rounded-lg p-4">
                            <h6 class="font-semibold mb-3">Distribución de Movimientos</h6>
                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-green-600">Ingresos</span>
                                        <span>{{ incomeMovements.length }} ({{ incomePercentage }}%)</span>
                                    </div>
                                    <ProgressBar :value="incomePercentage" class="h-2"
                                        style="background-color: #e2e8f0">
                                        <template #value>
                                            <div class="h-full bg-green-500 rounded"></div>
                                        </template>
                                    </ProgressBar>
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-red-600">Egresos</span>
                                        <span>{{ expenseMovements.length }} ({{ expensePercentage }}%)</span>
                                    </div>
                                    <ProgressBar :value="expensePercentage" class="h-2"
                                        style="background-color: #e2e8f0">
                                        <template #value>
                                            <div class="h-full bg-red-500 rounded"></div>
                                        </template>
                                    </ProgressBar>
                                </div>
                            </div>
                        </div>
                    </TabPanel>
                </TabView>
            </div>

            <!-- Empty state -->
            <div class="text-center py-8" v-else-if="!loading">
                <i class="pi pi-inbox text-4xl text-gray-400 mb-3" />
                <p class="text-gray-600">No se encontraron movimientos para este inversionista</p>
            </div>
        </div>

        <template #footer>
            <Button label="Cerrar" severity="secondary" text icon="pi pi-times" @click="closeDialog" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import ProgressBar from 'primevue/progressbar';
import axios from 'axios';


// Props
const props = defineProps({
    visible: { type: Boolean, default: false },
    investorId: { type: String, default: '' },
    currentWithdraw: { type: Object, default: null }   // 👈 NEW
});

// header computed
const headerTitle = computed(() => {
    const name = investorInfo?.value?.name || props.investorId;
    const amt = props.currentWithdraw
        ? formatCurrency(props.currentWithdraw.amount, props.currentWithdraw.currency)
        : null;
    return amt
        ? `Movimientos del Inversionista: ${name} — Retiro actual: ${amt}`
        : `Movimientos del Inversionista: ${name}`;
});

// Emits
const emit = defineEmits(['close']);

// Composables
const toast = useToast();

// Computed
const dialogVisible = computed({
    get: () => props.visible,
    set: (value) => {
        if (!value) emit('close');
    }
});

// Reactive data
const loading = ref(false);
const movements = ref([]);
const investorInfo = ref(null);

// Filtros simplificados - solo strings
const filters = ref({
    income: '',
    expenses: ''
});

// Computed properties para separar y calcular movimientos
// Computed properties para separar y calcular movimientos
const incomeMovements = computed(() => {
    return movements.value.filter(m => {
        if (m.type === 'deposit') {
            const sc = String(m.related?.status_conclusion ?? '').toLowerCase();
            return sc === 'approved' || sc === 'aprobado';
        }
        if (m.type === 'investment') {
            const st = String((m.related?.status ?? m.status) || '').toLowerCase();
            return st === 'paid' || st === 'pagado';
        }
        return false;
    });
});

const expenseMovements = computed(() => {
    return movements.value.filter(m => {
        if (m.type === 'withdraw') {
            const st = String((m.related?.status ?? m.status) || '').toLowerCase();
            return st === 'approved' || st === 'aprobado' || st === 'aprobado';
        }
        if (m.type === 'investment') {
            const st = String((m.related?.status ?? m.status) || '').toLowerCase();
            // cualquier inversión que NO esté pagada se considera egreso
            return !(st === 'paid' || st === 'pagado');
        }
        return false;
    });
});



// Filtrado manual para evitar el problema de DataTable filters
const filteredIncome = computed(() => {
    if (!filters.value.income) return incomeMovements.value;

    const searchTerm = filters.value.income.toLowerCase();
    return incomeMovements.value.filter(movement =>
        getTypeLabel(movement.type).toLowerCase().includes(searchTerm) ||
        movement.amount.toString().includes(searchTerm) ||
        movement.date.toLowerCase().includes(searchTerm) ||
        (movement.related?.nro_operation && movement.related.nro_operation.toLowerCase().includes(searchTerm))
    );
});

const filteredExpenses = computed(() => {
    if (!filters.value.expenses) return expenseMovements.value;

    const searchTerm = filters.value.expenses.toLowerCase();
    return expenseMovements.value.filter(movement =>
        getTypeLabel(movement.type).toLowerCase().includes(searchTerm) ||
        movement.amount.toString().includes(searchTerm) ||
        movement.date.toLowerCase().includes(searchTerm) ||
        (movement.related?.nro_operation && movement.related.nro_operation.toLowerCase().includes(searchTerm)) ||
        (movement.related?.purpouse && movement.related.purpouse.toLowerCase().includes(searchTerm))
    );
});



const isPaidInvestment = (m) => {
    const st = String((m.related?.status ?? m.status) || '').toLowerCase();
    return m.type === 'investment' && (st === 'paid' || st === 'pagado');
};

const incomeTypeLabel = (m) => {
    return isPaidInvestment(m) ? 'Retorno' : getTypeLabel(m.type);
};


const incomeDisplayAmount = (m) => {
    // when investment is PAID, show its return; otherwise show amount
    if (isPaidInvestment(m)) {
        // use related.return if present; fallback to m.return or 0
        return parseFloat(m.related?.return ?? m.return ?? 0);
    }
    return parseFloat(m.amount ?? 0);
};


const totalIncome = computed(() => {
    return incomeMovements.value.reduce((total, m) => {
        return total + incomeDisplayAmount(m);
    }, 0);
});


const totalExpenses = computed(() => {
    return expenseMovements.value.reduce((total, movement) => {
        return total + parseFloat(movement.amount);
    }, 0);
});

const netBalance = computed(() => {
    return totalIncome.value - totalExpenses.value;
});

const incomePercentage = computed(() => {
    const total = movements.value.length;
    return total > 0 ? Math.round((incomeMovements.value.length / total) * 100) : 0;
});

const expensePercentage = computed(() => {
    const total = movements.value.length;
    return total > 0 ? Math.round((expenseMovements.value.length / total) * 100) : 0;
});

// Watchers
watch(() => props.visible, (newValue) => {
    if (newValue && props.investorId) {
        loadInvestorMovements();
    }
});

watch(() => props.investorId, (newValue) => {
    if (newValue && props.visible) {
        loadInvestorMovements();
    }
});

// Methods
const loadInvestorMovements = async () => {
    if (!props.investorId) return;

    loading.value = true;

    try {
        console.log('Cargando movimientos para investor_id:', props.investorId);

        const response = await axios.get(`/withdraws/${props.investorId}`);
        const data = response.data;

        movements.value = data.data || [];
        investorInfo.value = data.investor || null;

        console.log('Movimientos cargados:', movements.value.length);
        console.log('Ingresos:', incomeMovements.value.length);
        console.log('Egresos:', expenseMovements.value.length);

    } catch (error) {
        console.error('Error al cargar movimientos del inversionista:', error);

        let errorMessage = 'Error al cargar los movimientos del inversionista';

        if (error.response) {
            errorMessage = error.response.data?.message || `Error ${error.response.status}`;
        } else if (error.request) {
            errorMessage = 'Error de conexión con el servidor';
        }

        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage,
            life: 5000
        });

        movements.value = [];
        investorInfo.value = null;
    } finally {
        loading.value = false;
    }
};

const closeDialog = () => {
    emit('close');
    movements.value = [];
    investorInfo.value = null;
    // Resetear filtros
    filters.value.income = '';
    filters.value.expenses = '';
};

const viewVoucher = (deposit) => {
    if (deposit.related?.resource_path) {
        // Abrir el voucher en una nueva pestaña
        window.open(deposit.related.resource_path, '_blank');
        toast.add({
            severity: 'info',
            summary: 'Voucher',
            detail: 'Abriendo comprobante de depósito',
            life: 3000
        });
    } else {
        toast.add({
            severity: 'warn',
            summary: 'Voucher no disponible',
            detail: 'No hay comprobante disponible para este depósito',
            life: 3000
        });
    }
};

// Formatters
const getTypeLabel = (type) => {
    const typeMap = {
        'withdraw': 'Retiro',
        'investment': 'Inversión',
        'deposit': 'Depósito'
    };
    return typeMap[type] || type;
};

const formatCurrency = (value, currency = 'PEN') => {
    if (!value) return '';
    const numValue = parseFloat(value);
    const currencyMap = {
        'PEN': { locale: 'es-PE', currency: 'PEN' },
        'USD': { locale: 'en-US', currency: 'USD' },
        'EUR': { locale: 'de-DE', currency: 'EUR' }
    };
    const config = currencyMap[currency] || currencyMap['PEN'];
    return numValue.toLocaleString(config.locale, {
        style: 'currency',
        currency: config.currency
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
};

const formatTime = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleTimeString('es-PE', {
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>