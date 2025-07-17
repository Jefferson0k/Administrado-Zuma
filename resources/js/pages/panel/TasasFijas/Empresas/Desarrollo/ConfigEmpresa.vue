<template>
  <Dialog v-model:visible="visible" modal maximizable header="Configuración de" :style="{ width: '50vw' }"
    :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
    <div v-if="empresa">
      <h5 class="mb-3 font-bold text-lg">{{ empresa.nombre }}</h5>

      <TabView>
        <TabPanel header="Rangos de Monto">
          <AmountRangesList :empresa-id="empresa.id" />
        </TabPanel>

        <TabPanel header="Plazos">
            <TermPlansList :empresa-id="empresa.id" />
        </TabPanel>

        <TabPanel header="Tasas por Plazo y Rango">
          <FixedTermMatrix :empresa-id="empresa.id" />
        </TabPanel>
      </TabView>
    </div>

    <template #footer>
      <Button label="Cerrar" icon="pi pi-times"  severity="secondary" @click="visible = false" text />
    </template>
  </Dialog>
</template>

<script setup>
import { ref } from 'vue'

// PrimeVue
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'

// Subcomponentes (los crearás tú o te ayudo con eso)
import AmountRangesList from './tabs/AmountRangesList.vue'
import TermPlansList from './tabs/TermPlansList.vue'
import FixedTermMatrix from './tabs/FixedTermMatrix.vue'

const visible = ref(false)
const empresa = ref(null)

function open(data) {
  empresa.value = data
  visible.value = true
}

defineExpose({ open })
</script>
