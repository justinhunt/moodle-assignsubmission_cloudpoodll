/**
 * Add a modal to manage question adding and editing to the page.
 *
 * @module     assignsubmission_cloudpoodll/modalformhelper
 * @class      modalformhelper
 * @package    assignsubmission_cloudpoodll
 * @copyright  2020 Justin Hunt <poodllsupport@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/log', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/fragment'],
    function ($, log, Str, ModalFactory, ModalEvents, Fragment) {

        /**
         * Constructor
         *
         * @param {String} selector used to find triggers for the new group modal.
         *
         * Each call to init gets it's own instance of this class.
         */
        var TheForm = function (selector) {

            //this will init on item click (better for lots of items)
            this.init(selector);

        };

        /**
         * @var {Modal} modal
         * @private
         */
        TheForm.prototype.modal = null;


        /**
         * Initialise the class.
         *
         * @param {String} selector used to find triggers for the new group modal.
         * @private
         * @return {Promise}
         */
        TheForm.prototype.init = function (selector) {
            var dd = this;

            $('body').on('click', selector, function (e) {
                //prevent it doing a real click (which will do the non ajax version of a click)
                e.preventDefault();
                dd.modaldata = {
                    'contextid': $(this).data('contextid'),
                    'mediaurl': $(this).data('mediaurl'),
                    'mediatype': $(this).data('mediatype'),
                    'transcripturl': $(this).data('transcripturl'),
                    'lang': $(this).data('lang')
                };


                ModalFactory.create({
                    type: ModalFactory.types.CANCEL,
                    title: dd.formtitle,
                    body: dd.getBody(dd.modaldata)
                }).then(function (modal) {
                    // Keep a reference to the modal.
                    dd.modal = modal;
                    // dd.modal.setLarge();
                    // dd.modal.setTitle('bananas');

                    // We want to reset the form every time it is opened.
                    dd.modal.getRoot().on(ModalEvents.hidden, function () {
                        dd.modal.setBody(dd.getBody(dd.modaldata));
                    }.bind(dd));

                    dd.modal.show();
                    return dd.modal;
                });

            });//end of on click

        };


        /**
         * @method getBody
         * @private
         * @return {Promise}
         */
        TheForm.prototype.getBody = function (modaldata) {


            // Get the content of the modal.
            return Fragment.loadFragment('assignsubmission_cloudpoodll', 'mform', modaldata['contextid'], modaldata);

        };


        return /** @alias module:assignsubmission_cloudpoodll/modalformhelper */ {
            // Public variables and functions.
            /**
             * Attach event listeners to initialise this module.
             *
             * @method init
             * @param {string} selector The CSS selector used to find nodes that will trigger this module.
             * @return {Promise}
             */
            init: function (selector) {
                return new TheForm(selector);
            }
        };
    });