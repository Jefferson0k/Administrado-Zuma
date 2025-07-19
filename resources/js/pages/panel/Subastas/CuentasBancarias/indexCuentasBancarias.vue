<template>

  <Head title="Reservas" />
  <AppLayout>
    <Toast />
    <div>
      <template v-if="isLoading">
        <Espera />
      </template>

      <template v-else>
        <div class="card">
          <DataTable :value="reservas" :lazy="true" :paginator="true" :rows="meta.per_page" :totalRecords="meta.total"
            :loading="isLoading" :first="(meta.current_page - 1) * meta.per_page" @page="onPage" filterDisplay="menu"
            responsiveLayout="scroll">
            <template #header>
              <div class="flex flex-wrap gap-2 items-center justify-between">
                <h2 class="text-xl font-semibold">Entrada - inversionista</h2>
              </div>
            </template>

            <Column field="id" header="ID" />
            <Column field="nombre" header="Nombre" />
            <Column field="dni" header="DNI" />
            <Column field="telefono" header="Teléfono" />
            <Column field="propiedad" header="Propiedad" />
            <Column field="tea" header="TEA" />
            <Column field="tem" header="TEM" />
            <Column field="riesgo" header="Riesgo" />
            <Column field="plazo" header="Plazo" />
            <Column field="amount" header="Monto" />
            <Column field="status" header="Estado" />

            <Column header="" style="width: 1rem">
              <template #body="{ data }">
                <Button icon="pi pi-ellipsis-v" text rounded severity="secondary" @click="toggleMenu($event, data)"
                  aria-haspopup="true" aria-controls="overlay_menu" v-tooltip.bottom="'Opciones'" />
                <Menu :model="menuItems[data.id]" :popup="true" :ref="setMenuRef(data.id)" />
              </template>
            </Column>
          </DataTable>
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import AppLayout from '@/layout/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Espera from '@/components/Espera.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

const isLoading = ref(true);
const reservas = ref([]);
const meta = ref({
  current_page: 1,
  per_page: 15,
  total: 0,
});

const menuItems = ref<Record<number, any[]>>({});
const menusRefMap = ref<Record<number, any>>({});

const setMenuRef = (id: number) => (el: any) => {
  if (el) menusRefMap.value[id] = el;
};

const cargarReservas = async (page = 1) => {
  isLoading.value = true;
  try {
    const response = await axios.get(`/reservas-propiedades?page=${page}`);
    reservas.value = response.data.data;
    meta.value = response.data.meta;

    reservas.value.forEach((row: any) => {
      menuItems.value[row.id] = [
        {
          label: 'Cancelar',
          icon: 'pi pi-times',
          command: () => cancelarReserva(row)
        }
      ];
    });

  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar', life: 3000 });
  } finally {
    isLoading.value = false;
  }
};

const onPage = (event: any) => {
  const page = event.page + 1;
  cargarReservas(page);
};

const toggleMenu = (event: MouseEvent, row: any) => {
  const menu = menusRefMap.value[row.id];
  if (menu) menu.toggle(event);
};

const cancelarReserva = async (row: any) => {
  try {
    await axios.put(`/reservas-propiedades/${row.id}/cancelar`);
    toast.add({ 
      severity: 'success', 
      summary: 'Cancelado', 
      detail: `Reserva ${row.id} cancelada`, 
      life: 3000 
    });
    cargarReservas(meta.value.current_page);
  } catch (error) {
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: 'No se pudo cancelar', 
      life: 3000 
    });
  }
};


onMounted(() => {
  cargarReservas();
});
</script>
