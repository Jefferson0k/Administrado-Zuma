<template>
  <Dialog v-model:visible="visible" :style="{ width: '90vw', maxWidth: '1200px' }" header="Validación de Inversionista"
    :modal="true" :closable="true" @hide="closeDialog">
    <div>
      <!-- Información básica en 4 columnas -->
      <Message severity="info" :closable="false">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <div>
            <span class="text-sm font-medium text-blue-800">Nombre:</span>
            <p class="font-semibold">{{ investor.name }}</p>
          </div>
          <div>
            <span class="text-sm font-medium text-blue-800">DNI:</span>
            <p class="font-semibold">{{ investor.document }}</p>
          </div>
          <div>
            <span class="text-sm font-medium text-blue-800">Estado:</span>
            <Tag :value="getStatusLabel(investor.status)"
                 :severity="getStatusSeverity(investor.status)" class="ml-2" />
          </div>
          <div>
            <span class="text-sm font-medium text-blue-800">Teléfono:</span>
            <p>{{ investor.telephone }}</p>
          </div>
          <div>
            <span class="text-sm font-medium text-blue-800">Email:</span>
            <p>{{ investor.email }}</p>
          </div>
        </div>
      </Message>

      <br>

      <!-- Documentos DNI -->
      <div class="mb-6">
        <h3 class="text-lg font-bold mb-3 text-gray-900 flex items-center gap-2">
          <i class="pi pi-id-card text-blue-600"></i>
          Documentos de Identidad para Validación
        </h3>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <!-- DNI Frontal -->
          <div v-if="investor.document_front" class="border rounded-lg overflow-hidden">
            <div class="bg-gray-100 px-3 py-2 border-b">
              <span class="text-sm font-medium text-gray-900">DNI - Parte Frontal</span>
            </div>
            <div class="p-2">
              <img :src="investor.document_front" alt="DNI Frontal"
                   class="w-full h-48 object-contain cursor-pointer hover:opacity-90 transition-opacity border rounded"
                   @click="viewDocument(investor.document_front)" />
              <Button label="Ver completo" icon="pi pi-eye" size="small" outlined class="w-full mt-2"
                      @click="viewDocument(investor.document_front)" />
            </div>
          </div>

          <!-- DNI Posterior -->
          <div v-if="investor.document_back" class="border rounded-lg overflow-hidden">
            <div class="bg-gray-100 px-3 py-2 border-b">
              <span class="text-sm font-medium text-gray-900">DNI - Parte Posterior</span>
            </div>
            <div class="p-2">
              <img :src="investor.document_back" alt="DNI Posterior"
                   class="w-full h-48 object-contain cursor-pointer hover:opacity-90 transition-opacity border rounded"
                   @click="viewDocument(investor.document_back)" />
              <Button label="Ver completo" icon="pi pi-eye" size="small" outlined class="w-full mt-2"
                      @click="viewDocument(investor.document_back)" />
            </div>
          </div>
        </div>

        <!-- Alerta si no hay documentos -->
        <div v-if="!investor.document_front && !investor.document_back"
             class="text-center py-6 bg-yellow-50 border border-yellow-200 rounded-lg">
          <i class="pi pi-exclamation-triangle text-2xl text-yellow-600 mb-2"></i>
          <p class="text-gray-900 font-medium">No se han subido documentos de identidad</p>
        </div>
      </div>

      <!-- Información adicional colapsable -->
      <details class="mb-4">
        <summary class="cursor-pointer font-medium hover:text-blue-600 transition-colors">
          <i class="pi pi-chevron-right mr-2"></i>
          Ver información adicional
        </summary>
        <div class="mt-3 pl-6 space-y-3">
          <!-- Primera fila: 4 columnas -->
          <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 text-sm">
            <div>
              <span class="font-medium">Perfil:</span>
              <p>{{ investor.perfil || 'No especificado' }}</p>
            </div>
            <div>
              <span class="font-medium">Departamento:</span>
              <p>{{ investor.department || 'No especificado' }}</p>
            </div>
            <div>
              <span class="font-medium">Provincia:</span>
              <p>{{ investor.province || 'No especificada' }}</p>
            </div>
            <div>
              <span class="font-medium">Distrito:</span>
              <p>{{ investor.district || 'No especificado' }}</p>
            </div>
          </div>

          <!-- Segunda fila: 3 columnas -->
          <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 text-sm">
            <div>
              <span class="font-medium">Persona Expuesta:</span>
              <Tag :value="investor.personaexpuesta ? 'Sí' : 'No'"
                   :severity="investor.personaexpuesta ? 'danger' : 'success'" class="ml-2" />
            </div>
            <div>
              <span class="font-medium">Relación Política:</span>
              <Tag :value="investor.relacionPolitica ? 'Sí' : 'No'"
                   :severity="investor.relacionPolitica ? 'danger' : 'success'" class="ml-2" />
            </div>
            <div>
              <span class="font-medium">Email Verificado:</span>
              <p>{{ investor.emailverificacion }}</p>
            </div>
          </div>

          <!-- Fecha -->
          <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 text-sm">
            <div>
              <span class="font-medium">Fecha de Registro:</span>
              <p>{{ investor.creacion }}</p>
            </div>
          </div>
        </div>
      </details>

      <!-- Notas de validación -->
      <div v-if="investor.validation_notes" class="mb-4">
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
          <h4 class="font-medium text-gray-900 mb-2 flex items-center gap-2">
            <i class="pi pi-comment"></i>
            Notas de Validación
          </h4>
          <p>{{ investor.validation_notes }}</p>
        </div>
      </div>
    </div>

    <!-- Footer con botones de acción -->
    <template #footer>
      <div class="flex gap-2">
        <!-- Solo mostrar botones de validación si está pendiente -->
        <template v-if="isStatusPending">
          <Button label="Validar" severity="success" icon="pi pi-check"
                  class="font-semibold" @click="updateStatus('validated')" />
          <Button label="Rechazar" severity="danger" icon="pi pi-times" outlined
                  @click="updateStatus('rejected')" />
        </template>
        
        <!-- Mostrar estado actual si ya está procesado -->
        <div v-else class="flex items-center gap-2">
          <i class="pi pi-info-circle text-blue-500"></i>
          <span class="text-sm text-gray-600">
            Este inversionista ya ha sido {{ getStatusLabel(investor.status).toLowerCase() }}
          </span>
        </div>
      </div>
      <Button label="Cerrar" severity="secondary" text @click="closeDialog" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Message from 'primevue/message';
import axios from 'axios';

interface Investor {
  id: string;
  name: string;
  document: string;
  alias: string;
  telephone: string;
  email: string;
  status: string;
  creacion: string;
  document_front?: string;
  document_back?: string;
  perfil?: string;
  department?: string;
  province?: string;
  district?: string;
  personaexpuesta?: boolean;
  relacionPolitica?: boolean;
  emailverificacion?: string;
  validation_notes?: string;
}

const props = defineProps<{
  investor: Investor;
  visible: boolean;
}>();

const emit = defineEmits<{
  'update:visible': [value: boolean];
  'status-updated': [investor: Investor];
}>();

const visible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
});

// Computed para verificar si el estado está pendiente
const isStatusPending = computed(() => {
  return props.investor.status === 'No validado' || props.investor.status === 'pending';
});

// Funciones de utilidad
const getStatusLabel = (status: string) => {
  switch (status) {
    case 'validated': return 'Validado';
    case 'Validado': return 'Validado';
    case 'No validado': return 'Pendiente';
    case 'pending': return 'Pendiente';
    case 'rejected': return 'Rechazado';
    case 'Rechazado': return 'Rechazado';
    default: return status;
  }
};

const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'validated':
    case 'Validado': 
      return 'success';
    case 'No validado':
    case 'pending': 
      return 'warn';
    case 'rejected':
    case 'Rechazado': 
      return 'danger';
    default: 
      return 'info';
  }
};

const closeDialog = () => {
  visible.value = false;
};

const updateStatus = async (status: string) => {
  try {
    let response;
    
    if (status === 'validated') {
      response = await axios.put(`/investor/${props.investor.id}/aprobar`);
    } else if (status === 'rejected') {
      response = await axios.put(`/investor/${props.investor.id}/rechazar`);
    }
    
    if (response && response.data && response.data.data) {
      // Crear el objeto actualizado preservando la fecha de creación
      const updatedInvestor = {
        ...props.investor, // Mantener todos los datos originales
        ...response.data.data, // Aplicar las actualizaciones del servidor
        creacion: props.investor.creacion // Asegurar que la fecha de creación se mantenga
      };
      
      emit('status-updated', updatedInvestor);
    } else {
      // Si no hay respuesta del servidor, actualizar solo el status localmente
      const updatedInvestor = {
        ...props.investor,
        status: status
      };
      emit('status-updated', updatedInvestor);
    }
    
    closeDialog();
  } catch (error) {
    console.error('Error al actualizar estado:', error);
    
    // En caso de error, intentar actualizar localmente
    const updatedInvestor = {
      ...props.investor,
      status: status
    };
    emit('status-updated', updatedInvestor);
    closeDialog();
  }
};

const viewDocument = (url: string) => {
  window.open(url, '_blank');
};
</script>