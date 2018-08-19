import {pdfPreviewImageFirstPage, pdfPreviewImageForPage} from './../serverRoutes';

export default {

    computed: {

        pageCount() {
            return this.resource.page_count || 0;
        },
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