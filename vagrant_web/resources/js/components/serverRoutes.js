export const searchGuessRoute  = '/pool/search/guess';
export const searchGuessRoute2 = '/pool/search/guess2';


// Keyword
export function keywordSearchLink(keyword) {
    return '/pool/keyword/' + keyword.lc_title;
}

// Bibleverse
export function bibleverseUpdatePivotRoute(materialId, bibleverseId) {
    return '/api/v1/material/' + materialId + '/bibleverse/' + bibleverseId;
}

export function materialRemoveBibleverseRoute(materialID, bibleverseId) {
    return '/api/v1/material/' + materialID + '/bibleverse/' + bibleverseId;
}

export function bibleverseSearchLink(bibleverse) {
    return '/pool/bibleverse/' + bibleverse.from + '-' + bibleverse.to;
}

//Resources
export function resourceEditLink(resource) {
    return '/pool/resource/' + resource.id;
}

export function resourceDownloadLink(resource) {
    return '/pool/resource/' + resource.id + '/download';
}

export function previewImageFirstPage(resource, width, height) {
    width  = width || 1024;
    height = height || 1024;
    return '/resource/' + resource.id + '/image/' + width + '/' + height;
}

export function pdfPreviewImageForPage(resource, page) {
    page = page || 1;
    return '/resource/' + resource.id + '/image/page-' + page;
}

export function resourceLimitedPdfDownload(resourceId, materialId) {
    return '/pool/resource/' + resourceId + '/material/' + materialId + '/pdfdownload';
}

export function poolResourceVideostream(resource) {
    return '/pool/resource/' + resource.id + '/videostream';
}

export const api_v1_resources_find = '/api/v1/resources/find';

// GeneralOptions
export const api_v1_general_options = '/api/v1/general/options';


// Resource-Material Assignment
export function api_v2_materialresource_attach(materialId, resourceId) {
    return '/api/v2/material/' + materialId + '/resource/' + resourceId + '/attach';
}

export function api_v2_materialresource_detach(materialId, resourceId) {
    return '/api/v2/material/' + materialId + '/resource/' + resourceId + '/detach';
}


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

export const api_v1_resources_create_material = '/api/v1/resources/create-material';

export function api_v1_resource_pdf_tags(resourceId) {
    return '/api/v1/resources/' + resourceId + '/pdf-tags';
}


// API - Materials
export function api_v1_materials_show(materialsId) {
    return '/api/v1/materials/' + materialsId;
}

export const api_v1_materials_store = '/api/v1/materials';
export const api_v1_materials_index = '/api/v1/materials';

export function api_v1_materials_update(materialId) {
    return '/api/v1/materials/' + materialId;
}

export function api_v2_materials_delete(materialId) {
    return '/api/v2/materials/' + materialId;
}

export function api_v1_materials_copy(materialId) {
    return '/api/v1/materials/' + materialId + '/copy';
}

export function material_preview_image(materialId) {
    return '/material/' + materialId + '/preview';
}

// API - Keywords
export const api_v1_keywords_index = '/api/v1/keywords/';

export function api_v1_keywords_show(keywordId) {
    return '/api/v1/keywords/' + keywordId;
}

export const api_v1_keywords_create = '/api/v1/keywords';

export function api_v1_keywords_update(keywordId) {
    return '/api/v1/keywords/' + keywordId;
}

export function api_v1_keywords_delete(keywordId) {
    return '/api/v1/keywords/' + keywordId;
}

export function api_v1_keywords_updateassignment(materialId, keywordId) {
    return '/api/v1/material/' + materialId + '/keyword/' + keywordId;
}

export function api_v1_keywords_deleteassignment(materialID, keywordId) {
    return '/api/v1/material/' + materialID + '/keyword/' + keywordId;
}


// API - Bibles
export const api_v1_bibles_index = '/api/v1/bibles';

export function api_v1_bibles_show(bibleUuid) {
    return '/api/v1/bibles/' + bibleUuid;
}

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

export function api_v1_bundles_update_show(bundleId) {
    return '/api/v1/bundles/' + bundleId;
}

export function api_v1_bundles_update_init(bundleId) {
    return '/api/v1/bundles/' + bundleId + '/init-update';
}

export function api_v1_bundles_update_run(bundleId) {
    return '/api/v1/bundles/' + bundleId + '/run-update';
}

export function api_v1_bundles_get_icon(bundleId) {
    return '/api/v1/bundles/' + bundleId + '/icon';
}

// API - BibleContentVerse
export function api_v1_biblecontents_get(from, to, bibleUuid) {

    let route = '/api/v1/biblecontents/' + from + '-' + to;

    if (bibleUuid) {
        route += '/' + bibleUuid;
    }

    return route;
}

export function api_v1_biblecontents_search_and_get(searchText, bibleUuid) {

    let route = '/api/v1/biblecontents/search';

    if (bibleUuid) {
        route += '/' + bibleUuid;
    }

    return route;
}
