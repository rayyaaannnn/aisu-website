// ============================================================
//  js/quiz-webrtc.js - WebRTC Video/Audio for Quiz Rooms
//  Handles peer connections, video/audio streaming, and admin controls
// ============================================================

class QuizWebRTC {
    constructor(socket) {
        this.socket = socket;
        this.peerConnections = {}; // { remoteSid: RTCPeerConnection }
        this.localStream = null;
        this.videoEnabled = true;
        this.audioEnabled = true;
        this.remoteTracks = {}; // { remoteSid: { video: [], audio: [] } }
        
        // Configuration
        this.iceServers = [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' },
        ];

        this.rtcConfig = {
            iceServers: this.iceServers,
            iceCandidatePoolSize: 10
        };
    }

    // ── INITIALIZE LOCAL MEDIA ────────────────────────────────
    async initializeMedia(video = true, audio = true) {
        try {
            this.localStream = await navigator.mediaDevices.getUserMedia({
                video: video ? {
                    width: { ideal: 320 },
                    height: { ideal: 240 }
                } : false,
                audio: audio ? {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true
                } : false
            });

            this.videoEnabled = video;
            this.audioEnabled = audio;

            return this.localStream;
        } catch (err) {
            console.error('Failed to get media:', err);
            throw err;
        }
    }

    // ── SET LOCAL VIDEO ELEMENT ───────────────────────────────
    displayLocalVideo(videoElement) {
        if (this.localStream && videoElement) {
            videoElement.srcObject = this.localStream;
            videoElement.onloadedmetadata = () => {
                videoElement.play().catch(e => console.error('Play error:', e));
            };
        }
    }

    // ── TOGGLE VIDEO ──────────────────────────────────────────
    toggleVideo(enabled) {
        if (this.localStream) {
            this.localStream.getVideoTracks().forEach(track => {
                track.enabled = enabled;
            });
            this.videoEnabled = enabled;
            this.notifyMediaStatus();
        }
    }

    // ── TOGGLE AUDIO ──────────────────────────────────────────
    toggleAudio(enabled) {
        if (this.localStream) {
            this.localStream.getAudioTracks().forEach(track => {
                track.enabled = enabled;
            });
            this.audioEnabled = enabled;
            this.notifyMediaStatus();
        }
    }

    // ── NOTIFY MEDIA STATUS ───────────────────────────────────
    notifyMediaStatus() {
        if (this.socket) {
            this.socket.emit('media_status_change', {
                room_code: window.roomCode,
                video_enabled: this.videoEnabled,
                audio_enabled: this.audioEnabled
            });
        }
    }

    // ── CREATE PEER CONNECTION ────────────────────────────────
    async createPeerConnection(remoteSid) {
        if (this.peerConnections[remoteSid]) {
            return this.peerConnections[remoteSid];
        }

        const pc = new RTCPeerConnection(this.rtcConfig);

        // Add local tracks
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => {
                pc.addTrack(track, this.localStream);
            });
        }

        // Handle remote tracks
        pc.ontrack = (event) => {
            console.log('Received remote track:', event.track.kind);
            if (!this.remoteTracks[remoteSid]) {
                this.remoteTracks[remoteSid] = { video: [], audio: [] };
            }
            this.remoteTracks[remoteSid][event.track.kind].push(event.track);

            // Create video element for remote stream
            if (event.track.kind === 'video') {
                const videoElement = this.createRemoteVideoElement(remoteSid);
                const stream = new MediaStream([event.track]);
                videoElement.srcObject = stream;
                videoElement.play().catch(e => console.error('Play error:', e));
            }
        };

        // Handle ICE candidates
        pc.onicecandidate = (event) => {
            if (event.candidate) {
                this.socket.emit('webrtc_ice_candidate', {
                    room_code: window.roomCode,
                    target_sid: remoteSid,
                    candidate: event.candidate.toJSON()
                });
            }
        };

        // Connection state changes
        pc.onconnectionstatechange = () => {
            console.log(`Connection state with ${remoteSid}: ${pc.connectionState}`);
            if (pc.connectionState === 'failed' || pc.connectionState === 'disconnected') {
                this.closePeerConnection(remoteSid);
            }
        };

        this.peerConnections[remoteSid] = pc;
        return pc;
    }

    // ── CREATE REMOTE VIDEO ELEMENT ───────────────────────────
    createRemoteVideoElement(remoteSid) {
        const container = document.getElementById('video-grid-container');
        if (!container) {
            console.error('Video grid container not found');
            return null;
        }

        let videoElement = document.getElementById(`video-${remoteSid}`);
        if (!videoElement) {
            videoElement = document.createElement('video');
            videoElement.id = `video-${remoteSid}`;
            videoElement.autoplay = true;
            videoElement.playsinline = true;
            videoElement.style.cssText = `
                width: 100%;
                height: 100%;
                background: #000;
                border-radius: 8px;
                object-fit: cover;
            `;
            
            const wrapper = document.createElement('div');
            wrapper.id = `video-wrapper-${remoteSid}`;
            wrapper.style.cssText = `
                position: relative;
                width: 100%;
                padding-bottom: 100%;
                border-radius: 8px;
                overflow: hidden;
                background: rgba(0,0,0,0.5);
                border: 1px solid rgba(255,255,255,0.1);
            `;
            wrapper.style.position = 'relative';
            wrapper.appendChild(videoElement);
            container.appendChild(wrapper);

            // Adjust wrapper to be positioned absolutely
            wrapper.style.position = 'absolute';
            wrapper.style.width = '150px';
            wrapper.style.height = '150px';
            wrapper.style.padding = '0';
        }

        return videoElement;
    }

    // ── CREATE OFFER ──────────────────────────────────────────
    async createOffer(remoteSid) {
        const pc = await this.createPeerConnection(remoteSid);
        try {
            const offer = await pc.createOffer({
                offerToReceiveAudio: true,
                offerToReceiveVideo: true
            });
            await pc.setLocalDescription(offer);
            
            this.socket.emit('webrtc_offer', {
                room_code: window.roomCode,
                target_sid: remoteSid,
                offer: offer
            });
        } catch (err) {
            console.error('Failed to create offer:', err);
        }
    }

    // ── HANDLE OFFER ──────────────────────────────────────────
    async handleOffer(remoteSid, offer) {
        const pc = await this.createPeerConnection(remoteSid);
        try {
            await pc.setRemoteDescription(new RTCSessionDescription(offer));
            const answer = await pc.createAnswer();
            await pc.setLocalDescription(answer);

            this.socket.emit('webrtc_answer', {
                room_code: window.roomCode,
                target_sid: remoteSid,
                answer: answer
            });
        } catch (err) {
            console.error('Failed to handle offer:', err);
        }
    }

    // ── HANDLE ANSWER ─────────────────────────────────────────
    async handleAnswer(remoteSid, answer) {
        const pc = this.peerConnections[remoteSid];
        if (pc) {
            try {
                await pc.setRemoteDescription(new RTCSessionDescription(answer));
            } catch (err) {
                console.error('Failed to handle answer:', err);
            }
        }
    }

    // ── HANDLE ICE CANDIDATE ──────────────────────────────────
    async handleIceCandidate(remoteSid, candidate) {
        const pc = this.peerConnections[remoteSid];
        if (pc && candidate) {
            try {
                await pc.addIceCandidate(new RTCIceCandidate(candidate));
            } catch (err) {
                console.error('Failed to add ICE candidate:', err);
            }
        }
    }

    // ── CLOSE PEER CONNECTION ─────────────────────────────────
    closePeerConnection(remoteSid) {
        const pc = this.peerConnections[remoteSid];
        if (pc) {
            pc.close();
            delete this.peerConnections[remoteSid];
        }

        // Remove video element
        const wrapper = document.getElementById(`video-wrapper-${remoteSid}`);
        if (wrapper) {
            wrapper.remove();
        }

        if (this.remoteTracks[remoteSid]) {
            delete this.remoteTracks[remoteSid];
        }
    }

    // ── STOP ALL CONNECTIONS ─────────────────────────────────
    stopAll() {
        // Stop local stream
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => track.stop());
            this.localStream = null;
        }

        // Close all peer connections
        Object.keys(this.peerConnections).forEach(sid => {
            this.closePeerConnection(sid);
        });

        this.peerConnections = {};
        this.remoteTracks = {};
    }

    // ── APPLY MEDIA RESTRICTIONS (Admin) ────────────────────
    muteParticipant(remoteSid) {
        const pc = this.peerConnections[remoteSid];
        if (pc) {
            pc.getSenders().forEach(sender => {
                if (sender.track && sender.track.kind === 'audio') {
                    sender.track.enabled = false;
                }
            });
        }
    }

    muteTeam(teamName) {
        // This would be called by admin to mute an entire team
        // Implementation depends on team member tracking
    }

    unmuteParticipant(remoteSid) {
        const pc = this.peerConnections[remoteSid];
        if (pc) {
            pc.getSenders().forEach(sender => {
                if (sender.track && sender.track.kind === 'audio') {
                    sender.track.enabled = true;
                }
            });
        }
    }

    // ── RECORDING SUPPORT ──────────────────────────────────────
    startRecording() {
        const fileName = `quiz-recording-${Date.now()}.webm`;
        
        // Collect all streams for recording
        const recordingStreams = [];
        if (this.localStream) {
            recordingStreams.push(this.localStream);
        }

        Object.values(this.remoteTracks).forEach(tracks => {
            if (tracks.video.length > 0 || tracks.audio.length > 0) {
                const stream = new MediaStream();
                tracks.video.forEach(track => stream.addTrack(track));
                tracks.audio.forEach(track => stream.addTrack(track));
                recordingStreams.push(stream);
            }
        });

        // Create composite canvas for recording (optional - advanced)
        // For now, use simple MediaRecorder

        if (recordingStreams.length > 0) {
            const canvas = new OffscreenCanvas(1280, 720);
            const ctx = canvas.getContext('2d');
            
            // This is a simplified approach
            console.log('Recording started with', recordingStreams.length, 'streams');
            return { fileName, streams: recordingStreams };
        }

        return null;
    }

    stopRecording() {
        console.log('Recording stopped');
    }
}

// Export for use
window.QuizWebRTC = QuizWebRTC;
