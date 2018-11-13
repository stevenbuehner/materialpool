import {getRelativeClickCoordinates} from "../general/relativeElementPositions";

let elementWidth = 0;

export const draggingSupport = {

    props: {
        dragable: {
            type: Boolean,
            required: false,
            default: true,
        }
    },

    data() {
        return {
            dragging: {
                ongoing: false,
                xStart: 0,
                xEnd: 0,
                backupRelevance: 0
            }
        };
    },

    computed: {
        dragPercentage() {
            return Math.min(Math.max(this.dragging.xEnd, 0), elementWidth) / elementWidth;
        }
    },

    methods: {

        startDrag(event) {
            if (this.dragable !== true) {
                return;
            }

            // update element width
            elementWidth = this.$el.offsetWidth;

            this.dragging.ongoing = true;
            this.dragging.xEnd    = this.dragging.xStart = getRelativeClickCoordinates(event, this.$el).x || 0;

            window.addEventListener('mouseup', this.stopDrag);
            window.addEventListener('mousemove', this.doDrag);
            window.addEventListener('keydown', this.keydown)

        },
        doDrag(event) {
            this.dragging.xEnd = getRelativeClickCoordinates(event, this.$el).x;
        },
        stopDrag(event) {

            // Remove Event Listeners
            window.removeEventListener('mouseup', this.stopDrag);
            window.removeEventListener('mousemove', this.doDrag);
            window.removeEventListener('keydown', this.keydown);


            if (this.dragging.ongoing /* true if dragging was not canceled */
                && event /* Event exists when dragging was not canceled */
            ) {
                this.doDrag(event); // Use the last mouse coordinates

                this.dragging.ongoing = false;

                if (this.dragging.xEnd === this.dragging.xStart) {
                    // Don't call an Pivot update - this was only a missdirected single click
                    // To set the relevance = 0 we can use negative direction
                } else {
                    this.$emit('dragging-done', this.dragPercentage);
                }

            }


        },
        cancelDrag() {
            this.dragging.ongoing = false;
            this.stopDrag();
        },
        keydown(event) {
            event = event || window.event;
            if (event.keyCode === 27) {
                // ESC Pressed
                this.cancelDrag();
            }
        },
    }

}