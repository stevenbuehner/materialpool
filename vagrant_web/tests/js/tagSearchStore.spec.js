import {beforeEach, describe, expect, it, vi} from 'vitest';
import {createPinia, setActivePinia} from 'pinia';
import axios from '../../resources/js/apps/main/axiosInstance.js';
import {useTagSearchStore} from '../../resources/js/apps/main/stores/tagSearch.js';

vi.mock('../../resources/js/apps/main/axiosInstance.js', () => ({
    default: {
        get: vi.fn(),
    },
}));

describe('tag search Pinia store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('passes the search text as q and returns the response body', async () => {
        const responseBody = {data: [{text: 'Gnade'}]};
        axios.get.mockResolvedValue({data: responseBody});

        await expect(useTagSearchStore().searchTags('Gnade')).resolves.toBe(responseBody);
        expect(axios.get).toHaveBeenCalledWith('/pool/search/guess', {
            params: {q: 'Gnade'},
        });
    });

    it('logs and returns the original rejected value', async () => {
        const failure = new Error('network failed');
        const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});
        axios.get.mockRejectedValue(failure);

        await expect(useTagSearchStore().searchTags('Gnade')).resolves.toBe(failure);
        expect(consoleError).toHaveBeenCalledWith(failure);

        consoleError.mockRestore();
    });
});
