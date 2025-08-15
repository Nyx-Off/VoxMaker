// Mode client-side avec Web Speech API
class ClientTTS {
    constructor() {
        this.synth = window.speechSynthesis;
        this.voices = [];
        this.audioContext = null;
        this.loadVoices();
    }

    loadVoices() {
        // Charger les voix disponibles
        const loadVoiceList = () => {
            this.voices = this.synth.getVoices();
            this.updateVoiceList();
        };

        loadVoiceList();
        
        // Certains navigateurs chargent les voix de manière asynchrone
        if (this.synth.onvoiceschanged !== undefined) {
            this.synth.onvoiceschanged = loadVoiceList;
        }
    }

    updateVoiceList() {
        const modeInputs = document.querySelectorAll('input[name="mode"]');
        const currentMode = Array.from(modeInputs).find(input => input.checked)?.value;
        
        if (currentMode !== 'client') return;

        const voiceSelect = document.getElementById('voiceSelect');
        voiceSelect.innerHTML = '';
        
        // Filtrer les voix françaises
        const frenchVoices = this.voices.filter(voice => 
            voice.lang.startsWith('fr') || voice.lang === 'fr-FR'
        );
        
        if (frenchVoices.length === 0) {
            const option = document.createElement('option');
            option.value = 'default';
            option.textContent = 'Voix par défaut (français)';
            voiceSelect.appendChild(option);
        } else {
            frenchVoices.forEach(voice => {
                const option = document.createElement('option');
                option.value = voice.name;
                option.textContent = `${voice.name} (${voice.lang})`;
                voiceSelect.appendChild(option);
            });
        }
    }

    async generateSpeech(text, voiceName, rate = 0.95, pitch = 1) {
        return new Promise((resolve, reject) => {
            try {
                // Créer un contexte audio
                if (!this.audioContext) {
                    this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                }

                // Créer la synthèse vocale
                const utterance = new SpeechSynthesisUtterance(text);
                
                // Configurer la voix
                if (voiceName && voiceName !== 'default') {
                    const selectedVoice = this.voices.find(voice => voice.name === voiceName);
                    if (selectedVoice) {
                        utterance.voice = selectedVoice;
                    }
                }
                
                utterance.lang = 'fr-FR';
                utterance.rate = rate;
                utterance.pitch = pitch;
                
                // Créer un enregistreur
                const destination = this.audioContext.createMediaStreamDestination();
                const mediaRecorder = new MediaRecorder(destination.stream, {
                    mimeType: 'audio/webm'
                });
                
                const chunks = [];
                
                mediaRecorder.ondataavailable = (e) => {
                    if (e.data.size > 0) {
                        chunks.push(e.data);
                    }
                };
                
                mediaRecorder.onstop = () => {
                    const blob = new Blob(chunks, { type: 'audio/webm' });
                    resolve(blob);
                };
                
                // Démarrer l'enregistrement avant la synthèse
                mediaRecorder.start();
                
                utterance.onend = () => {
                    setTimeout(() => {
                        mediaRecorder.stop();
                    }, 500); // Petit délai pour capturer la fin
                };
                
                utterance.onerror = (error) => {
                    mediaRecorder.stop();
                    reject(error);
                };
                
                // Lancer la synthèse
                this.synth.speak(utterance);
                
            } catch (error) {
                reject(error);
            }
        });
    }

    async mixWithMusic(voiceBlob, musicUrl, musicVolume = 30) {
        try {
            if (!this.audioContext) {
                this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            }

            // Si pas de musique, retourner juste la voix
            if (musicUrl === 'none') {
                return voiceBlob;
            }

            // Charger la musique
            const musicResponse = await fetch(musicUrl);
            const musicArrayBuffer = await musicResponse.arrayBuffer();
            const musicBuffer = await this.audioContext.decodeAudioData(musicArrayBuffer);

            // Convertir la voix en buffer audio
            const voiceArrayBuffer = await voiceBlob.arrayBuffer();
            const voiceBuffer = await this.audioContext.decodeAudioData(voiceArrayBuffer);

            // Créer un buffer pour le mixage
            const duration = voiceBuffer.duration;
            const sampleRate = this.audioContext.sampleRate;
            const length = Math.ceil(duration * sampleRate);
            
            const mixedBuffer = this.audioContext.createBuffer(
                2, // Stéréo
                length,
                sampleRate
            );

            // Mixer les canaux
            for (let channel = 0; channel < 2; channel++) {
                const mixedData = mixedBuffer.getChannelData(channel);
                const voiceData = voiceBuffer.getChannelData(Math.min(channel, voiceBuffer.numberOfChannels - 1));
                const musicData = musicBuffer.getChannelData(Math.min(channel, musicBuffer.numberOfChannels - 1));
                
                const musicGain = musicVolume / 100;
                
                for (let i = 0; i < length; i++) {
                    let voiceSample = 0;
                    let musicSample = 0;
                    
                    if (i < voiceData.length) {
                        voiceSample = voiceData[i];
                    }
                    
                    if (i < musicData.length) {
                        musicSample = musicData[i] * musicGain;
                    } else {
                        // Boucler la musique si elle est plus courte
                        const loopIndex = i % musicData.length;
                        musicSample = musicData[loopIndex] * musicGain;
                    }
                    
                    mixedData[i] = voiceSample + musicSample;
                }
            }

            // Convertir le buffer en blob
            const audioBlob = await this.bufferToWave(mixedBuffer);
            return audioBlob;

        } catch (error) {
            console.error('Erreur lors du mixage:', error);
            // En cas d'erreur, retourner juste la voix
            return voiceBlob;
        }
    }

    async bufferToWave(buffer) {
        const length = buffer.length * buffer.numberOfChannels * 2 + 44;
        const arrayBuffer = new ArrayBuffer(length);
        const view = new DataView(arrayBuffer);
        const channels = [];
        let offset = 0;
        let pos = 0;

        // Écrire l'en-tête WAVE
        const setUint16 = (data) => {
            view.setUint16(pos, data, true);
            pos += 2;
        };

        const setUint32 = (data) => {
            view.setUint32(pos, data, true);
            pos += 4;
        };

        // RIFF identifier
        setUint32(0x46464952); // "RIFF"
        setUint32(length - 8); // file length - 8
        setUint32(0x45564157); // "WAVE"

        // fmt sub-chunk
        setUint32(0x20746D66); // "fmt "
        setUint32(16); // subchunk size
        setUint16(1); // PCM
        setUint16(buffer.numberOfChannels);
        setUint32(buffer.sampleRate);
        setUint32(buffer.sampleRate * 2 * buffer.numberOfChannels); // byte rate
        setUint16(buffer.numberOfChannels * 2); // block align
        setUint16(16); // bits per sample

        // data sub-chunk
        setUint32(0x61746164); // "data"
        setUint32(length - pos - 4);

        // Convertir les échantillons float32 en int16
        const volume = 0.8;
        for (let i = 0; i < buffer.numberOfChannels; i++) {
            channels.push(buffer.getChannelData(i));
        }

        while (pos < length) {
            for (let i = 0; i < buffer.numberOfChannels; i++) {
                let sample = Math.max(-1, Math.min(1, channels[i][offset]));
                sample = sample < 0 ? sample * 0x8000 : sample * 0x7FFF;
                view.setInt16(pos, sample * volume, true);
                pos += 2;
            }
            offset++;
        }

        return new Blob([arrayBuffer], { type: 'audio/wav' });
    }
}

// Initialiser le TTS client quand le DOM est prêt
document.addEventListener('DOMContentLoaded', function() {
    const clientTTS = new ClientTTS();
    
    // Gérer le changement de mode
    const modeInputs = document.querySelectorAll('input[name="mode"]');
    modeInputs.forEach(input => {
        input.addEventListener('change', function() {
            const voiceSelect = document.getElementById('voiceSelect');
            
            if (this.value === 'client') {
                // Mode client : charger les voix du navigateur
                clientTTS.updateVoiceList();
            } else {
                // Mode serveur : remettre les voix Edge TTS
                voiceSelect.innerHTML = `
                    <option value="fr-FR-VivienneMultilingualNeural" selected>Vivienne (Femme - Multilingue)</option>
                    <option value="fr-FR-DeniseNeural">Denise (Femme - Standard)</option>
                    <option value="fr-FR-EloiseNeural">Eloise (Femme - Jeune)</option>
                    <option value="fr-FR-BrigitteNeural">Brigitte (Femme - Mature)</option>
                `;
            }
        });
    });
    
    // Exposer le TTS client globalement
    window.clientTTS = clientTTS;
});