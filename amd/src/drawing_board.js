define(['jquery'], function ($) {
    "use strict";

    var DrawingBoard = function (canvasid) {
        this.canvas = document.getElementById(canvasid);
        this.ctx = this.canvas.getContext('2d');
        this.width = this.canvas.width;
        this.height = this.canvas.height;

        this.drawing = false;
        this.steps = [];
        this.redoStack = [];
        this.currentStep = null;

        this.tool = 'pencil';
        this.strokeColor = '#000000';
        this.fillColor = '#ffffff';
        this.bgColor = '#ffffff';
        this.strokeSize = 5;

        this.backimage = null;
        this.zoom = 1;

        this.init();
    };

    DrawingBoard.prototype.init = function () {
        var that = this;

        $(this.canvas).on('mousedown touchstart', function (e) {
            if (that.tool === 'text' || that.tool === 'stamp') {
                return; // Let whiteboard.js handle these exclusively
            }
            that.drawing = true;
            var pos = that.getPos(e);

            that.currentStep = {
                type: that.tool,
                points: [pos],
                strokeColor: that.strokeColor,
                fillColor: that.fillColor,
                strokeSize: that.strokeSize
            };

            that.redraw();
        });

        $(document).on('mousemove touchmove', function (e) {
            if (!that.drawing) {
                return;
            }
            var pos = that.getPos(e);

            if (that.tool === 'pencil' || that.tool === 'eraser' || that.tool === 'highlighter') {
                that.currentStep.points.push(pos);
            } else {
                // For shapes, we only care about start and end
                if (that.currentStep.points.length > 1) {
                    that.currentStep.points[1] = pos;
                } else {
                    that.currentStep.points.push(pos);
                }
            }

            that.redraw();
            e.preventDefault();
        });

        $(document).on('mouseup touchend', function () {
            if (that.drawing) {
                that.drawing = false;
                if (that.currentStep) {
                    this.addStep(that.currentStep);
                }
                that.currentStep = null;
                that.redraw();
            }
        }.bind(this));
    };

    DrawingBoard.prototype.addStep = function (step) {
        this.steps.push(step);
        this.redoStack = []; // Clear redo on new action
        $(this.canvas).trigger('wb:changed');
    };

    DrawingBoard.prototype.undo = function () {
        if (this.steps.length > 0) {
            this.redoStack.push(this.steps.pop());
            this.redraw();
            $(this.canvas).trigger('wb:changed');
        }
    };

    DrawingBoard.prototype.redo = function () {
        if (this.redoStack.length > 0) {
            this.steps.push(this.redoStack.pop());
            this.redraw();
            $(this.canvas).trigger('wb:changed');
        }
    };

    DrawingBoard.prototype.getPos = function (e) {
        var rect = this.canvas.getBoundingClientRect();
        var clientX = e.clientX || (e.originalEvent && e.originalEvent.touches ? e.originalEvent.touches[0].clientX : 0);
        var clientY = e.clientY || (e.originalEvent && e.originalEvent.touches ? e.originalEvent.touches[0].clientY : 0);
        return {
            x: (clientX - rect.left) / this.zoom,
            y: (clientY - rect.top) / this.zoom
        };
    };

    DrawingBoard.prototype.redraw = function () {
        this.ctx.clearRect(0, 0, this.width, this.height);

        // Fill background
        this.ctx.fillStyle = this.bgColor;
        this.ctx.fillRect(0, 0, this.width, this.height);

        if (this.backimage) {
            this.ctx.drawImage(this.backimage, 0, 0, this.width, this.height);
        }

        this.steps.forEach(function (step) {
            this.drawStep(step);
        }.bind(this));

        if (this.currentStep) {
            this.drawStep(this.currentStep);
        }
    };

    DrawingBoard.prototype.drawStep = function (step) {
        this.ctx.lineJoin = 'round';
        this.ctx.lineCap = 'round';
        this.ctx.strokeStyle = step.strokeColor;
        this.ctx.fillStyle = step.fillColor;
        this.ctx.lineWidth = step.strokeSize;

        if (step.type === 'eraser') {
            this.ctx.strokeStyle = this.bgColor;
        }

        switch (step.type) {
            case 'pencil':
            case 'eraser':
            case 'highlighter':
                this.ctx.beginPath();
                this.ctx.moveTo(step.points[0].x, step.points[0].y);
                step.points.forEach(function (p) {
                    this.ctx.lineTo(p.x, p.y);
                }.bind(this));

                if (step.type === 'highlighter') {
                    this.ctx.globalAlpha = 0.5;
                    this.ctx.stroke();
                    this.ctx.globalAlpha = 1.0;
                } else {
                    this.ctx.stroke();
                }
                break;
            case 'line':
                if (step.points.length < 2) return;
                this.ctx.beginPath();
                this.ctx.moveTo(step.points[0].x, step.points[0].y);
                this.ctx.lineTo(step.points[1].x, step.points[1].y);
                this.ctx.stroke();
                break;
            case 'rect':
                if (step.points.length < 2) return;
                var x = Math.min(step.points[0].x, step.points[1].x);
                var y = Math.min(step.points[0].y, step.points[1].y);
                var w = Math.abs(step.points[1].x - step.points[0].x);
                var h = Math.abs(step.points[1].y - step.points[0].y);
                this.ctx.fillRect(x, y, w, h);
                this.ctx.strokeRect(x, y, w, h);
                break;
            case 'circle':
                if (step.points.length < 2) return;
                var dx = step.points[1].x - step.points[0].x;
                var dy = step.points[1].y - step.points[0].y;
                var radius = Math.sqrt(dx * dx + dy * dy);
                this.ctx.beginPath();
                this.ctx.arc(step.points[0].x, step.points[0].y, radius, 0, 2 * Math.PI);
                this.ctx.fill();
                this.ctx.stroke();
                break;
            case 'stamp':
                if (step.stamp && step.paths && step.paths[step.stamp]) {
                    this.ctx.save();
                    if (step.strokeColor !== 'transparent') {
                        this.ctx.fillStyle = step.strokeColor;
                    }
                    var p = new Path2D(step.paths[step.stamp]);

                    var x = step.points[0].x;
                    var y = step.points[0].y;

                    var cx = x + step.width / 2;
                    var cy = y + step.height / 2;

                    this.ctx.translate(cx, cy);
                    var scaleX = step.width / 512;
                    var scaleY = step.height / 512;
                    this.ctx.scale(scaleX, scaleY);

                    // Move back to draw path at origin (-256, -256) since viewBox is 512x512
                    this.ctx.translate(-256, -256);

                    if (step.strokeColor !== 'transparent') {
                        this.ctx.fill(p);
                    }
                    this.ctx.restore();
                }
                break;
            case 'text':
                if (step.text) {
                    var fontStyle = step.isItalic ? "italic " : "";
                    var fontWeight = step.isBold ? "bold " : "";
                    var fontSize = step.fontSize || (step.strokeSize * 5); // Fallback to old behavior
                    var fontFamily = step.fontFamily || "Arial";

                    this.ctx.font = fontStyle + fontWeight + fontSize + "px " + fontFamily;
                    this.ctx.textBaseline = "top"; // Better alignment for multiline
                    this.ctx.fillStyle = step.strokeColor; // Use stroke color instead of fill color for text

                    var maxWidth = step.width || 9999;
                    var paragraphs = step.text.split('\n');
                    var lineHeight = fontSize * 1.2; // Approximate line height
                    var startX = step.points[0].x + 4; // Add a small padding to match textarea
                    var startY = step.points[0].y + 4;

                    var y = startY;
                    for (var i = 0; i < paragraphs.length; i++) {
                        var words = paragraphs[i].split(' ');
                        var line = '';
                        for (var n = 0; n < words.length; n++) {
                            var testLine = line + words[n] + ' ';
                            var metrics = this.ctx.measureText(testLine);
                            var testWidth = metrics.width;
                            if (testWidth > maxWidth && n > 0) {
                                this.ctx.fillText(line, startX, y);
                                line = words[n] + ' ';
                                y += lineHeight;
                            } else {
                                line = testLine;
                            }
                        }
                        this.ctx.fillText(line, startX, y);
                        y += lineHeight;
                    }
                }
                break;
        }
    };

    DrawingBoard.prototype.clear = function () {
        this.addStep({
            type: 'clear',
            bgColor: this.bgColor
        });
        this.steps = [];
        this.redraw();
    };

    DrawingBoard.prototype.setBackImage = function (url) {
        var img = new Image();
        img.onload = function () {
            this.backimage = img;

            // Adjust canvas height to match aspect ratio (keeping width fixed)
            var aspect = img.width / img.height;
            var newHeight = this.width / aspect;

            this.height = newHeight;
            this.canvas.height = newHeight;

            this.redraw();

            // Notify whiteboard.js to update container size
            $(this.canvas).trigger('wb:resized', [{ width: this.width, height: this.height }]);
        }.bind(this);
        img.src = url;
    };

    DrawingBoard.prototype.setVectorData = function (steps) {
        this.steps = steps;
        this.redraw();
    };

    DrawingBoard.prototype.getVectorData = function () {
        return JSON.stringify(this.steps);
    };

    DrawingBoard.prototype.getImageData = function () {
        return this.canvas.toDataURL('image/jpeg', 0.8);
    };

    DrawingBoard.prototype.loadVectorData = function (json) {
        try {
            this.steps = JSON.parse(json);
            this.redraw();
        } catch (e) {
            console.error("Error loading vector data", e);
        }
    };

    return DrawingBoard;
});
