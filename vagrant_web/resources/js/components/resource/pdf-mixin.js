import {pdfPreviewImageForPage} from '../serverRoutes';

export default {

    computed: {

        pageCount() {
            return this.resource.page_count || 0;
        },

        pagePivotCount() {
            if (this.resource.pivot && this.resource.pivot.limitation && Array.isArray(this.resource.pivot.limitation.pages)) {
                return this.resource.pivot.limitation.pages.length;
            } else {
                return undefined;
            }
        },

        previewablePages() {

            let result = [];

            if (this.resource.pivot && this.resource.pivot.limitation && this.resource.pivot.limitation.pages && this.resource.pivot.limitation.pages.length > 0) {

                let pages = this.resource.pivot.limitation.pages;

                for (let i in pages) {
                    if (pages[i] > 0 && pages[i] <= this.pageCount) {
                        result.push(pages[i]);
                    }
                }

            } else {
                for (let i = 1; i <= this.pageCount; i++) {
                    result.push(i);
                }
            }

            return result;
        }
    },

    methods: {
        generatePreviewObject(resource, pageNo) {

            return {
                src: pdfPreviewImageForPage(resource, pageNo),
                title: 'Seite ' + pageNo,
                page_no: pageNo
            };

        },
    },

}