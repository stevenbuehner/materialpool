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
        materials: [{
            from_bot: true,
            id: 1,
            description: "Voluptates consectetur in non sapiente sequi. Rerum porro omnis repudiandae explicabo tempora officia.",
            created_at: "2017-04-06 20:36:19",
            created_by: 8,
            author: {
                icon: "/img/icons/person.svg",
                id: 22,
                lc_title: "andreas_jägers",
                parent_id: null,
                title: "Andreas Jägers",
                type: "person"
            },
            limitation: false,
            modified_by: 8,
            rating: 20,
            title: "Commodi molestias vitae natus in aliquid est. Cumque perspiciatis aut praesentium saepe. Inventore qui saepe beatae veniam et ut dolorem. Ipsam id amet quibusdam corporis.",
            updated_at: "2017-04-06 20:36:19",
        }, {title: 'test'}, {title: 'another'}],
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