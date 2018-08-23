import MaterialDetail from './../../components/Material/MaterialDetail.vue';

export const routes = [

    {
        path: '/material', component: MaterialDetail, name: 'material', children: [
            {path: ':id', component: MaterialDetail, name: 'material-detail', props: true}
        ],
    },


    {path: '*', redirect: '/'}

];