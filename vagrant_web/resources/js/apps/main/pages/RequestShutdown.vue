<template>
    <div class="systemDownContainer" :class="{systemIsDown}">

        <custom-dialog ref="areyoushure">
            {{modalContent}}
            <span v-if="timer.show">{{timer.seconds}}</span>
        </custom-dialog>

        <div class="startAgain" v-if="systemIsDown">{{$t('pool.System-is-down')}}</div>

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
				},

				systemIsDown: false,
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
					this.systemIsDown = true;
					this.$refs.areyoushure.hide();
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

<style type="text/scss">
    @import "resources/sass/theme";

    .systemDownContainer {
        position: absolute;
        height: 100%;
        width: 100%;
    }

    .systemIsDown {
        background-color: $sidebar-input-background-colour-disabled;
        color: #636b6f;
        font-family: 'Raleway', sans-serif;
        font-weight: 100;
        height: 100vh;
        margin: 0;
        font-size: 84px;

        .startAgain {
            position: relative;
            text-align: center;
            top: 20%
        }
    }
</style>