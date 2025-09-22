<template>
    <Dialog :visible="visible" @update:visible="$emit('update:visible', $event)" :style="{ width: '50rem' }" header="Detalles de la Empresa" :modal="true">
        <div v-if="company" class="flex flex-col gap-4">
            <!-- Información básica -->
            <div>
                <label class="block font-bold mb-2">RUC</label>
                <div class="p-3 font-mono">{{ company.document }}</div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold mb-2">Razón social</label>
                    <div class="p-3">{{ company.business_name }}</div>
                </div>
                <div>
                    <label class="block font-bold mb-2">Nombre comercial</label>
                    <div class="p-3">{{ company.name || 'N/A' }}</div>
                </div>
            </div>

            <div>
                <label class="block font-bold mb-2">Descripción</label>
                <div class="p-3">{{ company.description || 'N/A' }}</div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block font-bold mb-2">Riesgo</label>
                    <Tag :value="getRiskLabel(company.risk)" :severity="getRiskSeverity(company.risk)"
                        class="px-3 py-1 rounded-lg font-bold" />
                </div>
                <div>
                    <label class="block font-bold mb-2">Año constitución</label>
                    <div class="p-3 font-mono">{{ company.incorporation_year }}</div>
                </div>
                <div>
                    <label class="block font-bold mb-2">Sector</label>
                    <div class="p-3 text-blue-600 font-medium">{{ company.sectornom }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold mb-2">Subsector</label>
                    <div class="p-3">{{ company.subsectornom || 'N/A' }}</div>
                </div>
                <div>
                    <label class="block font-bold mb-2">Sitio Web</label>
                    <div class="p-3">
                        <template v-if="company.link_web_page">
                            <a 
                                :href="company.link_web_page" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                class="text-blue-600 underline hover:text-blue-800"
                            >
                                {{ company.link_web_page }}
                            </a>
                        </template>
                        <template v-else>
                            N/A
                        </template>
                    </div>
                </div>
            </div>

            <div>
                <label class="block font-bold mb-2">Moneda</label>
                <div class="p-3">{{ getMonedaLabel(company.moneda) }}</div>
            </div>

            <!-- Campos de Ventas según moneda -->
            <div v-if="company.moneda && company.moneda !== ''" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Ventas PEN -->
                <div v-if="company.moneda === 'PEN' || company.moneda === 'BOTH'">
                    <label class="block font-bold mb-2">Volumen de ventas PEN</label>
                    <div class="p-3 font-mono text-green-600">
                        {{ formatCurrency(company.sales_PEN, 'PEN') }}
                    </div>
                </div>

                <!-- Ventas USD -->
                <div v-if="company.moneda === 'USD' || company.moneda === 'BOTH'">
                    <label class="block font-bold mb-2">Volumen de ventas USD</label>
                    <div class="p-3 font-mono text-blue-600">
                        {{ formatCurrency(company.sales_USD, 'USD') }}
                    </div>
                </div>
            </div>

            <!-- Información Financiera -->
            <div class="border p-4 rounded bg-gray-50">
                <h4 class="font-bold mb-4">Información Financiera</h4>
                
                <!-- Datos en PEN - Mostrar si moneda es PEN o BOTH -->
                <div v-if="company.moneda === 'PEN' || company.moneda === 'BOTH'" class="mb-6">
                    <h5 class="font-semibold mb-3 text-green-700">Datos en PEN (Soles)</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-medium mb-1">Facturas Financiadas</label>
                            <div class="p-3 bg-white rounded font-mono">
                                {{ company.finances?.facturas_financiadas_pen !== null && company.finances?.facturas_financiadas_pen !== undefined ? formatNumber(company.finances.facturas_financiadas_pen) : 'N/A' }}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Monto Financiado</label>
                            <div class="p-3 bg-white rounded font-mono text-green-600">
                                {{ company.finances?.monto_total_financiado_pen !== null && company.finances?.monto_total_financiado_pen !== undefined ? formatCurrency(company.finances.monto_total_financiado_pen, 'PEN') : 'N/A' }}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Facturas Pagadas</label>
                            <div class="p-3 bg-white rounded font-mono">
                                {{ company.finances?.pagadas_pen !== null && company.finances?.pagadas_pen !== undefined ? formatNumber(company.finances.pagadas_pen) : 'N/A' }}
                            </div>
                        </div>
                        
                        <!-- <div>
                            <label class="block font-medium mb-1">Facturas Pendientes</label>
                            <div class="p-3 bg-white rounded font-mono">
                                {{ company.finances?.pendientes_pen !== null && company.finances?.pendientes_pen !== undefined ? formatNumber(company.finances.pendientes_pen) : 'N/A' }}
                            </div>
                        </div> -->
                        
                        <div>
                            <label class="block font-medium mb-1">Plazo Pago (días)</label>
                            <div class="p-3 bg-white rounded font-mono">
                                {{ company.finances?.plazo_promedio_pago_pen !== null && company.finances?.plazo_promedio_pago_pen !== undefined ? formatNumber(company.finances.plazo_promedio_pago_pen) : 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Datos en USD - Mostrar si moneda es USD o BOTH -->
                <div v-if="company.moneda === 'USD' || company.moneda === 'BOTH'">
                    <h5 class="font-semibold mb-3 text-blue-700">Datos en USD (Dólares)</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-medium mb-1">Facturas Financiadas</label>
                            <div class="p-3 bg-white rounded font-mono">
                                {{ company.finances?.facturas_financiadas_usd !== null && company.finances?.facturas_financiadas_usd !== undefined ? formatNumber(company.finances.facturas_financiadas_usd) : 'N/A' }}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Monto Financiado</label>
                            <div class="p-3 bg-white rounded font-mono text-blue-600">
                                {{ company.finances?.monto_total_financiado_usd !== null && company.finances?.monto_total_financiado_usd !== undefined ? formatCurrency(company.finances.monto_total_financiado_usd, 'USD') : 'N/A' }}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Facturas Pagadas</label>
                            <div class="p-3 bg-white rounded font-mono">
                                {{ company.finances?.pagadas_usd !== null && company.finances?.pagadas_usd !== undefined ? formatNumber(company.finances.pagadas_usd) : 'N/A' }}
                            </div>
                        </div>
                        
                        <!-- <div>
                            <label class="block font-medium mb-1">Facturas Pendientes</label>
                            <div class="p-3 bg-white rounded font-mono">
                                {{ company.finances?.pendientes_usd !== null && company.finances?.pendientes_usd !== undefined ? formatNumber(company.finances.pendientes_usd) : 'N/A' }}
                            </div>
                        </div> -->
                        
                        <div>
                            <label class="block font-medium mb-1">Plazo Pago (días)</label>
                            <div class="p-3 bg-white rounded font-mono">
                                {{ company.finances?.plazo_promedio_pago_usd !== null && company.finances?.plazo_promedio_pago_usd !== undefined ? formatNumber(company.finances.plazo_promedio_pago_usd) : 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <template #footer>
            <Button label="Cerrar" severity="secondary" text @click="closeDialog" />
        </template>
    </Dialog>
</template>

<script setup lang="ts">
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

interface Company {
    id: string;
    document: string;
    business_name: string;
    name?: string;
    risk: string;
    sectornom: string;
    subsectornom?: string;
    incorporation_year: string;
    link_web_page?: string;
    description?: string;
    moneda: string;
    sales_PEN?: string;
    sales_USD?: string;
    creacion: string;
    finances?: {
        facturas_financiadas_pen?: number;
        monto_total_financiado_pen?: string;
        pagadas_pen?: number;
        // pendientes_pen?: number;
        plazo_promedio_pago_pen?: number;
        facturas_financiadas_usd?: number;
        monto_total_financiado_usd?: string;
        pagadas_usd?: number;
        // pendientes_usd?: number;
        plazo_promedio_pago_usd?: number;
    };
}

interface Props {
    visible: boolean;
    company: Company | null;
}

interface Emits {
    (e: 'update:visible', value: boolean): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const closeDialog = () => {
    emit('update:visible', false);
};

const getRiskSeverity = (risk: string | number) => {
    const riskNum = parseInt(risk.toString());
    return ['success', 'info', 'warn', 'danger', 'secondary'][riskNum] || 'secondary';
};

const getRiskLabel = (risk: string | number) => {
    const labels = ['A', 'B', 'C', 'D', 'E'];
    return labels[parseInt(risk.toString())] || 'N/A';
};

const getMonedaLabel = (moneda: string) => {
    const monedaLabels: { [key: string]: string } = {
        'PEN': 'Soles (PEN)',
        'USD': 'Dólares (USD)', 
        'BOTH': 'Ambas monedas (PEN y USD)'
    };
    return monedaLabels[moneda] || moneda;
};

const formatCurrency = (amount: string | number | null, currency: string) => {
    if (amount === null || amount === undefined || amount === '') {
        return 'N/A';
    }
    
    const numericAmount = typeof amount === 'string' ? parseFloat(amount) : amount;
    
    if (isNaN(numericAmount)) {
        return 'N/A';
    }

    const options: Intl.NumberFormatOptions = {
        style: 'currency',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    };

    if (currency === 'PEN') {
        return new Intl.NumberFormat('es-PE', { ...options, currency: 'PEN' }).format(numericAmount);
    } else if (currency === 'USD') {
        return new Intl.NumberFormat('en-US', { ...options, currency: 'USD' }).format(numericAmount);
    }
    
    return numericAmount.toFixed(2);
};

const formatNumber = (number: number | null | undefined) => {
    if (number === null || number === undefined) {
        return 'N/A';
    }
    return new Intl.NumberFormat().format(number);
};
</script>