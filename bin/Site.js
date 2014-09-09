
/**
 * Site History Control
 * Display the history of a site. You can compare and restore older versions of a site.
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define([

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'qui/controls/windows/Popup',
    'qui/controls/windows/Confirm',
    'controls/grid/Grid',

    'Ajax',
    'Locale',

    'css!package/quiqqer/history/bin/Site.css'

], function(QUI, QUIControl, QUILoader, QUIWindow, QUIConfirm, Grid, Ajax, Locale)
{
    "use strict";

    return new Class({

        Extends : QUIControl,
        Type    : 'quiqqer/history/bin/Site',

        Binds : [
            '$onInject',
            'openPreview',
            'openCompare',
            'openRestore'
        ],

        options : {
            project : false,
            lang    : false,
            id      : false
        },

        initialize : function(options)
        {
            this.parent( options );

            this.Loader = new QUILoader();
            this.$Grid  = null;

            this.addEvents({
                onInject : this.$onInject
            });

            if ( typeof options.Site === 'undefined' ) {
                return;
            }

            var Site    = options.Site,
                Project = Site.getProject();

            this.setAttribute( 'project', Project.getName() );
            this.setAttribute( 'lang', Project.getLang() );
            this.setAttribute( 'id', Site.getId() );
        },

        /**
         * Return the DOMNode Element
         *
         * @return {DOMNode}
         */
        create : function()
        {
            var self = this;

            this.$Elm = new Element('div', {
                'class' : 'quiqqer-history-site qui-box'
            });

            this.Loader.inject( this.$Elm );

            var Container = new Element('div', {
                'class' : 'quiqqer-history-site-container qui-box'
            }).inject( this.$Elm );

            this.$Grid = new Grid(Container, {
                columnModel : [{
                    header    : '&nbsp;',
                    dataIndex : 'select1',
                    dataType  : 'node',
                    width     : 50
                }, {
                    header    : '&nbsp;',
                    dataIndex : 'select2',
                    dataType  : 'node',
                    width     : 50
                }, {
                    header    : 'Datum',
                    dataIndex : 'created',
                    dataType  : 'string',
                    width     : 200
                }, {
                    header    : 'Benutzer',
                    dataIndex : 'username',
                    dataType  : 'string',
                    width     : 100
                }, {
                    header    : 'Benutzer-ID',
                    dataIndex : 'uid',
                    dataType  : 'string',
                    width     : 100
                }],

                buttons : [{
                    name : 'compare',
                    text : 'Zwei Versionen Vergleichen',
                    disabled : true,
                    events : {
                        onClick : self.openCompare
                    }
                }, {
                    type : 'seperator'
                }, {
                    name : 'preview',
                    text : 'Version anzeigen',
                    disabled  : true,
                    textimage : 'icon-eye-open',
                    events : {
                        onClick : self.openPreview
                    }
                }, {
                    name : 'revert',
                    text : 'Version zurückspielen',
                    disabled  : true,
                    textimage : 'icon-retweet',
                    events : {
                        onClick : this.openRestore
                    }
                }],
                pagination : false
            });

            this.$Grid.addEvents({
                onClick : function() {
                    self.$refreshGridButtons();
                }
            });

            return this.$Elm;
        },

        /**
         * Resize the control
         */
        resize : function()
        {
            var elmSize = this.$Elm.getSize();

            this.$Grid.setHeight( elmSize.y );
            this.$Grid.setWidth( elmSize.x );
        },

        /**
         * load the data
         */
        load : function()
        {
            var self = this;

            this.Loader.show();

            Ajax.get('package_quiqqer_history_ajax_list', function(result)
            {
                var id = self.getId();

                var inputClick = function() {
                    self.$refreshGridButtons();
                }

                for ( var i = 0, len = result.length; i < len; i++ )
                {
                    result[ i ].select1 = new Element('input', {
                        type   : 'radio',
                        name   : id +'_select1',
                        value  : result[ i ].created,
                        events : {
                            click : inputClick
                        }
                    });

                    result[ i ].select2 = new Element('input', {
                        type   : 'radio',
                        name   : id +'_select2',
                        value  : result[ i ].created,
                        events : {
                            click : inputClick
                        }
                    });
                }


                self.$Grid.setData({
                    data : result
                });

                self.Loader.hide();
                self.resize();

            }, {
                'package' : 'quiqqer/history',
                project   : this.getAttribute( 'project' ),
                lang      : this.getAttribute( 'lang' ),
                id        : this.getAttribute( 'id' )
            });
        },

        /**
         * event : on inject
         */
        $onInject : function()
        {
            this.load();
        },

        /**
         * refresh the buttons, disable or enable the grid buttons
         */
        $refreshGridButtons : function()
        {
            var buttons = this.$Grid.getButtons(),
                sels    = this.$Grid.getSelectedIndices(),
                id1     = this.getId() +'_select1',
                id2     = this.getId() +'_select2';

            var Radio1 = this.$Elm.getElement( 'input[name="'+ id1 +'"]:checked' ),
                Radio2 = this.$Elm.getElement( 'input[name="'+ id2 +'"]:checked' );

            // buttons
            var Compare = null,
                Preview = null,
                Revert  = null;

            for ( var i = 0, len = buttons.length; i < len; i++ )
            {
                if ( buttons[ i ].getAttribute( 'name' ) == 'compare' )
                {
                    Compare = buttons[ i ];
                    continue;
                }

                if ( buttons[ i ].getAttribute( 'name' ) == 'preview' )
                {
                    Preview = buttons[ i ];
                    continue;
                }

                if ( buttons[ i ].getAttribute( 'name' ) == 'revert' )
                {
                    Revert = buttons[ i ];
                    continue;
                }
            }


            if ( Radio1 && Radio2 )
            {
                Compare.enable();
            } else
            {
                Compare.disable();
            }


            if ( sels.length == 1 )
            {
                Preview.enable();
                Revert.enable();

            } else
            {
                Preview.disable();
                Revert.disable();
            }
        },


        /**
         * window methods
         */

        /**
         * open a preview window
         */
        openPreview : function()
        {
            var self = this,
                size = document.body.getSize(),
                data = this.$Grid.getSelectedData();

            if ( !data.length ) {
                return;
            }

            new QUIWindow({
                maxWidth  : size.x - 60,
                maxHeight : size.y - 60,
                events :
                {
                    onOpen : function(Win)
                    {
                        Win.Loader.show();

                        self.preview( data[0].created, function(result)
                        {
                            var Frame = new Element('iframe', {
                                styles : {
                                    height : '100%',
                                    width  : '100%',
                                    border : '0px'
                                }
                            }).inject( Win.getContent() );

                            Frame.contentWindow.document.open();
                            Frame.contentWindow.document.write( result );
                            Frame.contentWindow.document.close();

                            Win.Loader.hide();
                        });
                    }
                }
            }).open();
        },

        /**
         * open the comparison window
         */
        openCompare : function()
        {
            var self = this,
                size = document.body.getSize(),
                id1  = this.getId() +'_select1',
                id2  = this.getId() +'_select2';

            var Radio1 = this.$Elm.getElement( 'input[name="'+ id1 +'"]:checked' ),
                Radio2 = this.$Elm.getElement( 'input[name="'+ id2 +'"]:checked' );

            if ( !Radio1 || !Radio2 ) {
                return;
            }

            new QUIWindow({
                maxWidth  : size.x - 60,
                maxHeight : size.y - 60,
                events :
                {
                    onOpen : function(Win)
                    {
                        Win.Loader.show();

                        self.compare( Radio1.value, Radio2.value, function(result)
                        {
                            var Frame = new Element('iframe', {
                                styles : {
                                    height : '100%',
                                    width  : '100%',
                                    border : '0px'
                                }
                            }).inject( Win.getContent() );

                            Frame.contentWindow.document.open();
                            Frame.contentWindow.document.write( result );
                            Frame.contentWindow.document.close();

                            Win.Loader.hide();
                        });
                    }
                }
            }).open();
        },

        /**
         * open the restore window
         */
        openRestore : function()
        {
            var self = this,
                size = document.body.getSize(),
                data = this.$Grid.getSelectedData();

            if ( !data.length ) {
                return;
            }

            new QUIConfirm({
                title  : 'Archiveintrag zurück spielen?',
                icon   : 'icon-retweet',
                text   : 'Archiveintrag zurück spielen?',
                information  : 'Möchten sie den Archiveintrag vom '+ data[ 0 ].created +' wirklich zurück spielen?',
                texticon : 'icon-retweet',
                events :
                {
                    onSubmit : function(Win)
                    {
                        Win.Loader.show();

                        self.restore( data[ 0 ].created, function(result)
                        {

                        });
                    }
                }
            }).open();
        },

        /**
         * methods
         */

        /**
         * compare two history entries
         *
         * @param {String} date1 - date of the first history entry
         * @param {String} date2 - date of the second history entry
         * @param {Function} callback - callback function after finish
         */
        compare : function(date1, date2, callback)
        {
            Ajax.get('package_quiqqer_history_ajax_compare', function(result)
            {
                callback( result );
            }, {
                'package' : 'quiqqer/history',
                project   : this.getAttribute( 'project' ),
                lang      : this.getAttribute( 'lang' ),
                id        : this.getAttribute( 'id' ),
                date1     : date1,
                date2     : date2
            });
        },

        /**
         * restore a history version of the site
         *
         * @param {String} date - date of the history entry
         * @param {Function} callback - callback function after finish
         */
        restore : function(date, callback)
        {
            var self = this;

            Ajax.get('package_quiqqer_history_ajax_restore', function(result)
            {
                // refresh the site
                require(['Projects'], function(Projects)
                {
                    var Project = Projects.get(
                        self.getAttribute('project'),
                        self.getAttribute('lang')
                    );

                    Project.get( self.getAttribute( 'id' ) ).load();
                });

                self.load();

                callback( result );
            }, {
                'package' : 'quiqqer/history',
                project   : this.getAttribute( 'project' ),
                lang      : this.getAttribute( 'lang' ),
                id        : this.getAttribute( 'id' ),
                date      : date
            });
        },

        /**
         * return a html preview of a history entry from the site
         *
         * @param {String} date - date of the history entry
         * @param {Function} callback - callback function after finish
         */
        preview : function(date, callback)
        {
            Ajax.get('package_quiqqer_history_ajax_preview', function(result)
            {
                callback( result );
            }, {
                'package' : 'quiqqer/history',
                project   : this.getAttribute( 'project' ),
                lang      : this.getAttribute( 'lang' ),
                id        : this.getAttribute( 'id' ),
                date      : date
            });
        }

    });

});