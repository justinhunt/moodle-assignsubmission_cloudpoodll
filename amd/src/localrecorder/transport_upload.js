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
 * Sends a finished recording or file to Cloud Poodll.
 *
 * The plugin's web service asks Cloud Poodll for a presigned S3 upload URL (the browser never
 * talks to Cloud Poodll itself), then the file is PUT straight to S3. Cloud Poodll converts it and
 * writes the result to the media URL, which is polled until it is there.
 *
 * This file is shared by several Poodll plugins and kept in sync between them,
 * so it must not mention any component name. The web service name is passed in.
 *
 * @copyright  2026 Justin Hunt - Poodll.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

const POLL_INTERVAL = 2500;

export default class UploadTransport {

    /**
     * @param {Object} options
     * @param {String} options.wsfunction the web service that returns posturl and mediaurl
     * @param {Object} options.wsargs arguments for it, besides sourcemimetype (for example {cmid: 12})
     */
    constructor(options) {
        this.wsfunction = options.wsfunction;
        this.wsargs = options.wsargs || {};
        this.xhr = null;
        this.polling = null;
    }

    /**
     * Upload a recording or file.
     *
     * @param {Blob} blob
     * @param {String} mimetype
     * @param {Function} onprogress called with a number from 0 to 1
     * @returns {Promise<String>} the media URL, where the converted file will appear
     */
    async send(blob, mimetype, onprogress) {
        const args = Object.assign({}, this.wsargs, {sourcemimetype: mimetype});
        const details = await Ajax.call([{methodname: this.wsfunction, args: args}])[0];
        await this.put(details.posturl, blob, onprogress);
        return details.mediaurl;
    }

    /**
     * PUT the file to S3. XMLHttpRequest is used because fetch can not report upload progress.
     *
     * @param {String} url
     * @param {Blob} blob
     * @param {Function} onprogress
     * @returns {Promise}
     */
    put(url, blob, onprogress) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            this.xhr = xhr;
            xhr.open('PUT', url);
            xhr.setRequestHeader('Content-Type', 'application/octet-stream');
            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable && onprogress) {
                    onprogress(e.loaded / e.total);
                }
            };
            xhr.onload = () => {
                this.xhr = null;
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve();
                } else {
                    reject(new Error('Upload failed with HTTP ' + xhr.status));
                }
            };
            xhr.onerror = () => {
                this.xhr = null;
                reject(new Error('Upload failed'));
            };
            xhr.onabort = () => {
                this.xhr = null;
                reject(new Error('Upload cancelled'));
            };
            xhr.send(blob);
        });
    }

    /**
     * Wait for the converted file to appear at the media URL.
     *
     * Only a non-empty file counts. The transcoder writes an empty file when a conversion fails,
     * and S3 answers HEAD with 200 for that too.
     *
     * @param {String} mediaurl
     * @param {Number} timeout seconds to wait
     * @returns {Promise<Boolean>} true if it appeared, false if we gave up waiting
     */
    waitUntilReady(mediaurl, timeout) {
        this.stopPolling();
        const giveup = Date.now() + timeout * 1000;
        return new Promise((resolve) => {
            const check = async() => {
                try {
                    const sep = mediaurl.indexOf('?') === -1 ? '?' : '&';
                    const response = await fetch(mediaurl + sep + '_=' + Date.now(), {method: 'HEAD', cache: 'no-store'});
                    const length = response.headers.get('Content-Length');
                    if (response.ok && (length === null || Number(length) > 0)) {
                        this.polling = null;
                        resolve(true);
                        return;
                    }
                } catch (e) {
                    // Not there yet, or the network blinked. Keep trying until the timeout.
                }
                if (Date.now() > giveup) {
                    this.polling = null;
                    resolve(false);
                    return;
                }
                this.polling = setTimeout(check, POLL_INTERVAL);
            };
            this.polling = setTimeout(check, POLL_INTERVAL);
        });
    }

    stopPolling() {
        if (this.polling) {
            clearTimeout(this.polling);
            this.polling = null;
        }
    }

    /**
     * Stop an upload or a wait in progress.
     */
    cancel() {
        if (this.xhr) {
            this.xhr.abort();
        }
        this.stopPolling();
    }
}
