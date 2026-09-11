import {createApp, h} from 'vue';
import '@babel/polyfill';
import {createRouter, createWebHistory} from 'vue-router';
import {store}   from './store'; // Before routes to use in BeforeRouting-Functions
import {routes}  from './routes';
import mainApp   from './App.vue';
// Styling
import '../../../sass/main.scss';

// Localisation
import {vueLangConfig} from './localisation';
import {sessionKeepAlive}            from "../../helper/sessionKeepAlive";
import {keepalive_seconds_intervall} from "../config";
import {installLegacyPlugins}        from './installLegacyPlugins';

const router = createRouter({
	history: createWebHistory('/vue'),
	scrollBehavior(to, from, savedPosition) {
		// console.info(to, from, savedPosition);
		return {left: 0, top: 0}
	},
	routes
});

const app = createApp({
	name: 'Materialpool',
	render: () => h(mainApp),
});
installLegacyPlugins(app, vueLangConfig);
app.use(store);
app.use(router);
app.mount('#app');


if (window.materialpool) {

	/* Auto load stuff into vuex store */
	if (window.materialpool.store) {
		if (window.materialpool.store.materials && window.materialpool.store.materials.length > 0) {
			const mat = window.materialpool.store.materials;

			for (let i in mat) {
				store.commit('materials/setMaterial', mat[i]);
			}
		}
	}

	/* Redirect to vue-route */
	if (window.materialpool.route) {
		router.push(window.materialpool.route);
	}

}

let sessionKeepAliveErrorCounter = 0;

// Session keepalive
setInterval(() => {
	sessionKeepAlive().catch((errorMessage) => {
		sessionKeepAliveErrorCounter++;
		console.error(errorMessage, sessionKeepAliveErrorCounter);
	});
}, keepalive_seconds_intervall * 1000);
