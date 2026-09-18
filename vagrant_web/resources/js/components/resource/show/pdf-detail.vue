<template>
  <div>
    <div class="row" v-if="maxPagesToDisplay < pagePivotCount">
      <div class="col">
        <b-form-select
            v-model="maxPagesToDisplay"
            :options="displayPagesLimitOptionsFormated"
        />
      </div>
    </div>
    <div class="row m-n1 pdfDetailWrapper">
      <div v-for="(image, index) in previewImages"
           class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3 p-1 imageContainer"
           @click="$refs.imageZoom.show(index)"
           :key="image.src">
        <div class="oneImagePage"
             @click.right.stop.prevent="isAdmin && $refs.menu.openMenu($event, {src: image.src, page: image.page_no})">
          <b-img-lazy
              v-if="index > 12"
              :src="image.thumbnailSrc || image.src"
              :alt="image.title"
              fluid
          ></b-img-lazy>
          <b-img
              v-if="index <= 12"
              :src="image.thumbnailSrc || image.src"
              :alt="image.title"
              fluid
							v-image-queue="100-index"
          ></b-img>

          <div class="title text-center">{{ image.title }}</div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3 p-1 imageContainer "
           v-if="pagePivotCount > maxPagesToDisplay">
        <div class="moreImages d-flex justify-content-center align-items-center">
          <div>
            <div class="moreDots">...</div>
            <div class="title text-center">{{ $t('pool.more-pages-available') }}</div>
          </div>
        </div>
      </div>

      <image-zoom :data="previewImages" ref="imageZoom"></image-zoom>

      <div>
        <context-menu ref="menu" v-slot:default="{optionalData}">
          <context-menu-item v-if="isAdmin"
                             @click.stop="openImage(optionalData.page)">
            {{ $t('pool.open-image') }}
          </context-menu-item>
          <context-menu-item v-if="isAdmin"
                             @click.stop="refreshPageImage(optionalData.page)">
            {{ $t('pool.refresh-image') }}
          </context-menu-item>
        </context-menu>
      </div>

    </div>
  </div>

</template>

<script>

import {pdfPreviewImageForPage, pdfPreviewImageForPageRefresh, previewImageFirstPage, previewImageLarge, pdfPreviewImageForPageLarge} from '../../serverRoutes';
import {BFormSelect, BImg, BImgLazy}                                                  from '@/adapters/bootstrap';
import pdfMixin                                                                       from '../pdf-mixin';
import ImageZoom                                                                      from "../../modals/imageZoom";
import ContextMenu
                                                                                      from "../../context-menu/context-menu";
import ContextMenuItem
                                                                                      from "../../context-menu/context-menu-item";
import asyncIsAdminMixin
                                                                                      from "../../general/async-isAdmin-mixin";
import axiosInstance
                                                                                      from "../../../apps/main/axiosInstance";
import {limitedPreviewPages}                                                          from './pdfPreviewPages';

export default {
  name: 'PdfDetail',

  mixins: [pdfMixin, asyncIsAdminMixin],

  props: {
    resource: {
      required: true,
      type: Object
    },
  },

  data() {
    return {
      maxPagesToDisplay: 50,
    };
  },
  computed: {

    currentlyDisplayedImage() {
      return this.previewImages[this.currentlyDisplayedPageIndex];
    },

    previewImages() {

      let urls = [];

      if (this.pageCount === 0) {
        // Die Seitenanzahl-Erkennung auf dem Server ist fehlgeschlagen => Zeige einfach nur die erste Seite an
			urls.push({
				src: previewImageLarge(this.resource),
				thumbnailSrc: previewImageFirstPage(this.resource),
          title: 'Startseite',
          page_no: 1
        });
      } else {
        urls = limitedPreviewPages(this.previewablePages, this.maxPagesToDisplay).map((pageNo) => {
			return {
				src: pdfPreviewImageForPageLarge(this.resource, pageNo),
				thumbnailSrc: pdfPreviewImageForPage(this.resource, pageNo),
				title: 'Seite ' + pageNo,
				page_no: pageNo,
			};
        });
      }

      return urls;
    },

    displayPagesLimitOptionsFormated() {
      return this.displayPagesLimitOptions.map((el) => {
        return {value: el, text: el};
      })
    },

    displayPagesLimitOptions() {
      // 50 ist Standard und sollte in jeder Auswahl vorhanden sein!

      if (this.pagePivotCount <= 100) {
        return [20, 30, 40, 50, 70, 80, 100];
      } else if (this.pagePivotCount <= 500) {
        return [20, 50, 100, 300, 500];
      } else if (this.pagePivotCount <= 1000) {
        return [20, 50, 200, 400, 600, 1000];
      } else {
        return [20, 50, 200, 400, 600, 1000, 10000];
      }
    }
  },

  methods: {

    openImage(page) {
      window.location.href = pdfPreviewImageForPage(this.resource, page);
    },

    refreshPageImage(page) {
      const urlRefresh = pdfPreviewImageForPageRefresh(this.resource, page);
      axiosInstance.get(urlRefresh).then(() => {
        alert('Image reloaded. Force Page reload please.');
        window.location.href = urlRefresh;
      })
    }

  },

  components: {
    ImageZoom,
    BImg,
    BImgLazy,
    BFormSelect,
    ContextMenu, ContextMenuItem
  }

}
</script>

<style lang="scss">

@use "resources/sass/theme" as *;

.pdfDetailWrapper {
  margin: 0;

  .imageContainer {


    .oneImagePage, .moreImages {

      background-color: $card-bg;
      cursor: pointer;

      border: $border-width solid $border-color;
      border-radius: $card-border-radius;

      .title {
        font-size: 0.75em;
      }

      .moreDots {
        font-size: 5em;
        line-height: 1em;
        color: grey;
      }

      &.moreImages {
        cursor: default;
        text-align: center;
        height: 100%;
        background-color: $card-bg;
      }
    }
  }

}

</style>
