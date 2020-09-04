//copyright 2012 onwards University of New England
var WebServiceErrorPageJs = {
    
    initialise: function(Y) {
        Y.one('#newBatchFromThisBatch').on('click', function(e) {
            Y.one('#newBatchForm').getDOMNode().submit();
        });
    },
}
