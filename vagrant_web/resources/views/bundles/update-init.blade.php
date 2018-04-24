@extends('layouts.app')

@section('content')
    <div id="bundleapp">

        <h1 v-text="title"></h1>


        <div class="container">
            <p>
                {{trans_choice('pool.bundle.jobs-deleted', $deletedJobs, ['count' => $deletedJobs])}}
            </p>
            <p>
                {{trans_choice('pool.bundle.jobs-created', $deleteJobs + $updateJobs, ['count' => $deleteJobs + $updateJobs])}}
            </p>
        </div>


        <div>

            <h5>Import {{$bundle->name}} v{{$info["version"]}}</h5>
            <b-progress :max="progress.max" show-value class="mb-3">
                <b-progress-bar :value="progress.value">
                    @{{ progress.value }} / @{{ progress.max }}
                </b-progress-bar>
            </b-progress>

            <button @click="runNextJobs" v-if="!isRunning && !allDone" v-text="startButtonText"></button>
            <button @click="stopRunning" v-if="isRunning" v-text="stopButtonText"></button>
            <span class="badge badge-info" v-if="allDone">All done</span>
        </div>

    </div>

    <script type="text/javascript">
        var app = new Vue({
            el: '#bundleapp',
            data: function () {
                return {
                    title: "Update prepared",
                    progress: {
                        value: 0,
                        max: {{(int)($deleteJobs + $updateJobs)}}
                    },
                    interruptQueue: true,
                    isRunning: false,
                    stopButtonText: 'Stop',
                    startButtonText: 'Start Process'

                }
            },

            computed: {
                allDone: function () {
                    return this.progress.max === this.progress.value;
                }

            },


            methods: {

                stopRunning: function () {
                    this.interruptQueue = true;
                    this.stopButtonText = "Going to stop";
                },

                processStopped: function () {
                    this.interruptQueue  = false;
                    this.isRunning       = false;
                    this.startButtonText = 'Continue Process again';
                },

                processIsRunning: function () {
                    this.isRunning      = true;
                    this.interruptQueue = false;
                    this.stopButtonText = "Stop";
                },

                continueRunning: function () {
                    return this.interruptQueue === false && this.progress.max > this.progress.value;
                },


                runNextJobs: function () {
                    var self = this;

                    this.processIsRunning();

                    axios.get('{{route('pool.bundles.update.run', ['bundle' => $bundle->id])}}')
                        .then(function (response) {

                            // console.log(response);

                            var data            = response.data;
                            self.progress.value = self.progress.value + data.done;

                            if (self.progress.max <= self.progress.value + data.open) {
                                self.progress.max = self.progress.value + data.open;
                            }
                        }).then(function () {

                        if (self.continueRunning()) {
                            self.runNextJobs();
                        } else {
                            self.processStopped();
                        }

                    }).catch(function (error) {
                        console.log(error);
                        self.stopRunning();
                        self.processStopped();
                    });
                }
            }
        });


    </script>


@endsection
