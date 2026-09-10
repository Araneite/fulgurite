import { D as useLocale, F as useAppConfig, u as tv, w as useComponentUI } from "./usePortal-DZb6nPjI.js";
import { ssrInterpolate, ssrRenderClass, ssrRenderComponent, ssrRenderList, ssrRenderSlot, ssrRenderStyle } from "vue/server-renderer";
import { Fragment, computed, createBlock, createCommentVNode, createTextVNode, createVNode, mergeProps, openBlock, renderList, renderSlot, toDisplayString, unref, useSSRContext, useSlots, withCtx } from "vue";
import { Primitive, ProgressIndicator, ProgressRoot, useForwardPropsEmits } from "reka-ui";
import { reactivePick } from "@vueuse/core";
//#region virtual:nuxt-ui-templates/ui/progress.ts
var progress_default = {
	"slots": {
		"root": "gap-2",
		"base": "relative overflow-hidden rounded-full bg-accented",
		"indicator": "rounded-full size-full transition-transform duration-200 ease-out",
		"status": "flex text-dimmed transition-[width] duration-200",
		"steps": "grid items-end",
		"step": "truncate text-end row-start-1 col-start-1 transition-opacity"
	},
	"variants": {
		"animation": {
			"carousel": "",
			"carousel-inverse": "",
			"swing": "",
			"elastic": ""
		},
		"color": {
			"primary": {
				"indicator": "bg-primary",
				"steps": "text-primary"
			},
			"secondary": {
				"indicator": "bg-secondary",
				"steps": "text-secondary"
			},
			"success": {
				"indicator": "bg-success",
				"steps": "text-success"
			},
			"info": {
				"indicator": "bg-info",
				"steps": "text-info"
			},
			"warning": {
				"indicator": "bg-warning",
				"steps": "text-warning"
			},
			"error": {
				"indicator": "bg-error",
				"steps": "text-error"
			},
			"neutral": {
				"indicator": "bg-inverted",
				"steps": "text-inverted"
			}
		},
		"size": {
			"2xs": {
				"status": "text-xs",
				"steps": "text-xs"
			},
			"xs": {
				"status": "text-xs",
				"steps": "text-xs"
			},
			"sm": {
				"status": "text-sm",
				"steps": "text-sm"
			},
			"md": {
				"status": "text-sm",
				"steps": "text-sm"
			},
			"lg": {
				"status": "text-sm",
				"steps": "text-sm"
			},
			"xl": {
				"status": "text-base",
				"steps": "text-base"
			},
			"2xl": {
				"status": "text-base",
				"steps": "text-base"
			}
		},
		"step": {
			"active": { "step": "opacity-100" },
			"first": { "step": "opacity-100 text-muted" },
			"other": { "step": "opacity-0" },
			"last": { "step": "" }
		},
		"orientation": {
			"horizontal": {
				"root": "w-full flex flex-col",
				"base": "w-full",
				"status": "flex-row items-center justify-end min-w-fit"
			},
			"vertical": {
				"root": "h-full flex flex-row-reverse",
				"base": "h-full",
				"status": "flex-col justify-end min-h-fit"
			}
		},
		"inverted": { "true": { "status": "self-end" } }
	},
	"compoundVariants": [
		{
			"inverted": true,
			"orientation": "horizontal",
			"class": {
				"step": "text-start",
				"status": "flex-row-reverse"
			}
		},
		{
			"inverted": true,
			"orientation": "vertical",
			"class": {
				"steps": "items-start",
				"status": "flex-col-reverse"
			}
		},
		{
			"orientation": "horizontal",
			"size": "2xs",
			"class": "h-px"
		},
		{
			"orientation": "horizontal",
			"size": "xs",
			"class": "h-0.5"
		},
		{
			"orientation": "horizontal",
			"size": "sm",
			"class": "h-1"
		},
		{
			"orientation": "horizontal",
			"size": "md",
			"class": "h-2"
		},
		{
			"orientation": "horizontal",
			"size": "lg",
			"class": "h-3"
		},
		{
			"orientation": "horizontal",
			"size": "xl",
			"class": "h-4"
		},
		{
			"orientation": "horizontal",
			"size": "2xl",
			"class": "h-5"
		},
		{
			"orientation": "vertical",
			"size": "2xs",
			"class": "w-px"
		},
		{
			"orientation": "vertical",
			"size": "xs",
			"class": "w-0.5"
		},
		{
			"orientation": "vertical",
			"size": "sm",
			"class": "w-1"
		},
		{
			"orientation": "vertical",
			"size": "md",
			"class": "w-2"
		},
		{
			"orientation": "vertical",
			"size": "lg",
			"class": "w-3"
		},
		{
			"orientation": "vertical",
			"size": "xl",
			"class": "w-4"
		},
		{
			"orientation": "vertical",
			"size": "2xl",
			"class": "w-5"
		},
		{
			"orientation": "horizontal",
			"animation": "carousel",
			"class": { "indicator": "data-[state=indeterminate]:animate-[carousel_2s_ease-in-out_infinite] data-[state=indeterminate]:rtl:animate-[carousel-rtl_2s_ease-in-out_infinite]" }
		},
		{
			"orientation": "vertical",
			"animation": "carousel",
			"class": { "indicator": "data-[state=indeterminate]:animate-[carousel-vertical_2s_ease-in-out_infinite]" }
		},
		{
			"orientation": "horizontal",
			"animation": "carousel-inverse",
			"class": { "indicator": "data-[state=indeterminate]:animate-[carousel-inverse_2s_ease-in-out_infinite] data-[state=indeterminate]:rtl:animate-[carousel-inverse-rtl_2s_ease-in-out_infinite]" }
		},
		{
			"orientation": "vertical",
			"animation": "carousel-inverse",
			"class": { "indicator": "data-[state=indeterminate]:animate-[carousel-inverse-vertical_2s_ease-in-out_infinite]" }
		},
		{
			"orientation": "horizontal",
			"animation": "swing",
			"class": { "indicator": "data-[state=indeterminate]:animate-[swing_2s_ease-in-out_infinite]" }
		},
		{
			"orientation": "vertical",
			"animation": "swing",
			"class": { "indicator": "data-[state=indeterminate]:animate-[swing-vertical_2s_ease-in-out_infinite]" }
		},
		{
			"orientation": "horizontal",
			"animation": "elastic",
			"class": { "indicator": "data-[state=indeterminate]:animate-[elastic_2s_ease-in-out_infinite]" }
		},
		{
			"orientation": "vertical",
			"animation": "elastic",
			"class": { "indicator": "data-[state=indeterminate]:animate-[elastic-vertical_2s_ease-in-out_infinite]" }
		}
	],
	"defaultVariants": {
		"animation": "carousel",
		"color": "primary",
		"size": "md"
	}
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Progress.vue
var _sfc_main = {
	__name: "Progress",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false
		},
		max: {
			type: [Number, Array],
			required: false
		},
		status: {
			type: Boolean,
			required: false
		},
		inverted: {
			type: Boolean,
			required: false,
			default: false
		},
		size: {
			type: null,
			required: false
		},
		color: {
			type: null,
			required: false
		},
		orientation: {
			type: null,
			required: false,
			default: "horizontal"
		},
		animation: {
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
		getValueLabel: {
			type: Function,
			required: false
		},
		getValueText: {
			type: Function,
			required: false
		},
		modelValue: {
			type: [Number, null],
			required: false,
			default: null
		}
	},
	emits: ["update:modelValue", "update:max"],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const slots = useSlots();
		const { dir } = useLocale();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("progress", props);
		const rootProps = useForwardPropsEmits(reactivePick(props, "getValueLabel", "getValueText", "modelValue"), emits);
		const isIndeterminate = computed(() => rootProps.value.modelValue === null);
		const hasSteps = computed(() => Array.isArray(props.max));
		const realMax = computed(() => {
			if (isIndeterminate.value || !props.max) return;
			if (Array.isArray(props.max)) return props.max.length - 1;
			return Number(props.max);
		});
		const percent = computed(() => {
			if (isIndeterminate.value) return;
			switch (true) {
				case rootProps.value.modelValue < 0: return 0;
				case rootProps.value.modelValue > (realMax.value ?? 100): return 100;
				default: return Math.round(rootProps.value.modelValue / (realMax.value ?? 100) * 100);
			}
		});
		const indicatorStyle = computed(() => {
			if (percent.value === void 0) return;
			if (props.orientation === "vertical") return { transform: `translateY(${props.inverted ? "" : "-"}${100 - percent.value}%)` };
			else if (dir.value === "rtl") return { transform: `translateX(${props.inverted ? "-" : ""}${100 - percent.value}%)` };
			else return { transform: `translateX(${props.inverted ? "" : "-"}${100 - percent.value}%)` };
		});
		const statusStyle = computed(() => {
			const value = `${Math.max(percent.value ?? 0, 0)}%`;
			return props.orientation === "vertical" ? { height: value } : { width: value };
		});
		function isActive(index) {
			return index === Number(props.modelValue);
		}
		function isFirst(index) {
			return index === 0;
		}
		function isLast(index) {
			return index === realMax.value;
		}
		function stepVariant(index) {
			index = Number(index);
			if (isActive(index) && !isFirst(index)) return "active";
			if (isFirst(index) && isActive(index)) return "first";
			if (isLast(index) && isActive(index)) return "last";
			return "other";
		}
		const ui = computed(() => tv({
			extend: tv(progress_default),
			...appConfig.ui?.progress || {}
		})({
			animation: props.animation,
			size: props.size,
			color: props.color,
			orientation: props.orientation,
			inverted: props.inverted
		}));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(Primitive), mergeProps({
				as: __props.as,
				"data-orientation": __props.orientation,
				"data-slot": "root",
				class: ui.value.root({ class: [unref(uiProp)?.root, props.class] })
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						if (!isIndeterminate.value && (__props.status || !!slots.status)) {
							_push(`<div data-slot="status" class="${ssrRenderClass(ui.value.status({ class: unref(uiProp)?.status }))}" style="${ssrRenderStyle(statusStyle.value)}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "status", { percent: percent.value }, () => {
								_push(`${ssrInterpolate(percent.value)}% `);
							}, _push, _parent, _scopeId);
							_push(`</div>`);
						} else _push(`<!---->`);
						_push(ssrRenderComponent(unref(ProgressRoot), mergeProps(unref(rootProps), {
							max: realMax.value,
							"data-slot": "base",
							class: ui.value.base({ class: unref(uiProp)?.base }),
							style: { "transform": "translateZ(0)" }
						}), {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(unref(ProgressIndicator), {
									"data-slot": "indicator",
									class: ui.value.indicator({ class: unref(uiProp)?.indicator }),
									style: indicatorStyle.value
								}, null, _parent, _scopeId));
								else return [createVNode(unref(ProgressIndicator), {
									"data-slot": "indicator",
									class: ui.value.indicator({ class: unref(uiProp)?.indicator }),
									style: indicatorStyle.value
								}, null, 8, ["class", "style"])];
							}),
							_: 1
						}, _parent, _scopeId));
						if (hasSteps.value) {
							_push(`<div data-slot="steps" class="${ssrRenderClass(ui.value.steps({ class: unref(uiProp)?.steps }))}"${_scopeId}><!--[-->`);
							ssrRenderList(__props.max, (step, index) => {
								_push(`<div data-slot="step" class="${ssrRenderClass(ui.value.step({
									class: unref(uiProp)?.step,
									step: stepVariant(index)
								}))}"${_scopeId}>`);
								ssrRenderSlot(_ctx.$slots, `step-${index}`, { step }, () => {
									_push(`${ssrInterpolate(step)}`);
								}, _push, _parent, _scopeId);
								_push(`</div>`);
							});
							_push(`<!--]--></div>`);
						} else _push(`<!---->`);
					} else return [
						!isIndeterminate.value && (__props.status || !!slots.status) ? (openBlock(), createBlock("div", {
							key: 0,
							"data-slot": "status",
							class: ui.value.status({ class: unref(uiProp)?.status }),
							style: statusStyle.value
						}, [renderSlot(_ctx.$slots, "status", { percent: percent.value }, () => [createTextVNode(toDisplayString(percent.value) + "% ", 1)])], 6)) : createCommentVNode("", true),
						createVNode(unref(ProgressRoot), mergeProps(unref(rootProps), {
							max: realMax.value,
							"data-slot": "base",
							class: ui.value.base({ class: unref(uiProp)?.base }),
							style: { "transform": "translateZ(0)" }
						}), {
							default: withCtx(() => [createVNode(unref(ProgressIndicator), {
								"data-slot": "indicator",
								class: ui.value.indicator({ class: unref(uiProp)?.indicator }),
								style: indicatorStyle.value
							}, null, 8, ["class", "style"])]),
							_: 1
						}, 16, ["max", "class"]),
						hasSteps.value ? (openBlock(), createBlock("div", {
							key: 1,
							"data-slot": "steps",
							class: ui.value.steps({ class: unref(uiProp)?.steps })
						}, [(openBlock(true), createBlock(Fragment, null, renderList(__props.max, (step, index) => {
							return openBlock(), createBlock("div", {
								key: index,
								"data-slot": "step",
								class: ui.value.step({
									class: unref(uiProp)?.step,
									step: stepVariant(index)
								})
							}, [renderSlot(_ctx.$slots, `step-${index}`, { step }, () => [createTextVNode(toDisplayString(step), 1)])], 2);
						}), 128))], 2)) : createCommentVNode("", true)
					];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Progress.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
//#endregion
export { _sfc_main as t };

//# sourceMappingURL=Progress-Cl7OHCpt.js.map