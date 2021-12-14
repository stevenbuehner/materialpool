import {marked}                 from 'marked';

import {getBibleverseTokenizer} from "./bibleverseRenderer";


marked.use({
	gfm: true,
	breaks: true,
	// sanitize: true,
	smartLists: true,
	smartypants: true,
	tables: true,
});

const loadBibleverses = true;

marked.use({extensions: [getBibleverseTokenizer(loadBibleverses)]});

export default marked;