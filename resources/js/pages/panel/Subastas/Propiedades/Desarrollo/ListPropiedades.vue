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

const sortField = ref(null);
const sortOrder = ref(null);

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

const getEstadoSeverity = (estado) => {
  switch (estado?.toLowerCase()) {
    case 'pendiente': return 'warn';
    case 'aprobada': return 'success';
    case 'rechazada': return 'danger';
    default: return 'secondary';
  }
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
    { label: 'Editar', icon: 'pi pi-pencil', command: () => onEditar(data) },
    { label: 'Eliminar', icon: 'pi pi-trash', command: () => onEliminar(data) },
    { label: 'Copiar ID', icon: 'pi pi-copy', command: () => copiarId(data.id) }
  ];
  menu.value.show(event);
};
</script>

<template>
  <DataTable
    ref="dt"
    v-model:selection="selectedProducts"
    :value="products"
    dataKey="id"
    :paginator="true"
    :rows="perPage"
    :first="(currentPage - 1) * perPage"
    :totalRecords="totalRecords"
    :loading="loading"
    lazy
    @page="onPage"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    :rowsPerPageOptions="[10, 15, 25]"
    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} solicitudes"
    class="p-datatable-sm"
    :sortField="sortField"
    :sortOrder="sortOrder"
    sortMode="single"
    @sort="onSort"
  >
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

          <Select
            v-model="selectedEstado"
            :options="selectedOpcions"
            optionLabel="name"
            placeholder="Estado"
            class="w-full md:w-auto"
            showClear
          />

          <Button 
            icon="pi pi-refresh" 
            outlined 
            rounded 
            aria-label="Refresh" 
            @click="loadData" 
          />
        </div>
      </div>
    </template>

    <Column selectionMode="multiple" style="width: 3rem" :exportable="false" />
    
    <Column field="codigo" header="Código" sortable style="min-width: 8rem" />
    
    <Column field="investor" header="Inversionista" sortable style="min-width: 18rem" />
    
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

    <Column field="valor_general" header="Valor Estimado" sortable style="min-width: 12rem">
      <template #body="slotProps">
        <span>{{ formatCurrency(slotProps.data.valor_general, slotProps.data.currency) }}</span>
      </template>
    </Column>

    <Column field="valor_requerido" header="Valor Requerido" sortable style="min-width: 12rem">
      <template #body="slotProps">
        <span>{{ formatCurrency(slotProps.data.valor_requerido, slotProps.data.currency) }}</span>
      </template>
    </Column>

    <Column field="estado_nombre" header="Estado" style="min-width: 10rem" sortable>
      <template #body="slotProps">
        <Tag 
          :value="slotProps.data.estado_nombre" 
          :severity="getEstadoSeverity(slotProps.data.estado_nombre)" 
        />
      </template>
    </Column>

    <Column field="created_at" header="Fecha Creación" sortable style="min-width: 12rem" />

    <Column header="" style="width: 4rem">
      <template #body="slotProps">
        <Button
          icon="pi pi-ellipsis-v"
          text
          rounded
          aria-label="Más opciones"
          @click="showContextMenu($event, slotProps.data)"
        />
      </template>
    </Column>
  </DataTable>

  <Menu ref="menu" :model="menuItems" popup />

  <ConfigPropiedades 
    v-model:visible="showModal" 
    :idPropiedad="selectedId" 
    @configuracion-guardada="loadData" 
  />
  
  <UpdatePropiedades 
    v-model:visible="showUpdateModal" 
    :idPropiedad="selectedId" 
    @propiedad-actualizada="onPropiedadActualizada" 
  />
  
  <DeletePropiedades 
    v-model:visible="showDeleteModal" 
    :idPropiedad="selectedId" 
    @propiedad-eliminada="onPropiedadEliminada" 
  />
</template>