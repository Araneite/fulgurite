import { r as _sfc_main } from "./usePortal-DZb6nPjI.js";
import { t as _sfc_main$1 } from "./Drawer-xm5bIe9t.js";
import { ssrRenderComponent, ssrRenderSlot } from "vue/server-renderer";
import { createVNode, defineComponent, mergeModels, mergeProps, renderSlot, useModel, useSSRContext, withCtx } from "vue";
//#region resources/js/Pages/Dashboard/Users/Partials/UserDrawerShell.vue?vue&type=script&setup=true&lang.ts
var UserDrawerShell_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "UserDrawerShell",
	__ssrInlineRender: true,
	props: /* @__PURE__ */ mergeModels({
		title: {},
		description: { default: "" },
		dismissible: {
			type: Boolean,
			default: true
		},
		closeLabel: {},
		submitLabel: {},
		submitColor: { default: "success" },
		loading: {
			type: Boolean,
			default: false
		},
		disabled: {
			type: Boolean,
			default: false
		}
	}, {
		"open": {
			type: Boolean,
			required: true
		},
		"openModifiers": {}
	}),
	emits: /* @__PURE__ */ mergeModels([
		"close",
		"submit",
		"update:open"
	], ["update:open"]),
	setup(__props, { emit: __emit }) {
		const open = useModel(__props, "open");
		const emit = __emit;
		function handleOpenChange(value) {
			emit("update:open", value);
		}
		return (_ctx, _push, _parent, _attrs) => {
			const _component_UDrawer = _sfc_main$1;
			const _component_UButton = _sfc_main;
			_push(ssrRenderComponent(_component_UDrawer, mergeProps({
				open: open.value,
				dismissible: __props.dismissible,
				direction: "right",
				class: "min-w-80 w-80 md:w-90 lg:w-100",
				title: __props.title,
				description: __props.description,
				ui: { header: "border-b border-white/10 pb-3" },
				"onUpdate:open": handleOpenChange
			}, _attrs), {
				body: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
					else return [renderSlot(_ctx.$slots, "default")];
				}),
				footer: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex justify-end gap-4 w-full"${_scopeId}>`);
						_push(ssrRenderComponent(_component_UButton, {
							color: "neutral",
							variant: "ghost",
							label: __props.closeLabel,
							onClick: ($event) => _ctx.$emit("close")
						}, null, _parent, _scopeId));
						_push(ssrRenderComponent(_component_UButton, {
							color: __props.submitColor,
							label: __props.submitLabel,
							loading: __props.loading,
							disabled: __props.disabled || __props.loading,
							onClick: ($event) => _ctx.$emit("submit")
						}, null, _parent, _scopeId));
						_push(`</div>`);
					} else return [createVNode("div", { class: "flex justify-end gap-4 w-full" }, [createVNode(_component_UButton, {
						color: "neutral",
						variant: "ghost",
						label: __props.closeLabel,
						onClick: ($event) => _ctx.$emit("close")
					}, null, 8, ["label", "onClick"]), createVNode(_component_UButton, {
						color: __props.submitColor,
						label: __props.submitLabel,
						loading: __props.loading,
						disabled: __props.disabled || __props.loading,
						onClick: ($event) => _ctx.$emit("submit")
					}, null, 8, [
						"color",
						"label",
						"loading",
						"disabled",
						"onClick"
					])])];
				}),
				_: 3
			}, _parent));
		};
	}
});
//#endregion
//#region resources/js/Pages/Dashboard/Users/Partials/UserDrawerShell.vue
var _sfc_setup = UserDrawerShell_vue_vue_type_script_setup_true_lang_default.setup;
UserDrawerShell_vue_vue_type_script_setup_true_lang_default.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Dashboard/Users/Partials/UserDrawerShell.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
var UserDrawerShell_default = UserDrawerShell_vue_vue_type_script_setup_true_lang_default;
//#endregion
export { UserDrawerShell_default as t };

//# sourceMappingURL=UserDrawerShell-BgMobjON.js.map