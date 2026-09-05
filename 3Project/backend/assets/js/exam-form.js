const questionSelector = document.getElementById('question-selector');
const addQuestionButton = document.getElementById('add-question');
const selectedQuestions = document.getElementById('selected-questions');
const emptyMessage = document.getElementById('no-selected-questions');
const totalMarks = document.getElementById('total-marks');
const initialQuestionsElement = document.getElementById(
    'initial-selected-questions'
);

const addedQuestionIds = new Set();
const initialSelectedQuestions = JSON.parse(
    initialQuestionsElement.textContent
);

function updateTotalMarks() {
    let total = 0;

    selectedQuestions
        .querySelectorAll('.question-weight')
        .forEach(function (input) {
            total += Number(input.value) || 0;
        });

    totalMarks.textContent = total.toFixed(2);
}

function addSelectedQuestion(questionId, text, type, weight = '') {
    questionId = String(questionId);

    if (questionId === '' || addedQuestionIds.has(questionId)) {
        return;
    }

    addedQuestionIds.add(questionId);
    emptyMessage.classList.add('d-none');

    const question = document.createElement('div');
    question.className = 'border rounded p-3 selected-question';
    question.dataset.questionId = questionId;

    const row = document.createElement('div');
    row.className = 'row align-items-center g-3';

    const informationColumn = document.createElement('div');
    informationColumn.className = 'col';

    const typeBadge = document.createElement('span');
    typeBadge.className = 'badge text-bg-secondary mb-2';
    typeBadge.textContent = type === 'TrueOrFalse'
        ? 'True / False'
        : 'Multiple Choice';

    const questionText = document.createElement('p');
    questionText.className = 'fw-semibold mb-0';
    questionText.textContent = text;

    informationColumn.append(typeBadge, questionText);

    const weightColumn = document.createElement('div');
    weightColumn.className = 'col-12 col-md-3';

    const weightInput = document.createElement('input');
    weightInput.type = 'number';
    weightInput.name = `questions[${questionId}][weight]`;
    weightInput.className = 'form-control question-weight';
    weightInput.placeholder = 'Marks';
    weightInput.min = '0.5';
    weightInput.max = '100';
    weightInput.step = '0.5';
    weightInput.value = weight;
    weightInput.required = true;
    weightInput.addEventListener('input', updateTotalMarks);

    weightColumn.appendChild(weightInput);

    const deleteColumn = document.createElement('div');
    deleteColumn.className = 'col-12 col-md-auto';

    const deleteButton = document.createElement('button');
    deleteButton.type = 'button';
    deleteButton.className = 'btn btn-outline-danger';
    deleteButton.textContent = 'Delete';

    deleteButton.addEventListener('click', function () {
        addedQuestionIds.delete(questionId);
        question.remove();

        if (addedQuestionIds.size === 0) {
            emptyMessage.classList.remove('d-none');
        }

        updateTotalMarks();
    });

    deleteColumn.appendChild(deleteButton);
    row.append(informationColumn, weightColumn, deleteColumn);
    question.appendChild(row);
    selectedQuestions.appendChild(question);
    updateTotalMarks();
}

addQuestionButton?.addEventListener('click', function () {
    const option = questionSelector.options[
        questionSelector.selectedIndex
    ];

    addSelectedQuestion(
        option.value,
        option.dataset.text,
        option.dataset.type
    );

    questionSelector.value = '';
});

initialSelectedQuestions.forEach(function (question) {
    addSelectedQuestion(
        question.id,
        question.question_text,
        question.question_type,
        question.weight
    );
});
