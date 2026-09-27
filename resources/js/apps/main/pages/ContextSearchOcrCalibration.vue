<template>
  <main class="container-fluid py-3 ocr-calibration">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
      <div><h1 class="h3">{{ $t('pool.ocr-calibration-title') }}</h1><p class="text-muted">{{ $t('pool.ocr-calibration-intro') }}</p></div>
      <router-link class="btn btn-outline-secondary" :to="{name: 'context-search-evaluation-datasets'}">{{ $t('pool.ai-datasets') }}</router-link>
    </div>
    <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
    <div class="alert alert-warning">{{ $t('pool.ocr-calibration-dev-only') }}</div>
    <section class="card mb-3"><div class="card-body">
      <h2 class="h5">{{ $t('pool.ocr-calibration-new-run') }}</h2>
      <div class="row g-2 align-items-end">
        <div class="col-lg-5"><label class="form-label" for="ocr-dataset">{{ $t('pool.ocr-calibration-dataset') }}</label><select id="ocr-dataset" v-model="datasetId" class="form-select"><option value="">{{ $t('pool.ocr-calibration-choose-dataset') }}</option><option v-for="dataset in datasets" :key="dataset.id" :value="dataset.id">{{ dataset.title || dataset.id }} — {{ dataset.resource_count }} {{ $t('pool.ocr-calibration-resources') }}<template v-if="dataset.includes_private"> · {{ $t('pool.ocr-calibration-private') }}</template></option></select></div>
        <div class="col-sm-4 col-lg-2"><label class="form-label" for="ocr-sample-size">{{ $t('pool.ocr-calibration-sample-size') }}</label><input id="ocr-sample-size" v-model.number="sampleLimit" class="form-control" type="number" min="15" max="500"></div>
        <div class="col-sm-8 col-lg-3"><label class="form-label" for="ocr-run-title">{{ $t('pool.ocr-calibration-run-title') }}</label><input id="ocr-run-title" v-model="title" class="form-control" maxlength="255"></div>
        <div class="col-lg-2"><button class="btn btn-primary w-100" :disabled="!datasetId || busy" @click="createRun">{{ $t('pool.ocr-calibration-start') }}</button></div>
      </div>
    </div></section>
    <section class="row g-3">
      <aside class="col-xl-3"><div class="card"><div class="card-header d-flex justify-content-between"><strong>{{ $t('pool.ocr-calibration-runs') }}</strong><button class="btn btn-sm btn-outline-secondary" @click="load">{{ $t('pool.ocr-calibration-refresh') }}</button></div><div class="list-group list-group-flush"><button v-for="item in runs" :key="item.id" class="list-group-item list-group-item-action text-start" :class="{'active': run?.id === item.id}" @click="selectRun(item.id)"><span class="d-block fw-semibold">{{ item.title || item.id }}</span><small>{{ status(item.status) }} · {{ item.processed_pages }}/{{ item.total_pages }} · {{ item.reviewed_pages }} {{ $t('pool.ocr-calibration-reviewed') }}</small></button><p v-if="!runs.length" class="p-3 text-muted mb-0">{{ $t('pool.ocr-calibration-no-runs') }}</p></div></div></aside>
      <div class="col-xl-9" v-if="run">
        <section class="card mb-3"><div class="card-body d-flex justify-content-between flex-wrap gap-2"><div><h2 class="h5 mb-1">{{ run.title || run.id }}</h2><div class="text-muted small">{{ run.ocr_profile?.profile_id }} · {{ run.ocr_profile?.languages }} · {{ run.ocr_profile?.render_dpi }} dpi · PSM {{ run.ocr_profile?.page_segmentation_mode }} · {{ run.ocr_profile?.tesseract_version }}</div></div><button class="btn btn-outline-primary" @click="evaluate" :disabled="busy || run.processed_pages < run.total_pages">{{ $t('pool.ocr-calibration-evaluate') }}</button></div><div class="progress rounded-0" role="progressbar" :aria-valuenow="run.processed_pages" :aria-valuemax="run.total_pages"><div class="progress-bar" :style="{width: `${run.total_pages ? run.processed_pages / run.total_pages * 100 : 0}%`}"></div></div></section>
        <section v-if="run.sweep_results" class="card mb-3"><div class="card-body"><h2 class="h5">{{ $t('pool.ocr-calibration-results') }}</h2><p>{{ $t('pool.ocr-calibration-recommendation', {threshold: run.sweep_results.recommended_threshold ?? '—'}) }}</p><p v-if="run.sweep_results.holdout">{{ $t('pool.ocr-calibration-holdout', {precision: percent(run.sweep_results.holdout.usable_precision), coverage: percent(run.sweep_results.holdout.coverage), cer: percent(run.sweep_results.holdout.character_error_rate), wer: percent(run.sweep_results.holdout.word_error_rate)}) }}</p><div class="table-responsive"><table class="table table-sm"><thead><tr><th>{{ $t('pool.ocr-calibration-threshold') }}</th><th>{{ $t('pool.ocr-calibration-coverage') }}</th><th>{{ $t('pool.ocr-calibration-precision') }}</th><th>CER</th><th>WER</th></tr></thead><tbody><tr v-for="score in run.sweep_results.threshold_sweep" :key="score.threshold" :class="{'table-success': score.threshold === run.sweep_results.recommended_threshold}"><td>{{ percent(score.threshold) }}</td><td>{{ percent(score.coverage) }}</td><td>{{ percent(score.usable_precision) }}</td><td>{{ percent(score.character_error_rate) }}</td><td>{{ percent(score.word_error_rate) }}</td></tr></tbody></table></div><button v-if="run.status === 'evaluated'" class="btn btn-success" @click="approve" :disabled="busy">{{ $t('pool.ocr-calibration-approve') }}</button><div v-if="run.approved_profile_hash" class="alert alert-success mt-3 mb-0"><strong>{{ $t('pool.ocr-calibration-profile-approved') }}</strong><div class="small font-monospace">SHA-256: {{ run.approved_profile_hash }}</div><pre class="small mt-2 mb-0">{{ JSON.stringify(run.approved_profile, null, 2) }}</pre><label class="form-label mt-3">{{ $t('pool.ocr-calibration-env-settings') }}</label><pre class="small mb-0">{{ approvedEnv }}</pre></div></div></section>
        <section><h2 class="h5">{{ $t('pool.ocr-calibration-pages') }} ({{ run.pages.length }})</h2><article v-for="page in run.pages" :key="page.id" class="card mb-3" :class="{'border-warning': page.split === 'holdout'}"><div class="card-header d-flex justify-content-between flex-wrap gap-2"><span>Resource {{ page.resource_id }} · {{ $t('pool.ocr-calibration-page') }} {{ page.page_number }} · {{ page.split === 'holdout' ? $t('pool.ocr-calibration-holdout-set') : $t('pool.ocr-calibration-calibration-set') }}</span><span>{{ page.status }}<template v-if="page.metrics"> · {{ $t('pool.ocr-calibration-confidence') }} {{ percent(page.metrics.mean_confidence) }} · {{ page.metrics.recognized_word_count }} {{ $t('pool.ocr-calibration-words') }}</template></span></div><div class="card-body"><div class="row g-3"><div class="col-lg-5"><div class="ocr-preview border bg-light"><img class="img-fluid" :key="preview(page)" :src="preview(page)" :alt="$t('pool.ocr-calibration-page-preview')" v-image-queue.hide @q-queued="page.previewLoadState = 'queued'" @q-loading="page.previewLoadState = 'loading'" @q-loaded="page.previewLoadState = 'loaded'" @q-error="page.previewLoadState = 'error'"><div v-if="page.previewLoadState === 'loading'" class="ocr-preview-indicator"><materialpool-spinner size="lg"/></div><div v-else-if="!page.previewLoadState || page.previewLoadState === 'queued'" class="ocr-preview-indicator" :title="$t('pool.Preview-waiting-in-queue')"><history-icon aria-hidden="true"/><span class="visually-hidden">{{ $t('pool.Preview-waiting-in-queue') }}</span></div><span v-else-if="page.previewLoadState === 'error'" class="ocr-preview-indicator">{{ $t('pool.ocr-calibration-page-preview') }}</span></div></div><div class="col-lg-7"><label class="form-label">{{ $t('pool.ocr-calibration-ocr-output') }}</label><pre class="ocr-text">{{ ocrOutput(page, $t) }}</pre><div class="row g-2"><div class="col-md-5"><label class="form-label" :for="`label-${page.id}`">{{ $t('pool.ocr-calibration-quality-label') }}</label><select :id="`label-${page.id}`" v-model="page.quality_label" class="form-select"><option value="">—</option><option v-for="label in labels" :key="label" :value="label">{{ $t(`pool.ocr-calibration-label-${label}`) }}</option></select></div><div class="col-md-7"><label class="form-label" :for="`reference-${page.id}`">{{ $t('pool.ocr-calibration-reference') }}</label><textarea :id="`reference-${page.id}`" v-model="page.reference_text" class="form-control" rows="4" maxlength="30000"></textarea></div></div><label class="form-label mt-2" :for="`note-${page.id}`">{{ $t('pool.ocr-calibration-note') }}</label><div class="d-flex gap-2"><input :id="`note-${page.id}`" v-model="page.review_note" class="form-control" maxlength="2000"><button class="btn btn-outline-primary flex-shrink-0" :disabled="busy || page.status !== 'processed' || !page.quality_label" @click="saveReview(page)">{{ $t('pool.ocr-calibration-save-review') }}</button></div></div></div></div></article></section>
      </div>
      <div v-else class="col-xl-9"><div class="alert alert-info">{{ $t('pool.ocr-calibration-select-run') }}</div></div>
    </section>
  </main>
</template>

<script>
import axios from 'axios';
import {pdfPreviewImageForPageLarge} from '@/components/serverRoutes';
import {ocrOutput} from './ocrCalibrationOutput';
import MaterialpoolSpinner from '@/components/spinner/materialpool-spinner.vue';
import HistoryIcon from '@primer/octicons/build/svg/history.svg';

export default {
  name: 'ContextSearchOcrCalibration',
  components: {MaterialpoolSpinner, HistoryIcon},
  data: () => ({datasets: [], runs: [], run: null, datasetId: '', sampleLimit: 50, title: '', busy: false, error: '', labels: ['usable', 'unusable', 'uncertain', 'handwriting', 'blank']}),
  computed: {
    approvedEnv() {
      const profile = this.run?.approved_profile || {};
      const settings = [
        `CONTEXT_SEARCH_OCR_QUALITY_PROFILE=${profile.profile_id || ''}`,
        `CONTEXT_SEARCH_OCR_MINIMUM_MEAN_CONFIDENCE=${profile.minimum_mean_confidence ?? ''}`,
        `CONTEXT_SEARCH_OCR_MINIMUM_RECOGNIZED_WORDS=${profile.minimum_recognized_words ?? ''}`,
        `CONTEXT_SEARCH_OCR_MINIMUM_ALPHANUMERIC_RATIO=${profile.minimum_alphanumeric_ratio ?? ''}`,
        `CONTEXT_SEARCH_OCR_MAXIMUM_REPLACEMENT_CHARACTER_RATIO=${profile.maximum_replacement_character_ratio ?? ''}`,
        `CONTEXT_SEARCH_OCR_RENDER_DPI=${profile.render_dpi ?? ''}`,
        `CONTEXT_SEARCH_OCR_PSM=${profile.page_segmentation_mode ?? ''}`,
        `CONTEXT_SEARCH_OCR_ENGINE_VERSION=${profile.tesseract_version || ''}`,
      ];
      if (profile.max_image_pixels != null) settings.push(`CONTEXT_SEARCH_OCR_MAX_IMAGE_PIXELS=${profile.max_image_pixels}`);
      if (profile.languages) settings.push(`CONTEXT_SEARCH_OCR_LANGUAGES=${profile.languages}`);
      return settings.join('\n');
    },
  },
  async mounted() { await this.load(); },
  methods: {
    ocrOutput,
    async load() { try { const {data} = await axios.get('/api/v2/admin/context-search/ocr-calibration'); this.datasets = data.datasets; this.runs = data.runs; if (this.run) await this.selectRun(this.run.id); } catch (error) { this.error = error.response?.data?.message || error.message; } },
    async createRun() { this.busy = true; this.error = ''; try { const {data} = await axios.post('/api/v2/admin/context-search/ocr-calibration/runs', {dataset_id: this.datasetId, sample_limit: this.sampleLimit, title: this.title || null}); this.runs.unshift(data.run); await this.selectRun(data.run.id); } catch (error) { this.error = error.response?.data?.message || error.message; } finally { this.busy = false; } },
    async selectRun(id) { this.error = ''; try { const {data} = await axios.get(`/api/v2/admin/context-search/ocr-calibration/runs/${id}`); this.run = data.run; const index = this.runs.findIndex(item => item.id === id); if (index >= 0) this.runs.splice(index, 1, {...this.runs[index], ...data.run}); } catch (error) { this.error = error.response?.data?.message || error.message; } },
    async saveReview(page) { this.busy = true; try { const {data} = await axios.put(`/api/v2/admin/context-search/ocr-calibration/runs/${this.run.id}/pages/${page.id}`, {quality_label: page.quality_label, reference_text: page.reference_text, review_note: page.review_note}); this.run = data.run; } catch (error) { this.error = error.response?.data?.message || error.message; } finally { this.busy = false; } },
    async evaluate() { this.busy = true; try { await axios.post(`/api/v2/admin/context-search/ocr-calibration/runs/${this.run.id}/evaluate`); await this.selectRun(this.run.id); } catch (error) { this.error = error.response?.data?.message || error.message; } finally { this.busy = false; } },
    async approve() { this.busy = true; try { await axios.post(`/api/v2/admin/context-search/ocr-calibration/runs/${this.run.id}/approve`); await this.selectRun(this.run.id); await this.load(); } catch (error) { this.error = error.response?.data?.message || error.message; } finally { this.busy = false; } },
    preview(page) { return pdfPreviewImageForPageLarge({id: page.resource_id}, page.page_number); },
    status(value) { return this.$t(`pool.ocr-calibration-status-${value}`); },
    percent(value) { return value === null || value === undefined ? '—' : `${(Number(value) * 100).toFixed(1)} %`; },
  },
};
</script>

<style scoped>
.ocr-preview { position: relative; min-height: 12rem; }
.ocr-preview-indicator { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: var(--bs-primary); pointer-events: none; }
.ocr-preview-indicator :deep(svg) { width: 2.5rem; height: 2.5rem; fill: currentColor; }
.ocr-text { min-height: 7rem; max-height: 14rem; overflow: auto; white-space: pre-wrap; overflow-wrap: anywhere; background: var(--bs-light); border: 1px solid var(--bs-border-color); padding: .75rem; font-size: .9rem; }
</style>
