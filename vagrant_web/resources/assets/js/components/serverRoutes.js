export const searchGuessRoute  = '/pool/search/guess';
export const searchGuessRoute2 = '/pool/search/guess2';

// Material
export const materialUpdateRoute      = (materialId) => {
    return '/api/v1/materials/' + materialId;
};

// Keyword
export const keywordUpdateRoute      = (keywordId) => {
    return '/api/v1/keywords/' + keywordId;
};
export const keywordUpdatePivotRoute = (materialId, keywordId) => {
    return '/api/v1/material/' + materialId + '/keyword/' + keywordId;
};
export const keywordSearchLink = (keyword) => {
    return '/pool/keyword/' + keyword.lc_title;
};

// Bibleverse
export const bibleverseUpdatePivotRoute = (materialId, bibleverseId) => {
    return '/api/v1/material/' + materialId + '/bibleverse/' + bibleverseId;
};