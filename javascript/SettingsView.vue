<template>
    <div class="col-sm-10 col-sm-offset-1">
        <div class="h2-placeholder">
            <h2>Settings</h2>
        </div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
              <li class="breadcrumb-item">
                  <router-link to="../profile" class="btn-link">
                      My Profile
                  </router-link>
              </li>
              <li class="breadcrumb-item active" aria-current="page">
                  Settings
              </li>
          </ol>
        </nav>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title clearfix">
                            Notification Preferences
                        </h3>
                    </div>
                    <div class="card-content">
                        <form @submit.prevent="updateSettings" @keyup="forms.notifications.dirty = true">
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <label>
                                            Email Updates
                                            &nbsp;
                                            <help-icon
                                                message="Set to ON if you want to receive periodic email updates."
                                            ></help-icon>
                                        </label>
                                    </div>
                                    <div class="col-sm-2">
                                        <bootstrap-switch
                                            v-model="settings.notifications.notify_email"
                                            :options="{onText: 'ON', onValue: 1, onColor: 'success', offText: 'OFF', offColor: 'default', offValue: 0}"
                                            @change="dirtyForm('notifications')"
                                        ></bootstrap-switch>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <label>
                                            SMS Alerts
                                            &nbsp;
                                            <help-icon
                                                message="Set to ON if you want to receive alerts via SMS (text messages)."
                                            ></help-icon>
                                        </label>
                                    </div>
                                    <div class="col-sm-2">
                                        <bootstrap-switch
                                            v-model="settings.notifications.notify_sms"
                                            :options="{onText: 'ON', onValue: 1, onColor: 'success', offText: 'OFF', offColor: 'default', offValue: 0}"
                                            @change="dirtyForm('notifications')"
                                        ></bootstrap-switch>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center btn-placeholder">
                                <transition name="fade">
                                    <button type="submit" v-show="forms.notifications.dirty" class="btn btn-info btn-fill btn-wd" :disabled="forms.notifications.updating">Update Settings</button>
                                </transition>
                            </div>
                            <div class="clearfix"></div>
                        </form>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title clearfix">
                            Newsfeed Preferences
                        </h3>
                    </div>
                    <div class="card-content">
                        <form @submit.prevent="updateSettings" @keyup="forms.newsfeed.dirty = true">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Default Time Period to Show</label>
                                        <input type="text" class="form-control border-input" placeholder="e.g., 30 days" v-model="settings.newsfeed.newsfeed_default_time_period">
                                    </div>
                                </div>
                            </div>
                            <div class="text-center btn-placeholder">
                                <transition name="fade">
                                    <button type="submit" v-show="forms.newsfeed.dirty" class="btn btn-info btn-fill btn-wd" :disabled="forms.newsfeed.updating">Update Settings</button>
                                </transition>
                            </div>
                            <div class="clearfix"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data: function() {
            return {
                settings: {
                    notifications: {
                        notify_email: 0,
                        notify_sms: 0
                    },
                    newsfeed: {
                        newsfeed_default_time_period: ''
                    }
                },
                forms: {
                    notifications: {
                        dirty: false,
                        updating: false
                    },
                    newsfeed: {
                        dirty: false,
                        updating: false
                    }
                },
                wait: true
            }
        },
        methods: {
            /**
             * Mark the specified form as dirty.
             */
            dirtyForm(formName) {
                if (!this.wait) {
                    this.forms[formName].dirty = true;
                }
            },

            /**
             * Get the user settings.
             */
            getSettings() {
                return axios.get([Laravel.appUrl, 'profile/settings'].join('/'))
                    .then(response => this.settings = response.data)
                    .catch(error => KPH.notifyError(error));
            },

            /**
             * Post the updates to the user settings.
             * Can handle subform-specific updates or blanket update
             */
            updateSettings(formName) {
                let payload;
                const singleFormMode = formName && typeof formName === 'string';
                // console.log("UPDATING SETTINGS...", formName, singleFormMode);
                if (singleFormMode) {
                    payload = {};
                    payload[formName] = this.settings[formName];
                } else {
                    payload = this.settings;
                }
                return axios.put([Laravel.appUrl, 'profile/settings'].join('/'), payload)
                            .then(response => {
                                if (singleFormMode) {
                                    this.forms[formName].updating = false;
                                    this.forms[formName].dirty = false;
                                } else {
                                    for (const [key, value] of Object.entries(this.forms)) {
                                        value.dirty = false;
                                        value.updating = false;
                                    }
                                }
                                KPH.notify(response.data);
                           })
                           .catch(error => {
                                let vm = this;
                                if (formName) {
                                    this.forms[formName].updating = false;
                                    this.forms[formName].dirty = false;
                                } else {
                                    for (const [key, value] of Object.entries(this.forms)) {
                                        vm.forms.key.dirty = false;
                                        vm.forms.key.updating = false;
                                    }
                                }
                                KPH.notifyError(error);
                           });
            }
        },
        created() {
            let vm = this;
            this.getSettings().then(function() {
                // give the page a sec to react to the new alert object, then remove the wait overlay
                setTimeout(function() { vm.wait = false; }, 500);
            });
        },
        mounted() {
            // ...
        }
    }
</script>
