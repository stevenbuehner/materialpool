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
export const resourceEditLink           = (resource) => {
    return '/pool/resource/' + resource.id;
};
export const resourceDownloadLink       = (resource) => {
    return '/pool/resource/' + resource.id + '/download';
};
export const pdfPreviewImageFirstPage   = (resource, width, height) => {
    width  = width || 1024;
    height = height || 1024;

    return '/resource/image/' + resource.id + '/' + width + '/' + height;
};
export const pdfPreviewImageForPage     = (resource, page) => {
    page = page || 1;

    return '/pdfpreview/res-' + resource.id + '/page-' + page;
};
export const resourceLimitedpdfDownload = (resourceId, materialId) => {
    return '/pool/resource/' + resourceId + '/material/' + materialId + '/pdfdownload';
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


// API - Resource
export function api_v1_resources_show(resourceId) {
    return '/api/v1/resources/' + resourceId;
}

export function api_v1_resources_update(resourceId) {
    return '/api/v1/resources/' + resourceId;
}

export function api_v1_resources_delete(resourceId) {
    return '/api/v1/resources/' + resourceId;
}

export const api_v1_resources_store = '/api/v1/resources';

export function api_v1_resources_create_material(resourceId) {
    return '/api/v1/resources/' + resourceId + '/create-material';
}


// API - Materials
export const api_v1_materials_show  = (materialsId) => {
    return '/api/v1/materials/' + materialsId;
};
export const api_v1_materials_store = '/api/v1/materials';
export const api_v1_materials_index = '/api/v1/materials';

export const api_v1_materials_update = (materialId) => {
    return '/api/v1/materials/' + materialId;
};
export const api_v2_materials_delete = (materialId) => {
    return '/api/v2/materials/' + materialId;
};

// API - Keywords
export const api_v1_keywords_index = '/api/v1/keywords/';

export function api_v1_keywords_show(keywordId) {
    return '/api/v1/keywords/' + keywordId;
}

export const api_v1_keywords_create           = '/api/v1/keywords';
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

// API - Bundles
export const api_v1_bundles_index = '/api/v1/bundles';

export function api_v1_bundles_update_init(bundleId) {
    return '/api/v1/bundles/' + bundleId + '/init-update';
}

export function api_v1_bundles_update_run(bundleId) {
    return '/api/v1/bundles/' + bundleId + '/run-update';
}
