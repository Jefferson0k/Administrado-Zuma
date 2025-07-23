<template>
    <Dialog v-model:visible="visible" header="Editar Propiedad" :modal="true" :style="{ width: '450px' }">
        <div v-if="loading" class="text-center p-4">
            <i class="pi pi-spin pi-spinner" style="font-size: 2rem"></i>
            <p class="mt-2">Cargando información de la propiedad...</p>
        </div>

        <form v-else @submit.prevent="actualizarPropiedad" class="p-fluid">
            <div class="flex flex-col gap-4">
                <div>
                    <label class="font-bold mb-1">Nombre <span class="text-red-500">*</span></label>
                    <InputText v-model="form.nombre" class="w-full" />
                </div>

                <div class="flex gap-4">
                    <div class="w-1/2">
                        <label class="font-bold mb-1">Departamento <span class="text-red-500">*</span></label>
                        <Select v-model="form.departamento" :options="departamentos" optionLabel="ubigeo_name"
                            dataKey="ubigeo_id" placeholder="Seleccione departamento" class="w-full"
                            @change="onDepartamentoChange" />
                    </div>
                    <div class="w-1/2">
                        <label class="font-bold mb-1">Provincia <span class="text-red-500">*</span></label>
                        <Select v-model="form.provincia" :options="provincias" optionLabel="ubigeo_name"
                            dataKey="ubigeo_id" placeholder="Seleccione provincia" class="w-full"
                            :disabled="!form.departamento" @change="onProvinciaChange" />
                    </div>
                </div>

                <div>
                    <label class="font-bold mb-1">Distrito <span class="text-red-500">*</span></label>
                    <Select v-model="form.distrito" :options="distritos" optionLabel="ubigeo_name" dataKey="ubigeo_id"
                        placeholder="Seleccione distrito" class="w-full" :disabled="!form.provincia" />
                </div>

                <div>
                    <label class="font-bold mb-1">Dirección <span class="text-red-500">*</span></label>
                    <InputText v-model="form.direccion" class="w-full" />
                </div>

                <div>
                    <label class="font-bold mb-1">Descripción</label>
                    <Textarea v-model="form.descripcion" rows="3" class="w-full" autoResize />
                </div>

                <div class="flex gap-4">
                    <div class="w-1/2">
                        <label class="font-bold mb-1">Moneda <span class="text-red-500">*</span></label>
                        <Select v-model="form.currency_id" :options="monedas" optionLabel="label" optionValue="value"
                            placeholder="Selecciona moneda" class="w-full" />
                    </div>
                    <div class="w-1/2">
                        <label class="font-bold mb-1">Valor de la propiedad <span class="text-red-500">*</span></label>
                        <InputNumber v-model="form.valor_estimado" class="w-full" :useGrouping="true"
                            :locale="'es-PE'" />
                    </div>
                </div>

                <div>
                    <label class="font-bold mb-1">Monto requerido <span class="text-red-500">*</span></label>
                    <InputNumber v-model="form.valor_requerido" class="w-full" :useGrouping="true" :locale="'es-PE'" />
                </div>
                <!-- Imágenes actuales -->
                <div v-if="imagenesActuales.length > 0">
                    <label class="font-bold mb-2 block">Imágenes actuales</label>
                    <div class="grid grid-cols-2 gap-2">
                        <div v-for="imagen in imagenesActuales" :key="imagen.id"
                            class="relative border border-gray-300 rounded p-2">
                            <img :src="imagen.url" :alt="imagen.imagen" class="w-full h-20 object-cover rounded mb-2" />
                            <Button icon="pi pi-trash" severity="danger" size="small" @click="eliminarImagen(imagen)"
                                class="absolute top-1 right-1 p-1" />
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block font-bold mb-1">
                        {{ imagenesActuales.length > 0 ? 'Agregar más imágenes' : 'Imágenes' }}
                    </label>
                    <FileUpload name="imagenes[]" :multiple="true" accept="image/*" :maxFileSize="1000000" customUpload
                        :auto="false" @select="onSelectedFiles" />
                </div>
            </div>
        </form>

        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" severity="secondary" text @click="cerrarModal" />
            <Button label="Actualizar" icon="pi pi-check" severity="contrast" @click="actualizarPropiedad"
                :loading="saving" :disabled="loading" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch, reactive, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import FileUpload from 'primevue/fileupload';
import Textarea from 'primevue/textarea';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false
    },
    idPropiedad: {
        type: [String, Number],
        default: null
    }
});

const emit = defineEmits(['update:visible', 'propiedad-actualizada']);

const toast = useToast();
const loading = ref(false);
const saving = ref(false);
const archivos = ref([]);
const imagenesActuales = ref([]);
const imagenesAEliminar = ref([]);

const visible = ref(props.visible);

const form = reactive({
    nombre: '',
    departamento: null,
    provincia: null,
    distrito: null,
    direccion: '',
    descripcion: '',
    valor_estimado: null,
    valor_requerido: null,
    currency_id: null,
    estado: '',
    tea: null,
    tem: null,
    riesgo: null,
    // Agregamos el investor_id al formulario
    investor_id: null
});

const monedas = [
    { label: 'PEN (S/)', value: 1 },
    { label: 'USD ($)', value: 2 }
];

const departamentos = ref([]);
const provincias = ref([]);
const distritos = ref([]);

onMounted(async () => {
    try {
        const { data } = await axios.get('https://novalink.oswa.workers.dev/api/v1/peru/ubigeo');
        departamentos.value = data;
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Error al cargar departamentos', life: 3000 });
    }
});

watch(() => props.visible, (newVal) => {
    visible.value = newVal;
    if (newVal && props.idPropiedad) {
        cargarPropiedad();
    }
});

watch(visible, (newVal) => {
    emit('update:visible', newVal);
    if (!newVal) {
        resetForm();
    }
});

const onDepartamentoChange = () => {
    form.provincia = null;
    form.distrito = null;
    provincias.value = form.departamento?.provinces || [];
    distritos.value = [];
};

const onProvinciaChange = () => {
    form.distrito = null;
    distritos.value = form.provincia?.districts || [];
};

const cargarPropiedad = async () => {
    if (!props.idPropiedad) return;

    try {
        loading.value = true;
        const response = await axios.get(`/property/${props.idPropiedad}/show`);
        const property = response.data;

        form.nombre = property.nombre || '';
        form.direccion = property.direccion || '';
        form.descripcion = property.descripcion || '';
        form.valor_estimado = property.valor_estimado;
        form.valor_requerido = property.valor_requerido;
        form.currency_id = property.currency_id;
        form.estado = property.estado || '';
        // Guardamos el investor_id de la respuesta
        form.investor_id = property.investor_id;

        await buscarUbicacion(property.departamento, property.provincia, property.distrito);

        imagenesActuales.value = property.images ? property.images.map(img => ({
            id: img.id,
            imagen: img.imagen,
            url: `/Propiedades/${property.id}/${img.imagen}`
        })) : [];

    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo cargar la información de la propiedad',
            life: 3000
        });
        cerrarModal();
    } finally {
        loading.value = false;
    }
};

const buscarUbicacion = async (departamento, provincia, distrito) => {
    try {
        const deptEncontrado = departamentos.value.find(dept =>
            dept.ubigeo_name.toLowerCase() === departamento.toLowerCase()
        );

        if (deptEncontrado) {
            form.departamento = deptEncontrado;
            provincias.value = deptEncontrado.provinces || [];

            const provEncontrada = provincias.value.find(prov =>
                prov.ubigeo_name.toLowerCase() === provincia.toLowerCase()
            );

            if (provEncontrada) {
                form.provincia = provEncontrada;
                distritos.value = provEncontrada.districts || [];

                const distEncontrado = distritos.value.find(dist =>
                    dist.ubigeo_name.toLowerCase() === distrito.toLowerCase()
                );

                if (distEncontrado) {
                    form.distrito = distEncontrado;
                }
            }
        }
    } catch (error) {
        console.error('Error al buscar ubicación:', error);
    }
};

const onSelectedFiles = (event) => {
    archivos.value = [...event.files];
};

const eliminarImagen = (imagen) => {
    imagenesAEliminar.value.push(imagen.id);
    imagenesActuales.value = imagenesActuales.value.filter(img => img.id !== imagen.id);
};

const actualizarPropiedad = async () => {
    if (!props.idPropiedad) return;

    if (
        !form.nombre ||
        !form.departamento ||
        !form.provincia ||
        !form.distrito ||
        !form.direccion ||
        !form.currency_id ||
        !form.valor_requerido ||
        !form.valor_estimado
    ) {
        toast.add({
            severity: 'warn',
            summary: 'Validación',
            detail: 'Por favor completa todos los campos obligatorios.',
            life: 3000
        });
        return;
    }

    try {
        saving.value = true;

        const formData = new FormData();

        // Enviar los nombres de los lugares, no los IDs (igual que en el registro)
        formData.append('nombre', form.nombre);
        formData.append('departamento', form.departamento.ubigeo_name);
        formData.append('provincia', form.provincia.ubigeo_name);
        formData.append('distrito', form.distrito.ubigeo_name);
        formData.append('direccion', form.direccion);
        formData.append('descripcion', form.descripcion || '');
        formData.append('valor_estimado', form.valor_estimado);
        formData.append('valor_requerido', form.valor_requerido);
        formData.append('currency_id', form.currency_id);
        
        // Enviar el investor_id de forma interna
        if (form.investor_id) {
            formData.append('investor_id', form.investor_id);
        }

        archivos.value.forEach((file) => {
            formData.append('imagenes[]', file);
        });

        imagenesAEliminar.value.forEach(id => {
            formData.append('imagenes_eliminar[]', id);
        });

        await axios.post(`/property/${props.idPropiedad}/actualizar?_method=PUT`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Propiedad actualizada correctamente',
            life: 3000
        });

        emit('propiedad-actualizada');
        cerrarModal();

    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'No se pudo actualizar la propiedad',
            life: 3000
        });
    } finally {
        saving.value = false;
    }
};

const resetForm = () => {
    Object.keys(form).forEach(key => {
        if (key === 'departamento' || key === 'provincia' || key === 'distrito') {
            form[key] = null;
        } else if (key === 'valor_estimado' || key === 'valor_requerido' || key === 'currency_id' || key === 'tea' || key === 'tem' || key === 'riesgo' || key === 'investor_id') {
            form[key] = null;
        } else {
            form[key] = '';
        }
    });
    archivos.value = [];
    imagenesActuales.value = [];
    imagenesAEliminar.value = [];
    provincias.value = [];
    distritos.value = [];
};

const cerrarModal = () => {
    visible.value = false;
};
</script>