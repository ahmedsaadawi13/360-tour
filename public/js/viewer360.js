/**
 * Viewer360 - dependency-free 360° panorama viewer
 *
 * Renders equirectangular panoramas with a WebGL fragment shader (true
 * spherical projection, no distortion at the poles) and falls back to a
 * simple 2D canvas when WebGL is unavailable.
 *
 * Angles are in degrees. yaw: -180..180 (0 = image center, positive = right),
 * pitch: -90..90 (positive = up).
 *
 *   const viewer = new Viewer360('container-id', {
 *       hfov: 75, autoRotate: false,
 *       onHotspotClick: (hotspot) => {},
 *       onPick: (yaw, pitch) => {},        // click on empty panorama (editor)
 *       onViewChange: (view) => {}
 *   });
 *   viewer.loadPanorama(url, yaw, pitch, hotspots).then(...);
 */
(function (global) {
    'use strict';

    var DEG = Math.PI / 180;
    var MIN_FOV = 30;
    var MAX_FOV = 110;

    var VERTEX_SHADER =
        'attribute vec2 aPos;' +
        'void main() { gl_Position = vec4(aPos, 0.0, 1.0); }';

    // For every pixel: build the view ray from the camera basis and look up
    // the equirectangular texture by longitude/latitude.
    var FRAGMENT_SHADER =
        '#ifdef GL_FRAGMENT_PRECISION_HIGH\n precision highp float;\n#else\n precision mediump float;\n#endif\n' +
        'uniform sampler2D uTex;' +
        'uniform vec2 uRes;' +
        'uniform vec3 uRight; uniform vec3 uUp; uniform vec3 uFwd;' +
        'uniform float uTanH; uniform float uTanV;' +
        'const float PI = 3.141592653589793;' +
        'void main() {' +
        '  vec2 ndc = (gl_FragCoord.xy / uRes) * 2.0 - 1.0;' +
        '  vec3 d = normalize(uFwd + ndc.x * uTanH * uRight + ndc.y * uTanV * uUp);' +
        '  float lon = atan(d.x, -d.z);' +
        '  float lat = asin(clamp(d.y, -1.0, 1.0));' +
        '  vec2 uv = vec2(fract(0.5 + lon / (2.0 * PI)), clamp(0.5 - lat / PI, 0.0, 1.0));' +
        '  gl_FragColor = texture2D(uTex, uv);' +
        '}';

    function clamp(v, min, max) {
        return Math.max(min, Math.min(max, v));
    }

    function wrapYaw(yaw) {
        yaw = ((yaw + 180) % 360 + 360) % 360 - 180;
        return yaw === -180 ? 180 : yaw;
    }

    function dirFromAngles(yaw, pitch) {
        var y = yaw * DEG, p = pitch * DEG;
        return [Math.sin(y) * Math.cos(p), Math.sin(p), -Math.cos(y) * Math.cos(p)];
    }

    function dot(a, b) {
        return a[0] * b[0] + a[1] * b[1] + a[2] * b[2];
    }

    function Viewer360(container, options) {
        this.container = typeof container === 'string' ? document.getElementById(container) : container;
        if (!this.container) {
            throw new Error('Viewer360: container not found');
        }

        options = options || {};
        this.options = {
            hfov: options.hfov || 75,
            autoRotate: !!options.autoRotate,
            autoRotateSpeed: options.autoRotateSpeed || 2, // degrees per second
            controls: options.controls !== false,
            onHotspotClick: options.onHotspotClick || null,
            onPick: options.onPick || null,
            onViewChange: options.onViewChange || null
        };

        this.yaw = 0;
        this.pitch = 0;
        this.hfov = clamp(this.options.hfov, MIN_FOV, MAX_FOV);
        this.hotspots = [];
        this.pointers = {};
        this.velocity = { yaw: 0, pitch: 0 };
        this.lastInteraction = 0;
        this.dirty = true;
        this.destroyed = false;

        this._build();
        this._bindEvents();
        this._loop = this._loop.bind(this);
        this._lastFrame = performance.now();
        requestAnimationFrame(this._loop);
    }

    Viewer360.prototype._build = function () {
        var c = this.container;
        c.classList.add('v360');
        c.innerHTML = '';
        c.setAttribute('tabindex', '0');
        c.setAttribute('role', 'application');
        c.setAttribute('aria-label', '360 degree panorama. Drag or use arrow keys to look around.');

        this.canvas = document.createElement('canvas');
        this.canvas.className = 'v360-canvas';
        c.appendChild(this.canvas);

        this.hotspotLayer = document.createElement('div');
        this.hotspotLayer.className = 'v360-hotspots';
        c.appendChild(this.hotspotLayer);

        this.loader = document.createElement('div');
        this.loader.className = 'v360-loader';
        this.loader.innerHTML = '<div class="v360-spinner"></div>';
        c.appendChild(this.loader);

        if (this.options.controls) {
            var bar = document.createElement('div');
            bar.className = 'v360-controls';
            var self = this;
            [
                ['zoom-in', '+', 'Zoom in', function () { self.setFov(self.hfov - 10); }],
                ['zoom-out', '−', 'Zoom out', function () { self.setFov(self.hfov + 10); }],
                ['rotate', '⟳', 'Toggle auto-rotate', function () { self.setAutoRotate(!self.options.autoRotate); }],
                ['fullscreen', '⛶', 'Fullscreen', function () { self.toggleFullscreen(); }]
            ].forEach(function (def) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'v360-btn v360-btn-' + def[0];
                btn.textContent = def[1];
                btn.title = def[2];
                btn.setAttribute('aria-label', def[2]);
                btn.addEventListener('click', function (e) { e.stopPropagation(); def[3](); });
                bar.appendChild(btn);
            });
            c.appendChild(bar);
        }

        this.gl = null;
        try {
            this.gl = this.canvas.getContext('webgl', { antialias: false, preserveDrawingBuffer: false }) ||
                this.canvas.getContext('experimental-webgl');
        } catch (e) {
            this.gl = null;
        }

        if (this.gl && !this._initGL()) {
            this.gl = null;
        }
        if (!this.gl) {
            // A canvas can only ever have one context type; replace it for 2D
            var canvas2d = document.createElement('canvas');
            canvas2d.className = 'v360-canvas';
            c.replaceChild(canvas2d, this.canvas);
            this.canvas = canvas2d;
            this.ctx2d = this.canvas.getContext('2d');
        }

        this.resize();
    };

    Viewer360.prototype._initGL = function () {
        var gl = this.gl;

        function compile(type, src) {
            var shader = gl.createShader(type);
            gl.shaderSource(shader, src);
            gl.compileShader(shader);
            if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
                console.warn('Viewer360 shader error:', gl.getShaderInfoLog(shader));
                return null;
            }
            return shader;
        }

        var vs = compile(gl.VERTEX_SHADER, VERTEX_SHADER);
        var fs = compile(gl.FRAGMENT_SHADER, FRAGMENT_SHADER);
        if (!vs || !fs) return false;

        var program = gl.createProgram();
        gl.attachShader(program, vs);
        gl.attachShader(program, fs);
        gl.linkProgram(program);
        if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
            return false;
        }
        gl.useProgram(program);

        // One triangle covering the whole viewport
        var buffer = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 3, -1, -1, 3]), gl.STATIC_DRAW);
        var loc = gl.getAttribLocation(program, 'aPos');
        gl.enableVertexAttribArray(loc);
        gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);

        this.uniforms = {};
        ['uTex', 'uRes', 'uRight', 'uUp', 'uFwd', 'uTanH', 'uTanV'].forEach(function (name) {
            this.uniforms[name] = gl.getUniformLocation(program, name);
        }, this);

        this.texture = gl.createTexture();
        gl.bindTexture(gl.TEXTURE_2D, this.texture);
        // NPOT-safe parameters
        gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
        gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
        gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
        gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
        gl.uniform1i(this.uniforms.uTex, 0);

        this.maxTextureSize = gl.getParameter(gl.MAX_TEXTURE_SIZE);
        return true;
    };

    Viewer360.prototype._bindEvents = function () {
        var self = this;
        var canvas = this.canvas;
        this.container.style.touchAction = 'none';

        this._onResize = function () { self.resize(); };
        window.addEventListener('resize', this._onResize);

        this.container.addEventListener('pointerdown', function (e) {
            if (e.target.closest('.v360-hotspot, .v360-controls')) return;
            self.container.setPointerCapture && self.container.setPointerCapture(e.pointerId);
            self.pointers[e.pointerId] = { x: e.clientX, y: e.clientY, startX: e.clientX, startY: e.clientY };
            self.velocity = { yaw: 0, pitch: 0 };
            self.lastInteraction = performance.now();
            self.container.classList.add('v360-dragging');
        });

        this.container.addEventListener('pointermove', function (e) {
            var p = self.pointers[e.pointerId];
            if (!p) return;

            var ids = Object.keys(self.pointers);
            if (ids.length === 2) {
                // Pinch zoom
                var a = self.pointers[ids[0]], b = self.pointers[ids[1]];
                var before = Math.hypot(a.x - b.x, a.y - b.y);
                p.x = e.clientX; p.y = e.clientY;
                var after = Math.hypot(a.x - b.x, a.y - b.y);
                if (before > 0) self.setFov(self.hfov * before / after);
                return;
            }

            var dx = e.clientX - p.x, dy = e.clientY - p.y;
            p.x = e.clientX; p.y = e.clientY;

            // Drag the panorama: degrees per pixel follows the field of view
            var scale = self.hfov / self.canvas.clientWidth;
            self.velocity = { yaw: -dx * scale, pitch: dy * scale };
            self.lookAt(self.yaw - dx * scale, self.pitch + dy * scale);
            self.lastInteraction = performance.now();
        });

        function end(e) {
            var p = self.pointers[e.pointerId];
            if (!p) return;
            delete self.pointers[e.pointerId];
            if (!Object.keys(self.pointers).length) {
                self.container.classList.remove('v360-dragging');
            }
            var moved = Math.hypot(e.clientX - p.startX, e.clientY - p.startY);
            if (moved < 5 && e.type === 'pointerup' && self.options.onPick) {
                var rect = self.canvas.getBoundingClientRect();
                var angles = self.screenToAngles(e.clientX - rect.left, e.clientY - rect.top);
                if (angles) self.options.onPick(angles.yaw, angles.pitch);
            }
        }
        this.container.addEventListener('pointerup', end);
        this.container.addEventListener('pointercancel', end);

        this.container.addEventListener('wheel', function (e) {
            e.preventDefault();
            self.setFov(self.hfov + (e.deltaY > 0 ? 5 : -5));
            self.lastInteraction = performance.now();
        }, { passive: false });

        this.container.addEventListener('keydown', function (e) {
            var step = 5, handled = true;
            switch (e.key) {
                case 'ArrowLeft': self.lookAt(self.yaw - step, self.pitch); break;
                case 'ArrowRight': self.lookAt(self.yaw + step, self.pitch); break;
                case 'ArrowUp': self.lookAt(self.yaw, self.pitch + step); break;
                case 'ArrowDown': self.lookAt(self.yaw, self.pitch - step); break;
                case '+': case '=': self.setFov(self.hfov - 5); break;
                case '-': case '_': self.setFov(self.hfov + 5); break;
                default: handled = false;
            }
            if (handled) {
                e.preventDefault();
                self.lastInteraction = performance.now();
            }
        });

        canvas.addEventListener('contextmenu', function (e) { e.preventDefault(); });
    };

    /**
     * Load a panorama; resolves when the image is displayed
     */
    Viewer360.prototype.loadPanorama = function (src, yaw, pitch, hotspots) {
        var self = this;
        this.loader.classList.add('active');
        this.container.classList.add('v360-loading');

        return new Promise(function (resolve, reject) {
            var img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = function () {
                if (self.destroyed) return;
                self._setImage(img);
                self.yaw = wrapYaw(yaw || 0);
                self.pitch = clamp(pitch || 0, -90, 90);
                self.setHotspots(hotspots || []);
                self.loader.classList.remove('active');
                self.container.classList.remove('v360-loading');
                self.dirty = true;
                resolve();
            };
            img.onerror = function () {
                self.loader.classList.remove('active');
                self.container.classList.remove('v360-loading');
                reject(new Error('Could not load panorama: ' + src));
            };
            img.src = src;
        });
    };

    Viewer360.prototype._setImage = function (img) {
        this.image = img;
        if (!this.gl) return;

        var source = img;
        var max = Math.min(this.maxTextureSize || 4096, 8192);
        if (img.width > max || img.height > max) {
            // Downscale panoramas larger than the GPU supports
            var ratio = Math.min(max / img.width, max / img.height);
            var c = document.createElement('canvas');
            c.width = Math.floor(img.width * ratio);
            c.height = Math.floor(img.height * ratio);
            c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
            source = c;
        }

        var gl = this.gl;
        gl.bindTexture(gl.TEXTURE_2D, this.texture);
        gl.pixelStorei(gl.UNPACK_FLIP_Y_WEBGL, false);
        gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGB, gl.RGB, gl.UNSIGNED_BYTE, source);
    };

    Viewer360.prototype.setHotspots = function (hotspots) {
        var self = this;
        this.hotspots = (hotspots || []).map(function (h) {
            return Object.assign({}, h, { yaw: parseFloat(h.yaw) || 0, pitch: parseFloat(h.pitch) || 0 });
        });
        this.hotspotLayer.innerHTML = '';

        this.hotspots.forEach(function (h) {
            var el = document.createElement('button');
            el.type = 'button';
            el.className = 'v360-hotspot v360-hotspot-' + (h.type || 'info') + (h.temporary ? ' v360-hotspot-temp' : '');
            el.setAttribute('aria-label', h.label || h.type);

            var icon = document.createElement('span');
            icon.className = 'v360-hotspot-icon';
            icon.textContent = h.type === 'navigation' ? '➜' : (h.type === 'link' ? '↗' : 'i');
            el.appendChild(icon);

            if (h.label) {
                var label = document.createElement('span');
                label.className = 'v360-hotspot-label';
                label.textContent = h.label;
                el.appendChild(label);
            }

            el.addEventListener('click', function (e) {
                e.stopPropagation();
                if (self.options.onHotspotClick) self.options.onHotspotClick(h);
            });

            h._el = el;
            self.hotspotLayer.appendChild(el);
        });

        this.dirty = true;
    };

    Viewer360.prototype._basis = function () {
        var y = this.yaw * DEG, p = this.pitch * DEG;
        var cy = Math.cos(y), sy = Math.sin(y), cp = Math.cos(p), sp = Math.sin(p);
        return {
            fwd: [sy * cp, sp, -cy * cp],
            right: [cy, 0, sy],
            up: [-sy * sp, cp, cy * sp]
        };
    };

    Viewer360.prototype._tans = function () {
        var w = this.canvas.clientWidth || 1, h = this.canvas.clientHeight || 1;
        var tanH = Math.tan(this.hfov * DEG / 2);
        return { h: tanH, v: tanH * h / w };
    };

    /**
     * Project yaw/pitch to canvas pixel coordinates (null when behind camera)
     */
    Viewer360.prototype.anglesToScreen = function (yaw, pitch) {
        var b = this._basis(), t = this._tans();
        var d = dirFromAngles(yaw, pitch);
        var z = dot(d, b.fwd);
        if (z <= 0.01) return null;
        var x = dot(d, b.right) / z / t.h;
        var y = dot(d, b.up) / z / t.v;
        if (Math.abs(x) > 1.2 || Math.abs(y) > 1.2) return null;
        return {
            x: (x + 1) / 2 * this.canvas.clientWidth,
            y: (1 - y) / 2 * this.canvas.clientHeight
        };
    };

    /**
     * Inverse projection: canvas pixel to yaw/pitch
     */
    Viewer360.prototype.screenToAngles = function (px, py) {
        var w = this.canvas.clientWidth, h = this.canvas.clientHeight;
        if (!w || !h) return null;
        var b = this._basis(), t = this._tans();
        var nx = (px / w) * 2 - 1, ny = 1 - (py / h) * 2;
        var d = [0, 1, 2].map(function (i) {
            return b.fwd[i] + nx * t.h * b.right[i] + ny * t.v * b.up[i];
        });
        var len = Math.hypot(d[0], d[1], d[2]);
        d = d.map(function (v) { return v / len; });
        return {
            yaw: Math.round(wrapYaw(Math.atan2(d[0], -d[2]) / DEG) * 100) / 100,
            pitch: Math.round(Math.asin(clamp(d[1], -1, 1)) / DEG * 100) / 100
        };
    };

    Viewer360.prototype.lookAt = function (yaw, pitch, hfov) {
        this.yaw = wrapYaw(yaw);
        this.pitch = clamp(pitch, -89.9, 89.9);
        if (hfov) this.hfov = clamp(hfov, MIN_FOV, MAX_FOV);
        this.dirty = true;
    };

    Viewer360.prototype.setFov = function (hfov) {
        this.hfov = clamp(hfov, MIN_FOV, MAX_FOV);
        this.dirty = true;
    };

    Viewer360.prototype.getView = function () {
        return {
            yaw: Math.round(this.yaw * 100) / 100,
            pitch: Math.round(this.pitch * 100) / 100,
            hfov: Math.round(this.hfov)
        };
    };

    Viewer360.prototype.setAutoRotate = function (on) {
        this.options.autoRotate = !!on;
        this.container.classList.toggle('v360-rotating', this.options.autoRotate);
        this.lastInteraction = 0;
    };

    Viewer360.prototype.toggleFullscreen = function () {
        var c = this.container;
        if (document.fullscreenElement || document.webkitFullscreenElement) {
            (document.exitFullscreen || document.webkitExitFullscreen).call(document);
        } else if (c.requestFullscreen) {
            c.requestFullscreen();
        } else if (c.webkitRequestFullscreen) {
            c.webkitRequestFullscreen();
        }
    };

    Viewer360.prototype.resize = function () {
        var dpr = Math.min(window.devicePixelRatio || 1, 2);
        var w = this.container.clientWidth, h = this.container.clientHeight;
        this.canvas.width = Math.max(1, Math.floor(w * dpr));
        this.canvas.height = Math.max(1, Math.floor(h * dpr));
        this.canvas.style.width = w + 'px';
        this.canvas.style.height = h + 'px';
        this.dirty = true;
    };

    Viewer360.prototype._loop = function (now) {
        if (this.destroyed) return;
        var dt = Math.min((now - this._lastFrame) / 1000, 0.1);
        this._lastFrame = now;

        var dragging = Object.keys(this.pointers).length > 0;
        if (!dragging) {
            // Inertia after a drag
            if (Math.abs(this.velocity.yaw) > 0.01 || Math.abs(this.velocity.pitch) > 0.01) {
                this.lookAt(this.yaw + this.velocity.yaw, this.pitch + this.velocity.pitch);
                this.velocity.yaw *= 0.9;
                this.velocity.pitch *= 0.9;
            } else if (this.options.autoRotate && now - this.lastInteraction > 3000) {
                this.lookAt(this.yaw + this.options.autoRotateSpeed * dt, this.pitch);
            }
        }

        if (this.dirty && this.image) {
            this.dirty = false;
            this._render();
            if (this.options.onViewChange) this.options.onViewChange(this.getView());
        }

        requestAnimationFrame(this._loop);
    };

    Viewer360.prototype._render = function () {
        if (this.gl) {
            var gl = this.gl, u = this.uniforms, b = this._basis(), t = this._tans();
            gl.viewport(0, 0, this.canvas.width, this.canvas.height);
            gl.uniform2f(u.uRes, this.canvas.width, this.canvas.height);
            gl.uniform3fv(u.uRight, b.right);
            gl.uniform3fv(u.uUp, b.up);
            gl.uniform3fv(u.uFwd, b.fwd);
            gl.uniform1f(u.uTanH, t.h);
            gl.uniform1f(u.uTanV, t.v);
            gl.drawArrays(gl.TRIANGLES, 0, 3);
        } else {
            this._render2d();
        }
        this._positionHotspots();
    };

    /**
     * 2D fallback: crop of the equirectangular image (approximate projection)
     */
    Viewer360.prototype._render2d = function () {
        var ctx = this.ctx2d, img = this.image, cw = this.canvas.width, ch = this.canvas.height;
        var srcW = img.width * this.hfov / 360;
        var srcH = Math.min(img.height, srcW * ch / cw);
        var cx = (this.yaw / 360 + 0.5) * img.width;
        var cy = (0.5 - this.pitch / 180) * img.height;
        var sx = cx - srcW / 2;
        var sy = clamp(cy - srcH / 2, 0, img.height - srcH);

        sx = ((sx % img.width) + img.width) % img.width;
        var firstW = Math.min(srcW, img.width - sx);
        var destFirst = cw * firstW / srcW;
        ctx.drawImage(img, sx, sy, firstW, srcH, 0, 0, destFirst, ch);
        if (firstW < srcW) {
            ctx.drawImage(img, 0, sy, srcW - firstW, srcH, destFirst, 0, cw - destFirst, ch);
        }
    };

    Viewer360.prototype._positionHotspots = function () {
        for (var i = 0; i < this.hotspots.length; i++) {
            var h = this.hotspots[i];
            var pos = this.anglesToScreen(h.yaw, h.pitch);
            if (!pos) {
                h._el.style.display = 'none';
                continue;
            }
            h._el.style.display = '';
            h._el.style.transform = 'translate(' + pos.x.toFixed(1) + 'px,' + pos.y.toFixed(1) + 'px)';
        }
    };

    Viewer360.prototype.destroy = function () {
        this.destroyed = true;
        window.removeEventListener('resize', this._onResize);
        this.container.innerHTML = '';
    };

    Viewer360.wrapYaw = wrapYaw;
    global.Viewer360 = Viewer360;
})(window);
