import { c as _sfc_main$1, r as _sfc_main$2 } from "./usePortal-DZb6nPjI.js";
import { t as _sfc_main$3 } from "./Alert-LEzzgm8x.js";
import { t as _sfc_main$4 } from "./Card-DdXBumkA.js";
import { t as _sfc_main$5 } from "./AuthLayout-BCZkOr_K.js";
import { ssrInterpolate, ssrRenderAttr, ssrRenderAttrs, ssrRenderComponent } from "vue/server-renderer";
import { createVNode, mergeProps, toDisplayString, useSSRContext, withCtx } from "vue";
//#region resources/js/Pages/Errors/InvitationUnavailable.vue
var _sfc_main = /* @__PURE__ */ Object.assign({ layout: _sfc_main$5 }, {
	__name: "InvitationUnavailable",
	__ssrInlineRender: true,
	props: {
		reason: {
			type: String,
			required: true
		},
		__: {
			type: Object,
			required: true
		}
	},
	setup(__props) {
		const props = __props;
		const iconByReason = {
			expired: "i-lucide-clock-alert",
			accepted: "i-lucide-check-circle-2",
			revoked: "i-lucide-ban",
			not_found: "i-lucide-link-2-off"
		};
		return (_ctx, _push, _parent, _attrs) => {
			const _component_UCard = _sfc_main$4;
			const _component_UIcon = _sfc_main$1;
			const _component_UAlert = _sfc_main$3;
			const _component_UButton = _sfc_main$2;
			_push(`<div${ssrRenderAttrs(mergeProps({ class: "w-full max-w-md" }, _attrs))}>`);
			_push(ssrRenderComponent(_component_UCard, { ui: {
				root: "bg-darknight/80 ring-white/10",
				body: "p-6 sm:p-7",
				header: "p-6 sm:p-7 border-white/10",
				footer: "p-6 sm:p-7 border-white/10"
			} }, {
				header: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex flex-col items-center text-center"${_scopeId}><img${ssrRenderAttr("src", "/assets/img/fulgurite-logo.svg")} alt="Fulgurite" class="size-14"${_scopeId}><div class="mt-5 flex size-12 items-center justify-center rounded-full bg-red-500/10 text-red-400"${_scopeId}>`);
						_push(ssrRenderComponent(_component_UIcon, {
							name: iconByReason[props.reason] ?? iconByReason.not_found,
							class: "size-6"
						}, null, _parent, _scopeId));
						_push(`</div><h1 class="mt-4 text-xl font-semibold text-white"${_scopeId}>${ssrInterpolate(props.__.title)}</h1><p class="mt-2 text-sm leading-6 text-text-400"${_scopeId}>${ssrInterpolate(props.__.description)}</p></div>`);
					} else return [createVNode("div", { class: "flex flex-col items-center text-center" }, [
						createVNode("img", {
							src: "/assets/img/fulgurite-logo.svg",
							alt: "Fulgurite",
							class: "size-14"
						}),
						createVNode("div", { class: "mt-5 flex size-12 items-center justify-center rounded-full bg-red-500/10 text-red-400" }, [createVNode(_component_UIcon, {
							name: iconByReason[props.reason] ?? iconByReason.not_found,
							class: "size-6"
						}, null, 8, ["name"])]),
						createVNode("h1", { class: "mt-4 text-xl font-semibold text-white" }, toDisplayString(props.__.title), 1),
						createVNode("p", { class: "mt-2 text-sm leading-6 text-text-400" }, toDisplayString(props.__.description), 1)
					])];
				}),
				footer: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex flex-col gap-3"${_scopeId}>`);
						_push(ssrRenderComponent(_component_UButton, {
							to: "/login",
							icon: "i-lucide-log-in",
							color: "primary",
							label: props.__.login_link,
							block: ""
						}, null, _parent, _scopeId));
						_push(`<p class="text-center text-xs leading-5 text-text-500"${_scopeId}>${ssrInterpolate(props.__.footer)}</p></div>`);
					} else return [createVNode("div", { class: "flex flex-col gap-3" }, [createVNode(_component_UButton, {
						to: "/login",
						icon: "i-lucide-log-in",
						color: "primary",
						label: props.__.login_link,
						block: ""
					}, null, 8, ["label"]), createVNode("p", { class: "text-center text-xs leading-5 text-text-500" }, toDisplayString(props.__.footer), 1)])];
				}),
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(ssrRenderComponent(_component_UAlert, {
						color: "error",
						variant: "soft",
						icon: iconByReason[props.reason] ?? iconByReason.not_found,
						title: props.__.alert_title,
						description: props.__.alert_description
					}, null, _parent, _scopeId));
					else return [createVNode(_component_UAlert, {
						color: "error",
						variant: "soft",
						icon: iconByReason[props.reason] ?? iconByReason.not_found,
						title: props.__.alert_title,
						description: props.__.alert_description
					}, null, 8, [
						"icon",
						"title",
						"description"
					])];
				}),
				_: 1
			}, _parent));
			_push(`</div>`);
		};
	}
});
var _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Errors/InvitationUnavailable.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
//#endregion
export { _sfc_main as default };

//# sourceMappingURL=InvitationUnavailable-Y7Z_1MIK.js.map