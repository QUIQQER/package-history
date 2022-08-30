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

define('package/quiqqer/history/bin/Brick', [

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
        Type   : 'package/quiqqer/history/bin/Brick',

        Binds: [
            '$onImport',
            'openPreview',
            'openCompare',
            'openRestore',
            'getBrickId'
        ],

        options: {
            id  : false,
            Site: false
        },

        selectedCheckboxes: [],

        initialize: function (options) {
            this.parent(options);

            this.Loader = new QUILoader();
            this.$Grid  = null;
            this.$BrickPanel = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        $onImport: function () {
            let SettingsContainer = this.getElm().getParent('div.quiqqer-bricks-container');

            this.$BrickPanel = QUI.Controls.getById(SettingsContainer.getParent('.qui-panel').getAttribute('data-quiid'));

            SettingsContainer.style.height = '100%';

            SettingsContainer.querySelector('table').style.display = 'none';

            var self = this;

            this.$Elm = new Element('div', {
                'class': 'quiqqer-history-brick qui-box'
            });

            this.Loader.inject(this.$Elm);

            var Container = new Element('div', {
                'class': 'quiqqer-history-brick-container qui-box'
            }).inject(this.$Elm);

            this.$Grid = new Grid(Container, {
                columnModel: [{
                    header   : QUILocale.get('quiqqer/history', 'compare'),
                    dataIndex: 'versions',
                    dataType : 'node',
                    width    : 80
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
                    type: 'separator'
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

            this.$Elm.inject(SettingsContainer);

            this.load();

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

            Ajax.get('package_quiqqer_history_ajax_bricks_list', function (result) {
                var versionCheckboxChange = function (event) {
                    var index      = event.target.getAttribute('data-index'),
                        checkboxes = self.getCheckboxes(),
                        i          = 0;

                    // Checkbox was unchecked
                    if (!event.target.checked) {
                        // Remove index from selected checkboxes
                        self.selectedCheckboxes.splice(self.selectedCheckboxes.indexOf(index), 1);

                        // Enable all checkboxes
                        for (i = 0; i < checkboxes.length; i++) {
                            checkboxes[i].disabled = false;
                        }
                    }

                    // Checkbox was checked
                    if (event.target.checked) {
                        // Add index to selected checkboxes
                        self.selectedCheckboxes.push(index);
                    }

                    if (self.selectedCheckboxes.length >= 2) {
                        // Disable all unselected checkboxes
                        for (i = 0; i < checkboxes.length; i++) {
                            if (!checkboxes[i].checked) {
                                checkboxes[i].disabled = true;
                            }
                        }
                    }

                    self.$refreshGridButtons();
                };

                for (var i = 0, len = result.length; i < len; i++) {
                    result[i].versions = new Element('input', {
                        type        : 'checkbox',
                        'data-index': i,
                        events      : {
                            change: versionCheckboxChange
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
                brickId: this.getBrickId()
            });
        },

        getBrickId: function () {
            return this.$BrickPanel.getAttribute('id');
        },

        getCheckboxes: function() {
            return this.$Grid.getElm().querySelectorAll('div[data-index="versions"] > input[type="checkbox"]');
        },

        /**
         * refresh the buttons, disable or enable the grid buttons
         */
        $refreshGridButtons: function () {
            var buttons = this.$Grid.getButtons(),
                sels    = this.$Grid.getSelectedIndices();

            // buttons
            var Compare = null,
                Preview = null,
                Revert  = null;

            for (var i = 0, len = buttons.length; i < len; i++) {
                if (buttons[i].getAttribute('name') === 'compare') {
                    Compare = buttons[i];
                    continue;
                }

                if (buttons[i].getAttribute('name') === 'preview') {
                    Preview = buttons[i];
                    continue;
                }

                if (buttons[i].getAttribute('name') === 'revert') {
                    Revert = buttons[i];
                }
            }


            if (this.selectedCheckboxes.length === 2) {
                Compare.enable();
            } else {
                Compare.disable();
            }

            if (sels.length === 1) {
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
                date1 = this.$Grid.getDataByRow(this.selectedCheckboxes[0]).created,
                date2 = this.$Grid.getDataByRow(this.selectedCheckboxes[1]).created;

            new QUIWindow({
                maxWidth : size.x - 60,
                maxHeight: size.y - 60,
                events   : {
                    onOpen: function (Win) {
                        Win.Loader.show();

                        self.compare(date1, date2).then(function (result) {
                            var FrameOriginal   = new Element('iframe', {
                                    class : 'history-comparison',
                                    styles: {
                                        height: '100%',
                                        width : '50%',
                                        border: '0px',
                                        float : 'left'
                                    }
                                }),

                                FrameDifference = FrameOriginal.clone(),

                                frames = [{
                                    element: FrameOriginal,
                                    html   : result.originalHtml
                                }, {
                                    element: FrameDifference,
                                    html   : result.differenceHtml
                                }];

                            frames.forEach(function (Frame) {
                                Frame.element.inject(Win.getContent());
                                Frame.element.contentWindow.document.open();
                                Frame.element.contentWindow.document.write(Frame.html);
                                Frame.element.contentWindow.document.close();
                            });

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
                autoclose  : false,
                events     : {
                    onSubmit: function (Win) {
                        Win.Loader.show();
                        self.restore(data[0].created).then(function () {
                            Win.close();
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
         * @param {Function} [callback] - callback function after finish
         * @return {Promise}
         */
        compare: function (date1, date2, callback) {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_history_ajax_bricks_compare', function (result) {
                    if (typeof callback === 'function') {
                        callback(result);
                    }

                    resolve(result);
                }, {
                    'package': 'quiqqer/history',
                    brickId  : this.getBrickId(),
                    date1    : date1,
                    date2    : date2,
                    onError  : reject
                });
            }.bind(this));
        },

        /**
         * restore a history entry of the site
         *
         * @param {String} date - date of the history entry
         * @param {Function} [callback] - callback function after finish
         * @return {Promise}
         */
        restore: function (date, callback) {
            var self = this;

            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_history_ajax_bricks_restore', function (result) {
                        // TODO: refresh the brick panel to load the new/restored settings

                        if (typeof callback === 'function') {
                            callback(result);
                        }

                        resolve(result);
                }, {
                    'package': 'quiqqer/history',
                    brickId  : self.getBrickId(),
                    date     : date,
                    onError  : reject
                });
            });
        },

        /**
         * return a html preview of a history entry from the site
         *
         * @param {String} date - date of the history entry
         * @param {Function} [callback] - callback function after finish
         * @return {Promise}
         */
        preview: function (date, callback) {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_history_ajax_bricks_preview', function (result) {
                    if (typeof callback === 'function') {
                        callback(result);
                    }

                    resolve(result);
                }, {
                    'package': 'quiqqer/history',
                    brickId  : this.getBrickId(),
                    date     : date,
                    onError  : reject
                });
            }.bind(this));
        }
    });
});
