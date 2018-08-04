<template>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Tags
                    </h3>
                </div>
                <div class="card-content">
                    <form @submit.prevent="storeNewTags">
                        <div class="form-group">
                            <multiselect
                                v-model="tags"
                                tag-placeholder="Add this as a new tag"
                                placeholder="Search or add a tag"
                                label="value"
                                track-by="id"
                                :options="tagOptions"
                                :multiple="true"
                                :taggable="true"
                                @tag="addTag"
                                @input="tagsDirty = true"
                            ></multiselect>
                        </div>
                        <div class="form-group">
                            <div class="btn-placeholder">
                                <transition name="fade">
                                    <div class="text-center" v-if="tagsDirty">
                                        <button type="submit" class="btn btn-info btn-fill btn-wd">Update Tags</button>
                                    </div>
                                </transition>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['model', 'id'],
        data: function() {
            return {
                tagUrl: [Laravel.appUrl, 'tags'].join('/'),
                tagOptionsUrl: [Laravel.appUrl, 'tags?f=multiselect'].join('/'),
                tagStoreUrl: [Laravel.appUrl, 'tags'].join('/'),
                tagOptions: [],
                tags: [],
                tagsDirty: false
            };
        },
        methods: {
            addTag(newTag) {
              const tag = {
                value: newTag,
                id: newTag.substring(0, 2) + Math.floor((Math.random() * 10000000))
              }
              this.tagOptions.push(tag);
              this.tags.push(tag);
              this.tagsDirty = true;
            },
            clearTags() {
                this.tags = [];
            },
            fetchTags() {
                axios.get(this.tagUrl)
                 .then(response => { this.tags = response.data })
                 .catch(error => { console.error('ERROR FETCHING TAGS') });
            },
            fetchTagOptions() {
                axios.get(this.tagOptionsUrl)
                 .then(response => { this.tagOptions = response.data })
                 .catch(error => { console.error('ERROR FETCHING TAG OPTIONS') });
            },
            storeNewTags() {
                let links = [];
                this.tagsDirty = false;
                if (this.model && this.id) {
                    links.push({
                        model: this.model,
                        id: this.id
                    });
                }
                axios.post(this.tagStoreUrl, {
                      tags: this.tags,
                      links: links
                     })
                     .then(response => KPH.notify(response.data))
                     .catch(error => KPH.notifyError(error));
            }
        },
        mounted() {
            // console.log('Tags component mounted');
            // fetch the tag options available in the database
            this.fetchTagOptions();
            // fetch the tags for this item
            if (this.model) {
                this.tagUrl = [this.tagUrl, this.model].join('/');
                if (this.id) {
                    this.tagUrl = [this.tagUrl, this.id].join('/');
                }
            }
            this.tagUrl += '?f=component';
            this.fetchTags();
        }
    };
</script>