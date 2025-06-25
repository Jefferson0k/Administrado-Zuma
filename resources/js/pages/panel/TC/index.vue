<template>
    <Head title="Calculadora" />
    <AppLayout>
        <div class="p-4 space-y-6 max-w-xl mx-auto">
            <h1 class="text-2xl font-bold text-center">Calculadora</h1>

            <!-- Formulario -->
            <Card>
                <template #content>
                    <div class="space-y-4">
                        <div class="flex flex-col gap-2">
                            <label for="ingresos">Ingresos (S/)</label>
                            <InputNumber v-model="form.ingresos" inputId="ingresos" mode="decimal" :min="0"
                                placeholder="Ingrese ingresos" class="w-full" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="egresos">Egresos (S/)</label>
                            <InputNumber v-model="form.egresos" inputId="egresos" mode="decimal" :min="0"
                                placeholder="Ingrese egresos" class="w-full" />
                        </div>
                        <div class="pt-2 text-end">
                            <Button label="Calcular" icon="pi pi-check" @click="calcular" />
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Resultados -->
            <Card v-if="resultado">
                <template #title>Resultado</template>
                <template #content>
                    <DataTable :value="tabla" class="p-datatable-sm">
                        <Column field="concepto" header="Concepto" />
                        <Column field="soles" header="Soles (S/)" />
                        <Column field="dolares" header="Dólares (USD)" />
                    </DataTable>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/layout/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import axios from 'axios'

// PrimeVue Components
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

const form = ref({
    ingresos: null,
    egresos: null
})

const resultado = ref(null)

const calcular = async () => {
    try {
        const response = await axios.post('/api/calculadora', form.value)
        resultado.value = response.data
    } catch (error) {
        console.error(error)
        alert('Ocurrió un error al calcular.')
    }
}

const tabla = computed(() => {
    if (!resultado.value) return []

    return [
        {
            concepto: 'Ingresos',
            soles: `S/ ${Number(form.value.ingresos).toFixed(2)}`,
            dolares: `$ ${resultado.value.ingresos_dolares.toFixed(2)}`
        },
        {
            concepto: 'Egresos',
            soles: `S/ ${Number(form.value.egresos).toFixed(2)}`,
            dolares: `$ ${resultado.value.egresos_dolares.toFixed(2)}`
        },
        {
            concepto: 'Cuota ideal',
            soles: `S/ ${resultado.value.cuota_ideal_soles.toFixed(2)}`,
            dolares: `$ ${resultado.value.cuota_ideal_dolares.toFixed(2)}`
        }
    ]
})
</script>
