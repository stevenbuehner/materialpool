import {beforeEach, describe, expect, it, vi} from 'vitest';
import {createPinia, setActivePinia} from 'pinia';
import axios from '../../resources/js/apps/main/axiosInstance.js';
import {moment} from '../../resources/js/apps/main/localisation.js';
import {useMaterialUsagesStore} from '../../resources/js/apps/main/stores/materialUsages.js';

vi.mock('../../resources/js/apps/main/axiosInstance.js', () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
    },
}));

vi.mock('../../resources/js/apps/main/localisation.js', () => ({
    moment: vi.fn(() => ({format: () => 'formatted-datetime'})),
}));

describe('material usages Pinia store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('loads, caches and reuses a non-empty usage list', async () => {
        const usages = [{id: 3, material_id: 12, reason: 'Unterricht'}];
        axios.get.mockResolvedValue({data: usages});
        const store = useMaterialUsagesStore();

        await expect(store.getMaterialUsages(12)).resolves.toEqual(usages);
        await expect(store.getMaterialUsages(12)).resolves.toEqual(usages);

        expect(axios.get).toHaveBeenCalledOnce();
        expect(axios.get).toHaveBeenCalledWith('/api/v2/material/12/usage', {});
        expect(store.hasMaterialUsagesCached(12)).toBe(true);
    });

    it('returns an empty response without treating it as cached', async () => {
        axios.get.mockResolvedValue({data: []});
        const store = useMaterialUsagesStore();

        await expect(store.getMaterialUsages(12)).resolves.toEqual([]);

        expect(store.hasMaterialUsagesCached(12)).toBe(false);
    });

    it('creates with defaults and updates the cached record', async () => {
        const usage = {id: 5, material_id: 12, reason: '', place: ''};
        axios.post.mockResolvedValue({data: usage});
        const store = useMaterialUsagesStore();

        await expect(store.addMaterialUsage({material_id: 12, used_by_id: 8})).resolves.toBe(usage);

        expect(axios.post).toHaveBeenCalledWith('/api/v2/material/12/usage', {
            datetime: 'formatted-datetime',
            place: '',
            reason: '',
            used_by_id: 8,
        });
        expect(moment).toHaveBeenCalledWith(expect.any(Date));
        expect(store.getCachedMaterialUsages(12)).toEqual([usage]);
    });

    it('uses the update URL and replaces an existing cached record', async () => {
        const store = useMaterialUsagesStore();
        store.setMaterialUsage({id: 5, material_id: 12, reason: 'Alt'});
        const updated = {id: 5, material_id: 12, reason: 'Neu'};
        axios.post.mockResolvedValue({data: updated});

        await store.updateMaterialUsage({
            material_id: 12,
            id: 5,
            datetime: '2026-09-11T10:00:00Z',
            place: 'Berlin',
            reason: 'Neu',
            used_by_id: null,
        });

        expect(axios.post).toHaveBeenCalledWith('/api/v2/material/12/usage/5', {
            datetime: 'formatted-datetime',
            place: 'Berlin',
            reason: 'Neu',
            used_by_id: null,
        });
        expect(store.getCachedMaterialUsages(12)).toEqual([updated]);
    });

    it('deletes successfully and removes only the matching cached record', async () => {
        const store = useMaterialUsagesStore();
        store.setMaterialUsages([{id: 5, material_id: 12}, {id: 6, material_id: 12}]);
        axios.post.mockResolvedValue({data: {success: true}});

        await expect(store.deleteMaterialUsage({material_id: 12, id: 5})).resolves.toBe(true);

        expect(axios.post).toHaveBeenCalledWith('/api/v2/material/12/usage/5', {_method: 'DELETE'});
        expect(store.getCachedMaterialUsages(12)).toEqual([{id: 6, material_id: 12}]);
    });

    it('keeps the server message when a delete response is unsuccessful', async () => {
        axios.post.mockResolvedValue({data: {success: false, message: 'Löschen fehlgeschlagen'}});

        await expect(useMaterialUsagesStore().deleteMaterialUsage({material_id: 12, id: 5}))
            .rejects.toBe('Löschen fehlgeschlagen');
    });

    it('converts rejected requests to the existing message contract', async () => {
        axios.get.mockRejectedValue({response: {data: {error: 'Nicht erreichbar'}}});

        await expect(useMaterialUsagesStore().getMaterialUsages(12)).rejects.toBe('Nicht erreichbar');
    });
});
