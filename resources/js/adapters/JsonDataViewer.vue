<template>
  <div class="json-data-viewer"><VueJsonPretty :data="data" :deep="10" :show-length="true" :editable="false" /></div>
</template>

<script setup>
import VueJsonPretty from 'vue-json-pretty';
import 'vue-json-pretty/lib/styles.css';

defineProps({data: {type: [Object, Array], required: true}});
</script>

<style scoped>
.json-data-viewer :deep(.vjs-value-string) { color: var(--bs-success-text-emphasis); }
.json-data-viewer :deep(.vjs-value-number),
.json-data-viewer :deep(.vjs-value-boolean) { color: var(--bs-primary-text-emphasis); }
.json-data-viewer :deep(.vjs-value-null) { color: var(--bs-secondary-color); }
.json-data-viewer :deep(.vjs-tree-node:hover) { background-color: var(--bs-primary-bg-subtle); }
</style>
