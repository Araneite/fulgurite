import { C as useComponentIcons, D as useLocale, F as useAppConfig, N as omit, S as useFieldGroup, a as _sfc_main$24, b as FieldGroupReset, c as _sfc_main$20, i as _sfc_main$23, j as isArrayOfArray, k as get, l as pickLinkProps, n as usePortal, o as _sfc_main$21, r as _sfc_main$22, s as _sfc_main$25, u as tv, w as useComponentUI, x as fieldGroupInjectionKey, y as useFormField } from "./usePortal-DZb6nPjI.js";
import { n as _sfc_main$27, r as _sfc_main$26, t as _sfc_main$28 } from "./Form-DAQH5xxu.js";
import { t as _sfc_main$29 } from "./Alert-LEzzgm8x.js";
import { n as useToast$1 } from "./useToast-itvNvMq-.js";
import { t as _sfc_main$30 } from "./Progress-Cl7OHCpt.js";
import { t as _sfc_main$31 } from "./App-BCJnMUgD.js";
import { n as useResolvedVariants, r as _sfc_main$33, t as _sfc_main$32 } from "./Select-Yh80p2Xl.js";
import { t as _plugin_vue_export_helper_default } from "./_plugin-vue_export-helper-sK8SLxpB.js";
import { i as _sfc_main$35, n as _sfc_main$34, r as useFilter$1, t as UserQuickCreateForm_default } from "./UserQuickCreateForm-DF3WcUz2.js";
import { n as _sfc_main$36, r as _sfc_main$37 } from "./PhoneInput-aIWeFFh9.js";
import { t as pointerDownOutside } from "./overlay-DvxSEWvQ.js";
import { n as _sfc_main$38, t as UserDetailsDrawer_default } from "./UserDetailsDrawer-DinJzwg2.js";
import { n as _sfc_main$39, t as _sfc_main$40 } from "./Tooltip-_q5JW3sO.js";
import { n as isFieldDirty, t as UserQuickEditForm_default } from "./UserQuickEditForm-BAcXzIN6.js";
import { t as UserDrawerShell_default } from "./UserDrawerShell-BgMobjON.js";
import { Link, router, useForm, usePage } from "@inertiajs/vue3";
import { ssrGetDirectiveProps, ssrInterpolate, ssrRenderAttr, ssrRenderAttrs, ssrRenderClass, ssrRenderComponent, ssrRenderList, ssrRenderSlot, ssrRenderStyle, ssrRenderVNode } from "vue/server-renderer";
import { Fragment, computed, createBlock, createCommentVNode, createSlots, createTextVNode, createVNode, defineComponent, h, mergeModels, mergeProps, nextTick, onBeforeUnmount, onMounted, openBlock, provide, ref, renderList, renderSlot, resolveComponent, resolveDynamicComponent, toDisplayString, toHandlers, toRef, toValue, unref, useAttrs, useId, useModel, useSSRContext, useSlots, useTemplateRef, vShow, watch, withCtx, withDirectives, withModifiers } from "vue";
import { defu } from "defu";
import { AccordionContent, AccordionHeader, AccordionItem, AccordionRoot, AccordionTrigger, CheckboxGroupRoot, CheckboxIndicator, CheckboxRoot, ContextMenuRoot, ContextMenuTrigger, DialogClose, DialogContent, DialogDescription, DialogOverlay, DialogPortal, DialogRoot, DialogTitle, DialogTrigger, DropdownMenuArrow, DropdownMenuRoot, DropdownMenuTrigger, Label, NumberFieldDecrement, NumberFieldIncrement, NumberFieldInput, NumberFieldRoot, PaginationEllipsis, PaginationFirst, PaginationLast, PaginationList, PaginationListItem, PaginationNext, PaginationPrev, PaginationRoot, Primitive, Separator, TabsContent, TabsIndicator, TabsList, TabsRoot, TabsTrigger, VisuallyHidden, useForwardProps, useForwardPropsEmits } from "reka-ui";
import { createRef, createReusableTemplate, reactiveOmit, reactivePick, useClipboard, useVModel } from "@vueuse/core";
import { Passkeys } from "@laravel/passkeys";
import { Calendar, ContextMenu, DateField, DateRangeField, DropdownMenu, RangeCalendar } from "reka-ui/namespaced";
import { defineShortcuts, extractShortcuts, useToast } from "@nuxt/ui/composables";
import { fromDate, getLocalTimeZone, toCalendarDate, today } from "@internationalized/date";
import { vMaska } from "maska/vue";
import { en, en_gb, fr } from "@nuxt/ui/locale";
import { upperFirst } from "scule";
import { FlexRender, getCoreRowModel, getExpandedRowModel, getFilteredRowModel, getSortedRowModel, useVueTable } from "@tanstack/vue-table";
import { useVirtualizer } from "@tanstack/vue-virtual";
import { getWeekNumber } from "reka-ui/date";
//#region virtual:nuxt-ui-templates/ui/separator.ts
var separator_default = {
	"slots": {
		"root": "flex items-center align-center text-center",
		"border": "",
		"container": "font-medium text-default flex",
		"icon": "shrink-0 size-5",
		"avatar": "shrink-0",
		"avatarSize": "2xs",
		"label": "text-sm"
	},
	"variants": {
		"color": {
			"primary": { "border": "border-primary" },
			"secondary": { "border": "border-secondary" },
			"success": { "border": "border-success" },
			"info": { "border": "border-info" },
			"warning": { "border": "border-warning" },
			"error": { "border": "border-error" },
			"neutral": { "border": "border-default" }
		},
		"orientation": {
			"horizontal": {
				"root": "w-full flex-row",
				"border": "w-full",
				"container": "mx-3 whitespace-nowrap"
			},
			"vertical": {
				"root": "h-full flex-col",
				"border": "h-full",
				"container": "my-2"
			}
		},
		"size": {
			"xs": "",
			"sm": "",
			"md": "",
			"lg": "",
			"xl": ""
		},
		"type": {
			"solid": { "border": "border-solid" },
			"dashed": { "border": "border-dashed" },
			"dotted": { "border": "border-dotted" }
		}
	},
	"compoundVariants": [
		{
			"orientation": "horizontal",
			"size": "xs",
			"class": { "border": "border-t" }
		},
		{
			"orientation": "horizontal",
			"size": "sm",
			"class": { "border": "border-t-[2px]" }
		},
		{
			"orientation": "horizontal",
			"size": "md",
			"class": { "border": "border-t-[3px]" }
		},
		{
			"orientation": "horizontal",
			"size": "lg",
			"class": { "border": "border-t-[4px]" }
		},
		{
			"orientation": "horizontal",
			"size": "xl",
			"class": { "border": "border-t-[5px]" }
		},
		{
			"orientation": "vertical",
			"size": "xs",
			"class": { "border": "border-s" }
		},
		{
			"orientation": "vertical",
			"size": "sm",
			"class": { "border": "border-s-[2px]" }
		},
		{
			"orientation": "vertical",
			"size": "md",
			"class": { "border": "border-s-[3px]" }
		},
		{
			"orientation": "vertical",
			"size": "lg",
			"class": { "border": "border-s-[4px]" }
		},
		{
			"orientation": "vertical",
			"size": "xl",
			"class": { "border": "border-s-[5px]" }
		}
	],
	"defaultVariants": {
		"color": "neutral",
		"size": "xs",
		"type": "solid"
	}
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Separator.vue
var _sfc_main$19 = {
	__name: "Separator",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false
		},
		label: {
			type: String,
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
		size: {
			type: null,
			required: false
		},
		type: {
			type: null,
			required: false
		},
		orientation: {
			type: null,
			required: false,
			default: "horizontal"
		},
		class: {
			type: null,
			required: false
		},
		ui: {
			type: null,
			required: false
		},
		decorative: {
			type: Boolean,
			required: false
		}
	},
	setup(__props) {
		const props = __props;
		const slots = useSlots();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("separator", props);
		const rootProps = useForwardProps(reactivePick(props, "as", "decorative", "orientation"));
		const ui = computed(() => tv({
			extend: tv(separator_default),
			...appConfig.ui?.separator || {}
		})({
			color: props.color,
			orientation: props.orientation,
			size: props.size,
			type: props.type
		}));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(Separator), mergeProps(unref(rootProps), {
				"data-slot": "root",
				class: ui.value.root({ class: [unref(uiProp)?.root, props.class] })
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div data-slot="border" class="${ssrRenderClass(ui.value.border({ class: unref(uiProp)?.border }))}"${_scopeId}></div>`);
						if (__props.label || __props.icon || __props.avatar || !!slots.default) {
							_push(`<!--[--><div data-slot="container" class="${ssrRenderClass(ui.value.container({ class: unref(uiProp)?.container }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "default", { ui: ui.value }, () => {
								if (__props.label) _push(`<span data-slot="label" class="${ssrRenderClass(ui.value.label({ class: unref(uiProp)?.label }))}"${_scopeId}>${ssrInterpolate(__props.label)}</span>`);
								else if (__props.icon) _push(ssrRenderComponent(_sfc_main$20, {
									name: __props.icon,
									"data-slot": "icon",
									class: ui.value.icon({ class: unref(uiProp)?.icon })
								}, null, _parent, _scopeId));
								else if (__props.avatar) _push(ssrRenderComponent(_sfc_main$21, mergeProps({ size: unref(uiProp)?.avatarSize || ui.value.avatarSize() }, __props.avatar, {
									"data-slot": "avatar",
									class: ui.value.avatar({ class: unref(uiProp)?.avatar })
								}), null, _parent, _scopeId));
								else _push(`<!---->`);
							}, _push, _parent, _scopeId);
							_push(`</div><div data-slot="border" class="${ssrRenderClass(ui.value.border({ class: unref(uiProp)?.border }))}"${_scopeId}></div><!--]-->`);
						} else _push(`<!---->`);
					} else return [createVNode("div", {
						"data-slot": "border",
						class: ui.value.border({ class: unref(uiProp)?.border })
					}, null, 2), __props.label || __props.icon || __props.avatar || !!slots.default ? (openBlock(), createBlock(Fragment, { key: 0 }, [createVNode("div", {
						"data-slot": "container",
						class: ui.value.container({ class: unref(uiProp)?.container })
					}, [renderSlot(_ctx.$slots, "default", { ui: ui.value }, () => [__props.label ? (openBlock(), createBlock("span", {
						key: 0,
						"data-slot": "label",
						class: ui.value.label({ class: unref(uiProp)?.label })
					}, toDisplayString(__props.label), 3)) : __props.icon ? (openBlock(), createBlock(_sfc_main$20, {
						key: 1,
						name: __props.icon,
						"data-slot": "icon",
						class: ui.value.icon({ class: unref(uiProp)?.icon })
					}, null, 8, ["name", "class"])) : __props.avatar ? (openBlock(), createBlock(_sfc_main$21, mergeProps({
						key: 2,
						size: unref(uiProp)?.avatarSize || ui.value.avatarSize()
					}, __props.avatar, {
						"data-slot": "avatar",
						class: ui.value.avatar({ class: unref(uiProp)?.avatar })
					}), null, 16, ["size", "class"])) : createCommentVNode("", true)])], 2), createVNode("div", {
						"data-slot": "border",
						class: ui.value.border({ class: unref(uiProp)?.border })
					}, null, 2)], 64)) : createCommentVNode("", true)];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$28 = _sfc_main$19.setup;
_sfc_main$19.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Separator.vue");
	return _sfc_setup$28 ? _sfc_setup$28(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/field-group.ts
var field_group_default = {
	"base": "relative",
	"variants": {
		"size": {
			"xs": "",
			"sm": "",
			"md": "",
			"lg": "",
			"xl": ""
		},
		"orientation": {
			"horizontal": "inline-flex -space-x-px",
			"vertical": "flex flex-col -space-y-px"
		}
	}
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/FieldGroup.vue
var _sfc_main$18 = {
	__name: "FieldGroup",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false
		},
		size: {
			type: null,
			required: false
		},
		orientation: {
			type: null,
			required: false,
			default: "horizontal"
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
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("fieldGroup", props);
		const ui = computed(() => tv({
			extend: tv(field_group_default),
			...appConfig.ui?.fieldGroup || {}
		}));
		provide(fieldGroupInjectionKey, computed(() => ({
			orientation: props.orientation,
			size: props.size
		})));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(Primitive), mergeProps({
				as: __props.as,
				"data-orientation": __props.orientation,
				class: ui.value({
					orientation: __props.orientation,
					class: [unref(uiProp)?.base, props.class]
				})
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
					else return [renderSlot(_ctx.$slots, "default")];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$27 = _sfc_main$18.setup;
_sfc_main$18.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/FieldGroup.vue");
	return _sfc_setup$27 ? _sfc_setup$27(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/accordion.ts
var accordion_default = {
	"slots": {
		"root": "w-full",
		"item": "border-b border-default last:border-b-0",
		"header": "flex",
		"trigger": "group flex-1 flex items-center gap-1.5 font-medium text-sm py-3.5 focus-visible:outline-primary min-w-0",
		"content": "data-[state=open]:animate-[accordion-down_200ms_ease-out] data-[state=closed]:animate-[accordion-up_200ms_ease-out] overflow-hidden focus:outline-none",
		"body": "text-sm pb-3.5",
		"leadingIcon": "shrink-0 size-5",
		"trailingIcon": "shrink-0 size-5 ms-auto group-data-[state=open]:rotate-180 transition-transform duration-200",
		"label": "text-start break-words"
	},
	"variants": { "disabled": { "true": { "trigger": "cursor-not-allowed opacity-75" } } }
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Accordion.vue
var _sfc_main$17 = {
	__name: "Accordion",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false
		},
		items: {
			type: Array,
			required: false
		},
		trailingIcon: {
			type: null,
			required: false
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
		class: {
			type: null,
			required: false
		},
		ui: {
			type: Object,
			required: false
		},
		collapsible: {
			type: Boolean,
			required: false,
			default: true
		},
		defaultValue: {
			type: null,
			required: false
		},
		modelValue: {
			type: null,
			required: false
		},
		type: {
			type: String,
			required: false,
			default: "single"
		},
		disabled: {
			type: Boolean,
			required: false
		},
		unmountOnHide: {
			type: Boolean,
			required: false,
			default: true
		}
	},
	emits: ["update:modelValue"],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const slots = useSlots();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("accordion", props);
		const rootProps = useForwardPropsEmits(reactivePick(props, "as", "collapsible", "defaultValue", "disabled", "modelValue", "unmountOnHide"), emits);
		const ui = computed(() => tv({
			extend: tv(accordion_default),
			...appConfig.ui?.accordion || {}
		})({ disabled: props.disabled }));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(AccordionRoot), mergeProps(unref(rootProps), {
				type: __props.type,
				"data-slot": "root",
				class: ui.value.root({ class: [unref(uiProp)?.root, props.class] })
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<!--[-->`);
						ssrRenderList(props.items, (item, index) => {
							_push(ssrRenderComponent(unref(AccordionItem), {
								key: unref(get)(item, props.valueKey) ?? index,
								value: unref(get)(item, props.valueKey) ?? String(index),
								disabled: item.disabled,
								"data-slot": "item",
								class: ui.value.item({ class: [
									unref(uiProp)?.item,
									item.ui?.item,
									item.class
								] })
							}, {
								default: withCtx(({ open }, _push, _parent, _scopeId) => {
									if (_push) {
										_push(ssrRenderComponent(unref(AccordionHeader), {
											as: "div",
											"data-slot": "header",
											class: ui.value.header({ class: [unref(uiProp)?.header, item.ui?.header] })
										}, {
											default: withCtx((_, _push, _parent, _scopeId) => {
												if (_push) _push(ssrRenderComponent(unref(AccordionTrigger), {
													"data-slot": "trigger",
													class: ui.value.trigger({
														class: [unref(uiProp)?.trigger, item.ui?.trigger],
														disabled: item.disabled
													})
												}, {
													default: withCtx((_, _push, _parent, _scopeId) => {
														if (_push) {
															ssrRenderSlot(_ctx.$slots, "leading", {
																item,
																index,
																open,
																ui: ui.value
															}, () => {
																if (item.icon) _push(ssrRenderComponent(_sfc_main$20, {
																	name: item.icon,
																	"data-slot": "leadingIcon",
																	class: ui.value.leadingIcon({ class: [unref(uiProp)?.leadingIcon, item?.ui?.leadingIcon] })
																}, null, _parent, _scopeId));
																else _push(`<!---->`);
															}, _push, _parent, _scopeId);
															if (unref(get)(item, props.labelKey) || !!slots.default) {
																_push(`<span data-slot="label" class="${ssrRenderClass(ui.value.label({ class: [unref(uiProp)?.label, item.ui?.label] }))}"${_scopeId}>`);
																ssrRenderSlot(_ctx.$slots, "default", {
																	item,
																	index,
																	open
																}, () => {
																	_push(`${ssrInterpolate(unref(get)(item, props.labelKey))}`);
																}, _push, _parent, _scopeId);
																_push(`</span>`);
															} else _push(`<!---->`);
															ssrRenderSlot(_ctx.$slots, "trailing", {
																item,
																index,
																open,
																ui: ui.value
															}, () => {
																_push(ssrRenderComponent(_sfc_main$20, {
																	name: item.trailingIcon || __props.trailingIcon || unref(appConfig).ui.icons.chevronDown,
																	"data-slot": "trailingIcon",
																	class: ui.value.trailingIcon({ class: [unref(uiProp)?.trailingIcon, item.ui?.trailingIcon] })
																}, null, _parent, _scopeId));
															}, _push, _parent, _scopeId);
														} else return [
															renderSlot(_ctx.$slots, "leading", {
																item,
																index,
																open,
																ui: ui.value
															}, () => [item.icon ? (openBlock(), createBlock(_sfc_main$20, {
																key: 0,
																name: item.icon,
																"data-slot": "leadingIcon",
																class: ui.value.leadingIcon({ class: [unref(uiProp)?.leadingIcon, item?.ui?.leadingIcon] })
															}, null, 8, ["name", "class"])) : createCommentVNode("", true)]),
															unref(get)(item, props.labelKey) || !!slots.default ? (openBlock(), createBlock("span", {
																key: 0,
																"data-slot": "label",
																class: ui.value.label({ class: [unref(uiProp)?.label, item.ui?.label] })
															}, [renderSlot(_ctx.$slots, "default", {
																item,
																index,
																open
															}, () => [createTextVNode(toDisplayString(unref(get)(item, props.labelKey)), 1)])], 2)) : createCommentVNode("", true),
															renderSlot(_ctx.$slots, "trailing", {
																item,
																index,
																open,
																ui: ui.value
															}, () => [createVNode(_sfc_main$20, {
																name: item.trailingIcon || __props.trailingIcon || unref(appConfig).ui.icons.chevronDown,
																"data-slot": "trailingIcon",
																class: ui.value.trailingIcon({ class: [unref(uiProp)?.trailingIcon, item.ui?.trailingIcon] })
															}, null, 8, ["name", "class"])])
														];
													}),
													_: 2
												}, _parent, _scopeId));
												else return [createVNode(unref(AccordionTrigger), {
													"data-slot": "trigger",
													class: ui.value.trigger({
														class: [unref(uiProp)?.trigger, item.ui?.trigger],
														disabled: item.disabled
													})
												}, {
													default: withCtx(() => [
														renderSlot(_ctx.$slots, "leading", {
															item,
															index,
															open,
															ui: ui.value
														}, () => [item.icon ? (openBlock(), createBlock(_sfc_main$20, {
															key: 0,
															name: item.icon,
															"data-slot": "leadingIcon",
															class: ui.value.leadingIcon({ class: [unref(uiProp)?.leadingIcon, item?.ui?.leadingIcon] })
														}, null, 8, ["name", "class"])) : createCommentVNode("", true)]),
														unref(get)(item, props.labelKey) || !!slots.default ? (openBlock(), createBlock("span", {
															key: 0,
															"data-slot": "label",
															class: ui.value.label({ class: [unref(uiProp)?.label, item.ui?.label] })
														}, [renderSlot(_ctx.$slots, "default", {
															item,
															index,
															open
														}, () => [createTextVNode(toDisplayString(unref(get)(item, props.labelKey)), 1)])], 2)) : createCommentVNode("", true),
														renderSlot(_ctx.$slots, "trailing", {
															item,
															index,
															open,
															ui: ui.value
														}, () => [createVNode(_sfc_main$20, {
															name: item.trailingIcon || __props.trailingIcon || unref(appConfig).ui.icons.chevronDown,
															"data-slot": "trailingIcon",
															class: ui.value.trailingIcon({ class: [unref(uiProp)?.trailingIcon, item.ui?.trailingIcon] })
														}, null, 8, ["name", "class"])])
													]),
													_: 2
												}, 1032, ["class"])];
											}),
											_: 2
										}, _parent, _scopeId));
										if (item.content || !!slots.content || item.slot && !!slots[item.slot] || !!slots.body || item.slot && !!slots[`${item.slot}-body`]) _push(ssrRenderComponent(unref(AccordionContent), {
											"data-slot": "content",
											class: ui.value.content({ class: [unref(uiProp)?.content, item.ui?.content] })
										}, {
											default: withCtx((_, _push, _parent, _scopeId) => {
												if (_push) ssrRenderSlot(_ctx.$slots, item.slot || "content", {
													item,
													index,
													open,
													ui: ui.value
												}, () => {
													_push(`<div data-slot="body" class="${ssrRenderClass(ui.value.body({ class: [unref(uiProp)?.body, item.ui?.body] }))}"${_scopeId}>`);
													ssrRenderSlot(_ctx.$slots, item.slot ? `${item.slot}-body` : "body", {
														item,
														index,
														open,
														ui: ui.value
													}, () => {
														_push(`${ssrInterpolate(item.content)}`);
													}, _push, _parent, _scopeId);
													_push(`</div>`);
												}, _push, _parent, _scopeId);
												else return [renderSlot(_ctx.$slots, item.slot || "content", {
													item,
													index,
													open,
													ui: ui.value
												}, () => [createVNode("div", {
													"data-slot": "body",
													class: ui.value.body({ class: [unref(uiProp)?.body, item.ui?.body] })
												}, [renderSlot(_ctx.$slots, item.slot ? `${item.slot}-body` : "body", {
													item,
													index,
													open,
													ui: ui.value
												}, () => [createTextVNode(toDisplayString(item.content), 1)])], 2)])];
											}),
											_: 2
										}, _parent, _scopeId));
										else _push(`<!---->`);
									} else return [createVNode(unref(AccordionHeader), {
										as: "div",
										"data-slot": "header",
										class: ui.value.header({ class: [unref(uiProp)?.header, item.ui?.header] })
									}, {
										default: withCtx(() => [createVNode(unref(AccordionTrigger), {
											"data-slot": "trigger",
											class: ui.value.trigger({
												class: [unref(uiProp)?.trigger, item.ui?.trigger],
												disabled: item.disabled
											})
										}, {
											default: withCtx(() => [
												renderSlot(_ctx.$slots, "leading", {
													item,
													index,
													open,
													ui: ui.value
												}, () => [item.icon ? (openBlock(), createBlock(_sfc_main$20, {
													key: 0,
													name: item.icon,
													"data-slot": "leadingIcon",
													class: ui.value.leadingIcon({ class: [unref(uiProp)?.leadingIcon, item?.ui?.leadingIcon] })
												}, null, 8, ["name", "class"])) : createCommentVNode("", true)]),
												unref(get)(item, props.labelKey) || !!slots.default ? (openBlock(), createBlock("span", {
													key: 0,
													"data-slot": "label",
													class: ui.value.label({ class: [unref(uiProp)?.label, item.ui?.label] })
												}, [renderSlot(_ctx.$slots, "default", {
													item,
													index,
													open
												}, () => [createTextVNode(toDisplayString(unref(get)(item, props.labelKey)), 1)])], 2)) : createCommentVNode("", true),
												renderSlot(_ctx.$slots, "trailing", {
													item,
													index,
													open,
													ui: ui.value
												}, () => [createVNode(_sfc_main$20, {
													name: item.trailingIcon || __props.trailingIcon || unref(appConfig).ui.icons.chevronDown,
													"data-slot": "trailingIcon",
													class: ui.value.trailingIcon({ class: [unref(uiProp)?.trailingIcon, item.ui?.trailingIcon] })
												}, null, 8, ["name", "class"])])
											]),
											_: 2
										}, 1032, ["class"])]),
										_: 2
									}, 1032, ["class"]), item.content || !!slots.content || item.slot && !!slots[item.slot] || !!slots.body || item.slot && !!slots[`${item.slot}-body`] ? (openBlock(), createBlock(unref(AccordionContent), {
										key: 0,
										"data-slot": "content",
										class: ui.value.content({ class: [unref(uiProp)?.content, item.ui?.content] })
									}, {
										default: withCtx(() => [renderSlot(_ctx.$slots, item.slot || "content", {
											item,
											index,
											open,
											ui: ui.value
										}, () => [createVNode("div", {
											"data-slot": "body",
											class: ui.value.body({ class: [unref(uiProp)?.body, item.ui?.body] })
										}, [renderSlot(_ctx.$slots, item.slot ? `${item.slot}-body` : "body", {
											item,
											index,
											open,
											ui: ui.value
										}, () => [createTextVNode(toDisplayString(item.content), 1)])], 2)])]),
										_: 2
									}, 1032, ["class"])) : createCommentVNode("", true)];
								}),
								_: 2
							}, _parent, _scopeId));
						});
						_push(`<!--]-->`);
					} else return [(openBlock(true), createBlock(Fragment, null, renderList(props.items, (item, index) => {
						return openBlock(), createBlock(unref(AccordionItem), {
							key: unref(get)(item, props.valueKey) ?? index,
							value: unref(get)(item, props.valueKey) ?? String(index),
							disabled: item.disabled,
							"data-slot": "item",
							class: ui.value.item({ class: [
								unref(uiProp)?.item,
								item.ui?.item,
								item.class
							] })
						}, {
							default: withCtx(({ open }) => [createVNode(unref(AccordionHeader), {
								as: "div",
								"data-slot": "header",
								class: ui.value.header({ class: [unref(uiProp)?.header, item.ui?.header] })
							}, {
								default: withCtx(() => [createVNode(unref(AccordionTrigger), {
									"data-slot": "trigger",
									class: ui.value.trigger({
										class: [unref(uiProp)?.trigger, item.ui?.trigger],
										disabled: item.disabled
									})
								}, {
									default: withCtx(() => [
										renderSlot(_ctx.$slots, "leading", {
											item,
											index,
											open,
											ui: ui.value
										}, () => [item.icon ? (openBlock(), createBlock(_sfc_main$20, {
											key: 0,
											name: item.icon,
											"data-slot": "leadingIcon",
											class: ui.value.leadingIcon({ class: [unref(uiProp)?.leadingIcon, item?.ui?.leadingIcon] })
										}, null, 8, ["name", "class"])) : createCommentVNode("", true)]),
										unref(get)(item, props.labelKey) || !!slots.default ? (openBlock(), createBlock("span", {
											key: 0,
											"data-slot": "label",
											class: ui.value.label({ class: [unref(uiProp)?.label, item.ui?.label] })
										}, [renderSlot(_ctx.$slots, "default", {
											item,
											index,
											open
										}, () => [createTextVNode(toDisplayString(unref(get)(item, props.labelKey)), 1)])], 2)) : createCommentVNode("", true),
										renderSlot(_ctx.$slots, "trailing", {
											item,
											index,
											open,
											ui: ui.value
										}, () => [createVNode(_sfc_main$20, {
											name: item.trailingIcon || __props.trailingIcon || unref(appConfig).ui.icons.chevronDown,
											"data-slot": "trailingIcon",
											class: ui.value.trailingIcon({ class: [unref(uiProp)?.trailingIcon, item.ui?.trailingIcon] })
										}, null, 8, ["name", "class"])])
									]),
									_: 2
								}, 1032, ["class"])]),
								_: 2
							}, 1032, ["class"]), item.content || !!slots.content || item.slot && !!slots[item.slot] || !!slots.body || item.slot && !!slots[`${item.slot}-body`] ? (openBlock(), createBlock(unref(AccordionContent), {
								key: 0,
								"data-slot": "content",
								class: ui.value.content({ class: [unref(uiProp)?.content, item.ui?.content] })
							}, {
								default: withCtx(() => [renderSlot(_ctx.$slots, item.slot || "content", {
									item,
									index,
									open,
									ui: ui.value
								}, () => [createVNode("div", {
									"data-slot": "body",
									class: ui.value.body({ class: [unref(uiProp)?.body, item.ui?.body] })
								}, [renderSlot(_ctx.$slots, item.slot ? `${item.slot}-body` : "body", {
									item,
									index,
									open,
									ui: ui.value
								}, () => [createTextVNode(toDisplayString(item.content), 1)])], 2)])]),
								_: 2
							}, 1032, ["class"])) : createCommentVNode("", true)]),
							_: 2
						}, 1032, [
							"value",
							"disabled",
							"class"
						]);
					}), 128))];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$26 = _sfc_main$17.setup;
_sfc_main$17.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Accordion.vue");
	return _sfc_setup$26 ? _sfc_setup$26(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/tabs.ts
var tabs_default = {
	"slots": {
		"root": "flex items-center gap-2",
		"list": "relative flex p-1 group",
		"indicator": "absolute transition-[translate,width] duration-200",
		"trigger": ["group relative inline-flex items-center min-w-0 data-[state=inactive]:text-muted hover:data-[state=inactive]:not-disabled:text-default font-medium rounded-md disabled:cursor-not-allowed disabled:opacity-75", "transition-colors"],
		"leadingIcon": "shrink-0",
		"leadingAvatar": "shrink-0",
		"leadingAvatarSize": "",
		"label": "truncate",
		"trailingBadge": "shrink-0",
		"trailingBadgeSize": "sm",
		"content": "focus:outline-none w-full"
	},
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
			"pill": {
				"list": "bg-elevated rounded-lg",
				"trigger": "grow",
				"indicator": "rounded-md shadow-xs"
			},
			"link": {
				"list": "border-default",
				"indicator": "rounded-full",
				"trigger": "focus:outline-none"
			}
		},
		"orientation": {
			"horizontal": {
				"root": "flex-col",
				"list": "w-full",
				"indicator": "left-0 w-(--reka-tabs-indicator-size) translate-x-(--reka-tabs-indicator-position)",
				"trigger": "justify-center"
			},
			"vertical": {
				"list": "flex-col",
				"indicator": "top-0 h-(--reka-tabs-indicator-size) translate-y-(--reka-tabs-indicator-position)"
			}
		},
		"size": {
			"xs": {
				"trigger": "px-2 py-1 text-xs gap-1",
				"leadingIcon": "size-4",
				"leadingAvatarSize": "3xs"
			},
			"sm": {
				"trigger": "px-2.5 py-1.5 text-xs gap-1.5",
				"leadingIcon": "size-4",
				"leadingAvatarSize": "3xs"
			},
			"md": {
				"trigger": "px-3 py-1.5 text-sm gap-1.5",
				"leadingIcon": "size-5",
				"leadingAvatarSize": "2xs"
			},
			"lg": {
				"trigger": "px-3 py-2 text-sm gap-2",
				"leadingIcon": "size-5",
				"leadingAvatarSize": "2xs"
			},
			"xl": {
				"trigger": "px-3 py-2 text-base gap-2",
				"leadingIcon": "size-6",
				"leadingAvatarSize": "xs"
			}
		}
	},
	"compoundVariants": [
		{
			"orientation": "horizontal",
			"variant": "pill",
			"class": { "indicator": "inset-y-1" }
		},
		{
			"orientation": "horizontal",
			"variant": "link",
			"class": {
				"list": "border-b -mb-px",
				"indicator": "-bottom-px h-px"
			}
		},
		{
			"orientation": "vertical",
			"variant": "pill",
			"class": {
				"indicator": "inset-x-1",
				"list": "items-center"
			}
		},
		{
			"orientation": "vertical",
			"variant": "link",
			"class": {
				"list": "border-s -ms-px",
				"indicator": "-start-px w-px"
			}
		},
		{
			"color": "primary",
			"variant": "pill",
			"class": {
				"indicator": "bg-primary",
				"trigger": "data-[state=active]:text-inverted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
			}
		},
		{
			"color": "secondary",
			"variant": "pill",
			"class": {
				"indicator": "bg-secondary",
				"trigger": "data-[state=active]:text-inverted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary"
			}
		},
		{
			"color": "success",
			"variant": "pill",
			"class": {
				"indicator": "bg-success",
				"trigger": "data-[state=active]:text-inverted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-success"
			}
		},
		{
			"color": "info",
			"variant": "pill",
			"class": {
				"indicator": "bg-info",
				"trigger": "data-[state=active]:text-inverted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-info"
			}
		},
		{
			"color": "warning",
			"variant": "pill",
			"class": {
				"indicator": "bg-warning",
				"trigger": "data-[state=active]:text-inverted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-warning"
			}
		},
		{
			"color": "error",
			"variant": "pill",
			"class": {
				"indicator": "bg-error",
				"trigger": "data-[state=active]:text-inverted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-error"
			}
		},
		{
			"color": "neutral",
			"variant": "pill",
			"class": {
				"indicator": "bg-inverted",
				"trigger": "data-[state=active]:text-inverted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-inverted"
			}
		},
		{
			"color": "primary",
			"variant": "link",
			"class": {
				"indicator": "bg-primary",
				"trigger": "data-[state=active]:text-primary focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary"
			}
		},
		{
			"color": "secondary",
			"variant": "link",
			"class": {
				"indicator": "bg-secondary",
				"trigger": "data-[state=active]:text-secondary focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-secondary"
			}
		},
		{
			"color": "success",
			"variant": "link",
			"class": {
				"indicator": "bg-success",
				"trigger": "data-[state=active]:text-success focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-success"
			}
		},
		{
			"color": "info",
			"variant": "link",
			"class": {
				"indicator": "bg-info",
				"trigger": "data-[state=active]:text-info focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-info"
			}
		},
		{
			"color": "warning",
			"variant": "link",
			"class": {
				"indicator": "bg-warning",
				"trigger": "data-[state=active]:text-warning focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-warning"
			}
		},
		{
			"color": "error",
			"variant": "link",
			"class": {
				"indicator": "bg-error",
				"trigger": "data-[state=active]:text-error focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-error"
			}
		},
		{
			"color": "neutral",
			"variant": "link",
			"class": {
				"indicator": "bg-inverted",
				"trigger": "data-[state=active]:text-highlighted focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-inverted"
			}
		}
	],
	"defaultVariants": {
		"color": "primary",
		"variant": "pill",
		"size": "md"
	}
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Tabs.vue
var _sfc_main$16 = {
	__name: "Tabs",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false
		},
		items: {
			type: Array,
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
		orientation: {
			type: null,
			required: false,
			default: "horizontal"
		},
		content: {
			type: Boolean,
			required: false,
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
		class: {
			type: null,
			required: false
		},
		ui: {
			type: Object,
			required: false
		},
		defaultValue: {
			type: [String, Number],
			required: false,
			default: "0"
		},
		modelValue: {
			type: [String, Number],
			required: false
		},
		activationMode: {
			type: String,
			required: false
		},
		unmountOnHide: {
			type: Boolean,
			required: false,
			default: true
		}
	},
	emits: ["update:modelValue"],
	setup(__props, { expose: __expose, emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const slots = useSlots();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("tabs", props);
		const rootProps = useForwardPropsEmits(reactivePick(props, "as", "unmountOnHide"), emits);
		const ui = computed(() => tv({
			extend: tv(tabs_default),
			...appConfig.ui?.tabs || {}
		})({
			color: props.color,
			variant: props.variant,
			size: props.size,
			orientation: props.orientation
		}));
		const triggersRef = ref([]);
		function setTriggerRef(index, el) {
			triggersRef.value[index] = el;
		}
		__expose({ triggersRef });
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(TabsRoot), mergeProps(unref(rootProps), {
				"model-value": __props.modelValue,
				"default-value": __props.defaultValue,
				orientation: __props.orientation,
				"activation-mode": __props.activationMode,
				"data-slot": "root",
				class: ui.value.root({ class: [unref(uiProp)?.root, props.class] })
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(ssrRenderComponent(unref(TabsList), {
							"data-slot": "list",
							class: ui.value.list({ class: unref(uiProp)?.list })
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) {
									_push(ssrRenderComponent(unref(TabsIndicator), {
										"data-slot": "indicator",
										class: ui.value.indicator({ class: unref(uiProp)?.indicator })
									}, null, _parent, _scopeId));
									ssrRenderSlot(_ctx.$slots, "list-leading", {}, null, _push, _parent, _scopeId);
									_push(`<!--[-->`);
									ssrRenderList(__props.items, (item, index) => {
										_push(ssrRenderComponent(unref(TabsTrigger), {
											key: unref(get)(item, props.valueKey) ?? index,
											ref_for: true,
											ref: (el) => setTriggerRef(index, el),
											value: unref(get)(item, props.valueKey) ?? String(index),
											disabled: item.disabled,
											"data-slot": "trigger",
											class: ui.value.trigger({ class: [unref(uiProp)?.trigger, item.ui?.trigger] })
										}, {
											default: withCtx((_, _push, _parent, _scopeId) => {
												if (_push) {
													ssrRenderSlot(_ctx.$slots, "leading", {
														item,
														index,
														ui: ui.value
													}, () => {
														if (item.icon) _push(ssrRenderComponent(_sfc_main$20, {
															name: item.icon,
															"data-slot": "leadingIcon",
															class: ui.value.leadingIcon({ class: [unref(uiProp)?.leadingIcon, item.ui?.leadingIcon] })
														}, null, _parent, _scopeId));
														else if (item.avatar) _push(ssrRenderComponent(_sfc_main$21, mergeProps({ size: item.ui?.leadingAvatarSize || unref(uiProp)?.leadingAvatarSize || ui.value.leadingAvatarSize() }, { ref_for: true }, item.avatar, {
															"data-slot": "leadingAvatar",
															class: ui.value.leadingAvatar({ class: [unref(uiProp)?.leadingAvatar, item.ui?.leadingAvatar] })
														}), null, _parent, _scopeId));
														else _push(`<!---->`);
													}, _push, _parent, _scopeId);
													if (unref(get)(item, props.labelKey) || !!slots.default) {
														_push(`<span data-slot="label" class="${ssrRenderClass(ui.value.label({ class: [unref(uiProp)?.label, item.ui?.label] }))}"${_scopeId}>`);
														ssrRenderSlot(_ctx.$slots, "default", {
															item,
															index
														}, () => {
															_push(`${ssrInterpolate(unref(get)(item, props.labelKey))}`);
														}, _push, _parent, _scopeId);
														_push(`</span>`);
													} else _push(`<!---->`);
													ssrRenderSlot(_ctx.$slots, "trailing", {
														item,
														index,
														ui: ui.value
													}, () => {
														if (item.badge || item.badge === 0) _push(ssrRenderComponent(_sfc_main$38, mergeProps({
															color: "neutral",
															variant: "outline",
															size: item.ui?.trailingBadgeSize || unref(uiProp)?.trailingBadgeSize || ui.value.trailingBadgeSize()
														}, { ref_for: true }, typeof item.badge === "string" || typeof item.badge === "number" ? { label: item.badge } : item.badge, {
															"data-slot": "trailingBadge",
															class: ui.value.trailingBadge({ class: [unref(uiProp)?.trailingBadge, item.ui?.trailingBadge] })
														}), null, _parent, _scopeId));
														else _push(`<!---->`);
													}, _push, _parent, _scopeId);
												} else return [
													renderSlot(_ctx.$slots, "leading", {
														item,
														index,
														ui: ui.value
													}, () => [item.icon ? (openBlock(), createBlock(_sfc_main$20, {
														key: 0,
														name: item.icon,
														"data-slot": "leadingIcon",
														class: ui.value.leadingIcon({ class: [unref(uiProp)?.leadingIcon, item.ui?.leadingIcon] })
													}, null, 8, ["name", "class"])) : item.avatar ? (openBlock(), createBlock(_sfc_main$21, mergeProps({
														key: 1,
														size: item.ui?.leadingAvatarSize || unref(uiProp)?.leadingAvatarSize || ui.value.leadingAvatarSize()
													}, { ref_for: true }, item.avatar, {
														"data-slot": "leadingAvatar",
														class: ui.value.leadingAvatar({ class: [unref(uiProp)?.leadingAvatar, item.ui?.leadingAvatar] })
													}), null, 16, ["size", "class"])) : createCommentVNode("", true)]),
													unref(get)(item, props.labelKey) || !!slots.default ? (openBlock(), createBlock("span", {
														key: 0,
														"data-slot": "label",
														class: ui.value.label({ class: [unref(uiProp)?.label, item.ui?.label] })
													}, [renderSlot(_ctx.$slots, "default", {
														item,
														index
													}, () => [createTextVNode(toDisplayString(unref(get)(item, props.labelKey)), 1)])], 2)) : createCommentVNode("", true),
													renderSlot(_ctx.$slots, "trailing", {
														item,
														index,
														ui: ui.value
													}, () => [item.badge || item.badge === 0 ? (openBlock(), createBlock(_sfc_main$38, mergeProps({
														key: 0,
														color: "neutral",
														variant: "outline",
														size: item.ui?.trailingBadgeSize || unref(uiProp)?.trailingBadgeSize || ui.value.trailingBadgeSize()
													}, { ref_for: true }, typeof item.badge === "string" || typeof item.badge === "number" ? { label: item.badge } : item.badge, {
														"data-slot": "trailingBadge",
														class: ui.value.trailingBadge({ class: [unref(uiProp)?.trailingBadge, item.ui?.trailingBadge] })
													}), null, 16, ["size", "class"])) : createCommentVNode("", true)])
												];
											}),
											_: 2
										}, _parent, _scopeId));
									});
									_push(`<!--]-->`);
									ssrRenderSlot(_ctx.$slots, "list-trailing", {}, null, _push, _parent, _scopeId);
								} else return [
									createVNode(unref(TabsIndicator), {
										"data-slot": "indicator",
										class: ui.value.indicator({ class: unref(uiProp)?.indicator })
									}, null, 8, ["class"]),
									renderSlot(_ctx.$slots, "list-leading"),
									(openBlock(true), createBlock(Fragment, null, renderList(__props.items, (item, index) => {
										return openBlock(), createBlock(unref(TabsTrigger), {
											key: unref(get)(item, props.valueKey) ?? index,
											ref_for: true,
											ref: (el) => setTriggerRef(index, el),
											value: unref(get)(item, props.valueKey) ?? String(index),
											disabled: item.disabled,
											"data-slot": "trigger",
											class: ui.value.trigger({ class: [unref(uiProp)?.trigger, item.ui?.trigger] })
										}, {
											default: withCtx(() => [
												renderSlot(_ctx.$slots, "leading", {
													item,
													index,
													ui: ui.value
												}, () => [item.icon ? (openBlock(), createBlock(_sfc_main$20, {
													key: 0,
													name: item.icon,
													"data-slot": "leadingIcon",
													class: ui.value.leadingIcon({ class: [unref(uiProp)?.leadingIcon, item.ui?.leadingIcon] })
												}, null, 8, ["name", "class"])) : item.avatar ? (openBlock(), createBlock(_sfc_main$21, mergeProps({
													key: 1,
													size: item.ui?.leadingAvatarSize || unref(uiProp)?.leadingAvatarSize || ui.value.leadingAvatarSize()
												}, { ref_for: true }, item.avatar, {
													"data-slot": "leadingAvatar",
													class: ui.value.leadingAvatar({ class: [unref(uiProp)?.leadingAvatar, item.ui?.leadingAvatar] })
												}), null, 16, ["size", "class"])) : createCommentVNode("", true)]),
												unref(get)(item, props.labelKey) || !!slots.default ? (openBlock(), createBlock("span", {
													key: 0,
													"data-slot": "label",
													class: ui.value.label({ class: [unref(uiProp)?.label, item.ui?.label] })
												}, [renderSlot(_ctx.$slots, "default", {
													item,
													index
												}, () => [createTextVNode(toDisplayString(unref(get)(item, props.labelKey)), 1)])], 2)) : createCommentVNode("", true),
												renderSlot(_ctx.$slots, "trailing", {
													item,
													index,
													ui: ui.value
												}, () => [item.badge || item.badge === 0 ? (openBlock(), createBlock(_sfc_main$38, mergeProps({
													key: 0,
													color: "neutral",
													variant: "outline",
													size: item.ui?.trailingBadgeSize || unref(uiProp)?.trailingBadgeSize || ui.value.trailingBadgeSize()
												}, { ref_for: true }, typeof item.badge === "string" || typeof item.badge === "number" ? { label: item.badge } : item.badge, {
													"data-slot": "trailingBadge",
													class: ui.value.trailingBadge({ class: [unref(uiProp)?.trailingBadge, item.ui?.trailingBadge] })
												}), null, 16, ["size", "class"])) : createCommentVNode("", true)])
											]),
											_: 2
										}, 1032, [
											"value",
											"disabled",
											"class"
										]);
									}), 128)),
									renderSlot(_ctx.$slots, "list-trailing")
								];
							}),
							_: 3
						}, _parent, _scopeId));
						if (!!__props.content) {
							_push(`<!--[-->`);
							ssrRenderList(__props.items, (item, index) => {
								_push(ssrRenderComponent(unref(TabsContent), {
									key: unref(get)(item, props.valueKey) ?? index,
									value: unref(get)(item, props.valueKey) ?? String(index),
									"data-slot": "content",
									class: ui.value.content({ class: [
										unref(uiProp)?.content,
										item.ui?.content,
										item.class
									] })
								}, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) ssrRenderSlot(_ctx.$slots, item.slot || "content", {
											item,
											index,
											ui: ui.value
										}, () => {
											_push(`${ssrInterpolate(item.content)}`);
										}, _push, _parent, _scopeId);
										else return [renderSlot(_ctx.$slots, item.slot || "content", {
											item,
											index,
											ui: ui.value
										}, () => [createTextVNode(toDisplayString(item.content), 1)])];
									}),
									_: 2
								}, _parent, _scopeId));
							});
							_push(`<!--]-->`);
						} else _push(`<!---->`);
					} else return [createVNode(unref(TabsList), {
						"data-slot": "list",
						class: ui.value.list({ class: unref(uiProp)?.list })
					}, {
						default: withCtx(() => [
							createVNode(unref(TabsIndicator), {
								"data-slot": "indicator",
								class: ui.value.indicator({ class: unref(uiProp)?.indicator })
							}, null, 8, ["class"]),
							renderSlot(_ctx.$slots, "list-leading"),
							(openBlock(true), createBlock(Fragment, null, renderList(__props.items, (item, index) => {
								return openBlock(), createBlock(unref(TabsTrigger), {
									key: unref(get)(item, props.valueKey) ?? index,
									ref_for: true,
									ref: (el) => setTriggerRef(index, el),
									value: unref(get)(item, props.valueKey) ?? String(index),
									disabled: item.disabled,
									"data-slot": "trigger",
									class: ui.value.trigger({ class: [unref(uiProp)?.trigger, item.ui?.trigger] })
								}, {
									default: withCtx(() => [
										renderSlot(_ctx.$slots, "leading", {
											item,
											index,
											ui: ui.value
										}, () => [item.icon ? (openBlock(), createBlock(_sfc_main$20, {
											key: 0,
											name: item.icon,
											"data-slot": "leadingIcon",
											class: ui.value.leadingIcon({ class: [unref(uiProp)?.leadingIcon, item.ui?.leadingIcon] })
										}, null, 8, ["name", "class"])) : item.avatar ? (openBlock(), createBlock(_sfc_main$21, mergeProps({
											key: 1,
											size: item.ui?.leadingAvatarSize || unref(uiProp)?.leadingAvatarSize || ui.value.leadingAvatarSize()
										}, { ref_for: true }, item.avatar, {
											"data-slot": "leadingAvatar",
											class: ui.value.leadingAvatar({ class: [unref(uiProp)?.leadingAvatar, item.ui?.leadingAvatar] })
										}), null, 16, ["size", "class"])) : createCommentVNode("", true)]),
										unref(get)(item, props.labelKey) || !!slots.default ? (openBlock(), createBlock("span", {
											key: 0,
											"data-slot": "label",
											class: ui.value.label({ class: [unref(uiProp)?.label, item.ui?.label] })
										}, [renderSlot(_ctx.$slots, "default", {
											item,
											index
										}, () => [createTextVNode(toDisplayString(unref(get)(item, props.labelKey)), 1)])], 2)) : createCommentVNode("", true),
										renderSlot(_ctx.$slots, "trailing", {
											item,
											index,
											ui: ui.value
										}, () => [item.badge || item.badge === 0 ? (openBlock(), createBlock(_sfc_main$38, mergeProps({
											key: 0,
											color: "neutral",
											variant: "outline",
											size: item.ui?.trailingBadgeSize || unref(uiProp)?.trailingBadgeSize || ui.value.trailingBadgeSize()
										}, { ref_for: true }, typeof item.badge === "string" || typeof item.badge === "number" ? { label: item.badge } : item.badge, {
											"data-slot": "trailingBadge",
											class: ui.value.trailingBadge({ class: [unref(uiProp)?.trailingBadge, item.ui?.trailingBadge] })
										}), null, 16, ["size", "class"])) : createCommentVNode("", true)])
									]),
									_: 2
								}, 1032, [
									"value",
									"disabled",
									"class"
								]);
							}), 128)),
							renderSlot(_ctx.$slots, "list-trailing")
						]),
						_: 3
					}, 8, ["class"]), !!__props.content ? (openBlock(true), createBlock(Fragment, { key: 0 }, renderList(__props.items, (item, index) => {
						return openBlock(), createBlock(unref(TabsContent), {
							key: unref(get)(item, props.valueKey) ?? index,
							value: unref(get)(item, props.valueKey) ?? String(index),
							"data-slot": "content",
							class: ui.value.content({ class: [
								unref(uiProp)?.content,
								item.ui?.content,
								item.class
							] })
						}, {
							default: withCtx(() => [renderSlot(_ctx.$slots, item.slot || "content", {
								item,
								index,
								ui: ui.value
							}, () => [createTextVNode(toDisplayString(item.content), 1)])]),
							_: 2
						}, 1032, ["value", "class"]);
					}), 128)) : createCommentVNode("", true)];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$25 = _sfc_main$16.setup;
_sfc_main$16.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Tabs.vue");
	return _sfc_setup$25 ? _sfc_setup$25(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/input-number.ts
var input_number_default = {
	"slots": {
		"root": "relative inline-flex items-center",
		"base": ["w-full rounded-md border-0 placeholder:text-dimmed focus:outline-none disabled:cursor-not-allowed disabled:opacity-75", "transition-colors"],
		"increment": "absolute flex items-center",
		"decrement": "absolute flex items-center"
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
		"color": {
			"primary": "",
			"secondary": "",
			"success": "",
			"info": "",
			"warning": "",
			"error": "",
			"neutral": ""
		},
		"size": {
			"xs": "px-2 py-1 text-sm/4 gap-1",
			"sm": "px-2.5 py-1.5 text-sm/4 gap-1.5",
			"md": "px-2.5 py-1.5 text-base/5 gap-1.5",
			"lg": "px-3 py-2 text-base/5 gap-2",
			"xl": "px-3 py-2 text-base gap-2"
		},
		"variant": {
			"outline": "text-highlighted bg-default ring ring-inset ring-accented",
			"soft": "text-highlighted bg-elevated/50 hover:bg-elevated focus:bg-elevated disabled:bg-elevated/50",
			"subtle": "text-highlighted bg-elevated ring ring-inset ring-accented",
			"ghost": "text-highlighted bg-transparent hover:bg-elevated focus:bg-elevated disabled:bg-transparent dark:disabled:bg-transparent",
			"none": "text-highlighted bg-transparent"
		},
		"disabled": { "true": {
			"increment": "opacity-75 cursor-not-allowed",
			"decrement": "opacity-75 cursor-not-allowed"
		} },
		"orientation": {
			"horizontal": {
				"base": "text-center",
				"increment": "inset-y-0 end-0 pe-1",
				"decrement": "inset-y-0 start-0 ps-1"
			},
			"vertical": {
				"increment": "top-0 end-0 pe-1 [&>button]:py-0 scale-80",
				"decrement": "bottom-0 end-0 pe-1 [&>button]:py-0 scale-80"
			}
		},
		"highlight": { "true": "" },
		"fixed": { "false": "" },
		"increment": { "false": "" },
		"decrement": { "false": "" }
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
			"orientation": "horizontal",
			"decrement": false,
			"class": "text-start"
		},
		{
			"decrement": true,
			"size": "xs",
			"class": "ps-7"
		},
		{
			"decrement": true,
			"size": "sm",
			"class": "ps-8"
		},
		{
			"decrement": true,
			"size": "md",
			"class": "ps-9"
		},
		{
			"decrement": true,
			"size": "lg",
			"class": "ps-10"
		},
		{
			"decrement": true,
			"size": "xl",
			"class": "ps-11"
		},
		{
			"increment": true,
			"size": "xs",
			"class": "pe-7"
		},
		{
			"increment": true,
			"size": "sm",
			"class": "pe-8"
		},
		{
			"increment": true,
			"size": "md",
			"class": "pe-9"
		},
		{
			"increment": true,
			"size": "lg",
			"class": "pe-10"
		},
		{
			"increment": true,
			"size": "xl",
			"class": "pe-11"
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
//#region node_modules/@nuxt/ui/dist/runtime/components/InputNumber.vue
var _sfc_main$15 = /* @__PURE__ */ Object.assign({ inheritAttrs: false }, {
	__name: "InputNumber",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
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
		highlight: {
			type: Boolean,
			required: false
		},
		fixed: {
			type: Boolean,
			required: false
		},
		orientation: {
			type: null,
			required: false,
			default: "horizontal"
		},
		increment: {
			type: [Boolean, Object],
			required: false,
			default: true
		},
		incrementIcon: {
			type: null,
			required: false
		},
		incrementDisabled: {
			type: Boolean,
			required: false
		},
		decrement: {
			type: [Boolean, Object],
			required: false,
			default: true
		},
		decrementIcon: {
			type: null,
			required: false
		},
		decrementDisabled: {
			type: Boolean,
			required: false
		},
		autofocus: {
			type: Boolean,
			required: false
		},
		autofocusDelay: {
			type: Number,
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
		min: {
			type: Number,
			required: false
		},
		max: {
			type: Number,
			required: false
		},
		step: {
			type: Number,
			required: false
		},
		stepSnapping: {
			type: Boolean,
			required: false
		},
		disabled: {
			type: Boolean,
			required: false
		},
		required: {
			type: Boolean,
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
		formatOptions: {
			type: null,
			required: false
		},
		disableWheelChange: {
			type: Boolean,
			required: false
		},
		invertWheelChange: {
			type: Boolean,
			required: false
		},
		readonly: {
			type: Boolean,
			required: false
		},
		focusOnChange: {
			type: Boolean,
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
		const modelValue = useVModel(props, "modelValue", emits, { defaultValue: props.defaultValue });
		const { t } = useLocale();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("inputNumber", props);
		const rootProps = useForwardPropsEmits(reactivePick(props, "as", "stepSnapping", "formatOptions", "disableWheelChange", "invertWheelChange", "required", "readonly", "focusOnChange"), emits);
		const { emitFormBlur, emitFormFocus, emitFormChange, emitFormInput, id, color, size: formFieldSize, name, highlight, disabled, ariaAttrs } = useFormField(props);
		const { orientation, size: fieldGroupSize } = useFieldGroup(props);
		const inputSize = computed(() => fieldGroupSize.value || formFieldSize.value);
		const ui = computed(() => tv({
			extend: tv(input_number_default),
			...appConfig.ui?.inputNumber || {}
		})({
			color: color.value,
			variant: props.variant,
			size: inputSize.value,
			highlight: highlight.value,
			fixed: props.fixed,
			orientation: props.orientation,
			fieldGroup: orientation.value,
			increment: props.orientation === "vertical" ? !!props.increment || !!props.decrement : !!props.increment,
			decrement: props.orientation === "vertical" ? false : !!props.decrement
		}));
		const incrementIcon = computed(() => props.incrementIcon || (props.orientation === "horizontal" ? appConfig.ui.icons.plus : appConfig.ui.icons.chevronUp));
		const decrementIcon = computed(() => props.decrementIcon || (props.orientation === "horizontal" ? appConfig.ui.icons.minus : appConfig.ui.icons.chevronDown));
		const inputRef = useTemplateRef("inputRef");
		function onUpdate(value) {
			if (props.modelModifiers?.optional) modelValue.value = value = value ?? void 0;
			emits("change", new Event("change", { target: { value } }));
			emitFormChange();
			emitFormInput();
		}
		function onBlur(event) {
			emitFormBlur();
			emits("blur", event);
		}
		function autoFocus() {
			if (props.autofocus) inputRef.value?.$el?.focus();
		}
		onMounted(() => {
			setTimeout(() => {
				autoFocus();
			}, props.autofocusDelay);
		});
		__expose({ inputRef: toRef(() => inputRef.value?.$el) });
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(NumberFieldRoot), mergeProps(unref(rootProps), {
				id: unref(id),
				"default-value": __props.defaultValue,
				"model-value": unref(modelValue),
				min: __props.min,
				max: __props.max,
				step: __props.step,
				"data-slot": "root",
				class: ui.value.root({ class: [unref(uiProp)?.root, props.class] }),
				name: unref(name),
				disabled: unref(disabled),
				"onUpdate:modelValue": (val) => onUpdate(val)
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(ssrRenderComponent(unref(NumberFieldInput), mergeProps({
							..._ctx.$attrs,
							...unref(ariaAttrs)
						}, {
							ref_key: "inputRef",
							ref: inputRef,
							placeholder: __props.placeholder,
							required: __props.required,
							"data-slot": "base",
							class: ui.value.base({ class: unref(uiProp)?.base }),
							onBlur,
							onFocus: unref(emitFormFocus)
						}), null, _parent, _scopeId));
						if (!!__props.increment) {
							_push(`<div data-slot="increment" class="${ssrRenderClass(ui.value.increment({ class: unref(uiProp)?.increment }))}"${_scopeId}>`);
							_push(ssrRenderComponent(unref(NumberFieldIncrement), {
								"as-child": "",
								disabled: unref(disabled) || __props.incrementDisabled
							}, {
								default: withCtx((_, _push, _parent, _scopeId) => {
									if (_push) ssrRenderSlot(_ctx.$slots, "increment", {}, () => {
										_push(ssrRenderComponent(_sfc_main$22, mergeProps({
											icon: incrementIcon.value,
											color: unref(color),
											size: inputSize.value,
											variant: "link",
											"aria-label": unref(t)("inputNumber.increment")
										}, typeof __props.increment === "object" ? __props.increment : void 0), null, _parent, _scopeId));
									}, _push, _parent, _scopeId);
									else return [renderSlot(_ctx.$slots, "increment", {}, () => [createVNode(_sfc_main$22, mergeProps({
										icon: incrementIcon.value,
										color: unref(color),
										size: inputSize.value,
										variant: "link",
										"aria-label": unref(t)("inputNumber.increment")
									}, typeof __props.increment === "object" ? __props.increment : void 0), null, 16, [
										"icon",
										"color",
										"size",
										"aria-label"
									])])];
								}),
								_: 3
							}, _parent, _scopeId));
							_push(`</div>`);
						} else _push(`<!---->`);
						if (!!__props.decrement) {
							_push(`<div data-slot="decrement" class="${ssrRenderClass(ui.value.decrement({ class: unref(uiProp)?.decrement }))}"${_scopeId}>`);
							_push(ssrRenderComponent(unref(NumberFieldDecrement), {
								"as-child": "",
								disabled: unref(disabled) || __props.decrementDisabled
							}, {
								default: withCtx((_, _push, _parent, _scopeId) => {
									if (_push) ssrRenderSlot(_ctx.$slots, "decrement", {}, () => {
										_push(ssrRenderComponent(_sfc_main$22, mergeProps({
											icon: decrementIcon.value,
											color: unref(color),
											size: inputSize.value,
											variant: "link",
											"aria-label": unref(t)("inputNumber.decrement")
										}, typeof __props.decrement === "object" ? __props.decrement : void 0), null, _parent, _scopeId));
									}, _push, _parent, _scopeId);
									else return [renderSlot(_ctx.$slots, "decrement", {}, () => [createVNode(_sfc_main$22, mergeProps({
										icon: decrementIcon.value,
										color: unref(color),
										size: inputSize.value,
										variant: "link",
										"aria-label": unref(t)("inputNumber.decrement")
									}, typeof __props.decrement === "object" ? __props.decrement : void 0), null, 16, [
										"icon",
										"color",
										"size",
										"aria-label"
									])])];
								}),
								_: 3
							}, _parent, _scopeId));
							_push(`</div>`);
						} else _push(`<!---->`);
					} else return [
						createVNode(unref(NumberFieldInput), mergeProps({
							..._ctx.$attrs,
							...unref(ariaAttrs)
						}, {
							ref_key: "inputRef",
							ref: inputRef,
							placeholder: __props.placeholder,
							required: __props.required,
							"data-slot": "base",
							class: ui.value.base({ class: unref(uiProp)?.base }),
							onBlur,
							onFocus: unref(emitFormFocus)
						}), null, 16, [
							"placeholder",
							"required",
							"class",
							"onFocus"
						]),
						!!__props.increment ? (openBlock(), createBlock("div", {
							key: 0,
							"data-slot": "increment",
							class: ui.value.increment({ class: unref(uiProp)?.increment })
						}, [createVNode(unref(NumberFieldIncrement), {
							"as-child": "",
							disabled: unref(disabled) || __props.incrementDisabled
						}, {
							default: withCtx(() => [renderSlot(_ctx.$slots, "increment", {}, () => [createVNode(_sfc_main$22, mergeProps({
								icon: incrementIcon.value,
								color: unref(color),
								size: inputSize.value,
								variant: "link",
								"aria-label": unref(t)("inputNumber.increment")
							}, typeof __props.increment === "object" ? __props.increment : void 0), null, 16, [
								"icon",
								"color",
								"size",
								"aria-label"
							])])]),
							_: 3
						}, 8, ["disabled"])], 2)) : createCommentVNode("", true),
						!!__props.decrement ? (openBlock(), createBlock("div", {
							key: 1,
							"data-slot": "decrement",
							class: ui.value.decrement({ class: unref(uiProp)?.decrement })
						}, [createVNode(unref(NumberFieldDecrement), {
							"as-child": "",
							disabled: unref(disabled) || __props.decrementDisabled
						}, {
							default: withCtx(() => [renderSlot(_ctx.$slots, "decrement", {}, () => [createVNode(_sfc_main$22, mergeProps({
								icon: decrementIcon.value,
								color: unref(color),
								size: inputSize.value,
								variant: "link",
								"aria-label": unref(t)("inputNumber.decrement")
							}, typeof __props.decrement === "object" ? __props.decrement : void 0), null, 16, [
								"icon",
								"color",
								"size",
								"aria-label"
							])])]),
							_: 3
						}, 8, ["disabled"])], 2)) : createCommentVNode("", true)
					];
				}),
				_: 3
			}, _parent));
		};
	}
});
var _sfc_setup$24 = _sfc_main$15.setup;
_sfc_main$15.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/InputNumber.vue");
	return _sfc_setup$24 ? _sfc_setup$24(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/checkbox.ts
var checkbox_default = {
	"slots": {
		"root": "relative flex items-start",
		"container": "flex items-center",
		"base": "rounded-sm ring ring-inset ring-accented overflow-hidden focus-visible:outline-2 focus-visible:outline-offset-2",
		"indicator": "flex items-center justify-center size-full text-inverted",
		"icon": "shrink-0 size-full",
		"wrapper": "w-full",
		"label": "block font-medium text-default",
		"description": "text-muted"
	},
	"variants": {
		"color": {
			"primary": {
				"base": "focus-visible:outline-primary",
				"indicator": "bg-primary"
			},
			"secondary": {
				"base": "focus-visible:outline-secondary",
				"indicator": "bg-secondary"
			},
			"success": {
				"base": "focus-visible:outline-success",
				"indicator": "bg-success"
			},
			"info": {
				"base": "focus-visible:outline-info",
				"indicator": "bg-info"
			},
			"warning": {
				"base": "focus-visible:outline-warning",
				"indicator": "bg-warning"
			},
			"error": {
				"base": "focus-visible:outline-error",
				"indicator": "bg-error"
			},
			"neutral": {
				"base": "focus-visible:outline-inverted",
				"indicator": "bg-inverted"
			}
		},
		"variant": {
			"list": { "root": "" },
			"card": { "root": "border border-muted rounded-lg" }
		},
		"indicator": {
			"start": {
				"root": "flex-row",
				"wrapper": "ms-2"
			},
			"end": {
				"root": "flex-row-reverse",
				"wrapper": "me-2"
			},
			"hidden": {
				"base": "sr-only",
				"wrapper": "text-center"
			}
		},
		"size": {
			"xs": {
				"base": "size-3",
				"container": "h-4",
				"wrapper": "text-xs"
			},
			"sm": {
				"base": "size-3.5",
				"container": "h-4",
				"wrapper": "text-xs"
			},
			"md": {
				"base": "size-4",
				"container": "h-5",
				"wrapper": "text-sm"
			},
			"lg": {
				"base": "size-4.5",
				"container": "h-5",
				"wrapper": "text-sm"
			},
			"xl": {
				"base": "size-5",
				"container": "h-6",
				"wrapper": "text-base"
			}
		},
		"required": { "true": { "label": "after:content-['*'] after:ms-0.5 after:text-error" } },
		"disabled": { "true": {
			"root": "opacity-75",
			"base": "cursor-not-allowed",
			"label": "cursor-not-allowed",
			"description": "cursor-not-allowed"
		} },
		"checked": { "true": "" }
	},
	"compoundVariants": [
		{
			"size": "xs",
			"variant": "card",
			"class": { "root": "p-2.5" }
		},
		{
			"size": "sm",
			"variant": "card",
			"class": { "root": "p-3" }
		},
		{
			"size": "md",
			"variant": "card",
			"class": { "root": "p-3.5" }
		},
		{
			"size": "lg",
			"variant": "card",
			"class": { "root": "p-4" }
		},
		{
			"size": "xl",
			"variant": "card",
			"class": { "root": "p-4.5" }
		},
		{
			"color": "primary",
			"variant": "card",
			"class": { "root": "has-data-[state=checked]:border-primary" }
		},
		{
			"color": "secondary",
			"variant": "card",
			"class": { "root": "has-data-[state=checked]:border-secondary" }
		},
		{
			"color": "success",
			"variant": "card",
			"class": { "root": "has-data-[state=checked]:border-success" }
		},
		{
			"color": "info",
			"variant": "card",
			"class": { "root": "has-data-[state=checked]:border-info" }
		},
		{
			"color": "warning",
			"variant": "card",
			"class": { "root": "has-data-[state=checked]:border-warning" }
		},
		{
			"color": "error",
			"variant": "card",
			"class": { "root": "has-data-[state=checked]:border-error" }
		},
		{
			"color": "neutral",
			"variant": "card",
			"class": { "root": "has-data-[state=checked]:border-inverted" }
		},
		{
			"variant": "card",
			"disabled": true,
			"class": { "root": "cursor-not-allowed" }
		}
	],
	"defaultVariants": {
		"size": "md",
		"color": "primary",
		"variant": "list",
		"indicator": "start"
	}
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Checkbox.vue
var _sfc_main$14 = /* @__PURE__ */ Object.assign({ inheritAttrs: false }, {
	__name: "Checkbox",
	__ssrInlineRender: true,
	props: {
		as: {
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
		indicator: {
			type: null,
			required: false
		},
		icon: {
			type: null,
			required: false
		},
		indeterminateIcon: {
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
		disabled: {
			type: Boolean,
			required: false
		},
		required: {
			type: Boolean,
			required: false
		},
		name: {
			type: String,
			required: false
		},
		value: {
			type: null,
			required: false
		},
		id: {
			type: String,
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
		trueValue: {
			type: null,
			required: false
		},
		falseValue: {
			type: null,
			required: false
		}
	},
	emits: ["change", "update:modelValue"],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const slots = useSlots();
		const emits = __emit;
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("checkbox", props);
		const rootProps = useForwardPropsEmits(reactivePick(props, "required", "value", "defaultValue", "modelValue", "trueValue", "falseValue"), emits);
		const { id: _id, emitFormChange, emitFormInput, size, color, name, disabled, ariaAttrs } = useFormField(props);
		const id = _id.value ?? useId();
		const { variant } = useResolvedVariants("checkbox", props, checkbox_default, ["variant"]);
		const attrs = useAttrs();
		const forwardedAttrs = computed(() => {
			const { "data-state": _, ...rest } = attrs;
			return rest;
		});
		const ui = computed(() => tv({
			extend: tv(checkbox_default),
			...appConfig.ui?.checkbox || {}
		})({
			size: size.value,
			color: color.value,
			variant: variant.value,
			indicator: props.indicator,
			required: props.required,
			disabled: disabled.value
		}));
		function onUpdate(value) {
			emits("change", new Event("change", { target: { value } }));
			emitFormChange();
			emitFormInput();
		}
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(Primitive), mergeProps({
				as: unref(variant) === "list" ? __props.as : unref(Label),
				"data-slot": "root",
				class: ui.value.root({ class: [unref(uiProp)?.root, props.class] })
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div data-slot="container" class="${ssrRenderClass(ui.value.container({ class: unref(uiProp)?.container }))}"${_scopeId}>`);
						_push(ssrRenderComponent(unref(CheckboxRoot), mergeProps({ id: unref(id) }, {
							...unref(rootProps),
							...forwardedAttrs.value,
							...unref(ariaAttrs)
						}, {
							name: unref(name),
							disabled: unref(disabled),
							"data-slot": "base",
							class: ui.value.base({ class: unref(uiProp)?.base }),
							"onUpdate:modelValue": onUpdate
						}), {
							default: withCtx(({ state }, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(unref(CheckboxIndicator), {
									"data-slot": "indicator",
									class: ui.value.indicator({ class: unref(uiProp)?.indicator })
								}, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) if (state === "indeterminate") _push(ssrRenderComponent(_sfc_main$20, {
											name: __props.indeterminateIcon || unref(appConfig).ui.icons.minus,
											"data-slot": "icon",
											class: ui.value.icon({ class: unref(uiProp)?.icon })
										}, null, _parent, _scopeId));
										else _push(ssrRenderComponent(_sfc_main$20, {
											name: __props.icon || unref(appConfig).ui.icons.check,
											"data-slot": "icon",
											class: ui.value.icon({ class: unref(uiProp)?.icon })
										}, null, _parent, _scopeId));
										else return [state === "indeterminate" ? (openBlock(), createBlock(_sfc_main$20, {
											key: 0,
											name: __props.indeterminateIcon || unref(appConfig).ui.icons.minus,
											"data-slot": "icon",
											class: ui.value.icon({ class: unref(uiProp)?.icon })
										}, null, 8, ["name", "class"])) : (openBlock(), createBlock(_sfc_main$20, {
											key: 1,
											name: __props.icon || unref(appConfig).ui.icons.check,
											"data-slot": "icon",
											class: ui.value.icon({ class: unref(uiProp)?.icon })
										}, null, 8, ["name", "class"]))];
									}),
									_: 2
								}, _parent, _scopeId));
								else return [createVNode(unref(CheckboxIndicator), {
									"data-slot": "indicator",
									class: ui.value.indicator({ class: unref(uiProp)?.indicator })
								}, {
									default: withCtx(() => [state === "indeterminate" ? (openBlock(), createBlock(_sfc_main$20, {
										key: 0,
										name: __props.indeterminateIcon || unref(appConfig).ui.icons.minus,
										"data-slot": "icon",
										class: ui.value.icon({ class: unref(uiProp)?.icon })
									}, null, 8, ["name", "class"])) : (openBlock(), createBlock(_sfc_main$20, {
										key: 1,
										name: __props.icon || unref(appConfig).ui.icons.check,
										"data-slot": "icon",
										class: ui.value.icon({ class: unref(uiProp)?.icon })
									}, null, 8, ["name", "class"]))]),
									_: 2
								}, 1032, ["class"])];
							}),
							_: 1
						}, _parent, _scopeId));
						_push(`</div>`);
						if (__props.label || !!slots.label || __props.description || !!slots.description) {
							_push(`<div data-slot="wrapper" class="${ssrRenderClass(ui.value.wrapper({ class: unref(uiProp)?.wrapper }))}"${_scopeId}>`);
							if (__props.label || !!slots.label) ssrRenderVNode(_push, createVNode(resolveDynamicComponent(unref(variant) === "list" ? unref(Label) : "p"), {
								for: unref(id),
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
							}), _parent, _scopeId);
							else _push(`<!---->`);
							if (__props.description || !!slots.description) {
								_push(`<p data-slot="description" class="${ssrRenderClass(ui.value.description({ class: unref(uiProp)?.description }))}"${_scopeId}>`);
								ssrRenderSlot(_ctx.$slots, "description", { description: __props.description }, () => {
									_push(`${ssrInterpolate(__props.description)}`);
								}, _push, _parent, _scopeId);
								_push(`</p>`);
							} else _push(`<!---->`);
							_push(`</div>`);
						} else _push(`<!---->`);
					} else return [createVNode("div", {
						"data-slot": "container",
						class: ui.value.container({ class: unref(uiProp)?.container })
					}, [createVNode(unref(CheckboxRoot), mergeProps({ id: unref(id) }, {
						...unref(rootProps),
						...forwardedAttrs.value,
						...unref(ariaAttrs)
					}, {
						name: unref(name),
						disabled: unref(disabled),
						"data-slot": "base",
						class: ui.value.base({ class: unref(uiProp)?.base }),
						"onUpdate:modelValue": onUpdate
					}), {
						default: withCtx(({ state }) => [createVNode(unref(CheckboxIndicator), {
							"data-slot": "indicator",
							class: ui.value.indicator({ class: unref(uiProp)?.indicator })
						}, {
							default: withCtx(() => [state === "indeterminate" ? (openBlock(), createBlock(_sfc_main$20, {
								key: 0,
								name: __props.indeterminateIcon || unref(appConfig).ui.icons.minus,
								"data-slot": "icon",
								class: ui.value.icon({ class: unref(uiProp)?.icon })
							}, null, 8, ["name", "class"])) : (openBlock(), createBlock(_sfc_main$20, {
								key: 1,
								name: __props.icon || unref(appConfig).ui.icons.check,
								"data-slot": "icon",
								class: ui.value.icon({ class: unref(uiProp)?.icon })
							}, null, 8, ["name", "class"]))]),
							_: 2
						}, 1032, ["class"])]),
						_: 1
					}, 16, [
						"id",
						"name",
						"disabled",
						"class"
					])], 2), __props.label || !!slots.label || __props.description || !!slots.description ? (openBlock(), createBlock("div", {
						key: 0,
						"data-slot": "wrapper",
						class: ui.value.wrapper({ class: unref(uiProp)?.wrapper })
					}, [__props.label || !!slots.label ? (openBlock(), createBlock(resolveDynamicComponent(unref(variant) === "list" ? unref(Label) : "p"), {
						key: 0,
						for: unref(id),
						"data-slot": "label",
						class: ui.value.label({ class: unref(uiProp)?.label })
					}, {
						default: withCtx(() => [renderSlot(_ctx.$slots, "label", { label: __props.label }, () => [createTextVNode(toDisplayString(__props.label), 1)])]),
						_: 3
					}, 8, ["for", "class"])) : createCommentVNode("", true), __props.description || !!slots.description ? (openBlock(), createBlock("p", {
						key: 1,
						"data-slot": "description",
						class: ui.value.description({ class: unref(uiProp)?.description })
					}, [renderSlot(_ctx.$slots, "description", { description: __props.description }, () => [createTextVNode(toDisplayString(__props.description), 1)])], 2)) : createCommentVNode("", true)], 2)) : createCommentVNode("", true)];
				}),
				_: 3
			}, _parent));
		};
	}
});
var _sfc_setup$23 = _sfc_main$14.setup;
_sfc_main$14.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Checkbox.vue");
	return _sfc_setup$23 ? _sfc_setup$23(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/checkbox-group.ts
var checkbox_group_default = {
	"slots": {
		"root": "relative",
		"fieldset": "flex gap-x-2",
		"legend": "mb-1 block font-medium text-default",
		"item": ""
	},
	"variants": {
		"orientation": {
			"horizontal": { "fieldset": "flex-row" },
			"vertical": { "fieldset": "flex-col" }
		},
		"color": {
			"primary": {},
			"secondary": {},
			"success": {},
			"info": {},
			"warning": {},
			"error": {},
			"neutral": {}
		},
		"variant": {
			"list": {},
			"card": {},
			"table": { "item": "border border-muted" }
		},
		"size": {
			"xs": {
				"fieldset": "gap-y-0.5",
				"legend": "text-xs"
			},
			"sm": {
				"fieldset": "gap-y-0.5",
				"legend": "text-xs"
			},
			"md": {
				"fieldset": "gap-y-1",
				"legend": "text-sm"
			},
			"lg": {
				"fieldset": "gap-y-1",
				"legend": "text-sm"
			},
			"xl": {
				"fieldset": "gap-y-1.5",
				"legend": "text-base"
			}
		},
		"required": { "true": { "legend": "after:content-['*'] after:ms-0.5 after:text-error" } },
		"disabled": { "true": {} }
	},
	"compoundVariants": [
		{
			"size": "xs",
			"variant": "table",
			"class": { "item": "p-2.5" }
		},
		{
			"size": "sm",
			"variant": "table",
			"class": { "item": "p-3" }
		},
		{
			"size": "md",
			"variant": "table",
			"class": { "item": "p-3.5" }
		},
		{
			"size": "lg",
			"variant": "table",
			"class": { "item": "p-4" }
		},
		{
			"size": "xl",
			"variant": "table",
			"class": { "item": "p-4.5" }
		},
		{
			"orientation": "horizontal",
			"variant": "table",
			"class": {
				"item": "first-of-type:rounded-s-lg last-of-type:rounded-e-lg",
				"fieldset": "gap-0 -space-x-px"
			}
		},
		{
			"orientation": "vertical",
			"variant": "table",
			"class": {
				"item": "first-of-type:rounded-t-lg last-of-type:rounded-b-lg",
				"fieldset": "gap-0 -space-y-px"
			}
		},
		{
			"color": "primary",
			"variant": "table",
			"class": { "item": "has-data-[state=checked]:bg-primary/10 has-data-[state=checked]:border-primary/50 has-data-[state=checked]:z-[1]" }
		},
		{
			"color": "secondary",
			"variant": "table",
			"class": { "item": "has-data-[state=checked]:bg-secondary/10 has-data-[state=checked]:border-secondary/50 has-data-[state=checked]:z-[1]" }
		},
		{
			"color": "success",
			"variant": "table",
			"class": { "item": "has-data-[state=checked]:bg-success/10 has-data-[state=checked]:border-success/50 has-data-[state=checked]:z-[1]" }
		},
		{
			"color": "info",
			"variant": "table",
			"class": { "item": "has-data-[state=checked]:bg-info/10 has-data-[state=checked]:border-info/50 has-data-[state=checked]:z-[1]" }
		},
		{
			"color": "warning",
			"variant": "table",
			"class": { "item": "has-data-[state=checked]:bg-warning/10 has-data-[state=checked]:border-warning/50 has-data-[state=checked]:z-[1]" }
		},
		{
			"color": "error",
			"variant": "table",
			"class": { "item": "has-data-[state=checked]:bg-error/10 has-data-[state=checked]:border-error/50 has-data-[state=checked]:z-[1]" }
		},
		{
			"color": "neutral",
			"variant": "table",
			"class": { "item": "has-data-[state=checked]:bg-elevated has-data-[state=checked]:border-inverted/50 has-data-[state=checked]:z-[1]" }
		},
		{
			"variant": "table",
			"disabled": true,
			"class": { "item": "cursor-not-allowed" }
		}
	],
	"defaultVariants": {
		"size": "md",
		"variant": "list",
		"color": "primary"
	}
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/CheckboxGroup.vue
var _sfc_main$13 = {
	__name: "CheckboxGroup",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false
		},
		legend: {
			type: String,
			required: false
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
		modelValue: {
			type: null,
			required: false
		},
		defaultValue: {
			type: null,
			required: false
		},
		size: {
			type: null,
			required: false
		},
		variant: {
			type: null,
			required: false
		},
		orientation: {
			type: null,
			required: false,
			default: "vertical"
		},
		class: {
			type: null,
			required: false
		},
		ui: {
			type: Object,
			required: false
		},
		disabled: {
			type: Boolean,
			required: false
		},
		loop: {
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
		color: {
			type: null,
			required: false
		},
		indicator: {
			type: null,
			required: false
		},
		icon: {
			type: null,
			required: false
		}
	},
	emits: ["change", "update:modelValue"],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const slots = useSlots();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("checkboxGroup", props);
		const rootProps = useForwardPropsEmits(reactivePick(props, "as", "modelValue", "defaultValue", "orientation", "loop", "required"), emits);
		const checkboxProps = useForwardProps(reactivePick(props, "variant", "indicator", "icon"));
		const getProxySlots = () => omit(slots, ["legend"]);
		const { emitFormChange, emitFormInput, color, name, size, id: _id, disabled, ariaAttrs } = useFormField(props, { bind: false });
		const id = _id.value ?? useId();
		const ui = computed(() => tv({
			extend: checkbox_group_default,
			...appConfig.ui?.checkboxGroup || {}
		})({
			size: size.value,
			required: props.required,
			orientation: props.orientation,
			color: props.color,
			variant: props.variant,
			disabled: disabled.value
		}));
		function normalizeItem(item) {
			if (item === null) return {
				id: `${id}:null`,
				value: void 0,
				label: void 0
			};
			if (typeof item === "string" || typeof item === "number") return {
				id: `${id}:${item}`,
				value: String(item),
				label: String(item)
			};
			const value = get(item, props.valueKey);
			const label = get(item, props.labelKey);
			const description = get(item, props.descriptionKey);
			return {
				...item,
				value,
				label,
				description,
				id: `${id}:${value}`
			};
		}
		const normalizedItems = computed(() => {
			if (!props.items) return [];
			return props.items.map(normalizeItem);
		});
		function onUpdate(value) {
			emits("change", new Event("change", { target: { value } }));
			emitFormChange();
			emitFormInput();
		}
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(CheckboxGroupRoot), mergeProps({ id: unref(id) }, unref(rootProps), {
				name: unref(name),
				disabled: unref(disabled),
				"data-slot": "root",
				class: ui.value.root({ class: [unref(uiProp)?.root, props.class] }),
				"onUpdate:modelValue": onUpdate
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<fieldset${ssrRenderAttrs(mergeProps({
							"data-slot": "fieldset",
							class: ui.value.fieldset({ class: unref(uiProp)?.fieldset })
						}, unref(ariaAttrs)))}${_scopeId}>`);
						if (__props.legend || !!slots.legend) {
							_push(`<legend data-slot="legend" class="${ssrRenderClass(ui.value.legend({ class: unref(uiProp)?.legend }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "legend", {}, () => {
								_push(`${ssrInterpolate(__props.legend)}`);
							}, _push, _parent, _scopeId);
							_push(`</legend>`);
						} else _push(`<!---->`);
						_push(`<!--[-->`);
						ssrRenderList(normalizedItems.value, (item) => {
							_push(ssrRenderComponent(_sfc_main$14, mergeProps({ key: item.value }, { ref_for: true }, {
								...item,
								...unref(checkboxProps)
							}, {
								color: unref(color),
								size: unref(size),
								name: unref(name),
								disabled: item.disabled || unref(disabled),
								ui: {
									...unref(uiProp) ? unref(omit)(unref(uiProp), ["root"]) : void 0,
									...item.ui || {}
								},
								"data-slot": "item",
								class: ui.value.item({
									class: [
										unref(uiProp)?.item,
										item.ui?.item,
										item.class
									],
									disabled: item.disabled || unref(disabled)
								})
							}), createSlots({ _: 2 }, [renderList(getProxySlots(), (_, name) => {
								return {
									name,
									fn: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) ssrRenderSlot(_ctx.$slots, name, { item }, null, _push, _parent, _scopeId);
										else return [renderSlot(_ctx.$slots, name, { item })];
									})
								};
							})]), _parent, _scopeId));
						});
						_push(`<!--]--></fieldset>`);
					} else return [createVNode("fieldset", mergeProps({
						"data-slot": "fieldset",
						class: ui.value.fieldset({ class: unref(uiProp)?.fieldset })
					}, unref(ariaAttrs)), [__props.legend || !!slots.legend ? (openBlock(), createBlock("legend", {
						key: 0,
						"data-slot": "legend",
						class: ui.value.legend({ class: unref(uiProp)?.legend })
					}, [renderSlot(_ctx.$slots, "legend", {}, () => [createTextVNode(toDisplayString(__props.legend), 1)])], 2)) : createCommentVNode("", true), (openBlock(true), createBlock(Fragment, null, renderList(normalizedItems.value, (item) => {
						return openBlock(), createBlock(_sfc_main$14, mergeProps({ key: item.value }, { ref_for: true }, {
							...item,
							...unref(checkboxProps)
						}, {
							color: unref(color),
							size: unref(size),
							name: unref(name),
							disabled: item.disabled || unref(disabled),
							ui: {
								...unref(uiProp) ? unref(omit)(unref(uiProp), ["root"]) : void 0,
								...item.ui || {}
							},
							"data-slot": "item",
							class: ui.value.item({
								class: [
									unref(uiProp)?.item,
									item.ui?.item,
									item.class
								],
								disabled: item.disabled || unref(disabled)
							})
						}), createSlots({ _: 2 }, [renderList(getProxySlots(), (_, name) => {
							return {
								name,
								fn: withCtx(() => [renderSlot(_ctx.$slots, name, { item })])
							};
						})]), 1040, [
							"color",
							"size",
							"name",
							"disabled",
							"ui",
							"class"
						]);
					}), 128))], 16)];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$22 = _sfc_main$13.setup;
_sfc_main$13.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/CheckboxGroup.vue");
	return _sfc_setup$22 ? _sfc_setup$22(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/modal.ts
var modal_default = {
	"slots": {
		"overlay": "fixed inset-0",
		"content": "bg-default divide-y divide-default flex flex-col focus:outline-none",
		"header": "flex items-center gap-1.5 p-4 sm:px-6 min-h-(--ui-header-height)",
		"wrapper": "",
		"body": "flex-1 p-4 sm:p-6",
		"footer": "flex items-center gap-1.5 p-4 sm:px-6",
		"title": "text-highlighted font-semibold",
		"description": "mt-1 text-muted text-sm",
		"close": "absolute top-4 end-4"
	},
	"variants": {
		"transition": { "true": {
			"overlay": "data-[state=open]:animate-[fade-in_200ms_ease-out] data-[state=closed]:animate-[fade-out_200ms_ease-in]",
			"content": "data-[state=open]:animate-[scale-in_200ms_ease-out] data-[state=closed]:animate-[scale-out_200ms_ease-in]"
		} },
		"fullscreen": {
			"true": { "content": "inset-0" },
			"false": { "content": "w-[calc(100vw-2rem)] max-w-lg rounded-lg shadow-lg ring ring-default" }
		},
		"overlay": { "true": { "overlay": "bg-elevated/75" } },
		"scrollable": {
			"true": {
				"overlay": "overflow-y-auto",
				"content": "relative"
			},
			"false": {
				"content": "fixed",
				"body": "overflow-y-auto"
			}
		}
	},
	"compoundVariants": [{
		"scrollable": true,
		"fullscreen": false,
		"class": { "overlay": "grid place-items-center p-4 sm:py-8" }
	}, {
		"scrollable": false,
		"fullscreen": false,
		"class": { "content": "top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 max-h-[calc(100dvh-2rem)] sm:max-h-[calc(100dvh-4rem)] overflow-hidden" }
	}]
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Modal.vue
var _sfc_main$12 = {
	__name: "Modal",
	__ssrInlineRender: true,
	props: {
		title: {
			type: String,
			required: false
		},
		description: {
			type: String,
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
		scrollable: {
			type: Boolean,
			required: false
		},
		transition: {
			type: Boolean,
			required: false,
			default: true
		},
		fullscreen: {
			type: Boolean,
			required: false
		},
		portal: {
			type: [Boolean, String],
			required: false,
			skipCheck: true,
			default: true
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
		modal: {
			type: Boolean,
			required: false,
			default: true
		}
	},
	emits: [
		"after:leave",
		"after:enter",
		"close:prevent",
		"update:open"
	],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const slots = useSlots();
		const { t } = useLocale();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("modal", props);
		const rootProps = useForwardPropsEmits(reactivePick(props, "open", "defaultOpen", "modal"), emits);
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
			return { pointerDownOutside: (e) => pointerDownOutside(e, { scrollable: props.scrollable }) };
		});
		const [DefineContentTemplate, ReuseContentTemplate] = createReusableTemplate();
		const ui = computed(() => tv({
			extend: tv(modal_default),
			...appConfig.ui?.modal || {}
		})({
			transition: props.transition,
			fullscreen: props.fullscreen,
			overlay: props.overlay,
			scrollable: props.scrollable
		}));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(DialogRoot), mergeProps(unref(rootProps), _attrs), {
				default: withCtx(({ open, close }, _push, _parent, _scopeId) => {
					if (_push) {
						_push(ssrRenderComponent(unref(DefineContentTemplate), null, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(unref(DialogContent), mergeProps({
									"data-slot": "content",
									class: ui.value.content({ class: [!slots.default && props.class, unref(uiProp)?.content] })
								}, contentProps.value, {
									onAfterEnter: ($event) => emits("after:enter"),
									onAfterLeave: ($event) => emits("after:leave")
								}, toHandlers(contentEvents.value)), {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) {
											if (!__props.title && !slots.title || !__props.description && !slots.description || !!slots.content) _push(ssrRenderComponent(unref(VisuallyHidden), null, {
												default: withCtx((_, _push, _parent, _scopeId) => {
													if (_push) {
														if (!__props.title && !slots.title) _push(ssrRenderComponent(unref(DialogTitle), null, null, _parent, _scopeId));
														else if (!!slots.content) _push(ssrRenderComponent(unref(DialogTitle), null, {
															default: withCtx((_, _push, _parent, _scopeId) => {
																if (_push) ssrRenderSlot(_ctx.$slots, "title", {}, () => {
																	_push(`${ssrInterpolate(__props.title)}`);
																}, _push, _parent, _scopeId);
																else return [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(__props.title), 1)])];
															}),
															_: 2
														}, _parent, _scopeId));
														else _push(`<!---->`);
														if (!__props.description && !slots.description) _push(ssrRenderComponent(unref(DialogDescription), null, null, _parent, _scopeId));
														else if (!!slots.content) _push(ssrRenderComponent(unref(DialogDescription), null, {
															default: withCtx((_, _push, _parent, _scopeId) => {
																if (_push) ssrRenderSlot(_ctx.$slots, "description", {}, () => {
																	_push(`${ssrInterpolate(__props.description)}`);
																}, _push, _parent, _scopeId);
																else return [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(__props.description), 1)])];
															}),
															_: 2
														}, _parent, _scopeId));
														else _push(`<!---->`);
													} else return [!__props.title && !slots.title ? (openBlock(), createBlock(unref(DialogTitle), { key: 0 })) : !!slots.content ? (openBlock(), createBlock(unref(DialogTitle), { key: 1 }, {
														default: withCtx(() => [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(__props.title), 1)])]),
														_: 3
													})) : createCommentVNode("", true), !__props.description && !slots.description ? (openBlock(), createBlock(unref(DialogDescription), { key: 2 })) : !!slots.content ? (openBlock(), createBlock(unref(DialogDescription), { key: 3 }, {
														default: withCtx(() => [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(__props.description), 1)])]),
														_: 3
													})) : createCommentVNode("", true)];
												}),
												_: 2
											}, _parent, _scopeId));
											else _push(`<!---->`);
											ssrRenderSlot(_ctx.$slots, "content", { close }, () => {
												if (!!slots.header || __props.title || !!slots.title || __props.description || !!slots.description || props.close || !!slots.close) {
													_push(`<div data-slot="header" class="${ssrRenderClass(ui.value.header({ class: unref(uiProp)?.header }))}"${_scopeId}>`);
													ssrRenderSlot(_ctx.$slots, "header", { close }, () => {
														if (__props.title || !!slots.title || __props.description || !!slots.description) {
															_push(`<div data-slot="wrapper" class="${ssrRenderClass(ui.value.wrapper({ class: unref(uiProp)?.wrapper }))}"${_scopeId}>`);
															if (__props.title || !!slots.title) _push(ssrRenderComponent(unref(DialogTitle), {
																"data-slot": "title",
																class: ui.value.title({ class: unref(uiProp)?.title })
															}, {
																default: withCtx((_, _push, _parent, _scopeId) => {
																	if (_push) ssrRenderSlot(_ctx.$slots, "title", {}, () => {
																		_push(`${ssrInterpolate(__props.title)}`);
																	}, _push, _parent, _scopeId);
																	else return [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(__props.title), 1)])];
																}),
																_: 2
															}, _parent, _scopeId));
															else _push(`<!---->`);
															if (__props.description || !!slots.description) _push(ssrRenderComponent(unref(DialogDescription), {
																"data-slot": "description",
																class: ui.value.description({ class: unref(uiProp)?.description })
															}, {
																default: withCtx((_, _push, _parent, _scopeId) => {
																	if (_push) ssrRenderSlot(_ctx.$slots, "description", {}, () => {
																		_push(`${ssrInterpolate(__props.description)}`);
																	}, _push, _parent, _scopeId);
																	else return [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(__props.description), 1)])];
																}),
																_: 2
															}, _parent, _scopeId));
															else _push(`<!---->`);
															_push(`</div>`);
														} else _push(`<!---->`);
														ssrRenderSlot(_ctx.$slots, "actions", {}, null, _push, _parent, _scopeId);
														if (props.close || !!slots.close) _push(ssrRenderComponent(unref(DialogClose), { "as-child": "" }, {
															default: withCtx((_, _push, _parent, _scopeId) => {
																if (_push) ssrRenderSlot(_ctx.$slots, "close", { ui: ui.value }, () => {
																	if (props.close) _push(ssrRenderComponent(_sfc_main$22, mergeProps({
																		icon: __props.closeIcon || unref(appConfig).ui.icons.close,
																		color: "neutral",
																		variant: "ghost",
																		"aria-label": unref(t)("modal.close")
																	}, typeof props.close === "object" ? props.close : {}, {
																		"data-slot": "close",
																		class: ui.value.close({ class: unref(uiProp)?.close })
																	}), null, _parent, _scopeId));
																	else _push(`<!---->`);
																}, _push, _parent, _scopeId);
																else return [renderSlot(_ctx.$slots, "close", { ui: ui.value }, () => [props.close ? (openBlock(), createBlock(_sfc_main$22, mergeProps({
																	key: 0,
																	icon: __props.closeIcon || unref(appConfig).ui.icons.close,
																	color: "neutral",
																	variant: "ghost",
																	"aria-label": unref(t)("modal.close")
																}, typeof props.close === "object" ? props.close : {}, {
																	"data-slot": "close",
																	class: ui.value.close({ class: unref(uiProp)?.close })
																}), null, 16, [
																	"icon",
																	"aria-label",
																	"class"
																])) : createCommentVNode("", true)])];
															}),
															_: 2
														}, _parent, _scopeId));
														else _push(`<!---->`);
													}, _push, _parent, _scopeId);
													_push(`</div>`);
												} else _push(`<!---->`);
												if (!!slots.body) {
													_push(`<div data-slot="body" class="${ssrRenderClass(ui.value.body({ class: unref(uiProp)?.body }))}"${_scopeId}>`);
													ssrRenderSlot(_ctx.$slots, "body", { close }, null, _push, _parent, _scopeId);
													_push(`</div>`);
												} else _push(`<!---->`);
												if (!!slots.footer) {
													_push(`<div data-slot="footer" class="${ssrRenderClass(ui.value.footer({ class: unref(uiProp)?.footer }))}"${_scopeId}>`);
													ssrRenderSlot(_ctx.$slots, "footer", { close }, null, _push, _parent, _scopeId);
													_push(`</div>`);
												} else _push(`<!---->`);
											}, _push, _parent, _scopeId);
										} else return [!__props.title && !slots.title || !__props.description && !slots.description || !!slots.content ? (openBlock(), createBlock(unref(VisuallyHidden), { key: 0 }, {
											default: withCtx(() => [!__props.title && !slots.title ? (openBlock(), createBlock(unref(DialogTitle), { key: 0 })) : !!slots.content ? (openBlock(), createBlock(unref(DialogTitle), { key: 1 }, {
												default: withCtx(() => [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(__props.title), 1)])]),
												_: 3
											})) : createCommentVNode("", true), !__props.description && !slots.description ? (openBlock(), createBlock(unref(DialogDescription), { key: 2 })) : !!slots.content ? (openBlock(), createBlock(unref(DialogDescription), { key: 3 }, {
												default: withCtx(() => [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(__props.description), 1)])]),
												_: 3
											})) : createCommentVNode("", true)]),
											_: 3
										})) : createCommentVNode("", true), renderSlot(_ctx.$slots, "content", { close }, () => [
											!!slots.header || __props.title || !!slots.title || __props.description || !!slots.description || props.close || !!slots.close ? (openBlock(), createBlock("div", {
												key: 0,
												"data-slot": "header",
												class: ui.value.header({ class: unref(uiProp)?.header })
											}, [renderSlot(_ctx.$slots, "header", { close }, () => [
												__props.title || !!slots.title || __props.description || !!slots.description ? (openBlock(), createBlock("div", {
													key: 0,
													"data-slot": "wrapper",
													class: ui.value.wrapper({ class: unref(uiProp)?.wrapper })
												}, [__props.title || !!slots.title ? (openBlock(), createBlock(unref(DialogTitle), {
													key: 0,
													"data-slot": "title",
													class: ui.value.title({ class: unref(uiProp)?.title })
												}, {
													default: withCtx(() => [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(__props.title), 1)])]),
													_: 3
												}, 8, ["class"])) : createCommentVNode("", true), __props.description || !!slots.description ? (openBlock(), createBlock(unref(DialogDescription), {
													key: 1,
													"data-slot": "description",
													class: ui.value.description({ class: unref(uiProp)?.description })
												}, {
													default: withCtx(() => [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(__props.description), 1)])]),
													_: 3
												}, 8, ["class"])) : createCommentVNode("", true)], 2)) : createCommentVNode("", true),
												renderSlot(_ctx.$slots, "actions"),
												props.close || !!slots.close ? (openBlock(), createBlock(unref(DialogClose), {
													key: 1,
													"as-child": ""
												}, {
													default: withCtx(() => [renderSlot(_ctx.$slots, "close", { ui: ui.value }, () => [props.close ? (openBlock(), createBlock(_sfc_main$22, mergeProps({
														key: 0,
														icon: __props.closeIcon || unref(appConfig).ui.icons.close,
														color: "neutral",
														variant: "ghost",
														"aria-label": unref(t)("modal.close")
													}, typeof props.close === "object" ? props.close : {}, {
														"data-slot": "close",
														class: ui.value.close({ class: unref(uiProp)?.close })
													}), null, 16, [
														"icon",
														"aria-label",
														"class"
													])) : createCommentVNode("", true)])]),
													_: 2
												}, 1024)) : createCommentVNode("", true)
											])], 2)) : createCommentVNode("", true),
											!!slots.body ? (openBlock(), createBlock("div", {
												key: 1,
												"data-slot": "body",
												class: ui.value.body({ class: unref(uiProp)?.body })
											}, [renderSlot(_ctx.$slots, "body", { close })], 2)) : createCommentVNode("", true),
											!!slots.footer ? (openBlock(), createBlock("div", {
												key: 2,
												"data-slot": "footer",
												class: ui.value.footer({ class: unref(uiProp)?.footer })
											}, [renderSlot(_ctx.$slots, "footer", { close })], 2)) : createCommentVNode("", true)
										])];
									}),
									_: 2
								}, _parent, _scopeId));
								else return [createVNode(unref(DialogContent), mergeProps({
									"data-slot": "content",
									class: ui.value.content({ class: [!slots.default && props.class, unref(uiProp)?.content] })
								}, contentProps.value, {
									onAfterEnter: ($event) => emits("after:enter"),
									onAfterLeave: ($event) => emits("after:leave")
								}, toHandlers(contentEvents.value)), {
									default: withCtx(() => [!__props.title && !slots.title || !__props.description && !slots.description || !!slots.content ? (openBlock(), createBlock(unref(VisuallyHidden), { key: 0 }, {
										default: withCtx(() => [!__props.title && !slots.title ? (openBlock(), createBlock(unref(DialogTitle), { key: 0 })) : !!slots.content ? (openBlock(), createBlock(unref(DialogTitle), { key: 1 }, {
											default: withCtx(() => [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(__props.title), 1)])]),
											_: 3
										})) : createCommentVNode("", true), !__props.description && !slots.description ? (openBlock(), createBlock(unref(DialogDescription), { key: 2 })) : !!slots.content ? (openBlock(), createBlock(unref(DialogDescription), { key: 3 }, {
											default: withCtx(() => [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(__props.description), 1)])]),
											_: 3
										})) : createCommentVNode("", true)]),
										_: 3
									})) : createCommentVNode("", true), renderSlot(_ctx.$slots, "content", { close }, () => [
										!!slots.header || __props.title || !!slots.title || __props.description || !!slots.description || props.close || !!slots.close ? (openBlock(), createBlock("div", {
											key: 0,
											"data-slot": "header",
											class: ui.value.header({ class: unref(uiProp)?.header })
										}, [renderSlot(_ctx.$slots, "header", { close }, () => [
											__props.title || !!slots.title || __props.description || !!slots.description ? (openBlock(), createBlock("div", {
												key: 0,
												"data-slot": "wrapper",
												class: ui.value.wrapper({ class: unref(uiProp)?.wrapper })
											}, [__props.title || !!slots.title ? (openBlock(), createBlock(unref(DialogTitle), {
												key: 0,
												"data-slot": "title",
												class: ui.value.title({ class: unref(uiProp)?.title })
											}, {
												default: withCtx(() => [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(__props.title), 1)])]),
												_: 3
											}, 8, ["class"])) : createCommentVNode("", true), __props.description || !!slots.description ? (openBlock(), createBlock(unref(DialogDescription), {
												key: 1,
												"data-slot": "description",
												class: ui.value.description({ class: unref(uiProp)?.description })
											}, {
												default: withCtx(() => [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(__props.description), 1)])]),
												_: 3
											}, 8, ["class"])) : createCommentVNode("", true)], 2)) : createCommentVNode("", true),
											renderSlot(_ctx.$slots, "actions"),
											props.close || !!slots.close ? (openBlock(), createBlock(unref(DialogClose), {
												key: 1,
												"as-child": ""
											}, {
												default: withCtx(() => [renderSlot(_ctx.$slots, "close", { ui: ui.value }, () => [props.close ? (openBlock(), createBlock(_sfc_main$22, mergeProps({
													key: 0,
													icon: __props.closeIcon || unref(appConfig).ui.icons.close,
													color: "neutral",
													variant: "ghost",
													"aria-label": unref(t)("modal.close")
												}, typeof props.close === "object" ? props.close : {}, {
													"data-slot": "close",
													class: ui.value.close({ class: unref(uiProp)?.close })
												}), null, 16, [
													"icon",
													"aria-label",
													"class"
												])) : createCommentVNode("", true)])]),
												_: 2
											}, 1024)) : createCommentVNode("", true)
										])], 2)) : createCommentVNode("", true),
										!!slots.body ? (openBlock(), createBlock("div", {
											key: 1,
											"data-slot": "body",
											class: ui.value.body({ class: unref(uiProp)?.body })
										}, [renderSlot(_ctx.$slots, "body", { close })], 2)) : createCommentVNode("", true),
										!!slots.footer ? (openBlock(), createBlock("div", {
											key: 2,
											"data-slot": "footer",
											class: ui.value.footer({ class: unref(uiProp)?.footer })
										}, [renderSlot(_ctx.$slots, "footer", { close })], 2)) : createCommentVNode("", true)
									])]),
									_: 2
								}, 1040, [
									"class",
									"onAfterEnter",
									"onAfterLeave"
								])];
							}),
							_: 2
						}, _parent, _scopeId));
						if (!!slots.default) _push(ssrRenderComponent(unref(DialogTrigger), {
							"as-child": "",
							class: props.class
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) ssrRenderSlot(_ctx.$slots, "default", { open }, null, _push, _parent, _scopeId);
								else return [renderSlot(_ctx.$slots, "default", { open })];
							}),
							_: 2
						}, _parent, _scopeId));
						else _push(`<!---->`);
						_push(ssrRenderComponent(unref(DialogPortal), unref(portalProps), {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(unref(FieldGroupReset), null, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) if (__props.scrollable) _push(ssrRenderComponent(unref(DialogOverlay), {
											"data-slot": "overlay",
											class: ui.value.overlay({ class: unref(uiProp)?.overlay })
										}, {
											default: withCtx((_, _push, _parent, _scopeId) => {
												if (_push) _push(ssrRenderComponent(unref(ReuseContentTemplate), null, null, _parent, _scopeId));
												else return [createVNode(unref(ReuseContentTemplate))];
											}),
											_: 2
										}, _parent, _scopeId));
										else {
											_push(`<!--[-->`);
											if (__props.overlay) _push(ssrRenderComponent(unref(DialogOverlay), {
												"data-slot": "overlay",
												class: ui.value.overlay({ class: unref(uiProp)?.overlay })
											}, null, _parent, _scopeId));
											else _push(`<!---->`);
											_push(ssrRenderComponent(unref(ReuseContentTemplate), null, null, _parent, _scopeId));
											_push(`<!--]-->`);
										}
										else return [__props.scrollable ? (openBlock(), createBlock(unref(DialogOverlay), {
											key: 0,
											"data-slot": "overlay",
											class: ui.value.overlay({ class: unref(uiProp)?.overlay })
										}, {
											default: withCtx(() => [createVNode(unref(ReuseContentTemplate))]),
											_: 1
										}, 8, ["class"])) : (openBlock(), createBlock(Fragment, { key: 1 }, [__props.overlay ? (openBlock(), createBlock(unref(DialogOverlay), {
											key: 0,
											"data-slot": "overlay",
											class: ui.value.overlay({ class: unref(uiProp)?.overlay })
										}, null, 8, ["class"])) : createCommentVNode("", true), createVNode(unref(ReuseContentTemplate))], 64))];
									}),
									_: 2
								}, _parent, _scopeId));
								else return [createVNode(unref(FieldGroupReset), null, {
									default: withCtx(() => [__props.scrollable ? (openBlock(), createBlock(unref(DialogOverlay), {
										key: 0,
										"data-slot": "overlay",
										class: ui.value.overlay({ class: unref(uiProp)?.overlay })
									}, {
										default: withCtx(() => [createVNode(unref(ReuseContentTemplate))]),
										_: 1
									}, 8, ["class"])) : (openBlock(), createBlock(Fragment, { key: 1 }, [__props.overlay ? (openBlock(), createBlock(unref(DialogOverlay), {
										key: 0,
										"data-slot": "overlay",
										class: ui.value.overlay({ class: unref(uiProp)?.overlay })
									}, null, 8, ["class"])) : createCommentVNode("", true), createVNode(unref(ReuseContentTemplate))], 64))]),
									_: 1
								})];
							}),
							_: 2
						}, _parent, _scopeId));
					} else return [
						createVNode(unref(DefineContentTemplate), null, {
							default: withCtx(() => [createVNode(unref(DialogContent), mergeProps({
								"data-slot": "content",
								class: ui.value.content({ class: [!slots.default && props.class, unref(uiProp)?.content] })
							}, contentProps.value, {
								onAfterEnter: ($event) => emits("after:enter"),
								onAfterLeave: ($event) => emits("after:leave")
							}, toHandlers(contentEvents.value)), {
								default: withCtx(() => [!__props.title && !slots.title || !__props.description && !slots.description || !!slots.content ? (openBlock(), createBlock(unref(VisuallyHidden), { key: 0 }, {
									default: withCtx(() => [!__props.title && !slots.title ? (openBlock(), createBlock(unref(DialogTitle), { key: 0 })) : !!slots.content ? (openBlock(), createBlock(unref(DialogTitle), { key: 1 }, {
										default: withCtx(() => [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(__props.title), 1)])]),
										_: 3
									})) : createCommentVNode("", true), !__props.description && !slots.description ? (openBlock(), createBlock(unref(DialogDescription), { key: 2 })) : !!slots.content ? (openBlock(), createBlock(unref(DialogDescription), { key: 3 }, {
										default: withCtx(() => [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(__props.description), 1)])]),
										_: 3
									})) : createCommentVNode("", true)]),
									_: 3
								})) : createCommentVNode("", true), renderSlot(_ctx.$slots, "content", { close }, () => [
									!!slots.header || __props.title || !!slots.title || __props.description || !!slots.description || props.close || !!slots.close ? (openBlock(), createBlock("div", {
										key: 0,
										"data-slot": "header",
										class: ui.value.header({ class: unref(uiProp)?.header })
									}, [renderSlot(_ctx.$slots, "header", { close }, () => [
										__props.title || !!slots.title || __props.description || !!slots.description ? (openBlock(), createBlock("div", {
											key: 0,
											"data-slot": "wrapper",
											class: ui.value.wrapper({ class: unref(uiProp)?.wrapper })
										}, [__props.title || !!slots.title ? (openBlock(), createBlock(unref(DialogTitle), {
											key: 0,
											"data-slot": "title",
											class: ui.value.title({ class: unref(uiProp)?.title })
										}, {
											default: withCtx(() => [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(__props.title), 1)])]),
											_: 3
										}, 8, ["class"])) : createCommentVNode("", true), __props.description || !!slots.description ? (openBlock(), createBlock(unref(DialogDescription), {
											key: 1,
											"data-slot": "description",
											class: ui.value.description({ class: unref(uiProp)?.description })
										}, {
											default: withCtx(() => [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(__props.description), 1)])]),
											_: 3
										}, 8, ["class"])) : createCommentVNode("", true)], 2)) : createCommentVNode("", true),
										renderSlot(_ctx.$slots, "actions"),
										props.close || !!slots.close ? (openBlock(), createBlock(unref(DialogClose), {
											key: 1,
											"as-child": ""
										}, {
											default: withCtx(() => [renderSlot(_ctx.$slots, "close", { ui: ui.value }, () => [props.close ? (openBlock(), createBlock(_sfc_main$22, mergeProps({
												key: 0,
												icon: __props.closeIcon || unref(appConfig).ui.icons.close,
												color: "neutral",
												variant: "ghost",
												"aria-label": unref(t)("modal.close")
											}, typeof props.close === "object" ? props.close : {}, {
												"data-slot": "close",
												class: ui.value.close({ class: unref(uiProp)?.close })
											}), null, 16, [
												"icon",
												"aria-label",
												"class"
											])) : createCommentVNode("", true)])]),
											_: 2
										}, 1024)) : createCommentVNode("", true)
									])], 2)) : createCommentVNode("", true),
									!!slots.body ? (openBlock(), createBlock("div", {
										key: 1,
										"data-slot": "body",
										class: ui.value.body({ class: unref(uiProp)?.body })
									}, [renderSlot(_ctx.$slots, "body", { close })], 2)) : createCommentVNode("", true),
									!!slots.footer ? (openBlock(), createBlock("div", {
										key: 2,
										"data-slot": "footer",
										class: ui.value.footer({ class: unref(uiProp)?.footer })
									}, [renderSlot(_ctx.$slots, "footer", { close })], 2)) : createCommentVNode("", true)
								])]),
								_: 2
							}, 1040, [
								"class",
								"onAfterEnter",
								"onAfterLeave"
							])]),
							_: 2
						}, 1024),
						!!slots.default ? (openBlock(), createBlock(unref(DialogTrigger), {
							key: 0,
							"as-child": "",
							class: props.class
						}, {
							default: withCtx(() => [renderSlot(_ctx.$slots, "default", { open })]),
							_: 2
						}, 1032, ["class"])) : createCommentVNode("", true),
						createVNode(unref(DialogPortal), unref(portalProps), {
							default: withCtx(() => [createVNode(unref(FieldGroupReset), null, {
								default: withCtx(() => [__props.scrollable ? (openBlock(), createBlock(unref(DialogOverlay), {
									key: 0,
									"data-slot": "overlay",
									class: ui.value.overlay({ class: unref(uiProp)?.overlay })
								}, {
									default: withCtx(() => [createVNode(unref(ReuseContentTemplate))]),
									_: 1
								}, 8, ["class"])) : (openBlock(), createBlock(Fragment, { key: 1 }, [__props.overlay ? (openBlock(), createBlock(unref(DialogOverlay), {
									key: 0,
									"data-slot": "overlay",
									class: ui.value.overlay({ class: unref(uiProp)?.overlay })
								}, null, 8, ["class"])) : createCommentVNode("", true), createVNode(unref(ReuseContentTemplate))], 64))]),
								_: 1
							})]),
							_: 1
						}, 16)
					];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$21 = _sfc_main$12.setup;
_sfc_main$12.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Modal.vue");
	return _sfc_setup$21 ? _sfc_setup$21(props, ctx) : void 0;
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/DropdownMenuContent.vue
var _sfc_main$11 = {
	__name: "DropdownMenuContent",
	__ssrInlineRender: true,
	props: {
		items: {
			type: null,
			required: false
		},
		portal: {
			type: [Boolean, String],
			required: false,
			skipCheck: true
		},
		sub: {
			type: Boolean,
			required: false
		},
		labelKey: {
			type: null,
			required: true
		},
		descriptionKey: {
			type: null,
			required: true
		},
		checkedIcon: {
			type: null,
			required: false
		},
		loadingIcon: {
			type: null,
			required: false
		},
		externalIcon: {
			type: [Boolean, String],
			required: false,
			skipCheck: true
		},
		size: {
			type: null,
			required: false
		},
		filter: {
			type: [Boolean, Object],
			required: false
		},
		filterFields: {
			type: Array,
			required: false
		},
		ignoreFilter: {
			type: Boolean,
			required: false
		},
		searchTerm: {
			type: String,
			required: false
		},
		class: {
			type: null,
			required: false
		},
		ui: {
			type: null,
			required: true
		},
		uiOverride: {
			type: null,
			required: false
		},
		loop: {
			type: Boolean,
			required: false
		},
		side: {
			type: null,
			required: false
		},
		sideOffset: {
			type: Number,
			required: false
		},
		sideFlip: {
			type: Boolean,
			required: false
		},
		align: {
			type: null,
			required: false
		},
		alignOffset: {
			type: Number,
			required: false
		},
		alignFlip: {
			type: Boolean,
			required: false
		},
		avoidCollisions: {
			type: Boolean,
			required: false
		},
		collisionBoundary: {
			type: null,
			required: false
		},
		collisionPadding: {
			type: [Number, Object],
			required: false
		},
		arrowPadding: {
			type: Number,
			required: false
		},
		hideShiftedArrow: {
			type: Boolean,
			required: false
		},
		sticky: {
			type: String,
			required: false
		},
		hideWhenDetached: {
			type: Boolean,
			required: false
		},
		positionStrategy: {
			type: String,
			required: false
		},
		updatePositionStrategy: {
			type: String,
			required: false
		},
		disableUpdateOnLayoutShift: {
			type: Boolean,
			required: false
		},
		prioritizePosition: {
			type: Boolean,
			required: false
		},
		reference: {
			type: null,
			required: false
		}
	},
	emits: [
		"update:searchTerm",
		"escapeKeyDown",
		"pointerDownOutside",
		"focusOutside",
		"interactOutside",
		"closeAutoFocus"
	],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const slots = useSlots();
		const { t, dir } = useLocale();
		const appConfig = useAppConfig();
		const { filterGroups } = useFilter$1();
		const _searchTerm = ref("");
		const searchTerm = computed({
			get: () => props.searchTerm ?? _searchTerm.value,
			set: (value) => {
				_searchTerm.value = value;
				emits("update:searchTerm", value);
			}
		});
		const inputProps = toRef(() => defu(props.filter, {
			placeholder: t("dropdownMenu.search"),
			variant: "none"
		}));
		const portalProps = usePortal(toRef(() => props.portal));
		const contentProps = useForwardPropsEmits(reactiveOmit(props, "sub", "items", "portal", "labelKey", "descriptionKey", "checkedIcon", "loadingIcon", "externalIcon", "size", "filter", "filterFields", "ignoreFilter", "searchTerm", "class", "ui", "uiOverride"), emits);
		const getProxySlots = () => omit(slots, ["default"]);
		const [DefineItemTemplate, ReuseItemTemplate] = createReusableTemplate();
		const childrenIcon = computed(() => dir.value === "rtl" ? appConfig.ui.icons.chevronLeft : appConfig.ui.icons.chevronRight);
		const groups = computed(() => {
			if (!props.items?.length) return [];
			return isArrayOfArray(props.items) ? props.items : [props.items];
		});
		const isStructuralItem = (item) => !!item.type && ["label", "separator"].includes(item.type);
		const filteredGroups = computed(() => {
			if (!props.filter || props.ignoreFilter || !searchTerm.value) return groups.value;
			const fields = Array.isArray(props.filterFields) && props.filterFields.length ? props.filterFields : [props.labelKey];
			return filterGroups(groups.value, searchTerm.value, {
				fields,
				isStructural: isStructuralItem
			});
		});
		const hasFilteredItems = computed(() => filteredGroups.value.some((group) => group.some((item) => !isStructuralItem(item))));
		return (_ctx, _push, _parent, _attrs) => {
			_push(`<!--[-->`);
			_push(ssrRenderComponent(unref(DefineItemTemplate), null, {
				default: withCtx(({ item, active, index }, _push, _parent, _scopeId) => {
					if (_push) ssrRenderSlot(_ctx.$slots, item.slot || "item", {
						item,
						index,
						ui: __props.ui
					}, () => {
						ssrRenderSlot(_ctx.$slots, item.slot ? `${item.slot}-leading` : "item-leading", {
							item,
							active,
							index,
							ui: __props.ui
						}, () => {
							if (item.loading) _push(ssrRenderComponent(_sfc_main$20, {
								name: __props.loadingIcon || unref(appConfig).ui.icons.loading,
								"data-slot": "itemLeadingIcon",
								class: __props.ui.itemLeadingIcon({
									class: [__props.uiOverride?.itemLeadingIcon, item.ui?.itemLeadingIcon],
									color: item?.color,
									loading: true
								})
							}, null, _parent, _scopeId));
							else if (item.icon) _push(ssrRenderComponent(_sfc_main$20, {
								name: item.icon,
								"data-slot": "itemLeadingIcon",
								class: __props.ui.itemLeadingIcon({
									class: [__props.uiOverride?.itemLeadingIcon, item.ui?.itemLeadingIcon],
									color: item?.color,
									active
								})
							}, null, _parent, _scopeId));
							else if (item.avatar) _push(ssrRenderComponent(_sfc_main$21, mergeProps({ size: item.ui?.itemLeadingAvatarSize || __props.uiOverride?.itemLeadingAvatarSize || __props.ui.itemLeadingAvatarSize() }, item.avatar, {
								"data-slot": "itemLeadingAvatar",
								class: __props.ui.itemLeadingAvatar({
									class: [__props.uiOverride?.itemLeadingAvatar, item.ui?.itemLeadingAvatar],
									active
								})
							}), null, _parent, _scopeId));
							else _push(`<!---->`);
						}, _push, _parent, _scopeId);
						if (unref(get)(item, props.labelKey) || !!slots[item.slot ? `${item.slot}-label` : "item-label"] || unref(get)(item, props.descriptionKey) || !!slots[item.slot ? `${item.slot}-description` : "item-description"]) {
							_push(`<span data-slot="itemWrapper" class="${ssrRenderClass(__props.ui.itemWrapper({ class: [__props.uiOverride?.itemWrapper, item.ui?.itemWrapper] }))}"${_scopeId}><span data-slot="itemLabel" class="${ssrRenderClass(__props.ui.itemLabel({
								class: [__props.uiOverride?.itemLabel, item.ui?.itemLabel],
								active
							}))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, item.slot ? `${item.slot}-label` : "item-label", {
								item,
								active,
								index
							}, () => {
								_push(`${ssrInterpolate(unref(get)(item, props.labelKey))}`);
							}, _push, _parent, _scopeId);
							if (item.target === "_blank" && __props.externalIcon !== false) _push(ssrRenderComponent(_sfc_main$20, {
								name: typeof __props.externalIcon === "string" ? __props.externalIcon : unref(appConfig).ui.icons.external,
								"data-slot": "itemLabelExternalIcon",
								class: __props.ui.itemLabelExternalIcon({
									class: [__props.uiOverride?.itemLabelExternalIcon, item.ui?.itemLabelExternalIcon],
									color: item?.color,
									active
								})
							}, null, _parent, _scopeId));
							else _push(`<!---->`);
							_push(`</span>`);
							if (unref(get)(item, props.descriptionKey) || !!slots[item.slot ? `${item.slot}-description` : "item-description"]) {
								_push(`<span data-slot="itemDescription" class="${ssrRenderClass(__props.ui.itemDescription({ class: [__props.uiOverride?.itemDescription, item.ui?.itemDescription] }))}"${_scopeId}>`);
								ssrRenderSlot(_ctx.$slots, item.slot ? `${item.slot}-description` : "item-description", {
									item,
									active,
									index
								}, () => {
									_push(`${ssrInterpolate(unref(get)(item, props.descriptionKey))}`);
								}, _push, _parent, _scopeId);
								_push(`</span>`);
							} else _push(`<!---->`);
							_push(`</span>`);
						} else _push(`<!---->`);
						_push(`<span data-slot="itemTrailing" class="${ssrRenderClass(__props.ui.itemTrailing({ class: [__props.uiOverride?.itemTrailing, item.ui?.itemTrailing] }))}"${_scopeId}>`);
						ssrRenderSlot(_ctx.$slots, item.slot ? `${item.slot}-trailing` : "item-trailing", {
							item,
							active,
							index,
							ui: __props.ui
						}, () => {
							if (item.children?.length) _push(ssrRenderComponent(_sfc_main$20, {
								name: childrenIcon.value,
								"data-slot": "itemTrailingIcon",
								class: __props.ui.itemTrailingIcon({
									class: [__props.uiOverride?.itemTrailingIcon, item.ui?.itemTrailingIcon],
									color: item?.color,
									active
								})
							}, null, _parent, _scopeId));
							else if (item.kbds?.length) {
								_push(`<span data-slot="itemTrailingKbds" class="${ssrRenderClass(__props.ui.itemTrailingKbds({ class: [__props.uiOverride?.itemTrailingKbds, item.ui?.itemTrailingKbds] }))}"${_scopeId}><!--[-->`);
								ssrRenderList(item.kbds, (kbd, kbdIndex) => {
									_push(ssrRenderComponent(_sfc_main$39, mergeProps({
										key: kbdIndex,
										size: item.ui?.itemTrailingKbdsSize || __props.uiOverride?.itemTrailingKbdsSize || __props.ui.itemTrailingKbdsSize()
									}, { ref_for: true }, typeof kbd === "string" ? { value: kbd } : kbd), null, _parent, _scopeId));
								});
								_push(`<!--]--></span>`);
							} else _push(`<!---->`);
						}, _push, _parent, _scopeId);
						_push(ssrRenderComponent(unref(DropdownMenu).ItemIndicator, { "as-child": "" }, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(_sfc_main$20, {
									name: __props.checkedIcon || unref(appConfig).ui.icons.check,
									"data-slot": "itemTrailingIcon",
									class: __props.ui.itemTrailingIcon({
										class: [__props.uiOverride?.itemTrailingIcon, item.ui?.itemTrailingIcon],
										color: item?.color
									})
								}, null, _parent, _scopeId));
								else return [createVNode(_sfc_main$20, {
									name: __props.checkedIcon || unref(appConfig).ui.icons.check,
									"data-slot": "itemTrailingIcon",
									class: __props.ui.itemTrailingIcon({
										class: [__props.uiOverride?.itemTrailingIcon, item.ui?.itemTrailingIcon],
										color: item?.color
									})
								}, null, 8, ["name", "class"])];
							}),
							_: 2
						}, _parent, _scopeId));
						_push(`</span>`);
					}, _push, _parent, _scopeId);
					else return [renderSlot(_ctx.$slots, item.slot || "item", {
						item,
						index,
						ui: __props.ui
					}, () => [
						renderSlot(_ctx.$slots, item.slot ? `${item.slot}-leading` : "item-leading", {
							item,
							active,
							index,
							ui: __props.ui
						}, () => [item.loading ? (openBlock(), createBlock(_sfc_main$20, {
							key: 0,
							name: __props.loadingIcon || unref(appConfig).ui.icons.loading,
							"data-slot": "itemLeadingIcon",
							class: __props.ui.itemLeadingIcon({
								class: [__props.uiOverride?.itemLeadingIcon, item.ui?.itemLeadingIcon],
								color: item?.color,
								loading: true
							})
						}, null, 8, ["name", "class"])) : item.icon ? (openBlock(), createBlock(_sfc_main$20, {
							key: 1,
							name: item.icon,
							"data-slot": "itemLeadingIcon",
							class: __props.ui.itemLeadingIcon({
								class: [__props.uiOverride?.itemLeadingIcon, item.ui?.itemLeadingIcon],
								color: item?.color,
								active
							})
						}, null, 8, ["name", "class"])) : item.avatar ? (openBlock(), createBlock(_sfc_main$21, mergeProps({
							key: 2,
							size: item.ui?.itemLeadingAvatarSize || __props.uiOverride?.itemLeadingAvatarSize || __props.ui.itemLeadingAvatarSize()
						}, item.avatar, {
							"data-slot": "itemLeadingAvatar",
							class: __props.ui.itemLeadingAvatar({
								class: [__props.uiOverride?.itemLeadingAvatar, item.ui?.itemLeadingAvatar],
								active
							})
						}), null, 16, ["size", "class"])) : createCommentVNode("", true)]),
						unref(get)(item, props.labelKey) || !!slots[item.slot ? `${item.slot}-label` : "item-label"] || unref(get)(item, props.descriptionKey) || !!slots[item.slot ? `${item.slot}-description` : "item-description"] ? (openBlock(), createBlock("span", {
							key: 0,
							"data-slot": "itemWrapper",
							class: __props.ui.itemWrapper({ class: [__props.uiOverride?.itemWrapper, item.ui?.itemWrapper] })
						}, [createVNode("span", {
							"data-slot": "itemLabel",
							class: __props.ui.itemLabel({
								class: [__props.uiOverride?.itemLabel, item.ui?.itemLabel],
								active
							})
						}, [renderSlot(_ctx.$slots, item.slot ? `${item.slot}-label` : "item-label", {
							item,
							active,
							index
						}, () => [createTextVNode(toDisplayString(unref(get)(item, props.labelKey)), 1)]), item.target === "_blank" && __props.externalIcon !== false ? (openBlock(), createBlock(_sfc_main$20, {
							key: 0,
							name: typeof __props.externalIcon === "string" ? __props.externalIcon : unref(appConfig).ui.icons.external,
							"data-slot": "itemLabelExternalIcon",
							class: __props.ui.itemLabelExternalIcon({
								class: [__props.uiOverride?.itemLabelExternalIcon, item.ui?.itemLabelExternalIcon],
								color: item?.color,
								active
							})
						}, null, 8, ["name", "class"])) : createCommentVNode("", true)], 2), unref(get)(item, props.descriptionKey) || !!slots[item.slot ? `${item.slot}-description` : "item-description"] ? (openBlock(), createBlock("span", {
							key: 0,
							"data-slot": "itemDescription",
							class: __props.ui.itemDescription({ class: [__props.uiOverride?.itemDescription, item.ui?.itemDescription] })
						}, [renderSlot(_ctx.$slots, item.slot ? `${item.slot}-description` : "item-description", {
							item,
							active,
							index
						}, () => [createTextVNode(toDisplayString(unref(get)(item, props.descriptionKey)), 1)])], 2)) : createCommentVNode("", true)], 2)) : createCommentVNode("", true),
						createVNode("span", {
							"data-slot": "itemTrailing",
							class: __props.ui.itemTrailing({ class: [__props.uiOverride?.itemTrailing, item.ui?.itemTrailing] })
						}, [renderSlot(_ctx.$slots, item.slot ? `${item.slot}-trailing` : "item-trailing", {
							item,
							active,
							index,
							ui: __props.ui
						}, () => [item.children?.length ? (openBlock(), createBlock(_sfc_main$20, {
							key: 0,
							name: childrenIcon.value,
							"data-slot": "itemTrailingIcon",
							class: __props.ui.itemTrailingIcon({
								class: [__props.uiOverride?.itemTrailingIcon, item.ui?.itemTrailingIcon],
								color: item?.color,
								active
							})
						}, null, 8, ["name", "class"])) : item.kbds?.length ? (openBlock(), createBlock("span", {
							key: 1,
							"data-slot": "itemTrailingKbds",
							class: __props.ui.itemTrailingKbds({ class: [__props.uiOverride?.itemTrailingKbds, item.ui?.itemTrailingKbds] })
						}, [(openBlock(true), createBlock(Fragment, null, renderList(item.kbds, (kbd, kbdIndex) => {
							return openBlock(), createBlock(_sfc_main$39, mergeProps({
								key: kbdIndex,
								size: item.ui?.itemTrailingKbdsSize || __props.uiOverride?.itemTrailingKbdsSize || __props.ui.itemTrailingKbdsSize()
							}, { ref_for: true }, typeof kbd === "string" ? { value: kbd } : kbd), null, 16, ["size"]);
						}), 128))], 2)) : createCommentVNode("", true)]), createVNode(unref(DropdownMenu).ItemIndicator, { "as-child": "" }, {
							default: withCtx(() => [createVNode(_sfc_main$20, {
								name: __props.checkedIcon || unref(appConfig).ui.icons.check,
								"data-slot": "itemTrailingIcon",
								class: __props.ui.itemTrailingIcon({
									class: [__props.uiOverride?.itemTrailingIcon, item.ui?.itemTrailingIcon],
									color: item?.color
								})
							}, null, 8, ["name", "class"])]),
							_: 2
						}, 1024)], 2)
					])];
				}),
				_: 3
			}, _parent));
			_push(ssrRenderComponent(unref(DropdownMenu).Portal, unref(portalProps), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(ssrRenderComponent(unref(FieldGroupReset), null, {
						default: withCtx((_, _push, _parent, _scopeId) => {
							if (_push) ssrRenderVNode(_push, createVNode(resolveDynamicComponent(__props.sub ? unref(DropdownMenu).SubContent : unref(DropdownMenu).Content), mergeProps({
								"data-slot": "content",
								class: __props.ui.content({ class: [__props.uiOverride?.content, props.class] })
							}, unref(contentProps)), {
								default: withCtx((_, _push, _parent, _scopeId) => {
									if (_push) {
										if (!!__props.filter) _push(ssrRenderComponent(unref(DropdownMenu).Filter, {
											modelValue: searchTerm.value,
											"onUpdate:modelValue": ($event) => searchTerm.value = $event,
											"as-child": ""
										}, {
											default: withCtx((_, _push, _parent, _scopeId) => {
												if (_push) _push(ssrRenderComponent(_sfc_main$26, mergeProps({
													autofocus: "",
													autocomplete: "off",
													size: __props.size
												}, inputProps.value, {
													"data-slot": "input",
													class: __props.ui.input({ class: __props.uiOverride?.input }),
													onChange: () => {}
												}), null, _parent, _scopeId));
												else return [createVNode(_sfc_main$26, mergeProps({
													autofocus: "",
													autocomplete: "off",
													size: __props.size
												}, inputProps.value, {
													"data-slot": "input",
													class: __props.ui.input({ class: __props.uiOverride?.input }),
													onChange: withModifiers(() => {}, ["stop"])
												}), null, 16, [
													"size",
													"class",
													"onChange"
												])];
											}),
											_: 1
										}, _parent, _scopeId));
										else _push(`<!---->`);
										ssrRenderSlot(_ctx.$slots, "content-top", { sub: __props.sub ?? false }, null, _push, _parent, _scopeId);
										if (!searchTerm.value || hasFilteredItems.value) {
											_push(`<div role="presentation" data-slot="viewport" class="${ssrRenderClass(__props.ui.viewport({ class: __props.uiOverride?.viewport }))}"${_scopeId}><!--[-->`);
											ssrRenderList(filteredGroups.value, (group, groupIndex) => {
												_push(ssrRenderComponent(unref(DropdownMenu).Group, {
													key: `group-${groupIndex}`,
													"data-slot": "group",
													class: __props.ui.group({ class: __props.uiOverride?.group })
												}, {
													default: withCtx((_, _push, _parent, _scopeId) => {
														if (_push) {
															_push(`<!--[-->`);
															ssrRenderList(group, (item, index) => {
																_push(`<!--[-->`);
																if (item.type === "label") _push(ssrRenderComponent(unref(DropdownMenu).Label, {
																	"data-slot": "label",
																	class: __props.ui.label({ class: [
																		__props.uiOverride?.label,
																		item.ui?.label,
																		item.class
																	] })
																}, {
																	default: withCtx((_, _push, _parent, _scopeId) => {
																		if (_push) _push(ssrRenderComponent(unref(ReuseItemTemplate), {
																			item,
																			index
																		}, null, _parent, _scopeId));
																		else return [createVNode(unref(ReuseItemTemplate), {
																			item,
																			index
																		}, null, 8, ["item", "index"])];
																	}),
																	_: 2
																}, _parent, _scopeId));
																else if (item.type === "separator") _push(ssrRenderComponent(unref(DropdownMenu).Separator, {
																	"data-slot": "separator",
																	class: __props.ui.separator({ class: [
																		__props.uiOverride?.separator,
																		item.ui?.separator,
																		item.class
																	] })
																}, null, _parent, _scopeId));
																else if (item?.children?.length) _push(ssrRenderComponent(unref(DropdownMenu).Sub, {
																	open: item.open,
																	"default-open": item.defaultOpen
																}, {
																	default: withCtx((_, _push, _parent, _scopeId) => {
																		if (_push) {
																			_push(ssrRenderComponent(unref(DropdownMenu).SubTrigger, {
																				as: "button",
																				type: "button",
																				disabled: item.disabled,
																				"text-value": unref(get)(item, props.labelKey),
																				"data-slot": "item",
																				class: __props.ui.item({
																					class: [
																						__props.uiOverride?.item,
																						item.ui?.item,
																						item.class
																					],
																					color: item?.color
																				})
																			}, {
																				default: withCtx((_, _push, _parent, _scopeId) => {
																					if (_push) _push(ssrRenderComponent(unref(ReuseItemTemplate), {
																						item,
																						index
																					}, null, _parent, _scopeId));
																					else return [createVNode(unref(ReuseItemTemplate), {
																						item,
																						index
																					}, null, 8, ["item", "index"])];
																				}),
																				_: 2
																			}, _parent, _scopeId));
																			_push(ssrRenderComponent(_sfc_main$11, mergeProps({
																				sub: "",
																				class: item.ui?.content,
																				ui: __props.ui,
																				"ui-override": __props.uiOverride,
																				portal: __props.portal,
																				items: item.children,
																				align: "start",
																				"align-offset": -4,
																				"side-offset": 3,
																				"label-key": __props.labelKey,
																				"description-key": __props.descriptionKey,
																				"checked-icon": __props.checkedIcon,
																				"loading-icon": __props.loadingIcon,
																				"external-icon": __props.externalIcon,
																				size: __props.size,
																				filter: item.filter,
																				"filter-fields": item.filterFields || __props.filterFields,
																				"ignore-filter": item.ignoreFilter ?? __props.ignoreFilter
																			}, { ref_for: true }, item.content), createSlots({ _: 2 }, [renderList(getProxySlots(), (_, name) => {
																				return {
																					name,
																					fn: withCtx((slotData, _push, _parent, _scopeId) => {
																						if (_push) ssrRenderSlot(_ctx.$slots, name, mergeProps({ ref_for: true }, slotData), null, _push, _parent, _scopeId);
																						else return [renderSlot(_ctx.$slots, name, mergeProps({ ref_for: true }, slotData))];
																					})
																				};
																			})]), _parent, _scopeId));
																		} else return [createVNode(unref(DropdownMenu).SubTrigger, {
																			as: "button",
																			type: "button",
																			disabled: item.disabled,
																			"text-value": unref(get)(item, props.labelKey),
																			"data-slot": "item",
																			class: __props.ui.item({
																				class: [
																					__props.uiOverride?.item,
																					item.ui?.item,
																					item.class
																				],
																				color: item?.color
																			})
																		}, {
																			default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
																				item,
																				index
																			}, null, 8, ["item", "index"])]),
																			_: 2
																		}, 1032, [
																			"disabled",
																			"text-value",
																			"class"
																		]), createVNode(_sfc_main$11, mergeProps({
																			sub: "",
																			class: item.ui?.content,
																			ui: __props.ui,
																			"ui-override": __props.uiOverride,
																			portal: __props.portal,
																			items: item.children,
																			align: "start",
																			"align-offset": -4,
																			"side-offset": 3,
																			"label-key": __props.labelKey,
																			"description-key": __props.descriptionKey,
																			"checked-icon": __props.checkedIcon,
																			"loading-icon": __props.loadingIcon,
																			"external-icon": __props.externalIcon,
																			size: __props.size,
																			filter: item.filter,
																			"filter-fields": item.filterFields || __props.filterFields,
																			"ignore-filter": item.ignoreFilter ?? __props.ignoreFilter
																		}, { ref_for: true }, item.content), createSlots({ _: 2 }, [renderList(getProxySlots(), (_, name) => {
																			return {
																				name,
																				fn: withCtx((slotData) => [renderSlot(_ctx.$slots, name, mergeProps({ ref_for: true }, slotData))])
																			};
																		})]), 1040, [
																			"class",
																			"ui",
																			"ui-override",
																			"portal",
																			"items",
																			"label-key",
																			"description-key",
																			"checked-icon",
																			"loading-icon",
																			"external-icon",
																			"size",
																			"filter",
																			"filter-fields",
																			"ignore-filter"
																		])];
																	}),
																	_: 2
																}, _parent, _scopeId));
																else if (item.type === "checkbox") _push(ssrRenderComponent(unref(DropdownMenu).CheckboxItem, {
																	"model-value": item.checked,
																	disabled: item.disabled,
																	"text-value": unref(get)(item, props.labelKey),
																	"data-slot": "item",
																	class: __props.ui.item({
																		class: [
																			__props.uiOverride?.item,
																			item.ui?.item,
																			item.class
																		],
																		color: item?.color
																	}),
																	"onUpdate:modelValue": item.onUpdateChecked,
																	onSelect: item.onSelect
																}, {
																	default: withCtx((_, _push, _parent, _scopeId) => {
																		if (_push) _push(ssrRenderComponent(unref(ReuseItemTemplate), {
																			item,
																			index
																		}, null, _parent, _scopeId));
																		else return [createVNode(unref(ReuseItemTemplate), {
																			item,
																			index
																		}, null, 8, ["item", "index"])];
																	}),
																	_: 2
																}, _parent, _scopeId));
																else _push(ssrRenderComponent(_sfc_main$23, mergeProps({ ref_for: true }, unref(pickLinkProps)(item), { custom: "" }), {
																	default: withCtx(({ active, ...slotProps }, _push, _parent, _scopeId) => {
																		if (_push) _push(ssrRenderComponent(unref(DropdownMenu).Item, {
																			"as-child": "",
																			disabled: item.disabled,
																			"text-value": unref(get)(item, props.labelKey),
																			onSelect: item.onSelect
																		}, {
																			default: withCtx((_, _push, _parent, _scopeId) => {
																				if (_push) _push(ssrRenderComponent(_sfc_main$24, mergeProps({ ref_for: true }, slotProps, {
																					"data-slot": "item",
																					class: __props.ui.item({
																						class: [
																							__props.uiOverride?.item,
																							item.ui?.item,
																							item.class
																						],
																						color: item?.color,
																						active
																					})
																				}), {
																					default: withCtx((_, _push, _parent, _scopeId) => {
																						if (_push) _push(ssrRenderComponent(unref(ReuseItemTemplate), {
																							item,
																							active,
																							index
																						}, null, _parent, _scopeId));
																						else return [createVNode(unref(ReuseItemTemplate), {
																							item,
																							active,
																							index
																						}, null, 8, [
																							"item",
																							"active",
																							"index"
																						])];
																					}),
																					_: 2
																				}, _parent, _scopeId));
																				else return [createVNode(_sfc_main$24, mergeProps({ ref_for: true }, slotProps, {
																					"data-slot": "item",
																					class: __props.ui.item({
																						class: [
																							__props.uiOverride?.item,
																							item.ui?.item,
																							item.class
																						],
																						color: item?.color,
																						active
																					})
																				}), {
																					default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
																						item,
																						active,
																						index
																					}, null, 8, [
																						"item",
																						"active",
																						"index"
																					])]),
																					_: 2
																				}, 1040, ["class"])];
																			}),
																			_: 2
																		}, _parent, _scopeId));
																		else return [createVNode(unref(DropdownMenu).Item, {
																			"as-child": "",
																			disabled: item.disabled,
																			"text-value": unref(get)(item, props.labelKey),
																			onSelect: item.onSelect
																		}, {
																			default: withCtx(() => [createVNode(_sfc_main$24, mergeProps({ ref_for: true }, slotProps, {
																				"data-slot": "item",
																				class: __props.ui.item({
																					class: [
																						__props.uiOverride?.item,
																						item.ui?.item,
																						item.class
																					],
																					color: item?.color,
																					active
																				})
																			}), {
																				default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
																					item,
																					active,
																					index
																				}, null, 8, [
																					"item",
																					"active",
																					"index"
																				])]),
																				_: 2
																			}, 1040, ["class"])]),
																			_: 2
																		}, 1032, [
																			"disabled",
																			"text-value",
																			"onSelect"
																		])];
																	}),
																	_: 2
																}, _parent, _scopeId));
																_push(`<!--]-->`);
															});
															_push(`<!--]-->`);
														} else return [(openBlock(true), createBlock(Fragment, null, renderList(group, (item, index) => {
															return openBlock(), createBlock(Fragment, { key: `group-${groupIndex}-${index}` }, [item.type === "label" ? (openBlock(), createBlock(unref(DropdownMenu).Label, {
																key: 0,
																"data-slot": "label",
																class: __props.ui.label({ class: [
																	__props.uiOverride?.label,
																	item.ui?.label,
																	item.class
																] })
															}, {
																default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
																	item,
																	index
																}, null, 8, ["item", "index"])]),
																_: 2
															}, 1032, ["class"])) : item.type === "separator" ? (openBlock(), createBlock(unref(DropdownMenu).Separator, {
																key: 1,
																"data-slot": "separator",
																class: __props.ui.separator({ class: [
																	__props.uiOverride?.separator,
																	item.ui?.separator,
																	item.class
																] })
															}, null, 8, ["class"])) : item?.children?.length ? (openBlock(), createBlock(unref(DropdownMenu).Sub, {
																key: 2,
																open: item.open,
																"default-open": item.defaultOpen
															}, {
																default: withCtx(() => [createVNode(unref(DropdownMenu).SubTrigger, {
																	as: "button",
																	type: "button",
																	disabled: item.disabled,
																	"text-value": unref(get)(item, props.labelKey),
																	"data-slot": "item",
																	class: __props.ui.item({
																		class: [
																			__props.uiOverride?.item,
																			item.ui?.item,
																			item.class
																		],
																		color: item?.color
																	})
																}, {
																	default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
																		item,
																		index
																	}, null, 8, ["item", "index"])]),
																	_: 2
																}, 1032, [
																	"disabled",
																	"text-value",
																	"class"
																]), createVNode(_sfc_main$11, mergeProps({
																	sub: "",
																	class: item.ui?.content,
																	ui: __props.ui,
																	"ui-override": __props.uiOverride,
																	portal: __props.portal,
																	items: item.children,
																	align: "start",
																	"align-offset": -4,
																	"side-offset": 3,
																	"label-key": __props.labelKey,
																	"description-key": __props.descriptionKey,
																	"checked-icon": __props.checkedIcon,
																	"loading-icon": __props.loadingIcon,
																	"external-icon": __props.externalIcon,
																	size: __props.size,
																	filter: item.filter,
																	"filter-fields": item.filterFields || __props.filterFields,
																	"ignore-filter": item.ignoreFilter ?? __props.ignoreFilter
																}, { ref_for: true }, item.content), createSlots({ _: 2 }, [renderList(getProxySlots(), (_, name) => {
																	return {
																		name,
																		fn: withCtx((slotData) => [renderSlot(_ctx.$slots, name, mergeProps({ ref_for: true }, slotData))])
																	};
																})]), 1040, [
																	"class",
																	"ui",
																	"ui-override",
																	"portal",
																	"items",
																	"label-key",
																	"description-key",
																	"checked-icon",
																	"loading-icon",
																	"external-icon",
																	"size",
																	"filter",
																	"filter-fields",
																	"ignore-filter"
																])]),
																_: 2
															}, 1032, ["open", "default-open"])) : item.type === "checkbox" ? (openBlock(), createBlock(unref(DropdownMenu).CheckboxItem, {
																key: 3,
																"model-value": item.checked,
																disabled: item.disabled,
																"text-value": unref(get)(item, props.labelKey),
																"data-slot": "item",
																class: __props.ui.item({
																	class: [
																		__props.uiOverride?.item,
																		item.ui?.item,
																		item.class
																	],
																	color: item?.color
																}),
																"onUpdate:modelValue": item.onUpdateChecked,
																onSelect: item.onSelect
															}, {
																default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
																	item,
																	index
																}, null, 8, ["item", "index"])]),
																_: 2
															}, 1032, [
																"model-value",
																"disabled",
																"text-value",
																"class",
																"onUpdate:modelValue",
																"onSelect"
															])) : (openBlock(), createBlock(_sfc_main$23, mergeProps({
																key: 4,
																ref_for: true
															}, unref(pickLinkProps)(item), { custom: "" }), {
																default: withCtx(({ active, ...slotProps }) => [createVNode(unref(DropdownMenu).Item, {
																	"as-child": "",
																	disabled: item.disabled,
																	"text-value": unref(get)(item, props.labelKey),
																	onSelect: item.onSelect
																}, {
																	default: withCtx(() => [createVNode(_sfc_main$24, mergeProps({ ref_for: true }, slotProps, {
																		"data-slot": "item",
																		class: __props.ui.item({
																			class: [
																				__props.uiOverride?.item,
																				item.ui?.item,
																				item.class
																			],
																			color: item?.color,
																			active
																		})
																	}), {
																		default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
																			item,
																			active,
																			index
																		}, null, 8, [
																			"item",
																			"active",
																			"index"
																		])]),
																		_: 2
																	}, 1040, ["class"])]),
																	_: 2
																}, 1032, [
																	"disabled",
																	"text-value",
																	"onSelect"
																])]),
																_: 2
															}, 1040))], 64);
														}), 128))];
													}),
													_: 2
												}, _parent, _scopeId));
											});
											_push(`<!--]--></div>`);
										} else _push(`<!---->`);
										if (searchTerm.value && !hasFilteredItems.value) {
											_push(`<div data-slot="empty" class="${ssrRenderClass(__props.ui.empty({ class: __props.uiOverride?.empty }))}"${_scopeId}>`);
											ssrRenderSlot(_ctx.$slots, "empty", { searchTerm: searchTerm.value }, () => {
												_push(`${ssrInterpolate(unref(t)("dropdownMenu.noMatch", { searchTerm: searchTerm.value }))}`);
											}, _push, _parent, _scopeId);
											_push(`</div>`);
										} else _push(`<!---->`);
										ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
										ssrRenderSlot(_ctx.$slots, "content-bottom", { sub: __props.sub ?? false }, null, _push, _parent, _scopeId);
									} else return [
										!!__props.filter ? (openBlock(), createBlock(unref(DropdownMenu).Filter, {
											key: 0,
											modelValue: searchTerm.value,
											"onUpdate:modelValue": ($event) => searchTerm.value = $event,
											"as-child": ""
										}, {
											default: withCtx(() => [createVNode(_sfc_main$26, mergeProps({
												autofocus: "",
												autocomplete: "off",
												size: __props.size
											}, inputProps.value, {
												"data-slot": "input",
												class: __props.ui.input({ class: __props.uiOverride?.input }),
												onChange: withModifiers(() => {}, ["stop"])
											}), null, 16, [
												"size",
												"class",
												"onChange"
											])]),
											_: 1
										}, 8, ["modelValue", "onUpdate:modelValue"])) : createCommentVNode("", true),
										renderSlot(_ctx.$slots, "content-top", { sub: __props.sub ?? false }),
										!searchTerm.value || hasFilteredItems.value ? (openBlock(), createBlock("div", {
											key: 1,
											role: "presentation",
											"data-slot": "viewport",
											class: __props.ui.viewport({ class: __props.uiOverride?.viewport })
										}, [(openBlock(true), createBlock(Fragment, null, renderList(filteredGroups.value, (group, groupIndex) => {
											return openBlock(), createBlock(unref(DropdownMenu).Group, {
												key: `group-${groupIndex}`,
												"data-slot": "group",
												class: __props.ui.group({ class: __props.uiOverride?.group })
											}, {
												default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(group, (item, index) => {
													return openBlock(), createBlock(Fragment, { key: `group-${groupIndex}-${index}` }, [item.type === "label" ? (openBlock(), createBlock(unref(DropdownMenu).Label, {
														key: 0,
														"data-slot": "label",
														class: __props.ui.label({ class: [
															__props.uiOverride?.label,
															item.ui?.label,
															item.class
														] })
													}, {
														default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
															item,
															index
														}, null, 8, ["item", "index"])]),
														_: 2
													}, 1032, ["class"])) : item.type === "separator" ? (openBlock(), createBlock(unref(DropdownMenu).Separator, {
														key: 1,
														"data-slot": "separator",
														class: __props.ui.separator({ class: [
															__props.uiOverride?.separator,
															item.ui?.separator,
															item.class
														] })
													}, null, 8, ["class"])) : item?.children?.length ? (openBlock(), createBlock(unref(DropdownMenu).Sub, {
														key: 2,
														open: item.open,
														"default-open": item.defaultOpen
													}, {
														default: withCtx(() => [createVNode(unref(DropdownMenu).SubTrigger, {
															as: "button",
															type: "button",
															disabled: item.disabled,
															"text-value": unref(get)(item, props.labelKey),
															"data-slot": "item",
															class: __props.ui.item({
																class: [
																	__props.uiOverride?.item,
																	item.ui?.item,
																	item.class
																],
																color: item?.color
															})
														}, {
															default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
																item,
																index
															}, null, 8, ["item", "index"])]),
															_: 2
														}, 1032, [
															"disabled",
															"text-value",
															"class"
														]), createVNode(_sfc_main$11, mergeProps({
															sub: "",
															class: item.ui?.content,
															ui: __props.ui,
															"ui-override": __props.uiOverride,
															portal: __props.portal,
															items: item.children,
															align: "start",
															"align-offset": -4,
															"side-offset": 3,
															"label-key": __props.labelKey,
															"description-key": __props.descriptionKey,
															"checked-icon": __props.checkedIcon,
															"loading-icon": __props.loadingIcon,
															"external-icon": __props.externalIcon,
															size: __props.size,
															filter: item.filter,
															"filter-fields": item.filterFields || __props.filterFields,
															"ignore-filter": item.ignoreFilter ?? __props.ignoreFilter
														}, { ref_for: true }, item.content), createSlots({ _: 2 }, [renderList(getProxySlots(), (_, name) => {
															return {
																name,
																fn: withCtx((slotData) => [renderSlot(_ctx.$slots, name, mergeProps({ ref_for: true }, slotData))])
															};
														})]), 1040, [
															"class",
															"ui",
															"ui-override",
															"portal",
															"items",
															"label-key",
															"description-key",
															"checked-icon",
															"loading-icon",
															"external-icon",
															"size",
															"filter",
															"filter-fields",
															"ignore-filter"
														])]),
														_: 2
													}, 1032, ["open", "default-open"])) : item.type === "checkbox" ? (openBlock(), createBlock(unref(DropdownMenu).CheckboxItem, {
														key: 3,
														"model-value": item.checked,
														disabled: item.disabled,
														"text-value": unref(get)(item, props.labelKey),
														"data-slot": "item",
														class: __props.ui.item({
															class: [
																__props.uiOverride?.item,
																item.ui?.item,
																item.class
															],
															color: item?.color
														}),
														"onUpdate:modelValue": item.onUpdateChecked,
														onSelect: item.onSelect
													}, {
														default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
															item,
															index
														}, null, 8, ["item", "index"])]),
														_: 2
													}, 1032, [
														"model-value",
														"disabled",
														"text-value",
														"class",
														"onUpdate:modelValue",
														"onSelect"
													])) : (openBlock(), createBlock(_sfc_main$23, mergeProps({
														key: 4,
														ref_for: true
													}, unref(pickLinkProps)(item), { custom: "" }), {
														default: withCtx(({ active, ...slotProps }) => [createVNode(unref(DropdownMenu).Item, {
															"as-child": "",
															disabled: item.disabled,
															"text-value": unref(get)(item, props.labelKey),
															onSelect: item.onSelect
														}, {
															default: withCtx(() => [createVNode(_sfc_main$24, mergeProps({ ref_for: true }, slotProps, {
																"data-slot": "item",
																class: __props.ui.item({
																	class: [
																		__props.uiOverride?.item,
																		item.ui?.item,
																		item.class
																	],
																	color: item?.color,
																	active
																})
															}), {
																default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
																	item,
																	active,
																	index
																}, null, 8, [
																	"item",
																	"active",
																	"index"
																])]),
																_: 2
															}, 1040, ["class"])]),
															_: 2
														}, 1032, [
															"disabled",
															"text-value",
															"onSelect"
														])]),
														_: 2
													}, 1040))], 64);
												}), 128))]),
												_: 2
											}, 1032, ["class"]);
										}), 128))], 2)) : createCommentVNode("", true),
										searchTerm.value && !hasFilteredItems.value ? (openBlock(), createBlock("div", {
											key: 2,
											"data-slot": "empty",
											class: __props.ui.empty({ class: __props.uiOverride?.empty })
										}, [renderSlot(_ctx.$slots, "empty", { searchTerm: searchTerm.value }, () => [createTextVNode(toDisplayString(unref(t)("dropdownMenu.noMatch", { searchTerm: searchTerm.value })), 1)])], 2)) : createCommentVNode("", true),
										renderSlot(_ctx.$slots, "default"),
										renderSlot(_ctx.$slots, "content-bottom", { sub: __props.sub ?? false })
									];
								}),
								_: 3
							}), _parent, _scopeId);
							else return [(openBlock(), createBlock(resolveDynamicComponent(__props.sub ? unref(DropdownMenu).SubContent : unref(DropdownMenu).Content), mergeProps({
								"data-slot": "content",
								class: __props.ui.content({ class: [__props.uiOverride?.content, props.class] })
							}, unref(contentProps)), {
								default: withCtx(() => [
									!!__props.filter ? (openBlock(), createBlock(unref(DropdownMenu).Filter, {
										key: 0,
										modelValue: searchTerm.value,
										"onUpdate:modelValue": ($event) => searchTerm.value = $event,
										"as-child": ""
									}, {
										default: withCtx(() => [createVNode(_sfc_main$26, mergeProps({
											autofocus: "",
											autocomplete: "off",
											size: __props.size
										}, inputProps.value, {
											"data-slot": "input",
											class: __props.ui.input({ class: __props.uiOverride?.input }),
											onChange: withModifiers(() => {}, ["stop"])
										}), null, 16, [
											"size",
											"class",
											"onChange"
										])]),
										_: 1
									}, 8, ["modelValue", "onUpdate:modelValue"])) : createCommentVNode("", true),
									renderSlot(_ctx.$slots, "content-top", { sub: __props.sub ?? false }),
									!searchTerm.value || hasFilteredItems.value ? (openBlock(), createBlock("div", {
										key: 1,
										role: "presentation",
										"data-slot": "viewport",
										class: __props.ui.viewport({ class: __props.uiOverride?.viewport })
									}, [(openBlock(true), createBlock(Fragment, null, renderList(filteredGroups.value, (group, groupIndex) => {
										return openBlock(), createBlock(unref(DropdownMenu).Group, {
											key: `group-${groupIndex}`,
											"data-slot": "group",
											class: __props.ui.group({ class: __props.uiOverride?.group })
										}, {
											default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(group, (item, index) => {
												return openBlock(), createBlock(Fragment, { key: `group-${groupIndex}-${index}` }, [item.type === "label" ? (openBlock(), createBlock(unref(DropdownMenu).Label, {
													key: 0,
													"data-slot": "label",
													class: __props.ui.label({ class: [
														__props.uiOverride?.label,
														item.ui?.label,
														item.class
													] })
												}, {
													default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
														item,
														index
													}, null, 8, ["item", "index"])]),
													_: 2
												}, 1032, ["class"])) : item.type === "separator" ? (openBlock(), createBlock(unref(DropdownMenu).Separator, {
													key: 1,
													"data-slot": "separator",
													class: __props.ui.separator({ class: [
														__props.uiOverride?.separator,
														item.ui?.separator,
														item.class
													] })
												}, null, 8, ["class"])) : item?.children?.length ? (openBlock(), createBlock(unref(DropdownMenu).Sub, {
													key: 2,
													open: item.open,
													"default-open": item.defaultOpen
												}, {
													default: withCtx(() => [createVNode(unref(DropdownMenu).SubTrigger, {
														as: "button",
														type: "button",
														disabled: item.disabled,
														"text-value": unref(get)(item, props.labelKey),
														"data-slot": "item",
														class: __props.ui.item({
															class: [
																__props.uiOverride?.item,
																item.ui?.item,
																item.class
															],
															color: item?.color
														})
													}, {
														default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
															item,
															index
														}, null, 8, ["item", "index"])]),
														_: 2
													}, 1032, [
														"disabled",
														"text-value",
														"class"
													]), createVNode(_sfc_main$11, mergeProps({
														sub: "",
														class: item.ui?.content,
														ui: __props.ui,
														"ui-override": __props.uiOverride,
														portal: __props.portal,
														items: item.children,
														align: "start",
														"align-offset": -4,
														"side-offset": 3,
														"label-key": __props.labelKey,
														"description-key": __props.descriptionKey,
														"checked-icon": __props.checkedIcon,
														"loading-icon": __props.loadingIcon,
														"external-icon": __props.externalIcon,
														size: __props.size,
														filter: item.filter,
														"filter-fields": item.filterFields || __props.filterFields,
														"ignore-filter": item.ignoreFilter ?? __props.ignoreFilter
													}, { ref_for: true }, item.content), createSlots({ _: 2 }, [renderList(getProxySlots(), (_, name) => {
														return {
															name,
															fn: withCtx((slotData) => [renderSlot(_ctx.$slots, name, mergeProps({ ref_for: true }, slotData))])
														};
													})]), 1040, [
														"class",
														"ui",
														"ui-override",
														"portal",
														"items",
														"label-key",
														"description-key",
														"checked-icon",
														"loading-icon",
														"external-icon",
														"size",
														"filter",
														"filter-fields",
														"ignore-filter"
													])]),
													_: 2
												}, 1032, ["open", "default-open"])) : item.type === "checkbox" ? (openBlock(), createBlock(unref(DropdownMenu).CheckboxItem, {
													key: 3,
													"model-value": item.checked,
													disabled: item.disabled,
													"text-value": unref(get)(item, props.labelKey),
													"data-slot": "item",
													class: __props.ui.item({
														class: [
															__props.uiOverride?.item,
															item.ui?.item,
															item.class
														],
														color: item?.color
													}),
													"onUpdate:modelValue": item.onUpdateChecked,
													onSelect: item.onSelect
												}, {
													default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
														item,
														index
													}, null, 8, ["item", "index"])]),
													_: 2
												}, 1032, [
													"model-value",
													"disabled",
													"text-value",
													"class",
													"onUpdate:modelValue",
													"onSelect"
												])) : (openBlock(), createBlock(_sfc_main$23, mergeProps({
													key: 4,
													ref_for: true
												}, unref(pickLinkProps)(item), { custom: "" }), {
													default: withCtx(({ active, ...slotProps }) => [createVNode(unref(DropdownMenu).Item, {
														"as-child": "",
														disabled: item.disabled,
														"text-value": unref(get)(item, props.labelKey),
														onSelect: item.onSelect
													}, {
														default: withCtx(() => [createVNode(_sfc_main$24, mergeProps({ ref_for: true }, slotProps, {
															"data-slot": "item",
															class: __props.ui.item({
																class: [
																	__props.uiOverride?.item,
																	item.ui?.item,
																	item.class
																],
																color: item?.color,
																active
															})
														}), {
															default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
																item,
																active,
																index
															}, null, 8, [
																"item",
																"active",
																"index"
															])]),
															_: 2
														}, 1040, ["class"])]),
														_: 2
													}, 1032, [
														"disabled",
														"text-value",
														"onSelect"
													])]),
													_: 2
												}, 1040))], 64);
											}), 128))]),
											_: 2
										}, 1032, ["class"]);
									}), 128))], 2)) : createCommentVNode("", true),
									searchTerm.value && !hasFilteredItems.value ? (openBlock(), createBlock("div", {
										key: 2,
										"data-slot": "empty",
										class: __props.ui.empty({ class: __props.uiOverride?.empty })
									}, [renderSlot(_ctx.$slots, "empty", { searchTerm: searchTerm.value }, () => [createTextVNode(toDisplayString(unref(t)("dropdownMenu.noMatch", { searchTerm: searchTerm.value })), 1)])], 2)) : createCommentVNode("", true),
									renderSlot(_ctx.$slots, "default"),
									renderSlot(_ctx.$slots, "content-bottom", { sub: __props.sub ?? false })
								]),
								_: 3
							}, 16, ["class"]))];
						}),
						_: 3
					}, _parent, _scopeId));
					else return [createVNode(unref(FieldGroupReset), null, {
						default: withCtx(() => [(openBlock(), createBlock(resolveDynamicComponent(__props.sub ? unref(DropdownMenu).SubContent : unref(DropdownMenu).Content), mergeProps({
							"data-slot": "content",
							class: __props.ui.content({ class: [__props.uiOverride?.content, props.class] })
						}, unref(contentProps)), {
							default: withCtx(() => [
								!!__props.filter ? (openBlock(), createBlock(unref(DropdownMenu).Filter, {
									key: 0,
									modelValue: searchTerm.value,
									"onUpdate:modelValue": ($event) => searchTerm.value = $event,
									"as-child": ""
								}, {
									default: withCtx(() => [createVNode(_sfc_main$26, mergeProps({
										autofocus: "",
										autocomplete: "off",
										size: __props.size
									}, inputProps.value, {
										"data-slot": "input",
										class: __props.ui.input({ class: __props.uiOverride?.input }),
										onChange: withModifiers(() => {}, ["stop"])
									}), null, 16, [
										"size",
										"class",
										"onChange"
									])]),
									_: 1
								}, 8, ["modelValue", "onUpdate:modelValue"])) : createCommentVNode("", true),
								renderSlot(_ctx.$slots, "content-top", { sub: __props.sub ?? false }),
								!searchTerm.value || hasFilteredItems.value ? (openBlock(), createBlock("div", {
									key: 1,
									role: "presentation",
									"data-slot": "viewport",
									class: __props.ui.viewport({ class: __props.uiOverride?.viewport })
								}, [(openBlock(true), createBlock(Fragment, null, renderList(filteredGroups.value, (group, groupIndex) => {
									return openBlock(), createBlock(unref(DropdownMenu).Group, {
										key: `group-${groupIndex}`,
										"data-slot": "group",
										class: __props.ui.group({ class: __props.uiOverride?.group })
									}, {
										default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(group, (item, index) => {
											return openBlock(), createBlock(Fragment, { key: `group-${groupIndex}-${index}` }, [item.type === "label" ? (openBlock(), createBlock(unref(DropdownMenu).Label, {
												key: 0,
												"data-slot": "label",
												class: __props.ui.label({ class: [
													__props.uiOverride?.label,
													item.ui?.label,
													item.class
												] })
											}, {
												default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
													item,
													index
												}, null, 8, ["item", "index"])]),
												_: 2
											}, 1032, ["class"])) : item.type === "separator" ? (openBlock(), createBlock(unref(DropdownMenu).Separator, {
												key: 1,
												"data-slot": "separator",
												class: __props.ui.separator({ class: [
													__props.uiOverride?.separator,
													item.ui?.separator,
													item.class
												] })
											}, null, 8, ["class"])) : item?.children?.length ? (openBlock(), createBlock(unref(DropdownMenu).Sub, {
												key: 2,
												open: item.open,
												"default-open": item.defaultOpen
											}, {
												default: withCtx(() => [createVNode(unref(DropdownMenu).SubTrigger, {
													as: "button",
													type: "button",
													disabled: item.disabled,
													"text-value": unref(get)(item, props.labelKey),
													"data-slot": "item",
													class: __props.ui.item({
														class: [
															__props.uiOverride?.item,
															item.ui?.item,
															item.class
														],
														color: item?.color
													})
												}, {
													default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
														item,
														index
													}, null, 8, ["item", "index"])]),
													_: 2
												}, 1032, [
													"disabled",
													"text-value",
													"class"
												]), createVNode(_sfc_main$11, mergeProps({
													sub: "",
													class: item.ui?.content,
													ui: __props.ui,
													"ui-override": __props.uiOverride,
													portal: __props.portal,
													items: item.children,
													align: "start",
													"align-offset": -4,
													"side-offset": 3,
													"label-key": __props.labelKey,
													"description-key": __props.descriptionKey,
													"checked-icon": __props.checkedIcon,
													"loading-icon": __props.loadingIcon,
													"external-icon": __props.externalIcon,
													size: __props.size,
													filter: item.filter,
													"filter-fields": item.filterFields || __props.filterFields,
													"ignore-filter": item.ignoreFilter ?? __props.ignoreFilter
												}, { ref_for: true }, item.content), createSlots({ _: 2 }, [renderList(getProxySlots(), (_, name) => {
													return {
														name,
														fn: withCtx((slotData) => [renderSlot(_ctx.$slots, name, mergeProps({ ref_for: true }, slotData))])
													};
												})]), 1040, [
													"class",
													"ui",
													"ui-override",
													"portal",
													"items",
													"label-key",
													"description-key",
													"checked-icon",
													"loading-icon",
													"external-icon",
													"size",
													"filter",
													"filter-fields",
													"ignore-filter"
												])]),
												_: 2
											}, 1032, ["open", "default-open"])) : item.type === "checkbox" ? (openBlock(), createBlock(unref(DropdownMenu).CheckboxItem, {
												key: 3,
												"model-value": item.checked,
												disabled: item.disabled,
												"text-value": unref(get)(item, props.labelKey),
												"data-slot": "item",
												class: __props.ui.item({
													class: [
														__props.uiOverride?.item,
														item.ui?.item,
														item.class
													],
													color: item?.color
												}),
												"onUpdate:modelValue": item.onUpdateChecked,
												onSelect: item.onSelect
											}, {
												default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
													item,
													index
												}, null, 8, ["item", "index"])]),
												_: 2
											}, 1032, [
												"model-value",
												"disabled",
												"text-value",
												"class",
												"onUpdate:modelValue",
												"onSelect"
											])) : (openBlock(), createBlock(_sfc_main$23, mergeProps({
												key: 4,
												ref_for: true
											}, unref(pickLinkProps)(item), { custom: "" }), {
												default: withCtx(({ active, ...slotProps }) => [createVNode(unref(DropdownMenu).Item, {
													"as-child": "",
													disabled: item.disabled,
													"text-value": unref(get)(item, props.labelKey),
													onSelect: item.onSelect
												}, {
													default: withCtx(() => [createVNode(_sfc_main$24, mergeProps({ ref_for: true }, slotProps, {
														"data-slot": "item",
														class: __props.ui.item({
															class: [
																__props.uiOverride?.item,
																item.ui?.item,
																item.class
															],
															color: item?.color,
															active
														})
													}), {
														default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
															item,
															active,
															index
														}, null, 8, [
															"item",
															"active",
															"index"
														])]),
														_: 2
													}, 1040, ["class"])]),
													_: 2
												}, 1032, [
													"disabled",
													"text-value",
													"onSelect"
												])]),
												_: 2
											}, 1040))], 64);
										}), 128))]),
										_: 2
									}, 1032, ["class"]);
								}), 128))], 2)) : createCommentVNode("", true),
								searchTerm.value && !hasFilteredItems.value ? (openBlock(), createBlock("div", {
									key: 2,
									"data-slot": "empty",
									class: __props.ui.empty({ class: __props.uiOverride?.empty })
								}, [renderSlot(_ctx.$slots, "empty", { searchTerm: searchTerm.value }, () => [createTextVNode(toDisplayString(unref(t)("dropdownMenu.noMatch", { searchTerm: searchTerm.value })), 1)])], 2)) : createCommentVNode("", true),
								renderSlot(_ctx.$slots, "default"),
								renderSlot(_ctx.$slots, "content-bottom", { sub: __props.sub ?? false })
							]),
							_: 3
						}, 16, ["class"]))]),
						_: 3
					})];
				}),
				_: 3
			}, _parent));
			_push(`<!--]-->`);
		};
	}
};
var _sfc_setup$20 = _sfc_main$11.setup;
_sfc_main$11.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/DropdownMenuContent.vue");
	return _sfc_setup$20 ? _sfc_setup$20(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/dropdown-menu.ts
var dropdown_menu_default = {
	"slots": {
		"content": "min-w-32 bg-default shadow-lg rounded-md ring ring-default overflow-hidden data-[state=open]:animate-[scale-in_100ms_ease-out] data-[state=closed]:animate-[scale-out_100ms_ease-in] origin-(--reka-dropdown-menu-content-transform-origin) flex flex-col",
		"input": "border-b border-default",
		"empty": "text-center text-muted",
		"viewport": "relative divide-y divide-default scroll-py-1 overflow-y-auto flex-1",
		"arrow": "fill-bg stroke-default",
		"group": "p-1 isolate",
		"label": "w-full flex items-center font-semibold text-highlighted",
		"separator": "-mx-1 my-1 h-px bg-border",
		"item": "group relative w-full flex items-start select-none outline-none before:absolute before:z-[-1] before:inset-px before:rounded-md data-disabled:cursor-not-allowed data-disabled:opacity-75",
		"itemLeadingIcon": "shrink-0",
		"itemLeadingAvatar": "shrink-0",
		"itemLeadingAvatarSize": "",
		"itemTrailing": "ms-auto inline-flex gap-1.5 items-center",
		"itemTrailingIcon": "shrink-0",
		"itemTrailingKbds": "hidden lg:inline-flex items-center shrink-0",
		"itemTrailingKbdsSize": "",
		"itemWrapper": "flex-1 flex flex-col text-start min-w-0",
		"itemLabel": "truncate",
		"itemDescription": "truncate text-muted",
		"itemLabelExternalIcon": "inline-block size-3 align-top text-dimmed"
	},
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
		"active": {
			"true": {
				"item": "text-highlighted before:bg-elevated",
				"itemLeadingIcon": "text-default"
			},
			"false": {
				"item": ["text-default data-highlighted:text-highlighted data-[state=open]:text-highlighted data-highlighted:before:bg-elevated/50 data-[state=open]:before:bg-elevated/50", "transition-colors before:transition-colors"],
				"itemLeadingIcon": ["text-dimmed group-data-highlighted:text-default group-data-[state=open]:text-default", "transition-colors"]
			}
		},
		"loading": { "true": { "itemLeadingIcon": "animate-spin" } },
		"size": {
			"xs": {
				"label": "p-1 text-xs gap-1",
				"item": "p-1 text-xs gap-1",
				"empty": "p-2 text-xs",
				"itemLeadingIcon": "size-4",
				"itemLeadingAvatarSize": "3xs",
				"itemTrailingIcon": "size-4",
				"itemTrailingKbds": "gap-0.5",
				"itemTrailingKbdsSize": "sm"
			},
			"sm": {
				"label": "p-1.5 text-xs gap-1.5",
				"item": "p-1.5 text-xs gap-1.5",
				"empty": "p-2.5 text-xs",
				"itemLeadingIcon": "size-4",
				"itemLeadingAvatarSize": "3xs",
				"itemTrailingIcon": "size-4",
				"itemTrailingKbds": "gap-0.5",
				"itemTrailingKbdsSize": "sm"
			},
			"md": {
				"label": "p-1.5 text-sm gap-1.5",
				"item": "p-1.5 text-sm gap-1.5",
				"empty": "p-2.5 text-sm",
				"itemLeadingIcon": "size-5",
				"itemLeadingAvatarSize": "2xs",
				"itemTrailingIcon": "size-5",
				"itemTrailingKbds": "gap-0.5",
				"itemTrailingKbdsSize": "md"
			},
			"lg": {
				"label": "p-2 text-sm gap-2",
				"item": "p-2 text-sm gap-2",
				"empty": "p-3 text-sm",
				"itemLeadingIcon": "size-5",
				"itemLeadingAvatarSize": "2xs",
				"itemTrailingIcon": "size-5",
				"itemTrailingKbds": "gap-1",
				"itemTrailingKbdsSize": "md"
			},
			"xl": {
				"label": "p-2 text-base gap-2",
				"item": "p-2 text-base gap-2",
				"empty": "p-3 text-base",
				"itemLeadingIcon": "size-6",
				"itemLeadingAvatarSize": "xs",
				"itemTrailingIcon": "size-6",
				"itemTrailingKbds": "gap-1",
				"itemTrailingKbdsSize": "lg"
			}
		}
	},
	"compoundVariants": [
		{
			"color": "primary",
			"active": false,
			"class": {
				"item": "text-primary data-highlighted:text-primary data-highlighted:before:bg-primary/10 data-[state=open]:before:bg-primary/10",
				"itemLeadingIcon": "text-primary/75 group-data-highlighted:text-primary group-data-[state=open]:text-primary"
			}
		},
		{
			"color": "secondary",
			"active": false,
			"class": {
				"item": "text-secondary data-highlighted:text-secondary data-highlighted:before:bg-secondary/10 data-[state=open]:before:bg-secondary/10",
				"itemLeadingIcon": "text-secondary/75 group-data-highlighted:text-secondary group-data-[state=open]:text-secondary"
			}
		},
		{
			"color": "success",
			"active": false,
			"class": {
				"item": "text-success data-highlighted:text-success data-highlighted:before:bg-success/10 data-[state=open]:before:bg-success/10",
				"itemLeadingIcon": "text-success/75 group-data-highlighted:text-success group-data-[state=open]:text-success"
			}
		},
		{
			"color": "info",
			"active": false,
			"class": {
				"item": "text-info data-highlighted:text-info data-highlighted:before:bg-info/10 data-[state=open]:before:bg-info/10",
				"itemLeadingIcon": "text-info/75 group-data-highlighted:text-info group-data-[state=open]:text-info"
			}
		},
		{
			"color": "warning",
			"active": false,
			"class": {
				"item": "text-warning data-highlighted:text-warning data-highlighted:before:bg-warning/10 data-[state=open]:before:bg-warning/10",
				"itemLeadingIcon": "text-warning/75 group-data-highlighted:text-warning group-data-[state=open]:text-warning"
			}
		},
		{
			"color": "error",
			"active": false,
			"class": {
				"item": "text-error data-highlighted:text-error data-highlighted:before:bg-error/10 data-[state=open]:before:bg-error/10",
				"itemLeadingIcon": "text-error/75 group-data-highlighted:text-error group-data-[state=open]:text-error"
			}
		},
		{
			"color": "primary",
			"active": true,
			"class": {
				"item": "text-primary before:bg-primary/10",
				"itemLeadingIcon": "text-primary"
			}
		},
		{
			"color": "secondary",
			"active": true,
			"class": {
				"item": "text-secondary before:bg-secondary/10",
				"itemLeadingIcon": "text-secondary"
			}
		},
		{
			"color": "success",
			"active": true,
			"class": {
				"item": "text-success before:bg-success/10",
				"itemLeadingIcon": "text-success"
			}
		},
		{
			"color": "info",
			"active": true,
			"class": {
				"item": "text-info before:bg-info/10",
				"itemLeadingIcon": "text-info"
			}
		},
		{
			"color": "warning",
			"active": true,
			"class": {
				"item": "text-warning before:bg-warning/10",
				"itemLeadingIcon": "text-warning"
			}
		},
		{
			"color": "error",
			"active": true,
			"class": {
				"item": "text-error before:bg-error/10",
				"itemLeadingIcon": "text-error"
			}
		}
	],
	"defaultVariants": { "size": "md" }
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/DropdownMenu.vue
var _sfc_main$10 = {
	__name: "DropdownMenu",
	__ssrInlineRender: true,
	props: /* @__PURE__ */ mergeModels({
		size: {
			type: null,
			required: false
		},
		items: {
			type: null,
			required: false
		},
		checkedIcon: {
			type: null,
			required: false
		},
		loadingIcon: {
			type: null,
			required: false
		},
		externalIcon: {
			type: [Boolean, String],
			required: false,
			skipCheck: true,
			default: true
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
		filter: {
			type: [Boolean, Object],
			required: false,
			default: false
		},
		filterFields: {
			type: Array,
			required: false
		},
		ignoreFilter: {
			type: Boolean,
			required: false,
			default: false
		},
		disabled: {
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
			required: false,
			default: true
		}
	}, {
		"searchTerm": {
			type: String,
			default: ""
		},
		"searchTermModifiers": {}
	}),
	emits: /* @__PURE__ */ mergeModels(["update:open"], ["update:searchTerm"]),
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const slots = useSlots();
		const searchTerm = useModel(__props, "searchTerm", {
			type: String,
			default: ""
		});
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("dropdownMenu", props);
		const rootProps = useForwardPropsEmits(reactivePick(props, "defaultOpen", "open", "modal"), emits);
		const contentProps = toRef(() => defu(props.content, {
			side: "bottom",
			sideOffset: 8,
			collisionPadding: 8
		}));
		const arrowProps = toRef(() => defu(props.arrow, { rounded: true }));
		const getProxySlots = () => omit(slots, ["default"]);
		const ui = computed(() => tv({
			extend: tv(dropdown_menu_default),
			...appConfig.ui?.dropdownMenu || {}
		})({ size: props.size }));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(DropdownMenuRoot), mergeProps(unref(rootProps), _attrs), {
				default: withCtx(({ open }, _push, _parent, _scopeId) => {
					if (_push) {
						if (!!slots.default) _push(ssrRenderComponent(unref(DropdownMenuTrigger), {
							"as-child": "",
							class: props.class,
							disabled: __props.disabled
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) ssrRenderSlot(_ctx.$slots, "default", { open }, null, _push, _parent, _scopeId);
								else return [renderSlot(_ctx.$slots, "default", { open })];
							}),
							_: 2
						}, _parent, _scopeId));
						else _push(`<!---->`);
						_push(ssrRenderComponent(_sfc_main$11, mergeProps({
							"search-term": searchTerm.value,
							"onUpdate:searchTerm": ($event) => searchTerm.value = $event,
							class: ui.value.content({ class: [!slots.default && props.class, unref(uiProp)?.content] }),
							ui: ui.value,
							"ui-override": unref(uiProp)
						}, contentProps.value, {
							items: __props.items,
							portal: __props.portal,
							"label-key": __props.labelKey,
							"description-key": __props.descriptionKey,
							"checked-icon": __props.checkedIcon,
							"loading-icon": __props.loadingIcon,
							"external-icon": __props.externalIcon,
							size: __props.size,
							filter: __props.filter,
							"filter-fields": __props.filterFields,
							"ignore-filter": __props.ignoreFilter
						}), createSlots({
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) if (!!__props.arrow) _push(ssrRenderComponent(unref(DropdownMenuArrow), mergeProps(arrowProps.value, {
									"data-slot": "arrow",
									class: ui.value.arrow({ class: unref(uiProp)?.arrow })
								}), null, _parent, _scopeId));
								else _push(`<!---->`);
								else return [!!__props.arrow ? (openBlock(), createBlock(unref(DropdownMenuArrow), mergeProps({ key: 0 }, arrowProps.value, {
									"data-slot": "arrow",
									class: ui.value.arrow({ class: unref(uiProp)?.arrow })
								}), null, 16, ["class"])) : createCommentVNode("", true)];
							}),
							_: 2
						}, [renderList(getProxySlots(), (_, name) => {
							return {
								name,
								fn: withCtx((slotData, _push, _parent, _scopeId) => {
									if (_push) ssrRenderSlot(_ctx.$slots, name, slotData, null, _push, _parent, _scopeId);
									else return [renderSlot(_ctx.$slots, name, slotData)];
								})
							};
						})]), _parent, _scopeId));
					} else return [!!slots.default ? (openBlock(), createBlock(unref(DropdownMenuTrigger), {
						key: 0,
						"as-child": "",
						class: props.class,
						disabled: __props.disabled
					}, {
						default: withCtx(() => [renderSlot(_ctx.$slots, "default", { open })]),
						_: 2
					}, 1032, ["class", "disabled"])) : createCommentVNode("", true), createVNode(_sfc_main$11, mergeProps({
						"search-term": searchTerm.value,
						"onUpdate:searchTerm": ($event) => searchTerm.value = $event,
						class: ui.value.content({ class: [!slots.default && props.class, unref(uiProp)?.content] }),
						ui: ui.value,
						"ui-override": unref(uiProp)
					}, contentProps.value, {
						items: __props.items,
						portal: __props.portal,
						"label-key": __props.labelKey,
						"description-key": __props.descriptionKey,
						"checked-icon": __props.checkedIcon,
						"loading-icon": __props.loadingIcon,
						"external-icon": __props.externalIcon,
						size: __props.size,
						filter: __props.filter,
						"filter-fields": __props.filterFields,
						"ignore-filter": __props.ignoreFilter
					}), createSlots({
						default: withCtx(() => [!!__props.arrow ? (openBlock(), createBlock(unref(DropdownMenuArrow), mergeProps({ key: 0 }, arrowProps.value, {
							"data-slot": "arrow",
							class: ui.value.arrow({ class: unref(uiProp)?.arrow })
						}), null, 16, ["class"])) : createCommentVNode("", true)]),
						_: 2
					}, [renderList(getProxySlots(), (_, name) => {
						return {
							name,
							fn: withCtx((slotData) => [renderSlot(_ctx.$slots, name, slotData)])
						};
					})]), 1040, [
						"search-term",
						"onUpdate:searchTerm",
						"class",
						"ui",
						"ui-override",
						"items",
						"portal",
						"label-key",
						"description-key",
						"checked-icon",
						"loading-icon",
						"external-icon",
						"size",
						"filter",
						"filter-fields",
						"ignore-filter"
					])];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$19 = _sfc_main$10.setup;
_sfc_main$10.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/DropdownMenu.vue");
	return _sfc_setup$19 ? _sfc_setup$19(props, ctx) : void 0;
};
//#endregion
//#region resources/js/Composables/useKeyboardShortcutRegistry.ts
var shortcuts = ref([]);
var activePageScope = ref(null);
var activeComponentScopes = ref([]);
var idCounter = 0;
function normalizeKey(key) {
	return key.trim().toLowerCase();
}
function nextId() {
	idCounter += 1;
	return `shortcut-${idCounter}`;
}
function isDisabled(shortcut) {
	if (typeof shortcut.disabled === "function") return shortcut.disabled();
	return shortcut.disabled === true;
}
function isVisible$1(shortcut) {
	if (typeof shortcut.visible === "function") return shortcut.visible();
	return shortcut.visible !== false;
}
function ensureValidShortcut(shortcut) {
	if (!shortcut.key.trim()) throw new Error("[Shortcuts] A shortcut key is required.");
	if (!shortcut.handler) throw new Error(`[Shortcuts] Shortcut "${shortcut.key}" requires a handler.`);
	if (shortcut.scope !== "global" && !shortcut.ownerId) throw new Error(`[Shortcuts] Shortcut "${shortcut.key}" requires ownerId for scope "${shortcut.scope}".`);
}
function ensureNoConflict(nextShortcut, ignoreId) {
	const key = normalizeKey(nextShortcut.key);
	const existing = shortcuts.value.filter((shortcut) => {
		return shortcut.id !== ignoreId && normalizeKey(shortcut.key) === key;
	});
	const globalConflict = existing.find((shortcut) => shortcut.scope === "global");
	if (globalConflict) throw new Error(`[Shortcuts] "${nextShortcut.key}" is already reserved globally by "${globalConflict.label ?? globalConflict.id}".`);
	if (nextShortcut.scope === "global" && existing.length > 0) {
		const conflict = existing[0];
		throw new Error(`[Shortcuts] Cannot register global shortcut "${nextShortcut.key}" because it is already used by "${conflict.label ?? conflict.id}".`);
	}
	if (nextShortcut.scope === "page") {
		if (existing.find((shortcut) => {
			return shortcut.scope === "page" && shortcut.ownerId === nextShortcut.ownerId;
		})) throw new Error(`[Shortcuts] "${nextShortcut.key}" is already used on page "${nextShortcut.ownerId}".`);
	}
	if (nextShortcut.scope === "component") {
		if (existing.find((shortcut) => {
			return shortcut.scope === "component" && shortcut.ownerId === nextShortcut.ownerId;
		})) throw new Error(`[Shortcuts] "${nextShortcut.key}" is already used on component "${nextShortcut.ownerId}".`);
	}
}
function resolveShortcutForKey(key) {
	const normalizedKey = normalizeKey(key);
	const candidates = shortcuts.value.filter((shortcut) => normalizeKey(shortcut.key) === normalizedKey);
	for (const componentId of [...activeComponentScopes.value].reverse()) {
		const shortcut = candidates.find((item) => item.scope === "component" && item.ownerId === componentId);
		if (shortcut && !isDisabled(shortcut)) return shortcut;
	}
	if (activePageScope.value) {
		const shortcut = candidates.find((item) => {
			return item.scope === "page" && item.ownerId === activePageScope.value;
		});
		if (shortcut && !isDisabled(shortcut)) return shortcut;
	}
	const globalShortcut = candidates.find((item) => item.scope === "global");
	if (globalShortcut && !isDisabled(globalShortcut)) return globalShortcut;
	return null;
}
function useShortcutRegistry() {
	function registerShortcut(shortcut, source) {
		const normalizedShortcut = {
			...shortcut,
			key: normalizeKey(shortcut.key)
		};
		ensureValidShortcut(normalizedShortcut);
		ensureNoConflict(normalizedShortcut);
		const registered = {
			...normalizedShortcut,
			id: nextId(),
			source
		};
		shortcuts.value = [...shortcuts.value, registered];
		return () => unregisterShortcut(registered.id);
	}
	function registerShortcuts(nextShortcuts, source, options = {}) {
		if (source && options.replaceSource !== false) unregisterShortcutsBySource(source);
		const disposers = nextShortcuts.map((shortcut) => registerShortcut(shortcut, source));
		return () => disposers.forEach((dispose) => dispose());
	}
	function unregisterShortcutsBySource(source) {
		shortcuts.value = shortcuts.value.filter((shortcut) => shortcut.source !== source);
	}
	function registerNuxtUiItems(options) {
		const extracted = extractShortcuts(toValue(options.items), options.separator ?? "_");
		return registerShortcuts(Object.entries(extracted).map(([key, handler]) => ({
			key,
			scope: options.scope,
			ownerId: options.ownerId,
			handler
		})), options.source, { replaceSource: options.replaceSource });
	}
	function unregisterShortcut(id) {
		shortcuts.value = shortcuts.value.filter((shortcut) => shortcut.id !== id);
	}
	function setActivePageShortcutScope(ownerId) {
		activePageScope.value = ownerId;
		return () => {
			if (activePageScope.value === ownerId) activePageScope.value = null;
		};
	}
	function activateComponentShortcutScope(ownerId) {
		activeComponentScopes.value = [...activeComponentScopes.value.filter((id) => id !== ownerId), ownerId];
		return () => deactivateComponentShortcutScope(ownerId);
	}
	function deactivateComponentShortcutScope(ownerId) {
		activeComponentScopes.value = activeComponentScopes.value.filter((id) => id !== ownerId);
	}
	return {
		shortcuts,
		visibleShortcuts: computed(() => {
			return shortcuts.value.filter(isVisible$1).filter((shortcut) => {
				if (shortcut.scope === "global") return true;
				if (shortcut.scope === "page") return shortcut.ownerId === activePageScope.value;
				if (shortcut.scope === "component") return activeComponentScopes.value.includes(String(shortcut.ownerId));
				return false;
			}).sort((a, b) => {
				return (a.order ?? 999) - (b.order ?? 999) || String(a.group ?? "").localeCompare(String(b.group ?? "")) || String(a.label ?? a.key).localeCompare(String(b.label ?? b.key));
			});
		}),
		activePageScope,
		activeComponentScopes,
		nuxtShortcuts: computed(() => {
			return [...new Set(shortcuts.value.map((shortcut) => normalizeKey(shortcut.key)))].reduce((acc, key) => {
				acc[key] = {
					usingInput: false,
					handler: (event) => {
						const shortcut = resolveShortcutForKey(key);
						if (!shortcut) return;
						shortcut.handler(event);
					}
				};
				return acc;
			}, {});
		}),
		registerShortcut,
		registerShortcuts,
		registerNuxtUiItems,
		unregisterShortcut,
		unregisterShortcutsBySource,
		setActivePageShortcutScope,
		activateComponentShortcutScope,
		deactivateComponentShortcutScope
	};
}
function usePageShortcutScope(ownerId) {
	const { setActivePageShortcutScope } = useShortcutRegistry();
	const dispose = setActivePageShortcutScope(ownerId);
	onBeforeUnmount(dispose);
	return dispose;
}
//#endregion
//#region resources/js/Components/Navigation/SidebarItem.vue
var _sfc_main$9 = {
	__name: "SidebarItem",
	__ssrInlineRender: true,
	props: {
		item: {
			type: Object,
			required: true
		},
		depth: {
			type: Number,
			default: 0
		}
	},
	setup(__props) {
		const props = __props;
		const page = usePage();
		const count = computed(() => props.item.items?.length ?? 0);
		const hasChildren = computed(() => Array.isArray(props.item.items) && props.item.items.length > 0);
		const normalizeUrl = (url) => {
			if (!url) return null;
			return url !== "/" ? url.replace(/\/$/, "") : "/";
		};
		const isActiveUrl = (url) => {
			const normalized = normalizeUrl(url);
			const current = normalizeUrl(page.url);
			if (!normalized || !current) return false;
			return normalized === "/" ? current === "/" : current === normalized || current.startsWith(`${normalized}/`);
		};
		const hasActiveChild = (item) => {
			return (item.items ?? []).some((child) => {
				return isActiveUrl(child.url) || hasActiveChild(child);
			});
		};
		const isActive = computed(() => isActiveUrl(props.item.url));
		const childIsActive = computed(() => hasActiveChild(props.item));
		const isOpen = ref(Boolean(props.item.open ?? childIsActive.value));
		watch(childIsActive, (active) => {
			if (active) isOpen.value = true;
		});
		const paddingLeft = computed(() => `${12 + props.depth * 18}px`);
		const height = computed(() => `${(props.depth + count.value) * 36 + 16}px`);
		return (_ctx, _push, _parent, _attrs) => {
			const _component_SidebarItem = resolveComponent("SidebarItem", true);
			_push(`<div${ssrRenderAttrs(_attrs)}>`);
			if (hasChildren.value) {
				_push(`<button type="button" class="${ssrRenderClass([{ "bg-[#1d2a3b] text-white": isOpen.value || childIsActive.value }, "flex min-h-9 w-full items-center gap-3 rounded-md pr-3 text-left text-sm text-white/80 hover:bg-white/5 hover:text-white transition-all duration-300 cursor-pointer"])}" style="${ssrRenderStyle({ paddingLeft: paddingLeft.value })}">`);
				if (__props.item.icon) _push(`<span class="${ssrRenderClass([["pi", __props.item.icon], "text-sm text-current"])}"></span>`);
				else _push(`<!---->`);
				_push(`<span class="min-w-0 flex-1 truncate">${ssrInterpolate(__props.item.label)}</span><span style="${ssrRenderStyle({ "font-size": ".8rem" })}" class="${ssrRenderClass([isOpen.value ? "rotate-180" : "pi-chevron-down", "pi text-current pi-chevron-down transition duration-300"])}"></span></button>`);
			} else _push(ssrRenderComponent(unref(Link), {
				href: __props.item.url,
				class: ["flex min-h-9 items-center gap-3 rounded-md pr-3 text-sm text-white/80 hover:bg-white/5 hover:text-white duration-200", { "bg-moonlight text-white": isActive.value }],
				style: { paddingLeft: paddingLeft.value }
			}, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						if (__props.item.icon) _push(`<span class="${ssrRenderClass([["pi", __props.item.icon], "text-sm text-current"])}"${_scopeId}></span>`);
						else _push(`<!---->`);
						_push(`<span class="min-w-0 truncate"${_scopeId}>${ssrInterpolate(__props.item.label)}</span>`);
					} else return [__props.item.icon ? (openBlock(), createBlock("span", {
						key: 0,
						class: [["pi", __props.item.icon], "text-sm text-current"]
					}, null, 2)) : createCommentVNode("", true), createVNode("span", { class: "min-w-0 truncate" }, toDisplayString(__props.item.label), 1)];
				}),
				_: 1
			}, _parent));
			_push(`<div class="${ssrRenderClass([hasChildren.value && isOpen.value ? "py-2" : "h-0", "ml-4 border-l border-slate-700/70 transition-height duration-300 overflow-hidden"])}" style="${ssrRenderStyle(hasChildren.value && isOpen.value ? "height: " + height.value : "")}">`);
			if (hasChildren.value) {
				_push(`<!--[-->`);
				ssrRenderList(__props.item.items, (child) => {
					_push(ssrRenderComponent(_component_SidebarItem, {
						key: child.key ?? child.url ?? child.label,
						item: child,
						depth: __props.depth + 1
					}, null, _parent));
				});
				_push(`<!--]-->`);
			} else _push(`<!---->`);
			_push(`</div></div>`);
		};
	}
};
var _sfc_setup$18 = _sfc_main$9.setup;
_sfc_main$9.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Navigation/SidebarItem.vue");
	return _sfc_setup$18 ? _sfc_setup$18(props, ctx) : void 0;
};
//#endregion
//#region resources/js/Components/Forms/CsrfForm.vue
var _sfc_main$8 = {
	__name: "CsrfForm",
	__ssrInlineRender: true,
	props: {
		action: {
			type: String,
			required: true
		},
		method: {
			type: String,
			default: "POST"
		}
	},
	setup(__props) {
		const props = __props;
		const page = usePage();
		const normalizedMethod = computed(() => props.method.toLowerCase());
		const formMethod = computed(() => normalizedMethod.value === "get" ? "get" : "post");
		const csrfToken = computed(() => page.props.csrf_token);
		return (_ctx, _push, _parent, _attrs) => {
			_push(`<form${ssrRenderAttrs(mergeProps({
				action: __props.action,
				method: formMethod.value
			}, _attrs))}>`);
			if (!formMethod.value === "get") _push(`<input type="hidden" name="_token"${ssrRenderAttr("value", csrfToken.value)}>`);
			else _push(`<!---->`);
			if (!["get", "post"].includes(normalizedMethod.value)) _push(`<input type="hidden" name="_method"${ssrRenderAttr("value", normalizedMethod.value.toUpperCase())}>`);
			else _push(`<!---->`);
			ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
			_push(`</form>`);
		};
	}
};
var _sfc_setup$17 = _sfc_main$8.setup;
_sfc_main$8.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Forms/CsrfForm.vue");
	return _sfc_setup$17 ? _sfc_setup$17(props, ctx) : void 0;
};
//#endregion
//#region resources/js/Components/Navigation/Sidebar.vue
var _sfc_main$7 = {
	__name: "Sidebar",
	__ssrInlineRender: true,
	setup(__props) {
		const page = usePage();
		const user = computed(() => page.props.auth?.user);
		const userFirstChar = computed(() => page.props.auth?.user.username.charAt(0).toUpperCase());
		const trans = page.props.trans;
		const sections = computed(() => page.props.dashboard?.pages ?? []);
		return (_ctx, _push, _parent, _attrs) => {
			_push(`<aside${ssrRenderAttrs(mergeProps({ class: "min-h-screen w-56 shrink-0 bg-night py-4 text-white flex flex-col justify-between fixed border-e border-r-white/10" }, _attrs))}><div class="nav-start"><div class="px-3 pb-3 border-b border-b-white/10">`);
			_push(ssrRenderComponent(unref(Link), {
				href: "/",
				class: "flex h-12 items-center gap-3 rounded-md px-2 hover:bg-white/5"
			}, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(`<img${ssrRenderAttr("src", "/assets/img/fulgurite-logo.svg")} alt="Fulgurite" class="size-8"${_scopeId}><span class="font-semibold"${_scopeId}>Fulgurite</span>`);
					else return [createVNode("img", {
						src: "/assets/img/fulgurite-logo.svg",
						alt: "Fulgurite",
						class: "size-8"
					}), createVNode("span", { class: "font-semibold" }, "Fulgurite")];
				}),
				_: 1
			}, _parent));
			_push(`</div><nav class="px-3 py-6"><!--[-->`);
			ssrRenderList(sections.value, (section) => {
				_push(`<section class="space-y-2"><p class="px-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">${ssrInterpolate(section.label)}</p><div class="space-y-1"><!--[-->`);
				ssrRenderList(section.items, (item) => {
					_push(ssrRenderComponent(_sfc_main$9, {
						key: item.key ?? item.url ?? item.label,
						item
					}, null, _parent));
				});
				_push(`<!--]--></div></section>`);
			});
			_push(`<!--]--></nav></div><div class="nav-end flex flex-col px-3 py-5 border-t border-t-white/30 gap-2"><div class="user flex items-center justify-start gap-4 w-full"><div class="avatar rounded-full bg-primary text-midnight font-semibold size-8 flex items-center justify-center"><p class="text-sm">${ssrInterpolate(userFirstChar.value)}</p></div><div class="user-auth flex flex-col justify-center items-start text-white/80 gap-0.5"><p class="username text-xs">${ssrInterpolate(user.value.username)}</p><p class="displayName text-sm text-white">${ssrInterpolate(user.value.username.charAt(0).toUpperCase() + user.value.username.slice(1))}</p></div></div>`);
			_push(ssrRenderComponent(unref(Link), {
				href: "/profile",
				class: "btn btn-tertiary text-xs font-normal text-center py-2 px-3"
			}, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(`${ssrInterpolate(unref(trans).layout.profile_btn)}`);
					else return [createTextVNode(toDisplayString(unref(trans).layout.profile_btn), 1)];
				}),
				_: 1
			}, _parent));
			_push(ssrRenderComponent(_sfc_main$8, {
				action: "/logout",
				method: "post"
			}, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(`<button type="submit" class="link link-danger text-xs font-normal text-center w-full"${_scopeId}>${ssrInterpolate(unref(trans).layout.logout)}</button>`);
					else return [createVNode("button", {
						type: "submit",
						class: "link link-danger text-xs font-normal text-center w-full"
					}, toDisplayString(unref(trans).layout.logout), 1)];
				}),
				_: 1
			}, _parent));
			_push(`</div></aside>`);
		};
	}
};
var _sfc_setup$16 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Navigation/Sidebar.vue");
	return _sfc_setup$16 ? _sfc_setup$16(props, ctx) : void 0;
};
//#endregion
//#region resources/js/Components/Navigation/Topbar.vue
var _sfc_main$6 = {
	__name: "Topbar",
	__ssrInlineRender: true,
	props: {
		title: {
			type: String,
			required: true
		},
		description: {
			type: String,
			default: ""
		}
	},
	setup(__props) {
		ref("");
		return (_ctx, _push, _parent, _attrs) => {
			_push(`<nav${ssrRenderAttrs(mergeProps({ class: "topbar w-full sticky flex items-center justify-between px-6 py-4 bg-night border-b border-b-white/10 top-0 min-h-19.25 max-h-19.25" }, _attrs))}><div class="w-1/2"><hgroup class="page"><h1 class="${ssrRenderClass([__props.description !== "" ? "text-lg" : "text-xl", "font-bold text-white"])}">${ssrInterpolate(__props.title)}</h1>`);
			if (__props.description !== "") _push(`<p class="text-sm font-normal text-white/70 min-h-5 max-h-5">${ssrInterpolate(__props.description)}</p>`);
			else _push(`<!---->`);
			_push(`</hgroup></div><div class="flex items-center justify-end w-1/2 gap-10">`);
			_push(ssrRenderComponent(unref(Link), { href: "notifications" }, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(`<!---->`);
					else return [createCommentVNode("", true)];
				}),
				_: 1
			}, _parent));
			_push(`</div></nav>`);
		};
	}
};
var _sfc_setup$15 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Navigation/Topbar.vue");
	return _sfc_setup$15 ? _sfc_setup$15(props, ctx) : void 0;
};
//#endregion
//#region resources/js/Components/Modals/FgKeyboardShortcutsModal.vue?vue&type=script&setup=true&lang.ts
var FgKeyboardShortcutsModal_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "FgKeyboardShortcutsModal",
	__ssrInlineRender: true,
	props: {
		"open": {
			type: Boolean,
			default: false
		},
		"openModifiers": {}
	},
	emits: ["update:open"],
	setup(__props) {
		const open = useModel(__props, "open");
		const { visibleShortcuts } = useShortcutRegistry();
		const sectionConfig = {
			global: {
				title: "Global",
				description: "Shortcuts available in all app.",
				icon: "i-lucide-globe-2"
			},
			page: {
				title: "Page",
				description: "Shortcuts available in this page.",
				icon: "i-lucide-file-text"
			},
			component: {
				title: "Component",
				description: "Shortcuts available in component in this page.",
				icon: "i-lucide-component"
			}
		};
		const sections = computed(() => {
			return [
				"global",
				"page",
				"component"
			].map((scope) => {
				const shortcuts = visibleShortcuts.value.filter((shortcut) => shortcut.scope === scope);
				return {
					scope,
					...sectionConfig[scope],
					shortcuts
				};
			});
		});
		const totalShortcuts = computed(() => visibleShortcuts.value.length);
		function close() {
			open.value = false;
		}
		function formatKey(shortcut) {
			if (shortcut.displayKey) return shortcut.displayKey;
			return shortcut.key.split("-").map((sequence) => {
				return sequence.split("_").map(formatKeyToken).join(" + ");
			}).join(" puis ");
		}
		function formatKeyToken(token) {
			const normalized = token.toLowerCase();
			return {
				meta: "⌘",
				command: "⌘",
				cmd: "⌘",
				ctrl: "Ctrl",
				control: "Ctrl",
				alt: "Alt",
				option: "Alt",
				shift: "Shift",
				enter: "Entrée",
				escape: "Échap",
				esc: "Échap",
				backspace: "Retour",
				delete: "Suppr",
				space: "Espace",
				tab: "Tab",
				up: "↑",
				down: "↓",
				left: "←",
				right: "→",
				arrowup: "↑",
				arrowdown: "↓",
				arrowleft: "←",
				arrowright: "→"
			}[normalized] ?? normalized.toUpperCase();
		}
		function groupedShortcuts(shortcuts) {
			return shortcuts.reduce((groups, shortcut) => {
				const group = shortcut.group ?? "Raccourcis";
				groups[group] ??= [];
				groups[group].push(shortcut);
				return groups;
			}, {});
		}
		return (_ctx, _push, _parent, _attrs) => {
			const _component_UModal = _sfc_main$12;
			const _component_UIcon = _sfc_main$20;
			const _component_UButton = _sfc_main$22;
			const _component_UBadge = _sfc_main$38;
			_push(ssrRenderComponent(_component_UModal, mergeProps({
				open: open.value,
				"onUpdate:open": ($event) => open.value = $event,
				ui: { content: "sm:max-w-3xl" }
			}, _attrs), {
				header: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex w-full items-start justify-between gap-4"${_scopeId}><div class="flex items-start gap-3"${_scopeId}><div class="flex size-10 shrink-0 items-center justify-center rounded-md border border-primary/25 bg-primary/10 text-primary"${_scopeId}>`);
						_push(ssrRenderComponent(_component_UIcon, {
							name: "i-lucide-keyboard",
							class: "size-5"
						}, null, _parent, _scopeId));
						_push(`</div><div${_scopeId}><h2 class="text-lg font-semibold text-white"${_scopeId}> Raccourcis clavier </h2><p class="mt-1 text-sm text-muted"${_scopeId}>${ssrInterpolate(totalShortcuts.value)} raccourci(s) disponible(s) dans le contexte actuel. </p></div></div>`);
						_push(ssrRenderComponent(_component_UButton, {
							icon: "i-lucide-x",
							color: "neutral",
							variant: "ghost",
							size: "sm",
							"aria-label": "Fermer",
							onClick: close
						}, null, _parent, _scopeId));
						_push(`</div>`);
					} else return [createVNode("div", { class: "flex w-full items-start justify-between gap-4" }, [createVNode("div", { class: "flex items-start gap-3" }, [createVNode("div", { class: "flex size-10 shrink-0 items-center justify-center rounded-md border border-primary/25 bg-primary/10 text-primary" }, [createVNode(_component_UIcon, {
						name: "i-lucide-keyboard",
						class: "size-5"
					})]), createVNode("div", null, [createVNode("h2", { class: "text-lg font-semibold text-white" }, " Raccourcis clavier "), createVNode("p", { class: "mt-1 text-sm text-muted" }, toDisplayString(totalShortcuts.value) + " raccourci(s) disponible(s) dans le contexte actuel. ", 1)])]), createVNode(_component_UButton, {
						icon: "i-lucide-x",
						color: "neutral",
						variant: "ghost",
						size: "sm",
						"aria-label": "Fermer",
						onClick: close
					})])];
				}),
				body: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="max-h-[65vh] overflow-y-auto pr-1"${_scopeId}>`);
						if (totalShortcuts.value === 0) {
							_push(`<div class="flex flex-col items-center justify-center rounded-md border border-dashed border-default px-6 py-12 text-center"${_scopeId}>`);
							_push(ssrRenderComponent(_component_UIcon, {
								name: "i-lucide-keyboard-off",
								class: "mb-3 size-8 text-muted"
							}, null, _parent, _scopeId));
							_push(`<p class="font-medium text-white"${_scopeId}> Aucun raccourci disponible </p><p class="mt-1 text-sm text-muted"${_scopeId}> Les raccourcis apparaîtront ici dès qu’une page ou un composant en enregistrera. </p></div>`);
						} else {
							_push(`<div class="grid gap-4"${_scopeId}><!--[-->`);
							ssrRenderList(sections.value, (section) => {
								_push(`<section class="rounded-md border border-default bg-white/[0.03]"${_scopeId}><header class="flex items-center justify-between gap-3 border-b border-default px-4 py-3"${_scopeId}><div class="flex items-center gap-3"${_scopeId}>`);
								_push(ssrRenderComponent(_component_UIcon, {
									name: section.icon,
									class: "size-4 text-primary"
								}, null, _parent, _scopeId));
								_push(`<div${_scopeId}><h3 class="text-sm font-semibold text-white"${_scopeId}>${ssrInterpolate(section.title)}</h3><p class="text-xs text-muted"${_scopeId}>${ssrInterpolate(section.description)}</p></div></div>`);
								_push(ssrRenderComponent(_component_UBadge, {
									label: String(section.shortcuts.length),
									color: "neutral",
									variant: "soft"
								}, null, _parent, _scopeId));
								_push(`</header>`);
								if (section.shortcuts.length === 0) _push(`<div class="px-4 py-5 text-sm text-muted"${_scopeId}> Aucun raccourci actif pour cette section. </div>`);
								else {
									_push(`<div class="divide-y divide-default"${_scopeId}><!--[-->`);
									ssrRenderList(groupedShortcuts(section.shortcuts), (groupShortcuts, group) => {
										_push(`<div class="px-4 py-3"${_scopeId}><p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted"${_scopeId}>${ssrInterpolate(group)}</p><ul class="grid gap-2"${_scopeId}><!--[-->`);
										ssrRenderList(groupShortcuts, (shortcut) => {
											_push(`<li class="${ssrRenderClass([{ "opacity-50": shortcut.disabled === true }, "flex items-center justify-between gap-4 rounded-md px-2 py-2 transition-colors hover:bg-white/[0.04]"])}"${_scopeId}><div class="min-w-0"${_scopeId}><p class="truncate text-sm font-medium text-white"${_scopeId}>${ssrInterpolate(shortcut.label ?? shortcut.key)}</p>`);
											if (shortcut.description) _push(`<p class="mt-0.5 truncate text-xs text-muted"${_scopeId}>${ssrInterpolate(shortcut.description)}</p>`);
											else _push(`<!---->`);
											_push(`</div><div class="flex shrink-0 items-center gap-1"${_scopeId}><!--[-->`);
											ssrRenderList(formatKey(shortcut).split(" + "), (part) => {
												_push(`<kbd class="min-w-7 rounded-md border border-white/15 bg-night px-2 py-1 text-center text-xs font-semibold text-sandstone shadow-sm"${_scopeId}>${ssrInterpolate(part)}</kbd>`);
											});
											_push(`<!--]--></div></li>`);
										});
										_push(`<!--]--></ul></div>`);
									});
									_push(`<!--]--></div>`);
								}
								_push(`</section>`);
							});
							_push(`<!--]--></div>`);
						}
						_push(`</div>`);
					} else return [createVNode("div", { class: "max-h-[65vh] overflow-y-auto pr-1" }, [totalShortcuts.value === 0 ? (openBlock(), createBlock("div", {
						key: 0,
						class: "flex flex-col items-center justify-center rounded-md border border-dashed border-default px-6 py-12 text-center"
					}, [
						createVNode(_component_UIcon, {
							name: "i-lucide-keyboard-off",
							class: "mb-3 size-8 text-muted"
						}),
						createVNode("p", { class: "font-medium text-white" }, " Aucun raccourci disponible "),
						createVNode("p", { class: "mt-1 text-sm text-muted" }, " Les raccourcis apparaîtront ici dès qu’une page ou un composant en enregistrera. ")
					])) : (openBlock(), createBlock("div", {
						key: 1,
						class: "grid gap-4"
					}, [(openBlock(true), createBlock(Fragment, null, renderList(sections.value, (section) => {
						return openBlock(), createBlock("section", {
							key: section.scope,
							class: "rounded-md border border-default bg-white/[0.03]"
						}, [createVNode("header", { class: "flex items-center justify-between gap-3 border-b border-default px-4 py-3" }, [createVNode("div", { class: "flex items-center gap-3" }, [createVNode(_component_UIcon, {
							name: section.icon,
							class: "size-4 text-primary"
						}, null, 8, ["name"]), createVNode("div", null, [createVNode("h3", { class: "text-sm font-semibold text-white" }, toDisplayString(section.title), 1), createVNode("p", { class: "text-xs text-muted" }, toDisplayString(section.description), 1)])]), createVNode(_component_UBadge, {
							label: String(section.shortcuts.length),
							color: "neutral",
							variant: "soft"
						}, null, 8, ["label"])]), section.shortcuts.length === 0 ? (openBlock(), createBlock("div", {
							key: 0,
							class: "px-4 py-5 text-sm text-muted"
						}, " Aucun raccourci actif pour cette section. ")) : (openBlock(), createBlock("div", {
							key: 1,
							class: "divide-y divide-default"
						}, [(openBlock(true), createBlock(Fragment, null, renderList(groupedShortcuts(section.shortcuts), (groupShortcuts, group) => {
							return openBlock(), createBlock("div", {
								key: group,
								class: "px-4 py-3"
							}, [createVNode("p", { class: "mb-2 text-xs font-medium uppercase tracking-wide text-muted" }, toDisplayString(group), 1), createVNode("ul", { class: "grid gap-2" }, [(openBlock(true), createBlock(Fragment, null, renderList(groupShortcuts, (shortcut) => {
								return openBlock(), createBlock("li", {
									key: shortcut.id,
									class: ["flex items-center justify-between gap-4 rounded-md px-2 py-2 transition-colors hover:bg-white/[0.04]", { "opacity-50": shortcut.disabled === true }]
								}, [createVNode("div", { class: "min-w-0" }, [createVNode("p", { class: "truncate text-sm font-medium text-white" }, toDisplayString(shortcut.label ?? shortcut.key), 1), shortcut.description ? (openBlock(), createBlock("p", {
									key: 0,
									class: "mt-0.5 truncate text-xs text-muted"
								}, toDisplayString(shortcut.description), 1)) : createCommentVNode("", true)]), createVNode("div", { class: "flex shrink-0 items-center gap-1" }, [(openBlock(true), createBlock(Fragment, null, renderList(formatKey(shortcut).split(" + "), (part) => {
									return openBlock(), createBlock("kbd", {
										key: `${shortcut.id}-${part}`,
										class: "min-w-7 rounded-md border border-white/15 bg-night px-2 py-1 text-center text-xs font-semibold text-sandstone shadow-sm"
									}, toDisplayString(part), 1);
								}), 128))])], 2);
							}), 128))])]);
						}), 128))]))]);
					}), 128))]))])];
				}),
				footer: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex w-full items-center justify-between gap-3"${_scopeId}><p class="text-xs text-muted"${_scopeId}> Les raccourcis affichés dépendent de la page et du composant actuellement actifs. </p>`);
						_push(ssrRenderComponent(_component_UButton, {
							label: "Fermer",
							color: "neutral",
							variant: "ghost",
							onClick: close
						}, null, _parent, _scopeId));
						_push(`</div>`);
					} else return [createVNode("div", { class: "flex w-full items-center justify-between gap-3" }, [createVNode("p", { class: "text-xs text-muted" }, " Les raccourcis affichés dépendent de la page et du composant actuellement actifs. "), createVNode(_component_UButton, {
						label: "Fermer",
						color: "neutral",
						variant: "ghost",
						onClick: close
					})])];
				}),
				_: 1
			}, _parent));
		};
	}
});
//#endregion
//#region resources/js/Components/Modals/FgKeyboardShortcutsModal.vue
var _sfc_setup$14 = FgKeyboardShortcutsModal_vue_vue_type_script_setup_true_lang_default.setup;
FgKeyboardShortcutsModal_vue_vue_type_script_setup_true_lang_default.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Modals/FgKeyboardShortcutsModal.vue");
	return _sfc_setup$14 ? _sfc_setup$14(props, ctx) : void 0;
};
var FgKeyboardShortcutsModal_default = FgKeyboardShortcutsModal_vue_vue_type_script_setup_true_lang_default;
//#endregion
//#region resources/js/Layouts/DashboardLayout.vue?vue&type=script&setup=true&lang.ts
var DashboardLayout_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "DashboardLayout",
	__ssrInlineRender: true,
	setup(__props) {
		const { nuxtShortcuts, registerShortcuts } = useShortcutRegistry();
		defineShortcuts(nuxtShortcuts);
		registerShortcuts([
			{
				key: "meta_k",
				label: "Recherche globale",
				scope: "global",
				handler: () => {}
			},
			{
				key: "g-u",
				label: "Aller aux utilisateurs",
				scope: "global",
				handler: () => {
					router.get("/users");
				}
			},
			{
				key: "?",
				label: "Afficher les raccourcis clavier",
				description: "Ouvre l’aide contextuelle des raccourcis disponibles.",
				scope: "global",
				group: "Aide",
				order: 100,
				handler: () => {
					keyboardShortcutsOpen.value = true;
				}
			}
		], "dashboard-global");
		const page = usePage();
		const toast = useToast();
		const flash = computed(() => page.props.flash ?? {});
		const flashToastingConfig = {
			success: {
				color: "success",
				icon: "i-lucide-circle-check"
			},
			error: {
				color: "error",
				icon: "i-lucide-circle-x"
			},
			warning: {
				color: "warning",
				icon: "i-lucide-triangle-alert"
			},
			info: {
				color: "info",
				icon: "i-lucide-info"
			}
		};
		watch(flash, (value) => {
			Object.entries(flashToastingConfig).forEach(([type, config]) => {
				const message = value?.[type];
				if (!message) return;
				const payload = typeof message === "object" ? message : { title: String(message) };
				toast.add({
					title: payload.title,
					description: payload.description,
					color: config.color,
					icon: config.icon
				});
			});
		}, { deep: true });
		const uiLocales = {
			fr,
			"fr_FR": fr,
			en,
			"en_GB": en_gb,
			"en_US": en
		};
		const uiLocale = computed(() => {
			return uiLocales[String(page.props.locale)] ?? fr;
		});
		const keyboardShortcutsOpen = ref(false);
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(_sfc_main$31, mergeProps({ locale: uiLocale.value }, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex h-screen bg-[#0b1016] font-sans text-zinc-100 antialiased overflow-hidden"${_scopeId}>`);
						_push(ssrRenderComponent(FgKeyboardShortcutsModal_default, {
							open: keyboardShortcutsOpen.value,
							"onUpdate:open": ($event) => keyboardShortcutsOpen.value = $event
						}, null, _parent, _scopeId));
						_push(ssrRenderComponent(_sfc_main$7, null, null, _parent, _scopeId));
						_push(`<main class="w-full ml-56 relative flex flex-1 flex-col overflow-hidden min-w-0"${_scopeId}>`);
						_push(ssrRenderComponent(_sfc_main$6, {
							title: unref(page).props.page?.title ?? "Dashboard",
							description: unref(page).props.page?.description ?? ""
						}, null, _parent, _scopeId));
						_push(`<div class="page min-h-0 flex-1 overflow-x-hidden p-5 max-h-[calc(100vh-77px)]"${_scopeId}>`);
						ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
						_push(`</div></main></div>`);
					} else return [createVNode("div", { class: "flex h-screen bg-[#0b1016] font-sans text-zinc-100 antialiased overflow-hidden" }, [
						createVNode(FgKeyboardShortcutsModal_default, {
							open: keyboardShortcutsOpen.value,
							"onUpdate:open": ($event) => keyboardShortcutsOpen.value = $event
						}, null, 8, ["open", "onUpdate:open"]),
						createVNode(_sfc_main$7),
						createVNode("main", { class: "w-full ml-56 relative flex flex-1 flex-col overflow-hidden min-w-0" }, [createVNode(_sfc_main$6, {
							title: unref(page).props.page?.title ?? "Dashboard",
							description: unref(page).props.page?.description ?? ""
						}, null, 8, ["title", "description"]), createVNode("div", { class: "page min-h-0 flex-1 overflow-x-hidden p-5 max-h-[calc(100vh-77px)]" }, [renderSlot(_ctx.$slots, "default")])])
					])];
				}),
				_: 3
			}, _parent));
		};
	}
});
//#endregion
//#region resources/js/Layouts/DashboardLayout.vue
var _sfc_setup$13 = DashboardLayout_vue_vue_type_script_setup_true_lang_default.setup;
DashboardLayout_vue_vue_type_script_setup_true_lang_default.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Layouts/DashboardLayout.vue");
	return _sfc_setup$13 ? _sfc_setup$13(props, ctx) : void 0;
};
var DashboardLayout_default = DashboardLayout_vue_vue_type_script_setup_true_lang_default;
//#endregion
//#region virtual:nuxt-ui-templates/ui/pagination.ts
var pagination_default = { "slots": {
	"root": "",
	"list": "flex items-center gap-1",
	"ellipsis": "pointer-events-none",
	"label": "min-w-5 text-center",
	"first": "",
	"prev": "",
	"item": "",
	"next": "",
	"last": ""
} };
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Pagination.vue
var _sfc_main$5 = {
	__name: "Pagination",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false
		},
		firstIcon: {
			type: null,
			required: false
		},
		prevIcon: {
			type: null,
			required: false
		},
		nextIcon: {
			type: null,
			required: false
		},
		lastIcon: {
			type: null,
			required: false
		},
		ellipsisIcon: {
			type: null,
			required: false
		},
		color: {
			type: null,
			required: false,
			default: "neutral"
		},
		variant: {
			type: null,
			required: false,
			default: "outline"
		},
		activeColor: {
			type: null,
			required: false,
			default: "primary"
		},
		activeVariant: {
			type: null,
			required: false,
			default: "solid"
		},
		showControls: {
			type: Boolean,
			required: false,
			default: true
		},
		size: {
			type: null,
			required: false
		},
		to: {
			type: Function,
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
		defaultPage: {
			type: Number,
			required: false
		},
		disabled: {
			type: Boolean,
			required: false
		},
		itemsPerPage: {
			type: Number,
			required: false,
			default: 10
		},
		page: {
			type: Number,
			required: false
		},
		showEdges: {
			type: Boolean,
			required: false,
			default: false
		},
		siblingCount: {
			type: Number,
			required: false,
			default: 2
		},
		total: {
			type: Number,
			required: false,
			default: 0
		}
	},
	emits: ["update:page"],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const slots = useSlots();
		const { dir } = useLocale();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("pagination", props);
		const rootProps = useForwardPropsEmits(reactivePick(props, "as", "defaultPage", "disabled", "itemsPerPage", "page", "showEdges", "siblingCount", "total"), emits);
		const firstIcon = computed(() => props.firstIcon || (dir.value === "rtl" ? appConfig.ui.icons.chevronDoubleRight : appConfig.ui.icons.chevronDoubleLeft));
		const prevIcon = computed(() => props.prevIcon || (dir.value === "rtl" ? appConfig.ui.icons.chevronRight : appConfig.ui.icons.chevronLeft));
		const nextIcon = computed(() => props.nextIcon || (dir.value === "rtl" ? appConfig.ui.icons.chevronLeft : appConfig.ui.icons.chevronRight));
		const lastIcon = computed(() => props.lastIcon || (dir.value === "rtl" ? appConfig.ui.icons.chevronDoubleLeft : appConfig.ui.icons.chevronDoubleRight));
		const ui = computed(() => tv({
			extend: tv(pagination_default),
			...appConfig.ui?.pagination || {}
		})());
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(PaginationRoot), mergeProps(unref(rootProps), {
				"data-slot": "root",
				class: ui.value.root({ class: [unref(uiProp)?.root, props.class] })
			}, _attrs), {
				default: withCtx(({ page, pageCount }, _push, _parent, _scopeId) => {
					if (_push) _push(ssrRenderComponent(unref(PaginationList), {
						"data-slot": "list",
						class: ui.value.list({ class: unref(uiProp)?.list })
					}, {
						default: withCtx(({ items }, _push, _parent, _scopeId) => {
							if (_push) {
								if (__props.showControls || !!slots.first) _push(ssrRenderComponent(unref(PaginationFirst), {
									"as-child": "",
									"data-slot": "first",
									class: ui.value.first({ class: unref(uiProp)?.first })
								}, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) ssrRenderSlot(_ctx.$slots, "first", {}, () => {
											_push(ssrRenderComponent(_sfc_main$22, {
												color: __props.color,
												variant: __props.variant,
												size: __props.size,
												icon: firstIcon.value,
												to: __props.to?.(1)
											}, null, _parent, _scopeId));
										}, _push, _parent, _scopeId);
										else return [renderSlot(_ctx.$slots, "first", {}, () => [createVNode(_sfc_main$22, {
											color: __props.color,
											variant: __props.variant,
											size: __props.size,
											icon: firstIcon.value,
											to: __props.to?.(1)
										}, null, 8, [
											"color",
											"variant",
											"size",
											"icon",
											"to"
										])])];
									}),
									_: 2
								}, _parent, _scopeId));
								else _push(`<!---->`);
								if (__props.showControls || !!slots.prev) _push(ssrRenderComponent(unref(PaginationPrev), {
									"as-child": "",
									"data-slot": "prev",
									class: ui.value.prev({ class: unref(uiProp)?.prev })
								}, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) ssrRenderSlot(_ctx.$slots, "prev", {}, () => {
											_push(ssrRenderComponent(_sfc_main$22, {
												color: __props.color,
												variant: __props.variant,
												size: __props.size,
												icon: prevIcon.value,
												to: page > 1 ? __props.to?.(page - 1) : void 0
											}, null, _parent, _scopeId));
										}, _push, _parent, _scopeId);
										else return [renderSlot(_ctx.$slots, "prev", {}, () => [createVNode(_sfc_main$22, {
											color: __props.color,
											variant: __props.variant,
											size: __props.size,
											icon: prevIcon.value,
											to: page > 1 ? __props.to?.(page - 1) : void 0
										}, null, 8, [
											"color",
											"variant",
											"size",
											"icon",
											"to"
										])])];
									}),
									_: 2
								}, _parent, _scopeId));
								else _push(`<!---->`);
								_push(`<!--[-->`);
								ssrRenderList(items, (item, index) => {
									_push(`<!--[-->`);
									if (item.type === "page") _push(ssrRenderComponent(unref(PaginationListItem), {
										"as-child": "",
										value: item.value,
										"data-slot": "item",
										class: ui.value.item({ class: unref(uiProp)?.item })
									}, {
										default: withCtx((_, _push, _parent, _scopeId) => {
											if (_push) ssrRenderSlot(_ctx.$slots, "item", mergeProps({ ref_for: true }, {
												item,
												index,
												page,
												pageCount
											}), () => {
												_push(ssrRenderComponent(_sfc_main$22, {
													color: page === item.value ? __props.activeColor : __props.color,
													variant: page === item.value ? __props.activeVariant : __props.variant,
													size: __props.size,
													label: String(item.value),
													ui: { label: ui.value.label() },
													to: __props.to?.(item.value),
													square: ""
												}, null, _parent, _scopeId));
											}, _push, _parent, _scopeId);
											else return [renderSlot(_ctx.$slots, "item", mergeProps({ ref_for: true }, {
												item,
												index,
												page,
												pageCount
											}), () => [createVNode(_sfc_main$22, {
												color: page === item.value ? __props.activeColor : __props.color,
												variant: page === item.value ? __props.activeVariant : __props.variant,
												size: __props.size,
												label: String(item.value),
												ui: { label: ui.value.label() },
												to: __props.to?.(item.value),
												square: ""
											}, null, 8, [
												"color",
												"variant",
												"size",
												"label",
												"ui",
												"to"
											])])];
										}),
										_: 2
									}, _parent, _scopeId));
									else _push(ssrRenderComponent(unref(PaginationEllipsis), {
										"as-child": "",
										"data-slot": "ellipsis",
										class: ui.value.ellipsis({ class: unref(uiProp)?.ellipsis })
									}, {
										default: withCtx((_, _push, _parent, _scopeId) => {
											if (_push) ssrRenderSlot(_ctx.$slots, "ellipsis", { ui: ui.value }, () => {
												_push(ssrRenderComponent(_sfc_main$22, {
													as: "div",
													color: __props.color,
													variant: __props.variant,
													size: __props.size,
													icon: __props.ellipsisIcon || unref(appConfig).ui.icons.ellipsis
												}, null, _parent, _scopeId));
											}, _push, _parent, _scopeId);
											else return [renderSlot(_ctx.$slots, "ellipsis", { ui: ui.value }, () => [createVNode(_sfc_main$22, {
												as: "div",
												color: __props.color,
												variant: __props.variant,
												size: __props.size,
												icon: __props.ellipsisIcon || unref(appConfig).ui.icons.ellipsis
											}, null, 8, [
												"color",
												"variant",
												"size",
												"icon"
											])])];
										}),
										_: 2
									}, _parent, _scopeId));
									_push(`<!--]-->`);
								});
								_push(`<!--]-->`);
								if (__props.showControls || !!slots.next) _push(ssrRenderComponent(unref(PaginationNext), {
									"as-child": "",
									"data-slot": "next",
									class: ui.value.next({ class: unref(uiProp)?.next })
								}, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) ssrRenderSlot(_ctx.$slots, "next", {}, () => {
											_push(ssrRenderComponent(_sfc_main$22, {
												color: __props.color,
												variant: __props.variant,
												size: __props.size,
												icon: nextIcon.value,
												to: page < pageCount ? __props.to?.(page + 1) : void 0
											}, null, _parent, _scopeId));
										}, _push, _parent, _scopeId);
										else return [renderSlot(_ctx.$slots, "next", {}, () => [createVNode(_sfc_main$22, {
											color: __props.color,
											variant: __props.variant,
											size: __props.size,
											icon: nextIcon.value,
											to: page < pageCount ? __props.to?.(page + 1) : void 0
										}, null, 8, [
											"color",
											"variant",
											"size",
											"icon",
											"to"
										])])];
									}),
									_: 2
								}, _parent, _scopeId));
								else _push(`<!---->`);
								if (__props.showControls || !!slots.last) _push(ssrRenderComponent(unref(PaginationLast), {
									"as-child": "",
									"data-slot": "last",
									class: ui.value.last({ class: unref(uiProp)?.last })
								}, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) ssrRenderSlot(_ctx.$slots, "last", {}, () => {
											_push(ssrRenderComponent(_sfc_main$22, {
												color: __props.color,
												variant: __props.variant,
												size: __props.size,
												icon: lastIcon.value,
												to: __props.to?.(pageCount)
											}, null, _parent, _scopeId));
										}, _push, _parent, _scopeId);
										else return [renderSlot(_ctx.$slots, "last", {}, () => [createVNode(_sfc_main$22, {
											color: __props.color,
											variant: __props.variant,
											size: __props.size,
											icon: lastIcon.value,
											to: __props.to?.(pageCount)
										}, null, 8, [
											"color",
											"variant",
											"size",
											"icon",
											"to"
										])])];
									}),
									_: 2
								}, _parent, _scopeId));
								else _push(`<!---->`);
							} else return [
								__props.showControls || !!slots.first ? (openBlock(), createBlock(unref(PaginationFirst), {
									key: 0,
									"as-child": "",
									"data-slot": "first",
									class: ui.value.first({ class: unref(uiProp)?.first })
								}, {
									default: withCtx(() => [renderSlot(_ctx.$slots, "first", {}, () => [createVNode(_sfc_main$22, {
										color: __props.color,
										variant: __props.variant,
										size: __props.size,
										icon: firstIcon.value,
										to: __props.to?.(1)
									}, null, 8, [
										"color",
										"variant",
										"size",
										"icon",
										"to"
									])])]),
									_: 3
								}, 8, ["class"])) : createCommentVNode("", true),
								__props.showControls || !!slots.prev ? (openBlock(), createBlock(unref(PaginationPrev), {
									key: 1,
									"as-child": "",
									"data-slot": "prev",
									class: ui.value.prev({ class: unref(uiProp)?.prev })
								}, {
									default: withCtx(() => [renderSlot(_ctx.$slots, "prev", {}, () => [createVNode(_sfc_main$22, {
										color: __props.color,
										variant: __props.variant,
										size: __props.size,
										icon: prevIcon.value,
										to: page > 1 ? __props.to?.(page - 1) : void 0
									}, null, 8, [
										"color",
										"variant",
										"size",
										"icon",
										"to"
									])])]),
									_: 2
								}, 1032, ["class"])) : createCommentVNode("", true),
								(openBlock(true), createBlock(Fragment, null, renderList(items, (item, index) => {
									return openBlock(), createBlock(Fragment, { key: index }, [item.type === "page" ? (openBlock(), createBlock(unref(PaginationListItem), {
										key: 0,
										"as-child": "",
										value: item.value,
										"data-slot": "item",
										class: ui.value.item({ class: unref(uiProp)?.item })
									}, {
										default: withCtx(() => [renderSlot(_ctx.$slots, "item", mergeProps({ ref_for: true }, {
											item,
											index,
											page,
											pageCount
										}), () => [createVNode(_sfc_main$22, {
											color: page === item.value ? __props.activeColor : __props.color,
											variant: page === item.value ? __props.activeVariant : __props.variant,
											size: __props.size,
											label: String(item.value),
											ui: { label: ui.value.label() },
											to: __props.to?.(item.value),
											square: ""
										}, null, 8, [
											"color",
											"variant",
											"size",
											"label",
											"ui",
											"to"
										])])]),
										_: 2
									}, 1032, ["value", "class"])) : (openBlock(), createBlock(unref(PaginationEllipsis), {
										key: 1,
										"as-child": "",
										"data-slot": "ellipsis",
										class: ui.value.ellipsis({ class: unref(uiProp)?.ellipsis })
									}, {
										default: withCtx(() => [renderSlot(_ctx.$slots, "ellipsis", { ui: ui.value }, () => [createVNode(_sfc_main$22, {
											as: "div",
											color: __props.color,
											variant: __props.variant,
											size: __props.size,
											icon: __props.ellipsisIcon || unref(appConfig).ui.icons.ellipsis
										}, null, 8, [
											"color",
											"variant",
											"size",
											"icon"
										])])]),
										_: 3
									}, 8, ["class"]))], 64);
								}), 128)),
								__props.showControls || !!slots.next ? (openBlock(), createBlock(unref(PaginationNext), {
									key: 2,
									"as-child": "",
									"data-slot": "next",
									class: ui.value.next({ class: unref(uiProp)?.next })
								}, {
									default: withCtx(() => [renderSlot(_ctx.$slots, "next", {}, () => [createVNode(_sfc_main$22, {
										color: __props.color,
										variant: __props.variant,
										size: __props.size,
										icon: nextIcon.value,
										to: page < pageCount ? __props.to?.(page + 1) : void 0
									}, null, 8, [
										"color",
										"variant",
										"size",
										"icon",
										"to"
									])])]),
									_: 2
								}, 1032, ["class"])) : createCommentVNode("", true),
								__props.showControls || !!slots.last ? (openBlock(), createBlock(unref(PaginationLast), {
									key: 3,
									"as-child": "",
									"data-slot": "last",
									class: ui.value.last({ class: unref(uiProp)?.last })
								}, {
									default: withCtx(() => [renderSlot(_ctx.$slots, "last", {}, () => [createVNode(_sfc_main$22, {
										color: __props.color,
										variant: __props.variant,
										size: __props.size,
										icon: lastIcon.value,
										to: __props.to?.(pageCount)
									}, null, 8, [
										"color",
										"variant",
										"size",
										"icon",
										"to"
									])])]),
									_: 2
								}, 1032, ["class"])) : createCommentVNode("", true)
							];
						}),
						_: 2
					}, _parent, _scopeId));
					else return [createVNode(unref(PaginationList), {
						"data-slot": "list",
						class: ui.value.list({ class: unref(uiProp)?.list })
					}, {
						default: withCtx(({ items }) => [
							__props.showControls || !!slots.first ? (openBlock(), createBlock(unref(PaginationFirst), {
								key: 0,
								"as-child": "",
								"data-slot": "first",
								class: ui.value.first({ class: unref(uiProp)?.first })
							}, {
								default: withCtx(() => [renderSlot(_ctx.$slots, "first", {}, () => [createVNode(_sfc_main$22, {
									color: __props.color,
									variant: __props.variant,
									size: __props.size,
									icon: firstIcon.value,
									to: __props.to?.(1)
								}, null, 8, [
									"color",
									"variant",
									"size",
									"icon",
									"to"
								])])]),
								_: 3
							}, 8, ["class"])) : createCommentVNode("", true),
							__props.showControls || !!slots.prev ? (openBlock(), createBlock(unref(PaginationPrev), {
								key: 1,
								"as-child": "",
								"data-slot": "prev",
								class: ui.value.prev({ class: unref(uiProp)?.prev })
							}, {
								default: withCtx(() => [renderSlot(_ctx.$slots, "prev", {}, () => [createVNode(_sfc_main$22, {
									color: __props.color,
									variant: __props.variant,
									size: __props.size,
									icon: prevIcon.value,
									to: page > 1 ? __props.to?.(page - 1) : void 0
								}, null, 8, [
									"color",
									"variant",
									"size",
									"icon",
									"to"
								])])]),
								_: 2
							}, 1032, ["class"])) : createCommentVNode("", true),
							(openBlock(true), createBlock(Fragment, null, renderList(items, (item, index) => {
								return openBlock(), createBlock(Fragment, { key: index }, [item.type === "page" ? (openBlock(), createBlock(unref(PaginationListItem), {
									key: 0,
									"as-child": "",
									value: item.value,
									"data-slot": "item",
									class: ui.value.item({ class: unref(uiProp)?.item })
								}, {
									default: withCtx(() => [renderSlot(_ctx.$slots, "item", mergeProps({ ref_for: true }, {
										item,
										index,
										page,
										pageCount
									}), () => [createVNode(_sfc_main$22, {
										color: page === item.value ? __props.activeColor : __props.color,
										variant: page === item.value ? __props.activeVariant : __props.variant,
										size: __props.size,
										label: String(item.value),
										ui: { label: ui.value.label() },
										to: __props.to?.(item.value),
										square: ""
									}, null, 8, [
										"color",
										"variant",
										"size",
										"label",
										"ui",
										"to"
									])])]),
									_: 2
								}, 1032, ["value", "class"])) : (openBlock(), createBlock(unref(PaginationEllipsis), {
									key: 1,
									"as-child": "",
									"data-slot": "ellipsis",
									class: ui.value.ellipsis({ class: unref(uiProp)?.ellipsis })
								}, {
									default: withCtx(() => [renderSlot(_ctx.$slots, "ellipsis", { ui: ui.value }, () => [createVNode(_sfc_main$22, {
										as: "div",
										color: __props.color,
										variant: __props.variant,
										size: __props.size,
										icon: __props.ellipsisIcon || unref(appConfig).ui.icons.ellipsis
									}, null, 8, [
										"color",
										"variant",
										"size",
										"icon"
									])])]),
									_: 3
								}, 8, ["class"]))], 64);
							}), 128)),
							__props.showControls || !!slots.next ? (openBlock(), createBlock(unref(PaginationNext), {
								key: 2,
								"as-child": "",
								"data-slot": "next",
								class: ui.value.next({ class: unref(uiProp)?.next })
							}, {
								default: withCtx(() => [renderSlot(_ctx.$slots, "next", {}, () => [createVNode(_sfc_main$22, {
									color: __props.color,
									variant: __props.variant,
									size: __props.size,
									icon: nextIcon.value,
									to: page < pageCount ? __props.to?.(page + 1) : void 0
								}, null, 8, [
									"color",
									"variant",
									"size",
									"icon",
									"to"
								])])]),
								_: 2
							}, 1032, ["class"])) : createCommentVNode("", true),
							__props.showControls || !!slots.last ? (openBlock(), createBlock(unref(PaginationLast), {
								key: 3,
								"as-child": "",
								"data-slot": "last",
								class: ui.value.last({ class: unref(uiProp)?.last })
							}, {
								default: withCtx(() => [renderSlot(_ctx.$slots, "last", {}, () => [createVNode(_sfc_main$22, {
									color: __props.color,
									variant: __props.variant,
									size: __props.size,
									icon: lastIcon.value,
									to: __props.to?.(pageCount)
								}, null, 8, [
									"color",
									"variant",
									"size",
									"icon",
									"to"
								])])]),
								_: 2
							}, 1032, ["class"])) : createCommentVNode("", true)
						]),
						_: 2
					}, 1032, ["class"])];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$12 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Pagination.vue");
	return _sfc_setup$12 ? _sfc_setup$12(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/table.ts
var table_default = {
	"slots": {
		"root": "relative overflow-auto",
		"base": "min-w-full overflow-clip",
		"caption": "sr-only",
		"thead": "relative",
		"tbody": "isolate [&>tr]:data-[selectable=true]:hover:bg-elevated/50 [&>tr]:data-[selectable=true]:focus-visible:outline-primary divide-y divide-default",
		"tfoot": "relative",
		"tr": "data-[selected=true]:bg-elevated/50",
		"th": "px-4 py-3.5 text-sm text-highlighted text-left rtl:text-right font-semibold [&:has([role=checkbox])]:pe-0",
		"td": "p-4 text-sm text-muted whitespace-nowrap [&:has([role=checkbox])]:pe-0",
		"separator": "absolute z-1 left-0 w-full h-px bg-(--ui-border-accented)",
		"empty": "py-6 text-center text-sm text-muted",
		"loading": "py-6 text-center"
	},
	"variants": {
		"pinned": { "true": {
			"th": "sticky bg-default/75 z-1",
			"td": "sticky bg-default/75 z-1"
		} },
		"sticky": {
			"true": {
				"thead": "sticky top-0 inset-x-0 bg-default/75 backdrop-blur z-1",
				"tfoot": "sticky bottom-0 inset-x-0 bg-default/75 backdrop-blur z-1"
			},
			"header": { "thead": "sticky top-0 inset-x-0 bg-default/75 backdrop-blur z-1" },
			"footer": { "tfoot": "sticky bottom-0 inset-x-0 bg-default/75 backdrop-blur z-1" }
		},
		"loading": { "true": { "thead": "after:absolute after:z-1 after:h-px" } },
		"loadingAnimation": {
			"carousel": "",
			"carousel-inverse": "",
			"swing": "",
			"elastic": ""
		},
		"loadingColor": {
			"primary": "",
			"secondary": "",
			"success": "",
			"info": "",
			"warning": "",
			"error": "",
			"neutral": ""
		}
	},
	"compoundVariants": [
		{
			"loading": true,
			"loadingColor": "primary",
			"class": { "thead": "after:bg-primary" }
		},
		{
			"loading": true,
			"loadingColor": "secondary",
			"class": { "thead": "after:bg-secondary" }
		},
		{
			"loading": true,
			"loadingColor": "success",
			"class": { "thead": "after:bg-success" }
		},
		{
			"loading": true,
			"loadingColor": "info",
			"class": { "thead": "after:bg-info" }
		},
		{
			"loading": true,
			"loadingColor": "warning",
			"class": { "thead": "after:bg-warning" }
		},
		{
			"loading": true,
			"loadingColor": "error",
			"class": { "thead": "after:bg-error" }
		},
		{
			"loading": true,
			"loadingColor": "neutral",
			"class": { "thead": "after:bg-inverted" }
		},
		{
			"loading": true,
			"loadingAnimation": "carousel",
			"class": { "thead": "after:animate-[carousel_2s_ease-in-out_infinite] rtl:after:animate-[carousel-rtl_2s_ease-in-out_infinite]" }
		},
		{
			"loading": true,
			"loadingAnimation": "carousel-inverse",
			"class": { "thead": "after:animate-[carousel-inverse_2s_ease-in-out_infinite] rtl:after:animate-[carousel-inverse-rtl_2s_ease-in-out_infinite]" }
		},
		{
			"loading": true,
			"loadingAnimation": "swing",
			"class": { "thead": "after:animate-[swing_2s_ease-in-out_infinite]" }
		},
		{
			"loading": true,
			"loadingAnimation": "elastic",
			"class": { "thead": "after:animate-[elastic_2s_ease-in-out_infinite]" }
		}
	],
	"defaultVariants": {
		"loadingColor": "primary",
		"loadingAnimation": "carousel"
	}
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Table.vue
var _sfc_main$4 = /* @__PURE__ */ Object.assign({ inheritAttrs: false }, {
	__name: "Table",
	__ssrInlineRender: true,
	props: /* @__PURE__ */ mergeModels({
		as: {
			type: null,
			required: false
		},
		data: {
			type: Array,
			required: false
		},
		columns: {
			type: Array,
			required: false
		},
		caption: {
			type: String,
			required: false
		},
		meta: {
			type: Object,
			required: false
		},
		virtualize: {
			type: [Boolean, Object],
			required: false,
			default: false
		},
		empty: {
			type: String,
			required: false
		},
		sticky: {
			type: [Boolean, String],
			required: false
		},
		loading: {
			type: Boolean,
			required: false
		},
		loadingColor: {
			type: null,
			required: false
		},
		loadingAnimation: {
			type: null,
			required: false
		},
		watchOptions: {
			type: Object,
			required: false,
			default: () => ({ deep: true })
		},
		globalFilterOptions: {
			type: Object,
			required: false
		},
		columnFiltersOptions: {
			type: Object,
			required: false
		},
		columnPinningOptions: {
			type: Object,
			required: false
		},
		columnSizingOptions: {
			type: Object,
			required: false
		},
		visibilityOptions: {
			type: Object,
			required: false
		},
		sortingOptions: {
			type: Object,
			required: false
		},
		groupingOptions: {
			type: Object,
			required: false
		},
		expandedOptions: {
			type: Object,
			required: false
		},
		rowSelectionOptions: {
			type: Object,
			required: false
		},
		rowPinningOptions: {
			type: Object,
			required: false
		},
		paginationOptions: {
			type: Object,
			required: false
		},
		facetedOptions: {
			type: Object,
			required: false
		},
		onSelect: {
			type: Function,
			required: false
		},
		onHover: {
			type: Function,
			required: false
		},
		onContextmenu: {
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
		state: {
			type: Object,
			required: false
		},
		onStateChange: {
			type: Function,
			required: false
		},
		renderFallbackValue: {
			type: null,
			required: false
		},
		_features: {
			type: Array,
			required: false
		},
		autoResetAll: {
			type: Boolean,
			required: false
		},
		debugAll: {
			type: Boolean,
			required: false
		},
		debugCells: {
			type: Boolean,
			required: false
		},
		debugColumns: {
			type: Boolean,
			required: false
		},
		debugHeaders: {
			type: Boolean,
			required: false
		},
		debugRows: {
			type: Boolean,
			required: false
		},
		debugTable: {
			type: Boolean,
			required: false
		},
		defaultColumn: {
			type: Object,
			required: false
		},
		getRowId: {
			type: Function,
			required: false
		},
		getSubRows: {
			type: Function,
			required: false
		},
		initialState: {
			type: Object,
			required: false
		},
		mergeOptions: {
			type: Function,
			required: false
		}
	}, {
		"globalFilter": { type: String },
		"globalFilterModifiers": {},
		"columnFilters": { type: Array },
		"columnFiltersModifiers": {},
		"columnOrder": { type: Array },
		"columnOrderModifiers": {},
		"columnVisibility": { type: Object },
		"columnVisibilityModifiers": {},
		"columnPinning": { type: Object },
		"columnPinningModifiers": {},
		"columnSizing": { type: Object },
		"columnSizingModifiers": {},
		"columnSizingInfo": { type: Object },
		"columnSizingInfoModifiers": {},
		"rowSelection": { type: Object },
		"rowSelectionModifiers": {},
		"rowPinning": { type: Object },
		"rowPinningModifiers": {},
		"sorting": { type: Array },
		"sortingModifiers": {},
		"grouping": { type: Array },
		"groupingModifiers": {},
		"expanded": { type: [Boolean, Object] },
		"expandedModifiers": {},
		"pagination": { type: Object },
		"paginationModifiers": {}
	}),
	emits: [
		"update:globalFilter",
		"update:columnFilters",
		"update:columnOrder",
		"update:columnVisibility",
		"update:columnPinning",
		"update:columnSizing",
		"update:columnSizingInfo",
		"update:rowSelection",
		"update:rowPinning",
		"update:sorting",
		"update:grouping",
		"update:expanded",
		"update:pagination"
	],
	setup(__props, { expose: __expose }) {
		const props = __props;
		const slots = useSlots();
		const { t } = useLocale();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("table", props);
		const data = createRef(props.data ?? [], props.watchOptions?.deep !== false);
		const meta = computed(() => props.meta ?? {});
		const columns = computed(() => processColumns(props.columns ?? Object.keys(data.value[0] ?? {}).map((accessorKey) => ({
			accessorKey,
			header: upperFirst(accessorKey)
		}))));
		function processColumns(columns2) {
			return columns2.map((column) => {
				const col = { ...column };
				if ("columns" in col && col.columns) col.columns = processColumns(col.columns);
				if (!col.cell) col.cell = ({ getValue }) => {
					const value = getValue();
					if (value === "" || value === null || value === void 0) return "\xA0";
					return String(value);
				};
				return col;
			});
		}
		const ui = computed(() => tv({
			extend: tv(table_default),
			...appConfig.ui?.table || {}
		})({
			sticky: props.sticky,
			loading: props.loading,
			loadingColor: props.loadingColor,
			loadingAnimation: props.loadingAnimation
		}));
		const [DefineTableTemplate, ReuseTableTemplate] = createReusableTemplate();
		const [DefineRowTemplate, ReuseRowTemplate] = createReusableTemplate({ props: {
			row: {
				type: Object,
				required: true
			},
			style: {
				type: Object,
				required: false
			}
		} });
		const hasFooter = computed(() => {
			function hasFooterRecursive(columns2) {
				for (const column of columns2) {
					if ("footer" in column) return true;
					if ("columns" in column && hasFooterRecursive(column.columns)) return true;
				}
				return false;
			}
			return hasFooterRecursive(columns.value);
		});
		const globalFilterState = useModel(__props, "globalFilter");
		const columnFiltersState = useModel(__props, "columnFilters");
		const columnOrderState = useModel(__props, "columnOrder");
		const columnVisibilityState = useModel(__props, "columnVisibility");
		const columnPinningState = useModel(__props, "columnPinning");
		const columnSizingState = useModel(__props, "columnSizing");
		const columnSizingInfoState = useModel(__props, "columnSizingInfo");
		const rowSelectionState = useModel(__props, "rowSelection");
		const rowPinningState = useModel(__props, "rowPinning");
		const sortingState = useModel(__props, "sorting");
		const groupingState = useModel(__props, "grouping");
		const expandedState = useModel(__props, "expanded");
		const paginationState = useModel(__props, "pagination");
		const rootRef = useTemplateRef("rootRef");
		const tableRef = useTemplateRef("tableRef");
		const tableApi = useVueTable({
			...useForwardProps(reactivePick(props, "_features", "autoResetAll", "debugAll", "debugCells", "debugColumns", "debugHeaders", "debugRows", "debugTable", "defaultColumn", "getRowId", "getSubRows", "initialState", "mergeOptions", "renderFallbackValue")).value,
			get data() {
				return data.value;
			},
			get columns() {
				return columns.value;
			},
			meta: meta.value,
			getCoreRowModel: getCoreRowModel(),
			...props.globalFilterOptions || {},
			...globalFilterState.value !== void 0 && { onGlobalFilterChange: (updaterOrValue) => valueUpdater(updaterOrValue, globalFilterState) },
			...props.columnFiltersOptions || {},
			getFilteredRowModel: getFilteredRowModel(),
			...columnFiltersState.value !== void 0 && { onColumnFiltersChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnFiltersState) },
			...columnOrderState.value !== void 0 && { onColumnOrderChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnOrderState) },
			...props.visibilityOptions || {},
			...columnVisibilityState.value !== void 0 && { onColumnVisibilityChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnVisibilityState) },
			...props.columnPinningOptions || {},
			...columnPinningState.value !== void 0 && { onColumnPinningChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnPinningState) },
			...props.columnSizingOptions || {},
			...columnSizingState.value !== void 0 && { onColumnSizingChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnSizingState) },
			...columnSizingInfoState.value !== void 0 && { onColumnSizingInfoChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnSizingInfoState) },
			...props.rowSelectionOptions || {},
			...rowSelectionState.value !== void 0 && { onRowSelectionChange: (updaterOrValue) => valueUpdater(updaterOrValue, rowSelectionState) },
			...props.rowPinningOptions || {},
			...rowPinningState.value !== void 0 && { onRowPinningChange: (updaterOrValue) => valueUpdater(updaterOrValue, rowPinningState) },
			...props.sortingOptions || {},
			getSortedRowModel: getSortedRowModel(),
			...sortingState.value !== void 0 && { onSortingChange: (updaterOrValue) => valueUpdater(updaterOrValue, sortingState) },
			...props.groupingOptions || {},
			...groupingState.value !== void 0 && { onGroupingChange: (updaterOrValue) => valueUpdater(updaterOrValue, groupingState) },
			...props.expandedOptions || {},
			getExpandedRowModel: getExpandedRowModel(),
			...expandedState.value !== void 0 && { onExpandedChange: (updaterOrValue) => valueUpdater(updaterOrValue, expandedState) },
			...props.paginationOptions || {},
			...paginationState.value !== void 0 && { onPaginationChange: (updaterOrValue) => valueUpdater(updaterOrValue, paginationState) },
			...props.facetedOptions || {},
			state: {
				get globalFilter() {
					return globalFilterState.value;
				},
				get columnFilters() {
					return columnFiltersState.value;
				},
				get columnOrder() {
					return columnOrderState.value;
				},
				get columnVisibility() {
					return columnVisibilityState.value;
				},
				get columnPinning() {
					return columnPinningState.value;
				},
				get expanded() {
					return expandedState.value;
				},
				get rowSelection() {
					return rowSelectionState.value;
				},
				get sorting() {
					return sortingState.value;
				},
				get grouping() {
					return groupingState.value;
				},
				get rowPinning() {
					return rowPinningState.value;
				},
				get columnSizing() {
					return columnSizingState.value;
				},
				get columnSizingInfo() {
					return columnSizingInfoState.value;
				},
				get pagination() {
					return paginationState.value;
				}
			}
		});
		const rows = computed(() => tableApi.getRowModel().rows);
		const topRows = computed(() => props.virtualize ? [] : tableApi.getTopRows());
		const bottomRows = computed(() => props.virtualize ? [] : tableApi.getBottomRows());
		const centerRows = computed(() => topRows.value.length || bottomRows.value.length ? tableApi.getCenterRows() : rows.value);
		const virtualizerProps = toRef(() => defu(typeof props.virtualize === "boolean" ? {} : props.virtualize, {
			estimateSize: 65,
			overscan: 12
		}));
		const virtualizer = !!props.virtualize && useVirtualizer({
			...virtualizerProps.value,
			get count() {
				return centerRows.value.length;
			},
			getScrollElement: () => rootRef.value?.$el,
			estimateSize: (index) => {
				const estimate = virtualizerProps.value.estimateSize;
				return typeof estimate === "function" ? estimate(index) : estimate;
			}
		});
		const virtualItems = computed(() => virtualizer ? virtualizer.value.getVirtualItems() : []);
		const virtualPaddingTop = computed(() => virtualItems.value[0]?.start ?? 0);
		const virtualPaddingBottom = computed(() => {
			if (!virtualizer || !virtualItems.value.length) return 0;
			return virtualizer.value.getTotalSize() - (virtualItems.value[virtualItems.value.length - 1]?.end ?? 0);
		});
		function valueUpdater(updaterOrValue, ref) {
			ref.value = typeof updaterOrValue === "function" ? updaterOrValue(ref.value) : updaterOrValue;
		}
		function onRowSelect(e, row) {
			if (!props.onSelect) return;
			const target = e.target;
			if (target.closest("button") || target.closest("a")) return;
			e.preventDefault();
			e.stopPropagation();
			props.onSelect(e, row);
		}
		function onRowHover(e, row) {
			if (!props.onHover) return;
			props.onHover(e, row);
		}
		function onRowContextmenu(e, row) {
			if (!props.onContextmenu) return;
			if (Array.isArray(props.onContextmenu)) props.onContextmenu.forEach((fn) => fn(e, row));
			else props.onContextmenu(e, row);
		}
		function resolveValue(prop, arg) {
			if (typeof prop === "function") return prop(arg);
			return prop;
		}
		function getColumnStyles(column) {
			const styles = {};
			const pinned = column.getIsPinned();
			if (pinned === "left") styles.left = `${column.getStart("left")}px`;
			else if (pinned === "right") styles.right = `${column.getAfter("right")}px`;
			return styles;
		}
		watch(() => props.data, () => {
			data.value = props.data ? [...props.data] : [];
		}, props.watchOptions);
		__expose({
			get $el() {
				return rootRef.value?.$el;
			},
			tableRef,
			tableApi
		});
		return (_ctx, _push, _parent, _attrs) => {
			_push(`<!--[-->`);
			_push(ssrRenderComponent(unref(DefineRowTemplate), null, {
				default: withCtx(({ row, style }, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<tr${ssrRenderAttr("data-selected", row.getIsSelected())}${ssrRenderAttr("data-selectable", !!props.onSelect || !!props.onHover || !!props.onContextmenu)}${ssrRenderAttr("data-expanded", row.getIsExpanded())}${ssrRenderAttr("data-pinned", row.getIsPinned() || void 0)}${ssrRenderAttr("role", props.onSelect ? "button" : void 0)}${ssrRenderAttr("tabindex", props.onSelect ? 0 : void 0)} data-slot="tr" class="${ssrRenderClass(ui.value.tr({ class: [unref(uiProp)?.tr, resolveValue(unref(tableApi).options.meta?.class?.tr, row)] }))}" style="${ssrRenderStyle([resolveValue(unref(tableApi).options.meta?.style?.tr, row), style])}"${_scopeId}><!--[-->`);
						ssrRenderList(row.getVisibleCells(), (cell) => {
							_push(`<td${ssrRenderAttr("data-pinned", cell.column.getIsPinned())}${ssrRenderAttr("colspan", resolveValue(cell.column.columnDef.meta?.colspan?.td, cell))}${ssrRenderAttr("rowspan", resolveValue(cell.column.columnDef.meta?.rowspan?.td, cell))} data-slot="td" class="${ssrRenderClass(ui.value.td({
								class: [unref(uiProp)?.td, resolveValue(cell.column.columnDef.meta?.class?.td, cell)],
								pinned: !!cell.column.getIsPinned()
							}))}" style="${ssrRenderStyle([getColumnStyles(cell.column), resolveValue(cell.column.columnDef.meta?.style?.td, cell)])}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, `${cell.column.id}-cell`, mergeProps({ ref_for: true }, cell.getContext()), () => {
								_push(ssrRenderComponent(unref(FlexRender), {
									render: cell.column.columnDef.cell,
									props: cell.getContext()
								}, null, _parent, _scopeId));
							}, _push, _parent, _scopeId);
							_push(`</td>`);
						});
						_push(`<!--]--></tr>`);
						if (row.getIsExpanded()) {
							_push(`<tr data-slot="tr" class="${ssrRenderClass(ui.value.tr({ class: [unref(uiProp)?.tr] }))}"${_scopeId}><td${ssrRenderAttr("colspan", row.getAllCells().length)} data-slot="td" class="${ssrRenderClass(ui.value.td({ class: [unref(uiProp)?.td] }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "expanded", { row }, null, _push, _parent, _scopeId);
							_push(`</td></tr>`);
						} else _push(`<!---->`);
					} else return [createVNode("tr", {
						"data-selected": row.getIsSelected(),
						"data-selectable": !!props.onSelect || !!props.onHover || !!props.onContextmenu,
						"data-expanded": row.getIsExpanded(),
						"data-pinned": row.getIsPinned() || void 0,
						role: props.onSelect ? "button" : void 0,
						tabindex: props.onSelect ? 0 : void 0,
						"data-slot": "tr",
						class: ui.value.tr({ class: [unref(uiProp)?.tr, resolveValue(unref(tableApi).options.meta?.class?.tr, row)] }),
						style: [resolveValue(unref(tableApi).options.meta?.style?.tr, row), style],
						onClick: ($event) => onRowSelect($event, row),
						onPointerenter: ($event) => onRowHover($event, row),
						onPointerleave: ($event) => onRowHover($event, null),
						onContextmenu: ($event) => onRowContextmenu($event, row)
					}, [(openBlock(true), createBlock(Fragment, null, renderList(row.getVisibleCells(), (cell) => {
						return openBlock(), createBlock("td", {
							key: cell.id,
							"data-pinned": cell.column.getIsPinned(),
							colspan: resolveValue(cell.column.columnDef.meta?.colspan?.td, cell),
							rowspan: resolveValue(cell.column.columnDef.meta?.rowspan?.td, cell),
							"data-slot": "td",
							class: ui.value.td({
								class: [unref(uiProp)?.td, resolveValue(cell.column.columnDef.meta?.class?.td, cell)],
								pinned: !!cell.column.getIsPinned()
							}),
							style: [getColumnStyles(cell.column), resolveValue(cell.column.columnDef.meta?.style?.td, cell)]
						}, [renderSlot(_ctx.$slots, `${cell.column.id}-cell`, mergeProps({ ref_for: true }, cell.getContext()), () => [createVNode(unref(FlexRender), {
							render: cell.column.columnDef.cell,
							props: cell.getContext()
						}, null, 8, ["render", "props"])])], 14, [
							"data-pinned",
							"colspan",
							"rowspan"
						]);
					}), 128))], 46, [
						"data-selected",
						"data-selectable",
						"data-expanded",
						"data-pinned",
						"role",
						"tabindex",
						"onClick",
						"onPointerenter",
						"onPointerleave",
						"onContextmenu"
					]), row.getIsExpanded() ? (openBlock(), createBlock("tr", {
						key: 0,
						"data-slot": "tr",
						class: ui.value.tr({ class: [unref(uiProp)?.tr] })
					}, [createVNode("td", {
						colspan: row.getAllCells().length,
						"data-slot": "td",
						class: ui.value.td({ class: [unref(uiProp)?.td] })
					}, [renderSlot(_ctx.$slots, "expanded", { row })], 10, ["colspan"])], 2)) : createCommentVNode("", true)];
				}),
				_: 3
			}, _parent));
			_push(ssrRenderComponent(unref(DefineTableTemplate), null, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<table data-slot="base" class="${ssrRenderClass(ui.value.base({ class: [unref(uiProp)?.base] }))}"${_scopeId}>`);
						if (__props.caption || !!slots.caption) {
							_push(`<caption data-slot="caption" class="${ssrRenderClass(ui.value.caption({ class: [unref(uiProp)?.caption] }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "caption", {}, () => {
								_push(`${ssrInterpolate(__props.caption)}`);
							}, _push, _parent, _scopeId);
							_push(`</caption>`);
						} else _push(`<!---->`);
						_push(`<thead data-slot="thead" class="${ssrRenderClass(ui.value.thead({ class: [unref(uiProp)?.thead] }))}"${_scopeId}><!--[-->`);
						ssrRenderList(unref(tableApi).getHeaderGroups(), (headerGroup) => {
							_push(`<tr data-slot="tr" class="${ssrRenderClass(ui.value.tr({ class: [unref(uiProp)?.tr] }))}"${_scopeId}><!--[-->`);
							ssrRenderList(headerGroup.headers, (header) => {
								_push(`<th${ssrRenderAttr("data-pinned", header.column.getIsPinned())}${ssrRenderAttr("scope", header.colSpan > 1 ? "colgroup" : "col")}${ssrRenderAttr("colspan", header.colSpan > 1 ? header.colSpan : void 0)}${ssrRenderAttr("rowspan", header.rowSpan > 1 ? header.rowSpan : void 0)} data-slot="th" class="${ssrRenderClass(ui.value.th({
									class: [unref(uiProp)?.th, resolveValue(header.column.columnDef.meta?.class?.th, header)],
									pinned: !!header.column.getIsPinned()
								}))}" style="${ssrRenderStyle([getColumnStyles(header.column), resolveValue(header.column.columnDef.meta?.style?.th, header)])}"${_scopeId}>`);
								ssrRenderSlot(_ctx.$slots, `${header.id}-header`, mergeProps({ ref_for: true }, header.getContext()), () => {
									if (!header.isPlaceholder) _push(ssrRenderComponent(unref(FlexRender), {
										render: header.column.columnDef.header,
										props: header.getContext()
									}, null, _parent, _scopeId));
									else _push(`<!---->`);
								}, _push, _parent, _scopeId);
								_push(`</th>`);
							});
							_push(`<!--]--></tr>`);
						});
						_push(`<!--]--><tr data-slot="separator" class="${ssrRenderClass(ui.value.separator({ class: [unref(uiProp)?.separator] }))}"${_scopeId}></tr></thead><tbody data-slot="tbody" class="${ssrRenderClass(ui.value.tbody({ class: [unref(uiProp)?.tbody] }))}"${_scopeId}>`);
						ssrRenderSlot(_ctx.$slots, "body-top", {}, null, _push, _parent, _scopeId);
						if (rows.value.length) {
							_push(`<!--[--><!--[-->`);
							ssrRenderList(topRows.value, (row) => {
								_push(ssrRenderComponent(unref(ReuseRowTemplate), {
									key: row.id,
									row
								}, null, _parent, _scopeId));
							});
							_push(`<!--]-->`);
							if (unref(virtualizer)) {
								_push(`<!--[-->`);
								if (virtualPaddingTop.value > 0) _push(`<tr style="${ssrRenderStyle({ height: `${virtualPaddingTop.value}px` })}" aria-hidden="true"${_scopeId}><td${ssrRenderAttr("colspan", unref(tableApi).getAllLeafColumns().length)}${_scopeId}></td></tr>`);
								else _push(`<!---->`);
								_push(`<!--[-->`);
								ssrRenderList(virtualItems.value, (virtualRow) => {
									_push(`<!--[-->`);
									if (centerRows.value[virtualRow.index]) _push(ssrRenderComponent(unref(ReuseRowTemplate), {
										row: centerRows.value[virtualRow.index],
										style: { height: `${virtualRow.size}px` }
									}, null, _parent, _scopeId));
									else _push(`<!---->`);
									_push(`<!--]-->`);
								});
								_push(`<!--]-->`);
								if (virtualPaddingBottom.value > 0) _push(`<tr style="${ssrRenderStyle({ height: `${virtualPaddingBottom.value}px` })}" aria-hidden="true"${_scopeId}><td${ssrRenderAttr("colspan", unref(tableApi).getAllLeafColumns().length)}${_scopeId}></td></tr>`);
								else _push(`<!---->`);
								_push(`<!--]-->`);
							} else {
								_push(`<!--[-->`);
								ssrRenderList(centerRows.value, (row) => {
									_push(ssrRenderComponent(unref(ReuseRowTemplate), {
										key: row.id,
										row
									}, null, _parent, _scopeId));
								});
								_push(`<!--]-->`);
							}
							_push(`<!--[-->`);
							ssrRenderList(bottomRows.value, (row) => {
								_push(ssrRenderComponent(unref(ReuseRowTemplate), {
									key: row.id,
									row
								}, null, _parent, _scopeId));
							});
							_push(`<!--]--><!--]-->`);
						} else if (__props.loading && !!slots["loading"]) {
							_push(`<tr${_scopeId}><td${ssrRenderAttr("colspan", unref(tableApi).getAllLeafColumns().length)} data-slot="loading" class="${ssrRenderClass(ui.value.loading({ class: unref(uiProp)?.loading }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "loading", {}, null, _push, _parent, _scopeId);
							_push(`</td></tr>`);
						} else {
							_push(`<tr${_scopeId}><td${ssrRenderAttr("colspan", unref(tableApi).getAllLeafColumns().length)} data-slot="empty" class="${ssrRenderClass(ui.value.empty({ class: unref(uiProp)?.empty }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "empty", {}, () => {
								_push(`${ssrInterpolate(__props.empty || unref(t)("table.noData"))}`);
							}, _push, _parent, _scopeId);
							_push(`</td></tr>`);
						}
						ssrRenderSlot(_ctx.$slots, "body-bottom", {}, null, _push, _parent, _scopeId);
						_push(`</tbody>`);
						if (hasFooter.value) {
							_push(`<tfoot data-slot="tfoot" class="${ssrRenderClass(ui.value.tfoot({ class: [unref(uiProp)?.tfoot] }))}"${_scopeId}><tr data-slot="separator" class="${ssrRenderClass(ui.value.separator({ class: [unref(uiProp)?.separator] }))}"${_scopeId}></tr><!--[-->`);
							ssrRenderList(unref(tableApi).getFooterGroups(), (footerGroup) => {
								_push(`<tr data-slot="tr" class="${ssrRenderClass(ui.value.tr({ class: [unref(uiProp)?.tr] }))}"${_scopeId}><!--[-->`);
								ssrRenderList(footerGroup.headers, (header) => {
									_push(`<th${ssrRenderAttr("data-pinned", header.column.getIsPinned())}${ssrRenderAttr("colspan", header.colSpan > 1 ? header.colSpan : void 0)}${ssrRenderAttr("rowspan", header.rowSpan > 1 ? header.rowSpan : void 0)} data-slot="th" class="${ssrRenderClass(ui.value.th({
										class: [unref(uiProp)?.th, resolveValue(header.column.columnDef.meta?.class?.th, header)],
										pinned: !!header.column.getIsPinned()
									}))}" style="${ssrRenderStyle([getColumnStyles(header.column), resolveValue(header.column.columnDef.meta?.style?.th, header)])}"${_scopeId}>`);
									ssrRenderSlot(_ctx.$slots, `${header.id}-footer`, mergeProps({ ref_for: true }, header.getContext()), () => {
										if (!header.isPlaceholder) _push(ssrRenderComponent(unref(FlexRender), {
											render: header.column.columnDef.footer,
											props: header.getContext()
										}, null, _parent, _scopeId));
										else _push(`<!---->`);
									}, _push, _parent, _scopeId);
									_push(`</th>`);
								});
								_push(`<!--]--></tr>`);
							});
							_push(`<!--]--></tfoot>`);
						} else _push(`<!---->`);
						_push(`</table>`);
					} else return [createVNode("table", {
						ref_key: "tableRef",
						ref: tableRef,
						"data-slot": "base",
						class: ui.value.base({ class: [unref(uiProp)?.base] })
					}, [
						__props.caption || !!slots.caption ? (openBlock(), createBlock("caption", {
							key: 0,
							"data-slot": "caption",
							class: ui.value.caption({ class: [unref(uiProp)?.caption] })
						}, [renderSlot(_ctx.$slots, "caption", {}, () => [createTextVNode(toDisplayString(__props.caption), 1)])], 2)) : createCommentVNode("", true),
						createVNode("thead", {
							"data-slot": "thead",
							class: ui.value.thead({ class: [unref(uiProp)?.thead] })
						}, [(openBlock(true), createBlock(Fragment, null, renderList(unref(tableApi).getHeaderGroups(), (headerGroup) => {
							return openBlock(), createBlock("tr", {
								key: headerGroup.id,
								"data-slot": "tr",
								class: ui.value.tr({ class: [unref(uiProp)?.tr] })
							}, [(openBlock(true), createBlock(Fragment, null, renderList(headerGroup.headers, (header) => {
								return openBlock(), createBlock("th", {
									key: header.id,
									"data-pinned": header.column.getIsPinned(),
									scope: header.colSpan > 1 ? "colgroup" : "col",
									colspan: header.colSpan > 1 ? header.colSpan : void 0,
									rowspan: header.rowSpan > 1 ? header.rowSpan : void 0,
									"data-slot": "th",
									class: ui.value.th({
										class: [unref(uiProp)?.th, resolveValue(header.column.columnDef.meta?.class?.th, header)],
										pinned: !!header.column.getIsPinned()
									}),
									style: [getColumnStyles(header.column), resolveValue(header.column.columnDef.meta?.style?.th, header)]
								}, [renderSlot(_ctx.$slots, `${header.id}-header`, mergeProps({ ref_for: true }, header.getContext()), () => [!header.isPlaceholder ? (openBlock(), createBlock(unref(FlexRender), {
									key: 0,
									render: header.column.columnDef.header,
									props: header.getContext()
								}, null, 8, ["render", "props"])) : createCommentVNode("", true)])], 14, [
									"data-pinned",
									"scope",
									"colspan",
									"rowspan"
								]);
							}), 128))], 2);
						}), 128)), createVNode("tr", {
							"data-slot": "separator",
							class: ui.value.separator({ class: [unref(uiProp)?.separator] })
						}, null, 2)], 2),
						createVNode("tbody", {
							"data-slot": "tbody",
							class: ui.value.tbody({ class: [unref(uiProp)?.tbody] })
						}, [
							renderSlot(_ctx.$slots, "body-top"),
							rows.value.length ? (openBlock(), createBlock(Fragment, { key: 0 }, [
								(openBlock(true), createBlock(Fragment, null, renderList(topRows.value, (row) => {
									return openBlock(), createBlock(unref(ReuseRowTemplate), {
										key: row.id,
										row
									}, null, 8, ["row"]);
								}), 128)),
								unref(virtualizer) ? (openBlock(), createBlock(Fragment, { key: 0 }, [
									virtualPaddingTop.value > 0 ? (openBlock(), createBlock("tr", {
										key: 0,
										style: { height: `${virtualPaddingTop.value}px` },
										"aria-hidden": "true"
									}, [createVNode("td", { colspan: unref(tableApi).getAllLeafColumns().length }, null, 8, ["colspan"])], 4)) : createCommentVNode("", true),
									(openBlock(true), createBlock(Fragment, null, renderList(virtualItems.value, (virtualRow) => {
										return openBlock(), createBlock(Fragment, { key: centerRows.value[virtualRow.index]?.id ?? `virtual-${virtualRow.index}` }, [centerRows.value[virtualRow.index] ? (openBlock(), createBlock(unref(ReuseRowTemplate), {
											key: 0,
											row: centerRows.value[virtualRow.index],
											style: { height: `${virtualRow.size}px` }
										}, null, 8, ["row", "style"])) : createCommentVNode("", true)], 64);
									}), 128)),
									virtualPaddingBottom.value > 0 ? (openBlock(), createBlock("tr", {
										key: 1,
										style: { height: `${virtualPaddingBottom.value}px` },
										"aria-hidden": "true"
									}, [createVNode("td", { colspan: unref(tableApi).getAllLeafColumns().length }, null, 8, ["colspan"])], 4)) : createCommentVNode("", true)
								], 64)) : (openBlock(true), createBlock(Fragment, { key: 1 }, renderList(centerRows.value, (row) => {
									return openBlock(), createBlock(unref(ReuseRowTemplate), {
										key: row.id,
										row
									}, null, 8, ["row"]);
								}), 128)),
								(openBlock(true), createBlock(Fragment, null, renderList(bottomRows.value, (row) => {
									return openBlock(), createBlock(unref(ReuseRowTemplate), {
										key: row.id,
										row
									}, null, 8, ["row"]);
								}), 128))
							], 64)) : __props.loading && !!slots["loading"] ? (openBlock(), createBlock("tr", { key: 1 }, [createVNode("td", {
								colspan: unref(tableApi).getAllLeafColumns().length,
								"data-slot": "loading",
								class: ui.value.loading({ class: unref(uiProp)?.loading })
							}, [renderSlot(_ctx.$slots, "loading")], 10, ["colspan"])])) : (openBlock(), createBlock("tr", { key: 2 }, [createVNode("td", {
								colspan: unref(tableApi).getAllLeafColumns().length,
								"data-slot": "empty",
								class: ui.value.empty({ class: unref(uiProp)?.empty })
							}, [renderSlot(_ctx.$slots, "empty", {}, () => [createTextVNode(toDisplayString(__props.empty || unref(t)("table.noData")), 1)])], 10, ["colspan"])])),
							renderSlot(_ctx.$slots, "body-bottom")
						], 2),
						hasFooter.value ? (openBlock(), createBlock("tfoot", {
							key: 1,
							"data-slot": "tfoot",
							class: ui.value.tfoot({ class: [unref(uiProp)?.tfoot] })
						}, [createVNode("tr", {
							"data-slot": "separator",
							class: ui.value.separator({ class: [unref(uiProp)?.separator] })
						}, null, 2), (openBlock(true), createBlock(Fragment, null, renderList(unref(tableApi).getFooterGroups(), (footerGroup) => {
							return openBlock(), createBlock("tr", {
								key: footerGroup.id,
								"data-slot": "tr",
								class: ui.value.tr({ class: [unref(uiProp)?.tr] })
							}, [(openBlock(true), createBlock(Fragment, null, renderList(footerGroup.headers, (header) => {
								return openBlock(), createBlock("th", {
									key: header.id,
									"data-pinned": header.column.getIsPinned(),
									colspan: header.colSpan > 1 ? header.colSpan : void 0,
									rowspan: header.rowSpan > 1 ? header.rowSpan : void 0,
									"data-slot": "th",
									class: ui.value.th({
										class: [unref(uiProp)?.th, resolveValue(header.column.columnDef.meta?.class?.th, header)],
										pinned: !!header.column.getIsPinned()
									}),
									style: [getColumnStyles(header.column), resolveValue(header.column.columnDef.meta?.style?.th, header)]
								}, [renderSlot(_ctx.$slots, `${header.id}-footer`, mergeProps({ ref_for: true }, header.getContext()), () => [!header.isPlaceholder ? (openBlock(), createBlock(unref(FlexRender), {
									key: 0,
									render: header.column.columnDef.footer,
									props: header.getContext()
								}, null, 8, ["render", "props"])) : createCommentVNode("", true)])], 14, [
									"data-pinned",
									"colspan",
									"rowspan"
								]);
							}), 128))], 2);
						}), 128))], 2)) : createCommentVNode("", true)
					], 2)];
				}),
				_: 3
			}, _parent));
			_push(ssrRenderComponent(unref(Primitive), mergeProps({
				ref_key: "rootRef",
				ref: rootRef,
				as: __props.as
			}, _ctx.$attrs, {
				"data-slot": "root",
				class: ui.value.root({ class: [unref(uiProp)?.root, props.class] })
			}), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(ssrRenderComponent(unref(ReuseTableTemplate), null, null, _parent, _scopeId));
					else return [createVNode(unref(ReuseTableTemplate))];
				}),
				_: 1
			}, _parent));
			_push(`<!--]-->`);
		};
	}
});
var _sfc_setup$11 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Table.vue");
	return _sfc_setup$11 ? _sfc_setup$11(props, ctx) : void 0;
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/ContextMenuContent.vue
var _sfc_main$3 = {
	__name: "ContextMenuContent",
	__ssrInlineRender: true,
	props: {
		items: {
			type: null,
			required: false
		},
		portal: {
			type: [Boolean, String],
			required: false,
			skipCheck: true
		},
		sub: {
			type: Boolean,
			required: false
		},
		labelKey: {
			type: null,
			required: true
		},
		descriptionKey: {
			type: null,
			required: true
		},
		checkedIcon: {
			type: null,
			required: false
		},
		loadingIcon: {
			type: null,
			required: false
		},
		externalIcon: {
			type: [Boolean, String],
			required: false,
			skipCheck: true
		},
		class: {
			type: null,
			required: false
		},
		ui: {
			type: null,
			required: true
		},
		uiOverride: {
			type: null,
			required: false
		},
		loop: {
			type: Boolean,
			required: false
		},
		sideFlip: {
			type: Boolean,
			required: false
		},
		alignOffset: {
			type: Number,
			required: false
		},
		alignFlip: {
			type: Boolean,
			required: false
		},
		avoidCollisions: {
			type: Boolean,
			required: false
		},
		collisionBoundary: {
			type: null,
			required: false
		},
		collisionPadding: {
			type: [Number, Object],
			required: false
		},
		hideShiftedArrow: {
			type: Boolean,
			required: false
		},
		sticky: {
			type: String,
			required: false
		},
		hideWhenDetached: {
			type: Boolean,
			required: false
		},
		positionStrategy: {
			type: String,
			required: false
		},
		disableUpdateOnLayoutShift: {
			type: Boolean,
			required: false
		},
		prioritizePosition: {
			type: Boolean,
			required: false
		},
		reference: {
			type: null,
			required: false
		}
	},
	emits: [
		"escapeKeyDown",
		"pointerDownOutside",
		"focusOutside",
		"interactOutside",
		"closeAutoFocus"
	],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const slots = useSlots();
		const { dir } = useLocale();
		const appConfig = useAppConfig();
		const portalProps = usePortal(toRef(() => props.portal));
		const contentProps = useForwardPropsEmits(reactiveOmit(props, "sub", "items", "portal", "labelKey", "descriptionKey", "checkedIcon", "loadingIcon", "externalIcon", "class", "ui", "uiOverride"), emits);
		const getProxySlots = () => omit(slots, ["default"]);
		const [DefineItemTemplate, ReuseItemTemplate] = createReusableTemplate();
		const childrenIcon = computed(() => dir.value === "rtl" ? appConfig.ui.icons.chevronLeft : appConfig.ui.icons.chevronRight);
		const groups = computed(() => props.items?.length ? isArrayOfArray(props.items) ? props.items : [props.items] : []);
		return (_ctx, _push, _parent, _attrs) => {
			_push(`<!--[-->`);
			_push(ssrRenderComponent(unref(DefineItemTemplate), null, {
				default: withCtx(({ item, active, index }, _push, _parent, _scopeId) => {
					if (_push) ssrRenderSlot(_ctx.$slots, item.slot || "item", {
						item,
						index,
						ui: __props.ui
					}, () => {
						ssrRenderSlot(_ctx.$slots, item.slot ? `${item.slot}-leading` : "item-leading", {
							item,
							active,
							index,
							ui: __props.ui
						}, () => {
							if (item.loading) _push(ssrRenderComponent(_sfc_main$20, {
								name: __props.loadingIcon || unref(appConfig).ui.icons.loading,
								"data-slot": "itemLeadingIcon",
								class: __props.ui.itemLeadingIcon({
									class: [__props.uiOverride?.itemLeadingIcon, item.ui?.itemLeadingIcon],
									color: item?.color,
									loading: true
								})
							}, null, _parent, _scopeId));
							else if (item.icon) _push(ssrRenderComponent(_sfc_main$20, {
								name: item.icon,
								"data-slot": "itemLeadingIcon",
								class: __props.ui.itemLeadingIcon({
									class: [__props.uiOverride?.itemLeadingIcon, item.ui?.itemLeadingIcon],
									color: item?.color,
									active
								})
							}, null, _parent, _scopeId));
							else if (item.avatar) _push(ssrRenderComponent(_sfc_main$21, mergeProps({ size: item.ui?.itemLeadingAvatarSize || __props.uiOverride?.itemLeadingAvatarSize || __props.ui.itemLeadingAvatarSize() }, item.avatar, {
								"data-slot": "itemLeadingAvatar",
								class: __props.ui.itemLeadingAvatar({
									class: [__props.uiOverride?.itemLeadingAvatar, item.ui?.itemLeadingAvatar],
									active
								})
							}), null, _parent, _scopeId));
							else _push(`<!---->`);
						}, _push, _parent, _scopeId);
						if (unref(get)(item, props.labelKey) || !!slots[item.slot ? `${item.slot}-label` : "item-label"] || unref(get)(item, props.descriptionKey) || !!slots[item.slot ? `${item.slot}-description` : "item-description"]) {
							_push(`<span data-slot="itemWrapper" class="${ssrRenderClass(__props.ui.itemWrapper({ class: [__props.uiOverride?.itemWrapper, item.ui?.itemWrapper] }))}"${_scopeId}><span data-slot="itemLabel" class="${ssrRenderClass(__props.ui.itemLabel({
								class: [__props.uiOverride?.itemLabel, item.ui?.itemLabel],
								active
							}))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, item.slot ? `${item.slot}-label` : "item-label", {
								item,
								active,
								index
							}, () => {
								_push(`${ssrInterpolate(unref(get)(item, props.labelKey))}`);
							}, _push, _parent, _scopeId);
							if (item.target === "_blank" && __props.externalIcon !== false) _push(ssrRenderComponent(_sfc_main$20, {
								name: typeof __props.externalIcon === "string" ? __props.externalIcon : unref(appConfig).ui.icons.external,
								"data-slot": "itemLabelExternalIcon",
								class: __props.ui.itemLabelExternalIcon({
									class: [__props.uiOverride?.itemLabelExternalIcon, item.ui?.itemLabelExternalIcon],
									color: item?.color,
									active
								})
							}, null, _parent, _scopeId));
							else _push(`<!---->`);
							_push(`</span>`);
							if (unref(get)(item, props.descriptionKey) || !!slots[item.slot ? `${item.slot}-description` : "item-description"]) {
								_push(`<span data-slot="itemDescription" class="${ssrRenderClass(__props.ui.itemDescription({ class: [__props.uiOverride?.itemDescription, item.ui?.itemDescription] }))}"${_scopeId}>`);
								ssrRenderSlot(_ctx.$slots, item.slot ? `${item.slot}-description` : "item-description", {
									item,
									active,
									index
								}, () => {
									_push(`${ssrInterpolate(unref(get)(item, props.descriptionKey))}`);
								}, _push, _parent, _scopeId);
								_push(`</span>`);
							} else _push(`<!---->`);
							_push(`</span>`);
						} else _push(`<!---->`);
						_push(`<span data-slot="itemTrailing" class="${ssrRenderClass(__props.ui.itemTrailing({ class: [__props.uiOverride?.itemTrailing, item.ui?.itemTrailing] }))}"${_scopeId}>`);
						ssrRenderSlot(_ctx.$slots, item.slot ? `${item.slot}-trailing` : "item-trailing", {
							item,
							active,
							index,
							ui: __props.ui
						}, () => {
							if (item.children?.length) _push(ssrRenderComponent(_sfc_main$20, {
								name: childrenIcon.value,
								"data-slot": "itemTrailingIcon",
								class: __props.ui.itemTrailingIcon({
									class: [__props.uiOverride?.itemTrailingIcon, item.ui?.itemTrailingIcon],
									color: item?.color,
									active
								})
							}, null, _parent, _scopeId));
							else if (item.kbds?.length) {
								_push(`<span data-slot="itemTrailingKbds" class="${ssrRenderClass(__props.ui.itemTrailingKbds({ class: [__props.uiOverride?.itemTrailingKbds, item.ui?.itemTrailingKbds] }))}"${_scopeId}><!--[-->`);
								ssrRenderList(item.kbds, (kbd, kbdIndex) => {
									_push(ssrRenderComponent(_sfc_main$39, mergeProps({
										key: kbdIndex,
										size: item.ui?.itemTrailingKbdsSize || __props.uiOverride?.itemTrailingKbdsSize || __props.ui.itemTrailingKbdsSize()
									}, { ref_for: true }, typeof kbd === "string" ? { value: kbd } : kbd), null, _parent, _scopeId));
								});
								_push(`<!--]--></span>`);
							} else _push(`<!---->`);
						}, _push, _parent, _scopeId);
						_push(ssrRenderComponent(unref(ContextMenu).ItemIndicator, { "as-child": "" }, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(_sfc_main$20, {
									name: __props.checkedIcon || unref(appConfig).ui.icons.check,
									"data-slot": "itemTrailingIcon",
									class: __props.ui.itemTrailingIcon({
										class: [__props.uiOverride?.itemTrailingIcon, item.ui?.itemTrailingIcon],
										color: item?.color
									})
								}, null, _parent, _scopeId));
								else return [createVNode(_sfc_main$20, {
									name: __props.checkedIcon || unref(appConfig).ui.icons.check,
									"data-slot": "itemTrailingIcon",
									class: __props.ui.itemTrailingIcon({
										class: [__props.uiOverride?.itemTrailingIcon, item.ui?.itemTrailingIcon],
										color: item?.color
									})
								}, null, 8, ["name", "class"])];
							}),
							_: 2
						}, _parent, _scopeId));
						_push(`</span>`);
					}, _push, _parent, _scopeId);
					else return [renderSlot(_ctx.$slots, item.slot || "item", {
						item,
						index,
						ui: __props.ui
					}, () => [
						renderSlot(_ctx.$slots, item.slot ? `${item.slot}-leading` : "item-leading", {
							item,
							active,
							index,
							ui: __props.ui
						}, () => [item.loading ? (openBlock(), createBlock(_sfc_main$20, {
							key: 0,
							name: __props.loadingIcon || unref(appConfig).ui.icons.loading,
							"data-slot": "itemLeadingIcon",
							class: __props.ui.itemLeadingIcon({
								class: [__props.uiOverride?.itemLeadingIcon, item.ui?.itemLeadingIcon],
								color: item?.color,
								loading: true
							})
						}, null, 8, ["name", "class"])) : item.icon ? (openBlock(), createBlock(_sfc_main$20, {
							key: 1,
							name: item.icon,
							"data-slot": "itemLeadingIcon",
							class: __props.ui.itemLeadingIcon({
								class: [__props.uiOverride?.itemLeadingIcon, item.ui?.itemLeadingIcon],
								color: item?.color,
								active
							})
						}, null, 8, ["name", "class"])) : item.avatar ? (openBlock(), createBlock(_sfc_main$21, mergeProps({
							key: 2,
							size: item.ui?.itemLeadingAvatarSize || __props.uiOverride?.itemLeadingAvatarSize || __props.ui.itemLeadingAvatarSize()
						}, item.avatar, {
							"data-slot": "itemLeadingAvatar",
							class: __props.ui.itemLeadingAvatar({
								class: [__props.uiOverride?.itemLeadingAvatar, item.ui?.itemLeadingAvatar],
								active
							})
						}), null, 16, ["size", "class"])) : createCommentVNode("", true)]),
						unref(get)(item, props.labelKey) || !!slots[item.slot ? `${item.slot}-label` : "item-label"] || unref(get)(item, props.descriptionKey) || !!slots[item.slot ? `${item.slot}-description` : "item-description"] ? (openBlock(), createBlock("span", {
							key: 0,
							"data-slot": "itemWrapper",
							class: __props.ui.itemWrapper({ class: [__props.uiOverride?.itemWrapper, item.ui?.itemWrapper] })
						}, [createVNode("span", {
							"data-slot": "itemLabel",
							class: __props.ui.itemLabel({
								class: [__props.uiOverride?.itemLabel, item.ui?.itemLabel],
								active
							})
						}, [renderSlot(_ctx.$slots, item.slot ? `${item.slot}-label` : "item-label", {
							item,
							active,
							index
						}, () => [createTextVNode(toDisplayString(unref(get)(item, props.labelKey)), 1)]), item.target === "_blank" && __props.externalIcon !== false ? (openBlock(), createBlock(_sfc_main$20, {
							key: 0,
							name: typeof __props.externalIcon === "string" ? __props.externalIcon : unref(appConfig).ui.icons.external,
							"data-slot": "itemLabelExternalIcon",
							class: __props.ui.itemLabelExternalIcon({
								class: [__props.uiOverride?.itemLabelExternalIcon, item.ui?.itemLabelExternalIcon],
								color: item?.color,
								active
							})
						}, null, 8, ["name", "class"])) : createCommentVNode("", true)], 2), unref(get)(item, props.descriptionKey) || !!slots[item.slot ? `${item.slot}-description` : "item-description"] ? (openBlock(), createBlock("span", {
							key: 0,
							"data-slot": "itemDescription",
							class: __props.ui.itemDescription({ class: [__props.uiOverride?.itemDescription, item.ui?.itemDescription] })
						}, [renderSlot(_ctx.$slots, item.slot ? `${item.slot}-description` : "item-description", {
							item,
							active,
							index
						}, () => [createTextVNode(toDisplayString(unref(get)(item, props.descriptionKey)), 1)])], 2)) : createCommentVNode("", true)], 2)) : createCommentVNode("", true),
						createVNode("span", {
							"data-slot": "itemTrailing",
							class: __props.ui.itemTrailing({ class: [__props.uiOverride?.itemTrailing, item.ui?.itemTrailing] })
						}, [renderSlot(_ctx.$slots, item.slot ? `${item.slot}-trailing` : "item-trailing", {
							item,
							active,
							index,
							ui: __props.ui
						}, () => [item.children?.length ? (openBlock(), createBlock(_sfc_main$20, {
							key: 0,
							name: childrenIcon.value,
							"data-slot": "itemTrailingIcon",
							class: __props.ui.itemTrailingIcon({
								class: [__props.uiOverride?.itemTrailingIcon, item.ui?.itemTrailingIcon],
								color: item?.color,
								active
							})
						}, null, 8, ["name", "class"])) : item.kbds?.length ? (openBlock(), createBlock("span", {
							key: 1,
							"data-slot": "itemTrailingKbds",
							class: __props.ui.itemTrailingKbds({ class: [__props.uiOverride?.itemTrailingKbds, item.ui?.itemTrailingKbds] })
						}, [(openBlock(true), createBlock(Fragment, null, renderList(item.kbds, (kbd, kbdIndex) => {
							return openBlock(), createBlock(_sfc_main$39, mergeProps({
								key: kbdIndex,
								size: item.ui?.itemTrailingKbdsSize || __props.uiOverride?.itemTrailingKbdsSize || __props.ui.itemTrailingKbdsSize()
							}, { ref_for: true }, typeof kbd === "string" ? { value: kbd } : kbd), null, 16, ["size"]);
						}), 128))], 2)) : createCommentVNode("", true)]), createVNode(unref(ContextMenu).ItemIndicator, { "as-child": "" }, {
							default: withCtx(() => [createVNode(_sfc_main$20, {
								name: __props.checkedIcon || unref(appConfig).ui.icons.check,
								"data-slot": "itemTrailingIcon",
								class: __props.ui.itemTrailingIcon({
									class: [__props.uiOverride?.itemTrailingIcon, item.ui?.itemTrailingIcon],
									color: item?.color
								})
							}, null, 8, ["name", "class"])]),
							_: 2
						}, 1024)], 2)
					])];
				}),
				_: 3
			}, _parent));
			_push(ssrRenderComponent(unref(ContextMenu).Portal, unref(portalProps), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(ssrRenderComponent(unref(FieldGroupReset), null, {
						default: withCtx((_, _push, _parent, _scopeId) => {
							if (_push) ssrRenderVNode(_push, createVNode(resolveDynamicComponent(__props.sub ? unref(ContextMenu).SubContent : unref(ContextMenu).Content), mergeProps({
								"data-slot": "content",
								class: __props.ui.content({ class: [__props.uiOverride?.content, props.class] })
							}, unref(contentProps)), {
								default: withCtx((_, _push, _parent, _scopeId) => {
									if (_push) {
										ssrRenderSlot(_ctx.$slots, "content-top", { sub: __props.sub ?? false }, null, _push, _parent, _scopeId);
										_push(`<div role="presentation" data-slot="viewport" class="${ssrRenderClass(__props.ui.viewport({ class: __props.uiOverride?.viewport }))}"${_scopeId}><!--[-->`);
										ssrRenderList(groups.value, (group, groupIndex) => {
											_push(ssrRenderComponent(unref(ContextMenu).Group, {
												key: `group-${groupIndex}`,
												"data-slot": "group",
												class: __props.ui.group({ class: __props.uiOverride?.group })
											}, {
												default: withCtx((_, _push, _parent, _scopeId) => {
													if (_push) {
														_push(`<!--[-->`);
														ssrRenderList(group, (item, index) => {
															_push(`<!--[-->`);
															if (item.type === "label") _push(ssrRenderComponent(unref(ContextMenu).Label, {
																"data-slot": "label",
																class: __props.ui.label({ class: [
																	__props.uiOverride?.label,
																	item.ui?.label,
																	item.class
																] })
															}, {
																default: withCtx((_, _push, _parent, _scopeId) => {
																	if (_push) _push(ssrRenderComponent(unref(ReuseItemTemplate), {
																		item,
																		index
																	}, null, _parent, _scopeId));
																	else return [createVNode(unref(ReuseItemTemplate), {
																		item,
																		index
																	}, null, 8, ["item", "index"])];
																}),
																_: 2
															}, _parent, _scopeId));
															else if (item.type === "separator") _push(ssrRenderComponent(unref(ContextMenu).Separator, {
																"data-slot": "separator",
																class: __props.ui.separator({ class: [
																	__props.uiOverride?.separator,
																	item.ui?.separator,
																	item.class
																] })
															}, null, _parent, _scopeId));
															else if (item?.children?.length) _push(ssrRenderComponent(unref(ContextMenu).Sub, {
																open: item.open,
																"default-open": item.defaultOpen
															}, {
																default: withCtx((_, _push, _parent, _scopeId) => {
																	if (_push) {
																		_push(ssrRenderComponent(unref(ContextMenu).SubTrigger, {
																			as: "button",
																			type: "button",
																			disabled: item.disabled,
																			"text-value": unref(get)(item, props.labelKey),
																			"data-slot": "item",
																			class: __props.ui.item({
																				class: [
																					__props.uiOverride?.item,
																					item.ui?.item,
																					item.class
																				],
																				color: item?.color
																			})
																		}, {
																			default: withCtx((_, _push, _parent, _scopeId) => {
																				if (_push) _push(ssrRenderComponent(unref(ReuseItemTemplate), {
																					item,
																					index
																				}, null, _parent, _scopeId));
																				else return [createVNode(unref(ReuseItemTemplate), {
																					item,
																					index
																				}, null, 8, ["item", "index"])];
																			}),
																			_: 2
																		}, _parent, _scopeId));
																		_push(ssrRenderComponent(_sfc_main$3, mergeProps({
																			sub: "",
																			class: item.ui?.content,
																			ui: __props.ui,
																			"ui-override": __props.uiOverride,
																			portal: __props.portal,
																			items: item.children,
																			"align-offset": -4,
																			"label-key": __props.labelKey,
																			"description-key": __props.descriptionKey,
																			"checked-icon": __props.checkedIcon,
																			"loading-icon": __props.loadingIcon,
																			"external-icon": __props.externalIcon
																		}, { ref_for: true }, item.content), createSlots({ _: 2 }, [renderList(getProxySlots(), (_, name) => {
																			return {
																				name,
																				fn: withCtx((slotData, _push, _parent, _scopeId) => {
																					if (_push) ssrRenderSlot(_ctx.$slots, name, mergeProps({ ref_for: true }, slotData), null, _push, _parent, _scopeId);
																					else return [renderSlot(_ctx.$slots, name, mergeProps({ ref_for: true }, slotData))];
																				})
																			};
																		})]), _parent, _scopeId));
																	} else return [createVNode(unref(ContextMenu).SubTrigger, {
																		as: "button",
																		type: "button",
																		disabled: item.disabled,
																		"text-value": unref(get)(item, props.labelKey),
																		"data-slot": "item",
																		class: __props.ui.item({
																			class: [
																				__props.uiOverride?.item,
																				item.ui?.item,
																				item.class
																			],
																			color: item?.color
																		})
																	}, {
																		default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
																			item,
																			index
																		}, null, 8, ["item", "index"])]),
																		_: 2
																	}, 1032, [
																		"disabled",
																		"text-value",
																		"class"
																	]), createVNode(_sfc_main$3, mergeProps({
																		sub: "",
																		class: item.ui?.content,
																		ui: __props.ui,
																		"ui-override": __props.uiOverride,
																		portal: __props.portal,
																		items: item.children,
																		"align-offset": -4,
																		"label-key": __props.labelKey,
																		"description-key": __props.descriptionKey,
																		"checked-icon": __props.checkedIcon,
																		"loading-icon": __props.loadingIcon,
																		"external-icon": __props.externalIcon
																	}, { ref_for: true }, item.content), createSlots({ _: 2 }, [renderList(getProxySlots(), (_, name) => {
																		return {
																			name,
																			fn: withCtx((slotData) => [renderSlot(_ctx.$slots, name, mergeProps({ ref_for: true }, slotData))])
																		};
																	})]), 1040, [
																		"class",
																		"ui",
																		"ui-override",
																		"portal",
																		"items",
																		"label-key",
																		"description-key",
																		"checked-icon",
																		"loading-icon",
																		"external-icon"
																	])];
																}),
																_: 2
															}, _parent, _scopeId));
															else if (item.type === "checkbox") _push(ssrRenderComponent(unref(ContextMenu).CheckboxItem, {
																"model-value": item.checked,
																disabled: item.disabled,
																"text-value": unref(get)(item, props.labelKey),
																"data-slot": "item",
																class: __props.ui.item({
																	class: [
																		__props.uiOverride?.item,
																		item.ui?.item,
																		item.class
																	],
																	color: item?.color
																}),
																"onUpdate:modelValue": item.onUpdateChecked,
																onSelect: item.onSelect
															}, {
																default: withCtx((_, _push, _parent, _scopeId) => {
																	if (_push) _push(ssrRenderComponent(unref(ReuseItemTemplate), {
																		item,
																		index
																	}, null, _parent, _scopeId));
																	else return [createVNode(unref(ReuseItemTemplate), {
																		item,
																		index
																	}, null, 8, ["item", "index"])];
																}),
																_: 2
															}, _parent, _scopeId));
															else _push(ssrRenderComponent(_sfc_main$23, mergeProps({ ref_for: true }, unref(pickLinkProps)(item), { custom: "" }), {
																default: withCtx(({ active, ...slotProps }, _push, _parent, _scopeId) => {
																	if (_push) _push(ssrRenderComponent(unref(ContextMenu).Item, {
																		"as-child": "",
																		disabled: item.disabled,
																		"text-value": unref(get)(item, props.labelKey),
																		onSelect: item.onSelect
																	}, {
																		default: withCtx((_, _push, _parent, _scopeId) => {
																			if (_push) _push(ssrRenderComponent(_sfc_main$24, mergeProps({ ref_for: true }, slotProps, {
																				"data-slot": "item",
																				class: __props.ui.item({
																					class: [
																						__props.uiOverride?.item,
																						item.ui?.item,
																						item.class
																					],
																					active,
																					color: item?.color
																				})
																			}), {
																				default: withCtx((_, _push, _parent, _scopeId) => {
																					if (_push) _push(ssrRenderComponent(unref(ReuseItemTemplate), {
																						item,
																						active,
																						index
																					}, null, _parent, _scopeId));
																					else return [createVNode(unref(ReuseItemTemplate), {
																						item,
																						active,
																						index
																					}, null, 8, [
																						"item",
																						"active",
																						"index"
																					])];
																				}),
																				_: 2
																			}, _parent, _scopeId));
																			else return [createVNode(_sfc_main$24, mergeProps({ ref_for: true }, slotProps, {
																				"data-slot": "item",
																				class: __props.ui.item({
																					class: [
																						__props.uiOverride?.item,
																						item.ui?.item,
																						item.class
																					],
																					active,
																					color: item?.color
																				})
																			}), {
																				default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
																					item,
																					active,
																					index
																				}, null, 8, [
																					"item",
																					"active",
																					"index"
																				])]),
																				_: 2
																			}, 1040, ["class"])];
																		}),
																		_: 2
																	}, _parent, _scopeId));
																	else return [createVNode(unref(ContextMenu).Item, {
																		"as-child": "",
																		disabled: item.disabled,
																		"text-value": unref(get)(item, props.labelKey),
																		onSelect: item.onSelect
																	}, {
																		default: withCtx(() => [createVNode(_sfc_main$24, mergeProps({ ref_for: true }, slotProps, {
																			"data-slot": "item",
																			class: __props.ui.item({
																				class: [
																					__props.uiOverride?.item,
																					item.ui?.item,
																					item.class
																				],
																				active,
																				color: item?.color
																			})
																		}), {
																			default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
																				item,
																				active,
																				index
																			}, null, 8, [
																				"item",
																				"active",
																				"index"
																			])]),
																			_: 2
																		}, 1040, ["class"])]),
																		_: 2
																	}, 1032, [
																		"disabled",
																		"text-value",
																		"onSelect"
																	])];
																}),
																_: 2
															}, _parent, _scopeId));
															_push(`<!--]-->`);
														});
														_push(`<!--]-->`);
													} else return [(openBlock(true), createBlock(Fragment, null, renderList(group, (item, index) => {
														return openBlock(), createBlock(Fragment, { key: `group-${groupIndex}-${index}` }, [item.type === "label" ? (openBlock(), createBlock(unref(ContextMenu).Label, {
															key: 0,
															"data-slot": "label",
															class: __props.ui.label({ class: [
																__props.uiOverride?.label,
																item.ui?.label,
																item.class
															] })
														}, {
															default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
																item,
																index
															}, null, 8, ["item", "index"])]),
															_: 2
														}, 1032, ["class"])) : item.type === "separator" ? (openBlock(), createBlock(unref(ContextMenu).Separator, {
															key: 1,
															"data-slot": "separator",
															class: __props.ui.separator({ class: [
																__props.uiOverride?.separator,
																item.ui?.separator,
																item.class
															] })
														}, null, 8, ["class"])) : item?.children?.length ? (openBlock(), createBlock(unref(ContextMenu).Sub, {
															key: 2,
															open: item.open,
															"default-open": item.defaultOpen
														}, {
															default: withCtx(() => [createVNode(unref(ContextMenu).SubTrigger, {
																as: "button",
																type: "button",
																disabled: item.disabled,
																"text-value": unref(get)(item, props.labelKey),
																"data-slot": "item",
																class: __props.ui.item({
																	class: [
																		__props.uiOverride?.item,
																		item.ui?.item,
																		item.class
																	],
																	color: item?.color
																})
															}, {
																default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
																	item,
																	index
																}, null, 8, ["item", "index"])]),
																_: 2
															}, 1032, [
																"disabled",
																"text-value",
																"class"
															]), createVNode(_sfc_main$3, mergeProps({
																sub: "",
																class: item.ui?.content,
																ui: __props.ui,
																"ui-override": __props.uiOverride,
																portal: __props.portal,
																items: item.children,
																"align-offset": -4,
																"label-key": __props.labelKey,
																"description-key": __props.descriptionKey,
																"checked-icon": __props.checkedIcon,
																"loading-icon": __props.loadingIcon,
																"external-icon": __props.externalIcon
															}, { ref_for: true }, item.content), createSlots({ _: 2 }, [renderList(getProxySlots(), (_, name) => {
																return {
																	name,
																	fn: withCtx((slotData) => [renderSlot(_ctx.$slots, name, mergeProps({ ref_for: true }, slotData))])
																};
															})]), 1040, [
																"class",
																"ui",
																"ui-override",
																"portal",
																"items",
																"label-key",
																"description-key",
																"checked-icon",
																"loading-icon",
																"external-icon"
															])]),
															_: 2
														}, 1032, ["open", "default-open"])) : item.type === "checkbox" ? (openBlock(), createBlock(unref(ContextMenu).CheckboxItem, {
															key: 3,
															"model-value": item.checked,
															disabled: item.disabled,
															"text-value": unref(get)(item, props.labelKey),
															"data-slot": "item",
															class: __props.ui.item({
																class: [
																	__props.uiOverride?.item,
																	item.ui?.item,
																	item.class
																],
																color: item?.color
															}),
															"onUpdate:modelValue": item.onUpdateChecked,
															onSelect: item.onSelect
														}, {
															default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
																item,
																index
															}, null, 8, ["item", "index"])]),
															_: 2
														}, 1032, [
															"model-value",
															"disabled",
															"text-value",
															"class",
															"onUpdate:modelValue",
															"onSelect"
														])) : (openBlock(), createBlock(_sfc_main$23, mergeProps({
															key: 4,
															ref_for: true
														}, unref(pickLinkProps)(item), { custom: "" }), {
															default: withCtx(({ active, ...slotProps }) => [createVNode(unref(ContextMenu).Item, {
																"as-child": "",
																disabled: item.disabled,
																"text-value": unref(get)(item, props.labelKey),
																onSelect: item.onSelect
															}, {
																default: withCtx(() => [createVNode(_sfc_main$24, mergeProps({ ref_for: true }, slotProps, {
																	"data-slot": "item",
																	class: __props.ui.item({
																		class: [
																			__props.uiOverride?.item,
																			item.ui?.item,
																			item.class
																		],
																		active,
																		color: item?.color
																	})
																}), {
																	default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
																		item,
																		active,
																		index
																	}, null, 8, [
																		"item",
																		"active",
																		"index"
																	])]),
																	_: 2
																}, 1040, ["class"])]),
																_: 2
															}, 1032, [
																"disabled",
																"text-value",
																"onSelect"
															])]),
															_: 2
														}, 1040))], 64);
													}), 128))];
												}),
												_: 2
											}, _parent, _scopeId));
										});
										_push(`<!--]--></div>`);
										ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
										ssrRenderSlot(_ctx.$slots, "content-bottom", { sub: __props.sub ?? false }, null, _push, _parent, _scopeId);
									} else return [
										renderSlot(_ctx.$slots, "content-top", { sub: __props.sub ?? false }),
										createVNode("div", {
											role: "presentation",
											"data-slot": "viewport",
											class: __props.ui.viewport({ class: __props.uiOverride?.viewport })
										}, [(openBlock(true), createBlock(Fragment, null, renderList(groups.value, (group, groupIndex) => {
											return openBlock(), createBlock(unref(ContextMenu).Group, {
												key: `group-${groupIndex}`,
												"data-slot": "group",
												class: __props.ui.group({ class: __props.uiOverride?.group })
											}, {
												default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(group, (item, index) => {
													return openBlock(), createBlock(Fragment, { key: `group-${groupIndex}-${index}` }, [item.type === "label" ? (openBlock(), createBlock(unref(ContextMenu).Label, {
														key: 0,
														"data-slot": "label",
														class: __props.ui.label({ class: [
															__props.uiOverride?.label,
															item.ui?.label,
															item.class
														] })
													}, {
														default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
															item,
															index
														}, null, 8, ["item", "index"])]),
														_: 2
													}, 1032, ["class"])) : item.type === "separator" ? (openBlock(), createBlock(unref(ContextMenu).Separator, {
														key: 1,
														"data-slot": "separator",
														class: __props.ui.separator({ class: [
															__props.uiOverride?.separator,
															item.ui?.separator,
															item.class
														] })
													}, null, 8, ["class"])) : item?.children?.length ? (openBlock(), createBlock(unref(ContextMenu).Sub, {
														key: 2,
														open: item.open,
														"default-open": item.defaultOpen
													}, {
														default: withCtx(() => [createVNode(unref(ContextMenu).SubTrigger, {
															as: "button",
															type: "button",
															disabled: item.disabled,
															"text-value": unref(get)(item, props.labelKey),
															"data-slot": "item",
															class: __props.ui.item({
																class: [
																	__props.uiOverride?.item,
																	item.ui?.item,
																	item.class
																],
																color: item?.color
															})
														}, {
															default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
																item,
																index
															}, null, 8, ["item", "index"])]),
															_: 2
														}, 1032, [
															"disabled",
															"text-value",
															"class"
														]), createVNode(_sfc_main$3, mergeProps({
															sub: "",
															class: item.ui?.content,
															ui: __props.ui,
															"ui-override": __props.uiOverride,
															portal: __props.portal,
															items: item.children,
															"align-offset": -4,
															"label-key": __props.labelKey,
															"description-key": __props.descriptionKey,
															"checked-icon": __props.checkedIcon,
															"loading-icon": __props.loadingIcon,
															"external-icon": __props.externalIcon
														}, { ref_for: true }, item.content), createSlots({ _: 2 }, [renderList(getProxySlots(), (_, name) => {
															return {
																name,
																fn: withCtx((slotData) => [renderSlot(_ctx.$slots, name, mergeProps({ ref_for: true }, slotData))])
															};
														})]), 1040, [
															"class",
															"ui",
															"ui-override",
															"portal",
															"items",
															"label-key",
															"description-key",
															"checked-icon",
															"loading-icon",
															"external-icon"
														])]),
														_: 2
													}, 1032, ["open", "default-open"])) : item.type === "checkbox" ? (openBlock(), createBlock(unref(ContextMenu).CheckboxItem, {
														key: 3,
														"model-value": item.checked,
														disabled: item.disabled,
														"text-value": unref(get)(item, props.labelKey),
														"data-slot": "item",
														class: __props.ui.item({
															class: [
																__props.uiOverride?.item,
																item.ui?.item,
																item.class
															],
															color: item?.color
														}),
														"onUpdate:modelValue": item.onUpdateChecked,
														onSelect: item.onSelect
													}, {
														default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
															item,
															index
														}, null, 8, ["item", "index"])]),
														_: 2
													}, 1032, [
														"model-value",
														"disabled",
														"text-value",
														"class",
														"onUpdate:modelValue",
														"onSelect"
													])) : (openBlock(), createBlock(_sfc_main$23, mergeProps({
														key: 4,
														ref_for: true
													}, unref(pickLinkProps)(item), { custom: "" }), {
														default: withCtx(({ active, ...slotProps }) => [createVNode(unref(ContextMenu).Item, {
															"as-child": "",
															disabled: item.disabled,
															"text-value": unref(get)(item, props.labelKey),
															onSelect: item.onSelect
														}, {
															default: withCtx(() => [createVNode(_sfc_main$24, mergeProps({ ref_for: true }, slotProps, {
																"data-slot": "item",
																class: __props.ui.item({
																	class: [
																		__props.uiOverride?.item,
																		item.ui?.item,
																		item.class
																	],
																	active,
																	color: item?.color
																})
															}), {
																default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
																	item,
																	active,
																	index
																}, null, 8, [
																	"item",
																	"active",
																	"index"
																])]),
																_: 2
															}, 1040, ["class"])]),
															_: 2
														}, 1032, [
															"disabled",
															"text-value",
															"onSelect"
														])]),
														_: 2
													}, 1040))], 64);
												}), 128))]),
												_: 2
											}, 1032, ["class"]);
										}), 128))], 2),
										renderSlot(_ctx.$slots, "default"),
										renderSlot(_ctx.$slots, "content-bottom", { sub: __props.sub ?? false })
									];
								}),
								_: 3
							}), _parent, _scopeId);
							else return [(openBlock(), createBlock(resolveDynamicComponent(__props.sub ? unref(ContextMenu).SubContent : unref(ContextMenu).Content), mergeProps({
								"data-slot": "content",
								class: __props.ui.content({ class: [__props.uiOverride?.content, props.class] })
							}, unref(contentProps)), {
								default: withCtx(() => [
									renderSlot(_ctx.$slots, "content-top", { sub: __props.sub ?? false }),
									createVNode("div", {
										role: "presentation",
										"data-slot": "viewport",
										class: __props.ui.viewport({ class: __props.uiOverride?.viewport })
									}, [(openBlock(true), createBlock(Fragment, null, renderList(groups.value, (group, groupIndex) => {
										return openBlock(), createBlock(unref(ContextMenu).Group, {
											key: `group-${groupIndex}`,
											"data-slot": "group",
											class: __props.ui.group({ class: __props.uiOverride?.group })
										}, {
											default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(group, (item, index) => {
												return openBlock(), createBlock(Fragment, { key: `group-${groupIndex}-${index}` }, [item.type === "label" ? (openBlock(), createBlock(unref(ContextMenu).Label, {
													key: 0,
													"data-slot": "label",
													class: __props.ui.label({ class: [
														__props.uiOverride?.label,
														item.ui?.label,
														item.class
													] })
												}, {
													default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
														item,
														index
													}, null, 8, ["item", "index"])]),
													_: 2
												}, 1032, ["class"])) : item.type === "separator" ? (openBlock(), createBlock(unref(ContextMenu).Separator, {
													key: 1,
													"data-slot": "separator",
													class: __props.ui.separator({ class: [
														__props.uiOverride?.separator,
														item.ui?.separator,
														item.class
													] })
												}, null, 8, ["class"])) : item?.children?.length ? (openBlock(), createBlock(unref(ContextMenu).Sub, {
													key: 2,
													open: item.open,
													"default-open": item.defaultOpen
												}, {
													default: withCtx(() => [createVNode(unref(ContextMenu).SubTrigger, {
														as: "button",
														type: "button",
														disabled: item.disabled,
														"text-value": unref(get)(item, props.labelKey),
														"data-slot": "item",
														class: __props.ui.item({
															class: [
																__props.uiOverride?.item,
																item.ui?.item,
																item.class
															],
															color: item?.color
														})
													}, {
														default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
															item,
															index
														}, null, 8, ["item", "index"])]),
														_: 2
													}, 1032, [
														"disabled",
														"text-value",
														"class"
													]), createVNode(_sfc_main$3, mergeProps({
														sub: "",
														class: item.ui?.content,
														ui: __props.ui,
														"ui-override": __props.uiOverride,
														portal: __props.portal,
														items: item.children,
														"align-offset": -4,
														"label-key": __props.labelKey,
														"description-key": __props.descriptionKey,
														"checked-icon": __props.checkedIcon,
														"loading-icon": __props.loadingIcon,
														"external-icon": __props.externalIcon
													}, { ref_for: true }, item.content), createSlots({ _: 2 }, [renderList(getProxySlots(), (_, name) => {
														return {
															name,
															fn: withCtx((slotData) => [renderSlot(_ctx.$slots, name, mergeProps({ ref_for: true }, slotData))])
														};
													})]), 1040, [
														"class",
														"ui",
														"ui-override",
														"portal",
														"items",
														"label-key",
														"description-key",
														"checked-icon",
														"loading-icon",
														"external-icon"
													])]),
													_: 2
												}, 1032, ["open", "default-open"])) : item.type === "checkbox" ? (openBlock(), createBlock(unref(ContextMenu).CheckboxItem, {
													key: 3,
													"model-value": item.checked,
													disabled: item.disabled,
													"text-value": unref(get)(item, props.labelKey),
													"data-slot": "item",
													class: __props.ui.item({
														class: [
															__props.uiOverride?.item,
															item.ui?.item,
															item.class
														],
														color: item?.color
													}),
													"onUpdate:modelValue": item.onUpdateChecked,
													onSelect: item.onSelect
												}, {
													default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
														item,
														index
													}, null, 8, ["item", "index"])]),
													_: 2
												}, 1032, [
													"model-value",
													"disabled",
													"text-value",
													"class",
													"onUpdate:modelValue",
													"onSelect"
												])) : (openBlock(), createBlock(_sfc_main$23, mergeProps({
													key: 4,
													ref_for: true
												}, unref(pickLinkProps)(item), { custom: "" }), {
													default: withCtx(({ active, ...slotProps }) => [createVNode(unref(ContextMenu).Item, {
														"as-child": "",
														disabled: item.disabled,
														"text-value": unref(get)(item, props.labelKey),
														onSelect: item.onSelect
													}, {
														default: withCtx(() => [createVNode(_sfc_main$24, mergeProps({ ref_for: true }, slotProps, {
															"data-slot": "item",
															class: __props.ui.item({
																class: [
																	__props.uiOverride?.item,
																	item.ui?.item,
																	item.class
																],
																active,
																color: item?.color
															})
														}), {
															default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
																item,
																active,
																index
															}, null, 8, [
																"item",
																"active",
																"index"
															])]),
															_: 2
														}, 1040, ["class"])]),
														_: 2
													}, 1032, [
														"disabled",
														"text-value",
														"onSelect"
													])]),
													_: 2
												}, 1040))], 64);
											}), 128))]),
											_: 2
										}, 1032, ["class"]);
									}), 128))], 2),
									renderSlot(_ctx.$slots, "default"),
									renderSlot(_ctx.$slots, "content-bottom", { sub: __props.sub ?? false })
								]),
								_: 3
							}, 16, ["class"]))];
						}),
						_: 3
					}, _parent, _scopeId));
					else return [createVNode(unref(FieldGroupReset), null, {
						default: withCtx(() => [(openBlock(), createBlock(resolveDynamicComponent(__props.sub ? unref(ContextMenu).SubContent : unref(ContextMenu).Content), mergeProps({
							"data-slot": "content",
							class: __props.ui.content({ class: [__props.uiOverride?.content, props.class] })
						}, unref(contentProps)), {
							default: withCtx(() => [
								renderSlot(_ctx.$slots, "content-top", { sub: __props.sub ?? false }),
								createVNode("div", {
									role: "presentation",
									"data-slot": "viewport",
									class: __props.ui.viewport({ class: __props.uiOverride?.viewport })
								}, [(openBlock(true), createBlock(Fragment, null, renderList(groups.value, (group, groupIndex) => {
									return openBlock(), createBlock(unref(ContextMenu).Group, {
										key: `group-${groupIndex}`,
										"data-slot": "group",
										class: __props.ui.group({ class: __props.uiOverride?.group })
									}, {
										default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(group, (item, index) => {
											return openBlock(), createBlock(Fragment, { key: `group-${groupIndex}-${index}` }, [item.type === "label" ? (openBlock(), createBlock(unref(ContextMenu).Label, {
												key: 0,
												"data-slot": "label",
												class: __props.ui.label({ class: [
													__props.uiOverride?.label,
													item.ui?.label,
													item.class
												] })
											}, {
												default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
													item,
													index
												}, null, 8, ["item", "index"])]),
												_: 2
											}, 1032, ["class"])) : item.type === "separator" ? (openBlock(), createBlock(unref(ContextMenu).Separator, {
												key: 1,
												"data-slot": "separator",
												class: __props.ui.separator({ class: [
													__props.uiOverride?.separator,
													item.ui?.separator,
													item.class
												] })
											}, null, 8, ["class"])) : item?.children?.length ? (openBlock(), createBlock(unref(ContextMenu).Sub, {
												key: 2,
												open: item.open,
												"default-open": item.defaultOpen
											}, {
												default: withCtx(() => [createVNode(unref(ContextMenu).SubTrigger, {
													as: "button",
													type: "button",
													disabled: item.disabled,
													"text-value": unref(get)(item, props.labelKey),
													"data-slot": "item",
													class: __props.ui.item({
														class: [
															__props.uiOverride?.item,
															item.ui?.item,
															item.class
														],
														color: item?.color
													})
												}, {
													default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
														item,
														index
													}, null, 8, ["item", "index"])]),
													_: 2
												}, 1032, [
													"disabled",
													"text-value",
													"class"
												]), createVNode(_sfc_main$3, mergeProps({
													sub: "",
													class: item.ui?.content,
													ui: __props.ui,
													"ui-override": __props.uiOverride,
													portal: __props.portal,
													items: item.children,
													"align-offset": -4,
													"label-key": __props.labelKey,
													"description-key": __props.descriptionKey,
													"checked-icon": __props.checkedIcon,
													"loading-icon": __props.loadingIcon,
													"external-icon": __props.externalIcon
												}, { ref_for: true }, item.content), createSlots({ _: 2 }, [renderList(getProxySlots(), (_, name) => {
													return {
														name,
														fn: withCtx((slotData) => [renderSlot(_ctx.$slots, name, mergeProps({ ref_for: true }, slotData))])
													};
												})]), 1040, [
													"class",
													"ui",
													"ui-override",
													"portal",
													"items",
													"label-key",
													"description-key",
													"checked-icon",
													"loading-icon",
													"external-icon"
												])]),
												_: 2
											}, 1032, ["open", "default-open"])) : item.type === "checkbox" ? (openBlock(), createBlock(unref(ContextMenu).CheckboxItem, {
												key: 3,
												"model-value": item.checked,
												disabled: item.disabled,
												"text-value": unref(get)(item, props.labelKey),
												"data-slot": "item",
												class: __props.ui.item({
													class: [
														__props.uiOverride?.item,
														item.ui?.item,
														item.class
													],
													color: item?.color
												}),
												"onUpdate:modelValue": item.onUpdateChecked,
												onSelect: item.onSelect
											}, {
												default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
													item,
													index
												}, null, 8, ["item", "index"])]),
												_: 2
											}, 1032, [
												"model-value",
												"disabled",
												"text-value",
												"class",
												"onUpdate:modelValue",
												"onSelect"
											])) : (openBlock(), createBlock(_sfc_main$23, mergeProps({
												key: 4,
												ref_for: true
											}, unref(pickLinkProps)(item), { custom: "" }), {
												default: withCtx(({ active, ...slotProps }) => [createVNode(unref(ContextMenu).Item, {
													"as-child": "",
													disabled: item.disabled,
													"text-value": unref(get)(item, props.labelKey),
													onSelect: item.onSelect
												}, {
													default: withCtx(() => [createVNode(_sfc_main$24, mergeProps({ ref_for: true }, slotProps, {
														"data-slot": "item",
														class: __props.ui.item({
															class: [
																__props.uiOverride?.item,
																item.ui?.item,
																item.class
															],
															active,
															color: item?.color
														})
													}), {
														default: withCtx(() => [createVNode(unref(ReuseItemTemplate), {
															item,
															active,
															index
														}, null, 8, [
															"item",
															"active",
															"index"
														])]),
														_: 2
													}, 1040, ["class"])]),
													_: 2
												}, 1032, [
													"disabled",
													"text-value",
													"onSelect"
												])]),
												_: 2
											}, 1040))], 64);
										}), 128))]),
										_: 2
									}, 1032, ["class"]);
								}), 128))], 2),
								renderSlot(_ctx.$slots, "default"),
								renderSlot(_ctx.$slots, "content-bottom", { sub: __props.sub ?? false })
							]),
							_: 3
						}, 16, ["class"]))]),
						_: 3
					})];
				}),
				_: 3
			}, _parent));
			_push(`<!--]-->`);
		};
	}
};
var _sfc_setup$10 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/ContextMenuContent.vue");
	return _sfc_setup$10 ? _sfc_setup$10(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/context-menu.ts
var context_menu_default = {
	"slots": {
		"content": "min-w-32 bg-default shadow-lg rounded-md ring ring-default overflow-hidden data-[state=open]:animate-[scale-in_100ms_ease-out] data-[state=closed]:animate-[scale-out_100ms_ease-in] origin-(--reka-context-menu-content-transform-origin) flex flex-col",
		"viewport": "relative divide-y divide-default scroll-py-1 overflow-y-auto flex-1",
		"group": "p-1 isolate",
		"label": "w-full flex items-center font-semibold text-highlighted",
		"separator": "-mx-1 my-1 h-px bg-border",
		"item": "group relative w-full flex items-start select-none outline-none before:absolute before:z-[-1] before:inset-px before:rounded-md data-disabled:cursor-not-allowed data-disabled:opacity-75",
		"itemLeadingIcon": "shrink-0",
		"itemLeadingAvatar": "shrink-0",
		"itemLeadingAvatarSize": "",
		"itemTrailing": "ms-auto inline-flex gap-1.5 items-center",
		"itemTrailingIcon": "shrink-0",
		"itemTrailingKbds": "hidden lg:inline-flex items-center shrink-0",
		"itemTrailingKbdsSize": "",
		"itemWrapper": "flex-1 flex flex-col text-start min-w-0",
		"itemLabel": "truncate",
		"itemDescription": "truncate text-muted",
		"itemLabelExternalIcon": "inline-block size-3 align-top text-dimmed"
	},
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
		"active": {
			"true": {
				"item": "text-highlighted before:bg-elevated",
				"itemLeadingIcon": "text-default"
			},
			"false": {
				"item": ["text-default data-highlighted:text-highlighted data-[state=open]:text-highlighted data-highlighted:before:bg-elevated/50 data-[state=open]:before:bg-elevated/50", "transition-colors before:transition-colors"],
				"itemLeadingIcon": ["text-dimmed group-data-highlighted:text-default group-data-[state=open]:text-default", "transition-colors"]
			}
		},
		"loading": { "true": { "itemLeadingIcon": "animate-spin" } },
		"size": {
			"xs": {
				"label": "p-1 text-xs gap-1",
				"item": "p-1 text-xs gap-1",
				"itemLeadingIcon": "size-4",
				"itemLeadingAvatarSize": "3xs",
				"itemTrailingIcon": "size-4",
				"itemTrailingKbds": "gap-0.5",
				"itemTrailingKbdsSize": "sm"
			},
			"sm": {
				"label": "p-1.5 text-xs gap-1.5",
				"item": "p-1.5 text-xs gap-1.5",
				"itemLeadingIcon": "size-4",
				"itemLeadingAvatarSize": "3xs",
				"itemTrailingIcon": "size-4",
				"itemTrailingKbds": "gap-0.5",
				"itemTrailingKbdsSize": "sm"
			},
			"md": {
				"label": "p-1.5 text-sm gap-1.5",
				"item": "p-1.5 text-sm gap-1.5",
				"itemLeadingIcon": "size-5",
				"itemLeadingAvatarSize": "2xs",
				"itemTrailingIcon": "size-5",
				"itemTrailingKbds": "gap-0.5",
				"itemTrailingKbdsSize": "md"
			},
			"lg": {
				"label": "p-2 text-sm gap-2",
				"item": "p-2 text-sm gap-2",
				"itemLeadingIcon": "size-5",
				"itemLeadingAvatarSize": "2xs",
				"itemTrailingIcon": "size-5",
				"itemTrailingKbds": "gap-1",
				"itemTrailingKbdsSize": "md"
			},
			"xl": {
				"label": "p-2 text-base gap-2",
				"item": "p-2 text-base gap-2",
				"itemLeadingIcon": "size-6",
				"itemLeadingAvatarSize": "xs",
				"itemTrailingIcon": "size-6",
				"itemTrailingKbds": "gap-1",
				"itemTrailingKbdsSize": "lg"
			}
		}
	},
	"compoundVariants": [
		{
			"color": "primary",
			"active": false,
			"class": {
				"item": "text-primary data-highlighted:text-primary data-highlighted:before:bg-primary/10 data-[state=open]:before:bg-primary/10",
				"itemLeadingIcon": "text-primary/75 group-data-highlighted:text-primary group-data-[state=open]:text-primary"
			}
		},
		{
			"color": "secondary",
			"active": false,
			"class": {
				"item": "text-secondary data-highlighted:text-secondary data-highlighted:before:bg-secondary/10 data-[state=open]:before:bg-secondary/10",
				"itemLeadingIcon": "text-secondary/75 group-data-highlighted:text-secondary group-data-[state=open]:text-secondary"
			}
		},
		{
			"color": "success",
			"active": false,
			"class": {
				"item": "text-success data-highlighted:text-success data-highlighted:before:bg-success/10 data-[state=open]:before:bg-success/10",
				"itemLeadingIcon": "text-success/75 group-data-highlighted:text-success group-data-[state=open]:text-success"
			}
		},
		{
			"color": "info",
			"active": false,
			"class": {
				"item": "text-info data-highlighted:text-info data-highlighted:before:bg-info/10 data-[state=open]:before:bg-info/10",
				"itemLeadingIcon": "text-info/75 group-data-highlighted:text-info group-data-[state=open]:text-info"
			}
		},
		{
			"color": "warning",
			"active": false,
			"class": {
				"item": "text-warning data-highlighted:text-warning data-highlighted:before:bg-warning/10 data-[state=open]:before:bg-warning/10",
				"itemLeadingIcon": "text-warning/75 group-data-highlighted:text-warning group-data-[state=open]:text-warning"
			}
		},
		{
			"color": "error",
			"active": false,
			"class": {
				"item": "text-error data-highlighted:text-error data-highlighted:before:bg-error/10 data-[state=open]:before:bg-error/10",
				"itemLeadingIcon": "text-error/75 group-data-highlighted:text-error group-data-[state=open]:text-error"
			}
		},
		{
			"color": "primary",
			"active": true,
			"class": {
				"item": "text-primary before:bg-primary/10",
				"itemLeadingIcon": "text-primary"
			}
		},
		{
			"color": "secondary",
			"active": true,
			"class": {
				"item": "text-secondary before:bg-secondary/10",
				"itemLeadingIcon": "text-secondary"
			}
		},
		{
			"color": "success",
			"active": true,
			"class": {
				"item": "text-success before:bg-success/10",
				"itemLeadingIcon": "text-success"
			}
		},
		{
			"color": "info",
			"active": true,
			"class": {
				"item": "text-info before:bg-info/10",
				"itemLeadingIcon": "text-info"
			}
		},
		{
			"color": "warning",
			"active": true,
			"class": {
				"item": "text-warning before:bg-warning/10",
				"itemLeadingIcon": "text-warning"
			}
		},
		{
			"color": "error",
			"active": true,
			"class": {
				"item": "text-error before:bg-error/10",
				"itemLeadingIcon": "text-error"
			}
		}
	],
	"defaultVariants": { "size": "md" }
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/ContextMenu.vue
var _sfc_main$2 = {
	__name: "ContextMenu",
	__ssrInlineRender: true,
	props: {
		size: {
			type: null,
			required: false
		},
		items: {
			type: null,
			required: false
		},
		checkedIcon: {
			type: null,
			required: false
		},
		loadingIcon: {
			type: null,
			required: false
		},
		externalIcon: {
			type: [Boolean, String],
			required: false,
			skipCheck: true,
			default: true
		},
		content: {
			type: Object,
			required: false
		},
		portal: {
			type: [Boolean, String],
			required: false,
			skipCheck: true,
			default: true
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
		disabled: {
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
		pressOpenDelay: {
			type: Number,
			required: false
		},
		modal: {
			type: Boolean,
			required: false,
			default: true
		}
	},
	emits: ["update:open"],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const slots = useSlots();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("contextMenu", props);
		const rootProps = useForwardPropsEmits(reactivePick(props, "modal"), emits);
		const contentProps = toRef(() => props.content);
		const getProxySlots = () => omit(slots, ["default"]);
		const ui = computed(() => tv({
			extend: tv(context_menu_default),
			...appConfig.ui?.contextMenu || {}
		})({ size: props.size }));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(ContextMenuRoot), mergeProps(unref(rootProps), _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						if (!!slots.default) _push(ssrRenderComponent(unref(ContextMenuTrigger), {
							"as-child": "",
							disabled: __props.disabled,
							class: props.class
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
								else return [renderSlot(_ctx.$slots, "default")];
							}),
							_: 3
						}, _parent, _scopeId));
						else _push(`<!---->`);
						_push(ssrRenderComponent(_sfc_main$3, mergeProps({
							class: ui.value.content({ class: [!slots.default && props.class, unref(uiProp)?.content] }),
							ui: ui.value,
							"ui-override": unref(uiProp)
						}, contentProps.value, {
							items: __props.items,
							portal: __props.portal,
							"label-key": __props.labelKey,
							"description-key": __props.descriptionKey,
							"checked-icon": __props.checkedIcon,
							"loading-icon": __props.loadingIcon,
							"external-icon": __props.externalIcon
						}), createSlots({ _: 2 }, [renderList(getProxySlots(), (_, name) => {
							return {
								name,
								fn: withCtx((slotData, _push, _parent, _scopeId) => {
									if (_push) ssrRenderSlot(_ctx.$slots, name, slotData, null, _push, _parent, _scopeId);
									else return [renderSlot(_ctx.$slots, name, slotData)];
								})
							};
						})]), _parent, _scopeId));
					} else return [!!slots.default ? (openBlock(), createBlock(unref(ContextMenuTrigger), {
						key: 0,
						"as-child": "",
						disabled: __props.disabled,
						class: props.class
					}, {
						default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
						_: 3
					}, 8, ["disabled", "class"])) : createCommentVNode("", true), createVNode(_sfc_main$3, mergeProps({
						class: ui.value.content({ class: [!slots.default && props.class, unref(uiProp)?.content] }),
						ui: ui.value,
						"ui-override": unref(uiProp)
					}, contentProps.value, {
						items: __props.items,
						portal: __props.portal,
						"label-key": __props.labelKey,
						"description-key": __props.descriptionKey,
						"checked-icon": __props.checkedIcon,
						"loading-icon": __props.loadingIcon,
						"external-icon": __props.externalIcon
					}), createSlots({ _: 2 }, [renderList(getProxySlots(), (_, name) => {
						return {
							name,
							fn: withCtx((slotData) => [renderSlot(_ctx.$slots, name, slotData)])
						};
					})]), 1040, [
						"class",
						"ui",
						"ui-override",
						"items",
						"portal",
						"label-key",
						"description-key",
						"checked-icon",
						"loading-icon",
						"external-icon"
					])];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$9 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/ContextMenu.vue");
	return _sfc_setup$9 ? _sfc_setup$9(props, ctx) : void 0;
};
//#endregion
//#region resources/js/Composables/useDataTableKeyboardNavigation.ts
var interactiveSelector = [
	"a[href]",
	"button:not([disabled])",
	"[role=\"button\"]",
	"[role=\"menuitem\"]",
	"[role=\"checkbox\"]",
	"[aria-haspopup]",
	"input:not([disabled])",
	"select:not([disabled])",
	"textarea:not([disabled])",
	"[tabindex]:not([tabindex=\"-1\"])"
].join(",");
function isTextInput(target) {
	const element = target;
	if (!element) return false;
	return element.tagName === "INPUT" || element.tagName === "TEXTAREA" || element.isContentEditable;
}
function isVisible(element) {
	return element.offsetParent !== null;
}
function isNavigableTableRow(row) {
	if (!isVisible(row)) return false;
	if (row.getAttribute("aria-hidden") === "true") return false;
	if (row.getAttribute("role") === "separator") return false;
	if (row.dataset.divider === "true") return false;
	if (row.dataset.separator === "true") return false;
	if (row.classList.contains("divider")) return false;
	if (row.classList.contains("separator")) return false;
	const cells = Array.from(row.querySelectorAll("th, td")).filter(isVisible);
	if (cells.length === 0) return false;
	return cells.some((cell) => {
		if (cell.getAttribute("aria-hidden") === "true") return false;
		if (cell.getAttribute("role") === "separator") return false;
		const text = cell.textContent?.trim() ?? "";
		const hasInteractive = Boolean(cell.querySelector(interactiveSelector));
		return text !== "" || hasInteractive;
	});
}
function getFocusablePageElements() {
	return Array.from(document.querySelectorAll([
		"a[href]",
		"button:not([disabled])",
		"input:not([disabled])",
		"select:not([disabled])",
		"textarea:not([disabled])",
		"[tabindex]:not([tabindex=\"-1\"])"
	].join(","))).filter(isVisible);
}
function useDataTableKeyboardNavigation(options) {
	const activeRowIndex = ref(0);
	const activeColumnIndex = ref(0);
	const activeActionIndex = ref(0);
	const focusMode = ref("row");
	const isTableFocused = ref(false);
	const isKeyboardMode = ref(false);
	let refreshFrame = null;
	function emitActiveRowChange() {
		options.onActiveRowChange?.(getCurrentRowApi());
	}
	function getHeaderRows() {
		return Array.from(options.container.value?.querySelectorAll("thead tr") ?? []).filter(isNavigableTableRow);
	}
	function getBodyRows() {
		return Array.from(options.container.value?.querySelectorAll("tbody tr") ?? []).filter(isNavigableTableRow);
	}
	function getRows() {
		return [...getHeaderRows(), ...getBodyRows()];
	}
	function getBodyRowStartIndex() {
		return getHeaderRows().length;
	}
	function getCells(row) {
		return Array.from(row?.querySelectorAll("th, td") ?? []);
	}
	function getCurrentDomRow() {
		return getRows()[activeRowIndex.value] ?? null;
	}
	function getCurrentCell() {
		return getCells(getCurrentDomRow())[activeColumnIndex.value] ?? null;
	}
	function getCurrentRowApi() {
		const bodyIndex = activeRowIndex.value - getBodyRowStartIndex();
		if (bodyIndex < 0) return null;
		return options.table.value?.tableApi?.getRowModel?.().rows?.[bodyIndex] ?? null;
	}
	function getCellActions(cell = getCurrentCell()) {
		if (!cell) return [];
		return Array.from(cell.querySelectorAll(interactiveSelector)).filter((element) => isVisible(element) && !element.closest("[aria-hidden=\"true\"]"));
	}
	function isSelectCell(cell = getCurrentCell()) {
		if (!cell) return false;
		return Boolean(cell.querySelector("[role=\"checkbox\"], input[type=\"checkbox\"]") || activeColumnIndex.value === 0);
	}
	function clearManagedTabIndexes() {
		const container = options.container.value;
		if (!container) return;
		container.querySelectorAll(interactiveSelector).forEach((element) => {
			element.tabIndex = -1;
		});
	}
	function syncTabIndexes() {
		clearManagedTabIndexes();
		const row = getCurrentDomRow();
		const cell = getCurrentCell();
		getRows().forEach((tableRow) => {
			tableRow.dataset.keyboardManaged = "true";
			tableRow.tabIndex = -1;
			getCells(tableRow).forEach((tableCell) => {
				tableCell.dataset.keyboardManaged = "true";
				tableCell.dataset.keyboardCell = "true";
				tableCell.tabIndex = -1;
			});
		});
		if (focusMode.value === "row" && row) {
			row.tabIndex = 0;
			return;
		}
		if (focusMode.value === "cell" && cell) {
			cell.tabIndex = 0;
			return;
		}
		if (focusMode.value === "action") {
			const action = getCellActions(cell)[activeActionIndex.value];
			if (action) action.tabIndex = 0;
		}
	}
	function scheduleRefresh() {
		if (refreshFrame !== null) cancelAnimationFrame(refreshFrame);
		refreshFrame = requestAnimationFrame(() => {
			refreshFrame = null;
			syncTabIndexes();
		});
	}
	function focusRow(rowIndex) {
		const rows = getRows();
		if (!rows.length) return;
		activeRowIndex.value = Math.max(0, Math.min(rowIndex, rows.length - 1));
		const currentCells = getCells(rows[activeRowIndex.value]);
		activeColumnIndex.value = Math.max(0, Math.min(activeColumnIndex.value, currentCells.length - 1));
		focusMode.value = "row";
		activeActionIndex.value = 0;
		syncTabIndexes();
		emitActiveRowChange();
		rows[activeRowIndex.value]?.focus({ preventScroll: false });
	}
	function focusCell(rowIndex, columnIndex) {
		const rows = getRows();
		if (!rows.length) return;
		activeRowIndex.value = Math.max(0, Math.min(rowIndex, rows.length - 1));
		const cells = getCells(rows[activeRowIndex.value]);
		if (!cells.length) return;
		activeColumnIndex.value = Math.max(0, Math.min(columnIndex, cells.length - 1));
		focusMode.value = "cell";
		activeActionIndex.value = 0;
		syncTabIndexes();
		emitActiveRowChange();
		cells[activeColumnIndex.value]?.focus({ preventScroll: false });
	}
	function focusAction(index) {
		const actions = getCellActions();
		if (!actions.length) return;
		activeActionIndex.value = (index % actions.length + actions.length) % actions.length;
		focusMode.value = "action";
		syncTabIndexes();
		actions[activeActionIndex.value]?.focus({ preventScroll: false });
	}
	function focusNextOutsideContainer(backward) {
		const container = options.container.value;
		if (!container) return;
		const focusables = getFocusablePageElements();
		const outside = focusables.filter((element) => !container.contains(element));
		const currentIndex = focusables.findIndex((element) => element === document.activeElement);
		(backward ? outside.filter((element) => focusables.indexOf(element) < currentIndex).reverse() : outside.filter((element) => focusables.indexOf(element) > currentIndex))[0]?.focus();
	}
	function toggleCurrentRowSelection() {
		if (!options.selectable.value) return;
		const row = getCurrentRowApi();
		if (!row) return;
		row.toggleSelected(!row.getIsSelected());
	}
	function activateCurrentCell() {
		const cell = getCurrentCell();
		if (!cell) return;
		if (options.selectable.value && isSelectCell(cell)) {
			toggleCurrentRowSelection();
			return;
		}
		const actions = getCellActions();
		if (actions.length === 0) return;
		if (actions.length === 1) {
			actions[0].click();
			return;
		}
		focusAction(0);
	}
	function activateCurrentAction() {
		getCellActions()[activeActionIndex.value]?.click();
	}
	function openCurrentRowContextMenu() {
		const row = getCurrentDomRow();
		if (!row) return;
		const rect = row.getBoundingClientRect();
		row.dispatchEvent(new MouseEvent("contextmenu", {
			bubbles: true,
			cancelable: true,
			button: 2,
			buttons: 2,
			clientX: rect.left + rect.width / 2,
			clientY: rect.top + rect.height / 2
		}));
	}
	const keyboardNavigationKeys = new Set([
		"Tab",
		"ArrowUp",
		"ArrowDown",
		"ArrowLeft",
		"ArrowRight",
		"Home",
		"End",
		"Enter",
		" ",
		"Escape",
		"ContextMenu",
		"F10"
	]);
	function enableKeyboardMode() {
		isKeyboardMode.value = true;
	}
	function disableKeyboardMode() {
		isKeyboardMode.value = false;
	}
	function onGlobalPointerDown() {
		disableKeyboardMode();
	}
	function onGlobalKeyDown(event) {
		if (isTextInput(event.target)) return;
		if (keyboardNavigationKeys.has(event.key)) enableKeyboardMode();
	}
	function onFocusIn(event) {
		const target = event.target;
		if (!target || !options.container.value?.contains(target)) return;
		isTableFocused.value = true;
		if (target === options.container.value) {
			syncTabIndexes();
			return;
		}
		const row = target.closest("tr");
		const cell = target.closest("th, td");
		if (!row) return;
		const rows = getRows();
		const cells = getCells(row);
		activeRowIndex.value = Math.max(0, rows.indexOf(row));
		if (cell) {
			activeColumnIndex.value = Math.max(0, cells.indexOf(cell));
			if (target.matches(interactiveSelector) && target !== cell) {
				const actions = getCellActions(cell);
				activeActionIndex.value = Math.max(0, actions.indexOf(target));
				focusMode.value = "action";
			} else focusMode.value = "cell";
		} else focusMode.value = "row";
		syncTabIndexes();
		emitActiveRowChange();
	}
	function onFocusOut(event) {
		const nextTarget = event.relatedTarget;
		if (!nextTarget || !options.container.value?.contains(nextTarget)) isTableFocused.value = false;
	}
	function onKeydown(event) {
		if (isTextInput(event.target)) return;
		const key = event.key;
		if (key === "Tab") {
			event.preventDefault();
			focusNextOutsideContainer(event.shiftKey);
			return;
		}
		if (event.target === options.container.value) {
			if (key === "ArrowDown" || key === "ArrowRight" || key === "Enter") {
				event.preventDefault();
				focusRow(0);
				return;
			}
			if (key === "ArrowUp" || key === "ArrowLeft") {
				event.preventDefault();
				focusRow(getRows().length - 1);
				return;
			}
		}
		if (key.toLowerCase() === "x") {
			event.preventDefault();
			toggleCurrentRowSelection();
			return;
		}
		if (key === "ContextMenu" || key === "F10") {
			event.preventDefault();
			openCurrentRowContextMenu();
			return;
		}
		if (focusMode.value === "action") {
			if (key === "ArrowRight" || key === "ArrowDown") {
				event.preventDefault();
				focusAction(activeActionIndex.value + 1);
				return;
			}
			if (key === "ArrowLeft" || key === "ArrowUp") {
				event.preventDefault();
				focusAction(activeActionIndex.value - 1);
				return;
			}
			if (key === "Enter" || key === " ") {
				event.preventDefault();
				activateCurrentAction();
				return;
			}
			if (key === "Escape") {
				event.preventDefault();
				focusCell(activeRowIndex.value, activeColumnIndex.value);
				return;
			}
			return;
		}
		if (key === "ArrowDown") {
			event.preventDefault();
			if (focusMode.value === "row") focusRow(activeRowIndex.value + 1);
			else focusCell(activeRowIndex.value + 1, activeColumnIndex.value);
			return;
		}
		if (key === "ArrowUp") {
			event.preventDefault();
			if (focusMode.value === "row") focusRow(activeRowIndex.value - 1);
			else focusCell(activeRowIndex.value - 1, activeColumnIndex.value);
			return;
		}
		if (key === "ArrowRight") {
			event.preventDefault();
			if (focusMode.value === "row") focusCell(activeRowIndex.value, 0);
			else focusCell(activeRowIndex.value, activeColumnIndex.value + 1);
			return;
		}
		if (key === "ArrowLeft") {
			event.preventDefault();
			if (focusMode.value === "cell" && activeColumnIndex.value === 0) focusRow(activeRowIndex.value);
			else if (focusMode.value === "cell") focusCell(activeRowIndex.value, activeColumnIndex.value - 1);
			return;
		}
		if (key === "Home") {
			event.preventDefault();
			if (focusMode.value === "row") focusRow(0);
			else focusCell(activeRowIndex.value, 0);
			return;
		}
		if (key === "End") {
			event.preventDefault();
			if (focusMode.value === "row") focusRow(getRows().length - 1);
			else focusCell(activeRowIndex.value, getCells(getCurrentDomRow()).length - 1);
			return;
		}
		if (key === "Escape" && focusMode.value === "cell") {
			event.preventDefault();
			focusRow(activeRowIndex.value);
			return;
		}
		if (key === "Enter" || key === " ") {
			event.preventDefault();
			if (focusMode.value === "row") {
				if (key === "Enter") {
					const row = getCurrentRowApi();
					if (row) options.onRowActivate?.(row);
					return;
				}
				toggleCurrentRowSelection();
				return;
			}
			activateCurrentCell();
		}
	}
	async function refreshKeyboardNavigation() {
		await nextTick();
		const rows = getRows();
		activeRowIndex.value = Math.min(activeRowIndex.value, Math.max(rows.length - 1, 0));
		activeColumnIndex.value = Math.min(activeColumnIndex.value, Math.max(getCells(getCurrentDomRow()).length - 1, 0));
		scheduleRefresh();
		emitActiveRowChange();
	}
	onMounted(() => {
		window.addEventListener("pointerdown", onGlobalPointerDown, true);
		window.addEventListener("mousedown", onGlobalPointerDown, true);
		window.addEventListener("keydown", onGlobalKeyDown, true);
	});
	onBeforeUnmount(() => {
		if (refreshFrame !== null) cancelAnimationFrame(refreshFrame);
		window.removeEventListener("pointerdown", onGlobalPointerDown, true);
		window.removeEventListener("mousedown", onGlobalPointerDown, true);
		window.removeEventListener("keydown", onGlobalKeyDown, true);
	});
	return {
		onKeydown,
		onFocusIn,
		onFocusOut,
		refreshKeyboardNavigation,
		isTableFocused,
		isKeyboardMode
	};
}
//#endregion
//#region resources/js/Components/DataTable/PrimaryDataTable.vue?vue&type=script&setup=true&lang.ts
var PrimaryDataTable_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "PrimaryDataTable",
	__ssrInlineRender: true,
	props: /* @__PURE__ */ mergeModels({
		title: {},
		description: {},
		data: {},
		columns: {},
		meta: {},
		filters: { default: () => ({}) },
		loading: { type: Boolean },
		getRowId: {
			type: Function,
			default: (row) => row.id
		},
		initialColumnVisibility: { default: () => ({}) },
		perPageItems: { default: () => [
			10,
			20,
			50,
			100
		] },
		searchable: {
			type: Boolean,
			default: true
		},
		selectable: {
			type: Boolean,
			default: true
		},
		stripedRows: {
			type: Boolean,
			default: true
		},
		views: { default: () => [] },
		viewFilterKey: { default: "view" },
		labels: { default: () => ({
			search: "Rechercher...",
			columns: "Colonnes",
			selectedWord: "sélectionné(s)",
			connectingWord: "sur",
			unselect: "Désélectionner"
		}) },
		rowMenuItems: {
			type: Function,
			default: void 0
		},
		ui: { default: () => ({}) }
	}, {
		"rowSelection": { default: {} },
		"rowSelectionModifiers": {}
	}),
	emits: /* @__PURE__ */ mergeModels([
		"reload",
		"create",
		"contextmenu",
		"activeRowChange",
		"rowActivate",
		"rowDoubleClick",
		"bulkDelete",
		"bulkForceDelete",
		"bulkRestore"
	], ["update:rowSelection"]),
	setup(__props, { emit: __emit }) {
		const props = __props;
		const page = usePage();
		const emit = __emit;
		const table = useTemplateRef("table");
		const UCheckbox = _sfc_main$14;
		const tableKeyboardContainer = useTemplateRef("tableKeyboardContainer");
		const { onKeydown: onTableKeydown, onFocusIn: onTableFocusIn, onFocusOut: onTableFocusOut, refreshKeyboardNavigation, isTableFocused, isKeyboardMode } = useDataTableKeyboardNavigation({
			table,
			container: tableKeyboardContainer,
			selectable: toRef(props, "selectable"),
			onActiveRowChange: (row) => emit("activeRowChange", row),
			onRowActivate: (row) => emit("rowActivate", row)
		});
		watch(() => [
			props.data,
			props.columns,
			props.loading
		], () => refreshKeyboardNavigation(), {
			deep: true,
			immediate: true
		});
		const rowSelection = useModel(__props, "rowSelection");
		const columnVisibility = ref({ ...props.initialColumnVisibility });
		const globalFilter = ref(props.filters.search ?? "");
		const perPageSelectValue = ref(props.filters.per_page ?? props.meta.per_page ?? 10);
		let searchTimeout = null;
		const currentPage = computed(() => props.meta.current_page ?? 1);
		const perPage = computed(() => props.meta.per_page ?? perPageSelectValue);
		const total = computed(() => props.meta.total ?? 0);
		const totalSelected = computed(() => Object.keys(rowSelection.value ?? {}).length);
		const tableColumns = computed(() => {
			if (!props.selectable) return props.columns;
			return [{
				id: "select",
				header: ({ table }) => h(UCheckbox, {
					modelValue: table.getIsSomePageRowsSelected() ? "indeterminate" : table.getIsAllPageRowsSelected(),
					"onUpdate:modelValue": (value) => table.toggleAllPageRowsSelected(!!value),
					"aria-label": "Select all"
				}),
				cell: ({ row }) => h(UCheckbox, {
					modelValue: row.getIsSelected(),
					"onUpdate:modelValue": (value) => row.toggleSelected(!!value),
					"aria-label": "Select row"
				}),
				enableHiding: false,
				meta: { class: {
					th: "align-middle !p-4",
					td: "align-middle !p-4"
				} }
			}, ...props.columns];
		});
		const tableMeta = computed(() => ({ class: { tr: (row) => [props.stripedRows ? row.index % 2 === 0 ? "bg-white/2" : "bg-white/5" : "", "hover:bg-white/10 transition-colors"].join(" ") } }));
		const defaultUi = {
			root: "min-h-[300px]",
			base: "min-w-[1200px] w-full table-auto relative",
			thead: "sticky top-0 bg-darknight",
			tbody: "align-top",
			tr: "h-auto",
			td: "w-fit"
		};
		const contextMenuItems = ref([]);
		watch(() => props.filters.search, (search) => {
			globalFilter.value = search ?? "";
		});
		watch(globalFilter, (search) => {
			if (search === (props.filters.search ?? "")) return;
			if (searchTimeout) clearTimeout(searchTimeout);
			searchTimeout = setTimeout(() => {
				rowSelection.value = {};
				emit("reload", {
					search,
					page: 1
				});
			}, 300);
		});
		watch(perPageSelectValue, (value) => {
			if (Number(value) === Number(props.filters.per_page ?? props.meta.per_page)) return;
			rowSelection.value = {};
			emit("reload", {
				per_page: value,
				page: 1
			});
		});
		function goToPage(page) {
			if (Number(page) === Number(currentPage.value)) return;
			emit("reload", { page });
		}
		function onSelect(event, row) {
			if (event instanceof MouseEvent && event.detail === 2) {
				emit("rowDoubleClick", row);
				return;
			}
			row.toggleSelected(!row.getIsSelected());
		}
		function onUnselectAll() {
			rowSelection.value = {};
		}
		function onContextMenu(event, row) {
			if (props.rowMenuItems) contextMenuItems.value = props.rowMenuItems(row);
			emit("contextmenu", event, row);
		}
		function isCurrentUser(row) {
			return Boolean(row?.id === page.props.auth.user.id);
		}
		computed(() => props.views.length > 0);
		const currentTableView = computed(() => {
			return props.filters?.[props.viewFilterKey] ?? props.views[0]?.value;
		});
		function changeTableView(view) {
			if (view === currentTableView.value) return;
			rowSelection.value = {};
			emit("reload", {
				[props.viewFilterKey]: view,
				page: 1
			});
		}
		const selected = computed(() => {
			return props.data.filter((row) => rowSelection.value[row.id]);
		});
		const selectedActive = computed(() => selected.value.filter((row) => !row.deleted_at));
		const selectedTrashed = computed(() => selected.value.filter((row) => row.deleted_at));
		const softDeletable = computed(() => selectedActive.value.filter((row) => row.permissions?.delete && !isCurrentUser(row)));
		const forceDeletable = computed(() => selectedTrashed.value.filter((row) => row.permissions?.force_delete && !isCurrentUser(row)));
		const restorable = computed(() => selectedTrashed.value.filter((row) => row.permissions?.restore && !isCurrentUser(row)));
		const canBulkDelete = computed(() => softDeletable.value.length > 0);
		const canBulkForceDelete = computed(() => forceDeletable.value.length > 0);
		const canBulkRestore = computed(() => restorable.value.length > 0);
		return (_ctx, _push, _parent, _attrs) => {
			const _component_UInput = _sfc_main$26;
			const _component_UTooltip = _sfc_main$40;
			const _component_UButton = _sfc_main$22;
			const _component_UDropdownMenu = _sfc_main$10;
			const _component_UContextMenu = _sfc_main$2;
			const _component_UTable = _sfc_main$4;
			const _component_USelect = _sfc_main$32;
			const _component_UPagination = _sfc_main$5;
			_push(`<section${ssrRenderAttrs(mergeProps({ class: "min-h-0" }, _attrs))} data-v-6db84711><div class="container flex flex-col gap-4 h-full justify-between min-h-0" data-v-6db84711><div class="table-header flex flex-col justify-between" data-v-6db84711>`);
			if (__props.title || __props.description) {
				_push(`<hgroup class="mb-6" data-v-6db84711>`);
				if (__props.title) _push(`<h3 class="text-base" data-v-6db84711>${ssrInterpolate(__props.title)}</h3>`);
				else _push(`<!---->`);
				if (__props.description) _push(`<p class="text-sm text-muted" data-v-6db84711>${ssrInterpolate(__props.description)}</p>`);
				else _push(`<!---->`);
				_push(`</hgroup>`);
			} else _push(`<!---->`);
			_push(`<div class="flex justify-between item-center w-full flex-wrap gap-3" data-v-6db84711><div class="flex items-center justify-between w-full md:justify-start gap-4 md:w-fit" data-v-6db84711>`);
			if (__props.searchable) _push(ssrRenderComponent(_component_UInput, {
				modelValue: globalFilter.value,
				"onUpdate:modelValue": ($event) => globalFilter.value = $event,
				placeholder: __props.labels.search,
				class: "min-w-[150px]",
				"data-users-search": ""
			}, null, _parent));
			else _push(`<!---->`);
			_push(`<div class="flex gap-2" data-v-6db84711><!--[-->`);
			ssrRenderList(__props.views, (view) => {
				_push(ssrRenderComponent(_component_UTooltip, {
					key: view.value,
					text: view.label,
					"delay-duration": 0
				}, {
					default: withCtx((_, _push, _parent, _scopeId) => {
						if (_push) _push(ssrRenderComponent(_component_UButton, {
							icon: view.icon,
							size: "sm",
							color: currentTableView.value === view.value ? "primary" : "neutral",
							variant: currentTableView.value === view.value ? "solid" : "ghost",
							onClick: ($event) => changeTableView(view.value),
							class: "cursor-pointer"
						}, null, _parent, _scopeId));
						else return [createVNode(_component_UButton, {
							icon: view.icon,
							size: "sm",
							color: currentTableView.value === view.value ? "primary" : "neutral",
							variant: currentTableView.value === view.value ? "solid" : "ghost",
							onClick: ($event) => changeTableView(view.value),
							class: "cursor-pointer"
						}, null, 8, [
							"icon",
							"color",
							"variant",
							"onClick"
						])];
					}),
					_: 2
				}, _parent));
			});
			_push(`<!--]--></div></div><div class="flex gap-2 flex-wrap sm:flex-nowrap w-fit" data-v-6db84711>`);
			ssrRenderSlot(_ctx.$slots, "global-actions", {}, null, _push, _parent);
			_push(ssrRenderComponent(_component_UDropdownMenu, {
				items: table.value?.tableApi?.getAllColumns().filter((column) => column.getCanHide()).map((column) => ({
					label: unref(upperFirst)(column.id),
					type: "checkbox",
					checked: column.getIsVisible(),
					onUpdateChecked(checked) {
						table.value?.tableApi?.getColumn(column.id)?.toggleVisibility(!!checked);
					},
					onSelect(event) {
						event.preventDefault();
					}
				})),
				ui: { content: "z-99" }
			}, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(ssrRenderComponent(_component_UButton, {
						label: __props.labels.columns,
						color: "neutral",
						variant: "outline",
						"trailing-icon": "i-lucide-chevron-down",
						onClick: () => {}
					}, null, _parent, _scopeId));
					else return [createVNode(_component_UButton, {
						label: __props.labels.columns,
						color: "neutral",
						variant: "outline",
						"trailing-icon": "i-lucide-chevron-down",
						onClick: withModifiers(() => {}, ["stop"])
					}, null, 8, ["label", "onClick"])];
				}),
				_: 1
			}, _parent));
			_push(`</div></div></div>`);
			_push(ssrRenderComponent(_component_UContextMenu, { items: contextMenuItems.value }, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="min-h-0 flex-1 overflow-auto rounded-md data-table-keyboard" tabindex="0"${ssrRenderAttr("data-table-focused", unref(isTableFocused) ? "true" : void 0)}${ssrRenderAttr("data-keyboard-mode", unref(isKeyboardMode) ? "true" : void 0)} data-v-6db84711${_scopeId}>`);
						_push(ssrRenderComponent(_component_UTable, {
							ref_key: "table",
							ref: table,
							"row-selection": rowSelection.value,
							"onUpdate:rowSelection": ($event) => rowSelection.value = $event,
							"column-visibility": columnVisibility.value,
							"onUpdate:columnVisibility": ($event) => columnVisibility.value = $event,
							data: __props.data,
							columns: tableColumns.value,
							"get-row-id": __props.getRowId,
							loading: __props.loading,
							meta: tableMeta.value,
							ui: {
								...defaultUi,
								...__props.ui
							},
							onContextmenu: onContextMenu,
							onSelect
						}, createSlots({ _: 2 }, [renderList(_ctx.$slots, (_, slotName) => {
							return {
								name: slotName,
								fn: withCtx((slotProps, _push, _parent, _scopeId) => {
									if (_push) ssrRenderSlot(_ctx.$slots, slotName, slotProps, null, _push, _parent, _scopeId);
									else return [renderSlot(_ctx.$slots, slotName, slotProps, void 0, true)];
								})
							};
						})]), _parent, _scopeId));
						_push(`</div>`);
					} else return [createVNode("div", {
						ref_key: "tableKeyboardContainer",
						ref: tableKeyboardContainer,
						class: "min-h-0 flex-1 overflow-auto rounded-md data-table-keyboard",
						tabindex: "0",
						"data-table-focused": unref(isTableFocused) ? "true" : void 0,
						"data-keyboard-mode": unref(isKeyboardMode) ? "true" : void 0,
						onPointerdown: _ctx.onTablePointerDown,
						onKeydown: unref(onTableKeydown),
						onFocusin: unref(onTableFocusIn),
						onFocusout: unref(onTableFocusOut)
					}, [createVNode(_component_UTable, {
						ref_key: "table",
						ref: table,
						"row-selection": rowSelection.value,
						"onUpdate:rowSelection": ($event) => rowSelection.value = $event,
						"column-visibility": columnVisibility.value,
						"onUpdate:columnVisibility": ($event) => columnVisibility.value = $event,
						data: __props.data,
						columns: tableColumns.value,
						"get-row-id": __props.getRowId,
						loading: __props.loading,
						meta: tableMeta.value,
						ui: {
							...defaultUi,
							...__props.ui
						},
						onContextmenu: onContextMenu,
						onSelect
					}, createSlots({ _: 2 }, [renderList(_ctx.$slots, (_, slotName) => {
						return {
							name: slotName,
							fn: withCtx((slotProps) => [renderSlot(_ctx.$slots, slotName, slotProps, void 0, true)])
						};
					})]), 1032, [
						"row-selection",
						"onUpdate:rowSelection",
						"column-visibility",
						"onUpdate:columnVisibility",
						"data",
						"columns",
						"get-row-id",
						"loading",
						"meta",
						"ui"
					])], 40, [
						"data-table-focused",
						"data-keyboard-mode",
						"onPointerdown",
						"onKeydown",
						"onFocusin",
						"onFocusout"
					])];
				}),
				_: 3
			}, _parent));
			_push(`<div class="table-footer m-2 flex gap-2" data-v-6db84711>`);
			if (__props.selectable && totalSelected.value > 0) {
				_push(`<div class="flex gap-2" data-v-6db84711>`);
				if (selectedActive.value.length) _push(ssrRenderComponent(_component_UButton, {
					icon: "i-lucide-trash",
					color: "error",
					variant: "outline",
					disabled: !canBulkDelete.value,
					onClick: ($event) => emit("bulkDelete", softDeletable.value)
				}, {
					default: withCtx((_, _push, _parent, _scopeId) => {
						if (_push) _push(` Mettre à la corbeille `);
						else return [createTextVNode(" Mettre à la corbeille ")];
					}),
					_: 1
				}, _parent));
				else _push(`<!---->`);
				if (selectedTrashed.value.length) _push(ssrRenderComponent(_component_UButton, {
					icon: "i-lucide-rotate-ccw",
					color: "neutral",
					variant: "outline",
					disabled: !canBulkRestore.value,
					onClick: ($event) => emit("bulkRestore", restorable.value)
				}, {
					default: withCtx((_, _push, _parent, _scopeId) => {
						if (_push) _push(` Restaurer `);
						else return [createTextVNode(" Restaurer ")];
					}),
					_: 1
				}, _parent));
				else _push(`<!---->`);
				if (selectedTrashed.value.length) _push(ssrRenderComponent(_component_UButton, {
					icon: "i-lucide-trash-2",
					color: "error",
					variant: "solid",
					disabled: !canBulkForceDelete.value,
					onClick: ($event) => emit("bulkForceDelete", forceDeletable.value)
				}, {
					default: withCtx((_, _push, _parent, _scopeId) => {
						if (_push) _push(` Supprimer définitivement `);
						else return [createTextVNode(" Supprimer définitivement ")];
					}),
					_: 1
				}, _parent));
				else _push(`<!---->`);
				_push(`</div>`);
			} else _push(`<!---->`);
			_push(`<div class="flex items-center gap-4" data-v-6db84711>`);
			if (totalSelected.value > 0) _push(ssrRenderComponent(_component_UButton, {
				variant: "outline",
				label: __props.labels.unselect,
				class: "cursor-pointer",
				onClick: onUnselectAll
			}, null, _parent));
			else _push(`<!---->`);
			_push(`<p class="text-lg" data-v-6db84711>${ssrInterpolate(totalSelected.value)} ${ssrInterpolate(__props.labels.connectingWord)} ${ssrInterpolate(total.value)} ${ssrInterpolate(__props.labels.selectedWord)}</p></div></div><div class="flex items-center justify-center w-full mt-2 gap-5" data-v-6db84711>`);
			_push(ssrRenderComponent(_component_USelect, {
				modelValue: perPageSelectValue.value,
				"onUpdate:modelValue": ($event) => perPageSelectValue.value = $event,
				items: __props.perPageItems,
				disabled: __props.loading
			}, null, _parent));
			_push(ssrRenderComponent(_component_UPagination, {
				page: currentPage.value,
				"items-per-page": perPage.value,
				total: total.value,
				"onUpdate:page": goToPage
			}, null, _parent));
			_push(`</div></div></section>`);
		};
	}
});
//#endregion
//#region resources/js/Components/DataTable/PrimaryDataTable.vue
var _sfc_setup$8 = PrimaryDataTable_vue_vue_type_script_setup_true_lang_default.setup;
PrimaryDataTable_vue_vue_type_script_setup_true_lang_default.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/DataTable/PrimaryDataTable.vue");
	return _sfc_setup$8 ? _sfc_setup$8(props, ctx) : void 0;
};
var PrimaryDataTable_default = /* @__PURE__ */ _plugin_vue_export_helper_default(PrimaryDataTable_vue_vue_type_script_setup_true_lang_default, [["__scopeId", "data-v-6db84711"]]);
//#endregion
//#region resources/js/Components/DataTable/SecondaryDataTable.vue?vue&type=script&setup=true&lang.ts
var SecondaryDataTable_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "SecondaryDataTable",
	__ssrInlineRender: true,
	props: /* @__PURE__ */ mergeModels({
		tableKey: {},
		title: {},
		description: {},
		data: {},
		columns: {},
		meta: { default: () => ({}) },
		filters: { default: () => ({}) },
		loading: { type: Boolean },
		getRowId: {
			type: Function,
			default: (row) => row.id
		},
		initialColumnVisibility: { default: () => ({}) },
		perPageItems: { default: () => [
			10,
			20,
			50,
			100
		] },
		searchable: {
			type: Boolean,
			default: true
		},
		selectable: {
			type: Boolean,
			default: false
		},
		stripedRows: {
			type: Boolean,
			default: true
		},
		pagination: {
			type: Boolean,
			default: false
		},
		infiniteScroll: {
			type: Boolean,
			default: true
		},
		maxHeight: { default: "28rem" },
		serverSearchDelay: { default: 300 },
		rowMenuItems: {
			type: Function,
			default: void 0
		},
		views: { default: () => [] },
		viewFilterKey: { default: "view" },
		labels: { default: () => ({
			search: "Search...",
			columns: "Columns",
			selectedWord: "selected",
			connectingWord: "of",
			unselect: "Unselect",
			loadingMore: "Loading...",
			endReached: "All elements are displayed."
		}) },
		ui: { default: () => ({}) }
	}, {
		"rowSelection": { default: {} },
		"rowSelectionModifiers": {}
	}),
	emits: /* @__PURE__ */ mergeModels(["reload", "contextMenu"], ["update:rowSelection"]),
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emit = __emit;
		const table = useTemplateRef("table");
		const UCheckbox = _sfc_main$14;
		const rowSelection = useModel(__props, "rowSelection");
		const columnVisibility = ref({ ...props.initialColumnVisibility });
		const localRows = ref([...props.data]);
		const pendingAppend = ref(false);
		const searchTimeout = ref(null);
		const currentContextMenuItems = ref([]);
		const prefix = computed(() => `${props.tableKey}_`);
		function scopedParam(key) {
			return key.startsWith(prefix.value) ? key : `${prefix.value}${key}`;
		}
		const pageParam = computed(() => scopedParam("page"));
		const perPageParam = computed(() => scopedParam("per_page"));
		const searchParam = computed(() => scopedParam("search"));
		const viewParam = computed(() => scopedParam(props.viewFilterKey));
		const searchValue = ref(props.filters[searchParam.value] ?? "");
		const perPageSelectValue = ref(props.filters[perPageParam.value] ?? props.meta.per_page ?? props.perPageItems[0]);
		const currentPage = computed(() => Number(props.meta.current_page ?? props.filters[pageParam.value] ?? 1));
		const perPage = computed(() => Number(props.meta.per_page ?? perPageSelectValue.value));
		const total = computed(() => Number(props.meta.total ?? localRows.value.length));
		const lastPage = computed(() => Number(props.meta.last_page ?? Math.ceil(total.value / perPage.value) ?? 1));
		const hasMore = computed(() => currentPage.value < lastPage.value && localRows.value.length < total.value);
		const allRowsLoaded = computed(() => total.value <= localRows.value.length);
		const totalSelected = computed(() => Object.keys(rowSelection.value ?? {}).length);
		const hasViews = computed(() => props.views.length > 0);
		const currentTableView = computed(() => {
			return props.filters?.[viewParam.value] ?? props.views[0]?.value;
		});
		const searchableKeys = computed(() => {
			return props.columns.map((column) => column.accessorKey ?? column.id).filter((key) => key && key !== "actions");
		});
		const displayedRows = computed(() => {
			if (!props.searchable || !allRowsLoaded.value || !searchValue.value) return localRows.value;
			const needle = String(searchValue.value).toLowerCase();
			return localRows.value.filter((row) => {
				return searchableKeys.value.some((key) => {
					const value = row[key];
					return value !== null && value !== void 0 && String(value).toLowerCase().includes(needle);
				});
			});
		});
		const selectedRows = computed(() => {
			return localRows.value.filter((row) => rowSelection.value[String(props.getRowId(row))]);
		});
		const tableColumns = computed(() => {
			if (!props.selectable) return props.columns;
			return [{
				id: "select",
				header: ({ table }) => h(UCheckbox, {
					modelValue: table.getIsSomePageRowsSelected() ? "indeterminate" : table.getIsAllPageRowsSelected(),
					"onUpdate:modelValue": (value) => table.toggleAllRowsSelected(!!value),
					"aria-label": "Select all"
				}),
				cell: ({ row }) => h(UCheckbox, {
					modelValue: row.getIsSelected(),
					"onUpdate:modelValue": (value) => row.toggleSelected(!!value),
					"aria-label": "Select row"
				}),
				enableHiding: false
			}, ...props.columns];
		});
		const tableMeta = computed(() => ({ class: { tr: (row) => [props.stripedRows ? row.index % 2 === 0 ? "bg-white/2" : "bg-white/5" : "", "hover:bg-white/10 transition-colors"].join(" ") } }));
		const defaultUi = {
			root: "min-h-0",
			base: "min-w-[760px] w-full table-auto relative",
			thead: "sticky top-0 bg-darknight",
			tbody: "align-top",
			tr: "h-auto"
		};
		watch(() => props.data, (rows) => {
			if (props.pagination || !pendingAppend.value || currentPage.value <= 1) localRows.value = [...rows];
			else {
				const existingIds = new Set(localRows.value.map((row) => String(props.getRowId(row))));
				const nextRows = rows.filter((row) => !existingIds.has(String(props.getRowId(row))));
				localRows.value = [...localRows.value, ...nextRows];
			}
			pendingAppend.value = false;
		});
		watch(() => props.filters[searchParam.value], (search) => {
			searchValue.value = search ?? "";
		});
		watch(searchValue, (search) => {
			if (!props.searchable) return;
			if (allRowsLoaded.value) return;
			if (searchTimeout.value) clearTimeout(searchTimeout.value);
			searchTimeout.value = setTimeout(() => {
				rowSelection.value = {};
				pendingAppend.value = false;
				emit("reload", {
					[searchParam.value]: search,
					[pageParam.value]: 1
				});
			}, props.serverSearchDelay);
		});
		watch(perPageSelectValue, (value) => {
			if (Number(value) === Number(props.filters[perPageParam.value] ?? props.meta.per_page)) return;
			rowSelection.value = {};
			pendingAppend.value = false;
			emit("reload", {
				[perPageParam.value]: value,
				[pageParam.value]: 1
			});
		});
		function changeTableView(view) {
			if (view === currentTableView.value) return;
			rowSelection.value = {};
			pendingAppend.value = false;
			localRows.value = [];
			emit("reload", {
				[viewParam.value]: view,
				[pageParam.value]: 1
			});
		}
		function goToPage(page) {
			if (Number(page) === Number(currentPage.value)) return;
			rowSelection.value = {};
			pendingAppend.value = false;
			emit("reload", { [pageParam.value]: page });
		}
		function onSelect(_, row) {
			if (!props.selectable) return;
			row.toggleSelected(!row.getIsSelected());
		}
		function onUnselectAll() {
			rowSelection.value = {};
		}
		function onContextMenu(event, row) {
			const items = row ? props.rowMenuItems?.(row) ?? [] : [];
			currentContextMenuItems.value = items;
			if (items.length === 0) {
				event.preventDefault();
				event.stopPropagation();
			}
			emit("contextMenu", event, row);
		}
		return (_ctx, _push, _parent, _attrs) => {
			const _component_UInput = _sfc_main$26;
			const _component_UTooltip = _sfc_main$40;
			const _component_UButton = _sfc_main$22;
			const _component_UDropdownMenu = _sfc_main$10;
			const _component_UContextMenu = _sfc_main$2;
			const _component_UTable = _sfc_main$4;
			const _component_USelect = _sfc_main$32;
			const _component_UPagination = _sfc_main$5;
			_push(`<section${ssrRenderAttrs(mergeProps({ class: "min-h-0" }, _attrs))}><div class="flex min-h-0 flex-col gap-3">`);
			if (__props.title || __props.description) {
				_push(`<hgroup class="min-w-0">`);
				if (__props.title) _push(`<h3 class="text-base font-semibold">${ssrInterpolate(__props.title)}</h3>`);
				else _push(`<!---->`);
				if (__props.description) _push(`<p class="text-xs text-muted">${ssrInterpolate(__props.description)}</p>`);
				else _push(`<!---->`);
				_push(`</hgroup>`);
			} else _push(`<!---->`);
			_push(`<div class="flex flex-col justify-end gap-3 md:flex-row md:items-end"><div class="flex flex-wrap items-center gap-2">`);
			if (__props.searchable) _push(ssrRenderComponent(_component_UInput, {
				modelValue: searchValue.value,
				"onUpdate:modelValue": ($event) => searchValue.value = $event,
				size: "sm",
				placeholder: __props.labels.search
			}, null, _parent));
			else _push(`<!---->`);
			if (hasViews.value) {
				_push(`<div class="flex gap-2"><!--[-->`);
				ssrRenderList(__props.views, (view) => {
					_push(ssrRenderComponent(_component_UTooltip, {
						key: view.value,
						text: view.label,
						"delay-duration": 0
					}, {
						default: withCtx((_, _push, _parent, _scopeId) => {
							if (_push) _push(ssrRenderComponent(_component_UButton, {
								icon: view.icon,
								size: "sm",
								color: currentTableView.value === view.value ? "primary" : "neutral",
								variant: currentTableView.value === view.value ? "solid" : "ghost",
								class: "cursor-pointer",
								onClick: ($event) => changeTableView(view.value)
							}, null, _parent, _scopeId));
							else return [createVNode(_component_UButton, {
								icon: view.icon,
								size: "sm",
								color: currentTableView.value === view.value ? "primary" : "neutral",
								variant: currentTableView.value === view.value ? "solid" : "ghost",
								class: "cursor-pointer",
								onClick: ($event) => changeTableView(view.value)
							}, null, 8, [
								"icon",
								"color",
								"variant",
								"onClick"
							])];
						}),
						_: 2
					}, _parent));
				});
				_push(`<!--]--></div>`);
			} else _push(`<!---->`);
			ssrRenderSlot(_ctx.$slots, "global-actions", {}, null, _push, _parent);
			_push(ssrRenderComponent(_component_UDropdownMenu, {
				items: table.value?.tableApi?.getAllColumns().filter((column) => column.getCanHide()).map((column) => ({
					label: unref(upperFirst)(column.id),
					type: "checkbox",
					checked: column.getIsVisible(),
					onUpdateChecked(checked) {
						table.value?.tableApi?.getColumn(column.id)?.toggleVisibility(!!checked);
					},
					onSelect(event) {
						event.preventDefault();
					}
				})),
				ui: { content: "z-99" }
			}, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(ssrRenderComponent(_component_UButton, {
						size: "sm",
						label: __props.labels.columns,
						color: "neutral",
						variant: "outline",
						"trailing-icon": "i-lucide-chevron-down",
						onClick: () => {}
					}, null, _parent, _scopeId));
					else return [createVNode(_component_UButton, {
						size: "sm",
						label: __props.labels.columns,
						color: "neutral",
						variant: "outline",
						"trailing-icon": "i-lucide-chevron-down",
						onClick: withModifiers(() => {}, ["stop"])
					}, null, 8, ["label", "onClick"])];
				}),
				_: 1
			}, _parent));
			_push(`</div></div><div class="min-h-0 overflow-auto rounded-md border border-white/10" style="${ssrRenderStyle({ maxHeight: __props.maxHeight })}">`);
			_push(ssrRenderComponent(_component_UContextMenu, { items: currentContextMenuItems.value }, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(ssrRenderComponent(_component_UTable, {
						ref_key: "table",
						ref: table,
						"row-selection": rowSelection.value,
						"onUpdate:rowSelection": ($event) => rowSelection.value = $event,
						"column-visibility": columnVisibility.value,
						"onUpdate:columnVisibility": ($event) => columnVisibility.value = $event,
						data: displayedRows.value,
						columns: tableColumns.value,
						"get-row-id": __props.getRowId,
						loading: __props.loading,
						meta: tableMeta.value,
						ui: {
							...defaultUi,
							...__props.ui
						},
						onContextmenu: onContextMenu,
						onSelect
					}, createSlots({ _: 2 }, [renderList(_ctx.$slots, (_, slotName) => {
						return {
							name: slotName,
							fn: withCtx((slotProps, _push, _parent, _scopeId) => {
								if (_push) ssrRenderSlot(_ctx.$slots, slotName, slotProps, null, _push, _parent, _scopeId);
								else return [renderSlot(_ctx.$slots, slotName, slotProps)];
							})
						};
					})]), _parent, _scopeId));
					else return [createVNode(_component_UTable, {
						ref_key: "table",
						ref: table,
						"row-selection": rowSelection.value,
						"onUpdate:rowSelection": ($event) => rowSelection.value = $event,
						"column-visibility": columnVisibility.value,
						"onUpdate:columnVisibility": ($event) => columnVisibility.value = $event,
						data: displayedRows.value,
						columns: tableColumns.value,
						"get-row-id": __props.getRowId,
						loading: __props.loading,
						meta: tableMeta.value,
						ui: {
							...defaultUi,
							...__props.ui
						},
						onContextmenu: onContextMenu,
						onSelect
					}, createSlots({ _: 2 }, [renderList(_ctx.$slots, (_, slotName) => {
						return {
							name: slotName,
							fn: withCtx((slotProps) => [renderSlot(_ctx.$slots, slotName, slotProps)])
						};
					})]), 1032, [
						"row-selection",
						"onUpdate:rowSelection",
						"column-visibility",
						"onUpdate:columnVisibility",
						"data",
						"columns",
						"get-row-id",
						"loading",
						"meta",
						"ui"
					])];
				}),
				_: 3
			}, _parent));
			if (!__props.pagination && __props.infiniteScroll) {
				_push(`<div class="flex items-center justify-center px-3 py-2 text-xs text-muted">`);
				if (__props.loading) _push(`<span>${ssrInterpolate(__props.labels.loadingMore)}</span>`);
				else if (!hasMore.value) _push(`<span>${ssrInterpolate(__props.labels.endReached)}</span>`);
				else _push(`<!---->`);
				_push(`</div>`);
			} else _push(`<!---->`);
			_push(`</div>`);
			if (__props.selectable && totalSelected.value > 0) {
				_push(`<div class="flex flex-wrap items-center justify-between gap-2"><div class="flex flex-wrap items-center gap-2">`);
				ssrRenderSlot(_ctx.$slots, "bulk-actions", {
					selectedRows: selectedRows.value,
					rowSelection: rowSelection.value,
					unselectAll: onUnselectAll
				}, null, _push, _parent);
				_push(`</div><div class="flex items-center gap-3 text-sm">`);
				_push(ssrRenderComponent(_component_UButton, {
					size: "sm",
					variant: "outline",
					label: __props.labels.unselect,
					class: "cursor-pointer",
					onClick: onUnselectAll
				}, null, _parent));
				_push(`<p>${ssrInterpolate(totalSelected.value)} ${ssrInterpolate(__props.labels.connectingWord)} ${ssrInterpolate(total.value)} ${ssrInterpolate(__props.labels.selectedWord)}</p></div></div>`);
			} else _push(`<!---->`);
			if (__props.pagination) {
				_push(`<div class="flex flex-wrap items-center justify-center gap-4">`);
				_push(ssrRenderComponent(_component_USelect, {
					modelValue: perPageSelectValue.value,
					"onUpdate:modelValue": ($event) => perPageSelectValue.value = $event,
					size: "sm",
					items: __props.perPageItems,
					disabled: __props.loading
				}, null, _parent));
				_push(ssrRenderComponent(_component_UPagination, {
					page: currentPage.value,
					"items-per-page": perPage.value,
					total: total.value,
					"onUpdate:page": goToPage
				}, null, _parent));
				_push(`</div>`);
			} else _push(`<!---->`);
			_push(`</div></section>`);
		};
	}
});
//#endregion
//#region resources/js/Components/DataTable/SecondaryDataTable.vue
var _sfc_setup$7 = SecondaryDataTable_vue_vue_type_script_setup_true_lang_default.setup;
SecondaryDataTable_vue_vue_type_script_setup_true_lang_default.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/DataTable/SecondaryDataTable.vue");
	return _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
var SecondaryDataTable_default = SecondaryDataTable_vue_vue_type_script_setup_true_lang_default;
//#endregion
//#region resources/js/utils/data/countries.js
var countries = [
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
];
//#endregion
//#region resources/js/utils/http.js
function csrfToken() {
	return document.querySelector("meta[name=\"csrf-token\"]")?.getAttribute("content") ?? "";
}
function csrfHeaders() {
	return {
		Accept: "application/json",
		"X-CSRF-TOKEN": csrfToken()
	};
}
//#endregion
//#region resources/js/utils/data/table.js
function getHeader(column, label, components) {
	const { UDropdownMenu, UButton } = components;
	const isSorted = column.getIsSorted();
	return h(UDropdownMenu, {
		content: { align: "start" },
		ui: { content: "z-100" },
		"aria-label": "Actions dropdown",
		items: [{
			label: "Asc",
			type: "checkbox",
			icon: "i-lucide-arrow-up-narrow-wide",
			checked: isSorted === "asc",
			onSelect: () => {
				if (isSorted === "asc") column.clearSorting();
				else column.toggleSorting(false);
			}
		}, {
			label: "Desc",
			icon: "i-lucide-arrow-down-wide-narrow",
			type: "checkbox",
			checked: isSorted === "desc",
			onSelect: () => {
				if (isSorted === "desc") column.clearSorting();
				else column.toggleSorting(true);
			}
		}]
	}, () => h(UButton, {
		color: "neutral",
		variant: "ghost",
		label,
		icon: isSorted ? isSorted === "asc" ? "i-lucide-arrow-up-narrow-wide" : "i-lucide-arrow-down-wide-narrow" : "i-lucide-arrow-up-down",
		class: "-mx-2.5 data-[state=open]:bg-elevated",
		"aria-label": `Sort by ${isSorted === "asc" ? "descending" : "ascending"}`
	}));
}
//#endregion
//#region resources/js/utils/date.js
function serializeDate(value) {
	return value?.toString ? value.toString() : value;
}
//#endregion
//#region resources/js/utils/string.js
function truncate(str, maxLength) {
	if (str.length > maxLength) return str.slice(0, maxLength) + "...";
	return str;
}
//#endregion
//#region virtual:nuxt-ui-templates/ui/calendar.ts
var calendar_default = {
	"slots": {
		"root": "",
		"header": "flex items-center justify-between",
		"body": "flex flex-col space-y-4 pt-4 sm:flex-row sm:space-x-4 sm:space-y-0",
		"heading": "text-center font-medium truncate mx-auto",
		"grid": "w-full border-collapse select-none space-y-1 focus:outline-none",
		"gridRow": "grid grid-cols-7 place-items-center",
		"gridWeekDaysRow": "mb-1 grid w-full grid-cols-7",
		"gridBody": "grid",
		"headCell": "rounded-md",
		"headCellWeek": "rounded-md text-muted",
		"cell": "relative text-center",
		"cellTrigger": ["m-0.5 relative flex items-center justify-center rounded-full whitespace-nowrap focus-visible:ring-2 focus:outline-none data-disabled:text-muted data-unavailable:line-through data-unavailable:text-muted data-unavailable:pointer-events-none data-today:font-semibold data-[outside-view]:text-muted", "transition"],
		"cellWeek": "relative text-center text-muted"
	},
	"variants": {
		"color": {
			"primary": {
				"headCell": "text-primary",
				"cellTrigger": "focus-visible:ring-primary"
			},
			"secondary": {
				"headCell": "text-secondary",
				"cellTrigger": "focus-visible:ring-secondary"
			},
			"success": {
				"headCell": "text-success",
				"cellTrigger": "focus-visible:ring-success"
			},
			"info": {
				"headCell": "text-info",
				"cellTrigger": "focus-visible:ring-info"
			},
			"warning": {
				"headCell": "text-warning",
				"cellTrigger": "focus-visible:ring-warning"
			},
			"error": {
				"headCell": "text-error",
				"cellTrigger": "focus-visible:ring-error"
			},
			"neutral": {
				"headCell": "text-highlighted",
				"cellTrigger": "focus-visible:ring-inverted"
			}
		},
		"variant": {
			"solid": "",
			"outline": "",
			"soft": "",
			"subtle": ""
		},
		"size": {
			"xs": {
				"heading": "text-xs",
				"cell": "text-xs",
				"cellWeek": "text-xs",
				"headCell": "text-[10px]",
				"headCellWeek": "text-[10px]",
				"cellTrigger": "size-7",
				"body": "space-y-2 pt-2"
			},
			"sm": {
				"heading": "text-xs",
				"headCell": "text-xs",
				"headCellWeek": "text-xs",
				"cellWeek": "text-xs",
				"cell": "text-xs",
				"cellTrigger": "size-7"
			},
			"md": {
				"heading": "text-sm",
				"headCell": "text-xs",
				"headCellWeek": "text-xs",
				"cellWeek": "text-xs",
				"cell": "text-sm",
				"cellTrigger": "size-8"
			},
			"lg": {
				"heading": "text-md",
				"headCell": "text-md",
				"headCellWeek": "text-md",
				"cellTrigger": "size-9 text-md"
			},
			"xl": {
				"heading": "text-lg",
				"headCell": "text-lg",
				"headCellWeek": "text-lg",
				"cellTrigger": "size-10 text-lg"
			}
		},
		"weekNumbers": { "true": {
			"gridRow": "grid-cols-8",
			"gridWeekDaysRow": "grid-cols-8 [&>*:first-child]:col-start-2"
		} }
	},
	"compoundVariants": [
		{
			"color": "primary",
			"variant": "solid",
			"class": { "cellTrigger": "data-[selected]:bg-primary data-[selected]:text-inverted data-today:not-data-[selected]:text-primary data-[highlighted]:bg-primary/20 hover:not-data-[selected]:bg-primary/20" }
		},
		{
			"color": "secondary",
			"variant": "solid",
			"class": { "cellTrigger": "data-[selected]:bg-secondary data-[selected]:text-inverted data-today:not-data-[selected]:text-secondary data-[highlighted]:bg-secondary/20 hover:not-data-[selected]:bg-secondary/20" }
		},
		{
			"color": "success",
			"variant": "solid",
			"class": { "cellTrigger": "data-[selected]:bg-success data-[selected]:text-inverted data-today:not-data-[selected]:text-success data-[highlighted]:bg-success/20 hover:not-data-[selected]:bg-success/20" }
		},
		{
			"color": "info",
			"variant": "solid",
			"class": { "cellTrigger": "data-[selected]:bg-info data-[selected]:text-inverted data-today:not-data-[selected]:text-info data-[highlighted]:bg-info/20 hover:not-data-[selected]:bg-info/20" }
		},
		{
			"color": "warning",
			"variant": "solid",
			"class": { "cellTrigger": "data-[selected]:bg-warning data-[selected]:text-inverted data-today:not-data-[selected]:text-warning data-[highlighted]:bg-warning/20 hover:not-data-[selected]:bg-warning/20" }
		},
		{
			"color": "error",
			"variant": "solid",
			"class": { "cellTrigger": "data-[selected]:bg-error data-[selected]:text-inverted data-today:not-data-[selected]:text-error data-[highlighted]:bg-error/20 hover:not-data-[selected]:bg-error/20" }
		},
		{
			"color": "primary",
			"variant": "outline",
			"class": { "cellTrigger": "data-[selected]:ring data-[selected]:ring-inset data-[selected]:ring-primary/50 data-[selected]:text-primary data-today:not-data-[selected]:text-primary data-[highlighted]:bg-primary/10 hover:not-data-[selected]:bg-primary/10" }
		},
		{
			"color": "secondary",
			"variant": "outline",
			"class": { "cellTrigger": "data-[selected]:ring data-[selected]:ring-inset data-[selected]:ring-secondary/50 data-[selected]:text-secondary data-today:not-data-[selected]:text-secondary data-[highlighted]:bg-secondary/10 hover:not-data-[selected]:bg-secondary/10" }
		},
		{
			"color": "success",
			"variant": "outline",
			"class": { "cellTrigger": "data-[selected]:ring data-[selected]:ring-inset data-[selected]:ring-success/50 data-[selected]:text-success data-today:not-data-[selected]:text-success data-[highlighted]:bg-success/10 hover:not-data-[selected]:bg-success/10" }
		},
		{
			"color": "info",
			"variant": "outline",
			"class": { "cellTrigger": "data-[selected]:ring data-[selected]:ring-inset data-[selected]:ring-info/50 data-[selected]:text-info data-today:not-data-[selected]:text-info data-[highlighted]:bg-info/10 hover:not-data-[selected]:bg-info/10" }
		},
		{
			"color": "warning",
			"variant": "outline",
			"class": { "cellTrigger": "data-[selected]:ring data-[selected]:ring-inset data-[selected]:ring-warning/50 data-[selected]:text-warning data-today:not-data-[selected]:text-warning data-[highlighted]:bg-warning/10 hover:not-data-[selected]:bg-warning/10" }
		},
		{
			"color": "error",
			"variant": "outline",
			"class": { "cellTrigger": "data-[selected]:ring data-[selected]:ring-inset data-[selected]:ring-error/50 data-[selected]:text-error data-today:not-data-[selected]:text-error data-[highlighted]:bg-error/10 hover:not-data-[selected]:bg-error/10" }
		},
		{
			"color": "primary",
			"variant": "soft",
			"class": { "cellTrigger": "data-[selected]:bg-primary/10 data-[selected]:text-primary data-today:not-data-[selected]:text-primary data-[highlighted]:bg-primary/20 hover:not-data-[selected]:bg-primary/20" }
		},
		{
			"color": "secondary",
			"variant": "soft",
			"class": { "cellTrigger": "data-[selected]:bg-secondary/10 data-[selected]:text-secondary data-today:not-data-[selected]:text-secondary data-[highlighted]:bg-secondary/20 hover:not-data-[selected]:bg-secondary/20" }
		},
		{
			"color": "success",
			"variant": "soft",
			"class": { "cellTrigger": "data-[selected]:bg-success/10 data-[selected]:text-success data-today:not-data-[selected]:text-success data-[highlighted]:bg-success/20 hover:not-data-[selected]:bg-success/20" }
		},
		{
			"color": "info",
			"variant": "soft",
			"class": { "cellTrigger": "data-[selected]:bg-info/10 data-[selected]:text-info data-today:not-data-[selected]:text-info data-[highlighted]:bg-info/20 hover:not-data-[selected]:bg-info/20" }
		},
		{
			"color": "warning",
			"variant": "soft",
			"class": { "cellTrigger": "data-[selected]:bg-warning/10 data-[selected]:text-warning data-today:not-data-[selected]:text-warning data-[highlighted]:bg-warning/20 hover:not-data-[selected]:bg-warning/20" }
		},
		{
			"color": "error",
			"variant": "soft",
			"class": { "cellTrigger": "data-[selected]:bg-error/10 data-[selected]:text-error data-today:not-data-[selected]:text-error data-[highlighted]:bg-error/20 hover:not-data-[selected]:bg-error/20" }
		},
		{
			"color": "primary",
			"variant": "subtle",
			"class": { "cellTrigger": "data-[selected]:bg-primary/10 data-[selected]:text-primary data-[selected]:ring data-[selected]:ring-inset data-[selected]:ring-primary/25 data-today:not-data-[selected]:text-primary data-[highlighted]:bg-primary/20 hover:not-data-[selected]:bg-primary/20" }
		},
		{
			"color": "secondary",
			"variant": "subtle",
			"class": { "cellTrigger": "data-[selected]:bg-secondary/10 data-[selected]:text-secondary data-[selected]:ring data-[selected]:ring-inset data-[selected]:ring-secondary/25 data-today:not-data-[selected]:text-secondary data-[highlighted]:bg-secondary/20 hover:not-data-[selected]:bg-secondary/20" }
		},
		{
			"color": "success",
			"variant": "subtle",
			"class": { "cellTrigger": "data-[selected]:bg-success/10 data-[selected]:text-success data-[selected]:ring data-[selected]:ring-inset data-[selected]:ring-success/25 data-today:not-data-[selected]:text-success data-[highlighted]:bg-success/20 hover:not-data-[selected]:bg-success/20" }
		},
		{
			"color": "info",
			"variant": "subtle",
			"class": { "cellTrigger": "data-[selected]:bg-info/10 data-[selected]:text-info data-[selected]:ring data-[selected]:ring-inset data-[selected]:ring-info/25 data-today:not-data-[selected]:text-info data-[highlighted]:bg-info/20 hover:not-data-[selected]:bg-info/20" }
		},
		{
			"color": "warning",
			"variant": "subtle",
			"class": { "cellTrigger": "data-[selected]:bg-warning/10 data-[selected]:text-warning data-[selected]:ring data-[selected]:ring-inset data-[selected]:ring-warning/25 data-today:not-data-[selected]:text-warning data-[highlighted]:bg-warning/20 hover:not-data-[selected]:bg-warning/20" }
		},
		{
			"color": "error",
			"variant": "subtle",
			"class": { "cellTrigger": "data-[selected]:bg-error/10 data-[selected]:text-error data-[selected]:ring data-[selected]:ring-inset data-[selected]:ring-error/25 data-today:not-data-[selected]:text-error data-[highlighted]:bg-error/20 hover:not-data-[selected]:bg-error/20" }
		},
		{
			"color": "neutral",
			"variant": "solid",
			"class": { "cellTrigger": "data-[selected]:bg-inverted data-[selected]:text-inverted data-today:not-data-[selected]:text-highlighted data-[highlighted]:bg-inverted/20 hover:not-data-[selected]:bg-inverted/10" }
		},
		{
			"color": "neutral",
			"variant": "outline",
			"class": { "cellTrigger": "data-[selected]:ring data-[selected]:ring-inset data-[selected]:ring-accented data-[selected]:text-default data-[selected]:bg-default data-today:not-data-[selected]:text-highlighted data-[highlighted]:bg-inverted/10 hover:not-data-[selected]:bg-inverted/10" }
		},
		{
			"color": "neutral",
			"variant": "soft",
			"class": { "cellTrigger": "data-[selected]:bg-elevated data-[selected]:text-default data-today:not-data-[selected]:text-highlighted data-[highlighted]:bg-inverted/20 hover:not-data-[selected]:bg-inverted/10" }
		},
		{
			"color": "neutral",
			"variant": "subtle",
			"class": { "cellTrigger": "data-[selected]:bg-elevated data-[selected]:text-default data-[selected]:ring data-[selected]:ring-inset data-[selected]:ring-accented data-today:not-data-[selected]:text-highlighted data-[highlighted]:bg-inverted/20 hover:not-data-[selected]:bg-inverted/10" }
		}
	],
	"defaultVariants": {
		"size": "md",
		"color": "primary",
		"variant": "solid"
	}
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Calendar.vue
var _sfc_main$1 = {
	__name: "Calendar",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false
		},
		nextYearIcon: {
			type: null,
			required: false
		},
		nextYear: {
			type: Object,
			required: false
		},
		nextMonthIcon: {
			type: null,
			required: false
		},
		nextMonth: {
			type: Object,
			required: false
		},
		prevYearIcon: {
			type: null,
			required: false
		},
		prevYear: {
			type: Object,
			required: false
		},
		prevMonthIcon: {
			type: null,
			required: false
		},
		prevMonth: {
			type: Object,
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
		range: {
			type: Boolean,
			required: false
		},
		multiple: {
			type: Boolean,
			required: false
		},
		monthControls: {
			type: Boolean,
			required: false,
			default: true
		},
		yearControls: {
			type: Boolean,
			required: false,
			default: true
		},
		defaultValue: {
			type: null,
			required: false
		},
		modelValue: {
			type: null,
			required: false
		},
		weekNumbers: {
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
		defaultPlaceholder: {
			type: Object,
			required: false
		},
		placeholder: {
			type: Object,
			required: false
		},
		allowNonContiguousRanges: {
			type: Boolean,
			required: false
		},
		pagedNavigation: {
			type: Boolean,
			required: false
		},
		preventDeselect: {
			type: Boolean,
			required: false
		},
		maximumDays: {
			type: Number,
			required: false
		},
		weekStartsOn: {
			type: Number,
			required: false
		},
		weekdayFormat: {
			type: String,
			required: false
		},
		fixedWeeks: {
			type: Boolean,
			required: false,
			default: true
		},
		maxValue: {
			type: Object,
			required: false
		},
		minValue: {
			type: Object,
			required: false
		},
		numberOfMonths: {
			type: Number,
			required: false
		},
		disabled: {
			type: Boolean,
			required: false
		},
		readonly: {
			type: Boolean,
			required: false
		},
		initialFocus: {
			type: Boolean,
			required: false
		},
		isDateDisabled: {
			type: Function,
			required: false
		},
		isDateUnavailable: {
			type: Function,
			required: false
		},
		isDateHighlightable: {
			type: Function,
			required: false
		},
		nextPage: {
			type: Function,
			required: false
		},
		prevPage: {
			type: Function,
			required: false
		},
		disableDaysOutsideCurrentView: {
			type: Boolean,
			required: false
		},
		fixedDate: {
			type: String,
			required: false
		}
	},
	emits: [
		"update:modelValue",
		"update:placeholder",
		"update:validModelValue",
		"update:startValue"
	],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const { dir, t, locale } = useLocale();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("calendar", props);
		const rootProps = useForwardPropsEmits(reactiveOmit(props, "range", "modelValue", "defaultValue", "color", "variant", "size", "monthControls", "yearControls", "class", "ui"), emits);
		const nextYearIcon = computed(() => props.nextYearIcon || (dir.value === "rtl" ? appConfig.ui.icons.chevronDoubleLeft : appConfig.ui.icons.chevronDoubleRight));
		const nextMonthIcon = computed(() => props.nextMonthIcon || (dir.value === "rtl" ? appConfig.ui.icons.chevronLeft : appConfig.ui.icons.chevronRight));
		const prevYearIcon = computed(() => props.prevYearIcon || (dir.value === "rtl" ? appConfig.ui.icons.chevronDoubleRight : appConfig.ui.icons.chevronDoubleLeft));
		const prevMonthIcon = computed(() => props.prevMonthIcon || (dir.value === "rtl" ? appConfig.ui.icons.chevronRight : appConfig.ui.icons.chevronLeft));
		const ui = computed(() => tv({
			extend: tv(calendar_default),
			...appConfig.ui?.calendar || {}
		})({
			color: props.color,
			size: props.size,
			variant: props.variant,
			weekNumbers: props.weekNumbers
		}));
		function paginateYear(date, sign) {
			if (sign === -1) return date.subtract({ years: 1 });
			return date.add({ years: 1 });
		}
		const Calendar$1 = computed(() => props.range ? RangeCalendar : Calendar);
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(Calendar$1).Root, mergeProps(unref(rootProps), {
				"model-value": __props.modelValue,
				"default-value": __props.defaultValue,
				"data-slot": "root",
				class: ui.value.root({ class: [unref(uiProp)?.root, props.class] })
			}, _attrs), {
				default: withCtx(({ weekDays, grid }, _push, _parent, _scopeId) => {
					if (_push) {
						_push(ssrRenderComponent(unref(Calendar$1).Header, {
							"data-slot": "header",
							class: ui.value.header({ class: unref(uiProp)?.header })
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) {
									if (props.yearControls) _push(ssrRenderComponent(unref(Calendar$1).Prev, {
										"prev-page": (date) => paginateYear(date, -1),
										"aria-label": unref(t)("calendar.prevYear"),
										"as-child": ""
									}, {
										default: withCtx((_, _push, _parent, _scopeId) => {
											if (_push) _push(ssrRenderComponent(_sfc_main$22, mergeProps({
												icon: prevYearIcon.value,
												size: props.size,
												color: "neutral",
												variant: "ghost"
											}, props.prevYear), null, _parent, _scopeId));
											else return [createVNode(_sfc_main$22, mergeProps({
												icon: prevYearIcon.value,
												size: props.size,
												color: "neutral",
												variant: "ghost"
											}, props.prevYear), null, 16, ["icon", "size"])];
										}),
										_: 2
									}, _parent, _scopeId));
									else _push(`<!---->`);
									if (props.monthControls) _push(ssrRenderComponent(unref(Calendar$1).Prev, {
										"aria-label": unref(t)("calendar.prevMonth"),
										"as-child": ""
									}, {
										default: withCtx((_, _push, _parent, _scopeId) => {
											if (_push) _push(ssrRenderComponent(_sfc_main$22, mergeProps({
												icon: prevMonthIcon.value,
												size: props.size,
												color: "neutral",
												variant: "ghost"
											}, props.prevMonth), null, _parent, _scopeId));
											else return [createVNode(_sfc_main$22, mergeProps({
												icon: prevMonthIcon.value,
												size: props.size,
												color: "neutral",
												variant: "ghost"
											}, props.prevMonth), null, 16, ["icon", "size"])];
										}),
										_: 2
									}, _parent, _scopeId));
									else _push(`<!---->`);
									_push(ssrRenderComponent(unref(Calendar$1).Heading, {
										"data-slot": "heading",
										class: ui.value.heading({ class: unref(uiProp)?.heading })
									}, {
										default: withCtx(({ headingValue }, _push, _parent, _scopeId) => {
											if (_push) ssrRenderSlot(_ctx.$slots, "heading", { value: headingValue }, () => {
												_push(`${ssrInterpolate(headingValue)}`);
											}, _push, _parent, _scopeId);
											else return [renderSlot(_ctx.$slots, "heading", { value: headingValue }, () => [createTextVNode(toDisplayString(headingValue), 1)])];
										}),
										_: 2
									}, _parent, _scopeId));
									if (props.monthControls) _push(ssrRenderComponent(unref(Calendar$1).Next, {
										"aria-label": unref(t)("calendar.nextMonth"),
										"as-child": ""
									}, {
										default: withCtx((_, _push, _parent, _scopeId) => {
											if (_push) _push(ssrRenderComponent(_sfc_main$22, mergeProps({
												icon: nextMonthIcon.value,
												size: props.size,
												color: "neutral",
												variant: "ghost"
											}, props.nextMonth), null, _parent, _scopeId));
											else return [createVNode(_sfc_main$22, mergeProps({
												icon: nextMonthIcon.value,
												size: props.size,
												color: "neutral",
												variant: "ghost"
											}, props.nextMonth), null, 16, ["icon", "size"])];
										}),
										_: 2
									}, _parent, _scopeId));
									else _push(`<!---->`);
									if (props.yearControls) _push(ssrRenderComponent(unref(Calendar$1).Next, {
										"next-page": (date) => paginateYear(date, 1),
										"aria-label": unref(t)("calendar.nextYear"),
										"as-child": ""
									}, {
										default: withCtx((_, _push, _parent, _scopeId) => {
											if (_push) _push(ssrRenderComponent(_sfc_main$22, mergeProps({
												icon: nextYearIcon.value,
												size: props.size,
												color: "neutral",
												variant: "ghost"
											}, props.nextYear), null, _parent, _scopeId));
											else return [createVNode(_sfc_main$22, mergeProps({
												icon: nextYearIcon.value,
												size: props.size,
												color: "neutral",
												variant: "ghost"
											}, props.nextYear), null, 16, ["icon", "size"])];
										}),
										_: 2
									}, _parent, _scopeId));
									else _push(`<!---->`);
								} else return [
									props.yearControls ? (openBlock(), createBlock(unref(Calendar$1).Prev, {
										key: 0,
										"prev-page": (date) => paginateYear(date, -1),
										"aria-label": unref(t)("calendar.prevYear"),
										"as-child": ""
									}, {
										default: withCtx(() => [createVNode(_sfc_main$22, mergeProps({
											icon: prevYearIcon.value,
											size: props.size,
											color: "neutral",
											variant: "ghost"
										}, props.prevYear), null, 16, ["icon", "size"])]),
										_: 1
									}, 8, ["prev-page", "aria-label"])) : createCommentVNode("", true),
									props.monthControls ? (openBlock(), createBlock(unref(Calendar$1).Prev, {
										key: 1,
										"aria-label": unref(t)("calendar.prevMonth"),
										"as-child": ""
									}, {
										default: withCtx(() => [createVNode(_sfc_main$22, mergeProps({
											icon: prevMonthIcon.value,
											size: props.size,
											color: "neutral",
											variant: "ghost"
										}, props.prevMonth), null, 16, ["icon", "size"])]),
										_: 1
									}, 8, ["aria-label"])) : createCommentVNode("", true),
									createVNode(unref(Calendar$1).Heading, {
										"data-slot": "heading",
										class: ui.value.heading({ class: unref(uiProp)?.heading })
									}, {
										default: withCtx(({ headingValue }) => [renderSlot(_ctx.$slots, "heading", { value: headingValue }, () => [createTextVNode(toDisplayString(headingValue), 1)])]),
										_: 3
									}, 8, ["class"]),
									props.monthControls ? (openBlock(), createBlock(unref(Calendar$1).Next, {
										key: 2,
										"aria-label": unref(t)("calendar.nextMonth"),
										"as-child": ""
									}, {
										default: withCtx(() => [createVNode(_sfc_main$22, mergeProps({
											icon: nextMonthIcon.value,
											size: props.size,
											color: "neutral",
											variant: "ghost"
										}, props.nextMonth), null, 16, ["icon", "size"])]),
										_: 1
									}, 8, ["aria-label"])) : createCommentVNode("", true),
									props.yearControls ? (openBlock(), createBlock(unref(Calendar$1).Next, {
										key: 3,
										"next-page": (date) => paginateYear(date, 1),
										"aria-label": unref(t)("calendar.nextYear"),
										"as-child": ""
									}, {
										default: withCtx(() => [createVNode(_sfc_main$22, mergeProps({
											icon: nextYearIcon.value,
											size: props.size,
											color: "neutral",
											variant: "ghost"
										}, props.nextYear), null, 16, ["icon", "size"])]),
										_: 1
									}, 8, ["next-page", "aria-label"])) : createCommentVNode("", true)
								];
							}),
							_: 2
						}, _parent, _scopeId));
						_push(`<div data-slot="body" class="${ssrRenderClass(ui.value.body({ class: unref(uiProp)?.body }))}"${_scopeId}><!--[-->`);
						ssrRenderList(grid, (month) => {
							_push(ssrRenderComponent(unref(Calendar$1).Grid, {
								key: month.value.toString(),
								"data-slot": "grid",
								class: ui.value.grid({ class: unref(uiProp)?.grid })
							}, {
								default: withCtx((_, _push, _parent, _scopeId) => {
									if (_push) {
										_push(ssrRenderComponent(unref(Calendar$1).GridHead, null, {
											default: withCtx((_, _push, _parent, _scopeId) => {
												if (_push) _push(ssrRenderComponent(unref(Calendar$1).GridRow, {
													"data-slot": "gridWeekDaysRow",
													class: ui.value.gridWeekDaysRow({ class: unref(uiProp)?.gridWeekDaysRow })
												}, {
													default: withCtx((_, _push, _parent, _scopeId) => {
														if (_push) {
															_push(`<!--[-->`);
															ssrRenderList(weekDays, (day) => {
																_push(ssrRenderComponent(unref(Calendar$1).HeadCell, {
																	key: day,
																	"data-slot": "headCell",
																	class: ui.value.headCell({ class: unref(uiProp)?.headCell })
																}, {
																	default: withCtx((_, _push, _parent, _scopeId) => {
																		if (_push) ssrRenderSlot(_ctx.$slots, "week-day", { day }, () => {
																			_push(`${ssrInterpolate(day)}`);
																		}, _push, _parent, _scopeId);
																		else return [renderSlot(_ctx.$slots, "week-day", { day }, () => [createTextVNode(toDisplayString(day), 1)])];
																	}),
																	_: 2
																}, _parent, _scopeId));
															});
															_push(`<!--]-->`);
														} else return [(openBlock(true), createBlock(Fragment, null, renderList(weekDays, (day) => {
															return openBlock(), createBlock(unref(Calendar$1).HeadCell, {
																key: day,
																"data-slot": "headCell",
																class: ui.value.headCell({ class: unref(uiProp)?.headCell })
															}, {
																default: withCtx(() => [renderSlot(_ctx.$slots, "week-day", { day }, () => [createTextVNode(toDisplayString(day), 1)])]),
																_: 2
															}, 1032, ["class"]);
														}), 128))];
													}),
													_: 2
												}, _parent, _scopeId));
												else return [createVNode(unref(Calendar$1).GridRow, {
													"data-slot": "gridWeekDaysRow",
													class: ui.value.gridWeekDaysRow({ class: unref(uiProp)?.gridWeekDaysRow })
												}, {
													default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(weekDays, (day) => {
														return openBlock(), createBlock(unref(Calendar$1).HeadCell, {
															key: day,
															"data-slot": "headCell",
															class: ui.value.headCell({ class: unref(uiProp)?.headCell })
														}, {
															default: withCtx(() => [renderSlot(_ctx.$slots, "week-day", { day }, () => [createTextVNode(toDisplayString(day), 1)])]),
															_: 2
														}, 1032, ["class"]);
													}), 128))]),
													_: 2
												}, 1032, ["class"])];
											}),
											_: 2
										}, _parent, _scopeId));
										_push(ssrRenderComponent(unref(Calendar$1).GridBody, {
											"data-slot": "gridBody",
											class: ui.value.gridBody({ class: unref(uiProp)?.gridBody })
										}, {
											default: withCtx((_, _push, _parent, _scopeId) => {
												if (_push) {
													_push(`<!--[-->`);
													ssrRenderList(month.rows, (weekDates, index) => {
														_push(ssrRenderComponent(unref(Calendar$1).GridRow, {
															key: `weekDate-${index}`,
															"data-slot": "gridRow",
															class: ui.value.gridRow({ class: unref(uiProp)?.gridRow })
														}, {
															default: withCtx((_, _push, _parent, _scopeId) => {
																if (_push) {
																	if (__props.weekNumbers && weekDates[0]) _push(`<td role="gridcell" data-slot="cellWeek" class="${ssrRenderClass(ui.value.cellWeek({ class: unref(uiProp)?.cellWeek }))}"${_scopeId}>${ssrInterpolate(unref(getWeekNumber)(weekDates[0], unref(locale).code))}</td>`);
																	else _push(`<!---->`);
																	_push(`<!--[-->`);
																	ssrRenderList(weekDates, (weekDate) => {
																		_push(ssrRenderComponent(unref(Calendar$1).Cell, {
																			key: weekDate.toString(),
																			date: weekDate,
																			"data-slot": "cell",
																			class: ui.value.cell({ class: unref(uiProp)?.cell })
																		}, {
																			default: withCtx((_, _push, _parent, _scopeId) => {
																				if (_push) _push(ssrRenderComponent(unref(Calendar$1).CellTrigger, {
																					day: weekDate,
																					month: month.value,
																					"data-slot": "cellTrigger",
																					class: ui.value.cellTrigger({ class: unref(uiProp)?.cellTrigger })
																				}, {
																					default: withCtx((_, _push, _parent, _scopeId) => {
																						if (_push) ssrRenderSlot(_ctx.$slots, "day", { day: weekDate }, () => {
																							_push(`${ssrInterpolate(weekDate.day)}`);
																						}, _push, _parent, _scopeId);
																						else return [renderSlot(_ctx.$slots, "day", { day: weekDate }, () => [createTextVNode(toDisplayString(weekDate.day), 1)])];
																					}),
																					_: 2
																				}, _parent, _scopeId));
																				else return [createVNode(unref(Calendar$1).CellTrigger, {
																					day: weekDate,
																					month: month.value,
																					"data-slot": "cellTrigger",
																					class: ui.value.cellTrigger({ class: unref(uiProp)?.cellTrigger })
																				}, {
																					default: withCtx(() => [renderSlot(_ctx.$slots, "day", { day: weekDate }, () => [createTextVNode(toDisplayString(weekDate.day), 1)])]),
																					_: 2
																				}, 1032, [
																					"day",
																					"month",
																					"class"
																				])];
																			}),
																			_: 2
																		}, _parent, _scopeId));
																	});
																	_push(`<!--]-->`);
																} else return [__props.weekNumbers && weekDates[0] ? (openBlock(), createBlock("td", {
																	key: 0,
																	role: "gridcell",
																	"data-slot": "cellWeek",
																	class: ui.value.cellWeek({ class: unref(uiProp)?.cellWeek })
																}, toDisplayString(unref(getWeekNumber)(weekDates[0], unref(locale).code)), 3)) : createCommentVNode("", true), (openBlock(true), createBlock(Fragment, null, renderList(weekDates, (weekDate) => {
																	return openBlock(), createBlock(unref(Calendar$1).Cell, {
																		key: weekDate.toString(),
																		date: weekDate,
																		"data-slot": "cell",
																		class: ui.value.cell({ class: unref(uiProp)?.cell })
																	}, {
																		default: withCtx(() => [createVNode(unref(Calendar$1).CellTrigger, {
																			day: weekDate,
																			month: month.value,
																			"data-slot": "cellTrigger",
																			class: ui.value.cellTrigger({ class: unref(uiProp)?.cellTrigger })
																		}, {
																			default: withCtx(() => [renderSlot(_ctx.$slots, "day", { day: weekDate }, () => [createTextVNode(toDisplayString(weekDate.day), 1)])]),
																			_: 2
																		}, 1032, [
																			"day",
																			"month",
																			"class"
																		])]),
																		_: 2
																	}, 1032, ["date", "class"]);
																}), 128))];
															}),
															_: 2
														}, _parent, _scopeId));
													});
													_push(`<!--]-->`);
												} else return [(openBlock(true), createBlock(Fragment, null, renderList(month.rows, (weekDates, index) => {
													return openBlock(), createBlock(unref(Calendar$1).GridRow, {
														key: `weekDate-${index}`,
														"data-slot": "gridRow",
														class: ui.value.gridRow({ class: unref(uiProp)?.gridRow })
													}, {
														default: withCtx(() => [__props.weekNumbers && weekDates[0] ? (openBlock(), createBlock("td", {
															key: 0,
															role: "gridcell",
															"data-slot": "cellWeek",
															class: ui.value.cellWeek({ class: unref(uiProp)?.cellWeek })
														}, toDisplayString(unref(getWeekNumber)(weekDates[0], unref(locale).code)), 3)) : createCommentVNode("", true), (openBlock(true), createBlock(Fragment, null, renderList(weekDates, (weekDate) => {
															return openBlock(), createBlock(unref(Calendar$1).Cell, {
																key: weekDate.toString(),
																date: weekDate,
																"data-slot": "cell",
																class: ui.value.cell({ class: unref(uiProp)?.cell })
															}, {
																default: withCtx(() => [createVNode(unref(Calendar$1).CellTrigger, {
																	day: weekDate,
																	month: month.value,
																	"data-slot": "cellTrigger",
																	class: ui.value.cellTrigger({ class: unref(uiProp)?.cellTrigger })
																}, {
																	default: withCtx(() => [renderSlot(_ctx.$slots, "day", { day: weekDate }, () => [createTextVNode(toDisplayString(weekDate.day), 1)])]),
																	_: 2
																}, 1032, [
																	"day",
																	"month",
																	"class"
																])]),
																_: 2
															}, 1032, ["date", "class"]);
														}), 128))]),
														_: 2
													}, 1032, ["class"]);
												}), 128))];
											}),
											_: 2
										}, _parent, _scopeId));
									} else return [createVNode(unref(Calendar$1).GridHead, null, {
										default: withCtx(() => [createVNode(unref(Calendar$1).GridRow, {
											"data-slot": "gridWeekDaysRow",
											class: ui.value.gridWeekDaysRow({ class: unref(uiProp)?.gridWeekDaysRow })
										}, {
											default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(weekDays, (day) => {
												return openBlock(), createBlock(unref(Calendar$1).HeadCell, {
													key: day,
													"data-slot": "headCell",
													class: ui.value.headCell({ class: unref(uiProp)?.headCell })
												}, {
													default: withCtx(() => [renderSlot(_ctx.$slots, "week-day", { day }, () => [createTextVNode(toDisplayString(day), 1)])]),
													_: 2
												}, 1032, ["class"]);
											}), 128))]),
											_: 2
										}, 1032, ["class"])]),
										_: 2
									}, 1024), createVNode(unref(Calendar$1).GridBody, {
										"data-slot": "gridBody",
										class: ui.value.gridBody({ class: unref(uiProp)?.gridBody })
									}, {
										default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(month.rows, (weekDates, index) => {
											return openBlock(), createBlock(unref(Calendar$1).GridRow, {
												key: `weekDate-${index}`,
												"data-slot": "gridRow",
												class: ui.value.gridRow({ class: unref(uiProp)?.gridRow })
											}, {
												default: withCtx(() => [__props.weekNumbers && weekDates[0] ? (openBlock(), createBlock("td", {
													key: 0,
													role: "gridcell",
													"data-slot": "cellWeek",
													class: ui.value.cellWeek({ class: unref(uiProp)?.cellWeek })
												}, toDisplayString(unref(getWeekNumber)(weekDates[0], unref(locale).code)), 3)) : createCommentVNode("", true), (openBlock(true), createBlock(Fragment, null, renderList(weekDates, (weekDate) => {
													return openBlock(), createBlock(unref(Calendar$1).Cell, {
														key: weekDate.toString(),
														date: weekDate,
														"data-slot": "cell",
														class: ui.value.cell({ class: unref(uiProp)?.cell })
													}, {
														default: withCtx(() => [createVNode(unref(Calendar$1).CellTrigger, {
															day: weekDate,
															month: month.value,
															"data-slot": "cellTrigger",
															class: ui.value.cellTrigger({ class: unref(uiProp)?.cellTrigger })
														}, {
															default: withCtx(() => [renderSlot(_ctx.$slots, "day", { day: weekDate }, () => [createTextVNode(toDisplayString(weekDate.day), 1)])]),
															_: 2
														}, 1032, [
															"day",
															"month",
															"class"
														])]),
														_: 2
													}, 1032, ["date", "class"]);
												}), 128))]),
												_: 2
											}, 1032, ["class"]);
										}), 128))]),
										_: 2
									}, 1032, ["class"])];
								}),
								_: 2
							}, _parent, _scopeId));
						});
						_push(`<!--]--></div>`);
					} else return [createVNode(unref(Calendar$1).Header, {
						"data-slot": "header",
						class: ui.value.header({ class: unref(uiProp)?.header })
					}, {
						default: withCtx(() => [
							props.yearControls ? (openBlock(), createBlock(unref(Calendar$1).Prev, {
								key: 0,
								"prev-page": (date) => paginateYear(date, -1),
								"aria-label": unref(t)("calendar.prevYear"),
								"as-child": ""
							}, {
								default: withCtx(() => [createVNode(_sfc_main$22, mergeProps({
									icon: prevYearIcon.value,
									size: props.size,
									color: "neutral",
									variant: "ghost"
								}, props.prevYear), null, 16, ["icon", "size"])]),
								_: 1
							}, 8, ["prev-page", "aria-label"])) : createCommentVNode("", true),
							props.monthControls ? (openBlock(), createBlock(unref(Calendar$1).Prev, {
								key: 1,
								"aria-label": unref(t)("calendar.prevMonth"),
								"as-child": ""
							}, {
								default: withCtx(() => [createVNode(_sfc_main$22, mergeProps({
									icon: prevMonthIcon.value,
									size: props.size,
									color: "neutral",
									variant: "ghost"
								}, props.prevMonth), null, 16, ["icon", "size"])]),
								_: 1
							}, 8, ["aria-label"])) : createCommentVNode("", true),
							createVNode(unref(Calendar$1).Heading, {
								"data-slot": "heading",
								class: ui.value.heading({ class: unref(uiProp)?.heading })
							}, {
								default: withCtx(({ headingValue }) => [renderSlot(_ctx.$slots, "heading", { value: headingValue }, () => [createTextVNode(toDisplayString(headingValue), 1)])]),
								_: 3
							}, 8, ["class"]),
							props.monthControls ? (openBlock(), createBlock(unref(Calendar$1).Next, {
								key: 2,
								"aria-label": unref(t)("calendar.nextMonth"),
								"as-child": ""
							}, {
								default: withCtx(() => [createVNode(_sfc_main$22, mergeProps({
									icon: nextMonthIcon.value,
									size: props.size,
									color: "neutral",
									variant: "ghost"
								}, props.nextMonth), null, 16, ["icon", "size"])]),
								_: 1
							}, 8, ["aria-label"])) : createCommentVNode("", true),
							props.yearControls ? (openBlock(), createBlock(unref(Calendar$1).Next, {
								key: 3,
								"next-page": (date) => paginateYear(date, 1),
								"aria-label": unref(t)("calendar.nextYear"),
								"as-child": ""
							}, {
								default: withCtx(() => [createVNode(_sfc_main$22, mergeProps({
									icon: nextYearIcon.value,
									size: props.size,
									color: "neutral",
									variant: "ghost"
								}, props.nextYear), null, 16, ["icon", "size"])]),
								_: 1
							}, 8, ["next-page", "aria-label"])) : createCommentVNode("", true)
						]),
						_: 3
					}, 8, ["class"]), createVNode("div", {
						"data-slot": "body",
						class: ui.value.body({ class: unref(uiProp)?.body })
					}, [(openBlock(true), createBlock(Fragment, null, renderList(grid, (month) => {
						return openBlock(), createBlock(unref(Calendar$1).Grid, {
							key: month.value.toString(),
							"data-slot": "grid",
							class: ui.value.grid({ class: unref(uiProp)?.grid })
						}, {
							default: withCtx(() => [createVNode(unref(Calendar$1).GridHead, null, {
								default: withCtx(() => [createVNode(unref(Calendar$1).GridRow, {
									"data-slot": "gridWeekDaysRow",
									class: ui.value.gridWeekDaysRow({ class: unref(uiProp)?.gridWeekDaysRow })
								}, {
									default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(weekDays, (day) => {
										return openBlock(), createBlock(unref(Calendar$1).HeadCell, {
											key: day,
											"data-slot": "headCell",
											class: ui.value.headCell({ class: unref(uiProp)?.headCell })
										}, {
											default: withCtx(() => [renderSlot(_ctx.$slots, "week-day", { day }, () => [createTextVNode(toDisplayString(day), 1)])]),
											_: 2
										}, 1032, ["class"]);
									}), 128))]),
									_: 2
								}, 1032, ["class"])]),
								_: 2
							}, 1024), createVNode(unref(Calendar$1).GridBody, {
								"data-slot": "gridBody",
								class: ui.value.gridBody({ class: unref(uiProp)?.gridBody })
							}, {
								default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(month.rows, (weekDates, index) => {
									return openBlock(), createBlock(unref(Calendar$1).GridRow, {
										key: `weekDate-${index}`,
										"data-slot": "gridRow",
										class: ui.value.gridRow({ class: unref(uiProp)?.gridRow })
									}, {
										default: withCtx(() => [__props.weekNumbers && weekDates[0] ? (openBlock(), createBlock("td", {
											key: 0,
											role: "gridcell",
											"data-slot": "cellWeek",
											class: ui.value.cellWeek({ class: unref(uiProp)?.cellWeek })
										}, toDisplayString(unref(getWeekNumber)(weekDates[0], unref(locale).code)), 3)) : createCommentVNode("", true), (openBlock(true), createBlock(Fragment, null, renderList(weekDates, (weekDate) => {
											return openBlock(), createBlock(unref(Calendar$1).Cell, {
												key: weekDate.toString(),
												date: weekDate,
												"data-slot": "cell",
												class: ui.value.cell({ class: unref(uiProp)?.cell })
											}, {
												default: withCtx(() => [createVNode(unref(Calendar$1).CellTrigger, {
													day: weekDate,
													month: month.value,
													"data-slot": "cellTrigger",
													class: ui.value.cellTrigger({ class: unref(uiProp)?.cellTrigger })
												}, {
													default: withCtx(() => [renderSlot(_ctx.$slots, "day", { day: weekDate }, () => [createTextVNode(toDisplayString(weekDate.day), 1)])]),
													_: 2
												}, 1032, [
													"day",
													"month",
													"class"
												])]),
												_: 2
											}, 1032, ["date", "class"]);
										}), 128))]),
										_: 2
									}, 1032, ["class"]);
								}), 128))]),
								_: 2
							}, 1032, ["class"])]),
							_: 2
						}, 1032, ["class"]);
					}), 128))], 2)];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$6 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Calendar.vue");
	return _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/input-date.ts
var input_date_default = {
	"slots": {
		"base": ["group relative inline-flex items-center rounded-md select-none", "transition-colors"],
		"leading": "absolute inset-y-0 start-0 flex items-center",
		"leadingIcon": "shrink-0 text-dimmed",
		"leadingAvatar": "shrink-0",
		"leadingAvatarSize": "",
		"trailing": "absolute inset-y-0 end-0 flex items-center",
		"trailingIcon": "shrink-0 text-dimmed",
		"segment": ["rounded text-center outline-hidden data-placeholder:text-dimmed data-[segment=literal]:text-muted data-invalid:text-error data-disabled:cursor-not-allowed data-disabled:opacity-75", "transition-colors"],
		"separatorIcon": "shrink-0 size-4 text-muted"
	},
	"variants": {
		"fieldGroup": {
			"horizontal": "not-only:first:rounded-e-none not-only:last:rounded-s-none not-last:not-first:rounded-none focus-visible:z-[1]",
			"vertical": "not-only:first:rounded-b-none not-only:last:rounded-t-none not-last:not-first:rounded-none focus-visible:z-[1]"
		},
		"size": {
			"xs": {
				"base": ["px-2 py-1 text-sm/4 gap-1", "gap-0.25"],
				"leading": "ps-2",
				"trailing": "pe-2",
				"leadingIcon": "size-4",
				"leadingAvatarSize": "3xs",
				"trailingIcon": "size-4",
				"segment": "data-[segment=day]:w-8 data-[segment=month]:w-8 data-[segment=year]:w-10"
			},
			"sm": {
				"base": ["px-2.5 py-1.5 text-sm/4 gap-1.5", "gap-0.5"],
				"leading": "ps-2.5",
				"trailing": "pe-2.5",
				"leadingIcon": "size-4",
				"leadingAvatarSize": "3xs",
				"trailingIcon": "size-4",
				"segment": "data-[segment=day]:w-8 data-[segment=month]:w-8 data-[segment=year]:w-10"
			},
			"md": {
				"base": ["px-2.5 py-1.5 text-base/5 gap-1.5", "gap-0.5"],
				"leading": "ps-2.5",
				"trailing": "pe-2.5",
				"leadingIcon": "size-5",
				"leadingAvatarSize": "2xs",
				"trailingIcon": "size-5",
				"segment": "data-[segment=day]:w-9 data-[segment=month]:w-9 data-[segment=year]:w-11"
			},
			"lg": {
				"base": ["px-3 py-2 text-base/5 gap-2", "gap-0.75"],
				"leading": "ps-3",
				"trailing": "pe-3",
				"leadingIcon": "size-5",
				"leadingAvatarSize": "2xs",
				"trailingIcon": "size-5",
				"segment": "data-[segment=day]:w-9 data-[segment=month]:w-9 data-[segment=year]:w-11"
			},
			"xl": {
				"base": ["px-3 py-2 text-base gap-2", "gap-0.75"],
				"leading": "ps-3",
				"trailing": "pe-3",
				"leadingIcon": "size-6",
				"leadingAvatarSize": "xs",
				"trailingIcon": "size-6",
				"segment": "data-[segment=day]:w-10 data-[segment=month]:w-10 data-[segment=year]:w-12"
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
			"variant": "outline",
			"class": { "segment": "focus:bg-elevated" }
		},
		{
			"variant": "soft",
			"class": { "segment": "focus:bg-accented/50 group-hover:focus:bg-accented" }
		},
		{
			"variant": "subtle",
			"class": { "segment": "focus:bg-accented" }
		},
		{
			"variant": "ghost",
			"class": { "segment": "focus:bg-elevated group-hover:focus:bg-accented" }
		},
		{
			"variant": "none",
			"class": { "segment": "focus:bg-elevated" }
		},
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
//#region node_modules/@nuxt/ui/dist/runtime/components/InputDate.vue
var _sfc_main = /* @__PURE__ */ Object.assign({ inheritAttrs: false }, {
	__name: "InputDate",
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
		highlight: {
			type: Boolean,
			required: false
		},
		fixed: {
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
		separatorIcon: {
			type: null,
			required: false
		},
		range: {
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
		defaultPlaceholder: {
			type: Object,
			required: false
		},
		placeholder: {
			type: Object,
			required: false
		},
		hourCycle: {
			type: null,
			required: false
		},
		step: {
			type: Object,
			required: false
		},
		granularity: {
			type: String,
			required: false
		},
		hideTimeZone: {
			type: Boolean,
			required: false
		},
		maxValue: {
			type: Object,
			required: false
		},
		minValue: {
			type: Object,
			required: false
		},
		disabled: {
			type: Boolean,
			required: false
		},
		readonly: {
			type: Boolean,
			required: false
		},
		isDateUnavailable: {
			type: Function,
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
		required: {
			type: Boolean,
			required: false
		}
	},
	emits: [
		"update:modelValue",
		"change",
		"blur",
		"focus",
		"update:placeholder"
	],
	setup(__props, { expose: __expose, emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const slots = useSlots();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("inputDate", props);
		const rootProps = useForwardPropsEmits(reactiveOmit(props, "id", "name", "range", "modelValue", "defaultValue", "color", "variant", "size", "highlight", "fixed", "disabled", "autofocus", "autofocusDelay", "icon", "avatar", "leading", "leadingIcon", "trailing", "trailingIcon", "loading", "loadingIcon", "separatorIcon", "class", "ui"), emits);
		const { emitFormBlur, emitFormFocus, emitFormChange, emitFormInput, size: formFieldSize, color, id, name, highlight, disabled, ariaAttrs } = useFormField(props);
		const { orientation, size: fieldGroupSize } = useFieldGroup(props);
		const { isLeading, isTrailing, leadingIconName, trailingIconName } = useComponentIcons(props);
		const [DefineSegmentsTemplate, ReuseSegmentsTemplate] = createReusableTemplate();
		const inputSize = computed(() => fieldGroupSize.value || formFieldSize.value);
		const ui = computed(() => tv({
			extend: tv(input_date_default),
			...appConfig.ui?.inputDate || {}
		})({
			color: color.value,
			variant: props.variant,
			size: inputSize.value,
			highlight: highlight.value,
			fixed: props.fixed,
			loading: props.loading,
			leading: isLeading.value || !!props.avatar || !!slots.leading,
			trailing: isTrailing.value || !!slots.trailing,
			fieldGroup: orientation.value
		}));
		const inputsRef = ref([]);
		function setInputRef(index, el) {
			inputsRef.value[index] = el;
		}
		function onUpdate(value) {
			emits("change", new Event("change", { target: { value } }));
			emitFormChange();
			emitFormInput();
		}
		function onBlur(event) {
			emitFormBlur();
			emits("blur", event);
		}
		function onFocus(event) {
			emitFormFocus();
			emits("focus", event);
		}
		function autoFocus() {
			if (props.autofocus) inputsRef.value[0]?.$el?.focus();
		}
		onMounted(() => {
			setTimeout(() => {
				autoFocus();
			}, props.autofocusDelay);
		});
		const DateField$1 = computed(() => props.range ? DateRangeField : DateField);
		__expose({ inputsRef });
		return (_ctx, _push, _parent, _attrs) => {
			_push(`<!--[-->`);
			_push(ssrRenderComponent(unref(DefineSegmentsTemplate), null, {
				default: withCtx(({ segments, type }, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<!--[-->`);
						ssrRenderList(segments, (segment, index) => {
							_push(ssrRenderComponent(unref(DateField$1).Input, {
								key: `${segment.part}-${index}`,
								ref_for: true,
								ref: (el) => setInputRef(index, el),
								type,
								part: segment.part,
								"data-slot": "segment",
								class: ui.value.segment({ class: unref(uiProp)?.segment }),
								"data-segment": segment.part
							}, {
								default: withCtx((_, _push, _parent, _scopeId) => {
									if (_push) _push(`${ssrInterpolate(segment.value.trim())}`);
									else return [createTextVNode(toDisplayString(segment.value.trim()), 1)];
								}),
								_: 2
							}, _parent, _scopeId));
						});
						_push(`<!--]-->`);
					} else return [(openBlock(true), createBlock(Fragment, null, renderList(segments, (segment, index) => {
						return openBlock(), createBlock(unref(DateField$1).Input, {
							key: `${segment.part}-${index}`,
							ref_for: true,
							ref: (el) => setInputRef(index, el),
							type,
							part: segment.part,
							"data-slot": "segment",
							class: ui.value.segment({ class: unref(uiProp)?.segment }),
							"data-segment": segment.part
						}, {
							default: withCtx(() => [createTextVNode(toDisplayString(segment.value.trim()), 1)]),
							_: 2
						}, 1032, [
							"type",
							"part",
							"class",
							"data-segment"
						]);
					}), 128))];
				}),
				_: 1
			}, _parent));
			_push(ssrRenderComponent(unref(DateField$1).Root, mergeProps({
				...unref(rootProps),
				..._ctx.$attrs,
				...unref(ariaAttrs)
			}, {
				id: unref(id),
				"model-value": __props.modelValue,
				"default-value": __props.defaultValue,
				name: unref(name),
				disabled: unref(disabled),
				"data-slot": "base",
				class: ui.value.base({ class: [unref(uiProp)?.base, props.class] }),
				"onUpdate:modelValue": onUpdate,
				onBlur,
				onFocus
			}), {
				default: withCtx(({ segments }, _push, _parent, _scopeId) => {
					if (_push) {
						if (Array.isArray(segments)) _push(ssrRenderComponent(unref(ReuseSegmentsTemplate), { segments }, null, _parent, _scopeId));
						else {
							_push(`<!--[-->`);
							_push(ssrRenderComponent(unref(ReuseSegmentsTemplate), {
								segments: segments.start,
								type: "start"
							}, null, _parent, _scopeId));
							ssrRenderSlot(_ctx.$slots, "separator", { ui: ui.value }, () => {
								_push(ssrRenderComponent(_sfc_main$20, {
									name: __props.separatorIcon || unref(appConfig).ui.icons.minus,
									"data-slot": "separatorIcon",
									class: ui.value.separatorIcon({ class: unref(uiProp)?.separatorIcon })
								}, null, _parent, _scopeId));
							}, _push, _parent, _scopeId);
							_push(ssrRenderComponent(unref(ReuseSegmentsTemplate), {
								segments: segments.end,
								type: "end"
							}, null, _parent, _scopeId));
							_push(`<!--]-->`);
						}
						ssrRenderSlot(_ctx.$slots, "default", { ui: ui.value }, null, _push, _parent, _scopeId);
						if (unref(isLeading) || !!__props.avatar || !!slots.leading) {
							_push(`<span data-slot="leading" class="${ssrRenderClass(ui.value.leading({ class: unref(uiProp)?.leading }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => {
								if (unref(isLeading) && unref(leadingIconName)) _push(ssrRenderComponent(_sfc_main$20, {
									name: unref(leadingIconName),
									"data-slot": "leadingIcon",
									class: ui.value.leadingIcon({ class: unref(uiProp)?.leadingIcon })
								}, null, _parent, _scopeId));
								else if (!!__props.avatar) _push(ssrRenderComponent(_sfc_main$21, mergeProps({ size: unref(uiProp)?.leadingAvatarSize || ui.value.leadingAvatarSize() }, __props.avatar, {
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
								if (unref(trailingIconName)) _push(ssrRenderComponent(_sfc_main$20, {
									name: unref(trailingIconName),
									"data-slot": "trailingIcon",
									class: ui.value.trailingIcon({ class: unref(uiProp)?.trailingIcon })
								}, null, _parent, _scopeId));
								else _push(`<!---->`);
							}, _push, _parent, _scopeId);
							_push(`</span>`);
						} else _push(`<!---->`);
					} else return [
						Array.isArray(segments) ? (openBlock(), createBlock(unref(ReuseSegmentsTemplate), {
							key: 0,
							segments
						}, null, 8, ["segments"])) : (openBlock(), createBlock(Fragment, { key: 1 }, [
							createVNode(unref(ReuseSegmentsTemplate), {
								segments: segments.start,
								type: "start"
							}, null, 8, ["segments"]),
							renderSlot(_ctx.$slots, "separator", { ui: ui.value }, () => [createVNode(_sfc_main$20, {
								name: __props.separatorIcon || unref(appConfig).ui.icons.minus,
								"data-slot": "separatorIcon",
								class: ui.value.separatorIcon({ class: unref(uiProp)?.separatorIcon })
							}, null, 8, ["name", "class"])]),
							createVNode(unref(ReuseSegmentsTemplate), {
								segments: segments.end,
								type: "end"
							}, null, 8, ["segments"])
						], 64)),
						renderSlot(_ctx.$slots, "default", { ui: ui.value }),
						unref(isLeading) || !!__props.avatar || !!slots.leading ? (openBlock(), createBlock("span", {
							key: 2,
							"data-slot": "leading",
							class: ui.value.leading({ class: unref(uiProp)?.leading })
						}, [renderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => [unref(isLeading) && unref(leadingIconName) ? (openBlock(), createBlock(_sfc_main$20, {
							key: 0,
							name: unref(leadingIconName),
							"data-slot": "leadingIcon",
							class: ui.value.leadingIcon({ class: unref(uiProp)?.leadingIcon })
						}, null, 8, ["name", "class"])) : !!__props.avatar ? (openBlock(), createBlock(_sfc_main$21, mergeProps({
							key: 1,
							size: unref(uiProp)?.leadingAvatarSize || ui.value.leadingAvatarSize()
						}, __props.avatar, {
							"data-slot": "leadingAvatar",
							class: ui.value.leadingAvatar({ class: unref(uiProp)?.leadingAvatar })
						}), null, 16, ["size", "class"])) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true),
						unref(isTrailing) || !!slots.trailing ? (openBlock(), createBlock("span", {
							key: 3,
							"data-slot": "trailing",
							class: ui.value.trailing({ class: unref(uiProp)?.trailing })
						}, [renderSlot(_ctx.$slots, "trailing", { ui: ui.value }, () => [unref(trailingIconName) ? (openBlock(), createBlock(_sfc_main$20, {
							key: 0,
							name: unref(trailingIconName),
							"data-slot": "trailingIcon",
							class: ui.value.trailingIcon({ class: unref(uiProp)?.trailingIcon })
						}, null, 8, ["name", "class"])) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true)
					];
				}),
				_: 3
			}, _parent));
			_push(`<!--]-->`);
		};
	}
});
var _sfc_setup$5 = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/InputDate.vue");
	return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
//#endregion
//#region resources/js/Components/Forms/FgInputDatePicker.vue?vue&type=script&setup=true&lang.ts
var FgInputDatePicker_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "FgInputDatePicker",
	__ssrInlineRender: true,
	props: /* @__PURE__ */ mergeModels({
		placeholder: {
			type: String,
			required: false,
			default: void 0
		},
		disabled: {
			type: Boolean,
			required: false,
			default: false
		},
		ui: {
			type: Object,
			required: false,
			default: () => ({})
		},
		size: {
			type: String,
			required: false,
			default: "sm"
		},
		icon: {
			type: String,
			required: false,
			default: "i-lucide-calendar"
		},
		__: {
			type: Object,
			required: true
		}
	}, {
		"modelValue": {},
		"modelModifiers": {}
	}),
	emits: ["update:modelValue"],
	setup(__props) {
		const model = useModel(__props, "modelValue");
		const inputDate = useTemplateRef("inputDate");
		return (_ctx, _push, _parent, _attrs) => {
			const _component_UInputDate = _sfc_main;
			const _component_UPopover = _sfc_main$36;
			const _component_UButton = _sfc_main$22;
			const _component_UCalendar = _sfc_main$1;
			_push(ssrRenderComponent(_component_UInputDate, mergeProps({
				ref_key: "inputDate",
				ref: inputDate,
				modelValue: model.value,
				"onUpdate:modelValue": ($event) => model.value = $event,
				placeholder: __props.placeholder,
				disabled: __props.disabled,
				ui: __props.ui
			}, _attrs), {
				trailing: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(ssrRenderComponent(_component_UPopover, { reference: inputDate.value?.inputsRef[3]?.$el }, {
						content: withCtx((_, _push, _parent, _scopeId) => {
							if (_push) _push(ssrRenderComponent(_component_UCalendar, {
								modelValue: model.value,
								"onUpdate:modelValue": ($event) => model.value = $event,
								class: "p-2"
							}, null, _parent, _scopeId));
							else return [createVNode(_component_UCalendar, {
								modelValue: model.value,
								"onUpdate:modelValue": ($event) => model.value = $event,
								class: "p-2"
							}, null, 8, ["modelValue", "onUpdate:modelValue"])];
						}),
						default: withCtx((_, _push, _parent, _scopeId) => {
							if (_push) _push(ssrRenderComponent(_component_UButton, {
								color: "neutral",
								variant: "link",
								size: __props.size,
								icon: __props.icon,
								"aria-label": __props.__.date_picker.aria_label,
								class: "px-0"
							}, null, _parent, _scopeId));
							else return [createVNode(_component_UButton, {
								color: "neutral",
								variant: "link",
								size: __props.size,
								icon: __props.icon,
								"aria-label": __props.__.date_picker.aria_label,
								class: "px-0"
							}, null, 8, [
								"size",
								"icon",
								"aria-label"
							])];
						}),
						_: 1
					}, _parent, _scopeId));
					else return [createVNode(_component_UPopover, { reference: inputDate.value?.inputsRef[3]?.$el }, {
						content: withCtx(() => [createVNode(_component_UCalendar, {
							modelValue: model.value,
							"onUpdate:modelValue": ($event) => model.value = $event,
							class: "p-2"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						default: withCtx(() => [createVNode(_component_UButton, {
							color: "neutral",
							variant: "link",
							size: __props.size,
							icon: __props.icon,
							"aria-label": __props.__.date_picker.aria_label,
							class: "px-0"
						}, null, 8, [
							"size",
							"icon",
							"aria-label"
						])]),
						_: 1
					}, 8, ["reference"])];
				}),
				_: 1
			}, _parent));
		};
	}
});
//#endregion
//#region resources/js/Components/Forms/FgInputDatePicker.vue
var _sfc_setup$4 = FgInputDatePicker_vue_vue_type_script_setup_true_lang_default.setup;
FgInputDatePicker_vue_vue_type_script_setup_true_lang_default.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Forms/FgInputDatePicker.vue");
	return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
var FgInputDatePicker_default = FgInputDatePicker_vue_vue_type_script_setup_true_lang_default;
//#endregion
//#region resources/js/Components/Modals/FgDiscardChangesModal.vue?vue&type=script&setup=true&lang.ts
var FgDiscardChangesModal_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "FgDiscardChangesModal",
	__ssrInlineRender: true,
	props: /* @__PURE__ */ mergeModels({
		title: {
			type: String,
			required: true
		},
		description: {
			type: String,
			required: true
		},
		keepLabel: {
			type: String,
			required: true
		},
		discardLabel: {
			type: String,
			required: true
		},
		discardColor: {
			type: String,
			default: "error"
		},
		keepColor: {
			type: String,
			default: "neutral"
		}
	}, {
		"open": {
			type: Boolean,
			default: false
		},
		"openModifiers": {}
	}),
	emits: /* @__PURE__ */ mergeModels(["keep", "discard"], ["update:open"]),
	setup(__props, { emit: __emit }) {
		const open = useModel(__props, "open");
		const emit = __emit;
		return (_ctx, _push, _parent, _attrs) => {
			const _component_UModal = _sfc_main$12;
			const _component_UButton = _sfc_main$22;
			_push(ssrRenderComponent(_component_UModal, mergeProps({
				open: open.value,
				"onUpdate:open": ($event) => open.value = $event
			}, _attrs), {
				header: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(`<h2 class="text-lg font-semibold"${_scopeId}>${ssrInterpolate(__props.title)}</h2>`);
					else return [createVNode("h2", { class: "text-lg font-semibold" }, toDisplayString(__props.title), 1)];
				}),
				body: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(`<p class="text-muted"${_scopeId}>${ssrInterpolate(__props.description)}</p>`);
					else return [createVNode("p", { class: "text-muted" }, toDisplayString(__props.description), 1)];
				}),
				footer: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex justify-end gap-2 w-full"${_scopeId}>`);
						_push(ssrRenderComponent(_component_UButton, {
							color: __props.keepColor,
							variant: "ghost",
							label: __props.keepLabel,
							type: "button",
							onClick: ($event) => emit("keep"),
							class: "cursor-pointer"
						}, null, _parent, _scopeId));
						_push(ssrRenderComponent(_component_UButton, {
							color: __props.discardColor,
							label: __props.discardLabel,
							type: "button",
							onClick: ($event) => emit("discard"),
							class: "cursor-pointer"
						}, null, _parent, _scopeId));
						_push(`</div>`);
					} else return [createVNode("div", { class: "flex justify-end gap-2 w-full" }, [createVNode(_component_UButton, {
						color: __props.keepColor,
						variant: "ghost",
						label: __props.keepLabel,
						type: "button",
						onClick: ($event) => emit("keep"),
						class: "cursor-pointer"
					}, null, 8, [
						"color",
						"label",
						"onClick"
					]), createVNode(_component_UButton, {
						color: __props.discardColor,
						label: __props.discardLabel,
						type: "button",
						onClick: ($event) => emit("discard"),
						class: "cursor-pointer"
					}, null, 8, [
						"color",
						"label",
						"onClick"
					])])];
				}),
				_: 1
			}, _parent));
		};
	}
});
//#endregion
//#region resources/js/Components/Modals/FgDiscardChangesModal.vue
var _sfc_setup$3 = FgDiscardChangesModal_vue_vue_type_script_setup_true_lang_default.setup;
FgDiscardChangesModal_vue_vue_type_script_setup_true_lang_default.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Modals/FgDiscardChangesModal.vue");
	return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
var FgDiscardChangesModal_default = FgDiscardChangesModal_vue_vue_type_script_setup_true_lang_default;
//#endregion
//#region resources/js/Components/Modals/FgConfirmIdentityModal.vue?vue&type=script&setup=true&lang.ts
var FgConfirmIdentityModal_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "FgConfirmIdentityModal",
	__ssrInlineRender: true,
	props: /* @__PURE__ */ mergeModels({
		methods: {},
		selectedMethod: { default: null },
		maskedEmail: { default: "" },
		config: {}
	}, {
		"open": {
			type: Boolean,
			default: false
		},
		"openModifiers": {}
	}),
	emits: /* @__PURE__ */ mergeModels(["confirmed"], ["update:open"]),
	setup(__props, { emit: __emit }) {
		const open = useModel(__props, "open");
		const toast = useToast$1();
		const props = __props;
		const emit = __emit;
		const form = useForm({
			password: "",
			method: props.selectedMethod,
			code: "",
			passkey: ""
		});
		const routes = computed(() => ({
			confirm: props.config.routes?.confirm ?? "/user/confirm-identity",
			method: props.config.routes?.method ?? "/user/confirm-identity/method",
			emailCode: props.config.routes?.emailCode ?? "/user/confirm-identity/email-code",
			passkeyOptions: props.config.routes?.passkeyOptions ?? "/user/confirm-identity/passkey/options",
			passkeySubmit: props.config.routes?.passkeySubmit ?? "/user/confirm-identity/passkey/verify"
		}));
		const availableMethods = computed(() => {
			return (props.methods ?? []).map((method) => {
				if (typeof method === "string") return {
					label: props.config.methods?.[method] ?? method,
					value: method
				};
				return method;
			});
		});
		const hasMethods = computed(() => availableMethods.value.length > 0);
		const hasMethodSwitcher = computed(() => availableMethods.value.length > 1);
		const isPasskey = computed(() => form.method === "passkey");
		const requiresOtp = computed(() => ["email", "one_time_code"].includes(String(form.method)));
		const requiresPassword = computed(() => !isPasskey.value);
		const defaultMethod = computed(() => {
			const methods = availableMethods.value;
			if (props.selectedMethod && methods.some((method) => method.value === props.selectedMethod)) return props.selectedMethod;
			return methods[0]?.value ?? null;
		});
		const canSubmit = computed(() => {
			if (form.processing) return false;
			if (requiresPassword.value && !form.password) return false;
			if (!hasMethods.value) return true;
			if (isPasskey.value) return false;
			if (requiresOtp.value) return Array.isArray(form.code) ? form.code.join("").length === 6 : String(form.code ?? "").length === 6;
			return true;
		});
		watch(() => open.value, (open) => {
			if (!open) {
				form.reset();
				form.clearErrors();
				return;
			}
			form.method = defaultMethod.value;
			form.password = "";
			form.code = "";
			form.clearErrors();
			if (form.method === "email") sendEmailCode();
		});
		function closeModal() {
			if (form.processing) return;
			open.value = false;
		}
		function submit() {
			form.post(routes.value.confirm, {
				preserveState: true,
				preserveScroll: true,
				onSuccess: () => {
					emit("confirmed");
					open.value = false;
				},
				onError: () => {
					form.reset("code");
				}
			});
		}
		function showToastError(errors) {
			if (!errors?.toast) return;
			try {
				const error = JSON.parse(errors.toast);
				toast.add({
					title: error.title,
					description: error.description,
					color: "error"
				});
			} catch {
				toast.add({
					title: errors.toast,
					color: "error"
				});
			}
		}
		function changeMethod() {
			form.code = "";
			form.clearErrors();
			router.post(routes.value.method, { method: form.method }, {
				preserveScroll: true,
				preserveState: true,
				onError: (errors) => {
					showToastError(errors);
					if (errors.method) form.setError("method", errors.method);
					form.method = defaultMethod.value;
				}
			});
		}
		function sendEmailCode() {
			router.post(routes.value.emailCode, {}, {
				preserveScroll: true,
				preserveState: true
			});
		}
		function resendEmailCode() {
			sendEmailCode();
		}
		async function verifyPasskey() {
			form.clearErrors();
			try {
				await Passkeys.verify({ routes: {
					options: routes.value.passkeyOptions,
					submit: routes.value.passkeySubmit
				} });
				emit("confirmed");
				open.value = false;
			} catch (error) {
				form.setError("passkey", error?.message ?? props.config.errors.invalidPasskey);
			}
		}
		return (_ctx, _push, _parent, _attrs) => {
			const _component_UModal = _sfc_main$12;
			const _component_UFormField = _sfc_main$27;
			const _component_UInput = _sfc_main$26;
			const _component_USelect = _sfc_main$32;
			const _component_UAlert = _sfc_main$29;
			const _component_UPinInput = _sfc_main$33;
			const _component_UButton = _sfc_main$22;
			_push(ssrRenderComponent(_component_UModal, mergeProps({
				open: open.value,
				"onUpdate:open": [($event) => open.value = $event, (value) => value ? open.value = true : closeModal()]
			}, _attrs), {
				header: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(`<h2 class="text-lg font-semibold"${_scopeId}>${ssrInterpolate(__props.config.title)}</h2>`);
					else return [createVNode("h2", { class: "text-lg font-semibold" }, toDisplayString(__props.config.title), 1)];
				}),
				body: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex flex-col gap-4"${_scopeId}>`);
						ssrRenderSlot(_ctx.$slots, "before-form", {}, null, _push, _parent, _scopeId);
						_push(`<form class="flex flex-col gap-4"${_scopeId}>`);
						if (__props.config.subtitle) _push(`<p class="text-sm text-muted"${_scopeId}>${ssrInterpolate(__props.config.subtitle)}</p>`);
						else _push(`<!---->`);
						if (requiresPassword.value) _push(ssrRenderComponent(_component_UFormField, {
							label: __props.config.fields.password,
							required: "",
							error: unref(form).errors.password
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(_component_UInput, {
									modelValue: unref(form).password,
									"onUpdate:modelValue": ($event) => unref(form).password = $event,
									type: "password",
									"auto-complete": " current-password",
									class: "w-full"
								}, null, _parent, _scopeId));
								else return [createVNode(_component_UInput, {
									modelValue: unref(form).password,
									"onUpdate:modelValue": ($event) => unref(form).password = $event,
									type: "password",
									"auto-complete": " current-password",
									class: "w-full"
								}, null, 8, ["modelValue", "onUpdate:modelValue"])];
							}),
							_: 1
						}, _parent, _scopeId));
						else _push(`<!---->`);
						if (hasMethodSwitcher.value) _push(ssrRenderComponent(_component_UFormField, {
							label: __props.config.fields.method,
							required: "",
							error: unref(form).errors.method
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(_component_USelect, {
									modelValue: unref(form).method,
									"onUpdate:modelValue": [($event) => unref(form).method = $event, changeMethod],
									items: availableMethods.value,
									"label-key": "label",
									"value-key": "value",
									class: "w-full"
								}, null, _parent, _scopeId));
								else return [createVNode(_component_USelect, {
									modelValue: unref(form).method,
									"onUpdate:modelValue": [($event) => unref(form).method = $event, changeMethod],
									items: availableMethods.value,
									"label-key": "label",
									"value-key": "value",
									class: "w-full"
								}, null, 8, [
									"modelValue",
									"onUpdate:modelValue",
									"items"
								])];
							}),
							_: 1
						}, _parent, _scopeId));
						else _push(`<!---->`);
						if (unref(form).method === "email") _push(ssrRenderComponent(_component_UAlert, {
							color: "info",
							variant: "soft",
							icon: "i-lucide-mail",
							title: `${__props.config.emailSentTo} ${__props.maskedEmail}`
						}, null, _parent, _scopeId));
						else _push(`<!---->`);
						if (requiresOtp.value) {
							_push(`<div class="flex flex-col gap-3"${_scopeId}>`);
							_push(ssrRenderComponent(_component_UFormField, {
								label: __props.config.fields.code,
								required: "",
								error: unref(form).errors.code
							}, {
								default: withCtx((_, _push, _parent, _scopeId) => {
									if (_push) _push(ssrRenderComponent(_component_UPinInput, {
										modelValue: unref(form).code,
										"onUpdate:modelValue": ($event) => unref(form).code = $event,
										otp: "",
										length: 6,
										class: "flex items-center justify-center"
									}, null, _parent, _scopeId));
									else return [createVNode(_component_UPinInput, {
										modelValue: unref(form).code,
										"onUpdate:modelValue": ($event) => unref(form).code = $event,
										otp: "",
										length: 6,
										class: "flex items-center justify-center"
									}, null, 8, ["modelValue", "onUpdate:modelValue"])];
								}),
								_: 1
							}, _parent, _scopeId));
							_push(`</div>`);
						} else _push(`<!---->`);
						if (unref(form).errors.passkey) _push(`<p class="text-sm text-error"${_scopeId}>${ssrInterpolate(unref(form).errors.passkey)}</p>`);
						else _push(`<!---->`);
						_push(`</form></div>`);
					} else return [createVNode("div", { class: "flex flex-col gap-4" }, [renderSlot(_ctx.$slots, "before-form"), createVNode("form", {
						class: "flex flex-col gap-4",
						onSubmit: withModifiers(submit, ["prevent"])
					}, [
						__props.config.subtitle ? (openBlock(), createBlock("p", {
							key: 0,
							class: "text-sm text-muted"
						}, toDisplayString(__props.config.subtitle), 1)) : createCommentVNode("", true),
						requiresPassword.value ? (openBlock(), createBlock(_component_UFormField, {
							key: 1,
							label: __props.config.fields.password,
							required: "",
							error: unref(form).errors.password
						}, {
							default: withCtx(() => [createVNode(_component_UInput, {
								modelValue: unref(form).password,
								"onUpdate:modelValue": ($event) => unref(form).password = $event,
								type: "password",
								"auto-complete": " current-password",
								class: "w-full"
							}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
							_: 1
						}, 8, ["label", "error"])) : createCommentVNode("", true),
						hasMethodSwitcher.value ? (openBlock(), createBlock(_component_UFormField, {
							key: 2,
							label: __props.config.fields.method,
							required: "",
							error: unref(form).errors.method
						}, {
							default: withCtx(() => [createVNode(_component_USelect, {
								modelValue: unref(form).method,
								"onUpdate:modelValue": [($event) => unref(form).method = $event, changeMethod],
								items: availableMethods.value,
								"label-key": "label",
								"value-key": "value",
								class: "w-full"
							}, null, 8, [
								"modelValue",
								"onUpdate:modelValue",
								"items"
							])]),
							_: 1
						}, 8, ["label", "error"])) : createCommentVNode("", true),
						unref(form).method === "email" ? (openBlock(), createBlock(_component_UAlert, {
							key: 3,
							color: "info",
							variant: "soft",
							icon: "i-lucide-mail",
							title: `${__props.config.emailSentTo} ${__props.maskedEmail}`
						}, null, 8, ["title"])) : createCommentVNode("", true),
						requiresOtp.value ? (openBlock(), createBlock("div", {
							key: 4,
							class: "flex flex-col gap-3"
						}, [createVNode(_component_UFormField, {
							label: __props.config.fields.code,
							required: "",
							error: unref(form).errors.code
						}, {
							default: withCtx(() => [createVNode(_component_UPinInput, {
								modelValue: unref(form).code,
								"onUpdate:modelValue": ($event) => unref(form).code = $event,
								otp: "",
								length: 6,
								class: "flex items-center justify-center"
							}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
							_: 1
						}, 8, ["label", "error"])])) : createCommentVNode("", true),
						unref(form).errors.passkey ? (openBlock(), createBlock("p", {
							key: 5,
							class: "text-sm text-error"
						}, toDisplayString(unref(form).errors.passkey), 1)) : createCommentVNode("", true)
					], 32)])];
				}),
				footer: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex justify-end gap-2 w-full"${_scopeId}>`);
						_push(ssrRenderComponent(_component_UButton, {
							color: "neutral",
							variant: "ghost",
							label: __props.config.actions.cancel,
							type: "button",
							disabled: unref(form).processing,
							onClick: closeModal
						}, null, _parent, _scopeId));
						if (requiresOtp.value || !hasMethods.value) _push(ssrRenderComponent(_component_UButton, {
							type: "button",
							label: __props.config.actions.confirmButton,
							loading: unref(form).processing,
							disabled: !canSubmit.value,
							onClick: submit
						}, null, _parent, _scopeId));
						else if (isPasskey.value) _push(ssrRenderComponent(_component_UButton, {
							type: "button",
							label: __props.config.actions.passkeyButton,
							loading: unref(form).processing,
							disabled: unref(form).processing,
							icon: "i-lucide-key-round",
							onClick: verifyPasskey
						}, null, _parent, _scopeId));
						else _push(`<!---->`);
						if (unref(form).method === "email") _push(ssrRenderComponent(_component_UButton, {
							type: "button",
							color: "neutral",
							label: __props.config.actions.resendEmailCode,
							onClick: resendEmailCode
						}, null, _parent, _scopeId));
						else _push(`<!---->`);
						_push(`</div>`);
					} else return [createVNode("div", { class: "flex justify-end gap-2 w-full" }, [
						createVNode(_component_UButton, {
							color: "neutral",
							variant: "ghost",
							label: __props.config.actions.cancel,
							type: "button",
							disabled: unref(form).processing,
							onClick: closeModal
						}, null, 8, ["label", "disabled"]),
						requiresOtp.value || !hasMethods.value ? (openBlock(), createBlock(_component_UButton, {
							key: 0,
							type: "button",
							label: __props.config.actions.confirmButton,
							loading: unref(form).processing,
							disabled: !canSubmit.value,
							onClick: submit
						}, null, 8, [
							"label",
							"loading",
							"disabled"
						])) : isPasskey.value ? (openBlock(), createBlock(_component_UButton, {
							key: 1,
							type: "button",
							label: __props.config.actions.passkeyButton,
							loading: unref(form).processing,
							disabled: unref(form).processing,
							icon: "i-lucide-key-round",
							onClick: verifyPasskey
						}, null, 8, [
							"label",
							"loading",
							"disabled"
						])) : createCommentVNode("", true),
						unref(form).method === "email" ? (openBlock(), createBlock(_component_UButton, {
							key: 2,
							type: "button",
							color: "neutral",
							label: __props.config.actions.resendEmailCode,
							onClick: resendEmailCode
						}, null, 8, ["label"])) : createCommentVNode("", true)
					])];
				}),
				_: 3
			}, _parent));
		};
	}
});
//#endregion
//#region resources/js/Components/Modals/FgConfirmIdentityModal.vue
var _sfc_setup$2 = FgConfirmIdentityModal_vue_vue_type_script_setup_true_lang_default.setup;
FgConfirmIdentityModal_vue_vue_type_script_setup_true_lang_default.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Modals/FgConfirmIdentityModal.vue");
	return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
var FgConfirmIdentityModal_default = FgConfirmIdentityModal_vue_vue_type_script_setup_true_lang_default;
//#endregion
//#region resources/js/Components/Modals/FgConfirmBulkActionModal.vue?vue&type=script&setup=true&lang.ts
var FgConfirmBulkActionModal_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "FgConfirmBulkActionModal",
	__ssrInlineRender: true,
	props: /* @__PURE__ */ mergeModels({
		methods: {},
		selectedMethod: { default: null },
		maskedEmail: { default: "" },
		identityConfig: {},
		bulkConfig: {}
	}, {
		"open": {
			type: Boolean,
			default: false
		},
		"openModifiers": {}
	}),
	emits: /* @__PURE__ */ mergeModels(["confirmed"], ["update:open"]),
	setup(__props, { emit: __emit }) {
		const open = useModel(__props, "open");
		const props = __props;
		const emit = __emit;
		const page = usePage();
		const selectedRows = computed(() => props.bulkConfig.selectedRows ?? []);
		const actionableRows = computed(() => props.bulkConfig.actionableRows ?? []);
		const selectedCount = computed(() => selectedRows.value.length);
		const actionableCount = computed(() => actionableRows.value.length);
		const ignoredCount = computed(() => Math.max(selectedCount.value - actionableCount.value, 0));
		const actionableIds = computed(() => new Set(actionableRows.value.map((row) => row.id)));
		const ignoredRows = computed(() => selectedRows.value.filter((row) => !actionableIds.value.has(row.id)));
		const modalConfig = computed(() => ({
			title: props.bulkConfig.translations.title,
			subtitle: props.identityConfig.subtitle,
			fields: {
				password: props.identityConfig.fields.password,
				method: props.identityConfig.fields.method,
				code: props.identityConfig.fields.code
			},
			methods: props.identityConfig.methods,
			actions: {
				cancel: props.identityConfig.actions.cancel,
				confirmButton: props.bulkConfig.translations.confirm,
				passkeyButton: props.bulkConfig.translations.confirm,
				resendEmailCode: props.identityConfig.actions.resend_email_code
			},
			emailSentTo: props.identityConfig.messages.email_sent_to,
			errors: { invalidPasskey: props.identityConfig.errors.invalid_passkey },
			routes: props.identityConfig.routes
		}));
		const icon = computed(() => {
			if (props.bulkConfig.type === "restore") return "i-lucide-rotate-ccw";
			if (props.bulkConfig.type === "force_delete") return "i-lucide-trash-2";
			return "i-lucide-trash";
		});
		const color = computed(() => {
			if (props.bulkConfig.type === "restore") return "success";
			return "error";
		});
		const colorClass = computed(() => ({
			"text-error": color.value === "error",
			"text-success": color.value === "success"
		}));
		function isCurrentUser(row) {
			return Boolean(row?.id === page.props.auth.user.id);
		}
		function isTrashed(row) {
			return Boolean(row?.deleted_at || row?.is_deleted);
		}
		function hasPermission(row) {
			if (props.bulkConfig.type === "delete") return Boolean(row?.permissions?.delete) && Boolean(row?.id !== page.props.auth.user.id);
			if (props.bulkConfig.type === "force_delete") return Boolean(row?.permissions?.force_delete) && Boolean(row?.id !== page.props.auth.user.id);
			return Boolean(row?.permissions?.restore) && Boolean(row?.id !== page.props.auth.user.id);
		}
		const ignoredReasons = computed(() => {
			const currentUser = ignoredRows.value.filter((row) => isCurrentUser(row)).length;
			const reasons = [];
			const trans = props.bulkConfig.translations.ignored_reasons;
			if (props.bulkConfig.type === "delete") {
				if (currentUser > 0 && trans.current_user) reasons.push({
					label: trans.current_user,
					count: currentUser
				});
				const alreadyTrashed = ignoredRows.value.filter((row) => !isCurrentUser(row) && isTrashed(row)).length;
				const missingPermission = ignoredRows.value.filter((row) => !isCurrentUser(row) && !isTrashed(row) && !hasPermission(row)).length;
				if (alreadyTrashed > 0 && trans.already_trashed) reasons.push({
					label: trans.already_trashed,
					count: alreadyTrashed
				});
				if (missingPermission > 0) reasons.push({
					label: trans.missing_permission,
					count: missingPermission
				});
			}
			if (props.bulkConfig.type === "force_delete") {
				if (currentUser > 0 && trans.current_user) reasons.push({
					label: trans.current_user,
					count: currentUser
				});
				const notTrashed = ignoredRows.value.filter((row) => !isCurrentUser(row) && !isTrashed(row)).length;
				const missingPermission = ignoredRows.value.filter((row) => !isCurrentUser(row) && isTrashed(row) && !hasPermission(row)).length;
				if (notTrashed > 0 && trans.not_trashed) reasons.push({
					label: trans.not_trashed,
					count: notTrashed
				});
				if (missingPermission > 0) reasons.push({
					label: trans.missing_permission,
					count: missingPermission
				});
			}
			if (props.bulkConfig.type === "restore") {
				if (currentUser > 0 && trans.current_user) reasons.push({
					label: trans.current_user,
					count: currentUser
				});
				const notTrashed = ignoredRows.value.filter((row) => !isCurrentUser(row) && !isTrashed(row)).length;
				const missingPermission = ignoredRows.value.filter((row) => !isCurrentUser(row) && isTrashed(row) && !hasPermission(row)).length;
				if (notTrashed > 0 && trans.not_trashed) reasons.push({
					label: trans.not_trashed,
					count: notTrashed
				});
				if (missingPermission > 0) reasons.push({
					label: trans.missing_permission,
					count: missingPermission
				});
			}
			return reasons;
		});
		const ignoredTitle = computed(() => {
			if (props.bulkConfig.translations.ignored_title) return props.bulkConfig.translations.ignored_title.replace(":count", String(ignoredCount.value));
			return `${ignoredCount.value} utilisateur(s) ignoré(s)`;
		});
		return (_ctx, _push, _parent, _attrs) => {
			const _component_UIcon = _sfc_main$20;
			const _component_UAlert = _sfc_main$29;
			_push(ssrRenderComponent(FgConfirmIdentityModal_default, mergeProps({
				open: open.value,
				"onUpdate:open": ($event) => open.value = $event,
				methods: __props.methods,
				"selected-method": __props.selectedMethod,
				"masked-email": __props.maskedEmail,
				config: modalConfig.value,
				onConfirmed: ($event) => emit("confirmed")
			}, _attrs), {
				"before-form": withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex flex-col gap-4"${_scopeId}><div class="flex items-start gap-3"${_scopeId}>`);
						_push(ssrRenderComponent(_component_UIcon, {
							name: icon.value,
							class: ["mt-0.5 size-5", colorClass.value]
						}, null, _parent, _scopeId));
						_push(`<p class="text-sm text-muted"${_scopeId}>${ssrInterpolate(__props.bulkConfig.translations.description)}</p></div><div class="grid grid-cols-3 gap-2"${_scopeId}><div class="rounded-md border border-default p-3"${_scopeId}><p class="text-xs text-muted"${_scopeId}>${ssrInterpolate(__props.bulkConfig.translations.counters.selected)}</p><p class="text-lg font-semibold"${_scopeId}>${ssrInterpolate(selectedCount.value)}</p></div><div class="rounded-md border border-default p-3"${_scopeId}><p class="text-xs text-muted"${_scopeId}>${ssrInterpolate(__props.bulkConfig.translations.counters.actionable)}</p><p class="text-lg font-semibold"${_scopeId}>${ssrInterpolate(actionableCount.value)}</p></div><div class="rounded-md border border-default p-3"${_scopeId}><p class="text-xs text-muted"${_scopeId}>${ssrInterpolate(__props.bulkConfig.translations.counters.ignored)}</p><p class="text-lg font-semibold"${_scopeId}>${ssrInterpolate(ignoredCount.value)}</p></div></div>`);
						if (__props.bulkConfig.translations.action_description) _push(ssrRenderComponent(_component_UAlert, {
							color: "info",
							variant: "soft",
							icon: "i-lucide-info",
							description: __props.bulkConfig.translations.action_description
						}, null, _parent, _scopeId));
						else _push(`<!---->`);
						if (ignoredCount.value > 0) _push(ssrRenderComponent(_component_UAlert, {
							color: "warning",
							variant: "soft",
							icon: "i-lucide-triangle-alert"
						}, {
							title: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(`${ssrInterpolate(ignoredTitle.value)}`);
								else return [createTextVNode(toDisplayString(ignoredTitle.value), 1)];
							}),
							description: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) {
									_push(`<ul class="mt-2 list-disc space-y-1 pl-4"${_scopeId}><!--[-->`);
									ssrRenderList(ignoredReasons.value, (reason) => {
										_push(`<li${_scopeId}>${ssrInterpolate(reason.count)} - ${ssrInterpolate(reason.label)}</li>`);
									});
									_push(`<!--]--></ul>`);
								} else return [createVNode("ul", { class: "mt-2 list-disc space-y-1 pl-4" }, [(openBlock(true), createBlock(Fragment, null, renderList(ignoredReasons.value, (reason) => {
									return openBlock(), createBlock("li", { key: reason.label }, toDisplayString(reason.count) + " - " + toDisplayString(reason.label), 1);
								}), 128))])];
							}),
							_: 1
						}, _parent, _scopeId));
						else _push(`<!---->`);
						_push(`</div>`);
					} else return [createVNode("div", { class: "flex flex-col gap-4" }, [
						createVNode("div", { class: "flex items-start gap-3" }, [createVNode(_component_UIcon, {
							name: icon.value,
							class: ["mt-0.5 size-5", colorClass.value]
						}, null, 8, ["name", "class"]), createVNode("p", { class: "text-sm text-muted" }, toDisplayString(__props.bulkConfig.translations.description), 1)]),
						createVNode("div", { class: "grid grid-cols-3 gap-2" }, [
							createVNode("div", { class: "rounded-md border border-default p-3" }, [createVNode("p", { class: "text-xs text-muted" }, toDisplayString(__props.bulkConfig.translations.counters.selected), 1), createVNode("p", { class: "text-lg font-semibold" }, toDisplayString(selectedCount.value), 1)]),
							createVNode("div", { class: "rounded-md border border-default p-3" }, [createVNode("p", { class: "text-xs text-muted" }, toDisplayString(__props.bulkConfig.translations.counters.actionable), 1), createVNode("p", { class: "text-lg font-semibold" }, toDisplayString(actionableCount.value), 1)]),
							createVNode("div", { class: "rounded-md border border-default p-3" }, [createVNode("p", { class: "text-xs text-muted" }, toDisplayString(__props.bulkConfig.translations.counters.ignored), 1), createVNode("p", { class: "text-lg font-semibold" }, toDisplayString(ignoredCount.value), 1)])
						]),
						__props.bulkConfig.translations.action_description ? (openBlock(), createBlock(_component_UAlert, {
							key: 0,
							color: "info",
							variant: "soft",
							icon: "i-lucide-info",
							description: __props.bulkConfig.translations.action_description
						}, null, 8, ["description"])) : createCommentVNode("", true),
						ignoredCount.value > 0 ? (openBlock(), createBlock(_component_UAlert, {
							key: 1,
							color: "warning",
							variant: "soft",
							icon: "i-lucide-triangle-alert"
						}, {
							title: withCtx(() => [createTextVNode(toDisplayString(ignoredTitle.value), 1)]),
							description: withCtx(() => [createVNode("ul", { class: "mt-2 list-disc space-y-1 pl-4" }, [(openBlock(true), createBlock(Fragment, null, renderList(ignoredReasons.value, (reason) => {
								return openBlock(), createBlock("li", { key: reason.label }, toDisplayString(reason.count) + " - " + toDisplayString(reason.label), 1);
							}), 128))])]),
							_: 1
						})) : createCommentVNode("", true)
					])];
				}),
				_: 1
			}, _parent));
		};
	}
});
//#endregion
//#region resources/js/Components/Modals/FgConfirmBulkActionModal.vue
var _sfc_setup$1 = FgConfirmBulkActionModal_vue_vue_type_script_setup_true_lang_default.setup;
FgConfirmBulkActionModal_vue_vue_type_script_setup_true_lang_default.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Modals/FgConfirmBulkActionModal.vue");
	return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
var FgConfirmBulkActionModal_default = FgConfirmBulkActionModal_vue_vue_type_script_setup_true_lang_default;
//#endregion
//#region resources/js/Pages/Dashboard/Users/Index.vue?vue&type=script&setup=true&lang.ts
var Index_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	layout: DashboardLayout_default,
	__name: "Index",
	__ssrInlineRender: true,
	props: {
		users: {
			type: Object,
			required: true
		},
		invitations: {
			type: Object,
			required: true
		},
		filters: {
			type: Object,
			required: true
		},
		fields: {
			type: Object,
			required: true
		},
		tableName: {
			type: String,
			required: false
		},
		locale: {
			type: String,
			required: false
		},
		__: {
			type: Object,
			required: true
		},
		phoneCountries: {
			type: Object,
			required: true
		},
		availableForcedActions: {
			type: Object,
			required: false,
			default: []
		},
		availableLocales: {
			type: Object,
			required: true
		},
		availableTimezones: {
			type: Object,
			required: true
		},
		availableStartPages: {
			type: Object,
			required: true
		},
		permissions: {
			type: Object,
			required: true
		}
	},
	setup(__props) {
		const page = usePage();
		const toast = useToast();
		const props = __props;
		const UButton = _sfc_main$22;
		const UDropdownMenu = _sfc_main$10;
		const tableHeaderComponent = {
			UDropdownMenu,
			UButton
		};
		const usersLoading = ref(false);
		const rowSelection = ref({});
		const invitationsLoading = ref(false);
		const invitationsRowSelection = ref({});
		const currentUserId = computed(() => page.props.auth?.user?.id ?? null);
		const permissions = computed(() => {
			return props.permissions ?? {
				view: true,
				create: false,
				edit: false,
				delete: false,
				restore: false,
				forceDelete: false,
				view_sensitive: false
			};
		});
		function reloadUsers(params = {}) {
			usersLoading.value = true;
			router.get("/users", {
				...props.filters,
				...params
			}, {
				only: ["users", "filters"],
				preserveScroll: true,
				preserveState: true,
				replace: true,
				async: true,
				onFinish: () => {
					usersLoading.value = false;
				}
			});
		}
		function reloadInvitations(params = {}) {
			invitationsLoading.value = true;
			router.get("/users", {
				...props.filters,
				...params
			}, {
				only: ["invitations", "filters"],
				preserveScroll: true,
				preserveState: true,
				replace: true,
				async: true,
				onFinish: () => {
					invitationsLoading.value = false;
				}
			});
		}
		usePageShortcutScope("dashboard.users.index");
		const { registerShortcuts } = useShortcutRegistry();
		onBeforeUnmount(registerShortcuts([{
			key: "c",
			label: "Créer un utilisateur",
			description: "Ouvre le formulaire de création rapide.",
			scope: "page",
			ownerId: "dashboard.users.index",
			group: "Utilisateurs",
			order: 10,
			handler: () => {
				openQuickCreate();
			}
		}, {
			key: "meta_f",
			label: "Rechercher dans les utilisateurs",
			description: "Place le focus dans le champ de recherche.",
			scope: "page",
			ownerId: "dashboard.users.index",
			group: "Navigation",
			order: 20,
			handler: () => {
				document.querySelector("[data-users-search]")?.focus();
			}
		}], "dashboard-users-page"));
		const detailsDrawerOpen = ref(false);
		const detailsUserId = ref(null);
		function openUserDetails(row) {
			const selectedUser = row.original ?? row;
			if (!selectedUser?.id || selectedUser.permissions?.view === false) return;
			detailsUserId.value = selectedUser.id;
			detailsDrawerOpen.value = true;
		}
		function destroyDetailsDrawer() {
			if (!detailsDrawerOpen.value) detailsUserId.value = null;
		}
		const discardModalOpen = ref(false);
		const discardModalConfig = ref({
			title: "",
			description: "",
			keepLabel: "",
			discardLabel: "",
			discardColor: "",
			keepColor: "",
			onKeep: null,
			onDiscard: null
		});
		function openDiscardModal(config) {
			discardModalConfig.value = {
				title: config.title ?? props.__.modals.discard.title,
				description: config.description ?? props.__.modals.discard.description,
				keepLabel: config.keepLabel ?? props.__.actions.keep,
				discardLabel: config.discardLabel ?? props.__.actions.discard,
				discardColor: config.discardColor ?? "error",
				keepColor: config.keepColor ?? "neutral",
				onKeep: config.onKeep ?? null,
				onDiscard: config.onDiscard ?? null
			};
			discardModalOpen.value = true;
		}
		function closeDiscardModal() {
			discardModalOpen.value = false;
		}
		async function requestDiscardModal(action, config = discardModalConfig.value) {
			openDiscardModal({
				...config,
				onDiscard: async () => await action(),
				onKeep: () => closeDiscardModal()
			});
		}
		async function handleDiscardKeep() {
			const callback = discardModalConfig.value.onKeep;
			discardModalOpen.value = false;
			if (callback) await callback();
		}
		async function handleDiscardDiscard() {
			const callback = discardModalConfig.value.onDiscard;
			discardModalOpen.value = false;
			if (callback) await callback();
		}
		const identityModalOpen = ref(false);
		const identityModalConfig = ref({
			title: "",
			subtitle: "",
			fields: {
				password: "",
				method: "",
				code: ""
			},
			methods: {},
			actions: {
				cancel: "",
				confirmButton: "",
				passkeyButton: "",
				resendEmailCode: ""
			},
			emailSentTo: "",
			errors: { invalidPasskey: "" },
			onConfirmed: null
		});
		function openIdentityModal(config) {
			const trans = props.__.global.confirm_identity;
			identityModalConfig.value = {
				title: config.title ?? trans.title,
				subtitle: config.subtitle ?? trans.subtitle,
				fields: {
					password: trans.fields.password,
					method: trans.fields.method,
					code: trans.fields.code
				},
				actions: {
					cancel: trans.actions.cancel,
					confirmButton: trans.actions.confirm,
					passkeyButton: trans.actions.passkey,
					resendEmailCode: trans.actions.resend_email_code
				},
				methods: trans.methods,
				emailSentTo: trans.messages.email_sent_to,
				errors: { invalidPasskey: trans.errors.invalid_passkey },
				onConfirmed: config.onConfirmed ?? null
			};
			identityModalOpen.value = true;
		}
		function requestIdentityConfirmation(action) {
			openIdentityModal({ onConfirmed: action });
		}
		async function runPendingSensitiveAction() {
			const action = identityModalConfig.value.onConfirmed;
			identityModalConfig.value.onConfirmed = null;
			if (action) await action();
		}
		const bulkActionModalOpen = ref(false);
		const bulkActionLoading = ref(false);
		const bulkActionConfig = ref({
			type: "delete",
			translations: {},
			selectedRows: [],
			actionableRows: []
		});
		function getSelectedUsers() {
			return props.users.data.filter((user) => rowSelection.value[user.id]);
		}
		function openBulkActionModal(type, actionableRows) {
			bulkActionConfig.value = {
				type,
				translations: props.__.modals.bulk_actions[type],
				selectedRows: getSelectedUsers(),
				actionableRows
			};
			bulkActionModalOpen.value = true;
		}
		function handleBulkDelete(rows) {
			openBulkActionModal("delete", rows);
		}
		function handleBulkForceDelete(rows) {
			openBulkActionModal("force_delete", rows);
		}
		function handleBulkRestore(rows) {
			openBulkActionModal("restore", rows);
		}
		function confirmBulkAction() {
			const config = bulkActionConfig.value;
			const ids = config.actionableRows.map((row) => row.id);
			if (!ids.length || bulkActionLoading.value) return;
			bulkActionLoading.value = true;
			const options = {
				preserveScroll: true,
				preserveState: true,
				only: [
					"users",
					"filters",
					"flash"
				],
				onSuccess: () => {
					rowSelection.value = {};
					bulkActionModalOpen.value = false;
				},
				onFinish: () => {
					bulkActionLoading.value = false;
				}
			};
			if (config.type === "delete") {
				router.delete("/users/bulk/delete", {
					...options,
					data: { ids }
				});
				return;
			}
			if (config.type === "force_delete") {
				router.delete("/users/bulk/force-delete", {
					...options,
					data: { ids }
				});
				return;
			}
			if (config.type === "restore") router.patch("/users/bulk/restore", { ids }, options);
		}
		const rolesModalOpen = ref(false);
		const rolesModalUser = ref(null);
		function openRolesModal(user) {
			rolesModalUser.value = user;
			rolesModalOpen.value = true;
		}
		const expandedAdminNotes = ref({});
		function toggleAdminNote(userId) {
			const key = String(userId);
			expandedAdminNotes.value[key] = !expandedAdminNotes.value[key];
		}
		function isAdminNoteExpanded(userId) {
			return expandedAdminNotes.value[String(userId)] === true;
		}
		const columns = computed(() => [
			{
				accessorKey: "id",
				header: "#ID",
				cell: ({ row }) => `#${row.original.id}`
			},
			{
				accessorKey: "username",
				header: ({ column }) => getHeader(column, props.__.tables.users.header.username, tableHeaderComponent),
				meta: { class: { td: "text-primary-light font-semibold min-w-20" } }
			},
			{
				accessorKey: "email",
				header: ({ column }) => getHeader(column, props.__.tables.users.header.contact, tableHeaderComponent)
			},
			{
				accessorKey: "active",
				header: props.__.tables.users.header.active
			},
			...permissions.value.view_sensitive ? [{
				accessorKey: "admin_notes",
				header: props.__.tables.users.header.admin_notes,
				enableSorting: false,
				meta: { class: { td: "max-w-72 whitespace-normal" } }
			}] : [],
			{
				accessorKey: "updated_at",
				header: ({ column }) => getHeader(column, props.__.tables.users.header.updated_at, tableHeaderComponent)
			},
			{
				id: "actions",
				header: "",
				enableHiding: false
			}
		]);
		const invitationsColumns = [
			{
				accessorKey: "id",
				header: "#ID",
				cell: ({ row }) => `#${row.original.id}`
			},
			{
				accessorKey: "email",
				header: ({ column }) => getHeader(column, props.__.tables.invitations.header.email, tableHeaderComponent)
			},
			{
				accessorKey: "username",
				header: ({ column }) => getHeader(column, props.__.tables.invitations.header.username, tableHeaderComponent),
				cell: ({ row }) => row.original.username ?? props.__.tables.invitations.cell.username.undefined
			},
			{
				accessorKey: "status_label",
				header: props.__.tables.invitations.header.status
			},
			{
				accessorKey: "expires_at",
				header: props.__.tables.invitations.header.expires_at,
				cell: ({ row }) => row.original.expires_at ?? props.__.tables.invitations.cell.expires_at.no_date
			},
			{
				accessorKey: "created_at",
				header: ({ column }) => getHeader(column, props.__.tables.invitations.header.created_at, tableHeaderComponent)
			},
			{
				accessorKey: "invited_by",
				header: props.__.tables.invitations.header.invited_by,
				cell: ({ row }) => row.original.invited_by?.username ?? props.__.tables.invitations.cell.invited_by.unknown
			},
			{
				id: "actions",
				header: "",
				enableHiding: false
			}
		];
		const { copy } = useClipboard();
		const activeUserModalTab = ref("data");
		function isBlank(value) {
			return value === null || value === void 0 || String(value).trim() === "";
		}
		const isDataTabValid = computed(() => {
			return !isBlank(userDataForm.username) && !isBlank(userDataForm.email);
		});
		const isSecurityTabValid = computed(() => {
			return score.value >= 5 && samePassword.value;
		});
		const isAdminTabValid = computed(() => {
			return true;
		});
		const isCurrentTabValid = computed(() => {
			if (activeUserModalTab.value === "data") return isDataTabValid.value;
			if (activeUserModalTab.value === "security") return isSecurityTabValid.value;
			if (activeUserModalTab.value === "admin") return isAdminTabValid.value;
			return false;
		});
		const userTableViews = computed(() => [
			{
				label: props.__.views.active,
				value: "active",
				icon: "i-lucide-circle-check"
			},
			{
				label: props.__.views.trash,
				value: "trash",
				icon: "i-lucide-trash"
			},
			{
				label: props.__.views.all,
				value: "all",
				icon: "i-lucide-archive"
			}
		]);
		const quickDrawerOpen = ref(false);
		const quickDrawerMode = ref(null);
		const quickCreateFormRef = ref(null);
		const quickEditFormRef = ref(null);
		const selectedQuickUser = ref(null);
		const activeQuickForm = computed(() => {
			if (quickDrawerMode.value === "create") return quickCreateFormRef.value;
			if (quickDrawerMode.value === "edit") return quickEditFormRef.value;
			return null;
		});
		const quickDrawerTitle = computed(() => {
			if (quickDrawerMode.value === "create") return props.__.user_actions.fast_create;
			if (quickDrawerMode.value === "edit") return props.__.forms.users.edit.title;
			return "";
		});
		const quickDrawerDescription = computed(() => {
			if (quickDrawerMode.value === "edit") return `${props.__.forms.users.edit.description} "${selectedQuickUser.value?.username ?? ""}"`;
			return "";
		});
		const quickDrawerProcessing = computed(() => activeQuickForm.value?.processing ?? false);
		const quickDrawerDisabled = computed(() => activeQuickForm.value?.disabled ?? false);
		const quickDrawerDirty = computed(() => activeQuickForm.value?.dirty ?? false);
		function openQuickEdit(row) {
			selectedQuickUser.value = row.original;
			quickDrawerMode.value = "edit";
			quickDrawerOpen.value = true;
			nextTick(() => {
				quickEditFormRef.value?.open(selectedQuickUser.value);
			});
		}
		function submitQuickDrawer() {
			activeQuickForm.value?.submit();
		}
		function closeQuickDrawer() {
			activeQuickForm.value?.close();
			quickDrawerOpen.value = false;
			quickDrawerMode.value = null;
			selectedQuickUser.value = null;
		}
		function handleQuickDrawerOpenChange(value) {
			if (value) {
				quickDrawerOpen.value = true;
				return;
			}
			if (quickDrawerDirty.value) {
				quickDrawerOpen.value = true;
				openDiscardModal({
					onKeep: () => {
						quickDrawerOpen.value = true;
					},
					onDiscard: () => {
						closeQuickDrawer();
					}
				});
				return;
			}
			closeQuickDrawer();
		}
		function handleQuickDrawerSaved() {
			if (quickDrawerMode.value === "create") toast.add({
				title: props.__.resources.users.messages.created.title,
				description: `${props.__.resources.users.messages.created.description}`.replace(":user", selectedQuickUser.value?.username ?? "unknown"),
				color: "success",
				icon: "i-lucide-circle-check"
			});
			else if (quickDrawerMode.value === "edit") toast.add({
				title: props.__.resources.users.messages.updated.title,
				description: `${props.__.resources.users.messages.updated.description}`.replace(":user", selectedQuickUser.value?.username ?? "unknown"),
				color: "success",
				icon: "i-lucide-circle-check"
			});
			closeQuickDrawer();
		}
		function openQuickCreate() {
			quickDrawerMode.value = "create";
			selectedQuickUser.value = null;
			quickDrawerOpen.value = true;
			nextTick(() => {
				quickCreateFormRef.value?.open();
			});
		}
		function getRowEditActionsDropdownItems(row) {
			if (row.original.id === currentUserId.value) return [{
				label: props.__.user_actions.fast_edit,
				icon: "i-lucide-panel-right-open",
				onSelect() {
					openQuickEdit(row);
				}
			}];
			return [[{
				label: props.__.user_actions.fast_edit,
				icon: "i-lucide-panel-right-open",
				onSelect() {
					openQuickEdit(row);
				}
			}], [{
				label: props.__.user_actions.reset_password,
				icon: "i-lucide-rotate-ccw-key",
				color: "warning"
			}, {
				label: props.__.user_actions.revoke_sessions,
				icon: "i-lucide-shield-off",
				color: "warning"
			}]];
		}
		function onDeleteUser(user) {
			const identityModalConfig = {
				title: props.__.resources.users.confirmations.delete.title,
				description: `${props.__.resources.users.confirmations.delete.description}`.replace(":user", selectedQuickUser.value?.username ?? "unknown")
			};
			if (user.is_deleted) {
				openIdentityModal({
					...identityModalConfig,
					onConfirmed: () => {
						router.delete(`/users/${user.id}/force-delete`, {
							preserveState: true,
							preserveScroll: true,
							only: ["users", "filters"]
						});
					}
				});
				return;
			}
			requestIdentityConfirmation(() => {
				router.delete(`/users/${user.id}/delete`, {
					preserveState: true,
					preserveScroll: true,
					only: ["users", "filters"],
					onSuccess: () => {
						toast.add({
							title: props.__.resources.users.messages.deleted.title,
							description: `${props.__.resources.users.messages.deleted.description}`.replace(":user", user.username ?? "unknown").replace(":retention_days", page.props.app.config.retention_days),
							color: "success"
						});
					},
					onError: (errors) => {
						if (errors.toast) {
							const error = JSON.parse(errors.toast);
							toast.add({
								title: error.title,
								description: error.description,
								color: "error"
							});
						}
					}
				});
			});
		}
		const userModalOpen = ref(false);
		const userModalMode = ref(null);
		const userDataForm = useForm({
			username: "",
			email: "",
			first_name: "",
			last_name: "",
			job_title: "",
			phone: {
				extension: "",
				number: ""
			}
		});
		const userSecurityForm = useForm({
			new_password: "",
			confirm_password: ""
		});
		const userAdminForm = useForm({
			active: true,
			admin_notes: "",
			suspended_until: null,
			suspension_reason: null,
			expire_at: null,
			roles: [],
			forced_actions: [],
			repo_scope: [],
			host_scope: [],
			host_scope_mode: ""
		});
		const userSettingsForm = useForm({
			preferred_locale: "",
			preferred_timezone: "",
			preferred_start_page: ""
		});
		const initialUserDataForm = ref({});
		ref({});
		ref({});
		const initialUserSettingsForm = ref({});
		const isCreateModal = computed(() => userModalMode.value === "create");
		const isEditModal = computed(() => userModalMode.value === "edit");
		const userModalTitle = computed(() => {
			return isEditModal.value ? props.__.user_actions.edit ?? "Edit User" : props.__.user_actions.create ?? "Create User";
		});
		const currentTabProcessing = computed(() => {
			if (activeUserModalTab.value === "data") return userDataForm.processing;
			if (activeUserModalTab.value === "security") return userSecurityForm.processing;
			if (activeUserModalTab.value === "admin") return userAdminForm.processing;
			return false;
		});
		ref(false);
		function fillDataUserForm(user = null) {
			if (!user) return {
				username: "",
				email: "",
				first_name: "",
				last_name: "",
				job_title: "",
				phone: {
					extension: "",
					number: ""
				}
			};
			return {
				username: user.username,
				email: user.email,
				first_name: user.first_name ?? "",
				last_name: user.last_name ?? "",
				job_title: user.job_title ?? "",
				phone: {
					extension: user.phone.extension ?? "",
					number: user.phone.number ?? ""
				}
			};
		}
		function fillSecurityUserForm() {
			return {
				new_password: "",
				confirm_password: ""
			};
		}
		function fillAdminUserForm(user = null) {
			if (!user) return {
				active: true,
				admin_notes: "",
				suspended_until: calendarDateFromTimestamp(null),
				suspended_reason: null,
				expire_at: calendarDateFromTimestamp(null),
				roles: [],
				forced_actions: []
			};
			return {
				active: user.active ?? true,
				admin_notes: user.admin_notes ?? "",
				suspended_until: calendarDateFromTimestamp(user.suspended_until) ?? calendarDateFromTimestamp(null),
				suspension_reason: user.suspension_reason ?? null,
				expire_at: calendarDateFromTimestamp(user.expire_at) ?? calendarDateFromTimestamp(null),
				roles: user.roles ?? [],
				forced_actions: user.forced_actions ?? []
			};
		}
		function fillSettingsUserForm(user = null) {
			if (!user) return {
				preferred_locale: "en_US",
				preferred_timezone: "UTC",
				preferred_start_page: "dashboard"
			};
			return {
				preferred_locale: user.preferred_locale ?? "en_US",
				preferred_timezone: user.preferred_timezone ?? "UTC",
				preferred_start_page: user.preferred_start_page ?? "dashboard"
			};
		}
		function closeUserModal() {
			userModalMode.value = null;
			userDataForm.defaults(fillDataUserForm());
			userSettingsForm.defaults(fillSettingsUserForm());
			userSecurityForm.defaults(fillSecurityUserForm());
			userAdminForm.defaults(fillAdminUserForm());
			userModalOpen.value = false;
		}
		function submitUserModal(userId = null) {
			if (!isCurrentTabValid.value) return;
			if (isCreateModal.value) {
				submitCreateUser();
				return;
			}
			if (!userEditing.value) return;
			if (activeUserModalTab.value === "data") {
				userDataForm.patch(`/users/${userEditing.value}/data`, {
					only: [
						"users",
						"filters",
						"flash"
					],
					preserveScroll: true,
					preserveState: true,
					onSuccess: () => {
						userDataForm.defaults();
					}
				});
				return;
			}
			if (activeUserModalTab.value === "security") {
				requestIdentityConfirmation(() => {
					userSecurityForm.patch(`users/${userEditing.value}/security`, {
						only: [
							"users",
							"filters",
							"flash"
						],
						preserveScroll: true,
						preserveState: true,
						onSuccess: () => {
							userSecurityForm.reset();
							userSecurityForm.defaults();
						}
					});
				});
				return;
			}
			if (activeUserModalTab.value === "admin") requestIdentityConfirmation(() => {
				userAdminForm.patch(`users/${userEditing.value}/admin`, {
					only: [
						"users",
						"filters",
						"flash"
					],
					preserveScroll: true,
					preserveState: true,
					onSuccess: () => {
						userAdminForm.defaults();
					}
				});
			});
		}
		function handleUserModalOpenChange(value) {
			if (value) {
				userModalOpen.value = true;
				return;
			}
			requestCloseUserModal();
		}
		function submitCreateUser() {
			const payload = {
				...userDataForm.data(),
				...userSecurityForm.data(),
				...userAdminForm.data()
			};
			router.post("/users", payload, {
				only: [
					"users",
					"filters",
					"flash"
				],
				preserveScroll: true,
				preserveState: true,
				onSuccess: closeUserModal
			});
		}
		const userDataAccordionActive = ref("0");
		const userDataAccordionItems = [
			{
				label: props.__.forms.users.sections.login,
				slot: "login",
				icon: "i-lucide-log-in"
			},
			{
				label: props.__.forms.users.sections.data,
				slot: "personal-data",
				icon: "i-lucide-clipboard-pen-line"
			},
			{
				label: props.__.forms.users.sections.settings,
				slot: "settings",
				icon: "i-lucide-settings"
			}
		];
		const userAdminAccordionActive = ref("0");
		const userAdminAccordionItems = [
			{
				label: props.__.forms.users.sections.access,
				slot: "access",
				icon: "i-lucide-key-round"
			},
			{
				label: props.__.forms.users.sections.notes,
				slot: "notes",
				icon: "i-lucide-notepad-text"
			},
			{
				label: props.__.forms.users.sections.constraints,
				slot: "constraints",
				icon: "i-lucide-shield-alert"
			}
		];
		const tabsItems = computed(() => {
			const mode = userModalMode.value;
			const verb = props.__.user_actions.verbs[mode];
			return [
				{
					value: "data",
					label: props.__.tabs.data.title,
					description: `${verb ?? ""} ${props.__.tabs.data.description}`,
					icon: "i-lucide-user",
					slot: "data",
					dirty: userDataForm.isDirty
				},
				{
					value: "security",
					label: props.__.tabs.security.title,
					description: `${verb ?? ""} ${props.__.tabs.security.description}`,
					icon: "i-lucide-lock-keyhole",
					slot: "security",
					dirty: userSecurityForm.isDirty
				},
				{
					value: "admin",
					label: props.__.tabs.admin.title,
					description: props.__.tabs.admin.description,
					icon: "i-lucide-shield-check",
					slot: "admin",
					dirty: userAdminForm.isDirty
				}
			];
		});
		const confirmDiscardModalOpen = ref(false);
		ref("");
		const hasUnsavedModalChanges = computed(() => {
			return userDataForm.isDirty || userSecurityForm.isDirty || userAdminForm.isDirty || userSettingsForm.isDirty;
		});
		function requestCloseUserModal() {
			if (hasUnsavedModalChanges.value) {
				confirmDiscardModalOpen.value = true;
				userModalOpen.value = true;
				return;
			}
			closeUserModal();
		}
		const showPassword = ref(false);
		const showConfirmation = ref(false);
		function checkStrength(str) {
			return [
				{
					regex: /.{8,}/,
					text: props.__.resources.users.validation.password.length
				},
				{
					regex: /\d/,
					text: props.__.resources.users.validation.password.number
				},
				{
					regex: /[a-z]/,
					text: props.__.resources.users.validation.password.lowercase
				},
				{
					regex: /[A-Z]/,
					text: props.__.resources.users.validation.password.uppercase
				},
				{
					regex: /[!"#$%&'()*+,\-./:;<=>?@[\\\]^_`{|}~]/,
					text: props.__.resources.users.validation.password.symbol
				}
			].map((req) => ({
				met: req.regex.test(str),
				text: req.text
			}));
		}
		function checkSame(str1, str2) {
			return str1 === str2 && str1 !== "" && str2 !== "";
		}
		const strengthPassword = computed(() => checkStrength(userSecurityForm.new_password ?? ""));
		const score = computed(() => strengthPassword.value.filter((req) => req.met).length);
		const samePassword = computed(() => checkSame(userSecurityForm.new_password, userSecurityForm.confirm_password ?? ""));
		const colorPassword = computed(() => {
			if (score.value === 0) return "neutral";
			if (score.value <= 1) return "error";
			if (score.value <= 2) return "warning";
			if (score.value <= 4) return "warning";
			return "success";
		});
		const colorConfirmation = computed(() => {
			if (!samePassword.value) return "error";
			return "success";
		});
		const confirmationText = computed(() => {
			if (!samePassword.value) return "Password don't match";
			return "Password match!";
		});
		const validatorText = computed(() => {
			if (score.value === 0) return "Enter a password";
			if (score.value <= 2) return "Weak password";
			if (score.value <= 4) return "Medium password";
			return "Strong password";
		});
		const isAccountExpired = computed(() => {
			const expireAt = userAdminForm.expire_at;
			const suspended_until = userAdminForm.suspended_until;
			const suspension_reason = userAdminForm.suspension_reason;
			if (expireAt) return expireAt.compare(today(getLocalTimeZone())) < 0;
			if (suspended_until) return suspended_until.compare(today(getLocalTimeZone())) > 0;
			return !!suspension_reason;
		});
		watch(isAccountExpired, (expired) => {
			if (expired) userAdminForm.active = false;
		});
		function calendarDateFromTimestamp(timestamp) {
			if (!timestamp) return null;
			return toCalendarDate(fromDate(new Date(timestamp), getLocalTimeZone()));
		}
		const countryOpen = ref(false);
		const countrySearch = ref("");
		const selectedCountry = computed(() => {
			return countries.find((country) => {
				return String(country.extension) === String(quickEditForm.phone.extension);
			}) ?? null;
		});
		const filteredCountries = computed(() => {
			const search = countrySearch.value.trim().toLowerCase();
			if (!search) return countries;
			return countries.filter((country) => {
				return country.name.toLowerCase().includes(search) || country.extension.includes(search) || `+${country.extension}`.includes(search);
			});
		});
		function selectCountry(country) {
			quickEditForm.phone.extension = country.extension;
			countryOpen.value = false;
			countrySearch.value = "";
		}
		function updateNumber(value) {
			quickEditForm.phone.number = String(value) ?? "";
		}
		const roles = ref([]);
		const rolesLoading = ref(false);
		async function fetchRoles() {
			rolesLoading.value = true;
			try {
				roles.value = (await (await fetch("/roles/select-menu", {
					method: "POST",
					headers: csrfHeaders()
				})).json() ?? []).map((role) => ({
					label: `${role.name}`,
					project: role.project?.name ? role.project.name : "Global",
					value: role.id
				}));
			} finally {
				rolesLoading.value = false;
			}
		}
		onMounted(async () => {
			await fetchRoles();
		});
		const inviteModalOpen = ref(false);
		const inviteMode = ref("email");
		const inviteForm = useForm({
			username: "",
			email: "",
			roles: [],
			forced_actions: [],
			admin_notes: ""
		});
		function openInviteModal(mode) {
			inviteModalOpen.value = true;
			inviteMode.value = mode;
			inviteForm.reset();
			inviteForm.clearErrors();
		}
		function closeInviteModal() {
			inviteModalOpen.value = false;
			inviteForm.reset();
			inviteForm.clearErrors();
		}
		function submitInviteForm() {
			router.post("/users/invite", {
				...inviteForm.data(),
				mode: inviteMode.value
			}, {
				only: [
					"users",
					"invitations",
					"filters",
					"flash"
				],
				preserveState: true,
				preserveScroll: true,
				onSuccess: () => {
					closeInviteModal();
					toast.add({
						title: props.__.resources.invitations.messages.sent,
						color: "success"
					});
				}
			});
		}
		const globalActionsDropdownItems = [{
			label: props.__.user_actions.invite,
			icon: "i-lucide-send",
			children: [{
				label: props.__.user_actions.invite_by_email,
				icon: "i-lucide-mail-plus",
				onSelect() {
					openInviteModal("email");
				}
			}, {
				label: props.__.user_actions.invite_by_link,
				icon: "i-lucide-link",
				onSelect() {
					openInviteModal("link");
				}
			}]
		}, {
			label: props.__.user_actions.create,
			icon: "i-lucide-user-plus",
			onSelect() {
				router.get("/users/create");
			}
		}];
		function getRowContextMenuItems(row) {
			const user = row.original;
			const phone = user.phone?.extension && user.phone?.phone ? `+${user.phone.extension}${user.phone.phone}` : null;
			const roles = Array.isArray(user.roles) ? user.roles : [];
			roles.length > 0 ? roles.map((role) => role.name).join(", ") : props.__.table.context_menu.roles.empty;
			return [
				{
					type: "label",
					label: props.__.context_menu.sections.copy
				},
				{
					label: props.__.context_menu.copy_email,
					icon: "i-lucide-mail",
					ui: { item: "cursor-pointer rounded-md" },
					onSelect() {
						copy(user.email);
						toast.add({
							title: props.__.toasts.copy_email.title,
							color: "success",
							icon: "i-lucide-circle-check"
						});
					}
				},
				{
					label: props.__.context_menu.copy_identifier,
					icon: "i-lucide-hash",
					ui: { item: "cursor-pointer rounded-md" },
					onSelect() {
						copy(String(user.id));
						toast.add({
							title: props.__.toasts.copy_identifier.title,
							color: "success",
							icon: "i-lucide-circle-check"
						});
					}
				},
				phone && {
					label: props.__.context_menu.copy_phone,
					icon: "i-lucide-phone",
					ui: { item: "cursor-pointer rounded-md" },
					onSelect() {
						copy(phone);
						toast.add({
							title: props.__.toasts.copy_phone.title,
							color: "success",
							icon: "i-lucide-circle-check"
						});
					}
				},
				{ type: "separator" },
				{
					type: "label",
					label: props.__.context_menu.sections.inspect
				},
				user.permissions.view_sensitive && {
					label: props.__.context_menu.view_roles,
					icon: "i-lucide-shield",
					ui: { item: "cursor-pointer rounded-md" },
					onSelect() {
						openRolesModal(user);
					}
				},
				{
					label: props.__.context_menu.view_logs,
					icon: "i-lucide-scroll-text",
					ui: { item: "cursor-pointer rounded-md" },
					onSelect() {
						router.get("/logs", { user_id: user.id }, { preserveScroll: true });
					}
				}
			].filter(Boolean);
		}
		function getInvitationActionRowItems(row) {
			const arr = [];
			if (row.original.status === "pending") arr.push([{
				label: props.__.invitation_actions.resend_email,
				icon: "i-lucide-send",
				color: "info",
				onSelect() {
					resendInvitationMail(row);
				}
			}]);
			if (row.original.status === "pending" || row.original.status === "expired") arr.push([{
				label: props.__.invitation_actions.renew_expiration,
				icon: "i-lucide-rotate-ccw-key",
				color: "warning",
				kbds: ["meta", "d"],
				onSelect() {
					openRenewModal(row);
				}
			}, {
				label: props.__.invitation_actions.remove_expiration,
				icon: "i-lucide-timer-off",
				color: "error",
				onSelect() {
					removeInvitationExpiration(row);
				}
			}]);
			if (row.original.is_deleted) arr.push([{
				label: props.__.invitation_actions.restore,
				icon: "i-lucide-rotate-ccw",
				color: "success",
				onSelect() {
					restoreInvite(row);
				}
			}]);
			arr.push([{
				label: row.original.is_deleted ? props.__.invitation_actions.force_delete : props.__.invitation_actions.delete,
				icon: "i-lucide-trash",
				color: "error",
				onSelect() {
					if (row.original.is_deleted) return forceDeleteInvite(row);
					else return deleteInvite(row);
				}
			}]);
			return arr;
		}
		function getInvitationContextMenuItems(row) {
			if (row.original.status === "revoked") return [];
			return [{
				label: props.__.context_menu.copy_link,
				icon: "i-lucide-copy",
				color: "neutral",
				onSelect() {
					copyInvitationLink(row.original);
				}
			}];
		}
		async function copyInvitationLink(invitation) {
			const response = await fetch(`/users/invitations/${invitation.id}/link`, { headers: csrfHeaders() });
			if (!response.ok) {
				toast.add({
					title: props.__.resources.invitations.messages.link_unavailable,
					color: "error",
					icon: "i-lucide-circle-x"
				});
				return;
			}
			await copy((await response.json()).link);
			toast.add({
				title: props.__.toasts.invite_link_copy.title,
				color: "success",
				icon: "i-lucide-copy-check"
			});
		}
		function resendInvitationMail(row) {
			requestDiscardModal(() => {
				router.post(`/users/invitations/${row.original.id}/send-email`, {}, {
					only: ["flash"],
					preserveScroll: true,
					preserveState: true
				});
			}, {
				...discardModalConfig.value,
				title: props.__.global.modals.confirm.title,
				description: `${props.__.global.modals.confirm.description}`.replace(":action", `${props.__.invitation_actions.resend_email}`.toLowerCase()),
				discardLabel: props.__.invitation_actions.resend_email,
				keepLabel: props.__.actions.cancel,
				discardColor: "warning"
			});
		}
		const expirationModalOpen = ref(false);
		const invitationEditing = ref(null);
		const renewInvitationExpirationForm = useForm({
			renew_amount: 7,
			expiration_date: null
		});
		function openRenewModal(row) {
			expirationModalOpen.value = true;
			invitationEditing.value = row.original.id;
		}
		function closeRenewModal() {
			expirationModalOpen.value = false;
			invitationEditing.value = null;
		}
		function submitRenewExpirationForm() {
			requestDiscardModal(() => {
				renewInvitationExpirationForm.transform((data) => ({
					...data,
					expiration_date: serializeDate(data.expiration_date)
				})).post(`/users/invitations/${invitationEditing.value}/renew`, {
					only: ["invitations", "flash"],
					preserveState: true,
					preserveScroll: true,
					onSuccess: () => {
						closeRenewModal();
					}
				});
			}, {
				...discardModalConfig.value,
				title: props.__.global.modals.confirm.title,
				description: `${props.__.global.modals.confirm.description}`.replace(":action", `${props.__.invitation_actions.renew_expiration}`.toLowerCase()),
				discardLabel: props.__.invitation_actions.renew_expiration,
				keepLabel: props.__.actions.cancel,
				discardColor: "warning"
			});
		}
		function removeInvitationExpiration(row) {
			requestDiscardModal(() => {
				router.post(`/users/invitations/${row.original.id}/remove-expiration`, {}, {
					only: ["invitations", "flash"],
					preserveState: true,
					preserveScroll: true
				});
			}, {
				...discardModalConfig.value,
				title: props.__.global.modals.confirm.title,
				description: `${props.__.global.modals.confirm.description}`.replace(":action", `${props.__.invitation_actions.renew_expiration}`.toLowerCase()),
				discardLabel: props.__.invitation_actions.renew_expiration,
				keepLabel: props.__.actions.cancel,
				discardColor: "warning"
			});
		}
		function reactiveInvite(row) {
			requestIdentityConfirmation(() => {
				router.post(`/users/invitations/${row.original.id}/reactive`, {}, {
					only: [
						"invitations",
						"flash",
						"filters"
					],
					preserveState: true,
					preserveScroll: true
				});
			});
		}
		function revokeInvite(row) {
			requestIdentityConfirmation(() => {
				router.post(`/users/invitations/${row.original.id}/revoke`, {}, {
					only: [
						"invitations",
						"filters",
						"flash"
					],
					preserveScroll: true,
					preserveState: true
				});
			});
		}
		function restoreInvite(row) {
			router.post(`/users/invitations/${row.original.id}/restore`, {}, {
				only: ["invitations", "flash"],
				preserveScroll: true,
				preserveState: true
			});
		}
		function deleteInvite(row) {
			requestIdentityConfirmation(() => {
				router.delete(`/users/invitations/${row.original.id}/delete`, {
					only: ["invitations", "flash"],
					preserveScroll: true,
					preserveState: true
				});
			});
		}
		function forceDeleteInvite(row) {
			requestIdentityConfirmation(() => {
				router.delete(`/users/invitations/${row.original.id}/force-delete`, {
					only: ["invitations", "flash"],
					preserveScroll: true,
					preserveState: true
				});
			});
		}
		return (_ctx, _push, _parent, _attrs) => {
			const _component_UModal = _sfc_main$12;
			const _component_UForm = _sfc_main$28;
			const _component_UFormField = _sfc_main$27;
			const _component_UInput = _sfc_main$26;
			const _component_UCheckboxGroup = _sfc_main$13;
			const _component_USelectMenu = _sfc_main$34;
			const _component_UInputNumber = _sfc_main$15;
			const _component_UBadge = _sfc_main$38;
			const _component_UAlert = _sfc_main$29;
			const _component_UTabs = _sfc_main$16;
			const _component_UChip = _sfc_main$25;
			const _component_UAccordion = _sfc_main$17;
			const _component_UFieldGroup = _sfc_main$18;
			const _component_UPopover = _sfc_main$36;
			const _component_USelect = _sfc_main$32;
			const _component_UProgress = _sfc_main$30;
			const _component_UIcon = _sfc_main$20;
			const _component_USeparator = _sfc_main$19;
			const _component_UTextarea = _sfc_main$37;
			const _component_USwitch = _sfc_main$35;
			_push(`<!--[-->`);
			_push(ssrRenderComponent(_component_UModal, {
				open: inviteModalOpen.value,
				"onUpdate:open": ($event) => inviteModalOpen.value = $event,
				class: "max-w-9/10 w-9/10 md:w-7/10 lg:w-6/10"
			}, {
				header: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(`<h2 class="text-lg font-semibold"${_scopeId}>${ssrInterpolate(props.__.modals.invite.title)} ${ssrInterpolate(props.__.modals.invite.mode[inviteMode.value])}</h2>`);
					else return [createVNode("h2", { class: "text-lg font-semibold" }, toDisplayString(props.__.modals.invite.title) + " " + toDisplayString(props.__.modals.invite.mode[inviteMode.value]), 1)];
				}),
				body: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<p class="text-muted"${_scopeId}>${ssrInterpolate(props.__.modals.discard.description)}</p>`);
						_push(ssrRenderComponent(_component_UForm, {
							state: unref(inviteForm),
							class: "flex flex-col gap-4 mt-5",
							onSubmit: submitInviteForm
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) {
									_push(`<div class="flex gap-4 justify-between"${_scopeId}>`);
									_push(ssrRenderComponent(_component_UFormField, {
										required: inviteMode.value === "email",
										class: "w-full",
										label: props.__.resources.users.fields.email
									}, {
										default: withCtx((_, _push, _parent, _scopeId) => {
											if (_push) _push(ssrRenderComponent(_component_UInput, {
												modelValue: unref(inviteForm).email,
												"onUpdate:modelValue": ($event) => unref(inviteForm).email = $event,
												placeholder: " ",
												ui: { base: "peer" },
												required: "",
												class: "w-full"
											}, null, _parent, _scopeId));
											else return [createVNode(_component_UInput, {
												modelValue: unref(inviteForm).email,
												"onUpdate:modelValue": ($event) => unref(inviteForm).email = $event,
												placeholder: " ",
												ui: { base: "peer" },
												required: "",
												class: "w-full"
											}, null, 8, ["modelValue", "onUpdate:modelValue"])];
										}),
										_: 1
									}, _parent, _scopeId));
									_push(ssrRenderComponent(_component_UFormField, {
										help: props.__.forms.users.help.username_optional,
										hint: props.__.forms.users.hint.optional,
										name: "username",
										class: "w-full",
										label: props.__.resources.users.fields.username
									}, {
										default: withCtx((_, _push, _parent, _scopeId) => {
											if (_push) _push(ssrRenderComponent(_component_UInput, {
												modelValue: unref(inviteForm).username,
												"onUpdate:modelValue": ($event) => unref(inviteForm).username = $event,
												placeholder: " ",
												ui: { base: "peer" },
												required: "",
												class: "w-full"
											}, null, _parent, _scopeId));
											else return [createVNode(_component_UInput, {
												modelValue: unref(inviteForm).username,
												"onUpdate:modelValue": ($event) => unref(inviteForm).username = $event,
												placeholder: " ",
												ui: { base: "peer" },
												required: "",
												class: "w-full"
											}, null, 8, ["modelValue", "onUpdate:modelValue"])];
										}),
										_: 1
									}, _parent, _scopeId));
									_push(`</div><div class="flex flex-col gap-4 justify-between"${_scopeId}>`);
									_push(ssrRenderComponent(_component_UFormField, {
										label: props.__.resources.users.fields.forced_actions,
										hint: props.__.forms.users.hint.optional
									}, {
										default: withCtx((_, _push, _parent, _scopeId) => {
											if (_push) _push(ssrRenderComponent(_component_UCheckboxGroup, {
												indicator: "start",
												variant: "table",
												modelValue: unref(inviteForm).forced_actions,
												"onUpdate:modelValue": ($event) => unref(inviteForm).forced_actions = $event,
												items: __props.availableForcedActions,
												"value-key": "value"
											}, {
												label: withCtx(({ item }, _push, _parent, _scopeId) => {
													if (_push) _push(`${ssrInterpolate(item.label.title)}`);
													else return [createTextVNode(toDisplayString(item.label.title), 1)];
												}),
												description: withCtx(({ item }, _push, _parent, _scopeId) => {
													if (_push) _push(`${ssrInterpolate(item.label.description)}`);
													else return [createTextVNode(toDisplayString(item.label.description), 1)];
												}),
												_: 1
											}, _parent, _scopeId));
											else return [createVNode(_component_UCheckboxGroup, {
												indicator: "start",
												variant: "table",
												modelValue: unref(inviteForm).forced_actions,
												"onUpdate:modelValue": ($event) => unref(inviteForm).forced_actions = $event,
												items: __props.availableForcedActions,
												"value-key": "value"
											}, {
												label: withCtx(({ item }) => [createTextVNode(toDisplayString(item.label.title), 1)]),
												description: withCtx(({ item }) => [createTextVNode(toDisplayString(item.label.description), 1)]),
												_: 1
											}, 8, [
												"modelValue",
												"onUpdate:modelValue",
												"items"
											])];
										}),
										_: 1
									}, _parent, _scopeId));
									_push(ssrRenderComponent(_component_UFormField, {
										required: "",
										label: "Roles",
										class: "w-full"
									}, {
										default: withCtx((_, _push, _parent, _scopeId) => {
											if (_push) _push(ssrRenderComponent(_component_USelectMenu, {
												modelValue: unref(inviteForm).roles,
												"onUpdate:modelValue": ($event) => unref(inviteForm).roles = $event,
												"filter-fields": ["label", "project"],
												items: roles.value,
												loading: rolesLoading.value,
												multiple: "",
												class: "w-full"
											}, {
												"item-label": withCtx(({ item }, _push, _parent, _scopeId) => {
													if (_push) _push(`${ssrInterpolate(item.label)} <span class="text-muted"${_scopeId}>- ${ssrInterpolate(item.project)}</span>`);
													else return [createTextVNode(toDisplayString(item.label) + " ", 1), createVNode("span", { class: "text-muted" }, "- " + toDisplayString(item.project), 1)];
												}),
												_: 1
											}, _parent, _scopeId));
											else return [createVNode(_component_USelectMenu, {
												modelValue: unref(inviteForm).roles,
												"onUpdate:modelValue": ($event) => unref(inviteForm).roles = $event,
												"filter-fields": ["label", "project"],
												items: roles.value,
												loading: rolesLoading.value,
												multiple: "",
												class: "w-full"
											}, {
												"item-label": withCtx(({ item }) => [createTextVNode(toDisplayString(item.label) + " ", 1), createVNode("span", { class: "text-muted" }, "- " + toDisplayString(item.project), 1)]),
												_: 1
											}, 8, [
												"modelValue",
												"onUpdate:modelValue",
												"items",
												"loading"
											])];
										}),
										_: 1
									}, _parent, _scopeId));
									_push(`</div>`);
								} else return [createVNode("div", { class: "flex gap-4 justify-between" }, [createVNode(_component_UFormField, {
									required: inviteMode.value === "email",
									class: "w-full",
									label: props.__.resources.users.fields.email
								}, {
									default: withCtx(() => [createVNode(_component_UInput, {
										modelValue: unref(inviteForm).email,
										"onUpdate:modelValue": ($event) => unref(inviteForm).email = $event,
										placeholder: " ",
										ui: { base: "peer" },
										required: "",
										class: "w-full"
									}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
									_: 1
								}, 8, ["required", "label"]), createVNode(_component_UFormField, {
									help: props.__.forms.users.help.username_optional,
									hint: props.__.forms.users.hint.optional,
									name: "username",
									class: "w-full",
									label: props.__.resources.users.fields.username
								}, {
									default: withCtx(() => [createVNode(_component_UInput, {
										modelValue: unref(inviteForm).username,
										"onUpdate:modelValue": ($event) => unref(inviteForm).username = $event,
										placeholder: " ",
										ui: { base: "peer" },
										required: "",
										class: "w-full"
									}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
									_: 1
								}, 8, [
									"help",
									"hint",
									"label"
								])]), createVNode("div", { class: "flex flex-col gap-4 justify-between" }, [createVNode(_component_UFormField, {
									label: props.__.resources.users.fields.forced_actions,
									hint: props.__.forms.users.hint.optional
								}, {
									default: withCtx(() => [createVNode(_component_UCheckboxGroup, {
										indicator: "start",
										variant: "table",
										modelValue: unref(inviteForm).forced_actions,
										"onUpdate:modelValue": ($event) => unref(inviteForm).forced_actions = $event,
										items: __props.availableForcedActions,
										"value-key": "value"
									}, {
										label: withCtx(({ item }) => [createTextVNode(toDisplayString(item.label.title), 1)]),
										description: withCtx(({ item }) => [createTextVNode(toDisplayString(item.label.description), 1)]),
										_: 1
									}, 8, [
										"modelValue",
										"onUpdate:modelValue",
										"items"
									])]),
									_: 1
								}, 8, ["label", "hint"]), createVNode(_component_UFormField, {
									required: "",
									label: "Roles",
									class: "w-full"
								}, {
									default: withCtx(() => [createVNode(_component_USelectMenu, {
										modelValue: unref(inviteForm).roles,
										"onUpdate:modelValue": ($event) => unref(inviteForm).roles = $event,
										"filter-fields": ["label", "project"],
										items: roles.value,
										loading: rolesLoading.value,
										multiple: "",
										class: "w-full"
									}, {
										"item-label": withCtx(({ item }) => [createTextVNode(toDisplayString(item.label) + " ", 1), createVNode("span", { class: "text-muted" }, "- " + toDisplayString(item.project), 1)]),
										_: 1
									}, 8, [
										"modelValue",
										"onUpdate:modelValue",
										"items",
										"loading"
									])]),
									_: 1
								})])];
							}),
							_: 1
						}, _parent, _scopeId));
					} else return [createVNode("p", { class: "text-muted" }, toDisplayString(props.__.modals.discard.description), 1), createVNode(_component_UForm, {
						state: unref(inviteForm),
						class: "flex flex-col gap-4 mt-5",
						onSubmit: withModifiers(submitInviteForm, ["prevent"])
					}, {
						default: withCtx(() => [createVNode("div", { class: "flex gap-4 justify-between" }, [createVNode(_component_UFormField, {
							required: inviteMode.value === "email",
							class: "w-full",
							label: props.__.resources.users.fields.email
						}, {
							default: withCtx(() => [createVNode(_component_UInput, {
								modelValue: unref(inviteForm).email,
								"onUpdate:modelValue": ($event) => unref(inviteForm).email = $event,
								placeholder: " ",
								ui: { base: "peer" },
								required: "",
								class: "w-full"
							}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
							_: 1
						}, 8, ["required", "label"]), createVNode(_component_UFormField, {
							help: props.__.forms.users.help.username_optional,
							hint: props.__.forms.users.hint.optional,
							name: "username",
							class: "w-full",
							label: props.__.resources.users.fields.username
						}, {
							default: withCtx(() => [createVNode(_component_UInput, {
								modelValue: unref(inviteForm).username,
								"onUpdate:modelValue": ($event) => unref(inviteForm).username = $event,
								placeholder: " ",
								ui: { base: "peer" },
								required: "",
								class: "w-full"
							}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
							_: 1
						}, 8, [
							"help",
							"hint",
							"label"
						])]), createVNode("div", { class: "flex flex-col gap-4 justify-between" }, [createVNode(_component_UFormField, {
							label: props.__.resources.users.fields.forced_actions,
							hint: props.__.forms.users.hint.optional
						}, {
							default: withCtx(() => [createVNode(_component_UCheckboxGroup, {
								indicator: "start",
								variant: "table",
								modelValue: unref(inviteForm).forced_actions,
								"onUpdate:modelValue": ($event) => unref(inviteForm).forced_actions = $event,
								items: __props.availableForcedActions,
								"value-key": "value"
							}, {
								label: withCtx(({ item }) => [createTextVNode(toDisplayString(item.label.title), 1)]),
								description: withCtx(({ item }) => [createTextVNode(toDisplayString(item.label.description), 1)]),
								_: 1
							}, 8, [
								"modelValue",
								"onUpdate:modelValue",
								"items"
							])]),
							_: 1
						}, 8, ["label", "hint"]), createVNode(_component_UFormField, {
							required: "",
							label: "Roles",
							class: "w-full"
						}, {
							default: withCtx(() => [createVNode(_component_USelectMenu, {
								modelValue: unref(inviteForm).roles,
								"onUpdate:modelValue": ($event) => unref(inviteForm).roles = $event,
								"filter-fields": ["label", "project"],
								items: roles.value,
								loading: rolesLoading.value,
								multiple: "",
								class: "w-full"
							}, {
								"item-label": withCtx(({ item }) => [createTextVNode(toDisplayString(item.label) + " ", 1), createVNode("span", { class: "text-muted" }, "- " + toDisplayString(item.project), 1)]),
								_: 1
							}, 8, [
								"modelValue",
								"onUpdate:modelValue",
								"items",
								"loading"
							])]),
							_: 1
						})])]),
						_: 1
					}, 8, ["state"])];
				}),
				footer: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex justify-end gap-2 w-full"${_scopeId}>`);
						_push(ssrRenderComponent(unref(UButton), {
							color: "neutral",
							variant: "ghost",
							label: props.__.actions.close,
							type: "button",
							onClick: closeInviteModal,
							class: "cursor-pointer"
						}, null, _parent, _scopeId));
						_push(ssrRenderComponent(unref(UButton), {
							type: "submit",
							loading: unref(inviteForm).processing,
							label: props.__.actions.save,
							onClick: submitInviteForm,
							class: "cursor-pointer"
						}, null, _parent, _scopeId));
						_push(`</div>`);
					} else return [createVNode("div", { class: "flex justify-end gap-2 w-full" }, [createVNode(unref(UButton), {
						color: "neutral",
						variant: "ghost",
						label: props.__.actions.close,
						type: "button",
						onClick: closeInviteModal,
						class: "cursor-pointer"
					}, null, 8, ["label"]), createVNode(unref(UButton), {
						type: "submit",
						loading: unref(inviteForm).processing,
						label: props.__.actions.save,
						onClick: submitInviteForm,
						class: "cursor-pointer"
					}, null, 8, ["loading", "label"])])];
				}),
				_: 1
			}, _parent));
			_push(ssrRenderComponent(_component_UModal, {
				open: expirationModalOpen.value,
				"onUpdate:open": ($event) => expirationModalOpen.value = $event,
				class: "max-w-9/10 w-9/10 md:w-7/10 lg:w-6/10",
				title: props.__.forms.invitations.renew_expiration.title,
				description: props.__.forms.invitations.renew_expiration.description
			}, {
				body: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(ssrRenderComponent(_component_UForm, {
						state: unref(renewInvitationExpirationForm),
						class: "flex gap-4 justify-between"
					}, {
						default: withCtx((_, _push, _parent, _scopeId) => {
							if (_push) {
								_push(ssrRenderComponent(_component_UFormField, {
									name: "renew_amount",
									label: props.__.forms.invitations.fields.labels.renew_amount,
									help: props.__.forms.invitations.fields.helps.renew_amount,
									error: unref(renewInvitationExpirationForm).errors.renew_amount,
									class: "w-4/10 h-full"
								}, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) _push(ssrRenderComponent(_component_UInputNumber, {
											modelValue: unref(renewInvitationExpirationForm).renew_amount,
											"onUpdate:modelValue": ($event) => unref(renewInvitationExpirationForm).renew_amount = $event,
											min: 1,
											max: 30,
											"format-options": {
												style: "unit",
												unit: "day",
												unitDisplay: "long"
											}
										}, null, _parent, _scopeId));
										else return [createVNode(_component_UInputNumber, {
											modelValue: unref(renewInvitationExpirationForm).renew_amount,
											"onUpdate:modelValue": ($event) => unref(renewInvitationExpirationForm).renew_amount = $event,
											min: 1,
											max: 30,
											"format-options": {
												style: "unit",
												unit: "day",
												unitDisplay: "long"
											}
										}, null, 8, ["modelValue", "onUpdate:modelValue"])];
									}),
									_: 1
								}, _parent, _scopeId));
								_push(`<p class="text-muted text-sm w-1/10 justify-center flex items-center"${_scopeId}>OU</p>`);
								_push(ssrRenderComponent(_component_UFormField, {
									name: "expiration_date",
									label: props.__.forms.invitations.fields.labels.expiration_date,
									help: props.__.forms.invitations.fields.helps.expiration_date,
									error: unref(renewInvitationExpirationForm).errors.expiration_date,
									class: "w-4/10 flex flex-col justify-stretch items-end"
								}, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) _push(ssrRenderComponent(FgInputDatePicker_default, {
											__: props.__.global.forms,
											modelValue: unref(renewInvitationExpirationForm).expiration_date,
											"onUpdate:modelValue": ($event) => unref(renewInvitationExpirationForm).expiration_date = $event
										}, null, _parent, _scopeId));
										else return [createVNode(FgInputDatePicker_default, {
											__: props.__.global.forms,
											modelValue: unref(renewInvitationExpirationForm).expiration_date,
											"onUpdate:modelValue": ($event) => unref(renewInvitationExpirationForm).expiration_date = $event
										}, null, 8, [
											"__",
											"modelValue",
											"onUpdate:modelValue"
										])];
									}),
									_: 1
								}, _parent, _scopeId));
							} else return [
								createVNode(_component_UFormField, {
									name: "renew_amount",
									label: props.__.forms.invitations.fields.labels.renew_amount,
									help: props.__.forms.invitations.fields.helps.renew_amount,
									error: unref(renewInvitationExpirationForm).errors.renew_amount,
									class: "w-4/10 h-full"
								}, {
									default: withCtx(() => [createVNode(_component_UInputNumber, {
										modelValue: unref(renewInvitationExpirationForm).renew_amount,
										"onUpdate:modelValue": ($event) => unref(renewInvitationExpirationForm).renew_amount = $event,
										min: 1,
										max: 30,
										"format-options": {
											style: "unit",
											unit: "day",
											unitDisplay: "long"
										}
									}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"help",
									"error"
								]),
								createVNode("p", { class: "text-muted text-sm w-1/10 justify-center flex items-center" }, "OU"),
								createVNode(_component_UFormField, {
									name: "expiration_date",
									label: props.__.forms.invitations.fields.labels.expiration_date,
									help: props.__.forms.invitations.fields.helps.expiration_date,
									error: unref(renewInvitationExpirationForm).errors.expiration_date,
									class: "w-4/10 flex flex-col justify-stretch items-end"
								}, {
									default: withCtx(() => [createVNode(FgInputDatePicker_default, {
										__: props.__.global.forms,
										modelValue: unref(renewInvitationExpirationForm).expiration_date,
										"onUpdate:modelValue": ($event) => unref(renewInvitationExpirationForm).expiration_date = $event
									}, null, 8, [
										"__",
										"modelValue",
										"onUpdate:modelValue"
									])]),
									_: 1
								}, 8, [
									"label",
									"help",
									"error"
								])
							];
						}),
						_: 1
					}, _parent, _scopeId));
					else return [createVNode(_component_UForm, {
						state: unref(renewInvitationExpirationForm),
						class: "flex gap-4 justify-between"
					}, {
						default: withCtx(() => [
							createVNode(_component_UFormField, {
								name: "renew_amount",
								label: props.__.forms.invitations.fields.labels.renew_amount,
								help: props.__.forms.invitations.fields.helps.renew_amount,
								error: unref(renewInvitationExpirationForm).errors.renew_amount,
								class: "w-4/10 h-full"
							}, {
								default: withCtx(() => [createVNode(_component_UInputNumber, {
									modelValue: unref(renewInvitationExpirationForm).renew_amount,
									"onUpdate:modelValue": ($event) => unref(renewInvitationExpirationForm).renew_amount = $event,
									min: 1,
									max: 30,
									"format-options": {
										style: "unit",
										unit: "day",
										unitDisplay: "long"
									}
								}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
								_: 1
							}, 8, [
								"label",
								"help",
								"error"
							]),
							createVNode("p", { class: "text-muted text-sm w-1/10 justify-center flex items-center" }, "OU"),
							createVNode(_component_UFormField, {
								name: "expiration_date",
								label: props.__.forms.invitations.fields.labels.expiration_date,
								help: props.__.forms.invitations.fields.helps.expiration_date,
								error: unref(renewInvitationExpirationForm).errors.expiration_date,
								class: "w-4/10 flex flex-col justify-stretch items-end"
							}, {
								default: withCtx(() => [createVNode(FgInputDatePicker_default, {
									__: props.__.global.forms,
									modelValue: unref(renewInvitationExpirationForm).expiration_date,
									"onUpdate:modelValue": ($event) => unref(renewInvitationExpirationForm).expiration_date = $event
								}, null, 8, [
									"__",
									"modelValue",
									"onUpdate:modelValue"
								])]),
								_: 1
							}, 8, [
								"label",
								"help",
								"error"
							])
						]),
						_: 1
					}, 8, ["state"])];
				}),
				footer: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex justify-end gap-2 w-full"${_scopeId}>`);
						_push(ssrRenderComponent(unref(UButton), {
							color: "neutral",
							variant: "ghost",
							label: props.__.actions.close,
							type: "button",
							onClick: closeRenewModal,
							class: "cursor-pointer"
						}, null, _parent, _scopeId));
						_push(ssrRenderComponent(unref(UButton), {
							type: "submit",
							loading: unref(renewInvitationExpirationForm).processing,
							label: props.__.forms.invitations.renew_expiration.submit,
							onClick: submitRenewExpirationForm,
							class: "cursor-pointer"
						}, null, _parent, _scopeId));
						_push(`</div>`);
					} else return [createVNode("div", { class: "flex justify-end gap-2 w-full" }, [createVNode(unref(UButton), {
						color: "neutral",
						variant: "ghost",
						label: props.__.actions.close,
						type: "button",
						onClick: closeRenewModal,
						class: "cursor-pointer"
					}, null, 8, ["label"]), createVNode(unref(UButton), {
						type: "submit",
						loading: unref(renewInvitationExpirationForm).processing,
						label: props.__.forms.invitations.renew_expiration.submit,
						onClick: submitRenewExpirationForm,
						class: "cursor-pointer"
					}, null, 8, ["loading", "label"])])];
				}),
				_: 1
			}, _parent));
			_push(ssrRenderComponent(_component_UModal, {
				open: rolesModalOpen.value,
				"onUpdate:open": ($event) => rolesModalOpen.value = $event
			}, {
				header: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(`<div${_scopeId}><h2 class="text-lg font-semibold"${_scopeId}> Rôles de ${ssrInterpolate(rolesModalUser.value?.username)}</h2><p class="text-sm text-muted"${_scopeId}>${ssrInterpolate(rolesModalUser.value?.roles?.length ?? 0)} rôle(s) assigné(s) </p></div>`);
					else return [createVNode("div", null, [createVNode("h2", { class: "text-lg font-semibold" }, " Rôles de " + toDisplayString(rolesModalUser.value?.username), 1), createVNode("p", { class: "text-sm text-muted" }, toDisplayString(rolesModalUser.value?.roles?.length ?? 0) + " rôle(s) assigné(s) ", 1)])];
				}),
				body: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) if (rolesModalUser.value?.roles?.length) {
						_push(`<div class="flex flex-col gap-2"${_scopeId}><!--[-->`);
						ssrRenderList(rolesModalUser.value.roles, (role) => {
							_push(`<div class="flex items-center justify-between rounded-md border border-default px-3 py-2"${_scopeId}><div class="min-w-0"${_scopeId}><p class="font-medium truncate"${_scopeId}>${ssrInterpolate(role.name)}</p>`);
							if (role.project?.name) _push(`<p class="text-sm text-muted truncate"${_scopeId}>${ssrInterpolate(role.project.name)}</p>`);
							else _push(`<!---->`);
							_push(`</div>`);
							_push(ssrRenderComponent(_component_UBadge, {
								color: "neutral",
								variant: "soft"
							}, {
								default: withCtx((_, _push, _parent, _scopeId) => {
									if (_push) _push(` Rôle `);
									else return [createTextVNode(" Rôle ")];
								}),
								_: 2
							}, _parent, _scopeId));
							_push(`</div>`);
						});
						_push(`<!--]--></div>`);
					} else _push(ssrRenderComponent(_component_UAlert, {
						color: "neutral",
						variant: "soft",
						icon: "i-lucide-shield",
						title: "Aucun rôle assigné"
					}, null, _parent, _scopeId));
					else return [rolesModalUser.value?.roles?.length ? (openBlock(), createBlock("div", {
						key: 0,
						class: "flex flex-col gap-2"
					}, [(openBlock(true), createBlock(Fragment, null, renderList(rolesModalUser.value.roles, (role) => {
						return openBlock(), createBlock("div", {
							key: role.id,
							class: "flex items-center justify-between rounded-md border border-default px-3 py-2"
						}, [createVNode("div", { class: "min-w-0" }, [createVNode("p", { class: "font-medium truncate" }, toDisplayString(role.name), 1), role.project?.name ? (openBlock(), createBlock("p", {
							key: 0,
							class: "text-sm text-muted truncate"
						}, toDisplayString(role.project.name), 1)) : createCommentVNode("", true)]), createVNode(_component_UBadge, {
							color: "neutral",
							variant: "soft"
						}, {
							default: withCtx(() => [createTextVNode(" Rôle ")]),
							_: 1
						})]);
					}), 128))])) : (openBlock(), createBlock(_component_UAlert, {
						key: 1,
						color: "neutral",
						variant: "soft",
						icon: "i-lucide-shield",
						title: "Aucun rôle assigné"
					}))];
				}),
				footer: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex justify-end w-full"${_scopeId}>`);
						_push(ssrRenderComponent(unref(UButton), {
							color: "neutral",
							variant: "ghost",
							label: "Fermer",
							onClick: ($event) => rolesModalOpen.value = false
						}, null, _parent, _scopeId));
						_push(`</div>`);
					} else return [createVNode("div", { class: "flex justify-end w-full" }, [createVNode(unref(UButton), {
						color: "neutral",
						variant: "ghost",
						label: "Fermer",
						onClick: ($event) => rolesModalOpen.value = false
					}, null, 8, ["onClick"])])];
				}),
				_: 1
			}, _parent));
			_push(ssrRenderComponent(FgConfirmBulkActionModal_default, {
				open: bulkActionModalOpen.value,
				"onUpdate:open": ($event) => bulkActionModalOpen.value = $event,
				methods: unref(page).props.auth?.user?.two_factor_methods ?? [],
				"selected-method": unref(page).props.auth?.user?.primary_second_factor ?? null,
				"masked-email": unref(page).props.auth?.user?.masked_email ?? "",
				"identity-config": props.__.global.confirm_identity,
				"bulk-config": bulkActionConfig.value,
				onConfirmed: confirmBulkAction
			}, null, _parent));
			_push(ssrRenderComponent(_component_UModal, {
				scrollable: "",
				open: userModalOpen.value,
				"onUpdate:open": [($event) => userModalOpen.value = $event, handleUserModalOpenChange],
				class: "w-7/10 max-w-235 max-h-[calc(100vh-8rem)] overflow-y-scroll relative"
			}, {
				header: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(`<h2 class="text-lg font-semibold"${_scopeId}>${ssrInterpolate(userModalTitle.value)}</h2>`);
					else return [createVNode("h2", { class: "text-lg font-semibold" }, toDisplayString(userModalTitle.value), 1)];
				}),
				body: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(ssrRenderComponent(_component_UTabs, {
						modelValue: activeUserModalTab.value,
						"onUpdate:modelValue": ($event) => activeUserModalTab.value = $event,
						items: tabsItems.value,
						ui: {
							trigger: "cursor-pointer",
							label: "overflow-visible"
						}
					}, {
						default: withCtx(({ item }, _push, _parent, _scopeId) => {
							if (_push) _push(ssrRenderComponent(_component_UChip, {
								color: "warning",
								show: item.dirty,
								ui: { base: "ring-0 border-1" }
							}, {
								default: withCtx((_, _push, _parent, _scopeId) => {
									if (_push) _push(`<span class="px-1"${_scopeId}>${ssrInterpolate(item.label)}</span>`);
									else return [createVNode("span", { class: "px-1" }, toDisplayString(item.label), 1)];
								}),
								_: 2
							}, _parent, _scopeId));
							else return [createVNode(_component_UChip, {
								color: "warning",
								show: item.dirty,
								ui: { base: "ring-0 border-1" }
							}, {
								default: withCtx(() => [createVNode("span", { class: "px-1" }, toDisplayString(item.label), 1)]),
								_: 2
							}, 1032, ["show"])];
						}),
						data: withCtx(({ item }, _push, _parent, _scopeId) => {
							if (_push) {
								_push(`<p class="text-muted"${_scopeId}>${ssrInterpolate(item.description)}</p><hr class="text-white/30 mt-2"${_scopeId}>`);
								_push(ssrRenderComponent(_component_UForm, {
									state: unref(userDataForm),
									class: "flex flex-col gap-4 mt-5",
									onSubmit: submitUserModal
								}, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) _push(ssrRenderComponent(_component_UAccordion, {
											modelValue: userDataAccordionActive.value,
											"onUpdate:modelValue": ($event) => userDataAccordionActive.value = $event,
											items: userDataAccordionItems,
											ui: {
												trigger: "cursor-pointer rounded-md hover:bg-white/10 px-4 text-white data-[state=open]:text-primary-light data-[state=open]:bg-white/5",
												content: "py-4"
											}
										}, {
											default: withCtx(({ item }, _push, _parent, _scopeId) => {
												if (_push) _push(`<h3 class="text-lg"${_scopeId}>${ssrInterpolate(item.label)}</h3>`);
												else return [createVNode("h3", { class: "text-lg" }, toDisplayString(item.label), 1)];
											}),
											login: withCtx(({ item }, _push, _parent, _scopeId) => {
												if (_push) {
													_push(`<div class="flex gap-4 justify-between"${_scopeId}>`);
													_push(ssrRenderComponent(_component_UFormField, {
														required: "",
														help: props.__.forms.users.help.username,
														name: "username",
														class: "w-full"
													}, {
														label: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) {
																_push(`<span class="inline-flex items-center gap-1"${_scopeId}><span${_scopeId}>${ssrInterpolate(props.__.resources.users.fields.username)}</span>`);
																if (unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "username")) _push(`<span class="inline-block size-1.5 shrink-0 rounded-full bg-warning" title="Modification non sauvegardée"${_scopeId}></span>`);
																else _push(`<!---->`);
																_push(`</span>`);
															} else return [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.username), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "username") ? (openBlock(), createBlock("span", {
																key: 0,
																class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
																title: "Modification non sauvegardée"
															})) : createCommentVNode("", true)])];
														}),
														default: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) _push(ssrRenderComponent(_component_UInput, {
																modelValue: unref(userDataForm).username,
																"onUpdate:modelValue": ($event) => unref(userDataForm).username = $event,
																placeholder: " ",
																ui: { base: "peer" },
																required: "",
																class: "w-full",
																size: "lg"
															}, null, _parent, _scopeId));
															else return [createVNode(_component_UInput, {
																modelValue: unref(userDataForm).username,
																"onUpdate:modelValue": ($event) => unref(userDataForm).username = $event,
																placeholder: " ",
																ui: { base: "peer" },
																required: "",
																class: "w-full",
																size: "lg"
															}, null, 8, ["modelValue", "onUpdate:modelValue"])];
														}),
														_: 2
													}, _parent, _scopeId));
													_push(ssrRenderComponent(_component_UFormField, {
														required: "",
														class: "w-full"
													}, {
														label: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) {
																_push(`<span class="inline-flex items-center gap-1"${_scopeId}><span${_scopeId}>${ssrInterpolate(props.__.resources.users.fields.email)}</span>`);
																if (unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "email")) _push(`<span class="inline-block size-1.5 shrink-0 rounded-full bg-warning" title="Modification non sauvegardée"${_scopeId}></span>`);
																else _push(`<!---->`);
																_push(`</span>`);
															} else return [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.email), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "email") ? (openBlock(), createBlock("span", {
																key: 0,
																class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
																title: "Modification non sauvegardée"
															})) : createCommentVNode("", true)])];
														}),
														default: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) _push(ssrRenderComponent(_component_UInput, {
																modelValue: unref(userDataForm).email,
																"onUpdate:modelValue": ($event) => unref(userDataForm).email = $event,
																placeholder: " ",
																ui: { base: "peer" },
																required: "",
																class: "w-full",
																size: "lg"
															}, null, _parent, _scopeId));
															else return [createVNode(_component_UInput, {
																modelValue: unref(userDataForm).email,
																"onUpdate:modelValue": ($event) => unref(userDataForm).email = $event,
																placeholder: " ",
																ui: { base: "peer" },
																required: "",
																class: "w-full",
																size: "lg"
															}, null, 8, ["modelValue", "onUpdate:modelValue"])];
														}),
														_: 2
													}, _parent, _scopeId));
													_push(`</div>`);
												} else return [createVNode("div", { class: "flex gap-4 justify-between" }, [createVNode(_component_UFormField, {
													required: "",
													help: props.__.forms.users.help.username,
													name: "username",
													class: "w-full"
												}, {
													label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.username), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "username") ? (openBlock(), createBlock("span", {
														key: 0,
														class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
														title: "Modification non sauvegardée"
													})) : createCommentVNode("", true)])]),
													default: withCtx(() => [createVNode(_component_UInput, {
														modelValue: unref(userDataForm).username,
														"onUpdate:modelValue": ($event) => unref(userDataForm).username = $event,
														placeholder: " ",
														ui: { base: "peer" },
														required: "",
														class: "w-full",
														size: "lg"
													}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
													_: 1
												}, 8, ["help"]), createVNode(_component_UFormField, {
													required: "",
													class: "w-full"
												}, {
													label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.email), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "email") ? (openBlock(), createBlock("span", {
														key: 0,
														class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
														title: "Modification non sauvegardée"
													})) : createCommentVNode("", true)])]),
													default: withCtx(() => [createVNode(_component_UInput, {
														modelValue: unref(userDataForm).email,
														"onUpdate:modelValue": ($event) => unref(userDataForm).email = $event,
														placeholder: " ",
														ui: { base: "peer" },
														required: "",
														class: "w-full",
														size: "lg"
													}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
													_: 1
												})])];
											}),
											"personal-data": withCtx(({ item }, _push, _parent, _scopeId) => {
												if (_push) {
													_push(`<div class="grid grid-cols-2 gap-4 py-2"${_scopeId}>`);
													_push(ssrRenderComponent(_component_UFormField, null, {
														label: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) {
																_push(`<span class="inline-flex items-center gap-1"${_scopeId}><span${_scopeId}>${ssrInterpolate(props.__.resources.users.fields.last_name)}</span>`);
																if (unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "last_name")) _push(`<span class="inline-block size-1.5 shrink-0 rounded-full bg-warning" title="Modification non sauvegardée"${_scopeId}></span>`);
																else _push(`<!---->`);
																_push(`</span>`);
															} else return [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.last_name), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "last_name") ? (openBlock(), createBlock("span", {
																key: 0,
																class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
																title: "Modification non sauvegardée"
															})) : createCommentVNode("", true)])];
														}),
														default: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) _push(ssrRenderComponent(_component_UInput, {
																modelValue: unref(userDataForm).last_name,
																"onUpdate:modelValue": ($event) => unref(userDataForm).last_name = $event,
																placeholder: " ",
																ui: { base: "peer" },
																class: "w-full"
															}, null, _parent, _scopeId));
															else return [createVNode(_component_UInput, {
																modelValue: unref(userDataForm).last_name,
																"onUpdate:modelValue": ($event) => unref(userDataForm).last_name = $event,
																placeholder: " ",
																ui: { base: "peer" },
																class: "w-full"
															}, null, 8, ["modelValue", "onUpdate:modelValue"])];
														}),
														_: 2
													}, _parent, _scopeId));
													_push(ssrRenderComponent(_component_UFormField, null, {
														label: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) {
																_push(`<span class="inline-flex items-center gap-1"${_scopeId}><span${_scopeId}>${ssrInterpolate(props.__.resources.users.fields.first_name)}</span>`);
																if (unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "first_name")) _push(`<span class="inline-block size-1.5 shrink-0 rounded-full bg-warning" title="Modification non sauvegardée"${_scopeId}></span>`);
																else _push(`<!---->`);
																_push(`</span>`);
															} else return [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.first_name), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "first_name") ? (openBlock(), createBlock("span", {
																key: 0,
																class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
																title: "Modification non sauvegardée"
															})) : createCommentVNode("", true)])];
														}),
														default: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) _push(ssrRenderComponent(_component_UInput, {
																modelValue: unref(userDataForm).first_name,
																"onUpdate:modelValue": ($event) => unref(userDataForm).first_name = $event,
																placeholder: " ",
																ui: { base: "peer" },
																class: "w-full"
															}, null, _parent, _scopeId));
															else return [createVNode(_component_UInput, {
																modelValue: unref(userDataForm).first_name,
																"onUpdate:modelValue": ($event) => unref(userDataForm).first_name = $event,
																placeholder: " ",
																ui: { base: "peer" },
																class: "w-full"
															}, null, 8, ["modelValue", "onUpdate:modelValue"])];
														}),
														_: 2
													}, _parent, _scopeId));
													_push(`</div><div class="grid grid-cols-2 gap-4"${_scopeId}>`);
													_push(ssrRenderComponent(_component_UFormField, null, {
														label: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) {
																_push(`<span class="inline-flex items-center gap-1"${_scopeId}><span${_scopeId}>${ssrInterpolate(props.__.resources.users.fields.job_title)}</span>`);
																if (unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "job_title")) _push(`<span class="inline-block size-1.5 shrink-0 rounded-full bg-warning" title="Modification non sauvegardée"${_scopeId}></span>`);
																else _push(`<!---->`);
																_push(`</span>`);
															} else return [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.job_title), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "job_title") ? (openBlock(), createBlock("span", {
																key: 0,
																class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
																title: "Modification non sauvegardée"
															})) : createCommentVNode("", true)])];
														}),
														default: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) _push(ssrRenderComponent(_component_UInput, {
																modelValue: unref(userDataForm).job_title,
																"onUpdate:modelValue": ($event) => unref(userDataForm).job_title = $event,
																label: "Poste",
																placeholder: " ",
																ui: { base: "peer" },
																class: "w-full"
															}, null, _parent, _scopeId));
															else return [createVNode(_component_UInput, {
																modelValue: unref(userDataForm).job_title,
																"onUpdate:modelValue": ($event) => unref(userDataForm).job_title = $event,
																label: "Poste",
																placeholder: " ",
																ui: { base: "peer" },
																class: "w-full"
															}, null, 8, ["modelValue", "onUpdate:modelValue"])];
														}),
														_: 2
													}, _parent, _scopeId));
													_push(ssrRenderComponent(_component_UFormField, { class: "w-full" }, {
														label: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) {
																_push(`<span class="inline-flex items-center gap-1"${_scopeId}><span${_scopeId}>${ssrInterpolate(props.__.resources.users.fields.phone.label)}</span>`);
																if (unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "phone")) _push(`<span class="inline-block size-1.5 shrink-0 rounded-full bg-warning" title="Modification non sauvegardée"${_scopeId}></span>`);
																else _push(`<!---->`);
																_push(`</span>`);
															} else return [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.phone.label), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "phone") ? (openBlock(), createBlock("span", {
																key: 0,
																class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
																title: "Modification non sauvegardée"
															})) : createCommentVNode("", true)])];
														}),
														default: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) _push(ssrRenderComponent(_component_UFieldGroup, { class: "w-full" }, {
																default: withCtx((_, _push, _parent, _scopeId) => {
																	if (_push) {
																		_push(ssrRenderComponent(_component_UPopover, {
																			open: countryOpen.value,
																			"onUpdate:open": ($event) => countryOpen.value = $event
																		}, {
																			content: withCtx((_, _push, _parent, _scopeId) => {
																				if (_push) {
																					_push(`<div class="w-80 space-y-2 p-2"${_scopeId}>`);
																					_push(ssrRenderComponent(_component_UInput, {
																						size: _ctx.size,
																						modelValue: countrySearch.value,
																						"onUpdate:modelValue": ($event) => countrySearch.value = $event,
																						placeholder: "Rechercher un pays ou indicatif...",
																						icon: "i-lucide-search",
																						autofocus: "",
																						class: "w-full"
																					}, null, _parent, _scopeId));
																					_push(`<div class="max-h-72 overflow-y-auto"${_scopeId}><!--[-->`);
																					ssrRenderList(filteredCountries.value, (country) => {
																						_push(`<button type="button" class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm hover:bg-white/10"${_scopeId}><span class="text-lg"${_scopeId}>${ssrInterpolate(country.flag)}</span><span class="min-w-0 flex-1"${_scopeId}><span class="block truncate text-white"${_scopeId}>${ssrInterpolate(country.name)}</span><span class="text-xs text-white/50"${_scopeId}> +${ssrInterpolate(country.extension)}</span></span></button>`);
																					});
																					_push(`<!--]-->`);
																					if (filteredCountries.value.length === 0) _push(`<p class="px-3 py-4 text-center text-sm text-white/50"${_scopeId}> Aucun résultat </p>`);
																					else _push(`<!---->`);
																					_push(`</div></div>`);
																				} else return [createVNode("div", { class: "w-80 space-y-2 p-2" }, [createVNode(_component_UInput, {
																					size: _ctx.size,
																					modelValue: countrySearch.value,
																					"onUpdate:modelValue": ($event) => countrySearch.value = $event,
																					placeholder: "Rechercher un pays ou indicatif...",
																					icon: "i-lucide-search",
																					autofocus: "",
																					class: "w-full"
																				}, null, 8, [
																					"size",
																					"modelValue",
																					"onUpdate:modelValue"
																				]), createVNode("div", { class: "max-h-72 overflow-y-auto" }, [(openBlock(true), createBlock(Fragment, null, renderList(filteredCountries.value, (country) => {
																					return openBlock(), createBlock("button", {
																						key: `${country.name}-${country.extension}`,
																						type: "button",
																						class: "flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm hover:bg-white/10",
																						onClick: ($event) => selectCountry(country)
																					}, [createVNode("span", { class: "text-lg" }, toDisplayString(country.flag), 1), createVNode("span", { class: "min-w-0 flex-1" }, [createVNode("span", { class: "block truncate text-white" }, toDisplayString(country.name), 1), createVNode("span", { class: "text-xs text-white/50" }, " +" + toDisplayString(country.extension), 1)])], 8, ["onClick"]);
																				}), 128)), filteredCountries.value.length === 0 ? (openBlock(), createBlock("p", {
																					key: 0,
																					class: "px-3 py-4 text-center text-sm text-white/50"
																				}, " Aucun résultat ")) : createCommentVNode("", true)])])];
																			}),
																			default: withCtx((_, _push, _parent, _scopeId) => {
																				if (_push) _push(ssrRenderComponent(unref(UButton), {
																					type: "button",
																					color: "neutral",
																					variant: "outline",
																					class: "h-full w-1/4 justify-between rounded-none border-0 bg-transparent px-3 rounded-l-md",
																					"trailing-icon": "i-lucide-chevron-down"
																				}, {
																					default: withCtx((_, _push, _parent, _scopeId) => {
																						if (_push) {
																							_push(`<span class="flex items-center gap-2"${_scopeId}>`);
																							if (selectedCountry.value) _push(`<span${_scopeId}>${ssrInterpolate(selectedCountry.value.flag)}</span>`);
																							else _push(`<!---->`);
																							_push(`<span${_scopeId}>${ssrInterpolate(unref(userDataForm).phone.extension ? `+${unref(userDataForm).phone.extension}` : "+...")}</span></span>`);
																						} else return [createVNode("span", { class: "flex items-center gap-2" }, [selectedCountry.value ? (openBlock(), createBlock("span", { key: 0 }, toDisplayString(selectedCountry.value.flag), 1)) : createCommentVNode("", true), createVNode("span", null, toDisplayString(unref(userDataForm).phone.extension ? `+${unref(userDataForm).phone.extension}` : "+..."), 1)])];
																					}),
																					_: 2
																				}, _parent, _scopeId));
																				else return [createVNode(unref(UButton), {
																					type: "button",
																					color: "neutral",
																					variant: "outline",
																					class: "h-full w-1/4 justify-between rounded-none border-0 bg-transparent px-3 rounded-l-md",
																					"trailing-icon": "i-lucide-chevron-down"
																				}, {
																					default: withCtx(() => [createVNode("span", { class: "flex items-center gap-2" }, [selectedCountry.value ? (openBlock(), createBlock("span", { key: 0 }, toDisplayString(selectedCountry.value.flag), 1)) : createCommentVNode("", true), createVNode("span", null, toDisplayString(unref(userDataForm).phone.extension ? `+${unref(userDataForm).phone.extension}` : "+..."), 1)])]),
																					_: 1
																				})];
																			}),
																			_: 2
																		}, _parent, _scopeId));
																		_push(`<div class="w-px bg-white/10"${_scopeId}></div>`);
																		_push(ssrRenderComponent(_component_UInput, mergeProps({
																			"model-value": unref(userDataForm).phone.number,
																			variant: "outline",
																			type: "tel",
																			inputmode: "tel",
																			class: "min-w-0 flex-1 h-full",
																			placeholder: props.__.resources.users.fields.phone.label,
																			ui: { base: "rounded-none border-0 bg-transparent h-full rounded-r-md" },
																			"onUpdate:modelValue": updateNumber
																		}, ssrGetDirectiveProps(_ctx, unref(vMaska), "##########")), null, _parent, _scopeId));
																	} else return [
																		createVNode(_component_UPopover, {
																			open: countryOpen.value,
																			"onUpdate:open": ($event) => countryOpen.value = $event
																		}, {
																			content: withCtx(() => [createVNode("div", { class: "w-80 space-y-2 p-2" }, [createVNode(_component_UInput, {
																				size: _ctx.size,
																				modelValue: countrySearch.value,
																				"onUpdate:modelValue": ($event) => countrySearch.value = $event,
																				placeholder: "Rechercher un pays ou indicatif...",
																				icon: "i-lucide-search",
																				autofocus: "",
																				class: "w-full"
																			}, null, 8, [
																				"size",
																				"modelValue",
																				"onUpdate:modelValue"
																			]), createVNode("div", { class: "max-h-72 overflow-y-auto" }, [(openBlock(true), createBlock(Fragment, null, renderList(filteredCountries.value, (country) => {
																				return openBlock(), createBlock("button", {
																					key: `${country.name}-${country.extension}`,
																					type: "button",
																					class: "flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm hover:bg-white/10",
																					onClick: ($event) => selectCountry(country)
																				}, [createVNode("span", { class: "text-lg" }, toDisplayString(country.flag), 1), createVNode("span", { class: "min-w-0 flex-1" }, [createVNode("span", { class: "block truncate text-white" }, toDisplayString(country.name), 1), createVNode("span", { class: "text-xs text-white/50" }, " +" + toDisplayString(country.extension), 1)])], 8, ["onClick"]);
																			}), 128)), filteredCountries.value.length === 0 ? (openBlock(), createBlock("p", {
																				key: 0,
																				class: "px-3 py-4 text-center text-sm text-white/50"
																			}, " Aucun résultat ")) : createCommentVNode("", true)])])]),
																			default: withCtx(() => [createVNode(unref(UButton), {
																				type: "button",
																				color: "neutral",
																				variant: "outline",
																				class: "h-full w-1/4 justify-between rounded-none border-0 bg-transparent px-3 rounded-l-md",
																				"trailing-icon": "i-lucide-chevron-down"
																			}, {
																				default: withCtx(() => [createVNode("span", { class: "flex items-center gap-2" }, [selectedCountry.value ? (openBlock(), createBlock("span", { key: 0 }, toDisplayString(selectedCountry.value.flag), 1)) : createCommentVNode("", true), createVNode("span", null, toDisplayString(unref(userDataForm).phone.extension ? `+${unref(userDataForm).phone.extension}` : "+..."), 1)])]),
																				_: 1
																			})]),
																			_: 1
																		}, 8, ["open", "onUpdate:open"]),
																		createVNode("div", { class: "w-px bg-white/10" }),
																		withDirectives(createVNode(_component_UInput, {
																			"model-value": unref(userDataForm).phone.number,
																			variant: "outline",
																			type: "tel",
																			inputmode: "tel",
																			class: "min-w-0 flex-1 h-full",
																			placeholder: props.__.resources.users.fields.phone.label,
																			ui: { base: "rounded-none border-0 bg-transparent h-full rounded-r-md" },
																			"onUpdate:modelValue": updateNumber
																		}, null, 8, ["model-value", "placeholder"]), [[unref(vMaska), "##########"]])
																	];
																}),
																_: 2
															}, _parent, _scopeId));
															else return [createVNode(_component_UFieldGroup, { class: "w-full" }, {
																default: withCtx(() => [
																	createVNode(_component_UPopover, {
																		open: countryOpen.value,
																		"onUpdate:open": ($event) => countryOpen.value = $event
																	}, {
																		content: withCtx(() => [createVNode("div", { class: "w-80 space-y-2 p-2" }, [createVNode(_component_UInput, {
																			size: _ctx.size,
																			modelValue: countrySearch.value,
																			"onUpdate:modelValue": ($event) => countrySearch.value = $event,
																			placeholder: "Rechercher un pays ou indicatif...",
																			icon: "i-lucide-search",
																			autofocus: "",
																			class: "w-full"
																		}, null, 8, [
																			"size",
																			"modelValue",
																			"onUpdate:modelValue"
																		]), createVNode("div", { class: "max-h-72 overflow-y-auto" }, [(openBlock(true), createBlock(Fragment, null, renderList(filteredCountries.value, (country) => {
																			return openBlock(), createBlock("button", {
																				key: `${country.name}-${country.extension}`,
																				type: "button",
																				class: "flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm hover:bg-white/10",
																				onClick: ($event) => selectCountry(country)
																			}, [createVNode("span", { class: "text-lg" }, toDisplayString(country.flag), 1), createVNode("span", { class: "min-w-0 flex-1" }, [createVNode("span", { class: "block truncate text-white" }, toDisplayString(country.name), 1), createVNode("span", { class: "text-xs text-white/50" }, " +" + toDisplayString(country.extension), 1)])], 8, ["onClick"]);
																		}), 128)), filteredCountries.value.length === 0 ? (openBlock(), createBlock("p", {
																			key: 0,
																			class: "px-3 py-4 text-center text-sm text-white/50"
																		}, " Aucun résultat ")) : createCommentVNode("", true)])])]),
																		default: withCtx(() => [createVNode(unref(UButton), {
																			type: "button",
																			color: "neutral",
																			variant: "outline",
																			class: "h-full w-1/4 justify-between rounded-none border-0 bg-transparent px-3 rounded-l-md",
																			"trailing-icon": "i-lucide-chevron-down"
																		}, {
																			default: withCtx(() => [createVNode("span", { class: "flex items-center gap-2" }, [selectedCountry.value ? (openBlock(), createBlock("span", { key: 0 }, toDisplayString(selectedCountry.value.flag), 1)) : createCommentVNode("", true), createVNode("span", null, toDisplayString(unref(userDataForm).phone.extension ? `+${unref(userDataForm).phone.extension}` : "+..."), 1)])]),
																			_: 1
																		})]),
																		_: 1
																	}, 8, ["open", "onUpdate:open"]),
																	createVNode("div", { class: "w-px bg-white/10" }),
																	withDirectives(createVNode(_component_UInput, {
																		"model-value": unref(userDataForm).phone.number,
																		variant: "outline",
																		type: "tel",
																		inputmode: "tel",
																		class: "min-w-0 flex-1 h-full",
																		placeholder: props.__.resources.users.fields.phone.label,
																		ui: { base: "rounded-none border-0 bg-transparent h-full rounded-r-md" },
																		"onUpdate:modelValue": updateNumber
																	}, null, 8, ["model-value", "placeholder"]), [[unref(vMaska), "##########"]])
																]),
																_: 1
															})];
														}),
														_: 2
													}, _parent, _scopeId));
													_push(`</div>`);
												} else return [createVNode("div", { class: "grid grid-cols-2 gap-4 py-2" }, [createVNode(_component_UFormField, null, {
													label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.last_name), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "last_name") ? (openBlock(), createBlock("span", {
														key: 0,
														class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
														title: "Modification non sauvegardée"
													})) : createCommentVNode("", true)])]),
													default: withCtx(() => [createVNode(_component_UInput, {
														modelValue: unref(userDataForm).last_name,
														"onUpdate:modelValue": ($event) => unref(userDataForm).last_name = $event,
														placeholder: " ",
														ui: { base: "peer" },
														class: "w-full"
													}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
													_: 1
												}), createVNode(_component_UFormField, null, {
													label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.first_name), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "first_name") ? (openBlock(), createBlock("span", {
														key: 0,
														class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
														title: "Modification non sauvegardée"
													})) : createCommentVNode("", true)])]),
													default: withCtx(() => [createVNode(_component_UInput, {
														modelValue: unref(userDataForm).first_name,
														"onUpdate:modelValue": ($event) => unref(userDataForm).first_name = $event,
														placeholder: " ",
														ui: { base: "peer" },
														class: "w-full"
													}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
													_: 1
												})]), createVNode("div", { class: "grid grid-cols-2 gap-4" }, [createVNode(_component_UFormField, null, {
													label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.job_title), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "job_title") ? (openBlock(), createBlock("span", {
														key: 0,
														class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
														title: "Modification non sauvegardée"
													})) : createCommentVNode("", true)])]),
													default: withCtx(() => [createVNode(_component_UInput, {
														modelValue: unref(userDataForm).job_title,
														"onUpdate:modelValue": ($event) => unref(userDataForm).job_title = $event,
														label: "Poste",
														placeholder: " ",
														ui: { base: "peer" },
														class: "w-full"
													}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
													_: 1
												}), createVNode(_component_UFormField, { class: "w-full" }, {
													label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.phone.label), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "phone") ? (openBlock(), createBlock("span", {
														key: 0,
														class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
														title: "Modification non sauvegardée"
													})) : createCommentVNode("", true)])]),
													default: withCtx(() => [createVNode(_component_UFieldGroup, { class: "w-full" }, {
														default: withCtx(() => [
															createVNode(_component_UPopover, {
																open: countryOpen.value,
																"onUpdate:open": ($event) => countryOpen.value = $event
															}, {
																content: withCtx(() => [createVNode("div", { class: "w-80 space-y-2 p-2" }, [createVNode(_component_UInput, {
																	size: _ctx.size,
																	modelValue: countrySearch.value,
																	"onUpdate:modelValue": ($event) => countrySearch.value = $event,
																	placeholder: "Rechercher un pays ou indicatif...",
																	icon: "i-lucide-search",
																	autofocus: "",
																	class: "w-full"
																}, null, 8, [
																	"size",
																	"modelValue",
																	"onUpdate:modelValue"
																]), createVNode("div", { class: "max-h-72 overflow-y-auto" }, [(openBlock(true), createBlock(Fragment, null, renderList(filteredCountries.value, (country) => {
																	return openBlock(), createBlock("button", {
																		key: `${country.name}-${country.extension}`,
																		type: "button",
																		class: "flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm hover:bg-white/10",
																		onClick: ($event) => selectCountry(country)
																	}, [createVNode("span", { class: "text-lg" }, toDisplayString(country.flag), 1), createVNode("span", { class: "min-w-0 flex-1" }, [createVNode("span", { class: "block truncate text-white" }, toDisplayString(country.name), 1), createVNode("span", { class: "text-xs text-white/50" }, " +" + toDisplayString(country.extension), 1)])], 8, ["onClick"]);
																}), 128)), filteredCountries.value.length === 0 ? (openBlock(), createBlock("p", {
																	key: 0,
																	class: "px-3 py-4 text-center text-sm text-white/50"
																}, " Aucun résultat ")) : createCommentVNode("", true)])])]),
																default: withCtx(() => [createVNode(unref(UButton), {
																	type: "button",
																	color: "neutral",
																	variant: "outline",
																	class: "h-full w-1/4 justify-between rounded-none border-0 bg-transparent px-3 rounded-l-md",
																	"trailing-icon": "i-lucide-chevron-down"
																}, {
																	default: withCtx(() => [createVNode("span", { class: "flex items-center gap-2" }, [selectedCountry.value ? (openBlock(), createBlock("span", { key: 0 }, toDisplayString(selectedCountry.value.flag), 1)) : createCommentVNode("", true), createVNode("span", null, toDisplayString(unref(userDataForm).phone.extension ? `+${unref(userDataForm).phone.extension}` : "+..."), 1)])]),
																	_: 1
																})]),
																_: 1
															}, 8, ["open", "onUpdate:open"]),
															createVNode("div", { class: "w-px bg-white/10" }),
															withDirectives(createVNode(_component_UInput, {
																"model-value": unref(userDataForm).phone.number,
																variant: "outline",
																type: "tel",
																inputmode: "tel",
																class: "min-w-0 flex-1 h-full",
																placeholder: props.__.resources.users.fields.phone.label,
																ui: { base: "rounded-none border-0 bg-transparent h-full rounded-r-md" },
																"onUpdate:modelValue": updateNumber
															}, null, 8, ["model-value", "placeholder"]), [[unref(vMaska), "##########"]])
														]),
														_: 1
													})]),
													_: 1
												})])];
											}),
											settings: withCtx((_, _push, _parent, _scopeId) => {
												if (_push) {
													_push(`<div class="flex gap-4 items-center justify-center w-full"${_scopeId}>`);
													_push(ssrRenderComponent(_component_UFormField, { class: "w-full" }, {
														label: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) {
																_push(`<span class="inline-flex items-center gap-1"${_scopeId}><span${_scopeId}>${ssrInterpolate(props.__.resources.users.fields.preferred_locale)}</span>`);
																if (unref(isFieldDirty)(unref(userDataForm), initialUserSettingsForm.value, "preferred_locale")) _push(`<span class="inline-block size-1.5 shrink-0 rounded-full bg-warning" title="Modification non sauvegardée"${_scopeId}></span>`);
																else _push(`<!---->`);
																_push(`</span>`);
															} else return [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.preferred_locale), 1), unref(isFieldDirty)(unref(userDataForm), initialUserSettingsForm.value, "preferred_locale") ? (openBlock(), createBlock("span", {
																key: 0,
																class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
																title: "Modification non sauvegardée"
															})) : createCommentVNode("", true)])];
														}),
														default: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) _push(ssrRenderComponent(_component_USelectMenu, {
																modelValue: unref(userSettingsForm).preferred_locale,
																"onUpdate:modelValue": ($event) => unref(userSettingsForm).preferred_locale = $event,
																items: __props.availableLocales,
																"value-key": "value",
																class: "w-full"
															}, {
																default: withCtx(({ modelValue }, _push, _parent, _scopeId) => {
																	if (_push) _push(`${ssrInterpolate(__props.availableLocales.filter((locale) => locale.value === modelValue)[0]?.icon || "")} ${ssrInterpolate(__props.availableLocales.filter((locale) => locale.value === modelValue)[0]?.label || "")}`);
																	else return [createTextVNode(toDisplayString(__props.availableLocales.filter((locale) => locale.value === modelValue)[0]?.icon || "") + " " + toDisplayString(__props.availableLocales.filter((locale) => locale.value === modelValue)[0]?.label || ""), 1)];
																}),
																item: withCtx(({ item }, _push, _parent, _scopeId) => {
																	if (_push) _push(`${ssrInterpolate(item.icon)} ${ssrInterpolate(item.label)}`);
																	else return [createTextVNode(toDisplayString(item.icon) + " " + toDisplayString(item.label), 1)];
																}),
																_: 2
															}, _parent, _scopeId));
															else return [createVNode(_component_USelectMenu, {
																modelValue: unref(userSettingsForm).preferred_locale,
																"onUpdate:modelValue": ($event) => unref(userSettingsForm).preferred_locale = $event,
																items: __props.availableLocales,
																"value-key": "value",
																class: "w-full"
															}, {
																default: withCtx(({ modelValue }) => [createTextVNode(toDisplayString(__props.availableLocales.filter((locale) => locale.value === modelValue)[0]?.icon || "") + " " + toDisplayString(__props.availableLocales.filter((locale) => locale.value === modelValue)[0]?.label || ""), 1)]),
																item: withCtx(({ item }) => [createTextVNode(toDisplayString(item.icon) + " " + toDisplayString(item.label), 1)]),
																_: 2
															}, 1032, [
																"modelValue",
																"onUpdate:modelValue",
																"items"
															])];
														}),
														_: 2
													}, _parent, _scopeId));
													_push(ssrRenderComponent(_component_UFormField, { class: "w-full" }, {
														label: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) {
																_push(`<span class="inline-flex items-center gap-1"${_scopeId}><span${_scopeId}>${ssrInterpolate(props.__.resources.users.fields.preferred_timezone)}</span>`);
																if (unref(isFieldDirty)(unref(userDataForm), initialUserSettingsForm.value, "preferred_timezone")) _push(`<span class="inline-block size-1.5 shrink-0 rounded-full bg-warning" title="Modification non sauvegardée"${_scopeId}></span>`);
																else _push(`<!---->`);
																_push(`</span>`);
															} else return [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.preferred_timezone), 1), unref(isFieldDirty)(unref(userDataForm), initialUserSettingsForm.value, "preferred_timezone") ? (openBlock(), createBlock("span", {
																key: 0,
																class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
																title: "Modification non sauvegardée"
															})) : createCommentVNode("", true)])];
														}),
														default: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) _push(ssrRenderComponent(_component_USelectMenu, {
																modelValue: unref(userSettingsForm).preferred_timezone,
																"onUpdate:modelValue": ($event) => unref(userSettingsForm).preferred_timezone = $event,
																items: __props.availableTimezones,
																"value-key": "value",
																class: "w-full"
															}, null, _parent, _scopeId));
															else return [createVNode(_component_USelectMenu, {
																modelValue: unref(userSettingsForm).preferred_timezone,
																"onUpdate:modelValue": ($event) => unref(userSettingsForm).preferred_timezone = $event,
																items: __props.availableTimezones,
																"value-key": "value",
																class: "w-full"
															}, null, 8, [
																"modelValue",
																"onUpdate:modelValue",
																"items"
															])];
														}),
														_: 2
													}, _parent, _scopeId));
													_push(ssrRenderComponent(_component_UFormField, { class: "w-full" }, {
														label: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) {
																_push(`<span class="inline-flex items-center gap-1"${_scopeId}><span${_scopeId}>${ssrInterpolate(props.__.resources.users.fields.preferred_start_page)}</span>`);
																if (unref(isFieldDirty)(unref(userDataForm), initialUserSettingsForm.value, "preferred_start_page")) _push(`<span class="inline-block size-1.5 shrink-0 rounded-full bg-warning" title="Modification non sauvegardée"${_scopeId}></span>`);
																else _push(`<!---->`);
																_push(`</span>`);
															} else return [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.preferred_start_page), 1), unref(isFieldDirty)(unref(userDataForm), initialUserSettingsForm.value, "preferred_start_page") ? (openBlock(), createBlock("span", {
																key: 0,
																class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
																title: "Modification non sauvegardée"
															})) : createCommentVNode("", true)])];
														}),
														default: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) _push(ssrRenderComponent(_component_USelect, {
																modelValue: unref(userSettingsForm).preferred_start_page,
																"onUpdate:modelValue": ($event) => unref(userSettingsForm).preferred_start_page = $event,
																items: __props.availableStartPages,
																class: "w-full"
															}, null, _parent, _scopeId));
															else return [createVNode(_component_USelect, {
																modelValue: unref(userSettingsForm).preferred_start_page,
																"onUpdate:modelValue": ($event) => unref(userSettingsForm).preferred_start_page = $event,
																items: __props.availableStartPages,
																class: "w-full"
															}, null, 8, [
																"modelValue",
																"onUpdate:modelValue",
																"items"
															])];
														}),
														_: 2
													}, _parent, _scopeId));
													_push(`</div>`);
												} else return [createVNode("div", { class: "flex gap-4 items-center justify-center w-full" }, [
													createVNode(_component_UFormField, { class: "w-full" }, {
														label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.preferred_locale), 1), unref(isFieldDirty)(unref(userDataForm), initialUserSettingsForm.value, "preferred_locale") ? (openBlock(), createBlock("span", {
															key: 0,
															class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
															title: "Modification non sauvegardée"
														})) : createCommentVNode("", true)])]),
														default: withCtx(() => [createVNode(_component_USelectMenu, {
															modelValue: unref(userSettingsForm).preferred_locale,
															"onUpdate:modelValue": ($event) => unref(userSettingsForm).preferred_locale = $event,
															items: __props.availableLocales,
															"value-key": "value",
															class: "w-full"
														}, {
															default: withCtx(({ modelValue }) => [createTextVNode(toDisplayString(__props.availableLocales.filter((locale) => locale.value === modelValue)[0]?.icon || "") + " " + toDisplayString(__props.availableLocales.filter((locale) => locale.value === modelValue)[0]?.label || ""), 1)]),
															item: withCtx(({ item }) => [createTextVNode(toDisplayString(item.icon) + " " + toDisplayString(item.label), 1)]),
															_: 2
														}, 1032, [
															"modelValue",
															"onUpdate:modelValue",
															"items"
														])]),
														_: 2
													}, 1024),
													createVNode(_component_UFormField, { class: "w-full" }, {
														label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.preferred_timezone), 1), unref(isFieldDirty)(unref(userDataForm), initialUserSettingsForm.value, "preferred_timezone") ? (openBlock(), createBlock("span", {
															key: 0,
															class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
															title: "Modification non sauvegardée"
														})) : createCommentVNode("", true)])]),
														default: withCtx(() => [createVNode(_component_USelectMenu, {
															modelValue: unref(userSettingsForm).preferred_timezone,
															"onUpdate:modelValue": ($event) => unref(userSettingsForm).preferred_timezone = $event,
															items: __props.availableTimezones,
															"value-key": "value",
															class: "w-full"
														}, null, 8, [
															"modelValue",
															"onUpdate:modelValue",
															"items"
														])]),
														_: 1
													}),
													createVNode(_component_UFormField, { class: "w-full" }, {
														label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.preferred_start_page), 1), unref(isFieldDirty)(unref(userDataForm), initialUserSettingsForm.value, "preferred_start_page") ? (openBlock(), createBlock("span", {
															key: 0,
															class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
															title: "Modification non sauvegardée"
														})) : createCommentVNode("", true)])]),
														default: withCtx(() => [createVNode(_component_USelect, {
															modelValue: unref(userSettingsForm).preferred_start_page,
															"onUpdate:modelValue": ($event) => unref(userSettingsForm).preferred_start_page = $event,
															items: __props.availableStartPages,
															class: "w-full"
														}, null, 8, [
															"modelValue",
															"onUpdate:modelValue",
															"items"
														])]),
														_: 1
													})
												])];
											}),
											_: 2
										}, _parent, _scopeId));
										else return [createVNode(_component_UAccordion, {
											modelValue: userDataAccordionActive.value,
											"onUpdate:modelValue": ($event) => userDataAccordionActive.value = $event,
											items: userDataAccordionItems,
											ui: {
												trigger: "cursor-pointer rounded-md hover:bg-white/10 px-4 text-white data-[state=open]:text-primary-light data-[state=open]:bg-white/5",
												content: "py-4"
											}
										}, {
											default: withCtx(({ item }) => [createVNode("h3", { class: "text-lg" }, toDisplayString(item.label), 1)]),
											login: withCtx(({ item }) => [createVNode("div", { class: "flex gap-4 justify-between" }, [createVNode(_component_UFormField, {
												required: "",
												help: props.__.forms.users.help.username,
												name: "username",
												class: "w-full"
											}, {
												label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.username), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "username") ? (openBlock(), createBlock("span", {
													key: 0,
													class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
													title: "Modification non sauvegardée"
												})) : createCommentVNode("", true)])]),
												default: withCtx(() => [createVNode(_component_UInput, {
													modelValue: unref(userDataForm).username,
													"onUpdate:modelValue": ($event) => unref(userDataForm).username = $event,
													placeholder: " ",
													ui: { base: "peer" },
													required: "",
													class: "w-full",
													size: "lg"
												}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
												_: 1
											}, 8, ["help"]), createVNode(_component_UFormField, {
												required: "",
												class: "w-full"
											}, {
												label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.email), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "email") ? (openBlock(), createBlock("span", {
													key: 0,
													class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
													title: "Modification non sauvegardée"
												})) : createCommentVNode("", true)])]),
												default: withCtx(() => [createVNode(_component_UInput, {
													modelValue: unref(userDataForm).email,
													"onUpdate:modelValue": ($event) => unref(userDataForm).email = $event,
													placeholder: " ",
													ui: { base: "peer" },
													required: "",
													class: "w-full",
													size: "lg"
												}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
												_: 1
											})])]),
											"personal-data": withCtx(({ item }) => [createVNode("div", { class: "grid grid-cols-2 gap-4 py-2" }, [createVNode(_component_UFormField, null, {
												label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.last_name), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "last_name") ? (openBlock(), createBlock("span", {
													key: 0,
													class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
													title: "Modification non sauvegardée"
												})) : createCommentVNode("", true)])]),
												default: withCtx(() => [createVNode(_component_UInput, {
													modelValue: unref(userDataForm).last_name,
													"onUpdate:modelValue": ($event) => unref(userDataForm).last_name = $event,
													placeholder: " ",
													ui: { base: "peer" },
													class: "w-full"
												}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
												_: 1
											}), createVNode(_component_UFormField, null, {
												label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.first_name), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "first_name") ? (openBlock(), createBlock("span", {
													key: 0,
													class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
													title: "Modification non sauvegardée"
												})) : createCommentVNode("", true)])]),
												default: withCtx(() => [createVNode(_component_UInput, {
													modelValue: unref(userDataForm).first_name,
													"onUpdate:modelValue": ($event) => unref(userDataForm).first_name = $event,
													placeholder: " ",
													ui: { base: "peer" },
													class: "w-full"
												}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
												_: 1
											})]), createVNode("div", { class: "grid grid-cols-2 gap-4" }, [createVNode(_component_UFormField, null, {
												label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.job_title), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "job_title") ? (openBlock(), createBlock("span", {
													key: 0,
													class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
													title: "Modification non sauvegardée"
												})) : createCommentVNode("", true)])]),
												default: withCtx(() => [createVNode(_component_UInput, {
													modelValue: unref(userDataForm).job_title,
													"onUpdate:modelValue": ($event) => unref(userDataForm).job_title = $event,
													label: "Poste",
													placeholder: " ",
													ui: { base: "peer" },
													class: "w-full"
												}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
												_: 1
											}), createVNode(_component_UFormField, { class: "w-full" }, {
												label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.phone.label), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "phone") ? (openBlock(), createBlock("span", {
													key: 0,
													class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
													title: "Modification non sauvegardée"
												})) : createCommentVNode("", true)])]),
												default: withCtx(() => [createVNode(_component_UFieldGroup, { class: "w-full" }, {
													default: withCtx(() => [
														createVNode(_component_UPopover, {
															open: countryOpen.value,
															"onUpdate:open": ($event) => countryOpen.value = $event
														}, {
															content: withCtx(() => [createVNode("div", { class: "w-80 space-y-2 p-2" }, [createVNode(_component_UInput, {
																size: _ctx.size,
																modelValue: countrySearch.value,
																"onUpdate:modelValue": ($event) => countrySearch.value = $event,
																placeholder: "Rechercher un pays ou indicatif...",
																icon: "i-lucide-search",
																autofocus: "",
																class: "w-full"
															}, null, 8, [
																"size",
																"modelValue",
																"onUpdate:modelValue"
															]), createVNode("div", { class: "max-h-72 overflow-y-auto" }, [(openBlock(true), createBlock(Fragment, null, renderList(filteredCountries.value, (country) => {
																return openBlock(), createBlock("button", {
																	key: `${country.name}-${country.extension}`,
																	type: "button",
																	class: "flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm hover:bg-white/10",
																	onClick: ($event) => selectCountry(country)
																}, [createVNode("span", { class: "text-lg" }, toDisplayString(country.flag), 1), createVNode("span", { class: "min-w-0 flex-1" }, [createVNode("span", { class: "block truncate text-white" }, toDisplayString(country.name), 1), createVNode("span", { class: "text-xs text-white/50" }, " +" + toDisplayString(country.extension), 1)])], 8, ["onClick"]);
															}), 128)), filteredCountries.value.length === 0 ? (openBlock(), createBlock("p", {
																key: 0,
																class: "px-3 py-4 text-center text-sm text-white/50"
															}, " Aucun résultat ")) : createCommentVNode("", true)])])]),
															default: withCtx(() => [createVNode(unref(UButton), {
																type: "button",
																color: "neutral",
																variant: "outline",
																class: "h-full w-1/4 justify-between rounded-none border-0 bg-transparent px-3 rounded-l-md",
																"trailing-icon": "i-lucide-chevron-down"
															}, {
																default: withCtx(() => [createVNode("span", { class: "flex items-center gap-2" }, [selectedCountry.value ? (openBlock(), createBlock("span", { key: 0 }, toDisplayString(selectedCountry.value.flag), 1)) : createCommentVNode("", true), createVNode("span", null, toDisplayString(unref(userDataForm).phone.extension ? `+${unref(userDataForm).phone.extension}` : "+..."), 1)])]),
																_: 1
															})]),
															_: 1
														}, 8, ["open", "onUpdate:open"]),
														createVNode("div", { class: "w-px bg-white/10" }),
														withDirectives(createVNode(_component_UInput, {
															"model-value": unref(userDataForm).phone.number,
															variant: "outline",
															type: "tel",
															inputmode: "tel",
															class: "min-w-0 flex-1 h-full",
															placeholder: props.__.resources.users.fields.phone.label,
															ui: { base: "rounded-none border-0 bg-transparent h-full rounded-r-md" },
															"onUpdate:modelValue": updateNumber
														}, null, 8, ["model-value", "placeholder"]), [[unref(vMaska), "##########"]])
													]),
													_: 1
												})]),
												_: 1
											})])]),
											settings: withCtx(() => [createVNode("div", { class: "flex gap-4 items-center justify-center w-full" }, [
												createVNode(_component_UFormField, { class: "w-full" }, {
													label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.preferred_locale), 1), unref(isFieldDirty)(unref(userDataForm), initialUserSettingsForm.value, "preferred_locale") ? (openBlock(), createBlock("span", {
														key: 0,
														class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
														title: "Modification non sauvegardée"
													})) : createCommentVNode("", true)])]),
													default: withCtx(() => [createVNode(_component_USelectMenu, {
														modelValue: unref(userSettingsForm).preferred_locale,
														"onUpdate:modelValue": ($event) => unref(userSettingsForm).preferred_locale = $event,
														items: __props.availableLocales,
														"value-key": "value",
														class: "w-full"
													}, {
														default: withCtx(({ modelValue }) => [createTextVNode(toDisplayString(__props.availableLocales.filter((locale) => locale.value === modelValue)[0]?.icon || "") + " " + toDisplayString(__props.availableLocales.filter((locale) => locale.value === modelValue)[0]?.label || ""), 1)]),
														item: withCtx(({ item }) => [createTextVNode(toDisplayString(item.icon) + " " + toDisplayString(item.label), 1)]),
														_: 2
													}, 1032, [
														"modelValue",
														"onUpdate:modelValue",
														"items"
													])]),
													_: 2
												}, 1024),
												createVNode(_component_UFormField, { class: "w-full" }, {
													label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.preferred_timezone), 1), unref(isFieldDirty)(unref(userDataForm), initialUserSettingsForm.value, "preferred_timezone") ? (openBlock(), createBlock("span", {
														key: 0,
														class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
														title: "Modification non sauvegardée"
													})) : createCommentVNode("", true)])]),
													default: withCtx(() => [createVNode(_component_USelectMenu, {
														modelValue: unref(userSettingsForm).preferred_timezone,
														"onUpdate:modelValue": ($event) => unref(userSettingsForm).preferred_timezone = $event,
														items: __props.availableTimezones,
														"value-key": "value",
														class: "w-full"
													}, null, 8, [
														"modelValue",
														"onUpdate:modelValue",
														"items"
													])]),
													_: 1
												}),
												createVNode(_component_UFormField, { class: "w-full" }, {
													label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.preferred_start_page), 1), unref(isFieldDirty)(unref(userDataForm), initialUserSettingsForm.value, "preferred_start_page") ? (openBlock(), createBlock("span", {
														key: 0,
														class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
														title: "Modification non sauvegardée"
													})) : createCommentVNode("", true)])]),
													default: withCtx(() => [createVNode(_component_USelect, {
														modelValue: unref(userSettingsForm).preferred_start_page,
														"onUpdate:modelValue": ($event) => unref(userSettingsForm).preferred_start_page = $event,
														items: __props.availableStartPages,
														class: "w-full"
													}, null, 8, [
														"modelValue",
														"onUpdate:modelValue",
														"items"
													])]),
													_: 1
												})
											])]),
											_: 2
										}, 1032, ["modelValue", "onUpdate:modelValue"])];
									}),
									_: 2
								}, _parent, _scopeId));
							} else return [
								createVNode("p", { class: "text-muted" }, toDisplayString(item.description), 1),
								createVNode("hr", { class: "text-white/30 mt-2" }),
								createVNode(_component_UForm, {
									state: unref(userDataForm),
									class: "flex flex-col gap-4 mt-5",
									onSubmit: submitUserModal
								}, {
									default: withCtx(() => [createVNode(_component_UAccordion, {
										modelValue: userDataAccordionActive.value,
										"onUpdate:modelValue": ($event) => userDataAccordionActive.value = $event,
										items: userDataAccordionItems,
										ui: {
											trigger: "cursor-pointer rounded-md hover:bg-white/10 px-4 text-white data-[state=open]:text-primary-light data-[state=open]:bg-white/5",
											content: "py-4"
										}
									}, {
										default: withCtx(({ item }) => [createVNode("h3", { class: "text-lg" }, toDisplayString(item.label), 1)]),
										login: withCtx(({ item }) => [createVNode("div", { class: "flex gap-4 justify-between" }, [createVNode(_component_UFormField, {
											required: "",
											help: props.__.forms.users.help.username,
											name: "username",
											class: "w-full"
										}, {
											label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.username), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "username") ? (openBlock(), createBlock("span", {
												key: 0,
												class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
												title: "Modification non sauvegardée"
											})) : createCommentVNode("", true)])]),
											default: withCtx(() => [createVNode(_component_UInput, {
												modelValue: unref(userDataForm).username,
												"onUpdate:modelValue": ($event) => unref(userDataForm).username = $event,
												placeholder: " ",
												ui: { base: "peer" },
												required: "",
												class: "w-full",
												size: "lg"
											}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
											_: 1
										}, 8, ["help"]), createVNode(_component_UFormField, {
											required: "",
											class: "w-full"
										}, {
											label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.email), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "email") ? (openBlock(), createBlock("span", {
												key: 0,
												class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
												title: "Modification non sauvegardée"
											})) : createCommentVNode("", true)])]),
											default: withCtx(() => [createVNode(_component_UInput, {
												modelValue: unref(userDataForm).email,
												"onUpdate:modelValue": ($event) => unref(userDataForm).email = $event,
												placeholder: " ",
												ui: { base: "peer" },
												required: "",
												class: "w-full",
												size: "lg"
											}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
											_: 1
										})])]),
										"personal-data": withCtx(({ item }) => [createVNode("div", { class: "grid grid-cols-2 gap-4 py-2" }, [createVNode(_component_UFormField, null, {
											label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.last_name), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "last_name") ? (openBlock(), createBlock("span", {
												key: 0,
												class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
												title: "Modification non sauvegardée"
											})) : createCommentVNode("", true)])]),
											default: withCtx(() => [createVNode(_component_UInput, {
												modelValue: unref(userDataForm).last_name,
												"onUpdate:modelValue": ($event) => unref(userDataForm).last_name = $event,
												placeholder: " ",
												ui: { base: "peer" },
												class: "w-full"
											}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
											_: 1
										}), createVNode(_component_UFormField, null, {
											label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.first_name), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "first_name") ? (openBlock(), createBlock("span", {
												key: 0,
												class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
												title: "Modification non sauvegardée"
											})) : createCommentVNode("", true)])]),
											default: withCtx(() => [createVNode(_component_UInput, {
												modelValue: unref(userDataForm).first_name,
												"onUpdate:modelValue": ($event) => unref(userDataForm).first_name = $event,
												placeholder: " ",
												ui: { base: "peer" },
												class: "w-full"
											}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
											_: 1
										})]), createVNode("div", { class: "grid grid-cols-2 gap-4" }, [createVNode(_component_UFormField, null, {
											label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.job_title), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "job_title") ? (openBlock(), createBlock("span", {
												key: 0,
												class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
												title: "Modification non sauvegardée"
											})) : createCommentVNode("", true)])]),
											default: withCtx(() => [createVNode(_component_UInput, {
												modelValue: unref(userDataForm).job_title,
												"onUpdate:modelValue": ($event) => unref(userDataForm).job_title = $event,
												label: "Poste",
												placeholder: " ",
												ui: { base: "peer" },
												class: "w-full"
											}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
											_: 1
										}), createVNode(_component_UFormField, { class: "w-full" }, {
											label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.phone.label), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "phone") ? (openBlock(), createBlock("span", {
												key: 0,
												class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
												title: "Modification non sauvegardée"
											})) : createCommentVNode("", true)])]),
											default: withCtx(() => [createVNode(_component_UFieldGroup, { class: "w-full" }, {
												default: withCtx(() => [
													createVNode(_component_UPopover, {
														open: countryOpen.value,
														"onUpdate:open": ($event) => countryOpen.value = $event
													}, {
														content: withCtx(() => [createVNode("div", { class: "w-80 space-y-2 p-2" }, [createVNode(_component_UInput, {
															size: _ctx.size,
															modelValue: countrySearch.value,
															"onUpdate:modelValue": ($event) => countrySearch.value = $event,
															placeholder: "Rechercher un pays ou indicatif...",
															icon: "i-lucide-search",
															autofocus: "",
															class: "w-full"
														}, null, 8, [
															"size",
															"modelValue",
															"onUpdate:modelValue"
														]), createVNode("div", { class: "max-h-72 overflow-y-auto" }, [(openBlock(true), createBlock(Fragment, null, renderList(filteredCountries.value, (country) => {
															return openBlock(), createBlock("button", {
																key: `${country.name}-${country.extension}`,
																type: "button",
																class: "flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm hover:bg-white/10",
																onClick: ($event) => selectCountry(country)
															}, [createVNode("span", { class: "text-lg" }, toDisplayString(country.flag), 1), createVNode("span", { class: "min-w-0 flex-1" }, [createVNode("span", { class: "block truncate text-white" }, toDisplayString(country.name), 1), createVNode("span", { class: "text-xs text-white/50" }, " +" + toDisplayString(country.extension), 1)])], 8, ["onClick"]);
														}), 128)), filteredCountries.value.length === 0 ? (openBlock(), createBlock("p", {
															key: 0,
															class: "px-3 py-4 text-center text-sm text-white/50"
														}, " Aucun résultat ")) : createCommentVNode("", true)])])]),
														default: withCtx(() => [createVNode(unref(UButton), {
															type: "button",
															color: "neutral",
															variant: "outline",
															class: "h-full w-1/4 justify-between rounded-none border-0 bg-transparent px-3 rounded-l-md",
															"trailing-icon": "i-lucide-chevron-down"
														}, {
															default: withCtx(() => [createVNode("span", { class: "flex items-center gap-2" }, [selectedCountry.value ? (openBlock(), createBlock("span", { key: 0 }, toDisplayString(selectedCountry.value.flag), 1)) : createCommentVNode("", true), createVNode("span", null, toDisplayString(unref(userDataForm).phone.extension ? `+${unref(userDataForm).phone.extension}` : "+..."), 1)])]),
															_: 1
														})]),
														_: 1
													}, 8, ["open", "onUpdate:open"]),
													createVNode("div", { class: "w-px bg-white/10" }),
													withDirectives(createVNode(_component_UInput, {
														"model-value": unref(userDataForm).phone.number,
														variant: "outline",
														type: "tel",
														inputmode: "tel",
														class: "min-w-0 flex-1 h-full",
														placeholder: props.__.resources.users.fields.phone.label,
														ui: { base: "rounded-none border-0 bg-transparent h-full rounded-r-md" },
														"onUpdate:modelValue": updateNumber
													}, null, 8, ["model-value", "placeholder"]), [[unref(vMaska), "##########"]])
												]),
												_: 1
											})]),
											_: 1
										})])]),
										settings: withCtx(() => [createVNode("div", { class: "flex gap-4 items-center justify-center w-full" }, [
											createVNode(_component_UFormField, { class: "w-full" }, {
												label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.preferred_locale), 1), unref(isFieldDirty)(unref(userDataForm), initialUserSettingsForm.value, "preferred_locale") ? (openBlock(), createBlock("span", {
													key: 0,
													class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
													title: "Modification non sauvegardée"
												})) : createCommentVNode("", true)])]),
												default: withCtx(() => [createVNode(_component_USelectMenu, {
													modelValue: unref(userSettingsForm).preferred_locale,
													"onUpdate:modelValue": ($event) => unref(userSettingsForm).preferred_locale = $event,
													items: __props.availableLocales,
													"value-key": "value",
													class: "w-full"
												}, {
													default: withCtx(({ modelValue }) => [createTextVNode(toDisplayString(__props.availableLocales.filter((locale) => locale.value === modelValue)[0]?.icon || "") + " " + toDisplayString(__props.availableLocales.filter((locale) => locale.value === modelValue)[0]?.label || ""), 1)]),
													item: withCtx(({ item }) => [createTextVNode(toDisplayString(item.icon) + " " + toDisplayString(item.label), 1)]),
													_: 2
												}, 1032, [
													"modelValue",
													"onUpdate:modelValue",
													"items"
												])]),
												_: 2
											}, 1024),
											createVNode(_component_UFormField, { class: "w-full" }, {
												label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.preferred_timezone), 1), unref(isFieldDirty)(unref(userDataForm), initialUserSettingsForm.value, "preferred_timezone") ? (openBlock(), createBlock("span", {
													key: 0,
													class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
													title: "Modification non sauvegardée"
												})) : createCommentVNode("", true)])]),
												default: withCtx(() => [createVNode(_component_USelectMenu, {
													modelValue: unref(userSettingsForm).preferred_timezone,
													"onUpdate:modelValue": ($event) => unref(userSettingsForm).preferred_timezone = $event,
													items: __props.availableTimezones,
													"value-key": "value",
													class: "w-full"
												}, null, 8, [
													"modelValue",
													"onUpdate:modelValue",
													"items"
												])]),
												_: 1
											}),
											createVNode(_component_UFormField, { class: "w-full" }, {
												label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.preferred_start_page), 1), unref(isFieldDirty)(unref(userDataForm), initialUserSettingsForm.value, "preferred_start_page") ? (openBlock(), createBlock("span", {
													key: 0,
													class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
													title: "Modification non sauvegardée"
												})) : createCommentVNode("", true)])]),
												default: withCtx(() => [createVNode(_component_USelect, {
													modelValue: unref(userSettingsForm).preferred_start_page,
													"onUpdate:modelValue": ($event) => unref(userSettingsForm).preferred_start_page = $event,
													items: __props.availableStartPages,
													class: "w-full"
												}, null, 8, [
													"modelValue",
													"onUpdate:modelValue",
													"items"
												])]),
												_: 1
											})
										])]),
										_: 2
									}, 1032, ["modelValue", "onUpdate:modelValue"])]),
									_: 2
								}, 1032, ["state"])
							];
						}),
						security: withCtx(({ item }, _push, _parent, _scopeId) => {
							if (_push) {
								_push(`<p class="text-muted"${_scopeId}>${ssrInterpolate(item.description)}</p><hr class="text-white/30 mt-2"${_scopeId}>`);
								_push(ssrRenderComponent(_component_UForm, {
									state: unref(userSecurityForm),
									class: "flex flex-col gap-4 mt-5",
									onSubmit: submitUserModal
								}, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) {
											_push(`<div${_scopeId}>`);
											_push(ssrRenderComponent(_component_UFormField, {
												label: props.__.resources.users.fields.password,
												required: ""
											}, {
												default: withCtx((_, _push, _parent, _scopeId) => {
													if (_push) _push(ssrRenderComponent(_component_UInput, {
														modelValue: unref(userSecurityForm).new_password,
														"onUpdate:modelValue": ($event) => unref(userSecurityForm).new_password = $event,
														color: colorPassword.value,
														type: showPassword.value ? "text" : "password",
														"aria-invalid": score.value < 4,
														"aria-describedby": "password-strength",
														ui: { trailing: "pe-1" },
														class: "w-full"
													}, {
														trailing: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) _push(ssrRenderComponent(unref(UButton), {
																color: "neutral",
																variant: "link",
																size: "sm",
																icon: showPassword.value ? "i-lucide-eye-off" : "i-lucide-eye",
																"aria-label": showPassword.value ? "Hide password" : "Show password",
																"aria-pressed": showPassword.value,
																"aria-controls": "password",
																onClick: ($event) => showPassword.value = !showPassword.value
															}, null, _parent, _scopeId));
															else return [createVNode(unref(UButton), {
																color: "neutral",
																variant: "link",
																size: "sm",
																icon: showPassword.value ? "i-lucide-eye-off" : "i-lucide-eye",
																"aria-label": showPassword.value ? "Hide password" : "Show password",
																"aria-pressed": showPassword.value,
																"aria-controls": "password",
																onClick: ($event) => showPassword.value = !showPassword.value
															}, null, 8, [
																"icon",
																"aria-label",
																"aria-pressed",
																"onClick"
															])];
														}),
														_: 2
													}, _parent, _scopeId));
													else return [createVNode(_component_UInput, {
														modelValue: unref(userSecurityForm).new_password,
														"onUpdate:modelValue": ($event) => unref(userSecurityForm).new_password = $event,
														color: colorPassword.value,
														type: showPassword.value ? "text" : "password",
														"aria-invalid": score.value < 4,
														"aria-describedby": "password-strength",
														ui: { trailing: "pe-1" },
														class: "w-full"
													}, {
														trailing: withCtx(() => [createVNode(unref(UButton), {
															color: "neutral",
															variant: "link",
															size: "sm",
															icon: showPassword.value ? "i-lucide-eye-off" : "i-lucide-eye",
															"aria-label": showPassword.value ? "Hide password" : "Show password",
															"aria-pressed": showPassword.value,
															"aria-controls": "password",
															onClick: ($event) => showPassword.value = !showPassword.value
														}, null, 8, [
															"icon",
															"aria-label",
															"aria-pressed",
															"onClick"
														])]),
														_: 1
													}, 8, [
														"modelValue",
														"onUpdate:modelValue",
														"color",
														"type",
														"aria-invalid"
													])];
												}),
												_: 2
											}, _parent, _scopeId));
											_push(ssrRenderComponent(_component_UProgress, {
												color: colorPassword.value,
												indicator: validatorText.value,
												"model-value": score.value,
												max: 5,
												size: "sm",
												class: "mt-1"
											}, null, _parent, _scopeId));
											_push(`<p id="password-strength" class="text-sm font-medium"${_scopeId}>${ssrInterpolate(validatorText.value)}. `);
											if (score.value < 5) _push(`<span${_scopeId}>Must contain:</span>`);
											else _push(`<!---->`);
											_push(`</p><ul class="space-y-1" aria-label="Password requirements"${_scopeId}><!--[-->`);
											ssrRenderList(strengthPassword.value, (req, index) => {
												_push(`<li class="${ssrRenderClass([req.met ? "text-success" : "text-muted", "flex items-center gap-0.5"])}"${_scopeId}>`);
												_push(ssrRenderComponent(_component_UIcon, {
													name: req.met ? "i-lucide-circle-check" : "i-lucide-circle-x",
													class: "size-4 shrink-0"
												}, null, _parent, _scopeId));
												_push(`<span class="text-sm font-light"${_scopeId}>${ssrInterpolate(req.text)} <span class="sr-only"${_scopeId}>${ssrInterpolate(req.met ? " - Requirement met : " : " - Requirement not met")}</span></span></li>`);
											});
											_push(`<!--]--></ul></div>`);
											if (score.value >= 5) {
												_push(`<div${_scopeId}>`);
												_push(ssrRenderComponent(_component_UFormField, { label: props.__.resources.users.fields.password_confirmation }, {
													default: withCtx((_, _push, _parent, _scopeId) => {
														if (_push) {
															_push(ssrRenderComponent(_component_UInput, {
																modelValue: unref(userSecurityForm).confirm_password,
																"onUpdate:modelValue": ($event) => unref(userSecurityForm).confirm_password = $event,
																color: colorConfirmation.value,
																type: showConfirmation.value ? "text" : "password",
																"aria-invalid": score.value < 4,
																"aria-describedby": "password-strength",
																ui: { trailing: "pe-1" },
																class: "w-full"
															}, {
																trailing: withCtx((_, _push, _parent, _scopeId) => {
																	if (_push) _push(ssrRenderComponent(unref(UButton), {
																		color: "neutral",
																		variant: "link",
																		size: "sm",
																		icon: showConfirmation.value ? "i-lucide-eye-off" : "i-lucide-eye",
																		"aria-label": showConfirmation.value ? "Hide password" : "Show password",
																		"aria-pressed": showConfirmation.value,
																		"aria-controls": "password",
																		onClick: ($event) => showConfirmation.value = !showConfirmation.value
																	}, null, _parent, _scopeId));
																	else return [createVNode(unref(UButton), {
																		color: "neutral",
																		variant: "link",
																		size: "sm",
																		icon: showConfirmation.value ? "i-lucide-eye-off" : "i-lucide-eye",
																		"aria-label": showConfirmation.value ? "Hide password" : "Show password",
																		"aria-pressed": showConfirmation.value,
																		"aria-controls": "password",
																		onClick: ($event) => showConfirmation.value = !showConfirmation.value
																	}, null, 8, [
																		"icon",
																		"aria-label",
																		"aria-pressed",
																		"onClick"
																	])];
																}),
																_: 2
															}, _parent, _scopeId));
															if (unref(userSecurityForm).confirm_password !== "") _push(`<p id="password-confirmation" class="${ssrRenderClass([samePassword.value ? "text-success/80" : "text-danger/80", "text-sm font-medium"])}"${_scopeId}>${ssrInterpolate(confirmationText.value)}</p>`);
															else _push(`<!---->`);
														} else return [createVNode(_component_UInput, {
															modelValue: unref(userSecurityForm).confirm_password,
															"onUpdate:modelValue": ($event) => unref(userSecurityForm).confirm_password = $event,
															color: colorConfirmation.value,
															type: showConfirmation.value ? "text" : "password",
															"aria-invalid": score.value < 4,
															"aria-describedby": "password-strength",
															ui: { trailing: "pe-1" },
															class: "w-full"
														}, {
															trailing: withCtx(() => [createVNode(unref(UButton), {
																color: "neutral",
																variant: "link",
																size: "sm",
																icon: showConfirmation.value ? "i-lucide-eye-off" : "i-lucide-eye",
																"aria-label": showConfirmation.value ? "Hide password" : "Show password",
																"aria-pressed": showConfirmation.value,
																"aria-controls": "password",
																onClick: ($event) => showConfirmation.value = !showConfirmation.value
															}, null, 8, [
																"icon",
																"aria-label",
																"aria-pressed",
																"onClick"
															])]),
															_: 1
														}, 8, [
															"modelValue",
															"onUpdate:modelValue",
															"color",
															"type",
															"aria-invalid"
														]), unref(userSecurityForm).confirm_password !== "" ? (openBlock(), createBlock("p", {
															key: 0,
															id: "password-confirmation",
															class: ["text-sm font-medium", samePassword.value ? "text-success/80" : "text-danger/80"]
														}, toDisplayString(confirmationText.value), 3)) : createCommentVNode("", true)];
													}),
													_: 2
												}, _parent, _scopeId));
												_push(`</div>`);
											} else _push(`<!---->`);
										} else return [createVNode("div", null, [
											createVNode(_component_UFormField, {
												label: props.__.resources.users.fields.password,
												required: ""
											}, {
												default: withCtx(() => [createVNode(_component_UInput, {
													modelValue: unref(userSecurityForm).new_password,
													"onUpdate:modelValue": ($event) => unref(userSecurityForm).new_password = $event,
													color: colorPassword.value,
													type: showPassword.value ? "text" : "password",
													"aria-invalid": score.value < 4,
													"aria-describedby": "password-strength",
													ui: { trailing: "pe-1" },
													class: "w-full"
												}, {
													trailing: withCtx(() => [createVNode(unref(UButton), {
														color: "neutral",
														variant: "link",
														size: "sm",
														icon: showPassword.value ? "i-lucide-eye-off" : "i-lucide-eye",
														"aria-label": showPassword.value ? "Hide password" : "Show password",
														"aria-pressed": showPassword.value,
														"aria-controls": "password",
														onClick: ($event) => showPassword.value = !showPassword.value
													}, null, 8, [
														"icon",
														"aria-label",
														"aria-pressed",
														"onClick"
													])]),
													_: 1
												}, 8, [
													"modelValue",
													"onUpdate:modelValue",
													"color",
													"type",
													"aria-invalid"
												])]),
												_: 1
											}, 8, ["label"]),
											createVNode(_component_UProgress, {
												color: colorPassword.value,
												indicator: validatorText.value,
												"model-value": score.value,
												max: 5,
												size: "sm",
												class: "mt-1"
											}, null, 8, [
												"color",
												"indicator",
												"model-value"
											]),
											createVNode("p", {
												id: "password-strength",
												class: "text-sm font-medium"
											}, [createTextVNode(toDisplayString(validatorText.value) + ". ", 1), score.value < 5 ? (openBlock(), createBlock("span", { key: 0 }, "Must contain:")) : createCommentVNode("", true)]),
											createVNode("ul", {
												class: "space-y-1",
												"aria-label": "Password requirements"
											}, [(openBlock(true), createBlock(Fragment, null, renderList(strengthPassword.value, (req, index) => {
												return openBlock(), createBlock("li", {
													key: index,
													class: ["flex items-center gap-0.5", req.met ? "text-success" : "text-muted"]
												}, [createVNode(_component_UIcon, {
													name: req.met ? "i-lucide-circle-check" : "i-lucide-circle-x",
													class: "size-4 shrink-0"
												}, null, 8, ["name"]), createVNode("span", { class: "text-sm font-light" }, [createTextVNode(toDisplayString(req.text) + " ", 1), createVNode("span", { class: "sr-only" }, toDisplayString(req.met ? " - Requirement met : " : " - Requirement not met"), 1)])], 2);
											}), 128))])
										]), score.value >= 5 ? (openBlock(), createBlock("div", { key: 0 }, [createVNode(_component_UFormField, { label: props.__.resources.users.fields.password_confirmation }, {
											default: withCtx(() => [createVNode(_component_UInput, {
												modelValue: unref(userSecurityForm).confirm_password,
												"onUpdate:modelValue": ($event) => unref(userSecurityForm).confirm_password = $event,
												color: colorConfirmation.value,
												type: showConfirmation.value ? "text" : "password",
												"aria-invalid": score.value < 4,
												"aria-describedby": "password-strength",
												ui: { trailing: "pe-1" },
												class: "w-full"
											}, {
												trailing: withCtx(() => [createVNode(unref(UButton), {
													color: "neutral",
													variant: "link",
													size: "sm",
													icon: showConfirmation.value ? "i-lucide-eye-off" : "i-lucide-eye",
													"aria-label": showConfirmation.value ? "Hide password" : "Show password",
													"aria-pressed": showConfirmation.value,
													"aria-controls": "password",
													onClick: ($event) => showConfirmation.value = !showConfirmation.value
												}, null, 8, [
													"icon",
													"aria-label",
													"aria-pressed",
													"onClick"
												])]),
												_: 1
											}, 8, [
												"modelValue",
												"onUpdate:modelValue",
												"color",
												"type",
												"aria-invalid"
											]), unref(userSecurityForm).confirm_password !== "" ? (openBlock(), createBlock("p", {
												key: 0,
												id: "password-confirmation",
												class: ["text-sm font-medium", samePassword.value ? "text-success/80" : "text-danger/80"]
											}, toDisplayString(confirmationText.value), 3)) : createCommentVNode("", true)]),
											_: 1
										}, 8, ["label"])])) : createCommentVNode("", true)];
									}),
									_: 2
								}, _parent, _scopeId));
							} else return [
								createVNode("p", { class: "text-muted" }, toDisplayString(item.description), 1),
								createVNode("hr", { class: "text-white/30 mt-2" }),
								createVNode(_component_UForm, {
									state: unref(userSecurityForm),
									class: "flex flex-col gap-4 mt-5",
									onSubmit: submitUserModal
								}, {
									default: withCtx(() => [createVNode("div", null, [
										createVNode(_component_UFormField, {
											label: props.__.resources.users.fields.password,
											required: ""
										}, {
											default: withCtx(() => [createVNode(_component_UInput, {
												modelValue: unref(userSecurityForm).new_password,
												"onUpdate:modelValue": ($event) => unref(userSecurityForm).new_password = $event,
												color: colorPassword.value,
												type: showPassword.value ? "text" : "password",
												"aria-invalid": score.value < 4,
												"aria-describedby": "password-strength",
												ui: { trailing: "pe-1" },
												class: "w-full"
											}, {
												trailing: withCtx(() => [createVNode(unref(UButton), {
													color: "neutral",
													variant: "link",
													size: "sm",
													icon: showPassword.value ? "i-lucide-eye-off" : "i-lucide-eye",
													"aria-label": showPassword.value ? "Hide password" : "Show password",
													"aria-pressed": showPassword.value,
													"aria-controls": "password",
													onClick: ($event) => showPassword.value = !showPassword.value
												}, null, 8, [
													"icon",
													"aria-label",
													"aria-pressed",
													"onClick"
												])]),
												_: 1
											}, 8, [
												"modelValue",
												"onUpdate:modelValue",
												"color",
												"type",
												"aria-invalid"
											])]),
											_: 1
										}, 8, ["label"]),
										createVNode(_component_UProgress, {
											color: colorPassword.value,
											indicator: validatorText.value,
											"model-value": score.value,
											max: 5,
											size: "sm",
											class: "mt-1"
										}, null, 8, [
											"color",
											"indicator",
											"model-value"
										]),
										createVNode("p", {
											id: "password-strength",
											class: "text-sm font-medium"
										}, [createTextVNode(toDisplayString(validatorText.value) + ". ", 1), score.value < 5 ? (openBlock(), createBlock("span", { key: 0 }, "Must contain:")) : createCommentVNode("", true)]),
										createVNode("ul", {
											class: "space-y-1",
											"aria-label": "Password requirements"
										}, [(openBlock(true), createBlock(Fragment, null, renderList(strengthPassword.value, (req, index) => {
											return openBlock(), createBlock("li", {
												key: index,
												class: ["flex items-center gap-0.5", req.met ? "text-success" : "text-muted"]
											}, [createVNode(_component_UIcon, {
												name: req.met ? "i-lucide-circle-check" : "i-lucide-circle-x",
												class: "size-4 shrink-0"
											}, null, 8, ["name"]), createVNode("span", { class: "text-sm font-light" }, [createTextVNode(toDisplayString(req.text) + " ", 1), createVNode("span", { class: "sr-only" }, toDisplayString(req.met ? " - Requirement met : " : " - Requirement not met"), 1)])], 2);
										}), 128))])
									]), score.value >= 5 ? (openBlock(), createBlock("div", { key: 0 }, [createVNode(_component_UFormField, { label: props.__.resources.users.fields.password_confirmation }, {
										default: withCtx(() => [createVNode(_component_UInput, {
											modelValue: unref(userSecurityForm).confirm_password,
											"onUpdate:modelValue": ($event) => unref(userSecurityForm).confirm_password = $event,
											color: colorConfirmation.value,
											type: showConfirmation.value ? "text" : "password",
											"aria-invalid": score.value < 4,
											"aria-describedby": "password-strength",
											ui: { trailing: "pe-1" },
											class: "w-full"
										}, {
											trailing: withCtx(() => [createVNode(unref(UButton), {
												color: "neutral",
												variant: "link",
												size: "sm",
												icon: showConfirmation.value ? "i-lucide-eye-off" : "i-lucide-eye",
												"aria-label": showConfirmation.value ? "Hide password" : "Show password",
												"aria-pressed": showConfirmation.value,
												"aria-controls": "password",
												onClick: ($event) => showConfirmation.value = !showConfirmation.value
											}, null, 8, [
												"icon",
												"aria-label",
												"aria-pressed",
												"onClick"
											])]),
											_: 1
										}, 8, [
											"modelValue",
											"onUpdate:modelValue",
											"color",
											"type",
											"aria-invalid"
										]), unref(userSecurityForm).confirm_password !== "" ? (openBlock(), createBlock("p", {
											key: 0,
											id: "password-confirmation",
											class: ["text-sm font-medium", samePassword.value ? "text-success/80" : "text-danger/80"]
										}, toDisplayString(confirmationText.value), 3)) : createCommentVNode("", true)]),
										_: 1
									}, 8, ["label"])])) : createCommentVNode("", true)]),
									_: 1
								}, 8, ["state"])
							];
						}),
						admin: withCtx(({ item }, _push, _parent, _scopeId) => {
							if (_push) {
								_push(`<p class="text-muted"${_scopeId}>${ssrInterpolate(item.description)}</p><hr class="text-white/30 mt-2"${_scopeId}>`);
								_push(ssrRenderComponent(_component_UForm, {
									state: unref(userAdminForm),
									class: "flex flex-col gap-4 mt-5",
									onSubmit: submitUserModal
								}, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) _push(ssrRenderComponent(_component_UAccordion, {
											modelValue: userAdminAccordionActive.value,
											"onUpdate:modelValue": ($event) => userAdminAccordionActive.value = $event,
											items: userAdminAccordionItems,
											ui: {
												trigger: "cursor-pointer rounded-md hover:bg-white/10 px-4 text-white data-[state=open]:text-primary-light data-[state=open]:bg-white/5",
												content: "py-4"
											}
										}, {
											default: withCtx(({ item }, _push, _parent, _scopeId) => {
												if (_push) _push(`<h3 class="text-lg"${_scopeId}>${ssrInterpolate(item.label)}</h3>`);
												else return [createVNode("h3", { class: "text-lg" }, toDisplayString(item.label), 1)];
											}),
											access: withCtx(({ item }, _push, _parent, _scopeId) => {
												if (_push) {
													_push(`<div class="flex gap-4"${_scopeId}>`);
													_push(ssrRenderComponent(_component_UFormField, {
														label: props.__.resources.users.fields.suspended_until,
														help: props.__.forms.users.help.suspended_until,
														class: "w-1/2"
													}, {
														default: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) _push(ssrRenderComponent(FgInputDatePicker_default, {
																modelValue: unref(userAdminForm).suspended_until,
																"onUpdate:modelValue": ($event) => unref(userAdminForm).suspended_until = $event,
																__: props.__.global.forms
															}, null, _parent, _scopeId));
															else return [createVNode(FgInputDatePicker_default, {
																modelValue: unref(userAdminForm).suspended_until,
																"onUpdate:modelValue": ($event) => unref(userAdminForm).suspended_until = $event,
																__: props.__.global.forms
															}, null, 8, [
																"modelValue",
																"onUpdate:modelValue",
																"__"
															])];
														}),
														_: 2
													}, _parent, _scopeId));
													_push(ssrRenderComponent(_component_UFormField, {
														label: props.__.resources.users.fields.expire_at,
														help: props.__.forms.users.help.expire_at,
														name: "expire_at",
														class: "flex flex-col items-end [&>*:nth-child(2)]:flex [&>*:nth-child(2)]:flex-col [&>*:nth-child(2)]:items-end w-3/5",
														ui: { help: "text-right" }
													}, {
														default: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) _push(ssrRenderComponent(FgInputDatePicker_default, {
																modelValue: unref(userAdminForm).expire_at,
																"onUpdate:modelValue": ($event) => unref(userAdminForm).expire_at = $event,
																ui: { base: "flex justify-end" },
																__: props.__.global.forms
															}, null, _parent, _scopeId));
															else return [createVNode(FgInputDatePicker_default, {
																modelValue: unref(userAdminForm).expire_at,
																"onUpdate:modelValue": ($event) => unref(userAdminForm).expire_at = $event,
																ui: { base: "flex justify-end" },
																__: props.__.global.forms
															}, null, 8, [
																"modelValue",
																"onUpdate:modelValue",
																"__"
															])];
														}),
														_: 2
													}, _parent, _scopeId));
													_push(`</div>`);
													_push(ssrRenderComponent(_component_USeparator, { class: "my-3" }, null, _parent, _scopeId));
													_push(`<div class="flex gap-4"${_scopeId}>`);
													_push(ssrRenderComponent(_component_UFormField, {
														label: props.__.resources.users.fields.suspension_reason,
														help: props.__.forms.users.help.suspension_reason,
														class: "w-1/2"
													}, {
														default: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) _push(ssrRenderComponent(_component_UTextarea, {
																modelValue: unref(userAdminForm).suspension_reason,
																"onUpdate:modelValue": ($event) => unref(userAdminForm).suspension_reason = $event,
																placeholder: " ",
																class: "w-full",
																ui: { base: "peer max-h-37.5 min-h-15" }
															}, null, _parent, _scopeId));
															else return [createVNode(_component_UTextarea, {
																modelValue: unref(userAdminForm).suspension_reason,
																"onUpdate:modelValue": ($event) => unref(userAdminForm).suspension_reason = $event,
																placeholder: " ",
																class: "w-full",
																ui: { base: "peer max-h-37.5 min-h-15" }
															}, null, 8, ["modelValue", "onUpdate:modelValue"])];
														}),
														_: 2
													}, _parent, _scopeId));
													_push(ssrRenderComponent(_component_UFormField, {
														label: props.__.resources.users.fields.active,
														name: "active",
														class: "flex flex-col gap-2 w-2/5",
														help: props.__.forms.users.help.active
													}, {
														default: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) _push(ssrRenderComponent(_component_USwitch, {
																modelValue: unref(userAdminForm).active,
																"onUpdate:modelValue": ($event) => unref(userAdminForm).active = $event,
																color: "success",
																disabled: isAccountExpired.value
															}, null, _parent, _scopeId));
															else return [createVNode(_component_USwitch, {
																modelValue: unref(userAdminForm).active,
																"onUpdate:modelValue": ($event) => unref(userAdminForm).active = $event,
																color: "success",
																disabled: isAccountExpired.value
															}, null, 8, [
																"modelValue",
																"onUpdate:modelValue",
																"disabled"
															])];
														}),
														_: 2
													}, _parent, _scopeId));
													_push(`</div>`);
												} else return [
													createVNode("div", { class: "flex gap-4" }, [createVNode(_component_UFormField, {
														label: props.__.resources.users.fields.suspended_until,
														help: props.__.forms.users.help.suspended_until,
														class: "w-1/2"
													}, {
														default: withCtx(() => [createVNode(FgInputDatePicker_default, {
															modelValue: unref(userAdminForm).suspended_until,
															"onUpdate:modelValue": ($event) => unref(userAdminForm).suspended_until = $event,
															__: props.__.global.forms
														}, null, 8, [
															"modelValue",
															"onUpdate:modelValue",
															"__"
														])]),
														_: 1
													}, 8, ["label", "help"]), createVNode(_component_UFormField, {
														label: props.__.resources.users.fields.expire_at,
														help: props.__.forms.users.help.expire_at,
														name: "expire_at",
														class: "flex flex-col items-end [&>*:nth-child(2)]:flex [&>*:nth-child(2)]:flex-col [&>*:nth-child(2)]:items-end w-3/5",
														ui: { help: "text-right" }
													}, {
														default: withCtx(() => [createVNode(FgInputDatePicker_default, {
															modelValue: unref(userAdminForm).expire_at,
															"onUpdate:modelValue": ($event) => unref(userAdminForm).expire_at = $event,
															ui: { base: "flex justify-end" },
															__: props.__.global.forms
														}, null, 8, [
															"modelValue",
															"onUpdate:modelValue",
															"__"
														])]),
														_: 1
													}, 8, ["label", "help"])]),
													createVNode(_component_USeparator, { class: "my-3" }),
													createVNode("div", { class: "flex gap-4" }, [createVNode(_component_UFormField, {
														label: props.__.resources.users.fields.suspension_reason,
														help: props.__.forms.users.help.suspension_reason,
														class: "w-1/2"
													}, {
														default: withCtx(() => [createVNode(_component_UTextarea, {
															modelValue: unref(userAdminForm).suspension_reason,
															"onUpdate:modelValue": ($event) => unref(userAdminForm).suspension_reason = $event,
															placeholder: " ",
															class: "w-full",
															ui: { base: "peer max-h-37.5 min-h-15" }
														}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
														_: 1
													}, 8, ["label", "help"]), createVNode(_component_UFormField, {
														label: props.__.resources.users.fields.active,
														name: "active",
														class: "flex flex-col gap-2 w-2/5",
														help: props.__.forms.users.help.active
													}, {
														default: withCtx(() => [createVNode(_component_USwitch, {
															modelValue: unref(userAdminForm).active,
															"onUpdate:modelValue": ($event) => unref(userAdminForm).active = $event,
															color: "success",
															disabled: isAccountExpired.value
														}, null, 8, [
															"modelValue",
															"onUpdate:modelValue",
															"disabled"
														])]),
														_: 1
													}, 8, ["label", "help"])])
												];
											}),
											notes: withCtx(({ item }, _push, _parent, _scopeId) => {
												if (_push) _push(ssrRenderComponent(_component_UFormField, {
													label: props.__.resources.users.fields.admin_notes,
													help: props.__.forms.users.help.admin_notes,
													name: "admin_notes"
												}, {
													default: withCtx((_, _push, _parent, _scopeId) => {
														if (_push) _push(ssrRenderComponent(_component_UTextarea, {
															modelValue: unref(userAdminForm).admin_notes,
															"onUpdate:modelValue": ($event) => unref(userAdminForm).admin_notes = $event,
															placeholder: " ",
															ui: { base: "peer max-h-37.5 min-h-15" },
															class: "w-full"
														}, null, _parent, _scopeId));
														else return [createVNode(_component_UTextarea, {
															modelValue: unref(userAdminForm).admin_notes,
															"onUpdate:modelValue": ($event) => unref(userAdminForm).admin_notes = $event,
															placeholder: " ",
															ui: { base: "peer max-h-37.5 min-h-15" },
															class: "w-full"
														}, null, 8, ["modelValue", "onUpdate:modelValue"])];
													}),
													_: 2
												}, _parent, _scopeId));
												else return [createVNode(_component_UFormField, {
													label: props.__.resources.users.fields.admin_notes,
													help: props.__.forms.users.help.admin_notes,
													name: "admin_notes"
												}, {
													default: withCtx(() => [createVNode(_component_UTextarea, {
														modelValue: unref(userAdminForm).admin_notes,
														"onUpdate:modelValue": ($event) => unref(userAdminForm).admin_notes = $event,
														placeholder: " ",
														ui: { base: "peer max-h-37.5 min-h-15" },
														class: "w-full"
													}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
													_: 1
												}, 8, ["label", "help"])];
											}),
											constraints: withCtx(({ item }, _push, _parent, _scopeId) => {
												if (_push) {
													_push(`<div class="flex gap-4 justify-center"${_scopeId}>`);
													_push(ssrRenderComponent(_component_UFormField, { label: props.__.resources.users.fields.forced_actions }, {
														default: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) _push(ssrRenderComponent(_component_UCheckboxGroup, {
																indicator: "start",
																variant: "table",
																modelValue: unref(userAdminForm).forced_actions,
																"onUpdate:modelValue": ($event) => unref(userAdminForm).forced_actions = $event,
																items: __props.availableForcedActions,
																"value-key": "value"
															}, {
																label: withCtx(({ item }, _push, _parent, _scopeId) => {
																	if (_push) _push(`${ssrInterpolate(item.label.title)}`);
																	else return [createTextVNode(toDisplayString(item.label.title), 1)];
																}),
																description: withCtx(({ item }, _push, _parent, _scopeId) => {
																	if (_push) _push(`${ssrInterpolate(item.label.description)}`);
																	else return [createTextVNode(toDisplayString(item.label.description), 1)];
																}),
																_: 2
															}, _parent, _scopeId));
															else return [createVNode(_component_UCheckboxGroup, {
																indicator: "start",
																variant: "table",
																modelValue: unref(userAdminForm).forced_actions,
																"onUpdate:modelValue": ($event) => unref(userAdminForm).forced_actions = $event,
																items: __props.availableForcedActions,
																"value-key": "value"
															}, {
																label: withCtx(({ item }) => [createTextVNode(toDisplayString(item.label.title), 1)]),
																description: withCtx(({ item }) => [createTextVNode(toDisplayString(item.label.description), 1)]),
																_: 2
															}, 1032, [
																"modelValue",
																"onUpdate:modelValue",
																"items"
															])];
														}),
														_: 2
													}, _parent, _scopeId));
													_push(ssrRenderComponent(_component_UFormField, {
														label: "Roles",
														class: "w-full"
													}, {
														default: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) _push(ssrRenderComponent(_component_USelectMenu, {
																modelValue: unref(userAdminForm).roles,
																"onUpdate:modelValue": ($event) => unref(userAdminForm).roles = $event,
																"filter-fields": ["label", "project"],
																items: roles.value,
																loading: rolesLoading.value,
																multiple: "",
																class: "w-full"
															}, {
																"item-label": withCtx(({ item }, _push, _parent, _scopeId) => {
																	if (_push) _push(`${ssrInterpolate(item.label)} <span class="text-muted"${_scopeId}> - ${ssrInterpolate(item.project)}</span>`);
																	else return [createTextVNode(toDisplayString(item.label) + " ", 1), createVNode("span", { class: "text-muted" }, " - " + toDisplayString(item.project), 1)];
																}),
																_: 2
															}, _parent, _scopeId));
															else return [createVNode(_component_USelectMenu, {
																modelValue: unref(userAdminForm).roles,
																"onUpdate:modelValue": ($event) => unref(userAdminForm).roles = $event,
																"filter-fields": ["label", "project"],
																items: roles.value,
																loading: rolesLoading.value,
																multiple: "",
																class: "w-full"
															}, {
																"item-label": withCtx(({ item }) => [createTextVNode(toDisplayString(item.label) + " ", 1), createVNode("span", { class: "text-muted" }, " - " + toDisplayString(item.project), 1)]),
																_: 2
															}, 1032, [
																"modelValue",
																"onUpdate:modelValue",
																"items",
																"loading"
															])];
														}),
														_: 2
													}, _parent, _scopeId));
													_push(`</div>`);
												} else return [createVNode("div", { class: "flex gap-4 justify-center" }, [createVNode(_component_UFormField, { label: props.__.resources.users.fields.forced_actions }, {
													default: withCtx(() => [createVNode(_component_UCheckboxGroup, {
														indicator: "start",
														variant: "table",
														modelValue: unref(userAdminForm).forced_actions,
														"onUpdate:modelValue": ($event) => unref(userAdminForm).forced_actions = $event,
														items: __props.availableForcedActions,
														"value-key": "value"
													}, {
														label: withCtx(({ item }) => [createTextVNode(toDisplayString(item.label.title), 1)]),
														description: withCtx(({ item }) => [createTextVNode(toDisplayString(item.label.description), 1)]),
														_: 2
													}, 1032, [
														"modelValue",
														"onUpdate:modelValue",
														"items"
													])]),
													_: 2
												}, 1032, ["label"]), createVNode(_component_UFormField, {
													label: "Roles",
													class: "w-full"
												}, {
													default: withCtx(() => [createVNode(_component_USelectMenu, {
														modelValue: unref(userAdminForm).roles,
														"onUpdate:modelValue": ($event) => unref(userAdminForm).roles = $event,
														"filter-fields": ["label", "project"],
														items: roles.value,
														loading: rolesLoading.value,
														multiple: "",
														class: "w-full"
													}, {
														"item-label": withCtx(({ item }) => [createTextVNode(toDisplayString(item.label) + " ", 1), createVNode("span", { class: "text-muted" }, " - " + toDisplayString(item.project), 1)]),
														_: 2
													}, 1032, [
														"modelValue",
														"onUpdate:modelValue",
														"items",
														"loading"
													])]),
													_: 2
												}, 1024)])];
											}),
											_: 2
										}, _parent, _scopeId));
										else return [createVNode(_component_UAccordion, {
											modelValue: userAdminAccordionActive.value,
											"onUpdate:modelValue": ($event) => userAdminAccordionActive.value = $event,
											items: userAdminAccordionItems,
											ui: {
												trigger: "cursor-pointer rounded-md hover:bg-white/10 px-4 text-white data-[state=open]:text-primary-light data-[state=open]:bg-white/5",
												content: "py-4"
											}
										}, {
											default: withCtx(({ item }) => [createVNode("h3", { class: "text-lg" }, toDisplayString(item.label), 1)]),
											access: withCtx(({ item }) => [
												createVNode("div", { class: "flex gap-4" }, [createVNode(_component_UFormField, {
													label: props.__.resources.users.fields.suspended_until,
													help: props.__.forms.users.help.suspended_until,
													class: "w-1/2"
												}, {
													default: withCtx(() => [createVNode(FgInputDatePicker_default, {
														modelValue: unref(userAdminForm).suspended_until,
														"onUpdate:modelValue": ($event) => unref(userAdminForm).suspended_until = $event,
														__: props.__.global.forms
													}, null, 8, [
														"modelValue",
														"onUpdate:modelValue",
														"__"
													])]),
													_: 1
												}, 8, ["label", "help"]), createVNode(_component_UFormField, {
													label: props.__.resources.users.fields.expire_at,
													help: props.__.forms.users.help.expire_at,
													name: "expire_at",
													class: "flex flex-col items-end [&>*:nth-child(2)]:flex [&>*:nth-child(2)]:flex-col [&>*:nth-child(2)]:items-end w-3/5",
													ui: { help: "text-right" }
												}, {
													default: withCtx(() => [createVNode(FgInputDatePicker_default, {
														modelValue: unref(userAdminForm).expire_at,
														"onUpdate:modelValue": ($event) => unref(userAdminForm).expire_at = $event,
														ui: { base: "flex justify-end" },
														__: props.__.global.forms
													}, null, 8, [
														"modelValue",
														"onUpdate:modelValue",
														"__"
													])]),
													_: 1
												}, 8, ["label", "help"])]),
												createVNode(_component_USeparator, { class: "my-3" }),
												createVNode("div", { class: "flex gap-4" }, [createVNode(_component_UFormField, {
													label: props.__.resources.users.fields.suspension_reason,
													help: props.__.forms.users.help.suspension_reason,
													class: "w-1/2"
												}, {
													default: withCtx(() => [createVNode(_component_UTextarea, {
														modelValue: unref(userAdminForm).suspension_reason,
														"onUpdate:modelValue": ($event) => unref(userAdminForm).suspension_reason = $event,
														placeholder: " ",
														class: "w-full",
														ui: { base: "peer max-h-37.5 min-h-15" }
													}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
													_: 1
												}, 8, ["label", "help"]), createVNode(_component_UFormField, {
													label: props.__.resources.users.fields.active,
													name: "active",
													class: "flex flex-col gap-2 w-2/5",
													help: props.__.forms.users.help.active
												}, {
													default: withCtx(() => [createVNode(_component_USwitch, {
														modelValue: unref(userAdminForm).active,
														"onUpdate:modelValue": ($event) => unref(userAdminForm).active = $event,
														color: "success",
														disabled: isAccountExpired.value
													}, null, 8, [
														"modelValue",
														"onUpdate:modelValue",
														"disabled"
													])]),
													_: 1
												}, 8, ["label", "help"])])
											]),
											notes: withCtx(({ item }) => [createVNode(_component_UFormField, {
												label: props.__.resources.users.fields.admin_notes,
												help: props.__.forms.users.help.admin_notes,
												name: "admin_notes"
											}, {
												default: withCtx(() => [createVNode(_component_UTextarea, {
													modelValue: unref(userAdminForm).admin_notes,
													"onUpdate:modelValue": ($event) => unref(userAdminForm).admin_notes = $event,
													placeholder: " ",
													ui: { base: "peer max-h-37.5 min-h-15" },
													class: "w-full"
												}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
												_: 1
											}, 8, ["label", "help"])]),
											constraints: withCtx(({ item }) => [createVNode("div", { class: "flex gap-4 justify-center" }, [createVNode(_component_UFormField, { label: props.__.resources.users.fields.forced_actions }, {
												default: withCtx(() => [createVNode(_component_UCheckboxGroup, {
													indicator: "start",
													variant: "table",
													modelValue: unref(userAdminForm).forced_actions,
													"onUpdate:modelValue": ($event) => unref(userAdminForm).forced_actions = $event,
													items: __props.availableForcedActions,
													"value-key": "value"
												}, {
													label: withCtx(({ item }) => [createTextVNode(toDisplayString(item.label.title), 1)]),
													description: withCtx(({ item }) => [createTextVNode(toDisplayString(item.label.description), 1)]),
													_: 2
												}, 1032, [
													"modelValue",
													"onUpdate:modelValue",
													"items"
												])]),
												_: 2
											}, 1032, ["label"]), createVNode(_component_UFormField, {
												label: "Roles",
												class: "w-full"
											}, {
												default: withCtx(() => [createVNode(_component_USelectMenu, {
													modelValue: unref(userAdminForm).roles,
													"onUpdate:modelValue": ($event) => unref(userAdminForm).roles = $event,
													"filter-fields": ["label", "project"],
													items: roles.value,
													loading: rolesLoading.value,
													multiple: "",
													class: "w-full"
												}, {
													"item-label": withCtx(({ item }) => [createTextVNode(toDisplayString(item.label) + " ", 1), createVNode("span", { class: "text-muted" }, " - " + toDisplayString(item.project), 1)]),
													_: 2
												}, 1032, [
													"modelValue",
													"onUpdate:modelValue",
													"items",
													"loading"
												])]),
												_: 2
											}, 1024)])]),
											_: 2
										}, 1032, ["modelValue", "onUpdate:modelValue"])];
									}),
									_: 2
								}, _parent, _scopeId));
							} else return [
								createVNode("p", { class: "text-muted" }, toDisplayString(item.description), 1),
								createVNode("hr", { class: "text-white/30 mt-2" }),
								createVNode(_component_UForm, {
									state: unref(userAdminForm),
									class: "flex flex-col gap-4 mt-5",
									onSubmit: submitUserModal
								}, {
									default: withCtx(() => [createVNode(_component_UAccordion, {
										modelValue: userAdminAccordionActive.value,
										"onUpdate:modelValue": ($event) => userAdminAccordionActive.value = $event,
										items: userAdminAccordionItems,
										ui: {
											trigger: "cursor-pointer rounded-md hover:bg-white/10 px-4 text-white data-[state=open]:text-primary-light data-[state=open]:bg-white/5",
											content: "py-4"
										}
									}, {
										default: withCtx(({ item }) => [createVNode("h3", { class: "text-lg" }, toDisplayString(item.label), 1)]),
										access: withCtx(({ item }) => [
											createVNode("div", { class: "flex gap-4" }, [createVNode(_component_UFormField, {
												label: props.__.resources.users.fields.suspended_until,
												help: props.__.forms.users.help.suspended_until,
												class: "w-1/2"
											}, {
												default: withCtx(() => [createVNode(FgInputDatePicker_default, {
													modelValue: unref(userAdminForm).suspended_until,
													"onUpdate:modelValue": ($event) => unref(userAdminForm).suspended_until = $event,
													__: props.__.global.forms
												}, null, 8, [
													"modelValue",
													"onUpdate:modelValue",
													"__"
												])]),
												_: 1
											}, 8, ["label", "help"]), createVNode(_component_UFormField, {
												label: props.__.resources.users.fields.expire_at,
												help: props.__.forms.users.help.expire_at,
												name: "expire_at",
												class: "flex flex-col items-end [&>*:nth-child(2)]:flex [&>*:nth-child(2)]:flex-col [&>*:nth-child(2)]:items-end w-3/5",
												ui: { help: "text-right" }
											}, {
												default: withCtx(() => [createVNode(FgInputDatePicker_default, {
													modelValue: unref(userAdminForm).expire_at,
													"onUpdate:modelValue": ($event) => unref(userAdminForm).expire_at = $event,
													ui: { base: "flex justify-end" },
													__: props.__.global.forms
												}, null, 8, [
													"modelValue",
													"onUpdate:modelValue",
													"__"
												])]),
												_: 1
											}, 8, ["label", "help"])]),
											createVNode(_component_USeparator, { class: "my-3" }),
											createVNode("div", { class: "flex gap-4" }, [createVNode(_component_UFormField, {
												label: props.__.resources.users.fields.suspension_reason,
												help: props.__.forms.users.help.suspension_reason,
												class: "w-1/2"
											}, {
												default: withCtx(() => [createVNode(_component_UTextarea, {
													modelValue: unref(userAdminForm).suspension_reason,
													"onUpdate:modelValue": ($event) => unref(userAdminForm).suspension_reason = $event,
													placeholder: " ",
													class: "w-full",
													ui: { base: "peer max-h-37.5 min-h-15" }
												}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
												_: 1
											}, 8, ["label", "help"]), createVNode(_component_UFormField, {
												label: props.__.resources.users.fields.active,
												name: "active",
												class: "flex flex-col gap-2 w-2/5",
												help: props.__.forms.users.help.active
											}, {
												default: withCtx(() => [createVNode(_component_USwitch, {
													modelValue: unref(userAdminForm).active,
													"onUpdate:modelValue": ($event) => unref(userAdminForm).active = $event,
													color: "success",
													disabled: isAccountExpired.value
												}, null, 8, [
													"modelValue",
													"onUpdate:modelValue",
													"disabled"
												])]),
												_: 1
											}, 8, ["label", "help"])])
										]),
										notes: withCtx(({ item }) => [createVNode(_component_UFormField, {
											label: props.__.resources.users.fields.admin_notes,
											help: props.__.forms.users.help.admin_notes,
											name: "admin_notes"
										}, {
											default: withCtx(() => [createVNode(_component_UTextarea, {
												modelValue: unref(userAdminForm).admin_notes,
												"onUpdate:modelValue": ($event) => unref(userAdminForm).admin_notes = $event,
												placeholder: " ",
												ui: { base: "peer max-h-37.5 min-h-15" },
												class: "w-full"
											}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
											_: 1
										}, 8, ["label", "help"])]),
										constraints: withCtx(({ item }) => [createVNode("div", { class: "flex gap-4 justify-center" }, [createVNode(_component_UFormField, { label: props.__.resources.users.fields.forced_actions }, {
											default: withCtx(() => [createVNode(_component_UCheckboxGroup, {
												indicator: "start",
												variant: "table",
												modelValue: unref(userAdminForm).forced_actions,
												"onUpdate:modelValue": ($event) => unref(userAdminForm).forced_actions = $event,
												items: __props.availableForcedActions,
												"value-key": "value"
											}, {
												label: withCtx(({ item }) => [createTextVNode(toDisplayString(item.label.title), 1)]),
												description: withCtx(({ item }) => [createTextVNode(toDisplayString(item.label.description), 1)]),
												_: 2
											}, 1032, [
												"modelValue",
												"onUpdate:modelValue",
												"items"
											])]),
											_: 2
										}, 1032, ["label"]), createVNode(_component_UFormField, {
											label: "Roles",
											class: "w-full"
										}, {
											default: withCtx(() => [createVNode(_component_USelectMenu, {
												modelValue: unref(userAdminForm).roles,
												"onUpdate:modelValue": ($event) => unref(userAdminForm).roles = $event,
												"filter-fields": ["label", "project"],
												items: roles.value,
												loading: rolesLoading.value,
												multiple: "",
												class: "w-full"
											}, {
												"item-label": withCtx(({ item }) => [createTextVNode(toDisplayString(item.label) + " ", 1), createVNode("span", { class: "text-muted" }, " - " + toDisplayString(item.project), 1)]),
												_: 2
											}, 1032, [
												"modelValue",
												"onUpdate:modelValue",
												"items",
												"loading"
											])]),
											_: 2
										}, 1024)])]),
										_: 2
									}, 1032, ["modelValue", "onUpdate:modelValue"])]),
									_: 2
								}, 1032, ["state"])
							];
						}),
						_: 1
					}, _parent, _scopeId));
					else return [createVNode(_component_UTabs, {
						modelValue: activeUserModalTab.value,
						"onUpdate:modelValue": ($event) => activeUserModalTab.value = $event,
						items: tabsItems.value,
						ui: {
							trigger: "cursor-pointer",
							label: "overflow-visible"
						}
					}, {
						default: withCtx(({ item }) => [createVNode(_component_UChip, {
							color: "warning",
							show: item.dirty,
							ui: { base: "ring-0 border-1" }
						}, {
							default: withCtx(() => [createVNode("span", { class: "px-1" }, toDisplayString(item.label), 1)]),
							_: 2
						}, 1032, ["show"])]),
						data: withCtx(({ item }) => [
							createVNode("p", { class: "text-muted" }, toDisplayString(item.description), 1),
							createVNode("hr", { class: "text-white/30 mt-2" }),
							createVNode(_component_UForm, {
								state: unref(userDataForm),
								class: "flex flex-col gap-4 mt-5",
								onSubmit: submitUserModal
							}, {
								default: withCtx(() => [createVNode(_component_UAccordion, {
									modelValue: userDataAccordionActive.value,
									"onUpdate:modelValue": ($event) => userDataAccordionActive.value = $event,
									items: userDataAccordionItems,
									ui: {
										trigger: "cursor-pointer rounded-md hover:bg-white/10 px-4 text-white data-[state=open]:text-primary-light data-[state=open]:bg-white/5",
										content: "py-4"
									}
								}, {
									default: withCtx(({ item }) => [createVNode("h3", { class: "text-lg" }, toDisplayString(item.label), 1)]),
									login: withCtx(({ item }) => [createVNode("div", { class: "flex gap-4 justify-between" }, [createVNode(_component_UFormField, {
										required: "",
										help: props.__.forms.users.help.username,
										name: "username",
										class: "w-full"
									}, {
										label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.username), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "username") ? (openBlock(), createBlock("span", {
											key: 0,
											class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
											title: "Modification non sauvegardée"
										})) : createCommentVNode("", true)])]),
										default: withCtx(() => [createVNode(_component_UInput, {
											modelValue: unref(userDataForm).username,
											"onUpdate:modelValue": ($event) => unref(userDataForm).username = $event,
											placeholder: " ",
											ui: { base: "peer" },
											required: "",
											class: "w-full",
											size: "lg"
										}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
										_: 1
									}, 8, ["help"]), createVNode(_component_UFormField, {
										required: "",
										class: "w-full"
									}, {
										label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.email), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "email") ? (openBlock(), createBlock("span", {
											key: 0,
											class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
											title: "Modification non sauvegardée"
										})) : createCommentVNode("", true)])]),
										default: withCtx(() => [createVNode(_component_UInput, {
											modelValue: unref(userDataForm).email,
											"onUpdate:modelValue": ($event) => unref(userDataForm).email = $event,
											placeholder: " ",
											ui: { base: "peer" },
											required: "",
											class: "w-full",
											size: "lg"
										}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
										_: 1
									})])]),
									"personal-data": withCtx(({ item }) => [createVNode("div", { class: "grid grid-cols-2 gap-4 py-2" }, [createVNode(_component_UFormField, null, {
										label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.last_name), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "last_name") ? (openBlock(), createBlock("span", {
											key: 0,
											class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
											title: "Modification non sauvegardée"
										})) : createCommentVNode("", true)])]),
										default: withCtx(() => [createVNode(_component_UInput, {
											modelValue: unref(userDataForm).last_name,
											"onUpdate:modelValue": ($event) => unref(userDataForm).last_name = $event,
											placeholder: " ",
											ui: { base: "peer" },
											class: "w-full"
										}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
										_: 1
									}), createVNode(_component_UFormField, null, {
										label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.first_name), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "first_name") ? (openBlock(), createBlock("span", {
											key: 0,
											class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
											title: "Modification non sauvegardée"
										})) : createCommentVNode("", true)])]),
										default: withCtx(() => [createVNode(_component_UInput, {
											modelValue: unref(userDataForm).first_name,
											"onUpdate:modelValue": ($event) => unref(userDataForm).first_name = $event,
											placeholder: " ",
											ui: { base: "peer" },
											class: "w-full"
										}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
										_: 1
									})]), createVNode("div", { class: "grid grid-cols-2 gap-4" }, [createVNode(_component_UFormField, null, {
										label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.job_title), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "job_title") ? (openBlock(), createBlock("span", {
											key: 0,
											class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
											title: "Modification non sauvegardée"
										})) : createCommentVNode("", true)])]),
										default: withCtx(() => [createVNode(_component_UInput, {
											modelValue: unref(userDataForm).job_title,
											"onUpdate:modelValue": ($event) => unref(userDataForm).job_title = $event,
											label: "Poste",
											placeholder: " ",
											ui: { base: "peer" },
											class: "w-full"
										}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
										_: 1
									}), createVNode(_component_UFormField, { class: "w-full" }, {
										label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.phone.label), 1), unref(isFieldDirty)(unref(userDataForm), initialUserDataForm.value, "phone") ? (openBlock(), createBlock("span", {
											key: 0,
											class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
											title: "Modification non sauvegardée"
										})) : createCommentVNode("", true)])]),
										default: withCtx(() => [createVNode(_component_UFieldGroup, { class: "w-full" }, {
											default: withCtx(() => [
												createVNode(_component_UPopover, {
													open: countryOpen.value,
													"onUpdate:open": ($event) => countryOpen.value = $event
												}, {
													content: withCtx(() => [createVNode("div", { class: "w-80 space-y-2 p-2" }, [createVNode(_component_UInput, {
														size: _ctx.size,
														modelValue: countrySearch.value,
														"onUpdate:modelValue": ($event) => countrySearch.value = $event,
														placeholder: "Rechercher un pays ou indicatif...",
														icon: "i-lucide-search",
														autofocus: "",
														class: "w-full"
													}, null, 8, [
														"size",
														"modelValue",
														"onUpdate:modelValue"
													]), createVNode("div", { class: "max-h-72 overflow-y-auto" }, [(openBlock(true), createBlock(Fragment, null, renderList(filteredCountries.value, (country) => {
														return openBlock(), createBlock("button", {
															key: `${country.name}-${country.extension}`,
															type: "button",
															class: "flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm hover:bg-white/10",
															onClick: ($event) => selectCountry(country)
														}, [createVNode("span", { class: "text-lg" }, toDisplayString(country.flag), 1), createVNode("span", { class: "min-w-0 flex-1" }, [createVNode("span", { class: "block truncate text-white" }, toDisplayString(country.name), 1), createVNode("span", { class: "text-xs text-white/50" }, " +" + toDisplayString(country.extension), 1)])], 8, ["onClick"]);
													}), 128)), filteredCountries.value.length === 0 ? (openBlock(), createBlock("p", {
														key: 0,
														class: "px-3 py-4 text-center text-sm text-white/50"
													}, " Aucun résultat ")) : createCommentVNode("", true)])])]),
													default: withCtx(() => [createVNode(unref(UButton), {
														type: "button",
														color: "neutral",
														variant: "outline",
														class: "h-full w-1/4 justify-between rounded-none border-0 bg-transparent px-3 rounded-l-md",
														"trailing-icon": "i-lucide-chevron-down"
													}, {
														default: withCtx(() => [createVNode("span", { class: "flex items-center gap-2" }, [selectedCountry.value ? (openBlock(), createBlock("span", { key: 0 }, toDisplayString(selectedCountry.value.flag), 1)) : createCommentVNode("", true), createVNode("span", null, toDisplayString(unref(userDataForm).phone.extension ? `+${unref(userDataForm).phone.extension}` : "+..."), 1)])]),
														_: 1
													})]),
													_: 1
												}, 8, ["open", "onUpdate:open"]),
												createVNode("div", { class: "w-px bg-white/10" }),
												withDirectives(createVNode(_component_UInput, {
													"model-value": unref(userDataForm).phone.number,
													variant: "outline",
													type: "tel",
													inputmode: "tel",
													class: "min-w-0 flex-1 h-full",
													placeholder: props.__.resources.users.fields.phone.label,
													ui: { base: "rounded-none border-0 bg-transparent h-full rounded-r-md" },
													"onUpdate:modelValue": updateNumber
												}, null, 8, ["model-value", "placeholder"]), [[unref(vMaska), "##########"]])
											]),
											_: 1
										})]),
										_: 1
									})])]),
									settings: withCtx(() => [createVNode("div", { class: "flex gap-4 items-center justify-center w-full" }, [
										createVNode(_component_UFormField, { class: "w-full" }, {
											label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.preferred_locale), 1), unref(isFieldDirty)(unref(userDataForm), initialUserSettingsForm.value, "preferred_locale") ? (openBlock(), createBlock("span", {
												key: 0,
												class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
												title: "Modification non sauvegardée"
											})) : createCommentVNode("", true)])]),
											default: withCtx(() => [createVNode(_component_USelectMenu, {
												modelValue: unref(userSettingsForm).preferred_locale,
												"onUpdate:modelValue": ($event) => unref(userSettingsForm).preferred_locale = $event,
												items: __props.availableLocales,
												"value-key": "value",
												class: "w-full"
											}, {
												default: withCtx(({ modelValue }) => [createTextVNode(toDisplayString(__props.availableLocales.filter((locale) => locale.value === modelValue)[0]?.icon || "") + " " + toDisplayString(__props.availableLocales.filter((locale) => locale.value === modelValue)[0]?.label || ""), 1)]),
												item: withCtx(({ item }) => [createTextVNode(toDisplayString(item.icon) + " " + toDisplayString(item.label), 1)]),
												_: 2
											}, 1032, [
												"modelValue",
												"onUpdate:modelValue",
												"items"
											])]),
											_: 2
										}, 1024),
										createVNode(_component_UFormField, { class: "w-full" }, {
											label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.preferred_timezone), 1), unref(isFieldDirty)(unref(userDataForm), initialUserSettingsForm.value, "preferred_timezone") ? (openBlock(), createBlock("span", {
												key: 0,
												class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
												title: "Modification non sauvegardée"
											})) : createCommentVNode("", true)])]),
											default: withCtx(() => [createVNode(_component_USelectMenu, {
												modelValue: unref(userSettingsForm).preferred_timezone,
												"onUpdate:modelValue": ($event) => unref(userSettingsForm).preferred_timezone = $event,
												items: __props.availableTimezones,
												"value-key": "value",
												class: "w-full"
											}, null, 8, [
												"modelValue",
												"onUpdate:modelValue",
												"items"
											])]),
											_: 1
										}),
										createVNode(_component_UFormField, { class: "w-full" }, {
											label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.preferred_start_page), 1), unref(isFieldDirty)(unref(userDataForm), initialUserSettingsForm.value, "preferred_start_page") ? (openBlock(), createBlock("span", {
												key: 0,
												class: "inline-block size-1.5 shrink-0 rounded-full bg-warning",
												title: "Modification non sauvegardée"
											})) : createCommentVNode("", true)])]),
											default: withCtx(() => [createVNode(_component_USelect, {
												modelValue: unref(userSettingsForm).preferred_start_page,
												"onUpdate:modelValue": ($event) => unref(userSettingsForm).preferred_start_page = $event,
												items: __props.availableStartPages,
												class: "w-full"
											}, null, 8, [
												"modelValue",
												"onUpdate:modelValue",
												"items"
											])]),
											_: 1
										})
									])]),
									_: 2
								}, 1032, ["modelValue", "onUpdate:modelValue"])]),
								_: 2
							}, 1032, ["state"])
						]),
						security: withCtx(({ item }) => [
							createVNode("p", { class: "text-muted" }, toDisplayString(item.description), 1),
							createVNode("hr", { class: "text-white/30 mt-2" }),
							createVNode(_component_UForm, {
								state: unref(userSecurityForm),
								class: "flex flex-col gap-4 mt-5",
								onSubmit: submitUserModal
							}, {
								default: withCtx(() => [createVNode("div", null, [
									createVNode(_component_UFormField, {
										label: props.__.resources.users.fields.password,
										required: ""
									}, {
										default: withCtx(() => [createVNode(_component_UInput, {
											modelValue: unref(userSecurityForm).new_password,
											"onUpdate:modelValue": ($event) => unref(userSecurityForm).new_password = $event,
											color: colorPassword.value,
											type: showPassword.value ? "text" : "password",
											"aria-invalid": score.value < 4,
											"aria-describedby": "password-strength",
											ui: { trailing: "pe-1" },
											class: "w-full"
										}, {
											trailing: withCtx(() => [createVNode(unref(UButton), {
												color: "neutral",
												variant: "link",
												size: "sm",
												icon: showPassword.value ? "i-lucide-eye-off" : "i-lucide-eye",
												"aria-label": showPassword.value ? "Hide password" : "Show password",
												"aria-pressed": showPassword.value,
												"aria-controls": "password",
												onClick: ($event) => showPassword.value = !showPassword.value
											}, null, 8, [
												"icon",
												"aria-label",
												"aria-pressed",
												"onClick"
											])]),
											_: 1
										}, 8, [
											"modelValue",
											"onUpdate:modelValue",
											"color",
											"type",
											"aria-invalid"
										])]),
										_: 1
									}, 8, ["label"]),
									createVNode(_component_UProgress, {
										color: colorPassword.value,
										indicator: validatorText.value,
										"model-value": score.value,
										max: 5,
										size: "sm",
										class: "mt-1"
									}, null, 8, [
										"color",
										"indicator",
										"model-value"
									]),
									createVNode("p", {
										id: "password-strength",
										class: "text-sm font-medium"
									}, [createTextVNode(toDisplayString(validatorText.value) + ". ", 1), score.value < 5 ? (openBlock(), createBlock("span", { key: 0 }, "Must contain:")) : createCommentVNode("", true)]),
									createVNode("ul", {
										class: "space-y-1",
										"aria-label": "Password requirements"
									}, [(openBlock(true), createBlock(Fragment, null, renderList(strengthPassword.value, (req, index) => {
										return openBlock(), createBlock("li", {
											key: index,
											class: ["flex items-center gap-0.5", req.met ? "text-success" : "text-muted"]
										}, [createVNode(_component_UIcon, {
											name: req.met ? "i-lucide-circle-check" : "i-lucide-circle-x",
											class: "size-4 shrink-0"
										}, null, 8, ["name"]), createVNode("span", { class: "text-sm font-light" }, [createTextVNode(toDisplayString(req.text) + " ", 1), createVNode("span", { class: "sr-only" }, toDisplayString(req.met ? " - Requirement met : " : " - Requirement not met"), 1)])], 2);
									}), 128))])
								]), score.value >= 5 ? (openBlock(), createBlock("div", { key: 0 }, [createVNode(_component_UFormField, { label: props.__.resources.users.fields.password_confirmation }, {
									default: withCtx(() => [createVNode(_component_UInput, {
										modelValue: unref(userSecurityForm).confirm_password,
										"onUpdate:modelValue": ($event) => unref(userSecurityForm).confirm_password = $event,
										color: colorConfirmation.value,
										type: showConfirmation.value ? "text" : "password",
										"aria-invalid": score.value < 4,
										"aria-describedby": "password-strength",
										ui: { trailing: "pe-1" },
										class: "w-full"
									}, {
										trailing: withCtx(() => [createVNode(unref(UButton), {
											color: "neutral",
											variant: "link",
											size: "sm",
											icon: showConfirmation.value ? "i-lucide-eye-off" : "i-lucide-eye",
											"aria-label": showConfirmation.value ? "Hide password" : "Show password",
											"aria-pressed": showConfirmation.value,
											"aria-controls": "password",
											onClick: ($event) => showConfirmation.value = !showConfirmation.value
										}, null, 8, [
											"icon",
											"aria-label",
											"aria-pressed",
											"onClick"
										])]),
										_: 1
									}, 8, [
										"modelValue",
										"onUpdate:modelValue",
										"color",
										"type",
										"aria-invalid"
									]), unref(userSecurityForm).confirm_password !== "" ? (openBlock(), createBlock("p", {
										key: 0,
										id: "password-confirmation",
										class: ["text-sm font-medium", samePassword.value ? "text-success/80" : "text-danger/80"]
									}, toDisplayString(confirmationText.value), 3)) : createCommentVNode("", true)]),
									_: 1
								}, 8, ["label"])])) : createCommentVNode("", true)]),
								_: 1
							}, 8, ["state"])
						]),
						admin: withCtx(({ item }) => [
							createVNode("p", { class: "text-muted" }, toDisplayString(item.description), 1),
							createVNode("hr", { class: "text-white/30 mt-2" }),
							createVNode(_component_UForm, {
								state: unref(userAdminForm),
								class: "flex flex-col gap-4 mt-5",
								onSubmit: submitUserModal
							}, {
								default: withCtx(() => [createVNode(_component_UAccordion, {
									modelValue: userAdminAccordionActive.value,
									"onUpdate:modelValue": ($event) => userAdminAccordionActive.value = $event,
									items: userAdminAccordionItems,
									ui: {
										trigger: "cursor-pointer rounded-md hover:bg-white/10 px-4 text-white data-[state=open]:text-primary-light data-[state=open]:bg-white/5",
										content: "py-4"
									}
								}, {
									default: withCtx(({ item }) => [createVNode("h3", { class: "text-lg" }, toDisplayString(item.label), 1)]),
									access: withCtx(({ item }) => [
										createVNode("div", { class: "flex gap-4" }, [createVNode(_component_UFormField, {
											label: props.__.resources.users.fields.suspended_until,
											help: props.__.forms.users.help.suspended_until,
											class: "w-1/2"
										}, {
											default: withCtx(() => [createVNode(FgInputDatePicker_default, {
												modelValue: unref(userAdminForm).suspended_until,
												"onUpdate:modelValue": ($event) => unref(userAdminForm).suspended_until = $event,
												__: props.__.global.forms
											}, null, 8, [
												"modelValue",
												"onUpdate:modelValue",
												"__"
											])]),
											_: 1
										}, 8, ["label", "help"]), createVNode(_component_UFormField, {
											label: props.__.resources.users.fields.expire_at,
											help: props.__.forms.users.help.expire_at,
											name: "expire_at",
											class: "flex flex-col items-end [&>*:nth-child(2)]:flex [&>*:nth-child(2)]:flex-col [&>*:nth-child(2)]:items-end w-3/5",
											ui: { help: "text-right" }
										}, {
											default: withCtx(() => [createVNode(FgInputDatePicker_default, {
												modelValue: unref(userAdminForm).expire_at,
												"onUpdate:modelValue": ($event) => unref(userAdminForm).expire_at = $event,
												ui: { base: "flex justify-end" },
												__: props.__.global.forms
											}, null, 8, [
												"modelValue",
												"onUpdate:modelValue",
												"__"
											])]),
											_: 1
										}, 8, ["label", "help"])]),
										createVNode(_component_USeparator, { class: "my-3" }),
										createVNode("div", { class: "flex gap-4" }, [createVNode(_component_UFormField, {
											label: props.__.resources.users.fields.suspension_reason,
											help: props.__.forms.users.help.suspension_reason,
											class: "w-1/2"
										}, {
											default: withCtx(() => [createVNode(_component_UTextarea, {
												modelValue: unref(userAdminForm).suspension_reason,
												"onUpdate:modelValue": ($event) => unref(userAdminForm).suspension_reason = $event,
												placeholder: " ",
												class: "w-full",
												ui: { base: "peer max-h-37.5 min-h-15" }
											}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
											_: 1
										}, 8, ["label", "help"]), createVNode(_component_UFormField, {
											label: props.__.resources.users.fields.active,
											name: "active",
											class: "flex flex-col gap-2 w-2/5",
											help: props.__.forms.users.help.active
										}, {
											default: withCtx(() => [createVNode(_component_USwitch, {
												modelValue: unref(userAdminForm).active,
												"onUpdate:modelValue": ($event) => unref(userAdminForm).active = $event,
												color: "success",
												disabled: isAccountExpired.value
											}, null, 8, [
												"modelValue",
												"onUpdate:modelValue",
												"disabled"
											])]),
											_: 1
										}, 8, ["label", "help"])])
									]),
									notes: withCtx(({ item }) => [createVNode(_component_UFormField, {
										label: props.__.resources.users.fields.admin_notes,
										help: props.__.forms.users.help.admin_notes,
										name: "admin_notes"
									}, {
										default: withCtx(() => [createVNode(_component_UTextarea, {
											modelValue: unref(userAdminForm).admin_notes,
											"onUpdate:modelValue": ($event) => unref(userAdminForm).admin_notes = $event,
											placeholder: " ",
											ui: { base: "peer max-h-37.5 min-h-15" },
											class: "w-full"
										}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
										_: 1
									}, 8, ["label", "help"])]),
									constraints: withCtx(({ item }) => [createVNode("div", { class: "flex gap-4 justify-center" }, [createVNode(_component_UFormField, { label: props.__.resources.users.fields.forced_actions }, {
										default: withCtx(() => [createVNode(_component_UCheckboxGroup, {
											indicator: "start",
											variant: "table",
											modelValue: unref(userAdminForm).forced_actions,
											"onUpdate:modelValue": ($event) => unref(userAdminForm).forced_actions = $event,
											items: __props.availableForcedActions,
											"value-key": "value"
										}, {
											label: withCtx(({ item }) => [createTextVNode(toDisplayString(item.label.title), 1)]),
											description: withCtx(({ item }) => [createTextVNode(toDisplayString(item.label.description), 1)]),
											_: 2
										}, 1032, [
											"modelValue",
											"onUpdate:modelValue",
											"items"
										])]),
										_: 2
									}, 1032, ["label"]), createVNode(_component_UFormField, {
										label: "Roles",
										class: "w-full"
									}, {
										default: withCtx(() => [createVNode(_component_USelectMenu, {
											modelValue: unref(userAdminForm).roles,
											"onUpdate:modelValue": ($event) => unref(userAdminForm).roles = $event,
											"filter-fields": ["label", "project"],
											items: roles.value,
											loading: rolesLoading.value,
											multiple: "",
											class: "w-full"
										}, {
											"item-label": withCtx(({ item }) => [createTextVNode(toDisplayString(item.label) + " ", 1), createVNode("span", { class: "text-muted" }, " - " + toDisplayString(item.project), 1)]),
											_: 2
										}, 1032, [
											"modelValue",
											"onUpdate:modelValue",
											"items",
											"loading"
										])]),
										_: 2
									}, 1024)])]),
									_: 2
								}, 1032, ["modelValue", "onUpdate:modelValue"])]),
								_: 2
							}, 1032, ["state"])
						]),
						_: 1
					}, 8, [
						"modelValue",
						"onUpdate:modelValue",
						"items"
					])];
				}),
				footer: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex justify-end gap-2 w-full"${_scopeId}>`);
						_push(ssrRenderComponent(unref(UButton), {
							color: "neutral",
							variant: "ghost",
							label: props.__.actions.close,
							type: "button",
							onClick: requestCloseUserModal,
							class: "cursor-pointer"
						}, null, _parent, _scopeId));
						_push(ssrRenderComponent(unref(UButton), {
							type: "submit",
							disabled: currentTabProcessing.value || !isCurrentTabValid.value,
							loading: currentTabProcessing.value,
							label: isEditModal.value ? "Enregistrer" : props.__.actions.save,
							onClick: submitUserModal,
							class: "cursor-pointer"
						}, null, _parent, _scopeId));
						_push(`</div>`);
					} else return [createVNode("div", { class: "flex justify-end gap-2 w-full" }, [createVNode(unref(UButton), {
						color: "neutral",
						variant: "ghost",
						label: props.__.actions.close,
						type: "button",
						onClick: requestCloseUserModal,
						class: "cursor-pointer"
					}, null, 8, ["label"]), createVNode(unref(UButton), {
						type: "submit",
						disabled: currentTabProcessing.value || !isCurrentTabValid.value,
						loading: currentTabProcessing.value,
						label: isEditModal.value ? "Enregistrer" : props.__.actions.save,
						onClick: submitUserModal,
						class: "cursor-pointer"
					}, null, 8, [
						"disabled",
						"loading",
						"label"
					])])];
				}),
				_: 1
			}, _parent));
			_push(ssrRenderComponent(FgConfirmIdentityModal_default, {
				open: identityModalOpen.value,
				"onUpdate:open": ($event) => identityModalOpen.value = $event,
				methods: unref(page).props.auth?.user?.two_factor_methods ?? [],
				"selected-method": unref(page).props.auth?.user?.primary_second_factor ?? null,
				"masked-email": unref(page).props.auth?.user?.masked_email ?? "",
				config: identityModalConfig.value,
				onConfirmed: runPendingSensitiveAction
			}, null, _parent));
			_push(ssrRenderComponent(UserDrawerShell_default, {
				open: quickDrawerOpen.value,
				"onUpdate:open": [($event) => quickDrawerOpen.value = $event, handleQuickDrawerOpenChange],
				dismissible: !quickDrawerDirty.value,
				title: quickDrawerTitle.value,
				description: quickDrawerDescription.value,
				"close-label": props.__.actions.close,
				"submit-label": props.__.actions.save,
				loading: quickDrawerProcessing.value,
				disabled: quickDrawerDisabled.value,
				onClose: ($event) => handleQuickDrawerOpenChange(false),
				onSubmit: submitQuickDrawer
			}, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(ssrRenderComponent(UserQuickCreateForm_default, {
							style: quickDrawerMode.value === "create" ? null : { display: "none" },
							ref_key: "quickCreateFormRef",
							ref: quickCreateFormRef,
							__: props.__,
							countries: __props.phoneCountries,
							roles: roles.value,
							"roles-loading": rolesLoading.value,
							onCreated: handleQuickDrawerSaved
						}, null, _parent, _scopeId));
						_push(ssrRenderComponent(UserQuickEditForm_default, {
							style: quickDrawerMode.value === "edit" ? null : { display: "none" },
							ref_key: "quickEditFormRef",
							ref: quickEditFormRef,
							__: props.__,
							user: selectedQuickUser.value,
							countries: __props.phoneCountries,
							onSaved: handleQuickDrawerSaved
						}, null, _parent, _scopeId));
					} else return [withDirectives(createVNode(UserQuickCreateForm_default, {
						ref_key: "quickCreateFormRef",
						ref: quickCreateFormRef,
						__: props.__,
						countries: __props.phoneCountries,
						roles: roles.value,
						"roles-loading": rolesLoading.value,
						onCreated: handleQuickDrawerSaved
					}, null, 8, [
						"__",
						"countries",
						"roles",
						"roles-loading"
					]), [[vShow, quickDrawerMode.value === "create"]]), withDirectives(createVNode(UserQuickEditForm_default, {
						ref_key: "quickEditFormRef",
						ref: quickEditFormRef,
						__: props.__,
						user: selectedQuickUser.value,
						countries: __props.phoneCountries,
						onSaved: handleQuickDrawerSaved
					}, null, 8, [
						"__",
						"user",
						"countries"
					]), [[vShow, quickDrawerMode.value === "edit"]])];
				}),
				_: 1
			}, _parent));
			_push(ssrRenderComponent(FgDiscardChangesModal_default, {
				open: discardModalOpen.value,
				"onUpdate:open": ($event) => discardModalOpen.value = $event,
				title: discardModalConfig.value.title,
				description: discardModalConfig.value.description,
				"keep-label": discardModalConfig.value.keepLabel,
				"discard-label": discardModalConfig.value.discardLabel,
				"keep-color": discardModalConfig.value.keepColor,
				"discard-color": discardModalConfig.value.discardColor,
				onKeep: handleDiscardKeep,
				onDiscard: handleDiscardDiscard
			}, null, _parent));
			if (detailsUserId.value !== null) _push(ssrRenderComponent(UserDetailsDrawer_default, {
				open: detailsDrawerOpen.value,
				"onUpdate:open": ($event) => detailsDrawerOpen.value = $event,
				"user-id": detailsUserId.value,
				locale: __props.locale ?? "fr-FR",
				labels: props.__.drawer,
				onClosed: destroyDetailsDrawer
			}, null, _parent));
			else _push(`<!---->`);
			_push(ssrRenderComponent(PrimaryDataTable_default, {
				title: props.__.title,
				description: props.__.description,
				"row-selection": rowSelection.value,
				"onUpdate:rowSelection": ($event) => rowSelection.value = $event,
				selectable: "",
				data: __props.users.data,
				columns: columns.value,
				meta: __props.users.meta,
				filters: __props.filters,
				views: userTableViews.value,
				"view-filter-key": "view",
				loading: usersLoading.value,
				"initial-column-visibility": { id: false },
				"row-menu-items": getRowContextMenuItems,
				labels: {
					search: props.__.actions.search,
					columns: props.__.tables.users.columns,
					selectedWord: props.__.messages.selected,
					connectingWord: props.__.messages.selected_connecting_word,
					unselect: props.__.actions.unselect
				},
				onReload: reloadUsers,
				onRowDoubleClick: openUserDetails,
				onRowActivate: openUserDetails,
				onBulkDelete: handleBulkDelete,
				onBulkForceDelete: handleBulkForceDelete,
				onBulkRestore: handleBulkRestore,
				onActiveRowChange: (row) => {
					_ctx.activeTable = row ? "users" : null;
					_ctx.activeUserRow = row;
				}
			}, {
				"global-actions": withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex gap-4 justif-between w-full"${_scopeId}>`);
						_push(ssrRenderComponent(_component_UFieldGroup, null, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) {
									if (permissions.value.create) _push(ssrRenderComponent(unref(UButton), {
										label: props.__.user_actions.fast_create,
										onClick: openQuickCreate,
										class: "cursor-pointer"
									}, null, _parent, _scopeId));
									else _push(`<!---->`);
									_push(ssrRenderComponent(unref(UDropdownMenu), {
										items: globalActionsDropdownItems,
										ui: { item: "cursor-pointer" }
									}, {
										default: withCtx((_, _push, _parent, _scopeId) => {
											if (_push) _push(ssrRenderComponent(unref(UButton), {
												color: "primary",
												icon: "i-lucide-chevron-down"
											}, null, _parent, _scopeId));
											else return [createVNode(unref(UButton), {
												color: "primary",
												icon: "i-lucide-chevron-down"
											})];
										}),
										_: 1
									}, _parent, _scopeId));
								} else return [permissions.value.create ? (openBlock(), createBlock(unref(UButton), {
									key: 0,
									label: props.__.user_actions.fast_create,
									onClick: openQuickCreate,
									class: "cursor-pointer"
								}, null, 8, ["label"])) : createCommentVNode("", true), createVNode(unref(UDropdownMenu), {
									items: globalActionsDropdownItems,
									ui: { item: "cursor-pointer" }
								}, {
									default: withCtx(() => [createVNode(unref(UButton), {
										color: "primary",
										icon: "i-lucide-chevron-down"
									})]),
									_: 1
								})];
							}),
							_: 1
						}, _parent, _scopeId));
						_push(`</div>`);
					} else return [createVNode("div", { class: "flex gap-4 justif-between w-full" }, [createVNode(_component_UFieldGroup, null, {
						default: withCtx(() => [permissions.value.create ? (openBlock(), createBlock(unref(UButton), {
							key: 0,
							label: props.__.user_actions.fast_create,
							onClick: openQuickCreate,
							class: "cursor-pointer"
						}, null, 8, ["label"])) : createCommentVNode("", true), createVNode(unref(UDropdownMenu), {
							items: globalActionsDropdownItems,
							ui: { item: "cursor-pointer" }
						}, {
							default: withCtx(() => [createVNode(unref(UButton), {
								color: "primary",
								icon: "i-lucide-chevron-down"
							})]),
							_: 1
						})]),
						_: 1
					})])];
				}),
				"username-cell": withCtx(({ row, getValue }, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex flex-col gap-1 items-start justify-center"${_scopeId}><div class="flex gap-2"${_scopeId}>`);
						if (currentUserId.value === row.original.id) _push(ssrRenderComponent(_component_UBadge, {
							label: props.__.tables.users.cell.username.badge,
							color: "success",
							size: "sm",
							class: "rounded-full opacity-80"
						}, null, _parent, _scopeId));
						else _push(`<!---->`);
						_push(`<p class="text-muted"${_scopeId}>${ssrInterpolate(getValue())}</p></div>`);
						if (row.original.first_name || row.original.last_name) _push(`<p class="text-xs text-sandstone"${_scopeId}><span class="text-muted font-bold"${_scopeId}>${ssrInterpolate(props.__.resources.users.fields.last_name)}</span> : ${ssrInterpolate(row.original.last_name)} ${ssrInterpolate(row.original.first_name)}</p>`);
						else _push(`<!---->`);
						if (row.original.job_title) _push(`<p class="text-xs text-sandstone"${_scopeId}><span class="text-muted font-bold"${_scopeId}>${ssrInterpolate(props.__.resources.users.fields.job_title)}</span> : ${ssrInterpolate(row.original.job_title)}</p>`);
						else _push(`<!---->`);
						_push(`</div>`);
					} else return [createVNode("div", { class: "flex flex-col gap-1 items-start justify-center" }, [
						createVNode("div", { class: "flex gap-2" }, [currentUserId.value === row.original.id ? (openBlock(), createBlock(_component_UBadge, {
							key: 0,
							label: props.__.tables.users.cell.username.badge,
							color: "success",
							size: "sm",
							class: "rounded-full opacity-80"
						}, null, 8, ["label"])) : createCommentVNode("", true), createVNode("p", { class: "text-muted" }, toDisplayString(getValue()), 1)]),
						row.original.first_name || row.original.last_name ? (openBlock(), createBlock("p", {
							key: 0,
							class: "text-xs text-sandstone"
						}, [createVNode("span", { class: "text-muted font-bold" }, toDisplayString(props.__.resources.users.fields.last_name), 1), createTextVNode(" : " + toDisplayString(row.original.last_name) + " " + toDisplayString(row.original.first_name), 1)])) : createCommentVNode("", true),
						row.original.job_title ? (openBlock(), createBlock("p", {
							key: 1,
							class: "text-xs text-sandstone"
						}, [createVNode("span", { class: "text-muted font-bold" }, toDisplayString(props.__.resources.users.fields.job_title), 1), createTextVNode(" : " + toDisplayString(row.original.job_title), 1)])) : createCommentVNode("", true)
					])];
				}),
				"email-cell": withCtx(({ row, getValue }, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex flex-col gap-2"${_scopeId}><a class="text-center text-white/50 hover:underline inline-flex gap-1 items-center w-fit"${ssrRenderAttr("href", `mailto:${getValue()}`)}${_scopeId}>`);
						_push(ssrRenderComponent(_component_UIcon, {
							name: "i-lucide-mail",
							class: "size-5"
						}, null, _parent, _scopeId));
						_push(`<p class="align-middle"${_scopeId}>${ssrInterpolate(getValue())}</p></a>`);
						if (row.original.phone?.extension && row.original.phone?.phone) {
							_push(`<a class="text-center text-white/50 hover:underline inline-flex gap-1 items-center w-fit"${ssrRenderAttr("href", `tel:+${row.original.phone.extension}${row.original.phone.phone}`)}${_scopeId}>`);
							_push(ssrRenderComponent(_component_UIcon, {
								name: "i-lucide-phone",
								class: "size-5"
							}, null, _parent, _scopeId));
							_push(`<p class="align-middle"${_scopeId}>+${ssrInterpolate(row.original.phone.extension + row.original.phone.phone)}</p></a>`);
						} else _push(`<!---->`);
						_push(`</div>`);
					} else return [createVNode("div", { class: "flex flex-col gap-2" }, [createVNode("a", {
						class: "text-center text-white/50 hover:underline inline-flex gap-1 items-center w-fit",
						href: `mailto:${getValue()}`
					}, [createVNode(_component_UIcon, {
						name: "i-lucide-mail",
						class: "size-5"
					}), createVNode("p", { class: "align-middle" }, toDisplayString(getValue()), 1)], 8, ["href"]), row.original.phone?.extension && row.original.phone?.phone ? (openBlock(), createBlock("a", {
						key: 0,
						class: "text-center text-white/50 hover:underline inline-flex gap-1 items-center w-fit",
						href: `tel:+${row.original.phone.extension}${row.original.phone.phone}`
					}, [createVNode(_component_UIcon, {
						name: "i-lucide-phone",
						class: "size-5"
					}), createVNode("p", { class: "align-middle" }, "+" + toDisplayString(row.original.phone.extension + row.original.phone.phone), 1)], 8, ["href"])) : createCommentVNode("", true)])];
				}),
				"active-cell": withCtx(({ row, getValue }, _push, _parent, _scopeId) => {
					if (_push) _push(ssrRenderComponent(_component_UBadge, {
						label: getValue() ? props.__.tables.users.cell.active.true : props.__.tables.users.cell.active.false,
						color: getValue() ? "success" : "error",
						size: "sm",
						class: "rounded-full"
					}, null, _parent, _scopeId));
					else return [createVNode(_component_UBadge, {
						label: getValue() ? props.__.tables.users.cell.active.true : props.__.tables.users.cell.active.false,
						color: getValue() ? "success" : "error",
						size: "sm",
						class: "rounded-full"
					}, null, 8, ["label", "color"])];
				}),
				"admin_notes-cell": withCtx(({ row, getValue }, _push, _parent, _scopeId) => {
					if (_push) if (row.original.permissions.view_sensitive && getValue()) {
						_push(`<div class="flex max-w-md items-start justify-between gap-2"${_scopeId}><p class="whitespace-pre-wrap text-sm text-white/70"${_scopeId}>${ssrInterpolate(isAdminNoteExpanded(row.original.id) ? getValue() : unref(truncate)(getValue(), 20))}</p>`);
						if (getValue().length > 20) _push(ssrRenderComponent(unref(UButton), {
							type: "button",
							icon: isAdminNoteExpanded(row.original.id) ? "i-lucide-chevron-up" : "i-lucide-chevron-down",
							size: "xs",
							color: "neutral",
							variant: "ghost",
							class: "shrink-0 cursor-pointer",
							"aria-expanded": isAdminNoteExpanded(row.original.id),
							"aria-label": isAdminNoteExpanded(row.original.id) ? props.__.tables.users.cell.admin_notes.collapse : props.__.tables.users.cell.admin_notes.expand,
							onClick: ($event) => toggleAdminNote(row.original.id),
							onDblclick: () => {}
						}, null, _parent, _scopeId));
						else _push(`<!---->`);
						_push(`</div>`);
					} else _push(`<span class="text-muted"${_scopeId}>—</span>`);
					else return [row.original.permissions.view_sensitive && getValue() ? (openBlock(), createBlock("div", {
						key: 0,
						class: "flex max-w-md items-start justify-between gap-2"
					}, [createVNode("p", { class: "whitespace-pre-wrap text-sm text-white/70" }, toDisplayString(isAdminNoteExpanded(row.original.id) ? getValue() : unref(truncate)(getValue(), 20)), 1), getValue().length > 20 ? (openBlock(), createBlock(unref(UButton), {
						key: 0,
						type: "button",
						icon: isAdminNoteExpanded(row.original.id) ? "i-lucide-chevron-up" : "i-lucide-chevron-down",
						size: "xs",
						color: "neutral",
						variant: "ghost",
						class: "shrink-0 cursor-pointer",
						"aria-expanded": isAdminNoteExpanded(row.original.id),
						"aria-label": isAdminNoteExpanded(row.original.id) ? props.__.tables.users.cell.admin_notes.collapse : props.__.tables.users.cell.admin_notes.expand,
						onClick: withModifiers(($event) => toggleAdminNote(row.original.id), ["stop"]),
						onDblclick: withModifiers(() => {}, ["stop"])
					}, null, 8, [
						"icon",
						"aria-expanded",
						"aria-label",
						"onClick",
						"onDblclick"
					])) : createCommentVNode("", true)])) : (openBlock(), createBlock("span", {
						key: 1,
						class: "text-muted"
					}, "—"))];
				}),
				"actions-cell": withCtx(({ row }, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex flex-col justify-end gap-2"${_scopeId}>`);
						if (row.original.permissions.view) _push(ssrRenderComponent(unref(UButton), {
							type: "button",
							icon: "i-lucide-external-link",
							size: "xs",
							color: "neutral",
							variant: "outline",
							label: props.__.drawer.actions.view_details,
							class: "w-fit cursor-pointer",
							onClick: ($event) => unref(router).get(`/users/${row.original.id}`)
						}, null, _parent, _scopeId));
						else _push(`<!---->`);
						if (row.original.permissions.update) _push(ssrRenderComponent(_component_UFieldGroup, null, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) {
									_push(ssrRenderComponent(unref(UButton), {
										type: "button",
										icon: "i-lucide-pencil",
										size: "xs",
										color: "neutral",
										variant: "outline",
										class: "cursor-pointer",
										label: props.__.user_actions.edit,
										onClick: ($event) => unref(router).get(`/users/${row.original.id}/edit`)
									}, null, _parent, _scopeId));
									_push(ssrRenderComponent(unref(UDropdownMenu), {
										items: getRowEditActionsDropdownItems(row),
										modal: true,
										size: "xs",
										ui: {
											item: "cursor-pointer",
											separator: "h-px w-full bg-white/20"
										}
									}, {
										default: withCtx((_, _push, _parent, _scopeId) => {
											if (_push) _push(ssrRenderComponent(unref(UButton), {
												color: "neutral",
												variant: "outline",
												size: "xs",
												icon: "i-lucide-chevron-down",
												class: "cursor-pointer"
											}, null, _parent, _scopeId));
											else return [createVNode(unref(UButton), {
												color: "neutral",
												variant: "outline",
												size: "xs",
												icon: "i-lucide-chevron-down",
												class: "cursor-pointer"
											})];
										}),
										_: 2
									}, _parent, _scopeId));
								} else return [createVNode(unref(UButton), {
									type: "button",
									icon: "i-lucide-pencil",
									size: "xs",
									color: "neutral",
									variant: "outline",
									class: "cursor-pointer",
									label: props.__.user_actions.edit,
									onClick: withModifiers(($event) => unref(router).get(`/users/${row.original.id}/edit`), ["stop"])
								}, null, 8, ["label", "onClick"]), createVNode(unref(UDropdownMenu), {
									items: getRowEditActionsDropdownItems(row),
									modal: true,
									size: "xs",
									ui: {
										item: "cursor-pointer",
										separator: "h-px w-full bg-white/20"
									}
								}, {
									default: withCtx(() => [createVNode(unref(UButton), {
										color: "neutral",
										variant: "outline",
										size: "xs",
										icon: "i-lucide-chevron-down",
										class: "cursor-pointer"
									})]),
									_: 1
								}, 8, ["items"])];
							}),
							_: 2
						}, _parent, _scopeId));
						else _push(`<!---->`);
						if (row.original.id !== currentUserId.value && (row.original.is_deleted ? row.original.permissions.force_delete : row.original.permissions.delete)) _push(ssrRenderComponent(unref(UButton), {
							icon: "i-lucide-trash",
							size: "xs",
							color: "error",
							variant: "outline",
							label: row.original.is_deleted ? props.__.user_actions.force_delete : props.__.user_actions.delete,
							class: "w-fit cursor-pointer",
							onClick: ($event) => onDeleteUser(row.original)
						}, null, _parent, _scopeId));
						else _push(`<!---->`);
						_push(`</div>`);
					} else return [createVNode("div", { class: "flex flex-col justify-end gap-2" }, [
						row.original.permissions.view ? (openBlock(), createBlock(unref(UButton), {
							key: 0,
							type: "button",
							icon: "i-lucide-external-link",
							size: "xs",
							color: "neutral",
							variant: "outline",
							label: props.__.drawer.actions.view_details,
							class: "w-fit cursor-pointer",
							onClick: withModifiers(($event) => unref(router).get(`/users/${row.original.id}`), ["stop"])
						}, null, 8, ["label", "onClick"])) : createCommentVNode("", true),
						row.original.permissions.update ? (openBlock(), createBlock(_component_UFieldGroup, { key: 1 }, {
							default: withCtx(() => [createVNode(unref(UButton), {
								type: "button",
								icon: "i-lucide-pencil",
								size: "xs",
								color: "neutral",
								variant: "outline",
								class: "cursor-pointer",
								label: props.__.user_actions.edit,
								onClick: withModifiers(($event) => unref(router).get(`/users/${row.original.id}/edit`), ["stop"])
							}, null, 8, ["label", "onClick"]), createVNode(unref(UDropdownMenu), {
								items: getRowEditActionsDropdownItems(row),
								modal: true,
								size: "xs",
								ui: {
									item: "cursor-pointer",
									separator: "h-px w-full bg-white/20"
								}
							}, {
								default: withCtx(() => [createVNode(unref(UButton), {
									color: "neutral",
									variant: "outline",
									size: "xs",
									icon: "i-lucide-chevron-down",
									class: "cursor-pointer"
								})]),
								_: 1
							}, 8, ["items"])]),
							_: 2
						}, 1024)) : createCommentVNode("", true),
						row.original.id !== currentUserId.value && (row.original.is_deleted ? row.original.permissions.force_delete : row.original.permissions.delete) ? (openBlock(), createBlock(unref(UButton), {
							key: 2,
							icon: "i-lucide-trash",
							size: "xs",
							color: "error",
							variant: "outline",
							label: row.original.is_deleted ? props.__.user_actions.force_delete : props.__.user_actions.delete,
							class: "w-fit cursor-pointer",
							onClick: ($event) => onDeleteUser(row.original)
						}, null, 8, ["label", "onClick"])) : createCommentVNode("", true)
					])];
				}),
				_: 1
			}, _parent));
			_push(ssrRenderComponent(_component_USeparator, { class: "mt-10" }, null, _parent));
			_push(ssrRenderComponent(SecondaryDataTable_default, {
				"table-key": "invitations",
				title: props.__.tables.invitations.name,
				"row-selection": invitationsRowSelection.value,
				"onUpdate:rowSelection": ($event) => invitationsRowSelection.value = $event,
				"initial-column-visibility": { id: false },
				data: __props.invitations.data,
				columns: invitationsColumns,
				meta: __props.invitations.meta,
				filters: __props.filters,
				loading: invitationsLoading.value,
				"row-menu-items": getInvitationContextMenuItems,
				selectable: false,
				pagination: false,
				"infinite-scroll": true,
				"max-height": "24rem",
				views: userTableViews.value,
				"view-filter-key": "view",
				labels: {
					search: props.__.actions.search,
					columns: props.__.tables.users.columns,
					selectedWord: props.__.messages.selected,
					connectingWord: props.__.messages.selected_connecting_word,
					unselect: props.__.actions.unselect,
					loadingMore: "Chargement des invitations...",
					endReached: "Toutes les invitations sont affichées"
				},
				class: "mb-10 mt-20",
				onReload: reloadInvitations
			}, {
				"email-cell": withCtx(({ row, getValue }, _push, _parent, _scopeId) => {
					if (_push) _push(`<div class="flex flex-col gap-2"${_scopeId}><a class="text-center text-white/50 hover:underline inline-flex gap-1 items-center w-fit"${ssrRenderAttr("href", `mailto:${getValue()}`)}${_scopeId}><p class="align-middle"${_scopeId}>${ssrInterpolate(getValue())}</p></a></div>`);
					else return [createVNode("div", { class: "flex flex-col gap-2" }, [createVNode("a", {
						class: "text-center text-white/50 hover:underline inline-flex gap-1 items-center w-fit",
						href: `mailto:${getValue()}`
					}, [createVNode("p", { class: "align-middle" }, toDisplayString(getValue()), 1)], 8, ["href"])])];
				}),
				"status_label-cell": withCtx(({ row, getValue }, _push, _parent, _scopeId) => {
					if (_push) _push(ssrRenderComponent(_component_UBadge, {
						label: row.original.status_label,
						color: row.original.status === "accepted" ? "success" : row.original.status === "revoked" || row.original.status === "expired" ? "error" : "warning",
						size: "sm",
						class: "rounded-full"
					}, null, _parent, _scopeId));
					else return [createVNode(_component_UBadge, {
						label: row.original.status_label,
						color: row.original.status === "accepted" ? "success" : row.original.status === "revoked" || row.original.status === "expired" ? "error" : "warning",
						size: "sm",
						class: "rounded-full"
					}, null, 8, ["label", "color"])];
				}),
				"actions-cell": withCtx(({ row }, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex gap-2"${_scopeId}>`);
						_push(ssrRenderComponent(unref(UDropdownMenu), {
							items: getInvitationActionRowItems(row),
							modal: true,
							size: "xs",
							ui: {
								item: "cursor-pointer",
								separator: "h-px w-full bg-white/20"
							}
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(unref(UButton), {
									color: "neutral",
									variant: "outline",
									size: "xs",
									"trailing-icon": "i-lucide-chevron-down",
									class: "cursor-pointer",
									label: "Actions"
								}, null, _parent, _scopeId));
								else return [createVNode(unref(UButton), {
									color: "neutral",
									variant: "outline",
									size: "xs",
									"trailing-icon": "i-lucide-chevron-down",
									class: "cursor-pointer",
									label: "Actions"
								})];
							}),
							_: 2
						}, _parent, _scopeId));
						if (row.original.status !== "accepted" && row.original.status !== "revoked") _push(ssrRenderComponent(unref(UButton), {
							color: "error",
							variant: "outline",
							icon: "i-lucide-link-2-off",
							size: "xs",
							class: "w-fit cursor-pointer",
							label: props.__.invitation_actions.revoke,
							onClick: ($event) => revokeInvite(row)
						}, null, _parent, _scopeId));
						else _push(`<!---->`);
						if (row.original.status === "revoked") _push(ssrRenderComponent(unref(UButton), {
							color: "success",
							variant: "outline",
							icon: "i-lucide-link",
							size: "xs",
							class: "w-fit cursor-pointer",
							label: props.__.invitation_actions.reactive,
							onClick: ($event) => reactiveInvite(row)
						}, null, _parent, _scopeId));
						else _push(`<!---->`);
						_push(`</div>`);
					} else return [createVNode("div", { class: "flex gap-2" }, [
						createVNode(unref(UDropdownMenu), {
							items: getInvitationActionRowItems(row),
							modal: true,
							size: "xs",
							ui: {
								item: "cursor-pointer",
								separator: "h-px w-full bg-white/20"
							}
						}, {
							default: withCtx(() => [createVNode(unref(UButton), {
								color: "neutral",
								variant: "outline",
								size: "xs",
								"trailing-icon": "i-lucide-chevron-down",
								class: "cursor-pointer",
								label: "Actions"
							})]),
							_: 1
						}, 8, ["items"]),
						row.original.status !== "accepted" && row.original.status !== "revoked" ? (openBlock(), createBlock(unref(UButton), {
							key: 0,
							color: "error",
							variant: "outline",
							icon: "i-lucide-link-2-off",
							size: "xs",
							class: "w-fit cursor-pointer",
							label: props.__.invitation_actions.revoke,
							onClick: ($event) => revokeInvite(row)
						}, null, 8, ["label", "onClick"])) : createCommentVNode("", true),
						row.original.status === "revoked" ? (openBlock(), createBlock(unref(UButton), {
							key: 1,
							color: "success",
							variant: "outline",
							icon: "i-lucide-link",
							size: "xs",
							class: "w-fit cursor-pointer",
							label: props.__.invitation_actions.reactive,
							onClick: ($event) => reactiveInvite(row)
						}, null, 8, ["label", "onClick"])) : createCommentVNode("", true)
					])];
				}),
				_: 1
			}, _parent));
			_push(`<!--]-->`);
		};
	}
});
//#endregion
//#region resources/js/Pages/Dashboard/Users/Index.vue
var _sfc_setup = Index_vue_vue_type_script_setup_true_lang_default.setup;
Index_vue_vue_type_script_setup_true_lang_default.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Dashboard/Users/Index.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
var Index_default = Index_vue_vue_type_script_setup_true_lang_default;
//#endregion
export { Index_default as default };

//# sourceMappingURL=Index-DCIB333C.js.map