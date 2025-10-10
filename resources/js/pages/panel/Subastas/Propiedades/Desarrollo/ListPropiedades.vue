<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Button from 'primevue/button';
import Select from 'primevue/select';
import Menu from 'primevue/menu';
import ConfigPropiedades from './ConfigPropiedades.vue';
import UpdatePropiedades from './UpdatePropiedades.vue';
import DeletePropiedades from './DeletePropiedades.vue';
import Tag from 'primevue/tag';
import AprobarProperty from './AprobarProperty.vue';
import HistoryAprobar from './HistoryAprobar.vue';
const props = defineProps({
  refresh: { type: Number, default: 0 }
});

const toast = useToast();
const dt = ref();
const products = ref([]);
const selectedProducts = ref([]);
const loading = ref(false);
const totalRecords = ref(0);
const currentPage = ref(1);
const perPage = ref(10);
const search = ref('');
const showModal = ref(false);
const showUpdateModal = ref(false);
const showDeleteModal = ref(false);
const selectedId = ref(null);
const menu = ref();
const menuItems = ref([]);
const metaKey = ref(true);
const sortField = ref(null);
const sortOrder = ref(null);
const showAprobarModal = ref(false);
const showHistorialModal = ref(false);

const selectedEstado = ref(null);
const selectedOpcions = ref([
  { name: 'Pendiente', value: 'pendiente' },
  { name: 'Aprobada', value: 'aprobada' },
  { name: 'Rechazada', value: 'rechazada' },
]);

let searchTimeout;

const loadData = async () => {
  loading.value = true;
  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
      search: search.value,
      estado: selectedEstado.value?.value || null,
    };

    if (sortField.value) {
      params.sort_field = sortField.value;
      params.sort_order = sortOrder.value === 1 ? 'asc' : 'desc';
    }

    const { data } = await axios.get('/property', { params });

    products.value = data.data ?? [];
    totalRecords.value = data.meta?.total ?? data.total ?? 0;

  } catch (error) {
    console.error('Error al cargar solicitudes:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudieron cargar las solicitudes',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
};

onMounted(loadData);
watch(() => props.refresh, loadData);

watch([search, perPage, selectedEstado], () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    currentPage.value = 1;
    loadData();
  }, 500);
});

const onPage = (event) => {
  currentPage.value = event.page + 1;
  perPage.value = event.rows;
  loadData();
};

const onSort = (event) => {
  sortField.value = event.sortField ?? null;
  sortOrder.value = typeof event.sortOrder === 'number' ? event.sortOrder : 1;
  currentPage.value = 1;
  loadData();
};

const onAprobar = (data) => {
  selectedId.value = data.id;
  showAprobarModal.value = true;
};

const onHistorial = (data) => {
  selectedId.value = data.id;
  showHistorialModal.value = true;
};

const onEditar = (data) => {
  selectedId.value = data.id;
  showUpdateModal.value = true;
};

const onEliminar = (data) => {
  selectedId.value = data.id;
  showDeleteModal.value = true;
};

const formatCurrency = (value, currency = 'USD') => {
  if (!value && value !== 0) return '-';
  if (Number(value) === 0) return '-';
  return new Intl.NumberFormat('es-PE', {
    style: 'currency',
    currency,
    minimumFractionDigits: 2
  }).format(value);
};

const getEstadoConclusionLabel = (estado) => {
  const labels = {
    'en_subasta': 'En Subasta',
    'subastada': 'Subastada',
    'programada': 'Programada',
    'desactivada': 'Desactivada',
    'activa': 'Activa',
    'adquirido': 'Adquirido',
    'pendiente': 'Pendiente',
    'completo': 'Completo',
    'espera': 'En Espera',
    'rejected': 'Rechazado',
    'observed': 'Observado'
  };
  return labels[estado] || estado;
};

const getEstadoSeverity = (estado) => {
  switch (estado?.toLowerCase()) {
    case 'completo':
    case 'adquirido':
    case 'activa': 
      return 'success';
    
    case 'pendiente':
    case 'espera':
    case 'programada':
    case 'observed':
      return 'warn';
    
    case 'rejected':
    case 'desactivada':
      return 'danger';
    
    case 'en_subasta':
    case 'subastada':
      return 'info';
    
    default: 
      return 'secondary';
  }
};

const getApprovalStatusLabel = (status) => {
  const labels = {
    'approved': 'Aprobado',
    'rejected': 'Rechazado',
    'observed': 'Observado',
  };
  return labels[status] || 'Pendiente';
};

const getApprovalStatusSeverity = (status) => {
  const severities = {
    'approved': 'success',
    'rejected': 'danger',
    'observed': 'warn',
  };
  return severities[status] || 'secondary';
};

const onPropiedadActualizada = () => loadData();
const onPropiedadEliminada = () => loadData();

const copiarId = async (id) => {
  try {
    await navigator.clipboard.writeText(id);
    toast.add({
      severity: 'success',
      summary: 'ID copiado',
      detail: `ID ${id} copiado al portapapeles`,
      life: 3000
    });
  } catch {
    toast.add({
      severity: 'error',
      summary: 'Error al copiar',
      detail: 'No se pudo copiar el ID',
      life: 3000
    });
  }
};

const showContextMenu = (event, data) => {
  menuItems.value = [
    { label: 'Aprobar/Revisar', icon: 'pi pi-check-circle', command: () => onAprobar(data) },
    { label: 'Historial', icon: 'pi pi-history', command: () => onHistorial(data) },
    { separator: true },
    { label: 'Editar', icon: 'pi pi-pencil', command: () => onEditar(data) },
    { label: 'Eliminar', icon: 'pi pi-trash', command: () => onEliminar(data) },
    { label: 'Copiar ID', icon: 'pi pi-copy', command: () => copiarId(data.id) }
  ];
  menu.value.show(event);
};

const onSolicitudProcesada = () => loadData();
</script>

<template>
  <DataTable ref="dt" v-model:selection="selectedProducts" :value="products" dataKey="id" :paginator="true"
    :rows="perPage" :first="(currentPage - 1) * perPage" :totalRecords="totalRecords" :loading="loading" lazy
    @page="onPage" selectionMode="multiple" :metaKeySelection="metaKey"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    :rowsPerPageOptions="[10, 15, 25]"
    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} solicitudes" class="p-datatable-sm"
    :sortField="sortField" :sortOrder="sortOrder" sortMode="single" @sort="onSort">
    <template #header>
      <div class="flex flex-wrap gap-2 items-center justify-between">
        <div class="flex items-center gap-2">
          <h4 class="m-0">Solicitudes</h4>
        </div>

        <div class="flex flex-wrap gap-2">
          <IconField>
            <InputIcon>
              <i class="pi pi-search" />
            </InputIcon>
            <InputText v-model="search" placeholder="Buscar..." />
          </IconField>

          <Select v-model="selectedEstado" :options="selectedOpcions" optionLabel="name" placeholder="Estado"
            class="w-full md:w-auto" showClear />

          <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" @click="loadData" />
        </div>
      </div>
    </template>


    <Column field="codigo" header="Código" sortable style="min-width: 8rem" />

    <Column field="investor" header="Inversionista" sortable style="min-width: 25rem" />

    <Column field="document" header="DNI" sortable style="min-width: 7rem" />

    <Column field="propiedades_count" header="Propiedades" sortable style="min-width: 8rem">
      <template #body="slotProps">
        <span>{{ slotProps.data.propiedades_count || 0 }}</span>
      </template>
    </Column>

    <Column field="currency" header="Moneda" sortable style="min-width: 7rem">
      <template #body="slotProps">
        <span>{{ slotProps.data.currency || '-' }}</span>
      </template>
    </Column>

    <Column field="valor_general" header="Valor Estimado" sortable style="min-width: 10rem">
      <template #body="slotProps">
        <span>{{ formatCurrency(slotProps.data.valor_general, slotProps.data.currency) }}</span>
      </template>
    </Column>

    <Column field="valor_requerido" header="Valor Requerido" sortable style="min-width: 10rem">
      <template #body="slotProps">
        <span>{{ formatCurrency(slotProps.data.valor_requerido, slotProps.data.currency) }}</span>
      </template>
    </Column>

    <Column field="approval1_status" header="1ª Aprobador" style="min-width: 10rem" sortable>
      <template #body="slotProps">
        <Tag 
          :value="getApprovalStatusLabel(slotProps.data.approval1_status)" 
          :severity="getApprovalStatusSeverity(slotProps.data.approval1_status)" 
        />
      </template>
    </Column>
    
    <Column field="approval1_by" header="1ª Usuario" style="min-width: 25rem" sortable></Column>

    <Column field="approval1_at" header="T. 1ª Aprobación" style="min-width: 13rem" sortable></Column>
    
    <Column field="estado_nombre" header="Estado Conclusión" style="min-width: 11rem" sortable>
      <template #body="slotProps">
        <Tag 
          :value="getEstadoConclusionLabel(slotProps.data.estado_nombre)" 
          :severity="getEstadoSeverity(slotProps.data.estado_nombre)" 
        />
      </template>
    </Column>

    <Column field="created_at" header="Fecha Creación" sortable style="min-width: 12rem" />

    <Column header="" style="width: 4rem">
      <template #body="slotProps">
        <Button icon="pi pi-ellipsis-v" text rounded aria-label="Más opciones"
          @click="showContextMenu($event, slotProps.data)" />
      </template>
    </Column>
  </DataTable>

  <Menu ref="menu" :model="menuItems" popup />
  
  <AprobarProperty 
      v-model:visible="showAprobarModal" 
      :idPropiedad="selectedId"
      @solicitud-procesada="onSolicitudProcesada" 
    />
  <HistoryAprobar 
    v-model:visible="showHistorialModal" 
    :idPropiedad="selectedId"
  />
  <ConfigPropiedades v-model:visible="showModal" :idPropiedad="selectedId" @configuracion-guardada="loadData" />

  <UpdatePropiedades v-model:visible="showUpdateModal" :idPropiedad="selectedId"
    @propiedad-actualizada="onPropiedadActualizada" />

  <DeletePropiedades v-model:visible="showDeleteModal" :idPropiedad="selectedId"
    @propiedad-eliminada="onPropiedadEliminada" />
</template>