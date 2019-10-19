<template>
    <div class="container">

        <custom-dialog ref="areyoushure"/>

    </div>
</template>

<script>
    import CustomDialog from "../../../components/modals/dialogs/customDialog";
    import {api_v2_system_shutdown} from "../../../components/serverRoutes";
    import axiosInstance from "../axiosInstance";

    export default {
        name: "RequestShutdown",

        data() {
            return {};
        },

        asyncComputed: {
            isAdmin: {
                get() {
                    return this.$store.dispatch('general/isAdmin')
                        .then((isAdmin) => {
                            return isAdmin;
                        });
                },
                default: false,
                /* watch() {
                    this.forceReload
                }*/
            }
        },

        computed: {},

        methods: {
            shutdownSystem() {
                if (this.isAdmin === true) {
                    return axiosInstance.get(api_v2_system_shutdown).then(response => {
                        return true;
                    });
                } else {
                    console.error("ONLY Admin-Users allowed!");
                    return false;
                }
            }
        },

        mounted() {
            this.$refs.areyoushure.show({
                title: this.$t('pool.attention'),
                yesVariant: "danger",
                noVariant: "success",
                content: this.$t('pool.Realy-shutdown?'),
                allowBackdrop: false
            }).then((answer) => {

                if (answer === true) {
                    // Do System shutdown
                    this.$refs.areyoushure.show({
                        title: this.$t('pool.notice'),
                        content: this.$t('pool.System-is-beeing-shutdown'),
                        noEnabled: false,
                        cancelEnabled: false,
                        yesEnabled: false,
                        yesText: this.$t('pool.Ok'),
                        yesVariant: 'primary'
                    }).then(() => {
                        // Um keine Fehlermeldung in der Konsole zu haben
                    });

                    this.shutdownSystem();

                } else {
                    this.$router.back();
                }
            });
        },


        components: {CustomDialog},

    }
</script>

<style scoped>

</style>