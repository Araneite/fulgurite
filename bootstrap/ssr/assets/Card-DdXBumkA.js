import { F as useAppConfig, u as tv, w as useComponentUI } from "./usePortal-DZb6nPjI.js";
import { ssrInterpolate, ssrRenderClass, ssrRenderComponent, ssrRenderSlot } from "vue/server-renderer";
import { computed, createBlock, createCommentVNode, createTextVNode, mergeProps, openBlock, renderSlot, toDisplayString, unref, useSSRContext, useSlots, withCtx } from "vue";
import { Primitive } from "reka-ui";
//#region virtual:nuxt-ui-templates/ui/card.ts
var card_default = {
	"slots": {
		"root": "rounded-lg overflow-hidden",
		"header": "p-4 sm:px-6",
		"title": "text-highlighted font-semibold",
		"description": "mt-1 text-muted text-sm",
		"body": "p-4 sm:p-6",
		"footer": "p-4 sm:px-6"
	},
	"variants": { "variant": {
		"solid": {
			"root": "bg-inverted text-inverted",
			"title": "text-inverted",
			"description": "text-dimmed"
		},
		"outline": { "root": "bg-default ring ring-default divide-y divide-default" },
		"soft": { "root": "bg-elevated/50 divide-y divide-default" },
		"subtle": { "root": "bg-elevated/50 ring ring-default divide-y divide-default" }
	} },
	"defaultVariants": { "variant": "outline" }
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Card.vue
var _sfc_main = {
	__name: "Card",
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
		variant: {
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
		const uiProp = useComponentUI("card", props);
		const ui = computed(() => tv({
			extend: tv(card_default),
			...appConfig.ui?.card || {}
		})({ variant: props.variant }));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(Primitive), mergeProps({
				as: __props.as,
				"data-slot": "root",
				class: ui.value.root({ class: [unref(uiProp)?.root, props.class] })
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						if (!!slots.header || __props.title || !!slots.title || __props.description || !!slots.description) {
							_push(`<div data-slot="header" class="${ssrRenderClass(ui.value.header({ class: unref(uiProp)?.header }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "header", {}, () => {
								if (__props.title || !!slots.title) {
									_push(`<div data-slot="title" class="${ssrRenderClass(ui.value.title({ class: unref(uiProp)?.title }))}"${_scopeId}>`);
									ssrRenderSlot(_ctx.$slots, "title", {}, () => {
										_push(`${ssrInterpolate(__props.title)}`);
									}, _push, _parent, _scopeId);
									_push(`</div>`);
								} else _push(`<!---->`);
								if (__props.description || !!slots.description) {
									_push(`<div data-slot="description" class="${ssrRenderClass(ui.value.description({ class: unref(uiProp)?.description }))}"${_scopeId}>`);
									ssrRenderSlot(_ctx.$slots, "description", {}, () => {
										_push(`${ssrInterpolate(__props.description)}`);
									}, _push, _parent, _scopeId);
									_push(`</div>`);
								} else _push(`<!---->`);
							}, _push, _parent, _scopeId);
							_push(`</div>`);
						} else _push(`<!---->`);
						if (!!slots.default) {
							_push(`<div data-slot="body" class="${ssrRenderClass(ui.value.body({ class: unref(uiProp)?.body }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
							_push(`</div>`);
						} else _push(`<!---->`);
						if (!!slots.footer) {
							_push(`<div data-slot="footer" class="${ssrRenderClass(ui.value.footer({ class: unref(uiProp)?.footer }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "footer", {}, null, _push, _parent, _scopeId);
							_push(`</div>`);
						} else _push(`<!---->`);
					} else return [
						!!slots.header || __props.title || !!slots.title || __props.description || !!slots.description ? (openBlock(), createBlock("div", {
							key: 0,
							"data-slot": "header",
							class: ui.value.header({ class: unref(uiProp)?.header })
						}, [renderSlot(_ctx.$slots, "header", {}, () => [__props.title || !!slots.title ? (openBlock(), createBlock("div", {
							key: 0,
							"data-slot": "title",
							class: ui.value.title({ class: unref(uiProp)?.title })
						}, [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(__props.title), 1)])], 2)) : createCommentVNode("", true), __props.description || !!slots.description ? (openBlock(), createBlock("div", {
							key: 1,
							"data-slot": "description",
							class: ui.value.description({ class: unref(uiProp)?.description })
						}, [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(__props.description), 1)])], 2)) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true),
						!!slots.default ? (openBlock(), createBlock("div", {
							key: 1,
							"data-slot": "body",
							class: ui.value.body({ class: unref(uiProp)?.body })
						}, [renderSlot(_ctx.$slots, "default")], 2)) : createCommentVNode("", true),
						!!slots.footer ? (openBlock(), createBlock("div", {
							key: 2,
							"data-slot": "footer",
							class: ui.value.footer({ class: unref(uiProp)?.footer })
						}, [renderSlot(_ctx.$slots, "footer")], 2)) : createCommentVNode("", true)
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
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Card.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
//#endregion
export { _sfc_main as t };

//# sourceMappingURL=Card-DdXBumkA.js.map