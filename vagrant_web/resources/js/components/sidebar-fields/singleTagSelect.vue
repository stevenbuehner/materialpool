<script>
import tagEdit from "./tagEdit";

export default {

  name: "singleTagSelect",
  extends: tagEdit,

  mixins: [],


  data() {
    return {
      // Override
      multipleTags: false
    }
  },

  computed: {},

  methods: {
    // Override
    async onInputChanged(input) {

      // Nothing to change
      if ((input === null && this.value === null) || (input && this.value && input.id === this.value.id)) {
        return;
      }

      // Dissociate Keyword
      if (input === null) {

        this.$emit('input:dissociated', null);

        // Reset SearchResults
        // Because: Removed Keywords might have been lonely and deleted at the server
        // Therefore we MIGHT not be able to assign them anymore ... but have to create them first again
        this.page           = 1;
        this.hasMoreResults = true;
        this.suggestedTags  = [];
        this.onSearchTermChanged(this.searchTerm, () => true);

        return;

      }

      let keyword = input;
      let statusFlash;

      // Create Keyword if needed
      if (input?.isNew === true) {

        statusFlash = this.flashStartCreating(this.$t('pool.new-keyword') + ' ' + input.title)

        // Keyword first has to be created first
        try {

          keyword = await this.$store.dispatch('keywords/create', {
            title: input.title,
            type: input.type
          });
          statusFlash = this.flashCreated(this.$t('pool.keyword'), statusFlash);

        } catch (errorMessage) {

          this.flashError(this.$t('pool.new-keyword') + ' ' + input.title, statusFlash);

        }
        // this.$emit('input', keyword);
      }

      // Associacte Keyword
      this.$emit('input:associated', keyword);

    },

  },

}
</script>