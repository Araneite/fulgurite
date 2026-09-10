import { D as useLocale, E as localeContextInjectionKey, F as useAppConfig, N as omit, c as _sfc_main$5, n as usePortal, o as _sfc_main$4, r as _sfc_main$6, t as portalTargetInjectionKey, u as tv, w as useComponentUI } from "./usePortal-DZb6nPjI.js";
import { n as useToast, t as toastMaxInjectionKey } from "./useToast-itvNvMq-.js";
import { t as _sfc_main$7 } from "./Progress-Cl7OHCpt.js";
import { ssrInterpolate, ssrRenderClass, ssrRenderComponent, ssrRenderList, ssrRenderSlot, ssrRenderVNode } from "vue/server-renderer";
import { Fragment, computed, createBlock, createCommentVNode, createTextVNode, createVNode, markRaw, mergeProps, onMounted, openBlock, provide, reactive, ref, renderList, renderSlot, resolveDynamicComponent, shallowReactive, toDisplayString, toRef, unref, useId, useSSRContext, useSlots, useTemplateRef, withCtx, withModifiers } from "vue";
import { ConfigProvider, ToastAction, ToastClose, ToastDescription, ToastPortal, ToastProvider, ToastRoot, ToastTitle, ToastViewport, TooltipProvider, useForwardProps, useForwardPropsEmits } from "reka-ui";
import { createSharedComposable, reactivePick } from "@vueuse/core";
//#region virtual:nuxt-ui-templates/ui/toast.ts
var toast_default = {
	"slots": {
		"root": "relative group overflow-hidden bg-default shadow-lg rounded-lg ring ring-default p-4 flex gap-2.5 focus:outline-none",
		"wrapper": "w-0 flex-1 flex flex-col",
		"title": "text-sm font-medium text-highlighted",
		"description": "text-sm text-muted",
		"icon": "shrink-0 size-5",
		"avatar": "shrink-0",
		"avatarSize": "2xl",
		"actions": "flex gap-1.5 shrink-0",
		"progress": "absolute inset-x-0 bottom-0",
		"close": "p-0"
	},
	"variants": {
		"color": {
			"primary": {
				"root": "focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary",
				"icon": "text-primary"
			},
			"secondary": {
				"root": "focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-secondary",
				"icon": "text-secondary"
			},
			"success": {
				"root": "focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-success",
				"icon": "text-success"
			},
			"info": {
				"root": "focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-info",
				"icon": "text-info"
			},
			"warning": {
				"root": "focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-warning",
				"icon": "text-warning"
			},
			"error": {
				"root": "focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-error",
				"icon": "text-error"
			},
			"neutral": {
				"root": "focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-inverted",
				"icon": "text-highlighted"
			}
		},
		"orientation": {
			"horizontal": {
				"root": "items-center",
				"actions": "items-center"
			},
			"vertical": {
				"root": "items-start",
				"actions": "items-start mt-2.5"
			}
		},
		"title": { "true": { "description": "mt-1" } }
	},
	"defaultVariants": { "color": "primary" }
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Toast.vue
var _sfc_main$3 = {
	__name: "Toast",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false
		},
		title: {
			type: [
				String,
				Object,
				Function
			],
			required: false
		},
		description: {
			type: [
				String,
				Object,
				Function
			],
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
		color: {
			type: null,
			required: false
		},
		orientation: {
			type: null,
			required: false,
			default: "vertical"
		},
		close: {
			type: [Boolean, Object],
			required: false,
			default: true
		},
		closeIcon: {
			type: null,
			required: false
		},
		actions: {
			type: Array,
			required: false
		},
		progress: {
			type: [Boolean, Object],
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
		defaultOpen: {
			type: Boolean,
			required: false
		},
		open: {
			type: Boolean,
			required: false
		},
		type: {
			type: String,
			required: false
		},
		duration: {
			type: Number,
			required: false
		}
	},
	emits: [
		"escapeKeyDown",
		"pause",
		"resume",
		"swipeStart",
		"swipeMove",
		"swipeCancel",
		"swipeEnd",
		"update:open"
	],
	setup(__props, { expose: __expose, emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const slots = useSlots();
		const { t } = useLocale();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("toast", props);
		const rootProps = useForwardPropsEmits(reactivePick(props, "as", "defaultOpen", "open", "duration", "type"), emits);
		const ui = computed(() => tv({
			extend: tv(toast_default),
			...appConfig.ui?.toast || {}
		})({
			color: props.color,
			orientation: props.orientation,
			title: !!props.title || !!slots.title
		}));
		const rootRef = useTemplateRef("rootRef");
		const height = ref(0);
		onMounted(() => {
			if (!rootRef.value?.$el?.getBoundingClientRect) return;
			height.value = rootRef.value.$el.getBoundingClientRect().height;
		});
		__expose({ height });
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(ToastRoot), mergeProps({
				ref_key: "rootRef",
				ref: rootRef
			}, unref(rootProps), {
				"data-orientation": __props.orientation,
				"data-slot": "root",
				class: ui.value.root({ class: [unref(uiProp)?.root, props.class] }),
				style: { "--height": height.value }
			}, _attrs), {
				default: withCtx(({ remaining, duration, open }, _push, _parent, _scopeId) => {
					if (_push) {
						ssrRenderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => {
							if (__props.avatar) _push(ssrRenderComponent(_sfc_main$4, mergeProps({ size: unref(uiProp)?.avatarSize || ui.value.avatarSize() }, __props.avatar, {
								"data-slot": "avatar",
								class: ui.value.avatar({ class: unref(uiProp)?.avatar })
							}), null, _parent, _scopeId));
							else if (__props.icon) _push(ssrRenderComponent(_sfc_main$5, {
								name: __props.icon,
								"data-slot": "icon",
								class: ui.value.icon({ class: unref(uiProp)?.icon })
							}, null, _parent, _scopeId));
							else _push(`<!---->`);
						}, _push, _parent, _scopeId);
						_push(`<div data-slot="wrapper" class="${ssrRenderClass(ui.value.wrapper({ class: unref(uiProp)?.wrapper }))}"${_scopeId}>`);
						if (__props.title || !!slots.title) _push(ssrRenderComponent(unref(ToastTitle), {
							"data-slot": "title",
							class: ui.value.title({ class: unref(uiProp)?.title })
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) ssrRenderSlot(_ctx.$slots, "title", {}, () => {
									if (typeof __props.title === "function") ssrRenderVNode(_push, createVNode(resolveDynamicComponent(__props.title()), null, null), _parent, _scopeId);
									else if (typeof __props.title === "object") ssrRenderVNode(_push, createVNode(resolveDynamicComponent(__props.title), null, null), _parent, _scopeId);
									else _push(`<!--[-->${ssrInterpolate(__props.title)}<!--]-->`);
								}, _push, _parent, _scopeId);
								else return [renderSlot(_ctx.$slots, "title", {}, () => [typeof __props.title === "function" ? (openBlock(), createBlock(resolveDynamicComponent(__props.title()), { key: 0 })) : typeof __props.title === "object" ? (openBlock(), createBlock(resolveDynamicComponent(__props.title), { key: 1 })) : (openBlock(), createBlock(Fragment, { key: 2 }, [createTextVNode(toDisplayString(__props.title), 1)], 64))])];
							}),
							_: 2
						}, _parent, _scopeId));
						else _push(`<!---->`);
						if (__props.description || !!slots.description) _push(ssrRenderComponent(unref(ToastDescription), {
							"data-slot": "description",
							class: ui.value.description({ class: unref(uiProp)?.description })
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) ssrRenderSlot(_ctx.$slots, "description", {}, () => {
									if (typeof __props.description === "function") ssrRenderVNode(_push, createVNode(resolveDynamicComponent(__props.description()), null, null), _parent, _scopeId);
									else if (typeof __props.description === "object") ssrRenderVNode(_push, createVNode(resolveDynamicComponent(__props.description), null, null), _parent, _scopeId);
									else _push(`<!--[-->${ssrInterpolate(__props.description)}<!--]-->`);
								}, _push, _parent, _scopeId);
								else return [renderSlot(_ctx.$slots, "description", {}, () => [typeof __props.description === "function" ? (openBlock(), createBlock(resolveDynamicComponent(__props.description()), { key: 0 })) : typeof __props.description === "object" ? (openBlock(), createBlock(resolveDynamicComponent(__props.description), { key: 1 })) : (openBlock(), createBlock(Fragment, { key: 2 }, [createTextVNode(toDisplayString(__props.description), 1)], 64))])];
							}),
							_: 2
						}, _parent, _scopeId));
						else _push(`<!---->`);
						if (__props.orientation === "vertical" && (__props.actions?.length || !!slots.actions)) {
							_push(`<div data-slot="actions" class="${ssrRenderClass(ui.value.actions({ class: unref(uiProp)?.actions }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "actions", {}, () => {
								_push(`<!--[-->`);
								ssrRenderList(__props.actions, (action, index) => {
									_push(ssrRenderComponent(unref(ToastAction), {
										key: index,
										"alt-text": action.label || "Action",
										"as-child": "",
										onClick: () => {}
									}, {
										default: withCtx((_, _push, _parent, _scopeId) => {
											if (_push) _push(ssrRenderComponent(_sfc_main$6, mergeProps({
												size: "xs",
												color: __props.color
											}, { ref_for: true }, action), null, _parent, _scopeId));
											else return [createVNode(_sfc_main$6, mergeProps({
												size: "xs",
												color: __props.color
											}, { ref_for: true }, action), null, 16, ["color"])];
										}),
										_: 2
									}, _parent, _scopeId));
								});
								_push(`<!--]-->`);
							}, _push, _parent, _scopeId);
							_push(`</div>`);
						} else _push(`<!---->`);
						_push(`</div>`);
						if (__props.orientation === "horizontal" && (__props.actions?.length || !!slots.actions) || __props.close) {
							_push(`<div data-slot="actions" class="${ssrRenderClass(ui.value.actions({
								class: unref(uiProp)?.actions,
								orientation: "horizontal"
							}))}"${_scopeId}>`);
							if (__props.orientation === "horizontal" && (__props.actions?.length || !!slots.actions)) ssrRenderSlot(_ctx.$slots, "actions", {}, () => {
								_push(`<!--[-->`);
								ssrRenderList(__props.actions, (action, index) => {
									_push(ssrRenderComponent(unref(ToastAction), {
										key: index,
										"alt-text": action.label || "Action",
										"as-child": "",
										onClick: () => {}
									}, {
										default: withCtx((_, _push, _parent, _scopeId) => {
											if (_push) _push(ssrRenderComponent(_sfc_main$6, mergeProps({
												size: "xs",
												color: __props.color
											}, { ref_for: true }, action), null, _parent, _scopeId));
											else return [createVNode(_sfc_main$6, mergeProps({
												size: "xs",
												color: __props.color
											}, { ref_for: true }, action), null, 16, ["color"])];
										}),
										_: 2
									}, _parent, _scopeId));
								});
								_push(`<!--]-->`);
							}, _push, _parent, _scopeId);
							else _push(`<!---->`);
							if (__props.close || !!slots.close) _push(ssrRenderComponent(unref(ToastClose), { "as-child": "" }, {
								default: withCtx((_, _push, _parent, _scopeId) => {
									if (_push) ssrRenderSlot(_ctx.$slots, "close", { ui: ui.value }, () => {
										if (__props.close) _push(ssrRenderComponent(_sfc_main$6, mergeProps({
											icon: __props.closeIcon || unref(appConfig).ui.icons.close,
											color: "neutral",
											variant: "link",
											"aria-label": unref(t)("toast.close")
										}, typeof __props.close === "object" ? __props.close : {}, {
											"data-slot": "close",
											class: ui.value.close({ class: unref(uiProp)?.close }),
											onClick: () => {}
										}), null, _parent, _scopeId));
										else _push(`<!---->`);
									}, _push, _parent, _scopeId);
									else return [renderSlot(_ctx.$slots, "close", { ui: ui.value }, () => [__props.close ? (openBlock(), createBlock(_sfc_main$6, mergeProps({
										key: 0,
										icon: __props.closeIcon || unref(appConfig).ui.icons.close,
										color: "neutral",
										variant: "link",
										"aria-label": unref(t)("toast.close")
									}, typeof __props.close === "object" ? __props.close : {}, {
										"data-slot": "close",
										class: ui.value.close({ class: unref(uiProp)?.close }),
										onClick: withModifiers(() => {}, ["stop"])
									}), null, 16, [
										"icon",
										"aria-label",
										"class",
										"onClick"
									])) : createCommentVNode("", true)])];
								}),
								_: 2
							}, _parent, _scopeId));
							else _push(`<!---->`);
							_push(`</div>`);
						} else _push(`<!---->`);
						if (__props.progress && open && remaining > 0 && duration) _push(ssrRenderComponent(_sfc_main$7, mergeProps({
							"model-value": remaining / duration * 100,
							color: __props.color
						}, typeof __props.progress === "object" ? __props.progress : {}, {
							size: "sm",
							"data-slot": "progress",
							class: ui.value.progress({ class: unref(uiProp)?.progress })
						}), null, _parent, _scopeId));
						else _push(`<!---->`);
					} else return [
						renderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => [__props.avatar ? (openBlock(), createBlock(_sfc_main$4, mergeProps({
							key: 0,
							size: unref(uiProp)?.avatarSize || ui.value.avatarSize()
						}, __props.avatar, {
							"data-slot": "avatar",
							class: ui.value.avatar({ class: unref(uiProp)?.avatar })
						}), null, 16, ["size", "class"])) : __props.icon ? (openBlock(), createBlock(_sfc_main$5, {
							key: 1,
							name: __props.icon,
							"data-slot": "icon",
							class: ui.value.icon({ class: unref(uiProp)?.icon })
						}, null, 8, ["name", "class"])) : createCommentVNode("", true)]),
						createVNode("div", {
							"data-slot": "wrapper",
							class: ui.value.wrapper({ class: unref(uiProp)?.wrapper })
						}, [
							__props.title || !!slots.title ? (openBlock(), createBlock(unref(ToastTitle), {
								key: 0,
								"data-slot": "title",
								class: ui.value.title({ class: unref(uiProp)?.title })
							}, {
								default: withCtx(() => [renderSlot(_ctx.$slots, "title", {}, () => [typeof __props.title === "function" ? (openBlock(), createBlock(resolveDynamicComponent(__props.title()), { key: 0 })) : typeof __props.title === "object" ? (openBlock(), createBlock(resolveDynamicComponent(__props.title), { key: 1 })) : (openBlock(), createBlock(Fragment, { key: 2 }, [createTextVNode(toDisplayString(__props.title), 1)], 64))])]),
								_: 3
							}, 8, ["class"])) : createCommentVNode("", true),
							__props.description || !!slots.description ? (openBlock(), createBlock(unref(ToastDescription), {
								key: 1,
								"data-slot": "description",
								class: ui.value.description({ class: unref(uiProp)?.description })
							}, {
								default: withCtx(() => [renderSlot(_ctx.$slots, "description", {}, () => [typeof __props.description === "function" ? (openBlock(), createBlock(resolveDynamicComponent(__props.description()), { key: 0 })) : typeof __props.description === "object" ? (openBlock(), createBlock(resolveDynamicComponent(__props.description), { key: 1 })) : (openBlock(), createBlock(Fragment, { key: 2 }, [createTextVNode(toDisplayString(__props.description), 1)], 64))])]),
								_: 3
							}, 8, ["class"])) : createCommentVNode("", true),
							__props.orientation === "vertical" && (__props.actions?.length || !!slots.actions) ? (openBlock(), createBlock("div", {
								key: 2,
								"data-slot": "actions",
								class: ui.value.actions({ class: unref(uiProp)?.actions })
							}, [renderSlot(_ctx.$slots, "actions", {}, () => [(openBlock(true), createBlock(Fragment, null, renderList(__props.actions, (action, index) => {
								return openBlock(), createBlock(unref(ToastAction), {
									key: index,
									"alt-text": action.label || "Action",
									"as-child": "",
									onClick: withModifiers(() => {}, ["stop"])
								}, {
									default: withCtx(() => [createVNode(_sfc_main$6, mergeProps({
										size: "xs",
										color: __props.color
									}, { ref_for: true }, action), null, 16, ["color"])]),
									_: 2
								}, 1032, ["alt-text", "onClick"]);
							}), 128))])], 2)) : createCommentVNode("", true)
						], 2),
						__props.orientation === "horizontal" && (__props.actions?.length || !!slots.actions) || __props.close ? (openBlock(), createBlock("div", {
							key: 0,
							"data-slot": "actions",
							class: ui.value.actions({
								class: unref(uiProp)?.actions,
								orientation: "horizontal"
							})
						}, [__props.orientation === "horizontal" && (__props.actions?.length || !!slots.actions) ? renderSlot(_ctx.$slots, "actions", { key: 0 }, () => [(openBlock(true), createBlock(Fragment, null, renderList(__props.actions, (action, index) => {
							return openBlock(), createBlock(unref(ToastAction), {
								key: index,
								"alt-text": action.label || "Action",
								"as-child": "",
								onClick: withModifiers(() => {}, ["stop"])
							}, {
								default: withCtx(() => [createVNode(_sfc_main$6, mergeProps({
									size: "xs",
									color: __props.color
								}, { ref_for: true }, action), null, 16, ["color"])]),
								_: 2
							}, 1032, ["alt-text", "onClick"]);
						}), 128))]) : createCommentVNode("", true), __props.close || !!slots.close ? (openBlock(), createBlock(unref(ToastClose), {
							key: 1,
							"as-child": ""
						}, {
							default: withCtx(() => [renderSlot(_ctx.$slots, "close", { ui: ui.value }, () => [__props.close ? (openBlock(), createBlock(_sfc_main$6, mergeProps({
								key: 0,
								icon: __props.closeIcon || unref(appConfig).ui.icons.close,
								color: "neutral",
								variant: "link",
								"aria-label": unref(t)("toast.close")
							}, typeof __props.close === "object" ? __props.close : {}, {
								"data-slot": "close",
								class: ui.value.close({ class: unref(uiProp)?.close }),
								onClick: withModifiers(() => {}, ["stop"])
							}), null, 16, [
								"icon",
								"aria-label",
								"class",
								"onClick"
							])) : createCommentVNode("", true)])]),
							_: 3
						})) : createCommentVNode("", true)], 2)) : createCommentVNode("", true),
						__props.progress && open && remaining > 0 && duration ? (openBlock(), createBlock(_sfc_main$7, mergeProps({
							key: 1,
							"model-value": remaining / duration * 100,
							color: __props.color
						}, typeof __props.progress === "object" ? __props.progress : {}, {
							size: "sm",
							"data-slot": "progress",
							class: ui.value.progress({ class: unref(uiProp)?.progress })
						}), null, 16, [
							"model-value",
							"color",
							"class"
						])) : createCommentVNode("", true)
					];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Toast.vue");
	return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/toaster.ts
var toaster_default = {
	"slots": {
		"viewport": "fixed flex flex-col w-[calc(100%-2rem)] sm:w-96 z-[100] data-[expanded=true]:h-(--height) focus:outline-none",
		"base": "pointer-events-auto absolute inset-x-0 z-(--index) transform-(--transform) data-[expanded=false]:data-[front=false]:h-(--front-height) data-[expanded=false]:data-[front=false]:*:opacity-0 data-[front=false]:*:transition-opacity data-[front=false]:*:duration-100 data-[state=closed]:animate-[toast-closed_200ms_ease-in-out] data-[state=closed]:data-[expanded=false]:data-[front=false]:animate-[toast-collapsed-closed_200ms_ease-in-out] data-[state=open]:data-[pulsing=odd]:animate-[toast-pulse-a_300ms_ease-out] data-[state=open]:data-[pulsing=even]:animate-[toast-pulse-b_300ms_ease-out] data-[swipe=move]:transition-none transition-[transform,translate,height] duration-200 ease-out"
	},
	"variants": {
		"position": {
			"top-left": { "viewport": "left-4" },
			"top-center": { "viewport": "left-1/2 transform -translate-x-1/2" },
			"top-right": { "viewport": "right-4" },
			"bottom-left": { "viewport": "left-4" },
			"bottom-center": { "viewport": "left-1/2 transform -translate-x-1/2" },
			"bottom-right": { "viewport": "right-4" }
		},
		"swipeDirection": {
			"up": "data-[swipe=end]:animate-[toast-slide-up_200ms_ease-out]",
			"right": "data-[swipe=end]:animate-[toast-slide-right_200ms_ease-out]",
			"down": "data-[swipe=end]:animate-[toast-slide-down_200ms_ease-out]",
			"left": "data-[swipe=end]:animate-[toast-slide-left_200ms_ease-out]"
		}
	},
	"compoundVariants": [
		{
			"position": [
				"top-left",
				"top-center",
				"top-right"
			],
			"class": {
				"viewport": "top-4",
				"base": "top-0 data-[state=open]:animate-[toast-slide-in-from-top_200ms_ease-in-out]"
			}
		},
		{
			"position": [
				"bottom-left",
				"bottom-center",
				"bottom-right"
			],
			"class": {
				"viewport": "bottom-4",
				"base": "bottom-0 data-[state=open]:animate-[toast-slide-in-from-bottom_200ms_ease-in-out]"
			}
		},
		{
			"swipeDirection": ["left", "right"],
			"class": "data-[swipe=move]:translate-x-(--reka-toast-swipe-move-x) data-[swipe=end]:translate-x-(--reka-toast-swipe-end-x) data-[swipe=cancel]:translate-x-0"
		},
		{
			"swipeDirection": ["up", "down"],
			"class": "data-[swipe=move]:translate-y-(--reka-toast-swipe-move-y) data-[swipe=end]:translate-y-(--reka-toast-swipe-end-y) data-[swipe=cancel]:translate-y-0"
		}
	],
	"defaultVariants": { "position": "bottom-right" }
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Toaster.vue
var _sfc_main$2 = /* @__PURE__ */ Object.assign({ name: "Toaster" }, {
	__ssrInlineRender: true,
	props: {
		position: {
			type: null,
			required: false
		},
		expand: {
			type: Boolean,
			required: false,
			default: true
		},
		progress: {
			type: Boolean,
			required: false,
			default: true
		},
		portal: {
			type: [Boolean, String],
			required: false,
			skipCheck: true,
			default: true
		},
		max: {
			type: Number,
			required: false,
			default: 5
		},
		class: {
			type: null,
			required: false
		},
		ui: {
			type: Object,
			required: false
		},
		label: {
			type: String,
			required: false
		},
		duration: {
			type: Number,
			required: false,
			default: 5e3
		},
		disableSwipe: {
			type: Boolean,
			required: false
		},
		swipeThreshold: {
			type: Number,
			required: false
		}
	},
	setup(__props) {
		const props = __props;
		const { toasts, remove } = useToast();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("toaster", props);
		provide(toastMaxInjectionKey, toRef(() => props.max));
		const providerProps = useForwardProps(reactivePick(props, "duration", "label", "swipeThreshold", "disableSwipe"));
		const portalProps = usePortal(toRef(() => props.portal));
		const swipeDirection = computed(() => {
			switch (props.position) {
				case "top-center": return "up";
				case "top-right":
				case "bottom-right": return "right";
				case "bottom-center": return "down";
				case "top-left":
				case "bottom-left": return "left";
			}
			return "right";
		});
		const ui = computed(() => tv({
			extend: tv(toaster_default),
			...appConfig.ui?.toaster || {}
		})({
			position: props.position,
			swipeDirection: swipeDirection.value
		}));
		function onUpdateOpen(value, id) {
			if (value) return;
			remove(id);
		}
		const hovered = ref(false);
		const expanded = computed(() => props.expand || hovered.value);
		const refs = ref([]);
		const height = computed(() => refs.value.reduce((acc, { height: height2 }) => acc + height2 + 16, 0));
		const frontHeight = computed(() => refs.value[refs.value.length - 1]?.height || 0);
		function getOffset(index) {
			return refs.value.slice(index + 1).reduce((acc, { height: height2 }) => acc + height2 + 16, 0);
		}
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(ToastProvider), mergeProps({ "swipe-direction": swipeDirection.value }, unref(providerProps), _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
						_push(`<!--[-->`);
						ssrRenderList(unref(toasts), (toast, index) => {
							_push(ssrRenderComponent(_sfc_main$3, mergeProps({
								key: toast.id,
								ref_for: true,
								ref_key: "refs",
								ref: refs,
								progress: __props.progress
							}, { ref_for: true }, unref(omit)(toast, [
								"id",
								"close",
								"_duplicate",
								"_updated"
							]), {
								close: toast.close,
								"data-expanded": expanded.value,
								"data-front": !expanded.value && index === unref(toasts).length - 1,
								"data-pulsing": toast._duplicate ? toast._duplicate % 2 === 0 ? "even" : "odd" : void 0,
								style: {
									"--index": index - unref(toasts).length + unref(toasts).length,
									"--before": unref(toasts).length - 1 - index,
									"--offset": getOffset(index),
									"--scale": expanded.value ? "1" : "calc(1 - var(--before) * var(--scale-factor))",
									"--translate": expanded.value ? "calc(var(--offset) * var(--translate-factor))" : "calc(var(--before) * var(--gap))",
									"--transform": "translateY(var(--translate)) scale(var(--scale))"
								},
								"data-slot": "base",
								class: ui.value.base({ class: [unref(uiProp)?.base, toast.onClick ? "cursor-pointer" : void 0] }),
								"onUpdate:open": ($event) => onUpdateOpen($event, toast.id),
								onClick: ($event) => toast.onClick && toast.onClick(toast)
							}), null, _parent, _scopeId));
						});
						_push(`<!--]-->`);
						_push(ssrRenderComponent(unref(ToastPortal), unref(portalProps), {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(unref(ToastViewport), {
									"data-expanded": expanded.value,
									"data-slot": "viewport",
									class: ui.value.viewport({ class: [unref(uiProp)?.viewport, props.class] }),
									style: {
										"--scale-factor": "0.05",
										"--translate-factor": __props.position?.startsWith("top") ? "1px" : "-1px",
										"--gap": __props.position?.startsWith("top") ? "16px" : "-16px",
										"--front-height": `${frontHeight.value}px`,
										"--height": `${height.value}px`
									},
									onMouseenter: ($event) => hovered.value = true,
									onMouseleave: ($event) => hovered.value = false
								}, null, _parent, _scopeId));
								else return [createVNode(unref(ToastViewport), {
									"data-expanded": expanded.value,
									"data-slot": "viewport",
									class: ui.value.viewport({ class: [unref(uiProp)?.viewport, props.class] }),
									style: {
										"--scale-factor": "0.05",
										"--translate-factor": __props.position?.startsWith("top") ? "1px" : "-1px",
										"--gap": __props.position?.startsWith("top") ? "16px" : "-16px",
										"--front-height": `${frontHeight.value}px`,
										"--height": `${height.value}px`
									},
									onMouseenter: ($event) => hovered.value = true,
									onMouseleave: ($event) => hovered.value = false
								}, null, 8, [
									"data-expanded",
									"class",
									"style",
									"onMouseenter",
									"onMouseleave"
								])];
							}),
							_: 1
						}, _parent, _scopeId));
					} else return [
						renderSlot(_ctx.$slots, "default"),
						(openBlock(true), createBlock(Fragment, null, renderList(unref(toasts), (toast, index) => {
							return openBlock(), createBlock(_sfc_main$3, mergeProps({
								key: toast.id,
								ref_for: true,
								ref_key: "refs",
								ref: refs,
								progress: __props.progress
							}, { ref_for: true }, unref(omit)(toast, [
								"id",
								"close",
								"_duplicate",
								"_updated"
							]), {
								close: toast.close,
								"data-expanded": expanded.value,
								"data-front": !expanded.value && index === unref(toasts).length - 1,
								"data-pulsing": toast._duplicate ? toast._duplicate % 2 === 0 ? "even" : "odd" : void 0,
								style: {
									"--index": index - unref(toasts).length + unref(toasts).length,
									"--before": unref(toasts).length - 1 - index,
									"--offset": getOffset(index),
									"--scale": expanded.value ? "1" : "calc(1 - var(--before) * var(--scale-factor))",
									"--translate": expanded.value ? "calc(var(--offset) * var(--translate-factor))" : "calc(var(--before) * var(--gap))",
									"--transform": "translateY(var(--translate)) scale(var(--scale))"
								},
								"data-slot": "base",
								class: ui.value.base({ class: [unref(uiProp)?.base, toast.onClick ? "cursor-pointer" : void 0] }),
								"onUpdate:open": ($event) => onUpdateOpen($event, toast.id),
								onClick: ($event) => toast.onClick && toast.onClick(toast)
							}), null, 16, [
								"progress",
								"close",
								"data-expanded",
								"data-front",
								"data-pulsing",
								"style",
								"class",
								"onUpdate:open",
								"onClick"
							]);
						}), 128)),
						createVNode(unref(ToastPortal), unref(portalProps), {
							default: withCtx(() => [createVNode(unref(ToastViewport), {
								"data-expanded": expanded.value,
								"data-slot": "viewport",
								class: ui.value.viewport({ class: [unref(uiProp)?.viewport, props.class] }),
								style: {
									"--scale-factor": "0.05",
									"--translate-factor": __props.position?.startsWith("top") ? "1px" : "-1px",
									"--gap": __props.position?.startsWith("top") ? "16px" : "-16px",
									"--front-height": `${frontHeight.value}px`,
									"--height": `${height.value}px`
								},
								onMouseenter: ($event) => hovered.value = true,
								onMouseleave: ($event) => hovered.value = false
							}, null, 8, [
								"data-expanded",
								"class",
								"style",
								"onMouseenter",
								"onMouseleave"
							])]),
							_: 1
						}, 16)
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
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Toaster.vue");
	return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/composables/useOverlay.js
function _useOverlay() {
	const overlays = shallowReactive([]);
	const create = (component, _options) => {
		const { props, defaultOpen, destroyOnClose } = _options || {};
		const options = reactive({
			id: Symbol(import.meta.dev ? "useOverlay" : ""),
			isOpen: !!defaultOpen,
			component: markRaw(component),
			isMounted: !!defaultOpen,
			destroyOnClose: !!destroyOnClose,
			originalProps: props || {},
			props: { ...props }
		});
		overlays.push(options);
		return {
			...options,
			open: (props2) => open(options.id, props2),
			close: (value) => close(options.id, value),
			patch: (props2) => patch(options.id, props2)
		};
	};
	const open = (id, props) => {
		const overlay = getOverlay(id);
		if (props) overlay.props = {
			...overlay.originalProps,
			...props
		};
		else overlay.props = { ...overlay.originalProps };
		overlay.isOpen = true;
		overlay.isMounted = true;
		const result = new Promise((resolve) => overlay.resolvePromise = resolve);
		return Object.assign(result, {
			id,
			isMounted: overlay.isMounted,
			isOpen: overlay.isOpen,
			result
		});
	};
	const close = (id, value) => {
		const overlay = getOverlay(id);
		overlay.isOpen = false;
		if (overlay.resolvePromise) {
			overlay.resolvePromise(value);
			overlay.resolvePromise = void 0;
		}
	};
	const closeAll = () => {
		overlays.forEach((overlay) => close(overlay.id));
	};
	const unmount = (id) => {
		const overlay = getOverlay(id);
		overlay.isMounted = false;
		if (overlay.destroyOnClose) {
			const index = overlays.findIndex((overlay2) => overlay2.id === id);
			overlays.splice(index, 1);
		}
	};
	const patch = (id, props) => {
		const overlay = getOverlay(id);
		overlay.props = {
			...overlay.props,
			...props
		};
	};
	const getOverlay = (id) => {
		const overlay = overlays.find((overlay2) => overlay2.id === id);
		if (!overlay) throw new Error("Overlay not found");
		return overlay;
	};
	const isOpen = (id) => {
		return getOverlay(id).isOpen;
	};
	return {
		overlays,
		open,
		close,
		closeAll,
		create,
		patch,
		unmount,
		isOpen
	};
}
var useOverlay = /* @__PURE__ */ createSharedComposable(_useOverlay);
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/OverlayProvider.vue
var _sfc_main$1 = {
	__name: "OverlayProvider",
	__ssrInlineRender: true,
	setup(__props) {
		const { overlays, unmount, close } = useOverlay();
		const mountedOverlays = computed(() => overlays.filter((overlay) => overlay.isMounted));
		const onAfterLeave = (id) => {
			close(id);
			unmount(id);
		};
		const onClose = (id, value) => {
			close(id, value);
		};
		return (_ctx, _push, _parent, _attrs) => {
			_push(`<!--[-->`);
			ssrRenderList(mountedOverlays.value, (overlay) => {
				ssrRenderVNode(_push, createVNode(resolveDynamicComponent(overlay.component), mergeProps({ key: overlay.id }, { ref_for: true }, overlay.props, {
					open: overlay.isOpen,
					"onUpdate:open": ($event) => overlay.isOpen = $event,
					onClose: (value) => onClose(overlay.id, value),
					"onAfter:leave": ($event) => onAfterLeave(overlay.id)
				}), null), _parent);
			});
			_push(`<!--]-->`);
		};
	}
};
var _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/OverlayProvider.vue");
	return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/App.vue
var _sfc_main = /* @__PURE__ */ Object.assign({ name: "App" }, {
	__ssrInlineRender: true,
	props: {
		tooltip: {
			type: Object,
			required: false
		},
		toaster: {
			type: [Object, null],
			required: false
		},
		locale: {
			type: Object,
			required: false
		},
		portal: {
			type: [Boolean, String],
			required: false,
			skipCheck: true,
			default: "body"
		},
		dir: {
			type: String,
			required: false
		},
		scrollBody: {
			type: [Boolean, Object],
			required: false
		},
		nonce: {
			type: String,
			required: false
		}
	},
	setup(__props) {
		const props = __props;
		const configProviderProps = useForwardProps(reactivePick(props, "scrollBody"));
		const tooltipProps = toRef(() => props.tooltip);
		const toasterProps = toRef(() => props.toaster);
		const locale = toRef(() => props.locale);
		provide(localeContextInjectionKey, locale);
		provide(portalTargetInjectionKey, toRef(() => props.portal));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(ConfigProvider), mergeProps({
				"use-id": () => useId(),
				dir: props.dir || locale.value?.dir,
				locale: locale.value?.code
			}, unref(configProviderProps), _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(ssrRenderComponent(unref(TooltipProvider), tooltipProps.value, {
						default: withCtx((_, _push, _parent, _scopeId) => {
							if (_push) {
								if (__props.toaster !== null) _push(ssrRenderComponent(_sfc_main$2, toasterProps.value, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
										else return [renderSlot(_ctx.$slots, "default")];
									}),
									_: 3
								}, _parent, _scopeId));
								else ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
								_push(ssrRenderComponent(_sfc_main$1, null, null, _parent, _scopeId));
							} else return [__props.toaster !== null ? (openBlock(), createBlock(_sfc_main$2, mergeProps({ key: 0 }, toasterProps.value), {
								default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
								_: 3
							}, 16)) : renderSlot(_ctx.$slots, "default", { key: 1 }), createVNode(_sfc_main$1)];
						}),
						_: 3
					}, _parent, _scopeId));
					else return [createVNode(unref(TooltipProvider), tooltipProps.value, {
						default: withCtx(() => [__props.toaster !== null ? (openBlock(), createBlock(_sfc_main$2, mergeProps({ key: 0 }, toasterProps.value), {
							default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
							_: 3
						}, 16)) : renderSlot(_ctx.$slots, "default", { key: 1 }), createVNode(_sfc_main$1)]),
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
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/App.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
//#endregion
export { _sfc_main as t };

//# sourceMappingURL=App-BCJnMUgD.js.map