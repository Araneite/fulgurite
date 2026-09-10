import { r as _sfc_main$1 } from "./usePortal-DZb6nPjI.js";
import { t as _sfc_main$2 } from "./AuthLayout-BCZkOr_K.js";
import { r as _sfc_main$4, t as _sfc_main$3 } from "./Select-Yh80p2Xl.js";
import { router, useForm } from "@inertiajs/vue3";
import { ssrInterpolate, ssrRenderAttr, ssrRenderAttrs, ssrRenderComponent } from "vue/server-renderer";
import { computed, mergeProps, unref, useSSRContext } from "vue";
import "@laravel/passkeys";
//#region resources/js/Pages/Auth/TwoFactorChallenge.vue
var _sfc_main = /* @__PURE__ */ Object.assign({ layout: _sfc_main$2 }, {
	__name: "TwoFactorChallenge",
	__ssrInlineRender: true,
	props: {
		methods: {
			type: Array,
			required: true
		},
		selectedMethod: {
			type: String,
			required: true
		},
		maskedEmail: {
			type: String,
			required: true
		},
		trans: {
			type: Object,
			required: true
		}
	},
	setup(__props) {
		const props = __props;
		computed(() => form.method === "passkey");
		const form = useForm({
			method: props.selectedMethod,
			code: ""
		});
		const availableMethods = computed(() => props.methods ?? []);
		const hasMethodSwitcher = computed(() => availableMethods.value.length > 1);
		const requiresOtp = computed(() => ["email", "one_time_code"].includes(form.method));
		console.log(props.methods);
		const selectedMethodLabel = computed(() => {
			return availableMethods.value.find((method) => method.value === form.method)?.label ?? "";
		});
		function changeMethod() {
			form.code = "";
			form.clearErrors();
			router.post("/a2f/method", { method: form.method }, {
				preserveScroll: true,
				preserveState: true
			});
		}
		return (_ctx, _push, _parent, _attrs) => {
			const _component_USelect = _sfc_main$3;
			const _component_UPinInput = _sfc_main$4;
			const _component_UButton = _sfc_main$1;
			_push(`<form${ssrRenderAttrs(mergeProps({ class: "box classic-form" }, _attrs))}><div class="form-header"><div class="logo"><img${ssrRenderAttr("src", "/assets/img/fulgurite-logo.svg")} alt="Fulgurite" class="logo-icon"><h2>Fulgurite</h2></div><p class="mt-3 text-center text-sm text-text-400">${ssrInterpolate(__props.trans.subtitle)}</p></div>`);
			if (hasMethodSwitcher.value) {
				_push(`<div class="flex w-full flex-col gap-2"><label for="two_factor_method" class="text-sm text-text-300">${ssrInterpolate(__props.trans.fields.method)}</label>`);
				_push(ssrRenderComponent(_component_USelect, {
					id: "two_factor_method",
					modelValue: unref(form).method,
					"onUpdate:modelValue": ($event) => unref(form).method = $event,
					items: availableMethods.value,
					"option-label": "label",
					"option-value": "value",
					class: "w-full",
					onChange: changeMethod
				}, null, _parent));
				_push(`</div>`);
			} else _push(`<!---->`);
			if (requiresOtp.value) {
				_push(`<div class="flex flex-col items-center gap-3"><label for="two_factor_code" class="text-sm text-text-300">${ssrInterpolate(selectedMethodLabel.value)}</label>`);
				_push(ssrRenderComponent(_component_UPinInput, {
					otp: "",
					id: "two_factor_code",
					name: "code",
					length: 6,
					modelValue: unref(form).code,
					"onUpdate:modelValue": ($event) => unref(form).code = $event
				}, null, _parent));
				if (unref(form).errors.code) _push(`<p class="text-sm text-red-400">${ssrInterpolate(unref(form).errors.code)}</p>`);
				else _push(`<!---->`);
				_push(`</div>`);
			} else _push(`<!---->`);
			if (unref(form).errors.passkey) _push(`<p class="text-sm text-red-400">${ssrInterpolate(unref(form).errors.passkey)}</p>`);
			else _push(`<!---->`);
			_push(ssrRenderComponent(_component_UButton, {
				label: __props.trans.confirm_button,
				type: "submit",
				class: "w-full"
			}, null, _parent));
			_push(`</form>`);
		};
	}
});
var _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Auth/TwoFactorChallenge.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
//#endregion
export { _sfc_main as default };

//# sourceMappingURL=TwoFactorChallenge-CQkbz1Y5.js.map