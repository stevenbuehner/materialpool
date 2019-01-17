import MaterialDetail from './pages/MaterialDetail.vue';
import SearchPage from './pages/search/searchPage.vue';
import ResourceDetail from './pages/Resource.vue';
import AssignApp from './pages/PdfAssignApp.vue';
// import PassportClient from '../../components/passport/Clients.vue';
// import PassportAuthorizedClient from '../../components/passport/AuthorizedClients.vue';
// import PassportPersonalAccessTokens from '../../components/passport/PersonalAccessTokens.vue';
import ResourceCreate from './pages/ResourceCreate.vue';
import KeywordDetail from './pages/KeywordDetail.vue'
import ResourceTextCreate from './pages/ResourceTextCreateWithMaterial.vue';


const KeywordList = () => import('./pages/KeywordList.vue');
const ReadBible   = () => import('./pages/ReadBible');
const BundleList  = () => import('./pages/BundleList.vue');
const MaterialApp = () => import('./pages/MaterialList.vue');

export const routes = [

           {
               path: '/search/:search?', component: SearchPage, name: 'search', props: (route) => {

                   let page = 1;

                   if (route.query.page) {
                       page = parseInt(route.query.page);
                   }

                   return {
                       query: route.params.search || '',
                       page: page
                   };
               },
           },

           {
               path: '/material', component: MaterialApp, name: 'material', alias: '/materials'
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
               path: '/resource/text/create', component: ResourceTextCreate, name: 'resource-text-create', props: false
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
               path: '/resource/:id/pdf-assign', name: 'resource-pdf-assign', component: AssignApp, props: (route) => {
                   return {id: parseInt(route.params.id)};
               }
           },

           {
               path: '/keyword', name: 'keyword-list', component: KeywordList, alias: '/keywords'
           },
           {
               path: '/keyword/:id', name:
                   'keyword-detail', component:
               KeywordDetail, props:
                   (route) => {
                       return {id: parseInt(route.params.id)};
                   }
           }
           ,
           {
               path: '/bundle', name: 'bundle-list', component: BundleList, alias: '/bundles'
           },
           {
               path: '/readbible/:from/:to/:bibleId?', component: ReadBible, name: 'readbible', props: (route) => {
                   const result = {
                       from: parseInt(route.params.from),
                       to: parseInt(route.params.to),
                   };

                   if (route.params.bibleId) {
                       result.bibleId = parseInt(route.params.bibleId)
                   }

                   return result;
               }
           },
           /*
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
           */
           {
               path: '*', redirect:
                   '/search'
           }

       ]
;