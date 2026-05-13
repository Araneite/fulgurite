import { Link } from "@inertiajs/vue3";
import { ssrInterpolate, ssrRenderAttr, ssrRenderAttrs, ssrRenderComponent, ssrRenderSlot } from "vue/server-renderer";
import { createTextVNode, createVNode, mergeProps, unref, useSSRContext, withCtx } from "vue";
//#region resources/js/Layouts/DashboardLayout.vue
var _sfc_main$1 = {
	__name: "DashboardLayout",
	__ssrInlineRender: true,
	setup(__props) {
		return (_ctx, _push, _parent, _attrs) => {
			_push(`<div${ssrRenderAttrs(mergeProps({ class: "flex h-min-screen bg-midnight font-sans text-zinc-100 antialiased textured" }, _attrs))}><aside class="h-screen w-fit min-w-55 bg-darknight py-5 light:bg-sandstone">`);
			_push(ssrRenderComponent(unref(Link), {
				href: "/",
				class: "mx-auto flex items-center gap-4 border-b border-white/10 px-5 pb-5 font-semibold tracking-wide text-white light:text-black"
			}, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(`<img${ssrRenderAttr("src", "/assets/img/fulgurite-logo.svg")} alt="Fulgurite" class="size-10"${_scopeId}><p class="text-xl"${_scopeId}>Fulgur<span class="text-primary"${_scopeId}>ite</span></p>`);
					else return [createVNode("img", {
						src: "/assets/img/fulgurite-logo.svg",
						alt: "Fulgurite",
						class: "size-10"
					}), createVNode("p", { class: "text-xl" }, [createTextVNode("Fulgur"), createVNode("span", { class: "text-primary" }, "ite")])];
				}),
				_: 1
			}, _parent));
			_push(`</aside><main class="flex h-screen w-full flex-col"><nav class="h-22 w-full border-b border-white/10 bg-darknight/80 light:bg-sandstone"><div class="mx-auto flex h-full max-w 6xl items-center justify-between px-6 py-4"><div class="text-xs text-zinc-400">Interface interne</div></div></nav><div class="flex h-full w-full items-center justify-center gap-6 bg-midnight p-4">`);
			ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
			_push(`</div></main></div>`);
		};
	}
};
var _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Layouts/DashboardLayout.vue");
	return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
//#endregion
//#region resources/js/Pages/Dashboard/Users/Index.vue
var _sfc_main = /* @__PURE__ */ Object.assign({ layout: _sfc_main$1 }, {
	__name: "Index",
	__ssrInlineRender: true,
	props: {
		activeUsersCount: Number,
		inactiveUsersCount: Number,
		trashedUsersCount: Number
	},
	setup(__props) {
		return (_ctx, _push, _parent, _attrs) => {
			_push(`<div${ssrRenderAttrs(mergeProps({ class: "w-full space-y-8" }, _attrs))}><header class="flex flex-col gap-4 border-b border-white/10 pb-6 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-sm font-medium text-cyan-300">Utilisateurs</p><h1 class="mt-2 text-3xl font-semibold tracking-normal text-white"> Gestion des comptes internes </h1></div></header><section class="grid gap-4 sm:grid-cols-3"><div class="rounded-lg border border-white/10 bg-zinc-900 p-4"><p class="text-sm text-zinc-400">Actifs</p><p class="mt-2 text-2xl font-semibold text-white">${ssrInterpolate(__props.activeUsersCount)}</p></div><div class="rounded-lg border border-white/10 bg-zinc-900 p-4"><p class="text-sm text-zinc-400">Inactifs</p><p class="mt-2 text-2xl font-semibold text-white">${ssrInterpolate(__props.inactiveUsersCount)}</p></div><div class="rounded-lg border border-white/10 bg-zinc-900 p-4"><p class="text-sm text-zinc-400">Supprimes</p><p class="mt-2 text-2xl font-semibold text-white">${ssrInterpolate(__props.trashedUsersCount)}</p></div></section></div>`);
		};
	}
});
var _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Dashboard/Users/Index.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
//#endregion
export { _sfc_main as default };

//# sourceMappingURL=Index-BurEsxL4.js.map