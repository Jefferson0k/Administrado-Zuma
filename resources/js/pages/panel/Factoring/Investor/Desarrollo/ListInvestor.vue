<template>
  <DataTable
    ref="dt"
    :value="investors"
    dataKey="id"
    v-model:selection="selectedInvestors"
    :paginator="true"
    :rows="rowsPerPage"
    :totalRecords="totalRecords"
    :first="(currentPage - 1) * rowsPerPage"
    :loading="loading"
    :rowsPerPageOptions="[5, 10, 20, 50]"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} inversionistas"
    @page="onPage"
    scrollable
    scrollHeight="574px"
    class="p-datatable-sm"
  >
    <!-- Header -->
    <template #header>
      <div class="flex flex-wrap gap-2 items-center justify-between">
        <h4 class="m-0">
          Inversionistas
          <Tag severity="contrast" :value="totalRecords" />
        </h4>
        <div class="flex flex-wrap gap-2">
          <IconField>
            <InputIcon>
              <i class="pi pi-search" />
            </InputIcon>
            <InputText
              v-model="globalFilter"
              @input="onGlobalSearch"
              placeholder="Buscar por nombre, alias, email o estado..."
            />
          </IconField>
          <Button
            icon="pi pi-refresh"
            outlined
            rounded
            aria-label="Refresh"
            severity="contrast"
            @click="loadInvestors"
          />
        </div>
      </div>
    </template>

    <!-- Columns -->
    <Column selectionMode="multiple" style="width: 1rem" />
    <Column field="name" header="Nombre" sortable style="min-width: 25rem" />
    <Column field="document" header="Documento" sortable style="min-width: 7rem" />
    <Column field="alias" header="Alias" sortable style="min-width: 10rem" />
    <Column field="telephone" header="Teléfono" sortable style="min-width: 7rem" />
    <Column field="email" header="Email" sortable style="min-width: 18rem" />
    <!-- Validación 1 -->
    <Column field="approval1_status" header="1ª Aprobador" sortable style="min-width: 9rem">
      <template #body="slotProps">
        <Tag
          v-if="slotProps.data.approval1_status"
          :value="getStatusLabel(slotProps.data.approval1_status)"
          :severity="getStatusSeverity(slotProps.data.approval1_status)"
        />
        <span v-else class="italic">Sin estado</span>
      </template>
    </Column>

    <Column field="approval1_by" header="1ª User Aprobador" sortable style="min-width: 15rem">
      <template #body="slotProps">
        <span :class="!slotProps.data.approval1_by ? 'italic' : ''">
          {{ slotProps.data.approval1_by || 'Sin usuario' }}
        </span>
      </template>
    </Column>

    <Column field="approval1_at" header="T. 1ª Aprobador" sortable style="min-width: 13rem">
      <template #body="slotProps">
        <span :class="!slotProps.data.approval1_at ? 'italic' : ''">
          {{ slotProps.data.approval1_at || 'Sin fecha' }}
        </span>
      </template>
    </Column>

    <!-- Validación 2 -->
    <Column field="approval2_status" header="2ª Aprobado" sortable style="min-width: 9rem">
      <template #body="slotProps">
        <Tag
          v-if="slotProps.data.approval2_status"
          :value="getStatusLabel(slotProps.data.approval2_status)"
          :severity="getStatusSeverity(slotProps.data.approval2_status)"
        />
        <span v-else class="italic">Sin estado</span>
      </template>
    </Column>

    <Column field="approval2_by" header="2º User Aprob" sortable style="min-width: 15rem">
      <template #body="slotProps">
        <span :class="!slotProps.data.approval2_by ? 'italic' : ''">
          {{ slotProps.data.approval2_by || 'Sin usuario' }}
        </span>
      </template>
    </Column>

    <Column field="approval2_at" header="T. 2ª Aprobación" sortable style="min-width: 12rem">
      <template #body="slotProps">
        <span :class="!slotProps.data.approval2_at ? 'italic' : ''">
          {{ slotProps.data.approval2_at || 'Sin fecha' }}
        </span>
      </template>
    </Column>
    <!-- Estado principal -->
    <Column field="status" header="Estado Conclusion" sortable style="min-width: 11rem">
      <template #body="slotProps">
        <Tag
          v-if="slotProps.data.status"
          :value="getStatusLabel(slotProps.data.status)"
          :severity="getStatusSeverity(slotProps.data.status)"
        />
        <span v-else class="italic">Sin estado</span>
      </template>
    </Column>

    <!-- Creación -->
    <Column field="creacion" header="Creación" sortable style="min-width: 12rem"></Column>

    <!-- Botón de detalle -->
    <Column header="">
      <template #body="{ data }">
        <Button
          icon="pi pi-eye"
          text
          rounded
          @click="viewInvestorDetail(data)"
          aria-label="Ver detalle"
          title="Ver detalle"
        />
      </template>
    </Column>
  </DataTable>

  <!-- Modal de detalle -->
  <showInvertor
    v-if="selectedInvestorForDetail"
    :investor="selectedInvestorForDetail"
    :visible="showDetailDialog"
    @update:visible="showDetailDialog = $event"
    @status-updated="handleStatusUpdate"
  />
</template>

<script setup lang="ts">
import axios from 'axios';
import { debounce } from 'lodash';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { onMounted, ref } from 'vue';
import showInvertor from './showInvertor.vue';

const toast = useToast();
const investors = ref<any[]>([]);
const selectedInvestors = ref<any[]>([]);
const loading = ref(false);
const totalRecords = ref(0);
const globalFilter = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);

const showDetailDialog = ref(false);
const selectedInvestorForDetail = ref<any>(null);

const loadInvestors = async (event: any = {}) => {
    loading.value = true;
    const page = event.page != null ? event.page + 1 : currentPage.value;
    const perPage = event.rows != null ? Number(event.rows) : rowsPerPage.value;

  try {
    const response = await axios.get('/investor', {
      params: { search: globalFilter.value, page, perPage }
    });
    investors.value = response.data.data;
    totalRecords.value = response.data.total;
    currentPage.value = page;
    rowsPerPage.value = perPage;
  } catch (error) {
    console.error('Error al cargar inversionistas:', error);
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar los inversionistas' });
  } finally {
    loading.value = false;
  }
};

const onGlobalSearch = debounce(() => {
    currentPage.value = 1;
    loadInvestors();
}, 500);

const onPage = (event: any) => {
    loadInvestors(event);
};

const getStatusLabel = (status: string) => {
  const normalized = status?.toLowerCase();

  switch (normalized) {
    case 'validated':
    case 'approved':
      return 'Aprobado';
    case 'observed':
      return 'Observado';
    case 'rejected':
      return 'Rechazado';
    case 'no validado':
    case 'pending':
      return 'Pendiente';
    default:
      return status ?? '—';
  }
};

const getStatusSeverity = (status: string) => {
  const normalized = status?.toLowerCase();

  switch (normalized) {
    case 'validated':
    case 'approved':
      return 'success';
    case 'observed':
      return 'warn';
    case 'rejected':
      return 'danger';
    case 'no validado':
    case 'pending':
      return 'contrast';
    default:
      return 'success';
  }
};

const viewInvestorDetail = async (investor: any) => {
    try {
        const response = await axios.get(`/investor/${investor.id}`);
        selectedInvestorForDetail.value = response.data.data;
        showDetailDialog.value = true;
    } catch (error) {
        console.error('Error al obtener detalles del inversionista:', error);
        selectedInvestorForDetail.value = investor;
        showDetailDialog.value = true;
    }
};

const handleStatusUpdate = (updatedInvestor: any) => {
  console.log('Inversionista actualizado:', updatedInvestor);

  // Actualizar en la lista
  const index = investors.value.findIndex(inv => inv.id === updatedInvestor.id);
  if (index !== -1) investors.value[index] = updatedInvestor;

  // Actualizar en el detalle
  if (selectedInvestorForDetail.value?.id === updatedInvestor.id) {
    selectedInvestorForDetail.value = updatedInvestor;
  }

  showDetailDialog.value = false;
};

onMounted(() => {
    loadInvestors();
});
</script>

