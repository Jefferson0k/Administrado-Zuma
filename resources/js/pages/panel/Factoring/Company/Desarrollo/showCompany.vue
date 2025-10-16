<template>
    <Dialog :visible="visible" @update:visible="$emit('update:visible', $event)" :style="{ width: '800px' }" header="Detalles de la Empresa" :modal="true">
        <div v-if="company" class="flex flex-col gap-4">
            <!-- RUC -->
            <div>
                <label class="block font-bold mb-2">RUC</label>
                <div class="p-3 bg-gray-50 rounded font-mono">{{ company.document }}</div>
            </div>
            
            <!-- Razón Social y Nombre comercial en grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold mb-2">Razón social</label>
                    <div class="p-3 bg-gray-50 rounded">{{ company.business_name }}</div>
                </div>
                <div>
                    <label class="block font-bold mb-2">Nombre comercial</label>
                    <div class="p-3 bg-gray-50 rounded">{{ company.name || 'N/A' }}</div>
                </div>
            </div>

            <!-- Nuevo nombre de empresa (si existe) -->
            <div v-if="company.nuevonombreempresa">
                <label class="block font-bold mb-2">Nuevo nombre de empresa</label>
                <div class="p-3 bg-gray-50 rounded">{{ company.nuevonombreempresa }}</div>
            </div>

            <!-- Descripción -->
            <div>
                <label class="block font-bold mb-2">Descripción</label>
                <div class="p-3 bg-gray-50 rounded">{{ company.description || 'N/A' }}</div>
            </div>

            <!-- Riesgo, Año y Sector en grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block font-bold mb-2">Riesgo</label>
                        <div class="p-3 bg-gray-50 rounded font-mono">{{ company.risk }}</div>
                </div>
                <div>
                    <label class="block font-bold mb-2">Año constitución</label>
                    <div class="p-3 bg-gray-50 rounded font-mono">{{ company.incorporation_year }}</div>
                </div>
                <div>
                    <label class="block font-bold mb-2">Sector</label>
                    <div class="p-3 bg-gray-50 rounded text-blue-600 font-medium">{{ company.sectornom }}</div>
                </div>
            </div>

            <!-- Subsector y Página web en grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold mb-2">Subsector</label>
                    <div class="p-3 bg-gray-50 rounded">{{ company.subsectornom || 'N/A' }}</div>
                </div>
                <div>
                    <label class="block font-bold mb-2">Página web</label>
                    <div class="p-3 bg-gray-50 rounded">
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

            <!-- Moneda (oculto pero consistente) -->
            <div class="hidden">
                <label class="block font-bold mb-2">Moneda</label>
                <div class="p-3 bg-gray-50 rounded">{{ getMonedaLabel(company.moneda) }}</div>
            </div>

            <!-- Campos de Ventas según moneda -->
            <div v-if="company.moneda && company.moneda !== ''" class="grid grid-cols-1 gap-4">
                <!-- Ventas PEN -->
                <div v-if="company.moneda === 'PEN' || company.moneda === 'BOTH'">
                    <label class="block font-bold mb-2">Facturado del año anterior PEN</label>
                    <div class="p-3 bg-gray-50 rounded font-mono text-green-600">
                        {{ formatCurrency(company.sales_PEN, 'PEN') }}
                    </div>
                </div>

                <!-- Ventas USD -->
                <div v-if="company.moneda === 'USD' || company.moneda === 'BOTH'">
                    <label class="block font-bold mb-2">Facturado del año anterior USD</label>
                    <div class="p-3 bg-gray-50 rounded font-mono text-blue-600">
                        {{ formatCurrency(company.sales_USD, 'USD') }}
                    </div>
                </div>
            </div>

            <!-- Información Financiera -->
            <div v-if="company.moneda && company.moneda !== ''" class="border rounded bg-gray-50 p-4">
                <h4 class="font-bold mb-4">Información Financiera</h4>
                
                <!-- Datos en PEN -->
                <div class="mb-6">
                    <h5 class="font-semibold mb-3 text-green-700">Datos en PEN (Soles)</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-medium mb-1">Facturas Financiadas</label>
                            <div class="p-3 bg-white rounded font-mono">
                                {{ formatNumber(company.finances?.facturas_financiadas_pen) }}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Monto Financiado</label>
                            <div class="p-3 bg-white rounded font-mono text-green-600">
                                {{ formatCurrency(company.finances?.monto_total_financiado_pen, 'PEN') }}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Facturas Pagadas</label>
                            <div class="p-3 bg-white rounded font-mono">
                                {{ formatNumber(company.finances?.pagadas_pen) }}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Plazo Promedio (pago)</label>
                            <div class="p-3 bg-white rounded font-mono">
                                {{ formatNumber(company.finances?.plazo_promedio_pago_pen) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Datos en USD -->
                <div>
                    <h5 class="font-semibold mb-3 text-blue-700">Datos en USD (Dólares)</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-medium mb-1">Facturas Financiadas</label>
                            <div class="p-3 bg-white rounded font-mono">
                                {{ formatNumber(company.finances?.facturas_financiadas_usd) }}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Monto Financiado</label>
                            <div class="p-3 bg-white rounded font-mono text-blue-600">
                                {{ formatCurrency(company.finances?.monto_total_financiado_usd, 'USD') }}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Facturas Pagadas</label>
                            <div class="p-3 bg-white rounded font-mono">
                                {{ formatNumber(company.finances?.pagadas_usd) }}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Plazo Promedio (pago)</label>
                            <div class="p-3 bg-white rounded font-mono">
                                {{ formatNumber(company.finances?.plazo_promedio_pago_usd) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <template #footer>
            <Button label="Cerrar" icon="pi pi-times" severity="secondary" text @click="closeDialog" />
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
    nuevonombreempresa?: string;
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
        plazo_promedio_pago_pen?: number;
        facturas_financiadas_usd?: number;
        monto_total_financiado_usd?: string;
        pagadas_usd?: number;
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
    const severities = ['success', 'info', 'warn', 'danger', 'contrast'];
    return severities[riskNum] || 'secondary';
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

const formatCurrency = (amount: string | number | null | undefined, currency: string) => {
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