//copyright 2012 onwards University of New England
var queuedPageJs = {
    
    initialise: function(Y) {
        Y.one('#instructionHeading').on('click', function(e) {
            e.preventDefault();
            Y.one('#instructions').toggleView();
            Y.all('#instructionHeading > img').toggleView();
        });
        
        //Set the minimum width of the table heading cells so that when the heading is made invisible, the table doesn't change dimensions.
        Y.all('.sortingLink').each(function() {
            var parentNode = this.ancestor('th');
            parentNode.setStyle('minWidth', parentNode.getComputedStyle('width'));
        });
        
        Y.one('#markstable>thead>tr').delegate('click', function(e) {
            e.preventDefault();
            
            var q = new Y.AsyncQueue(
                {
                    fn:         queuedPageJs.showBusyIcon,
                    context:    this,
                    args:       new Array(Y)
                },
                {
                    fn:         queuedPageJs.sortTable,
                    context:    this,
                    args:       new Array(Y)
                });
            q.run();
        }, '.sortingLink');

        Y.all('.emulateCron').on('click', function(e) {
            Y.one('#emulateForm').getDOMNode().submit();
        });
    },

    showBusyIcon: function(Y) {
        Y.one('#markstable').setStyle('cursor', 'wait');
        this.hide();
        this.ancestor('th').addClass('busyBackground');
    },

    sortTable: function(Y) {
        var columnSelector = '.' + this.get('id');
        var rowNodeList = Y.all('#markstable>tbody>tr');
        var nodeArray = new Array();
        for(var i = 0; i < rowNodeList.size(); i++) {
            nodeArray.push(rowNodeList.item(i));
        }
        nodeArray.sort(function(n1, n2) {
            var o1 = n1.one(columnSelector);
            var o2 = n2.one(columnSelector);
            var s1 = o1.get('innerText');
            var s2 = o2.get('innerText');

            // Browser detection
            var f = navigator.userAgent.search("Firefox");
            var ms_ie = false;
            var ua = window.navigator.userAgent;
            var old_ie = ua.indexOf('MSIE ');
            var new_ie = ua.indexOf('Trident/');

            if ((old_ie > -1) || (new_ie > -1)) {
                ms_ie = true;
            }
            if (f > -1) { // If FireFox
                o1 = o1._node;
                o2 = o2._node;
                s1 = o1.innerHTML;
                s2 = o2.innerHTML;
            }
            var f1 = parseFloat(s1);
            var f2 = parseFloat(s2);

            // If not a float / int, reprocess if an object like select / input.
            if (isNaN(f1)) {
                if (typeof o1 == 'object') {
                    if ((ms_ie && o1._node.children.length > 0) || (f > -1  && o1.children.length > 0)) { // If Firefox or IE
                        if (ms_ie) {
                            s1 = o1._node.children[0].value;
                            s2 = o2._node.children[0].value;
                        } else {
                            s1 = o1.children[0].value;
                            s2 = o2.children[0].value;
                        }
                        f1 = parseFloat(s1);
                        f2 = parseFloat(s2);
                    } else {
                        if (o1.hasChildNodes() && s1.length == 0) { // Chrome, IE and Safari
                            s1 = o1.get('children')._nodes[0].value;
                            s2 = o2.get('children')._nodes[0].value;
                            f1 = parseFloat(s1);
                            f2 = parseFloat(s2);
                        }
                    }
                }
            }

            // Process float / int numerically
            if (!isNaN(f1) || !isNaN(f2)) {
                if (f2 > f1 || isNaN(f2)) {
                    return -1;
                } else {
                    return 1;
                }
            }

            return s1.length == 0 ? 1 : s2.length == 0 ? -1 : s1.localeCompare(s2);
        });

        var sortAscending = !this.hasClass('ascending');
        var sortableLinks = Y.all('.sortingLink');
        sortableLinks.removeClass('ascending');
        sortableLinks.removeClass('descending');
        if(sortAscending) {
            this.addClass('ascending');
        } else {
            nodeArray.reverse();
            this.addClass('descending');
        }

        rowNodeList.remove();
        var bodyNode = Y.one('#markstable>tbody');
        bodyNode.insert(new Y.NodeList(nodeArray), 0);

        Y.one('#markstable').setStyle('cursor', 'auto');
        this.ancestor('th').removeClass('busyBackground');
        this.show();
    }
}
