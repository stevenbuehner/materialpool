import {beforeEach, describe, expect, it, vi} from 'vitest';
import {createPinia, setActivePinia} from 'pinia';
import axios from '../../resources/js/apps/main/axiosInstance.js';
import {useMaterialsStore} from '../../resources/js/apps/main/stores/materials.js';
import {useResourcesStore} from '../../resources/js/apps/main/stores/resources.js';

vi.mock('../../resources/js/apps/main/axiosInstance.js', () => ({
    default: {delete: vi.fn(), get: vi.fn(), post: vi.fn(), put: vi.fn()},
}));

describe('materials Pinia store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('distinguishes preview and detailed cache entries and clears all flags', () => {
        const store = useMaterialsStore();
        store.setMaterial({id: 7, title: 'Preview'});
        expect(store.hasMaterialPreviewCached(7)).toBe(true);
        expect(store.hasMaterialDetailsCached(7)).toBe(false);

        store.setMaterialDetailed({id: 7, title: 'Detail'});
        expect(store.getMaterial(7)).toEqual({id: 7, title: 'Detail'});
        expect(store.hasMaterialDetails(7)).toBe(true);

        store.clearMaterial(7);
        expect(store.getMaterial(7)).toBeNull();
        expect(store.getMaterialLoadingPromise(7)).toBe(false);
    });

    it('coalesces detailed loads and permits retry after a converted failure', async () => {
        axios.get.mockResolvedValueOnce({data: {id: 7, title: 'Detail'}});
        const store = useMaterialsStore();
        const first = store.getMaterialDetailed(7);
        const second = store.getMaterialDetailed(7);

        await expect(Promise.all([first, second])).resolves.toEqual([
            {id: 7, title: 'Detail'},
            {id: 7, title: 'Detail'},
        ]);
        expect(axios.get).toHaveBeenCalledOnce();
        expect(axios.get).toHaveBeenCalledWith('/api/v1/materials/7', {});

        axios.get.mockRejectedValueOnce({response: {data: {error: 'Nicht gefunden'}}});
        await expect(store.getMaterialDetailed(8)).rejects.toBe('Nicht gefunden');
        expect(store.getMaterialLoadingPromise(8)).toBe(false);
    });

    it('creates with the legacy optional payload rules and caches full details', async () => {
        axios.post.mockResolvedValue({data: {id: 7, title: 'Neu', resources: []}});
        const store = useMaterialsStore();

        await expect(store.create({
            title: 'Neu',
            from_bot: false,
            description: '',
            rating: 0,
            author: {id: 3},
            keywords: [],
            bibleverses: [],
        })).resolves.toEqual({id: 7, title: 'Neu', resources: []});

        expect(axios.post).toHaveBeenCalledWith('/api/v1/materials', {
            title: 'Neu',
            from_bot: false,
            author: {id: 3},
            keywords: [],
            bibleverses: [],
        });
        expect(store.hasMaterialDetails(7)).toBe(true);
    });

    it('returns the raw update response while refreshing the detailed cache', async () => {
        const response = {data: {id: 7, title: 'Aktualisiert'}};
        axios.post.mockResolvedValue(response);
        const update = {title: 'Aktualisiert'};
        const store = useMaterialsStore();

        await expect(store.updateMaterial({id: 7, data: update})).resolves.toBe(response);
        expect(update).toEqual({title: 'Aktualisiert', _method: 'PUT'});
        expect(store.getMaterial(7)).toEqual(response.data);
        expect(store.hasMaterialDetails(7)).toBe(true);
    });

    it('updates keyword assignments in an existing material cache', () => {
        const store = useMaterialsStore();
        store.setMaterial({id: 7, keywords: [{id: 1, title: 'Alt'}]});

        store.addKeywordToMaterial({materialId: 7, keyword: {id: 2}, relevance: 200});
        store.updateMaterialKeywords({materialId: 7, keyword: {id: 1, title: 'Neu'}, pivot: {relevance: 100}});
        store.removeKeywordFromMaterial({materialId: 7, keywordId: 2});

        expect(store.getMaterial(7).keywords).toEqual([
            {id: 1, title: 'Neu', pivot: {relevance: 100}},
        ]);
    });

    it('attaches and detaches resources while refreshing both caches', async () => {
        axios.post.mockResolvedValueOnce({data: {
            material: {id: 7, resources: [{id: 9}]},
            resource: {id: 9, materials: [{id: 7}]},
        }});
        axios.delete.mockResolvedValueOnce({data: {
            material: {id: 7, resources: []},
            resource: {id: 9, materials: []},
        }});
        const store = useMaterialsStore();

        await store.attachResource({materialId: 7, resourceId: 9, limitation: {type: 'pages', value: '1-2'}});
        expect(axios.post).toHaveBeenCalledWith('/api/v2/material/7/resource/9/attach', {
            limitation: {type: 'pages', value: '1-2'},
        });
        expect(useResourcesStore().updateResource(9).materials).toEqual([{id: 7}]);

        await store.detachResource({materialId: 7, resourceId: 9});
        expect(axios.delete).toHaveBeenCalledWith('/api/v2/material/7/resource/9/detach');
        expect(store.getMaterial(7).resources).toEqual([]);
        expect(useResourcesStore().updateResource(9).materials).toEqual([]);
    });

    it('deletes, copies and creates download links with stable result contracts', async () => {
        const store = useMaterialsStore();
        store.setMaterial({id: 7});
        useResourcesStore().setResource({id: 9});
        axios.delete.mockResolvedValueOnce({data: {success: true}});
        await expect(store.deleteMaterial(7)).resolves.toBe(true);
        expect(store.getMaterial(7)).toBeNull();

        axios.get.mockResolvedValueOnce({data: {id: 8, resources: [{id: 9}]}});
        await expect(store.copyMaterial(7)).resolves.toEqual({id: 8, resources: [{id: 9}]});
        expect(store.hasMaterialDetails(8)).toBe(true);
        expect(useResourcesStore().updateResource(9)).toBeNull();

        axios.get.mockResolvedValueOnce({data: {success: true, link: '/download/a', until: 'tomorrow'}});
        await expect(store.createDownloadLink(8)).resolves.toEqual({link: '/download/a', until: 'tomorrow'});
        axios.get.mockResolvedValueOnce({data: {success: false}});
        await expect(store.createDownloadLink(8)).rejects.toBe('invalid download link');
    });
});
