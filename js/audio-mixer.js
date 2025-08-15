// Classe pour mixer l'audio côté client
class AudioMixer {
    constructor() {
        this.audioContext = null;
    }

    async mixAudioFiles(voiceUrl, musicUrl, musicVolume = 30) {
        try {
            // Créer le contexte audio
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            
            // Charger les fichiers audio
            console.log('Chargement de la voix:', voiceUrl);
            const voiceResponse = await fetch(voiceUrl);
            const voiceData = await voiceResponse.arrayBuffer();
            const voiceBuffer = await this.audioContext.decodeAudioData(voiceData);
            
            // Si pas de musique, retourner juste la voix
            if (!musicUrl || musicUrl === 'none') {
                return await this.encodeAudioBuffer(voiceBuffer);
            }
            
            console.log('Chargement de la musique:', musicUrl);
            const musicResponse = await fetch(musicUrl);
            const musicData = await musicResponse.arrayBuffer();
            const musicBuffer = await this.audioContext.decodeAudioData(musicData);
            
            // Créer un buffer pour le mixage
            const duration = voiceBuffer.duration;
            const sampleRate = this.audioContext.sampleRate;
            const numberOfChannels = 2; // Stéréo
            const length = Math.ceil(duration * sampleRate);
            
            const mixedBuffer = this.audioContext.createBuffer(
                numberOfChannels,
                length,
                sampleRate
            );
            
            // Mixer les canaux
            for (let channel = 0; channel < numberOfChannels; channel++) {
                const mixedData = mixedBuffer.getChannelData(channel);
                const voiceData = voiceBuffer.getChannelData(Math.min(channel, voiceBuffer.numberOfChannels - 1));
                const musicData = musicBuffer.getChannelData(Math.min(channel, musicBuffer.numberOfChannels - 1));
                
                const musicGain = musicVolume / 100;
                
                for (let i = 0; i < length; i++) {
                    let voiceSample = 0;
                    let musicSample = 0;
                    
                    // Voix
                    if (i < voiceData.length) {
                        voiceSample = voiceData[i];
                    }
                    
                    // Musique (en boucle si nécessaire)
                    if (musicData.length > 0) {
                        const musicIndex = i % musicData.length;
                        musicSample = musicData[musicIndex] * musicGain;
                    }
                    
                    // Mixer avec limitation pour éviter la saturation
                    mixedData[i] = Math.max(-1, Math.min(1, voiceSample + musicSample));
                }
            }
            
            // Encoder le buffer mixé
            return await this.encodeAudioBuffer(mixedBuffer);
            
        } catch (error) {
            console.error('Erreur lors du mixage:', error);
            throw error;
        }
    }

    async encodeAudioBuffer(buffer) {
        // Créer un nouveau contexte pour l'enregistrement
        const offlineContext = new OfflineAudioContext(
            buffer.numberOfChannels,
            buffer.length,
            buffer.sampleRate
        );
        
        const source = offlineContext.createBufferSource();
        source.buffer = buffer;
        source.connect(offlineContext.destination);
        source.start();
        
        const renderedBuffer = await offlineContext.startRendering();
        
        // Convertir en WAV
        const wavBlob = await this.bufferToWave(renderedBuffer);
        return wavBlob;
    }

    bufferToWave(buffer) {
        const length = buffer.length * buffer.numberOfChannels * 2 + 44;
        const arrayBuffer = new ArrayBuffer(length);
        const view = new DataView(arrayBuffer);
        let offset = 0;
        
        // Écriture de l'en-tête RIFF
        const writeString = (str) => {
            for (let i = 0; i < str.length; i++) {
                view.setUint8(offset + i, str.charCodeAt(i));
            }
            offset += str.length;
        };
        
        const writeUint32 = (value) => {
            view.setUint32(offset, value, true);
            offset += 4;
        };
        
        const writeUint16 = (value) => {
            view.setUint16(offset, value, true);
            offset += 2;
        };
        
        // RIFF header
        writeString('RIFF');
        writeUint32(length - 8);
        writeString('WAVE');
        
        // fmt chunk
        writeString('fmt ');
        writeUint32(16); // chunk size
        writeUint16(1); // PCM
        writeUint16(buffer.numberOfChannels);
        writeUint32(buffer.sampleRate);
        writeUint32(buffer.sampleRate * buffer.numberOfChannels * 2); // byte rate
        writeUint16(buffer.numberOfChannels * 2); // block align
        writeUint16(16); // bits per sample
        
        // data chunk
        writeString('data');
        writeUint32(buffer.length * buffer.numberOfChannels * 2);
        
        // Écriture des données audio
        const channelData = [];
        for (let i = 0; i < buffer.numberOfChannels; i++) {
            channelData.push(buffer.getChannelData(i));
        }
        
        let sampleIndex = 0;
        while (offset < length) {
            for (let i = 0; i < buffer.numberOfChannels; i++) {
                const sample = Math.max(-1, Math.min(1, channelData[i][sampleIndex]));
                view.setInt16(offset, sample * 0x7FFF, true);
                offset += 2;
            }
            sampleIndex++;
        }
        
        return new Blob([arrayBuffer], { type: 'audio/wav' });
    }
}

// Exposer globalement
window.AudioMixer = AudioMixer;