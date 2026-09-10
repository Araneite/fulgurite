import { r as _sfc_main$1 } from "./usePortal-DZb6nPjI.js";
import { n as _sfc_main$3, r as _sfc_main$4, t as _sfc_main$2 } from "./Form-DAQH5xxu.js";
import { t as _sfc_main$5 } from "./Alert-LEzzgm8x.js";
import { t as _sfc_main$6 } from "./Card-DdXBumkA.js";
import { t as _sfc_main$7 } from "./AuthLayout-BCZkOr_K.js";
import { Link, useForm } from "@inertiajs/vue3";
import { ssrInterpolate, ssrRenderAttr, ssrRenderAttrs, ssrRenderComponent } from "vue/server-renderer";
import { computed, createBlock, createCommentVNode, createTextVNode, createVNode, mergeProps, openBlock, toDisplayString, unref, useSSRContext, withCtx, withModifiers } from "vue";
//#region resources/js/Pages/Auth/AcceptInvitation.vue
var _sfc_main = /* @__PURE__ */ Object.assign({ layout: _sfc_main$7 }, {
	__name: "AcceptInvitation",
	__ssrInlineRender: true,
	props: {
		token: {
			type: String,
			required: true
		},
		invitation: {
			type: Object,
			required: true
		},
		__: {
			type: Object,
			required: true
		}
	},
	setup(__props) {
		const props = __props;
		const form = useForm({
			username: props.invitation.username ?? "",
			password: "",
			password_confirmation: ""
		}).dontRemember("password", "password_confirmation");
		const invitationAction = computed(() => `/invitations/accept/${props.token}`);
		function submit() {
			form.post(invitationAction, {
				preserveScroll: true,
				onError: () => {
					form.reset("password", "password_confirmation");
				}
			});
		}
		return (_ctx, _push, _parent, _attrs) => {
			const _component_UCard = _sfc_main$6;
			const _component_UAlert = _sfc_main$5;
			const _component_UForm = _sfc_main$2;
			const _component_UFormField = _sfc_main$3;
			const _component_UInput = _sfc_main$4;
			const _component_UButton = _sfc_main$1;
			_push(`<div${ssrRenderAttrs(mergeProps({ class: "w-full max-w-md" }, _attrs))}>`);
			_push(ssrRenderComponent(_component_UCard, { ui: {
				root: "bg-darknight/80 ring-white/10",
				body: "p-6 sm:p-7",
				header: "p-6 sm:p-7 border-white/10",
				footer: "p-6 sm:p-7 border-white/10"
			} }, {
				header: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(`<div class="flex flex-col items-center text-center"${_scopeId}><img${ssrRenderAttr("src", "/assets/img/fulgurite-logo.svg")} alt="Fulgurite" class="size-14"${_scopeId}><h1 class="mt-4 text-xl font-semibold text-white"${_scopeId}>${ssrInterpolate(__props.__.title)}</h1><p class="mt-2 text-sm text-text-400"${_scopeId}>${ssrInterpolate(__props.__.subtitle)}</p></div>`);
					else return [createVNode("div", { class: "flex flex-col items-center text-center" }, [
						createVNode("img", {
							src: "/assets/img/fulgurite-logo.svg",
							alt: "Fulgurite",
							class: "size-14"
						}),
						createVNode("h1", { class: "mt-4 text-xl font-semibold text-white" }, toDisplayString(__props.__.title), 1),
						createVNode("p", { class: "mt-2 text-sm text-text-400" }, toDisplayString(__props.__.subtitle), 1)
					])];
				}),
				footer: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<p class="text-center text-xs text-text-500"${_scopeId}>${ssrInterpolate(__props.__.footer)} `);
						_push(ssrRenderComponent(unref(Link), {
							href: "/login",
							class: "text-primary hover:underline"
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(`${ssrInterpolate(__props.__.login_link)}`);
								else return [createTextVNode(toDisplayString(__props.__.login_link), 1)];
							}),
							_: 1
						}, _parent, _scopeId));
						_push(`</p>`);
					} else return [createVNode("p", { class: "text-center text-xs text-text-500" }, [createTextVNode(toDisplayString(__props.__.footer) + " ", 1), createVNode(unref(Link), {
						href: "/login",
						class: "text-primary hover:underline"
					}, {
						default: withCtx(() => [createTextVNode(toDisplayString(__props.__.login_link), 1)]),
						_: 1
					})])];
				}),
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(ssrRenderComponent(_component_UAlert, {
							color: "primary",
							variant: "soft",
							icon: "i-lucide-mail-check",
							title: __props.__.invitation_title,
							description: `${__props.__.invitation_description}`.replace(":email", __props.invitation.email),
							class: "mb-5"
						}, null, _parent, _scopeId));
						_push(`<div class="mb-5 grid gap-2 rounded-lg border border-white/10 bg-white/5 p-4 text-sm"${_scopeId}><div class="flex items-center justify-between gap-4"${_scopeId}><span class="text-text-400"${_scopeId}>${ssrInterpolate(__props.__.fields.email)}</span><span class="truncate font-medium text-white"${_scopeId}>${ssrInterpolate(__props.invitation.email)}</span></div>`);
						if (__props.invitation.inviter) _push(`<div class="flex items-center justify-between gap-4"${_scopeId}><span class="text-text-400"${_scopeId}>${ssrInterpolate(__props.__.fields.inviter)}</span><span class="truncate font-medium text-white"${_scopeId}>${ssrInterpolate(__props.invitation.inviter)}</span></div>`);
						else _push(`<!---->`);
						if (__props.invitation.expires_at) _push(`<div class="flex items-center justify-between gap-4"${_scopeId}><span class="text-text-400"${_scopeId}>${ssrInterpolate(__props.__.fields.expires_at)}</span><span class="truncate font-medium text-white"${_scopeId}>${ssrInterpolate(__props.invitation.expires_at)}</span></div>`);
						else _push(`<!---->`);
						_push(`</div>`);
						_push(ssrRenderComponent(_component_UForm, {
							state: unref(form),
							class: "flex flex-col gap-4",
							onSubmit: submit
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) {
									_push(ssrRenderComponent(_component_UFormField, {
										name: "username",
										label: __props.__.fields.username,
										error: unref(form).errors.username,
										required: ""
									}, {
										default: withCtx((_, _push, _parent, _scopeId) => {
											if (_push) _push(ssrRenderComponent(_component_UInput, {
												modelValue: unref(form).username,
												"onUpdate:modelValue": ($event) => unref(form).username = $event,
												icon: "i-lucide-user",
												autocomplete: "username",
												class: "w-full",
												autofocus: ""
											}, null, _parent, _scopeId));
											else return [createVNode(_component_UInput, {
												modelValue: unref(form).username,
												"onUpdate:modelValue": ($event) => unref(form).username = $event,
												icon: "i-lucide-user",
												autocomplete: "username",
												class: "w-full",
												autofocus: ""
											}, null, 8, ["modelValue", "onUpdate:modelValue"])];
										}),
										_: 1
									}, _parent, _scopeId));
									_push(ssrRenderComponent(_component_UFormField, {
										name: "password",
										label: __props.__.fields.password,
										error: unref(form).errors.password,
										required: ""
									}, {
										default: withCtx((_, _push, _parent, _scopeId) => {
											if (_push) _push(ssrRenderComponent(_component_UInput, {
												modelValue: unref(form).password,
												"onUpdate:modelValue": ($event) => unref(form).password = $event,
												type: "password",
												icon: "i-lucide-lock",
												autocomplete: "new-password",
												class: "w-full"
											}, null, _parent, _scopeId));
											else return [createVNode(_component_UInput, {
												modelValue: unref(form).password,
												"onUpdate:modelValue": ($event) => unref(form).password = $event,
												type: "password",
												icon: "i-lucide-lock",
												autocomplete: "new-password",
												class: "w-full"
											}, null, 8, ["modelValue", "onUpdate:modelValue"])];
										}),
										_: 1
									}, _parent, _scopeId));
									_push(ssrRenderComponent(_component_UFormField, {
										name: "password_confirmation",
										label: __props.__.fields.password_confirmation,
										error: unref(form).errors.password_confirmation,
										required: ""
									}, {
										default: withCtx((_, _push, _parent, _scopeId) => {
											if (_push) _push(ssrRenderComponent(_component_UInput, {
												modelValue: unref(form).password_confirmation,
												"onUpdate:modelValue": ($event) => unref(form).password_confirmation = $event,
												type: "password",
												icon: "i-lucide-lock-keyhole",
												autocomplete: "new-password",
												class: "w-full"
											}, null, _parent, _scopeId));
											else return [createVNode(_component_UInput, {
												modelValue: unref(form).password_confirmation,
												"onUpdate:modelValue": ($event) => unref(form).password_confirmation = $event,
												type: "password",
												icon: "i-lucide-lock-keyhole",
												autocomplete: "new-password",
												class: "w-full"
											}, null, 8, ["modelValue", "onUpdate:modelValue"])];
										}),
										_: 1
									}, _parent, _scopeId));
									_push(ssrRenderComponent(_component_UButton, {
										type: "submit",
										color: "primary",
										icon: "i-lucide-check",
										loading: unref(form).processing,
										label: __props.__.submit,
										block: "",
										class: "mt-2"
									}, null, _parent, _scopeId));
								} else return [
									createVNode(_component_UFormField, {
										name: "username",
										label: __props.__.fields.username,
										error: unref(form).errors.username,
										required: ""
									}, {
										default: withCtx(() => [createVNode(_component_UInput, {
											modelValue: unref(form).username,
											"onUpdate:modelValue": ($event) => unref(form).username = $event,
											icon: "i-lucide-user",
											autocomplete: "username",
											class: "w-full",
											autofocus: ""
										}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									createVNode(_component_UFormField, {
										name: "password",
										label: __props.__.fields.password,
										error: unref(form).errors.password,
										required: ""
									}, {
										default: withCtx(() => [createVNode(_component_UInput, {
											modelValue: unref(form).password,
											"onUpdate:modelValue": ($event) => unref(form).password = $event,
											type: "password",
											icon: "i-lucide-lock",
											autocomplete: "new-password",
											class: "w-full"
										}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									createVNode(_component_UFormField, {
										name: "password_confirmation",
										label: __props.__.fields.password_confirmation,
										error: unref(form).errors.password_confirmation,
										required: ""
									}, {
										default: withCtx(() => [createVNode(_component_UInput, {
											modelValue: unref(form).password_confirmation,
											"onUpdate:modelValue": ($event) => unref(form).password_confirmation = $event,
											type: "password",
											icon: "i-lucide-lock-keyhole",
											autocomplete: "new-password",
											class: "w-full"
										}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									createVNode(_component_UButton, {
										type: "submit",
										color: "primary",
										icon: "i-lucide-check",
										loading: unref(form).processing,
										label: __props.__.submit,
										block: "",
										class: "mt-2"
									}, null, 8, ["loading", "label"])
								];
							}),
							_: 1
						}, _parent, _scopeId));
					} else return [
						createVNode(_component_UAlert, {
							color: "primary",
							variant: "soft",
							icon: "i-lucide-mail-check",
							title: __props.__.invitation_title,
							description: `${__props.__.invitation_description}`.replace(":email", __props.invitation.email),
							class: "mb-5"
						}, null, 8, ["title", "description"]),
						createVNode("div", { class: "mb-5 grid gap-2 rounded-lg border border-white/10 bg-white/5 p-4 text-sm" }, [
							createVNode("div", { class: "flex items-center justify-between gap-4" }, [createVNode("span", { class: "text-text-400" }, toDisplayString(__props.__.fields.email), 1), createVNode("span", { class: "truncate font-medium text-white" }, toDisplayString(__props.invitation.email), 1)]),
							__props.invitation.inviter ? (openBlock(), createBlock("div", {
								key: 0,
								class: "flex items-center justify-between gap-4"
							}, [createVNode("span", { class: "text-text-400" }, toDisplayString(__props.__.fields.inviter), 1), createVNode("span", { class: "truncate font-medium text-white" }, toDisplayString(__props.invitation.inviter), 1)])) : createCommentVNode("", true),
							__props.invitation.expires_at ? (openBlock(), createBlock("div", {
								key: 1,
								class: "flex items-center justify-between gap-4"
							}, [createVNode("span", { class: "text-text-400" }, toDisplayString(__props.__.fields.expires_at), 1), createVNode("span", { class: "truncate font-medium text-white" }, toDisplayString(__props.invitation.expires_at), 1)])) : createCommentVNode("", true)
						]),
						createVNode(_component_UForm, {
							state: unref(form),
							class: "flex flex-col gap-4",
							onSubmit: withModifiers(submit, ["prevent"])
						}, {
							default: withCtx(() => [
								createVNode(_component_UFormField, {
									name: "username",
									label: __props.__.fields.username,
									error: unref(form).errors.username,
									required: ""
								}, {
									default: withCtx(() => [createVNode(_component_UInput, {
										modelValue: unref(form).username,
										"onUpdate:modelValue": ($event) => unref(form).username = $event,
										icon: "i-lucide-user",
										autocomplete: "username",
										class: "w-full",
										autofocus: ""
									}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								createVNode(_component_UFormField, {
									name: "password",
									label: __props.__.fields.password,
									error: unref(form).errors.password,
									required: ""
								}, {
									default: withCtx(() => [createVNode(_component_UInput, {
										modelValue: unref(form).password,
										"onUpdate:modelValue": ($event) => unref(form).password = $event,
										type: "password",
										icon: "i-lucide-lock",
										autocomplete: "new-password",
										class: "w-full"
									}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								createVNode(_component_UFormField, {
									name: "password_confirmation",
									label: __props.__.fields.password_confirmation,
									error: unref(form).errors.password_confirmation,
									required: ""
								}, {
									default: withCtx(() => [createVNode(_component_UInput, {
										modelValue: unref(form).password_confirmation,
										"onUpdate:modelValue": ($event) => unref(form).password_confirmation = $event,
										type: "password",
										icon: "i-lucide-lock-keyhole",
										autocomplete: "new-password",
										class: "w-full"
									}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								createVNode(_component_UButton, {
									type: "submit",
									color: "primary",
									icon: "i-lucide-check",
									loading: unref(form).processing,
									label: __props.__.submit,
									block: "",
									class: "mt-2"
								}, null, 8, ["loading", "label"])
							]),
							_: 1
						}, 8, ["state"])
					];
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
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Auth/AcceptInvitation.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
//#endregion
export { _sfc_main as default };

//# sourceMappingURL=AcceptInvitation-BCBndmcE.js.map