import { Link, useForm } from "@inertiajs/vue3";
import { ssrIncludeBooleanAttr, ssrInterpolate, ssrLooseContain, ssrRenderAttr, ssrRenderAttrs, ssrRenderClass, ssrRenderComponent, ssrRenderDynamicModel, ssrRenderSlot, ssrRenderStyle } from "vue/server-renderer";
import { computed, createTextVNode, createVNode, mergeModels, mergeProps, onMounted, reactive, ref, toDisplayString, unref, useModel, useSSRContext, withCtx } from "vue";
//#region resources/js/Layouts/AuthLayout.vue
var _sfc_main$4 = {
	__name: "AuthLayout",
	__ssrInlineRender: true,
	setup(__props) {
		return (_ctx, _push, _parent, _attrs) => {
			_push(`<div${ssrRenderAttrs(mergeProps({ class: "flex h-min-screen bg-midnight font-sans text-zinc-100 antialiased textured" }, _attrs))}><main class="flex h-screen w-full flex-col"><nav class="h-22 w-full border-b border-white/10 bg-darknight/80 light:bg-sandstone flex items-center justify-between">`);
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
			}, _parent));
			_push(`<div class="flex h-full max-w 6xl items-center justify-between px-6 py-4"><div class="text-xs text-zinc-400">Interface interne</div></div></nav><div class="flex h-full w-full items-center justify-center gap-6 p-4">`);
			ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
			_push(`</div></main></div>`);
		};
	}
};
var _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Layouts/AuthLayout.vue");
	return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
//#endregion
//#region resources/js/Components/Forms/TextInput.vue
var _sfc_main$3 = {
	__name: "TextInput",
	__ssrInlineRender: true,
	props: /* @__PURE__ */ mergeModels({
		id: {
			type: String,
			default: null
		},
		name: {
			type: String,
			required: true
		},
		type: {
			type: String,
			default: "text"
		},
		label: {
			type: String,
			required: true
		},
		autocomplete: {
			type: String,
			default: "off"
		},
		error: {
			type: String,
			default: null
		},
		autofocus: {
			type: Boolean,
			default: false
		}
	}, {
		"modelValue": {
			type: [String, Number],
			default: ""
		},
		"modelModifiers": {}
	}),
	emits: ["update:modelValue"],
	setup(__props) {
		const model = useModel(__props, "modelValue");
		return (_ctx, _push, _parent, _attrs) => {
			_push(`<!--[--><input${ssrRenderAttr("id", __props.id ?? __props.name)}${ssrRenderDynamicModel(__props.type, model.value, null)}${ssrRenderAttr("type", __props.type)}${ssrRenderAttr("name", __props.name)}${ssrRenderAttr("autocomplete", __props.autocomplete)}${ssrIncludeBooleanAttr(__props.autofocus) ? " autofocus" : ""} placeholder=""><label${ssrRenderAttr("for", __props.id ?? __props.name)}>${ssrInterpolate(__props.label)}</label>`);
			if (__props.error) _push(`<p class="mt-2 text-sm text-red-400">${ssrInterpolate(__props.error)}</p>`);
			else _push(`<!---->`);
			_push(`<!--]-->`);
		};
	}
};
var _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Forms/TextInput.vue");
	return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
//#endregion
//#region resources/js/Components/Forms/PasswordInput.vue
var _sfc_main$2 = {
	__name: "PasswordInput",
	__ssrInlineRender: true,
	props: /* @__PURE__ */ mergeModels({
		id: {
			type: String,
			default: null
		},
		name: {
			type: String,
			required: true
		},
		label: {
			type: String,
			required: true
		},
		autocomplete: {
			type: String,
			default: "current-password"
		},
		error: {
			rtpe: String,
			default: null
		}
	}, {
		"modelValue": {
			type: String,
			default: ""
		},
		"modelModifiers": {}
	}),
	emits: ["update:modelValue"],
	setup(__props, { expose: __expose }) {
		const model = useModel(__props, "modelValue");
		const eye = reactive({
			visible: false,
			pupilX: 0,
			pupilY: 0
		});
		const inputType = computed(() => eye.visible ? "text" : "password");
		function trackPoint(event) {
			if (eye.visible) return;
			const svg = event.currentTarget.querySelector(".password-eye");
			if (!svg) return;
			const rect = svg.getBoundingClientRect();
			const centerX = rect.left + rect.width / 2;
			const centerY = rect.top + rect.height / 2;
			const dx = event.clientX - centerX;
			const dy = event.clientY - centerY;
			eye.pupilX = Math.max(-4, Math.min(4, dx / 18));
			eye.pupilY = Math.max(-3, Math.min(3, dy / 18));
		}
		function reset() {
			eye.pupilX = 0;
			eye.pupilY = 0;
		}
		__expose({
			trackPoint,
			reset
		});
		return (_ctx, _push, _parent, _attrs) => {
			_push(`<!--[--><input${ssrRenderAttr("id", __props.id ?? __props.name)}${ssrRenderDynamicModel(inputType.value, model.value, null)}${ssrRenderAttr("type", inputType.value)}${ssrRenderAttr("name", __props.name)}${ssrRenderAttr("autocomplete", __props.autocomplete)} placeholder="" class="pr-12"><label${ssrRenderAttr("for", __props.id ?? __props.name)}>${ssrInterpolate(__props.label)}</label><button type="button" class="absolute right-3 top-1/2 grid size-8 -translate-y-1/2 cursor-pointer place-items-center rounded-md text-text-400 hover:text-text-300 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary"${ssrRenderAttr("aria-label", eye.visible ? "Masquer le mot de passe" : "Afficher le mot de passe")}><svg class="${ssrRenderClass([{ "is-closed": eye.visible }, "password-eye"])}" viewBox="0 0 64 40" width="28" height="28" aria-hidden="true"><path class="password-eye__upper" d="M4 20C10 9 20 4 32 4C44 4 54 9 60 20"></path><path class="password-eye__lower" d="M4 20C10 31 20 36 32 36C44 36 54 31 60 20"></path><g class="password-eye__iris" style="${ssrRenderStyle(`transform: translate(${eye.pupilX}px, ${eye.pupilY}px)`)}"><circle cx="32" cy="20" r="8" class="password-eye__iris-fill"></circle><circle cx="32" cy="20" r="3.5" class="password-eye__pupil"></circle></g><path class="password-eye__closed-line" d="M6 22C14 16 22 14 32 14C42 14 50 16 58 22"></path><path class="password-eye__lash password-eye__lash-left" d="M18 24L14 31"></path><path class="password-eye__lash password-eye__lash-center" d="M32 26L32 34"></path><path class="password-eye__lash password-eye__lash-right" d="M46 24L50 31"></path></svg></button>`);
			if (__props.error) _push(`<p class="mt-2 text-sm text-red-400">${ssrInterpolate(__props.error)}</p>`);
			else _push(`<!---->`);
			_push(`<!--]-->`);
		};
	}
};
var _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Forms/PasswordInput.vue");
	return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
//#endregion
//#region resources/js/Components/Forms/CheckboxInput.vue
var _sfc_main$1 = {
	__name: "CheckboxInput",
	__ssrInlineRender: true,
	props: /* @__PURE__ */ mergeModels({
		id: {
			type: String,
			default: null
		},
		name: {
			type: String,
			required: true
		},
		label: {
			type: String,
			required: true
		},
		error: {
			type: String,
			default: null
		}
	}, {
		"modelValue": {
			type: Boolean,
			default: false
		},
		"modelModifiers": {}
	}),
	emits: ["update:modelValue"],
	setup(__props) {
		const model = useModel(__props, "modelValue");
		return (_ctx, _push, _parent, _attrs) => {
			_push(`<!--[--><label class="flex items-center gap-2 has-focus-visible:ring-2 has-focus-visible:ring-primary-500"${ssrRenderAttr("for", __props.id ?? __props.name)}><input${ssrRenderAttr("id", __props.id ?? __props.name)}${ssrIncludeBooleanAttr(Array.isArray(model.value) ? ssrLooseContain(model.value, null) : model.value) ? " checked" : ""} type="checkbox"${ssrRenderAttr("name", __props.name)}><svg viewBox="0 0 64 64" height="1em" width="1em" aria-hidden="true"><path d="M 0 16 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 16 L 32 48 L 64 16 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 16" pathLength="575.0541381835938" class="path"></path></svg><p class="label">${ssrInterpolate(__props.label)}</p></label>`);
			if (__props.error) _push(`<p class="mt-2 text-sm text-red-400">${ssrInterpolate(__props.error)}</p>`);
			else _push(`<!---->`);
			_push(`<!--]-->`);
		};
	}
};
var _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Forms/CheckboxInput.vue");
	return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
//#endregion
//#region resources/js/Pages/Auth/Login.vue
var _sfc_main = /* @__PURE__ */ Object.assign({ layout: _sfc_main$4 }, {
	__name: "Login",
	__ssrInlineRender: true,
	props: {
		resetPasswordUrl: {
			type: String,
			required: true
		},
		trans: {
			type: Object,
			required: true
		}
	},
	setup(__props) {
		const form = useForm("LoginForm", {
			email: "",
			password: "",
			rememberMe: false
		}).dontRemember("password");
		const isInitialLoading = ref(true);
		const passwordInput = ref(null);
		onMounted(() => {
			window.setTimeout(() => {
				isInitialLoading.value = false;
			}, 450);
		});
		return (_ctx, _push, _parent, _attrs) => {
			_push(`<div${ssrRenderAttrs(mergeProps({ class: "relative" }, _attrs))}><form class="box classic-form"><div class="form-header"><div class="logo"><img${ssrRenderAttr("src", "/assets/img/fulgurite-logo.svg")} alt="Fulgurite" class="logo-icon"><h2>Fulgurite</h2></div><p class="mt-3 text-center text-sm text-text-400"> Interface de sauvegarde pour Restic </p></div><div class="input-group text">`);
			_push(ssrRenderComponent(_sfc_main$3, {
				modelValue: unref(form).email,
				"onUpdate:modelValue": ($event) => unref(form).email = $event,
				label: "Email",
				type: "email",
				name: "email",
				autocomplete: "email",
				error: unref(form).errors.email,
				autofocus: ""
			}, null, _parent));
			_push(`</div><div class="input-group text">`);
			_push(ssrRenderComponent(_sfc_main$2, {
				ref_key: "passwordInput",
				ref: passwordInput,
				modelValue: unref(form).password,
				"onUpdate:modelValue": ($event) => unref(form).password = $event,
				name: "password",
				label: __props.trans.fields.password,
				autocomplete: "current-password",
				error: unref(form).errors.password
			}, null, _parent));
			_push(`</div><div class="input-group checkbox flex items-center justify-center">`);
			_push(ssrRenderComponent(_sfc_main$1, {
				modelValue: unref(form).remember,
				"onUpdate:modelValue": ($event) => unref(form).remember = $event,
				name: "remember",
				label: __props.trans.remember_me,
				error: unref(form).errors.remember
			}, null, _parent));
			_push(`</div>`);
			_push(ssrRenderComponent(unref(Link), {
				href: __props.resetPasswordUrl,
				class: "link link-secondary"
			}, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(`${ssrInterpolate(__props.trans.reset_password)}`);
					else return [createTextVNode(toDisplayString(__props.trans.reset_password), 1)];
				}),
				_: 1
			}, _parent));
			_push(`<button class="btn btn-primary" type="submit"${ssrIncludeBooleanAttr(unref(form).processing) ? " disabled" : ""}> Connexion </button></form>`);
			if (isInitialLoading.value) _push(`<div class="fixed inset-0 z-50 flex items-center justify-center bg-midnight/85 px-6 backdrop-blur-sm" role="status" aria-live="polite"><div class="flex flex-col items-center gap-5 text-center"><img${ssrRenderAttr("src", "/assets/img/fulgurite-logo.svg")} alt="" class="size-16"><div class="size-12 rounded-full border-4 border-white/15 border-t-primary-500 animate-spin"></div><p class="text-sm font-medium text-text-300">Chargement</p></div></div>`);
			else _push(`<!---->`);
			_push(`</div>`);
		};
	}
});
var _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Auth/Login.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
//#endregion
export { _sfc_main as default };

//# sourceMappingURL=Login-DeBta9lY.js.map