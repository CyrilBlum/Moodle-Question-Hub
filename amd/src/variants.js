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
 * Variants page: shows what happens to each question of a group and confirms the deletions.
 *
 * @module     qbank_questionhub/variants
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {confirmSubmit} from 'qbank_questionhub/confirm_submit';

/**
 * Marks what happens to each question of a group: kept, deleted, or kept because a quiz uses it.
 *
 * @param {Element} group The group of questions.
 */
const update = (group) => {
    const chosen = group.querySelector('input[type="radio"]:checked');
    const keepall = !chosen || chosen.value === '0';
    const merge = !keepall && chosen.value.charAt(0) === 'm';
    const target = keepall ? '' : chosen.value.replace('m', '');
    group.querySelectorAll('tr[data-qh-entry]').forEach((row) => {
        const kept = keepall || row.getAttribute('data-qh-entry') === target;
        const inuse = row.hasAttribute('data-qh-inuse');
        const merged = merge && row.hasAttribute('data-qh-source');
        row.classList.toggle('table-success', !keepall && kept);
        row.classList.toggle('table-danger', !kept && (!inuse || merged));
        row.querySelector('[data-qh-fate="merge"]').classList.toggle('d-none', !(merge && kept));
        row.querySelector('[data-qh-fate="merged"]').classList.toggle('d-none', !merged);
        row.querySelector('[data-qh-fate="delete"]').classList.toggle('d-none', kept || inuse || merged);
        row.querySelector('[data-qh-fate="inuse"]').classList.toggle('d-none', kept || !inuse);
    });
};

/**
 * Sets up the variants form.
 */
export const init = () => {
    const form = document.getElementById('qh-variants-form');
    if (!form) {
        return;
    }
    const groups = form.querySelectorAll('[data-qh-group]');
    groups.forEach((group) => {
        group.addEventListener('change', () => update(group));
        update(group);
    });
    form.querySelectorAll('[data-qh-variants]').forEach((button) => {
        button.addEventListener('click', () => {
            const hub = button.getAttribute('data-qh-variants') === 'hub';
            groups.forEach((group) => {
                // A used local question takes over the Hub question; otherwise the Hub question is kept.
                const target = hub
                    ? (group.querySelector('input[data-qh-merge]') || group.querySelector('input[data-qh-hub]'))
                    : null;
                (target || group.querySelector('input[value="0"]')).checked = true;
                update(group);
            });
        });
    });
    form.addEventListener('submit', (event) => {
        if (form.dataset.qhConfirmed) {
            return;
        }
        const count = form.querySelectorAll('tr.table-danger').length;
        if (!count) {
            event.preventDefault();
            return;
        }
        const message = form.querySelector('[data-confirm]').getAttribute('data-confirm').replace('{count}', count);
        confirmSubmit(event, form, message);
    });
};
