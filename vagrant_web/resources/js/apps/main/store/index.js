import {createStore}             from 'vuex';
import general                   from './modules/general';

export const store = createStore({

	modules: {
		general,
	}
});
