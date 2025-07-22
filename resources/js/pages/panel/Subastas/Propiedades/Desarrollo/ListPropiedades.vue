    <script setup>
    import { ref, onMounted, watch } from 'vue';
    import axios from 'axios';
    import { useToast } from 'primevue/usetoast';

    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    import InputText from 'primevue/inputtext';
    import IconField from 'primevue/iconfield';
    import InputIcon from 'primevue/inputicon';
    import Button from 'primevue/button';
    import MultiSelect from 'primevue/multiselect';
    import Select from 'primevue/select';
    import Image from 'primevue/image';
    import Menu from 'primevue/menu';
    import ConfigPropiedades from './ConfigPropiedades.vue';
    import UpdatePropiedades from './UpdatePropiedades.vue';
    import DeletePropiedades from './DeletePropiedades.vue';

    import Tag from 'primevue/tag';

    const props = defineProps({
    refresh: {
        type: Number,
        default: 0
    }
    });

    const toast = useToast();
    const dt = ref();
    const products = ref([]);
    const selectedProducts = ref([]);
    const loading = ref(false);
    const totalRecords = ref(0);
    const currentPage = ref(1);
    const perPage = ref(10);
    const search = ref('');
    const selectedColumns = ref([]);
    const showModal = ref(false);
    const showUpdateModal = ref(false);
    const showDeleteModal = ref(false);
    const selectedId = ref(null);
    const menu = ref();
    const menuItems = ref([]);

    const selectedEstado = ref(null);
    // Estados actualizados según tu enum de la base de datos
    const selectedOpcions = ref([
        { name: 'En subasta', value: 'en_subasta' },
        { name: 'Subastada', value: 'subastada' },
        { name: 'Programada', value: 'programada' },
        { name: 'Desactivada', value: 'desactivada' },
        { name: 'Activa', value: 'activa' },
        { name: 'Adquirido', value: 'adquirido' },
    ]);

    let searchTimeout;

    const loadData = async () => {
        loading.value = true;
        try {
            const response = await axios.get('/property', {
                params: {
                    page: currentPage.value,
                    per_page: perPage.value,
                    search: search.value,
                    estado: selectedEstado.value?.value || null,
                },
            });
            products.value = response.data.data;
            totalRecords.value = response.data.meta.total;
        } catch (error) {
            toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar las propiedades', life: 3000 });
        } finally {
            loading.value = false;
        }
    };

    onMounted(loadData);

    watch(() => props.refresh, () => {
        loadData();
    });

    watch([search, perPage, selectedEstado], () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage.value = 1;
            loadData();
        }, 500);
    });

    const onPage = (event) => {
        currentPage.value = event.page + 1;
        perPage.value = event.rows;
        loadData();
    };

    const isColumnSelected = (fieldName) => {
        return selectedColumns.value.some(col => col.field === fieldName);
    };

    const optionalColumns = ref([
        { field: 'descripcion', header: 'Descripción' },
        { field: 'foto', header: 'Imagen' },
        { field: 'valor_subasta', header: 'Valor Subasta' },
    ]);

    const onEditar = (data) => {
        selectedId.value = data.id;
        showUpdateModal.value = true;
    };

    const onEliminar = (data) => {
        selectedId.value = data.id;
        showDeleteModal.value = true;
    };

    // Función para formatear valores monetarios
    const formatCurrency = (value, currency = 'USD') => {
        if (!value || value === 0) return '-';
        return new Intl.NumberFormat('es-PE', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 2
        }).format(value);
    };

    // Función para obtener el color del estado
    const getEstadoSeverity = (estado) => {
        switch (estado) {
            case 'en_subasta':
                return 'info';
            case 'activa':
                return 'success';
            case 'subastada':
                return 'warn';
            case 'programada':
                return 'info';
            case 'desactivada':
                return 'danger';
            case 'adquirido':
                return 'success';
            case 'pendiente':
                return 'warn';
            default:
                return 'secondary';
        }
    };

    const onPropiedadActualizada = () => {
        loadData();
    };

    const onPropiedadEliminada = () => {
        loadData();
    };

    const copiarId = async (id) => {
        try {
            await navigator.clipboard.writeText(id);
            toast.add({
                severity: 'success',
                summary: 'ID copiado',
                detail: `ID ${id} copiado al portapapeles`,
                life: 3000,
            });
        } catch (err) {
            toast.add({
                severity: 'error',
                summary: 'Error al copiar',
                detail: 'No se pudo copiar el ID',
                life: 3000,
            });
        }
    };

    // Función para mostrar el menú contextual
    const showContextMenu = (event, data) => {
        menuItems.value = [
            {
                label: 'Editar',
                icon: 'pi pi-pencil',
                command: () => onEditar(data)
            },
            {
                label: 'Eliminar',
                icon: 'pi pi-trash',
                command: () => onEliminar(data)
            },
            {
                label: 'Copiar ID',
                icon: 'pi pi-copy',
                command: () => copiarId(data.id)
            }
        ];
        
        menu.value.show(event);
    };

    </script>

    <template>
        <DataTable ref="dt" v-model:selection="selectedProducts" :value="products" dataKey="id" :paginator="true"
            :rows="perPage" :first="(currentPage - 1) * perPage" :totalRecords="totalRecords" :loading="loading" lazy
            @page="onPage"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[10, 15, 25]"
            currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} propiedades" class="p-datatable-sm">
            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <div class="flex items-center gap-2">
                        <h4 class="m-0">Propiedades</h4>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="search" placeholder="Buscar..." />
                        </IconField>

                        <Select v-model="selectedEstado" :options="selectedOpcions" optionLabel="name" placeholder="Estado"
                            class="w-full md:w-auto" showClear />
                        <MultiSelect v-model="selectedColumns" :options="optionalColumns" optionLabel="header"
                            display="chip" placeholder="Seleccionar Columnas" />
                        <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" @click="loadData" />
                    </div>
                </div>
            </template>

            <Column selectionMode="multiple" style="width: 3rem" :exportable="false" />
            <Column field="nombre" header="Nombre" sortable style="min-width: 15rem" />

            <Column field="departamento" header="Departamento" sortable style="min-width: 12rem">
                <template #body="slotProps">
                    <span>{{ slotProps.data.departamento || '-' }}</span>
                </template>
            </Column>

            <Column field="provincia" header="Provincia" sortable style="min-width: 12rem">
                <template #body="slotProps">
                    <span>{{ slotProps.data.provincia || '-' }}</span>
                </template>
            </Column>
            <Column field="distrito" header="Distrito" sortable style="min-width: 12rem" />
            
            <Column v-if="isColumnSelected('direccion')" field="direccion" header="Dirección" sortable style="min-width: 20rem">
                <template #body="slotProps">
                    <span>{{ slotProps.data.direccion || '-' }}</span>
                </template>
            </Column>

            <!-- Columnas opcionales -->
            <Column v-if="isColumnSelected('descripcion')" field="descripcion" header="Descripción" sortable
                style="min-width: 25rem">
            </Column>
            
            <Column v-if="isColumnSelected('foto')" header="Imagen">
                <template #body="slotProps">
                    <div v-if="slotProps.data.foto && slotProps.data.foto.length > 0" class="flex gap-1">
                        <Image v-for="(imagen, index) in slotProps.data.foto.slice(0, 3)" 
                            :key="index" 
                            :src="imagen" 
                            class="rounded" 
                            alt="Foto" 
                            preview
                            width="40" 
                            height="40" 
                            style="object-fit: cover" />
                        <span v-if="slotProps.data.foto.length > 3" class="text-sm text-gray-500 self-center">
                            +{{ slotProps.data.foto.length - 3 }}
                        </span>
                    </div>
                    <span v-else>Sin imágenes</span>
                </template>
            </Column>

            <Column field="Moneda" header="Moneda" sortable style="min-width: 5rem">
                <template #body="slotProps">
                    <span>{{ slotProps.data.Moneda || '-' }}</span>
                </template>
            </Column>

            <Column field="valor_estimado" header="Valor Estimado" sortable style="min-width: 10rem">
                <template #body="slotProps">
                    <span>{{ formatCurrency(slotProps.data.valor_estimado, slotProps.data.Moneda) }}</span>
                </template>
            </Column>

            <Column field="valor_requerido" header="Valor requerido" sortable style="min-width: 10rem">
                <template #body="slotProps">
                    <span>{{ formatCurrency(slotProps.data.valor_requerido, slotProps.data.Moneda) }}</span>
                </template>
            </Column>
            
            <Column v-if="isColumnSelected('valor_subasta')" field="valor_subasta" header="Valor Subasta" sortable style="min-width: 10rem">
                <template #body="slotProps">
                    <span>{{ formatCurrency(slotProps.data.valor_subasta, slotProps.data.Moneda) }}</span>
                </template>
            </Column>

            <Column field="estado_nombre" header="Estado" style="min-width: 5rem" sortable>
                <template #body="slotProps">
                    <Tag :value="slotProps.data.estado_nombre" :severity="getEstadoSeverity(slotProps.data.estado)" />
                </template>
            </Column>
            
            <Column header="">
                <template #body="slotProps">
                    <Button 
                        icon="pi pi-ellipsis-v" 
                        text 
                        rounded 
                        aria-label="Más opciones"
                        @click="showContextMenu($event, slotProps.data)" 
                    />
                </template>
            </Column>

        </DataTable>
        
        <!-- Menú contextual -->
        <Menu ref="menu" :model="menuItems" popup />
        
        <!-- Modales -->
        <ConfigPropiedades v-model:visible="showModal" :idPropiedad="selectedId" @configuracion-guardada="loadData" />
        <UpdatePropiedades v-model:visible="showUpdateModal" :idPropiedad="selectedId" @propiedad-actualizada="onPropiedadActualizada" />
        <DeletePropiedades v-model:visible="showDeleteModal" :idPropiedad="selectedId" @propiedad-eliminada="onPropiedadEliminada" />
    </template>