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

            return this.getMaterialPromise(id).then((material) => {
                this.material = material;
                return material;
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