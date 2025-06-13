<script setup>
import { ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/layout/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Espera from '@/components/Espera.vue';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

const props = defineProps({
    property: Object
});

const isLoading = ref(true);
const monto = ref(null);
const isInvesting = ref(false);
const hasInvested = ref(false);
const toast = useToast();

const page = usePage();
const propertyResponse = props.property || page.props.property;

const property = propertyResponse.data;

const isValidMonto = () => {
    if (!monto.value) {
        toast.add({
            severity: 'warn',
            summary: 'Monto requerido',
            detail: 'Por favor, ingrese un monto para invertir.',
            life: 3000
        });
        return false;
    }
    
    if (monto.value <= 0) {
        toast.add({
            severity: 'warn',
            summary: 'Monto inválido',
            detail: 'El monto debe ser mayor a cero.',
            life: 3000
        });
        return false;
    }
    
    return true;
};

const handleInvertir = async () => {
    if (!isValidMonto()) return;
    
    if (hasInvested.value) {
        toast.add({
            severity: 'info',
            summary: 'Ya has invertido',
            detail: 'Ya has realizado una inversión en esta propiedad.',
            life: 3000
        });
        return;
    }

    isInvesting.value = true;

    try {
        const response = await axios.post('/api/investments', {
            property_id: property.id,
            monto_invertido: monto.value
        });

        toast.add({
            severity: 'success',
            summary: 'Inversión exitosa',
            detail: response.data.message || 'Inversión registrada exitosamente.',
            life: 5000
        });
        
        hasInvested.value = true;
        monto.value = null;
        
    } catch (error) {
        console.error('Error al invertir:', error);
        
        let errorMessage = 'Error al procesar la inversión. Intente nuevamente.';
        let severity = 'error';
        
        if (error.response && error.response.data) {
            errorMessage = error.response.data.message;
            
            if (error.response.status === 422) {
                severity = 'warn'; 
            } else if (error.response.status === 401) {
                severity = 'error';
                errorMessage = 'Debe iniciar sesión para invertir.';
            }
        } else if (error.request) {
            errorMessage = 'Error de conexión. Verifique su internet.';
        }
        
        toast.add({
            severity: severity,
            summary: severity === 'warn' ? 'Fondos insuficientes' : 'Error de inversión',
            detail: errorMessage,
            life: 5000
        });
    } finally {
        isInvesting.value = false;
    }
};

onMounted(() => {
    console.log('Property response:', propertyResponse);
    console.log('Property data:', property);
    isLoading.value = false;
});
</script>

<template>
    <Head title="Detalle de Subasta" />
    <AppLayout>
        <Toast />
        <div>
            <template v-if="isLoading">
                <Espera />
            </template>

            <template v-else>
                <div class="card">
                    <!-- Estado de inversión -->
                    <div v-if="hasInvested" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="pi pi-check-circle text-green-600 mr-2"></i>
                            <span class="text-green-800 font-medium">Ya has invertido en esta propiedad</span>
                        </div>
                    </div>

                    <!-- Mostrar datos de la propiedad -->
                    <div class="property-details">
                        <h2 class="text-2xl font-bold mb-4">{{ property.nombre }}</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <img :src="property.foto" :alt="property.nombre" class="w-full h-64 object-cover rounded-lg" />
                            </div>
                            
                            <div class="space-y-3">
                                <div>
                                    <label class="font-semibold">Distrito:</label>
                                    <p>{{ property.distrito }}</p>
                                </div>
                                
                                <div>
                                    <label class="font-semibold">Descripción:</label>
                                    <p>{{ property.descripcion }}</p>
                                </div>
                                
                                <div>
                                    <label class="font-semibold">Monto Inicial:</label>
                                    <p class="text-green-600 font-bold">S/ {{ property.monto?.toLocaleString('es-PE') }}</p>
                                </div>
                                
                                <div>
                                    <label class="font-semibold">Finalización:</label>
                                    <p>{{ property.finalizacion }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label class="block text-sm font-medium mb-1">Monto a invertir (S/)</label>
                        <InputNumber 
                            v-model="monto" 
                            inputClass="w-full" 
                            mode="currency" 
                            currency="PEN" 
                            locale="es-PE" 
                            fluid
                            :disabled="hasInvested"
                            placeholder="Ingrese el monto a invertir"
                        />
                        <Button 
                            :label="hasInvested ? 'Ya invertiste' : 'Invertir'" 
                            :icon="hasInvested ? 'pi pi-check' : 'pi pi-wallet'" 
                            class="mt-4 w-full" 
                            @click="handleInvertir"
                            :loading="isInvesting"
                            :disabled="isInvesting || hasInvested"
                            :severity="hasInvested ? 'success' : 'primary'"
                        />
                        
                        <!-- Texto de ayuda -->
                        <p v-if="!hasInvested" class="text-sm text-gray-500 mt-2">
                            * Una vez realizada la inversión, no podrás modificarla.
                        </p>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>