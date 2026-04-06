(function () {
    if (window.__gsItemImageZoomInitialized) {
        return;
    }
    window.__gsItemImageZoomInitialized = true;

    var supportsHover = window.matchMedia && (
        window.matchMedia('(any-hover: hover)').matches ||
        window.matchMedia('(hover: hover)').matches
    );

    if (!supportsHover) {
        return;
    }

    function clamp(value, min, max) {
        return Math.min(max, Math.max(min, value));
    }

    function getZoomMetrics(wrap, img) {
        var containerWidth = wrap.clientWidth;
        var containerHeight = wrap.clientHeight;
        var naturalWidth = img.naturalWidth;
        var naturalHeight = img.naturalHeight;

        if (containerWidth <= 0 || containerHeight <= 0 || naturalWidth <= 0 || naturalHeight <= 0) {
            return null;
        }

        var fitScale = Math.min(containerWidth / naturalWidth, containerHeight / naturalHeight);

        var renderWidth = naturalWidth * fitScale;
        var renderHeight = naturalHeight * fitScale;
        var offsetX = (containerWidth - renderWidth) / 2;
        var offsetY = (containerHeight - renderHeight) / 2;

        if (fitScale >= 1) {
            return {
                containerWidth: containerWidth,
                containerHeight: containerHeight,
                renderWidth: containerWidth,
                renderHeight: containerHeight,
                offsetX: 0,
                offsetY: 0,
                zoomScale: 1.6
            };
        }

        return {
            containerWidth: containerWidth,
            containerHeight: containerHeight,
            renderWidth: renderWidth,
            renderHeight: renderHeight,
            offsetX: offsetX,
            offsetY: offsetY,
            zoomScale: Math.min(3, 1 / fitScale)
        };
    }

    function applyPointerOrigin(wrap, metrics, event) {
        var rect = wrap.getBoundingClientRect();
        var x = event.clientX - rect.left;
        var y = event.clientY - rect.top;

        var localX = clamp((x - metrics.offsetX) / metrics.renderWidth, 0, 1);
        var localY = clamp((y - metrics.offsetY) / metrics.renderHeight, 0, 1);

        var originX = ((metrics.offsetX + (localX * metrics.renderWidth)) / metrics.containerWidth) * 100;
        var originY = ((metrics.offsetY + (localY * metrics.renderHeight)) / metrics.containerHeight) * 100;

        wrap.style.setProperty('--item-image-origin-x', originX.toFixed(2) + '%');
        wrap.style.setProperty('--item-image-origin-y', originY.toFixed(2) + '%');
    }

    function resetWrap(wrap) {
        wrap.classList.remove('is-zoomable');
        wrap.classList.remove('is-zoom-active');
        wrap.style.removeProperty('--item-image-zoom-scale');
        wrap.style.removeProperty('--item-image-origin-x');
        wrap.style.removeProperty('--item-image-origin-y');
    }

    function refreshWrapState(wrap, img) {
        var metrics = getZoomMetrics(wrap, img);

        if (!metrics) {
            resetWrap(wrap);
            return null;
        }

        wrap.classList.add('is-zoomable');
        wrap.style.setProperty('--item-image-zoom-scale', metrics.zoomScale.toFixed(3));
        return metrics;
    }

    function bindZoom(wrap) {
        if (!wrap || wrap.dataset.zoomBound === '1') {
            return;
        }

        var img = wrap.querySelector('.item-preview-image');

        if (!img) {
            return;
        }

        wrap.dataset.zoomBound = '1';

        function onEnter(event) {
            if (!img.currentSrc && !img.src) {
                return;
            }

            var metrics = refreshWrapState(wrap, img);

            if (!metrics) {
                return;
            }

            wrap.classList.add('is-zoom-active');
            applyPointerOrigin(wrap, metrics, event);
        }

        function onMove(event) {
            if (!wrap.classList.contains('is-zoom-active')) {
                return;
            }

            var metrics = refreshWrapState(wrap, img);

            if (!metrics) {
                return;
            }

            applyPointerOrigin(wrap, metrics, event);
        }

        function onLeave() {
            wrap.classList.remove('is-zoom-active');
            wrap.style.removeProperty('--item-image-origin-x');
            wrap.style.removeProperty('--item-image-origin-y');
        }

        function onImageLoad() {
            refreshWrapState(wrap, img);
        }

        wrap.addEventListener('mouseenter', onEnter);
        wrap.addEventListener('mousemove', onMove);
        wrap.addEventListener('mouseleave', onLeave);
        img.addEventListener('load', onImageLoad);
    }

    function initAll() {
        var wraps = document.querySelectorAll('.item-preview-image-wrap');
        wraps.forEach(bindZoom);
    }

    window.gsInitItemImageZoom = initAll;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
