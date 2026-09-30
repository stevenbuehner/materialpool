import {defineStore} from 'pinia';
import axios from '../axiosInstance';
import {api_v2_admin_queue_overview} from '../../../components/serverRoutes';
import {convertErrorResponseToMessage} from '../store/modules/handleErrorsHelper';

export const useQueueOverviewStore = defineStore('queueOverview', {
	state: () => ({
		tab: 'jobs',
		filters: {queue: '', type: '', status: ''},
		summary: {waiting: 0, delayed: 0, reserved: 0, failed: 0},
		items: [],
		queues: [],
		pagination: {current_page: 1, last_page: 1, total: 0},
		refreshedAt: null,
		loading: false,
		pendingPage: null,
		error: null,
	}),
	actions: {
		async load(page = 1) {
			if (this.loading) {
				this.pendingPage = page;
				return;
			}
			this.loading = true;
			const requestedTab = this.tab;
			try {
				const {data} = await axios.get(api_v2_admin_queue_overview, {
					params: {
						tab: this.tab,
						page,
						...(this.filters.queue ? {queue: this.filters.queue} : {}),
						...(this.filters.type && this.tab !== 'batches' ? {type: this.filters.type} : {}),
						...(this.filters.status && this.tab === 'jobs' ? {status: this.filters.status} : {}),
					},
				});
				if (requestedTab !== this.tab) return;
				this.summary = data.summary;
				this.items = data.data.data;
				this.queues = data.queues;
				this.pagination = {current_page: data.data.current_page, last_page: data.data.last_page, total: data.data.total};
				this.refreshedAt = data.refreshed_at;
				this.error = null;
			} catch (error) {
				if (requestedTab === this.tab) this.error = convertErrorResponseToMessage(error);
			} finally {
				this.loading = false;
				if (this.pendingPage !== null) {
					const nextPage = this.pendingPage;
					this.pendingPage = null;
					await this.load(nextPage);
				}
			}
		},
	},
});
