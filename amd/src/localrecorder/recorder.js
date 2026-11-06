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
 * Records audio or video in the browser. This module has no user interface.
 *
 * This file is shared by several Poodll plugins and kept in sync between them,
 * so it must not mention any component name.
 *
 * @copyright  2026 Justin Hunt - Poodll.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Formats to try, best first. VP9 is never asked for, because the Cloud Poodll
// transcoder turns VP9 into an empty file. Plain "video/webm" is left out for the
// same reason: the browser would choose the codec.
const AUDIO_TYPES = ['audio/webm;codecs=opus', 'audio/mp4', 'audio/ogg;codecs=opus', 'audio/webm'];
const VIDEO_TYPES = ['video/webm;codecs=vp8,opus', 'video/mp4;codecs=avc1.42E01E,mp4a.40.2', 'video/mp4',
    'video/webm;codecs=vp8'];

// How often the clock, the time limit and the level meter are updated, in milliseconds.
const TICK = 100;

/**
 * Pick the first recording format this browser supports.
 *
 * @param {String} mediatype audio or video
 * @returns {String|null} the mimetype, or null when none is supported
 */
const pickMimeType = (mediatype) => {
    const types = mediatype === 'video' ? VIDEO_TYPES : AUDIO_TYPES;
    return types.find((type) => window.MediaRecorder.isTypeSupported(type)) || null;
};

export default class Recorder {

    /**
     * @param {Object} options
     * @param {String} options.mediatype audio or video
     * @param {Boolean} options.noaudiofilters turn off echo cancellation, noise suppression and gain control
     * @param {Number} options.timelimit seconds, 0 for no limit
     * @param {Function} options.ontick called with (elapsed seconds, level 0 to 1) while recording
     * @param {Function} options.onlimit called when the time limit is reached
     */
    constructor(options) {
        this.mediatype = options.mediatype;
        this.noaudiofilters = !!options.noaudiofilters;
        this.timelimit = options.timelimit || 0;
        this.ontick = options.ontick || (() => null);
        this.onlimit = options.onlimit || (() => null);
        this.stream = null;
        this.mediarecorder = null;
        this.chunks = [];
        this.levels = [];
        this.audioctx = null;
        this.analyser = null;
        this.timer = null;
        this.elapsedms = 0;
        this.runningsince = 0;
        this.deviceids = {audio: '', video: ''};
    }

    /**
     * Can this browser record at all?
     *
     * @param {String} mediatype audio or video
     * @returns {Boolean}
     */
    static isSupported(mediatype) {
        return !!(window.isSecureContext && navigator.mediaDevices && navigator.mediaDevices.getUserMedia &&
            window.MediaRecorder && pickMimeType(mediatype));
    }

    /**
     * Choose the microphone or camera to use next time the devices are opened.
     *
     * @param {String} kind audio or video
     * @param {String} deviceid
     */
    setDevice(kind, deviceid) {
        this.deviceids[kind] = deviceid;
    }

    /**
     * Ask for the microphone (and camera) and start the level meter.
     * Rejects with the getUserMedia error, whose name says what went wrong.
     *
     * @returns {Promise<MediaStream>}
     */
    async open() {
        if (this.stream) {
            return this.stream;
        }
        const filters = !this.noaudiofilters;
        const audio = {echoCancellation: filters, noiseSuppression: filters, autoGainControl: filters};
        if (this.deviceids.audio) {
            audio.deviceId = {exact: this.deviceids.audio};
        }
        let video = false;
        if (this.mediatype === 'video') {
            video = {width: {ideal: 640}, height: {ideal: 360}, frameRate: {ideal: 30}};
            if (this.deviceids.video) {
                video.deviceId = {exact: this.deviceids.video};
            }
        }
        this.stream = await navigator.mediaDevices.getUserMedia({audio: audio, video: video});
        this.startMeter();
        return this.stream;
    }

    /**
     * Stop using the microphone and camera, so the browser's recording light goes off.
     */
    close() {
        this.stopTimer();
        if (this.stream) {
            this.stream.getTracks().forEach((track) => track.stop());
            this.stream = null;
        }
        if (this.audioctx) {
            this.audioctx.close().catch(() => null);
            this.audioctx = null;
            this.analyser = null;
        }
    }

    /**
     * List the microphones and cameras. Labels are only filled in after permission was given.
     *
     * @returns {Promise<Object>} {audio: [{id, label}], video: [{id, label}]}
     */
    async listDevices() {
        const ret = {audio: [], video: []};
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
            return ret;
        }
        const devices = await navigator.mediaDevices.enumerateDevices();
        devices.forEach((device) => {
            if (!device.label) {
                return;
            }
            if (device.kind === 'audioinput') {
                ret.audio.push({id: device.deviceId, label: device.label});
            } else if (device.kind === 'videoinput') {
                ret.video.push({id: device.deviceId, label: device.label});
            }
        });
        return ret;
    }

    /**
     * Start recording. open() must have finished first.
     */
    start() {
        this.chunks = [];
        this.levels = [];
        this.elapsedms = 0;
        const options = {mimeType: pickMimeType(this.mediatype)};
        if (this.mediatype === 'video') {
            options.videoBitsPerSecond = 1000000;
        }
        options.audioBitsPerSecond = 96000;
        this.mediarecorder = new MediaRecorder(this.stream, options);
        this.mediarecorder.ondataavailable = (e) => {
            if (e.data && e.data.size) {
                this.chunks.push(e.data);
            }
        };
        this.mediarecorder.start(1000);
        this.runningsince = performance.now();
        this.startTimer();
    }

    pause() {
        if (this.mediarecorder && this.mediarecorder.state === 'recording') {
            this.mediarecorder.pause();
            this.elapsedms += performance.now() - this.runningsince;
            this.runningsince = 0;
        }
    }

    resume() {
        if (this.mediarecorder && this.mediarecorder.state === 'paused') {
            this.mediarecorder.resume();
            this.runningsince = performance.now();
        }
    }

    /**
     * @returns {Number} seconds recorded, not counting pauses
     */
    elapsed() {
        const running = this.runningsince ? performance.now() - this.runningsince : 0;
        return (this.elapsedms + running) / 1000;
    }

    /**
     * Stop recording and close the devices.
     *
     * @returns {Promise<Object>} {blob, mimetype, duration, levels}
     */
    stop() {
        return new Promise((resolve) => {
            const duration = this.elapsed();
            this.elapsedms = duration * 1000;
            this.runningsince = 0;
            this.stopTimer();
            const mr = this.mediarecorder;
            const finish = () => {
                const mimetype = (mr && mr.mimeType) || pickMimeType(this.mediatype);
                const blob = new Blob(this.chunks, {type: mimetype});
                this.chunks = [];
                this.close();
                resolve({blob: blob, mimetype: mimetype, duration: duration, levels: this.levels.slice()});
            };
            if (!mr || mr.state === 'inactive') {
                finish();
                return;
            }
            mr.onstop = finish;
            mr.stop();
        });
    }

    /**
     * Throw away a recording in progress.
     */
    cancel() {
        const mr = this.mediarecorder;
        if (mr && mr.state !== 'inactive') {
            mr.ondataavailable = null;
            mr.onstop = null;
            mr.stop();
        }
        this.chunks = [];
        this.close();
    }

    /**
     * The current input level, from 0 to 1.
     *
     * @returns {Number}
     */
    level() {
        if (!this.analyser) {
            return 0;
        }
        const data = new Float32Array(this.analyser.fftSize);
        this.analyser.getFloatTimeDomainData(data);
        let sum = 0;
        for (let i = 0; i < data.length; i++) {
            sum += data[i] * data[i];
        }
        return Math.min(1, Math.sqrt(Math.sqrt(sum / data.length)) * 1.6);
    }

    startMeter() {
        const Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx || !this.stream.getAudioTracks().length) {
            return;
        }
        try {
            this.audioctx = new Ctx();
            this.audioctx.resume().catch(() => null);
            this.analyser = this.audioctx.createAnalyser();
            this.analyser.fftSize = 1024;
            this.audioctx.createMediaStreamSource(this.stream).connect(this.analyser);
        } catch (e) {
            // The meter is only a nicety. Recording works without it.
            this.audioctx = null;
            this.analyser = null;
        }
    }

    startTimer() {
        this.stopTimer();
        this.timer = setInterval(() => {
            const paused = !this.runningsince;
            const level = paused ? 0 : this.level();
            if (!paused) {
                this.levels.push(level);
            }
            const elapsed = this.elapsed();
            this.ontick(elapsed, level, paused);
            if (this.timelimit && elapsed >= this.timelimit) {
                this.stopTimer();
                this.onlimit();
            }
        }, TICK);
    }

    stopTimer() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }
}

/** Number of level samples per second in the levels array. */
export const LEVELS_PER_SECOND = 1000 / TICK;
