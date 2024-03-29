define(['jquery','core/log'], function($,log) {
    "use strict"; // jshint ;_;

    log.debug('Interactive Transcript: initialising');

    return {

        init: function(opts){

            var component= opts['component'];
            var playerid= opts['playerid'];
            var containerid= opts['containerid'];
            var cssprefix= opts['cssprefix'];
            var lang = opts['cssprefix'];
            var config={};
            config.settings ={};
            if(playerid && $('#' + playerid).length) {
                config.component = component;
                config.prefix = cssprefix;
                config.player = $('#' + playerid)[0];
                config.title = M.util.get_string('transcripttitle',component);
                var transcript = this.transcript(config);
                $('#' + containerid).append(transcript.el());
            }
        },


// Defaults
        defaults: {
            autoscroll: true,
            clickArea: 'line', //the clickable part of line text,line,timestamp, none
            showTitle: true, //the title
            showTrackSelector: false, //the drop down box of caption tracks
            followPlayerTrack: true,
            scrollToCenter: false, //show current text in center
            stopScrollWhenInUse: true, //stop scrolling when user interacting
        },

        /*global */
        utils: {
            prefix: 'transcript',
            secondsToTime: function (timeInSeconds) {
                var hour = Math.floor(timeInSeconds / 3600);
                var min = Math.floor(timeInSeconds % 3600 / 60);
                var sec = Math.floor(timeInSeconds % 60);
                sec = (sec < 10) ? '0' + sec : sec;
                min = (hour > 0 && min < 10) ? '0' + min : min;
                if (hour > 0) {
                    return hour + ':' + min + ':' + sec;
                }
                return min + ':' + sec;
            },
            localize: function (string) {
                return string; // TODO: do something here;
            },
            createEl: function (elementName, className) {
                className = className || '';
                var el = document.createElement(elementName);
                el.className = className;
                return el;
            },
            extend: function(obj) {
                var type = typeof obj;
                if (!(type === 'function' || type === 'object' && !!obj)) {
                    return obj;
                }
                var source, prop;
                for (var i = 1, length = arguments.length; i < length; i++) {
                    source = arguments[i];
                    for (prop in source) {
                        obj[prop] = source[prop];
                    }
                }
                return obj;
            }
        },

        eventEmitter: {
            handlers_: [],
            on: function on (object, eventtype, callback) {
                if (typeof callback === 'function') {
                    this.handlers_.push([object, eventtype, callback]);
                } else {
                    throw new TypeError('Callback is not a function.');
                }
            },
            trigger: function trigger (object, eventtype) {
                this.handlers_.forEach( function(h) {
                    if (h[0] === object &&
                        h[1] === eventtype) {
                        h[2].apply();
                    }
                });
            }
        },

        scrollerProto: function(config) {

            var initHandlers = function (el) {
                var self = this;
                // The scroll event. We want to keep track of when the user is scrolling the transcript.
                el.addEventListener('scroll', function () {
                    if (self.isAutoScrolling) {

                        // If isAutoScrolling was set to true, we can set it to false and then ignore this event.
                        // It wasn't the user.
                        self.isAutoScrolling = false; // event handled
                    } else {

                        // We only care about when the user scrolls. Set userIsScrolling to true and add a nice class.
                        self.userIsScrolling = true;
                        el.classList.add('is-inuse');
                    }
                });

                // The mouseover event.
                el.addEventListener('mouseenter', function () {
                    self.mouseIsOverTranscript = true;
                });
                el.addEventListener('mouseleave', function () {
                    self.mouseIsOverTranscript = false;

                    // Have a small delay before deciding user as done interacting.
                    setTimeout(function () {

                        // Make sure the user didn't move the pointer back in.
                        if (!self.mouseIsOverTranscript) {
                            self.userIsScrolling = false;
                            el.classList.remove('is-inuse');
                        }
                    }, 1000);
                });
            };

            // Init instance variables
            var init = function (element) {
                this.element = element;
                this.userIsScrolling = false;

                //default to true in case user isn't using a mouse;
                this.mouseIsOverTranscript = true;
                this.isAutoScrolling = true;
                initHandlers.call(this, this.element);
                return this;
            };

            // Easing function for smoothness.
            var easeOut = function (time, start, change, duration) {
                return start + change * Math.sin(Math.min(1, time / duration) * (Math.PI / 2));
            };

            // Animate the scrolling.
            var scrollTo = function (element, newPos, duration) {
                var startTime = Date.now();
                var startPos = element.scrollTop;
                var self = this;

                // Don't try to scroll beyond the limits. You won't get there and this will loop forever.
                newPos = Math.max(0, newPos);
                newPos = Math.min(element.scrollHeight - element.clientHeight, newPos);
                var change = newPos - startPos;

                // This inner function is called until the elements scrollTop reaches newPos.
                var updateScroll = function () {
                    var now = Date.now();
                    var time = now - startTime;
                    self.isAutoScrolling = true;
                    element.scrollTop = easeOut(time, startPos, change, duration);
                    if (element.scrollTop !== newPos) {
                        requestAnimationFrame(updateScroll, element);
                    }
                };
                requestAnimationFrame(updateScroll, element);
            };

            // Scroll an element's parent so the element is brought into view.
            var scrollToElement = function (element) {
                if (this.canScroll()) {
                    var parent = element.parentElement;
                    var parentOffsetBottom = parent.offsetTop + parent.clientHeight;
                    var elementOffsetBottom = element.offsetTop + element.clientHeight;
                    var relTop = element.offsetTop;
                    var relBottom = (element.offsetTop + element.clientHeight);
                    var centerPosCorrection = 0;
                    var newPos;
                    /*
                                        console.log('element.offsetTop: ' + element.offsetTop );
                                        console.log('element.clientHeight: ' + element.clientHeight );
                                        console.log('parent.offsetTop: ' + parent.offsetTop );
                                        console.log('parent.scrollTop: ' + parent.scrollTop );
                                        console.log('parent.clientHeight: ' + parent.clientHeight );
                                        console.log(element);
                                        console.log(parent);
                    */
                    //scroll to center if we must
                    if (config.settings.scrollToCenter){
                        centerPosCorrection = Math.round(parent.clientHeight/2 - element.clientHeight/2);
                    }
                    // If the top of the line is above the top of the parent view, were scrolling up,
                    // so we want to move the top of the element downwards to match the top of the parent.
                    if (relTop < parent.scrollTop + centerPosCorrection) {
                        newPos = element.offsetTop  -centerPosCorrection;

                        // If the bottom of the line is below the parent view, we're scrolling down, so we want the
                        // bottom edge of the line to move up to meet the bottom edge of the parent.
                    } else if (relBottom > (parent.scrollTop + parent.clientHeight) - centerPosCorrection) {
                        newPos = elementOffsetBottom + centerPosCorrection;
                    }

                    // Don't try to scroll if we haven't set a new position.  If we didn't
                    // set a new position the line is already in view (i.e. It's not above
                    // or below the view)
                    // And don't try to scroll when the element is already in position.
                    if (newPos !== undefined && parent.scrollTop !== newPos) {
                        scrollTo.call(this, parent, newPos, 400);
                    }
                }
            };


            // Return whether the element is scrollable.
            var canScroll = function () {
                var el = this.element;
                //console.log(el.scrollHeight + ' ' + el.offsetHeight);
                return el.scrollHeight > el.offsetHeight;
            };

            // Return whether the user is interacting with the transcript.
            var inUse = function () {
                return this.userIsScrolling;
            };

            return {
                init: init,
                to : scrollToElement,
                canScroll : canScroll,
                inUse : inUse
            }
        },

        scroller:  function(element,config) {
            return Object.create(this.scrollerProto(config)).init(element);
        },


        /*global config*/
        trackList: function(config) {
            var activeTrack;
            return {
                get: function () {
                    var validTracks = [];
                    var i, track;
                    config.tracks = config.player.textTracks;
                    for (i = 0; i < config.tracks.length; i++) {
                        track = config.tracks[i];
                        if (track.kind === 'captions' || track.kind === 'subtitles') {
                            validTracks.push(track);
                        }
                    }
                    return validTracks;
                },
                active: function (tracks) {
                    var i, track;
                    for (i = 0; i < config.tracks.length; i++) {
                        track = config.tracks[i];
                        if (track.mode === 'showing') {
                            activeTrack = track;
                            return track;
                        }
                    }
                    // fallback to first track
                    return activeTrack || tracks[0];
                },
            };
        },

        /*globals utils, eventEmitter,scrollable*/

        widget:  function(config) {
            var that = this;
            var thewidget = {};
            thewidget.element = {};
            thewidget.body = {};
            var on = function (event, callback) {
                eventEmitter.on(that, event, callback);
            };
            var trigger = function (event) {
                eventEmitter.trigger(that, event);
            };
            var createTitle = function () {
                var header = that.utils.createEl('header', config.prefix + '-header');
                header.textContent = config.title;
                return header;
            };
            var createSelector = function (){
                var selector = that.utils.createEl('select', config.prefix + '-selector');
                config.validTracks.forEach(function (track, i) {
                    var option = document.createElement('option');
                    option.value = i;
                    option.textContent = track.label + ' (' + track.language + ')';
                    selector.appendChild(option);
                });
                selector.addEventListener('change', function (e) {
                    setTrack(document.querySelector('#' + config.prefix + '-' + config.player.id + ' option:checked').value);
                    trigger('trackchanged');
                });
                return selector;
            };
            var clickToSeekHandler = function (event) {
                var clickedClasses = event.target.classList;
                var clickedTime = event.target.getAttribute('data-begin') || event.target.parentElement.getAttribute('data-begin');
                if (clickedTime !== undefined && clickedTime !== null) { // can be zero
                    if ((config.settings.clickArea === 'line') || // clickArea: 'line' activates on all elements
                        (config.settings.clickArea === 'timestamp' && clickedClasses.contains(config.prefix + '-timestamp')) ||
                        (config.settings.clickArea === 'text' && clickedClasses.contains(config.prefix + '-text'))) {
                        config.player.currentTime =clickedTime ;
                    }
                }
            };
            var createLine = function (cue) {
                var line = that.utils.createEl('div', config.prefix +'-line');
                var timestamp = that.utils.createEl('span',config.prefix + '-timestamp');
                var text = that.utils.createEl('span', config.prefix + '-text');
                line.setAttribute('data-begin', cue.startTime);
                line.setAttribute('tabindex', thewidget._options.tabIndex || 0);
                timestamp.textContent = that.utils.secondsToTime(cue.startTime);
                text.innerHTML = cue.text;
                line.appendChild(timestamp);
                line.appendChild(text);
                return line;
            };
            var createTranscriptBody = function (track) {
                if (typeof track !== 'object') {
                    track = config.player.textTracks()[track];
                }
                var body = that.utils.createEl('div', config.prefix + '-body');
                var line, i;
                var fragment = document.createDocumentFragment();
                // activeCues returns null when the track isn't loaded (for now?)
                if (!track.activeCues) {
                    // If cues aren't loaded, set mode to hidden, wait, and try again.
                    // But don't hide an active track. In that case, just wait and try again.
                    if (track.mode !== 'showing') {
                        track.mode = 'hidden';
                    }
                    window.setTimeout(function() {
                        createTranscriptBody(track);
                    }, 100);
                } else {
                    var cues = track.cues;
                    for (i = 0; i < cues.length; i++) {
                        line = createLine(cues[i]);
                        fragment.appendChild(line);
                    }
                    body.innerHTML = '';
                    body.appendChild(fragment);
                    body.setAttribute('lang', track.language);
                    body.scroll = that.scroller(body,config);
                    body.addEventListener('click', clickToSeekHandler);
                    thewidget.element.replaceChild(body, thewidget.body);
                    thewidget.body = body;
                }

            };
            var create = function (options) {
                var el = document.createElement('div');
                thewidget._options = options;
                thewidget.element = el;
                el.setAttribute('id', config.prefix + '-' + config.player.id);
                if (config.settings.showTitle) {
                    var title = createTitle();
                    el.appendChild(title);
                }
                if (config.settings.showTrackSelector) {
                    var selector = createSelector();
                    el.appendChild(selector);
                }
                thewidget.body = that.utils.createEl('div',config.prefix + '-body');
                el.appendChild(thewidget.body);
                setTrack(config.currentTrack);
                return this;
            };
            var setTrack = function (track, trackCreated) {
                createTranscriptBody(track, trackCreated);
            };
            var setCue = function (time) {
                var active, i, line, begin, end;
                var lines = thewidget.body.children;
                for (i = 0; i < lines.length; i++) {
                    line = lines[i];
                    begin = line.getAttribute('data-begin');
                    if (i < lines.length - 1) {
                        end = lines[i + 1].getAttribute('data-begin');
                    } else {
                        end = config.player.duration || Infinity;
                    }
                    if (time > begin && time < end) {
                        if (!line.classList.contains('is-active')) { // don't update if it hasn't changed
                            line.classList.add('is-active');
                            if (config.settings.autoscroll && !(config.settings.stopScrollWhenInUse && thewidget.body.scroll.inUse())) {
                                thewidget.body.scroll.to(line);
                            }
                        }
                    } else {
                        line.classList.remove('is-active');
                    }
                }
            };
            var el = function () {
                return thewidget.element;
            };
            return {
                create: create,
                setTrack: setTrack,
                setCue: setCue,
                el : el,
                on: on,
                trigger: trigger,
            };
        },

        transcript: function(config){
            var that=this;
            var options=this.defaults;
            this.utils.prefix='transcript';

            config.validTracks = this.trackList(config).get();
            config.currentTrack = this.trackList(config).active(config.validTracks);
            config.settings = options;
            config.widget = this.widget(config).create(options);

            var timeUpdate = function () {
                config.widget.setCue(config.player.currentTime);
            };
            var updateTrack = function () {
                config.currentTrack = that.trackList(config).active(config.validTracks);
                config.widget.setTrack(config.currentTrack);
            };
            if (config.validTracks.length > 0) {
                updateTrack();
                config.player.ontimeupdate = timeUpdate;
                if (config.settings.followPlayerTrack) {
                    config.player.oncaptionstrackchange = updateTrack;
                    config.player.onsubtitlestrackchange = updateTrack;
                }
            } else {
                throw new Error('transcript: No tracks found!');
            }
            return {
                el: function () {
                    return config.widget.el();
                },
                setTrack: config.widget.setTrack
            };
        }

    };//end of return object
});