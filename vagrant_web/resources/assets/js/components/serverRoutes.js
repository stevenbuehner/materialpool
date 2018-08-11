export const searchGuessRoute  = '/pool/search/guess';
export const searchGuessRoute2 = '/pool/search/guess2';

// Material
export const materialUpdateRoute = (materialId) => {
    return '/api/v1/materials/' + materialId;
};

// Keyword
export const materialAddKeywordRoute    = (materialID, keywordId) => {
    return '/api/v1/material/' + materialID + '/keyword/' + keywordId;
};
export const materialRemoveKeywordRoute = (materialID, keywordId) => {
    return '/api/v1/material/' + materialID + '/keyword/' + keywordId;
};
export const createKeywordRoute         = '/api/v1/keywords/';
export const keywordUpdateRoute         = (keywordId) => {
    return '/api/v1/keywords/' + keywordId;
};
export const keywordUpdatePivotRoute    = (materialId, keywordId) => {
    return '/api/v1/material/' + materialId + '/keyword/' + keywordId;
};
export const keywordSearchLink          = (keyword) => {
    return '/pool/keyword/' + keyword.lc_title;
};

// Bibleverse
export const bibleverseUpdatePivotRoute = (materialId, bibleverseId) => {
    return '/api/v1/material/' + materialId + '/bibleverse/' + bibleverseId;
};
export const materialRemoveBibleverseRoute = (materialID, bibleverseId) => {
    return '/api/v1/material/' + materialID + '/bibleverse/' + bibleverseId;
};
export const createBibleverseRoute = '/api/v1/bibleverses';
export const bibleverseSearchLink          = (bibleverse) => {
    return '/pool/bibleverse/' + bibleverse.from + '-' + bibleverse.to;
};

// Search
export const searchUrl              = '/pool/search/get';
export const searchGuessKeywords    = '/pool/search/guess/keywords';
export const searchGuessBibleverses = '/pool/search/guess/bibleverses';