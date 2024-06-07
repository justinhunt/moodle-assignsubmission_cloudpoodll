define(['jquery','core/log','core/str','core/ajax','core/notification', './copy_to_clipboard'],
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
                config.container =$('#' + containerid);
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
            var textcontainer = $('<p id="' + config.prefix + '-text" class="' + config.prefix + '-text"></p>');

            textcontainer.load(config.transcripturl,function(){
                config.transcripttext = textcontainer.text();
                config.container.prepend(header);
                config.container.append(textcontainer);

                var copybutton = that.prepare_copy_button(config.prefix + '-text');
                var correctionsbutton = that.prepare_corrections_button(config);
                //we dont need this anymore, its in the cloudpoodllfeedback
               // config.container.append('<div class="assignsubmission_cloudpoodll_actionbar">' + copybutton + correctionsbutton + '</div>');


            });
        },

        prepare_copy_button: function(target) {
            return '<a href="javascript:void()" data-action="copy" data-clipboard-target="#' + target + '"><i class="fa fa-copy"></i></a>';
        },

        prepare_corrections_button: function(config){
            var that = this;

            var onetimeloaded = false;
           // config.button =  config.container.append(button);

            $(config.container).on('click', '[data-action="getcorrection"]', function(e){
                e.preventDefault();
                if (!onetimeloaded) {
                    that.fetch_corrections(config);
                    onetimeloaded = true;
                }
            });
            var button = '<a href="javascript:void()" data-action="getcorrection"><i class="fa fa-check"></a>';
            return button;
        },

        fetch_corrections: function(config){
            var that = this;
            //do the check
            var text = config.transcripttext;
            //but quit if its empty
            if(!text || text==='' || text.trim()===''){
                return;
            }
            config.correctionscontainer = $('<code class="' + config.prefix + '-difftext"></code>').appendTo(config.container);

            Ajax.call([{
                methodname: 'assignsubmission_cloudpoodll_check_grammar',
                args: {
                    assignmentid: config.assignmentid,
                    text: text

                },
                done: function (ajaxresult) {
                    var payloadobject = JSON.parse(ajaxresult);
                    if (payloadobject) {
                        config.correctionscontainer.html(payloadobject.diffhtml);
                        config.correctionscontainer.append('<textarea style="display: none" id ="' + config.prefix + '-difftext">' +
                            payloadobject.corrections + '</textarea>');
                        var copybutton =that.prepare_copy_button(config.prefix + '-difftext')

                        config.correctionscontainer.append('<div class="assignsubmission_cloudpoodll_actionbar">' + copybutton +  '</div>');
                    }else{
                        //something went wrong
                        config.textcontainer.html('could not fetch corrections');
                        log.debug('result not fetched');
                    }

                },
                fail: notification.exception
            }]);
        }

    };//end of return object
});