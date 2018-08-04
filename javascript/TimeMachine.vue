<template>
    <div>
        <div v-show="model.timeMachine && model.timeMachine.length">
            <div class="form-group clearfix">
                <div class="floating-unit">
                    <a href="#" :class="historyButtonCss" @click.prevent="toggleTimeMachine" title="Time Machine (View Edit History)">
                        <i class="fa fa-history"></i>
                    </a>
                </div>
                <div class="col-sm-6 floating-unit" v-show="showTimeMachine">
                    <select :value="value" @input="updateTimeMachineTarget" ref="timeMachineSelector" class="form-control input-md" :autofocus="!disabled" :disabled="disabled">
                      <option selected value="">Latest</option>
                      <option v-for="edit in model.timeMachine" :value="edit.id">{{ versionName(edit) }}</option>
                      <option value="-1">Original</option>
                    </select>
                </div>
                <div class="floating-unit" v-show="showTimeMachine">
                    <span v-if="disabled" class="wait-text">
                        <em>Loading...&nbsp;&nbsp;&nbsp;<i class="fa fa-spinner fa-spin"></i></em>
                    </span>
                </div>
            </div>
            <div class="alert alert-warning" v-show="viewingHistoricalVersion">
                <i class="fa fa-exclamation-triangle"></i> <span v-html="alertMessage"></span>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data: function() {
            return {
                showTimeMachine: false,
                viewingHistoricalVersion: false,
                alertMessage: '',
                historyButtonCss: 'btn'
            }
        },
        props: ['model', 'value', 'disabled'],
        watch: {
            // TODO: is this an appropriate use of a watcher?
            // I need to listen for a change from the parent, and this works.
            value: function(val) {
                this.viewingHistoricalVersion = !!val;
            }
        },
        methods: {
            /**
             * Show/Hide time machine history of edits.
             */
            toggleTimeMachine() {
                let vm = this;
                this.showTimeMachine = !this.showTimeMachine;
                if (this.showTimeMachine) {
                    this.historyButtonCss = 'btn btn-warning';
                    setTimeout(function(){vm.focus();}, 50);
                } else {
                    this.historyButtonCss = 'btn';
                }
            },

            /**
             * Update the time machine target.
             */
            updateTimeMachineTarget(e) {
                // update the component state
                const edit = _.find(this.model.timeMachine, o => { return o.id == this.$refs.timeMachineSelector.value; });
                const editDate = edit && edit.version_date ? moment(edit.version_date).format('dddd, MMMM, Do YYYY, h:mm:ss a') : null;
                this.viewingHistoricalVersion = !!this.$refs.timeMachineSelector.value;
                if (this.viewingHistoricalVersion) {
                    if (editDate) {
                        this.alertMessage = `You are viewing a historical version of this record, edited by ${edit.editor} on ${editDate}. Please save your changes to restore this as the active version.`;
                    } else {
                        this.alertMessage = `You are viewing the original record, as it was initially created in the system. Please save your changes to restore this as the active version.`;
                    }
                } else {
                    this.alertMessage = `You are viewing the latest version of this record.`;
                }
                // emit the events
                this.$emit('input', this.$refs.timeMachineSelector.value);
                this.$emit('change', this.$refs.timeMachineSelector.value);
            },

            versionName(edit) {
                let editDate = moment(edit.version_date).calendar();
                if (editDate.match(/\d{2}\/\d{2}\/\d{4}/)) {
                    editDate = moment(edit.version_date).format('dddd, MMMM, Do YYYY, h:mm:ss a');
                }
                return `${editDate} (${edit.editor})`;
            },

            /**
             * Set focus on the selector.
             */
            focus() {
                this.$refs.timeMachineSelector.focus();
            }
        },
        created() {
            let vm = this;
        },
        mounted() {
            // ...
        }
    }
</script>

<style>
  .floating-unit {
    display: inline-block;
    float: right;
  }
  .wait-text {
      line-height: 40px;
  }
</style>