// window.Vue = require('vue');

$.ajaxSetup({
    headers: {
        'X-CSRF-Token': window.Laravel.csrfToken
    }
});

Vue.component('material', require('./components/Material.vue'));
Vue.component('material-list', require('./components/MaterialListing.vue'));

testVue = new Vue({
    el: '#searchbar_content_row',
    data: {
        materials: [],
        paging: {
            current_page: 1,
            from: 1,
            last_page: 1,
            next_page_url: null,
            per_page: 20,
            prev_page_url: null,
            to: 3,
            total: 3,
        }


    },
    methods: {
        updateMaterialList: function (formData) {
            console.log('updating');

            var data = {
                q: formData,
                page: 1
            };

            $.post("/pool/search/get", data)
                .done(function (result) {
                    testVue.materials = result.data;

                    testVue.paging = {
                        current_page: result.current_page,
                        from: result.from,
                        last_page: result.last_page,
                        next_page_url: result.next_page_url,
                        per_page: result.per_page,
                        prev_page_url: result.prev_page_url,
                        to: result.to,
                        total: result.total,
                    }
                });
        }
    }
})
;