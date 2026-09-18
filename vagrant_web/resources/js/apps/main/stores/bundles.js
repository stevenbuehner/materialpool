import {defineStore} from 'pinia';
import axios from '../axiosInstance';
import {convertErrorResponseToMessage} from '../store/modules/handleErrorsHelper';
import {
	api_v1_bundles_get_icon,
	api_v1_bundles_index,
	api_v1_bundles_uninstall_init,
	api_v1_bundles_run_status,
	api_v1_bundles_update_init,
	api_v1_bundles_update_run,
} from '../../../components/serverRoutes';

export const useBundlesStore = defineStore('bundles', {
	state: () => ({
		bundles: null,
		bundleInfos: null,
		bundleIcons: {},
	}),

	getters: {
		getAllBundles: state => () => state.bundles,
		getAllBundeInfos: state => () => state.bundleInfos,
	},

	actions: {
		setAllBundles(bundles) {
			this.bundles = bundles;
		},

		setAllBundleInfos(bundleInfos) {
			this.bundleInfos = bundleInfos;
		},

		updateBundle(bundle) {
			if (Array.isArray(this.bundles)) {
				const cachedBundle = this.bundles.find(({uuid}) => uuid === bundle.uuid);

				for (const property in bundle) {
					cachedBundle[property] = bundle[property];
				}
			} else {
				console.error('bundle.js has no Array to update the bundle to', bundle);
			}
		},

		allBundles(force) {
			if (force === true) {
				this.setAllBundles(null);
				this.setAllBundleInfos(null);
			}

			if (Array.isArray(this.getAllBundles())) {
				return Promise.resolve(this.getAllBundles());
			}

			if (this.getAllBundles() === null) {
				const promise = axios.get(api_v1_bundles_index)
					.then(response => response.data);

				this.setAllBundles(promise.then(({bundles}) => bundles));
				this.setAllBundleInfos(promise.then(({infos}) => infos));

				promise
					.then(({bundles, infos}) => {
						this.setAllBundles(bundles);
						this.setAllBundleInfos(infos);
					})
					.catch(response => convertErrorResponseToMessage(response));
			}

			return this.getAllBundles();
		},

		async allInfos() {
			if (Array.isArray(this.getAllBundeInfos())) {
				return this.getAllBundeInfos();
			}

			this.allBundles();
			return this.getAllBundeInfos();
		},

		async getBundle(uuid) {
			const allBundles = await this.allBundles();
			const bundle = allBundles.find(item => item.uuid === uuid);

			if (!bundle) {
				throw new Error('No bundle with the uuid ' + uuid + ' found');
			}

			return bundle;
		},

		async getBundleInfo(uuid) {
			const allInfos = await this.allInfos();
			const info = allInfos.find(item => item.uuid === uuid);

			if (!info) {
				throw new Error('No info with the uuid ' + uuid + ' found');
			}

			return info;
		},

		initUpdateJobs(id) {
			return axios.post(api_v1_bundles_update_init(id), {}, {timeout: 0})
				.then(({data}) => data);
		},

		initUninstallJobs(id) {
			return axios.post(api_v1_bundles_uninstall_init(id), {}, {timeout: 0})
				.then(({data}) => data);
		},

		runJobs(bundleId) {
			const response = axios.post(api_v1_bundles_update_run(bundleId), {}, {timeout: 0})
				.then(({data}) => data);

			response.then(({bundle}) => {
				if (bundle) {
					this.updateBundle(bundle);
				}
			});

			return response;
		},

		getRunStatus(bundleId, runId) {
			return axios.get(api_v1_bundles_run_status(bundleId, runId))
				.then(({data}) => data);
		},

		getBundleIcon(bundleId) {
			this.bundleIcons[bundleId] = this.bundleIcons[bundleId] || false;

			if (this.bundleIcons[bundleId]) {
				return this.bundleIcons[bundleId];
			}

			this.bundleIcons[bundleId] = axios.get(api_v1_bundles_get_icon(bundleId))
				.then(({data}) => data)
				.catch(response => convertErrorResponseToMessage(response));

			return this.bundleIcons[bundleId];
		},

		getBundleById(bundleId) {
			return this.allBundles()
				.then(bundles => bundles.find(({id}) => id === bundleId));
		},

		getBundleNameById(bundleId) {
			return this.getBundleById(bundleId)
				.then(data => {
					if (data === undefined) {
						throw 'not found';
					}

					return data;
				})
				.then(({name}) => name);
		},
	},
});
