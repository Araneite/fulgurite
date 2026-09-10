import { C as useComponentIcons, F as useAppConfig, M as looseToNumber, b as FieldGroupReset, c as _sfc_main$3, n as usePortal, o as _sfc_main$4, r as _sfc_main$5, u as tv, w as useComponentUI, y as useFormField } from "./usePortal-DZb6nPjI.js";
import { r as _sfc_main$6 } from "./Form-DAQH5xxu.js";
import { t as pointerDownOutside } from "./overlay-DvxSEWvQ.js";
import { ssrInterpolate, ssrRenderAttrs, ssrRenderClass, ssrRenderComponent, ssrRenderList, ssrRenderSlot } from "vue/server-renderer";
import { Fragment, computed, createBlock, createCommentVNode, createVNode, mergeProps, nextTick, onMounted, openBlock, ref, renderList, renderSlot, toDisplayString, toHandlers, toRef, unref, useSSRContext, useSlots, useTemplateRef, watch, withCtx } from "vue";
import { defu } from "defu";
import { Primitive, useForwardPropsEmits } from "reka-ui";
import { reactivePick, useVModel } from "@vueuse/core";
import { HoverCard, Popover } from "reka-ui/namespaced";
//#region virtual:nuxt-ui-templates/ui/textarea.ts
var textarea_default = {
	"slots": {
		"root": "relative inline-flex items-center",
		"base": ["w-full rounded-md border-0 appearance-none placeholder:text-dimmed focus:outline-none disabled:cursor-not-allowed disabled:opacity-75", "transition-colors"],
		"leading": "absolute start-0 flex items-start",
		"leadingIcon": "shrink-0 text-dimmed",
		"leadingAvatar": "shrink-0",
		"leadingAvatarSize": "",
		"trailing": "absolute end-0 flex items-start",
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
				"leading": "ps-2 inset-y-1",
				"trailing": "pe-2 inset-y-1",
				"leadingIcon": "size-4",
				"leadingAvatarSize": "3xs",
				"trailingIcon": "size-4"
			},
			"sm": {
				"base": "px-2.5 py-1.5 text-sm/4 gap-1.5",
				"leading": "ps-2.5 inset-y-1.5",
				"trailing": "pe-2.5 inset-y-1.5",
				"leadingIcon": "size-4",
				"leadingAvatarSize": "3xs",
				"trailingIcon": "size-4"
			},
			"md": {
				"base": "px-2.5 py-1.5 text-base/5 gap-1.5",
				"leading": "ps-2.5 inset-y-1.5",
				"trailing": "pe-2.5 inset-y-1.5",
				"leadingIcon": "size-5",
				"leadingAvatarSize": "2xs",
				"trailingIcon": "size-5"
			},
			"lg": {
				"base": "px-3 py-2 text-base/5 gap-2",
				"leading": "ps-3 inset-y-2",
				"trailing": "pe-3 inset-y-2",
				"leadingIcon": "size-5",
				"leadingAvatarSize": "2xs",
				"trailingIcon": "size-5"
			},
			"xl": {
				"base": "px-3 py-2 text-base gap-2",
				"leading": "ps-3 inset-y-2",
				"trailing": "pe-3 inset-y-2",
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
		"type": { "file": "file:me-1.5 file:font-medium file:text-muted file:outline-none" },
		"autoresize": { "true": { "base": "resize-none" } }
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
//#region node_modules/@nuxt/ui/dist/runtime/components/Textarea.vue
var _sfc_main$2 = /* @__PURE__ */ Object.assign({ inheritAttrs: false }, {
	__name: "Textarea",
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
		autofocus: {
			type: Boolean,
			required: false
		},
		autofocusDelay: {
			type: Number,
			required: false,
			default: 0
		},
		autoresize: {
			type: Boolean,
			required: false
		},
		autoresizeDelay: {
			type: Number,
			required: false,
			default: 0
		},
		disabled: {
			type: Boolean,
			required: false
		},
		rows: {
			type: Number,
			required: false,
			default: 3
		},
		maxrows: {
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
		const uiProp = useComponentUI("textarea", props);
		const { emitFormFocus, emitFormBlur, emitFormInput, emitFormChange, size, color, id, name, highlight, disabled, ariaAttrs } = useFormField(props, { deferInputValidation: true });
		const { isLeading, isTrailing, leadingIconName, trailingIconName } = useComponentIcons(props);
		const ui = computed(() => tv({
			extend: tv(textarea_default),
			...appConfig.ui?.textarea || {}
		})({
			color: color.value,
			variant: props.variant,
			size: size?.value,
			loading: props.loading,
			highlight: highlight.value,
			fixed: props.fixed,
			autoresize: props.autoresize,
			leading: isLeading.value || !!props.avatar || !!slots.leading,
			trailing: isTrailing.value || !!slots.trailing
		}));
		const textareaRef = useTemplateRef("textareaRef");
		function updateInput(value) {
			if (props.modelModifiers?.trim && (typeof value === "string" || value === null || value === void 0)) value = value?.trim() ?? null;
			if (props.modelModifiers?.number) value = looseToNumber(value);
			if (props.modelModifiers?.nullable) value ||= null;
			if (props.modelModifiers?.optional && !props.modelModifiers?.nullable && value !== null) value ||= void 0;
			modelValue.value = value;
			emitFormInput();
		}
		function onInput(event) {
			autoResize();
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
			if (props.autofocus) textareaRef.value?.focus();
		}
		function autoResize() {
			if (props.autoresize && textareaRef.value) {
				textareaRef.value.rows = props.rows;
				const overflow = textareaRef.value.style.overflow;
				textareaRef.value.style.overflow = "hidden";
				const styles = window.getComputedStyle(textareaRef.value);
				const padding = Number.parseInt(styles.paddingTop) + Number.parseInt(styles.paddingBottom);
				const lineHeight = Number.parseInt(styles.lineHeight);
				const { scrollHeight } = textareaRef.value;
				const newRows = (scrollHeight - padding) / lineHeight;
				if (newRows > props.rows) textareaRef.value.rows = props.maxrows ? Math.min(newRows, props.maxrows) : newRows;
				textareaRef.value.style.overflow = overflow;
			}
		}
		watch(modelValue, () => {
			nextTick(autoResize);
		});
		onMounted(() => {
			setTimeout(() => {
				autoFocus();
			}, props.autofocusDelay);
			setTimeout(() => {
				autoResize();
			}, props.autoresizeDelay);
		});
		__expose({
			textareaRef,
			autoResize
		});
		return (_ctx, _push, _parent, _attrs) => {
			let _temp0;
			_push(ssrRenderComponent(unref(Primitive), mergeProps({
				as: __props.as,
				"data-slot": "root",
				class: ui.value.root({ class: [unref(uiProp)?.root, props.class] })
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<textarea${ssrRenderAttrs(_temp0 = mergeProps({
							id: unref(id),
							ref_key: "textareaRef",
							ref: textareaRef,
							value: unref(modelValue),
							name: unref(name),
							rows: __props.rows,
							placeholder: __props.placeholder,
							"data-slot": "base",
							class: ui.value.base({ class: unref(uiProp)?.base }),
							disabled: unref(disabled),
							required: __props.required
						}, {
							..._ctx.$attrs,
							...unref(ariaAttrs)
						}), "textarea")}${_scopeId}>${ssrInterpolate("value" in _temp0 ? _temp0.value : "")}</textarea>`);
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
						createVNode("textarea", mergeProps({
							id: unref(id),
							ref_key: "textareaRef",
							ref: textareaRef,
							value: unref(modelValue),
							name: unref(name),
							rows: __props.rows,
							placeholder: __props.placeholder,
							"data-slot": "base",
							class: ui.value.base({ class: unref(uiProp)?.base }),
							disabled: unref(disabled),
							required: __props.required
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
							"value",
							"name",
							"rows",
							"placeholder",
							"disabled",
							"required",
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
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Textarea.vue");
	return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/popover.ts
var popover_default = { "slots": {
	"content": "bg-default shadow-lg rounded-md ring ring-default data-[state=open]:animate-[scale-in_100ms_ease-out] data-[state=closed]:animate-[scale-out_100ms_ease-in] origin-(--reka-popover-content-transform-origin) focus:outline-none pointer-events-auto",
	"arrow": "fill-bg stroke-default"
} };
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Popover.vue
var _sfc_main$1 = {
	__name: "Popover",
	__ssrInlineRender: true,
	props: {
		mode: {
			type: null,
			required: false,
			default: "click"
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
		reference: {
			type: null,
			required: false
		},
		dismissible: {
			type: Boolean,
			required: false,
			default: true
		},
		class: {
			type: null,
			required: false
		},
		ui: {
			type: null,
			required: false
		},
		defaultOpen: {
			type: Boolean,
			required: false
		},
		open: {
			type: Boolean,
			required: false
		},
		modal: {
			type: Boolean,
			required: false
		},
		openDelay: {
			type: Number,
			required: false,
			default: 0
		},
		closeDelay: {
			type: Number,
			required: false,
			default: 0
		}
	},
	emits: ["close:prevent", "update:open"],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const slots = useSlots();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("popover", props);
		const rootProps = useForwardPropsEmits(props.mode === "hover" ? reactivePick(props, "defaultOpen", "open", "openDelay", "closeDelay") : reactivePick(props, "defaultOpen", "open", "modal"), emits);
		const portalProps = usePortal(toRef(() => props.portal));
		const contentProps = toRef(() => defu(props.content, {
			side: "bottom",
			sideOffset: 8,
			collisionPadding: 8
		}));
		const contentEvents = computed(() => {
			if (!props.dismissible) return ["interactOutside", "escapeKeyDown"].reduce((acc, curr) => {
				acc[curr] = (e) => {
					e.preventDefault();
					emits("close:prevent");
				};
				return acc;
			}, {});
			return { pointerDownOutside };
		});
		const arrowProps = toRef(() => defu(props.arrow, { rounded: true }));
		const ui = computed(() => tv({
			extend: tv(popover_default),
			...appConfig.ui?.popover || {}
		})({ side: contentProps.value.side }));
		const Component = computed(() => props.mode === "hover" ? HoverCard : Popover);
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(Component).Root, mergeProps(unref(rootProps), _attrs), {
				default: withCtx(({ open, close }, _push, _parent, _scopeId) => {
					if (_push) {
						if (!!slots.default || !!__props.reference) _push(ssrRenderComponent(unref(Component).Trigger, {
							"as-child": "",
							reference: __props.reference,
							class: props.class
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) ssrRenderSlot(_ctx.$slots, "default", { open }, null, _push, _parent, _scopeId);
								else return [renderSlot(_ctx.$slots, "default", { open })];
							}),
							_: 2
						}, _parent, _scopeId));
						else _push(`<!---->`);
						if ("Anchor" in Component.value && !!slots.anchor) _push(ssrRenderComponent(unref(Component).Anchor, { "as-child": "" }, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) ssrRenderSlot(_ctx.$slots, "anchor", close ? { close } : {}, null, _push, _parent, _scopeId);
								else return [renderSlot(_ctx.$slots, "anchor", close ? { close } : {})];
							}),
							_: 2
						}, _parent, _scopeId));
						else _push(`<!---->`);
						_push(ssrRenderComponent(unref(Component).Portal, unref(portalProps), {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(unref(FieldGroupReset), null, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) _push(ssrRenderComponent(unref(Component).Content, mergeProps(contentProps.value, {
											"data-slot": "content",
											class: ui.value.content({ class: [!slots.default && props.class, unref(uiProp)?.content] })
										}, toHandlers(contentEvents.value)), {
											default: withCtx((_, _push, _parent, _scopeId) => {
												if (_push) {
													ssrRenderSlot(_ctx.$slots, "content", close ? { close } : {}, null, _push, _parent, _scopeId);
													if (!!__props.arrow) _push(ssrRenderComponent(unref(Component).Arrow, mergeProps(arrowProps.value, {
														"data-slot": "arrow",
														class: ui.value.arrow({ class: unref(uiProp)?.arrow })
													}), null, _parent, _scopeId));
													else _push(`<!---->`);
												} else return [renderSlot(_ctx.$slots, "content", close ? { close } : {}), !!__props.arrow ? (openBlock(), createBlock(unref(Component).Arrow, mergeProps({ key: 0 }, arrowProps.value, {
													"data-slot": "arrow",
													class: ui.value.arrow({ class: unref(uiProp)?.arrow })
												}), null, 16, ["class"])) : createCommentVNode("", true)];
											}),
											_: 2
										}, _parent, _scopeId));
										else return [createVNode(unref(Component).Content, mergeProps(contentProps.value, {
											"data-slot": "content",
											class: ui.value.content({ class: [!slots.default && props.class, unref(uiProp)?.content] })
										}, toHandlers(contentEvents.value)), {
											default: withCtx(() => [renderSlot(_ctx.$slots, "content", close ? { close } : {}), !!__props.arrow ? (openBlock(), createBlock(unref(Component).Arrow, mergeProps({ key: 0 }, arrowProps.value, {
												"data-slot": "arrow",
												class: ui.value.arrow({ class: unref(uiProp)?.arrow })
											}), null, 16, ["class"])) : createCommentVNode("", true)]),
											_: 2
										}, 1040, ["class"])];
									}),
									_: 2
								}, _parent, _scopeId));
								else return [createVNode(unref(FieldGroupReset), null, {
									default: withCtx(() => [createVNode(unref(Component).Content, mergeProps(contentProps.value, {
										"data-slot": "content",
										class: ui.value.content({ class: [!slots.default && props.class, unref(uiProp)?.content] })
									}, toHandlers(contentEvents.value)), {
										default: withCtx(() => [renderSlot(_ctx.$slots, "content", close ? { close } : {}), !!__props.arrow ? (openBlock(), createBlock(unref(Component).Arrow, mergeProps({ key: 0 }, arrowProps.value, {
											"data-slot": "arrow",
											class: ui.value.arrow({ class: unref(uiProp)?.arrow })
										}), null, 16, ["class"])) : createCommentVNode("", true)]),
										_: 2
									}, 1040, ["class"])]),
									_: 2
								}, 1024)];
							}),
							_: 2
						}, _parent, _scopeId));
					} else return [
						!!slots.default || !!__props.reference ? (openBlock(), createBlock(unref(Component).Trigger, {
							key: 0,
							"as-child": "",
							reference: __props.reference,
							class: props.class
						}, {
							default: withCtx(() => [renderSlot(_ctx.$slots, "default", { open })]),
							_: 2
						}, 1032, ["reference", "class"])) : createCommentVNode("", true),
						"Anchor" in Component.value && !!slots.anchor ? (openBlock(), createBlock(unref(Component).Anchor, {
							key: 1,
							"as-child": ""
						}, {
							default: withCtx(() => [renderSlot(_ctx.$slots, "anchor", close ? { close } : {})]),
							_: 2
						}, 1024)) : createCommentVNode("", true),
						createVNode(unref(Component).Portal, unref(portalProps), {
							default: withCtx(() => [createVNode(unref(FieldGroupReset), null, {
								default: withCtx(() => [createVNode(unref(Component).Content, mergeProps(contentProps.value, {
									"data-slot": "content",
									class: ui.value.content({ class: [!slots.default && props.class, unref(uiProp)?.content] })
								}, toHandlers(contentEvents.value)), {
									default: withCtx(() => [renderSlot(_ctx.$slots, "content", close ? { close } : {}), !!__props.arrow ? (openBlock(), createBlock(unref(Component).Arrow, mergeProps({ key: 0 }, arrowProps.value, {
										"data-slot": "arrow",
										class: ui.value.arrow({ class: unref(uiProp)?.arrow })
									}), null, 16, ["class"])) : createCommentVNode("", true)]),
									_: 2
								}, 1040, ["class"])]),
								_: 2
							}, 1024)]),
							_: 2
						}, 1040)
					];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Popover.vue");
	return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
//#endregion
//#region resources/js/Components/Forms/PhoneInput.vue
var _sfc_main = {
	__name: "PhoneInput",
	__ssrInlineRender: true,
	props: {
		modelValue: {
			type: Object,
			default: () => ({
				extension: "",
				number: ""
			})
		},
		countries: {
			type: Array,
			default: () => [
				{
					name: "France",
					extension: "33",
					flag: "🇫🇷"
				},
				{
					name: "Belgique",
					extension: "32",
					flag: "🇧🇪"
				},
				{
					name: "Suisse",
					extension: "41",
					flag: "🇨🇭"
				},
				{
					name: "Luxembourg",
					extension: "352",
					flag: "🇱🇺"
				},
				{
					name: "Canada",
					extension: "1",
					flag: "🇨🇦"
				},
				{
					name: "États-Unis",
					extension: "1",
					flag: "🇺🇸"
				},
				{
					name: "Royaume-Uni",
					extension: "44",
					flag: "🇬🇧"
				},
				{
					name: "Allemagne",
					extension: "49",
					flag: "🇩🇪"
				},
				{
					name: "Espagne",
					extension: "34",
					flag: "🇪🇸"
				},
				{
					name: "Italie",
					extension: "39",
					flag: "🇮🇹"
				},
				{
					name: "Portugal",
					extension: "351",
					flag: "🇵🇹"
				},
				{
					name: "Maroc",
					extension: "212",
					flag: "🇲🇦"
				},
				{
					name: "Algérie",
					extension: "213",
					flag: "🇩🇿"
				},
				{
					name: "Tunisie",
					extension: "216",
					flag: "🇹🇳"
				}
			]
		},
		size: {
			type: String,
			default: "md",
			validator: (value) => [
				"xs",
				"sm",
				"md",
				"lg",
				"xl"
			].includes(value)
		},
		label: {
			type: String,
			default: ""
		},
		placeholder: {
			type: String,
			default: "Numéro"
		},
		disabled: {
			type: Boolean,
			default: false
		}
	},
	emits: ["update:modelValue"],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emit = __emit;
		const extension = ref(props.modelValue?.extension ?? "");
		const number = ref(props.modelValue?.number ?? "");
		const countryOpen = ref(false);
		const countrySearch = ref("");
		const selectedCountry = computed(() => {
			return props.countries.find((country) => country.extension === extension.value) ?? null;
		});
		const filteredCountries = computed(() => {
			const search = countrySearch.value.trim().toLowerCase();
			if (!search) return props.countries;
			return props.countries.filter((country) => {
				return country.name.toLowerCase().includes(search) || country.extension.includes(search) || `+${country.extension}`.includes(search);
			});
		});
		const wrapperClasses = computed(() => {
			return {
				xs: "rounded-sm",
				sm: "rounded-md",
				md: "rounded-md",
				lg: "rounded-lg",
				xl: "rounded-lg"
			}[props.size];
		});
		const countryButtonClasses = computed(() => {
			return {
				xs: "w-24 px-1 text-xs",
				sm: "w-28 px-2 text-sm",
				md: "w-32 px-2.5 text-sm",
				lg: "w-36 px-3 text-base",
				xl: "w-40 px-3.5 text-base"
			}[props.size];
		});
		const countryItemClasses = computed(() => {
			return {
				xs: "gap-2 px-2 py-1 text-xs",
				sm: "gap-2 px-2.5 py-1.5 text-sm",
				md: "gap-3 px-3 py-1.5 text-sm",
				lg: "gap-3 px-3.5 py-2 text-base",
				xl: "gap-3 px-4 py-2.5 text-base"
			}[props.size];
		});
		const fieldHeightClasses = computed(() => {
			return {
				xs: "h-7",
				sm: "h-8",
				md: "h-9",
				lg: "h-10",
				xl: "h-11"
			}[props.size];
		});
		function emitValue() {
			emit("update:modelValue", {
				extension: extension.value,
				number: number.value,
				full: extension.value && number.value ? `+${extension.value}${number.value}` : ""
			});
		}
		function selectCountry(country) {
			extension.value = country.extension;
			countryOpen.value = false;
			countrySearch.value = "";
			emitValue();
		}
		function updateNumber(value) {
			number.value = String(value ?? "");
			emitValue();
		}
		watch(() => props.modelValue, (value) => {
			const nextExtension = value?.extension ?? "";
			const nextNumber = value?.number ?? "";
			if (nextExtension !== extension.value) extension.value = nextExtension;
			if (nextNumber !== number.value) number.value = nextNumber;
		}, { deep: true });
		return (_ctx, _push, _parent, _attrs) => {
			const _component_UPopover = _sfc_main$1;
			const _component_UButton = _sfc_main$5;
			const _component_UInput = _sfc_main$6;
			_push(`<div${ssrRenderAttrs(mergeProps({ class: "flex w-full flex-col gap-1" }, _attrs))}>`);
			if (__props.label) _push(`<label class="text-sm font-medium text-white/70">${ssrInterpolate(__props.label)}</label>`);
			else _push(`<!---->`);
			_push(`<div class="${ssrRenderClass([[
				wrapperClasses.value,
				fieldHeightClasses.value,
				{ "opacity-60": __props.disabled }
			], "flex w-full overflow-hidden rounded-md border border-white/10 focus-within:ring-2 focus-within:ring-primary/60"])}">`);
			_push(ssrRenderComponent(_component_UPopover, {
				open: countryOpen.value,
				"onUpdate:open": ($event) => countryOpen.value = $event
			}, {
				content: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="w-80 space-y-2 p-2"${_scopeId}>`);
						_push(ssrRenderComponent(_component_UInput, {
							size: __props.size,
							modelValue: countrySearch.value,
							"onUpdate:modelValue": ($event) => countrySearch.value = $event,
							placeholder: "Rechercher un pays ou indicatif...",
							icon: "i-lucide-search",
							autofocus: ""
						}, null, _parent, _scopeId));
						_push(`<div class="max-h-72 overflow-y-auto"${_scopeId}><!--[-->`);
						ssrRenderList(filteredCountries.value, (country) => {
							_push(`<button type="button" class="${ssrRenderClass([countryItemClasses.value, "flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm hover:bg-white/10"])}"${_scopeId}><span class="text-lg"${_scopeId}>${ssrInterpolate(country.flag)}</span><span class="min-w-0 flex-1"${_scopeId}><span class="block truncate text-white"${_scopeId}>${ssrInterpolate(country.name)}</span><span class="text-xs text-white/50"${_scopeId}> +${ssrInterpolate(country.extension)}</span></span></button>`);
						});
						_push(`<!--]-->`);
						if (filteredCountries.value.length === 0) _push(`<p class="px-3 py-4 text-center text-sm text-white/50"${_scopeId}> Aucun résultat </p>`);
						else _push(`<!---->`);
						_push(`</div></div>`);
					} else return [createVNode("div", { class: "w-80 space-y-2 p-2" }, [createVNode(_component_UInput, {
						size: __props.size,
						modelValue: countrySearch.value,
						"onUpdate:modelValue": ($event) => countrySearch.value = $event,
						placeholder: "Rechercher un pays ou indicatif...",
						icon: "i-lucide-search",
						autofocus: ""
					}, null, 8, [
						"size",
						"modelValue",
						"onUpdate:modelValue"
					]), createVNode("div", { class: "max-h-72 overflow-y-auto" }, [(openBlock(true), createBlock(Fragment, null, renderList(filteredCountries.value, (country) => {
						return openBlock(), createBlock("button", {
							key: `${country.name}-${country.extension}`,
							type: "button",
							class: ["flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm hover:bg-white/10", countryItemClasses.value],
							onClick: ($event) => selectCountry(country)
						}, [createVNode("span", { class: "text-lg" }, toDisplayString(country.flag), 1), createVNode("span", { class: "min-w-0 flex-1" }, [createVNode("span", { class: "block truncate text-white" }, toDisplayString(country.name), 1), createVNode("span", { class: "text-xs text-white/50" }, " +" + toDisplayString(country.extension), 1)])], 10, ["onClick"]);
					}), 128)), filteredCountries.value.length === 0 ? (openBlock(), createBlock("p", {
						key: 0,
						class: "px-3 py-4 text-center text-sm text-white/50"
					}, " Aucun résultat ")) : createCommentVNode("", true)])])];
				}),
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(ssrRenderComponent(_component_UButton, {
						type: "button",
						color: "neutral",
						variant: "none",
						disabled: __props.disabled,
						class: ["h-full w-1/3 justify-between rounded-none border-0 bg-transparent px-3", countryButtonClasses.value],
						"trailing-icon": "i-lucide-chevron-down"
					}, {
						default: withCtx((_, _push, _parent, _scopeId) => {
							if (_push) {
								_push(`<span class="flex items-center gap-2"${_scopeId}>`);
								if (selectedCountry.value) _push(`<span${_scopeId}>${ssrInterpolate(selectedCountry.value.flag)}</span>`);
								else _push(`<!---->`);
								_push(`<span${_scopeId}>${ssrInterpolate(extension.value ? `+${extension.value}` : "+...")}</span></span>`);
							} else return [createVNode("span", { class: "flex items-center gap-2" }, [selectedCountry.value ? (openBlock(), createBlock("span", { key: 0 }, toDisplayString(selectedCountry.value.flag), 1)) : createCommentVNode("", true), createVNode("span", null, toDisplayString(extension.value ? `+${extension.value}` : "+..."), 1)])];
						}),
						_: 1
					}, _parent, _scopeId));
					else return [createVNode(_component_UButton, {
						type: "button",
						color: "neutral",
						variant: "none",
						disabled: __props.disabled,
						class: ["h-full w-1/3 justify-between rounded-none border-0 bg-transparent px-3", countryButtonClasses.value],
						"trailing-icon": "i-lucide-chevron-down"
					}, {
						default: withCtx(() => [createVNode("span", { class: "flex items-center gap-2" }, [selectedCountry.value ? (openBlock(), createBlock("span", { key: 0 }, toDisplayString(selectedCountry.value.flag), 1)) : createCommentVNode("", true), createVNode("span", null, toDisplayString(extension.value ? `+${extension.value}` : "+..."), 1)])]),
						_: 1
					}, 8, ["disabled", "class"])];
				}),
				_: 1
			}, _parent));
			_push(`<div class="w-px bg-white/10"></div>`);
			_push(ssrRenderComponent(_component_UInput, {
				"model-value": number.value,
				variant: "none",
				size: __props.size,
				maxLength: "10",
				type: "tel",
				inputmode: "tel",
				placeholder: __props.placeholder,
				disabled: __props.disabled,
				class: "min-w-0 flex-1 h-full",
				ui: {
					root: "h-full",
					base: "rounded-none border-0 bg-transparent h-full"
				},
				"onUpdate:modelValue": updateNumber
			}, null, _parent));
			_push(`</div></div>`);
		};
	}
};
var _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Forms/PhoneInput.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
//#endregion
export { _sfc_main$1 as n, _sfc_main$2 as r, _sfc_main as t };

//# sourceMappingURL=PhoneInput-aIWeFFh9.js.map