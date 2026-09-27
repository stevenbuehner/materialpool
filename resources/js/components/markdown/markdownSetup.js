import {marked} from 'marked';

import {getBibleverseTokenizer}   from "./bibleverseRenderer";
import {getArrowMarkdownRenderer} from "./arrowRenderer";


marked.use({
	gfm: true,
	breaks: true,
	smartLists: true,
	smartypants: true,
	tables: true,
	sanitize: false, // Deprecated
});

const loadBibleverses = true;


marked.use({extensions: [getBibleverseTokenizer(loadBibleverses), getArrowMarkdownRenderer()]});

export default marked;