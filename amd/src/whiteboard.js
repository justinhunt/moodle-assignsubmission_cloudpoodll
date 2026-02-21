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
                        container.find('.wb-text-settings').show();
                    } else {
                        container.find('.wb-text-settings').hide();
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

                // --- Color Pickers ---
                container.find('.wb-color-box').click(function () {
                    $(this).next('.wb-color-picker').click();
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

                $(board.canvas).on('mousedown touchstart', function (e) {
                    if (board.tool === 'text') {
                        if ($activeTextBox && $activeTextBox.hasClass('selected')) {
                            commitText();
                        } else if (!$activeTextBox) {
                            var rect = board.canvas.getBoundingClientRect();
                            var touch = e.originalEvent.touches ? e.originalEvent.touches[0] : e;
                            var clientX = touch.clientX;
                            var clientY = touch.clientY;

                            // Only create if click is inside the actual canvas bounds
                            if (clientX >= rect.left && clientX <= rect.right &&
                                clientY >= rect.top && clientY <= rect.bottom) {
                                var x = (clientX - rect.left) / board.zoom;
                                var y = (clientY - rect.top) / board.zoom;
                                createTextBox(x, y);
                            }
                        }
                    } else {
                        commitText(); // Clicked with another tool, commit text
                    }
                });

                // Commit on tool change
                container.find('.wb-tool').click(function () {
                    commitText();
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
