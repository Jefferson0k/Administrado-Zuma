<template>
    <Head title="Planes" />
    <AppLayout>
        <div>
            <template v-if="isLoading">
                <Espera />
            </template>

            <template v-else>
                <div class="card">
                    <AddTermPlan @plan-added="handlePlanAdded" />
                    <ListTermPlan :refresh-trigger="refreshTrigger" />
                </div>
            </template>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AppLayout from '@/layout/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Espera from '@/components/Espera.vue';
import AddTermPlan from './Desarrollo/AddTermPlan.vue';
import ListTermPlan from './Desarrollo/ListTermPlan.vue';

const isLoading = ref(true);
const refreshTrigger = ref(0);

const handlePlanAdded = () => {
    refreshTrigger.value++;
};

onMounted(() => {
    setTimeout(() => {
        isLoading.value = false;
    }, 1000);
});
</script>