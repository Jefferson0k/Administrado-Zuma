import "@/assets/styles.scss";

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import "primeicons/primeicons.css";
import { setupPrimeVue } from './plugins/primevue';
import axios from 'axios';
import Swal from 'sweetalert2';

// Extend ImportMeta interface for Vite...

axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response) {
            const status = error.response.status;
            console.log('error interceptor',error)
            console.log('status interceptor',status)
            switch (status) {
                case 419:

                    console.log('status interceptor case',status)
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sesión expirada',
                        text: 'Tu sesión ha caducado. Se recargará la página.',
                        confirmButtonText: 'Aceptar'
                    }).then(() => window.location.reload());
                    break;

                case 404:
                    Swal.fire({
                        icon: 'error',
                        title: 'Ruta no encontrada',
                        text: 'Verifica la URL o inténtalo más tarde.',
                    });
                    break;

                case 500:
                    Swal.fire({
                        icon: 'error',
                        title: 'Error interno del servidor',
                        text: 'Por favor, inténtalo nuevamente más tarde.',
                    });
                    break;

                default:
                    Swal.fire({
                        icon: 'info',
                        title: `Error ${status}`,
                        text: 'Ha ocurrido un problema inesperado.',
                    });
            }
        }

        return Promise.reject(error);
    }
);

declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            const vueApp = createApp({ render: () => h(App, props) });
            vueApp.use(plugin)
            vueApp.use(ZiggyVue)
            setupPrimeVue(vueApp);
            vueApp.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

