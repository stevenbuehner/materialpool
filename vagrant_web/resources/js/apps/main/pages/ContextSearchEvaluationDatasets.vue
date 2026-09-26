<template>
  <main class="container-fluid context-search-curation">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <div>
        <h1 class="h3 mb-1">{{ $t('pool.ai-datasets') }}</h1>
        <p class="text-muted mb-0">{{ $t('pool.ai-datasets-intro') }}</p>
      </div>
      <router-link :to="{name: 'material'}" class="btn btn-outline-secondary">{{ $t('pool.back-to-materials') }}</router-link>
    </div>

    <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>

    <section class="mb-4" :aria-label="$t('pool.ai-dataset-overview')">
      <div class="d-flex flex-wrap gap-2 align-items-stretch">
        <article v-for="dataset in datasets" :key="dataset.id" class="dataset-card card" :class="purposeClass(dataset.purpose)">
          <div class="card-body py-2">
            <button class="dataset-badge btn btn-sm" :class="purposeClass(dataset.purpose)" type="button" @click="activeDatasetId = dataset.id" :aria-describedby="`dataset-progress-${dataset.id}`">
              <span aria-hidden="true">{{ purposeIcon(dataset.purpose) }}</span>
              <span>{{ purposeLabel(dataset.purpose) }}</span>
              <span class="visually-hidden">: {{ dataset.status }}</span>
            </button>
            <span v-if="dataset.title" class="ms-1 small">{{ dataset.title }}</span>
            <div :id="`dataset-progress-${dataset.id}`" class="dataset-tooltip" role="tooltip">
              <strong>{{ purposeLabel(dataset.purpose) }}: {{ statusLabel(dataset.status) }}</strong>
              <div>{{ $t('pool.ai-dataset-progress', {materials: dataset.material_count, materialTarget: dataset.target_material_count, resources: dataset.resource_count, resourceTarget: dataset.target_resource_count}) }}</div>
              <div class="progress mt-1" aria-hidden="true"><div class="progress-bar" :style="{width: `${progress(dataset)}%`}"></div></div>
              <div class="small">{{ $t('pool.ai-dataset-remaining', {materials: dataset.materials_remaining, resources: dataset.resources_remaining}) }}</div>
              <ul v-if="Object.keys(dataset.quotas).length" class="mb-0 ps-3 small">
                <li v-for="(quota, name) in dataset.quotas" :key="name">{{ quotaLabel(name) }}: {{ quota.actual }}/{{ quota.target }} ({{ quota.remaining }} {{ $t('pool.ai-dataset-open') }})</li>
              </ul>
            </div>
            <button class="btn btn-link btn-sm p-0 ms-2" type="button" @click="activeDatasetId = dataset.id">{{ dataset.status === 'draft' || dataset.status === 'ready' ? $t('pool.ai-dataset-select') : $t('pool.ai-dataset-view') }}</button>
          </div>
        </article>
        <button v-for="purpose in availablePurposes" :key="purpose" class="btn btn-outline-primary" type="button" @click="createDataset(purpose)">
          {{ purposeIcon(purpose) }} {{ $t('pool.ai-dataset-create', {purpose: purposeLabel(purpose)}) }}
        </button>
      </div>
    </section>

    <section v-if="activeDataset" class="card mb-4">
      <div class="card-body">
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
          <div>
            <h2 class="h5 mb-1">{{ purposeIcon(activeDataset.purpose) }} {{ purposeLabel(activeDataset.purpose) }}</h2>
            <p class="mb-0 text-muted">{{ $t('pool.ai-dataset-selected-help') }}</p>
          </div>
          <button v-if="activeDataset.status === 'ready'" class="btn btn-warning" type="button" @click="freezeDataset" :disabled="saving">{{ $t('pool.ai-dataset-freeze') }}</button>
        </div>
        <div class="row g-2 mt-2">
          <div class="col-md-4"><label class="form-label" for="private-reason">{{ $t('pool.ai-dataset-private-reason') }}</label><input id="private-reason" v-model="privateReason" class="form-control" :disabled="!includePrivate || !isMutable"></div>
          <div class="col-md-4 d-flex align-items-end"><div class="form-check"><input id="include-private" v-model="includePrivate" class="form-check-input" type="checkbox" :disabled="!isMutable"><label for="include-private" class="form-check-label">{{ $t('pool.ai-dataset-include-private') }}</label></div></div>
          <div class="col-md-4 d-flex align-items-end justify-content-md-end gap-2"><button class="btn btn-outline-primary" type="button" @click="previewSelection" :disabled="!isMutable || !hasSelection || previewing">{{ $t('pool.ai-dataset-preview') }}</button><button class="btn btn-primary" type="button" @click="assignSelection" :disabled="!isMutable || !hasSelection || saving || hasConflicts">{{ $t('pool.ai-dataset-assign') }}</button></div>
        </div>
        <div v-if="preview" class="alert mt-3 mb-0" :class="hasConflicts ? 'alert-warning' : 'alert-info'">
          {{ $t('pool.ai-dataset-preview-result', {materials: preview.counts.materials, resources: preview.counts.resources, excluded: preview.excluded_resource_count}) }}
          <span v-if="hasConflicts"> {{ $t('pool.ai-dataset-preview-conflicts', {count: preview.conflicts.length}) }}</span>
        </div>
      </div>
    </section>

    <section class="card">
      <div class="card-body">
        <div class="row g-2 mb-3">
          <div class="col-md-5"><label class="visually-hidden" for="candidate-search">{{ $t('pool.Search') }}</label><input id="candidate-search" v-model.trim="filters.search" class="form-control" :placeholder="$t('pool.ai-dataset-search-placeholder')" @keyup.enter="loadCandidates(1)"></div>
          <div class="col-sm-4 col-md-2"><select v-model="filters.type" class="form-select" @change="loadCandidates(1)"><option value="all">{{ $t('pool.ai-dataset-all-types') }}</option><option value="pdf">PDF</option><option value="text">Text</option></select></div>
          <div class="col-sm-4 col-md-2"><select v-model="filters.visibility" class="form-select" @change="loadCandidates(1)"><option value="all">{{ $t('pool.ai-dataset-all-visibility') }}</option><option value="public">{{ $t('pool.ai-dataset-public') }}</option><option value="private">{{ $t('pool.ai-dataset-private') }}</option></select></div>
          <div class="col-sm-4 col-md-3"><button class="btn btn-outline-secondary w-100" type="button" @click="loadCandidates(1)">{{ $t('pool.Search') }}</button></div>
        </div>
        <p class="small text-muted">{{ $t('pool.ai-dataset-closure-notice') }}</p>
        <div v-if="loading" class="text-muted">{{ $t('pool.Loading-resource') }}</div>
        <div v-else class="list-group">
          <article v-for="material in candidates.data" :key="material.id" class="list-group-item">
            <div class="d-flex gap-2 align-items-start">
              <input :id="`material-${material.id}`" v-model="selectedMaterialIds" :value="material.id" class="form-check-input mt-1" type="checkbox" :disabled="!isMutable || Boolean(material.assignment)" @change="clearPreview">
              <div class="flex-grow-1"><label :for="`material-${material.id}`" class="fw-semibold">{{ material.title }}</label>
                <dataset-assignment-badge v-if="material.assignment" :assignment="material.assignment" />
                <p v-if="material.description" class="small mb-1 text-muted">{{ material.description }}</p>
                <div v-for="resource in material.resources" :key="resource.id" class="form-check small ms-1">
                  <input :id="`resource-${resource.id}`" v-model="selectedResourceIds" :value="resource.id" class="form-check-input" type="checkbox" :disabled="!isMutable || Boolean(resource.assignment)" @change="clearPreview">
                  <label :for="`resource-${resource.id}`" class="form-check-label">{{ resource.type.toUpperCase() }} · {{ resource.notes || $t('pool.Resource') }} <span class="text-muted">({{ resource.is_public ? $t('pool.ai-dataset-public') : $t('pool.ai-dataset-private') }})</span></label>
                  <dataset-assignment-badge v-if="resource.assignment" :assignment="resource.assignment" />
                </div>
              </div>
            </div>
          </article>
        </div>
        <nav v-if="candidates.last_page > 1" class="mt-3" :aria-label="$t('pool.ai-dataset-pages')"><div class="btn-group"><button class="btn btn-outline-secondary" type="button" :disabled="candidates.current_page <= 1" @click="loadCandidates(candidates.current_page - 1)">‹</button><span class="btn btn-outline-secondary disabled">{{ candidates.current_page }} / {{ candidates.last_page }}</span><button class="btn btn-outline-secondary" type="button" :disabled="candidates.current_page >= candidates.last_page" @click="loadCandidates(candidates.current_page + 1)">›</button></div></nav>
      </div>
    </section>
  </main>
</template>

<script>
import axios from '../axiosInstance';
import {useGeneralStore} from '../stores/general';

const DatasetAssignmentBadge = {
  props: {assignment: {type: Object, required: true}},
  methods: {icon(purpose) { return ({calibration: '◉', acceptance: '✓', ocr: '⌁', load: '◌', capacity: '▣'})[purpose] || '•'; }},
  template: '<span class="badge text-bg-secondary ms-1"><span aria-hidden="true">{{ icon(assignment.purpose) }}</span> {{ assignment.title || assignment.purpose }}</span>',
};

export default {
  name: 'ContextSearchEvaluationDatasets', components: {DatasetAssignmentBadge},
  data() { return {datasets: [], candidates: {data: [], current_page: 1, last_page: 1}, filters: {search: '', type: 'all', visibility: 'all'}, activeDatasetId: null, selectedMaterialIds: [], selectedResourceIds: [], includePrivate: false, privateReason: '', preview: null, loading: false, previewing: false, saving: false, error: ''}; },
  computed: {
    activeDataset() { return this.datasets.find(dataset => dataset.id === this.activeDatasetId) || null; },
    isMutable() { return ['draft', 'ready'].includes(this.activeDataset?.status); },
    hasSelection() { return this.selectedMaterialIds.length > 0 || this.selectedResourceIds.length > 0; },
    hasConflicts() { return (this.preview?.conflicts?.length || 0) > 0; },
    availablePurposes() { return ['calibration', 'acceptance', 'ocr', 'load', 'capacity'].filter(purpose => !this.datasets.some(dataset => dataset.purpose === purpose && ['draft', 'ready'].includes(dataset.status))); },
  },
  watch: {activeDataset(dataset) { this.includePrivate = Boolean(dataset?.includes_private); this.privateReason = dataset?.private_reason || ''; this.clearSelection(); }},
  async created() { const user = await useGeneralStore().currentUser(); if (!user?.is_admin) return this.$router.replace({name: 'material'}); await Promise.all([this.loadDatasets(), this.loadCandidates(1)]); },
  methods: {
    purposeIcon(purpose) { return ({calibration: '◉', acceptance: '✓', ocr: '⌁', load: '◌', capacity: '▣'})[purpose] || '•'; },
    purposeLabel(purpose) { return this.$t(`pool.ai-dataset-purpose-${purpose}`); },
    purposeClass(purpose) { return `dataset-${purpose}`; },
    statusLabel(status) { return this.$t(`pool.ai-dataset-status-${status}`); },
    quotaLabel(name) { return this.$t(`pool.ai-dataset-quota-${name}`); },
    progress(dataset) { const target = Math.max(1, dataset.target_resource_count || 1); return Math.min(100, Math.round((dataset.resource_count / target) * 100)); },
    async loadDatasets() { try { const {data} = await axios.get('/api/v2/admin/context-search/datasets'); this.datasets = data.datasets; if (!this.activeDatasetId && this.datasets.length) this.activeDatasetId = this.datasets.find(dataset => dataset.status === 'draft')?.id || this.datasets[0].id; } catch (error) { this.error = error.response?.data?.message || error.message; } },
    async loadCandidates(page) { this.loading = true; try { const {data} = await axios.get('/api/v2/admin/context-search/datasets/candidates', {params: {...this.filters, page}}); this.candidates = data; } catch (error) { this.error = error.response?.data?.message || error.message; } finally { this.loading = false; } },
    async createDataset(purpose) { try { const {data} = await axios.post('/api/v2/admin/context-search/datasets', {purpose}); this.datasets.push(data.dataset); this.activeDatasetId = data.dataset.id; } catch (error) { this.error = error.response?.data?.message || error.message; } },
    clearPreview() { this.preview = null; }, clearSelection() { this.selectedMaterialIds = []; this.selectedResourceIds = []; this.preview = null; },
    async previewSelection() { this.previewing = true; this.error = ''; try { const {data} = await axios.post('/api/v2/admin/context-search/datasets/preview', {material_ids: this.selectedMaterialIds, resource_ids: this.selectedResourceIds}); this.preview = data.preview; this.selectedMaterialIds = data.preview.material_ids; this.selectedResourceIds = data.preview.resource_ids; } catch (error) { this.error = error.response?.data?.message || error.message; } finally { this.previewing = false; } },
    async assignSelection() { if (!this.preview) await this.previewSelection(); if (this.hasConflicts || !this.preview) return; this.saving = true; this.error = ''; try { const {data} = await axios.post(`/api/v2/admin/context-search/datasets/${this.activeDataset.id}/assign`, {material_ids: this.selectedMaterialIds, resource_ids: this.selectedResourceIds, expected_version: this.activeDataset.version, include_private: this.includePrivate, private_reason: this.privateReason || null}); this.replaceDataset(data.dataset); this.clearSelection(); await this.loadCandidates(this.candidates.current_page); } catch (error) { this.error = error.response?.data?.message || error.message; await this.loadDatasets(); } finally { this.saving = false; } },
    async freezeDataset() { this.saving = true; try { const {data} = await axios.post(`/api/v2/admin/context-search/datasets/${this.activeDataset.id}/freeze`); this.replaceDataset(data.dataset); await this.loadCandidates(this.candidates.current_page); } catch (error) { this.error = error.response?.data?.message || error.message; } finally { this.saving = false; } },
    replaceDataset(dataset) { const index = this.datasets.findIndex(item => item.id === dataset.id); if (index >= 0) this.datasets.splice(index, 1, dataset); },
  },
};
</script>

<style scoped>
.dataset-card { min-width: 15rem; position: relative; border-left-width: .35rem; }
.dataset-badge { font-weight: 700; }
.dataset-tooltip { display: none; position: absolute; z-index: 5; top: calc(100% + .25rem); left: 0; width: 21rem; padding: .75rem; border-radius: .25rem; background: #212529; color: #fff; box-shadow: 0 .25rem .75rem rgba(0,0,0,.25); }
.dataset-card:hover .dataset-tooltip, .dataset-card:focus-within .dataset-tooltip { display: block; }
.dataset-calibration { border-color: #0d6efd; }.dataset-acceptance { border-color: #198754; }.dataset-ocr { border-color: #6f42c1; }.dataset-load { border-color: #fd7e14; }.dataset-capacity { border-color: #0dcaf0; }
.dataset-badge.dataset-calibration { color:#084298; }.dataset-badge.dataset-acceptance { color:#0f5132; }.dataset-badge.dataset-ocr { color:#432874; }.dataset-badge.dataset-load { color:#984c0c; }.dataset-badge.dataset-capacity { color:#055160; }
@media (max-width: 575.98px) { .dataset-card { width: 100%; }.dataset-tooltip { left: auto; right: 0; max-width: 100%; } }
</style>
