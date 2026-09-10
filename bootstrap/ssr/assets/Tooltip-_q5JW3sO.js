import { F as useAppConfig, P as useKbd, b as FieldGroupReset, n as usePortal, u as tv, w as useComponentUI } from "./usePortal-DZb6nPjI.js";
import { ssrInterpolate, ssrRenderClass, ssrRenderComponent, ssrRenderList, ssrRenderSlot } from "vue/server-renderer";
import { Fragment, computed, createBlock, createCommentVNode, createTextVNode, createVNode, mergeProps, openBlock, renderList, renderSlot, toDisplayString, toRef, unref, useSSRContext, useSlots, withCtx } from "vue";
import { defu } from "defu";
import { Primitive, TooltipArrow, TooltipContent, TooltipPortal, TooltipRoot, TooltipTrigger, injectTooltipProviderContext, useForwardPropsEmits } from "reka-ui";
import { reactivePick } from "@vueuse/core";
//#region virtual:nuxt-ui-templates/ui/kbd.ts
var kbd_default = {
	"base": "inline-flex items-center justify-center px-1 rounded-sm font-medium font-sans uppercase",
	"variants": {
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
			"subtle": ""
		},
		"size": {
			"sm": "h-4 min-w-[16px] text-[10px]",
			"md": "h-5 min-w-[20px] text-[11px]",
			"lg": "h-6 min-w-[24px] text-[12px]"
		}
	},
	"compoundVariants": [
		{
			"color": "primary",
			"variant": "solid",
			"class": "text-inverted bg-primary"
		},
		{
			"color": "secondary",
			"variant": "solid",
			"class": "text-inverted bg-secondary"
		},
		{
			"color": "success",
			"variant": "solid",
			"class": "text-inverted bg-success"
		},
		{
			"color": "info",
			"variant": "solid",
			"class": "text-inverted bg-info"
		},
		{
			"color": "warning",
			"variant": "solid",
			"class": "text-inverted bg-warning"
		},
		{
			"color": "error",
			"variant": "solid",
			"class": "text-inverted bg-error"
		},
		{
			"color": "primary",
			"variant": "outline",
			"class": "ring ring-inset ring-primary/50 text-primary"
		},
		{
			"color": "secondary",
			"variant": "outline",
			"class": "ring ring-inset ring-secondary/50 text-secondary"
		},
		{
			"color": "success",
			"variant": "outline",
			"class": "ring ring-inset ring-success/50 text-success"
		},
		{
			"color": "info",
			"variant": "outline",
			"class": "ring ring-inset ring-info/50 text-info"
		},
		{
			"color": "warning",
			"variant": "outline",
			"class": "ring ring-inset ring-warning/50 text-warning"
		},
		{
			"color": "error",
			"variant": "outline",
			"class": "ring ring-inset ring-error/50 text-error"
		},
		{
			"color": "primary",
			"variant": "soft",
			"class": "text-primary bg-primary/10"
		},
		{
			"color": "secondary",
			"variant": "soft",
			"class": "text-secondary bg-secondary/10"
		},
		{
			"color": "success",
			"variant": "soft",
			"class": "text-success bg-success/10"
		},
		{
			"color": "info",
			"variant": "soft",
			"class": "text-info bg-info/10"
		},
		{
			"color": "warning",
			"variant": "soft",
			"class": "text-warning bg-warning/10"
		},
		{
			"color": "error",
			"variant": "soft",
			"class": "text-error bg-error/10"
		},
		{
			"color": "primary",
			"variant": "subtle",
			"class": "text-primary ring ring-inset ring-primary/25 bg-primary/10"
		},
		{
			"color": "secondary",
			"variant": "subtle",
			"class": "text-secondary ring ring-inset ring-secondary/25 bg-secondary/10"
		},
		{
			"color": "success",
			"variant": "subtle",
			"class": "text-success ring ring-inset ring-success/25 bg-success/10"
		},
		{
			"color": "info",
			"variant": "subtle",
			"class": "text-info ring ring-inset ring-info/25 bg-info/10"
		},
		{
			"color": "warning",
			"variant": "subtle",
			"class": "text-warning ring ring-inset ring-warning/25 bg-warning/10"
		},
		{
			"color": "error",
			"variant": "subtle",
			"class": "text-error ring ring-inset ring-error/25 bg-error/10"
		},
		{
			"color": "neutral",
			"variant": "solid",
			"class": "text-inverted bg-inverted"
		},
		{
			"color": "neutral",
			"variant": "outline",
			"class": "ring ring-inset ring-accented text-default bg-default"
		},
		{
			"color": "neutral",
			"variant": "soft",
			"class": "text-default bg-elevated"
		},
		{
			"color": "neutral",
			"variant": "subtle",
			"class": "ring ring-inset ring-accented text-default bg-elevated"
		}
	],
	"defaultVariants": {
		"variant": "outline",
		"color": "neutral",
		"size": "md"
	}
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Kbd.vue
var _sfc_main$1 = {
	__name: "Kbd",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false,
			default: "kbd"
		},
		value: {
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
		const { getKbdKey } = useKbd();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("kbd", props);
		const ui = computed(() => tv({
			extend: tv(kbd_default),
			...appConfig.ui?.kbd || {}
		}));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(Primitive), mergeProps({
				as: __props.as,
				class: ui.value({
					class: [unref(uiProp)?.base, props.class],
					color: props.color,
					variant: props.variant,
					size: props.size
				})
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) ssrRenderSlot(_ctx.$slots, "default", {}, () => {
						_push(`${ssrInterpolate(unref(getKbdKey)(__props.value))}`);
					}, _push, _parent, _scopeId);
					else return [renderSlot(_ctx.$slots, "default", {}, () => [createTextVNode(toDisplayString(unref(getKbdKey)(__props.value)), 1)])];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Kbd.vue");
	return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/tooltip.ts
var tooltip_default = { "slots": {
	"content": "flex items-center gap-1 bg-default text-highlighted shadow-sm rounded-sm ring ring-default h-6 px-2.5 py-1 text-xs select-none data-[state=delayed-open]:animate-[scale-in_100ms_ease-out] data-[state=closed]:animate-[scale-out_100ms_ease-in] origin-(--reka-tooltip-content-transform-origin) pointer-events-auto",
	"arrow": "fill-bg stroke-default",
	"text": "truncate",
	"kbds": "hidden lg:inline-flex items-center shrink-0 gap-0.5 not-first-of-type:before:content-['·'] not-first-of-type:before:me-0.5",
	"kbdsSize": "sm"
} };
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Tooltip.vue
var _sfc_main = {
	__name: "Tooltip",
	__ssrInlineRender: true,
	props: {
		text: {
			type: String,
			required: false
		},
		kbds: {
			type: Array,
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
		reference: {
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
		defaultOpen: {
			type: Boolean,
			required: false
		},
		open: {
			type: Boolean,
			required: false
		},
		delayDuration: {
			type: Number,
			required: false
		},
		disableHoverableContent: {
			type: Boolean,
			required: false
		},
		disableClosingTrigger: {
			type: Boolean,
			required: false
		},
		disabled: {
			type: Boolean,
			required: false
		},
		ignoreNonKeyboardFocus: {
			type: Boolean,
			required: false
		}
	},
	emits: ["update:open"],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const slots = useSlots();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("tooltip", props);
		const providerContext = injectTooltipProviderContext();
		const rootProps = useForwardPropsEmits(reactivePick(props, "defaultOpen", "open", "delayDuration", "disableHoverableContent", "disableClosingTrigger", "ignoreNonKeyboardFocus"), emits);
		const portalProps = usePortal(toRef(() => props.portal));
		const contentProps = toRef(() => defu(props.content, providerContext.content.value, {
			side: "bottom",
			sideOffset: 8,
			collisionPadding: 8
		}));
		const arrowProps = toRef(() => defu(props.arrow, { rounded: true }));
		const ui = computed(() => tv({
			extend: tv(tooltip_default),
			...appConfig.ui?.tooltip || {}
		})({ side: contentProps.value.side }));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(TooltipRoot), mergeProps(unref(rootProps), { disabled: !(__props.text || __props.kbds?.length || !!slots.content) || props.disabled }, _attrs), {
				default: withCtx(({ open }, _push, _parent, _scopeId) => {
					if (_push) {
						if (!!slots.default || !!__props.reference) _push(ssrRenderComponent(unref(TooltipTrigger), mergeProps(_ctx.$attrs, {
							"as-child": "",
							reference: __props.reference,
							class: props.class
						}), {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) ssrRenderSlot(_ctx.$slots, "default", { open }, null, _push, _parent, _scopeId);
								else return [renderSlot(_ctx.$slots, "default", { open })];
							}),
							_: 2
						}, _parent, _scopeId));
						else _push(`<!---->`);
						_push(ssrRenderComponent(unref(TooltipPortal), unref(portalProps), {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(unref(FieldGroupReset), null, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) _push(ssrRenderComponent(unref(TooltipContent), mergeProps(contentProps.value, {
											"data-slot": "content",
											class: ui.value.content({ class: [!slots.default && props.class, unref(uiProp)?.content] })
										}), {
											default: withCtx((_, _push, _parent, _scopeId) => {
												if (_push) {
													ssrRenderSlot(_ctx.$slots, "content", { ui: ui.value }, () => {
														if (__props.text) _push(`<span data-slot="text" class="${ssrRenderClass(ui.value.text({ class: unref(uiProp)?.text }))}"${_scopeId}>${ssrInterpolate(__props.text)}</span>`);
														else _push(`<!---->`);
														if (__props.kbds?.length) {
															_push(`<span data-slot="kbds" class="${ssrRenderClass(ui.value.kbds({ class: unref(uiProp)?.kbds }))}"${_scopeId}><!--[-->`);
															ssrRenderList(__props.kbds, (kbd, index) => {
																_push(ssrRenderComponent(_sfc_main$1, mergeProps({
																	key: index,
																	size: unref(uiProp)?.kbdsSize || ui.value.kbdsSize()
																}, { ref_for: true }, typeof kbd === "string" ? { value: kbd } : kbd), null, _parent, _scopeId));
															});
															_push(`<!--]--></span>`);
														} else _push(`<!---->`);
													}, _push, _parent, _scopeId);
													if (!!__props.arrow) _push(ssrRenderComponent(unref(TooltipArrow), mergeProps(arrowProps.value, {
														"data-slot": "arrow",
														class: ui.value.arrow({ class: unref(uiProp)?.arrow })
													}), null, _parent, _scopeId));
													else _push(`<!---->`);
												} else return [renderSlot(_ctx.$slots, "content", { ui: ui.value }, () => [__props.text ? (openBlock(), createBlock("span", {
													key: 0,
													"data-slot": "text",
													class: ui.value.text({ class: unref(uiProp)?.text })
												}, toDisplayString(__props.text), 3)) : createCommentVNode("", true), __props.kbds?.length ? (openBlock(), createBlock("span", {
													key: 1,
													"data-slot": "kbds",
													class: ui.value.kbds({ class: unref(uiProp)?.kbds })
												}, [(openBlock(true), createBlock(Fragment, null, renderList(__props.kbds, (kbd, index) => {
													return openBlock(), createBlock(_sfc_main$1, mergeProps({
														key: index,
														size: unref(uiProp)?.kbdsSize || ui.value.kbdsSize()
													}, { ref_for: true }, typeof kbd === "string" ? { value: kbd } : kbd), null, 16, ["size"]);
												}), 128))], 2)) : createCommentVNode("", true)]), !!__props.arrow ? (openBlock(), createBlock(unref(TooltipArrow), mergeProps({ key: 0 }, arrowProps.value, {
													"data-slot": "arrow",
													class: ui.value.arrow({ class: unref(uiProp)?.arrow })
												}), null, 16, ["class"])) : createCommentVNode("", true)];
											}),
											_: 2
										}, _parent, _scopeId));
										else return [createVNode(unref(TooltipContent), mergeProps(contentProps.value, {
											"data-slot": "content",
											class: ui.value.content({ class: [!slots.default && props.class, unref(uiProp)?.content] })
										}), {
											default: withCtx(() => [renderSlot(_ctx.$slots, "content", { ui: ui.value }, () => [__props.text ? (openBlock(), createBlock("span", {
												key: 0,
												"data-slot": "text",
												class: ui.value.text({ class: unref(uiProp)?.text })
											}, toDisplayString(__props.text), 3)) : createCommentVNode("", true), __props.kbds?.length ? (openBlock(), createBlock("span", {
												key: 1,
												"data-slot": "kbds",
												class: ui.value.kbds({ class: unref(uiProp)?.kbds })
											}, [(openBlock(true), createBlock(Fragment, null, renderList(__props.kbds, (kbd, index) => {
												return openBlock(), createBlock(_sfc_main$1, mergeProps({
													key: index,
													size: unref(uiProp)?.kbdsSize || ui.value.kbdsSize()
												}, { ref_for: true }, typeof kbd === "string" ? { value: kbd } : kbd), null, 16, ["size"]);
											}), 128))], 2)) : createCommentVNode("", true)]), !!__props.arrow ? (openBlock(), createBlock(unref(TooltipArrow), mergeProps({ key: 0 }, arrowProps.value, {
												"data-slot": "arrow",
												class: ui.value.arrow({ class: unref(uiProp)?.arrow })
											}), null, 16, ["class"])) : createCommentVNode("", true)]),
											_: 3
										}, 16, ["class"])];
									}),
									_: 2
								}, _parent, _scopeId));
								else return [createVNode(unref(FieldGroupReset), null, {
									default: withCtx(() => [createVNode(unref(TooltipContent), mergeProps(contentProps.value, {
										"data-slot": "content",
										class: ui.value.content({ class: [!slots.default && props.class, unref(uiProp)?.content] })
									}), {
										default: withCtx(() => [renderSlot(_ctx.$slots, "content", { ui: ui.value }, () => [__props.text ? (openBlock(), createBlock("span", {
											key: 0,
											"data-slot": "text",
											class: ui.value.text({ class: unref(uiProp)?.text })
										}, toDisplayString(__props.text), 3)) : createCommentVNode("", true), __props.kbds?.length ? (openBlock(), createBlock("span", {
											key: 1,
											"data-slot": "kbds",
											class: ui.value.kbds({ class: unref(uiProp)?.kbds })
										}, [(openBlock(true), createBlock(Fragment, null, renderList(__props.kbds, (kbd, index) => {
											return openBlock(), createBlock(_sfc_main$1, mergeProps({
												key: index,
												size: unref(uiProp)?.kbdsSize || ui.value.kbdsSize()
											}, { ref_for: true }, typeof kbd === "string" ? { value: kbd } : kbd), null, 16, ["size"]);
										}), 128))], 2)) : createCommentVNode("", true)]), !!__props.arrow ? (openBlock(), createBlock(unref(TooltipArrow), mergeProps({ key: 0 }, arrowProps.value, {
											"data-slot": "arrow",
											class: ui.value.arrow({ class: unref(uiProp)?.arrow })
										}), null, 16, ["class"])) : createCommentVNode("", true)]),
										_: 3
									}, 16, ["class"])]),
									_: 3
								})];
							}),
							_: 2
						}, _parent, _scopeId));
					} else return [!!slots.default || !!__props.reference ? (openBlock(), createBlock(unref(TooltipTrigger), mergeProps({ key: 0 }, _ctx.$attrs, {
						"as-child": "",
						reference: __props.reference,
						class: props.class
					}), {
						default: withCtx(() => [renderSlot(_ctx.$slots, "default", { open })]),
						_: 2
					}, 1040, ["reference", "class"])) : createCommentVNode("", true), createVNode(unref(TooltipPortal), unref(portalProps), {
						default: withCtx(() => [createVNode(unref(FieldGroupReset), null, {
							default: withCtx(() => [createVNode(unref(TooltipContent), mergeProps(contentProps.value, {
								"data-slot": "content",
								class: ui.value.content({ class: [!slots.default && props.class, unref(uiProp)?.content] })
							}), {
								default: withCtx(() => [renderSlot(_ctx.$slots, "content", { ui: ui.value }, () => [__props.text ? (openBlock(), createBlock("span", {
									key: 0,
									"data-slot": "text",
									class: ui.value.text({ class: unref(uiProp)?.text })
								}, toDisplayString(__props.text), 3)) : createCommentVNode("", true), __props.kbds?.length ? (openBlock(), createBlock("span", {
									key: 1,
									"data-slot": "kbds",
									class: ui.value.kbds({ class: unref(uiProp)?.kbds })
								}, [(openBlock(true), createBlock(Fragment, null, renderList(__props.kbds, (kbd, index) => {
									return openBlock(), createBlock(_sfc_main$1, mergeProps({
										key: index,
										size: unref(uiProp)?.kbdsSize || ui.value.kbdsSize()
									}, { ref_for: true }, typeof kbd === "string" ? { value: kbd } : kbd), null, 16, ["size"]);
								}), 128))], 2)) : createCommentVNode("", true)]), !!__props.arrow ? (openBlock(), createBlock(unref(TooltipArrow), mergeProps({ key: 0 }, arrowProps.value, {
									"data-slot": "arrow",
									class: ui.value.arrow({ class: unref(uiProp)?.arrow })
								}), null, 16, ["class"])) : createCommentVNode("", true)]),
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
};
var _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Tooltip.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
//#endregion
export { _sfc_main$1 as n, _sfc_main as t };

//# sourceMappingURL=Tooltip-_q5JW3sO.js.map