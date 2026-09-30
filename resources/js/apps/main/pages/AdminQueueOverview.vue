<template>
  <main class="container pb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <div>
        <h1 class="h3 mb-1">{{ $t('pool.queue-overview-title') }}</h1>
        <p class="text-muted mb-0">{{ $t('pool.queue-overview-intro') }}</p>
      </div>
      <button class="btn btn-outline-primary" type="button" :disabled="store.loading" @click="store.load(store.pagination.current_page)">{{ $t('pool.queue-refresh') }}</button>
    </div>

    <div v-if="store.error" class="alert alert-danger" role="alert">{{ store.error }} {{ $t('pool.queue-stale') }}</div>
    <p class="small text-muted" role="status">{{ $t('pool.queue-last-update') }}: {{ store.refreshedAt ? dateTime(store.refreshedAt) : '–' }}<span v-if="store.loading"> · {{ $t('pool.queue-loading') }}</span></p>

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

    <p v-if="!store.loading && store.items.length === 0" class="text-muted">{{ $t('pool.queue-empty') }}</p>
    <section v-for="group in groups" :key="group.name" class="card mb-3" :aria-label="group.name">
      <div class="card-header d-flex justify-content-between gap-2"><strong>{{ group.name }}</strong><span>{{ group.items.length }} {{ $t('pool.queue-on-page') }}</span></div>
      <p class="small text-muted px-3 pt-2 mb-0 d-md-none">{{ $t('pool.queue-scroll-hint') }}</p>
      <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
          <thead v-if="store.tab === 'jobs'"><tr><th>ID</th><th>{{ $t('pool.queue-job-type') }}</th><th>{{ $t('pool.Status') }}</th><th>{{ $t('pool.queue-created') }}</th><th>{{ $t('pool.queue-available') }}</th><th>{{ $t('pool.queue-attempts') }}</th></tr></thead>
          <thead v-else-if="store.tab === 'failed'"><tr><th>ID</th><th>{{ $t('pool.queue-job-type') }}</th><th>{{ $t('pool.queue-connection') }}</th><th>{{ $t('pool.queue-failed-at') }}</th></tr></thead>
          <thead v-else><tr><th>{{ $t('pool.queue-batch') }}</th><th>{{ $t('pool.Status') }}</th><th>{{ $t('pool.queue-progress') }}</th><th>{{ $t('pool.queue-failed') }}</th><th>{{ $t('pool.queue-created') }}</th></tr></thead>
          <tbody>
            <tr v-for="item in group.items" :key="item.id">
              <template v-if="store.tab === 'jobs'">
                <td>{{ item.id }}</td><td class="text-break">{{ item.type }}</td><td>{{ statusLabel(item.status) }}</td><td>{{ epochTime(item.created_at) }}</td><td>{{ epochTime(item.available_at) }}</td><td>{{ item.attempts }}</td>
              </template>
              <template v-else-if="store.tab === 'failed'">
                <td>{{ item.id }}</td><td class="text-break">{{ item.type }}</td><td>{{ item.connection }}</td><td>{{ dateTime(item.failed_at) }}</td>
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
  </main>
</template>

<script setup>
import {computed, getCurrentInstance, onMounted, onUnmounted} from 'vue';
import {useQueueOverviewStore} from '../stores/queueOverview';

const store = useQueueOverviewStore();
const $t = getCurrentInstance().proxy.$t;
const tabs = [{key: 'jobs', label: 'pool.queue-current-jobs'}, {key: 'failed', label: 'pool.queue-failed-jobs'}, {key: 'batches', label: 'pool.queue-batches'}];
const summaryMetrics = [{key: 'waiting', label: 'pool.queue-waiting'}, {key: 'delayed', label: 'pool.queue-delayed'}, {key: 'reserved', label: 'pool.queue-reserved'}, {key: 'failed', label: 'pool.queue-failed'}];
const groups = computed(() => {
  const grouped = new Map();
  for (const item of store.items) {
    const name = item.queue || $t('pool.queue-unknown');
    if (!grouped.has(name)) grouped.set(name, []);
    grouped.get(name).push(item);
  }
  return [...grouped].map(([name, items]) => ({name, items}));
});
let timer;

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

onMounted(() => {
  store.load(1);
  timer = window.setInterval(() => {
    if (document.visibilityState === 'visible') store.load(store.pagination.current_page);
  }, 5000);
});
onUnmounted(() => window.clearInterval(timer));
</script>

<style scoped>
.table-responsive table { min-width: 48rem; }
</style>
