import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import PrimeVue from "primevue/config";
import ToastService from "primevue/toastservice";
import Aura from "@primeuix/themes/Aura";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import {definePreset} from "@primeuix/themes";

const Fulgurite = definePreset(Aura, {
    primitive: {
        fulgurite: {
            sandstone: '#ffe3b7',
            magicsand: '#fff3e9',
            sand: '#fffbef',
            sandAlt: '#fffaf7',

            darknight: '#050e15',
            midnight: '#0D1117',
            night: '#161B22',
            moonlight: '#1A2535',

            primaryLight: '#f3ad40',
            primary: '#eea400',
            primaryDark: '#b08300'
        }
    },
    semantic: {
        primary: {
            50: '#fff8e1',
            100: '#ffecb3',
            200: '#ffe082',
            300: '#ffd54f',
            400: '#ffca28',
            500: '{fulgurite.primary}',
            600: '#d89400',
            700: '{fulgurite.primaryDark}',
            800: '#8f6a00',
            900: '#604600',
            950: '#332500',

            color: '{fulgurite.primary}',
            hoverColor: '{fulgurite.primaryLight}',
            activeColor: '{fulgurite.primaryDark}',
            contrastColor: '{fulgurite.darknight}'
        },
        colorScheme: {
            light: {
                surface: {
                    0: '#ffffff',
                    50: '{fulgurite.sand}',
                    100: '{fulgurite.sandAlt}',
                    200: '{fulgurite.magicsand}',
                    300: '{fulgurite.sandstone}',
                    400: '#f2cf99',
                    500: '#d9b279',
                    600: '#a67f50',
                    700: '#755637',
                    800: '#4d3927',
                    900: '#2f2218',
                    950: '#19110c'
                },

                formField: {
                    background: '{surface.0}',
                    disabledBackground: '{surface.100}',
                    filledBackground: '{surface.100}',
                    filledHoverBackground: '{surface.200}',
                    filledFocusBackground: '{surface.0}',
                    borderColor: '{surface.300}',
                    hoverBorderColor: '{primary.color}',
                    focusBorderColor: '{primary.color}',
                    color: '{surface.950}',
                    disabledColor: '{surface.500}',
                    placeholderColor: '{surface.600}',
                    borderRadius: '0.5rem'
                },

                text: {
                    color: '{surface.950}',
                    hoverColor: '{surface.900}',
                    mutedColor: '{surface.700}',
                    hoverMutedColor: '{surface.800}'
                },

                content: {
                    background: '{surface.0}',
                    hoverBackground: '{surface.100}',
                    borderColor: '{surface.300}',
                    color: '{surface.950}',
                    hoverColor: '{surface.900}'
                },

                overlay: {
                    select: {
                        background: '{surface.0}',
                        borderColor: '{surface.300}',
                        color: '{surface.950}'
                    },
                    popover: {
                        background: '{surface.0}',
                        borderColor: '{surface.300}',
                        color: '{surface.950}'
                    },
                    modal: {
                        background: '{surface.0}',
                        borderColor: '{surface.300}',
                        color: '{surface.950}'
                    }
                }
            },

            dark: {
                surface: {
                    0: '#ffffff',
                    50: '#f8fafc',
                    100: '#f1f5f9',
                    200: '#e2e8f0',
                    300: '#cbd5e1',
                    400: '#94a3b8',
                    500: '#404f5e',
                    600: '{fulgurite.moonlight}',
                    700: '{fulgurite.night}',
                    800: '{fulgurite.midnight}',
                    900: '{fulgurite.darknight}',
                    950: '#020617'
                },

                formField: {
                    background: '{fulgurite.night}',
                    disabledBackground: '{fulgurite.midnight}',
                    filledBackground: '{fulgurite.night}',
                    filledHoverBackground: '{fulgurite.moonlight}',
                    filledFocusBackground: '{fulgurite.night}',
                    borderColor: '{fulgurite.moonlight}',
                    hoverBorderColor: '{primary.color}',
                    focusBorderColor: '{primary.color}',
                    color: '{surface.100}',
                    disabledColor: '{surface.500}',
                    placeholderColor: '{surface.400}',
                    borderRadius: '0.5rem'
                },

                text: {
                    color: '{surface.100}',
                    hoverColor: '{surface.0}',
                    mutedColor: '{surface.400}',
                    hoverMutedColor: '{surface.300}'
                },

                content: {
                    background: '{fulgurite.night}',
                    hoverBackground: '{surface.0}',
                    borderColor: '{surface.500}',
                    color: '{surface.100}',
                    hoverColor: '{surface.0}'
                },

                overlay: {
                    select: {
                        background: '{fulgurite.night}',
                        borderColor: '{fulgurite.moonlight}',
                        color: '{surface.100}'
                    },
                    popover: {
                        background: '{fulgurite.night}',
                        borderColor: '{fulgurite.moonlight}',
                        color: '{surface.100}'
                    },
                    modal: {
                        background: '{fulgurite.night}',
                        borderColor: '{fulgurite.moonlight}',
                        color: '{surface.100}'
                    }
                }
            }
        },
        focusRing: {
            width: '2px',
            style: 'solid',
            color: '{primary.color}',
            offset: '2px',
            shadow: 'none'
        }
    },
    
    components: {
        menu: {
            item: {
                focusBackground: "color-mix(in srgb, {fulgurite.magicsand} 5%, transparent)"
            }
        }
    }
})

createInertiaApp({
    resolve: (name) => resolvePageComponent(
        `./Pages/${name}.vue`,
        import.meta.glob("./Pages/**/*.vue")
    ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(PrimeVue, {
                theme: {
                    preset: Fulgurite,
                }
            })
            .use(ToastService)
            .mount(el);
    },
});
