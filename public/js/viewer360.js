/**
 * Simple 360° Panorama Viewer
 *
 * Vanilla JavaScript implementation for viewing 360° panoramic images
 * with hotspot navigation
 */

class Viewer360 {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error('Container not found:', containerId);
            return;
        }

        this.options = {
            initialYaw: options.initialYaw || 0,
            initialPitch: options.initialPitch || 0,
            autoRotate: options.autoRotate || false,
            autoRotateSpeed: options.autoRotateSpeed || 0.5,
            hotspots: options.hotspots || [],
            onHotspotClick: options.onHotspotClick || null
        };

        this.yaw = this.options.initialYaw;
        this.pitch = this.options.initialPitch;
        this.isDragging = false;
        this.lastX = 0;
        this.lastY = 0;
        this.zoom = 75;

        this.init();
    }

    init() {
        // Create canvas
        this.canvas = document.createElement('canvas');
        this.canvas.className = 'viewer-canvas';
        this.container.innerHTML = '';
        this.container.appendChild(this.canvas);

        this.ctx = this.canvas.getContext('2d');
        this.resize();

        // Event listeners
        window.addEventListener('resize', () => this.resize());

        // Mouse events
        this.canvas.addEventListener('mousedown', (e) => this.onMouseDown(e));
        this.canvas.addEventListener('mousemove', (e) => this.onMouseMove(e));
        this.canvas.addEventListener('mouseup', () => this.onMouseUp());
        this.canvas.addEventListener('mouseleave', () => this.onMouseUp());

        // Touch events
        this.canvas.addEventListener('touchstart', (e) => this.onTouchStart(e));
        this.canvas.addEventListener('touchmove', (e) => this.onTouchMove(e));
        this.canvas.addEventListener('touchend', () => this.onMouseUp());

        // Wheel for zoom
        this.canvas.addEventListener('wheel', (e) => this.onWheel(e));

        // Hotspot clicks
        this.canvas.addEventListener('click', (e) => this.onCanvasClick(e));

        this.render();
    }

    loadPanorama(imageSrc, yaw = 0, pitch = 0, hotspots = []) {
        this.image = new Image();
        this.image.crossOrigin = 'anonymous';
        this.image.onload = () => {
            this.yaw = yaw;
            this.pitch = pitch;
            this.options.hotspots = hotspots;
            this.render();
        };
        this.image.src = imageSrc;
    }

    resize() {
        this.canvas.width = this.container.clientWidth;
        this.canvas.height = this.container.clientHeight;
        this.render();
    }

    onMouseDown(e) {
        this.isDragging = true;
        this.lastX = e.clientX;
        this.lastY = e.clientY;
    }

    onMouseMove(e) {
        if (!this.isDragging) return;

        const deltaX = e.clientX - this.lastX;
        const deltaY = e.clientY - this.lastY;

        this.yaw += deltaX * 0.1;
        this.pitch -= deltaY * 0.1;
        this.pitch = Math.max(-90, Math.min(90, this.pitch));

        this.lastX = e.clientX;
        this.lastY = e.clientY;

        this.render();
    }

    onMouseUp() {
        this.isDragging = false;
    }

    onTouchStart(e) {
        if (e.touches.length === 1) {
            this.isDragging = true;
            this.lastX = e.touches[0].clientX;
            this.lastY = e.touches[0].clientY;
        }
    }

    onTouchMove(e) {
        if (!this.isDragging || e.touches.length !== 1) return;

        e.preventDefault();
        const deltaX = e.touches[0].clientX - this.lastX;
        const deltaY = e.touches[0].clientY - this.lastY;

        this.yaw += deltaX * 0.1;
        this.pitch -= deltaY * 0.1;
        this.pitch = Math.max(-90, Math.min(90, this.pitch));

        this.lastX = e.touches[0].clientX;
        this.lastY = e.touches[0].clientY;

        this.render();
    }

    onWheel(e) {
        e.preventDefault();
        this.zoom -= e.deltaY * 0.05;
        this.zoom = Math.max(30, Math.min(120, this.zoom));
        this.render();
    }

    onCanvasClick(e) {
        const rect = this.canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        // Check if clicked on a hotspot
        for (const hotspot of this.options.hotspots) {
            const screenPos = this.worldToScreen(hotspot.yaw, hotspot.pitch);
            if (screenPos) {
                const distance = Math.sqrt(
                    Math.pow(x - screenPos.x, 2) + Math.pow(y - screenPos.y, 2)
                );

                if (distance < 20) {
                    if (this.options.onHotspotClick) {
                        this.options.onHotspotClick(hotspot);
                    }
                    return;
                }
            }
        }
    }

    worldToScreen(hotspotYaw, hotspotPitch) {
        const fov = this.zoom;
        const aspect = this.canvas.width / this.canvas.height;

        const yawDiff = hotspotYaw - this.yaw;
        const pitchDiff = hotspotPitch - this.pitch;

        // Simple projection (not perfect but works for demo)
        const x = this.canvas.width / 2 + (yawDiff * this.canvas.width / fov);
        const y = this.canvas.height / 2 - (pitchDiff * this.canvas.height / (fov / aspect));

        // Check if in view
        if (Math.abs(yawDiff) > fov / 2 || Math.abs(pitchDiff) > fov / 2 / aspect) {
            return null;
        }

        return { x, y };
    }

    render() {
        if (!this.image || !this.image.complete) return;

        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        // Simple equirectangular projection
        const fov = this.zoom;
        const yawRad = (this.yaw * Math.PI) / 180;
        const pitchRad = (this.pitch * Math.PI) / 180;

        // Calculate source region
        const imgWidth = this.image.width;
        const imgHeight = this.image.height;

        const centerX = (this.yaw / 360) * imgWidth;
        const centerY = ((this.pitch + 90) / 180) * imgHeight;

        const srcWidth = (fov / 360) * imgWidth;
        const srcHeight = srcWidth * (this.canvas.height / this.canvas.width);

        let sx = centerX - srcWidth / 2;
        const sy = Math.max(0, Math.min(imgHeight - srcHeight, centerY - srcHeight / 2));

        // Handle wrapping
        if (sx < 0) {
            sx += imgWidth;
        }
        if (sx + srcWidth > imgWidth) {
            sx = sx % imgWidth;
        }

        this.ctx.drawImage(
            this.image,
            sx, sy, srcWidth, srcHeight,
            0, 0, this.canvas.width, this.canvas.height
        );

        // Draw hotspots
        this.drawHotspots();
    }

    drawHotspots() {
        for (const hotspot of this.options.hotspots) {
            const screenPos = this.worldToScreen(parseFloat(hotspot.yaw), parseFloat(hotspot.pitch));
            if (!screenPos) continue;

            // Draw hotspot icon
            this.ctx.save();
            this.ctx.translate(screenPos.x, screenPos.y);

            // Hotspot background
            this.ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
            this.ctx.beginPath();
            this.ctx.arc(0, 0, 15, 0, Math.PI * 2);
            this.ctx.fill();

            // Hotspot border
            this.ctx.strokeStyle = '#3498db';
            this.ctx.lineWidth = 2;
            this.ctx.beginPath();
            this.ctx.arc(0, 0, 15, 0, Math.PI * 2);
            this.ctx.stroke();

            // Draw icon based on type
            this.ctx.fillStyle = '#3498db';
            this.ctx.font = 'bold 14px Arial';
            this.ctx.textAlign = 'center';
            this.ctx.textBaseline = 'middle';

            if (hotspot.type === 'navigation') {
                this.ctx.fillText('→', 0, 0);
            } else if (hotspot.type === 'info') {
                this.ctx.fillText('i', 0, 0);
            } else if (hotspot.type === 'link') {
                this.ctx.fillText('🔗', 0, 0);
            }

            // Draw label
            if (hotspot.label) {
                this.ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
                this.ctx.fillRect(-40, 20, 80, 25);

                this.ctx.fillStyle = '#ffffff';
                this.ctx.font = '12px Arial';
                this.ctx.fillText(hotspot.label, 0, 32);
            }

            this.ctx.restore();
        }
    }
}
