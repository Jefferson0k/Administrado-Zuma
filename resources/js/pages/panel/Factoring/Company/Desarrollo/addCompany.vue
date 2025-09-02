<template>
    <Toolbar class="mb-6">
        <template #start>
            <Button label="Nueva Empresa" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" />
        </template>
        <template #end>
                <Button label="Exportar" icon="pi pi-upload" severity="secondary" @click="$emit('export')" />
        </template>
    </Toolbar>

    <Dialog v-model:visible="AgregarDialog" :style="{ width: '800px' }" header="Registro de Empresas" :modal="true">
        <div class="flex flex-col gap-4">

            <!-- RUC -->
            <div>
                <label class="block font-bold mb-2">RUC <span class="text-red-500">*</span></label>
                <InputNumber v-model="empresa.document" :useGrouping="false" :maxlength="11"
                    placeholder="Nº 12345678910" inputId="document" class="w-full" 
                    :class="{ 'p-invalid': submitted && (!empresa.document || serverErrors.document) }"
                    @keydown.enter="consultarRuc" />
                <small v-if="submitted && !empresa.document" class="text-red-500">
                    El RUC es obligatorio.
                </small>
                <small v-else-if="submitted && empresa.document && empresa.document.toString().length !== 11" class="text-red-500">
                    El RUC debe tener 11 dígitos.
                </small>
                <small v-else-if="serverErrors.document" class="text-red-500">
                    {{ serverErrors.document[0] }}
                </small>
            </div>

            <!-- Razón Social y Nombre comercial en grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold mb-2">Razón social <span class="text-red-500">*</span></label>
                    <InputText v-model.trim="empresa.business_name" placeholder="Razón social completa" 
                        class="w-full" maxlength="255"
                        :class="{ 'p-invalid': submitted && (!empresa.business_name || serverErrors.business_name) }"
                        :disabled="!rucConsultado" />
                    <small v-if="submitted && !empresa.business_name && rucConsultado" class="text-red-500">
                        La razón social es obligatoria.
                    </small>
                    <small v-else-if="serverErrors.business_name" class="text-red-500">
                        {{ serverErrors.business_name[0] }}
                    </small>
                </div>

                <div>
                    <label class="block font-bold mb-2">Nombre comercial <span class="text-red-500">*</span></label>
                    <InputText v-model.trim="empresa.name" placeholder="Nombre corto de la empresa" 
                        class="w-full" maxlength="255"
                        :class="{ 'p-invalid': submitted && (!empresa.name || serverErrors.name) }"
                        :disabled="!rucConsultado" />
                    <small v-if="submitted && !empresa.name && rucConsultado" class="text-red-500">
                        El nombre comercial es obligatorio.
                    </small>
                    <small v-else-if="serverErrors.name" class="text-red-500">
                        {{ serverErrors.name[0] }}
                    </small>
                </div>
            </div>

            <!-- Descripción -->
            <div>
                <label class="block font-bold mb-2">Descripción <span class="text-red-500">*</span></label>
                <Textarea v-model.trim="empresa.description" rows="2" placeholder="Breve descripción de la empresa"
                    class="w-full" maxlength="250"
                    :class="{ 'p-invalid': submitted && (!empresa.description || serverErrors.description) }"
                    :disabled="!rucConsultado" />
                <div class="flex justify-between items-center">
                    <div>
                        <small v-if="submitted && !empresa.description && rucConsultado" class="text-red-500">
                            La descripción es obligatoria.
                        </small>
                        <small v-else-if="serverErrors.description" class="text-red-500">
                            {{ serverErrors.description[0] }}
                        </small>
                    </div>
                    <small class="text-gray-500">
                        {{ empresa.description ? empresa.description.length : 0 }}/250
                    </small>
                </div>
            </div>
            
            <!-- Riesgo, Sector y Año en grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block font-bold mb-2">Riesgo <span class="text-red-500">*</span></label>
                    <Select v-model="empresa.risk" :options="riesgos" optionLabel="label" optionValue="value"
                        placeholder="Seleccionar" class="w-full" 
                        :class="{ 'p-invalid': submitted && (empresa.risk === null || empresa.risk === '' || serverErrors.risk) }"
                        :disabled="!rucConsultado">
                        <template #value="slotProps">
                            <div v-if="slotProps.value !== null && slotProps.value !== undefined" class="flex items-center">
                                <Tag :value="getRiesgoLabel(slotProps.value)" :severity="getRiesgoSeverity(slotProps.value)" />
                            </div>
                            <span v-else>{{ slotProps.placeholder }}</span>
                        </template>
                        <template #option="slotProps">
                            <Tag :value="slotProps.option.label" :severity="getRiesgoSeverity(slotProps.option.value)" />
                        </template>
                    </Select>
                    <small v-if="submitted && (empresa.risk === null || empresa.risk === '') && rucConsultado" class="text-red-500">
                        El riesgo es obligatorio.
                    </small>
                </div>
                <div>
                    <label class="block font-bold mb-2">Año constitución <span class="text-red-500">*</span></label>
                    <InputNumber v-model="empresa.incorporation_year" :useGrouping="false" :maxlength="4"
                        :min="1800" :max="2030" placeholder="2005" class="w-full" 
                        :class="{ 'p-invalid': submitted && (!empresa.incorporation_year || serverErrors.incorporation_year || (empresa.incorporation_year && (empresa.incorporation_year < 1800 || empresa.incorporation_year > 2030))) }"
                        :disabled="!rucConsultado" />
                    <small v-if="submitted && !empresa.incorporation_year && rucConsultado" class="text-red-500">
                        El año de constitución es obligatorio.
                    </small>
                    <small v-else-if="submitted && empresa.incorporation_year && (empresa.incorporation_year < 1800 || empresa.incorporation_year > 2030)" class="text-red-500">
                        El año debe estar entre 1800 y {{ new Date().getFullYear() }}.
                    </small>
                    <small v-else-if="serverErrors.incorporation_year" class="text-red-500">
                        {{ serverErrors.incorporation_year[0] }}
                    </small>
                </div>
                <div>
                    <label class="block font-bold mb-2">Sector <span class="text-red-500">*</span></label>
                    <Select v-model="empresa.sector_id" :options="sectores" optionLabel="name" optionValue="id"
                        placeholder="Seleccionar" class="w-full" 
                        :class="{ 'p-invalid': submitted && (!empresa.sector_id || serverErrors.sector_id) }"
                        :disabled="!rucConsultado" />
                    <small v-if="submitted && !empresa.sector_id && rucConsultado" class="text-red-500">
                        El sector es obligatorio.
                    </small>
                </div>
            </div>

            <!-- Subsector y Página web en grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold mb-2">Subsector</label>
                    <Select v-model="empresa.subsector_id" :options="subsectores" optionLabel="name" optionValue="id"
                        placeholder="Seleccionar" class="w-full" 
                        :class="{ 'p-invalid': serverErrors.subsector_id }"
                        :disabled="!rucConsultado || !empresa.sector_id" />
                </div>

                <div>
                    <label class="block font-bold mb-2">Página web <span class="text-red-500">*</span></label>
                    <InputText v-model.trim="empresa.link_web_page" placeholder="https://www.miempresa.com" 
                        class="w-full" maxlength="255"
                        :class="{ 'p-invalid': submitted && (!empresa.link_web_page || serverErrors.link_web_page || !isValidUrl(empresa.link_web_page)) }"
                        :disabled="!rucConsultado" />
                    <small v-if="submitted && !empresa.link_web_page && rucConsultado" class="text-red-500">
                        La página web es obligatoria.
                    </small>
                    <small v-else-if="submitted && empresa.link_web_page && !isValidUrl(empresa.link_web_page)" class="text-red-500">
                        Ingrese una URL válida.
                    </small>
                </div>
            </div>

            <!-- Moneda -->
            <div>
                <label class="block font-bold mb-2">Moneda <span class="text-red-500">*</span></label>
                <Select v-model="empresa.moneda" :options="monedas" optionLabel="label" optionValue="value"
                    placeholder="Seleccione la moneda" class="w-full" 
                    :class="{ 'p-invalid': submitted && (!empresa.moneda || serverErrors.moneda) }"
                    :disabled="!rucConsultado" />
                <small v-if="submitted && !empresa.moneda && rucConsultado" class="text-red-500">
                    La moneda es obligatoria.
                </small>
                <small v-else-if="serverErrors.moneda" class="text-red-500">
                    {{ serverErrors.moneda[0] }}
                </small>
            </div>

            <!-- Campos de Ventas según moneda -->
            <div v-if="empresa.moneda && empresa.moneda !== ''" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Campo PEN -->
                <div v-if="empresa.moneda === 'PEN' || empresa.moneda === 'BOTH'">
                    <label class="block font-bold mb-2">
                        Volumen de ventas PEN <span class="text-red-500">*</span>
                    </label>
                    <div class="p-inputgroup">
                    <InputNumber 
                        v-model="empresa.sales_PEN" 
                        mode="currency" 
                        currency="PEN" 
                        locale="es-PE"
                        :minFractionDigits="2" 
                        :min="0"
                        placeholder="Ej: 500000.00" 
                        class="w-full" 
                        :class="{ 'p-invalid': submitted && (!empresa.sales_PEN && empresa.sales_PEN !== 0 || serverErrors.sales_PEN) }"
                        :disabled="!rucConsultado" 
                    />
                    </div>

                    <small v-if="submitted && !empresa.sales_PEN && empresa.sales_PEN !== 0 && (empresa.moneda === 'PEN' || empresa.moneda === 'BOTH')" class="text-red-500">
                        El volumen de ventas en PEN es obligatorio.
                    </small>
                    <small v-else-if="serverErrors.sales_PEN" class="text-red-500">
                        {{ serverErrors.sales_PEN[0] }}
                    </small>
                </div>

                <!-- Campo USD -->
                <div v-if="empresa.moneda === 'USD' || empresa.moneda === 'BOTH'">
                    <label class="block font-bold mb-2">
                        Volumen de ventas USD <span class="text-red-500">*</span>
                    </label>
                    <div class="p-inputgroup">
                        <InputNumber v-model="empresa.sales_USD" mode="currency" currency="USD" locale="en-US" :minFractionDigits="2" :min="0"
                            placeholder="Ej: 150000.00" class="w-full" 
                            :class="{ 'p-invalid': submitted && (!empresa.sales_USD && empresa.sales_USD !== 0 || serverErrors.sales_USD) }"
                            :disabled="!rucConsultado" />
                    </div>
                    <small v-if="submitted && !empresa.sales_USD && empresa.sales_USD !== 0 && (empresa.moneda === 'USD' || empresa.moneda === 'BOTH')" class="text-red-500">
                        El volumen de ventas en USD es obligatorio.
                    </small>
                    <small v-else-if="serverErrors.sales_USD" class="text-red-500">
                        {{ serverErrors.sales_USD[0] }}
                    </small>
                </div>
            </div>

            <!-- Campos financieros según moneda seleccionada -->
            <div v-if="empresa.moneda && empresa.moneda !== ''" class="border p-4 rounded bg-gray-50">
                <h4 class="font-bold mb-4">Información Financiera</h4>
                
                <!-- Si es PEN o BOTH -->
                <div v-if="empresa.moneda === 'PEN' || empresa.moneda === 'BOTH'" class="mb-6">
                    <h5 class="font-semibold mb-3 text-green-700">Datos en PEN (Soles)</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-medium mb-1">Facturas Financiadas <span class="text-red-500">*</span></label>
                            <InputNumber v-model="empresa.facturas_financiadas_pen"  :min="0" placeholder="0" class="w-full" 
                                :class="{ 'p-invalid': submitted && (empresa.facturas_financiadas_pen === null || empresa.facturas_financiadas_pen === undefined) }"
                                :disabled="!rucConsultado" />
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Monto Financiado <span class="text-red-500">*</span></label>
                            <div class="p-inputgroup">
                                <InputNumber v-model="empresa.monto_total_financiado_pen" mode="currency" currency="PEN"  locale="es-PE" :minFractionDigits="2" :min="0" 
                                    placeholder="0.00" class="w-full" 
                                    :class="{ 'p-invalid': submitted && (empresa.monto_total_financiado_pen === null || empresa.monto_total_financiado_pen === undefined) }"
                                    :disabled="!rucConsultado" />
                            </div>
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Facturas Pagadas <span class="text-red-500">*</span></label>
                            <InputNumber v-model="empresa.pagadas_pen" :min="0" placeholder="0" class="w-full" 
                                :class="{ 'p-invalid': submitted && (empresa.pagadas_pen === null || empresa.pagadas_pen === undefined) }"
                                :disabled="!rucConsultado" />
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Facturas Pendientes <span class="text-red-500">*</span></label>
                            <InputNumber v-model="empresa.pendientes_pen" :min="0" placeholder="0" class="w-full" 
                                :class="{ 'p-invalid': submitted && (empresa.pendientes_pen === null || empresa.pendientes_pen === undefined) }"
                                :disabled="!rucConsultado" />
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Plazo Pago (días) <span class="text-red-500">*</span></label>
                            <InputNumber v-model="empresa.plazo_promedio_pago_pen" :min="0" placeholder="30" class="w-full" 
                                :class="{ 'p-invalid': submitted && (empresa.plazo_promedio_pago_pen === null || empresa.plazo_promedio_pago_pen === undefined) }"
                                :disabled="!rucConsultado" />
                        </div>
                    </div>
                </div>

                <!-- Si es USD o BOTH -->
                <div v-if="empresa.moneda === 'USD' || empresa.moneda === 'BOTH'">
                    <h5 class="font-semibold mb-3 text-blue-700">Datos en USD (Dólares)</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-medium mb-1">Facturas Financiadas <span class="text-red-500">*</span></label>
                            <InputNumber v-model="empresa.facturas_financiadas_usd" :min="0" placeholder="0" class="w-full" 
                                :class="{ 'p-invalid': submitted && (empresa.facturas_financiadas_usd === null || empresa.facturas_financiadas_usd === undefined) }"
                                :disabled="!rucConsultado" />
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Monto Financiado <span class="text-red-500">*</span></label>
                            <div class="p-inputgroup">
                                <InputNumber v-model="empresa.monto_total_financiado_usd" mode="currency" currency="USD" locale="en-US" :minFractionDigits="2" :min="0" 
                                    placeholder="0.00" class="w-full" 
                                    :class="{ 'p-invalid': submitted && (empresa.monto_total_financiado_usd === null || empresa.monto_total_financiado_usd === undefined) }"
                                    :disabled="!rucConsultado" />
                            </div>
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Facturas Pagadas <span class="text-red-500">*</span></label>
                            <InputNumber v-model="empresa.pagadas_usd" :min="0" placeholder="0" class="w-full" 
                                :class="{ 'p-invalid': submitted && (empresa.pagadas_usd === null || empresa.pagadas_usd === undefined) }"
                                :disabled="!rucConsultado" />
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Facturas Pendientes <span class="text-red-500">*</span></label>
                            <InputNumber v-model="empresa.pendientes_usd" :min="0" placeholder="0" class="w-full" 
                                :class="{ 'p-invalid': submitted && (empresa.pendientes_usd === null || empresa.pendientes_usd === undefined) }"
                                :disabled="!rucConsultado" />
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Plazo Pago (días) <span class="text-red-500">*</span></label>
                            <InputNumber v-model="empresa.plazo_promedio_pago_usd" :min="0" placeholder="30" class="w-full" 
                                :class="{ 'p-invalid': submitted && (empresa.plazo_promedio_pago_usd === null || empresa.plazo_promedio_pago_usd === undefined) }"
                                :disabled="!rucConsultado" />
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Botones -->
        <template #footer>
            <div class="flex justify-between items-center w-full">
                <small class="italic text-sm">
                    Los campos marcados con <span class="text-red-500">*</span> son obligatorios.
                </small>
                <div class="flex gap-2">
                    <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog" severity="secondary" />
                    <Button label="Guardar" icon="pi pi-check" :loading="loading" 
                        :disabled="!rucConsultado || !isFormValid()" @click="guardarEmpresa" severity="contrast"/>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import Tag from 'primevue/tag';
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import { defineEmits } from 'vue';

const toast = useToast();

const emit = defineEmits(['agregado', 'export']);

const AgregarDialog = ref(false);
const rucConsultado = ref(false);
const submitted = ref(false);
const loading = ref(false);
const serverErrors = ref({});

const riesgos = [
    { label: 'A', value: 0 },
    { label: 'B', value: 1 },
    { label: 'C', value: 2 },
    { label: 'D', value: 3 },
    { label: 'E', value: 4 }
];

// Opciones de moneda actualizadas para manejar BOTH
const monedas = [
    { label: 'Soles (PEN)', value: 'PEN' },
    { label: 'Dólares (USD)', value: 'USD' },
    { label: 'Ambas Monedas', value: 'BOTH' }
];

const sectores = ref([]);
const subsectores = ref([]);

const empresa = ref({
    document: null,
    name: '',
    business_name: '',
    risk: null,
    sector_id: null,
    subsector_id: null,
    incorporation_year: null,
    sales_PEN: null,
    sales_USD: null,
    link_web_page: '',
    moneda: '',
    description: '',
    // Campos financieros integrados - estos van al CompanyFinance
    sales_volume_pen: null,
    sales_volume_usd: null,
    facturas_financiadas_pen: null,
    monto_total_financiado_pen: null,
    pagadas_pen: null,
    pendientes_pen: null,
    plazo_promedio_pago_pen: null,
    facturas_financiadas_usd: null,
    monto_total_financiado_usd: null,
    pagadas_usd: null,
    pendientes_usd: null,
    plazo_promedio_pago_usd: null
});

// Funciones helper para el select de riesgo
function getRiesgoLabel(value) {
    const riesgo = riesgos.find(r => r.value === value);
    return riesgo ? riesgo.label : '';
}

// Función para determinar el color del tag según el riesgo
function getRiesgoSeverity(value) {
    switch (value) {
        case 0: return 'success';    // A - Verde
        case 1: return 'info';       // B - Azul
        case 2: return 'warn';       // C - Amarillo
        case 3: return 'danger';     // D - Rojo
        case 4: return 'contrast';   // E - Negro/Gris
        default: return 'secondary';
    }
}

// Validación de URL
function isValidUrl(url) {
    if (!url) return false;
    try {
        return url.startsWith('http://') || url.startsWith('https://');
    } catch {
        return false;
    }
}

// Función para transformar moneda antes del envío
function transformMonedaForSubmit(moneda) {
    // El backend espera BOTH, no AMBAS
    return moneda;
}

// Función para transformar moneda desde el servidor
function transformMonedaFromServer(moneda) {
    // El backend usa BOTH, no AMBAS
    return moneda;
}

// Validación del formulario
function isFormValid() {
    if (!rucConsultado.value) return false;
    
    // Verificar campos básicos requeridos
    const requiredFields = [
        'document', 'business_name', 'name', 'link_web_page', 'moneda', 'sector_id', 'description', 'incorporation_year'
    ];
    
    for (const field of requiredFields) {
        if (!empresa.value[field]) return false;
    }
    
    // Verificar riesgo (puede ser 0)
    if (empresa.value.risk === null || empresa.value.risk === '') return false;
    
    // Verificar campos de ventas según moneda
    if (empresa.value.moneda === 'PEN' || empresa.value.moneda === 'BOTH') {
        if (empresa.value.sales_PEN === null || empresa.value.sales_PEN === undefined) return false;
    }
    
    if (empresa.value.moneda === 'USD' || empresa.value.moneda === 'BOTH') {
        if (empresa.value.sales_USD === null || empresa.value.sales_USD === undefined) return false;
    }
    
    // Validar campos financieros específicos según moneda
    if (empresa.value.moneda === 'PEN' || empresa.value.moneda === 'BOTH') {
        const requiredPenFields = [
            'facturas_financiadas_pen', 'monto_total_financiado_pen', 
            'pagadas_pen', 'pendientes_pen', 'plazo_promedio_pago_pen'
        ];
        for (const field of requiredPenFields) {
            if (empresa.value[field] === null || empresa.value[field] === undefined) return false;
        }
    }
    
    if (empresa.value.moneda === 'USD' || empresa.value.moneda === 'BOTH') {
        const requiredUsdFields = [
            'facturas_financiadas_usd', 'monto_total_financiado_usd', 
            'pagadas_usd', 'pendientes_usd', 'plazo_promedio_pago_usd'
        ];
        for (const field of requiredUsdFields) {
            if (empresa.value[field] === null || empresa.value[field] === undefined) return false;
        }
    }
    
    // Validar RUC
    if (!empresa.value.document || empresa.value.document.toString().length !== 11) return false;
    
    // Validar longitudes
    if (empresa.value.business_name.length > 255) return false;
    if (empresa.value.name.length > 255) return false;
    if (empresa.value.description && empresa.value.description.length > 250) return false;
    if (empresa.value.link_web_page.length > 255) return false;
    
    // Validar URL
    if (!isValidUrl(empresa.value.link_web_page)) return false;
    
    // Validar año
    if (empresa.value.incorporation_year && (empresa.value.incorporation_year < 1800 || empresa.value.incorporation_year > 2030)) return false;
    
    return true;
}

/* Cargar sectores al montar */
onMounted(async () => {
    try {
        const response = await axios.get('/sectors/search');
        sectores.value = response.data.data;
    } catch (error) {
        console.error('Error al cargar sectores:', error);
    }
});

/* Cada vez que se selecciona un sector, cargar subsectores */
watch(() => empresa.value.sector_id, async (nuevoSector) => {
    if (!nuevoSector) {
        subsectores.value = [];
        empresa.value.subsector_id = null;
        return;
    }

    try {
        const response = await axios.get(`/subsectors/search/${nuevoSector}`);
        subsectores.value = response.data.data;
    } catch (error) {
        console.error('Error al cargar subsectores:', error);
        subsectores.value = [];
    }
});

// Watch para resetear campos de ventas cuando cambia la moneda
watch(() => empresa.value.moneda, (nuevaMoneda) => {
    if (nuevaMoneda !== 'PEN' && nuevaMoneda !== 'BOTH') {
        empresa.value.sales_PEN = null;
        empresa.value.sales_volume_pen = null;
        // Resetear campos financieros PEN
        empresa.value.facturas_financiadas_pen = null;
        empresa.value.monto_total_financiado_pen = null;
        empresa.value.pagadas_pen = null;
        empresa.value.pendientes_pen = null;
        empresa.value.plazo_promedio_pago_pen = null;
    }
    
    if (nuevaMoneda !== 'USD' && nuevaMoneda !== 'BOTH') {
        empresa.value.sales_USD = null;
        empresa.value.sales_volume_usd = null;
        // Resetear campos financieros USD
        empresa.value.facturas_financiadas_usd = null;
        empresa.value.monto_total_financiado_usd = null;
        empresa.value.pagadas_usd = null;
        empresa.value.pendientes_usd = null;
        empresa.value.plazo_promedio_pago_usd = null;
    }
    
    // Sincronizar los campos de ventas
    if (nuevaMoneda === 'PEN' || nuevaMoneda === 'BOTH') {
        empresa.value.sales_volume_pen = empresa.value.sales_PEN;
    }
    if (nuevaMoneda === 'USD' || nuevaMoneda === 'BOTH') {
        empresa.value.sales_volume_usd = empresa.value.sales_USD;
    }
});

async function consultarRuc() {
    if (!empresa.value.document || empresa.value.document.toString().length !== 11) {
        toast.add({
            severity: 'warn',
            summary: 'RUC inválido',
            detail: 'Debe ingresar un RUC válido de 11 dígitos',
            life: 3000
        });
        return;
    }

    try {
        const response = await axios.get(`/api/consultar-ruc/${empresa.value.document}`);
        const data = response.data;

        rucConsultado.value = true;

        if (data.razonSocial) {
            empresa.value.business_name = data.razonSocial;
            empresa.value.name = data.razonSocial;
        } else {
            empresa.value.business_name = '';
            empresa.value.name = '';
        }

        if (data.actividadEconomica) {
            empresa.value.description = data.actividadEconomica;
        } else {
            empresa.value.description = '';
        }

        toast.add({
            severity: 'success',
            summary: 'Datos cargados',
            detail: 'Información del RUC obtenida correctamente',
            life: 3000
        });
    } catch (error) {
        rucConsultado.value = false;
        
        empresa.value.business_name = '';
        empresa.value.name = '';
        empresa.value.description = '';

        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'No se pudo obtener información del RUC',
            life: 5000
        });
    }
}

function resetEmpresa() {
    rucConsultado.value = false;
    submitted.value = false;
    loading.value = false;
    serverErrors.value = {};
    empresa.value = {
        document: null,
        name: '',
        business_name: '',
        risk: null,
        sector_id: null,
        subsector_id: null,
        incorporation_year: null,
        sales_PEN: null,
        sales_USD: null,
        link_web_page: '',
        moneda: '',
        description: '',
        // Campos financieros integrados
        sales_volume_pen: null,
        sales_volume_usd: null,
        facturas_financiadas_pen: null,
        monto_total_financiado_pen: null,
        pagadas_pen: null,
        pendientes_pen: null,
        plazo_promedio_pago_pen: null,
        facturas_financiadas_usd: null,
        monto_total_financiado_usd: null,
        pagadas_usd: null,
        pendientes_usd: null,
        plazo_promedio_pago_usd: null
    };
}

function openNew() {
    resetEmpresa();
    AgregarDialog.value = true;
}

function hideDialog() {
    AgregarDialog.value = false;
    resetEmpresa();
}

// Función para cargar datos desde el JSON (útil para edición)
function loadEmpresaData(data) {
    // No necesitamos transformar la moneda, el backend ya usa BOTH
    empresa.value = {
        document: data.document,
        name: data.name,
        business_name: data.business_name,
        risk: data.risk,
        sector_id: data.sector_id,
        subsector_id: data.subsector_id,
        incorporation_year: data.incorporation_year,
        sales_PEN: data.sales_PEN,
        sales_USD: data.sales_USD,
        link_web_page: data.link_web_page,
        moneda: data.moneda,
        description: data.description,
        // Campos financieros
        sales_volume_pen: data.sales_PEN,
        sales_volume_usd: data.sales_USD,
        facturas_financiadas_pen: data.facturas_financiadas_pen,
        monto_total_financiado_pen: data.monto_total_financiado_pen,
        pagadas_pen: data.pagadas_pen,
        pendientes_pen: data.pendientes_pen,
        plazo_promedio_pago_pen: data.plazo_promedio_pago_pen,
        facturas_financiadas_usd: data.facturas_financiadas_usd,
        monto_total_financiado_usd: data.monto_total_financiado_usd,
        pagadas_usd: data.pagadas_usd,
        pendientes_usd: data.pendientes_usd,
        plazo_promedio_pago_usd: data.plazo_promedio_pago_usd
    };
    
    rucConsultado.value = true;
}

async function guardarEmpresa() {
    submitted.value = true;
    serverErrors.value = {};
    loading.value = true;

    try {
        const empresaParaEnvio = { ...empresa.value };
        empresaParaEnvio.moneda = transformMonedaForSubmit(empresa.value.moneda);
        await axios.post('/companies', empresaParaEnvio);
        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Empresa y datos financieros registrados correctamente',
            life: 3000
        });
        hideDialog();
        emit('agregado');
    } catch (error) {
        if (error.response && error.response.status === 422) {
            serverErrors.value = error.response.data.errors || {};
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se pudo registrar la empresa',
                life: 3000
            });
        }
    } finally {
        loading.value = false;
    }
}

function showToast() {
    toast.add({
        severity: 'info',
        summary: 'Información',
        detail: 'Funcionalidad en desarrollo',
        life: 3000
    });
}

// Exponer funciones útiles para uso externo
defineExpose({
    openNew,
    loadEmpresaData,
    hideDialog
});
</script>

<style scoped>
/* Estilos adicionales si son necesarios */
.p-tag {
    font-weight: 600;
}
</style>