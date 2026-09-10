<template>
  <nav v-bind="$attrs" aria-label="Pagination">
    <ul class="pagination" :class="paginationClasses">
      <li class="page-item" :class="{disabled: currentPage <= 1}">
        <a class="page-link" href="#" aria-label="Go to first page" @click.prevent="navigatePage(1)">&laquo;</a>
      </li>
      <li class="page-item" :class="{disabled: currentPage <= 1}">
        <a class="page-link" href="#" aria-label="Go to previous page" @click.prevent="navigatePage(currentPage - 1)">&lsaquo;</a>
      </li>
      <li v-if="visiblePages[0] > 1" class="page-item disabled" role="separator"><span class="page-link">…</span></li>
      <li v-for="page in visiblePages" :key="page" class="page-item" :class="{active: page === currentPage}">
        <router-link
            v-if="useRouter"
            class="page-link"
            :to="makeLink(page)"
            :aria-current="page === currentPage ? 'page' : null"
            @click="selectPage(page)"
        >{{ page }}</router-link>
        <a v-else class="page-link" :href="makeLink(page)" @click="selectPage(page)">{{ page }}</a>
      </li>
      <li v-if="visiblePages[visiblePages.length - 1] < pageCount" class="page-item disabled" role="separator"><span class="page-link">…</span></li>
      <li class="page-item" :class="{disabled: currentPage >= pageCount}">
        <a class="page-link" href="#" aria-label="Go to next page" @click.prevent="navigatePage(currentPage + 1)">&rsaquo;</a>
      </li>
      <li class="page-item" :class="{disabled: currentPage >= pageCount}">
        <a class="page-link" href="#" aria-label="Go to last page" @click.prevent="navigatePage(pageCount)">&raquo;</a>
      </li>
    </ul>
  </nav>
</template>

<script>
import {paginationPages} from './bootstrap-pagination';

export default {
    name: 'BPaginationNav',
    inheritAttrs: false,
    props: {
        align: {type: String, default: 'start'},
        baseUrl: {type: String, default: '/'},
        limit: {type: [Number, String], default: 5},
        linkGen: {type: Function, default: null},
        modelValue: {default: undefined},
        numberOfPages: {type: [Number, String], default: 1},
        pills: {type: Boolean, default: false},
        size: {type: String, default: null},
        useRouter: {type: Boolean, default: false},
        value: {default: 1},
    },
    emits: ['change', 'input', 'page-click', 'update:modelValue'],
    computed: {
        currentPage() {
            return Number.parseInt(this.modelValue === undefined ? this.value : this.modelValue, 10) || 1;
        },
        pageCount() {
            return Math.max(1, Number.parseInt(this.numberOfPages, 10) || 1);
        },
        visiblePages() {
            return paginationPages(this.currentPage, this.pageCount, this.limit);
        },
        paginationClasses() {
            return {
                [`pagination-${this.size}`]: this.size,
                'b-pagination-pills': this.pills,
                'justify-content-center': this.align === 'center',
                'justify-content-end': this.align === 'right' || this.align === 'end',
            };
        },
    },
    methods: {
        makeLink(page) {
            return this.linkGen ? this.linkGen(page, {link: `${this.baseUrl}${page}`, text: String(page)}) : `${this.baseUrl}${page}`;
        },
        navigatePage(page) {
            const normalized = Math.min(this.pageCount, Math.max(1, page));
            if (normalized === this.currentPage) return;
            this.selectPage(normalized);
            if (this.useRouter) {
                this.$router.push(this.makeLink(normalized));
            } else {
                window.location.assign(this.makeLink(normalized));
            }
        },
        selectPage(page) {
            const normalized = Math.min(this.pageCount, Math.max(1, page));
            if (normalized === this.currentPage) return;
            this.$emit('page-click', normalized);
            this.$emit('input', normalized);
            this.$emit('update:modelValue', normalized);
            this.$emit('change', normalized);
        },
    },
};
</script>
