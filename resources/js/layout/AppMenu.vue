<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppMenuItem from './AppMenuItem.vue'

const page = usePage()
const permissions = computed(() => page.props?.auth?.user?.permissions ?? [])
const hasPermission = (perm) => permissions.value?.includes?.(perm)

const model = computed(() => {
    const menuItems = [
        // ===== Dashboard =====
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

        // ===== Tasas Fijas =====
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

        // ===== Factoring (interno) + link externo =====
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
                },
                // FIX: use the right permission for Tipo de Cambio
                hasPermission('ver tipo cambio') && {
                    label: 'Tipo de Cambio',
                    icon: 'pi pi-fw pi-refresh',
                    to: '/factoring/tipo-cambio'
                },

                // External Backoffice link (kept, as requested)
                {
                    label: 'Backoffice (externo)',
                    icon: 'pi pi-fw pi-external-link',
                    url: 'https://backoffice.zuma.com.pe/',
                    target: '_blank'
                }
            ].filter(Boolean)
        },

        // ===== Subasta de Hipotecas =====
        {
            label: 'Subasta de Hipotecas',
            icon: 'pi pi-fw pi-home',
            items: [
                hasPermission('ver propiedades') && {
                    label: 'Registro de Solicitud',
                    icon: 'pi pi-fw pi-building',
                    to: '/subasta-hipotecas/propiedades'
                },
                hasPermission('ver reglas del imueble') && {
                    label: 'Reglas del Solicitud',
                    icon: 'pi pi-fw pi-list',
                    to: '/subasta-hipotecas/reglas'
                },
                hasPermission('ver informacion del cliente') && {
                    label: 'Información del Solicitud',
                    icon: 'pi pi-fw pi-user',
                    to: '/subasta-hipotecas/inversionista'
                },
               
                hasPermission('ver reservas') && {
                    label: 'Reservas',
                    icon: 'pi pi-fw pi-calendar-plus',
                    to: '/subasta-hipotecas/reserva'
                },
                {
                    label: 'Histórico de Pujas',
                    icon: 'pi pi-fw pi-chart-line',
                    to: '/subasta-hipotecas/historicos'
                },
                hasPermission('ver pujas') && {
                    label: 'Depósitos',
                    icon: 'pi pi-fw pi-arrow-down',
                    to: '/subasta-hipotecas/pagos'
                },
                hasPermission('ver pagos de entrada') && {
                    label: 'Pago de Entrada',
                    icon: 'pi pi-fw pi-arrow-circle-down',
                    to: '/subasta-hipotecas/cliente/pagos'
                },
                hasPermission('ver pagos de salida') && {
                    label: 'Pago de Salida',
                    icon: 'pi pi-fw pi-arrow-circle-up',
                    to: '/subasta-hipotecas/inversionista/pagos'
                }
            ].filter(Boolean)
        },

        // ===== Blog (added back, no permissions in your snippet) =====
         hasPermission('ver posts') && {
            label: 'Blog',
            icon: 'pi pi-fw pi-sparkles',
            items: [
                {
                    label: 'Categorias',
                    icon: 'pi pi-fw pi-objects-column',
                    to: '/blog/categorias'
                },
                {
                    label: 'Registro',
                    icon: 'pi pi-fw pi-file-plus',
                    to: '/blog/registro'
                },
                {
                    label: 'Posts',
                    icon: 'pi pi-fw pi-list',
                    to: '/blog/posts'
                },
                {
                    label: 'Seguimiento',
                    icon: 'pi pi-fw pi-star',
                    to: '/blog/seguimiento'
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
                },
                hasPermission('ver tipo cambio') && {
                    label: 'Tipo de Cambio',
                    icon: 'pi pi-fw pi-refresh',
                    to: '/factoring/tipo-cambio/nuevo'
                }
            ].filter(Boolean)
        }
    ]

    // Hide sections that ended up empty after permission filtering
    return menuItems.filter(section => section.items && section.items.length > 0)
})
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item.label">
            <app-menu-item :item="item" :index="i" />
        </template>
    </ul>
</template>
