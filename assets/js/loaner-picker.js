function initLoanerPicker() {
    const ui = getLoanerPickerElements();

    if (!ui) {
        return;
    }

    const state = {
        selectedLabel: '',
    };

    const helpers = createSharedHelpers(ui, state);

    // Section 1: picker/search/select flow.
    bindPickerSelectionEvents(ui, state, helpers);

    // Section 2: edit selected loaner info flow.
    bindLoanerInfoEditorEvents(ui, helpers);

    // Section 3: create loaner flow.
    bindCreateLoanerEvents(ui, helpers);

    helpers.renderLoanerInfo('');
    ui.editButton.disabled = true;
    helpers.closeCreateEditor();
    helpers.closeList();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLoanerPicker);
} else {
    initLoanerPicker();
}

function getLoanerPickerElements() {
    const ui = {
        searchInput: document.getElementById('loaner-search'),
        toggleButton: document.querySelector('.loaner-toggle'),
        hiddenInput: document.getElementById('loaner-id'),
        loanerList: document.getElementById('loaner-list'),
        loanerInfo: document.getElementById('loaner-info'),
        editButton: document.getElementById('loaner-info-edit-btn'),
        addButton: document.getElementById('loaner-info-add-btn'),
        editor: document.getElementById('loaner-info-editor'),
        editorInput: document.getElementById('loaner-info-input'),
        saveButton: document.getElementById('loaner-info-save-btn'),
        cancelButton: document.getElementById('loaner-info-cancel-btn'),
        statusMessage: document.getElementById('loaner-info-status'),
        createEditor: document.getElementById('loaner-create-editor'),
        createNameInput: document.getElementById('loaner-create-name'),
        createInfoInput: document.getElementById('loaner-create-info'),
        createSaveButton: document.getElementById('loaner-create-save-btn'),
        createCancelButton: document.getElementById('loaner-create-cancel-btn'),
        createStatusMessage: document.getElementById('loaner-create-status'),
        picker: document.querySelector('.loaner-picker'),
    };

    const ajaxUrl = ui.picker ? ui.picker.dataset.ajaxUrl : '';
    const updateNonce = ui.picker ? ui.picker.dataset.updateNonce : '';
    const createNonce = ui.picker ? ui.picker.dataset.createNonce : '';

    if (!ui.searchInput || !ui.toggleButton || !ui.hiddenInput || !ui.loanerList || !ui.loanerInfo || !ui.editButton || !ui.addButton || !ui.editor || !ui.editorInput || !ui.saveButton || !ui.cancelButton || !ui.statusMessage || !ui.createEditor || !ui.createNameInput || !ui.createInfoInput || !ui.createSaveButton || !ui.createCancelButton || !ui.createStatusMessage || !ui.picker || !ajaxUrl || !updateNonce || !createNonce) {
        return null;
    }

    ui.ajaxUrl = ajaxUrl;
    ui.updateNonce = updateNonce;
    ui.createNonce = createNonce;

    return ui;
}

function createSharedHelpers(ui, state) {
    function getOptions() {
        return ui.loanerList.querySelectorAll('.loaner-option');
    }

    function openList() {
        ui.loanerList.hidden = false;
        ui.searchInput.setAttribute('aria-expanded', 'true');
    }

    function closeList() {
        ui.loanerList.hidden = true;
        ui.searchInput.setAttribute('aria-expanded', 'false');
    }

    function showAllOptions() {
        getOptions().forEach((option) => {
            option.classList.remove('is-hidden');
        });
    }

    function filterOptions() {
        const query = ui.searchInput.value.trim().toLowerCase();

        getOptions().forEach((option) => {
            const text = option.textContent.trim().toLowerCase();
            const isMatch = query.length === 0 || text.includes(query);
            option.classList.toggle('is-hidden', !isMatch);
        });
    }

    function renderLoanerInfo(infoText) {
        const text = (infoText || '').trim();

        if (text === '') {
            ui.loanerInfo.textContent = 'No additional info available.';
            ui.loanerInfo.classList.add('is-empty');
            return;
        }

        ui.loanerInfo.textContent = text;
        ui.loanerInfo.classList.remove('is-empty');
    }

    function getSelectedOption() {
        const selectedId = ui.hiddenInput.value;

        if (!selectedId) {
            return null;
        }

        return ui.loanerList.querySelector('.loaner-option[data-loaner-id="' + CSS.escape(selectedId) + '"]');
    }

    function setStatus(message, isError) {
        ui.statusMessage.textContent = message || '';
        ui.statusMessage.classList.toggle('is-error', Boolean(isError));
    }

    function setCreateStatus(message, isError) {
        ui.createStatusMessage.textContent = message || '';
        ui.createStatusMessage.classList.toggle('is-error', Boolean(isError));
    }

    function closeEditor() {
        ui.editor.hidden = true;
        setStatus('');
    }

    function closeCreateEditor() {
        ui.createEditor.hidden = true;
        setCreateStatus('');
    }

    function dispatchLoanerChange() {
        ui.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        ui.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function selectLoanerOption(option) {
        if (!option) {
            return;
        }

        const loanerId = option.dataset.loanerId || '';
        const info = option.dataset.loanerInfo || '';
        const loanerTitle = option.textContent.trim();

        ui.hiddenInput.value = loanerId;
        state.selectedLabel = loanerTitle;
        ui.searchInput.value = state.selectedLabel;
        renderLoanerInfo(info);
        ui.editButton.disabled = false;
        closeEditor();
        ui.picker.classList.add('has-selection');

        dispatchLoanerChange();
        getOptions().forEach((item) => item.classList.remove('is-selected'));
        option.classList.add('is-selected');
        closeList();
    }

    function postFormEncoded(payload) {
        return fetch(ui.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
            body: payload.toString(),
        }).then((response) => response.json());
    }

    return {
        getOptions,
        openList,
        closeList,
        showAllOptions,
        filterOptions,
        renderLoanerInfo,
        getSelectedOption,
        setStatus,
        setCreateStatus,
        closeEditor,
        closeCreateEditor,
        selectLoanerOption,
        postFormEncoded,
    };
}

function bindPickerSelectionEvents(ui, state, helpers) {
    ui.searchInput.addEventListener('focus', function () {
        if (state.selectedLabel !== '' && ui.searchInput.value.trim() === state.selectedLabel) {
            ui.searchInput.value = '';
        }

        helpers.openList();
        helpers.showAllOptions();
    });

    ui.toggleButton.addEventListener('click', function () {
        if (ui.loanerList.hidden) {
            helpers.openList();
            ui.searchInput.focus();
            helpers.filterOptions();
            return;
        }

        helpers.closeList();
    });

    ui.searchInput.addEventListener('input', function () {
        helpers.openList();
        helpers.filterOptions();
    });

    ui.searchInput.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        if (state.selectedLabel !== '') {
            ui.searchInput.value = state.selectedLabel;
        }

        helpers.closeList();
        ui.searchInput.blur();
    });

    ui.searchInput.addEventListener('blur', function () {
        window.setTimeout(function () {
            if (document.activeElement && ui.picker.contains(document.activeElement)) {
                return;
            }

            if (ui.searchInput.value.trim() === '' && state.selectedLabel !== '') {
                ui.searchInput.value = state.selectedLabel;
            }
        }, 0);
    });

    ui.loanerList.addEventListener('click', function (event) {
        const option = event.target.closest('.loaner-option');

        if (!option) {
            return;
        }

        helpers.selectLoanerOption(option);
        helpers.closeCreateEditor();
    });

    document.addEventListener('click', function (event) {
        if (!ui.picker.contains(event.target)) {
            helpers.closeList();
        }
    });
}

function bindLoanerInfoEditorEvents(ui, helpers) {
    ui.editButton.addEventListener('click', function () {
        const selectedOption = helpers.getSelectedOption();

        if (!selectedOption) {
            helpers.setStatus('Select a loaner first.', true);
            return;
        }

        ui.editorInput.value = (selectedOption.dataset.loanerInfo || '').trim();
        ui.editor.hidden = false;
        helpers.setStatus('');
        ui.editorInput.focus();
    });

    ui.cancelButton.addEventListener('click', function () {
        helpers.closeEditor();
    });

    ui.saveButton.addEventListener('click', function () {
        const loanerId = ui.hiddenInput.value;

        if (!loanerId) {
            helpers.setStatus('Select a loaner first.', true);
            return;
        }

        const infoValue = ui.editorInput.value.trim();
        const payload = new URLSearchParams({
            action: 'gs_update_loaner_info',
            nonce: ui.updateNonce,
            loaner_id: loanerId,
            info: infoValue,
        });

        ui.saveButton.disabled = true;
        helpers.setStatus('Saving...', false);

        helpers.postFormEncoded(payload)
            .then((result) => {
                if (!result || !result.success) {
                    throw new Error((result && result.data && result.data.message) ? result.data.message : 'Could not save info.');
                }

                const selectedOption = helpers.getSelectedOption();
                if (selectedOption) {
                    selectedOption.dataset.loanerInfo = infoValue;
                }

                helpers.renderLoanerInfo(infoValue);
                helpers.setStatus('Saved.', false);
                helpers.closeEditor();
            })
            .catch((error) => {
                helpers.setStatus(error.message || 'Save failed.', true);
            })
            .finally(() => {
                ui.saveButton.disabled = false;
            });
    });
}

function bindCreateLoanerEvents(ui, helpers) {
    ui.addButton.addEventListener('click', function () {
        helpers.closeEditor();
        ui.createEditor.hidden = false;
        ui.createNameInput.value = '';
        ui.createInfoInput.value = '';
        helpers.setCreateStatus('');
        ui.createNameInput.focus();
    });

    ui.createCancelButton.addEventListener('click', function () {
        helpers.closeCreateEditor();
    });

    ui.createSaveButton.addEventListener('click', function () {
        const nameValue = ui.createNameInput.value.trim();
        const infoValue = ui.createInfoInput.value.trim();

        if (nameValue === '') {
            helpers.setCreateStatus('Loaner name is required.', true);
            ui.createNameInput.focus();
            return;
        }

        const payload = new URLSearchParams({
            action: 'gs_create_loaner',
            nonce: ui.createNonce,
            name: nameValue,
            info: infoValue,
        });

        ui.createSaveButton.disabled = true;
        helpers.setCreateStatus('Saving...', false);

        helpers.postFormEncoded(payload)
            .then((result) => {
                if (!result || !result.success || !result.data || !result.data.loaner) {
                    throw new Error((result && result.data && result.data.message) ? result.data.message : 'Could not create loaner.');
                }

                const createdLoaner = result.data.loaner;
                const newOption = document.createElement('button');
                newOption.type = 'button';
                newOption.className = 'loaner-option';
                newOption.dataset.loanerId = String(createdLoaner.id);
                newOption.dataset.loanerInfo = String(createdLoaner.info || '');
                newOption.textContent = String(createdLoaner.title || nameValue);

                ui.loanerList.appendChild(newOption);
                helpers.showAllOptions();
                helpers.selectLoanerOption(newOption);
                helpers.closeCreateEditor();
                helpers.setStatus('New loaner created.', false);
            })
            .catch((error) => {
                helpers.setCreateStatus(error.message || 'Create failed.', true);
            })
            .finally(() => {
                ui.createSaveButton.disabled = false;
            });
    });
}
