import { Link, usePage } from "@inertiajs/vue3";
import { ssrInterpolate, ssrRenderClass, ssrRenderComponent, ssrRenderSlot, ssrRenderVNode } from "vue/server-renderer";
import { computed, createBlock, createCommentVNode, createTextVNode, createVNode, defineComponent, inject, isRef, mergeModels, mergeProps, onMounted, openBlock, provide, reactive, ref, renderSlot, resolveDynamicComponent, toDisplayString, toRef, toValue, unref, useModel, useSSRContext, useSlots, watch, withCtx } from "vue";
import defu$1, { defu } from "defu";
import { Primitive, Slot, createContext, useForwardProps } from "reka-ui";
import { createHooks } from "hookable";
import { createSharedComposable, reactiveOmit, reactivePick, useDebounceFn } from "@vueuse/core";
import { isEqual } from "ohash/utils";
import { hasProtocol } from "ufo";
import { createTV } from "tailwind-variants";
import { Icon } from "@iconify/vue";
//#region virtual:nuxt-ui-app-config
var virtual_nuxt_ui_app_config_default = {
	"ui": {
		"colors": {
			"primary": "green",
			"secondary": "blue",
			"success": "green",
			"info": "blue",
			"warning": "yellow",
			"error": "red",
			"neutral": "slate"
		},
		"icons": {
			"arrowDown": "i-lucide-arrow-down",
			"arrowLeft": "i-lucide-arrow-left",
			"arrowRight": "i-lucide-arrow-right",
			"arrowUp": "i-lucide-arrow-up",
			"caution": "i-lucide-circle-alert",
			"check": "i-lucide-check",
			"chevronDoubleLeft": "i-lucide-chevrons-left",
			"chevronDoubleRight": "i-lucide-chevrons-right",
			"chevronDown": "i-lucide-chevron-down",
			"chevronLeft": "i-lucide-chevron-left",
			"chevronRight": "i-lucide-chevron-right",
			"chevronUp": "i-lucide-chevron-up",
			"close": "i-lucide-x",
			"copy": "i-lucide-copy",
			"copyCheck": "i-lucide-copy-check",
			"dark": "i-lucide-moon",
			"drag": "i-lucide-grip-vertical",
			"ellipsis": "i-lucide-ellipsis",
			"error": "i-lucide-circle-x",
			"external": "i-lucide-arrow-up-right",
			"eye": "i-lucide-eye",
			"eyeOff": "i-lucide-eye-off",
			"file": "i-lucide-file",
			"folder": "i-lucide-folder",
			"folderOpen": "i-lucide-folder-open",
			"hash": "i-lucide-hash",
			"info": "i-lucide-info",
			"light": "i-lucide-sun",
			"loading": "i-lucide-loader-circle",
			"menu": "i-lucide-menu",
			"minus": "i-lucide-minus",
			"panelClose": "i-lucide-panel-left-close",
			"panelOpen": "i-lucide-panel-left-open",
			"plus": "i-lucide-plus",
			"reload": "i-lucide-rotate-ccw",
			"search": "i-lucide-search",
			"stop": "i-lucide-square",
			"success": "i-lucide-circle-check",
			"system": "i-lucide-monitor",
			"tip": "i-lucide-lightbulb",
			"upload": "i-lucide-upload",
			"warning": "i-lucide-triangle-alert"
		},
		"tv": { "twMergeConfig": {} }
	},
	"colorMode": true
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/vue/composables/useAppConfig.js
var _appConfig = reactive(virtual_nuxt_ui_app_config_default);
var useAppConfig = () => _appConfig;
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/composables/useKbd.js
var kbdKeysMap = {
	meta: "",
	ctrl: "",
	alt: "",
	win: "⊞",
	command: "⌘",
	shift: "⇧",
	control: "⌃",
	option: "⌥",
	enter: "↵",
	delete: "⌦",
	backspace: "⌫",
	escape: "Esc",
	tab: "⇥",
	capslock: "⇪",
	arrowup: "↑",
	arrowright: "→",
	arrowdown: "↓",
	arrowleft: "←",
	pageup: "⇞",
	pagedown: "⇟",
	home: "↖",
	end: "↘"
};
var _useKbd = () => {
	const macOS = computed(() => navigator && navigator.userAgent && navigator.userAgent.match(/Macintosh;/));
	const kbdKeysSpecificMap = reactive({
		meta: " ",
		alt: " ",
		ctrl: " "
	});
	onMounted(() => {
		kbdKeysSpecificMap.meta = macOS.value ? kbdKeysMap.command : "Ctrl";
		kbdKeysSpecificMap.ctrl = macOS.value ? kbdKeysMap.control : "Ctrl";
		kbdKeysSpecificMap.alt = macOS.value ? kbdKeysMap.option : "Alt";
	});
	function getKbdKey(value) {
		if (!value) return;
		if ([
			"meta",
			"alt",
			"ctrl"
		].includes(value)) return kbdKeysSpecificMap[value];
		return kbdKeysMap[value] || value;
	}
	return {
		macOS,
		getKbdKey
	};
};
var useKbd = /* @__PURE__ */ createSharedComposable(_useKbd);
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/composables/defineLocale.js
/* @__NO_SIDE_EFFECTS__ */
function defineLocale(options) {
	return defu(options, { dir: "ltr" });
}
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/utils/index.js
function omit(data, keys) {
	const result = { ...data };
	for (const key of keys) delete result[key];
	return result;
}
function get(object, path, defaultValue) {
	if (typeof path === "string") path = path.split(".").map((key) => {
		const numKey = Number(key);
		return Number.isNaN(numKey) ? key : numKey;
	});
	let result = object;
	for (const key of path) {
		if (result === void 0 || result === null) return defaultValue;
		result = result[key];
	}
	return result !== void 0 ? result : defaultValue;
}
function looseToNumber(val) {
	const n = Number.parseFloat(val);
	return Number.isNaN(n) ? val : n;
}
function compare(value, currentValue, comparator) {
	if (value === void 0 || currentValue === void 0) return false;
	if (typeof value === "string") return value === currentValue;
	if (typeof comparator === "function") return comparator(value, currentValue);
	if (typeof comparator === "string") return get(value, comparator) === get(currentValue, comparator);
	return isEqual(value, currentValue);
}
function isEmpty(value) {
	if (value == null) return true;
	if (typeof value === "boolean" || typeof value === "number") return false;
	if (typeof value === "string") return value.trim().length === 0;
	if (Array.isArray(value)) return value.length === 0;
	if (value instanceof Map || value instanceof Set) return value.size === 0;
	if (value instanceof Date || value instanceof RegExp || typeof value === "function") return false;
	if (typeof value === "object") {
		for (const _ in value) if (Object.prototype.hasOwnProperty.call(value, _)) return false;
		return true;
	}
	return false;
}
function getDisplayValue(items, value, options = {}) {
	const { valueKey, labelKey, by } = options;
	const foundItem = items.find((item) => {
		return compare(typeof item === "object" && item !== null && valueKey ? get(item, valueKey) : item, value, by);
	});
	if (isEmpty(value) && foundItem) return labelKey ? get(foundItem, labelKey) : void 0;
	if (isEmpty(value)) return;
	const source = foundItem ?? value;
	if (source === null || source === void 0) return;
	if (typeof source === "object") return labelKey ? get(source, labelKey) : void 0;
	return String(source);
}
function isArrayOfArray(item) {
	return Array.isArray(item[0]);
}
function mergeClasses(appConfigClass, propClass) {
	if (!appConfigClass && !propClass) return "";
	return [...Array.isArray(appConfigClass) ? appConfigClass : [appConfigClass], propClass].filter(Boolean);
}
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/utils/locale.js
function buildTranslator(locale) {
	return (path, option) => translate(path, option, unref(locale));
}
function translate(path, option, locale) {
	return get(locale, `messages.${path}`, path).replace(/\{(\w+)\}/g, (_, key) => `${option?.[key] ?? `{${key}}`}`);
}
function buildLocaleContext(locale) {
	return {
		lang: computed(() => unref(locale).name),
		code: computed(() => unref(locale).code),
		dir: computed(() => unref(locale).dir),
		locale: isRef(locale) ? locale : ref(locale),
		t: buildTranslator(locale)
	};
}
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/locale/en.js
var en_default = /* @__PURE__ */ defineLocale({
	name: "English",
	code: "en",
	messages: {
		alert: { close: "Close" },
		authForm: {
			hidePassword: "Hide password",
			showPassword: "Show password",
			submit: "Continue"
		},
		banner: { close: "Close" },
		calendar: {
			nextMonth: "Next month",
			nextYear: "Next year",
			prevMonth: "Previous month",
			prevYear: "Previous year"
		},
		carousel: {
			dots: "Choose slide to display",
			goto: "Go to slide {slide}",
			next: "Next",
			prev: "Prev"
		},
		chatPrompt: { placeholder: "Type your message here…" },
		chatPromptSubmit: { label: "Send prompt" },
		colorMode: {
			dark: "Dark",
			light: "Light",
			switchToDark: "Switch to dark mode",
			switchToLight: "Switch to light mode",
			system: "System"
		},
		commandPalette: {
			back: "Back",
			close: "Close",
			noData: "No data",
			noMatch: "No matching data",
			placeholder: "Type a command or search…"
		},
		contentSearch: {
			links: "Links",
			theme: "Theme"
		},
		contentSearchButton: { label: "Search…" },
		contentToc: { title: "On this page" },
		dropdownMenu: {
			noMatch: "No matching data",
			search: "Search…"
		},
		dashboardSearch: { theme: "Theme" },
		dashboardSearchButton: { label: "Search…" },
		dashboardSidebarCollapse: {
			collapse: "Collapse sidebar",
			expand: "Expand sidebar"
		},
		dashboardSidebarToggle: {
			close: "Close sidebar",
			open: "Open sidebar"
		},
		error: { clear: "Back to home" },
		fileUpload: { removeFile: "Remove {filename}" },
		header: {
			close: "Close menu",
			open: "Open menu"
		},
		inputMenu: {
			create: "Create \"{label}\"",
			noData: "No data",
			noMatch: "No matching data"
		},
		inputNumber: {
			decrement: "Decrement",
			increment: "Increment"
		},
		listbox: {
			noData: "No data",
			noMatch: "No matching data",
			search: "Search…"
		},
		modal: { close: "Close" },
		pricingTable: { caption: "Pricing plan comparison" },
		prose: {
			codeCollapse: {
				closeText: "Collapse",
				name: "code",
				openText: "Expand"
			},
			collapsible: {
				closeText: "Hide",
				name: "properties",
				openText: "Show"
			},
			pre: { copy: "Copy code to clipboard" },
			prompt: {
				copy: "Copy prompt",
				openIn: "Open in {name}"
			}
		},
		chatReasoning: {
			thinking: "Thinking…",
			thought: "Thought",
			thoughtFor: "Thought for {duration}"
		},
		sidebar: {
			close: "Close",
			toggle: "Toggle"
		},
		selectMenu: {
			create: "Create \"{label}\"",
			noData: "No data",
			noMatch: "No matching data",
			search: "Search…"
		},
		slideover: { close: "Close" },
		table: { noData: "No data" },
		toast: { close: "Close" }
	}
});
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/composables/useLocale.js
var localeContextInjectionKey = Symbol.for("nuxt-ui.locale-context");
var _useLocale = (localeOverrides) => {
	const locale = localeOverrides || toRef(inject(localeContextInjectionKey, en_default));
	return buildLocaleContext(computed(() => locale.value || en_default));
};
var useLocale = createSharedComposable(_useLocale);
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/vue/stubs/base.js
var state = {};
var useState = (key, init) => {
	if (state[key]) return state[key];
	const value = ref(init());
	state[key] = value;
	return value;
};
createHooks();
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/composables/useComponentUI.js
var [injectThemeContext, provideThemeContext] = createContext("UTheme", "RootContext");
function useComponentUI(name, props) {
	const { ui } = injectThemeContext({ ui: computed(() => ({})) });
	return computed(() => {
		const themeOverrides = get(ui.value, name) || {};
		return defu$1(props.ui ?? {}, themeOverrides);
	});
}
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/composables/useComponentIcons.js
function useComponentIcons(componentProps) {
	const appConfig = useAppConfig();
	const props = computed(() => toValue(componentProps));
	const isLeading = computed(() => props.value.icon && props.value.leading || props.value.icon && !props.value.trailing || props.value.loading && !props.value.trailing || !!props.value.leadingIcon);
	return {
		isLeading,
		isTrailing: computed(() => props.value.icon && props.value.trailing || props.value.loading && props.value.trailing || !!props.value.trailingIcon),
		leadingIconName: computed(() => {
			if (props.value.loading) return props.value.loadingIcon || appConfig.ui.icons.loading;
			return props.value.leadingIcon || props.value.icon;
		}),
		trailingIconName: computed(() => {
			if (props.value.loading && !isLeading.value) return props.value.loadingIcon || appConfig.ui.icons.loading;
			return props.value.trailingIcon || props.value.icon;
		})
	};
}
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/composables/useFieldGroup.js
var fieldGroupInjectionKey = Symbol("nuxt-ui.field-group");
function useFieldGroup(props) {
	const fieldGroup = inject(fieldGroupInjectionKey, void 0);
	return {
		orientation: computed(() => fieldGroup?.value.orientation),
		size: computed(() => props?.size ?? fieldGroup?.value.size)
	};
}
var FieldGroupReset = defineComponent({
	name: "FieldGroupReset",
	setup(_, { slots }) {
		provide(fieldGroupInjectionKey, computed(() => ({
			size: void 0,
			orientation: void 0
		})));
		return () => slots.default?.();
	}
});
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/composables/useFormField.js
var formOptionsInjectionKey = Symbol("nuxt-ui.form-options");
var formBusInjectionKey = Symbol("nuxt-ui.form-events");
var formStateInjectionKey = Symbol("nuxt-ui.form-state");
var formFieldInjectionKey = Symbol("nuxt-ui.form-field");
var inputIdInjectionKey = Symbol("nuxt-ui.input-id");
var formInputsInjectionKey = Symbol("nuxt-ui.form-inputs");
var formLoadingInjectionKey = Symbol("nuxt-ui.form-loading");
var formErrorsInjectionKey = Symbol("nuxt-ui.form-errors");
function useFormField(props, opts) {
	const formOptions = inject(formOptionsInjectionKey, void 0);
	const formBus = inject(formBusInjectionKey, void 0);
	const formField = inject(formFieldInjectionKey, void 0);
	const inputId = inject(inputIdInjectionKey, void 0);
	provide(formFieldInjectionKey, void 0);
	if (formField && inputId) {
		if (opts?.bind === false) inputId.value = void 0;
		else if (props?.id) inputId.value = props?.id;
	}
	function emitFormEvent(type, name, eager) {
		if (formBus && formField && name) formBus.emit({
			type,
			name,
			eager
		});
	}
	function emitFormBlur() {
		emitFormEvent("blur", formField?.value.name);
	}
	function emitFormFocus() {
		emitFormEvent("focus", formField?.value.name);
	}
	function emitFormChange() {
		emitFormEvent("change", formField?.value.name);
	}
	const emitFormInput = useDebounceFn(() => {
		emitFormEvent("input", formField?.value.name, !opts?.deferInputValidation || formField?.value.eagerValidation);
	}, formField?.value.validateOnInputDelay ?? formOptions?.value.validateOnInputDelay ?? 0);
	return {
		id: computed(() => props?.id ?? inputId?.value),
		name: computed(() => props?.name ?? formField?.value.name),
		size: computed(() => props?.size ?? formField?.value.size),
		color: computed(() => formField?.value.error ? "error" : props?.color),
		highlight: computed(() => formField?.value.error ? true : props?.highlight),
		disabled: computed(() => formOptions?.value.disabled || props?.disabled),
		emitFormBlur,
		emitFormInput,
		emitFormChange,
		emitFormFocus,
		ariaAttrs: computed(() => {
			if (!formField?.value) return;
			const descriptiveAttrs = [
				"error",
				"hint",
				"description",
				"help"
			].filter((type) => formField?.value?.[type]).map((type) => `${formField?.value.ariaId}-${type}`) || [];
			const attrs = { "aria-invalid": !!formField?.value.error };
			if (descriptiveAttrs.length > 0) attrs["aria-describedby"] = descriptiveAttrs.join(" ");
			return attrs;
		})
	};
}
var tv = /* @__PURE__ */ createTV(virtual_nuxt_ui_app_config_default.ui?.tv);
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/utils/link.js
var linkKeys = [
	"active",
	"activeClass",
	"ariaCurrentValue",
	"as",
	"disabled",
	"download",
	"exact",
	"exactActiveClass",
	"exactHash",
	"exactQuery",
	"external",
	"form",
	"formaction",
	"formenctype",
	"formmethod",
	"formnovalidate",
	"formtarget",
	"href",
	"hreflang",
	"inactiveClass",
	"locale",
	"media",
	"noPrefetch",
	"noRel",
	"onClick",
	"ping",
	"prefetch",
	"prefetchOn",
	"prefetchedClass",
	"referrerpolicy",
	"rel",
	"replace",
	"target",
	"title",
	"to",
	"trailingSlash",
	"type",
	"viewTransition"
];
function pickLinkProps(link) {
	const keys = Object.keys(link);
	const ariaKeys = keys.filter((key) => key.startsWith("aria-"));
	const dataKeys = keys.filter((key) => key.startsWith("data-"));
	return reactivePick(link, ...[
		...linkKeys,
		...ariaKeys,
		...dataKeys
	]);
}
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/vue/components/Icon.vue
var _sfc_main$5 = {
	__name: "Icon",
	__ssrInlineRender: true,
	props: {
		name: {
			type: null,
			required: true
		},
		mode: {
			type: String,
			required: false
		},
		size: {
			type: [String, Number],
			required: false
		},
		customize: {
			type: [
				Function,
				Boolean,
				null
			],
			required: false
		}
	},
	setup(__props) {
		const props = __props;
		const appConfig = useAppConfig();
		function resolveCustomizeFn(customize2, globalCustomize) {
			if (customize2 === false) return void 0;
			if (customize2 === true || customize2 === null) return globalCustomize;
			return customize2;
		}
		const mode = computed(() => {
			const mode2 = props.mode || appConfig.icon?.mode;
			if (mode2 === "css") return "style";
			return mode2;
		});
		const size = computed(() => props.size || appConfig.icon?.size);
		const customize = computed(() => resolveCustomizeFn(props.customize, appConfig.icon?.customize));
		return (_ctx, _push, _parent, _attrs) => {
			if (typeof __props.name === "string") _push(ssrRenderComponent(unref(Icon), mergeProps({
				icon: __props.name.replace(/^i-/, ""),
				mode: mode.value,
				width: size.value,
				height: size.value,
				customise: customize.value
			}, _attrs), null, _parent));
			else ssrRenderVNode(_push, createVNode(resolveDynamicComponent(__props.name), _attrs, null), _parent);
		};
	}
};
var _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/vue/components/Icon.vue");
	return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/composables/useAvatarGroup.js
var avatarGroupInjectionKey = Symbol("nuxt-ui.avatar-group");
function useAvatarGroup(props) {
	const avatarGroup = inject(avatarGroupInjectionKey, void 0);
	const size = computed(() => props.size ?? avatarGroup?.value.size);
	provide(avatarGroupInjectionKey, computed(() => ({ size: size.value })));
	return { size };
}
//#endregion
//#region virtual:nuxt-ui-templates/ui/chip.ts
var chip_default = {
	"slots": {
		"root": "relative inline-flex items-center justify-center shrink-0",
		"base": "rounded-full ring ring-bg flex items-center justify-center text-inverted font-medium whitespace-nowrap"
	},
	"variants": {
		"color": {
			"primary": "bg-primary",
			"secondary": "bg-secondary",
			"success": "bg-success",
			"info": "bg-info",
			"warning": "bg-warning",
			"error": "bg-error",
			"neutral": "bg-inverted"
		},
		"size": {
			"3xs": "h-[4px] min-w-[4px] text-[4px]",
			"2xs": "h-[5px] min-w-[5px] text-[5px]",
			"xs": "h-[6px] min-w-[6px] text-[6px]",
			"sm": "h-[7px] min-w-[7px] text-[7px]",
			"md": "h-[8px] min-w-[8px] text-[8px]",
			"lg": "h-[9px] min-w-[9px] text-[9px]",
			"xl": "h-[10px] min-w-[10px] text-[10px]",
			"2xl": "h-[11px] min-w-[11px] text-[11px]",
			"3xl": "h-[12px] min-w-[12px] text-[12px]"
		},
		"position": {
			"top-right": "top-0 right-0",
			"bottom-right": "bottom-0 right-0",
			"top-left": "top-0 left-0",
			"bottom-left": "bottom-0 left-0"
		},
		"inset": { "false": "" },
		"standalone": { "false": "absolute" }
	},
	"compoundVariants": [
		{
			"position": "top-right",
			"inset": false,
			"class": "-translate-y-1/2 translate-x-1/2 transform"
		},
		{
			"position": "bottom-right",
			"inset": false,
			"class": "translate-y-1/2 translate-x-1/2 transform"
		},
		{
			"position": "top-left",
			"inset": false,
			"class": "-translate-y-1/2 -translate-x-1/2 transform"
		},
		{
			"position": "bottom-left",
			"inset": false,
			"class": "translate-y-1/2 -translate-x-1/2 transform"
		}
	],
	"defaultVariants": {
		"size": "md",
		"color": "primary",
		"position": "top-right"
	}
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Chip.vue
var _sfc_main$4 = /* @__PURE__ */ Object.assign({ inheritAttrs: false }, {
	__name: "Chip",
	__ssrInlineRender: true,
	props: /* @__PURE__ */ mergeModels({
		as: {
			type: null,
			required: false
		},
		text: {
			type: [String, Number],
			required: false
		},
		color: {
			type: null,
			required: false
		},
		size: {
			type: null,
			required: false
		},
		position: {
			type: null,
			required: false
		},
		inset: {
			type: Boolean,
			required: false,
			default: false
		},
		standalone: {
			type: Boolean,
			required: false,
			default: false
		},
		class: {
			type: null,
			required: false
		},
		ui: {
			type: Object,
			required: false
		}
	}, {
		"show": {
			type: Boolean,
			default: true
		},
		"showModifiers": {}
	}),
	emits: ["update:show"],
	setup(__props) {
		const props = __props;
		const show = useModel(__props, "show", {
			type: Boolean,
			default: true
		});
		const { size } = useAvatarGroup(props);
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("chip", props);
		const ui = computed(() => tv({
			extend: tv(chip_default),
			...appConfig.ui?.chip || {}
		})({
			color: props.color,
			size: size.value,
			position: props.position,
			inset: props.inset,
			standalone: props.standalone
		}));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(Primitive), mergeProps({
				as: __props.as,
				"data-slot": "root",
				class: ui.value.root({ class: [unref(uiProp)?.root, props.class] })
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(ssrRenderComponent(unref(Slot), _ctx.$attrs, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
								else return [renderSlot(_ctx.$slots, "default")];
							}),
							_: 3
						}, _parent, _scopeId));
						if (show.value) {
							_push(`<span data-slot="base" class="${ssrRenderClass(ui.value.base({ class: unref(uiProp)?.base }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "content", {}, () => {
								_push(`${ssrInterpolate(__props.text)}`);
							}, _push, _parent, _scopeId);
							_push(`</span>`);
						} else _push(`<!---->`);
					} else return [createVNode(unref(Slot), _ctx.$attrs, {
						default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
						_: 3
					}, 16), show.value ? (openBlock(), createBlock("span", {
						key: 0,
						"data-slot": "base",
						class: ui.value.base({ class: unref(uiProp)?.base })
					}, [renderSlot(_ctx.$slots, "content", {}, () => [createTextVNode(toDisplayString(__props.text), 1)])], 2)) : createCommentVNode("", true)];
				}),
				_: 3
			}, _parent));
		};
	}
});
var _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Chip.vue");
	return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/avatar.ts
var avatar_default = {
	"slots": {
		"root": "inline-flex items-center justify-center shrink-0 select-none rounded-full align-middle bg-elevated",
		"image": "h-full w-full rounded-[inherit] object-cover",
		"fallback": "font-medium text-muted truncate",
		"icon": "text-muted shrink-0"
	},
	"variants": { "size": {
		"3xs": { "root": "size-4 text-[8px]" },
		"2xs": { "root": "size-5 text-[10px]" },
		"xs": { "root": "size-6 text-xs" },
		"sm": { "root": "size-7 text-sm" },
		"md": { "root": "size-8 text-base" },
		"lg": { "root": "size-9 text-lg" },
		"xl": { "root": "size-10 text-xl" },
		"2xl": { "root": "size-11 text-[22px]" },
		"3xl": { "root": "size-12 text-2xl" }
	} },
	"defaultVariants": { "size": "md" }
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Avatar.vue
var _sfc_main$3 = /* @__PURE__ */ Object.assign({ inheritAttrs: false }, {
	__name: "Avatar",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false
		},
		src: {
			type: String,
			required: false
		},
		alt: {
			type: String,
			required: false
		},
		icon: {
			type: null,
			required: false
		},
		text: {
			type: String,
			required: false
		},
		size: {
			type: null,
			required: false
		},
		chip: {
			type: [Boolean, Object],
			required: false
		},
		class: {
			type: null,
			required: false
		},
		style: {
			type: null,
			required: false
		},
		ui: {
			type: Object,
			required: false
		}
	},
	setup(__props) {
		const props = __props;
		const as = computed(() => {
			if (typeof props.as === "string" || typeof props.as?.render === "function") return { root: props.as };
			return defu(props.as, { root: "span" });
		});
		const fallback = computed(() => props.text || (props.alt || "").split(" ").map((word) => word.charAt(0)).join("").substring(0, 2));
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("avatar", props);
		const { size } = useAvatarGroup(props);
		const ui = computed(() => tv({
			extend: tv(avatar_default),
			...appConfig.ui?.avatar || {}
		})({ size: size.value }));
		const rootClass = computed(() => ui.value.root({ class: [uiProp.value?.root, props.class] }));
		const sizePx = computed(() => {
			const sizeClass = rootClass.value.split(" ").find((c) => /^size-\d+$/.test(c));
			if (sizeClass) {
				const num = Number.parseFloat(sizeClass.split("-")[1] ?? "");
				if (!Number.isNaN(num)) return num * 4;
			}
			return null;
		});
		const error = ref(false);
		watch(() => props.src, () => {
			if (error.value) error.value = false;
		});
		function onError() {
			error.value = true;
		}
		return (_ctx, _push, _parent, _attrs) => {
			ssrRenderVNode(_push, createVNode(resolveDynamicComponent(props.chip ? _sfc_main$4 : unref(Primitive)), mergeProps({ as: as.value.root }, props.chip ? typeof props.chip === "object" ? {
				inset: true,
				...props.chip
			} : { inset: true } : {}, {
				"data-slot": "root",
				class: rootClass.value,
				style: props.style
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) if (__props.src && !error.value) ssrRenderVNode(_push, createVNode(resolveDynamicComponent(as.value.img || unref("img")), mergeProps({
						src: __props.src,
						alt: __props.alt,
						width: sizePx.value,
						height: sizePx.value
					}, _ctx.$attrs, {
						"data-slot": "image",
						class: ui.value.image({ class: unref(uiProp)?.image }),
						onError
					}), null), _parent, _scopeId);
					else _push(ssrRenderComponent(unref(Slot), _ctx.$attrs, {
						default: withCtx((_, _push, _parent, _scopeId) => {
							if (_push) ssrRenderSlot(_ctx.$slots, "default", {}, () => {
								if (__props.icon) _push(ssrRenderComponent(_sfc_main$5, {
									name: __props.icon,
									"data-slot": "icon",
									class: ui.value.icon({ class: unref(uiProp)?.icon })
								}, null, _parent, _scopeId));
								else _push(`<span data-slot="fallback" class="${ssrRenderClass(ui.value.fallback({ class: unref(uiProp)?.fallback }))}"${_scopeId}>${ssrInterpolate(fallback.value || "\xA0")}</span>`);
							}, _push, _parent, _scopeId);
							else return [renderSlot(_ctx.$slots, "default", {}, () => [__props.icon ? (openBlock(), createBlock(_sfc_main$5, {
								key: 0,
								name: __props.icon,
								"data-slot": "icon",
								class: ui.value.icon({ class: unref(uiProp)?.icon })
							}, null, 8, ["name", "class"])) : (openBlock(), createBlock("span", {
								key: 1,
								"data-slot": "fallback",
								class: ui.value.fallback({ class: unref(uiProp)?.fallback })
							}, toDisplayString(fallback.value || "\xA0"), 3))])];
						}),
						_: 3
					}, _parent, _scopeId));
					else return [__props.src && !error.value ? (openBlock(), createBlock(resolveDynamicComponent(as.value.img || unref("img")), mergeProps({
						key: 0,
						src: __props.src,
						alt: __props.alt,
						width: sizePx.value,
						height: sizePx.value
					}, _ctx.$attrs, {
						"data-slot": "image",
						class: ui.value.image({ class: unref(uiProp)?.image }),
						onError
					}), null, 16, [
						"src",
						"alt",
						"width",
						"height",
						"class"
					])) : (openBlock(), createBlock(unref(Slot), mergeProps({ key: 1 }, _ctx.$attrs), {
						default: withCtx(() => [renderSlot(_ctx.$slots, "default", {}, () => [__props.icon ? (openBlock(), createBlock(_sfc_main$5, {
							key: 0,
							name: __props.icon,
							"data-slot": "icon",
							class: ui.value.icon({ class: unref(uiProp)?.icon })
						}, null, 8, ["name", "class"])) : (openBlock(), createBlock("span", {
							key: 1,
							"data-slot": "fallback",
							class: ui.value.fallback({ class: unref(uiProp)?.fallback })
						}, toDisplayString(fallback.value || "\xA0"), 3))])]),
						_: 3
					}, 16))];
				}),
				_: 3
			}), _parent);
		};
	}
});
var _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Avatar.vue");
	return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/vue/overrides/inertia/LinkBase.vue
var _sfc_main$2 = /* @__PURE__ */ Object.assign({ inheritAttrs: false }, {
	__name: "LinkBase",
	__ssrInlineRender: true,
	props: {
		as: {
			type: String,
			required: false,
			default: "button"
		},
		type: {
			type: String,
			required: false,
			default: "button"
		},
		disabled: {
			type: Boolean,
			required: false
		},
		onClick: {
			type: [Function, Array],
			required: false
		},
		href: {
			type: String,
			required: false
		},
		target: {
			type: [
				String,
				Object,
				null
			],
			required: false
		},
		rel: {
			type: [
				String,
				Object,
				null
			],
			required: false
		},
		active: {
			type: Boolean,
			required: false
		},
		isExternal: {
			type: Boolean,
			required: false
		}
	},
	setup(__props) {
		const props = __props;
		function onClickWrapper(e) {
			if (props.disabled) {
				e.stopPropagation();
				e.preventDefault();
				return;
			}
			if (props.onClick) for (const onClick of Array.isArray(props.onClick) ? props.onClick : [props.onClick]) onClick(e);
		}
		return (_ctx, _push, _parent, _attrs) => {
			if (!!__props.href && !__props.isExternal && !__props.disabled) _push(ssrRenderComponent(unref(Link), mergeProps({ href: __props.href }, {
				rel: __props.rel,
				target: __props.target,
				..._ctx.$attrs
			}, { onClick: onClickWrapper }, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
					else return [renderSlot(_ctx.$slots, "default")];
				}),
				_: 3
			}, _parent));
			else _push(ssrRenderComponent(unref(Primitive), mergeProps(__props.href ? {
				"as": "a",
				"href": __props.disabled ? void 0 : __props.href,
				"aria-disabled": __props.disabled ? "true" : void 0,
				"role": __props.disabled ? "link" : void 0,
				"tabindex": __props.disabled ? -1 : void 0,
				"rel": __props.rel,
				"target": __props.target,
				..._ctx.$attrs
			} : __props.as === "button" ? {
				as: __props.as,
				type: __props.type,
				disabled: __props.disabled,
				..._ctx.$attrs
			} : {
				as: __props.as,
				..._ctx.$attrs
			}, { onClick: onClickWrapper }, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
					else return [renderSlot(_ctx.$slots, "default")];
				}),
				_: 3
			}, _parent));
		};
	}
});
var _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/vue/overrides/inertia/LinkBase.vue");
	return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/link.ts
var link_default = {
	"base": "focus-visible:outline-primary",
	"variants": {
		"active": {
			"true": "text-primary",
			"false": "text-muted"
		},
		"disabled": { "true": "cursor-not-allowed opacity-75" }
	},
	"compoundVariants": [{
		"active": false,
		"disabled": false,
		"class": ["hover:text-default", "transition-colors"]
	}]
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/vue/overrides/inertia/Link.vue
var _sfc_main$1 = /* @__PURE__ */ Object.assign({ inheritAttrs: false }, {
	__name: "Link",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false,
			default: "button"
		},
		activeClass: {
			type: String,
			required: false
		},
		to: {
			type: String,
			required: false
		},
		href: {
			type: String,
			required: false
		},
		external: {
			type: Boolean,
			required: false
		},
		target: {
			type: [
				String,
				Object,
				null
			],
			required: false
		},
		rel: {
			type: [
				String,
				Object,
				null
			],
			required: false
		},
		noRel: {
			type: Boolean,
			required: false
		},
		ariaCurrentValue: {
			type: String,
			required: false,
			default: "page"
		},
		type: {
			type: null,
			required: false,
			default: "button"
		},
		disabled: {
			type: Boolean,
			required: false
		},
		active: {
			type: Boolean,
			required: false,
			default: void 0
		},
		exact: {
			type: Boolean,
			required: false
		},
		inactiveClass: {
			type: String,
			required: false
		},
		custom: {
			type: Boolean,
			required: false
		},
		raw: {
			type: Boolean,
			required: false
		},
		class: {
			type: null,
			required: false
		},
		data: {
			type: null,
			required: false
		},
		method: {
			type: String,
			required: false
		},
		replace: {
			type: Boolean,
			required: false
		},
		preserveScroll: {
			type: [
				Boolean,
				String,
				Function
			],
			required: false
		},
		preserveState: {
			type: [
				Boolean,
				String,
				Function
			],
			required: false
		},
		preserveUrl: {
			type: Boolean,
			required: false
		},
		only: {
			type: Array,
			required: false
		},
		except: {
			type: Array,
			required: false
		},
		headers: {
			type: Object,
			required: false
		},
		queryStringArrayFormat: {
			type: String,
			required: false
		},
		async: {
			type: Boolean,
			required: false
		},
		viewTransition: {
			type: [Boolean, Function],
			required: false
		},
		onCancelToken: {
			type: Function,
			required: false
		},
		onBefore: {
			type: Function,
			required: false
		},
		onBeforeUpdate: {
			type: Function,
			required: false
		},
		onStart: {
			type: Function,
			required: false
		},
		onProgress: {
			type: Function,
			required: false
		},
		onFinish: {
			type: Function,
			required: false
		},
		onCancel: {
			type: Function,
			required: false
		},
		onSuccess: {
			type: Function,
			required: false
		},
		onError: {
			type: Function,
			required: false
		},
		onFlash: {
			type: Function,
			required: false
		},
		onPrefetched: {
			type: Function,
			required: false
		},
		onPrefetching: {
			type: Function,
			required: false
		},
		prefetch: {
			type: [
				Boolean,
				String,
				Array
			],
			required: false
		},
		cacheFor: {
			type: null,
			required: false
		},
		cacheTags: {
			type: [String, Array],
			required: false
		}
	},
	setup(__props) {
		const props = __props;
		const page = usePage();
		const appConfig = useAppConfig();
		const routerLinkProps = useForwardProps(reactiveOmit(props, "as", "type", "disabled", "active", "exact", "activeClass", "inactiveClass", "to", "href", "raw", "custom", "class", "noRel"));
		const ui = computed(() => tv({
			extend: tv(link_default),
			...defu({ variants: { active: {
				true: mergeClasses(appConfig.ui?.link?.variants?.active?.true, props.activeClass),
				false: mergeClasses(appConfig.ui?.link?.variants?.active?.false, props.inactiveClass)
			} } }, appConfig.ui?.link || {})
		}));
		const href = computed(() => props.to ?? props.href);
		const isExternal = computed(() => {
			if (props.target === "_blank") return true;
			if (props.external) return true;
			if (!href.value) return false;
			return typeof href.value === "string" && hasProtocol(href.value, { acceptRelative: true });
		});
		const hasTarget = computed(() => !!props.target && props.target !== "_self");
		const rel = computed(() => {
			if (props.noRel) return null;
			if (props.rel !== void 0) return props.rel || null;
			if (isExternal.value || hasTarget.value) return "noopener noreferrer";
			return null;
		});
		const isLinkActive = computed(() => {
			if (props.active !== void 0) return props.active;
			if (!href.value) return false;
			if (props.exact && page.url === href.value) return true;
			if (!props.exact && page.url.startsWith(href.value)) return true;
			return false;
		});
		const linkClass = computed(() => {
			const active = isLinkActive.value;
			if (props.raw) return [props.class, active ? props.activeClass : props.inactiveClass];
			return ui.value({
				class: props.class,
				active,
				disabled: props.disabled
			});
		});
		return (_ctx, _push, _parent, _attrs) => {
			if (__props.custom) _push(ssrRenderComponent(unref(Slot), _attrs, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) ssrRenderSlot(_ctx.$slots, "default", {
						..._ctx.$attrs,
						...unref(routerLinkProps),
						as: __props.as,
						type: __props.type,
						disabled: __props.disabled,
						href: href.value,
						rel: rel.value,
						target: __props.target,
						active: isLinkActive.value,
						isExternal: isExternal.value
					}, null, _push, _parent, _scopeId);
					else return [renderSlot(_ctx.$slots, "default", {
						..._ctx.$attrs,
						...unref(routerLinkProps),
						as: __props.as,
						type: __props.type,
						disabled: __props.disabled,
						href: href.value,
						rel: rel.value,
						target: __props.target,
						active: isLinkActive.value,
						isExternal: isExternal.value
					})];
				}),
				_: 3
			}, _parent));
			else _push(ssrRenderComponent(_sfc_main$2, mergeProps({
				..._ctx.$attrs,
				...unref(routerLinkProps),
				as: __props.as,
				type: __props.type,
				disabled: __props.disabled,
				href: href.value,
				rel: rel.value,
				target: __props.target,
				isExternal: isExternal.value
			}, { class: linkClass.value }, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) ssrRenderSlot(_ctx.$slots, "default", { active: isLinkActive.value }, null, _push, _parent, _scopeId);
					else return [renderSlot(_ctx.$slots, "default", { active: isLinkActive.value })];
				}),
				_: 3
			}, _parent));
		};
	}
});
var _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/vue/overrides/inertia/Link.vue");
	return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/button.ts
var button_default = {
	"slots": {
		"base": ["rounded-md font-medium inline-flex items-center disabled:cursor-not-allowed aria-disabled:cursor-not-allowed disabled:opacity-75 aria-disabled:opacity-75", "transition-colors"],
		"label": "truncate",
		"leadingIcon": "shrink-0",
		"leadingAvatar": "shrink-0",
		"leadingAvatarSize": "",
		"trailingIcon": "shrink-0"
	},
	"variants": {
		"fieldGroup": {
			"horizontal": "not-only:first:rounded-e-none not-only:last:rounded-s-none not-last:not-first:rounded-none focus-visible:z-[1]",
			"vertical": "not-only:first:rounded-b-none not-only:last:rounded-t-none not-last:not-first:rounded-none focus-visible:z-[1]"
		},
		"color": {
			"primary": "",
			"secondary": "",
			"success": "",
			"info": "",
			"warning": "",
			"error": "",
			"neutral": ""
		},
		"variant": {
			"solid": "",
			"outline": "",
			"soft": "",
			"subtle": "",
			"ghost": "",
			"link": ""
		},
		"size": {
			"xs": {
				"base": "px-2 py-1 text-xs gap-1",
				"leadingIcon": "size-4",
				"leadingAvatarSize": "3xs",
				"trailingIcon": "size-4"
			},
			"sm": {
				"base": "px-2.5 py-1.5 text-xs gap-1.5",
				"leadingIcon": "size-4",
				"leadingAvatarSize": "3xs",
				"trailingIcon": "size-4"
			},
			"md": {
				"base": "px-2.5 py-1.5 text-sm gap-1.5",
				"leadingIcon": "size-5",
				"leadingAvatarSize": "2xs",
				"trailingIcon": "size-5"
			},
			"lg": {
				"base": "px-3 py-2 text-sm gap-2",
				"leadingIcon": "size-5",
				"leadingAvatarSize": "2xs",
				"trailingIcon": "size-5"
			},
			"xl": {
				"base": "px-3 py-2 text-base gap-2",
				"leadingIcon": "size-6",
				"leadingAvatarSize": "xs",
				"trailingIcon": "size-6"
			}
		},
		"block": { "true": {
			"base": "w-full justify-center",
			"trailingIcon": "ms-auto"
		} },
		"square": { "true": "" },
		"leading": { "true": "" },
		"trailing": { "true": "" },
		"loading": { "true": "" },
		"active": {
			"true": { "base": "" },
			"false": { "base": "" }
		}
	},
	"compoundVariants": [
		{
			"color": "primary",
			"variant": "solid",
			"class": "text-inverted bg-primary hover:bg-primary/75 active:bg-primary/75 disabled:bg-primary aria-disabled:bg-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
		},
		{
			"color": "secondary",
			"variant": "solid",
			"class": "text-inverted bg-secondary hover:bg-secondary/75 active:bg-secondary/75 disabled:bg-secondary aria-disabled:bg-secondary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary"
		},
		{
			"color": "success",
			"variant": "solid",
			"class": "text-inverted bg-success hover:bg-success/75 active:bg-success/75 disabled:bg-success aria-disabled:bg-success focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-success"
		},
		{
			"color": "info",
			"variant": "solid",
			"class": "text-inverted bg-info hover:bg-info/75 active:bg-info/75 disabled:bg-info aria-disabled:bg-info focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-info"
		},
		{
			"color": "warning",
			"variant": "solid",
			"class": "text-inverted bg-warning hover:bg-warning/75 active:bg-warning/75 disabled:bg-warning aria-disabled:bg-warning focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-warning"
		},
		{
			"color": "error",
			"variant": "solid",
			"class": "text-inverted bg-error hover:bg-error/75 active:bg-error/75 disabled:bg-error aria-disabled:bg-error focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-error"
		},
		{
			"color": "primary",
			"variant": "outline",
			"class": "ring ring-inset ring-primary/50 text-primary hover:bg-primary/10 active:bg-primary/10 disabled:bg-transparent aria-disabled:bg-transparent dark:disabled:bg-transparent dark:aria-disabled:bg-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
		},
		{
			"color": "secondary",
			"variant": "outline",
			"class": "ring ring-inset ring-secondary/50 text-secondary hover:bg-secondary/10 active:bg-secondary/10 disabled:bg-transparent aria-disabled:bg-transparent dark:disabled:bg-transparent dark:aria-disabled:bg-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary"
		},
		{
			"color": "success",
			"variant": "outline",
			"class": "ring ring-inset ring-success/50 text-success hover:bg-success/10 active:bg-success/10 disabled:bg-transparent aria-disabled:bg-transparent dark:disabled:bg-transparent dark:aria-disabled:bg-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-success"
		},
		{
			"color": "info",
			"variant": "outline",
			"class": "ring ring-inset ring-info/50 text-info hover:bg-info/10 active:bg-info/10 disabled:bg-transparent aria-disabled:bg-transparent dark:disabled:bg-transparent dark:aria-disabled:bg-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-info"
		},
		{
			"color": "warning",
			"variant": "outline",
			"class": "ring ring-inset ring-warning/50 text-warning hover:bg-warning/10 active:bg-warning/10 disabled:bg-transparent aria-disabled:bg-transparent dark:disabled:bg-transparent dark:aria-disabled:bg-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-warning"
		},
		{
			"color": "error",
			"variant": "outline",
			"class": "ring ring-inset ring-error/50 text-error hover:bg-error/10 active:bg-error/10 disabled:bg-transparent aria-disabled:bg-transparent dark:disabled:bg-transparent dark:aria-disabled:bg-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-error"
		},
		{
			"color": "primary",
			"variant": "soft",
			"class": "text-primary bg-primary/10 hover:bg-primary/15 active:bg-primary/15 focus:outline-none focus-visible:bg-primary/15 disabled:bg-primary/10 aria-disabled:bg-primary/10"
		},
		{
			"color": "secondary",
			"variant": "soft",
			"class": "text-secondary bg-secondary/10 hover:bg-secondary/15 active:bg-secondary/15 focus:outline-none focus-visible:bg-secondary/15 disabled:bg-secondary/10 aria-disabled:bg-secondary/10"
		},
		{
			"color": "success",
			"variant": "soft",
			"class": "text-success bg-success/10 hover:bg-success/15 active:bg-success/15 focus:outline-none focus-visible:bg-success/15 disabled:bg-success/10 aria-disabled:bg-success/10"
		},
		{
			"color": "info",
			"variant": "soft",
			"class": "text-info bg-info/10 hover:bg-info/15 active:bg-info/15 focus:outline-none focus-visible:bg-info/15 disabled:bg-info/10 aria-disabled:bg-info/10"
		},
		{
			"color": "warning",
			"variant": "soft",
			"class": "text-warning bg-warning/10 hover:bg-warning/15 active:bg-warning/15 focus:outline-none focus-visible:bg-warning/15 disabled:bg-warning/10 aria-disabled:bg-warning/10"
		},
		{
			"color": "error",
			"variant": "soft",
			"class": "text-error bg-error/10 hover:bg-error/15 active:bg-error/15 focus:outline-none focus-visible:bg-error/15 disabled:bg-error/10 aria-disabled:bg-error/10"
		},
		{
			"color": "primary",
			"variant": "subtle",
			"class": "text-primary ring ring-inset ring-primary/25 bg-primary/10 hover:bg-primary/15 active:bg-primary/15 disabled:bg-primary/10 aria-disabled:bg-primary/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
		},
		{
			"color": "secondary",
			"variant": "subtle",
			"class": "text-secondary ring ring-inset ring-secondary/25 bg-secondary/10 hover:bg-secondary/15 active:bg-secondary/15 disabled:bg-secondary/10 aria-disabled:bg-secondary/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary"
		},
		{
			"color": "success",
			"variant": "subtle",
			"class": "text-success ring ring-inset ring-success/25 bg-success/10 hover:bg-success/15 active:bg-success/15 disabled:bg-success/10 aria-disabled:bg-success/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-success"
		},
		{
			"color": "info",
			"variant": "subtle",
			"class": "text-info ring ring-inset ring-info/25 bg-info/10 hover:bg-info/15 active:bg-info/15 disabled:bg-info/10 aria-disabled:bg-info/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-info"
		},
		{
			"color": "warning",
			"variant": "subtle",
			"class": "text-warning ring ring-inset ring-warning/25 bg-warning/10 hover:bg-warning/15 active:bg-warning/15 disabled:bg-warning/10 aria-disabled:bg-warning/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-warning"
		},
		{
			"color": "error",
			"variant": "subtle",
			"class": "text-error ring ring-inset ring-error/25 bg-error/10 hover:bg-error/15 active:bg-error/15 disabled:bg-error/10 aria-disabled:bg-error/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-error"
		},
		{
			"color": "primary",
			"variant": "ghost",
			"class": "text-primary hover:bg-primary/10 active:bg-primary/10 focus:outline-none focus-visible:bg-primary/10 disabled:bg-transparent aria-disabled:bg-transparent dark:disabled:bg-transparent dark:aria-disabled:bg-transparent"
		},
		{
			"color": "secondary",
			"variant": "ghost",
			"class": "text-secondary hover:bg-secondary/10 active:bg-secondary/10 focus:outline-none focus-visible:bg-secondary/10 disabled:bg-transparent aria-disabled:bg-transparent dark:disabled:bg-transparent dark:aria-disabled:bg-transparent"
		},
		{
			"color": "success",
			"variant": "ghost",
			"class": "text-success hover:bg-success/10 active:bg-success/10 focus:outline-none focus-visible:bg-success/10 disabled:bg-transparent aria-disabled:bg-transparent dark:disabled:bg-transparent dark:aria-disabled:bg-transparent"
		},
		{
			"color": "info",
			"variant": "ghost",
			"class": "text-info hover:bg-info/10 active:bg-info/10 focus:outline-none focus-visible:bg-info/10 disabled:bg-transparent aria-disabled:bg-transparent dark:disabled:bg-transparent dark:aria-disabled:bg-transparent"
		},
		{
			"color": "warning",
			"variant": "ghost",
			"class": "text-warning hover:bg-warning/10 active:bg-warning/10 focus:outline-none focus-visible:bg-warning/10 disabled:bg-transparent aria-disabled:bg-transparent dark:disabled:bg-transparent dark:aria-disabled:bg-transparent"
		},
		{
			"color": "error",
			"variant": "ghost",
			"class": "text-error hover:bg-error/10 active:bg-error/10 focus:outline-none focus-visible:bg-error/10 disabled:bg-transparent aria-disabled:bg-transparent dark:disabled:bg-transparent dark:aria-disabled:bg-transparent"
		},
		{
			"color": "primary",
			"variant": "link",
			"class": "text-primary hover:text-primary/75 active:text-primary/75 disabled:text-primary aria-disabled:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary"
		},
		{
			"color": "secondary",
			"variant": "link",
			"class": "text-secondary hover:text-secondary/75 active:text-secondary/75 disabled:text-secondary aria-disabled:text-secondary focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-secondary"
		},
		{
			"color": "success",
			"variant": "link",
			"class": "text-success hover:text-success/75 active:text-success/75 disabled:text-success aria-disabled:text-success focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-success"
		},
		{
			"color": "info",
			"variant": "link",
			"class": "text-info hover:text-info/75 active:text-info/75 disabled:text-info aria-disabled:text-info focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-info"
		},
		{
			"color": "warning",
			"variant": "link",
			"class": "text-warning hover:text-warning/75 active:text-warning/75 disabled:text-warning aria-disabled:text-warning focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-warning"
		},
		{
			"color": "error",
			"variant": "link",
			"class": "text-error hover:text-error/75 active:text-error/75 disabled:text-error aria-disabled:text-error focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-error"
		},
		{
			"color": "neutral",
			"variant": "solid",
			"class": "text-inverted bg-inverted hover:bg-inverted/90 active:bg-inverted/90 disabled:bg-inverted aria-disabled:bg-inverted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-inverted"
		},
		{
			"color": "neutral",
			"variant": "outline",
			"class": "ring ring-inset ring-accented text-default bg-default hover:bg-elevated active:bg-elevated disabled:bg-default aria-disabled:bg-default focus:outline-none focus-visible:ring-2 focus-visible:ring-inverted"
		},
		{
			"color": "neutral",
			"variant": "soft",
			"class": "text-default bg-elevated hover:bg-accented/75 active:bg-accented/75 focus:outline-none focus-visible:bg-accented/75 disabled:bg-elevated aria-disabled:bg-elevated"
		},
		{
			"color": "neutral",
			"variant": "subtle",
			"class": "ring ring-inset ring-accented text-default bg-elevated hover:bg-accented/75 active:bg-accented/75 disabled:bg-elevated aria-disabled:bg-elevated focus:outline-none focus-visible:ring-2 focus-visible:ring-inverted"
		},
		{
			"color": "neutral",
			"variant": "ghost",
			"class": "text-default hover:bg-elevated active:bg-elevated focus:outline-none focus-visible:bg-elevated hover:disabled:bg-transparent dark:hover:disabled:bg-transparent hover:aria-disabled:bg-transparent dark:hover:aria-disabled:bg-transparent"
		},
		{
			"color": "neutral",
			"variant": "link",
			"class": "text-muted hover:text-default active:text-default disabled:text-muted aria-disabled:text-muted focus:outline-none focus-visible:ring-inset focus-visible:ring-2 focus-visible:ring-inverted"
		},
		{
			"size": "xs",
			"square": true,
			"class": "p-1"
		},
		{
			"size": "sm",
			"square": true,
			"class": "p-1.5"
		},
		{
			"size": "md",
			"square": true,
			"class": "p-1.5"
		},
		{
			"size": "lg",
			"square": true,
			"class": "p-2"
		},
		{
			"size": "xl",
			"square": true,
			"class": "p-2"
		},
		{
			"loading": true,
			"leading": true,
			"class": { "leadingIcon": "animate-spin" }
		},
		{
			"loading": true,
			"leading": false,
			"trailing": true,
			"class": { "trailingIcon": "animate-spin" }
		}
	],
	"defaultVariants": {
		"color": "primary",
		"variant": "solid",
		"size": "md"
	}
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Button.vue
var _sfc_main = {
	__name: "Button",
	__ssrInlineRender: true,
	props: {
		label: {
			type: String,
			required: false
		},
		color: {
			type: null,
			required: false
		},
		activeColor: {
			type: null,
			required: false
		},
		variant: {
			type: null,
			required: false
		},
		activeVariant: {
			type: null,
			required: false
		},
		size: {
			type: null,
			required: false
		},
		square: {
			type: Boolean,
			required: false
		},
		block: {
			type: Boolean,
			required: false
		},
		loadingAuto: {
			type: Boolean,
			required: false
		},
		onClick: {
			type: [Function, Array],
			required: false
		},
		class: {
			type: null,
			required: false
		},
		ui: {
			type: Object,
			required: false
		},
		icon: {
			type: null,
			required: false
		},
		avatar: {
			type: Object,
			required: false
		},
		leading: {
			type: Boolean,
			required: false
		},
		leadingIcon: {
			type: null,
			required: false
		},
		trailing: {
			type: Boolean,
			required: false
		},
		trailingIcon: {
			type: null,
			required: false
		},
		loading: {
			type: Boolean,
			required: false
		},
		loadingIcon: {
			type: null,
			required: false
		},
		as: {
			type: null,
			required: false
		},
		type: {
			type: null,
			required: false
		},
		disabled: {
			type: Boolean,
			required: false
		},
		active: {
			type: Boolean,
			required: false
		},
		exact: {
			type: Boolean,
			required: false
		},
		exactQuery: {
			type: [Boolean, String],
			required: false
		},
		exactHash: {
			type: Boolean,
			required: false
		},
		inactiveClass: {
			type: String,
			required: false
		},
		locale: {
			type: [Boolean, String],
			required: false
		},
		to: {
			type: null,
			required: false
		},
		href: {
			type: null,
			required: false
		},
		external: {
			type: Boolean,
			required: false
		},
		target: {
			type: [
				String,
				Object,
				null
			],
			required: false
		},
		rel: {
			type: [
				String,
				Object,
				null
			],
			required: false
		},
		noRel: {
			type: Boolean,
			required: false
		},
		prefetchedClass: {
			type: String,
			required: false
		},
		prefetch: {
			type: Boolean,
			required: false
		},
		prefetchOn: {
			type: [String, Object],
			required: false
		},
		noPrefetch: {
			type: Boolean,
			required: false
		},
		trailingSlash: {
			type: String,
			required: false
		},
		activeClass: {
			type: String,
			required: false
		},
		exactActiveClass: {
			type: String,
			required: false
		},
		ariaCurrentValue: {
			type: String,
			required: false
		},
		viewTransition: {
			type: Boolean,
			required: false
		},
		replace: {
			type: Boolean,
			required: false
		}
	},
	setup(__props) {
		const props = __props;
		const slots = useSlots();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("button", props);
		const { orientation, size: buttonSize } = useFieldGroup(props);
		const linkProps = useForwardProps(pickLinkProps(props));
		const loadingAutoState = ref(false);
		const formLoading = inject(formLoadingInjectionKey, void 0);
		async function onClickWrapper(event) {
			loadingAutoState.value = true;
			const callbacks = Array.isArray(props.onClick) ? props.onClick : [props.onClick];
			try {
				await Promise.all(callbacks.map((fn) => fn?.(event)));
			} finally {
				loadingAutoState.value = false;
			}
		}
		const isLoading = computed(() => {
			return props.loading || props.loadingAuto && (loadingAutoState.value || formLoading?.value && props.type === "submit");
		});
		const { isLeading, isTrailing, leadingIconName, trailingIconName } = useComponentIcons(computed(() => ({
			...props,
			loading: isLoading.value
		})));
		const ui = computed(() => tv({
			extend: tv(button_default),
			...defu({ variants: { active: {
				true: { base: mergeClasses(appConfig.ui?.button?.variants?.active?.true?.base, props.activeClass) },
				false: { base: mergeClasses(appConfig.ui?.button?.variants?.active?.false?.base, props.inactiveClass) }
			} } }, appConfig.ui?.button || {})
		})({
			color: props.color,
			variant: props.variant,
			size: buttonSize.value,
			loading: isLoading.value,
			block: props.block,
			square: props.square || !slots.default && !props.label,
			leading: isLeading.value,
			trailing: isTrailing.value,
			fieldGroup: orientation.value
		}));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(_sfc_main$1, mergeProps({
				type: __props.type,
				disabled: __props.disabled || isLoading.value
			}, unref(omit)(unref(linkProps), [
				"type",
				"disabled",
				"onClick"
			]), { custom: "" }, _attrs), {
				default: withCtx(({ active, ...slotProps }, _push, _parent, _scopeId) => {
					if (_push) _push(ssrRenderComponent(_sfc_main$2, mergeProps(slotProps, {
						"data-slot": "base",
						class: ui.value.base({
							class: [unref(uiProp)?.base, props.class],
							active,
							...active && __props.activeVariant ? { variant: __props.activeVariant } : {},
							...active && __props.activeColor ? { color: __props.activeColor } : {}
						}),
						onClick: onClickWrapper
					}), {
						default: withCtx((_, _push, _parent, _scopeId) => {
							if (_push) {
								ssrRenderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => {
									if (unref(isLeading) && unref(leadingIconName)) _push(ssrRenderComponent(_sfc_main$5, {
										name: unref(leadingIconName),
										"data-slot": "leadingIcon",
										class: ui.value.leadingIcon({
											class: unref(uiProp)?.leadingIcon,
											active
										})
									}, null, _parent, _scopeId));
									else if (!!__props.avatar) _push(ssrRenderComponent(_sfc_main$3, mergeProps({ size: unref(uiProp)?.leadingAvatarSize || ui.value.leadingAvatarSize() }, __props.avatar, {
										"data-slot": "leadingAvatar",
										class: ui.value.leadingAvatar({
											class: unref(uiProp)?.leadingAvatar,
											active
										})
									}), null, _parent, _scopeId));
									else _push(`<!---->`);
								}, _push, _parent, _scopeId);
								ssrRenderSlot(_ctx.$slots, "default", { ui: ui.value }, () => {
									if (__props.label !== void 0 && __props.label !== null) _push(`<span data-slot="label" class="${ssrRenderClass(ui.value.label({
										class: unref(uiProp)?.label,
										active
									}))}"${_scopeId}>${ssrInterpolate(__props.label)}</span>`);
									else _push(`<!---->`);
								}, _push, _parent, _scopeId);
								ssrRenderSlot(_ctx.$slots, "trailing", { ui: ui.value }, () => {
									if (unref(isTrailing) && unref(trailingIconName)) _push(ssrRenderComponent(_sfc_main$5, {
										name: unref(trailingIconName),
										"data-slot": "trailingIcon",
										class: ui.value.trailingIcon({
											class: unref(uiProp)?.trailingIcon,
											active
										})
									}, null, _parent, _scopeId));
									else _push(`<!---->`);
								}, _push, _parent, _scopeId);
							} else return [
								renderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => [unref(isLeading) && unref(leadingIconName) ? (openBlock(), createBlock(_sfc_main$5, {
									key: 0,
									name: unref(leadingIconName),
									"data-slot": "leadingIcon",
									class: ui.value.leadingIcon({
										class: unref(uiProp)?.leadingIcon,
										active
									})
								}, null, 8, ["name", "class"])) : !!__props.avatar ? (openBlock(), createBlock(_sfc_main$3, mergeProps({
									key: 1,
									size: unref(uiProp)?.leadingAvatarSize || ui.value.leadingAvatarSize()
								}, __props.avatar, {
									"data-slot": "leadingAvatar",
									class: ui.value.leadingAvatar({
										class: unref(uiProp)?.leadingAvatar,
										active
									})
								}), null, 16, ["size", "class"])) : createCommentVNode("", true)]),
								renderSlot(_ctx.$slots, "default", { ui: ui.value }, () => [__props.label !== void 0 && __props.label !== null ? (openBlock(), createBlock("span", {
									key: 0,
									"data-slot": "label",
									class: ui.value.label({
										class: unref(uiProp)?.label,
										active
									})
								}, toDisplayString(__props.label), 3)) : createCommentVNode("", true)]),
								renderSlot(_ctx.$slots, "trailing", { ui: ui.value }, () => [unref(isTrailing) && unref(trailingIconName) ? (openBlock(), createBlock(_sfc_main$5, {
									key: 0,
									name: unref(trailingIconName),
									"data-slot": "trailingIcon",
									class: ui.value.trailingIcon({
										class: unref(uiProp)?.trailingIcon,
										active
									})
								}, null, 8, ["name", "class"])) : createCommentVNode("", true)])
							];
						}),
						_: 2
					}, _parent, _scopeId));
					else return [createVNode(_sfc_main$2, mergeProps(slotProps, {
						"data-slot": "base",
						class: ui.value.base({
							class: [unref(uiProp)?.base, props.class],
							active,
							...active && __props.activeVariant ? { variant: __props.activeVariant } : {},
							...active && __props.activeColor ? { color: __props.activeColor } : {}
						}),
						onClick: onClickWrapper
					}), {
						default: withCtx(() => [
							renderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => [unref(isLeading) && unref(leadingIconName) ? (openBlock(), createBlock(_sfc_main$5, {
								key: 0,
								name: unref(leadingIconName),
								"data-slot": "leadingIcon",
								class: ui.value.leadingIcon({
									class: unref(uiProp)?.leadingIcon,
									active
								})
							}, null, 8, ["name", "class"])) : !!__props.avatar ? (openBlock(), createBlock(_sfc_main$3, mergeProps({
								key: 1,
								size: unref(uiProp)?.leadingAvatarSize || ui.value.leadingAvatarSize()
							}, __props.avatar, {
								"data-slot": "leadingAvatar",
								class: ui.value.leadingAvatar({
									class: unref(uiProp)?.leadingAvatar,
									active
								})
							}), null, 16, ["size", "class"])) : createCommentVNode("", true)]),
							renderSlot(_ctx.$slots, "default", { ui: ui.value }, () => [__props.label !== void 0 && __props.label !== null ? (openBlock(), createBlock("span", {
								key: 0,
								"data-slot": "label",
								class: ui.value.label({
									class: unref(uiProp)?.label,
									active
								})
							}, toDisplayString(__props.label), 3)) : createCommentVNode("", true)]),
							renderSlot(_ctx.$slots, "trailing", { ui: ui.value }, () => [unref(isTrailing) && unref(trailingIconName) ? (openBlock(), createBlock(_sfc_main$5, {
								key: 0,
								name: unref(trailingIconName),
								"data-slot": "trailingIcon",
								class: ui.value.trailingIcon({
									class: unref(uiProp)?.trailingIcon,
									active
								})
							}, null, 8, ["name", "class"])) : createCommentVNode("", true)])
						]),
						_: 2
					}, 1040, ["class"])];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Button.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/composables/usePortal.js
var portalTargetInjectionKey = Symbol("nuxt-ui.portal-target");
function usePortal(portal) {
	const globalPortal = inject(portalTargetInjectionKey, void 0);
	const value = computed(() => portal.value === true ? globalPortal?.value : portal.value);
	const disabled = computed(() => typeof value.value === "boolean" ? !value.value : false);
	const to = computed(() => typeof value.value === "boolean" ? "body" : value.value);
	return computed(() => ({
		to: to.value,
		disabled: disabled.value
	}));
}
//#endregion
export { getDisplayValue as A, useComponentIcons as C, useLocale as D, localeContextInjectionKey as E, useAppConfig as F, looseToNumber as M, omit as N, compare as O, useKbd as P, useFieldGroup as S, useState as T, formStateInjectionKey as _, _sfc_main$2 as a, FieldGroupReset as b, _sfc_main$5 as c, formBusInjectionKey as d, formErrorsInjectionKey as f, formOptionsInjectionKey as g, formLoadingInjectionKey as h, _sfc_main$1 as i, isArrayOfArray as j, get as k, pickLinkProps as l, formInputsInjectionKey as m, usePortal as n, _sfc_main$3 as o, formFieldInjectionKey as p, _sfc_main as r, _sfc_main$4 as s, portalTargetInjectionKey as t, tv as u, inputIdInjectionKey as v, useComponentUI as w, fieldGroupInjectionKey as x, useFormField as y };

//# sourceMappingURL=usePortal-DZb6nPjI.js.map