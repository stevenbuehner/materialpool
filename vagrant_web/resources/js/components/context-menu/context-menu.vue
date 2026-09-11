<template>
  <ul class="sb-context-menu"
      tabindex="-1"
      v-if="menuOpen"
      :style="{top:menuTop, left:menuLeft}">
    <slot :optional-data="optionalData"></slot>

  </ul>
</template>

<script>
const openMenus = new Set();

export default {
  name: "context-menu",

  provide() {
    return {
      contextMenuItemClicked: this.onMenuItemClicked,
    };
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

      this.menuOpen = false;
      openMenus.delete(this);
    },

    openMenu: function (event, optionalData) {
      if (event) {
        event.preventDefault();
      }

      if (optionalData) {
        this.optionalData = optionalData;
      }

      openMenus.forEach(menu => {
        if (menu !== this) menu.closeMenu();
      });

      this.menuOpen = true;
      openMenus.add(this);

      this.$nextTick(function () {
        this.$el.focus();
        this.setMenu(event.y, event.x)
      });

    },

    onDocumentMouseDown(event) {
      const target = event.target;
      if (!(target instanceof Element) || !target.closest('.sb-context-menu')) {
        this.closeMenu();
      }
    },

    onMenuItemClicked() {
      this.closeMenu();
    },
  },

  mounted() {
    document.addEventListener('mousedown', this.onDocumentMouseDown);
  },

  beforeUnmount() {
    document.removeEventListener('mousedown', this.onDocumentMouseDown);
    this.closeMenu();
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
