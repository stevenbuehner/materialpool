<template>
  <main class="container-fluid py-3 ocr-calibration" @wheel.passive="clearScrollAnchor" @touchstart.passive="clearScrollAnchor">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
      <div>
        <h1 class="h3">{{ $t('pool.ocr-calibration-title') }}</h1>
        <p class="ocr-calibration-intro mt-2 mb-0 p-3 border-start border-3 border-primary bg-light rounded-end">{{ $t('pool.ocr-calibration-intro') }}</p>
      </div>
      <router-link class="btn btn-outline-secondary" :to="{name: 'context-search-evaluation-datasets'}">
        {{ $t('pool.ai-datasets') }}
      </router-link>
    </div>
    <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
    <div v-if="warningVisible" class="alert alert-warning alert-dismissible" role="alert">
      {{ $t('pool.ocr-calibration-dev-only') }}
      <button type="button" class="btn-close" :aria-label="$t('pool.close')" @click="warningVisible = false"></button>
    </div>
    <section class="card mb-3">
      <div class="card-body">
        <h2 class="h5">{{ $t('pool.ocr-calibration-new-run') }}</h2>
        <div class="row g-2 align-items-end">
          <div class="col-lg-5">
            <label class="form-label" for="ocr-dataset">{{ $t('pool.ocr-calibration-dataset') }}</label>
            <select id="ocr-dataset" v-model="datasetId" class="form-select">
              <option value="">{{ $t('pool.ocr-calibration-choose-dataset') }}</option>
              <option v-for="dataset in datasets" :key="dataset.id" :value="dataset.id">
                {{ dataset.title || dataset.id }} — {{ dataset.resource_count }} {{ $t('pool.ocr-calibration-resources') }}<template v-if="dataset.includes_private"> · {{ $t('pool.ocr-calibration-private') }}</template>
              </option>
            </select>
          </div>
          <div class="col-sm-4 col-lg-2">
            <label class="form-label" for="ocr-sample-size">{{ $t('pool.ocr-calibration-sample-size') }}</label>
            <input id="ocr-sample-size" v-model.number="sampleLimit" class="form-control" type="number" min="15" max="500">
          </div>
          <div class="col-sm-8 col-lg-3">
            <label class="form-label" for="ocr-run-title">{{ $t('pool.ocr-calibration-run-title') }}</label>
            <input id="ocr-run-title" v-model="title" class="form-control" maxlength="255">
          </div>
          <div class="col-lg-2">
            <button class="btn btn-primary w-100" :disabled="!datasetId || busy" @click="createRun">
              {{ $t('pool.ocr-calibration-start') }}
            </button>
          </div>
        </div>
      </div>
    </section>
    <section class="row g-3">
      <aside class="col-xl-3">
        <div class="card">
          <div class="card-header d-flex justify-content-between">
            <strong>{{ $t('pool.ocr-calibration-runs') }}</strong>
            <button class="btn btn-sm btn-outline-secondary" @click="load">
              {{ $t('pool.ocr-calibration-refresh') }}
            </button>
          </div>
          <div class="list-group list-group-flush">
            <button v-for="item in runs" :key="item.id" class="list-group-item list-group-item-action text-start" :class="{'active': run?.id === item.id}" @click="selectRun(item.id)">
              <span class="d-block fw-semibold">{{ item.title || item.id }}</span>
              <small>{{ status(item.status) }} · {{ item.processed_pages }}/{{ item.total_pages }} · {{ item.reviewed_pages }} {{ $t('pool.ocr-calibration-reviewed') }}</small>
            </button>
            <p v-if="!runs.length" class="p-3 text-muted mb-0">{{ $t('pool.ocr-calibration-no-runs') }}</p>
          </div>
        </div>
      </aside>
      <div class="col-xl-9" v-if="run">
        <section class="card mb-3"><div class="card-body d-flex justify-content-between flex-wrap gap-2"><div><h2 class="h5 mb-1">{{ run.title || run.id }}</h2><div class="text-muted small">{{ run.ocr_profile?.profile_id }} · {{ run.ocr_profile?.languages }} · {{ run.ocr_profile?.render_dpi }} dpi · PSM {{ run.ocr_profile?.page_segmentation_mode }} · {{ run.ocr_profile?.tesseract_version }}</div></div><button class="btn btn-outline-primary" @click="showEvaluateCommand" :disabled="busy || run.processed_pages < run.total_pages || run.status === 'approved'">{{ $t('pool.ocr-calibration-evaluate') }}</button></div><div class="progress rounded-0" role="progressbar" :aria-valuenow="run.processed_pages" :aria-valuemax="run.total_pages"><div class="progress-bar" :style="{width: `${run.total_pages ? run.processed_pages / run.total_pages * 100 : 0}%`}"></div></div></section>
        <section v-if="run.sweep_results" class="card mb-3"><div class="card-body"><h2 class="h5">{{ $t('pool.ocr-calibration-results') }}</h2><p>{{ $t('pool.ocr-calibration-recommendation', {threshold: run.sweep_results.recommended_threshold ?? '—'}) }}</p><p v-if="run.sweep_results.holdout">{{ $t('pool.ocr-calibration-holdout', {precision: percent(run.sweep_results.holdout.usable_precision), coverage: percent(run.sweep_results.holdout.coverage), cer: percent(run.sweep_results.holdout.character_error_rate), wer: percent(run.sweep_results.holdout.word_error_rate)}) }}</p><div class="table-responsive"><table class="table table-sm"><thead><tr><th>{{ $t('pool.ocr-calibration-threshold') }}</th><th>{{ $t('pool.ocr-calibration-coverage') }}</th><th>{{ $t('pool.ocr-calibration-precision') }}</th><th>CER</th><th>WER</th></tr></thead><tbody><tr v-for="score in run.sweep_results.threshold_sweep" :key="score.threshold" :class="{'table-success': score.threshold === run.sweep_results.recommended_threshold}"><td>{{ percent(score.threshold) }}</td><td>{{ percent(score.coverage) }}</td><td>{{ percent(score.usable_precision) }}</td><td>{{ percent(score.character_error_rate) }}</td><td>{{ percent(score.word_error_rate) }}</td></tr></tbody></table></div><button v-if="run.status === 'evaluated'" class="btn btn-success" @click="approve" :disabled="busy">{{ $t('pool.ocr-calibration-approve') }}</button><div v-if="run.approved_profile_hash" class="alert alert-success mt-3 mb-0"><strong>{{ $t('pool.ocr-calibration-profile-approved') }}</strong><div class="small font-monospace">SHA-256: {{ run.approved_profile_hash }}</div><pre class="small mt-2 mb-0">{{ JSON.stringify(run.approved_profile, null, 2) }}</pre><label class="form-label mt-3">{{ $t('pool.ocr-calibration-env-settings') }}</label><pre class="small mb-0">{{ approvedEnv }}</pre></div></div></section>
        <section>
          <h2 class="h5">{{ $t('pool.ocr-calibration-pages') }} ({{ reviewedPageCount }}/{{ run.pages.length }})</h2>
          <div class="card mb-3"><div class="card-body">
            <div class="row g-2 align-items-end">
              <div class="col-sm-6 col-lg-3"><label class="form-label" for="ocr-filter-quality">{{ $t('pool.ocr-calibration-filter-quality') }}</label><select id="ocr-filter-quality" v-model="qualityFilter" class="form-select"><option value="">{{ $t('pool.ocr-calibration-filter-all') }}</option><option v-for="label in labels" :key="label" :value="label">{{ $t(`pool.ocr-calibration-label-${label}`) }}</option></select></div>
              <div class="col-sm-6 col-lg-3"><label class="form-label" for="ocr-filter-reviewed">{{ $t('pool.ocr-calibration-filter-review') }}</label><select id="ocr-filter-reviewed" v-model="reviewFilter" class="form-select"><option value="">{{ $t('pool.ocr-calibration-filter-all') }}</option><option value="reviewed">{{ $t('pool.ocr-calibration-filter-reviewed') }}</option><option value="unreviewed">{{ $t('pool.ocr-calibration-filter-unreviewed') }}</option></select></div>
              <div class="col-sm-6 col-lg-3"><label class="form-label" for="ocr-filter-reference">{{ $t('pool.ocr-calibration-filter-reference') }}</label><select id="ocr-filter-reference" v-model="referenceFilter" class="form-select"><option value="">{{ $t('pool.ocr-calibration-filter-all') }}</option><option value="with">{{ $t('pool.ocr-calibration-filter-with-reference') }}</option><option value="without">{{ $t('pool.ocr-calibration-filter-without-reference') }}</option></select></div>
              <div class="col-sm-6 col-lg-3"><label class="form-label" for="ocr-filter-confidence">{{ $t('pool.ocr-calibration-filter-confidence') }}</label><input id="ocr-filter-confidence" v-model="minimumConfidence" class="form-control" type="number" min="0" max="100" step="0.1" :class="{'is-invalid': !validMinimumConfidence}" :aria-invalid="!validMinimumConfidence"><div v-if="!validMinimumConfidence" class="invalid-feedback">{{ $t('pool.ocr-calibration-filter-confidence-invalid') }}</div></div>
            </div>
            <p class="small text-muted mb-0 mt-2" role="status">{{ $t('pool.ocr-calibration-filter-count', {visible: filteredPages.length, total: run.pages.length}) }}</p>
          </div></div>
          <p v-if="!filteredPages.length" class="text-muted">{{ $t('pool.ocr-calibration-filter-empty') }}</p>
          <article v-for="page in filteredPages" :id="`ocr-calibration-page-${page.id}`" :key="page.id" class="card mb-3" :class="{'border-warning': page.split === 'holdout'}"><div class="card-header d-flex justify-content-between flex-wrap gap-2"><span><router-link class="ocr-resource-link" :to="{name: 'resource-detail', params: {id: page.resource_id}}" target="_blank" rel="noopener noreferrer">Resource {{ page.resource_id }} · {{ $t('pool.ocr-calibration-page') }} {{ page.page_number }}</router-link> · {{ page.split === 'holdout' ? $t('pool.ocr-calibration-holdout-set') : $t('pool.ocr-calibration-calibration-set') }}</span><span>{{ page.status }}<template v-if="page.metrics"> · {{ $t('pool.ocr-calibration-confidence') }} {{ percent(page.metrics.mean_confidence) }} · {{ page.metrics.recognized_word_count }} {{ $t('pool.ocr-calibration-words') }}</template></span></div><div class="card-body"><div class="row g-3"><div class="col-lg-5"><div class="ocr-preview border bg-light" @mouseenter="updatePreviewZoom(page, $event)" @mousemove="updatePreviewZoom(page, $event)" @mouseleave="clearPreviewZoom"><img class="img-fluid ocr-preview-image" :key="preview(page)" :src="preview(page)" :alt="$t('pool.ocr-calibration-page-preview')" v-image-queue.hide @q-queued="page.previewLoadState = 'queued'" @q-loading="page.previewLoadState = 'loading'" @q-loaded="onPreviewLoaded(page)" @q-error="onPreviewLoadError(page)"><img v-if="zoomPageId === page.id" v-show="zoomLoadedPageId === page.id" class="ocr-preview-zoom" :src="previewLarge(page)" :style="previewZoomStyle()" alt="" aria-hidden="true" @load="onPreviewZoomLoaded(page, $event)" @error="onPreviewZoomError(page)"><div v-if="page.previewLoadState === 'loading'" class="ocr-preview-indicator"><materialpool-spinner size="lg"/></div><div v-else-if="!page.previewLoadState || page.previewLoadState === 'queued'" class="ocr-preview-indicator" :title="$t('pool.Preview-waiting-in-queue')"><history-icon aria-hidden="true"/><span class="visually-hidden">{{ $t('pool.Preview-waiting-in-queue') }}</span></div><span v-else-if="page.previewLoadState === 'error'" class="ocr-preview-indicator">{{ $t('pool.ocr-calibration-page-preview') }}</span></div></div><div class="col-lg-7"><label class="form-label">{{ $t('pool.ocr-calibration-ocr-output') }}</label><div class="ocr-text-wrapper"><pre class="ocr-text bg-light">{{ ocrOutput(page, $t) }}</pre><div class="ocr-copy-actions"><button class="ocr-copy-button" type="button" :disabled="!page.ocr_text" :title="$t(copiedPageId === page.id ? 'pool.ocr-calibration-copied' : 'pool.Copy')" :aria-label="$t(copiedPageId === page.id ? 'pool.ocr-calibration-copied' : 'pool.Copy')" @click="copyOcrText(page)"><check-icon v-if="copiedPageId === page.id" aria-hidden="true"/><copy-icon v-else aria-hidden="true"/></button><button class="ocr-copy-button" type="button" :disabled="!page.ocr_text" :title="$t('pool.ocr-calibration-copy-to-reference')" :aria-label="$t('pool.ocr-calibration-copy-to-reference')" @click="copyOcrTextToReference(page)"><reference-icon aria-hidden="true"/></button></div></div><div class="mb-2"><label class="form-label" :for="`reference-${page.id}`">{{ $t('pool.ocr-calibration-reference') }}</label><textarea :id="`reference-${page.id}`" ref="referenceFields" v-model="page.reference_text" class="form-control" rows="10" maxlength="30000"></textarea></div><div class="row g-2 align-items-end"><div class="col-md-4"><label class="form-label" :for="`label-${page.id}`">{{ $t('pool.ocr-calibration-quality-label') }}</label><select :id="`label-${page.id}`" v-model="page.quality_label" class="form-select"><option value="">—</option><option v-for="label in labels" :key="label" :value="label">{{ $t(`pool.ocr-calibration-label-${label}`) }}</option></select></div><div class="col-md-8"><label class="form-label" :for="`note-${page.id}`">{{ $t('pool.ocr-calibration-note') }}</label><div class="d-flex gap-2"><input :id="`note-${page.id}`" v-model="page.review_note" class="form-control" maxlength="2000"><button class="btn btn-outline-primary flex-shrink-0" :disabled="busy || page.status !== 'processed' || !page.quality_label" @click="saveReview(page)"><materialpool-spinner v-if="savingPageId === page.id" size="sm" variant="primary" class="me-1"/>{{ $t(page.reviewSaved ? 'pool.ocr-calibration-change-review' : 'pool.ocr-calibration-save-review') }}</button></div></div></div></div></div></div></article>
        </section>
      </div>
      <div v-else class="col-xl-9"><div class="alert alert-info">{{ $t('pool.ocr-calibration-select-run') }}</div></div>
    </section>
    <b-modal ref="evaluateCommandModal" hide-footer :title="$t('pool.ocr-calibration-evaluate-command-title')">
      <p>{{ $t('pool.ocr-calibration-evaluate-command-intro') }}</p>
      <label class="form-label" for="ocr-evaluate-command">{{ $t('pool.ocr-calibration-evaluate-command-label') }}</label>
      <div class="d-grid gap-2">
        <textarea id="ocr-evaluate-command" class="form-control font-monospace" rows="3" readonly :value="evaluateCommand" @focus="$event.target.select()"></textarea>
        <button type="button" class="btn btn-outline-primary" @click="copyEvaluateCommand">{{ $t(commandCopied ? 'pool.ocr-calibration-copied' : 'pool.ocr-calibration-evaluate-command-copy') }}</button>
      </div>
      <p class="text-muted small mt-3 mb-0">{{ $t('pool.ocr-calibration-evaluate-command-after') }}</p>
    </b-modal>
  </main>
</template>

<script>
import axios from 'axios';
import {pdfPreviewImageForPage, pdfPreviewImageForPageLarge} from '@/components/serverRoutes';
import {ocrOutput} from './ocrCalibrationOutput';
import {copyOcrTextToClipboard} from './ocrCalibrationClipboard';
import {filterOcrCalibrationPages, validConfidencePercent} from './ocrCalibrationFilters';
import {BModal} from '@/adapters/bootstrap';
import MaterialpoolSpinner from '@/components/spinner/materialpool-spinner.vue';
import HistoryIcon from '@primer/octicons/build/svg/history.svg';
import CheckIcon from '@primer/octicons/build/svg/check.svg';
import CopyIcon from '@icons/vendor/svg-icon/trimmed-svg/bootstrap/copy.svg';
import ReferenceIcon from '@icons/vendor/svg-icon/svg/material/description.svg';

export default {
  name: 'ContextSearchOcrCalibration',
  components: {BModal, MaterialpoolSpinner, HistoryIcon, CheckIcon, CopyIcon, ReferenceIcon},
  data: () => ({
    datasets: [],
    runs: [],
    run: null,
    zoomPageId: null,
    zoomLoadedPageId: null,
    zoomPosition: {x: 50, y: 50},
    zoomScale: 1,
    scrollAnchorPageId: null,
    scrollAnchorTop: null,
    warningVisible: true,
    datasetId: '',
    sampleLimit: 50,
    title: '',
    busy: false,
    savingPageId: null,
    copiedPageId: null,
    commandCopied: false,
    error: '',
    labels: ['usable', 'unusable', 'uncertain', 'handwriting', 'blank'],
    qualityFilter: '',
    reviewFilter: '',
    referenceFilter: '',
    minimumConfidence: '',
  }),
  computed: {
    evaluateCommand() {
      return this.run ? `./vendor/bin/sail artisan context-search:ocr-calibration:evaluate ${this.run.id}` : '';
    },
    validMinimumConfidence() {
      return validConfidencePercent(this.minimumConfidence);
    },
    filteredPages() {
      return filterOcrCalibrationPages(this.run?.pages || [], {
        quality: this.qualityFilter,
        review: this.reviewFilter,
        reference: this.referenceFilter,
        minimumConfidence: this.minimumConfidence,
      });
    },
    reviewedPageCount() {
      return this.run?.pages?.filter(page => page.reviewSaved).length || 0;
    },
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
  watch: {
    '$route.query.run'(id) {
      if (id) {
        this.fetchRun(String(id));
      } else {
        this.stopPolling();
        this.run = null;
      }
    },
  },
  async mounted() {
    await this.load();
  },
  beforeUnmount() {
    this.stopPolling();
    clearTimeout(this.copyResetTimer);
    clearTimeout(this.commandCopyResetTimer);
  },
  methods: {
    ocrOutput,
    async load() {
      try {
        const {data} = await axios.get('/api/v2/admin/context-search/ocr-calibration');
        this.datasets = data.datasets;
        this.runs = data.runs;
        if (this.$route.query.run) await this.fetchRun(String(this.$route.query.run));
      } catch (error) {
        this.error = error.response?.data?.message || error.message;
      }
    },
    async createRun() {
      this.busy = true;
      this.error = '';
      try {
        const {data} = await axios.post('/api/v2/admin/context-search/ocr-calibration/runs', {
          dataset_id: this.datasetId,
          sample_limit: this.sampleLimit,
          title: this.title || null,
        });
        this.runs.unshift(data.run);
        await this.selectRun(data.run.id);
      } catch (error) {
        this.error = error.response?.data?.message || error.message;
      } finally {
        this.busy = false;
      }
    },
    async selectRun(id) {
      if (String(this.$route.query.run || '') !== String(id)) {
        await this.$router.push({
          name: 'context-search-ocr-calibration',
          query: {...this.$route.query, run: String(id)},
        });
        return;
      }
      await this.fetchRun(String(id));
    },
    async fetchRun(id) {
      this.stopPolling();
      this.clearScrollAnchor();
      const version = this.pollVersion;
      this.error = '';
      try {
        const {data} = await axios.get(`/api/v2/admin/context-search/ocr-calibration/runs/${id}`);
        if (version !== this.pollVersion) return;
        this.run = data.run;
        for (const loadedPage of this.run.pages) this.markSavedReview(loadedPage);
        const index = this.runs.findIndex(item => item.id === id);
        if (index >= 0) this.runs.splice(index, 1, {...this.runs[index], ...data.run});
        await this.$nextTick();
        this.scrollToFirstUnreviewedPage();
        this.schedulePoll();
      } catch (error) {
        this.error = error.response?.data?.message || error.message;
      }
    },
    scrollToFirstUnreviewedPage() {
      const page = this.filteredPages.find(item => !item.reviewSaved);
      if (!page) return;
      const target = document.getElementById(`ocr-calibration-page-${page.id}`);
      if (!target) return;
      this.scrollAnchorPageId = page.id;
      const alignTarget = () => {
        if (this.scrollAnchorPageId !== page.id || !target.isConnected) return;
        target.scrollIntoView({behavior: 'instant', block: 'start'});
        this.scrollAnchorTop = target.getBoundingClientRect().top;
      };
      alignTarget();
      requestAnimationFrame(() => requestAnimationFrame(alignTarget));
    },
    clearScrollAnchor() {
      this.scrollAnchorPageId = null;
      this.scrollAnchorTop = null;
    },
    onPreviewLoaded(page) {
      page.previewLoadState = 'loaded';
      this.realignScrollAnchorAfterPreviewChange(page);
    },
    onPreviewLoadError(page) {
      page.previewLoadState = 'error';
      this.realignScrollAnchorAfterPreviewChange(page);
    },
    realignScrollAnchorAfterPreviewChange(page) {
      if (!this.scrollAnchorPageId || !this.run) return;
      const targetIndex = this.run.pages.findIndex(item => item.id === this.scrollAnchorPageId);
      const changedPageIndex = this.run.pages.findIndex(item => item.id === page.id);
      if (targetIndex < 0 || changedPageIndex < 0 || changedPageIndex >= targetIndex) return;
      this.$nextTick(() => requestAnimationFrame(() => {
        if (!this.scrollAnchorPageId) return;
        const target = document.getElementById(`ocr-calibration-page-${this.scrollAnchorPageId}`);
        if (!target) return this.clearScrollAnchor();
        const currentTop = target.getBoundingClientRect().top;
        const offset = currentTop - this.scrollAnchorTop;
        if (Math.abs(offset) > 1) window.scrollBy({top: offset, behavior: 'instant'});
        this.scrollAnchorTop = target.getBoundingClientRect().top;
        if (this.run.pages.slice(0, targetIndex).every(item => ['loaded', 'error'].includes(item.previewLoadState))) {
          this.clearScrollAnchor();
        }
      }));
    },
    // Der Versionszähler verwirft Antworten älterer Polling-Anfragen nach einem Laufwechsel.
    stopPolling() {
      clearTimeout(this.pollTimer);
      this.pollTimer = null;
      this.pollVersion = (this.pollVersion || 0) + 1;
    },
    schedulePoll() {
      if (this.run?.status === 'processing') {
        this.pollTimer = setTimeout(() => this.pollProgress(), 10000);
      }
    },
    async pollProgress() {
      const version = this.pollVersion;
      const runId = this.run?.id;
      if (!runId || this.run.status !== 'processing') return;
      try {
        const {data} = await axios.get('/api/v2/admin/context-search/ocr-calibration');
        if (version !== this.pollVersion || this.run?.id !== runId) return;
        const latest = data.runs.find(item => item.id === runId)
          || (await axios.get(`/api/v2/admin/context-search/ocr-calibration/runs/${runId}`)).data.run;
        if (version !== this.pollVersion || this.run?.id !== runId) return;
        if (latest) {
          Object.assign(this.run, {status: latest.status, processed_pages: latest.processed_pages, total_pages: latest.total_pages, reviewed_pages: latest.reviewed_pages});
          const index = this.runs.findIndex(item => item.id === runId);
          if (index >= 0) Object.assign(this.runs[index], latest);
          if (latest.status !== 'processing') {
            const {data: completed} = await axios.get(`/api/v2/admin/context-search/ocr-calibration/runs/${runId}`);
            if (version !== this.pollVersion || this.run?.id !== runId) return;
            for (const incoming of completed.run.pages) {
              const local = this.run.pages.find(page => page.id === incoming.id);
              if (local) Object.assign(local, {status: incoming.status, metrics: incoming.metrics, ocr_text: incoming.ocr_text});
            }
            return;
          }
        }
      } catch (error) {
        this.error = error.response?.data?.message || error.message;
      }
      if (version === this.pollVersion) this.schedulePoll();
    },
    async copyOcrText(page) {
      try {
        await copyOcrTextToClipboard(page.ocr_text);
        this.copiedPageId = page.id;
        clearTimeout(this.copyResetTimer);
        this.copyResetTimer = setTimeout(() => {
          this.copiedPageId = null;
        }, 3000);
      } catch (error) {
        this.error = this.$t('pool.ocr-calibration-copy-failed');
      }
    },
    copyOcrTextToReference(page) {
      page.reference_text = page.ocr_text || '';
      this.$nextTick(() => requestAnimationFrame(() => {
        const referenceFields = Array.isArray(this.$refs.referenceFields)
          ? this.$refs.referenceFields
          : [this.$refs.referenceFields];
        const referenceField = referenceFields.find(field => field?.id === `reference-${page.id}`);
        if (!referenceField?.isConnected) return;
        referenceField.focus({preventScroll: true});
        referenceField.setSelectionRange(0, 0);
      }));
    },
    async saveReview(page) {
      this.busy = true;
      this.savingPageId = page.id;
      try {
        const {data} = await axios.put(
          `/api/v2/admin/context-search/ocr-calibration/runs/${this.run.id}/pages/${page.id}`,
          {
            quality_label: page.quality_label,
            reference_text: page.reference_text,
            review_note: page.review_note,
          },
        );
        const previewLoadStates = new Map(
          this.run.pages.map(currentPage => [currentPage.id, currentPage.previewLoadState]),
        );
        this.run = data.run;
        for (const updatedPage of this.run.pages) {
          this.markSavedReview(updatedPage);
          if (previewLoadStates.has(updatedPage.id)) {
            updatedPage.previewLoadState = previewLoadStates.get(updatedPage.id);
          }
        }
      } catch (error) {
        this.error = error.response?.data?.message || error.message;
      } finally {
        this.busy = false;
        this.savingPageId = null;
      }
    },
    showEvaluateCommand() {
      this.commandCopied = false;
      this.$refs.evaluateCommandModal.show();
    },
    async copyEvaluateCommand() {
      try {
        await copyOcrTextToClipboard(this.evaluateCommand);
        this.commandCopied = true;
        clearTimeout(this.commandCopyResetTimer);
        this.commandCopyResetTimer = setTimeout(() => {
          this.commandCopied = false;
        }, 3000);
      } catch (error) {
        this.error = this.$t('pool.ocr-calibration-copy-failed');
      }
    },
    async approve() {
      this.busy = true;
      try {
        await axios.post(`/api/v2/admin/context-search/ocr-calibration/runs/${this.run.id}/approve`);
        await this.selectRun(this.run.id);
        await this.load();
      } catch (error) {
        this.error = error.response?.data?.message || error.message;
      } finally {
        this.busy = false;
      }
    },
    preview(page) {
      return pdfPreviewImageForPage({id: page.resource_id}, page.page_number);
    },
    markSavedReview(page) {
      page.reviewSaved = Boolean(page.quality_label);
      page.savedQualityLabel = page.quality_label;
      page.savedReferencePresent = Boolean(page.reference_text?.trim());
    },
    previewLarge(page) {
      return pdfPreviewImageForPageLarge({id: page.resource_id}, page.page_number);
    },
    updatePreviewZoom(page, event) {
      if (page.previewLoadState !== 'loaded') return;
      const frame = event.currentTarget;
      const image = frame.querySelector('.ocr-preview-image');
      if (!image) return;
      const imageRect = image.getBoundingClientRect();
      if (event.clientX < imageRect.left || event.clientX > imageRect.right
        || event.clientY < imageRect.top || event.clientY > imageRect.bottom) return;
      const frameRect = frame.getBoundingClientRect();
      if (this.zoomPageId !== page.id) this.zoomLoadedPageId = null;
      this.zoomPageId = page.id;
      this.zoomPosition = {
        x: ((event.clientX - frameRect.left) / frameRect.width) * 100,
        y: ((event.clientY - frameRect.top) / frameRect.height) * 100,
      };
    },
    onPreviewZoomLoaded(page, event) {
      if (this.zoomPageId !== page.id) return;
      const previewImage = event.currentTarget.parentElement.querySelector('.ocr-preview-image');
      const previewWidth = previewImage?.getBoundingClientRect().width || 0;
      this.zoomScale = previewWidth ? Math.max(1, event.currentTarget.naturalWidth / previewWidth) : 1;
      this.zoomLoadedPageId = page.id;
    },
    onPreviewZoomError(page) {
      if (this.zoomPageId === page.id) this.clearPreviewZoom();
    },
    clearPreviewZoom() {
      this.zoomPageId = null;
      this.zoomLoadedPageId = null;
      this.zoomScale = 1;
    },
    previewZoomStyle() {
      return {
        transform: `scale(${this.zoomScale})`,
        transformOrigin: `${this.zoomPosition.x}% ${this.zoomPosition.y}%`,
      };
    },
    status(value) {
      return this.$t(`pool.ocr-calibration-status-${value}`);
    },
    percent(value) {
      return value === null || value === undefined ? '—' : `${(Number(value) * 100).toFixed(1)} %`;
    },
  },
};
</script>

<style scoped>
.ocr-calibration article[id^="ocr-calibration-page-"] {
  scroll-margin-top: 5rem;
}

.ocr-resource-link {
  color: inherit;
  text-decoration: none;
}

.ocr-resource-link:hover,
.ocr-resource-link:focus-visible {
  color: inherit;
  text-decoration: underline;
}

.ocr-text-wrapper {
  position: relative;
}

.ocr-calibration-intro {
  max-width: 72ch;
}

.ocr-copy-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: .375rem;
  color: var(--bs-secondary-color);
  background: transparent;
  border: 1px solid transparent;
  border-radius: var(--bs-border-radius);
  line-height: 1;
}

.ocr-copy-actions {
  position: absolute;
  right: .5rem;
  bottom: .5rem;
  display: flex;
  gap: .125rem;
}

.ocr-copy-button:hover:not(:disabled),
.ocr-copy-button:focus-visible {
  color: var(--bs-body-color);
  background: var(--bs-body-bg);
  border-color: var(--bs-border-color);
}

.ocr-copy-button:disabled {
  opacity: .5;
}

.ocr-copy-button :deep(svg) {
  width: 1rem;
  height: 1rem;
  fill: currentColor;
}

.ocr-text {
  height: 6rem;
  overflow: auto;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
  border: 1px solid var(--bs-border-color);
  padding: .75rem 4.5rem .75rem .75rem;
  font-size: .9rem;
}

.ocr-preview {
  position: relative;
  min-height: 12rem;
  overflow: hidden;
  cursor: zoom-in;
}

.ocr-preview-zoom {
  position: absolute;
  inset: 0;
  z-index: 1;
  width: 100%;
  height: 100%;
  object-fit: contain;
  pointer-events: none;
  will-change: transform;
  transition: transform 120ms ease-out, transform-origin 120ms ease-out;
}

.ocr-preview-indicator {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--bs-primary);
  z-index: 2;
  pointer-events: none;
}

.ocr-preview-indicator :deep(svg) {
  width: 2.5rem;
  height: 2.5rem;
  fill: currentColor;
}
</style>
