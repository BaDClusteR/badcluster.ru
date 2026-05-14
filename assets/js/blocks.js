import Lightbox from '../lib/lightbox.min.js';

Gallery.autoload();
window.lightbox = new Lightbox();

(function() {
    /**
     * @param {HTMLVideoElement} video
     */
    const initiateLoad = (video) => {
        video.querySelectorAll('[data-src]').forEach(
            (source) => {
                source.src = source.getAttribute('data-src');
                source.removeAttribute('data-src')
            }
        )

        video.load();
    }

    document.addEventListener("DOMContentLoaded", function() {
        const lazyVideos = [].slice.call(document.querySelectorAll(".figure--video-lazy video"));

        if ("IntersectionObserver" in window) {
            const lazyVideoObserver = new IntersectionObserver(
                (entries) => {
                    entries.forEach(
                        (video) => {
                            if (video.isIntersecting) {
                                initiateLoad(video.target);
                                lazyVideoObserver.unobserve(video.target);
                            }
                        }
                    );
                }
            );

            lazyVideos.forEach(
                (lazyVideo) => {
                    lazyVideoObserver.observe(lazyVideo);
                }
            );
        } else {
            lazyVideos.forEach(
                (video) => {
                    initiateLoad(video);
                }
            )
        }
    });
})();
