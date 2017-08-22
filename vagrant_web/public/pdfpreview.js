Vue.component('page-list', {
    template: '<div class="page-list"><page ' +
    'v-for="page in pages" ' +
    'v-bind:index="page.index" ' +
    'v-bind:isSelectable="page.isSelectable"' +
    'v-bind:isSelectionStart="page.isSelectionStart"' +
    'v-bind:isSelectionEnd="page.isSelectionEnd"' +
    'v-bind:isSelected="page.isSelected"' +
    'v-bind:key="page.index"' +
    '@firstPageSelected="firstPageSelected"' +
    '@lastPageSelected="lastPageSelected"' +
    '@addPageSelection="addPageSelection">Page {{page.label}}</page></div>',

    data: function () {
        return {
            pages: [
                {
                    label: 1,
                    index: 1,
                    isSelectable: true,
                    isSelected: false,
                    image: '/preview-page1.jpg'
                },
                {
                    label: 2,
                    index: 2,
                    isSelectable: false,
                    isSelected: false,
                    image: '/preview-page2.jpg'
                },
                {
                    label: 3,
                    index: 3,
                    isSelectable: true,
                    isSelected: false,
                    image: '/preview-page4.jpg'
                }
            ],

            selection: {
                firstIndex: undefined,
                lastIndex: undefined,
                additionalPages: []
            }

        }
    },

    created: function () {
    },

    computed: {
        /**
         *
         * @returns index|false
         */
        firstSelectedPage: function () {
            for (var page in this.pages) {
                if (this.pages[page].isSelected === true) {
                    return this.pages[page].index;
                }
            }

            return false;
        },

        lastSelectedPage: function (pageIndex) {
            var num = this.pages.length;

            while (num--) {
                if (this.pages[num].isSelected === true)
                    return this.pages[num].index;
            }

            return false;
        }
    },

    methods: {
        firstPageSelected: function (pageIndex) {
            for (var page in this.pages) {
                this.pages[page].isSelected = this.pages[page].index == pageIndex;
            }
            this.updateSelectedPages();
        },


        lastPageSelected: function (pageIndex) {
            var firstSelectedPage = this.firstSelectedPage;
            var lastSelectedPage = this.lastSelectedPage;

            if (firstSelectedPage === false) {
                // Nothing was selected before -> select everything beginning from first page
                if (this.pages.length > 0) {
                    firstSelectedPage = this.pages[0].index;
                } else {
                    console.error("This shouldn't happen. You cant't select pages if there are none.");
                }
            }

            if (firstSelectedPage == pageIndex) {
                // Nothing to Change
            } else {
                // Select everything in between
                var min = Math.min(Math.min(lastSelectedPage, firstSelectedPage), pageIndex);
                var max = Math.max(Math.max(lastSelectedPage, firstSelectedPage), pageIndex);

                for (var page in this.pages) {
                    this.pages[page].isSelected = (this.pages[page].index >= min && this.pages[page].index <= max);
                }
            }
            this.updateSelectedPages();
        },
        addPageSelection: function (pageIndex) {
            for (var page in this.pages) {
                if (this.pages[page].index == pageIndex) {
                    this.pages[page].isSelected = !this.pages[page].isSelected;
                    break;
                }
            }
            this.updateSelectedPages();
        },

        updateSelectedPages: function () {
            var formerPage = undefined;
            var formerPageSelected = false;

            for (var page in this.pages) {

                this.pages[page].isSelectionStart = (formerPageSelected === false && this.pages[page].isSelected === true);

                if (formerPage !== undefined) {
                    formerPage.isSelectionEnd = (formerPageSelected === true && this.pages[page].isSelected === false)
                }

                formerPage = this.pages[page];
                formerPageSelected = this.pages[page].isSelected == true;
            }

            if (formerPage !== undefined && formerPageSelected === true) {
                formerPage.isSelectionEnd = formerPageSelected === true;
            }
        }
    },


});

Vue.component('page', {
    template: '<div class="cell" ' +
    'v-show="isVisible" ' +
    ':class="{selectable : isSelectable, selected : isSelected, isSelectionStart : isSelectionStart, isSelectionEnd : isSelectionEnd}">' +
    '    <div class="selection-container"><div class="start"></div><div class="middle"></div><div class="end"></div>' +
    '    </div>' +
    '    <div class="image-container" @click="handleClick">' +
    'IMAGE {{index}}' +
    '        <img v-bind="image"/>' +
    '    </div>' +
    '    <div class="menue-container">' +
    '        <div class="left" @click="firstPageSelected"></div>' +
    '        <div class="middle">{{label}}</div>' +
    '        <div class="right" @click="lastPageSelected"></div>' +
    '    </div>' +
    '</div>',

    data: function () {
        return {
            isVisible: true,
        }
    },

    props: {
        index: {
            required: true,
            type: Number
        },
        image: {
            type: String
        },
        isSelectable: {
            default: true,
            type: Boolean
        },
        isSelected: {
            default: false,
            type: Boolean
        },
        isSelectionStart: {
            default: false,
            type: Boolean
        },
        isSelectionEnd: {
            default: false,
            type: Boolean
        }
    },

    computed: {
        label: function () {
            return this.index;
        }
    },

    methods: {
        handleClick: function (event) {
            if (event.shiftKey) {
                this.lastPageSelected();
            } else if (event.metaKey) {
                this.addPageSelected();
            } else {
                this.firstPageSelected();
            }
        },

        firstPageSelected: function () {
            this.$emit('firstPageSelected', this.index)
        },
        lastPageSelected: function () {
            this.$emit('lastPageSelected', this.index);
        },
        addPageSelected: function () {
            this.$emit('addPageSelection', this.index);
        },

        hidePage: function () {
            this.isVisible = false;
        },
        showPage: function () {
            this.isVisible = true;
        },

        checkSelectionRequest: function () {
            if (this.isSelectable !== true) {
                console.log("Page is not selectable!");
            }

            return this.isSelectable === true;
        }
    }

});