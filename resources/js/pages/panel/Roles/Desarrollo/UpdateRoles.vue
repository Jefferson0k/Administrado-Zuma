<template>
    <Dialog :visible="visible" @update:visible="emit('update:visible', $event)" :style="{ width: '1000px' }"
        header="Actualizar Roles y Permisos" modal>
        <div class="flex flex-col gap-6">
            <div>
                <label for="name" class="block font-bold mb-3">Nombre <span class="text-red-500">*</span></label>
                <InputText v-model="rolName" fluid required maxlength="100" />
            </div>
            <div>
                <label class="block font-bold mb-3">Permisos <span class="text-red-500">*</span></label>

                <div v-if="loading" class="text-center p-4">
                    <i class="pi pi-spin pi-spinner text-2xl"></i>
                    <p>Cargando permisos...</p>
                </div>

                <div v-else class="permisos-container">
                    <TabView>
                        <!-- FACTORING -->
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
                                            <label :for="'permiso_' + permiso.id" class="cursor-pointer">{{ permiso.name
                                                }}</label>
                                        </div>
                                    </div>
                                </Fieldset>
                            </div>
                        </TabPanel>

                        <!-- HIPOTECAS (propiedades) -->
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
                                            <label :for="'permiso_' + permiso.id" class="cursor-pointer">{{ permiso.name
                                                }}</label>
                                        </div>
                                    </div>
                                </Fieldset>
                            </div>
                        </TabPanel>

                        <!-- BLOG -->
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
                                            <label :for="'permiso_' + permiso.id" class="cursor-pointer">{{ permiso.name
                                                }}</label>
                                        </div>
                                    </div>
                                </Fieldset>
                            </div>
                        </TabPanel>

                        <!-- ADMIN -->
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
                                            <label :for="'permiso_' + permiso.id" class="cursor-pointer">{{ permiso.name
                                                }}</label>
                                        </div>
                                    </div>
                                </Fieldset>
                            </div>
                        </TabPanel>


                        <!-- OTROS -->
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
            <Button label="Cancelar" icon="pi pi-times" text @click="emit('update:visible', false)" />
            <Button label="Guardar" icon="pi pi-check" @click="updateRol" :loading="saving" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Fieldset from 'primevue/fieldset';
import { useToast } from 'primevue/usetoast';


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
    return String(str)
        .toLowerCase()
        .normalize('NFD').replace(/\p{Diacritic}/gu, '')
        .replace(/\s+/g, ' ')
        .trim()
}

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

// Extrae la categoría base desde el nombre del permiso
function getCategoriaFromPermName(name) {
    const parts = normalize(name).split(' ')
    const after = parts.slice(1).filter(w => !STOPWORDS.has(w))
    if (after.length === 0) return 'general'

    // frase completa (compuesto)
    const full = aliasOrSame(after.join(' '))
    if (KNOWN_BASES.has(full)) return full

    // coincidencia más a la derecha
    for (let i = after.length - 1; i >= 0; i--) {
        const w = aliasOrSame(after[i])
        if (KNOWN_BASES.has(w)) return w
    }

    // dos primeros tokens como compuesto (tipo cambio, sub sector)
    if (after.length >= 2) {
        const pair = aliasOrSame(`${after[0]} ${after[1]}`)
        if (KNOWN_BASES.has(pair)) return pair
    }

    // empieza por base conocida
    const first = aliasOrSame(after[0])
    if (KNOWN_BASES.has(first)) return first

    // fallback → “otros”
    return full
}


const props = defineProps({
    RolId: Number,
    visible: Boolean
});

const emit = defineEmits(['update:visible', 'updated']);

const toast = useToast();
const allPermissions = ref([]);
const permisosSeleccionados = ref([]);
const rolName = ref('');
const loading = ref(true);
const saving = ref(false);
const submitted = ref(false);
// Módulos y sus categorías (2ª palabra del permiso)
// Módulos y sus categorías (normalizado a Sets)
const moduloCategoriasRaw = {
    factoring: [
        'empresas', 'factura', 'inversiones', 'inversionistas', 'cuenta',
        'pagos', 'depositos', 'retiros', 'sectores', 'sector', 'tipo'
    ],
    propiedades: [ // pestaña "Hipotecas" en UI
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


const permisosAgrupados = computed(() => {
    const grupos = {}
    if (Array.isArray(allPermissions.value)) {
        allPermissions.value.forEach(p => {
            const cat = getCategoriaFromPermName(p.name)
            if (!grupos[cat]) grupos[cat] = []
            grupos[cat].push(p)
        })
    }
    return grupos
})



// Distribuye los grupos por módulo
// Distribuye los grupos por módulo con fallback "otros"
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


// Ordena categorías según moduloCategorias
// Ordena categorías (respeta tu orden; "otros" va alfabético)
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



watch(() => props.visible, async (val) => {
    if (val && props.RolId) {
        await fetchPermissions();
        await loadRolData(props.RolId);
    }
});

const loadRolData = async (id) => {
    loading.value = true;
    try {
        const res = await axios.get(`/rol/${id}`);
        const rol = res.data;
        rolName.value = rol.name;

        if (rol.permissions && Array.isArray(rol.permissions)) {
            permisosSeleccionados.value = rol.permissions.map(p => p.id);
        } else {
            permisosSeleccionados.value = [];
        }

    } catch (err) {
        console.error('Error al cargar el rol:', err);
    } finally {
        loading.value = false;
    }
};

const fetchPermissions = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/rol/Permisos');

        if (res.data && Array.isArray(res.data.permissions)) {
            allPermissions.value = res.data.permissions;
        } else if (res.data && Array.isArray(res.data)) {
            allPermissions.value = res.data;
        } else {
            allPermissions.value = [];
            console.error('Formato de respuesta inesperado:', res.data);
        }
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Error al obtener permisos', life: 3000 });
        allPermissions.value = [];
    } finally {
        loading.value = false;
    }
};

const updateRol = async () => {
    submitted.value = true;

    if (permisosSeleccionados.value.length === 0) {
        return;
    }

    saving.value = true;
    try {
        await axios.put(`/rol/${props.RolId}`, {
            name: rolName.value,
            permissions: permisosSeleccionados.value
        });
        toast.add({ severity: 'success', summary: 'Actualizado', detail: 'Rol y Permiso actualizado correctamente', life: 3000 });
        emit('updated');
        emit('update:visible', false);
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Error al actualizar el rol', life: 3000 });
        console.error('Error al actualizar el rol:', err);
    } finally {
        saving.value = false;
    }
};

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

onMounted(async () => {
    if (props.visible && props.RolId) {
        await fetchPermissions();
        await loadRolData(props.RolId);
    }
});
</script>