<script>
import marked       from './markdownSetup';
import biblePopover from "./../bibleverse/bibleverse-inline-popover-txt";
import _throttle    from 'lodash/throttle'
import DOMPurify    from "dompurify";

export default {
  name: "compiledMarkdown",

  props: {
    text: {
      type: String,
      required: true,
    },

    throttle: {
      type: Number,
      default: 40
    }
  },

  data() {
    return {
      compiledText: ''
    };
  },

  watch: {
    text: {
      handler(val) {
        this.debounceCompilation(val);
      },
      immediate: true
    },
  },

  methods: {
    debounceCompilation: _throttle(function () {
      const dirty       = marked.parse(this.text);
      this.compiledText = DOMPurify.sanitize(dirty);
    }, 200)
  },

  render(h) {
    // See: npm v-runtime-template
    const dynamic = {
      template: '<div class=\'compiledMarkdown\'>' + this.compiledText + '</div>',
      components: {biblePopover}
    };

    return h(dynamic, {});
  },

  components: {}

}
</script>

<style lang="scss">
.myMarkdown {
  border: solid red 1px;
}

.compiledMarkdown {

  a {
    color: #4183c4;
    text-decoration: none;
  }

  code {
    display: block;
    overflow: auto;
    margin: 15px 0;
    padding: 1em 1em;
    background-color: #f8f8f8;
    font-size: 1em;
    line-height: 1.25em;
    border: 1px solid #ddd;
    border-radius: 3px;
    color: inherit;
    font-family: monospace;
  }

  blockquote {
    border-left: 4px solid #DDD;
    padding: 0 15px;
    color: #777;
  }

  .summary {
    padding-left: 1em;
  }
}

</style>