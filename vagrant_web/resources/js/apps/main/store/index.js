import {createStore}             from 'vuex';
import search                    from './modules/search';
import materialapp               from './modules/materialapp';
import general                   from './modules/general';

export const store = createStore({

	modules: {
		materialapp,
		search,
		general,
	}
});
