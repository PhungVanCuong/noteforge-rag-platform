<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $studySet->title }} - NoteForge Offline Player</title>
    <style>
        :root {
            --bg-grid: #0D2E5C;
            --paper-card: #F7F4EC;
            --paper-border: #D1C7B7;
            --accent-amber: #FFB627;
            --primary-navy: #081C36;
            --correct-green: #10B981;
            --wrong-red: #EF4444;
            --text-dark: #1F2937;
            --text-muted: #6B7280;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-grid);
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 24px 24px;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background: rgba(8, 28, 54, 0.95);
            backdrop-filter: blur(8px);
            border-bottom: 2px solid var(--accent-amber);
            padding: 16px 24px;
            color: #FFFFFF;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .brand-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--accent-amber);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-tag {
            background: #1E3A8A;
            color: #93C5FD;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: monospace;
        }

        .mode-nav {
            display: flex;
            gap: 8px;
            background: rgba(255,255,255,0.08);
            padding: 4px;
            border-radius: 8px;
        }

        .mode-btn {
            background: transparent;
            border: none;
            color: #D1D5DB;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .mode-btn.active {
            background: var(--accent-amber);
            color: var(--primary-navy);
            box-shadow: 0 2px 8px rgba(255, 182, 39, 0.4);
        }

        main {
            flex: 1;
            max-width: 960px;
            width: 100%;
            margin: 24px auto;
            padding: 0 16px 40px;
        }

        .paper-card {
            background: var(--paper-card);
            border: 2px solid var(--paper-border);
            border-radius: 12px;
            padding: 24px 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            margin-bottom: 20px;
            position: relative;
        }

        .card-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px dashed var(--paper-border);
        }

        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }

        .filter-chip {
            background: rgba(13, 46, 92, 0.08);
            border: 1px solid var(--paper-border);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            font-weight: 500;
        }

        .filter-chip.active {
            background: var(--bg-grid);
            color: #FFFFFF;
            border-color: var(--bg-grid);
        }

        .filter-chip.danger.active {
            background: var(--wrong-red);
            border-color: var(--wrong-red);
            color: #fff;
        }

        .question-item {
            margin-bottom: 30px;
            padding-bottom: 24px;
            border-bottom: 1px solid #E5E7EB;
        }

        .question-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--primary-navy);
            margin-bottom: 14px;
            line-height: 1.5;
        }

        .options-grid {
            display: grid;
            gap: 10px;
        }

        .option-btn {
            background: #FFFFFF;
            border: 1.5px solid #D1D5DB;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 15px;
            text-align: left;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .option-btn:hover:not(:disabled) {
            border-color: var(--bg-grid);
            background: #F3F4F6;
        }

        .option-btn.correct {
            background: #ECFDF5 !important;
            border-color: var(--correct-green) !important;
            color: #065F46 !important;
            font-weight: 600;
        }

        .option-btn.wrong {
            background: #FEF2F2 !important;
            border-color: var(--wrong-red) !important;
            color: #991B1B !important;
            font-weight: 600;
        }

        .explanation-box {
            margin-top: 12px;
            background: #EFF6FF;
            border-left: 4px solid #3B82F6;
            padding: 12px 16px;
            border-radius: 4px;
            font-size: 14px;
            color: #1E3A8A;
            line-height: 1.5;
        }

        /* Cloze Code Style */
        .code-container {
            background: #1E293B;
            color: #E2E8F0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px;
            line-height: 2.2;
            overflow-x: auto;
            white-space: pre-wrap;
        }

        .cloze-input {
            background: #0F172A;
            color: #38BDF8;
            border: 1.5px dashed #94A3B8;
            border-radius: 4px;
            padding: 4px 8px;
            font-family: inherit;
            font-size: 14px;
            font-weight: bold;
            outline: none;
            transition: all 0.2s;
            margin: 0 4px;
            vertical-align: middle;
        }

        .cloze-input.valid {
            border-color: var(--correct-green);
            background: rgba(16, 185, 129, 0.15);
            color: #34D399;
        }

        .cloze-input.invalid {
            border-color: var(--wrong-red);
            background: rgba(239, 68, 68, 0.15);
            color: #F87171;
        }

        /* Timer badge */
        .timer-badge {
            font-family: monospace;
            font-size: 20px;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 6px;
            background: #111827;
            color: #10B981;
            border: 1px solid #374151;
        }

        .timer-badge.urgent {
            color: var(--wrong-red);
            animation: pulse-red 1s infinite alternate;
        }

        @keyframes pulse-red {
            from { opacity: 1; transform: scale(1); }
            to { opacity: 0.6; transform: scale(1.05); }
        }

        .action-btn {
            background: var(--bg-grid);
            color: #FFFFFF;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .action-btn.amber {
            background: var(--accent-amber);
            color: var(--primary-navy);
        }
    </style>
</head>
<body>

<header>
    <div class="brand-title">
        <span>⚡ NoteForge AI</span>
        <span class="brand-tag">OFFLINE PLAYER</span>
    </div>
    <div class="mode-nav">
        <button class="mode-btn active" onclick="switchMode('practice')">📝 Luyện trắc nghiệm</button>
        <button class="mode-btn" onclick="switchMode('exam')">⏱️ Thi thử tính giờ</button>
        <button class="mode-btn" onclick="switchMode('cloze')">💻 Điền khuyết Code</button>
    </div>
</header>

<main>
    <div class="paper-card">
        <div class="card-header-bar">
            <div>
                <h1 style="font-size: 20px; color: var(--primary-navy);">{{ $studySet->title }}</h1>
                <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
                    Tổng số câu: <strong id="total-q-count">0</strong> &bull; Dữ liệu nén Offline độc lập
                </p>
            </div>
            <div id="exam-timer-wrapper" style="display: none;">
                <div id="exam-timer" class="timer-badge">15:00</div>
            </div>
        </div>

        <!-- Mode 1: Practice -->
        <div id="view-practice">
            <div class="filter-bar" id="category-filters"></div>
            <div id="practice-questions-list"></div>
        </div>

        <!-- Mode 2: Exam -->
        <div id="view-exam" style="display: none;">
            <div id="exam-start-banner" style="text-align: center; padding: 40px 20px;">
                <h2 style="margin-bottom: 12px; color: var(--primary-navy);">Sẵn sàng cho bài thi thử?</h2>
                <p style="color: var(--text-muted); margin-bottom: 24px;">
                    Cấu trúc đề: 20 câu ngẫu nhiên &bull; Thời gian làm bài: 15 phút &bull; Thang điểm: 10
                </p>
                <button class="action-btn amber" style="font-size: 16px; padding: 12px 30px;" onclick="startExam()">
                    🚀 Bắt đầu làm bài
                </button>
            </div>
            <div id="exam-questions-container" style="display: none;">
                <div id="exam-questions-list"></div>
                <div style="text-align: center; margin-top: 30px;">
                    <button class="action-btn amber" style="font-size: 16px; padding: 12px 36px;" onclick="submitExam()">
                        ✅ Nộp bài thi
                    </button>
                </div>
            </div>
            <div id="exam-result-banner" style="display: none; padding: 24px; text-align: center;"></div>
        </div>

        <!-- Mode 3: Cloze Code -->
        <div id="view-cloze" style="display: none;">
            <div id="cloze-exercises-list"></div>
        </div>
    </div>
</main>

<script>
    // Embedded Data injected by Laravel
    const STUDY_DATA = {
        categories: {!! json_encode($studySet->categories_json ?? []) !!},
        questions: {!! json_encode($studySet->questions_json ?? []) !!},
        code_exercises: {!! json_encode($studySet->code_exercises_json ?? []) !!}
    };

    let currentMode = 'practice';
    let selectedCat = 'ALL';
    let filterWrongOnly = false;
    let wrongQuestionIds = new Set(JSON.parse(localStorage.getItem('noteforge_wrong_' + '{{ $studySet->id }}') || '[]'));
    let practiceAnswers = {};

    // Exam state
    let examQuestions = [];
    let examUserAnswers = {};
    let timerInterval = null;
    let timeLeft = 15 * 60; // 15 mins

    document.getElementById('total-q-count').innerText = STUDY_DATA.questions.length;

    function saveWrongProgress() {
        localStorage.setItem('noteforge_wrong_' + '{{ $studySet->id }}', JSON.stringify(Array.from(wrongQuestionIds)));
    }

    function switchMode(mode) {
        currentMode = mode;
        document.querySelectorAll('.mode-btn').forEach((btn, idx) => {
            btn.classList.toggle('active', 
                (mode === 'practice' && idx === 0) || 
                (mode === 'exam' && idx === 1) || 
                (mode === 'cloze' && idx === 2)
            );
        });

        document.getElementById('view-practice').style.display = mode === 'practice' ? 'block' : 'none';
        document.getElementById('view-exam').style.display = mode === 'exam' ? 'block' : 'none';
        document.getElementById('view-cloze').style.display = mode === 'cloze' ? 'block' : 'none';
        document.getElementById('exam-timer-wrapper').style.display = (mode === 'exam' && timerInterval) ? 'block' : 'none';

        if (mode === 'practice') renderPractice();
        if (mode === 'cloze') renderCloze();
    }

    // -------------------------------------------------------------
    // PRACTICE MODE
    // -------------------------------------------------------------
    function renderPractice() {
        // Render Category Filter Chips
        const filtersDiv = document.getElementById('category-filters');
        let chipsHtml = `
            <button class="filter-chip ${selectedCat === 'ALL' && !filterWrongOnly ? 'active' : ''}" onclick="selectCat('ALL')">Tất cả (${STUDY_DATA.questions.length})</button>
        `;
        STUDY_DATA.categories.forEach((cat, idx) => {
            const count = STUDY_DATA.questions.filter(q => q.cat === idx).length;
            chipsHtml += `
                <button class="filter-chip ${selectedCat === idx && !filterWrongOnly ? 'active' : ''}" onclick="selectCat(${idx})">${cat} (${count})</button>
            `;
        });
        chipsHtml += `
            <button class="filter-chip danger ${filterWrongOnly ? 'active' : ''}" onclick="toggleWrongFilter()">❗ Đã làm sai (${wrongQuestionIds.size})</button>
        `;
        filtersDiv.innerHTML = chipsHtml;

        // Render Questions
        const listDiv = document.getElementById('practice-questions-list');
        let filtered = STUDY_DATA.questions.filter(q => {
            if (filterWrongOnly) return wrongQuestionIds.has(q.id);
            if (selectedCat !== 'ALL') return q.cat === selectedCat;
            return true;
        });

        if (filtered.length === 0) {
            listDiv.innerHTML = '<div style="text-align:center; padding: 40px; color: var(--text-muted);">Không có câu hỏi nào phù hợp với bộ lọc hiện tại.</div>';
            return;
        }

        let qHtml = '';
        filtered.forEach((q, idx) => {
            const userPick = practiceAnswers[q.id];
            const hasAnswered = userPick !== undefined;

            qHtml += `
                <div class="question-item" id="q-item-${q.id}">
                    <div class="question-title">
                        <span>Câu ${idx + 1}: ${q.en}</span>
                    </div>
                    <div class="options-grid">
            `;

            q.opts.forEach((opt, optIdx) => {
                let optClass = 'option-btn';
                if (hasAnswered) {
                    if (optIdx === q.ans) optClass += ' correct';
                    else if (optIdx === userPick) optClass += ' wrong';
                }

                qHtml += `
                    <button class="${optClass}" onclick="handlePracticeAnswer('${q.id}', ${optIdx}, ${q.ans})" ${hasAnswered ? 'disabled' : ''}>
                        <strong>${String.fromCharCode(65 + optIdx)}.</strong> ${opt}
                    </button>
                `;
            });

            qHtml += `</div>`;

            if (hasAnswered) {
                qHtml += `
                    <div class="explanation-box">
                        <strong>💡 Giải thích:</strong> ${q.ex || 'Đáp án theo giáo trình nội bộ.'}
                    </div>
                `;
            }

            qHtml += `</div>`;
        });

        listDiv.innerHTML = qHtml;
    }

    function selectCat(cat) {
        selectedCat = cat;
        filterWrongOnly = false;
        renderPractice();
    }

    function toggleWrongFilter() {
        filterWrongOnly = !filterWrongOnly;
        renderPractice();
    }

    function handlePracticeAnswer(qId, selectedIdx, correctIdx) {
        practiceAnswers[qId] = selectedIdx;
        if (selectedIdx !== correctIdx) {
            wrongQuestionIds.add(qId);
        } else {
            wrongQuestionIds.delete(qId);
        }
        saveWrongProgress();
        renderPractice();
    }

    // -------------------------------------------------------------
    // EXAM MODE
    // -------------------------------------------------------------
    function startExam() {
        // Random 20 questions
        const shuffled = [...STUDY_DATA.questions].sort(() => 0.5 - Math.random());
        examQuestions = shuffled.slice(0, Math.min(20, shuffled.length));
        examUserAnswers = {};
        timeLeft = 15 * 60;

        document.getElementById('exam-start-banner').style.display = 'none';
        document.getElementById('exam-result-banner').style.display = 'none';
        document.getElementById('exam-questions-container').style.display = 'block';
        document.getElementById('exam-timer-wrapper').style.display = 'block';

        renderExamQuestions();

        if (timerInterval) clearInterval(timerInterval);
        timerInterval = setInterval(() => {
            timeLeft--;
            const mins = Math.floor(timeLeft / 60).toString().padStart(2, '0');
            const secs = (timeLeft % 60).toString().padStart(2, '0');
            const timerEl = document.getElementById('exam-timer');
            timerEl.innerText = `${mins}:${secs}`;

            if (timeLeft <= 60) {
                timerEl.classList.add('urgent');
            } else {
                timerEl.classList.remove('urgent');
            }

            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                submitExam();
            }
        }, 1000);
    }

    function renderExamQuestions() {
        const listDiv = document.getElementById('exam-questions-list');
        let html = '';
        examQuestions.forEach((q, idx) => {
            html += `
                <div class="question-item">
                    <div class="question-title">Câu ${idx + 1}: ${q.en}</div>
                    <div class="options-grid">
            `;
            q.opts.forEach((opt, optIdx) => {
                const isSelected = examUserAnswers[q.id] === optIdx;
                html += `
                    <button class="option-btn ${isSelected ? 'active' : ''}" style="${isSelected ? 'border-color: var(--bg-grid); background: #E0E7FF;' : ''}" onclick="selectExamOption('${q.id}', ${optIdx})">
                        <strong>${String.fromCharCode(65 + optIdx)}.</strong> ${opt}
                    </button>
                `;
            });
            html += `</div></div>`;
        });
        listDiv.innerHTML = html;
    }

    function selectExamOption(qId, optIdx) {
        examUserAnswers[qId] = optIdx;
        renderExamQuestions();
    }

    function submitExam() {
        clearInterval(timerInterval);
        timerInterval = null;
        document.getElementById('exam-timer-wrapper').style.display = 'none';
        document.getElementById('exam-questions-container').style.display = 'none';

        let correctCount = 0;
        examQuestions.forEach(q => {
            if (examUserAnswers[q.id] === q.ans) {
                correctCount++;
            } else {
                wrongQuestionIds.add(q.id);
            }
        });
        saveWrongProgress();

        const score10 = ((correctCount / examQuestions.length) * 10).toFixed(2);
        const resultBanner = document.getElementById('exam-result-banner');
        resultBanner.style.display = 'block';

        resultBanner.innerHTML = `
            <div style="font-size: 48px; font-weight: 800; color: ${score10 >= 5 ? 'var(--correct-green)' : 'var(--wrong-red)'};">
                ${score10} / 10
            </div>
            <h2 style="color: var(--primary-navy); margin: 10px 0;">Kết quả thi thử</h2>
            <p style="color: var(--text-muted); font-size: 16px;">
                Đúng: <strong>${correctCount} / ${examQuestions.length}</strong> câu
            </p>
            <div style="margin-top: 24px; display: flex; justify-content: center; gap: 12px;">
                <button class="action-btn" onclick="startExam()">🔄 Thi lại đề khác</button>
                <button class="action-btn amber" onclick="switchMode('practice'); toggleWrongFilter();">❗ Ôn lại câu sai</button>
            </div>
        `;
    }

    // -------------------------------------------------------------
    // CLOZE CODE MODE
    // -------------------------------------------------------------
    function renderCloze() {
        const listDiv = document.getElementById('cloze-exercises-list');
        const exercises = STUDY_DATA.code_exercises || [];

        if (exercises.length === 0) {
            listDiv.innerHTML = '<div style="text-align:center; padding: 40px; color: var(--text-muted);">Không có bài tập code điền khuyết trong tài liệu này.</div>';
            return;
        }

        let html = '';
        exercises.forEach((ex, exIdx) => {
            // Parse [[token]] into input boxes
            const pattern = /\[\[(.*?)\]\]/g;
            let counter = 0;
            const answers = [];

            const parsedCode = ex.code.replace(pattern, (match, p1) => {
                const answer = p1.trim();
                answers.push(answer);
                const inputId = `cloze_${exIdx}_${counter}`;
                const widthCh = Math.max(answer.length + 2, 8);
                counter++;
                return `<input type="text" id="${inputId}" data-answer="${answer}" class="cloze-input" style="width: ${widthCh}ch" placeholder="..." autocomplete="off" autocorrect="off">`;
            });

            html += `
                <div style="margin-bottom: 30px;">
                    <h3 style="color: var(--primary-navy); margin-bottom: 8px;">Bài ${exIdx + 1}: ${ex.title}</h3>
                    <div class="code-container">${parsedCode}</div>
                    <div style="margin-top: 12px; display: flex; gap: 10px;">
                        <button class="action-btn amber" onclick="checkCloze(${exIdx}, ${counter})">Kiểm tra kết quả</button>
                        <button class="action-btn" style="background: #4B5563;" onclick="revealCloze(${exIdx}, ${counter})">💡 Xem đáp án</button>
                    </div>
                </div>
            `;
        });

        listDiv.innerHTML = html;
    }

    function checkCloze(exIdx, count) {
        for (let i = 0; i < count; i++) {
            const input = document.getElementById(`cloze_${exIdx}_${i}`);
            if (!input) continue;
            const userVal = input.value.trim().toLowerCase();
            const correctVal = input.getAttribute('data-answer').trim().toLowerCase();

            input.classList.remove('valid', 'invalid');
            if (userVal === correctVal) {
                input.classList.add('valid');
            } else {
                input.classList.add('invalid');
            }
        }
    }

    function revealCloze(exIdx, count) {
        for (let i = 0; i < count; i++) {
            const input = document.getElementById(`cloze_${exIdx}_${i}`);
            if (!input) continue;
            input.value = input.getAttribute('data-answer');
            input.classList.remove('invalid');
            input.classList.add('valid');
        }
    }

    // Initial render
    renderPractice();
</script>

</body>
</html>
