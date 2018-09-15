export default {

    data() {
        return {
            material: null
        };
    },

    methods: {

        getMaterialPromise(id) {
            return this.$store.dispatch('materials/getMaterial', id);
        },

        updateMaterialData(id) {
            this.material = null;

            this.getMaterialPromise(id).then((material) => {
                this.material = material;
            });
        },

        goToMaterial(id) {
            this.$router.push(
                {
                    name: 'material-detail',
                    params: {id: id}
                }
            );
        }
    }

}