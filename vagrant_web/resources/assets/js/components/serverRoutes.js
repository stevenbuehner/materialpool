export const searchGuessRoute  = '/pool/search/guess';
export const searchGuessRoute2 = '/pool/search/guess2';


// Keyword
export const keywordSearchLink = (keyword) => {
    return '/pool/keyword/' + keyword.lc_title;
};

// Bibleverse
export const bibleverseUpdatePivotRoute    = (materialId, bibleverseId) => {
    return '/api/v1/material/' + materialId + '/bibleverse/' + bibleverseId;
};
export const materialRemoveBibleverseRoute = (materialID, bibleverseId) => {
    return '/api/v1/material/' + materialID + '/bibleverse/' + bibleverseId;
};
export const bibleverseSearchLink          = (bibleverse) => {
    return '/pool/bibleverse/' + bibleverse.from + '-' + bibleverse.to;
};

//Resources
export const resourceEditLink         = (resource) => {
    return '/pool/resource/' + resource.id;
};
export const resourceDownloadLink     = (resource) => {
    return '/pool/resource/' + resource.id + '/download';
};
export const pdfPreviewImageFirstPage = (resource, width, height) => {
    width  = width || 1024;
    height = height || 1024;

    return '/resource/image/' + resource.id + '/' + width + '/' + height;
};
export const pdfPreviewImageForPage   = (resource, page) => {
    page = page || 1;

    return '/pdfpreview/res-' + resource.id + '/page-' + page;
};

// Resource-Material Assignment
export const api_v2_materialresource_attach = (materialId, resourceId) => {
    return '/api/v2/material/' + materialId + '/resource/' + resourceId + '/attach';
};

export const api_v2_materialresource_detach = (materialId, resourceId) => {
    return '/api/v2/material/' + materialId + '/resource/' + resourceId + '/detach';
};


// Search
export const searchUrl              = '/pool/search/get';
export const searchGuessKeywords    = '/pool/search/guess/keywords';
export const searchGuessBibleverses = '/pool/search/guess/bibleverses';


// API - Material
export const api_v1_materials_show  = (materialsId) => {
    return '/api/v1/materials/' + materialsId;
};
export const api_v1_materials_index = '/api/v1/materials';

// API - Resource
export const api_v1_resources_show   = (resourceId) => {
    return '/api/v1/resources/' + resourceId;
};
export const api_v1_materials_update = (materialId) => {
    return '/api/v1/materials/' + materialId;
};

// API - Keywords
export function api_v1_keywords_show(keywordId) {
    return '/api/v1/keywords/' + keywordId;
}

export const api_v1_keywords_create           = '/api/v1/keywords/';
export const api_v1_keywords_update           = (keywordId) => {
    return '/api/v1/keywords/' + keywordId;
};
export const api_v1_keywords_updateassignment = (materialId, keywordId) => {
    return '/api/v1/material/' + materialId + '/keyword/' + keywordId;
};
export const api_v1_keywords_deleteassignment = (materialID, keywordId) => {
    return '/api/v1/material/' + materialID + '/keyword/' + keywordId;
};

// API - Bibleverses
export function api_v1_bibleverses_show(bibleverseId) {
    return '/api/v1/bibleverses/' + bibleverseId;
}

export const api_v1_bibleverses_create = '/api/v1/bibleverses';

export function api_v1_bibleverse_updateassignment(materialId, bibleverseId) {
    return '/api/v1/material/' + materialId + '/bibleverse/' + bibleverseId;
}

export function api_v1_bibleverse_deleteassignment(materialID, bibleverseId) {
    return '/api/v1/material/' + materialID + '/bibleverse/' + bibleverseId;
}