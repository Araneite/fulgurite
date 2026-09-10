import { T as useState } from "./usePortal-DZb6nPjI.js";
import { inject, nextTick, ref } from "vue";
//#region node_modules/@nuxt/ui/dist/runtime/composables/useToast.js
var toastMaxInjectionKey = Symbol("nuxt-ui.toast-max");
function useToast() {
	const toasts = useState("toasts", () => []);
	const max = inject(toastMaxInjectionKey, void 0);
	const running = ref(false);
	const queue = [];
	const generateId = () => `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
	async function processQueue() {
		if (running.value || queue.length === 0) return;
		running.value = true;
		while (queue.length > 0) {
			const toast = queue.shift();
			await nextTick();
			toasts.value = [...toasts.value, toast].slice(-(max?.value ?? 5));
		}
		running.value = false;
	}
	function add(toast) {
		const body = {
			id: generateId(),
			open: true,
			...toast
		};
		const existingIndex = toasts.value.findIndex((t) => t.id === body.id);
		if (existingIndex !== -1) {
			toasts.value[existingIndex] = {
				...toasts.value[existingIndex],
				...body,
				_duplicate: (toasts.value[existingIndex]._duplicate || 0) + 1
			};
			return body;
		}
		queue.push(body);
		processQueue();
		return body;
	}
	function update(id, toast) {
		const index = toasts.value.findIndex((t) => t.id === id);
		if (index !== -1) {
			toasts.value[index] = {
				...toasts.value[index],
				...toast,
				duration: toast.duration,
				open: true,
				_updated: true
			};
			nextTick(() => {
				const i = toasts.value.findIndex((t) => t.id === id);
				if (i !== -1 && toasts.value[i]._updated) toasts.value[i] = {
					...toasts.value[i],
					_updated: void 0
				};
			});
		}
	}
	function remove(id) {
		const index = toasts.value.findIndex((t) => t.id === id);
		if (index !== -1 && toasts.value[index]._updated) return;
		if (index !== -1) toasts.value[index] = {
			...toasts.value[index],
			open: false
		};
		setTimeout(() => {
			toasts.value = toasts.value.filter((t) => t.id !== id);
		}, 200);
	}
	function clear() {
		toasts.value = [];
	}
	return {
		toasts,
		add,
		update,
		remove,
		clear
	};
}
//#endregion
export { useToast as n, toastMaxInjectionKey as t };

//# sourceMappingURL=useToast-itvNvMq-.js.map