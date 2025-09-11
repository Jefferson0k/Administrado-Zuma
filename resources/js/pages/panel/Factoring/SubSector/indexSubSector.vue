<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layout/AppLayout.vue';
import Espera from '@/components/Espera.vue';
import AddSubSectores from './Desarrollo/addSubSectores.vue';
import ListSubSectores from './Desarrollo/listSubSectores.vue';

const props = defineProps({
    sector: Object,
});

const isLoading = ref(true);
const refreshKey = ref(0);

function refrescarListado() {
    refreshKey.value++;
}

onMounted(() => {
    setTimeout(() => {
        isLoading.value = false;
    }, 1000);
});
</script>

<template>
    <Head title="Sub Sectores" />
    <AppLayout>
        <div>
            <template v-if="isLoading">
                <Espera />
            </template>
            <template v-else>
                <div class="card">
                    <AddSubSectores :sector="sector.data" @agregado="refrescarListado" />
                    <ListSubSectores :sector-id="sector.data.id" :refresh="refreshKey" />
                </div>
            </template>
        </div>
    </AppLayout>
</template>
