<template>
  <ul class="sb-context-menu"
      tabindex="-1"
      v-if="menuOpen"
      :style="{top:menuTop, left:menuLeft}">
    <slot :optional-data="optionalData"></slot>

  </ul>
</template>

<script>
import menuItem from './context-menu-item.vue';

const MENU_CLOSE_EVENT         = 'context-menu:close';
const MENU_OPEN_EVENT          = 'context-menu:open';
export const MENU_ITEM_CLICKED = 'item-clicked';

export default {
  name: "context-menu",
  components: {
    menuItem
  },

  props: {
    menuOffsetX: {
      type: Number,
      required: false,
      default: 5
    },
    menuOffsetY: {
      type: Number,
      required: false,
      default: -20
    },
  },

  data() {
    return {
      menuOpen: false,
      menuTop: '0px',
      menuLeft: '0px',
      optionalData: {},
    };
  },

  methods: {
    setMenu: function (top, left) {

      const fensterHohe = window.innerHeight;

      // const fensterBreite = window.innerWidth;
      const fensterBreite = document.documentElement.clientWidth || document.body.clientWidth; // El. width minus scrollbar width

      const domRect = this.$el.getBoundingClientRect();

      const menuHoehe  = domRect.height;
      const menuBreite = domRect.width;
      const menuLeft   = domRect.left;
      const menuTop    = domRect.top;

      const menuLeftOf = this.$el.offsetLeft;
      const menuTopOf  = this.$el.offsetTop;

      let moveTop  = top /* - menuTop + menuTopOf */ + this.menuOffsetY;
      let moveLeft = left /* - menuLeft + menuLeftOf */ + this.menuOffsetX;

      if ((moveLeft + menuBreite) > fensterBreite) {
        moveLeft = fensterBreite - menuBreite;
      }

      this.menuTop  = moveTop + 'px';
      this.menuLeft = moveLeft + 'px';
    },


    closeMenu: function () {
      //       v-on:blur="closeMenu"
      // Entfernt, weil ansonsten <a> Elemente im Menü nicht mehr funktionieren
      // Vermutlich löscht der DOM die Elemente, bevor der Link geöffnet werden kann. Darum passiert gar nichts
      // Auch $nextTick hat nicht geholfen

      this.$root.$emit(MENU_CLOSE_EVENT);
    },

    openMenu: function (event, optionalData) {
      if (event) {
        event.preventDefault();
      }

      if (optionalData) {
        this.optionalData = optionalData;
      }

      this.$root.$emit(MENU_OPEN_EVENT, this);

      this.menuOpen = true;

      this.$nextTick(function () {
        this.$el.focus();
        this.setMenu(event.y, event.x)
      });

    },
  },

  created() {

    this.$root.$on(MENU_CLOSE_EVENT, function (e) {
      // console.log(e);
      this.menuOpen = false;
    }.bind(this));

    this.$root.$on(MENU_OPEN_EVENT, function (instance) {
      if (instance !== this) {
        this.menuOpen = false;
      }
    }.bind(this));

    // Only once for the first component
    if (this.$root.contextMenuClickSetupComplete === undefined) {

      document.onmousedown = function (event) {

        const target   = event.target;
        const dropdown = target.closest('.sb-context-menu');

        if (!dropdown) {
          this.$root.$emit(MENU_CLOSE_EVENT, event);
        }

        this.$root.contextMenuClickSetupComplete = true;
      }.bind(this);
    }


    this.$on(MENU_ITEM_CLICKED, () => {
      this.menuOpen = false;
    });

  },

  destroyed() {
    // Todo: Remove document onmousedown event
    this.$off(MENU_ITEM_CLICKED);
  },

}
</script>

<style scoped>

.sb-context-menu {
  position: fixed;
  top: 100%;
  left: 0;
  z-index: 999999;
  display: block;
  float: left;
  min-width: 10rem;
  padding: .25em 0;
  margin: .125rem 0 0;
  font-size: 1rem;
  color: #212529;
  text-align: left;
  list-style: none;
  background-color: #fff;
  background-clip: padding-box;
  border: 1px solid rgba(0, 0, 0, .15);
  border-radius: .25rem;
}

</style>