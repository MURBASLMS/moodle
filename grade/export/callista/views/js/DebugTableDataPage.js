//copyright 2012 onwards University of New England
var debugTableDataPageJs = {
    initialise: function(Y) {
        Y.one('#batchRecords').delegate('click',
                                        function(e) {
                                            var otherEndNode = this.siblings('td.collapseControl');
                                            var rowDivs = this.ancestor('tr', false).all('td > div');

                                            if(this.getHTML() === '-') {
                                                this.setStyle('height', 60).setHTML('+');
                                                otherEndNode.setStyle('height', 60).setHTML('+');
                                                rowDivs.setStyle('height', 60);
                                            } else {
                                                this.setStyle('height', 'auto').setHTML('-');
                                                otherEndNode.setStyle('height', 'auto').setHTML('-');
                                                rowDivs.setStyle('height', 'auto');
                                            }
                                        },
                                        'tbody > tr > td.collapseControl');

        Y.one('#markRecords').delegate('click',
                                       function(e) {
                                           var otherEndNode = this.siblings('td.collapseControl');
                                           var batchRowsToToggleSelector = 'tr.batchRow' + this.siblings('td.c2', false).getHTML();

                                           if(this.getHTML() === '+') {
                                               this.setAttribute('rowspan', this.getData('expandedRowspan')).setHTML('-');
                                               otherEndNode.setAttribute('rowspan', otherEndNode.getData('expandedRowspan')).setHTML('-');
                                               this.ancestor('tr', false).siblings(batchRowsToToggleSelector).show();
                                           } else {
                                               this.setData('expandedRowspan', this.getAttribute('rowspan'));
                                               otherEndNode.setData('expandedRowspan', otherEndNode.getAttribute('rowspan'));
                                               this.setAttribute('rowspan', 1).setHTML('+');
                                               otherEndNode.setAttribute('rowspan', 1).setHTML('+');
                                               this.ancestor('tr', false).siblings(batchRowsToToggleSelector).hide();
                                           }
                                       },
                                       'tbody > tr > td.collapseControl');

        Y.all('#markRecords tr > td.collapseControl.lastcol').each(
                                       function(node) {
                                           var otherEndNode = node.siblings('td.collapseControl');
                                           var batchRowsToToggleSelector = 'tr.batchRow' + node.siblings('td.c2', false).getHTML();

                                           node.setData('expandedRowspan', node.getAttribute('rowspan'));
                                           otherEndNode.setData('expandedRowspan', otherEndNode.getAttribute('rowspan'));
                                           node.setAttribute('rowspan', 1).setHTML('+');
                                           otherEndNode.setAttribute('rowspan', 1).setHTML('+');
                                           node.ancestor('tr', false).siblings(batchRowsToToggleSelector).hide();
                                       });
    }
};