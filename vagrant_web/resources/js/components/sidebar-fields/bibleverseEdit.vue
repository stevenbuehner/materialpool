<script>
import tagIcon from 'svg-icon/dist/svg/material/style.svg';
import tagEdit from "./tagEdit";

export default {
  name: "bibleverseEdit",
  extends: tagEdit,

  data() {
    return {
      clearOnSelect: true, // Override
    }
  },

  computed: {
    validTypesValues() {
      return this.value;
    },

    invalidTypesValues() {
      return [];
    },

    optionsWithNewTag() {
      // Override
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

    getTagLabelFromObject(value) {
      if (typeof value === 'object') {
        if (!value.hasOwnProperty('label')) {
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

    doSearch(query, page) {

      const counter    = ++this.queryCounter;
      const queryCache = {
        query,
        page,
        isLoading: true
      }

      this.queryHandler[counter] = queryCache;

      // console.log('Loading No' + counter + '...: "' + query + '"', 'Page ' + page);

      queryCache.promise = this.$store.dispatch('bibleverses/search', query)
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

    // Override
    _getOptionKey(el) {
      return el.from + '-' + el.to;
    }

  },

  components: {
    tagIcon
  }
}
</script>
