import { C as useComponentIcons, F as useAppConfig, M as looseToNumber, S as useFieldGroup, _ as formStateInjectionKey, c as _sfc_main$3, d as formBusInjectionKey, f as formErrorsInjectionKey, g as formOptionsInjectionKey, h as formLoadingInjectionKey, m as formInputsInjectionKey, o as _sfc_main$4, p as formFieldInjectionKey, u as tv, v as inputIdInjectionKey, w as useComponentUI, y as useFormField } from "./usePortal-DZb6nPjI.js";
import { ssrInterpolate, ssrRenderAttr, ssrRenderAttrs, ssrRenderClass, ssrRenderComponent, ssrRenderSlot, ssrRenderVNode } from "vue/server-renderer";
import { computed, createBlock, createCommentVNode, createTextVNode, createVNode, inject, mergeProps, nextTick, onMounted, onUnmounted, openBlock, provide, reactive, readonly, ref, renderSlot, resolveDynamicComponent, toDisplayString, unref, useId, useSSRContext, useSlots, useTemplateRef, watch, withCtx } from "vue";
import { Label, Primitive } from "reka-ui";
import { useEventBus, useVModel } from "@vueuse/core";
//#region virtual:nuxt-ui-templates/ui/input.ts
var input_default = {
	"slots": {
		"root": "relative inline-flex items-center",
		"base": ["w-full rounded-md border-0 appearance-none placeholder:text-dimmed focus:outline-none disabled:cursor-not-allowed disabled:opacity-75", "transition-colors"],
		"leading": "absolute inset-y-0 start-0 flex items-center",
		"leadingIcon": "shrink-0 text-dimmed",
		"leadingAvatar": "shrink-0",
		"leadingAvatarSize": "",
		"trailing": "absolute inset-y-0 end-0 flex items-center",
		"trailingIcon": "shrink-0 text-dimmed"
	},
	"variants": {
		"fieldGroup": {
			"horizontal": {
				"root": "group has-focus-visible:z-[1]",
				"base": "group-not-only:group-first:rounded-e-none group-not-only:group-last:rounded-s-none group-not-last:group-not-first:rounded-none"
			},
			"vertical": {
				"root": "group has-focus-visible:z-[1]",
				"base": "group-not-only:group-first:rounded-b-none group-not-only:group-last:rounded-t-none group-not-last:group-not-first:rounded-none"
			}
		},
		"size": {
			"xs": {
				"base": "px-2 py-1 text-sm/4 gap-1",
				"leading": "ps-2",
				"trailing": "pe-2",
				"leadingIcon": "size-4",
				"leadingAvatarSize": "3xs",
				"trailingIcon": "size-4"
			},
			"sm": {
				"base": "px-2.5 py-1.5 text-sm/4 gap-1.5",
				"leading": "ps-2.5",
				"trailing": "pe-2.5",
				"leadingIcon": "size-4",
				"leadingAvatarSize": "3xs",
				"trailingIcon": "size-4"
			},
			"md": {
				"base": "px-2.5 py-1.5 text-base/5 gap-1.5",
				"leading": "ps-2.5",
				"trailing": "pe-2.5",
				"leadingIcon": "size-5",
				"leadingAvatarSize": "2xs",
				"trailingIcon": "size-5"
			},
			"lg": {
				"base": "px-3 py-2 text-base/5 gap-2",
				"leading": "ps-3",
				"trailing": "pe-3",
				"leadingIcon": "size-5",
				"leadingAvatarSize": "2xs",
				"trailingIcon": "size-5"
			},
			"xl": {
				"base": "px-3 py-2 text-base gap-2",
				"leading": "ps-3",
				"trailing": "pe-3",
				"leadingIcon": "size-6",
				"leadingAvatarSize": "xs",
				"trailingIcon": "size-6"
			}
		},
		"variant": {
			"outline": "text-highlighted bg-default ring ring-inset ring-accented",
			"soft": "text-highlighted bg-elevated/50 hover:bg-elevated focus:bg-elevated disabled:bg-elevated/50",
			"subtle": "text-highlighted bg-elevated ring ring-inset ring-accented",
			"ghost": "text-highlighted bg-transparent hover:bg-elevated focus:bg-elevated disabled:bg-transparent dark:disabled:bg-transparent",
			"none": "text-highlighted bg-transparent"
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
		"leading": { "true": "" },
		"trailing": { "true": "" },
		"loading": { "true": "" },
		"highlight": { "true": "" },
		"fixed": { "false": "" },
		"type": { "file": "file:me-1.5 file:font-medium file:text-muted file:outline-none" }
	},
	"compoundVariants": [
		{
			"color": "primary",
			"variant": ["outline", "subtle"],
			"class": "focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary"
		},
		{
			"color": "secondary",
			"variant": ["outline", "subtle"],
			"class": "focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-secondary"
		},
		{
			"color": "success",
			"variant": ["outline", "subtle"],
			"class": "focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-success"
		},
		{
			"color": "info",
			"variant": ["outline", "subtle"],
			"class": "focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-info"
		},
		{
			"color": "warning",
			"variant": ["outline", "subtle"],
			"class": "focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-warning"
		},
		{
			"color": "error",
			"variant": ["outline", "subtle"],
			"class": "focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-error"
		},
		{
			"color": "primary",
			"highlight": true,
			"class": "ring ring-inset ring-primary"
		},
		{
			"color": "secondary",
			"highlight": true,
			"class": "ring ring-inset ring-secondary"
		},
		{
			"color": "success",
			"highlight": true,
			"class": "ring ring-inset ring-success"
		},
		{
			"color": "info",
			"highlight": true,
			"class": "ring ring-inset ring-info"
		},
		{
			"color": "warning",
			"highlight": true,
			"class": "ring ring-inset ring-warning"
		},
		{
			"color": "error",
			"highlight": true,
			"class": "ring ring-inset ring-error"
		},
		{
			"color": "neutral",
			"variant": ["outline", "subtle"],
			"class": "focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-inverted"
		},
		{
			"color": "neutral",
			"highlight": true,
			"class": "ring ring-inset ring-inverted"
		},
		{
			"leading": true,
			"size": "xs",
			"class": "ps-7"
		},
		{
			"leading": true,
			"size": "sm",
			"class": "ps-8"
		},
		{
			"leading": true,
			"size": "md",
			"class": "ps-9"
		},
		{
			"leading": true,
			"size": "lg",
			"class": "ps-10"
		},
		{
			"leading": true,
			"size": "xl",
			"class": "ps-11"
		},
		{
			"trailing": true,
			"size": "xs",
			"class": "pe-7"
		},
		{
			"trailing": true,
			"size": "sm",
			"class": "pe-8"
		},
		{
			"trailing": true,
			"size": "md",
			"class": "pe-9"
		},
		{
			"trailing": true,
			"size": "lg",
			"class": "pe-10"
		},
		{
			"trailing": true,
			"size": "xl",
			"class": "pe-11"
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
		},
		{
			"fixed": false,
			"size": "xs",
			"class": "md:text-xs"
		},
		{
			"fixed": false,
			"size": "sm",
			"class": "md:text-xs"
		},
		{
			"fixed": false,
			"size": "md",
			"class": "md:text-sm"
		},
		{
			"fixed": false,
			"size": "lg",
			"class": "md:text-sm"
		}
	],
	"defaultVariants": {
		"size": "md",
		"color": "primary",
		"variant": "outline"
	}
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Input.vue
var _sfc_main$2 = /* @__PURE__ */ Object.assign({ inheritAttrs: false }, {
	__name: "Input",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false
		},
		id: {
			type: String,
			required: false
		},
		name: {
			type: String,
			required: false
		},
		type: {
			type: null,
			required: false,
			default: "text"
		},
		placeholder: {
			type: String,
			required: false
		},
		color: {
			type: null,
			required: false
		},
		variant: {
			type: null,
			required: false
		},
		size: {
			type: null,
			required: false
		},
		required: {
			type: Boolean,
			required: false
		},
		autocomplete: {
			type: [String, Object],
			required: false,
			default: "off"
		},
		autofocus: {
			type: Boolean,
			required: false
		},
		autofocusDelay: {
			type: Number,
			required: false,
			default: 0
		},
		disabled: {
			type: Boolean,
			required: false
		},
		highlight: {
			type: Boolean,
			required: false
		},
		fixed: {
			type: Boolean,
			required: false
		},
		modelValue: {
			type: null,
			required: false
		},
		defaultValue: {
			type: null,
			required: false
		},
		modelModifiers: {
			type: null,
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
		}
	},
	emits: [
		"update:modelValue",
		"blur",
		"change"
	],
	setup(__props, { expose: __expose, emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const slots = useSlots();
		const modelValue = useVModel(props, "modelValue", emits, { defaultValue: props.defaultValue });
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("input", props);
		const { emitFormBlur, emitFormInput, emitFormChange, size: formFieldSize, color, id, name, highlight, disabled, emitFormFocus, ariaAttrs } = useFormField(props, { deferInputValidation: true });
		const { orientation, size: fieldGroupSize } = useFieldGroup(props);
		const { isLeading, isTrailing, leadingIconName, trailingIconName } = useComponentIcons(props);
		const inputSize = computed(() => fieldGroupSize.value || formFieldSize.value);
		const ui = computed(() => tv({
			extend: tv(input_default),
			...appConfig.ui?.input || {}
		})({
			type: props.type,
			color: color.value,
			variant: props.variant,
			size: inputSize?.value,
			loading: props.loading,
			highlight: highlight.value,
			fixed: props.fixed,
			leading: isLeading.value || !!props.avatar || !!slots.leading,
			trailing: isTrailing.value || !!slots.trailing,
			fieldGroup: orientation.value
		}));
		const inputRef = useTemplateRef("inputRef");
		function updateInput(value) {
			if (props.modelModifiers?.trim && (typeof value === "string" || value === null || value === void 0)) value = value?.trim() ?? null;
			if (props.modelModifiers?.number || props.type === "number") value = looseToNumber(value);
			if (props.modelModifiers?.nullable) value ||= null;
			if (props.modelModifiers?.optional && !props.modelModifiers?.nullable && value !== null) value ||= void 0;
			modelValue.value = value;
			emitFormInput();
		}
		function onInput(event) {
			if (!props.modelModifiers?.lazy) updateInput(event.target.value);
		}
		function onChange(event) {
			const value = event.target.value;
			if (props.modelModifiers?.lazy) updateInput(value);
			if (props.modelModifiers?.trim) event.target.value = value.trim();
			emitFormChange();
			emits("change", event);
		}
		function onBlur(event) {
			emitFormBlur();
			emits("blur", event);
		}
		function autoFocus() {
			if (props.autofocus) inputRef.value?.focus();
		}
		onMounted(() => {
			setTimeout(() => {
				autoFocus();
			}, props.autofocusDelay);
		});
		__expose({ inputRef });
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(Primitive), mergeProps({
				as: __props.as,
				"data-slot": "root",
				class: ui.value.root({ class: [unref(uiProp)?.root, props.class] })
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<input${ssrRenderAttrs(mergeProps({
							id: unref(id),
							ref_key: "inputRef",
							ref: inputRef,
							type: __props.type,
							value: unref(modelValue),
							name: unref(name),
							placeholder: __props.placeholder,
							"data-slot": "base",
							class: ui.value.base({ class: unref(uiProp)?.base }),
							disabled: unref(disabled),
							required: __props.required,
							autocomplete: __props.autocomplete
						}, {
							..._ctx.$attrs,
							...unref(ariaAttrs)
						}))}${_scopeId}>`);
						ssrRenderSlot(_ctx.$slots, "default", { ui: ui.value }, null, _push, _parent, _scopeId);
						if (unref(isLeading) || !!__props.avatar || !!slots.leading) {
							_push(`<span data-slot="leading" class="${ssrRenderClass(ui.value.leading({ class: unref(uiProp)?.leading }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => {
								if (unref(isLeading) && unref(leadingIconName)) _push(ssrRenderComponent(_sfc_main$3, {
									name: unref(leadingIconName),
									"data-slot": "leadingIcon",
									class: ui.value.leadingIcon({ class: unref(uiProp)?.leadingIcon })
								}, null, _parent, _scopeId));
								else if (!!__props.avatar) _push(ssrRenderComponent(_sfc_main$4, mergeProps({ size: unref(uiProp)?.leadingAvatarSize || ui.value.leadingAvatarSize() }, __props.avatar, {
									"data-slot": "leadingAvatar",
									class: ui.value.leadingAvatar({ class: unref(uiProp)?.leadingAvatar })
								}), null, _parent, _scopeId));
								else _push(`<!---->`);
							}, _push, _parent, _scopeId);
							_push(`</span>`);
						} else _push(`<!---->`);
						if (unref(isTrailing) || !!slots.trailing) {
							_push(`<span data-slot="trailing" class="${ssrRenderClass(ui.value.trailing({ class: unref(uiProp)?.trailing }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "trailing", { ui: ui.value }, () => {
								if (unref(trailingIconName)) _push(ssrRenderComponent(_sfc_main$3, {
									name: unref(trailingIconName),
									"data-slot": "trailingIcon",
									class: ui.value.trailingIcon({ class: unref(uiProp)?.trailingIcon })
								}, null, _parent, _scopeId));
								else _push(`<!---->`);
							}, _push, _parent, _scopeId);
							_push(`</span>`);
						} else _push(`<!---->`);
					} else return [
						createVNode("input", mergeProps({
							id: unref(id),
							ref_key: "inputRef",
							ref: inputRef,
							type: __props.type,
							value: unref(modelValue),
							name: unref(name),
							placeholder: __props.placeholder,
							"data-slot": "base",
							class: ui.value.base({ class: unref(uiProp)?.base }),
							disabled: unref(disabled),
							required: __props.required,
							autocomplete: __props.autocomplete
						}, {
							..._ctx.$attrs,
							...unref(ariaAttrs)
						}, {
							onInput,
							onBlur,
							onChange,
							onFocus: unref(emitFormFocus)
						}), null, 16, [
							"id",
							"type",
							"value",
							"name",
							"placeholder",
							"disabled",
							"required",
							"autocomplete",
							"onFocus"
						]),
						renderSlot(_ctx.$slots, "default", { ui: ui.value }),
						unref(isLeading) || !!__props.avatar || !!slots.leading ? (openBlock(), createBlock("span", {
							key: 0,
							"data-slot": "leading",
							class: ui.value.leading({ class: unref(uiProp)?.leading })
						}, [renderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => [unref(isLeading) && unref(leadingIconName) ? (openBlock(), createBlock(_sfc_main$3, {
							key: 0,
							name: unref(leadingIconName),
							"data-slot": "leadingIcon",
							class: ui.value.leadingIcon({ class: unref(uiProp)?.leadingIcon })
						}, null, 8, ["name", "class"])) : !!__props.avatar ? (openBlock(), createBlock(_sfc_main$4, mergeProps({
							key: 1,
							size: unref(uiProp)?.leadingAvatarSize || ui.value.leadingAvatarSize()
						}, __props.avatar, {
							"data-slot": "leadingAvatar",
							class: ui.value.leadingAvatar({ class: unref(uiProp)?.leadingAvatar })
						}), null, 16, ["size", "class"])) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true),
						unref(isTrailing) || !!slots.trailing ? (openBlock(), createBlock("span", {
							key: 1,
							"data-slot": "trailing",
							class: ui.value.trailing({ class: unref(uiProp)?.trailing })
						}, [renderSlot(_ctx.$slots, "trailing", { ui: ui.value }, () => [unref(trailingIconName) ? (openBlock(), createBlock(_sfc_main$3, {
							key: 0,
							name: unref(trailingIconName),
							"data-slot": "trailingIcon",
							class: ui.value.trailingIcon({ class: unref(uiProp)?.trailingIcon })
						}, null, 8, ["name", "class"])) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true)
					];
				}),
				_: 3
			}, _parent));
		};
	}
});
var _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Input.vue");
	return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/form-field.ts
var form_field_default = {
	"slots": {
		"root": "",
		"wrapper": "",
		"labelWrapper": "flex content-center items-center justify-between gap-1",
		"label": "block font-medium text-default",
		"container": "relative",
		"description": "text-muted",
		"error": "mt-2 text-error",
		"hint": "text-muted",
		"help": "mt-2 text-muted"
	},
	"variants": {
		"size": {
			"xs": { "root": "text-xs" },
			"sm": { "root": "text-xs" },
			"md": { "root": "text-sm" },
			"lg": { "root": "text-sm" },
			"xl": { "root": "text-base" }
		},
		"required": { "true": { "label": "after:content-['*'] after:ms-0.5 after:text-error" } },
		"orientation": {
			"vertical": { "container": "mt-1" },
			"horizontal": { "root": "flex justify-between place-items-baseline gap-2" }
		}
	},
	"defaultVariants": {
		"size": "md",
		"orientation": "vertical"
	}
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/FormField.vue
var _sfc_main$1 = {
	__name: "FormField",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false
		},
		name: {
			type: String,
			required: false
		},
		errorPattern: {
			type: null,
			required: false
		},
		label: {
			type: String,
			required: false
		},
		description: {
			type: String,
			required: false
		},
		help: {
			type: String,
			required: false
		},
		error: {
			type: [Boolean, String],
			required: false,
			default: void 0
		},
		hint: {
			type: String,
			required: false
		},
		size: {
			type: null,
			required: false
		},
		required: {
			type: Boolean,
			required: false
		},
		eagerValidation: {
			type: Boolean,
			required: false
		},
		validateOnInputDelay: {
			type: Number,
			required: false
		},
		orientation: {
			type: null,
			required: false
		},
		class: {
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
		const slots = useSlots();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("formField", props);
		const ui = computed(() => tv({
			extend: tv(form_field_default),
			...appConfig.ui?.formField || {}
		})({
			size: props.size,
			required: props.required,
			orientation: props.orientation
		}));
		const formErrors = inject(formErrorsInjectionKey, null);
		const error = computed(() => props.error || formErrors?.value?.find((error2) => error2.name === props.name || props.errorPattern && error2.name?.match(props.errorPattern))?.message);
		const id = ref(useId());
		const ariaId = id.value;
		const formInputs = inject(formInputsInjectionKey, void 0);
		watch(id, () => {
			if (formInputs && props.name) formInputs.value[props.name] = {
				id: id.value,
				pattern: props.errorPattern
			};
		}, { immediate: true });
		provide(inputIdInjectionKey, id);
		provide(formFieldInjectionKey, computed(() => ({
			error: error.value,
			name: props.name,
			size: props.size,
			eagerValidation: props.eagerValidation,
			validateOnInputDelay: props.validateOnInputDelay,
			errorPattern: props.errorPattern,
			hint: props.hint,
			description: props.description,
			help: props.help,
			ariaId
		})));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(Primitive), mergeProps({
				as: __props.as,
				"data-orientation": __props.orientation,
				"data-slot": "root",
				class: ui.value.root({ class: [unref(uiProp)?.root, props.class] })
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div data-slot="wrapper" class="${ssrRenderClass(ui.value.wrapper({ class: unref(uiProp)?.wrapper }))}"${_scopeId}>`);
						if (__props.label || !!slots.label) {
							_push(`<div data-slot="labelWrapper" class="${ssrRenderClass(ui.value.labelWrapper({ class: unref(uiProp)?.labelWrapper }))}"${_scopeId}>`);
							_push(ssrRenderComponent(unref(Label), {
								for: id.value,
								"data-slot": "label",
								class: ui.value.label({ class: unref(uiProp)?.label })
							}, {
								default: withCtx((_, _push, _parent, _scopeId) => {
									if (_push) ssrRenderSlot(_ctx.$slots, "label", { label: __props.label }, () => {
										_push(`${ssrInterpolate(__props.label)}`);
									}, _push, _parent, _scopeId);
									else return [renderSlot(_ctx.$slots, "label", { label: __props.label }, () => [createTextVNode(toDisplayString(__props.label), 1)])];
								}),
								_: 3
							}, _parent, _scopeId));
							if (__props.hint || !!slots.hint) {
								_push(`<span${ssrRenderAttr("id", `${unref(ariaId)}-hint`)} data-slot="hint" class="${ssrRenderClass(ui.value.hint({ class: unref(uiProp)?.hint }))}"${_scopeId}>`);
								ssrRenderSlot(_ctx.$slots, "hint", { hint: __props.hint }, () => {
									_push(`${ssrInterpolate(__props.hint)}`);
								}, _push, _parent, _scopeId);
								_push(`</span>`);
							} else _push(`<!---->`);
							_push(`</div>`);
						} else _push(`<!---->`);
						if (__props.description || !!slots.description) {
							_push(`<p${ssrRenderAttr("id", `${unref(ariaId)}-description`)} data-slot="description" class="${ssrRenderClass(ui.value.description({ class: unref(uiProp)?.description }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "description", { description: __props.description }, () => {
								_push(`${ssrInterpolate(__props.description)}`);
							}, _push, _parent, _scopeId);
							_push(`</p>`);
						} else _push(`<!---->`);
						_push(`</div><div class="${ssrRenderClass([(__props.label || !!slots.label || __props.description || !!slots.description) && ui.value.container({ class: unref(uiProp)?.container })])}"${_scopeId}>`);
						ssrRenderSlot(_ctx.$slots, "default", { error: error.value }, null, _push, _parent, _scopeId);
						if (props.error !== false && (typeof error.value === "string" && error.value || !!slots.error)) {
							_push(`<div${ssrRenderAttr("id", `${unref(ariaId)}-error`)} data-slot="error" class="${ssrRenderClass(ui.value.error({ class: unref(uiProp)?.error }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "error", { error: error.value }, () => {
								_push(`${ssrInterpolate(error.value)}`);
							}, _push, _parent, _scopeId);
							_push(`</div>`);
						} else if (__props.help || !!slots.help) {
							_push(`<div${ssrRenderAttr("id", `${unref(ariaId)}-help`)} data-slot="help" class="${ssrRenderClass(ui.value.help({ class: unref(uiProp)?.help }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "help", { help: __props.help }, () => {
								_push(`${ssrInterpolate(__props.help)}`);
							}, _push, _parent, _scopeId);
							_push(`</div>`);
						} else _push(`<!---->`);
						_push(`</div>`);
					} else return [createVNode("div", {
						"data-slot": "wrapper",
						class: ui.value.wrapper({ class: unref(uiProp)?.wrapper })
					}, [__props.label || !!slots.label ? (openBlock(), createBlock("div", {
						key: 0,
						"data-slot": "labelWrapper",
						class: ui.value.labelWrapper({ class: unref(uiProp)?.labelWrapper })
					}, [createVNode(unref(Label), {
						for: id.value,
						"data-slot": "label",
						class: ui.value.label({ class: unref(uiProp)?.label })
					}, {
						default: withCtx(() => [renderSlot(_ctx.$slots, "label", { label: __props.label }, () => [createTextVNode(toDisplayString(__props.label), 1)])]),
						_: 3
					}, 8, ["for", "class"]), __props.hint || !!slots.hint ? (openBlock(), createBlock("span", {
						key: 0,
						id: `${unref(ariaId)}-hint`,
						"data-slot": "hint",
						class: ui.value.hint({ class: unref(uiProp)?.hint })
					}, [renderSlot(_ctx.$slots, "hint", { hint: __props.hint }, () => [createTextVNode(toDisplayString(__props.hint), 1)])], 10, ["id"])) : createCommentVNode("", true)], 2)) : createCommentVNode("", true), __props.description || !!slots.description ? (openBlock(), createBlock("p", {
						key: 1,
						id: `${unref(ariaId)}-description`,
						"data-slot": "description",
						class: ui.value.description({ class: unref(uiProp)?.description })
					}, [renderSlot(_ctx.$slots, "description", { description: __props.description }, () => [createTextVNode(toDisplayString(__props.description), 1)])], 10, ["id"])) : createCommentVNode("", true)], 2), createVNode("div", { class: [(__props.label || !!slots.label || __props.description || !!slots.description) && ui.value.container({ class: unref(uiProp)?.container })] }, [renderSlot(_ctx.$slots, "default", { error: error.value }), props.error !== false && (typeof error.value === "string" && error.value || !!slots.error) ? (openBlock(), createBlock("div", {
						key: 0,
						id: `${unref(ariaId)}-error`,
						"data-slot": "error",
						class: ui.value.error({ class: unref(uiProp)?.error })
					}, [renderSlot(_ctx.$slots, "error", { error: error.value }, () => [createTextVNode(toDisplayString(error.value), 1)])], 10, ["id"])) : __props.help || !!slots.help ? (openBlock(), createBlock("div", {
						key: 1,
						id: `${unref(ariaId)}-help`,
						"data-slot": "help",
						class: ui.value.help({ class: unref(uiProp)?.help })
					}, [renderSlot(_ctx.$slots, "help", { help: __props.help }, () => [createTextVNode(toDisplayString(__props.help), 1)])], 10, ["id"])) : createCommentVNode("", true)], 2)];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/FormField.vue");
	return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/utils/form.js
function isSuperStructSchema(schema) {
	return "schema" in schema && typeof schema.coercer === "function" && typeof schema.validator === "function" && typeof schema.refiner === "function";
}
function isStandardSchema(schema) {
	return "~standard" in schema;
}
async function validateStandardSchema(state, schema) {
	const result = await schema["~standard"].validate(state);
	if (result.issues) return {
		errors: result.issues?.map((issue) => ({
			name: issue.path?.map((item) => typeof item === "object" ? item.key : item).join(".") || "",
			message: issue.message
		})) || [],
		result: null
	};
	return {
		errors: null,
		result: result.value
	};
}
async function validateSuperstructSchema(state, schema) {
	const [err, result] = schema.validate(state);
	if (err) return {
		errors: err.failures().map((error) => ({
			message: error.message,
			name: error.path.join(".")
		})),
		result: null
	};
	return {
		errors: null,
		result
	};
}
function validateSchema(state, schema) {
	if (isStandardSchema(schema)) return validateStandardSchema(state, schema);
	else if (isSuperStructSchema(schema)) return validateSuperstructSchema(state, schema);
	else throw new Error("Form validation failed: Unsupported form schema");
}
function getAtPath(data, path) {
	if (!path) return data;
	return path.split(".").reduce((value2, key) => value2?.[key], data);
}
function setAtPath(data, path, value) {
	if (!path) return Object.assign(data, value);
	if (!data) return data;
	const keys = path.split(".");
	let current = data;
	for (let i = 0; i < keys.length - 1; i++) {
		const key = keys[i];
		if (current[key] === void 0 || current[key] === null) if (i + 1 < keys.length && !Number.isNaN(Number(keys[i + 1]))) current[key] = [];
		else current[key] = {};
		current = current[key];
	}
	const lastKey = keys[keys.length - 1];
	current[lastKey] = value;
	return data;
}
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/types/form.js
var FormValidationException = class FormValidationException extends Error {
	formId;
	errors;
	constructor(formId, errors) {
		super("Form validation exception");
		this.formId = formId;
		this.errors = errors;
		Object.setPrototypeOf(this, FormValidationException.prototype);
	}
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/form.ts
var form_default = { "base": "" };
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Form.vue
var _sfc_main = {
	__name: "Form",
	__ssrInlineRender: true,
	props: {
		id: {
			type: [String, Number],
			required: false
		},
		schema: {
			type: null,
			required: false
		},
		state: {
			type: null,
			required: false
		},
		validate: {
			type: Function,
			required: false
		},
		validateOn: {
			type: Array,
			required: false,
			default() {
				return [
					"input",
					"blur",
					"change"
				];
			}
		},
		disabled: {
			type: Boolean,
			required: false
		},
		name: {
			type: null,
			required: false
		},
		validateOnInputDelay: {
			type: Number,
			required: false,
			default: 300
		},
		transform: {
			type: null,
			required: false,
			default: () => true
		},
		nested: {
			type: Boolean,
			required: false
		},
		loadingAuto: {
			type: Boolean,
			required: false,
			default: true
		},
		class: {
			type: null,
			required: false
		},
		ui: {
			type: Object,
			required: false
		},
		onSubmit: {
			type: Function,
			required: false
		}
	},
	emits: ["submit", "error"],
	setup(__props, { expose: __expose, emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("form", props);
		const ui = computed(() => tv({
			extend: tv(form_default),
			...appConfig.ui?.form || {}
		}));
		const formId = props.id ?? useId();
		const formRef = useTemplateRef("formRef");
		const bus = useEventBus(`form-${formId}`);
		const parentBus = props.nested === true && inject(formBusInjectionKey, void 0);
		const parentState = props.nested === true ? inject(formStateInjectionKey, void 0) : void 0;
		const state = computed(() => {
			if (parentState?.value) return props.name ? getAtPath(parentState.value, props.name) : parentState.value;
			return props.state;
		});
		provide(formBusInjectionKey, bus);
		provide(formStateInjectionKey, state);
		const nestedForms = ref(/* @__PURE__ */ new Map());
		onMounted(async () => {
			if (parentBus) {
				await nextTick();
				parentBus.emit({
					type: "attach",
					validate: _validate,
					formId,
					name: props.name,
					api
				});
			}
		});
		onUnmounted(() => {
			bus.reset();
			if (parentBus) parentBus.emit({
				type: "detach",
				formId
			});
		});
		onMounted(async () => {
			bus.on(async (event) => {
				if (event.type === "attach") nestedForms.value.set(event.formId, {
					validate: event.validate,
					name: event.name,
					api: event.api
				});
				else if (event.type === "detach") nestedForms.value.delete(event.formId);
				else if (props.validateOn?.includes(event.type) && !loading.value) {
					if (event.type !== "input") await _validate({
						name: event.name,
						silent: true,
						nested: false
					});
					else if (event.eager || blurredFields.has(event.name)) await _validate({
						name: event.name,
						silent: true,
						nested: false
					});
				}
				if (event.type === "blur") blurredFields.add(event.name);
				if (event.type === "change" || event.type === "input" || event.type === "blur" || event.type === "focus") touchedFields.add(event.name);
				if (event.type === "change" || event.type === "input") dirtyFields.add(event.name);
			});
		});
		const errors = ref([]);
		provide(formErrorsInjectionKey, errors);
		const inputs = ref({});
		provide(formInputsInjectionKey, inputs);
		const dirtyFields = reactive(/* @__PURE__ */ new Set());
		const touchedFields = reactive(/* @__PURE__ */ new Set());
		const blurredFields = reactive(/* @__PURE__ */ new Set());
		function resolveErrorIds(errs) {
			return errs.map((err) => ({
				...err,
				id: err?.name ? inputs.value[err.name]?.id : void 0
			}));
		}
		const transformedState = ref(null);
		async function getErrors() {
			let errs = props.validate ? await props.validate(state.value) ?? [] : [];
			if (props.schema) {
				const { errors: errors2, result } = await validateSchema(state.value, props.schema);
				if (errors2) errs = errs.concat(errors2);
				else transformedState.value = result;
			}
			return resolveErrorIds(errs);
		}
		async function _validate(opts = {
			silent: false,
			nested: false,
			transform: false
		}) {
			const names = opts.name && !Array.isArray(opts.name) ? [opts.name] : opts.name;
			let nestedResults = [];
			let nestedErrors = [];
			if (!names && opts.nested) {
				const validations = Array.from(nestedForms.value.values()).map((form) => validateNestedForm(form, opts));
				const results = await Promise.all(validations);
				nestedErrors = results.filter((r) => r.error).flatMap((r) => r.error.errors.map((e) => addFormPath(e, r.name)));
				nestedResults = results.filter((r) => r.output !== void 0);
			}
			const allErrors = [...await getErrors(), ...nestedErrors];
			if (names) errors.value = filterErrorsByNames(allErrors, names);
			else errors.value = allErrors;
			if (errors.value?.length) {
				if (opts.silent) return false;
				throw new FormValidationException(formId, errors.value);
			}
			if (opts.transform) {
				nestedResults.forEach((result) => {
					if (result.name) setAtPath(transformedState.value, result.name, result.output);
					else Object.assign(transformedState.value, result.output);
				});
				return transformedState.value ?? state.value;
			}
			return state.value;
		}
		const loading = ref(false);
		provide(formLoadingInjectionKey, readonly(loading));
		async function onSubmitWrapper(payload) {
			loading.value = props.loadingAuto && true;
			const event = payload;
			try {
				event.data = await _validate({
					nested: true,
					transform: props.transform
				});
				await props.onSubmit?.(event);
				dirtyFields.clear();
			} catch (error) {
				if (!(error instanceof FormValidationException)) throw error;
				emits("error", {
					...event,
					errors: error.errors
				});
			} finally {
				loading.value = false;
			}
		}
		const disabled = computed(() => props.disabled || loading.value);
		provide(formOptionsInjectionKey, computed(() => ({
			disabled: disabled.value,
			validateOnInputDelay: props.validateOnInputDelay
		})));
		async function validateNestedForm(form, opts) {
			try {
				const result = await form.validate({
					...opts,
					silent: false
				});
				return {
					name: form.name,
					output: result
				};
			} catch (error) {
				if (!(error instanceof FormValidationException)) throw error;
				return {
					name: form.name,
					error
				};
			}
		}
		function addFormPath(error, formPath) {
			if (!formPath || !error.name) return error;
			return {
				...error,
				name: formPath + "." + error.name
			};
		}
		function stripFormPath(error, formPath) {
			const prefix = formPath + ".";
			const name = error?.name?.startsWith(prefix) ? error.name.substring(prefix.length) : error.name;
			return {
				...error,
				name
			};
		}
		function filterFormErrors(errors2, formPath) {
			if (!formPath) return errors2;
			return errors2.filter((e) => e?.name?.startsWith(formPath + ".")).map((e) => stripFormPath(e, formPath));
		}
		function getFormErrors(form) {
			return form.api.getErrors().map((e) => form.name ? {
				...e,
				name: form.name + "." + e.name
			} : e);
		}
		function matchesTarget(target, path) {
			if (!target || !path) return true;
			if (target instanceof RegExp) return target.test(path);
			return path === target || typeof target === "string" && target.startsWith(path + ".");
		}
		function getNestedTarget(target, formPath) {
			if (!target || target instanceof RegExp) return target;
			if (formPath === target) return void 0;
			if (typeof target === "string" && target.startsWith(formPath + ".")) return target.substring(formPath.length + 1);
			return target;
		}
		function filterErrorsByNames(allErrors, names) {
			const nameSet = new Set(names);
			const patterns = names.map((name) => inputs.value?.[name]?.pattern).filter(Boolean);
			const matchesNames = (error) => {
				if (!error.name) return false;
				if (nameSet.has(error.name)) return true;
				return patterns.some((pattern) => pattern.test(error.name));
			};
			const keepErrors = errors.value.filter((error) => !matchesNames(error));
			const newErrors = allErrors.filter(matchesNames);
			return [...keepErrors, ...newErrors];
		}
		function filterErrorsByTarget(currentErrors, target) {
			return currentErrors.filter((err) => target instanceof RegExp ? !(err.name && target.test(err.name)) : !err.name || err.name !== target);
		}
		function isLocalError(error) {
			return !error.name || !!inputs.value[error.name];
		}
		const api = {
			validate: _validate,
			errors,
			setErrors(errs, name) {
				const localErrors = resolveErrorIds(errs.filter(isLocalError));
				const nestedErrors = [];
				for (const form of nestedForms.value.values()) if (matchesTarget(name, form.name)) {
					const formErrors = filterFormErrors(errs, form.name);
					form.api.setErrors(formErrors, getNestedTarget(name, form.name || ""));
					nestedErrors.push(...getFormErrors(form));
				}
				if (name) errors.value = [
					...filterErrorsByTarget(errors.value, name),
					...localErrors,
					...nestedErrors
				];
				else errors.value = [...localErrors, ...nestedErrors];
			},
			async submit() {
				if (formRef.value instanceof HTMLFormElement && formRef.value.reportValidity() === false) return;
				await onSubmitWrapper(new Event("submit"));
			},
			getErrors(name) {
				if (!name) return errors.value;
				return errors.value.filter((err) => name instanceof RegExp ? err.name && name.test(err.name) : err.name === name);
			},
			clear(name) {
				const localErrors = name ? errors.value.filter((err) => isLocalError(err) && (name instanceof RegExp ? !(err.name && name.test(err.name)) : err.name !== name)) : [];
				const nestedErrors = [];
				for (const form of nestedForms.value.values()) {
					if (matchesTarget(name, form.name)) form.api.clear();
					nestedErrors.push(...getFormErrors(form));
				}
				errors.value = [...localErrors, ...nestedErrors];
			},
			disabled,
			loading,
			dirty: computed(() => !!dirtyFields.size),
			dirtyFields: readonly(dirtyFields),
			blurredFields: readonly(blurredFields),
			touchedFields: readonly(touchedFields)
		};
		__expose(api);
		return (_ctx, _push, _parent, _attrs) => {
			ssrRenderVNode(_push, createVNode(resolveDynamicComponent(unref(parentBus) ? "div" : "form"), mergeProps({
				id: unref(formId),
				ref_key: "formRef",
				ref: formRef,
				class: ui.value({ class: [unref(uiProp)?.base, props.class] }),
				onSubmit: onSubmitWrapper
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) ssrRenderSlot(_ctx.$slots, "default", {
						errors: errors.value,
						loading: loading.value
					}, null, _push, _parent, _scopeId);
					else return [renderSlot(_ctx.$slots, "default", {
						errors: errors.value,
						loading: loading.value
					})];
				}),
				_: 3
			}), _parent);
		};
	}
};
var _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Form.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
//#endregion
export { _sfc_main$1 as n, _sfc_main$2 as r, _sfc_main as t };

//# sourceMappingURL=Form-DAQH5xxu.js.map