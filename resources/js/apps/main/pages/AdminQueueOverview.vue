<template>
  <main class="container pb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <div>
        <h1 class="h3 mb-1">{{ $t('pool.queue-overview-title') }}</h1>
        <p class="text-muted mb-0">{{ $t('pool.queue-overview-intro') }}</p>
      </div>
      <button class="btn btn-outline-primary queue-refresh-button" type="button" :disabled="store.loading" @click="refresh()">
        {{ store.loading ? $t('pool.queue-loading') : $t('pool.queue-refresh-countdown', {seconds: countdown}) }}
      </button>
    </div>

    <div v-if="store.error" class="alert alert-danger" role="alert">{{ store.error }} {{ $t('pool.queue-stale') }}</div>
    <div v-if="store.actionMessage" class="alert alert-success alert-dismissible" role="status">{{ store.actionMessage }}<button type="button" class="btn-close" :aria-label="$t('pool.close')" @click="store.actionMessage = null"></button></div>

    <section class="row g-2 mb-4" :aria-label="$t('pool.queue-summary')">
      <div v-for="metric in summaryMetrics" :key="metric.key" class="col-6 col-lg-3">
        <div class="card h-100"><div class="card-body py-2"><div class="small text-muted">{{ $t(metric.label) }}</div><strong class="fs-4">{{ store.summary[metric.key] }}</strong></div></div>
      </div>
    </section>

    <div class="nav nav-tabs mb-3" role="tablist" :aria-label="$t('pool.queue-tabs')">
      <button v-for="tab in tabs" :key="tab.key" class="nav-link" :class="{active: store.tab === tab.key}" type="button" role="tab" :aria-selected="store.tab === tab.key" @click="selectTab(tab.key)">{{ $t(tab.label) }}</button>
    </div>

    <form class="row g-2 mb-3" @submit.prevent="store.load(1)">
      <div class="col-md-4">
        <label class="form-label" for="queue-filter">{{ $t('pool.queue-name') }}</label>
        <select id="queue-filter" v-model="store.filters.queue" class="form-select" @change="store.load(1)">
          <option value="">{{ $t('pool.All') }}</option>
          <option v-for="queue in store.queues" :key="queue" :value="queue">{{ queue }}</option>
        </select>
      </div>
      <div v-if="store.tab !== 'batches'" class="col-md-4">
        <label class="form-label" for="queue-type">{{ $t('pool.queue-job-type') }}</label>
        <input id="queue-type" v-model="store.filters.type" class="form-control" type="search">
      </div>
      <div v-if="store.tab === 'jobs'" class="col-md-2">
        <label class="form-label" for="queue-status">{{ $t('pool.Status') }}</label>
        <select id="queue-status" v-model="store.filters.status" class="form-select" @change="store.load(1)">
          <option value="">{{ $t('pool.All') }}</option>
          <option value="waiting">{{ $t('pool.queue-waiting') }}</option>
          <option value="delayed">{{ $t('pool.queue-delayed') }}</option>
          <option value="reserved">{{ $t('pool.queue-reserved') }}</option>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-primary" type="submit" :disabled="store.loading">{{ $t('pool.Apply-filter') }}</button>
      </div>
    </form>

    <p v-if="store.tab === 'jobs' && sort.key" class="small text-muted mb-2">{{ $t('pool.queue-sort-page-hint') }}</p>
    <p v-if="!store.loading && store.items.length === 0" class="text-muted">{{ $t('pool.queue-empty') }}</p>
    <section v-for="group in groups" :key="group.name" class="card mb-3" :aria-label="group.name">
      <div class="card-header d-flex justify-content-between gap-2"><strong>{{ group.name }}</strong><span>{{ group.items.length }} {{ $t('pool.queue-on-page') }}</span></div>
      <p class="small text-muted px-3 pt-2 mb-0 d-md-none">{{ $t('pool.queue-scroll-hint') }}</p>
      <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
          <thead v-if="store.tab === 'jobs'"><tr>
            <th v-for="column in jobColumns" :key="column.key" scope="col" :aria-sort="sort.key === column.key ? (sort.direction === 'asc' ? 'ascending' : 'descending') : 'none'">
              <button class="btn btn-link p-0 text-body text-decoration-none fw-semibold" type="button" :aria-label="$t(sort.key === column.key && sort.direction === 'asc' ? 'pool.queue-sort-desc' : 'pool.queue-sort-asc', {column: $t(column.label)})" @click="sortJobs(column.key)">
                {{ $t(column.label) }} <span aria-hidden="true">{{ sort.key === column.key ? (sort.direction === 'asc' ? '▲' : '▼') : '↕' }}</span>
              </button>
            </th>
            <th scope="col">{{ $t('pool.Actions') }}</th>
          </tr></thead>
          <thead v-else-if="store.tab === 'failed'"><tr><th>ID</th><th>{{ $t('pool.queue-job-type') }}</th><th>{{ $t('pool.queue-connection') }}</th><th>{{ $t('pool.queue-failed-at') }}</th><th>{{ $t('pool.Actions') }}</th></tr></thead>
          <thead v-else><tr><th>{{ $t('pool.queue-batch') }}</th><th>{{ $t('pool.Status') }}</th><th>{{ $t('pool.queue-progress') }}</th><th>{{ $t('pool.queue-failed') }}</th><th>{{ $t('pool.queue-created') }}</th></tr></thead>
          <tbody>
            <tr v-for="item in group.items" :key="item.id">
              <template v-if="store.tab === 'jobs'">
                <td>{{ item.id }}</td><td class="text-break">{{ item.type }}</td><td>{{ statusLabel(item.status) }}</td><td>{{ epochTime(item.created_at) }}</td><td>{{ epochTime(item.available_at) }}</td><td>{{ item.attempts }}</td><td><button class="btn btn-sm btn-outline-primary" type="button" @click="openJobDetail(item)">{{ $t('pool.queue-details') }}</button></td>
              </template>
              <template v-else-if="store.tab === 'failed'">
                <td>{{ item.id }}</td><td class="text-break">{{ item.type }}</td><td>{{ item.connection }}</td><td>{{ dateTime(item.failed_at) }}</td><td><button class="btn btn-sm btn-outline-primary" type="button" @click="openFailedDetail(item)">{{ $t('pool.queue-details') }}</button></td>
              </template>
              <template v-else>
                <td class="text-break">{{ item.name }}<div class="small text-muted">{{ item.id }}</div></td><td>{{ batchStatus(item) }}</td><td>{{ item.total_jobs - item.pending_jobs }} / {{ item.total_jobs }}</td><td>{{ item.failed_jobs }}</td><td>{{ epochTime(item.created_at) }}</td>
              </template>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <nav class="d-flex justify-content-between align-items-center" :aria-label="$t('pool.Pagination')">
      <span>{{ store.pagination.total }} {{ $t('pool.queue-entries') }}</span>
      <div class="btn-group">
        <button class="btn btn-outline-secondary" type="button" :disabled="store.loading || store.pagination.current_page <= 1" @click="store.load(store.pagination.current_page - 1)">‹</button>
        <button class="btn btn-outline-secondary" type="button" disabled>{{ store.pagination.current_page }} / {{ store.pagination.last_page }}</button>
        <button class="btn btn-outline-secondary" type="button" :disabled="store.loading || store.pagination.current_page >= store.pagination.last_page" @click="store.load(store.pagination.current_page + 1)">›</button>
      </div>
    </nav>

    <b-modal ref="jobDetailModal" size="xl" scrollable hide-footer :title="$t('pool.queue-job-detail-title')" @hide="store.resetJobDetail()">
      <p v-if="store.jobDetailLoading" role="status">{{ $t('pool.queue-loading') }}</p>
      <div v-if="store.jobDetailError" class="alert alert-danger" role="alert">{{ store.jobDetailError }}</div>
      <template v-if="store.jobDetail">
        <p class="text-break"><strong>{{ store.jobDetail.type }}</strong><br>{{ store.jobDetail.queue }} · {{ statusLabel(store.jobDetail.status) }} · ID {{ store.jobDetail.id }}</p>
        <p class="small text-muted">{{ $t('pool.queue-created') }}: {{ epochTime(store.jobDetail.created_at) }} · {{ $t('pool.queue-available') }}: {{ epochTime(store.jobDetail.available_at) }} · {{ $t('pool.queue-attempts') }}: {{ store.jobDetail.attempts }}</p>
        <h2 class="h6">{{ $t('pool.queue-payload') }}</h2>
        <div v-if="store.jobDetail.payload" class="queue-json-viewer border rounded p-2 bg-light"><JsonDataViewer :data="store.jobDetail.payload" /></div>
        <p v-else class="text-muted">{{ $t('pool.queue-payload-unavailable') }}</p>
      </template>
    </b-modal>

    <b-modal ref="failedDetailModal" size="xl" scrollable hide-footer :title="$t('pool.queue-failed-detail-title')" @hide="store.resetFailedDetail()">
      <p v-if="store.failedDetailLoading" role="status">{{ $t('pool.queue-loading') }}</p>
      <div v-if="store.failedDetailError" class="alert alert-danger" role="alert">{{ store.failedDetailError }}</div>
      <template v-if="store.failedDetail">
        <p class="text-break"><strong>{{ store.failedDetail.type }}</strong><br>{{ store.failedDetail.queue }} · {{ store.failedDetail.connection }} · {{ dateTime(store.failedDetail.failed_at) }}<br><small>{{ store.failedDetail.uuid }}</small></p>
        <h2 class="h6">{{ $t('pool.queue-exception') }}</h2>
        <div class="alert alert-danger text-break mb-2">{{ parsedException.headline }}</div>
        <template v-if="parsedException.applicationFrames.length">
          <h3 class="h6">{{ $t('pool.queue-app-frames') }}</h3>
          <ol class="queue-detail-text border rounded p-2 ps-5 bg-light">
            <li v-for="frame in parsedException.applicationFrames" :key="frame" class="text-break">{{ frame.replace(/^#\d+\s+/, '') }}</li>
          </ol>
        </template>
        <details v-if="parsedException.trace" class="mb-3">
          <summary class="text-primary">{{ $t('pool.queue-full-trace') }}</summary>
          <pre class="queue-detail-text border rounded p-2 bg-light mt-2">{{ store.failedDetail.exception }}</pre>
        </details>
        <h2 class="h6">{{ $t('pool.queue-payload') }}</h2>
        <div v-if="store.failedDetail.payload" class="queue-json-viewer border rounded p-2 bg-light"><JsonDataViewer :data="store.failedDetail.payload" /></div>
        <p v-else class="text-muted">{{ $t('pool.queue-payload-unavailable') }}</p>
        <p v-if="!store.failedDetail.can_retry" class="small text-muted">{{ $t('pool.queue-retry-unavailable') }}</p>
        <div class="d-flex flex-wrap justify-content-end gap-2">
          <button class="btn btn-outline-secondary" type="button" :disabled="store.failedActionLoading" @click="failedDetailModal.hide()">{{ $t('pool.close') }}</button>
          <button class="btn btn-outline-danger" type="button" :disabled="store.failedActionLoading" @click="runFailedAction('delete')">{{ $t('pool.queue-delete') }}</button>
          <button class="btn btn-primary" type="button" :disabled="store.failedActionLoading || !store.failedDetail.can_retry" @click="runFailedAction('retry')">{{ $t('pool.queue-retry') }}</button>
        </div>
      </template>
    </b-modal>
  </main>
</template>

<script setup>
import {computed, getCurrentInstance, onMounted, onUnmounted, ref, watch} from 'vue';
import {BModal} from '@/adapters/bootstrap';
import JsonDataViewer from '@/adapters/JsonDataViewer.vue';
import {useQueueOverviewStore} from '../stores/queueOverview';
import {parsePhpException} from './parsePhpException';

const store = useQueueOverviewStore();
const $t = getCurrentInstance().proxy.$t;
const tabs = [{key: 'jobs', label: 'pool.queue-current-jobs'}, {key: 'failed', label: 'pool.queue-failed-jobs'}, {key: 'batches', label: 'pool.queue-batches'}];
const summaryMetrics = [{key: 'waiting', label: 'pool.queue-waiting'}, {key: 'delayed', label: 'pool.queue-delayed'}, {key: 'reserved', label: 'pool.queue-reserved'}, {key: 'failed', label: 'pool.queue-failed'}];
const jobColumns = [{key: 'id', label: 'pool.queue-id'}, {key: 'type', label: 'pool.queue-job-type'}, {key: 'status', label: 'pool.Status'}, {key: 'created_at', label: 'pool.queue-created'}, {key: 'available_at', label: 'pool.queue-available'}, {key: 'attempts', label: 'pool.queue-attempts'}];
const sort = ref({key: null, direction: 'asc'});
const textCollator = new Intl.Collator('de-DE', {sensitivity: 'base'});
const groups = computed(() => {
  const grouped = new Map();
  for (const item of store.items) {
    const name = item.queue || $t('pool.queue-unknown');
    if (!grouped.has(name)) grouped.set(name, []);
    grouped.get(name).push(item);
  }
  return [...grouped].map(([name, items]) => ({name, items: store.tab === 'jobs' && sort.value.key ? [...items].sort(compareJobs) : items}));
});
const failedDetailModal = ref(null);
const jobDetailModal = ref(null);
const now = ref(Date.now());
const nextRefreshAt = ref(null);
const countdown = computed(() => Math.max(0, Math.ceil(((nextRefreshAt.value || now.value + 5000) - now.value) / 1000)));
const parsedException = computed(() => parsePhpException(store.failedDetail?.exception));
let timer;

function compareJobs(first, second) {
  const key = sort.value.key;
  const firstValue = key === 'status' ? statusLabel(first.status) : first[key];
  const secondValue = key === 'status' ? statusLabel(second.status) : second[key];
  const comparison = typeof firstValue === 'string' ? textCollator.compare(firstValue, secondValue) : firstValue - secondValue;
  return (comparison || first.id - second.id) * (sort.value.direction === 'asc' ? 1 : -1);
}

function sortJobs(key) {
  sort.value = {key, direction: sort.value.key === key && sort.value.direction === 'asc' ? 'desc' : 'asc'};
}

watch(() => store.loading, loading => {
  if (!loading) scheduleRefresh();
});

function scheduleRefresh() {
  now.value = Date.now();
  nextRefreshAt.value = now.value + 5000;
}

function refresh(page = store.pagination.current_page) {
  scheduleRefresh();
  return store.load(page);
}

function selectTab(tab) {
  if (store.tab === tab) return;
  store.tab = tab;
  store.filters = {queue: '', type: '', status: ''};
  store.items = [];
  store.queues = [];
  store.pagination = {current_page: 1, last_page: 1, total: 0};
  store.refreshedAt = null;
  store.error = null;
  store.load(1);
}

function epochTime(value) {
  return value == null ? '–' : dateTime(new Date(value * 1000));
}

function dateTime(value) {
  return value ? new Intl.DateTimeFormat('de-DE', {dateStyle: 'short', timeStyle: 'medium'}).format(new Date(value)) : '–';
}

function statusLabel(status) {
  return $t(`pool.queue-${status}`);
}

function batchStatus(batch) {
  return $t(batch.cancelled_at ? 'pool.queue-cancelled' : (batch.finished_at ? 'pool.queue-finished' : 'pool.queue-open'));
}

function openFailedDetail(item) {
  failedDetailModal.value.show();
  store.loadFailedDetail(item.uuid);
}

function openJobDetail(item) {
  jobDetailModal.value.show();
  store.loadJobDetail(item.id);
}

async function runFailedAction(action) {
  const detail = store.failedDetail;
  if (!detail || !window.confirm($t(action === 'retry' ? 'pool.queue-retry-confirm' : 'pool.queue-delete-confirm'))) return;
  const succeeded = action === 'retry' ? await store.retryFailed(detail.uuid) : await store.deleteFailed(detail.uuid);
  if (!succeeded) return;
  failedDetailModal.value.hide();
  await refresh();
  if (store.items.length === 0 && store.pagination.current_page > store.pagination.last_page) await refresh(store.pagination.last_page);
}

onMounted(() => {
  refresh(1);
  timer = window.setInterval(() => {
    now.value = Date.now();
    if (document.visibilityState === 'visible' && nextRefreshAt.value && now.value >= nextRefreshAt.value && !store.loading) refresh();
  }, 1000);
});
onUnmounted(() => window.clearInterval(timer));
</script>

<style scoped>
.table-responsive table { min-width: 48rem; }
.queue-refresh-button { width: 17rem; max-width: 100%; font-variant-numeric: tabular-nums; }
.queue-detail-text { max-height: 18rem; overflow: auto; white-space: pre-wrap; overflow-wrap: anywhere; }
.queue-json-viewer { max-height: 30rem; overflow: auto; }
</style>
