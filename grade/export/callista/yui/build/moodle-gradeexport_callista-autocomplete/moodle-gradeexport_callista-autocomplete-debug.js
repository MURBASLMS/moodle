YUI.add('moodle-gradeexport_callista-autocomplete', function (Y, NAME) {

M.gradeexport_callista = M.gradeexport_callista || {};
M.gradeexport_callista.autocomplete = {

    init: function($source) {
        YUI().use('autocomplete', function (Y) {
            Y.all('input.markoverridecombo').plug(Y.Plugin.AutoComplete, {
                source: [$source],
                render: false,
                minQueryLength: 0,
                tabSelect: true
            });

            Y.all('input.markoverridecombo').on('focus', function(e) {
                e.currentTarget.ac.render();
                e.currentTarget.ac.sendRequest('');
            });
        })
    }
}

}, '@VERSION@');
