import {BibleVerse} from '../../../helper/BibleverseHelper';

export function fromRangeArrayToString(verseRanges) {
    return verseRanges.map(bibleVerse => {
        if (bibleVerse instanceof BibleVerse) {
            return `${bibleVerse.getFrom()}-${bibleVerse.getTo()}`;
        }

        const bibleId = bibleVerse.bibleId ? `-${bibleVerse.bibleId}` : '';

        return `${bibleVerse.from}-${bibleVerse.to}${bibleId}`;
    }).join(',');
}
