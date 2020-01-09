import Vue       from 'vue';
import '@babel/polyfill';
import VueRouter from 'vue-router';
import {store}   from './store'; // Before routes to use in BeforeRouting-Functions
import {routes}  from './routes';
import mainApp   from './App.vue';
// Styling
import '../../../sass/main.scss';
// Localisation
import {i18n}    from "./localisation";

import ShortKey                      from 'vue-shortkey'
import AsyncComputed                 from 'vue-async-computed';
import {sessionKeepAlive}            from "../../helper/sessionKeepAlive";
import {keepalive_seconds_intervall} from "../config";


Vue.use(ShortKey);
Vue.use(VueRouter);
Vue.use(AsyncComputed);

const router = new VueRouter({
	mode: 'history',
	base: '/vue',
	scrollBehavior(to, from, savedPosition) {
		console.info(to, from, savedPosition);
		return {x: 0, y: 0}
	},
	routes
});

let vueInstance = new Vue({
	el: '#app',
	i18n,
	router: router,
	render: h => h(mainApp),
	components: {},
	store
});


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

// Session keepalive
setInterval(() => {
	sessionKeepAlive();
}, keepalive_seconds_intervall * 1000);