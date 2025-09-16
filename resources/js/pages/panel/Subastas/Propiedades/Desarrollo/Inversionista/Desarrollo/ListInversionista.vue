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
        <Column field="requerido" header="Valor Requerido">
            <template #body="{ data }">
                {{ formatCurrency(data.requerido) }}
            </template>
        </Column>
        <Column field="cronograma" header="Tipo Cronograma">
            <template #body="{ data }">
                {{ formatCronograma(data.cronograma) }}
            </template>
        </Column>
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

        <Column v-if="isColumnSelected('valor')" field="valor" header="Valor de la Propiedad" style="min-width: 10rem">
            <template #body="{ data }">
                {{ formatCurrency(data.valor) }}
            </template>
        </Column>

        <Column v-if="isColumnSelected('subasta')" field="subasta" header="Valor Subasta" style="min-width: 10rem">
            <template #body="{ data }">
                <span v-if="data.subasta && data.subasta !== '0'">
                    {{ formatCurrency(data.subasta) }}
                </span>
                <span v-else class="text-gray-400">-</span>
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

        <!-- COLUMNA CORREGIDA - Usando ref() correctamente -->
        <Column style="width: 5rem">
            <template #body="{ data }">
                <div class="flex justify-center">
                    <Menu 
                        :ref="(el) => setMenuRef(el, data.id)" 
                        :model="getMenuItems(data)" 
                        :popup="true" 
                    />
                    <Button 
                        icon="pi pi-ellipsis-v" 
                        severity="secondary" 
                        variant="text" 
                        rounded
                        @click="toggleMenu($event, data)"
                        aria-haspopup="true"
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
const products = ref([]);
const selectedProducts = ref([]);
const showPrintDialog = ref(false);
const prestamosId = ref(null);
const showModal = ref(false);
const currentData = ref(null);

// CORRECCIÓN: Crear un objeto para almacenar las referencias de los menús
const menuRefs = ref({});

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
    { field: 'valor', header: 'Valor de la Propiedad' },
    { field: 'subasta', header: 'Valor Subasta' },
]);

// CORRECCIÓN: Función para establecer referencias de menús
const setMenuRef = (el, id) => {
    if (el) {
        menuRefs.value[`menu_${id}`] = el;
    }
};

// Función para formatear moneda
const formatCurrency = (value) => {
    if (!value || value === '0') return '-';
    const number = parseFloat(value);
    return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
        minimumFractionDigits: 2
    }).format(number);
};

// Función para formatear cronograma
const formatCronograma = (cronograma) => {
    const cronogramas = {
        'frances': 'Francés',
        'aleman': 'Alemán',
        'americano': 'Americano'
    };
    return cronogramas[cronograma] || cronograma;
};

// Función para obtener los items del menú
const getMenuItems = (data) => {
    console.log('Generando menú para:', data); // Debug
    return [
        {
            label: 'Ver Detalle',
            icon: 'pi pi-eye',
            command: () => {
                console.log('Ver detalle clickeado para:', data); // Debug
                verDetalle(data);
            }
        },
        {
            separator: true
        },
        {
            label: 'Subastarla',
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
    ];
};

// CORRECCIÓN: Función corregida para mostrar/ocultar el menú usando las referencias correctas
const toggleMenu = (event, data) => {
    console.log('Toggle menu para:', data); // Debug
    currentData.value = data;
    
    // Obtener la referencia del menú específico para esta fila usando el objeto menuRefs
    const menuKey = `menu_${data.id}`;
    const menuComponent = menuRefs.value[menuKey];
    
    if (menuComponent) {
        menuComponent.toggle(event);
    } else {
        console.error('No se encontró la referencia del menú para:', menuKey);
    }
};

// Función para abrir el dialog de email
const abrirEmailDialog = (data) => {
    console.log('Abrir email para:', data); // Debug
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
            investor_id: currentData.value.investor_id
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
    console.log('Descargar PDF para:', data); // Debug
    toast.add({ 
        severity: 'info', 
        summary: 'Descarga', 
        detail: `Descargando PDF del préstamo ${data.id}` 
    });
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

// Función principal para obtener datos del API
const getData = async () => {
    try {
        console.log('Iniciando petición a la API...');
        const response = await axios.get('/property-loan-details');
        
        console.log('Respuesta completa del servidor:', response);
        console.log('Datos de respuesta:', response.data);
        
        // Verificar que la respuesta tenga la estructura esperada
        if (response.data && Array.isArray(response.data.data)) {
            products.value = response.data.data;
            console.log('Datos cargados correctamente:', products.value);
            console.log('Número de registros:', products.value.length);
            
            // Verificar la estructura del primer elemento si existe
            if (products.value.length > 0) {
                console.log('Primer registro:', products.value[0]);
            }
        } else if (response.data && Array.isArray(response.data)) {
            // Por si acaso la respuesta viene directamente como array
            products.value = response.data;
            console.log('Datos cargados directamente como array:', products.value);
        } else {
            console.error('Estructura de respuesta inesperada:', response.data);
            console.error('Tipo de data:', typeof response.data.data);
            console.error('Es array data?', Array.isArray(response.data.data));
            
            toast.add({ 
                severity: 'warn', 
                summary: 'Advertencia', 
                detail: 'Los datos recibidos tienen una estructura inesperada' 
            });
            products.value = [];
        }
    } catch (error) {
        console.error('Error completo:', error);
        console.error('Respuesta del error:', error.response);
        console.error('Status del error:', error.response?.status);
        console.error('Datos del error:', error.response?.data);
        
        let errorMessage = 'No se pudo cargar los datos';
        
        if (error.response?.status === 404) {
            errorMessage = 'Endpoint no encontrado. Verifica la URL de la API.';
        } else if (error.response?.status === 500) {
            errorMessage = 'Error interno del servidor. Revisa los logs del backend.';
        } else if (error.response?.data?.message) {
            errorMessage = error.response.data.message;
        }
        
        toast.add({ 
            severity: 'error', 
            summary: 'Error', 
            detail: errorMessage,
            life: 5000
        });
        products.value = [];
    }
};

const handleClosePrestamo = () => {
    showPrintDialog.value = false;
    prestamosId.value = null;
};

// Función para ver detalle
const verDetalle = (prestamo) => {
    console.log('Ver detalle ejecutado con:', prestamo); // Debug
    prestamosId.value = prestamo.id;
    showPrintDialog.value = true;
    toast.add({ 
        severity: 'info', 
        summary: 'Ver Detalle', 
        detail: `Mostrando detalle del préstamo ID: ${prestamo.id} - Cliente: ${prestamo.cliente}` 
    });
};

const abrirConfiguracion = (data) => {
    console.log('Abrir configuración para:', data); // Debug
    prestamosId.value = data.property_id;
    showModal.value = true;
};

const editarPrestamo = (data) => {
    console.log('Editar préstamo:', data); // Debug
    toast.add({ severity: 'info', summary: 'Editar', detail: `Editar préstamo ${data.id}` });
    // Implementar lógica para editar
};

const eliminarPrestamo = (data) => {
    console.log('Eliminar préstamo:', data); // Debug
    toast.add({ severity: 'warn', summary: 'Eliminar', detail: `Eliminar préstamo ${data.id}` });
    // Implementar lógica para eliminar con confirmación
};

const getEstadoSeverity = (estado) => {
    switch (estado) {
        case 'activa':
            return 'success';
        case 'pendiente':
            return 'warn';
        case 'en_subasta':
            return 'info';
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

onMounted(() => {
    getData();
});
</script>