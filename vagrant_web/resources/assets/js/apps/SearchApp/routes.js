import MaterialApp from './../Material/App.vue'
import MaterialDetail from './../../components/Material/MaterialDetail.vue';
import SearchPage from './../../components/pages/searchPage.vue';
import ResourceDetail from './../../components/resource/resource.vue';

export const routes = [

    {
        path: '/search/:page?', component: SearchPage, name: 'search', props: (route) => {

            let params = {};

            if (route.params.page) {
                params.page = parseInt(route.params.page);
            }

            return params;
        }
    },
    {
        path: '/material', component: MaterialApp, name: 'material'
    },
    {
        path: '/material/:id', component: MaterialDetail, name: 'material-detail', props: (route) => {
            return {id: parseInt(route.params.id)};
        }
    },
    {
        path: '/resource/:id', component: ResourceDetail, name: 'resource-detail', props: (route) => {
            return {id: parseInt(route.params.id)};
        }
    },

    {path: '*', redirect: '/search'}

];