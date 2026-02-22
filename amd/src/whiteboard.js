define(['jquery', 'core/log', 'core/ajax', 'core/notification', 'core/str', 'assignsubmission_cloudpoodll/drawing_board'],
    function ($, log, Ajax, Notification, Str, DrawingBoard) {
        "use strict";

        return {

            strings: {},

            init: function (uniqueid) {
                var container = $('#' + uniqueid);
                var canvasid = uniqueid + '_canvas';
                var board = new DrawingBoard(canvasid);
                this.setup_strings(container);
                this.setup_controls(container, board);
            },

            setup_strings: function () {
                var self = this;
                // Set up strings
                Str.get_strings([
                    { "key": "save", "component": "assignsubmission_cloudpoodll" },
                    { "key": "saving", "component": "assignsubmission_cloudpoodll" },
                    { "key": "saved", "component": "assignsubmission_cloudpoodll" },
                    { "key": "confirmclearcanvas", "component": "assignsubmission_cloudpoodll" },
                    { "key": "enterthetext", "component": "assignsubmission_cloudpoodll" },
                ]).done(function (s) {
                    var i = 0;
                    self.strings.save = s[i++];
                    self.strings.saving = s[i++];
                    self.strings.saved = s[i++];
                    self.strings.confirmclearcanvas = s[i++];
                    self.strings.enterthetext = s[i++];
                });
            },

            setup_controls: function (container, board) {
                var self = this;

                // Set background image if provided
                var backimage = container.data('backimage');
                if (backimage) {
                    board.setBackImage(backimage);
                }

                // Set vector data if provided
                var vdata = container.data('vdata');
                if (vdata) {
                    board.setVectorData(vdata);
                }


                // --- Tool Selection ---
                container.find('.wb-tool').click(function () {
                    var tool = $(this).data('tool');
                    board.tool = tool;
                    container.find('.wb-tool').removeClass('active');
                    $(this).addClass('active');

                    // Toggle top bar panels
                    if (tool === 'text') {
                        container.find('.wb-stroke-sizes').hide();
                        container.find('.wb-stamp-settings').hide();
                        container.find('.wb-text-settings').show();
                    } else if (tool === 'stamp') {
                        container.find('.wb-stroke-sizes').hide();
                        container.find('.wb-text-settings').hide();
                        container.find('.wb-stamp-settings').css('display', 'flex');
                    } else {
                        container.find('.wb-text-settings').hide();
                        container.find('.wb-stamp-settings').hide();
                        container.find('.wb-stroke-sizes').show();
                    }
                });

                // --- Stroke Size ---
                container.find('.wb-size').click(function () {
                    var size = $(this).data('size');
                    board.strokeSize = size;
                    container.find('.wb-size').removeClass('active');
                    $(this).addClass('active');
                });

                // --- Stamp Selection & Paths ---
                var stampPaths = {
                    'check-circle': 'M504 256c0 136.967-111.033 248-248 248S8 392.967 8 256 119.033 8 256 8s248 111.033 248 248zM227.314 387.314l184-184c6.248-6.248 6.248-16.379 0-22.627l-22.627-22.627c-6.248-6.249-16.379-6.249-22.628 0L216 308.118l-70.059-70.059c-6.248-6.248-16.379-6.248-22.628 0l-22.627 22.627c-6.248 6.248-6.248 16.379 0 22.627l104 104c6.249 6.249 16.379 6.249 22.628 0z',
                    'times-circle': 'M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm121.6 313.1c4.7 4.7 4.7 12.3 0 17L338 377.6c-4.7 4.7-12.3 4.7-17 0L256 312l-65.1 65.6c-4.7 4.7-12.3 4.7-17 0L134.4 338c-4.7-4.7-4.7-12.3 0-17l65.6-65-65.6-65.1c-4.7-4.7-4.7-12.3 0-17l39.6-39.6c4.7-4.7 12.3-4.7 17 0l65 65.7 65.1-65.6c4.7-4.7 12.3-4.7 17 0l39.6 39.6c4.7 4.7 4.7 12.3 0 17L312 256l65.6 65.1z',
                    'exclamation-triangle': 'M506.3 417l-213.3-364c-16.33-28-57.54-28-73.98 0l-213.2 364C-10.59 444.9 9.849 480 42.74 480h426.6C502.1 480 522.6 445 506.3 417zM232 168c0-8.836 7.164-16 16-16h16c8.836 0 16 7.164 16 16v148c0 8.836-7.164 16-16 16h-16c-8.836 0-16-7.164-16-16V168zm24 272c-17.67 0-32-14.33-32-32s14.33-32 32-32 32 14.33 32 32-14.33 32-32 32z',
                    'lightbulb': 'M355.2 331.1C339.4 345.2 320 357.7 320 384h-128c0-26.3-19.4-38.8-35.2-52.9C119.8 298.5 96 261 96 216c0-88.4 71.6-160 160-160s160 71.6 160 160c0 45-23.8 82.5-60.8 115.1zM256 512c26.5 0 48-21.5 48-48h-96c0 26.5 21.5 48 48 48zm64-112h-128c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h128c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16z',
                    'heart': 'M462.3 62.6C407.5 15.9 326 24.3 275.7 76.2L256 96.5l-19.7-20.3C186.1 24.3 104.5 15.9 49.7 62.6c-62.8 53.6-66.1 149.8-9.9 207.9l193.5 199.8c12.5 12.9 32.8 12.9 45.3 0l193.5-199.8c56.3-58.1 53-154.3-9.8-207.9z'
                };

                var activeStamp = 'check-circle';
                container.find('.wb-stamp-pick').click(function () {
                    activeStamp = $(this).data('stamp');
                    container.find('.wb-stamp-pick').removeClass('active');
                    $(this).addClass('active');
                    if ($activeStampBox) {
                        updateStampSVG();
                    }
                });

                // --- Color Pickers ---
                container.find('.wb-color-box').click(function () {
                    $(this).siblings('.wb-color-picker').click();
                });

                // --- Transparent options ---
                container.find('.wb-stroke-clear').click(function () {
                    board.strokeColor = 'transparent';
                    container.find('.wb-stroke-color').css('background-color', 'transparent');
                });

                container.find('.wb-fill-clear').click(function () {
                    board.fillColor = 'transparent';
                    container.find('.wb-fill-color').css('background-color', 'transparent');
                });

                container.find('.wb-bg-clear').click(function () {
                    board.bgColor = 'transparent';
                    container.find('.wb-bg-color').css('background-color', 'transparent');
                    board.redraw();
                });

                container.find('.wb-stroke-picker').on('input change', function () {
                    var color = $(this).val();
                    board.strokeColor = color;
                    container.find('.wb-stroke-color').css('background-color', color);
                });

                container.find('.wb-fill-picker').on('input change', function () {
                    var color = $(this).val();
                    board.fillColor = color;
                    container.find('.wb-fill-color').css('background-color', color);
                });

                container.find('.wb-bg-picker').on('input change', function () {
                    var color = $(this).val();
                    board.bgColor = color;
                    container.find('.wb-bg-color').css('background-color', color);
                    board.redraw();
                });

                // --- Sidebar Toggle ---
                container.find('.wb-sidebar-toggle').click(function () {
                    container.find('.wb-sidebar').toggleClass('collapsed');
                });

                // --- Actions ---
                container.find('.undo-whiteboard').click(function () { board.undo(); });
                container.find('.redo-whiteboard').click(function () { board.redo(); });
                container.find('.clear-whiteboard').click(function () {
                    if (confirm(self.strings.confirmclearcanvas)) {
                        board.clear();
                    }
                });

                var baseWidth = container.data('width') || 800;
                var baseHeight = container.data('height') || 600;

                $(board.canvas).on('wb:resized', function (e, dims) {
                    baseWidth = dims.width;
                    baseHeight = dims.height;
                    updateZoom();
                });

                var updateZoom = function () {
                    var $canvas = $(board.canvas);
                    $canvas.css({
                        'transform': 'scale(' + board.zoom + ')',
                        'transform-origin': '0 0'
                    });
                    var $wb = container.find('.assignsubmission_cloudpoodll_whiteboard');
                    $wb.css({
                        'width': (baseWidth * board.zoom) + 'px',
                        'height': (baseHeight * board.zoom) + 'px'
                    });
                };

                container.find('.zoom-in').click(function () {
                    board.zoom += 0.1;
                    updateZoom();
                });
                container.find('.zoom-out').click(function () {
                    if (board.zoom > 0.5) {
                        board.zoom -= 0.1;
                        updateZoom();
                    }
                });

                // --- Interactive Text Tool ---
                var $activeTextBox = null;

                var commitText = function () {
                    if (!$activeTextBox) return;
                    var $textarea = $activeTextBox.find('textarea');
                    var text = $textarea.val();
                    if (text.trim() !== '') {
                        var pos = $activeTextBox.position();
                        // Adjust position based on zoom
                        var x = pos.left / board.zoom;
                        var y = pos.top / board.zoom;

                        var step = {
                            type: 'text',
                            text: text,
                            points: [{ x: x, y: y }],
                            width: Math.max(10, ($activeTextBox.width() - 8) / board.zoom), // -8px for padding match
                            strokeColor: board.strokeColor,
                            fillColor: board.fillColor,
                            strokeSize: board.strokeSize,
                            fontFamily: container.find('.wb-text-font').val(),
                            fontSize: parseInt(container.find('.wb-text-size').val()),
                            isBold: container.find('.wb-text-bold').is(':checked'),
                            isItalic: container.find('.wb-text-italic').is(':checked')
                        };
                        board.addStep(step);
                        board.redraw();
                    }
                    $activeTextBox.remove();
                    $activeTextBox = null;
                };

                var createTextBox = function (x, y) {
                    commitText(); // Commit any existing one

                    var $overlay = $('<div class="wb-text-overlay editing"></div>');
                    var $textarea = $('<textarea spellcheck="false"></textarea>');

                    // Apply current styles
                    var updateStyles = function () {
                        var fontFamily = container.find('.wb-text-font').val();
                        var fontSize = container.find('.wb-text-size').val() + 'px';
                        var isBold = container.find('.wb-text-bold').is(':checked');
                        var isItalic = container.find('.wb-text-italic').is(':checked');

                        $textarea.css({
                            'font-family': fontFamily,
                            'font-size': fontSize,
                            'font-weight': isBold ? 'bold' : 'normal',
                            'font-style': isItalic ? 'italic' : 'normal',
                            'color': board.strokeColor
                        });
                    };

                    updateStyles();

                    // Listeners for live styling updates
                    container.find('.wb-text-settings input, .wb-text-settings select, .wb-stroke-picker').off('change.wblive').on('change.wblive', updateStyles);

                    $overlay.append($textarea);

                    // Position it taking zoom into account
                    $overlay.css({
                        left: x * board.zoom,
                        top: y * board.zoom
                    });

                    // Interaction logic
                    var isDragging = false;
                    var dragStartX, dragStartY, initialLeft, initialTop;

                    $overlay.on('mousedown touchstart', function (e) {
                        if ($overlay.hasClass('selected')) {
                            isDragging = true;
                            var touch = e.originalEvent.touches ? e.originalEvent.touches[0] : e;
                            dragStartX = touch.pageX;
                            dragStartY = touch.pageY;
                            var pos = $overlay.position();
                            initialLeft = pos.left;
                            initialTop = pos.top;
                            e.stopPropagation(); // Prevent board from drawing
                        }
                    });

                    $(document).on('mousemove.wbtextdrag touchmove.wbtextdrag', function (e) {
                        if (isDragging) {
                            var touch = e.originalEvent.touches ? e.originalEvent.touches[0] : e;
                            var dx = touch.pageX - dragStartX;
                            var dy = touch.pageY - dragStartY;
                            var newLeft = initialLeft + dx;
                            var newTop = initialTop + dy;

                            // Constrain to canvas
                            var maxLeft = board.canvas.width * board.zoom - $overlay.outerWidth();
                            var maxTop = board.canvas.height * board.zoom - $overlay.outerHeight();

                            newLeft = Math.max(0, Math.min(newLeft, maxLeft));
                            newTop = Math.max(0, Math.min(newTop, maxTop));

                            $overlay.css({
                                left: newLeft,
                                top: newTop
                            });
                        }
                    });

                    $(document).on('mouseup.wbtextdrag touchend.wbtextdrag', function (e) {
                        isDragging = false;
                    });

                    // Switch to selected mode on blur
                    $textarea.on('blur', function () {
                        $overlay.removeClass('editing').addClass('selected');
                    });

                    // Switch back to editing on double click or if it's selected and clicked
                    $overlay.on('click', function (e) {
                        if ($overlay.hasClass('selected')) {
                            $overlay.removeClass('selected').addClass('editing');
                            $textarea.focus();
                            e.stopPropagation();
                        }
                    });

                    container.find('.assignsubmission_cloudpoodll_whiteboard').append($overlay);
                    $textarea.focus();
                    $activeTextBox = $overlay;
                };

                // --- Interactive Stamp Tool ---
                var $activeStampBox = null;

                var commitStamp = function () {
                    if (!$activeStampBox) return;
                    var pos = $activeStampBox.position();
                    // Adjust position and size based on zoom
                    var x = pos.left / board.zoom;
                    var y = pos.top / board.zoom;
                    var width = $activeStampBox.width() / board.zoom;
                    var height = $activeStampBox.height() / board.zoom;

                    var step = {
                        type: 'stamp',
                        stamp: $activeStampBox.data('stamp'),
                        paths: stampPaths,
                        points: [{ x: x, y: y }],
                        width: width,
                        height: height,
                        strokeColor: board.strokeColor
                    };
                    board.addStep(step);
                    board.redraw();

                    $activeStampBox.remove();
                    $activeStampBox = null;
                };

                var updateStampSVG = function () {
                    if (!$activeStampBox) return;
                    activeStamp = container.find('.wb-stamp-pick.active').data('stamp');
                    $activeStampBox.data('stamp', activeStamp);
                    $activeStampBox.find('path').attr('d', stampPaths[activeStamp]);
                    $activeStampBox.find('path').attr('fill', board.strokeColor);
                };

                // Listeners for live styling updates
                container.find('.wb-stroke-picker').on('input change', function () {
                    updateStampSVG();
                });
                container.find('.wb-stroke-clear').click(function () {
                    setTimeout(updateStampSVG, 10);
                });

                var createStampBox = function (x, y) {
                    commitStamp(); // Commit any existing one

                    activeStamp = container.find('.wb-stamp-pick.active').data('stamp');

                    var $overlay = $('<div class="wb-stamp-overlay"></div>');
                    var $svg = $('<svg viewBox="0 0 512 512"><path d="' + stampPaths[activeStamp] + '" fill="' + board.strokeColor + '"/></svg>');
                    var $resizeHandle = $('<div class="wb-stamp-resize"></div>');

                    $overlay.data('stamp', activeStamp);
                    $overlay.append($svg).append($resizeHandle);

                    // Initial size
                    var size = 100;
                    $overlay.css({
                        left: x * board.zoom - (size * board.zoom) / 2,
                        top: y * board.zoom - (size * board.zoom) / 2,
                        width: size * board.zoom,
                        height: size * board.zoom
                    });

                    // Interaction logic
                    var isDragging = false, isResizing = false;
                    var dragStartX, dragStartY, initialLeft, initialTop, initialWidth, initialHeight;

                    $overlay.on('mousedown touchstart', function (e) {
                        var target = $(e.target);
                        var touch = e.originalEvent.touches ? e.originalEvent.touches[0] : e;
                        dragStartX = touch.pageX;
                        dragStartY = touch.pageY;
                        var pos = $overlay.position();
                        initialLeft = pos.left;
                        initialTop = pos.top;
                        initialWidth = $overlay.width();
                        initialHeight = $overlay.height();

                        if (target.hasClass('wb-stamp-resize')) {
                            isResizing = true;
                        } else {
                            isDragging = true;
                        }
                        e.stopPropagation(); // Prevent board from drawing
                        e.preventDefault();
                    });

                    $(document).on('mousemove.wbstampdrag touchmove.wbstampdrag', function (e) {
                        if (!isDragging && !isResizing) return;
                        var touch = e.originalEvent.touches ? e.originalEvent.touches[0] : e;

                        if (isDragging) {
                            var dx = touch.pageX - dragStartX;
                            var dy = touch.pageY - dragStartY;
                            $overlay.css({ left: initialLeft + dx, top: initialTop + dy });
                        } else if (isResizing) {
                            var dx = touch.pageX - dragStartX;
                            var newSize = Math.max(20 * board.zoom, initialWidth + dx);
                            $overlay.css({
                                width: newSize,
                                height: newSize
                            });
                        }
                    });

                    $(document).on('mouseup.wbstampdrag touchend.wbstampdrag', function (e) {
                        isDragging = false;
                        isResizing = false;
                    });

                    container.find('.assignsubmission_cloudpoodll_whiteboard').append($overlay);
                    $activeStampBox = $overlay;
                };

                $(board.canvas).on('mousedown touchstart', function (e) {
                    var rect = board.canvas.getBoundingClientRect();
                    var touch = e.originalEvent.touches ? e.originalEvent.touches[0] : e;
                    var clientX = touch.clientX;
                    var clientY = touch.clientY;

                    var isInside = (clientX >= rect.left && clientX <= rect.right && clientY >= rect.top && clientY <= rect.bottom);

                    if (board.tool === 'text') {
                        commitStamp();
                        if ($activeTextBox && $activeTextBox.hasClass('selected')) {
                            commitText();
                        } else if (!$activeTextBox && isInside) {
                            var x = (clientX - rect.left) / board.zoom;
                            var y = (clientY - rect.top) / board.zoom;
                            createTextBox(x, y);
                        }
                    } else if (board.tool === 'stamp') {
                        commitText();
                        if ($activeStampBox) {
                            commitStamp();
                        } else if (isInside) {
                            var x = (clientX - rect.left) / board.zoom;
                            var y = (clientY - rect.top) / board.zoom;
                            createStampBox(x, y);
                        }
                    } else {
                        commitText();
                        commitStamp();
                    }
                });

                // Commit on tool change
                container.find('.wb-tool').click(function () {
                    commitText();
                    commitStamp();
                });

                // --- Eyedropper Feedback ---
                $(board.canvas).on('wb:colorpicked', function (e, hex) {
                    board.strokeColor = hex;
                    container.find('.wb-stroke-color').css('background-color', hex);
                    container.find('.wb-stroke-picker').val(hex);
                    // Switch back to pencil after picking
                    container.find('[data-tool="pencil"]').click();
                });

                // --- Auto-Save and Manual Save Handling ---
                var saveTimer = null;
                var performSave = function () {
                    var btn = container.find('.save-whiteboard');
                    // If button is already disabled (saving in progress), skip.
                    if (btn.prop('disabled')) {
                        return;
                    }

                    var vectorData = board.getVectorData();
                    var imageData = board.getImageData();

                    var vectorControl = $('#' + container.data('vectorcontrol'));
                    var updateControl = $('#' + container.data('updatecontrol'));
                    var draftitemid = container.data('draftitemid');

                    btn.text(self.strings.saving).prop('disabled', true);

                    Ajax.call([{
                        methodname: 'assignsubmission_cloudpoodll_upload_whiteboard_image',
                        args: {
                            base64data: imageData,
                            draftitemid: draftitemid,
                            filename: 'img' + draftitemid + '.jpg'
                        }
                    }])[0].then(function (filename) {
                        vectorControl.val(vectorData);
                        updateControl.val(filename);
                        btn.text(self.strings.saved);
                        // Trigger change on update control to notify Moodle form.
                        updateControl.trigger('change');
                        return filename;
                    }).catch(function (e) {
                        btn.text(self.strings.save).prop('disabled', false);
                        Notification.exception(e);
                    });
                };

                container.find('.save-whiteboard').click(function () {
                    if (saveTimer) {
                        clearTimeout(saveTimer);
                    }
                    performSave();
                });

                $(board.canvas).on('wb:changed', function () {
                    var btn = container.find('.save-whiteboard');
                    btn.text(self.strings.save).prop('disabled', false);

                    if (saveTimer) {
                        clearTimeout(saveTimer);
                    }
                    saveTimer = setTimeout(function () {
                        performSave();
                    }, 1000);
                });

                // --- Load Existing Data ---
                var vectorControlId = container.data('vectorcontrol');
                if (vectorControlId) {
                    var existingJson = $('#' + vectorControlId).val();
                    if (existingJson) {
                        board.loadVectorData(existingJson);
                    }
                }
            }
        };
    });
