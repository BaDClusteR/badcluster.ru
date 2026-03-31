/**
 * @typedef {Object} Track
 * @property {HTMLElement} container
 * @property {HTMLAudioElement} audio
 * @property {HTMLButtonElement} playPauseBtn
 * @property {HTMLInputElement} seekSlider
 * @property {HTMLInputElement} volumeSlider
 * @property {HTMLElement} currentTimeEl
 * @property {HTMLElement} durationEl
 * @property {SVGElement} iconPlay
 * @property {SVGElement} iconPause
 */
class AudioPlayer {
    /**
     * @type Track[]
     */
    #tracks;

    /**
     * @type HTMLElement
     */
    #root;

    /**
     * @param {HTMLElement} [root] Default: document.documentElement
     */
    constructor(root = document.documentElement) {
        this.#initDOM(root);
    }

    #initDOM(root) {
        this.#root = root;
        this.#initTracks();
    }

    #initTracks() {
        this.#tracks = [];

        this.#root.querySelectorAll('.release__track').forEach(
            track => {
                this.#tracks.push(
                    this.#initTrack(track)
                );
            }
        );
    }

    /**
     * @param {HTMLElement} container
     *
     * @return Track
     */
    #initTrack(container) {
        /**
         * @type Track
         */
        const track = {
            container,
            audio: container.querySelector('audio'),
            playPauseBtn: container.querySelector('.release__track-play'),
            seekSlider: container.querySelector('.release__track-progress'),
            volumeSlider: container.querySelector('.release__track-control-volume-slider'),
            currentTimeEl: container.querySelector('.release__track-time-current'),
            durationEl: container.querySelector('.release__track-time-total'),
            iconPlay: container.querySelector('.release__track-play-icon--play'),
            iconPause: container.querySelector('.release__track-play-icon--pause'),
        }

        const storedVolume = this.#getStoredVolume();
        if (storedVolume !== null) {
            this.#setVolume(track, storedVolume);
        }

        this.#setSliderPercentage(track.seekSlider);
        this.#setSliderPercentage(track.volumeSlider);

        this.#initTrackListeners(track);

        return track;
    }

    /**
     * @param {Track} track
     */
    #initTrackListeners(track) {
        const audio = track.audio;

        audio.addEventListener('loadedmetadata', () => {
            const duration = audio.duration;
            track.durationEl.textContent = this.#formatTime(duration);
            track.seekSlider.max = String(Math.floor(duration));
        });

        track.playPauseBtn.addEventListener('click', () => {
            if (audio.paused) {
                this.#tracks.forEach(anotherTrack => {
                    const anotherAudio = anotherTrack.audio;

                    if (anotherAudio !== audio) {
                        this.#pauseTrack(anotherTrack);
                    }
                });

                this.#playTrack(track);
            } else {
                this.#pauseTrack(track);
            }
        });

        audio.addEventListener('play', () => {
            track.iconPlay.style.display = 'none';
            track.iconPause.style.display = 'block';
        });

        audio.addEventListener('pause', () => {
            track.iconPlay.style.display = 'block';
            track.iconPause.style.display = 'none';
        });

        audio.addEventListener('ended', () => {
            this.#tracks.forEach(
                (track, i) => {
                    if (
                        track.audio === audio
                        && this.#tracks[i + 1]
                    ) {
                        this.#stopTrack(track);
                        this.#tracks[i + 1].audio.currentTime = 0;
                        this.#playTrack(this.#tracks[i + 1]);
                    }
                }
            );

            audio.currentTime = 0;
        });

        audio.addEventListener('timeupdate', () => {
            const currentTime = audio.currentTime;
            track.seekSlider.value = String(Math.floor(currentTime));
            this.#setSliderPercentage(track.seekSlider);
            track.currentTimeEl.textContent = this.#formatTime(currentTime);

            const percent = (currentTime / audio.duration) * 100;
            track.seekSlider.style.setProperty('--progress', `${percent}%`);
        });

        track.seekSlider.addEventListener('input', () => {
            audio.currentTime = Number(track.seekSlider.value);
            this.#setSliderPercentage(track.seekSlider);
        });

        track.volumeSlider.addEventListener('input', (e) => {
            const newVolume = Number(e.target.value || 0) / 100;
            this.#storeVolume(newVolume);

            this.#tracks.forEach(
                track => {
                    this.#setVolume(track, newVolume);
                }
            );
        });
    }

    /**
     * @param {Number} volume
     */
    #storeVolume(volume) {
        localStorage.setItem('volume', String(volume));
    }

    /**
     * @return {null|Number}
     */
    #getStoredVolume() {
        const volume = localStorage.getItem('volume');

        return (volume === null)
            ? null
            : Number(volume);
    }

    /**
     * @param {Track} track
     * @param {Number} volume 0 - 1
     */
    #setVolume(track, volume) {
        track.audio.volume = volume;
        track.volumeSlider.value = String(volume * 100);
        this.#setSliderPercentage(track.volumeSlider);
    }

    /**
     * @param {Track} track
     */
    #playTrack(track) {
        const containerClassList = track.container.classList;

        track.audio.play().then(
            () => {
                containerClassList.add('playing', 'active');
                containerClassList.remove('paused');
            }
        );
    }

    /**
     * @param {Track} track
     */
    #pauseTrack(track) {
        const containerClassList = track.container.classList;

        track.audio.pause();
        containerClassList.remove('playing');
        containerClassList.add('paused');
    }

    #stopTrack(track) {
        const audio = track.audio;

        audio.pause();
        audio.currentTime = 0;
        track.container.classList.remove('playing', 'paused');
    }

    /**
     * @param {HTMLInputElement|null|undefined} slider
     */
    #setSliderPercentage = (slider) => {
        if (slider) {
            const min = slider.min || 0;
            const max = slider.max || 100;
            const val = slider.value;
            const percentage = ((val - min) / (max - min)) * 100;

            slider.style.setProperty('--progress', `${percentage}%`);
        }
    };

    /**
     * @param {Number} seconds
     */
    #formatTime(seconds) {
        const formatNumber = (num) => `${Math.floor(num / 10)}${num % 10}`;

        const min = Math.floor(seconds / 60);
        const sec = Math.floor(seconds % 60);
        return `${formatNumber(min)}:${formatNumber(sec)}`;
    };

    static autoload() {
        document.querySelectorAll('.release__tracklist').forEach(
            tracklist => {
                tracklist.audioPlayer = new AudioPlayer(tracklist);
            }
        );
    }
}

AudioPlayer.autoload();

EventDispatcher.getInstance().listen(
    'tab:shown',
    ({tab}) => {
        tab.querySelectorAll('iframe[data-src]').forEach(
            (iframe) => {
                iframe.setAttribute('src', iframe.getAttribute('data-src'));
                iframe.removeAttribute('data-src');
            }
        );
    }
)
