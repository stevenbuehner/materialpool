// Page-level components are lazy-loaded so that feature-specific dependencies
// are fetched only when their route is opened.
const LandingPage = () => import('./pages/LandingPage.vue');
const SearchPage = () => import('./pages/search/searchPage.vue');
const MaterialDetail = () => import('./pages/MaterialDetail2.vue');
const KeywordList = () => import('./pages/KeywordList.vue');
const ReadBible = () => import('./pages/ReadBible.vue');
const BundleList = () => import('./pages/BundleList.vue');
const MaterialApp = () => import('./pages/MaterialList.vue');
const ResourceCreate = () => import('./pages/ResourceCreate.vue');
const ResourceTextCreate = () => import('./pages/ResourceTextCreateWithMaterial.vue');
const ResourceDetail = () => import('./pages/Resource.vue');
const AssignApp = () => import('./pages/AssignApp.vue');
const ResourceLonely = () => import('./pages/ResourceLonely.vue');
const ResourceNewest = () => import('./pages/ResourceNewest.vue');
const MaterialOrderedListing = () => import('./pages/MaterialOrderedListing.vue');
const ResourceReplace = () => import('./pages/ResourceReplace.vue');
const KeywordDetail = () => import('./pages/KeywordDetail.vue');
const SystemShutdown = () => import('./pages/RequestShutdown.vue');
const AdminUsers = () => import('./pages/AdminUsers.vue');
const Profile = () => import('./pages/Profile.vue');
const AdminQueueOverview = () => import('./pages/AdminQueueOverview.vue');
const ContextSearchEvaluationDatasets = () => import('./pages/ContextSearchEvaluationDatasets.vue');
const ContextSearchOcrCalibration = () => import('./pages/ContextSearchOcrCalibration.vue');

const numericIdProps = (route) => ({id: parseInt(route.params.id)});

export function scrollBehavior(_to, _from, savedPosition) {
	return savedPosition || {left: 0, top: 0};
}

export const routes = [
	{
		path: '/',
		component: LandingPage,
		name: 'landingpage',
	},
	{
		path: '/search/:search?',
		component: SearchPage,
		name: 'search',
		props: (route) => ({
			query: route.params.search || '',
			page: route.query.page ? parseInt(route.query.page) : 1,
		}),
	},
	{
		path: '/material',
		component: MaterialApp,
		name: 'material',
		alias: '/materials',
	},
	{
		path: '/material/newest',
		component: MaterialOrderedListing,
		name: 'material-newest',
		props: (route) => ({
			orderBy: 'created_at',
			titleKey: 'Newest-Materials',
			page: route.query.page ? parseInt(route.query.page) : 1,
		}),
	},
	{
		path: '/material/recently-updated',
		component: MaterialOrderedListing,
		name: 'material-recently-updated',
		props: (route) => ({
			orderBy: 'updated_at',
			titleKey: 'Recently-Updated-Materials',
			page: route.query.page ? parseInt(route.query.page) : 1,
		}),
	},
	{
		path: '/material/:id',
		component: MaterialDetail,
		name: 'material-detail',
		props: (route) => ({
			id: parseInt(route.params.id),
			tabIndex: parseInt(route.query.tabIndex) || 0,
		}),
	},
	{
		path: '/resource/create',
		component: ResourceCreate,
		name: 'resource-create',
		props: false,
	},
	{
		path: '/resource/text/create',
		component: ResourceTextCreate,
		name: 'resource-text-create',
		props: false,
	},
	{
		path: '/resource/lonely',
		component: ResourceLonely,
		name: 'resource-lonely',
		props: false,
	},
	{
		path: '/resource/newest',
		component: ResourceNewest,
		name: 'resource-newest',
		props: false,
	},
	{
		path: '/resource/:id',
		component: ResourceDetail,
		name: 'resource-detail',
		props: numericIdProps,
	},
	{
		path: '/resource/:id/assign',
		component: AssignApp,
		name: 'resource-assign',
		props: numericIdProps,
	},
	{
		path: '/resource/:id/page-assign',
		component: AssignApp,
		name: 'resource-page-assign',
		props: numericIdProps,
	},
	{
		path: '/resource/:r1/replace-with/:r2?',
		component: ResourceReplace,
		name: 'resource-replace',
		props: (route) => ({
			r1: parseInt(route.params.r1),
			r2: route.params.r2 ? parseInt(route.params.r2) : null,
		}),
	},
	{
		path: '/keyword',
		component: KeywordList,
		name: 'keyword-list',
		alias: '/keywords',
	},
	{
		path: '/keyword/:id',
		component: KeywordDetail,
		name: 'keyword-detail',
		props: numericIdProps,
	},
	{
		path: '/bundle',
		component: BundleList,
		name: 'bundle-list',
		alias: '/bundles',
	},
	{
		path: '/readbible/:searchquery?',
		component: ReadBible,
		name: 'readbible',
		props: true,
	},
	{
		path: '/profil',
		component: Profile,
		name: 'profile',
	},
	{
		path: '/admin/users',
		component: AdminUsers,
		name: 'admin-users',
		props: false,
	},
	{
		path: '/admin/queues',
		component: AdminQueueOverview,
		name: 'admin-queues',
	},
	{
		path: '/admin/context-search/datasets',
		component: ContextSearchEvaluationDatasets,
		name: 'context-search-evaluation-datasets',
		props: false,
	},
	{
		path: '/admin/context-search/ocr-calibration',
		component: ContextSearchOcrCalibration,
		name: 'context-search-ocr-calibration',
		props: false,
	},
	{
		path: '/system/shutdown',
		component: SystemShutdown,
		name: 'system-shutdown',
		props: false,
	},
	{
		path: '/:pathMatch(.*)*',
		redirect: '/search',
	},
];
