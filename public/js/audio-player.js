(function() {
    'use strict';

    // Does the browser actually support the audio element?
    var supportsAudio = !!document.createElement('audio').canPlayType;

    if (supportsAudio) {
        // Obtain handles to main elements
        var audioContainer = document.getElementById('audio-container');
        var audio = document.getElementById('audio-file');
        var audioControls = document.getElementById('audio-controls');

        // Hide the default controls
        audio.controls = false;

        // Display the user defined audio controls
        audioControls.setAttribute('data-state', 'visible');

        // Obtain handles to buttons and other elements
        var playpause = document.getElementById('playpause');
        // var stop = document.getElementById('stop');
        var mute = document.getElementById('mute');
        // var volinc = document.getElementById('volinc');
        // var voldec = document.getElementById('voldec');
        var progress = document.getElementById('progress');
        var progressBar = document.getElementById('progress-bar');

        // If the browser doesn't support the progress element, set its state for some different styling
        var supportsProgress = (document.createElement('progress').max !== undefined);
        if (!supportsProgress) progress.setAttribute('data-state', 'fake');

        // Check the volume
        var checkVolume = function(dir) {
            if (dir) {
                var currentVolume = Math.floor(audio.volume * 10) / 10;
                if (dir === '+') {
                    if (currentVolume < 1) audio.volume += 0.1;
                } else if (dir === '-') {
                    if (currentVolume > 0) audio.volume -= 0.1;
                }
                // If the volume has been turned off, also set it as muted
                // Note: can only do this with the custom control set as when the 'volumechange' event is raised, there is no way to know if it was via a volume or a mute change
                if (currentVolume <= 0) audio.muted = true;
                else audio.muted = false;
            }
            changeButtonState('mute');
        }

        // Change the volume
        var alterVolume = function(dir) {
            checkVolume(dir);
        }

        // Only add the events if addEventListener is supported (IE8 and less don't support it, but that will use Flash anyway)
        if (document.addEventListener) {
            // Wait for the audio's meta data to be loaded, then set the progress bar's max value to the duration of the audio
            audio.addEventListener('loadedmetadata', function() {
                progress.setAttribute('max', audio.duration);
            });

            // Changes the button state of certain button's so the correct visuals can be displayed with CSS
            var changeButtonState = function(type) {
                // Play/Pause button
                if (type == 'playpause') {
                    if (audio.paused || audio.ended) {
                        playpause.setAttribute('data-state', 'play');
                    } else {
                        playpause.setAttribute('data-state', 'pause');
                    }
                }
                // Mute button
                else if (type == 'mute') {
                    mute.setAttribute('data-state', audio.muted ? 'unmute' : 'mute');
                }
            }

            // Add event listeners for audio specific events
            audio.addEventListener('play', function() {
                changeButtonState('playpause');
            }, false);
            audio.addEventListener('pause', function() {
                changeButtonState('playpause');
            }, false);
            audio.addEventListener('volumechange', function() {
                checkVolume();
            }, false);

            // Add events for all buttons			
            playpause.addEventListener('click', function(e) {
                if (audio.paused || audio.ended) audio.play();
                else audio.pause();
            });

            // The Media API has no 'stop()' function, so pause the audio and reset its time and the progress bar
            // stop.addEventListener('click', function(e) {
            //     audio.pause();
            //     audio.currentTime = 0;
            //     progress.value = 0;
            //     // Update the play/pause button's 'data-state' which allows the correct button image to be set via CSS
            //     changeButtonState('playpause');
            // });
            mute.addEventListener('click', function(e) {
                audio.muted = !audio.muted;
                changeButtonState('mute');
            });
            // volinc.addEventListener('click', function(e) {
            //     alterVolume('+');
            // });
            // voldec.addEventListener('click', function(e) {
            //     alterVolume('-');
            // });

            // As the audio is playing, update the progress bar
            audio.addEventListener('timeupdate', function() {
                // For mobile browsers, ensure that the progress element's max attribute is set
                if (!progress.getAttribute('max')) progress.setAttribute('max', audio.duration);
                progress.value = audio.currentTime;
                progressBar.style.width = Math.floor((audio.currentTime / audio.duration) * 100) + '%';
            });

            // React to the user clicking within the progress bar
            progress.addEventListener('click', function(e) {
                //var pos = (e.pageX  - this.offsetLeft) / this.offsetWidth; // Also need to take the parent into account here as .controls now has position:relative
                var pos = (e.pageX - (this.offsetLeft + this.offsetParent.offsetLeft)) / this.offsetWidth;
                audio.currentTime = pos * audio.duration;
            });
        }
    }

})();