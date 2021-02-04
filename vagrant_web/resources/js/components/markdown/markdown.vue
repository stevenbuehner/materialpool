<script>
import {BibleVerseService} from '../../../../vendor/stevenbuehner/bible-verse-bundle/js/out/BibleVerseService_de.js';
import marked              from 'marked';
import biblePopover        from "./../bibleverse/bibleverse-inline-popover-txt";

const regexp = BibleVerseService.biblePattern;

export default {
  name: "Markdown",

  props: {
    text: {
      type: String,
      required: true,
    },

    loadBibleverses: {
      type: Boolean,
      required: false,
      default: true
    }
  },

  computed: {
    myRenderedText() {

      const renderer = new marked.Renderer();
      const loadBVs  = this.loadBibleverses;

      renderer.text = function (text) {
        // console.log(text);


        // match.trim() ... um zu verhindern dass Zeilenumbrüche am Ende in den Quelltext kommen => Darstellungsfehler
        return text.replace(regexp, function (match) {
          return `<bible-popover :text="'${match.trim()}'" :load-contents="${loadBVs}"/>`;
        });
      };

      return marked(this.text, {
        sanitize: true,
        gfm: true,
        smartLists: true,
        smartypants: true,
        tables: true,
        breaks: true,
        renderer
      });
    }
  },

  render(h) {
    if (this.myRenderedText) {

      // See: npm v-runtime-template
      const dynamic = {
        template: "<div class='compiledMarkdown'>" + this.myRenderedText + "</div>",
        components: {biblePopover}
      };

      return h(dynamic, {});
    }
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
}

</style>