<template>
    <Dialog
        :visible="visible"
        @update:visible="$emit('update:visible', $event)"
        :style="{ width: '800px' }"
        header="Editar Empresa"
        :modal="true"
    >
        <div class="flex flex-col gap-4" v-if="company">
            <!-- RUC (🔒 deshabilitado) -->
            <div>
                <label class="block font-bold mb-2">RUC <span class="text-red-500">*</span></label>
                <InputNumber
                    v-model="editForm.document"
                    :useGrouping="false"
                    :maxlength="11"
                    placeholder="Nº 12345678910"
                    inputId="document"
                    class="w-full"
                    :disabled="true"
                    :class="{ 'p-invalid': submitted && (!editForm.document || editErrors.document) }"
                />
                <small v-if="submitted && !editForm.document" class="text-red-500">
                    El RUC es obligatorio.
                </small>
                <small
                    v-else-if="submitted && editForm.document && editForm.document.toString().length !== 11"
                    class="text-red-500"
                >
                    El RUC debe tener 11 dígitos.
                </small>
                <small v-else-if="editErrors.document" class="text-red-500">
                    {{ editErrors.document[0] }}
                </small>
            </div>

            <!-- Razón Social y Nombre comercial (ambos 🔒) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold mb-2">Razón social <span class="text-red-500">*</span></label>
                    <InputText
                        v-model.trim="editForm.business_name"
                        placeholder="Razón social completa"
                        class="w-full"
                        maxlength="255"
                        :disabled="true"
                        :class="{ 'p-invalid': submitted && (!editForm.business_name || editErrors.business_name) }"
                    />
                    <small v-if="submitted && !editForm.business_name" class="text-red-500">
                        La razón social es obligatoria.
                    </small>
                    <small v-else-if="editErrors.business_name" class="text-red-500">
                        {{ editErrors.business_name[0] }}
                    </small>
                </div>

                <div>
                    <label class="block font-bold mb-2">Nombre comercial <span class="text-red-500">*</span></label>
                    <InputText
                        v-model.trim="editForm.name"
                        placeholder="Nombre corto de la empresa"
                        class="w-full"
                        maxlength="255"
                        :disabled="true"
                        :class="{ 'p-invalid': submitted && (!editForm.name || editErrors.name) }"
                    />
                    <small v-if="submitted && !editForm.name" class="text-red-500">
                        El nombre comercial es obligatorio.
                    </small>
                    <small v-else-if="editErrors.name" class="text-red-500">
                        {{ editErrors.name[0] }}
                    </small>
                </div>
            </div>

            <!-- ✅ Nuevo nombre de empresa (opcional y editable) -->
            <div>
                <label class="block font-bold mb-2">Nuevo nombre de empresa (opcional)</label>
                <InputText
                    v-model.trim="editForm.nuevonombreempresa"
                    placeholder="Propuesta de nuevo nombre"
                    class="w-full"
                    maxlength="255"
                    :class="{
                        'p-invalid':
                            submitted &&
                            (
                                (editForm.nuevonombreempresa && editForm.nuevonombreempresa.length > 255)
                                || editErrors.nuevonombreempresa
                            )
                    }"
                />
                <small
                    v-if="submitted && editForm.nuevonombreempresa && editForm.nuevonombreempresa.length > 255"
                    class="text-red-500"
                >
                    No puede superar 255 caracteres.
                </small>
                <small v-else-if="editErrors.nuevonombreempresa" class="text-red-500">
                    {{ editErrors.nuevonombreempresa[0] }}
                </small>
            </div>

            <!-- Descripción (✅ editable) -->
            <div>
                <label class="block font-bold mb-2">Descripción <span class="text-red-500">*</span></label>
                <Textarea
                    v-model.trim="editForm.description"
                    rows="2"
                    placeholder="Breve descripción de la empresa"
                    class="w-full"
                    maxlength="250"
                    :class="{ 'p-invalid': submitted && (!editForm.description || editErrors.description) }"
                />
                <div class="flex justify-between items-center">
                    <div>
                        <small v-if="submitted && !editForm.description" class="text-red-500">
                            La descripción es obligatoria.
                        </small>
                        <small v-else-if="editErrors.description" class="text-red-500">
                            {{ editErrors.description[0] }}
                        </small>
                    </div>
                    <small class="text-gray-500">
                        {{ editForm.description ? editForm.description.length : 0 }}/250
                    </small>
                </div>
            </div>

            <!-- Riesgo, Año y Sector -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Riesgo (✅ editable) -->
                <div>
                    <label class="block font-bold mb-2">Riesgo <span class="text-red-500">*</span></label>
                    <Select
                        v-model="editForm.risk"
                        :options="riesgos"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Seleccionar"
                        class="w-full"
                        :class="{ 'p-invalid': submitted && (editForm.risk === null || editForm.risk === '' || editErrors.risk) }"
                    >
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
                    <small v-if="submitted && (editForm.risk === null || editForm.risk === '')" class="text-red-500">
                        El riesgo es obligatorio.
                    </small>
                </div>

                <!-- Año constitución (🔒) -->
                <div>
                    <label class="block font-bold mb-2">Año constitución <span class="text-red-500">*</span></label>
                    <InputNumber
                        v-model="editForm.incorporation_year"
                        :useGrouping="false"
                        :maxlength="4"
                        :min="1800"
                        :max="2030"
                        placeholder="2005"
                        class="w-full"
                        :disabled="true"
                        :class="{ 'p-invalid': submitted && (!editForm.incorporation_year || editErrors.incorporation_year || (editForm.incorporation_year && (editForm.incorporation_year < 1800 || editForm.incorporation_year > 2030))) }"
                    />
                    <small v-if="submitted && !editForm.incorporation_year" class="text-red-500">
                        El año de constitución es obligatorio.
                    </small>
                    <small
                        v-else-if="submitted && editForm.incorporation_year && (editForm.incorporation_year < 1800 || editForm.incorporation_year > 2030)"
                        class="text-red-500"
                    >
                        El año debe estar entre 1800 y {{ new Date().getFullYear() }}.
                    </small>
                    <small v-else-if="editErrors.incorporation_year" class="text-red-500">
                        {{ editErrors.incorporation_year[0] }}
                    </small>
                </div>

                <!-- Sector (🔒) -->
                <div>
                    <label class="block font-bold mb-2">Sector <span class="text-red-500">*</span></label>
                    <Select
                        v-model="editForm.sector_id"
                        :options="sectores"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Seleccionar"
                        class="w-full"
                        :disabled="true"
                        :class="{ 'p-invalid': submitted && (!editForm.sector_id || editErrors.sector_id) }"
                    />
                    <small v-if="submitted && !editForm.sector_id" class="text-red-500">
                        El sector es obligatorio.
                    </small>
                </div>
            </div>

            <!-- Subsector (🔒) y Página web (✅) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold mb-2">Subsector</label>
                    <Select
                        v-model="editForm.subsector_id"
                        :options="subsectores"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Seleccionar"
                        class="w-full"
                        :disabled="true"
                        :class="{ 'p-invalid': editErrors.subsector_id }"
                    />
                </div>

                <div>
                    <label class="block font-bold mb-2">Página web <span class="text-red-500">*</span></label>
                    <InputText
                        v-model.trim="editForm.link_web_page"
                        placeholder="https://www.miempresa.com"
                        class="w-full"
                        maxlength="255"
                        :class="{ 'p-invalid': submitted && (!editForm.link_web_page || editErrors.link_web_page || !isValidUrl(editForm.link_web_page)) }"
                    />
                    <small v-if="submitted && !editForm.link_web_page" class="text-red-500">
                        La página web es obligatoria.
                    </small>
                    <small v-else-if="submitted && editForm.link_web_page && !isValidUrl(editForm.link_web_page)" class="text-red-500">
                        Ingrese una URL válida.
                    </small>
                </div>
            </div>

            <!-- Moneda (🔒) -->
            <div>
                <label class="block font-bold mb-2">Moneda <span class="text-red-500">*</span></label>
                <Select
                    v-model="editForm.moneda"
                    :options="monedas"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Seleccione la moneda"
                    class="w-full"
                    :disabled="true"
                    :class="{ 'p-invalid': submitted && (!editForm.moneda || editErrors.moneda) }"
                />
                <small v-if="submitted && !editForm.moneda" class="text-red-500">
                    La moneda es obligatoria.
                </small>
                <small v-else-if="editErrors.moneda" class="text-red-500">
                    {{ editErrors.moneda[0] }}
                </small>
            </div>

            <!-- Campos de Ventas (🔒) -->
            <div v-if="editForm.moneda && editForm.moneda !== ''" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-if="editForm.moneda === 'PEN' || editForm.moneda === 'BOTH'">
                    <label class="block font-bold mb-2">Volumen de ventas PEN <span class="text-red-500">*</span></label>
                    <div class="p-inputgroup">
                        <InputNumber
                            v-model="editForm.sales_PEN"
                            mode="currency"
                            currency="PEN"
                            locale="es-PE"
                            :minFractionDigits="2"
                            :min="0"
                            placeholder="Ej: 500000.00"
                            class="w-full"
                            :disabled="true"
                            :class="{ 'p-invalid': submitted && (!editForm.sales_PEN && editForm.sales_PEN !== 0 || editErrors.sales_PEN) }"
                        />
                    </div>
                    <small
                        v-if="submitted && !editForm.sales_PEN && editForm.sales_PEN !== 0 && (editForm.moneda === 'PEN' || editForm.moneda === 'BOTH')"
                        class="text-red-500"
                    >
                        El volumen de ventas en PEN es obligatorio.
                    </small>
                    <small v-else-if="editErrors.sales_PEN" class="text-red-500">
                        {{ editErrors.sales_PEN[0] }}
                    </small>
                </div>

                <div v-if="editForm.moneda === 'USD' || editForm.moneda === 'BOTH'">
                    <label class="block font-bold mb-2">Volumen de ventas USD <span class="text-red-500">*</span></label>
                    <div class="p-inputgroup">
                        <InputNumber
                            v-model="editForm.sales_USD"
                            mode="currency"
                            currency="USD"
                            locale="en-US"
                            :minFractionDigits="2"
                            :min="0"
                            placeholder="Ej: 150000.00"
                            class="w-full"
                            :disabled="true"
                            :class="{ 'p-invalid': submitted && (!editForm.sales_USD && editForm.sales_USD !== 0 || editErrors.sales_USD) }"
                        />
                    </div>
                    <small
                        v-if="submitted && !editForm.sales_USD && editForm.sales_USD !== 0 && (editForm.moneda === 'USD' || editForm.moneda === 'BOTH')"
                        class="text-red-500"
                    >
                        El volumen de ventas en USD es obligatorio.
                    </small>
                    <small v-else-if="editErrors.sales_USD" class="text-red-500">
                        {{ editErrors.sales_USD[0] }}
                    </small>
                </div>
            </div>

            <!-- Información Financiera (🔒) -->
            <div v-if="editForm.moneda && editForm.moneda !== ''" class="border p-4 rounded bg-gray-50">
                <h4 class="font-bold mb-4">Información Financiera</h4>

                <div v-if="editForm.moneda === 'PEN' || editForm.moneda === 'BOTH'" class="mb-6">
                    <h5 class="font-semibold mb-3 text-green-700">Datos en PEN (Soles)</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-medium mb-1">Facturas Financiadas <span class="text-red-500">*</span></label>
                            <InputNumber v-model="editForm.facturas_financiadas_pen" :min="0" placeholder="0" class="w-full" :disabled="true" />
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Monto Financiado <span class="text-red-500">*</span></label>
                            <div class="p-inputgroup">
                                <InputNumber
                                    v-model="editForm.monto_total_financiado_pen"
                                    mode="currency"
                                    currency="PEN"
                                    locale="es-PE"
                                    :minFractionDigits="2"
                                    :min="0"
                                    placeholder="0.00"
                                    class="w-full"
                                    :disabled="true"
                                />
                            </div>
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Facturas Pagadas <span class="text-red-500">*</span></label>
                            <InputNumber v-model="editForm.pagadas_pen" :min="0" placeholder="0" class="w-full" :disabled="true" />
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Facturas Pendientes <span class="text-red-500">*</span></label>
                            <InputNumber v-model="editForm.pendientes_pen" :min="0" placeholder="0" class="w-full" :disabled="true" />
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Plazo Pago (días) <span class="text-red-500">*</span></label>
                            <InputNumber v-model="editForm.plazo_promedio_pago_pen" :min="0" placeholder="30" class="w-full" :disabled="true" />
                        </div>
                    </div>
                </div>

                <div v-if="editForm.moneda === 'USD' || editForm.moneda === 'BOTH'">
                    <h5 class="font-semibold mb-3 text-blue-700">Datos en USD (Dólares)</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-medium mb-1">Facturas Financiadas <span class="text-red-500">*</span></label>
                            <InputNumber v-model="editForm.facturas_financiadas_usd" :min="0" placeholder="0" class="w-full" :disabled="true" />
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Monto Financiado <span class="text-red-500">*</span></label>
                            <div class="p-inputgroup">
                                <InputNumber
                                    v-model="editForm.monto_total_financiado_usd"
                                    mode="currency"
                                    currency="USD"
                                    locale="en-US"
                                    :minFractionDigits="2"
                                    :min="0"
                                    placeholder="0.00"
                                    class="w-full"
                                    :disabled="true"
                                />
                            </div>
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Facturas Pagadas <span class="text-red-500">*</span></label>
                            <InputNumber v-model="editForm.pagadas_usd" :min="0" placeholder="0" class="w-full" :disabled="true" />
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Facturas Pendientes <span class="text-red-500">*</span></label>
                            <InputNumber v-model="editForm.pendientes_usd" :min="0" placeholder="0" class="w-full" :disabled="true" />
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Plazo Pago (días) <span class="text-red-500">*</span></label>
                            <InputNumber v-model="editForm.plazo_promedio_pago_usd" :min="0" placeholder="30" class="w-full" :disabled="true" />
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
                    <Button label="Cancelar" icon="pi pi-times" text @click="closeDialog" severity="secondary" />
                    <Button
                        label="Actualizar"
                        icon="pi pi-check"
                        :loading="saving"
                        :disabled="!isFormValid()"
                        @click="updateCompany"
                        severity="contrast"
                    />
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';

const props = defineProps({
    visible: Boolean,
    company: Object
});

const emit = defineEmits(['update:visible', 'updated']);

const toast = useToast();
const submitted = ref(false);
const saving = ref(false);
const editErrors = ref({});

const riesgos = [
    { label: 'A', value: 0 },
    { label: 'B', value: 1 },
    { label: 'C', value: 2 },
    { label: 'D', value: 3 },
    { label: 'E', value: 4 }
];

const monedas = [
    { label: 'Soles (PEN)', value: 'PEN' },
    { label: 'Dólares (USD)', value: 'USD' },
    { label: 'Ambas Monedas', value: 'BOTH' }
];

const sectores = ref([]);
const subsectores = ref([]);

const editForm = ref({
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
    // ✅ Nuevo campo opcional
    nuevonombreempresa: '',
    // Finanzas
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

// Helpers riesgo
function getRiesgoLabel(value) {
    const r = riesgos.find((x) => x.value === value);
    return r ? r.label : '';
}
function getRiesgoSeverity(value) {
    switch (value) {
        case 0: return 'success';
        case 1: return 'info';
        case 2: return 'warn';
        case 3: return 'danger';
        case 4: return 'contrast';
        default: return 'secondary';
    }
}

// URL válida
function isValidUrl(url) {
    if (!url) return false;
    try {
        return url.startsWith('http://') || url.startsWith('https://');
    } catch {
        return false;
    }
}

// ✅ Validamos solo riesgo, descripción y web; más un límite de 255 para el nuevo campo
function isFormValid() {
    const hasRisk = !(editForm.value.risk === null || editForm.value.risk === '');
    const hasDescription = !!editForm.value.description && editForm.value.description.trim().length > 0;
    const hasValidUrl = isValidUrl(editForm.value.link_web_page);
    const nuevoOk = !editForm.value.nuevonombreempresa || editForm.value.nuevonombreempresa.length <= 255;
    return hasRisk && hasDescription && hasValidUrl && nuevoOk;
}

// Cargar sectores (solo para mostrar)
onMounted(async () => {
    try {
        const response = await axios.get('/sectors/search');
        sectores.value = response.data.data;
    } catch (error) {
        console.error('Error al cargar sectores:', error);
    }
});

// Cargar subsectores si cambiara el sector (mantener coherencia)
watch(
    () => editForm.value.sector_id,
    async (nuevoSector) => {
        if (!nuevoSector) {
            subsectores.value = [];
            editForm.value.subsector_id = null;
            return;
        }
        try {
            const response = await axios.get(`/subsectors/search/${nuevoSector}`);
            subsectores.value = response.data.data;
        } catch (error) {
            console.error('Error al cargar subsectores:', error);
            subsectores.value = [];
        }
    }
);

// Reset ventas si cambia moneda (aunque estén deshabilitados)
watch(() => editForm.value.moneda, (nuevaMoneda) => {
    if (nuevaMoneda !== 'PEN' && nuevaMoneda !== 'BOTH') {
        editForm.value.sales_PEN = null;
        editForm.value.facturas_financiadas_pen = null;
        editForm.value.monto_total_financiado_pen = null;
        editForm.value.pagadas_pen = null;
        editForm.value.pendientes_pen = null;
        editForm.value.plazo_promedio_pago_pen = null;
    }
    if (nuevaMoneda !== 'USD' && nuevaMoneda !== 'BOTH') {
        editForm.value.sales_USD = null;
        editForm.value.facturas_financiadas_usd = null;
        editForm.value.monto_total_financiado_usd = null;
        editForm.value.pagadas_usd = null;
        editForm.value.pendientes_usd = null;
        editForm.value.plazo_promedio_pago_usd = null;
    }
});

// Cargar datos al abrir
watch([() => props.visible, () => props.company], ([visible, company]) => {
    if (visible && company) {
        editForm.value = {
            document: parseInt(company.document),
            name: company.name || '',
            business_name: company.business_name || '',
            risk: parseInt(company.risk),
            sector_id: company.sector_id,
            subsector_id: company.subsector_id,
            incorporation_year: parseInt(company.incorporation_year),
            sales_PEN: parseFloat(company.sales_PEN) || null,
            sales_USD: parseFloat(company.sales_USD) || null,
            link_web_page: company.link_web_page || '',
            moneda: company.moneda || '',
            description: company.description || '',
            // ✅ traer el nuevo campo si existe en el modelo
            nuevonombreempresa: company.nuevonombreempresa ?? '',
            // Finanzas
            facturas_financiadas_pen: company.finances?.facturas_financiadas_pen || null,
            monto_total_financiado_pen: parseFloat(company.finances?.monto_total_financiado_pen) || null,
            pagadas_pen: company.finances?.pagadas_pen || null,
            pendientes_pen: company.finances?.pendientes_pen || null,
            plazo_promedio_pago_pen: company.finances?.plazo_promedio_pago_pen || null,
            facturas_financiadas_usd: company.finances?.facturas_financiadas_usd || null,
            monto_total_financiado_usd: parseFloat(company.finances?.monto_total_financiado_usd) || null,
            pagadas_usd: company.finances?.pagadas_usd || null,
            pendientes_usd: company.finances?.pendientes_usd || null,
            plazo_promedio_pago_usd: company.finances?.plazo_promedio_pago_usd || null
        };

        editErrors.value = {};
        submitted.value = false;
    }
});

const closeDialog = () => {
    emit('update:visible', false);
    editErrors.value = {};
    submitted.value = false;
};

async function updateCompany() {
    submitted.value = true;
    editErrors.value = {};
    saving.value = true;

    try {
        const empresaParaEnvio = { ...editForm.value };

        await axios.put(`/companies/${props.company.id}`, empresaParaEnvio);

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Empresa y datos financieros actualizados correctamente',
            life: 3000
        });

        closeDialog();
        emit('updated');
    } catch (error) {
        if (error.response && error.response.status === 422) {
            editErrors.value = error.response.data.errors || {};
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se pudo actualizar la empresa',
                life: 3000
            });
        }
    } finally {
        saving.value = false;
    }
}
</script>
