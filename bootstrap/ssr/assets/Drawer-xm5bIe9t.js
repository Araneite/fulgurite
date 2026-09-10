import { F as useAppConfig, b as FieldGroupReset, n as usePortal, u as tv, w as useComponentUI } from "./usePortal-DZb6nPjI.js";
import { t as pointerDownOutside } from "./overlay-DvxSEWvQ.js";
import { ssrInterpolate, ssrRenderClass, ssrRenderComponent, ssrRenderSlot, ssrRenderVNode } from "vue/server-renderer";
import { computed, createBlock, createCommentVNode, createTextVNode, createVNode, mergeProps, openBlock, renderSlot, resolveDynamicComponent, toDisplayString, toHandlers, toRef, unref, useSSRContext, useSlots, withCtx } from "vue";
import { VisuallyHidden, useForwardPropsEmits } from "reka-ui";
import { reactivePick } from "@vueuse/core";
import { DrawerContent, DrawerDescription, DrawerHandle, DrawerOverlay, DrawerPortal, DrawerRoot, DrawerRootNested, DrawerTitle, DrawerTrigger } from "vaul-vue";
//#region virtual:nuxt-ui-templates/ui/drawer.ts
var drawer_default = {
	"slots": {
		"overlay": "fixed inset-0 bg-elevated/75",
		"content": "fixed bg-default ring ring-default flex focus:outline-none",
		"handle": ["shrink-0 !bg-accented", "transition-opacity"],
		"container": "w-full flex flex-col gap-4 p-4 overflow-y-auto",
		"header": "",
		"title": "text-highlighted font-semibold",
		"description": "mt-1 text-muted text-sm",
		"body": "flex-1",
		"footer": "flex flex-col gap-1.5"
	},
	"variants": {
		"direction": {
			"top": {
				"content": "mb-24 flex-col-reverse",
				"handle": "mb-4"
			},
			"right": {
				"content": "flex-row rtl:flex-row-reverse",
				"handle": "!ml-4"
			},
			"bottom": {
				"content": "mt-24 flex-col",
				"handle": "mt-4"
			},
			"left": {
				"content": "flex-row-reverse rtl:flex-row",
				"handle": "!mr-4"
			}
		},
		"inset": { "true": { "content": "rounded-lg after:hidden overflow-hidden [--initial-transform:calc(100%+1.5rem)]" } },
		"snapPoints": { "true": "" }
	},
	"compoundVariants": [
		{
			"direction": ["top", "bottom"],
			"class": {
				"content": "h-auto max-h-[96%]",
				"handle": "!w-12 !h-1.5 mx-auto"
			}
		},
		{
			"direction": ["top", "bottom"],
			"snapPoints": true,
			"class": { "content": "h-full" }
		},
		{
			"direction": ["right", "left"],
			"class": {
				"content": "w-auto max-w-[calc(100%-2rem)]",
				"handle": "!h-12 !w-1.5 mt-auto mb-auto"
			}
		},
		{
			"direction": ["right", "left"],
			"snapPoints": true,
			"class": { "content": "w-full" }
		},
		{
			"direction": "top",
			"inset": true,
			"class": { "content": "inset-x-4 top-4" }
		},
		{
			"direction": "top",
			"inset": false,
			"class": { "content": "inset-x-0 top-0 rounded-b-lg" }
		},
		{
			"direction": "bottom",
			"inset": true,
			"class": { "content": "inset-x-4 bottom-4" }
		},
		{
			"direction": "bottom",
			"inset": false,
			"class": { "content": "inset-x-0 bottom-0 rounded-t-lg" }
		},
		{
			"direction": "left",
			"inset": true,
			"class": { "content": "inset-y-4 left-4" }
		},
		{
			"direction": "left",
			"inset": false,
			"class": { "content": "inset-y-0 left-0 rounded-r-lg" }
		},
		{
			"direction": "right",
			"inset": true,
			"class": { "content": "inset-y-4 right-4" }
		},
		{
			"direction": "right",
			"inset": false,
			"class": { "content": "inset-y-0 right-0 rounded-l-lg" }
		}
	]
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Drawer.vue
var _sfc_main = {
	__name: "Drawer",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false
		},
		title: {
			type: String,
			required: false
		},
		description: {
			type: String,
			required: false
		},
		inset: {
			type: Boolean,
			required: false
		},
		content: {
			type: Object,
			required: false
		},
		overlay: {
			type: Boolean,
			required: false,
			default: true
		},
		handle: {
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
		nested: {
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
		activeSnapPoint: {
			type: [
				Number,
				String,
				null
			],
			required: false
		},
		closeThreshold: {
			type: Number,
			required: false
		},
		shouldScaleBackground: {
			type: Boolean,
			required: false
		},
		setBackgroundColorOnScale: {
			type: Boolean,
			required: false
		},
		scrollLockTimeout: {
			type: Number,
			required: false
		},
		fixed: {
			type: Boolean,
			required: false
		},
		dismissible: {
			type: Boolean,
			required: false,
			default: true
		},
		modal: {
			type: Boolean,
			required: false,
			default: true
		},
		open: {
			type: Boolean,
			required: false
		},
		defaultOpen: {
			type: Boolean,
			required: false
		},
		direction: {
			type: String,
			required: false,
			default: "bottom"
		},
		noBodyStyles: {
			type: Boolean,
			required: false
		},
		handleOnly: {
			type: Boolean,
			required: false
		},
		preventScrollRestoration: {
			type: Boolean,
			required: false
		},
		snapPoints: {
			type: Array,
			required: false
		}
	},
	emits: [
		"close:prevent",
		"drag",
		"release",
		"close",
		"update:open",
		"update:activeSnapPoint",
		"animationEnd"
	],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const slots = useSlots();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("drawer", props);
		const rootProps = useForwardPropsEmits(reactivePick(props, "activeSnapPoint", "closeThreshold", "shouldScaleBackground", "setBackgroundColorOnScale", "scrollLockTimeout", "fixed", "dismissible", "modal", "open", "defaultOpen", "nested", "direction", "noBodyStyles", "handleOnly", "preventScrollRestoration", "snapPoints"), emits);
		const portalProps = usePortal(toRef(() => props.portal));
		const contentProps = toRef(() => props.content);
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
		const ui = computed(() => tv({
			extend: tv(drawer_default),
			...appConfig.ui?.drawer || {}
		})({
			direction: props.direction,
			inset: props.inset,
			snapPoints: props.snapPoints && props.snapPoints.length > 0
		}));
		return (_ctx, _push, _parent, _attrs) => {
			ssrRenderVNode(_push, createVNode(resolveDynamicComponent(__props.nested ? unref(DrawerRootNested) : unref(DrawerRoot)), mergeProps(unref(rootProps), _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						if (!!slots.default) _push(ssrRenderComponent(unref(DrawerTrigger), {
							"as-child": "",
							class: props.class
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
								else return [renderSlot(_ctx.$slots, "default")];
							}),
							_: 3
						}, _parent, _scopeId));
						else _push(`<!---->`);
						_push(ssrRenderComponent(unref(DrawerPortal), unref(portalProps), {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(unref(FieldGroupReset), null, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) {
											if (__props.overlay) _push(ssrRenderComponent(unref(DrawerOverlay), {
												"data-slot": "overlay",
												class: ui.value.overlay({ class: unref(uiProp)?.overlay })
											}, null, _parent, _scopeId));
											else _push(`<!---->`);
											_push(ssrRenderComponent(unref(DrawerContent), mergeProps({
												"data-slot": "content",
												class: ui.value.content({ class: [!slots.default && props.class, unref(uiProp)?.content] })
											}, contentProps.value, toHandlers(contentEvents.value)), {
												default: withCtx((_, _push, _parent, _scopeId) => {
													if (_push) {
														if (__props.handle) _push(ssrRenderComponent(unref(DrawerHandle), {
															"data-slot": "handle",
															class: ui.value.handle({ class: unref(uiProp)?.handle })
														}, null, _parent, _scopeId));
														else _push(`<!---->`);
														if (!__props.title && !slots.title || !__props.description && !slots.description || !!slots.content) _push(ssrRenderComponent(unref(VisuallyHidden), null, {
															default: withCtx((_, _push, _parent, _scopeId) => {
																if (_push) {
																	if (!__props.title && !slots.title) _push(ssrRenderComponent(unref(DrawerTitle), null, null, _parent, _scopeId));
																	else if (!!slots.content) _push(ssrRenderComponent(unref(DrawerTitle), null, {
																		default: withCtx((_, _push, _parent, _scopeId) => {
																			if (_push) ssrRenderSlot(_ctx.$slots, "title", {}, () => {
																				_push(`${ssrInterpolate(__props.title)}`);
																			}, _push, _parent, _scopeId);
																			else return [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(__props.title), 1)])];
																		}),
																		_: 3
																	}, _parent, _scopeId));
																	else _push(`<!---->`);
																	if (!__props.description && !slots.description) _push(ssrRenderComponent(unref(DrawerDescription), null, null, _parent, _scopeId));
																	else if (!!slots.content) _push(ssrRenderComponent(unref(DrawerDescription), null, {
																		default: withCtx((_, _push, _parent, _scopeId) => {
																			if (_push) ssrRenderSlot(_ctx.$slots, "description", {}, () => {
																				_push(`${ssrInterpolate(__props.description)}`);
																			}, _push, _parent, _scopeId);
																			else return [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(__props.description), 1)])];
																		}),
																		_: 3
																	}, _parent, _scopeId));
																	else _push(`<!---->`);
																} else return [!__props.title && !slots.title ? (openBlock(), createBlock(unref(DrawerTitle), { key: 0 })) : !!slots.content ? (openBlock(), createBlock(unref(DrawerTitle), { key: 1 }, {
																	default: withCtx(() => [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(__props.title), 1)])]),
																	_: 3
																})) : createCommentVNode("", true), !__props.description && !slots.description ? (openBlock(), createBlock(unref(DrawerDescription), { key: 2 })) : !!slots.content ? (openBlock(), createBlock(unref(DrawerDescription), { key: 3 }, {
																	default: withCtx(() => [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(__props.description), 1)])]),
																	_: 3
																})) : createCommentVNode("", true)];
															}),
															_: 3
														}, _parent, _scopeId));
														else _push(`<!---->`);
														ssrRenderSlot(_ctx.$slots, "content", {}, () => {
															_push(`<div data-slot="container" class="${ssrRenderClass(ui.value.container({ class: unref(uiProp)?.container }))}"${_scopeId}>`);
															if (!!slots.header || __props.title || !!slots.title || __props.description || !!slots.description) {
																_push(`<div data-slot="header" class="${ssrRenderClass(ui.value.header({ class: unref(uiProp)?.header }))}"${_scopeId}>`);
																ssrRenderSlot(_ctx.$slots, "header", {}, () => {
																	if (__props.title || !!slots.title) _push(ssrRenderComponent(unref(DrawerTitle), {
																		"data-slot": "title",
																		class: ui.value.title({ class: unref(uiProp)?.title })
																	}, {
																		default: withCtx((_, _push, _parent, _scopeId) => {
																			if (_push) ssrRenderSlot(_ctx.$slots, "title", {}, () => {
																				_push(`${ssrInterpolate(__props.title)}`);
																			}, _push, _parent, _scopeId);
																			else return [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(__props.title), 1)])];
																		}),
																		_: 3
																	}, _parent, _scopeId));
																	else _push(`<!---->`);
																	if (__props.description || !!slots.description) _push(ssrRenderComponent(unref(DrawerDescription), {
																		"data-slot": "description",
																		class: ui.value.description({ class: unref(uiProp)?.description })
																	}, {
																		default: withCtx((_, _push, _parent, _scopeId) => {
																			if (_push) ssrRenderSlot(_ctx.$slots, "description", {}, () => {
																				_push(`${ssrInterpolate(__props.description)}`);
																			}, _push, _parent, _scopeId);
																			else return [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(__props.description), 1)])];
																		}),
																		_: 3
																	}, _parent, _scopeId));
																	else _push(`<!---->`);
																}, _push, _parent, _scopeId);
																_push(`</div>`);
															} else _push(`<!---->`);
															if (!!slots.body) {
																_push(`<div data-slot="body" class="${ssrRenderClass(ui.value.body({ class: unref(uiProp)?.body }))}"${_scopeId}>`);
																ssrRenderSlot(_ctx.$slots, "body", {}, null, _push, _parent, _scopeId);
																_push(`</div>`);
															} else _push(`<!---->`);
															if (!!slots.footer) {
																_push(`<div data-slot="footer" class="${ssrRenderClass(ui.value.footer({ class: unref(uiProp)?.footer }))}"${_scopeId}>`);
																ssrRenderSlot(_ctx.$slots, "footer", {}, null, _push, _parent, _scopeId);
																_push(`</div>`);
															} else _push(`<!---->`);
															_push(`</div>`);
														}, _push, _parent, _scopeId);
													} else return [
														__props.handle ? (openBlock(), createBlock(unref(DrawerHandle), {
															key: 0,
															"data-slot": "handle",
															class: ui.value.handle({ class: unref(uiProp)?.handle })
														}, null, 8, ["class"])) : createCommentVNode("", true),
														!__props.title && !slots.title || !__props.description && !slots.description || !!slots.content ? (openBlock(), createBlock(unref(VisuallyHidden), { key: 1 }, {
															default: withCtx(() => [!__props.title && !slots.title ? (openBlock(), createBlock(unref(DrawerTitle), { key: 0 })) : !!slots.content ? (openBlock(), createBlock(unref(DrawerTitle), { key: 1 }, {
																default: withCtx(() => [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(__props.title), 1)])]),
																_: 3
															})) : createCommentVNode("", true), !__props.description && !slots.description ? (openBlock(), createBlock(unref(DrawerDescription), { key: 2 })) : !!slots.content ? (openBlock(), createBlock(unref(DrawerDescription), { key: 3 }, {
																default: withCtx(() => [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(__props.description), 1)])]),
																_: 3
															})) : createCommentVNode("", true)]),
															_: 3
														})) : createCommentVNode("", true),
														renderSlot(_ctx.$slots, "content", {}, () => [createVNode("div", {
															"data-slot": "container",
															class: ui.value.container({ class: unref(uiProp)?.container })
														}, [
															!!slots.header || __props.title || !!slots.title || __props.description || !!slots.description ? (openBlock(), createBlock("div", {
																key: 0,
																"data-slot": "header",
																class: ui.value.header({ class: unref(uiProp)?.header })
															}, [renderSlot(_ctx.$slots, "header", {}, () => [__props.title || !!slots.title ? (openBlock(), createBlock(unref(DrawerTitle), {
																key: 0,
																"data-slot": "title",
																class: ui.value.title({ class: unref(uiProp)?.title })
															}, {
																default: withCtx(() => [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(__props.title), 1)])]),
																_: 3
															}, 8, ["class"])) : createCommentVNode("", true), __props.description || !!slots.description ? (openBlock(), createBlock(unref(DrawerDescription), {
																key: 1,
																"data-slot": "description",
																class: ui.value.description({ class: unref(uiProp)?.description })
															}, {
																default: withCtx(() => [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(__props.description), 1)])]),
																_: 3
															}, 8, ["class"])) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true),
															!!slots.body ? (openBlock(), createBlock("div", {
																key: 1,
																"data-slot": "body",
																class: ui.value.body({ class: unref(uiProp)?.body })
															}, [renderSlot(_ctx.$slots, "body")], 2)) : createCommentVNode("", true),
															!!slots.footer ? (openBlock(), createBlock("div", {
																key: 2,
																"data-slot": "footer",
																class: ui.value.footer({ class: unref(uiProp)?.footer })
															}, [renderSlot(_ctx.$slots, "footer")], 2)) : createCommentVNode("", true)
														], 2)])
													];
												}),
												_: 3
											}, _parent, _scopeId));
										} else return [__props.overlay ? (openBlock(), createBlock(unref(DrawerOverlay), {
											key: 0,
											"data-slot": "overlay",
											class: ui.value.overlay({ class: unref(uiProp)?.overlay })
										}, null, 8, ["class"])) : createCommentVNode("", true), createVNode(unref(DrawerContent), mergeProps({
											"data-slot": "content",
											class: ui.value.content({ class: [!slots.default && props.class, unref(uiProp)?.content] })
										}, contentProps.value, toHandlers(contentEvents.value)), {
											default: withCtx(() => [
												__props.handle ? (openBlock(), createBlock(unref(DrawerHandle), {
													key: 0,
													"data-slot": "handle",
													class: ui.value.handle({ class: unref(uiProp)?.handle })
												}, null, 8, ["class"])) : createCommentVNode("", true),
												!__props.title && !slots.title || !__props.description && !slots.description || !!slots.content ? (openBlock(), createBlock(unref(VisuallyHidden), { key: 1 }, {
													default: withCtx(() => [!__props.title && !slots.title ? (openBlock(), createBlock(unref(DrawerTitle), { key: 0 })) : !!slots.content ? (openBlock(), createBlock(unref(DrawerTitle), { key: 1 }, {
														default: withCtx(() => [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(__props.title), 1)])]),
														_: 3
													})) : createCommentVNode("", true), !__props.description && !slots.description ? (openBlock(), createBlock(unref(DrawerDescription), { key: 2 })) : !!slots.content ? (openBlock(), createBlock(unref(DrawerDescription), { key: 3 }, {
														default: withCtx(() => [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(__props.description), 1)])]),
														_: 3
													})) : createCommentVNode("", true)]),
													_: 3
												})) : createCommentVNode("", true),
												renderSlot(_ctx.$slots, "content", {}, () => [createVNode("div", {
													"data-slot": "container",
													class: ui.value.container({ class: unref(uiProp)?.container })
												}, [
													!!slots.header || __props.title || !!slots.title || __props.description || !!slots.description ? (openBlock(), createBlock("div", {
														key: 0,
														"data-slot": "header",
														class: ui.value.header({ class: unref(uiProp)?.header })
													}, [renderSlot(_ctx.$slots, "header", {}, () => [__props.title || !!slots.title ? (openBlock(), createBlock(unref(DrawerTitle), {
														key: 0,
														"data-slot": "title",
														class: ui.value.title({ class: unref(uiProp)?.title })
													}, {
														default: withCtx(() => [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(__props.title), 1)])]),
														_: 3
													}, 8, ["class"])) : createCommentVNode("", true), __props.description || !!slots.description ? (openBlock(), createBlock(unref(DrawerDescription), {
														key: 1,
														"data-slot": "description",
														class: ui.value.description({ class: unref(uiProp)?.description })
													}, {
														default: withCtx(() => [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(__props.description), 1)])]),
														_: 3
													}, 8, ["class"])) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true),
													!!slots.body ? (openBlock(), createBlock("div", {
														key: 1,
														"data-slot": "body",
														class: ui.value.body({ class: unref(uiProp)?.body })
													}, [renderSlot(_ctx.$slots, "body")], 2)) : createCommentVNode("", true),
													!!slots.footer ? (openBlock(), createBlock("div", {
														key: 2,
														"data-slot": "footer",
														class: ui.value.footer({ class: unref(uiProp)?.footer })
													}, [renderSlot(_ctx.$slots, "footer")], 2)) : createCommentVNode("", true)
												], 2)])
											]),
											_: 3
										}, 16, ["class"])];
									}),
									_: 3
								}, _parent, _scopeId));
								else return [createVNode(unref(FieldGroupReset), null, {
									default: withCtx(() => [__props.overlay ? (openBlock(), createBlock(unref(DrawerOverlay), {
										key: 0,
										"data-slot": "overlay",
										class: ui.value.overlay({ class: unref(uiProp)?.overlay })
									}, null, 8, ["class"])) : createCommentVNode("", true), createVNode(unref(DrawerContent), mergeProps({
										"data-slot": "content",
										class: ui.value.content({ class: [!slots.default && props.class, unref(uiProp)?.content] })
									}, contentProps.value, toHandlers(contentEvents.value)), {
										default: withCtx(() => [
											__props.handle ? (openBlock(), createBlock(unref(DrawerHandle), {
												key: 0,
												"data-slot": "handle",
												class: ui.value.handle({ class: unref(uiProp)?.handle })
											}, null, 8, ["class"])) : createCommentVNode("", true),
											!__props.title && !slots.title || !__props.description && !slots.description || !!slots.content ? (openBlock(), createBlock(unref(VisuallyHidden), { key: 1 }, {
												default: withCtx(() => [!__props.title && !slots.title ? (openBlock(), createBlock(unref(DrawerTitle), { key: 0 })) : !!slots.content ? (openBlock(), createBlock(unref(DrawerTitle), { key: 1 }, {
													default: withCtx(() => [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(__props.title), 1)])]),
													_: 3
												})) : createCommentVNode("", true), !__props.description && !slots.description ? (openBlock(), createBlock(unref(DrawerDescription), { key: 2 })) : !!slots.content ? (openBlock(), createBlock(unref(DrawerDescription), { key: 3 }, {
													default: withCtx(() => [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(__props.description), 1)])]),
													_: 3
												})) : createCommentVNode("", true)]),
												_: 3
											})) : createCommentVNode("", true),
											renderSlot(_ctx.$slots, "content", {}, () => [createVNode("div", {
												"data-slot": "container",
												class: ui.value.container({ class: unref(uiProp)?.container })
											}, [
												!!slots.header || __props.title || !!slots.title || __props.description || !!slots.description ? (openBlock(), createBlock("div", {
													key: 0,
													"data-slot": "header",
													class: ui.value.header({ class: unref(uiProp)?.header })
												}, [renderSlot(_ctx.$slots, "header", {}, () => [__props.title || !!slots.title ? (openBlock(), createBlock(unref(DrawerTitle), {
													key: 0,
													"data-slot": "title",
													class: ui.value.title({ class: unref(uiProp)?.title })
												}, {
													default: withCtx(() => [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(__props.title), 1)])]),
													_: 3
												}, 8, ["class"])) : createCommentVNode("", true), __props.description || !!slots.description ? (openBlock(), createBlock(unref(DrawerDescription), {
													key: 1,
													"data-slot": "description",
													class: ui.value.description({ class: unref(uiProp)?.description })
												}, {
													default: withCtx(() => [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(__props.description), 1)])]),
													_: 3
												}, 8, ["class"])) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true),
												!!slots.body ? (openBlock(), createBlock("div", {
													key: 1,
													"data-slot": "body",
													class: ui.value.body({ class: unref(uiProp)?.body })
												}, [renderSlot(_ctx.$slots, "body")], 2)) : createCommentVNode("", true),
												!!slots.footer ? (openBlock(), createBlock("div", {
													key: 2,
													"data-slot": "footer",
													class: ui.value.footer({ class: unref(uiProp)?.footer })
												}, [renderSlot(_ctx.$slots, "footer")], 2)) : createCommentVNode("", true)
											], 2)])
										]),
										_: 3
									}, 16, ["class"])]),
									_: 3
								})];
							}),
							_: 3
						}, _parent, _scopeId));
					} else return [!!slots.default ? (openBlock(), createBlock(unref(DrawerTrigger), {
						key: 0,
						"as-child": "",
						class: props.class
					}, {
						default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
						_: 3
					}, 8, ["class"])) : createCommentVNode("", true), createVNode(unref(DrawerPortal), unref(portalProps), {
						default: withCtx(() => [createVNode(unref(FieldGroupReset), null, {
							default: withCtx(() => [__props.overlay ? (openBlock(), createBlock(unref(DrawerOverlay), {
								key: 0,
								"data-slot": "overlay",
								class: ui.value.overlay({ class: unref(uiProp)?.overlay })
							}, null, 8, ["class"])) : createCommentVNode("", true), createVNode(unref(DrawerContent), mergeProps({
								"data-slot": "content",
								class: ui.value.content({ class: [!slots.default && props.class, unref(uiProp)?.content] })
							}, contentProps.value, toHandlers(contentEvents.value)), {
								default: withCtx(() => [
									__props.handle ? (openBlock(), createBlock(unref(DrawerHandle), {
										key: 0,
										"data-slot": "handle",
										class: ui.value.handle({ class: unref(uiProp)?.handle })
									}, null, 8, ["class"])) : createCommentVNode("", true),
									!__props.title && !slots.title || !__props.description && !slots.description || !!slots.content ? (openBlock(), createBlock(unref(VisuallyHidden), { key: 1 }, {
										default: withCtx(() => [!__props.title && !slots.title ? (openBlock(), createBlock(unref(DrawerTitle), { key: 0 })) : !!slots.content ? (openBlock(), createBlock(unref(DrawerTitle), { key: 1 }, {
											default: withCtx(() => [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(__props.title), 1)])]),
											_: 3
										})) : createCommentVNode("", true), !__props.description && !slots.description ? (openBlock(), createBlock(unref(DrawerDescription), { key: 2 })) : !!slots.content ? (openBlock(), createBlock(unref(DrawerDescription), { key: 3 }, {
											default: withCtx(() => [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(__props.description), 1)])]),
											_: 3
										})) : createCommentVNode("", true)]),
										_: 3
									})) : createCommentVNode("", true),
									renderSlot(_ctx.$slots, "content", {}, () => [createVNode("div", {
										"data-slot": "container",
										class: ui.value.container({ class: unref(uiProp)?.container })
									}, [
										!!slots.header || __props.title || !!slots.title || __props.description || !!slots.description ? (openBlock(), createBlock("div", {
											key: 0,
											"data-slot": "header",
											class: ui.value.header({ class: unref(uiProp)?.header })
										}, [renderSlot(_ctx.$slots, "header", {}, () => [__props.title || !!slots.title ? (openBlock(), createBlock(unref(DrawerTitle), {
											key: 0,
											"data-slot": "title",
											class: ui.value.title({ class: unref(uiProp)?.title })
										}, {
											default: withCtx(() => [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(__props.title), 1)])]),
											_: 3
										}, 8, ["class"])) : createCommentVNode("", true), __props.description || !!slots.description ? (openBlock(), createBlock(unref(DrawerDescription), {
											key: 1,
											"data-slot": "description",
											class: ui.value.description({ class: unref(uiProp)?.description })
										}, {
											default: withCtx(() => [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(__props.description), 1)])]),
											_: 3
										}, 8, ["class"])) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true),
										!!slots.body ? (openBlock(), createBlock("div", {
											key: 1,
											"data-slot": "body",
											class: ui.value.body({ class: unref(uiProp)?.body })
										}, [renderSlot(_ctx.$slots, "body")], 2)) : createCommentVNode("", true),
										!!slots.footer ? (openBlock(), createBlock("div", {
											key: 2,
											"data-slot": "footer",
											class: ui.value.footer({ class: unref(uiProp)?.footer })
										}, [renderSlot(_ctx.$slots, "footer")], 2)) : createCommentVNode("", true)
									], 2)])
								]),
								_: 3
							}, 16, ["class"])]),
							_: 3
						})]),
						_: 3
					}, 16)];
				}),
				_: 3
			}), _parent);
		};
	}
};
var _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Drawer.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
//#endregion
export { _sfc_main as t };

//# sourceMappingURL=Drawer-xm5bIe9t.js.map