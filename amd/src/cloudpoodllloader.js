define(['jquery','core/log',
    'assignsubmission_cloudpoodll/cloudpoodll'],
    function($,log,CloudPoodll){
    return {
        init: function(recorderid,thecallback){
            CloudPoodll.createRecorder(recorderid);
            CloudPoodll.theCallback = thecallback;
            CloudPoodll.initEvents();
            $( "iframe" ).on("load",function(){
                $(".assignsubmission_cloudpoodll_recording_cont").css('background-image','none');
            });
        }
};
});