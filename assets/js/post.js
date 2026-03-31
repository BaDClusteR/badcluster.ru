import Lightbox from './lightbox.js';

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

// (function(root) {
//     root.querySelectorAll('[data-switcher-switch]').forEach(
//         (switcher) => {
//             switcher.addEventListener(
//                 'click',
//                 () => {
//                     const tabToSwitch = switcher.getAttribute('data-switcher-switch');
//                     const localRoot = switcher.closest('[data-switcher]');
//
//                     if (localRoot) {
//                         localRoot.querySelectorAll('[data-switcher-tab]').forEach(
//                             (tab) => {
//                                 const isTabActive = tab.getAttribute('data-switcher-tab') === tabToSwitch;
//
//                                 if (isTabActive) {
//                                     tab.removeAttribute('hidden');
//                                 } else {
//                                     tab.setAttribute('hidden', '');
//                                 }
//
//                                 tab.classList.toggle('active', isTabActive);
//                             }
//                         );
//
//                         localRoot.querySelectorAll('[data-switcher-switch]').forEach(
//                             (_switch) => {
//                                 const isButtonActive = _switch.getAttribute('data-switcher-switch') === tabToSwitch;
//
//                                 _switch.classList.toggle('active', isButtonActive);
//                                 _switch.setAttribute('aria-selected', isButtonActive ? 'true' : 'false');
//                             }
//                         )
//                     }
//                 }
//             );
//         }
//     )
// })(document);
