const questionType = document.getElementById('question_type');
const multipleChoiceEditor = document.getElementById('multiple-choice-editor');
const trueFalseEditor = document.getElementById('true-false-editor');
const choicesContainer = document.getElementById('choices');
const addChoiceButton = document.getElementById('add-choice');

let nextChoiceIndex = Number(choicesContainer.dataset.nextIndex);

function updateQuestionEditor() {
    const isTrueFalse = questionType.value === 'TrueOrFalse';

    multipleChoiceEditor.hidden = isTrueFalse;
    trueFalseEditor.hidden = !isTrueFalse;

    multipleChoiceEditor
        .querySelectorAll('input, button')
        .forEach(function (element) {
            element.disabled = isTrueFalse;
        });

    trueFalseEditor
        .querySelectorAll('input')
        .forEach(function (input) {
            input.disabled = !isTrueFalse;
            input.required = isTrueFalse;
        });
}

function updateChoiceLabels() {
    const rows = choicesContainer.querySelectorAll('.choice-row');

    rows.forEach(function (row, index) {
        row.querySelector('.choice-number').textContent = index + 1;
        row.querySelector('input[type="text"]').placeholder =
            `Choice ${index + 1}`;
    });
}

questionType.addEventListener('change', updateQuestionEditor);

addChoiceButton.addEventListener('click', function () {
    const index = nextChoiceIndex++;
    const row = document.createElement('div');

    row.className = 'choice-row';
    row.innerHTML = `
        <div class="input-group">
            <span class="input-group-text choice-number"></span>
            <input
                name="choices[${index}][text]"
                type="text"
                class="form-control"
                placeholder="Choice"
                required
            >
            <span class="input-group-text">
                <input
                    name="choices[${index}][correct]"
                    type="checkbox"
                    value="1"
                    class="form-check-input mt-0 me-2"
                    aria-label="Mark choice as correct"
                >
                Correct
            </span>
            <button
                type="button"
                class="btn btn-outline-danger remove-choice"
            >
                Remove
            </button>
        </div>
    `;

    choicesContainer.appendChild(row);
    updateChoiceLabels();
});

choicesContainer.addEventListener('click', function (event) {
    if (!event.target.classList.contains('remove-choice')) {
        return;
    }

    const rows = choicesContainer.querySelectorAll('.choice-row');

    if (rows.length <= 2) {
        alert('A question must have at least two choices.');
        return;
    }

    event.target.closest('.choice-row').remove();
    updateChoiceLabels();
});

updateChoiceLabels();
updateQuestionEditor();
