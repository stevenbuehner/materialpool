import MaterialDetail from './../../components/Material/MaterialDetail.vue';
import SearchPage from './../../components/pages/searchPage.vue';

export const routes = [

    {
        path: '/search/:page?', component: SearchPage, name: 'search', props: true, children: [
            {path: 'query', component: SearchPage, name: 'searchquerry', props: true}
        ]
    },
    {
        path: '/material', component: MaterialDetail, name: 'material', children: [
            {path: ':id', component: MaterialDetail, name: 'material-detail', props: true}
        ]
    },

    {path: '*', redirect: '/search'}

];