import Vue             from 'vue';
import VueX            from 'vuex';
import resources       from './modules/resources';
import materials       from './modules/materials';
import materialusages  from './modules/materialusages';
import keywords        from './modules/keywords';
import bibleverses     from './modules/bibleverses';
import search          from './modules/search';
import tagsearch       from './modules/tagsearch';
import materialapp     from './modules/materialapp';
import recentmaterials from './modules/recentmaterials';
import bundles         from './modules/bundles';
import biblecontents   from './modules/biblecontents';
import general         from './modules/general';
import bibles          from './modules/bibles';

Vue.use(VueX);


export const store = new VueX.Store({

	modules: {
		resources,
		materials,
		materialusages,
		materialapp,
		keywords,
		bibleverses,
		search,
		tagsearch,
		recentmaterials,
		bundles,
		biblecontents,
		general,
		bibles
	}
});