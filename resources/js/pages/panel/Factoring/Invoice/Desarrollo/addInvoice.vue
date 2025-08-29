<template>
  <Toolbar class="mb-6">
    <template #start>
      <Button label="Nueva Factura" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" />
    </template>
  </Toolbar>

  <Dialog v-model:visible="facturaDialog" :style="{ width: '900px' }" header="Registro de facturas" :modal="true">
    <div class="flex flex-col gap-6">
      <div class="grid grid-cols-12 gap-4">
        <!-- Empresa -->
        <div class="col-span-6">
          <label for="company_id" class="block font-bold mb-2">
            Empresa <span class="text-red-500">*</span>
          </label>
          <Dropdown id="company_id" v-model="factura.company_id" :options="empresas" 
            optionLabel="name" optionValue="id" placeholder="Seleccionar empresa" fluid
            :class="{ 'p-invalid': submitted && !factura.company_id }" />
          <small v-if="submitted && !factura.company_id" class="text-red-500">
            {{ errors.company_id || 'Debe seleccionar una empresa.' }}
          </small>
        </div>

        <!-- Moneda -->
        <div class="col-span-6">
          <label for="currency" class="block font-bold mb-2">
            Moneda <span class="text-red-500">*</span>
          </label>
          <Dropdown id="currency" v-model="factura.currency" :options="monedas" 
            optionLabel="name" optionValue="code" placeholder="Seleccionar moneda" fluid
            :class="{ 'p-invalid': submitted && !factura.currency }" />
          <small v-if="submitted && !factura.currency" class="text-red-500">
            {{ errors.currency || 'La moneda es obligatoria.' }}
          </small>
        </div>

        <!-- Monto Factura -->
        <div class="col-span-6">
          <label for="amount" class="block font-bold mb-2">
            Monto Factura <span class="text-red-500">*</span>
          </label>
          <InputNumber id="amount" v-model="factura.amount" mode="decimal" 
            :minFractionDigits="2" :maxFractionDigits="2" :min="0.01" fluid
            :class="{ 'p-invalid': submitted && (!factura.amount || factura.amount <= 0) }" />
          <small v-if="submitted && (!factura.amount || factura.amount <= 0)" class="text-red-500">
            {{ errors.amount || 'El monto de factura debe ser mayor a 0.' }}
          </small>
        </div>

        <!-- Monto Financiado por Garantía -->
        <div class="col-span-6">
          <label for="financed_amount_by_garantia" class="block font-bold mb-2">
            Monto Financiado por Garantía <span class="text-red-500">*</span>
          </label>
          <InputNumber id="financed_amount_by_garantia" v-model="factura.financed_amount_by_garantia" 
            mode="decimal" :minFractionDigits="2" :maxFractionDigits="2" :min="0.01" 
            :max="factura.amount || undefined" fluid
            :class="{ 'p-invalid': submitted && (!factura.financed_amount_by_garantia || factura.financed_amount_by_garantia <= 0 || (factura.amount && factura.financed_amount_by_garantia > factura.amount)) }" />
          <small v-if="submitted && (!factura.financed_amount_by_garantia || factura.financed_amount_by_garantia <= 0)" class="text-red-500">
            {{ errors.financed_amount_by_garantia || 'El monto financiado por garantía es obligatorio.' }}
          </small>
          <small v-else-if="submitted && factura.amount && factura.financed_amount_by_garantia > factura.amount" class="text-red-500">
            El monto financiado no puede ser mayor al monto de la factura.
          </small>
        </div>

        <!-- Tasa % -->
        <div class="col-span-6">
          <label for="rate" class="block font-bold mb-2">
            Tasa % <span class="text-red-500">*</span>
          </label>
          <InputNumber id="rate" v-model="factura.rate" mode="decimal" 
            :minFractionDigits="2" :maxFractionDigits="4" :min="0.01" :max="6" suffix="%" fluid
            :class="{ 'p-invalid': submitted && (!factura.rate || factura.rate <= 0 || factura.rate > 6) }" />
          <small v-if="submitted && (!factura.rate || factura.rate <= 0)" class="text-red-500">
            {{ errors.rate || 'La tasa es obligatoria y debe ser mayor a 0.' }}
          </small>
          <small v-else-if="submitted && factura.rate > 6" class="text-red-500">
            La tasa no puede superar el 6%.
          </small>
        </div>

        <!-- Fecha Estimada de Pago -->
        <div class="col-span-6">
          <label for="estimated_pay_date" class="block font-bold mb-2">
            Fecha Estimada de Pago <span class="text-red-500">*</span>
          </label>
          <Calendar id="estimated_pay_date" v-model="factura.estimated_pay_date" dateFormat="dd/mm/yy" fluid
            :class="{ 'p-invalid': submitted && !factura.estimated_pay_date }" />
          <small v-if="submitted && !factura.estimated_pay_date" class="text-red-500">
            {{ errors.estimated_pay_date || 'La fecha estimada de pago es obligatoria.' }}
          </small>
        </div>

        <!-- Número del Préstamo -->
        <div class="col-span-6">
          <label for="loan_number" class="block font-bold mb-2">
            Número del Préstamo
          </label>
          <InputText id="loan_number" v-model.trim="factura.loan_number" maxlength="50" fluid />
          <small v-if="errors.loan_number" class="text-red-500">
            {{ errors.loan_number }}
          </small>
        </div>

        <!-- Número de la Factura -->
        <div class="col-span-6">
          <label for="invoice_number" class="block font-bold mb-2">
            Número de la Factura
          </label>
          <InputText id="invoice_number" v-model.trim="factura.invoice_number" maxlength="50" fluid />
          <small v-if="errors.invoice_number" class="text-red-500">
            {{ errors.invoice_number }}
          </small>
        </div>

        <!-- RUC del Cliente -->
        <div class="col-span-12">
          <label for="RUC_client" class="block font-bold mb-2">
            RUC del Cliente
          </label>
          <InputText id="RUC_client" v-model.trim="factura.RUC_client" maxlength="11" 
            placeholder="11 dígitos" fluid
            :class="{ 'p-invalid': submitted && factura.RUC_client && !/^[0-9]{11}$/.test(factura.RUC_client) }" />
          <small v-if="submitted && factura.RUC_client && !/^[0-9]{11}$/.test(factura.RUC_client)" class="text-red-500">
            {{ errors.RUC_client || 'El RUC del cliente debe tener exactamente 11 dígitos.' }}
          </small>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-between items-center w-full">
        <small class="italic text-sm">
          Los campos marcados con <span class="text-red-500">*</span> son obligatorios.
        </small>
        <div class="flex gap-2">
          <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog" />
          <Button label="Guardar" icon="pi pi-check" :loading="loading"
            :disabled="!isFormValid()" @click="guardarFactura" />
        </div>
      </div>
    </template>
  </Dialog>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import Toolbar from 'primevue/toolbar';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import { defineEmits } from 'vue';
import axios from 'axios';

const emit = defineEmits(['agregado']);

const facturaDialog = ref(false);
const submitted = ref(false);
const loading = ref(false);
const errors = ref({});

// Opciones para dropdowns
const monedas = ref([
  { name: 'Soles (PEN)', code: 'PEN' },
  { name: 'Dólares (USD)', code: 'USD' }
]);

const empresas = ref([]);

const factura = ref({
  company_id: null,
  currency: null,
  amount: null,
  financed_amount_by_garantia: null,
  rate: null,
  estimated_pay_date: null,
  loan_number: '',
  invoice_number: '',
  RUC_client: ''
});

// Cargar empresas al montar el componente
onMounted(async () => {
  try {
    const response = await axios.get('/companies');
    // Soporta /companies que devuelvan {data:[...]} o directamente [...]
    empresas.value = Array.isArray(response.data) ? response.data : (response.data?.data ?? []);
  } catch (error) {
    console.error('Error al cargar empresas:', error);
    // Fallback si no se puede cargar desde la API
    empresas.value = [
      { id: 1, name: 'Empresa 1' },
      { id: 2, name: 'Empresa 2' }
    ];
  }
});

function resetFactura() {
  factura.value = {
    company_id: null,
    currency: null,
    amount: null,
    financed_amount_by_garantia: null,
    rate: null,
    estimated_pay_date: null,
    loan_number: '',
    invoice_number: '',
    RUC_client: ''
  };
  submitted.value = false;
  loading.value = false;
  errors.value = {};
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
  // Validaciones principales requeridas
  const requiredFields = [
    'company_id',
    'currency', 
    'amount',
    'financed_amount_by_garantia',
    'rate',
    'estimated_pay_date'
  ];
  
  // Verificar campos requeridos
  for (let field of requiredFields) {
    if (!factura.value[field]) return false;
  }
  
  // Validaciones adicionales
  if (factura.value.amount <= 0) return false;
  if (factura.value.financed_amount_by_garantia <= 0) return false;
  if (factura.value.financed_amount_by_garantia > factura.value.amount) return false;
  if (factura.value.rate <= 0 || factura.value.rate > 6) return false;
  
  // Validar RUC si está presente
  if (factura.value.RUC_client && !/^[0-9]{11}$/.test(factura.value.RUC_client)) {
    return false;
  }
  
  return true;
}

async function guardarFactura() {
  submitted.value = true;
  if (!isFormValid()) return;

  loading.value = true;
  errors.value = {};
  
  try {
    // Preparar datos para envío
    const formData = {
      ...factura.value,
      // Convertir fecha al formato que espera Laravel (YYYY-MM-DD)
      estimated_pay_date: factura.value.estimated_pay_date
        ? formatDateForBackend(factura.value.estimated_pay_date)
        : null
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
    // Aquí puedes disparar un toast si usas uno
  } catch (error) {
    loading.value = false;
    console.error('Error completo:', error);

    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {};
      console.log('Errores de validación:', errors.value);
    } else if (error.response?.status === 403) {
      console.error('Sin permisos:', error.response.data.message);
    } else {
      console.error('Error al guardar:', error);
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
</script>
