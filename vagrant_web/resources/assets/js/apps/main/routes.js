import MaterialApp from './pages/MaterialList.vue'
import MaterialDetail from './pages/MaterialDetail.vue';
import SearchPage from './pages/search/searchPage.vue';
import ResourceDetail from './pages/Resource.vue';
import AssignApp from './pages/PdfAssignApp.vue';
import PassportClient from '../../components/passport/Clients.vue';
import PassportAuthorizedClient from '../../components/passport/AuthorizedClients.vue';
import PassportPersonalAccessTokens from '../../components/passport/PersonalAccessTokens.vue';
import ResourceCreate from './pages/ResourceCreate.vue'

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
        path: '/resource/create', component: ResourceCreate, name: 'resource-create', props: false
    },
    {
        path: '/resource/:id', component: ResourceDetail, name: 'resource-detail', props: (route) => {
            return {id: parseInt(route.params.id)};
        }
    },
    {
        path: '/resource/:id/assign',
        component: AssignApp,
        name: 'resource-assign',
        props: (route) => {
            return {id: parseInt(route.params.id)};
        }
    },
    {
        path: '/resource/:id/pdf-assign', name: 'resource-pdf-assign', component: AssignApp, props: (route) => {
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