export const draggingSupport = {
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
        dragDifference() {
            return Math.min(Math.max(this.dragging.xEnd - this.dragging.xStart, 0), 300);
        }
    },

    methods: {

        startDrag(event) {
            if (this.editable !== true) {
                return;
            }

            this.dragging.ongoing = true;
            this.dragging.xStart  = this.dragging.xEnd = event.clientX;

            window.addEventListener('mouseup', this.stopDrag);
            window.addEventListener('mousemove', this.doDrag);
            window.addEventListener('keydown', this.keydown)

        },
        doDrag(event) {
            this.dragging.xEnd = event.clientX;
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
                    this.updatePivot({relevance: this.dragDifference});
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