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
 * Asks for confirmation before a form with a data-qh-confirm message is submitted.
 *
 * @module     qbank_questionhub/confirm_submit
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Notification from 'core/notification';
import {getString} from 'core/str';

let initialised = false;

/**
 * Stops a form submission and submits the form again once the user has confirmed.
 *
 * @param {Event} event The submit event.
 * @param {HTMLFormElement} form The form.
 * @param {string} message The question to ask.
 */
export const confirmSubmit = async(event, form, message) => {
    event.preventDefault();
    const submitter = event.submitter;
    const label = await getString('confirm', 'core');
    try {
        await Notification.saveCancelPromise(label, message, label);
    } catch (error) {
        // Cancelled.
        return;
    }
    form.dataset.qhConfirmed = '1';
    form.requestSubmit(submitter);
};

/**
 * Listens for submissions of forms that need a confirmation.
 */
export const init = () => {
    if (initialised) {
        return;
    }
    initialised = true;
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (form.dataset.qhConfirm && !form.dataset.qhConfirmed) {
            confirmSubmit(event, form, form.dataset.qhConfirm);
        }
    });
};
