<template>
    <ul class="sb-context-menu"
        tabindex="-1"
        v-if="menuOpen"
        v-on:blur="closeMenu"
        :style="{top:menuTop, left:menuLeft}">

        <slot></slot>

    </ul>
</template>

<script>
    import menuItem from './context-menu-item.vue';

    const MENU_CLOSE_EVENT = 'context-menu:close';
    const MENU_OPEN_EVENT  = 'context-menu:open';

    export default {
        name: "context-menu",
        components: {
            menuItem
        },

        data() {
            return {
                menuOpen: false,
                menuTop: '0px',
                menuLeft: '0px',
            };
        },

        methods: {
            setMenu: function (top, left) {

                const fensterHohe   = window.innerHeight;
                const fensterBreite = window.innerWidth;

                const domRect = this.$el.getBoundingClientRect();

                const menuHoehe  = domRect.height;
                const menuBreite = domRect.width;
                const menuLeft   = domRect.left;
                const menuTop    = domRect.top;

                const menuLeftOf = this.$el.offsetLeft;
                const menuTopOf  = this.$el.offsetTop;

                let moveTop  = top - menuTop + menuTopOf;
                let moveLeft = left - menuLeft + menuLeftOf;

                if ((left + moveLeft + menuBreite) > fensterBreite) {
                    moveLeft = fensterBreite - menuBreite;
                }

                this.menuTop  = moveTop + 'px';
                this.menuLeft = moveLeft + 'px';
            },

            closeMenu: function () {
                this.$root.$emit(MENU_CLOSE_EVENT);
            },

            openMenu: function (event) {
                if (event) {
                    event.preventDefault();
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
                        this.$root.$emit(MENU_CLOSE_EVENT);
                    }

                    this.$root.contextMenuClickSetupComplete = true;
                }.bind(this);
            }

            /*
            this.$on('item-clicked', () => {
                console.log('itemCLicked');
            });
            */
        },

        beforeDestroy() {
            // Todo: Remove document onmousedown event
        }
    }
</script>

<style scoped>

    .sb-context-menu {
        position: absolute;
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