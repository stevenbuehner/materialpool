/**
 *
 * array [
 *    zeile 1 => [Keyword, Bibleverse, Freitext, ...]
 *    zeile 2 => []
 *    zeile 3 => []
 * ]
 * @param searchLines
 */
export function searchArrayObjectsToSearchArrayItems(searchLines) {

    let result  = [];
    let counter = 1;

    if (searchLines && searchLines instanceof Array) {

        for (let line in searchLines) {

            result.push({
                id: counter++,
                values: objectArrayToSearchItems(searchLines[line])
            });

        }
    }

    return result;


}

/**
 *
 * i.e. [Keyword, Bibleverse, Freitext, ...]
 *
 * @param {Array} searchObjects
 * @return {Array}
 */
function objectArrayToSearchItems(searchObjects) {

    let result = [];

    if (searchObjects && searchObjects instanceof Array) {

        searchObjects.forEach((obj) => {
            const searchItem = objectToSearchItem(obj);

            if (searchItem !== false) {
                result.push(searchItem);
            } else {
                console.error('Could not convert object to searchItem: ', obj);
            }
        });
    }

    return result;

}

function objectToSearchItem(obj) {

    if (obj instanceof String) {
        // Is freetext
        return freeTextToSearchItem(obj);
    }

    if (obj.id && obj.lc_title) {
        // Is Keyword
        return keywordToSearchItem(obj);

    } else if (obj.id && obj.from && obj.to) {
        // Is Bibleverse
        return bibleverseToSearchItem(obj);
    }

    return false;

}


function freeTextToSearchItem(text) {
    return {
        icon: "/img/icons/ayce.svg",
        text: text,
        item: {
            text: text,
            type: '*'
        }
    }
}

function keywordToSearchItem(keyword) {
    return {
        icon: keyword.icon || "/img/icons/tag.svg",
        text: keyword.title,
        item: {
            id: keyword.id,
            type: 'k'
        }
    }
}


function bibleverseToSearchItem(bibleverse) {
    return {
        icon: bibleverse.icon || "/img/icons/bible.svg",
        text: bibleverse.label,
        item: {
            from: bibleverse.from,
            to: bibleverse.to,
            type: 'b'
        }
    }
}

const QUERY_SEPARATOR = ',';

export function searchArrayObjectsToSearchQuery(searchObjects) {

    const search = searchArrayObjectsToSearchArrayItems(searchObjects);
    const query  = search.map((lineObj) => {

        const id     = lineObj.id || 1;
        const values = lineObj.values || [];

        return values.map(({item}) => {

            switch (item.type) {
                case 'k':
                    return id + item.type + item.id;
                case 'b':
                    return id + item.type + item.from + '-' + item.to;
                case '*':
                    return id + item.type + item.text;
                default:
                    console.error('Unknown searchItemType', lineObj);
            }

            return ''

        }).join(QUERY_SEPARATOR);

    });

    return query;

}

export function keywordIdsToSimpleQuery(keywordIds) {


    return searchArrayObjectsToSearchQuery([
        keywordIds.map(id => {
            return {
                type: 'k',
                id,
                lc_title: true
            }
        })
    ]).pop();

}

import {store} from './../../apps/main/store/index.js';
import BibleVerse from './../../../../../vendor/stevenbuehner/bible-verse-bundle/js/in/BibleVerse.js';
import {BibleVerseService} from './../../../../../vendor/stevenbuehner/bible-verse-bundle/js/out/BibleVerseService_de.js';

export function searchQueryToSearchArrayObjects(query) {

    query              = query || '';
    const queryItems   = query.split(QUERY_SEPARATOR);
    let searchPromises = [];
    const regExp       = /([0-9]+)([kb\*])(.*)/i;

    queryItems.forEach((objStr) => {
        const found = objStr.match(regExp);

        if (found) {
            const lineId = parseInt(found[1]);
            const type   = found[2];
            const search = found[3];

            switch (type) {
                case 'k':
                    searchPromises.push(
                        new Promise((resolve, reject) => {
                            store
                                .dispatch('keywords/get', search)
                                .then((keyword) => {
                                    resolve({
                                        line: lineId,
                                        value: keywordToSearchItem(keyword)
                                    });
                                })

                        })
                    );
                    break;

                case 'b':
                    const fromTo = search.match(/(\d+)-(\d+)/);

                    if (fromTo) {

                        const bibleverse = new BibleVerse(fromTo[1], fromTo[2]);

                        searchPromises.push({
                            line: lineId,
                            value: bibleverseToSearchItem({
                                label: BibleVerseService.bibleVerseToString(bibleverse),
                                from: bibleverse.getFrom(),
                                to: bibleverse.getTo()
                            })
                        });
                    }
                    break;

                case '*':
                    searchPromises.push({
                        line: lineId,
                        value: freeTextToSearchItem(search)
                    });
                    break;

                default:
                    console.error('Unknown searchItemType', searchItem);
            }
        }

    });


    return Promise.all(searchPromises)
        .then((results) => {

            let searchParams = {};

            results.forEach((qObj) => {
                const line  = qObj.line;
                const value = qObj.value;

                if (searchParams[line] === undefined) {
                    searchParams[line] = {
                        id: line,
                        values: []
                    }
                }

                searchParams[line].values.push(value);
            });

            return searchParams;

        });

}

