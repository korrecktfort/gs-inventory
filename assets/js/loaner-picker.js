document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('loaner-search');
    const toggleButton = document.querySelector('.loaner-toggle');
    const hiddenInput = document.getElementById('loaner-id');
    const loanerList = document.getElementById('loaner-list');
    const loanerInfo = document.getElementById('loaner-info');
    const editButton = document.getElementById('loaner-info-edit-btn');
    const addButton = document.getElementById('loaner-info-add-btn');
    const editor = document.getElementById('loaner-info-editor');
    const editorInput = document.getElementById('loaner-info-input');
    const saveButton = document.getElementById('loaner-info-save-btn');
    const cancelButton = document.getElementById('loaner-info-cancel-btn');
    const statusMessage = document.getElementById('loaner-info-status');
    const createEditor = document.getElementById('loaner-create-editor');
    const createNameInput = document.getElementById('loaner-create-name');
    const createInfoInput = document.getElementById('loaner-create-info');
    const createSaveButton = document.getElementById('loaner-create-save-btn');
    const createCancelButton = document.getElementById('loaner-create-cancel-btn');
    const createStatusMessage = document.getElementById('loaner-create-status');
    const picker = document.querySelector('.loaner-picker');
    const ajaxUrl = picker ? picker.dataset.ajaxUrl : '';
    const updateNonce = picker ? picker.dataset.updateNonce : '';
    const createNonce = picker ? picker.dataset.createNonce : '';

    if (!searchInput || !toggleButton || !hiddenInput || !loanerList || !loanerInfo || !editButton || !addButton || !editor || !editorInput || !saveButton || !cancelButton || !statusMessage || !createEditor || !createNameInput || !createInfoInput || !createSaveButton || !createCancelButton || !createStatusMessage || !picker || !ajaxUrl || !updateNonce || !createNonce) {
        return;
    }

    let selectedLabel = '';

    function getOptions() {
        return loanerList.querySelectorAll('.loaner-option');
    }

    closeList();

    function openList() {
        loanerList.hidden = false;
        searchInput.setAttribute('aria-expanded', 'true');
    }

    function closeList() {
        loanerList.hidden = true;
        searchInput.setAttribute('aria-expanded', 'false');
    }

    function filterOptions() {
        const query = searchInput.value.trim().toLowerCase();

        getOptions().forEach((option) => {
            const text = option.textContent.trim().toLowerCase();
            const isMatch = query.length === 0 || text.includes(query);
            option.classList.toggle('is-hidden', !isMatch);
        });
    }

    function showAllOptions() {
        getOptions().forEach((option) => {
            option.classList.remove('is-hidden');
        });
    }

    function renderLoanerInfo(infoText) {
        const text = (infoText || '').trim();

        if (text === '') {
            loanerInfo.textContent = 'No additional info available.';
            loanerInfo.classList.add('is-empty');
            return;
        }

        loanerInfo.textContent = text;
        loanerInfo.classList.remove('is-empty');
    }

    function getSelectedOption() {
        const selectedId = hiddenInput.value;

        if (!selectedId) {
            return null;
        }

        return loanerList.querySelector('.loaner-option[data-loaner-id="' + CSS.escape(selectedId) + '"]');
    }

    function setStatus(message, isError) {
        statusMessage.textContent = message || '';
        statusMessage.classList.toggle('is-error', Boolean(isError));
    }

    function closeEditor() {
        editor.hidden = true;
        setStatus('');
    }

    function setCreateStatus(message, isError) {
        createStatusMessage.textContent = message || '';
        createStatusMessage.classList.toggle('is-error', Boolean(isError));
    }

    function closeCreateEditor() {
        createEditor.hidden = true;
        setCreateStatus('');
    }

    function selectLoanerOption(option) {
        if (!option) {
            return;
        }

        const loanerId = option.dataset.loanerId || '';
        const info = option.dataset.loanerInfo || '';
        const loanerTitle = option.textContent.trim();

        hiddenInput.value = loanerId;
        selectedLabel = loanerTitle;
        searchInput.value = selectedLabel;
        renderLoanerInfo(info);
        editButton.disabled = false;
        closeEditor();
        picker.classList.add('has-selection');

        hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));

        getOptions().forEach((item) => item.classList.remove('is-selected'));
        option.classList.add('is-selected');

        closeList();
    }

    searchInput.addEventListener('focus', function () {
        if (selectedLabel !== '' && searchInput.value.trim() === selectedLabel) {
            searchInput.value = '';
        }

        openList();
        showAllOptions();
    });

    toggleButton.addEventListener('click', function () {
        if (loanerList.hidden) {
            openList();
            searchInput.focus();
            filterOptions();
            return;
        }

        closeList();
    });

    searchInput.addEventListener('input', function () {
        openList();
        filterOptions();
    });

    searchInput.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            if (selectedLabel !== '') {
                searchInput.value = selectedLabel;
            }

            closeList();
            searchInput.blur();
        }
    });

    searchInput.addEventListener('blur', function () {
        window.setTimeout(function () {
            if (document.activeElement && picker.contains(document.activeElement)) {
                return;
            }

            if (searchInput.value.trim() === '' && selectedLabel !== '') {
                searchInput.value = selectedLabel;
            }
        }, 0);
    });

    loanerList.addEventListener('click', function (event) {
        const option = event.target.closest('.loaner-option');

        if (!option) {
            return;
        }

        selectLoanerOption(option);
        closeCreateEditor();
    });

    document.addEventListener('click', function (event) {
        if (!picker.contains(event.target)) {
            closeList();
        }
    });

    editButton.addEventListener('click', function () {
        const selectedOption = getSelectedOption();

        if (!selectedOption) {
            setStatus('Select a loaner first.', true);
            return;
        }

        editorInput.value = (selectedOption.dataset.loanerInfo || '').trim();
        editor.hidden = false;
        setStatus('');
        editorInput.focus();
    });

    cancelButton.addEventListener('click', function () {
        closeEditor();
    });

    saveButton.addEventListener('click', function () {
        const loanerId = hiddenInput.value;

        if (!loanerId) {
            setStatus('Select a loaner first.', true);
            return;
        }

        const infoValue = editorInput.value.trim();
        const payload = new URLSearchParams({
            action: 'gs_update_loaner_info',
            nonce: updateNonce,
            loaner_id: loanerId,
            info: infoValue,
        });

        saveButton.disabled = true;
        setStatus('Saving...', false);

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
            body: payload.toString(),
        })
            .then((response) => response.json())
            .then((result) => {
                if (!result || !result.success) {
                    throw new Error((result && result.data && result.data.message) ? result.data.message : 'Could not save info.');
                }

                const selectedOption = getSelectedOption();
                if (selectedOption) {
                    selectedOption.dataset.loanerInfo = infoValue;
                }

                renderLoanerInfo(infoValue);
                setStatus('Saved.', false);
                closeEditor();
            })
            .catch((error) => {
                setStatus(error.message || 'Save failed.', true);
            })
            .finally(() => {
                saveButton.disabled = false;
            });
    });

    addButton.addEventListener('click', function () {
        closeEditor();
        createEditor.hidden = false;
        createNameInput.value = '';
        createInfoInput.value = '';
        setCreateStatus('');
        createNameInput.focus();
    });

    createCancelButton.addEventListener('click', function () {
        closeCreateEditor();
    });

    createSaveButton.addEventListener('click', function () {
        const nameValue = createNameInput.value.trim();
        const infoValue = createInfoInput.value.trim();

        if (nameValue === '') {
            setCreateStatus('Loaner name is required.', true);
            createNameInput.focus();
            return;
        }

        const payload = new URLSearchParams({
            action: 'gs_create_loaner',
            nonce: createNonce,
            name: nameValue,
            info: infoValue,
        });

        createSaveButton.disabled = true;
        setCreateStatus('Saving...', false);

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
            body: payload.toString(),
        })
            .then((response) => response.json())
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

                loanerList.appendChild(newOption);
                showAllOptions();
                selectLoanerOption(newOption);
                closeCreateEditor();
                setStatus('New loaner created.', false);
            })
            .catch((error) => {
                setCreateStatus(error.message || 'Create failed.', true);
            })
            .finally(() => {
                createSaveButton.disabled = false;
            });
    });

    renderLoanerInfo('');
    editButton.disabled = true;
    closeCreateEditor();
});
