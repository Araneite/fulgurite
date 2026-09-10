import { t as _sfc_main$1 } from "./App-BCJnMUgD.js";
import { Link } from "@inertiajs/vue3";
import { ssrRenderAttr, ssrRenderComponent, ssrRenderSlot } from "vue/server-renderer";
import { createTextVNode, createVNode, renderSlot, unref, useSSRContext, withCtx } from "vue";
//#region resources/js/Layouts/AuthLayout.vue
var _sfc_main = {
	__name: "AuthLayout",
	__ssrInlineRender: true,
	setup(__props) {
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(_sfc_main$1, _attrs, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex h-min-screen bg-midnight font-sans text-zinc-100 antialiased textured"${_scopeId}><main class="flex h-screen w-full flex-col"${_scopeId}><nav class="h-22 w-full border-b border-white/10 bg-darknight/80 light:bg-sandstone flex items-center justify-between"${_scopeId}>`);
						_push(ssrRenderComponent(unref(Link), {
							href: "/",
							class: "flex items-center gap-4 px-5 font-semibold tracking-wide text-white light:text-black"
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
						}, _parent, _scopeId));
						_push(`<div class="flex h-full max-w 6xl items-center justify-between px-6 py-4"${_scopeId}><div class="text-xs text-zinc-400"${_scopeId}>Interface interne</div></div></nav><div class="flex h-full w-full items-center justify-center gap-6 p-4"${_scopeId}>`);
						ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
						_push(`</div></main></div>`);
					} else return [createVNode("div", { class: "flex h-min-screen bg-midnight font-sans text-zinc-100 antialiased textured" }, [createVNode("main", { class: "flex h-screen w-full flex-col" }, [createVNode("nav", { class: "h-22 w-full border-b border-white/10 bg-darknight/80 light:bg-sandstone flex items-center justify-between" }, [createVNode(unref(Link), {
						href: "/",
						class: "flex items-center gap-4 px-5 font-semibold tracking-wide text-white light:text-black"
					}, {
						default: withCtx(() => [createVNode("img", {
							src: "/assets/img/fulgurite-logo.svg",
							alt: "Fulgurite",
							class: "size-10"
						}), createVNode("p", { class: "text-xl" }, [createTextVNode("Fulgur"), createVNode("span", { class: "text-primary" }, "ite")])]),
						_: 1
					}), createVNode("div", { class: "flex h-full max-w 6xl items-center justify-between px-6 py-4" }, [createVNode("div", { class: "text-xs text-zinc-400" }, "Interface interne")])]), createVNode("div", { class: "flex h-full w-full items-center justify-center gap-6 p-4" }, [renderSlot(_ctx.$slots, "default")])])])];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Layouts/AuthLayout.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
//#endregion
export { _sfc_main as t };

//# sourceMappingURL=AuthLayout-BCZkOr_K.js.map