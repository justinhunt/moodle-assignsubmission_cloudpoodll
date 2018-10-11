define(['jquery','core/log','assignsubmission_cloudpoodll/cloudpoodllloader'], function($,log,cloudpoodll) {
    "use strict"; // jshint ;_;

    log.debug('submission helper: initialising');

    return {

        uploadstate: false,

        init:  function(opts) {
            this.component = opts['component'];

            this.register_controls();
            this.register_events();
            this.setup_recorder();
        },

        setup_recorder: function(){
            var that = this;
            var recorder_callback = function(evt){
                switch(evt.type){
                    case 'recording':
                        if(evt.action==='started'){
                            that.controls.updatecontrol.val();
                        }
                        break;
                    case 'awaitingprocessing':
                        if(that.uploadstate!='posted') {
                            that.controls.updatecontrol.val(evt.mediaurl);
                        }
                        that.uploadstate='posted';
                        break;
                }
            };

            cloudpoodll.init(this.component + '_therecorder',recorder_callback);
        },


        register_controls: function(){
          this.controls={};
          this.controls.deletebutton = $('.' + this.component + '_deletesubmissionbutton');
          this.controls.updatecontrol =  $('#' + this.component + '_updatecontrol');
          this.controls.currentcontainer =  $('.' + this.component + '_currentsubmission');
        },

        register_events: function(){
            var that =this;
            this.controls.deletebutton.click(function(){
                if(that.controls.updatecontrol){
                    if(confirm(M.util.get_string('reallydeletesubmission',that.component))){
                        that.controls.updatecontrol.val(-1);
                        that.controls.currentcontainer.html('');
                    }
                }
            });
        }
    };//end of return object
});