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
                                icon: 'pi pi-fw pi-calendar-clock',
                                to: '/Frecuencia/Pagos'
                            },
                        ]
                    },
                    {
                        label: 'Entidades Financieras',
                        icon: 'pi pi-fw pi-briefcase',
                        to: '/tasas-fijas/empresas'
                    },
                    {
                        label: 'Depósitos',
                        icon: 'pi pi-fw pi-wallet',
                        to: '/tasas-fijas/depositos'
                    },
                    {
                        label: 'Pagos',
                        icon: 'pi pi-fw pi-credit-card',
                        to: '/tasas-fijas/pagos'
                    },
                ]
            },
            {
                label: 'Subasta de Hipotecas',
                icon: 'pi pi-fw pi-home',
                items: [
                    {
                        label: 'Registros',
                        icon: 'pi pi-fw pi-folder-open',
                        items: [
                            {
                                label: 'Registro de Inmueble',
                                icon: 'pi pi-fw pi-building',
                                to: '/subasta-hipotecas/propiedades'
                            },
                            {
                                label: 'Reglas del Inmueble',
                                icon: 'pi pi-fw pi-sliders-h',
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
                        label: 'Reservas',
                        icon: 'pi pi-fw pi-calendar-plus',
                        to: '/subasta-hipotecas/reserva'
                    },
                    {
                        label: 'Histórico de Pujas',
                        icon: 'pi pi-fw pi-chart-line',
                        to: '/subasta-hipotecas/historicos'
                    },
                    {
                        label: 'Pagos',
                        icon: 'pi pi-fw pi-wallet',
                        items: [
                            {
                                label: 'Depósitos',
                                icon: 'pi pi-fw pi-arrow-down',
                                to: '/subasta-hipotecas/pagos'
                            },
                            {
                                label: 'Pago de Entrada',
                                icon: 'pi pi-fw pi-arrow-circle-down',
                                to: '/subasta-hipotecas/cliente/pagos'
                            },
                            {
                                label: 'Pago de Salida',
                                icon: 'pi pi-fw pi-arrow-circle-up',
                                to: '/subasta-hipotecas/inversionista/pagos'
                            }
                        ]
                    }
                ]
            },

            {
                label: 'Factoring',
                icon: 'pi pi-fw pi-external-link',
                url: 'https://backoffice.zuma.com.pe/',
                target: '_blank'
            }
        ]
    },
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
