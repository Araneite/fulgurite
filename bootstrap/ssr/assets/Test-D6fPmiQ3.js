import { ssrIncludeBooleanAttr, ssrInterpolate, ssrRenderAttr, ssrRenderAttrs, ssrRenderClass, ssrRenderComponent, ssrRenderList, ssrRenderSlot, ssrRenderVNode } from "vue/server-renderer";
import { computed, createBlock, createCommentVNode, createTextVNode, createVNode, mergeProps, onBeforeUnmount, openBlock, ref, renderSlot, resolveDynamicComponent, toDisplayString, useAttrs, useSSRContext, withCtx } from "vue";
//#region resources/js/Components/Button/Button.vue
var _sfc_main$1 = /* @__PURE__ */ Object.assign({ inheritAttrs: false }, {
	__name: "Button",
	__ssrInlineRender: true,
	props: {
		label: {
			type: String,
			default: null
		},
		icon: {
			type: String,
			default: null
		},
		href: {
			type: String,
			default: null
		},
		split: {
			type: Boolean,
			default: false
		},
		splitClass: {
			type: String,
			default: ""
		},
		actions: {
			type: Array,
			default: () => []
		},
		severity: {
			type: String,
			default: "primary",
			validator: (value) => [
				"primary",
				"secondary",
				"success",
				"info",
				"warning",
				"danger"
			].includes(value)
		},
		disabled: {
			type: Boolean,
			default: false
		},
		menuLabel: {
			type: String,
			default: "Action supplémentaire"
		}
	},
	emits: [
		"click",
		"action-click",
		"menu-open",
		"menu-close"
	],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emit = __emit;
		const attrs = useAttrs();
		const root = ref(null);
		const isMenuOpen = ref(false);
		const isLink = computed(() => Boolean(props.href));
		const buttonType = computed(() => attrs.type ?? "button");
		const severityClasses = computed(() => ({
			primary: "border-transparent bg-primary-500 text-text-800 hover:bg-primary-600 focus-visible:bg-primary-600",
			secondary: "border-primary/60 bg-transparent text-current hover:border-primary/80 hover:bg-primary/10 focus-visible:border-primary/80 focus-visible:bg-primary/10",
			tertiary: "border-white/20 bg-transparent text-current hover:bg-white/5",
			danger: "border-transparent bg-red-600 text-white hover:bg-red-700 focus-visible:bg-red-700"
		})[props.severity]);
		const baseButtonClasses = computed(() => [
			"inline-flex min-h-11 items-center justify-center gap-2 border px-4 py-2.5 text-[0.95rem] font-bold leading-none no-underline outline-none transition-colors duration-200",
			"focus-visible:ring-1 focus-visible:ring-primary",
			props.disabled ? "cursor-not-allowed opacity-55" : "cursor-pointer",
			severityClasses.value
		]);
		const mainButtonClasses = computed(() => [
			...baseButtonClasses.value,
			props.split ? "rounded-l-lg rounded-r-none" : "rounded-lg",
			attrs.class
		]);
		const splitButtonClasses = computed(() => [...baseButtonClasses.value, "w-11 rounded-l-none rounded-r-lg border-l-black/20 px-0"]);
		const forwardedAttrs = computed(() => {
			const { class: __class, type: __type, ...rest } = attrs;
			return rest;
		});
		const visibleActions = computed(() => {
			return props.actions.filter((action) => action.visible !== false);
		});
		const iconClass = computed(() => normalizeIcon(props.icon));
		function normalizeIcon(icon) {
			if (!icon) return null;
			if (icon.startsWith("bi ")) return icon;
			if (icon.startsWith("bi-")) return `bi ${icon}`;
			return `bi bi-${icon}`;
		}
		function closeMenu() {
			if (!isMenuOpen.value) return;
			isMenuOpen.value = false;
			emit("menu-close");
			document.removeEventListener("click", handleOutsideClick);
			document.removeEventListener("keydown", handleKeydown);
		}
		function handleOutsideClick(event) {
			if (!root.value || root.value.contains(event.target)) return;
			closeMenu();
		}
		function handleKeydown(event) {
			if (event.key === "Escape") closeMenu();
		}
		function handleMainClick(event) {
			if (props.disabled) {
				event.preventDefault();
				return;
			}
			emit("click", event);
		}
		function handleActionClick(action, event) {
			if (action.disabled) {
				event.preventDefault();
				return;
			}
			emit("qction-click", action, event);
			if (typeof action.command === "function") action.command({
				originalEvent: event,
				item: action
			});
			closeMenu();
		}
		onBeforeUnmount(() => {
			document.removeEventListener("click", handleOutsideClick);
			document.removeEventListerner("keydown", handleKeydown);
		});
		return (_ctx, _push, _parent, _attrs) => {
			_push(`<div${ssrRenderAttrs(mergeProps({
				ref_key: "root",
				ref: root,
				class: "relative inline-flex align-middle"
			}, _attrs))}>`);
			ssrRenderVNode(_push, createVNode(resolveDynamicComponent(isLink.value ? "a" : "button"), mergeProps(forwardedAttrs.value, {
				href: isLink.value && !__props.disabled ? __props.href : void 0,
				type: isLink.value ? void 0 : buttonType.value,
				"aria-disabled": __props.disabled || void 0,
				disabled: !isLink.value ? __props.disabled : void 0,
				class: mainButtonClasses.value,
				onClick: handleMainClick
			}), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						if (iconClass.value) _push(`<span class="${ssrRenderClass(iconClass.value)}" aria-hidden="true"${_scopeId}></span>`);
						else _push(`<!---->`);
						if (_ctx.$slots.default || __props.label) {
							_push(`<span class="min-w-0"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "default", {}, () => {
								_push(`${ssrInterpolate(__props.label)}`);
							}, _push, _parent, _scopeId);
							_push(`</span>`);
						} else _push(`<!---->`);
					} else return [iconClass.value ? (openBlock(), createBlock("span", {
						key: 0,
						class: iconClass.value,
						"aria-hidden": "true"
					}, null, 2)) : createCommentVNode("", true), _ctx.$slots.default || __props.label ? (openBlock(), createBlock("span", {
						key: 1,
						class: "min-w-0"
					}, [renderSlot(_ctx.$slots, "default", {}, () => [createTextVNode(toDisplayString(__props.label), 1)])])) : createCommentVNode("", true)];
				}),
				_: 3
			}), _parent);
			if (__props.split) _push(`<button type="button" class="${ssrRenderClass(splitButtonClasses.value)}"${ssrIncludeBooleanAttr(__props.disabled || visibleActions.value.length === 0) ? " disabled" : ""}${ssrRenderAttr("aria-label", __props.menuLabel)}${ssrRenderAttr("aria-expanded", isMenuOpen.value)} aria-haspopup="menu"><span class="bi bi-chevron-down" aria-hidden="true"></span></button>`);
			else _push(`<!---->`);
			if (__props.split && isMenuOpen.value) {
				_push(`<div class="absolute right-0 top-[calc(100%+0.35rem)] z-50 min-w-52 overflow-hidden rounded-lg border border-white/15 bg-night p-1.5 text-white shadow-[0_18px_45px_rgb(0_0_0/32%)]" role="menu"><!--[-->`);
				ssrRenderList(visibleActions.value, (action) => {
					_push(`<!--[-->`);
					if (action.separator) _push(`<div class="my-1 h-px bg-white/10" role="separator"></div>`);
					else ssrRenderVNode(_push, createVNode(resolveDynamicComponent(action.href ? "a" : "button"), {
						href: action.disabled ? void 0 : action.href,
						target: action.target,
						rel: action.target === "_blank" ? "noopener noreferrer" : void 0,
						type: "button",
						class: ["flex w-full items-center gap-2.5 rounded-md bordr-0 bg-transparent px-3 py-2.5 text-left text-inherit no-underline outline-none hover:bg-white/7 focus-visible:bg-white/7", [action.class, {
							"text-red-400": action.danger,
							"pointer-events-none cursor-not-allowed opacity-50": action.disabled,
							"cursor-pointer": !action.disabled
						}]],
						disabled: !action.href ? action.disabled : void 0,
						"aria-disabled": action.disabled || void 0,
						role: "menuitem",
						onClick: ($event) => handleActionClick(action, $event)
					}, {
						default: withCtx((_, _push, _parent, _scopeId) => {
							if (_push) {
								if (normalizeIcon(action.icon)) _push(`<span class="${ssrRenderClass(normalizeIcon(action.icon))}" aria-hidden="true"${_scopeId}></span>`);
								else _push(`<!---->`);
								_push(`<span class="min-w-0 flex-1"${_scopeId}>`);
								ssrRenderSlot(_ctx.$slots, "action", { action }, () => {
									_push(`${ssrInterpolate(action.label)}`);
								}, _push, _parent, _scopeId);
								_push(`</span>`);
							} else return [normalizeIcon(action.icon) ? (openBlock(), createBlock("span", {
								key: 0,
								class: normalizeIcon(action.icon),
								"aria-hidden": "true"
							}, null, 2)) : createCommentVNode("", true), createVNode("span", { class: "min-w-0 flex-1" }, [renderSlot(_ctx.$slots, "action", { action }, () => [createTextVNode(toDisplayString(action.label), 1)])])];
						}),
						_: 2
					}), _parent);
					_push(`<!--]-->`);
				});
				_push(`<!--]--></div>`);
			} else _push(`<!---->`);
			_push(`</div>`);
		};
	}
});
var _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Button/Button.vue");
	return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
//#endregion
//#region resources/js/Pages/Dashboard/Users/Test.vue
var _sfc_main = {
	__name: "Test",
	__ssrInlineRender: true,
	setup(__props) {
		ref([]);
		const usersAction = [
			{
				label: "Modifier",
				icon: "pencil",
				command: ({ item }) => {
					console.log("edit : " + item);
				}
			},
			{
				label: "Voir le profil",
				icon: "person",
				href: "/profile"
			},
			{ separator: true },
			{
				label: "Supprimer",
				icon: "trash",
				danger: true,
				command: ({ item }) => {
					console.log("delete : " + item);
				}
			}
		];
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(_sfc_main$1, mergeProps({
				split: "",
				icon: "gear",
				actions: usersAction,
				class: "flex items-center justify-center",
				href: "/test",
				onClick: () => console.log("test")
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(`<p class="font-bold"${_scopeId}>Options avancées</p>`);
					else return [createVNode("p", { class: "font-bold" }, "Options avancées")];
				}),
				_: 1
			}, _parent));
		};
	}
};
var _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Dashboard/Users/Test.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
//#endregion
export { _sfc_main as default };

//# sourceMappingURL=Test-D6fPmiQ3.js.map