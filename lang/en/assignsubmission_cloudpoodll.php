<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'assignsubmission_cloudpoodll', language 'en'
 *
 * @package    assignsubmission_cloudpoodll
 * @copyright 2012 Justin Hunt {@link http://www.poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['allowcloudpoodllsubmissions'] = 'Enabled';
$string['default'] = 'Enabled by default';
$string['default_help'] = 'If set, this submission method will be enabled by default for all new assignments.';
$string['enabled'] = 'Cloud Poodll';
$string['enabled_help'] = 'If enabled, students are able to record audio or video (as per the settings) for this assignment.';
$string['nosubmission'] = 'Nothing has been submitted for this assignment';
$string['cloudpoodll'] = 'Cloud Poodll';
$string['cloudpoodllfilename'] = 'cloudpoodll.html'; //what this for ?
$string['cloudpoodllsubmission'] = 'Allow Cloud Poodll submission';
$string['pluginname'] = 'Cloud Poodll submissions';
$string['recorder'] = 'Recorder Type';
$string['recorderaudio'] = 'Audio Recorder';
$string['recordervideo'] = 'Video Recorder';
$string['defaultrecorder'] = 'Recorder Type';
$string['defaultrecorderdetails'] = '';

$string['apiuser']='Poodll API User ';
$string['apiuser_details']='The Poodll account username that authorises Poodll on this site.';
$string['apisecret']='Poodll API Secret ';
$string['apisecret_details']='The Poodll API secret. See <a href= "https://support.poodll.com/support/solutions/articles/19000083076-cloud-poodll-api-secret">here</a> for more details';
$string['enabletranscription']='Enable Transcription';
$string['enabletranscription_details']='Cloud Poodll can transcribe the student speaking if required (English French or Spanish)';
$string['enabletranscode']='Enable Transcode';
$string['enabletranscode_details']='Cloud Poodll can transcode the recording to mp3/mp4';
$string['language']='Language';

$string['useast1']='US East';
$string['tokyo']='Tokyo, Japan';
$string['sydney']='Sydney, Australia';
$string['dublin']='Dublin, Ireland';
$string['ottawa']='Ottawa, Canada (slow)';
$string['frankfurt']='Frankfurt, Germany (slow)';
$string['london']='London, U.K (slow)';
$string['saopaulo']='Sao Paulo, Brazil (slow)';
$string['forever']='Never expire';
$string['en-us']='English (US)';
$string['es-us']='Spanish (US)';
$string['en-au']='English (Aus.)';
$string['en-uk']='English (UK)';
$string['fr-ca']='French (Can.)';
$string['awsregion']='AWS Region';
$string['region']='AWS Region';
$string['expiredays']='Cloud Poodll Days to keep file';


$string['timelimit'] = 'Cloud Poodll Rec. Time Limit';
$string['currentsubmission'] = 'Current Submission:';
$string['yes'] = 'yes';
$string['no'] = 'no';

$string['showcurrentsubmission'] = 'Show Current Submission';
$string['showcurrentsubmissiondetails'] = 'Show previously recorded submission on submission form.';

$string['displayaudioplayersingle'] = 'Show audio player(normal)';
$string['displayaudioplayerlist'] = 'Show audio player(lists)';

$string['displaysizesingle'] = 'Video player size(normal)';
$string['displaysizesingledetails'] = '';
$string['displaysizelist'] = 'Video player size(in lists)';
$string['displaysizelistdetails'] = '';
$string['shownovideo'] = 'Do not display video';
$string['videoplaceholder'] = ' [video submitted] ';
$string['audioplaceholder'] = ' [audio submitted] ';
$string['shownoimage'] = 'Do not display image';

$string['setting_audio_heading'] = 'Audio player settings';
$string['setting_audio_heading_details'] = 'Settings controlling player appearance in submission review and list pages';
$string['setting_video_heading'] = 'Video player settings';
$string['setting_video_heading_details'] = 'Settings controlling player appearance in submission review and list pages';
$string['setting_snapshot_heading'] = 'Snapshot image settings';
$string['setting_snapshot_heading_details'] = 'Settings controlling image appearance in submission review and list pages';
$string['setting_whiteboard_heading'] = 'Whiteboard image settings';
$string['setting_whiteboard_heading_details'] = 'Settings controlling image appearance in submission review and list pages';
$string['deletesubmission'] = 'Delete this submission.';
$string['reallydeletesubmission'] = 'Really delete this submission?';

$string['cloudpoodll:use'] = 'Allow use of Cloud Poodll submissions';
$string['privacy:metadata:cloudpoodllcom'] = 'The assignsubmission_cloudpoodll plugin stores recordings in AWS S3 buckets via cloud.poodll.com.';
$string['privacy:metadata:cloudpoodllcom:userid'] = 'The assignsubmission_cloudpoodll plugin includes the moodle userid in the urls of recordings.';
$string['privacy:metadata:assignmentid'] = 'Assignment identifier';
$string['privacy:metadata:filepurpose'] = 'File urls of submitted recordings.';
$string['privacy:metadata:submissionpurpose'] = 'The submission ID that links to submissions for the user.';
$string['privacy:metadata:tablepurpose'] = 'Stores the text and URLs that make the submission for each attempt.';
$string['privacy:metadata:transcriptpurpose'] = 'The transcript for this attempt of the assignment.';
$string['privacy:metadata:fulltranscriptpurpose'] = 'The transcript with metadata for this attempt of the assignment.';
$string['privacy:metadata:vttpurpose'] = 'The subtitle rendering of transcript for this attempt of the assignment.';
$string['privacy:path'] = 'Submission Text';



$string['recordertype'] = 'Cloud Poodll Rec. Type';
$string['recorderskin'] = 'Cloud Poodll Rec. Skin';
$string['skinplain'] = 'Plain';
$string['skinbmr'] = 'Burnt Rose';
$string['skinfresh'] = 'Fresh (audio only)';
$string['skin123'] = 'One Two Three';
$string['skinonce'] = 'Once';
$string['skinupload'] = 'Upload';

$string['fallback'] = 'non-HTML5 Fallback';
$string['fallbackdetails'] = 'If the browser does not support HTML5 recording for the selected mediatype, fallback to an upload screen or a warning.';
$string['fallbackupload'] = 'Upload';
$string['fallbackiosupload'] = 'iOS: upload, else warning';
$string['fallbackwarning'] = 'Warning';

$string['playertype']='Player type';
$string['playertypedefault']='System Default';
$string['playertypetranscript']='Transcript player';
$string['audioplayertype']='Audio player type';
$string['audioplayertypedetails']='Use the default audio player assigned by Moodle, or use the transcript player if you are transcribing.';
$string['videoplayertype']='Audio player type';
$string['videoplayertypedetails']='Use the default video player assigned by Moodle, or use the transcript player if you are transcribing.';
$string['transcripttitle']='Transcript';

$string['displaysubs'] = '{$a->subscriptionname} : expires {$a->expiredate}';
$string['noapiuser'] = "No API user entered. Read Aloud will not work correctly.";
$string['noapisecret'] = "No API secret entered. Read Aloud will not work correctly.";
$string['credentialsinvalid'] = "The API user and secret entered could not be used to get access. Please check them.";
$string['appauthorised']= "Assign Submission Cloud Poodll is authorised for this site.";
$string['appnotauthorised']= "Assign Submission Cloud Poodll  is NOT authorised for this site.";
$string['refreshtoken']= "Refresh license information";
$string['notokenincache']= "Refresh to see license information. Contact Poodll support if there is a problem.";

