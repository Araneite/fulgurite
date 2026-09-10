import { n as _sfc_main$1, r as _sfc_main$2, t as _sfc_main } from "./Form-DAQH5xxu.js";
import { n as useToast } from "./useToast-itvNvMq-.js";
import { r as _sfc_main$3, t as _sfc_main$4 } from "./PhoneInput-aIWeFFh9.js";
import { t as _sfc_main$5 } from "./Tooltip-_q5JW3sO.js";
import { useForm } from "@inertiajs/vue3";
import { ssrInterpolate, ssrRenderComponent } from "vue/server-renderer";
import { computed, createBlock, createCommentVNode, createVNode, defineComponent, mergeProps, nextTick, openBlock, ref, toDisplayString, unref, useSSRContext, watch, withCtx } from "vue";
import * as z from "zod";
//#region resources/js/utils/snapshot-form.js
function clone(value) {
	return JSON.parse(JSON.stringify(value));
}
function normalizeForDirty(value) {
	if (value === null || value === void 0) return "";
	if (typeof value === "string") return value.replace(/\r\n/g, "\n").trim();
	if (typeof value === "number" || typeof value === "boolean") return String(value);
	return JSON.stringify(value);
}
function getValueByPath(source, path) {
	return path.split(".").reduce((value, key) => value?.[key], source);
}
/**
* Check if a field in form is dirty
* Can be used for highlight unsaved field for example
* 
* @param form - The vue form related to field
* @param initialValues - The ref of initial values
* @param fieldPath - The field to check if is dirty
* @returns {boolean}
*/
function isFieldDirty(form, initialValues, fieldPath) {
	return normalizeForDirty(getValueByPath(form, fieldPath)) !== normalizeForDirty(getValueByPath(initialValues, fieldPath));
}
/**
* 
* @param form - The vue form
* @param initialValues - The ref to inital values
* @param fields - Array of fields to check
* @returns {*}
*/
function hasDirtyFields(form, initialValues, fields) {
	return fields.some((fieldPath) => {
		return isFieldDirty(form, initialValues, fieldPath);
	});
}
//#endregion
//#region resources/js/Pages/Dashboard/Users/Partials/UserQuickEditForm.vue?vue&type=script&setup=true&lang.ts
var UserQuickEditForm_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "UserQuickEditForm",
	__ssrInlineRender: true,
	props: {
		__: {},
		countries: {},
		user: {}
	},
	emits: [
		"saved",
		"dirty",
		"closeRequested"
	],
	setup(__props, { expose: __expose, emit: __emit }) {
		const props = __props;
		const emit = __emit;
		const toast = useToast();
		const initialForm = ref({});
		const dirtyFields = [
			"username",
			"email",
			"job_title",
			"phone.extension",
			"phone.number",
			"admin_notes"
		];
		const schema = z.object({
			id: z.coerce.number().int(),
			email: z.email(props.__.errors.inputs.email_invalid),
			username: z.string(props.__.errors.inputs.string).min(1),
			job_title: z.string(props.__.errors.inputs.string).nullable().optional(),
			phone: z.object({
				extension: z.string().nullable().optional(),
				number: z.string().nullable().optional()
			}),
			admin_notes: z.string(props.__.errors.inputs.string).nullable().optional()
		});
		const form = useForm({
			id: "",
			email: "",
			username: "",
			job_title: "",
			phone: {
				extension: "",
				number: ""
			},
			admin_notes: ""
		});
		const hasUnsavedChanges = computed(() => {
			return hasDirtyFields(form, initialForm.value, dirtyFields);
		});
		const drawerDescription = computed(() => {
			return `${props.__.forms.users.edit.description} "${form.username}"`;
		});
		function snapshot() {
			initialForm.value = clone({
				email: form.email,
				username: form.username,
				job_title: form.job_title,
				phone: {
					extension: form.phone.extension,
					number: form.phone.number
				},
				admin_notes: form.admin_notes
			});
		}
		function fillForm(user) {
			const data = {
				id: user.id ?? "",
				email: user.email ?? "",
				username: user.username ?? "",
				job_title: user.job_title ?? "",
				phone: {
					extension: user.phone?.extension ? String(user.phone.extension) : "",
					number: user.phone?.phone ? String(user.phone.phone) : ""
				},
				admin_notes: user.admin_notes ?? ""
			};
			form.defaults(data);
			form.id = data.id;
			form.email = data.email;
			form.username = data.username;
			form.job_title = data.job_title;
			form.phone = data.phone;
			form.admin_notes = data.admin_notes;
			form.clearErrors();
			nextTick(snapshot);
		}
		function open(user) {
			fillForm(user);
		}
		function close() {
			form.reset();
			form.clearErrors();
		}
		function submit() {
			form.clearErrors();
			const result = schema.safeParse(form.data());
			if (!result.success) {
				const errors = result.error.flatten().fieldErrors;
				Object.entries(errors).forEach(([field, messages]) => {
					form.setError(field, messages?.[0] ?? "");
				});
				return;
			}
			form.transform(() => result.data).patch(`/users/${form.id}/quick-edit`, {
				only: [
					"users",
					"filters",
					"flash"
				],
				preserveScroll: true,
				preserveState: true,
				onSuccess: () => {
					close();
					emit("saved");
				},
				onError: (errors) => {
					if (errors?.action) toast.add({
						title: errors.action,
						color: "error"
					});
				}
			});
		}
		watch(() => props.user, (user) => {
			if (!user) return;
			fillForm(user);
		}, {
			immediate: true,
			flush: "post"
		});
		__expose({
			open,
			close,
			submit,
			title: computed(() => props.__.forms.users.edit.title),
			description: drawerDescription,
			processing: computed(() => form.processing),
			disabled: computed(() => false),
			dirty: hasUnsavedChanges
		});
		return (_ctx, _push, _parent, _attrs) => {
			const _component_UForm = _sfc_main;
			const _component_UFormField = _sfc_main$1;
			const _component_UTooltip = _sfc_main$5;
			const _component_UInput = _sfc_main$2;
			const _component_UTextarea = _sfc_main$3;
			_push(ssrRenderComponent(_component_UForm, mergeProps({
				class: "flex flex-col gap-4",
				schema: unref(schema),
				state: unref(form),
				id: "quick-edit-form",
				onSubmit: submit
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(ssrRenderComponent(_component_UFormField, {
							label: props.__.resources.users.fields.username,
							help: props.__.forms.users.help.username,
							error: unref(form).errors.username,
							required: "",
							name: "username"
						}, {
							label: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) if (unref(isFieldDirty)(unref(form), initialForm.value, "username")) _push(ssrRenderComponent(_component_UTooltip, { text: props.__.global.forms.unsaved_change }, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) _push(`<span class="inline-flex items-center gap-1"${_scopeId}><span${_scopeId}>${ssrInterpolate(props.__.resources.users.fields.username)}</span><span class="inline-block size-1.5 shrink-0 rounded-full bg-warning"${_scopeId}></span></span>`);
										else return [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.username), 1), createVNode("span", { class: "inline-block size-1.5 shrink-0 rounded-full bg-warning" })])];
									}),
									_: 1
								}, _parent, _scopeId));
								else _push(`<span${_scopeId}>${ssrInterpolate(props.__.resources.users.fields.username)}</span>`);
								else return [unref(isFieldDirty)(unref(form), initialForm.value, "username") ? (openBlock(), createBlock(_component_UTooltip, {
									key: 0,
									text: props.__.global.forms.unsaved_change
								}, {
									default: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.username), 1), createVNode("span", { class: "inline-block size-1.5 shrink-0 rounded-full bg-warning" })])]),
									_: 1
								}, 8, ["text"])) : (openBlock(), createBlock("span", { key: 1 }, toDisplayString(props.__.resources.users.fields.username), 1))];
							}),
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(_component_UInput, {
									modelValue: unref(form).username,
									"onUpdate:modelValue": ($event) => unref(form).username = $event,
									placeholder: " ",
									ui: { base: "peer" },
									required: "",
									class: "w-full",
									size: "lg"
								}, null, _parent, _scopeId));
								else return [createVNode(_component_UInput, {
									modelValue: unref(form).username,
									"onUpdate:modelValue": ($event) => unref(form).username = $event,
									placeholder: " ",
									ui: { base: "peer" },
									required: "",
									class: "w-full",
									size: "lg"
								}, null, 8, ["modelValue", "onUpdate:modelValue"])];
							}),
							_: 1
						}, _parent, _scopeId));
						_push(ssrRenderComponent(_component_UFormField, {
							label: props.__.resources.users.fields.email,
							help: props.__.forms.users.help.email,
							error: unref(form).errors.email,
							required: "",
							name: "email"
						}, {
							label: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) if (unref(isFieldDirty)(unref(form), initialForm.value, "email")) _push(ssrRenderComponent(_component_UTooltip, { text: props.__.global.forms.unsaved_change }, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) _push(`<span class="inline-flex items-center gap-1"${_scopeId}><span${_scopeId}>${ssrInterpolate(props.__.resources.users.fields.email)}</span><span class="inline-block size-1.5 shrink-0 rounded-full bg-warning"${_scopeId}></span></span>`);
										else return [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.email), 1), createVNode("span", { class: "inline-block size-1.5 shrink-0 rounded-full bg-warning" })])];
									}),
									_: 1
								}, _parent, _scopeId));
								else _push(`<span${_scopeId}>${ssrInterpolate(props.__.resources.users.fields.email)}</span>`);
								else return [unref(isFieldDirty)(unref(form), initialForm.value, "email") ? (openBlock(), createBlock(_component_UTooltip, {
									key: 0,
									text: props.__.global.forms.unsaved_change
								}, {
									default: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.email), 1), createVNode("span", { class: "inline-block size-1.5 shrink-0 rounded-full bg-warning" })])]),
									_: 1
								}, 8, ["text"])) : (openBlock(), createBlock("span", { key: 1 }, toDisplayString(props.__.resources.users.fields.email), 1))];
							}),
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(_component_UInput, {
									modelValue: unref(form).email,
									"onUpdate:modelValue": ($event) => unref(form).email = $event,
									placeholder: " ",
									ui: { base: "peer" },
									required: "",
									class: "w-full",
									size: "lg"
								}, null, _parent, _scopeId));
								else return [createVNode(_component_UInput, {
									modelValue: unref(form).email,
									"onUpdate:modelValue": ($event) => unref(form).email = $event,
									placeholder: " ",
									ui: { base: "peer" },
									required: "",
									class: "w-full",
									size: "lg"
								}, null, 8, ["modelValue", "onUpdate:modelValue"])];
							}),
							_: 1
						}, _parent, _scopeId));
						_push(ssrRenderComponent(_component_UFormField, {
							label: props.__.resources.users.fields.job_title,
							error: unref(form).errors.job_title,
							name: "job_title"
						}, {
							label: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) if (unref(isFieldDirty)(unref(form), initialForm.value, "job_title")) _push(ssrRenderComponent(_component_UTooltip, { text: props.__.global.forms.unsaved_change }, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) _push(`<span class="inline-flex items-center gap-1"${_scopeId}><span${_scopeId}>${ssrInterpolate(props.__.resources.users.fields.job_title)}</span><span class="inline-block size-1.5 shrink-0 rounded-full bg-warning"${_scopeId}></span></span>`);
										else return [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.job_title), 1), createVNode("span", { class: "inline-block size-1.5 shrink-0 rounded-full bg-warning" })])];
									}),
									_: 1
								}, _parent, _scopeId));
								else _push(`<span${_scopeId}>${ssrInterpolate(props.__.resources.users.fields.job_title)}</span>`);
								else return [unref(isFieldDirty)(unref(form), initialForm.value, "job_title") ? (openBlock(), createBlock(_component_UTooltip, {
									key: 0,
									text: props.__.global.forms.unsaved_change
								}, {
									default: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.job_title), 1), createVNode("span", { class: "inline-block size-1.5 shrink-0 rounded-full bg-warning" })])]),
									_: 1
								}, 8, ["text"])) : (openBlock(), createBlock("span", { key: 1 }, toDisplayString(props.__.resources.users.fields.job_title), 1))];
							}),
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(_component_UInput, {
									modelValue: unref(form).job_title,
									"onUpdate:modelValue": ($event) => unref(form).job_title = $event,
									label: "Poste",
									placeholder: " ",
									ui: { base: "peer" },
									class: "w-full"
								}, null, _parent, _scopeId));
								else return [createVNode(_component_UInput, {
									modelValue: unref(form).job_title,
									"onUpdate:modelValue": ($event) => unref(form).job_title = $event,
									label: "Poste",
									placeholder: " ",
									ui: { base: "peer" },
									class: "w-full"
								}, null, 8, ["modelValue", "onUpdate:modelValue"])];
							}),
							_: 1
						}, _parent, _scopeId));
						_push(ssrRenderComponent(_component_UFormField, {
							name: "phone",
							error: unref(form).errors["phone.extension"] || unref(form).errors["phone.number"]
						}, {
							label: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) if (unref(isFieldDirty)(unref(form), initialForm.value, "phone.extension") || unref(isFieldDirty)(unref(form), initialForm.value, "phone.number")) _push(ssrRenderComponent(_component_UTooltip, { text: props.__.global.forms.unsaved_change }, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) _push(`<span class="inline-flex items-center gap-1"${_scopeId}><span${_scopeId}>${ssrInterpolate(props.__.resources.users.fields.phone.label)}</span><span class="inline-block size-1.5 shrink-0 rounded-full bg-warning"${_scopeId}></span></span>`);
										else return [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.phone.label), 1), createVNode("span", { class: "inline-block size-1.5 shrink-0 rounded-full bg-warning" })])];
									}),
									_: 1
								}, _parent, _scopeId));
								else _push(`<span${_scopeId}>${ssrInterpolate(props.__.resources.users.fields.phone.label)}</span>`);
								else return [unref(isFieldDirty)(unref(form), initialForm.value, "phone.extension") || unref(isFieldDirty)(unref(form), initialForm.value, "phone.number") ? (openBlock(), createBlock(_component_UTooltip, {
									key: 0,
									text: props.__.global.forms.unsaved_change
								}, {
									default: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.phone.label), 1), createVNode("span", { class: "inline-block size-1.5 shrink-0 rounded-full bg-warning" })])]),
									_: 1
								}, 8, ["text"])) : (openBlock(), createBlock("span", { key: 1 }, toDisplayString(props.__.resources.users.fields.phone.label), 1))];
							}),
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(_sfc_main$4, {
									modelValue: unref(form).phone,
									"onUpdate:modelValue": ($event) => unref(form).phone = $event,
									countries: __props.countries,
									size: "lg",
									placeholder: props.__.resources.users.fields.phone.label
								}, null, _parent, _scopeId));
								else return [createVNode(_sfc_main$4, {
									modelValue: unref(form).phone,
									"onUpdate:modelValue": ($event) => unref(form).phone = $event,
									countries: __props.countries,
									size: "lg",
									placeholder: props.__.resources.users.fields.phone.label
								}, null, 8, [
									"modelValue",
									"onUpdate:modelValue",
									"countries",
									"placeholder"
								])];
							}),
							_: 1
						}, _parent, _scopeId));
						_push(ssrRenderComponent(_component_UFormField, {
							label: props.__.resources.users.fields.admin_notes,
							help: props.__.forms.users.help.admin_notes,
							error: unref(form).errors.admin_notes,
							name: "admin_notes"
						}, {
							label: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) {
									_push(`<span class="inline-flex items-center gap-1"${_scopeId}><span${_scopeId}>${ssrInterpolate(props.__.resources.users.fields.admin_notes)}</span>`);
									if (unref(isFieldDirty)(unref(form), initialForm.value, "admin_notes")) _push(`<span class="inline-block size-1.5 shrink-0 rounded-full bg-warning"${_scopeId}></span>`);
									else _push(`<!---->`);
									_push(`</span>`);
								} else return [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.admin_notes), 1), unref(isFieldDirty)(unref(form), initialForm.value, "admin_notes") ? (openBlock(), createBlock("span", {
									key: 0,
									class: "inline-block size-1.5 shrink-0 rounded-full bg-warning"
								})) : createCommentVNode("", true)])];
							}),
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(_component_UTextarea, {
									modelValue: unref(form).admin_notes,
									"onUpdate:modelValue": ($event) => unref(form).admin_notes = $event,
									placeholder: " ",
									ui: { base: "peer min-h-37.5" },
									class: "w-full"
								}, null, _parent, _scopeId));
								else return [createVNode(_component_UTextarea, {
									modelValue: unref(form).admin_notes,
									"onUpdate:modelValue": ($event) => unref(form).admin_notes = $event,
									placeholder: " ",
									ui: { base: "peer min-h-37.5" },
									class: "w-full"
								}, null, 8, ["modelValue", "onUpdate:modelValue"])];
							}),
							_: 1
						}, _parent, _scopeId));
					} else return [
						createVNode(_component_UFormField, {
							label: props.__.resources.users.fields.username,
							help: props.__.forms.users.help.username,
							error: unref(form).errors.username,
							required: "",
							name: "username"
						}, {
							label: withCtx(() => [unref(isFieldDirty)(unref(form), initialForm.value, "username") ? (openBlock(), createBlock(_component_UTooltip, {
								key: 0,
								text: props.__.global.forms.unsaved_change
							}, {
								default: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.username), 1), createVNode("span", { class: "inline-block size-1.5 shrink-0 rounded-full bg-warning" })])]),
								_: 1
							}, 8, ["text"])) : (openBlock(), createBlock("span", { key: 1 }, toDisplayString(props.__.resources.users.fields.username), 1))]),
							default: withCtx(() => [createVNode(_component_UInput, {
								modelValue: unref(form).username,
								"onUpdate:modelValue": ($event) => unref(form).username = $event,
								placeholder: " ",
								ui: { base: "peer" },
								required: "",
								class: "w-full",
								size: "lg"
							}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"help",
							"error"
						]),
						createVNode(_component_UFormField, {
							label: props.__.resources.users.fields.email,
							help: props.__.forms.users.help.email,
							error: unref(form).errors.email,
							required: "",
							name: "email"
						}, {
							label: withCtx(() => [unref(isFieldDirty)(unref(form), initialForm.value, "email") ? (openBlock(), createBlock(_component_UTooltip, {
								key: 0,
								text: props.__.global.forms.unsaved_change
							}, {
								default: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.email), 1), createVNode("span", { class: "inline-block size-1.5 shrink-0 rounded-full bg-warning" })])]),
								_: 1
							}, 8, ["text"])) : (openBlock(), createBlock("span", { key: 1 }, toDisplayString(props.__.resources.users.fields.email), 1))]),
							default: withCtx(() => [createVNode(_component_UInput, {
								modelValue: unref(form).email,
								"onUpdate:modelValue": ($event) => unref(form).email = $event,
								placeholder: " ",
								ui: { base: "peer" },
								required: "",
								class: "w-full",
								size: "lg"
							}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"help",
							"error"
						]),
						createVNode(_component_UFormField, {
							label: props.__.resources.users.fields.job_title,
							error: unref(form).errors.job_title,
							name: "job_title"
						}, {
							label: withCtx(() => [unref(isFieldDirty)(unref(form), initialForm.value, "job_title") ? (openBlock(), createBlock(_component_UTooltip, {
								key: 0,
								text: props.__.global.forms.unsaved_change
							}, {
								default: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.job_title), 1), createVNode("span", { class: "inline-block size-1.5 shrink-0 rounded-full bg-warning" })])]),
								_: 1
							}, 8, ["text"])) : (openBlock(), createBlock("span", { key: 1 }, toDisplayString(props.__.resources.users.fields.job_title), 1))]),
							default: withCtx(() => [createVNode(_component_UInput, {
								modelValue: unref(form).job_title,
								"onUpdate:modelValue": ($event) => unref(form).job_title = $event,
								label: "Poste",
								placeholder: " ",
								ui: { base: "peer" },
								class: "w-full"
							}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						createVNode(_component_UFormField, {
							name: "phone",
							error: unref(form).errors["phone.extension"] || unref(form).errors["phone.number"]
						}, {
							label: withCtx(() => [unref(isFieldDirty)(unref(form), initialForm.value, "phone.extension") || unref(isFieldDirty)(unref(form), initialForm.value, "phone.number") ? (openBlock(), createBlock(_component_UTooltip, {
								key: 0,
								text: props.__.global.forms.unsaved_change
							}, {
								default: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.phone.label), 1), createVNode("span", { class: "inline-block size-1.5 shrink-0 rounded-full bg-warning" })])]),
								_: 1
							}, 8, ["text"])) : (openBlock(), createBlock("span", { key: 1 }, toDisplayString(props.__.resources.users.fields.phone.label), 1))]),
							default: withCtx(() => [createVNode(_sfc_main$4, {
								modelValue: unref(form).phone,
								"onUpdate:modelValue": ($event) => unref(form).phone = $event,
								countries: __props.countries,
								size: "lg",
								placeholder: props.__.resources.users.fields.phone.label
							}, null, 8, [
								"modelValue",
								"onUpdate:modelValue",
								"countries",
								"placeholder"
							])]),
							_: 1
						}, 8, ["error"]),
						createVNode(_component_UFormField, {
							label: props.__.resources.users.fields.admin_notes,
							help: props.__.forms.users.help.admin_notes,
							error: unref(form).errors.admin_notes,
							name: "admin_notes"
						}, {
							label: withCtx(() => [createVNode("span", { class: "inline-flex items-center gap-1" }, [createVNode("span", null, toDisplayString(props.__.resources.users.fields.admin_notes), 1), unref(isFieldDirty)(unref(form), initialForm.value, "admin_notes") ? (openBlock(), createBlock("span", {
								key: 0,
								class: "inline-block size-1.5 shrink-0 rounded-full bg-warning"
							})) : createCommentVNode("", true)])]),
							default: withCtx(() => [createVNode(_component_UTextarea, {
								modelValue: unref(form).admin_notes,
								"onUpdate:modelValue": ($event) => unref(form).admin_notes = $event,
								placeholder: " ",
								ui: { base: "peer min-h-37.5" },
								class: "w-full"
							}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"help",
							"error"
						])
					];
				}),
				_: 1
			}, _parent));
		};
	}
});
//#endregion
//#region resources/js/Pages/Dashboard/Users/Partials/UserQuickEditForm.vue
var _sfc_setup = UserQuickEditForm_vue_vue_type_script_setup_true_lang_default.setup;
UserQuickEditForm_vue_vue_type_script_setup_true_lang_default.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Dashboard/Users/Partials/UserQuickEditForm.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
var UserQuickEditForm_default = UserQuickEditForm_vue_vue_type_script_setup_true_lang_default;
//#endregion
export { isFieldDirty as n, UserQuickEditForm_default as t };

//# sourceMappingURL=UserQuickEditForm-BAcXzIN6.js.map