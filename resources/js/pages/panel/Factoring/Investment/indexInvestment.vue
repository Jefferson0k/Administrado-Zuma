<template>
    <Head title="Inversionista" />
    <AppLayout>
        <div>
            <template v-if="isLoading">
                <Espera />
            </template>
            <template v-else>
                <div class="card" v-if="invoice">
                    <listInvestment :invoice="invoice" :investments="investments" />
                </div>
                <div v-else>
                    <p>No se encontró la factura.</p>
                </div>
            </template>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/layout/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Espera from '@/components/Espera.vue';
import listInvestment from './Desarrollo/listInvestment.vue';

const isLoading = ref(true);

const page = usePage<{ invoice?: any, investments?: any[] }>();
const invoice = ref(page.props.invoice || null);
const investments = ref(page.props.investments || []);

onMounted(() => {
    setTimeout(() => {
        isLoading.value = false;
    }, 500);
});
</script>
