<template>
  <Head title="Empresa" />
  <AppLayout>
    <div>
      <template v-if="isLoading">
        <Espera />
      </template>
      <template v-else>
        <div class="card">
          <addCompany 
            @agregado="refrescarListado"
            @export="handleExport"
          />
          <listCompany ref="listCompanyRef" :refresh="refreshKey"/>
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AppLayout from '@/layout/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Espera from '@/components/Espera.vue';
import listCompany from './Desarrollo/listCompany.vue';
import addCompany from './Desarrollo/addCompany.vue';

const isLoading = ref(true);
const refreshKey = ref(0);

const listCompanyRef = ref(null);

function refrescarListado() {
  refreshKey.value++;
}

function handleExport() {
  if (listCompanyRef.value) {
    listCompanyRef.value.exportCSV();
  }
}

onMounted(() => {
  setTimeout(() => {
    isLoading.value = false;
  }, 1000);
});
</script>
