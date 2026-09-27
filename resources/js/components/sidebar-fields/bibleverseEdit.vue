<script>
import tagIcon from '@icons/vendor/svg-icon/svg/material/style.svg';
import tagEdit from "./tagEdit";
import {useBibleversesStore} from '../../apps/main/stores/bibleverses';

export default {
  name: "bibleverseEdit",
  extends: tagEdit,

  data() {
    return {
      clearOnSelect: true, // Override
    }
  },

  computed: {
    // Override from tagEdit - all Types are valid
    validTypesValues() {
      return this.value;
    },

    // Override from tagEdit - no Invalid Tags
    invalidTypesValues() {
      return [];
    },

    // Override from tagEdit -> no new tags
    optionsWithNewTag() {
      return this.suggestedTags;
    }
  },

  methods: {

    // Override => no Obeserver
    async onOpen() {
    },
    // Override => no Obeserver
    async onClose() {
    },

    // Override from tagEdit
    doSearch(query, page) {

      const counter    = ++this.queryCounter;
      const queryCache = {
        query,
        page,
        isLoading: true
      }

      this.queryHandler[counter] = queryCache;

      queryCache.promise = useBibleversesStore().search(query)
                               .then((bibleverses) => {

                                 queryCache.isLoading = false;

                                 return {
                                   keywords: bibleverses,
                                   hasMore: false,
                                   current_page: 1
                                 };

                               })
                               .catch((err) => {
                                 console.error(err);
                               });

      return {queryCache, counter};

    },

    // Override from tagEdit
    _getTagLabelFromObject(value) {
      if (typeof value === 'object') {
        if (!Object.hasOwn(value, 'label')) {
          return console.warn(
              `[vue-select warn]: Label key "option.label" does not` +
              ` exist in options object ${JSON.stringify(value)}.\n` +
              'http://sagalbot.github.io/vue-select/#ex-labels'
          )
        } else {
          return value.label;
        }

      } else {
        return value;
      }
    },

    // Override from tagEdit
    _getOptionKey(el) {
      return el.from + '-' + el.to;
    }

  },

  components: {
    tagIcon
  }
}
</script>
