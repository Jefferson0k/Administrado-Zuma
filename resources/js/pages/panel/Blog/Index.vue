<template>

    <Head title="Blog" />
    <AppLayout>
        <template v-if="isLoading">
            <Espera />
        </template>

        <template v-else>
            <div class="card">
                <!--<AddPost @agregado="refrescar" />-->
                <ListPost :user="user" :refresh="refreshKey" />
            </div>
        </template>
    </AppLayout>
</template>

<script setup lang="ts">
import Espera from '@/components/Espera.vue';
import AppLayout from '@/layout/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import AddPost from './components/AddPost.vue';
import ListPost from './components/ListPost.vue';
import { usePage } from '@inertiajs/vue3'

const { props } = usePage()
const user = props.user
const refreshKey = ref(0);
const isLoading = ref(true);

function refrescar() {
    refreshKey.value++;
}

onMounted(() => {
    setTimeout(() => {
        isLoading.value = false;
    }, 1000);
});
</script>