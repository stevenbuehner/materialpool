import {createStore}             from 'vuex';
import resources                 from './modules/resources';
import materials                 from './modules/materials';
import keywords                  from './modules/keywords';
import bibleverses               from './modules/bibleverses';
import search                    from './modules/search';
import materialapp               from './modules/materialapp';
import bundles                   from './modules/bundles';
import general                   from './modules/general';
import keywordsSuggestions       from './modules/keywordsSuggestions';

export const store = createStore({

	modules: {
		resources,
		materials,
		materialapp,
		keywords,
		keywordsSuggestions,
		bibleverses,
		search,
		bundles,
		general,
	}
});
