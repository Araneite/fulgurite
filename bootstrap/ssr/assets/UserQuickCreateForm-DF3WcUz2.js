import { A as getDisplayValue, C as useComponentIcons, D as useLocale, F as useAppConfig, M as looseToNumber, O as compare, S as useFieldGroup, b as FieldGroupReset, c as _sfc_main$2, j as isArrayOfArray, k as get, n as usePortal, o as _sfc_main$3, r as _sfc_main$5, s as _sfc_main$4, u as tv, w as useComponentUI, y as useFormField } from "./usePortal-DZb6nPjI.js";
import { n as _sfc_main$7, r as _sfc_main$6, t as _sfc_main$8 } from "./Form-DAQH5xxu.js";
import { n as useToast } from "./useToast-itvNvMq-.js";
import { t as _sfc_main$9 } from "./Progress-Cl7OHCpt.js";
import { r as _sfc_main$10, t as _sfc_main$11 } from "./PhoneInput-aIWeFFh9.js";
import { useForm } from "@inertiajs/vue3";
import { ssrInterpolate, ssrRenderAttrs, ssrRenderClass, ssrRenderComponent, ssrRenderList, ssrRenderSlot } from "vue/server-renderer";
import { Fragment, computed, createBlock, createCommentVNode, createTextVNode, createVNode, defineComponent, mergeModels, mergeProps, onMounted, openBlock, ref, renderList, renderSlot, toDisplayString, toRaw, toRef, unref, useAttrs, useId, useModel, useSSRContext, useSlots, useTemplateRef, watch, withCtx, withModifiers } from "vue";
import { defu } from "defu";
import { ComboboxAnchor, ComboboxArrow, ComboboxCancel, ComboboxContent, ComboboxEmpty, ComboboxGroup, ComboboxInput, ComboboxItem, ComboboxItemIndicator, ComboboxLabel, ComboboxPortal, ComboboxRoot, ComboboxSeparator, ComboboxTrigger, ComboboxVirtualizer, FocusScope, Label, Primitive, SwitchRoot, SwitchThumb, useFilter, useForwardPropsEmits } from "reka-ui";
import { createReusableTemplate, reactivePick } from "@vueuse/core";
import * as z from "zod";
//#region virtual:nuxt-ui-templates/ui/switch.ts
var switch_default = {
	"slots": {
		"root": "relative flex items-start",
		"base": ["inline-flex items-center shrink-0 rounded-full border-2 border-transparent focus-visible:outline-2 focus-visible:outline-offset-2 data-[state=unchecked]:bg-accented", "transition-[background] duration-200"],
		"container": "flex items-center",
		"thumb": "group pointer-events-none rounded-full bg-default shadow-lg ring-0 transition-transform duration-200 data-[state=unchecked]:translate-x-0 data-[state=unchecked]:rtl:-translate-x-0 flex items-center justify-center",
		"icon": ["absolute shrink-0 group-data-[state=unchecked]:text-dimmed opacity-0 size-10/12", "transition-[color,opacity] duration-200"],
		"wrapper": "ms-2",
		"label": "block font-medium text-default",
		"description": "text-muted"
	},
	"variants": {
		"color": {
			"primary": {
				"base": "data-[state=checked]:bg-primary focus-visible:outline-primary",
				"icon": "group-data-[state=checked]:text-primary"
			},
			"secondary": {
				"base": "data-[state=checked]:bg-secondary focus-visible:outline-secondary",
				"icon": "group-data-[state=checked]:text-secondary"
			},
			"success": {
				"base": "data-[state=checked]:bg-success focus-visible:outline-success",
				"icon": "group-data-[state=checked]:text-success"
			},
			"info": {
				"base": "data-[state=checked]:bg-info focus-visible:outline-info",
				"icon": "group-data-[state=checked]:text-info"
			},
			"warning": {
				"base": "data-[state=checked]:bg-warning focus-visible:outline-warning",
				"icon": "group-data-[state=checked]:text-warning"
			},
			"error": {
				"base": "data-[state=checked]:bg-error focus-visible:outline-error",
				"icon": "group-data-[state=checked]:text-error"
			},
			"neutral": {
				"base": "data-[state=checked]:bg-inverted focus-visible:outline-inverted",
				"icon": "group-data-[state=checked]:text-highlighted"
			}
		},
		"size": {
			"xs": {
				"base": "w-7",
				"container": "h-4",
				"thumb": "size-3 data-[state=checked]:translate-x-3 data-[state=checked]:rtl:-translate-x-3",
				"wrapper": "text-xs"
			},
			"sm": {
				"base": "w-8",
				"container": "h-4",
				"thumb": "size-3.5 data-[state=checked]:translate-x-3.5 data-[state=checked]:rtl:-translate-x-3.5",
				"wrapper": "text-xs"
			},
			"md": {
				"base": "w-9",
				"container": "h-5",
				"thumb": "size-4 data-[state=checked]:translate-x-4 data-[state=checked]:rtl:-translate-x-4",
				"wrapper": "text-sm"
			},
			"lg": {
				"base": "w-10",
				"container": "h-5",
				"thumb": "size-4.5 data-[state=checked]:translate-x-4.5 data-[state=checked]:rtl:-translate-x-4.5",
				"wrapper": "text-sm"
			},
			"xl": {
				"base": "w-11",
				"container": "h-6",
				"thumb": "size-5 data-[state=checked]:translate-x-5 data-[state=checked]:rtl:-translate-x-5",
				"wrapper": "text-base"
			}
		},
		"checked": { "true": { "icon": "group-data-[state=checked]:opacity-100" } },
		"unchecked": { "true": { "icon": "group-data-[state=unchecked]:opacity-100" } },
		"loading": { "true": { "icon": "animate-spin" } },
		"required": { "true": { "label": "after:content-['*'] after:ms-0.5 after:text-error" } },
		"disabled": { "true": {
			"root": "opacity-75",
			"base": "cursor-not-allowed",
			"label": "cursor-not-allowed",
			"description": "cursor-not-allowed"
		} }
	},
	"defaultVariants": {
		"color": "primary",
		"size": "md"
	}
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Switch.vue
var _sfc_main$1 = /* @__PURE__ */ Object.assign({ inheritAttrs: false }, {
	__name: "Switch",
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
		size: {
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
		checkedIcon: {
			type: null,
			required: false
		},
		uncheckedIcon: {
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
		},
		value: {
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
		const uiProp = useComponentUI("switch", props);
		const rootProps = useForwardPropsEmits(reactivePick(props, "required", "value", "defaultValue", "modelValue", "trueValue", "falseValue"), emits);
		const { id: _id, emitFormChange, emitFormInput, size, color, name, disabled, ariaAttrs } = useFormField(props);
		const id = _id.value ?? useId();
		const attrs = useAttrs();
		const forwardedAttrs = computed(() => {
			const { "data-state": _, ...rest } = attrs;
			return rest;
		});
		const ui = computed(() => tv({
			extend: tv(switch_default),
			...appConfig.ui?.switch || {}
		})({
			size: size.value,
			color: color.value,
			required: props.required,
			loading: props.loading,
			disabled: disabled.value || props.loading
		}));
		function onUpdate(value) {
			emits("change", new Event("change", { target: { value } }));
			emitFormChange();
			emitFormInput();
		}
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(Primitive), mergeProps({
				as: __props.as,
				"data-slot": "root",
				class: ui.value.root({ class: [unref(uiProp)?.root, props.class] })
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div data-slot="container" class="${ssrRenderClass(ui.value.container({ class: unref(uiProp)?.container }))}"${_scopeId}>`);
						_push(ssrRenderComponent(unref(SwitchRoot), mergeProps({ id: unref(id) }, {
							...unref(rootProps),
							...forwardedAttrs.value,
							...unref(ariaAttrs)
						}, {
							name: unref(name),
							disabled: unref(disabled) || __props.loading,
							"data-slot": "base",
							class: ui.value.base({ class: unref(uiProp)?.base }),
							"onUpdate:modelValue": onUpdate
						}), {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(unref(SwitchThumb), {
									"data-slot": "thumb",
									class: ui.value.thumb({ class: unref(uiProp)?.thumb })
								}, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) if (__props.loading) _push(ssrRenderComponent(_sfc_main$2, {
											name: __props.loadingIcon || unref(appConfig).ui.icons.loading,
											"data-slot": "icon",
											class: ui.value.icon({
												class: unref(uiProp)?.icon,
												checked: true,
												unchecked: true
											})
										}, null, _parent, _scopeId));
										else {
											_push(`<!--[-->`);
											if (__props.checkedIcon) _push(ssrRenderComponent(_sfc_main$2, {
												name: __props.checkedIcon,
												"data-slot": "icon",
												class: ui.value.icon({
													class: unref(uiProp)?.icon,
													checked: true
												})
											}, null, _parent, _scopeId));
											else _push(`<!---->`);
											if (__props.uncheckedIcon) _push(ssrRenderComponent(_sfc_main$2, {
												name: __props.uncheckedIcon,
												"data-slot": "icon",
												class: ui.value.icon({
													class: unref(uiProp)?.icon,
													unchecked: true
												})
											}, null, _parent, _scopeId));
											else _push(`<!---->`);
											_push(`<!--]-->`);
										}
										else return [__props.loading ? (openBlock(), createBlock(_sfc_main$2, {
											key: 0,
											name: __props.loadingIcon || unref(appConfig).ui.icons.loading,
											"data-slot": "icon",
											class: ui.value.icon({
												class: unref(uiProp)?.icon,
												checked: true,
												unchecked: true
											})
										}, null, 8, ["name", "class"])) : (openBlock(), createBlock(Fragment, { key: 1 }, [__props.checkedIcon ? (openBlock(), createBlock(_sfc_main$2, {
											key: 0,
											name: __props.checkedIcon,
											"data-slot": "icon",
											class: ui.value.icon({
												class: unref(uiProp)?.icon,
												checked: true
											})
										}, null, 8, ["name", "class"])) : createCommentVNode("", true), __props.uncheckedIcon ? (openBlock(), createBlock(_sfc_main$2, {
											key: 1,
											name: __props.uncheckedIcon,
											"data-slot": "icon",
											class: ui.value.icon({
												class: unref(uiProp)?.icon,
												unchecked: true
											})
										}, null, 8, ["name", "class"])) : createCommentVNode("", true)], 64))];
									}),
									_: 1
								}, _parent, _scopeId));
								else return [createVNode(unref(SwitchThumb), {
									"data-slot": "thumb",
									class: ui.value.thumb({ class: unref(uiProp)?.thumb })
								}, {
									default: withCtx(() => [__props.loading ? (openBlock(), createBlock(_sfc_main$2, {
										key: 0,
										name: __props.loadingIcon || unref(appConfig).ui.icons.loading,
										"data-slot": "icon",
										class: ui.value.icon({
											class: unref(uiProp)?.icon,
											checked: true,
											unchecked: true
										})
									}, null, 8, ["name", "class"])) : (openBlock(), createBlock(Fragment, { key: 1 }, [__props.checkedIcon ? (openBlock(), createBlock(_sfc_main$2, {
										key: 0,
										name: __props.checkedIcon,
										"data-slot": "icon",
										class: ui.value.icon({
											class: unref(uiProp)?.icon,
											checked: true
										})
									}, null, 8, ["name", "class"])) : createCommentVNode("", true), __props.uncheckedIcon ? (openBlock(), createBlock(_sfc_main$2, {
										key: 1,
										name: __props.uncheckedIcon,
										"data-slot": "icon",
										class: ui.value.icon({
											class: unref(uiProp)?.icon,
											unchecked: true
										})
									}, null, 8, ["name", "class"])) : createCommentVNode("", true)], 64))]),
									_: 1
								}, 8, ["class"])];
							}),
							_: 1
						}, _parent, _scopeId));
						_push(`</div>`);
						if (__props.label || !!slots.label || __props.description || !!slots.description) {
							_push(`<div data-slot="wrapper" class="${ssrRenderClass(ui.value.wrapper({ class: unref(uiProp)?.wrapper }))}"${_scopeId}>`);
							if (__props.label || !!slots.label) _push(ssrRenderComponent(unref(Label), {
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
							}, _parent, _scopeId));
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
					}, [createVNode(unref(SwitchRoot), mergeProps({ id: unref(id) }, {
						...unref(rootProps),
						...forwardedAttrs.value,
						...unref(ariaAttrs)
					}, {
						name: unref(name),
						disabled: unref(disabled) || __props.loading,
						"data-slot": "base",
						class: ui.value.base({ class: unref(uiProp)?.base }),
						"onUpdate:modelValue": onUpdate
					}), {
						default: withCtx(() => [createVNode(unref(SwitchThumb), {
							"data-slot": "thumb",
							class: ui.value.thumb({ class: unref(uiProp)?.thumb })
						}, {
							default: withCtx(() => [__props.loading ? (openBlock(), createBlock(_sfc_main$2, {
								key: 0,
								name: __props.loadingIcon || unref(appConfig).ui.icons.loading,
								"data-slot": "icon",
								class: ui.value.icon({
									class: unref(uiProp)?.icon,
									checked: true,
									unchecked: true
								})
							}, null, 8, ["name", "class"])) : (openBlock(), createBlock(Fragment, { key: 1 }, [__props.checkedIcon ? (openBlock(), createBlock(_sfc_main$2, {
								key: 0,
								name: __props.checkedIcon,
								"data-slot": "icon",
								class: ui.value.icon({
									class: unref(uiProp)?.icon,
									checked: true
								})
							}, null, 8, ["name", "class"])) : createCommentVNode("", true), __props.uncheckedIcon ? (openBlock(), createBlock(_sfc_main$2, {
								key: 1,
								name: __props.uncheckedIcon,
								"data-slot": "icon",
								class: ui.value.icon({
									class: unref(uiProp)?.icon,
									unchecked: true
								})
							}, null, 8, ["name", "class"])) : createCommentVNode("", true)], 64))]),
							_: 1
						}, 8, ["class"])]),
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
					}, [__props.label || !!slots.label ? (openBlock(), createBlock(unref(Label), {
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
var _sfc_setup$3 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Switch.vue");
	return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/composables/useFilter.js
function useFilter$1() {
	const { contains, startsWith } = useFilter({ sensitivity: "base" });
	function score(value, searchTerm) {
		if (!contains(value, searchTerm)) return null;
		if (contains(searchTerm, value)) return 0;
		if (startsWith(value, searchTerm)) return 1;
		return 2;
	}
	function scoreItem(item, searchTerm, fields) {
		if (typeof item !== "object" || item === null) return score(String(item), searchTerm);
		let bestScore = null;
		for (const field of fields) {
			const value = get(item, field);
			if (value == null) continue;
			const values = Array.isArray(value) ? value.map(String) : [String(value)];
			for (const v of values) {
				const s = score(v, searchTerm);
				if (s !== null && (bestScore === null || s < bestScore)) bestScore = s;
				if (bestScore === 0) return 0;
			}
		}
		return bestScore;
	}
	function filter(items, searchTerm, fields) {
		if (!searchTerm) return items;
		const scored = [];
		for (const item of items) {
			const s = scoreItem(item, searchTerm, fields);
			if (s !== null) scored.push({
				item,
				score: s
			});
		}
		scored.sort((a, b) => a.score - b.score);
		return scored.map(({ item }) => item);
	}
	function filterGroups(groups, searchTerm, options) {
		if (!searchTerm) return groups;
		return groups.map((group) => {
			const result = [];
			for (const item of group) {
				if (item === void 0 || item === null) continue;
				if (options.isStructural?.(item)) {
					result.push({
						item,
						score: -1
					});
					continue;
				}
				const s = scoreItem(item, searchTerm, options.fields);
				if (s !== null) result.push({
					item,
					score: s
				});
			}
			result.sort((a, b) => a.score - b.score);
			return result.map(({ item }) => item);
		}).filter((group) => group.some((item) => !options.isStructural?.(item)));
	}
	return {
		score,
		scoreItem,
		filter,
		filterGroups
	};
}
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/utils/virtualizer.js
function itemHasDescription(item, descriptionKey) {
	if (typeof item !== "object" || item === null) return false;
	const value = get(item, descriptionKey);
	return value !== void 0 && value !== null && value !== "";
}
function getSize(size, hasDescription) {
	if (hasDescription) return {
		xs: 44,
		sm: 48,
		md: 52,
		lg: 56,
		xl: 60
	}[size];
	return {
		xs: 24,
		sm: 28,
		md: 32,
		lg: 36,
		xl: 40
	}[size];
}
function getEstimateSize(items, size, descriptionKey, hasDescriptionSlot) {
	const sizeWithDescription = getSize(size, true);
	const sizeWithoutDescription = getSize(size, false);
	if (hasDescriptionSlot) return () => sizeWithDescription;
	if (!descriptionKey) return () => sizeWithoutDescription;
	return (index) => {
		return itemHasDescription(items[index], descriptionKey) ? sizeWithDescription : sizeWithoutDescription;
	};
}
//#endregion
//#region virtual:nuxt-ui-templates/ui/select-menu.ts
var select_menu_default = {
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
		"content": ["max-h-60 w-(--reka-select-trigger-width) bg-default shadow-lg rounded-md ring ring-default overflow-hidden origin-(--reka-select-content-transform-origin) pointer-events-auto flex flex-col", "origin-(--reka-combobox-content-transform-origin) w-(--reka-combobox-trigger-width)"],
		"viewport": "relative scroll-py-1 overflow-y-auto flex-1",
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
		"itemDescription": "truncate text-muted",
		"input": "border-b border-default",
		"focusScope": "flex flex-col min-h-0",
		"trailingClear": "p-0"
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
		},
		"virtualize": {
			"true": { "viewport": "p-1 isolate" },
			"false": { "viewport": "divide-y divide-default" }
		}
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
		"variant": "outline",
		"position": "popper"
	}
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/SelectMenu.vue
var _sfc_main = /* @__PURE__ */ Object.assign({ inheritAttrs: false }, {
	__name: "SelectMenu",
	__ssrInlineRender: true,
	props: /* @__PURE__ */ mergeModels({
		id: {
			type: String,
			required: false
		},
		placeholder: {
			type: String,
			required: false
		},
		searchInput: {
			type: [Boolean, Object],
			required: false,
			default: true
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
		trailingIcon: {
			type: null,
			required: false
		},
		selectedIcon: {
			type: null,
			required: false
		},
		clear: {
			type: [Boolean, Object],
			required: false
		},
		clearIcon: {
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
		virtualize: {
			type: [Boolean, Object],
			required: false,
			default: false
		},
		valueKey: {
			type: null,
			required: false
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
		createItem: {
			type: [
				Boolean,
				String,
				Object
			],
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
		disabled: {
			type: Boolean,
			required: false
		},
		name: {
			type: String,
			required: false
		},
		resetSearchTermOnBlur: {
			type: Boolean,
			required: false,
			default: true
		},
		resetSearchTermOnSelect: {
			type: Boolean,
			required: false,
			default: true
		},
		resetModelValueOnClear: {
			type: Boolean,
			required: false,
			default: true
		},
		highlightOnHover: {
			type: Boolean,
			required: false
		},
		by: {
			type: [String, Function],
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
	}, {
		"searchTerm": {
			type: String,
			default: ""
		},
		"searchTermModifiers": {}
	}),
	emits: /* @__PURE__ */ mergeModels([
		"change",
		"blur",
		"focus",
		"create",
		"clear",
		"highlight",
		"update:modelValue",
		"update:open"
	], ["update:searchTerm"]),
	setup(__props, { expose: __expose, emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const slots = useSlots();
		const searchTerm = useModel(__props, "searchTerm", {
			type: String,
			default: ""
		});
		const { t } = useLocale();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("selectMenu", props);
		const { filterGroups } = useFilter$1();
		const rootProps = useForwardPropsEmits(reactivePick(props, "modelValue", "defaultValue", "open", "defaultOpen", "required", "multiple", "resetSearchTermOnBlur", "resetSearchTermOnSelect", "resetModelValueOnClear", "highlightOnHover", "by"), emits);
		const portalProps = usePortal(toRef(() => props.portal));
		const contentProps = toRef(() => defu(props.content, {
			side: "bottom",
			sideOffset: 8,
			collisionPadding: 8,
			position: "popper"
		}));
		const arrowProps = toRef(() => defu(props.arrow, { rounded: true }));
		const clearProps = computed(() => typeof props.clear === "object" ? props.clear : {});
		const virtualizerProps = toRef(() => {
			if (!props.virtualize) return false;
			return defu(typeof props.virtualize === "boolean" ? {} : props.virtualize, { estimateSize: getEstimateSize(filteredItems.value, selectSize.value || "md", props.descriptionKey, !!slots["item-description"]) });
		});
		const searchInputProps = toRef(() => defu(props.searchInput, {
			placeholder: t("selectMenu.search"),
			variant: "none"
		}));
		const { emitFormBlur, emitFormFocus, emitFormInput, emitFormChange, size: formFieldSize, color, id, name, highlight, disabled, ariaAttrs } = useFormField(props);
		const { orientation, size: fieldGroupSize } = useFieldGroup(props);
		const { isLeading, isTrailing, leadingIconName, trailingIconName } = useComponentIcons(toRef(() => defu(props, { trailingIcon: appConfig.ui.icons.chevronDown })));
		const selectSize = computed(() => fieldGroupSize.value || formFieldSize.value);
		const [DefineCreateItemTemplate, ReuseCreateItemTemplate] = createReusableTemplate();
		const [DefineItemTemplate, ReuseItemTemplate] = createReusableTemplate({ props: {
			item: {
				type: [
					Object,
					String,
					Number,
					Boolean
				],
				required: true
			},
			index: {
				type: Number,
				required: false
			}
		} });
		const ui = computed(() => tv({
			extend: tv(select_menu_default),
			...appConfig.ui?.selectMenu || {}
		})({
			color: color.value,
			variant: props.variant,
			size: selectSize?.value,
			loading: props.loading,
			highlight: highlight.value,
			leading: isLeading.value || !!props.avatar || !!slots.leading,
			trailing: isTrailing.value || !!slots.trailing,
			fieldGroup: orientation.value,
			virtualize: !!props.virtualize
		}));
		function displayValue(value) {
			if (props.multiple && Array.isArray(value)) {
				const displayedValues = value.map((item) => getDisplayValue(items.value, item, {
					labelKey: props.labelKey,
					valueKey: props.valueKey,
					by: props.by
				})).filter((v) => v != null && v !== "");
				return displayedValues.length > 0 ? displayedValues.join(", ") : void 0;
			}
			return getDisplayValue(items.value, value, {
				labelKey: props.labelKey,
				valueKey: props.valueKey,
				by: props.by
			});
		}
		const groups = computed(() => props.items?.length ? isArrayOfArray(props.items) ? props.items : [props.items] : []);
		const items = computed(() => groups.value.flatMap((group) => group));
		const filteredGroups = computed(() => {
			if (props.ignoreFilter || !searchTerm.value) return groups.value;
			const fields = Array.isArray(props.filterFields) ? props.filterFields : [props.labelKey];
			return filterGroups(groups.value, searchTerm.value, {
				fields,
				isStructural: (item) => isSelectItem(item) && !!item.type && ["label", "separator"].includes(item.type)
			});
		});
		const filteredItems = computed(() => filteredGroups.value.flatMap((group) => group));
		const createItem = computed(() => {
			if (!props.createItem || !searchTerm.value) return false;
			const newItem = props.valueKey ? { [props.valueKey]: searchTerm.value } : searchTerm.value;
			if (typeof props.createItem === "object" && props.createItem.when === "always" || props.createItem === "always") return !filteredItems.value.find((item) => compare(item, newItem, props.by ?? props.valueKey));
			return !filteredItems.value.length;
		});
		const createItemPosition = computed(() => typeof props.createItem === "object" ? props.createItem.position : "bottom");
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
			if (toRaw(props.modelValue) === value) return;
			if (props.modelModifiers?.trim && (typeof value === "string" || value === null || value === void 0)) value = value?.trim() ?? null;
			if (props.modelModifiers?.number) value = looseToNumber(value);
			if (props.modelModifiers?.nullable) value ??= null;
			if (props.modelModifiers?.optional && !props.modelModifiers?.nullable && value !== null) value ??= void 0;
			emits("change", new Event("change", { target: { value } }));
			emitFormChange();
			emitFormInput();
			if (props.resetSearchTermOnSelect) searchTerm.value = "";
		}
		function onUpdateOpen(value) {
			let timeoutId;
			if (!value) {
				emits("blur", new FocusEvent("blur"));
				emitFormBlur();
				if (props.resetSearchTermOnBlur) timeoutId = setTimeout(() => {
					searchTerm.value = "";
				}, 100);
			} else {
				emits("focus", new FocusEvent("focus"));
				emitFormFocus();
				clearTimeout(timeoutId);
			}
		}
		function onCreate(e) {
			e.preventDefault();
			e.stopPropagation();
			emits("create", searchTerm.value);
		}
		function onSelect(e, item) {
			if (!isSelectItem(item)) return;
			if (item.disabled) {
				e.preventDefault();
				return;
			}
			item.onSelect?.(e);
		}
		function isSelectItem(item) {
			return typeof item === "object" && item !== null;
		}
		function isModelValueEmpty(modelValue) {
			if (props.multiple && Array.isArray(modelValue)) return modelValue.length === 0;
			return modelValue === void 0 || modelValue === null || modelValue === "";
		}
		function onClear() {
			emits("clear");
		}
		const viewportRef = useTemplateRef("viewportRef");
		__expose({
			triggerRef: toRef(() => triggerRef.value?.$el),
			viewportRef: toRef(() => viewportRef.value)
		});
		return (_ctx, _push, _parent, _attrs) => {
			_push(`<!--[-->`);
			_push(ssrRenderComponent(unref(DefineCreateItemTemplate), null, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(ssrRenderComponent(unref(ComboboxItem), {
						"data-slot": "item",
						class: ui.value.item({ class: unref(uiProp)?.item }),
						value: searchTerm.value,
						onSelect: onCreate
					}, {
						default: withCtx((_, _push, _parent, _scopeId) => {
							if (_push) {
								_push(`<span data-slot="itemLabel" class="${ssrRenderClass(ui.value.itemLabel({ class: unref(uiProp)?.itemLabel }))}"${_scopeId}>`);
								ssrRenderSlot(_ctx.$slots, "create-item-label", { item: searchTerm.value }, () => {
									_push(`${ssrInterpolate(unref(t)("selectMenu.create", { label: searchTerm.value }))}`);
								}, _push, _parent, _scopeId);
								_push(`</span>`);
							} else return [createVNode("span", {
								"data-slot": "itemLabel",
								class: ui.value.itemLabel({ class: unref(uiProp)?.itemLabel })
							}, [renderSlot(_ctx.$slots, "create-item-label", { item: searchTerm.value }, () => [createTextVNode(toDisplayString(unref(t)("selectMenu.create", { label: searchTerm.value })), 1)])], 2)];
						}),
						_: 3
					}, _parent, _scopeId));
					else return [createVNode(unref(ComboboxItem), {
						"data-slot": "item",
						class: ui.value.item({ class: unref(uiProp)?.item }),
						value: searchTerm.value,
						onSelect: onCreate
					}, {
						default: withCtx(() => [createVNode("span", {
							"data-slot": "itemLabel",
							class: ui.value.itemLabel({ class: unref(uiProp)?.itemLabel })
						}, [renderSlot(_ctx.$slots, "create-item-label", { item: searchTerm.value }, () => [createTextVNode(toDisplayString(unref(t)("selectMenu.create", { label: searchTerm.value })), 1)])], 2)]),
						_: 3
					}, 8, ["class", "value"])];
				}),
				_: 3
			}, _parent));
			_push(ssrRenderComponent(unref(DefineItemTemplate), null, {
				default: withCtx(({ item, index }, _push, _parent, _scopeId) => {
					if (_push) if (isSelectItem(item) && item.type === "label") _push(ssrRenderComponent(unref(ComboboxLabel), {
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
					else if (isSelectItem(item) && item.type === "separator") _push(ssrRenderComponent(unref(ComboboxSeparator), {
						"data-slot": "separator",
						class: ui.value.separator({ class: [
							unref(uiProp)?.separator,
							item.ui?.separator,
							item.class
						] })
					}, null, _parent, _scopeId));
					else _push(ssrRenderComponent(unref(ComboboxItem), {
						"data-slot": "item",
						class: ui.value.item({ class: [
							unref(uiProp)?.item,
							isSelectItem(item) && item.ui?.item,
							isSelectItem(item) && item.class
						] }),
						disabled: isSelectItem(item) && item.disabled,
						value: props.valueKey && isSelectItem(item) ? unref(get)(item, props.valueKey) : item,
						onSelect: ($event) => onSelect($event, item)
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
									else if (isSelectItem(item) && item.avatar) _push(ssrRenderComponent(_sfc_main$3, mergeProps({ size: item.ui?.itemLeadingAvatarSize || unref(uiProp)?.itemLeadingAvatarSize || ui.value.itemLeadingAvatarSize() }, item.avatar, {
										"data-slot": "itemLeadingAvatar",
										class: ui.value.itemLeadingAvatar({ class: [unref(uiProp)?.itemLeadingAvatar, item.ui?.itemLeadingAvatar] })
									}), null, _parent, _scopeId));
									else if (isSelectItem(item) && item.chip) _push(ssrRenderComponent(_sfc_main$4, mergeProps({
										size: item.ui?.itemLeadingChipSize || unref(uiProp)?.itemLeadingChipSize || ui.value.itemLeadingChipSize(),
										inset: "",
										standalone: ""
									}, item.chip, {
										"data-slot": "itemLeadingChip",
										class: ui.value.itemLeadingChip({ class: [unref(uiProp)?.itemLeadingChip, item.ui?.itemLeadingChip] })
									}), null, _parent, _scopeId));
									else _push(`<!---->`);
								}, _push, _parent, _scopeId);
								_push(`<span data-slot="itemWrapper" class="${ssrRenderClass(ui.value.itemWrapper({ class: [unref(uiProp)?.itemWrapper, isSelectItem(item) && item.ui?.itemWrapper] }))}"${_scopeId}><span data-slot="itemLabel" class="${ssrRenderClass(ui.value.itemLabel({ class: [unref(uiProp)?.itemLabel, isSelectItem(item) && item.ui?.itemLabel] }))}"${_scopeId}>`);
								ssrRenderSlot(_ctx.$slots, "item-label", {
									item,
									index
								}, () => {
									_push(`${ssrInterpolate(isSelectItem(item) ? unref(get)(item, props.labelKey) : item)}`);
								}, _push, _parent, _scopeId);
								_push(`</span>`);
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
								_push(ssrRenderComponent(unref(ComboboxItemIndicator), { "as-child": "" }, {
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
								}, item.avatar, {
									"data-slot": "itemLeadingAvatar",
									class: ui.value.itemLeadingAvatar({ class: [unref(uiProp)?.itemLeadingAvatar, item.ui?.itemLeadingAvatar] })
								}), null, 16, ["size", "class"])) : isSelectItem(item) && item.chip ? (openBlock(), createBlock(_sfc_main$4, mergeProps({
									key: 2,
									size: item.ui?.itemLeadingChipSize || unref(uiProp)?.itemLeadingChipSize || ui.value.itemLeadingChipSize(),
									inset: "",
									standalone: ""
								}, item.chip, {
									"data-slot": "itemLeadingChip",
									class: ui.value.itemLeadingChip({ class: [unref(uiProp)?.itemLeadingChip, item.ui?.itemLeadingChip] })
								}), null, 16, ["size", "class"])) : createCommentVNode("", true)]),
								createVNode("span", {
									"data-slot": "itemWrapper",
									class: ui.value.itemWrapper({ class: [unref(uiProp)?.itemWrapper, isSelectItem(item) && item.ui?.itemWrapper] })
								}, [createVNode("span", {
									"data-slot": "itemLabel",
									class: ui.value.itemLabel({ class: [unref(uiProp)?.itemLabel, isSelectItem(item) && item.ui?.itemLabel] })
								}, [renderSlot(_ctx.$slots, "item-label", {
									item,
									index
								}, () => [createTextVNode(toDisplayString(isSelectItem(item) ? unref(get)(item, props.labelKey) : item), 1)])], 2), isSelectItem(item) && (unref(get)(item, props.descriptionKey) || !!slots["item-description"]) ? (openBlock(), createBlock("span", {
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
								}), createVNode(unref(ComboboxItemIndicator), { "as-child": "" }, {
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
					else return [isSelectItem(item) && item.type === "label" ? (openBlock(), createBlock(unref(ComboboxLabel), {
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
					}, 1032, ["class"])) : isSelectItem(item) && item.type === "separator" ? (openBlock(), createBlock(unref(ComboboxSeparator), {
						key: 1,
						"data-slot": "separator",
						class: ui.value.separator({ class: [
							unref(uiProp)?.separator,
							item.ui?.separator,
							item.class
						] })
					}, null, 8, ["class"])) : (openBlock(), createBlock(unref(ComboboxItem), {
						key: 2,
						"data-slot": "item",
						class: ui.value.item({ class: [
							unref(uiProp)?.item,
							isSelectItem(item) && item.ui?.item,
							isSelectItem(item) && item.class
						] }),
						disabled: isSelectItem(item) && item.disabled,
						value: props.valueKey && isSelectItem(item) ? unref(get)(item, props.valueKey) : item,
						onSelect: ($event) => onSelect($event, item)
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
							}, item.avatar, {
								"data-slot": "itemLeadingAvatar",
								class: ui.value.itemLeadingAvatar({ class: [unref(uiProp)?.itemLeadingAvatar, item.ui?.itemLeadingAvatar] })
							}), null, 16, ["size", "class"])) : isSelectItem(item) && item.chip ? (openBlock(), createBlock(_sfc_main$4, mergeProps({
								key: 2,
								size: item.ui?.itemLeadingChipSize || unref(uiProp)?.itemLeadingChipSize || ui.value.itemLeadingChipSize(),
								inset: "",
								standalone: ""
							}, item.chip, {
								"data-slot": "itemLeadingChip",
								class: ui.value.itemLeadingChip({ class: [unref(uiProp)?.itemLeadingChip, item.ui?.itemLeadingChip] })
							}), null, 16, ["size", "class"])) : createCommentVNode("", true)]),
							createVNode("span", {
								"data-slot": "itemWrapper",
								class: ui.value.itemWrapper({ class: [unref(uiProp)?.itemWrapper, isSelectItem(item) && item.ui?.itemWrapper] })
							}, [createVNode("span", {
								"data-slot": "itemLabel",
								class: ui.value.itemLabel({ class: [unref(uiProp)?.itemLabel, isSelectItem(item) && item.ui?.itemLabel] })
							}, [renderSlot(_ctx.$slots, "item-label", {
								item,
								index
							}, () => [createTextVNode(toDisplayString(isSelectItem(item) ? unref(get)(item, props.labelKey) : item), 1)])], 2), isSelectItem(item) && (unref(get)(item, props.descriptionKey) || !!slots["item-description"]) ? (openBlock(), createBlock("span", {
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
							}), createVNode(unref(ComboboxItemIndicator), { "as-child": "" }, {
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
					]))];
				}),
				_: 3
			}, _parent));
			_push(ssrRenderComponent(unref(ComboboxRoot), mergeProps({ id: unref(id) }, {
				...unref(rootProps),
				..._ctx.$attrs,
				...unref(ariaAttrs)
			}, {
				"ignore-filter": "",
				"as-child": "",
				name: unref(name),
				disabled: unref(disabled),
				"onUpdate:modelValue": onUpdate,
				"onUpdate:open": onUpdateOpen
			}), {
				default: withCtx(({ modelValue, open }, _push, _parent, _scopeId) => {
					if (_push) {
						_push(ssrRenderComponent(unref(ComboboxAnchor), { "as-child": "" }, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(unref(ComboboxTrigger), {
									ref_key: "triggerRef",
									ref: triggerRef,
									"data-slot": "base",
									class: ui.value.base({ class: [unref(uiProp)?.base, props.class] }),
									tabindex: "0"
								}, {
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
											ssrRenderSlot(_ctx.$slots, "default", {
												modelValue,
												open,
												ui: ui.value
											}, () => {
												_push(`<!--[-->`);
												ssrRenderList([displayValue(modelValue)], (displayedModelValue) => {
													_push(`<!--[-->`);
													if (displayedModelValue !== void 0 && displayedModelValue !== null) _push(`<span data-slot="value" class="${ssrRenderClass(ui.value.value({ class: unref(uiProp)?.value }))}"${_scopeId}>${ssrInterpolate(displayedModelValue)}</span>`);
													else _push(`<span data-slot="placeholder" class="${ssrRenderClass(ui.value.placeholder({ class: unref(uiProp)?.placeholder }))}"${_scopeId}>${ssrInterpolate(__props.placeholder ?? "\xA0")}</span>`);
													_push(`<!--]-->`);
												});
												_push(`<!--]-->`);
											}, _push, _parent, _scopeId);
											if (unref(isTrailing) || !!slots.trailing || !!__props.clear) {
												_push(`<span data-slot="trailing" class="${ssrRenderClass(ui.value.trailing({ class: unref(uiProp)?.trailing }))}"${_scopeId}>`);
												ssrRenderSlot(_ctx.$slots, "trailing", {
													modelValue,
													open,
													ui: ui.value
												}, () => {
													if (!!__props.clear && !isModelValueEmpty(modelValue)) _push(ssrRenderComponent(unref(ComboboxCancel), { "as-child": "" }, {
														default: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) _push(ssrRenderComponent(_sfc_main$5, mergeProps({
																as: "span",
																icon: __props.clearIcon || unref(appConfig).ui.icons.close,
																size: selectSize.value,
																variant: "link",
																color: "neutral",
																tabindex: "-1"
															}, clearProps.value, {
																"data-slot": "trailingClear",
																class: ui.value.trailingClear({ class: unref(uiProp)?.trailingClear }),
																onClick: onClear
															}), null, _parent, _scopeId));
															else return [createVNode(_sfc_main$5, mergeProps({
																as: "span",
																icon: __props.clearIcon || unref(appConfig).ui.icons.close,
																size: selectSize.value,
																variant: "link",
																color: "neutral",
																tabindex: "-1"
															}, clearProps.value, {
																"data-slot": "trailingClear",
																class: ui.value.trailingClear({ class: unref(uiProp)?.trailingClear }),
																onClick: withModifiers(onClear, ["stop"])
															}), null, 16, [
																"icon",
																"size",
																"class"
															])];
														}),
														_: 2
													}, _parent, _scopeId));
													else if (unref(trailingIconName)) _push(ssrRenderComponent(_sfc_main$2, {
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
											renderSlot(_ctx.$slots, "default", {
												modelValue,
												open,
												ui: ui.value
											}, () => [(openBlock(true), createBlock(Fragment, null, renderList([displayValue(modelValue)], (displayedModelValue) => {
												return openBlock(), createBlock(Fragment, { key: displayedModelValue }, [displayedModelValue !== void 0 && displayedModelValue !== null ? (openBlock(), createBlock("span", {
													key: 0,
													"data-slot": "value",
													class: ui.value.value({ class: unref(uiProp)?.value })
												}, toDisplayString(displayedModelValue), 3)) : (openBlock(), createBlock("span", {
													key: 1,
													"data-slot": "placeholder",
													class: ui.value.placeholder({ class: unref(uiProp)?.placeholder })
												}, toDisplayString(__props.placeholder ?? "\xA0"), 3))], 64);
											}), 128))]),
											unref(isTrailing) || !!slots.trailing || !!__props.clear ? (openBlock(), createBlock("span", {
												key: 1,
												"data-slot": "trailing",
												class: ui.value.trailing({ class: unref(uiProp)?.trailing })
											}, [renderSlot(_ctx.$slots, "trailing", {
												modelValue,
												open,
												ui: ui.value
											}, () => [!!__props.clear && !isModelValueEmpty(modelValue) ? (openBlock(), createBlock(unref(ComboboxCancel), {
												key: 0,
												"as-child": ""
											}, {
												default: withCtx(() => [createVNode(_sfc_main$5, mergeProps({
													as: "span",
													icon: __props.clearIcon || unref(appConfig).ui.icons.close,
													size: selectSize.value,
													variant: "link",
													color: "neutral",
													tabindex: "-1"
												}, clearProps.value, {
													"data-slot": "trailingClear",
													class: ui.value.trailingClear({ class: unref(uiProp)?.trailingClear }),
													onClick: withModifiers(onClear, ["stop"])
												}), null, 16, [
													"icon",
													"size",
													"class"
												])]),
												_: 1
											})) : unref(trailingIconName) ? (openBlock(), createBlock(_sfc_main$2, {
												key: 1,
												name: unref(trailingIconName),
												"data-slot": "trailingIcon",
												class: ui.value.trailingIcon({ class: unref(uiProp)?.trailingIcon })
											}, null, 8, ["name", "class"])) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true)
										];
									}),
									_: 2
								}, _parent, _scopeId));
								else return [createVNode(unref(ComboboxTrigger), {
									ref_key: "triggerRef",
									ref: triggerRef,
									"data-slot": "base",
									class: ui.value.base({ class: [unref(uiProp)?.base, props.class] }),
									tabindex: "0"
								}, {
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
										renderSlot(_ctx.$slots, "default", {
											modelValue,
											open,
											ui: ui.value
										}, () => [(openBlock(true), createBlock(Fragment, null, renderList([displayValue(modelValue)], (displayedModelValue) => {
											return openBlock(), createBlock(Fragment, { key: displayedModelValue }, [displayedModelValue !== void 0 && displayedModelValue !== null ? (openBlock(), createBlock("span", {
												key: 0,
												"data-slot": "value",
												class: ui.value.value({ class: unref(uiProp)?.value })
											}, toDisplayString(displayedModelValue), 3)) : (openBlock(), createBlock("span", {
												key: 1,
												"data-slot": "placeholder",
												class: ui.value.placeholder({ class: unref(uiProp)?.placeholder })
											}, toDisplayString(__props.placeholder ?? "\xA0"), 3))], 64);
										}), 128))]),
										unref(isTrailing) || !!slots.trailing || !!__props.clear ? (openBlock(), createBlock("span", {
											key: 1,
											"data-slot": "trailing",
											class: ui.value.trailing({ class: unref(uiProp)?.trailing })
										}, [renderSlot(_ctx.$slots, "trailing", {
											modelValue,
											open,
											ui: ui.value
										}, () => [!!__props.clear && !isModelValueEmpty(modelValue) ? (openBlock(), createBlock(unref(ComboboxCancel), {
											key: 0,
											"as-child": ""
										}, {
											default: withCtx(() => [createVNode(_sfc_main$5, mergeProps({
												as: "span",
												icon: __props.clearIcon || unref(appConfig).ui.icons.close,
												size: selectSize.value,
												variant: "link",
												color: "neutral",
												tabindex: "-1"
											}, clearProps.value, {
												"data-slot": "trailingClear",
												class: ui.value.trailingClear({ class: unref(uiProp)?.trailingClear }),
												onClick: withModifiers(onClear, ["stop"])
											}), null, 16, [
												"icon",
												"size",
												"class"
											])]),
											_: 1
										})) : unref(trailingIconName) ? (openBlock(), createBlock(_sfc_main$2, {
											key: 1,
											name: unref(trailingIconName),
											"data-slot": "trailingIcon",
											class: ui.value.trailingIcon({ class: unref(uiProp)?.trailingIcon })
										}, null, 8, ["name", "class"])) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true)
									]),
									_: 2
								}, 1032, ["class"])];
							}),
							_: 2
						}, _parent, _scopeId));
						_push(ssrRenderComponent(unref(ComboboxPortal), unref(portalProps), {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(unref(FieldGroupReset), null, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) _push(ssrRenderComponent(unref(ComboboxContent), mergeProps({
											"data-slot": "content",
											class: ui.value.content({ class: unref(uiProp)?.content })
										}, contentProps.value), {
											default: withCtx((_, _push, _parent, _scopeId) => {
												if (_push) {
													_push(ssrRenderComponent(unref(FocusScope), {
														trapped: "",
														"data-slot": "focusScope",
														class: ui.value.focusScope({ class: unref(uiProp)?.focusScope })
													}, {
														default: withCtx((_, _push, _parent, _scopeId) => {
															if (_push) {
																ssrRenderSlot(_ctx.$slots, "content-top", {}, null, _push, _parent, _scopeId);
																if (!!__props.searchInput) _push(ssrRenderComponent(unref(ComboboxInput), {
																	modelValue: searchTerm.value,
																	"onUpdate:modelValue": ($event) => searchTerm.value = $event,
																	"display-value": () => searchTerm.value,
																	"as-child": ""
																}, {
																	default: withCtx((_, _push, _parent, _scopeId) => {
																		if (_push) _push(ssrRenderComponent(_sfc_main$6, mergeProps({
																			autofocus: "",
																			autocomplete: "off",
																			size: selectSize.value
																		}, searchInputProps.value, {
																			"model-modifiers": { trim: __props.modelModifiers?.trim },
																			"data-slot": "input",
																			class: ui.value.input({ class: unref(uiProp)?.input }),
																			onChange: () => {}
																		}), null, _parent, _scopeId));
																		else return [createVNode(_sfc_main$6, mergeProps({
																			autofocus: "",
																			autocomplete: "off",
																			size: selectSize.value
																		}, searchInputProps.value, {
																			"model-modifiers": { trim: __props.modelModifiers?.trim },
																			"data-slot": "input",
																			class: ui.value.input({ class: unref(uiProp)?.input }),
																			onChange: withModifiers(() => {}, ["stop"])
																		}), null, 16, [
																			"size",
																			"model-modifiers",
																			"class",
																			"onChange"
																		])];
																	}),
																	_: 2
																}, _parent, _scopeId));
																else _push(`<!---->`);
																_push(ssrRenderComponent(unref(ComboboxEmpty), {
																	"data-slot": "empty",
																	class: ui.value.empty({ class: unref(uiProp)?.empty })
																}, {
																	default: withCtx((_, _push, _parent, _scopeId) => {
																		if (_push) ssrRenderSlot(_ctx.$slots, "empty", { searchTerm: searchTerm.value }, () => {
																			_push(`${ssrInterpolate(searchTerm.value ? unref(t)("selectMenu.noMatch", { searchTerm: searchTerm.value }) : unref(t)("selectMenu.noData"))}`);
																		}, _push, _parent, _scopeId);
																		else return [renderSlot(_ctx.$slots, "empty", { searchTerm: searchTerm.value }, () => [createTextVNode(toDisplayString(searchTerm.value ? unref(t)("selectMenu.noMatch", { searchTerm: searchTerm.value }) : unref(t)("selectMenu.noData")), 1)])];
																	}),
																	_: 2
																}, _parent, _scopeId));
																_push(`<div role="presentation" data-slot="viewport" class="${ssrRenderClass(ui.value.viewport({ class: unref(uiProp)?.viewport }))}"${_scopeId}>`);
																if (!!__props.virtualize) {
																	_push(`<!--[-->`);
																	if (createItem.value && createItemPosition.value === "top") _push(ssrRenderComponent(unref(ReuseCreateItemTemplate), null, null, _parent, _scopeId));
																	else _push(`<!---->`);
																	_push(ssrRenderComponent(unref(ComboboxVirtualizer), mergeProps({
																		options: filteredItems.value,
																		"text-content": (item2) => isSelectItem(item2) ? unref(get)(item2, props.labelKey) : String(item2)
																	}, virtualizerProps.value), {
																		default: withCtx(({ option: item, virtualItem }, _push, _parent, _scopeId) => {
																			if (_push) _push(ssrRenderComponent(unref(ReuseItemTemplate), {
																				item,
																				index: virtualItem.index
																			}, null, _parent, _scopeId));
																			else return [createVNode(unref(ReuseItemTemplate), {
																				item,
																				index: virtualItem.index
																			}, null, 8, ["item", "index"])];
																		}),
																		_: 2
																	}, _parent, _scopeId));
																	if (createItem.value && createItemPosition.value === "bottom") _push(ssrRenderComponent(unref(ReuseCreateItemTemplate), null, null, _parent, _scopeId));
																	else _push(`<!---->`);
																	_push(`<!--]-->`);
																} else {
																	_push(`<!--[-->`);
																	if (createItem.value && createItemPosition.value === "top") _push(ssrRenderComponent(unref(ComboboxGroup), {
																		"data-slot": "group",
																		class: ui.value.group({ class: unref(uiProp)?.group })
																	}, {
																		default: withCtx((_, _push, _parent, _scopeId) => {
																			if (_push) _push(ssrRenderComponent(unref(ReuseCreateItemTemplate), null, null, _parent, _scopeId));
																			else return [createVNode(unref(ReuseCreateItemTemplate))];
																		}),
																		_: 2
																	}, _parent, _scopeId));
																	else _push(`<!---->`);
																	_push(`<!--[-->`);
																	ssrRenderList(filteredGroups.value, (group, groupIndex) => {
																		_push(ssrRenderComponent(unref(ComboboxGroup), {
																			key: `group-${groupIndex}`,
																			"data-slot": "group",
																			class: ui.value.group({ class: unref(uiProp)?.group })
																		}, {
																			default: withCtx((_, _push, _parent, _scopeId) => {
																				if (_push) {
																					_push(`<!--[-->`);
																					ssrRenderList(group, (item, index) => {
																						_push(ssrRenderComponent(unref(ReuseItemTemplate), {
																							key: `group-${groupIndex}-${index}`,
																							item,
																							index
																						}, null, _parent, _scopeId));
																					});
																					_push(`<!--]-->`);
																				} else return [(openBlock(true), createBlock(Fragment, null, renderList(group, (item, index) => {
																					return openBlock(), createBlock(unref(ReuseItemTemplate), {
																						key: `group-${groupIndex}-${index}`,
																						item,
																						index
																					}, null, 8, ["item", "index"]);
																				}), 128))];
																			}),
																			_: 2
																		}, _parent, _scopeId));
																	});
																	_push(`<!--]-->`);
																	if (createItem.value && createItemPosition.value === "bottom") _push(ssrRenderComponent(unref(ComboboxGroup), {
																		"data-slot": "group",
																		class: ui.value.group({ class: unref(uiProp)?.group })
																	}, {
																		default: withCtx((_, _push, _parent, _scopeId) => {
																			if (_push) _push(ssrRenderComponent(unref(ReuseCreateItemTemplate), null, null, _parent, _scopeId));
																			else return [createVNode(unref(ReuseCreateItemTemplate))];
																		}),
																		_: 2
																	}, _parent, _scopeId));
																	else _push(`<!---->`);
																	_push(`<!--]-->`);
																}
																_push(`</div>`);
																ssrRenderSlot(_ctx.$slots, "content-bottom", {}, null, _push, _parent, _scopeId);
															} else return [
																renderSlot(_ctx.$slots, "content-top"),
																!!__props.searchInput ? (openBlock(), createBlock(unref(ComboboxInput), {
																	key: 0,
																	modelValue: searchTerm.value,
																	"onUpdate:modelValue": ($event) => searchTerm.value = $event,
																	"display-value": () => searchTerm.value,
																	"as-child": ""
																}, {
																	default: withCtx(() => [createVNode(_sfc_main$6, mergeProps({
																		autofocus: "",
																		autocomplete: "off",
																		size: selectSize.value
																	}, searchInputProps.value, {
																		"model-modifiers": { trim: __props.modelModifiers?.trim },
																		"data-slot": "input",
																		class: ui.value.input({ class: unref(uiProp)?.input }),
																		onChange: withModifiers(() => {}, ["stop"])
																	}), null, 16, [
																		"size",
																		"model-modifiers",
																		"class",
																		"onChange"
																	])]),
																	_: 1
																}, 8, [
																	"modelValue",
																	"onUpdate:modelValue",
																	"display-value"
																])) : createCommentVNode("", true),
																createVNode(unref(ComboboxEmpty), {
																	"data-slot": "empty",
																	class: ui.value.empty({ class: unref(uiProp)?.empty })
																}, {
																	default: withCtx(() => [renderSlot(_ctx.$slots, "empty", { searchTerm: searchTerm.value }, () => [createTextVNode(toDisplayString(searchTerm.value ? unref(t)("selectMenu.noMatch", { searchTerm: searchTerm.value }) : unref(t)("selectMenu.noData")), 1)])]),
																	_: 3
																}, 8, ["class"]),
																createVNode("div", {
																	ref_key: "viewportRef",
																	ref: viewportRef,
																	role: "presentation",
																	"data-slot": "viewport",
																	class: ui.value.viewport({ class: unref(uiProp)?.viewport })
																}, [!!__props.virtualize ? (openBlock(), createBlock(Fragment, { key: 0 }, [
																	createItem.value && createItemPosition.value === "top" ? (openBlock(), createBlock(unref(ReuseCreateItemTemplate), { key: 0 })) : createCommentVNode("", true),
																	createVNode(unref(ComboboxVirtualizer), mergeProps({
																		options: filteredItems.value,
																		"text-content": (item2) => isSelectItem(item2) ? unref(get)(item2, props.labelKey) : String(item2)
																	}, virtualizerProps.value), {
																		default: withCtx(({ option: item, virtualItem }) => [createVNode(unref(ReuseItemTemplate), {
																			item,
																			index: virtualItem.index
																		}, null, 8, ["item", "index"])]),
																		_: 1
																	}, 16, ["options", "text-content"]),
																	createItem.value && createItemPosition.value === "bottom" ? (openBlock(), createBlock(unref(ReuseCreateItemTemplate), { key: 1 })) : createCommentVNode("", true)
																], 64)) : (openBlock(), createBlock(Fragment, { key: 1 }, [
																	createItem.value && createItemPosition.value === "top" ? (openBlock(), createBlock(unref(ComboboxGroup), {
																		key: 0,
																		"data-slot": "group",
																		class: ui.value.group({ class: unref(uiProp)?.group })
																	}, {
																		default: withCtx(() => [createVNode(unref(ReuseCreateItemTemplate))]),
																		_: 1
																	}, 8, ["class"])) : createCommentVNode("", true),
																	(openBlock(true), createBlock(Fragment, null, renderList(filteredGroups.value, (group, groupIndex) => {
																		return openBlock(), createBlock(unref(ComboboxGroup), {
																			key: `group-${groupIndex}`,
																			"data-slot": "group",
																			class: ui.value.group({ class: unref(uiProp)?.group })
																		}, {
																			default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(group, (item, index) => {
																				return openBlock(), createBlock(unref(ReuseItemTemplate), {
																					key: `group-${groupIndex}-${index}`,
																					item,
																					index
																				}, null, 8, ["item", "index"]);
																			}), 128))]),
																			_: 2
																		}, 1032, ["class"]);
																	}), 128)),
																	createItem.value && createItemPosition.value === "bottom" ? (openBlock(), createBlock(unref(ComboboxGroup), {
																		key: 1,
																		"data-slot": "group",
																		class: ui.value.group({ class: unref(uiProp)?.group })
																	}, {
																		default: withCtx(() => [createVNode(unref(ReuseCreateItemTemplate))]),
																		_: 1
																	}, 8, ["class"])) : createCommentVNode("", true)
																], 64))], 2),
																renderSlot(_ctx.$slots, "content-bottom")
															];
														}),
														_: 2
													}, _parent, _scopeId));
													if (!!__props.arrow) _push(ssrRenderComponent(unref(ComboboxArrow), mergeProps(arrowProps.value, {
														"data-slot": "arrow",
														class: ui.value.arrow({ class: unref(uiProp)?.arrow })
													}), null, _parent, _scopeId));
													else _push(`<!---->`);
												} else return [createVNode(unref(FocusScope), {
													trapped: "",
													"data-slot": "focusScope",
													class: ui.value.focusScope({ class: unref(uiProp)?.focusScope })
												}, {
													default: withCtx(() => [
														renderSlot(_ctx.$slots, "content-top"),
														!!__props.searchInput ? (openBlock(), createBlock(unref(ComboboxInput), {
															key: 0,
															modelValue: searchTerm.value,
															"onUpdate:modelValue": ($event) => searchTerm.value = $event,
															"display-value": () => searchTerm.value,
															"as-child": ""
														}, {
															default: withCtx(() => [createVNode(_sfc_main$6, mergeProps({
																autofocus: "",
																autocomplete: "off",
																size: selectSize.value
															}, searchInputProps.value, {
																"model-modifiers": { trim: __props.modelModifiers?.trim },
																"data-slot": "input",
																class: ui.value.input({ class: unref(uiProp)?.input }),
																onChange: withModifiers(() => {}, ["stop"])
															}), null, 16, [
																"size",
																"model-modifiers",
																"class",
																"onChange"
															])]),
															_: 1
														}, 8, [
															"modelValue",
															"onUpdate:modelValue",
															"display-value"
														])) : createCommentVNode("", true),
														createVNode(unref(ComboboxEmpty), {
															"data-slot": "empty",
															class: ui.value.empty({ class: unref(uiProp)?.empty })
														}, {
															default: withCtx(() => [renderSlot(_ctx.$slots, "empty", { searchTerm: searchTerm.value }, () => [createTextVNode(toDisplayString(searchTerm.value ? unref(t)("selectMenu.noMatch", { searchTerm: searchTerm.value }) : unref(t)("selectMenu.noData")), 1)])]),
															_: 3
														}, 8, ["class"]),
														createVNode("div", {
															ref_key: "viewportRef",
															ref: viewportRef,
															role: "presentation",
															"data-slot": "viewport",
															class: ui.value.viewport({ class: unref(uiProp)?.viewport })
														}, [!!__props.virtualize ? (openBlock(), createBlock(Fragment, { key: 0 }, [
															createItem.value && createItemPosition.value === "top" ? (openBlock(), createBlock(unref(ReuseCreateItemTemplate), { key: 0 })) : createCommentVNode("", true),
															createVNode(unref(ComboboxVirtualizer), mergeProps({
																options: filteredItems.value,
																"text-content": (item2) => isSelectItem(item2) ? unref(get)(item2, props.labelKey) : String(item2)
															}, virtualizerProps.value), {
																default: withCtx(({ option: item, virtualItem }) => [createVNode(unref(ReuseItemTemplate), {
																	item,
																	index: virtualItem.index
																}, null, 8, ["item", "index"])]),
																_: 1
															}, 16, ["options", "text-content"]),
															createItem.value && createItemPosition.value === "bottom" ? (openBlock(), createBlock(unref(ReuseCreateItemTemplate), { key: 1 })) : createCommentVNode("", true)
														], 64)) : (openBlock(), createBlock(Fragment, { key: 1 }, [
															createItem.value && createItemPosition.value === "top" ? (openBlock(), createBlock(unref(ComboboxGroup), {
																key: 0,
																"data-slot": "group",
																class: ui.value.group({ class: unref(uiProp)?.group })
															}, {
																default: withCtx(() => [createVNode(unref(ReuseCreateItemTemplate))]),
																_: 1
															}, 8, ["class"])) : createCommentVNode("", true),
															(openBlock(true), createBlock(Fragment, null, renderList(filteredGroups.value, (group, groupIndex) => {
																return openBlock(), createBlock(unref(ComboboxGroup), {
																	key: `group-${groupIndex}`,
																	"data-slot": "group",
																	class: ui.value.group({ class: unref(uiProp)?.group })
																}, {
																	default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(group, (item, index) => {
																		return openBlock(), createBlock(unref(ReuseItemTemplate), {
																			key: `group-${groupIndex}-${index}`,
																			item,
																			index
																		}, null, 8, ["item", "index"]);
																	}), 128))]),
																	_: 2
																}, 1032, ["class"]);
															}), 128)),
															createItem.value && createItemPosition.value === "bottom" ? (openBlock(), createBlock(unref(ComboboxGroup), {
																key: 1,
																"data-slot": "group",
																class: ui.value.group({ class: unref(uiProp)?.group })
															}, {
																default: withCtx(() => [createVNode(unref(ReuseCreateItemTemplate))]),
																_: 1
															}, 8, ["class"])) : createCommentVNode("", true)
														], 64))], 2),
														renderSlot(_ctx.$slots, "content-bottom")
													]),
													_: 3
												}, 8, ["class"]), !!__props.arrow ? (openBlock(), createBlock(unref(ComboboxArrow), mergeProps({ key: 0 }, arrowProps.value, {
													"data-slot": "arrow",
													class: ui.value.arrow({ class: unref(uiProp)?.arrow })
												}), null, 16, ["class"])) : createCommentVNode("", true)];
											}),
											_: 2
										}, _parent, _scopeId));
										else return [createVNode(unref(ComboboxContent), mergeProps({
											"data-slot": "content",
											class: ui.value.content({ class: unref(uiProp)?.content })
										}, contentProps.value), {
											default: withCtx(() => [createVNode(unref(FocusScope), {
												trapped: "",
												"data-slot": "focusScope",
												class: ui.value.focusScope({ class: unref(uiProp)?.focusScope })
											}, {
												default: withCtx(() => [
													renderSlot(_ctx.$slots, "content-top"),
													!!__props.searchInput ? (openBlock(), createBlock(unref(ComboboxInput), {
														key: 0,
														modelValue: searchTerm.value,
														"onUpdate:modelValue": ($event) => searchTerm.value = $event,
														"display-value": () => searchTerm.value,
														"as-child": ""
													}, {
														default: withCtx(() => [createVNode(_sfc_main$6, mergeProps({
															autofocus: "",
															autocomplete: "off",
															size: selectSize.value
														}, searchInputProps.value, {
															"model-modifiers": { trim: __props.modelModifiers?.trim },
															"data-slot": "input",
															class: ui.value.input({ class: unref(uiProp)?.input }),
															onChange: withModifiers(() => {}, ["stop"])
														}), null, 16, [
															"size",
															"model-modifiers",
															"class",
															"onChange"
														])]),
														_: 1
													}, 8, [
														"modelValue",
														"onUpdate:modelValue",
														"display-value"
													])) : createCommentVNode("", true),
													createVNode(unref(ComboboxEmpty), {
														"data-slot": "empty",
														class: ui.value.empty({ class: unref(uiProp)?.empty })
													}, {
														default: withCtx(() => [renderSlot(_ctx.$slots, "empty", { searchTerm: searchTerm.value }, () => [createTextVNode(toDisplayString(searchTerm.value ? unref(t)("selectMenu.noMatch", { searchTerm: searchTerm.value }) : unref(t)("selectMenu.noData")), 1)])]),
														_: 3
													}, 8, ["class"]),
													createVNode("div", {
														ref_key: "viewportRef",
														ref: viewportRef,
														role: "presentation",
														"data-slot": "viewport",
														class: ui.value.viewport({ class: unref(uiProp)?.viewport })
													}, [!!__props.virtualize ? (openBlock(), createBlock(Fragment, { key: 0 }, [
														createItem.value && createItemPosition.value === "top" ? (openBlock(), createBlock(unref(ReuseCreateItemTemplate), { key: 0 })) : createCommentVNode("", true),
														createVNode(unref(ComboboxVirtualizer), mergeProps({
															options: filteredItems.value,
															"text-content": (item2) => isSelectItem(item2) ? unref(get)(item2, props.labelKey) : String(item2)
														}, virtualizerProps.value), {
															default: withCtx(({ option: item, virtualItem }) => [createVNode(unref(ReuseItemTemplate), {
																item,
																index: virtualItem.index
															}, null, 8, ["item", "index"])]),
															_: 1
														}, 16, ["options", "text-content"]),
														createItem.value && createItemPosition.value === "bottom" ? (openBlock(), createBlock(unref(ReuseCreateItemTemplate), { key: 1 })) : createCommentVNode("", true)
													], 64)) : (openBlock(), createBlock(Fragment, { key: 1 }, [
														createItem.value && createItemPosition.value === "top" ? (openBlock(), createBlock(unref(ComboboxGroup), {
															key: 0,
															"data-slot": "group",
															class: ui.value.group({ class: unref(uiProp)?.group })
														}, {
															default: withCtx(() => [createVNode(unref(ReuseCreateItemTemplate))]),
															_: 1
														}, 8, ["class"])) : createCommentVNode("", true),
														(openBlock(true), createBlock(Fragment, null, renderList(filteredGroups.value, (group, groupIndex) => {
															return openBlock(), createBlock(unref(ComboboxGroup), {
																key: `group-${groupIndex}`,
																"data-slot": "group",
																class: ui.value.group({ class: unref(uiProp)?.group })
															}, {
																default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(group, (item, index) => {
																	return openBlock(), createBlock(unref(ReuseItemTemplate), {
																		key: `group-${groupIndex}-${index}`,
																		item,
																		index
																	}, null, 8, ["item", "index"]);
																}), 128))]),
																_: 2
															}, 1032, ["class"]);
														}), 128)),
														createItem.value && createItemPosition.value === "bottom" ? (openBlock(), createBlock(unref(ComboboxGroup), {
															key: 1,
															"data-slot": "group",
															class: ui.value.group({ class: unref(uiProp)?.group })
														}, {
															default: withCtx(() => [createVNode(unref(ReuseCreateItemTemplate))]),
															_: 1
														}, 8, ["class"])) : createCommentVNode("", true)
													], 64))], 2),
													renderSlot(_ctx.$slots, "content-bottom")
												]),
												_: 3
											}, 8, ["class"]), !!__props.arrow ? (openBlock(), createBlock(unref(ComboboxArrow), mergeProps({ key: 0 }, arrowProps.value, {
												"data-slot": "arrow",
												class: ui.value.arrow({ class: unref(uiProp)?.arrow })
											}), null, 16, ["class"])) : createCommentVNode("", true)]),
											_: 3
										}, 16, ["class"])];
									}),
									_: 2
								}, _parent, _scopeId));
								else return [createVNode(unref(FieldGroupReset), null, {
									default: withCtx(() => [createVNode(unref(ComboboxContent), mergeProps({
										"data-slot": "content",
										class: ui.value.content({ class: unref(uiProp)?.content })
									}, contentProps.value), {
										default: withCtx(() => [createVNode(unref(FocusScope), {
											trapped: "",
											"data-slot": "focusScope",
											class: ui.value.focusScope({ class: unref(uiProp)?.focusScope })
										}, {
											default: withCtx(() => [
												renderSlot(_ctx.$slots, "content-top"),
												!!__props.searchInput ? (openBlock(), createBlock(unref(ComboboxInput), {
													key: 0,
													modelValue: searchTerm.value,
													"onUpdate:modelValue": ($event) => searchTerm.value = $event,
													"display-value": () => searchTerm.value,
													"as-child": ""
												}, {
													default: withCtx(() => [createVNode(_sfc_main$6, mergeProps({
														autofocus: "",
														autocomplete: "off",
														size: selectSize.value
													}, searchInputProps.value, {
														"model-modifiers": { trim: __props.modelModifiers?.trim },
														"data-slot": "input",
														class: ui.value.input({ class: unref(uiProp)?.input }),
														onChange: withModifiers(() => {}, ["stop"])
													}), null, 16, [
														"size",
														"model-modifiers",
														"class",
														"onChange"
													])]),
													_: 1
												}, 8, [
													"modelValue",
													"onUpdate:modelValue",
													"display-value"
												])) : createCommentVNode("", true),
												createVNode(unref(ComboboxEmpty), {
													"data-slot": "empty",
													class: ui.value.empty({ class: unref(uiProp)?.empty })
												}, {
													default: withCtx(() => [renderSlot(_ctx.$slots, "empty", { searchTerm: searchTerm.value }, () => [createTextVNode(toDisplayString(searchTerm.value ? unref(t)("selectMenu.noMatch", { searchTerm: searchTerm.value }) : unref(t)("selectMenu.noData")), 1)])]),
													_: 3
												}, 8, ["class"]),
												createVNode("div", {
													ref_key: "viewportRef",
													ref: viewportRef,
													role: "presentation",
													"data-slot": "viewport",
													class: ui.value.viewport({ class: unref(uiProp)?.viewport })
												}, [!!__props.virtualize ? (openBlock(), createBlock(Fragment, { key: 0 }, [
													createItem.value && createItemPosition.value === "top" ? (openBlock(), createBlock(unref(ReuseCreateItemTemplate), { key: 0 })) : createCommentVNode("", true),
													createVNode(unref(ComboboxVirtualizer), mergeProps({
														options: filteredItems.value,
														"text-content": (item2) => isSelectItem(item2) ? unref(get)(item2, props.labelKey) : String(item2)
													}, virtualizerProps.value), {
														default: withCtx(({ option: item, virtualItem }) => [createVNode(unref(ReuseItemTemplate), {
															item,
															index: virtualItem.index
														}, null, 8, ["item", "index"])]),
														_: 1
													}, 16, ["options", "text-content"]),
													createItem.value && createItemPosition.value === "bottom" ? (openBlock(), createBlock(unref(ReuseCreateItemTemplate), { key: 1 })) : createCommentVNode("", true)
												], 64)) : (openBlock(), createBlock(Fragment, { key: 1 }, [
													createItem.value && createItemPosition.value === "top" ? (openBlock(), createBlock(unref(ComboboxGroup), {
														key: 0,
														"data-slot": "group",
														class: ui.value.group({ class: unref(uiProp)?.group })
													}, {
														default: withCtx(() => [createVNode(unref(ReuseCreateItemTemplate))]),
														_: 1
													}, 8, ["class"])) : createCommentVNode("", true),
													(openBlock(true), createBlock(Fragment, null, renderList(filteredGroups.value, (group, groupIndex) => {
														return openBlock(), createBlock(unref(ComboboxGroup), {
															key: `group-${groupIndex}`,
															"data-slot": "group",
															class: ui.value.group({ class: unref(uiProp)?.group })
														}, {
															default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(group, (item, index) => {
																return openBlock(), createBlock(unref(ReuseItemTemplate), {
																	key: `group-${groupIndex}-${index}`,
																	item,
																	index
																}, null, 8, ["item", "index"]);
															}), 128))]),
															_: 2
														}, 1032, ["class"]);
													}), 128)),
													createItem.value && createItemPosition.value === "bottom" ? (openBlock(), createBlock(unref(ComboboxGroup), {
														key: 1,
														"data-slot": "group",
														class: ui.value.group({ class: unref(uiProp)?.group })
													}, {
														default: withCtx(() => [createVNode(unref(ReuseCreateItemTemplate))]),
														_: 1
													}, 8, ["class"])) : createCommentVNode("", true)
												], 64))], 2),
												renderSlot(_ctx.$slots, "content-bottom")
											]),
											_: 3
										}, 8, ["class"]), !!__props.arrow ? (openBlock(), createBlock(unref(ComboboxArrow), mergeProps({ key: 0 }, arrowProps.value, {
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
					} else return [createVNode(unref(ComboboxAnchor), { "as-child": "" }, {
						default: withCtx(() => [createVNode(unref(ComboboxTrigger), {
							ref_key: "triggerRef",
							ref: triggerRef,
							"data-slot": "base",
							class: ui.value.base({ class: [unref(uiProp)?.base, props.class] }),
							tabindex: "0"
						}, {
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
								renderSlot(_ctx.$slots, "default", {
									modelValue,
									open,
									ui: ui.value
								}, () => [(openBlock(true), createBlock(Fragment, null, renderList([displayValue(modelValue)], (displayedModelValue) => {
									return openBlock(), createBlock(Fragment, { key: displayedModelValue }, [displayedModelValue !== void 0 && displayedModelValue !== null ? (openBlock(), createBlock("span", {
										key: 0,
										"data-slot": "value",
										class: ui.value.value({ class: unref(uiProp)?.value })
									}, toDisplayString(displayedModelValue), 3)) : (openBlock(), createBlock("span", {
										key: 1,
										"data-slot": "placeholder",
										class: ui.value.placeholder({ class: unref(uiProp)?.placeholder })
									}, toDisplayString(__props.placeholder ?? "\xA0"), 3))], 64);
								}), 128))]),
								unref(isTrailing) || !!slots.trailing || !!__props.clear ? (openBlock(), createBlock("span", {
									key: 1,
									"data-slot": "trailing",
									class: ui.value.trailing({ class: unref(uiProp)?.trailing })
								}, [renderSlot(_ctx.$slots, "trailing", {
									modelValue,
									open,
									ui: ui.value
								}, () => [!!__props.clear && !isModelValueEmpty(modelValue) ? (openBlock(), createBlock(unref(ComboboxCancel), {
									key: 0,
									"as-child": ""
								}, {
									default: withCtx(() => [createVNode(_sfc_main$5, mergeProps({
										as: "span",
										icon: __props.clearIcon || unref(appConfig).ui.icons.close,
										size: selectSize.value,
										variant: "link",
										color: "neutral",
										tabindex: "-1"
									}, clearProps.value, {
										"data-slot": "trailingClear",
										class: ui.value.trailingClear({ class: unref(uiProp)?.trailingClear }),
										onClick: withModifiers(onClear, ["stop"])
									}), null, 16, [
										"icon",
										"size",
										"class"
									])]),
									_: 1
								})) : unref(trailingIconName) ? (openBlock(), createBlock(_sfc_main$2, {
									key: 1,
									name: unref(trailingIconName),
									"data-slot": "trailingIcon",
									class: ui.value.trailingIcon({ class: unref(uiProp)?.trailingIcon })
								}, null, 8, ["name", "class"])) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true)
							]),
							_: 2
						}, 1032, ["class"])]),
						_: 2
					}, 1024), createVNode(unref(ComboboxPortal), unref(portalProps), {
						default: withCtx(() => [createVNode(unref(FieldGroupReset), null, {
							default: withCtx(() => [createVNode(unref(ComboboxContent), mergeProps({
								"data-slot": "content",
								class: ui.value.content({ class: unref(uiProp)?.content })
							}, contentProps.value), {
								default: withCtx(() => [createVNode(unref(FocusScope), {
									trapped: "",
									"data-slot": "focusScope",
									class: ui.value.focusScope({ class: unref(uiProp)?.focusScope })
								}, {
									default: withCtx(() => [
										renderSlot(_ctx.$slots, "content-top"),
										!!__props.searchInput ? (openBlock(), createBlock(unref(ComboboxInput), {
											key: 0,
											modelValue: searchTerm.value,
											"onUpdate:modelValue": ($event) => searchTerm.value = $event,
											"display-value": () => searchTerm.value,
											"as-child": ""
										}, {
											default: withCtx(() => [createVNode(_sfc_main$6, mergeProps({
												autofocus: "",
												autocomplete: "off",
												size: selectSize.value
											}, searchInputProps.value, {
												"model-modifiers": { trim: __props.modelModifiers?.trim },
												"data-slot": "input",
												class: ui.value.input({ class: unref(uiProp)?.input }),
												onChange: withModifiers(() => {}, ["stop"])
											}), null, 16, [
												"size",
												"model-modifiers",
												"class",
												"onChange"
											])]),
											_: 1
										}, 8, [
											"modelValue",
											"onUpdate:modelValue",
											"display-value"
										])) : createCommentVNode("", true),
										createVNode(unref(ComboboxEmpty), {
											"data-slot": "empty",
											class: ui.value.empty({ class: unref(uiProp)?.empty })
										}, {
											default: withCtx(() => [renderSlot(_ctx.$slots, "empty", { searchTerm: searchTerm.value }, () => [createTextVNode(toDisplayString(searchTerm.value ? unref(t)("selectMenu.noMatch", { searchTerm: searchTerm.value }) : unref(t)("selectMenu.noData")), 1)])]),
											_: 3
										}, 8, ["class"]),
										createVNode("div", {
											ref_key: "viewportRef",
											ref: viewportRef,
											role: "presentation",
											"data-slot": "viewport",
											class: ui.value.viewport({ class: unref(uiProp)?.viewport })
										}, [!!__props.virtualize ? (openBlock(), createBlock(Fragment, { key: 0 }, [
											createItem.value && createItemPosition.value === "top" ? (openBlock(), createBlock(unref(ReuseCreateItemTemplate), { key: 0 })) : createCommentVNode("", true),
											createVNode(unref(ComboboxVirtualizer), mergeProps({
												options: filteredItems.value,
												"text-content": (item2) => isSelectItem(item2) ? unref(get)(item2, props.labelKey) : String(item2)
											}, virtualizerProps.value), {
												default: withCtx(({ option: item, virtualItem }) => [createVNode(unref(ReuseItemTemplate), {
													item,
													index: virtualItem.index
												}, null, 8, ["item", "index"])]),
												_: 1
											}, 16, ["options", "text-content"]),
											createItem.value && createItemPosition.value === "bottom" ? (openBlock(), createBlock(unref(ReuseCreateItemTemplate), { key: 1 })) : createCommentVNode("", true)
										], 64)) : (openBlock(), createBlock(Fragment, { key: 1 }, [
											createItem.value && createItemPosition.value === "top" ? (openBlock(), createBlock(unref(ComboboxGroup), {
												key: 0,
												"data-slot": "group",
												class: ui.value.group({ class: unref(uiProp)?.group })
											}, {
												default: withCtx(() => [createVNode(unref(ReuseCreateItemTemplate))]),
												_: 1
											}, 8, ["class"])) : createCommentVNode("", true),
											(openBlock(true), createBlock(Fragment, null, renderList(filteredGroups.value, (group, groupIndex) => {
												return openBlock(), createBlock(unref(ComboboxGroup), {
													key: `group-${groupIndex}`,
													"data-slot": "group",
													class: ui.value.group({ class: unref(uiProp)?.group })
												}, {
													default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(group, (item, index) => {
														return openBlock(), createBlock(unref(ReuseItemTemplate), {
															key: `group-${groupIndex}-${index}`,
															item,
															index
														}, null, 8, ["item", "index"]);
													}), 128))]),
													_: 2
												}, 1032, ["class"]);
											}), 128)),
											createItem.value && createItemPosition.value === "bottom" ? (openBlock(), createBlock(unref(ComboboxGroup), {
												key: 1,
												"data-slot": "group",
												class: ui.value.group({ class: unref(uiProp)?.group })
											}, {
												default: withCtx(() => [createVNode(unref(ReuseCreateItemTemplate))]),
												_: 1
											}, 8, ["class"])) : createCommentVNode("", true)
										], 64))], 2),
										renderSlot(_ctx.$slots, "content-bottom")
									]),
									_: 3
								}, 8, ["class"]), !!__props.arrow ? (openBlock(), createBlock(unref(ComboboxArrow), mergeProps({ key: 0 }, arrowProps.value, {
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
			_push(`<!--]-->`);
		};
	}
});
var _sfc_setup$2 = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/SelectMenu.vue");
	return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
//#endregion
//#region resources/js/Components/Forms/FgPasswordConfirmationInput.vue?vue&type=script&setup=true&lang.ts
var FgPasswordConfirmationInput_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "FgPasswordConfirmationInput",
	__ssrInlineRender: true,
	props: /* @__PURE__ */ mergeModels({
		passwordLabel: {},
		confirmationLabel: {},
		passwordError: { default: null },
		confirmationError: { default: null },
		validation: {},
		required: {
			type: Boolean,
			default: true
		},
		minScore: { default: 5 }
	}, {
		"password": { default: "" },
		"passwordModifiers": {},
		"confirmation": { default: "" },
		"confirmationModifiers": {}
	}),
	emits: /* @__PURE__ */ mergeModels(["valid", "score"], ["update:password", "update:confirmation"]),
	setup(__props, { emit: __emit }) {
		const password = useModel(__props, "password");
		const confirmation = useModel(__props, "confirmation");
		const props = __props;
		const emit = __emit;
		const showPassword = ref(false);
		const showConfirmation = ref(false);
		const requirements = computed(() => [
			{
				regex: /.{8,}/,
				text: props.validation.length
			},
			{
				regex: /\d/,
				text: props.validation.number
			},
			{
				regex: /[a-z]/,
				text: props.validation.lowercase
			},
			{
				regex: /[A-Z]/,
				text: props.validation.uppercase
			},
			{
				regex: /[!"#$%&'()*+,\-./:;<=>?@[\\\]^_`{|}~]/,
				text: props.validation.symbol
			}
		]);
		const strength = computed(() => {
			return requirements.value.map((requirements) => ({
				met: requirements.regex.test(password.value ?? ""),
				text: requirements.text
			}));
		});
		const score = computed(() => {
			return strength.value.filter((requirement) => requirement.met).length;
		});
		const passwordsMatch = computed(() => {
			return password.value !== "" && confirmation.value !== "" && password.value === confirmation.value;
		});
		const isValid = computed(() => {
			return score.value >= props.minScore && passwordsMatch.value;
		});
		const passwordColor = computed(() => {
			if (score.value === 0) return "neutral";
			if (score.value <= 1) return "error";
			if (score.value <= 4) return "warning";
			return "success";
		});
		const confirmationColor = computed(() => {
			if (confirmation.value === "") return "neutral";
			return passwordsMatch.value ? "success" : "error";
		});
		const validatorText = computed(() => {
			if (score.value === 0) return "Saisissez un mot de passe";
			if (score.value <= 1) return "Mot de passe faible";
			if (score.value <= 4) return "Mot de passe moyen";
			return "Mot de passe fort";
		});
		const confirmationText = computed(() => {
			return passwordsMatch.value ? "Les mots de passe correspondent." : props.validation.confirmed;
		});
		watch(isValid, (value) => emit("valid", value), { immediate: true });
		watch(score, (value) => emit("score", value), { immediate: true });
		return (_ctx, _push, _parent, _attrs) => {
			const _component_UFormField = _sfc_main$7;
			const _component_UInput = _sfc_main$6;
			const _component_UButton = _sfc_main$5;
			const _component_UProgress = _sfc_main$9;
			const _component_UIcon = _sfc_main$2;
			_push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-4" }, _attrs))}><div>`);
			_push(ssrRenderComponent(_component_UFormField, {
				label: __props.passwordLabel,
				error: __props.passwordError,
				required: __props.required,
				name: "password"
			}, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(ssrRenderComponent(_component_UInput, {
						modelValue: password.value,
						"onUpdate:modelValue": ($event) => password.value = $event,
						color: passwordColor.value,
						type: showPassword.value ? "text" : "password",
						"aria-invalid": score.value < __props.minScore,
						"aria-describedby": "password-strength",
						ui: { trailing: "pe-1" },
						class: "w-full"
					}, {
						trailing: withCtx((_, _push, _parent, _scopeId) => {
							if (_push) _push(ssrRenderComponent(_component_UButton, {
								color: "neutral",
								variant: "link",
								size: "sm",
								icon: showPassword.value ? "i-lucide-eye-off" : "i-lucide-eye",
								"aria-label": showPassword.value ? "Masquer le mot de passe" : "Afficher le mot de passe",
								"aria-pressed": showPassword.value,
								type: "button",
								onClick: ($event) => showPassword.value = !showPassword.value
							}, null, _parent, _scopeId));
							else return [createVNode(_component_UButton, {
								color: "neutral",
								variant: "link",
								size: "sm",
								icon: showPassword.value ? "i-lucide-eye-off" : "i-lucide-eye",
								"aria-label": showPassword.value ? "Masquer le mot de passe" : "Afficher le mot de passe",
								"aria-pressed": showPassword.value,
								type: "button",
								onClick: ($event) => showPassword.value = !showPassword.value
							}, null, 8, [
								"icon",
								"aria-label",
								"aria-pressed",
								"onClick"
							])];
						}),
						_: 1
					}, _parent, _scopeId));
					else return [createVNode(_component_UInput, {
						modelValue: password.value,
						"onUpdate:modelValue": ($event) => password.value = $event,
						color: passwordColor.value,
						type: showPassword.value ? "text" : "password",
						"aria-invalid": score.value < __props.minScore,
						"aria-describedby": "password-strength",
						ui: { trailing: "pe-1" },
						class: "w-full"
					}, {
						trailing: withCtx(() => [createVNode(_component_UButton, {
							color: "neutral",
							variant: "link",
							size: "sm",
							icon: showPassword.value ? "i-lucide-eye-off" : "i-lucide-eye",
							"aria-label": showPassword.value ? "Masquer le mot de passe" : "Afficher le mot de passe",
							"aria-pressed": showPassword.value,
							type: "button",
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
				_: 1
			}, _parent));
			_push(ssrRenderComponent(_component_UProgress, {
				color: passwordColor.value,
				indicator: validatorText.value,
				"model-value": score.value,
				max: 5,
				size: "sm",
				class: "mt-1"
			}, null, _parent));
			_push(`<p id="password-strength" class="mt-2 text-sm font-medium">${ssrInterpolate(validatorText.value)}`);
			if (score.value < 5) _push(`<span>. Doit contenir :</span>`);
			else _push(`<!---->`);
			_push(`</p><ul class="mt-2 space-y-1" aria-label="Exigences du mot de passe"><!--[-->`);
			ssrRenderList(strength.value, (requirement, index) => {
				_push(`<li class="${ssrRenderClass([requirement.met ? "text-success" : "text-muted", "flex items-center gap-1"])}">`);
				_push(ssrRenderComponent(_component_UIcon, {
					name: requirement.met ? "i-lucide-circle-check" : "i-lucide-circle-x",
					class: "size-4 shrink-0"
				}, null, _parent));
				_push(`<span class="text-sm font-light">${ssrInterpolate(requirement.text)}</span></li>`);
			});
			_push(`<!--]--></ul></div>`);
			if (score.value >= __props.minScore) _push(ssrRenderComponent(_component_UFormField, {
				label: __props.confirmationLabel,
				error: __props.confirmationError,
				required: __props.required,
				name: "password_confirmation"
			}, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(ssrRenderComponent(_component_UInput, {
							modelValue: confirmation.value,
							"onUpdate:modelValue": ($event) => confirmation.value = $event,
							color: confirmationColor.value,
							type: showConfirmation.value ? "text" : "password",
							"aria-describedby": "password-confirmation",
							ui: { trailing: "pe-1" },
							class: "w-full"
						}, {
							trailing: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(_component_UButton, {
									color: "neutral",
									variant: "link",
									size: "sm",
									icon: showConfirmation.value ? "i-lucide-eye-off" : "i-lucide-eye",
									"aria-label": showConfirmation.value ? "Masquer le mot de passe" : "Afficher le mot de passe",
									"aria-pressed": showConfirmation.value,
									type: "button",
									onClick: ($event) => showConfirmation.value = !showConfirmation.value
								}, null, _parent, _scopeId));
								else return [createVNode(_component_UButton, {
									color: "neutral",
									variant: "link",
									size: "sm",
									icon: showConfirmation.value ? "i-lucide-eye-off" : "i-lucide-eye",
									"aria-label": showConfirmation.value ? "Masquer le mot de passe" : "Afficher le mot de passe",
									"aria-pressed": showConfirmation.value,
									type: "button",
									onClick: ($event) => showConfirmation.value = !showConfirmation.value
								}, null, 8, [
									"icon",
									"aria-label",
									"aria-pressed",
									"onClick"
								])];
							}),
							_: 1
						}, _parent, _scopeId));
						if (confirmation.value !== "") _push(`<p id="password-confirmation" class="${ssrRenderClass([passwordsMatch.value ? "text-success/80" : "text-danger/80", "mt-1 text-sm font-medium"])}"${_scopeId}>${ssrInterpolate(confirmationText.value)}</p>`);
						else _push(`<!---->`);
					} else return [createVNode(_component_UInput, {
						modelValue: confirmation.value,
						"onUpdate:modelValue": ($event) => confirmation.value = $event,
						color: confirmationColor.value,
						type: showConfirmation.value ? "text" : "password",
						"aria-describedby": "password-confirmation",
						ui: { trailing: "pe-1" },
						class: "w-full"
					}, {
						trailing: withCtx(() => [createVNode(_component_UButton, {
							color: "neutral",
							variant: "link",
							size: "sm",
							icon: showConfirmation.value ? "i-lucide-eye-off" : "i-lucide-eye",
							"aria-label": showConfirmation.value ? "Masquer le mot de passe" : "Afficher le mot de passe",
							"aria-pressed": showConfirmation.value,
							type: "button",
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
						"type"
					]), confirmation.value !== "" ? (openBlock(), createBlock("p", {
						key: 0,
						id: "password-confirmation",
						class: ["mt-1 text-sm font-medium", passwordsMatch.value ? "text-success/80" : "text-danger/80"]
					}, toDisplayString(confirmationText.value), 3)) : createCommentVNode("", true)];
				}),
				_: 1
			}, _parent));
			else _push(`<!---->`);
			_push(`</div>`);
		};
	}
});
//#endregion
//#region resources/js/Components/Forms/FgPasswordConfirmationInput.vue
var _sfc_setup$1 = FgPasswordConfirmationInput_vue_vue_type_script_setup_true_lang_default.setup;
FgPasswordConfirmationInput_vue_vue_type_script_setup_true_lang_default.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Forms/FgPasswordConfirmationInput.vue");
	return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
var FgPasswordConfirmationInput_default = FgPasswordConfirmationInput_vue_vue_type_script_setup_true_lang_default;
//#endregion
//#region resources/js/Pages/Dashboard/Users/Partials/UserQuickCreateForm.vue?vue&type=script&setup=true&lang.ts
var UserQuickCreateForm_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "UserQuickCreateForm",
	__ssrInlineRender: true,
	props: {
		__: {},
		countries: {},
		roles: {},
		rolesLoading: { type: Boolean }
	},
	emits: ["created"],
	setup(__props, { expose: __expose, emit: __emit }) {
		const props = __props;
		const emit = __emit;
		const toast = useToast();
		const schema = z.object({
			username: z.string(props.__.errors.inputs.string).min(1),
			email: z.email(props.__.errors.inputs.email_invalid),
			first_name: z.string().nullable().optional(),
			last_name: z.string().nullable().optional(),
			job_title: z.string().nullable().optional(),
			phone: z.object({
				extension: z.string().nullable().optional(),
				number: z.string().nullable().optional()
			}),
			password: z.string().min(6),
			password_confirmation: z.string().min(6),
			active: z.boolean(),
			roles: z.array(z.any()).optional(),
			admin_notes: z.string().nullable().optional()
		}).refine((data) => data.password === data.password_confirmation, {
			path: ["password_confirmation"],
			message: props.__.resources.users.validation.password.confirmed
		});
		const form = useForm({
			username: "",
			email: "",
			first_name: "",
			last_name: "",
			job_title: "",
			phone: {
				extension: "",
				number: ""
			},
			password: "",
			password_confirmation: "",
			active: true,
			roles: [],
			admin_notes: ""
		});
		const passwordValid = ref(false);
		const canSubmit = computed(() => {
			return form.username.trim() !== "" && form.email.trim() !== "" && form.password.trim() !== "" && form.password_confirmation.trim() !== "" && passwordValid.value;
		});
		function open() {
			form.defaults({
				username: "",
				email: "",
				first_name: "",
				last_name: "",
				job_title: "",
				phone: {
					extension: "",
					number: ""
				},
				password: "",
				password_confirmation: "",
				active: true,
				roles: [],
				admin_notes: ""
			});
			form.reset();
			form.clearErrors();
		}
		function close() {
			form.reset();
			form.clearErrors();
		}
		function submit() {
			form.clearErrors();
			const result = schema.safeParse(form.data());
			if (!result.success) {
				const errors = result.error.flatten().fieldErrors;
				Object.entries(errors).forEach(([field, messages]) => {
					form.setError(field, messages?.[0] ?? "");
				});
				return;
			}
			form.transform(() => ({
				...result.data,
				roles: (result.data.roles ?? []).map((role) => ({ id: role.id ?? role.value }))
			})).post("/users", {
				only: [
					"users",
					"filters",
					"flash"
				],
				preserveScroll: true,
				preserveState: true,
				onSuccess: () => {
					close();
					emit("created");
				},
				onError: (errors) => {
					if (errors?.action) toast.add({
						title: errors.action,
						color: "error"
					});
				}
			});
		}
		__expose({
			open,
			close,
			submit,
			processing: computed(() => form.processing),
			disabled: computed(() => !canSubmit.value),
			dirty: computed(() => form.isDirty)
		});
		return (_ctx, _push, _parent, _attrs) => {
			const _component_UForm = _sfc_main$8;
			const _component_UFormField = _sfc_main$7;
			const _component_UInput = _sfc_main$6;
			const _component_USelectMenu = _sfc_main;
			const _component_USwitch = _sfc_main$1;
			const _component_UTextarea = _sfc_main$10;
			_push(ssrRenderComponent(_component_UForm, mergeProps({
				id: "quick-create-form",
				class: "flex flex-col gap-4",
				schema: unref(schema),
				state: unref(form),
				onSubmit: submit
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(ssrRenderComponent(_component_UFormField, {
							label: props.__.resources.users.fields.username,
							error: unref(form).errors.username,
							required: "",
							name: "username"
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(_component_UInput, {
									modelValue: unref(form).username,
									"onUpdate:modelValue": ($event) => unref(form).username = $event,
									required: "",
									class: "w-full",
									size: "lg"
								}, null, _parent, _scopeId));
								else return [createVNode(_component_UInput, {
									modelValue: unref(form).username,
									"onUpdate:modelValue": ($event) => unref(form).username = $event,
									required: "",
									class: "w-full",
									size: "lg"
								}, null, 8, ["modelValue", "onUpdate:modelValue"])];
							}),
							_: 1
						}, _parent, _scopeId));
						_push(ssrRenderComponent(_component_UFormField, {
							label: props.__.resources.users.fields.email,
							error: unref(form).errors.email,
							required: "",
							name: "email"
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(_component_UInput, {
									modelValue: unref(form).email,
									"onUpdate:modelValue": ($event) => unref(form).email = $event,
									required: "",
									class: "w-full",
									size: "lg"
								}, null, _parent, _scopeId));
								else return [createVNode(_component_UInput, {
									modelValue: unref(form).email,
									"onUpdate:modelValue": ($event) => unref(form).email = $event,
									required: "",
									class: "w-full",
									size: "lg"
								}, null, 8, ["modelValue", "onUpdate:modelValue"])];
							}),
							_: 1
						}, _parent, _scopeId));
						_push(ssrRenderComponent(FgPasswordConfirmationInput_default, {
							password: unref(form).password,
							"onUpdate:password": ($event) => unref(form).password = $event,
							confirmation: unref(form).password_confirmation,
							"onUpdate:confirmation": ($event) => unref(form).password_confirmation = $event,
							"password-label": props.__.resources.users.fields.password,
							"confirmation-label": props.__.resources.users.fields.password_confirmation,
							"password-error": unref(form).errors.password,
							"confirmation-error": unref(form).errors.password_confirmation,
							validation: props.__.resources.users.validation.password,
							onValid: ($event) => passwordValid.value = $event
						}, null, _parent, _scopeId));
						_push(ssrRenderComponent(_component_UFormField, {
							label: props.__.resources.users.fields.first_name,
							error: unref(form).errors.first_name,
							name: "first_name"
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(_component_UInput, {
									modelValue: unref(form).first_name,
									"onUpdate:modelValue": ($event) => unref(form).first_name = $event,
									class: "w-full"
								}, null, _parent, _scopeId));
								else return [createVNode(_component_UInput, {
									modelValue: unref(form).first_name,
									"onUpdate:modelValue": ($event) => unref(form).first_name = $event,
									class: "w-full"
								}, null, 8, ["modelValue", "onUpdate:modelValue"])];
							}),
							_: 1
						}, _parent, _scopeId));
						_push(ssrRenderComponent(_component_UFormField, {
							label: props.__.resources.users.fields.last_name,
							error: unref(form).errors.last_name,
							name: "last_name"
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(_component_UInput, {
									modelValue: unref(form).last_name,
									"onUpdate:modelValue": ($event) => unref(form).last_name = $event,
									class: "w-full"
								}, null, _parent, _scopeId));
								else return [createVNode(_component_UInput, {
									modelValue: unref(form).last_name,
									"onUpdate:modelValue": ($event) => unref(form).last_name = $event,
									class: "w-full"
								}, null, 8, ["modelValue", "onUpdate:modelValue"])];
							}),
							_: 1
						}, _parent, _scopeId));
						_push(ssrRenderComponent(_component_UFormField, {
							label: props.__.resources.users.fields.job_title,
							error: unref(form).errors.job_title,
							name: "job_title"
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(_component_UInput, {
									modelValue: unref(form).job_title,
									"onUpdate:modelValue": ($event) => unref(form).job_title = $event,
									class: "w-full"
								}, null, _parent, _scopeId));
								else return [createVNode(_component_UInput, {
									modelValue: unref(form).job_title,
									"onUpdate:modelValue": ($event) => unref(form).job_title = $event,
									class: "w-full"
								}, null, 8, ["modelValue", "onUpdate:modelValue"])];
							}),
							_: 1
						}, _parent, _scopeId));
						_push(ssrRenderComponent(_component_UFormField, {
							label: props.__.resources.users.fields.phone.label,
							error: unref(form).errors["phone.extension"] || unref(form).errors["phone.number"],
							name: "phone"
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(_sfc_main$11, {
									modelValue: unref(form).phone,
									"onUpdate:modelValue": ($event) => unref(form).phone = $event,
									countries: __props.countries,
									size: "lg",
									placeholder: props.__.resources.users.fields.phone.label
								}, null, _parent, _scopeId));
								else return [createVNode(_sfc_main$11, {
									modelValue: unref(form).phone,
									"onUpdate:modelValue": ($event) => unref(form).phone = $event,
									countries: __props.countries,
									size: "lg",
									placeholder: props.__.resources.users.fields.phone.label
								}, null, 8, [
									"modelValue",
									"onUpdate:modelValue",
									"countries",
									"placeholder"
								])];
							}),
							_: 1
						}, _parent, _scopeId));
						_push(ssrRenderComponent(_component_UFormField, {
							label: props.__.resources.users.fields.roles,
							error: unref(form).errors.roles,
							name: "roles"
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(_component_USelectMenu, {
									modelValue: unref(form).roles,
									"onUpdate:modelValue": ($event) => unref(form).roles = $event,
									"filter-fields": ["label", "project"],
									items: __props.roles,
									loading: __props.rolesLoading,
									multiple: "",
									class: "w-full"
								}, null, _parent, _scopeId));
								else return [createVNode(_component_USelectMenu, {
									modelValue: unref(form).roles,
									"onUpdate:modelValue": ($event) => unref(form).roles = $event,
									"filter-fields": ["label", "project"],
									items: __props.roles,
									loading: __props.rolesLoading,
									multiple: "",
									class: "w-full"
								}, null, 8, [
									"modelValue",
									"onUpdate:modelValue",
									"items",
									"loading"
								])];
							}),
							_: 1
						}, _parent, _scopeId));
						_push(ssrRenderComponent(_component_UFormField, {
							label: props.__.resources.users.fields.active,
							error: unref(form).errors.active,
							name: "active"
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(_component_USwitch, {
									modelValue: unref(form).active,
									"onUpdate:modelValue": ($event) => unref(form).active = $event,
									color: "success"
								}, null, _parent, _scopeId));
								else return [createVNode(_component_USwitch, {
									modelValue: unref(form).active,
									"onUpdate:modelValue": ($event) => unref(form).active = $event,
									color: "success"
								}, null, 8, ["modelValue", "onUpdate:modelValue"])];
							}),
							_: 1
						}, _parent, _scopeId));
						_push(ssrRenderComponent(_component_UFormField, {
							label: props.__.resources.users.fields.admin_notes,
							help: props.__.forms.users.help.admin_notes,
							error: unref(form).errors.admin_notes,
							name: "admin_notes"
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(_component_UTextarea, {
									modelValue: unref(form).admin_notes,
									"onUpdate:modelValue": ($event) => unref(form).admin_notes = $event,
									ui: { base: "peer min-h-37.5" },
									class: "w-full"
								}, null, _parent, _scopeId));
								else return [createVNode(_component_UTextarea, {
									modelValue: unref(form).admin_notes,
									"onUpdate:modelValue": ($event) => unref(form).admin_notes = $event,
									ui: { base: "peer min-h-37.5" },
									class: "w-full"
								}, null, 8, ["modelValue", "onUpdate:modelValue"])];
							}),
							_: 1
						}, _parent, _scopeId));
					} else return [
						createVNode(_component_UFormField, {
							label: props.__.resources.users.fields.username,
							error: unref(form).errors.username,
							required: "",
							name: "username"
						}, {
							default: withCtx(() => [createVNode(_component_UInput, {
								modelValue: unref(form).username,
								"onUpdate:modelValue": ($event) => unref(form).username = $event,
								required: "",
								class: "w-full",
								size: "lg"
							}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						createVNode(_component_UFormField, {
							label: props.__.resources.users.fields.email,
							error: unref(form).errors.email,
							required: "",
							name: "email"
						}, {
							default: withCtx(() => [createVNode(_component_UInput, {
								modelValue: unref(form).email,
								"onUpdate:modelValue": ($event) => unref(form).email = $event,
								required: "",
								class: "w-full",
								size: "lg"
							}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						createVNode(FgPasswordConfirmationInput_default, {
							password: unref(form).password,
							"onUpdate:password": ($event) => unref(form).password = $event,
							confirmation: unref(form).password_confirmation,
							"onUpdate:confirmation": ($event) => unref(form).password_confirmation = $event,
							"password-label": props.__.resources.users.fields.password,
							"confirmation-label": props.__.resources.users.fields.password_confirmation,
							"password-error": unref(form).errors.password,
							"confirmation-error": unref(form).errors.password_confirmation,
							validation: props.__.resources.users.validation.password,
							onValid: ($event) => passwordValid.value = $event
						}, null, 8, [
							"password",
							"onUpdate:password",
							"confirmation",
							"onUpdate:confirmation",
							"password-label",
							"confirmation-label",
							"password-error",
							"confirmation-error",
							"validation",
							"onValid"
						]),
						createVNode(_component_UFormField, {
							label: props.__.resources.users.fields.first_name,
							error: unref(form).errors.first_name,
							name: "first_name"
						}, {
							default: withCtx(() => [createVNode(_component_UInput, {
								modelValue: unref(form).first_name,
								"onUpdate:modelValue": ($event) => unref(form).first_name = $event,
								class: "w-full"
							}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						createVNode(_component_UFormField, {
							label: props.__.resources.users.fields.last_name,
							error: unref(form).errors.last_name,
							name: "last_name"
						}, {
							default: withCtx(() => [createVNode(_component_UInput, {
								modelValue: unref(form).last_name,
								"onUpdate:modelValue": ($event) => unref(form).last_name = $event,
								class: "w-full"
							}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						createVNode(_component_UFormField, {
							label: props.__.resources.users.fields.job_title,
							error: unref(form).errors.job_title,
							name: "job_title"
						}, {
							default: withCtx(() => [createVNode(_component_UInput, {
								modelValue: unref(form).job_title,
								"onUpdate:modelValue": ($event) => unref(form).job_title = $event,
								class: "w-full"
							}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						createVNode(_component_UFormField, {
							label: props.__.resources.users.fields.phone.label,
							error: unref(form).errors["phone.extension"] || unref(form).errors["phone.number"],
							name: "phone"
						}, {
							default: withCtx(() => [createVNode(_sfc_main$11, {
								modelValue: unref(form).phone,
								"onUpdate:modelValue": ($event) => unref(form).phone = $event,
								countries: __props.countries,
								size: "lg",
								placeholder: props.__.resources.users.fields.phone.label
							}, null, 8, [
								"modelValue",
								"onUpdate:modelValue",
								"countries",
								"placeholder"
							])]),
							_: 1
						}, 8, ["label", "error"]),
						createVNode(_component_UFormField, {
							label: props.__.resources.users.fields.roles,
							error: unref(form).errors.roles,
							name: "roles"
						}, {
							default: withCtx(() => [createVNode(_component_USelectMenu, {
								modelValue: unref(form).roles,
								"onUpdate:modelValue": ($event) => unref(form).roles = $event,
								"filter-fields": ["label", "project"],
								items: __props.roles,
								loading: __props.rolesLoading,
								multiple: "",
								class: "w-full"
							}, null, 8, [
								"modelValue",
								"onUpdate:modelValue",
								"items",
								"loading"
							])]),
							_: 1
						}, 8, ["label", "error"]),
						createVNode(_component_UFormField, {
							label: props.__.resources.users.fields.active,
							error: unref(form).errors.active,
							name: "active"
						}, {
							default: withCtx(() => [createVNode(_component_USwitch, {
								modelValue: unref(form).active,
								"onUpdate:modelValue": ($event) => unref(form).active = $event,
								color: "success"
							}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						createVNode(_component_UFormField, {
							label: props.__.resources.users.fields.admin_notes,
							help: props.__.forms.users.help.admin_notes,
							error: unref(form).errors.admin_notes,
							name: "admin_notes"
						}, {
							default: withCtx(() => [createVNode(_component_UTextarea, {
								modelValue: unref(form).admin_notes,
								"onUpdate:modelValue": ($event) => unref(form).admin_notes = $event,
								ui: { base: "peer min-h-37.5" },
								class: "w-full"
							}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"help",
							"error"
						])
					];
				}),
				_: 1
			}, _parent));
		};
	}
});
//#endregion
//#region resources/js/Pages/Dashboard/Users/Partials/UserQuickCreateForm.vue
var _sfc_setup = UserQuickCreateForm_vue_vue_type_script_setup_true_lang_default.setup;
UserQuickCreateForm_vue_vue_type_script_setup_true_lang_default.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Dashboard/Users/Partials/UserQuickCreateForm.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
var UserQuickCreateForm_default = UserQuickCreateForm_vue_vue_type_script_setup_true_lang_default;
//#endregion
export { _sfc_main$1 as i, _sfc_main as n, useFilter$1 as r, UserQuickCreateForm_default as t };

//# sourceMappingURL=UserQuickCreateForm-DF3WcUz2.js.map