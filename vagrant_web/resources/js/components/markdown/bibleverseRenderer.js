import {BibleVerseService} from '../../../../vendor/stevenbuehner/bible-verse-bundle/js/out/BibleVerseService_de.js';

const startRegexp = /([1-5]\.?\s*)?[a-zäöü\.]{2,15}\s([1-9][0-9]{0,2}[,;\. 0-9-]*)/i;

// Der Lexer funktioniert nur, wenn am Anfang eines Strings gesucht wird (mit ^)
const regexp = new RegExp('^(' + BibleVerseService?.biblePattern?.source + ')', BibleVerseService?.biblePattern?.flags);

export function getBibleverseTokenizer(doAutoload) {
	doAutoload = doAutoload === true;

	return {
		name: 'bibleverse',
		level: 'inline',                                 // Is this a block-level or inline-level tokenizer?
		start(src) {

			// Hint to Marked.js to stop and check for a match
			const match = startRegexp.exec(src);
			// console.log('Start-Text: ', src, 'Index: ', match?.index);

			if (match) {
				return match.index;
			} else {
				return -1;
			}

		},
		tokenizer(src, tokens) {
			const match = regexp.exec(src);
			if (match) {
				return {                                         // Token to generate
					type: 'bibleverse',                          // Should match "name" above
					raw: match[0].trim(),                        // Text to consume from the source
					bibleverse: match[0].trim(),                 // Additional custom properties, including any further-nested inline tokens
					doAutoload,
				};
			}
		},
		renderer(token) {
			return `<bible-popover :text="'${token.bibleverse}'" :load-contents="${token.doAutoload}"/>`
		},
		childTokens: [],                 // Any child tokens to be visited by walkTokens
	};
}

