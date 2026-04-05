<form method="post" class="loan-form">
    <?php wp_nonce_field('create_loan_action', 'create_loan_nonce'); ?>
    
    <?php get_template_part('template-parts/loans/form', 'loan-meta'); ?>
    <?php get_template_part('template-parts/loans/item', 'list'); ?>

    <div class="loan-form-submit">
        <button class="btn-submit ui-button" type="submit" name="create_loan_submit" value="1" disabled>
            Create Loan
        </button>

        <div class="loan-form-requirements" aria-live="polite">
            <p class="loan-form-requirements-title">Checklist:</p>
            <ul class="loan-form-missing-list"></ul>
        </div>
    </div>
</form>

<script>
function initLoanFormChecklist() {
    const form = document.querySelector('.loan-form');

    if (!form) {
        return;
    }

    const submitButton = form.querySelector('.btn-submit');
    const missingList = form.querySelector('.loan-form-missing-list');
    const requirementsBox = form.querySelector('.loan-form-requirements');
    const titleInput = form.querySelector('#loan_title');
    const loanerInput = form.querySelector('#loaner-id');
    const loanerSearchInput = form.querySelector('#loaner-search');
    const startDateInput = form.querySelector('#start_date');
    const dueDateInput = form.querySelector('#due_date');

    if (!submitButton || !missingList || !requirementsBox || !titleInput || !loanerInput || !startDateInput || !dueDateInput) {
        return;
    }

    function hasSelectedItems() {
        const quantityInputs = form.querySelectorAll('.qty-input');

        return Array.from(quantityInputs).some((input) => {
            const quantity = parseInt(input.value || 0, 10);
            return !isNaN(quantity) && quantity > 0;
        });
    }

    function isDueDateOrderValid() {
        if ((startDateInput.value || '').trim() === '' || (dueDateInput.value || '').trim() === '') {
            return true;
        }

        return dueDateInput.value >= startDateInput.value;
    }

    function syncDueDateConstraints() {
        const startValue = (startDateInput.value || '').trim();

        dueDateInput.min = startValue;

        if (startValue !== '' && (dueDateInput.value || '').trim() !== '' && dueDateInput.value < startValue) {
            dueDateInput.value = startValue;
        }

        if (!isDueDateOrderValid()) {
            dueDateInput.setCustomValidity('Due date cannot be earlier than start date.');
        } else {
            dueDateInput.setCustomValidity('');
        }
    }

    function getRequirements() {
        return [
            {
                label: 'Loan name',
                complete: titleInput.value.trim() !== '',
                focusTarget: titleInput,
            },
            {
                label: 'Loaner',
                complete: (loanerInput.value || '').trim() !== '',
                focusTarget: loanerSearchInput || loanerInput,
            },
            {
                label: 'Start date',
                complete: (startDateInput.value || '').trim() !== '',
                focusTarget: startDateInput,
            },
            {
                label: 'Due date',
                complete: (dueDateInput.value || '').trim() !== '',
                focusTarget: dueDateInput,
            },
            {
                label: 'Due date is not earlier than start date',
                complete: isDueDateOrderValid(),
                focusTarget: dueDateInput,
            },
            {
                label: 'At least one item',
                complete: hasSelectedItems(),
                focusTarget: form.querySelector('.item-list'),
            },
        ];
    }

    function renderFormRequirements() {
        const requirements = getRequirements();
        const missing = requirements.filter((item) => item.complete !== true);

        submitButton.disabled = missing.length > 0;
        requirementsBox.hidden = false;
        missingList.innerHTML = requirements
            .map((item) => '<li class="' + (item.complete ? 'is-complete' : 'is-missing') + '">' + item.label + '</li>')
            .join('');
    }

    form.addEventListener('submit', function (event) {
        syncDueDateConstraints();

        const requirements = getRequirements();
        const firstMissing = requirements.find((item) => item.complete !== true);

        if (!firstMissing) {
            return;
        }

        event.preventDefault();
        renderFormRequirements();

        if (firstMissing.focusTarget instanceof HTMLElement) {
            firstMissing.focusTarget.focus();

            if (typeof firstMissing.focusTarget.scrollIntoView === 'function') {
                firstMissing.focusTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });

    startDateInput.addEventListener('input', function () {
        syncDueDateConstraints();
        renderFormRequirements();
    });

    dueDateInput.addEventListener('input', function () {
        syncDueDateConstraints();
        renderFormRequirements();
    });

    form.addEventListener('input', renderFormRequirements);
    form.addEventListener('change', renderFormRequirements);
    syncDueDateConstraints();
    renderFormRequirements();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLoanFormChecklist);
} else {
    initLoanFormChecklist();
}
</script>