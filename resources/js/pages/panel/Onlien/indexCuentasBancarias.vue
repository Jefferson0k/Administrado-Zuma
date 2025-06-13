<template>
    <Head title="Propiedades Subastadas" />
    <AppLayout>
        <div>
            <template v-if="isLoading">
                <Espera />
            </template>
            <template v-else>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-4">
                    <Card
                        v-for="item in propiedades"
                        :key="item.id"
                        style="width: 100%; overflow: hidden"
                        class="shadow-lg"
                    >
                        <template #header>
                            <img :src="item.foto" alt="Imagen propiedad" class="w-full h-48 object-cover" />
                        </template>

                        <template #title>{{ item.nombre }}</template>
                        <template #subtitle>{{ item.distrito }}</template>

                        <template #content>
                            <p class="text-sm text-gray-700 line-clamp-3">
                                {{ item.descripcion }}
                            </p>
                            <p class="mt-2 font-semibold text-green-700">S/ {{ item.monto }}</p>
                            <p class="text-xs text-gray-500">Finaliza: {{ item.finalizacion }}</p>
                            <p class="text-xs text-gray-500 mt-2">
                                Usuario tiene: S/ {{ userMonto }} |
                                Puede invertir: {{ puedeInvertir(item.monto) ? 'Sí' : 'No' }}
                            </p>
                        </template>

                        <template #footer>
                            <div class="flex justify-center">
                                <div v-if="yaInvertio(item.id)" class="w-full text-center">
                                    <p class="text-green-600 font-semibold text-sm bg-green-50 py-2 px-4 rounded">
                                        ✅ Ya invertiste en esta propiedad
                                    </p>
                                </div>

                                <Button
                                    v-else
                                    label="Invertir"
                                    icon="pi pi-wallet"
                                    class="w-full"
                                    :disabled="!puedeInvertir(item.monto)"
                                    :class="{ 
                                        'opacity-50 cursor-not-allowed': !puedeInvertir(item.monto),
                                        'bg-red-500 border-red-500': !puedeInvertir(item.monto)
                                    }"
                                    @click="handleInvertir(item.id)"
                                />
                            </div>
                        </template>
                    </Card>
                </div>
            </template>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import AppLayout from '@/layout/AppLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import Espera from '@/components/Espera.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';

const propiedades = ref([]);
const isLoading = ref(true);

// Datos del usuario
const page = usePage();
const userMonto = computed(() => {
    const monto = page.props.auth.user?.monto || 0;
    return Number(monto); // Asegura que sea numérico
});

// Cargar propiedades desde API
const fetchPropiedades = async () => {
    try {
        const response = await axios.get('/api/subastadas');
        propiedades.value = response.data.data.map(p => ({
            ...p,
            monto: Number(p.monto), // Asegura que sea número
        }));
    } catch (error) {
        console.error('Error cargando propiedades:', error);
    } finally {
        isLoading.value = false;
    }
};

// Verifica si el usuario puede invertir
const puedeInvertir = (montoPropiedad) => {
    const monto = Number(montoPropiedad);
    return userMonto.value >= monto;
};

// Verifica si ya invirtió en una propiedad
const yaInvertio = (propertyId) => {
    const propiedad = propiedades.value.find(p => p.id === propertyId);
    return propiedad?.ya_invertido || false;
};

// Acción al hacer clic en "Invertir"
const handleInvertir = (propertyId) => {
    const propiedad = propiedades.value.find(p => p.id === propertyId);

    if (!puedeInvertir(propiedad.monto)) {
        alert('No tienes suficiente monto para invertir en esta propiedad');
        return;
    }

    if (yaInvertio(propertyId)) {
        alert('Ya has invertido en esta propiedad');
        return;
    }

    router.visit(`/property/${propertyId}`);
};

onMounted(() => {
    fetchPropiedades();
});
</script>
