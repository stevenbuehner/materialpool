import {createStore}             from 'vuex';
import resources                 from './modules/resources';
import materials                 from './modules/materials';
import materialusages            from './modules/materialusages';
import keywords                  from './modules/keywords';
import bibleverses               from './modules/bibleverses';
import bibleverseCrossReferences from './modules/bibleverseCrossReferences';
import search                    from './modules/search';
import tagsearch                 from './modules/tagsearch';
import materialapp               from './modules/materialapp';
import bundles                   from './modules/bundles';
import biblecontents             from './modules/biblecontents';
import general                   from './modules/general';
import bibles                    from './modules/bibles';
import users                     from './modules/users';
import keywordsSuggestions       from './modules/keywordsSuggestions';

export const store = createStore({

	modules: {
		resources,
		materials,
		materialusages,
		materialapp,
		keywords,
		keywordsSuggestions,
		bibleverses,
		bibleverseCrossReferences,
		search,
		tagsearch,
		bundles,
		biblecontents,
		general,
		bibles,
		users
	}
});
