import { A as getDisplayValue, C as useComponentIcons, F as useAppConfig, M as looseToNumber, S as useFieldGroup, b as FieldGroupReset, c as _sfc_main$2, j as isArrayOfArray, k as get, n as usePortal, o as _sfc_main$3, s as _sfc_main$4, u as tv, w as useComponentUI, y as useFormField } from "./usePortal-DZb6nPjI.js";
import { ssrInterpolate, ssrRenderClass, ssrRenderComponent, ssrRenderList, ssrRenderSlot, ssrRenderVNode } from "vue/server-renderer";
import { Fragment, computed, createBlock, createCommentVNode, createTextVNode, createVNode, mergeProps, onMounted, openBlock, ref, renderList, renderSlot, resolveDynamicComponent, toDisplayString, toRef, toValue, unref, useSSRContext, useSlots, useTemplateRef, withCtx } from "vue";
import { defu } from "defu";
import { PinInputInput, PinInputRoot, SelectArrow, SelectContent, SelectGroup, SelectItem, SelectItemIndicator, SelectItemText, SelectLabel, SelectPortal, SelectRoot, SelectSeparator, SelectTrigger, SelectValue, SelectViewport, useForwardPropsEmits } from "reka-ui";
import { reactivePick } from "@vueuse/core";
//#region virtual:nuxt-ui-templates/ui/pin-input.ts
var pin_input_default = {
	"slots": {
		"root": "relative inline-flex items-center gap-1.5",
		"base": ["rounded-md border-0 placeholder:text-dimmed text-center focus:outline-none disabled:cursor-not-allowed disabled:opacity-75", "transition-colors"]
	},
	"variants": {
		"size": {
			"xs": { "base": "size-6 text-sm/4" },
			"sm": { "base": "size-7 text-sm/4" },
			"md": { "base": "size-8 text-base/5" },
			"lg": { "base": "size-9 text-base/5" },
			"xl": { "base": "size-10 text-base" }
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
		"highlight": { "true": "" },
		"fixed": { "false": "" }
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
//#region node_modules/@nuxt/ui/dist/runtime/components/PinInput.vue
var _sfc_main$1 = {
	__name: "PinInput",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
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
		length: {
			type: [Number, String],
			required: false,
			default: 5
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
		highlight: {
			type: Boolean,
			required: false
		},
		fixed: {
			type: Boolean,
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
		defaultValue: {
			type: null,
			required: false
		},
		disabled: {
			type: Boolean,
			required: false
		},
		id: {
			type: String,
			required: false
		},
		mask: {
			type: Boolean,
			required: false
		},
		modelValue: {
			type: null,
			required: false
		},
		name: {
			type: String,
			required: false
		},
		otp: {
			type: Boolean,
			required: false
		},
		placeholder: {
			type: String,
			required: false
		},
		required: {
			type: Boolean,
			required: false
		},
		type: {
			type: null,
			required: false,
			default: "text"
		}
	},
	emits: [
		"update:modelValue",
		"complete",
		"change",
		"blur"
	],
	setup(__props, { expose: __expose, emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("pinInput", props);
		const rootProps = useForwardPropsEmits(reactivePick(props, "disabled", "id", "mask", "name", "otp", "required", "type"), emits);
		const { emitFormInput, emitFormFocus, emitFormChange, emitFormBlur, size, color, id, name, highlight, disabled, ariaAttrs } = useFormField(props);
		const ui = computed(() => tv({
			extend: tv(pin_input_default),
			...appConfig.ui?.pinInput || {}
		})({
			color: color.value,
			variant: props.variant,
			size: size.value,
			highlight: highlight.value,
			fixed: props.fixed
		}));
		const inputsRef = ref([]);
		function setInputRef(index, el) {
			inputsRef.value[index] = el;
		}
		const completed = ref(false);
		function onComplete(value) {
			emits("change", new Event("change", { target: { value } }));
			emitFormChange();
		}
		function onBlur(event) {
			if (!event.relatedTarget || completed.value) {
				emits("blur", event);
				emitFormBlur();
			}
		}
		function autoFocus() {
			if (props.autofocus) inputsRef.value[0]?.$el?.focus();
		}
		onMounted(() => {
			setTimeout(() => {
				autoFocus();
			}, props.autofocusDelay);
		});
		__expose({ inputsRef });
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(PinInputRoot), mergeProps({
				...unref(rootProps),
				...unref(ariaAttrs)
			}, {
				id: unref(id),
				name: unref(name),
				placeholder: __props.placeholder,
				"model-value": __props.modelValue,
				"default-value": __props.defaultValue,
				"data-slot": "root",
				class: ui.value.root({ class: [unref(uiProp)?.root, props.class] }),
				"onUpdate:modelValue": ($event) => unref(emitFormInput)(),
				onComplete
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<!--[-->`);
						ssrRenderList(unref(looseToNumber)(props.length), (ids, index) => {
							_push(ssrRenderComponent(unref(PinInputInput), {
								key: ids,
								ref_for: true,
								ref: (el) => setInputRef(index, el),
								index,
								"data-slot": "base",
								class: ui.value.base({ class: unref(uiProp)?.base }),
								disabled: unref(disabled),
								onBlur,
								onFocus: unref(emitFormFocus)
							}, null, _parent, _scopeId));
						});
						_push(`<!--]-->`);
					} else return [(openBlock(true), createBlock(Fragment, null, renderList(unref(looseToNumber)(props.length), (ids, index) => {
						return openBlock(), createBlock(unref(PinInputInput), {
							key: ids,
							ref_for: true,
							ref: (el) => setInputRef(index, el),
							index,
							"data-slot": "base",
							class: ui.value.base({ class: unref(uiProp)?.base }),
							disabled: unref(disabled),
							onBlur,
							onFocus: unref(emitFormFocus)
						}, null, 8, [
							"index",
							"class",
							"disabled",
							"onFocus"
						]);
					}), 128))];
				}),
				_: 1
			}, _parent));
		};
	}
};
var _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/PinInput.vue");
	return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/composables/useResolvedVariants.js
function useResolvedVariants(name, props, theme, keys, overrides) {
	const appConfig = useAppConfig();
	const result = {};
	for (const key of keys) result[key] = computed(() => {
		return (overrides?.[key] !== void 0 ? toValue(overrides[key]) : get(props, key)) ?? appConfig.ui?.[name]?.defaultVariants?.[key] ?? theme.defaultVariants?.[key];
	});
	return result;
}
//#endregion
//#region virtual:nuxt-ui-templates/ui/select.ts
var select_default = {
	"slots": {
		"base": ["relative group rounded-md inline-flex items-center focus:outline-none disabled:cursor-not-allowed disabled:opacity-75", "transition-colors"],
		"leading": "absolute inset-y-0 start-0 flex items-center",
		"leadingIcon": "shrink-0 text-dimmed",
		"leadingAvatar": "shrink-0",
		"leadingAvatarSize": "",
		"trailing": "absolute inset-y-0 end-0 flex items-center",
		"trailingIcon": "shrink-0 text-dimmed",
		"value": "truncate pointer-events-none",
		"placeholder": "truncate text-dimmed",
		"arrow": "fill-bg stroke-default",
		"content": "max-h-60 w-(--reka-select-trigger-width) bg-default shadow-lg rounded-md ring ring-default overflow-hidden origin-(--reka-select-content-transform-origin) pointer-events-auto flex flex-col",
		"viewport": "relative divide-y divide-default scroll-py-1 overflow-y-auto flex-1",
		"group": "p-1 isolate",
		"empty": "text-center text-muted",
		"label": "font-semibold text-highlighted",
		"separator": "-mx-1 my-1 h-px bg-border",
		"item": ["group relative w-full flex items-start select-none outline-none before:absolute before:z-[-1] before:inset-px before:rounded-md data-disabled:cursor-not-allowed data-disabled:opacity-75 text-default data-highlighted:not-data-disabled:text-highlighted data-highlighted:not-data-disabled:before:bg-elevated/50", "transition-colors before:transition-colors"],
		"itemLeadingIcon": ["shrink-0 text-dimmed group-data-highlighted:not-group-data-disabled:text-default", "transition-colors"],
		"itemLeadingAvatar": "shrink-0",
		"itemLeadingAvatarSize": "",
		"itemLeadingChip": "shrink-0",
		"itemLeadingChipSize": "",
		"itemTrailing": "ms-auto inline-flex gap-1.5 items-center",
		"itemTrailingIcon": "shrink-0",
		"itemWrapper": "flex-1 flex flex-col min-w-0",
		"itemLabel": "truncate",
		"itemDescription": "truncate text-muted"
	},
	"variants": {
		"fieldGroup": {
			"horizontal": "not-only:first:rounded-e-none not-only:last:rounded-s-none not-last:not-first:rounded-none focus-visible:z-[1]",
			"vertical": "not-only:first:rounded-b-none not-only:last:rounded-t-none not-last:not-first:rounded-none focus-visible:z-[1]"
		},
		"size": {
			"xs": {
				"base": "px-2 py-1 text-xs gap-1",
				"leading": "ps-2",
				"trailing": "pe-2",
				"leadingIcon": "size-4",
				"leadingAvatarSize": "3xs",
				"trailingIcon": "size-4",
				"label": "p-1 text-[10px]/3 gap-1",
				"item": "p-1 text-xs gap-1",
				"itemLeadingIcon": "size-4",
				"itemLeadingAvatarSize": "3xs",
				"itemLeadingChip": "size-4",
				"itemLeadingChipSize": "sm",
				"itemTrailingIcon": "size-4",
				"empty": "p-2 text-xs"
			},
			"sm": {
				"base": "px-2.5 py-1.5 text-xs gap-1.5",
				"leading": "ps-2.5",
				"trailing": "pe-2.5",
				"leadingIcon": "size-4",
				"leadingAvatarSize": "3xs",
				"trailingIcon": "size-4",
				"label": "p-1.5 text-[10px]/3 gap-1.5",
				"item": "p-1.5 text-xs gap-1.5",
				"itemLeadingIcon": "size-4",
				"itemLeadingAvatarSize": "3xs",
				"itemLeadingChip": "size-4",
				"itemLeadingChipSize": "sm",
				"itemTrailingIcon": "size-4",
				"empty": "p-2.5 text-xs"
			},
			"md": {
				"base": "px-2.5 py-1.5 text-sm gap-1.5",
				"leading": "ps-2.5",
				"trailing": "pe-2.5",
				"leadingIcon": "size-5",
				"leadingAvatarSize": "2xs",
				"trailingIcon": "size-5",
				"label": "p-1.5 text-xs gap-1.5",
				"item": "p-1.5 text-sm gap-1.5",
				"itemLeadingIcon": "size-5",
				"itemLeadingAvatarSize": "2xs",
				"itemLeadingChip": "size-5",
				"itemLeadingChipSize": "md",
				"itemTrailingIcon": "size-5",
				"empty": "p-2.5 text-sm"
			},
			"lg": {
				"base": "px-3 py-2 text-sm gap-2",
				"leading": "ps-3",
				"trailing": "pe-3",
				"leadingIcon": "size-5",
				"leadingAvatarSize": "2xs",
				"trailingIcon": "size-5",
				"label": "p-2 text-xs gap-2",
				"item": "p-2 text-sm gap-2",
				"itemLeadingIcon": "size-5",
				"itemLeadingAvatarSize": "2xs",
				"itemLeadingChip": "size-5",
				"itemLeadingChipSize": "md",
				"itemTrailingIcon": "size-5",
				"empty": "p-3 text-sm"
			},
			"xl": {
				"base": "px-3 py-2 text-base gap-2",
				"leading": "ps-3",
				"trailing": "pe-3",
				"leadingIcon": "size-6",
				"leadingAvatarSize": "xs",
				"trailingIcon": "size-6",
				"label": "p-2 text-sm gap-2",
				"item": "p-2 text-base gap-2",
				"itemLeadingIcon": "size-6",
				"itemLeadingAvatarSize": "xs",
				"itemLeadingChip": "size-6",
				"itemLeadingChipSize": "lg",
				"itemTrailingIcon": "size-6",
				"empty": "p-3 text-base"
			}
		},
		"variant": {
			"outline": "text-highlighted bg-default ring ring-inset ring-accented hover:bg-elevated disabled:bg-default",
			"soft": "text-highlighted bg-elevated/50 hover:bg-elevated focus:bg-elevated disabled:bg-elevated/50",
			"subtle": "text-highlighted bg-elevated ring ring-inset ring-accented hover:bg-accented/75 disabled:bg-elevated",
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
		"type": { "file": "file:me-1.5 file:font-medium file:text-muted file:outline-none" },
		"position": {
			"popper": { "content": "data-[state=open]:animate-[scale-in_100ms_ease-out] data-[state=closed]:animate-[scale-out_100ms_ease-in]" },
			"item-aligned": { "content": "" }
		}
	},
	"compoundVariants": [
		{
			"color": "primary",
			"variant": ["outline", "subtle"],
			"class": "focus:ring-2 focus:ring-inset focus:ring-primary"
		},
		{
			"color": "secondary",
			"variant": ["outline", "subtle"],
			"class": "focus:ring-2 focus:ring-inset focus:ring-secondary"
		},
		{
			"color": "success",
			"variant": ["outline", "subtle"],
			"class": "focus:ring-2 focus:ring-inset focus:ring-success"
		},
		{
			"color": "info",
			"variant": ["outline", "subtle"],
			"class": "focus:ring-2 focus:ring-inset focus:ring-info"
		},
		{
			"color": "warning",
			"variant": ["outline", "subtle"],
			"class": "focus:ring-2 focus:ring-inset focus:ring-warning"
		},
		{
			"color": "error",
			"variant": ["outline", "subtle"],
			"class": "focus:ring-2 focus:ring-inset focus:ring-error"
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
			"class": "focus:ring-2 focus:ring-inset focus:ring-inverted"
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
		"variant": "outline",
		"position": "popper"
	}
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Select.vue
var _sfc_main = /* @__PURE__ */ Object.assign({ inheritAttrs: false }, {
	__name: "Select",
	__ssrInlineRender: true,
	props: {
		id: {
			type: String,
			required: false
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
		trailingIcon: {
			type: null,
			required: false
		},
		selectedIcon: {
			type: null,
			required: false
		},
		content: {
			type: Object,
			required: false
		},
		arrow: {
			type: [Boolean, Object],
			required: false
		},
		portal: {
			type: [Boolean, String],
			required: false,
			skipCheck: true,
			default: true
		},
		valueKey: {
			type: null,
			required: false,
			default: "value"
		},
		labelKey: {
			type: null,
			required: false,
			default: "label"
		},
		descriptionKey: {
			type: null,
			required: false,
			default: "description"
		},
		items: {
			type: null,
			required: false
		},
		defaultValue: {
			type: null,
			required: false
		},
		modelValue: {
			type: null,
			required: false
		},
		modelModifiers: {
			type: null,
			required: false
		},
		multiple: {
			type: Boolean,
			required: false
		},
		highlight: {
			type: Boolean,
			required: false
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
		class: {
			type: null,
			required: false
		},
		ui: {
			type: Object,
			required: false
		},
		open: {
			type: Boolean,
			required: false
		},
		defaultOpen: {
			type: Boolean,
			required: false
		},
		autocomplete: {
			type: String,
			required: false
		},
		disabled: {
			type: Boolean,
			required: false
		},
		name: {
			type: String,
			required: false
		},
		required: {
			type: Boolean,
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
		"change",
		"blur",
		"focus",
		"update:modelValue",
		"update:open"
	],
	setup(__props, { expose: __expose, emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const slots = useSlots();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("select", props);
		const rootProps = useForwardPropsEmits(reactivePick(props, "open", "defaultOpen", "disabled", "autocomplete", "required", "multiple"), emits);
		const portalProps = usePortal(toRef(() => props.portal));
		const { position } = useResolvedVariants("select", props, select_default, ["position"], { position: () => props.content?.position });
		const contentProps = toRef(() => defu(props.content, {
			side: "bottom",
			sideOffset: 8,
			collisionPadding: 8,
			position: position.value
		}));
		const arrowProps = toRef(() => defu(props.arrow, { rounded: true }));
		const { emitFormChange, emitFormInput, emitFormBlur, emitFormFocus, size: formFieldSize, color, id, name, highlight, disabled, ariaAttrs } = useFormField(props);
		const { orientation, size: fieldGroupSize } = useFieldGroup(props);
		const { isLeading, isTrailing, leadingIconName, trailingIconName } = useComponentIcons(toRef(() => defu(props, { trailingIcon: appConfig.ui.icons.chevronDown })));
		const selectSize = computed(() => fieldGroupSize.value || formFieldSize.value);
		const isItemAligned = computed(() => position.value === "item-aligned");
		const ui = computed(() => tv({
			extend: tv(select_default),
			...appConfig.ui?.select || {}
		})({
			color: color.value,
			variant: props.variant,
			size: selectSize?.value,
			loading: props.loading,
			highlight: highlight.value,
			leading: isLeading.value || !!props.avatar || !!slots.leading,
			trailing: isTrailing.value || !!slots.trailing,
			fieldGroup: orientation.value,
			position: position.value
		}));
		const groups = computed(() => props.items?.length ? isArrayOfArray(props.items) ? props.items : [props.items] : []);
		const items = computed(() => groups.value.flatMap((group) => group));
		function displayValue(value) {
			if (props.multiple && Array.isArray(value)) {
				const displayedValues = value.map((item) => getDisplayValue(items.value, item, {
					labelKey: props.labelKey,
					valueKey: props.valueKey
				})).filter((v) => v != null && v !== "");
				return displayedValues.length > 0 ? displayedValues.join(", ") : void 0;
			}
			return getDisplayValue(items.value, value, {
				labelKey: props.labelKey,
				valueKey: props.valueKey
			});
		}
		const triggerRef = useTemplateRef("triggerRef");
		function autoFocus() {
			if (props.autofocus) triggerRef.value?.$el?.focus({ focusVisible: true });
		}
		onMounted(() => {
			setTimeout(() => {
				autoFocus();
			}, props.autofocusDelay);
		});
		function onUpdate(value) {
			if (props.modelModifiers?.trim && (typeof value === "string" || value === null || value === void 0)) value = value?.trim() ?? null;
			if (props.modelModifiers?.number) value = looseToNumber(value);
			if (props.modelModifiers?.nullable) value ??= null;
			if (props.modelModifiers?.optional && !props.modelModifiers?.nullable && value !== null) value ??= void 0;
			emits("change", new Event("change", { target: { value } }));
			emitFormChange();
			emitFormInput();
		}
		function onUpdateOpen(value) {
			if (!value) {
				emits("blur", new FocusEvent("blur"));
				emitFormBlur();
			} else {
				emits("focus", new FocusEvent("focus"));
				emitFormFocus();
			}
		}
		function isSelectItem(item) {
			return typeof item === "object" && item !== null;
		}
		const viewportRef = useTemplateRef("viewportRef");
		__expose({
			triggerRef: toRef(() => triggerRef.value?.$el),
			viewportRef: toRef(() => {
				const instance = viewportRef.value;
				return instance && typeof instance === "object" && "$el" in instance ? instance.$el : instance;
			})
		});
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(SelectRoot), mergeProps({ name: unref(name) }, unref(rootProps), {
				autocomplete: __props.autocomplete,
				disabled: unref(disabled),
				"default-value": __props.defaultValue,
				"model-value": __props.modelValue,
				"onUpdate:modelValue": onUpdate,
				"onUpdate:open": onUpdateOpen
			}, _attrs), {
				default: withCtx(({ modelValue, open }, _push, _parent, _scopeId) => {
					if (_push) {
						_push(ssrRenderComponent(unref(SelectTrigger), mergeProps({
							id: unref(id),
							ref_key: "triggerRef",
							ref: triggerRef,
							"data-slot": "base",
							class: ui.value.base({ class: [unref(uiProp)?.base, props.class] })
						}, {
							..._ctx.$attrs,
							...unref(ariaAttrs)
						}), {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) {
									if (unref(isLeading) || !!__props.avatar || !!slots.leading) {
										_push(`<span data-slot="leading" class="${ssrRenderClass(ui.value.leading({ class: unref(uiProp)?.leading }))}"${_scopeId}>`);
										ssrRenderSlot(_ctx.$slots, "leading", {
											modelValue,
											open,
											ui: ui.value
										}, () => {
											if (unref(isLeading) && unref(leadingIconName)) _push(ssrRenderComponent(_sfc_main$2, {
												name: unref(leadingIconName),
												"data-slot": "leadingIcon",
												class: ui.value.leadingIcon({ class: unref(uiProp)?.leadingIcon })
											}, null, _parent, _scopeId));
											else if (!!__props.avatar) _push(ssrRenderComponent(_sfc_main$3, mergeProps({ size: unref(uiProp)?.itemLeadingAvatarSize || ui.value.itemLeadingAvatarSize() }, __props.avatar, {
												"data-slot": "itemLeadingAvatar",
												class: ui.value.itemLeadingAvatar({ class: unref(uiProp)?.itemLeadingAvatar })
											}), null, _parent, _scopeId));
											else _push(`<!---->`);
										}, _push, _parent, _scopeId);
										_push(`</span>`);
									} else _push(`<!---->`);
									_push(`<!--[-->`);
									ssrRenderList([displayValue(modelValue)], (displayedModelValue) => {
										_push(ssrRenderComponent(unref(SelectValue), {
											"data-slot": displayedModelValue != null ? "value" : "placeholder",
											class: displayedModelValue != null ? ui.value.value({ class: unref(uiProp)?.value }) : ui.value.placeholder({ class: unref(uiProp)?.placeholder })
										}, {
											default: withCtx((_, _push, _parent, _scopeId) => {
												if (_push) ssrRenderSlot(_ctx.$slots, "default", {
													modelValue,
													open,
													ui: ui.value
												}, () => {
													_push(`${ssrInterpolate(displayedModelValue ?? __props.placeholder ?? "\xA0")}`);
												}, _push, _parent, _scopeId);
												else return [renderSlot(_ctx.$slots, "default", {
													modelValue,
													open,
													ui: ui.value
												}, () => [createTextVNode(toDisplayString(displayedModelValue ?? __props.placeholder ?? "\xA0"), 1)])];
											}),
											_: 2
										}, _parent, _scopeId));
									});
									_push(`<!--]-->`);
									if (unref(isTrailing) || !!slots.trailing) {
										_push(`<span data-slot="trailing" class="${ssrRenderClass(ui.value.trailing({ class: unref(uiProp)?.trailing }))}"${_scopeId}>`);
										ssrRenderSlot(_ctx.$slots, "trailing", {
											modelValue,
											open,
											ui: ui.value
										}, () => {
											if (unref(trailingIconName)) _push(ssrRenderComponent(_sfc_main$2, {
												name: unref(trailingIconName),
												"data-slot": "trailingIcon",
												class: ui.value.trailingIcon({ class: unref(uiProp)?.trailingIcon })
											}, null, _parent, _scopeId));
											else _push(`<!---->`);
										}, _push, _parent, _scopeId);
										_push(`</span>`);
									} else _push(`<!---->`);
								} else return [
									unref(isLeading) || !!__props.avatar || !!slots.leading ? (openBlock(), createBlock("span", {
										key: 0,
										"data-slot": "leading",
										class: ui.value.leading({ class: unref(uiProp)?.leading })
									}, [renderSlot(_ctx.$slots, "leading", {
										modelValue,
										open,
										ui: ui.value
									}, () => [unref(isLeading) && unref(leadingIconName) ? (openBlock(), createBlock(_sfc_main$2, {
										key: 0,
										name: unref(leadingIconName),
										"data-slot": "leadingIcon",
										class: ui.value.leadingIcon({ class: unref(uiProp)?.leadingIcon })
									}, null, 8, ["name", "class"])) : !!__props.avatar ? (openBlock(), createBlock(_sfc_main$3, mergeProps({
										key: 1,
										size: unref(uiProp)?.itemLeadingAvatarSize || ui.value.itemLeadingAvatarSize()
									}, __props.avatar, {
										"data-slot": "itemLeadingAvatar",
										class: ui.value.itemLeadingAvatar({ class: unref(uiProp)?.itemLeadingAvatar })
									}), null, 16, ["size", "class"])) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true),
									(openBlock(true), createBlock(Fragment, null, renderList([displayValue(modelValue)], (displayedModelValue) => {
										return openBlock(), createBlock(unref(SelectValue), {
											key: displayedModelValue,
											"data-slot": displayedModelValue != null ? "value" : "placeholder",
											class: displayedModelValue != null ? ui.value.value({ class: unref(uiProp)?.value }) : ui.value.placeholder({ class: unref(uiProp)?.placeholder })
										}, {
											default: withCtx(() => [renderSlot(_ctx.$slots, "default", {
												modelValue,
												open,
												ui: ui.value
											}, () => [createTextVNode(toDisplayString(displayedModelValue ?? __props.placeholder ?? "\xA0"), 1)])]),
											_: 2
										}, 1032, ["data-slot", "class"]);
									}), 128)),
									unref(isTrailing) || !!slots.trailing ? (openBlock(), createBlock("span", {
										key: 1,
										"data-slot": "trailing",
										class: ui.value.trailing({ class: unref(uiProp)?.trailing })
									}, [renderSlot(_ctx.$slots, "trailing", {
										modelValue,
										open,
										ui: ui.value
									}, () => [unref(trailingIconName) ? (openBlock(), createBlock(_sfc_main$2, {
										key: 0,
										name: unref(trailingIconName),
										"data-slot": "trailingIcon",
										class: ui.value.trailingIcon({ class: unref(uiProp)?.trailingIcon })
									}, null, 8, ["name", "class"])) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true)
								];
							}),
							_: 2
						}, _parent, _scopeId));
						_push(ssrRenderComponent(unref(SelectPortal), unref(portalProps), {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(unref(FieldGroupReset), null, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) _push(ssrRenderComponent(unref(SelectContent), mergeProps({
											"data-slot": "content",
											class: ui.value.content({ class: unref(uiProp)?.content })
										}, contentProps.value), {
											default: withCtx((_, _push, _parent, _scopeId) => {
												if (_push) {
													ssrRenderSlot(_ctx.$slots, "content-top", {}, null, _push, _parent, _scopeId);
													ssrRenderVNode(_push, createVNode(resolveDynamicComponent(isItemAligned.value ? unref(SelectViewport) : "div"), {
														ref_key: "viewportRef",
														ref: viewportRef,
														role: "presentation",
														"data-slot": "viewport",
														class: ui.value.viewport({ class: unref(uiProp)?.viewport })
													}, {
														default: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) {
																_push(`<!--[-->`);
																ssrRenderList(groups.value, (group, groupIndex) => {
																	_push(ssrRenderComponent(unref(SelectGroup), {
																		key: `group-${groupIndex}`,
																		"data-slot": "group",
																		class: ui.value.group({ class: unref(uiProp)?.group })
																	}, {
																		default: withCtx((_, _push, _parent, _scopeId) => {
																			if (_push) {
																				_push(`<!--[-->`);
																				ssrRenderList(group, (item, index) => {
																					_push(`<!--[-->`);
																					if (isSelectItem(item) && item.type === "label") _push(ssrRenderComponent(unref(SelectLabel), {
																						"data-slot": "label",
																						class: ui.value.label({ class: [
																							unref(uiProp)?.label,
																							item.ui?.label,
																							item.class
																						] })
																					}, {
																						default: withCtx((_, _push, _parent, _scopeId) => {
																							if (_push) _push(`${ssrInterpolate(unref(get)(item, props.labelKey))}`);
																							else return [createTextVNode(toDisplayString(unref(get)(item, props.labelKey)), 1)];
																						}),
																						_: 2
																					}, _parent, _scopeId));
																					else if (isSelectItem(item) && item.type === "separator") _push(ssrRenderComponent(unref(SelectSeparator), {
																						"data-slot": "separator",
																						class: ui.value.separator({ class: [
																							unref(uiProp)?.separator,
																							item.ui?.separator,
																							item.class
																						] })
																					}, null, _parent, _scopeId));
																					else _push(ssrRenderComponent(unref(SelectItem), {
																						"data-slot": "item",
																						class: ui.value.item({ class: [
																							unref(uiProp)?.item,
																							isSelectItem(item) && item.ui?.item,
																							isSelectItem(item) && item.class
																						] }),
																						disabled: isSelectItem(item) && item.disabled,
																						value: isSelectItem(item) ? unref(get)(item, props.valueKey) : item,
																						onSelect: ($event) => isSelectItem(item) && item.onSelect?.($event)
																					}, {
																						default: withCtx((_, _push, _parent, _scopeId) => {
																							if (_push) ssrRenderSlot(_ctx.$slots, "item", {
																								item,
																								index,
																								ui: ui.value
																							}, () => {
																								ssrRenderSlot(_ctx.$slots, "item-leading", {
																									item,
																									index,
																									ui: ui.value
																								}, () => {
																									if (isSelectItem(item) && item.icon) _push(ssrRenderComponent(_sfc_main$2, {
																										name: item.icon,
																										"data-slot": "itemLeadingIcon",
																										class: ui.value.itemLeadingIcon({ class: [unref(uiProp)?.itemLeadingIcon, item.ui?.itemLeadingIcon] })
																									}, null, _parent, _scopeId));
																									else if (isSelectItem(item) && item.avatar) _push(ssrRenderComponent(_sfc_main$3, mergeProps({ size: item.ui?.itemLeadingAvatarSize || unref(uiProp)?.itemLeadingAvatarSize || ui.value.itemLeadingAvatarSize() }, { ref_for: true }, item.avatar, {
																										"data-slot": "itemLeadingAvatar",
																										class: ui.value.itemLeadingAvatar({ class: [unref(uiProp)?.itemLeadingAvatar, item.ui?.itemLeadingAvatar] })
																									}), null, _parent, _scopeId));
																									else if (isSelectItem(item) && item.chip) _push(ssrRenderComponent(_sfc_main$4, mergeProps({
																										size: item.ui?.itemLeadingChipSize || unref(uiProp)?.itemLeadingChipSize || ui.value.itemLeadingChipSize(),
																										inset: "",
																										standalone: ""
																									}, { ref_for: true }, item.chip, {
																										"data-slot": "itemLeadingChip",
																										class: ui.value.itemLeadingChip({ class: [unref(uiProp)?.itemLeadingChip, item.ui?.itemLeadingChip] })
																									}), null, _parent, _scopeId));
																									else _push(`<!---->`);
																								}, _push, _parent, _scopeId);
																								_push(`<span data-slot="itemWrapper" class="${ssrRenderClass(ui.value.itemWrapper({ class: [unref(uiProp)?.itemWrapper, isSelectItem(item) && item.ui?.itemWrapper] }))}"${_scopeId}>`);
																								_push(ssrRenderComponent(unref(SelectItemText), {
																									"data-slot": "itemLabel",
																									class: ui.value.itemLabel({ class: [unref(uiProp)?.itemLabel, isSelectItem(item) && item.ui?.itemLabel] })
																								}, {
																									default: withCtx((_, _push, _parent, _scopeId) => {
																										if (_push) ssrRenderSlot(_ctx.$slots, "item-label", {
																											item,
																											index
																										}, () => {
																											_push(`${ssrInterpolate(isSelectItem(item) ? unref(get)(item, props.labelKey) : item)}`);
																										}, _push, _parent, _scopeId);
																										else return [renderSlot(_ctx.$slots, "item-label", {
																											item,
																											index
																										}, () => [createTextVNode(toDisplayString(isSelectItem(item) ? unref(get)(item, props.labelKey) : item), 1)])];
																									}),
																									_: 2
																								}, _parent, _scopeId));
																								if (isSelectItem(item) && (unref(get)(item, props.descriptionKey) || !!slots["item-description"])) {
																									_push(`<span data-slot="itemDescription" class="${ssrRenderClass(ui.value.itemDescription({ class: [unref(uiProp)?.itemDescription, isSelectItem(item) && item.ui?.itemDescription] }))}"${_scopeId}>`);
																									ssrRenderSlot(_ctx.$slots, "item-description", {
																										item,
																										index
																									}, () => {
																										_push(`${ssrInterpolate(unref(get)(item, props.descriptionKey))}`);
																									}, _push, _parent, _scopeId);
																									_push(`</span>`);
																								} else _push(`<!---->`);
																								_push(`</span><span data-slot="itemTrailing" class="${ssrRenderClass(ui.value.itemTrailing({ class: [unref(uiProp)?.itemTrailing, isSelectItem(item) && item.ui?.itemTrailing] }))}"${_scopeId}>`);
																								ssrRenderSlot(_ctx.$slots, "item-trailing", {
																									item,
																									index,
																									ui: ui.value
																								}, null, _push, _parent, _scopeId);
																								_push(ssrRenderComponent(unref(SelectItemIndicator), { "as-child": "" }, {
																									default: withCtx((_, _push, _parent, _scopeId) => {
																										if (_push) _push(ssrRenderComponent(_sfc_main$2, {
																											name: __props.selectedIcon || unref(appConfig).ui.icons.check,
																											"data-slot": "itemTrailingIcon",
																											class: ui.value.itemTrailingIcon({ class: [unref(uiProp)?.itemTrailingIcon, isSelectItem(item) && item.ui?.itemTrailingIcon] })
																										}, null, _parent, _scopeId));
																										else return [createVNode(_sfc_main$2, {
																											name: __props.selectedIcon || unref(appConfig).ui.icons.check,
																											"data-slot": "itemTrailingIcon",
																											class: ui.value.itemTrailingIcon({ class: [unref(uiProp)?.itemTrailingIcon, isSelectItem(item) && item.ui?.itemTrailingIcon] })
																										}, null, 8, ["name", "class"])];
																									}),
																									_: 2
																								}, _parent, _scopeId));
																								_push(`</span>`);
																							}, _push, _parent, _scopeId);
																							else return [renderSlot(_ctx.$slots, "item", {
																								item,
																								index,
																								ui: ui.value
																							}, () => [
																								renderSlot(_ctx.$slots, "item-leading", {
																									item,
																									index,
																									ui: ui.value
																								}, () => [isSelectItem(item) && item.icon ? (openBlock(), createBlock(_sfc_main$2, {
																									key: 0,
																									name: item.icon,
																									"data-slot": "itemLeadingIcon",
																									class: ui.value.itemLeadingIcon({ class: [unref(uiProp)?.itemLeadingIcon, item.ui?.itemLeadingIcon] })
																								}, null, 8, ["name", "class"])) : isSelectItem(item) && item.avatar ? (openBlock(), createBlock(_sfc_main$3, mergeProps({
																									key: 1,
																									size: item.ui?.itemLeadingAvatarSize || unref(uiProp)?.itemLeadingAvatarSize || ui.value.itemLeadingAvatarSize()
																								}, { ref_for: true }, item.avatar, {
																									"data-slot": "itemLeadingAvatar",
																									class: ui.value.itemLeadingAvatar({ class: [unref(uiProp)?.itemLeadingAvatar, item.ui?.itemLeadingAvatar] })
																								}), null, 16, ["size", "class"])) : isSelectItem(item) && item.chip ? (openBlock(), createBlock(_sfc_main$4, mergeProps({
																									key: 2,
																									size: item.ui?.itemLeadingChipSize || unref(uiProp)?.itemLeadingChipSize || ui.value.itemLeadingChipSize(),
																									inset: "",
																									standalone: ""
																								}, { ref_for: true }, item.chip, {
																									"data-slot": "itemLeadingChip",
																									class: ui.value.itemLeadingChip({ class: [unref(uiProp)?.itemLeadingChip, item.ui?.itemLeadingChip] })
																								}), null, 16, ["size", "class"])) : createCommentVNode("", true)]),
																								createVNode("span", {
																									"data-slot": "itemWrapper",
																									class: ui.value.itemWrapper({ class: [unref(uiProp)?.itemWrapper, isSelectItem(item) && item.ui?.itemWrapper] })
																								}, [createVNode(unref(SelectItemText), {
																									"data-slot": "itemLabel",
																									class: ui.value.itemLabel({ class: [unref(uiProp)?.itemLabel, isSelectItem(item) && item.ui?.itemLabel] })
																								}, {
																									default: withCtx(() => [renderSlot(_ctx.$slots, "item-label", {
																										item,
																										index
																									}, () => [createTextVNode(toDisplayString(isSelectItem(item) ? unref(get)(item, props.labelKey) : item), 1)])]),
																									_: 2
																								}, 1032, ["class"]), isSelectItem(item) && (unref(get)(item, props.descriptionKey) || !!slots["item-description"]) ? (openBlock(), createBlock("span", {
																									key: 0,
																									"data-slot": "itemDescription",
																									class: ui.value.itemDescription({ class: [unref(uiProp)?.itemDescription, isSelectItem(item) && item.ui?.itemDescription] })
																								}, [renderSlot(_ctx.$slots, "item-description", {
																									item,
																									index
																								}, () => [createTextVNode(toDisplayString(unref(get)(item, props.descriptionKey)), 1)])], 2)) : createCommentVNode("", true)], 2),
																								createVNode("span", {
																									"data-slot": "itemTrailing",
																									class: ui.value.itemTrailing({ class: [unref(uiProp)?.itemTrailing, isSelectItem(item) && item.ui?.itemTrailing] })
																								}, [renderSlot(_ctx.$slots, "item-trailing", {
																									item,
																									index,
																									ui: ui.value
																								}), createVNode(unref(SelectItemIndicator), { "as-child": "" }, {
																									default: withCtx(() => [createVNode(_sfc_main$2, {
																										name: __props.selectedIcon || unref(appConfig).ui.icons.check,
																										"data-slot": "itemTrailingIcon",
																										class: ui.value.itemTrailingIcon({ class: [unref(uiProp)?.itemTrailingIcon, isSelectItem(item) && item.ui?.itemTrailingIcon] })
																									}, null, 8, ["name", "class"])]),
																									_: 2
																								}, 1024)], 2)
																							])];
																						}),
																						_: 2
																					}, _parent, _scopeId));
																					_push(`<!--]-->`);
																				});
																				_push(`<!--]-->`);
																			} else return [(openBlock(true), createBlock(Fragment, null, renderList(group, (item, index) => {
																				return openBlock(), createBlock(Fragment, { key: `group-${groupIndex}-${index}` }, [isSelectItem(item) && item.type === "label" ? (openBlock(), createBlock(unref(SelectLabel), {
																					key: 0,
																					"data-slot": "label",
																					class: ui.value.label({ class: [
																						unref(uiProp)?.label,
																						item.ui?.label,
																						item.class
																					] })
																				}, {
																					default: withCtx(() => [createTextVNode(toDisplayString(unref(get)(item, props.labelKey)), 1)]),
																					_: 2
																				}, 1032, ["class"])) : isSelectItem(item) && item.type === "separator" ? (openBlock(), createBlock(unref(SelectSeparator), {
																					key: 1,
																					"data-slot": "separator",
																					class: ui.value.separator({ class: [
																						unref(uiProp)?.separator,
																						item.ui?.separator,
																						item.class
																					] })
																				}, null, 8, ["class"])) : (openBlock(), createBlock(unref(SelectItem), {
																					key: 2,
																					"data-slot": "item",
																					class: ui.value.item({ class: [
																						unref(uiProp)?.item,
																						isSelectItem(item) && item.ui?.item,
																						isSelectItem(item) && item.class
																					] }),
																					disabled: isSelectItem(item) && item.disabled,
																					value: isSelectItem(item) ? unref(get)(item, props.valueKey) : item,
																					onSelect: ($event) => isSelectItem(item) && item.onSelect?.($event)
																				}, {
																					default: withCtx(() => [renderSlot(_ctx.$slots, "item", {
																						item,
																						index,
																						ui: ui.value
																					}, () => [
																						renderSlot(_ctx.$slots, "item-leading", {
																							item,
																							index,
																							ui: ui.value
																						}, () => [isSelectItem(item) && item.icon ? (openBlock(), createBlock(_sfc_main$2, {
																							key: 0,
																							name: item.icon,
																							"data-slot": "itemLeadingIcon",
																							class: ui.value.itemLeadingIcon({ class: [unref(uiProp)?.itemLeadingIcon, item.ui?.itemLeadingIcon] })
																						}, null, 8, ["name", "class"])) : isSelectItem(item) && item.avatar ? (openBlock(), createBlock(_sfc_main$3, mergeProps({
																							key: 1,
																							size: item.ui?.itemLeadingAvatarSize || unref(uiProp)?.itemLeadingAvatarSize || ui.value.itemLeadingAvatarSize()
																						}, { ref_for: true }, item.avatar, {
																							"data-slot": "itemLeadingAvatar",
																							class: ui.value.itemLeadingAvatar({ class: [unref(uiProp)?.itemLeadingAvatar, item.ui?.itemLeadingAvatar] })
																						}), null, 16, ["size", "class"])) : isSelectItem(item) && item.chip ? (openBlock(), createBlock(_sfc_main$4, mergeProps({
																							key: 2,
																							size: item.ui?.itemLeadingChipSize || unref(uiProp)?.itemLeadingChipSize || ui.value.itemLeadingChipSize(),
																							inset: "",
																							standalone: ""
																						}, { ref_for: true }, item.chip, {
																							"data-slot": "itemLeadingChip",
																							class: ui.value.itemLeadingChip({ class: [unref(uiProp)?.itemLeadingChip, item.ui?.itemLeadingChip] })
																						}), null, 16, ["size", "class"])) : createCommentVNode("", true)]),
																						createVNode("span", {
																							"data-slot": "itemWrapper",
																							class: ui.value.itemWrapper({ class: [unref(uiProp)?.itemWrapper, isSelectItem(item) && item.ui?.itemWrapper] })
																						}, [createVNode(unref(SelectItemText), {
																							"data-slot": "itemLabel",
																							class: ui.value.itemLabel({ class: [unref(uiProp)?.itemLabel, isSelectItem(item) && item.ui?.itemLabel] })
																						}, {
																							default: withCtx(() => [renderSlot(_ctx.$slots, "item-label", {
																								item,
																								index
																							}, () => [createTextVNode(toDisplayString(isSelectItem(item) ? unref(get)(item, props.labelKey) : item), 1)])]),
																							_: 2
																						}, 1032, ["class"]), isSelectItem(item) && (unref(get)(item, props.descriptionKey) || !!slots["item-description"]) ? (openBlock(), createBlock("span", {
																							key: 0,
																							"data-slot": "itemDescription",
																							class: ui.value.itemDescription({ class: [unref(uiProp)?.itemDescription, isSelectItem(item) && item.ui?.itemDescription] })
																						}, [renderSlot(_ctx.$slots, "item-description", {
																							item,
																							index
																						}, () => [createTextVNode(toDisplayString(unref(get)(item, props.descriptionKey)), 1)])], 2)) : createCommentVNode("", true)], 2),
																						createVNode("span", {
																							"data-slot": "itemTrailing",
																							class: ui.value.itemTrailing({ class: [unref(uiProp)?.itemTrailing, isSelectItem(item) && item.ui?.itemTrailing] })
																						}, [renderSlot(_ctx.$slots, "item-trailing", {
																							item,
																							index,
																							ui: ui.value
																						}), createVNode(unref(SelectItemIndicator), { "as-child": "" }, {
																							default: withCtx(() => [createVNode(_sfc_main$2, {
																								name: __props.selectedIcon || unref(appConfig).ui.icons.check,
																								"data-slot": "itemTrailingIcon",
																								class: ui.value.itemTrailingIcon({ class: [unref(uiProp)?.itemTrailingIcon, isSelectItem(item) && item.ui?.itemTrailingIcon] })
																							}, null, 8, ["name", "class"])]),
																							_: 2
																						}, 1024)], 2)
																					])]),
																					_: 2
																				}, 1032, [
																					"class",
																					"disabled",
																					"value",
																					"onSelect"
																				]))], 64);
																			}), 128))];
																		}),
																		_: 2
																	}, _parent, _scopeId));
																});
																_push(`<!--]-->`);
															} else return [(openBlock(true), createBlock(Fragment, null, renderList(groups.value, (group, groupIndex) => {
																return openBlock(), createBlock(unref(SelectGroup), {
																	key: `group-${groupIndex}`,
																	"data-slot": "group",
																	class: ui.value.group({ class: unref(uiProp)?.group })
																}, {
																	default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(group, (item, index) => {
																		return openBlock(), createBlock(Fragment, { key: `group-${groupIndex}-${index}` }, [isSelectItem(item) && item.type === "label" ? (openBlock(), createBlock(unref(SelectLabel), {
																			key: 0,
																			"data-slot": "label",
																			class: ui.value.label({ class: [
																				unref(uiProp)?.label,
																				item.ui?.label,
																				item.class
																			] })
																		}, {
																			default: withCtx(() => [createTextVNode(toDisplayString(unref(get)(item, props.labelKey)), 1)]),
																			_: 2
																		}, 1032, ["class"])) : isSelectItem(item) && item.type === "separator" ? (openBlock(), createBlock(unref(SelectSeparator), {
																			key: 1,
																			"data-slot": "separator",
																			class: ui.value.separator({ class: [
																				unref(uiProp)?.separator,
																				item.ui?.separator,
																				item.class
																			] })
																		}, null, 8, ["class"])) : (openBlock(), createBlock(unref(SelectItem), {
																			key: 2,
																			"data-slot": "item",
																			class: ui.value.item({ class: [
																				unref(uiProp)?.item,
																				isSelectItem(item) && item.ui?.item,
																				isSelectItem(item) && item.class
																			] }),
																			disabled: isSelectItem(item) && item.disabled,
																			value: isSelectItem(item) ? unref(get)(item, props.valueKey) : item,
																			onSelect: ($event) => isSelectItem(item) && item.onSelect?.($event)
																		}, {
																			default: withCtx(() => [renderSlot(_ctx.$slots, "item", {
																				item,
																				index,
																				ui: ui.value
																			}, () => [
																				renderSlot(_ctx.$slots, "item-leading", {
																					item,
																					index,
																					ui: ui.value
																				}, () => [isSelectItem(item) && item.icon ? (openBlock(), createBlock(_sfc_main$2, {
																					key: 0,
																					name: item.icon,
																					"data-slot": "itemLeadingIcon",
																					class: ui.value.itemLeadingIcon({ class: [unref(uiProp)?.itemLeadingIcon, item.ui?.itemLeadingIcon] })
																				}, null, 8, ["name", "class"])) : isSelectItem(item) && item.avatar ? (openBlock(), createBlock(_sfc_main$3, mergeProps({
																					key: 1,
																					size: item.ui?.itemLeadingAvatarSize || unref(uiProp)?.itemLeadingAvatarSize || ui.value.itemLeadingAvatarSize()
																				}, { ref_for: true }, item.avatar, {
																					"data-slot": "itemLeadingAvatar",
																					class: ui.value.itemLeadingAvatar({ class: [unref(uiProp)?.itemLeadingAvatar, item.ui?.itemLeadingAvatar] })
																				}), null, 16, ["size", "class"])) : isSelectItem(item) && item.chip ? (openBlock(), createBlock(_sfc_main$4, mergeProps({
																					key: 2,
																					size: item.ui?.itemLeadingChipSize || unref(uiProp)?.itemLeadingChipSize || ui.value.itemLeadingChipSize(),
																					inset: "",
																					standalone: ""
																				}, { ref_for: true }, item.chip, {
																					"data-slot": "itemLeadingChip",
																					class: ui.value.itemLeadingChip({ class: [unref(uiProp)?.itemLeadingChip, item.ui?.itemLeadingChip] })
																				}), null, 16, ["size", "class"])) : createCommentVNode("", true)]),
																				createVNode("span", {
																					"data-slot": "itemWrapper",
																					class: ui.value.itemWrapper({ class: [unref(uiProp)?.itemWrapper, isSelectItem(item) && item.ui?.itemWrapper] })
																				}, [createVNode(unref(SelectItemText), {
																					"data-slot": "itemLabel",
																					class: ui.value.itemLabel({ class: [unref(uiProp)?.itemLabel, isSelectItem(item) && item.ui?.itemLabel] })
																				}, {
																					default: withCtx(() => [renderSlot(_ctx.$slots, "item-label", {
																						item,
																						index
																					}, () => [createTextVNode(toDisplayString(isSelectItem(item) ? unref(get)(item, props.labelKey) : item), 1)])]),
																					_: 2
																				}, 1032, ["class"]), isSelectItem(item) && (unref(get)(item, props.descriptionKey) || !!slots["item-description"]) ? (openBlock(), createBlock("span", {
																					key: 0,
																					"data-slot": "itemDescription",
																					class: ui.value.itemDescription({ class: [unref(uiProp)?.itemDescription, isSelectItem(item) && item.ui?.itemDescription] })
																				}, [renderSlot(_ctx.$slots, "item-description", {
																					item,
																					index
																				}, () => [createTextVNode(toDisplayString(unref(get)(item, props.descriptionKey)), 1)])], 2)) : createCommentVNode("", true)], 2),
																				createVNode("span", {
																					"data-slot": "itemTrailing",
																					class: ui.value.itemTrailing({ class: [unref(uiProp)?.itemTrailing, isSelectItem(item) && item.ui?.itemTrailing] })
																				}, [renderSlot(_ctx.$slots, "item-trailing", {
																					item,
																					index,
																					ui: ui.value
																				}), createVNode(unref(SelectItemIndicator), { "as-child": "" }, {
																					default: withCtx(() => [createVNode(_sfc_main$2, {
																						name: __props.selectedIcon || unref(appConfig).ui.icons.check,
																						"data-slot": "itemTrailingIcon",
																						class: ui.value.itemTrailingIcon({ class: [unref(uiProp)?.itemTrailingIcon, isSelectItem(item) && item.ui?.itemTrailingIcon] })
																					}, null, 8, ["name", "class"])]),
																					_: 2
																				}, 1024)], 2)
																			])]),
																			_: 2
																		}, 1032, [
																			"class",
																			"disabled",
																			"value",
																			"onSelect"
																		]))], 64);
																	}), 128))]),
																	_: 2
																}, 1032, ["class"]);
															}), 128))];
														}),
														_: 2
													}), _parent, _scopeId);
													ssrRenderSlot(_ctx.$slots, "content-bottom", {}, null, _push, _parent, _scopeId);
													if (!!__props.arrow) _push(ssrRenderComponent(unref(SelectArrow), mergeProps(arrowProps.value, {
														"data-slot": "arrow",
														class: ui.value.arrow({ class: unref(uiProp)?.arrow })
													}), null, _parent, _scopeId));
													else _push(`<!---->`);
												} else return [
													renderSlot(_ctx.$slots, "content-top"),
													(openBlock(), createBlock(resolveDynamicComponent(isItemAligned.value ? unref(SelectViewport) : "div"), {
														ref_key: "viewportRef",
														ref: viewportRef,
														role: "presentation",
														"data-slot": "viewport",
														class: ui.value.viewport({ class: unref(uiProp)?.viewport })
													}, {
														default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(groups.value, (group, groupIndex) => {
															return openBlock(), createBlock(unref(SelectGroup), {
																key: `group-${groupIndex}`,
																"data-slot": "group",
																class: ui.value.group({ class: unref(uiProp)?.group })
															}, {
																default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(group, (item, index) => {
																	return openBlock(), createBlock(Fragment, { key: `group-${groupIndex}-${index}` }, [isSelectItem(item) && item.type === "label" ? (openBlock(), createBlock(unref(SelectLabel), {
																		key: 0,
																		"data-slot": "label",
																		class: ui.value.label({ class: [
																			unref(uiProp)?.label,
																			item.ui?.label,
																			item.class
																		] })
																	}, {
																		default: withCtx(() => [createTextVNode(toDisplayString(unref(get)(item, props.labelKey)), 1)]),
																		_: 2
																	}, 1032, ["class"])) : isSelectItem(item) && item.type === "separator" ? (openBlock(), createBlock(unref(SelectSeparator), {
																		key: 1,
																		"data-slot": "separator",
																		class: ui.value.separator({ class: [
																			unref(uiProp)?.separator,
																			item.ui?.separator,
																			item.class
																		] })
																	}, null, 8, ["class"])) : (openBlock(), createBlock(unref(SelectItem), {
																		key: 2,
																		"data-slot": "item",
																		class: ui.value.item({ class: [
																			unref(uiProp)?.item,
																			isSelectItem(item) && item.ui?.item,
																			isSelectItem(item) && item.class
																		] }),
																		disabled: isSelectItem(item) && item.disabled,
																		value: isSelectItem(item) ? unref(get)(item, props.valueKey) : item,
																		onSelect: ($event) => isSelectItem(item) && item.onSelect?.($event)
																	}, {
																		default: withCtx(() => [renderSlot(_ctx.$slots, "item", {
																			item,
																			index,
																			ui: ui.value
																		}, () => [
																			renderSlot(_ctx.$slots, "item-leading", {
																				item,
																				index,
																				ui: ui.value
																			}, () => [isSelectItem(item) && item.icon ? (openBlock(), createBlock(_sfc_main$2, {
																				key: 0,
																				name: item.icon,
																				"data-slot": "itemLeadingIcon",
																				class: ui.value.itemLeadingIcon({ class: [unref(uiProp)?.itemLeadingIcon, item.ui?.itemLeadingIcon] })
																			}, null, 8, ["name", "class"])) : isSelectItem(item) && item.avatar ? (openBlock(), createBlock(_sfc_main$3, mergeProps({
																				key: 1,
																				size: item.ui?.itemLeadingAvatarSize || unref(uiProp)?.itemLeadingAvatarSize || ui.value.itemLeadingAvatarSize()
																			}, { ref_for: true }, item.avatar, {
																				"data-slot": "itemLeadingAvatar",
																				class: ui.value.itemLeadingAvatar({ class: [unref(uiProp)?.itemLeadingAvatar, item.ui?.itemLeadingAvatar] })
																			}), null, 16, ["size", "class"])) : isSelectItem(item) && item.chip ? (openBlock(), createBlock(_sfc_main$4, mergeProps({
																				key: 2,
																				size: item.ui?.itemLeadingChipSize || unref(uiProp)?.itemLeadingChipSize || ui.value.itemLeadingChipSize(),
																				inset: "",
																				standalone: ""
																			}, { ref_for: true }, item.chip, {
																				"data-slot": "itemLeadingChip",
																				class: ui.value.itemLeadingChip({ class: [unref(uiProp)?.itemLeadingChip, item.ui?.itemLeadingChip] })
																			}), null, 16, ["size", "class"])) : createCommentVNode("", true)]),
																			createVNode("span", {
																				"data-slot": "itemWrapper",
																				class: ui.value.itemWrapper({ class: [unref(uiProp)?.itemWrapper, isSelectItem(item) && item.ui?.itemWrapper] })
																			}, [createVNode(unref(SelectItemText), {
																				"data-slot": "itemLabel",
																				class: ui.value.itemLabel({ class: [unref(uiProp)?.itemLabel, isSelectItem(item) && item.ui?.itemLabel] })
																			}, {
																				default: withCtx(() => [renderSlot(_ctx.$slots, "item-label", {
																					item,
																					index
																				}, () => [createTextVNode(toDisplayString(isSelectItem(item) ? unref(get)(item, props.labelKey) : item), 1)])]),
																				_: 2
																			}, 1032, ["class"]), isSelectItem(item) && (unref(get)(item, props.descriptionKey) || !!slots["item-description"]) ? (openBlock(), createBlock("span", {
																				key: 0,
																				"data-slot": "itemDescription",
																				class: ui.value.itemDescription({ class: [unref(uiProp)?.itemDescription, isSelectItem(item) && item.ui?.itemDescription] })
																			}, [renderSlot(_ctx.$slots, "item-description", {
																				item,
																				index
																			}, () => [createTextVNode(toDisplayString(unref(get)(item, props.descriptionKey)), 1)])], 2)) : createCommentVNode("", true)], 2),
																			createVNode("span", {
																				"data-slot": "itemTrailing",
																				class: ui.value.itemTrailing({ class: [unref(uiProp)?.itemTrailing, isSelectItem(item) && item.ui?.itemTrailing] })
																			}, [renderSlot(_ctx.$slots, "item-trailing", {
																				item,
																				index,
																				ui: ui.value
																			}), createVNode(unref(SelectItemIndicator), { "as-child": "" }, {
																				default: withCtx(() => [createVNode(_sfc_main$2, {
																					name: __props.selectedIcon || unref(appConfig).ui.icons.check,
																					"data-slot": "itemTrailingIcon",
																					class: ui.value.itemTrailingIcon({ class: [unref(uiProp)?.itemTrailingIcon, isSelectItem(item) && item.ui?.itemTrailingIcon] })
																				}, null, 8, ["name", "class"])]),
																				_: 2
																			}, 1024)], 2)
																		])]),
																		_: 2
																	}, 1032, [
																		"class",
																		"disabled",
																		"value",
																		"onSelect"
																	]))], 64);
																}), 128))]),
																_: 2
															}, 1032, ["class"]);
														}), 128))]),
														_: 3
													}, 8, ["class"])),
													renderSlot(_ctx.$slots, "content-bottom"),
													!!__props.arrow ? (openBlock(), createBlock(unref(SelectArrow), mergeProps({ key: 0 }, arrowProps.value, {
														"data-slot": "arrow",
														class: ui.value.arrow({ class: unref(uiProp)?.arrow })
													}), null, 16, ["class"])) : createCommentVNode("", true)
												];
											}),
											_: 2
										}, _parent, _scopeId));
										else return [createVNode(unref(SelectContent), mergeProps({
											"data-slot": "content",
											class: ui.value.content({ class: unref(uiProp)?.content })
										}, contentProps.value), {
											default: withCtx(() => [
												renderSlot(_ctx.$slots, "content-top"),
												(openBlock(), createBlock(resolveDynamicComponent(isItemAligned.value ? unref(SelectViewport) : "div"), {
													ref_key: "viewportRef",
													ref: viewportRef,
													role: "presentation",
													"data-slot": "viewport",
													class: ui.value.viewport({ class: unref(uiProp)?.viewport })
												}, {
													default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(groups.value, (group, groupIndex) => {
														return openBlock(), createBlock(unref(SelectGroup), {
															key: `group-${groupIndex}`,
															"data-slot": "group",
															class: ui.value.group({ class: unref(uiProp)?.group })
														}, {
															default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(group, (item, index) => {
																return openBlock(), createBlock(Fragment, { key: `group-${groupIndex}-${index}` }, [isSelectItem(item) && item.type === "label" ? (openBlock(), createBlock(unref(SelectLabel), {
																	key: 0,
																	"data-slot": "label",
																	class: ui.value.label({ class: [
																		unref(uiProp)?.label,
																		item.ui?.label,
																		item.class
																	] })
																}, {
																	default: withCtx(() => [createTextVNode(toDisplayString(unref(get)(item, props.labelKey)), 1)]),
																	_: 2
																}, 1032, ["class"])) : isSelectItem(item) && item.type === "separator" ? (openBlock(), createBlock(unref(SelectSeparator), {
																	key: 1,
																	"data-slot": "separator",
																	class: ui.value.separator({ class: [
																		unref(uiProp)?.separator,
																		item.ui?.separator,
																		item.class
																	] })
																}, null, 8, ["class"])) : (openBlock(), createBlock(unref(SelectItem), {
																	key: 2,
																	"data-slot": "item",
																	class: ui.value.item({ class: [
																		unref(uiProp)?.item,
																		isSelectItem(item) && item.ui?.item,
																		isSelectItem(item) && item.class
																	] }),
																	disabled: isSelectItem(item) && item.disabled,
																	value: isSelectItem(item) ? unref(get)(item, props.valueKey) : item,
																	onSelect: ($event) => isSelectItem(item) && item.onSelect?.($event)
																}, {
																	default: withCtx(() => [renderSlot(_ctx.$slots, "item", {
																		item,
																		index,
																		ui: ui.value
																	}, () => [
																		renderSlot(_ctx.$slots, "item-leading", {
																			item,
																			index,
																			ui: ui.value
																		}, () => [isSelectItem(item) && item.icon ? (openBlock(), createBlock(_sfc_main$2, {
																			key: 0,
																			name: item.icon,
																			"data-slot": "itemLeadingIcon",
																			class: ui.value.itemLeadingIcon({ class: [unref(uiProp)?.itemLeadingIcon, item.ui?.itemLeadingIcon] })
																		}, null, 8, ["name", "class"])) : isSelectItem(item) && item.avatar ? (openBlock(), createBlock(_sfc_main$3, mergeProps({
																			key: 1,
																			size: item.ui?.itemLeadingAvatarSize || unref(uiProp)?.itemLeadingAvatarSize || ui.value.itemLeadingAvatarSize()
																		}, { ref_for: true }, item.avatar, {
																			"data-slot": "itemLeadingAvatar",
																			class: ui.value.itemLeadingAvatar({ class: [unref(uiProp)?.itemLeadingAvatar, item.ui?.itemLeadingAvatar] })
																		}), null, 16, ["size", "class"])) : isSelectItem(item) && item.chip ? (openBlock(), createBlock(_sfc_main$4, mergeProps({
																			key: 2,
																			size: item.ui?.itemLeadingChipSize || unref(uiProp)?.itemLeadingChipSize || ui.value.itemLeadingChipSize(),
																			inset: "",
																			standalone: ""
																		}, { ref_for: true }, item.chip, {
																			"data-slot": "itemLeadingChip",
																			class: ui.value.itemLeadingChip({ class: [unref(uiProp)?.itemLeadingChip, item.ui?.itemLeadingChip] })
																		}), null, 16, ["size", "class"])) : createCommentVNode("", true)]),
																		createVNode("span", {
																			"data-slot": "itemWrapper",
																			class: ui.value.itemWrapper({ class: [unref(uiProp)?.itemWrapper, isSelectItem(item) && item.ui?.itemWrapper] })
																		}, [createVNode(unref(SelectItemText), {
																			"data-slot": "itemLabel",
																			class: ui.value.itemLabel({ class: [unref(uiProp)?.itemLabel, isSelectItem(item) && item.ui?.itemLabel] })
																		}, {
																			default: withCtx(() => [renderSlot(_ctx.$slots, "item-label", {
																				item,
																				index
																			}, () => [createTextVNode(toDisplayString(isSelectItem(item) ? unref(get)(item, props.labelKey) : item), 1)])]),
																			_: 2
																		}, 1032, ["class"]), isSelectItem(item) && (unref(get)(item, props.descriptionKey) || !!slots["item-description"]) ? (openBlock(), createBlock("span", {
																			key: 0,
																			"data-slot": "itemDescription",
																			class: ui.value.itemDescription({ class: [unref(uiProp)?.itemDescription, isSelectItem(item) && item.ui?.itemDescription] })
																		}, [renderSlot(_ctx.$slots, "item-description", {
																			item,
																			index
																		}, () => [createTextVNode(toDisplayString(unref(get)(item, props.descriptionKey)), 1)])], 2)) : createCommentVNode("", true)], 2),
																		createVNode("span", {
																			"data-slot": "itemTrailing",
																			class: ui.value.itemTrailing({ class: [unref(uiProp)?.itemTrailing, isSelectItem(item) && item.ui?.itemTrailing] })
																		}, [renderSlot(_ctx.$slots, "item-trailing", {
																			item,
																			index,
																			ui: ui.value
																		}), createVNode(unref(SelectItemIndicator), { "as-child": "" }, {
																			default: withCtx(() => [createVNode(_sfc_main$2, {
																				name: __props.selectedIcon || unref(appConfig).ui.icons.check,
																				"data-slot": "itemTrailingIcon",
																				class: ui.value.itemTrailingIcon({ class: [unref(uiProp)?.itemTrailingIcon, isSelectItem(item) && item.ui?.itemTrailingIcon] })
																			}, null, 8, ["name", "class"])]),
																			_: 2
																		}, 1024)], 2)
																	])]),
																	_: 2
																}, 1032, [
																	"class",
																	"disabled",
																	"value",
																	"onSelect"
																]))], 64);
															}), 128))]),
															_: 2
														}, 1032, ["class"]);
													}), 128))]),
													_: 3
												}, 8, ["class"])),
												renderSlot(_ctx.$slots, "content-bottom"),
												!!__props.arrow ? (openBlock(), createBlock(unref(SelectArrow), mergeProps({ key: 0 }, arrowProps.value, {
													"data-slot": "arrow",
													class: ui.value.arrow({ class: unref(uiProp)?.arrow })
												}), null, 16, ["class"])) : createCommentVNode("", true)
											]),
											_: 3
										}, 16, ["class"])];
									}),
									_: 2
								}, _parent, _scopeId));
								else return [createVNode(unref(FieldGroupReset), null, {
									default: withCtx(() => [createVNode(unref(SelectContent), mergeProps({
										"data-slot": "content",
										class: ui.value.content({ class: unref(uiProp)?.content })
									}, contentProps.value), {
										default: withCtx(() => [
											renderSlot(_ctx.$slots, "content-top"),
											(openBlock(), createBlock(resolveDynamicComponent(isItemAligned.value ? unref(SelectViewport) : "div"), {
												ref_key: "viewportRef",
												ref: viewportRef,
												role: "presentation",
												"data-slot": "viewport",
												class: ui.value.viewport({ class: unref(uiProp)?.viewport })
											}, {
												default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(groups.value, (group, groupIndex) => {
													return openBlock(), createBlock(unref(SelectGroup), {
														key: `group-${groupIndex}`,
														"data-slot": "group",
														class: ui.value.group({ class: unref(uiProp)?.group })
													}, {
														default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(group, (item, index) => {
															return openBlock(), createBlock(Fragment, { key: `group-${groupIndex}-${index}` }, [isSelectItem(item) && item.type === "label" ? (openBlock(), createBlock(unref(SelectLabel), {
																key: 0,
																"data-slot": "label",
																class: ui.value.label({ class: [
																	unref(uiProp)?.label,
																	item.ui?.label,
																	item.class
																] })
															}, {
																default: withCtx(() => [createTextVNode(toDisplayString(unref(get)(item, props.labelKey)), 1)]),
																_: 2
															}, 1032, ["class"])) : isSelectItem(item) && item.type === "separator" ? (openBlock(), createBlock(unref(SelectSeparator), {
																key: 1,
																"data-slot": "separator",
																class: ui.value.separator({ class: [
																	unref(uiProp)?.separator,
																	item.ui?.separator,
																	item.class
																] })
															}, null, 8, ["class"])) : (openBlock(), createBlock(unref(SelectItem), {
																key: 2,
																"data-slot": "item",
																class: ui.value.item({ class: [
																	unref(uiProp)?.item,
																	isSelectItem(item) && item.ui?.item,
																	isSelectItem(item) && item.class
																] }),
																disabled: isSelectItem(item) && item.disabled,
																value: isSelectItem(item) ? unref(get)(item, props.valueKey) : item,
																onSelect: ($event) => isSelectItem(item) && item.onSelect?.($event)
															}, {
																default: withCtx(() => [renderSlot(_ctx.$slots, "item", {
																	item,
																	index,
																	ui: ui.value
																}, () => [
																	renderSlot(_ctx.$slots, "item-leading", {
																		item,
																		index,
																		ui: ui.value
																	}, () => [isSelectItem(item) && item.icon ? (openBlock(), createBlock(_sfc_main$2, {
																		key: 0,
																		name: item.icon,
																		"data-slot": "itemLeadingIcon",
																		class: ui.value.itemLeadingIcon({ class: [unref(uiProp)?.itemLeadingIcon, item.ui?.itemLeadingIcon] })
																	}, null, 8, ["name", "class"])) : isSelectItem(item) && item.avatar ? (openBlock(), createBlock(_sfc_main$3, mergeProps({
																		key: 1,
																		size: item.ui?.itemLeadingAvatarSize || unref(uiProp)?.itemLeadingAvatarSize || ui.value.itemLeadingAvatarSize()
																	}, { ref_for: true }, item.avatar, {
																		"data-slot": "itemLeadingAvatar",
																		class: ui.value.itemLeadingAvatar({ class: [unref(uiProp)?.itemLeadingAvatar, item.ui?.itemLeadingAvatar] })
																	}), null, 16, ["size", "class"])) : isSelectItem(item) && item.chip ? (openBlock(), createBlock(_sfc_main$4, mergeProps({
																		key: 2,
																		size: item.ui?.itemLeadingChipSize || unref(uiProp)?.itemLeadingChipSize || ui.value.itemLeadingChipSize(),
																		inset: "",
																		standalone: ""
																	}, { ref_for: true }, item.chip, {
																		"data-slot": "itemLeadingChip",
																		class: ui.value.itemLeadingChip({ class: [unref(uiProp)?.itemLeadingChip, item.ui?.itemLeadingChip] })
																	}), null, 16, ["size", "class"])) : createCommentVNode("", true)]),
																	createVNode("span", {
																		"data-slot": "itemWrapper",
																		class: ui.value.itemWrapper({ class: [unref(uiProp)?.itemWrapper, isSelectItem(item) && item.ui?.itemWrapper] })
																	}, [createVNode(unref(SelectItemText), {
																		"data-slot": "itemLabel",
																		class: ui.value.itemLabel({ class: [unref(uiProp)?.itemLabel, isSelectItem(item) && item.ui?.itemLabel] })
																	}, {
																		default: withCtx(() => [renderSlot(_ctx.$slots, "item-label", {
																			item,
																			index
																		}, () => [createTextVNode(toDisplayString(isSelectItem(item) ? unref(get)(item, props.labelKey) : item), 1)])]),
																		_: 2
																	}, 1032, ["class"]), isSelectItem(item) && (unref(get)(item, props.descriptionKey) || !!slots["item-description"]) ? (openBlock(), createBlock("span", {
																		key: 0,
																		"data-slot": "itemDescription",
																		class: ui.value.itemDescription({ class: [unref(uiProp)?.itemDescription, isSelectItem(item) && item.ui?.itemDescription] })
																	}, [renderSlot(_ctx.$slots, "item-description", {
																		item,
																		index
																	}, () => [createTextVNode(toDisplayString(unref(get)(item, props.descriptionKey)), 1)])], 2)) : createCommentVNode("", true)], 2),
																	createVNode("span", {
																		"data-slot": "itemTrailing",
																		class: ui.value.itemTrailing({ class: [unref(uiProp)?.itemTrailing, isSelectItem(item) && item.ui?.itemTrailing] })
																	}, [renderSlot(_ctx.$slots, "item-trailing", {
																		item,
																		index,
																		ui: ui.value
																	}), createVNode(unref(SelectItemIndicator), { "as-child": "" }, {
																		default: withCtx(() => [createVNode(_sfc_main$2, {
																			name: __props.selectedIcon || unref(appConfig).ui.icons.check,
																			"data-slot": "itemTrailingIcon",
																			class: ui.value.itemTrailingIcon({ class: [unref(uiProp)?.itemTrailingIcon, isSelectItem(item) && item.ui?.itemTrailingIcon] })
																		}, null, 8, ["name", "class"])]),
																		_: 2
																	}, 1024)], 2)
																])]),
																_: 2
															}, 1032, [
																"class",
																"disabled",
																"value",
																"onSelect"
															]))], 64);
														}), 128))]),
														_: 2
													}, 1032, ["class"]);
												}), 128))]),
												_: 3
											}, 8, ["class"])),
											renderSlot(_ctx.$slots, "content-bottom"),
											!!__props.arrow ? (openBlock(), createBlock(unref(SelectArrow), mergeProps({ key: 0 }, arrowProps.value, {
												"data-slot": "arrow",
												class: ui.value.arrow({ class: unref(uiProp)?.arrow })
											}), null, 16, ["class"])) : createCommentVNode("", true)
										]),
										_: 3
									}, 16, ["class"])]),
									_: 3
								})];
							}),
							_: 2
						}, _parent, _scopeId));
					} else return [createVNode(unref(SelectTrigger), mergeProps({
						id: unref(id),
						ref_key: "triggerRef",
						ref: triggerRef,
						"data-slot": "base",
						class: ui.value.base({ class: [unref(uiProp)?.base, props.class] })
					}, {
						..._ctx.$attrs,
						...unref(ariaAttrs)
					}), {
						default: withCtx(() => [
							unref(isLeading) || !!__props.avatar || !!slots.leading ? (openBlock(), createBlock("span", {
								key: 0,
								"data-slot": "leading",
								class: ui.value.leading({ class: unref(uiProp)?.leading })
							}, [renderSlot(_ctx.$slots, "leading", {
								modelValue,
								open,
								ui: ui.value
							}, () => [unref(isLeading) && unref(leadingIconName) ? (openBlock(), createBlock(_sfc_main$2, {
								key: 0,
								name: unref(leadingIconName),
								"data-slot": "leadingIcon",
								class: ui.value.leadingIcon({ class: unref(uiProp)?.leadingIcon })
							}, null, 8, ["name", "class"])) : !!__props.avatar ? (openBlock(), createBlock(_sfc_main$3, mergeProps({
								key: 1,
								size: unref(uiProp)?.itemLeadingAvatarSize || ui.value.itemLeadingAvatarSize()
							}, __props.avatar, {
								"data-slot": "itemLeadingAvatar",
								class: ui.value.itemLeadingAvatar({ class: unref(uiProp)?.itemLeadingAvatar })
							}), null, 16, ["size", "class"])) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true),
							(openBlock(true), createBlock(Fragment, null, renderList([displayValue(modelValue)], (displayedModelValue) => {
								return openBlock(), createBlock(unref(SelectValue), {
									key: displayedModelValue,
									"data-slot": displayedModelValue != null ? "value" : "placeholder",
									class: displayedModelValue != null ? ui.value.value({ class: unref(uiProp)?.value }) : ui.value.placeholder({ class: unref(uiProp)?.placeholder })
								}, {
									default: withCtx(() => [renderSlot(_ctx.$slots, "default", {
										modelValue,
										open,
										ui: ui.value
									}, () => [createTextVNode(toDisplayString(displayedModelValue ?? __props.placeholder ?? "\xA0"), 1)])]),
									_: 2
								}, 1032, ["data-slot", "class"]);
							}), 128)),
							unref(isTrailing) || !!slots.trailing ? (openBlock(), createBlock("span", {
								key: 1,
								"data-slot": "trailing",
								class: ui.value.trailing({ class: unref(uiProp)?.trailing })
							}, [renderSlot(_ctx.$slots, "trailing", {
								modelValue,
								open,
								ui: ui.value
							}, () => [unref(trailingIconName) ? (openBlock(), createBlock(_sfc_main$2, {
								key: 0,
								name: unref(trailingIconName),
								"data-slot": "trailingIcon",
								class: ui.value.trailingIcon({ class: unref(uiProp)?.trailingIcon })
							}, null, 8, ["name", "class"])) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true)
						]),
						_: 2
					}, 1040, ["id", "class"]), createVNode(unref(SelectPortal), unref(portalProps), {
						default: withCtx(() => [createVNode(unref(FieldGroupReset), null, {
							default: withCtx(() => [createVNode(unref(SelectContent), mergeProps({
								"data-slot": "content",
								class: ui.value.content({ class: unref(uiProp)?.content })
							}, contentProps.value), {
								default: withCtx(() => [
									renderSlot(_ctx.$slots, "content-top"),
									(openBlock(), createBlock(resolveDynamicComponent(isItemAligned.value ? unref(SelectViewport) : "div"), {
										ref_key: "viewportRef",
										ref: viewportRef,
										role: "presentation",
										"data-slot": "viewport",
										class: ui.value.viewport({ class: unref(uiProp)?.viewport })
									}, {
										default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(groups.value, (group, groupIndex) => {
											return openBlock(), createBlock(unref(SelectGroup), {
												key: `group-${groupIndex}`,
												"data-slot": "group",
												class: ui.value.group({ class: unref(uiProp)?.group })
											}, {
												default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(group, (item, index) => {
													return openBlock(), createBlock(Fragment, { key: `group-${groupIndex}-${index}` }, [isSelectItem(item) && item.type === "label" ? (openBlock(), createBlock(unref(SelectLabel), {
														key: 0,
														"data-slot": "label",
														class: ui.value.label({ class: [
															unref(uiProp)?.label,
															item.ui?.label,
															item.class
														] })
													}, {
														default: withCtx(() => [createTextVNode(toDisplayString(unref(get)(item, props.labelKey)), 1)]),
														_: 2
													}, 1032, ["class"])) : isSelectItem(item) && item.type === "separator" ? (openBlock(), createBlock(unref(SelectSeparator), {
														key: 1,
														"data-slot": "separator",
														class: ui.value.separator({ class: [
															unref(uiProp)?.separator,
															item.ui?.separator,
															item.class
														] })
													}, null, 8, ["class"])) : (openBlock(), createBlock(unref(SelectItem), {
														key: 2,
														"data-slot": "item",
														class: ui.value.item({ class: [
															unref(uiProp)?.item,
															isSelectItem(item) && item.ui?.item,
															isSelectItem(item) && item.class
														] }),
														disabled: isSelectItem(item) && item.disabled,
														value: isSelectItem(item) ? unref(get)(item, props.valueKey) : item,
														onSelect: ($event) => isSelectItem(item) && item.onSelect?.($event)
													}, {
														default: withCtx(() => [renderSlot(_ctx.$slots, "item", {
															item,
															index,
															ui: ui.value
														}, () => [
															renderSlot(_ctx.$slots, "item-leading", {
																item,
																index,
																ui: ui.value
															}, () => [isSelectItem(item) && item.icon ? (openBlock(), createBlock(_sfc_main$2, {
																key: 0,
																name: item.icon,
																"data-slot": "itemLeadingIcon",
																class: ui.value.itemLeadingIcon({ class: [unref(uiProp)?.itemLeadingIcon, item.ui?.itemLeadingIcon] })
															}, null, 8, ["name", "class"])) : isSelectItem(item) && item.avatar ? (openBlock(), createBlock(_sfc_main$3, mergeProps({
																key: 1,
																size: item.ui?.itemLeadingAvatarSize || unref(uiProp)?.itemLeadingAvatarSize || ui.value.itemLeadingAvatarSize()
															}, { ref_for: true }, item.avatar, {
																"data-slot": "itemLeadingAvatar",
																class: ui.value.itemLeadingAvatar({ class: [unref(uiProp)?.itemLeadingAvatar, item.ui?.itemLeadingAvatar] })
															}), null, 16, ["size", "class"])) : isSelectItem(item) && item.chip ? (openBlock(), createBlock(_sfc_main$4, mergeProps({
																key: 2,
																size: item.ui?.itemLeadingChipSize || unref(uiProp)?.itemLeadingChipSize || ui.value.itemLeadingChipSize(),
																inset: "",
																standalone: ""
															}, { ref_for: true }, item.chip, {
																"data-slot": "itemLeadingChip",
																class: ui.value.itemLeadingChip({ class: [unref(uiProp)?.itemLeadingChip, item.ui?.itemLeadingChip] })
															}), null, 16, ["size", "class"])) : createCommentVNode("", true)]),
															createVNode("span", {
																"data-slot": "itemWrapper",
																class: ui.value.itemWrapper({ class: [unref(uiProp)?.itemWrapper, isSelectItem(item) && item.ui?.itemWrapper] })
															}, [createVNode(unref(SelectItemText), {
																"data-slot": "itemLabel",
																class: ui.value.itemLabel({ class: [unref(uiProp)?.itemLabel, isSelectItem(item) && item.ui?.itemLabel] })
															}, {
																default: withCtx(() => [renderSlot(_ctx.$slots, "item-label", {
																	item,
																	index
																}, () => [createTextVNode(toDisplayString(isSelectItem(item) ? unref(get)(item, props.labelKey) : item), 1)])]),
																_: 2
															}, 1032, ["class"]), isSelectItem(item) && (unref(get)(item, props.descriptionKey) || !!slots["item-description"]) ? (openBlock(), createBlock("span", {
																key: 0,
																"data-slot": "itemDescription",
																class: ui.value.itemDescription({ class: [unref(uiProp)?.itemDescription, isSelectItem(item) && item.ui?.itemDescription] })
															}, [renderSlot(_ctx.$slots, "item-description", {
																item,
																index
															}, () => [createTextVNode(toDisplayString(unref(get)(item, props.descriptionKey)), 1)])], 2)) : createCommentVNode("", true)], 2),
															createVNode("span", {
																"data-slot": "itemTrailing",
																class: ui.value.itemTrailing({ class: [unref(uiProp)?.itemTrailing, isSelectItem(item) && item.ui?.itemTrailing] })
															}, [renderSlot(_ctx.$slots, "item-trailing", {
																item,
																index,
																ui: ui.value
															}), createVNode(unref(SelectItemIndicator), { "as-child": "" }, {
																default: withCtx(() => [createVNode(_sfc_main$2, {
																	name: __props.selectedIcon || unref(appConfig).ui.icons.check,
																	"data-slot": "itemTrailingIcon",
																	class: ui.value.itemTrailingIcon({ class: [unref(uiProp)?.itemTrailingIcon, isSelectItem(item) && item.ui?.itemTrailingIcon] })
																}, null, 8, ["name", "class"])]),
																_: 2
															}, 1024)], 2)
														])]),
														_: 2
													}, 1032, [
														"class",
														"disabled",
														"value",
														"onSelect"
													]))], 64);
												}), 128))]),
												_: 2
											}, 1032, ["class"]);
										}), 128))]),
										_: 3
									}, 8, ["class"])),
									renderSlot(_ctx.$slots, "content-bottom"),
									!!__props.arrow ? (openBlock(), createBlock(unref(SelectArrow), mergeProps({ key: 0 }, arrowProps.value, {
										"data-slot": "arrow",
										class: ui.value.arrow({ class: unref(uiProp)?.arrow })
									}), null, 16, ["class"])) : createCommentVNode("", true)
								]),
								_: 3
							}, 16, ["class"])]),
							_: 3
						})]),
						_: 3
					}, 16)];
				}),
				_: 3
			}, _parent));
		};
	}
});
var _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Select.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
//#endregion
export { useResolvedVariants as n, _sfc_main$1 as r, _sfc_main as t };

//# sourceMappingURL=Select-Yh80p2Xl.js.map