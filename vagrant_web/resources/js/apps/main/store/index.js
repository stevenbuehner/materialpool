import {createStore}             from 'vuex';
import resources                 from './modules/resources';
import materials                 from './modules/materials';
import search                    from './modules/search';
import materialapp               from './modules/materialapp';
import general                   from './modules/general';
import keywordsSuggestions       from './modules/keywordsSuggestions';

export const store = createStore({

	modules: {
		resources,
		materials,
		materialapp,
		keywordsSuggestions,
		search,
		general,
	}
});
