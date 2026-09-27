<template>
  <teleport to="body">
    <ul ref="menu"
        class="dropdown-menu sb-context-menu show"
        tabindex="-1"
        v-if="menuOpen"
        :style="{top:menuTop, left:menuLeft}"
        role="menu">
      <slot :optional-data="optionalData"></slot>
    </ul>
  </teleport>
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

      const fensterBreite = document.documentElement.clientWidth || document.body.clientWidth; // El. width minus scrollbar width

      const menuBreite = this.$refs.menu?.getBoundingClientRect().width || 0;

      const moveTop = top + this.menuOffsetY;
      let moveLeft  = left + this.menuOffsetX;

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
        this.$refs.menu?.focus();
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
}

</style>
