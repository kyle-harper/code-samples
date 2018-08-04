<template>
    <table class="table table-striped table-bordered" cellspacing="0" width="100%">
        <thead v-if="this.options.showHeader">
            <tr>
                <th v-for="column in this.options.columns"><span v-html="column.header"></span></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot v-if="this.options.showFooter">
            <th v-for="column in this.options.columns"><span v-html="column.header"></span></th>
        </tfoot>
    </table>
</template>

<script>
    export default {
        props: ['name', 'options'],
        methods: {
            reload(url) {
                if (url) {
                    KPH.dataTables[this.name].table.ajax.url(url);
                }
                KPH.dataTables[this.name].table.ajax.reload();
            },

            selected() {
                return KPH.dataTables[this.name].selected;
            },

            updateDataTableSelectAllCtrl() {
                let $table = this.$el,
                    $chkbox_all = $('tbody input[type="checkbox"]', $table),
                    $chkbox_checked = $('tbody input[type="checkbox"]:checked', $table),
                    chkbox_select_all = $('thead input[name="select-all"]', $table).get(0);

                // If none of the checkboxes are checked
                if($chkbox_checked.length === 0){
                    chkbox_select_all.checked = false;
                    if('indeterminate' in chkbox_select_all){
                        chkbox_select_all.indeterminate = false;
                    }

                // If all of the checkboxes are checked
                } else if ($chkbox_checked.length === $chkbox_all.length){
                    chkbox_select_all.checked = true;
                    if('indeterminate' in chkbox_select_all){
                        chkbox_select_all.indeterminate = false;
                    }

                // If some of the checkboxes are checked
                } else {
                    chkbox_select_all.checked = true;
                    if('indeterminate' in chkbox_select_all){
                        chkbox_select_all.indeterminate = true;
                    }
                }
            }
        },
        created() {
            // ...
        },
        mounted() {
            // console.log('DataTable Component mounted.')
            let vm = this;
            KPH.dataTables[this.name] = {};
            // convert the ajax property to an object if it isn't already, so we can expose the error callback
            if (typeof this.options.ajax === 'string') {
                this.options.ajax = {
                    url: this.options.ajax
                }
            }
            // set the default error handler on the ajax if one is not provided with the component instance
            if (!this.options.ajax.error) {
                this.options.ajax.error = function(jqXHR, statusText, error) {
                    console.error("DATATABLES AJAX ERROR:", statusText, error);
                    // if the error is due to session expiration, redirect user to login page
                    if (['unauthorized', 'unknown status'].indexOf(error.toLowerCase()) > -1) {
                        KPH.redirectLogin();
                    }
                }
            }

            // if the table needs to be selectable, set some additional parameters prior to instantiation
            if (this.options.selectable) {
                // instantiate the selected array for holding selected rows in state
                KPH.dataTables[this.name].selected = [];

                // push a column in on the left to handle the selectability
                Array.forEach(this.options.columnDefs, function(colDef, index) {
                    if (!Array.isArray(colDef.targets)) {
                        colDef.targets = [colDef.targets];
                    }
                    vm.options.columnDefs[index].targets = colDef.targets.map(colIndex => colIndex + 1);
                });
                this.options.columns.unshift({
                    data: null,
                    header: '<input name="select-all" value="1" type="checkbox">'
                });
                this.options.columnDefs.unshift({
                    targets: 0,
                    width: '1%',
                    searchable: false,
                    orderable: false,
                    className: 'text-center',
                    render: function (data, type, full, meta){
                        return '<div class="pretty p-round p-fill"><input type="checkbox"><div class="state p-warning nudge-dt-checkbox"><label>&nbsp;</label></div></div>';
                    }
                });

                // set a default rowCallback if one is not already provided
                this.options.rowCallback = this.options.rowCallback ?
                    this.options.rowCallback :
                    function(row, data) {
                        if ( $.inArray(data.DT_RowId, KPH.dataTables[vm.name].selected) !== -1 ) {
                            $(row).addClass('selected');
                        }
                    };

                // handle select all/none
                $(this.$el).on('click', 'td:first-child, thead th:first-child', function(e) {
                    $(this).parent().find('input[type="checkbox"]').trigger('click');
                    e.stopPropagation();
                });

                // handle row selection
                $(this.$el).find('tbody').on('click', 'input[type="checkbox"]', function(e){
                    let dt = KPH.dataTables[vm.name],
                        row = this.closest('tr'),
                        data = dt.table.row(row).data(),
                        id = data.id,
                        selected = dt.selected,
                        index = $.inArray(id, selected);

                    if (index === -1) {
                        selected.push(id);
                    } else {
                        selected.splice(index, 1);
                    }

                    $(row).toggleClass('selected');
                    vm.updateDataTableSelectAllCtrl();
                    e.stopPropagation();
                });
            }

            // instantiate the DataTable
            if (this.options.selectable) {
                // if the table is selectable, wait a beat before instantiating to allow the config to catch up with itself
                // TODO: isolate the reason(s) this is needed and refactor into a Promise.all situation.
                //       it seems to be due to pushing the new column into the options above, but that is blocking so shouldn't
                //       be causing async race conditions...
                setTimeout(function() {
                    KPH.dataTables[vm.name].table = $(vm.$el).DataTable(vm.options);
                    // Handle table draw event
                    $(this.$el).on('draw', function(){
                        vm.updateDataTableSelectAllCtrl();
                    });
                    // Handle click on "Select all" control
                    $(vm.$el).find('thead input[name="select-all"]').on('click', function(e){
                        if(this.checked){
                            $(vm.$el).find('tbody input[type="checkbox"]:not(:checked)').trigger('click');
                        } else {
                            $(vm.$el).find('tbody input[type="checkbox"]:checked').trigger('click');
                        }

                        // Prevent click event from propagating to parent
                        e.stopPropagation();
                    });
                }, 500);
            } else {
                KPH.dataTables[this.name].table = $(this.$el).DataTable(this.options);
            }
        }
    }
</script>

<style>
  .clickable {
    cursor:pointer;
  }
  .nudge-dt-checkbox {
    margin-left: -19px;
  }
</style>