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
            // === TASAS FIJAS ===
            {
                label: 'Tasas fijas',
                icon: 'pi pi-fw pi-percentage',
                items: [
                    {
                        label: 'Entidades Financieras',
                        icon: 'pi pi-fw pi-briefcase', // mejor que 'building' para representar empresas financieras
                        to: '/tasas-fijas/empresas'
                    },
                    {
                        label: 'Depósitos',
                        icon: 'pi pi-fw pi-wallet', // mejor ícono para depósitos
                        to: '/tasas-fijas/depositos'
                    },
                    {
                        label: 'Pagos',
                        icon: 'pi pi-fw pi-credit-card', // mejor ícono para pagos
                        to: '/tasas-fijas/pagos'
                    },
                    {
                        label: 'Configuración',
                        icon: 'pi pi-fw pi-cog',
                        items: [
                            {
                                label: 'Tipos de Tasa',
                                icon: 'pi pi-fw pi-sliders-h',
                                to: '/tasas-fijas/tipos'
                            },
                            {
                                label: 'Frecuencia de Pago',
                                icon: 'pi pi-fw pi-calendar-clock', // más específico que calendar
                                to: '/Frecuencia/Pagos'
                            },
                        ]
                    },
                ]
            },
            // === SUBASTA DE HIPOTECAS ===
            {
                label: 'Subasta de Hipotecas',
                icon: 'pi pi-fw pi-credit-card',
                items: [
                    {
                        label: 'Registros',
                        icon: 'pi pi-fw pi-folder-open',
                        items: [
                            {
                                label: 'Registro de Inmueble',
                                icon: 'pi pi-fw pi-home',
                                to: '/subasta-hipotecas/propiedades'
                            },
                            {
                                label: 'Reglas del Inmueble',
                                icon: 'pi pi-fw pi-cog',
                                to: '/subasta-hipotecas/reglas'
                            },
                            {
                                label: 'Información del Inversionista',
                                icon: 'pi pi-fw pi-user',
                                to: '/subasta-hipotecas/inversionista'
                            }
                        ]
                    },
                    {
                        label: 'Histórico de Pujas',
                        icon: 'pi pi-fw pi-history',
                        to: '/subasta-hipotecas/historicos'
                    },
                    {
                        label: 'Pagos',
                        icon: 'pi pi-fw pi-money-bill',
                        items: [
                            {
                                label: 'Pago de Entrada',
                                icon: 'pi pi-fw pi-sign-in',
                                to: '/subasta-hipotecas/pagos/entrada'
                            },
                            {
                                label: 'Pago de Salida',
                                icon: 'pi pi-fw pi-sign-out',
                                to: '/subasta-hipotecas/pagos/salida'
                            }
                        ]
                    }
                ]
            },

            // === FACTORING (link externo) ===
            {
                label: 'Factoring',
                icon: 'pi pi-fw pi-external-link',
                url: 'https://fondeoadmin.apros.global/',
                target: '_blank'
            }
        ]
    },

    // === USUARIOS Y ROLES ===
    {
        label: 'Usuarios',
        items: [
            hasPermission('ver usuarios') && {
                label: 'Gestión de Usuarios',
                icon: 'pi pi-fw pi-users',
                to: '/usuario'
            },
            hasPermission('ver roles') && {
                label: 'Roles',
                icon: 'pi pi-fw pi-id-card',
                to: '/roles'
            }
        ].filter(Boolean)
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
