<template>
    <Dialog :visible="localVisible" @update:visible="handleVisibilityChange" modal header="Configuración de subasta"
        :style="{ width: '600px' }">
        <form @submit.prevent="submitForm" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="col-span-1">
                <label for="dia_subasta" class="block font-bold mb-2">Día <span class="text-red-500">*</span></label>
                <Calendar id="dia_subasta" v-model="formData.dia_subasta" :minDate="new Date()" dateFormat="dd/mm/yy"
                    :required="true" class="w-full" placeholder="Seleccione el día" />
            </div>

            <div class="col-span-1">
                <label for="hora_inicio" class="block font-bold mb-2">Hora inicio <span
                        class="text-red-500">*</span></label>
                <Calendar id="hora_inicio" v-model="formData.hora_inicio" timeOnly hourFormat="24" :required="true"
                    class="w-full" placeholder="Seleccione hora de inicio" />
            </div>

            <div class="col-span-1">
                <label for="hora_fin" class="block font-bold mb-2">Hora fin <span class="text-red-500">*</span></label>
                <Calendar id="hora_fin" v-model="formData.hora_fin" timeOnly hourFormat="24" :required="true"
                    class="w-full" placeholder="Seleccione hora de fin" />
            </div>

            <div class="col-span-1">
                <label class="block font-bold mb-2">Duración de la subasta</label>
                <p class="text-gray-800">{{ duracionCalculada || ' ' }}</p>
            </div>

            <div class="col-span-2">
                <label class="block font-bold mb-2">Autocompletado</label>
                <p class="text-gray-800">{{ fechaFinalizacionFormateada || ' ' }}</p>
            </div>

            <div v-if="mensajeValidacion" class="col-span-2 text-red-500 text-sm">
                {{ mensajeValidacion }}
            </div>

            <div class="col-span-2 flex justify-end gap-3 mt-3">
                <Button label="Cancelar" severity="secondary" @click="cancelar" />
                <Button label="Configurar" type="submit" :disabled="!formularioValido || cargando"
                    :loading="cargando" severity="warn"/>
            </div>
        </form>
    </Dialog>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Calendar from "primevue/calendar";

const props = defineProps({
    visible: Boolean,
    idPropiedad: Number
});

const emit = defineEmits(['update:visible', 'configuracion-guardada']);

const toast = useToast();
const localVisible = ref(props.visible);
const cargando = ref(false);
const mensajeValidacion = ref('');

const formData = ref({
    estado: 'programada',
    monto_inicial: 0,
    valor_subasta: null,
    dia_subasta: null,
    hora_inicio: null,
    hora_fin: null
});

watch(() => props.visible, async (val) => {
    localVisible.value = val;
    if (val && props.idPropiedad) {
        await cargarDatosPropiedad();
    }
});

const handleVisibilityChange = (val) => {
    localVisible.value = val;
    emit('update:visible', val);
};

const cargarDatosPropiedad = async () => {
    try {
        const response = await axios.get(`/property/${props.idPropiedad}`);
        const data = response.data;

        formData.value.estado = data.estado || 'programada';
        formData.value.monto_inicial = 0;
        formData.value.valor_subasta = data.valor_subasta || null;

        formData.value.dia_subasta = data.dia_subasta ? new Date(data.dia_subasta) : null;
        formData.value.hora_inicio = data.hora_inicio ? new Date(`1970-01-01T${data.hora_inicio}`) : null;
        formData.value.hora_fin = data.hora_fin ? new Date(`1970-01-01T${data.hora_fin}`) : null;

        mensajeValidacion.value = '';
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar la propiedad', life: 3000 });
    }
};

const duracionCalculada = computed(() => {
    if (!formData.value.hora_inicio || !formData.value.hora_fin) return '';
    const inicio = new Date(formData.value.hora_inicio);
    const fin = new Date(formData.value.hora_fin);

    if (fin <= inicio) {
        mensajeValidacion.value = 'La hora de fin debe ser posterior a la de inicio';
        return '';
    }

    mensajeValidacion.value = '';
    const diff = fin.getTime() - inicio.getTime();
    const horas = Math.floor(diff / (1000 * 60 * 60));
    const minutos = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    return `${horas}h ${minutos}m`;
});

const fechaFinalizacion = computed(() => {
    if (!formData.value.dia_subasta || !formData.value.hora_fin) return null;

    const fecha = new Date(formData.value.dia_subasta);
    const hora = new Date(formData.value.hora_fin);
    fecha.setHours(hora.getHours(), hora.getMinutes(), 0, 0);
    return fecha;
});

const fechaFinalizacionFormateada = computed(() => {
    if (!fechaFinalizacion.value) return '';
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
    return formData.value.dia_subasta &&
        formData.value.hora_inicio &&
        formData.value.hora_fin &&
        duracionCalculada.value !== '' &&
        !mensajeValidacion.value;
});

const cancelar = () => {
    handleVisibilityChange(false);
    resetForm();
};

const resetForm = () => {
    formData.value = {
        estado: 'programada',
        monto_inicial: 0,
        valor_subasta: null,
        dia_subasta: null,
        hora_inicio: null,
        hora_fin: null
    };
    mensajeValidacion.value = '';
};

const formatearFecha = (fecha) => fecha?.toISOString().split('T')[0] || null;
const formatearHora = (hora) => {
    if (!hora) return null;
    const h = hora.getHours().toString().padStart(2, '0');
    const m = hora.getMinutes().toString().padStart(2, '0');
    return `${h}:${m}:00`;
};

const submitForm = async () => {
    if (!props.idPropiedad) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se ha seleccionado una propiedad', life: 3000 });
        return;
    }

    cargando.value = true;
    try {
        const payload = {
            monto_inicial: formData.value.monto_inicial,
            dia_subasta: formatearFecha(formData.value.dia_subasta),
            hora_inicio: formatearHora(formData.value.hora_inicio),
            hora_fin: formatearHora(formData.value.hora_fin)
        };

        const response = await axios.post(`/properties/${props.idPropiedad}/activacion`, payload);

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data.message || 'Configuración guardada correctamente',
            life: 3000
        });

        emit('configuracion-guardada');
        handleVisibilityChange(false);
        resetForm();
    } catch (error) {
        const msg = error.response?.data?.message || error.response?.data?.error || 'Error al guardar';
        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 3000 });
    } finally {
        cargando.value = false;
    }
};

</script>