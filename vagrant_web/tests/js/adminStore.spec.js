import {beforeEach, describe, expect, it, vi} from 'vitest';
import {createPinia, setActivePinia} from 'pinia';
import axios from '../../resources/js/apps/main/axiosInstance.js';
import {useAdminStore} from '../../resources/js/apps/main/stores/admin.js';

vi.mock('../../resources/js/apps/main/axiosInstance.js', () => ({
	default: {get: vi.fn(), post: vi.fn(), patch: vi.fn(), delete: vi.fn()},
}));

describe('admin Pinia store', () => {
	beforeEach(() => {
		setActivePinia(createPinia());
		vi.clearAllMocks();
	});

	it('loads paginated users and reference data', async () => {
		axios.get
			.mockResolvedValueOnce({data: {data: [{id: 2}], current_page: 1, last_page: 3, total: 42}})
			.mockResolvedValueOnce({data: {data: [{id: 1, name: 'Standardnutzer'}]}})
			.mockResolvedValueOnce({data: {data: [{code: 'materials.create', area: 'materials'}]}});
		const store = useAdminStore();

		await store.loadUsers({page: 1, status: 'active'});
		await store.loadReferenceData();

		expect(axios.get).toHaveBeenCalledWith('/api/v2/admin/users', {params: {page: 1, status: 'active'}});
		expect(store.users).toEqual([{id: 2}]);
		expect(store.pagination.total).toBe(42);
		expect(store.groups[0].name).toBe('Standardnutzer');
		expect(store.permissions[0].code).toBe('materials.create');
	});

	it('keeps server authorization errors available to the page', async () => {
		axios.patch.mockRejectedValue({response: {data: {message: 'Nicht erlaubt'}}});
		const store = useAdminStore();

		await expect(store.updateUser({id: 4, status: 'suspended'})).rejects.toBeTruthy();
		expect(store.error).toBe('Nicht erlaubt');
		expect(store.loading).toBe(false);
	});
});
