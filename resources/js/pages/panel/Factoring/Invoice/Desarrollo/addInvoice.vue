<template>
  <Toolbar class="mb-6">
    <template #start>
      <Button label="Nueva Factura" icon="pi pi-plus" severity="contrast" class="mr-2" @click="openNew" />
    </template>
    <template #end>
      <Button label="Export" icon="pi pi-upload" severity="secondary" />
    </template>
  </Toolbar>

  <Dialog v-model:visible="facturaDialog" :style="{ width: '600px' }" header="Registro de facturas" :modal="true">
    <div class="flex flex-col gap-4">
      <!-- Empresa (solo ocupa toda la fila) -->
      <div>
        <label for="company_id" class="block font-bold mb-2">
          Empresa <span class="text-red-500">*</span>
        </label>
        <Select v-model="factura.company_id" :options="empresas" class="w-full"
          :class="{ 'p-invalid': submitted && (!factura.company_id || errors.company_id) }"
          :disabled="camposGeneralesBloqueados" editable optionLabel="label" optionValue="value" showClear
          placeholder="Buscar empresas..." @update:modelValue="onEmpresaSelected" @filter="onInputChange"
          :virtualScrollerOptions="{ itemSize: 60 }" scrollHeight="300px">

          <template #value="{ value }">
            <div class="flex items-center">
              <span class="truncate">{{ getEmpresaLabel(value) }}</span>
            </div>
          </template>

          <template #option="{ option }">
            <div class="flex justify-between items-center py-2 px-1">
              <div class="flex-1 min-w-0 mr-2">
                <div class="font-semibold truncate">{{ option.label }}</div>
                <div class="text-sm text-gray-600 truncate">{{ option.sublabel }}</div>
              </div>
              <Tag v-if="option.riesgo" :value="option.riesgo" :severity="getRiesgoSeverity(option.riesgoValue)"
                class="flex-shrink-0" />
            </div>
          </template>

          <template #empty>
            <div class="text-center py-2">Empresa no encontrada</div>
          </template>
        </Select>
        <small v-if="submitted && (!factura.company_id || errors.company_id)" class="text-red-500">
          {{ errors.company_id || 'Debe seleccionar una empresa.' }}
        </small>
      </div>

      <!-- Grid para Moneda y Tasa (2 columnas) -->
      <div class="grid grid-cols-2 gap-4">
        <!-- Moneda -->
        <div>
          <label for="currency" class="block font-bold mb-2">
            Moneda <span class="text-red-500">*</span>
          </label>
          <Select id="currency" v-model="factura.currency" :options="monedas" optionLabel="name" optionValue="code"
            placeholder="Seleccionar moneda" class="w-full"
            :class="{ 'p-invalid': submitted && (!factura.currency || errors.currency) }" />
          <small v-if="submitted && (!factura.currency || errors.currency)" class="text-red-500">
            {{ errors.currency || 'La moneda es obligatoria.' }}
          </small>
        </div>

        <!-- Tasa % -->
        <div>
          <label for="rate" class="block font-bold mb-2">
            Tasa % <span class="text-red-500">*</span>
          </label>
          <InputNumber id="rate" v-model="factura.rate" mode="decimal" :minFractionDigits="2" :maxFractionDigits="4"
            :min="0.01" :max="6" suffix="%" class="w-full"
            :class="{ 'p-invalid': submitted && (!factura.rate || factura.rate <= 0 || factura.rate > 6 || errors.rate) }" />
          <small v-if="submitted && (!factura.rate || factura.rate <= 0)" class="text-red-500">
            {{ errors.rate || 'La tasa es obligatoria y debe ser mayor a 0.' }}
          </small>
          <small v-else-if="submitted && factura.rate > 6" class="text-red-500">
            {{ errors.rate || 'La tasa no puede superar el 6%.' }}
          </small>
        </div>
      </div>

      <!-- Grid para Monto Factura y Monto Financiado (2 columnas) -->
      <div class="grid grid-cols-2 gap-4">
        <!-- Monto Factura -->
        <div>
          <label for="amount" class="block font-bold mb-2">
            Monto Factura <span class="text-red-500">*</span>
          </label>
          <InputNumber id="amount" v-model="factura.amount" mode="currency" :currency="factura.currency || 'PEN'"
            :locale="currencyLocale" :minFractionDigits="2" :maxFractionDigits="2" :min="0.01" class="w-full"
            :class="{ 'p-invalid': submitted && (!factura.amount || factura.amount <= 0 || errors.amount) }" />
          <small v-if="submitted && (!factura.amount || factura.amount <= 0 || errors.amount)" class="text-red-500">
            {{ errors.amount || 'El monto de factura es obligatorio y debe ser mayor a 0.' }}
          </small>
        </div>

        <!-- Monto Financiado por Garantía -->
        <div>
          <label for="financed_amount_by_garantia" class="block font-bold mb-2">
            Monto Financiado por Garantía <span class="text-red-500">*</span>
          </label>
          <InputNumber id="financed_amount_by_garantia" v-model="factura.financed_amount_by_garantia" mode="currency"
            :currency="factura.currency || 'PEN'" :locale="currencyLocale" :minFractionDigits="2" :maxFractionDigits="2"
            :min="0.01" :max="factura.amount || undefined" class="w-full"
            :class="{ 'p-invalid': submitted && (!factura.financed_amount_by_garantia || factura.financed_amount_by_garantia <= 0 || (factura.amount && factura.financed_amount_by_garantia > factura.amount) || errors.financed_amount_by_garantia) }" />
          <small v-if="submitted && (!factura.financed_amount_by_garantia || factura.financed_amount_by_garantia <= 0)"
            class="text-red-500">
            {{ errors.financed_amount_by_garantia || 'El monto financiado por garantía es obligatorio y debe ser mayor a 0.' }}
          </small>
          <small v-else-if="submitted && factura.amount && factura.financed_amount_by_garantia > factura.amount"
            class="text-red-500">
            {{ errors.financed_amount_by_garantia || 'El monto financiado no puede ser mayor al monto de la factura.' }}
          </small>
        </div>
      </div>

      <!-- Grid para Fecha y Número del Préstamo (2 columnas) -->
      <div class="grid grid-cols-2 gap-4">
        <!-- Fecha Estimada de Pago -->
        <div>
          <label for="estimated_pay_date" class="block font-bold mb-2">
            Fecha Estimada de Pago <span class="text-red-500">*</span>
          </label>
          <DatePicker id="estimated_pay_date" v-model="factura.estimated_pay_date" dateFormat="dd/mm/yy" class="w-full"
            :minDate="minDate"
            :class="{ 'p-invalid': submitted && (!factura.estimated_pay_date || errors.estimated_pay_date) }" />
          <small v-if="submitted && (!factura.estimated_pay_date || errors.estimated_pay_date)" class="text-red-500">
            {{ errors.estimated_pay_date || 'La fecha estimada de pago es obligatoria.' }}
          </small>
        </div>

        <!-- Número del Préstamo -->
        <div>
          <label for="loan_number" class="block font-bold mb-2">
            Número del Préstamo <span class="text-red-500">*</span>
          </label>
          <InputText id="loan_number" v-model.trim="factura.loan_number" maxlength="50" class="w-full"
            :class="{ 'p-invalid': submitted && (!factura.loan_number || errors.loan_number) }" />
          <small v-if="submitted && (!factura.loan_number || errors.loan_number)" class="text-red-500">
            {{ errors.loan_number || 'El número del préstamo es obligatorio.' }}
          </small>
        </div>
      </div>

      <!-- Grid para Número de Factura y RUC del Cliente (2 columnas) -->
      <div class="grid grid-cols-2 gap-4">
        <!-- Número de la Factura -->
        <div>
          <label for="invoice_number" class="block font-bold mb-2">
            Número de la Factura <span class="text-red-500">*</span>
          </label>
          <InputText id="invoice_number" v-model.trim="factura.invoice_number" maxlength="50" class="w-full"
            :class="{ 'p-invalid': submitted && (!factura.invoice_number || errors.invoice_number) }" />
          <small v-if="submitted && (!factura.invoice_number || errors.invoice_number)" class="text-red-500">
            {{ errors.invoice_number || 'El número de la factura es obligatorio.' }}
          </small>
        </div>

        <!-- RUC del Cliente con botón de consulta -->
        <div>
          <label for="RUC_client" class="block font-bold mb-2">
            RUC del Cliente <span class="text-red-500">*</span>
          </label>
          <div class="flex gap-2">
            <InputText id="RUC_client" v-model.trim="factura.RUC_client" maxlength="11" placeholder="11 dígitos"
              class="flex-1"
              :class="{ 'p-invalid': submitted && (!factura.RUC_client || errors.RUC_client || (factura.RUC_client && !/^[0-9]{11}$/.test(factura.RUC_client))) }"
              @input="onRucChange" />
            <Button icon="pi pi-search" severity="secondary" :disabled="!isValidRuc || consultingRuc" 
              :loading="consultingRuc" @click="consultarRuc" />
          </div>
          <small v-if="submitted && (!factura.RUC_client || errors.RUC_client)" class="text-red-500">
            {{ errors.RUC_client || 'El RUC del cliente es obligatorio.' }}
          </small>
          <small v-else-if="submitted && factura.RUC_client && !/^[0-9]{11}$/.test(factura.RUC_client)"
            class="text-red-500">
            El RUC del cliente debe tener exactamente 11 dígitos.
          </small>
        </div>
      </div>

      <!-- Mensajes de consulta RUC -->
      <Message v-if="rucData.razonSocial" severity="info" class="mt-2">
        <div class="flex flex-col gap-1">
          <div><b>Razón Social:</b> {{ rucData.razonSocial }}</div>
          <div><b>Estado: </b> 
            <span :class="rucData.estado === 'ACTIVO' ? 'font-semibold' : 'text-red-600 font-semibold'">
              {{ rucData.estado }}
            </span>
          </div>
        </div>
      </Message>
      
      <Message v-if="rucError" severity="error" class="mt-2">
        {{ rucError }}
      </Message>
    </div>

    <template #footer>
      <div class="flex justify-between items-center w-full">
        <small class="italic text-sm">
          Los campos marcados con <span class="text-red-500">*</span> son obligatorios.
        </small>
        <div class="flex gap-2">
          <Button label="Cancelar" icon="pi pi-times" text severity="secondary" @click="hideDialog" />
          <Button label="Guardar" icon="pi pi-check" severity="contrast" :loading="loading" @click="guardarFactura" />
        </div>
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed } from 'vue';
import Toolbar from 'primevue/toolbar';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import { defineEmits } from 'vue';
import axios from 'axios';
import Select from 'primevue/select';
import { debounce } from 'lodash'
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast'
import DatePicker from 'primevue/datepicker';
import Message from 'primevue/message';

const emit = defineEmits(['agregado']);

const facturaDialog = ref(false);
const submitted = ref(false);
const loading = ref(false);
const errors = ref({});
const inversionistaGuardado = ref(false)
const clienteGuardado = ref(false)
const empresaSeleccionada = ref(null)
const toast = useToast()
const empresas = ref([])
const consultingRuc = ref(false)
const rucData = ref({})
const rucError = ref('')

const monedas = ref([
  { name: 'Soles (PEN)', code: 'PEN' },
  { name: 'Dólares (USD)', code: 'USD' }
]);

const riesgos = [
  { label: 'A', value: 0 },
  { label: 'B', value: 1 },
  { label: 'C', value: 2 },
  { label: 'D', value: 3 },
  { label: 'E', value: 4 }
];

const factura = ref({
  company_id: null,
  currency: 'PEN', // Valor por defecto
  amount: null,
  financed_amount_by_garantia: null,
  rate: null,
  estimated_pay_date: null,
  loan_number: '',
  invoice_number: '',
  RUC_client: ''
});

// Computed para validar RUC
const isValidRuc = computed(() => {
  return factura.value.RUC_client && /^[0-9]{11}$/.test(factura.value.RUC_client);
});

// Computed para el locale de moneda
const currencyLocale = computed(() => {
  return factura.value.currency === 'USD' ? 'en-US' : 'es-PE';
});

// Computed para fecha mínima (hoy + 25 días)
const minDate = computed(() => {
  const today = new Date();
  const minDate = new Date(today);
  minDate.setDate(today.getDate() + 25);
  return minDate;
});

const camposGeneralesBloqueados = computed(() => {
  return inversionistaGuardado.value || clienteGuardado.value
})

function resetFactura() {
  factura.value = {
    company_id: null,
    currency: 'PEN',
    amount: null,
    financed_amount_by_garantia: null,
    rate: null,
    estimated_pay_date: null,
    loan_number: '',
    invoice_number: '',
    RUC_client: ''
  };
  empresas.value = [];
  submitted.value = false;
  loading.value = false;
  errors.value = {};
  consultingRuc.value = false;
  rucData.value = {};
  rucError.value = '';
}

const onEmpresaSelected = (valor) => {
  // Si es un string (usuario escribiendo), buscar empresas
  if (typeof valor === 'string' && !camposGeneralesBloqueados.value) {
    buscarEmpresas(valor);
    return;
  }

  // Si es un número/ID (selección de empresa), asignar
  if (valor) {
    factura.value.company_id = valor;
    // Limpiar error si se selecciona una empresa válida
    if (errors.value.company_id) {
      delete errors.value.company_id;
    }
  } else {
    // Si se limpia la selección
    factura.value.company_id = null;
    empresas.value = [];
  }
}

function openNew() {
  resetFactura();
  facturaDialog.value = true;
}

function hideDialog() {
  facturaDialog.value = false;
  resetFactura();
}

function isFormValid() {
  const requiredFields = [
    'company_id',
    'currency',
    'amount',
    'financed_amount_by_garantia',
    'rate',
    'estimated_pay_date',
    'loan_number',
    'invoice_number',
    'RUC_client'
  ];

  // Verificar campos requeridos
  for (let field of requiredFields) {
    if (!factura.value[field]) return false;
  }

  // Validaciones adicionales según las reglas del backend
  if (factura.value.amount <= 0) return false;
  if (factura.value.financed_amount_by_garantia <= 0) return false;
  if (factura.value.financed_amount_by_garantia > factura.value.amount) return false;
  if (factura.value.rate <= 0 || factura.value.rate > 6) return false;

  // Validar moneda
  if (!['PEN', 'USD'].includes(factura.value.currency)) return false;

  // Validar RUC (ahora es obligatorio)
  if (!factura.value.RUC_client || !/^[0-9]{11}$/.test(factura.value.RUC_client)) {
    return false;
  }

  // Validar fecha mínima
  if (factura.value.estimated_pay_date && factura.value.estimated_pay_date < minDate.value) {
    return false;
  }

  return true;
}

async function guardarFactura() {
  submitted.value = true;

  // Limpiar errores previos
  errors.value = {};

  if (!isFormValid()) return;

  loading.value = true;

  try {
    // Preparar datos para envío
    const formData = {
      ...factura.value,
      // Convertir fecha al formato que espera Laravel (YYYY-MM-DD)
      estimated_pay_date: factura.value.estimated_pay_date
        ? formatDateForBackend(factura.value.estimated_pay_date)
        : null,
      // Asegurar que los campos opcionales no sean strings vacías
      loan_number: factura.value.loan_number || null,
      invoice_number: factura.value.invoice_number || null,
      RUC_client: factura.value.RUC_client || null
    };

    // Llamada a la API
    const response = await axios.post('/invoices', formData, {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    });

    console.log('Respuesta del servidor:', response.data);

    loading.value = false;
    hideDialog();
    emit('agregado');
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Factura guardada correctamente',
      life: 3000
    });
  } catch (error) {
    loading.value = false;
    console.error('Error completo:', error);

    if (error.response?.status === 422) {
      // Errores de validación del backend
      errors.value = error.response.data.errors || {};
      console.log('Errores de validación:', errors.value);

      toast.add({
        severity: 'error',
        summary: 'Error de validación',
        detail: 'Revise los campos marcados en rojo',
        life: 4000
      });
    } else if (error.response?.status === 403) {
      console.error('Sin permisos:', error.response.data.message);
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Sin permisos para realizar esta acción',
        life: 3000
      });
    } else {
      console.error('Error al guardar:', error);
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Error al guardar la factura. Intente nuevamente.',
        life: 3000
      });
    }
  }
}

function formatDateForBackend(date) {
  if (!date) return null;
  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

const getEmpresaLabel = (id) => {
  const empresa = empresas.value.find(e => e.value === id);
  return empresa ? empresa.label : (id || '');
}

const getRiesgoSeverity = (riesgoValue) => {
  switch (riesgoValue) {
    case 0: // A
      return 'success'
    case 1: // B
      return 'info'
    case 2: // C
      return 'warn'
    case 3: // D
      return 'danger'
    case 4: // E
      return 'contrast'
    default:
      return 'info'
  }
}

const getRiesgoLabel = (riesgoValue) => {
  const riesgo = riesgos.find(r => r.value === riesgoValue);
  return riesgo ? riesgo.label : riesgoValue;
}

const buscarEmpresas = debounce(async (texto) => {
  if (!texto || texto.length < 2) {
    empresas.value = []
    return
  }

  try {
    const response = await axios.get("/companies/search", {
      params: { search: texto },
    })

    const nuevasEmpresas = response.data.data.map((empresa) => ({
      label: `${empresa.name} - ${empresa.document}`,
      sublabel: `${empresa.business_name} | Año: ${empresa.incorporation_year}`,
      value: empresa.id,
      riesgo: getRiesgoLabel(parseInt(empresa.risk)),
      riesgoValue: parseInt(empresa.risk),
      document: empresa.document,
      business_name: empresa.business_name,
      incorporation_year: empresa.incorporation_year
    }))

    // Si hay una empresa seleccionada, mantenerla en la lista
    if (empresaSeleccionada.value) {
      const empresaSeleccionadaObj = empresas.value.find(e => e.value === empresaSeleccionada.value);
      if (empresaSeleccionadaObj && !nuevasEmpresas.find(e => e.value === empresaSeleccionada.value)) {
        nuevasEmpresas.unshift(empresaSeleccionadaObj);
      }
    }

    empresas.value = nuevasEmpresas;
  } catch (error) {
    console.error('Error al buscar empresas:', error);
    toast.add({
      severity: "error",
      summary: "Error",
      detail: "Error al buscar empresas",
      life: 3000,
    })
  }
}, 500)

const onInputChange = (event) => {
  const texto = event.value || event;
  if (typeof texto === 'string' && !camposGeneralesBloqueados.value) {
    buscarEmpresas(texto);
  }
}

// Función para limpiar datos de RUC cuando cambia
const onRucChange = () => {
  rucData.value = {};
  rucError.value = '';
}

// Función para consultar RUC
const consultarRuc = async () => {
  if (!isValidRuc.value) return;
  
  consultingRuc.value = true;
  rucError.value = '';
  rucData.value = {};
  
  try {
    const response = await axios.get(`/api/consultar-ruc/${factura.value.RUC_client}`);
    
    if (response.data) {
      rucData.value = {
        razonSocial: response.data.razonSocial,
        estado: response.data.estado,
        condicion: response.data.condicion
      };
      
      // Mostrar toast de éxito
      toast.add({
        severity: 'success',
        summary: 'Consulta exitosa',
        detail: `RUC encontrado: ${response.data.razonSocial}`,
        life: 3000
      });
    }
  } catch (error) {
    console.error('Error al consultar RUC:', error);
    
    if (error.response?.status === 404) {
      rucError.value = 'RUC no encontrado en la base de datos de SUNAT';
    } else if (error.response?.status === 400) {
      rucError.value = 'RUC inválido o formato incorrecto';
    } else {
      rucError.value = 'Error al consultar el RUC. Intente nuevamente.';
    }
    
    toast.add({
      severity: 'error',
      summary: 'Error en consulta',
      detail: rucError.value,
      life: 4000
    });
  } finally {
    consultingRuc.value = false;
  }
}
</script>