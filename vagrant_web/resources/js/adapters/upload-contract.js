let uploadId = 0;

export const UploadStatus = Object.freeze({
    ADDED: 'added',
    QUEUED: 'queued',
    UPLOADING: 'uploading',
    CANCELED: 'canceled',
    ERROR: 'error',
    TIMEOUT: 'timeout',
    SUCCESS: 'success',
});

export function maxFileSizeInBytes(maxFileSize, binary = false) {
    const base = binary ? 1024 : 1000;

    return maxFileSize * base * base;
}

export function isAcceptedFileType(file, acceptedFileTypes = []) {
    if (acceptedFileTypes.length === 0) {
        return true;
    }

    const mimeType = file.type || '';
    const mimeGroup = mimeType.includes('/') ? mimeType.slice(0, mimeType.indexOf('/')) : '';
    const lowerName = (file.name || '').toLowerCase();

    return acceptedFileTypes.some(acceptedType => {
        const normalizedType = acceptedType.toLowerCase();

        if (normalizedType.startsWith('.')) {
            return lowerName.endsWith(normalizedType);
        }

        if (normalizedType.endsWith('/*')) {
            return mimeGroup.toLowerCase() === normalizedType.slice(0, -2);
        }

        return mimeType.toLowerCase() === normalizedType;
    });
}

export function createUploadFile(nativeFile) {
    uploadId += 1;

    return {
        id: `materialpool_upload_${uploadId}`,
        status: UploadStatus.ADDED,
        accepted: false,
        processing: false,
        nativeFile,
        lastModified: nativeFile.lastModified,
        lastModifiedDate: nativeFile.lastModifiedDate,
        name: nativeFile.name,
        size: nativeFile.size,
        type: nativeFile.type,
        webkitRelativePath: nativeFile.webkitRelativePath,
        upload: {
            bytesSent: 0,
            progress: 0,
            total: nativeFile.size,
        },
    };
}
