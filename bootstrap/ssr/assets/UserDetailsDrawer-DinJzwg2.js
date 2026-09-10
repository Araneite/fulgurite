import { C as useComponentIcons, F as useAppConfig, S as useFieldGroup, c as _sfc_main$1, o as _sfc_main$2, r as _sfc_main$3, u as tv, w as useComponentUI } from "./usePortal-DZb6nPjI.js";
import { t as _sfc_main$4 } from "./Alert-LEzzgm8x.js";
import { t as _sfc_main$5 } from "./Tooltip-_q5JW3sO.js";
import { t as _sfc_main$6 } from "./Drawer-xm5bIe9t.js";
import { router } from "@inertiajs/vue3";
import { ssrInterpolate, ssrRenderAttr, ssrRenderClass, ssrRenderComponent, ssrRenderList, ssrRenderSlot } from "vue/server-renderer";
import { Fragment, computed, createBlock, createCommentVNode, createVNode, defineComponent, mergeModels, mergeProps, nextTick, onBeforeUnmount, openBlock, ref, renderList, renderSlot, toDisplayString, unref, useModel, useSSRContext, useSlots, watch, withCtx } from "vue";
import { Primitive } from "reka-ui";
//#region virtual:nuxt-ui-templates/ui/badge.ts
var badge_default = {
	"slots": {
		"base": "font-medium inline-flex items-center",
		"label": "truncate",
		"leadingIcon": "shrink-0",
		"leadingAvatar": "shrink-0",
		"leadingAvatarSize": "",
		"trailingIcon": "shrink-0"
	},
	"variants": {
		"fieldGroup": {
			"horizontal": "not-only:first:rounded-e-none not-only:last:rounded-s-none not-last:not-first:rounded-none focus-visible:z-[1]",
			"vertical": "not-only:first:rounded-b-none not-only:last:rounded-t-none not-last:not-first:rounded-none focus-visible:z-[1]"
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
		"variant": {
			"solid": "",
			"outline": "",
			"soft": "",
			"subtle": ""
		},
		"size": {
			"xs": {
				"base": "text-[8px]/3 px-1 py-0.5 gap-1 rounded-sm",
				"leadingIcon": "size-3",
				"leadingAvatarSize": "3xs",
				"trailingIcon": "size-3"
			},
			"sm": {
				"base": "text-[10px]/3 px-1.5 py-1 gap-1 rounded-sm",
				"leadingIcon": "size-3",
				"leadingAvatarSize": "3xs",
				"trailingIcon": "size-3"
			},
			"md": {
				"base": "text-xs px-2 py-1 gap-1 rounded-md",
				"leadingIcon": "size-4",
				"leadingAvatarSize": "3xs",
				"trailingIcon": "size-4"
			},
			"lg": {
				"base": "text-sm px-2 py-1 gap-1.5 rounded-md",
				"leadingIcon": "size-5",
				"leadingAvatarSize": "2xs",
				"trailingIcon": "size-5"
			},
			"xl": {
				"base": "text-base px-2.5 py-1 gap-1.5 rounded-md",
				"leadingIcon": "size-6",
				"leadingAvatarSize": "2xs",
				"trailingIcon": "size-6"
			}
		},
		"square": { "true": "" }
	},
	"compoundVariants": [
		{
			"color": "primary",
			"variant": "solid",
			"class": "bg-primary text-inverted"
		},
		{
			"color": "secondary",
			"variant": "solid",
			"class": "bg-secondary text-inverted"
		},
		{
			"color": "success",
			"variant": "solid",
			"class": "bg-success text-inverted"
		},
		{
			"color": "info",
			"variant": "solid",
			"class": "bg-info text-inverted"
		},
		{
			"color": "warning",
			"variant": "solid",
			"class": "bg-warning text-inverted"
		},
		{
			"color": "error",
			"variant": "solid",
			"class": "bg-error text-inverted"
		},
		{
			"color": "primary",
			"variant": "outline",
			"class": "text-primary ring ring-inset ring-primary/50"
		},
		{
			"color": "secondary",
			"variant": "outline",
			"class": "text-secondary ring ring-inset ring-secondary/50"
		},
		{
			"color": "success",
			"variant": "outline",
			"class": "text-success ring ring-inset ring-success/50"
		},
		{
			"color": "info",
			"variant": "outline",
			"class": "text-info ring ring-inset ring-info/50"
		},
		{
			"color": "warning",
			"variant": "outline",
			"class": "text-warning ring ring-inset ring-warning/50"
		},
		{
			"color": "error",
			"variant": "outline",
			"class": "text-error ring ring-inset ring-error/50"
		},
		{
			"color": "primary",
			"variant": "soft",
			"class": "bg-primary/10 text-primary"
		},
		{
			"color": "secondary",
			"variant": "soft",
			"class": "bg-secondary/10 text-secondary"
		},
		{
			"color": "success",
			"variant": "soft",
			"class": "bg-success/10 text-success"
		},
		{
			"color": "info",
			"variant": "soft",
			"class": "bg-info/10 text-info"
		},
		{
			"color": "warning",
			"variant": "soft",
			"class": "bg-warning/10 text-warning"
		},
		{
			"color": "error",
			"variant": "soft",
			"class": "bg-error/10 text-error"
		},
		{
			"color": "primary",
			"variant": "subtle",
			"class": "bg-primary/10 text-primary ring ring-inset ring-primary/25"
		},
		{
			"color": "secondary",
			"variant": "subtle",
			"class": "bg-secondary/10 text-secondary ring ring-inset ring-secondary/25"
		},
		{
			"color": "success",
			"variant": "subtle",
			"class": "bg-success/10 text-success ring ring-inset ring-success/25"
		},
		{
			"color": "info",
			"variant": "subtle",
			"class": "bg-info/10 text-info ring ring-inset ring-info/25"
		},
		{
			"color": "warning",
			"variant": "subtle",
			"class": "bg-warning/10 text-warning ring ring-inset ring-warning/25"
		},
		{
			"color": "error",
			"variant": "subtle",
			"class": "bg-error/10 text-error ring ring-inset ring-error/25"
		},
		{
			"color": "neutral",
			"variant": "solid",
			"class": "text-inverted bg-inverted"
		},
		{
			"color": "neutral",
			"variant": "outline",
			"class": "ring ring-inset ring-accented text-default bg-default"
		},
		{
			"color": "neutral",
			"variant": "soft",
			"class": "text-default bg-elevated"
		},
		{
			"color": "neutral",
			"variant": "subtle",
			"class": "ring ring-inset ring-accented text-default bg-elevated"
		},
		{
			"size": "xs",
			"square": true,
			"class": "p-0.5"
		},
		{
			"size": "sm",
			"square": true,
			"class": "p-1"
		},
		{
			"size": "md",
			"square": true,
			"class": "p-1"
		},
		{
			"size": "lg",
			"square": true,
			"class": "p-1"
		},
		{
			"size": "xl",
			"square": true,
			"class": "p-1"
		}
	],
	"defaultVariants": {
		"color": "primary",
		"variant": "solid",
		"size": "md"
	}
};
//#endregion
//#region node_modules/@nuxt/ui/dist/runtime/components/Badge.vue
var _sfc_main = {
	__name: "Badge",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false,
			default: "span"
		},
		label: {
			type: [String, Number],
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
		square: {
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
		}
	},
	setup(__props) {
		const props = __props;
		const slots = useSlots();
		const appConfig = useAppConfig();
		const uiProp = useComponentUI("badge", props);
		const { orientation, size: fieldGroupSize } = useFieldGroup(props);
		const { isLeading, isTrailing, leadingIconName, trailingIconName } = useComponentIcons(props);
		const ui = computed(() => tv({
			extend: tv(badge_default),
			...appConfig.ui?.badge || {}
		})({
			color: props.color,
			variant: props.variant,
			size: fieldGroupSize.value || props.size,
			square: props.square || !slots.default && !props.label,
			fieldGroup: orientation.value
		}));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(Primitive), mergeProps({
				as: __props.as,
				"data-slot": "base",
				class: ui.value.base({ class: [unref(uiProp)?.base, props.class] })
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						ssrRenderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => {
							if (unref(isLeading) && unref(leadingIconName)) _push(ssrRenderComponent(_sfc_main$1, {
								name: unref(leadingIconName),
								"data-slot": "leadingIcon",
								class: ui.value.leadingIcon({ class: unref(uiProp)?.leadingIcon })
							}, null, _parent, _scopeId));
							else if (!!__props.avatar) _push(ssrRenderComponent(_sfc_main$2, mergeProps({ size: unref(uiProp)?.leadingAvatarSize || ui.value.leadingAvatarSize() }, __props.avatar, {
								"data-slot": "leadingAvatar",
								class: ui.value.leadingAvatar({ class: unref(uiProp)?.leadingAvatar })
							}), null, _parent, _scopeId));
							else _push(`<!---->`);
						}, _push, _parent, _scopeId);
						ssrRenderSlot(_ctx.$slots, "default", { ui: ui.value }, () => {
							if (__props.label !== void 0 && __props.label !== null) _push(`<span data-slot="label" class="${ssrRenderClass(ui.value.label({ class: unref(uiProp)?.label }))}"${_scopeId}>${ssrInterpolate(__props.label)}</span>`);
							else _push(`<!---->`);
						}, _push, _parent, _scopeId);
						ssrRenderSlot(_ctx.$slots, "trailing", { ui: ui.value }, () => {
							if (unref(isTrailing) && unref(trailingIconName)) _push(ssrRenderComponent(_sfc_main$1, {
								name: unref(trailingIconName),
								"data-slot": "trailingIcon",
								class: ui.value.trailingIcon({ class: unref(uiProp)?.trailingIcon })
							}, null, _parent, _scopeId));
							else _push(`<!---->`);
						}, _push, _parent, _scopeId);
					} else return [
						renderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => [unref(isLeading) && unref(leadingIconName) ? (openBlock(), createBlock(_sfc_main$1, {
							key: 0,
							name: unref(leadingIconName),
							"data-slot": "leadingIcon",
							class: ui.value.leadingIcon({ class: unref(uiProp)?.leadingIcon })
						}, null, 8, ["name", "class"])) : !!__props.avatar ? (openBlock(), createBlock(_sfc_main$2, mergeProps({
							key: 1,
							size: unref(uiProp)?.leadingAvatarSize || ui.value.leadingAvatarSize()
						}, __props.avatar, {
							"data-slot": "leadingAvatar",
							class: ui.value.leadingAvatar({ class: unref(uiProp)?.leadingAvatar })
						}), null, 16, ["size", "class"])) : createCommentVNode("", true)]),
						renderSlot(_ctx.$slots, "default", { ui: ui.value }, () => [__props.label !== void 0 && __props.label !== null ? (openBlock(), createBlock("span", {
							key: 0,
							"data-slot": "label",
							class: ui.value.label({ class: unref(uiProp)?.label })
						}, toDisplayString(__props.label), 3)) : createCommentVNode("", true)]),
						renderSlot(_ctx.$slots, "trailing", { ui: ui.value }, () => [unref(isTrailing) && unref(trailingIconName) ? (openBlock(), createBlock(_sfc_main$1, {
							key: 0,
							name: unref(trailingIconName),
							"data-slot": "trailingIcon",
							class: ui.value.trailingIcon({ class: unref(uiProp)?.trailingIcon })
						}, null, 8, ["name", "class"])) : createCommentVNode("", true)])
					];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$1 = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/@nuxt/ui/dist/runtime/components/Badge.vue");
	return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
//#endregion
//#region resources/js/Pages/Dashboard/Users/Partials/UserDetailsDrawer.vue?vue&type=script&setup=true&lang.ts
var UserDetailsDrawer_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "UserDetailsDrawer",
	__ssrInlineRender: true,
	props: /* @__PURE__ */ mergeModels({
		userId: {},
		locale: { default: "fr-FR" },
		labels: {}
	}, {
		"open": {
			type: Boolean,
			required: true
		},
		"openModifiers": {}
	}),
	emits: /* @__PURE__ */ mergeModels(["closed"], ["update:open"]),
	setup(__props, { emit: __emit }) {
		const open = useModel(__props, "open");
		const props = __props;
		const emit = __emit;
		const user = ref(null);
		const loading = ref(false);
		const error = ref(null);
		let abortController = null;
		const statusConfiguration = {
			active: {
				color: "success",
				icon: "i-lucide-circle-check"
			},
			locked: {
				color: "neutral",
				icon: "i-lucide-circle-pause"
			},
			suspended: {
				color: "warning",
				icon: "i-lucide-shield-alert"
			},
			expired: {
				color: "error",
				icon: "i-lucide-clock-alert"
			},
			trashed: {
				color: "error",
				icon: "i-lucide-trash-2"
			}
		};
		const title = computed(() => {
			return user.value?.username ?? props.labels.title;
		});
		const currentStatus = computed(() => {
			const status = user.value?.status;
			if (!status || !statusConfiguration[status.value]) return {
				label: props.labels.unknown_status,
				color: "neutral",
				icon: "i-lucide-circle-help"
			};
			return {
				label: status.label,
				...statusConfiguration[status.value]
			};
		});
		const fullName = computed(() => {
			const contact = user.value?.contact;
			if (!contact) return null;
			return [contact.first_name, contact.last_name].filter(Boolean).join(" ") || null;
		});
		const initials = computed(() => {
			return (fullName.value ?? user.value?.username ?? "").split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part.charAt(0).toUpperCase()).join("");
		});
		const phoneNumber = computed(() => {
			const phone = user.value?.contact?.phone;
			if (!phone?.number) return null;
			const extension = phone.extension ? String(phone.extension).replace(/^\+/, "") : "";
			const number = String(phone.number);
			return extension ? `+${extension}${number}` : number;
		});
		const canDisplaySensitiveData = computed(() => {
			return Boolean(user.value?.permissions.view_sensitive && user.value?.sensitive);
		});
		const hasAdministrativeDetails = computed(() => {
			const sensitive = user.value?.sensitive;
			if (!sensitive) return false;
			return Boolean((sensitive.roles?.length ?? 0) > 0 || sensitive.last_login || sensitive.expire_at || sensitive.suspended_until || sensitive.suspension_reason || sensitive.admin_notes);
		});
		function formatDate(value) {
			if (!value) return props.labels.not_provided;
			const date = new Date(value);
			if (Number.isNaN(date.getTime())) return props.labels.not_provided;
			return new Intl.DateTimeFormat(props.locale.replace("_", "-"), {
				dateStyle: "medium",
				timeStyle: "short"
			}).format(date);
		}
		function openDetailsPage() {
			router.get(`/users/${props.userId}`);
		}
		async function loadUserDetails() {
			abortController?.abort();
			const currentController = new AbortController();
			abortController = currentController;
			loading.value = true;
			error.value = null;
			user.value = null;
			try {
				const response = await fetch(`/users/${encodeURIComponent(props.userId)}/details`, {
					method: "GET",
					headers: {
						Accept: "application/json",
						"X-Requested-With": "XMLHttpRequest"
					},
					signal: currentController.signal
				});
				if (!response.ok) {
					const body = await response.json().catch(() => null);
					throw new Error(body?.message ?? props.labels.errors.load.replace(":status", String(response.status)));
				}
				user.value = (await response.json()).data;
			} catch (exception) {
				if (exception instanceof DOMException && exception.name === "AbortError") return;
				error.value = exception instanceof Error ? exception.message : props.labels.errors.unknown;
			} finally {
				if (abortController === currentController) loading.value = false;
			}
		}
		async function handleAnimationEnd(isOpen) {
			if (isOpen) return;
			await nextTick();
			emit("closed");
		}
		watch(() => props.userId, () => loadUserDetails(), { immediate: true });
		onBeforeUnmount(() => {
			abortController?.abort();
		});
		return (_ctx, _push, _parent, _attrs) => {
			const _component_UDrawer = _sfc_main$6;
			const _component_UTooltip = _sfc_main$5;
			const _component_UButton = _sfc_main$3;
			const _component_UIcon = _sfc_main$1;
			const _component_UAlert = _sfc_main$4;
			const _component_UAvatar = _sfc_main$2;
			const _component_UBadge = _sfc_main;
			_push(ssrRenderComponent(_component_UDrawer, mergeProps({
				open: open.value,
				"onUpdate:open": ($event) => open.value = $event,
				direction: "right",
				title: title.value,
				description: __props.labels.description,
				class: "w-full sm:max-w-md",
				ui: {
					header: "border-b border-default",
					body: "p-0",
					footer: "border-t border-default"
				},
				onAnimationEnd: handleAnimationEnd
			}, _attrs), {
				header: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex w-full items-start justify-between gap-4"${_scopeId}><div class="min-w-0"${_scopeId}><h2 class="truncate font-semibold"${_scopeId}>${ssrInterpolate(title.value)}</h2><p class="mt-1 text-sm text-muted"${_scopeId}>${ssrInterpolate(__props.labels.description)}</p></div>`);
						_push(ssrRenderComponent(_component_UTooltip, { text: __props.labels.open_full_page }, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(_component_UButton, {
									type: "button",
									icon: "i-lucide-external-link",
									color: "neutral",
									variant: "ghost",
									"aria-label": __props.labels.open_full_page,
									class: "shrink-0 cursor-pointer",
									onClick: openDetailsPage
								}, null, _parent, _scopeId));
								else return [createVNode(_component_UButton, {
									type: "button",
									icon: "i-lucide-external-link",
									color: "neutral",
									variant: "ghost",
									"aria-label": __props.labels.open_full_page,
									class: "shrink-0 cursor-pointer",
									onClick: openDetailsPage
								}, null, 8, ["aria-label"])];
							}),
							_: 1
						}, _parent, _scopeId));
						_push(`</div>`);
					} else return [createVNode("div", { class: "flex w-full items-start justify-between gap-4" }, [createVNode("div", { class: "min-w-0" }, [createVNode("h2", { class: "truncate font-semibold" }, toDisplayString(title.value), 1), createVNode("p", { class: "mt-1 text-sm text-muted" }, toDisplayString(__props.labels.description), 1)]), createVNode(_component_UTooltip, { text: __props.labels.open_full_page }, {
						default: withCtx(() => [createVNode(_component_UButton, {
							type: "button",
							icon: "i-lucide-external-link",
							color: "neutral",
							variant: "ghost",
							"aria-label": __props.labels.open_full_page,
							class: "shrink-0 cursor-pointer",
							onClick: openDetailsPage
						}, null, 8, ["aria-label"])]),
						_: 1
					}, 8, ["text"])])];
				}),
				body: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) if (loading.value) {
						_push(`<div class="flex min-h-64 items-center justify-center"${_scopeId}><div class="flex flex-col items-center gap-3 text-muted"${_scopeId}>`);
						_push(ssrRenderComponent(_component_UIcon, {
							name: "i-lucide-loader-circle",
							class: "size-7 animate-spin"
						}, null, _parent, _scopeId));
						_push(`<span${_scopeId}>${ssrInterpolate(__props.labels.loading)}</span></div></div>`);
					} else if (error.value) {
						_push(`<div class="flex min-h-64 items-center justify-center p-6"${_scopeId}>`);
						_push(ssrRenderComponent(_component_UAlert, {
							color: "error",
							variant: "subtle",
							icon: "i-lucide-circle-alert",
							title: __props.labels.errors.title,
							description: error.value
						}, {
							actions: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(_component_UButton, {
									color: "error",
									variant: "soft",
									icon: "i-lucide-refresh-cw",
									label: __props.labels.actions.retry,
									onClick: loadUserDetails
								}, null, _parent, _scopeId));
								else return [createVNode(_component_UButton, {
									color: "error",
									variant: "soft",
									icon: "i-lucide-refresh-cw",
									label: __props.labels.actions.retry,
									onClick: loadUserDetails
								}, null, 8, ["label"])];
							}),
							_: 1
						}, _parent, _scopeId));
						_push(`</div>`);
					} else if (user.value) {
						_push(`<div class="divide-y divide-default"${_scopeId}><section class="flex items-start gap-4 p-6"${_scopeId}>`);
						_push(ssrRenderComponent(_component_UAvatar, {
							alt: user.value.username,
							text: initials.value,
							size: "xl"
						}, null, _parent, _scopeId));
						_push(`<div class="min-w-0 flex-1"${_scopeId}><div class="flex flex-wrap items-center gap-2"${_scopeId}><p class="truncate text-lg font-semibold"${_scopeId}>${ssrInterpolate(fullName.value ?? user.value.username)}</p>`);
						_push(ssrRenderComponent(_component_UBadge, {
							color: currentStatus.value.color,
							variant: "subtle",
							icon: currentStatus.value.icon,
							label: currentStatus.value.label
						}, null, _parent, _scopeId));
						_push(`</div><p class="truncate text-sm text-muted"${_scopeId}> @${ssrInterpolate(user.value.username)}</p>`);
						if (user.value.contact?.job_title) _push(`<p class="mt-1 text-sm"${_scopeId}>${ssrInterpolate(user.value.contact.job_title)}</p>`);
						else _push(`<!---->`);
						_push(`</div></section><section class="space-y-4 p-6"${_scopeId}><h3 class="font-semibold"${_scopeId}>${ssrInterpolate(__props.labels.sections.contact)}</h3><div class="space-y-3 text-sm"${_scopeId}><a${ssrRenderAttr("href", `mailto:${user.value.email}`)} class="flex items-center gap-3 hover:underline"${_scopeId}>`);
						_push(ssrRenderComponent(_component_UIcon, {
							name: "i-lucide-mail",
							class: "size-4 shrink-0 text-muted"
						}, null, _parent, _scopeId));
						_push(`<span class="break-all"${_scopeId}>${ssrInterpolate(user.value.email)}</span></a>`);
						if (phoneNumber.value) {
							_push(`<a${ssrRenderAttr("href", `tel:${phoneNumber.value}`)} class="flex items-center gap-3 hover:underline"${_scopeId}>`);
							_push(ssrRenderComponent(_component_UIcon, {
								name: "i-lucide-phone",
								class: "size-4 shrink-0 text-muted"
							}, null, _parent, _scopeId));
							_push(`<span${_scopeId}>${ssrInterpolate(phoneNumber.value)}</span></a>`);
						} else _push(`<!---->`);
						_push(`</div></section><section class="space-y-4 p-6"${_scopeId}><h3 class="font-semibold"${_scopeId}>${ssrInterpolate(__props.labels.sections.information)}</h3><dl class="space-y-3 text-sm"${_scopeId}><div class="flex justify-between gap-4"${_scopeId}><dt class="text-muted"${_scopeId}>${ssrInterpolate(__props.labels.fields.id)}</dt><dd${_scopeId}>${ssrInterpolate(user.value.id)}</dd></div><div class="flex justify-between gap-4"${_scopeId}><dt class="text-muted"${_scopeId}>${ssrInterpolate(__props.labels.fields.created_at)}</dt><dd class="text-right"${_scopeId}>${ssrInterpolate(formatDate(user.value.created_at))}</dd></div><div class="flex justify-between gap-4"${_scopeId}><dt class="text-muted"${_scopeId}>${ssrInterpolate(__props.labels.fields.updated_at)}</dt><dd class="text-right"${_scopeId}>${ssrInterpolate(formatDate(user.value.updated_at))}</dd></div></dl></section>`);
						if (canDisplaySensitiveData.value && hasAdministrativeDetails.value && user.value.sensitive) {
							_push(`<section class="space-y-4 p-6"${_scopeId}><div class="flex items-center gap-2"${_scopeId}>`);
							_push(ssrRenderComponent(_component_UIcon, {
								name: "i-lucide-shield",
								class: "size-4 text-muted"
							}, null, _parent, _scopeId));
							_push(`<h3 class="font-semibold"${_scopeId}>${ssrInterpolate(__props.labels.sections.administration)}</h3></div>`);
							if (user.value.sensitive.roles.length) {
								_push(`<div${_scopeId}><p class="mb-2 text-sm text-muted"${_scopeId}>${ssrInterpolate(__props.labels.fields.roles)}</p><div class="flex flex-wrap gap-2"${_scopeId}><!--[-->`);
								ssrRenderList(user.value.sensitive.roles, (role) => {
									_push(ssrRenderComponent(_component_UBadge, {
										key: role.id,
										color: "info",
										variant: "subtle",
										label: role.name
									}, null, _parent, _scopeId));
								});
								_push(`<!--]--></div></div>`);
							} else _push(`<!---->`);
							_push(`<dl class="space-y-3 text-sm"${_scopeId}>`);
							if (user.value.sensitive.last_login) _push(`<div class="flex justify-between gap-4"${_scopeId}><dt class="text-muted"${_scopeId}>${ssrInterpolate(__props.labels.fields.last_login)}</dt><dd class="text-right"${_scopeId}>${ssrInterpolate(formatDate(user.value.sensitive.last_login))}</dd></div>`);
							else _push(`<!---->`);
							if (user.value.sensitive.expire_at) _push(`<div class="flex justify-between gap-4"${_scopeId}><dt class="text-muted"${_scopeId}>${ssrInterpolate(__props.labels.fields.expire_at)}</dt><dd class="text-right"${_scopeId}>${ssrInterpolate(formatDate(user.value.sensitive.expire_at))}</dd></div>`);
							else _push(`<!---->`);
							if (user.value.sensitive.suspended_until) _push(`<div class="flex justify-between gap-4"${_scopeId}><dt class="text-muted"${_scopeId}>${ssrInterpolate(__props.labels.fields.suspended_until)}</dt><dd class="text-right"${_scopeId}>${ssrInterpolate(formatDate(user.value.sensitive.suspended_until))}</dd></div>`);
							else _push(`<!---->`);
							_push(`</dl>`);
							if (user.value.sensitive.suspension_reason) _push(ssrRenderComponent(_component_UAlert, {
								color: "warning",
								variant: "subtle",
								icon: "i-lucide-shield-alert",
								title: __props.labels.fields.suspension_reason,
								description: user.value.sensitive.suspension_reason
							}, null, _parent, _scopeId));
							else _push(`<!---->`);
							if (user.value.sensitive.admin_notes) _push(`<div${_scopeId}><p class="mb-2 text-sm font-medium"${_scopeId}>${ssrInterpolate(__props.labels.fields.admin_notes)}</p><p class="whitespace-pre-wrap text-sm text-muted"${_scopeId}>${ssrInterpolate(user.value.sensitive.admin_notes)}</p></div>`);
							else _push(`<!---->`);
							_push(`</section>`);
						} else _push(`<!---->`);
						_push(`</div>`);
					} else _push(`<!---->`);
					else return [loading.value ? (openBlock(), createBlock("div", {
						key: 0,
						class: "flex min-h-64 items-center justify-center"
					}, [createVNode("div", { class: "flex flex-col items-center gap-3 text-muted" }, [createVNode(_component_UIcon, {
						name: "i-lucide-loader-circle",
						class: "size-7 animate-spin"
					}), createVNode("span", null, toDisplayString(__props.labels.loading), 1)])])) : error.value ? (openBlock(), createBlock("div", {
						key: 1,
						class: "flex min-h-64 items-center justify-center p-6"
					}, [createVNode(_component_UAlert, {
						color: "error",
						variant: "subtle",
						icon: "i-lucide-circle-alert",
						title: __props.labels.errors.title,
						description: error.value
					}, {
						actions: withCtx(() => [createVNode(_component_UButton, {
							color: "error",
							variant: "soft",
							icon: "i-lucide-refresh-cw",
							label: __props.labels.actions.retry,
							onClick: loadUserDetails
						}, null, 8, ["label"])]),
						_: 1
					}, 8, ["title", "description"])])) : user.value ? (openBlock(), createBlock("div", {
						key: 2,
						class: "divide-y divide-default"
					}, [
						createVNode("section", { class: "flex items-start gap-4 p-6" }, [createVNode(_component_UAvatar, {
							alt: user.value.username,
							text: initials.value,
							size: "xl"
						}, null, 8, ["alt", "text"]), createVNode("div", { class: "min-w-0 flex-1" }, [
							createVNode("div", { class: "flex flex-wrap items-center gap-2" }, [createVNode("p", { class: "truncate text-lg font-semibold" }, toDisplayString(fullName.value ?? user.value.username), 1), createVNode(_component_UBadge, {
								color: currentStatus.value.color,
								variant: "subtle",
								icon: currentStatus.value.icon,
								label: currentStatus.value.label
							}, null, 8, [
								"color",
								"icon",
								"label"
							])]),
							createVNode("p", { class: "truncate text-sm text-muted" }, " @" + toDisplayString(user.value.username), 1),
							user.value.contact?.job_title ? (openBlock(), createBlock("p", {
								key: 0,
								class: "mt-1 text-sm"
							}, toDisplayString(user.value.contact.job_title), 1)) : createCommentVNode("", true)
						])]),
						createVNode("section", { class: "space-y-4 p-6" }, [createVNode("h3", { class: "font-semibold" }, toDisplayString(__props.labels.sections.contact), 1), createVNode("div", { class: "space-y-3 text-sm" }, [createVNode("a", {
							href: `mailto:${user.value.email}`,
							class: "flex items-center gap-3 hover:underline"
						}, [createVNode(_component_UIcon, {
							name: "i-lucide-mail",
							class: "size-4 shrink-0 text-muted"
						}), createVNode("span", { class: "break-all" }, toDisplayString(user.value.email), 1)], 8, ["href"]), phoneNumber.value ? (openBlock(), createBlock("a", {
							key: 0,
							href: `tel:${phoneNumber.value}`,
							class: "flex items-center gap-3 hover:underline"
						}, [createVNode(_component_UIcon, {
							name: "i-lucide-phone",
							class: "size-4 shrink-0 text-muted"
						}), createVNode("span", null, toDisplayString(phoneNumber.value), 1)], 8, ["href"])) : createCommentVNode("", true)])]),
						createVNode("section", { class: "space-y-4 p-6" }, [createVNode("h3", { class: "font-semibold" }, toDisplayString(__props.labels.sections.information), 1), createVNode("dl", { class: "space-y-3 text-sm" }, [
							createVNode("div", { class: "flex justify-between gap-4" }, [createVNode("dt", { class: "text-muted" }, toDisplayString(__props.labels.fields.id), 1), createVNode("dd", null, toDisplayString(user.value.id), 1)]),
							createVNode("div", { class: "flex justify-between gap-4" }, [createVNode("dt", { class: "text-muted" }, toDisplayString(__props.labels.fields.created_at), 1), createVNode("dd", { class: "text-right" }, toDisplayString(formatDate(user.value.created_at)), 1)]),
							createVNode("div", { class: "flex justify-between gap-4" }, [createVNode("dt", { class: "text-muted" }, toDisplayString(__props.labels.fields.updated_at), 1), createVNode("dd", { class: "text-right" }, toDisplayString(formatDate(user.value.updated_at)), 1)])
						])]),
						canDisplaySensitiveData.value && hasAdministrativeDetails.value && user.value.sensitive ? (openBlock(), createBlock("section", {
							key: 0,
							class: "space-y-4 p-6"
						}, [
							createVNode("div", { class: "flex items-center gap-2" }, [createVNode(_component_UIcon, {
								name: "i-lucide-shield",
								class: "size-4 text-muted"
							}), createVNode("h3", { class: "font-semibold" }, toDisplayString(__props.labels.sections.administration), 1)]),
							user.value.sensitive.roles.length ? (openBlock(), createBlock("div", { key: 0 }, [createVNode("p", { class: "mb-2 text-sm text-muted" }, toDisplayString(__props.labels.fields.roles), 1), createVNode("div", { class: "flex flex-wrap gap-2" }, [(openBlock(true), createBlock(Fragment, null, renderList(user.value.sensitive.roles, (role) => {
								return openBlock(), createBlock(_component_UBadge, {
									key: role.id,
									color: "info",
									variant: "subtle",
									label: role.name
								}, null, 8, ["label"]);
							}), 128))])])) : createCommentVNode("", true),
							createVNode("dl", { class: "space-y-3 text-sm" }, [
								user.value.sensitive.last_login ? (openBlock(), createBlock("div", {
									key: 0,
									class: "flex justify-between gap-4"
								}, [createVNode("dt", { class: "text-muted" }, toDisplayString(__props.labels.fields.last_login), 1), createVNode("dd", { class: "text-right" }, toDisplayString(formatDate(user.value.sensitive.last_login)), 1)])) : createCommentVNode("", true),
								user.value.sensitive.expire_at ? (openBlock(), createBlock("div", {
									key: 1,
									class: "flex justify-between gap-4"
								}, [createVNode("dt", { class: "text-muted" }, toDisplayString(__props.labels.fields.expire_at), 1), createVNode("dd", { class: "text-right" }, toDisplayString(formatDate(user.value.sensitive.expire_at)), 1)])) : createCommentVNode("", true),
								user.value.sensitive.suspended_until ? (openBlock(), createBlock("div", {
									key: 2,
									class: "flex justify-between gap-4"
								}, [createVNode("dt", { class: "text-muted" }, toDisplayString(__props.labels.fields.suspended_until), 1), createVNode("dd", { class: "text-right" }, toDisplayString(formatDate(user.value.sensitive.suspended_until)), 1)])) : createCommentVNode("", true)
							]),
							user.value.sensitive.suspension_reason ? (openBlock(), createBlock(_component_UAlert, {
								key: 1,
								color: "warning",
								variant: "subtle",
								icon: "i-lucide-shield-alert",
								title: __props.labels.fields.suspension_reason,
								description: user.value.sensitive.suspension_reason
							}, null, 8, ["title", "description"])) : createCommentVNode("", true),
							user.value.sensitive.admin_notes ? (openBlock(), createBlock("div", { key: 2 }, [createVNode("p", { class: "mb-2 text-sm font-medium" }, toDisplayString(__props.labels.fields.admin_notes), 1), createVNode("p", { class: "whitespace-pre-wrap text-sm text-muted" }, toDisplayString(user.value.sensitive.admin_notes), 1)])) : createCommentVNode("", true)
						])) : createCommentVNode("", true)
					])) : createCommentVNode("", true)];
				}),
				footer: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="flex w-full justify-end mt-4"${_scopeId}>`);
						_push(ssrRenderComponent(_component_UButton, {
							color: "neutral",
							variant: "outline",
							label: __props.labels.actions.close,
							onClick: ($event) => open.value = false
						}, null, _parent, _scopeId));
						_push(`</div>`);
					} else return [createVNode("div", { class: "flex w-full justify-end mt-4" }, [createVNode(_component_UButton, {
						color: "neutral",
						variant: "outline",
						label: __props.labels.actions.close,
						onClick: ($event) => open.value = false
					}, null, 8, ["label", "onClick"])])];
				}),
				_: 1
			}, _parent));
		};
	}
});
//#endregion
//#region resources/js/Pages/Dashboard/Users/Partials/UserDetailsDrawer.vue
var _sfc_setup = UserDetailsDrawer_vue_vue_type_script_setup_true_lang_default.setup;
UserDetailsDrawer_vue_vue_type_script_setup_true_lang_default.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Dashboard/Users/Partials/UserDetailsDrawer.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
var UserDetailsDrawer_default = UserDetailsDrawer_vue_vue_type_script_setup_true_lang_default;
//#endregion
export { _sfc_main as n, UserDetailsDrawer_default as t };

//# sourceMappingURL=UserDetailsDrawer-DinJzwg2.js.map