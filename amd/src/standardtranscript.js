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

        copybutton: function(target) {
            return '<a href="javascript:void()" class="fa fa-copy" data-action="copy" data-clipboard-target="#' + target + '"></a>';
        },

        fetch_transcript: function(config){
            var that = this;
            var header = '<header class="' + config.prefix + '-header">' +config.title+'</header>';
            var textcontainer = $('<p id="' + config.prefix + '-text" class="' + config.prefix + '-text"></p>');

            textcontainer.load(config.transcripturl,function(){
                config.transcripttext = textcontainer.text();
                config.container.prepend(header);
                config.container.append(textcontainer.append(that.copybutton(config.prefix + '-text')));
                //This is a prototype fetch corrections button
               that.prepare_corrections_button(config);
            });
        },

        prepare_corrections_button: function(config){
            var that = this;
            var button = '<a class="btn btn-standard" href="#" data-action="getcorrection">fetch corrections</a>';
            var onetimeloaded = false;
            config.button =  config.container.append(button);
            config.textcontainer = $('<code class="' + config.prefix + '-difftext"></code>').appendTo(config.container);
            $(config.container).on('click', '[data-action="getcorrection"]', function(e){
                e.preventDefault();
                if (!onetimeloaded) {
                    that.fetch_corrections(config);
                    onetimeloaded = true;
                }
            });
        },

        fetch_corrections: function(config){
            var that = this;
            //do the check
            var text = config.transcripttext;
            //but quit if its empty
            if(!text || text==='' || text.trim()===''){
                return;
            }

            config.textcontainer.html('');

            Ajax.call([{
                methodname: 'assignsubmission_cloudpoodll_check_grammar',
                args: {
                    assignmentid: config.assignmentid,
                    text: text

                },
                done: function (ajaxresult) {
                    var payloadobject = JSON.parse(ajaxresult);
                    if (payloadobject) {
                        config.textcontainer.html(payloadobject.diffhtml);
                        config.textcontainer.append('<textarea style="display: none" id ="' + config.prefix + '-difftext">' +
                            payloadobject.corrections + '</textarea>');
                        config.textcontainer.append(that.copybutton(config.prefix + '-difftext'));
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