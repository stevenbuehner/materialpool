<template>
    <div class="container">

        <custom-dialog ref="areyoushure">
            {{modalContent}}
            <span v-if="timer.show">{{timer.seconds}}</span>
        </custom-dialog>

    </div>
</template>

<script>
	import CustomDialog       from "../../../components/modals/dialogs/customDialog";
	import {systemShutdown}   from "../../../helper/systemShutdown";
	import {sessionKeepAlive} from "../../../helper/sessionKeepAlive";

	export default {
		name: "RequestShutdown",

		data() {
			return {
				modalContent: '',

				timer: {
					show: false,
					seconds: 0,
					timer: null,
				}
			};
		},

		created() {
			this.resetTimer();
		},

		computed: {},

		methods: {

			resetTimer() {
				this.stopTimer();
				this.timer.timer   = null;
				this.timer.show    = false;
				this.timer.seconds = 0;
			},

			startTimer() {
				this.stopTimer();
				const $this = this;

				this.timer.timer = window.setInterval(() => {
					this.timer.seconds++;

					sessionKeepAlive().catch(() => {
						$this.stopTimer();
					})

				}, 1000);
			},

			stopTimer() {
				if (this.timer.timer !== null) {
					clearInterval(this.timer.timer);
					this.timer.timer  = null;
					this.timer.show   = false;
					this.modalContent = this.$t('pool.System-was-shutdown');
				}
			},
		},

		mounted() {
			this.modalContent = this.$t('pool.Realy-shutdown?');

			this.$refs.areyoushure.show({
				title: this.$t('pool.attention'),
				yesVariant: "danger",
				noVariant: "success",
				allowBackdrop: false
			}).then((answer) => {

				if (answer === true) {
					// Do System shutdown
					this.modalContent = this.$t('pool.System-is-beeing-shutdown');
					this.timer.show   = true;
					this.startTimer();

					this.$refs.areyoushure.show({
						title: this.$t('pool.notice'),
						noEnabled: false,
						cancelEnabled: false,
						yesEnabled: true,
						yesText: this.$t('pool.Ok'),
						yesVariant: 'primary'
					}).then(() => {
						// Um keine Fehlermeldung in der Konsole zu haben
					});

					return systemShutdown()
						.catch((message) => {
							this.modalContent = message;
							this.resetTimer();
						});

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