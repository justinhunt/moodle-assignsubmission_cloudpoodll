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

namespace assignsubmission_cloudpoodll\localrecorder;

/**
 * Fetches the S3 upload details for one local recorder recording from Cloud Poodll.
 *
 * This file is shared by the Poodll plugins that use the local recorder. Keep it identical in each of
 * them apart from the namespace: it must not refer to the plugin it sits in.
 *
 * @package    assignsubmission_cloudpoodll
 * @copyright  2026 Justin Hunt <justin@poodll.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_details {
    /** @var int Seconds to wait for Cloud Poodll before giving up. */
    const TIMEOUT = 30;

    /**
     * Reduce a browser mime type to the bare type the Cloud Poodll processing expects.
     *
     * MediaRecorder reports types such as "video/webm;codecs=vp8,opus". The processing names the copy of
     * the original file after everything that follows the slash, so the codec part has to go.
     *
     * @param string $mimetype The mime type reported by the browser.
     * @param string $mediatype 'audio' or 'video'.
     * @return string The bare mime type, or '' if it is not a usable audio or video type.
     */
    public static function clean_mimetype(string $mimetype, string $mediatype): string {
        $mimetype = strtolower(trim(explode(';', $mimetype)[0]));
        if (!preg_match('~^(audio|video)/[a-z0-9.+-]{1,40}$~', $mimetype)) {
            return '';
        }
        // Safari reports audio recordings in an mp4 container as video/mp4 in some versions.
        if ($mediatype === 'audio' && $mimetype === 'video/mp4') {
            return 'audio/mp4';
        }
        return $mimetype;
    }

    /**
     * Ask Cloud Poodll for a presigned upload URL and register the processing job for it.
     *
     * Cloud Poodll checks the subscription, the app id and that the parent URL is a registered site.
     * Each call registers one processing job, so call it once per recording, after the recording ends.
     *
     * @param string $server The Cloud Poodll server URL, e.g. https://cloud.poodll.com.
     * @param string $token The Cloud Poodll API token.
     * @param array $params Keys: mediatype, parent, appid, owner, region, expiredays, transcribe,
     *      subtitle, language, vocab, sourcemimetype.
     * @return array ['success' => bool, 'message' => string, 'posturl' => string, 'mediaurl' => string,
     *      's3root' => string, 's3filename' => string]
     */
    public static function fetch(string $server, string $token, array $params): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $ret = ['success' => false, 'message' => '', 'posturl' => '', 'mediaurl' => '', 's3root' => '',
            's3filename' => ''];
        if (empty($token)) {
            $ret['message'] = 'No Cloud Poodll token';
            return $ret;
        }

        $mediatype = $params['mediatype'] === 'video' ? 'video' : 'audio';
        $postdata = [
            'wstoken' => $token,
            'wsfunction' => 'local_cpapi_fetch_upload_details',
            'moodlewsrestformat' => 'json',
            'multipart' => 0,
            'mediatype' => $mediatype,
            'parent' => $params['parent'],
            'appid' => $params['appid'],
            'owner' => $params['owner'],
            'region' => $params['region'],
            'expiredays' => (int) $params['expiredays'],
            // Recordings are always converted to mp3 or mp4.
            'transcode' => 1,
            'transcoder' => 'default',
            'transcribe' => (int) $params['transcribe'],
            'subtitle' => (int) $params['subtitle'],
            'transcribelanguage' => $params['language'],
            'transcribevocab' => empty($params['vocab']) ? 'none' : $params['vocab'],
            'notificationurl' => 'none',
            'sourcemimetype' => self::clean_mimetype($params['sourcemimetype'], $mediatype),
        ];

        $curl = new \curl();
        $curl->setopt(['CURLOPT_TIMEOUT' => self::TIMEOUT, 'CURLOPT_CONNECTTIMEOUT' => 10]);
        $response = $curl->post(rtrim($server, '/') . '/webservice/rest/server.php', $postdata);
        if ($curl->get_errno()) {
            $ret['message'] = 'Could not reach Cloud Poodll: ' . $curl->error;
            return $ret;
        }

        $result = json_decode($response);
        if (!$result || !isset($result->returnCode)) {
            // An exception from the web service, or PHP errors printed in front of the JSON.
            $ret['message'] = isset($result->message) ? $result->message : 'Unexpected reply from Cloud Poodll';
            return $ret;
        }
        if ((int) $result->returnCode !== 0) {
            $ret['message'] = (string) $result->returnMessage;
            return $ret;
        }
        if (empty($result->postURL) || empty($result->s3root) || empty($result->s3filename)) {
            $ret['message'] = 'Cloud Poodll returned no upload URL';
            return $ret;
        }

        $ret['success'] = true;
        $ret['posturl'] = $result->postURL;
        $ret['s3root'] = $result->s3root;
        $ret['s3filename'] = $result->s3filename;
        $ret['mediaurl'] = $result->s3root . $result->s3filename;
        return $ret;
    }
}
