// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Question Hub page: bank selector, category editing, package preview and the import dialog.
 *
 * @module     qbank_questionhub/hub_browser
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalEvents from 'core/modal_events';
import ModalForm from 'core_form/modalform';
import Notification from 'core/notification';
import {getString} from 'core/str';

/**
 * Text content of an HTML fragment.
 *
 * @param {string} html
 * @returns {string}
 */
const plainText = (html) => {
    const doc = new DOMParser().parseFromString(html || '', 'text/html');
    return (doc.body.textContent || '').trim();
};

/**
 * Opens core's own category form (same form as Question bank > Categories) with a naming note for Question Hub links.
 *
 * @param {HTMLElement} button The edit button of the category.
 * @param {object} config Page configuration.
 */
const editCategory = async(button, config) => {
    const args = {
        contextid: config.bankcontextid,
        categoryid: button.dataset.categoryid,
        sortorder: button.dataset.sortorder,
        questioncount: button.dataset.questioncount,
        parent: `${button.dataset.parent},${config.bankcontextid}`,
    };
    // Moodle 5.0 banks live in a mod_qbank module, 4.5 banks in the course context.
    if (config.cmid) {
        args.cmid = config.cmid;
    } else {
        args.courseid = config.courseid;
    }
    const modalForm = new ModalForm({
        formClass: 'qbank_managecategories\\form\\question_category_edit_form',
        args,
        modalConfig: {title: button.getAttribute('title'), large: true},
        saveButtonText: await getString('savechanges', 'core'),
        returnFocus: button,
    });
    const addNote = () => {
        const form = modalForm.getFormNode();
        if (form && !form.parentNode.querySelector('.qh-category-naming-note')) {
            const note = document.getElementById('qh-category-naming-note').content.cloneNode(true);
            form.parentNode.insertBefore(note, form);
        }
    };
    // LOADED fires before the form is in the modal; the body renders after it and after each validation error.
    modalForm.addEventListener(modalForm.events.LOADED, () => {
        modalForm.modal.getRoot().on(ModalEvents.bodyRendered, addNote);
        addNote();
    });
    modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => window.location.reload());
    modalForm.show();
};

/**
 * Builds the list of preview questions.
 *
 * @param {Array} questions Questions from the Hub.
 * @returns {Promise<HTMLElement>}
 */
const previewList = async(questions) => {
    const untitled = await getString('preview_untitled', 'qbank_questionhub');
    const list = document.createElement('div');
    list.className = 'list-group';
    questions.forEach((question, index) => {
        const item = document.createElement('div');
        item.className = 'list-group-item';
        const head = document.createElement('div');
        head.className = 'd-flex w-100 justify-content-between';
        const heading = document.createElement('h6');
        heading.className = 'mb-1';
        heading.textContent = `${index + 1}. ${question.name || untitled}`;
        const badge = document.createElement('span');
        badge.className = 'badge badge-secondary bg-secondary';
        badge.textContent = question.type || '';
        head.append(heading, badge);
        const text = document.createElement('div');
        text.className = 'text-muted mt-2 small';
        text.textContent = plainText(question.questiontextHtml || question.questiontext);
        item.append(head, text);
        list.append(item);
    });
    return list;
};

/**
 * Loads a package preview from the Hub into the preview modal.
 *
 * @param {HTMLElement} button The preview button.
 * @param {string} hubUrl Base URL of the Hub.
 */
const showPreview = async(button, hubUrl) => {
    const body = document.getElementById('previewModalBody');
    document.getElementById('previewModalLabel').textContent =
        await getString('preview_title', 'qbank_questionhub', button.dataset.title);
    body.textContent = await getString('preview_loading', 'qbank_questionhub');
    try {
        const id = encodeURIComponent(button.dataset.packageid);
        const version = encodeURIComponent(button.dataset.version);
        const response = await fetch(`${hubUrl}/api/v1/packages/${id}/preview?version=${version}`);
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || `HTTP ${response.status}`);
        }
        if (!data.questions || data.questions.length === 0) {
            body.textContent = await getString('preview_empty', 'qbank_questionhub');
            return;
        }
        const list = await previewList(data.questions);
        body.textContent = '';
        body.append(list);
    } catch (error) {
        body.textContent = await getString('preview_unavailable', 'qbank_questionhub', error.message);
    }
};

/**
 * Sets up the import dialog.
 */
const initImport = () => {
    const targetCategory = document.getElementById('importTargetCat');
    if (!targetCategory) {
        return;
    }
    const renameOption = document.getElementById('renameCategoryOption');
    const renameRadio = document.getElementById('categoryModeRename');
    const keepRadio = document.getElementById('categoryModeKeep');
    const isSet = (value) => value === 'true' || value === '1';
    let packageRootName = '';

    const updatePlacementOptions = () => {
        const selected = targetCategory.options[targetCategory.selectedIndex];
        const canRename = packageRootName !== '' && !isSet(selected.getAttribute('data-is-root')) &&
            isSet(selected.getAttribute('data-can-rename'));
        renameOption.classList.toggle('d-none', !canRename);
        if (!canRename && renameRadio.checked) {
            keepRadio.checked = true;
        }
    };
    targetCategory.addEventListener('change', updatePlacementOptions);

    document.querySelectorAll('.qh-import-btn').forEach((button) => {
        button.addEventListener('click', async() => {
            const {title, version, packageid} = button.dataset;
            packageRootName = button.dataset.packageroot || '';
            // The original first, then the variants the account may see; the choice sets what is imported.
            const original = await getString('import_variant_original', 'qbank_questionhub');
            let options = [{id: packageid, version, title, label: `${original} ${version}`}];
            try {
                options = options.concat(JSON.parse(button.dataset.variants || '[]'));
            } catch (error) {
                // No variants.
            }
            const select = document.getElementById('importVariant');
            select.innerHTML = '';
            options.forEach((option, index) => {
                const element = document.createElement('option');
                element.value = String(index);
                element.textContent = option.label;
                select.append(element);
            });
            const apply = () => {
                const chosen = options[Number(select.value) || 0];
                document.getElementById('importModalPackageId').value = chosen.id;
                document.getElementById('importModalVersion').value = chosen.version;
                document.getElementById('importModalTitle').value = chosen.title;
                document.getElementById('importModalTitleText').textContent = `${chosen.title} (${chosen.version})`;
            };
            select.onchange = apply;
            document.getElementById('importVariantGroup').classList.toggle('d-none', options.length < 2);
            apply();
            document.getElementById('packageRootName').textContent = packageRootName;
            updatePlacementOptions();
        });
    });
};

/**
 * Sets up the page. The buttons open the modals through data attributes (Bootstrap 4 and 5); this only fills them.
 *
 * @param {object} config
 * @param {number} config.bankcontextid Context of the question bank.
 * @param {number} config.cmid Course module of the bank, 0 on Moodle 4.5.
 * @param {number} config.courseid Course.
 * @param {string} config.huburl Base URL of the Hub.
 */
export const init = (config) => {
    document.querySelector('[data-qh-bank-select]')?.addEventListener('change', (event) => {
        window.location.href = event.target.value;
    });
    document.addEventListener('click', (event) => {
        const editButton = event.target.closest('.qh-edit-category');
        if (editButton) {
            editCategory(editButton, config).catch(Notification.exception);
        }
    });
    document.querySelectorAll('.qh-preview-btn').forEach((button) => {
        button.addEventListener('click', () => showPreview(button, config.huburl).catch(Notification.exception));
    });
    // The settings form lives in the category list; bring it into view when opened from a Browse card.
    document.querySelectorAll('.qh-settings-toggle').forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.querySelector(button.getAttribute('data-target'));
            if (target && !button.closest('.list-group-item')) {
                setTimeout(() => target.scrollIntoView({behavior: 'smooth', block: 'center'}), 350);
            }
        });
    });
    initImport();
};
