/**
 * Notification Sound Generator
 * Fallback menggunakan Web Audio API jika file sound tidak tersedia
 */

class NotificationSound {
    constructor() {
        this.audioContext = null;
        this.audioElement = document.getElementById('newOrderSound');
    }
    
    /**
     * Initialize Audio Context
     */
    initAudioContext() {
        if (!this.audioContext) {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
        return this.audioContext;
    }
    
    /**
     * Play notification sound
     * Try audio file first, fallback to generated tone
     */
    async play() {
        // Try to play audio file first
        if (this.audioElement) {
            try {
                await this.audioElement.play();
                return;
            } catch (e) {
                console.log('Audio file not available, using generated tone');
            }
        }
        
        // Fallback: Generate tone using Web Audio API
        this.playGeneratedTone();
    }
    
    /**
     * Generate notification tone using Web Audio API
     * Creates a pleasant "ding" sound
     */
    playGeneratedTone() {
        const context = this.initAudioContext();
        
        // Create oscillator (tone generator)
        const oscillator = context.createOscillator();
        const gainNode = context.createGain();
        
        // Connect nodes
        oscillator.connect(gainNode);
        gainNode.connect(context.destination);
        
        // Configure sound
        oscillator.type = 'sine'; // Smooth sine wave
        oscillator.frequency.setValueAtTime(800, context.currentTime); // 800 Hz (pleasant ding)
        
        // Envelope (volume over time)
        gainNode.gain.setValueAtTime(0.3, context.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, context.currentTime + 0.5);
        
        // Play sound
        oscillator.start(context.currentTime);
        oscillator.stop(context.currentTime + 0.5);
        
        // Play second tone for "double ding" effect
        setTimeout(() => {
            const osc2 = context.createOscillator();
            const gain2 = context.createGain();
            
            osc2.connect(gain2);
            gain2.connect(context.destination);
            
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(1000, context.currentTime);
            gain2.gain.setValueAtTime(0.25, context.currentTime);
            gain2.gain.exponentialRampToValueAtTime(0.01, context.currentTime + 0.4);
            
            osc2.start(context.currentTime);
            osc2.stop(context.currentTime + 0.4);
        }, 150);
    }
    
    /**
     * Play urgent notification (more aggressive)
     */
    playUrgentTone() {
        const context = this.initAudioContext();
        
        // Play 3 quick beeps
        for (let i = 0; i < 3; i++) {
            setTimeout(() => {
                const oscillator = context.createOscillator();
                const gainNode = context.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(context.destination);
                
                oscillator.type = 'square'; // More aggressive square wave
                oscillator.frequency.setValueAtTime(1200, context.currentTime);
                
                gainNode.gain.setValueAtTime(0.2, context.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, context.currentTime + 0.15);
                
                oscillator.start(context.currentTime);
                oscillator.stop(context.currentTime + 0.15);
            }, i * 200);
        }
    }
}

// Export for use
window.NotificationSound = NotificationSound;
