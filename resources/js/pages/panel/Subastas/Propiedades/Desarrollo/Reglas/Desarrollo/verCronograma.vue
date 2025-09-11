<template>
    <Dialog
        v-model:visible="visible"
        modal
        header="Cronograma de Pagos"
        :style="{ width: '95vw', maxWidth: '1200px' }"
        :closable="true"
        :dismissableMask="true"
    >
        <!-- Header Info Card -->
        <div v-if="propiedadData" class="mb-6 rounded-lg border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="text-center md:text-left">
                    <h3 class="text-lg font-bold text-gray-800">{{ propiedadData.nombre }}</h3>
                    <p class="text-sm text-gray-600">{{ propiedadData.departamento }}, {{ propiedadData.provincia }}</p>
                </div>
                <div class="text-center">
                    <span class="block text-sm text-gray-600">Valor Requerido</span>
                    <span class="text-2xl font-bold text-blue-600">
                        {{ formatCurrency(parametros.valor_requerido, propiedadData.currency_symbol) }}
                    </span>
                </div>
                <div class="text-center md:text-right">
                    <Tag :value="propiedadData.currency" :severity="propiedadData.currency_id === 1 ? 'success' : 'info'" class="mb-2" />
                    <p class="text-sm text-gray-600">
                        TEM: <strong>{{ parametros.tem }}%</strong> | TEA: <strong>{{ parametros.tea }}%</strong>
                    </p>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex flex-col items-center justify-center py-12">
            <div class="mb-4 h-12 w-12 animate-spin rounded-full border-b-2 border-blue-500"></div>
            <span class="text-gray-600">Generando cronograma...</span>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="py-12 text-center">
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-6">
                <i class="pi pi-exclamation-triangle mb-4 text-4xl text-red-500"></i>
                <p class="mb-4 text-lg text-red-600">{{ error }}</p>
                <Button label="Reintentar" icon="pi pi-refresh" severity="danger" outlined @click="generarCronograma" />
            </div>
        </div>

        <!-- Cronograma Content -->
        <div v-else-if="cronograma" class="space-y-6">
            <!-- Summary Cards -->
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-center">
                    <i class="pi pi-calendar mb-2 text-2xl text-green-600"></i>
                    <p class="text-sm text-gray-600">Plazo Total</p>
                    <p class="text-lg font-bold text-green-700">{{ cronograma.cronograma_final.plazo_total }} meses</p>
                </div>

                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-center">
                    <i class="pi pi-money-bill mb-2 text-2xl text-blue-600"></i>
                    <p class="text-sm text-gray-600">Capital Otorgado</p>
                    <p class="text-lg font-bold text-blue-700">{{ cronograma.cronograma_final.capital_otorgado }}</p>
                </div>

                <div class="rounded-lg border border-orange-200 bg-orange-50 p-4 text-center">
                    <i class="pi pi-percentage mb-2 text-2xl text-orange-600"></i>
                    <p class="text-sm text-gray-600">TEA</p>
                    <p class="text-lg font-bold text-orange-700">{{ cronograma.cronograma_final.tea_compensatoria }}</p>
                </div>

                <div class="rounded-lg border border-purple-200 bg-purple-50 p-4 text-center">
                    <i class="pi pi-chart-line mb-2 text-2xl text-purple-600"></i>
                    <p class="text-sm text-gray-600">TEM</p>
                    <p class="text-lg font-bold text-purple-700">{{ cronograma.cronograma_final.tem_compensatoria }}</p>
                </div>
            </div>

            <!-- Data Table -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-3">
                    <h4 class="flex items-center text-lg font-semibold text-gray-800">
                        <i class="pi pi-table mr-2 text-blue-600"></i>
                        Cronograma de Pagos - {{ cronograma.cronograma_final.moneda }}
                    </h4>
                </div>

                <div class="overflow-x-auto">
                    <DataTable
                        :value="cronograma.cronograma_final.pagos"
                        :paginator="true"
                        :rows="15"
                        class="w-full"
                        stripedRows
                        :rowsPerPageOptions="[10, 15, 25, 50]"
                        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} cuotas"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                    >
                        <Column field="cuota" header="Nº" :sortable="true" class="w-16 text-center">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.cuota" severity="info" class="font-bold" />
                            </template>
                        </Column>

                        <Column field="vcmto" header="Fecha Vcmto." :sortable="true" class="text-center">
                            <template #body="slotProps">
                                <div>
                                    <i class="pi pi-calendar mr-2 text-gray-500"></i>
                                    <span>{{ slotProps.data.vcmto }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="saldo_inicial" header="Saldo Inicial" :sortable="true" class="text-right">
                            <template #body="slotProps">
                                <span class="font-medium text-gray-700">
                                    {{ formatCurrency(slotProps.data.saldo_inicial, currencySymbol) }}
                                </span>
                            </template>
                        </Column>

                        <Column field="cuota_neta" header="Cuota Total" :sortable="true" class="text-right">
                            <template #body="slotProps">
                                <div class="rounded-md bg-blue-100 px-3 py-2">
                                    <span class="text-lg font-bold text-blue-700">
                                        {{ formatCurrency(slotProps.data.cuota_neta, currencySymbol) }}
                                    </span>
                                </div>
                            </template>
                        </Column>

                        <Column field="interes" header="Interés" :sortable="true" class="text-right">
                            <template #body="slotProps">
                                <span class="rounded bg-red-50 px-2 py-1 font-semibold text-red-600">
                                    {{ formatCurrency(slotProps.data.interes, currencySymbol) }}
                                </span>
                            </template>
                        </Column>

                        <Column field="capital" header="Capital" :sortable="true" class="text-right">
                            <template #body="slotProps">
                                <span class="rounded bg-green-50 px-2 py-1 font-semibold text-green-600">
                                    {{ formatCurrency(slotProps.data.capital, currencySymbol) }}
                                </span>
                            </template>
                        </Column>

                        <Column field="saldo_final" header="Saldo Final" :sortable="true" class="text-right">
                            <template #body="slotProps">
                                <span class="font-medium text-gray-700">
                                    {{ formatCurrency(slotProps.data.saldo_final, currencySymbol) }}
                                </span>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>

            <!-- Totales Summary -->
            <div class="rounded-lg border border-gray-200 bg-gradient-to-r from-gray-50 to-blue-50 p-6">
                <div class="grid grid-cols-1 gap-4 text-center md:grid-cols-3">
                    <div>
                        <p class="mb-1 text-sm text-gray-600">Total Capital</p>
                        <p class="text-xl font-bold text-green-600">
                            {{ formatCurrency(totalCapital, currencySymbol) }}
                        </p>
                    </div>
                    <div>
                        <p class="mb-1 text-sm text-gray-600">Total Intereses</p>
                        <p class="text-xl font-bold text-red-600">
                            {{ formatCurrency(totalIntereses, currencySymbol) }}
                        </p>
                    </div>
                    <div>
                        <p class="mb-1 text-sm text-gray-600">Total a Pagar</p>
                        <p class="text-xl font-bold text-blue-600">
                            {{ formatCurrency(totalPagar, currencySymbol) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex items-center justify-between">
                <div v-if="cronograma" class="text-sm text-gray-500">Cronograma generado: {{ new Date().toLocaleDateString('es-ES') }}</div>
                <div class="flex gap-3">
                    <Button label="Cerrar" icon="pi pi-times" severity="secondary" @click="cerrarDialog" />
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup lang="ts">
import axios from 'axios';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { computed, ref, watch } from 'vue';

interface Props {
    visible: boolean;
    propiedadData: any;
    parametros: {
        tea: number;
        tem: number;
        cronograma: string;
        duracion_meses: number;
        valor_requerido: number;
        currency_id?: number;
    };
}

const props = defineProps<Props>();
const emit = defineEmits(['update:visible']);

const toast = useToast();

const loading = ref(false);
const error = ref('');
const cronograma = ref(null);

const visible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value),
});

// Computed para obtener el símbolo de moneda
const currencySymbol = computed(() => {
    if (props.propiedadData?.currency_symbol) {
        return props.propiedadData.currency_symbol;
    }
    // Fallback basado en currency_id
    return props.parametros.currency_id === 1 ? 'S/' : '$';
});

// Computed para totales
const totalCapital = computed(() => {
    if (!cronograma.value?.cronograma_final?.pagos) return 0;
    return cronograma.value.cronograma_final.pagos.reduce((sum, pago) => sum + parseFloat(pago.capital || 0), 0);
});

const totalIntereses = computed(() => {
    if (!cronograma.value?.cronograma_final?.pagos) return 0;
    return cronograma.value.cronograma_final.pagos.reduce((sum, pago) => sum + parseFloat(pago.interes || 0), 0);
});

const totalPagar = computed(() => {
    return totalCapital.value + totalIntereses.value;
});

watch(
    () => props.visible,
    (newVal) => {
        if (newVal && props.propiedadData && props.parametros) {
            generarCronograma();
        }
    },
);

const generarCronograma = async () => {
    if (!props.propiedadData || !props.parametros) {
        error.value = 'Faltan datos para generar el cronograma';
        return;
    }

    loading.value = true;
    error.value = '';
    cronograma.value = null;

    try {
        // Usar tus endpoints existentes
        const endpoint = props.parametros.cronograma === 'frances' ? '/simulacion/preview-frances' : '/simulacion/preview-americano';

        // Obtener currency_id de la propiedad
        const currencyId = props.propiedadData.currency_id || 1;

        const payload = {
            valor_requerido: props.parametros.valor_requerido,
            tem: props.parametros.tem,
            tea: props.parametros.tea,
            plazo: props.parametros.duracion_meses,
            moneda_id: currencyId, // ← Usar la moneda de la propiedad
        };

        console.log('Payload enviado:', payload);

        const response = await axios.post(endpoint, payload);
        cronograma.value = response.data;

        toast.add({
            severity: 'success',
            summary: 'Cronograma generado',
            detail: `Cronograma ${props.parametros.cronograma} generado exitosamente`,
            life: 3000,
        });
    } catch (err) {
        console.error('Error al generar cronograma:', err);
        error.value = err.response?.data?.message || 'Error al generar el cronograma';
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.value,
            life: 4000,
        });
    } finally {
        loading.value = false;
    }
};

const formatCurrency = (value: string | number, symbol: string = 'S/') => {
    if (!value) return `${symbol} 0.00`;

    const numValue = typeof value === 'string' ? parseFloat(value) : value;
    if (isNaN(numValue)) return `${symbol} 0.00`;

    return `${symbol} ${numValue.toLocaleString('es-PE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
};

const cerrarDialog = () => {
    visible.value = false;
    cronograma.value = null;
    error.value = '';
    loading.value = false;
};
</script>
