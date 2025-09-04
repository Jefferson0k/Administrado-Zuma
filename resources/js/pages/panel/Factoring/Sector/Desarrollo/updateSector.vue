<template>
  <Dialog
    :visible="visible"
    :style="{ width: '600px' }"
    header="Actualizar Sector"
    :modal="true"
    @hide="hideDialog"
  >
    <div class="flex flex-col gap-6">
      <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
          <label for="name" class="block font-bold mb-2">
            Nombre <span class="text-red-500">*</span>
          </label>
          <InputText
            id="name"
            v-model.trim="sector.name"
            maxlength="255"
            fluid
            :class="{ 'p-invalid': submitted && (!sector.name || serverErrors.name) }"
          />
          <small v-if="submitted && !sector.name" class="text-red-500">
            El nombre es obligatorio.
          </small>
          <small v-else-if="serverErrors.name" class="text-red-500">
            {{ serverErrors.name[0] }}
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
          <Button label="Cancelar" icon="pi pi-times" severity="secondary" text @click="hideDialog" />
          <Button
            label="Actualizar"
            icon="pi pi-check"
            severity="contrast"
            :loading="loading"
            :disabled="!sector.name || sector.name.length > 255"
            @click="actualizarSector"
          />
        </div>
      </div>
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import axios from 'axios';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

const props = defineProps({
  tipoClienteId: { type: Number, default: null }, // permite null al inicio
  visible: { type: Boolean, required: true }
});

const emit = defineEmits(['update:visible', 'updated']);

const sector = ref({ name: '' });
const submitted = ref(false);
const loading = ref(false);
const serverErrors = ref({});

// Cargar sector cuando cambia el ID
watch(
  () => props.tipoClienteId,
  async (id) => {
    if (id !== null) {
      loading.value = true;
      try {
        const response = await axios.get(`/sectors/${id}`);
        sector.value = { ...response.data.data }; // <- response.data.data según tu API
      } catch (error) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'No se pudo cargar el sector',
          life: 3000
        });
      } finally {
        loading.value = false;
      }
    } else {
      resetSector();
    }
  },
  { immediate: true }
);

function hideDialog() {
  emit('update:visible', false);
  resetSector();
}

function resetSector() {
  sector.value = { name: '' };
  submitted.value = false;
  serverErrors.value = {};
  loading.value = false;
}

async function actualizarSector() {
  submitted.value = true;
  serverErrors.value = {};
  loading.value = true;
  try {
    await axios.put(`/sectors/${props.tipoClienteId}`, sector.value);
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Sector actualizado correctamente',
      life: 3000
    });
    hideDialog();
    emit('updated'); // notifica al padre para refrescar tabla
  } catch (error) {
    if (error.response && error.response.status === 422) {
      serverErrors.value = error.response.data.errors || {};
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'No se pudo actualizar el sector',
        life: 3000
      });
    }
  } finally {
    loading.value = false;
  }
}
</script>
