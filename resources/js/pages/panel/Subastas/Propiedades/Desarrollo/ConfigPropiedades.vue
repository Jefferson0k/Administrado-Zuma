<template>
    <Button label="Configurar Subasta" @click="visible = true" />
    <Dialog v-model:visible="visible" modal header="Configuración de subasta" :style="{ width: '450px' }">
        <span class="text-surface-500 dark:text-surface-400 block mb-8">
            Después de la configuración no se podrán deshacer los cambios.
        </span>

        <form @submit.prevent="submitForm" class="flex flex-col gap-6">
            <div>
                <label for="monto_inicial" class="block font-bold mb-3">
                    Monto inicial <span class="text-red-500">*</span>
                </label>
                <InputNumber id="monto_inicial" v-model="formData.monto_inicial" mode="currency" currency="USD"
                    locale="en-US" :min="0" :required="true" class="w-full" placeholder="Ingrese el monto inicial" />
            </div>

            <div>
                <label for="dia_subasta" class="block font-bold mb-3">
                    Día <span class="text-red-500">*</span>
                </label>
                <Calendar id="dia_subasta" v-model="formData.dia_subasta" :minDate="new Date()" dateFormat="dd/mm/yy"
                    :required="true" class="w-full" placeholder="Seleccione el día" @date-select="calculateEndTime" />
            </div>

            <div>
                <label for="hora_inicio" class="block font-bold mb-3">
                    Hora inicio <span class="text-red-500">*</span>
                </label>
                <Calendar id="hora_inicio" v-model="formData.hora_inicio" timeOnly hourFormat="24" :required="true"
                    class="w-full" placeholder="Seleccione hora de inicio" @date-select="calculateEndTime" />
            </div>

            <div>
                <label for="hora_fin" class="block font-bold mb-3">
                    Hora fin <span class="text-red-500">*</span>
                </label>
                <Calendar id="hora_fin" v-model="formData.hora_fin" timeOnly hourFormat="24" :required="true"
                    class="w-full" placeholder="Seleccione hora de fin" @date-select="calculateEndTime" />
            </div>

            <div>
                <label for="duracion_calculada" class="block font-bold mb-3">
                    Duración calculada
                </label>
                <InputText id="duracion_calculada" :value="duracionCalculada" readonly class="w-full"
                    placeholder="Se calculará automáticamente" />
            </div>

            <div>
                <label for="tiempo_finalizacion" class="block font-bold mb-3">
                    Fecha y hora de finalización
                </label>
                <InputText id="tiempo_finalizacion" :value="fechaFinalizacionFormateada" readonly class="w-full"
                    placeholder="Se calculará automáticamente" />
            </div>

            <!-- Mensaje de validación -->
            <div v-if="mensajeValidacion" class="p-3 border border-red-300 bg-red-50 text-red-700 rounded">
                {{ mensajeValidacion }}
            </div>

            <!-- Botones -->
            <div class="flex justify-end gap-3 mt-6">
                <Button label="Cancelar" severity="secondary" @click="cancelar" />
                <Button label="Configurar" type="submit" :disabled="!formularioValido || cargando"
                    :loading="cargando" />
            </div>
        </form>
    </Dialog>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import axios from 'axios';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from "primevue/inputtext";
import InputNumber from "primevue/inputnumber";
import Calendar from "primevue/calendar";

const visible = ref(false);

const formData = ref({
    monto_inicial: null,
    dia_subasta: null,
    hora_inicio: null,
    hora_fin: null
});

const mensajeValidacion = ref('');

const duracionCalculada = computed(() => {
    if (!formData.value.hora_inicio || !formData.value.hora_fin) {
        return '';
    }

    const inicio = new Date(formData.value.hora_inicio);
    const fin = new Date(formData.value.hora_fin);

    if (fin <= inicio) {
        mensajeValidacion.value = 'La hora de fin debe ser posterior a la hora de inicio';
        return '';
    }

    mensajeValidacion.value = '';

    const diferencia = fin.getTime() - inicio.getTime();
    const horas = Math.floor(diferencia / (1000 * 60 * 60));
    const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));

    return `${horas}h ${minutos}m`;
});

const fechaFinalizacion = computed(() => {
    if (!formData.value.dia_subasta || !formData.value.hora_fin) {
        return null;
    }

    const fecha = new Date(formData.value.dia_subasta);
    const hora = new Date(formData.value.hora_fin);

    fecha.setHours(hora.getHours());
    fecha.setMinutes(hora.getMinutes());
    fecha.setSeconds(0);
    fecha.setMilliseconds(0);

    return fecha;
});

const fechaFinalizacionFormateada = computed(() => {
    if (!fechaFinalizacion.value) {
        return '';
    }

    return fechaFinalizacion.value.toLocaleString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    });
});

const formularioValido = computed(() => {
    return formData.value.monto_inicial &&
        formData.value.monto_inicial > 0 &&
        formData.value.dia_subasta &&
        formData.value.hora_inicio &&
        formData.value.hora_fin &&
        duracionCalculada.value !== '' &&
        !mensajeValidacion.value;
});

const cargando = ref(false);

const cancelar = () => {
    visible.value = false;
    resetForm();
};

const resetForm = () => {
    formData.value = {
        monto_inicial: null,
        dia_subasta: null,
        hora_inicio: null,
        hora_fin: null
    };
    mensajeValidacion.value = '';
};

watch(() => formData.value.hora_fin, () => {
    if (formData.value.hora_inicio && formData.value.hora_fin) {
        const inicio = new Date(formData.value.hora_inicio);
        const fin = new Date(formData.value.hora_fin);

        if (fin > inicio) {
            mensajeValidacion.value = '';
        }
    }
});
</script>