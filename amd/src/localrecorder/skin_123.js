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
 * The 123 skin of the local recorder: Record, Check, Save. With asktouse off it is the
 * "once" skin, which uploads as soon as Stop is pressed.
 *
 * The skin tells the page what is happening with a bubbling "localrecorder" DOM event on
 * its root element. event.detail.type is one of:
 * - recording (action: started): a new recording has begun, so the page is busy
 * - awaitingprocessing (mediaurl): the file is uploaded, and mediaurl is what to save
 * - idle: the recording was abandoned, so the page is no longer busy
 *
 * This file is shared by several Poodll plugins and kept in sync between them,
 * so it must not mention any component name. The component is passed in.
 *
 * @copyright  2026 Justin Hunt - Poodll.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Templates from 'core/templates';
import Notification from 'core/notification';
import {get_strings as getStrings} from 'core/str';
import Recorder from './recorder';
import UploadTransport from './transport_upload';

// The strings the skin uses. Each is lr_<key> in the component's language file.
const STRINGS = ['recorder', 'stepdone', 'steprecord', 'stepcheck', 'stepsave', 'limit', 'nolimit',
    'startrecording', 'willaskaudio', 'willaskvideo', 'readywhenyouare', 'waitingaudio', 'waitingvideo', 'chooseallow',
    'blockedaudio', 'blockedvideo', 'blockedhelp', 'notfoundaudio', 'notfoundvideo', 'notfoundhelp',
    'deviceerror', 'deviceerrorhelp', 'tryagain', 'nosupport', 'nosupporthelp', 'nosecure', 'nosecurehelp', 'getready',
    'startsin', 'cancel',
    'recording', 'paused', 'stop', 'pause', 'resume', 'recordagain', 'usethisaudio', 'usethisvideo', 'play',
    'pauseplayback', 'position', 'uploading', 'uploadprogress', 'preparingaudio', 'preparingvideo', 'preparingfile',
    'preparinghelp', 'uploadfailed', 'uploadfailedhelp', 'startover', 'readyaudio', 'readyvideo', 'readyfile',
    'clicksave', 'stillpreparing', 'replaceq', 'replace', 'keep', 'uploadinstead', 'wrongfileaudio', 'wrongfilevideo',
    'microphone', 'camera', 'recordercontrols', 'stoppedcheck', 'timeup', 'cancelled', 'uploaded'];

// The parts of the template shown in each state.
const VIEWS = {
    ready: ['ready'],
    permission: ['permission'],
    deviceerror: ['deviceerror'],
    nosupport: ['nosupport'],
    countdown: ['countdown'],
    recording: ['recording'],
    paused: ['recording'],
    check: ['player', 'checkbuttons'],
    saving: ['saving'],
    uploaderror: ['uploaderror'],
    done: ['donemsg', 'player'],
};

// How long to wait for Cloud Poodll to convert a file before saying it is still being prepared.
const READY_TIMEOUT = {audio: 180, video: 600};

/**
 * Format seconds as m:ss.
 *
 * @param {Number} sec
 * @returns {String}
 */
const fmt = (sec) => {
    sec = Math.max(0, Math.floor(sec));
    return Math.floor(sec / 60) + ':' + String(sec % 60).padStart(2, '0');
};

class Skin {

    /**
     * @param {HTMLElement} root
     * @param {Object} config see init()
     * @param {Object} s the strings
     */
    constructor(root, config, s) {
        this.root = root;
        this.config = config;
        this.s = s;
        this.isvideo = config.mediatype === 'video';
        this.state = null;
        this.take = null;
        this.mediaurl = null;
        this.uploadseq = 0;
        this.counttimer = null;
        this.cardvisible = true;
        this.recorder = new Recorder({
            mediatype: config.mediatype,
            noaudiofilters: config.noaudiofilters,
            timelimit: config.timelimit,
            ontick: (elapsed, level, paused) => this.onTick(elapsed, paused),
            onlimit: () => this.stopRecording(true),
        });
        this.transport = new UploadTransport({wsfunction: config.wsfunction, wsargs: config.wsargs});
    }

    /**
     * Find a part of the recorder by its data-region.
     *
     * @param {String} name
     * @returns {HTMLElement|null}
     */
    region(name) {
        return this.root.querySelector('[data-region="' + name + '"]');
    }

    str(key, param) {
        const text = this.s[key] || key;
        return param === undefined ? text : text.replace('{$a}', param);
    }

    /**
     * Pick the audio or video version of a string.
     *
     * @param {String} key the string name without "audio" or "video" on the end
     * @returns {String}
     */
    media(key) {
        return this.str(key + (this.isvideo ? 'video' : 'audio'));
    }

    async render() {
        const steps = [this.str('steprecord')];
        if (this.config.asktouse) {
            steps.push(this.str('stepcheck'));
        }
        steps.push(this.str('stepsave'));
        const strings = Object.assign({}, this.s, {
            willask: this.media('willask'),
            waiting: this.media('waiting'),
            usethis: this.media('usethis'),
        });
        const context = {
            isvideo: this.isvideo,
            steps: steps.map((label, i) => ({label: label, number: i + 1})),
            limittext: this.config.timelimit ? this.str('limit', fmt(this.config.timelimit)) : this.str('nolimit'),
            allowpause: this.config.allowpause,
            allowupload: this.config.allowupload,
            // Browsers only allow recording on https pages (and localhost), so say that rather than blame the browser.
            insecure: !window.isSecureContext,
            accept: this.isvideo ? 'video/*' : 'audio/*,.webm,.m4a,.mp4,.ogg',
            s: strings,
        };
        const {html, js} = await Templates.renderForPromise(this.config.component + '/localrecorder/recorder', context);
        Templates.replaceNodeContents(this.root, html, js);
        this.mediael = this.region(this.isvideo ? 'video' : 'audio');
        this.registerEvents();
        this.go(Recorder.isSupported(this.config.mediatype) ? 'ready' : 'nosupport');
    }

    registerEvents() {
        this.root.addEventListener('click', (e) => {
            const button = e.target.closest('[data-action]');
            if (button && this.root.contains(button)) {
                this.onAction(button.dataset.action);
            }
        });

        const file = this.region('file');
        if (file) {
            file.addEventListener('change', () => {
                const chosen = file.files && file.files[0];
                file.value = '';
                if (chosen) {
                    this.useFile(chosen);
                }
            });
        }

        const devices = this.region('devices');
        devices.addEventListener('change', () => {
            this.recorder.setDevice(this.isvideo ? 'video' : 'audio', devices.value);
        });

        const wave = this.region('wave');
        wave.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                e.preventDefault();
                this.seek(this.position() + (e.key === 'ArrowRight' ? 2 : -2));
            }
        });
        wave.addEventListener('pointerdown', (e) => {
            const rect = wave.getBoundingClientRect();
            this.seek(Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width)) * this.duration());
        });

        this.mediael.addEventListener('ended', () => this.showPlaying(false));
        this.mediael.addEventListener('pause', () => this.showPlaying(false));
        this.mediael.addEventListener('play', () => this.showPlaying(true));

        // The mini bar appears when the Stop and Pause buttons scroll out of view. On a phone a
        // video card can be mostly on screen with its buttons below the fold.
        if ('IntersectionObserver' in window) {
            new IntersectionObserver((entries) => {
                this.cardvisible = entries[0].isIntersecting;
                this.showMini();
            }, {threshold: 0.9}).observe(this.root.querySelector('[data-state="recording"] .plr-line'));
        }
        window.addEventListener('resize', () => this.drawWave());
    }

    onAction(action) {
        switch (action) {
            case 'record':
            case 'replace':
                this.startRecordFlow();
                break;
            case 'again':
                if (this.take && this.take.file) {
                    this.take = null;
                    this.go('ready');
                    this.emit('idle');
                } else {
                    this.startRecordFlow();
                }
                break;
            case 'cancel':
                this.cancelCountdown();
                break;
            case 'stop':
                this.stopRecording(false);
                break;
            case 'ministop':
                this.stopRecording(false);
                this.region('card').scrollIntoView({behavior: 'smooth', block: 'center'});
                break;
            case 'pause':
                this.togglePause();
                break;
            case 'play':
                this.togglePlay();
                break;
            case 'use':
            case 'retry':
                this.upload();
                break;
            case 'askreplace':
                this.stopPlayback();
                this.region('againrow').hidden = true;
                this.root.querySelector('[data-state="donebuttons"]').hidden = false;
                this.region('confirm').hidden = false;
                this.root.querySelector('[data-action="replace"]').focus();
                break;
            case 'keep':
                this.root.querySelector('[data-state="donebuttons"]').hidden = true;
                this.region('againrow').hidden = false;
                this.root.querySelector('[data-action="askreplace"]').focus();
                break;
            case 'pickfile':
                this.region('file').click();
                break;
        }
    }

    /**
     * Change state, show its parts and move focus to its main button.
     *
     * @param {String} state
     * @param {String} announcement read out by screen readers
     */
    go(state, announcement) {
        const focuswashere = !document.activeElement || document.activeElement === document.body ||
            this.root.contains(document.activeElement);
        this.state = state;
        let parts = VIEWS[state].slice();
        const isfile = this.take && this.take.file;
        if (isfile && (state === 'saving' || state === 'done')) {
            parts = parts.filter((part) => part !== 'player');
            parts.unshift('filechip');
        }
        this.root.querySelectorAll('[data-state]').forEach((el) => {
            el.hidden = parts.indexOf(el.dataset.state) === -1;
        });
        this.showSteps();
        this.showMedia();
        this.showFoot();
        this.showMini();
        const alt = this.region('alt');
        if (alt) {
            alt.hidden = !(state === 'ready' || state === 'deviceerror' || state === 'nosupport');
        }
        if (state === 'ready') {
            this.region('readyhelp').textContent = this.devicesknown ? this.str('readywhenyouare') : this.media('willask');
        }
        if (state === 'done') {
            this.region('confirm').hidden = true;
            this.region('againrow').hidden = false;
        }
        if (parts.indexOf('player') !== -1) {
            this.preparePlayer();
        }
        if (focuswashere) {
            const target = this.root.querySelector('[data-state]:not([hidden]) [data-focus]');
            if (target) {
                target.focus({preventScroll: true});
            }
        }
        if (announcement) {
            this.announce(announcement);
        }
    }

    stepIndex() {
        const last = this.config.asktouse ? 2 : 1;
        switch (this.state) {
            case 'check':
                return 1;
            case 'saving':
            case 'uploaderror':
                return last;
            case 'done':
                return last + 1;
            default:
                return 0;
        }
    }

    showSteps() {
        const current = this.stepIndex();
        this.root.querySelectorAll('[data-step]').forEach((li, i) => {
            li.classList.toggle('plr-done', i < current);
            li.classList.toggle('plr-current', i === current);
            if (i === current) {
                li.setAttribute('aria-current', 'step');
            } else {
                li.removeAttribute('aria-current');
            }
            li.querySelector('[data-region="stepdone"]').hidden = i >= current;
        });
    }

    /**
     * Video only: show the live camera while recording, and the recording afterwards.
     */
    showMedia() {
        if (!this.isvideo) {
            return;
        }
        const state = this.state;
        const isfile = this.take && this.take.file;
        const hide = isfile || ['permission', 'deviceerror', 'nosupport'].indexOf(state) !== -1;
        this.region('media').hidden = hide;
        const live = ['countdown', 'recording', 'paused'].indexOf(state) !== -1;
        const video = this.mediael;
        if (live && this.recorder.stream) {
            if (video.srcObject !== this.recorder.stream) {
                video.removeAttribute('src');
                video.srcObject = this.recorder.stream;
                video.muted = true;
                video.play().catch(() => null);
            }
        } else if (video.srcObject) {
            video.pause();
            video.srcObject = null;
        }
        this.region('placeholder').hidden = live || !!(this.take && !isfile && state !== 'ready');
        this.region('recbadge').hidden = state !== 'recording';
    }

    async showDevices() {
        const kind = this.isvideo ? 'video' : 'audio';
        const devices = (await this.recorder.listDevices())[kind];
        const select = this.region('devices');
        const current = this.recorder.deviceids[kind] || (this.recorder.stream &&
            this.recorder.stream.getTracks().filter((t) => t.kind === kind).map((t) => t.getSettings().deviceId)[0]);
        select.innerHTML = '';
        devices.forEach((device) => {
            const option = document.createElement('option');
            option.value = device.id;
            option.textContent = device.label;
            option.selected = device.id === current;
            select.appendChild(option);
        });
        this.devicecount = devices.length;
        this.showFoot();
    }

    showFoot() {
        this.region('foot').hidden = !(this.devicecount > 1 && this.state === 'ready');
    }

    showMini() {
        const mini = this.region('mini');
        const recording = this.state === 'recording' || this.state === 'paused';
        mini.hidden = !(recording && !this.cardvisible);
    }

    announce(text) {
        const live = this.region('live');
        live.textContent = '';
        setTimeout(() => {
            live.textContent = text;
        }, 50);
    }

    emit(type, extra) {
        const detail = Object.assign({type: type}, extra || {});
        this.root.dispatchEvent(new CustomEvent('localrecorder', {bubbles: true, detail: detail}));
    }

    /* ---------- Recording ---------- */

    async startRecordFlow() {
        this.stopPlayback();
        this.transport.cancel();
        this.uploadseq++;
        const waiting = setTimeout(() => this.go('permission', this.media('waiting')), 400);
        try {
            await this.recorder.open();
        } catch (e) {
            clearTimeout(waiting);
            this.showDeviceError(e);
            return;
        }
        clearTimeout(waiting);
        this.devicesknown = true;
        this.showDevices();
        this.emit('recording', {action: 'started'});
        this.countdown();
    }

    showDeviceError(e) {
        const name = e && e.name;
        let title = this.str('deviceerror');
        let help = this.str('deviceerrorhelp');
        if (name === 'NotAllowedError' || name === 'SecurityError') {
            title = this.media('blocked');
            help = this.str('blockedhelp');
        } else if (name === 'NotFoundError' || name === 'OverconstrainedError') {
            title = this.media('notfound');
            help = this.str('notfoundhelp');
            // A device chosen earlier may have been unplugged. Use the default next time.
            this.recorder.setDevice('audio', '');
            this.recorder.setDevice('video', '');
        }
        this.region('errortitle').textContent = title;
        this.region('errorhelp').textContent = help;
        this.go('deviceerror', title);
        this.emit('idle');
    }

    countdown() {
        let count = 3;
        const show = () => {
            this.region('count').textContent = count;
            this.region('countmsg').textContent = this.str('startsin', count);
        };
        show();
        this.go('countdown', this.str('startsin', count));
        const step = () => {
            count--;
            if (count <= 0) {
                this.counttimer = null;
                this.beginRecording();
                return;
            }
            show();
            this.counttimer = setTimeout(step, 1000);
        };
        this.counttimer = setTimeout(step, 1000);
    }

    cancelCountdown() {
        clearTimeout(this.counttimer);
        this.counttimer = null;
        this.recorder.close();
        this.go(this.take && this.mediaurl ? 'done' : 'ready', this.str('cancelled'));
        this.emit('idle');
    }

    beginRecording() {
        this.take = null;
        this.mediaurl = null;
        this.recorder.start();
        const limitbar = this.region('limitbar');
        limitbar.hidden = !this.config.timelimit;
        limitbar.firstElementChild.style.width = '0';
        this.levelhistory = [];
        this.onTick(0, false);
        this.go('recording', this.str('recording'));
    }

    onTick(elapsed, paused) {
        let clock = fmt(elapsed);
        if (this.config.timelimit) {
            clock += ' / ' + fmt(this.config.timelimit);
            this.region('limitbar').firstElementChild.style.width = Math.min(100, elapsed / this.config.timelimit * 100) + '%';
        }
        this.region('clock').textContent = clock;
        this.region('miniclock').textContent = clock;
        if (!paused) {
            this.drawMeter();
        }
    }

    togglePause() {
        const paused = this.state === 'paused';
        if (paused) {
            this.recorder.resume();
        } else {
            this.recorder.pause();
        }
        this.showPauseControls(!paused);
        this.go(paused ? 'recording' : 'paused', paused ? this.str('recording') : this.str('paused'));
        this.drawMeter();
    }

    /**
     * Show the controls as paused or recording, in the recorder and in the mini bar.
     *
     * @param {Boolean} paused
     */
    showPauseControls(paused) {
        const label = paused ? this.str('resume') : this.str('pause');
        const flag = paused ? this.str('paused') : this.str('recording');
        ['pausebtn', 'minipausebtn'].forEach((name) => {
            const button = this.region(name);
            if (button) {
                button.textContent = label;
            }
        });
        ['recflag', 'minirecflag'].forEach((name) => this.region(name).classList.toggle('plr-paused', paused));
        this.region('recflagtext').textContent = flag;
        this.region('minirecflagtext').textContent = flag;
    }

    async stopRecording(timeup) {
        if (this.state !== 'recording' && this.state !== 'paused') {
            return;
        }
        // Put the pause controls back for next time.
        this.showPauseControls(false);
        const take = await this.recorder.stop();
        this.take = take;
        this.mediael.srcObject = null;
        this.mediael.src = URL.createObjectURL(take.blob);
        this.mediael.muted = false;
        const why = timeup ? this.str('timeup') + ' ' : '';
        if (this.config.asktouse) {
            this.go('check', why + this.str('stoppedcheck'));
        } else {
            this.upload(why);
        }
    }

    /* ---------- Files ---------- */

    useFile(file) {
        let type = (file.type || '').toLowerCase();
        if (!this.isvideo && /^video\/(webm|mp4|ogg|quicktime)$/.test(type)) {
            // Browsers label audio-only .webm, .mp4 and .ogg files as video. Cloud Poodll converts
            // the sound of a video to mp3 too, so treat them as audio.
            type = type.replace('video/', 'audio/');
        }
        if (type.indexOf(this.config.mediatype + '/') !== 0) {
            Notification.alert('', this.media('wrongfile'));
            return;
        }
        this.stopPlayback();
        this.take = {blob: file, mimetype: type, file: true, name: file.name};
        this.region('filename').textContent = file.name;
        this.emit('recording', {action: 'started'});
        this.upload();
    }

    /* ---------- Upload ---------- */

    thing() {
        if (this.take && this.take.file) {
            return 'file';
        }
        return this.isvideo ? 'video' : 'audio';
    }

    async upload(prefix) {
        const seq = ++this.uploadseq;
        this.stopPlayback();
        this.setProgress(0);
        this.region('uploadstatus').hidden = false;
        this.region('processing').hidden = true;
        this.go('saving', (prefix || '') + this.str('uploading'));
        let mediaurl;
        try {
            mediaurl = await this.transport.send(this.take.blob, this.take.mimetype, (p) => {
                if (seq === this.uploadseq) {
                    this.setProgress(p);
                }
            });
        } catch (e) {
            if (seq === this.uploadseq) {
                // A web service error has a message for the student, for example when the
                // assignment closed while they were recording.
                const help = e && e.errorcode && e.message ? e.message : this.str('uploadfailedhelp');
                this.root.querySelector('[data-state="uploaderror"] p').textContent = help;
                this.go('uploaderror', this.str('uploadfailed'));
            }
            return;
        }
        if (seq !== this.uploadseq) {
            return;
        }
        this.mediaurl = mediaurl;
        this.emit('awaitingprocessing', {mediaurl: mediaurl});
        this.region('uploadstatus').hidden = true;
        this.region('processing').hidden = false;
        this.region('preparingtitle').textContent = this.str('preparing' + this.thing());
        this.announce(this.str('uploaded'));
        const timeout = READY_TIMEOUT[this.take.file ? 'video' : this.config.mediatype];
        const ready = await this.transport.waitUntilReady(mediaurl, timeout);
        if (seq !== this.uploadseq) {
            return;
        }
        this.region('donetitle').textContent = this.str('ready' + this.thing());
        this.region('donehelp').textContent = ready ? this.str('clicksave') : this.str('stillpreparing');
        this.go('done', this.str('ready' + this.thing()) + ' ' + this.region('donehelp').textContent);
    }

    setProgress(fraction) {
        const pct = Math.round(fraction * 100);
        this.region('pct').textContent = pct + '%';
        const bar = this.region('upbar');
        bar.setAttribute('aria-valuenow', pct);
        bar.firstElementChild.style.width = pct + '%';
    }

    /* ---------- Playback ---------- */

    duration() {
        return this.take ? this.take.duration : 0;
    }

    position() {
        return this.mediael.currentTime || 0;
    }

    preparePlayer() {
        const el = this.mediael;
        if (el.dataset.durationfixed !== el.src) {
            // MediaRecorder files often have no duration in their header, so browsers report
            // Infinity and can not seek until they have scanned to the end once.
            el.dataset.durationfixed = el.src;
            const fix = () => {
                if (el.duration === Infinity) {
                    el.currentTime = 1e101;
                    el.addEventListener('timeupdate', () => {
                        el.currentTime = 0;
                    }, {once: true});
                }
            };
            if (el.readyState >= 1) {
                fix();
            } else {
                el.addEventListener('loadedmetadata', fix, {once: true});
            }
        }
        const wave = this.region('wave');
        wave.setAttribute('aria-valuemax', Math.round(this.duration()));
        this.showPlaying(false);
        // Wait for layout, so the canvas has its size.
        requestAnimationFrame(() => this.drawWave());
    }

    togglePlay() {
        const el = this.mediael;
        if (el.paused) {
            if (this.position() >= this.duration() - 0.05) {
                el.currentTime = 0;
            }
            el.play().catch(() => this.showPlaying(false));
        } else {
            el.pause();
        }
    }

    stopPlayback() {
        if (this.mediael && !this.mediael.paused) {
            this.mediael.pause();
        }
    }

    seek(seconds) {
        this.mediael.currentTime = Math.max(0, Math.min(this.duration(), seconds));
        this.drawWave();
    }

    showPlaying(playing) {
        this.region('playicon').hidden = playing;
        this.region('pauseicon').hidden = !playing;
        this.region('playbtn').setAttribute('aria-label', playing ? this.str('pauseplayback') : this.str('play'));
        if (playing) {
            const loop = () => {
                this.drawWave();
                if (!this.mediael.paused) {
                    requestAnimationFrame(loop);
                }
            };
            requestAnimationFrame(loop);
        } else {
            this.drawWave();
        }
    }

    /* ---------- Drawing ---------- */

    colour(name) {
        return getComputedStyle(this.region('card')).getPropertyValue(name).trim();
    }

    /**
     * Size a canvas for the screen's pixel density.
     *
     * @param {HTMLCanvasElement} canvas
     * @returns {Number} the device pixel ratio, or 0 if the canvas is not shown
     */
    fitCanvas(canvas) {
        const rect = canvas.getBoundingClientRect();
        if (!rect.width) {
            return 0;
        }
        const dpr = window.devicePixelRatio || 1;
        const w = Math.round(rect.width * dpr);
        const h = Math.round(rect.height * dpr);
        if (canvas.width !== w || canvas.height !== h) {
            canvas.width = w;
            canvas.height = h;
        }
        return dpr;
    }

    drawMeter() {
        const canvas = this.region('meter');
        if (!canvas) {
            return;
        }
        const dpr = this.fitCanvas(canvas);
        if (!dpr) {
            return;
        }
        const g = canvas.getContext('2d');
        const w = canvas.width;
        const h = canvas.height;
        g.clearRect(0, 0, w, h);
        const bw = 3 * dpr;
        const gap = 2 * dpr;
        const levels = this.recorder.levels.slice(-Math.floor(w / (bw + gap)));
        g.fillStyle = this.colour(this.state === 'paused' ? '--plr-muted' : '--plr-accent');
        levels.forEach((level, i) => {
            const bh = Math.max(2 * dpr, level * h);
            g.fillRect(w - (levels.length - i) * (bw + gap), (h - bh) / 2, bw, bh);
        });
    }

    drawWave() {
        const canvas = this.region('wave');
        if (!canvas || !this.take || this.take.file) {
            return;
        }
        const duration = this.duration();
        const pos = Math.min(this.position(), duration);
        this.region('ptime').textContent = fmt(pos) + ' / ' + fmt(duration);
        canvas.setAttribute('aria-valuenow', Math.round(pos));
        canvas.setAttribute('aria-valuetext', fmt(pos) + ' / ' + fmt(duration));
        const dpr = this.fitCanvas(canvas);
        if (!dpr) {
            return;
        }
        const levels = this.take.levels.length ? this.take.levels : [0.1];
        const g = canvas.getContext('2d');
        const w = canvas.width;
        const h = canvas.height;
        g.clearRect(0, 0, w, h);
        const bw = 3 * dpr;
        const gap = 2 * dpr;
        const bars = Math.max(1, Math.floor(w / (bw + gap)));
        const played = duration ? pos / duration * w : 0;
        const accent = this.colour('--plr-accent');
        const line = this.colour('--plr-line');
        for (let i = 0; i < bars; i++) {
            const a = Math.floor(i / bars * levels.length);
            const b = Math.max(a + 1, Math.floor((i + 1) / bars * levels.length));
            let peak = 0;
            for (let j = a; j < b && j < levels.length; j++) {
                peak = Math.max(peak, levels[j]);
            }
            const bh = Math.max(2 * dpr, peak * h * 0.95);
            const x = i * (bw + gap);
            g.fillStyle = x < played ? accent : line;
            g.fillRect(x, (h - bh) / 2, bw, bh);
        }
    }
}

/**
 * Start a recorder.
 *
 * @param {Object} config
 * @param {String} config.rootid id of the element to put the recorder in
 * @param {String} config.component the plugin, for its strings and templates
 * @param {String} config.wsfunction the web service that returns the upload details
 * @param {Object} config.wsargs arguments for that web service
 * @param {String} config.mediatype audio or video
 * @param {Number} config.timelimit seconds, 0 for none
 * @param {Boolean} config.allowpause show the Pause button
 * @param {Boolean} config.allowupload offer "Upload a file instead"
 * @param {Boolean} config.asktouse 123 skin (true) or once skin (false)
 * @param {Boolean} config.noaudiofilters turn off the browser's noise filters
 * @returns {Promise}
 */
export const init = async(config) => {
    const root = document.getElementById(config.rootid);
    if (!root) {
        return;
    }
    try {
        const values = await getStrings(STRINGS.map((key) => ({key: 'lr_' + key, component: config.component})));
        const s = {};
        STRINGS.forEach((key, i) => {
            s[key] = values[i];
        });
        const skin = new Skin(root, config, s);
        await skin.render();
    } catch (e) {
        Notification.exception(e);
    }
};
