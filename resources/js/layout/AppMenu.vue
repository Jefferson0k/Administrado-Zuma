<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppMenuItem from './AppMenuItem.vue';

const page = usePage();
const permissions = computed(() => page.props.auth.user?.permissions ?? []);
const hasPermission = (perm) => permissions.value.includes(perm);

const model = computed(() => [
    {
        label: 'Home',
        items: [
            { label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/dashboard' }
        ]
    },
    {
        label: 'Productos',
        icon: 'pi pi-fw pi-box',
        to: '/pages',
        items: [
            {
                label: 'Tasas fijas',
                icon: 'pi pi-fw pi-percentage',
                items: [
                    {
                        label: 'Tipo tarifa',
                        icon: 'pi pi-fw pi-sliders-h',
                        to: '/tasas-fijas/tipos'
                    },
                    {
                        label: 'Frecuencia',
                        icon: 'pi pi-fw pi-money-bill',
                        to: '/Frecuencia/Pagos'
                    },
                    {
                        label: 'Planes',
                        icon: 'pi pi-fw pi-sliders-h',
                        to: '/tasas-fijas/planes'
                    },
                    {
                        label: 'Entidades',
                        icon: 'pi pi-fw pi-building',
                        to: '/tasas-fijas/empresas'
                    },
                    {
                        label: 'Depósitos',
                        icon: 'pi pi-fw pi-arrow-circle-down',
                        to: '/tasas-fijas/depositos'
                    },
                    {
                        label: 'Pagos',
                        icon: 'pi pi-fw pi-money-bill',
                        to: '/tasas-fijas/pagos'
                    },
                ]
            },
            {
                label: 'Subasta de Hipotecas',
                icon: 'pi pi-fw pi-credit-card',
                items: [
                    {
                        label: 'Registros',
                        icon: 'pi pi-fw pi-folder-open',
                        items: [
                            {
                                label: 'Registro de inmueble',
                                icon: 'pi pi-fw pi-home',
                                to: '/subasta-hipotecas/propiedades'
                            },
                            {
                                label: 'Reglas',
                                icon: 'pi pi-fw pi-cog',
                                to: '/subasta-hipotecas/reglas'
                            },
                            {
                                label: 'Información del inversionista',
                                icon: 'pi pi-fw pi-user',
                                to: '/subasta-hipotecas/inversionista'
                            },
                        ]
                    },
                    {
                        label: 'Histórico de pujas',
                        icon: 'pi pi-fw pi-history',
                        to: '/subasta-hipotecas/historicos'
                    },
                    {
                        label: 'Pagos',
                        icon: 'pi pi-fw pi-credit-card',
                        to: '/subasta-hipotecas/pagos'
                    },
                ]
            },
            {
                label: 'Factoring',
                icon: 'pi pi-fw pi-external-link',
                url: 'https://fondeoadmin.apros.global/',
                target: '_blank'
            },
        ]
    },
    {
        label: 'Usuarios',
        items: [
            hasPermission('ver usuarios') && { label: 'Gestión de Usuarios', icon: 'pi pi-fw pi-users', to: '/usuario' },
            hasPermission('ver roles') && { label: 'Roles', icon: 'pi pi-fw pi-id-card', to: '/roles' },
        ].filter(Boolean),
    }
].filter(section => section.items.length > 0));
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="i">
            <app-menu-item :item="item" :index="i" />
        </template>
    </ul>
</template>

<style scoped lang="scss">
/* Puedes agregar tus estilos aquí si lo deseas */
</style>
