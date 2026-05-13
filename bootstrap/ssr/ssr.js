import { createInertiaApp } from "@inertiajs/vue3";
import createServer from "@inertiajs/vue3/server";
import { renderToString } from "vue/server-renderer";
//#region resources/js/ssr.js
var render = await createInertiaApp({ resolve: async (name, page) => {
	const module = await (/* @__PURE__ */ Object.assign({
		"./Pages/Auth/Login.vue": () => import("./assets/Login-DeBta9lY.js"),
		"./Pages/Dashboard/Users/Index.vue": () => import("./assets/Index-BurEsxL4.js")
	}))[`./Pages/${name}.vue`]?.();
	if (!module) throw new Error(`Page not found: ${name}`);
	return module.default ?? module;
} });
var renderPage = (page) => render(page, renderToString);
createServer(renderPage, { "host": "127.0.0.1" });
//#endregion
export { renderPage as default };

//# sourceMappingURL=ssr.js.map