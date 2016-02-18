/**
 * Site History Control
 * Display the history of a site. You can compare and restore older versions of a site.
 *
 * @module package/quiqqer/history/bin/Site
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/loader/Loader
 * @require qui/controls/windows/Popup
 * @require qui/controls/windows/Confirm
 * @require controls/grid/Grid
 * @require Ajax
 * @require Locale
 * @require css!URL_OPT_DIR/quiqqer/history/bin/Site.css
 */

define('package/quiqqer/history/bin/Site', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'qui/controls/windows/Popup',
    'qui/controls/windows/Confirm',
    'controls/grid/Grid',

    'Ajax',
    'Locale',

    'css!URL_OPT_DIR/quiqqer/history/bin/Site.css'

], function (QUI, QUIControl, QUILoader, QUIWindow, QUIConfirm, Grid, Ajax, QUILocale) {
    "use strict";

    var lg = 'quiqqer/history';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/history/bin/Site',

        Binds: [
            '$onInject',
            'openPreview',
            'openCompare',
            'openRestore'
        ],

        options: {
            id  : false,
            Site: false
        },

        initialize: function (options) {
            this.parent(options);

            this.Loader = new QUILoader();
            this.$Grid  = null;

            this.addEvents({
                onInject: this.$onInject
            });

            if (typeof options.Site === 'undefined') {
                return;
            }

            this.$Site    = options.Site;
            this.$Project = this.$Site.getProject();
        },

        /**
         * Return the DOMNode Element
         *
         * @return {HTMLElement}
         */
        create: function () {
            var self = this;

            this.$Elm = new Element('div', {
                'class': 'quiqqer-history-site qui-box'
            });

            this.Loader.inject(this.$Elm);

            var Container = new Element('div', {
                'class': 'quiqqer-history-site-container qui-box'
            }).inject(this.$Elm);

            this.$Grid = new Grid(Container, {
                columnModel: [{
                    header   : '&nbsp;',
                    dataIndex: 'select1',
                    dataType : 'node',
                    width    : 50
                }, {
                    header   : '&nbsp;',
                    dataIndex: 'select2',
                    dataType : 'node',
                    width    : 50
                }, {
                    header   : QUILocale.get('quiqqer/system', 'c_date'),
                    dataIndex: 'created',
                    dataType : 'string',
                    width    : 200
                }, {
                    header   : QUILocale.get('quiqqer/system', 'c_user'),
                    dataIndex: 'username',
                    dataType : 'string',
                    width    : 100
                }, {
                    header   : QUILocale.get('quiqqer/system', 'user_id'),
                    dataIndex: 'uid',
                    dataType : 'string',
                    width    : 100
                }],

                buttons   : [{
                    name    : 'compare',
                    text    : QUILocale.get(lg, 'btn.compare.text'),
                    disabled: true,
                    events  : {
                        onClick: self.openCompare
                    }
                }, {
                    type: 'seperator'
                }, {
                    name     : 'preview',
                    text     : QUILocale.get(lg, 'btn.preview.text'),
                    disabled : true,
                    textimage: 'fa fa-eye',
                    events   : {
                        onClick: self.openPreview
                    }
                }, {
                    name     : 'revert',
                    text     : QUILocale.get(lg, 'btn.revert.text'),
                    disabled : true,
                    textimage: 'fa fa-retweet',
                    events   : {
                        onClick: this.openRestore
                    }
                }],
                pagination: false
            });

            this.$Grid.addEvents({
                onClick: function () {
                    self.$refreshGridButtons();
                }
            });

            return this.$Elm;
        },

        /**
         * Resize the control
         */
        resize: function () {
            var elmSize = this.$Elm.getSize();

            this.$Grid.setHeight(elmSize.y);
            this.$Grid.setWidth(elmSize.x);
            this.$Grid.resize();
        },

        /**
         * load the data
         */
        load: function () {
            var self = this;

            this.Loader.show();

            Ajax.get('package_quiqqer_history_ajax_list', function (result) {
                var id = self.getId();

                var inputClick = function () {
                    self.$refreshGridButtons();
                };

                for (var i = 0, len = result.length; i < len; i++) {
                    result[i].select1 = new Element('input', {
                        type  : 'radio',
                        name  : id + '_select1',
                        value : result[i].created,
                        events: {
                            click: inputClick
                        }
                    });

                    result[i].select2 = new Element('input', {
                        type  : 'radio',
                        name  : id + '_select2',
                        value : result[i].created,
                        events: {
                            click: inputClick
                        }
                    });
                }


                self.$Grid.setData({
                    data: result
                });

                self.Loader.hide();
                self.resize();

            }, {
                'package': 'quiqqer/history',
                project  : this.$Project.encode(),
                id       : this.$Site.getId()
            });
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.load();
        },

        /**
         * refresh the buttons, disable or enable the grid buttons
         */
        $refreshGridButtons: function () {
            var buttons = this.$Grid.getButtons(),
                sels    = this.$Grid.getSelectedIndices(),
                id1     = this.getId() + '_select1',
                id2     = this.getId() + '_select2';

            var Radio1 = this.$Elm.getElement('input[name="' + id1 + '"]:checked'),
                Radio2 = this.$Elm.getElement('input[name="' + id2 + '"]:checked');

            // buttons
            var Compare = null,
                Preview = null,
                Revert  = null;

            for (var i = 0, len = buttons.length; i < len; i++) {
                if (buttons[i].getAttribute('name') == 'compare') {
                    Compare = buttons[i];
                    continue;
                }

                if (buttons[i].getAttribute('name') == 'preview') {
                    Preview = buttons[i];
                    continue;
                }

                if (buttons[i].getAttribute('name') == 'revert') {
                    Revert = buttons[i];
                }
            }


            if (Radio1 && Radio2) {
                Compare.enable();
            } else {
                Compare.disable();
            }


            if (sels.length == 1) {
                Preview.enable();
                Revert.enable();

            } else {
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
        openPreview: function () {
            var self = this,
                size = document.body.getSize(),
                data = this.$Grid.getSelectedData();

            if (!data.length) {
                return;
            }

            new QUIWindow({
                maxWidth : size.x - 60,
                maxHeight: size.y - 60,
                events   : {
                    onOpen: function (Win) {
                        Win.Loader.show();

                        self.preview(data[0].created, function (result) {
                            var Frame = new Element('iframe', {
                                styles: {
                                    height: '100%',
                                    width : '100%',
                                    border: '0px'
                                }
                            }).inject(Win.getContent());

                            Frame.contentWindow.document.open();
                            Frame.contentWindow.document.write(result);
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
        openCompare: function () {
            var self = this,
                size = document.body.getSize(),
                id1  = this.getId() + '_select1',
                id2  = this.getId() + '_select2';

            var Radio1 = this.$Elm.getElement('input[name="' + id1 + '"]:checked'),
                Radio2 = this.$Elm.getElement('input[name="' + id2 + '"]:checked');

            if (!Radio1 || !Radio2) {
                return;
            }

            new QUIWindow({
                maxWidth : size.x - 60,
                maxHeight: size.y - 60,
                events   : {
                    onOpen: function (Win) {
                        Win.Loader.show();

                        self.compare(Radio1.value, Radio2.value, function (result) {
                            var Frame = new Element('iframe', {
                                styles: {
                                    height: '100%',
                                    width : '100%',
                                    border: '0px'
                                }
                            }).inject(Win.getContent());

                            Frame.contentWindow.document.open();
                            Frame.contentWindow.document.write(result);
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
        openRestore: function () {
            var self = this,
                data = this.$Grid.getSelectedData();

            if (!data.length) {
                return;
            }

            new QUIConfirm({
                icon       : 'fa fa-retweet',
                title      : QUILocale.get(lg, 'restore.window.title'),
                text       : QUILocale.get(lg, 'restore.window.text'),
                information: QUILocale.get(lg, 'restore.window.information', {
                    date: data[0].created
                }),
                texticon   : 'fa fa-retweet',
                events     : {
                    onSubmit: function (Win) {
                        Win.Loader.show();

                        self.restore(data[0].created, function () {

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
        compare: function (date1, date2, callback) {
            Ajax.get('package_quiqqer_history_ajax_compare', function (result) {
                callback(result);
            }, {
                'package': 'quiqqer/history',
                project  : this.$Project.encode(),
                id       : this.$Site.getId(),
                date1    : date1,
                date2    : date2
            });
        },

        /**
         * restore a history entry of the site
         *
         * @param {String} date - date of the history entry
         * @param {Function} callback - callback function after finish
         */
        restore: function (date, callback) {
            var self = this;

            Ajax.get('package_quiqqer_history_ajax_restore', function (result) {
                // refresh the site
                require(['Projects'], function (Projects) {
                    var Project = Projects.get(
                        self.getAttribute('project'),
                        self.getAttribute('lang')
                    );

                    Project.get(self.getAttribute('id')).load();
                });

                self.load();

                callback(result);
            }, {
                'package': 'quiqqer/history',
                project  : this.$Project.encode(),
                id       : this.$Site.getId(),
                date     : date
            });
        },

        /**
         * return a html preview of a history entry from the site
         *
         * @param {String} date - date of the history entry
         * @param {Function} callback - callback function after finish
         */
        preview: function (date, callback) {
            Ajax.get('package_quiqqer_history_ajax_preview', function (result) {
                callback(result);
            }, {
                'package': 'quiqqer/history',
                project  : this.$Project.encode(),
                id       : this.$Site.getId(),
                date     : date
            });
        }
    });
});
