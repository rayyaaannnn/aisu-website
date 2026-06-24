// =============================================================
//  AISU Quiz Room — WebSocket Server (Node.js + Socket.IO)
//  Provides real-time quiz functionality: rooms, questions,
//  answers, scoring, chat, and moderator controls.
// =============================================================

const { createServer } = require('http');
const { Server } = require('socket.io');

const PORT = 3001;

const httpServer = createServer((req, res) => {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ status: 'ok', service: 'AISU Quiz WebSocket Server', rooms: Object.keys(rooms).length }));
});

const io = new Server(httpServer, {
    cors: { origin: '*', methods: ['GET', 'POST'] }
});

// ── In-memory Room Store ─────────────────────────────────────
const rooms = {};

function getRoom(code) {
    return rooms[code] || null;
}

function createRoom(code) {
    rooms[code] = {
        room_code: code,
        room_name: 'Quiz Room ' + code,
        status: 'waiting',         // waiting | in_progress | ended
        participants: [],          // { sid, name, team }
        teams: {},                 // { teamName: { members: [], score: 0 } }
        questions: [],             // loaded by moderator
        currentQuestionIndex: -1,
        answers: {},               // { sid: { answer, correct } } per question
        moderator_sid: null,
        created_at: new Date().toISOString()
    };
    return rooms[code];
}

// ── Socket.IO Event Handlers ─────────────────────────────────
io.on('connection', (socket) => {
    console.log(`[CONNECT] ${socket.id}`);

    // ── JOIN ROOM ────────────────────────────────────────────
    socket.on('join_quiz_room', (data) => {
        const { room_code, name, team, is_moderator } = data;
        if (!room_code || !name) {
            socket.emit('error', { msg: 'Room code and name are required.' });
            return;
        }
        const code = room_code.toUpperCase();
        let room = getRoom(code);
        if (!room) {
            room = createRoom(code);
        }

        // Register participant
        const participant = { sid: socket.id, name, team: team || 'Solo' };
        room.participants.push(participant);

        // Register team
        if (!room.teams[participant.team]) {
            room.teams[participant.team] = { members: [], score: 0 };
        }
        room.teams[participant.team].members.push(name);

        // Set moderator
        if (is_moderator && !room.moderator_sid) {
            room.moderator_sid = socket.id;
        }

        // Initialize proctoring data for this participant
        if (!room.proctor_data) room.proctor_data = {};
        room.proctor_data[socket.id] = {
            name: name,
            violations: [],
            violation_count: 0,
            disqualified: false
        };

        socket.join(code);
        socket.data.room_code = code;
        socket.data.name = name;
        socket.data.team = participant.team;

        // Send room state to joiner
        socket.emit('room_state', {
            room_code: code,
            room_name: room.room_name,
            status: room.status,
            participants: room.participants.map(p => ({ name: p.name, team: p.team })),
            teams: room.teams,
            is_moderator: socket.id === room.moderator_sid,
        });

        // Notify others
        socket.to(code).emit('participant_joined', {
            name,
            team: participant.team,
            total: room.participants.length
        });

        // Notify moderator of proctoring status change
        if (room.moderator_sid) {
            io.to(room.moderator_sid).emit('proctor_participant_joined', {
                sid: socket.id,
                name: name,
                team: participant.team
            });
        }

        console.log(`[JOIN] ${name} → Room ${code} (${room.participants.length} participants)`);
    });

    // ── MODERATOR: START QUIZ ────────────────────────────────
    socket.on('moderator_start_quiz', (data) => {
        const code = data.room_code?.toUpperCase();
        const room = getRoom(code);
        if (!room) return;
        if (socket.id !== room.moderator_sid) {
            socket.emit('error', { msg: 'Only the moderator can start the quiz.' });
            return;
        }

        room.questions = data.questions || getSampleQuestions();
        room.status = 'in_progress';
        room.currentQuestionIndex = 0;
        room.answers = {};

        io.to(code).emit('quiz_started');

        // Send first question
        sendQuestion(code);

        console.log(`[START] Quiz started in ${code} with ${room.questions.length} questions`);
    });

    // ── MODERATOR: NEXT QUESTION ─────────────────────────────
    socket.on('moderator_next_question', (data) => {
        const code = data.room_code?.toUpperCase();
        const room = getRoom(code);
        if (!room) return;
        if (socket.id !== room.moderator_sid) return;

        room.currentQuestionIndex++;
        room.answers = {};

        if (room.currentQuestionIndex >= room.questions.length) {
            // Quiz ended
            endQuiz(code);
        } else {
            sendQuestion(code);
        }
    });

    // ── SUBMIT ANSWER ────────────────────────────────────────
    socket.on('submit_answer', (data) => {
        const code = data.room_code?.toUpperCase();
        const room = getRoom(code);
        if (!room || room.status !== 'in_progress') return;

        const q = room.questions[room.currentQuestionIndex];
        if (!q) return;

        const isCorrect = data.answer === q.correct;
        const team = socket.data.team || 'Solo';

        room.answers[socket.id] = { answer: data.answer, correct: isCorrect };

        // Update team score
        if (isCorrect && room.teams[team]) {
            room.teams[team].score += (q.points || 10);
        }

        // Notify answerer
        socket.emit('answer_received', {
            correct: isCorrect,
            correct_answer: q.correct,
            team_score: room.teams[team]?.score || 0
        });

        // Notify moderator which team answered
        if (room.moderator_sid) {
            io.to(room.moderator_sid).emit('team_answered', {
                team,
                name: socket.data.name,
                answer: data.answer,
                correct: isCorrect
            });
        }
    });

    // ── CHAT ─────────────────────────────────────────────────
    socket.on('quiz_chat', (data) => {
        const code = data.room_code?.toUpperCase();
        if (!code) return;

        io.to(code).emit('chat_message', {
            name: socket.data.name || 'Anonymous',
            message: data.message
        });
    });

    // ── MODERATOR: MUTE PARTICIPANT ──────────────────────────
    socket.on('moderator_mute', (data) => {
        const code = data.room_code?.toUpperCase();
        const room = getRoom(code);
        if (!room || socket.id !== room.moderator_sid) return;

        const target = room.participants.find(p => p.name === data.target_name);
        if (target) {
            io.to(target.sid).emit('you_were_muted', { by: socket.data.name });
        }
    });

    // ── MODERATOR: REMOVE PARTICIPANT ────────────────────────
    socket.on('moderator_remove', (data) => {
        const code = data.room_code?.toUpperCase();
        const room = getRoom(code);
        if (!room || socket.id !== room.moderator_sid) return;

        const targetIdx = room.participants.findIndex(p => p.name === data.target_name);
        if (targetIdx >= 0) {
            const target = room.participants[targetIdx];
            io.to(target.sid).emit('you_were_removed', { reason: data.reason || 'Removed by moderator' });
            const targetSocket = io.sockets.sockets.get(target.sid);
            if (targetSocket) {
                targetSocket.leave(code);
            }
            room.participants.splice(targetIdx, 1);
            io.to(code).emit('participant_left', { name: target.name, total: room.participants.length });
        }
    });

    // ── WEBRTC: OFFER ───────────────────────────────────────
    socket.on('webrtc_offer', (data) => {
        const { room_code, target_sid, offer } = data;
        const code = room_code?.toUpperCase();
        if (!code || !target_sid) return;

        const targetSocket = io.sockets.sockets.get(target_sid);
        if (targetSocket) {
            targetSocket.emit('webrtc_offer', {
                from: socket.id,
                from_name: socket.data.name || 'Anonymous',
                offer: offer
            });
        }
    });

    // ── WEBRTC: ANSWER ──────────────────────────────────────
    socket.on('webrtc_answer', (data) => {
        const { room_code, target_sid, answer } = data;
        const code = room_code?.toUpperCase();
        if (!code || !target_sid) return;

        const targetSocket = io.sockets.sockets.get(target_sid);
        if (targetSocket) {
            targetSocket.emit('webrtc_answer', {
                from: socket.id,
                answer: answer
            });
        }
    });

    // ── WEBRTC: ICE CANDIDATE ────────────────────────────────
    socket.on('webrtc_ice_candidate', (data) => {
        const { room_code, target_sid, candidate } = data;
        const code = room_code?.toUpperCase();
        if (!code || !target_sid) return;

        const targetSocket = io.sockets.sockets.get(target_sid);
        if (targetSocket) {
            targetSocket.emit('webrtc_ice_candidate', {
                from: socket.id,
                candidate: candidate
            });
        }
    });

    // ── VIDEO/AUDIO STATUS CHANGE ─────────────────────────────
    socket.on('media_status_change', (data) => {
        const code = data.room_code?.toUpperCase();
        if (!code) return;

        io.to(code).emit('participant_media_status', {
            participant_name: socket.data.name || 'Anonymous',
            video_enabled: data.video_enabled,
            audio_enabled: data.audio_enabled
        });
    });

    // ── ADMIN: MUTE PARTICIPANT ──────────────────────────────
    socket.on('admin_mute_participant', (data) => {
        const code = data.room_code?.toUpperCase();
        const room = getRoom(code);
        if (!room || socket.id !== room.moderator_sid) return;

        const target = room.participants.find(p => p.name === data.target_name);
        if (target) {
            io.to(target.sid).emit('admin_audio_disabled', { 
                by: socket.data.name,
                reason: 'Muted by administrator'
            });
        }
    });

    // ── ADMIN: MUTE TEAM/GROUP ──────────────────────────────
    socket.on('admin_mute_team', (data) => {
        const code = data.room_code?.toUpperCase();
        const room = getRoom(code);
        if (!room || socket.id !== room.moderator_sid) return;

        const team = room.teams[data.team_name];
        if (team) {
            team.members.forEach(memberName => {
                const member = room.participants.find(p => p.name === memberName);
                if (member) {
                    io.to(member.sid).emit('team_audio_disabled', {
                        by: socket.data.name,
                        reason: 'Team muted by administrator'
                    });
                }
            });
        }
    });

    // ── ADMIN: UNMUTE ───────────────────────────────────────
    socket.on('admin_unmute', (data) => {
        const code = data.room_code?.toUpperCase();
        if (!code) return;

        const target_name = data.target_name;
        const target_type = data.target_type || 'participant'; // 'participant' or 'team'

        const room = getRoom(code);
        if (room && socket.id === room.moderator_sid) {
            if (target_type === 'team') {
                const team = room.teams[target_name];
                if (team) {
                    team.members.forEach(memberName => {
                        const member = room.participants.find(p => p.name === memberName);
                        if (member) {
                            io.to(member.sid).emit('audio_enabled', { type: 'team' });
                        }
                    });
                }
            } else {
                const target = room.participants.find(p => p.name === target_name);
                if (target) {
                    io.to(target.sid).emit('audio_enabled', { type: 'participant' });
                }
            }
        }
    });

    // ── ADMIN: START RECORDING ──────────────────────────────
    socket.on('admin_start_recording', (data) => {
        const code = data.room_code?.toUpperCase();
        const room = getRoom(code);
        if (!room || socket.id !== room.moderator_sid) return;

        room.recording = {
            started_at: new Date().toISOString(),
            recording_id: 'REC-' + Math.random().toString(36).substr(2, 9),
            admin: socket.data.name
        };

        io.to(code).emit('recording_started', {
            recording_id: room.recording.recording_id,
            admin_name: socket.data.name
        });
        console.log(`[RECORDING] Started in ${code}: ${room.recording.recording_id}`);
    });

    // ── ADMIN: STOP RECORDING ────────────────————————────────
    socket.on('admin_stop_recording', (data) => {
        const code = data.room_code?.toUpperCase();
        const room = getRoom(code);
        if (!room || socket.id !== room.moderator_sid) return;

        if (room.recording) {
            const recording_id = room.recording.recording_id;
            room.recording.stopped_at = new Date().toISOString();
            room.recording.duration = 
                (new Date(room.recording.stopped_at) - new Date(room.recording.started_at)) / 1000;
            
            io.to(code).emit('recording_stopped', {
                recording_id: recording_id,
                duration: room.recording.duration
            });
            console.log(`[RECORDING] Stopped in ${code}: ${recording_id} (${room.recording.duration}s)`);
        }
    });

    // ── ADMIN: FINAL SCORES ──────────────────────────────────
    socket.on('admin_finalize_scores', (data) => {
        const code = data.room_code?.toUpperCase();
        const room = getRoom(code);
        if (!room || socket.id !== room.moderator_sid) return;

        // Sort teams by score
        const sortedTeams = Object.entries(room.teams)
            .sort((a, b) => b[1].score - a[1].score)
            .map(([teamName, teamData], index) => ({
                rank: index + 1,
                team_name: teamName,
                score: teamData.score,
                members: teamData.members
            }));

        room.final_scores = sortedTeams;

        // Broadcast to all participants
        io.to(code).emit('final_results', {
            results: sortedTeams,
            announced_by: socket.data.name,
            announced_at: new Date().toISOString()
        });

        console.log(`[SCORES] Finalized in ${code}:`, sortedTeams);
    });

    // ── PROCTOR: VIOLATION ────────────────────────────────────
    socket.on('proctor_violation', (data) => {
        const code = data.room_code?.toUpperCase();
        const room = getRoom(code);
        if (!room) return;

        const sid = socket.id;
        if (!room.proctor_data) room.proctor_data = {};
        if (!room.proctor_data[sid]) {
            room.proctor_data[sid] = { name: socket.data.name || 'Unknown', violations: [], violation_count: 0, disqualified: false };
        }

        const pd = room.proctor_data[sid];
        const violation = {
            type: data.violation_type || 'unknown',
            message: data.message || 'Violation',
            count: data.count || (pd.violation_count + 1),
            max_allowed: data.max_allowed || 3,
            timestamp: new Date().toISOString()
        };
        pd.violations.push(violation);
        pd.violation_count = pd.violations.length;

        console.log(`[PROCTOR] ${pd.name} violated: ${violation.type} (${pd.violation_count}/${violation.max_allowed})`);

        // Broadcast violation to moderator
        if (room.moderator_sid) {
            io.to(room.moderator_sid).emit('proctor_violation', {
                participant_sid: sid,
                participant_name: pd.name,
                violation_type: violation.type,
                message: violation.message,
                count: pd.violation_count,
                max_allowed: violation.max_allowed,
                timestamp: violation.timestamp
            });
        }

        // If max violations reached — auto-disqualify
        if (pd.violation_count >= (data.max_allowed || 3) && !pd.disqualified) {
            pd.disqualified = true;
            console.log(`[PROCTOR] ${pd.name} DISQUALIFIED from room ${code}`);

            io.to(sid).emit('proctor_disqualified', {
                reason: 'Exceeded maximum allowed violations (' + (data.max_allowed || 3) + ')'
            });

            // Notify moderator
            if (room.moderator_sid) {
                io.to(room.moderator_sid).emit('proctor_disqualified', {
                    participant_sid: sid,
                    participant_name: pd.name,
                    reason: 'Exceeded maximum allowed violations'
                });
            }

            // Remove participant from room
            const idx = room.participants.findIndex(p => p.sid === sid);
            if (idx >= 0) {
                const left = room.participants[idx];
                room.participants.splice(idx, 1);
                io.to(code).emit('participant_left', { name: left.name, total: room.participants.length });
                if (room.teams[left.team]) {
                    room.teams[left.team].members = room.teams[left.team].members.filter(m => m !== left.name);
                    if (room.teams[left.team].members.length === 0) {
                        delete room.teams[left.team];
                    }
                }
            }
        }
    });

    // ── PROCTOR: DISQUALIFIED (self-report from client) ──────
    socket.on('proctor_disqualified', (data) => {
        const code = data.room_code?.toUpperCase();
        const room = getRoom(code);
        if (!room) return;

        const sid = socket.id;
        if (room.proctor_data && room.proctor_data[sid]) {
            room.proctor_data[sid].disqualified = true;
        }

        if (room.moderator_sid) {
            io.to(room.moderator_sid).emit('proctor_disqualified', {
                participant_sid: sid,
                participant_name: socket.data.name || 'Unknown',
                reason: data.reason || 'Manual disqualification'
            });
        }
    });

    // ── PROCTOR: ADMIN REQUEST STATUS ──────────────────────────
    socket.on('proctor_request_status', (data) => {
        const code = data.room_code?.toUpperCase();
        const room = getRoom(code);
        if (!room || socket.id !== room.moderator_sid) return;

        const proctorStatus = [];
        if (room.proctor_data) {
            Object.entries(room.proctor_data).forEach(([sid, pd]) => {
                const participant = room.participants.find(p => p.sid === sid);
                proctorStatus.push({
                    sid: sid,
                    name: pd.name,
                    team: participant?.team || 'Solo',
                    violation_count: pd.violation_count,
                    violations: pd.violations.slice(-10), // last 10
                    disqualified: pd.disqualified,
                    camera_on: true // set by client
                });
            });
        }

        socket.emit('proctor_status_list', { participants: proctorStatus });
    });

    // ── PROCTOR: ADMIN DISQUALIFY PARTICIPANT ─────────────────
    socket.on('proctor_admin_disqualify', (data) => {
        const code = data.room_code?.toUpperCase();
        const room = getRoom(code);
        if (!room || socket.id !== room.moderator_sid) return;

        const targetSid = data.target_sid;
        if (!targetSid) return;

        if (room.proctor_data && room.proctor_data[targetSid]) {
            room.proctor_data[targetSid].disqualified = true;
        }

        // Notify target
        io.to(targetSid).emit('proctor_disqualified', {
            reason: data.reason || 'Disqualified by administrator'
        });

        // Remove from room
        const idx = room.participants.findIndex(p => p.sid === targetSid);
        if (idx >= 0) {
            const left = room.participants[idx];
            room.participants.splice(idx, 1);
            io.to(code).emit('participant_left', { name: left.name, total: room.participants.length });
        }

        console.log(`[PROCTOR] Admin disqualified ${room.proctor_data[targetSid]?.name || targetSid} from ${code}`);
    });

    // ── PROCTOR: ADMIN WARN PARTICIPANT ────────────────────────
    socket.on('proctor_admin_warn', (data) => {
        const code = data.room_code?.toUpperCase();
        if (!code) return;

        const targetSid = data.target_sid;
        if (targetSid) {
            io.to(targetSid).emit('proctor_warning', {
                message: data.message || 'Warning from administrator',
                issued_by: socket.data.name || 'Admin'
            });
        }
    });

    // ── DISCONNECT ───────────────────────────────────────────
    socket.on('disconnect', () => {
        const code = socket.data.room_code;
        if (code) {
            const room = getRoom(code);
            if (room) {
                const idx = room.participants.findIndex(p => p.sid === socket.id);
                if (idx >= 0) {
                    const left = room.participants[idx];
                    room.participants.splice(idx, 1);
                    io.to(code).emit('participant_left', { name: left.name, total: room.participants.length });

                    // Remove from team
                    if (room.teams[left.team]) {
                        room.teams[left.team].members = room.teams[left.team].members.filter(m => m !== left.name);
                        if (room.teams[left.team].members.length === 0) {
                            delete room.teams[left.team];
                        }
                    }
                }
                // Clean up proctor data for disconnected participant
                if (room.proctor_data) {
                    delete room.proctor_data[socket.id];
                }
                // Clean up empty rooms
                if (room.participants.length === 0) {
                    delete rooms[code];
                    console.log(`[CLEANUP] Room ${code} removed (empty)`);
                }
            }
        }
        console.log(`[DISCONNECT] ${socket.id}`);
    });
});

// ── Helper Functions ─────────────────────────────────────────

function sendQuestion(code) {
    const room = getRoom(code);
    if (!room) return;

    const q = room.questions[room.currentQuestionIndex];
    if (!q) return;

    io.to(code).emit('question', {
        index: room.currentQuestionIndex,
        total: room.questions.length,
        text: q.text,
        options: q.options,
        image: q.image || null,
        time_limit: q.time_limit || 30
    });
}

function endQuiz(code) {
    const room = getRoom(code);
    if (!room) return;

    room.status = 'ended';

    // Build leaderboard sorted by score
    const leaderboard = Object.entries(room.teams)
        .map(([team, data]) => ({ team, score: data.score, members: data.members.length }))
        .sort((a, b) => b.score - a.score);

    io.to(code).emit('quiz_ended', { leaderboard });
    console.log(`[END] Quiz ended in ${code}. Winner: ${leaderboard[0]?.team || 'N/A'}`);
}

function getSampleQuestions() {
    return [
        {
            text: 'What does AISU stand for?',
            options: ['All India Students Union', 'All India Social Unity', 'Association of Indian Student Unions', 'Allied Indian Students United'],
            correct: 'All India Students Union',
            points: 10,
            time_limit: 30
        },
        {
            text: 'Under which act is AISU registered?',
            options: ['Indian Trust Act, 1882', 'Societies Registration Act, 1860', 'Companies Act, 2013', 'Cooperative Societies Act, 1912'],
            correct: 'Indian Trust Act, 1882',
            points: 10,
            time_limit: 30
        },
        {
            text: 'What is the AISU NITI Aayog Registration Number?',
            options: ['BR/2023/0335557', 'BR/2022/0123456', 'DL/2023/0987654', 'MH/2022/0456789'],
            correct: 'BR/2023/0335557',
            points: 10,
            time_limit: 30
        },
        {
            text: 'Where is AISU headquarters located?',
            options: ['West Champaran, Bihar', 'New Delhi', 'Mumbai, Maharashtra', 'Lucknow, Uttar Pradesh'],
            correct: 'West Champaran, Bihar',
            points: 10,
            time_limit: 30
        },
        {
            text: 'AISU is a division of which parent organization?',
            options: ['Federation of Indian Youth Association', 'National Student Forum', 'Youth Congress India', 'Bharatiya Yuva Sangh'],
            correct: 'Federation of Indian Youth Association',
            points: 10,
            time_limit: 30
        }
    ];
}

// ── Start Server ─────────────────────────────────────────────
httpServer.listen(PORT, () => {
    console.log(`\n  🎯 AISU Quiz WebSocket Server`);
    console.log(`  ─────────────────────────────`);
    console.log(`  ✓ Listening on http://localhost:${PORT}`);
    console.log(`  ✓ Socket.IO ready for connections`);
    console.log(`  ✓ CORS: all origins allowed\n`);
});
