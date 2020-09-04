//copyright 2012 onwards University of New England
var overridePageJs = {
    allowedGrades: null,
    nomark: null,
    wellFormedMarkOverrideRegex: null,
    wellFormedGradeOverrideRegex: null,

    initialise: function(Y, malformedOverrideMessage, nomark) {
        // A mark override should either be empty, an integer, or a decimal number with up to 5 decimal places.
        this.wellFormedMarkOverrideRegex = new RegExp(nomark + '|(?:^$)|(?:^[0-9]+$)|(?:^[0-9]*$)');
        this.allowedGrades = ['HD', 'D', 'C', 'P', 'N', 'SA', 'SX', 'NA', 'DNS'];
        this.wellFormedGradeOverrideRegex = new RegExp('(?:^$)|(?:^' + this.allowedGrades.join('$)|(?:^') + '$)');
        this.nomark = nomark;

        Y.one('#instructionHeading').on('click', function(e) {
            e.preventDefault();
            Y.one('#instructions').toggleView();
            Y.all('#instructionHeading > img').toggleView();
        });

        // Run the Override CSS JS after Select / AutoComplete
        Y.one('#markstable').delegate('valuechange', function(e){
            overridePageJs.calculateMarkOverrideCss(Y, e.target);
        }, '.markoverride');

        // Run the Override CSS JS after KeyUp
        Y.one('#markstable').delegate('keyup', function(e){
            overridePageJs.calculateMarkOverrideCss(Y, e.target);
        }, '.markoverride');

        Y.one('#markstable').delegate('change', function(e) {
            overridePageJs.calculateGradeOverrideCss(Y, this);
        }, '.gradeoverride');

        Y.all('.saveButton').on('click', function(e) {
            var hasMalformedOverrides = false;
            Y.all('.markoverride').each(function(node) {
               if(!overridePageJs.wellFormedMarkOverrideRegex.test(node.get('value')) || node.get('value') > 100) {
                   hasMalformedOverrides = true;
               }
            });
            if(hasMalformedOverrides) {
                Y.all('#errorMessage').set('text', malformedOverrideMessage)
                Y.all('#errorMessage').show();
            } else {
                Y.one('#buttonClicked').getDOMNode().value = 'saveButton';
                Y.one('#sendToCallista').getDOMNode().submit();
            }
        });

        //Check for malformed overrides before sending the data.
        Y.all('.sendButton').on('click', function(e) {
            var hasMalformedOverrides = false;
            Y.all('.markoverride').each(function(node) {
               if(!overridePageJs.wellFormedMarkOverrideRegex.test(node.get('value'))|| node.get('value') > 100) {
                   hasMalformedOverrides = true;
               }
            });
            if(hasMalformedOverrides) {
                Y.all('#errorMessage').set('text', malformedOverrideMessage)
                Y.all('#errorMessage').show();
            } else {
                //submit the form
                Y.one('#buttonClicked').getDOMNode().value = 'sendButton';
                Y.one('#sendToCallista').getDOMNode().submit();
            }
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
                    fn:         overridePageJs.showBusyIcon,
                    context:    this,
                    args:       new Array(Y)
                },
                {
                    fn:         overridePageJs.sortTable,
                    context:    this,
                    args:       new Array(Y)
                });
            q.run();
            
        }, '.sortingLink');
    },
    
    /* Whenever the mark override textbox is changed (on the keyup event), we want to apply css styles to indicate whether the 
     * new data is in the correct format (the regex above) and whether the new data is different from Moodle's calculated mark.
     */
    calculateMarkOverrideCss: function(Y, markOverrideTextBox) {
        var tablecell = markOverrideTextBox.ancestor('td');
        var overrideValue = markOverrideTextBox.get('value');
        //Does the override match the correct format?
        if(overridePageJs.wellFormedMarkOverrideRegex.test(overrideValue) && (overrideValue == this.nomark || overrideValue <= 100)) {
            tablecell.removeClass('malformed');

            // Browser detection
            var f = navigator.userAgent.search("Firefox");

            //Check Moodle's calculated mark to see if the override will change anything.
            var element = 'innerText';
            if (f > -1) { // If FireFox
                element = 'innerHTML';
            }
            var calculatedMark = parseFloat(tablecell.previous('.calculatedmark').get(element)).toFixed(5);

            // Round Moodle's calculated mark and see if the same as the overridden value
            var roundedMark = parseFloat((Math.ceil(calculatedMark*100000)/100000).toFixed(0)).toFixed(5);

            // Round override value to 5 decimal places to match calculated Mark
            var roundedOverrideValue = parseFloat(overrideValue).toFixed(5);

            if (overrideValue != '') {
                // An mark override always shown as overridden
                tablecell.addClass('overridden');
                tablecell.removeClass('autooverridden');
            } else {
                tablecell.removeClass('overridden');
                tablecell.removeClass('autooverridden');
            }
        } else {
            /* The override is badly formated, so add the 'malformed' class. Remove the 'overridden' class so they don't 
                * interfere with each other.
                */
            tablecell.replaceClass('autooverridden', 'malformed');
            tablecell.replaceClass('overridden', 'malformed');
        }
    },
    
    /* Whenever the grade override is changed, check its new value. If the new value is not empty and not equal to the old 
     * value, the derived grade is being overridden, so we add the 'overridden' class to the table cell. Otherwise we are not
     * doing an override on the grade, so we remove the 'overridden' class.
     */
    calculateGradeOverrideCss: function(Y, gradeOverrideSelectBox) {
        var tablecell = gradeOverrideSelectBox.ancestor('td');
        var overrideValue = gradeOverrideSelectBox.get('value');
        var autooverrideValue = tablecell.get('lastChild').get('value');
        var derivedLetterGrade = tablecell.previous('.derivedgrade').get('innerText');

        if(overrideValue != '') {
            if (autooverrideValue == overrideValue) {
                tablecell.addClass('autooverridden');
                tablecell.removeClass('overridden');
            } else {
                tablecell.addClass('overridden');
                tablecell.removeClass('autooverridden');
            }
        } else {
            tablecell.removeClass('overridden');
            tablecell.removeClass('autooverridden');
        }
    },
    
    showBusyIcon: function(Y) {
        Y.one('#markstable').setStyle('cursor', 'wait');
        this.hide();
        this.ancestor('th').addClass('busyBackground');
    },
    
    sortTable: function(Y) {
        /* YUI's node list does not have a sort function. To sort it, the node list is converted to a javascript array.
         * This function reorders the rows of the table, not just the data within the rows.
         */
        //The heading link's id matches the class of the column being sorted. This makes it easy to use the javascript array's sort
        //function with a custom function.
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
            } else {
                // Remove newlines to force processing of input elements if not a float.
                s1 = s1.replace(/(\r\n|\n|\r)/gm, '');
                s2 = s2.replace(/(\r\n|\n|\r)/gm, '');
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

        //If the column is unsorted or descending, we want to sort it ascending. If the column is already ascending, we want to sort
        //it descending.
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

        //Remove the rows from the table, and then insert the rows in their sorted order.
        rowNodeList.remove();
        var bodyNode = Y.one('#markstable>tbody');
        bodyNode.insert(new Y.NodeList(nodeArray), 0);

        //undo the style changes to the column heading.
        Y.one('#markstable').setStyle('cursor', 'auto');
        this.ancestor('th').removeClass('busyBackground');
        this.show();
    }
};
