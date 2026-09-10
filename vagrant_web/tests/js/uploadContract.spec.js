import {describe, expect, it} from 'vitest';
import {
    createUploadFile,
    isAcceptedFileType,
    maxFileSizeInBytes,
    UploadStatus,
} from '../../resources/js/adapters/upload-contract.js';

describe('upload adapter contract', () => {
    it('keeps vue-transmit decimal and binary size limits', () => {
        expect(maxFileSizeInBytes(10)).toBe(10_000_000);
        expect(maxFileSizeInBytes(10, true)).toBe(10_485_760);
    });

    it('matches extensions, exact MIME types and MIME groups', () => {
        const pdf = {name: 'Example.PDF', type: 'application/pdf'};

        expect(isAcceptedFileType(pdf, [])).toBe(true);
        expect(isAcceptedFileType(pdf, ['.pdf'])).toBe(true);
        expect(isAcceptedFileType(pdf, ['application/pdf'])).toBe(true);
        expect(isAcceptedFileType(pdf, ['application/*'])).toBe(true);
        expect(isAcceptedFileType(pdf, ['image/*'])).toBe(false);
    });

    it('wraps native files with the fields consumed by the uploader UI', () => {
        const nativeFile = {
            name: 'example.txt',
            size: 12,
            type: 'text/plain',
            lastModified: 123,
            webkitRelativePath: '',
        };
        const file = createUploadFile(nativeFile);

        expect(file).toMatchObject({
            status: UploadStatus.ADDED,
            accepted: false,
            nativeFile,
            name: 'example.txt',
            size: 12,
            type: 'text/plain',
            upload: {bytesSent: 0, progress: 0, total: 12},
        });
        expect(file.id).toMatch(/^materialpool_upload_\d+$/);
    });
});
