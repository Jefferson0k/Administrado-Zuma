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
                        label: 'Inversionista',
                        icon: 'pi pi-fw pi-briefcase',
                        to: '/tasas-fijas/inversionistas'
                    },
                    {
                        label: 'Empresa',
                        icon: 'pi pi-fw pi-building',
                        to: '/tasas-fijas/empresas'
                    },
                    {
                        label: 'Facturas',
                        icon: 'pi pi-fw pi-file',
                        to: '/tasas-fijas/facturas'
                    },
                    {
                        label: 'Cuentas bancarias',
                        icon: 'pi pi-fw pi-wallet',
                        to: '/tasas-fijas/cuentas-bancarias'
                    },
                    {
                        label: 'Inversiones',
                        icon: 'pi pi-fw pi-chart-line',
                        to: '/tasas-fijas/inversiones'
                    },
                    {
                        label: 'Depósitos',
                        icon: 'pi pi-fw pi-wallet',
                        to: '/tasas-fijas/depositos'
                    },
                    {
                        label: 'Pagos',
                        icon: 'pi pi-fw pi-money-bill',
                        to: '/tasas-fijas/pagos'
                    },
                    {
                        label: 'Retiros',
                        icon: 'pi pi-fw pi-arrow-circle-down',
                        to: '/tasas-fijas/retiros'
                    },
                    {
                        label: 'Tipo de cambio',
                        icon: 'pi pi-fw pi-refresh',
                        to: '/tasas-fijas/tipo-cambio'
                    },
                ]
            },
            {
                label: 'Subasta de Hipotecas',
                icon: 'pi pi-fw pi-credit-card',
                items: [
                    {
                        label: 'Inversionista',
                        icon: 'pi pi-fw pi-briefcase',
                        to: '/subasta-hipotecas/inversionistas'
                    },
                    {
                        label: 'Propiedades',
                        icon: 'pi pi-fw pi-home',
                        to: '/subasta-hipotecas/propiedades'
                    },
                    {
                        label: 'Cuentas bancarias',
                        icon: 'pi pi-fw pi-wallet',
                        to: '/subasta-hipotecas/cuentas-bancarias'
                    },
                    {
                        label: 'Historico de pujas',
                        icon: 'pi pi-fw pi-history',
                        to: '/subasta-hipotecas/historicos'
                    },
                    {
                        label: 'Depósitos',
                        icon: 'pi pi-fw pi-wallet',
                        to: '/subasta-hipotecas/depositos'
                    },
                    {
                        label: 'Pagos',
                        icon: 'pi pi-fw pi-credit-card',
                        to: '/subasta-hipotecas/pagos'
                    },
                    {
                        label: 'Retiros',
                        icon: 'pi pi-fw pi-arrow-circle-down',
                        to: '/subasta-hipotecas/retiros'
                    },
                    {
                        label: 'Tipo de cambio',
                        icon: 'pi pi-fw pi-refresh',
                        to: '/subasta-hipotecas/tipo-cambio'
                    },
                ]
            },
            {
                label: 'Factoring',
                icon: 'pi pi-fw pi-external-link',
                url: 'https://fondeoadmin.apros.global/',
                target: '_blank'
            }
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
