<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppMenuItem from './AppMenuItem.vue';

const page = usePage();
const permissions = computed(() => page.props.auth.user?.permissions ?? []);
const hasPermission = (perm) => permissions.value.includes(perm);

const model = computed(() => [
    // ========== DASHBOARD ==========
    {
        label: 'Dashboard',
        items: [
            { 
                label: 'Panel Principal', 
                icon: 'pi pi-fw pi-home', 
                to: '/dashboard' 
            }
        ]
    },

    // ========== TASAS FIJAS ==========
    {
        label: 'Tasas Fijas',
        icon: 'pi pi-fw pi-percentage',
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
            {
                label: 'Entidades Financieras',
                icon: 'pi pi-fw pi-building-columns',
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
            }
        ]
    },

    // ========== FACTORING ==========
    {
        label: 'Factoring',
        icon: 'pi pi-fw pi-file-invoice',
        items: [
            {
                label: 'Empresas',
                icon: 'pi pi-fw pi-building',
                to: '/factoring/empresas'
            },
            {
                label: 'Facturas',
                icon: 'pi pi-fw pi-file-edit',
                to: '/factoring/facturas'
            },
            {
                label: 'Inversiones',
                icon: 'pi pi-fw pi-chart-line',
                to: '/factoring/inversiones'
            },
            {
                label: 'Inversionistas',
                icon: 'pi pi-fw pi-users',
                to: '/factoring/inversionistas'
            },
            {
                label: 'Depósitos',
                icon: 'pi pi-fw pi-arrow-down',
                to: '/factoring/depositos'
            },
            {
                label: 'Realizar Pagos',
                icon: 'pi pi-fw pi-send',
                to: '/factoring/pagos'
            },
            {
                label: 'Retiros',
                icon: 'pi pi-fw pi-arrow-up',
                to: '/factoring/retiros'
            },
            {
                label: 'Cuentas Bancarias',
                icon: 'pi pi-fw pi-university',
                to: '/factoring/cuentas-bancarias'
            },
            {
                label: 'Tipo de Cambio',
                icon: 'pi pi-fw pi-refresh',
                to: '/factoring/tipo-cambio'
            },
            {
                label: 'Sectores',
                icon: 'pi pi-fw pi-sitemap',
                to: '/factoring/sectores'
            }
        ]
    },

    // ========== SUBASTA DE HIPOTECAS ==========
    {
        label: 'Subasta de Hipotecas',
        icon: 'pi pi-fw pi-home',
        items: [
            {
                label: 'Registro de Inmuebles',
                icon: 'pi pi-fw pi-building',
                to: '/subasta-hipotecas/propiedades'
            },
            {
                label: 'Reglas del Inmueble',
                icon: 'pi pi-fw pi-list',
                to: '/subasta-hipotecas/reglas'
            },
            {
                label: 'Información del Cliente',
                icon: 'pi pi-fw pi-user',
                to: '/subasta-hipotecas/inversionista'
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
    },

    // ========== ADMINISTRACIÓN ==========
    {
        label: 'Administración',
        icon: 'pi pi-fw pi-shield',
        items: [
            hasPermission('ver usuarios') && {
                label: 'Gestión de Usuarios',
                icon: 'pi pi-fw pi-users',
                to: '/usuario'
            },
            hasPermission('ver roles') && {
                label: 'Roles y Permisos',
                icon: 'pi pi-fw pi-id-card',
                to: '/roles'
            }
        ].filter(Boolean)
    }
].filter(section => section.items && section.items.length > 0));
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="i">
            <app-menu-item :item="item" :index="i" />
        </template>
    </ul>
</template>
