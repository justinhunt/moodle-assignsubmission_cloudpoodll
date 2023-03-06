define(['jquery','core/log','core/str','core/ajax','core/notification'],
    function($,log,str,Ajax,notification) {
    "use strict"; // jshint ;_;

    log.debug('Standard Transcript: initialising');

    return {

        init: function(opts){

            var component= opts['component'];
            var playerid= opts['playerid'];
            var containerid= opts['containerid'];
            var cssprefix= opts['cssprefix'];
            var config={};
            config.settings ={};
            if(playerid && $('#' + playerid).length) {
                config.component = component;
                config.container =$('#' + containerid)
                config.prefix = cssprefix;
                config.player = $('#' + playerid)[0];
                config.transcripturl = opts['transcripturl'];
                config.title = M.util.get_string('transcripttitle',component);
                config.assignmentid = opts['assignmentid'];
                this.fetch_transcript(config);
            }
        },

        fetch_transcript: function(config){
            var that = this;
            var header = '<header class="' + config.prefix + '-header">' +config.title+'</header>';
            config.container.load(config.transcripturl,function(){
                config.container.prepend(header);
                //This is a prototype fetch corrections button
               that.prepare_corrections_button(config);
            });
        },

        prepare_corrections_button: function(config){
            var that = this;
            var button = '<a class="btn btn-standard" href="#">fetch corrections</a>';
            config.button =  config.container.append(button);
            $(config.button).on('click',function(){that.fetch_corrections(config)});
        },

        fetch_corrections: function(config){
            //do the check
            var text = config.container.text();
            //but quit if its empty
            if(!text || text==='' || text.trim()===''){
                return;
            }

            Ajax.call([{
                methodname: 'assignsubmission_cloudpoodll_check_grammar',
                args: {
                    assignmentid: config.assignmentid,
                    text: text

                },
                done: function (ajaxresult) {

                    var payloadobject = JSON.parse(ajaxresult);
                    if (payloadobject) {
                        config.container.append(payloadobject.corrections);
                    }else{
                        //something went wrong
                        config.container.append('could not fetch corrections');
                        log.debug('result not fetched');
                    }

                },
                fail: notification.exception
            }]);
        }

    };//end of return object
});