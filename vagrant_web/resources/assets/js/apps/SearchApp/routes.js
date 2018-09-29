import MaterialApp from './../Material/App.vue'
import MaterialDetail from './../../components/Material/MaterialDetail.vue';
import SearchPage from './../../components/pages/searchPage.vue';
import ResourceDetail from './../../components/resource/resource.vue';
import AssignApp from './../Assign/PdfAssignApp.vue';
import PassportClient from './../../components/passport/Clients.vue';
import PassportAuthorizedClient from './../../components/passport/AuthorizedClients.vue';
import PassportPersonalAccessTokens from './../../components/passport/PersonalAccessTokens.vue';
import mainNavbar from './../../components/navbar/mainNavbar.vue';

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
    {
        path: '/resource/:id/assign', component: AssignApp, name: 'resource-assign', props: (route) => {
            return {id: parseInt(route.params.id)};
        }
    },
    {
        path: '/passport/client', component: PassportClient, name: 'passport-client'
    },
    {
        path: '/passport/authorizedclient', component: PassportAuthorizedClient, name: 'passport-authorizedclient'
    },
    {
        path: '/passport/personalaccesstokens',
        component: PassportPersonalAccessTokens,
        name: 'passport-personalaccesstokens'
    },
    {path: '*', redirect: '/search'}

];