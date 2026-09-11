<script>
import marked                     from './markdownSetup';
import bibleverseInlinePopoverTxt from "./../bibleverse/bibleverse-inline-popover-txt";
import _throttle                  from 'lodash/throttle'
import {sanitizeTextMarkup}       from "./sanitizeSetup";
import {h}                        from 'vue';

function vnodeData(element) {
  const data = {};

  for (const {name, value} of element.attributes) {
    if (name === 'class') {
      data.class = value;
    } else if (name === 'style') {
      data.style = value;
    } else {
      data[name] = value;
    }
  }

  return data;
}

function renderSanitizedNode(node) {
  if (node.nodeType === Node.TEXT_NODE) {
    return node.textContent;
  }

  if (node.nodeType !== Node.ELEMENT_NODE) {
    return null;
  }

  const children = [...node.childNodes]
    .map(child => renderSanitizedNode(child))
    .filter(child => child !== null);
  const tagName = node.tagName.toLowerCase();

  if (tagName === 'bibleverse-inline-popover-txt') {
    return h(bibleverseInlinePopoverTxt, {
      text: node.getAttribute('data-text') || node.textContent,
      loadContents: node.getAttribute('data-load-contents') === 'true',
    }, children);
  }

  return h(tagName, vnodeData(node), children);
}

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
      // console.log(dirty);
      this.compiledText = sanitizeTextMarkup(dirty);
    }, 200)
  },

  render() {
    const root = document.createElement('div');
    root.innerHTML = this.compiledText;
    const children = [...root.childNodes]
      .map(node => renderSanitizedNode(node))
      .filter(node => node !== null);

    return h('div', {class: 'compiledMarkdown'}, children);
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
