export const RELEVANCE_EXIF_MIN = 40;
export const RELEVANCE_EXIF_MAX = 49;
export const RELEVANCE_USER_MIN = 100;
export const RELEVANCE_USER_AVG = 200;
export const RELEVANCE_USER_MAX = 300;

export const timeout_flashSavingMessage = 4000;
export const timeout_flashErrorMessage  = 0;
export const server_datetime_format     = 'YYYY-MM-DD HH:mm:ss';

export const keepalive_seconds_intervall = 60;

// Die Blade-Seite liefert die konfigurierten Maximalgroessen; Fallback fuer isolierte JS-Tests.
export const small_preview_image_size_x = globalThis.window?.Laravel?.previewSizes?.small?.[0] ?? 640;
export const small_preview_image_size_y = globalThis.window?.Laravel?.previewSizes?.small?.[1] ?? 640;
export const max_preview_image_size_x = globalThis.window?.Laravel?.previewSizes?.large?.[0] ?? 1536;
export const max_preview_image_size_y = globalThis.window?.Laravel?.previewSizes?.large?.[1] ?? 1536;

// Image Loading Queue
export const MAX_SIMULTANEOUS_IMAGES_LOADING = 4;
