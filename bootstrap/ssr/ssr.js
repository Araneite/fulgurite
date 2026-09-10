import { createInertiaApp } from "@inertiajs/vue3";
import createServer from "@inertiajs/vue3/server";
import { renderToString } from "vue/server-renderer";
//#region resources/js/ssr.js
var render = await createInertiaApp({ resolve: async (name, page) => {
	const module = await (/* @__PURE__ */ Object.assign({
		"./Pages/Auth/AcceptInvitation.vue": () => import("./assets/AcceptInvitation-BCBndmcE.js"),
		"./Pages/Auth/Login.vue": () => import("./assets/Login-DWLhAELw.js"),
		"./Pages/Auth/TwoFactorChallenge.vue": () => import("./assets/TwoFactorChallenge-CQkbz1Y5.js"),
		"./Pages/Dashboard/Profile/Show.vue": () => import("./assets/Show-DDC3apFP.js"),
		"./Pages/Dashboard/Users/Index.vue": () => import("./assets/Index-DCIB333C.js"),
		"./Pages/Dashboard/Users/Partials/UserDetailsDrawer.vue": () => import("./assets/UserDetailsDrawer-BZ60fsxl.js"),
		"./Pages/Dashboard/Users/Partials/UserDrawerShell.vue": () => import("./assets/UserDrawerShell-C9F5I0Hd.js"),
		"./Pages/Dashboard/Users/Partials/UserQuickCreateForm.vue": () => import("./assets/UserQuickCreateForm-B2eGaTbd.js"),
		"./Pages/Dashboard/Users/Partials/UserQuickEditForm.vue": () => import("./assets/UserQuickEditForm-DI03gcuS.js"),
		"./Pages/Dashboard/Users/Test.vue": () => import("./assets/Test-D6fPmiQ3.js"),
		"./Pages/Errors/InvitationUnavailable.vue": () => import("./assets/InvitationUnavailable-Y7Z_1MIK.js")
	}))[`./Pages/${name}.vue`]?.();
	if (!module) throw new Error(`Page not found: ${name}`);
	return module.default ?? module;
} });
var renderPage = (page) => render(page, renderToString);
createServer(renderPage, { "host": "127.0.0.1" });
//#endregion
export { renderPage as default };

//# sourceMappingURL=ssr.js.map