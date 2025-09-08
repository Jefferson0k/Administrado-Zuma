<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppMenuItem from './AppMenuItem.vue';

const page = usePage();
const permissions = computed(() => page.props.auth.user?.permissions ?? []);
const hasPermission = (perm) => permissions.value.includes(perm);

const model = computed(() => {
    const menuItems = [
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
                hasPermission('ver tasas-fijas tipos') && {
                    label: 'Tipos de Tasa',
                    icon: 'pi pi-fw pi-sliders-h',
                    to: '/tasas-fijas/tipos'
                },
                hasPermission('ver tasas-fijas Frecuencia pagos') && {
                    label: 'Frecuencia de Pago',
                    icon: 'pi pi-fw pi-calendar-clock',
                    to: '/frecuencia/pagos'
                },
                hasPermission('ver tasas-fijas empresas') && {
                    label: 'Entidades Financieras',
                    icon: 'pi pi-fw pi-building-columns',
                    to: '/tasas-fijas/empresas'
                },
                hasPermission('ver tasas-fijas depositos') && {
                    label: 'Depósitos',
                    icon: 'pi pi-fw pi-wallet',
                    to: '/tasas-fijas/depositos'
                },
                hasPermission('ver tasas-fijas pagos') && {
                    label: 'Pagos',
                    icon: 'pi pi-fw pi-credit-card',
                    to: '/tasas-fijas/pagos'
                }
            ].filter(Boolean)
        },

        // ========== FACTORING ==========
        {
            label: 'Factoring',
            icon: 'pi pi-fw pi-file-invoice',
            items: [
                hasPermission('ver empresas') && {
                    label: 'Empresas',
                    icon: 'pi pi-fw pi-building',
                    to: '/factoring/empresas'
                },
                hasPermission('ver factura') && {
                    label: 'Facturas',
                    icon: 'pi pi-fw pi-file-edit',
                    to: '/factoring/facturas'
                },
                hasPermission('ver inversiones') && {
                    label: 'Inversiones',
                    icon: 'pi pi-fw pi-chart-line',
                    to: '/factoring/inversiones'
                },
                hasPermission('ver inversionistas') && {
                    label: 'Inversionistas',
                    icon: 'pi pi-fw pi-users',
                    to: '/factoring/inversionistas'
                },
                hasPermission('ver depositos') && {
                    label: 'Depósitos',
                    icon: 'pi pi-fw pi-arrow-down',
                    to: '/factoring/depositos'
                },
                hasPermission('ver pagos') && {
                    label: 'Realizar Pagos',
                    icon: 'pi pi-fw pi-send',
                    to: '/factoring/pagos'
                },
                hasPermission('ver retiros') && {
                    label: 'Retiros',
                    icon: 'pi pi-fw pi-arrow-up',
                    to: '/factoring/retiros'
                },
                hasPermission('ver cuenta bancaria') && {
                    label: 'Cuentas Bancarias',
                    icon: 'pi pi-fw pi-briefcase',
                    to: '/factoring/cuentas-bancarias'
                },
                hasPermission('ver sectores') && {
                    label: 'Sectores',
                    icon: 'pi pi-fw pi-sitemap',
                    to: '/factoring/sectores'
                }
            ].filter(Boolean)
        },

        // ========== SUBASTA DE HIPOTECAS ==========
        {
            label: 'Subasta de Hipotecas',
            icon: 'pi pi-fw pi-home',
            items: [
                hasPermission('ver propiedades') && {
                    label: 'Registro de Inmuebles',
                    icon: 'pi pi-fw pi-building',
                    to: '/subasta-hipotecas/propiedades'
                },
                hasPermission('ver reglas del imueble') && {
                    label: 'Reglas del Inmueble',
                    icon: 'pi pi-fw pi-list',
                    to: '/subasta-hipotecas/reglas'
                },
                hasPermission('ver informacion del cliente') && {
                    label: 'Información del Cliente',
                    icon: 'pi pi-fw pi-user',
                    to: '/subasta-hipotecas/inversionista'
                },
                // Estas opciones no tenían verificación de permisos, manteniéndolas visibles siempre
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
            ].filter(Boolean)
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
                },
                hasPermission('ver tipo cambio') && {
                    label: 'Tipo de Cambio',
                    icon: 'pi pi-fw pi-refresh',
                    to: '/factoring/tipo-cambio'
                },
            ].filter(Boolean)
        }
    ];

    // Filtrar secciones que tienen items después de aplicar permisos
    return menuItems.filter(section => section.items && section.items.length > 0);
});
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item.label">
            <app-menu-item :item="item" :index="i" />
        </template>
    </ul>
</template>