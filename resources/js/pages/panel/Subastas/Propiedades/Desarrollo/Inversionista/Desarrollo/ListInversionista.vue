<template>
    <DataTable ref="dt" v-model:selection="selectedProducts" :value="products" dataKey="id" :paginator="true" :rows="10"
        :filters="filters"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25]"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} registros" class="p-datatable-sm">

        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Informes</h4>
                
                <div class="flex flex-wrap gap-2">
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                    </IconField>
                    
                    <MultiSelect 
                        v-model="selectedColumns" 
                        :options="optionalColumns" 
                        optionLabel="header"
                        display="chip" 
                        placeholder="Seleccionar Columnas" 
                        class="w-full md:w-auto"
                    />
                </div>
            </div>
        </template>

        <Column selectionMode="multiple" style="width: 3rem" :exportable="false" />
        <Column field="documento" header="DNI" />
        <Column field="cliente" header="Cliente" />
        <Column field="propiedad" header="Propiedad" />
        <Column field="requerido" header="Valor Requerido" />
        <Column field="cronograma" header="Tipo Cronograma" />
        <Column field="plazo" header="Plazo" />
        
        <!-- Columnas opcionales -->
        <Column v-if="isColumnSelected('ocupacion_profesion')" field="ocupacion_profesion" header="Ocupación/Profesión" style="min-width: 15rem">
            <template #body="{ data }">
                <div v-if="data.ocupacion_profesion">
                    <span v-if="data.ocupacion_profesion.length <= 50">{{ data.ocupacion_profesion }}</span>
                    <div v-else>
                        <span>{{ data.ocupacion_profesion.substring(0, 50) }}...</span>
                        <Button 
                            label="Leer más" 
                            link 
                            size="small" 
                            class="ml-2 p-0"
                            @click="openDetailDialog('Ocupación/Profesión', data.ocupacion_profesion, data)"
                        />
                    </div>
                </div>
                <span v-else>-</span>
            </template>
        </Column>

        <Column v-if="isColumnSelected('motivo_prestamo')" field="motivo_prestamo" header="Motivo del Préstamo" style="min-width: 15rem">
            <template #body="{ data }">
                <div v-if="data.motivo_prestamo">
                    <span v-if="data.motivo_prestamo.length <= 50">{{ data.motivo_prestamo }}</span>
                    <div v-else>
                        <span>{{ data.motivo_prestamo.substring(0, 50) }}...</span>
                        <Button 
                            label="Leer más" 
                            link 
                            size="small" 
                            class="ml-2 p-0"
                            @click="openDetailDialog('Motivo del Préstamo', data.motivo_prestamo, data)"
                        />
                    </div>
                </div>
                <span v-else>-</span>
            </template>
        </Column>

        <Column v-if="isColumnSelected('descripcion_financiamiento')" field="descripcion_financiamiento" header="Descripción Financiamiento" style="min-width: 15rem">
            <template #body="{ data }">
                <div v-if="data.descripcion_financiamiento">
                    <span v-if="data.descripcion_financiamiento.length <= 50">{{ data.descripcion_financiamiento }}</span>
                    <div v-else>
                        <span>{{ data.descripcion_financiamiento.substring(0, 50) }}...</span>
                        <Button 
                            label="Leer más" 
                            link 
                            size="small" 
                            class="ml-2 p-0"
                            @click="openDetailDialog('Descripción Financiamiento', data.descripcion_financiamiento, data)"
                        />
                    </div>
                </div>
                <span v-else>-</span>
            </template>
        </Column>

        <Column v-if="isColumnSelected('solicitud_prestamo_para')" field="solicitud_prestamo_para" header="Solicitud Para" style="min-width: 15rem">
            <template #body="{ data }">
                <div v-if="data.solicitud_prestamo_para">
                    <span v-if="data.solicitud_prestamo_para.length <= 50">{{ data.solicitud_prestamo_para }}</span>
                    <div v-else>
                        <span>{{ data.solicitud_prestamo_para.substring(0, 50) }}...</span>
                        <Button 
                            label="Leer más" 
                            link 
                            size="small" 
                            class="ml-2 p-0"
                            @click="openDetailDialog('Solicitud Para', data.solicitud_prestamo_para, data)"
                        />
                    </div>
                </div>
                <span v-else>-</span>
            </template>
        </Column>

        <Column v-if="isColumnSelected('garantia')" field="garantia" header="Garantía" style="min-width: 15rem">
            <template #body="{ data }">
                <div v-if="data.garantia">
                    <span v-if="data.garantia.length <= 50">{{ data.garantia }}</span>
                    <div v-else>
                        <span>{{ data.garantia.substring(0, 50) }}...</span>
                        <Button 
                            label="Leer más" 
                            link 
                            size="small" 
                            class="ml-2 p-0"
                            @click="openDetailDialog('Garantía', data.garantia, data)"
                        />
                    </div>
                </div>
                <span v-else>-</span>
            </template>
        </Column>

        <Column v-if="isColumnSelected('perfil_riesgo')" field="perfil_riesgo" header="Perfil de Riesgo" style="min-width: 15rem">
            <template #body="{ data }">
                <div v-if="data.perfil_riesgo">
                    <span v-if="data.perfil_riesgo.length <= 50">{{ data.perfil_riesgo }}</span>
                    <div v-else>
                        <span>{{ data.perfil_riesgo.substring(0, 50) }}...</span>
                        <Button 
                            label="Leer más" 
                            link 
                            size="small" 
                            class="ml-2 p-0"
                            @click="openDetailDialog('Perfil de Riesgo', data.perfil_riesgo, data)"
                        />
                    </div>
                </div>
                <span v-else>-</span>
            </template>
        </Column>

        <Column field="riesgo" header="Riesgo">
            <template #body="{ data }">
                <Tag :value="data.riesgo" :severity="getRiesgoSeverity(data.riesgo)" />
            </template>
        </Column>
        
        <Column field="estado_nombre" header="Estado">
            <template #body="{ data }">
                <Tag :value="data.estado_nombre" :severity="getEstadoSeverity(data.estado)" />
            </template>
        </Column>

        <Column header="">
            <template #body="{ data }">
                <div class="flex justify-center">
                    <Menu 
                        ref="menu" 
                        :model="getMenuItems(data)" 
                        :popup="true" 
                        id="overlay_menu"
                    />
                    <Button 
                        icon="pi pi-ellipsis-v" 
                        severity="secondary" 
                        variant="text" 
                        rounded
                        @click="toggleMenu($event, data)"
                        aria-haspopup="true" 
                        aria-controls="overlay_menu"
                    />
                </div>
            </template>
        </Column>
    </DataTable>

    <!-- Dialog para mostrar información completa -->
    <Dialog 
        v-model:visible="showDetailDialog" 
        :header="dialogTitle"
        :style="{ width: '50vw' }"
        :breakpoints="{ '960px': '75vw', '641px': '90vw' }"
        modal
    >
        <div class="p-4">
            <div class="mb-4">
                <h6 class="text-lg font-semibold mb-2">{{ dialogTitle }}</h6>
                <div class="bg-gray-50 p-3 rounded border">
                    <p class="text-gray-800 leading-relaxed whitespace-pre-wrap">{{ dialogContent }}</p>
                </div>
            </div>
            
            <div v-if="dialogData" class="border-t pt-4">
                <h6 class="text-md font-semibold mb-3 text-gray-600">Información del Cliente</h6>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-600">Cliente:</span>
                        <span class="ml-2">{{ dialogData.cliente }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">DNI:</span>
                        <span class="ml-2">{{ dialogData.documento }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Propiedad:</span>
                        <span class="ml-2">{{ dialogData.propiedad }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Riesgo:</span>
                        <Tag :value="dialogData.riesgo" :severity="getRiesgoSeverity(dialogData.riesgo)" class="ml-2" />
                    </div>
                </div>
            </div>
        </div>
        
        <template #footer>
            <Button label="Cerrar" @click="showDetailDialog = false" />
        </template>
    </Dialog>

    <!-- Dialog para envío por email -->
    <Dialog 
        v-model:visible="showEmailDialog" 
        header="Enviar por Email"
        :style="{ width: '500px' }"
        modal
    >
        <div class="p-4">
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium mb-2">Correo electrónico:</label>
                <InputText 
                    id="email"
                    v-model="emailAddress" 
                    type="email" 
                    placeholder="correo@ejemplo.com"
                    class="w-full"
                />
            </div>
            <div class="mb-4">
                <label for="message" class="block text-sm font-medium mb-2">Mensaje <span class="text-red-500">*</span> (mínimo 10 caracteres):</label>
                <Textarea 
                    id="message"
                    v-model="emailMessage" 
                    rows="4" 
                    placeholder="Por favor complete los datos faltantes de su perfil..."
                    class="w-full"
                    :class="{ 'p-invalid': emailMessage && emailMessage.length < 10 }"
                />
                <small v-if="emailMessage && emailMessage.length < 10" class="text-red-500">
                    El mensaje debe tener al menos 10 caracteres ({{ emailMessage.length }}/10)
                </small>
            </div>
            <div class="mb-4">
                <label for="asunto" class="block text-sm font-medium mb-2">Asunto:</label>
                <InputText 
                    id="asunto"
                    v-model="emailSubject" 
                    placeholder="Completar datos del perfil"
                    class="w-full"
                />
            </div>
        </div>
        
        <template #footer>
            <Button label="Cancelar" severity="secondary" @click="showEmailDialog = false" />
            <Button 
                label="Enviar" 
                icon="pi pi-send" 
                @click="sendByEmail" 
                :disabled="!emailAddress || !emailMessage || emailMessage.length < 10 || !emailSubject"
            />
        </template>
    </Dialog>

    <A4 v-if="showPrintDialog" :prestamosId="prestamosId" v-model:visible="showPrintDialog"
        @close="handleClosePrestamo" />
    <Congiguracion v-model:visible="showModal" :idPropiedad="prestamosId" @configuracion-guardada="getData" />
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import MultiSelect from 'primevue/multiselect';
import Dialog from 'primevue/dialog';
import Menu from 'primevue/menu';
import Textarea from 'primevue/textarea';
import A4 from './A4.vue';
import Congiguracion from './Congiguracion.vue';

const toast = useToast();
const dt = ref();
const menu = ref();
const products = ref([]);
const selectedProducts = ref([]);
const showPrintDialog = ref(false);
const prestamosId = ref(null);
const showModal = ref(false);
const currentData = ref(null);

// Estados para el MultiSelect y Dialog
const selectedColumns = ref([]);
const showDetailDialog = ref(false);
const dialogTitle = ref('');
const dialogContent = ref('');
const dialogData = ref(null);

// Estados para el dialog de email
const showEmailDialog = ref(false);
const emailAddress = ref('');
const emailMessage = ref('');
const emailSubject = ref('Completar datos del perfil');

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

// Columnas opcionales que pueden ser seleccionadas
const optionalColumns = ref([
    { field: 'ocupacion_profesion', header: 'Ocupación/Profesión' },
    { field: 'motivo_prestamo', header: 'Motivo del Préstamo' },
    { field: 'descripcion_financiamiento', header: 'Descripción Financiamiento' },
    { field: 'solicitud_prestamo_para', header: 'Solicitud Para' },
    { field: 'garantia', header: 'Garantía' },
    { field: 'perfil_riesgo', header: 'Perfil de Riesgo' },
]);

// Función para obtener los items del menú según el estado
const getMenuItems = (data) => {
    const baseItems = [
        {
            label: 'Ver Detalle',
            icon: 'pi pi-eye',
            command: () => verDetalle(data)
        }
    ];

    if (data.estado === 'activa') {
        baseItems.push(
            {
                separator: true
            },
            {
                label: 'Configuración',
                icon: 'pi pi-cog',
                command: () => abrirConfiguracion(data)
            },
            {
                label: 'Editar',
                icon: 'pi pi-pencil',
                command: () => editarPrestamo(data)
            },
            {
                separator: true
            },
            {
                label: 'Enviar por Email',
                icon: 'pi pi-envelope',
                command: () => abrirEmailDialog(data)
            },
            {
                label: 'Descargar PDF',
                icon: 'pi pi-download',
                command: () => descargarPDF(data)
            },
            {
                separator: true
            },
            {
                label: 'Eliminar',
                icon: 'pi pi-trash',
                class: 'text-red-500',
                command: () => eliminarPrestamo(data)
            }
        );
    } else {
        baseItems.push(
            {
                separator: true
            },
            {
                label: 'Enviar por Email',
                icon: 'pi pi-envelope',
                command: () => abrirEmailDialog(data)
            },
            {
                label: 'Descargar PDF',
                icon: 'pi pi-download',
                command: () => descargarPDF(data)
            }
        );
    }

    return baseItems;
};

// Función para mostrar/ocultar el menú
const toggleMenu = (event, data) => {
    currentData.value = data;
    menu.value.toggle(event);
};

// Función para abrir el dialog de email
const abrirEmailDialog = (data) => {
    currentData.value = data;
    emailAddress.value = '';
    emailMessage.value = 'Estimado/a inversionista,\n\nEsperamos que se encuentre bien. Le escribimos para solicitar que complete algunos datos faltantes en su perfil de inversionista.\n\nPara completar su información, por favor haga clic en el siguiente botón:\n\n[BOTÓN DE ACCESO]\n\nGracias por su tiempo y confianza.\n\nSaludos cordiales,\nEquipo ZUMA';
    emailSubject.value = 'Completar datos del perfil';
    showEmailDialog.value = true;
};

// Función para enviar por email
const sendByEmail = async () => {
    if (!emailAddress.value) {
        toast.add({ 
            severity: 'warn',
            summary: 'Atención',
            detail: 'Por favor ingresa un correo electrónico',
            life: 3000
        });
        return;
    }

    if (!emailMessage.value || emailMessage.value.length < 10) {
        toast.add({ 
            severity: 'warn',
            summary: 'Atención',
            detail: 'El mensaje debe tener al menos 10 caracteres',
            life: 3000
        });
        return;
    }

    if (!emailSubject.value) {
        toast.add({ 
            severity: 'warn',
            summary: 'Atención',
            detail: 'Por favor ingresa un asunto',
            life: 3000
        });
        return;
    }

    try {
        const payload = {
            emails: emailAddress.value,
            mensaje: emailMessage.value,
            asunto: emailSubject.value,
            investor_id: currentData.value.investor_id // Agregar el investor_id
        };

        const response = await axios.post('/property/enviar-emails', payload);

        toast.add({ 
            severity: 'success',
            summary: 'Éxito',
            detail: response.data.message || 'Correo enviado correctamente.',
            life: 3000
        });
        showEmailDialog.value = false;
    } catch (error) {
        const detail = error.response?.data?.message || 'No se pudo enviar el correo';
        toast.add({ 
            severity: 'error', 
            summary: 'Error', 
            detail,
            life: 3000
        });
    }
};

// Función para descargar PDF
const descargarPDF = (data) => {
    toast.add({ 
        severity: 'info', 
        summary: 'Descarga', 
        detail: `Descargando PDF del préstamo ${data.id}` 
    });
    // Lógica para descargar PDF
};

// Función para verificar si una columna está seleccionada
const isColumnSelected = (fieldName) => {
    return selectedColumns.value.some(col => col.field === fieldName);
};

// Función para abrir el dialog con información detallada
const openDetailDialog = (title, content, data) => {
    dialogTitle.value = title;
    dialogContent.value = content;
    dialogData.value = data;
    showDetailDialog.value = true;
};

const getData = async () => {
    try {
        const response = await axios.get('/property-loan-details');
        products.value = response.data.data;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar los datos' });
    }
};

const handleClosePrestamo = () => {
    showPrintDialog.value = false;
    prestamosId.value = null;
};

const verDetalle = (prestamo) => {
    prestamosId.value = prestamo.id;
    showPrintDialog.value = true;
};

const abrirConfiguracion = (data) => {
    prestamosId.value = data.property_id;
    showModal.value = true;
};

const editarPrestamo = (data) => {
    toast.add({ severity: 'info', summary: 'Editar', detail: `Editar préstamo ${data.id}` });
    // Lógica para editar
};

const eliminarPrestamo = (data) => {
    toast.add({ severity: 'warn', summary: 'Eliminar', detail: `Eliminar préstamo ${data.id}` });
    // Lógica para eliminar
};

const getEstadoSeverity = (estado) => {
    switch (estado) {
        case 'activa':
            return 'success';
        case 'pendiente':
            return 'warn';
        case 'subastada':
            return 'info';
        case 'desactivada':
            return 'danger';
        default:
            return 'secondary';
    }
};

const getRiesgoSeverity = (riesgo) => {
    switch (riesgo) {
        case 'A+':
        case 'A':
            return 'success';
        case 'B':
            return 'info';
        case 'C':
            return 'warn';
        case 'D':
            return 'danger';
        default:
            return 'secondary';
    }
};

onMounted(getData);
</script>