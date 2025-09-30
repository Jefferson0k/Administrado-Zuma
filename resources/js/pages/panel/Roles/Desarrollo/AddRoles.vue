<template>
    <Toolbar class="mb-6">
        <template #start>
            <Button label="New" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" />
        </template>
        <template #end>

        </template>
    </Toolbar>

    <Dialog v-model:visible="rolDialog" :style="{ width: '1000px' }" header="Roles y Permisos" :modal="true">
        <div class="flex flex-col gap-6">
            <div>
                <label for="name" class="block font-bold mb-3">Nombre <span class="text-red-500">*</span></label>
                <InputText id="name" v-model.trim="rol.name" required maxlength="100" fluid />
                <small v-if="submitted && !rol.name" class="text-red-500">El nombre es obligatorio.</small>
                <small v-else-if="submitted && rol.name && rol.name.length < 2" class="text-red-500">
                    El nombre debe tener al menos 2 caracteres.
                </small>
                <small v-else-if="serverErrors.name" class="text-red-500">{{ serverErrors.name[0] }}</small>
            </div>
            <div>
                <label class="block font-bold mb-3">Permisos <span class="text-red-500">*</span></label>

                <div v-if="loadingPermissions" class="text-center p-4">
                    <i class="pi pi-spin pi-spinner text-2xl"></i>
                    <p>Cargando permisos...</p>
                </div>

                <div v-else class="permisos-container">
                    <TabView>

                        <!-- Pestaña: FACTORING -->
                        <TabPanel header="Factoring">
                            <div v-if="Object.keys(permisosPorModuloOrdenado.factoring).length === 0"
                                class="text-sm text-gray-500 p-2">
                                No hay permisos de este módulo.
                            </div>

                            <div v-for="(permisos, categoria) in permisosPorModuloOrdenado.factoring"
                                :key="'factoring-' + categoria" class="mb-4">
                                <Fieldset :toggleable="true" class="shadow-sm">
                                    <template #legend>
                                        <div class="flex justify-between items-center w-full">
                                            <span class="font-bold capitalize">{{ categoria }}</span>
                                            <div class="fieldset-actions flex gap-2">
                                                <Button icon="pi pi-check-square" size="small" text
                                                    @click.stop="seleccionarTodos(categoria)"
                                                    v-tooltip="{ value: 'Seleccionar todos', showDelay: 1000, hideDelay: 300 }" />
                                                <Button icon="pi pi-times" severity="danger" size="small" text
                                                    @click.stop="deseleccionarTodos(categoria)"
                                                    v-tooltip="{ value: 'Deseleccionar todos', showDelay: 1000, hideDelay: 300 }"
                                                    tooltipOptions="{ position: 'top' }" />
                                            </div>
                                        </div>
                                    </template>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        <div v-for="permiso of permisos" :key="permiso.id"
                                            class="flex items-center gap-2 p-2 rounded-md">
                                            <Checkbox v-model="permisosSeleccionados" :inputId="'permiso_' + permiso.id"
                                                :value="permiso.id" />
                                            <label :for="'permiso_' + permiso.id" class="cursor-pointer">
                                                {{ permiso.name }}
                                            </label>
                                        </div>
                                    </div>
                                </Fieldset>
                            </div>
                        </TabPanel>

                        <!-- Pestaña: PROPIEDADES -->
                        <TabPanel header="Hipotecas">
                            <div v-if="Object.keys(permisosPorModuloOrdenado.propiedades).length === 0"
                                class="text-sm text-gray-500 p-2">
                                No hay permisos de este módulo.
                            </div>


                            <div v-for="(permisos, categoria) in permisosPorModuloOrdenado.propiedades"
                                :key="'propiedades-' + categoria" class="mb-4">
                                <Fieldset :toggleable="true" class="shadow-sm">
                                    <template #legend>
                                        <div class="flex justify-between items-center w-full">
                                            <span class="font-bold capitalize">{{ categoria }}</span>
                                            <div class="fieldset-actions flex gap-2">
                                                <Button icon="pi pi-check-square" size="small" text
                                                    @click.stop="seleccionarTodos(categoria)"
                                                    v-tooltip="{ value: 'Seleccionar todos', showDelay: 1000, hideDelay: 300 }" />
                                                <Button icon="pi pi-times" severity="danger" size="small" text
                                                    @click.stop="deseleccionarTodos(categoria)"
                                                    v-tooltip="{ value: 'Deseleccionar todos', showDelay: 1000, hideDelay: 300 }"
                                                    tooltipOptions="{ position: 'top' }" />
                                            </div>
                                        </div>
                                    </template>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        <div v-for="permiso of permisos" :key="permiso.id"
                                            class="flex items-center gap-2 p-2 rounded-md">
                                            <Checkbox v-model="permisosSeleccionados" :inputId="'permiso_' + permiso.id"
                                                :value="permiso.id" />
                                            <label :for="'permiso_' + permiso.id" class="cursor-pointer">
                                                {{ permiso.name }}
                                            </label>
                                        </div>
                                    </div>
                                </Fieldset>
                            </div>
                        </TabPanel>

                        <!-- Pestaña: BLOG -->
                        <TabPanel header="Blog">
                            <div v-if="Object.keys(permisosPorModuloOrdenado.blog).length === 0"
                                class="text-sm text-gray-500 p-2">
                                No hay permisos de este módulo.
                            </div>

                            <div v-for="(permisos, categoria) in permisosPorModuloOrdenado.blog"
                                :key="'blog-' + categoria" class="mb-4">
                                <Fieldset :toggleable="true" class="shadow-sm">
                                    <template #legend>
                                        <div class="flex justify-between items-center w-full">
                                            <span class="font-bold capitalize">{{ categoria }}</span>
                                            <div class="fieldset-actions flex gap-2">
                                                <Button icon="pi pi-check-square" size="small" text
                                                    @click.stop="seleccionarTodos(categoria)"
                                                    v-tooltip="{ value: 'Seleccionar todos', showDelay: 1000, hideDelay: 300 }" />
                                                <Button icon="pi pi-times" severity="danger" size="small" text
                                                    @click.stop="deseleccionarTodos(categoria)"
                                                    v-tooltip="{ value: 'Deseleccionar todos', showDelay: 1000, hideDelay: 300 }"
                                                    tooltipOptions="{ position: 'top' }" />
                                            </div>
                                        </div>
                                    </template>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        <div v-for="permiso of permisos" :key="permiso.id"
                                            class="flex items-center gap-2 p-2 rounded-md">
                                            <Checkbox v-model="permisosSeleccionados" :inputId="'permiso_' + permiso.id"
                                                :value="permiso.id" />
                                            <label :for="'permiso_' + permiso.id" class="cursor-pointer">
                                                {{ permiso.name }}
                                            </label>
                                        </div>
                                    </div>
                                </Fieldset>
                            </div>
                        </TabPanel>




                        <!-- Pestaña: ADMIN -->
                        <TabPanel header="Admin">
                            <div v-if="Object.keys(permisosPorModuloOrdenado.admin).length === 0"
                                class="text-sm text-gray-500 p-2">
                                No hay permisos de este módulo.
                            </div>

                            <div v-for="(permisos, categoria) in permisosPorModuloOrdenado.admin"
                                :key="'admin-' + categoria" class="mb-4">
                                <Fieldset :toggleable="true" class="shadow-sm">
                                    <template #legend>
                                        <div class="flex justify-between items-center w-full">
                                            <span class="font-bold capitalize">{{ categoria }}</span>
                                            <div class="fieldset-actions flex gap-2">
                                                <Button icon="pi pi-check-square" size="small" text
                                                    @click.stop="seleccionarTodos(categoria)"
                                                    v-tooltip="{ value: 'Seleccionar todos', showDelay: 1000, hideDelay: 300 }" />
                                                <Button icon="pi pi-times" severity="danger" size="small" text
                                                    @click.stop="deseleccionarTodos(categoria)"
                                                    v-tooltip="{ value: 'Deseleccionar todos', showDelay: 1000, hideDelay: 300 }"
                                                    tooltipOptions="{ position: 'top' }" />
                                            </div>
                                        </div>
                                    </template>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        <div v-for="permiso of permisos" :key="permiso.id"
                                            class="flex items-center gap-2 p-2 rounded-md">
                                            <Checkbox v-model="permisosSeleccionados" :inputId="'permiso_' + permiso.id"
                                                :value="permiso.id" />
                                            <label :for="'permiso_' + permiso.id" class="cursor-pointer">
                                                {{ permiso.name }}
                                            </label>
                                        </div>
                                    </div>
                                </Fieldset>
                            </div>
                        </TabPanel>


                        <!-- Pestaña: OTROS -->
                        <TabPanel v-if="Object.keys(permisosPorModuloOrdenado.otros).length" header="Otros">


                            <div v-for="(permisos, categoria) in permisosPorModuloOrdenado.otros"
                                :key="'otros-' + categoria" class="mb-4">
                                <Fieldset :toggleable="true" class="shadow-sm">
                                    <template #legend>
                                        <div class="flex justify-between items-center w-full">
                                            <span class="font-bold capitalize">{{ categoria }}</span>
                                            <div class="fieldset-actions flex gap-2">
                                                <Button icon="pi pi-check-square" size="small" text
                                                    @click.stop="seleccionarTodos(categoria)" />
                                                <Button icon="pi pi-times" severity="danger" size="small" text
                                                    @click.stop="deseleccionarTodos(categoria)" />
                                            </div>
                                        </div>
                                    </template>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        <div v-for="permiso of permisos" :key="permiso.id"
                                            class="flex items-center gap-2 p-2 rounded-md">
                                            <Checkbox v-model="permisosSeleccionados" :inputId="'permiso_' + permiso.id"
                                                :value="permiso.id" />
                                            <label :for="'permiso_' + permiso.id" class="cursor-pointer">{{ permiso.name
                                                }}</label>
                                        </div>
                                    </div>
                                </Fieldset>
                            </div>
                        </TabPanel>


                    </TabView>

                    <small v-if="submitted && permisosSeleccionados.length === 0" class="text-red-500 block mt-2">
                        Debe seleccionar al menos un permiso
                    </small>
                </div>

            </div>
        </div>

        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog" />
            <Button label="Guardar" icon="pi pi-check" @click="guardarRol" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';
import { useToast } from 'primevue/usetoast';
import { defineEmits } from 'vue';
import Fieldset from 'primevue/fieldset';

import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';

// === Helpers de normalización y alias ===
const STOPWORDS = new Set(['de', 'del', 'la', 'las', 'los', 'y', 'para', 'por', 'al', 'el'])
const ALIAS = {
    // Factoring
    empresa: 'empresas', empresas: 'empresas',
    factura: 'factura', facturas: 'factura',
    inversion: 'inversiones', inversiones: 'inversiones',
    inversionista: 'inversionistas', inversionistas: 'inversionistas',
    cuenta: 'cuenta', cuentas: 'cuenta',
    'cuenta bancaria': 'cuenta', 'cuentas bancarias': 'cuenta',
    pago: 'pagos', pagos: 'pagos',
    deposito: 'depositos', depositos: 'depositos',
    retiro: 'retiros', retiros: 'retiros',
    sector: 'sector', sectores: 'sectores',
    tipo: 'tipo', 'tipo cambio': 'tipo',

    // Hipotecas / Propiedades
    subasta: 'subasta', subastas: 'subasta',
    informacion: 'informacion', información: 'informacion',
    calendario: 'calendario',
    'calendario pagos': 'calendario', 'calendario de pagos': 'calendario',
    inversionista_propiedad: 'inversionista',
    reglas: 'reglas', regla: 'reglas',
    imagen: 'imagenes', imagenes: 'imagenes', imágenes: 'imagenes',
    propiedad: 'propiedades', propiedades: 'propiedades',
    cargo: 'cargo',
    sub: 'sub', 'sub sector': 'sub', 'subsector': 'sub',

    // Blog
    post: 'posts', posts: 'posts',
    categoria: 'categorias', categorias: 'categorias', categorías: 'categorias',

    // Admin
    usuario: 'usuarios', usuarios: 'usuarios',
    rol: 'roles', roles: 'roles',
    permiso: 'permisos', permisos: 'permisos'
}

function normalize(str) {
    return str
        .toLowerCase()
        .normalize('NFD').replace(/\p{Diacritic}/gu, '')
        .replace(/\s+/g, ' ')
        .trim()
}

// Conjunto de "bases" conocidas por módulo para detectar dentro de frases largas
const KNOWN_BASES = new Set([
    'empresas', 'factura', 'inversiones', 'inversionistas', 'cuenta', 'pagos',
    'depositos', 'retiros', 'sectores', 'sector', 'tipo',
    'subasta', 'informacion', 'calendario', 'inversionista', 'reglas', 'imagenes', 'propiedades', 'cargo', 'sub',
    'posts', 'categorias',
    'usuarios', 'roles', 'permisos'
])

function aliasOrSame(key) {
    const k = normalize(key)
    return ALIAS[k] || k
}

// Dada una frase de permiso, extrae la categoría "base"
function getCategoriaFromPermName(name) {
    const parts = normalize(name).split(' ')
    // Todo lo que va después de la acción (primera palabra) es la "frase de categoría"
    const after = parts.slice(1)
    // Limpia stopwords, pero conserva el orden
    const cleaned = after.filter(w => !STOPWORDS.has(w))
    if (cleaned.length === 0) return 'general'

    // 1) Intenta alias exacto de la frase completa (soporta compuestos)
    const full = aliasOrSame(cleaned.join(' '))
    if (KNOWN_BASES.has(full)) return full

    // 2) Coincidencia conocida más a la derecha (prioriza último término fuerte)
    for (let i = cleaned.length - 1; i >= 0; i--) {
        const word = aliasOrSame(cleaned[i])
        if (KNOWN_BASES.has(word)) return word
    }

    // 3) Dos primeros tokens como compuesto (ej. "tipo cambio", "sub sector")
    if (cleaned.length >= 2) {
        const pair = aliasOrSame(`${cleaned[0]} ${cleaned[1]}`)
        if (KNOWN_BASES.has(pair)) return pair
    }

    // 4) Si empieza por una base conocida (ej. "reglas del imueble" -> "reglas")
    const first = aliasOrSame(cleaned[0])
    if (KNOWN_BASES.has(first)) return first

    // 5) Último recurso: devuelve la frase limpia (aparecerá en "otros")
    return full
}


const toast = useToast();
const submitted = ref(false);
const rolDialog = ref(false);
const selectedrols = ref();
const serverErrors = ref({});
const emit = defineEmits(['rol-agregado']);
const rol = ref({
    name: '',
});

const permisos = ref([]);
const permisosSeleccionados = ref([]);
const loadingPermissions = ref(false);


const moduloCategoriasRaw = {
    factoring: [
        'empresas', 'factura', 'inversiones', 'inversionistas', 'cuenta', 'pagos',
        'depositos', 'retiros', 'sectores', 'sector', 'tipo'
    ],
    propiedades: [
        'subasta', 'informacion', 'calendario', 'inversionista',
        'reglas', 'imagenes', 'propiedades', 'cargo', 'sub'
    ],
    blog: ['posts', 'categorias'],
    admin: ['usuarios', 'roles', 'permisos']
}
const moduloCategorias = Object.fromEntries(
    Object.entries(moduloCategoriasRaw).map(([mod, arr]) => [
        mod,
        new Set(arr.map(k => aliasOrSame(k)))
    ])
)



async function obtenerPermisos() {
    loadingPermissions.value = true;
    try {
        const response = await axios.get('/rol/Permisos');
        permisos.value = response.data.permissions;
        console.log(permisos.value)
    } catch (error) {
        console.error('Error al obtener permisos:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudieron cargar los permisos',
            life: 3000
        });
    } finally {
        loadingPermissions.value = false;
    }
}

const permisosAgrupados = computed(() => {
    const grupos = {}
    permisos.value.forEach(p => {
        const cat = getCategoriaFromPermName(p.name)
        if (!grupos[cat]) grupos[cat] = []
        grupos[cat].push(p)
    })
    return grupos
})


// Reparte los grupos por módulo usando moduloCategorias
const permisosPorModulo = computed(() => {
    const res = { factoring: {}, propiedades: {}, blog: {}, admin: {}, otros: {} }
    Object.entries(permisosAgrupados.value).forEach(([cat, lista]) => {
        let assigned = false
        for (const [mod, setCats] of Object.entries(moduloCategorias)) {
            if (setCats.has(cat)) { res[mod][cat] = lista; assigned = true; break }
        }
        if (!assigned) {
            res.otros[cat] = lista
            console.warn('[Permisos sin módulo]:', cat, lista.map(p => p.name))
        }
    })
    return res
})



const permisosPorModuloOrdenado = computed(() => {
    const res = { factoring: {}, propiedades: {}, blog: {}, admin: {}, otros: {} }
    for (const modulo of Object.keys(res)) {
        const cats = Object.keys(permisosPorModulo.value[modulo] || {})
        const ordenRef = modulo === 'otros' ? [] : Array.from(moduloCategorias[modulo] || [])
        cats.sort((a, b) => {
            const ia = ordenRef.indexOf(a), ib = ordenRef.indexOf(b)
            if (ia !== -1 && ib !== -1) return ia - ib
            if (ia !== -1) return -1
            if (ib !== -1) return 1
            return a.localeCompare(b)
        }).forEach(cat => { res[modulo][cat] = permisosPorModulo.value[modulo][cat] })
    }
    return res
})



function seleccionarTodos(categoria) {
    const nuevosSeleccionados = [...permisosSeleccionados.value];
    const permisosCategoria = permisosAgrupados.value[categoria].map(p => p.id);
    permisosCategoria.forEach(id => {
        if (!nuevosSeleccionados.includes(id)) {
            nuevosSeleccionados.push(id);
        }
    });
    permisosSeleccionados.value = nuevosSeleccionados;
}

function deseleccionarTodos(categoria) {
    const permisosCategoria = permisosAgrupados.value[categoria].map(p => p.id);
    permisosSeleccionados.value = permisosSeleccionados.value.filter(
        id => !permisosCategoria.includes(id)
    );
}

function openNew() {
    rol.value = {
        name: '',
    };
    permisosSeleccionados.value = [];
    submitted.value = false;
    serverErrors.value = {};
    rolDialog.value = true;
}

function hideDialog() {
    rolDialog.value = false;
    submitted.value = false;
    serverErrors.value = {};
}

function guardarRol() {
    submitted.value = true;

    if (rol.value.name && rol.value.name.length >= 2 && permisosSeleccionados.value.length > 0) {
        axios.post('/rol', {
            name: rol.value.name,
            permissions: permisosSeleccionados.value
        })
            .then(response => {
                toast.add({
                    severity: 'success',
                    summary: 'Éxito',
                    detail: 'Rol guardado correctamente',
                    life: 3000
                });
                hideDialog();
                emit('rol-agregado');
            })
            .catch(error => {
                if (error.response && error.response.data && error.response.data.errors) {
                    serverErrors.value = error.response.data.errors;
                } else {
                    toast.add({
                        severity: 'error',
                        summary: 'Error',
                        detail: 'Error al guardar el rol',
                        life: 3000
                    });
                }
            });
    }
}

onMounted(() => {
    obtenerPermisos();
});
</script>

<style scoped>
/* Puedes añadir estilos adicionales aquí si es necesario */
</style>